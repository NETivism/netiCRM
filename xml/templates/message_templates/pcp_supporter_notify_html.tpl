<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
<table width="620" border="0" cellpadding="0" cellspacing="0" id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left;">

  <!-- BEGIN HEADER -->
  <!-- You can add table row(s) here with logo or other header elements -->
  <!-- END HEADER -->

  <!-- BEGIN CONTENT -->

  <tr>
    <td>
    <p>{ts 1="$contribPageTitle"}Thanks for creating a personal campaign page in support of %1.{/ts}</p>
    </td>
  </tr>

  {if $pcpStatus eq 'Approved'}
    <tr>
      <td>
      <p>{ts}Your Personal Campaign Page is now available for public access.{/ts} {ts}Congratulations!{/ts} {ts}You can now openly share the page to gather more donations for your fundraising campaign!{/ts}</p>
      <p>{ts 1=$pcpInfoURL}Please copy your Personal Campign Page URL %1{/ts}. {ts}Subsequently, you can share your Personal Campaign Page link across various social media platforms to rally friends and family to support this fundraising initiative!{/ts}</p>
      </td>
    </tr>

  {elseif $pcpStatus eq 'Waiting Review'}
    <tr>
      <td>
      <p>{ts}Before you start sharing the Personal Campaign Page and commence fundraising, it needs to obtain approval from the administrator.{/ts}</p>
      <p>{ts}Currently, this page is undergoing the approval process.{/ts} {ts 1=$pcpNotifyEmailAddress}Once the review is complete, you will soon receive an approval confirmation email from %1.{/ts} {ts}Upon receiving an approval email, you can begin sharing the page and start fundraising!{/ts}</p>

      <p>{ts}If you wish to preview the edited fundraising page before administrator approval, please follow these steps:{/ts}</p>
      <ol>
        <li><a href="{$loginUrl}">{ts}Login to your account{/ts}</a></li>
        <li><a href="{$pcpInfoURL}">{ts}Preview your personal campaign page{/ts}</a></li>
      </ol>

      {if $pcpNotifyEmailAddress}
        <tr>
          <td>
          <p>{ts}Questions? Send email to{/ts}: {$pcpNotifyEmailAddress}</p>
          </td>
        </tr>
      {/if}
    </td>
    </tr>
  {elseif $pcpStatus eq 'Draft'}
    <tr>
      <td>
      <p>{ts}You have currently saved this page as a draft and it has not been submitted yet.{/ts}</p>
      <p>{ts 1=$loginUrl 2=$pcpInfoURL}To continue editing this page, please <a href="%1">log in</a> then you can further edit <a href="%2">your Personal Campaign Page</a>.{/ts}</p>
      </td>
    </tr>
  {/if}

</table>
</center>

</body>
</html>