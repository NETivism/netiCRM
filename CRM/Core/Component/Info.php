<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 * This interface defines methods that need to be implemented
 * for a component to introduce itself to the system.
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

abstract class CRM_Core_Component_Info {

  public $name;
  public $namespace;
  public $componentID;
  /*
     * Name of the class (minus component namespace path)
     * of the component invocation class'es name.
     */
  public const COMPONENT_INVOKE_CLASS = 'Invoke';

  /*
     * Name of the class (minus component namespace path)
     * of the component configuration class'es name.
     */
  public const COMPONENT_CONFIG_CLASS = 'Config';

  /*
     * Name of the class (minus component namespace path)
     * of the component BAO Query class'es name.
     */
  public const COMPONENT_BAO_QUERY_CLASS = 'BAO_Query';

  /*
     * Name of the class (minus component namespace path)
     * of the component user dashboard plugin.
     */
  public const COMPONENT_USERDASHBOARD_CLASS = 'Page_UserDashboard';

  /*
     * Name of the class (minus component namespace path)
     * of the component tab offered to contact record view.
     */
  public const COMPONENT_TAB_CLASS = 'Page_Tab';

  /*
     * Name of the class (minus component namespace path)
     * of the component tab offered to contact record view.
     */
  public const COMPONENT_ADVSEARCHPANE_CLASS = 'Form_Search_AdvancedSearchPane';

  /*
     * Name of the directory (assumed in component directory)
     * where xml resources used by this component live.
     */
  public const COMPONENT_XML_RESOURCES = 'xml';

  /*
     * Name of the directory (assumed in xml resources path)
     * containing component menu definition XML file names.
     */
  public const COMPONENT_MENU_XML = 'Menu';

  /*
     * Stores component information.
     * @var array component settings as key/value pairs
     */

  public $info;

  /*
     * Stores component keyword
     * @var string name of component keyword
     */

  protected $keyword;

  /**
   * Class constructor, sets name and namespace (those are stored
   * in the component registry (database) and no need to duplicate
   * them here, as well as populates the info variable.
   *
   * @param string $name Name of the component.
   * @param string $namespace Namespace prefix for component's files.
   * @param int $componentID ID of the component.
   */
  public function __construct($name, $namespace, $componentID) {
    $this->name = $name;
    $this->namespace = $namespace;
    $this->componentID = $componentID;
    $this->info = $this->getInfo();
    $this->info['url'] = $this->getKeyword();
  }

  /**
   * Provides base information about the component.
   * Needs to be implemented in component's information class.
   *
   * @return array Collection of required component settings.
   */
  abstract public function getInfo();

  /**
   * Provides permissions that are used by component.
   * Needs to be implemented in component's information class.
   *
   * @return array|null Collection of permissions, null if none.
   */
  abstract public function getPermissions();

  /**
   * Provides information about user dashboard element offered by this component.
   *
   * @return array|null Collection of required dashboard settings, null if no element offered.
   */
  abstract public function getUserDashboardElement();

  /**
   * Provides information about user dashboard element offered by this component.
   *
   * @return array|null Collection of required dashboard settings, null if no element offered.
   */
  abstract public function registerTab();

  /**
   * Provides information about advanced search pane offered by this component.
   *
   * @return array|null Collection of required pane settings, null if no element offered.
   */
  abstract public function registerAdvancedSearchPane();

  /**
   * Provides potential activity types that this
   * component might want to register in activity history.
   * Needs to be implemented in component's information class.
   *
   * @return array|null Collection of activity types.
   */
  abstract public function getActivityTypes();

  /**
   * Provides information whether given component is currently
   * marked as enabled in configuration.
   *
   * @return bool True if component is enabled, false if not.
   */
  public function isEnabled() {

    $config = CRM_Core_Config::singleton();
    if (in_array($this->info['name'], $config->enableComponents)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Provides component's configuration object.
   *
   * @return mixed Component's configuration object.
   */
  public function getConfigObject() {
    return $this->_instantiate(self::COMPONENT_CONFIG_CLASS);
  }

  /**
   * Provides component's menu definition object.
   *
   * @return mixed Component's menu definition object.
   */
  public function getMenuObject() {
    return $this->_instantiate(self::COMPONENT_MENU_CLASS);
  }

  /**
   * Provides component's invocation object.
   *
   * @return mixed Component's invocation object.
   */
  public function getInvokeObject() {
    return $this->_instantiate(self::COMPONENT_INVOKE_CLASS);
  }

  /**
   * Provides component's BAO Query object.
   *
   * @return mixed Component's BAO Query object.
   */
  public function getBAOQueryObject() {
    return $this->_instantiate(self::COMPONENT_BAO_QUERY_CLASS);
  }

  /**
   * Builds advanced search form's component specific pane.
   *
   * @param CRM_Core_Form $form The form object.
   */
  public function buildAdvancedSearchPaneForm(&$form) {
    $bao = $this->getBAOQueryObject();
    $bao->buildSearchForm($form);
  }

  /**
   * Provides component's user dashboard page object.
   *
   * @return mixed Component's User Dashboard applet object.
   */
  public function getUserDashboardObject() {
    return $this->_instantiate(self::COMPONENT_USERDASHBOARD_CLASS);
  }

  /**
   * Provides component's contact record tab object.
   *
   * @return mixed Component's contact record tab object.
   */
  public function getTabObject() {
    return $this->_instantiate(self::COMPONENT_TAB_CLASS);
  }

  /**
   * Provides component's advanced search pane's template path.
   *
   * @return string Component's advanced search pane's template path.
   */
  public function getAdvancedSearchPaneTemplatePath() {
    $fullpath = $this->namespace . '_' . self::COMPONENT_ADVSEARCHPANE_CLASS;
    return str_replace('_', DIRECTORY_SEPARATOR, $fullpath . '.tpl');
  }

  /**
   * Provides information whether given component uses system wide search.
   *
   * @return bool True if component needs search integration.
   */
  public function usesSearch() {
    return $this->info['search'] ? TRUE : FALSE;
  }

  /**
   * Provides the XML menu files.
   *
   * @return array Array of menu files.
   */
  public function menuFiles() {
    return CRM_Utils_File::getFilesByExtension($this->_getMenuXMLPath(), 'xml');
  }

  /**
   * Simple "keyword" getter.
   * FIXME: It should be protected so the keyword is not
   * FIXME: accessed from beyond component infrastructure.
   *
   * @return string|null Component keyword.
   */
  public function getKeyword() {
    return $this->keyword;
  }

  /**
   * Helper for figuring out menu XML file location.
   *
   * @return string Component's menu XML path.
   */
  private function _getMenuXMLPath() {
    global $civicrm_root;
    $fullpath = $this->namespace . '_' . self::COMPONENT_XML_RESOURCES . '_' . self::COMPONENT_MENU_XML;
    return CRM_Utils_File::addTrailingSlash($civicrm_root . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $fullpath));
  }

  /**
   * Helper for instantiating component's elements.
   *
   * @param string $cl Class suffix to instantiate.
   *
   * @return object Component's element as class instance.
   */
  private function _instantiate($cl) {
    $className = $this->namespace . '_' . $cl;
    require_once(str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php');
    return new $className();
  }
}
