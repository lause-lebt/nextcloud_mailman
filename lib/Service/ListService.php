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

 namespace OCA\Mailman\Service;

use OCP\IL10N;
use OCP\ILogger;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;

use OCA\Mailman\Service\ConfigService;
use OCA\Mailman\Service\MMService;

use OCA\Mailman\Exception\MailmanException;
use OCA\Mailman\Exception\UserNotFoundException;

class ListService {

	/** @var string */
	protected $appName;

	/** @var ConfigService */
	protected $config;
	
	/** @var MMService */
	protected $mm;
	
	/** @var ILogger */
	protected $logger;
	
	/** @var IL10N */
	protected $l;
	
	/** @var IGroupManager */
	private $groupManager;

	/** @var array */
	private $_mmlists;
	

    public function __construct(
		ConfigService $config,
		MMService $mm,
		ILogger $logger,
		IGroupManager $groupManager,
		IL10N $l,
		string $AppName
	){
		$this->config = $config;
		$this->mm = $mm;
		$this->logger = $logger;
		$this->groupManager = $groupManager;
		$this->l = $l;
		$this->appName = $AppName;

		$this->_mmlists = null;
	}

	private function mmlists(): array {
		if ($this->_mmlists === null) {
			$this->_mmlists = $this->mm->getLists();
		}
		return $this->_mmlists;
	}
	
	private function traverseLists(array $lists, $callback) {
		foreach ($lists as $l) {
			if (is_array($l) && array_key_exists('id', $l)) {
				$callback($l['id']);
			} else {
				$this->logger->warning(
					'[ListService] No list id found ('.print_r($l, true).')'
				);
			}
		}
	}

	private function traverseMMLists($callback) {
		foreach ($this->mmlists() as $mml) {
			if (is_array($mml) && array_key_exists('list_name', $mml)) {
				$callback($mml['list_name']);
			} else {
				$this->logger->warning(
					'[ListService] No mmlist name found ('.print_r($mml, true).')'
				);
			}
		}
	}

	private function listsFromConfig(): array {
		$lists = $this->config->getLists();
		if (!is_array($lists)) {
			$this->logger->warning(
				'[ListService] Config failure "lists"',
				[ 'data' => print_r($lists, true) ]
			);
			$lists = [];	
		}
		return $lists;
	}

	public function findList(?array $lists, string $name) {
		if ($lists === null) {
			$lists = $this->listsFromConfig();
		}
		foreach ($lists as $l) {
			if (is_array($l) && array_key_exists('id', $l)) {
				if (strcmp($l['id'], $name) === 0) {
					return $l;
				}
			} else {
				$this->logger->warning(
					'[ListService] No list id found ('.print_r($l, true).')'
				);
			}
		}
		return false;
	}

	private function findMMList(string $name) {
		foreach ($this->mmlists() as $l) {
			if (is_array($l) && array_key_exists('list_name', $l) &&
				strcmp($l['list_name'], $name) === 0)
			{
				return $l;
			}
		}
		return false;
	}

	private function groupMembers(string $name) {
		$group = $this->groupManager->get($name);
		$users = $group->getUsers();
		$emails = array();
		foreach ($users as $u) {
			$e = $u->getEMailAddress();
			if (is_string($e) && strlen($e) > 0) {
				array_push($emails, strtolower($e));
			}
		}
		return $emails;
	}

	public function listMembers(array $lists, string $name, bool $includeUnsubscribed = false) {
		$emails = array();
		$list = $this->findList($lists, $name);
		if (is_array($list)) {
			$groups = (array_key_exists('groups', $list)) ? $list['groups'] : [];
			foreach ($groups as $g) {
				$emails = array_merge($emails, $this->groupMembers($g));
			}
			if (!$includeUnsubscribed) {
				$exclude = array_map(function($e) {
					return strtolower($e);
				}, (array_key_exists('exclude', $list)) ? $list['exclude'] : []);
				$emails = array_diff($emails, $exclude);
			}
			$extra = array_map(function($e) {
				return strtolower($e);
			}, (array_key_exists('extra', $list)) ? $list['extra'] : []);
			$emails = array_merge($emails, $extra);
		}
		return array_map(function($e) {
			return strtolower($e);
		}, $emails);
	}

	private function mmListMembers(string $name) {
		$members = $this->mm->getMembers($name);
		$emails = array();
		if (is_array($members)) {
			foreach ($members as $m) {
				if (is_array($m) && array_key_exists('email', $m)) {
					array_push($emails, $m['email']);
				}
			}
		}
		return array_map(function($e) {
			return strtolower($e);
		}, $emails);
	}

