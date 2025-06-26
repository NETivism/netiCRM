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




class CRM_Contribute_BAO_PCP extends CRM_Contribute_DAO_PCP {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_pcpLinks = NULL;
  function __construct() {
    parent::__construct();
  }

  /**
   * function to add or update either a Personal Campaign Page OR a PCP Block
   *
   * @param array $params reference array contains the values submitted by the form
   * @param bool  $pcpBlock if true, create or update PCPBlock, else PCP
   * @access public
   * @static
   *
   * @return object
   */
  static function add(&$params, $pcpBlock = TRUE) {
    if ($pcpBlock) {
      // action is taken depending upon the mode

      $dao = new CRM_Contribute_DAO_PCPBlock();
      $dao->copyValues($params);
      $dao->save();
      return $dao;
    }
    else {

      $dao = new CRM_Contribute_DAO_PCP();
      $dao->copyValues($params);

      // ensure we set status_id since it is a not null field
      // we should change the schema and allow this to be null
      if (!$dao->id &&
        !isset($dao->status_id)
      ) {
        $dao->status_id = 0;
      }

      // set currency for CRM-1496
      if (!isset($dao->currency)) {
        $config = &CRM_Core_Config::singleton();
        $dao->currency = $config->defaultCurrency;
      }

      $dao->save();
      return $dao;
    }
  }

  /**
   * function to get the Display  name of a contact for a PCP
   *
   * @param  int    $id      id for the PCP
   *
   * @return null|string     Dispaly name of the contact if found
   * @static
   * @access public
   */
  static function displayName($id) {
    $id = CRM_Utils_Type::escape($id, 'Integer');

    $query = "
SELECT civicrm_contact.display_name
FROM   civicrm_pcp, civicrm_contact
WHERE  civicrm_pcp.contact_id = civicrm_contact.id
  AND  civicrm_pcp.id = {$id}
";
    return CRM_Core_DAO::singleValueQuery($query, CRM_Core_DAO::$_nullArray);
  }

