<?php
class CRM_Track_Page_Track extends CRM_Core_Page {

  /**
   * all the fields that are listings related
   *
   * @var array
   * @access protected
   */
  protected $_fields;

  /**
   * run this page (figure out the action needed and perform it).
   *
   * @return void
   */
  function run() {
    $null = CRM_Core_DAO::$_nullObject;
    $params = array(
      'pageType' => CRM_Utils_Request::retrieve('ptype', 'String', $null),
      'pageId' => CRM_Utils_Request::retrieve('pid', 'Positive', $null),
      'state' => CRM_Utils_Request::retrieve('state', 'Integer', $null),
      'referrerType' => CRM_Utils_Request::retrieve('rtype', 'String', $null),
      'referrerNetwork' => CRM_Utils_Request::retrieve('rnetwork', 'String', $null),
      'entityId' => CRM_Utils_Request::retrieve('entity_id', 'Positive', $null),
    );
    if ($params['pageType'] == 'civicrm_contribution_page' && $params['pageId']) {
      // breadcrumb starter
      $breadcrumbs = array(
        array('url' => CRM_Utils_System::url('civicrm/admin', 'reset=1'), 'title' => ts('Administer CiviCRM')),
        array('url' => CRM_Utils_System::url('civicrm/admin/contribute', 'reset=1'), 'title' => ts('Manage Contribution Pages')),
      );
      CRM_Utils_System::appendBreadCrumb($breadcrumbs);
    }
    else
    if ($params['pageType'] == 'civicrm_event' && $params['pageId']) {
      // breadcrumb starter
      $breadcrumbs = array(
        array('url' => CRM_Utils_System::url('civicrm/event', 'reset=1'), 'title' => ts('CiviEvent Dashboard')),
      );
      CRM_Utils_System::appendBreadCrumb($breadcrumbs);
    }
    $selector = new CRM_Track_Selector_Track($params, $this->_scope);
    $selector->filters($this);

    $controller = new CRM_Core_Selector_Controller(
      $selector,
      $this->get(CRM_Utils_Pager::PAGE_ID),
      $sortID,
      CRM_Core_Action::VIEW,
      $this,
      CRM_Core_Selector_Controller::TEMPLATE
    );

    $controller->setEmbedded(TRUE);
    $controller->run();


    CRM_Utils_System::setTitle($selector->getTitle());
    $this->assign('title', $selector->getTitle());

    $sortID = NULL;
    if ($this->get(CRM_Utils_Sort::SORT_ID)) {
      $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID),
        $this->get(CRM_Utils_Sort::SORT_DIRECTION)
      );
    }

    return parent::run();
  }
}

