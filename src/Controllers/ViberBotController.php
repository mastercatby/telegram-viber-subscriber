<?php 

namespace Mastercat\Bots\Controllers;
use Mastercat\Bots\Controllers\BotController;
use Mastercat\Bots\Subscribers\BotSubscriber;
use Mastercat\Bots\Lang\VB_MSG;


class ViberBotController extends BotController {

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
	
	
	protected function onSubscribed() : bool {
	
		if ((!$this->botInterface) || (!$this->subscriber)) {return false;}

		$input = $this->botInterface->getInput();
		
		if (!isset($input->user->id)) {return false;}
		$this->subscriber->checkSubscriber($input->user->id, $input->user->name ?? null);
		
		$this->botInterface->sendText($input->user->id, VB_MSG::SUBSCRIBE_MSG);
		
		if (!$this->subscriber->getRealName()) {
			$this->subscriber->RequestRealName();
			$this->botInterface->sendText($input->user->id, VB_MSG::NAME_REQ_MSG);
			return true;
		}

		if (!$this->subscriber->getPhone()) {
			$btn = new \stdClass();
			$btn->ActionType = "share-phone";
			$btn->ActionBody = "reply";
			$btn->Text = VB_MSG::PHONE_REQ_BTN;
			$btn->TextSize = "regular";
			$btn->BgColor = "#d5fbd5";
			$this->botInterface->sendKeyboard($input->user->id, sprintf(VB_MSG::PHONE_REQ_MSG, $this->subscriber->getRealName()), array($btn));
			return true;
		}

		return true;
		
	}
	

	protected function onUnsubscribed() : bool {

		if ((!$this->subscriber) || (!$this->botInterface)) {return false;}

		$input = $this->botInterface->getInput();
		if (!isset($input->user_id)) {return false;}

		$this->subscriber->checkSubscriber($input->user_id);
		return $this->subscriber->unsubscribe();

	}
	
	
	protected function onConversationStarted() : bool {

		if (!$this->botInterface) {return false;}

		$input = $this->botInterface->getInput();
		if (!isset($input->user->id)) {return false;}

		if ($input->subscribed ?? null !== 1) {
			$btn1 = new \stdClass();
			$btn1->ActionType = "reply";
			$btn1->ActionBody = "subscribe";
			$btn1->Text = VB_MSG::SUBSCRIBE_BTN;
			$btn1->TextSize = "regular";
			$btn1->BgColor = "#d5fbd5";
			return $this->botInterface->sendKeyboard($input->user->id, VB_MSG::CONVERS_STARTED_MSG, array($btn1), true);
		} else {
			return true;
		}

	}


	protected function onMessage() : bool {

		if ((!$this->subscriber) || (!$this->botInterface)) {return false;}

		$input = $this->botInterface->getInput();
		if ((!isset($input->sender->id)) || (!isset($input->message))) {return false;}

		
		if (isset($input->message->text)) {
			$this->subscriber->checkSubscriber($input->sender->id, $input->sender->name ?? null);
		}


		#user send contact
		if ($input->message->type == "contact") {
			if (isset($input->message->contact->phone_number)) {

				$this->subscriber->savePhone($input->message->contact->phone_number);
				$this->botInterface->sendText($input->sender->id, VB_MSG::HAS_PHONE_MSG);
				$this->adminMsg();

			}
			return true;		
		}

		#user subscribed
		if ($input->message->text == "subscribe") {
			$this->botInterface->sendText($input->sender->id, VB_MSG::SUBSCRIBE_MSG);
		}

		#user send realname
		if ($this->subscriber->isRealNameRequested()) {
			$this->subscriber->setName($input->message->text);
		}
		
		#realname required
		if (!$this->subscriber->getRealName()) {
			$this->subscriber->RequestRealName();
			$this->botInterface->sendText($input->sender->id, VB_MSG::NAME_REQ_MSG);
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
			$this->botInterface->sendKeyboard($input->sender->id, sprintf(VB_MSG::PHONE_REQ_MSG, $this->subscriber->getRealName()), array($btn));
			return true;
		}
		
		#default message
		$this->botInterface->sendText($input->sender->id, VB_MSG::DEFAULT_MSG);
		
		return true;

	}


	protected function adminMsg() : bool {

		if ((!$this->subscriber) || (!$this->botInterface) || (!$this->admin_id)) {return false;}

		return $this->botInterface->sendText($this->admin_id, sprintf(VB_MSG::NEW_SUBSCRIBER_MSG, 
				$this->subscriber->getName(), $this->subscriber->getRealName(), $this->subscriber->getPhone()
			));
		
	}
	
	
}