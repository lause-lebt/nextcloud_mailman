<?php
/**
 * @author 2020 Florian Gmeiner <florian@tinkatinka.com>
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

use OCP\AppFramework\Http;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\ILogger;

use GuzzleHttp\Exception\ClientException;


class MMService {

	/** @var ConfigService */
	private $config;

	/** @var IClientService */
	private $clientService;

	/** @var string */
	private $appName;

	/** @var string */
	private $url;

	/** @var string */
	private $cred;

	/** @var string */
	private $domain;

	/** @var string */
	private $requestURL;

	/** @var ILogger */
	private $logger;



	public function __construct(ConfigService $config, IClientService $clientService, ILogger $logger, $AppName) {
		$this->config = $config;
		$this->clientService = $clientService;
		$this->appName = $AppName;
		$this->logger = $logger;
		$this->url = $config->getAppValue('url');
		$this->cred = $config->getAppValue('credentials');
		$this->domain = $config->getAppValue('domain');
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

	protected function get(string $query) {
		try {
			$client = $this->clientService->newClient();
			$response = $client->get($this->requestURL . '/' . $query);
		} catch (ClientException $e) {
			$this->logger->error('MM GET query failed: ' . $query . ' -> ' . $e->getMessage());
			return false;
		}
		$status = $response->getStatusCode();
		$body = $response->getBody();
		if (strlen($body) > 0) {
			$rdata = json_decode($body, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				$this->logger->error('JSON decode failed: ' . $body);
				return false;
			}
		} else {
			$rdata = '';
		}
		return $rdata;
	}

	protected function post(string $query, $data) {
		try {
			$client = $this->clientService->newClient();
			$response = $client->post($this->requestURL . '/' . $query, [
				'body' => $data
			]);
		} catch (ClientException $e) {
			$this->logger->error('MM POST query failed: ' . $query . ' -> ' . $e->getMessage());
			return false;
		}
		$status = $response->getStatusCode();
		$body = $response->getBody();
		if (strlen($body) > 0) {
			$rdata = json_decode($body, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				$this->logger->error('JSON decode failed: ' . $body);
				return false;
			}
		} else {
			$rdata = '';
		}
		return $rdata;
	}

	protected function delete(string $query) {
		try {
			$client = $this->clientService->newClient();
			$response = $client->delete($this->requestURL . '/' . $query);
		} catch (ClientException $e) {
			$this->logger->error('MM DELETE query failed: ' . $query . ' -> ' . $e->getMessage());
			return false;
		}
		$status = $response->getStatusCode();
		$body = $response->getBody();
		if (strlen($body) > 0) {
			$rdata = json_decode($body, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				$this->logger->error('JSON decode failed: ' . $body);
				return false;
			}
		} else {
			$rdata = '';
		}
		return $rdata;
	}


	public function getLists() {
		$l = $this->get('domains/' . $this->domain . '/lists');
		$this->logger->info('getLists: ' . print_r($l, true));
		return $l;
	}

	public function getMembers(string $list) {
		$l = $this->get('lists/' . $list . '.' . $this->domain . '/roster/member');
		$this->logger->info('getMembers "'.$list.'": ' . print_r($l, true));
		return $l;
	}

	public function findMember(string $list, string $email) {
		$members = $this->getMembers($list);
		if (is_array($members) && array_key_exists('entries', $members)) {
			foreach ($members['entries'] as $m) {
				if (is_array($m) && array_key_exists('email', $m) && strcmp($m['email'], $email) === 0) {
					$this->logger->info('findMember "'.$email.'" in "'.$list.'": ' . print_r($m, true));
					return $m;
				}
			}
		}
		$this->logger->error('findMember "'.$email.'" in "'.$list.'": FAILED');
		return false;
	}
		

	public function subscribe(string $list, string $email) {
		$l = $this->post('members', [
			'list_id' => $list.'.'.$this->domain,
			'subscriber' => $email,
			'pre_verified' => true,
			'pre_confirmed' => true,
			'pre_approved' => true
		]);
		if ($l === false || strlen($l) > 0) {
			$msg = ($l === false || strlen($l) === 0) ? "FAILED" : print_r($l, true);
			$this->logger->error('subscribe "'.$email.'" to "'.$list.'": ' . $msg);
			return false;
		} else {
			$this->logger->info('subscribe "'.$email.'" to "'.$list.'": SUCCESS');
			return true;
		}
	}

	public function unsubscribe(string $list, string $email) {
		$m = $this->findMember($list, $email);
		if (is_array($m) && array_key_exists('member_id', $m)) {
			$l = $this->delete('members' . '/' . $m['member_id']);
			if ($l === false || strlen($l) > 0) {
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
		

}
