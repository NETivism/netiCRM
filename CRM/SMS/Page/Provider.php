<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
 */

/**
 * Page for displaying list of Providers
 */
class CRM_SMS_Page_Provider extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  static $_links = NULL;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_SMS_BAO_Provider';
  }

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/sms/provider',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Provider'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/sms/provider',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Provider'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_SMS_BAO_Provider' . '\',\'' . 'disable-enable' . '\' , true);"',
          'ref' => 'enable-action',
          'title' => ts('Enable Provider'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_SMS_BAO_Provider' . '\',\'' . 'enable-disable' . '\' , true);"',
          'ref' => 'disable-action',
          'title' => ts('Disable Provider'),
        ),
      );
    }
    return self::$_links;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   */
  public function run() {
    // set title and breadcrumb
    CRM_Utils_System::setTitle(ts('Settings - SMS Provider'));
    $breadCrumb = array(
      array(
        'title' => ts('SMS Provider'),
        'url' => CRM_Utils_System::url('civicrm/admin/sms/provider',
          'reset=1'
        ),
      ),
    );
    CRM_Utils_System::appendBreadCrumb($breadCrumb);

    $this->_id = CRM_Utils_Request::retrieve('id', 'String',
      $this, FALSE, 0
    );
    $this->_action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 0
    );

    return parent::run();
  }

  /**
   * Browse all Providers.
   *
   * @param array $action
   */
  public function browse($action = NULL) {
    $providers = CRM_SMS_BAO_Provider::getProviders();
    $rows = array();
    foreach ($providers as $provider) {
      $action = array_sum(array_keys($this->links()));
      // update enable/disable links.
      if ($provider['is_active']) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }

      $apiTypes = CRM_Core_OptionGroup::values('sms_api_type', FALSE, FALSE, FALSE, NULL, 'label');
      $provider['api_type'] = $apiTypes[$provider['api_type']];
      if ($provider['name'] == 'CRM_SMS_Provider_Flydove') {
        $apiParams = json_decode($provider['api_params'], TRUE);
        if (is_array($apiParams)) {
          $provider['api_params'] = array();
          foreach($apiParams as $key => $val) {
            if (is_array($val)) {
              $provider['api_params'][] = '<strong>'.$key.'</strong>';
              foreach($val as $k => $v) {
                $provider['api_params'][] = $k.":" . CRM_Utils_String::mask($v);
              }
            }
            else {
              $provider['api_params'][] = $key.":" . CRM_Utils_String::mask($val);
            }
          }
          $provider['api_params'] = implode('<br>', $provider['api_params']);
        }
      }

      $provider['action'] = CRM_Core_Action::formLink(self::links(), $action,
        array('id' => $provider['id']),
        ts('more'),
        FALSE,
        'sms.provider.row',
        'SMSProvider',
        $provider['id']
      );
      $rows[] = $provider;
    }
    $this->assign('rows', $rows);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_SMS_Form_Provider';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'SMS Provider';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string
   *   user context.
   */
  public function userContext($mode = NULL) {
    return 'civicrm/admin/sms/provider';
  }

}
