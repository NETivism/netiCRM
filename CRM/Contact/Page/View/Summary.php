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
class CRM_Contact_Page_View_Summary extends CRM_Contact_Page_View {

  public $_editOptions;
  public $_viewOptions;
  /**
   * Heart of the viewing process. The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   *
   * @return void
   * @access public
   *
   */
  function preProcess() {
    parent::preProcess();

    // actions buttom contextMenu
    $menuItems = CRM_Contact_BAO_Contact::contextMenu();

    $this->assign('actionsMenuList', $menuItems);

    //retrieve inline custom data
    $entityType = $this->get('contactType');
    $entitySubType = $this->get('contactSubtype');

    $groupTree = CRM_Core_BAO_CustomGroup::getTree($entityType,
      $this,
      $this->_contactId,
      NULL,
      $entitySubType
    );

    CRM_Core_BAO_CustomGroup::buildCustomDataView($this,
      $groupTree
    );

    // also create the form element for the activity links box
    $controller = new CRM_Core_Controller_Simple('CRM_Activity_Form_ActivityLinks',
      ts('Activity Links'),
      NULL
    );
    $controller->setEmbedded(TRUE);
    $controller->run();
  }

  /**
   * Heart of the viewing process. The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   *
   * @return void
   * @access public
   *
   */
  function run() {
    $this->preProcess();

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $this->edit();
    }
    else {
      $this->view();
    }

