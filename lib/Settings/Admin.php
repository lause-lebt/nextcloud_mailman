<?php
/**
 * @copyright Copyright (c) 2020 Florian Gmeiner <florian@tinkatinka.com>
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

namespace OCA\Mailman\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\ILogger;
use OCP\Settings\ISettings;
use OCP\Http\Client\IClientService;

use OCA\Mailman\Service\ConfigService;
use OCA\Mailman\Service\MMService;


class Admin implements ISettings {

	/** @var ConfigService */
	protected $config;

	/** @var MMService */
	protected $mm;

	/** @var ILogger */
	protected $logger;

	/** @var IL10N */
	protected $l;


	public function __construct(ConfigService $config, MMService $mm, ILogger $logger, IL10N $l) {
#	public function __construct(ConfigService $config, IClientService $client, ILogger $logger, IL10N $l) {
		$this->config = $config;
		$this->mm = $mm;
#		$this->mm = new MMService($config, $client , $logger, 'mailman');
		$this->logger = $logger;
		$this->l = $l;

		$this->logger->notice("Mailman settings NOTICE");
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$url = $this->config->getAppValue('url');
		$cred = $this->config->getAppValue('credentials');
		$domain = $this->config->getAppValue('domain');
		$limit = intval($this->config->getAppValue('limit'));
		$lists = $this->config->getAppValue('lists');
		$mmlists = $this->mm->getLists();

		$parameters = [
			'appId' => 'mailman',
			'url' => $url,
			'cred' => $cred,
			'domain' => $domain,
			'limit' => $limit,
			'lists' => $lists,
			'mmlists' => $mmlists
		];

		return new TemplateResponse('mailman', 'AdminConfig', $parameters);
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'mailman';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority() {
		return 90;
	}
}
