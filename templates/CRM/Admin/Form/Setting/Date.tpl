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
<div id="help">
    {ts}Use this screen to configure formats for date display and date input fields. Defaults are provided for standard United States formats. Settings use standard POSIX specifiers.{/ts} {help id='date-format'}
</div>
<div class="form-item">
<fieldset><legend>{ts}Date Display{/ts}</legend>
     <dl>
      <dt>{$form.dateformatDatetime.label}</dt><dd>{$form.dateformatDatetime.html}</dd>
      <dt>{$form.dateformatFull.label}</dt><dd>{$form.dateformatFull.html}</dd>
      <dt>{$form.dateformatPartial.label}</dt><dd>{$form.dateformatPartial.html}</dd>
      <dt>{$form.dateformatYear.label}</dt><dd>{$form.dateformatYear.html}</dd>
      <dt>{$form.dateformatTime.label}</dt><dd>{$form.dateformatTime.html}</dd>
    </dl>
</fieldset>
<fieldset><legend>{ts}Date Input Fields{/ts}</legend>
     <dl>
      <dt>{$form.dateInputFormat.label}</dt><dd>{$form.dateInputFormat.html}</dd>
      <dt>{$form.timeInputFormat.label}</dt><dd>{$form.timeInputFormat.html}</dd>
    </dl>
    <div class="action-link">
    	<a href="{crmURL p="civicrm/admin/setting/preferences/date" q="reset=1"}" id="advDateSetting" title="{ts}Manage available date ranges and input formats for different types of date fields.{/ts}">&raquo; {ts}Advanced Date Input Settings{/ts}</a>
    </div>
</fieldset>
<fieldset>
<dt>{$form.fiscalYearStart.label}</dt><dd>{$form.fiscalYearStart.html}</dd>
</fieldset>
     <dl>
      <dt></dt><dd>{$form.buttons.html}</dd>
    </dl>
<div class="spacer"></div>
</div>
