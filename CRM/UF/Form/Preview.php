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
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

/**
 * This class generates form components
 * for previewing Civicrm Profile Group
 *
 */
class CRM_UF_Form_Preview extends CRM_Core_Form {

  /**
   * The group id that we are editing
   *
   * @var int
   */
  protected $_gid;

  /**
   * the fields needed to build this form
   *
   * @var array
   */
  public $_fields;

  /**
   * Pre processing work done here.
   *
   * Gets session variables for group or field id.
   */
  public function preProcess() {
    $flag = FALSE;
    $this->_gid = $this->get('id');
    $this->set('gid', $this->_gid);
    $field = CRM_Utils_Request::retrieve('field', 'Boolean', $this, TRUE, 0);

    if ($field) {
      $this->_fields = CRM_Core_BAO_UFGroup::getFields($this->_gid, FALSE, NULL, NULL, NULL, TRUE);

      $fieldDAO = new CRM_Core_DAO_UFField();
      $fieldDAO->id = $this->get('fieldId');
      $fieldDAO->find(TRUE);

      if ($fieldDAO->is_active == 0) {
        return CRM_Core_Error::statusBounce(ts('This field is inactive so it will not be displayed on profile form.'));
      }
      elseif ($fieldDAO->is_view == 1) {
        return CRM_Core_Error::statusBounce(ts('This field is view only so it will not be displayed on profile form.'));
      }
      $name = $fieldDAO->field_name;
      // preview for field
      $specialFields = ['street_address', 'supplemental_address_1', 'supplemental_address_2', 'city', 'postal_code', 'postal_code_suffix', 'geo_code_1', 'geo_code_2', 'state_province', 'country', 'county', 'phone', 'email', 'im'];

      if ($fieldDAO->location_type_id) {
        $name .= '-' . $fieldDAO->location_type_id;
      }
      elseif (in_array($name, $specialFields)) {
        $name .= '-Primary';
      }

      if (isset($fieldDAO->phone_type)) {
        $name .= '-' . $fieldDAO->phone_type;
      }

      $fieldArray[$name] = $this->_fields[$name];
      $this->_fields = $fieldArray;
      if (!is_array($this->_fields[$name])) {
        $flag = TRUE;
      }
      $this->assign('previewField', TRUE);
    }
    else {
      $this->_fields = CRM_Core_BAO_UFGroup::getFields($this->_gid);
    }

    if ($flag) {
      $this->assign('viewOnly', FALSE);
    }
    else {
      $this->assign('viewOnly', TRUE);
    }

    $this->set('fieldId', NULL);
    $this->assign("fields", $this->_fields);

    // build usage pages section for full profile preview
    if (!$field) {
      $this->buildUsagePages();
    }
  }

  /**
   * Set the default form values.
   *
   * @return array
   *   The default array reference.
   */
  public function &setDefaultValues() {
    $defaults = [];
    $stateCountryMap = [];
    foreach ($this->_fields as $name => $field) {
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($field['name'])) {
        CRM_Core_BAO_CustomField::setProfileDefaults($customFieldID, $name, $defaults, NULL, CRM_Profile_Form::MODE_REGISTER);
      }

      //CRM-5403
      if ((substr($name, 0, 14) === 'state_province') || (substr($name, 0, 7) === 'country')) {
        list($fieldName, $index) = CRM_Utils_System::explode('-', $name, 2);
        if (!CRM_Utils_Array::arrayKeyExists($index, $stateCountryMap)) {
          $stateCountryMap[$index] = [];
        }
        $stateCountryMap[$index][$fieldName] = $name;
      }
    }

    // also take care of state country widget
    if (!empty($stateCountryMap)) {

      CRM_Core_BAO_Address::addStateCountryMap($stateCountryMap, $defaults);
    }

    //set default for country.
    CRM_Core_BAO_UFGroup::setRegisterDefaults($this->_fields, $defaults);

    // now fix all state country selectors

    CRM_Core_BAO_Address::fixAllStateSelects($this, $defaults);

