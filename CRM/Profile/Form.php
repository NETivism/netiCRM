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
 * This class generates form components for custom data
 *
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 *
 */
class CRM_Profile_Form extends CRM_Core_Form {
  public $_duplicateButtonName;
  public $_disallowed;
  public $_fieldset;
  public $_mail;
  CONST MODE_REGISTER = 1, MODE_SEARCH = 2, MODE_CREATE = 4, MODE_EDIT = 8;

  protected $_mode;

  protected $_skipPermission = FALSE;

  /**
   * The contact id that we are editing
   *
   * @var int
   */
  protected $_id;

  /**
   * The group id that we are editing
   *
   * @var int
   */
  protected $_gid;

  /**
   * @var array details of the UFGroup used on this page
   */
  protected $_ufGroup = ['name' => 'unknown'];
 

  /**
   * The group id that we are passing in url
   *
   * @var int
   */
  public $_grid;

  /**
   * The title of the category we are editing
   *
   * @var string
   */
  protected $_title;

  /**
   * the fields needed to build this form
   *
   * @var array
   */
  public $_fields;

  /**
   * to store contact details
   *
   * @var array
   */
  protected $_contact;

  /**
   * to store group_id of the group which is to be assigned to the contact
   *
   * @var int
   */
  protected $_addToGroupID;

  /**
   * Do we allow updates of the contact
   *
   * @var int
   */
  public $_isUpdateDupe = 0;

  public $_isAddCaptcha = FALSE;

  protected $_isPermissionedChecksum = FALSE;

  /**
   * THe context from which we came from, allows us to go there if redirect not set
   *
   * @var string
   */
  protected $_context;

  /**
   * THe contact type for registration case
   *
   * @var string
   */
  protected $_ctype = NULL;

  protected $_defaults = NULL;

  /**
   * Store profile ids if multiple profile ids are passed using comma separated.
   * Currently lets implement this functionality only for dialog mode
   */
  protected $_profileIds = [];

  /**
   * pre processing work done here.
   *
   * gets session variables for table name, id of entity in table, type of entity and stores them.
   *
   * @param
   *
   * @return void
   *
   * @access public
   */
  function preProcess() {



    $this->_id = $this->get('id');
    $this->_gid = $this->get('gid');
    $this->_profileIds = $this->get('profileIds');
    $this->_grid = CRM_Utils_Request::retrieve('grid', 'Integer', $this);
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);

    $this->_duplicateButtonName = $this->getButtonName('upload', 'duplicate');

    $gids = explode(',', CRM_Utils_Request::retrieve('gid', 'String', CRM_Core_DAO::$_nullObject, FALSE, 0, 'GET'));

    if ((count($gids) > 1) && !$this->_profileIds && empty($this->_profileIds)) {
      if (!empty($gids)) {
        foreach ($gids as $pfId) {
          $this->_profileIds[] = CRM_Utils_Type::escape($pfId, 'Positive');
        }
      }

      // check if we are rendering mixed profiles
      if (CRM_Core_BAO_UFGroup::checkForMixProfiles($this->_profileIds)) {
         return CRM_Core_Error::statusBounce(ts('You cannot combine profiles of multiple types.'));
      }

      // for now consider 1'st profile as primary profile and validate it
      // i.e check for profile type etc.
      // FIX ME: validations for other than primary
      $this->_gid = $this->_profileIds[0];
      $this->set('gid', $this->_gid);
      $this->set('profileIds', $this->_profileIds);
    }

    if (!$this->_gid) {
      $this->_gid = CRM_Utils_Request::retrieve('gid', 'Positive', $this, FALSE, 0, 'REQUEST');
    }

