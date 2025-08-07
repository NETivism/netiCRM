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
 * This class is to build the form for adding Group
 */
class CRM_Group_Form_Edit extends CRM_Core_Form {

  /**
   * the group id, used when editing a group
   *
   * @var int
   */
  protected $_id;

  /**
   * the group object, if an id is present
   *
   * @var object
   */
  protected $_group;

  /**
   * The title of the group being deleted
   *
   * @var string
   */
  protected $_title;

  /**
   * Store the group values
   *
   * @var array
   */
  protected $_groupValues;

  /**
   * what blocks should we show and hide.
   *
   * @var CRM_Core_ShowHideBlocks
   */
  protected $_showHide;

  /**
   * the civicrm_group_organization table id
   *
   * @var int
   */
  protected $_groupOrganizationID;

  /**
   * the smart marketing object
   *
   * @var object
   */
  protected $_smartMarketingService;

  /**
   * the smart marketing group type id
   *
   * @var object
   */
  protected $_smartMarketingTypeId;

  /**
   * the smart marketing freezed groups
   *
   * @var array
   */
  protected $_smartMarketingFreezed = [];

  /**
   * set up variables to build the form
   *
   * @return void
   * @acess protected
   */
  function preProcess() {
    $this->_id = $this->get('id');

    if ($this->_id) {
      $breadCrumb = [['title' => ts('Manage Groups'),
          'url' => CRM_Utils_System::url('civicrm/group',
            'reset=1'
          ),
        ]];
      CRM_Utils_System::appendBreadCrumb($breadCrumb);

      $this->_groupValues = [];
      $params = ['id' => $this->_id];
      $this->_group = &CRM_Contact_BAO_Group::retrieve($params,
        $this->_groupValues
      );
      $this->_title = $this->_groupValues['title'];
    }

    $this->assign('action', $this->_action);
    $this->assign('showBlockJS', TRUE);

    if ($this->_action == CRM_Core_Action::DELETE) {
      if (isset($this->_id)) {
        $this->assign('title', $this->_title);
        $this->assign('count', CRM_Contact_BAO_Group::memberCount($this->_id));
        CRM_Utils_System::setTitle(ts('Confirm Group Delete'));
      }
    }
    else {
      if (isset($this->_id)) {
        $groupValues = ['id' => $this->_id,
          'title' => $this->_title,
          'saved_search_id' =>
          $this->_groupValues['saved_search_id'] ?? '',
        ];
        if (isset($this->_groupValues['saved_search_id'])) {
          $groupValues['mapping_id'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch',
            $this->_groupValues['saved_search_id'],
            'mapping_id'
          );
        }
        $this->assign_by_ref('group', $groupValues);

        CRM_Utils_System::setTitle(ts('Group Settings: %1', [1 => $this->_title]));
      }
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url('civicrm/group', 'reset=1'));
    }

    //build custom data
    CRM_Custom_Form_CustomData::preProcess($this, NULL, NULL, 1, 'Group', $this->_id);

    // smart marketing
    $this->initSmartMarketingGroup();