  /**
   * Function to return PCP  Block info for dashboard
   *
   * @return array     array of Pcp if found
   * @access public
   * @static
   */
  static function getPcpDashboardInfo($contactId) {
    $links = self::pcpLinks();


    $query = "
        SELECT pg.start_date, pg.end_date, pg.title as pageTitle, pcp.id as pcpId, 
               pcp.title as pcpTitle, pcp.status_id as pcpStatusId, cov_status.label as pcpStatus,
               pcpblock.is_tellfriend_enabled as tellfriend, 
               pcpblock.id as blockId, pcp.is_active as pcpActive, pg.id as pageId
        FROM civicrm_contribution_page pg 
        LEFT JOIN civicrm_pcp pcp ON  (pg.id= pcp.contribution_page_id)
        LEFT JOIN civicrm_pcp_block as pcpblock ON ( pg.id = pcpblock.entity_id )
        
        LEFT JOIN civicrm_option_group cog_status ON cog_status.name = 'pcp_status'
        LEFT JOIN civicrm_option_value cov_status
               ON (pcp.status_id = cov_status.value
               AND cog_status.id = cov_status.option_group_id )
        
        INNER JOIN civicrm_contact as ct ON (ct.id = pcp.contact_id  AND pcp.contact_id = %1 )
        WHERE pcpblock.is_active = 1
        ORDER BY pcpStatus, pageTitle";

    $params = [1 => [$contactId, 'Integer']];
    $pcpInfoDao = CRM_Core_DAO::executeQuery($query, $params);
    $pcpInfo = [];
    $hide = $mask = array_sum(array_keys($links['all']));
    $contactPCPPages = [];

    $approvedId = CRM_Core_OptionGroup::getValue('pcp_status', 'Approved', 'name');
    while ($pcpInfoDao->fetch()) {
      $mask = $hide;
      if ($links) {
        $replace = ['pcpId' => $pcpInfoDao->pcpId,
          'pcpBlock' => $pcpInfoDao->blockId,
        ];
      }
      $pcpLink = $links['all'];
      $class = '';

      if ($pcpInfoDao->pcpStatusId != $approvedId || $pcpInfoDao->pcpActive != 1) {
        $class = "disabled";
      }
      if (!$pcpInfoDao->tellfriend || $pcpInfoDao->pcpStatusId != $approvedId || $pcpInfoDao->pcpActive != 1) {
        $mask -= CRM_Core_Action::DETACH;
      }
      if ($pcpInfoDao->pcpActive == 1) {
        $mask -= CRM_Core_Action::ENABLE;
      }
      else {
        $mask -= CRM_Core_Action::DISABLE;
      }
      $action = CRM_Core_Action::formLink($pcpLink, $mask, $replace);
      $pcpInfo[] = [
        'start_date' => $pcpInfoDao->start_date,
        'end_date' => $pcpInfoDao->end_date,
        'pageTitle' => $pcpInfoDao->pageTitle,
        'pcpId' => $pcpInfoDao->pcpId,
        'pcpTitle' => $pcpInfoDao->pcpTitle,
        'pcpStatus' => $pcpInfoDao->pcpStatus,
        'action' => $action,
        'class' => $class,
      ];
      $contactPCPPages[] = $pcpInfoDao->pageId;
    }

    $excludePageClause = NULL;
    if (!empty($contactPCPPages)) {
      $excludePageClause = " AND pg.id NOT IN ( " . CRM_Utils_Array::implode(',', $contactPCPPages) . ") ";
    }

    $query = "
        SELECT pg.id as pageId, pg.title as pageTitle, pg.start_date , 
                  pg.end_date 
        FROM civicrm_contribution_page pg 
        LEFT JOIN civicrm_pcp_block as pcpblock ON ( pg.id = pcpblock.entity_id )
        WHERE pcpblock.is_active = 1 {$excludePageClause}
        ORDER BY pageTitle ASC";

    $pcpBlockDao = CRM_Core_DAO::executeQuery($query);
    $pcpBlock = [];
    $mask = 0;

    while ($pcpBlockDao->fetch()) {
      if ($links) {
        $replace = ['pageId' => $pcpBlockDao->pageId];
      }
      $pcpLink = $links['add'];
      $action = CRM_Core_Action::formLink($pcpLink, $mask, $replace);
      $pcpBlock[] = ['pageId' => $pcpBlockDao->pageId,
        'pageTitle' => $pcpBlockDao->pageTitle,
        'start_date' => $pcpBlockDao->start_date,
        'end_date' => $pcpBlockDao->end_date,
        'action' => $action,
      ];
    }

    return [$pcpBlock, $pcpInfo];
  }

