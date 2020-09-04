<?php
namespace OCA\Mailman\Service;

use OCP\IL10N;
use OCP\ILogger;
use OCP\IGroupManager;

use OCA\Mailman\Service\ConfigService;
use OCA\Mailman\Service\MMService;

use Exception;
use OCA\Mailman\Exception\MailmanException;

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
	private $mmlists;
	

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

		$this->mmlists = $this->mm->getLists();
    }

	private function findList(array $lists, string $name) {
		foreach ($lists as $l) {
			if (is_array($l) && array_key_exists('id', $l)) {
				if (strcmp($l['id'], $name) === 0) {
					return $l;
				}
			} else {
				$this->logger->warning(
					'findList: no list id found ('.print_r($l, true).')'
				);
			}
		}
		return false;
	}

	private function findMMList(string $name) {
		foreach ($this->mmlists as $l) {
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
			$e = $u->getEmailAddress();
			if (is_string($e)) {
				array_push($emails, $e);
			}
		}
		return $emails;
	}

	private function listMembers(array $lists, string $name) {
		$emails = array();
		$list = $this->findList($lists, $name);
		if (is_array($list)) {
			$groups = (array_key_exists('groups', $list)) ? $list['groups'] : [];
			foreach ($groups as $g) {
				$emails = array_merge($emails, $this->groupMembers($g));
			}
			$exclude = (array_key_exists('exclude', $list)) ? $list['exclude'] : [];
			$emails = array_diff($emails, $exclude);
			$extra = (array_key_exists('extra', $list)) ? $list['extra'] : [];
			$emails = array_merge($emails, $extra);
		}
		return $emails;
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
		return $emails;
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

	public function checkLists($lists = null): array {

		if ($lists === null) {
			$lists = $this->config->getLists();
			if (!is_array($lists)) {
				$this->logger->warning(
					'[ListService] Config failure "lists"',
					[ 'data' => print_r($lists, true) ]
				);
				$lists = [];
			}	
		}

		$create = array();
		$delete = array();
		$subscribe = array();
		$unsubscribe = array();

		// create
		foreach ($lists as $l) {
			if (is_array($l) && array_key_exists('id', $l)) {
				$this->logger->debug(
					'Checking list "'.$l['id'].'" against MM lists',
					[ 'json' => json_encode($this->mmlists) ]
				);
				$mml = $this->findMMList($l['id'], $this->mmlists);
				if ($mml === false) {
					$this->logger->debug('--> NOT FOUND, needs to be CREATED');
					array_push($create, $l['id']);
				} else {
					$this->logger->debug('--> FOUND.');
				}
			}
		}

		// delete
		foreach ($this->mmlists as $mml) {
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

		// subscribe
		foreach ($lists as $l) {
			if (is_array($l) && array_key_exists('id', $l)) {
				$members = $this->listMembers($lists, $l['id']);
				$mmmembers = $this->mmListMembers($l['id']);
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
		if (is_array($this->mmlists)) {
			foreach ($this->mmlists as $mml) {
				if (is_array($mml) && array_key_exists('list_name', $mml)) {
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
					$this->mm->deleteList($list);
				}
			}
		}
		if (array_key_exists('create', $actions)) {
			foreach ($actions['create'] as $list) {
				if ($debug) {
					$this->logger->info('[updateLists] CREATE "' . $list . '"');
				} else {
					$this->mm->createList($list);
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
					$this->mm->subscribe($sub['list'], $sub['email']);
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
					$this->mm->unsubscribe($sub['list'], $sub['email']);
				}
			}
		}
	}

}
