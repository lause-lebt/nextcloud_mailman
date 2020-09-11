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
use OCP\ILogger;

use GuzzleHttp\Exception\ClientException;


class ArchiveService {

	/** @var ConfigService */
	private $config;

	/** @var IClientService */
	private $clientService;

	/** @var string */
	private $appName;

	/** @var string */
	private $requestURL;

	/** @var string */
	private $domain;

	/** @var ILogger */
	private $logger;



	public function __construct(
		ConfigService $configService,
		IClientService $clientService,
		ILogger $logger,
		string $AppName
	) {
		$this->configService = $configService;
		$this->clientService = $clientService;
		$this->appName = $AppName;
		$this->logger = $logger;
		$this->requestURL = $configService->getAppValue('kitty');
		$this->domain = $configService->getAppValue('domain');
	}

	public function get($query): string {
		try {
			$client = $this->clientService->newClient();
			$response = $client->get($this->requestURL . '/list/' . $query . '@' . $this->domain);
		} catch (ClientException $e) {
			$this->logger->error(
				'Archive query failed: '
				. $query . ' -> ' . $e->getMessage()
			);
			throw new MailmanException($e->getMessage());
		}
		return $response->getBody();
	}

}