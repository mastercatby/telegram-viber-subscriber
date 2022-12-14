<?php

namespace Mastercat\Bots\Repositories;


abstract class SubscriberRepository {

	abstract public function loadSubscriber(string $user_id, int $subscriber_type) : ?array;
	abstract public function createSubscriber(array $data) : bool;
	abstract public function updateSubscriber(array $data) : bool;

}
