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

use OCA\Mailman\Exception\UserNotFoundException;
use OCA\Mailman\Service\ConfigService;
use OCA\Mailman\Service\ListService;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Controller;
use OCP\IInitialStateService;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Util;


class PageController extends Controller {

	private const TEMPLATE_MAIN = 'index';
	private const TEMPLATE_NOTFOUND = 'notfound';

	/** @var IInitialStateService */
	private $initialStateService;

	/** @var IL10N */
	private $l10n;
	
	/** @var ILogger */
	private $logger;
	
	/** @var IUserManager */
	private $userManager;
		
	/** @var IUserSession */
	private $userSession;

	/** @var ConfigService */
	private $configService;

	/** @var ListService */
	private $listService;
	
	public function __construct(
		$AppName,
		IRequest $request,
		IInitialStateService $initialStateService,
		IL10N $l10n,
		ILogger $logger,
		IUserManager $userManager,
		IUserSession $userSession,
		ConfigService $configService,
		ListService $listService
	) {
		parent::__construct($AppName, $request);
		$this->initialStateService = $initialStateService;
		$this->l10n = $l10n;
		$this->logger = $logger;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->configService = $configService;
		$this->listService = $listService;
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index(): TemplateResponse {
		Util::addScript($this->appName, 'mailman-main');
		Util::addStyle($this->appName, 'mm');
//		$this->initialStateService->provideInitialState($this->appName, 'maxStringLengths', $this->maxStringLengths);
		return new TemplateResponse($this->appName, self::TEMPLATE_MAIN);
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function gotoList($id) {
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
		/*
		$members = $this->listService->listMembers($lists, $id, true);
		try {
			$email = $this->listService->getEmail($this->userSession->getUser());
		} catch (UserNotFoundException $e) {
			$email = '';
		}
		if (!in_array($email, $members)) {
			$this->logger->error('User "'.$email.'" has no access to list "'.$id.'"');
			return new NotFoundResponse();
		}*/
		Util::addStyle($this->appName, 'mm');
		Util::addScript($this->appName, 'mailman-main');
		$this->initialStateService->provideInitialState($this->appName, 'list', $id);
		return new TemplateResponse($this->appName, self::TEMPLATE_MAIN);
	}

}
