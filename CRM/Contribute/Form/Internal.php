<?php
class CRM_Contribute_Form_Internal extends CRM_Core_Form {
  public $_contactId;
  public $_pageId;
  public $_ajax;
  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    $this->_pageId = CRM_Utils_Request::retrieve('page_id', 'Positive', $this);
    $snippet = CRM_Utils_Request::retrieve('snippet', 'Positive', $this);
    if ($snippet == 4) {
      $this->_ajax = TRUE;
      $this->assign('ajax', TRUE);
    }

    // check if table field exists
    $checkQuery = "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'civicrm_contribution_page' AND column_name = 'is_internal'";
    $exists = CRM_Core_DAO::singleValueQuery($checkQuery);
    if (!$exists) {
      CRM_Core_Error::fatal('You need to install internal contribution page first before processing this');
    }
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @param null
   *
   * @return array   array of default values
   * @access public
   */
  function setDefaultValues() {
    $defaults = array();
    if (!empty($this->_contactId)) {
      $defaults['contact_id'] = $this->_contactId;
    }
    if (!empty($this->_pageId)) {
      $defaults['contribution_page_id'] = $this->_pageId;
    }
    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @param null
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_ajax) {
      $records = $this->getContributionDetails();
      $this->addSelect('original_id', ts('Based Contribution Record'), $records);
      return;
    }
    $pages = array();
    CRM_Core_PseudoConstant::populate($pages, 'CRM_Contribute_DAO_ContributionPage', FALSE, 'title', 'is_active', ' is_internal = 1');
    foreach($pages as $id => &$page) {
      $page .= " ($id)";
    }
    $records = array(
      ts('-- select --')
    );
    if ($this->_contactId) {
      $ele = $this->add('text', 'contact_id', ts('Contact').' - '.ts('Contact ID'), '', TRUE);
      $ele->freeze();
      $this->assign('contact_id', $this->_contactId);
      list($displayName) = CRM_Contact_BAO_Contact::getContactDetails($this->_contactId);
      if ($displayName) {
        $this->assign('display_name', $displayName);
      }
      $records = $this->getContributionDetails();
      $this->addSelect('original_id', ts('Based Contribution Record'), $records);
    }
    else {
      CRM_Contact_Form_NewContact::buildQuickForm($this);
      $this->addSelect('original_id', ts('Based Contribution Record'), array());
    }
    $this->addSelect('contribution_page_id', ts('Contribution Page'), $pages, '', TRUE);
    $this->addCheckBox('is_test', '', array( ts('Test-drive') => 1));
    $this->addButtons(array(
        array(
          'type' => 'refresh',
          'name' => ts('Next >>'),
          'spacing' => ' ',
          'isDefault' => TRUE,
        ),
      )
    );

    $this->addFormRule(array('CRM_Contribute_Form_Internal', 'formRule'), $this);
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($fields, $files, $form) {
    $errors = array();
    if (empty($fields['contact_select_id'][1])) {
      if (!ctype_digit($fields['contact_id']) || empty($fields['contact_id'])) {
        $errors['contact_id'] = ts('Please enter a valid number for %1', array(1 => ts('Contact ID')));
      }
      else {
        $found = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contact WHERE id = %1 AND is_deleted = 0", array(
          1 => array($fields['contact_id'], 'Positive')
        ));
        if (!$found) {
          $errors['contact_id'] = ts('contact does not exist: %1', array(1 => $fields['contact_id']));
        }
      }
    }
    $pages = CRM_Contribute_PseudoConstant::contributionPage(NULL, TRUE);
    if (!isset($pages[$fields['contribution_page_id']])) {
      $errors['contribution_page_id'] = ts('%1 does not exists or is empty', array(1 => ts('Contribution Page').'-'.$fields['contribution_page_id']));
    }
    return $errors;
  }

  /**
   * Process the form
   *
   * @param null
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    if (!empty($params['contact_select_id'][1])) {
      $params['contact_id'] = $params['contact_select_id'][1];
    }
    $cs = CRM_Contact_BAO_Contact_Utils::generateChecksum($params['contact_id'], CRM_REQUEST_TIME, 1);
    $cid = $params['contact_id'];
    $pageId = $params['contribution_page_id'];
    if ($params['is_test']) {
      $action = '&action=preview';
    }
    if ($params['original_id']) {
      $oid = $params['original_id'];
      $url = CRM_Utils_System::url('civicrm/contribute/transact', "reset=1&id={$pageId}&cid={$cid}&oid={$oid}&cs={$cs}{$action}");
    }
    else {
      $url = CRM_Utils_System::url('civicrm/contribute/transact', "reset=1&id={$pageId}&cid={$cid}&cs={$cs}{$action}");
    }

    CRM_Utils_System::redirect($url);
  }

  public function getContributionDetails() {
    $records = array(ts('-- select --'));
    // get exists contribution
    $dao = CRM_Core_DAO::executeQuery("SELECT id, total_amount, contribution_recur_id, MAX(receive_date) FROM civicrm_contribution WHERE contribution_recur_id > 0 AND contact_id = %1 AND is_test = 0 GROUP BY contribution_recur_id ORDER BY contribution_recur_id  DESC LIMIT 3", array(
      1 => array($this->_contactId, 'Positive'),
    ));

    while($dao->fetch()) {
      if ($dao->receive_date) {
        $date = CRM_Utils_Date::customFormat($dao->receive_date);
      }
      else {
        $date = ts('None');
      }
      $records[ts('Recurring Contribution')][$dao->id] = ts('Recurring Contribution ID').":".$dao->contribution_recur_id." / ".ts('Total Amount').':'.$dao->total_amount." / ".ts('Receive Date').':'.$date;
    }

    $dao = CRM_Core_DAO::executeQuery("SELECT id, total_amount, receive_date FROM civicrm_contribution WHERE contribution_recur_id IS NULL AND contact_id = %1 AND is_test = 0 AND contribution_status_id = 1 ORDER BY receive_date DESC LIMIT 10", array(
      1 => array($this->_contactId, 'Positive'),
    ));
    while($dao->fetch()) {
      if ($dao->receive_date) {
        $date = CRM_Utils_Date::customFormat($dao->receive_date);
      }
      else {
        $date = ts('None');
      }
      $records[ts('Non-Recurring Contribution')][$dao->id] = ts('Contribution ID').":".$dao->id." / ".ts('Total Amount').':'.$dao->total_amount. ' / '.ts('Receive Date').':'.$date;
    }
    return $records;
  }
}