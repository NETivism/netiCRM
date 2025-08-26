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
{* this template is used for adding/editing/deleting premium combination  *}
<div class="crm-block crm-form-block crm-contribution-manage_premium_combination-form-block">
<fieldset><legend>{if $action eq 1}{ts}New Premium Combination{/ts}{elseif $action eq 2}{ts}Edit Premium Combination{/ts}{elseif $action eq 1024}{ts}Preview Premium Combination{/ts}{else}{ts}Delete Premium Combination{/ts}{/if}</legend>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
   {if $action eq 8}
      <div class="messages status">
          {ts}Are you sure you want to delete this premium combination?{/ts} {ts}This action cannot be undone.{/ts} {ts}This will also remove the combination from any contribution pages that currently include it.{/ts}
      </div>
  {elseif $action eq 1024}
  {* TODO: preview *}
  {else}
  <table class="form-layout-compressed">
    <tr class="crm-contribution-form-block-is_active">
       <td class="label">{$form.is_active.label}</td>
       <td class="html-adjust">{$form.is_active.html}</td>
    </tr>
    <tr class="crm-contribution-form-block-combination_name">
      <td class="label">{$form.combination_name.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_premiums_combination' field='combination_name' id=$combinationId}{/if}
      </td>
      <td class="html-adjust">{$form.combination_name.html}<br />
        <span class="description">{ts}This is the name of the premium combination to be displayed to the donor.{/ts}</span>
      </td>
     </tr>
     <tr>
        <td class="label">{$form.description.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_premiums_combination' field='description' id=$combinationId}{/if}
        </td>
        <td class="html-adjust">{$form.description.html}
        </td>
     </tr>
     <tr class="crm-contribution-form-block-sku">
        <td class="label">{$form.sku.label}
        </td>
        <td class="html-adjust">{$form.sku.html}<br />
          <span class="description">{ts}Optional product SKU or code. If used, this value will be included in contributor receipts.{/ts}</span>
        </td>
     </tr>
     <tr>
      <td colspan="2">
        <div class="crm-accordion-wrapper crm-accordion-open" id="combination-products">
          <div class="crm-accordion-header">
            <div class="zmdi crm-accordion-pointer"></div>{ts}Select Combination Products{/ts}
          </div>
          <div class="crm-accordion-body">
            <div class="description">
              <p>{ts}You can select gifts to include in the combination and set the quantity for each gift.{/ts}</p>
            </div>
            <table class="form-layout-compressed" style="width: 100%; border-collapse: collapse;">
              <thead>
                <tr style="background-color: #f5f5f5;">
                  <th style="padding: 8px; border: 1px solid #ddd; text-align: center;">{ts}Premium{/ts}</th>
                  <th style="padding: 8px; border: 1px solid #ddd; text-align: center;">{ts}Quantity{/ts}</th>
                  <th style="padding: 8px; border: 1px solid #ddd; text-align: center;">{ts}Operation{/ts}</th>
                </tr>
              </thead>
              <tbody id="combination-products-table">
                {* The initial empty row is used to add a new product.*}
              </tbody>
            </table>
            <div class="action-link-button" style="margin-top: 10px;">
              <a href="#" id="add-more-products" class="button">
                <i class="zmdi zmdi-plus-circle-o"></i>{ts}Add more gifts to the combination{/ts}
              </a>
            </div>
          </div><!--Accordion Body-->
        </div>
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
              {if $thumbnailUrl}<tr class="odd-row"><td class="describe-image" colspan="2"><strong>{ts}目前的圖片縮圖{/ts}</strong><br /><img src="{$thumbnailUrl}" /></td></tr>{/if}
              <tr class="crm-contribution-form-block-imageOption"><td>{$form.imageOption.image.html}</td><td>{$form.uploadFile.html}</td></tr>
              <tr class="crm-contribution-form-block-imageOption-thumbnail"><td colspan="2">{$form.imageOption.thumbnail.html}</td></tr>
              <tr id="imageURL"{if $action eq 2}class="show-row" {else} class="hide-row" {/if}>
                  <td class="label">{$form.imageUrl.label}</td><td>{$form.imageUrl.html|crmReplace:class:huge}</td>
              </tr>
              <tr id="thumbnailURL"{if $action eq 2}class="show-row" {else} class="hide-row" {/if}>
                  <td class="label">{$form.thumbnailUrl.label}</td><td>{$form.thumbnailUrl.html|crmReplace:class:huge}</td>
              </tr>
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
            <div class="zmdi crm-accordion-pointer"></div>{ts}Min Contribution{/ts}
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
                        <div>{ts}When donor doesn't specify installment, calculate total amount by: amount per installment x estimate installments{/ts} {$form.installments.html} {ts}期{/ts} </div>
                      </div>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
          </div><!--Accordion Body-->
        </div>
      </td>
    </tr>
  </table>
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

  var productRowIndex = 0;
  $('#add-more-products').click(function(e) {
    e.preventDefault();
    // Create a product dropdown menu
    var productSelectHtml = '';
    {/literal}
    {if $availableProducts}
      productSelectHtml = '<select name="new_product_' + productRowIndex + '" style="width: 200px; padding: 4px;">' +
        '<option value="">{ts}-- select --{/ts}</option>';
      {foreach from=$availableProducts key=productId item=productName}
        productSelectHtml += '<option value="{$productId}">{$productName}</option>';
      {/foreach}
      productSelectHtml += '</select>';
    {/if}
    {literal}
    var quantityInputHtml = '<input type="text" name="new_product_quantity_' + productRowIndex + '" ' +
      'style="width: 60px; padding: 4px; text-align: center; border: 1px solid #ccc;" ' +
      'value="1" disabled maxlength="3" />';
    var newRow = '<tr class="product-row new-product-row" data-index="' + productRowIndex + '" style="border-bottom: 1px solid #ddd;">' +
      '<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' +
        productSelectHtml +
      '</td>' +
      '<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' +
        quantityInputHtml +
      '</td>' +
      '<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' +
        '<button type="button" class="button remove-product" style="background-color: #d9534f; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer;">{/literal}{ts}delete{/ts}{literal}</button>' +
      '</td>' +
    '</tr>';

    $('#combination-products-table').append(newRow);

    bindRowEvents($('#combination-products-table tr[data-index="' + productRowIndex + '"]'));

    productRowIndex++;

    $('html, body').animate({
      scrollTop: $('#combination-products-table tr:last').offset().top - 100
    }, 500);
  });

  function bindRowEvents($row) {
    $row.find('select[name^="new_product_"]:not([name*="_quantity"])').change(function() {
      var $quantityInput = $row.find('input[name*="_quantity"]');

      if ($(this).val()) {

        $quantityInput.prop('disabled', false);
        $quantityInput.focus();
        $row.addClass('selected');
        $row.css('background-color', '#f9f9f9');
      } else {
        $quantityInput.prop('disabled', true);
        $quantityInput.val('1');
        $row.removeClass('selected');
        $row.css('background-color', '');
      }
    });

    $row.find('input[name*="_quantity"]').on('input', function() {
      var value = $(this).val();
      if (!/^\d*$/.test(value)) {
        $(this).val(value.replace(/\D/g, ''));
      }
      if (parseInt(value) > 999) {
        $(this).val('999');
      }
    }).on('blur', function() {
      var value = $(this).val();
      if (value === '' || parseInt(value) === 0) {
        $(this).val('1');
      }
    });

    $row.find('.remove-product').click(function(e) {
      e.preventDefault();
      $row.fadeOut(300, function() {
        $row.remove();
      });
    });

    $row.hover(
      function() {
        $(this).css('background-color', '#f5f5f5');
      },
      function() {
        if (!$(this).hasClass('selected')) {
          $(this).css('background-color', '');
        } else {
          $(this).css('background-color', '#f9f9f9');
        }
      }
    );
  }

  $('#add-more-products').trigger('click');
  $('#add-more-products').trigger('click');
});
{/literal}
</script>

{/if}
</div>