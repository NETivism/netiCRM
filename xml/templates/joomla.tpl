<?xml version="1.0" encoding="utf-8"?>
<install method="upgrade" type="component" version="1.5">
  <name>CiviCRM</name>
  <creationDate>{$creationDate}</creationDate>
  <copyright>(C) CiviCRM LLC</copyright>
  <author>CiviCRM LLC</author>
  <authorEmail>info@civicrm.org</authorEmail>
  <authorUrl>civicrm.org</authorUrl>
  <version>{$CiviCRMVersion}</version>
  <description>CiviCRM</description>
  <files folder="site">
	  <filename>civicrm.php</filename>
	  <filename>civicrm.html.php</filename>
	  <folder>views</folder>
  </files>
  <install>
    <queries>
    </queries>
  </install>
  <uninstall>
      <queries>
      </queries>
  </uninstall>
  <installfile>install.civicrm.php</installfile>
  <uninstallfile>uninstall.civicrm.php</uninstallfile>
  <administration>
    <menu task="civicrm/dashboard&amp;reset=1">CiviCRM</menu>
    <files folder="admin">
      <filename>admin.civicrm.php</filename>
      <filename>toolbar.civicrm.php</filename>
      <filename>toolbar.civicrm.html.php</filename>
      <filename>configure.php</filename>
{if $pkgType eq 'alt'}
      <folder>civicrm</folder>
{else}
      <filename>civicrm.zip</filename>
{/if}
    </files>
  </administration>
</install>
