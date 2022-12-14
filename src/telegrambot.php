<?php 

namespace Mastercat\Bots;
use Mastercat\Bots\Controllers\TelegramBotController;
use Mastercat\Bots\Interfaces\TelegramBotInreface;
use Mastercat\Bots\Repositories\PdoSubscriberRepository;
use Mastercat\Bots\Subscribers\TelegramSubscriber;
use Mastercat\Bots\Config\TG_CONF;

require_once 'vendor/autoload.php';

header("Content-Type: application/json;charset=utf8");


$botInreface = new TelegramBotInreface([
	'auth_token'	=> TG_CONF::auth_token, 
	'debug'			=> TG_CONF::debug
	]);

$subscriberRepository = PdoSubscriberRepository::Factory(
	TG_CONF::dbconnect, 
	TG_CONF::dbuser, 
	TG_CONF::dbpass
	);

$subscriber = new TelegramSubscriber();
$subscriber->setRepository($subscriberRepository); 	

$controller = new TelegramBotController(['admin_id' => TG_CONF::admin_id]);
$controller->setSubscriber($subscriber);	
$controller->setBotInterface($botInreface);

$controller->dispatch();