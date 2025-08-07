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

/*
 * variable to assign value to tpl
 *
 */

$_tagGroup = NULL;
class CRM_Contact_Form_Edit_TagsandGroups {

  /**
   * constant to determine which forms we are generating
   *
   * Used by both profile and edit contact
   */
  CONST GROUP = 1, TAG = 2, ALL = 3;

  /**
   * This function is to build form elements
   * params object $form object of the form
   *
   * @param Object  $form        the form object that we are operating on
   * @param int     $contactId   contact id
   * @param int     $type        what components are we interested in
   * @param boolean $visibility  visibility of the field
   * @param string  $groupName   if used for building group block
   * @param string  $tagName     if used for building tag block
   * @param string  $fieldName   this is used in batch profile(i.e to build multiple blocks)
   *
   * @static
   * @access public
   */
  static function buildQuickForm(&$form,
    $contactId = 0,
    $type = CRM_Contact_Form_Edit_TagsandGroups::ALL,
    $visibility = FALSE,
    $isRequired = NULL,
    $groupName = 'Group(s)',
    $tagName = 'Tag(s)',
    $fieldName = NULL
  ) {
    $classContext = get_class($form);
    if ($classContext == 'CRM_Contact_Form_Contact') {
      $form->assign('useSelectBox', TRUE);
    }

    $type = (int ) $type;
    if ($type & CRM_Contact_Form_Edit_TagsandGroups::GROUP) {

      $fName = 'group';
      if ($fieldName) {
        $fName = $fieldName;
      }

      $elements = [];
      $groupID = $form->_grid ?? NULL;
      if ($groupID && $visibility) {
        $ids = '= ' . $groupID;
      }
      else {
        if ($visibility) {
          $group = &CRM_Core_PseudoConstant::allGroup();
        }
        else {
          $group = &CRM_Core_PseudoConstant::group();
        }
        $ids = CRM_Utils_Array::implode(',', array_keys($group));
        $ids = 'IN (' . $ids . ')';
      }

      if ($groupID || !empty($group)) {
        $sql = "
    SELECT   id, title, description, visibility
    FROM     civicrm_group
    WHERE    id $ids
    ORDER BY title
    ";
        $dao = CRM_Core_DAO::executeQuery($sql);
        // $attributes['skiplabel'] = TRUE;
        while ($dao->fetch()) {
          // make sure that this group has public visibility
          if ($visibility && $dao->visibility == 'User and User Admin Only') {
            continue;
          }
          $form->_tagGroup[$fName][$dao->id]['description'] = $dao->description;
          if ($classContext == 'CRM_Contact_Form_Contact') {
            $elements[$dao->id] = $dao->title.'(id:'.$dao->id.')';
          }
          else {
            $elements[] = &$form->addElement('advcheckbox', $dao->id, NULL, $dao->title, $attributes);
          }
        }

        if (!empty($elements)) {
          if ($classContext == 'CRM_Contact_Form_Contact') {
            $form->addSelect($fName, ts($groupName), $elements, ['multiple'=>'multiple']);
            if (!empty($form->_entityId)) {
              $contactGroup = CRM_Contact_BAO_GroupContact::getContactGroup($form->_entityId, 'Added', NULL, FALSE, TRUE);
              if (!empty($contactGroup)) {
                $defaultGroups = [];
                foreach($contactGroup as $gp) {
                  $defaultGroups[] = $gp['group_id'];
                }
                $form->setDefaults([$fName => $defaultGroups]);
              }
            }
          }
          else {
            $form->addGroup($elements, $fName, ts($groupName));
            $form->assign('groupCount', count($elements));
          }
          if ($isRequired) {
            $form->addRule($fName, ts('%1 is a required field.', [1 => $groupName]), 'required');
          }
        }
      }
    }

    if ($type & CRM_Contact_Form_Edit_TagsandGroups::TAG) {
      $fName = 'tag';
      if ($fieldName) {
        $fName = $fieldName;
      }
      $form->_tagGroup[$fName] = 1;
      $elements = [];

      $tag = CRM_Core_BAO_Tag::getTags();

      foreach ($tag as $id => $name) {
        if ($classContext == 'CRM_Contact_Form_Contact') {
          $elements[$id] = $name;
        }
        else {
          $elements[] = &$form->createElement('checkbox', $id, NULL, $name);
        }
      }
      if (!empty($elements)) {
        if ($classContext == 'CRM_Contact_Form_Contact') {
          $form->addSelect($fName, ts($tagName), $elements, ['multiple'=>'multiple']);
          if (!empty($form->_entityId)) {
            $contactTag = CRM_Core_BAO_EntityTag::getTag($form->_entityId);
            if (!empty($contactTag)) {
              $form->setDefaults([$fName => $contactTag]);
            }
          }
        }
        else {
          $form->addGroup($elements, $fName, ts($tagName), '<br />');
          $form->assign('tagCount', count($elements));
        }
      }

      if ($isRequired) {
        $form->addRule($fName, ts('%1 is a required field.', [1 => $tagName]), 'required');
      }

      // build tag widget


      $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_contact');

      CRM_Core_Form_Tag::buildQuickForm($form, $parentNames, 'civicrm_contact', $form->_contactId, FALSE, TRUE);
    }
    $form->assign('tagGroup', $form->_tagGroup);
  }

  /**
   * set defaults for relevant form elements
   *
   * @param int    $id        the contact id
   * @param array  $defaults  the defaults array to store the values in
   * @param int    $type      what components are we interested in
   * @param string $fieldName this is used in batch profile(i.e to build multiple blocks)
   *
   * @return void
   * @access public
   * @static
   */
  static function setDefaults($id, &$defaults, $type = CRM_Contact_Form_Edit_TagsandGroups::ALL, $fieldName = NULL) {
    $type = (int ) $type;
    if ($type & self::GROUP) {
      unset($defaults['group']);
      $fName = 'group';
      if ($fieldName) {
        $fName = $fieldName;
      }


      $contactGroup = &CRM_Contact_BAO_GroupContact::getContactGroup($id, 'Added', NULL, FALSE, TRUE);
      if ($contactGroup) {
        foreach ($contactGroup as $group) {
          $defaults[$fName . "[" . $group['group_id'] . "]"] = 1;
        }
      }
    }

    if ($type & self::TAG) {
      $fName = 'tag';
      if ($fieldName) {
        $fName = $fieldName;
      }


      $contactTag = &CRM_Core_BAO_EntityTag::getTag($id);
      if ($contactTag) {
        foreach ($contactTag as $tag) {
          $defaults[$fName . "[" . $tag . "]"] = 1;
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
   * @return None
   */
  static function setDefaultValues(&$form, &$defaults) {
    $contactEditOptions = $form->get('contactEditOptions');
    if ($form->_action & CRM_Core_Action::ADD) {
      if (CRM_Utils_Array::arrayKeyExists('TagsAndGroups', $contactEditOptions)) {
        // set group and tag defaults if any
        if ($form->_gid) {
          $defaults['group'][$form->_gid] = 1;
        }
        if ($form->_tid) {
          $defaults['tag'][$form->_tid] = 1;
        }
      }
    }
    else {
      if (CRM_Utils_Array::arrayKeyExists('TagsAndGroups', $contactEditOptions)) {
        // set the group and tag ids
        self::setDefaults($form->_contactId, $defaults, self::ALL);
      }
    }
  }
}

