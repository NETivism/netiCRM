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











/**
 * Main page for viewing contact.
 *
 */
class CRM_Contact_Page_View extends CRM_Core_Page {

  /**
   * the id of the object being viewed (note/relationship etc)
   *
   * @int
   * @access protected
   */
  protected $_id;

  /**
   * the contact id of the contact being viewed
   *
   * @int
   * @access protected
   */
  protected $_contactId;

  /**
   * The action that we are performing
   *
   * @string
   * @access protected
   */
  protected $_action;

  /**
   * The permission we have on this contact
   *
   * @string
   * @access protected
   */
  protected $_permission;

  /**
   * Heart of the viewing process. The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   *
   * @return void
   * @access public
   *
   */
  function preProcess() {
    // process url params
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->assign('id', $this->_id);

    $qfKey = CRM_Utils_Request::retrieve('key', 'String', $this);
    //validate the qfKey

    if (!CRM_Utils_Rule::qfKey($qfKey)) {
      $qfKey = NULL;
    }
    $this->assign('searchKey', $qfKey);

    // retrieve the group contact id, so that we can get contact id
    $gcid = CRM_Utils_Request::retrieve('gcid', 'Positive', $this);

    if (!$gcid) {
      $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    }
    else {
      $this->_contactId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_GroupContact', $gcid, 'contact_id');
    }

    if (!$this->_contactId) {
       return CRM_Core_Error::statusBounce(ts('We could not find a contact id.'));
    }
    $this->assign('contactId', $this->_contactId);

    $path = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $this->_contactId);
    CRM_Utils_System::appendBreadCrumb([['title' => ts('View Contact'),
          'url' => $path,
        ]]);
    CRM_Utils_System::appendBreadCrumb([['title' => ts('Search Results'),
          'url' => $this->getSearchURL(),
        ]]);

