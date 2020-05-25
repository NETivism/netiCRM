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
    dataLayer.push({
      'event' : 'ecommerce',
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
  {/literal}{/if}
{/if}
{if $dataLayerType == 'refund'}{literal}
  dataLayer.push({
    'event' : 'ecommerce',
    'ecommerce': {
      'refund': {
        'actionField': {
          'id': '{/literal}{$transaction_id}{literal}'
        }
      }
    }
  });
{/literal}{/if}
</script>
