<div class="crm-block crm-form-block crm-contact-custom-search-form-block">
<div class="crm-custom-search-description">
  TODO: RFM Search Page
</div>
<div class="crm-accordion-wrapper crm-custom_search_form-accordion crm-accordion-{if !$rows}open{else}closed{/if}">
    <div class="crm-accordion-header crm-master-accordion-header">
      <div class="zmdi crm-accordion-pointer"></div>
      {ts}Edit Search Criteria{/ts}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
            <table class="form-layout-compressed">
                <tr class="crm-contact-custom-search-form-row-receive_date">
                    <td class="label">{$form.receive_date_from.label}</td>
                    <td>{include file="CRM/common/jcalendar.tpl" elementName=receive_date_from} <span>{$form.receive_date_to.label}</span>
                        {include file="CRM/common/jcalendar.tpl" elementName=receive_date_to}
                    </td>
                </tr>
                {* Loop through all defined search criteria fields (defined in the buildForm() function). *}
                {foreach from=$elements item=element}
                <tr class="crm-contact-custom-search-form-row-{$element}">
                    <td class="label">{$form.$element.label}</td>
                    <td>{$form.$element.html}</td>
                </tr>
                {/foreach}
            </table>
            <div class="rfm-thresholds">
              <div class="rfm-thresholds-header">
                <h2 class="rfm-thresholds-title">🔍 {ts}RFM Thresholds{/ts}</h2>
                <a class="rfm-thresholds-edit rfm-popup-open-link" href="#rfm-popup">{ts}Edit Thresholds{/ts}</a>
              </div>
              <div class="rfm-thresholds-content">
                <dl class="rfm-thresholds-list">
                  <div class="rfm-thresholds-item">
                    <dt class="rfm-thresholds-term">{ts}Recency:{/ts}</dt>
                    <dd class="rfm-thresholds-definition">
                      <output class="rfm-thresholds-value" data-threshold-type="recency">{$rfmThresholds.r}</output>
                      <span class="rfm-thresholds-unit">{ts}days{/ts}</span>
                    </dd>
                  </div>
                  <div class="rfm-thresholds-item">
                    <dt class="rfm-thresholds-term">{ts}Frequency:{/ts}</dt>
                    <dd class="rfm-thresholds-definition">
                      <output class="rfm-thresholds-value" data-threshold-type="frequency">{$rfmThresholds.f}</output>
                      <span class="rfm-thresholds-unit">{ts}times{/ts}</span>
                    </dd>
                  </div>
                  <div class="rfm-thresholds-item">
                    <dt class="rfm-thresholds-term">{ts}Monetary:{/ts}</dt>
                    <dd class="rfm-thresholds-definition">
                      <output class="rfm-thresholds-value" data-threshold-type="monetary">{$rfmThresholds.m|crmMoney}</output>
                      <span class="rfm-thresholds-unit"></span>
                    </dd>
                  </div>
                </dl>
              </div>
            </div>
            {* RFM Overview START *}
            <div class="rfm-overview">
              <div class="rfm-overview-content">
                {foreach from=$rfmSegments item=segment}
                  <div class="rfm-segment-item {$segment.css_class}">
                    <div class="rfm-segment-name">
                      <a href="{crmURL p='civicrm/contact/search/custom' q="reset=`$urlParams.reset`&csid=`$urlParams.csid`&force=`$urlParams.force`&date=`$urlParams.date`&recurring=`$urlParams.recurring`&rv=`$urlParams.rv`&fv=`$urlParams.fv`&mv=`$urlParams.mv`&segment=`$segment.id`"}">{$segment.name}</a>
                    </div>
                    <div class="rfm-segment-code">{$segment.rfm_code}</div>
                  </div>
                {/foreach}
              </div>
            </div>
            {* RFM Overview END *}
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
</div><!-- /.crm-form-block -->

{if $rowsEmpty || $rows}
<div class="crm-content-block">
{if $rowsEmpty}
    {include file="CRM/Contact/Form/Search/Custom/EmptyResults.tpl"}
{/if}

{if $summary}
  {foreach from=$summary item=summary_item}
  <div><label>{$summary_item.label}</label>: {$summary_item.value}</div>
  {/foreach}
{/if}

