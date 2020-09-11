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

use OCA\Mailman\Service\ConfigService;
use OCA\Mailman\Exception\MailmanException;

use OCP\AppFramework\Http;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IUserManager;
use OCP\ILogger;

use GuzzleHttp\Exception\ClientException;


class MMService {

	/** @var ConfigService */
	private $config;

	/** @var IClientService */
	private $clientService;

	/** @var IUserManager */
	private $userManager;

	/** @var string */
	private $appName;

	/** @var string */
	private $url;

	/** @var string */
	private $cred;

	/** @var string */
	private $domain;

	/** @var int */
	private $limit;

	/** @var string */
	private $requestURL;

	/** @var ILogger */
	private $logger;



	public function __construct(
		ConfigService $config,
		IClientService $clientService,
		IUserManager $userManager,
		ILogger $logger,
		$AppName
	) {
		$this->config = $config;
		$this->clientService = $clientService;
		$this->userManager = $userManager;
		$this->appName = $AppName;
		$this->logger = $logger;
		$this->url = $config->getAppValue('url');
		$this->cred = $config->getAppValue('credentials');
		$this->domain = $config->getAppValue('domain');
		$this->limit = intval($config->getAppValue('limit'));
		$host = trim($this->url, '/');
		$scheme = 'http';
		$pos = stripos($host, '://');
		if ($pos !== false) {
			if ($pos > 0) {
				$scheme = substr($host, 0, $pos);
			}
			$host = substr($host, $pos+3);
			if ($host === false) {
				$host = 'localhost:8001';
			}
		}
		$this->requestURL = $scheme . '://' . $this->cred . '@' . $host;
	}

	protected function request(string $method, string $query, $data = null, $type = 'form_params') {
		try {
			$client = $this->clientService->newClient();
			switch (strtolower($method)) {
				case 'get':
					$response = $client->get($this->requestURL . '/' . $query);
					break;
				case 'post':
					$response = $client->post($this->requestURL . '/' . $query, [
						$type => $data
					]);
					break;
				case 'put':
					$response = $client->put($this->requestURL . '/' . $query, [
						$type => $data
					]);
					break;
				case 'delete':
					$response = $client->delete($this->requestURL . '/' . $query);
					break;
				default:
					throw new MailmanException('Unknown http method "'.$method.'"');
			}
		} catch (ClientException $e) {
			$this->logger->error(
				'Mailman "' . strtoupper($method) . '" query failed: '
				. $query . ' -> ' . $e->getMessage()
			);
			throw new MailmanException($e->getMessage());
		}
		$status = $response->getStatusCode();
		$body = $response->getBody();
		if (strlen($body) > 0) {
			$rdata = json_decode($body, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				$msg = json_last_error_msg();
				if ($msg === false) {
					$msg = 'N/N';
				}
				$msg = 'JSON decode failed. Reason: '.$msg;
				$this->logger->error($msg, [ 'response' => $body ]);
				throw new MailmanException($msg);
			}
		} else {
			$rdata = [];
		}
		return $rdata;
	}


	protected function get(string $query) {
		return $this->request('get', $query);
	}

	protected function post(string $query, $data) {
		return $this->request('post', $query, $data);
	}

	protected function put(string $query, $data) {
		return $this->request('put', $query, $data);
	}

	protected function delete(string $query) {
		return $this->request('delete', $query);
	}


	public function getVersions(): array {
		$l= $this->get('system/versions');
		$this->logger->debug('getVersions: ' . print_r($l, true));
		return $l;
	}

	public function getStatus(): array {
		try {
			$status = $this->getVersions();
		} catch (MailmanException $e) {
			return [
				'active' => false,
				'error' => $e->getMessage()
			];
		}
		$status['active'] = true;
		return $status;
	}

	public function getLists(): array {
		try {
			$l = $this->get('domains/' . $this->domain . '/lists');
		} catch (MailmanException $e) {
			return [];
		}
		$this->logger->debug('getLists: ' . print_r($l, true));
		if (is_array($l) && array_key_exists('entries', $l)) {
			return $l['entries'];
		} else {
			return [];
		}
	}

	public function allUsers(): array {
		$emails = array();
		$users = $this->userManager->search('');
		foreach ($users as $u) {
			$e = $u->getEMailAddress();
			if (is_string($e) && strlen($e) > 0) {
				array_push($emails, $e);
			}
		}
		return $emails;
	}

