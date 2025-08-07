{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
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
<h3>{if $action eq 1}{ts}New Payment Processor{/ts}{elseif $action eq 2}{ts}Edit Payment Processor{/ts}{else}{ts}Delete Payment Processor{/ts}{/if}</h3>
<div class="crm-block crm-form-block crm-paymentProcessor-form-block">
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>

{if $action eq 8}
  <div class="messages status">  
       
        {ts}WARNING: Deleting this Payment Processor may result in some transaction pages being rendered inactive.{/ts} {ts}Do you want to continue?{/ts}
  </div>
{else}
  <table class="form-layout-compressed">
    <tr class="crm-paymentProcessor-form-block-payment_processor_type">
        <td class="label">{$form.payment_processor_type.label}</td><td>{$form.payment_processor_type.html} {help id='proc-type'}</td>
    </tr>
    <tr class="crm-paymentProcessor-form-block-name">
        <td class="label">{$form.name.label}</td><td>{$form.name.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-form-block-description">
        <td class="label">{$form.description.label}</td><td>{$form.description.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-form-block-is_active">
        <td></td><td>{$form.is_active.html}&nbsp;{$form.is_active.label}</td>
    </tr>
    <tr class="crm-paymentProcessor-form-block-is_default">
        <td></td><td>{$form.is_default.html}&nbsp;{$form.is_default.label}</td>
    </tr>
  </table>
<fieldset>
<legend>{ts}Processor Details for Live Payments{/ts}</legend>
    <table class="form-layout-compressed">
        <tr class="crm-paymentProcessor-form-block-user_name">
            <td class="label">{$form.user_name.label}</td><td>{$form.user_name.html} {help id=$ppType|cat:'-live-user-name'}</td>
        </tr>
{if $form.password}
        <tr class="crm-paymentProcessor-form-block-password">
            <td class="label">{$form.password.label}</td><td>{$form.password.html} {help id=$ppType|cat:'-live-password'}</td>
        </tr>
{/if}
{if $form.signature}
        <tr class="crm-paymentProcessor-form-block-signature">
            <td class="label">{$form.signature.label}</td><td>{$form.signature.html} {help id=$ppType|cat:'-live-signature'}</td>
        </tr>
{/if}
{if $form.subject}
        <tr class="crm-paymentProcessor-form-block-subject">
            <td class="label">{$form.subject.label}</td><td>{$form.subject.html}</td>
        </tr>
{/if}
        <tr class="crm-paymentProcessor-form-block-url_site">
            <td class="label">{$form.url_site.label}</td><td>{$form.url_site.html|crmReplace:class:huge} {help id=$ppType|cat:'-live-url-site'}</td>
        </tr>
{if $form.url_api}
        <tr class="crm-paymentProcessor-form-block-url_api">
            <td class="label">{$form.url_api.label}</td><td>{$form.url_api.html|crmReplace:class:huge} {help id=$ppType|cat:'-live-url-api'}</td>
        </tr>
{/if}
{if $is_recur}
        <tr class="crm-paymentProcessor-form-block-url_recur">
            <td class="label">{$form.url_recur.label}</td><td>{$form.url_recur.html|crmReplace:class:huge} {help id=$ppType|cat:'-live-url-recur'}</td>
        </tr>
{/if}
{if $form.url_button}
        <tr class="crm-paymentProcessor-form-block-url_button">
            <td class="label">{$form.url_button.label}</td><td>{$form.url_button.html|crmReplace:class:huge} {help id=$ppType|cat:'-live-url-button'}</td>
        </tr>
{/if}
    </table>        
</fieldset>

<fieldset>
<legend>{ts}Processor Details for Test Payments{/ts}</legend>
    <table class="form-layout-compressed">                      
        <tr class="crm-paymentProcessor-form-block-test_user_name">
            <td class="label">{$form.test_user_name.label}</td><td>{$form.test_user_name.html} {help id=$ppType|cat:'-test-user-name'}</td></tr>
{if $form.test_password}
        <tr class="crm-paymentProcessor-form-block-test_password">
            <td class="label">{$form.test_password.label}</td><td>{$form.test_password.html} {help id=$ppType|cat:'-test-password'}</td>
        </tr>
{/if}
{if $form.test_signature}
        <tr class="crm-paymentProcessor-form-block-test_signature">
            <td class="label">{$form.test_signature.label}</td><td>{$form.test_signature.html} {help id=$ppType|cat:'-test-signature'}</td>
        </tr>
{/if}
{if $form.test_subject}
        <tr class="crm-paymentProcessor-form-block-test_subject">
            <td class="label">{$form.test_subject.label}</td><td>{$form.test_subject.html}</td>
        </tr>
{/if}
        <tr class="crm-paymentProcessor-form-block-test_url_site">
            <td class="label">{$form.test_url_site.label}</td><td>{$form.test_url_site.html|crmReplace:class:huge} {help id=$ppType|cat:'-test-url-site'}</td>
        </tr>
{if $form.test_url_api}
        <tr class="crm-paymentProcessor-form-block-test_url_api">
            <td class="label">{$form.test_url_api.label}</td><td>{$form.test_url_api.html|crmReplace:class:huge} {help id=$ppType|cat:'-test-url-api'}</td>
        </tr>
{/if}
{if $is_recur}
        <tr class="crm-paymentProcessor-form-block-test_url_recur">
            <td class="label">{$form.test_url_recur.label}</td><td>{$form.test_url_recur.html|crmReplace:class:huge} {help id=$ppType|cat:'-test-url-recur'}</td>
        </tr>
{/if}
{if $form.test_url_button}
        <tr class="crm-paymentProcessor-form-block-test_url_button">
            <td class="label">{$form.test_url_button.label}</td><td>{$form.test_url_button.html|crmReplace:class:huge} {help id=$ppType|cat:'-test-url-button'}</td>
        </tr>
{/if}  
{/if} 
</table>
       <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
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
{if $ppType eq 'TapPay'}
    <script>
        {literal}
            (function($){
                if ($('#url_site').length) {
                    function setTapPay3DSecureOption(formBlockName) {
                        var urlSiteFieldDiv = $(formBlockName);
                        var urlSiteTextField = urlSiteFieldDiv.find('input.form-text[type="text"]');
                        urlSiteTextField.hide();
                        if (!formBlockName.match('test')) {
                            var newCheckBox = $('<input type="checkbox" {/literal}{if $having_contribution} disabled="disabled"{/if}{literal}>');
                        }
                        else {
                            var newCheckBox = $('<input type="checkbox" {/literal}{if $having_contribution_test} disabled="disabled"{/if}{literal}>');
                        }
                        newCheckBox.insertBefore(urlSiteTextField);
                        newCheckBox.change(function(){
                            if (newCheckBox.prop('checked')) {
                                urlSiteTextField.val('1');
                            }
                            else {
                                urlSiteTextField.val('');
                            }
                        });
                        // Set Label Behavior
                        var urlSiteLabel = urlSiteFieldDiv.find('label');
                        urlSiteLabel.click(function(){
                            newCheckBox.click();
                        });
                        // Set Default Value
                        if (urlSiteTextField.val()) {
                            newCheckBox.prop('checked', 1);
                        }
                        // Set instruction text
                        var urlSiteTd = urlSiteTextField.closest('td');
                        urlSiteTd.append($('<span class="description">{/literal}{ts}To enable 3D Secure, you must add a TapPay  Merchant ID and Partner Key that supports this feature. If you need 3D Secure, please contact customer service.{/ts}{literal}</span>'));
                        urlSiteFieldDiv.find('.helpicon').hide();

                        // hide url recur
                        var urlRecurFieldTr = $(formBlockName.replace('url_site', 'url_recur')).hide();
                    }

                    setTapPay3DSecureOption('.crm-paymentProcessor-form-block-url_site');
                    setTapPay3DSecureOption('.crm-paymentProcessor-form-block-test_url_site');
                }
            })(jQuery);
        {/literal}
    </script>
{/if}

{if $ppType eq 'SPGATEWAY'}
<script>{literal}
  jQuery(document).ready(function($){
    $('[class*=url_site]').hide();
    $('[class*=url_api]').hide();
    $('[class*=url_recur]').hide();
    let having_contribution = 0;
    let having_contribution_test = 0;
    {/literal}{if $having_contribution}having_contribution = 1;{/if}{literal}
    {/literal}{if $having_contribution_test}having_contribution_test = 1;{/if}{literal}
    let spgateway_processor_options_live = {/literal}{if $spgateway_processor_options_live}{$spgateway_processor_options_live}{else}{ldelim} {rdelim}{/if};{literal}
    let spgateway_processor_options_test = {/literal}{if $spgateway_processor_options_test}{$spgateway_processor_options_test}{else}{ldelim} {rdelim}{/if};{literal}

    function addApiCheckbox($element, label) {
      if (!$element.length) {
        console.error('Element not found');
        return;
      }

      const elementClass = $element[0].className || 'default-class';
      const trClass = elementClass + '-api-checkbox-wrapper';
      const inputId = elementClass + '-api-checkbox';

      const $originalUrlInput = $element.find('input').first();
      if (!$originalUrlInput.length) {
        console.error('Original input field not found within the element', $element);
        return;
      }

      // Determine if this checkbox should be disabled
      const isTestElement = (elementClass.indexOf('-test_') !== -1);
      let shouldBeDisabled = false;
      if (isTestElement && having_contribution_test) {
        shouldBeDisabled = true;
      } else if (!isTestElement && having_contribution) {
        shouldBeDisabled = true;
      }

      const $checkbox = $('<input>')
        .attr({
          'type': 'checkbox',
          'id': inputId
        })
        .prop('checked', $originalUrlInput.val() === '1');

      if (shouldBeDisabled) {
        $checkbox.prop('disabled', true);
      }

      $checkbox.on('change', function() {
        if ($(this).prop('disabled')) return;
        
        // Make checkboxes mutually exclusive
        const currentId = $(this).attr('id');
        const isApiCheckbox = currentId.includes('url_api-api-checkbox');
        const isRecurCheckbox = currentId.includes('url_recur-api-checkbox');
        
        if ($(this).is(':checked')) {
          if (isApiCheckbox) {
            // Uncheck corresponding url_recur checkbox
            const recurCheckboxId = currentId.replace('url_api-api-checkbox', 'url_recur-api-checkbox');
            $('#' + recurCheckboxId).prop('checked', false).trigger('change');
          } else if (isRecurCheckbox) {
            // Uncheck corresponding url_api checkbox
            const apiCheckboxId = currentId.replace('url_recur-api-checkbox', 'url_api-api-checkbox');
            $('#' + apiCheckboxId).prop('checked', false).trigger('change');
          }
        }
      });

      const $newRow = $('<tr>')
        .addClass(trClass)
        .append(
          $('<td>')
            .addClass('label')
            .append(
              $('<label>')
                .attr('for', inputId)
                .text(label || '')
            )
        )
        .append(
          $('<td>').append($checkbox)
        );

      $element.before($newRow);
      return $newRow;
    }
    $('fieldset [class^="crm-paymentProcessor-"][class$="url_recur"]').each(function(i, element) {
      const $subject = $(this).closest('table').find('[class^="crm-paymentProcessor-"][class$=subject]');
      $subject.hide();
      addApiCheckbox($(this), '{/literal}{ts}Enable Neweb Recurring API{/ts}{literal}');
    });
    function createUrlSiteSelect($urlSiteElement) {
      const $originalInput = $urlSiteElement.find('input').first();
      const currentValue = $originalInput.val();
      const elementClass = $urlSiteElement[0].className || 'default-class';
      const selectId = elementClass + '-select';
      const isTestElement = (elementClass.indexOf('-test_') !== -1);

      // Hide the original input
      $originalInput.hide();

      // Create select dropdown
      const $select = $('<select>')
        .attr('id', selectId)
        .addClass('form-select');

      // Determine which options to use based on test/live
      const options = isTestElement ? spgateway_processor_options_test : spgateway_processor_options_live;
      
      // Add options to select
      if (options) {
        for (let value in options) {
          if (options.hasOwnProperty(value)) {
            const $option = $('<option>')
              .attr('value', value)
              .text(options[value]);
            $select.append($option);
          }
        }
      }
      
      // Set current value
      $select.val(currentValue);

      // Sync select value with hidden input
      $select.on('change', function() {
        $originalInput.val($(this).val());
      });

      $select.insertAfter($originalInput);

      return $select;
    }

    {/literal}{if $enableSPGatewayAgreement}{literal}
    $('fieldset [class^="crm-paymentProcessor-"][class$="url_api"]').each(function(i, element) {
      addApiCheckbox($(this), '{/literal}{ts}Credit Card Agreement{/ts}{literal}');
      const $subject = $(this).closest('table').find('[class^="crm-paymentProcessor-"][class$=subject]');
      const $urlSite = $(this).closest('table').find('[class^="crm-paymentProcessor-"][class$=url_site]');
      
      // Create select for url_site
      const $urlSiteSelect = createUrlSiteSelect($urlSite);
      
      $subject.insertAfter($(this));
      $urlSite.insertAfter($subject);
      $subject.hide();
      $urlSite.hide();
      
      const $apiCheckbox = $(this).closest('table').find('input[id$=url_api-api-checkbox]');
      if ($apiCheckbox.prop('checked')) {
        $subject.show();
        $urlSite.show();
      }
      $apiCheckbox.change(function(){
        if ($(this).prop('checked')) {
          $subject.show();
          $urlSite.show();
        }
        else {
          $subject.hide();
          $urlSite.hide();
        }
      });
    });
    {/literal}{/if}{literal}
  });
{/literal}</script>
{/if}