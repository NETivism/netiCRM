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


class CRM_Event_BAO_Event extends CRM_Event_DAO_Event {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Event_BAO_ManageEvent object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $event = new CRM_Event_DAO_Event();
    $event->copyValues($params);
    if ($event->find(TRUE)) {
      CRM_Core_DAO::storeValues($event, $defaults);
      return $event;
    }
    return NULL;
  }

  static function retrieveField($eventId, $fieldName) {
    $params = ['id' => $eventId];
    $object = [];
    self::retrieve($params, $object);
    if (isset($object[$fieldName])) {
      return $object[$fieldName];
    }
    return NULL;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Event_DAO_Event', $id, 'is_active', $is_active);
  }

  /**
   * function to add the event
   *
   * @param array $params reference array contains the values submitted by the form
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function add(&$params) {

    CRM_Utils_System::flushCache();



    if (CRM_Utils_Array::value('id', $params)) {
      CRM_Utils_Hook::pre('edit', 'Event', $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'Event', NULL, $params);
    }


    $event = new CRM_Event_DAO_Event();

    $event->copyValues($params);
    $result = $event->save();

    if (CRM_Utils_Array::value('id', $params)) {
      CRM_Utils_Hook::post('edit', 'Event', $event->id, $event);
    }
    else {
      CRM_Utils_Hook::post('create', 'Event', $event->id, $event);
    }

    return $result;
  }

  /**
   * function to create the event
   *
   * @param array $params reference array contains the values submitted by the form
   *
   * @access public
   * @static
   *
   */
  public static function create(&$params) {

    $transaction = new CRM_Core_Transaction();

    $event = self::add($params);

    if (is_a($event, 'CRM_Core_Error')) {
      CRM_Core_DAO::transaction('ROLLBACK');
      return $event;
    }

    $session = &CRM_Core_Session::singleton();
    $contactId = $session->get('userID');
    if (!$contactId) {
      $contactId = CRM_Utils_Array::value('contact_id', $params);
    }

    // Log the information on successful add/edit of Event

    $logParams = [
      'entity_table' => 'civicrm_event',
      'entity_id' => $event->id,
      'modified_id' => $contactId,
      'modified_date' => date('Ymd'),
    ];

    CRM_Core_BAO_Log::add($logParams);

    if (CRM_Utils_Array::value('custom', $params) &&
      is_array($params['custom'])
    ) {

      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_event', $event->id);
    }

    $transaction->commit();

    return $event;
  }

  /**
   * Function to delete the event
   *
   * @param int $id  event id
   *
   * @access public
   * @static
   *
   */
  static function del($id) {
    if (!$id) {
      return NULL;
    }


    CRM_Utils_Hook::pre('delete', 'Event', $id, CRM_Core_DAO::$_nullArray);


    $extends = ['event'];
    $groupTree = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, NULL, $extends);
    foreach ($groupTree as $values) {
      $query = "DELETE FROM " . $values['table_name'] . " WHERE entity_id = " . $id;

      $params = [1 => [$values['table_name'], 'string'],
        2 => [$id, 'integer'],
      ];

      CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    }

    $dependencies = [
      'CRM_Core_DAO_OptionGroup' => ['name' => 'civicrm_event.amount.' . $id],
      'CRM_Core_DAO_UFJoin' => [
        'entity_id' => $id,
        'entity_table' => 'civicrm_event',
      ],
    ];

    foreach ($dependencies as $daoName => $values) {
      $dao = new $daoName( );
      if ($daoName == 'CRM_Core_DAO_OptionGroup') {
        $dao->name = $values['name'];
        $dao->find();
        while ($dao->fetch()) {
          CRM_Core_BAO_OptionGroup::del($dao->id);
        }
      }
      else {
        foreach ($values as $fieldName => $fieldValue) {
          $dao->$fieldName = $fieldValue;
        }

        $dao->find();

        while ($dao->fetch()) {
          $dao->delete();
        }
      }
    }

    CRM_Core_OptionGroup::deleteAssoc("civicrm_event.amount.{$id}.discount.%", "LIKE");

    // price set cleanup, CRM-5527

    CRM_Price_BAO_Set::removeFrom('civicrm_event', $id);


    $event = new CRM_Event_DAO_Event();
    $event->id = $id;

    if ($event->find(TRUE)) {
      $locBlockId = $event->loc_block_id;
      $result = $event->delete();

      if (!is_null($locBlockId)) {
        self::deleteEventLocBlock($locBlockId, $id);
      }

      CRM_Utils_Hook::post('delete', 'Event', $id, $event);
      return $result;
    }

    return NULL;
  }

  /**
   * Function to delete the location block associated with an event,
   * if not being used by any other event.
   *
   * @param int $loc_block_id    location block id to be deleted
   * @param int $eventid         event id with which loc block is associated
   *
   * @access public
   * @static
   *
   */
  static function deleteEventLocBlock($locBlockId, $eventId = NULL) {
    $query = "SELECT count(ce.id) FROM civicrm_event ce WHERE ce.loc_block_id = $locBlockId";

    if ($eventId) {
      $query .= " AND ce.id != $eventId;";
    }

    $locCount = CRM_Core_DAO::singleValueQuery($query);

    if ($locCount == 0) {

      CRM_Core_BAO_Location::deleteLocBlock($locBlockId);
    }
  }

  /**
   * Function to get current/future Events
   *
   * @param $all boolean true if events all are required else returns current and future events
   * @param $id  int     id of a specific event to return
   *
   * @static
   */
  static function getEvents($all = FALSE, $id = FALSE, $isActive = TRUE) {
    $query = "SELECT `id`, `title`, `start_date` FROM `civicrm_event` WHERE ( civicrm_event.is_template IS NULL OR civicrm_event.is_template = 0 )";

    if ($id) {
      $query .= " AND `id` = {$id}";
    }
    elseif (!$all) {
      $endDate = date('YmdHis');
      $query .= " AND ( `end_date` >= {$endDate} OR end_date IS NULL )";
    }
    if ($isActive) {
      $query .= " AND civicrm_event.is_active = 1";
    }

    $query .= " ORDER BY title asc";
    $events = [];

    $dao = &CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      if (CRM_Event_BAO_Event::checkPermission($dao->id) && $dao->title) {
        $events[$dao->id] = $dao->title . ' - ' . CRM_Utils_Date::customFormat($dao->start_date);
      }
    }

    return $events;
  }

  /**
   * Get event title
   *
   * @param int $id
   * @return string
   */
  static function getEventTitle($id) {
    if  (!empty($id)) {
      return CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $id, 'title');
    }
    return '';
  }

  /**
   * Function to get events Summary
   *
   * @static
   *
   * @return array Array of event summary values
   */
  static function getEventSummary() {
    $eventSummary = $eventIds = [];

    $config = CRM_Core_Config::singleton();

    // We're fetching recent and upcoming events (where start date is 7 days ago OR later)
    $query = "SELECT count(id) as total_events
                  FROM   civicrm_event e
                  WHERE  e.is_active=1 AND
                        ( e.is_template IS NULL OR e.is_template = 0) AND
                        e.start_date >= DATE_SUB( NOW(), INTERVAL 7 day );";

    $dao = &CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);

    if ($dao->fetch()) {
      $eventSummary['total_events'] = $dao->total_events;
    }

    if (empty($eventSummary) ||
      $dao->total_events == 0
    ) {
      return $eventSummary;
    }

    //get the participant status type values.
    $query = 'SELECT id, name, class FROM civicrm_participant_status_type';
    $status = CRM_Core_DAO::executeQuery($query);
    $statusValues = [];
    while ($status->fetch()) {
      $statusValues[$status->id]['id'] = $status->id;
      $statusValues[$status->id]['name'] = $status->name;
      $statusValues[$status->id]['class'] = $status->class;
    }

    // Get the Id of Option Group for Event Types

    $optionGroupDAO = new CRM_Core_DAO_OptionGroup();
    $optionGroupDAO->name = 'event_type';
    $optionGroupId = NULL;
    if ($optionGroupDAO->find(TRUE)) {
      $optionGroupId = $optionGroupDAO->id;
    }

    $query = "
SELECT     civicrm_event.id as id, civicrm_event.title as event_title, civicrm_event.is_public as is_public,
           civicrm_event.max_participants as max_participants, civicrm_event.start_date as start_date,
           civicrm_event.end_date as end_date, civicrm_event.is_online_registration, civicrm_event.is_monetary, civicrm_event.is_show_location,civicrm_event.is_map as is_map, civicrm_option_value.label as event_type, civicrm_tell_friend.is_active as is_friend_active,
           civicrm_event.summary as summary
FROM       civicrm_event
LEFT JOIN  civicrm_option_value ON (
           civicrm_event.event_type_id = civicrm_option_value.value AND
           civicrm_option_value.option_group_id = %1 )
LEFT JOIN  civicrm_tell_friend ON ( civicrm_tell_friend.entity_id = civicrm_event.id  AND civicrm_tell_friend.entity_table = 'civicrm_event' )
WHERE      civicrm_event.is_active = 1 AND
           ( civicrm_event.is_template IS NULL OR civicrm_event.is_template = 0) AND
           civicrm_event.start_date >= DATE_SUB( NOW(), INTERVAL 7 day )
