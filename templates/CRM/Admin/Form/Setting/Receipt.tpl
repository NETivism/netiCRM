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
<div class="crm-block crm-form-block crm-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>                         
      <table class="form-layout-compressed">
        <tr class="crm-form-block-receiptLogo">
            <td class="label">{$form.receiptLogo.label}</td><td>{$form.receiptLogo.html}<br />    
            <span class="description">{ts}Paste logo url. Start with http://{/ts}</span></td>
        </tr>
        <tr class="crm-form-block-receiptPrefix">
            <td class="label">{$form.receiptPrefix.label}</td><td>{$form.receiptPrefix.html}<br />    
            <span class="description">{ts}Receipt ID prefix. Can be numberic or alphabetic.{/ts} {ts}Use this screen to configure formats for date display and date input fields. Defaults are provided for standard United States formats. Settings use standard POSIX specifiers.{/ts} {help id='date-format'}</span></td>
        </tr>
        <tr class="crm-form-block-receiptDescription">
            <td class="label">{$form.receiptDescription.label}</td><td>{$form.receiptDescription.html}<br />    
            <span class="description">{ts}Description will appear at the end of receipt.{/ts}</span></td>
        </tr>
        <tr class="crm-form-block-receiptOrgInfo">
            <td class="label">{$form.receiptOrgInfo.label}</td><td>{$form.receiptOrgInfo.html}<br />
            <span class="description">{ts}Organization info will appear at the end of receipt.{/ts}</span></td>
        </tr>
        <tr class="crm-form-block-receiptYesNo">
            <td class="label">{$form.receiptYesNo.label}</td><td>{$form.receiptYesNo.html}<br />
            <span class="description">{ts}Choose a Checkbox or a Radio field. If the field is selected to 'Yes' by contributor, then the receipt title and serial field will be required. On the contrary, the receipt title and serial field will hide.{/ts}</span></td>
        </tr>
        <tr class="crm-form-block-receiptTitle">
            <td class="label">{$form.receiptTitle.label}</td><td>{$form.receiptTitle.html}<br />
            <span class="description">{ts}When your receipt title save in another field, use this to select the field.{/ts}</span></td>
        </tr>
        <tr class="crm-form-block-receiptSerial">
            <td class="label">{$form.receiptSerial.label}</td><td>{$form.receiptSerial.html}<br />
            <span class="description">{ts}When your serial code save in another field, use this to select the field.{/ts}</span></td>
        </tr>
        <tr class="crm-form-block-receiptDonorCredit">
            <td class="label">{$form.receiptDonorCredit.label}</td><td>{$form.receiptDonorCredit.html}<br />
            <span class="description">{ts}When use custom field to record donor credit, use this to select the field.{/ts}</span></td>
        </tr>
        <tr class="crm-form-block-receiptAddrType">
            <td class="label">{$form.receiptAddrType.label}</td><td>{$form.receiptAddrType.html}<br />
            <span class="description">{ts}When use custom field to record donor credit, use this to select the field.{/ts}</span></td>
        </tr>
        <tr class="crm-form-block-uploadBigStamp">
            <td class="label">{$form.uploadBigStamp.label}</td><td>
                {if $imageBigStampUrl}
                <img style="max-height: 103px;" src="{$imageBigStampUrl}"><br/>
                {/if}
                {$form.uploadBigStamp.html}<br />
            <span class="description">{ts 1="receipt-image-button"}This image will show on receipt.The position please click <a class='%1'>here</a> to get more information.{/ts}</span></td>
        </tr>
        <tr class="crm-form-block-uploadSmallStamp">
            <td class="label">{$form.uploadSmallStamp.label}</td><td>
                {if $imageSmallStampUrl}
                <img style="max-height: 43px;" src="{$imageSmallStampUrl}"><br/>
                {/if}
                {$form.uploadSmallStamp.html}<br />
            <span class="description">{ts 1="receipt-image-button"}This image will show on receipt.The position please click <a class='%1'>here</a> to get more information.{/ts}</span></td>
        </tr>
      </table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>     
<div class="spacer"></div>
<div class="receipt-image">
    <div class="receipt-image-inner">
        <img src="{$config->resourceBase}i/admin/receipt/example1.png">
        <br/>
        <img src="{$config->resourceBase}i/admin/receipt/example2.png">
        <br/>
        <img src="{$config->resourceBase}i/admin/receipt/example_full.png">
        <div class="receipt-image-close-button receipt-image-button">X</div>
    </div>

</div>
{literal}
<style>
    .receipt-image-button{
        cursor: pointer;
    }
    .receipt-image{
        display: none;
    }
    .receipt-image.display{
        display: block;
        position: fixed;
        background: rgba(0,0,0,0.5);
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        padding: 5%;
    }
    .receipt-image-inner {
        overflow-y: scroll;
        background: white;
        height: 100%;
        padding: 20px;
    }
    .receipt-image-close-button{
        border: white 2px solid;
        width: 40px;
        position: absolute;
        top: 10%;
        right: calc(5% - 40px);
        color: white;
        font-size: 40px;
        line-height: 30px;
        text-align: center;
        padding: 10px 0;
        font-family: sans-serif;
    }
    .receipt-image-close-button:hover {
        background: white;
        color: black;
    }
</style>
<script>
    (function($){
        $(function(){
            $('.receipt-image-button,.receipt-image').click(function(e){
                if(e.srcElement.className == 'receipt-image-inner'){
                    return ;
                }
                $('.receipt-image').toggleClass('display');
                e.stopPropagation();
            });
        })
    })(cj);
</script>
{/literal}
</div>
