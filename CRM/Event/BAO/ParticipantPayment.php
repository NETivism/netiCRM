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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */


class CRM_Event_BAO_ParticipantPayment extends CRM_Event_DAO_ParticipantPayment {

  static function &create(&$params, &$ids) {

    if (CRM_Utils_Array::value('id', $params)) {
      CRM_Utils_Hook::pre('edit', 'ParticipantPayment', $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'ParticipantPayment', NULL, $params);
    }
    $paymentParticipant = new CRM_Event_BAO_ParticipantPayment();
    $paymentParticipant->copyValues($params);
    if (isset($ids['id'])) {
      $paymentParticipant->id = CRM_Utils_Array::value('id', $ids);
    }
    else {
      $paymentParticipant->find(TRUE);
    }
    $paymentParticipant->save();

    if (CRM_Utils_Array::value('id', $params)) {
      CRM_Utils_Hook::post('edit', 'ParticipantPayment', $paymentParticipant->id, $paymentParticipant);
    }
    else {
      CRM_Utils_Hook::post('create', 'ParticipantPayment', $paymentParticipant->id, $paymentParticipant);
    }

    return $paymentParticipant;
  }

  /**
   * Delete the record that are associated with this Participation Payment
   *
   * @param  array  $params   array in the format of $field => $value.
   *
   * @return boolean  true if deleted false otherwise
   * @access public
   */
  static function deleteParticipantPayment($params) {
    $participantPayment = new CRM_Event_DAO_ParticipantPayment();

    $valid = FALSE;
    foreach ($params as $field => $value) {
      if (!empty($value)) {
        $participantPayment->$field = $value;
        $valid = TRUE;
      }
    }

    if (!$valid) {
      CRM_Core_Error::fatal();
    }
    $participantPayment->find();

    while ($participantPayment->fetch()) {
      $participantPayment->delete();
    }
    if($participantPayment){
      return $participantPayment;
    }
    return FALSE;
  }
}

