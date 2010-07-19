{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
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
*}
<div class="form-item">
<fieldset><legend>{ts}Update Directory Path and URL{/ts}</legend>
<div id="help">
    <p>
    {ts}Use this form if you need to reset the Base Directory Path and Base URL settings for your CiviCRM installation. These settings are stored in the database, and generally need adjusting after moving a CiviCRM installation to another location in the file system and/or to another URL.{/ts}</p>
    <p>
    {ts}CiviCRM will attempt to detect the new values that should be used. These are provided below as the default values for the <strong>New Base Directory</strong> and <strong>New Base URL</strong> fields.{/ts}</p>
</div>    
        <dl>
            <dt>{ts}Old Base Directory{/ts}</dt><dd>{$oldBaseDir}</dd>
            <dt>{$form.newBaseDir.label}</dt><dd>{$form.newBaseDir.html|crmReplace:class:'huge'}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}For Drupal installs, this is the absolute path to the location of the 'files' directory. For Joomla installs this is the absolute path to the location of the 'media' directory.{/ts}</dd>
            <dt>{ts}Old Base URL{/ts}</dt><dd>{$oldBaseURL}</dd>
            <dt>{$form.newBaseURL.label}</dt><dd>{$form.newBaseURL.html|crmReplace:class:'huge'}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}This is the URL for your Drupal or Joomla site URL (e.g. http://www.mysite.com/drupal/).{/ts}</dt>
{if $oldSiteName}
            <dt>{ts}Old Site Name{/ts}</dt><dd>{$oldSiteName}</dd>
            <dt>{$form.newSiteName.label}</dt><dd>{$form.newSiteName.html|crmReplace:class:'huge'}</dd>
            <dt>&nbsp;</dt><dd class="description">{ts}This is the your site name for a multisite install.{/ts}</dt>
{/if}
            <dt></dt><dd>{$form.buttons.html}</dd>
        </dl>
   
<div class="spacer"></div>
</fieldset>
</div>
