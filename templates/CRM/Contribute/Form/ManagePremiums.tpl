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
{* this template is used for adding/editing/deleting premium  *}
<div class="crm-block crm-form-block crm-contribution-manage_premium-form-block">
<fieldset><legend>{if $action eq 1}{ts}New Premium{/ts}{elseif $action eq 2}{ts}Edit Premium{/ts}{elseif $action eq 1024}{ts}Preview a Premium{/ts}{else}{ts}Delete Premium Product{/ts}{/if}</legend>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
   {if $action eq 8}
      <div class="messages status">
          
          {ts}Are you sure you want to delete this premium?{/ts} {ts}This action cannot be undone.{/ts} {ts}This will also remove the premium from any contribution pages that currently include it.{/ts}
      </div>
  {elseif $action eq 1024}
     {include file="CRM/Contribute/Form/Contribution/PremiumBlock.tpl" context="previewPremium"}
  {else}  
  <table class="form-layout-compressed">
    <tr class="crm-contribution-form-block-is_active">
       <td class="label">{$form.is_active.label}</td>
       <td class="html-adjust">{$form.is_active.html}</td>
    </tr>
    <tr class="crm-contribution-form-block-name">
      <td class="label">{$form.name.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_product' field='name' id=$productId}{/if}
      </td>
      <td class="html-adjust">{$form.name.html}<br />
        <span class="description">{ts}Name of the premium (product, service, subscription, etc.) as it will be displayed to contributors.{/ts}</span>
      </td>
     </tr>
     <tr>
        <td class="label">{$form.description.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_product' field='description' id=$productId}{/if}
        </td>
        <td class="html-adjust">{$form.description.html}
        </td>
          <tr class="crm-contribution-form-block-sku">
              <td class="label">{$form.sku.label}
        </td>
        <td class="html-adjust">{$form.sku.html}<br />
          <span class="description">{ts}Optional product SKU or code. If used, this value will be included in contributor receipts.{/ts}</span>
        </td>
     </tr>
     <tr class="crm-contribution-form-block-imageOption" >
      <td colspan="2">
  <div class="crm-accordion-wrapper crm-accordion-open" id="premium-image">
    <div class="crm-accordion-header">
      <div class="zmdi crm-accordion-pointer"></div>{$form.imageOption.label}
    </div>
    <div class="crm-accordion-body">
    	<div class="description">
        <p>{ts}You can upload an image from your computer OR enter a URL for an image already on the Web. If you chose to upload an image file, a 'thumbnail' version will be automatically created for you. If you don't have an image available at this time, you may also choose 'Do not display an image' option.{/ts}</p>
        <p>{ts}Image must be in GIF, JPEG, or PNG format.{/ts} {ts 1="480x480"}Image will be resized to %1 pixels.{/ts}</p>
      </div>
      <table class="form-layout-compressed">
        {if $thumbnailUrl}<tr class="odd-row"><td class="describe-image" colspan="2"><strong>{ts}Current Image Thumbnail{/ts}</strong><br /><img src="{$thumbnailUrl}" /></td></tr>{/if}
        <tr class="crm-contribution-form-block-imageOption"><td>{$form.imageOption.image.html}</td><td>{$form.uploadFile.html}</td></tr>
        <tr class="crm-contribution-form-block-imageOption-thumbnail"><td colspan="2">{$form.imageOption.thumbnail.html}</td></tr>
        <tr id="imageURL"{if $action eq 2}class="show-row" {else} class="hide-row" {/if}>
            <td class="label">{$form.imageUrl.label}</td><td>{$form.imageUrl.html|crmReplace:class:huge}</td>
        </tr>
        <tr id="thumbnailURL"{if $action eq 2}class="show-row" {else} class="hide-row" {/if}>
            <td class="label">{$form.thumbnailUrl.label}</td><td>{$form.thumbnailUrl.html|crmReplace:class:huge}</td>
        </tr>
        <tr><td colspan="2">{$form.imageOption.default_image.html}</td></tr>
        <tr><td colspan="2">{$form.imageOption.noImage.html}</td></tr>
      </table>
    </div><!--Accordion Body-->
  </div>
      </td>
    </tr>
    <tr>
      <td colspan="2">
  <div class="crm-accordion-wrapper crm-accordion-open" id="minimum-contribution-amount">
    <div class="crm-accordion-header">
      <div class="zmdi crm-accordion-pointer"></div>{ts}Minimum Contribution Amount{/ts}
    </div>
    <div class="crm-accordion-body">
    <table class="form-layout-compressed">
      <tr class="crm-contribution-form-block-min_contribution">
        <td class="label">{$form.min_contribution.label}</td>
        <td class="html-adjust">{ts}Min Contribution{/ts} {$form.min_contribution.html}</td>
      </tr>
      <tr class="crm-contribution-form-block-calculate_mode">
        <td class="label"><label>{ts}Recurring Contribution{/ts} - {ts}Threshold{/ts} <span class="crm-marker">*</span></label></td>
        <td class="html-adjust">
          <table class="form-layout-compressed">
            <tr>
              <td class="html-adjust">
                <div class="calculate-mode">{$form.calculate_mode.html}</div>
                <div class="min-contribution-recur hiddenElement">
                  <label class="mode-first hiddenElement">{ts}Min contribution of first time donation{/ts}</label>
                  <label class="mode-cumulative hiddenElement" >{ts}Grand Total of recurring amount{/ts}</label>
                  {$form.min_contribution_recur.html}
                </div>
                <div class="installments hiddenElement">
                  <div>{ts}When donor do specify installment, calculate total amount by: amount per installment x donor choose installments{/ts}</div>
                </div>
                <div class="installments hiddenElement">
                  <div>{ts}When donor doesn't specify installment, calculate total amount by: amount per installment x estimate installments{/ts} {$form.installments.html} {ts}installments{/ts} </div>
                </div>
              </td>
            </tr>
          </table>
        </td>
    </table>
    </div><!--Accordion Body-->
  </div>
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <div class="crm-accordion-wrapper crm-accordion-open" id="inventory-management">
          <div class="crm-accordion-header">
            <div class="zmdi crm-accordion-pointer"></div>{ts}Inventory Management{/ts}
          </div>
          <div class="crm-accordion-body">
            <table class="form-layout-compressed">
              <tr class="crm-contribution-form-block-stock_status">
                <td class="label">{$form.stock_status.label}</td>
                <td class="html-adjust">{$form.stock_status.html}</td>
              </tr>
              <tr class="crm-contribution-form-block-stock_qty">
                <td class="label">{$form.stock_qty.label}</td>
                <td class="html-adjust">{$form.stock_qty.html}<br />
                    <span class="description">
                      {ts}Set the total inventory quantity for this premium. Once the given quantity exceeds this total, this premium will no longer be offered.{/ts}<br>
                      {ts}Note: Inventory is tied to the entire premium. If you need to manage inventory for different options, please create multiple premiums.{/ts}
                    </span>
                </td>
              </tr>
            </table>
          </div><!--Accordion Body-->
        </div>
      </td>
    </tr>
    <tr>
    <td colspan="2">
  <div class="crm-accordion-wrapper crm-accordion-open" id="other">
    <div class="crm-accordion-header">
      <div class="zmdi crm-accordion-pointer"></div>{ts}Other{/ts}
    </div>
    <div class="crm-accordion-body">
    <table class="form-layout-compressed">
      <tr class="crm-contribution-form-block-price">
        <td class="label">{$form.price.label}</td>
        <td class="html-adjust">{$form.price.html}<br />
      <span class="description">{ts}To estimate the cost of fundraising, please fill in the market price of this premium. The market price you filled in will not affect the total amount displayed on the contribution record and receipt.{/ts}</span>
        </td> 
      </tr>
      <tr class="crm-contribution-form-block-cost">
        <td class="label">{$form.cost.label}</td>
        <td class="html-adjust">{$form.cost.html}<br />
          <span class="description">{ts}You may optionally record the actual cost of this premium to your organization. This may be useful when evaluating net return for this incentive.{/ts}</span>
        </td>
      </tr>
      <tr class="crm-contribution-form-block-options">
        <td class="label">{$form.options.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_product' field='options' id=$productId}{/if}</td>
        <td class="html-adjust">{$form.options.html}<br />
          <span class="description">{ts}Enter a comma-delimited list of color, size, etc. options for the product if applicable. Contributors will be presented a drop-down menu of these options when they select this product.{/ts}</span>
        </td>
      </tr>
    </table>
    </div><!--Accordion Body-->
  </div>
    </td>
    </tr>
  </table>
  <div class="crm-accordion-wrapper crm-accordion-closed" id="time-delimited">
    <div class="crm-accordion-header">
      <div class="zmdi crm-accordion-pointer"></div>{ts}Subscription or Service Settings{/ts}
    </div>
    <div class="crm-accordion-body">
  <table class="form-layout-compressed">
    <tr class="crm-contribution-form-block-period_type">
       <td class="label">{$form.period_type.label}</td>
       <td class="html-adjust">{$form.period_type.html}<br />
          <span class="description">{ts}Select 'Rolling' if the subscription or service starts on the current day. Select 'Fixed' if the start date is a fixed month and day within the current year (set this value in the next field).{/ts}</span>
       </td>
    </tr>
    <tr class="crm-contribution-form-block-fixed_period_start_day">
       <td class="label">{$form.fixed_period_start_day.label}</td>
       <td class="html-adjust">{$form.fixed_period_start_day.html}<br />
          <span class="description">{ts}Month and day (MMDD) on which a fixed period subscription or service will start. EXAMPLE: A fixed period subscription with Start Day set to 0101 means that the subscription period would be 1/1/06 - 12/31/06 for anyone signing up during 2006.{/ts}</span>
       </td>
    </tr>
    <tr class="crm-contribution-form-block-duration_interval">
       <td class="label">{$form.duration_interval.label}</td>
       <td class="html-adjust">{$form.duration_interval.html} &nbsp; {$form.duration_unit.html}<br />
          <span class="description">{ts}Duration of subscription or service (e.g. 12-month subscription).{/ts}</span>
       </td>
    </tr>
    <tr class="crm-contribution-form-block-frequency_interval">
       <td class="label">{$form.frequency_interval.label}</td>
       <td class="html-adjust">{$form.frequency_interval.html} &nbsp; {$form.frequency_unit.html}<br />
          <span class="description">{ts}Frequency of subscription or service (e.g. journal delivered every two months).{/ts}</span> 
    </td>
    </tr>
  </table>
    </div><!--Accordion Body-->
	</div>	
 {/if}
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</fieldset>
<div>
{if $action eq 1 or $action eq 2 }		 

<script type="text/javascript">
{literal}

function add_upload_file_block(parms) {
	if (parms =='thumbnail') {
	      
          document.getElementById("imageURL").style.display="table-row";                    
	      document.getElementById("thumbnailURL").style.display="table-row";
	   
	} else {

	      document.getElementById("imageURL").style.display="none";    
	      document.getElementById("thumbnailURL").style.display="none";
	   	
	}	
}

function select_option() {
  cj('[name="imageOption"][value="image"]').prop('checked',true);
  add_upload_file_block('image');
}

cj(document).ready(function($){
  $().crmaccordions(); 
  var checkMode = function() {
    $("div.min-contribution-recur, div.installments").css('margin-left', '50px');
    var $checked = $("input[name=calculate_mode]:checked");
    var mode = $checked.val();
    $("div.min-contribution-recur").insertAfter($checked.closest('.crm-form-radio'));
    if (mode == 'first') {
      $("div.min-contribution-recur").removeClass('hiddenElement');
      $('.mode-first').removeClass('hiddenElement');
      $('.mode-cumulative').addClass('hiddenElement');
      $('div.installments').addClass('hiddenElement');
    }
    else if (mode == 'cumulative') {
      $("div.min-contribution-recur").removeClass('hiddenElement');
      $('.mode-first').addClass('hiddenElement');
      $('.mode-cumulative').removeClass('hiddenElement');
      $('div.installments').removeClass('hiddenElement');
    }
  }
  $("input[name=calculate_mode]").click(function() {
    checkMode();
  });
  checkMode();
});
{/literal}
</script>

{/if}
</div>
