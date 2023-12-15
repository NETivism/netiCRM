{ts 1="$contribPageTitle"}Thanks for creating a personal campaign page in support of %1.{/ts}

{if $pcpStatus eq 'Approved'}

{ts}Your Personal Campaign Page is now available for public access.{/ts} {ts}Congratulations!{/ts} {ts}You can now openly share the page to gather more donations for your fundraising campaign!{/ts}
{ts}Please log in to obtain your Personal Campaign Page link{/ts}. {ts}Subsequently, you can share your Personal Campaign Page link across various social media platforms to rally friends and family to support this fundraising initiative!{/ts}

{elseif $pcpStatus EQ 'Waiting Review'}

{ts}Before you start sharing the Personal Campaign Page and commence fundraising, it needs to obtain approval from the administrator.{/ts}
{ts}Currently, this page is undergoing the approval process.{/t} {ts 1=$pcpNotifyEmailAddress}Once the review is complete, you will soon receive an approval confirmation email from %1.{/ts} {ts}Upon receiving an approval email, you can begin sharing the page and start fundraising!{/ts}

{ts}If you wish to preview the edited fundraising page before administrator approval, please follow these steps:{/ts}

{ts}Login to your account{/ts}
{$loginUrl}

{ts}Preview your personal campaign page{/ts}
{$pcpInfoURL}

{if $pcpNotifyEmailAddress}
{ts}Questions? Send email to{/ts}: {$pcpNotifyEmailAddress}
{/if}