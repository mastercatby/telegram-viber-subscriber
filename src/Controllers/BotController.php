<?php

namespace Mastercat\Bots\Controllers;
use Mastercat\Bots\Interfaces\BotInreface;


abstract class BotController {

	protected ?BotInreface $botInterface;
	 

	public function __construct(array $config = array()) {

		$this->botInterface = null;

	}


	public function setBotInterface(BotInreface $botInterface) : void {

		$this->botInterface = $botInterface;

	}


	public function dispatch() : bool {

		if (!$this->botInterface) {return false;}	

		$event = $this->botInterface->getEvent();
		//$res = true; 
		switch ($event) {

			case 'webhook' :
				$res = $this->onSetWebhook();
				break;
			case 'subscribed' :
				$res = $this->onSubscribed();
				break;
			case 'unsubscribed' :
				$res = $this->onUnsubscribed();
				break;
			case 'conversation_started' :
				$res = $this->onConversationStarted();
				break;
			case 'message' :
				$res = $this->onMessage();
				break;
			case 'action' :
				$res = $this->onAction();
				break;
			case 'delivered' :
				$res = $this->onDelivered();
				break;
			case 'seen' :
				$res = $this->onSeen();
				break;
			case 'client_status' :
				$res = $this->onClientStatus();
				break;
			case 'failed' :
				$res = $this->onFailed();
				break;
			default:
				$res = $this->onDefault();
		}
		
		return $res;
		
	}
	

	protected function onSetWebhook() : bool {

		if (!$this->botInterface) {
			return false;
		} else {
			return $this->botInterface->sendWebhookResponse();
		}
		
	}


	protected function onSubscribed() : bool {return true;}
	protected function onUnsubscribed() : bool {return true;}
	protected function onConversationStarted() : bool {return true;}
	protected function onMessage() : bool {return true;}
	protected function onAction() : bool {return true;}
	protected function onDelivered() : bool {return true;}
	protected function onSeen() : bool {return true;}
	protected function onClientStatus() : bool {return true;}
	protected function onFailed() : bool {return true;}
	protected function onDefault() : bool {return true;}
	
	
}
