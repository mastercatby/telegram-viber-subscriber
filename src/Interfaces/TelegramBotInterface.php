<?php

namespace Mastercat\Bots\Interfaces;
use Mastercat\Bots\Interfaces\BotInterface;

class TelegramBotInterface extends BotInterface {

	protected ?object $input;
	protected string $auth_token;
	protected bool $debug;


	public function __construct(array $config = array()) {

		$this->input		= null;
		$this->auth_token	= $config['auth_token'];
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

		if (!$this->input) {return '';}


		if (isset($this->input->my_chat_member->new_chat_member)) {
		
			$members_status = array('creator', 'administrator', 'member');
			$non_members_status = array('restricted', 'left', 'kicked');
			
			if (in_array($this->input->my_chat_member->new_chat_member->status, $members_status)) {
				return 'subscribed';
			}
			
			if (in_array($this->input->my_chat_member->new_chat_member->status, $non_members_status)) {
				return 'unbscribed';
			}

		}


		if (isset($this->input->message)) {
		
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

		if ($this->debug) {file_put_contents(__DIR__ . '/../Logs/tg_log.txt', date('Y-m-d H:i:s') . ' - ' . $data . "\n", FILE_APPEND);}
	}

}