<?php 

namespace Mastercat\Bots\Controllers;
use Mastercat\Bots\Controllers\BotController;
use Mastercat\Bots\Subscribers\BotSubscriber;
use Mastercat\Bots\Lang\VB_MSG;


class ViberBotController extends BotController {

	protected ?BotSubscriber $subscriber;
	protected string $admin_id;	
	
	
	public function __construct(array $config = array()) {

		parent::__construct($config);
		
		$this->admin_id = (string)($config['admin_id']);
		$this->subscriber = null;
		
	}


	public function setSubscriber(BotSubscriber $subscriber) : void {
	
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
			$btn = new \stdClass();
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
			$btn1 = new \stdClass();
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
			$btn = new \stdClass();
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