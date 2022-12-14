<?php 

namespace Mastercat\Bots;
use Mastercat\Bots\Controllers\ViberBotController;
use Mastercat\Bots\Interfaces\ViberBotInreface;
use Mastercat\Bots\Repositories\PdoSubscriberRepository;
use Mastercat\Bots\Subscribers\ViberSubscriber;
use Mastercat\Bots\Config\VB_CONF;


header("Content-Type: application/json;charset=utf8");

$botInreface = new ViberBotInreface([
	'auth_token'	=> VB_CONF::auth_token, 
	'send_name'		=> VB_CONF::send_name, 
	'debug'			=> VB_CONF::debug
	]);

$repository = PdoSubscriberRepository::Factory(
	VB_CONF::dbconnect, 
	VB_CONF::dbuser, 
	VB_CONF::dbpass
	);

$subscriber = new ViberSubscriber();
$subscriber->setRepository($repository); 	

$controller = new ViberBotController(['admin_id' => VB_CONF::admin_id]);
$controller->setSubscriber($subscriber);	
$controller->setBotInterface($botInreface);

$controller->dispatch();
	