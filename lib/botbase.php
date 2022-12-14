<?php 


abstract class McBotInreface {

	abstract public function getInput() : ?object;
	abstract public function getEvent() : string;
	abstract public function sendText(string $receiver_id, string $text, bool $echo = false) : bool;
	abstract public function sendKeyboard(string $receiver_id, string $text, array $buttons, bool $echo = false) : bool;
	abstract public function sendWebhookResponse() : bool;

}


abstract class McBotController {

	protected ?McBotInreface $botInterface;
	 

	public function __construct(array $config = array()) {

		$this->botInterface = null;

	}


	public function setBotInterface(McBotInreface $botInterface) : void {

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


abstract class McSubscriberRepository {

	abstract public function loadSubscriber(string $user_id, int $subscriber_type) : ?array;
	abstract public function createSubscriber(array $data) : bool;
	abstract public function updateSubscriber(array $data) : bool;

}


class McSubscriber {

	protected string $user_id;
	protected string $name;
	protected string $real_name;
	protected string $phone;
	protected bool $subscribed;
	protected int $flags;
	protected ?McSubscriberRepository $repository;
	

	public function __construct() {

		$this->user_id = '';
		$this->name = '';
		$this->real_name = '';
		$this->phone = '';
		$this->subscribed = true;
		$this->flags = 0;
		$this->repository = null;
	
	}
	

	public function setRepository(McSubscriberRepository $repository) : void {
	
		$this->repository = $repository;

	}


	public function getUserId() : string	{return $this->user_id;}
	public function getName() : string		{return $this->name;}
	public function getRealName() : string	{return $this->real_name;}
	public function getPhone() : string		{return $this->phone;}
	public function getSubscribed() : bool	{return $this->subscribed;}
	public function getFlags() : int		{return $this->flags;}
	public function getType() : int			{return 0;}

	
	protected function getData() : array {
	
		return [
			'user_id'		=> $this->user_id,
			'name'			=> $this->name,
			'real_name'		=> $this->real_name,
			'subscribed' 	=> $this->subscribed,
			'flags'			=> $this->flags,
			'phone'			=> $this->phone,
			'messenger_type'	=> $this->getType()
		];
		
	}


	public function isLoaded() : bool {
	
		return (bool)($this->user_id);
	}
	

	public function loadSubscriber(string $user_id) : bool {

		if (!$this->repository) {return false;}

		if (!$this->isLoaded()) {
			
			$res = $this->repository->loadSubscriber($user_id, $this->getType());
			if ($res) {
				$this->user_id		= (string)($res['user_id']);
				$this->name			= (string)($res['name']);
				$this->real_name	= (string)($res['real_name']);
				$this->phone		= (string)($res['phone']);
				$this->flags		= (int)($res['flags']);
				$this->subscribed	= (bool)($res['subscribed']);
			}
		}

		return $this->isLoaded();

	}


	protected function createSubscriber(string $user_id, string $user_name = '') : bool {

		if (!$this->repository) {return false;}

		$this->user_id		= $user_id;
		$this->name		= $user_name;
		$this->real_name	= '';
		$this->phone		= '';
		$this->flags		= 0;
		$this->subscribed	= true;
		
		return $this->repository->createSubscriber($this->getData());

	}


	protected function saveSubscriber() : bool {
	
		if (!$this->repository) {
			return false;
		} else {
			return $this->repository->updateSubscriber($this->getData());
		}
			
	}

	
	public function checkSubscriber(string $user_id, string $user_name = '') : bool {

		if ($this->loadSubscriber($user_id)) {
			if (!$this->subscribed) {
				$this->subscribed = true;
				return $this->saveSubscriber();
			} else {
				return true;
			}
		} else {
			return $this->createSubscriber($user_id, $user_name);
		}
	
	}
	
	
	public function subscribe() : bool {

		$this->subscribed = true;
		return $this->saveSubscriber();
		
	}

	public function unsubscribe() : bool {

		$this->subscribed = false;
		return $this->saveSubscriber();
		
	}


	public function savePhone(string $phone_number) : bool {
	
		$this->phone = $phone_number;
		return $this->saveSubscriber();
		
	}
	
	
	public function setName(string $realname) : bool {
	
		$this->real_name = $realname;
		$this->flags = 0;
		return $this->saveSubscriber();
		
	}
	
	
	protected function setFlags(int $flags) : bool {

		$this->flags = $flags;
		return $this->saveSubscriber();
				
	}


	public function RequestRealName() : bool {
	
		return $this->setFlags(1); //FLG_NAME_REQ
	
	}


	public function isRealNameRequested() : bool {
	
		return $this->flags == 1; //FLG_NAME_REQ
	
	}

	
}