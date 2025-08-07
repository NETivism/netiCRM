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
 * Create a page for displaying Price Fields.
 *
 * Heart of this class is the run method which checks
 * for action type and then displays the appropriate
 * page.
 *
 */
class CRM_Price_Page_Field extends CRM_Core_Page {

  /**
   * The price set group id of the field
   *
   * @var int
   * @access protected
   */
  protected $_sid;

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @access private
   */
  private static $_actionLinks;

  /**
   * Get the action links for this page.
   *
   * @param null
   *
   * @return array  array of action links that we need to display for the browse screen
   * @access public
   */
  function &actionLinks() {
    if (!isset(self::$_actionLinks)) {
      // helper variable for nicer formatting
      $deleteExtra = ts('Are you sure you want to delete this price field?');
      $copyExtra = ts('Are you sure you want to make a copy of this price field?');
      self::$_actionLinks = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit Price Field'),
          'url' => 'civicrm/admin/price/field',
          'qs' => 'action=update&reset=1&sid=%%sid%%&fid=%%fid%%',
          'title' => ts('Edit Price'),
        ],
        CRM_Core_Action::PREVIEW => [
          'name' => ts('Preview Field'),
          'url' => 'civicrm/admin/price/field',
          'qs' => 'action=preview&reset=1&sid=%%sid%%&fid=%%fid%%',
          'title' => ts('Preview Price'),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'extra' => 'onclick = "enableDisable( %%fid%%,\'' . 'CRM_Price_BAO_Field' . '\',\'' . 'enable-disable' . '\' );"',
          'ref' => 'disable-action',
          'title' => ts('Disable Price'),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'extra' => 'onclick = "enableDisable( %%fid%%,\'' . 'CRM_Price_BAO_Field' . '\',\'' . 'disable-enable' . '\' );"',
          'ref' => 'enable-action',
          'title' => ts('Enable Price'),
        ],
        CRM_Core_Action::COPY => [
          'name' => ts('Copy'),
          'url' => CRM_Utils_System::currentPath(),
          'qs' => 'action=copy&sid=%%sid%%&fid=%%fid%%&key=%%key%%',
          'title' => ts('Make a Copy of Price Field'),
          'extra' => 'onclick = "return confirm(\'' . $copyExtra . '\');"',
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/price/field',
          'qs' => 'action=delete&reset=1&sid=%%sid%%&fid=%%fid%%',
          'title' => ts('Delete Price'),
          'extra' => 'onclick = "return confirm(\'' . $deleteExtra . '\');"',
        ],
      ];
    }
    return self::$_actionLinks;
  }

  /**
   * Browse all price set fields.
   *
   * @param null
   *
   * @return void
   * @access public
   */
  function browse() {

    $priceField = [];
    $priceFieldBAO = new CRM_Price_BAO_Field();

    // fkey is sid
    $priceFieldBAO->price_set_id = $this->_sid;
    $priceFieldBAO->orderBy('weight, label');
    $priceFieldBAO->find();

    $name = get_class($this);
    $key = CRM_Core_Key::get($name);
    $this->assign('key', $key);

    while ($priceFieldBAO->fetch()) {
      $priceField[$priceFieldBAO->id] = [];
      CRM_Core_DAO::storeValues($priceFieldBAO, $priceField[$priceFieldBAO->id]);

      // get price if it's a text field
      if ($priceFieldBAO->html_type == 'Text') {
        $optionValues = [];
        $params = ['price_field_id' => $priceFieldBAO->id];


        CRM_Price_BAO_FieldValue::retrieve($params, $optionValues);

        $priceField[$priceFieldBAO->id]['price'] = CRM_Utils_Array::value('amount', $optionValues);
      }

      $action = array_sum(array_keys($this->actionLinks()));

      if ($priceFieldBAO->is_active) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }

      if ($priceFieldBAO->active_on == '0000-00-00 00:00:00') {
        $priceField[$priceFieldBAO->id]['active_on'] = '';
      }

      if ($priceFieldBAO->expire_on == '0000-00-00 00:00:00') {
        $priceField[$priceFieldBAO->id]['expire_on'] = '';
      }

      // need to translate html types from the db

      $htmlTypes = CRM_Price_BAO_Field::htmlTypes();
      $priceField[$priceFieldBAO->id]['html_type'] = $htmlTypes[$priceField[$priceFieldBAO->id]['html_type']];
      $priceField[$priceFieldBAO->id]['order'] = $priceField[$priceFieldBAO->id]['weight'];
      $priceField[$priceFieldBAO->id]['action'] = CRM_Core_Action::formLink(self::actionLinks(), $action,
        [
          'fid' => $priceFieldBAO->id,
          'sid' => $this->_sid,
          'key' => $key
        ]
      );
    }

    $returnURL = CRM_Utils_System::url('civicrm/admin/price/field', "reset=1&action=browse&sid={$this->_sid}");
    $filter = "price_set_id = {$this->_sid}";

    CRM_Utils_Weight::addOrder($priceField, 'CRM_Price_DAO_Field',
      'id', $returnURL, $filter
    );
    $this->assign('priceField', $priceField);
  }

  /**
   * edit price data.
   *
   * editing would involved modifying existing fields + adding data to new fields.
   *
   * @param string  $action    the action to be invoked

   *
   * @return void
   * @access public
   */
  function edit($action) {
    // create a simple controller for editing price data
    $controller = new CRM_Core_Controller_Simple('CRM_Price_Form_Field', ts('Price Field'), $action);

    // set the userContext stack
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/price/field', 'reset=1&action=browse&sid=' . $this->_sid));

    $controller->set('sid', $this->_sid);
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();
  }


  /**
   * This function is to make a copy of a price set, including
   * all the fields in the page
   *
   * @return void
   * @access public
   */
  function copy() {
    $key = CRM_Utils_Request::retrieve('key', 'String',
      CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST'
    );

    $name = get_class($this);
    if (!CRM_Core_Key::validate($key, $name)) {
      return CRM_Core_Error::statusBounce(ts('Sorry, we cannot process this request for security reasons. The request may have expired or is invalid. Please return to the price field list and try again.'));
    }

    $sid = CRM_Utils_Request::retrieve('sid', 'Positive',
      $this, TRUE, 0, 'GET'
    );

    $fid = CRM_Utils_Request::retrieve('fid', 'Positive',
      $this, TRUE, 0, 'GET'
    );


    $copy = CRM_Price_BAO_Field::copy($fid);

    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/price/field', "action=update&reset=1&sid={$sid}&fid={$copy->id}"));
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   *
   * @param null
   *
   * @return void
   * @access public
   */
  function run() {


    // get the group id
    $this->_sid = CRM_Utils_Request::retrieve('sid', 'Positive',
      $this
    );
    $fid = CRM_Utils_Request::retrieve('fid', 'Positive',
      $this, FALSE, 0
    );
    $action = CRM_Utils_Request::retrieve('action', 'String',
      // default to 'browse'
      $this, FALSE, 'browse'
    );

    if ($this->_sid) {

      CRM_Price_BAO_Set::checkPermission($this->_sid);
    }
    if ($action & (CRM_Core_Action::DELETE)) {

      $usedBy = &CRM_Price_BAO_Set::getUsedBy($this->_sid);
      if (empty($usedBy)) {
        // prompt to delete
        $session = &CRM_Core_Session::singleton();
        $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/price/field', 'reset=1&action=browse&sid=' . $this->_sid));
        $controller = new CRM_Core_Controller_Simple('CRM_Price_Form_DeleteField', "Delete Price Field", '');
        $controller->set('fid', $fid);
        $controller->setEmbedded(TRUE);
        $controller->process();
        $controller->run();
      }
      else {
        // add breadcrumb

        $url = CRM_Utils_System::url('civicrm/admin/price/field', 'reset=1');
        CRM_Utils_System::appendBreadCrumb([['title'=>ts('Price'), 'url'=> $url]]);
        $this->assign('usedPriceSetTitle', CRM_Price_BAO_Field::getTitle($fid));
        $this->assign('usedBy', $usedBy);
        $comps = ["Event" => "civicrm_event",
          "Contribution" => "civicrm_contribution_page",
        ];
        $priceSetContexts = [];
        foreach ($comps as $name => $table) {
          if (CRM_Utils_Array::arrayKeyExists($table, $usedBy)) {
            $priceSetContexts[] = $name;
          }
        }
        $this->assign('contexts', $priceSetContexts);
      }
    }
    elseif ($action & CRM_Core_Action::COPY) {
      $session = CRM_Core_Session::singleton();
      CRM_Core_Session::setStatus(ts("A copy of the price field has been created"));
      $this->copy();
    }

    if ($this->_sid) {
      $groupTitle = CRM_Price_BAO_Set::getTitle($this->_sid);
      $this->assign('sid', $this->_sid);
      $this->assign('groupTitle', $groupTitle);
      CRM_Utils_System::setTitle(ts('%1 - Price Fields', [1 => $groupTitle]));
    }

    // assign vars to templates
    $this->assign('action', $action);

    // what action to take ?
    if ($action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      // no browse for edit/update/view
      $this->edit($action);
    }
    elseif ($action & CRM_Core_Action::PREVIEW) {
      $this->preview($fid);
    }
    else {

      $this->browse();
    }

    // Call the parents run method
    parent::run();
  }

  /**
   * Preview price field
   *
   * @param int  $id    price field id
   *
   * @return void
   * @access public
   */
  function preview($fid) {
    $controller = new CRM_Core_Controller_Simple('CRM_Price_Form_Preview', ts('Preview Form Field'), CRM_Core_Action::PREVIEW);
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/price/field', 'reset=1&action=browse&sid=' . $this->_sid));
    $controller->set('fieldId', $fid);
    $controller->set('groupId', $this->_sid);
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();
  }
}

