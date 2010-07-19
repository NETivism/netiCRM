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
    <fieldset><legend>{ts}Contribution Page{/ts}</legend>
    <dl>
    <dt>{$form.intro_text.label}</dt><dd>{$form.intro_text.html}</dd>
    <dt>{$form.amount.label}</dt><dd>{$form.amount.html}</dd>
{if $is_allow_other_amount}
    <dt>{$form.amount_other.label}</dt><dd>{$form.amount_other.html}</dd>
{/if}
    <dt>{$form.email.label}</dt><dd>{$form.email.html}</dd>
{include file="CRM/UF/Form/Block.tpl" fields=$customPre}
    <dt></dt><dd>{$form._qf_Preview_next_express.html}</dd>
    <dt>{$form.first_name.label}</dt><dd>{$form.first_name.html}</dd>
    <dt>{$form.middle_name.label}</dt><dd>{$form.middle_name.html}</dd>
    <dt>{$form.last_name.label}</dt><dd>{$form.last_name.html}</dd>
    <dt>{$form.street1.label}</dt><dd>{$form.street1.html}</dd>
    <dt>{$form.city.label}</dt><dd>{$form.city.html}</dd>
    <dt>{$form.state_province.label}</dt><dd>{$form.state_province.html}</dd>
    <dt>{$form.postal_code.label}</dt><dd>{$form.postal_code.html}</dd>
    <dt>{$form.country_id.label}</dt><dd>{$form.country_id.html}</dd>
    <dt>{$form.credit_card_number.label}</dt><dd>{$form.credit_card_number.html}</dd>
    <dt>{$form.cvv2.label}</dt><dd>{$form.cvv2.html}</dd>
    <dt>{$form.credit_card_type.label}</dt><dd>{$form.credit_card_type.html}</dd>
    <dt>{$form.credit_card_exp_date.label}</dt><dd>{$form.credit_card_exp_date.html}</dd>
{include file="CRM/UF/Form/Block.tpl" fields=$customPost}

    <div id="crm-submit-buttons">
      <dt></dt><dd>{$form.buttons.html}</dd>
    </div>

    </dl>
    </fieldset>
</div>