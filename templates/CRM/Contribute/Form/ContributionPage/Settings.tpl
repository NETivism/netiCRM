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
{if $cdType} 
<div class="crm-block crm-form-block crm-contribution-contributionpage-settings-form-block">
	{include file="CRM/Custom/Form/CustomData.tpl"} 
{else} 
{* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
{include file="CRM/common/WizardHeader.tpl"}
<div class="crm-block crm-form-block crm-contribution-contributionpage-settings-form-block">
<div id="help">
    {if $action eq 0}
        <p>{ts}This is the first step in creating a new online Contribution Page. You can create one or more different Contribution Pages for different purposes, audiences, campaigns, etc. Each page can have it's own introductory message, pre-configured contribution amounts, custom data collection fields, etc.{/ts}</p>
        <p>{ts}In this step, you will configure the page title, contribution type (donation, campaign contribution, etc.), goal amount, and introductory message. You will be able to go back and modify all aspects of this page at any time after completing the setup wizard.{/ts}</p>
    {else}
        {ts}Use this form to edit the page title, contribution type (e.g. donation, campaign contribution, etc.), goal amount, introduction, and status (active/inactive) for this online contribution page.{/ts}
    {/if}
</div>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div> 
	<table class="form-layout-compressed">
	<tr class="crm-contribution-contributionpage-settings-form-block-is_active">
    <td></td>
    <td>{$form.is_active.html} {$form.is_active.label}<br />
    {if $id}
    		<span class="description">
        	{if $config->userFramework EQ 'Drupal'}
            	{ts}When your page is active, you can link people to the page by copying and pasting the following URL:{/ts}<br />
            	<strong>{crmURL a=true p='civicrm/contribute/transact' q="reset=1&id=`$id`"}</strong>
        	{elseif $config->userFramework EQ 'Joomla'}
            	{ts 1=$title}When your page is active, create front-end links to the contribution page using the Menu Manager. Select <strong>CiviCRM &raquo; Contribution Pages</strong> and select <strong>%1</strong> for the contribution page.{/ts}
        	{/if}
		</span>
    {/if}
	  </td>
	</tr>
  {if $form.is_internal}
	<tr class="crm-contribution-contributionpage-settings-form-block-is_internal">
    <td></td>
    <td>{$form.is_internal.html} {$form.is_internal.label}<br />
	  </td>
	</tr>
  {/if}
    <tr class="crm-contribution-contributionpage-settings-form-block-is_special">
        <td>&nbsp;</td><td>{$form.is_special.html}{$form.is_special.label}</td>
    </tr>
    <tr class="crm-contribution-contributionpage-settings-form-block-uploadBackgroundImage">
        <td class="label">{$form.uploadBackgroundImage.label}</td>
        <td class="value">
            {if $background_URL}
            <img style="max-height: 103px;" src="{$background_URL}">
            <a class="delete-image" href="javascript:void(0);" data-field="deleteBackgroundImage">{ts}Delete{/ts}</a>
            <br/>
            {/if}
            {$form.uploadBackgroundImage.html}<br />
        <span class="description">{ts}The background image used in the special style.{/ts}</span></td>
    </tr>
    <tr class="crm-contribution-contributionpage-settings-form-block-uploadMobileBackgroundImage">
        <td class="label">{$form.uploadMobileBackgroundImage.label}</td>
        <td class="value">
            {if $mobile_background_URL}
            <img style="max-height: 103px;" src="{$mobile_background_URL}">
            <a class="delete-image" href="javascript:void(0);" data-field="deleteMobileBackgroundImage">{ts}Delete{/ts}</a>
            <br/>
            {/if}
            {$form.uploadMobileBackgroundImage.html}<br />
        <span class="description">{ts}The background image of mobile used in the special style.{/ts}</span></td>
    </tr>
    {literal}
    <script type="text/javascript">
        cj(function($){
            $('.delete-image').click(function(){
                deleteFieldName = $(this).attr('data-field');
                $('[name='+deleteFieldName+']').val(1);
                $(this).parent().find('img').css('filter','brightness(50%)');
            });
        });
        showSpecial();
        function showSpecial() {
            var checkbox = document.getElementsByName("is_active");
            var checkbox2 = document.getElementsByName("is_special");
            if (checkbox[0].checked) {
                document.getElementsByClassName("crm-contribution-contributionpage-settings-form-block-is_special")[0].style.display = 'table-row';
                if(checkbox2[0].checked){
                    document.getElementsByClassName("crm-contribution-contributionpage-settings-form-block-uploadBackgroundImage")[0].style.display = 'table-row';
                    document.getElementsByClassName("crm-contribution-contributionpage-settings-form-block-uploadMobileBackgroundImage")[0].style.display = 'table-row';
                }else{
                    document.getElementsByClassName("crm-contribution-contributionpage-settings-form-block-uploadBackgroundImage")[0].style.display = 'none';
                    document.getElementsByClassName("crm-contribution-contributionpage-settings-form-block-uploadMobileBackgroundImage")[0].style.display = 'none';

                }

            } else {
                document.getElementsByClassName("crm-contribution-contributionpage-settings-form-block-is_special")[0].style.display = 'none';
                document.getElementsByClassName("crm-contribution-contributionpage-settings-form-block-uploadBackgroundImage")[0].style.display = 'none';
                document.getElementsByClassName("crm-contribution-contributionpage-settings-form-block-uploadMobileBackgroundImage")[0].style.display = 'none';
            }
        }
    </script>
    {/literal}
	<tr class="crm-contribution-contributionpage-settings-form-block-title"><td class="label">{$form.title.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='title' id=$id}{/if}</td><td>{$form.title.html}<br/>
            <span class="description">{ts}This title will be displayed at the top of the page.<br />Please use only alphanumeric, spaces, hyphens and dashes for Title.{/ts}</td>
	</tr>
	<tr class="crm-contribution-contributionpage-settings-form-block-contribution_type_id"><td class="label">{$form.contribution_type_id.label}</td><td>{$form.contribution_type_id.html} {help id="id-contribution_type"}<br />	
            <span class="description">{ts}Select the corresponding contribution type for contributions made using this page.{/ts}</span></td>
	</tr>
	<tr class="crm-contribution-contributionpage-settings-form-block-display_progress_bar">
    <td class="label">{$form.display_progress_bar.label} {help id="id-progress_bar"}</td><td>{$form.display_progress_bar.html} {ts}Display progress bar on the top of contribution page.{/ts}</td>
  </tr>
	<tr id="for_goal_option" class="crm-contribution-form-block-display_progress_bar">
        <td></td>
        <td>
            <table class="form-layout-compressed">
              <tr class="crm-contribution-contributionpage-settings-form-block-goal_amount" id="goal_amount_row">
                  <td>{$form.goal_amount.label}: $ {$form.goal_amount.html} {ts}or{/ts} 
                  {$form.goal_recurring.label}:{$form.goal_recurring.html} {ts}People{/ts} {ts}or{/ts} 
                  {$form.goal_recuramount.label}:{$form.goal_recuramount.html}
                  </td>
              </tr>
            </table>
        </td>
  </tr>
	<tr class="crm-contribution-contributionpage-settings-form-block-intro_text">
	    <td class ="label">{$form.intro_text.label}<br />{help id="id-intro_msg"}</td><td>{$form.intro_text.html}</td>
	</tr>
	<tr class="crm-contribution-contributionpage-settings-form-block-footer_text">
	    <td class ="label"><div class="footer_text-label">{$form.footer_text.label}<br />{help id="id-footer_msg"}</div><div class="non_recurring_hint_msg-label">{ts}Non-recurring contribution hint message.{/ts}</div></td><td>{$form.footer_text.html}</td>
	</tr>
	<tr class="crm-contribution-contributionpage-settings-form-block-is_organization"><td>&nbsp;</td><td>{$form.is_organization.html} {$form.is_organization.label}</td></tr>
	<tr id="for_org_option" class="crm-contribution-form-block-is_organization">
        <td>&nbsp;</td>
        <td>
            <table class="form-layout-compressed">
            <tr id="for_org_text" class="crm-contribution-contributionpage-settings-form-block-for_organization">
                <td class="label">{$form.for_organization.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='for_organization' id=$id}{/if}</td><td>{$form.for_organization.html}<br />
                    <span class="description">{ts}Text displayed next to the checkbox on the contribution form.{/ts}</span>
                </td>
            </tr>
            <tr class="crm-contribution-contributionpage-settings-form-block-is_for_organization">
                <td>&nbsp;</td>
                <td>{$form.is_for_organization.html}<br />
                    <span class="description">{ts}Check 'Required' to force ALL users to contribute/signup on behalf of an organization.{/ts}</span>
                </td>
            </tr>
            </table>
        </td>
    </tr>
	<tr class="crm-contribution-contributionpage-settings-form-block-honor_block_is_active">
	    <td>&nbsp;</td><td>{$form.honor_block_is_active.html}{$form.honor_block_is_active.label} {help id="id-honoree_section"}</td>
	</tr>
</table>
<table class="form-layout-compressed" id="honor">
    <tr class="crm-contribution-contributionpage-settings-form-block-honor_block_title"><td class="label">{$form.honor_block_title.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='honor_block_title' id=$id}{/if}</td><td>{$form.honor_block_title.html}<br />
	    <span class="description">{ts}Title for the Honoree section (e.g. &quot;Honoree Information&quot;).{/ts}</span></td>
	</tr>
	<tr class="crm-contribution-contributionpage-settings-form-block-honor_block_text">
    	<td class="label">{$form.honor_block_text.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='honor_block_text' id=$id}{/if}</td><td>{$form.honor_block_text.html}<br />
    	<span class="description">{ts}Optional explanatory text for the Honoree section (displayed above the Honoree fields).{/ts}</span></td>
	</tr>
</table>
	<div id="customData"></div>
	{*include custom data js file*}
	{include file="CRM/common/customData.tpl"}	
	{literal}
		<script type="text/javascript">
			cj(document).ready(function($) {
				{/literal}
				{if $customDataSubType} 
					buildCustomData( '{$customDataType}', {$customDataSubType} );
				{else}
					buildCustomData( '{$customDataType}' );
				{/if}
				{literal}

        // progress bar setting
        $("input[name^=goal_]").change(function(){
          var name = $(this).prop("name");
          if ($(this).val()) {
            $("input[name^=goal_]").not($(this)).val("").prop("readonly", true);
          }
          else {
            $("input[name^=goal_]").val("").prop("readonly", false);
          }
        });

        var doChangeSpecialStyle = function(){
            if($('#is_special:checked').length){
                $('.non_recurring_hint_msg-label').show();
                $('.footer_text-label').hide();
            }else{
                $('.non_recurring_hint_msg-label').hide();
                $('.footer_text-label').show();
            }
        }
        doChangeSpecialStyle();
        $('#is_special').change(doChangeSpecialStyle);
			});
		</script>
	{/literal}
        <div class="crm-block crm-form-block crm-contribution-contributionpage-settings-form-block-date">
            <div class="crm-accordion-wrapper crm-contribution_page_start_end_date-accordion crm-accordion-closed">
                <div class="crm-accordion-header">
                    <div class="zmdi crm-accordion-pointer"></div> 
                    {ts}Contribution Widget{/ts}
                    
                </div><!-- /.crm-accordion-header -->
                <div class="crm-accordion-body" id="start_end_date">
                    <table class="crm-section form-layout-compressed">
                        <tr class="crm-contribution-contributionpage-settings-form-block-start_date">
                            <td class ="label">{$form.start_date.label} {help id="id-start_date"}</td>
                            <td>
                                {include file="CRM/common/jcalendar.tpl" elementName=start_date}
                            </td>    
                        </tr>
                        <tr class="crm-contribution-contributionpage-settings-form-block-end_date">
                            <td class ="label">{$form.end_date.label}</td>
                            <td>
                                {include file="CRM/common/jcalendar.tpl" elementName=end_date}
                            </td>    
                        </tr>
                    </table>
                </div><!-- /.crm-accordion-body -->
            </div>
        </div><!-- /.crm-accordion-wrapper -->
	 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="is_organization"
    trigger_value       = 1
    target_element_id   ="for_org_text" 
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
}

