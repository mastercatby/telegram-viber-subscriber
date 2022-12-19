<?php

namespace Mastercat\Bots\Subscribers;
use Mastercat\Bots\Subscribers\BotSubscriber;


class ViberSubscriber extends BotSubscriber {

	public function getType() : int {
	
		return 1; //MSGR_VIBER
	
	}

}