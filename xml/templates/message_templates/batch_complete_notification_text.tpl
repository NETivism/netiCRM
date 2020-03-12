{ts}Summary{/ts}
===========================================================

{ts}Create Date{/ts}: {$created_date|crmDate}
{ts}End Date{/ts}: {$modified_date|crmDate}
{ts}Expired Date{/ts}: {$expire_date|crmDate}
{if $total}{ts}Total{/ts}: {$total} {ts}rows{/ts}{/if}

===========================================================
{ts}You can check result by login to the website.{/ts}
{crmURL p='civicrm/admin/batch' q="reset=1&id=`$batch_id`" a=true h=0 fe=1}