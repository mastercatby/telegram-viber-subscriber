<?php

namespace Mastercat\Bots\Controllers;
use Mastercat\Bots\Controllers\BotController;
use Mastercat\Bots\Subscribers\BotSubscriber;
use Mastercat\Bots\Lang\TG_MSG;


class TelegramBotController extends BotController {

	protected ?BotSubscriber $subscriber;
	protected string $admin_id;
	
	
	public function __construct(array $config = array()) {

		parent::__construct($config);
		
		$this->admin_id = (string)$config['admin_id'];
		$this->subscriber = null;
		
	}


	public function setSubscriber(BotSubscriber $subscriber) : void {
	
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