{if $rows}
	<div class="crm-results-block">
    {* Search request has returned 1 or more matching rows. Display results and collapse the search criteria fieldset. *}
        {* This section handles form elements for action task select and submit *}
       <div class="crm-search-tasks">
        {include file="CRM/Contact/Form/Search/ResultTasks.tpl"}
		</div>
        {* This section displays the rows along and includes the paging controls *}
	    <div class="crm-search-results">

        {include file="CRM/common/pager.tpl" location="top"}

        {* Include alpha pager if defined. *}
        {if $atoZ}
            {include file="CRM/common/pagerAToZ.tpl"}
        {/if}

        {strip}
        <table class="selector" summary="{ts}Search results listings.{/ts}">
            <thead class="sticky">
                <tr>
                <th scope="col" title="Select All Rows">{$form.toggleSelect.html}</th>
                {foreach from=$columnHeaders item=header}
                    <th scope="col">
                        {if $header.sort}
                            {assign var='key' value=$header.sort}
                            {if $sort->_response.$key}{$sort->_response.$key.link}{else}{$header.name}{/if}
                        {else}
                            {$header.name}
                        {/if}
                    </th>
                {/foreach}
                <th>&nbsp;</th>
                </tr>
            </thead>

            {counter start=0 skip=1 print=false}
            {foreach from=$rows item=row}
                <tr id='rowid{$row.contact_id}' class="{cycle values="odd-row,even-row"}">
                    {assign var=cbName value=$row.checkbox}
                    <td>{$form.$cbName.html}</td>
                    {foreach from=$columnHeaders item=header}
                        {assign var=fName value=$header.sort}
                        {if $fName eq 'sort_name'}
                            <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}">{$row.sort_name}</a></td>
                        {else}
                            <td>{$row.$fName}</td>
                        {/if}
                    {/foreach}
                    <td>{$row.action}</td>
                </tr>
            {/foreach}
        </table>
        {/strip}

        {include file="CRM/common/pager.tpl" location="bottom"}
        </p>
    {* END Actions/Results section *}
    </div>
    </div>
{/if}



</div>
{/if}
{literal}
<script type="text/javascript">
  cj(function() {
    cj().crmaccordions();
  });
</script>
{/literal}

{* RFM popup start *}
<link rel="stylesheet" href="{$config->resourceBase}packages/Magnific-Popup/dist/magnific-popup.css?v{$config->ver}">
{js src=packages/Magnific-Popup/dist/jquery.magnific-popup.min.js group=999 weight=997 library=civicrm/civicrm-js-magnific-popup}{/js}
<div id="rfm-popup" class="rfm-popup crm-popup-sm crm-popup mfp-hide">
  <div class="crm-popup-inner">
    <div class="crm-popup-header">
      <div class="crm-popup-title">{ts}RFM Thresholds Settings{/ts}</div>
      <button type="button" class="rfm-popup-close crm-popup-close"><i class="zmdi zmdi-close"></i></button>
    </div>
    <div class="crm-popup-content">
      {* RFM fields start *}
      <div class="rfm-threshold-editor">
        {* R - Recency (days since last donation) *}
        <div class="rfm-threshold-editor-field">
          <div class="rfm-threshold-editor-field-header">
            <span class="rfm-threshold-editor-icon">🕐</span>
            <span class="rfm-threshold-editor-label">{$form.rfm_r_value.label}</span>
            <span class="rfm-threshold-editor-threshold-label">{ts}Threshold:{/ts}</span>
          </div>
          <div class="rfm-threshold-editor-slider">
            <div class="rfm-threshold-editor-input-wrapper">
              {$form.rfm_r_value.html}
            </div>
            <div class="rfm-threshold-editor-range">
              <span class="rfm-threshold-editor-range-min">{ts}Old R{/ts} ↓</span>
              <div class="rfm-threshold-editor-track"></div>
              <span class="rfm-threshold-editor-range-max">{ts}Recent R{/ts} ↑</span>
            </div>
          </div>
        </div>
        {* F - Frequency (number of donations) *}
        <div class="rfm-threshold-editor-field">
          <div class="rfm-threshold-editor-field-header">
            <span class="rfm-threshold-editor-icon">🏆</span>
            <span class="rfm-threshold-editor-label">{$form.rfm_f_value.label}</span>
            <span class="rfm-threshold-editor-threshold-label">{ts}Threshold:{/ts}</span>
          </div>
          <div class="rfm-threshold-editor-slider">
            <div class="rfm-threshold-editor-input-wrapper">
              {$form.rfm_f_value.html}
            </div>
            <div class="rfm-threshold-editor-range">
              <span class="rfm-threshold-editor-range-min">{ts}Low F{/ts} ↓</span>
              <div class="rfm-threshold-editor-track"></div>
              <span class="rfm-threshold-editor-range-max">{ts}High F{/ts} ↑</span>
            </div>
          </div>
        </div>
        {* M - Monetary (total donation amount) *}
        <div class="rfm-threshold-editor-field">
          <div class="rfm-threshold-editor-field-header">
            <span class="rfm-threshold-editor-icon">💲</span>
            <span class="rfm-threshold-editor-label">{$form.rfm_m_value.label}</span>
            <span class="rfm-threshold-editor-threshold-label">{ts}Threshold:{/ts}</span>
          </div>
          <div class="rfm-threshold-editor-slider">
            <div class="rfm-threshold-editor-input-wrapper">
              {$form.rfm_m_value.html}
            </div>
            <div class="rfm-threshold-editor-range">
              <span class="rfm-threshold-editor-range-min">{ts}Low M{/ts} ↓</span>
              <div class="rfm-threshold-editor-track"></div>
              <span class="rfm-threshold-editor-range-max">{ts}High M{/ts} ↑</span>
            </div>
          </div>
        </div>
      </div>
      {* RFM fields end *}
    </div>
    <div class="crm-popup-footer">
      <div class="crm-submit-buttons">
        <span class="crm-button crm-button-type-upload">
          <button type="button" class="form-submit rfm-save-btn">{ts}Save{/ts}</button>
        </span>
        <span class="crm-button crm-button-type-cancel">
          <button type="button" class="form-submit rfm-cancel-btn">{ts}Cancel{/ts}</button>
        </span>
      </div>
    </div>
  </div>
