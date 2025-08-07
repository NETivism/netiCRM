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





class CRM_Upgrade_Page_Upgrade extends CRM_Core_Page {
  function preProcess() {
    parent::preProcess();
  }

  function run() {
    // lets get around the time limit issue if possible for upgrades
    if (!ini_get('safe_mode')) {
      set_time_limit(0);
    }

    $latestVer = CRM_Utils_System::version();
    $currentVer = CRM_Core_BAO_Domain::version();
    if (!$currentVer) {
      CRM_Core_Error::fatal(ts('Version information missing in civicrm database.'));
    }
    elseif (stripos($currentVer, 'upgrade')) {
      CRM_Core_Error::fatal(ts('Database check failed - the database looks to have been partially upgraded. You may want to reload the database with the backup and try the upgrade process again.'));
    }
    if (!$latestVer) {
      CRM_Core_Error::fatal(ts('Version information missing in civicrm codebase.'));
    }

    // hack to make past ver compatible /w new incremental upgrade process
    $convertVer = ['2.1' => '2.1.0',
      '2.2' => '2.2.alpha1',
      '2.2.alph' => '2.2.alpha3',
      // since 3.1.1 had domain.version set as 3.1.0
      '3.1.0' => '3.1.1',
    ];
    if (isset($convertVer[$currentVer])) {
      $currentVer = $convertVer[$currentVer];
    }

    // since version is suppose to be in valid format at this point, especially after conversion ($convertVer),
    // lets do a pattern check -
    if (!CRM_Utils_System::isVersionFormatValid($currentVer)) {
      CRM_Core_Error::fatal(ts('Database is marked with invalid version format. You may want to investigate this before you proceed further.'));
    }

    // This could be removed in later rev
    if ($currentVer == '2.1.6') {
      $config = CRM_Core_Config::singleton();
      // also cleanup the templates_c directory
      $config->cleanup(1, FALSE);

      if ($config->userFramework !== 'Standalone') {
        // clean the session
        $session = CRM_Core_Session::singleton();
        $session->reset(2);
      }
    }
    // end of hack

    CRM_Utils_System::setTitle(ts('Upgrade CiviCRM to Version %1',
        [1 => $latestVer]
      ));

    $upgrade = new CRM_Upgrade_Form();

    $template = CRM_Core_Smarty::singleton();
    $template->assign('pageTitle', ts('Upgrade CiviCRM to Version %1',
        [1 => $latestVer]
      ));
    $template->assign('menuRebuildURL',
      CRM_Utils_System::url('civicrm/menu/rebuild', 'reset=1')
    );
    $template->assign('cancelURL',
      CRM_Utils_System::url('civicrm/dashboard', 'reset=1')
    );

    if (version_compare($currentVer, $latestVer) > 0) {
      // DB version number is higher than codebase being upgraded to. This is unexpected condition-fatal error.
      $dbToolsLink = CRM_Utils_System::docURL2("Database Troubleshooting Tools", TRUE);
      $error = ts('Your database is marked with an unexpected version number: %1. The automated upgrade to version %2 can not be run - and the %2 codebase may not be compatible with your database state. You will need to determine the correct version corresponding to your current database state. The database tools utility at %3 may be helpful. You may want to revert to the codebase you were using prior to beginning this upgrade until you resolve this problem.',
        [1 => $currentVer, 2 => $latestVer, 3 => $dbToolsLink]
      );
      CRM_Core_Error::fatal($error);
    }
    elseif (version_compare($currentVer, $latestVer) == 0) {
      $message = ts('Your database has already been upgraded to CiviCRM %1',
        [1 => $latestVer]
      );
      $template->assign('upgraded', TRUE);
    }
    else {
      $message = ts('CiviCRM upgrade was successful.');
      if ($latestVer == '3.2.alpha1') {
        $message .= '<br />' . ts("We have reset the COUNTED flag to false for the event participant status 'Pending from incomplete transaction'. This change ensures that people who have a problem during registration can try again.");
      }
      elseif ($latestVer == '3.2.beta3' && (version_compare($currentVer, '3.1.alpha1') >= 0)) {

        $subTypes = CRM_Contact_BAO_ContactType::subTypes();

        if (is_array($subTypes) && !empty($subTypes)) {
          $config = CRM_Core_Config::singleton();
          $subTypeTemplates = [];

          if (isset($config->customTemplateDir)) {
            foreach ($subTypes as $key => $subTypeName) {
              $customContactSubTypeEdit = $config->customTemplateDir . "CRM/Contact/Form/Edit/" . $subTypeName . ".tpl";
              $customContactSubTypeView = $config->customTemplateDir . "CRM/Contact/Page/View/" . $subTypeName . ".tpl";
              if (file_exists($customContactSubTypeEdit) || file_exists($customContactSubTypeView)) {
                $subTypeTemplates[$subTypeName] = $subTypeName;
              }
            }
          }

          foreach ($subTypes as $key => $subTypeName) {
            $customContactSubTypeEdit = $config->templateDir . "CRM/Contact/Form/Edit/" . $subTypeName . ".tpl";
            $customContactSubTypeView = $config->templateDir . "CRM/Contact/Page/View/" . $subTypeName . ".tpl";
            if (file_exists($customContactSubTypeEdit) || file_exists($customContactSubTypeView)) {
              $subTypeTemplates[$subTypeName] = $subTypeName;
            }
          }

          if (!empty($subTypeTemplates)) {
            $subTypeTemplates = CRM_Utils_Array::implode(',', $subTypeTemplates);
            $message .= '<br />' . ts('You are using custom template for contact subtypes: %1.', [1 => $subTypeTemplates]) . '<br />' . ts('You need to move these subtype templates to the SubType directory in %1 and %2 respectively.', [1 => 'CRM/Contact/Form/Edit', 2 => 'CRM/Contact/Page/View']);
          }
        }
      }
      elseif ($latestVer == '3.2.beta4') {
        $statuses = ['New', 'Current', 'Grace', 'Expired', 'Pending', 'Cancelled', 'Deceased'];
        $sql = "
SELECT  count( id ) as statusCount 
  FROM  civicrm_membership_status 
 WHERE  name IN ( '" . CRM_Utils_Array::implode("' , '", $statuses) . "' ) ";
        $count = CRM_Core_DAO::singleValueQuery($sql);
        if ($count < count($statuses)) {
          $message .= '<br />' . ts("One or more Membership Status Rules was disabled during the upgrade because it did not match a recognized status name. if custom membership status rules were added to this site - review the disabled statuses and re-enable any that are still needed (Administer > CiviMember > Membership Status Rules).");
        }
      }

      $template->assign('currentVersion', $currentVer);
      $template->assign('newVersion', $latestVer);
      $template->assign('upgradeTitle', ts('Upgrade CiviCRM from v %1 To v %2',
          [1 => $currentVer, 2 => $latestVer]
        ));
      $template->assign('upgraded', FALSE);

      if (CRM_Utils_Array::value('upgrade', $_POST)) {
        $revisions = $upgrade->getRevisionSequence();
        foreach ($revisions as $rev) {
          // proceed only if $currentVer < $rev
          if (version_compare($currentVer, $rev) < 0) {
            // as soon as we start doing anything we append ".upgrade" to version.
            // this also helps detect any partial upgrade issues
            $upgrade->setVersion($rev . '.upgrade');

            $phpFunctionName = 'upgrade_' . str_replace('.', '_', $rev);

            // follow old upgrade process for all version
            // below 3.2.alpha1
            if (version_compare($rev, '3.2.alpha1') < 0) {
              if (is_callable([$this, $phpFunctionName])) {
                $functionName = $this->$phpFunctionName;
                $functionName($rev);
              }
              else {
                $upgrade->processSQL($rev);
              }
            }
            else {
              // new upgrade process from version
              // 3.2.alpha1
              $versionObject = $upgrade->incrementalPhpObject($rev);

              // predb check for major release.
              if ($upgrade->checkVersionRelease($rev, 'alpha1')) {
                if (!(is_callable([$versionObject, 'verifyPreDBstate']))) {
                  CRM_Core_Error::fatal("verifyPreDBstate method was not found for $rev");
                }

                $error = NULL;
                if (!($versionObject->verifyPreDBstate($error))) {
                  if (!isset($error)) {
                    $error = "post-condition failed for current upgrade for $rev";
                  }
                  CRM_Core_Error::fatal($error);
                }
              }

              if (is_callable([$versionObject, $phpFunctionName])) {
                $versionObject->$phpFunctionName($rev);
              }
              else {
                $upgrade->processSQL($rev);
              }
            }

            // after an successful intermediate upgrade, set the complete version
            $upgrade->setVersion($rev);
          }
        }
        $upgrade->setVersion($latestVer);
        $template->assign('upgraded', TRUE);

        // also cleanup the templates_c directory
        $config = CRM_Core_Config::singleton();
        $config->cleanup(1, FALSE);

        // clear db caching
        $config->clearDBCache();

        // clear temporary tables
        $config->clearTempTables();

        // clean the session. Note: In case of standalone this makes the user logout.
        // So skip this step for standalone.
        if ($config->userFramework !== 'Standalone') {
          $session = CRM_Core_Session::singleton();
          $session->reset(2);
        }
      }
    }

    $template->assign('message', $message);
    $content = $template->fetch('CRM/common/success.tpl');
    return CRM_Utils_System::theme($content);
  }

