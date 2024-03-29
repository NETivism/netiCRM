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

require_once 'CRM/Core/OptionGroup.php';

/**
 * This class holds all the Pseudo constants those
 * are specific to Campaign and Survey.
 */
class CRM_Campaign_PseudoConstant extends CRM_Core_PseudoConstant {

  /**
   * Activity types
   * @var array
   * @static
   */
  private static $activityType;

  /**
   * Campaign Type
   * @var array
   * @static
   */
  private static $campaignType = array();

  /**
   * Campaign Status
   * @var array
   * @static
   */
  private static $campaignStatus = array();

  /**
   * Get all the survey activity types
   *
   * @access public
   *
   * @return array - array reference of all survey activity types.
   * @static
   */
  public static function &activityType($all = TRUE, $includeCaseActivities = FALSE, $reset = FALSE, $returnColumn = 'label', $includeCampaignActivities = FALSE) {
    $cacheKey = $returnColumn;
    if (!isset(self::$activityType[$cacheKey])) {
      require_once 'CRM/Core/OptionGroup.php';
      $campaingCompId = CRM_Core_Component::getComponentID('CiviCampaign');
      if ($campaingCompId) {
        self::$activityType[$cacheKey] = CRM_Core_OptionGroup::values('activity_type',
          FALSE, FALSE, FALSE,
          " AND v.component_id={$campaingCompId}",
          $returnColumn
        );
      }
    }

    return self::$activityType[$cacheKey];
  }

  /**
   * Get all campaign types.
   *
   * The static array campaignType is returned
   *
   * @access public
   * @static
   *
   * @return array - array reference of all campaign types.
   *
   */
  public static function &campaignType() {
    if (!self::$campaignType) {
      require_once 'CRM/Core/OptionGroup.php';
      self::$campaignType = CRM_Core_OptionGroup::values('campaign_type');
    }
    return self::$campaignType;
  }

  /**
   * Get all campaign status.
   *
   * The static array campaignStatus is returned
   *
   * @access public
   * @static
   *
   * @return array - array reference of all campaign status.
   *
   */
  public static function &campaignStatus() {
    if (!self::$campaignStatus) {
      require_once 'CRM/Core/OptionGroup.php';
      self::$campaignStatus = CRM_Core_OptionGroup::values('campaign_status');
    }
    return self::$campaignStatus;
  }
}

