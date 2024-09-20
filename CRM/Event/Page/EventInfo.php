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
 * Event Info Page - Summmary about the event
 */
class CRM_Event_Page_EventInfo extends CRM_Core_Page {

  public $_id;
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
    //get the event id.
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $this->track();
    $config = CRM_Core_Config::singleton();

    // ensure that the user has permission to see this page
    if (!CRM_Core_Permission::event(CRM_Core_Permission::VIEW,
        $this->_id
      )) {
       return CRM_Core_Error::statusBounce(ts('You do not have permission to view this event'));
    }

    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE);
    $context = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'register');
    $this->assign('context', $context);

    // Sometimes we want to suppress the Event Full msg
    $noFullMsg = CRM_Utils_Request::retrieve('noFullMsg', 'String', $this, FALSE, 'false');

    // set breadcrumb to append to 2nd layer pages
    $breadCrumbPath = CRM_Utils_System::url("civicrm/event/info", "id={$this->_id}&reset=1");
    $additionalBreadCrumb = "<a href=\"$breadCrumbPath\">" . ts('Events') . '</a>';

    //retrieve event information
    $params = array('id' => $this->_id);
    CRM_Event_BAO_Event::retrieve($params, $values['event']);

    if (!$values['event']['is_active'] && CRM_Core_Permission::check('access CiviEvent')) {
      CRM_Core_Session::setStatus(ts('Preview Page - %1', array(1 => $values['event']['title'])));
    }
    elseif (!$values['event']['is_active']) {
      // form is inactive, die a fatal death
       return CRM_Core_Error::statusBounce(ts('The page you requested is currently unavailable.'));
    }

    if (!empty($values['event']['is_template'])) {
      // form is an Event Template
       return CRM_Core_Error::statusBounce(ts('The page you requested is currently unavailable.'));
    }

    $this->assign('isShowLocation', CRM_Utils_Array::value('is_show_location', $values['event']));

    // show event fees.

    if ($this->_id && CRM_Utils_Array::value('is_monetary', $values['event'])) {
      $values['feeBlock'] = self::feeBlock($this->_id);
      if (!empty($values['feeBlock']['price_set_id'])) {
        $this->assign('isPriceSet', 1);
      }
    }

    $params = array('entity_id' => $this->_id, 'entity_table' => 'civicrm_event');

    $values['location'] = CRM_Core_BAO_Location::getValues($params, TRUE);

    //retrieve custom field information

    $groupTree = &CRM_Core_BAO_CustomGroup::getTree("Event", $this, $this->_id, 0, $values['event']['event_type_id']);
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree);
    $this->assign('action', CRM_Core_Action::VIEW);
    //To show the event location on maps directly on event info page
    if (CRM_Utils_Array::value('is_map', $values['event'])) {
      $locations = CRM_Event_BAO_Event::getMapInfo($this->_id);
      $this->assign('locationsJson', json_encode($locations));
      $this->assign_by_ref('locations', $locations);
      $this->assign('mapProvider', $config->mapProvider);
      $this->assign('mapKey', $config->mapAPIKey);
    }

    $eventFullMessage = CRM_Event_BAO_Participant::eventFull($this->_id);
    $contactID = NULL;
    $contactID = $this->getContactID();
    $forceAllowedRegister = NULL;
    CRM_Utils_Hook::checkRegistration($contactID, NULL, $this, FALSE, $forceAllowedRegister);
    $hasWaitingList = CRM_Utils_Array::value('has_waitlist', $values['event']);

    $allowRegistration = FALSE;
    if (CRM_Utils_Array::value('is_online_registration', $values['event'])) {
      if (CRM_Event_BAO_Event::validRegistrationDate($values['event'], $this->_id)) {
        if ($forceAllowedRegister || !$eventFullMessage || $hasWaitingList) {
          $registerText = ts('Register Now');
          if (CRM_Utils_Array::value('registration_link_text', $values['event'])) {
            $registerText = $values['event']['registration_link_text'];
          }
          //Fixed for CRM-4855
          $allowRegistration = CRM_Event_BAO_Event::showHideRegistrationLink($values, $forceAllowedRegister);

          $this->assign('registerText', $registerText);
        }

        // we always generate urls for the front end in joomla
        if ($action == CRM_Core_Action::PREVIEW) {
          $url = CRM_Utils_System::url('civicrm/event/register',
            "id={$this->_id}&reset=1&action=preview",
            TRUE, NULL, TRUE,
            TRUE
          );
        }
        else {
          $url = CRM_Utils_System::url('civicrm/event/register',
            "id={$this->_id}&reset=1",
            TRUE, NULL, TRUE,
            TRUE
          );
        }
        if (!$eventFullMessage || $hasWaitingList) {
          $this->assign('registerURL', $url);
        }
      }
      elseif (CRM_Core_Permission::check('register for events')) {
        $this->assign('registerClosed', TRUE);
      }
    }

    $this->assign('allowRegistration', $allowRegistration);

    if ($eventFullMessage && ($noFullMsg == 'false')) {
      $statusMessage = $eventFullMessage;

      $session = CRM_Core_Session::singleton();
      $params = array('contact_id' => $session->get('userID'),
        'event_id' => CRM_Utils_Array::value('id', $values['event']),
        'role_id' => CRM_Utils_Array::value('default_role_id', $values['event']),
      );

      if (CRM_Event_BAO_Event::alreadyRegistered($params)) {
        $statusMessage = ts("Oops. It looks like you are already registered for this event. If you want to change your registration, or you feel that you've gotten this message in error, please contact the site administrator.");
      }
      elseif ($hasWaitingList) {
        $statusMessage = CRM_Utils_Array::value('waitlist_text', $values['event']);
        if (!$statusMessage) {
          $statusMessage = ts('Event is currently full, but you can register and be a part of waiting list.');
        }
      }

      CRM_Core_Session::setStatus($statusMessage);
    }
    // we do not want to display recently viewed items, so turn off
    $this->assign('displayRecent', FALSE);

    // set page title = event title
    CRM_Utils_System::setTitle($values['event']['title']);
    $this->assign('event', $values['event']);

    if (isset($values['feeBlock'])) {
      $this->assign('feeBlock', $values['feeBlock']);
    }
    $this->assign('location', $values['location']);
    if ($values['location']) {
      $values['event']['address'] = $values['location']['address'][1]['display_text'];
    }
    CRM_Event_BAO_Event::assignEventShare($values['event'], $this);


    // Prepare params used for meta.
    $params = array();
    $siteName = CRM_Utils_System::siteName();
    $params['site'] = $siteName;
    $params['title'] = $values['event']['title'] . ' - ' . $siteName;

    $description = $values['event']['description'];
    $description = preg_replace("/ *<(?<tag>(style|script))( [^=]+=['\"][^'\"]*['\"])*>(.*?(\n))+.*?<\/\k<tag>>/", "", $description);
    $description = strip_tags($description);
    $description = preg_replace("/(?:(?:&nbsp;)|\n|\r)+/", ' ', $description);
    $description = trim(mb_substr($description, 0, 150));
    $params['description'] = $description;
    $groupTree = &CRM_Core_BAO_CustomGroup::getTree("Event", $this, $this->_id, 0, $values['event']['event_type_id']);
    foreach ($groupTree as $ufg_inner) {
      if (is_array($ufg_inner['fields'])) {
        foreach ($ufg_inner['fields'] as $uffield) {
          if (is_array($uffield)) {
            if ($uffield['data_type'] == 'File') {
              if (!empty($uffield['customValue'][1]) && preg_match('/\.(jpg|png|jpeg)$/',$uffield['customValue'][1]['data'])) {
                $image = $config->customFileUploadURL . $uffield['customValue'][1]['data'];
                break;
                break;
                break;
              }
            }
          }
        }
      }
    }
    if (empty($image)) {
      preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i', $values['event']['description'], $matches);
      if (count($matches) >= 2) {
        $image = $matches[1];
      }
    }
    $params['image'] = $image;
    CRM_Utils_System::setPageMetaInfo($params);

    parent::run();
  }

  function getTemplateFileName() {
    if ($this->_id) {
      $templateFile = "CRM/Event/Page/{$this->_id}/EventInfo.tpl";
      $template = &CRM_Core_Page::getTemplate();

      if ($template->template_exists($templateFile)) {
        return $templateFile;
      }
    }
    return parent::getTemplateFileName();
  }

  function track() {
    $params = array(
      'state' => '0',
      'page_type' => 'civicrm_event',
      'page_id' => $this->_id,
      'visit_date' => date('Y-m-d H:i:s'),
    );
    CRM_Core_BAO_Track::add($params);
  }

  function getContactID() {
    //check if this is a checksum authentication
    $userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $this);
    if ($userChecksum) {
      //check for anonymous user.

      $validUser = CRM_Contact_BAO_Contact_Utils::validChecksum($tempID, $userChecksum);
      if ($validUser) {
        return $tempID;
      }
    }

    // check if the user is registered and we have a contact ID
    $session = CRM_Core_Session::singleton();
    return $session->get('userID');
  }

  static function feeBlock($eventId) {
    $feeBlock = array();
    if ($priceSetId = CRM_Price_BAO_Set::getFor('civicrm_event', $eventId)) {
      $feeBlock['price_set_id'] = $priceSetId;
      $setDetails = CRM_Price_BAO_Set::getSetDetail($priceSetId);
      $priceSetFields = $setDetails[$priceSetId]['fields'];
      if (is_array($priceSetFields)) {
        $fieldCnt = 1;
        $visibility = CRM_Core_PseudoConstant::visibility('name');

        foreach ($priceSetFields as $fid => $fieldValues) {
          if (!is_array($fieldValues['options']) ||
            empty($fieldValues['options']) ||
            CRM_Utils_Array::value('visibility_id', $fieldValues) != array_search('public', $visibility)
          ) {
            continue;
          }

          if (count($fieldValues['options']) > 1) {
            $feeBlock['value'][$fieldCnt] = '';
            $feeBlock['label'][$fieldCnt] = $fieldValues['label'];
            $feeBlock['lClass'][$fieldCnt] = 'price_set_option_group-label';
            $fieldCnt++;
            $labelClass = 'price_set_option-label';
          }
          else {
            $labelClass = 'price_set_field-label';
          }

          foreach ($fieldValues['options'] as $optionId => $optionVal) {
            $feeBlock['value'][$fieldCnt] = $optionVal['amount'];
            $feeBlock['label'][$fieldCnt] = $optionVal['label'];
            $feeBlock['lClass'][$fieldCnt] = $labelClass;
            $fieldCnt++;
          }
        }
      }
    }
    else {
      //retrieve event fee block.
      $discountId = CRM_Core_BAO_Discount::findSet($eventId, 'civicrm_event');
      if ($discountId) {
        $optionGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Discount', $discountId, 'option_group_id');
        CRM_Core_OptionGroup::getAssoc($optionGroupId, $feeBlock, FALSE, 'id');
        $feeBlock['is_discount'] = 1;
      }
      else {
        CRM_Core_OptionGroup::getAssoc("civicrm_event.amount.{$eventId}", $feeBlock);
      }
    }
    return $feeBlock;
  }
}

