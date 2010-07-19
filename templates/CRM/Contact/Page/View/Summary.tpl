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
{* Contact Summary template for new tabbed interface. Replaces Basic.tpl *}
{if $action eq 2}
    {include file="CRM/Contact/Form/Contact.tpl"}
{else}
    <div id="mainTabContainer" >
        <ul>
            <li id="tab_summary"><a href="#contact-summary" title="{ts}Summary{/ts}" >{ts}Summary{/ts}</a></li>
            {foreach from=$allTabs key=tabName item=tabValue}
            <li id="tab_{$tabValue.id}"><a href="{$tabValue.url}" title="{$tabValue.title}">{$tabValue.title}&nbsp;({$tabValue.count})</a></li>
            {/foreach}
        </ul>

        <div title="Summary" id="contact-summary" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
            {if $hookContentPlacement neq 3}
                <div class="buttons ui-corner-all">
                    <ul id="actions">
                        {if $permission EQ 'edit'}
                        <li>
                        <a href="{crmURL p='civicrm/contact/add' q="reset=1&action=update&cid=$contactId"}" class="edit button" title="{ts}Edit{/ts}">
                        <span><div class="icon edit-icon"></div>{ts}Edit{/ts}</span>
                        </a>
                        </li>
                        {/if}

                        {* CRM-4418 *}
                        {* user should have edit permission to delete contact *}
                        {if (call_user_func(array('CRM_Core_Permission','check'), 'delete contacts')) && ($permission EQ 'edit') }
                        <li>
                        <a href="{crmURL p='civicrm/contact/view/delete' q="reset=1&delete=1&cid=$contactId"}" class="delete button" title="{ts}Delete{/ts}">
                        <span><div class="icon delete-icon"></div>{ts}Delete{/ts}</span>
                        </a>
                        </li>
                        {/if}

                        {* Include links to enter Activities if session has 'edit' permission *}
                        {if $permission EQ 'edit'}
                        <li>
                            {include file="CRM/Activity/Form/ActivityLinks.tpl"}
                        </li>
                        {/if}
                        <li><span class="label">{ts}Go to:{/ts}</span></li>
                        {if $dashboardURL }
                        <li>
                        <a href="{$dashboardURL}" class="dashboard button" title="{ts}dashboard{/ts}">
                        	<span><div class="icon dashboard-icon"></div>{ts}Dashboard{/ts}</span>
                        </a>
                        </li>
                        {/if}
                        {if $url }
                        <li>
                        <a href="{$url}" class="user-record button" title="{ts}User Record{/ts}">
                        <span><div class="icon user-record-icon"></div>{ts}User Record{/ts}</span>
                        </a>
                        </li>
                        {/if}
                        {if $groupOrganizationUrl}
                        <li>
                        <a href="{$groupOrganizationUrl}" class="associated-groups button" title="{ts}Associated Multi-Org Group{/ts}">
                        <span><div class="icon associated-groups-icon"></div>{ts}Associated Multi-Org Group{/ts}</span>
                        </a>   
                        </li>
                        {/if}
                    </ul> 
                    <span id="icons">
                        <a title="{ts}vCard record for this contact.{/ts}" href='{crmURL p='civicrm/contact/view/vcard' q="reset=1&cid=$contactId"}'> <img src="{$config->resourceBase}i/vcard-icon.png" alt="vCard record for this contact." /></a>
                        <a title="{ts}Printer-friendly view of this page.{/ts}" href='{crmURL p='civicrm/contact/view/print' q="reset=1&print=1&cid=$contactId"}'"> <img src="{$config->resourceBase}i/print-icon.png" alt="Printer-friendly view of this page." /></a>
                    </span>
                </div><!-- .buttons -->
                
                {if $hookContent and $hookContentPlacement eq 2}
                    {include file="CRM/Contact/Page/View/SummaryHook.tpl"}
                {/if}
                
                {if $contact_type_label OR $current_employer_id OR $job_title OR $legal_name OR $sic_code OR $nick_name OR $contactTag OR $source}
                <div id="contactTopBar" class="ui-corner-all">
                    <table>
                        {if $contact_type_label OR $current_employer_id OR $job_title OR $legal_name OR $sic_code OR $nick_name}
                        <tr>
                            <td class="label">{ts}Contact Type{/ts}</td>
                            <td>{$contact_type_label}</td>
                            {if $current_employer_id}
                            <td class="label">{ts}Employer{/ts}</td>
                            <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$current_employer_id`"}" title="{ts}view current employer{/ts}">{$current_employer}</a></td>
                            {/if}
                            {if $job_title}
                            <td class="label">{ts}Position{/ts}</td>
                            <td>{$job_title}</td>
                            {/if}
                            {if $legal_name}
                            <td class="label">{ts}Legal Name{/ts}</td>
                            <td>{$legal_name}</td>
                            {if $sic_code}
                            <td class="label">{ts}SIC Code{/ts}</td>
                            <td>{$sic_code}</td>
                            {/if}
                            {elseif $nick_name}
                            <td class="label">{ts}Nickname{/ts}</td>
                            <td>{$nick_name}</td>
                            {/if}
                        </tr>
                        {/if}
                        {if $contactTag OR $source}
                        <tr>
                            <td class="label" id="tagLink"><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$contactId&selectedChild=tag"}" title="{ts}Edit Tags{/ts}">{ts}Tags{/ts}</a></td><td id="tags">{$contactTag}</td>
                            {if $source}
                            <td class="label">{ts}Source{/ts}</td><td>{$source}</td>
                            {/if}
                        </tr>
                        {/if}
                    </table>

                    <div class="clear"></div>
                </div><!-- #contactTopBar -->
                {/if}

                <div class="contact_details ui-corner-all">
                    <div class="contact_panel">
                        <div class="contactCardLeft">
                            <table>
                                {foreach from=$email item=item }
                                    {if $item.email}
                                    <tr>
                                        <td class="label">{$item.location_type}&nbsp;{ts}Email{/ts}</td>
                                        <td><span class={if $privacy.do_not_email}"do-not-email" title="{ts}Privacy flag: Do Not Email{/ts}" {elseif $item.on_hold}"email-hold" title="{ts}Email on hold - generally due to bouncing.{/ts}" {elseif $item.is_primary eq 1}"primary"{/if}><a href="mailto:{$item.email}">{$item.email}</a>{if $item.on_hold}&nbsp;({ts}On Hold{/ts}){/if}{if $item.is_bulkmail}&nbsp;({ts}Bulk{/ts}){/if}</span></td>
                                    </tr>
                                    {/if}
                                {/foreach}
                                {if $home_URL}
                                <tr>
                                    <td class="label">{ts}Website{/ts}</td>
                                    <td><a href="{$home_URL}" target="_blank">{$home_URL}</a></td>
                                </tr>
                                {/if}
                                {if $user_unique_id}
                                    <tr>
                                        <td class="label">{ts}Unique Id{/ts}</td>
                                        <td>{$user_unique_id}</td>
                                    </tr>
                                {/if}
                            </table>
                        </div><!-- #contactCardLeft -->

                        <div class="contactCardRight">
                            {if $phone OR $im OR $openid}
                                <table>
                                    {foreach from=$phone item=item}
                                        {if $item.phone}
                                        <tr>
                                            <td class="label">{$item.location_type}&nbsp;{$item.phone_type}</td>
                                            <td {if $item.is_primary eq 1}class="primary"{/if}><span {if $privacy.do_not_phone} class="do-not-phone" title={ts}"Privacy flag: Do Not Phone"{/ts} {/if}>{$item.phone}</span></td>
                                        </tr>
                                        {/if}
                                    {/foreach}
                                    {foreach from=$im item=item}
                                        {if $item.name or $item.provider}
                                        {if $item.name}<tr><td class="label">{$item.provider}&nbsp;({$item.location_type})</td><td {if $item.is_primary eq 1}class="primary"{/if}>{$item.name}</td></tr>{/if}
                                        {/if}
                                    {/foreach}
                                    {foreach from=$openid item=item}
                                        {if $item.openid}
                                            <tr>
                                                <td class="label">{$item.location_type}&nbsp;{ts}OpenID{/ts}</td>
                                                <td {if $item.is_primary eq 1}class="primary"{/if}><a href="{$item.openid}">{$item.openid|mb_truncate:40}</a>
                                                    {if $config->userFramework eq "Standalone" AND $item.allowed_to_login eq 1}
                                                        <br/> <span style="font-size:9px;">{ts}(Allowed to login){/ts}</span>
                                                    {/if}
                                                </td>
                                            </tr>
                                        {/if}
                                    {/foreach}
                                </table>
    						{/if}
                        </div><!-- #contactCardRight -->

                        <div class="clear"></div>
                    </div><!-- #contact_panel -->
					{if $address}
                    <div class="separator"></div>

                    <div class="contact_panel">
                        {foreach from=$address item=add key=locationIndex}
                        <div class="{cycle name=location values="contactCardLeft,contactCardRight"}">
                            <table>
                                <tr>
                                    <td class="label">{ts 1=$add.location_type}%1&nbsp;Address{/ts}
                                        {if $config->mapAPIKey AND $add.geo_code_1 AND $add.geo_code_2}
                                            <br /><a href="{crmURL p='civicrm/contact/map' q="reset=1&cid=`$contactId`&lid=`$add.location_type_id`"}" title="{ts 1='&#123;$add.location_type&#125;'}Map %1 Address{/ts}"><span class="geotag">{ts}Map{/ts}</span></a>
                                        {/if}</td>
                                    <td>
                                        {if $householdName and $locationIndex eq 1}
                                        <strong>{ts}Household Address:{/ts}</strong><br />
                                        <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$mail_to_household_id`"}">{$householdName}</a><br />
                                        {/if}
                                        {$add.display|nl2br}
                                    </td>
                                </tr>
                            </table>
                        </div>
                        {/foreach}

                        <div class="clear"></div>
                    </div>

                    <div class="separator"></div>
					{/if}
                    <div class="contact_panel">
                        <div class="contactCardLeft">
                            <table>
                                <tr><td class="label">{ts}Privacy{/ts}</td>
                                    <td><span class="font-red upper">
                                        {foreach from=$privacy item=priv key=index}
                                            {if $priv}{$privacy_values.$index}<br />{/if}
                                        {/foreach}
					{if $is_opt_out}{ts}No Bulk Emails (User Opt Out){/ts}{/if}
                                    </span></td>
                                </tr>
                                <tr>
                                    <td class="label">{ts}Preferred Method(s){/ts}</td><td>{$preferred_communication_method_display}</td>
                                </tr>
                                <tr>
                                    <td class="label">{ts}Email Format{/ts}</td><td>{$preferred_mail_format}</td>
                                </tr>
                            </table>
                        </div>

                        {include file="CRM/Contact/Page/View/Demographics.tpl"}
						
		<div class="clear"></div>
                        <div class="separator"></div>
						
						<div class="contactCardLeft">
						{if $contact_type neq 'Organization'}
						 <table>
							<tr>
								<td class="label">{ts}Email Greeting{/ts}{if $email_greeting_custom}<br/><span style="font-size:8px;">({ts}Customized{/ts})</span>{/if}</td>
								<td>{$email_greeting_display}</td>
							</tr>
							<tr>
								<td class="label">{ts}Postal Greeting{/ts}{if $postal_greeting_custom}<br/><span style="font-size:8px;">({ts}Customized{/ts})</span>{/if}</td>
								<td>{$postal_greeting_display}</td>
							</tr>
						 </table>
						 {/if}
						</div>
						<div class="contactCardRight">
						 <table>
							<tr>
								<td class="label">{ts}Addressee{/ts}{if $addressee_custom}<br/><span style="font-size:8px;">({ts}Customized{/ts})</span>{/if}</td>
								<td>{$addressee_display}</td>
							</tr>
						 </table>
						</div>
						
                        <div class="clear"></div>
                    </div>
                </div><!--contact_details-->

                <div id="customFields">
                    <div class="contact_panel">
                        <div class="contactCardLeft">
                            {include file="CRM/Contact/Page/View/CustomDataView.tpl" side='1'}
                        </div><!--contactCardLeft-->

                        <div class="contactCardRight">
                            {include file="CRM/Contact/Page/View/CustomDataView.tpl" side='0'}
                        </div>

                        <div class="clear"></div>
                    </div>
                </div>
                
                {if $hookContent and $hookContentPlacement eq 1}
                    {include file="CRM/Contact/Page/View/SummaryHook.tpl"}
                {/if}
            {else}
                {include file="CRM/Contact/Page/View/SummaryHook.tpl"}
            {/if}
        </div>

    </div>

    <script type="text/javascript"> 
    var selectedTab = 'summary';
    {if $selectedChild}selectedTab = "{$selectedChild}";{/if}    
	{literal}
	cj( function() {
        var tabIndex = cj('#tab_' + selectedTab).prevAll().length
        cj("#mainTabContainer").tabs( {selected: tabIndex} );        
    });
    {/literal}
    </script>
{/if}
