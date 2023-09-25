{if $pcpStatus eq 'Approved'}
============================
{ts}Your Personal Campaign Page{/ts}

============================

{ts}Your personal campaign page is now available for public access. Congratulations!
Now you can share the page publicly to raise more donations for your fundraising project!{/ts}

{ts}Please log in to the system to get the link to your personal campaign page. You can
then share the link to your personal campaign page on major social media platforms to
encourage your friends and family to participate in this fundraising project!{/ts}:
{$loginUrl}

{* Rejected message *}
{elseif $pcpStatus eq 'Not Approved'}
============================
{ts}Your Personal Campaign Page{/ts}

============================

{ts}Your personal campaign page has been reviewed. There were some issues with the content
which prevented us from approving the page. We are sorry for any inconvenience.{/ts}

{if $pcpNotifyEmailAddress}

{ts}Please contact our site administrator for more information{/ts}:
{$pcpNotifyEmailAddress}
{/if}

{/if}
