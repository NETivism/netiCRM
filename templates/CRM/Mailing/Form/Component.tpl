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
{* this template is used for adding/editing a mailing component  *}
<div class="form-item">
<fieldset><legend>{if $action eq 1}{ts}New Mailing Component{/ts}{else}{ts}Edit Mailing Component{/ts}{/if}</legend>
  <dl class="html-adjust">
    <dt>{$form.name.label}</dt><dd>{$form.name.html}</dd>
    <dt>{$form.component_type.label}</dt><dd>{$form.component_type.html}</dd>
    <dt>{$form.subject.label}</dt><dd>{$form.subject.html}</dd>
    <dt>{$form.body_text.label}</dt><dd>{$form.body_text.html}</dd>
    <dt>{$form.body_html.label}</dt><dd>{$form.body_html.html}</dd>
    <dt>{$form.is_default.label}</dt><dd>{$form.is_default.html}</dd>
    <dt>{$form.is_active.label}</dt><dd>{$form.is_active.html}</dd>
    <dt></dt><dd>{$form.buttons.html}</dd>
  </dl>
</fieldset>
</div>
