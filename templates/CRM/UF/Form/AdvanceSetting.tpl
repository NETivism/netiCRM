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
{literal}
<script type="text/javascript">
    cj(function() {
        cj("#accordion").accordion({ active: false, header: "h3", collapsible: true, clearStyle: true });
    });
</script>
{/literal}

<div id="accordion">
    <h3><a href="#">Advanced Settings</a></h3>
    <div class ="form-item" style="background:#F7F7F7;">
        <dl>
            <dt>{$form.group.label}</dt>
            <dd>{$form.group.html} {help id='id-limit_group' file="CRM/UF/Form/Group.hlp"}</dd>
            
            <dt>{$form.add_contact_to_group.label}</dt>
            <dd>{$form.add_contact_to_group.html} {help id='id-add_group' file="CRM/UF/Form/Group.hlp"}</dd>
            
            <dt>{$form.notify.label}</dt>
            <dd>{$form.notify.html} {help id='id-notify_email' file="CRM/UF/Form/Group.hlp"}</dd>
            
            <dt>{$form.post_URL.label}</dt>
            <dd>{$form.post_URL.html} {help id='id-post_URL' file="CRM/UF/Form/Group.hlp"}</dd>
            
            <dt>{$form.cancel_URL.label}</dt>
            <dd>{$form.cancel_URL.html} {help id='id-cancel_URL' file="CRM/UF/Form/Group.hlp"}</dd>
            
            <dt></dt>
            <dd>{$form.add_captcha.html} {$form.add_captcha.label} {help id='id-add_captcha' file="CRM/UF/Form/Group.hlp"}</dd>
            
            {if ($config->userFramework == 'Drupal') OR ($config->userFramework == 'Joomla') }
                {* Create CMS user only available for Drupal/Joomla installs. *}
                <dt>{$form.is_cms_user.label}</dt>
                <dd>{$form.is_cms_user.html} {help id='id-is_cms_user' file="CRM/UF/Form/Group.hlp"}</dd>		
            {/if}
            <dt></dt>
            <dd>{$form.is_update_dupe.html} {$form.is_update_dupe.label} {help id='id-is_update_dupe' file="CRM/UF/Form/Group.hlp"}</dd>
            
            <dt></dt>
            <dd>{$form.is_map.html} {$form.is_map.label} {help id='id-is_map' file="CRM/UF/Form/Group.hlp"}</dd>
            
            <dt></dt>
            <dd>{$form.is_edit_link.html} {$form.is_edit_link.label} {help id='id-is_edit_link' file="CRM/UF/Form/Group.hlp"}</dd>
            
            <dt></dt>
            <dd>{$form.is_uf_link.html} {$form.is_uf_link.label} {help id='id-is_uf_link' file="CRM/UF/Form/Group.hlp"}</dd>
        </dl>
    </div>
</div>