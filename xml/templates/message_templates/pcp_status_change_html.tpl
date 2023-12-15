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
    {if $pcpStatus eq 'Approved'}
      <p>{ts}Your Personal Campaign Page is now available for public access.{/ts} {ts}Congratulations!{/ts} {ts}You can now openly share the page to gather more donations for your fundraising campaign!{/ts}</p>
      <p>{ts 1=$pcpInfoURL}Please copy your Personal Campign Page URL %1{/ts}. {ts}Subsequently, you can share your Personal Campaign Page link across various social media platforms to rally friends and family to support this fundraising initiative!{/ts}</p>

      {if $pcpNotifyEmailAddress}
      <p>{ts}Questions? Send email to{/ts}: {$pcpNotifyEmailAddress}</p>
      {/if}

    {elseif $pcpStatus eq 'Not Approved'}
      <p>{ts}Your personal campaign page has been reviewed. There were some issues with the content
which prevented us from approving the page. We are sorry for any inconvenience.{/ts}</p>
      {if $pcpNotifyEmailAddress}
      <p>{ts}Please contact our site administrator for more information{/ts}: {$pcpNotifyEmailAddress}</p>
      {/if}
    {elseif $pcpStatus eq 'Draft'}
      <p>{ts}Your Personal Campaign Page has been reviewed by our administrators, and it has been determined that further modifications are needed.{/ts} {ts}As a result, it has been reverted to a draft status.{/ts}<p>
      {if $pcpNotifyEmailAddress}
      <p>{ts 1=pcpNotifyEmailAddress}We recommend reaching out to the administrator at %1 to understand the reasons for the required changes.{/ts}</p>
      {/if}
      <p>{ts 1=$loginUrl 2=$pcpInfoURL}You can <a href="%1">log in</a> to the system again to <a href="%2">edit the page</a>.{/ts} {ts}Once the editing is completed, you can resubmit it for administrator review.{/ts}</p>
    {/if}
  </td>
  </tr>

</table>
</center>

</body>
</html>