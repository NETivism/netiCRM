{if $pcpStatus eq 'Approved'}
  {ts}Your Personal Campaign Page is now available for public access.{/ts} {ts}Congratulations!{/ts} {ts}You can now openly share the page to gather more donations for your fundraising campaign!{/ts}
  {ts 1=$pcpInfoURL}Please copy your Personal Campign Page URL %1{/ts}. {ts}Subsequently, you can share your Personal Campaign Page link across various social media platforms to rally friends and family to support this fundraising initiative!{/ts}

  {if $pcpNotifyEmailAddress}
  {ts}Questions? Send email to{/ts}: {$pcpNotifyEmailAddress}
  {/if}

{elseif $pcpStatus eq 'Not Approved'}
  {ts}Your personal campaign page has been reviewed. There were some issues with the content
which prevented us from approving the page. We are sorry for any inconvenience.{/ts}
  {if $pcpNotifyEmailAddress}
  {ts}Please contact our site administrator for more information{/ts}: {$pcpNotifyEmailAddress}
  {/if}
{elseif $pcpStatus eq 'Draft'}
  {ts}Your Personal Campaign Page has been reviewed by our administrators, and it has been determined that further modifications are needed.{/ts} {ts}As a result, it has been reverted to a draft status.{/ts}
  {if $pcpNotifyEmailAddress}
    {ts 1=pcpNotifyEmailAddress}We recommend reaching out to the administrator at %1 to understand the reasons for the required changes.{/ts}
  {/if}
  {ts}Afterward, you can log in to the system again to edit the page.{/ts} {ts}Once the editing is completed, you can resubmit it for administrator review.{/ts}
  1. {ts}Login to your account{/ts}: {$loginUrl}
  2. {ts}Edit personal campaign page{/ts}: {$pcpInfoURL}
{/if}