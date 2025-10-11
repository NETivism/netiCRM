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
    "stage2": "{/literal}{ts}Analyzing your description...{/ts}{literal}",
    "stage3": "{/literal}{ts}Starting the composition...{/ts}{literal}",
    "stage4": "{/literal}{ts}The image is taking shape...{/ts}{literal}",
    "stage5": "{/literal}{ts}Refining the details...{/ts}{literal}",
    "stage6": "{/literal}{ts}Finalizing the image...{/ts}{literal}",
    "stage7": "{/literal}{ts}The system is a bit busy. We're speeding things up - please hold on...{/ts}{literal}"
  }
};
</script>
{/literal}

{* AIImageGeneration HTML interface *}
<div class="netiaiig-container">
  <div class="netiaiig-inner">
    <div class="netiaiig-content">
      {* Imagej display area *}
      <div class="image-display">
        <div class="generated-image">
          <div class="image-placeholder">
            <img src="../images/thumb-00.png" alt="">

            {* Loading state overlay *}
            <div class="loading-overlay" style="display: none;">
              <div class="loading-spinner"></div>
              <div class="loading-message">送出請求中...</div>
              <div class="loading-progress">
                <div class="progress-bar">
                  <div class="progress-fill"></div>
                </div>
              </div>
              <div class="loading-timer">00.00 {ts}seconds{/ts}</div>
            </div>
          </div>

          {* Floating action buttons *}
          <div class="floating-actions">
            <button type="button" class="floating-btn" title="{ts}Regenerate{/ts}" data-tooltip data-tooltip-placement="nw">
              <i class="zmdi zmdi-refresh"></i>
            </button>
            <button type="button" class="floating-btn" title="{ts}Copy{/ts}" data-tooltip data-tooltip-placement="nw">
              <i class="zmdi zmdi-collection-plus"></i>
            </button>
            <button type="button" class="floating-btn" title="{ts}Download Image{/ts}" data-tooltip data-tooltip-placement="nw">
              <i class="zmdi zmdi-download"></i>
            </button>
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
                <button type="button" class="embedded-btn dropdown-toggle" title="{ts}Select Art Style{/ts}" data-tooltip data-tooltip-placement="n">
                  <i class="zmdi zmdi-flower-alt"></i>
                  <span id="styleText">Children's Book Illustration</span>
                  <i class="zmdi zmdi-chevron-down"></i>
                </button>
                <div class="style-dropdown-menu">
                  <div class="style-grid">
                    <div class="style-option" data-style="Watercolor printing">
                      <div class="style-preview"><img src="../images/thumb-01.png" alt=""></div>
                      <div class="style-label">Watercolor printing</div>
                    </div>
                    <div class="style-option" data-style="Pixel Art">
                      <div class="style-preview"><img src="../images/thumb-02.png" alt=""></div>
                      <div class="style-label">Pixel Art</div>
                    </div>
                    <div class="style-option" data-style="Expressionist Painting">
                      <div class="style-preview"><img src="../images/thumb-03.png" alt=""></div>
                      <div class="style-label">Expressionist Painting</div>
                    </div>
                    <div class="style-option" data-style="Simple Illustration">
                      <div class="style-preview"><img src="../images/thumb-04.png" alt=""></div>
                      <div class="style-label">Simple Illustration</div>
                    </div>
                    <div class="style-option" data-style="3D Animation">
                      <div class="style-preview"><img src="../images/thumb-05.png" alt=""></div>
                      <div class="style-label">3D Animation</div>
                    </div>
                    <div class="style-option selected" data-style="Children's Book Illustration">
                      <div class="style-preview"><img src="../images/thumb-06.png" alt=""></div>
                      <div class="style-label">Children's Book Illustration</div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="netiaiig-dropdown" id="ratioDropdown">
                <button type="button" class="embedded-btn dropdown-toggle" title="{ts}Select Aspect Ratio{/ts}" data-tooltip data-tooltip-placement="n">
                  <i class="zmdi zmdi-aspect-ratio-alt"></i>
                  <span id="ratioText">1:1</span>
                  <i class="zmdi zmdi-chevron-down"></i>
                </button>
                <div class="dropdown-menu">
                  <div class="dropdown-item selected" data-ratio="1:1">1:1</div>
                  <div class="dropdown-item" data-ratio="16:9">16:9</div>
                  <div class="dropdown-item" data-ratio="9:16">9:16</div>
                  <div class="dropdown-item" data-ratio="4:3">4:3</div>
                  <div class="dropdown-item" data-ratio="3:4">3:4</div>
                </div>
              </div>
            </div>

            <textarea name="netiaiig_prompt" class="prompt-textarea"
              placeholder="Describe the image you want to generate...">A gentle owl telling stories to small animals under the starry sky, soft pastel colors, warm golden moonlight, cozy storytelling atmosphere, rounded character design, digital painting style, whimsical warm mood, medium shot composition.</textarea>
          </div>
        </div>


        {* Generate button *}
        <button type="button" class="generate-btn">Generate Image</button>
      </div>

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

    </div>
  </div>
</div>
