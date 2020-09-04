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

namespace OCA\Mailman\Settings;

use OCA\Mailman\Exception\MailmanException;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\ILogger;
use OCP\Settings\ISettings;
use OCP\IInitialStateService;
use OCP\IGroupManager;
//use OCP\Http\Client\IClientService;

use OCA\Mailman\Service\ConfigService;
use OCA\Mailman\Service\ListService;
use OCA\Mailman\Service\MMService;


class Admin implements ISettings {

	private const TEMPLATE_ADMIN_CFG = 'admin';

	/** @var string */
	protected $appName;

	/** @var ConfigService */
	protected $config;

	/** @var MMService */
	protected $mm;

	/** @var ListService */
	protected $listService;

	/** @var ILogger */
	protected $logger;

	/** @var IL10N */
	protected $l;

	/** @var IInitialStateService */
	private $initialStateService;

	/** @var IGroupManager */
	private $groupManager;


	public function __construct(
		ConfigService $config,
		MMService $mm,
		ListService $listService,
		ILogger $logger,
		IInitialStateService $initialStateService,
		IGroupManager $groupManager,
		IL10N $l,
		string $AppName
	) {
		$this->config = $config;
		$this->mm = $mm;
		$this->listService = $listService;
		$this->logger = $logger;
		$this->initialStateService = $initialStateService;
		$this->groupManager = $groupManager;
		$this->l = $l;
		$this->appName = $AppName;
	}


	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$url = $this->config->getAppValue('url');
		$cred = $this->config->getAppValue('cred');
		$domain = $this->config->getAppValue('domain');
		$limit = intval($this->config->getAppValue('limit'));
		$lists = $this->config->getLists();
		$mmlists = $this->mm->getLists();
		$preview = $this->listService->checkLists();

		$parameters = [
			'appid' => $this->appName,
			'status' => $this->mm->getStatus(),
			'url' => $url,
			'cred' => $cred,
			'domain' => $domain,
			'limit' => $limit,
			'lists' => $lists,
			'mmlists' => ($mmlists === false) ? [] : $mmlists,
			'preview' => $preview,
			'groups' => $this->listService->allGroups()
		];

        #Util::addScript($this->appName, 'mailman-settings');
        #Util::addStyle($this->appName, 'mm');
		$this->initialStateService->provideInitialState(
			$this->appName, 'settings', $parameters
		);
		return new TemplateResponse(
			$this->appName, self::TEMPLATE_ADMIN_CFG
		);
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return $this->appName;
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
