<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */
class CRM_Mailing_BAO_Mailing extends CRM_Mailing_DAO_Mailing {

  public $delivered;
  public $entity_table;
  public $group_id;
  public $mailing_id;
  public $group_type;
  /**
   * @var string
   */
  public $mailing_name;
  public $group_hidden;
  public $queue;
  public $count;
  /**
   * An array that holds the complete templates
   * including any headers or footers that need to be prepended
   * or appended to the body
   */
  protected $preparedTemplates = NULL;

  /**
   * An array that holds the complete templates
   * including any headers or footers that need to be prepended
   * or appended to the body
   */
  protected $templates = NULL;

  /**
   * An array that holds the tokens that are specifically found in our text and html bodies
   */
  protected $tokens = NULL;

  /**
   * An array that holds the tokens that are specifically found in our text and html bodies
   */
  protected $flattenedTokens = NULL;

  /**
   * The header associated with this mailing
   */
  protected $header = NULL;

  /**
   * The footer associated with this mailing
   */
  protected $footer = NULL;

  /**
   * The HTML content of the message
   */
  protected $html = NULL;

  /**
   * The text content of the message
   */
  protected $text = NULL;

  /**
   * Cached BAO for the domain
   */
  protected $_domain = NULL;

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  static function &getRecipientsCount($job_id, $mailing_id = NULL) {
    // need this for backward compatibility, so we can get count for old mailings
    // please do not use this function if possible
    $eq = self::getRecipients($job_id, $mailing_id);
    return $eq->N;
  }

