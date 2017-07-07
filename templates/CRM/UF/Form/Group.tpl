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
{* add/update/view CiviCRM Profile *} 
  <div class=" crm-block crm-form-block crm-uf_group-form-block">  
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
{if $action eq 2 or $action eq 4 } {* Update or View*}
    <div class="action-link-button">
	<a href="{crmURL p='civicrm/admin/uf/group/field' q="action=browse&reset=1&gid=$gid"}" class="button"><span>{ts}View or Edit Fields for this Profile{/ts}</a></span>
	<div class="clear"></div>
    </div>
{/if}      
 
    {if $action eq 8 or $action eq 64}
    {if $action eq 8}
     <h2> {ts}Delete CiviCRM Profile{/ts}</h2>
    {/if}
            <div class="messages status">
                   
                   {$message}
            </div>   
	       
    {else}
<div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-open">
  <div class="crm-accordion-header">
    <div class="zmdi crm-accordion-pointer"></div> 
    {ts}Settings{/ts}
  </div><!-- /.crm-accordion-header -->
  <div class="crm-accordion-body">
    <table class="form-layout">
      <tr class="crm-uf_group-form-block-is_active" >
        <td class="label"></td><td class="html-adjust">{$form.is_active.html} {$form.is_active.label}</td>
      </tr>
      <tr class="crm-uf_group-form-block-title">
        <td class="label">{$form.title.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_uf_group' field='title' id=$gid}{/if}</td>
        <td class="html-adjust">{$form.title.html}</td>
      </tr>
      <tr class="crm-uf_group-form-block-uf_group_type">
        <td class="label">{$form.uf_group_type.label}</td>
        <td class="html-adjust">
          {$form.uf_group_type.html}
          <div class="description online-profile-{$onlineProfile} font-red" style="display:none;">{if !$sonlineProfile}{ts}This is not a standalone form and will not allowed to be embbed because used in contribution and event.{/ts}{/if}</div>
        </td>
      </tr>
      <tr class="crm-uf-group-form-block-uf_group_type_user">
        <td class="label">{$form.uf_group_type_user.label}</td>
        <td>{$form.uf_group_type_user.html}</td>
      </tr>		
      <tr class="crm-uf_group-form-block-help_pre" >
        <td class="label">{$form.help_pre.label}</td>
        <td class="html-adjust">{$form.help_pre.html}
          <div class="description">{ts}Explanatory text displayed at the beginning of the form.{/ts}</div>
        </td>
      </tr>
      <tr class="crm-uf_group-form-block-help_post" >
        <td class="label">{$form.help_post.label}</td>
        <td class="html-adjust">{$form.help_post.html}
          <div class="description">{ts}Explanatory text displayed at the end of the form.{/ts}{ts}You can add some notice or contact info here.{/ts}</div>
        </td>
      </tr>
      <tr class="crm-uf-advancesetting-form-block-add_contact_to_group">
        <td class="label">{$form.add_contact_to_group.label}</td>
        <td>{$form.add_contact_to_group.html} {help id='id-add_group' file="CRM/UF/Form/Group.hlp"}</td>
      </tr>
      
      <tr class="crm-uf-advancesetting-form-block-notify">
        <td class="label">{$form.notify.label}</td>
        <td>{$form.notify.html} {help id='id-notify_email' file="CRM/UF/Form/Group.hlp"}</td>
      </tr>
    </table>
  </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
{* adding advance setting tab *}
<div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-closed">
  <div class="crm-accordion-header">
    <div class="zmdi crm-accordion-pointer"></div> 
    {ts}Advanced options{/ts}
  </div><!-- /.crm-accordion-header -->
  <div class="crm-accordion-body">
    <table class="form-layout">
      <tr class="crm-uf_group-form-block-weight" >
        <td class="label">{$form.weight.label}{if $config->userFramework EQ 'Drupal'} {help id='id-profile_weight' file="CRM/UF/Form/Group.hlp"}{/if}</td>
        <td class="html-adjust">{$form.weight.html}</td>
      </tr>
      <tr class="crm-uf-advancesetting-form-block-post_URL">
        <td class="label">{$form.post_URL.label}</td>
        <td>{$form.post_URL.html} {help id='id-post_URL' file="CRM/UF/Form/Group.hlp"}</td>
      </tr>
      <tr class="crm-uf-advancesetting-form-block-cancel_URL">
        <td class="label">{$form.cancel_URL.label}</td>
        <td>{$form.cancel_URL.html} {help id='id-cancel_URL' file="CRM/UF/Form/Group.hlp"}</td>
      </tr>
      <tr class="crm-uf-advancesetting-form-block-add_captcha">
        <td class="label">{ts}Add Captcha{/ts}</td>
        <td>{$form.add_captcha.html} {$form.add_captcha.label} {help id='id-add_captcha' file="CRM/UF/Form/Group.hlp"}</td>
      </tr>
      <tr class="crm-uf-advancesetting-form-block-is_update_dupe">
        <td class="label">{$form.is_update_dupe.label}</td>
        <td>{$form.is_update_dupe.html} {help id='id-is_update_dupe' file="CRM/UF/Form/Group.hlp"}</td>
      </tr>
      {if ($config->userFramework == 'Drupal') OR ($config->userFramework == 'Joomla') }
          {* Create CMS user only available for Drupal/Joomla installs. *}
      <tr class="crm-uf-advancesetting-form-block-is_cms_user">
        <td class="label">{$form.is_cms_user.label}</td>
        <td>{$form.is_cms_user.html} {help id='id-is_cms_user' file="CRM/UF/Form/Group.hlp"}</td>
      </tr>		
      {/if}
    </table>
  </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
{literal}
<script type="text/javascript">
cj(function($) {
  $().crmaccordions(); 
  $("#is_active").change(function(){
    if (!this.checked) {
      var answer = window.confirm("{/literal}{ts}If you disable the profile - it will be removed from these forms and/or modules. Do you want to continue?{/ts}{literal}");
      if (!answer) {
        this.checked = true;
        return;
      }
    }
  });
  
  $("input[id^=uf_group_type]").change(function(){
    var show = true;
    $("input[id^=uf_group_type\\\[Civi]").each(function(){
      if (this.checked) {
        $("tr.crm-uf-group-form-block-uf_group_type_user").find("input").each(function() {
          this.checked = false;
        });
        $("tr.crm-uf-group-form-block-uf_group_type_user").hide();
        show = false;
      }
      else {
        $(this).closest('td').find('.description').show();
      }
    });
    if (show) {
      $("tr.crm-uf_group-form-block-uf_group_type .description").hide();
      $("tr.crm-uf-group-form-block-uf_group_type_user").show();
      $("#uf_group_type\\\[Profile\\\]").attr('checked', true);
    }
    else {
      $("#uf_group_type\\\[Profile\\\]").attr('checked', false);
      $("tr.crm-uf_group-form-block-uf_group_type .description").show();
    }
  });

  if ($("#uf_group_type\\\[Profile\\\]").attr('type') == 'hidden') {
    var standalone = true;
    $("input[id^=uf_group_type\\\[Civi]").each(function(){
      if ($(this).val()) {
        standalone = false;
      }
    });
    if (!standalone) {
      $('.online-profile-1, .online-profile-0').show();
    }
    else {
      $('.online-profile-1, .online-profile-0').hide();
    }
  }
});
</script>
{/literal}

    {/if}

    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
</div>
{include file="CRM/common/showHide.tpl"}

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}

