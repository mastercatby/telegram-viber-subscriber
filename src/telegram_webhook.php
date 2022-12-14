<?php

namespace Mastercat\Bots;
use Mastercat\Bots\Config\TG_CONF;
	
$http_path = explode('/', $_SERVER['REQUEST_URI']);
array_pop($http_path);
$uri = 'https://' . $_SERVER['HTTP_HOST'] . '/' . implode('/', $http_path) . '/' . TG_CONF::webhook;
	
$res = file_get_contents('https://api.telegram.org/bot' . TG_CONF::auth_token . '/setWebhook?url=' . $uri);
if ($res === false) {
	echo 'error.';
} else {
	echo $res;
}
?>