    return parent::run();
  }

  /**
   * Edit name and address of a contact
   *
   * @return void
   * @access public
   */
  function edit() {
    // set the userContext stack
    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $this->_contactId);
    $session->pushUserContext($url);

    $controller = new CRM_Core_Controller_Simple('CRM_Contact_Form_Contact', ts('Contact Page'), CRM_Core_Action::UPDATE);
    $controller->setEmbedded(TRUE);
    $controller->process();
    return $controller->run();
  }

  /**
   * View summary details of a contact
   *
   * @return void
   * @access public
   */
  function view() {
    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $this->_contactId);
    $session->pushUserContext($url);

    $params = [];
    $defaults = [];
    $ids = [];

    $params['id'] = $params['contact_id'] = $this->_contactId;
    $params['noRelationships'] = $params['noNotes'] = $params['noGroups'] = TRUE;
    $contact = CRM_Contact_BAO_Contact::retrieve($params, $defaults, TRUE);
    if (!empty($defaults['email'])) {
      $bounceTypes = CRM_Mailing_PseudoConstant::bounceType('name', 'description');
      foreach($defaults['email'] as $blid => &$em) {
        if ($em['on_hold']) {
          $bounceRecord = CRM_Mailing_Event_BAO_Bounce::getEmailBounceType(NULL, $em['id']);
          if (!empty($bounceRecord)) {
            $em['is_spam'] = $bounceRecord['bounce_type_name'] == 'Spam' ? TRUE : FALSE;
            $em['bounce_type_name'] = $bounceRecord['bounce_type_name'];
            $em['bounce_type_desc'] = $bounceTypes[$bounceRecord['bounce_type_name']];
            $em['bounce_mailing_id'] = $bounceRecord['mailing_id'];
          }
        }
      }
    }

    $communicationType = [
      'phone' => [
        'type' => 'phoneType',
        'id' => 'phone_type',
      ],
      'im' => [
        'type' => 'IMProvider',
        'id' => 'provider',
      ],
      'website' => [
        'type' => 'websiteType',
        'id' => 'website_type',
      ],
      'address' => ['skip' => TRUE, 'customData' => 1],
      'email' => ['skip' => TRUE],
      'openid' => ['skip' => TRUE],
    ];

    foreach ($communicationType as $key => $value) {
      if (CRM_Utils_Array::value($key, $defaults)) {
        foreach ($defaults[$key] as & $val) {
          CRM_Utils_Array::lookupValue($val, 'location_type', CRM_Core_PseudoConstant::locationType(), FALSE);
          if (!CRM_Utils_Array::value('skip', $value)) {
            $pseudoConst = CRM_Core_PseudoConstant::{$value['type']}( );
            CRM_Utils_Array::lookupValue($val, $value['id'], $pseudoConst, FALSE);
          }
        }
        if (isset($value['customData'])) {
          foreach ($defaults[$key] as $blockId => $blockVal) {
            $groupTree = CRM_Core_BAO_CustomGroup::getTree(ucfirst($key),
              $this,
              $blockVal['id']
            );
            // we setting the prefix to dnc_ below so that we don't overwrite smarty's grouptree var.
            $defaults[$key][$blockId]['custom'] = CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, FALSE, NULL, "dnc_");
          }
          // reset template variable since that won't be of any use, and could be misleading
          $this->assign("dnc_viewCustomData", NULL);
        }
      }
    }

    if (CRM_Utils_Array::value('gender_id', $defaults)) {
      $gender = CRM_Core_PseudoConstant::gender();
      $defaults['gender_display'] = $gender[CRM_Utils_Array::value('gender_id', $defaults)];
    }

    // to make contact type label available in the template -
    $contactType = CRM_Utils_Array::arrayKeyExists('contact_sub_type', $defaults) ? $defaults['contact_sub_type'] : $defaults['contact_type'];
    $defaults['contact_type_label'] = CRM_Contact_BAO_ContactType::contactTypePairs(TRUE, $contactType);

    // get contact tags

    $contactTags = CRM_Core_BAO_EntityTag::getContactTags($this->_contactId);

    if ( !empty( $contactTags ) ) {
      $contactTagsHtml = [];
      foreach ($contactTags as $key => $value) {
        $tagUrl = CRM_Utils_System::url('civicrm/contact/search','reset=1&force=1&tid=' . $key);
        $contactTagsHtml[] = '<a href="' . $tagUrl . '">' . $value . '</a>';
      }
      $defaults['contactTag'] = CRM_Utils_Array::implode( ', ', $contactTagsHtml );
    }

    $defaults['privacy_values'] = CRM_Core_SelectValues::privacy();

    //Show blocks only if they are visible in edit form

    $this->_editOptions = CRM_Core_BAO_Preferences::valueOptions('contact_edit_options');
    $configItems = ['CommBlock' => 'Communication Preferences',
      'Demographics' => 'Demographics',
      'TagsAndGroups' => 'Tags and Groups',
      'Notes' => 'Notes',
    ];

    foreach ($configItems as $c => $t) {
      $varName = '_show' . $c;
      $this->$varName = CRM_Utils_Array::value($c, $this->_editOptions);
      $this->assign(substr($varName, 1), $this->$varName);
    }

    // get contact name of shared contact names
    $sharedAddresses = [];
    $shareAddressContactNames = CRM_Contact_BAO_Contact_Utils::getAddressShareContactNames($defaults['address']);
    foreach ($defaults['address'] as $key => $addressValue) {
      if (CRM_Utils_Array::value('master_id', $addressValue) && !$shareAddressContactNames[$addressValue['master_id']]['is_deleted']) {
        $sharedAddresses[$key]['shared_address_display'] = ['address' => $addressValue['display'],
          'name' => $shareAddressContactNames[$addressValue['master_id']]['name'],
        ];
      }
    }
    $this->assign('sharedAddresses', $sharedAddresses);

    //get the current employer name
    if (CRM_Utils_Array::value('contact_type', $defaults) == 'Individual') {
      if ($contact->employer_id && $contact->organization_name) {
        $defaults['current_employer'] = $contact->organization_name;
        $defaults['current_employer_id'] = $contact->employer_id;
      }

      //for birthdate format with respect to birth format set
      $this->assign('birthDateViewFormat', CRM_Utils_Array::value('qfMapping', CRM_Utils_Date::checkBirthDateFormat()));
    }

    $this->assign($defaults);

    // also assign the last modifed details
    $createdBy = CRM_Core_BAO_Log::lastModified($this->_contactId, 'civicrm_contact', 'asc');
    if (!empty($createdBy)) {
      $this->assign_by_ref('createdBy', $createdBy);
      $lastModified = CRM_Core_BAO_Log::lastModified($this->_contactId, 'civicrm_contact');
      if ($createdBy['log_id'] !== $lastModified['log_id']) {
        $this->assign_by_ref('lastModified', $lastModified);
      }
    }
    else {
      if (!empty($contact->created_date)) {
        $createdBy = [
          'date' => $contact->created_date,
        ];  
        $this->assign_by_ref('createdBy', $createdBy);
      }
    }

    $allTabs = [];
    $weight = 10;

    $this->_viewOptions = CRM_Core_BAO_Preferences::valueOptions('contact_view_options', TRUE);
    $changeLog = $this->_viewOptions['log'];
    $this->assign_by_ref('changeLog', $changeLog);

    $components = CRM_Core_Component::getEnabledComponents();

    foreach ($components as $name => $component) {
      if (CRM_Utils_Array::value($name, $this->_viewOptions) &&
        CRM_Core_Permission::access($component->name)
      ) {
        $elem = $component->registerTab();

        // FIXME: not very elegant, probably needs better approach
        // allow explicit id, if not defined, use keyword instead
        if (CRM_Utils_Array::arrayKeyExists('id', $elem)) {
          $i = $elem['id'];
        }
        else {
          $i = $component->getKeyword();
        }
        $u = $elem['url'];

        //appending isTest to url for test soft credit CRM-3891.
        //FIXME: hack ajax url.
        $q = "reset=1&snippet=1&force=1&cid={$this->_contactId}";
        if (CRM_Utils_Request::retrieve('isTest', 'Positive', $this)) {
          $q = $q . "&isTest=1";
        }
        $allTabs[] = ['id' => $i,
          'url' => CRM_Utils_System::url("civicrm/contact/view/$u", $q),
          'title' => $elem['title'],
          'weight' => $elem['weight'],
          'count' => CRM_Contact_BAO_Contact::getCountComponent($u, $this->_contactId),
        ];
        // make sure to get maximum weight, rest of tabs go after
        // FIXME: not very elegant again
        if ($weight < $elem['weight']) {
          $weight = $elem['weight'];
        }
      }
    }

    $rest = ['activity' => ts('Activities'),
      'case' => ts('Cases'),
      'rel' => ts('Relationships'),
      'group' => ts('Groups'),
      'note' => ts('Notes'),
      'tag' => ts('Tags'),
      'log' => ts('Change Log'),
    ];

    $config = CRM_Core_Config::singleton();
    if (isset($config->sunlight) &&
      $config->sunlight
    ) {
      $title = ts('Elected Officials');
      $rest['sunlight'] = $title;
      $this->_viewOptions[$title] = TRUE;
    }

    foreach ($rest as $k => $v) {
      if (CRM_Utils_Array::value($k, $this->_viewOptions)) {
        $allTabs[] = ['id' => $k,
          'url' => CRM_Utils_System::url("civicrm/contact/view/$k",
            "reset=1&snippet=1&cid={$this->_contactId}"
          ),
          'title' => $v,
          'weight' => $weight,
          'count' => CRM_Contact_BAO_Contact::getCountComponent($k, $this->_contactId),
        ];
        $weight += 10;
      }
    }

    // now add all the custom tabs
    $entityType = $this->get('contactType');
    $activeGroups = CRM_Core_BAO_CustomGroup::getActiveGroups($entityType,
      'civicrm/contact/view/cd',
      $this->_contactId
    );

    foreach ($activeGroups as $group) {
      $id = "custom_{$group['id']}";
      $allTabs[] = ['id' => $id,
        'url' => CRM_Utils_System::url($group['path'], $group['query'] . "&snippet=1&selectedChild=$id"),
        'title' => $group['title'],
        'weight' => $weight,
        'count' => CRM_Contact_BAO_Contact::getCountComponent($id, $this->_contactId, $group['table_name']),
      ];
      $weight += 10;
    }

    // see if any other modules want to add any tabs

    CRM_Utils_Hook::tabs($allTabs, $this->_contactId);

    // now sort the tabs based on weight

    usort($allTabs, ['CRM_Utils_Sort', 'cmpFunc']);

    $this->assign('allTabs', $allTabs);

    $selectedChild = CRM_Utils_Request::retrieve('selectedChild', 'String', $this, FALSE, 'summary');
    $this->assign('selectedChild', $selectedChild);

    // hook for contact summary

    // ignored but needed to prevent warnings
    $contentPlacement = CRM_Utils_Hook::SUMMARY_BELOW;
    CRM_Utils_Hook::summary($this->_contactId, $content, $contentPlacement);
    if ($content) {
      $this->assign_by_ref('hookContent', $content);
      $this->assign('hookContentPlacement', $contentPlacement);
    }
  }

  function getTemplateFileName() {
    if ($this->_contactId) {
      $csType = $this->get('contactSubtype');
      if ($csType) {
        $templateFile = "CRM/Contact/Page/View/SubType/{$csType}.tpl";
        $template = CRM_Core_Page::getTemplate();
        if ($template->template_exists($templateFile)) {
          return $templateFile;
        }
      }
    }
    return parent::getTemplateFileName();
  }
}

