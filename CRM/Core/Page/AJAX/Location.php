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
 * This class contains all the function that are called using AJAX
 */
class CRM_Core_Page_AJAX_Location {

  /**
   * FIXME: we should make this method like getLocBlock() OR use the same method and
   * remove this one.
   *
   * Function to obtain the location of given contact-id.
   * This method is used by on-behalf-of form to dynamically generate poulate the
   * location field values for selected permissioned contact.
   */
  function getPermissionedLocation() {
    if (!CRM_Core_Permission::check('access CiviContribute')) {
      CRM_Utils_System::civiExit();
    }
    $cid = CRM_Utils_Type::escape($_GET['cid'], 'Integer');


    $entityBlock = array('contact_id' => $cid);
    $location = &CRM_Core_BAO_Location::getValues($entityBlock);

    $config = CRM_Core_Config::singleton();
    $addressSequence = array_flip($config->addressSequence());

    $elements = array("phone_1_phone" =>
      $location['phone'][1]['phone'],
      "email_1_email" =>
      $location['email'][1]['email'],
    );

    if (CRM_Utils_Array::arrayKeyExists('street_address', $addressSequence)) {
      $elements["address_1_street_address"] = $location['address'][1]['street_address'];
    }
    if (CRM_Utils_Array::arrayKeyExists('supplemental_address_1', $addressSequence)) {
      $elements['address_1_supplemental_address_1'] = $location['address'][1]['supplemental_address_1'];
    }
    if (CRM_Utils_Array::arrayKeyExists('supplemental_address_2', $addressSequence)) {
      $elements['address_1_supplemental_address_2'] = $location['address'][1]['supplemental_address_2'];
    }
    if (CRM_Utils_Array::arrayKeyExists('city', $addressSequence)) {
      $elements['address_1_city'] = $location['address'][1]['city'];
    }
    if (CRM_Utils_Array::arrayKeyExists('postal_code', $addressSequence)) {
      $elements['address_1_postal_code'] = $location['address'][1]['postal_code'];
      $elements['address_1_postal_code_suffix'] = $location['address'][1]['postal_code_suffix'];
    }
    if (CRM_Utils_Array::arrayKeyExists('country', $addressSequence)) {
      $elements['address_1_country_id'] = $location['address'][1]['country_id'];
    }
    if (CRM_Utils_Array::arrayKeyExists('state_province', $addressSequence)) {
      $elements['address_1_state_province_id'] = $location['address'][1]['state_province_id'];
    }

    echo json_encode($elements);
    CRM_Utils_System::civiExit();
  }

  public static function jqState($config) {
    $elements = array();
    if (!isset($_GET['_value']) || empty($_GET['_value']) || !CRM_Utils_Type::validate($_GET['_value'], 'Positive', FALSE)) {
      echo json_encode($elements);
      CRM_Utils_System::civiExit();
    }

    $result = &CRM_Core_PseudoConstant::stateProvinceForCountry($_GET['_value']);

    $elements = array(array('name' => ts('- select a state-'),
        'value' => '',
      ));
    foreach ($result as $id => $name) {
      $elements[] = array('name' => $name,
        'value' => $id,
      );
    }

    echo json_encode($elements);
    CRM_Utils_System::civiExit();
  }

  static function getLocBlock() {
    // i wish i could retrieve loc block info based on loc_block_id,
    // Anyway, lets retrieve an event which has loc_block_id set to 'lbid'.
    if ($_POST['lbid']) {
      $params = array('1' => array($_POST['lbid'], 'Integer'));
      $eventId = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_event WHERE loc_block_id=%1 LIMIT 1', $params);
    }
    // now lets use the event-id obtained above, to retrieve loc block information.
    if ($eventId) {
      $params = array('entity_id' => $eventId, 'entity_table' => 'civicrm_event');

      // second parameter is of no use, but since required, lets use the same variable.
      $location = CRM_Core_BAO_Location::getValues($params, $params);
    }

    $result = array();

    $addressOptions = CRM_Core_BAO_Preferences::valueOptions('address_options', TRUE, NULL, TRUE);
    // lets output only required fields.
    foreach ($addressOptions as $element => $isSet) {
      if ($isSet && (!in_array($element, array('im', 'openid')))) {
        if (in_array($element, array('country', 'state_province', 'county'))) {
          $element .= '_id';
        }
        elseif ($element == 'address_name') {
          $element = 'name';
        }
        $fld = "address[1][{$element}]";
        $value = CRM_Utils_Array::value($element, $location['address'][1]);
        $value = $value ? $value : "";
        $result[str_replace(array('][', '[', "]"), array('_', '_', ''), $fld)] = $value;
      }
    }

    foreach (array('email', 'phone_type_id', 'phone') as $element) {
      $block = ($element == 'phone_type_id') ? 'phone' : $element;
      for ($i = 1; $i < 3; $i++) {
        $fld = "{$block}[{$i}][{$element}]";
        $value = CRM_Utils_Array::value($element, $location[$block][$i]);
        $value = $value ? $value : "";
        $result[str_replace(array('][', '[', "]"), array('_', '_', ''), $fld)] = $value;
      }
    }

    // set the message if loc block is being used by more than one event.

    $result['count_loc_used'] = CRM_Event_BAO_Event::countEventsUsingLocBlockId($_POST['lbid']);

    echo json_encode($result);
    exit();
  }
}

