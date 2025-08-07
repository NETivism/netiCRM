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
class CRM_Contact_Form_Search_Criteria {

  static function basic(&$form) {
    $form->addElement('hidden', 'hidden_basic', 1);

    if ($form->_searchOptions['contactType']) {
      // add checkboxes for contact type
      $contact_type = [];

      $contactTypes = CRM_Contact_BAO_ContactType::getSelectElements();

      if ($contactTypes) {
        $form->add('select', 'contact_type', ts('Contact Type(s)'), $contactTypes, FALSE,
          ['id' => 'contact_type', 'multiple' => 'multiple', 'title' => ts('- select -')]
        );
      }
    }

    if ($form->_searchOptions['groups']) {
      // multiselect for groups
      if ($form->_group) {
        $form->add('select', 'group', ts('Groups'), $form->_group, FALSE,
          ['id' => 'group', 'multiple' => 'multiple', 'title' => ts('- select -')]
        );
      }
    }

    if ($form->_searchOptions['tags']) {
      // multiselect for categories

      $contactTags = CRM_Core_BAO_Tag::getTags();

      if ($contactTags) {
        $form->add('select', 'contact_tags', ts('Tags'), $contactTags, FALSE,
          ['id' => 'contact_tags', 'multiple' => 'multiple', 'title' => ts('- select -'), 'style' => 'width:160px']
        );
      }



      $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_contact');
      CRM_Core_Form_Tag::buildQuickForm($form, $parentNames, 'civicrm_contact', NULL, TRUE);
    }

    // add text box for last name, first name, street name, city
    $form->addElement('text', 'sort_name', ts('Find...'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    // add text box for last name, first name, street name, city
    $form->add('text', 'email', ts('Contact Email'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    //added contact source
    $form->add('text', 'contact_source', ts('Contact Source'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'source'));

    //added job title
    $attributes['job_title']['size'] = 30;
    $form->addElement('text', 'job_title', ts('Job Title'), $attributes['job_title'], 'size="30"');

    // add nick name
    $attributes['nick_name']['size'] = 30;
    $form->addElement('text', 'nick_name', ts('Nick Name'), $attributes['nick_name'], 'size="30"');

    //added internal ID
    $attributes['id']['size'] = 30;
    $form->addElement('text', 'id', ts('Contact ID'), $attributes['id'], 'size="30"');

    //added external ID
    $attributes['external_identifier']['size'] = 30;
    $form->addElement('text', 'external_identifier', ts('External ID'), $attributes['external_identifier'], 'size="30"');

    $attributes['legal_identifier']['size'] = 30;
    $form->addElement('text', 'legal_identifier', ts('Legal Identifier'), $attributes['legal_identifier'], 'size="30"');

    $config = &CRM_Core_Config::singleton();
    if (CRM_Core_Permission::check('access deleted contacts')) {
      $form->add('checkbox', 'deleted_contacts', ts('Search in Trash (deleted contacts)'));
    }

    // add checkbox for cms users only
    $form->addYesNo('uf_user', ts('CMS User?'));

    // tag all search
    $form->add('text', 'tag_search', ts('All Tags'));

    // add search profiles


    // FIXME: This is probably a part of profiles - need to be
    // FIXME: eradicated from here when profiles are reworked.
    $types = ['Participant', 'Contribution', 'Membership'];

    // get component profiles
    $componentProfiles = [];
    $componentProfiles = CRM_Core_BAO_UFGroup::getProfiles($types);

    $ufGroups = &CRM_Core_BAO_UFGroup::getModuleUFGroup('Search Profile', 1);
    $accessibleUfGroups = CRM_Core_Permission::ufGroup(CRM_Core_Permission::VIEW);

    $searchProfiles = [];
    foreach ($ufGroups as $key => $var) {
      if (!CRM_Utils_Array::arrayKeyExists($key, $componentProfiles) && in_array($key, $accessibleUfGroups)) {
        $searchProfiles[$key] = $var['title'];
      }
    }

    $form->addElement('select',
      'uf_group_id',
      ts('Search Views'),
      ['0' => ts('- default view -')] + $searchProfiles
    );


    $componentModes = $form->getModeSelect();

    if (count($componentModes) > 1) {
      $form->addElement('select',
        'component_mode',
        ts('Display Results As'),
        $componentModes
      );
    }

    $form->addElement('select',
      'operator',
      ts('Search Operator'),
      ['AND' => ts('AND'),
        'OR' => ts('OR'),
      ]
    );

    // add the option to display relationships
    $rTypes = CRM_Core_PseudoConstant::relationshipType();
    $rSelect = ['' => ts('- Select Relationship Type -')];
    foreach ($rTypes as $rid => $rValue) {
      if ($rValue['label_a_b'] == $rValue['label_b_a']) {
        $rSelect[$rid] = $rValue['label_a_b'];
      }
      else {
        $rSelect["{$rid}_a_b"] = $rValue['label_a_b'];
        $rSelect["{$rid}_b_a"] = $rValue['label_b_a'];
      }
    }

    $form->addElement('select',
      'display_relationship_type',
      ts('Display Results as Relationship'),
      $rSelect
    );

    // checkboxes for DO NOT phone, email, mail
    // we take labels from SelectValues
    $t = CRM_Core_SelectValues::privacy();
    $form->add('select',
      'privacy_options',
      ts('Privacy'),
      $t,
      FALSE,
      [
        'id' => 'privacy_options',
        'multiple' => 'multiple',
        'title' => ts('- select -'),
      ]
    );

    $form->addElement('select',
      'privacy_operator',
      ts('Operator'),
      ['OR' => ts('OR'),
        'AND' => ts('AND'),
      ]
    );

    $toggleChoice = [];
    $toggleChoice[] = $form->createElement('radio', NULL, '', ' ' . ts('Exclude'), '1');
    $toggleChoice[] = $form->createElement('radio', NULL, '', ' ' . ts('Include by Privacy Option(s)'), '2');
    $form->addGroup($toggleChoice, 'privacy_toggle', 'Privacy Options');

    // preferred communication method

    $comm = CRM_Core_PseudoConstant::pcm();

    $commPreff = [];
    foreach ($comm as $k => $v) {
      $commPreff[] = $form->createElement('advcheckbox', $k, NULL, $v);
    }

    $onHold[] = $form->createElement('advcheckbox', 'on_hold', NULL, ts(''));
    $form->addGroup($onHold, 'email_on_hold', ts('Email On Hold'));

    $form->addGroup($commPreff, 'preferred_communication_method', ts('Preferred Communication Method'));

    //CRM-6138 Preferred Language
    $langPreff = CRM_Core_PseudoConstant::languages();
    $form->add('select', 'preferred_language', ts('Preferred Language'), ['' => ts('- select language -')] + $langPreff);

    // #21360
    $form->addDateTime('contact_created_date_low', ts('Created On'), FALSE, ['formatType' => 'searchDate']);
    $form->addDateTime('contact_created_date_high', ts('and'), FALSE, ['formatType' => 'searchDate']);
    $form->addDateTime('contact_modified_date_low', ts('Last Modified Date'), FALSE, ['formatType' => 'searchDate']);
    $form->addDateTime('contact_modified_date_high', ts('and'), FALSE, ['formatType' => 'searchDate']);
  }

  static function location(&$form) {
    $form->addElement('hidden', 'hidden_location', 1);


    $addressOptions = CRM_Core_BAO_Preferences::valueOptions('address_options', TRUE, NULL, TRUE);

    $attributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_Address');

    $elements = [
      'street_address' => [ts('Street Address'), $attributes['street_address'], NULL, NULL],
      'city' => [ts('City'), $attributes['city'], NULL, NULL],
      'postal_code' => [ts('Zip / Postal Code'), $attributes['postal_code'], NULL, NULL],
      'county' => [ts('County'), $attributes['county_id'], 'county', FALSE],
      'state_province' => [ts('State / Province'), $attributes['state_province_id'], 'stateProvince', TRUE],
      'country' => [ts('Country'), $attributes['country_id'], 'country', FALSE],
      'address_name' => [ts('Address Name'), $attributes['address_name'], NULL, NULL],
    ];
    foreach ($elements as $name => $v) {
      list($title, $attributes, $select, $multiSelect) = $v;

      if (!$addressOptions[$name]) {
        continue;
      }

      if (!$attributes) {
        $attributes = $attributes[$name];
      }

      if ($select) {
        $config = CRM_Core_Config::singleton();
        $countryDefault = $config->defaultContactCountry;
        $stateCountryMap[] = ['state_province' => 'state_province',
          'country' => 'country',
        ];
        if ($select == 'stateProvince') {
          if ($countryDefault && !isset($form->_submitValues['country'])) {
            $selectElements = ['' => ts('- select -')] + CRM_Core_PseudoConstant::stateProvinceForCountry($countryDefault);
          }
          elseif ($form->_submitValues['country']) {
            $selectElements = ['' => ts('- select -')] + CRM_Core_PseudoConstant::stateProvinceForCountry($form->_submitValues['country']);
          }
          else {
            //if not setdefault any country
            $selectElements = ['' => ts('- select -')] + CRM_Core_PseudoConstant::$select();
          }
          $element = $form->addElement('select', $name, $title, $selectElements);
        }
        elseif ($select == 'country') {
          if ($countryDefault) {
            //for setdefault country
            $defaultValues = [];
            $defaultValues[$name] = $countryDefault;
            $form->setDefaults($defaultValues);
          }
          $selectElements = ['' => ts('- select -')] + CRM_Core_PseudoConstant::$select();
          $element = $form->addElement('select', $name, $title, $selectElements);
        }
        else {
          $selectElements = ['' => ts('- select -')] + CRM_Core_PseudoConstant::$select();
          $element = $form->addElement('select', $name, $title, $selectElements);
        }
        if ($multiSelect) {
          $element->setMultiple(TRUE);
        }
      }
      else {
        $form->addElement('text', $name, $title, $attributes);
      }

      if ($addressOptions['postal_code']) {
        $form->addElement('text', 'postal_code_low', ts('Range-From'),
          CRM_Utils_Array::value('postal_code', $attributes)
        );
        $form->addElement('text', 'postal_code_high', ts('To'),
          CRM_Utils_Array::value('postal_code', $attributes)
        );
      }
    }

    // extend addresses with proximity search
    $form->addElement('text', 'prox_distance', ts('Find contacts within'));
    $form->addElement('select', 'prox_distance_unit', NULL, ['miles' => ts('Miles'), 'kilos' => ts('Kilometers')]);

    // is there another form rule that does decimals besides money ? ...
    $form->addRule('prox_distance', ts('Please enter positive number as a distance'), 'numeric');


    CRM_Core_BAO_Address::addStateCountryMap($stateCountryMap);
    $worldRegions = ['' => ts('- any region -')] + CRM_Core_PseudoConstant::worldRegion();
    $form->addElement('select', 'world_region', ts('World Region'), $worldRegions);

    // checkboxes for location type
    $location_type = [];
    $locationType = CRM_Core_PseudoConstant::locationType();
    foreach ($locationType as $locationTypeID => $locationTypeName) {
      $location_type[] = $form->createElement('checkbox', $locationTypeID, NULL, $locationTypeName);
    }
    $form->addGroup($location_type, 'location_type', ts('Location Types'), '&nbsp;');

    // custom data extending addresses -

    $extends = ['Address'];
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $extends);
    if ($groupDetails) {

      $form->assign('addressGroupTree', $groupDetails);
      foreach ($groupDetails as $group) {
        foreach ($group['fields'] as $field) {
          $elementName = 'custom_' . $field['id'];
          CRM_Core_BAO_CustomField::addQuickFormElement($form,
            $elementName,
            $field['id'],
            FALSE, FALSE, TRUE
          );
        }
      }
    }
  }

  static function activity(&$form) {
    $form->add('hidden', 'hidden_activity', 1);

    CRM_Activity_BAO_Query::buildSearchForm($form);
  }

  static function changeLog(&$form) {
    $form->add('hidden', 'hidden_changeLog', 1);

    // block for change log
    $form->addElement('text', 'changed_by', ts('Modified By'), NULL);
    $form->addElement('text', 'changed_log', ts('Change Log'), NULL);

    $form->addDateTime('log_date_low', ts('Modified Between'), FALSE, ['formatType' => 'searchDate']);
    $form->addDateTime('log_date_high', ts('and'), FALSE, ['formatType' => 'searchDate']);
  }

  static function task(&$form) {
    $form->add('hidden', 'hidden_task', 1);

    if (CRM_Core_Permission::access('Quest')) {
      $form->assign('showTask', 1);

      // add the task search stuff
      // we add 2 select boxes, one for the task from the task table
      $taskSelect = ['' => '- select -'] + CRM_Core_PseudoConstant::tasks();
      $form->addElement('select', 'task_id', ts('Task'), $taskSelect);
      $form->addSelectByOption('task_status', ts('Task Status'));
    }
  }

  static function relationship(&$form) {
    $form->add('hidden', 'hidden_relationship', 1);



    $allRelationshipType = [];
    $allRelationshipType = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, NULL, TRUE);
    $form->addElement('select', 'relation_type_id', ts('Relationship Type'), ['' => ts('- select -')] + $allRelationshipType);
    $form->addElement('text', 'relation_target_name', ts('Target Contact'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));
    $relStatusOption = [ts('Active '), ts('Inactive '), ts('All')];
    $form->addRadio('relation_status', ts('Relationship Status'), $relStatusOption);
    $form->setDefaults(['relation_status' => 0]);

    // add all the custom  searchable fields

    $relationship = ['Relationship'];
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $relationship);
    if ($groupDetails) {

      $form->assign('relationshipGroupTree', $groupDetails);
      foreach ($groupDetails as $group) {
        foreach ($group['fields'] as $field) {
          $fieldId = $field['id'];
          $elementName = 'custom_' . $fieldId;
          CRM_Core_BAO_CustomField::addQuickFormElement($form,
            $elementName,
            $fieldId,
            FALSE, FALSE, TRUE
          );
        }
      }
    }
  }

  static function demographics(&$form) {
    $form->add('hidden', 'hidden_demographics', 1);
    // radio button for gender
    $genderOptions = [];
    $gender = CRM_Core_PseudoConstant::gender();
    foreach ($gender as $key => $var) {
      $genderOptions[$key] = $form->createElement('radio', NULL, ts('Gender'), $var, $key);
    }
    $form->addGroup($genderOptions, 'gender', ts('Gender'));

    $form->addDate('birth_date_low', ts('Birth Dates - From'), FALSE, ['formatType' => 'birth']);
    $form->addDate('birth_date_high', ts('To'), FALSE, ['formatType' => 'birth']);

    $form->addDate('deceased_date_low', ts('Deceased Dates - From'), FALSE, ['formatType' => 'birth']);
    $form->addDate('deceased_date_high', ts('To'), FALSE, ['formatType' => 'birth']);

    $attribute = ['min' => '0'];
    $form->addNumber('age_low', ts('Age - From'), $attribute);
    $form->addNumber('age_high', ts('To'), $attribute);
  }

  static function notes(&$form) {
    $form->add('hidden', 'hidden_notes', 1);

    $form->addElement('text', 'note', ts('Note Text'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));
  }

  /**
   * Generate the custom Data Fields based
   * on the is_searchable
   *
   * @access private
   *
   * @return void
   */
  static function custom(&$form) {
    $form->add('hidden', 'hidden_custom', 1);
    $extends = array_merge(['Contact', 'Individual', 'Household', 'Organization'],
      CRM_Contact_BAO_ContactType::subTypes()
    );
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE,
      $extends
    );

    $form->assign('groupTree', $groupDetails);

    foreach ($groupDetails as $key => $group) {
      $_groupTitle[$key] = $group['name'];
      CRM_Core_ShowHideBlocks::links($form, $group['name'], '', '');

      $groupId = $group['id'];
      foreach ($group['fields'] as $field) {
        $fieldId = $field['id'];
        $elementName = 'custom_' . $fieldId;

        CRM_Core_BAO_CustomField::addQuickFormElement($form,
          $elementName,
          $fieldId,
          FALSE, FALSE, TRUE
        );
      }
    }

    //TODO: validate for only one state if prox_distance isset
  }

  static function CiviCase(&$form) {
    //Looks like obsolete code, since CiviCase is a component, but might be used by HRD
    $form->add('hidden', 'hidden_CiviCase', 1);

    CRM_Case_BAO_Query::buildSearchForm($form);
  }
}

