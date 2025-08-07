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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */


class CRM_Admin_Form_WordReplacements extends CRM_Core_Form {
  public $_soInstance;
  protected $_numStrings = 10;

  protected $_stringName = NULL;

  protected $_defaults = NULL; function preProcess() {
    $this->_soInstance = CRM_Utils_Array::value('instance', $_GET);
    $this->assign('soInstance', $this->_soInstance);
    $breadCrumbUrl = CRM_Utils_System::url('civicrm/admin/options/wordreplacements',
      "reset=1"
    );
    $breadCrumb = [['title' => ts('Word Replacements'),
        'url' => $breadCrumbUrl,
      ]];
    CRM_Utils_System::appendBreadCrumb($breadCrumb);
  }

  public function setDefaultValues() {
    if ($this->_defaults !== NULL) {
      return $this->_defaults;
    }

    $this->_defaults = [];

    $config = CRM_Core_Config::singleton();

    $values = $config->localeCustomStrings[$config->lcMessages];
    $i = 1;

    $enableDisable = [1 => 'enabled',
      0 => 'disabled',
    ];

    $cardMatch = ['wildcardMatch', 'exactMatch'];

    foreach ($enableDisable as $key => $val) {
      foreach ($cardMatch as $kc => $vc) {
        if (!empty($values[$val][$vc])) {
          foreach ($values[$val][$vc] as $k => $v) {
            $this->_defaults["enabled"][$i] = $key;
            $this->_defaults["cb"][$i] = $kc;
            $this->_defaults["old"][$i] = $k;
            $this->_defaults["new"][$i] = $v;
            $i++;
          }
        }
      }
    }

    $name = $this->_stringName = "custom_string_override_{$config->lcMessages}";
    if (isset($config->$name) &&
      is_array($config->$name)
    ) {
      $this->_numStrings = 1;
      foreach ($config->$name as $old => $newValues) {
        $this->_numStrings++;
        $this->_numStrings += 9;
      }
    }
    else {
      $this->_numStrings = 10;
    }

    return $this->_defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $config = CRM_Core_Config::singleton();
    $values = $config->localeCustomStrings[$config->lcMessages];
    $instances = (is_array($values) ? count($values, COUNT_RECURSIVE) - 6 : 0);
    if ($instances > 10) {
      $this->_numStrings = $instances;
    }

    $soInstances = range(1, $this->_numStrings, 1);
    $stringOverrideInstances = [];
    if ($this->_soInstance) {
      $soInstances = [$this->_soInstance];
    }
    elseif (CRM_Utils_Array::value('old', $_POST)) {
      $soInstances = $stringOverrideInstances = array_keys($_POST['old']);
    }
    elseif (!empty($this->_defaults) && is_array($this->_defaults)) {
      $stringOverrideInstances = array_keys($this->_defaults['new']);
      if (count($this->_defaults['old']) > count($this->_defaults['new'])) {
        $stringOverrideInstances = array_keys($this->_defaults['old']);
      }
    }
    foreach ($soInstances as $instance) {
      $this->addElement('checkbox', "enabled[$instance]");
      $this->add('textarea', "old[$instance]", NULL, ['rows' => 1, 'cols' => 40]);
      $this->add('textarea', "new[$instance]", NULL, ['rows' => 1, 'cols' => 40]);
      $this->addElement('checkbox', "cb[$instance]");
    }
    $this->assign('numStrings', $this->_numStrings);
    if ($this->_soInstance) {
      return;
    }

    $this->assign('stringOverrideInstances', empty($stringOverrideInstances) ? FALSE : $stringOverrideInstances);

    $this->addButtons([
        ['type' => 'next',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );
    $this->addFormRule(['CRM_Admin_Form_WordReplacements', 'formRule'], $this);
  }

  /**
   * global validation rules for the form
   *
   * @param array $values posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($values) {
    $errors = [];

    $oldValues = CRM_Utils_Array::value('old', $values);
    $newValues = CRM_Utils_Array::value('new', $values);
    $enabled = CRM_Utils_Array::value('enabled', $values);
    $exactMatch = CRM_Utils_Array::value('cb', $values);

    foreach ($oldValues as $k => $v) {
      if ($v && !$newValues[$k]) {
        $errors['new[' . $k . ']'] = ts('Please Enter the value for Replacement Word');
      }
      elseif (!$v && $newValues[$k]) {
        $errors['old[' . $k . ']'] = ts('Please Enter the value for Original Word');
      }
      elseif ((!$newValues[$k] && !$oldValues[$k]) && ($enabled[$k] || $exactMatch[$k])) {
        $errors['old[' . $k . ']'] = ts('Please Enter the value for Original Word');
        $errors['new[' . $k . ']'] = ts('Please Enter the value for Replacement Word');
      }
    }

    return $errors;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $this->_numStrings = sizeof($params['old']);

    $enabled['exactMatch'] = $enabled['wildcardMatch'] = $disabled['exactMatch'] = $disabled['wildcardMatch'] = [];
    for ($i = 1; $i <= $this->_numStrings; $i++) {
      if (CRM_Utils_Array::value($i, $params['new']) &&
        CRM_Utils_Array::value($i, $params['old'])
      ) {
        if (CRM_Utils_Array::value($i, $params['enabled'])) {
          if (is_array($params['cb']) && CRM_Utils_Array::arrayKeyExists($i, $params['cb'])) {
            $enabled['exactMatch'] += [$params['old'][$i] => $params['new'][$i]];
          }
          else {
            $enabled['wildcardMatch'] += [$params['old'][$i] => $params['new'][$i]];
          }
        }
        else {
          if (is_array($params['cb']) && CRM_Utils_Array::arrayKeyExists($i, $params['cb'])) {
            $disabled['exactMatch'] += [$params['old'][$i] => $params['new'][$i]];
          }
          else {
            $disabled['wildcardMatch'] += [$params['old'][$i] => $params['new'][$i]];
          }
        }
      }
    }

    $overrides = ['enabled' => $enabled,
      'disabled' => $disabled,
    ];

    $config = CRM_Core_Config::singleton();
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    if ($domain->locales && $config->localeCustomStrings) {
      // for multilingual
      $addReplacements = $config->localeCustomStrings;
      $addReplacements[$config->lcMessages] = $overrides;
      $stringOverride = serialize($addReplacements);
    }
    else {
      // for single language
      $stringOverride = serialize([$config->lcMessages => $overrides]);
    }

    $params = ['locale_custom_strings' => $stringOverride];
    $id = CRM_Core_Config::domainID();


    $wordReplacementSettings = CRM_Core_BAO_Domain::edit($params, $id);

    if ($wordReplacementSettings) {
      //reset navigation

      CRM_Core_BAO_Navigation::resetNavigation();

      CRM_Core_Session::setStatus("Your Settings have been saved");
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/options/wordreplacements',
          "reset=1"
        ));
    }
  }
}