    //get values for captch and dupe update.
    if ($this->_gid) {
      $dao = new CRM_Core_DAO_UFGroup();
      $dao->id = $this->_gid;
      if ($dao->find(TRUE)) {
        $this->_isUpdateDupe = $dao->is_update_dupe;
        $this->_isAddCaptcha = $dao->add_captcha;
        $this->_ufGroup = (array) $dao;

        // restrict permission when profile is reserved
        if ($dao->is_reserved && !CRM_Core_Permission::check('access CiviCRM')) {
          $sql = "SELECT id FROM civicrm_uf_join WHERE uf_group_id = %1 and module = %2";
          $params = [
            1 => [$dao->id, 'Integer'],
            2 => ["User Account", 'String'],
          ];
          $useForIsUserAccount = CRM_Core_DAO::singleValueQuery($sql, $params);
          if (empty($useForIsUserAccount)) {
            return CRM_Core_Error::statusBounce(ts('The requested Profile (gid=%1) is disabled OR it is not configured to be used for \'Profile\' listings in its Settings OR there is no Profile with that ID OR you do not have permission to access this profile. Please contact the site administrator if you need assistance.', [
              1 => $this->_gid
            ]));
          }
        }
      }
      $dao->free();
    }

    if (empty($this->_profileIds)) {
      $gids = $this->_gid;
    }
    else {
      $gids = $this->_profileIds;
    }

    // if we dont have a gid use the default, else just use that specific gid
    if (($this->_mode == self::MODE_REGISTER || $this->_mode == self::MODE_CREATE) && !$this->_gid) {
      $this->_ctype = CRM_Utils_Request::retrieve('ctype', 'String', $this, FALSE, 'Individual', 'REQUEST');
      $this->_fields = CRM_Core_BAO_UFGroup::getRegistrationFields($this->_action, $this->_mode, $this->_ctype);
    }
    elseif ($this->_mode == self::MODE_SEARCH) {
      $this->_fields = CRM_Core_BAO_UFGroup::getListingFields($this->_action,
        CRM_Core_BAO_UFGroup::PUBLIC_VISIBILITY | CRM_Core_BAO_UFGroup::LISTINGS_VISIBILITY,
        FALSE,
        $gids,
        TRUE, NULL,
        $this->_skipPermission,
        CRM_Core_Permission::SEARCH
      );
    }
    else {
      $this->_fields = CRM_Core_BAO_UFGroup::getFields($gids, FALSE, NULL,
        NULL, NULL,
        FALSE, NULL,
        $this->_skipPermission,
        NULL,
        ($this->_action == CRM_Core_Action::ADD) ? CRM_Core_Permission::CREATE : CRM_Core_Permission::EDIT
      );

      ///is profile double-opt process configurablem, key
      ///should be present in civicrm.settting.php file
      $config = CRM_Core_Config::singleton();
      if ($config->profileDoubleOptIn &&
        CRM_Utils_Array::value('group', $this->_fields)
      ) {
        $emailField = FALSE;
        foreach ($this->_fields as $name => $values) {
          if (substr($name, 0, 6) == 'email-') {
            $emailField = TRUE;
          }
        }

        if (!$emailField) {
          $session = CRM_Core_Session::singleton();
          $status = ts("Email field should be included in profile if you want to use Group(s) when Profile double-opt in process is enabled.");
          $session->setStatus($status);
        }
      }
    }

    if (!is_array($this->_fields)) {
      $session = CRM_Core_Session::singleton();
      CRM_Core_Session::setStatus(ts('This feature is not currently available.'));
      return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm', 'reset=1'));
    }

    //lets have single status message, CRM-4363
    $disallowed = FALSE;
    $statusMessage = NULL;

    //we should not allow component and mix profiles in search mode
    if ($this->_mode != self::MODE_REGISTER) {
      //check for mix profile fields (eg:  individual + other contact type)
      if (CRM_Core_BAO_UFField::checkProfileType($this->_gid)) {
        $statusMessage = ts('Profile search, view and edit are not supported for Profiles which include fields for more than one record type.');
      }

      $profileType = CRM_Core_BAO_UFField::getProfileType($this->_gid);


      if ($this->_id) {
        list($contactType, $contactSubType) = CRM_Contact_BAO_Contact::getContactTypes($this->_id);

        $profileSubType = FALSE;
        if (CRM_Contact_BAO_ContactType::isaSubType($profileType)) {
          $profileSubType = $profileType;
          $profileType = CRM_Contact_BAO_ContactType::getBasicType($profileType);
        }

        if (($profileType != 'Contact') &&
          (($profileSubType && $contactSubType && ($profileSubType != $contactSubType)) ||
            ($profileType != $contactType)
          )
        ) {
          $orgId = CRM_Contact_BAO_Relationship::currentPermittedOrganization($this->_id);
          $this->set('orgId', $orgId);
          if ($orgId && $profileType == 'Organization') {
            $this->_id = $orgId;
          }
          else{
            $disallowed = TRUE;
            if (!$statusMessage) {
              $statusMessage = ts("This profile is configured for contact type '%1'. It cannot be used to edit contacts of other types.", [1 => $profileSubType ? $profileSubType : $profileType]);
            }
          }
        }
      }

      if (in_array($profileType, ["Membership", "Participant", "Contribution"])) {
        $disallowed = TRUE;
        if (!$statusMessage) {
          $statusMessage = ts('Profile is not configured for the selected action.');
        }
      }
    }

