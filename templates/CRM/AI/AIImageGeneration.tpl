{* Magnific Popup *}
<link rel="stylesheet" href="{$config->resourceBase}packages/Magnific-Popup/dist/magnific-popup.css?v{$config->ver}">
{js src=packages/Magnific-Popup/dist/jquery.magnific-popup.min.js group=999 weight=997 library=civicrm/civicrm-js-mailingeditor}{/js}

{* AIImageGeneration CSS *}
<link rel="stylesheet" href="{$config->resourceBase}packages/AIImageGeneration/AIImageGeneration.css?v{$config->ver}">

{* AIImageGeneration JavaScript *}
{js src=packages/AIImageGeneration/AIImageGeneration.js group=999 weight=998 library=civicrm/civicrm-js-aiimagegeneration}{/js}

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
    "editPromptTooltip": "{/literal}{ts}Edit prompt: Describe the image you want to generate{/ts}{literal}"
  }
};
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
              <button type="button" class="floating-btn" title="{ts}Regenerate{/ts}" data-tooltip data-tooltip-placement="s">
                <i class="zmdi zmdi-refresh"></i>
              </button>
              <button type="button" class="floating-btn" title="{ts}Copy{/ts}" data-tooltip data-tooltip-placement="s">
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
                <button type="button" class="embedded-btn dropdown-toggle" title="{ts}Select Art Style{/ts}" data-tooltip data-tooltip-placement="s">
                  <i class="zmdi zmdi-flower-alt"></i>
                  <span id="styleText">{ts}Simple Illustration{/ts}</span>
                  <i class="zmdi zmdi-chevron-down"></i>
                </button>
                <div class="style-dropdown-menu">
                  <div class="style-grid">
                    {assign var="rand_img_num" value=rand(1, 7)|string_format:"%02d"}
                    <div class="style-option selected" data-style="Simple Illustration">
                      <div class="style-preview"><img src="../images/style-preset-simple-illustration-{$rand_img_num}.webp" alt=""></div>
                      <div class="style-label">{ts}Simple Illustration{/ts}</div>
                    </div>
                    <div class="style-option" data-style="Japanese Simple Illustration">
                      <div class="style-preview"><img src="../images/style-preset-japanese-illustration-{$rand_img_num}.webp" alt=""></div>
                      <div class="style-label">{ts}Japanese Simple Illustration{/ts}</div>
                    </div>
                    <div class="style-option" data-style="Storybook Style">
                      <div class="style-preview"><img src="../images/style-preset-storybook-style-{$rand_img_num}.webp" alt=""></div>
                      <div class="style-label">{ts}Storybook Style{/ts}</div>
                    </div>
                    <div class="style-option" data-style="Watercolor Painting">
                      <div class="style-preview"><img src="../images/style-preset-watercolor-painting-{$rand_img_num}.webp" alt=""></div>
                      <div class="style-label">{ts}Watercolor Painting{/ts}</div>
                    </div>
                    <div class="style-option" data-style="Hand-Drawn Illustration">
                      <div class="style-preview"><img src="../images/style-preset-hand-drawn-illustration-{$rand_img_num}.webp" alt=""></div>
                      <div class="style-label">{ts}Hand-Drawn Illustration{/ts}</div>
                    </div>
                    <div class="style-option" data-style="Custom Style">
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

      <!--
      {* History section *}
      <div class="history-section">
        <label class="control-label">History</label>
        <div class="history-grid">
          <div class="history-item">
            <div style="background: linear-gradient(45deg, #667eea, #764ba2); width: 100%; height: 100%;"></div>
          </div>
          <div class="history-item">
            <div style="background: linear-gradient(45deg, #f093fb, #f5576c); width: 100%; height: 100%;"></div>
          </div>
          <div class="history-item">
            <div style="background: linear-gradient(45deg, #4facfe, #00f2fe); width: 100%; height: 100%;"></div>
          </div>
          <div class="history-item">
            <div style="background: linear-gradient(45deg, #43e97b, #38f9d7); width: 100%; height: 100%;"></div>
          </div>
          <div class="history-item">
            <div style="background: linear-gradient(45deg, #fa709a, #fee140); width: 100%; height: 100%;"></div>
          </div>
          <div class="history-item">
            <div style="background: linear-gradient(45deg, #a8edea, #fed6e3); width: 100%; height: 100%;"></div>
          </div>
        </div>
      </div>
      -->

    </div>
  </div>
</div>
