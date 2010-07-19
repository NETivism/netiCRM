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
{* This template is used for adding/configuring Payment Processors used by a particular site/domain.  *}
<div class="form-item">
<fieldset><legend>{if $action eq 1}{ts}New Payment Processor{/ts}{elseif $action eq 2}{ts}Edit Payment Processor{/ts}{else}{ts}Delete Payment Processor{/ts}{/if}</legend>

{if $action eq 8}
  <div class="messages status">
    <dl>
      <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}" /></dt>
      <dd>    
        {ts}WARNING: Deleting this Payment Processor may result in some transaction pages being rendered inactive.{/ts} {ts}Do you want to continue?{/ts}
      </dd>
    </dl>
  </div>
{else}
  <dl>
    <dt>{$form.payment_processor_type.label}</dt><dd>{$form.payment_processor_type.html} {help id='proc-type'}</dd>
    <dt>{$form.name.label}</dt><dd>{$form.name.html}</dd>
    <dt>{$form.description.label}</dt><dd>{$form.description.html}</dd>
    <dt>&nbsp;</dt><dd>{$form.is_active.html} {$form.is_active.label}</dd>
    <dt>&nbsp;</dt><dd>{$form.is_default.html} {$form.is_default.label}</dd>
  </dl>
<fieldset>
<legend>{ts}Processor Details for Live Payments{/ts}</legend>
    <dt>{$form.user_name.label}</dt><dd>{$form.user_name.html} {help id=$ppType|cat:'-live-user-name'}</dd>
{if $form.password}
    <dt>{$form.password.label}</dt><dd>{$form.password.html} {help id=$ppType|cat:'-live-password'}</dd>
{/if}
{if $form.signature}
    <dt>{$form.signature.label}</dt><dd>{$form.signature.html} {help id=$ppType|cat:'-live-signature'}</dd>
{/if}
{if $form.subject}
    <dt>{$form.subject.label}</dt><dd>{$form.subject.html}</dd>
{/if}
    <dt>{$form.url_site.label}</dt><dd>{$form.url_site.html|crmReplace:class:huge} {help id=$ppType|cat:'-live-url-site'}</dd>
{if $form.url_api}
    <dt>{$form.url_api.label}</dt><dd>{$form.url_api.html|crmReplace:class:huge} {help id=$ppType|cat:'-live-url-api'}</dd>
{/if}
{if $is_recur}
    <dt>{$form.url_recur.label}</dt><dd>{$form.url_recur.html|crmReplace:class:huge} {help id=$ppType|cat:'-live-url-recur'}</dd>
{/if}
{if $form.url_button}
    <dt>{$form.url_button.label}</dt><dd>{$form.url_button.html|crmReplace:class:huge} {help id=$ppType|cat:'-live-url-button'}</dd>
{/if}
</fieldset>

<fieldset>
<legend>{ts}Processor Details for Test Payments{/ts}</legend>
    <dt>{$form.test_user_name.label}</dt><dd>{$form.test_user_name.html} {help id=$ppType|cat:'-test-user-name'}</dd>
{if $form.test_password}
    <dt>{$form.test_password.label}</dt><dd>{$form.test_password.html} {help id=$ppType|cat:'-test-password'}</dd>
{/if}
{if $form.test_signature}
    <dt>{$form.test_signature.label}</dt><dd>{$form.test_signature.html} {help id=$ppType|cat:'-test-signature'}</dd>
{/if}
{if $form.test_subject}
    <dt>{$form.test_subject.label}</dt><dd>{$form.test_subject.html}</dd>
{/if}
    <dt>{$form.test_url_site.label}</dt><dd>{$form.test_url_site.html|crmReplace:class:huge} {help id=$ppType|cat:'-test-url-site'}</dd>
{if $form.test_url_api}
    <dt>{$form.test_url_api.label}</dt><dd>{$form.test_url_api.html|crmReplace:class:huge} {help id=$ppType|cat:'-test-url-api'}</dd>
{/if}
{if $is_recur}
    <dt>{$form.test_url_recur.label}</dt><dd>{$form.test_url_recur.html|crmReplace:class:huge} {help id=$ppType|cat:'-test-url-recur'}</dd>
{/if}
{if $form.test_url_button}
    <dt>{$form.test_url_button.label}</dt><dd>{$form.test_url_button.html|crmReplace:class:huge} {help id=$ppType|cat:'-test-url-button'}</dd>
{/if}
</fieldset>

{/if}
  <dl> 
    <dt></dt><dd>{$form.buttons.html}</dd>
  </dl> 
</fieldset>
</div>

{if $action eq 1  or $action eq 2}
<script type="text/javascript" >
{literal}
    function reload(refresh) {
        var paymentProcessorType = document.getElementById("payment_processor_type");
        var url = {/literal}"{$refreshURL}"{literal}
        var post = url + "&pp=" + paymentProcessorType.value;
        if( refresh ) {
            window.location= post; 
        }
    }
{/literal}
    </script>

{/if}
