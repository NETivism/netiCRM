{ts}Dear supporter{/ts},
{ts 1="$contribPageTitle"}Thanks for creating a personal campaign page in support of %1.{/ts}

{if $pcpStatus eq 'Approved'}
====================
{ts}Promoting Your Page{/ts}

====================
{if $isTellFriendEnabled}

{ts}You can begin your fundraising efforts using our "Tell a Friend" form{/ts}:

1. {ts}Login to your account at{/ts}:
{$loginUrl}

2. {ts}Click or paste this link into your browser and follow the prompts{/ts}:
{$pcpTellFriendURL}
{else}

{ts}Send email to family, friends and colleagues with a personal message about this campaign.{/ts}
{ts}Include this link to your fundraising page in your emails{/ts}:
{$pcpInfoURL}
{/if}

===================
{ts}Managing Your Page{/ts}

===================
{ts}Whenever you want to preview, update or promote your page{/ts}:
1. {ts}Login to your account at{/ts}:
{$loginUrl}

2. {ts}Click or paste this link into your browser to go to your page{/ts}:
{$pcpInfoURL}

{ts}When you view your campaign page WHILE LOGGED IN, the page includes links to edit
your page, tell friends, and update your contact info.{/ts}


{elseif $pcpStatus EQ 'Waiting Review'}
{ts}Before you can share your personal campaign page and start fundraising, it needs to be approved by the administrator.{/ts}

{ts 1=$pcpNotifyEmailAddress}Currently, your page is in the review process. Once the review is complete, you will soon receive an approval email from %1. After receiving the email, you can share your page and start fundraising!{/ts}

{ts}If you would like to preview your edited campaign page before the administrator approves it, please follow these steps{/ts}:

1. {ts}Log in to the system to preview your personal campaign page.{/ts}:
{$loginUrl}

2. {ts}Click or paste this link into your browser{/ts}:
{$pcpInfoURL}

{/if}
