<?php 

require_once('../lib/botbase.php');
require_once('config.php');
//require_once('lang_' . VB_CONF::lang . '.php');
require_once('lang_ru.php');


class McViberSubscriber extends McSubscriber {


	public function getType() : int {
	
		return 1; //MSGR_VIBER
	
	}

}


class McViberBotInreface extends McBotInreface {

	protected ?object $input;
	protected string $auth_token;
	protected string $send_name;
	protected bool $debug;


	public function __construct(array $config = array()) {

		$this->input		= null;
		$this->auth_token	= (string)($config['auth_token']);
		$this->send_name	= (string)($config['send_name']);
		$this->debug		= (bool)($config['debug']);
		
		$this->loadInput();
	}	


	public function loadInput() : ?object {
	
		$request = file_get_contents("php://input");
		$this->input = (object)(json_decode($request));
		$this->log('IN: ' . $request);
		return $this->input;

	}
	

	public function getInput() : ?object {
	
		return $this->input;
	}

	
	public function getEvent() : string {
	
		if ((isset($this->input)) && (isset($this->input->event))) {
			return (string)($this->input->event);
		} else {
			return '';
		}

	}


	public function sendReq(object $data, bool $echo = false) : bool {
	
		$request_data = json_encode($data);
		$this->log('OUT:' . $request_data);
	
		if (!$echo) {

			$ch = curl_init("https://chatapi.viber.com/pa/send_message");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			/*$response =*/ curl_exec($ch);
			$err = curl_error($ch);
			curl_close($ch);
			if($err) {return false; /*$err*/}
			else {return true; /*$response*/;}

		} else {
			echo $request_data;
			return true;
		}
		
	}


	public function sendMsg(string $receiver_id, string $text, string $type, ?string $tracking_data = null, ?array $arr_asoc = null, bool $echo = false) : bool {
	

		if($arr_asoc != null) {
			$data = (object)$arr_asoc;
		} else {
   	 		$data = new stdClass();
		}
    	
		$data->auth_token = $this->auth_token;
		$data->receiver = $receiver_id;
		$data->type = $type;
		if($text != null) {$data->text = $text;}
		if($tracking_data != null) {$data->tracking_data = $tracking_data;}
		$data->min_api_version = 3;
	
		$sender = new stdClass(); 
		$sender->name = $this->send_name;
		$data->sender = $sender; 
	
		return $this->sendReq($data, $echo);
	}


	public function sendText(string $receiver_id, string $text, bool $echo = false) : bool {
	
		return $this->sendMsg($receiver_id, $text, "text", null, null, $echo);
		
	}


	public function sendKeyboard(string $receiver_id, string $text, array $buttons, bool $echo = false) : bool {

  	  	$data = new stdClass();
    	
		$data->auth_token = $this->auth_token;
		$data->receiver = $receiver_id;
		$data->min_api_version = 3;
		$data->type = "text";
		if ($text) {$data->text = $text;}
		
		$data->keyboard = new stdClass();
		$data->keyboard->Type = "keyboard";
		$data->keyboard->DefaultHeight = true;
		$data->keyboard->Buttons = $buttons;

		return $this->sendReq($data, $echo);
	}

	
	public function sendWebhookResponse() : bool {

		$webhook_response = new stdClass();
		$webhook_response->status = 0;
		$webhook_response->status_message = "ok";
	//	$webhook_response->event_types = 'subscribed, unsubscribed, webhook, conversation_started, client_status, action, delivered, failed, message, seen';
		echo json_encode($webhook_response);
		return true;
		
	}


	protected function log(string $data) : void {

		if ($this->debug) {file_put_contents('data/vb_log.txt', date('Y-m-d H:i:s') . ' - ' . $data . "\n", FILE_APPEND);}
	}
	

}	


class McViberBotController extends McBotController {

	protected ?McSubscriber $subscriber;
	protected string $admin_id;	
	
	
	public function __construct(array $config = array()) {

		parent::__construct($config);
		
		$this->admin_id = (string)($config['admin_id']);
		$this->subscriber = null;
		
	}