    if ($image_URL = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'image_URL')) {
      $contactImage = CRM_Utils_Image::getImageVars($image_URL); 
      $this->assign('contactImage', $contactImage);
    }

    // also store in session for future use
    $session = CRM_Core_Session::singleton();
    $session->set('view.id', $this->_contactId);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->assign('action', $this->_action);

    // check logged in url permission
    self::checkUserPermission($this);

    list($displayName, $contactImage,
      $contactType, $contactSubtype, $contactImageUrl
    ) = self::getContactDetails($this->_contactId);
    $this->assign('displayName', $displayName);

    $this->set('contactType', $contactType);
    $this->set('contactSubtype', $contactSubtype);

    // see if other modules want to add a link activtity bar

    $hookLinks = CRM_Utils_Hook::links('view.contact.activity', 'Contact',
      $this->_contactId, CRM_Core_DAO::$_nullObject
    );
    if (is_array($hookLinks)) {
      $this->assign('hookLinks', $hookLinks);
    }

    // add to recently viewed block
    $isDeleted = (bool) CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'is_deleted');

    $recentOther = ['imageUrl' => $contactImageUrl,
      'subtype' => $contactSubtype,
      'isDeleted' => $isDeleted,
    ];



    if (($session->get('userID') == $this->_contactId) ||
      CRM_Contact_BAO_Contact_Permission::allow($this->_contactId, CRM_Core_Permission::EDIT)
    ) {
      $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/contact/add', "reset=1&action=update&cid={$this->_contactId}");
    }

    if (($session->get('userID') != $this->_contactId) && CRM_Core_Permission::check('delete contacts')
      && !$isDeleted
    ) {
      $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/contact/view/delete', "reset=1&delete=1&cid={$this->_contactId}");
    }

    CRM_Utils_Recent::add($displayName,
      CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$this->_contactId}"),
      $this->_contactId,
      $contactType,
      $this->_contactId,
      $displayName,
      $recentOther
    );
    $this->assign('isDeleted', $isDeleted);

    // set page title
    self::setTitle($this->_contactId, $isDeleted);

    $config = CRM_Core_Config::singleton();

    if ($uid = CRM_Core_BAO_UFMatch::getUFId($this->_contactId)) {
      if ($config->userFramework == 'Drupal') {
        $userRecordUrl = CRM_Utils_System::url('user/' . $uid);
      }
      elseif ($config->userFramework == 'Joomla') {
        $userRecordUrl = $config->userFrameworkBaseURL . 'index2.php?option=com_users&view=user&task=edit&cid[]=' . $uid;
      }
      else {
        $userRecordUrl = NULL;
      }
      $this->assign('userRecordUrl', $userRecordUrl);
      $this->assign('userRecordId', $uid);
    }

    if (CRM_Core_Permission::check('access Contact Dashboard')) {
      $dashboardURL = CRM_Utils_System::url('civicrm/user',
        "reset=1&id={$this->_contactId}"
      );
      $this->assign('dashboardURL', $dashboardURL);
    }

    if (defined('CIVICRM_MULTISITE') &&
      CIVICRM_MULTISITE &&
      $contactType == 'Organization' &&
      CRM_Core_Permission::check('administer Multiple Organizations')
    ) {

      //check is any relationship between the organization and groups
      $groupOrg = CRM_Contact_BAO_GroupOrganization::hasGroupAssociated($this->_contactId);
      if ($groupOrg) {
        $groupOrganizationUrl = CRM_Utils_System::url('civicrm/group',
          "reset=1&oid={$this->_contactId}"
        );
        $this->assign('groupOrganizationUrl', $groupOrganizationUrl);
      }
    }
  }

  /**
   * Get meta details of the contact.
   *
   * @return void
   * @access public
   */
  static function getContactDetails($contactId) {
    return list($displayName,
      $contactImage,
      $contactType,
      $contactSubtype,
      $contactImageUrl
    ) = CRM_Contact_BAO_Contact::getDisplayAndImage($contactId, TRUE);
  }

  function getSearchURL() {
    $qfKey = CRM_Utils_Request::retrieve('key', 'String', $this);
    $context = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'search');
    $this->assign('context', $context);

    //validate the qfKey

    if (!CRM_Utils_Rule::qfKey($qfKey)) {
      $qfKey = NULL;
    }

    $urlString = NULL;
    $urlParams = 'force=1';

    switch ($context) {
      case 'custom':
      case 'fulltext':
        $urlString = 'civicrm/contact/search/custom';
        break;

      case 'advanced':
        $urlString = 'civicrm/contact/search/advanced';
        break;

      case 'builder':
        $urlString = 'civicrm/contact/search/builder';
        break;

      case 'basic':
        $urlString = 'civicrm/contact/search/basic';
        break;

      case 'search':
        $urlString = 'civicrm/contact/search';
        break;

      case 'smog':
      case 'amtg':
        $urlString = 'civicrm/group/search';
        break;
    }
    if ($qfKey) {
      $urlParams .= "&qfKey=$qfKey";
    }
    if (!$urlString) {
      $urlString = 'civicrm/contact/search/basic';
    }

    return CRM_Utils_System::url($urlString, $urlParams);
  }

  static function checkUserPermission($page) {
    // check for permissions
    $page->_permission = NULL;

    // automatically grant permissin for users on their own record. makes
    // things easier in dashboard
    $session = CRM_Core_Session::singleton();


    if ($session->get('userID') == $page->_contactId) {
      $page->assign('permission', 'edit');
      $page->_permission = CRM_Core_Permission::EDIT;
      // deleted contacts’ stuff should be (at best) only viewable
    }
    elseif (CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $page->_contactId, 'is_deleted') and CRM_Core_Permission::check('access deleted contacts')) {
      $page->assign('permission', 'view');
      $page->_permission = CRM_Core_Permission::VIEW;
    }
    elseif (CRM_Contact_BAO_Contact_Permission::allow($page->_contactId, CRM_Core_Permission::EDIT)) {
      $page->assign('permission', 'edit');
      $page->_permission = CRM_Core_Permission::EDIT;
    }
    elseif (CRM_Contact_BAO_Contact_Permission::allow($page->_contactId, CRM_Core_Permission::VIEW)) {
      $page->assign('permission', 'view');
      $page->_permission = CRM_Core_Permission::VIEW;
    }
    else {
      $session->pushUserContext(CRM_Utils_System::url('civicrm', 'reset=1'));
       return CRM_Core_Error::statusBounce(ts('You do not have the necessary permission to view this contact.'));
    }
  }

  static function setTitle($contactId, $isDeleted = FALSE) {
    static $contactDetails;
    $displayName = $contactImage = NULL;
    if (!isset($contactDetails[$contactId])) {
      list($displayName, $contactImage) = self::getContactDetails($contactId);
      $contactDetails[$contactId] = ['displayName' => $displayName,
        'contactImage' => $contactImage,
      ];
    }
    else {
      $displayName = $contactDetails[$contactId]['displayName'];
      $contactImage = $contactDetails[$contactId]['contactImage'];
    }

    // set page title
    $title = $displayName;
    if ($isDeleted) {
      $title = "<del>{$title}</del>";
    }
    CRM_Utils_System::setTitle($displayName, $title);
  }
}

