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

    private $config;
    private $appName;

    public function __construct(IConfig $config, $AppName){
        $this->config = $config;
        $this->appName = $AppName;

        $this->setDefaults();
    }

    private function setDefaults() {
        
        if (empty($this->getAppValue('url')) ) { 
            $this->setAppValue('url','http://localhost:8001/3.1/') ;
        }

        if (empty($this->getAppValue('credentials')) ) { 
            $this->setAppValue('credentials','restadmin:RESTADMIN_PASSWORD') ;
	}

	if (empty($this->getAppValue('domain'))) {
		$this->setAppValue('domain','');
	}

	if (empty($this->getAppValue('limit'))) {
		$this->setAppValue('limit', '3000');
	}

	if (empty($this->getAppValue('lists'))) {
		$this->setAppValue('exclude', json_encode(array()));
	}

    }

    public function getAppValue($key) {
        return $this->config->getAppValue($this->appName, $key);
    }

    public function setAppValue($key, $value) {
        $this->config->setAppValue($this->appName, $key, $value);
    }

}