  /**
   * function to show the total amount for Personal Campaign Page on thermometer
   *
   * @param array $pcpId  contains the pcp ID
   *
   * @access public
   * @static
   *
   * @return total amount
   */
  static function thermoMeter($pcpId, $type = 'amount') {
    switch($type) {
      case 'amount':
        $query = "
SELECT SUM(cc.total_amount) as total
FROM civicrm_pcp pcp 
LEFT JOIN civicrm_contribution_soft cs ON ( pcp.id = cs.pcp_id ) 
LEFT JOIN civicrm_contribution cc ON ( cs.contribution_id = cc.id)
WHERE pcp.id = %1 AND cc.contribution_status_id =1 AND cc.is_test = 0";
        break;
      case 'amount_per_contact':
        $query = "
SELECT SUM(cc.total_amount) as total
FROM civicrm_pcp pcp 
LEFT JOIN (SELECT * FROM civicrm_contribution_soft WHERE 1 GROUP BY contact_id ORDER BY contribution_id ASC) cs ON pcp.id = cs.pcp_id  
LEFT JOIN civicrm_contribution cc ON cs.contribution_id = cc.id
WHERE pcp.id = %1 AND cc.contribution_status_id =1 AND cc.is_test = 0";
        break;
      case 'num_contact':
        $query = "
SELECT count(*) FROM (SELECT c.contact_id, 1 as grouping FROM civicrm_contribution c INNER JOIN civicrm_contribution_soft s ON s.contribution_id = c.id WHERE s.pcp_id = %1 AND c.contribution_status_id = 1 AND c.is_test = 0 GROUP BY c.contact_id) g GROUP BY grouping";
        break;
      case 'num_contribution':
        $query = "
SELECT COUNT(cc.total_amount) as total
FROM civicrm_pcp pcp 
LEFT JOIN civicrm_contribution_soft cs ON ( pcp.id = cs.pcp_id ) 
LEFT JOIN civicrm_contribution cc ON ( cs.contribution_id = cc.id)
WHERE pcp.id = %1 AND cc.contribution_status_id =1 AND cc.is_test = 0";
        break;
    }

    $params = [1 => [$pcpId, 'Integer']];
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * function to show the amount, nickname on honor roll
   *
   * @param array $pcpId contains the pcp ID
   *
   * @access public
   * @static
   *
   * @return array $honor
   */
  static function honorRoll($pcpId) {
    $query = "
            SELECT cc.id, cs.pcp_roll_nickname, cs.pcp_personal_note,
                   SUM(cc.total_amount) as total_amount, cc.currency, COUNT(cc.id) as total_count, cc.contribution_recur_id, MAX(cc.receive_date) as last_receive_date
            FROM civicrm_contribution cc 
                 INNER JOIN civicrm_contribution_soft cs ON cc.id = cs.contribution_id
            WHERE cs.pcp_id = {$pcpId}
                  AND cs.pcp_display_in_roll = 1 
                  AND contribution_status_id = 1 
                  AND is_test = 0
            GROUP BY cc.contact_id, cc.contribution_recur_id, cs.pcp_roll_nickname";
    $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    $honor = [];

    while ($dao->fetch()) {
      $honor[$dao->id]['nickname'] = ucwords($dao->pcp_roll_nickname);
      $honor[$dao->id]['total_amount'] = CRM_Utils_Money::format($dao->total_amount, $dao->currency);
      $honor[$dao->id]['total_count'] = $dao->total_count;
      $honor[$dao->id]['is_recur'] = $dao->contribution_recur_id ? 1 : 0;
      $honor[$dao->id]['personal_note'] = $dao->pcp_personal_note;
      $honor[$dao->id]['last_receive_date'] = $dao->last_receive_date;
    }
    return $honor;
  }

  /**
   * Get action links
   *
   * @return array (reference) of action links
   * @static
   */
  static function &pcpLinks() {
    if (!(self::$_pcpLinks)) {
      $deleteExtra = ts('Are you sure you want to delete this Personal Campaign Page?') . '\n' . ts('This action cannot be undone.');

      self::$_pcpLinks['add'] = [
        CRM_Core_Action::ADD => ['name' => ts('Create a Personal Campaign Page'),
          'url' => 'civicrm/contribute/campaign',
          'qs' => 'action=add&reset=1&pageId=%%pageId%%',
          'title' => ts('Configure'),
        ],
      ];

      self::$_pcpLinks['all'] = [
        CRM_Core_Action::UPDATE => ['name' => ts('Edit Your Page'),
          'url' => 'civicrm/contribute/pcp/info',
          'qs' => 'action=update&reset=1&id=%%pcpId%%',
          'title' => ts('Configure'),
        ],
        CRM_Core_Action::DETACH => ['name' => ts('Tell Friends'),
          'url' => 'civicrm/friend',
          'qs' => 'eid=%%pcpId%%&blockId=%%pcpBlock%%&reset=1&page=pcp',
          'title' => ts('Tell Friends'),
        ],
        CRM_Core_Action::BROWSE => ['name' => ts('Update Contact Information'),
          'url' => 'civicrm/contribute/pcp/info',
          'qs' => 'action=browse&reset=1&id=%%pcpId%%',
          'title' => ts('Update Contact Information'),
        ],
        CRM_Core_Action::ENABLE => ['name' => ts('Enable'),
          'url' => 'civicrm/contribute/pcp',
          'qs' => 'action=enable&reset=1&id=%%pcpId%%',
          'title' => ts('Enable'),
        ],
        CRM_Core_Action::DISABLE => ['name' => ts('Disable'),
          'url' => 'civicrm/contribute/pcp',
          'qs' => 'action=disable&reset=1&id=%%pcpId%%',
          'title' => ts('Disable'),
        ],
        CRM_Core_Action::DELETE => ['name' => ts('Delete'),
          'url' => 'civicrm/contribute/pcp',
          'qs' => 'action=delete&reset=1&id=%%pcpId%%',
          'extra' => 'onclick = "return confirm(\'' . $deleteExtra . '\');"',
          'title' => ts('Delete'),
        ],
      ];
    }
    return self::$_pcpLinks;
  }

  /**
   * Function to Delete the campaign page
   *
   * @param int $id campaign page id
   *
   * @return null
   * @access public
   * @static
   *
   */
  static function deleteById($id = NULL) {

    CRM_Utils_Hook::pre('delete', 'Campaign', $id, CRM_Core_DAO::$_nullArray);


    $transaction = new CRM_Core_Transaction();

    // delete from pcp table
    $pcp = new CRM_Contribute_DAO_PCP();
    $pcp->id = $id;
    $pcp->delete();

    $transaction->commit();

    CRM_Utils_Hook::post('delete', 'Campaign', $id, $pcp);
  }

  /**
   * Approve / Reject / Back to draft, enable / disable page and send email notification
   *
   * @param int $id campaign page id
   * @param int|bool $statusId bool for set is_active, number for set status_id
   *
   * @return null
   * @access public
   * @static
   *
   */
  static function setIsActive($id, $statusId) {
    if ($statusId === TRUE || $statusId === FALSE) {
      return self::setDisable($id, $statusId);
    }

    $pcpStatus = CRM_Contribute_PseudoConstant::pcpStatus();
    if (!in_array($statusId, array_keys($pcpStatus))) {
      return;
    }

    CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_PCP', $id, 'status_id', $statusId);

    $pcpTitle = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_PCP', $id, 'title');
    $pcpStatus = $pcpStatus[$statusId];

    CRM_Core_Session::setStatus(ts("%1 status has been updated to %2.", [
      1 => $pcpTitle,
      2 => $pcpStatus,
    ]));

    // send status change mail
    $result = self::sendStatusUpdate($id, $statusId);

    if ($result) {
      CRM_Core_Session::setStatus(ts("A notification email has been sent to the supporter."));
    }
    else {
      $fixUrl = CRM_Utils_System::url("civicrm/admin/domain", 'action=update&reset=1');
      CRM_Core_Session::setStatus(ts('The site administrator needs to enter a valid \'FROM Email Address\' in <a href="%1">Administer CiviCRM &raquo; Configure &raquo; Domain Information</a>. The email address used may need to be a valid mail account with your email service provider.', [1 => $fixUrl]), TRUE, 'warning');
    }
  }

  /**
   * Function to send notfication email to supporter
   * 1. when their PCP status is changed by site admin.
   * 2. when supporter initially creates a Personal Campaign Page ($isInitial set to true).
   *
   * @param int $pcpId      campaign page id
   * @param int $newStatus  pcp status id
   * @param int $isInitial  is it the first time, campaign page has been created by the user
   *
   * @return null
   * @access public
   * @static
   *
   */
  static function sendStatusUpdate($pcpId, $newStatus, $isInitial = FALSE) {
    $tplName = $isInitial ? 'pcp_supporter_notify' : 'pcp_status_change';
    $pcpStatus = CRM_Contribute_PseudoConstant::pcpStatus('name');
    $pcpStatusLabel = CRM_Contribute_PseudoConstant::pcpStatus();
    $config = CRM_Core_Config::singleton();

    if (!isset($pcpStatus[$newStatus])) {
      return FALSE;
    }

    //set loginUrl
    $loginUrl = $config->userFrameworkBaseURL;
    switch (ucfirst($config->userFramework)) {
      case 'Joomla':
        $loginUrl = str_replace('administrator/', '', $loginUrl);
        $loginUrl .= 'index.php?option=com_user&view=login';
        break;

      case 'Drupal':
        $loginUrl .= 'user';
        break;
    }

    // used in subject templates
    $contribPageTitle = self::getPcpContributionPageTitle($pcpId);

    $tplParams = [
      'loginUrl' => $loginUrl,
      'contribPageTitle' => $contribPageTitle,
    ];

    //get the default domain email address.
    list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();
    if (!$domainEmailAddress || $domainEmailAddress == 'info@FIXME.ORG') {
      return FALSE;
    }

    $receiptFrom = CRM_Utils_Mail::formatRFC822Email($domainEmailName, $domainEmailAddress);

    // get recipient (supporter) name and email
    $params = ['id' => $pcpId];
    CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_PCP', $params, $pcpInfo);

    // do_not_notify check
    $detail = CRM_Contact_BAO_Contact::getContactDetails($pcpInfo['contact_id']);
    if (!empty($detail[5])) {
      CRM_Core_Error::debug_log_message("Skipped email notify {$tplName} for contact {$pcpInfo['contact_id']} due to do_not_notify marked");
      $message = ts('Email has NOT been sent to %1 contact(s) - communication preferences specify DO NOT NOTIFY OR valid Email is NOT present.', [1 => '1']);
      CRM_Core_Session::singleton()->setStatus($message);
      return;
    }
    list($name, $address) = CRM_Contact_BAO_Contact_Location::getEmailDetails($pcpInfo['contact_id']);

    // get pcp block info
    list($blockId, $eid) = self::getPcpBlockEntityId($pcpId);
    $params = ['id' => $blockId];
    CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_PCPBlock', $params, $pcpBlockInfo);

    // assign urls required in email template
    if ($pcpStatus[$newStatus] == 'Approved') {
      $tplParams['isTellFriendEnabled'] = $pcpBlockInfo['is_tellfriend_enabled'];
      if ($pcpBlockInfo['is_tellfriend_enabled']) {
        $pcpTellFriendURL = CRM_Utils_System::url('civicrm/friend',
          "reset=1&eid=$pcpId&blockId=$blockId&page=pcp",
          TRUE, NULL, FALSE, TRUE
        );
        $tplParams['pcpTellFriendURL'] = $pcpTellFriendURL;
      }
    }
    $pcpInfoURL = CRM_Utils_System::url('civicrm/contribute/pcp/info',
      "reset=1&id=$pcpId",
      TRUE, NULL, FALSE, TRUE
    );
    $tplParams['pcpInfoURL'] = $pcpInfoURL;
    $cc = NULL;
    if ($emails = CRM_Utils_Array::value('notify_email', $pcpBlockInfo)) {
      $emailArray = explode(',', $emails);
      $tplParams['pcpNotifyEmailAddress'] = $emailArray[0];
      $cc = CRM_Utils_Array::implode(',', $emailArray);
    }
    // get appropriate message based on status
    $tplParams['pcpStatus'] = $pcpStatus[$newStatus];
    $tplParams['pcpStatusLabel'] = $pcpStatusLabel[$newStatus];
    $tplParams['pcpTitle'] = $pcpInfo['title'];


    list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplates::sendTemplate(
      [
        'groupName' => 'msg_tpl_workflow_contribution',
        'valueName' => $tplName,
        'contactId' => $pcpInfo['contact_id'],
        'tplParams' => $tplParams,
        'from' => $receiptFrom,
        'toName' => $name,
        'toEmail' => $address,
        'cc' => $cc,
      ]
    );
    return $sent;
  }

  /**
   * Function to Enable / Disable the campaign page
   *
   * @param int $id campaign page id
   *
   * @return null
   * @access public
   * @static
   *
   */
  static function setDisable($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_PCP', $id, 'is_active', $is_active);
  }

  /**
   * Function to get pcp block is active
   *
   * @param int $id campaign page id
   *
   * @return int
   * @access public
   * @static
   *
   */
  static function getStatus($pcpId) {
    $query = "
     SELECT pb.is_active 
     FROM civicrm_pcp pcp 
          LEFT JOIN civicrm_pcp_block pb ON ( pcp.contribution_page_id = pb.entity_id )
          LEFT JOIN civicrm_contribution_page as cp ON ( cp.id =  pcp.contribution_page_id )
     WHERE pcp.id = %1";

    $params = [1 => [$pcpId, 'Integer']];
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * Function to get pcp block is enabled for contribution page
   *
   * @param int $id contribution page id
   *
   * @return String
   * @access public
   * @static
   *
   */
  static function getPcpBlockStatus($pageId) {
    $query = "
     SELECT pb.link_text as linkText
     FROM civicrm_contribution_page cp 
          LEFT JOIN civicrm_pcp_block pb ON ( cp.id = pb.entity_id AND pb.entity_table = 'civicrm_contribution_page' )
     WHERE pb.is_active = 1 AND cp.id = %1";

    $params = [1 => [$pageId, 'Integer']];
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * Function to get email is enabled for supporter's profile
   *
   * @param int $id supporter's profile id
   *
   * @return boolean
   * @access public
   * @static
   *
   */
  static function checkEmailProfile($profileId) {
    $query = "
SELECT field_name
FROM civicrm_uf_field
WHERE field_name like 'email%' And is_active = 1 And uf_group_id = %1";

    $params = [1 => [$profileId, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if (!$dao->fetch()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Function to obtain the title of contribution page associated with a pcp
   *
   * @param int $id campaign page id
   *
   * @return int
   * @access public
   * @static
   *
   */
  static function getPcpContributionPageTitle($pcpId) {
    $query = "
SELECT cp.title 
FROM civicrm_pcp pcp 
LEFT JOIN civicrm_contribution_page as cp ON ( cp.id =  pcp.contribution_page_id )
WHERE pcp.id = %1";

    $params = [1 => [$pcpId, 'Integer']];
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * Get image urls
   *
   * @param int $pcpId
   * @return array
   */
  public static function getPcpImages($pcpId) {
    $dao = CRM_Core_DAO::executeQuery("SELECT file_id FROM civicrm_entity_file WHERE entity_table = 'civicrm_pcp' AND entity_id = %1", [
      1 => [$pcpId, 'Integer'],
    ]);
    $files = [];
    while($dao->fetch()) {
      $files[] = CRM_Core_BAO_File::url($dao->file_id, $pcpId, 'civicrm_pcp');
    }
    return $files;
  }

  /**
   * Function to get pcp block & entity id given pcp id
   *
   * @param int $id campaign page id
   *
   * @return String
   * @access public
   * @static
   *
   */
  static function getPcpBlockEntityId($pcpId) {
    $query = "
SELECT pb.id as pcpBlockId, pb.entity_id
FROM civicrm_pcp pcp 
LEFT JOIN civicrm_pcp_block pb ON ( pb.entity_id = pcp.contribution_page_id AND pb.entity_table = 'civicrm_contribution_page' )
WHERE pcp.id = %1";

    $params = [1 => [$pcpId, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->fetch()) {
      return [$dao->pcpBlockId, $dao->entity_id];
    }

    return [];
  }

  /**
   * Function to get supporter profile id
   *
   * @param int $contributionPageId contribution page id
   *
   * @return int
   * @access public
   *
   */
  public static function getSupporterProfileId($contributionPageId) {
    $query = "
SELECT pcp.supporter_profile_id
FROM civicrm_pcp_block pcp 
INNER JOIN civicrm_uf_group ufgroup 
      ON pcp.supporter_profile_id = ufgroup.id 
WHERE pcp.entity_id = %1 
      AND ufgroup.is_active = 1";
    $params = [1 => [$contributionPageId, 'Integer']];
    if (!$supporterProfileId = CRM_Core_DAO::singleValueQuery($query, $params)) {
       return CRM_Core_Error::statusBounce(ts('Supporter profile is not set for this Personal Campaign Page or the profile is disabled. Please contact the site administrator if you need assistance.'));
    }
    else {
      return $supporterProfileId;
    }
  }
}

