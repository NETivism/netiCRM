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
 * This class generates form components for processing a pledge payment
 *
 */
class CRM_Pledge_Form_Payment extends CRM_Core_Form {

  /**
   * the id of the pledge payment that we are proceessing
   *
   * @var int
   * @public
   */
  public $_id;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    // check for edit permission
    if (!CRM_Core_Permission::check('edit pledges')) {
       return CRM_Core_Error::statusBounce(ts('You do not have permission to access this page'));
    }

    $this->_id = CRM_Utils_Request::retrieve('ppId', 'Positive', $this);
  }

  /**
   * This function sets the default values for the form.
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $defaults = [];
    if ($this->_id) {
      $params['id'] = $this->_id;

      CRM_Pledge_BAO_Payment::retrieve($params, $defaults);
      list($defaults['scheduled_date']) = CRM_Utils_Date::setDateDefaults($defaults['scheduled_date']);
      if (isset($defaults['contribution_id'])) {
        $this->assign('pledgePayment', TRUE);
      }
      $status = CRM_Contribute_PseudoConstant::contributionStatus($defaults['status_id']);
      $this->assign('status', $status);
    }
    $defaults['option_type'] = 1;
    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    //add various dates
    $this->addDate('scheduled_date', ts('Scheduled Date'), TRUE);

    $this->add('text',
      'scheduled_amount',
      ts('Scheduled Amount'),
      ['READONLY' => TRUE,
        'style' => "background-color:#EBECE4",
      ],
      TRUE
    );
    $this->addRule('scheduled_amount', ts('Please enter a valid monetary amount.'), 'money');
    $optionTypes = ['1' => ts('Adjust Pledge Payment Schedule?'),
      '2' => ts('Adjust Total Pledge Amount?'),
    ];
    $element = $this->addRadio('option_type',
      NULL,
      $optionTypes,
      [], '<br/>'
    );

    $this->addButtons([
        ['type' => 'next',
          'name' => ts('Save'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'js' => ['onclick' => "return verify( );"],
          'isDefault' => TRUE,
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    //get the submitted form values.
    $formValues = $this->controller->exportValues($this->_name);
    $params = [];
    $formValues['scheduled_date'] = CRM_Utils_Date::processDate($formValues['scheduled_date']);
    $params['scheduled_date'] = CRM_Utils_Date::format($formValues['scheduled_date']);

    $now = date('Ymd');
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

    if (CRM_Utils_Date::overdue(CRM_Utils_Date::customFormat($params['scheduled_date'], '%Y%m%d'), $now)) {
      $params['status_id'] = array_search('Overdue', $contributionStatus);
    }
    else {
      $params['status_id'] = array_search('Pending', $contributionStatus);
    }

    $params['id'] = $this->_id;
    $pledgeId = CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_Payment', $params['id'], 'pledge_id');


    CRM_Pledge_BAO_Payment::add($params);
    $adjustTotalAmount = FALSE;
    if (CRM_Utils_Array::value('option_type', $formValues) == 2) {
      $adjustTotalAmount = TRUE;
    }


    $pledgeScheduledAmount = CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_Payment',
      $params['id'],
      'scheduled_amount',
      'id'
    );

    $oldestPaymentAmount = CRM_Pledge_BAO_Payment::getOldestPledgePayment($pledgeId, 2);
    if (($oldestPaymentAmount['count'] != 1) && ($oldestPaymentAmount['id'] == $params['id'])) {
      $oldestPaymentAmount = CRM_Pledge_BAO_Payment::getOldestPledgePayment($pledgeId);
    }
    if (($formValues['scheduled_amount'] - $pledgeScheduledAmount) >= $oldestPaymentAmount['amount']) {
      $adjustTotalAmount = TRUE;
    }
    //update pledge status
    CRM_Pledge_BAO_Payment::updatePledgePaymentStatus($pledgeId,
      [$params['id']],
      $params['status_id'],
      NULL,
      $formValues['scheduled_amount'],
      $adjustTotalAmount
    );

    $statusMsg = ts('Pledge Payment Schedule has been updated.<br />');
    CRM_Core_Session::setStatus($statusMsg);
  }
}

