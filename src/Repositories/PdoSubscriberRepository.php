<?php 

namespace Mastercat\Bots\Repositories;
use Mastercat\Bots\Repositories\SubscriberRepository;


class PdoSubscriberRepository extends SubscriberRepository {

	protected ?\PDO $db = null;
	

	public static function Factory(string $dbconnect, string $dbuser, string $dbpass) : PdoSubscriberRepository {
		
		$res = new self();
		$res->connect($dbconnect, $dbuser, $dbpass);
		return $res;

	}


	public function loadSubscriber(string $user_id, int $subscriber_type) : ?array {
	
		return $this->selectRow('SELECT * FROM `bot_subscribers` WHERE (`user_id` = :user_id) AND (`messenger_type` = :messenger_type)',
			['user_id' => $user_id, 'messenger_type' => $subscriber_type]);

	}


	public function createSubscriber(array $data) : bool {

		return $this->execCmd('INSERT INTO `bot_subscribers` (`user_id`, `name`, `real_name`, `phone`, `subscribed`, `flags`, `messenger_type`)'
			. ' VALUES (:user_id, :name, :real_name, :phone, :subscribed, :flags, :messenger_type)',
			$data);

	}


	public function updateSubscriber(array $data) : bool {

		return $this->execCmd('UPDATE `bot_subscribers` '
			. ' SET `subscribed` = :subscribed, `name` = :name, `real_name` = :real_name, `phone` = :phone, `flags` = :flags'
			. ' WHERE (`user_id` = :user_id) AND (`messenger_type` = :messenger_type)', $data);

	}


	protected function connect(string $dbconnect, string $dbuser, string $dbpass) : bool {

		$this->db = new \PDO($dbconnect, $dbuser, $dbpass);
		return (bool)($this->db);
	}


	protected function selectAll(string $query, array $args = []) : ?array {

		if (!$this->db) {return null;}

		$res = null;
		$sth = $this->db->prepare($query, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
		if ($sth !== false) {
			if ($sth->execute($args)) {
				$res = $sth->fetchAll();
			}
		}
		return $res;
		
	}


	protected function selectRow(string $query, array $args = []) : ?array {

		if (!$this->db) {return null;}

		$res = null;
		$sth = $this->db->prepare($query);
		if ($sth !== false) {
			if ($sth->execute($args)) {
				/** @var array|bool */
				$row = $sth->fetch();
				if ($row !== false) {
					$res = (array)$row;
				}
			}
		}
		return $res;
		
	}


	protected function execCmd(string $query, array $args = []) : bool {

		if (!$this->db) {return false;}

		$res = false;
		$sth = $this->db->prepare($query);
		if ($sth !== false) {
			$res = $sth->execute($args);
		}
		return $res;

	}


}