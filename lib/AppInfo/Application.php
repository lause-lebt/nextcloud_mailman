<?php

declare(strict_types=1);

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

namespace OCA\Mailman\AppInfo;

//use Psr\Container\ContainerInterface;

use OCP\AppFramework\App;
use OCP\EventDispatcher\IEventDispatcher;

use OCP\User\Events\UserCreatedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;

use OCA\Mailman\Listener\UserCreatedListener;
use OCA\Mailman\Listener\UserDeletedListener;
use OCA\Mailman\Listener\GroupDeletedListener;
use OCA\Mailman\Listener\UserAddedListener;
use OCA\Mailman\Listener\UserRemovedListener;

// use OCA\Mailman\Service\ConfigService;


class Application extends App {

	public const APP_ID = 'mailman';

    public function __construct(array $urlParams=[]) {
        parent::__construct(self::APP_ID, $urlParams);

//		/** @var ContainerInterface */
		$container = $this->getContainer();

		/** @var IEventDispatcher */
		$dispatcher = $container->query(IEventDispatcher::class);

		$dispatcher->addServiceListener(UserCreatedEvent::class, UserCreatedListener::class);
		$dispatcher->addServiceListener(UserDeletedEvent::class, UserDeletedListener::class);
		$dispatcher->addServiceListener(GroupDeletedEvent::class, GroupDeletedListener::class);
		$dispatcher->addServiceListener(UserAddedEvent::class, UserAddedListener::class);
		$dispatcher->addServiceListener(UserRemovedEvent::class, UserRemovedListener::class);
		
		/*

        $container->registerService('ConfigService', function($c) {
            return new ConfigService(
                $c->query('Config'),
                $c->query('AppName')
            );
        });

        $container->registerService('Config', function($c) {
            return $c->query('ServerContainer')->getConfig();
		}); */
	}
}