  // note that $job_id is used only as a variable in the temp table construction
  // and does not play a role in the queries generated
  static function &getRecipients($job_id, $mailing_id = NULL, $offset = NULL, $limit = NULL, $storeRecipients = FALSE, $dedupeEmail = FALSE, $mode = NULL) {
    $mailingGroup = new CRM_Mailing_DAO_Group();

    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_Job::getTableName();
    $mg = CRM_Mailing_DAO_Group::getTableName();
    $eq = CRM_Mailing_Event_DAO_Queue::getTableName();
    $ed = CRM_Mailing_Event_DAO_Delivered::getTableName();
    $eb = CRM_Mailing_Event_DAO_Bounce::getTableName();
    $eo = CRM_Mailing_Event_DAO_Opened::getTableName();
    $ec = CRM_Mailing_Event_DAO_TrackableURLOpen::getTableName();
    $eu = CRM_Mailing_Event_DAO_Unsubscribe::getTableName();

    $email = CRM_Core_DAO_Email::getTableName();
    $contact = CRM_Contact_DAO_Contact::getTableName();


    $group = CRM_Contact_DAO_Group::getTableName();
    $g2contact = CRM_Contact_DAO_GroupContact::getTableName();

    /* Create a temp table for contact exclusion */


    $mailingGroup->query(
      "CREATE TEMPORARY TABLE X_$job_id 
            (contact_id int primary key) 
            ENGINE=HEAP"
    );

    /* Add all the members of groups excluded from this mailing to the temp
         * table */


    $excludeSubGroup = "INSERT INTO        X_$job_id (contact_id)
                    SELECT  DISTINCT    $g2contact.contact_id
                    FROM                $g2contact
                    INNER JOIN          $mg
                            ON          $g2contact.group_id = $mg.entity_id AND $mg.entity_table = '$group'
                    WHERE
                                        $mg.mailing_id = {$mailing_id}
                        AND             $g2contact.status = 'Added'
                        AND             $mg.group_type = 'Exclude'";
    $mailingGroup->query($excludeSubGroup);

    /* Add all unsubscribe members of base group from this mailing to the temp
         * table */


    $unSubscribeBaseGroup = "INSERT INTO        X_$job_id (contact_id)
                    SELECT  DISTINCT    $g2contact.contact_id
                    FROM                $g2contact
                    INNER JOIN          $mg
                            ON          $g2contact.group_id = $mg.entity_id AND $mg.entity_table = '$group'
                    WHERE
                                        $mg.mailing_id = {$mailing_id}
                        AND             $g2contact.status = 'Removed'
                        AND             $mg.group_type = 'Base'";
    $mailingGroup->query($unSubscribeBaseGroup);

    /* Add all the (intended) recipients of an excluded prior mailing to
         * the temp table */


    $excludeSubMailing = "INSERT IGNORE INTO X_$job_id (contact_id)
                    SELECT  DISTINCT    $eq.contact_id
                    FROM                $eq
                    INNER JOIN          $job
                            ON          $eq.job_id = $job.id
                    INNER JOIN          $mg
                            ON          $job.mailing_id = $mg.entity_id AND $mg.entity_table = '$mailing'
                    WHERE
                                        $mg.mailing_id = {$mailing_id}
                        AND             $job.is_test   = 0
                        AND             $mg.group_type = 'Exclude'";
    $mailingGroup->query($excludeSubMailing);

    /* exclude all clicked mailing in specific mailnig */
    $excludeClickedMailing = "INSERT IGNORE INTO X_$job_id (contact_id)
                    SELECT  DISTINCT    $eq.contact_id
                    FROM                $eq
                    INNER JOIN          $job
                            ON          $eq.job_id = $job.id
                    INNER JOIN          $mg
                            ON          $job.mailing_id = $mg.entity_id AND $mg.entity_table = '$ec'
                    INNER JOIN          $ec
                            ON          $ec.event_queue_id = $eq.id
                    WHERE
                                        $mg.mailing_id = {$mailing_id}
                        AND             $job.is_test   = 0
                        AND             $mg.group_type = 'Exclude'";
    $mailingGroup->query($excludeClickedMailing);

    /* exclude all opened mailing in specific mailnig */
    $excludeOpenedMailing = "INSERT IGNORE INTO X_$job_id (contact_id)
                    SELECT  DISTINCT    $eq.contact_id
                    FROM                $eq
                    INNER JOIN          $job
                            ON          $eq.job_id = $job.id
                    INNER JOIN          $mg
                            ON          $job.mailing_id = $mg.entity_id AND $mg.entity_table = '$eo'
                    INNER JOIN          $eo
                            ON          $eo.event_queue_id = $eq.id
                    WHERE
                                        $mg.mailing_id = {$mailing_id}
                        AND             $job.is_test   = 0
                        AND             $mg.group_type = 'Exclude'";
    $mailingGroup->query($excludeOpenedMailing);

    // refs #22150, exclude unsubscribed mail when included in opened / click
    $excludeMailingUnsubsribed = "INSERT IGNORE INTO X_$job_id (contact_id)
                    SELECT  DISTINCT    $eq.contact_id
                    FROM                $eq
                    INNER JOIN          $job
                            ON          $eq.job_id = $job.id
                    INNER JOIN          $mg
                            ON          $job.mailing_id = $mg.entity_id AND $mg.entity_table = '$mailing'
                    INNER JOIN          $eu
                            ON          $eu.event_queue_id = $eq.id
                    WHERE
                                        $mg.mailing_id = {$mailing_id}
                        AND             $mg.group_type = 'Include'";
    $mailingGroup->query($excludeMailingUnsubsribed);

    $excludeOpenedUnsubscribed = "INSERT IGNORE INTO X_$job_id (contact_id)
                    SELECT  DISTINCT    $eq.contact_id
                    FROM                $eq
                    INNER JOIN          $job
                            ON          $eq.job_id = $job.id
                    INNER JOIN          $mg
                            ON          $job.mailing_id = $mg.entity_id AND $mg.entity_table = '$eo'
                    INNER JOIN          $eu
                            ON          $eu.event_queue_id = $eq.id
                    WHERE
                                        $mg.mailing_id = {$mailing_id}
                        AND             $mg.group_type = 'Include'";
    $mailingGroup->query($excludeOpenedUnsubscribed);

    $excludeClickedUnsubscribed = "INSERT IGNORE INTO X_$job_id (contact_id)
                    SELECT  DISTINCT    $eq.contact_id
                    FROM                $eq
                    INNER JOIN          $job
                            ON          $eq.job_id = $job.id
                    INNER JOIN          $mg
                            ON          $job.mailing_id = $mg.entity_id AND $mg.entity_table = '$ec'
                    INNER JOIN          $eu
                            ON          $eu.event_queue_id = $eq.id
                    WHERE
                                        $mg.mailing_id = {$mailing_id}
                        AND             $mg.group_type = 'Include'";
    $mailingGroup->query($excludeClickedUnsubscribed);

    // get all the saved searches AND hierarchical groups
    // and load them in the cache
    $sql = "
SELECT     $group.id, $group.cache_date, $group.saved_search_id, $group.children
FROM       $group
INNER JOIN $mg ON $mg.entity_id = $group.id
WHERE      $mg.entity_table = '$group'
  AND      $mg.group_type = 'Exclude'
  AND      $mg.mailing_id = {$mailing_id}
  AND      ( saved_search_id != 0
   OR        saved_search_id IS NOT NULL
   OR        children IS NOT NULL )
";

    $groupDAO = CRM_Core_DAO::executeQuery($sql);
    while ($groupDAO->fetch()) {
      if ($groupDAO->cache_date == NULL) {

        CRM_Contact_BAO_GroupContactCache::load($groupDAO);
      }

      $smartGroupExclude = "
INSERT IGNORE INTO X_$job_id (contact_id) 
SELECT c.contact_id
FROM   civicrm_group_contact_cache c
WHERE  c.group_id = {$groupDAO->id}
";
      $mailingGroup->query($smartGroupExclude);
    }

    /* From #22536. If $dedupeEmail, Collect all same email contact to exclude group */
    if($dedupeEmail){

      $mailingGroup->query(
        "CREATE TEMPORARY TABLE X_{$job_id}_2
              (contact_id int primary key)
              ENGINE=HEAP"
      );

      $mailingGroup->query(
        "INSERT IGNORE INTO X_{$job_id}_2 (contact_id)
          SELECT contact_id FROM X_$job_id"
      );

      $sql = "
        INSERT IGNORE INTO X_$job_id (contact_id)
        SELECT DISTINCT e2.contact_id
        FROM X_{$job_id}_2 x
        INNER JOIN civicrm_email e ON x.contact_id = e.contact_id
        LEFT JOIN civicrm_email e2 ON e.email = e2.email AND e.contact_id != e2.contact_id
        WHERE e2.contact_id IS NOT NULL
        ";
      $mailingGroup->query($sql);
    }

    /* Get all the group contacts we want to include */


    $mailingGroup->query(
      "CREATE TEMPORARY TABLE I_$job_id 
            (email_id int, contact_id int primary key)
            ENGINE=HEAP"
    );

    /* Get the group contacts, but only those which are not in the
         * exclusion temp table */



    /* Get the emails with no override */



    $query = "REPLACE INTO       I_$job_id (email_id, contact_id)

                    SELECT DISTINCT     $email.id as email_id,
                                        $contact.id as contact_id
                    FROM                $email
                    INNER JOIN          $contact
                            ON          $email.contact_id = $contact.id
                    INNER JOIN          $g2contact
                            ON          $contact.id = $g2contact.contact_id
                    INNER JOIN          $mg
                            ON          $g2contact.group_id = $mg.entity_id
                                AND     $mg.entity_table = '$group'
                    LEFT JOIN           X_$job_id
                            ON          $contact.id = X_$job_id.contact_id
                    WHERE           
                                       ($mg.group_type = 'Include')
                        AND             $mg.search_id IS NULL
                        AND             $g2contact.status = 'Added'
                        AND             $g2contact.email_id IS null
                        AND             $contact.do_not_email = 0
                        AND             $contact.is_opt_out = 0
                        AND             $contact.is_deceased = 0
                        AND            ($email.is_bulkmail = 1 OR $email.is_primary = 1)
                        AND             $email.email IS NOT NULL
                        AND             $email.email != ''
                        AND             $email.on_hold = 0
                        AND             $mg.mailing_id = {$mailing_id}
                        AND             X_$job_id.contact_id IS null
                    ORDER BY $email.is_bulkmail";
    $mailingGroup->query($query);

    /* Query prior mailings */
    $mailingGroup->query(
      "REPLACE INTO       I_$job_id (email_id, contact_id)
                    SELECT DISTINCT     $email.id as email_id,
                                        $contact.id as contact_id
                    FROM                $email
                    INNER JOIN          $contact
                            ON          $email.contact_id = $contact.id
                    INNER JOIN          $eq
                            ON          $eq.contact_id = $contact.id
                    INNER JOIN          $job
                            ON          $eq.job_id = $job.id
                    INNER JOIN          $mg
                            ON          $job.mailing_id = $mg.entity_id AND $mg.entity_table = '$mailing'
                    LEFT JOIN           X_$job_id
                            ON          $contact.id = X_$job_id.contact_id
                    WHERE
                                       ($mg.group_type = 'Include')
                        AND             $contact.do_not_email = 0
                        AND             $contact.is_opt_out = 0
                        AND             $contact.is_deceased = 0
                        AND            ($email.is_bulkmail = 1 OR $email.is_primary = 1)
                        AND             $email.on_hold = 0
                        AND             $mg.mailing_id = {$mailing_id}
                        AND             X_$job_id.contact_id IS null
                    ORDER BY $email.is_bulkmail"
    );

    /* Query opened mailings */
    $includeOpened = "REPLACE INTO       I_$job_id (email_id, contact_id)
                    SELECT DISTINCT     $email.id as email_id,
                                        $contact.id as contact_id
                    FROM                $email
                    INNER JOIN          $contact
                            ON          $email.contact_id = $contact.id
                    INNER JOIN          $eq
                            ON          $eq.contact_id = $contact.id
                    INNER JOIN          $job
                            ON          $eq.job_id = $job.id
                    INNER JOIN          $mg
                            ON          $job.mailing_id = $mg.entity_id AND $mg.entity_table = '$eo'
                    INNER JOIN          $eo
                            ON          $eo.event_queue_id = $eq.id
                    LEFT JOIN           X_$job_id
                            ON          $contact.id = X_$job_id.contact_id
                    WHERE
                                       ($mg.group_type = 'Include')
                        AND             $contact.do_not_email = 0
                        AND             $contact.is_opt_out = 0
                        AND             $contact.is_deceased = 0
                        AND            ($email.is_bulkmail = 1 OR $email.is_primary = 1)
                        AND             $email.on_hold = 0
                        AND             $mg.mailing_id = {$mailing_id}
                        AND             X_$job_id.contact_id IS null
                    ORDER BY $email.is_bulkmail";
    $mailingGroup->query($includeOpened);

    /* Query clicked mailings */
    $includeClicked = "REPLACE INTO       I_$job_id (email_id, contact_id)
                    SELECT DISTINCT     $email.id as email_id,
                                        $contact.id as contact_id
                    FROM                $email
                    INNER JOIN          $contact
                            ON          $email.contact_id = $contact.id
                    INNER JOIN          $eq
                            ON          $eq.contact_id = $contact.id
                    INNER JOIN          $job
                            ON          $eq.job_id = $job.id
                    INNER JOIN          $mg
                            ON          $job.mailing_id = $mg.entity_id AND $mg.entity_table = '$ec'
                    INNER JOIN          $ec
                            ON          $ec.event_queue_id = $eq.id
                    LEFT JOIN           X_$job_id
                            ON          $contact.id = X_$job_id.contact_id
                    WHERE
                                       ($mg.group_type = 'Include')
                        AND             $contact.do_not_email = 0
                        AND             $contact.is_opt_out = 0
                        AND             $contact.is_deceased = 0
                        AND            ($email.is_bulkmail = 1 OR $email.is_primary = 1)
                        AND             $email.on_hold = 0
                        AND             $mg.mailing_id = {$mailing_id}
                        AND             X_$job_id.contact_id IS null
                    ORDER BY $email.is_bulkmail";
    $mailingGroup->query($includeClicked);

    $sql = "
SELECT     $group.id, $group.cache_date, $group.saved_search_id, $group.children
FROM       $group
INNER JOIN $mg ON $mg.entity_id = $group.id
WHERE      $mg.entity_table = '$group'
  AND      $mg.group_type = 'Include'
  AND      $mg.search_id IS NULL
  AND      $mg.mailing_id = {$mailing_id}
  AND      ( saved_search_id != 0
   OR        saved_search_id IS NOT NULL
   OR        children IS NOT NULL )
";

    $groupDAO = CRM_Core_DAO::executeQuery($sql);
    while ($groupDAO->fetch()) {
      if ($groupDAO->cache_date == NULL) {

        CRM_Contact_BAO_GroupContactCache::load($groupDAO);
      }

      $smartGroupInclude = "
INSERT IGNORE INTO I_$job_id (email_id, contact_id) 
SELECT     e.id as email_id, c.id as contact_id
FROM       civicrm_contact c
INNER JOIN civicrm_email e                ON e.contact_id         = c.id
INNER JOIN civicrm_group_contact_cache gc ON gc.contact_id        = c.id
LEFT  JOIN X_$job_id                      ON X_$job_id.contact_id = c.id
WHERE      gc.group_id = {$groupDAO->id}
  AND      c.do_not_email = 0
  AND      c.is_opt_out = 0
  AND      c.is_deceased = 0
  AND      (e.is_bulkmail = 1 OR e.is_primary = 1)
  AND      e.on_hold = 0
  AND      X_$job_id.contact_id IS null
ORDER BY   e.is_bulkmail
";
      $mailingGroup->query($smartGroupInclude);
    }

    /**
     * Construct the filtered search queries
     */
    $query = "
SELECT search_id, search_args, entity_id
FROM   $mg
WHERE  $mg.search_id IS NOT NULL
AND    $mg.mailing_id = {$mailing_id}
";
    $dao = CRM_Core_DAO::executeQuery($query);

    while ($dao->fetch()) {
      $customSQL = CRM_Contact_BAO_SearchCustom::civiMailSQL($dao->search_id,
        $dao->search_args,
        $dao->entity_id
      );
      $query = "REPLACE INTO       I_$job_id (email_id, contact_id)
                         $customSQL";
      $mailingGroup->query($query);
    }

    /* Get the emails with only location override */


    $query = "REPLACE INTO       I_$job_id (email_id, contact_id)
                    SELECT DISTINCT     $email.id as local_email_id,
                                        $contact.id as contact_id
                    FROM                $email
                    INNER JOIN          $contact
                            ON          $email.contact_id = $contact.id
                    INNER JOIN          $g2contact
                            ON          $contact.id = $g2contact.contact_id
                    INNER JOIN          $mg
                            ON          $g2contact.group_id = $mg.entity_id
                    LEFT JOIN           X_$job_id
                            ON          $contact.id = X_$job_id.contact_id
                    WHERE           
                                        $mg.entity_table = '$group'
                        AND             $mg.group_type = 'Include'
                        AND             $g2contact.status = 'Added'
                        AND             $g2contact.email_id is null
                        AND             $contact.do_not_email = 0
                        AND             $contact.is_opt_out = 0
                        AND             $contact.is_deceased = 0
                        AND             ($email.is_bulkmail = 1 OR $email.is_primary = 1)
                        AND             $email.on_hold = 0
                        AND             $mg.mailing_id = {$mailing_id}
                        AND             X_$job_id.contact_id IS null
                    ORDER BY $email.is_bulkmail";
    $mailingGroup->query($query);

    /* Get the emails with full override */


    $mailingGroup->query(
      "REPLACE INTO       I_$job_id (email_id, contact_id)
                    SELECT DISTINCT     $email.id as email_id,
                                        $contact.id as contact_id
                    FROM                $email
                    INNER JOIN          $g2contact
                            ON          $email.id = $g2contact.email_id
                    INNER JOIN          $contact
                            ON          $contact.id = $g2contact.contact_id
                    INNER JOIN          $mg
                            ON          $g2contact.group_id = $mg.entity_id
                    LEFT JOIN           X_$job_id
                            ON          $contact.id = X_$job_id.contact_id
                    WHERE           
                                        $mg.entity_table = '$group'
                        AND             $mg.group_type = 'Include'
                        AND             $g2contact.status = 'Added'
                        AND             $g2contact.email_id IS NOT null
                        AND             $contact.do_not_email = 0
                        AND             $contact.is_opt_out = 0
                        AND             $contact.is_deceased = 0
                        AND             ($email.is_bulkmail = 1 OR $email.is_primary = 1)
                        AND             $email.on_hold = 0
                        AND             $mg.mailing_id = {$mailing_id}
                        AND             X_$job_id.contact_id IS null
                    ORDER BY $email.is_bulkmail"
    );

    $results = [];

    $eq = new CRM_Mailing_Event_BAO_Queue();


    list($aclFrom, $aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause();
    $aclWhere = $aclWhere ? "WHERE {$aclWhere}" : '';
    $limitString = NULL;
    if ($limit && $offset !== NULL) {
      $limitString = "LIMIT $offset, $limit";
    }

    if ($storeRecipients && $mailing_id) {
      $sql = "
DELETE 
FROM   civicrm_mailing_recipients
WHERE  mailing_id = %1
";
      $params = [1 => [$mailing_id, 'Integer']];
      CRM_Core_DAO::executeQuery($sql, $params);

      // CRM-3975
      $groupJoin = '';
      $groupBy = "GROUP BY i.email_id";
      if ($dedupeEmail) {
        $groupJoin = " INNER JOIN civicrm_email e ON e.id = i.email_id";
        $groupBy = "GROUP BY e.email";
      }

      $sql = "
INSERT INTO civicrm_mailing_recipients ( mailing_id, contact_id, email_id )
SELECT %1, i.contact_id, i.email_id
FROM       civicrm_contact contact_a
INNER JOIN I_$job_id i ON contact_a.id = i.contact_id AND contact_a.is_opt_out = 0 AND contact_a.do_not_email = 0 AND contact_a.is_deceased = 0
           $groupJoin
           {$aclFrom}
           {$aclWhere}
           {$groupBy}
ORDER BY   i.contact_id, i.email_id
";
      CRM_Core_DAO::executeQuery($sql, $params);
      
      // refs #30407, prevent duplicate entry for mailing recipients
      $sql = "SELECT id FROM civicrm_mailing_recipients WHERE mailing_id = %1 GROUP BY email_id HAVING count(id) > 1";
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      if ($dao->N) {
        $ids = [];
        while($dao->fetch()) {
          $ids[] = $dao->id;
        }
        $sql = "DELETE FROM civicrm_mailing_recipients WHERE mailing_id = %1 AND id IN(".CRM_Utils_Array::implode(',', $ids).")";
        CRM_Core_DAO::executeQuery($sql, $params);
      }
    }

    /* Delete the temp table */


    $mailingGroup->reset();
    $mailingGroup->query("DROP TEMPORARY TABLE X_$job_id");
    $mailingGroup->query("DROP TEMPORARY TABLE I_$job_id");

    return $eq;
  }

  private function _getMailingGroupIds($type = 'Include') {
    $mailingGroup = new CRM_Mailing_DAO_Group();
    $group = CRM_Contact_DAO_Group::getTableName();
    if (!isset($this->id)) {
      // we're just testing tokens, so return any group
      $query = "SELECT   id AS entity_id
                      FROM     $group
                      ORDER BY id
                      LIMIT 1";
    }
    else {
      $query = "SELECT entity_id
                      FROM   $mg
                      WHERE  mailing_id = {$this->id}
                      AND    group_type = '$type'
                      AND    entity_table = '$group'";
    }
    $mailingGroup->query($query);

    $groupIds = [];
    while ($mailingGroup->fetch()) {
      $groupIds[] = $mailingGroup->entity_id;
    }

    return $groupIds;
  }

  /**
   *
   * Returns the regex patterns that are used for preparing the text and html templates
   *
   * @access private
   *
   **/
  protected function &getPatterns($onlyHrefs = FALSE) {

    $patterns = [];

    $protos = '(https?)';
    $letters = '\w';
    $gunk = '\{\}/#~:.?+=&;%@!\,\-';
    $punc = '.:?\-';
    $any = "{$letters}{$gunk}{$punc}";
    if ($onlyHrefs) {
      $pattern = "<a\s+[^>]*\\bhref[ ]*=[ ]*([\"'])?(($protos:[$any]+(?=[$punc]*[^$any]|$)))([\"'])?";
    }
    else {
      $pattern = "\\b($protos:[$any]+(?=[$punc]*[^$any]|$))";
    }

    $patterns[] = $pattern;
    $patterns[] = '\\\\\{\w+\.\w+\\\\\}|\{\{\w+\.\w+\}\}';
    $patterns[] = '\{\w+\.\w+\}';

    $patterns = '{' . join('|', $patterns) . '}imu';

    return $patterns;
  }

  /**
   *  returns an array that denotes the type of token that we are dealing with
   *  we use the type later on when we are doing a token replcement lookup
   *
   *  @param string $token       The token for which we will be doing adata lookup
   *
   *  @return array $funcStruct  An array that holds the token itself and the type.
   *                             the type will tell us which function to use for the data lookup
   *                             if we need to do a lookup at all
   */
  function &getDataFunc($token) {
    static $_categories = NULL;
    static $_categoryString = NULL;
    if (!$_categories) {
      $_categories = [
        'domain' => NULL,
        'action' => NULL,
        'mailing' => NULL,
        'contact' => NULL,
      ];


      CRM_Utils_Hook::tokens($_categories);
      $_categoryString = CRM_Utils_Array::implode('|', array_keys($_categories));
    }

    $funcStruct = ['type' => NULL, 'token' => $token];
    $matches = [];
    if ((preg_match('/^href/i', $token) || preg_match('/^http/i', $token) || preg_match('/^<a[^>]+\bhref/i', $token))) {
      // it is a url so we need to check to see if there are any tokens embedded
      // if so then call this function again to get the token dataFunc
      // and assign the type 'embedded'  so that the data retrieving function
      // will know what how to handle this token.
      if (preg_match_all('/(\{\w+\.\w+\})/', $token, $matches)) {
        $funcStruct['type'] = 'embedded_url';
        $funcStruct['embed_parts'] = $funcStruct['token'] = [];
        foreach ($matches[1] as $match) {
          $preg_token = '/' . preg_quote($match, '/') . '/';
          $list = preg_split($preg_token, $token, 2);
          $funcStruct['embed_parts'][] = $list[0];
          $token = $list[1];
          $funcStruct['token'][] = $this->getDataFunc($match);
        }
        // fixed truncated url, CRM-7113
        if ($token) {
          $funcStruct['embed_parts'][] = $token;
        }
      }
      else {
        $funcStruct['type'] = 'url';
      }
    }
    elseif (preg_match('/^\{(' . $_categoryString . ')\.(\w+)\}$/', $token, $matches)) {
      $funcStruct['type'] = $matches[1];
      $funcStruct['token'] = $matches[2];
    }
    elseif (preg_match('/\\\\\{(\w+\.\w+)\\\\\}|\{\{(\w+\.\w+)\}\}/', $token, $matches)) {
      // we are an escaped token
      // so remove the escape chars
      $unescaped_token = preg_replace('/\{\{|\}\}|\\\\\{|\\\\\}/', '', $matches[0]);
      $funcStruct['token'] = '{' . $unescaped_token . '}';
    }
    return $funcStruct;
  }

  /**
   *
   * Prepares the text and html templates
   * for generating the emails and returns a copy of the
   * prepared templates
   *
   * @access private
   *
   **/
  protected function getPreparedTemplates() {
    if (!$this->preparedTemplates) {
      $patterns['html'] = $this->getPatterns(TRUE);
      $patterns['subject'] = $patterns['text'] = $this->getPatterns();
      $templates = $this->getTemplates();

      $this->preparedTemplates = [];

      foreach ([
          'html', 'text', 'subject',
        ] as $key) {
        if (!isset($templates[$key])) {
          continue;
        }

        $matches = [];
        $tokens = [];
        $split_template = [];

        $email = $templates[$key];
        preg_match_all($patterns[$key], $email, $matches, PREG_PATTERN_ORDER);
        foreach ($matches[0] as $idx => $token) {
          $preg_token = '/' . preg_quote($token, '/') . '/im';
          list($split_template[], $email) = preg_split($preg_token, $email, 2);
          array_push($tokens, $this->getDataFunc($token));
        }
        if ($email) {
          $split_template[] = $email;
        }
        $this->preparedTemplates[$key]['template'] = $split_template;
        $this->preparedTemplates[$key]['tokens'] = $tokens;
      }
    }
    return ($this->preparedTemplates);
  }

  /**
   *
   *  Retrieve a ref to an array that holds the email and text templates for this email
   *  assembles the complete template including the header and footer
   *  that the user has uploaded or declared (if they have dome that)
   *
   *
   * @return array reference to an assoc array
   * @access private
   *
   **/
  protected function &getTemplates() {

    if (!$this->templates) {
      $this->getHeaderFooter();
      $this->templates = [];

      if ($this->body_text) {
        $template = [];
        if ($this->header) {
          $template[] = $this->header->body_text;
        }

        $template[] = $this->body_text;

        if ($this->footer) {
          $template[] = $this->footer->body_text;
        }

        $this->templates['text'] = join("\n", $template);
      }

      if ($this->body_html) {

        $template = [];
        if ($this->header) {
          $template[] = $this->header->body_html;
        }

        $template[] = $this->body_html;

        if ($this->footer) {
          $template[] = $this->footer->body_html;
        }

        $this->templates['html'] = join("\n", $template);
      }

      if ($this->subject) {
        $template = [];
        $template[] = $this->subject;
        $this->templates['subject'] = join("\n", $template);
      }
    }
    return $this->templates;
  }

  /**
   *
   *  Retrieve a ref to an array that holds all of the tokens in the email body
   *  where the keys are the type of token and the values are ordinal arrays
   *  that hold the token names (even repeated tokens) in the order in which
   *  they appear in the body of the email.
   *
   *  note: the real work is done in the _getTokens() function
   *
   *  this function needs to have some sort of a body assigned
   *  either text or html for this to have any meaningful impact
   *
   * @return array               reference to an assoc array
   * @access public
   *
   **/
  public function &getTokens() {
    if (!$this->tokens) {

      $this->tokens = ['html' => [], 'text' => [], 'subject' => []];

      if ($this->body_html) {
        $this->_getTokens('html');
      }

      if ($this->body_text) {
        $this->_getTokens('text');
      }

      if ($this->subject) {
        $this->_getTokens('subject');
      }
    }

    return $this->tokens;
  }

  /**
   * Returns the token set for all 3 parts as one set. This allows it to be sent to the
   * hook in one call and standardizes it across other token workflows
   *
   * @return array               reference to an assoc array
   * @access public
   *
   **/
  public function &getFlattenedTokens() {
    if (!$this->flattenedTokens) {
      $tokens = $this->getTokens();

      $this->flattenedTokens = CRM_Utils_Token::flattenTokens($tokens);
    }

    return $this->flattenedTokens;
  }

  /**
   *
   *  _getTokens parses out all of the tokens that have been
   *  included in the html and text bodies of the email
   *  we get the tokens and then separate them into an
   *  internal structure named tokens that has the same
   *  form as the static tokens property(?) of the CRM_Utils_Token class.
   *  The difference is that there might be repeated token names as we want the
   *  structures to represent the order in which tokens were found from left to right, top to bottom.
   *
   *
   * @param str $prop     name of the property that holds the text that we want to scan for tokens (html, text)
   * @access private
   *
   * @return void
   */
  protected function _getTokens($prop) {
    $templates = $this->getTemplates();

    $newTokens = CRM_Utils_Token::getTokens($templates[$prop]);

    foreach ($newTokens as $type => $names) {
      if (!isset($this->tokens[$prop][$type])) {
        $this->tokens[$prop][$type] = [];
      }
      foreach ($names as $key => $name) {
        $this->tokens[$prop][$type][] = $name;
      }
    }
  }

  /**
   * Generate an event queue for a test job
   *
   * @params array $params contains form values
   *
   * @return void
   * @access public
   */
  public function getTestRecipients($testParams) {
    if (CRM_Utils_Array::arrayKeyExists($testParams['test_group'], CRM_Core_PseudoConstant::group())) {
      $group = new CRM_Contact_DAO_Group();
      $group->id = $testParams['test_group'];
      $contacts = CRM_Contact_BAO_GroupContact::getGroupContacts($group);
      if (count($contacts) > 50) {
        return;
      }
      $queued = [];
      foreach ($contacts as $contact) {
        if (empty($contact->email)) {
          continue;
        }
        elseif (!empty($queued[$contact->contact_id])) {
          continue;
        }
        else {
          $queued[$contact->contact_id] = 1;
        }
        $query = "SELECT DISTINCT civicrm_email.id AS email_id, civicrm_email.is_primary as is_primary,
                                 civicrm_email.is_bulkmail as is_bulkmail
FROM civicrm_email
INNER JOIN civicrm_contact ON civicrm_email.contact_id = civicrm_contact.id
WHERE civicrm_email.is_bulkmail = 1
AND civicrm_contact.id = {$contact->contact_id}
AND civicrm_contact.do_not_email = 0
AND civicrm_contact.is_deceased = 0
AND civicrm_email.on_hold = 0
AND civicrm_contact.is_opt_out =0";
        $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
        if ($dao->fetch()) {
          $params = [
            'job_id' => $testParams['job_id'],
            'email_id' => $dao->email_id,
            'contact_id' => $contact->contact_id,
          ];
          $queue = CRM_Mailing_Event_BAO_Queue::create($params);
        }
        else {
          $query = "SELECT DISTINCT civicrm_email.id AS email_id, civicrm_email.is_primary as is_primary,
                                 civicrm_email.is_bulkmail as is_bulkmail
FROM civicrm_email
INNER JOIN civicrm_contact ON civicrm_email.contact_id = civicrm_contact.id
WHERE civicrm_email.is_primary = 1
AND civicrm_contact.id = {$contact->contact_id}
AND civicrm_contact.do_not_email =0
AND civicrm_contact.is_deceased = 0
AND civicrm_email.on_hold = 0
AND civicrm_contact.is_opt_out =0";
          $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
          if ($dao->fetch()) {
            $params = [
              'job_id' => $testParams['job_id'],
              'email_id' => $dao->email_id,
              'contact_id' => $contact->contact_id,
            ];
            $queue = CRM_Mailing_Event_BAO_Queue::create($params);
          }
        }
      }
    }
  }

  /**
   * Retrieve the header and footer for this mailing
   *
   * @param void
   *
   * @return void
   * @access private
   */
  protected function getHeaderFooter() {
    if (!$this->header and $this->header_id) {
      $this->header = new CRM_Mailing_BAO_Component();
      $this->header->id = $this->header_id;
      $this->header->find(TRUE);
      $this->header->free();
    }

    if (!$this->footer and $this->footer_id) {
      $this->footer = new CRM_Mailing_BAO_Component();
      $this->footer->id = $this->footer_id;
      $this->footer->find(TRUE);
      $this->footer->free();
    }
  }

  /**
   * static wrapper for getting verp and urls
   *
   * @param int $job_id           ID of the Job associated with this message
   * @param int $event_queue_id   ID of the EventQueue
   * @param string $hash          Hash of the EventQueue
   * @param string $email         Destination address
   *
   * @return (reference) array    array ref that hold array refs to the verp info and urls
   */
  static function getVerpAndUrls($job_id, $event_queue_id, $hash, $email) {
    // create a skeleton object and set its properties that are required by getVerpAndUrlsAndHeaders()

    $config = CRM_Core_Config::singleton();
    $bao = new CRM_Mailing_BAO_Mailing();
    $bao->_domain = CRM_Core_BAO_Domain::getDomain();
    $bao->from_name = $bao->from_email = $bao->subject = '';

    // use $bao's instance method to get verp and urls
    list($verp, $urls, $_) = $bao->getVerpAndUrlsAndHeaders($job_id, $event_queue_id, $hash, $email);
    return [$verp, $urls];
  }

  /**
   * Given and array of headers and a prefix, job ID, event queue ID, and hash,
   * add a Message-ID header if needed.
   *
   * i.e. if the global includeMessageId is set and there isn't already a
   * Message-ID in the array.
   * The message ID is structured the same way as a verp. However no interpretation
   * is placed on the values received, so they do not need to follow the verp
   * convention.
   *
   * @param array $headers
   *   Array of message headers to update, in-out.
   * @param string $prefix
   *   Prefix for the message ID, use same prefixes as verp.
   *                                wherever possible
   * @param string $job_id
   *   Job ID component of the generated message ID.
   * @param string $event_queue_id
   *   Event Queue ID component of the generated message ID.
   * @param string $hash
   *   Hash component of the generated message ID.
   *
   * @return void
   */
  public static function addMessageIdHeader(&$headers, $prefix = NULL, $job_id = NULL, $event_queue_id = NULL, $hash = NULL) {
    $config = CRM_Core_Config::singleton();
    $localpart = CRM_Core_BAO_MailSettings::defaultLocalpart();
    $emailDomain = CRM_Core_BAO_MailSettings::defaultDomain();
    $fields = [];
    $fields[] = 'Message-ID';
    // CRM-17754 check if Resent-Message-id is set also if not add it in when re-laying reply email
    if ($prefix == 'r') {
      $fields[] = 'Resent-Message-ID';
    }

    // send from activity, single mail
    if (empty($prefix) && empty($job_id) && !CRM_Utils_Array::arrayKeyExists('Message-ID', $headers)) {
      $localpart = CRM_Core_BAO_MailSettings::defaultLocalpart();
      list($send, $host) = explode('@', $headers['Return-Path']); 
      $mailing_id = sprintf("<%s%s.%s@%s>",
        $localpart,
        base_convert(microtime(), 10, 36),
        base_convert(bin2hex(openssl_random_pseudo_bytes(8)), 16, 36),
        $host
      );
      $headers['Message-ID'] = $mailing_id;
    }
    else {
      foreach ($fields as $field) {
        if (!CRM_Utils_Array::arrayKeyExists($field, $headers)) {
          $headers[$field] = '<' . CRM_Utils_Array::implode($config->verpSeparator,
              [
                $localpart . $prefix,
                $job_id,
                $event_queue_id,
                $hash,
              ]
            ) . "@{$emailDomain}>";
        }
      }
    }
  }

  /**
   * get verp, urls and headers
   *
   * @param int $job_id           ID of the Job associated with this message
   * @param int $event_queue_id   ID of the EventQueue
   * @param string $hash          Hash of the EventQueue
   * @param string $email         Destination address
   *
   * @return (reference) array    array ref that hold array refs to the verp info, urls, and headers
   * @access private
   */
  protected function getVerpAndUrlsAndHeaders($job_id, $event_queue_id, $hash, $email, $isForward = FALSE) {
    $config = CRM_Core_Config::singleton();

    /**
     * Inbound VERP keys:
     *  reply:          user replied to mailing
     *  bounce:         email address bounced
     *  unsubscribe:    contact opts out of all target lists for the mailing
     *  resubscribe:    contact opts back into all target lists for the mailing
     *  optOut:         contact unsubscribes from the domain
     */
    $verp = [];
    $verpTokens = [
      'reply' => 'r',
      'bounce' => 'b',
      'unsubscribe' => 'u',
      'resubscribe' => 'e',
      'optOut' => 'o',
    ];


    $localpart = CRM_Core_BAO_MailSettings::defaultLocalpart();
    $emailDomain = CRM_Core_BAO_MailSettings::defaultDomain();

    foreach ($verpTokens as $key => $value) {
      $verp[$key] = CRM_Utils_Array::implode($config->verpSeparator,
        [
          $localpart . $value,
          $job_id,
          $event_queue_id,
          $hash,
        ]
      ) . "@$emailDomain";
    }

    //handle should override VERP address.
    $skipEncode = FALSE;

    if ($job_id &&
      self::overrideVerp($job_id)
    ) {
      $verp['reply'] = "\"{$this->from_name}\" <{$this->from_email}>";
    }

    $urls = [
      'forward' => CRM_Utils_System::url('civicrm/mailing/forward',
        "reset=1&jid={$job_id}&qid={$event_queue_id}&h={$hash}",
        TRUE, NULL, TRUE, TRUE
      ),
      'unsubscribeUrl' => CRM_Utils_System::url('civicrm/mailing/unsubscribe',
        "reset=1&jid={$job_id}&qid={$event_queue_id}&h={$hash}",
        TRUE, NULL, TRUE, TRUE
      ),
      'resubscribeUrl' => CRM_Utils_System::url('civicrm/mailing/resubscribe',
        "reset=1&jid={$job_id}&qid={$event_queue_id}&h={$hash}",
        TRUE, NULL, TRUE, TRUE
      ),
      'optOutUrl' => CRM_Utils_System::url('civicrm/mailing/optout',
        "reset=1&jid={$job_id}&qid={$event_queue_id}&h={$hash}",
        TRUE, NULL, TRUE, TRUE
      ),
      'subscribeUrl' => CRM_Utils_System::url('civicrm/mailing/subscribe',
        'reset=1',
        TRUE, NULL, TRUE, TRUE
      ),
    ];

    $unsubscribeUrl = str_replace(['&amp;', 'http://'], ['&', 'https://'], $urls['unsubscribeUrl']);
    $headers = [
      'List-Unsubscribe' => '<'.$unsubscribeUrl.'>'.' ,'."<mailto:{$verp['unsubscribe']}>",
      'From' => CRM_Utils_Mail::formatRFC822Email($this->from_name, $this->from_email),
      'Sender' => $verp['reply'],
      'Return-Path' => $verp['bounce'],
      'Subject' => $this->subject,
    ];
    if (isset($config->enableDMARC) && !empty($config->enableDMARC)) {
      $validatedEmails = CRM_Admin_Form_FromEmailAddress::getVerifiedEmail();
      if (in_array($this->from_email, $validatedEmails)) {
        $headers['Sender'] = $this->from_email;
      }
    }
    $headers['Reply-To'] = $headers['From'];
		self::addMessageIdHeader($headers, 'm', $job_id, $event_queue_id, $hash);

    if ($isForward) {
      $headers['Subject'] = "[Fwd:{$this->subject}]";
    }
    return [&$verp, &$urls, &$headers];
  }

  /**
   * Compose a message
   *
   * @param int $job_id           ID of the Job associated with this message
   * @param int $event_queue_id   ID of the EventQueue
   * @param string $hash          Hash of the EventQueue
   * @param string $contactId     ID of the Contact
   * @param string $email         Destination address
   * @param string $recipient     To: of the recipient
   * @param boolean $test         Is this mailing a test?
   * @param boolean $isForward    Is this mailing compose for forward?
   * @param string  $fromEmail    email address of who is forwardinf it.
   *
   * @return object               The mail object
   * @access public
   */
  public function &compose($job_id, $event_queue_id, $hash, $contactId,
    $email, &$recipient, $test,
    $contactDetails, &$attachments, $isForward = FALSE,
    $fromEmail = NULL, $replyToEmail = NULL
  ) {
    if ($this->checkIsHidden()) {
      CRM_Core_Error::fatal('Mailing is hidden. We can not compose hidden mailing by job.');
      return;
    }

    $config = CRM_Core_Config::singleton();
    $knownTokens = $this->getTokens();

    if ($this->_domain == NULL) {

      $this->_domain = CRM_Core_BAO_Domain::getDomain();
    }

    list($verp, $urls, $headers) = $this->getVerpAndUrlsAndHeaders($job_id,
      $event_queue_id,
      $hash,
      $email,
      $isForward
    );
    //set from email who is forwarding it and not original one.
    if ($fromEmail && CRM_Utils_Rule::email($fromEmail)) {
      unset($headers['From']);
      $headers['From'] = CRM_Utils_Mail::formatRFC822Email('', $fromEmail);
    }

    if ($replyToEmail && ($fromEmail != $replyToEmail) && CRM_Utils_Mail::checkRFC822Email($fromEmail)) {
      $headers['Reply-To'] = "{$replyToEmail}";
    }


    // refs #32614, disable smarty evaluation functions

    if ($contactDetails) {
      $contact = $contactDetails;
    }
    else {
      $params = [['contact_id', '=', $contactId, 0, 0]];
      list($contactArray, $_) = CRM_Contact_BAO_Query::apiQuery($params);

      //CRM-4524
      $contact = reset($contactArray);

      if (!$contact || is_a($contact, 'CRM_Core_Error')) {
        // setting this because function is called by reference
        //@todo test not calling function by reference
        $res = NULL;
        return $res;
      }

      // also call the hook to get contact details

      $contactIds = [$contactId];
      CRM_Utils_Hook::tokenValues($contactArray, $contactIds, $job_id, [], 'CRM_Mailing_BAO_Mailing_compose');
    }

    $pTemplates = $this->getPreparedTemplates();
    $pEmails = [];

    foreach ($pTemplates as $type => $pTemplate) {
      $html = ($type == 'html') ? TRUE : FALSE;
      $pEmails[$type] = [];
      $pEmail = &$pEmails[$type];
      $template = &$pTemplates[$type]['template'];
      $tokens = &$pTemplates[$type]['tokens'];
      $idx = 0;
      if (!empty($tokens)) {
        foreach ($tokens as $idx => $token) {
          $token_data = $this->getTokenData($token, $html, $contact, $verp, $urls, $event_queue_id);
          array_push($pEmail, $template[$idx]);
          array_push($pEmail, $token_data);
        }
      }
      else {
        array_push($pEmail, $template[$idx]);
      }

      if (isset($template[($idx + 1)])) {
        array_push($pEmail, $template[($idx + 1)]);
      }
    }

    $html = NULL;
    if (isset($pEmails['html']) && is_array($pEmails['html']) && count($pEmails['html'])) {
      $html = &$pEmails['html'];
    }

    $text = NULL;
    if (isset($pEmails['text']) && is_array($pEmails['text']) && count($pEmails['text'])) {
      $text = &$pEmails['text'];
    }
    else {
      // this is where we create a text template from the html template if the text template did not exist
      // this way we ensure that every recipient will receive an email even if the pref is set to text and the
      // user uploads an html email only
      $text = CRM_Utils_String::htmlToText(join('', $html));
    }

    // push the tracking url on to the html email if necessary
    if ($this->open_tracking && $html) {
      $trackedOpen = FALSE;
      $openTrack = '<img src="' . $config->userFrameworkResourceURL ."extern/open.php?q=$event_queue_id\" width='1' height='1' alt='' border='0'>\n";
      foreach($html as $idx => $document) {
        if (stristr($document, '</body>')) {
          $html[$idx] = preg_replace('@</body>@i', $openTrack.'</body>', $document);
          $trackedOpen = TRUE;
          break;
        }
      }
      if (!$trackedOpen){
        array_push($html, "\n".$openTrack);
      }
    }

    $message = new Mail_mime("\n");

    // refs #32614, disable smarty evaluation functions

    $mailParams = $headers;
    if ($text && ($test || $contact['preferred_mail_format'] == 'Text' ||
        $contact['preferred_mail_format'] == 'Both' ||
        ($contact['preferred_mail_format'] == 'HTML' && !CRM_Utils_Array::arrayKeyExists('html', $pEmails))
      )) {
      if (is_array($text)) {
        $textBody = join('', $text);
      }
      else {
        $textBody = $text;
      }
      $mailParams['text'] = $textBody;
    }

    if ($html && ($test || ($contact['preferred_mail_format'] == 'HTML' ||
          $contact['preferred_mail_format'] == 'Both'
        ))) {
      $htmlBody = join('', $html);

      // refs #32614, disable smarty evaluation functions
      // #17688, rwd support for newsletter image
      $htmlBody = CRM_Utils_String::removeImageHeight($htmlBody);
      $mailParams['html'] = $htmlBody;
    }

    if (empty($mailParams['text']) && empty($mailParams['html'])) {
      // CRM-9833
      // something went wrong, lets log it and return null (by reference)
      CRM_Core_Error::debug_log_message(ts('CiviMail will not send an empty mail body, Skipping: %1',
          [1 => $email]
        ));
      $res = NULL;
      return $res;
    }

    $mailParams['attachments'] = $attachments;

    $mailingSubject = CRM_Utils_Array::value('subject', $pEmails);
    if (is_array($mailingSubject)) {
      $mailingSubject = join('', $mailingSubject);
    }
    $mailParams['Subject'] = $mailingSubject;

    $mailParams['toName'] = CRM_Utils_Array::value('display_name',
      $contact
    );
    $mailParams['toEmail'] = $email;

    $mailParams['alterTag'] = 'civimail';
    CRM_Utils_Hook::alterMailParams($mailParams);
    unset($mailParams['alterTag']);

    //cycle through mailParams and set headers array
    foreach ($mailParams as $paramKey => $paramValue) {
      //exclude values not intended for the header
      if (!in_array($paramKey, [
            'text', 'html', 'attachments', 'toName', 'toEmail',
          ])) {
        $headers[$paramKey] = $paramValue;
      }
    }

    if (!empty($mailParams['text'])) {
      $message->setTxtBody($mailParams['text']);
    }

    if (!empty($mailParams['html'])) {
      $message->setHTMLBody($mailParams['html']);
    }

    if (!empty($mailParams['attachments'])) {
      foreach ($mailParams['attachments'] as $fileID => $attach) {
        $message->addAttachment($attach['fullPath'],
          $attach['mime_type'],
          $attach['cleanName']
        );
      }
    }

    $headers['To'] = CRM_Utils_Mail::formatRFC822Email($mailParams['toName'], $mailParams['toEmail']);
    $headers['Precedence'] = 'bulk';
    // Will test in the mail processor if the X-VERP is set in the bounced email.
    // (As an option to replace real VERP for those that can't set it up)
    $headers['X-CiviMail-Bounce'] = $verp['bounce'];

    // refs #30565, add google feedback loop header
    $campaignID = $this->id;
    $identifier = "j{$job_id}q{$event_queue_id}";
    $senderID = substr(str_replace(['.', '-'], '', $_SERVER['HTTP_HOST']), 0, 15);
    $headers['Feedback-ID'] = "$campaignID:$contactId:$identifier:$senderID";

    //CRM-5058
    //token replacement of subject
    $headers['Subject'] = $mailingSubject;

    CRM_Utils_Mail::setMimeParams($message);
    $headers = $message->headers($headers);

    //get formatted recipient
    $recipient = $headers['To'];

    // make sure we unset a lot of stuff
    unset($verp);
    unset($urls);
    unset($params);
    unset($contact);
    unset($ids);

    return $message;
  }

  /**
   *
   * get mailing object and replaces subscribeInvite,
   * domain and mailing tokens
   *
   */
  static function tokenReplace(&$mailing) {

    $domain = CRM_Core_BAO_Domain::getDomain();

    foreach ([
        'text', 'html',
      ] as $type) {
      $tokens = $mailing->getTokens();
      if (isset($mailing->templates[$type])) {
        $mailing->templates[$type] = CRM_Utils_Token::replaceSubscribeInviteTokens($mailing->templates[$type]);
        $mailing->templates[$type] = CRM_Utils_Token::replaceDomainTokens($mailing->templates[$type],
          $domain,
          $type == 'html' ? TRUE : FALSE,
          $tokens[$type]
        );
        $mailing->templates[$type] = CRM_Utils_Token::replaceMailingTokens($mailing->templates[$type], $mailing, NULL, $tokens[$type]);
      }
    }
  }

  /**
   *
   *  getTokenData receives a token from an email
   *  and returns the appropriate data for the token
   *
   */
  protected function getTokenData(&$token_a, $html, &$contact, &$verp, &$urls, $event_queue_id) {
    $type = $token_a['type'];
    $token = $token_a['token'];
    $data = $token;

    $escapeSmarty = FALSE;

    if ($type == 'embedded_url') {
      $embed_data = [];
      foreach ($token as $t) {
        $embed_data[] = $this->getTokenData($t, $html = FALSE, $contact, $verp, $urls, $event_queue_id);
      }
      $numSlices = count($embed_data);
      $url = '';
      for ($i = 0; $i < $numSlices; $i++) {
        $url .= "{$token_a['embed_parts'][$i]}{$embed_data[$i]}";
      }
      if (isset($token_a['embed_parts'][$numSlices])) {
        $url .= $token_a['embed_parts'][$numSlices];
      }
      // add trailing quote since we've gobbled it up in a previous regex
      // function getPatterns, line 431
      if (preg_match('/href[ ]*=[ ]*\'/', $url)) {
        $url .= "'";
      }
      elseif (preg_match('/href[ ]*=[ ]*\"/', $url)) {
        $url .= '"';
      }
      $data = $url;
    }
    elseif ($type == 'url') {
      if ($this->url_tracking) {
        $data = CRM_Mailing_BAO_TrackableURL::getTrackerURL($token, $this->id, $event_queue_id);
      }
      else {
        $data = $token;
      }
    }
    elseif ($type == 'contact') {
      $data = CRM_Utils_Token::getContactTokenReplacement($token, $contact, FALSE, FALSE, $escapeSmarty);
    }
    elseif ($type == 'action') {
      $data = CRM_Utils_Token::getActionTokenReplacement($token, $verp, $urls, $html);
    }
    elseif ($type == 'domain') {

      $domain = CRM_Core_BAO_Domain::getDomain();
      $data = CRM_Utils_Token::getDomainTokenReplacement($token, $domain, $html);
    }
    elseif ($type == 'mailing') {
      if ($token == 'name') {
        $data = $this->name;
      }
      elseif ($token == 'group') {
        $groups = $this->getGroupNames();
        $data = CRM_Utils_Array::implode(', ', $groups);
      }
    }
    else {
      $data = CRM_Utils_Array::value("{$type}.{$token}", $contact);
    }
    return $data;
  }

  /**
   * Return a list of group names for this mailing.  Does not work with
   * prior-mailing targets.
   *
   * @return array        Names of groups receiving this mailing
   * @access public
   */
  public function &getGroupNames() {
    if (!isset($this->id)) {
      return [];
    }
    $mg = new CRM_Mailing_DAO_Group();
    $mgtable = CRM_Mailing_DAO_Group::getTableName();
    $group = CRM_Contact_BAO_Group::getTableName();

    $mg->query("SELECT      $group.title as name FROM $mgtable 
                    INNER JOIN  $group ON $mgtable.entity_id = $group.id
                    WHERE       $mgtable.mailing_id = {$this->id}
                        AND     $mgtable.entity_table = '$group'
                        AND     $mgtable.group_type = 'Include'
                    ORDER BY    $group.name");

    $groups = [];
    while ($mg->fetch()) {
      $groups[] = $mg->name;
    }
    $mg->free();
    return $groups;
  }

  /**
   * function to add the mailings
   *
   * @param array $params reference array contains the values submitted by the form
   * @param array $ids    reference array contains the id
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function add(&$params, $ids = []) {
    $id = CRM_Utils_Array::value('mailing_id', $ids, CRM_Utils_Array::value('id', $params));
    if ($id) {
      CRM_Utils_Hook::pre('edit', 'Mailing', $id, $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'Mailing', NULL, $params);
    }

    $mailing = new CRM_Mailing_DAO_Mailing();
    $mailing->id = $id;
    $mailing->domain_id = CRM_Utils_Array::value('domain_id', $params, CRM_Core_Config::domainID());

    if (!isset($params['replyto_email']) &&
      isset($params['from_email'])
    ) {
      $params['replyto_email'] = $params['from_email'];
    }

    $mailing->copyValues($params);

    $result = $mailing->save();

    if (CRM_Utils_Array::value('mailing', $ids)) {
      CRM_Utils_Hook::post('edit', 'Mailing', $mailing->id, $mailing);
    }
    else {
      CRM_Utils_Hook::post('create', 'Mailing', $mailing->id, $mailing);
    }

    return $result;
  }

  /**
   * Construct a new mailing object, along with job and mailing_group
   * objects, from the form values of the create mailing wizard.
   *
   * @params array $params        Form values
   *
   * @return object $mailing      The new mailing object
   * @access public
   * @static
   */
  public static function create(&$params, $ids = []) {
    // Retrieve domain email and name for default sender
    if (!isset($ids['id']) && !isset($ids['mailing_id'])) {
      $domain = civicrm_api('Domain', 'getsingle', [
          'version' => 3,
          'current_domain' => 1,
          'sequential' => 1,
        ]);
      if (isset($domain['from_email'])) {
        $domain_email = $domain['from_email'];
        $domain_name = $domain['from_name'];
      }
      else {
        $domain_email = 'info@FIXME.ORG';
        $domain_name = 'FIXME.ORG';
      }
      if (!isset($params['created_id'])) {
        $session = &CRM_Core_Session::singleton();
        $params['created_id'] = $session->get('userID');
      }
      $defaults = [
        // load the default config settings for each
        // eg reply_id, unsubscribe_id need to use
        // correct template IDs here
        'override_verp' => TRUE,
        'forward_replies' => FALSE,
        'open_tracking' => TRUE,
        'url_tracking' => TRUE,
        'visibility' => 'User and User Admin Only',
        'replyto_email' => $domain_email,
        'header_id' => CRM_Mailing_PseudoConstant::defaultComponent('header_id', ''),
        'footer_id' => CRM_Mailing_PseudoConstant::defaultComponent('footer_id', ''),
        'from_email' => $domain_email,
        'from_name' => $domain_name,
        'msg_template_id' => NULL,
        'created_id' => $params['created_id'],
        'auto_responder' => 0,
        'created_date' => date('YmdHis'),
      ];

      // Get the default from email address, if not provided.
      if (empty($defaults['from_email'])) {
        $defaultAddress = CRM_Core_OptionGroup::values('from_email_address', NULL, NULL, NULL, ' AND is_default = 1');
        foreach ($defaultAddress as $id => $value) {
          if (preg_match('/"(.*)" <(.*)>/', $value, $match)) {
            $defaults['from_email'] = $match[2];
            $defaults['from_name'] = $match[1];
          }
        }
      }

      $params = array_merge($defaults, $params);
    }

    /**
     * Could check and warn for the following cases:
     *
     * - groups OR mailings should be populated.
     * - body html OR body text should be populated.
     */

    $transaction = new CRM_Core_Transaction();

    $mailing = self::add($params, $ids);

    if (is_a($mailing, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $mailing;
    }



    $groupTableName = CRM_Contact_BAO_Group::getTableName();
    $mailingTableName = CRM_Mailing_BAO_Mailing::getTableName();

    /* Create the mailing group record */


    $mg = new CRM_Mailing_DAO_Group();
    $tables = [
      'groups' => CRM_Contact_BAO_Group::getTableName(),
      'mailings' =>  CRM_Mailing_BAO_Mailing::getTableName(),
      'opened' =>  CRM_Mailing_Event_BAO_Opened::getTableName(),
      'clicked' =>  CRM_Mailing_Event_BAO_TrackableURLOpen::getTableName(),
    ];
    foreach ($tables as $entity => $table) {
      foreach (['include', 'exclude', 'base'] as $type) {
        if (isset($params[$entity]) && CRM_Utils_Array::value($type, $params[$entity]) && is_array($params[$entity][$type])) {
          foreach ($params[$entity][$type] as $entityId) {
            $mg->reset();
            $mg->mailing_id = $mailing->id;
            $mg->entity_table = $table;
            $mg->entity_id = $entityId;
            $mg->group_type = $type;
            $mg->save();
          }
        }
      }
    }

    if (!empty($params['search_id']) && !empty($params['group_id'])) {
      $mg->reset();
      $mg->mailing_id = $mailing->id;
      $mg->entity_table = $groupTableName;
      $mg->entity_id = $params['group_id'];
      $mg->search_id = $params['search_id'];
      $mg->search_args = $params['search_args'];
      $mg->group_type = 'Include';
      $mg->save();
    }

    // check and attach and files as needed

    CRM_Core_BAO_File::processAttachment($params, 'civicrm_mailing', $mailing->id);

    $transaction->commit();
    return $mailing;
  }

  /**
   * Generate a report.  Fetch event count information, mailing data, and job
   * status.
   *
   * @param int     $id          The mailing id to report
   * @param boolean $skipDetails whether return all detailed report
   *
   * @return array        Associative array of reporting data
   * @access public
   * @static
   */
  public static function &report($id, $skipDetails = FALSE) {
    $mailing_id = CRM_Utils_Type::escape($id, 'Integer');

    $mailing = new CRM_Mailing_BAO_Mailing();







    $t = [
      'mailing' => self::getTableName(),
      'mailing_group' => CRM_Mailing_DAO_Group::getTableName(),
      'group' => CRM_Contact_BAO_Group::getTableName(),
      'job' => CRM_Mailing_BAO_Job::getTableName(),
      'queue' => CRM_Mailing_Event_BAO_Queue::getTableName(),
      'delivered' => CRM_Mailing_Event_BAO_Delivered::getTableName(),
      'opened' => CRM_Mailing_Event_BAO_Opened::getTableName(),
      'reply' => CRM_Mailing_Event_BAO_Reply::getTableName(),
      'unsubscribe' =>
      CRM_Mailing_Event_BAO_Unsubscribe::getTableName(),
      'bounce' => CRM_Mailing_Event_BAO_Bounce::getTableName(),
      'forward' => CRM_Mailing_Event_BAO_Forward::getTableName(),
      'url' => CRM_Mailing_BAO_TrackableURL::getTableName(),
      'urlopen' =>
      CRM_Mailing_Event_BAO_TrackableURLOpen::getTableName(),
      'component' => CRM_Mailing_BAO_Component::getTableName(),
      'spool' => CRM_Mailing_BAO_Spool::getTableName(),
    ];


    $report = [];

    /* Get the mailing info */


    $mailing->query("
            SELECT          {$t['mailing']}.*
            FROM            {$t['mailing']}
            WHERE           {$t['mailing']}.id = $mailing_id");

    $mailing->fetch();


    $report['mailing'] = [];
    foreach (array_keys(self::fields()) as $field) {
      $report['mailing'][$field] = $mailing->$field;
    }

    //get the campaign
    /*
    if ($campaignId = CRM_Utils_Array::value('campaign_id', $report['mailing'])) {

      $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns($campaignId);
      $report['mailing']['campaign'] = $campaigns[$campaignId];
    }
    */


    //mailing report is called by activity
    //we dont need all detail report
    if ($skipDetails) {
      return $report;
    }

    /* Get the component info */


    $query = [];

    $components = [
      'header' => ts('Header'),
      'footer' => ts('Footer'),
      'reply' => ts('Reply'),
      'unsubscribe' => ts('Unsubscribe'),
      'optout' => ts('Opt-Out'),
    ];
    foreach (array_keys($components) as $type) {
      $query[] = "SELECT          {$t['component']}.name as name,
                                        '$type' as type,
                                        {$t['component']}.id as id
                        FROM            {$t['component']}
                        INNER JOIN      {$t['mailing']}
                                ON      {$t['mailing']}.{$type}_id =
                                                {$t['component']}.id
                        WHERE           {$t['mailing']}.id = $mailing_id";
    }
    $q = '(' . CRM_Utils_Array::implode(') UNION (', $query) . ')';
    $mailing->query($q);

    $report['component'] = [];
    while ($mailing->fetch()) {
      $report['component'][] = [
        'type' => $components[$mailing->type],
        'name' => $mailing->name,
        'link' =>
        CRM_Utils_System::url('civicrm/mailing/component',
          "reset=1&action=update&id={$mailing->id}"
        ),
      ];
    }

    /* Get the recipient group info */


    $mailing->query("
            SELECT          {$t['mailing_group']}.group_type as group_type,
                            {$t['mailing_group']}.entity_table as entity_table,
                            {$t['mailing_group']}.entity_id as entity_id,
                            {$t['group']}.id as group_id,
                            {$t['group']}.title as group_title,
                            {$t['group']}.title as group_title,
                            {$t['group']}.is_hidden as group_hidden,
                            {$t['mailing']}.id as mailing_id,
                            {$t['mailing']}.name as mailing_name
            FROM            {$t['mailing_group']}
            LEFT JOIN       {$t['group']}
                    ON      {$t['mailing_group']}.entity_id = {$t['group']}.id
                    AND     {$t['mailing_group']}.entity_table = '{$t['group']}'
            LEFT JOIN       {$t['mailing']}
                    ON      {$t['mailing_group']}.entity_id = {$t['mailing']}.id
                    AND     {$t['mailing_group']}.entity_table = '{$t['mailing']}'
            WHERE           {$t['mailing_group']}.mailing_id = $mailing_id
            ");

    $report['group'] = ['include' => [], 'exclude' => [], 'base' => []];
    while ($mailing->fetch()) {
      $row = [];
      if ($mailing->entity_table == 'civicrm_group') {
        $row['name'] = $mailing->group_title ? $mailing->group_title : ts("Deleted");
        if (isset($mailing->group_id)) {
          $row['id'] = $mailing->group_id ? $mailing->group_id : '';
          $row['link'] = CRM_Utils_System::url('civicrm/group/search', "reset=1&force=1&context=smog&gid={$row['id']}");
        }
      }
      elseif($mailing->entity_table) {
        if ($mailing->entity_table == 'civicrm_mailing_event_opened' || $mailing->entity_table == 'civicrm_mailing_event_trackable_url_open') {
          $mailing->mailing_id = $mailing->entity_id;
          $mailing_name = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Mailing', $mailing->mailing_id, 'name');
          if ($mailing->entity_table == 'civicrm_mailing_event_opened') {
            $label = 'Recipients who opened these mailing';
            $link = CRM_Utils_System::url('civicrm/mailing/report/event', "reset=1&event=opened&distinct=1&mid={$mailing->mailing_id}");
          }
          else {
            $label = 'Recipients who clicked these mailing';
            $link = CRM_Utils_System::url('civicrm/mailing/report/event', "reset=1&event=click&distinct=1&mid={$mailing->mailing_id}");
          }
          $label = strtoupper($mailing->group_type) . " " . $label;
          $label = ts($label) . ': '.$mailing_name;
          $mailing->mailing_name = $label;
        }
        else {
          $link = CRM_Utils_System::url('civicrm/mailing/report/event', "reset=1&event=queue&mid={$mailing->mailing_id}");
        }
        $row['id'] = $mailing->mailing_id;
        $row['name'] = $mailing->mailing_name;
        $row['mailing'] = TRUE;
        $row['link'] = $link;
      }

      /* Rename hidden groups */
      if ($mailing->group_hidden == 1) {
        $row['name'] = "Search Results";
      }

      if ($mailing->group_type == 'Include') {
        $report['group']['include'][] = $row;
      }
      elseif ($mailing->group_type == 'Base') {
        $report['group']['base'][] = $row;
      }
      else {
        $report['group']['exclude'][] = $row;
      }
    }

    /* Get the event totals, grouped by job (retries) */


    $mailing->query("
            SELECT          {$t['job']}.*,
                            COUNT(DISTINCT {$t['queue']}.id) as queue,
                            COUNT(DISTINCT {$t['delivered']}.id) as delivered,
                            COUNT(DISTINCT {$t['reply']}.id) as reply,
                            COUNT(DISTINCT {$t['forward']}.id) as forward,
                            COUNT(DISTINCT {$t['bounce']}.id) as bounce,
                            COUNT(DISTINCT {$t['spool']}.id) as spool
            FROM            {$t['job']}
            LEFT JOIN       {$t['queue']}
                    ON      {$t['queue']}.job_id = {$t['job']}.id
            LEFT JOIN       {$t['reply']}
                    ON      {$t['reply']}.event_queue_id = {$t['queue']}.id
            LEFT JOIN       {$t['forward']}
                    ON      {$t['forward']}.event_queue_id = {$t['queue']}.id
            LEFT JOIN       {$t['bounce']}
                    ON      {$t['bounce']}.event_queue_id = {$t['queue']}.id
            LEFT JOIN       {$t['delivered']}
                    ON      {$t['delivered']}.event_queue_id = {$t['queue']}.id
                    AND     {$t['bounce']}.id IS null
            LEFT JOIN       {$t['urlopen']}
                    ON      {$t['urlopen']}.event_queue_id = {$t['queue']}.id
            LEFT JOIN       {$t['spool']}
                    ON      {$t['spool']}.job_id = {$t['job']}.id
            WHERE           {$t['job']}.mailing_id = $mailing_id
                    AND     {$t['job']}.is_test = 0
            GROUP BY        {$t['job']}.id");

    $report['jobs'] = [];
    $report['event_totals'] = [];
    $elements = [
      'queue', 'delivered', 'url_opened', 'forward',
      'reply', 'unsubscribe', 'opened', 'bounce', 'spool',
    ];

    // initialize various counters
    foreach ($elements as $field) {
      $report['event_totals'][$field] = 0;
    }

    // url count
    $urlCount = CRM_Mailing_BAO_TrackableURL::getTrackerURLCount($mailing_id);

    while ($mailing->fetch()) {
      $row = [];
      foreach ($elements as $field) {
        if (isset($mailing->$field)) {
          $row[$field] = $mailing->$field;
          $report['event_totals'][$field] += $mailing->$field;
        }
      }

      // compute open total separately to discount duplicates
      // CRM-1258
      $row['opened'] = CRM_Mailing_Event_BAO_Opened::getTotalCount($mailing_id, $mailing->id, TRUE);
      $report['event_totals']['opened'] += $row['opened'];

      // compute unsub total separately to discount duplicates
      // CRM-1783
      $row['unsubscribe'] = CRM_Mailing_Event_BAO_Unsubscribe::getTotalCount($mailing_id, $mailing->id, TRUE);
      $report['event_totals']['unsubscribe'] += $row['unsubscribe'];

      $row['url'] = CRM_Mailing_Event_BAO_TrackableURLOpen::getTotalCount($mailing_id, $mailing->id, TRUE);
      $report['event_totals']['url_opened'] += $row['url'];

      foreach (array_keys(CRM_Mailing_BAO_Job::fields()) as $field) {
        $row[$field] = $mailing->$field;
      }

      if (!empty($mailing->queue) && !empty($mailing->delivered)) {
        $row['delivered_rate'] = (100.0 * $mailing->delivered) / $mailing->queue;
        $row['opened_rate'] = (100.0 * $row['opened']) / $mailing->delivered;
        $row['clicked_rate'] = (100.0 * $row['url']) / $mailing->delivered;
        $row['bounce_rate'] = (100.0 * $mailing->bounce) / $mailing->queue;
        $row['unsubscribe_rate'] = (100.0 * $row['unsubscribe']) / $mailing->queue;
      }
      else {
        $row['delivered_rate'] = 0;
        $row['bounce_rate'] = 0;
        $row['unsubscribe_rate'] = 0;
        $row['opened_rate'] = 0;
        $row['clicked_rate'] = 0;
      }

      $row['links'] = [
        'clicks' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=click&mid=$mailing_id&jid={$mailing->id}"
        ),
        'queue' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=queue&mid=$mailing_id&jid={$mailing->id}"
        ),
        'delivered' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=delivered&mid=$mailing_id&jid={$mailing->id}"
        ),
        'bounce' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=bounce&mid=$mailing_id&jid={$mailing->id}"
        ),
        'unsubscribe' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=unsubscribe&mid=$mailing_id&jid={$mailing->id}"
        ),
        'forward' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=forward&mid=$mailing_id&jid={$mailing->id}"
        ),
        'reply' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=reply&mid=$mailing_id&jid={$mailing->id}"
        ),
        'opened' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=opened&mid=$mailing_id&jid={$mailing->id}"
        ),
      ];

