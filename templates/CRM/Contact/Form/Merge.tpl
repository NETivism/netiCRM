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
<div class="crm-block crm-form-block crm-contact-merge-form-block">
<div id="help">
{ts}Click <strong>Merge</strong> to move data from the Duplicate Contact on the left into the Main Contact. In addition to the contact data (address, phone, email...), you may choose to move all or some of the related activity records (groups, contributions, memberships, etc.).{/ts} {help id="intro"}
</div>
<div class="action-link-button">
    	<a href="{$flip}" class="button"><i class="zmdi zmdi-swap"></i>{ts}Flip between original and duplicate contacts.{/ts}</a>
      <a id='notDuplicate' class="button" href="#" title={ts}Mark this pair as not a duplicate.{/ts} onClick="processDupes( {$main_cid}, {$other_cid}, 'dupe-nondupe', 'merge-contact', '{$userContextURL}' );return false;"><i class="zmdi zmdi-arrow-split"></i>{ts}Mark this pair as not a duplicate.{/ts}</a>
</div>

{literal}
<style>
table.dedupe-merge td .zmdi-minus, table.dedupe-merge td .zmdi-plus {
  display: block;
}
table.dedupe-merge td .zmdi-minus:before, #dedupe-merge td .zmdi-plus:before {
  display: inline-block;
	margin-right: 6px;
  color: #999;
}
table.dedupe-merge td .zmdi-minus {
  background-color: #ffeef0;
  text-decoration: line-through;
  color: red;
}
table.dedupe-merge td .zmdi-plus {
  background-color: #e6ffed;
}
</style>
{/literal}

<table class="dedupe-merge">
  <tr class="columnheader">
    <th>&nbsp;</th>
    <th><a target="_blank" href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$other_cid"}">{$other_name}&nbsp;<em>{$other_contact_subtype}</em>({$other_cid})<i class="zmdi zmdi-arrow-right-top"></i></a>({ts}duplicate{/ts})</th>
    <th>{ts}Mark All{/ts}<br />{$form.toggleSelect.html} <i class="zmdi zmdi-redo"></i></th>
    <th><a target="_blank" href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$main_cid"}">{$main_name}&nbsp;<em>{$main_contact_subtype}</em>({$main_cid})<i class="zmdi zmdi-arrow-right-top"></i></a>({ts}Reserved{/ts}) </th>
  </tr>
  {foreach from=$rows item=row key=field}
     <tr class="{cycle values="odd-row,even-row"}">
        <td>{$row.title}</td>
        <td>
           {if !is_array($row.other)}
               {$row.other}
           {else}
               {$row.other.fileName}
           {/if} 
        </td>
        <td style='white-space: nowrap'>{if $form.$field}{$form.$field.html} <i class="zmdi zmdi-redo"></i>{else}{ts}n/a{/ts}{/if}</td>
        <td>
        {if $row.title|substr:0:5 == "Email"   OR 
          $row.title|substr:0:7 == "Address" OR 
          $row.title|substr:0:2 == "IM"      OR 
          $row.title|substr:0:6 == "OpenID"  OR 
          $row.title|substr:0:5 == "Phone"}

          {assign var=position  value=$field|strrpos:'_'}
          {assign var=blockId   value=$field|substr:$position+1}
          {assign var=blockName value=$field|substr:14:$position-14}
          {$form.location.$blockName.$blockId.locTypeId.html} 

          {if $blockName eq 'email' || $blockName eq 'phone' }
            <span id="main_{$blockName}_{$blockId}_overwrite" class="{if $row.main}main-row{/if}">
              {$form.location.$blockName.$blockId.operation.html}
            </span>
          {else}
            <span id="main_{$blockName}_{$blockId}_overwrite">
              {if $row.main}({ts}overwrite{/ts})<br />{else}({ts}add new{/ts}){/if}
            </span>
          {/if}
        {/if}{* row.title addr ... *}
          <span id="main_{$blockName}_{$blockId}" class="original-value">
            {if !is_array($row.main)}
              {$row.main}
            {else}
              {$row.main.fileName}
            {/if}
          </span>
        </td>
     </tr>
  {/foreach}
  <tr>
    <th colspan=4 style="background: #777; color: #FFF;">
      {ts}Referenced Record{/ts}
    </th>
  </tr>
  {foreach from=$rel_tables item=params key=paramName}
    {if $paramName eq 'move_rel_table_users'}
    <tr class="{cycle values="even-row,odd-row"}">
      <td><i class="zmdi zmdi-forward"></i> {ts}Move related...{/ts}</td>
      <td>{ts}CMS User{/ts}<a href="{$params.other_url}">{$params.other_title}</a> ({ts}{/ts} {$otherUfName} - {$otherUfId})</td>
      <td style='white-space: nowrap'>{if $otherUfId}{$form.$paramName.html} <i class="zmdi zmdi-redo"></i>{/if}</td>
      <td>{if $mainUfId}<div>{ts}CMS User{/ts} <a href="{$params.main_url}">{$params.main_title}</a> ({$mainUfName} - {$mainUfId})</div>{/if}</td>
    </tr>
    {else}
    <tr class="{cycle values="even-row,odd-row"}">
      <td><i class="zmdi zmdi-forward"></i> {ts}Move related...{/ts}</td><td><a href="{$params.other_url}">{$params.title}</a> ({ts}Contact ID{/ts} {$other_cid})</td>
      <td style='white-space: nowrap'>{$form.$paramName.html} <i class="zmdi zmdi-redo"></i></td>
      <td><div><a href="{$params.main_url}">{$params.title}</a> ({ts}Contact ID{/ts} {$main_cid}){if $form.operation.$paramName.add.html}&nbsp;{$form.operation.$paramName.add.html}{/if}</div></td>
    </tr>
    {/if}
  {/foreach}
