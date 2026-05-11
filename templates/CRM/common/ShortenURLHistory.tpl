{*
 +--------------------------------------------------------------------+
 | netiCRM shorten URL history accordion partial.                     |
 +--------------------------------------------------------------------+
 | Required params:                                                   |
 |   $history   array of rows (short_url, utm_*, created_date)        |
 |   $pageType  one of CRM_Core_BAO_ShortenURLHistory::ALLOWED_*      |
 |   $pageId    integer page id                                       |
 +--------------------------------------------------------------------+
*}
<div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-closed shorten-url-history"
     data-page-type="{$pageType}" data-page-id="{$pageId}">
  <div class="crm-accordion-header">
    <div class="zmdi crm-accordion-pointer"></div>
    {if $pageType eq 'civicrm_pcp'}
      {ts}Shorten URL History (Personal Campaign){/ts}
    {else}
      {ts}Shorten URL History{/ts}
    {/if}
    <span class="shorten-url-history-hint">{ts}(latest 30 records){/ts}</span>
  </div>
  <div class="crm-accordion-body">
    {if $history}
    <table class="report shorten-url-history-table">
      <thead>
        <tr>
          <th>{ts}UTM Source{/ts}</th>
          <th>{ts}UTM Medium{/ts}</th>
          <th>{ts}UTM Term{/ts}</th>
          <th>{ts}UTM Content{/ts}</th>
          <th>{ts}UTM Campaign{/ts}</th>
          <th>{ts}Short URL{/ts}</th>
          <th>{ts}Original Target URL{/ts}</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$history item=row}
        <tr>
          <td>{$row.utm_source|escape}</td>
          <td>{$row.utm_medium|escape}</td>
          <td>{$row.utm_term|escape}</td>
          <td>{$row.utm_content|escape}</td>
          <td>{$row.utm_campaign|escape}</td>
          <td><a href="{$row.short_url|escape}" target="_blank" rel="noopener">{$row.short_url|escape}</a></td>
          <td>
            <div class="helpicon shorten-url-target" data-short-url="{$row.short_url|escape}">&nbsp;
              <span style="display:none"><div class="crm-help">{ts}Loading...{/ts}</div></span>
              <span class="original-target-url">{ts}Loading...{/ts}</span>
            </div>
          </td>
        </tr>
        {/foreach}
      </tbody>
    </table>
    {else}
    <div class="shorten-url-history-empty">{ts}No shortened URL records yet. Click "Shorten URL" to create one.{/ts}</div>
    {/if}
  </div>
</div>
<script type="text/javascript">
{literal}
// Thead HTML used by ShortenURL.tpl callback to upgrade an empty-state
// accordion body into a full table when the user shortens the first URL.
// Idempotent — assigning the same constant on every partial include is fine.
window._netiShortenUrlHistoryThead =
{/literal}
  '<thead><tr>'
  + '<th>{ts escape='js'}UTM Source{/ts}</th>'
  + '<th>{ts escape='js'}UTM Medium{/ts}</th>'
  + '<th>{ts escape='js'}UTM Term{/ts}</th>'
  + '<th>{ts escape='js'}UTM Content{/ts}</th>'
  + '<th>{ts escape='js'}UTM Campaign{/ts}</th>'
  + '<th>{ts escape='js'}Short URL{/ts}</th>'
  + '<th>{ts escape='js'}Original Target URL{/ts}</th>'
  + '</tr></thead>';
{literal}

cj(function($) {
  // Accordion handler is idempotent (checks crm-accordion-processed class).
  $().crmaccordions();

  // Init tooltip once per helpicon with the current .crm-help text. The text
  // starts as "Loading..." and is replaced once batch-info responds (or after
  // the dialog inserts a new row with the long URL already known).
  $('.shorten-url-target:not(.tooltip-inited)').addClass('tooltip-inited').toolTip({skipVerticalComparison: true});

  // Batch-load target URLs only once per page, even if this partial is
  // included multiple times (e.g. event info + register on the same page).
  if (window._netiShortenUrlBatchInited) return;
  window._netiShortenUrlBatchInited = true;

  var seen = {};
  var shortUrls = [];
  $('.shorten-url-target').each(function() {
    var u = $(this).attr('data-short-url');
    if (u && !seen[u]) { seen[u] = 1; shortUrls.push(u); }
  });
  if (!shortUrls.length) return;
{/literal}
  var failedText = '{ts escape='js'}Unable to load target URL.{/ts}';
  var emptyText  = '{ts escape='js'}(no data){/ts}';
{literal}

  function applyTarget($icon, text) {
    $icon.find('.crm-help').text(text);
    $icon.find('.original-target-url').text(text);
    // TipTip caches the title on init; unbind hover then re-init to pick up
    // the new .crm-help content.
    $icon.off('mouseenter mouseleave').toolTip({skipVerticalComparison: true});
  }

  $.ajax({
    url: '/civicrm/ajax/shortenurlbatchinfo',
    type: 'POST',
    dataType: 'json',
    data: {short_urls: JSON.stringify(shortUrls)},
    success: function(data) {
      var isFail = data.is_error || !data.result;
      $('.shorten-url-target').each(function() {
        var $icon = $(this);
        if (isFail) {
          applyTarget($icon, failedText);
          return;
        }
        var t = data.result[$icon.attr('data-short-url')];
        applyTarget($icon, (typeof t === 'string' && t !== '') ? t : emptyText);
      });
    },
    error: function() {
      $('.shorten-url-target').each(function() {
        applyTarget($(this), failedText);
      });
    }
  });
});
{/literal}
</script>
