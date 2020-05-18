
<script>
window.dataLayer = window.dataLayer || [];
{if $dataLayerType == 'purchase'}{literal}
window.dataLayer.push({
  'ecommerce': {
    'purchase': {
      'actionField': {
        'id': '{/literal}{$transaction_id}{literal}',
        'revenue': '{/literal}{$total_amount}{literal}',
      },
      'products': [
        { 
        'name': '{/literal}{$product_name}{literal}',
        'id': '{/literal}{$product_id}{literal}',
        'price': '{/literal}{$product_amount}{literal}',
        'category': '{/literal}{$product_category}{literal}',
        'quantity': {/literal}{$product_quantity}{literal},
        }
      ]
    }
  }
});
window.dataLayer.push({
  'transactionId': '{/literal}{$transaction_id}{literal}',
  'transactionTotal': '{/literal}{$total_amount}{literal}',
  'transactionProducts': [
    { 
      'name': '{/literal}{$product_name}{literal}',
      'sku': '{/literal}{$product_id}{literal}',
      'price': '{/literal}{$product_amount}{literal}',
      'category': '{/literal}{$product_category}{literal}',
      'quantity': {/literal}{$product_quantity}{literal},
    }
  ]
});
{/literal}{/if}
{if $dataLayerType == 'refund'}{literal}
window.dataLayer.push({
  'ecommerce': {
    'refund': {
      'actionField': {
        'id': '{/literal}{$trxn_id}{literal}'
      }
    }
  }
});
{/literal}{/if}
</script>
