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
    $destination = CRM_Utils_System::url('civicrm/admin/aicompletion', 'reset=1', FALSE, NULL, FALSE);
    $destination = urlencode($destination);
    $this->assign('destination', $destination);

    // org intro
    $config = CRM_Core_Config::singleton();
    $this->assign('organization_intro', $config->aiOrganizationIntro);

    // quota
    $quota = CRM_AI_BAO_AICompletion::quota();
    $this->assign('usage', $quota);
    $this->assign('chartAICompletionQuota', array(
      'id' => 'chart-pie-with-legend-aicompletion-usage',
      'classes' => array('ct-chart-pie'),
      'selector' => '#chart-pie-with-legend-aicompletion-usage',
      'type' => 'Pie',
      'series' => json_encode(array($quota['used'], $quota['max'])),
      'isFillDonut' => true,
    ));

    // table
    $sql = "
SELECT
    id,
    contact_id,
    created_date,
    component,
    ai_role,
    tone_style,
    context
FROM
    civicrm_aicompletion
ORDER BY
    created_date
DESC
";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $details = CRM_Contact_BAO_Contact::getContactDetails($dao->contact_id);
      $content = preg_replace('/^'.ts('Organization intro').'.*/u', '', $dao->context);
      $rows[] = array(
        'id' => $dao->id,
        'contact_id' => $dao->contact_id,
        'display_name' => $details[0],
        'created_date' => $dao->created_date,
        'component' => ts($dao->component),
        'ai_role' => $dao->ai_role,
        'ton_style' => $dao->tone_style,
        'content' => str_replace("\n", '', $content),
        'operation' => '...',
      );
    }

    $this->assign('rows', $rows);

    return parent::run();
  }
}