</table>
<div class='form-item'>
  <!--<p>{$form.moveBelongings.html} {$form.moveBelongings.label}</p>-->
  <!--<p>{$form.deleteOther.html} {$form.deleteOther.label}</p>-->
</div>
<div class="form-item">
  <div class="messages warning">
    <strong>{ts}WARNING: The duplicate contact record WILL BE DELETED after the merge is complete.{/ts}</strong>
    {if $user}
      <p><strong>{ts}There are Drupal user accounts associated with both the original and duplicate contacts. If you continue with the merge, the user record associated with the duplicate contact will not be deleted, but will be un-linked from the associated contact record (which will be deleted). If that user logs in again, a new contact record will be created for them.{/ts}</strong></p>
    {/if}
    {if $other_contact_subtype}
      <p><strong>The duplicate contact (the one that will be deleted) is a <em>{$other_contact_subtype}</em>. Any data related to this will be lost forever (there is no undo) if you complete the merge.</strong></p>
    {/if}
  </div>
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{literal}
<script type="text/javascript">
cj(document).ready(function(){ 
  var mainLocBlock = {/literal}{$mainLocBlock}{literal};
  cj('span[id$=_overwrite]').each(function(){
    if (cj(this).hasClass('main-row')) {
      cj(this).find('.form-checkbox').show();
    }
    else {
      cj(this).find('.form-checkbox').prop('checked', true);
      cj(this).find('.form-checkbox').hide();
    }
  });
  cj('select[data-location-name]').change(function(){
    var blockname = cj(this).data('location-name');
    var locationId = cj(this).val();
    var mainBlock = mainLocBlock['main_'+blockname+locationId] !== 'undefined' ? mainLocBlock['main_'+blockname+locationId] : '';
    var $block = cj("#main_"+blockname+'_'+cj(this).data('location-id')+'_overwrite');
    var $originalVal = cj("#main_"+blockname+'_'+cj(this).data('location-id'));

    if (mainBlock) {
      $block.find('.form-checkbox').show();
      $originalVal.html(mainBlock);
    }
    else {
      $originalVal.html('');
      $block.find('.form-checkbox').prop('checked', true);
      $block.find('.form-checkbox').hide();
    }
  });
  cj('table td input.form-checkbox').each(function() {
    var ele = null;
    var element = cj(this).attr('id').split('_',3);
    switch ( element['1'] ) {
      case 'addressee':
        var ele = '#' + element['0'] + '_' + element['1'];
        break;
      case 'email':
      case 'postal':
        var ele = '#' + element['0'] + '_' + element['1'] + '_' + element['2'];
        break;
    }

    if( ele ) {
      cj(this).bind( 'click', function() {
        if( cj( this).attr( 'checked' ) ){
          cj('input' + ele ).attr('checked', true );
          cj('input' + ele + '_custom' ).attr('checked', true );
        }
        else {
          cj('input' + ele ).attr('checked', false );
          cj('input' + ele + '_custom' ).attr('checked', false );
        }
      });
    }
  });
    
  cj('[id^="location"][type=checkbox]').change(onChangeAddnewCheckbox);
  cj('[id^="move_"]').change(onChangeOverlayCheckBox);
  doCheckAllIsReplace();

  cj('#toggleSelect').change(function(){
    if(cj(this).attr('checked')){
      alert("{/literal}{ts}WARNING: The duplicate contact record WILL BE DELETED after the merge is complete.{/ts}{literal}");
      
    }
    var is_checked = cj(this).attr('checked')== 'checked';
    cj('[id^="location"][type=checkbox][disabled!=disabled]').each(function(){
      cj(this).attr('checked',is_checked );
    })
    setTimeout(checkDataIsErase,100);
  })
});


