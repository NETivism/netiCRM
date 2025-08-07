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


class CRM_Report_Form extends CRM_Core_Form {
  public $_section;
  /**
   * @var string|null
   */
  public $_description;
  public $_aliases;
  public $_columnHeaders;
  public $_grandFlag;
  public $_interval;
  public $_where;
  public $_sendmail;
  /**
   * @var bool
   */
  public $_absoluteUrl;
  public $_outputMode;
  public $_from;
  public $_groupBy;
  public $_orderBy;

  protected $_charts;

  CONST ROW_COUNT_LIMIT = 50;

  /**
   * Operator types - used for displaying filter elements
   */
  CONST OP_INT = 1, OP_STRING = 2, OP_DATE = 4, OP_FLOAT = 8, 
  OP_SELECT = 64, OP_MULTISELECT = 65, OP_MULTISELECT_SEPARATOR = 66;

  /**
   * The id of the report instance
   *
   * @var integer
   */
  protected $_id;

  /**
   * The id of the report template
   *
   * @var integer;
   */
  protected $_templateID;

  /**
   * The report title
   *
   * @var string
   */
  protected $_title;

  /**
   * The set of all columns in the report. An associative array
   * with column name as the key and attribues as the value
   *
   * @var array
   */
  protected $_columns = [];

  /**
   * The set of filters in the report
   *
   * @var array
   */
  protected $_filters = [];

  /**
   * The set of optional columns in the report
   *
   * @var array
   */
  protected $_options = [];

  protected $_defaults = [];

  /**
   * Set of statistic fields
   *
   * @var array
   */
  protected $_statFields = [];

  /**
   * Set of statistics data
   *
   * @var array
   */
  protected $_statistics = [];

  /**
   * List of fields not to be repeated during display
   *
   * @var array
   */
  protected $_noRepeats = [];

  /**
   * List of fields not to be displayed
   *
   * @var array
   */
  protected $_noDisplay = [];

  /**
   * Object type that a custom group extends
   *
   * @var null
   */
  protected $_customGroupExtends = NULL;
  protected $_customGroupFilters = TRUE;
  protected $_customGroupGroupBy = FALSE;

  /**
   * build tags filter
   *
   */
  protected $_tagFilter = FALSE;

  /**
   * Navigation fields
   *
   * @var array
   */
  public $_navigation = [];

  /**
   * An attribute for checkbox/radio form field layout
   *
   * @var array
   */
  protected $_fourColumnAttribute = ['</td><td width="25%">', '</td><td width="25%">',
    '</td><td width="25%">', '</tr><tr><td>',
  ];

  protected $_force = 1;

  protected $_params = NULL;
  protected $_formValues = NULL;
  protected $_instanceValues = NULL;

  protected $_instanceForm = FALSE;

  protected $_instanceButtonName = NULL;
  protected $_printButtonName = NULL;
  protected $_pdfButtonName = NULL;
  protected $_csvButtonName = NULL;
  protected $_groupButtonName = NULL;
  protected $_chartButtonName = NULL;
  protected $_csvSupported = TRUE;
  protected $_add2groupSupported = TRUE;
  protected $_groups = NULL;
  protected $_having = NULL;
  protected $_rowsFound = NULL;
  protected $_select = NULL;
  protected $_rollup = NULL;
  protected $_limit = NULL;

  /**
   * To what frequency group-by a date column
   *
   * @var array
   */
  protected $_groupByDateFreq = ['MONTH' => 'Month',
    'YEARWEEK' => 'Week',
    'QUARTER' => 'Quarter',
    'YEAR' => 'Year',
  ];

  /**
   * Variables to hold the acl inner join and where clause
   */
  protected $_aclFrom = NULL;
  protected $_aclWhere = NULL;

  /**
   *
   */
  function __construct() {
    parent::__construct();

    // build tag filter
    if ($this->_tagFilter) {
      $this->buildTagFilter();
    }

    // do not allow custom data for reports if user don't have
    // permission to access custom data.
    if (!empty($this->_customGroupExtends) && !CRM_Core_Permission::check('access all custom data')) {
      $this->_customGroupExtends = [];
    }

    // merge custom data columns to _columns list, if any
    $this->addCustomDataToColumns();
  }

  function preProcessCommon() {
    $this->_force = CRM_Utils_Request::retrieve('force',
      'Boolean',
      CRM_Core_DAO::$_nullObject
    );

    $this->_section = CRM_Utils_Request::retrieve('section', 'Integer', CRM_Core_DAO::$_nullObject);

    $this->assign('section', $this->_section);

    $this->_id = $this->get('instanceId');
    if (!$this->_id) {
      $this->_id = CRM_Report_Utils_Report::getInstanceID();
      if (!$this->_id) {
        $this->_id = CRM_Report_Utils_Report::getInstanceIDForPath();
      }
    }

    // set qfkey so that pager picks it up and use it in the "Next > Last >>" links,
    $_GET['qfKey'] = $this->controller->_key;

    if ($this->_id) {
      $this->assign('instanceId', $this->_id);
      $params = ['id' => $this->_id];
      $this->_instanceValues = [];
      CRM_Core_DAO::commonRetrieve('CRM_Report_DAO_Instance',
        $params,
        $this->_instanceValues
      );
      if (empty($this->_instanceValues)) {
        CRM_Core_Error::fatal("Report could not be loaded.");
      }

      if (!empty($this->_instanceValues['permission']) &&
        (!(CRM_Core_Permission::check($this->_instanceValues['permission']) ||
            CRM_Core_Permission::check('administer Reports')
          ))
      ) {
        CRM_Utils_System::permissionDenied();
        CRM_Utils_System::civiExit();
      }
      $this->_formValues = unserialize($this->_instanceValues['form_values']);

      // lets always do a force if reset is found in the url.
      if (CRM_Utils_Array::value('reset', $_GET)) {
        $this->_force = 1;
      }

      // set the mode
      $this->assign('mode', 'instance');
    }
    else {
      list($optionValueID, $optionValue) = CRM_Report_Utils_Report::getValueIDFromUrl();
      $instanceCount = CRM_Report_Utils_Report::getInstanceCount($optionValue);
      if (($instanceCount > 0) && $optionValueID) {
        $this->assign('instanceUrl',
          CRM_Utils_System::url('civicrm/report/list',
            "reset=1&ovid=$optionValueID"
          )
        );
      }
      if ($optionValueID) {
        $this->_description = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $optionValueID, 'description');
      }

      // set the mode
      $this->assign('mode', 'template');
    }

    // lets display the
    $this->_instanceForm = $this->_force || $this->_id || (!empty($_POST));

    // do not display instance form if administer Reports permission is absent
    if (!CRM_Core_Permission::check('administer Reports')) {
      $this->_instanceForm = FALSE;
    }

    $this->assign('criteriaForm', FALSE);
    if (CRM_Core_Permission::check('administer Reports') ||
      CRM_Core_Permission::check('access Report Criteria')
    ) {
      $this->assign('criteriaForm', TRUE);
    }

