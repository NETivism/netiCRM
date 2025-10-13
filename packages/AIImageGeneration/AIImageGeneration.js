/**
 * AI Image Generation Component JavaScript
 * Handles user interactions and component functionality
 */
(function($) {
  'use strict';

  // Component namespace
  const NetiAIImageGeneration = {
    // Configuration
    config: {
      container: '.netiaiig-container',
      selectors: {
        styleDropdown: '#styleDropdown',
        ratioDropdown: '#ratioDropdown',
        styleText: '#styleText',
        ratioText: '#ratioText',
        generateBtn: '.generate-btn',
        floatingBtn: '.floating-btn',
        historyItem: '.history-item',
        promptTextarea: '.prompt-textarea',
        styleOptions: '.style-option',
        dropdownItems: '.dropdown-item'
      },
      classes: {
        active: 'active',
        selected: 'selected',
        loading: 'loading',
        disabled: 'disabled'
      }
    },

    // Initialize component
    init: function() {
      this.bindEvents();
      this.initAutoResizeTextarea();
      this.initVisibilityObserver();
      this.initImageLightbox();

      // Initialize floating actions state based on current image
      this.updateFloatingActionsBasedOnImage();

      console.log('AI Image Generation component initialized');
    },

    // Bind all events
    bindEvents: function() {
      const self = this;

      // Style dropdown functionality
      $(document).on('click', `${self.config.selectors.styleDropdown} .dropdown-toggle`, function(e) {
        e.stopPropagation();
        self.toggleDropdown($(this).closest('.netiaiig-dropdown'));
      });

      // Ratio dropdown functionality
      $(document).on('click', `${self.config.selectors.ratioDropdown} .dropdown-toggle`, function(e) {
        e.stopPropagation();
        self.toggleDropdown($(this).closest('.netiaiig-dropdown'));
      });

      // Style option selection
      $(document).on('click', self.config.selectors.styleOptions, function() {
        self.selectStyleOption($(this));
      });

      // Ratio item selection
      $(document).on('click', `${self.config.selectors.ratioDropdown} ${self.config.selectors.dropdownItems}`, function() {
        self.selectRatioOption($(this));
      });

      // Generate button
      $(document).on('click', self.config.selectors.generateBtn, function(e) {
        e.preventDefault();
        self.generateImage();
      });

      // Floating action buttons
      $(document).on('click', self.config.selectors.floatingBtn, function(e) {
        e.preventDefault();
        e.stopPropagation();
        self.handleFloatingAction($(this));
      });

      // History items
      $(document).on('click', self.config.selectors.historyItem, function() {
        self.loadHistoryImage($(this));
      });

      // Close dropdowns when clicking outside
      $(document).on('click', function() {
        self.closeAllDropdowns();
      });

      // Prevent dropdown close when clicking inside
      $(document).on('click', '.dropdown-menu, .style-dropdown-menu', function(e) {
        e.stopPropagation();
      });

      // Keyboard navigation support
      $(document).on('keydown', '.dropdown-toggle', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          $(this).click();
        }
      });

      // Textarea auto-resize event binding (following reference file logic)
      $(document).on('input', self.config.selectors.promptTextarea, function() {
        self.autoResizeTextarea($(this));
      });
    },

    // Toggle dropdown state
    toggleDropdown: function($dropdown) {
      const isActive = $dropdown.hasClass(this.config.classes.active);

      // Close all dropdowns first
      this.closeAllDropdowns();

      // Toggle current dropdown
      if (!isActive) {
        $dropdown.addClass(this.config.classes.active);
      }
    },

    // Close all dropdowns
    closeAllDropdowns: function() {
      $(this.config.container).find('.netiaiig-dropdown').removeClass(this.config.classes.active);
    },

    // Select style option
    selectStyleOption: function($option) {
      const style = $option.data('style');
      const $container = $option.closest('.netiaiig-dropdown');

      // Update selected state
      $option.siblings().removeClass(this.config.classes.selected);
      $option.addClass(this.config.classes.selected);

      // Update button text
      $container.find(this.config.selectors.styleText).text(style);

      // Close dropdown
      $container.removeClass(this.config.classes.active);

      console.log('Selected style:', style);

      // Trigger custom event
      $(this.config.container).trigger('styleChanged', [style]);
    },

    // Select ratio option
    selectRatioOption: function($option) {
      const ratio = $option.data('ratio');
      const $container = $option.closest('.netiaiig-dropdown');

      // Update selected state
      $option.siblings().removeClass(this.config.classes.selected);
      $option.addClass(this.config.classes.selected);

      // Update button text
      $container.find(this.config.selectors.ratioText).text(ratio);

      // Close dropdown
      $container.removeClass(this.config.classes.active);

      console.log('Selected ratio:', ratio);

      // Update image container aspect ratio using CSS variable
      this.updateImageAspectRatio(ratio);

      // Trigger custom event
      $(this.config.container).trigger('ratioChanged', [ratio]);
    },

    // Update image aspect ratio using CSS variable
    updateImageAspectRatio: function(ratio) {
      const $imageContainer = $(this.config.container).find('.generated-image');

      // Map ratio strings to CSS aspect-ratio values
      const ratioMap = {
        '1:1': '1',
        '16:9': '16/9',
        '9:16': '9/16',
        '4:3': '4/3',
        '3:4': '3/4'
      };

      if (ratioMap[ratio]) {
        $imageContainer.css('--image-ratio', ratioMap[ratio]);
      }
    },

    // Handle floating action buttons
    handleFloatingAction: function($button) {
      // Check if button is disabled (both HTML attribute and CSS class)
      if ($button.prop('disabled') || $button.hasClass(this.config.classes.disabled)) {
        this.showError('功能暫時無法使用，請先生成圖片');
        return;
      }

      // Double check if we have a valid image
      if (!this.hasGeneratedImage()) {
        this.showError('請先生成圖片');
        return;
      }

      // Get action from title attribute or data-tooltip for backwards compatibility
      const title = $button.attr('title') || $button.attr('data-tooltip');

      // Determine action based on icon class or title
      if ($button.find('.zmdi-refresh').length || title.includes('Regenerate')) {
        this.generateImage();
      } else if ($button.find('.zmdi-collection-plus').length || title.includes('Insert')) {
        this.insertToEditor();
      } else if ($button.find('.zmdi-download').length || title.includes('Download')) {
        this.downloadImage();
      } else {
        console.log('Unknown floating action:', title);
      }
    },

    // Generate image functionality
    generateImage: function() {
      const $btn = $(this.config.container).find(this.config.selectors.generateBtn);
      const $textarea = $(this.config.container).find(this.config.selectors.promptTextarea);
      const prompt = $textarea.val().trim();

      // Input validation
      if (!prompt) {
        this.showError('請輸入圖片描述');
        return;
      }

      if (prompt.length > 1000) {
        this.showError('描述文字超過1000字元限制');
        return;
      }

      // Hide any existing error state when starting new generation
      this.errorManager.reset();

      // Get current settings
      const style = $(this.config.container).find(this.config.selectors.styleText).text();
      const ratio = $(this.config.container).find(this.config.selectors.ratioText).text();

      // Prepare request data
      const requestData = {
        text: prompt,
        style: style,
        ratio: ratio,
        sourceUrlPath: window.location.pathname
      };

      // Set button loading state
      $btn.prop('disabled', true)
          .addClass(this.config.classes.loading)
          .text('正在生成圖片...');

      // Show staged loading overlay in image area
      this.loadingManager.show();

      console.log('Generating image with data:', requestData);

      // Trigger custom event
      $(this.config.container).trigger('imageGeneration', [requestData]);

      // Make API call
      const self = this;
      $.ajax({
        url: '/civicrm/ai/images/generate',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(requestData),
        timeout: 120000, // 60 seconds timeout

        success: function(response) {
          // Stop loading manager
          self.loadingManager.hide();

          // Check response status
          if (response.status === 1 && response.data) {
            // Success: show image
            self.onGenerationComplete(response.data.image_url, response.data);
            self.showSuccess('圖片生成成功！');
          } else {
            // Failure: show error message
            self.onGenerationComplete();
            self.errorManager.show({
              message: response.message || '圖片生成失敗'
            });
          }
        },

        error: function(xhr, status, error) {
          // Stop loading manager
          self.loadingManager.hide();

          // Reset generate button
          self.onGenerationComplete();

          // Handle HTTP errors
          let errorMessage = '圖片生成失敗';

          // Try to parse JSON response
          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          } else if (status === 'timeout') {
            errorMessage = 'timeout'; // Will be converted to friendly message
          } else {
            // Other cases keep generic error message, handled by HTTP status code
            errorMessage = '圖片生成失敗';
          }

          // Use error manager with HTTP status code
          self.errorManager.show({
            message: errorMessage,
            httpStatus: xhr.status
          });

          console.error('Image generation error:', status, error, xhr.responseText);
        }
      });
    },

    // Handle generation completion
    onGenerationComplete: function(imageUrl = null, responseData = null) {
      const $btn = $(this.config.container).find(this.config.selectors.generateBtn);

      // Reset button state
      $btn.prop('disabled', false)
          .removeClass(this.config.classes.loading)
          .text('Generate Image');

      if (imageUrl) {
        this.displayGeneratedImage(imageUrl);

        console.log('Image generation completed successfully:', {
          imageUrl: imageUrl,
          responseData: responseData
        });
      } else {
        console.log('Image generation completed without result');
      }

      // Update floating actions based on actual image state
      // Wait a moment for displayGeneratedImage to complete
      setTimeout(() => {
        this.updateFloatingActionsBasedOnImage();
      }, 100);

      // Trigger custom event with full response data
      $(this.config.container).trigger('generationComplete', [imageUrl, responseData]);
    },

    // Display generated image
    displayGeneratedImage: function(imageUrl) {
      const $imageContainer = $(this.config.container).find('.image-placeholder');

      if (imageUrl) {
        console.log('Displaying image:', imageUrl);

        // Find existing image and loading overlay, preserve structure
        let $existingImg = $imageContainer.find('img');
        const $loadingOverlay = $imageContainer.find('.loading-overlay');

        // Ensure loading overlay structure exists
        if ($loadingOverlay.length === 0) {
          this.restoreLoadingOverlay($imageContainer);
        }

        // Hide existing image during loading
        if ($existingImg.length > 0) {
          $existingImg.hide();
        }

        // Create new image element
        const img = new Image();
        const $img = $(img);

        // Set up load handler before setting src
        img.onload = function() {
          console.log('Image loaded successfully - displaying now');
          $img.attr('alt', 'AI 生成圖片');

          // Add specific class for AI generated images
          $img.addClass('ai-generated-image');

          // Create anchor tag to wrap the image for lightbox functionality
          const $link = $('<a>').attr({
            'href': imageUrl,
            'class': 'ai-image-link'
          }).append($img);

          // Remove old image if exists and add new wrapped image
          $existingImg.remove();

          // Insert new link before loading-overlay to maintain structure
          const $overlay = $imageContainer.find('.loading-overlay');
          if ($overlay.length > 0) {
            $overlay.before($link);
          } else {
            $imageContainer.prepend($link);
          }

          // Update floating actions state after image is successfully loaded
          setTimeout(() => {
            NetiAIImageGeneration.updateFloatingActionsBasedOnImage();
          }, 50);
        };

        // Set up error handler
        img.onerror = function() {
          console.error('Image failed to load:', imageUrl);
          // Show existing image if error occurs
          if ($existingImg.length > 0) {
            $existingImg.show();
          }
        };

        // Add timeout protection (10 seconds)
        setTimeout(function() {
          if (!img.complete || img.naturalWidth === 0) {
            console.error('Image loading failed or timed out');
            // Show existing image on timeout
            if ($existingImg.length > 0) {
              $existingImg.show();
            }
          }
        }, 10000);

        // Start loading
        console.log('Starting image load...');
        img.src = imageUrl;

      } else {
        // Reset to initial state, preserve loading-overlay
        const $existingImg = $imageContainer.find('img');
        const $loadingOverlay = $imageContainer.find('.loading-overlay');

        // Ensure loading overlay structure exists
        if ($loadingOverlay.length === 0) {
          this.restoreLoadingOverlay($imageContainer);
        }

        if ($existingImg.length > 0) {
          $existingImg.attr('src', '../images/thumb-00.png').attr('alt', '').show();
        } else {
          // Create default image if not exists
          const $defaultImg = $('<img src="../images/thumb-00.png" alt="">');
          const $overlay = $imageContainer.find('.loading-overlay');
          if ($overlay.length > 0) {
            $overlay.before($defaultImg);
          } else {
            $imageContainer.prepend($defaultImg);
          }
        }

        // Update floating actions state when resetting to placeholder
        setTimeout(() => {
          NetiAIImageGeneration.updateFloatingActionsBasedOnImage();
        }, 50);
      }
    },

    // Restore loading overlay structure when missing
    restoreLoadingOverlay: function($container) {
      // Get translation for "seconds"
      const secondsLabel = window.AIImageGeneration && window.AIImageGeneration.translation
        ? window.AIImageGeneration.translation.seconds
        : 'seconds';

      const loadingOverlayHtml = `
        <div class="loading-overlay" style="display: none;">
          <div class="loading-spinner"></div>
          <div class="loading-message">送出請求中...</div>
          <div class="loading-timer">00.00 ${secondsLabel}</div>
          <div class="loading-progress">
            <div class="progress-bar">
              <div class="progress-fill"></div>
            </div>
          </div>
        </div>
      `;

      $container.append(loadingOverlayHtml);
      console.log('Loading overlay structure restored');
    },

    // Insert image to editor
    insertToEditor: function() {
      if (!this.hasGeneratedImage()) {
        this.showError('沒有圖片可供插入');
        return;
      }

      const $image = $(this.config.container).find('.image-placeholder .ai-generated-image');
      const imageUrl = $image.attr('src');

      console.log('Inserting image to editor:', imageUrl);

      // Trigger custom event for parent component to handle
      $(this.config.container).trigger('insertToEditor', [imageUrl]);

      this.showSuccess('圖片已插入編輯器');
    },

    // Download generated image
    downloadImage: function() {
      if (!this.hasGeneratedImage()) {
        this.showError('沒有圖片可供下載');
        return;
      }

      const $image = $(this.config.container).find('.image-placeholder .ai-generated-image');
      const imageUrl = $image.attr('src');

      console.log('Downloading image:', imageUrl);

      // Extract file extension from URL or default to webp
      const getFileExtension = (url) => {
        const match = url.match(/\.([a-zA-Z0-9]+)(?:\?|$)/);
        return match ? match[1] : 'webp';
      };

      const fileExtension = getFileExtension(imageUrl);
      const timestamp = Date.now();
      const fileName = `ai-generated-image-${timestamp}.${fileExtension}`;

      // Create download link
      const link = document.createElement('a');
      link.href = imageUrl;
      link.download = fileName;
      link.click();

      console.log('Downloading as:', fileName);
      this.showSuccess('圖片下載已開始');
    },

    // Load history image
    loadHistoryImage: function($item) {
      console.log('Loading history image');

      // Get image from history item (could be background or img element)
      const $img = $item.find('img');
      if ($img.length > 0) {
        const imageUrl = $img.attr('src');
        this.displayGeneratedImage(imageUrl);

        // Update floating actions after loading history image
        setTimeout(() => {
          this.updateFloatingActionsBasedOnImage();
        }, 100);
      }

      // Trigger custom event
      $(this.config.container).trigger('historyImageLoaded', [$item]);
    },

    // Auto-resize textarea using logic from reference file
    autoResizeTextarea: function($textarea) {
      if (!$textarea || !$textarea.length) return;

      const element = $textarea[0];

      // Get the stored min height or calculate it
      if (!element._minHeight) {
        this.calculateMinHeight($textarea);
      }

      const minHeight = element._minHeight;
      const maxHeight = 400;

      // If content is empty, directly set to min height
      if (!element.value) {
        element.style.height = minHeight + 'px';
        element.style.overflowY = 'hidden';
        console.log('Auto-resize: Empty content, set to min height:', minHeight + 'px');
        return;
      }

      // Reset height to min height to get accurate scrollHeight (following reference file logic)
      element.style.height = minHeight + 'px';

      // Get required height based on content
      const scrollHeight = element.scrollHeight;

      // Calculate final height within constraints
      const newHeight = Math.min(Math.max(scrollHeight, minHeight), maxHeight);

      // Apply new height
      element.style.height = newHeight + 'px';

      // Handle overflow for content exceeding max height
      if (scrollHeight > maxHeight) {
        element.style.overflowY = 'auto';
      } else {
        element.style.overflowY = 'hidden';
      }

      console.log('Auto-resize: content length:', element.value.length, 'scrollHeight:', scrollHeight, 'newHeight:', newHeight);
    },

    // Calculate min height based on computed styles
    calculateMinHeight: function($textarea) {
      const element = $textarea[0];
      const styles = window.getComputedStyle(element);

      const lineHeight = parseInt(styles.lineHeight);
      const paddingTop = parseInt(styles.paddingTop) || 0;
      const paddingBottom = parseInt(styles.paddingBottom) || 0;
      const borderTop = parseInt(styles.borderTopWidth) || 0;
      const borderBottom = parseInt(styles.borderBottomWidth) || 0;

      // Store calculated min height on element
      element._minHeight = lineHeight + paddingTop + paddingBottom + borderTop + borderBottom;
    },

    // Initialize auto-resize for prompt textarea
    initAutoResizeTextarea: function() {
      const $textarea = $(this.config.selectors.promptTextarea);

      if ($textarea.length === 0) return;

      const element = $textarea[0];

      // Set basic styles (must be set before calculating min height)
      element.style.boxSizing = 'border-box';
      element.style.maxHeight = '400px';
      element.style.overflow = 'hidden';
      element.style.resize = 'none';

      // Calculate and store min height
      this.calculateMinHeight($textarea);

      // Set initial height to min height
      element.style.height = element._minHeight + 'px';

      // Initial adjustment for existing content (following reference file logic)
      const self = this;
      setTimeout(() => {
        if (element.value) {
          self.autoResizeTextarea($textarea);
        }
      }, 0);

      console.log('Auto-resize textarea initialized with min height:', element._minHeight + 'px');
    },

    // Initialize visibility observer - listening to tab click events (the root cause)
    initVisibilityObserver: function() {
      console.log('🔍 Starting initVisibilityObserver...');
      console.log('📝 Found root cause: #nme-aiimagegeneration is controlled by sidePanel tab system');

      const self = this;

      // Method 1: Listen to tab click events (most reliable)
      $(document).on('click', '.nme-setting-panels-tabs a', function() {
        const targetId = $(this).data('target-id');
        console.log('🗁 Tab clicked, target ID:', targetId);

        if (targetId === 'nme-aiimagegeneration') {
          console.log('✅ AI Image Generation tab clicked! Scheduling textarea height refresh...');

          // Wait for DOM to update after tab switch
          setTimeout(() => {
            console.log('⚡ Executing onContainerVisible() after tab switch...');
            self.onContainerVisible();
          }, 100);
        }
      });

      // Method 2: Check initial state if tab is already active
      const checkInitialState = () => {
        const currentContainer = document.querySelector('#nme-aiimagegeneration');
        if (currentContainer && currentContainer.classList.contains('is-active')) {
          console.log('⚡ INITIAL STATE: AI tab is already active! Triggering height refresh...');
          setTimeout(() => {
            self.onContainerVisible();
          }, 100);
          return true;
        }
        return false;
      };

      // Check initial state with multiple attempts
      if (!checkInitialState()) {
        setTimeout(checkInitialState, 500);
        setTimeout(checkInitialState, 1000);
      }

      console.log('✅ Tab click event listener initialized');
      console.log('🛠️ Simple and reliable solution based on actual tab switching mechanism');
    },

    // Handle container becoming visible with enhanced debugging
    onContainerVisible: function() {
      console.log('🚀 onContainerVisible() called!');

      const $textarea = $(this.config.selectors.promptTextarea);
      console.log('🔍 Textarea selector:', this.config.selectors.promptTextarea);
      console.log('🔍 Textarea found:', $textarea.length > 0);

      if ($textarea.length === 0) {
        console.error('❌ Textarea not found with selector:', this.config.selectors.promptTextarea);
        return;
      }

      const element = $textarea[0];
      console.log('📏 Textarea dimensions:', {
        offsetWidth: element.offsetWidth,
        offsetHeight: element.offsetHeight,
        scrollHeight: element.scrollHeight,
        value: element.value,
        valueLength: element.value ? element.value.length : 0
      });

      // Check if container is actually visible now
      if (element.offsetHeight === 0) {
        console.log('⚠️ Container still not visible (offsetHeight = 0), skipping height recalculation');
        return;
      }

      console.log('✅ Container is visible! Proceeding with height recalculation...');

      // Recalculate min height since previous calculation was done when hidden
      console.log('🔄 Recalculating min height...');
      const oldMinHeight = element._minHeight;
      this.calculateMinHeight($textarea);
      const newMinHeight = element._minHeight;

      console.log('📐 Min height calculation:', {
        oldMinHeight: oldMinHeight,
        newMinHeight: newMinHeight,
        changed: oldMinHeight !== newMinHeight
      });

      // Reset and recalculate height
      console.log('🔄 Resetting textarea height...');
      element.style.height = element._minHeight + 'px';

      if (element.value) {
        console.log('📝 Textarea has content, calling autoResizeTextarea...');
        this.autoResizeTextarea($textarea);
      } else {
        console.log('📝 Textarea is empty, keeping min height');
      }

      console.log('✅ Textarea height refresh completed!', {
        finalHeight: element.style.height,
        finalScrollHeight: element.scrollHeight,
        minHeight: element._minHeight
      });
    },

    // Loading state manager for staged progress display
    loadingManager: {
      // Stage configuration with time intervals and messages
      stages: [
        {
          message: function() {
            return window.AIImageGeneration && window.AIImageGeneration.translation
              ? window.AIImageGeneration.translation.stage1
              : 'Preparing your image...';
          },
          duration: 5000,
          progress: 5
        },
        {
          message: function() {
            return window.AIImageGeneration && window.AIImageGeneration.translation
              ? window.AIImageGeneration.translation.stage2
              : 'Analyzing and adjusting your prompt to help generate a better image...';
          },
          duration: 5000,
          progress: 15
        },
        {
          message: function() {
            return window.AIImageGeneration && window.AIImageGeneration.translation
              ? window.AIImageGeneration.translation.stage3
              : 'Starting the composition...';
          },
          duration: 8000,
          progress: 35
        },
        {
          message: function() {
            return window.AIImageGeneration && window.AIImageGeneration.translation
              ? window.AIImageGeneration.translation.stage4
              : 'The image is taking shape...';
          },
          duration: 10000,
          progress: 55
        },
        {
          message: function() {
            return window.AIImageGeneration && window.AIImageGeneration.translation
              ? window.AIImageGeneration.translation.stage5
              : 'Refining the details...';
          },
          duration: 9000,
          progress: 75
        },
        {
          message: function() {
            return window.AIImageGeneration && window.AIImageGeneration.translation
              ? window.AIImageGeneration.translation.stage6
              : 'Finalizing the image...';
          },
          duration: 8000,
          progress: 90
        },
        {
          message: function() {
            return window.AIImageGeneration && window.AIImageGeneration.translation
              ? window.AIImageGeneration.translation.stage7
              : 'The system is a bit busy. We\'re speeding things up - please hold on...';
          },
          duration: 0,
          progress: 95
        }
      ],

      currentStage: 0,
      timers: [],
      isActive: false,

      // Timer related properties
      startTime: null,
      timerInterval: null,

      // Show loading overlay with staged progress
      show: function() {
        const $container = $(NetiAIImageGeneration.config.container);
        const $overlay = $container.find('.loading-overlay');
        const $image = $container.find('.image-placeholder img');
        const $loadingInfo = $container.find('.loading-info');

        // Hide existing image and show loading overlay
        $image.hide();
        $overlay.show();

        // Restore loading elements that may have been hidden by errorManager
        const $loadingElements = $overlay.find('.loading-spinner, .loading-message, .loading-timer, .loading-progress');
        $loadingElements.show();

        // Hide error state if it was showing
        const $errorState = $overlay.find('.error-state');
        $errorState.hide();

        // Show and update loading info with translation
        const loadingInfoText = window.AIImageGeneration && window.AIImageGeneration.translation
          ? window.AIImageGeneration.translation.loadingInfo
          : 'Your image is being generated and usually takes about 40–45 seconds to complete. Feel free to do something else — we\'re working hard to finish your artwork!';

        $loadingInfo.find('.loading-info-text').text(loadingInfoText);
        $loadingInfo.show();

        // Hide floating actions during loading
        NetiAIImageGeneration.setFloatingActionsState('hidden');

        // Reset state
        this.currentStage = 0;
        this.isActive = true;
        this.clearTimers();

        // Initialize timer
        this.startTimer();

        // Start stage progression
        this.nextStage();

        console.log('Loading state manager: Started');
      },

      // Hide loading overlay
      hide: function() {
        const $container = $(NetiAIImageGeneration.config.container);
        const $overlay = $container.find('.loading-overlay');
        const $image = $container.find('.image-placeholder img');
        const $loadingInfo = $container.find('.loading-info');

        // Clear all timers
        this.clearTimers();
        this.stopTimer();
        this.isActive = false;

        // Reset loading state to initial values
        this.resetLoadingState();

        // Hide loading overlay, loading info and show image
        $overlay.hide();
        $loadingInfo.hide();
        $image.show();

        console.log('Loading state manager: Stopped');
      },

      // Progress to next stage
      nextStage: function() {
        if (!this.isActive || this.currentStage >= this.stages.length) {
          return;
        }

        const stage = this.stages[this.currentStage];
        const message = typeof stage.message === 'function' ? stage.message() : stage.message;
        this.updateMessage(message);
        this.updateProgress(stage.progress);

        console.log('Loading stage:', this.currentStage + 1, '-', stage.message);

        // Set timer for next stage if not the last stage and has duration
        if (this.currentStage < this.stages.length - 1 && stage.duration > 0) {
          const timer = setTimeout(() => {
            if (this.isActive) {
              this.currentStage++;
              this.nextStage();
            }
          }, stage.duration);

          this.timers.push(timer);
        }

        // Note: currentStage is incremented in setTimeout callback, not here
      },

      // Update loading message
      updateMessage: function(message) {
        const $container = $(NetiAIImageGeneration.config.container);
        $container.find('.loading-message').text(message);
      },

      // Update progress bar
      updateProgress: function(progress) {
        const $container = $(NetiAIImageGeneration.config.container);
        $container.find('.progress-fill').css('width', progress + '%');
      },

      // Clear all timers
      clearTimers: function() {
        this.timers.forEach(timer => clearTimeout(timer));
        this.timers = [];
      },

      // Start the timer display
      startTimer: function() {
        this.startTime = Date.now();
        this.updateTimer();

        // Update timer every 10ms for millisecond precision
        this.timerInterval = setInterval(() => {
          if (this.isActive) {
            this.updateTimer();
          }
        }, 10);
      },

      // Stop the timer
      stopTimer: function() {
        if (this.timerInterval) {
          clearInterval(this.timerInterval);
          this.timerInterval = null;
        }
        this.startTime = null;
      },

      // Update timer display
      updateTimer: function() {
        if (!this.startTime || !this.isActive) return;

        const elapsed = Date.now() - this.startTime;
        const seconds = Math.floor(elapsed / 1000);
        const milliseconds = Math.floor((elapsed % 1000) / 10); // Display centiseconds (00-99)

        // Format as SS.MM (seconds.centiseconds)
        const formattedTime = `${seconds.toString().padStart(2, '0')}.${milliseconds.toString().padStart(2, '0')}`;

        // Get translation for "seconds"
        const secondsLabel = window.AIImageGeneration && window.AIImageGeneration.translation
          ? window.AIImageGeneration.translation.seconds
          : 'seconds';

        const displayText = `${formattedTime} ${secondsLabel}`;

        const $container = $(NetiAIImageGeneration.config.container);
        $container.find('.loading-timer').text(displayText);
      },

      // Reset loading state to initial values
      resetLoadingState: function() {
        const $container = $(NetiAIImageGeneration.config.container);

        // Get translation for "seconds"
        const secondsLabel = window.AIImageGeneration && window.AIImageGeneration.translation
          ? window.AIImageGeneration.translation.seconds
          : 'seconds';

        // Reset progress bar to 0%
        $container.find('.progress-fill').css('width', '0%');

        // Reset message to initial state
        $container.find('.loading-message').text('送出請求中...');

        // Reset timer to initial state
        $container.find('.loading-timer').text(`00.00 ${secondsLabel}`);

        // Reset internal state
        this.currentStage = 0;

        console.log('Loading state reset to initial values');
      }
    },

    // Initialize tooltips using powerTip (same as sidePanel)
    initializeTooltips: function() {
      // Use same tooltip system as sidePanel
      var jq = $.fn.powerTip ? $ : jQuery.fn.powerTip ? jQuery : null;

      if (jq) {
        if ($(this.config.container).find('[data-tooltip]').length) {
          $(this.config.container).find('[data-tooltip]:not(.tooltip-initialized)').each(function() {
            let options = {};

            if ($(this).is('[data-tooltip-placement]')) {
              options.placement = $(this).data('tooltip-placement');
            }

            if ($(this).is('[data-tooltip-fadeouttime]')) {
              options.fadeOutTime = $(this).data('tooltip-fadeouttime');
            }

            jq(this).powerTip(options);
            $(this).addClass('tooltip-initialized');
          });
        }
      }
    },

    // Initialize image lightbox using Magnific Popup
    initImageLightbox: function() {
      // Check if Magnific Popup is available
      if (typeof $.magnificPopup !== 'undefined') {
        // Initialize lightbox for AI generated images using standard method
        this.setupImageLightbox();
        console.log('Image lightbox initialized');
      } else {
        console.warn('Magnific Popup not available - lightbox functionality disabled');
      }
    },

    // Setup image lightbox using standard Magnific Popup method
    setupImageLightbox: function() {
      // Initialize Magnific Popup for AI image links using standard method
      $(document).magnificPopup({
        delegate: '.ai-image-link',
        type: 'image',
        image: {
          titleSrc: 'AI 生成圖片'
        },
        closeOnContentClick: true,
        mainClass: 'mfp-with-zoom',
        zoom: {
          enabled: true,
          duration: 300
        }
      });
    },

    // Show success message
    showSuccess: function(message) {
      console.log('Success:', message);

      // Trigger custom success event
      $(this.config.container).trigger('aiImageSuccess', [message]);
    },

    // Show error message
    showError: function(message) {
      console.error('Error:', message);

      // Trigger custom error event
      $(this.config.container).trigger('aiImageError', [message]);
    },

    // Update floating buttons state (legacy method, use updateFloatingActionsBasedOnImage instead)
    updateFloatingButtonsState: function() {
      // Legacy method - now uses image detection
      this.updateFloatingActionsBasedOnImage();
    },

    // Set floating actions state based on image availability
    setFloatingActionsState: function(state) {
      const $floatingActions = $(this.config.container).find('.floating-actions');
      const $floatingBtns = $floatingActions.find(this.config.selectors.floatingBtn);

      switch(state) {
        case 'hidden':
          // Hide entire floating actions container (loading or no image)
          $floatingActions.hide();
          $floatingBtns.prop('disabled', true).addClass(this.config.classes.disabled);
          console.log('Floating actions: Hidden (no image or loading)');
          break;

        case 'enabled':
          // Show floating actions and enable all buttons (has real image)
          $floatingActions.show();
          $floatingBtns.prop('disabled', false).removeClass(this.config.classes.disabled);
          console.log('Floating actions: Visible and enabled (has image)');
          break;

        default:
          console.warn('Invalid floating actions state:', state, 'Valid states: hidden, enabled');
      }
    },

    // Update floating actions based on current image state
    updateFloatingActionsBasedOnImage: function() {
      if (this.hasGeneratedImage()) {
        this.setFloatingActionsState('enabled');
      } else {
        this.setFloatingActionsState('hidden');
      }
    },

    // Check if current image exists and is a real generated image
    hasGeneratedImage: function() {
      const $image = $(this.config.container).find('.image-placeholder .ai-generated-image');

      if ($image.length === 0) {
        return false;
      }

      const src = $image.attr('src');
      if (!src) {
        return false;
      }

      // Check if it's a placeholder image (not a real generated image)
      const isPlaceholder = src.includes('thumb-00.png') ||
                           src.includes('placeholder') ||
                           src.endsWith('thumb-00.png');

      return !isPlaceholder;
    },

    // Public API methods
    api: {
      // Set prompt text with auto-resize
      setPrompt: function(text) {
        const $textarea = $(NetiAIImageGeneration.config.container)
          .find(NetiAIImageGeneration.config.selectors.promptTextarea);

        $textarea.val(text);

        // Trigger auto-resize after setting text
        requestAnimationFrame(function() {
          NetiAIImageGeneration.autoResizeTextarea($textarea);
        });
      },

      // Get current prompt
      getPrompt: function() {
        return $(NetiAIImageGeneration.config.container)
          .find(NetiAIImageGeneration.config.selectors.promptTextarea)
          .val();
      },

      // Set style
      setStyle: function(style) {
        const $option = $(NetiAIImageGeneration.config.container)
          .find(`[data-style="${style}"]`);
        if ($option.length > 0) {
          NetiAIImageGeneration.selectStyleOption($option);
        }
      },

      // Set ratio
      setRatio: function(ratio) {
        const $option = $(NetiAIImageGeneration.config.container)
          .find(`[data-ratio="${ratio}"]`);
        if ($option.length > 0) {
          NetiAIImageGeneration.selectRatioOption($option);
        }
      },

      // Trigger generation
      generate: function() {
        NetiAIImageGeneration.generateImage();
      },

      // Refresh textarea height (useful when container becomes visible)
      refreshTextareaHeight: function() {
        NetiAIImageGeneration.onContainerVisible();
      },

      // Loading state management
      showLoading: function() {
        NetiAIImageGeneration.loadingManager.show();
      },

      hideLoading: function() {
        NetiAIImageGeneration.loadingManager.hide();
      }
    },

    // Error state manager
    errorManager: {

      // Show error state
      show: function(errorData) {
        const $container = $(NetiAIImageGeneration.config.container);
        const $loadingOverlay = $container.find('.loading-overlay');
        const $loadingElements = $loadingOverlay.find('.loading-spinner, .loading-message, .loading-timer, .loading-progress, .loading-info');
        const $errorState = $loadingOverlay.find('.error-state');

        // Hide loading elements
        $loadingElements.hide();

        // Update error message
        this.updateErrorMessage(errorData);

        // Show error state
        $errorState.show();

        // Ensure loading-overlay remains visible
        $loadingOverlay.show();

        // Hide floating actions
        NetiAIImageGeneration.setFloatingActionsState('hidden');
      },

      // Hide error state
      hide: function() {
        const $container = $(NetiAIImageGeneration.config.container);
        const $loadingOverlay = $container.find('.loading-overlay');
        const $errorState = $loadingOverlay.find('.error-state');

        // Hide error state and loading overlay
        $errorState.hide();
        $loadingOverlay.hide();

        // Restore floating actions state
        setTimeout(() => {
          NetiAIImageGeneration.updateFloatingActionsBasedOnImage();
        }, 100);
      },

      // Reset error state without hiding loading overlay (for regeneration)
      reset: function() {
        const $container = $(NetiAIImageGeneration.config.container);
        const $loadingOverlay = $container.find('.loading-overlay');
        const $errorState = $loadingOverlay.find('.error-state');

        // Hide error state only
        $errorState.hide();
      },

      // Update error message content
      updateErrorMessage: function(errorData) {
        const $container = $(NetiAIImageGeneration.config.container);
        const friendlyMessage = this.getFriendlyErrorMessage(errorData.message, errorData.httpStatus);

        $container.find('.error-reason').text(friendlyMessage);
      },

      // Convert various errors to user-friendly messages
      getFriendlyErrorMessage: function(technicalMessage, httpStatus) {
        // JSON response error mappings
        const jsonErrorMappings = {
          'The request is not a valid JSON format.': '請求格式錯誤，請重新整理頁面後重試',
          'The request does not match the expected format.': '請求參數錯誤，請檢查輸入內容',
          'Content exceeds the maximum character limit.': '描述文字過長，請縮短至 1000 字以內',
          'No corresponding component was found.': '頁面權限錯誤，請重新整理頁面',
          'Invalid request method or missing data.': '系統錯誤，請重新整理頁面後重試'
        };

        // HTTP status code mappings
        const httpStatusMappings = {
          400: '請求參數有誤，請檢查輸入內容',
          401: '登入已過期，請重新登入',
          403: '權限不足，請聯絡管理員',
          404: '服務暫時無法使用，請稍後重試',
          408: '請求逾時，請檢查網路連線',
          429: '使用頻率過高，請稍後重試',
          500: '伺服器暫時錯誤，請稍後重試',
          502: '服務暫時無法連線，請稍後重試',
          503: '服務暫時維護中，請稍後重試',
          504: '連線逾時，請檢查網路連線'
        };

        // Network connection error mappings
        const networkErrorMappings = {
          'network error': '網路連線中斷，請檢查網路狀態',
          'timeout': '連線逾時，請重新整理頁面後重試',
          'connection refused': '無法連接到伺服器，請稍後重試',
          'dns error': '網路設定問題，請檢查網路連線'
        };

        // 1. Priority check JSON response error messages
        if (jsonErrorMappings[technicalMessage]) {
          return jsonErrorMappings[technicalMessage];
        }

        // 2. Check for Image generation failed type
        if (technicalMessage.includes('Image generation failed')) {
          return '圖片生成失敗，請稍後重試';
        }

        // 3. Check HTTP status code
        if (httpStatus && httpStatusMappings[httpStatus]) {
          return httpStatusMappings[httpStatus];
        }

        // 4. Check network connection errors (fuzzy match)
        const lowerMessage = technicalMessage.toLowerCase();
        for (const [key, message] of Object.entries(networkErrorMappings)) {
          if (lowerMessage.includes(key)) {
            return message;
          }
        }

        // 5. Default error message
        return '圖片生成過程中發生錯誤，請稍後重試';
      }
    }
  };

  // Initialize when document is ready
  $(document).ready(function() {
    // Check if component container exists
    if ($(NetiAIImageGeneration.config.container).length > 0) {
      NetiAIImageGeneration.init();
    }
  });

  // Cleanup events when page unloads
  $(window).on('beforeunload', function() {
    $(document).off('click', '.nme-setting-panels-tabs a');
  });

  // Expose API to global scope
  window.NetiAIImageGeneration = NetiAIImageGeneration.api;

})(jQuery);