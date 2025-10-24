{* Magnific Popup *}
<link rel="stylesheet" href="{$config->resourceBase}packages/Magnific-Popup/dist/magnific-popup.css?v{$config->ver}">
{js src=packages/Magnific-Popup/dist/jquery.magnific-popup.min.js group=999 weight=997 library=civicrm/civicrm-js-mailingeditor}{/js}

{* AIImageGeneration CSS *}
<link rel="stylesheet" href="{$config->resourceBase}packages/AIImageGeneration/AIImageGeneration.css?v{$config->ver}">
<link rel="stylesheet" href="{$config->resourceBase}packages/AIImageGeneration/AIImageGeneration-History.css?v{$config->ver}">

{* AIImageGeneration JavaScript *}
{js src=packages/AIImageGeneration/AIImageGeneration.js group=999 weight=998 library=civicrm/civicrm-js-aiimagegeneration}{/js}
{js src=packages/AIImageGeneration/AIImageGeneration-History.js group=999 weight=999 library=civicrm/civicrm-js-aiimagegeneration}{/js}

{* Added global js variable: AIImageGeneration *}
{literal}
<script type="text/javascript">
window.AIImageGeneration = {
  translation: {
    "seconds": "{/literal}{ts}seconds{/ts}{literal}",
    "stage1": "{/literal}{ts}Preparing your image...{/ts}{literal}",
    "stage2": "{/literal}{ts}Analyzing and adjusting your prompt to help generate a better image...{/ts}{literal}",
    "stage3": "{/literal}{ts}Starting the composition...{/ts}{literal}",
    "stage4": "{/literal}{ts}The image is taking shape...{/ts}{literal}",
    "stage5": "{/literal}{ts}Refining the details...{/ts}{literal}",
    "stage6": "{/literal}{ts}Finalizing the image...{/ts}{literal}",
    "stage7": "{/literal}{ts}The system is a bit busy. We're speeding things up - please hold on...{/ts}{literal}",
    "loadingInfo": "{/literal}{ts}Your image is being generated and usually takes about 40–45 seconds to complete. Feel free to do something else — we're working hard to finish your artwork!{/ts}{literal}",
    "functionNotAvailable": "{/literal}{ts}Function temporarily unavailable, please generate an image first{/ts}{literal}",
    "pleaseGenerateFirst": "{/literal}{ts}Please generate an image first{/ts}{literal}",
    "pleaseEnterDescription": "{/literal}{ts}Please enter image description{/ts}{literal}",
    "descriptionTooLong": "{/literal}{ts}Description text exceeds 1000 character limit{/ts}{literal}",
    "generating": "{/literal}{ts}Generating image...{/ts}{literal}",
    "generateSuccess": "{/literal}{ts}Image generated successfully!{/ts}{literal}",
    "generateFailed": "{/literal}{ts}Image generation failed{/ts}{literal}",
    "generateButton": "{/literal}{ts}Generate Image{/ts}{literal}",
    "aiGeneratedImage": "{/literal}{ts}AI Generated Image{/ts}{literal}",
    "submittingRequest": "{/literal}{ts}Submitting request...{/ts}{literal}",
    "noImageToCopy": "{/literal}{ts}No image available to copy{/ts}{literal}",
    "browserNotSupported": "{/literal}{ts}Your browser does not support image copying feature{/ts}{literal}",
    "imageProcessFailed": "{/literal}{ts}Image processing failed{/ts}{literal}",
    "imageCopied": "{/literal}{ts}Image copied to clipboard{/ts}{literal}",
    "copyFailed": "{/literal}{ts}Failed to copy image, please try again{/ts}{literal}",
    "imageProcessError": "{/literal}{ts}Error occurred during image processing{/ts}{literal}",
    "imageLoadFailed": "{/literal}{ts}Failed to load image, please try again{/ts}{literal}",
    "noImageToDownload": "{/literal}{ts}No image available to download{/ts}{literal}",
    "downloadStarted": "{/literal}{ts}Image download started{/ts}{literal}",
    "lightboxTitle": "{/literal}{ts}AI Generated Image{/ts}{literal}",
    "imageGenerationError": "{/literal}{ts}An error occurred during image generation, please try again later{/ts}{literal}",
    "errorInvalidJson": "{/literal}{ts}Request format error, please refresh the page and try again{/ts}{literal}",
    "errorInvalidFormat": "{/literal}{ts}Request parameter error, please check input content{/ts}{literal}",
    "errorContentTooLong": "{/literal}{ts}Description text is too long, please shorten to within 1000 characters{/ts}{literal}",
    "errorNoComponent": "{/literal}{ts}Page permission error, please refresh the page{/ts}{literal}",
    "errorInvalidMethod": "{/literal}{ts}System error, please refresh the page and try again{/ts}{literal}",
    "errorBadRequest": "{/literal}{ts}Request parameter error, please check input content{/ts}{literal}",
    "errorUnauthorized": "{/literal}{ts}Login expired, please login again{/ts}{literal}",
    "errorForbidden": "{/literal}{ts}Insufficient permissions, please contact administrator{/ts}{literal}",
    "errorNotFound": "{/literal}{ts}Service temporarily unavailable, please try again later{/ts}{literal}",
    "errorTimeout": "{/literal}{ts}Request timeout, please check network connection{/ts}{literal}",
    "errorTooManyRequests": "{/literal}{ts}Usage frequency too high, please try again later{/ts}{literal}",
    "errorServerError": "{/literal}{ts}Server temporarily error, please try again later{/ts}{literal}",
    "errorBadGateway": "{/literal}{ts}Service temporarily unavailable, please try again later{/ts}{literal}",
    "errorServiceUnavailable": "{/literal}{ts}Service temporarily under maintenance, please try again later{/ts}{literal}",
    "errorGatewayTimeout": "{/literal}{ts}Connection timeout, please check network connection{/ts}{literal}",
    "errorNetworkError": "{/literal}{ts}Network connection interrupted, please check network status{/ts}{literal}",
    "errorConnectionTimeout": "{/literal}{ts}Connection timeout, please refresh the page and try again{/ts}{literal}",
    "errorConnectionRefused": "{/literal}{ts}Unable to connect to server, please try again later{/ts}{literal}",
    "errorDnsError": "{/literal}{ts}Network configuration problem, please check network connection{/ts}{literal}",
    "errorGenerationFailed": "{/literal}{ts}Image generation failed, please try again later{/ts}{literal}",
    "errorDefaultMessage": "{/literal}{ts}An error occurred during image generation, please try again later{/ts}{literal}",
    "editPromptTooltip": "{/literal}{ts}Edit prompt: Describe the image you want to generate{/ts}{literal}",
    "generateImagesUsingAI": "{/literal}{ts}Generate images using AI{/ts}{literal}",
    "lightboxImageInfo": "{/literal}{ts}Image Information{/ts}{literal}",
    "lightboxPrompt": "{/literal}{ts}Prompt{/ts}{literal}",
    "lightboxStyle": "{/literal}{ts}Image Style{/ts}{literal}",
    "lightboxRatio": "{/literal}{ts}Image Aspect Ratio{/ts}{literal}",
    "lightboxRegenerate": "{/literal}{ts}Regenerate Image{/ts}{literal}",
    "lightboxCopy": "{/literal}{ts}Copy Image{/ts}{literal}",
    "lightboxDownload": "{/literal}{ts}Download Image{/ts}{literal}",
    "lightboxActionSuccess": "{/literal}{ts}Action completed successfully{/ts}{literal}",
    "lightboxActionFailed": "{/literal}{ts}Action failed, please try again{/ts}{literal}",
    "historyLoading": "{/literal}{ts}Loading history...{/ts}{literal}",
    "historyEmpty": "{/literal}{ts}No image generation history yet{/ts}{literal}",
    "historyEmptySubtext": "{/literal}{ts}Generated images will appear here{/ts}{literal}",
    "historyLoadFailed": "{/literal}{ts}Failed to load history, please try again{/ts}{literal}",
    "historyRefresh": "{/literal}{ts}Refresh History{/ts}{literal}",
    "historyPrevious": "{/literal}{ts}Previous Page{/ts}{literal}",
    "historyNext": "{/literal}{ts}Next Page{/ts}{literal}",
    "historyShowing": "{/literal}{ts}Showing{/ts}{literal}",
    "historyOf": "{/literal}{ts}of{/ts}{literal}",
    "loadingSampleImage": "{/literal}{ts}Loading sample image...{/ts}{literal}",
    "confirmDialogTitle": "{/literal}{ts}Load Appropriate Example Image?{/ts}{literal}",
    "confirmDialogMainText": "{/literal}{ts}You clicked \"Generate images using AI\". The system will load a example image suitable for this field (ratio: {ratio}), but there is an AI image you personally created in the current generation area.{/ts}{literal}",
    "confirmDialogQuestion": "{/literal}{ts}Do you want to replace the current image with the example image?{/ts}{literal}",
    "confirmDialogReminder": "{/literal}{ts}All images you generate are saved in \"Generation History\" and can be retrieved at any time even if replaced.{/ts}{literal}",
    "confirmReplaceButton": "{/literal}{ts}Confirm Replace{/ts}{literal}",
    "cancelButton": "{/literal}{ts}Cancel{/ts}{literal}",
    "closeDialog": "{/literal}{ts}Close dialog{/ts}{literal}",
    "generateAILinkTooltip": "{/literal}{ts}Click to open AI image generator, please download and upload the image manually after generation{/ts}{literal}"
  }
};

