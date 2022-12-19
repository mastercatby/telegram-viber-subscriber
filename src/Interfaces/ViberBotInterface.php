<?php

namespace Mastercat\Bots\Interfaces;
use Mastercat\Bots\Interfaces\BotInterface;


class ViberBotInterface extends BotInterface {

	protected ?object $input;
	protected string $auth_token;
	protected string $send_name;
	protected bool $debug;


	public function __construct(array $config = array()) {

		$this->input		= null;
		$this->auth_token	= $config['auth_token'];
		$this->send_name	= $config['send_name'];
		$this->debug		= $config['debug'];
		
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
	
		if (isset($this->input->event)) {
			return ($this->input->event);
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
   	 		$data = new \stdClass();
		}
    	
		$data->auth_token = $this->auth_token;
		$data->receiver = $receiver_id;
		$data->type = $type;
		if($text != null) {$data->text = $text;}
		if($tracking_data != null) {$data->tracking_data = $tracking_data;}
		$data->min_api_version = 3;
	
		$sender = new \stdClass(); 
		$sender->name = $this->send_name;
		$data->sender = $sender; 
	
		return $this->sendReq($data, $echo);
	}


	public function sendText(string $receiver_id, string $text, bool $echo = false) : bool {
	
		return $this->sendMsg($receiver_id, $text, "text", null, null, $echo);
		
	}


	public function sendKeyboard(string $receiver_id, string $text, array $buttons, bool $echo = false) : bool {

  	  	$data = new \stdClass();
    	
		$data->auth_token = $this->auth_token;
		$data->receiver = $receiver_id;
		$data->min_api_version = 3;
		$data->type = "text";
		if ($text) {$data->text = $text;}
		
		$data->keyboard = new \stdClass();
		$data->keyboard->Type = "keyboard";
		$data->keyboard->DefaultHeight = true;
		$data->keyboard->Buttons = $buttons;

		return $this->sendReq($data, $echo);
	}

	
	public function sendWebhookResponse() : bool {

		$webhook_response = new \stdClass();
		$webhook_response->status = 0;
		$webhook_response->status_message = "ok";
	//	$webhook_response->event_types = 'subscribed, unsubscribed, webhook, conversation_started, client_status, action, delivered, failed, message, seen';
		echo json_encode($webhook_response);
		return true;
		
	}


	protected function log(string $data) : void {

		if ($this->debug) {file_put_contents('../Logs/vb_log.txt', date('Y-m-d H:i:s') . ' - ' . $data . "\n", FILE_APPEND);}
	}
	

}	
