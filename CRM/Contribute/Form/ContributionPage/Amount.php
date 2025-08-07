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
 * form to process actions on the group aspect of Custom Data
 */
class CRM_Contribute_Form_ContributionPage_Amount extends CRM_Contribute_Form_ContributionPage {

  /**
   * contribution amount block.
   *
   * @var array
   * @access protected
   */
  protected $_amountBlock = [];

  /**
   * Constants for number of options for data types of multiple option.
   */
  CONST NUM_OPTION = 11;

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {


    // do u want to allow a free form text field for amount
    $this->addElement('checkbox', 'is_allow_other_amount', ts('Allow other amounts'), NULL, ['onclick' => "minMax(this);showHideAmountBlock( this, 'is_allow_other_amount' );"]);
    $this->add('text', 'min_amount', ts('Minimum Amount'), ['size' => 8, 'maxlength' => 8]);
    $this->addRule('min_amount', ts('Please enter a valid money value (e.g. %1).', [1 => CRM_Utils_Money::format('9.99', ' ')]), 'money');

    $this->add('text', 'max_amount', ts('Maximum Amount'), ['size' => 8, 'maxlength' => 8]);
    $this->addRule('max_amount', ts('Please enter a valid money value (e.g. %1).', [1 => CRM_Utils_Money::format('99.99', ' ')]), 'money');

    $filter = [];
    $grouping = ['recurring' => ts('Recurring Contribution'), 'non-recurring' => ts('Non-recurring Contribution')];
    if (!empty($this->_membershipBlock) && !empty($this->_membershipBlock['membership_types'])) {
      $membershipTypes = CRM_Member_PseudoConstant::membershipType();
      foreach($this->_membershipBlock['membership_types'] as $mTypeId) {
        $grouping['membership-'.$mTypeId] = ts('Membership').':'.$membershipTypes[$mTypeId];
      }
    }
    for ($i = 1; $i <= self::NUM_OPTION; $i++) {
      // label
      $this->add('text', "label[$i]", ts('Label'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'label'));

      // value
      $this->add('text', "value[$i]", ts('Value'));
      $this->add('select', "grouping[$i]", ts('show'), ['' => ts('no limit')] + $grouping);
      $this->addRule("value[$i]", ts('Please enter a valid money value (e.g. %1).', [1 => CRM_Utils_Money::format('99.99', ' ')]), 'money');

      // default
      $filter[] = $this->createElement('checkbox', $i);
      $default[] = $this->createElement('radio', NULL, NULL, NULL, $i);
    }

    $this->addGroup($filter, 'filter');
    $this->addGroup($default, 'default');

    $this->addElement('checkbox', 'amount_block_is_active', ts('Contribution Amounts section enabled'), NULL, ['onclick' => "showHideAmountBlock( this, 'amount_block_is_active' );"]);

    $this->addElement('checkbox', 'is_monetary', ts('Execute real-time monetary transactions'),NULL,['onclick' => "showHideAmountBlock( this, 'is_monetary' );"]);

    $paymentProcessor = &CRM_Core_PseudoConstant::paymentProcessor(FALSE, FALSE, "payment_processor_type != 'TaiwanACH' AND billing_mode != 7");
    $recurringPaymentProcessor = [];

    if (!empty($paymentProcessor)) {
      $paymentProcessorIds = CRM_Utils_Array::implode(',', array_keys($paymentProcessor));
      $query = "
SELECT id
  FROM civicrm_payment_processor
 WHERE id IN ({$paymentProcessorIds})
   AND is_recur = 1";
      $dao = &CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        $recurringPaymentProcessor[] = $dao->id;
      }
    }
    $this->assign('recurringPaymentProcessor', $recurringPaymentProcessor);
    if (count($paymentProcessor)) {
      $this->assign('paymentProcessor', $paymentProcessor);
    }

    foreach($paymentProcessor as $pid => &$pvalue) {
      $pvalue .= "-".ts("ID")."$pid";
    }
    $this->addCheckBox('payment_processor', ts('Payment Processor'),
      array_flip($paymentProcessor),
      NULL, NULL, NULL, NULL,
      ['&nbsp;&nbsp;', '&nbsp;&nbsp;', '&nbsp;&nbsp;', '<br />']
    );




