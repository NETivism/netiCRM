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
      this.initializeTooltips();
      this.initAutoResizeTextarea();
      this.initVisibilityObserver();

      // Initialize floating buttons state (disabled by default)
      this.updateFloatingButtonsState(false);

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

      // Update image container aspect ratio
      this.updateImageAspectRatio(ratio);

      // Trigger custom event
      $(this.config.container).trigger('ratioChanged', [ratio]);
    },

    // Update image aspect ratio based on selection
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
        $imageContainer.css('aspect-ratio', ratioMap[ratio]);
      }
    },

    // Handle floating action buttons
    handleFloatingAction: function($button) {
      // Check if button is disabled
      if ($button.hasClass(this.config.classes.disabled)) {
        this.showError('請先生成圖片');
        return;
      }

      const tooltip = $button.attr('data-tooltip');

      switch(tooltip) {
        case 'Regenerate':
          this.generateImage();
          break;
        case 'Insert to Editor':
          this.insertToEditor();
          break;
        case 'Download Image':
          this.downloadImage();
          break;
        default:
          console.log('Unknown floating action:', tooltip);
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
        timeout: 60000, // 60 seconds timeout

        success: function(response) {
          // Hide loading overlay
          self.loadingManager.hide();

          if (response.status === 1 && response.data) {
            self.onGenerationComplete(response.data.image_url, response.data);
            self.showSuccess('圖片生成成功！');
          } else {
            self.onGenerationComplete();
            self.showError(response.message || '圖片生成失敗');
          }
        },

        error: function(xhr, status, error) {
          // Hide loading overlay
          self.loadingManager.hide();

          self.onGenerationComplete();

          let errorMessage = '圖片生成失敗';

          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          } else if (status === 'timeout') {
            errorMessage = '請求超時，請重試';
          } else if (xhr.status === 400) {
            errorMessage = '請求參數錯誤';
          } else if (xhr.status === 403) {
            errorMessage = '權限不足';
          } else if (xhr.status === 500) {
            errorMessage = '伺服器內部錯誤';
          }

          self.showError(errorMessage);
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

        // Enable floating action buttons when image is generated
        this.updateFloatingButtonsState(true);

        console.log('Image generation completed successfully:', {
          imageUrl: imageUrl,
          responseData: responseData
        });
      } else {
        // Disable floating action buttons when no image
        this.updateFloatingButtonsState(false);
        console.log('Image generation completed without result');
      }

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
          
          // Remove old image if exists and add new one
          $existingImg.remove();
          
          // Insert new image before loading-overlay to maintain structure
          const $overlay = $imageContainer.find('.loading-overlay');
          if ($overlay.length > 0) {
            $overlay.before($img);
          } else {
            $imageContainer.prepend($img);
          }
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
      }
    },

    // Restore loading overlay structure when missing
    restoreLoadingOverlay: function($container) {
      const loadingOverlayHtml = `
        <div class="loading-overlay" style="display: none;">
          <div class="loading-spinner"></div>
          <div class="loading-message">送出請求中...</div>
          <div class="loading-timer">00.00</div>
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
      const $image = $(this.config.container).find('.image-placeholder img');

      if ($image.length > 0) {
        const imageUrl = $image.attr('src');
        console.log('Inserting image to editor:', imageUrl);

        // Trigger custom event for parent component to handle
        $(this.config.container).trigger('insertToEditor', [imageUrl]);

        this.showSuccess('圖片已插入編輯器');
      } else {
        this.showError('沒有圖片可供插入');
      }
    },

    // Download generated image
    downloadImage: function() {
      const $image = $(this.config.container).find('.image-placeholder img');

      if ($image.length > 0) {
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
      } else {
        this.showError('沒有圖片可供下載');
      }
    },

    // Load history image
    loadHistoryImage: function($item) {
      console.log('Loading history image');

      // Get image from history item (could be background or img element)
      const $img = $item.find('img');
      if ($img.length > 0) {
        const imageUrl = $img.attr('src');
        this.displayGeneratedImage(imageUrl);
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
        { message: '送出請求中...', duration: 5000, progress: 5 },
        { message: '轉譯您的提示詞...', duration: 5000, progress: 10 },
        { message: '產製圖片中...', duration: 10000, progress: 20 },
        { message: '產製圖片中...', duration: 10000, progress: 60 },
        { message: '產製圖片中...', duration: 5000, progress: 80 },
        { message: '系統繁忙，正在啟用備援通道加速，請稍候...', duration: 0, progress: 90 }
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

        // Hide existing image and show loading overlay
        $image.hide();
        $overlay.show();

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

        // Clear all timers
        this.clearTimers();
        this.stopTimer();
        this.isActive = false;

        // Hide loading overlay and show image
        $overlay.hide();
        $image.show();

        console.log('Loading state manager: Stopped');
      },

      // Progress to next stage
      nextStage: function() {
        if (!this.isActive || this.currentStage >= this.stages.length) {
          return;
        }

        const stage = this.stages[this.currentStage];
        this.updateMessage(stage.message);
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

        const $container = $(NetiAIImageGeneration.config.container);
        $container.find('.loading-timer').text(formattedTime);
      }
    },

    // Initialize tooltips (if tooltip library is available)
    initializeTooltips: function() {
      // Check if tooltip library is available
      if (typeof tippy !== 'undefined') {
        tippy('[data-tooltip]', {
          content(reference) {
            return reference.getAttribute('data-tooltip');
          },
          placement: 'top',
          theme: 'dark',
          maxWidth: 280,
          animation: 'scale',
          delay: [200, 100],
          duration: [300, 200],
          allowHTML: true,
        });
      }
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

    // Update floating buttons state
    updateFloatingButtonsState: function(hasImage) {
      const $floatingBtns = $(this.config.container).find(this.config.selectors.floatingBtn);

      if (hasImage) {
        $floatingBtns.removeClass(this.config.classes.disabled);
      } else {
        $floatingBtns.addClass(this.config.classes.disabled);
      }
    },

    // Check if current image exists
    hasGeneratedImage: function() {
      const $image = $(this.config.container).find('.image-placeholder img');
      return $image.length > 0 && $image.attr('src');
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