  function upgrade_2_2_alpha1($rev) {
    for ($stepID = 1; $stepID <= 4; $stepID++) {
      $formName = "CRM_Upgrade_TwoTwo_Form_Step{$stepID}";
      $form = new $formName( );

      $error = NULL;
      if (!$form->verifyPreDBState($error)) {
        if (!isset($error)) {
          $error = "pre-condition failed for current upgrade step $stepID, rev $rev";
        }
        CRM_Core_Error::fatal($error);
      }

      if ($stepID == 4) {
        return;
      }

      $template = CRM_Core_Smarty::singleton();

      $eventFees = [];
      $query = "SELECT og.id ogid FROM civicrm_option_group og WHERE og.name LIKE  %1";
      $params = [1 => ['civicrm_event_page.amount%', 'String']];
      $dao = CRM_Core_DAO::executeQuery($query, $params);
      while ($dao->fetch()) {
        $eventFees[$dao->ogid] = $dao->ogid;
      }
      $template->assign('eventFees', $eventFees);

      $form->upgrade();

      if (!$form->verifyPostDBState($error)) {
        if (!isset($error)) {
          $error = "post-condition failed for current upgrade step $stepID, rev $rev";
        }
        CRM_Core_Error::fatal($error);
      }
    }
  }

  function upgrade_2_1_2($rev) {
    $formName = "CRM_Upgrade_TwoOne_Form_TwoOneTwo";
    $form = new $formName( $rev );

    $error = NULL;
    if (!$form->verifyPreDBState($error)) {
      if (!isset($error)) {
        $error = "pre-condition failed for current upgrade for $rev";
      }
      CRM_Core_Error::fatal($error);
    }

    $form->upgrade();

    if (!$form->verifyPostDBState($error)) {
      if (!isset($error)) {
        $error = "post-condition failed for current upgrade for $rev";
      }
      CRM_Core_Error::fatal($error);
    }
  }

