<?php
/**
 *  File for the CiviUnitTestCase class
 *
 *  (PHP 5)
 *
 *   @copyright Copyright CiviCRM LLC (C) 2009
 *   @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 *   @package   CiviCRM
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

/**
 *  Include configuration
 */
define('CIVICRM_SETTINGS_PATH', __DIR__ . '/civicrm.settings.dist.php');
define('CIVICRM_SETTINGS_LOCAL_PATH', __DIR__ . '/civicrm.settings.local.php');

if(file_exists(CIVICRM_SETTINGS_LOCAL_PATH)){
  require_once CIVICRM_SETTINGS_LOCAL_PATH;
}
require_once CIVICRM_SETTINGS_PATH;

/**
 *  Include class definitions
 */
require_once 'tests/phpunit/Utils.php';
require_once 'api/api.php';
define('API_LATEST_VERSION', 3);
// detect php version
if (PHP_MAJOR_VERSION === 5) {
  require_once __DIR__.'/CiviUnitTestCase.5.php';
}
elseif(PHP_MAJOR_VERSION === 7) {
  require_once __DIR__.'/CiviUnitTestCase.7.php';
}