	public function allGroups(): array {
		$groups = $this->groupManager->search('');
		return array_map(function($g) {
			return [
				'gid' => $g->getGID(),
				'displayName' => $g->getDisplayName(),
				'icon' => 'icon-group', // 'icon-user',
				'isNoUser' => true
			];
		}, $groups);
	}

	public function getEmail(IUser $user): string {
		$email = $user->getEMailAddress();
		if (!is_string($email) || strlen($email) < 1) {
			throw new UserNotFoundException($user);
		}
		return strtolower($email);
	}

	public function getUserLists(IUser $user): array {
		$email = $this->getEmail($user);
		$lists = array();
		$clists = $this->listsFromConfig();
		$domain = $this->config->getAppValue('domain');
		foreach ($clists as $l) {
			if (is_array($l) && array_key_exists('id', $l)) {
				$members = array();
				$included = false;
				$groups = (array_key_exists('groups', $l)) ? $l['groups'] : [];
				foreach ($groups as $g) {
					$groupMembers = array();
					$group = $this->groupManager->get($g);
					$users = $group->getUsers();
					foreach ($users as $u) {
						$userEmail = $u->getEMailAddress();
						array_push($groupMembers, [
							'uid' => $u->getUID(),
							'display_name' => $u->getDisplayName(),
							'email' => strtolower($userEmail)
						]);
						if (strcmp($email, $userEmail) === 0) {
							$included = true;
						}
					}
					array_push($members, [
						'gid' => $g,
						'members' => $groupMembers
					]);
				}
				$extra = (array_key_exists('extra', $l)) ? $l['extra'] : [];
				/* $extraMembers = array();
				foreach ($extra as $e) {
					array_push($extraMembers, [
						'uid' => null,
						'display_name' => $e,
						'email' => $e
					]);
				} */
				$exclude = (array_key_exists('exclude', $l)) ? $l['exclude'] : [];
				$subscribed = !in_array($email, $exclude);
				$show = (array_key_exists('show', $l)) ? $l['show'] : false;
				if ($show || $included) {
					array_push($lists, [
						'id' => $l['id'],
						'fqdn' => $l['id'] . '@' . $domain,
						'groups' => $members,
						'extra' => $extra,
						'included' => $included,
						'subscribed' => $subscribed
					]);
				}
			}
		}
		/*
		foreach ($this->mmlists() as $mml) {
			if (is_array($mml) && array_key_exists('list_name', $mml)) {
				$name = $mml['list_name'];
				$members = $this->mmListMembers($name);
				if (in_array($email, $members)) {
					array_push($lists, [
						'id' => $name,
						'subscribed' => true
					]);
				}
			}
		}
		$clists = $this->config->getLists();
		if (is_array($clists)) {
			foreach ($clists as $l) {
				if (is_array($l) && array_key_exists('id', $l)
					&& array_key_exists('exclude', $l) && is_array($l['exclude'])
				) {
					if (in_array($email, $l['exclude'])) {
						$id = $l['id'];
						if ($this->findList($lists, $id) !== false) {
							$this->logger->error(
								'[ListService] Email "'.$email
								.'" is subscribed to "'.$id
								.'" but also in exclude list'
							);
						} else {
							array_push($lists, [
								'id' => $id,
								'subscribed' => false
							]);
						}
					} // else {
						// $this->logger->info('Not in exclude list "'.$email.'" ('.print_r($l, true).')');
					// } 
				}
			}
		}  */
		return $lists;
	}

	public function subscribeUser(IUser $user, string $list) {
		$email = $this->getEmail($user);
		if (!$this->config->removeExclude($list, $email)) {
			throw new UserNotFoundException($user);
		}
		$this->mm->subscribe($list, $email);
	}

	public function unsubscribeUser(IUser $user, string $list) {
		$email = $this->getEmail($user);
		if (!$this->config->addExclude($list, $email)) {
			throw new UserNotFoundException($user);
		}
		$this->mm->unsubscribe($list, $email);
	}

	public function onGroupDeleted(IGroup $group) {
		$gid = $group->getGID();
		$lists = $this->listsFromConfig();
		$needUpdate = false;
		foreach ($lists as $l) {
			if (is_array($l) && array_key_exists('id', $l)) {
				$groups = (array_key_exists('groups', $l)) ? $l['groups'] : [];
				if (in_array($gid, $groups)) {
					$this->logger->info('Removing group "'.$gid.'" from "'.$l['id']);
					$this->config->updateList($l['id'], [
						'groups' => array_diff($groups, [ $gid ])
					]);
					$needUpdate = true;
				}
			}
		}
		if ($needUpdate) {
			$actions = $this->checkLists();
			$this->logger->info(
				'Group "'.$gid.'" deleted -> MM actions: '
				.print_r($actions, true)
			);
			$this->updateLists($actions);
		}
	}

