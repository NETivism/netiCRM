<!DOCTYPE html PUBLIC "-//W4C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <title></title>
</head>
<body>
{capture assign=headerStyle}colspan="2" style="text-align: left; padding: 4px; border-bottom: 1px solid #999; background-color: #eee;"{/capture}
{capture assign=labelStyle }style="padding: 4px; border-bottom: 1px solid #999; background-color: #f7f7f7;"{/capture}
{capture assign=valueStyle }style="padding: 4px; border-bottom: 1px solid #999;"{/capture}

<center>
<table width="620" border="0" cellpadding="0" cellspacing="0" id="crm-batch_complete" style="font-family: Arial, Verdana, sans-serif; text-align: left;"> 

  <tr>
    <td {$headerStyle} colspan="2">
      {ts}Summary{/ts}
    </td>
  </tr>

  <tr>
    <td {$labelStyle}>
      {ts}Create Date{/ts}
    </td>
    <td {$valueStyle}>
      {$created_date|crmDate}
    </td>
  </tr>

  <tr>
    <td {$labelStyle}>
      {ts}Complete Date{/ts}
    </td>
    <td {$valueStyle}>
      {$modified_date|crmDate}
    </td>
  </tr>

  <tr>
    <td {$labelStyle}>
      {ts}Expire Date{/ts}
    </td>
    <td {$valueStyle}>
      {$expire_date|crmDate}
    </td>
  </tr>


  {if $total}
  <tr>
    <td {$labelStyle}>
      {ts}Total{/ts}
    </td>
    <td {$valueStyle}>
      {$total} {ts}rows{/ts}
    </td>
  </tr>
  {/if}

  <tr>
    <td {$headerStyle} colspan="2">
      {ts}You can check result by login to the website.{/ts}
    </td>
  </tr>
  <tr>
    <td {$label} colspan="2">
      <a href="{crmURL p='civicrm/admin/batch' q="reset=1&id=`$batch_id`" a=true h=0 fe=1}">{ts}Link{/ts}</a><br>
      {crmURL p='civicrm/admin/batch' q="reset=1&id=`$batch_id`" a=true h=0 fe=1}
    </td>
  </tr>
</table>
</center>
</body>
</html>