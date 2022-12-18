<?php 

namespace Mastercat\Bots\Subscribers;
use Mastercat\Bots\Repositories\SubscriberRepository;


class BotSubscriber {

	protected string $user_id;
	protected string $name;
	protected string $real_name;
	protected string $phone;
	protected bool $subscribed;
	protected int $flags;
	protected ?SubscriberRepository $repository;
	

	public function __construct() {

		$this->user_id = '';
		$this->name = '';
		$this->real_name = '';
		$this->phone = '';
		$this->subscribed = true;
		$this->flags = 0;
		$this->repository = null;
	
	}
	

	public function setRepository(SubscriberRepository $repository) : void {
	
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
				$this->user_id		= $res['user_id'];
				$this->name			= $res['name'];
				$this->real_name	= $res['real_name'];
				$this->phone		= $res['phone'];
				$this->flags		= $res['flags'];
				$this->subscribed	= $res['subscribed'];
			}
		}

		return $this->isLoaded();

	}


	protected function createSubscriber(string $user_id, string $user_name = '') : bool {

		if (!$this->repository) {return false;}

		$this->user_id		= $user_id;
		$this->name			= $user_name;
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