<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
 <title></title>
</head>
<body>

<center>
 <table width="620" border="0" cellpadding="0" cellspacing="0" id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left;">

  <!-- BEGIN HEADER -->
  <!-- You can add table row(s) here with logo or other header elements -->
  <!-- END HEADER -->

  <!-- BEGIN CONTENT -->

  <tr>
   <td>

    <h1>{ts}Your Personal Campaign Page{/ts}</h1>

    {if $pcpStatus eq 'Approved'}

     <p>{ts}Your personal fundraising page is now available for public access. Congratulations! Now you can share the page publicly to raise more donations for your fundraising project!{/ts}</p>
     <p>{ts}Please <a href="{$loginUrl}">log in</a> to the system to get the link to your personal fundraising page. You can then share the link to your personal fundraising page on major social media platforms to encourage your friends and family to participate in this fundraising project!{/ts}</p>

    {elseif $pcpStatus eq 'Not Approved'}

     <p>{ts}Your personal campaign page has been reviewed. There were some issues with the content
which prevented us from approving the page. We are sorry for any inconvenience.{/ts}</p>
     {if $pcpNotifyEmailAddress}
      <p>{ts}Please contact our site administrator for more information{/ts}: {$pcpNotifyEmailAddress}</p>
     {/if}

    {/if}

   </td>
  </tr>

 </table>
</center>

</body>
</html>