      foreach ([
          'scheduled_date', 'start_date', 'end_date',
        ] as $key) {
        $row[$key] = CRM_Utils_Date::customFormat($row[$key]);
      }
      $report['jobs'][] = $row;
    }


    $newTableSize = CRM_Mailing_BAO_Recipients::mailingSize($mailing_id);

    // we need to do this for backward compatibility, since old mailings did not
    // use the mailing_recipients table
    if ($newTableSize > 0) {
      $report['event_totals']['queue'] = $newTableSize;
    }
    else {
      $report['event_totals']['queue'] = self::getRecipientsCount($mailing_id, $mailing_id);
    }

    if (CRM_Utils_Array::value('queue', $report['event_totals'])) {
      $report['event_totals']['delivered_rate'] = (100.0 * $report['event_totals']['delivered']) / $report['event_totals']['queue'];
      $report['event_totals']['bounce_rate'] = (100.0 * $report['event_totals']['bounce']) / $report['event_totals']['queue'];
      $report['event_totals']['unsubscribe_rate'] = (100.0 * $report['event_totals']['unsubscribe']) / $report['event_totals']['queue'];
      $report['event_totals']['opened_rate'] = (100.0 * $report['event_totals']['opened']) / $report['event_totals']['delivered'];
      $report['event_totals']['clicked_rate'] = (100.0 * $report['event_totals']['url_opened']) / $report['event_totals']['delivered'];
    }
    else {
      $report['event_totals']['delivered_rate'] = 0;
      $report['event_totals']['bounce_rate'] = 0;
      $report['event_totals']['unsubscribe_rate'] = 0;
      $report['event_totals']['opened_rate'] = 0;
      $report['event_totals']['clicked_rate'] = 0;
    }

    /* Get the click-through totals, grouped by URL */


    $mailing->query("
            SELECT      {$t['url']}.url,
                        {$t['url']}.id,
                        COUNT({$t['urlopen']}.id) as clicks,
                        COUNT(DISTINCT {$t['queue']}.id) as unique_clicks
            FROM        {$t['url']}
            LEFT JOIN   {$t['urlopen']}
                    ON  {$t['urlopen']}.trackable_url_id = {$t['url']}.id
            LEFT JOIN  {$t['queue']}
                    ON  {$t['urlopen']}.event_queue_id = {$t['queue']}.id
            LEFT JOIN  {$t['job']}
                    ON  {$t['queue']}.job_id = {$t['job']}.id
            WHERE       {$t['url']}.mailing_id = $mailing_id
                    AND {$t['job']}.is_test = 0
            GROUP BY    {$t['url']}.id");

    $report['click_through'] = [];

    while ($mailing->fetch()) {
      $report['click_through'][] = [
        'url' => $mailing->url,
        'link' =>
        CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=click&mid=$mailing_id&uid={$mailing->id}"
        ),
        'link_unique' =>
        CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=click&mid=$mailing_id&uid={$mailing->id}&distinct=1"
        ),
        'clicks' => $mailing->clicks,
        'unique' => $mailing->unique_clicks,
        'rate' => CRM_Utils_Array::value('delivered', $report['event_totals']) ? (100.0 * $mailing->unique_clicks) / $report['event_totals']['delivered'] : 0,
      ];
    }

    $report['event_totals']['links'] = [
      'clicks' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=click&mid=$mailing_id"
      ),
      'clicks_unique' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=click&mid=$mailing_id&distinct=1"
      ),
      'queue' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=queue&mid=$mailing_id"
      ),
      'delivered' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=delivered&mid=$mailing_id"
      ),
      'bounce' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=bounce&mid=$mailing_id"
      ),
      'unsubscribe' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=unsubscribe&mid=$mailing_id"
      ),
      'forward' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=forward&mid=$mailing_id"
      ),
      'reply' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=reply&mid=$mailing_id"
      ),
      'opened' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=opened&mid=$mailing_id&distinct=1"
      ),
    ];

    return $report;
  }

  /**
   * Get the count of mailings
   *
   * @param
   *
   * @return int              Count
   * @access public
   */
  public function getCount() {
    $this->selectAdd();
    $this->selectAdd('COUNT(id) as count');

    $session = CRM_Core_Session::singleton();
    $this->find(TRUE);

    return $this->count;
  }


  static function checkPermission($id) {
    if (!$id) {
      return;
    }

    $mailingIDs = CRM_Mailing_BAO_Mailing::mailingACLIDs();
    if (!in_array($id,
        $mailingIDs
      )) {
       return CRM_Core_Error::statusBounce(ts('You do not have permission to access this mailing report'));
    }
    return;
  }

  static function mailingACL($alias = NULL) {
    $mailingACL = " ( 0 ) ";

    $mailingIDs = self::mailingACLIDs();
    if (!empty($mailingIDs)) {
      $mailingIDs = CRM_Utils_Array::implode(',', $mailingIDs);
      $tableName = !$alias ? self::getTableName() : $alias;
      $mailingACL = " $tableName.id IN ( $mailingIDs ) ";
    }
    return $mailingACL;
  }

  static function &mailingACLIDs($count = FALSE, $condition = NULL) {
    // get all the groups that this user can access
    // if they dont have universal access
    $groups = CRM_Core_PseudoConstant::group();
    if (CRM_Core_Permission::check('Administer CiviCRM') || CRM_Core_Permission::check('view all contacts')) {
      $where = ' ( m.is_hidden = 0 )';
    }
    elseif (!empty($groups)) {
      $groupIDs = CRM_Utils_Array::implode(',', array_keys($groups));
      $where = "( ( g.entity_table = 'civicrm_group' AND g.entity_id IN ( $groupIDs ) ) OR   ( g.entity_table IS NULL AND g.entity_id IS NULL ) ) AND ( m.is_hidden = 0 ) ";
    }

    $selectClause = ($count) ? 'COUNT( DISTINCT m.id) as count' : 'DISTINCT( m.id ) as id';
    // get all the mailings that are in this subset of groups
    $query = "
SELECT    $selectClause 
  FROM    civicrm_mailing m
LEFT JOIN civicrm_mailing_group g ON g.mailing_id   = m.id
 WHERE $where 
   $condition";
   $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    if ($count) {
      $dao->fetch();
      return $dao->count;
    }
    $mailingIDs = [];
    while ($dao->fetch()) {
      $mailingIDs[] = $dao->id;
    }
    return $mailingIDs;
  }

  /**
   * Get the rows for a browse operation
   *
   * @param int $offset       The row number to start from
   * @param int $rowCount     The nmber of rows to return
   * @param string $sort      The sql string that describes the sort order
   *
   * @return array            The rows
   * @access public
   */
  public function &getRows($offset, $rowCount, $sort, $additionalClause = NULL, $additionalParams = NULL) {
    $mailing = self::getTableName();
    $job = CRM_Mailing_BAO_Job::getTableName();
    $group = CRM_Mailing_DAO_Group::getTableName();
    $session = CRM_Core_Session::singleton();

    $mailingACL = self::mailingACL();

    // we only care about parent jobs, since that holds all the info on
    // the mailing
    $query = "
            SELECT      $mailing.id,
                        $mailing.name, 
                        $job.status,
                        $mailing.subject,
                        MIN($job.scheduled_date) as scheduled_date, 
                        MIN($job.start_date) as start_date,
                        MAX($job.end_date) as end_date,
                        createdContact.sort_name as created_by, 
                        scheduledContact.sort_name as scheduled_by,
                        $mailing.created_id as created_id, 
                        $mailing.scheduled_id as scheduled_id,
                        $mailing.is_archived as archived,
                        $mailing.visibility as visibility,
                        $mailing.created_date as created_date
            FROM        $mailing
            LEFT JOIN   $job ON ( $job.mailing_id = $mailing.id AND $job.is_test = 0 AND $job.parent_id IS NULL )
            LEFT JOIN   civicrm_contact createdContact ON ( civicrm_mailing.created_id = createdContact.id )
            LEFT JOIN   civicrm_contact scheduledContact ON ( civicrm_mailing.scheduled_id = scheduledContact.id ) 
            WHERE       $mailingACL $additionalClause  
            GROUP BY    $mailing.id ";

    if ($sort) {
      $orderBy = trim($sort->orderBy());
      if (!empty($orderBy)) {
        $query .= " ORDER BY $orderBy";
      }
    }

    if ($rowCount) {
      $query .= " LIMIT $offset, $rowCount ";
    }

    if (!$additionalParams) {
      $additionalParams = [];
    }

    $dao = CRM_Core_DAO::executeQuery($query, $additionalParams);

    $rows = [];
    while ($dao->fetch()) {
      $rows[] = [
        'id' => $dao->id,
        'name' => $dao->name,
        'subject' => $dao->subject,
        'status' => $dao->status ? $dao->status : 'Not scheduled',
        'created_date' => CRM_Utils_Date::customFormat($dao->created_date),
        'scheduled' => CRM_Utils_Date::customFormat($dao->scheduled_date),
        'scheduled_iso' => $dao->scheduled_date,
        'start' => CRM_Utils_Date::customFormat($dao->start_date),
        'end' => CRM_Utils_Date::customFormat($dao->end_date),
        'created_by' => $dao->created_by,
        'scheduled_by' => $dao->scheduled_by,
        'created_id' => $dao->created_id,
        'scheduled_id' => $dao->scheduled_id,
        'archived' => $dao->archived,
        'visibility' => $dao->visibility,
        /*
        'approval_status_id' => $dao->approval_status_id,
        'campaign_id' => $dao->campaign_id,
        'campaign' => empty($dao->campaign_id) ? NULL : $allCampaigns[$dao->campaign_id],
*/
      ];
    }
    return $rows;
  }

  /**
   * Function to show detail Mailing report
   *
   * @param int $id
   *
   * @static
   * @access public
   */

  static function showEmailDetails($id) {
    return CRM_Utils_System::url('civicrm/mailing/report', "mid=$id");
  }

  /**
   * Delete Mails and all its associated records
   *
   * @param  int  $id id of the mail to delete
   *
   * @return void
   * @access public
   * @static
   */
  public static function del($id) {
    if (empty($id)) {
      CRM_Core_Error::fatal();
    }

    // delete all file attachments

    CRM_Core_BAO_File::deleteEntityFile('civicrm_mailing',
      $id
    );

    $dao = new CRM_Mailing_DAO_Mailing();
    $dao->id = $id;
    $dao->delete();

    CRM_Core_Session::setStatus(ts('Selected mailing has been deleted.'));
  }

  /**
   * Delete Jobss and all its associated records
   * related to test Mailings
   *
   * @param  int  $id id of the Job to delete
   *
   * @return void
   * @access public
   * @static
   */
  public static function delJob($id) {
    if (empty($id)) {
      CRM_Core_Error::fatal();
    }

    $dao = new CRM_Mailing_BAO_Job();
    $dao->id = $id;
    $dao->delete();
  }

  function getReturnProperties() {
    $tokens = &$this->getTokens();

    $properties = [];
    if (isset($tokens['html']) &&
      isset($tokens['html']['contact'])
    ) {
      $properties = array_merge($properties, $tokens['html']['contact']);
    }

    if (isset($tokens['text']) &&
      isset($tokens['text']['contact'])
    ) {
      $properties = array_merge($properties, $tokens['text']['contact']);
    }

    if (isset($tokens['subject']) &&
      isset($tokens['subject']['contact'])
    ) {
      $properties = array_merge($properties, $tokens['subject']['contact']);
    }

    $returnProperties = [];
    $returnProperties['display_name'] = $returnProperties['contact_id'] = $returnProperties['preferred_mail_format'] = $returnProperties['hash'] = 1;

    foreach ($properties as $p) {
      $returnProperties[$p] = 1;
    }

    return $returnProperties;
  }

  /**
   * gives required details of contacts
   *
   * @param  array   $contactIds       of conatcts
   * @param  array   $returnProperties of required properties
   * @param  boolean $skipOnHold       don't return on_hold contact info also.
   * @param  boolean $skipDeceased     don't return deceased contact info.
   * @param  array   $extraParams      extra params
   *
   * @return array
   * @access public
   */
  static function getDetails($contactIDs,
    $returnProperties = NULL,
    $skipOnHold = TRUE,
    $skipDeceased = TRUE,
    $extraParams = NULL,
    $customHook = FALSE
  ) {
    $params = [];
    foreach ($contactIDs as $key => $contactID) {
      $params[] = [CRM_Core_Form::CB_PREFIX . $contactID,
        '=', 1, 0, 0,
      ];
    }

    // fix for CRM-2613
    if ($skipDeceased) {
      $params[] = ['is_deceased', '=', 0, 0, 0];
    }

    //fix for CRM-3798
    if ($skipOnHold) {
      $params[] = ['on_hold', '=', 0, 0, 0];
    }

    if ($extraParams) {
      $params = array_merge($params, $extraParams);
    }

    // if return properties are not passed then get all return properties
    if (empty($returnProperties)) {

      $fields = array_merge(array_keys(CRM_Contact_BAO_Contact::exportableFields()),
        ['display_name', 'checksum', 'contact_id']
      );
      foreach ($fields as $key => $val) {
        $returnProperties[$val] = 1;
      }
    }

    $custom = [];
    foreach ($returnProperties as $name => $dontCare) {
      $cfID = CRM_Core_BAO_CustomField::getKeyID($name);
      if ($cfID) {
        $custom[] = $cfID;
      }
    }

    //get the total number of contacts to fetch from database.
    $numberofContacts = count($contactIDs);


    $details = CRM_Contact_BAO_Query::apiQuery( $params, $returnProperties, NULL, NULL, 0, $numberofContacts, TRUE, TRUE);

    $contactDetails = &$details[0];

    foreach ($contactIDs as $key => $contactID) {
      if (CRM_Utils_Array::arrayKeyExists($contactID, $contactDetails)) {

        if (CRM_Utils_Array::value('preferred_communication_method', $returnProperties) == 1
          && CRM_Utils_Array::arrayKeyExists('preferred_communication_method', $contactDetails[$contactID])
        ) {

          $pcm = CRM_Core_PseudoConstant::pcm();

          // communication Prefferance

          $contactPcm = explode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR,
            $contactDetails[$contactID]['preferred_communication_method']
          );
          $result = [];
          foreach ($contactPcm as $key => $val) {
            if ($val) {
              $result[$val] = $pcm[$val];
            }
          }
          $contactDetails[$contactID]['preferred_communication_method'] = CRM_Utils_Array::implode(', ', $result);
        }

        foreach ($custom as $cfID) {
          if (isset($contactDetails[$contactID]["custom_{$cfID}"])) {
            $contactDetails[$contactID]["custom_{$cfID}"] = CRM_Core_BAO_CustomField::getDisplayValue($contactDetails[$contactID]["custom_{$cfID}"],
              $cfID, $details[1]
            );
          }
        }

        //special case for greeting replacement
        foreach (['email_greeting', 'postal_greeting', 'addressee'] as $val) {
          if (CRM_Utils_Array::value($val, $contactDetails[$contactID])) {
            $contactDetails[$contactID][$val] = $contactDetails[$contactID]["{$val}_display"];
          }
        }
      }
    }

    // also call a hook and get token details
    if (empty($customHook)) {
      CRM_Utils_Hook::tokenValues($details[0], $contactIDs, NULL, [], 'CRM_Mailing_BAO_Mailing_getDetails');
    }
    return $details;
  }

  /**
   * gives required details of a contact
   *
   * @param  int $contactId     
   * @param  int $mailingId
   *
   * @return array
   * @access public
   */
  public static function getContactReport($contactId, $mailingId) {
    $job = CRM_Mailing_BAO_Job::getTableName();
    $eq = CRM_Mailing_Event_DAO_Queue::getTableName();
    $ed = CRM_Mailing_Event_DAO_Delivered::getTableName();
    $eb = CRM_Mailing_Event_DAO_Bounce::getTableName();
    $eo = CRM_Mailing_Event_DAO_Opened::getTableName();
    $ec = CRM_Mailing_Event_DAO_TrackableURLOpen::getTableName();
    $eu = CRM_Mailing_Event_DAO_Unsubscribe::getTableName();

    $from = [];
    $from[] = "INNER JOIN $job ON $job.id = $eq.job_id";
    $from[] = "LEFT JOIN $ed ON $ed.event_queue_id = $eq.id";
    $from[] = "LEFT JOIN $eo ON $eo.event_queue_id = $eq.id";
    $from[] = "LEFT JOIN $ec ON $ec.event_queue_id = $eq.id";
    $from[] = "LEFT JOIN $eb ON $eb.event_queue_id = $eq.id";
    $from[] = "LEFT JOIN $eu as unsubscribe ON unsubscribe.event_queue_id = $eq.id AND unsubscribe.org_unsubscribe = 0";
    $from[] = "LEFT JOIN $eu as optout ON optout.event_queue_id = $eq.id AND optout.org_unsubscribe = 1";
    $select = "SELECT $eq.contact_id, COUNT($ed.time_stamp) as delivered, COUNT($eo.time_stamp) as opened, COUNT($ec.time_stamp) as clicks, COUNT($eb.time_stamp) as bounce, COUNT(unsubscribe.time_stamp) as unsubscribe, COUNT(optout.time_stamp)  as optout";
    $from  = "\n FROM $eq ".CRM_Utils_Array::implode(" ", $from);
    $where = "\n WHERE $eq.contact_id = %1 AND $job.mailing_id = %2 AND $job.is_test = 0";
    $groupBy = "\n GROUP BY $eq.contact_id";
    $dao = CRM_Core_DAO::executeQuery($select . $from . $where . $groupBy, [
      1 => [$contactId, 'Positive'],
      2 => [$mailingId, 'Positive'],
    ]);
    $dao->fetch();
    return [
      'Delivered' => $dao->delivered,
      'Opened' => $dao->opened,
      'Clicks' => $dao->clicks,
      'Bounce' => $dao->bounce,
      'Unsubscribe' => $dao->unsubscribe,
      'Opt-Out' => $dao->optout,
    ];
  }

  /**
   * Function to build the  compose mail form
   *
   * @param   $form
   *
   * @return None
   * @access public
   */

  public static function commonCompose(&$form) {
    //get the tokens.
    $tokens = [];
    if (method_exists($form, 'listTokens')) {
      $tokens = $form->listTokens();
    }

    //token selector for subject
    //sorted in ascending order tokens by ignoring word case
    $form->assign('tokens', CRM_Utils_Token::formatTokensForDisplay($tokens));

    // refs #29057. Added tokens array to become a variable of the form
    $form->assign('tokensArray', $tokens);

    //CRM-5058
    $form->add('select', 'token3', ts('Insert Token'),
      $tokens, FALSE,
      [
        'size' => "5",
        'multiple' => TRUE,
        'onclick' => "return tokenReplText(this);",
      ]
    );

    $form->add('select', 'token1', ts('Insert Tokens'),
      $tokens, FALSE,
      [
        'size' => "5",
        'multiple' => TRUE,
        'onclick' => "return tokenReplText(this);",
      ]
    );

    $form->add('select', 'token2', ts('Insert Tokens'),
      $tokens, FALSE,
      [
        'size' => "5",
        'multiple' => TRUE,
        'onclick' => "return tokenReplHtml(this);",
      ]
    );

    //insert message Text by selecting "Select Template option"
    $form->add('textarea',
      'text_message',
      ts('Plain-text format'),
      [
        'cols' => '80', 'rows' => '8',
        'onkeyup' => "return verify(this)",
      ]
    );
    $form->addWysiwyg('html_message',
      ts('HTML format'),
      [
        'cols' => '80',
        'rows' => '8',
        'fullpage' => '1',
        'onkeyup' => "return verify(this)",
      ]
    );

    //get the tokens.
    $tokens = [];
    if (method_exists($form, 'listTokens')) {
      $tokens = $form->listTokens();
    }

    $templates = [];

    $textFields = ['text_message' => ts('HTML Format'), 'sms_text_message' => ts('SMS Message')];
    $modePrefixes = ['Mail' => NULL, 'SMS' => 'SMS'];

    $className = CRM_Utils_System::getClassName($form);

    if ($className != 'CRM_SMS_Form_Upload' && $className != 'CRM_Contact_Form_Task_SMS' && $className != 'CRM_Event_Form_Task_SMS' && $className != 'CRM_Contribute_Form_Task_SMS') {
      $form->addwysiwyg( 'html_message',
        strstr($className, 'PDF') ? ts('Document Body') : ts('HTML Format'),
        [
          'cols' => '80',
          'rows' => '8',
          'fullpage' => '1',
          'onkeyup' => "return verify(this)",
        ]
      );

      if ($className != 'CRM_Admin_Form_ScheduleReminders') {
        unset($modePrefixes['SMS']);
      }
    }
    else {
      unset($textFields['text_message']);
      unset($modePrefixes['Mail']);
    }

    //insert message Text by selecting "Select Template option"
    foreach ($textFields as $id => $label) {
      $prefix = NULL;
      if ($id == 'sms_text_message') {
        $prefix = "SMS";
        $form->assign('max_sms_length', CRM_SMS_Provider::MAX_SMS_CHAR);
        $form->assign('max_zh_sms_length', CRM_SMS_Provider::MAX_ZH_SMS_CHAR);
      }
      $form->add('textarea', $id, $label,
        [
          'cols' => '80',
          'rows' => '8',
          'onkeyup' => "return verify(this, '{$prefix}')",
        ]
      );
    }

    foreach ($modePrefixes as $prefix) {
      if ($prefix == 'SMS') {
        $availableTemplates = CRM_Core_BAO_MessageTemplates::getMessageTemplates(FALSE, TRUE);
      }
      else {
        $availableTemplates = CRM_Core_BAO_MessageTemplates::getMessageTemplates(FALSE);
      }
      if (!empty($availableTemplates)) {
        $form->assign('templates', TRUE);
        if (!empty($form->_submitValues["{$prefix}saveTemplate"]) && !empty($form->_submitValues["{$prefix}saveTemplateName"])) {
          $justSaved = array_search($form->_submitValues["{$prefix}saveTemplateName"], $availableTemplates);
          $availableTemplates = [
            $justSaved => $availableTemplates[$justSaved],
          ];
          $attr = [];
        }
        else {
          $availableTemplates = ['' => ts('- select -')] + $availableTemplates;
          $attr = ['onChange' => "selectValue( this.value, '{$prefix}');"];
        }
        $form->add('select', "{$prefix}template", ts('Use Template'), $availableTemplates, FALSE, $attr);
        $form->add('checkbox', "{$prefix}updateTemplate", ts('Update Template'), NULL);
      }

      $form->add('checkbox', "{$prefix}saveTemplate", ts('Save As New Template'), NULL, FALSE);
      $form->add('text', "{$prefix}saveTemplateName", ts('Template Title'));

      // always reset this parameter to 0 to prevent duplicate save template
      $form->setConstants([
        "{$prefix}saveTemplate" => 0,
        "{$prefix}saveTemplateName" => '',
      ]);
      // use this to detect if we need update template checked by default
      if (!empty($form->_submitValues["{$prefix}saveTemplate"]) && !empty($form->_submitValues["{$prefix}saveTemplateName"])) {
        $form->setConstants(
          ["{$prefix}updateTemplate" => 1]
        );
        if (!empty($_POST["{$prefix}saveTemplate"])) {
          $form->setConstants(
            ["{$prefix}updateTemplate" => 0]
          );
        }
      }
    }

    // I'm not sure this is ever called.
    $action = CRM_Utils_Request::retrieve('action', 'String', $form, FALSE);
    if ((CRM_Utils_System::getClassName($form) == 'CRM_Contact_Form_Task_PDF') &&
        $action == CRM_Core_Action::VIEW
    ) {
      $form->freeze('html_message');
    }
  }

  /**
   * Function to build the  compose PDF letter form
   *
   * @param   $form
   *
   * @return None
   * @access public
   */
  public static function commonLetterCompose(&$form) {
    //get the tokens.
    $tokens = CRM_Core_SelectValues::contactTokens();
    if (CRM_Utils_System::getClassName($form) == 'CRM_Mailing_Form_Upload') {
      $tokens = array_merge(CRM_Core_SelectValues::mailingTokens(), $tokens);
    }

    //sorted in ascending order tokens by ignoring word case
    natcasesort($tokens);

    $form->assign('tokens', json_encode($tokens));

    $form->add('select', 'token1', ts('Insert Tokens'),
      $tokens, FALSE,
      [
        'size' => "5",
        'multiple' => TRUE,
        'onchange' => "return tokenReplHtml(this);",
      ]
    );


    $form->_templates = CRM_Core_BAO_MessageTemplates::getMessageTemplates(FALSE);
    if (!empty($form->_templates)) {
      $form->assign('templates', TRUE);
      $form->add('select', 'template', ts('Select Template'),
        [
          '' => ts('- select -'),
        ] + $form->_templates, FALSE,
        ['onChange' => "selectValue( this.value );"]
      );
      $form->add('checkbox', 'updateTemplate', ts('Update Template'), NULL);
    }

    $form->add('checkbox', 'saveTemplate', ts('Save As New Template'), NULL, FALSE);
    $form->add('text', 'saveTemplateName', ts('Template Title'));


    $form->addWysiwyg('html_message',
      ts('Your Letter'),
      [
        'cols' => '80', 'rows' => '8',
        'onkeyup' => "return verify(this)",
      ]
    );
    $action = CRM_Utils_Request::retrieve('action', 'String', $form, FALSE);
    if ((CRM_Utils_System::getClassName($form) == 'CRM_Contact_Form_Task_PDF') &&
      $action == CRM_Core_Action::VIEW
    ) {
      $form->freeze('html_message');
    }
  }

  /**
   * Get the search based mailing Ids
   *
   * @return array $mailingIDs, searched base mailing ids.
   * @access public
   */
  public function searchMailingIDs() {
    $group = CRM_Mailing_DAO_Group::getTableName();
    $mailing = self::getTableName();

    $query = "
SELECT  $mailing.id as mailing_id
  FROM  $mailing, $group
 WHERE  $group.mailing_id = $mailing.id
   AND  $group.group_type = 'Base'";

    $searchDAO = CRM_Core_DAO::executeQuery($query);
    $mailingIDs = [];
    while ($searchDAO->fetch()) {
      $mailingIDs[] = $searchDAO->mailing_id;
    }

    return $mailingIDs;
  }

  /**
   * Get the content/components of mailing based on mailing Id
   *
   * @param $report array of mailing report
   *
   * @param $form reference of this
   *
   * @return $report array content/component.
   * @access public
   */
  public static function getMailingContent(&$report, &$form) {
    $htmlHeader = $textHeader = NULL;
    $htmlFooter = $textFooter = NULL;


    if ($report['mailing']['header_id']) {
      $header = new CRM_Mailing_BAO_Component();
      $header->id = $report['mailing']['header_id'];
      $header->find(TRUE);
      $htmlHeader = $header->body_html;
      $textHeader = $header->body_text;
    }

    if ($report['mailing']['footer_id']) {
      $footer = new CRM_Mailing_BAO_Component();
      $footer->id = $report['mailing']['footer_id'];
      $footer->find(TRUE);
      $htmlFooter = $footer->body_html;
      $textFooter = $footer->body_text;
    }

    $text = CRM_Utils_Request::retrieve('text', 'Boolean', $form);
    if ($text) {
      echo "<pre>{$textHeader}</br>{$report['mailing']['body_text']}</br>{$textFooter}</pre>";
      CRM_Utils_System::civiExit();
    }

    $html = CRM_Utils_Request::retrieve('html', 'Boolean', $form);
    if ($html) {
      if (!strstr($html, '</body>')) {
        echo '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body style="margin:0;">'."\n";
        echo $htmlHeader . $report['mailing']['body_html'] . $htmlFooter."\n";
        echo '</body></html>';
      }
      else {
        echo $htmlHeader . $report['mailing']['body_html'] . $htmlFooter;
      }
      CRM_Utils_System::civiExit();
    }

    if (!empty($report['mailing']['body_text'])) {
      $url = CRM_Utils_System::url('civicrm/mailing/report', 'reset=1&text=1&mid=' . $form->_mailing_id);
      $popup = "javascript:popUp(\"$url\");";
      $form->assign('textViewURL', $popup);
    }

    if (!empty($report['mailing']['body_html'])) {
      $url = CRM_Utils_System::url('civicrm/mailing/report', 'reset=1&html=1&mid=' . $form->_mailing_id);
      $popup = "javascript:popUp(\"$url\");";
      $form->assign('htmlViewURL', $popup);
    }


    $report['mailing']['attachment'] = CRM_Core_BAO_File::attachmentInfo('civicrm_mailing',
      $form->_mailing_id
    );
    return $report;
  }

  static function overrideVerp($jobID) {
    static $_cache = [];

    if (!isset($_cache[$jobID])) {
      $query = "
SELECT     override_verp 
FROM       civicrm_mailing
INNER JOIN civicrm_mailing_job ON civicrm_mailing.id = civicrm_mailing_job.mailing_id
WHERE  civicrm_mailing_job.id = %1
";
      $params = [1 => [$jobID, 'Integer']];
      $_cache[$jobID] = CRM_Core_DAO::singleValueQuery($query, $params);
    }
    return $_cache[$jobID];
  }

  static function processQueue() {

    $config = &CRM_Core_Config::singleton();
    CRM_Core_Error::debug_log_message("Beginning processQueue run: {$config->mailerJobsMax}, {$config->mailerJobSize}");


    if (CRM_Core_BAO_MailSettings::defaultDomain() == "FIXME.ORG") {
      CRM_Core_Session::setStatus(ts('The <a href="%1">default mailbox</a> has not been configured. You will find <a href="%2">more info in our online user and administrator guide.</a>', [1 => CRM_Utils_System::url('civicrm/admin/mailSettings', 'reset=1'), 2 => "http://book.civicrm.org/user/basic-setup/email-system-configuration"]));
      return;
    }

    // check if we are enforcing number of parallel cron jobs
    // CRM-8460
    $gotCronLock = FALSE;
    if ($config->mailerJobsMax && $config->mailerJobsMax > 1) {


      $lockArray = range(1, $config->mailerJobsMax);
      shuffle($lockArray);

      // check if we are using global locks
      /*
      // $serverWideLock = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
      //  'civimail_server_wide_lock'
      // );
      */
      $serverWideLock = FALSE;
      foreach ($lockArray as $lockID) {
        $cronLock = new CRM_Core_Lock("civimail.cronjob.{$lockID}", NULL);
        if ($cronLock->isAcquired()) {
          $gotCronLock = TRUE;
          break;
        }
      }

      // exit here since we have enuf cronjobs running
      if (!$gotCronLock) {
        CRM_Core_Error::debug_log_message('Returning early, since max number of cronjobs running');
        return TRUE;
      }
    }


    // load bootstrap to call hooks


    // Split up the parent jobs into multiple child jobs
    CRM_Mailing_BAO_Job::runJobs_pre($config->mailerJobSize);
    CRM_Mailing_BAO_Job::runJobs();
    CRM_Mailing_BAO_Job::runJobs_post();

    // lets release the global cron lock if we do have one
    if ($gotCronLock) {
      $cronLock->release();
    }

    CRM_Core_Error::debug_log_message('Ending processQueue run');
    return TRUE;
  }

  /**
   * @return array
   */
  public static function getMailingsList() {
    static $list = [];

    if (empty($list)) {
      $query = "
SELECT civicrm_mailing.id, civicrm_mailing.name, civicrm_mailing_job.end_date
FROM   civicrm_mailing
INNER JOIN civicrm_mailing_job ON civicrm_mailing.id = civicrm_mailing_job.mailing_id WHERE civicrm_mailing.is_archived = 0 AND civicrm_mailing.is_hidden = 0
ORDER BY civicrm_mailing.name";
      $mailing = CRM_Core_DAO::executeQuery($query);

      while ($mailing->fetch()) {
        $list[$mailing->id] = "{$mailing->name} :: {$mailing->end_date}";
      }
    }

    return $list;
  }

  public static function defaultFromMail($part = ""){
    $localpart = CRM_Core_BAO_MailSettings::defaultLocalpart();
    $localpart = rtrim($localpart, '+');
    $emailDomain = CRM_Core_BAO_MailSettings::defaultDomain();
    $part = empty($part) ? '' : '+'.$part;
    if (!empty($localpart) && !empty($emailDomain)) {
      return $localpart.$part.'@'.$emailDomain;
    }
    return '';
  }

  public static function changeVisibility($mid) {
    if (is_numeric($mid) && !empty($mid)) {
      $mailing = new CRM_Mailing_BAO_Mailing();
      $mailing->id = $mid;
      if ($mailing->find(TRUE)) {
        if ($mailing->visibility == 'User and User Admin Only') {
          $mailing->visibility = 'Public Pages';
        }
        else {
          $mailing->visibility = 'User and User Admin Only'; 
        }
        return $mailing->save();
      }
    }
    return FALSE;
  }

  function checkIsHidden() {
    if (!empty($this->id)) {
      return CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Mailing', $this->id, 'is_hidden');
    }
    return 0;
  }
}

