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
{* template for search builder *}
 <div id="map-field">
  {strip}
     {section start=1 name=blocks loop=$blockCount}
       {assign var="x" value=$smarty.section.blocks.index}
       <div class="crm-search-block">
    <h3>{if $x eq 1}{ts}Include contacts where{/ts}{else}{ts}or{/ts}{/if}</h3>
	<table>
        {section name=cols loop=$columnCount[$x]}
            {assign var="i" value=$smarty.section.cols.index}
            <tr>
                <td class="form-item even-row">
                    {$form.mapper[$x][$i].html}
                    {$form.operator[$x][$i].html}
                    &nbsp;&nbsp;{$form.value[$x][$i].html}
                </td>
            </tr>
        {/section}
    
         <tr>
           <td class="form-item even-row underline-effect">
               {$form.addMore[$x].html}
           </td>
         </tr>            
       </table>
      </div>
    {/section}
    <div class="underline-effect">{$form.addBlock.html}</div> 
  {/strip}
 </div>
<script>
  {literal}
  cj(document).ready(function($){
    $('Select[id^="mapper[1]"][id$="[1]"]').addClass('huge');
    var mappers = $("#map-field select").filter(function(){
      var n = $(this).attr('name');
      if(n.match(/mapper\[\d+\]\[\d+\]\[0\]/)){
        return true;
      }
      return false;
    });
    mappers.each(function(){
      var n = $(this).attr('name');
      var name = n.replace(/\[0\]$/, '[1]').replace(/\[/g, '\\[').replace(/\]/g,'\\]');
      $("select[name="+name+"]:visible").select2({
        "allowClear": true,
        "dropdownAutoWidth": true,
        "placeholder": "{/literal}{ts}-- Select --{/ts}{literal}",
        "language": "{/literal}{if $config->lcMessages}{$config->lcMessages|replace:'_':'-'}{else}en{/if}{literal}"
      }).hide();
    });
    $(document).on('select2:open', () => {
      var inputField = document.querySelector('.select2-search__field');
      if (inputField) {
        inputField.placeholder='{/literal}{ts}Input search keywords{/ts}{literal}';
        setTimeout(() => {
          inputField.focus();
        }, 100);
      }
    });
    mappers.bind('change', function( ) {
      // trigger change first
      var ochange = $('<div>').append($(this).clone()).html();
      var m = ochange.match(/swapOptions\([^)]+\);/);
      if(m[0]){
        eval(m[0]);
      }
      // now start select2
      var n = $(this).attr('name');
      var name = n.replace(/\[0\]$/, '[1]').replace(/\[/g, '\\[').replace(/\]/g,'\\]');
      console.log(name);
      $("select[name="+name+"]").select2({
        "dropdownAutoWidth": true,
        "placeholder": "{/literal}{ts}-- Select --{/ts}{literal}",
        "language": "{/literal}{if $config->lcMessages}{$config->lcMessages|replace:'_':'-'}{else}en{/if}{literal}"
      }).hide();
      $(document).on('select2:open', () => {
        var inputField = document.querySelector('input.select2-search__field');
        if (inputField) {
        inputField.placeholder='{/literal}{ts}Input search keywords{/ts}{literal}';
        setTimeout(() => {
          inputField.focus();
        }, 100);
      }
      });
      $("select[name="+name+"]").trigger("liszt:updated");
    });
  });
 {/literal}
  {if $config->lcMessages eq 'zh_TW'}
    {* this will compitable with drupal 6-7-9 *}
    {* parameter library will use library name pree-defined in civicrm.module *}
    {js src=packages/jquery/plugins/jquery.select2.zh-TW.js library=civicrm/civicrm-js-zh-tw group=999 weight=998}{/js}
  {/if}

</script>