	public function onUserCreated(IUser $user) {
		$email = $user->getEMailAddress();
		if (!is_string($email) || strlen($email) < 1) {
			return;
		}
		$email = strtolower($email);
		$lists = $this->listsFromConfig();
		foreach ($lists as $l) {
			if (is_array($l) && array_key_exists('id', $l)) {
				$public = array_key_exists('show', $l) && $l['show'];
				$this->mm->updateNonMembers($l['id'], $public, [ $email ]);
			}
		}
	}

	public function onUserDeleted(IUser $user) {
		$email = $user->getEMailAddress();
		if (!is_string($email) || strlen($email) < 1) {
			return;
		}
		$email = strtolower($email);
		$lists = $this->listsFromConfig();
		foreach ($lists as $l) {
			if (is_array($l) && array_key_exists('id', $l)) {
				$this->config->removeExclude($l['id'], $email);
				$public = array_key_exists('show', $l) && $l['show'];
				$this->mm->updateNonMembers($l['id'], $public);
			}
		}
		$actions = $this->checkLists();
		$this->logger->info(
			'User "'.$user->getUID().'" deleted -> MM actions: '
			.print_r($actions, true)
		);
		$this->updateLists($actions);
	}

	public function onUserAdded(IGroup $group, IUser $user) {
		// This is (unfortunately) called *before* the user was actually
		// added to the group, so we can't rely on our default handling...
/*		$actions = $this->checkLists();
		$this->logger->info(
			'User "'.$user->getUID().'" added to "'.$group->getGID()
			.'" -> MM actions: '.print_r($actions, true)
		);
		$this->updateLists($actions);
		*/
		$email = $user->getEMailAddress();
		if (!is_string($email) || strlen($email) < 1) {
			return;
		}
		$email = strtolower($email);
		$gid = $group->getGID();
		$lists = $this->listsFromConfig();
		foreach ($lists as $l) {
			if (is_array($l)
				&& array_key_exists('id', $l)
				&& array_key_exists('groups', $l)
				&& in_array($gid, $l['groups'])
			) {
				$mmmembers = $this->mmListMembers($l['id']);
				if (!in_array($email, $mmmembers)) {
					$this->logger->info(
						'User "'.$user->getUID().'" added to "'.$gid
						.'" -> Adding to MM list.'
					);
					try {
						$this->mm->subscribe($l['id'], $email);
					} catch (MailmanException $e) {
						$this->logger->error('SUBSCRIBE failed: '
							. $e->getMessage()
						);
					}
				}
			}
		}
	}

	public function onUserRemoved(IGroup $group, IUser $user) {
		$email = $user->getEMailAddress();
		if (!is_string($email) || strlen($email) < 1) {
			return;
		}
		$email = strtolower($email);
		$gid = $group->getGID();
		$lists = $this->listsFromConfig();
		for ($i=0; $i<count($lists); $i++) {
			$l = $lists[$i];
			if (is_array($l) && array_key_exists('groups', $l)
				&& in_array($gid, $l['groups'])
				&& array_key_exists('exclude', $l)
				&& in_array($email, $l['exclude'])
			) {
				$lists[$i]['exclude'] = array_diff($lists[$i]['exclude'], [$email]);
			}
		}
		$this->config->setLists($lists);
		$actions = $this->checkLists();
		$this->logger->info(
			'User "'.$user->getUID().'" removed from "'.$group->getGID()
			.'" -> MM actions: '.print_r($actions, true)
		);
		$this->updateLists($actions);
	}