/**
 * Check all the ==[]==> checkbox 
 * Only do once when page ready.
 */
function doCheckAllIsReplace(){
  cj('[id^="move_"]').each(function(){
    var cj_this = cj(this);
    var cj_left_td = cj_this.parent().prev();
    var cj_right_td = cj_this.parent().next();

    if(cj_right_td.text().split(/\s+/)[1] == "" && cj_left_td.text().split(/\s+/)[1] != ""){
      cj_this.trigger('click');
    }
    else if(cj_this.attr('id').match(/^move_location_/)){
      cj_this.trigger('click');
      var right_check_box = cj_right_td.find('input[type="checkbox"]')
      right_check_box.attr('checked',true);
      right_check_box.trigger('change');
    }
    else{
      var cj_left_left_td = cj_left_td.prev();
      if(cj_left_left_td.text().match("{/literal}{ts}Move related...{/ts}{literal}")){
        cj_this.attr('checked',true);
        checkDataIsErase(cj_this);
      }
    }
  })
}

/**
 * When click ==[]==> checkbox
 */
function onChangeOverlayCheckBox(){
  var cj_this = cj(this);
  var cj_left_td = cj_this.parent().prev();
  var cj_right_td = cj_this.parent().next();


  if(cj_this.attr('id').match(/^move_location_/)){
    if(cj_right_td.find('span').text() !== ""){
      var right_check_box = cj_right_td.find('input[type="checkbox"]');
      if (cj_this.attr("checked") == 'checked') {
        right_check_box.attr('checked', true);
        setTimeout(function(){
          right_check_box.trigger('change');
        }, 50);
      }
      else {
        right_check_box.attr('checked', false);
      }
    }
  }

  checkDataIsErase(cj_this);
  cj('#toggleSelect').removeAttr('checked');
}

/**
 * When click "Add new " checkbox on location type field
 */
function onChangeAddnewCheckbox(){
  $this = cj(this);
  if($this.attr('checked')){
    $this.closest('td').prev().find('[id^="move_"]').attr('checked',true);
  }
  checkDataIsErase($this);
}


/**
 * Check if right column need to show "will be erased" or not.
 * @param  jQuery_element cjCheckboxElement The cj checkbox element which want to check.
*                                           If null. than check all the ==[]==> element.
 */
function checkDataIsErase(cjCheckboxElement){
  if(!cjCheckboxElement){
    cj('[id^="move_"]').each(function(){
      checkDataIsErase(cj(this));  
    })
    
    return ;
  }

  var cj_left_td = cjCheckboxElement.closest('td').prev();
  var cj_right_td = cjCheckboxElement.closest('td').next();

  if(cjCheckboxElement.length <= 0){
    console.log('The query element cjCheckboxElement have error, Please check your code.');
    return ;
  }


  var is_erase = 0;

  if(cjCheckboxElement.attr('id').match(/^move_/) && cjCheckboxElement.attr('checked')){
    if(!cjCheckboxElement.attr('id').match(/^move_location_/)){
      is_erase = 1;
    }
    else if(cj_right_td.find('input[type="checkbox"]').length > 0 && typeof cj_right_td.find('input[type="checkbox"]').attr('checked') == "undefined"){
      is_erase = 1;
    }
  }
  else if(cjCheckboxElement.attr('id').match(/^location/)){
    if (cjCheckboxElement.attr("checked")) {
      is_erase = 2;
    }
    else {
      is_erase = 1;
    }

    var cj_left_td = cjCheckboxElement.closest('td').prev().prev();
    var cj_right_td = cjCheckboxElement.closest('td');
  }
  
  cj_right_td.find('.zmdi-plus').remove();
  cj_right_td.find('.original-value').show().css('display', 'block');
  if (is_erase == 1) {
    cj_right_td.find('.original-value').addClass('zmdi zmdi-minus');
    cj_right_td.append('<div class="zmdi zmdi-plus">'+cj_left_td.html()+'</div>')
  }
  else if (is_erase == 2) { // append
    cj_right_td.find('.original-value').hide();
    cj_right_td.append('<div class="zmdi zmdi-plus">'+cj_left_td.html()+'</div>')
  }
  else {
    cj_right_td.find('.original-value').removeClass('zmdi-minus');
    cj_right_td.find('.zmdi-plus').remove();
  }
}

</script>
{/literal}

{* process the dupe contacts *}
{include file="CRM/common/dedupe.tpl"}
