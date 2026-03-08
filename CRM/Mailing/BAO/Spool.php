<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 * @copyright CiviCRM LLC (c) 2004-2011
 *
 */

class CRM_Mailing_BAO_Spool extends CRM_Mailing_DAO_Spool {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Store Mails into Spool table.
   *
   * @param string|array $recipient Either a comma-separated list of recipients
   *              (RFC822 compliant), or an array of recipients,
   *              each RFC822 valid. This may contain recipients not
   *              specified in the headers, for Bcc:, resending
   *              messages, etc.
   *
   * @param array $headers The array of headers to send with the mail.
   *
   * @param string $body The full text of the message body, including any
   *               MIME parts, etc.
   *
   * @param int $job_id The job ID.
   *
   * @return bool Returns true on success.
   */
  public function send($recipient, $headers, $body, $job_id) {

    $headerStr = [];
    foreach ($headers as $name => $value) {
      $headerStr[] = "$name: $value";
    }
    $headerStr = CRM_Utils_Array::implode("\n", $headerStr);

    $session = CRM_Core_Session::singleton();

    $params = [
      'job_id' => $job_id,
      'recipient_email' => $recipient,
      'headers' => $headerStr,
      'body' => $body,
      'added_at' => date("YmdHis"),
      'removed_at' => NULL,
    ];

    $spoolMail = new CRM_Mailing_DAO_Spool();
    $spoolMail->copyValues($params);
    $spoolMail->save();

    return TRUE;
  }
}
