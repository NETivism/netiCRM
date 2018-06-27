<div class="crm-block crm-form-block crm-contact-custom-search-form-block">
<div class="crm-custom-search-description">
  <p>{ts}Best list for increase your recurring contribution pool. If someone already donated multiple times but not your recurring contribution donors, this is a good chance let them become your long-term donors.{/ts} {ts}You can filter specific range of date of these people. Invite them to become your recurring donor.{/ts}</p>
  <p>{ts}This search result will exclude exists online recurring donors (can not exclude offline donors).{/ts}</p>
</div>
<div class="crm-accordion-wrapper crm-custom_search_form-accordion crm-accordion-{if !$rows}open{else}closed{/if}">
    <div class="crm-accordion-header crm-master-accordion-header">
      <div class="zmdi crm-accordion-pointer"></div>
      {ts}Edit Search Criteria{/ts}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
      <div>
      {ts 1=$form.contribution_count.html}Single donation over %1 times{/ts} <span class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</span>
      </div>
      <div>
      {$form.receive_date_from.label}
      {include file="CRM/common/jcalendar.tpl" elementName=receive_date_from} <span>{$form.receive_date_to.label}</span>
      {include file="CRM/common/jcalendar.tpl" elementName=receive_date_to}
      </div>
    </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
</div><!-- /.crm-form-block -->

{if $rowsEmpty || $rows}
<div class="crm-content-block">
{if $rowsEmpty}
    {include file="CRM/Contact/Form/Search/Custom/EmptyResults.tpl"}
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
cj(function() {
   cj().crmaccordions(); 
});
</script>
{/literal}
