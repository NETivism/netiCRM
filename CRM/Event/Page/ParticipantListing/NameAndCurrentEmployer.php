<?php

require_once 'CRM/Event/Page/ParticipantListing/Simple.php';
class CRM_Event_Page_ParticipantListing_NameAndCurrentEmployer extends CRM_Core_Page {
  protected $_id;

  protected $_participantListingType;

  protected $_eventTitle;

  protected $_pager;

  function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Integer', $this, TRUE);

    // retrieve Event Title and include it in page title
    $this->_eventTitle = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->_id, 'title');
    CRM_Utils_System::setTitle(ts('%1 - Participants', array(1 => $this->_eventTitle)));

    // we do not want to display recently viewed contacts since this is potentially a public page
    $this->assign('displayRecent', FALSE);
  }

  function run() {
    $this->preProcess();

    $fromClause = "
FROM       civicrm_contact
INNER JOIN civicrm_participant ON civicrm_contact.id = civicrm_participant.contact_id 
INNER JOIN civicrm_event       ON civicrm_participant.event_id = civicrm_event.id
LEFT JOIN  civicrm_email       ON ( civicrm_contact.id = civicrm_email.contact_id AND civicrm_email.is_primary = 1 )
";

    $whereClause = "
WHERE    civicrm_event.id = %1
AND      civicrm_participant.is_test = 0
AND      civicrm_participant.status_id IN ( 1, 2 )";
    $params = array(1 => array($this->_id, 'Integer'));
    $this->pager($fromClause, $whereClause, $params);
    $orderBy = $this->orderBy();

    list($offset, $rowCount) = $this->_pager->getOffsetAndRowCount();

    $query = "
SELECT   civicrm_contact.id           as contact_id    ,
         civicrm_contact.display_name as name          ,
         civicrm_contact.sort_name    as sort_name     ,
         civicrm_contact.employer_id  as employer_id   ,
         (SELECT c.sort_name FROM civicrm_contact c WHERE c.id = civicrm_contact.employer_id)  as employer,
         civicrm_participant.id       as participant_id,
         civicrm_participant.role_id  as role_id,
         civicrm_email.email          as email
         $fromClause
         $whereClause
ORDER BY $orderBy
LIMIT    $offset, $rowCount";

    $rows = array();
    $object = CRM_Core_DAO::executeQuery($query, $params);
    $roles = CRM_Event_PseudoConstant::participantRole();
    while ($object->fetch()) {
      $row = array('id' => $object->contact_id,
        'participantID' => $object->participant_id,
        'name' => $object->name,
        'organization' => $object->employer,
        'role' => $roles[$object->role_id],
      );
      $rows[] = $row;
    }
    $this->assign_by_ref('rows', $rows);

    // summary
    $query = "
SELECT   civicrm_participant.role_id as role_id, count(role_id) as role_count
         $fromClause
         $whereClause
GROUP BY civicrm_participant.role_id
ORDER BY $orderBy
LIMIT    $offset, $rowCount";
    $object = CRM_Core_DAO::executeQuery($query, $params);
    $summary_item = array();
    while($object->fetch()){
      $summary_item[] = $roles[$object->role_id] . ": ". $object->role_count;
    }
    $this->assign_by_ref('summary', $summary_item);

    return parent::run();
  }

  function pager($fromClause, $whereClause, $whereParams) {
    require_once 'CRM/Utils/Pager.php';

    $params = array();

    $params['status'] = ts('Group') . ' %%StatusMessage%%';
    $params['csvString'] = NULL;
    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
    $params['rowCount'] = $this->get(CRM_Utils_Pager::PAGE_ROWCOUNT);
    if (!$params['rowCount']) {
      $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    }

    $query = "
SELECT count( civicrm_contact.id )
       $fromClause
       $whereClause
";

    $params['total'] = CRM_Core_DAO::singleValueQuery($query, $whereParams);
    $this->_pager = new CRM_Utils_Pager($params);
    $this->assign_by_ref('pager', $this->_pager);
  }

  function orderBy() {
    static $headers = NULL;
    require_once 'CRM/Utils/Sort.php';
    if (!$headers) {
      $headers = array(
        array(
          'name' => ts('Name'),
          'sort' => 'civicrm_contact.sort_name',
          'direction' => CRM_Utils_Sort::ASCENDING,
        ),
        array(
          'name' => ts('Organization'),
          'sort' => 'civicrm_contact.employer_id',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Type'),
          'sort' => 'civicrm_participant.role_id',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
      );
    }
    $sortID = NULL;
    if ($this->get(CRM_Utils_Sort::SORT_ID)) {
      $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID), $this->get(CRM_Utils_Sort::SORT_DIRECTION));
    }
    $sort = new CRM_Utils_Sort($headers, $sortID);
    $this->assign_by_ref('headers', $headers);
    $this->assign_by_ref('sort', $sort);
    $this->set(CRM_Utils_Sort::SORT_ID, $sort->getCurrentSortID());
    $this->set(CRM_Utils_Sort::SORT_DIRECTION, $sort->getCurrentSortDirection(employer_id));

    return $sort->orderBy();
  }
}


