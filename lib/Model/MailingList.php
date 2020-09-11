<?php
/**
 * @copyright 2020 Florian Gmeiner <florian@tinkatinka.com>
 *
 * @author Florian Gmeiner <florian@tinkatinka.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mailman\Models;

use JsonSerializable;


class MailingList implements JsonSerializable {

	/** @var string */
	protected $name;

	/** @var string */
	protected $groups;

	/** @var string */
	protected $extra;

	/** @var string */
	protected $exclude;


	/**
	 * Construct a new Mailing list.
	 * The only required parameter is an id.
	 * 
	 * @param string $id The name of this list (without domain)
	 * @param array $groups An array of strings of group ids
	 * @param array $extra An array of strings of additional email addresses
	 * @param array $exclude An array of strings of email addresses to exclude
	 */
	public function __construct(
		string $id,
		array $groups = [],
		array $extra = [],
		array $exclude = []
	) {
		$this->id = $id;
		$this->groups = $groups;
		$this->extra = $extra;
		$this->exclude = $exclude;
	}

	/**
	 * {@inheritdoc}
	 */
	public function jsonSerialize() {
		return [
			'id' => $this->id,
			'groups' => $this->groups,
			'extra' => $this->extra,
			'exclude' => $this->exclude
		];
	}
	
}