    // lets have sigle status message,
    $this->assign('statusMessage', $statusMessage);
    $this->_disallowed = $disallowed;

    if ($this->_mode != self::MODE_SEARCH) {
      CRM_Core_BAO_UFGroup::setRegisterDefaults($this->_fields, $defaults);
      $this->setDefaults($defaults);
    }

    $this->setDefaultsValues();
    $this->track(1);
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultsValues() {
    $this->_defaults = [];
    if ($this->_id) {
      CRM_Core_BAO_UFGroup::setProfileDefaults($this->_id, $this->_fields, $this->_defaults, TRUE);
    }
    else{
      if (isset($this->_fields['group'])) {
        CRM_Contact_BAO_Group::publicDefaultGroups($this->_defaults);
      }
    }

    //set custom field defaults

    foreach ($this->_fields as $name => $field) {
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
        $htmlType = $field['html_type'];

        if (!isset($this->_defaults[$name]) || $htmlType == 'File') {
          CRM_Core_BAO_CustomField::setProfileDefaults($customFieldID,
            $name,
            $this->_defaults,
            $this->_id,
            $this->_mode
          );
        }

        if ($htmlType == 'File') {
          $url = CRM_Core_BAO_CustomField::getFileURL($this->_id, $customFieldID);

          if ($url) {
            $customFiles[$field['name']]['displayURL'] = $url['file_url'];

            $deleteExtra = ts("Are you sure you want to delete attached file.");
            $fileId = $url['file_id'];
            if (empty($field['is_view'])) {
              $deleteURL = CRM_Utils_System::url('civicrm/file',
                "reset=1&id={$fileId}&eid=$this->_id&fid={$customFieldID}&action=delete&stay=1"
              );
              $customFiles[$field['name']]['deleteURL'] = "<a href=\"{$deleteURL}\" onclick = \"if (confirm( ' $deleteExtra ' )) this.href+='&confirmed=1'; else return false;\">".ts("Delete Attached File")."</a>"; 
            }
          }
        }
      }
    }
    if (isset($customFiles)) {
      $this->assign('customFiles', $customFiles);
    }

    if (CRM_Utils_Array::value('image_URL', $this->_defaults)) {
      $image = CRM_Utils_Image::getImageVars($this->_defaults['image_URL']);
      $this->assign('contactImage', $image);
    }

