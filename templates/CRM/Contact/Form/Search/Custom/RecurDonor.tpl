{* handle enable/disable actions*}
{include file="CRM/common/enableDisable.tpl"}
<div class="crm-block crm-form-block crm-contact-custom-search-form-block">
<div class="crm-custom-search-description">
  <p>{ts}Recurring donors matter! Explore your past, present or future recurring donors to support your organization.{/ts}</p>
</div>
<div class="crm-accordion-wrapper crm-custom_search_form-accordion crm-accordion-{if !$rows}open{else}closed{/if}">
    <div class="crm-accordion-header crm-master-accordion-header">
      <div class="zmdi crm-accordion-pointer"></div>
      {ts}Edit Search Criteria{/ts}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
        <table class="form-layout-compressed">
            {* Loop through all defined search criteria fields (defined in the buildForm() function). *}
            {foreach from=$elements item=element}
                <tr class="crm-contact-custom-search-form-row-{$element}">
                    <td class="label">{$form.$element.label}</td>
                    {capture assign=is_date}{$element|substr:-5}{/capture}
                    {capture assign=is_low}{$element|substr:-4}{/capture}
                    {if $is_date eq '_date'}
                        <td>{include file="CRM/common/jcalendar.tpl" elementName=$element}</td>
                    {elseif $is_low eq '_low'}
                        {capture assign=elehigh}{$element|substr:0:-4}_high{/capture}
                        <td>{$form.$element.html} <label>{$form.$elehigh.label}</label> {$form.$elehigh.html}</td>
                    {else}
                        <td>{$form.$element.html}</td>
                    {/if}
                </tr>
            {/foreach}
        </table>
        {include file="CRM/common/chosen.tpl" selector="select#contribution_page"}
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
</div><!-- /.crm-form-block -->

{if $rowsEmpty || $rows}
<div class="crm-content-block">
{if $rowsEmpty}
    {include file="CRM/Contact/Form/Search/Custom/EmptyResults.tpl"}
{/if}

{if $summary}
  {foreach from=$summary item=summary_item}
  <div><label>{$summary_item.label}</label>: {$summary_item.value}</div>
  {/foreach}
{/if}

{if $rows}
  <div class="crm-results-block">
    {* Search request has returned 1 or more matching rows. Display results and collapse the search criteria fieldset. *}
        {* This section handles form elements for action task select and submit *}
       <div class="crm-search-tasks">        
        {include file="CRM/Contact/Form/Search/ResultTasks.tpl"}
    </div>
        {* This section displays the rows along and includes the paging controls *}
      <div class="crm-search-results">

        {include file="CRM/common/pager.tpl" location="top"}

        {* Include alpha pager if defined. *}
        {if $atoZ}
            {include file="CRM/common/pagerAToZ.tpl"}
        {/if}
        
        {strip}
        <table class="selector" summary="{ts}Search results listings.{/ts}">
            <thead class="sticky">
                <tr>
                <th scope="col" title="Select All Rows">{$form.toggleSelect.html}</th>
                {foreach from=$columnHeaders item=header}
                    <th scope="col">
                        {if $header.sort}
                            {assign var='key' value=$header.sort}
                            {$sort->_response.$key.link}
                        {else}
                            {$header.name}
                        {/if}
                    </th>
                {/foreach}
                <th>&nbsp;</th>
                </tr>
            </thead>

            {counter start=0 skip=1 print=false}
            {foreach from=$rows item=row}
                <tr id='rowid{$row.contact_id}' class="{cycle values="odd-row,even-row"}">
                    {assign var=cbName value=$row.checkbox}
                    <td>{$form.$cbName.html}</td>
                    {foreach from=$columnHeaders item=header}
                        {assign var=fName value=$header.sort}
                        {if $fName eq 'sort_name'}
                            <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}">{$row.sort_name}</a></td>
                        {else}
                            <td>{$row.$fName}</td>
                        {/if}
                    {/foreach}
                    <td>{$row.action}</td>
                </tr>
            {/foreach}
        </table>
        {/strip}

        {include file="CRM/common/pager.tpl" location="bottom"}
        </p>
    {* END Actions/Results section *}
    </div>
    </div>
{/if}

</div>
{/if}
{literal}
<script type="text/javascript">
cj(function($) {
  $().crmaccordions(); 
  $("#search_criteria").change(function(){
    if($(this).val() == 'never') {
      $("tr.crm-contact-custom-search-form-row-amount_low, tr.crm-contact-custom-search-form-row-contribution_page_id, tr.crm-contact-custom-search-form-row-contribution_type_id").hide();
    }
    else {
      $("tr.crm-contact-custom-search-form-row-amount_low, tr.crm-contact-custom-search-form-row-contribution_page_id, tr.crm-contact-custom-search-form-row-contribution_type_id").show();
    }
  });
});
</script>
{/literal}