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
 * This class generates form components for Tell A Friend Form For End User
 *
 */
class CRM_Friend_Form extends CRM_Core_Form {

  public $_pcpBlockId;
  public $_mailLimit;
  /**
   * Constants for number of friend contacts
   */
  CONST NUM_OPTION = 3;

  /**
   * the id of the entity that we are proceessing
   *
   * @var int
   * @protected
   */
  protected $_entityId;

  /**
   * the table name of the entity that we are proceessing
   *
   * @var string
   * @protected
   */
  protected $_entityTable;

  /**
   * the contact ID
   *
   * @var int
   * @protected
   */
  protected $_contactID;

  public function preProcess() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);
    $this->_entityId = CRM_Utils_Request::retrieve('eid', 'Positive', $this, TRUE);

    $page = CRM_Utils_Request::retrieve('page', 'String', $this, TRUE);
    if ($page == 'contribution') {
      $this->_entityTable = 'civicrm_contribution_page';
      $this->_title = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $this->_entityId, 'title');
    }
    elseif ($page == 'event') {
      $this->_entityTable = 'civicrm_event';
      $this->_title = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->_entityId, 'title');
    }
    elseif ($page == 'pcp') {
      $this->_pcpBlockId = CRM_Utils_Request::retrieve('blockId', 'Positive', $this, TRUE);

      CRM_Core_DAO::commonRetrieveAll('CRM_Contribute_DAO_PCPBlock', 'id',
        $this->_pcpBlockId, $pcpBlock, ['is_tellfriend_enabled', 'tellfriend_limit']
      );

      if (!CRM_Utils_Array::value('is_tellfriend_enabled', $pcpBlock[$this->_pcpBlockId])) {
         return CRM_Core_Error::statusBounce(ts('Tell Friend is disable for this Personal Campaign Page'));
      }

      $this->_mailLimit = $pcpBlock[$this->_pcpBlockId]['tellfriend_limit'];
      $this->_entityTable = 'civicrm_pcp';
      $this->_title = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_PCP', $this->_entityId, 'title');
      $this->assign('context', 'pcp');
      $this->assign('pcpTitle', $this->_title);
    }
    else {
      CRM_Core_Error::fatal(ts('page argument missing or invalid'));
    }

    $session = CRM_Core_Session::singleton();
    $this->_contactID = $session->get('userID');
    if (!$this->_contactID) {
      $this->_contactID = $session->get('transaction.userID');
    }

    if (!$this->_contactID) {
       return CRM_Core_Error::statusBounce(ts('Could not get the contact ID'));
    }

    // we do not want to display recently viewed items, so turn off
    $this->assign('displayRecent', FALSE);
  }

  /**
   * This function sets the default values for the form.
   *
   * @access public
   *
   * @return None
   */
  public function setDefaultValues() {
    $defaults = [];

    $defaults['entity_id'] = $this->_entityId;
    $defaults['entity_table'] = $this->_entityTable;

    CRM_Friend_BAO_Friend::getValues($defaults);
    CRM_Utils_System::setTitle(CRM_Utils_Array::value('title', $defaults));

    $this->assign('title', CRM_Utils_Array::value('title', $defaults));
    $this->assign('intro', CRM_Utils_Array::value('intro', $defaults));
    $this->assign('message', CRM_Utils_Array::value('suggested_message', $defaults));


    list($fromName, $fromEmail) = CRM_Contact_BAO_Contact::getContactDetails($this->_contactID);

    $defaults['from_name'] = $fromName;
    $defaults['from_email'] = $fromEmail;

    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    // Details of User
    $name = &$this->add('text',
      'from_name',
      ts('From'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'first_name')
    );
    $name->freeze();

    $email = &$this->add('text',
      'from_email',
      ts('Your Email'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_Email', 'email'),
      TRUE
    );
    $email->freeze();

    $this->add('textarea', 'suggested_message', ts('Your Message'), CRM_Core_DAO::getAttribute('CRM_Friend_DAO_Friend', 'suggested_message'), TRUE);

    $friend = [];
    $mailLimit = self::NUM_OPTION;
    if ($this->_entityTable == 'civicrm_pcp') {
      $mailLimit = $this->_mailLimit;
    }
    $this->assign('mailLimit', $mailLimit + 1);
    for ($i = 1; $i <= $mailLimit; $i++) {
      $this->add('text', "friend[$i][first_name]", ts("Friend's First Name"));
      $this->add('text', "friend[$i][last_name]", ts("Friend's Last Name"));
      $this->add('text', "friend[$i][email]", ts("Friend's Email"));
      $this->addRule("friend[$i][email]", ts('The format of this email address is not valid.'), 'email');
    }

    $this->addButtons([
        ['type' => 'submit',
          'name' => ts('Send Your Message'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );

    $this->addFormRule(['CRM_Friend_Form', 'formRule']);
  }

  /**
   * Function for validation
   *
   * @param array $params (ref.) an assoc array of name/value pairs
   *
   * @return mixed true or array of errors
   * @access public
   * @static
   */
  static function formRule($fields) {

    $errors = [];

    $valid = FALSE;
    foreach ($fields['friend'] as $key => $val) {
      if (trim($val['first_name']) || trim($val['last_name']) || trim($val['email'])) {
        $valid = TRUE;

        if (!trim($val['first_name'])) {
          $errors["friend[{$key}][first_name]"] = ts('Please enter your friend\'s first name.');
        }

        if (!trim($val['last_name'])) {
          $errors["friend[{$key}][last_name]"] = ts('Please enter your friend\'s last name.');
        }

        if (!trim($val['email'])) {
          $errors["friend[{$key}][email]"] = ts('Please enter your friend\'s email address.');
        }
      }
    }

    if (!$valid) {
      $errors['friend[1][first_name]'] = ts("Please enter at least one friend's information, or click Cancel if you don't want to send emails at this time.");
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    // get the submitted form values.
    $formValues = $this->controller->exportValues($this->_name);

    $formValues['entity_id'] = $this->_entityId;
    $formValues['entity_table'] = $this->_entityTable;
    $formValues['source_contact_id'] = $this->_contactID;
    $formValues['is_test'] = $this->_action ? 1 : 0;
    $formValues['title'] = $this->_title;

    CRM_Friend_BAO_Friend::create($formValues);

    $this->assign('status', 'thankyou');
    $defaults = [];

    $defaults['entity_id'] = $this->_entityId;
    $defaults['entity_table'] = $this->_entityTable;

    CRM_Friend_BAO_Friend::getValues($defaults);
    if ($this->_entityTable == 'civicrm_pcp') {
      $defaults['thankyou_text'] = $defaults['thankyou_title'] = ts('Thanks for your Support');
      $defaults['thankyou_text'] = ts('Thanks for supporting this campaign by spreading the word to your friends.');
    }
    elseif ($this->_entityTable == 'civicrm_contribution_page') {
      // If this is tell a friend after contributing, give donor link to create their own fundraising page

      if ($linkText = CRM_Contribute_BAO_PCP::getPcpBlockStatus($defaults['entity_id'])) {

        $linkTextUrl = CRM_Utils_System::url('civicrm/contribute/campaign',
          "action=add&reset=1&pageId={$defaults['entity_id']}",
          FALSE, NULL, TRUE,
          TRUE
        );
        $this->assign('linkTextUrl', $linkTextUrl);
        $this->assign('linkText', $linkText);
      }
    }

    CRM_Utils_System::setTitle($defaults['thankyou_title']);
    $this->assign('thankYouText', $defaults['thankyou_text']);
  }
}

