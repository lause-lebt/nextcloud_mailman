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
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\ILogger;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;

use OCA\Mailman\Exception\MailmanException;
use OCA\Mailman\Exception\UserNotFoundException;
use OCA\Mailman\Service\ConfigService;
use OCA\Mailman\Service\ListService;
use OCA\Mailman\Service\MMService;

class ApiController extends Controller {

	/** @var ConfigService */
	protected $configService;

	/** @var MMService */
	protected $mmService;
	
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
		MMService $mmService,
		ListService $listService,
		IUserManager $userManager,
		IUserSession $userSession,
		ILogger $logger,
		IL10N $l
	) {
		parent::__construct($AppName, $request);
		
		$this->configService = $configService;
		$this->mmService = $mmService;
		$this->listService = $listService;
		$this->logger = $logger;
		$this->l = $l;
		$this->userManager = $userManager;
		$this->currentUser = $userSession->getUser();
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function getUserLists(): JSONResponse {
		try {
			$lists = $this->listService->getUserLists($this->currentUser);
			return new JSONResponse([
				'lists' => $lists
			]);
		} catch (UserNotFoundException $e) {
			return new JSONResponse([
				'error' => $e->getMessage()
			]);
		}
	}

	/**
	 * @NoAdminRequired
	 */
	public function subscribeUser(string $list): JSONResponse {
		// $this->logger->info('Subscribe "'.$this->currentUser->getEMailAddress().'" to "'.$list.'"');
		// return new JSONResponse(null);
		try {
			$this->listService->subscribeUser($this->currentUser, $list);
			return new JSONResponse(null);
		} catch (MailmanException $e) {
			return new JSONResponse([
				'error' => $e->getMessage()
			]);
		}
	}
    
	/**
	 * @NoAdminRequired
	 */
	public function unsubscribeUser(string $list): JSONResponse {
		// $this->logger->info('UnSubscribe "'.$this->currentUser->getEMailAddress().'" from "'.$list.'"');
		// return new JSONResponse(null);
		try {
			$this->listService->unsubscribeUser($this->currentUser, $list);
			return new JSONResponse(null);
		} catch (MailmanException $e) {
			return new JSONResponse([
				'error' => $e->getMessage()
			]);
		}
	}
    /**
     * NoAdminRequired
     * NoCSRFRequired
     * PublicPage
    */
    public function getServerConfig(): JSONResponse {
        return new JSONResponse($this->configService->getServerConfig());
	}
	
	public function setServerConfig(string $url, string $cred, string $kitty, string $domain, int $limit): JSONResponse {
		try {
			$oldlimit = intval($this->configService->getAppValue('limit'));
			$this->logger->info(
				'ServerConfig limit='.$limit.' (old limit='.$oldlimit.')'
			);
			if ($limit !== $oldlimit) {
				$this->mmService->updateLimit($limit);
			}
		} catch (MailmanException $e) {
			return new JSONResponse([
				'error' => $e->getMessage()
			]);
		}
		$this->configService->setServerConfig([
			'url' => $url,
			'cred' => $cred,
			'kitty' => $kitty,
			'domain' => $domain,
			'limit' => $limit
		]);
		return new JSONResponse(null);
	}

	public function getServerStatus(): JSONResponse {
		return new JSONResponse($this->mmService->getStatus());
	}

	public function getListData(): JSONResponse {
		$lists = $this->configService->getLists();
//		$mmlists = $this->mmService->getLists();
		$preview = $this->listService->checkLists();
		return new JSONResponse([
			'lists' => $lists,
//			'mmlists' => ($mmlists === false) ? [] : $mmlists,
			'preview' => $preview
		]);
	}

	public function setListData($lists): JSONResponse {
		$this->logger->debug(
			'[ApiController] setListData: '.print_r($lists, true)
		);
		try {
			$this->configService->setLists($lists);
			$actions = $this->listService->checkLists($lists);
			$this->listService->updateLists($actions);
		} catch (MailmanException $e) {
			return new JSONResponse([
				'error' => $e->getMessage()
			]);
		}
		return new JSONResponse(null);
	}

	public function updateListData($listid): JSONResponse {
		$params = $this->request->getParams();
		$this->logger->debug(
			'[ApiController] updateListData: '.print_r($params, true)
		);
		try {
			if (array_key_exists('show', $params)) {
				$l = $this->listService->findList(null, $listid);
				$oldshow = (is_array($l) && array_key_exists('show', $l) && boolval($l['show']));
				$newshow = boolval($params['show']);
				if ($oldshow !== $newshow) {
					$this->mmService->updateNonMembers($listid, $newshow);
				}
			}
			if (!$this->configService->updateList($listid, $params)) {
				throw new MailmanException('Update failed');
			}
		} catch (MailmanException $e) {
			return new JSONResponse([
				'error' => $e->getMessage()
			]);
		}
		return new JSONResponse(null);
	}

	public function getPreview($lists): JSONResponse {
//		$params = $this->request->post;
/*
		$this->logger->info('[ApiController] preview request: '. print_r([
			'method' => $this->request->getMethod(),
			'lists' => print_r($lists, true)
//			'params' => print_r($params, true)
		], true));
*/
//		$lists = $params;
		$preview = $this->listService->checkLists($lists);
		$this->logger->debug(
			'[ApiController] preview: '.print_r($preview, true)
		);
		return new JSONResponse($preview);
	}

	/*
    public function setConfig($key, $value): JSONResponse {
        $this->config->setAppValue($key, $value);
        return new JSONResponse(null);
    }*/

    /*
    public function removeExclude($group) {
	    $el = json_decode($this->config->getAppValue("exclude");
	    $idx = array_search($group, $el);
	    if ($idx !== false) {
		    array_splice($el, $idx, 1);
		    $this->config->setAppValue("exclude", json_encode($el));
	    }
    }

    public function addExclude($group) {
	    $el = json_decode($this->config->getAppValue("exclude");
            array_push($el, $group);
            $this->setConfig("exclude", json_encode($el));
    }*/

    /*
    public function removeUser($list, $email) {
	    $ml = json_decode($this->config->getAppValue("extra_users");
	    if (array_key_exists($ml, $list)) {
		    $ul = $ml[$list];
		    $idx = array_search($email, $ul);
		    if ($idx !== false) {
			    array_splice($ul, $idx, 1);
			    $ml[$list] = $ul;
			    $this->setConfig("exclude", json_encode($ml));
		    }
	    }
    }

    public function addUser($list, $email) {
	    $ml = json_decode($this->config->getAppValue("extra_users");
	    $ul = array();
	    if (array_key_exists($ml, $list)) {
		$ul = $ml[$list];
	    }
	    array_push($ul, $email);
	    $ml[$list] = $ul;
	    $this->setConfig("extra_users", json_encode($ml));
    }*/

}
