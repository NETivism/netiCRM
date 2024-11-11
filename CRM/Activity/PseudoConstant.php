<?php

class CRM_Activity_PseudoConstant extends CRM_Core_PseudoConstant {

  /**
   * Get all Activity Statuses.
   *
   * The static array activityStatus is returned
   *
   * @access public
   * @static
   *
   * @return array - array reference of all activity statuses
   */
  public static function &activityStatus($column = 'label') {
    return parent::activityStatus($column);
  }

  /**
   * Get all Activty types.
   *
   * The static array activityType is returned
   *
   * @param boolean $all - get All Activity  types - default is to get only active ones.
   *
   * @access public
   * @static
   *
   * @return array - array reference of all activty types.
   */
  public static function &activityType($all = TRUE,
    $includeCaseActivities = FALSE,
    $reset = FALSE,
    $returnColumn = 'label',
    $includeCampaignActivities = FALSE
  ) {
    return parent::activityType($all, $includeCaseActivities, $reset, $returnColumn, $includeCampaignActivities);
  }
}