    if ($this->_smartMarketingTypeId) {
      // check freezed remote group to prevent many local to 1 remote group issue
      if ($this->_id) {
        $syncData = CRM_Core_DAO::executeQuery("SELECT id, sync_data FROM civicrm_group WHERE NULLIF(sync_data, '') IS NOT NULL AND id != %1", [1 => [$this->_id, 'Integer']]);
      }
      else {
        $syncData = CRM_Core_DAO::executeQuery("SELECT id, sync_data FROM civicrm_group WHERE NULLIF(sync_data, '') IS NOT NULL");
      }
      while($syncData->fetch()) {
        $data = json_decode($syncData->sync_data, TRUE);
        if (!empty($data['remote_group_id'])) {
          $this->_smartMarketingFreezed[$data['remote_group_id']] = 1;
        }
      }
    }
  }

  /*
     * This function sets the default values for the form. LocationType that in edit/view mode
     * the default values are retrieved from the database
     *
     * @access public
     * @return None
     */
  function setDefaultValues() {
    $defaults = [];

    if (isset($this->_id)) {
      $defaults = $this->_groupValues;
      if (CRM_Utils_Array::value('group_type', $defaults)) {
        $types = explode(CRM_Core_DAO::VALUE_SEPARATOR,
          substr($defaults['group_type'], 1, -1)
        );
        $defaults['group_type'] = [];
        foreach ($types as $type) {
          $defaults['group_type'][$type] = 1;
        }
      }

      if (defined('CIVICRM_MULTISITE') && CIVICRM_MULTISITE &&
        CRM_Core_Permission::check('administer Multiple Organizations')
      ) {

        CRM_Contact_BAO_GroupOrganization::retrieve($this->_id, $defaults);

        if (CRM_Utils_Array::value('group_organization', $defaults)) {
          //used in edit mode
          $this->_groupOrganizationID = $defaults['group_organization'];
        }

        $this->assign('organizationID', $defaults['organization_id']);
      }

      // smart marketing
      if (!empty($defaults['is_sync']) && !empty($defaults['sync_data'])) {
        $data = json_decode($defaults['sync_data'], TRUE);
        if (!empty($data['remote_group_id'])) {
          $defaults['remote_group_id'] = $data['remote_group_id'];
        }
      }

      // last public mailing list group
      if (!empty($this->_id) && !empty($defaults['group_type'][2])){
        $publicSubsGroupCount = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_group WHERE visibility = 'Public Pages' AND id != %1 AND group_type LIKE CONCAT('%', CHAR(1), '2', CHAR(1), '%')", [
          1 => [$this->_id, 'Integer']
        ]);
        if ($publicSubsGroupCount == 0) {
          $this->assign('lastPublicSubsGroup', 1);
        }
      }

    }

    if (!CRM_Utils_Array::value('parents', $defaults)) {
      $defaults['parents'] = CRM_Core_BAO_Domain::getGroupId();
    }

    // custom data set defaults
    $defaults += CRM_Custom_Form_CustomData::setDefaultValues($this);

    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_action == CRM_Core_Action::DELETE) {
      $this->addButtons([
          ['type' => 'next',
            'name' => ts('Delete Group'),
            'isDefault' => TRUE,
          ],
          ['type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );
      return;
    }

    $this->applyFilter('__ALL__', 'trim');
    $this->add('text', 'title', ts('Name') . ' ',
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Group', 'title'), TRUE
    );

    $this->add('textarea', 'description', ts('Description') . ' ',
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Group', 'description')
    );


    $groupTypes = CRM_Core_OptionGroup::values('group_type', TRUE);
    $config = CRM_Core_Config::singleton();
    if ((isset($this->_id) &&
        CRM_Utils_Array::value('saved_search_id', $this->_groupValues)
      )
      || ($config->userFramework == 'Joomla')
    ) {
      unset($groupTypes['Access Control']);
    }

    if (!CRM_Core_Permission::access('CiviMail')) {
      unset($groupTypes['Mailing List']);
    }

    if (!empty($groupTypes)) {
      $tsGroupTypes = $filterGroupTypes = [];
      foreach ($groupTypes as $k => $v) {
        $gt = ts($k);
        $tsGroupTypes[$gt] = $v;
        $filterGroupTypes[$v] = str_replace(' ', '-', $k);
      }
      $this->addCheckBox('group_type',
        ts('Group Type'),
        $tsGroupTypes,
        NULL, NULL, NULL, NULL, '&nbsp;&nbsp;&nbsp;'
      );
      $groupTypeItems = $this->getElement('group_type');
      foreach($groupTypeItems->_elements as &$ele) {
        $gtId = $ele->_attributes['id'];
        $ele->_attributes['data-filter'] = $filterGroupTypes[$gtId];
        if (strstr($filterGroupTypes[$gtId], 'Smart-Marketing')) {
          // freeze when remote_group_id has value
          if (!empty($this->_groupValues['is_sync']) && !empty($this->_groupValues['sync_data'])) {
            $ele->freeze();
          }
        }
      }
    }

    if (isset($this->_smartMarketingService)) {
      // dropdown box
      $remoteGroups = CRM_Core_BAO_Cache::getItem('group editing', 'remote-groups-'.get_class($this->_smartMarketingService), NULL, CRM_REQUEST_TIME-180);
      if (empty($remoteGroups)) {
        $remoteGroups = $this->_smartMarketingService->getRemoteGroups();
        CRM_Core_BAO_Cache::setItem($remoteGroups, 'group editing', 'remote-groups-'.get_class($this->_smartMarketingService), NULL, CRM_REQUEST_TIME+180);
      }
      // flydove doesn't support create group
      $remoteGroups = array_diff_key($remoteGroups, $this->_smartMarketingFreezed);
      $remoteGroups = ['' => ts('-- Select --')] + $remoteGroups;
      $eleSmGroup = $this->addSelect('remote_group_id', ts('Remote Group'), $remoteGroups);
      if (!empty($this->_groupValues['is_sync']) && !empty($this->_groupValues['sync_data'])) {
        $eleSmGroup->freeze();
      }

      // button
      $vendorName = end(explode('_', get_class($this->_smartMarketingService)));
      $this->assign('smart_marketing_vendor', $vendorName);
      // $this->assign('smart_marketing_sync', TRUE);
      if (!empty($this->_groupValues['is_sync']) && !empty($this->_groupValues['sync_data'])) {
        $isPrepared = $this->_smartMarketingService->parseSavedData($this->_groupValues['sync_data']);
        if ($isPrepared !== FALSE) {
          $this->assign('smart_marketing_sync', TRUE);
        }
      }
    }
    $this->addSelect('visibility', ts('Visibility'), CRM_Core_SelectValues::ufVisibility(TRUE), TRUE);

    $groupNames = &CRM_Core_PseudoConstant::group();

    $parentGroups = $parentGroupElements = [];
    if (isset($this->_id) &&
      CRM_Utils_Array::value('parents', $this->_groupValues)
    ) {
      $parentGroupIds = explode(',', $this->_groupValues['parents']);
      foreach ($parentGroupIds as $parentGroupId) {
        $parentGroups[$parentGroupId] = $groupNames[$parentGroupId];
        if (CRM_Utils_Array::arrayKeyExists($parentGroupId, $groupNames)) {
          $parentGroupElements[$parentGroupId] = $groupNames[$parentGroupId];
          $this->addElement('checkbox', "remove_parent_group_$parentGroupId",
            $groupNames[$parentGroupId]
          );
        }
      }
    }
    $this->assign_by_ref('parent_groups', $parentGroupElements);

    if (isset($this->_id)) {

      $potentialParentGroupIds = CRM_Contact_BAO_GroupNestingCache::getPotentialCandidates($this->_id,
        $groupNames
      );
    }
    else {
      $potentialParentGroupIds = array_keys($groupNames);
    }

    $parentGroupSelectValues = ['' => '- ' . ts('select') . ' -'];
    foreach ($potentialParentGroupIds as $potentialParentGroupId) {
      if (CRM_Utils_Array::arrayKeyExists($potentialParentGroupId, $groupNames)) {
        $parentGroupSelectValues[$potentialParentGroupId] = $groupNames[$potentialParentGroupId];
      }
    }

    if (count($parentGroupSelectValues) > 1) {
      if (defined('CIVICRM_MULTISITE') && CIVICRM_MULTISITE) {
        $required = empty($parentGroups) ? TRUE : FALSE;
        $required = (($this->_id && CRM_Core_BAO_Domain::isDomainGroup($this->_id)) ||
          !isset($this->_id)
        ) ? FALSE : $required;
      }
      else {
        $required = FALSE;
      }
      $this->add('select', 'parents', ts('Add Parent'), $parentGroupSelectValues, $required);
    }
    if (defined('CIVICRM_MULTISITE') && CIVICRM_MULTISITE &&
      CRM_Core_Permission::check('administer Multiple Organizations')
    ) {
      //group organization Element
      $groupOrgDataURL = CRM_Utils_System::url('civicrm/ajax/search', 'org=1', FALSE, NULL, FALSE);
      $this->assign('groupOrgDataURL', $groupOrgDataURL);

      $this->addElement('text', 'organization', ts('Organization'), '');
      $this->addElement('hidden', 'organization_id', '', ['id' => 'organization_id']);
    }
    //build custom data
    CRM_Custom_Form_CustomData::buildQuickForm($this);
    $js = ['data' => 'click-once'];

    $this->addButtons([
        ['type' => 'upload',
          'name' =>
          ($this->_action == CRM_Core_Action::ADD) ?
          ts('Continue') : ts('Save'),
          'isDefault' => TRUE,
          'js' => $js,
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );

    $doParentCheck = FALSE;
    if (defined('CIVICRM_MULTISITE') && CIVICRM_MULTISITE) {
      $doParentCheck = ($this->_id && CRM_Core_BAO_Domain::isDomainGroup($this->_id)) ? FALSE : TRUE;
    }

    $options = ['selfObj' => $this,
      'parentGroups' => $parentGroups,
      'doParentCheck' => $doParentCheck,
    ];
    $this->addFormRule(['CRM_Group_Form_Edit', 'formRule'], $options);
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($fields, $fileParams, $options) {
    $errors = [];

    $doParentCheck = $options['doParentCheck'];
    $self = &$options['selfObj'];

    if ($doParentCheck) {
      $parentGroups = $options['parentGroups'];

      $grpRemove = 0;
      foreach ($fields as $key => $val) {
        if (substr($key, 0, 20) == 'remove_parent_group_') {
          $grpRemove++;
        }
      }

      $grpAdd = 0;
      if (CRM_Utils_Array::value('parents', $fields)) {
        $grpAdd++;
      }

      if ((count($parentGroups) >= 1) && (($grpRemove - $grpAdd) >= count($parentGroups))) {
        $errors['parents'] = ts('Make sure at least one parent group is set.');
      }
    }

    // do check for both name and title uniqueness
    if (CRM_Utils_Array::value('title', $fields)) {
      $title = trim($fields['title']);
      $name = CRM_Utils_String::titleToVar($title, 63);
      $query = "
SELECT count(*)
FROM   civicrm_group 
WHERE  (name LIKE %1 OR title LIKE %2) 
AND    id <> %3
";
      $grpCnt = CRM_Core_DAO::singleValueQuery($query, [1 => [$name, 'String'],
          2 => [$title, 'String'],
          3 => [(int)$self->_id, 'Integer'],
        ]);
      if ($grpCnt) {
        $errors['title'] = ts('Group \'%1\' already exists.', [1 => $fields['title']]);
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form when submitted
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    CRM_Utils_System::flushCache('CRM_Core_DAO_Group');

    $updateNestingCache = FALSE;
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Contact_BAO_Group::discard($this->_id);
      CRM_Core_Session::setStatus(ts("The Group '%1' has been deleted.", [1 => $this->_title]));
      $updateNestingCache = TRUE;
    }
    else {
      // store the submitted values in an array
      $params = $this->controller->exportValues($this->_name);

      $params['is_active'] = CRM_Utils_Array::value('is_active', $this->_groupValues, 1);

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $params['id'] = $this->_id;
      }

      if ($this->_action & CRM_Core_Action::UPDATE && isset($this->_groupOrganizationID)) {
        $params['group_organization'] = $this->_groupOrganizationID;
      }

      $customFields = CRM_Core_BAO_CustomField::getFields('Group');
      $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
        $customFields,
        $this->_id,
        'Group'
      );

      if (!empty($params['group_type']) && !empty($this->_smartMarketingTypeId)) {
        foreach($params['group_type'] as $typeId => $dontCare) {
          if ($typeId == $this->_smartMarketingTypeId) {
            // save smart marketing related fields
            $params['is_sync'] = 1;
            if (!empty($params['remote_group_id'])) {
              $params['sync_data'] = json_encode([
                'remote_group_id' => $params['remote_group_id'],
              ]);
            }
            break;
          }
        }
      }


      $group = &CRM_Contact_BAO_Group::create($params);

      /*
             * Remove any parent groups requested to be removed
             */

      if (CRM_Utils_Array::value('parents', $this->_groupValues)) {
        $parentGroupIds = explode(',', $this->_groupValues['parents']);
        foreach ($parentGroupIds as $parentGroupId) {
          if (isset($params["remove_parent_group_$parentGroupId"])) {
            CRM_Contact_BAO_GroupNesting::remove($parentGroupId, $group->id);
            $updateNestingCache = TRUE;
          }
        }
      }

      CRM_Core_Session::setStatus(ts('The Group \'%1\' has been saved.', [1 => $group->title]));

      /*
             * Add context to the session, in case we are adding members to the group
             */

      if ($this->_action & CRM_Core_Action::ADD) {
        $this->set('context', 'amtg');
        $this->set('amtgID', $group->id);

        $session = CRM_Core_Session::singleton();
        $session->pushUserContext(CRM_Utils_System::url('civicrm/group/search', 'reset=1&force=1&context=smog&gid=' . $group->id));
      }
    }

    // update the nesting cache
    if ($updateNestingCache) {

      CRM_Contact_BAO_GroupNestingCache::update();
    }
  }

  /**
   * init first enabled smart marketing group to property
   *
   * @return void
   */
  private function initSmartMarketingGroup() {
    $groupTypes = CRM_Core_OptionGroup::values('group_type');
    foreach($groupTypes as $typeId => $smartMarketingName) {
      if (strstr($smartMarketingName, 'Smart Marketing')) {
        $this->_smartMarketingTypeId = $typeId;
        list($smartMarketingVendor) = explode(' ', $smartMarketingName);
        if (strlen($smartMarketingVendor) > 0) {
          $smartMarketingVendor = ucfirst($smartMarketingVendor);
          $smartMarketingClass = 'CRM_Mailing_External_SmartMarketing_'.$smartMarketingVendor;
          if (class_exists($smartMarketingClass)) {
            $providers = CRM_SMS_BAO_Provider::getProviders(NULL, ['name' => 'CRM_SMS_Provider_'.$smartMarketingVendor]);
            if (!empty($providers)) {
              $provider = reset($providers);
              $this->_smartMarketingService = new $smartMarketingClass($provider['id']);
              return TRUE;
            }
          }
        }
      }
    }
  }
}

