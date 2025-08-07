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

class CRM_Contact_Page_DedupeFind extends CRM_Core_Page_Basic {
  public $_context;
  public $_currentPage;
  public $_numPerPage;
  public $_contactIds;
  public $_cachePath;
  public $action;
  /**
   * @var int[]
   */
  public $_cids;
  public $_cidString;
  public $_pager;
  /**
   * queue name
   */
  const QUEUE_NAME = 'dedupe_running';

  /**
   * running time limit
   */
  const RUNNING_TIME_LIMIT = 1800;

  protected $_cid = NULL;
  protected $_rgid;
  protected $_mainContacts;
  protected $_gid;

  /**
   * Get BAO Name
   *
   * @return string Classname of BAO.
   */
  function getBAOName() {
    return 'CRM_Dedupe_BAO_RuleGroup';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {}

  /**
   * Browse all rule groups
   *
   * @return void
   * @access public
   */
  function run() {
    set_time_limit(self::RUNNING_TIME_LIMIT);
    $this->_gid = CRM_Utils_Request::retrieve('gid', 'Positive', $this, FALSE, 0);
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 0);
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);
    $this->_cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE, 0);
    $this->_rgid = CRM_Utils_Request::retrieve('rgid', 'Positive', $this, FALSE, 0);
    $this->_currentPage = CRM_Utils_Request::retrieve(CRM_Utils_Pager::PAGE_ID, 'Integer', $this, FALSE, 1);
    $this->_numPerPage = CRM_Utils_Request::retrieve(CRM_Utils_Pager::PAGE_ROWCOUNT, 'Integer', $this, FALSE, 25);
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $this->purgeFoundDupes();
    }

    $session = CRM_Core_Session::singleton();
    if ($this->_context == 'search') {
      $this->_contactIds = $session->get('selectedSearchContactIds');
      $str = CRM_Utils_Array::implode('|',$this->_contactIds);
      $this->_cachePath = 'search:'.md5($str);
      $backURL = $session->readUserContext();
      $renewURL = CRM_Utils_System::url('civicrm/contact/dedupefind', "reset=1&action=renew&context=search");
    }
    else {
      if (!empty($this->_rgid)) {
        $dedupeGroupParams = ['id' => $this->_rgid];
        $ruleGroup = CRM_Dedupe_BAO_RuleGroup::getDetailsByParams($dedupeGroupParams);
        $ruleGroup = $ruleGroup[$this->_rgid];
        if (!$ruleGroup['threshold']) {
          $editUrl = CRM_Utils_System::url('civicrm/contact/deduperules', 'action=update&id='.$ruleGroup['id']);
          $message = ts('Please correct the following errors in the form fields below:').'<br>';
          $message .= ts('Dedupe Rule Group')." - {$ruleGroup['name']} ".ts("Weight Threshold to Consider Contacts 'Matching':").' '.$ruleGroup['threshold'];
          $session->setStatus($message, TRUE, 'error');
          CRM_Utils_System::redirect($editUrl);
        }

        if (!empty($this->_gid)) {
          $this->_cachePath = 'rgid:'.$this->_rgid."-gid:".$this->_gid;
          $backURL = CRM_Utils_System::url('civicrm/contact/dedupefind', "reset=1&rgid={$this->_rgid}&gid={$this->_gid}&action=preview");
          $renewURL = CRM_Utils_System::url('civicrm/contact/dedupefind', "reset=1&rgid={$this->_rgid}&gid={$this->_gid}&action=renew");
        }
        else {
          $this->_cachePath = 'rgid:'.$this->_rgid;
          $backURL = CRM_Utils_System::url('civicrm/contact/dedupefind', "reset=1&rgid={$this->_rgid}&action=preview");
          $renewURL = CRM_Utils_System::url('civicrm/contact/dedupefind', "reset=1&rgid={$this->_rgid}&action=renew");
        }
      }
      else {
        $backURL = CRM_Utils_System::url('civicrm/contact/deduperules', "reset=1");
      }
    }
    $this->assign('backURL', $backURL);
    if (!empty($renewURL)) {
      $this->assign('renewURL', $renewURL);
    }

    if ($action & CRM_Core_Action::RENEW) {
      $this->purgeFoundDupes();
      $url = str_replace("action=renew", 'action=update', $renewURL);
      CRM_Utils_System::redirect($url);
    }
    elseif ($action & CRM_Core_Action::MAP) {
      $result = $this->batchMergeDupes(); 
      $this->purgeFoundDupes();
      $session->setStatus(ts("Merged %1 pairs of contacts.", [1 => count($result['merged'])]).' '.ts('Skipped %1 pairs of contacts', [1 => count($result['skipped'])]));
      $url = str_replace("action=renew", 'action=update', $renewURL);
      CRM_Utils_System::redirect($url);
    }
    elseif ($action & CRM_Core_Action::UPDATE || $action & CRM_Core_Action::BROWSE) {
      $this->action = CRM_Core_Action::UPDATE;

      // get cache when available
      $foundDupes = $this->getFoundDupes();
      if (!empty($foundDupes)) {
        $datetime = CRM_Core_BAO_Cache::getItemCreatedDate('Dedupe Found Dupes', $this->_cachePath, NULL);
        $session->setStatus(ts("This list is cached result, generated at %1.", [1 => CRM_Utils_Date::customFormat($datetime)]).' '.ts('You can renew this list by click "Refresh" button.'));
      }
      else{
        if ($this->dedupeRunning()) {
          CRM_Core_Error::fatal(ts('You have another running dedupe job. For system performance concern, we can only allow one dedupe job concurrently. Please try again later.'));
          return;
        }
        else {
          $this->dedupeStart();
        }
        if ($this->_gid) {
          $foundDupes = CRM_Dedupe_Finder::dupesInGroup($this->_rgid, $this->_gid);
        }
        // do not cache this
        elseif (!empty($this->_contactIds)) {
          $foundDupes = CRM_Dedupe_Finder::dupes($this->_rgid, $this->_contactIds);
        }
        else {
          $foundDupes = CRM_Dedupe_Finder::dupes($this->_rgid);
        }
        if (!empty($foundDupes)) {
          $this->storeFoundDupes($foundDupes);
        }
        $this->dedupeEnd();
      }
      if (!$foundDupes) {
        $ruleGroup = new CRM_Dedupe_BAO_RuleGroup();
        $ruleGroup->id = $this->_rgid;
        $ruleGroup->find(TRUE);

        $session = CRM_Core_Session::singleton();
        $session->setStatus(ts("No possible duplicates were found using %1 rule.", [1 => $ruleGroup->name]));
        CRM_Utils_System::redirect($backURL);
      }
      else {
        $this->_cids = [];
        if (count($foundDupes) > $this->_numPerPage) {
          $this->pager(count($foundDupes));
        }
        // pager slice
        $foundDupes = array_slice($foundDupes, ($this->_currentPage-1)*$this->_numPerPage, $this->_numPerPage);

        foreach ($foundDupes as $dupe) {
          $this->_cids[$dupe[0]] = 1;
          $this->_cids[$dupe[1]] = 1;
        }
        $this->_cidString = CRM_Utils_Array::implode(', ', array_keys($this->_cids));
        $sql = "SELECT id, sort_name FROM civicrm_contact WHERE id IN ($this->_cidString) ORDER BY sort_name";
        $dao = new CRM_Core_DAO();
        $dao->query($sql);
        $displayNames = [];
        while ($dao->fetch()) {
          $displayNames[$dao->id] = $dao->sort_name;
        }
        // FIXME: sort the contacts; $displayName
        // is already sort_name-sorted, so use that
        // (also, consider sorting by dupe count first)
        // lobo - change the sort to by threshold value
        // so the more likely dupes are sorted first
        $session = CRM_Core_Session::singleton();
        $userId = $session->get('userID');
        $mainContacts = [];
        foreach ($foundDupes as $dupes) {
          $dstID = $dupes[0];
          $srcID = $dupes[1];
          if ($dstID == $userId) {
            $dstID = $dupes[1];
            $srcID = $dupes[0];
          }
          $canMerge = (CRM_Contact_BAO_Contact_Permission::allow($dstID, CRM_Core_Permission::EDIT) && CRM_Contact_BAO_Contact_Permission::allow($srcID, CRM_Core_Permission::EDIT));

          $conflicts = [];
          $batchMerge = TRUE;
          if ($canMerge) {
            $dupePairs = [
              0 => [
                'dstID' => $dstID,
                'srcID' => $srcID,
              ],
            ];
            $testResult = CRM_Dedupe_Merger::merge($dupePairs, [], 'safe', FALSE, FALSE, CRM_Core_Action::PREVIEW);
            if (!empty($testResult['skipped'])) {
              $conflicts = CRM_Dedupe_Merger::formatReason($testResult['skipped'][0]['reason']);
              $batchMerge = FALSE;
            }
          }

          $mainContacts[] = ['srcID' => $srcID,
            'srcName' => $displayNames[$srcID],
            'dstID' => $dstID,
            'dstName' => $displayNames[$dstID],
            'weight' => $dupes[2],
            'conflicts' => $conflicts,
            'canMerge' => $canMerge,
            'batchMerge' => $batchMerge,
          ];
        }
        $this->_mainContacts = $mainContacts;

        $session = CRM_Core_Session::singleton();
        if ($this->_cid) {
          $session->pushUserContext(CRM_Utils_System::url('civicrm/contact/deduperules', "action=update&rgid={$this->_rgid}&gid={$this->_gid}&cid={$this->_cid}"));
        }
        else {
          $session->pushUserContext(CRM_Utils_System::url('civicrm/contact/dedupefind', "reset=1&action=update&rgid={$this->_rgid}"));
        }
      }
      $this->assign('action', $this->action);
      $this->browse();
    }
    else {
      $this->action = CRM_Core_Action::UPDATE;
      $this->edit($this->action);
      $this->assign('action', $this->action);
    }
    $this->assign('context', $this->_context);

    // parent run
    parent::run();
  }

  /**
   * Browse all rule groups
   *
   * @return void
   * @access public
   */
  function browse() {
    $this->assign('main_contacts', $this->_mainContacts);

    if ($this->_cid) {
      $this->assign('cid', $this->_cid);
    }
    if (isset($this->_gid) || $this->_gid) {
      $this->assign('gid', $this->_gid);
    }
    $this->assign('rgid', $this->_rgid);
  }

  /**
   * Get name of edit form
   *
   * @return string  classname of edit form
   */
  function editForm() {
    return 'CRM_Contact_Form_DedupeFind';
  }

  /**
   * Get edit form name
   *
   * @return string  name of this page
   */
  function editName() {
    return 'DedupeFind';
  }

  /**
   * Get user context
   *
   * @return string  user context
   */
  function userContext($mode = NULL) {
    return 'civicrm/contact/dedupefind';
  }

  function storeFoundDupes($dupes) {
    CRM_Core_BAO_Cache::setItem($dupes, 'Dedupe Found Dupes', $this->_cachePath);
  }

  function getFoundDupes() {
    $createdTime = CRM_REQUEST_TIME - 3600*6; // 6 hours
    return CRM_Core_BAO_Cache::getItem('Dedupe Found Dupes', $this->_cachePath, NULL, $createdTime);
  }

  function purgeFoundDupes() {
    return CRM_Core_BAO_Cache::deleteItem('Dedupe Found Dupes', $this->_cachePath);
  }

  function batchMergeDupes() {
    $foundDupes = $this->getFoundDupes();
    $merged = $skipped = [];
    if (!empty($foundDupes)) {
      // skip conflict pairs
      foreach($foundDupes as $idx => $pair) {
        $dupePairs = [
          0 => [
            'dstID' => $pair[0],
            'srcID' => $pair[1],
          ],
        ];
        $testResult = CRM_Dedupe_Merger::merge($dupePairs, [], 'safe', FALSE, FALSE, CRM_Core_Action::PREVIEW); // detection
        if (!empty($testResult['skipped'])) {
          unset($foundDupes[$idx]);
          $skipped[] = $testResult['skipped'][0];
        }
      }
      if (empty($foundDupes)) {
        return [
          'merged' => $merged,
          'skipped' => $skipped,
        ];
      }

      // list prepared, generated tree map sorted pairs
      // this is real merge job
      $sortedDupes = CRM_Dedupe_Merger::sortDupes($foundDupes);
      foreach ($sortedDupes as $key => $pair) {
        $dupePairs = [
          0 => [
            'dstID' => $pair[0],
            'srcID' => $pair[1],
          ],
        ];
        $result = CRM_Dedupe_Merger::merge($dupePairs, [], 'safe', FALSE, FALSE, CRM_Core_Action::UPDATE); // real proceed
        if (!empty($result['merged'][0])) {
          $merged[] = $result['merged'][0];
        }
      }
    }
    return [
      'merged' => $merged,
      'skipped' => $skipped,
    ];
  }

  function pager($total) {
    $params = [];
    $params['status'] = '';
    $params['csvString'] = NULL;
    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
    $params['rowCount'] = $this->get(CRM_Utils_Pager::PAGE_ROWCOUNT);
    if (!$params['rowCount']) {
      $params['rowCount'] = $this->_numPerPage;
    }

    $params['total'] = $total;
    $this->_pager = new CRM_Utils_Pager($params);
    $this->assign_by_ref('pager', $this->_pager);
  }

  static function dedupeRunning() {
    $dao = new CRM_Core_DAO_Sequence();
    $dao->name = self::QUEUE_NAME;
    if ($dao->find(TRUE)) {
      $timestamp = (int) $dao->timestamp;
      if (CRM_REQUEST_TIME - $timestamp < self::RUNNING_TIME_LIMIT) {
        return TRUE;
      }
    }
    $dao->free();
    return FALSE;
  }

  function dedupeStart() {
    $dao = new CRM_Core_DAO_Sequence();
    $dao->name = self::QUEUE_NAME;
    if ($dao->find(TRUE)) {
      $dao->timestamp = microtime(true);
      $dao->value = $this->_cachePath;
      $dao->update();
    }
    else {
      $dao->timestamp = microtime(true);
      $dao->value = $this->_cachePath;
      $dao->insert();
    }
    return $dao;
  }

  function dedupeEnd() {
    $dao = new CRM_Core_DAO_Sequence();
    $dao->name = self::QUEUE_NAME;
    if ($dao->find()) {
      return $dao->delete();
    }
    return FALSE;
  }
}

