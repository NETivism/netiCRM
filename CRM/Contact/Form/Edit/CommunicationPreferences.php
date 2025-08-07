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
 * form helper class for an Communication Preferences object
 */
class CRM_Contact_Form_Edit_CommunicationPreferences {

  /**
   * greetings
   * @var array
   * @static
   */
  static $greetings = [];

  /**
   * build the form elements for Communication Preferences object
   *
   * @param CRM_Core_Form $form       reference to the form object
   *
   * @return void
   * @access public
   * @static
   */
  static function buildQuickForm(&$form) {
    // since the pcm - preferred comminication method is logically
    // grouped hence we'll use groups of HTML_QuickForm


    // checkboxes for DO NOT phone, email, mail
    // we take labels from SelectValues
    $privacy = $commPreff = $commPreference = [];
    $privacyOptions = CRM_Core_SelectValues::privacy();
    foreach ($privacyOptions as $name => $label) {
      $privacy[] = $form->createElement('advcheckbox', $name, NULL, $label);
    }
    $form->addGroup($privacy, 'privacy', ts('Privacy'), '&nbsp;');

    // preferred communication method

    $comm = CRM_Core_PseudoConstant::pcm();
    foreach ($comm as $value => $title) {
      $commPreff[] = $form->createElement('advcheckbox', $value, NULL, $title);
    }
    $form->addGroup($commPreff, 'preferred_communication_method', ts('Preferred Method(s)'));

    $form->add('select', 'preferred_language',
      ts('Preferred Language'),
      ['' => ts('- select -')] +
      CRM_Core_PseudoConstant::languages()
    );

    if (!empty($privacyOptions)) {
      $commPreference['privacy'] = $privacyOptions;
    }
    if (!empty($comm)) {
      $commPreference['preferred_communication_method'] = $comm;
    }

    //using for display purpose.
    $form->assign('commPreference', $commPreference);

    $form->add('select', 'preferred_mail_format', ts('Email Format'), CRM_Core_SelectValues::pmf());

    //check contact type and build filter clause accordingly for greeting types, CRM-4575
    $greetings = self::getGreetingFields($form->_contactType);

    foreach ($greetings as $greeting => $fields) {
      $filter = ['contact_type' => $form->_contactType,
        'greeting_type' => $greeting,
      ];

      //add addressee in Contact form
      $greetingTokens = CRM_Core_PseudoConstant::greeting($filter);
      $customizedKey = CRM_Utils_Array::key('Customized', $greetingTokens);
      if (!empty($customizedKey)) {
        $greetingTokens[$customizedKey] = ts('Customized');
      }
      if (!empty($greetingTokens)) {
        $form->addElement('select', $fields['field'], $fields['label'],
          ['' => ts('- select -')] + $greetingTokens
        );
        //custom addressee
        $form->addElement('text', $fields['customField'], $fields['customLabel'],
          CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', $fields['customField']), $fields['js']
        );
      }
    }
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
    //CRM-4575


    $greetings = self::getGreetingFields($self->_contactType);
    foreach ($greetings as $greeting => $details) {
      $customizedValue = CRM_Core_OptionGroup::getValue($greeting, 'Customized', 'name');
      if (CRM_Utils_Array::value($details['field'], $fields) == $customizedValue
        && !CRM_Utils_Array::value($details['customField'], $fields)
      ) {
        $errors[$details['customField']] = ts('Custom  %1 is a required field if %1 is of type Customized.',
          [1 => $details['label']]
        );
      }
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  static function setDefaultValues(&$form, &$defaults) {

    if (!empty($defaults['preferred_language'])) {
      $languages = array_flip(CRM_Core_PseudoConstant::languages());
      $defaults['preferred_language'] = $languages[$defaults['preferred_language']];
    }

    // CRM-7119: set preferred_language to default if unset
    if (!isset($defaults['preferred_language']) or empty($defaults['preferred_language'])) {
      $config = &CRM_Core_Config::singleton();
      $defaults['preferred_language'] = $config->lcMessages;
    }

    //set default from greeting types CRM-4575.
    $greetingTypes = ['addressee' => 'addressee_id',
      'email_greeting' => 'email_greeting_id',
      'postal_greeting' => 'postal_greeting_id',
    ];

    if ($form->_action & CRM_Core_Action::ADD) {
      $contactTypeFilters = [1 => 'Individual', 2 => 'Household', 3 => 'Organization'];
      $filter = CRM_Utils_Array::key($form->_contactType, $contactTypeFilters);

      foreach ($greetingTypes as $greetingType => $greeting) {
        if (!CRM_Utils_Array::value($greeting, $defaults)) {
          //get the default from email address.
          $defaultGreetingTypeId = CRM_Core_OptionGroup::values($greetingType, NULL, NULL,
            NULL, " AND is_default = 1 AND ( filter = {$filter} OR filter = 0 )", 'value'
          );
          if (!empty($defaultGreetingTypeId)) {
            $defaults[$greeting] = key($defaultGreetingTypeId);
          }
        }
      }
    }
    else {
      foreach ($greetingTypes as $greetingType => $greeting) {
        $name = "{$greetingType}_display";
        $form->assign($name, CRM_Utils_Array::value($name, $defaults));
      }
    }
  }

  /**
   *  set array of greeting fields
   *
   * @return None
   * @access public
   */
  static function getGreetingFields($contactType) {
    if (empty(self::$greetings[$contactType])) {
      self::$greetings[$contactType] = [];

      $js = ['onfocus' => "if (!this.value) {  this.value='Dear ';} else return false",
        'onblur' => "if ( this.value == 'Dear') {  this.value='';} else return false",
      ];

      self::$greetings[$contactType] = [
        'addressee' => ['field' => 'addressee_id',
          'customField' => 'addressee_custom',
          'label' => ts('Addressee'),
          'customLabel' => ts('Custom Addressee'),
          'js' => NULL,
        ],
        'email_greeting' => ['field' => 'email_greeting_id',
          'customField' => 'email_greeting_custom',
          'label' => ts('Email Greeting'),
          'customLabel' => ts('Custom Email Greeting'),
          'js' => $js,
        ],
        'postal_greeting' => ['field' => 'postal_greeting_id',
          'customField' => 'postal_greeting_custom',
          'label' => ts('Postal Greeting'),
          'customLabel' => ts('Custom Postal Greeting'),
          'js' => $js,
        ],
      ];

      if ($contactType == 'Organization') {
        unset(self::$greetings[$contactType]['email_greeting']);
        unset(self::$greetings[$contactType]['postal_greeting']);
      }
    }

    return self::$greetings[$contactType];
  }
}

