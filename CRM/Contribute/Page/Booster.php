<?php
class CRM_Contribute_Page_Booster extends CRM_Core_Page {
  function run() {
    CRM_Utils_System::setTitle(ts('Contribution Booster'));
    $mainCategories = [
      ts('Connect Exists Donors') => [
        [
          'id' => 'first-time-donor',
          'class' => 'mdl-card-theme',
          'title' => ts('First time donation donors'),
          'description' => ts('Send some message to these people who have first time donation to you. This should be first step to maintain your relationship with these donors.'),
          'link' => CRM_Utils_System::url('civicrm/search/FirstTimeDonor', 'force=1'),
        ],
        [
          'id' => 'half-year-donor',
          'class' => 'mdl-card-theme',
          'title' => ts('Donor who donate in last %count month', ['count' => 6, 'plural' => 'Donor who donate in last %count months']),
          'description' => ts('You can send some result or impact of your project to them. And don\'t forget to exclude them in donation letter.'),
          'link' => CRM_Utils_System::url('civicrm/search/HalfYearDonor', 'force=1'),
        ],
        [
          'id' => 'failed-no-further-donate',
          'class' => 'mdl-card-theme',
          'title' => ts('After payment failed but not retry in %1 days', [1 => '7']),
          'description' => ts('They may not have enough motivation to complete donation. Instead of contriubtion campaign, send some impact or result of your effort to them.'),
          'link' => CRM_Utils_System::url('civicrm/search/FailedNoFurtherDonate', 'force=1'),
        ],
        [
          'id' => 'recur-donor',
          'class' => 'mdl-card-theme',
          'title' => ts('Recurring Donors Search'),
          'description' => ts('Explore your past, present or future recurring donors to support your organization.'),
          'link' => CRM_Utils_System::url('civicrm/search/RecurDonor'),
        ],
        [
          'id' => 'birthdate-search',
          'class' => 'mdl-card-theme',
          'title' => ts('Birth Date Search'),
          'description' => ts("Birthday is a big blessing day for supporters, don't forget to offer your blessings, or invite supporters to call friends to praise beloved organization!"),
          'link' => CRM_Utils_System::url('civicrm/search/UpcomingBirthdays'),
        ],
      ],
      ts('Potential Donors') => [
        [
          'id' => 'contrib-sybnt',
          'class' => 'mdl-card-theme',
          'title' => ts('Last year but not this year donors'),
          'description' => ts('These supporter have donation last year, but not in this year. Remember to connect them when you have some donation campaign.'),
          'link' => CRM_Utils_System::url('civicrm/search/ContribSYBNT', 'force=1'),
        ],
        [
          'id' => 'single-not-recurring',
          'class' => 'mdl-card-theme',
          'title' => ts('Single donation over %1 times', [1 => '3']),
          'description' => ts('These supporter appeal they are interested in your orgnization. You should invite them to join your recurring campaign.'),
          'link' => CRM_Utils_System::url('civicrm/search/SingleNotRecurring', 'force=1'),
        ],
        [
          'id' => 'recur-search',
          'class' => 'mdl-card-theme',
          'title' => ts('End of recurring contribution'),
          'description' => ts('These supporter will finished their promised recurring contribution. Time to invite them join your next recurring campaign again.'),
          'link' => CRM_Utils_System::url('civicrm/search/RecurSearch', 'mode=booster&force=1'),
        ],
        [
          'id' => 'attendee-not-donor',
          'class' => 'mdl-card-theme',
          'title' => ts('Attendee but not donor'),
          'description' => ts('They join your event, but not become your donor yet. Time to try to invite them.'),
          'link' => CRM_Utils_System::url('civicrm/search/AttendeeNotDonor', 'force=1'),
        ],
      ],
    ];
    $this->assign('main_categories', $mainCategories);
    parent::run();
  }

  static function setBreadcrumb() {
    CRM_Utils_System::appendBreadCrumb([
      0 => [
        'title' => ts('Contribution Booster'),
        'url' => CRM_Utils_System::url('civicrm/contribute/booster'),
      ]
    ]);
  }
}
