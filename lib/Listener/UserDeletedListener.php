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

namespace OCA\Mailman\Listener;

use OCP\IUser;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserDeletedEvent;

use OCA\Mailman\Service\ListService;

class UserDeletedListener implements IEventListener {

	/** @var ListService */
	private $listService;
	
	public function __construct(ListService $listService) {
        $this->listService = $listService;
	}
		
	public function handle(Event $event): void {
		if (!($event instanceof UserDeletedEvent)) {
			return;
		}
		/** @var IUser */
		$user = $event->getUser();
		$this->listService->onUserDeleted($user);
	}

}
