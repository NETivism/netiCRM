<script>
var dataLayer = window.dataLayer || [];
{if $dataLayerType == 'purchase'}
  {if $smarty.config.gtmBasicEcommerce}{literal}dataLayer.push({
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
  });{/literal}
  {*Default to enhanced ecommerce*}
  {else}{literal}
    // gtag for ga4
    window.dataLayer = [];
    window.dataLayer.push({
      "event": "purchase",
      "ecommerce": {
        "transaction_id": "{/literal}{$transaction_id}{literal}",
        "value": "{/literal}{$total_amount}{literal}",
        "currency": "{/literal}{$currency_id}{literal}",
        "items": [
          {
            "item_id": "{/literal}{$product_id}{literal}",
            "item_name": "{/literal}{$product_name}{literal}",
            "item_category": "{/literal}{$product_category}{literal}",
            "quantity" : "{/literal}{$product_quantity}{literal}",
            "price": "{/literal}{$product_amount}{literal}"
          }
        ]
      }
    });
  {/literal}{/if}
{/if}
{if $dataLayerType == 'refund'}{literal}
  dataLayer.push({
    'event' : 'refund',
    'ecommerce': {
      'transaction_id': '{/literal}{$transaction_id}{literal}'
    }
  });
{/literal}{/if}
</script>
