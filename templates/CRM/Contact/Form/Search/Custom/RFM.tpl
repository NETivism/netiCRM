{* Initialize RFM segment data for frontend JavaScript *}
<script type="text/javascript">
  // Initialize window.rfmSegmentData from backend
  window.rfmSegmentData = {if $rfmSegmentDataJson}{$rfmSegmentDataJson}{else}{ldelim}{rdelim}{/if};
</script>

<div class="crm-block crm-form-block crm-contact-custom-search-form-block crm-contact-custom-search-form-rfm-block">
<div class="crm-custom-search-description">
  {ts}The RFM model helps organizations categorize donors based on their giving behavior, enabling more refined and personalized communication strategies that convert more supporters into loyal ones.{/ts}
</div>
<div class="crm-accordion-wrapper crm-custom_search_form-accordion crm-accordion-{if !$rows}open{else}closed{/if}">
    <div class="crm-accordion-header crm-master-accordion-header">
      <div class="zmdi crm-accordion-pointer"></div>
      {ts}Edit Search Criteria{/ts}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
        <div class="custom-search-desc">
          <p>{ts}Threshold values are automatically calculated based on the selected statistical time range. If the date range is adjusted, the condition values will be recalculated, and any previous manual adjustments will be cleared.{/ts}</p>
        </div>
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
            <div class="crm-submit-buttons crm-submit-buttons-top">{include file="CRM/common/formButtons.tpl" location="top"}</div>
            {* RFM 3D Visualization Container START *}
            <div class="rfm-3d-container">
              <div class="rfm-3d-header">
                <div class="crm-contact-custom-search-form-row-rfm-thresholds">
                  <div class="label">{ts}RFM Thresholds{/ts}</div>
                  <div class="content">
                      <ul class="rfm-thresholds-list">
                        <li>
                          <span class="item-label">{ts}Recency (days since last donation){/ts}</span>
                          <output class="item-value" data-threshold-type="recency">{$rfmThresholds.r}</output>
                          <span class="item-value-unit">{ts}days{/ts}</span>
                        </li>
                        <li>
                          <span class="item-label">{ts}Frequency (number of donations){/ts}</span>
                          <output class="item-value" data-threshold-type="frequency">{$rfmThresholds.f}</output>
                          <span class="item-value-unit">{ts}times{/ts}</span>
                        </li>
                        <li>
                          <span class="item-label">{ts}Monetary (total donation amount){/ts}</span>
                          <output class="item-value" data-threshold-type="monetary">{$rfmThresholds.m|crmMoney}</output>
                        </li>
                      </ul>
                      <a class="rfm-popup-open-link" href="#rfm-popup"><i class="zmdi zmdi-edit"></i>{ts}Edit Thresholds{/ts}</a>
                  </div>
                </div>
              </div>
              <div class="rfm-3d-main">
                <div class="rfm-3d-sidebar-left rfm-3d-sidebar">
                  {foreach from=$lowRfmSegments item=segment}
                    <div class="segment-item" data-segment="{$segment.numeric_id}">
                      <a href="{crmURL p='civicrm/contact/search/custom' q="reset=`$urlParams.reset`&csid=`$urlParams.csid`&force=`$urlParams.force`&date=`$urlParams.date`&recurring=`$urlParams.recurring`&rv=`$urlParams.rv`&fv=`$urlParams.fv`&mv=`$urlParams.mv`&segment=`$segment.id`"}" class="segment-link">
                        <div class="segment-text">
                          <div class="segment-color"></div>
                          <div class="segment-name">{$segment.name}</div>
                        </div>
                        <div class="rfm-indicators">
                          {assign var="rfm_code" value=$segment.id}
                          {if $rfm_code|substr:1:1 eq 'h'}
                            <span class="rfm-tag high">
                              <span class="triangle up"></span>R
                            </span>
                          {else}
                            <span class="rfm-tag low">
                              <span class="triangle down"></span>R
                            </span>
                          {/if}
                          {if $rfm_code|substr:3:1 eq 'h'}
                            <span class="rfm-tag high">
                              <span class="triangle up"></span>F
                            </span>
                          {else}
                            <span class="rfm-tag low">
                              <span class="triangle down"></span>F
                            </span>
                          {/if}
                          {if $rfm_code|substr:5:1 eq 'h'}
                            <span class="rfm-tag high">
                              <span class="triangle up"></span>M
                            </span>
                          {else}
                            <span class="rfm-tag low">
                              <span class="triangle down"></span>M
                            </span>
                          {/if}
                        </div>
                        {if !$hasSegmentParam}
                        <div class="segment-counter">
                          {ts 1=$segment.count 2=$segment.percentage}Showing <output class="record">%1</output> records, which is <output class="percent">%2</output>% of the search results{/ts}
                        </div>
                        {/if}
                        <div class="segment-description">{$segment.description}</div>
                      </a>
                    </div>
                  {/foreach}
                </div>
                <div class="rfm-3d-content">
                  {* 3D Cube Visualization *}
                  <div class="rfm-3d-wrapper">
                    <div class="cube-container">
                      {* Cube 1: Front-Left-Top - R low F high M high - Segment ID 3 - At Risk Big *}
                      <div class="small-cube cube-1" data-cube="1" data-segment-id="3" data-position="front-left-top">
                        <div class="cube-face front"></div>
                        <div class="cube-face back"></div>
                        <div class="cube-face right"></div>
                        <div class="cube-face left"></div>
                        <div class="cube-face top"></div>
                        <div class="cube-face bottom"></div>
                      </div>

                      {* Cube 2: Front-Right-Top - R high F high M high - Segment ID 7 - Champions *}
                      <div class="small-cube cube-2" data-cube="2" data-segment-id="7" data-position="front-right-top">
                        <div class="cube-face front"></div>
                        <div class="cube-face back"></div>
                        <div class="cube-face right"></div>
                        <div class="cube-face left"></div>
                        <div class="cube-face top"></div>
                        <div class="cube-face bottom"></div>
                      </div>

                      {* Cube 3: Front-Left-Bottom - R low F low M high - Segment ID 1 - Hibernating Big *}
                      <div class="small-cube cube-3" data-cube="3" data-segment-id="1" data-position="front-left-bottom">
                        <div class="cube-face front"></div>
                        <div class="cube-face back"></div>
                        <div class="cube-face right"></div>
                        <div class="cube-face left"></div>
                        <div class="cube-face top"></div>
                        <div class="cube-face bottom"></div>
                      </div>

                      {* Cube 4: Front-Right-Bottom - R high F low M high - Segment ID 5 - New Big *}
                      <div class="small-cube cube-4" data-cube="4" data-segment-id="5" data-position="front-right-bottom">
                        <div class="cube-face front"></div>
                        <div class="cube-face back"></div>
                        <div class="cube-face right"></div>
                        <div class="cube-face left"></div>
                        <div class="cube-face top"></div>
                        <div class="cube-face bottom"></div>
                      </div>

                      {* Cube 5: Back-Left-Top - R low F high M low - Segment ID 2 - At Risk Small *}
                      <div class="small-cube cube-5" data-cube="5" data-segment-id="2" data-position="back-left-top">
                        <div class="cube-face front"></div>
                        <div class="cube-face back"></div>
                        <div class="cube-face right"></div>
                        <div class="cube-face left"></div>
                        <div class="cube-face top"></div>
                        <div class="cube-face bottom"></div>
                      </div>

                      {* Cube 6: Back-Right-Top - R high F high M low - Segment ID 6 - Loyal Small *}
                      <div class="small-cube cube-6" data-cube="6" data-segment-id="6" data-position="back-right-top">
                        <div class="cube-face front"></div>
                        <div class="cube-face back"></div>
                        <div class="cube-face right"></div>
                        <div class="cube-face left"></div>
                        <div class="cube-face top"></div>
                        <div class="cube-face bottom"></div>
                      </div>

                      {* Cube 7: Back-Left-Bottom - R low F low M low - Segment ID 0 - Hibernating Small *}
                      <div class="small-cube cube-7" data-cube="7" data-segment-id="0" data-position="back-left-bottom">
                        <div class="cube-face front"></div>
                        <div class="cube-face back"></div>
                        <div class="cube-face right"></div>
                        <div class="cube-face left"></div>
                        <div class="cube-face top"></div>
                        <div class="cube-face bottom"></div>
                      </div>

                      {* Cube 8: Back-Right-Bottom - R high F low M low - Segment ID 4 - New Small *}
                      <div class="small-cube cube-8" data-cube="8" data-segment-id="4" data-position="back-right-bottom">
                        <div class="cube-face front"></div>
                        <div class="cube-face back"></div>
                        <div class="cube-face right"></div>
                        <div class="cube-face left"></div>
                        <div class="cube-face top"></div>
                        <div class="cube-face bottom"></div>
                      </div>
                    </div>

                    {* RFM 3D Axes System *}
                    <div class="axes-container">
                      {* R-axis (Recency - Last donation time) *}
                      <div class="axis r-axis">
                        <div class="axis-line"></div>
                        <div class="axis-label label-left"><span class="rfm-label">R</span>{ts}Recency: Old{/ts}</div>
                        <div class="axis-label label-right"><span class="rfm-label">R</span>{ts}Recency: Recent{/ts}</div>
                      </div>

                      {* F-axis (Frequency - Donation frequency) *}
                      <div class="axis f-axis">
                        <div class="axis-line"></div>
                        <div class="axis-label label-bottom"><span class="rfm-label">F</span>{ts}Frequency: Low{/ts}</div>
                        <div class="axis-label label-top"><span class="rfm-label">F</span>{ts}Frequency: High{/ts}</div>
                      </div>

                      {* M-axis (Monetary - Donation amount) *}
                      <div class="axis m-axis">
                        <div class="axis-line"></div>
                        <div class="axis-label label-back"><span class="rfm-label">M</span>{ts}Monetary: Low{/ts}</div>
                        <div class="axis-label label-front"><span class="rfm-label">M</span>{ts}Monetary: High{/ts}</div>
                      </div>
                    </div>
                  </div>                  
                </div>
                <div class="rfm-3d-sidebar-right rfm-3d-sidebar">
                  {foreach from=$highRfmSegments item=segment}
                    <div class="segment-item" data-segment="{$segment.numeric_id}">
                      <a href="{crmURL p='civicrm/contact/search/custom' q="reset=`$urlParams.reset`&csid=`$urlParams.csid`&force=`$urlParams.force`&date=`$urlParams.date`&recurring=`$urlParams.recurring`&rv=`$urlParams.rv`&fv=`$urlParams.fv`&mv=`$urlParams.mv`&segment=`$segment.id`"}" class="segment-link">
                        <div class="segment-text">
                          <div class="segment-color"></div>
                          <div class="segment-name">{$segment.name}</div>
                        </div>
                        <div class="rfm-indicators">
                          {assign var="rfm_code" value=$segment.id}
                          {if $rfm_code|substr:1:1 eq 'h'}
                            <span class="rfm-tag high">
                              <span class="triangle up"></span>R
                            </span>
                          {else}
                            <span class="rfm-tag low">
                              <span class="triangle down"></span>R
                            </span>
                          {/if}
                          {if $rfm_code|substr:3:1 eq 'h'}
                            <span class="rfm-tag high">
                              <span class="triangle up"></span>F
                            </span>
                          {else}
                            <span class="rfm-tag low">
                              <span class="triangle down"></span>F
                            </span>
                          {/if}
                          {if $rfm_code|substr:5:1 eq 'h'}
                            <span class="rfm-tag high">
                              <span class="triangle up"></span>M
                            </span>
                          {else}
                            <span class="rfm-tag low">
                              <span class="triangle down"></span>M
                            </span>
                          {/if}
                        </div>
                        {if !$hasSegmentParam}
                        <div class="segment-counter">
                          {ts 1=$segment.count 2=$segment.percentage}Showing <output class="record">%1</output> records, which is <output class="percent">%2</output> of the search results{/ts}
                        </div>
                        {/if}
                        <div class="segment-description">{$segment.description}</div>
                      </a>
                    </div>
                  {/foreach}
                </div>
              </div>
            </div>
            {* RFM 3D Visualization Container END *}
    </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
</div><!-- /.crm-form-block -->

{if $showResults && ($rowsEmpty || $rows)}
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

{* RFM package *}
<link rel="stylesheet" href="{$config->resourceBase}packages/RFM/RFM.css?v{$config->ver}">
{js src=packages/RFM/RFM.js group=999 weight=998 library=civicrm/civicrm-js-rfm}{/js}