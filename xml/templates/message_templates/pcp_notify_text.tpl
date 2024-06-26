===========================================================
{ts}Personal Campaign Page Notification{/ts}

===========================================================
{ts}Action{/ts}: {if $mode EQ 'Update'}{ts}Updated personal campaign page{/ts}{else}{ts}New personal campaign page{/ts}{/if}
{ts}Personal Campaign Page Title{/ts}: {$pcpTitle}
{ts}Current Status{/ts}: {$pcpStatus}
{ts}View Page{/ts}: {capture assign=pcpURL}{crmURL p="civicrm/contribute/pcp/info" q="reset=1&id=`$pcpId`" h=0 a=1}{/capture}
{$pcpURL}

{ts}Supporter{/ts}: {$supporterName}
{$supporterUrl}

{ts}Contribution Page{/ts}: {$contribPageTitle}
{$contribPageUrl}

{ts}Manage Personal Campaign Pages{/ts}:
{$managePCPUrl}