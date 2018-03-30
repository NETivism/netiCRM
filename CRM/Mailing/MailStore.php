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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */

class CRM_Mailing_MailStore {
  // flag to decide whether to print debug messages
  var $_debug = FALSE;

  /**
   * Return the proper mail store implementation, based on config settings
   *
   * @param  string $name  name of the settings set from civimail_mail_settings to use (null for default)
   *
   * @return object        mail store implementation for processing CiviMail-bound emails
   */
  function getStore($name = NULL) {
    if($name) {
      $params = array(
        'name' => $name,
      );
    }
    else{
      $params = array(
        'is_default' => 1,
      );
    }
    $setting = array();
    CRM_Core_BAO_MailSettings::retrieve($params, $setting);
    $protocols = CRM_Core_PseudoConstant::mailProtocol();

    switch ($protocols[$setting['protocol']]) {
      case 'IMAP':
        require_once 'CRM/Mailing/MailStore/Imap.php';
        return new CRM_Mailing_MailStore_Imap($setting['server'], $setting['username'], $setting['password'], (bool) $setting['is_ssl'], $setting['source']);
      case 'POP3':
        require_once 'CRM/Mailing/MailStore/Pop3.php';
        return new CRM_Mailing_MailStore_Pop3($setting['server'], $setting['username'], $setting['password'], (bool) $setting['is_ssl']);
      case 'Maildir':
        require_once 'CRM/Mailing/MailStore/Maildir.php';
        return new CRM_Mailing_MailStore_Maildir($setting['source']);

      case 'Localdir':
        require_once 'CRM/Mailing/MailStore/Localdir.php';
        return new CRM_Mailing_MailStore_Localdir($setting['source']);

      // DO NOT USE the mbox transport for anything other than testing
      // in particular, it does not clear the mbox afterwards

      case 'mbox':
        require_once 'CRM/Mailing/MailStore/Mbox.php';
        return new CRM_Mailing_MailStore_Mbox($setting['source']);

      default:
        throw new Exception("Unknown protocol {$setting['protocol']}");
    }
  }

  /**
   * Return all emails in the mail store
   *
   * @return array  array of ezcMail objects
   */
  function allMails() {
    return $this->fetchNext(0);
  }

  /**
   * Expunge the messages marked for deletion; stub function to be redefined by IMAP store
   */
  function expunge() {}

  /**
   * Return the next X messages from the mail store
   *
   * @param int $count  number of messages to fetch (0 to fetch all)
   *
   * @return array      array of ezcMail objects
   */
  function fetchNext($count = 1) {
    if (isset($this->_transport->options->uidReferencing) and $this->_transport->options->uidReferencing) {
      $identifiers = $this->_transport->listUniqueIdentifiers();
      if (is_array($identifiers)) {
        $offset = array_shift($identifiers);
      }
    }
    if (empty($offset)) {
      $offset = 1;
    }
    try {
      $set = $this->_transport->fetchFromOffset($offset, $count);
      if ($this->_debug) {
        print "fetching $count messages\n";
      }
    }
    catch(ezcMailOffsetOutOfRangeException$e) {
      if ($this->_debug) {
        print "got to the end of the mailbox\n";
      }
      return array();
    }
    $mails = array();
    $parser = new ezcMailParser;
    //set property text attachment as file CRM-5408
    $parser->options->parseTextAttachmentsAsFiles = TRUE;

    foreach ($set->getMessageNumbers() as $nr) {
      if ($this->_debug) {
        print "retrieving message $nr\n";
      }
      $single = $parser->parseMail($this->_transport->fetchByMessageNr($nr));
      $mails[$nr] = $single[0];
    }
    return $mails;
  }

  /**
   * Point to (and create if needed) a local Maildir for storing retrieved mail
   *
   * @param string $name  name of the Maildir
   *
   * @return string       path to the Maildir's cur directory
   */
  function maildir($name) {
    $config = CRM_Core_Config::singleton();
    $dir = $config->customFileUploadDir . DIRECTORY_SEPARATOR . $name;
    foreach (array(
        'cur', 'new', 'tmp',
      ) as $sub) {
      if (!file_exists($dir . DIRECTORY_SEPARATOR . $sub)) {
        if ($this->_debug) {
          print "creating $dir/$sub\n";
        }
        if (!mkdir($dir . DIRECTORY_SEPARATOR . $sub, 0700, TRUE)) {
          throw new Exception('Could not create ' . $dir . DIRECTORY_SEPARATOR . $sub);
        }
      }
    }
    return $dir . DIRECTORY_SEPARATOR . 'cur';
  }
}