    $this->setDefaults($this->_defaults);
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_disallowed) {
      return FALSE;
    }
    $sBlocks = [];
    $hBlocks = [];
    $config = CRM_Core_Config::singleton();

    $this->assign('id', $this->_id);
    $this->assign('mode', $this->_mode);
    $this->assign('action', $this->_action);
    $this->assign_by_ref('fields', $this->_fields);
    $this->assign('fieldset', (isset($this->_fieldset)) ? $this->_fieldset : "");

    // do we need inactive options ?
    if ($this->_action & CRM_Core_Action::VIEW) {
      $inactiveNeeded = TRUE;
    }
    else {
      $inactiveNeeded = FALSE;
    }

    $session = CRM_Core_Session::singleton();

    // should we restrict what we display
    $admin = TRUE;
    if ($this->_mode == self::MODE_EDIT) {
      $admin = FALSE;
      // show all fields that are visibile:
      // if we are a admin OR the same user OR acl-user with access to the profile
      // or we have checksum access to this contact (i.e. the user without a login) - CRM-5909

      if (CRM_Core_Permission::check('administer users') ||
        $this->_id == $session->get('userID') ||
        $this->_id == $this->get('orgId') ||
        $this->_isPermissionedChecksum ||
        in_array($this->_gid,
          CRM_ACL_API::group(CRM_Core_Permission::EDIT,
            NULL,
            'civicrm_uf_group',
            CRM_Core_PseudoConstant::ufGroup()
          )
        )
      ) {
        $admin = TRUE;
      }
    }

    $userID = $session->get('userID');
    // if false, user is not logged-in.
    $anonUser = FALSE;
    if (!$userID) {

      $defaultLocationType = &CRM_Core_BAO_LocationType::getDefault();
      $primaryLocationType = $defaultLocationType->id;
      $anonUser = TRUE;
      $this->assign('anonUser', TRUE);
    }

    $addCaptcha = [];
    $emailPresent = FALSE;

    // cache the state country fields. based on the results, we could use our javascript solution
    // in create or register mode
    $stateCountryMap = [];

    // add the form elements
    foreach ($this->_fields as $name => $field) {
      // make sure that there is enough permission to expose this field
      if (!$admin && $field['visibility'] == 'User and User Admin Only') {
        unset($this->_fields[$name]);
        continue;
      }

      // since the CMS manages the email field, suppress the email display if in
      // register mode which occur within the CMS form
      if ($this->_mode == self::MODE_REGISTER &&
        substr($name, 0, 5) == 'email'
      ) {
        unset($this->_fields[$name]);
        continue;
      }

      list($prefixName, $index) = CRM_Utils_System::explode('-', $name, 2);
      if ($prefixName == 'state_province' || $prefixName == 'country') {
        if (!CRM_Utils_Array::arrayKeyExists($index, $stateCountryMap)) {
          $stateCountryMap[$index] = [];
        }
        $stateCountryMap[$index][$prefixName] = $name;
      }

      CRM_Core_BAO_UFGroup::buildProfile($this, $field, $this->_mode);

      if ($field['add_to_group_id']) {
        $addToGroupId = $field['add_to_group_id'];
      }

      //build array for captcha
      if ($field['add_captcha']) {
        $addCaptcha[$field['group_id']] = $field['add_captcha'];
      }

      if (($name == 'email-Primary') || ($name == 'email-' . isset($primaryLocationType) ? $primaryLocationType : "")) {
        $emailPresent = TRUE;
        $this->_mail = $name;
      }
    }

    // add captcha only for create mode.
    if ($this->_mode == self::MODE_CREATE) {
      if (!$this->_isAddCaptcha && !empty($addCaptcha)) {
        $this->_isAddCaptcha = TRUE;
      }
      if ($this->_gid) {
        $dao = new CRM_Core_DAO_UFGroup();
        $dao->id = $this->_gid;
        $this->addSelectByOption('add_captcha', 'is_update_dupe');
        if ($dao->find(TRUE)) {
          if ($dao->add_captcha) {
            $setCaptcha = TRUE;
          }
          if ($dao->is_update_dupe) {
            $this->_isUpdateDupe = $dao->is_update_dupe;
          }
        }
      }
    }
    else {
      $this->_isAddCaptcha = FALSE;
    }

    //finally add captcha to form.
    if ($this->_isAddCaptcha) {

      $captcha = &CRM_Utils_ReCAPTCHA::singleton();
      $captcha->add($this);
    }
    $this->assign("isCaptcha", $this->_isAddCaptcha);

    if ($this->_mode != self::MODE_SEARCH) {
      if (isset($addToGroupId)) {
        $this->add('hidden', "add_to_group", $addToGroupId);
        $this->_addToGroupID = $addToGroupId;
      }
    }

    // also do state country js

    CRM_Core_BAO_Address::addStateCountryMap($stateCountryMap, $this->_defaults);

    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, NULL);
    if ($this->_mode == self::MODE_CREATE) {

      CRM_Core_BAO_CMSUser::buildForm($this, $this->_gid, $emailPresent, $action);
    }
    else {
      $this->assign('showCMS', FALSE);
    }

    $this->assign('groupId', $this->_gid);

    // now fix all state country selectors

    CRM_Core_BAO_Address::fixAllStateSelects($this, $this->_defaults);

    // if view mode pls freeze it with the done button.
    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
    }

    if ($this->_context == 'dialog') {
      $this->addElement('submit',
        $this->_duplicateButtonName,
        ts('Save Matching Contact')
      );
    }
  }

  /**
   * Function to validate profile and provided activity Id
   *
   * @params Integer $activityId Activity Id
   * @params Integer $gid        Profile Id
   *
   * @return Array   $errors     Errors ( if any ).
   */
  static function validateContactActivityProfile($activityId, $contactId, $gid) {
    $errors = [];
    if (!$activityId) {
      $errors[] = 'Profile is using one or more activity fields, and is missing the activity Id (aid) in the URL.';
      return $errors;
    }

    $activityDetails = [];
    $activityParams = ['id' => $activityId];
    CRM_Activity_BAO_Activity::retrieve($activityParams, $activityDetails);

    if (empty($activityDetails)) {
      $errors[] = 'Invalid Activity Id (aid).';
      return $errors;
    }

    $profileActivityTypes = CRM_Core_BAO_UFGroup::groupTypeValues($gid, 'Activity');

    if ((CRM_Utils_Array::value('Activity', $profileActivityTypes) &&
        !in_array($activityDetails['activity_type_id'], $profileActivityTypes['Activity'])
      ) ||
      (!in_array($contactId, $activityDetails['assignee_contact']) &&
        !in_array($contactId, $activityDetails['target_contact'])
      )
    ) {
      $errors[] = 'This activity cannot be edited or viewed via this profile.';
    }

    return $errors;
  }

  /**
   * global form rule
   *
   * @param array  $fields the input form values
   * @param array  $files  the uploaded files if any
   * @param object $form   the form object
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $form) {
    $errors = [];
    // if no values, return
    if (empty($fields)) {
      return TRUE;
    }

    $cid = $register = NULL;

    // hack we use a -1 in options to indicate that its registration
    if ($form->_id) {
      $cid = $form->_id;
      $form->_isUpdateDupe = 1;
    }

    if ($form->_mode == CRM_Profile_Form::MODE_REGISTER) {
      $register = TRUE;
    }

    $form->addFieldRequiredRule($errors, $fields ,$files);

    // dont check for duplicates during registration validation: CRM-375
    if (!$register && !CRM_Utils_Array::value('_qf_Edit_upload_duplicate', $fields)) {
      // fix for CRM-3240
      if (CRM_Utils_Array::value('email-Primary', $fields)) {
        $fields['email'] = CRM_Utils_Array::value('email-Primary', $fields);
      }

      // fix for CRM-6141
      if (CRM_Utils_Array::value('phone-Primary-1', $fields) &&
        !CRM_Utils_Array::value('phone-Primary', $fields)
      ) {
        $fields['phone-Primary'] = $fields['phone-Primary-1'];
      }

      $session = CRM_Core_Session::singleton();

      $ctype = CRM_Core_BAO_UFGroup::getContactType($form->_gid);
      // If all profile fields is of Contact Type then consider
      // profile is of Individual type(default).
      if (!$ctype) {
        $ctype = 'Individual';
      }

      $dedupeParams = CRM_Dedupe_Finder::formatParams($fields, $ctype);
      if ($form->_mode == CRM_Profile_Form::MODE_CREATE) {
        // fix for CRM-2888
        $exceptions = [];
      }
      else {
        // for edit mode we need to allow our own record to be a dupe match!
        $exceptions = [$session->get('userID')];
      }

      // for dialog mode we should always use fuzzy rule.
      $ruleType = 'Strict';
      if ($form->_context == 'dialog') {
        $ruleType = 'Fuzzy';
      }

      $dedupeParams['check_permission'] = FALSE;
      $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams,
        $ctype,
        $ruleType,
        $exceptions
      );
      if ($ids) {
        if ($form->_isUpdateDupe == 2) {
          CRM_Core_Session::setStatus(ts('Note: this contact may be a duplicate of an existing record.'));
        }
        elseif ($form->_isUpdateDupe == 1) {
          if (!$form->_id) {
            $form->_id = $ids[0];
          }
        }
        else {
          if ($form->_context == 'dialog') {


            $contactLinks = CRM_Contact_BAO_Contact_Utils::formatContactIDSToLinks($ids, TRUE, TRUE);

            $duplicateContactsLinks = '<div class="matching-contacts-found">';
            $duplicateContactsLinks .= ts('One matching contact was found. ', ['count' => count($contactLinks['rows']), 'plural' => '%count matching contacts were found.<br />']);
            if ($contactLinks['msg'] == 'view') {
              $duplicateContactsLinks .= ts('You can View the existing contact.', ['count' => count($contactLinks['rows']), 'plural' => 'You can View the existing contacts.']);
            }
            else {
              $duplicateContactsLinks .= ts('You can View or Edit the existing contact.', ['count' => count($contactLinks['rows']), 'plural' => 'You can View or Edit the existing contacts.']);
            }
            $duplicateContactsLinks .= '</div>';
            $duplicateContactsLinks .= '<table class="matching-contacts-actions">';
            $row = '';
            for ($i = 0; $i < sizeof($contactLinks['rows']); $i++) {
              $row .= '  <tr>	 ';
              $row .= '  	<td class="matching-contacts-name"> ';
              $row .= $contactLinks['rows'][$i]['display_name'];
              $row .= '  	</td>';
              $row .= '  	<td class="matching-contacts-email"> ';
              $row .= $contactLinks['rows'][$i]['primary_email'];
              $row .= '  	</td>';
              $row .= '  	<td class="action-items"> ';
              $row .= $contactLinks['rows'][$i]['view'] . ' ';
              $row .= $contactLinks['rows'][$i]['edit'];
              $row .= '  	</td>';
              $row .= '  </tr>	 ';
            }

            $duplicateContactsLinks .= $row . '</table>';
            $duplicateContactsLinks .= ts("If you're sure this record is not a duplicate, click the 'Save Matching Contact' button below.");

            $errors['_qf_default'] = $duplicateContactsLinks;


            // let smarty know that there are duplicates
            $template = CRM_Core_Smarty::singleton();
            $template->assign('isDuplicate', 1);
          }
          else {
            $errors['_qf_default'] = ts('A record already exists with the same information.');
          }
        }
      }
    }

    foreach ($fields as $key => $value) {
      list($fieldName, $locTypeId, $phoneTypeId) = CRM_Utils_System::explode('-', $key, 3);
      if ($fieldName == 'state_province' && $fields["country-{$locTypeId}"]) {
        // Validate Country - State list
        $countryId = $fields["country-{$locTypeId}"];
        $stateProvinceId = $value;

        if ($stateProvinceId && $countryId) {
          $stateProvinceDAO = new CRM_Core_DAO_StateProvince();
          $stateProvinceDAO->id = $stateProvinceId;
          $stateProvinceDAO->find(TRUE);

          if ($stateProvinceDAO->country_id != $countryId) {
            // country mismatch hence display error
            $stateProvinces = CRM_Core_PseudoConstant::stateProvince();
            $countries = &CRM_Core_PseudoConstant::country();
            $errors[$key] = "State/Province " . $stateProvinces[$stateProvinceId] . " is not part of " . $countries[$countryId] . ". It belongs to " . $countries[$stateProvinceDAO->country_id] . ".";
          }
        }
      }

      if ($fieldName == 'county' && $fields["state_province-{$locTypeId}"]) {
        // Validate County - State list
        $stateProvinceId = $fields["state_province-{$locTypeId}"];
        $countyId = $value;

        if ($countyId && $stateProvinceId) {
          $countyDAO = new CRM_Core_DAO_County();
          $countyDAO->id = $countyId;
          $countyDAO->find(TRUE);

          if ($countyDAO->state_province_id != $stateProvinceId) {
            // state province mismatch hence display error
            $stateProvinces = CRM_Core_PseudoConstant::stateProvince();
            $counties = &CRM_Core_PseudoConstant::county();
            $errors[$key] = "County " . $counties[$countyId] . " is not part of " . $stateProvinces[$stateProvinceId] . ". It belongs to " . $stateProvinces[$countyDAO->state_province_id] . ".";
          }
        }
      }
    }

    $elements = ['email_greeting' => 'email_greeting_custom',
      'postal_greeting' => 'postal_greeting_custom',
      'addressee' => 'addressee_custom',
    ];
    foreach ($elements as $greeting => $customizedGreeting) {
      if ($greetingType = CRM_Utils_Array::value($greeting, $fields)) {
        $customizedValue = CRM_Core_OptionGroup::getValue($greeting, 'Customized', 'name');
        if ($customizedValue == $greetingType &&
          !CRM_Utils_Array::value($customizedGreeting, $fields)
        ) {
          $errors[$customizedGreeting] = ts('Custom  %1 is a required field if %1 is of type Customized.',
            [1 => ucwords(str_replace('_', " ", $greeting))]
          );
        }
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the user submitted custom data values.
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    if (CRM_Utils_Array::value('image_URL', $params)) {
      CRM_Contact_BAO_Contact::processImageParams($params);
    }

    $greetingTypes = ['addressee' => 'addressee_id',
      'email_greeting' => 'email_greeting_id',
      'postal_greeting' => 'postal_greeting_id',
    ];
    if ($this->_id) {
      $contactDetails = CRM_Contact_BAO_Contact::getHierContactDetails($this->_id,
        $greetingTypes
      );
      $details = $contactDetails[0][$this->_id];
    }
    if (!(CRM_Utils_Array::value('addressee_id', $details) ||
        CRM_Utils_Array::value('email_greeting_id', $details) ||
        CRM_Utils_Array::value('postal_greeting_id', $details)
      )) {

      $profileType = CRM_Core_BAO_UFField::getProfileType($this->_gid);
      //Though Profile type is contact we need
      //Individual/Household/Organization for setting Greetings.
      if ($profileType == 'Contact') {
        $profileType = 'Individual';
        //if we editing Household/Organization.
        if ($this->_id) {
          $profileType = CRM_Contact_BAO_Contact::getContactType($this->_id);
        }
      }
      if (CRM_Contact_BAO_ContactType::isaSubType($profileType)) {
        $profileType = CRM_Contact_BAO_ContactType::getBasicType($profileType);
      }

      $contactTypeFilters = [1 => 'Individual', 2 => 'Household',
        3 => 'Organization',
      ];
      $filter = CRM_Utils_Array::key($profileType, $contactTypeFilters);
      if ($filter) {
        foreach ($greetingTypes as $key => $value) {
          if (!CRM_Utils_Array::arrayKeyExists($key, $params)) {
            $defaultGreetingTypeId = CRM_Core_OptionGroup::values($key, NULL,
              NULL, NULL,
              "AND is_default =1
                                                                               AND (filter = 
                                                                               {$filter} OR 
                                                                               filter = 0 )",
              'value'
            );

            $params[$key] = key($defaultGreetingTypeId);
          }
        }
      }
      if ($profileType == 'Organization') {
        unset($params['email_greeting'], $params['postal_greeting']);
      }
    }
    if ($this->_mode == self::MODE_REGISTER) {

      CRM_Core_BAO_Address::setOverwrite(FALSE);
    }
    if (!empty($params['log_data'])) {
      $params['log_data'] .= ' ('.ts('Profile').' - '.$this->_gid.')';
    }
    else{
      $params['log_data'] = ts('Profile').' - '.$this->_gid;
    }


    $transaction = new CRM_Core_Transaction();

    // first, trying to add contact from profile without group
    $submittedGroup = !empty($params['group']) ? $params['group'] : [];
    $fieldGroup = !empty($this->_fields['group']) ? $this->_fields['group'] : []; 
    unset($params['group']);
    unset($this->_fields['group']);
    $this->_id = CRM_Contact_BAO_Contact::createProfileContact(
      $params,
      $this->_fields,
      $this->_id,
      $this->_addToGroupID,
      $this->_gid,
      $this->_ctype,
      TRUE
    );

    // this dirty hack will set newly added contact
    // and let drupal UserProfile Form to access them
    if ($this->_id) {
      global $civicrm_profile_contact_id;
      $civicrm_profile_contact_id = $this->_id;
    }

    // second, trying to send mail to subscrber.
    $mailingType = [];
    $config = CRM_Core_Config::singleton();
    $groupSubscribed = [];
    //array of group id, subscribed by contact
    if ($this->_id) {
      $contactGroups = new CRM_Contact_DAO_GroupContact();
      $contactGroups->contact_id = $this->_id;
      $contactGroups->status = 'Added';
      $contactGroups->find();
      while ($contactGroups->fetch()) {
        $groupSubscribed[$contactGroups->group_id] = 1;
      }
    }

    if (!empty($submittedGroup)) {
      $profile = NULL;
      foreach ($params as $name => $values) {
        if (substr($name, 0, 6) == 'email-') {
          $profile['email'] = $values;
        }
      }
      if (!empty($profile['email'])) {
        foreach ($submittedGroup as $key => $val) {
          if (!empty($val)) {
            // only add who not subscribed
            if (empty($groupSubscribed[$key])) {
              $mailingType[$key] = 1;
              unset($submittedGroup[$key]);
            }
          }
          else{
            unset($submittedGroup[$key]);
          }
        }
      }
    }

    // third, keep subscribed contact remain in group
    if (CRM_Utils_Array::value('add_to_group', $params)) {
      $addToGroupId = $params['add_to_group'];
      $submittedGroup[$addToGroupId] = 1;
      if (!empty($mailingType[$addToGroupId])) {
        unset($mailingType[$key]);
      }
    }
    if (!empty($submittedGroup)) {
      // this means we are coming in via profile, not admin
      $method = 'Web';
      $visibility = TRUE;

      foreach($groupSubscribed as $key => $val){
        $submittedGroup[$key] = 1;
      }
      CRM_Contact_BAO_GroupContact::create($submittedGroup, $this->_id, $visibility, $method);
    }

    // last, if still have mail to subscribe group, send mail
    $toSubscribe = array_keys($mailingType);
    if ($config->profileDoubleOptIn) {
      CRM_Mailing_Event_BAO_Subscribe::commonSubscribe($toSubscribe, $profile, $this->_id);
    } else {
      foreach ($toSubscribe as $groupID) {
        $se = CRM_Mailing_Event_BAO_Subscribe::subscribe($groupID, $profile['email'], $this->_id);
        $confirm = CRM_Mailing_Event_BAO_Confirm::confirm($this->_id, $se->id, $se->hash);
      }
    }


    $ufGroups = [];
    if ($this->_gid) {
      $ufGroups[$this->_gid] = 1;
    }
    elseif ($this->_mode == self::MODE_REGISTER) {
      $ufGroups = &CRM_Core_BAO_UFGroup::getModuleUFGroup('User Registration');
    }

    foreach ($ufGroups as $gId => $val) {
      if ($notify = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $gId, 'notify')) {
        $values = CRM_Core_BAO_UFGroup::checkFieldsEmptyValues($gId, $this->_id, NULL);
        $fields = CRM_Core_BAO_UFGroup::getFields($gId, FALSE, CRM_Core_Action::VIEW);
        CRM_Core_BAO_UFGroup::verifySubmittedValue($fields, $values, $params);
        CRM_Core_BAO_UFGroup::commonSendMail($this->_id, $values);
      }
    }

    //create CMS user (if CMS user option is selected in profile)
    if (CRM_Utils_Array::value('cms_create_account', $params) &&
      $this->_mode == self::MODE_CREATE
    ) {
      $params['contactID'] = $this->_id;

      if (!CRM_Core_BAO_CMSUser::create($params, $this->_mail)) {
        CRM_Core_Session::setStatus(ts('Your profile is not saved and Account is not created.'));
        $transaction->rollback();
        return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/profile/create',
            'reset=1&gid=' . $this->_gid
          ));
      }
    }

    $this->track(4);
    $transaction->commit();
  }

  function getTemplateFileName() {
    if ($this->_gid) {
      $templateFile = "CRM/Profile/Form/{$this->_gid}/{$this->_name}.tpl";
      $template = &CRM_Core_Form::getTemplate();
      if ($template->template_exists($templateFile)) {
        return $templateFile;
      }

      // lets see if we have customized by name
      $ufGroupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $this->_gid, 'name');
      if ($ufGroupName) {
        $templateFile = "CRM/Profile/Form/{$ufGroupName}/{$this->_name}.tpl";
        if ($template->template_exists($templateFile)) {
          return $templateFile;
        }
      }
    }
    return parent::getTemplateFileName();
  }

  function track($state) {
    $params = [
      'state' => $state,
      'page_type' => 'civicrm_uf_group',
      'page_id' => $this->_gid,
      'visit_date' => date('Y-m-d H:i:s'),
    ];
    $track = CRM_Core_BAO_Track::add($params);
    if ($this->_id) {
      $params['entity_table'] = 'civicrm_contact';
      $params['entity_id'] = $this->_id;
    }
    $track = CRM_Core_BAO_Track::add($params);
  }
}

