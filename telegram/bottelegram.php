<?php

require_once('../lib/botbase.php');
require_once('config.php');
//require_once('lang_' . TG_CONF::lang . '.php');
require_once('lang_ru.php');


class McTelegramSubscriber extends McSubscriber {


	public function getType() : int {
	
		return 2; //MSGR_VIBER
	
	}


}


class McTelegramBotInreface extends McBotInreface {

	protected ?object $input;
	protected string $auth_token;
	protected bool $debug;


	public function __construct(array $config = array()) {

		$this->input		= null;
		$this->auth_token	= (string)$config['auth_token'];
		$this->debug		= (bool)$config['debug'];
		
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

		if (!is_object($this->input)) {return '';}


		if ((is_object($this->input->my_chat_member)) && (is_object($this->input->my_chat_member->new_chat_member))) {
		
			$members_status = array('creator', 'administrator', 'member');
			$non_members_status = array('restricted', 'left', 'kicked');
			
			if (in_array($this->input->my_chat_member->new_chat_member->status, $members_status)) {
				return 'subscribed';
			}
			
			if (in_array($this->input->my_chat_member->new_chat_member->status, $non_members_status)) {
				return 'unbscribed';
			}

		}


		if (is_object($this->input->message)) {
		
			if ((isset($this->input->message->text)) && ($this->input->message->text == '/start')) {
				return 'conversation_started';
			} else {
				return 'message';
			}

		}


		if (isset($this->input->callback_query)) {
		
			return 'callback_query';
//			$input->message = $input->callback_query->message;
//			switch ($input->callback_query->data) {}			
		}
		
		return '';

	}


	public function sendWebhookResponse() : bool {

		return true;
	}


	protected function echoMessage(string $chat_id, string $text, ?string $reply_to = null, ?array $keyboard = null) : bool {

		$answer = array();

		$answer['method'] = 'sendMessage';
		$answer['chat_id'] = $chat_id;
		$answer['text'] = $text;

		if ($reply_to) { $answer['reply_to_message_id'] = $reply_to; }
		if ($keyboard) { $answer['reply_markup'] = $keyboard; }
		
		$answer = json_encode($answer);
		echo $answer;
		
		$this->log('OUT: ' . $answer);
		
		return true;
				
	}


	public function callMethod(string $method, array $params = array()) : bool {
	
		$this->log('CALL ' . $method);

		$ch = curl_init();
		curl_setopt_array(
			$ch,
			array(
				CURLOPT_URL => 'https://api.telegram.org/bot' . $this->auth_token . '/' . $method,
				CURLOPT_POST => TRUE,
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_TIMEOUT => 10,
				CURLOPT_POSTFIELDS => $params,
			)
		);
		curl_exec($ch);

		$err = curl_error($ch);
		curl_close($ch);

		if ($err) {return false;}
		else {return true;}
		
	}
	
	
	public function sendText(string $receiver_id, string $text, bool $echo = false) : bool {
	
		if ($echo) {
			return $this->echoMessage($receiver_id, $text);
		} else {
			return $this->callMethod('sendMessage', ['chat_id' => $receiver_id, 'text' => $text]);			
		}
	
	}
	
	
	public function sendKeyboard(string $receiver_id, string $text, array $buttons, bool $echo = false) : bool {

		if ($echo) {
			return $this->echoMessage($receiver_id, $text, null, $buttons);
		} else {
			return $this->callMethod('sendMessage', ['chat_id' => $receiver_id, 'text' => $text, 'reply_markup' => $buttons]);			
		}
	
	}


	protected function log(string $data) : void {

		if ($this->debug) {file_put_contents('data/tg_log.txt', date('Y-m-d H:i:s') . ' - ' . $data . "\n", FILE_APPEND);}
	}

}


class McTelegramBotController extends McBotController {

	protected ?McSubscriber $subscriber;
	protected string $admin_id;
	
	
	public function __construct(array $config = array()) {

		parent::__construct($config);
		
		$this->admin_id = (string)$config['admin_id'];
		$this->subscriber = null;
		
	}


