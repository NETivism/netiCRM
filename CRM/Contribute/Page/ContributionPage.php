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
 * Create a page for displaying Contribute Pages
 * Contribute Pages are pages that are used to display
 * contributions of different types. Pages consist
 * of many customizable sections which can be
 * accessed.
 *
 * This page provides a top level browse view
 * of all the contribution pages in the system.
 *
 */
class CRM_Contribute_Page_ContributionPage extends CRM_Core_Page {

  public $_action;
  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   */
  private static $_actionLinks;
  private static $_contributionLinks;
  private static $_configureActionLinks;
  private static $_onlineContributionLinks;

  private static $_links = NULL;

  protected $_pager = NULL;

  protected $_sortByCharacter;

  /**
   * Get the action links for this page.
   *
   * @return array $_actionLinks
   *
   */
  function &actionLinks() {
    // check if variable _actionsLinks is populated
    if (!isset(self::$_actionLinks)) {
      // helper variable for nicer formatting
      $deleteExtra = ts('Are you sure you want to delete this Contribution page?');
      $copyExtra = ts('Are you sure you want to make a copy of this Contribution page?');

      $session = CRM_Core_Session::singleton();
      $pageKey = $this->_scope;
      $qfKey = $session->get('qfKey', $pageKey);

      self::$_actionLinks = [
        CRM_Core_Action::COPY => [
          'name' => ts('Make a Copy'),
          'url' => CRM_Utils_System::currentPath(),
          'qs' => 'action=copy&gid=%%id%%&key=%%key%%',
          'title' => ts('Make a Copy of CiviCRM Contribution Page'),
          'extra' => 'onclick = "return confirm(\'' . $copyExtra . '\');"',
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'title' => ts('Disable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Contribute_BAO_ContributionPage' . '\',\'' . 'enable-disable' . '\' );"',
          'ref' => 'disable-action',
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Contribute_BAO_ContributionPage' . '\',\'' . 'disable-enable' . '\' );"',
          'ref' => 'enable-action',
          'title' => ts('Enable'),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => CRM_Utils_System::currentPath(),
          'qs' => 'action=delete&reset=1&id=%%id%%',
          'title' => ts('Delete Custom Field'),
          'extra' => 'onclick = "return confirm(\'' . $deleteExtra . '\');"',
        ],
      ];
    }
    return self::$_actionLinks;
  }

  /**
   * Get the configure action links for this page.
   *
   * @return array $_configureActionLinks
   *
   */
  function &configureActionLinks() {
    // check if variable _actionsLinks is populated
    if (!isset(self::$_configureActionLinks)) {
      $urlString = 'civicrm/admin/contribute/';
      $urlParams = 'reset=1&action=update&id=%%id%%';

      self::$_configureActionLinks = [
        CRM_Core_Action::ADD => [
          'name' => ts('Title and Settings'),
          'title' => ts('Title and Settings'),
          'url' => $urlString . 'settings',
          'qs' => $urlParams,
          'uniqueName' => 'settings',
        ],
        CRM_Core_Action::UPDATE => [
          'name' => ts('Contribution Amounts'),
          'title' => ts('Contribution Amounts'),
          'url' => $urlString . 'amount',
          'qs' => $urlParams,
          'uniqueName' => 'amount',
        ],
        CRM_Core_Action::VIEW => [
          'name' => ts('Membership Settings'),
          'title' => ts('Membership Settings'),
          'url' => $urlString . 'membership',
          'qs' => $urlParams,
          'uniqueName' => 'membership',
        ],
        CRM_Core_Action::PROFILE => [
          'name' => ts('Include Profiles'),
          'title' => ts('Include Profiles'),
          'url' => $urlString . 'custom',
          'qs' => $urlParams,
          'uniqueName' => 'custom',
        ],
        CRM_Core_Action::EXPORT => [
          'name' => ts('Thank-you and Receipting'),
          'title' => ts('Thank-you and Receipting'),
          'url' => $urlString . 'thankYou',
          'qs' => $urlParams,
          'uniqueName' => 'thankYou',
        ],
        CRM_Core_Action::BASIC => [
          'name' => ts('Tell a Friend'),
          'title' => ts('Tell a Friend'),
          'url' => $urlString . 'friend',
          'qs' => $urlParams,
          'uniqueName' => 'friend',
        ],
        CRM_Core_Action::ADVANCED => [
          'name' => ts('Personal Campaign Pages'),
          'title' => ts('Personal Campaign Pages'),
          'url' => $urlString . 'pcp',
          'qs' => $urlParams,
          'uniqueName' => 'pcp',
        ],
        CRM_Core_Action::MAP => [
          'name' => ts('Contribution Widget'),
          'title' => ts('Contribution Widget'),
          'url' => $urlString . 'widget',
          'qs' => $urlParams,
          'uniqueName' => 'widget',
        ],
        CRM_Core_Action::FOLLOWUP => [
          'name' => ts('Premiums'),
          'title' => ts('Premiums'),
          'url' => $urlString . 'premium',
          'qs' => $urlParams,
          'uniqueName' => 'premium',
        ],
      ];
    }

    return self::$_configureActionLinks;
  }

  /**
   * Get the online contribution links.
   *
   * @return array $_onlineContributionLinks.
   *
   */
  function &onlineContributionLinks() {
    if (!isset(self::$_onlineContributionLinks)) {
      self::$_onlineContributionLinks = [
        CRM_Core_Action::RENEW => [
          'name' => ts('Dashlets'),
          'title' => ts('Dashlets'),
          'url' => 'civicrm/admin/contribute',
          'qs' => 'action=update&reset=1&id=%%id%%',
          'uniqueName' => 'dashlets',
        ],
        CRM_Core_Action::REOPEN => [
          'name' => ts('Traffic Source'),
          'title' => ts('Test-drive'),
          'url' => 'civicrm/track/report',
          'qs' => 'reset=1&ptype=civicrm_contribution_page&pid=%%id%%',
          'uniqueName' => 'traffic_source',
        ],
      ];
    }

    return self::$_onlineContributionLinks;
  }

  /**
   * Get the contributions links.
   *
   * @return array $_contributionLinks
   *
   */
  function &contributionLinks() {
    if (!isset(self::$_contributionLinks)) {
      //get contribution dates.

      $dates = CRM_Contribute_BAO_Contribution::getContributionDates();
      foreach (['now', 'yearDate', 'monthDate'] as $date) {
        $$date = $dates[$date];
      }
      $yearNow = $yearDate + 10000;

      $urlString = 'civicrm/contribute/search';
      $urlParams = 'reset=1&pid=%%id%%&force=1&test=0';

      self::$_contributionLinks = [
        CRM_Core_Action::BROWSE => [
          'name' => ts('All'),
          'title' => ts('All'),
          'url' => $urlString,
          'qs' => $urlParams,
          'uniqueName' => 'all_without_date',
        ],
        CRM_Core_Action::DETACH => [
          'name' => ts('Current Month-To-Date'),
          'title' => ts('Current Month-To-Date'),
          'url' => $urlString,
          'qs' => "{$urlParams}&start={$monthDate}&end={$now}",
          'uniqueName' => 'current_month_to_date',
        ],
        CRM_Core_Action::REVERT => [
          'name' => ts('Fiscal Year-To-Date'),
          'title' => ts('Fiscal Year-To-Date'),
          'url' => $urlString,
          'qs' => "{$urlParams}&start={$yearDate}&end={$yearNow}",
          'uniqueName' => 'fiscal_year_to_date',
        ],
        CRM_Core_Action::CLOSE=> [
          'name' => ts('Export Contributions'),
          'title' => ts('Export Contributions'),
          'url' => 'civicrm/contribute/search',
          'qs' => 'reset=1&pid=%%id%%&force=1&test=0',
          'uniqueName' => 'export_contributions',
        ],
      ];
    }

    return self::$_contributionLinks;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   *
   * @return void
   * @access public
   *
   */
  function run() {
    // get the requested action
    $action = CRM_Utils_Request::retrieve('action', 'String',
      // default to 'browse'
      $this, FALSE, 'browse'
    );

    // assign vars to templates
    $this->assign('action', $action);
    $id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE, 0
    );

    // set breadcrumb to append to 2nd layer pages
    $breadCrumb = [['title' => ts('Manage Contribution Pages'),
        'url' => CRM_Utils_System::url(CRM_Utils_System::currentPath(),
          'reset=1'
        ),
      ]];

    // what action to take ?
    if ($action & CRM_Core_Action::ADD) {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(),
          'action=browse&reset=1'
        ));


      $controller = new CRM_Contribute_Controller_ContributionPage(NULL, $action);
      CRM_Utils_System::setTitle(ts('Manage Contribution Page'));
      CRM_Utils_System::appendBreadCrumb($breadCrumb);
      return $controller->run();
    }
    elseif ($action & CRM_Core_Action::UPDATE) {
      CRM_Utils_System::appendBreadCrumb($breadCrumb);
      $page = [];
      CRM_Contribute_BAO_ContributionPage::setValues($id, $page);
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(),
          "action=update&reset=1&id={$id}"
        ));
      $config = CRM_Core_Config::singleton();

      // get shorten url
      $shorten = CRM_Core_OptionGroup::getValue('shorten_url', 'civicrm_contribution_page.'.$id, 'name', 'String', 'value');
      if ($shorten) {
        $this->assign('shorten', $shorten);
      }
      $shorten_pcp = CRM_Core_OptionGroup::getValue('shorten_url', 'civicrm_pcp.'.$id, 'name', 'String', 'value');
      if ($shorten_pcp) {
        $this->assign('shorten_pcp', $shorten_pcp);
      }

      $this->assign('pcp_is_active', 0);
      $pcpInfo = new CRM_Contribute_DAO_PCPBlock();
      $pcpInfo->entity_table = 'civicrm_contribution_page';
      $pcpInfo->entity_id = $id;
      $pcpInfo->find(TRUE);
      if (!empty($pcpInfo->is_active)) {
        $this->assign('pcp_is_active', 1);
      }

      // statistics
      CRM_Utils_System::setTitle(ts('Dashlets')." - ".$page['title']);
      $last3month = date('Y-m-01', strtotime('-3 months'));
      $pageStatistics = CRM_Contribute_Page_DashBoard::getContributionPageStatistics($id, $last3month);
      foreach($pageStatistics['track'] as &$track) {
        $track['display'] = '<div>'.ts("%1 achieved", [1 => "{$track['percent_goal']}% ({$track['count_goal']}".ts('People').")"])."</div><div style='color:grey'>".ts("Total")." {$track['percent']}% ({$track['count']}".ts('People').")</div>";
      }
      if ($track['start'] && $track['end']) {
        $this->assign('period_start', CRM_Utils_Date::customFormat($track['start'], $config->dateformatFull));
        $this->assign('period_end', CRM_Utils_Date::customFormat($track['end'], $config->dateformatFull));
      }
      unset($pageStatistics['page']['title']);
      $this->assign('contribution_page_statistics', $pageStatistics);

      // assign vars to templates
      $this->assign('id', $id);
      $this->assign('is_active', $page['is_active']);
      if (in_array('CiviMember', $config->enableComponents)) {
        $this->assign('CiviMember', TRUE);
      }
    }
    elseif ($action & CRM_Core_Action::COPY) {
      $session = CRM_Core_Session::singleton();
      CRM_Core_Session::setStatus(ts('A copy of the contribution page has been created'));
      $this->copy();
    }
    elseif ($action & CRM_Core_Action::DELETE) {
      CRM_Utils_System::appendBreadCrumb($breadCrumb);

      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(),
          'reset=1&action=browse'
        ));

      $id = CRM_Utils_Request::retrieve('id', 'Positive',
        $this, FALSE, 0
      );
      $query = "
