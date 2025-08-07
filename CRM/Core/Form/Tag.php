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
 * This class generates form element for free tag widget
 *
 */
class CRM_Core_Form_Tag {
  public $_entityTagValues;

  /**
   * Function to build tag widget if correct parent is passed
   *
   * @param object  $form form object
   * @param string  $parentName parent name ( tag name)
   * @param string  $entityTable entitytable 'eg: civicrm_contact'
   * @param int     $entityId    entityid  'eg: contact id'
   *
   * @return void
   * @access public
   * @static
   */
  static function buildQuickForm(&$form, $parentNames, $entityTable, $entityId = NULL, $skipTagCreate = FALSE, $skipEntityAction = FALSE) {
    $tagset = $form->_entityTagValues = [];

    foreach ($parentNames as & $parentNameItem) {
      // get the parent id for tag list input for keyword
      $parentId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $parentNameItem, 'id', 'name');

      // check if parent exists
      $entityTags = [];
      if ($parentId) {
        $tagsetItem = 'parentId_' . $parentId;
        $tagset[$tagsetItem]['parentName'] = $parentNameItem;
        $tagset[$tagsetItem]['parentID'] = $parentId;

        //tokeninput url
        $tagUrl = CRM_Utils_System::url('civicrm/ajax/taglist',
          "parentId={$parentId}",
          FALSE, NULL, FALSE
        );

        $tagset[$tagsetItem]['tagUrl'] = $tagUrl;
        $tagset[$tagsetItem]['entityTable'] = $entityTable;
        $tagset[$tagsetItem]['skipTagCreate'] = $skipTagCreate;
        $tagset[$tagsetItem]['skipEntityAction'] = $skipEntityAction;
        $tagset[$tagsetItem]['tagElementName'] = "taglist[{$parentId}]";

        $form->add('text', "taglist[{$parentId}]", NULL);

        if ($entityId) {
          $tagset[$tagsetItem]['entityId'] = $entityId;

          $entityTags = CRM_Core_BAO_EntityTag::getChildEntityTags($parentId, $entityId, $entityTable);
        }
        elseif (!empty($form->_submitValues['taglist']) &&
          CRM_Utils_Array::value($parentId, $form->_submitValues['taglist'])
        ) {

          $allTags = CRM_Core_PseudoConstant::tag();
          $tagIds = explode(',', $form->_submitValues['taglist'][$parentId]);
          foreach ($tagIds as $tagId) {
            if (is_numeric($tagId)) {
              $tagName = $allTags[$tagId];
            }
            else {
              $tagName = $tagId;
            }

            $entityTags[$tagId] = ['id' => $tagId,
              'name' => $tagName,
            ];
          }
        }
        elseif (!empty($form->_formValues['contact_tags'])) {

          $contactTags = CRM_Core_BAO_Tag::getTagsUsedFor('civicrm_contact', TRUE, FALSE, $parentId);

          foreach (array_keys($form->_formValues['contact_tags']) as $tagId) {
            if (CRM_Utils_Array::value($tagId, $contactTags)) {
              $tagName = $tagId;
              if (is_numeric($tagId)) {
                $tagName = $contactTags[$tagId];
              }

              $entityTags[$tagId] = ['id' => $tagId,
                'name' => $tagName,
              ];
            }
          }
        }

        if (!empty($entityTags)) {
          // assign as simple array for display in smarty
          $tagset[$tagsetItem]['entityTagsArray'] = $entityTags;
          // assign as json for js widget
          $tagset[$tagsetItem]['entityTags'] = json_encode(array_values($entityTags));

          if (!empty($form->_entityTagValues)) {
            $form->_entityTagValues = CRM_Utils_Array::arrayMerge($entityTags, $form->_entityTagValues);
          }
          else {
            $form->_entityTagValues = $entityTags;
          }
        }
      }
    }

    $form->assign('tagset', $tagset);
  }

  /**
   * Function to save entity tags when it is not save used AJAX
   *
   */
  static function postProcess(&$params, $entityId, $entityTable = 'civicrm_contact', &$form = NULL) {
    foreach ($params as $value) {
      if (!$value) {
        continue;
      }
      $tagsIDs = explode(',', $value);
      $insertValues = [];
      $insertSQL = NULL;
      if (!empty($tagsIDs)) {
        foreach ($tagsIDs as $tagId) {
          if (is_numeric($tagId) && !CRM_Utils_Array::arrayKeyExists($tagId, $form->_entityTagValues)) {
            $insertValues[] = "( {$tagId}, {$entityId}, '{$entityTable}' ) ";
          }
        }

        if (!empty($insertValues)) {
          $insertSQL = 'INSERT INTO civicrm_entity_tag ( tag_id, entity_id, entity_table ) VALUES ' . CRM_Utils_Array::implode(', ', $insertValues) . ';';
          CRM_Core_DAO::executeQuery($insertSQL);
        }
      }
    }
  }
}