	public function setSubscriber(McSubscriber $subscriber) : void {
	
		$this->subscriber = $subscriber;
	
	}
	
	
	protected function onConversationStarted() : bool {

		if ((!$this->botInterface) || (!$this->subscriber)) {return false;}

		$input = $this->botInterface->getInput();
		if ((!is_object($input)) || (!is_object($input->message)) || (!is_object($input->message->chat)) || (!isset($input->message->chat->id))) {return false;}

		$this->subscriber->checkSubscriber((string)$input->message->chat->id, (string)$input->message->chat->first_name);

		$this->botInterface->sendText((string)$input->message->chat->id, TG_MSG::CONVERS_STARTED_MSG, false);
		
		return $this->onMessage();

	}


	protected function onSubscribed() : bool {

		if ((!$this->botInterface) || (!$this->subscriber)) {return false;}

		$input = $this->botInterface->getInput();
		if ((!is_object($input)) || (!is_object($input->my_chat_member)) || (!is_object($input->my_chat_member->chat)) || (!isset($input->my_chat_member->chat->id))) {return false;}

		$this->subscriber->checkSubscriber((string)$input->my_chat_member->chat->id, (string)$input->my_chat_member->chat->first_name);
		return $this->botInterface->sendText((string)$input->my_chat_member->chat->id, TG_MSG::SUBSCRIBE_MSG, true);

	}


	protected function onUnsubscribed() : bool {
	
		if ((!$this->botInterface) || (!$this->subscriber)) {return false;}

		$input = $this->botInterface->getInput();
		if ((!is_object($input)) || (!is_object($input->my_chat_member)) || (!is_object($input->my_chat_member->chat)) || (!isset($input->my_chat_member->chat->id))) {return false;}

		$this->subscriber->checkSubscriber((string)$input->my_chat_member->chat->id);
		return $this->subscriber->unsubscribe();

	}
	
	
	protected function onMessage() : bool {

		if ((!$this->botInterface) || (!$this->subscriber)) {return false;}

		$input = $this->botInterface->getInput();
		if ((!is_object($input)) || (!is_object($input->message)) || (!is_object($input->message->chat)) || (!isset($input->message->chat->id))) {return false;}

		$this->subscriber->checkSubscriber((string)$input->message->chat->id, (string)$input->message->chat->first_name);

		#user send contact
		if (is_object($input->message->contact)) {
			if (isset($input->message->contact->phone_number)) {

				$this->subscriber->savePhone((string)$input->message->contact->phone_number);
				$this->botInterface->sendKeyboard((string)$input->message->chat->id, TG_MSG::HAS_PHONE_MSG, ['remove_keyboard' => true], true);

				$this->adminMsg();

			}
			return true;		
		}

		#user send realname
		if ($this->subscriber->isRealNameRequested()) {
			$this->subscriber->setName((string)$input->message->text);
		}
		
		#realname required
		if (!$this->subscriber->getRealName()) {
			$this->subscriber->RequestRealName();
			$this->botInterface->sendText((string)$input->message->chat->id, TG_MSG::NAME_REQ_MSG, true);
			return true;
		}

		#contact required
		if (!$this->subscriber->getPhone()) {
			$keyboard = ['keyboard' => array()];
			array_push($keyboard['keyboard'], array(array('text' => TG_MSG::PHONE_REQ_BTN, 'request_contact' => true)));
//			$keyboard = json_encode($keyboard);
			$this->botInterface->sendKeyboard((string)$input->message->chat->id, sprintf(TG_MSG::PHONE_REQ_MSG, $this->subscriber->getRealName()), $keyboard, true);
			return true;
		}
		
		#default message
		$this->botInterface->sendText((string)$input->message->chat->id, TG_MSG::DEFAULT_MSG, true);
		
		return true;

	}


	protected function adminMsg() : bool {

		if ((!$this->botInterface) || (!$this->subscriber) || (!$this->admin_id)) {return false;}

		return $this->botInterface->sendText($this->admin_id, sprintf(TG_MSG::NEW_SUBSCRIBER_MSG, 
				$this->subscriber->getName(), $this->subscriber->getRealName(), $this->subscriber->getPhone()
			), false);

	}


}