{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="is_organization"
    trigger_value       = 1
    target_element_id   ="for_org_option" 
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
}

{include file="CRM/common/showHideByFieldValue.tpl" 
    trigger_field_id    ="display_progress_bar"
    trigger_value       = 1
    target_element_id   ="goal_amount_row" 
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
}
<script type="text/javascript">
 showHonor();
 {literal}
     function showHonor() {
        var checkbox = document.getElementsByName("honor_block_is_active");
        if (checkbox[0].checked) {
            document.getElementById("honor").style.display = "block";
        } else {
            document.getElementById("honor").style.display = "none";
        }  
     } 
 {/literal} 
</script>

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}

{if $config->nextEnabled}
<div class="nme-setting-panels">
  <div class="nme-setting-panels-inner">
    <div class="nme-setting-panels-header" id="nme-setting-panels-header">
      <div class="inner">
        <ul data-target-contents="nme-setting-panel" class="nme-setting-panels-tabs">
          <li><a href="#nme-aicompletion" class="is-active" data-target-id="nme-aicompletion">{ts}AI Copywriter{/ts}</a></li>
          <li><a href="#nme-aiimagegeneration" data-target-id="nme-aiimagegeneration">{ts}AI Image Generator{/ts}</a></li>
        </ul>
      </div>
    </div>
    <div class="nme-ai-panels-content" id="nme-setting-panels-content">
      <div id="nme-aicompletion" class="nme-aicompletion nme-setting-panel is-active">
        <div class="nme-setting-panel-inner">
          <h3 class="nme-setting-panel-title">{ts}AI Copywriter{/ts}</h3>
          <div class="nme-setting-panel-content">
            {include file="CRM/AI/AICompletion.tpl"}
          </div>
        </div>
      </div>
      <div id="nme-aiimagegeneration" class="nme-aiimagegeneration nme-setting-panel">
        <div class="nme-setting-panel-inner">
          <h3 class="nme-setting-panel-title">{ts}AI Image Generator{/ts}</h3>
          <div class="nme-setting-panel-content">
            {include file="CRM/AI/AIImageGeneration.tpl"}
          </div>
        </div>
      </div>
    </div>
  </div>
	<div class="nme-setting-panels-trigger" data-tooltip data-tooltip-placement="w"><i
			class="zmdi zmdi-settings"></i></div>
</div>
{include file="CRM/common/sidePanel.tpl" type="inline" headerSelector="#nme-setting-panels-header" contentSelector="#nme-setting-panels-content" containerClass="nme-setting-panels" opened="true" userPreference="true" triggerText="AI Assistant" width="500px" fullscreen="true" triggerIcon="packages/AICompletion/images/icon--magic--white.svg"}
{/if}

{/if}{* end cdtype *}
