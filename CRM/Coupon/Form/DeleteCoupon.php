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
 * This class is to build the form for Deleting Set
 */
class CRM_Coupon_Form_DeleteCoupon extends CRM_Core_Form {

  /**
   * the coupon id
   *
   * @var int
   */
  protected $_id;

  /**
   * The description of the coupon being deleted
   *
   * @var string
   */
  protected $_description;

  /**
   * The code of the coupon being deleted
   *
   * @var string
   */
  protected $_code;

  /**
   * set up variables to build the form
   *
   * @return void
   * @acess protected
   */
  function preProcess() {
    $this->_id = $this->get('id');

    $this->_description = CRM_Core_DAO::getFieldValue('CRM_Coupon_DAO_Coupon',
      $this->_id, 'description'
    );
    $this->_code = CRM_Core_DAO::getFieldValue('CRM_Coupon_DAO_Coupon',
      $this->_id, 'code'
    );
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $this->assign('description', $this->_description);
    $this->assign('code', $this->_code);
    $this->addButtons([
        ['type' => 'next',
          'name' => ts('Delete Coupon'),
          'isDefault' => TRUE,
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );
  }

  /**
   * Process the form when submitted
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    if (CRM_Coupon_BAO_Coupon::deleteCoupon($this->_id)) {
      CRM_Core_Session::setStatus(ts('The Coupon \'%1 (%2)\' has been deleted.',
          [1 => $this->_description, 2 => $this->_code]
        ));
    }
    else {
      CRM_Core_Session::setStatus(ts('The Coupon \'%1 (%2)\' has not been deleted!',
          [1 => $this->_description, 2 => $this->_code]
        ));
    }
  }
}