// Magnific Popup internationalization
(function($) {
  $.extend(true, $.magnificPopup.defaults, {
    tClose: "{/literal}{ts}Close (Esc){/ts}{literal}",
    tLoading: "{/literal}{ts}Loading...{/ts}{literal}",
    gallery: {
      tPrev: "{/literal}{ts}Previous (Left arrow key){/ts}{literal}",
      tNext: "{/literal}{ts}Next (Right arrow key){/ts}{literal}",
      tCounter: "{/literal}{ts}%curr% of %total%{/ts}{literal}"
    },
    image: {
      tError: "{/literal}{ts}The image could not be loaded.{/ts}{literal}"
    },
    ajax: {
      tError: "{/literal}{ts}The content could not be loaded.{/ts}{literal}"
    }
  });
})(cj);
</script>
{/literal}

{* AIImageGeneration HTML interface *}
<div class="netiaiig-container">
  <div class="netiaiig-inner">
    <div class="netiaiig-content">
      {* Image display area with enhanced empty state *}
      <div class="image-display" role="region" aria-label="{ts}AI Image Generation Area{/ts}">
        <div class="generated-image">
          <div class="image-placeholder"
               role="img"
               aria-label="{ts}Image generation area, click the button below to start creating your custom image{/ts}"
               tabindex="0">

            {* Empty state content - visible when no image is generated *}
            <div class="empty-state-content">
              <div class="empty-state-icon" aria-hidden="true">
                <i class="zmdi zmdi-brush"></i>
              </div>
              <div class="empty-state-title">{ts}Ready to unleash your creativity?{/ts}</div>
              <div class="empty-state-subtitle">{ts}Enter your ideas and let AI create images for you{/ts}</div>
            </div>

            {* Sample loading error state *}
            <div class="sample-error-state" style="display: none;" role="alert" aria-live="polite">
              <div class="sample-error-icon" aria-hidden="true">
                <i class="zmdi zmdi-alert-triangle"></i>
              </div>
              <div class="sample-error-title">{ts}Unable to load example image{/ts}</div>
              <div class="sample-error-subtitle">{ts}Check your network connection or try again{/ts}</div>
              <button type="button" class="sample-retry-btn" aria-label="{ts}Retry loading example image{/ts}">
                <i class="zmdi zmdi-refresh"></i>
                {ts}Retry{/ts}
              </button>
              <div class="sample-error-fallback">{ts}Or start creating your own image below{/ts}</div>
            </div>

            <img src="../images/thumb-00.png" alt="" style="display: none;">

            {* Loading state overlay *}
            <div class="loading-overlay" style="display: none;">
              <div class="loading-spinner"></div>
              <div class="loading-message"></div>
              <div class="loading-progress">
                <div class="progress-bar">
                  <div class="progress-fill"></div>
                </div>
              </div>
              <div class="loading-timer">00.00 {ts}seconds{/ts}</div>
              {* Loading info message positioned below image area *}
              <div class="loading-info">
                <p class="loading-info-text"></p>
              </div>

              {* Error state elements (hidden by default) *}
              <div class="error-state" style="display: none;" role="alert" aria-live="polite">
                <div class="error-icon" aria-hidden="true">
                  <i class="zmdi zmdi-close-circle"></i>
                </div>
                <div class="error-message" id="error-title">{ts}Image Generation Failed{/ts}</div>
                <div class="error-details" aria-describedby="error-title">
                  <div class="error-reason"></div>
                </div>
              </div>
            </div>
          </div>

          {* Floating action buttons *}
          <div class="floating-actions">
            {* Message notification area *}
            <div class="floating-message" style="display: none;" role="alert" aria-live="polite">
              <div class="floating-message-content">
                <i class="floating-message-icon"></i>
                <span class="floating-message-text"></span>
              </div>
            </div>

            <div class="floating-buttons">
              <button type="button" class="floating-btn" title="{ts}Regenerate Image{/ts}" data-tooltip data-tooltip-placement="s">
                <i class="zmdi zmdi-refresh"></i>
              </button>
              <button type="button" class="floating-btn" title="{ts}Copy Image{/ts}" data-tooltip data-tooltip-placement="s">
                <i class="zmdi zmdi-collection-plus"></i>
              </button>
              <button type="button" class="floating-btn" title="{ts}Download Image{/ts}" data-tooltip data-tooltip-placement="s">
                <i class="zmdi zmdi-download"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      {* Control panel *}
      <div class="controls">
        {* Prompt input *}
        <div class="prompt-control">
          <div class="prompt-container">
            {* Embedded control buttons *}
            <div class="embedded-controls">
              <div class="netiaiig-dropdown" id="styleDropdown">
                <button type="button" class="embedded-btn dropdown-toggle" title="{ts}Select Style{/ts}" data-tooltip data-tooltip-placement="s">
                  <i class="zmdi zmdi-flower-alt"></i>
                  <span id="styleText">{ts}Simple Illustration{/ts}</span>
                  <i class="zmdi zmdi-chevron-down"></i>
                </button>
                <div class="style-dropdown-menu">
                  <div class="style-grid">
                    <div class="style-option selected" data-style="Simple Illustration" title="{ts}Utilizes clean lines and solid color blocks to create a fresh, modern graphic style, emphasizing a concise presentation of the subject{/ts}" data-tooltip data-tooltip-placement="s">
                      <div class="style-preview"><img src="{$config->resourceBase}packages/AIImageGeneration/images/style-presets/thumbs/neticrm-aigenimg-style-preset-thumb-si-01.webp" alt=""></div>
                      <div class="style-label">{ts}Simple Illustration{/ts}</div>
                    </div>
                    <div class="style-option" data-style="Japanese Simple Illustration" title="{ts}Incorporates Japanese aesthetics with gentle lines and subtle, soft colors, creating a serene, harmonious, and zen-like visual effect{/ts}" data-tooltip data-tooltip-placement="s">
                      <div class="style-preview"><img src="{$config->resourceBase}packages/AIImageGeneration/images/style-presets/thumbs/neticrm-aigenimg-style-preset-thumb-jsi-01.webp" alt=""></div>
                      <div class="style-label">{ts}Japanese Simple Illustration{/ts}</div>
                    </div>
                    <div class="style-option" data-style="Picture book illustration" title="{ts}An art style that emphasizes storytelling and emotional expression. It features diverse colors and lines, capable of presenting unique visual narratives ranging from playful to mature, and classic to innovative{/ts}" data-tooltip data-tooltip-placement="s">
                      <div class="style-preview"><img src="{$config->resourceBase}packages/AIImageGeneration/images/style-presets/thumbs/neticrm-aigenimg-style-preset-thumb-ss-01.webp" alt=""></div>
                      <div class="style-label">{ts}Picture book illustration{/ts}</div>
                    </div>
                    <div class="style-option" data-style="Watercolor Painting" title="{ts}Mimics the blending and bleeding effects of watercolor paints, resulting in transparent colors and fluid brushstrokes to create fresh, soft, and artistic visuals{/ts}" data-tooltip data-tooltip-placement="s">
                      <div class="style-preview"><img src="{$config->resourceBase}packages/AIImageGeneration/images/style-presets/thumbs/neticrm-aigenimg-style-preset-thumb-wp-01.webp" alt=""></div>
                      <div class="style-label">{ts}Watercolor Painting{/ts}</div>
                    </div>
                    <div class="style-option" data-style="Hand-Drawn Illustration" title="{ts}Simulates the texture of hand-drawn mediums like pencil, crayon, or pen, preserving stroke details and warmth to create a natural, friendly, and handcrafted style{/ts}" data-tooltip data-tooltip-placement="s">
                      <div class="style-preview"><img src="{$config->resourceBase}packages/AIImageGeneration/images/style-presets/thumbs/neticrm-aigenimg-style-preset-thumb-hdi-01.webp" alt=""></div>
                      <div class="style-label">{ts}Hand-Drawn Illustration{/ts}</div>
                    </div>
                    <div class="style-option" data-style="Custom Style" title="{ts}System won't auto-add style tags, so please explicitly describe your desired art style in the main prompt field above, such as: Pixel Art, Cyberpunk, 3D Animation, etc{/ts}" data-tooltip data-tooltip-placement="s">
                      <div class="style-preview custom-style"></div>
                      <div class="style-label">{ts}Custom Style{/ts}</div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="netiaiig-dropdown" id="ratioDropdown">
                <button type="button" class="embedded-btn dropdown-toggle" title="{ts}Select Aspect Ratio{/ts}" data-tooltip data-tooltip-placement="s">
                  <i class="zmdi zmdi-aspect-ratio-alt"></i>
                  <span id="ratioText">4:3</span>
                  <i class="zmdi zmdi-chevron-down"></i>
                </button>
                <div class="dropdown-menu">
                  <div class="dropdown-item selected" data-ratio="4:3">4:3</div>
                  <div class="dropdown-item" data-ratio="3:4">3:4</div>
                  <div class="dropdown-item" data-ratio="1:1">1:1</div>
                  <div class="dropdown-item" data-ratio="16:9">16:9</div>
                  <div class="dropdown-item" data-ratio="9:16">9:16</div>
                </div>
              </div>
            </div>

            <textarea name="netiaiig_prompt" class="prompt-textarea"
              placeholder="{ts}Describe the image you want to generate...{/ts}"></textarea>
          </div>
        </div>


        {* Generate button *}
        <button type="button" class="generate-btn">{ts}Generate Image{/ts}</button>
      </div>

      {* History section with real data *}
      <div class="history-section">
        <div class="history-header">
          <label class="control-label">{ts}Generated Images History{/ts}</label>
        </div>

        {* Loading state for history *}
        <div class="history-loading" style="display: none;">
          <div class="history-loading-spinner"></div>
          <span class="history-loading-text">{ts}Loading history...{/ts}</span>
        </div>

        {* Empty state for history *}
        <div class="history-empty" style="display: none;">
          <div class="history-empty-icon">
            <i class="zmdi zmdi-collection-image-o"></i>
          </div>
          <div class="history-empty-text">{ts}No image generation history yet{/ts}</div>
          <div class="history-empty-subtext">{ts}Generated images will appear here{/ts}</div>
        </div>

        {* History grid container *}
        <div class="history-grid">
          {* History items will be dynamically loaded here *}
        </div>

        {* Pagination controls *}
        <div class="history-pagination" style="display: none;">
          <div class="pagination-info">
            <span class="pagination-text">{ts}Showing{/ts} <span class="pagination-start">1</span>-<span class="pagination-end">10</span> {ts}of{/ts} <span class="pagination-total">0</span></span>
          </div>
          <div class="pagination-controls">
            <button type="button" class="pagination-btn pagination-prev" title="{ts}Previous Page{/ts}" disabled>
              <i class="zmdi zmdi-chevron-left"></i>
            </button>
            <span class="pagination-current">1</span>
            <span class="pagination-separator">/</span>
            <span class="pagination-total-pages">1</span>
            <button type="button" class="pagination-btn pagination-next" title="{ts}Next Page{/ts}" disabled>
              <i class="zmdi zmdi-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

