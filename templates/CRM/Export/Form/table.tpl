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
{* Export Wizard - Data Mapping table used by MapFields.tpl and Preview.tpl *}
 <div id="map-field">
    {strip}
    <table>
        {if $loadedMapping}
            <tr class="columnheader-dark"><th colspan="4">{ts 1=$savedName}Using Field Mapping: %1{/ts}</td></tr>
        {/if}
        <tr class="columnheader">
            <th>{ts}Fields to Include in Export File{/ts}</th>
        </tr>
        {*section name=cols loop=$columnCount*}
        {section name=cols loop=$columnCount.1}
            {assign var="i" value=$smarty.section.cols.index}
            <tr class="draggable">
                <td class="form-item even-row">
                   {$form.mapper.1[$i].html}
                  <div class="drag-handler"></div>
                </td>
            </tr>
        {/section}
    
        <tr>
           <td class="form-item even-row underline-effect">
               {$form.addMore.1.html}
           </td>
        </tr>            
    </table>
    {/strip}


    <div>
	{if $loadedMapping}
            <span>{$form.updateMapping.html}{$form.updateMapping.label}&nbsp;&nbsp;&nbsp;</span>
	{/if}
	<span>{$form.saveMapping.html}{$form.saveMapping.label}</span>
    <div id="saveDetails" class="form-item">
      <table class="form-layout-compressed">
         <tr><td class="label">{$form.saveMappingName.label}</td><td>{$form.saveMappingName.html}</td></tr>
         <tr><td class="label">{$form.saveMappingDesc.label}</td><td>{$form.saveMappingDesc.html}</td></tr>
      </table>
    </div>
	

	<script type="text/javascript">
         {if $mappingDetailsError }
            show('saveDetails');    
         {else}
    	    hide('saveDetails');
         {/if}

	     {literal}   
 	     function showSaveDetails(chkbox) {
    		 if (chkbox.checked) {
    			document.getElementById("saveDetails").style.display = "block";
    			document.getElementById("saveMappingName").disabled = false;
    			document.getElementById("saveMappingDesc").disabled = false;
    		 } else {
    			document.getElementById("saveDetails").style.display = "none";
    			document.getElementById("saveMappingName").disabled = true;
    			document.getElementById("saveMappingDesc").disabled = true;
    		 }
         }
        cj(document).ready(function($){
          $('Select[id^="mapper[1]"][id$="[1]"]').addClass('huge');
          var mappers = $("#map-field select").filter(function(){
            var n = $(this).attr('name');
            if(n.match(/mapper\[1\]\[\d+\]\[0\]/)){
              return true;
            }
            return false;
          });
          mappers.each(function(){
            var n = $(this).attr('name');
            var name = n.replace(/\[0\]$/, '[1]').replace(/\[/g, '\\[').replace(/\]/g,'\\]');
            $("select[name="+name+"]:visible").chosen({
              "search_contains": true,
              "placeholder_text": "{/literal}{ts}-- Select --{/ts}{literal}",
              "no_results_text": "{/literal}{ts}No matches found.{/ts}{literal}"
            }).hide();
          });
          mappers.bind('change', function( ) {
            // trigger change first
            var ochange = $('<div>').append($(this).clone()).html();
            var m = ochange.match(/swapOptions\([^)]+\);/);
            if(m[0]){
              eval(m[0]);
            }
            // now start chosen
            var n = $(this).attr('name');
            var name = n.replace(/\[0\]$/, '[1]').replace(/\[/g, '\\[').replace(/\]/g,'\\]');
            $("select[name="+name+"]").chosen({
              "search_contains": true,
              "placeholder_text": "{/literal}{ts}-- Select --{/ts}{literal}",
              "no_results_text": "{/literal}{ts}No matches found.{/ts}{literal}"
            }).hide();
            $("select[name="+name+"]").trigger("liszt:updated");
          });
        });
        var tbody = document.getElementById('map-field').querySelector('tbody');
        Sortable.create(tbody, {
          handle:'.drag-handler',
          draggable:'tr.draggable',
          onUpdate: function(event){
            var elem = event.srcElement.querySelectorAll('tr.draggable');
            elem.forEach(function(item, i){
              var input = item.querySelector('[name^="mapper"]');
              if(input){
                var k = /^mapper\[\d+\]\[(\d+)\]/.exec(input.name)[1];
                var weight_item = document.querySelector('[name^="weight[1]['+k+']"]');
                weight_item.value = i;
              }
            });
          }
        });
       {/literal}	     
	</script>
    </div>

 </div>