SELECT      ccp.title
FROM        civicrm_contribution_page ccp 
JOIN        civicrm_pcp cp ON ccp.id = cp.contribution_page_id
WHERE       cp.contribution_page_id = {$id}";

      if ($pageTitle = CRM_Core_DAO::singleValueQuery($query)) {
        CRM_Core_Session::setStatus(ts('The \'%1\' cannot be deleted! You must Delete all Personal Campaign Page(s) related with this contribution page prior to deleting the page.', [1 => $pageTitle]));

        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/contribute', 'reset=1'));
      }

      $controller = new CRM_Core_Controller_Simple('CRM_Contribute_Form_ContributionPage_Delete',
        'Delete Contribution Page',
        CRM_Core_Action::DELETE
      );
      $controller->set('id', $id);
      $controller->process();
      return $controller->run();
    }
    else {
      // finally browse the contribution pages
      $this->browse();

      CRM_Utils_System::setTitle(ts('Manage Contribution Pages'));
    }

    return parent::run();
  }

  /**
   * This function is to make a copy of a contribution page, including
   * all the fields in the page
   *
   * @return void
   * @access public
   */
  function copy() {
    $key = CRM_Utils_Request::retrieve('key', 'String',
      CRM_Core_DAO::$_nullObject, TRUE, NULL, 'REQUEST'
    );

    $name = get_class($this);
    if (!CRM_Core_Key::validate($key, $name)) {
      return CRM_Core_Error::statusBounce(ts('Sorry, we cannot process this request for security reasons. The request may have expired or is invalid. Please return to the contribution page list and try again.'));
    }

    $gid = CRM_Utils_Request::retrieve('gid', 'Positive',
      $this, TRUE, 0, 'GET'
    );

    CRM_Contribute_BAO_ContributionPage::copy($gid);

    CRM_Utils_System::redirect(CRM_Utils_System::url(CRM_Utils_System::currentPath(), 'reset=1'));
  }

  /**
   * Browse all contribution pages
   *
   * @return void
   * @access public
   * @static
   */
  function browse($action = NULL) {
    $this->_sortByCharacter = CRM_Utils_Request::retrieve('sortByCharacter',
      'String',
      $this
    );
    $createdId = CRM_Utils_Request::retrieve('cid', 'Positive',
      $this, FALSE, 0
    );

    if ($this->_sortByCharacter == 1 ||
      !empty($_POST)
    ) {
      $this->_sortByCharacter = '';
      $this->set('sortByCharacter', '');
    }

    $this->search();

    $params = [];
    $whereClause = $this->whereClause($params, FALSE);
    $this->pagerAToZ($whereClause, $params);

    $params = [];
    $whereClause = $this->whereClause($params, TRUE);
    $this->pager($whereClause, $params);

    list($offset, $rowCount) = $this->_pager->getOffsetAndRowCount();

    //check for delete CRM-4418

    $allowToDelete = CRM_Core_Permission::check('delete in CiviContribute');

    $query = "
  SELECT  id
    FROM  civicrm_contribution_page
   WHERE  $whereClause
   LIMIT  $offset, $rowCount";
    $contribPage = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Contribute_DAO_ContributionPage');
    $contribPageIds = [];
    while ($contribPage->fetch()) {
      $contribPageIds[$contribPage->id] = $contribPage->id;
    }
    //get all section info.
    $contriPageSectionInfo = CRM_Contribute_BAO_ContributionPage::getSectionInfo($contribPageIds);

    $query = "
SELECT *
FROM civicrm_contribution_page
WHERE $whereClause
ORDER BY is_active DESC, id ASC
   LIMIT $offset, $rowCount";

    $dao = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Contribute_DAO_ContributionPage');

    //get configure actions links.
    $configureActionLinks = self::configureActionLinks();

    $contributionTypes = CRM_Contribute_PseudoConstant::contributionType(NULl, NULL, TRUE);
    $contributionPage = [];

    // Add key for action validation.
    $name = get_class($this);
    $key = CRM_Core_Key::get($name);
    $this->assign('key', $key);

    while ($dao->fetch()) {
      $contributionPage[$dao->id] = [];
      CRM_Core_DAO::storeValues($dao, $contributionPage[$dao->id]);
      $contributionPage[$dao->id]['contribution_type'] = $contributionTypes[$dao->contribution_type_id];

      $contributionPage[$dao->id]['is_active'] = $dao->is_active & CRM_Contribute_BAO_ContributionPage::IS_ACTIVE;
      $contributionPage[$dao->id]['is_special'] = ($dao->is_active & CRM_Contribute_BAO_ContributionPage::IS_SPECIAL) ? 1 : 0;

      $action = self::checkPerm($contributionPage[$dao->id]);

      //build the configure links.
      $sectionsInfo = CRM_Utils_Array::value($dao->id, $contriPageSectionInfo, []);
      $contributionPage[$dao->id]['configureActionLinks'] = CRM_Core_Action::formLink(self::formatConfigureLinks($sectionsInfo),
        $action,
        ['id' => $dao->id],
        ts('Configure'),
        TRUE
      );

      //build the contributions links.
      $contributionPage[$dao->id]['contributionLinks'] = CRM_Core_Action::formLink(self::contributionLinks(),
        $action,
        ['id' => $dao->id],
        ts('Contributions'),
        TRUE
      );

      //build the online contribution links.
      $contributionPage[$dao->id]['onlineContributionLinks'] = CRM_Core_Action::formLink(self::onlineContributionLinks(),
        $action,
        ['id' => $dao->id],
        ts('Links'),
        TRUE
      );

      //build the normal action links.
      $contributionPage[$dao->id]['action'] = CRM_Core_Action::formLink(self::actionLinks(),
        $action,
        [
          'id' => $dao->id,
          'key' => $key
        ],
        ts('more'),
        TRUE
      );
    }

    if (isset($contributionPage)) {
      $this->assign('rows', $contributionPage);
    }
  }

  function search() {
    if (isset($this->_action) &
      (CRM_Core_Action::ADD |
        CRM_Core_Action::UPDATE |
        CRM_Core_Action::DELETE
      )
    ) {
      return;
    }

    $form = new CRM_Core_Controller_Simple('CRM_Contribute_Form_SearchContribution',
      ts('Search Contribution'),
      CRM_Core_Action::ADD
    );
    $form->setEmbedded(TRUE);
    $form->setParent($this);
    $form->process();
    $form->run();
  }

  function whereClause(&$params, $sortBy = TRUE) {
    $values = $clauses = [];
    $title = $this->get('title');
    $createdId = $this->get('cid');

    if ($createdId) {
      $clauses[] = "(created_id = {$createdId})";
    }

    if ($title) {
      $clauses[] = "title LIKE %1";
      if (strpos($title, '%') !== FALSE) {
        $params[1] = [trim($title), 'String', FALSE];
      }
      else {
        $params[1] = [trim($title), 'String', TRUE];
      }
    }

    $value = $this->get('contribution_type_id');
    $val = [];
    if ($value) {
      if (is_array($value)) {
        foreach ($value as $k => $v) {
          if ($v) {
            $val[$v] = $v;
          }
        }
        $type = CRM_Utils_Array::implode(',', $val);
      }

      $clauses[] = "contribution_type_id IN ({$type})";
    }

    if ($sortBy &&
      $this->_sortByCharacter
    ) {
      $clauses[] = 'title LIKE %3';
      $params[3] = [$this->_sortByCharacter . '%', 'String'];
    }

    if (empty($clauses)) {
      // Let template know if user has run a search or not
      $this->assign('isSearch', 0);
      return 1;
    }
    else {
      $this->assign('isSearch', 1);
    }

    return CRM_Utils_Array::implode(' AND ', $clauses);
  }

  function pager($whereClause, $whereParams) {


    $params['status'] = ts('Contribution %%StatusMessage%%');
    $params['csvString'] = NULL;
    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
    $params['rowCount'] = $this->get(CRM_Utils_Pager::PAGE_ROWCOUNT);
    if (!$params['rowCount']) {
      $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    }

    $query = "
SELECT count(id)
  FROM civicrm_contribution_page
 WHERE $whereClause";

    $params['total'] = CRM_Core_DAO::singleValueQuery($query, $whereParams);

    $this->_pager = new CRM_Utils_Pager($params);
    $this->assign_by_ref('pager', $this->_pager);
  }

  function pagerAtoZ($whereClause, $whereParams) {


    $query = "
   SELECT DISTINCT UPPER(LEFT(title, 1)) as sort_name
     FROM civicrm_contribution_page
    WHERE $whereClause
 ORDER BY LEFT(title, 1)
";
    $dao = CRM_Core_DAO::executeQuery($query, $whereParams);

    $aToZBar = CRM_Utils_PagerAToZ::getAToZBar($dao, $this->_sortByCharacter, TRUE);
    $this->assign('aToZ', $aToZBar);
  }

  function formatConfigureLinks($sectionsInfo) {
    //build the formatted configure links.
    $formattedConfLinks = self::configureActionLinks();
    foreach ($formattedConfLinks as $act => & $link) {
      $sectionName = CRM_Utils_Array::value('uniqueName', $link);
      if (!$sectionName) {
        continue;
      }

      $classes = [];
      if (isset($link['class'])) {
        $classes = $link['class'];
      }

      if (!CRM_Utils_Array::value($sectionName, $sectionsInfo)) {
        $classes = [];
        if (isset($link['class'])) {
          $classes = $link['class'];
        }
        $link['class'] = array_merge($classes, ['disabled']);
      }
    }

    return $formattedConfLinks;
  }

  function checkPerm($page) {
    $configureActionLinks = self::configureActionLinks();

    // form all action links
    $perm = array_sum(array_keys(self::actionLinks()));

    //add configure actions links.
    $perm += array_sum(array_keys($configureActionLinks));

    //add online contribution links.
    $perm += array_sum(array_keys(self::onlineContributionLinks()));

    //add contribution search links.
    $perm += array_sum(array_keys(self::contributionLinks()));

    if ($page['is_active']) {
      $perm -= CRM_Core_Action::ENABLE;
    }
    else {
      $perm -= CRM_Core_Action::DISABLE;
    }

    $allowToDelete = CRM_Core_Permission::check('delete in CiviContribute');
    if (!$allowToDelete) {
      $perm -= CRM_Core_Action::DELETE;
    }
    return $perm;
  }
}