{* Confirm Replace Dialog - Magnific Popup Modal *}
<div id="netiaiig-confirm-replace-modal" class="neticrm-mfp-modal mfp-hide">
  <div class="confirm-modal-content">
    <div class="confirm-modal-header">
      <h3 class="confirm-modal-title">{ts}Load Appropriate Example Image?{/ts}</h3>
    </div>

    <div class="confirm-modal-body">
      <div class="confirm-modal-message">
        <p class="confirm-main-text">
          {ts}You clicked "Generate images using AI". The system will load a example image suitable for this field (ratio: <span class="confirm-ratio-placeholder">4:3</span>), but there is an AI image you personally created in the current generation area.{/ts}
        </p>
        <p class="confirm-question">{ts}Do you want to replace the current image with the example image?{/ts}</p>
      </div>

      <div class="confirm-modal-reminder">
        <div class="reminder-icon">
          <i class="zmdi zmdi-info-outline"></i>
        </div>
        <div class="reminder-text">
          <p>{ts}All images you generate are saved in "Generation History" and can be retrieved at any time even if replaced.{/ts}</p>
        </div>
      </div>
    </div>

    <div class="confirm-modal-actions">
      <button type="button" class="btn btn-primary confirm-replace-btn">
        {ts}Confirm Replace{/ts}
      </button>
      <button type="button" class="btn btn-secondary popup-modal-dismiss">
        {ts}Cancel{/ts}
      </button>
    </div>
  </div>
</div>
