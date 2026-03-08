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
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

/**
 * Manages a session-stored list of recently viewed CiviCRM items.
 *
 * Provides methods to add, retrieve, and delete items from the
 * recently viewed queue, stored in the user's session.
 */
class CRM_Utils_Recent {

  /**
   * Maximum number of items in the recently viewed queue.
   *
   * @var int
   */
  public const MAX_ITEMS = 10, STORE_NAME = 'CRM_Utils_Recent';

  /**
   * The list of recently viewed items.
   *
   * @var array|null
   */
  private static $_recent = NULL;

  /**
   * Initialize the recently viewed list from the session.
   *
   * Loads the stored recent items from the session if not already loaded.
   *
   * @return void
   */
  public static function initialize() {
    if (!self::$_recent) {
      $session = CRM_Core_Session::singleton();
      self::$_recent = $session->get(self::STORE_NAME);
      if (empty(self::$_recent) || (!empty(self::$_recent) && !is_array(self::$_recent))) {
        self::$_recent = [];
      }
    }
  }

  /**
   * Return the recently viewed items array.
   *
   * @return array the recently viewed items
   */
  public static function &get() {
    self::initialize();
    return self::$_recent;
  }

  /**
   * Add an item to the recently viewed stack.
   *
   * If an item with the same URL already exists, it is removed before
   * adding the new entry to the top. The list is capped at MAX_ITEMS.
   *
   * @param string $title the display title
   * @param string $url the URL link for the item
   * @param int $id the entity ID
   * @param string $type the entity type (e.g. 'Contact', 'Activity')
   * @param int $contactId the related contact ID
   * @param string $contactName the related contact display name
   * @param array $others optional additional data (keys: subtype, isDeleted, imageUrl, editUrl, deleteUrl)
   *
   * @return void
   */
  public static function add(
    $title,
    $url,
    $id,
    $type,
    $contactId,
    $contactName,
    $others = []
  ) {
    self::initialize();
    $session = CRM_Core_Session::singleton();

    // make sure item is not already present in list
    for ($i = 0; $i < count(self::$_recent); $i++) {
      if (self::$_recent[$i]['url'] == $url) {
        // delete item from array
        array_splice(self::$_recent, $i, 1);
        break;
      }
    }

    if (!is_array($others)) {
      $others = [];
    }

    array_unshift(
      self::$_recent,
      ['title' => $title,
        'url' => $url,
        'id' => $id,
        'type' => $type,
        'contact_id' => $contactId,
        'contactName' => $contactName,
        'subtype' => CRM_Utils_Array::value('subtype', $others),
        'isDeleted' => CRM_Utils_Array::value('isDeleted', $others, FALSE),
        'image_url' => CRM_Utils_Array::value('imageUrl', $others),
        'edit_url' => CRM_Utils_Array::value('editUrl', $others),
        'delete_url' => CRM_Utils_Array::value('deleteUrl', $others),
      ]
    );
    if (count(self::$_recent) > self::MAX_ITEMS) {
      array_pop(self::$_recent);
    }

    CRM_Utils_Hook::recent(self::$_recent);

    $session->set(self::STORE_NAME, self::$_recent);
  }

  /**
   * Delete an item from the recently viewed stack by matching its ID and type.
   *
   * @param array $recentItem associative array with 'id' and 'type' keys identifying the item to remove
   *
   * @return void
   */
  public static function del($recentItem) {
    self::initialize();
    $tempRecent = self::$_recent;

    self::$_recent = [];

    // rebuild recent array excluding the matching item
    for ($i = 0; $i < count($tempRecent); $i++) {
      if (!(
        $tempRecent[$i]['id'] == $recentItem['id'] &&
          $tempRecent[$i]['type'] == $recentItem['type']
      )) {
        self::$_recent[] = $tempRecent[$i];
      }
    }

    $session = CRM_Core_Session::singleton();
    $session->set(self::STORE_NAME, self::$_recent);
  }

  /**
   * Remove all recently viewed items associated with a given contact ID.
   *
   * @param int $id the contact ID whose recent items should be removed
   *
   * @return void
   */
  public static function delContact($id) {
    self::initialize();

    $tempRecent = self::$_recent;

    self::$_recent = [];

    // rebuild recent.
    for ($i = 0; $i < count($tempRecent); $i++) {
      // don't include deleted contact in recent.
      if (CRM_Utils_Array::value('contact_id', $tempRecent[$i]) == $id) {
        continue;
      }
      self::$_recent[] = $tempRecent[$i];
    }

    $session = CRM_Core_Session::singleton();
    $session->set(self::STORE_NAME, self::$_recent);
  }
}