	public function createList(string $list, bool $public): bool {
		$l = array();
		$l[] = $this->post('lists', [
			'fqdn_listname' => $list . '@' . $this->domain
		]);
		$q = 'lists/' . $list . '.' . $this->domain . '/config';
		$l[] = $this->put($q . '/display_name', [ 'display_name' => $list ]);
		$l[] = $this->put($q . '/subject_prefix', [ 'subject_prefix' => '['.$list.'] ' ]);
		$l[] = $this->put($q . '/subscription_policy', [ 'subscription_policy' => 'moderate' ]);
		$l[] = $this->put($q . '/max_message_size', [ 'max_message_size' => $this->limit ]);
		if ($public) {
			$emails = $this->allUsers();
			$l[] = $this->put($q . '/accept_these_nonmembers', [ 'accept_these_nonmembers' => $emails ]);
		}
		$this->logger->info('createList "'.$list.'": ' . print_r($l, true));
		return true;
	}

	public function deleteList(string $list): bool {
		$l = $this->delete('lists/' . $list . '.' . $this->domain);
		$this->logger->info('deleteList "'.$list.'": ' . print_r($l, true));
		return true;
	}

	public function getMembers(string $list): array {
		try {
			$l = $this->get('lists/' . $list . '.' . $this->domain . '/roster/member');
		} catch (MailmanException $e) {
			return [];
		}
		$this->logger->debug('getMembers "'.$list.'": ' . print_r($l, true));
		if (is_array($l) && array_key_exists('entries', $l)) {
			return $l['entries'];
		} else {
			return [];
		}
	}

	public function findMember(string $list, string $email) {
		$members = $this->getMembers($list);
		if (is_array($members)) {
			foreach ($members as $m) {
				if (is_array($m) && array_key_exists('email', $m) && strcmp($m['email'], $email) === 0) {
					$this->logger->debug('findMember "'.$email.'" in "'.$list.'": ' . print_r($m, true));
					return $m;
				}
			}
		}
		$this->logger->error('findMember "'.$email.'" in "'.$list.'": FAILED');
		return false;
	}

	public function subscribe(string $list, string $email): bool {
		$l = $this->post('members', [
			'list_id' => $list.'.'.$this->domain,
			'subscriber' => $email,
			'pre_verified' => true,
			'pre_confirmed' => true,
			'pre_approved' => true
		]);
		if ($l === false || is_string($l) && strlen($l) > 0) {
			$msg = ($l === false || strlen($l) === 0) ? "FAILED" : print_r($l, true);
			$this->logger->error('subscribe "'.$email.'" to "'.$list.'": ' . $msg);
			return false;
		} else {
			$this->logger->info('subscribe "'.$email.'" to "'.$list.'": SUCCESS');
			return true;
		}
	}

	public function unsubscribe(string $list, string $email): bool {
		$m = $this->findMember($list, $email);
		if (is_array($m) && array_key_exists('member_id', $m)) {
			$l = $this->delete('members' . '/' . $m['member_id']);
			if ($l === false || is_string($l) && strlen($l) > 0) {
				$this->logger->error('unsubscribe "'.$email.'" from "'.$list.'": ' . print_r($l, true));
				return false;
			} else {
				$this->logger->info('unsubscribe "'.$email.'" from "'.$list.'": SUCCESS');
				return true;
			}
		} else {
			$this->logger->error('unsubscribe "'.$email.'" from "'.$list.'": FAILED');
			return false;
		}
	}

	public function updateLimit(int $limit) {
		foreach ($this->getLists() as $l) {
			if (is_array($l) && array_key_exists('list_name', $l)) {
				$q = 'lists/' . $l['list_name'] . '.' . $this->domain . '/config/max_message_size';
				$this->put($q, [ 'max_message_size' => $limit ]);
			}
		}
	}

	public function updateNonMembers(string $list, bool $public) {
		$q = 'lists/' . $list . '.' . $this->domain . '/config/accept_these_nonmembers';
		$emails = $public ? $this->allUsers() : [];
		$this->put($q, [ 'accept_these_nonmembers' => $emails ], 'json');
	}

}
