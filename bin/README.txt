This file should only seen by localhost.

Because of civicrm default cronjob file can be execute from outsider, we highly recommand using apache to deny the whole directory of bin.

But after that, how to setup a cron job?

You should use php-cli command line tool to running civicrm cronjob here.

The php cli command should called like this:
php <path to civicrm>/bin/civicrm_cron_cli.php site=<domain name without http> function=<file name list at bin/cron/*.inc> --force

Example usage - update membership status
$ php <path to civicrm>/bin/civicrm_cron_cli.php site=<domain name without http> function=run_membership_status_update --force

Example usage - update membership in time range (without --force)
$ php <path to civicrm>/bin/civicrm_cron_cli.php site=<domain name without http> function=run_membership_status_update

Example usage - run civimail mass mailing
$ php <path to civicrm>/bin/civicrm_cron_cli.php site=example.com function=run_civimail --force

Example usage - run civimail process incoming message
$ php <path to civicrm>/bin/civicrm_cron_cli.php site=example.com function=run_civimail_process --force