	public function checkLists($lists = null): array {

		if ($lists === null) {
			$lists = $this->listsFromConfig();
		}

		$create = array();
		$delete = array();
		$subscribe = array();
		$unsubscribe = array();

		// delete
		foreach ($this->mmlists() as $mml) {
			if (is_array($mml) && array_key_exists('list_name', $mml)) {
				$this->logger->debug(
					'Checking MM list "'.$mml['list_name'].'" against lists',
					[ 'json' => json_encode($lists) ]
				);
				$l = $this->findList($lists, $mml['list_name']);
				if ($l === false) {
					$this->logger->debug('--> NOT FOUND, will be DELETED');
					array_push($delete, $mml['list_name']);
						/*
						array_push($lists, [
							'id' => $mml['list_name'],
							'groups' => [],
							'exclude' => [],
							'extra' => []
						]);
						*/
				} else {
					$this->logger->debug('--> FOUND.');
				}
			}
		}

		// create
		foreach ($lists as $l) {
			if (is_array($l) && array_key_exists('id', $l)) {
				$this->logger->debug(
					'Checking list "'.$l['id'].'" against MM lists',
					[ 'json' => json_encode($this->mmlists()) ]
				);
				$mml = $this->findMMList($l['id'], $this->mmlists());
				if ($mml === false) {
					$this->logger->debug('--> NOT FOUND, needs to be CREATED');
					array_push($create, $l['id']);
				} else {
					$this->logger->debug('--> FOUND.');
				}
			}
		}

		// subscribe
		foreach ($lists as $l) {
			if (is_array($l) && array_key_exists('id', $l)) {
				$members = $this->listMembers($lists, $l['id']);
				$mmmembers = (!in_array($l['id'], $create)) ? $this->mmListMembers($l['id']) : [];
				$this->logger->debug(
					'Checking "'.$l['id'].'" list members ('
					.implode(', ', $members).') against MM list members ('
					.implode(', ', $mmmembers).')...'
				);
				foreach ($members as $email) {
					if (!in_array($email, $mmmembers)) {
						$d = array(
							'list' => $l['id'],
							'email' => $email
						);
						array_push($subscribe, $d);
					}
				}
			}
		}

		// unsubscribe
		if (is_array($this->mmlists())) {
			foreach ($this->mmlists() as $mml) {
				if (is_array($mml) && array_key_exists('list_name', $mml)
					&& !in_array($mml['list_name'], $delete)
				) {
					$mmmembers = $this->mmListMembers($mml['list_name']);
					$members = $this->listMembers($lists, $mml['list_name']);
					$this->logger->debug(
						'Checking "'.$mml['list_name'].'" MM list members ('
						.implode(', ', $mmmembers).') against list members ('
						.implode(', ', $members).')...'
					);
					foreach ($mmmembers as $email) {
						if (!in_array($email, $members)) {
							$d = array(
								'list' => $mml['list_name'],
								'email' => $email
							);
							array_push($unsubscribe, $d);
						}
					}
				}
			}
		}

		$result = [
			'create' => $create,
			'delete' => $delete,
			'subscribe' => $subscribe,
			'unsubscribe' => $unsubscribe 
		];
		return $result;
	}

	public function updateLists(array $actions, bool $debug = false) {
		if (array_key_exists('delete', $actions)) {
			foreach ($actions['delete'] as $list) {
				if ($debug) {
					$this->logger->info('[updateLists] DELETE "' . $list . '"');
				} else {
					try {
						$this->mm->deleteList($list);
					} catch (MailmanException $e) {
						$this->logger->error('[updateLists] DELETE failed: ' . $e->getMessage());
					}
				}
			}
		}
		if (array_key_exists('create', $actions)) {
			foreach ($actions['create'] as $list) {
				$l = $this->findList(null, $list);
				$public = (is_array($l) && array_key_exists('show', $l) && $l['show']);
				if ($debug) {
					$this->logger->info('[updateLists] CREATE "' . $list . '"');
				} else {
					try {
						$this->mm->createList($list, $public);
					} catch (MailmanException $e) {
						$this->logger->error('[updateLists] CREATE failed: ' . $e->getMessage());
					}
				}
			}
		}
		if (array_key_exists('subscribe', $actions)) {
			foreach ($actions['subscribe'] as $sub) {
				if ($debug) {
					$this->logger->info(
						'[updateLists] SUBSCRIBE "' . $sub['email'] . '" TO "'
						. $sub['list'] . '"'
					);
				} else {
					try {
						$this->mm->subscribe($sub['list'], $sub['email']);
					} catch (MailmanException $e) {
						$this->logger->error('[updateLists] SUBSCRIBE failed: ' . $e->getMessage());
					}
				}
			}
		}
		if (array_key_exists('unsubscribe', $actions)) {
			foreach ($actions['unsubscribe'] as $sub) {
				if ($debug) {
					$this->logger->info(
						'[updateLists] UNSUBSCRIBE "' . $sub['email'] . '" FROM "'
						. $sub['list'] . '"'
					);
				} else {
					try {
						$this->mm->unsubscribe($sub['list'], $sub['email']);
					} catch (MailmanException $e) {
						$this->logger->error('[updateLists] UNSUBSCRIBE failed: ' . $e->getMessage());
					}
				}
			}
		}
	}

}
