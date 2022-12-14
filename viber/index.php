<?php 

require_once('../lib/botbase.php');
require_once('../lib/botrepository.php');
require_once('botviber.php');
require_once('config.php');


header("Content-Type: application/json;charset=utf8");

$botInreface = new McViberBotInreface([
	'auth_token'	=> VB_CONF::auth_token, 
	'send_name'		=> VB_CONF::send_name, 
	'debug'			=> VB_CONF::debug
	]);

$subscriberRepository = McPdoSubscriberRepository::Factory(
	VB_CONF::dbconnect, 
	VB_CONF::dbuser, 
	VB_CONF::dbpass
	);

$subscriber = new McViberSubscriber();
$subscriber->setRepository($subscriberRepository); 	

$controller = new McViberBotController(['admin_id' => VB_CONF::admin_id]);
$controller->setSubscriber($subscriber);	
$controller->setBotInterface($botInreface);

$controller->dispatch();
	