    return $defaults;
  }

  /**
   * Build the form.
   */
  public function buildQuickForm() {
    foreach ($this->_fields as $name => $field) {
      if (!CRM_Utils_Array::value('is_view', $field)) {
        CRM_Core_BAO_UFGroup::buildProfile($this, $field, CRM_Profile_Form::MODE_CREATE);
      }
    }

    $this->addButtons(
      [
        ['type' => 'cancel',
          'name' => ts('Done with Preview'),
          'isDefault' => TRUE,
        ],
      ]
    );
  }

  /**
   * Build usage pages section showing which pages use this profile.
   */
  protected function buildUsagePages() {
    // WHERE conditions covering both contribution pages and event pages
    $whereModule = "(
      (uj.module = 'CiviContribute' AND uj.entity_table = 'civicrm_contribution_page') OR
      (uj.module IN ('CiviEvent', 'CiviEvent_Additional') AND uj.entity_table = 'civicrm_event')
    )";

    // count distinct usage pages (excluding deleted pages via HAVING page_title IS NOT NULL)
    $countSql = "
      SELECT COUNT(*) FROM (
        SELECT uj.entity_table, uj.entity_id,
          CASE WHEN uj.entity_table = 'civicrm_contribution_page' THEN cp.title
               WHEN uj.entity_table = 'civicrm_event' THEN ev.title END AS page_title
        FROM civicrm_uf_join uj
        LEFT JOIN civicrm_contribution_page cp ON uj.entity_table = 'civicrm_contribution_page' AND uj.entity_id = cp.id
        LEFT JOIN civicrm_event ev ON uj.entity_table = 'civicrm_event' AND uj.entity_id = ev.id
        WHERE uj.uf_group_id = %1 AND {$whereModule}
        GROUP BY uj.entity_table, uj.entity_id
        HAVING page_title IS NOT NULL
      ) AS usage_count
    ";
    $totalCount = CRM_Core_DAO::singleValueQuery($countSql, [1 => [$this->_gid, 'Integer']]);

    if (empty($totalCount)) {
      return;
    }

    // AC-1: assign subtitle for the blue banner (only when actual pages exist)
    $this->assign('usageSubtitle', ts('The actual style and layout should be viewed on the page where this profile is embedded.'));

    // AC-6: set up pager
    $pager = new CRM_Utils_Pager([
      'total' => $totalCount,
      'rowCount' => 25,
      'status' => ts('Pages %%StatusMessage%%'),
      'buttonBottom' => 'PagerBottomButton',
      'buttonTop' => 'PagerTopButton',
      'pageID' => $this->get(CRM_Utils_Pager::PAGE_ID),
    ]);
    $this->assign_by_ref('usagePager', $pager);

    list($offset, $limit) = $pager->getOffsetAndRowCount();

    // query usage pages with pagination
    $sql = "
      SELECT uj.entity_table, uj.entity_id,
        CASE WHEN uj.entity_table = 'civicrm_contribution_page' THEN cp.title
             WHEN uj.entity_table = 'civicrm_event' THEN ev.title END AS page_title,
        CASE WHEN uj.entity_table = 'civicrm_contribution_page' THEN cp.is_active
             WHEN uj.entity_table = 'civicrm_event' THEN ev.is_active END AS page_is_active
      FROM civicrm_uf_join uj
      LEFT JOIN civicrm_contribution_page cp ON uj.entity_table = 'civicrm_contribution_page' AND uj.entity_id = cp.id
      LEFT JOIN civicrm_event ev ON uj.entity_table = 'civicrm_event' AND uj.entity_id = ev.id
      WHERE uj.uf_group_id = %1 AND {$whereModule}
      GROUP BY uj.entity_table, uj.entity_id
      HAVING page_title IS NOT NULL
      ORDER BY uj.entity_table, uj.entity_id
      LIMIT %2 OFFSET %3
    ";
    $params = [
      1 => [$this->_gid, 'Integer'],
      2 => [$limit, 'Integer'],
      3 => [$offset, 'Integer'],
    ];
    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    $rows = [];
    while ($dao->fetch()) {
      $entityId = $dao->entity_id;
      $isContribution = ($dao->entity_table === 'civicrm_contribution_page');

      // build page title with ID format
      $pageTitle = $dao->page_title . ts('(ID: %1)', [1 => $entityId]);

      // build frontend URL
      if ($isContribution) {
        $frontUrl = CRM_Utils_System::url('civicrm/contribute/transact', 'reset=1&id=' . $entityId);
        $configUrl = CRM_Utils_System::url('civicrm/admin/contribute/custom', 'reset=1&action=update&id=' . $entityId);
      }
      else {
        $frontUrl = CRM_Utils_System::url('civicrm/event/register', 'reset=1&id=' . $entityId);
        $configUrl = CRM_Utils_System::url('civicrm/event/manage/registration', 'reset=1&action=update&id=' . $entityId);
      }

      $rows[] = [
        'pageTitle' => $pageTitle,
        'entityId' => $entityId,
        'isActive' => (int) $dao->page_is_active === 1,
        'frontUrl' => $frontUrl,
        'configUrl' => $configUrl,
      ];
    }

    $this->assign('usagePages', $rows);
    $this->assign('hasUsagePages', TRUE);
  }
}
