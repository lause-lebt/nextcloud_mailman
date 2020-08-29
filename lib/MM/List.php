<?php
namespace OCA\Mailman\MM;

use JsonSerializable;


class MailingList implements JsonSerializable {

	protected $name;
	protected $groups;
	protected $extraUsers;

	public function __construct() {
		$this->name = $name;
		$this->users = $users;
	}

	public function jsonSerialize() {
		return [
			'name' => $this->name,
			'groups' => $this->users
		];
	}
	
}

