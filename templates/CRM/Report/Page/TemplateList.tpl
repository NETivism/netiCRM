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
<div class="description">
{ts}Create reports for your users from any of the report templates listed below. Click on a template titles to get started. Click Existing Report(s) to see any reports that have already been created from that template.{/ts}
</div>
<div class="accordion ui-accordion ui-widget ui-helper-reset" style="width:99%">
{strip}
{if $list}
    {foreach from=$list item=rows key=report}		
	<h3 class="head"><span class="ui-icon ui-icon-triangle-1-e"></span>
		<a href="#">{if $report}{if $report EQ 'Contribute'}{ts}Contribution{/ts}{else}{$report}{/if}{else}Contact{/if} Report Templates</a>
	</h3>
	<div id="{$report}" class="ui-accordion-content boxBlock ui-corner-bottom">
	    <table class="report-layout">
		{foreach from=$rows item=row}
		    <tr>
			<td style="width:35%;">
			    <a href="{$row.url}" title="{ts}Create report from this template{/ts}">&raquo; <strong>{$row.title}</strong></a>
			    {if $row.instanceUrl}
				<div style="font-size:10px;text-align:right;margin-top:3px;">
				    <a href="{$row.instanceUrl}">{ts}Existing Report(s){/ts}</a>
				</div>
			    {/if}
			</td>
			<td style="cursor:help;">
			    {$row.description}
			</td>
		    </tr>
		{/foreach}
	    </table>
	</div>
	<div class="spacer"> </div>
    {/foreach}
{else}
    <div class="messages status">
        <dl>
            <dt>
                <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
            </dt>
            <dd>
                {ts}There are currently no Report.{/ts}
            </dd>
        </dl>
    </div>
{/if}
{/strip}
</div>
{literal}
<script type="text/javascript">

cj(function() {
	cj('.accordion .head').addClass( "ui-accordion-header ui-helper-reset ui-state-default ui-corner-all ");
	cj('.accordion .head').hover( function() { cj(this).addClass( "ui-state-hover");
	}, function() { cj(this).removeClass( "ui-state-hover");
}).bind('click', function() { 
	var checkClass = cj(this).find('span').attr( 'class' );
	var len        = checkClass.length;
	if ( checkClass.substring( len - 1, len ) == 's' ) {
		cj(this).find('span').removeClass().addClass('ui-icon ui-icon-triangle-1-e');
		cj("span#help"+cj(this).find('span').attr('id')).hide();
	} else {
		cj(this).find('span').removeClass().addClass('ui-icon ui-icon-triangle-1-s');
		cj("span#help"+cj(this).find('span').attr('id')).show();
	}
	cj(this).next().toggle(); return false; }).next().hide();

	cj('div.accordion div.ui-accordion-content').each(function() {
		cj(this).parent().find('h3 span').removeClass( ).addClass('ui-icon ui-icon-triangle-1-s');
		cj(this).show();
	});
});


</script>
{/literal}
