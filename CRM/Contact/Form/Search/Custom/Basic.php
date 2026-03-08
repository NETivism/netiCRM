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
 * Custom search form providing basic contact search with standard criteria
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

class CRM_Contact_Form_Search_Custom_Basic extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_query;
  /**
   * The constructor gets the submitted form values
   *
   * @param array $formValues
   *
   * @access public
   */
  public function __construct(&$formValues) {
    parent::__construct($formValues);

    $this->normalize();
    $this->_columns = [ts('') => 'contact_type',
      ts('') => 'contact_sub_type',
      ts('Name') => 'sort_name',
      ts('Address') => 'street_address',
      ts('City') => 'city',
      ts('State') => 'state_province',
      ts('Postal') => 'postal_code',
      ts('Country') => 'country',
      ts('Email') => 'email',
      ts('Phone') => 'phone',
    ];

    $params = &CRM_Contact_BAO_Query::convertFormValues($this->_formValues);
    $returnProperties = [];
    foreach ($this->_columns as $name => $field) {
      $returnProperties[$field] = 1;
    }

    $this->_query = new CRM_Contact_BAO_Query(
      $params,
      $returnProperties,
      NULL,
      FALSE,
      FALSE,
      1,
      FALSE,
      FALSE
    );
  }

  /**
   * normalize the form values to make it look similar to the advanced form values
   * this prevents a ton of work downstream and allows us to use the same code for
   * multiple purposes (queries, save/edit etc)
   *
   * @return void
   * @access public
   */
  public function normalize() {
    $contactType = CRM_Utils_Array::value('contact_type', $this->_formValues);
    if ($contactType && !is_array($contactType)) {
      unset($this->_formValues['contact_type']);
      $this->_formValues['contact_type'][$contactType] = 1;
    }

    $group = CRM_Utils_Array::value('group', $this->_formValues);
    if ($group && !is_array($group)) {
      unset($this->_formValues['group']);
      $this->_formValues['group'][$group] = 1;
    }

    $tag = CRM_Utils_Array::value('tag', $this->_formValues);
    if ($tag && !is_array($tag)) {
      unset($this->_formValues['tag']);
      $this->_formValues['tag'][$tag] = 1;
    }

    return;
  }

  /**
   * Builds the quickform for this search
   *
   * @param CRM_Core_Form $form
   *
   * @return void
   * @access public
   */
  public function buildForm(&$form) {
    $contactTypes = ['' => ts('- any contact type -')] + CRM_Contact_BAO_ContactType::getSelectElements();
    $form->add('select', 'contact_type', ts('Find...'), $contactTypes);

    // add select for groups
    $group = ['' => ts('- any group -')] + CRM_Core_PseudoConstant::group();
    $form->addElement('select', 'group', ts('in'), $group);

    // add select for categories
    $tag = ['' => ts('- any tag -')] + CRM_Core_PseudoConstant::tag();
    $form->addElement('select', 'tag', ts('Tagged'), $tag);

    // text for sort_name
    $form->add('text', 'sort_name', ts('Name'));

    $form->assign('elements', ['sort_name', 'contact_type', 'group', 'tag']);
  }

  /**
   * Get count
   *
   * @return int
   * @access public
   */
  public function count() {
    return $this->_query->searchQuery(0, 0, NULL, TRUE);
  }

  /**
   * Get all
   *
   * @param int $offset
   * @param int $rowCount
   * @param null $sort
   * @param bool $includeContactIDs
   *
   * @return string
   * @access public
   */
  public function all(
    $offset = 0,
    $rowCount = 0,
    $sort = NULL,
    $includeContactIDs = FALSE
  ) {
    return $this->_query->searchQuery(
      $offset,
      $rowCount,
      $sort,
      FALSE,
      $includeContactIDs,
      FALSE,
      FALSE,
      TRUE
    );
  }

  /**
   * Get from
   *
   * @return string
   * @access public
   */
  public function from() {
    return $this->_query->_fromClause;
  }

  /**
   * Get where
   *
   * @param bool $includeContactIDs
   *
   * @return string
   * @access public
   */
  public function where($includeContactIDs = FALSE) {
    if ($whereClause = $this->_query->whereClause()) {
      return $whereClause;
    }
    return ' (1) ';
  }

  /**
   * Get template file
   *
   * @return string
   * @access public
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Basic.tpl';
  }
}
