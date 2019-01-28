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
    $activeOptionIdsText = CRM_Utils_Request::retrieve('activePriceOptionIds', 'Text', $object, False, '', 'Post');
    if(!empty($activeOptionIdsText)){
      $activeOptionIds = explode(',', $activeOptionIdsText);
    }

    if(empty($event_id)){
      $qfKey = CRM_Utils_Request::retrieve('qfKey', 'Text', $object, False, '', 'Post');
      $session = CRM_Core_Session::singleton();
      $event_id = $session->get('id', 'CRM_Event_Controller_Registration_'.$qfKey);
    }

    if(!empty($activeOptionIds)){
      $coupon = CRM_Coupon_BAO_Coupon::validEventFromCode($code, $activeOptionIds, 'civicrm_price_field_value');
    }
    if(!$coupon){
      $coupon = CRM_Coupon_BAO_Coupon::validEventFromCode($code, $event_id);
    }
    if($coupon){
      $return = array(
        'description' => $coupon['description'],
      );
      if($coupon['entity_table'] == 'civicrm_price_field_value'){
        $return['entity_table'] = 'civicrm_price_field_value';
        $fields = array();
        foreach ($coupon['entity_id'] as $vid) {
          $sql = "SELECT price_field_id FROM civicrm_price_field_value WHERE id = %1";
          $params = array(1 => array($vid, 'Integer'));
          $fid = CRM_Core_DAO::singleValueQuery($sql, $params);
          $fieldName = 'price_'.$fid;
          $fields[] = array(
            'fieldName' => $fieldName,
            'vid' => $vid,
          );
        }
        $return['fields'] = $fields;
      }
    }
    else{
      $return = NULL;
    }
    $return = json_encode($return);
    print($return);
    exit;
  }


}