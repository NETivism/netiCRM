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
 * This class generates form components for Activity Links
 *
 */
class CRM_Activity_Form_ActivityLinks extends CRM_Core_Form {
  public function buildQuickForm() {
    self::commonBuildQuickForm($this);
  }
  public static function commonBuildQuickForm($self) {
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $self);
    $urlParams = "action=add&reset=1&cid={$contactId}&selectedChild=activity&atype=";

    $url = CRM_Utils_System::url('civicrm/contact/view/activity',
      $urlParams, FALSE, NULL, FALSE
    );

    $activityTypes = [];

    if (CRM_Utils_Mail::validOutBoundMail() && $contactId) {

      list($name, $email, $doNotEmail, $onHold, $isDeseased) = CRM_Contact_BAO_Contact::getContactDetails($contactId);
      if (!$doNotEmail && $email && !$isDeseased) {
        $activityTypes[3] = ts('Send an Email');
      }
    }
    $providersCount = CRM_SMS_BAO_Provider::activeProviderCount();
    if ($providersCount) {
      $phones = CRM_Core_BAO_Phone::allPhones($contactId, FALSE, ts('Mobile'));
      if (!empty($phones)) {
        $activityTypes[4] = ts('Send SMS');
      }
    }

    // this returns activity types sorted by weight
    $otherTypes = CRM_Core_PseudoConstant::activityType(FALSE);

    $activityTypes += $otherTypes;

    $self->assign('activityTypes', $activityTypes);
    $self->assign('url', $url);

    $self->assign('suppressForm', TRUE);
  }
}

