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
 * Component stores all the static and dynamic information of the various
 * CiviCRM components
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */
class CRM_Core_Component {

  /*
     * End part (filename) of the component information class'es name
     * that needs to be present in components main directory.
     */
  public const COMPONENT_INFO_CLASS = 'Info';

  private static $_info = NULL;

  public static $_contactSubTypes = NULL;

  /**
   * Returns information about enabled components.
   *
   * @return array List of enabled component information objects.
   */
  private static function &_info() {
    if (self::$_info == NULL) {
      self::$_info = [];
      $c = [];

      $config = CRM_Core_Config::singleton();
      $c = &self::getComponents();

      foreach ($c as $name => $comp) {
        if (in_array($name, $config->enableComponents)) {
          self::$_info[$name] = $comp;
        }
      }
    }

    return self::$_info;
  }

  /**
   * Gets a component object or a specific attribute of its info.
   *
   * @param string $name Component name.
   * @param string|null $attribute Specific attribute name from component info.
   *
   * @return mixed The component object or attribute value.
   */
  public static function get($name, $attribute = NULL) {
    $comp = CRM_Utils_Array::value($name, self::_info());
    if ($attribute) {
      return CRM_Utils_Array::value($attribute, $comp->info);
    }
    return $comp;
  }

  /**
   * Returns all registered components.
   *
   * @param bool $force Whether to force a reload of components from the registry.
   *
   * @return array List of component objects.
   */
  public static function &getComponents($force = FALSE) {
    static $_cache = NULL;

    if (!$_cache || $force) {
      $_cache = [];

      $cr = new CRM_Core_DAO_Component();
      $cr->find(FALSE);
      while ($cr->fetch()) {
        $infoClass = $cr->namespace . '_' . self::COMPONENT_INFO_CLASS;
        require_once(str_replace('_', DIRECTORY_SEPARATOR, $infoClass) . '.php');
        $infoObject = new $infoClass($cr->name, $cr->namespace, $cr->id);
        if ($infoObject->info['name'] !== $cr->name) {
          CRM_Core_Error::fatal("There is a discrepancy between name in component registry and in info file ({$cr->name}).");
        }
        $_cache[$cr->name] = $infoObject;
        unset($infoObject);
      }
    }

    return $_cache;
  }

  /**
   * Returns all enabled components.
   *
   * @return array List of enabled component objects.
   */
  public static function &getEnabledComponents() {
    return self::_info();
  }

  /**
   * Returns names of enabled components.
   *
   * @param bool $translated Whether to return translated names.
   *
   * @return array Associative array of component IDs and names.
   */
  public static function &getNames($translated = FALSE) {
    $allComponents = self::getComponents();

    $names = [];
    foreach ($allComponents as $name => $comp) {
      if ($translated) {
        $names[$comp->componentID] = $comp->info['translatedName'];
      }
      else {
        $names[$comp->componentID] = $name;
      }
    }
    return $names;
  }

