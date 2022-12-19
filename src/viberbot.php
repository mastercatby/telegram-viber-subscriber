<?php 

namespace Mastercat\Bots;
use Mastercat\Bots\Controllers\ViberBotController;
use Mastercat\Bots\Interfaces\ViberBotInterface;
use Mastercat\Bots\Repositories\PdoSubscriberRepository;
use Mastercat\Bots\Subscribers\ViberSubscriber;
use Mastercat\Bots\Config\VB_CONF;

require_once 'vendor/autoload.php';


header("Content-Type: application/json;charset=utf8");

$botInterface = new ViberBotInterface([
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
$controller->setBotInterface($botInterface);

$controller->dispatch();
	