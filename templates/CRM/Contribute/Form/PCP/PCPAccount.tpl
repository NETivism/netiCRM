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
{* Displays account creation and supporter profile form (step 1 in creating a personal campaign page as well as Update Contact info). *}
{if $action EQ 1}
<div id="help">
        {ts}Creating your own fundraising page is simple. Fill in some basic information below, which will allow you to manage your page and invite friends to make a contribution. Then click 'Continue' to personalize and announce your page.{/ts}
</div>
  {if $is_manager && $readonly_profile}
    <div class="action-link-button">
      {capture assign="create_pcp_for_others_link"}{crmURL p='civicrm/contribute/campaign' q="action=add&reset=1&cid=0&pageId=`$page_id`"}{/capture}
      <a href="{$create_pcp_for_others_link}" class="button"><span><i class="zmdi zmdi-accounts-add"></i> {ts}Create a Personal Campaign Page for others{/ts}</span></a>
    </div>
  </div>
  {elseif $is_manager && $create_pcp_for_others}
    {capture assign="create_pcp_for_myself"}{crmURL p='civicrm/contribute/campaign' q="action=add&reset=1&pageId=`$page_id`"}{/capture}
    <div class="messages">
      {ts 1="$create_pcp_for_myself"}This is a page for create PCP for a new contact. To create your own personal campaign, visit this <a href="%1">link</a>.{/ts}
    </div>
  {/if}
{/if}

{if $profileDisplay}
<div class="messages status">
    	<p><strong>{ts}Profile is not configured with Email address.{/ts}</strong></p>
</div>
{else}
<div class="form-item">
{include file="CRM/common/CMSUser.tpl"} 
{include file="CRM/UF/Form/Block.tpl" fields=$fields} 
{if $isCaptcha} 
{include file='CRM/common/ReCAPTCHA.tpl'} 
{/if}
</div>
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
{/if}
<script>{literal}
  cj(document).ready(function($){
    // prevent overwrite others contact info
    var lockfield = function($obj){
      $obj.attr('title', '{/literal}{ts}To change your personal info, go My Account page for further setting.{/ts}{literal}');
      if ($obj.val()) {
        $obj.attr("readonly", "readonly").addClass("readonly");
      }
      if($obj.parent('.crm-form-elem').length){
        $obj.parent('.crm-form-elem').addClass('crm-form-readonly');
      }
    }
    {/literal}
    {if $readonly_profile}
      lockfield($("input#last_name"));
      lockfield($("input#first_name"));
      lockfield($("input[name^=email-]"));
      $(".first_name-section .content").append('<div class="description">{ts 1="/user"}To change your personal info, go <a href="%1">My Account page</a> for further setting.{/ts}</div>');
      {if $is_manager}
        $(".first_name-section .content").append('<div class="description">{ts 1="$create_pcp_for_others_link"}To add pcp for others, click <a href="%1">Create a Personal Campaign Page for others</a> to create page.{/ts}</div>');
      {/if}
    {/if}
    {literal}
  });
{/literal}</script>