GROUP BY   civicrm_event.id
ORDER BY   civicrm_event.start_date ASC
LIMIT      0, 10
";
    $eventParticipant = [];



    $properties = ['eventTitle' => 'event_title', 'isPublic' => 'is_public',
      'maxParticipants' => 'max_participants', 'startDate' => 'start_date',
      'endDate' => 'end_date', 'eventType' => 'event_type',
      'isMap' => 'is_map', 'participants' => 'participants',
      'notCountedDueToRole' => 'notCountedDueToRole',
      'notCountedDueToStatus' => 'notCountedDueToStatus',
      'notCountedParticipants' => 'notCountedParticipants',
    ];

    $permissions = CRM_Event_BAO_Event::checkPermission();

    $params = [1 => [$optionGroupId, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    while ($dao->fetch()) {
      if (in_array($dao->id, $permissions[CRM_Core_Permission::VIEW])) {
        foreach ($properties as $property => $name) {
          $set = NULL;
          switch ($name) {
            case 'is_public':
              if ($dao->$name) {
                $set = 'Yes';
              }
              else {
                $set = 'No';
              }
              $eventSummary['events'][$dao->id][$property] = $set;
              break;

            case 'is_map':
              if ($dao->$name && $config->mapAPIKey) {
                $params = [];
                $values = [];
                $ids = [];
                $params = ['entity_id' => $dao->id, 'entity_table' => 'civicrm_event'];

                $values['location'] = CRM_Core_BAO_Location::getValues($params, TRUE);
                if (is_numeric(CRM_Utils_Array::value('geo_code_1', $values['location']['address'][1])) ||
                  ($config->mapGeoCoding &&
                    $values['location']['address'][1]['city'] &&
                    $values['location']['address'][1]['state_province_id']
                  )
                ) {
                  $set = CRM_Utils_System::url('civicrm/contact/map/event', "reset=1&eid={$dao->id}");
                }
              }

              $eventSummary['events'][$dao->id][$property] = $set;
              if (in_array($dao->id, $permissions[CRM_Core_Permission::EDIT])) {
                $eventSummary['events'][$dao->id]['configure'] = CRM_Utils_System::url("civicrm/admin/event", "action=update&id=$dao->id&reset=1");
              }
              break;

            case 'end_date':
            case 'start_date':
              $eventSummary['events'][$dao->id][$property] = CRM_Utils_Date::customFormat($dao->$name,
                NULL, ['d']
              );
              break;

            case 'participants':
            case 'notCountedDueToRole':
            case 'notCountedDueToStatus':
            case 'notCountedParticipants':
              $set = NULL;
              $propertyCnt = 0;
              if ($name == 'participants') {
                $propertyCnt = self::getParticipantCount($dao->id);
                if ($propertyCnt) {
                  $set = CRM_Utils_System::url('civicrm/event/search',
                    "reset=1&force=1&event=$dao->id&status=true&role=true"
                  );
                }
              }
              elseif ($name == 'notCountedParticipants') {
                $propertyCnt = self::getParticipantCount($dao->id, TRUE, FALSE, TRUE, FALSE);
                if ($propertyCnt) {
                  // FIXME : selector fail to search w/ OR operator.
                  // $set = CRM_Utils_System::url( 'civicrm/event/search',
                  // "reset=1&force=1&event=$dao->id&status=false&role=false" );
                }
              }
              elseif ($name == 'notCountedDueToStatus') {
                $propertyCnt = self::getParticipantCount($dao->id, TRUE, FALSE, FALSE, FALSE);
                if ($propertyCnt) {
                  $set = CRM_Utils_System::url('civicrm/event/search',
                    "reset=1&force=1&event=$dao->id&status=false"
                  );
                }
              }
              else {
                $propertyCnt = self::getParticipantCount($dao->id, FALSE, FALSE, TRUE, FALSE);
                if ($propertyCnt) {
                  $set = CRM_Utils_System::url('civicrm/event/search',
                    "reset=1&force=1&event=$dao->id&role=false"
                  );
                }
              }

              $eventSummary['events'][$dao->id][$property] = $propertyCnt;
              $eventSummary['events'][$dao->id][$name . '_url'] = $set;
              break;

            default:
              $eventSummary['events'][$dao->id][$property] = $dao->$name;
              break;
          }
        }

        // prepare the area for per-status participant counts
        $statusClasses = ['Positive', 'Pending', 'Waiting', 'Negative'];
        $eventSummary['events'][$dao->id]['statuses'] = array_fill_keys($statusClasses, []);

        // get eventIds.
        if (!in_array($dao->id, $eventIds)) {
          $eventIds[] = $dao->id;
        }
      }
      else {
        $eventSummary['total_events']--;
      }

      $eventSummary['events'][$dao->id]['friend'] = $dao->is_friend_active;
      $eventSummary['events'][$dao->id]['is_monetary'] = $dao->is_monetary;
      $eventSummary['events'][$dao->id]['is_online_registration'] = $dao->is_online_registration;
      $eventSummary['events'][$dao->id]['is_show_location'] = $dao->is_show_location;

      $statusTypes = CRM_Event_PseudoConstant::participantStatus();
      $statusLabels = CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label');
      foreach ($statusValues as $statusId => $statusValue) {
        if (!CRM_Utils_Array::arrayKeyExists($statusId, $statusTypes)) {
          continue;
        }
        $class = $statusValue['class'];
        $statusCount = self::eventTotalSeats($dao->id, "( participant.status_id = {$statusId} )");
        if ($statusCount) {
          $urlString = "reset=1&force=1&event={$dao->id}&status=$statusId";
          $statusInfo = ['url' => CRM_Utils_System::url('civicrm/event/search', $urlString),
            'name' => $statusValue['name'],
            'label' => $statusLabels[$statusId],
            'count' => $statusCount,
          ];
          $eventSummary['events'][$dao->id]['statuses'][$class][] = $statusInfo;
        }
      }
    }


    $countedRoles = CRM_Event_PseudoConstant::participantRole(NULL, 'filter = 1');
    $nonCountedRoles = CRM_Event_PseudoConstant::participantRole(NULL, '( filter = 0 OR filter IS NULL )');
    $countedStatus = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 1');
    $nonCountedStatus = CRM_Event_PseudoConstant::participantStatus(NULL, '( is_counted = 0 OR is_counted IS NULL )');

    $countedStatusANDRoles = array_merge($countedStatus, $countedRoles);
    $nonCountedStatusANDRoles = array_merge($nonCountedStatus, $nonCountedRoles);

    $eventSummary['nonCountedRoles'] = CRM_Utils_Array::implode('/', array_values($nonCountedRoles));
    $eventSummary['nonCountedStatus'] = CRM_Utils_Array::implode('/', array_values($nonCountedStatus));
    $eventSummary['countedStatusANDRoles'] = CRM_Utils_Array::implode('/', array_values($countedStatusANDRoles));
    $eventSummary['nonCountedStatusANDRoles'] = CRM_Utils_Array::implode('/', array_values($nonCountedStatusANDRoles));

    return $eventSummary;
  }

  /**
   * Function to get participant count
   *
   * @param  boolean $considerStatus consider status for participant count.
   * @param  boolean $status         consider counted participant.
   * @param  boolean $considerRole   consider role for participant count.
   * @param  boolean $role           consider counted( is filter role) participant.
   * @param  array   $eventIds       consider participants from given events.
   * @param  boolean $countWithStatus  retrieve participant count w/ each participant status.
   *
   * @access public
   *
   * @return array array with count of participants for each event based on status/role
   */
  static function getParticipantCount($eventId,
    $considerStatus = TRUE,
    $status = TRUE,
    $considerRole = TRUE,
    $role = TRUE
  ) {

    // consider both role and status for counted participants, CRM-4924.


    $operator = " AND ";
    // not counted participant.
    if ($considerStatus && $considerRole && !$status && !$role) {
      $operator = " OR ";
    }
    $clause = [];
    if ($considerStatus) {
      $statusTypes = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 1');
      $statusClause = 'NOT IN';
      if ($status) {
        $statusClause = 'IN';
      }
      $status = CRM_Utils_Array::implode(',', array_keys($statusTypes));
      if (empty($status)) {
        $status = 0;
      }
      $clause[] = "participant.status_id {$statusClause} ( {$status} ) ";
    }

    if ($considerRole) {
      $roleTypes = CRM_Event_PseudoConstant::participantRole(NULL, 'filter = 1');
      $roleClause = 'NOT IN';
      if ($role) {
        $roleClause = 'IN';
      }
      $roles = CRM_Utils_Array::implode(',', array_keys($roleTypes));
      if (empty($roles)) {
        $roles = 0;
      }
      $clause[] = "participant.role_id {$roleClause} ( $roles )";
    }

    $sqlClause = '';
    if (!empty($clause)) {
      $sqlClause = ' ( ' . CRM_Utils_Array::implode($operator, $clause) . ' )';
    }

    return self::eventTotalSeats($eventId, $sqlClause);
  }

  /**
   * function to get the information to map a event
   *
   * @param  array  $ids    the list of ids for which we want map info
   *
   * @return null|string     title of the event
   * @static
   * @access public
   */

  static function &getMapInfo(&$id) {

    $sql = "
SELECT 
   civicrm_event.id AS event_id, 
   civicrm_event.title AS display_name, 
   civicrm_address.street_address AS street_address, 
   civicrm_address.city AS city, 
   civicrm_address.postal_code AS postal_code, 
   civicrm_address.postal_code_suffix AS postal_code_suffix, 
   civicrm_address.geo_code_1 AS latitude, 
   civicrm_address.geo_code_2 AS longitude, 
   civicrm_state_province.abbreviation AS state, 
   civicrm_country.name AS country, 
   civicrm_location_type.name AS location_type
FROM 
   civicrm_event
   LEFT JOIN civicrm_loc_block ON ( civicrm_event.loc_block_id = civicrm_loc_block.id )
   LEFT JOIN civicrm_address ON ( civicrm_loc_block.address_id = civicrm_address.id )
   LEFT JOIN civicrm_state_province ON ( civicrm_address.state_province_id = civicrm_state_province.id )
   LEFT JOIN civicrm_country ON civicrm_address.country_id = civicrm_country.id
   LEFT JOIN civicrm_location_type ON ( civicrm_location_type.id = civicrm_address.location_type_id )
WHERE civicrm_event.id = " . CRM_Utils_Type::escape($id, 'Integer');

    $dao = new CRM_Core_DAO();
    $dao->query($sql);

    $locations = [];

    $config = CRM_Core_Config::singleton();

    while ($dao->fetch()) {

      $location = [];
      $location['locationName'] = $dao->display_name;
      $location['displayName'] = $dao->display_name;
      $location['marker_class'] = 'Event';
      $location['lat'] = $dao->latitude;
      $location['lng'] = $dao->longitude;
      $location['contactID'] = $dao->contact_id;
      $location['city'] = $dao->city;
      $location['state'] = $dao->state;
      $location['postal_code'] = $dao->postal_code;
      $location['lat'] = $dao->latitude;
      $location['lng'] = $dao->longitude;
      $location['street_address'] = $dao->street_address;
      $location['supplemental_address_1'] = $dao->supplemental_address_1;
      $location['supplemental_address_2'] = $dao->supplemental_address_2;
      $location['state_province_name'] = ts($dao->state_province_name);
      $location['country'] = $dao->country;

      $address = str_replace("\n", '', CRM_Utils_Address::format($location));
      $location['address'] = $address;
      $location['displayAddress'] = str_replace('<br />', ', ', $address);
      $location['url'] = CRM_Utils_System::url('civicrm/event/register', 'reset=1&id=' . $dao->event_id);
      $location['location_type'] = $dao->location_type;
      $locations[] = $location;
    }
    return $locations;
  }

  /**
   * function to get the complete information for one or more events
   *
   * @param  date    $start    get events with start date >= this date
   * @param  integer $type     get events on the a specific event type (by event_type_id)
   * @param  integer $eventId  return a single event - by event id
   * @param  date    $end      also get events with end date >= this date
   *
   * @return  array  $all      array of all the events that are searched
   * @static
   * @access public
   */
  static function &getCompleteInfo($start = NULL, $type = NULL, $eventId = NULL, $end = NULL) {
    // if start and end date are NOT passed, return all events with start_date OR end_date >= today CRM-5133
    if ($start) {
      // get events with start_date >= requested start
      $startDate = CRM_Utils_Type::escape($start, 'Date');
    }
    else {
      // get events with start date >= today
      $startDate = date("Ymd");
    }
    if ($end) {
      // also get events with end_date >= requested end
      $endDate = CRM_Utils_Type::escape($end, 'Date');
    }
    else {
      // OR also get events with end date >= today
      $endDate = date("Ymd");
    }
    $dateCondition = "AND (civicrm_event.start_date >= {$startDate} OR civicrm_event.end_date >= {$endDate})";


    if ($type) {
      $typeCondition = " AND civicrm_event.event_type_id = " . CRM_Utils_Type::escape($type, 'Integer');
    }

    // Get the Id of Option Group for Event Types

    $optionGroupDAO = new CRM_Core_DAO_OptionGroup();
    $optionGroupDAO->name = 'event_type';
    $optionGroupId = NULL;
    if ($optionGroupDAO->find(TRUE)) {
      $optionGroupId = $optionGroupDAO->id;
    }

    $query = "
SELECT
  civicrm_event.id as event_id, 
  civicrm_email.email as email, 
  civicrm_event.title as title, 
  civicrm_event.summary as summary, 
  civicrm_event.start_date as start, 
  civicrm_event.end_date as end, 
  civicrm_event.description as description, 
  civicrm_event.is_show_location as is_show_location, 
  civicrm_event.is_online_registration as is_online_registration,
  civicrm_event.registration_link_text as registration_link_text,
  civicrm_event.registration_start_date as registration_start_date,
  civicrm_event.registration_end_date as registration_end_date,
  civicrm_option_value.label as event_type, 
  civicrm_address.name as address_name, 
  civicrm_address.street_address as street_address, 
  civicrm_address.supplemental_address_1 as supplemental_address_1, 
  civicrm_address.supplemental_address_2 as supplemental_address_2, 
  civicrm_address.city as city, 
  civicrm_address.postal_code as postal_code, 
  civicrm_address.postal_code_suffix as postal_code_suffix, 
  civicrm_state_province.abbreviation as state, 
  civicrm_country.name AS country
FROM civicrm_event
LEFT JOIN civicrm_loc_block ON civicrm_event.loc_block_id = civicrm_loc_block.id
LEFT JOIN civicrm_address ON civicrm_loc_block.address_id = civicrm_address.id
LEFT JOIN civicrm_state_province ON civicrm_address.state_province_id = civicrm_state_province.id
LEFT JOIN civicrm_country ON civicrm_address.country_id = civicrm_country.id
LEFT JOIN civicrm_email ON civicrm_loc_block.email_id = civicrm_email.id
LEFT JOIN civicrm_option_value ON (
                                    civicrm_event.event_type_id = civicrm_option_value.value AND
                                    civicrm_option_value.option_group_id = %1 )
WHERE civicrm_event.is_active = 1 
      AND civicrm_event.is_public = 1
      AND (is_template = 0 OR is_template IS NULL)
      {$dateCondition}";

    if (isset($typeCondition)) {
      $query .= $typeCondition;
    }

    if (isset($eventId)) {
      $query .= " AND civicrm_event.id =$eventId ";
    }
    $query .= " ORDER BY   civicrm_event.start_date ASC";


    $params = [1 => [$optionGroupId, 'Integer']];
    $dao = &CRM_Core_DAO::executeQuery($query, $params);
    $all = [];
    $config = CRM_Core_Config::singleton();

    $baseURL = parse_url($config->userFrameworkBaseURL);
    $url = "@" . $baseURL['host'];
    if (CRM_Utils_Array::value('path', $baseURL)) {
      $url .= substr($baseURL['path'], 0, -1);
    }

    // check 'view event info' permission
    $permissions = CRM_Core_Permission::event(CRM_Core_Permission::VIEW);


    while ($dao->fetch()) {
      if (in_array($dao->event_id, $permissions)) {
        $info = [];
        $info['uid'] = "CiviCRM_EventID_{$dao->event_id}_" . md5($config->userFrameworkBaseURL) . $url;

        $info['title'] = $dao->title;
        $info['event_id'] = $dao->event_id;
        $info['summary'] = $dao->summary;
        $info['description'] = $dao->description;
        $info['start_date'] = $dao->start;
        $info['end_date'] = $dao->end;
        $info['contact_email'] = $dao->email;
        $info['event_type'] = $dao->event_type;
        $info['is_show_location'] = $dao->is_show_location;
        $info['is_online_registration'] = $dao->is_online_registration;
        $info['registration_link_text'] = $dao->registration_link_text;
        $info['registration_start_date'] = $dao->registration_start_date;
        $info['registration_end_date'] = $dao->registration_end_date;

        $address = '';

        $addrFields = [
          'address_name' => $dao->address_name,
          'street_address' => $dao->street_address,
          'supplemental_address_1' => $dao->supplemental_address_1,
          'supplemental_address_2' => $dao->supplemental_address_2,
          'city' => $dao->city,
          'state_province' => $dao->state,
          'postal_code' => $dao->postal_code,
          'postal_code_suffix' => $dao->postal_code_suffix,
          'country' => $dao->country,
          'county' => NULL,
        ];


        CRM_Utils_String::append($address, ', ',
          CRM_Utils_Address::format($addrFields)
        );
        $info['location'] = $address;
        $info['url'] = CRM_Utils_System::url('civicrm/event/info', 'reset=1&id=' . $dao->event_id, TRUE, NULL, FALSE);

        $all[] = $info;
      }
    }

    return $all;
  }

  /**
   * This function is to make a copy of a Event, including
   * all the fields in the event Wizard
   *
   * @param int $id the event id to copy
   *
   * @return void
   * @access public
   */
  static function copy($id) {
    $defaults = $eventValues = [];

    //get the require event values.
    $eventParams = ['id' => $id];
    $returnProperties = ['loc_block_id', 'is_show_location', 'default_fee_id', 'default_discount_fee_id'];

    CRM_Core_DAO::commonRetrieve('CRM_Event_DAO_Event', $eventParams, $eventValues, $returnProperties);

    // since the location is sharable, lets use the same loc_block_id.
    $locBlockId = CRM_Utils_Array::value('loc_block_id', $eventValues);

    $fieldsFix = ['prefix' => ['title' => ts('Copy of') . ' ']];
    if (!CRM_Utils_Array::value('is_show_location', $eventValues)) {
      $fieldsFix['prefix']['is_show_location'] = 0;
    }

    $copyEvent = &CRM_Core_DAO::copyGeneric('CRM_Event_DAO_Event',
      ['id' => $id],
      ['loc_block_id' =>
        ($locBlockId) ? $locBlockId : NULL,
      ],
      $fieldsFix
    );

    $copyPriceSet = &CRM_Core_DAO::copyGeneric('CRM_Price_DAO_SetEntity',
      ['entity_id' => $id,
        'entity_table' => 'civicrm_event',
      ],
      ['entity_id' => $copyEvent->id]
    );

    $copyUF = &CRM_Core_DAO::copyGeneric('CRM_Core_DAO_UFJoin',
      ['entity_id' => $id,
        'entity_table' => 'civicrm_event',
      ],
      ['entity_id' => $copyEvent->id]
    );

    $copyTellFriend = &CRM_Core_DAO::copyGeneric('CRM_Friend_DAO_Friend',
      ['entity_id' => $id,
        'entity_table' => 'civicrm_event',
      ],
      ['entity_id' => $copyEvent->id]
    );


    //copy option Group and values
    $copyEvent->default_fee_id = CRM_Core_BAO_OptionGroup::copyValue('event',
      $id,
      $copyEvent->id,
      CRM_Utils_Array::value('default_fee_id', $eventValues)
    );

    //copy discounted fee levels

    $discount = CRM_Core_BAO_Discount::getOptionGroup($id, 'civicrm_event');

    if (!empty($discount)) {
      foreach ($discount as $discountOptionGroup) {
        $name = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
          $discountOptionGroup
        );
        $length = substr_compare($name, "civicrm_event.amount." . $id, 0);
        $discountSuffix = substr($name, $length * (-1));

        $copyEvent->default_discount_fee_id = CRM_Core_BAO_OptionGroup::copyValue('event',
          $id,
          $copyEvent->id,
          CRM_Utils_Array::value('default_discount_fee_id', $eventValues),
          $discountSuffix
        );
      }
    }

    // Refs #23510, If Event is_pay_later is not checked, should remove pay_later_receipt
    if (empty($copyEvent->is_pay_later)) {
      $copyEvent->pay_later_receipt = 'null';
    }

    //copy custom data

    $extends = ['event'];
    $groupTree = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, NULL, $extends);
    if ($groupTree) {
      foreach ($groupTree as $groupID => $group) {
        $table[$groupTree[$groupID]['table_name']] = ['entity_id'];
        foreach ($group['fields'] as $fieldID => $field) {
          if ($field['data_type'] == 'File') {
            continue;
          }
          $table[$groupTree[$groupID]['table_name']][] = $groupTree[$groupID]['fields'][$fieldID]['column_name'];
        }
      }

      foreach ($table as $tableName => $tableColumns) {
        $insert = 'INSERT INTO ' . $tableName . ' (' . CRM_Utils_Array::implode(', ', $tableColumns) . ') ';
        $tableColumns[0] = $copyEvent->id;
        $select = 'SELECT ' . CRM_Utils_Array::implode(', ', $tableColumns);
        $from = ' FROM ' . $tableName;
        $where = " WHERE {$tableName}.entity_id = {$id}";
        $query = $insert . $select . $from . $where;
        $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
      }
    }
    $copyEvent->is_active = 0;
    $copyEvent->save();

    // Need original ID to duplicate Instrument. refs #14946
    $copyEvent->originId = $id;


    CRM_Utils_Hook::copy('Event', $copyEvent);

    return $copyEvent;
  }

  /**
   * This is sometimes called in a loop (during event search)
   * hence we cache the values to prevent repeated calls to the db
   */
  static function isMonetary($id) {
    static $isMonetary = [];
    if (!CRM_Utils_Array::arrayKeyExists($id, $isMonetary)) {
      $isMonetary[$id] = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event',
        $id,
        'is_monetary'
      );
    }
    return $isMonetary[$id];
  }

  /**
   * This is sometimes called in a loop (during event search)
   * hence we cache the values to prevent repeated calls to the db
   */
  static function usesPriceSet($id) {

    static $usesPriceSet = [];
    if (!CRM_Utils_Array::arrayKeyExists($id, $usesPriceSet)) {
      $usesPriceSet[$id] = CRM_Price_BAO_Set::getFor('civicrm_event', $id);
    }
    return $usesPriceSet[$id];
  }

  /**
   * Process that send e-mails
   *
   * @return void
   * @access public
   */
  static function sendMail($contactID, &$values, $participantId, $isTest = FALSE, $returnMessageText = FALSE) {
    $template = CRM_Core_Smarty::singleton();
    $config = CRM_Core_Config::singleton();
    $gIds = [
      'custom_pre_id' => $values['custom_pre_id'],
      'custom_post_id' => $values['custom_post_id'],
    ];


    //get the params submitted by participant.
    $participantParams = CRM_Utils_Array::value($participantId, $values['params'], []);

    if (isset($values['custom_pre_id'])) {
      $participantParams['custom_pre_id'] = [['participant_id', '=', $participantId, 0, 0]];
    }
    if (isset($values['custom_post_id'])) {
      $participantParams['custom_post_id'] = [['participant_id', '=', $participantId, 0, 0]];
    }

    //check whether it is a test drive
    if ($isTest && !empty($participantParams['custom_pre_id'])) {
      $participantParams['custom_pre_id'][] = ['participant_test', '=', 1, 0, 0];
    }

    if ($isTest && !empty($participantParams['custom_post_id'])) {
      $participantParams['custom_post_id'][] = ['participant_test', '=', 1, 0, 0];
    }

    if (!$returnMessageText) {
      //send notification email if field values are set (CRM-1941)
      foreach ($gIds as $key => $gId) {
        if ($gId) {
          $email = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $gId, 'notify');
          if ($email) {
            //get values of corresponding profile fields for notification
            $val = CRM_Core_BAO_UFGroup::checkFieldsEmptyValues($gId, $contactID, $participantParams[$key]);
            $fields = CRM_Core_BAO_UFGroup::getFields($gId, FALSE, CRM_Core_Action::VIEW);
            CRM_Core_BAO_UFGroup::verifySubmittedValue($fields, $val, $values['params'][$participantId]);
            CRM_Core_BAO_UFGroup::commonSendMail($contactID, $val);
          }
        }
      }
    }

    if ($values['event']['is_email_confirm'] || $returnMessageText) {

      //use primary email address, since we are not creating billing address for
      //1. participant is pay later.
      //2. participant might be additional participant.
      //3. participant might be on waiting list.
      //4. registration might require approval.
      if (CRM_Utils_Array::value('is_pay_later', $values['params']) ||
        CRM_Utils_Array::value('additionalParticipant', $values['params']) ||
        CRM_Utils_Array::value('isOnWaitlist', $values['params']) ||
        CRM_Utils_Array::value('isRequireApproval', $values['params']) ||
        !CRM_Utils_Array::value('is_monetary', $values['event'])
      ) {
        list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID);
      }
      else {
        // get the billing location type
        $locationTypes = &CRM_Core_PseudoConstant::locationType();
        $bltID = array_search('Billing', $locationTypes);
        list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID, FALSE, $bltID);
      }

      //send email only when email is present
      $template->clear_assign(['customPre', 'customPost', 'additionalCustomPre', 'additionalCustomPost']);
      if (isset($email) || $returnMessageText) {
        $preProfileID = $values['custom_pre_id'];
        $postProfileID = $values['custom_post_id'];

        if (CRM_Utils_Array::value('additionalParticipant', $values['params'])) {
          $preProfileID = $values['additional_custom_pre_id'];
          $postProfileID = $values['additional_custom_post_id'];
        }

        self::buildCustomDisplay($preProfileID,
          'customPre',
          $contactID,
          $template,
          $participantId,
          $isTest,
          NULL,
          $participantParams
        );

        self::buildCustomDisplay($postProfileID,
          'customPost',
          $contactID,
          $template,
          $participantId,
          $isTest,
          NULL,
          $participantParams
        );

        // #18853, tokenize thank you top text
        $confirmText = CRM_Utils_Array::value('confirm_email_text', $values['event']);
        if($confirmText) {
          $confirmText = self::tokenize($contactID, $confirmText); 
          $eventVar = $template->get_template_vars('event');
          $eventVar['confirm_email_text'] = $confirmText;
          $template->assign('event', $eventVar);
        }

        $sendTemplateParams = [
          'groupName' => 'msg_tpl_workflow_event',
          'valueName' => 'event_online_receipt',
          'contactId' => $contactID,
          'participantId' => $participantId,
          'eventId' => $values['event']['id'],
          'isTest' => $isTest,
          'tplParams' => [
            'email' => $email,
            'confirm_email_text' => $confirmText,
            'isShowLocation' => CRM_Utils_Array::value('is_show_location', $values['event']),
            'participantID' => $participantId,
          ],
          'PDFFilename' => 'eventReceipt.pdf',
        ];

        // Don't send qrcode to participant on waiting list or waiting for approval.
        $statusId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $participantId, 'status_id');
        $waitingStatusIds = array_keys(CRM_Event_PseudoConstant::participantStatus( null, "class = 'Waiting'"  ));
        if ($values['event']['is_qrcode'] && !in_array($statusId, $waitingStatusIds)) {
          $checkinCodeFile = CRM_Event_BAO_Participant::checkinCode($contactID, $participantId);
          $qrcodeName = 'qrcode-'.$participantId;
          $embedImages = [
            $qrcodeName => [
              'fullPath' => $checkinCodeFile,
              'mime_type' => 'image/png',
              'cleanName' => $qrcodeName.'.png',
            ],
          ];

          $checkinUrl = CRM_Event_BAO_Participant::checkinUrl($contactID, $participantId);
          $onlineQrcodeUrl = $config->userFrameworkResourceURL.'extern/qrcode.html?qrcode='.rawurlencode($checkinUrl); 
          $sendTemplateParams['tplParams']['checkinUrl'] = $onlineQrcodeUrl;
          $sendTemplateParams['tplParams']['checkinUrlTag'] = '<a href="'.$onlineQrcodeUrl.'">'.ts('Check In Code').' '.ts('Link').'</a>';
          if (!empty($embedImages)) {
            $sendTemplateParams['tplParams']['checkinCode'] = "<img src=\"cid:$qrcodeName\"><br>".$sendTemplateParams['tplParams']['checkinUrlTag'];
            $sendTemplateParams['images'] = $embedImages;
          }
        }

        // address required during receipt processing (pdf and email receipt)
        if ($displayAddress = CRM_Utils_Array::value('address', $values)) {
          $sendTemplateParams['tplParams']['address'] = $displayAddress;
          $sendTemplateParams['tplParams']['contributeMode'] = NULL;
        }
        // set lineItem details
        if ($lineItem = CRM_Utils_Array::value('lineItem', $values)) {
          $sendTemplateParams['tplParams']['lineItem'] = $lineItem;
        }

        $participant = new CRM_Event_DAO_Participant();
        $participant->id = $participantId;
        $participant->find(TRUE);
        $workflow = CRM_Core_BAO_MessageTemplates::getMessageTemplateByWorkflow($sendTemplateParams['groupName'], $sendTemplateParams['valueName']);
        $activityId = CRM_Activity_BAO_Activity::addTransactionalActivity($participant, 'Event Notification Email', $workflow['msg_title']);
        $sendTemplateParams['activityId'] = $activityId;
        if ($returnMessageText) {
          list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplates::sendTemplate($sendTemplateParams, CRM_Core_DAO::$_nullObject, [
            0 => ['CRM_Activity_BAO_Activity::updateTransactionalStatus' =>  [$activityId, TRUE]],
            1 => ['CRM_Activity_BAO_Activity::updateTransactionalStatus' =>  [$activityId, FALSE]],
          ]);
          return ['subject' => $subject,
            'body' => $message,
            'to' => $displayName,
            'html' => $html,
          ];
        }
        else {
          // do_not_notify check
          $contactDetail = CRM_Contact_BAO_Contact::getContactDetails($contactID);
          if (!empty($contactDetail[5])) {
            CRM_Core_Error::debug_log_message("Skipped email notify {$sendTemplateParams['valueName']} for contact $contactID due to do_not_notify marked");
            return;
          }
          $sendTemplateParams['from'] = "{$values['event']['confirm_from_name']} <{$values['event']['confirm_from_email']}>";
          $sendTemplateParams['toName'] = $displayName;
          $sendTemplateParams['toEmail'] = $email;
          $sendTemplateParams['autoSubmitted'] = TRUE;
          $sendTemplateParams['cc'] = CRM_Utils_Array::value('cc_confirm', $values['event']);
          $sendTemplateParams['bcc'] = CRM_Utils_Array::value('bcc_confirm', $values['event']);
          CRM_Core_BAO_MessageTemplates::sendTemplate($sendTemplateParams, CRM_Core_DAO::$_nullObject, [
            0 => ['CRM_Activity_BAO_Activity::updateTransactionalStatus' =>  [$activityId, TRUE]],
            1 => ['CRM_Activity_BAO_Activity::updateTransactionalStatus' =>  [$activityId, FALSE]],
          ]);
        }
      }
    }
  }

  /**
   * Function to add the custom fields OR array of participant's
   * profile info
   *
   * @return None
   * @access public
   */
  static function buildCustomDisplay($gid,
    $name,
    $cid,
    &$template,
    $participantId,
    $isTest,
    $isCustomProfile = FALSE,
    $participantParams = []
  ) {
    if ($gid) {

      if (CRM_Core_BAO_UFGroup::filterUFGroups($gid, $cid)) {
        $values = [];
        $fields = CRM_Core_BAO_UFGroup::getFields($gid, FALSE, CRM_Core_Action::VIEW);

        //this condition is added, since same contact can have multiple event registrations..
        $params = [['participant_id', '=', $participantId, 0, 0]];

        //add participant id
        $fields['participant_id'] = ['name' => 'participant_id',
          'title' => 'Participant Id',
        ];
        //check whether its a text drive
        if ($isTest) {
          $params[] = ['participant_test', '=', 1, 0, 0];
        }

        $groupTitles = [];
        $groupTitle = NULL;
        foreach ($fields as $k => $v) {
          if (!$groupTitle) {
            $groupTitle = $v["groupTitle"];
          }
          // unset all view only profile field
          if ($v['is_view']){
            unset($fields[$k]);
          }
        }

        if ($groupTitle) {
          $template->assign($name . "_grouptitle", $groupTitle);
          $groupTitles[$name . "_grouptitle"] = $groupTitle;
        }

        //display profile groups those are subscribed by participant.
        if (($groups = CRM_Utils_Array::value('group', $participantParams)) &&
          is_array($groups)
        ) {
          $grpIds = [];
          foreach ($groups as $grpId => $isSelected) {
            if ($isSelected) {
              $grpIds[] = $grpId;
            }
          }
          if (!empty($grpIds)) {
            //get the group titles.
            $grpTitles = [];
            $query = 'SELECT title FROM civicrm_group where id IN ( ' . CRM_Utils_Array::implode(',', $grpIds) . ' )';
            $grp = CRM_Core_DAO::executeQuery($query);
            while ($grp->fetch()) {
              $grpTitles[] = $grp->title;
            }
            $values[$fields['group']['title']] = CRM_Utils_Array::implode(', ', $grpTitles);
            unset($fields['group']);
          }
        }

        CRM_Core_BAO_UFGroup::getValues($cid, $fields, $values, FALSE, $params, CRM_Core_BAO_UFGroup::MASK_ALL);

        foreach ($fields as $k => $v) {
          // suppress all file fields from display
          if ((CRM_Utils_Array::value('data_type', $v, '') == 'File' || CRM_Utils_Array::value('name', $v, '') == 'image_URL') && !empty($values[$v['title']] )){
            $values[$v['title']] = ts("Uploaded files received");
          }
        }

        if (isset($values[$fields['participant_status_id']['title']]) &&
          is_numeric($values[$fields['participant_status_id']['title']])
        ) {
          $status = [];
          $status = CRM_Event_PseudoConstant::participantStatus();
          $values[$fields['participant_status_id']['title']] = $status[$values[$fields['participant_status_id']['title']]];
        }

        if (isset($values[$fields['participant_role_id']['title']]) &&
          is_numeric($values[$fields['participant_role_id']['title']])
        ) {
          $roles = [];
          $roles = CRM_Event_PseudoConstant::participantRole();
          $values[$fields['participant_role_id']['title']] = $roles[$values[$fields['participant_role_id']['title']]];
        }

        if (isset($fields['participant_register_date']['title']) &&
          isset($values[$fields['participant_register_date']['title']])
        ) {
          $values[$fields['participant_register_date']['title']] = CRM_Utils_Date::customFormat($values[$fields['participant_register_date']['title']]);
        }

        //handle fee_level for price set
        if (isset($values[$fields['participant_fee_level']['title']])) {
          $feeLevel = explode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR,
            $values[$fields['participant_fee_level']['title']]
          );
          foreach ($feeLevel as $key => $val) {
            if (!$val) {
              unset($feeLevel[$key]);
            }
          }
          $values[$fields['participant_fee_level']['title']] = CRM_Utils_Array::implode(",", $feeLevel);
        }

        unset($values[$fields['participant_id']['title']]);

        //return if we only require array of participant's info.
        if ($isCustomProfile) {
          if (count($values)) {
            return [$values, $groupTitles];
          }
          else {
            return NULL;
          }
        }

        if (count($values)) {
          $template->assign($name, $values);
        }
      }
    }
  }

  /**
   * Function to build the array for display the profile fields
   *
   * @param array $params key value.
   * @param int $gid profile Id
   * @param array $groupTitle Profile Group Title.
   * @param array $values formatted array of key value
   *
   * @return None
   * @access public
   */
  static function displayProfile(&$params, $gid, &$groupTitle, &$values) {
    if ($gid) {



      $session = CRM_Core_Session::singleton();
      $contactID = $session->get('userID');
      if ($contactID) {
        if (CRM_Core_BAO_UFGroup::filterUFGroups($gid, $contactID)) {
          $fields = CRM_Core_BAO_UFGroup::getFields($gid, FALSE, CRM_Core_Action::VIEW);
        }
      }
      else {
        $fields = CRM_Core_BAO_UFGroup::getFields($gid, FALSE, CRM_Core_Action::ADD);
      }

      if (is_array($fields)) {
        // unset any email-* fields since we already collect it, CRM-2888
        foreach (array_keys($fields) as $fieldName) {
          if (substr($fieldName, 0, 6) == 'email-') {
            unset($fields[$fieldName]);
          }
        }
      }

      foreach ($fields as $v) {
        if (CRM_Utils_Array::value('groupTitle', $v)) {
          $groupTitle['groupTitle'] = $v["groupTitle"];
          break;
        }
      }

      $config = CRM_Core_Config::singleton();

      $locationTypes = $imProviders = [];
      $locationTypes = CRM_Core_PseudoConstant::locationType();
      $imProviders = CRM_Core_PseudoConstant::IMProvider();
      //start of code to set the default values
      foreach ($fields as $name => $field) {
        $index = $field['title'];
        $customFieldName = NULL;
        $customVal = $displayValue = '';
        $skip = FALSE;
        if ($name === 'organization_name') {
          $values[$index] = $params[$name];
        }

        if ('state_province' == substr($name, 0, 14)) {
          if ($params[$name]) {
            $values[$index] = CRM_Core_PseudoConstant::stateProvince($params[$name]);
          }
          else {
            $values[$index] = '';
          }
        }
        elseif ('country' == substr($name, 0, 7)) {
          if ($params[$name]) {
            $values[$index] = CRM_Core_PseudoConstant::country($params[$name]);
          }
          else {
            $values[$index] = '';
          }
        }
        elseif ('county' == substr($name, 0, 6)) {
          if ($params[$name]) {
            $values[$index] = CRM_Core_PseudoConstant::county($params[$name]);
          }
          else {
            $values[$index] = '';
          }
        }
        elseif ('gender' == substr($name, 0, 6)) {
          $gender = CRM_Core_PseudoConstant::gender();
          $values[$index] = $gender[$params[$name]];
        }
        elseif ('individual_prefix' == substr($name, 0, 17)) {
          $prefix = CRM_Core_PseudoConstant::individualPrefix();
          $values[$index] = $prefix[$params[$name]];
        }
        elseif ('individual_suffix' == substr($name, 0, 17)) {
          $suffix = CRM_Core_PseudoConstant::individualSuffix();
          $values[$index] = $suffix[$params[$name]];
        }
        elseif (in_array($name, ['addressee', 'email_greeting', 'postal_greeting'])) {
          $filterCondition = ['greeting_type' => $name];
          $greeting = &CRM_Core_PseudoConstant::greeting($filterCondition);
          $values[$index] = $greeting[$params[$name]];
        }
        elseif ($name === 'preferred_communication_method') {
          $communicationFields = CRM_Core_PseudoConstant::pcm();
          $pref = [];
          $compref = [];
          $pref = $params[$name];
          if (is_array($pref)) {
            foreach ($pref as $k => $v) {
              if ($v) {
                $compref[] = $communicationFields[$k];
              }
            }
          }
          $values[$index] = CRM_Utils_Array::implode(",", $compref);
        }
        elseif ($name == 'group') {

          $groups = CRM_Contact_BAO_GroupContact::getGroupList();
          $title = [];
          foreach ($params[$name] as $gId => $dontCare) {
            if ($dontCare) {
              $title[] = $groups[$gId];
            }
          }
          $values[$index] = CRM_Utils_Array::implode(', ', $title);
        }
        elseif ($name == 'tag') {

          $entityTags = $params[$name];
          $allTags = &CRM_Core_PseudoConstant::tag();
          $title = [];
          if (is_array($entityTags)) {
            foreach ($entityTags as $tagId => $dontCare) {
              $title[] = $allTags[$tagId];
            }
          }
          $values[$index] = CRM_Utils_Array::implode(', ', $title);
        }
        elseif ('participant_role_id' == $name) {
          $roles = CRM_Event_PseudoConstant::participantRole();
          $values[$index] = $roles[$params[$name]];
        }
        elseif ('participant_status_id' == $name) {
          $status = CRM_Event_PseudoConstant::participantStatus();
          $values[$index] = $status[$params[$name]];
        }
        elseif ('image_URL' == $name) {
          if (!empty($params[$name]['name']) || !empty($params[$name])) {
            $values[$index] = ts('File uploaded');
          }
        }
        elseif (strpos($name, '-') !== FALSE) {
          list($fieldName, $id) = CRM_Utils_System::explode('-', $name, 2);
          $detailName = str_replace(' ', '_', $name);
          if (in_array($fieldName, ['state_province', 'country', 'county'])) {
            $values[$index] = $params[$detailName];
            $idx = $detailName . '_id';
            $values[$index] = $params[$idx];
          }
          elseif ($fieldName == 'im') {
            $providerName = NULL;
            if ($providerId = $detailName . '-provider_id') {
              $providerName = CRM_Utils_Array::value($params[$providerId], $imProviders);
            }
            if ($providerName) {
              $values[$index] = $params[$detailName] . " (" . $providerName . ")";
            }
            else {
              $values[$index] = $params[$detailName];
            }
          }
          else {
            $values[$index] = $params[$detailName];
          }
        }
        else {
          if (substr($name, 0, 7) === 'do_not_' or substr($name, 0, 3) === 'is_') {
            if ($params[$name]) {
              $values[$index] = '[ x ]';
            }
          }
          else {

            if ($cfID = CRM_Core_BAO_CustomField::getKeyID($name)) {
              $query = "
SELECT html_type, data_type, date_format, time_format
FROM   civicrm_custom_field
WHERE  id = $cfID
";
              $dao = CRM_Core_DAO::executeQuery($query,
                CRM_Core_DAO::$_nullArray
              );
              $dao->fetch();
              $htmlType = $dao->html_type;
              $dataType = $dao->data_type;

              if ($htmlType == 'File') {
                //$fileURL = CRM_Core_BAO_CustomField::getFileURL( $contactID, $cfID );
                //$params[$index] = $values[$index] = $fileURL['file_url'];
                if (!empty($params[$name]['name'])) {
                  $values[$index] = ts('File uploaded');
                }
              }
              else {
                if ($dao->data_type == 'Int' ||
                  $dao->data_type == 'Boolean'
                ) {
                  $customVal = (int )($params[$name]);
                }
                elseif ($dao->data_type == 'Float') {
                  $customVal = (float )($params[$name]);
                }
                elseif ($dao->data_type == 'Date') {
                  $posixFormats = CRM_Core_SelectValues::datePluginToPOSIXFormats();
                  if (!empty($params[$name . '_time'])) {
                    $customFormat = $config->dateformatDatetime;
                    if ($dao->date_format && !empty($posixFormats[$dao->date_format])) {
                      $customFormat = $posixFormats[$dao->date_format];
                      if (!empty($dao->time_format)) {
                        if ($dao->time_format == 1) {
                          $customFormat .= ' %I:%M %P';
                        }
                        else {
                          $customFormat .= ' %H:%M';
                        }
                      }
                    }
                    $customVal = $displayValue = CRM_Utils_Date::customFormat(CRM_Utils_Date::processDate($params[$name], $params[$name . '_time']), $customFormat);
                  }
                  else {
                    $customFormat = $config->dateformatFull;
                    if ($dao->date_format && !empty($posixFormats[$dao->date_format])) {
                      $customFormat = $posixFormats[$dao->date_format];
                    }
                    $customVal = $displayValue = CRM_Utils_Date::customFormat(CRM_Utils_Date::processDate($params[$name]), $customFormat);
                  }
                  $skip = TRUE;
                }
                else {
                  $customVal = $params[$name];
                }
                //take the custom field options
                $returnProperties = [$name => 1];

                $query = new CRM_Contact_BAO_Query($params, $returnProperties, $fields);
                $options = &$query->_options;
                if (!$skip) {
                  $displayValue = CRM_Core_BAO_CustomField::getDisplayValue($customVal, $cfID, $options);
                }

                //Hack since we dont have function to check empty.
                //FIXME in 2.3 using crmIsEmptyArray()
                $customValue = TRUE;
                if (is_array($customVal) && is_array($displayValue)) {
                  $customValue = array_diff($customVal, $displayValue);
                }
                //use difference of arrays
                if (empty($customValue) || !$customValue) {
                  $values[$index] = '';
                }
                else {
                  $values[$index] = $displayValue;
                }

                if (CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
                    $cfID, 'is_search_range'
                  )) {
                  $customFieldName = "{$name}_from";
                }
              }
            }
            elseif ($name == 'home_URL' &&
              !empty($params[$name])
            ) {
              $url = CRM_Utils_System::fixURL($params[$name]);
              $values[$index] = "<a href=\"$url\">{$params[$name]}</a>";
            }
            elseif (in_array($name, ['birth_date', 'deceased_date', 'participant_register_date'])) {

              $values[$index] = CRM_Utils_Date::customFormat($params[$name]);
            }
            else {
              $values[$index] = $params[$name];
            }
          }
        }
      }
    }
  }

  /**
   * Function to build the array for Additional participant's information  array of priamry and additional Ids
   *
   *@param int $participantId id of Primary participant
   *@param array $values key/value event info
   *@param int $contactId contact id of Primary participant
   *@param boolean $isTest whether test or live transaction
   *@param boolean $isIdsArray to return an array of Ids
   *
   *@return array $customProfile array of Additional participant's info OR array of Ids.
   *@access public
   */
  static function buildCustomProfile($participantId,
    $values,
    $contactId = NULL,
    $isTest = FALSE,
    $isIdsArray = FALSE,
    $skipCancel = TRUE
  ) {
    $customProfile = $additionalIDs = [];
    if (!$participantId) {
      CRM_Core_Error::fatal(ts('Cannot find participant ID'));
    }

    //set Ids of Primary Participant also.
    if ($isIdsArray && $contactId) {
      $additionalIDs[$participantId] = $contactId;
    }

    //hack to skip cancelled participants, CRM-4320
    $where = "participant.registered_by_id={$participantId}";
    if ($skipCancel) {
      $cancelStatusId = 0;

      $negativeStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Negative'");
      $cancelStatusId = array_search('Cancelled', $negativeStatuses);
      $where .= " AND participant.status_id != {$cancelStatusId}";
    }
    $query = "
  SELECT  participant.id, participant.contact_id
    FROM  civicrm_participant participant
   WHERE  {$where}";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $additionalIDs[$dao->id] = $dao->contact_id;
    }

    //return if only array is required.
    if ($isIdsArray && $contactId) {
      return $additionalIDs;
    }

    //else build array of Additional participant's information.
    $template = CRM_Core_Smarty::singleton();
    $template->clear_assign(['additionalCustomPre', 'additionalCustomPost']);
    if (count($additionalIDs)) {
      if ($values['additional_custom_pre_id'] || $values['additional_custom_post_id']) {
        $isCustomProfile = TRUE;
        $i = 1;
        foreach ($additionalIDs as $pId => $cId) {
          //get the params submitted by participant.
          $participantParams = CRM_Utils_Array::value($pId, $values['params'], []);

          list($profilePre, $groupTitles) = self::buildCustomDisplay($values['additional_custom_pre_id'],
            'additionalCustomPre',
            $cId,
            $template,
            $pId,
            $isTest,
            $isCustomProfile,
            $participantParams
          );
          if ($profilePre) {
            $customProfile[$i]['additionalCustomPre'] = $profilePre;
            $customProfile[$i] = array_merge($groupTitles, $customProfile[$i]);
          }

          list($profilePost, $groupTitles) = self::buildCustomDisplay($values['additional_custom_post_id'],
            'additionalCustomPost',
            $cId,
            $template,
            $pId,
            $isTest,
            $isCustomProfile,
            $participantParams
          );
          if ($profilePost) {
            $customProfile[$i]['additionalCustomPost'] = array_diff_assoc($profilePost, $profilePre);
            $customProfile[$i] = array_merge($groupTitles, $customProfile[$i]);
          }
          $i++;
        }
      }
    }

    return $customProfile;
  }

  /* Function to retrieve all events those having location block set.
     * 
     * @return array $events array of all events.
     */

  static function getLocationEvents() {
    $events = [];

    $query = "
SELECT ca.*, sp.name AS state_province, sp.id AS sp_id, cm.email AS email, cp.phone AS phone, ce.loc_block_id
FROM   civicrm_event ce
INNER JOIN civicrm_loc_block lb ON ce.loc_block_id = lb.id
INNER JOIN civicrm_address ca   ON lb.address_id = ca.id
LEFT  JOIN civicrm_state_province sp ON ca.state_province_id = sp.id
LEFT  JOIN civicrm_email cm ON cm.id = lb.email_id
LEFT  JOIN civicrm_phone cp ON cp.id = lb.phone_id
ORDER BY sp.name, ca.city, ca.street_address ASC
";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $state_province = !empty($dao->sp_id) ? CRM_Core_PseudoConstant::stateProvince($dao->sp_id) : "";
      $country = !empty($dao->country_id) ? CRM_Core_PseudoConstant::country($dao->country_id) : "";
      $county = !empty($dao->county_id) ? CRM_Core_PseudoConstant::county($dao->county_id) : "";
      $fullPostalCode = $dao->postal_code;
      if (!empty($dao->postal_code_suffix)) {
        $fullPostalCode .= "-{$dao->postal_code_suffix}";
      }
      $fields = [
        'street_address' => $dao->street_address,
        'city' => $dao->city,
        'state_province_name' => $state_province,
        'supplemental_address_1' => $dao->supplemental_address_1,
        'supplemental_address_2' => $dao->supplemental_address_2,
        'county' => $county,
        'state_province' => $dao->state_province,
        'postal_code' => $fullPostalCode,
        'country' => $country,
        'address_name' => $dao->name,
      ];
      $title = CRM_Utils_Address::format($fields);
      if ($dao->email || $dao->phone) {
        $contactInfo = [];
        if ($dao->email) {
          $contactInfo[] = $dao->email;
        }
        if ($dao->phone) {
          $contactInfo[] = $dao->phone;
        }
        $title .= " - (".ts('Contact info').":".CRM_Utils_Array::implode(',', $contactInfo).")";
      }
      $events[$dao->loc_block_id] = $title;
    }

    return $events;
  }

  static function countEventsUsingLocBlockId($locBlockId) {
    if (!$locBlockId) {
      return 0;
    }

    $locBlockId = CRM_Utils_Type::escape($locBlockId, 'Integer');

    $query = "
SELECT count(*) FROM civicrm_event ce
WHERE  ce.loc_block_id = $locBlockId";

    return CRM_Core_DAO::singleValueQuery($query);
  }

  static function validRegistrationDate(&$values, $contactID) {
    // make sure that we are between  registration start date and registration end date
    $startDate = CRM_Utils_Date::unixTime(CRM_Utils_Array::value('registration_start_date', $values));
    $endDate = CRM_Utils_Date::unixTime(CRM_Utils_Array::value('registration_end_date', $values), true);
    $now = time();
    $validDate = TRUE;
    if ($startDate && $startDate >= $now) {
      $validDate = FALSE;
    }
    if ($endDate && $endDate < $now) {
      $validDate = FALSE;
    }

    // also check that the user has permission to register for this event
    $hasPermission = CRM_Core_Permission::event(CRM_Core_Permission::EDIT,
      $contactID
    );

    return $validDate && $hasPermission;
  }

  /* Function to Show - Hide the Registration Link.
     *
     * @param  array   $values key/value event info     
     * @return boolean true if allow registration otherwise false
     * @access public
     */

  static function showHideRegistrationLink($values, $forceAllowedRegister = FALSE) {

    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');
    $alreadyRegistered = FALSE;

    if ($forceAllowedRegister) {
      return TRUE;
    }
    if ($contactID) {
      $params = ['contact_id' => $contactID];

      if ($eventId = CRM_Utils_Array::value('id', $values['event'])) {
        $params['event_id'] = $eventId;
      }
      if ($roleId = CRM_Utils_Array::value('default_role_id', $values['event'])) {
        $params['role_id'] = $roleId;
      }
      $alreadyRegistered = self::alreadyRegistered($params);
    }

    if (CRM_Utils_Array::value('allow_same_participant_emails', $values['event']) ||
      !$alreadyRegistered
    ) {
      return TRUE;
    }
    return FALSE;
  }

  /* Function to check if given contact is already registered.
     *
     * @param  array   $params key/value participant info     
     * @return boolean $alreadyRegistered true/false
     * @access public
     */
  static function alreadyRegistered($params) {
    $alreadyRegistered = FALSE;
    if (!CRM_Utils_Array::value('contact_id', $params)) {
      return $alreadyRegistered;
    }



    $statusTypes = CRM_Event_PseudoConstant::participantStatus(NULL, "is_counted = 1");

    $participant = new CRM_Event_DAO_Participant();
    $participant->copyValues($params);

    $participant->is_test = CRM_Utils_Array::value('is_test', $params, 0);
    $participant->selectAdd();
    $participant->selectAdd('status_id');
    if ($participant->find(TRUE) && CRM_Utils_Array::arrayKeyExists($participant->status_id, $statusTypes)) {
      $alreadyRegistered = TRUE;
    }

    return $alreadyRegistered;
  }

  /**
   * make sure that the user has permission to access this event
   *
   * @param int $id   the id of the event
   * @param int $name the name or title of the event
   *
   * @return string   the permission that the user has (or null)
   * @access public
   * @static
   */
  static function checkPermission($eventId = NULL, $type = CRM_Core_Permission::VIEW) {
    static $permissions = NULL;

    if (empty($permissions)) {


      $allEvents = CRM_Event_PseudoConstant::event(NULL, TRUE);
      $createdEvents = [];

      $session = &CRM_Core_Session::singleton();
      if ($userID = $session->get('userID')) {
        $createdEvents = array_keys(CRM_Event_PseudoConstant::event(NULL, TRUE, "created_id={$userID}"));
      }

      // Note: for a multisite setup, a user with edit all events, can edit all events
      // including those from other sites
      if (CRM_Core_Permission::check('edit all events')) {
        $permissions[CRM_Core_Permission::EDIT] = array_keys($allEvents);
      }
      else {
        $permissions[CRM_Core_Permission::EDIT] = &CRM_ACL_API::group(CRM_Core_Permission::EDIT, NULL, 'civicrm_event', $allEvents, $createdEvents);
      }

      if (CRM_Core_Permission::check('edit all events')) {
        $permissions[CRM_Core_Permission::VIEW] = array_keys($allEvents);
      }
      else {
        if (CRM_Core_Permission::check('access CiviEvent') &&
          CRM_Core_Permission::check('view event participants')
        ) {
          // use case: allow "view all events" but NOT "edit all events"
          // so for a normal site allow users with these two permissions to view all events AND
          // at the same time also allow any hook to override if needed.
          $createdEvents = array_keys($allEvents);
        }
        $permissions[CRM_Core_Permission::VIEW] = &CRM_ACL_API::group(CRM_Core_Permission::VIEW, NULL, 'civicrm_event', $allEvents, $createdEvents);
      }

      $permissions[CRM_Core_Permission::DELETE] = [];
      if (CRM_Core_Permission::check('delete in CiviEvent')) {
        // Note: we want to restrict the scope of delete permission to
        // events that are editable/viewable (usecase multisite).
        // We can remove array_intersect once we have ACL support for delete functionality.
        $permissions[CRM_Core_Permission::DELETE] = array_intersect($permissions[CRM_Core_Permission::EDIT],
          $permissions[CRM_Core_Permission::VIEW]
        );
      }
    }

    if ($eventId) {
      return in_array($eventId, $permissions[$type]) ? TRUE : FALSE;
    }

    return $permissions;
  }

  /**
   * Build From Email as the combination of all the email ids of the logged in user,
   * the domain email id and the email id configured for the event
   *
   * @param int $eventId   the id of the event
   *
   * @return array         an array of email ids
   * @access public
   * @static
   */
  static function getFromEmailIds($eventId = NULL) {
    static $emails;
    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');

    $cacheKey = 'd';
    if ($eventId) {
      $cacheKey .= '_eid_' . $eventId;
    }
    if ($contactID) {
      $cacheKey .= '_cid_' . $contactID;
    }

    $fromEmailValues = $fromEmailIds = $eventEmail = $contactEmails = [];

    if (isset($emails[$cacheKey])) {
      return $emails[$cacheKey];
    }

    if ($eventId) {
      // add the email id configured for the event
      $params = ['id' => $eventId];
      $returnProperties = ['confirm_from_name', 'confirm_from_email', 'cc_confirm', 'bcc_confirm'];

      CRM_Core_DAO::commonRetrieve('CRM_Event_DAO_Event', $params, $eventEmail, $returnProperties);
      if (CRM_Utils_Array::value('confirm_from_name', $eventEmail)
        && CRM_Utils_Array::value('confirm_from_email', $eventEmail)
      ) {
        $fromEmailValues[] = $fromEmailIds[] = "{$eventEmail['confirm_from_name']} <{$eventEmail['confirm_from_email']}>";
      }
    }

    // add the domain email id

    $domainEmail = CRM_Core_BAO_Domain::getNameAndEmail();
    $domainEmail = "$domainEmail[0] <$domainEmail[1]>";
    if (!in_array($domainEmail, $fromEmailIds)) {
      $fromEmailValues[] = $fromEmailIds[] = $domainEmail;
    }


    // add logged in user's active email ids
    if ($contactID) {
      $contactEmails = CRM_Core_BAO_Email::allEmails($contactID);
      $fromDisplayName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactID, 'display_name');

      foreach ($contactEmails as $emailId => $emailVal) {
        $email = trim($emailVal['email']);
        if (!$email || $emailVal['on_hold']) {
          continue;
        }

        $fromEmail = "$fromDisplayName <$email>";
        if (!in_array($fromEmail, $fromEmailIds)) {
          $fromEmailValues[$emailId] = $fromEmailIds[] = $fromEmail;
          $fromEmailValues[$emailId] .= $emailVal['locationType'];

          if (CRM_Utils_Array::value('is_primary', $emailVal)) {
            $fromEmailValues[$emailId] .= ' ' . ts('(preferred)');
          }
        }
      }
    }

    foreach ($fromEmailValues as $key => $value) {
      $emailValues[] = htmlspecialchars($value);
    }

    $emails[$cacheKey] = ['name' => $fromEmailIds,
      'label' => $emailValues,
      'cc' => CRM_Utils_Array::value('cc_confirm', $eventEmail),
      'bcc' => CRM_Utils_Array::value('bcc_confirm', $eventEmail),
    ];
    return $emails[$cacheKey];
  }

  /**
   * Function to calculate event total seats occupied.
   *
   * @param int    $eventId          event id.
   * @param sting  $extraWhereClause extra filter on participants.
   *
   * @return int   event total seats w/ given criteria.
   * @access public
   * @static
   */
  static function eventTotalSeats($eventId, $extraWhereClause = NULL) {
    if (empty($eventId)) {
      return 0;
    }

    $extraWhereClause = trim($extraWhereClause);
    if (!empty($extraWhereClause)) {
      $extraWhereClause = " AND ( {$extraWhereClause} )";
    }

    //event seats calculation :
    //1. consider event seat as a single when participant does not have line item.
    //2. consider event seat as a single when participant has line items but does not
    //   have count for corresponding price field value ( ie price field value does not carry any seat )
    //3. consider event seat as a sum of all seats from line items in case price field value carries count.

    $query = "
    SELECT  IF ( SUM( value.count*lineItem.qty ),
                 SUM( value.count*lineItem.qty ) + 
                 COUNT( DISTINCT participant.id ) -
                 COUNT( DISTINCT IF ( value.count, participant.id, NULL ) ),
                 COUNT( DISTINCT participant.id ) ) 
      FROM  civicrm_participant participant
INNER JOIN  civicrm_contact contact ON ( contact.id = participant.contact_id AND contact.is_deleted = 0 ) 
INNER JOIN  civicrm_event event ON ( event.id = participant.event_id ) 
LEFT  JOIN  civicrm_line_item lineItem ON ( lineItem.entity_id    = participant.id 
                                       AND  lineItem.entity_table = 'civicrm_participant' ) 
LEFT  JOIN  civicrm_price_field_value value ON ( value.id = lineItem.price_field_value_id AND value.count )    
     WHERE  ( participant.event_id = %1 )
            {$extraWhereClause}
  GROUP BY  participant.event_id";

    return (int)CRM_Core_DAO::singleValueQuery($query, [1 => [$eventId, 'Positive']]);
  }

  static function assignEventShare($event, $templateObject = NULL) {
    if (!$templateObject) {
      $templateObject = CRM_Core_Smarty::singleton();
    }
    // Used is Add to Google Calendar button. refs #16572
    $eventInfoUrl = CRM_Utils_System::url('civicrm/event/info', 'reset=1&id='.$event['id'], TRUE, FALSE, FALSE);
    $gcal = [
      'trp' => 'true',
      'action' => 'TEMPLATE',
      'text' => $event['event_title'],
      'sprop' => $eventInfoUrl,
      'details' => $eventInfoUrl,
    ];
    $event['event_start_date'] = strtotime($event['event_start_date']);
    if (empty($event['event_end_date'])) {
      $event['event_end_date'] = strtotime('+1 hour',$event['event_start_date']);
    }else{
      $event['event_end_date'] = strtotime($event['event_end_date']);
    }
    $gcal['dates'] = gmstrftime('%Y%m%dT%H%M%SZ', $event['event_start_date']).'/'.gmstrftime('%Y%m%dT%H%M%SZ', $event['event_end_date']);

    if ($event['address']) {
      $gcal['location'] = $event['address'];
    }
    if ($event['summary']) {
      $gcal['details'] .= "\n\n". strip_tags($event['summary']);
    }
    $templateObject->assign('share_google_calendar', 'http://www.google.com/calendar/event?'.http_build_query($gcal));
  }

  static function tokenize($contactId, $input) {
    $output = $input;
    $tokens = CRM_Utils_Token::getTokens($input);
    if (isset($tokens['contact'])) {
      $returnProperties = [];
      foreach ($tokens['contact'] as $name) {
        $returnProperties[$name] = 1;
      }
      $contactParams = ['contact_id' => $contactId];
      list($contact) = CRM_Mailing_BAO_Mailing::getDetails($contactParams, $returnProperties, FALSE);
      $contact = $contact[$contactId];

      $output = CRM_Utils_Token::replaceContactTokens($input, $contact, FALSE, $tokens, FALSE, TRUE);
    }
    return $output;
  }
}

