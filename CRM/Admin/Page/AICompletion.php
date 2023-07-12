<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Page for displaying list of AICompletion
 */
class CRM_Admin_Page_AICompletion extends CRM_Core_Page {

  function run() {
    $destination = CRM_Utils_System::url('civicrm/admin/aicompletion',
      'reset=1',
      FALSE, NULL, FALSE
    );

    $destination = urlencode($destination);
    $this->assign('destination', $destination);

    $quota = CRM_AI_BAO_AICompletion::quota();
    $used = ts("Number of usages this month").': '.$quota['used'];
    $remain = ts("Remaining number of times").': '.($quota['max'] - $quota['used']);
    echo $used."<br>".$remain;
    
    $sql = "
SELECT
    id,
    contact_id,
    created_date,
    component,
    ai_role,
    tone_style,
    prompt
FROM
    civicrm_aicompletion
WHERE
    is_template = 1
ORDER BY
    created_date
DESC
";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $displayName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $dao->contact_id, 'display_name');
      $rows[] = array(
        'id' => $dao->id,
        'contact_id' => $dao->contact_id,
        'display_name' => $displayName,
        'created_date' => $dao->created_date,
        'component' => $dao->component,
        'ai_role' => $dao->ai_role,
        'ton_style' => $dao->tone_style,
        'prompt' => htmlspecialchars((string) $dao->prompt, ENT_QUOTES, 'UTF-8'),
        'operation' => '...',
      );
    }

    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('rows', $rows);

    return parent::run();
  }
}
