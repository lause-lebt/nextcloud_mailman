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

use OCP\IConfig;


class ConfigService {

	private const KEYS_SERVER = [
		'url', 'cred', 'domain', 'limit'
	];

	private const DEFAULTS = [
		'url' => 'http://localhost:8001/3.1/',
		'cred' => 'restadmin:RESTADMIN_PASSWORD',
		'domain' => '',
		'limit' => '3000',
		'lists' => '[]'
	];

	/** @var IConfig */
	private $config;
	
	/** @var string */
    private $appName;

    public function __construct(IConfig $config, $AppName){
        $this->config = $config;
        $this->appName = $AppName;
        $this->setDefaults();
    }

    private function setDefaults() {
		foreach (self::DEFAULTS as $key => $value) {
			if (empty($this->getAppValue($key))) {
				$this->setAppValue($key, $value);
			}
		}
	}

    public function getAppValue(string $key) {
        return $this->config->getAppValue($this->appName, $key);
    }

    public function setAppValue(string $key, string $value) {
        $this->config->setAppValue($this->appName, $key, $value);
	}
	
	public function getServerConfig() {
		return [
			'url' => $this->getAppValue('url'),
			'cred' => $this->getAppValue('cred'),
			'domain' => $this->getAppValue('domain'),
			'limit' => intval($this->getAppValue('limit'))
		];
	}

	public function setServerConfig(array $param) {
		foreach (self::KEYS_SERVER as $key) {
			if (array_key_exists($key, $param)) {
				$this->setAppValue($key, strval($param[$key]));
			}
		}
	} 

	public function getLists(): array {
		return json_decode($this->getAppValue('lists'), true);
	}

	public function setLists(array $data) {
		$this->setAppValue('lists', json_encode($data));
	}

}
