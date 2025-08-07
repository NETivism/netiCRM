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
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
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
class CRM_Upgrade_Incremental_php_ThreeThree {
  function verifyPreDBstate(&$errors) {
    return TRUE;
  }

  function upgrade_3_3_alpha1($rev) {
    $config = CRM_Core_Config::singleton();
    if ($config->userFramework == 'Drupal') {
      // CRM-6426 - make civicrm profiles permissioned on drupal my account

      CRM_Utils_System_Drupal::updateCategories();
    }

    // CRM-6846
    // insert name column for custom field table.
    // make sure name for custom field, group and
    // profile should be unique and properly munged.
    $colQuery = 'ALTER TABLE `civicrm_custom_field` ADD `name` VARCHAR( 64 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `custom_group_id` ';
    CRM_Core_DAO::executeQuery($colQuery, CRM_Core_DAO::$_nullArray, TRUE, NULL, FALSE, FALSE);



    $customFldCntQuery = 'select count(*) from civicrm_custom_field where name like %1 and id != %2';
    $customField = new CRM_Core_DAO_CustomField();
    $customField->selectAdd();
    $customField->selectAdd('id, label');
    $customField->find();
    while ($customField->fetch()) {
      $name = CRM_Utils_String::munge($customField->label, '_', 64);
      $fldCnt = CRM_Core_DAO::singleValueQuery($customFldCntQuery,
        [1 => [$name, 'String'],
          2 => [$customField->id, 'Integer'],
        ], TRUE, FALSE
      );
      if ($fldCnt) {
        $name = CRM_Utils_String::munge("{$name}_" . rand(), '_', 64);
      }
      $customFieldQuery = "
Update `civicrm_custom_field`
SET `name` = %1
WHERE id = %2
";
      $customFieldParams = [1 => [$name, 'String'],
        2 => [$customField->id, 'Integer'],
      ];
      CRM_Core_DAO::executeQuery($customFieldQuery, $customFieldParams, TRUE, NULL, FALSE, FALSE);
    }
    $customField->free();


    $customGrpCntQuery = 'select count(*) from civicrm_custom_group where name like %1 and id != %2';
    $customGroup = new CRM_Core_DAO_CustomGroup();
    $customGroup->selectAdd();
    $customGroup->selectAdd('id, title');
    $customGroup->find();
    while ($customGroup->fetch()) {
      $name = CRM_Utils_String::munge($customGroup->title, '_', 64);
      $grpCnt = CRM_Core_DAO::singleValueQuery($customGrpCntQuery,
        [1 => [$name, 'String'],
          2 => [$customGroup->id, 'Integer'],
        ]
      );
      if ($grpCnt) {
        $name = CRM_Utils_String::munge("{$name}_" . rand(), '_', 64);
      }
      CRM_Core_DAO::setFieldValue('CRM_Core_DAO_CustomGroup', $customGroup->id, 'name', $name);
    }
    $customGroup->free();


    $ufGrpCntQuery = 'select count(*) from civicrm_uf_group where name like %1 and id != %2';
    $ufGroup = new CRM_Core_DAO_UFGroup();
    $ufGroup->selectAdd();
    $ufGroup->selectAdd('id, title');
    $ufGroup->find();
    while ($ufGroup->fetch()) {
      $name = CRM_Utils_String::munge($ufGroup->title, '_', 64);
      $ufGrpCnt = CRM_Core_DAO::singleValueQuery($ufGrpCntQuery,
        [1 => [$name, 'String'],
          2 => [$ufGroup->id, 'Integer'],
        ]
      );
      if ($ufGrpCnt) {
        $name = CRM_Utils_String::munge("{$name}_" . rand(), '_', 64);
      }
      CRM_Core_DAO::setFieldValue('CRM_Core_DAO_UFGroup', $ufGroup->id, 'name', $name);
    }
    $ufGroup->free();

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);

