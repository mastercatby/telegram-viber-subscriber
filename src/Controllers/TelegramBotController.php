<?php

namespace Mastercat\Bots\Controllers;
use Mastercat\Bots\Controllers\BotController;
use Mastercat\Bots\Subscribers\BotSubscriber;
use Mastercat\Bots\Lang\TG_MSG;


class TelegramBotController extends BotController {

	protected ?BotSubscriber $subscriber;
	protected string $admin_id;
	
	
	public function __construct(array $config = array()) {

		parent::__construct();
		
		$this->admin_id = $config['admin_id'];
		$this->subscriber = null;
		
	}


	public function setSubscriber(BotSubscriber $subscriber) : void {
	
		$this->subscriber = $subscriber;
	
	}
	
	
	protected function onConversationStarted() : bool {

		if ((!$this->botInterface) || (!$this->subscriber)) {return false;}

		$input = $this->botInterface->getInput();
		if (!isset($input->message->chat->id)) {return false;}

		$this->subscriber->checkSubscriber($input->message->chat->id, $input->message->chat->first_name ?? null);

		$this->botInterface->sendText($input->message->chat->id, TG_MSG::CONVERS_STARTED_MSG, false);
		
		return $this->onMessage();

	}


	protected function onSubscribed() : bool {

		if ((!$this->botInterface) || (!$this->subscriber)) {return false;}

		$input = $this->botInterface->getInput();
		if (!isset($input->my_chat_member->chat->id)) {return false;}

		$this->subscriber->checkSubscriber($input->my_chat_member->chat->id, $input->my_chat_member->chat->first_name ?? null);
		return $this->botInterface->sendText($input->my_chat_member->chat->id, TG_MSG::SUBSCRIBE_MSG, true);

	}


	protected function onUnsubscribed() : bool {
	
		if ((!$this->botInterface) || (!$this->subscriber)) {return false;}

		$input = $this->botInterface->getInput();
		if (!isset($input->my_chat_member->chat->id)) {return false;}

		$this->subscriber->checkSubscriber($input->my_chat_member->chat->id);
		return $this->subscriber->unsubscribe();

	}
	
	
	protected function onMessage() : bool {

		if ((!$this->botInterface) || (!$this->subscriber)) {return false;}

		$input = $this->botInterface->getInput();
		if (!isset($input->message->chat->id)) {return false;}

		$this->subscriber->checkSubscriber($input->message->chat->id, $input->message->chat->first_name ?? null);

		#user send contact
		if (isset($input->message->contact->phone_number)) {

			$this->subscriber->savePhone($input->message->contact->phone_number);
			$this->botInterface->sendKeyboard($input->message->chat->id, TG_MSG::HAS_PHONE_MSG, ['remove_keyboard' => true], true);

			$this->adminMsg();

			return true;		
		}

		#user send realname
		if (($this->subscriber->isRealNameRequested()) && (isset($input->message->text))) {
			$this->subscriber->setName($input->message->text);
		}
		
		#realname required
		if (!$this->subscriber->getRealName()) {
			$this->subscriber->RequestRealName();
			$this->botInterface->sendText($input->message->chat->id, TG_MSG::NAME_REQ_MSG, true);
			return true;
		}

		#contact required
		if (!$this->subscriber->getPhone()) {
			$keyboard = ['keyboard' => array()];
			array_push($keyboard['keyboard'], array(array('text' => TG_MSG::PHONE_REQ_BTN, 'request_contact' => true)));
//			$keyboard = json_encode($keyboard);
			$this->botInterface->sendKeyboard($input->message->chat->id, sprintf(TG_MSG::PHONE_REQ_MSG, $this->subscriber->getRealName()), $keyboard, true);
			return true;
		}
		
		#default message
		$this->botInterface->sendText($input->message->chat->id, TG_MSG::DEFAULT_MSG, true);
		
		return true;

	}


	protected function adminMsg() : bool {

		if ((!$this->botInterface) || (!$this->subscriber) || (!$this->admin_id)) {return false;}

		return $this->botInterface->sendText($this->admin_id, sprintf(TG_MSG::NEW_SUBSCRIBER_MSG, 
				$this->subscriber->getName(), $this->subscriber->getRealName(), $this->subscriber->getPhone()
			), false);

	}


}