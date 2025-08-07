<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                               |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */



/**
 * This class generates form components for processing Event
 *
 */
class CRM_Event_Form_Registration_ParticipantCancel extends CRM_Event_Form_Registration {
  public $_participantStatusId;
  public $_csContactID;
  // optional credit card return status code
  // CRM-6060
  protected $_cc = NULL;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $this->_participantId = CRM_Utils_Request::retrieve('participantId', 'Positive', $this);

    $this->_cc = CRM_Utils_Request::retrieve('cc', 'String', $this);

    //get the contact and event id and assing to session.
    $values = [];
    $csContactID = $eventId = NULL;
    if ($this->_participantId) {

      $params = ['id' => $this->_participantId];
      CRM_Core_DAO::commonRetrieve('CRM_Event_DAO_Participant', $params, $values,
        ['contact_id', 'event_id', 'status_id']
      );
    }

    $this->_participantStatusId = $values['status_id'];
    $this->_eventId = CRM_Utils_Array::value('event_id', $values);
    $csContactId = CRM_Utils_Array::value('contact_id', $values);

    // make sure we have right permission to edit this user
    $this->_csContactID = NULL;
    if ($csContactId && $this->_eventId) {
      $session = CRM_Core_Session::singleton();
      if ($csContactId == $session->get('userID')) {
        $this->_csContactID = $csContactId;
      }
      else {

        if (CRM_Contact_BAO_Contact_Permission::validateChecksumContact($csContactId, $this)) {
          //since we have landing page so get this contact
          //id in session if user really want to walk wizard.
          $this->_csContactID = $csContactId;
        }
      }
    }

    if (!$this->_csContactID) {
      $config = CRM_Core_Config::singleton();
       return CRM_Core_Error::statusBounce(ts('You do not have permission to access this event registration. Contact the site administrator if you need assistance.'), $config->userFrameworkBaseURL);
    }
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    if(CRM_Utils_Array::value('is_monetary', $values['event'])){
      $statusMessage = ts("Cancelling registration only available for free events. Contact your site administrator if you need assistance.");
       return CRM_Core_Error::statusBounce($statusMessage, CRM_Utils_System::url('civicrm/event/info', "reset=1&id={$this->_eventId}&noFullMsg=1",
          FALSE, NULL, FALSE, TRUE
        ));
      return ;
    }
    $params = ['id' => $this->_eventId];
    $values = [];
    CRM_Core_DAO::commonRetrieve('CRM_Event_DAO_Event', $params, $values,
      ['title']
    );

    $buttons = [];

    $statusMsg = NULL;

    // status class other than Negative should be able to cancel registration.
    if (CRM_Utils_Array::arrayKeyExists($this->_participantStatusId,
        CRM_Event_PseudoConstant::participantStatus(NULL, "class != 'Negative'")
      )) {
      $cancelConfirm = ts('Are you sure you want to cancel your registration for this event?');
      $buttons = array_merge($buttons, [['type' => 'next',
            'name' => ts('Cancel Registration'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'js' => ['onclick' => 'return confirm(\'' . $cancelConfirm . '\');'],
          ]]);
      if (!$statusMsg) {
        $url = CRM_Utils_System::url('civicrm/event/info', "reset=1&id={$this->_eventId}&noFullMsg=1",FALSE, NULL, FALSE, TRUE );
        $statusMsg = ts('You can cancel your registration for %1 by clicking "Cancel Registration".', [1 => "<a href='$url' target='_blank'>".$values['title']."</a>"]);
      }
    }
    if (!$statusMsg) {
      $statusMsg = ts("Oops, it looks like your registration for %1 has already been cancelled.",
        [1 => $values['title']]
      );
    }
    $this->assign('statusMsg', $statusMsg);

    $params = ['id' => $this->_eventId];
    CRM_Event_BAO_Event::retrieve($params, $values['event']);

    $this->assign('event', $values['event']);
    $this->assign('isShowLocation', CRM_Utils_Array::value('is_show_location', $values['event']));

    $params = ['entity_id' => $this->_eventId, 'entity_table' => 'civicrm_event'];

    $values['location'] = CRM_Core_BAO_Location::getValues($params, TRUE);

    //To show the event location on maps directly on event info page
    $config = CRM_Core_Config::singleton();
    $locations = &CRM_Event_BAO_Event::getMapInfo($this->_eventId);
    if (!empty($locations) && CRM_Utils_Array::value('is_map', $values['event'])) {
      $this->assign('locations', $locations);
      $this->assign('mapProvider', $config->mapProvider);
      $this->assign('mapKey', $config->mapAPIKey);
      $sumLat = $sumLng = 0;
      $maxLat = $maxLng = -400;
      $minLat = $minLng = + 400;
      foreach ($locations as $location) {
        $sumLat += $location['lat'];
        $sumLng += $location['lng'];

        if ($location['lat'] > $maxLat) {
          $maxLat = $location['lat'];
        }
        if ($location['lat'] < $minLat) {
          $minLat = $location['lat'];
        }

        if ($location['lng'] > $maxLng) {
          $maxLng = $location['lng'];
        }
        if ($location['lng'] < $minLng) {
          $minLng = $location['lng'];
        }
      }

      $center = ['lat' => (float ) $sumLat / count($locations),
        'lng' => (float ) $sumLng / count($locations),
      ];
      $span = ['lat' => (float )($maxLat - $minLat),
        'lng' => (float )($maxLng - $minLng),
      ];
      $this->assign_by_ref('center', $center);
      $this->assign_by_ref('span', $span);
      if ($action == CRM_Core_Action::PREVIEW) {
        $mapURL = CRM_Utils_System::url('civicrm/contact/map/event',
          "eid={$this->_eventId}&reset=1&action=preview",
          TRUE, NULL, TRUE,
          TRUE
        );
      }
      else {
        $mapURL = CRM_Utils_System::url('civicrm/contact/map/event',
          "eid={$this->_eventId}&reset=1",
          TRUE, NULL, TRUE,
          TRUE
        );
      }

      $this->assign('skipLocationType', TRUE);
      $this->assign('mapURL', $mapURL);
    }

    $this->assign('location', $values['location']);

    $this->addButtons($buttons);
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    //get the button.
    $buttonName = $this->controller->getButtonName();
    $eventId = $this->_eventId;
    $participantId = $this->_participantId;

    if ($buttonName == '_qf_ParticipantCancel_next') {
      //need to registration status to 'cancelled'.


      $cancelledId = array_search('Cancelled', CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Negative'"));
      $additionalParticipantIds = CRM_Event_BAO_Participant::getAdditionalParticipantIds($participantId);

      $participantIds = array_merge([$participantId], $additionalParticipantIds);
      $results = CRM_Event_BAO_Participant::transitionParticipants($participantIds, $cancelledId, NULL, TRUE);

      if (count($participantIds) > 1) {
        $statusMessage = ts("%1 Event registration(s) have been cancelled.", [1 => count($participantIds)]);
      }
      else {
        $statusMessage = ts("Your event registration has been cancelled.");
      }
      if (CRM_Utils_Array::value('mailedParticipants', $results)) {
        foreach ($results['mailedParticipants'] as $key => $displayName) {
          $statusMessage .= "<br />" . ts("Email has been sent to : %1", [1 => $displayName]);
        }
      }

       return CRM_Core_Error::statusBounce($statusMessage, CRM_Utils_System::url('civicrm/event/info', "reset=1&id={$this->_eventId}&noFullMsg=1",
          FALSE, NULL, FALSE, TRUE
        ));
    }
  }
}

