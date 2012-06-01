This module only secure when uncheck "View All Contacts" AND "Edit All Contacts" in Drupal Permissions for the user role.

CONFIGURATION
For the secure reason, we will suggest you can follow steps below to setup this module:

1. Disable permissions "View All Contacts" AND "Edit All Contacts" in all roles.
2. Setup settings.php to config secure civicrm group id (eg. civicrm_secure_group_gid) and allowed drupal role id (civicrm_secure_group_rid)
for example, you can setup these two line in settings.php
  $conf['civicrm_secure_group_gid'] = 4;      // civicrm group id
  $conf['civicrm_secure_group_rid'] = 9;      // drupal role id
  $conf['civicrm_secure_group_reverse'] = 0;  // Do you need to hide all other group for roles?

3. Use user 1 to assign a user to allowed role
4. Now, do more test, to see if the module work correctly
  - Login with user with allowed role, search a contact in secure group, visit group admin page, visit drupal user role assign page.
  - Login with user without allowed role, search a contact in secure group, visit group admin page, visit drupal user role assign page. When user without allowed role, should not see any contacts when they have secure group, should not see any secure group in admin page, should not see allowed role listed in drupal user page.
  - Login with user 1, you can assign any user to allowed role. But only if user 1 in the allowed role, user 1 can see contacts in secure group.
