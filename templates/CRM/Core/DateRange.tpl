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
{*this is included inside the table*}
{assign var=relativeName   value=$fieldName|cat:"_relative"}
<td >{$form.$relativeName.html}</td>
<td>   
    <span id="absolute_{$relativeName}"> 
        {assign var=fromName   value=$fieldName|cat:"_from"}
        {$form.$fromName.label}
        {include file="CRM/common/jcalendar.tpl" elementName=$fromName} 
        {assign var=toName   value=$fieldName|cat:"_to"}
        {$form.$toName.label}
        {include file="CRM/common/jcalendar.tpl" elementName=$toName} 
    </span>   
            
</td>
<script type="text/javascript">
{literal}
cj(document).ready(function($){
  var id = "{/literal}{$relativeName}{literal}";
  $("#"+id).change(function(){
    var val = this.value;
    if (val == "0") {
      $('#absolute_' + id).show();
    }
    else{
      $('#absolute_' + id).hide();
    }
  });
  $("#"+id).trigger('change');
});
{/literal}        
</script>
