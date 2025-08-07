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

/**
 * This class holds all the Pseudo constants that are specific to Mass mailing. This avoids
 * polluting the core class and isolates the mass mailer class
 */
class CRM_Mailing_PseudoConstant extends CRM_Core_PseudoConstant {

  /**
   * mailing approval status
   * @var array
   * @static
   */
  private static $approvalStatus;

  /**
   * mailing templates
   * @var array
   * @static
   */
  private static $template;

  /**
   * completed mailings
   * @var array
   * @static
   */
  private static $completed;

  /**
   * mailing components
   * @var array
   * @static
   */
  private static $component;

  /**
   * default component id's, indexed by component type
   */
  private static $defaultComponent;

  /**
   * default component id's, indexed by component type
   */
  private static $bounceType = [];

  /**
   * Get all the mailing components of a particular type
   *
   * @param $type the type of component needed
   * @access public
   *
   * @return array - array reference of all mailing components
   * @static
   */
  public static function &component($type = NULL) {
    $name = $type ? $type : 'ALL';

    if (!self::$component || !CRM_Utils_Array::arrayKeyExists($name, self::$component)) {
      if (!self::$component) {
        self::$component = [];
      }
      if (!$type) {
        self::$component[$name] = NULL;
        CRM_Core_PseudoConstant::populate(self::$component[$name], 'CRM_Mailing_DAO_Component');
      }
      else {
        // we need to add an additional filter for $type
        self::$component[$name] = [];



        $object = new CRM_Mailing_DAO_Component();
        $object->component_type = $type;
        $object->selectAdd();
        $object->selectAdd("id, name");
        $object->orderBy('component_type, is_default, name');
        $object->is_active = 1;
        $object->find();
        while ($object->fetch()) {
          self::$component[$name][$object->id] = $object->name;
        }
      }
    }
    return self::$component[$name];
  }

  /**
   * Determine the default mailing component of a given type
   *
   * @param $type the type of component needed
   * @param $undefined the value to use if no default is defined
   * @access public
   *
   * @return integer -The ID of the default mailing component.
   * @static
   */
  public static function &defaultComponent($type, $undefined = NULL) {
    if (!self::$defaultComponent) {
      $queryDefaultComponents = "SELECT id, component_type
                FROM    civicrm_mailing_component
                WHERE   is_active = 1
                AND     is_default = 1
                GROUP BY component_type";

      $dao = CRM_Core_DAO::executeQuery($queryDefaultComponents);

      self::$defaultComponent = [];
      while ($dao->fetch()) {
        self::$defaultComponent[$dao->component_type] = $dao->id;
      }
    }
    $value = CRM_Utils_Array::value($type, self::$defaultComponent, $undefined);
    return $value;
  }

  /**
   * Get all the mailing templates
   *
   * @access public
   *
   * @return array - array reference of all mailing templates if any
   * @static
   */
  public static function &template() {
    if (!self::$template) {
      CRM_Core_PseudoConstant::populate(self::$template, 'CRM_Mailing_DAO_Mailing', TRUE, 'name', 'is_template');
    }
    return self::$template;
  }

  /**
   * Get all the completed mailing
   *
   * @access public
   *
   * @return array - array reference of all mailing templates if any
   * @static
   */
  public static function &completed() {
    if (!self::$completed) {

      $mailingACL = CRM_Mailing_BAO_Mailing::mailingACL();
      CRM_Core_PseudoConstant::populate(self::$completed,
        'CRM_Mailing_DAO_Mailing',
        FALSE,
        'name',
        'is_completed',
        $mailingACL
      );
    }
    return self::$completed;
  }

  /**
   * Get all mail approval status.
   *
   * The static array approvalStatus is returned
   *
   * @access public
   * @static
   *
   * @return array - array reference of all mail approval statuses
   *
   */
  public static function &approvalStatus() {
    if (!self::$approvalStatus) {

      self::$approvalStatus = CRM_Core_OptionGroup::values('mail_approval_status');
    }
    return self::$approvalStatus;
  }

  /**
   * Labels for advanced search against mailing summary.
   *
   * @param $field
   *
   * @return unknown_type
   */
  public static function &yesNoOptions($field) {
    static $options;
    if (!$options) {
      $options = [
        'delivered' => [
          'Y' => ts('Delivered'), 'N' => ts('Not delivered'),
        ],
        'bounce' => [
          'N' => ts('Successful'), 'Y' => ts('Bounced'),
        ],
        'open' => [
          'Y' => ts('Opened'), 'N' => ts('Unopened/Hidden'),
        ],
        'click' => [
          'Y' => ts('Clicked'), 'N' => ts('Not Clicked'),
        ],
        'reply' => [
          'Y' => ts('Replied'), 'N' => ts('No Reply'),
        ],
      ];
    }
    return $options[$field];
  }

  public static function bounceType($key = 'id', $label = 'name'){
    $types = [];
    CRM_Core_DAO::commonRetrieveAll('CRM_Mailing_DAO_BounceType', 'id', NULL, $types);
    $bounceType = self::$bounceType;
    if(!isset($bounceType[$key.$label])){
      foreach($types as $t){
        if(isset($t[$key]) && isset($t[$label])){
          $bounceType[$key.$label][$t[$key]] = $t[$label];
        }
      }
    }
    return $bounceType[$key.$label];
  }
}

