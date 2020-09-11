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

namespace OCA\Mailman\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\IRequest;
use OCP\ILogger;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Util;


use OCA\Mailman\Exception\MailmanException;
use OCA\Mailman\Exception\UserNotFoundException;
use OCA\Mailman\Service\ConfigService;
use OCA\Mailman\Service\ListService;
use OCA\Mailman\Service\MMService;
use OCA\Mailman\Service\ArchiveService;

class ArchiveController extends Controller {

	/** @var ConfigService */
	protected $configService;

	/** @var ArchiveService */
	protected $archiveService;
	
	/** @var ListService */
	protected $listService;
	
	/** @var ILogger */
	protected $logger;
	
	/** @var IL10N */
	protected $l;

	/** @var IUser */
	private $currentUser;

	/** @var IUserManager */
	private $userManager;


    public function __construct(
		$AppName,
		IRequest $request,
		ConfigService $configService,
		ArchiveService $archiveService,
		ListService $listService,
		IUserManager $userManager,
		IUserSession $userSession,
		ILogger $logger,
		IL10N $l
	) {
		parent::__construct($AppName, $request);
		
		$this->configService = $configService;
		$this->archiveService = $archiveService;
		$this->listService = $listService;
		$this->logger = $logger;
		$this->l = $l;
		$this->userManager = $userManager;
		$this->currentUser = $userSession->getUser();
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function getArchive(string $id) {
		// Util::addStyle($this->appName, 'mm');
		$lists = $this->configService->getLists();
		if (!is_array($lists)) {
			$lists = [];
		}
		$list = $this->listService->findList($lists, $id);
		if ($list === false) {
			$this->logger->error('Mailing list not found: "'.$id.'"');
			return new NotFoundResponse();
			// return new TemplateResponse($this->appName, self::TEMPLATE_NOTFOUND);
		}
		$members = $this->listService->listMembers($lists, $id, true);
		try {
			$email = $this->listService->getEmail($this->currentUser);
		} catch (UserNotFoundException $e) {
			$email = '';
		}
		if (!in_array($email, $members)) {
			$this->logger->error('User "'.$email.'" has no access to list "'.$id.'"');
			return new NotFoundResponse();
		}
		try {
			$data = $this->archiveService->get($id);
		} catch (MailmanException $e) {
			$this->logger->error('Error obtaining archive for "'.$id.'": ' . $e->getMessage());
			return new NotFoundResponse();
		}
		return new DataDisplayResponse($data);
	}

}