    //check if selected payment processor supports recurring payment
    if (!empty($recurringPaymentProcessor)) {
      $this->addElement('checkbox', 'is_recur', ts('Recurring contributions'), NULL,
        ['onclick' => "showHideByValue('is_recur',true,'recurFields','table-row','radio',false); showRecurInterval( );"]
      );
      $this->addElement('checkbox', 'is_recur_only', ts('Only allowed recurring contribution'), NULL);
      $recurFrequencyUnits = CRM_Core_OptionGroup::values('recur_frequency_units', TRUE, FALSE, FALSE, NULL, 'label');
      self::doShowHideFrequencyUnits($recurFrequencyUnits, $recurringPaymentProcessor);
      $this->addCheckBox('recur_frequency_unit', ts('Supported recurring units'),
        $recurFrequencyUnits,
        NULL, NULL, NULL, NULL,
        ['&nbsp;&nbsp;']
      );
      // $this->addElement('checkbox', 'is_recur_interval', ts('Support recurring intervals'));
      $this->addElement('hidden', 'is_recur_interval', 0);
      $options = [
        1 => ts('Yes'),
        0 => ts('No'),
      ];
      $this->addRadio('show_installments_option', ts("Show Installments Option"), $options);
    }

    // add pay later options
    $this->addElement('checkbox', 'is_pay_later', ts('Pay later option'),
      NULL, ['onclick' => "payLater(this);"]
    );
    $this->addElement('textarea', 'pay_later_text', ts('Pay later label'),
      CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'pay_later_text'),
      FALSE
    );
    $this->addElement('textarea', 'pay_later_receipt', ts('Pay later instructions'),
      CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'pay_later_receipt'),
      FALSE
    );
    // add price set fields

    $price = CRM_Price_BAO_Set::getAssoc(FALSE, 'CiviContribute');
    if (CRM_Utils_System::isNull($price)) {
      $this->assign('price', FALSE);
    }
    else {
      $this->assign('price', TRUE);
    }
    $this->add('select', 'price_set_id', ts('Price Set'),
      ['' => ts('- none -')] + $price,
      NULL, ['onchange' => "showHideAmountBlock( this.value, 'price_set_id' );"]
    );
    //CiviPledge fields.
    $config = CRM_Core_Config::singleton();
    if (in_array('CiviPledge', $config->enableComponents)) {
      $this->assign('civiPledge', TRUE);

      $this->addElement('checkbox', 'is_pledge_active', ts('Pledges'),
        NULL, ['onclick' => "showHideAmountBlock( this, 'is_pledge_active' ); return showHideByValue('is_pledge_active',true,'pledgeFields','table-row','radio',false);"]
      );
      $this->addCheckBox('pledge_frequency_unit', ts('Supported pledge frequencies'),
        CRM_Core_OptionGroup::values("recur_frequency_units", FALSE, FALSE, FALSE, NULL, 'name'),
        NULL, NULL, NULL, NULL,
        ['&nbsp;&nbsp;', '&nbsp;&nbsp;', '&nbsp;&nbsp;', '<br/>']
      );
      $this->addElement('checkbox', 'is_pledge_interval', ts('Allow frequency intervals'));
      $this->addElement('text', 'initial_reminder_day', ts('Send payment reminder'), ['size' => 3]);
      $this->addElement('text', 'max_reminders', ts('Send up to'), ['size' => 3]);
      $this->addElement('text', 'additional_reminder_day', ts('Send additional reminders'), ['size' => 3]);
    }

    $params = ['id' => $this->_id];
    CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_ContributionPage', $params, $values, ['is_active']);

    if($values['is_active'] & CRM_Contribute_BAO_ContributionPage::IS_SPECIAL) {
      $this->assign('is_special', 1);
    }

    //add currency element.
    $this->addCurrency('currency', ts('Currency'));

    $this->addFormRule(['CRM_Contribute_Form_ContributionPage_Amount', 'formRule'], $this);

    parent::buildQuickForm();
  }

  private static function doShowHideFrequencyUnits(&$recurFrequencyUnits, $recurringPaymentProcessor = []) {
    if (empty($recurringPaymentProcessor)) {
      return ;
    }
    else {
      // If all recur processors don't allow certain unit, remove it.
      $paymentUnitsCount = [];
      foreach ($recurFrequencyUnits as $index => $unit) {
        $paymentUnitsCount[$unit] = 0;
        foreach ($recurringPaymentProcessor as $ppid) {
          $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($ppid, '');
          $paymentClass = 'CRM_Core_'.$paymentProcessor['class_name'];
          if (class_exists($paymentClass) && property_exists($paymentClass, '_allowRecurUnit')) {
            $paymentUnitsCount[$unit] += in_array($unit, $paymentClass::$_allowRecurUnit) ? 1 : 0;
          }
          else {
            $paymentUnitsCount[$unit] += 1;
          }
        }
        if ($paymentUnitsCount[$unit] == 0) {
          unset($recurFrequencyUnits[$index]);
        }
      }
    }
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    $title = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $this->_id, 'title');
    CRM_Utils_System::setTitle(ts('Contribution Amounts (%1)', [1 => $title]));

    if (!CRM_Utils_Array::value('pay_later_text', $defaults)) {
      $defaults['pay_later_text'] = ts('I will send payment by check');
    }
    
    if (CRM_Utils_Array::value('payment_processor', $defaults)) {
      $defaults['payment_processor'] = array_fill_keys(explode(CRM_Core_DAO::VALUE_SEPARATOR,
          $defaults['payment_processor']
        ), '1');
    }

    if (!isset($defaults['installments_option'])) {
      $defaults['show_installments_option'] = 1;
    }
    else {
      $defaults['show_installments_option'] = $defaults['installments_option'] ? 1 : 0;
    }

    if (CRM_Utils_Array::value('amount_block_is_active', $defaults)) {

      // don't allow other amount option when price set present.
      $this->assign('priceSetID', $this->_priceSetID);
      if ($this->_priceSetID) {
        return $defaults;
      }


      CRM_Core_OptionGroup::getAssoc("civicrm_contribution_page.amount.{$this->_id}", $this->_amountBlock);
      $hasAmountBlock = FALSE;
      if (!empty($this->_amountBlock)) {
        $hasAmountBlock = TRUE;
        $defaults = array_merge($defaults, $this->_amountBlock);
      }

      if (CRM_Utils_Array::value('value', $defaults) && is_array($defaults['value'])) {
        if (CRM_Utils_Array::value('amount_id', $defaults) && is_array($defaults['amount_id'])) {
          foreach ($defaults['value'] as $i => $v) {
            if (!empty($defaults['default_amount_id']) && $defaults['amount_id'][$i] == $defaults['default_amount_id']) {
              $defaults['default'] = $i;
            }
            if ($defaults['filter'][$i]) {
              $defaults['filter'][$i] = 1;
            }
          }
        }

        // CRM-4038: fix value display
        foreach ($defaults['value'] as & $amount) {
          $amount = trim(CRM_Utils_Money::format($amount, ' '));
        }
      }
    }

    // fix the display of the monetary value, CRM-4038

    if (isset($defaults['min_amount'])) {
      $defaults['min_amount'] = CRM_Utils_Money::format($defaults['min_amount'], NULL, '%a');
    }
    if (isset($defaults['max_amount'])) {
      $defaults['max_amount'] = CRM_Utils_Money::format($defaults['max_amount'], NULL, '%a');
    }

    return $defaults;
  }

  /**
   * global form rule
   *
   * @param array $fields  the input form values
   * @param array $files   the uploaded files if any
   * @param array $options additional user data
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $self) {
    $errors = [];

    $minAmount = CRM_Utils_Array::value('min_amount', $fields);
    $maxAmount = CRM_Utils_Array::value('max_amount', $fields);
    if (!empty($minAmount) && !empty($maxAmount)) {
      $minAmount = CRM_Utils_Rule::cleanMoney($minAmount);
      $maxAmount = CRM_Utils_Rule::cleanMoney($maxAmount);
      if ((float ) $minAmount > (float ) $maxAmount) {
        $errors['min_amount'] = ts('Minimum Amount should be less than Maximum Amount');
      }
    }
    if (isset($fields['is_monetary'])) {
      if (isset($fields['is_pay_later'])) {
        if (empty($fields['pay_later_text'])) {
          $errors['pay_later_text'] = ts('Please enter the text for the \'pay later\' checkbox displayed on the contribution form.');
        }
        if (empty($fields['pay_later_receipt'])) {
          $errors['pay_later_receipt'] = ts('Please enter the instructions to be sent to the contributor when they choose to \'pay later\'.');
        }
      }
      if (empty($fields['is_pay_later']) && empty($fields['payment_processor'])) {
        $errors['payment_processor'] = ts('A payment processor must be selected for this contribution page or must be configured to give users the option to pay later.');
        $errors['is_pay_later'] = ts('A payment processor must be selected for this contribution page or must be configured to give users the option to pay later.');
      }
    }

    //as for separate membership payment we has to have
    //contribution amount section enabled, hence to disable it need to
    //check if separate membership payment enabled,
    //if so disable first separate membership payment option
    //then disable contribution amount section. CRM-3801,


    $membershipBlock = new CRM_Member_DAO_MembershipBlock();
    $membershipBlock->entity_table = 'civicrm_contribution_page';
    $membershipBlock->entity_id = $self->_id;
    $membershipBlock->is_active = 1;
    $hasMembershipBlk = FALSE;
    if ($membershipBlock->find(TRUE)) {
      $hasMembershipBlk = TRUE;
      if ($membershipBlock->is_separate_payment && !$fields['amount_block_is_active']) {
        $errors['amount_block_is_active'] = ts('To disable Contribution Amounts section you need to first disable Separate Membership Payment option from Membership Settings.');
      }
    }

    // don't allow price set w/ membership signup, CRM-5095
    if ($priceSetId = CRM_Utils_Array::value('price_set_id', $fields)) {
      // don't allow price set w/ membership.
      if ($hasMembershipBlk) {
        $errors['price_set_id'] = ts('You cannot enable both Price Set and Membership Signup on the same online contribution page.');
      }
      $params = ['id' => $self->_id];
      CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_ContributionPage', $params, $values, ['is_active']);

      if($values['is_active'] & CRM_Contribute_BAO_ContributionPage::IS_SPECIAL) {
        $errors['price_set_id'] = ts("Cause you use special style. You can't use price set mode.");
      }
    }
    else {
      if (isset($fields['is_monetary'])) {
        if (isset($fields['is_recur'])) {
          if (empty($fields['recur_frequency_unit'])) {
            $errors['recur_frequency_unit'] = ts('At least one recurring frequency option needs to be checked.');
          }
        }
      }

      // validation for pledge fields.
      if (CRM_Utils_array::value('is_pledge_active', $fields)) {
        if (empty($fields['pledge_frequency_unit'])) {
          $errors['pledge_frequency_unit'] = ts('At least one pledge frequency option needs to be checked.');
        }
        if (CRM_Utils_array::value('is_recur', $fields)) {
          $errors['is_recur'] = ts('You cannot enable both Recurring Contributions AND Pledges on the same online contribution page.');
        }
      }

      // If Contribution amount section is enabled, then
      // Allow other amounts must be enabeld OR the Fixed Contribution
      // Contribution options must contain at least one set of values.
      if (CRM_Utils_Array::value('amount_block_is_active', $fields)) {
        if (!CRM_Utils_Array::value('is_allow_other_amount', $fields) &&
          !$priceSetId
        ) {
          //get the values of amount block
          $values = CRM_Utils_Array::value('value', $fields);
          $isSetRow = FALSE;
          for ($i = 1; $i < self::NUM_OPTION; $i++) {
            if ((isset($values[$i]) && (strlen(trim($values[$i])) > 0))) {
              $isSetRow = TRUE;
            }
          }
          if (!$isSetRow) {
            $errors['amount_block_is_active'] = ts('If you want to enable the \'Contribution Amounts section\', you need to either \'Allow Other Amounts\' and/or enter at least one row in the \'Fixed Contribution Amounts\' table.');
          }
        }
      }
    }

    return $errors;
  }

  /**
   * Process the form
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    // check for price set.
    $priceSetID = CRM_Utils_Array::value('price_set_id', $params);

    // get required fields.
    $fields = ['id' => $this->_id,
      'is_recur' => FALSE,
      'min_amount' => "null",
      'max_amount' => "null",
      'is_monetary' => FALSE,
      'is_pay_later' => FALSE,
      'is_recur_interval' => FALSE,
      'recur_frequency_unit' => "null",
      'default_amount_id' => "null",
      'is_allow_other_amount' => FALSE,
      'amount_block_is_active' => FALSE,
    ];
    $resetFields = [];
    if ($priceSetID) {
      $resetFields = ['min_amount', 'max_amount', 'is_allow_other_amount'];
    }

    if (!CRM_Utils_Array::value('is_recur', $params)) {
      $resetFields = array_merge($resetFields, ['is_recur_interval', 'recur_frequency_unit']);
    }

    foreach ($fields as $field => $defaultVal) {
      $val = CRM_Utils_Array::value($field, $params, $defaultVal);
      if (in_array($field, $resetFields)) {
        $val = $defaultVal;
      }

      if (in_array($field, ['min_amount', 'max_amount'])) {
        $val = CRM_Utils_Rule::cleanMoney($val);
      }

      $params[$field] = $val;
    }

    if ($params['is_recur']) {
      if ($params['is_recur_only']) {
        $params['is_recur'] = 2;
      }
      $params['recur_frequency_unit'] = CRM_Utils_Array::implode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR,
        array_keys($params['recur_frequency_unit'])
      );
      $params['is_recur_interval'] = CRM_Utils_Array::value('is_recur_interval', $params, FALSE);

      $params['installments_option'] = CRM_Utils_Array::value('show_installments_option', $params, '1');
    }

    if (CRM_Utils_Array::arrayKeyExists('payment_processor', $params) && !CRM_Utils_System::isNull($params['payment_processor'])) {
      $params['payment_processor'] = CRM_Utils_Array::implode(CRM_Core_DAO::VALUE_SEPARATOR, array_keys($params['payment_processor']));
    }
    else {
      $params['payment_processor'] = 'null';
    }

    // Refs #23510, clear pay_later_receipt if is_pay_later doesn't be checked.
    if (empty($params['is_pay_later'])) {
      $params['pay_later_receipt'] = 'null';
    }


    $contributionPage = CRM_Contribute_BAO_ContributionPage::create($params);
    $contributionPageID = $contributionPage->id;

    // prepare for data cleanup.
    $deleteAmountBlk = $deletePledgeBlk = $deletePriceSet = FALSE;
    if ($this->_priceSetID) {
      $deletePriceSet = TRUE;
    }
    if ($this->_pledgeBlockID) {
      $deletePledgeBlk = TRUE;
    }
    if (!empty($this->_amountBlock)) {
      $deleteAmountBlk = TRUE;
    }

    if ($contributionPageID) {




      if (CRM_Utils_Array::value('amount_block_is_active', $params)) {
        // handle price set.
        if ($priceSetID) {
          // add/update price set.
          $deletePriceSet = FALSE;
          CRM_Price_BAO_Set::addTo('civicrm_contribution_page', $contributionPageID, $priceSetID);
        }
        else {

          // process contribution amount block
          $deleteAmountBlk = FALSE;

          $labels = CRM_Utils_Array::value('label', $params);
          $values = CRM_Utils_Array::value('value', $params);
          $grouping = CRM_Utils_Array::value('grouping', $params);
          $default = CRM_Utils_Array::value('default', $params);
          $filter = CRM_Utils_Array::value('filter', $params);

          $options = [];
          for ($i = 1; $i < self::NUM_OPTION; $i++) {
            if (isset($values[$i]) &&
              (strlen(trim($values[$i])) > 0)
            ) {
              $options[] = ['label' => trim($labels[$i]),
                'value' => CRM_Utils_Rule::cleanMoney(trim($values[$i])),
                'weight' => $i,
                'grouping' => trim($grouping[$i]),
                'is_active' => 1,
                'is_default' => $default == $i,
                'filter' => $filter[$i] == 1,
              ];
            }
          }
          CRM_Core_OptionGroup::createAssoc("civicrm_contribution_page.amount.{$contributionPageID}", $options, $params['default_amount_id']);
          if ($params['default_amount_id']) {
            CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_ContributionPage',
              $contributionPageID, 'default_amount_id',
              $params['default_amount_id']
            );
          }

          if (CRM_Utils_Array::value('is_pledge_active', $params)) {
            $deletePledgeBlk = FALSE;
            $pledgeBlockParams = ['entity_id' => $contributionPageID,
              'entity_table' => ts('civicrm_contribution_page'),
            ];
            if ($this->_pledgeBlockID) {
              $pledgeBlockParams['id'] = $this->_pledgeBlockID;
            }
            $pledgeBlock = ['pledge_frequency_unit', 'max_reminders',
              'initial_reminder_day', 'additional_reminder_day',
            ];
            foreach ($pledgeBlock as $key) {
              $pledgeBlockParams[$key] = CRM_Utils_Array::value($key, $params);
            }
            $pledgeBlockParams['is_pledge_interval'] = CRM_Utils_Array::value('is_pledge_interval',
              $params, FALSE
            );
            // create pledge block.

            CRM_Pledge_BAO_PledgeBlock::create($pledgeBlockParams);
          }
        }
      }

      // delete pledge block.
      if ($deletePledgeBlk) {
        CRM_Pledge_BAO_PledgeBlock::deletePledgeBlock($this->_pledgeBlockID);
      }

      // delete previous price set.
      if ($deletePriceSet) {
        CRM_Price_BAO_Set::removeFrom('civicrm_contribution_page', $contributionPageID);
      }

      // delete amount block.
      if ($deleteAmountBlk) {
        CRM_Core_OptionGroup::deleteAssoc("civicrm_contribution_page.amount.{$contributionPageID}");
      }
    }
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Amounts');
  }
}

