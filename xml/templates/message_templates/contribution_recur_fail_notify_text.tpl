{ts}Recurring Failed Notification{/ts}

{ts 1=$amount 2=$display_name 3=$receive_date 4=$url}The recurring contribution %1 by contributor %2 is failed at %3.( Link: %4 ){/ts}{if $detail}{ts}Additional Details:{/ts}
{foreach from=$detail item=item}
- {$item}
{/foreach}
{/if}
{if $trxn_id}
{ts 1=$trxn_id}If you need more infomation, please check source from payment processor service page by transaction ID: %1{/ts}
{/if}
{ts}You can evaluate whether to notify the contributor for the relevant treatment (for example, due to the credit card expires and need to make online contribution again), or wait for the next time the transaction executing (if this is due to insufficient balance can not pay).{/ts}

{ts}Or you can contact us by our support on https://neticrm.tw/support .{/ts}