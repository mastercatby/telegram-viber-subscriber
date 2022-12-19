<?php

namespace Mastercat\Bots\Interfaces;

abstract class BotInterface {

	abstract public function getInput() : ?object;
	abstract public function getEvent() : string;
	abstract public function sendText(string $receiver_id, string $text, bool $echo = false) : bool;
	abstract public function sendKeyboard(string $receiver_id, string $text, array $buttons, bool $echo = false) : bool;
	abstract public function sendWebhookResponse() : bool;

}