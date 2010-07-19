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
{if !$printOnly} {* NO print section starts *}
    <div {if !$criteriaForm}style="display: none;"{/if}> {* criteria section starts *}
        <div id="id_{$formTpl}_show" class="section-hidden section-hidden-border">
            <a href="#" onclick="hide('id_{$formTpl}_show');show('id_{$formTpl}'); return false;">
                <img src="{$config->resourceBase}i/TreePlus.gif" class="action-icon" alt="{ts}open section{/ts}"/></a>
            <label>{ts}Report Criteria{/ts}</label>
            <br />
        </div>
        <div id="id_{$formTpl}"> {* search section starts *}
            <fieldset>
                <legend>
                    <a href="#" onclick="hide('id_{$formTpl}'); show('id_{$formTpl}_show'); return false;"><img src="{$config->resourceBase}i/TreeMinus.gif" class="action-icon" alt="{ts}close section{/ts}"/></a>{ts}Report Criteria{/ts}
                </legend>
                {include file="CRM/Report/Form/Criteria.tpl"}
            </fieldset>
        </div> {* search div section ends *}
    </div> {* criteria section ends *}

    {if ($instanceForm and $rows) OR $instanceFormError} {* settings section starts *}
        <div id="id_{$instanceForm}_show" class="section-hidden section-hidden-border">
            <a href="#" onclick="hide('id_{$instanceForm}_show'); show('id_{$instanceForm}'); {if $updateReportButton} hide('update-button');{/if} return false;"><img src="{$config->resourceBase}i/TreePlus.gif" class="action-icon" alt="{ts}open section{/ts}"/></a>
            <label>{if $mode eq 'template'}{ts}Create Report{/ts}{else}{ts}Report Settings{/ts}{/if}</label>
            <br />
        </div>

        <div id="id_{$instanceForm}">
            <fieldset>
                <legend>
                    <a href="#" onclick="hide('id_{$instanceForm}'); show('id_{$instanceForm}_show'); {if $updateReportButton} show('update-button'); {/if} return false;"><img src="{$config->resourceBase}i/TreeMinus.gif" class="action-icon" alt="{ts}close section{/ts}"/></a>{if $mode eq 'template'}{ts}Create Report{/ts}{else}{ts}Report Settings{/ts}{/if}
                </legend>
                <div id="instanceForm">
                    {include file="CRM/Report/Form/Instance.tpl"}
                    {assign var=save value="_qf_"|cat:$form.formName|cat:"_submit_save"}
                        <div>
                            <br />
                            {$form.$save.html}
                        </div>
                </div>
            </fieldset>
        </div>
    {/if} {* settings section ends *}
    
    {if $updateReportButton}
        <div id="update-button" class="section-hidden-border" style="margin:-5px 0 5px 5px;">        
            &nbsp;&nbsp;{$form.$save.html}            
        </div>
    {/if}


    {* build the print pdf buttons *}
    {if $rows}
        {assign var=print value="_qf_"|cat:$form.formName|cat:"_submit_print"}
        {assign var=pdf   value="_qf_"|cat:$form.formName|cat:"_submit_pdf"}
        {assign var=csv   value="_qf_"|cat:$form.formName|cat:"_submit_csv"}
        {assign var=group value="_qf_"|cat:$form.formName|cat:"_submit_group"}
        {assign var=chart value="_qf_"|cat:$form.formName|cat:"_submit_chart"}
        <table style="border:0;">
            <tr>
                <td>
                    <table class="form-layout-compressed">
                        <tr>
                            <td>{$form.$print.html}&nbsp;&nbsp;</td>
                            <td>{$form.$pdf.html}&nbsp;&nbsp;</td>
                            <td>{$form.$csv.html}&nbsp;&nbsp;</td>                        
                            {if $instanceUrl}
                                <td>&nbsp;&nbsp;&raquo;&nbsp;<a href="{$instanceUrl}">{ts}Existing report(s) from this template{/ts}</a></td>
                            {/if}
                        </tr>
                    </table>
                </td>
                <td>
                    <table class="form-layout-compressed" align="right">                        
                        {if $chartSupported}
                            <tr>
                                <td>{$form.charts.html|crmReplace:class:big}</td>
                                <td align="right">{$form.$chart.html}</td>
                            </tr>
                        {/if}
                        {if $form.groups}
                            <tr>
                                <td>{$form.groups.html|crmReplace:class:big}</td>
                                <td align="right">{$form.$group.html}</td>
                            </tr>
                        {/if}
                    </table>
                </td>
            </tr>
        </table>
    {/if}

    <script type="text/javascript">
        var showBlocks = [];
        var hideBlocks = [];
        {if $criteriaForm}
            {if $rows}
               showBlocks[0] = "id_{$formTpl}_show";
               hideBlocks[0] = "id_{$formTpl}";
            {else}
               hideBlocks[0] = "id_{$formTpl}_show";
               showBlocks[0] = "id_{$formTpl}";
            {/if}
        {/if}

        {if $instanceForm and $rows}
            hideBlocks[1] = "id_{$instanceForm}";
            showBlocks[1] = "id_{$instanceForm}_show";
        {/if}

        {* hide and display the appropriate blocks as directed by the php code *}
        on_load_init_blocks( showBlocks, hideBlocks );
        {if $instanceFormError}
	   showBlocks[0] = "id_{$instanceForm}";
           hideBlocks[0] = "id_{$instanceForm}_show";
 	   on_load_init_blocks( showBlocks, hideBlocks );
        {/if}

	    {literal}
	    function checkGroup( ) {
            var groupSelected = document.getElementById('groups').value;
            if( groupSelected ) { 
                return true;
            }
            alert('{/literal}{ts}Please select valid Group{/ts}{literal}');
            return false;
        }
        {/literal}
    </script>

{/if} {* NO print section ends *}