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
{if $tabHeader and count($tabHeader) gt 1}
<div id="mainTabContainer">
<ul>
   {foreach from=$tabHeader key=tabName item=tabValue}
      <li id="tab_{$tabName}">
      {if $tabValue.link and $tabValue.active}
         <a href="{$tabValue.link}" title="{$tabValue.title}">{$tabValue.title}{if !$tabValue.valid}&nbsp;({ts}disabled{/ts}){/if}</a>
      {else}
         {$tabValue.title}{if !$tabValue.valid}&nbsp;({ts}disabled{/ts}){/if}
      {/if}
      </li>
   {/foreach}
</ul>
</div>
{/if}


<script type="text/javascript"> 
   var selectedTab = 'EventInfo';
   {if $selectedTab}selectedTab = "{$selectedTab}";{/if}    
{literal}
    cj( function() {
        var tabIndex = cj('#tab_' + selectedTab).prevAll().length
        cj("#mainTabContainer").tabs( {
            selected: tabIndex,
            select: function(event, ui) {
                if ( !global_formNavigate ) {
                    var message = '{/literal}{ts}Confirm\n\nAre you sure you want to navigate away from this tab?\n\nYou have unsaved changes.\n\nPress OK to continue, or Cancel to stay on the current tab.{/ts}{literal}';
                    if ( !confirm( message ) ) {
                        return false;
                    } else {
                        global_formNavigate = true;
                    }
                }
                return true;
            }
        });        
    });
{/literal}
</script>
