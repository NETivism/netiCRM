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
<div class="form-item">
 <fieldset><legend>{ts}Find Events{/ts}</legend>
  <table class="form-layout">
    <tr>
        <td class="label">{$form.title.label}</td>
        <td>{$form.title.html|crmReplace:class:twenty}
             <div class="description font-italic">
                    {ts}Complete OR partial Event name.{/ts}
             </div>
             <div style="height: auto; vertical-align: bottom">{$form.eventsByDates.html}</div>
        </td>
        <td rowspan="2"><label>{ts}Event Type{/ts}</label>
            <div class="listing-box">
                {foreach from=$form.event_type_id item="event_val"}
                <div class="{cycle values="odd-row,even-row"}">
                    {$event_val.html}
                </div>
                {/foreach}
            </div>
        </td>
        <td class="right" rowspan="2">&nbsp;{$form.buttons.html}</td>  
    </tr>
  
    <tr>
       <td></td>
       <td colspan="2">
       <table class="form-layout-compressed" id="id_fromToDates">
        <tr>
            <td>{$form.start_date.label}</td>
            <td>{include file="CRM/common/jcalendar.tpl" elementName=start_date}</td>
        </tr>
        <tr>
            <td>{$form.end_date.label}</td>
            <td>{include file="CRM/common/jcalendar.tpl" elementName=end_date}</td>             
        </tr>
      </table> 
    </td></tr>  
  </table>
</fieldset>
</div>

{include file="CRM/common/showHide.tpl"}

{literal} 
<script type="text/javascript">
if ( document.getElementsByName('eventsByDates')[1].checked ) {
   show( 'id_fromToDates', 'block' );
}
</script>
{/literal} 