  /**
   * Invokes a method on a component's invoke object.
   *
   * @param array $args Arguments passed to the component method.
   * @param string $type Invocation type (e.g., 'main', 'admin').
   *
   * @return bool True if a component was successfully invoked.
   */
  public static function invoke(&$args, $type) {
    $info = &self::_info();
    $config = CRM_Core_Config::singleton();

    $firstArg = CRM_Utils_Array::value(1, $args, '');
    $secondArg = CRM_Utils_Array::value(2, $args, '');
    foreach ($info as $name => $comp) {
      if (in_array($name, $config->enableComponents) &&
        (
          ($comp->info['url'] === $firstArg && $type == 'main') ||
          ($comp->info['url'] === $secondArg && $type == 'admin')
        )
      ) {
        if ($type == 'main') {
          // also set the smarty variables to the current component
          $template = CRM_Core_Smarty::singleton();
          $template->assign('activeComponent', $name);
          if (CRM_Utils_Array::value('formTpl', $comp->info[$name])) {
            $template->assign('formTpl', $comp->info[$name]['formTpl']);
          }
          if (CRM_Utils_Array::value('css', $comp->info[$name])) {
            $styleSheet = [
              'tag' => 'style',
              'attributes' => [
                'type' => 'text/css',
              ],
              'value' => '@import url(' . "{$config->resourceBase}css/{$comp->info[$name]['css']});",
            ];
            CRM_Utils_System::addHTMLHead($styleSheet);
          }
        }
        $inv = &$comp->getInvokeObject();
        $inv->$type($args);
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Collects all XML menu files from all components.
   *
   * @return array List of menu file paths.
   */
  public static function xmlMenu() {

    // lets build the menu for all components
    $info = &self::getComponents(TRUE);

    $files = [];
    foreach ($info as $name => $comp) {
      $files = array_merge(
        $files,
        $comp->menuFiles()
      );
    }

    return $files;
  }

  /**
   * Collects menu items from all enabled components.
   *
   * @return array List of menu items.
   */
  public static function &menu() {
    $info = &self::_info();
    $items = [];
    foreach ($info as $name => $comp) {
      $mnu = &$comp->getMenuObject();

      $ret = $mnu->permissioned();
      $items = array_merge($items, $ret);

      $ret = $mnu->main($task);
      $items = array_merge($items, $ret);
    }
    return $items;
  }

  /**
   * Adds configuration from all enabled components.
   *
   * @param CRM_Core_Config $config The system config object.
   * @param bool $oldMode Whether to use old mode.
   */
  public static function addConfig(&$config, $oldMode = FALSE) {
    $info = &self::_info();

    foreach ($info as $name => $comp) {
      $cfg = &$comp->getConfigObject();
      $cfg->add($config, $oldMode);
    }
    return;
  }

  /**
   * Gets the component ID for a given name.
   *
   * @param string $componentName The component name.
   *
   * @return int The component ID.
   */
  public static function getComponentID($componentName) {
    $info = &self::_info();

    return $info[$componentName]->componentID;
  }

  /**
   * Gets the component name for a given ID.
   *
   * @param int $componentID The component ID.
   *
   * @return string|null The component name.
   */
  public static function getComponentName($componentID) {
    $info = &self::_info();

    $componentName = NULL;
    foreach ($info as $compName => $component) {
      if ($component->componentID == $componentID) {
        $componentName = $compName;
        break;
      }
    }

    return $componentName;
  }

  /**
   * Collects search query fields from all components that use search.
   *
   * @return array List of search query fields.
   */
  public static function &getQueryFields() {
    $info = &self::_info();
    $fields = [];
    foreach ($info as $name => $comp) {
      if ($comp->usesSearch()) {
        $bqr = &$comp->getBAOQueryObject();
        $flds = &$bqr->getFields();
        $fields = array_merge($fields, $flds);
      }
    }
    return $fields;
  }

  /**
   * Alters search queries via component BAO query objects.
   *
   * @param CRM_Contact_BAO_Query $query The query object.
   * @param string $fnName The method name to call on BAO query objects.
   */
  public static function alterQuery(&$query, $fnName) {
    $info = &self::_info();

    foreach ($info as $name => $comp) {
      if ($comp->usesSearch()) {
        $bqr = &$comp->getBAOQueryObject();
        $bqr->$fnName($query);
      }
    }
  }

  /**
   * Determines FROM clause contribution for a search field from components.
   *
   * @param string $fieldName Field name.
   * @param int $mode Mode.
   * @param int $side Side.
   *
   * @return string|null The FROM clause fragment.
   */
  public static function from($fieldName, $mode, $side) {
    $info = &self::_info();

    $from = NULL;
    foreach ($info as $name => $comp) {
      if ($comp->usesSearch()) {
        $bqr = &$comp->getBAOQueryObject();
        $from = $bqr->from($fieldName, $mode, $side);
        if ($from) {
          return $from;
        }
      }
    }
    return $from;
  }

  /**
   * Gets default return properties for search from components.
   *
   * @param int $mode Mode.
   *
   * @return array|null Default return properties.
   */
  public static function &defaultReturnProperties($mode) {
    $info = &self::_info();

    $properties = NULL;
    foreach ($info as $name => $comp) {
      if ($comp->usesSearch()) {
        $bqr = &$comp->getBAOQueryObject();
        $properties = &$bqr->defaultReturnProperties($mode);
        if ($properties) {
          return $properties;
        }
      }
    }
    return $properties;
  }

  /**
   * Builds search form elements from components.
   *
   * @param CRM_Core_Form $form The form object.
   */
  public static function &buildSearchForm(&$form) {
    $info = &self::_info();

    foreach ($info as $name => $comp) {
      if ($comp->usesSearch()) {
        $bqr = &$comp->getBAOQueryObject();
        $bqr->buildSearchForm($form);
      }
    }
  }

  /**
   * Adds show/hide JavaScript from components.
   *
   * @param CRM_Core_ShowHide $showHide The show/hide object.
   */
  public static function &addShowHide(&$showHide) {
    $info = &self::_info();

    foreach ($info as $name => $comp) {
      if ($comp->usesSearch()) {
        $bqr = &$comp->getBAOQueryObject();
        $bqr->addShowHide($showHide);
      }
    }
  }

  /**
   * Performs search actions for components.
   *
   * @param array $row Row data.
   * @param int $id ID.
   */
  public static function searchAction(&$row, $id) {
    $info = &self::_info();

    foreach ($info as $name => $comp) {
      if ($comp->usesSearch()) {
        $bqr = &$comp->getBAOQueryObject();
        $bqr->searchAction($row, $id);
      }
    }
  }

  /**
   * Returns registered contact subtypes.
   *
   * @return array List of contact subtypes.
   */
  public static function &contactSubTypes() {
    if (self::$_contactSubTypes == NULL) {
      self::$_contactSubTypes = [];

      if (CRM_Core_Permission::access('Quest')) {

        // Generalize this at some point
        self::$_contactSubTypes = [
          'Student' =>
          ['View' =>
            ['file' => 'CRM/Quest/Page/View/Student.php',
              'class' => 'CRM_Quest_Page_View_Student',
            ],
          ],
        ];
      }
    }
    return self::$_contactSubTypes;
  }

  /**
   * Gets properties for a specific contact subtype and operation.
   *
   * @param string $subType Contact subtype.
   * @param string $op Operation name.
   *
   * @return array Properties array.
   */
  public static function &contactSubTypeProperties($subType, $op) {
    $properties = &self::contactSubTypes();
    if (CRM_Utils_Array::arrayKeyExists($subType, $properties) &&
      CRM_Utils_Array::arrayKeyExists($op, $properties[$subType])
    ) {
      return $properties[$subType][$op];
    }
    return CRM_Core_DAO::$_nullObject;
  }

  /**
   * Collects task lists from all enabled components.
   *
   * @return array List of tasks.
   */
  public static function &taskList() {
    $info = &self::_info();

    $tasks = [];
    foreach ($info as $name => $value) {
      if (CRM_Utils_Array::value('task', $info[$name])) {
        $tasks += $info[$name]['task'];
      }
    }
    return $tasks;
  }

  /**
   * Function to handle table dependencies of components.
   *
   * @param array $tables Array of tables.
   */
  public static function tableNames(&$tables) {
    $info = &self::_info();

    foreach ($info as $name => $comp) {
      if ($comp->usesSearch()) {
        $bqr = &$comp->getBAOQueryObject();
        $bqr->tableNames($tables);
      }
    }
  }
}