    $this->_instanceButtonName = $this->getButtonName('submit', 'save');
    $this->_printButtonName = $this->getButtonName('submit', 'print');
    $this->_pdfButtonName = $this->getButtonName('submit', 'pdf');
    $this->_csvButtonName = $this->getButtonName('submit', 'csv');
    $this->_groupButtonName = $this->getButtonName('submit', 'group');
    $this->_chartButtonName = $this->getButtonName('submit', 'chart');
  }

  function addBreadCrumb() {
    $breadCrumbs = [['title' => ts('Report Templates'),
        'url' => CRM_Utils_System::url('civicrm/admin/report/template/list', 'reset=1'),
      ]];

    CRM_Utils_System::appendBreadCrumb($breadCrumbs);
  }

  function preProcess() {
    $this->preProcessCommon();
    if (!$this->_id) {
      self::addBreadCrumb();
    }

    foreach ($this->_columns as $tableName => $table) {
      // set alias
      if (!isset($table['alias'])) {
        $this->_columns[$tableName]['alias'] = substr($tableName, 8) . '_civireport';
      }
      else {
        $this->_columns[$tableName]['alias'] = $table['alias'] . '_civireport';
      }

      $this->_aliases[$tableName] = $this->_columns[$tableName]['alias'];

      // higher preference to bao object
      if (CRM_Utils_Array::arrayKeyExists('bao', $table)) {
        $baoName = $table['bao'];
        $expFields = $baoName::exportableFields( );
      }
      else {
        $daoName = $table['dao'];
        $expFields = $daoName::fields( );
      }

      $doNotCopy = ['required'];

      $fieldGroups = ['fields', 'filters', 'group_bys', 'order_bys'];
      foreach ($fieldGroups as $fieldGrp) {
        if (CRM_Utils_Array::value($fieldGrp, $table) && is_array($table[$fieldGrp])) {
          foreach ($table[$fieldGrp] as $fieldName => $field) {
            if (CRM_Utils_Array::arrayKeyExists($fieldName, $expFields)) {
              foreach ($doNotCopy as $dnc) {
                // unset the values we don't want to be copied.
                unset($expFields[$fieldName][$dnc]);
              }
              if (empty($field)) {
                $this->_columns[$tableName][$fieldGrp][$fieldName] = $expFields[$fieldName];
              }
              else {
                foreach ($expFields[$fieldName] as $property => $val) {
                  if (!CRM_Utils_Array::arrayKeyExists($property, $field)) {
                    $this->_columns[$tableName][$fieldGrp][$fieldName][$property] = $val;
                  }
                }
              }

              // fill other vars
              if (CRM_Utils_Array::value('no_repeat', $field)) {
                $this->_noRepeats[] = "{$tableName}_{$fieldName}";
              }
              if (CRM_Utils_Array::value('no_display', $field)) {
                $this->_noDisplay[] = "{$tableName}_{$fieldName}";
              }
            }

            // set alias = table-name, unless already set
            $alias = $field['alias'] ?? ($this->_columns[$tableName]['alias'] ?? $tableName
            );
            $this->_columns[$tableName][$fieldGrp][$fieldName]['alias'] = $alias;

            // set name = fieldName, unless already set
            if (!isset($this->_columns[$tableName][$fieldGrp][$fieldName]['name'])) {
              $this->_columns[$tableName][$fieldGrp][$fieldName]['name'] = $fieldName;
            }

            // set dbAlias = alias.name, unless already set
            if (!isset($this->_columns[$tableName][$fieldGrp][$fieldName]['dbAlias'])) {
              $this->_columns[$tableName][$fieldGrp][$fieldName]['dbAlias'] = $alias . '.' . $this->_columns[$tableName][$fieldGrp][$fieldName]['name'];
            }

            if (CRM_Utils_Array::value('type', $this->_columns[$tableName][$fieldGrp][$fieldName]) &&
              !isset($this->_columns[$tableName][$fieldGrp][$fieldName]['operatorType'])
            ) {
              if (in_array($this->_columns[$tableName][$fieldGrp][$fieldName]['type'],
                  [CRM_Utils_Type::T_MONEY, CRM_Utils_Type::T_FLOAT]
                )) {
                $this->_columns[$tableName][$fieldGrp][$fieldName]['operatorType'] = CRM_Report_Form::OP_FLOAT;
              }
              elseif (in_array($this->_columns[$tableName][$fieldGrp][$fieldName]['type'],
                  [CRM_Utils_Type::T_INT]
                )) {
                $this->_columns[$tableName][$fieldGrp][$fieldName]['operatorType'] = CRM_Report_Form::OP_INT;
              }
            }
          }
        }
      }

      // copy filters to a separate handy variable
      if (CRM_Utils_Array::arrayKeyExists('filters', $table)) {
        $this->_filters[$tableName] = $this->_columns[$tableName]['filters'];
      }

      if (CRM_Utils_Array::arrayKeyExists('group_bys', $table)) {
        $groupBys[$tableName] = $this->_columns[$tableName]['group_bys'];
      }

      if (CRM_Utils_Array::arrayKeyExists('fields', $table)) {
        $reportFields[$tableName] = $this->_columns[$tableName]['fields'];
      }
    }

    if ($this->_force) {
      $this->setDefaultValues(FALSE);
    }


    CRM_Report_Utils_Get::processFilter($this->_filters,
      $this->_defaults
    );
    CRM_Report_Utils_Get::processGroupBy($groupBys,
      $this->_defaults
    );
    CRM_Report_Utils_Get::processFields($reportFields,
      $this->_defaults
    );
    CRM_Report_Utils_Get::processChart($this->_defaults);

    if ($this->_force) {
      $this->_formValues = $this->_defaults;
      $this->postProcess();
    }
  }

  function setDefaultValues($freeze = TRUE) {
    $freezeGroup = [];

    // FIXME: generalizing form field naming conventions would reduce
    // lots of lines below.
    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!CRM_Utils_Array::arrayKeyExists('no_display', $field)) {
            if (isset($field['required'])) {
              // set default
              $this->_defaults['fields'][$fieldName] = 1;

              if ($freeze) {
                // find element object, so that we could use quickform's freeze method
                // for required elements
                $obj = $this->getElementFromGroup("fields",
                  $fieldName
                );
                if ($obj) {
                  $freezeGroup[] = $obj;
                }
              }
            }
            elseif (isset($field['default'])) {
              $this->_defaults['fields'][$fieldName] = $field['default'];
            }
          }
        }
      }

      if (CRM_Utils_Array::arrayKeyExists('group_bys', $table)) {
        foreach ($table['group_bys'] as $fieldName => $field) {
          if (isset($field['default'])) {
            if (CRM_Utils_Array::value('frequency', $field)) {
              $this->_defaults['group_bys_freq'][$fieldName] = 'MONTH';
            }
            $this->_defaults['group_bys'][$fieldName] = $field['default'];
          }
        }
      }
      if (CRM_Utils_Array::arrayKeyExists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          if (isset($field['default'])) {
            if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
              $this->_defaults["{$fieldName}_relative"] = $field['default'];
            }
            else {
              $this->_defaults["{$fieldName}_value"] = $field['default'];
            }
          }
          //assign default value as "in" for multiselect
          //operator, To freeze the select element
          if (CRM_Utils_Array::value('operatorType', $field) == CRM_Report_FORM::OP_MULTISELECT) {
            $this->_defaults["{$fieldName}_op"] = 'in';
          }
          elseif (CRM_Utils_Array::value('operatorType', $field) == CRM_Report_FORM::OP_MULTISELECT_SEPARATOR) {
            $this->_defaults["{$fieldName}_op"] = 'mhas';
          }
          elseif ($op = CRM_Utils_Array::value('default_op', $field)) {
            $this->_defaults["{$fieldName}_op"] = $op;
          }
        }
      }

      foreach ($this->_options as $fieldName => $field) {
        if (isset($field['default'])) {
          $this->_defaults['options'][$fieldName] = $field['default'];
        }
      }
    }

    // lets finish freezing task here itself
    if (!empty($freezeGroup)) {
      foreach ($freezeGroup as $elem) {
        $elem->freeze();
      }
    }

    if ($this->_formValues) {
      $this->_defaults = array_merge($this->_defaults, $this->_formValues);
    }

    if ($this->_instanceValues) {
      $this->_defaults = array_merge($this->_defaults, $this->_instanceValues);
    }


    CRM_Report_Form_Instance::setDefaultValues($this, $this->_defaults);

    return $this->_defaults;
  }

  function getElementFromGroup($group, $grpFieldName) {
    $eleObj = $this->getElement($group);
    foreach ($eleObj->_elements as $index => $obj) {
      if ($grpFieldName == $obj->_attributes['name']) {
        return $obj;
      }
    }
    return FALSE;
  }

  function addColumns() {
    $options = [];
    $colGroups = NULL;
    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!CRM_Utils_Array::arrayKeyExists('no_display', $field)) {
            if (isset($table['grouping'])) {
              $tableName = $table['grouping'];
            }
            $colGroups[$tableName]['fields'][$fieldName] = $field['title'];

            if (isset($table['group_title'])) {
              $colGroups[$tableName]['group_title'] = $table['group_title'];
            }

            $options[$fieldName] = $field['title'];
          }
        }
      }
    }

    $this->addCheckBox("fields", ts('Select Columns'), $options, NULL,
      NULL, NULL, NULL, $this->_fourColumnAttribute, TRUE
    );
    $this->assign('colGroups', $colGroups);
  }

  function addFilters() {


    $options = $filters = [];
    $count = 1;
    foreach ($this->_filters as $table => $attributes) {
      foreach ($attributes as $fieldName => $field) {
        // get ready with option value pair
        $operations = $this->getOperationPair(CRM_Utils_Array::value('operatorType', $field),
          $fieldName
        );
        if (!empty($field['operationPair']) && is_array($field['operationPair'])) {
          $operations = array_intersect_key($field['operationPair'], $operations);
        }

        $filters[$table][$fieldName] = $field;

        switch (CRM_Utils_Array::value('operatorType', $field)) {
          case CRM_Report_FORM::OP_MULTISELECT:
          case CRM_Report_FORM::OP_MULTISELECT_SEPARATOR:
            // assume a multi-select field
            if (!empty($field['options'])) {
              $element = $this->addElement('select', "{$fieldName}_op", ts('Operator:'), $operations);
              if (count($operations) <= 1) {
                $element->freeze();
              }
              $select = $this->addElement('select', "{$fieldName}_value", NULL,
                $field['options'], ['size' => 4,
                  'style' => 'width:200px',
                ]
              );
              $select->setMultiple(TRUE);
            }
            break;

          case CRM_Report_FORM::OP_SELECT:
            // assume a select field
            $this->addElement('select', "{$fieldName}_op", ts('Operator:'), $operations);
            $this->addElement('select', "{$fieldName}_value", NULL, $field['options']);
            break;

          case CRM_Report_FORM::OP_DATE:
            // build datetime fields
            CRM_Core_Form_Date::buildDateRange($this, $fieldName, $count);
            $count++;
            break;

          case CRM_Report_FORM::OP_INT:
          case CRM_Report_FORM::OP_FLOAT:
            // and a min value input box
            $this->add('text', "{$fieldName}_min", ts('Min'));
            // and a max value input box
            $this->add('text', "{$fieldName}_max", ts('Max'));
          default:
            // default type is string
            $this->addElement('select', "{$fieldName}_op", ts('Operator:'), $operations,
              ['onchange' => "return showHideMaxMinVal( '$fieldName', this.value );"]
            );
            // we need text box for value input
            $this->add('text', "{$fieldName}_value", NULL);
            break;
        }
      }
    }
    $this->assign('filters', $filters);
  }

  function addOptions() {
    if (!empty($this->_options)) {
      // FIXME: For now lets build all elements as checkboxes.
      // Once we clear with the format we can build elements based on type

      $options = [];
      foreach ($this->_options as $fieldName => $field) {
        if ($field['type'] == 'select') {
          $this->addElement('select', "{$fieldName}", $field['title'], $field['options']);
        }
        else {
          $options[$field['title']] = $fieldName;
        }
      }

      $this->addCheckBox("options", $field['title'],
        $options, NULL,
        NULL, NULL, NULL, $this->_fourColumnAttribute
      );
    }
  }

  function addChartOptions() {
    if (!empty($this->_charts)) {
      $this->addElement('select', "charts", ts('Chart'), $this->_charts);
      $this->assign('charts', $this->_charts);
      $this->addElement('submit', $this->_chartButtonName, ts('View'));
    }
  }

  function addGroupBys() {
    $options = $freqElements = [];

    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('group_bys', $table)) {
        foreach ($table['group_bys'] as $fieldName => $field) {
          if (!empty($field)) {
            $options[$field['title']] = $fieldName;
            if (CRM_Utils_Array::value('frequency', $field)) {
              $freqElements[$field['title']] = $fieldName;
            }
          }
        }
      }
    }
    $this->addCheckBox("group_bys", ts('Group by columns'), $options, NULL,
      NULL, NULL, NULL, $this->_fourColumnAttribute
    );
    $this->assign('groupByElements', $options);

    foreach ($freqElements as $name) {
      $this->addElement('select', "group_bys_freq[$name]",
        ts('Frequency'), $this->_groupByDateFreq
      );
    }
  }

  function buildInstanceAndButtons() {

    CRM_Report_Form_Instance::buildForm($this);

    $label = $this->_id ? ts('Update Report') : ts('Create Report');

    $this->addElement('submit', $this->_instanceButtonName, $label);
    $this->addElement('submit', $this->_printButtonName, ts('Print Report'));
    $this->addElement('submit', $this->_pdfButtonName, ts('PDF'));
    if ($this->_instanceForm) {
      $this->assign('instanceForm', TRUE);
    }

    $label = $this->_id ? ts('Print Report') : ts('Print Preview');
    $this->addElement('submit', $this->_printButtonName, $label);

    $label = $this->_id ? ts('PDF') : ts('Preview PDF');
    $this->addElement('submit', $this->_pdfButtonName, $label);

    $label = $this->_id ? ts('Export to CSV') : ts('Preview CSV');

    if ($this->_csvSupported) {
      $this->addElement('submit', $this->_csvButtonName, $label);
    }

    if (CRM_Core_Permission::check('administer Reports') && $this->_add2groupSupported) {
      $this->addElement('select', 'groups', ts('Group'),
        ['' => ts('- select group -')] + CRM_Core_PseudoConstant::staticGroup()
      );
      $this->assign('group', TRUE);
    }

    //$this->addElement('select', 'select_add_to_group_id', ts('Group'), $groupList);
    $label = ts('Add these Contacts to Group');
    $this->addElement('submit', $this->_groupButtonName, $label, ['onclick' => 'return checkGroup();']);

    $this->addChartOptions();
    $this->addButtons([
        ['type' => 'submit',
          'name' => ts('Preview Report'),
          'isDefault' => TRUE,
        ],
      ]
    );
  }

  function buildQuickForm() {
    $this->addColumns();

    $this->addFilters();

    $this->addOptions();

    $this->addGroupBys();

    $this->buildInstanceAndButtons();

    //add form rule for report
    if (is_callable([$this, 'formRule'])) {
      $this->addFormRule([get_class($this), 'formRule'], $this);
    }
  }

  // a formrule function to ensure that fields selected in group_by
  // (if any) should only be the ones present in display/select fields criteria;
  // note: works if and only if any custom field selected in group_by.
  function customDataFormRule($fields, $ignoreFields = []) {
    $errors = [];
    if (!empty($this->_customGroupExtends) && $this->_customGroupGroupBy && !empty($fields['group_bys'])) {
      foreach ($this->_columns as $tableName => $table) {
        if ((substr($tableName, 0, 13) == 'civicrm_value' || substr($tableName, 0, 12) == 'custom_value') && !empty($this->_columns[$tableName]['fields'])) {
          foreach ($this->_columns[$tableName]['fields'] as $fieldName => $field) {
            if (CRM_Utils_Array::arrayKeyExists($fieldName, $fields['group_bys']) &&
              !CRM_Utils_Array::arrayKeyExists($fieldName, $fields['fields'])
            ) {
              $errors['fields'] = "Please make sure fields selected in 'Group by Columns' section are also selected in 'Display Columns' section.";
            }
            elseif (CRM_Utils_Array::arrayKeyExists($fieldName, $fields['group_bys'])) {
              foreach ($fields['fields'] as $fld => $val) {
                if (!CRM_Utils_Array::arrayKeyExists($fld, $fields['group_bys']) && !in_array($fld, $ignoreFields)) {
                  $errors['fields'] = "Please ensure that fields selected in 'Display Columns' are also selected in 'Group by Columns' section.";
                }
              }
            }
          }
        }
      }
    }
    return $errors;
  }

  // Note: $fieldName param allows inheriting class to build operationPairs
  // specific to a field.
  static function getOperationPair($type = "string", $fieldName = NULL) {
    // FIXME: At some point we should move these key-val pairs
    // to option_group and option_value table.

    switch ($type) {
      case CRM_Report_FORM::OP_INT:
      case CRM_Report_FORM::OP_FLOAT:
        return ['lte' => ts('Is less than or equal to'),
          'gte' => ts('Is greater than or equal to'),
          'bw' => ts('Is between'),
          'eq' => ts('Is equal to'),
          'lt' => ts('Is less than'),
          'gt' => ts('Is greater than'),
          'neq' => ts('Is not equal to'),
          'nbw' => ts('Is not between'),
          'nll' => ts('Is empty (Null)'),
          'nnll' => ts('Is not empty (Null)'),
        ];
      break;

      case CRM_Report_FORM::OP_SELECT:
        return ['eq' => ts('Is equal to')];

      case CRM_Report_FORM::OP_MULTISELECT:
        return ['in' => ts('Is one of')];

      case CRM_Report_FORM::OP_DATE:
        return ['nll' => ts('Is empty (Null)'),
          'nnll' => ts('Is not empty (Null)'),
        ];
      break;

      case CRM_Report_FORM::OP_MULTISELECT_SEPARATOR:
        // use this operator for the values, concatenated with separator. For e.g if
        // multiple options for a column is stored as ^A{val1}^A{val2}^A
        return ['mhas' => ts('Is one of')];

      default:
        // type is string
        return ['has' => ts('Contains'),
          'sw' => ts('Starts with'),
          'ew' => ts('Ends with'),
          'nhas' => ts('Does not contain'),
          'eq' => ts('Is equal to'),
          'neq' => ts('Is not equal to'),
          'nll' => ts('Is empty (Null)'),
          'nnll' => ts('Is not empty (Null)'),
        ];
    }
  }

  function buildTagFilter() {

    $contactTags = CRM_Core_BAO_Tag::getTags();
    if (!empty($contactTags)) {
      $this->_columns['civicrm_tag'] = ['dao' => 'CRM_Core_DAO_Tag',
        'filters' =>
        ['tagid' =>
          ['name' => 'tag_id',
            'title' => ts('Tag'),
            'tag' => TRUE,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $contactTags,
          ],
        ],
      ];
    }
  }

  static function getSQLOperator($operator = "like") {
    switch ($operator) {
      case 'eq':
        return '=';

      case 'lt':
        return '<';

      case 'lte':
        return '<=';

      case 'gt':
        return '>';

      case 'gte':
        return '>=';

      case 'ne':
      case 'neq':
        return '!=';

      case 'nhas':
        return 'NOT LIKE';

      case 'in':
        return 'IN';

      case 'nll':
        return 'IS NULL';

      case 'nnll':
        return 'IS NOT NULL';

      default:
        // type is string
        return 'LIKE';
    }
  }

  function whereClause(&$field, $op,
    $value, $min, $max
  ) {

    $type = CRM_Utils_Type::typeToString(CRM_Utils_Array::value('type', $field));
    $clause = NULL;

    switch ($op) {
      case 'bw':
      case 'nbw':
        if (($min !== NULL && strlen($min) > 0) ||
          ($max !== NULL && strlen($max) > 0)
        ) {
          $min = CRM_Utils_Type::escape($min, $type);
          $max = CRM_Utils_Type::escape($max, $type);
          $clauses = [];
          if ($min) {
            if ($op == 'bw') {
              $clauses[] = "( {$field['dbAlias']} >= $min )";
            }
            else {
              $clauses[] = "( {$field['dbAlias']} < $min )";
            }
          }
          if ($max) {
            if ($op == 'bw') {
              $clauses[] = "( {$field['dbAlias']} <= $max )";
            }
            else {
              $clauses[] = "( {$field['dbAlias']} > $max )";
            }
          }

          if (!empty($clauses)) {
            if ($op == 'bw') {
              $clause = CRM_Utils_Array::implode(' AND ', $clauses);
            }
            else {
              $clause = CRM_Utils_Array::implode(' OR ', $clauses);
            }
          }
        }
        break;

      case 'has':
      case 'nhas':
        if ($value !== NULL && strlen($value) > 0) {
          $value = CRM_Utils_Type::escape($value, $type);
          if (strpos($value, '%') === FALSE) {
            $value = "'%{$value}%'";
          }
          else {
            $value = "'{$value}'";
          }
          $sqlOP = self::getSQLOperator($op);
          $clause = "(IFNULL({$field['dbAlias']}, '') $sqlOP $value )";
        }
        break;

      case 'in':
        if ($value !== NULL && is_array($value) && count($value) > 0) {
          $sqlOP = self::getSQLOperator($op);
          if (CRM_Utils_Array::value('type', $field) == CRM_Utils_Type::T_STRING) {
            $clause = "( {$field['dbAlias']} $sqlOP ( '" . CRM_Utils_Array::implode("' , '", $value) . "') )";
          }
          else {
            // for numerical values
            $clause = "( {$field['dbAlias']} $sqlOP (" . CRM_Utils_Array::implode(', ', $value) . ") )";
          }
        }
        break;

      case 'mhas':
        // mhas == multiple has
        if ($value !== NULL && count($value) > 0) {
          $sqlOP = self::getSQLOperator($op);
          $clause = "{$field['dbAlias']} REGEXP '[[:<:]]" . CRM_Utils_Array::implode('|', $value) . "[[:>:]]'";
        }
        break;

      case 'sw':
      case 'ew':
        if ($value !== NULL && strlen($value) > 0) {
          $value = CRM_Utils_Type::escape($value, $type);
          if (strpos($value, '%') === FALSE) {
            if ($op == 'sw') {
              $value = "'{$value}%'";
            }
            else {
              $value = "'%{$value}'";
            }
          }
          else {
            $value = "'{$value}'";
          }
          $sqlOP = self::getSQLOperator($op);
          $clause = "( {$field['dbAlias']} $sqlOP $value )";
        }
        break;

      case 'nll':
      case 'nnll':
        $sqlOP = self::getSQLOperator($op);
        $clause = "( NULLIF(TRIM({$field['dbAlias']}), '') $sqlOP )";
        break;

      default:
        if ($value !== NULL && strlen($value) > 0) {
          if (isset($field['clause'])) {
            // FIXME: we not doing escape here. Better solution is to use two
            // different types - data-type and filter-type
            $clause = $field['clause'];
          }
          else {
            $value = CRM_Utils_Type::escape($value, $type);
            $sqlOP = self::getSQLOperator($op);
            if ($field['type'] == CRM_Utils_Type::T_STRING) {
              $value = "'{$value}'";
            }
            $clause = "( {$field['dbAlias']} $sqlOP $value )";
          }
        }
        break;
    }

    if (CRM_Utils_Array::value('group', $field) && $clause) {
      $clause = $this->whereGroupClause($clause);
    }
    elseif (CRM_Utils_Array::value('tag', $field) && $clause) {
      // not using left join in query because if any contact
      // belongs to more than one tag, results duplicate
      // entries.
      $clause = $this->whereTagClause($clause);
    }

    return $clause;
  }

  function dateClause($fieldName,
    $relative, $from, $to, $type = NULL
  ) {
    $clauses = [];
    if (in_array($relative, array_keys($this->getOperationPair(CRM_Report_FORM::OP_DATE)))) {
      $sqlOP = self::getSQLOperator($relative);
      return "( {$fieldName} {$sqlOP} )";
    }

    list($from, $to) = self::getFromTo($relative, $from, $to);

    if ($from) {
      $from = ($type == CRM_Utils_Type::T_DATE) ? substr($from, 0, 8) : $from;
      $clauses[] = "( {$fieldName} >= $from )";
    }

    if ($to) {
      $to = ($type == CRM_Utils_Type::T_DATE) ? substr($to, 0, 8) : $to;
      $clauses[] = "( {$fieldName} <= {$to} )";
    }

    if (!empty($clauses)) {
      return CRM_Utils_Array::implode(' AND ', $clauses);
    }

    return NULL;
  }

  static function dateDisplay($relative, $from, $to) {
    list($from, $to) = self::getFromTo($relative, $from, $to);

    if ($from) {
      $clauses[] = CRM_Utils_Date::customFormat($from, NULL, ['m', 'M']);
    }
    else {
      $clauses[] = 'Past';
    }

    if ($to) {
      $clauses[] = CRM_Utils_Date::customFormat($to, NULL, ['m', 'M']);
    }
    else {
      $clauses[] = 'Today';
    }

    if (!empty($clauses)) {
      return CRM_Utils_Array::implode(' - ', $clauses);
    }

    return NULL;
  }

  static function getFromTo($relative, $from, $to) {

    //FIX ME not working for relative
    if ($relative) {
      list($term, $unit) = explode('.', $relative);
      $dateRange = CRM_Utils_Date::relativeToAbsolute($term, $unit);
      $from = $dateRange['from'];
      //Take only Date Part, Sometime Time part is also present in 'to'
      $to = substr($dateRange['to'], 0, 8);
    }

    $from = CRM_Utils_Date::processDate($from);
    $to = CRM_Utils_Date::processDate($to, '235959');

    return [$from, $to];
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
  }

  function alterCustomDataDisplay(&$rows) {
    // custom code to alter rows having custom values
    if (empty($this->_customGroupExtends)) {
      return;
    }

    $customFieldIds = [];

    foreach ($this->_params['fields'] as $fieldAlias => $value) {
      if ($fieldId = CRM_Core_BAO_CustomField::getKeyID($fieldAlias)) {
        $customFieldIds[$fieldAlias] = $fieldId;
      }
    }
    if (empty($customFieldIds)) {
      return;
    }

    $customFields = $fieldValueMap = [];
    $customFieldCols = ['column_name', 'data_type', 'html_type', 'option_group_id', 'id'];

    // skip for type date and ContactReference since date format is already handled
    $query = " 
SELECT cg.table_name, cf." . CRM_Utils_Array::implode(", cf.", $customFieldCols) . ", ov.value, ov.label
FROM  civicrm_custom_field cf      
INNER JOIN civicrm_custom_group cg ON cg.id = cf.custom_group_id        
LEFT JOIN civicrm_option_value ov ON cf.option_group_id = ov.option_group_id
WHERE cg.extends IN ('" . CRM_Utils_Array::implode("','", $this->_customGroupExtends) . "') AND 
      cg.is_active = 1 AND 
      cf.is_active = 1 AND
      cf.is_searchable = 1 AND
      cf.data_type   NOT IN ('ContactReference', 'Date') AND
      cf.id IN (" . CRM_Utils_Array::implode(",", $customFieldIds) . ")";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      foreach ($customFieldCols as $key) {
        $customFields[$dao->table_name . '_custom_' . $dao->id][$key] = $dao->$key;
      }
      if ($dao->option_group_id) {
        $fieldValueMap[$dao->option_group_id][$dao->value] = $dao->label;
      }
    }
    $dao->free();

    $entryFound = FALSE;
    foreach ($rows as $rowNum => $row) {
      foreach ($row as $tableCol => $val) {
        if (CRM_Utils_Array::arrayKeyExists($tableCol, $customFields)) {
          $rows[$rowNum][$tableCol] = $this->formatCustomValues($val, $customFields[$tableCol], $fieldValueMap);
          $entryFound = TRUE;
        }
      }

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

  function formatCustomValues($value, $customField, $fieldValueMap) {
    if (CRM_Utils_System::isNull($value)) {
      return;
    }

    $htmlType = $customField['html_type'];

    switch ($customField['data_type']) {
      case 'Boolean':
        if ($value == '1') {
          $retValue = ts('Yes');
        }
        else {
          $retValue = ts('No');
        }
        break;

      case 'Link':
        $retValue = CRM_Utils_System::formatWikiURL($value);
        break;

      case 'File':
        $retValue = $value;
        break;

      case 'Memo':
        $retValue = $value;
        break;

      case 'Float':
        if ($htmlType == 'Text') {
          $retValue = (float)$value;
          break;
        }
      case 'Money':
        if ($htmlType == 'Text') {

          $retValue = CRM_Utils_Money::format($value, NULL, '%a');
          break;
        }
      case 'String':
      case 'Int':
        if (in_array($htmlType, ['Text', 'TextArea'])) {
          $retValue = $value;
          break;
        }
      case 'StateProvince':
      case 'Country':

        switch ($htmlType) {
          case 'Multi-Select Country':
            $value = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
            $customData = [];
            foreach ($value as $val) {
                if ($val) {
                    $customData[] = CRM_Core_PseudoConstant::country($val, FALSE);
                  }
                }
                $retValue = CRM_Utils_Array::implode(', ', $customData);
                break;

              case 'Select Country':
                $retValue = CRM_Core_PseudoConstant::country($value, FALSE);
                break;

              case 'Select State/Province':
                $retValue = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
                break;

              case 'Multi-Select State/Province':
                $value = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
                $customData = [];
                foreach ($value as $val) {
                    if ($val) {
                        $customData[] = CRM_Core_PseudoConstant::stateProvince($val, FALSE);
                      }
                    }
                    $retValue = CRM_Utils_Array::implode(', ', $customData);
                    break;

                  case 'Select':
                  case 'Radio':
                  case 'Autocomplete-Select':
                    $retValue = $fieldValueMap[$customField['option_group_id']][$value];
                    break;

                  case 'CheckBox':
                  case 'AdvMulti-Select':
                  case 'Multi-Select':
                    $value = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
                    $customData = [];
                    foreach ($value as $val) {
                        if ($val) {
                            $customData[] = $fieldValueMap[$customField['option_group_id']][$val];
                          }
                        }
                        $retValue = CRM_Utils_Array::implode(', ', $customData);
                        break;

                      default:
                        $retValue = $value;
                    }
                    break;

                  default:
                    $retValue = $value;
                }

                return $retValue;
              }

              function removeDuplicates(&$rows) {
                if (empty($this->_noRepeats)) {
                  return;
                }
                $checkList = [];

                foreach ($rows as $key => $list) {
                  foreach ($list as $colName => $colVal) {
                    if (is_array($checkList[$colName]) &&
                      in_array($colVal, $checkList[$colName])
                    ) {
                      $rows[$key][$colName] = "";
                    }
                    if (in_array($colName, $this->_noRepeats)) {
                      $checkList[$colName][] = $colVal;
                    }
                  }
                }
              }

              function fixSubTotalDisplay(&$row, $fields, $subtotal = TRUE) {

                foreach ($row as $colName => $colVal) {
                  if (in_array($colName, $fields)) {
                    $row[$colName] = $row[$colName];
                  }
                  elseif (isset($this->_columnHeaders[$colName])) {
                    if ($subtotal) {
                      $row[$colName] = "Subtotal";
                      $subtotal = FALSE;
                    }
                    else {
                      unset($row[$colName]);
                    }
                  }
                }
              }

              function grandTotal(&$rows) {
                if (!$this->_rollup || ($this->_rollup == '') ||
                  ($this->_limit && count($rows) >= self::ROW_COUNT_LIMIT)
                ) {
                  return FALSE;
                }
                $lastRow = array_pop($rows);

                $this->_grandFlag = FALSE;
                foreach ($this->_columnHeaders as $fld => $val) {
                  if (!in_array($fld, $this->_statFields)) {
                    if (!$this->_grandFlag) {
                      $lastRow[$fld] = "Grand Total";
                      $this->_grandFlag = TRUE;
                    }
                    else {
                      $lastRow[$fld] = "";
                    }
                  }
                }

                $this->assign('grandStat', $lastRow);
                return TRUE;
              }

              function formatDisplay(&$rows, $pager = TRUE) {
                // set pager based on if any limit was applied in the query.
                if ($pager) {
                  $this->setPager();
                }

                // allow building charts if any
                if (!empty($this->_params['charts']) && !empty($rows)) {
                  $this->buildChart($rows);
                  $this->assign('chartEnabled', TRUE);
                }

                // unset columns not to be displayed.
                foreach ($this->_columnHeaders as $key => $value) {
                  if (is_array($value) && isset($value['no_display'])) {
                    unset($this->_columnHeaders[$key]);
                  }
                }

                // unset columns not to be displayed.
                if (!empty($rows)) {
                  foreach ($this->_noDisplay as $noDisplayField) {
                    foreach ($rows as $rowNum => $row) {
                      unset($this->_columnHeaders[$noDisplayField]);
                    }
                  }
                }

                // process grand-total row
                $this->grandTotal($rows);

                // use this method for formatting rows for display purpose.
                $this->alterDisplay($rows);

                // use this method for formatting custom rows for display purpose.
                $this->alterCustomDataDisplay($rows);
              }

              function buildChart(&$rows) {
                // override this method for building charts.
              }

              // select() method below has been added recently (v3.3), and many of the report templates might
              // still be having their own select() method. We should fix them as and when encountered and move
              // towards generalizing the select() method below.
              function select() {
                $select = [];

                $this->_columnHeaders = [];
                foreach ($this->_columns as $tableName => $table) {
                  if (CRM_Utils_Array::arrayKeyExists('fields', $table)) {
                    foreach ($table['fields'] as $fieldName => $field) {
                      if (CRM_Utils_Array::value('required', $field) ||
                        CRM_Utils_Array::value($fieldName, $this->_params['fields'])
                      ) {

                        // 1. In many cases we want select clause to be built in slightly different way
                        //    for a particular field of a particular type.
                        // 2. This method when used should receive params by reference and modify $this->_columnHeaders
                        //    as needed.
                        $selectClause = $this->selectClause($tableName, 'fields', $fieldName, $field);
                        if ($selectClause) {
                          $select[] = $selectClause;
                          continue;
                        }

                        // include statistics columns only if set
                        if (CRM_Utils_Array::value('statistics', $field)) {
                          foreach ($field['statistics'] as $stat => $label) {
                            switch (strtolower($stat)) {
                              case 'sum':
                                $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                                $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                                $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = $field['type'];
                                $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                                break;

                              case 'count':
                                $select[] = "COUNT({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                                $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                                $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_INT;
                                $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                                break;

                              case 'avg':
                                $select[] = "ROUND(AVG({$field['dbAlias']}),2) as {$tableName}_{$fieldName}_{$stat}";
                                $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                                $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = $field['type'];
                                $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                                break;
                            }
                          }
                        }
                        else {
                          $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
                          $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
                          $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
                        }
                      }
                    }
                  }

                  // select for group bys
                  if (CRM_Utils_Array::arrayKeyExists('group_bys', $table)) {
                    foreach ($table['group_bys'] as $fieldName => $field) {

                      // 1. In many cases we want select clause to be built in slightly different way
                      //    for a particular field of a particular type.
                      // 2. This method when used should receive params by reference and modify $this->_columnHeaders
                      //    as needed.
                      $selectClause = $this->selectClause($tableName, 'group_bys', $fieldName, $field);
                      if ($selectClause) {
                        $select[] = $selectClause;
                        continue;
                      }

                      if (CRM_Utils_Array::value($fieldName, $this->_params['group_bys'])) {
                        switch (CRM_Utils_Array::value($fieldName, $this->_params['group_bys_freq'])) {
                          case 'YEARWEEK':
                            $select[] = "DATE_SUB({$field['dbAlias']}, INTERVAL WEEKDAY({$field['dbAlias']}) DAY) AS {$tableName}_{$fieldName}_start";
                            $select[] = "YEARWEEK({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                            $select[] = "WEEKOFYEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                            $field['title'] = 'Week';
                            break;

                          case 'YEAR':
                            $select[] = "MAKEDATE(YEAR({$field['dbAlias']}), 1)  AS {$tableName}_{$fieldName}_start";
                            $select[] = "YEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                            $select[] = "YEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                            $field['title'] = 'Year';
                            break;

                          case 'MONTH':
                            $select[] = "DATE_SUB({$field['dbAlias']}, INTERVAL (DAYOFMONTH({$field['dbAlias']})-1) DAY) as {$tableName}_{$fieldName}_start";
                            $select[] = "MONTH({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                            $select[] = "MONTHNAME({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                            $field['title'] = 'Month';
                            break;

                          case 'QUARTER':
                            $select[] = "STR_TO_DATE(CONCAT( 3 * QUARTER( {$field['dbAlias']} ) -2 , '/', '1', '/', YEAR( {$field['dbAlias']} ) ), '%m/%d/%Y') AS {$tableName}_{$fieldName}_start";
                            $select[] = "QUARTER({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                            $select[] = "QUARTER({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                            $field['title'] = 'Quarter';
                            break;
                        }
                        // for graphs and charts -
                        if (CRM_Utils_Array::value($fieldName, $this->_params['group_bys_freq'])) {
                          $this->_interval = $field['title'];
                          $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['title'] = $field['title'] . ' Beginning';
                          $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['type'] = $field['type'];
                          $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['group_by'] = $this->_params['group_bys_freq'][$fieldName];

                          // just to make sure these values are transfered to rows.
                          // since we 'll need them for calculation purpose,
                          // e.g making subtotals look nicer or graphs
                          $this->_columnHeaders["{$tableName}_{$fieldName}_interval"] = ['no_display' => TRUE];
                          $this->_columnHeaders["{$tableName}_{$fieldName}_subtotal"] = ['no_display' => TRUE];
                        }
                      }
                    }
                  }
                }

                $this->_select = "SELECT " . CRM_Utils_Array::implode(', ', $select) . " ";
              }

              function selectClause(&$tableName, $tableKey, &$fieldName, &$field) {
                return FALSE;
              }

              function where() {
                $whereClauses = $havingClauses = [];
                foreach ($this->_columns as $tableName => $table) {
                  if (CRM_Utils_Array::arrayKeyExists('filters', $table)) {
                    foreach ($table['filters'] as $fieldName => $field) {
                      $clause = NULL;
                      if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
                        $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
                        $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
                        $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

                        $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
                      }
                      else {
                        $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
                        if ($op) {
                          $clause = $this->whereClause($field,
                            $op,
                            CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                            CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                            CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
                          );
                        }
                      }

                      if (!empty($clause)) {
                        if (CRM_Utils_Array::value('having', $field)) {
                          $havingClauses[] = $clause;
                        }
                        else {
                          $whereClauses[] = $clause;
                        }
                      }
                    }
                  }
                }

                if (empty($whereClauses)) {
                  $this->_where = "WHERE ( 1 ) ";
                  $this->_having = "";
                }
                else {
                  $this->_where = "WHERE " . CRM_Utils_Array::implode(' AND ', $whereClauses);
                }

                if ($this->_aclWhere) {
                  $this->_where .= " AND {$this->_aclWhere} ";
                }

                if (!empty($havingClauses)) {
                  // use this clause to construct group by clause.
                  $this->_having = "HAVING " . CRM_Utils_Array::implode(' AND ', $havingClauses);
                }
              }

              function processReportMode() {
                $buttonName = $this->controller->getButtonName();

                $output = CRM_Utils_Request::retrieve('output',
                  'String', CRM_Core_DAO::$_nullObject
                );
                $this->_sendmail = CRM_Utils_Request::retrieve('sendmail',
                  'Boolean', CRM_Core_DAO::$_nullObject
                );
                $this->_absoluteUrl = FALSE;
                $printOnly = FALSE;
                $this->assign('printOnly', FALSE);

                if ($this->_printButtonName == $buttonName || $output == 'print' || $this->_sendmail) {
                  $this->assign('printOnly', TRUE);
                  $printOnly = TRUE;
                  $this->assign('outputMode', 'print');
                  $this->_outputMode = 'print';
                }
                elseif ($this->_pdfButtonName == $buttonName || $output == 'pdf') {
                  $this->assign('printOnly', TRUE);
                  $printOnly = TRUE;
                  $this->assign('outputMode', 'pdf');
                  $this->_outputMode = 'pdf';
                  $this->_absoluteUrl = TRUE;
                }
                elseif ($this->_csvButtonName == $buttonName || $output == 'csv') {
                  $this->assign('printOnly', TRUE);
                  $printOnly = TRUE;
                  $this->assign('outputMode', 'csv');
                  $this->_outputMode = 'csv';
                  $this->_absoluteUrl = TRUE;
                }
                elseif ($this->_groupButtonName == $buttonName || $output == 'group') {
                  $this->assign('outputMode', 'group');
                  $this->_outputMode = 'group';
                }
                else {
                  $this->assign('outputMode', 'html');
                  $this->_outputMode = 'html';
                }

                // Get today's date to include in printed reports
                if ($printOnly) {

                  $reportDate = CRM_Utils_Date::customFormat(date('Y-m-d H:i'));
                  $this->assign('reportDate', $reportDate);
                }
              }

              function beginPostProcess() {
                $this->_params = $this->controller->exportValues($this->_name);
                if (empty($this->_params) &&
                  $this->_force
                ) {
                  $this->_params = $this->_formValues;
                }
                $this->_formValues = $this->_params;
                if (CRM_Core_Permission::check('administer Reports') &&
                  isset($this->_id) &&
                  ($this->_instanceButtonName == $this->controller->getButtonName() . '_save' ||
                    $this->_chartButtonName == $this->controller->getButtonName()
                  )
                ) {
                  $this->assign('updateReportButton', TRUE);
                }
                $this->processReportMode();
              }

              function buildQuery($applyLimit = TRUE) {
                $this->select();
                $this->from();
                $this->customDataFrom();
                $this->where();
                $this->groupBy();
                $this->orderBy();

                if ($applyLimit && !CRM_Utils_Array::value('charts', $this->_params)) {
                  $this->limit();
                }
                $sql = "{$this->_select}
                {$this->_from}
                {$this->_where}
                {$this->_groupBy}
                {$this->_having}
                {$this->_orderBy}
                {$this->_limit}";

                return $sql;
              }

              function groupBy() {
                $this->_groupBy = "";
              }

              function orderBy() {
                $this->_orderBy = "";
              }

              function buildRows($sql, &$rows) {
                $dao = CRM_Core_DAO::executeQuery($sql);
                if (!is_array($rows)) {
                  $rows = [];
                }

                // use this method to modify $this->_columnHeaders
                $this->modifyColumnHeaders();

                while ($dao->fetch()) {
                  $row = [];
                  foreach ($this->_columnHeaders as $key => $value) {
                    if (property_exists($dao, $key)) {
                      $row[$key] = $dao->$key;
                    }
                  }
                  $rows[] = $row;
                }
              }

              function modifyColumnHeaders() {
                // use this method to modify $this->_columnHeaders
              }

              function doTemplateAssignment(&$rows) {
                $this->assign_by_ref('columnHeaders', $this->_columnHeaders);
                $this->assign_by_ref('rows', $rows);
                $this->assign('statistics', $this->statistics($rows));
              }

              // override this method to build your own statistics
              function statistics(&$rows) {
                $statistics = [];

                $count = is_array($rows) ? count($rows) : 0;

                if ($this->_rollup && ($this->_rollup != '') && $this->_grandFlag) {
                  $count++;
                }

                $this->countStat($statistics, $count);

                $this->groupByStat($statistics);

                $this->filterStat($statistics);

                return $statistics;
              }

              function countStat(&$statistics, $count) {
                $statistics['counts']['rowCount'] = ['title' => ts('Row(s) Listed'),
                  'value' => $count,
                ];

                if ($this->_rowsFound && ($this->_rowsFound > $count)) {
                  $statistics['counts']['rowsFound'] = ['title' => ts('Total Row(s)'),
                    'value' => $this->_rowsFound,
                  ];
                }
              }

              function groupByStat(&$statistics) {
                if (CRM_Utils_Array::value('group_bys', $this->_params) &&
                  is_array($this->_params['group_bys']) &&
                  !empty($this->_params['group_bys'])
                ) {
                  foreach ($this->_columns as $tableName => $table) {
                    if (CRM_Utils_Array::arrayKeyExists('group_bys', $table)) {
                      foreach ($table['group_bys'] as $fieldName => $field) {
                        if (CRM_Utils_Array::value($fieldName, $this->_params['group_bys'])) {
                          $combinations[] = $field['title'];
                        }
                      }
                    }
                  }
                  $statistics['groups'][] = ['title' => ts('Grouping(s)'),
                    'value' => CRM_Utils_Array::implode(' & ', $combinations),
                  ];
                }
              }

              function filterStat(&$statistics) {
                foreach ($this->_columns as $tableName => $table) {
                  if (CRM_Utils_Array::arrayKeyExists('filters', $table)) {
                    foreach ($table['filters'] as $fieldName => $field) {
                      if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
                        list($from, $to) = $this->getFromTo(CRM_Utils_Array::value("{$fieldName}_relative", $this->_params),
                          CRM_Utils_Array::value("{$fieldName}_from", $this->_params),
                          CRM_Utils_Array::value("{$fieldName}_to", $this->_params)
                        );
                        $from = CRM_Utils_Date::customFormat($from, NULL, ['d']);
                        $to = CRM_Utils_Date::customFormat($to, NULL, ['d']);

                        if ($from || $to) {
                          $statistics['filters'][] = ['title' => $field['title'],
                            'value' => ts("Between %1 and %2", [1 => $from, 2 => $to]),
                          ];
                        }
                        elseif (in_array($rel = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params),
                            array_keys($this->getOperationPair(CRM_Report_FORM::OP_DATE))
                          )) {
                          $pair = $this->getOperationPair(CRM_Report_FORM::OP_DATE);
                          $statistics['filters'][] = ['title' => $field['title'],
                            'value' => $pair[$rel],
                          ];
                        }
                      }
                      else {
                        $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
                        $value = NULL;
                        if ($op) {
                          $pair = $this->getOperationPair(CRM_Utils_Array::value('operatorType', $field),
                            $fieldName
                          );
                          $min = CRM_Utils_Array::value("{$fieldName}_min", $this->_params);
                          $max = CRM_Utils_Array::value("{$fieldName}_max", $this->_params);
                          $val = CRM_Utils_Array::value("{$fieldName}_value", $this->_params);
                          if (in_array($op, ['bw', 'nbw']) && ($min || $max)) {
                            $value = "{$pair[$op]} " . $min . ' and ' . $max;
                          }
                          elseif ($op == 'nll' || $op == 'nnll') {
                            $value = $pair[$op];
                          }
                          elseif (is_array($val) && (!empty($val))) {
                            $options = $field['options'];
                            foreach ($val as $key => $valIds) {
                              if (isset($options[$valIds])) {
                                $val[$key] = $options[$valIds];
                              }
                            }
                            $pair[$op] = (count($val) == 1) ? ts('Is') : $pair[$op];
                            $val = CRM_Utils_Array::implode(', ', $val);
                            $value = "{$pair[$op]} " . $val;
                          }
                          elseif ($val) {
                            $value = "{$pair[$op]} " . $val;
                          }
                        }
                        if ($value) {
                          $statistics['filters'][] = ['title' => CRM_Utils_Array::value('title', $field),
                            'value' => $value,
                          ];
                        }
                      }
                    }
                  }
                }
              }

              function endPostProcess(&$rows = NULL) {
                if ($this->_outputMode == 'print' ||
                  $this->_outputMode == 'pdf' ||
                  $this->_sendmail
                ) {
                  $templateFile = parent::getTemplateFileName();

                  $header = str_replace("<title>", '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>', $this->_formValues['report_header']);
                  $content = $header . CRM_Core_Form::$_template->fetch($templateFile) . $this->_formValues['report_footer'];

                  if ($this->_sendmail) {
                    if (CRM_Report_Utils_Report::mailReport($content, $this->_id,
                        $this->_outputMode
                      )) {
                      CRM_Core_Session::setStatus(ts("Report mail has been sent."));
                    }
                    else {
                      CRM_Core_Session::setStatus(ts("Report mail could not be sent."));
                    }
                    if ($this->get('instanceId')) {
                      CRM_Utils_System::civiExit();
                    }

                    CRM_Utils_System::redirect(CRM_Utils_System::url(CRM_Utils_System::currentPath(),
                        'reset=1'
                      ));
                  }
                  elseif ($this->_outputMode == 'print') {
                    echo $content;
                  }
                  else {
                    if ($chartType = CRM_Utils_Array::value('charts', $this->_params)) {
                      $config = &CRM_Core_Config::singleton();
                      //get chart image name
                      $chartImg = $chartType . '_' . $this->_id . '.png';
                      //get image url path
                      $uploadUrl = str_replace('persist/contribute', 'upload/openFlashChart', $config->imageUploadURL);
                      $uploadUrl .= $chartImg;
                      //get image doc path to overwrite
                      $uploadImg = $config->uploadDir . 'openFlashChart/' . $chartImg;
                      //Load the image
                      $chart = imagecreatefrompng($uploadUrl);
                      //convert it into formattd png
                      header('Content-type: image/png');
                      //overwrite with same image
                      imagepng($chart, $uploadImg);
                      //delete the object
                      imagedestroy($chart);
                    }

                    CRM_Utils_PDF_Utils::html2pdf($content, "CiviReport.pdf");
                  }
                  CRM_Utils_System::civiExit();
                }
                elseif ($this->_outputMode == 'csv') {
                  CRM_Report_Utils_Report::export2xls($this, $rows);
                }
                elseif ($this->_outputMode == 'group') {
                  $group = $this->_params['groups'];
                  CRM_Report_Utils_Report::add2group($this, $group);
                }
                elseif ($this->_instanceButtonName == $this->controller->getButtonName()) {

                  CRM_Report_Form_Instance::postProcess($this);
                }
              }

              function postProcess() {
                // get ready with post process params
                $this->beginPostProcess();

                // build query
                $sql = $this->buildQuery();

                // build array of result based on column headers. This method also allows
                // modifying column headers before using it to build result set i.e $rows.
                $this->buildRows($sql, $rows);

                // format result set.
                $this->formatDisplay($rows);

                // assign variables to templates
                $this->doTemplateAssignment($rows);

                // do print / pdf / instance stuff if needed
                $this->endPostProcess($rows);
              }

              function limit($rowCount = self::ROW_COUNT_LIMIT) {

                // lets do the pager if in html mode
                $this->_limit = NULL;
                if ($this->_outputMode == 'html' || $this->_outputMode == 'group') {
                  $this->_select = str_ireplace('SELECT ', 'SELECT SQL_CALC_FOUND_ROWS ', $this->_select);

                  $pageId = CRM_Utils_Request::retrieve('crmPID', 'Integer', CRM_Core_DAO::$_nullObject);

                  if (!$pageId && !empty($_POST)) {
                    if (isset($_POST['PagerBottomButton']) && isset($_POST['crmPID_B'])) {
                      $pageId = max((int)@$_POST['crmPID_B'], 1);
                    }
                    elseif (isset($_POST['PagerTopButton']) && isset($_POST['crmPID'])) {
                      $pageId = max((int)@$_POST['crmPID'], 1);
                    }
                    unset($_POST['crmPID_B'], $_POST['crmPID']);
                  }

                  $pageId = $pageId ? $pageId : 1;
                  $this->set(CRM_Utils_Pager::PAGE_ID, $pageId);
                  $offset = ($pageId - 1) * $rowCount;

                  $this->_limit = " LIMIT $offset, " . $rowCount;
                }
              }

              function setPager($rowCount = self::ROW_COUNT_LIMIT) {
                if ($this->_limit && ($this->_limit != '')) {

                  $sql = "SELECT FOUND_ROWS();";
                  $this->_rowsFound = CRM_Core_DAO::singleValueQuery($sql);
                  $params = ['total' => $this->_rowsFound,
                    'rowCount' => $rowCount,
                    'status' => ts('Records %%StatusMessage%%'),
                    'buttonBottom' => 'PagerBottomButton',
                    'buttonTop' => 'PagerTopButton',
                    'pageID' => $this->get(CRM_Utils_Pager::PAGE_ID),
                  ];

                  $pager = new CRM_Utils_Pager($params);
                  $this->assign_by_ref('pager', $pager);
                }
              }

              function whereGroupClause($clause) {

                $smartGroupQuery = "";



                $group = new CRM_Contact_DAO_Group();
                $group->is_active = 1;
                $group->find();
                while ($group->fetch()) {
                  if (in_array($group->id, $this->_params['gid_value']) && $group->saved_search_id) {
                    $smartGroups[] = $group->id;
                  }
                }


                CRM_Contact_BAO_GroupContactCache::check($smartGroups);

                if (!empty($smartGroups)) {
                  $smartGroups = CRM_Utils_Array::implode(',', $smartGroups);
                  $smartGroupQuery = " UNION DISTINCT 
                  SELECT DISTINCT smartgroup_contact.contact_id                                    
                  FROM civicrm_group_contact_cache smartgroup_contact        
                  WHERE smartgroup_contact.group_id IN ({$smartGroups}) ";
                }

                return " {$this->_aliases['civicrm_contact']}.id IN ( 
                          SELECT DISTINCT {$this->_aliases['civicrm_group']}.contact_id 
                          FROM civicrm_group_contact {$this->_aliases['civicrm_group']}
                          WHERE {$clause} AND {$this->_aliases['civicrm_group']}.status = 'Added' 
                          {$smartGroupQuery} ) ";
              }

              function whereTagClause($clause) {
                // not using left join in query because if any contact
                // belongs to more than one tag, results duplicate
                // entries.
                return " {$this->_aliases['civicrm_contact']}.id IN ( 
                          SELECT DISTINCT {$this->_aliases['civicrm_tag']}.entity_id 
                          FROM civicrm_entity_tag {$this->_aliases['civicrm_tag']}
                          WHERE entity_table = 'civicrm_contact' AND {$clause} ) ";
              }

              function buildACLClause($tableAlias = 'contact_a') {

                list($this->_aclFrom, $this->_aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause($tableAlias);
              }

              function addCustomDataToColumns($addFields = TRUE) {
                if (empty($this->_customGroupExtends)) {
                  return;
                }
                if (!is_array($this->_customGroupExtends)) {
                  $this->_customGroupExtends = [$this->_customGroupExtends];
                }

                $sql = "
SELECT cg.table_name, cg.title, cg.extends, cf.id as cf_id, cf.label, 
       cf.column_name, cf.data_type, cf.html_type, cf.option_group_id, cf.time_format
FROM   civicrm_custom_group cg 
INNER  JOIN civicrm_custom_field cf ON cg.id = cf.custom_group_id
WHERE cg.extends IN ('" . CRM_Utils_Array::implode("','", $this->_customGroupExtends) . "') AND 
      cg.is_active = 1 AND 
      cf.is_active = 1 AND 
      cf.is_searchable = 1
ORDER BY cg.weight";
                $customDAO = &CRM_Core_DAO::executeQuery($sql);

                $curTable = NULL;
                while ($customDAO->fetch()) {
                  if ($customDAO->table_name != $curTable) {
                    $curTable = $customDAO->table_name;
                    $curFields = $curFilters = [];

                    // dummy dao object
                    $this->_columns[$curTable]['dao'] = 'CRM_Contact_DAO_Contact';
                    $this->_columns[$curTable]['extends'] = $customDAO->extends;
                    $this->_columns[$curTable]['grouping'] = $customDAO->table_name;
                    $this->_columns[$curTable]['group_title'] = $customDAO->title;
                  }
                  $fieldName = 'custom_' . $customDAO->cf_id;

                  if ($addFields) {
                    // this makes aliasing work in favor
                    $curFields[$fieldName] =
                    ['name' => $customDAO->column_name,
                      'title' => $customDAO->label,
                      'dataType' => $customDAO->data_type,
                      'htmlType' => $customDAO->html_type,
                    ];
                  }
                  if ($this->_customGroupFilters) {
                    // this makes aliasing work in favor
                    $curFilters[$fieldName] =
                    ['name' => $customDAO->column_name,
                      'title' => $customDAO->label,
                      'dataType' => $customDAO->data_type,
                      'htmlType' => $customDAO->html_type,
                    ];
                  }

                  switch ($customDAO->data_type) {
                    case 'Date':
                      // filters
                      $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_DATE;
                      $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_DATE;
                      // CRM-6946, show time part for datetime date fields
                      if ($customDAO->time_format) {
                        $curFields[$fieldName]['type'] = CRM_Utils_Type::T_TIMESTAMP;
                      }
                      break;

                    case 'Boolean':
                      $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_SELECT;
                      $curFilters[$fieldName]['options'] = ['' => ts('- select -'), 1 => ts('Yes'), 0 => ts('No'),
                      ];
                      $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_INT;
                      break;

                    case 'Int':
                      $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_INT;
                      $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_INT;
                      break;

                    case 'Money':
                      $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_FLOAT;
                      $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_MONEY;
                      break;

                    case 'Float':
                      $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_FLOAT;
                      $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_FLOAT;
                      break;

                    case 'String':
                      $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_STRING;

                      if (!empty($customDAO->option_group_id)) {
                        if (in_array($customDAO->html_type, ['Multi-Select', 'AdvMulti-Select', 'CheckBox'])) {
                          $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_MULTISELECT_SEPARATOR;
                        }
                        else {
                          $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_MULTISELECT;
                        }
                        if ($this->_customGroupFilters) {
                          $curFilters[$fieldName]['options'] = [];
                          $ogDAO = &CRM_Core_DAO::executeQuery("SELECT ov.value, ov.label FROM civicrm_option_value ov WHERE ov.option_group_id = %1 ORDER BY ov.weight", [1 => [$customDAO->option_group_id, 'Integer']]);
                          while ($ogDAO->fetch()) {
                            $curFilters[$fieldName]['options'][$ogDAO->value] = $ogDAO->label;
                          }
                        }
                      }
                      break;

                    case 'StateProvince':
                      if (in_array($customDAO->html_type, ['Multi-Select State/Province'])) {
                        $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_MULTISELECT_SEPARATOR;
                      }
                      else {
                        $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_MULTISELECT;
                      }
                      $curFilters[$fieldName]['options'] = CRM_Core_PseudoConstant::stateProvince();
                      break;

                    case 'Country':
                      if (in_array($customDAO->html_type, ['Multi-Select Country'])) {
                        $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_MULTISELECT_SEPARATOR;
                      }
                      else {
                        $curFilters[$fieldName]['operatorType'] = CRM_Report_Form::OP_MULTISELECT;
                      }
                      $curFilters[$fieldName]['options'] = CRM_Core_PseudoConstant::country();
                      break;

                    case 'ContactReference':
                      $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_STRING;
                      $curFilters[$fieldName]['name'] = 'display_name';
                      $curFilters[$fieldName]['alias'] = "contact_{$fieldName}_civireport";

                      $curFields[$fieldName]['type'] = CRM_Utils_Type::T_STRING;
                      $curFields[$fieldName]['name'] = 'display_name';
                      $curFields[$fieldName]['alias'] = "contact_{$fieldName}_civireport";
                      break;

                    default:
                      $curFields[$fieldName]['type'] = CRM_Utils_Type::T_STRING;
                      $curFilters[$fieldName]['type'] = CRM_Utils_Type::T_STRING;
                  }

                  if (!CRM_Utils_Array::arrayKeyExists('type', $curFields[$fieldName])) {
                    $curFields[$fieldName]['type'] = $curFilters[$fieldName]['type'];
                  }

                  if ($addFields) {
                    $this->_columns[$curTable]['fields'] = $curFields;
                  }
                  if ($this->_customGroupFilters) {
                    $this->_columns[$curTable]['filters'] = $curFilters;
                  }
                  if ($this->_customGroupGroupBy) {
                    $this->_columns[$curTable]['group_bys'] = $curFields;
                  }
                }
              }

              function customDataFrom() {
                if (empty($this->_customGroupExtends)) {
                  return;
                }

                $mapper = CRM_Core_BAO_CustomQuery::$extendsMap;

                foreach ($this->_columns as $table => $prop) {
                  if (substr($table, 0, 13) == 'civicrm_value' || substr($table, 0, 12) == 'custom_value') {
                    $extendsTable = $mapper[$prop['extends']];

                    // check field is in params
                    if (!$this->isFieldSelected($prop)) {
                      continue;
                    }

                    $this->_from .= " 
LEFT JOIN $table {$this->_aliases[$table]} ON {$this->_aliases[$table]}.entity_id = {$this->_aliases[$extendsTable]}.id";
                    // handle for ContactReference
                    if (CRM_Utils_Array::arrayKeyExists('fields', $prop)) {
                      foreach ($prop['fields'] as $fieldName => $field) {
                        if (CRM_Utils_Array::value('dataType', $field) == 'ContactReference') {

                          $columnName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', CRM_Core_BAO_CustomField::getKeyID($fieldName), 'column_name');
                          $this->_from .= "
LEFT JOIN civicrm_contact {$field['alias']} ON {$field['alias']}.id = {$this->_aliases[$table]}.{$columnName} ";
                        }
                      }
                    }
                  }
                }
              }

              function isFieldSelected($prop) {
                if (empty($prop)) {
                  return FALSE;
                }


                if (!empty($this->_params['fields'])) {
                  foreach (array_keys($prop['fields']) as $fieldAlias) {
                    if (CRM_Utils_Array::arrayKeyExists($fieldAlias, $this->_params['fields']) && CRM_Core_BAO_CustomField::getKeyID($fieldAlias)) {
                      return TRUE;
                    }
                  }
                }

                if (!empty($this->_params['group_bys']) && $this->_customGroupGroupBy) {
                  foreach (array_keys($prop['group_bys']) as $fieldAlias) {
                    if (CRM_Utils_Array::arrayKeyExists($fieldAlias, $this->_params['group_bys']) && CRM_Core_BAO_CustomField::getKeyID($fieldAlias)) {
                      return TRUE;
                    }
                  }
                }

                if (!empty($prop['filters']) && $this->_customGroupFilters) {
                  foreach ($prop['filters'] as $fieldAlias => $val) {
                    foreach (['value', 'min', 'max', 'relative', 'from', 'to'] as $attach) {
                      if (isset($this->_params[$fieldAlias . '_' . $attach]) &&
                        !empty($this->_params[$fieldAlias . '_' . $attach])
                      ) {
                        return TRUE;
                      }
                    }
                  }
                }

                return FALSE;
              }
            }

