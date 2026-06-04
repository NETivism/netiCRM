<?php

class CRM_Activity_PseudoConstant extends CRM_Core_PseudoConstant {

  /**
   * Get all Activity Statuses.
   *
   * The static array activityStatus is returned
   *
   * @param string $column
   *
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
   * @param bool $all - get All Activity  types - default is to get only active ones.
   * @param bool $includeCaseActivities
   * @param bool $reset
   * @param string $returnColumn
   * @param bool $includeCampaignActivities
   *
   *
   * @return array - array reference of all activty types.
   */
  public static function &activityType(
    $all = TRUE,
    $includeCaseActivities = FALSE,
    $reset = FALSE,
    $returnColumn = 'label',
    $includeCampaignActivities = FALSE
  ) {
    return parent::activityType($all, $includeCaseActivities, $reset, $returnColumn, $includeCampaignActivities);
  }
}