	public function setSubscriber(McSubscriber $subscriber) : void {
	
		$this->subscriber = $subscriber;
	
	}
	
	
	protected function onSubscribed() : bool {
	
		if ((!$this->botInterface) || (!$this->subscriber)) {return false;}

		$input = $this->botInterface->getInput();
		
		if ((!is_object($input)) || (!is_object($input->user)) || (!isset($input->user->id))) {return false;}
		$this->subscriber->checkSubscriber((string)$input->user->id, (string)$input->user->name);
		
		$this->botInterface->sendText((string)$input->user->id, VB_MSG::SUBSCRIBE_MSG);
		
		if (!$this->subscriber->getRealName()) {
			$this->subscriber->RequestRealName();
			$this->botInterface->sendText((string)$input->user->id, VB_MSG::NAME_REQ_MSG);
			return true;
		}

		if (!$this->subscriber->getPhone()) {
			$btn = new stdClass();
			$btn->ActionType = "share-phone";
			$btn->ActionBody = "reply";
			$btn->Text = VB_MSG::PHONE_REQ_BTN;
			$btn->TextSize = "regular";
			$btn->BgColor = "#d5fbd5";
			$this->botInterface->sendKeyboard((string)$input->user->id, sprintf(VB_MSG::PHONE_REQ_MSG, $this->subscriber->getRealName()), array($btn));
			return true;
		}

		return true;
		
	}
	

	protected function onUnsubscribed() : bool {

		if ((!$this->subscriber) || (!$this->botInterface)) {return false;}

		$input = $this->botInterface->getInput();
		if ((!is_object($input)) || (!isset($input->user_id))) {return false;}

		$this->subscriber->checkSubscriber((string)$input->user_id);
		return $this->subscriber->unsubscribe();

	}
	
	
	protected function onConversationStarted() : bool {

		if (!$this->botInterface) {return false;}

		$input = $this->botInterface->getInput();
		if ((!is_object($input)) || (!is_object($input->user)) || (!isset($input->user->id))) {return false;}

		if (!$input->subscribed) {
			$btn1 = new stdClass();
			$btn1->ActionType = "reply";
			$btn1->ActionBody = "subscribe";
			$btn1->Text = VB_MSG::SUBSCRIBE_BTN;
			$btn1->TextSize = "regular";
			$btn1->BgColor = "#d5fbd5";
			return $this->botInterface->sendKeyboard((string)$input->user->id, VB_MSG::CONVERS_STARTED_MSG, array($btn1), true);
		} else {
			return true;
		}

	}


	protected function onMessage() : bool {

		if ((!$this->subscriber) || (!$this->botInterface)) {return false;}

		$input = $this->botInterface->getInput();
		if ((!is_object($input)) || (!is_object($input->message)) || (!is_object($input->sender)) || (!isset($input->sender->id))) {return false;}

		
		if (isset($input->message->text)) {
			$this->subscriber->checkSubscriber((string)$input->sender->id, (string)$input->sender->name);
		}


		#user send contact
		if ($input->message->type == "contact") {
			if (is_object($input->message->contact) && (isset($input->message->contact->phone_number))) {

				$this->subscriber->savePhone((string)$input->message->contact->phone_number);
				$this->botInterface->sendText((string)$input->sender->id, VB_MSG::HAS_PHONE_MSG);
				$this->adminMsg();

			}
			return true;		
		}

		#user subscribed
		if ($input->message->text == "subscribe") {
			$this->botInterface->sendText((string)$input->sender->id, VB_MSG::SUBSCRIBE_MSG);
		}

		#user send realname
		if ($this->subscriber->isRealNameRequested()) {
			$this->subscriber->setName((string)$input->message->text);
		}
		
		#realname required
		if (!$this->subscriber->getRealName()) {
			$this->subscriber->RequestRealName();
			$this->botInterface->sendText((string)$input->sender->id, VB_MSG::NAME_REQ_MSG);
			return true;
		}

		#contact required
		if (!$this->subscriber->getPhone()) {
			$btn = new stdClass();
			$btn->ActionType = "share-phone";
			$btn->ActionBody = "reply";
			$btn->Text = VB_MSG::PHONE_REQ_BTN;
			$btn->TextSize = "regular";
			$btn->BgColor = "#d5fbd5";
			$this->botInterface->sendKeyboard((string)$input->sender->id, sprintf(VB_MSG::PHONE_REQ_MSG, $this->subscriber->getRealName()), array($btn));
			return true;
		}
		
		#default message
		$this->botInterface->sendText((string)$input->sender->id, VB_MSG::DEFAULT_MSG);
		
		return true;

	}


	protected function adminMsg() : bool {

		if ((!$this->subscriber) || (!$this->botInterface) || (!$this->admin_id)) {return false;}

		return $this->botInterface->sendText($this->admin_id, sprintf(VB_MSG::NEW_SUBSCRIBER_MSG, 
				$this->subscriber->getName(), $this->subscriber->getRealName(), $this->subscriber->getPhone()
			));
		
	}
	
	
}