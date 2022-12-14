<?php

namespace Mastercat\Bots\Subscribers;
use Mastercat\Bots\Subscribers\BotSubscriber;

class TelegramSubscriber extends BotSubscriber {


	public function getType() : int {
	
		return 2; //MSGR_VIBER
	
	}


}