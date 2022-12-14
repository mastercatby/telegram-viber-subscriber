<?php 

require_once('../lib/botbase.php');
require_once('../lib/botrepository.php');
require_once('bottelegram.php');
require_once('config.php');


header("Content-Type: application/json;charset=utf8");

$botInreface = new McTelegramBotInreface([
	'auth_token'	=> TG_CONF::auth_token, 
	'debug'			=> TG_CONF::debug
	]);

$subscriberRepository = McPdoSubscriberRepository::Factory(
	TG_CONF::dbconnect, 
	TG_CONF::dbuser, 
	TG_CONF::dbpass
	);

$subscriber = new McTelegramSubscriber();
$subscriber->setRepository($subscriberRepository); 	

$controller = new McTelegramBotController(['admin_id' => TG_CONF::admin_id]);
$controller->setSubscriber($subscriber);	
$controller->setBotInterface($botInreface);

$controller->dispatch();