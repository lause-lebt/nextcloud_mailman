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

namespace OCA\Mailman\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

use OCA\Mailman\Service\ConfigService;


class ConfigController extends Controller {

    public function __construct($AppName, IRequest $request, ConfigService $config ) {
        parent::__construct($AppName, $request);

        $this->config = $config;
    }
    
    /**
     * @NoAdminRequired
	 * @TrapError
     * NoCSRFRequired
     * PublicPage
    */
    public function getConfig(): JSONResponse {
        $params = [
            "url" => $this->config->getAppValue("url"),
	    	"cred" => $this->config->getAppValue("cred"),
	    	"domain" => $this->config->getAppValue("domain"),
	    	"limit" => $this->config->getAppValue("limit"),
            "lists" => $this->config->getAppValue("lists")
        ];
        return new JSONResponse($params);
	}
	
	public function setConfig(string $url, string $cred, string $domain, int $limit): JSONResponse {
		$this->config->setAppValue('url', $url);
		$this->config->setAppValue('cred', $cred);
		$this->config->setAppValue('domain', $domain);
		$this->config->setAppValue('limit', strval($limit));
		return new JSONResponse(null);
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
