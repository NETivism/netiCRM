<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */

/**
 * This is base class for all ajax calls
 */
class CRM_Coupon_Page_AJAX {
  static function validEventFromCode(){
    $code = CRM_Utils_Request::retrieve('code', 'Text', $object, False, '', 'Post');
    $event_id = CRM_Utils_Request::retrieve('event_id', 'Positive', $object, False, '', 'Post');
    if(empty($event_id)){
      $qfKey = CRM_Utils_Request::retrieve('qfKey', 'Text', $object, False, '', 'Post');
      $session = CRM_Core_Session::singleton();
      $event_id = $session->get('id', 'CRM_Event_Controller_Registration_'.$qfKey);
    }

    $activeOptionIdsText = CRM_Utils_Request::retrieve('activePriceOptionIds', 'Text', $object, False, '', 'Post');
    if(!empty($activeOptionIdsText)){
      $activeOptionIds = explode(',', $activeOptionIdsText);
    }

    if (!empty($event_id) && CRM_Utils_Rule::positiveInteger($event_id)) {
      $coupon = CRM_Coupon_BAO_Coupon::validEventFromCode($code, $event_id);
      if($coupon){
        // this coupon doesn't specify any event, check price field value
        if (empty($coupon['used_for']['civicrm_event']) && !empty($coupon['used_for']['civicrm_price_field_value'])) {
          $matches = array_intersect($coupon['used_for']['civicrm_price_field_value'], $activeOptionIds);
          if (!empty($matches)) {
            $coupon['used_for']['civicrm_price_field_value'] = $matches;
            $json = self::prepareJson($coupon);
          }
        }
        else {
          $json = self::prepareJson($coupon);
        }
      }
    }
    if ($json) {
      echo $json;
    }
    else {
      $json = json_encode(['error' => ts('The coupon is not valid.')]);
      echo $json;
      http_response_code(400);
    }
    CRM_Utils_System::civiExit();
  }

  public static function prepareJson($coupon) {
    $return = [
      'description' => $coupon['description'],
    ];
    if (!empty($coupon['used_for']['civicrm_price_field_value'])) {
      $fvids = $coupon['used_for']['civicrm_price_field_value'];
      $return['entity_table'] = 'civicrm_price_field_value';
      $fields = [];
      $sql = "SELECT price_field_id, id FROM civicrm_price_field_value WHERE id IN(".CRM_Utils_Array::implode(',', $fvids).")";
      $dao = CRM_Core_DAO::executeQuery($sql);
      while($dao->fetch()) {
        $fieldName = 'price_'.$dao->price_field_id;
        $fields[] = [
          'fieldName' => $fieldName,
          'vid' => $dao->id,
        ];
      }
      $return['fields'] = $fields;
    }
    return json_encode($return);
  }
}
