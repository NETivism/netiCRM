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
{* this template is used for adding/editing available Payment Processors  *}
<div class="form-item">
<fieldset><legend>{if $action eq 1}{ts}New Payment Procesor Type{/ts}{elseif $action eq 2}{ts}Edit Payment Procesor Type{/ts}{else}{ts}Delete Payment Procesor Type{/ts}{/if}</legend>

{if $action eq 8}
  <div class="messages status">
    <dl>
      <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
      <dd>    
        {ts}Do you want to continue?{/ts}
      </dd>
    </dl>
  </div>
{else}
  <dl>
    <dt>{$form.title.label}</dt><dd>{$form.title.html}</dd>
    <dt>{$form.name.label}</dt><dd>{$form.name.html}</dd>
    <dt>{$form.description.label}</dt><dd>{$form.description.html}</dd>
    <dt>{$form.billing_mode.label}</dt><dd>{$form.billing_mode.html}</dd>
    <dt>{$form.class_name.label}</dt><dd>{$form.class_name.html}</dd>
    <dt>&nbsp;</dt><dd>{$form.is_active.html} {$form.is_active.label}</dd>
    <dt>&nbsp;</dt><dd>{$form.is_default.html} {$form.is_default.label}</dd>
    <dt>&nbsp;</dt><dd>{$form.is_recur.html} {$form.is_recur.label}</dd>
    <dt>{$form.user_name_label.label}</dt><dd>{$form.user_name_label.html}</dd>
    <dt>{$form.password_label.label}</dt><dd>{$form.password_label.html}</dd>
    <dt>{$form.signature_label.label}</dt><dd>{$form.signature_label.html}</dd>
    <dt>{$form.subject_label.label}</dt><dd>{$form.subject_label.html}</dd>
    <dt>{$form.url_site_default.label}</dt><dd>{$form.url_site_default.html}</dd>
    <dt>{$form.url_api_default.label}</dt><dd>{$form.url_api_default.html}</dd>
    <dt>{$form.url_recur_default.label}</dt><dd>{$form.url_recur_default.html}</dd>
    <dt>{$form.url_button_default.label}</dt><dd>{$form.url_button_default.html}</dd>
    <dt>{$form.url_site_test_default.label}</dt><dd>{$form.url_site_test_default.html}</dd>
    <dt>{$form.url_api_test_default.label}</dt><dd>{$form.url_api_test_default.html}</dd>
    <dt>{$form.url_recur_test_default.label}</dt><dd>{$form.url_recur_test_default.html}</dd>
    <dt>{$form.url_button_test_default.label}</dt><dd>{$form.url_button_test_default.html}</dd>
</dl>
{/if}
  <dl> 
    <dt></dt><dd>{$form.buttons.html}</dd>
  </dl> 
</fieldset>
</div>