    // now modify the config so that the directories are stored in option group/value
    // CRM-6914
    $params = [];
    CRM_Core_BAO_ConfigSetting::add($params);
  }

  function upgrade_3_3_beta1($rev) {
    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);

    // CRM-6902
    // Add column price_field_value_id in civicrm_line_item.
    // Do not drop option_group_id column now since we need it to
    // update line items.
    $updateLineItem1 = "ALTER TABLE civicrm_line_item ADD COLUMN price_field_value_id int(10) unsigned default NULL;";
    CRM_Core_DAO::executeQuery($updateLineItem1);







    $priceFieldDAO = new CRM_Price_DAO_Field();
    $priceFieldDAO->find();
    $ids = [];
    while ($priceFieldDAO->fetch()) {

      $opGroupDAO = new CRM_Core_DAO_OptionGroup();
      $opGroupDAO->name = 'civicrm_price_field.amount.' . $priceFieldDAO->id;

      if (!$opGroupDAO->find(TRUE)) {
        $opGroupDAO->free();
        continue;
      }

      $opValueDAO = new CRM_Core_DAO_OptionValue();
      $opValueDAO->option_group_id = $opGroupDAO->id;
      $opValueDAO->find();

      while ($opValueDAO->fetch()) {
        // FIX ME: not migrating description(?), there will
        // be a field description for each option.
        $fieldValue = ['price_field_id' => $priceFieldDAO->id,
          'label' => $opValueDAO->label,
          'name' => CRM_Utils_String::munge($opValueDAO->label, '_', 64),
          'amount' => $opValueDAO->name,
          'weight' => $opValueDAO->weight,
          'is_default' => $opValueDAO->is_default,
          'is_active' => $opValueDAO->is_active,
        ];

        if ($priceFieldDAO->count) {
          // Migrate Participant Counts on option level.
          // count of each option will be the same
          // as earlier field count.
          $fieldValue['count'] = $priceFieldDAO->count;
        }

        $fieldValueDAO = CRM_Price_BAO_FieldValue::add($fieldValue, $ids);

        $lineItemDAO = new CRM_Price_DAO_LineItem();
        $lineItemDAO->option_group_id = $opGroupDAO->id;
        $lineItemDAO->label = $opValueDAO->label;
        $lineItemDAO->unit_price = $opValueDAO->name;

        $labelFound = $priceFound = FALSE;

        // check with label and amount
        if (!$lineItemDAO->find(TRUE)) {
          $lineItemDAO->free();
          $lineItemDAO = new CRM_Price_DAO_LineItem();
          $lineItemDAO->option_group_id = $opGroupDAO->id;
          $lineItemDAO->label = $opValueDAO->label;

          // check with label only
          if ($lineItemDAO->find(TRUE)) {
            $labelFound = TRUE;
          }
        }
        else {
          $labelFound = TRUE;
          $priceFound = TRUE;
        }

        $lineItemDAO->free();

        // update civicrm_line_item for price_field_value_id.
        // Used query to avoid line by line update.
        if ($labelFound || $priceFound) {
          $lineItemParams = [1 => [$fieldValueDAO->id, 'Integer'],
            2 => [$opValueDAO->label, 'String'],
          ];
          $updateLineItems = "UPDATE civicrm_line_item SET price_field_value_id = %1 WHERE label = %2";
          if ($priceFound) {
            $lineItemParams[3] = [$opValueDAO->name, 'Float'];
            $updateLineItems .= " AND unit_price = %3";
          }
          CRM_Core_DAO::executeQuery($updateLineItems, $lineItemParams);
        }
      }

      $opGroupDAO->delete();
      $opValueDAO->free();
      $opGroupDAO->free();
    }

    $priceFieldDAO->free();

    // Now drop option_group_id column from civicrm_line_item
    $updateLineItem2 = "ALTER TABLE civicrm_line_item DROP option_group_id,
                           ADD CONSTRAINT `FK_civicrm_price_field_value_id` FOREIGN KEY (price_field_value_id) REFERENCES civicrm_price_field_value(id) ON DELETE SET NULL;";
    CRM_Core_DAO::executeQuery($updateLineItem2, [], TRUE, NULL, FALSE, FALSE);

    $updatePriceField = "ALTER TABLE civicrm_price_field DROP count";
    CRM_Core_DAO::executeQuery($updatePriceField, [], TRUE, NULL, FALSE, FALSE);

    // as the table 'civicrm_price_field' is localised and column 'count' is dropped
    // after the views are rebuild, we need to rebuild views to avoid invalid refrence of table.
    if ($upgrade->multilingual) {

      CRM_Core_I18n_Schema::rebuildMultilingualSchema($upgrade->locales, $rev);
    }
  }

  function upgrade_3_3_beta3($rev) {
    // get the duplicate Ids of line item entries
    $dupeLineItemIds = [];
    $fields = ['entity_table', 'entity_id', 'price_field_id', 'price_field_value_id'];

    $mainLineItem = new CRM_Price_BAO_LineItem();
    $mainLineItem->find(TRUE);
    while ($mainLineItem->fetch()) {
      $dupeLineItem = new CRM_Price_BAO_LineItem();
      foreach ($fields as $fld) $dupeLineItem->$fld = $mainLineItem->$fld;
      $dupeLineItem->find(TRUE);
      $dupeLineItem->addWhere("id != $mainLineItem->id");
      while ($dupeLineItem->fetch()) {
        $dupeLineItemIds[$dupeLineItem->id] = $dupeLineItem->id;
      }
      $dupeLineItem->free();
    }
    $mainLineItem->free();

    //clean line item table.
    if (!empty($dupeLineItemIds)) {
      $sql = 'DELETE FROM civicrm_line_item WHERE id IN ( ' . CRM_Utils_Array::implode(', ', $dupeLineItemIds) . ' )';
      CRM_Core_DAO::executeQuery($sql);
    }

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }

  function upgrade_3_3_0($rev) {
    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);

    //CRM-7123 -lets activate needful languages.
    $config = CRM_Core_Config::singleton();
    $locales = [];
    if (is_dir($config->gettextResourceDir)) {
      $dir = opendir($config->gettextResourceDir);
      while ($filename = readdir($dir)) {
        if (preg_match('/^[a-z][a-z]_[A-Z][A-Z]$/', $filename)) {
          $locales[$filename] = $filename;
        }
      }
      closedir($dir);
    }

    if (isset($config->languageLimit) && !empty($config->languageLimit)) {
      //get all already enabled and all l10n languages.
      $locales = array_merge(array_values($locales), array_keys($config->languageLimit));
    }

    if (!empty($locales)) {
      $sql = '
    UPDATE  civicrm_option_value val
INNER JOIN  civicrm_option_group grp ON ( grp.id = val.option_group_id )
       SET  val.is_active = 1
     WHERE  grp.name = %1
       AND  val.name IN ( ' . "'" . CRM_Utils_Array::implode("', '", $locales) . "' )";

      CRM_Core_DAO::executeQuery($sql,
        [1 => ['languages', 'String']],
        TRUE, NULL, FALSE, FALSE
      );
    }
  }
}