</div>
{literal}
<script type="text/javascript">
let originalValues = {
  recency: 0,
  frequency: 0,
  monetary: 0
};
let valuesSaved = false;

(function ($) {
  function updateThresholdValues() {
    const rValue = $('#rfm_r_value').val();
    $('output[data-threshold-type="recency"]').text(rValue);

    const fValue = $('#rfm_f_value').val();
    $('output[data-threshold-type="frequency"]').text(fValue);

    const mValue = $('#rfm_m_value').val();
    const formattedMValue = Number(mValue).toLocaleString('zh-TW');
    $('output[data-threshold-type="monetary"]').text(formattedMValue);
  }

  function saveOriginalValues() {
    originalValues.recency = $('#rfm_r_value').val();
    originalValues.frequency = $('#rfm_f_value').val();
    originalValues.monetary = $('#rfm_m_value').val();
    valuesSaved = false;
  }

  function restoreOriginalValues() {
    $('#rfm_r_value').val(originalValues.recency);
    $('#rfm_f_value').val(originalValues.frequency);
    $('#rfm_m_value').val(originalValues.monetary);
  }

  $(function () {
    if ($.fn.magnificPopup && $('#rfm-popup').length) {
      $('.crm-container').on('click', '.rfm-popup-open-link', function(e) {
        e.preventDefault();

        saveOriginalValues();

        $.magnificPopup.open({
          items: {
            src: '#rfm-popup'
          },
          type: 'inline',
          mainClass: 'mfp-rfm-popup',
          preloader: true,
          showCloseBtn: false,
          callbacks: {
            open: function() {
              $('body').addClass('rfm-popup-active mfp-is-active');
            },
            beforeClose: function() {
              if (!valuesSaved) {
                restoreOriginalValues();
              }
            },
            close: function() {
              $('body').removeClass('rfm-popup-active mfp-is-active');
            }
          }
        });
      });

      $('body').on('click', '.rfm-save-btn', function() {
          updateThresholdValues();
          valuesSaved = true;
          $.magnificPopup.close();
        });

      $('body').on('click', '.rfm-cancel-btn, .rfm-popup-close', function() {
        $.magnificPopup.close();
      });
    }
  });
})(cj);
</script>
{/literal}
{* RFM popup end *}