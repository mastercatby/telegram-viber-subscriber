<?php

namespace Mastercat\Bots;
use Mastercat\Bots\Config\VB_CONF;
require_once '../vendor/autoload.php';

if ((!isset($_SERVER['REQUEST_URI'])) || (!isset($_SERVER['HTTP_HOST']))) {return;}
$http_path = explode('/', $_SERVER['REQUEST_URI']);
array_pop($http_path);
$uri = 'https://' . $_SERVER['HTTP_HOST'] . '/' . implode('/', $http_path) . '/' . VB_CONF::webhook;

$jsonData = 
	'{
		"auth_token": "'.VB_CONF::auth_token.'",
		"url": "'.$uri.'",
		"send_name" : "true",
		"send_photo" : "false"
	}';
//		"event_types": ["subscribed", "unsubscribed", "delivered", "message", "seen", "conversation_started", "failed"]
	
$ch = curl_init('https://chatapi.viber.com/pa/set_webhook');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if($err) {echo($err);}
else {echo($response);}
?>