  /**
   * This function should check if if need to skip current sql file
   * Name of this function will change according to the latest release
   *
   */
  function upgrade_2_2_alpha3($rev) {
    // skip processing sql file, if fresh install -
    if (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'mail_protocol', 'id', 'name')) {
      $upgrade = new CRM_Upgrade_Form();
      $upgrade->processSQL($rev);
    }
    return TRUE;
  }

  function upgrade_2_2_beta1($rev) {
    if (!CRM_Core_DAO::checkFieldExists('civicrm_pcp_block', 'notify_email')) {
      $template = CRM_Core_Smarty::singleton();
      $template->assign('notifyAbsent', TRUE);
    }
    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }

  function upgrade_2_2_beta2($rev) {
    $template = CRM_Core_Smarty::singleton();
    if (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue',
        'CRM_Contact_Form_Search_Custom_ZipCodeRange', 'id', 'name'
      )) {
      $template->assign('customSearchAbsentAll', TRUE);
    }
    elseif (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue',
        'CRM_Contact_Form_Search_Custom_MultipleValues', 'id', 'name'
      )) {
      $template->assign('customSearchAbsent', TRUE);
    }
    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }

  function upgrade_2_2_beta3($rev) {
    $template = CRM_Core_Smarty::singleton();
    if (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'custom_data_type', 'id', 'name')) {
      $template->assign('customDataType', TRUE);
    }

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }

  function upgrade_3_0_alpha1($rev) {


    $threeZero = new CRM_Upgrade_ThreeZero_ThreeZero();

    $error = NULL;
    if (!$threeZero->verifyPreDBState($error)) {
      if (!isset($error)) {
        $error = 'pre-condition failed for current upgrade for 3.0.alpha2';
      }
      CRM_Core_Error::fatal($error);
    }

    $threeZero->upgrade($rev);
  }

  function upgrade_3_1_alpha1($rev) {


    $threeOne = new CRM_Upgrade_ThreeOne_ThreeOne();

    $error = NULL;
    if (!$threeOne->verifyPreDBState($error)) {
      if (!isset($error)) {
        $error = 'pre-condition failed for current upgrade for 3.0.alpha2';
      }
      CRM_Core_Error::fatal($error);
    }

    $threeOne->upgrade($rev);
  }

  function upgrade_2_2_7($rev) {
    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
    $sql = "UPDATE civicrm_report_instance 
                       SET form_values = REPLACE(form_values,'#',';') ";
    CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);

    // make report component enabled by default

    $domain = new CRM_Core_DAO_Domain();
    $domain->selectAdd();
    $domain->selectAdd('config_backend');
    $domain->find(TRUE);
    if ($domain->config_backend) {
      $defaults = unserialize($domain->config_backend);

      if (is_array($defaults['enableComponents'])) {
        $compId = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_component WHERE name = 'CiviReport'");
        if ($compId) {
          $defaults['enableComponents'][] = 'CiviReport';
          $defaults['enableComponentIDs'][] = $compId;

          CRM_Core_BAO_ConfigSetting::add($defaults);
        }
      }
    }
  }

  function upgrade_3_0_2($rev) {

    $template = CRM_Core_Smarty::singleton();

    //check whether upgraded from 2.1.x or 2.2.x
    $inboundEmailID = CRM_Core_OptionGroup::getValue('activity_type', 'Inbound Email', 'name');

    if (!empty($inboundEmailID)) {
      $template->assign('addInboundEmail', FALSE);
    }
    else {
      $template->assign('addInboundEmail', TRUE);
    }

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }

  function upgrade_3_0_4($rev) {
    //make sure 'Deceased' membership status present in db,CRM-5636
    $template = CRM_Core_Smarty::singleton();

    $addDeceasedStatus = FALSE;
    $sql = "SELECT max(id) FROM civicrm_membership_status where name = 'Deceased'";
    if (!CRM_Core_DAO::singleValueQuery($sql)) {
      $addDeceasedStatus = TRUE;
    }
    $template->assign('addDeceasedStatus', $addDeceasedStatus);

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }

  function upgrade_3_1_0($rev) {
    // upgrade all roles who have 'access CiviEvent' permission, to also have
    // newly added permission 'edit_all_events', CRM-5472
    $config = &CRM_Core_Config::singleton();
    if ($config->userFramework == 'Drupal') {
      $roles = user_roles(FALSE, 'access CiviEvent');
      if (!empty($roles)) {
        db_query('UPDATE {permission} SET perm = CONCAT( perm, \', edit all events\') WHERE rid IN (' . CRM_Utils_Array::implode(',', array_keys($roles)) . ')');
      }
    }

    //make sure 'Deceased' membership status present in db,CRM-5636
    $template = &CRM_Core_Smarty::singleton();

    $addDeceasedStatus = FALSE;
    $sql = "SELECT max(id) FROM civicrm_membership_status where name = 'Deceased'";
    if (!CRM_Core_DAO::singleValueQuery($sql)) {
      $addDeceasedStatus = TRUE;
    }
    $template->assign('addDeceasedStatus', $addDeceasedStatus);

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }

  function upgrade_3_1_3($rev) {

    $threeOne = new CRM_Upgrade_ThreeOne_ThreeOne();
    $threeOne->upgrade_3_1_3();

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }

  function upgrade_3_1_4($rev) {

    $threeOne = new CRM_Upgrade_ThreeOne_ThreeOne();
    $threeOne->upgrade_3_1_4();

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }
}

