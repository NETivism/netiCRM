<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <title></title>
</head>
<body>

{capture assign=headerStyle}colspan="2" style="text-align: left; padding: 4px; border-bottom: 1px solid #999; background-color: #eee;"{/capture}
{capture assign=labelStyle }style="padding: 4px; border-bottom: 1px solid #999; background-color: #f7f7f7;"{/capture}
{capture assign=valueStyle }style="padding: 4px; border-bottom: 1px solid #999;"{/capture}

<center>
<table width="620" border="0" cellpadding="0" cellspacing="0" id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left;">
  <!-- BEGIN HEADER -->
  <tr>
    <td>
      <div class="receipt-text">{$receipt_text}</div>
    </td>
  </tr>
</table>
</center>

</body>
</html>
