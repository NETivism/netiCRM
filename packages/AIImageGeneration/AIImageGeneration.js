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
        dropdownItems: '.dropdown-item',
        floatingMessage: '.floating-message',
        floatingMessageIcon: '.floating-message-icon',
        floatingMessageText: '.floating-message-text'
      },
      classes: {
        active: 'active',
        selected: 'selected',
        loading: 'loading',
        disabled: 'disabled'
      }
    },

    // State management for tooltip timers
    tooltipTimers: {},

    // Context marking for trigger source tracking
    _aiLinkTriggerContext: null,

    // Initialize component
    init: function() {
      this.bindEvents();
      this.initAutoResizeTextarea();
      this.initVisibilityObserver();
      this.initImageLightbox();

      // Initialize empty state visibility
      this.initEmptyState();

      // Initialize floating actions state based on current image
      this.updateFloatingActionsBasedOnImage();

      // Initialize sample image loading mechanism
      this.initSampleImageLoading();

      // Initialize tooltip based on existing content
      this.initPromptTooltip();

      // Initialize file upload field integration
      this.initFileUploadIntegration();

      console.log('AI Image Generation component initialized');
    },

    // Initialize empty state visibility
    initEmptyState: function() {
      const $container = $(this.config.container);
      const $emptyState = $container.find('.empty-state-content');
      const $image = $container.find('.image-placeholder img');

      // Show empty state and hide placeholder image on initial load
      if (!this.hasGeneratedImage()) {
        $emptyState.show();
        $image.hide();
      } else {
        $emptyState.hide();
        $image.show();
      }
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

      // Sample retry button
      $(document).on('click', '.sample-retry-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        self.handleSampleRetry();
      });

      // Note: History items now use lightbox directly via AIImageGeneration-History.js
      // No need for custom click handlers as Magnific Popup handles the lightbox functionality

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

        // Update tooltip based on content
        self.updatePromptTooltip($(this));
      });

      // Also listen for change event (for paste operations)
      $(document).on('change', self.config.selectors.promptTextarea, function() {
        self.updatePromptTooltip($(this));
      });

      // Confirm dialog events
      $(document).on('click', '.netiaiig-confirm-dialog .dialog-confirm', function(e) {
        e.preventDefault();
        e.stopPropagation();
        self.handleConfirmReplace();
      });

      $(document).on('click', '.netiaiig-confirm-dialog .dialog-cancel', function(e) {
        e.preventDefault();
        e.stopPropagation();
        self.handleCancelReplace();
      });

      $(document).on('click', '.netiaiig-confirm-dialog .dialog-close', function(e) {
        e.preventDefault();
        e.stopPropagation();
        self.handleDialogClose();
      });

      // Close dialog when clicking on overlay
      $(document).on('click', '.netiaiig-confirm-dialog .dialog-overlay', function(e) {
        e.preventDefault();
        e.stopPropagation();
        self.handleDialogClose();
      });

      // Prevent dialog close when clicking inside dialog content
      $(document).on('click', '.netiaiig-confirm-dialog .dialog-content', function(e) {
        e.stopPropagation();
      });

      // ESC key to close dialog
      $(document).on('keydown', function(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
          const $dialog = $('.netiaiig-confirm-dialog');
          if ($dialog.is(':visible')) {
            e.preventDefault();
            self.handleDialogClose();
          }
        }
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
      const styleLabel = $option.find('.style-label').text() || style; // Use translated text or fallback to data-style
      const $container = $option.closest('.netiaiig-dropdown');

      // Update selected state
      $option.siblings().removeClass(this.config.classes.selected);
      $option.addClass(this.config.classes.selected);

      // Update button text with translated label
      $container.find(this.config.selectors.styleText).text(styleLabel);

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

      // Trigger custom event
      $(this.config.container).trigger('ratioChanged', [ratio]);
    },


    // Handle floating action buttons
    handleFloatingAction: function($button) {
      // Check if button is disabled (both HTML attribute and CSS class)
      if ($button.prop('disabled') || $button.hasClass(this.config.classes.disabled)) {
        const message = window.AIImageGeneration && window.AIImageGeneration.translation
          ? window.AIImageGeneration.translation.functionNotAvailable
          : 'Function temporarily unavailable, please generate an image first';
        this.showError(message);
        return;
      }

      // Double check if we have a valid image
      if (!this.hasGeneratedImage()) {
        const message = window.AIImageGeneration && window.AIImageGeneration.translation
          ? window.AIImageGeneration.translation.pleaseGenerateFirst
          : 'Please generate an image first';
        this.showError(message);
        return;
      }

      // Get action from title attribute or data-tooltip for backwards compatibility
      const title = $button.attr('title') || $button.attr('data-tooltip');

      // Determine action based on icon class or title
      if ($button.find('.zmdi-refresh').length || title.includes('Regenerate')) {
        this.generateImage();
      } else if ($button.find('.zmdi-collection-plus').length || title.includes('Copy')) {
        this.copyImage();
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

      // Clear sample error state when starting new generation
      this.clearSampleErrorIfNeeded();

      // Input validation
      if (!prompt) {
        const message = window.AIImageGeneration && window.AIImageGeneration.translation
          ? window.AIImageGeneration.translation.pleaseEnterDescription
          : 'Please enter image description';
        this.showError(message);
        return;
      }

      if (prompt.length > 1000) {
        const message = window.AIImageGeneration && window.AIImageGeneration.translation
          ? window.AIImageGeneration.translation.descriptionTooLong
          : 'Description text exceeds 1000 character limit';
        this.showError(message);
        return;
      }

      // Hide any existing error state when starting new generation
      this.errorManager.reset();

      // Get current settings
      // Get style from selected option's data-style attribute (original English value for API)
      const selectedStyleOption = $(this.config.container).find(this.config.selectors.styleOptions + '.selected');
      const style = selectedStyleOption.length > 0 ? selectedStyleOption.data('style') : 'Simple Illustration';
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
          .text(window.AIImageGeneration && window.AIImageGeneration.translation
            ? window.AIImageGeneration.translation.generating
            : 'Generating image...');

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
            const message = window.AIImageGeneration && window.AIImageGeneration.translation
              ? window.AIImageGeneration.translation.generateSuccess
              : 'Image generated successfully!';
            self.showSuccess(message);
          } else {
            // Failure: show error message
            self.onGenerationComplete();
            const defaultMessage = window.AIImageGeneration && window.AIImageGeneration.translation
              ? window.AIImageGeneration.translation.generateFailed
              : 'Image generation failed';
            self.errorManager.show({
              message: response.message || defaultMessage
            });
          }
        },

        error: function(xhr, status, error) {
          // Stop loading manager
          self.loadingManager.hide();

          // Reset generate button
          self.onGenerationComplete();

          // Handle HTTP errors
          let errorMessage = window.AIImageGeneration && window.AIImageGeneration.translation
            ? window.AIImageGeneration.translation.generateFailed
            : 'Image generation failed';

          // Try to parse JSON response
          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          } else if (status === 'timeout') {
            errorMessage = 'timeout'; // Will be converted to friendly message
          } else {
            // Other cases keep generic error message, handled by HTTP status code
            errorMessage = window.AIImageGeneration && window.AIImageGeneration.translation
              ? window.AIImageGeneration.translation.generateFailed
              : 'Image generation failed';
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

      // Clear sample error state when image generation completes successfully
      if (imageUrl) {
        this.clearSampleErrorIfNeeded();
      }

      // Reset button state
      $btn.prop('disabled', false)
          .removeClass(this.config.classes.loading)
          .text(window.AIImageGeneration && window.AIImageGeneration.translation
            ? window.AIImageGeneration.translation.generateButton
            : 'Generate Image');

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

        // Hide empty state content and show image
        $imageContainer.find('.empty-state-content').hide();

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
        const self = this; // Store reference to NetiAIImageGeneration instance
        img.onload = function() {
          console.log('Image loaded successfully - displaying now');
          const altText = window.AIImageGeneration && window.AIImageGeneration.translation
            ? window.AIImageGeneration.translation.aiGeneratedImage
            : 'AI Generated Image';
          $img.attr('alt', altText);

          // Add specific class for AI generated images
          $img.addClass('ai-generated-image');

          // Get current form data for lightbox metadata using correct context
          const currentPrompt = $(self.config.container).find(self.config.selectors.promptTextarea).val() || '';
          // Get original style value from selected option for data attribute (used by regenerate)
          const selectedStyleOption = $(self.config.container).find(self.config.selectors.styleOptions + '.selected');
          const currentStyle = selectedStyleOption.length > 0 ? selectedStyleOption.data('style') : 'Simple Illustration';
          const currentRatio = $(self.config.container).find(self.config.selectors.ratioText).text() || '';

          // Create anchor tag to wrap the image for lightbox functionality with metadata
          const $link = $('<a>').attr({
            'href': imageUrl,
            'class': 'ai-image-link',
            'data-prompt': currentPrompt,
            'data-style': currentStyle,
            'data-ratio': currentRatio
          }).append($img);

          // Remove old image and its link wrapper if exists
          const $existingLink = $imageContainer.find('.ai-image-link');
          if ($existingLink.length > 0) {
            $existingLink.remove();
          } else {
            $existingImg.remove();
          }

          // Insert new link before loading-overlay to maintain structure
          const $overlay = $imageContainer.find('.loading-overlay');
          if ($overlay.length > 0) {
            $overlay.before($link);
          } else {
            $imageContainer.prepend($link);
          }

          // Update floating actions state after image is successfully loaded
          setTimeout(() => {
            self.updateFloatingActionsBasedOnImage();
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
        // Reset to empty state, show empty state content
        const $existingImg = $imageContainer.find('img');
        const $loadingOverlay = $imageContainer.find('.loading-overlay');
        const $emptyStateContent = $imageContainer.find('.empty-state-content');

        // Show empty state content
        $emptyStateContent.show();

        // Hide existing image
        if ($existingImg.length > 0) {
          $existingImg.hide().attr('src', '../images/thumb-00.png').attr('alt', '');
        }

        // Remove any generated image links
        $imageContainer.find('.ai-image-link').remove();

        // Ensure loading overlay structure exists
        if ($loadingOverlay.length === 0) {
          this.restoreLoadingOverlay($imageContainer);
        }

        // Update floating actions state when resetting to empty state
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
          <div class="loading-message">` + (window.AIImageGeneration && window.AIImageGeneration.translation ? window.AIImageGeneration.translation.submittingRequest : 'Submitting request...') + `</div>
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

    // Copy image to clipboard
    copyImage: function() {
      if (!this.hasGeneratedImage()) {
        const message = window.AIImageGeneration && window.AIImageGeneration.translation
          ? window.AIImageGeneration.translation.noImageToCopy
          : 'No image available to copy';
        this.showError(message);
        return;
      }

      // Clear any existing tooltip timer to handle repeated clicks
      if (this.tooltipTimers.copyButton) {
        clearTimeout(this.tooltipTimers.copyButton);
        delete this.tooltipTimers.copyButton;
      }

      // Try to find generated image first, then sample image
      let $image = $(this.config.container).find('.image-placeholder .ai-generated-image');
      if ($image.length === 0) {
        $image = $(this.config.container).find('.image-placeholder .ai-sample-image');
      }
      const imageUrl = $image.attr('src');

      console.log('Copying image to clipboard:', imageUrl);

      // Check if clipboard API is supported
      if (!navigator.clipboard || !navigator.clipboard.write) {
        const message = window.AIImageGeneration && window.AIImageGeneration.translation
          ? window.AIImageGeneration.translation.browserNotSupported
          : 'Your browser does not support image copying feature';
        this.showError(message);
        return;
      }

      const self = this;

      // Create a new image element to load the image data
      const img = new Image();
      img.crossOrigin = 'anonymous'; // Handle CORS if needed

      img.onload = function() {
        try {
          // Create canvas to convert image to blob
          const canvas = document.createElement('canvas');
          const ctx = canvas.getContext('2d');

          canvas.width = img.naturalWidth;
          canvas.height = img.naturalHeight;

          // Draw image to canvas
          ctx.drawImage(img, 0, 0);

          // Convert canvas to blob
          canvas.toBlob(function(blob) {
            if (!blob) {
              const message = window.AIImageGeneration && window.AIImageGeneration.translation
                ? window.AIImageGeneration.translation.imageProcessFailed
                : 'Image processing failed';
              self.showError(message);
              return;
            }

            // Create clipboard item and copy to clipboard
            const clipboardItem = new ClipboardItem({ [blob.type]: blob });

            navigator.clipboard.write([clipboardItem]).then(function() {
              const message = window.AIImageGeneration && window.AIImageGeneration.translation
                ? window.AIImageGeneration.translation.imageCopied
                : 'Image copied to clipboard';
              self.showSuccess(message);
              console.log('Image copied to clipboard successfully');
            }).catch(function(error) {
              console.error('Failed to copy image to clipboard:', error);
              const message = window.AIImageGeneration && window.AIImageGeneration.translation
                ? window.AIImageGeneration.translation.copyFailed
                : 'Failed to copy image, please try again';
              self.showError(message);
            });
          }, 'image/png');

        } catch (error) {
          console.error('Error processing image for clipboard:', error);
          const message = window.AIImageGeneration && window.AIImageGeneration.translation
            ? window.AIImageGeneration.translation.imageProcessError
            : 'Error occurred during image processing';
          self.showLightboxMessage(message, 'error');
        }
      };

      img.onerror = function() {
        console.error('Failed to load image for copying');
        const message = window.AIImageGeneration && window.AIImageGeneration.translation
          ? window.AIImageGeneration.translation.imageLoadFailed
          : 'Failed to load image, please try again';
        self.showLightboxMessage(message, 'error');
      };

      // Load the image
      img.src = imageUrl;
    },

    // Download generated image
    downloadImage: function() {
      if (!this.hasGeneratedImage()) {
        const message = window.AIImageGeneration && window.AIImageGeneration.translation
          ? window.AIImageGeneration.translation.noImageToDownload
          : 'No image available to download';
        this.showError(message);
        return;
      }

      // Try to find generated image first, then sample image
      let $image = $(this.config.container).find('.image-placeholder .ai-generated-image');
      if ($image.length === 0) {
        $image = $(this.config.container).find('.image-placeholder .ai-sample-image');
      }
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
      const message = window.AIImageGeneration && window.AIImageGeneration.translation
        ? window.AIImageGeneration.translation.downloadStarted
        : 'Image download started';
      this.showLightboxMessage(message, 'success');
    },

    // Note: loadHistoryImage function removed as history items now use lightbox directly

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
          duration: 11000,
          progress: 75
        },
        {
          message: function() {
            return window.AIImageGeneration && window.AIImageGeneration.translation
              ? window.AIImageGeneration.translation.stage6
              : 'Finalizing the image...';
          },
          duration: 21000,
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
        const $emptyState = $container.find('.empty-state-content');

        // Hide existing image and empty state content, show loading overlay
        $image.hide();
        $emptyState.hide();
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
        const $emptyState = $container.find('.empty-state-content');

        // Clear all timers
        this.clearTimers();
        this.stopTimer();
        this.isActive = false;

        // Reset loading state to initial values
        this.resetLoadingState();

        // Hide loading overlay and loading info
        $overlay.hide();
        $loadingInfo.hide();

        // Show appropriate content based on whether we have a generated image
        if (NetiAIImageGeneration.hasGeneratedImage()) {
          $image.show();
        } else {
          $emptyState.show();
        }

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
        const initialMessage = window.AIImageGeneration && window.AIImageGeneration.translation
          ? window.AIImageGeneration.translation.submittingRequest
          : 'Submitting request...';
        $container.find('.loading-message').text(initialMessage);

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

    // Setup enhanced image lightbox with metadata and actions
    setupImageLightbox: function() {
      const self = this;

      // Get translations for lightbox
      const getTranslation = (key, fallback) => {
        return window.AIImageGeneration && window.AIImageGeneration.translation
          ? window.AIImageGeneration.translation[key] || fallback
          : fallback;
      };

      // Initialize enhanced Magnific Popup for AI image links
      $(document).magnificPopup({
        delegate: '.ai-image-link',
        type: 'image',
        image: {
          markup: `<div class="mfp-figure enhanced-lightbox">
            <div class="mfp-close"></div>

            <!-- Main content wrapper: Image + Info panel -->
            <div class="mfp-content-wrapper">
              <!-- Image display area -->
              <div class="mfp-img-holder">
                <div class="mfp-img"></div>
              </div>

              <!-- Right info panel (desktop) -->
              <div class="mfp-info-panel desktop-panel">
                <div class="panel-header">
                  <h3>${getTranslation('lightboxImageInfo', 'Image Information')}</h3>
                </div>
                <div class="panel-content">
                  <div class="meta-item">
                    <label>${getTranslation('lightboxPrompt', 'Prompt')}</label>
                    <div class="prompt-text"></div>
                  </div>
                  <div class="meta-item">
                    <label>${getTranslation('lightboxStyle', 'Image Style')}</label>
                    <div class="style-text"></div>
                  </div>
                  <div class="meta-item">
                    <label>${getTranslation('lightboxRatio', 'Image Aspect Ratio')}</label>
                    <div class="ratio-text"></div>
                  </div>
                </div>
                <div class="panel-actions">
                  <!-- Panel message area for desktop lightbox -->
                  <div class="floating-message panel-message" style="display: none;" role="alert" aria-live="polite">
                    <div class="floating-message-content">
                      <i class="floating-message-icon"></i>
                      <span class="floating-message-text"></span>
                    </div>
                  </div>

                  <button class="lightbox-btn regenerate-btn" title="${getTranslation('lightboxRegenerate', 'Regenerate Image')}">
                    <i class="zmdi zmdi-refresh"></i>
                    <span>${getTranslation('lightboxRegenerate', 'Regenerate Image')}</span>
                  </button>
                  <button class="lightbox-btn copy-btn" title="${getTranslation('lightboxCopy', 'Copy Image')}">
                    <i class="zmdi zmdi-collection-plus"></i>
                    <span>${getTranslation('lightboxCopy', 'Copy Image')}</span>
                  </button>
                  <button class="lightbox-btn download-btn" title="${getTranslation('lightboxDownload', 'Download Image')}">
                    <i class="zmdi zmdi-download"></i>
                    <span>${getTranslation('lightboxDownload', 'Download Image')}</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Floating info card (mobile) -->
            <div class="mfp-floating-info mobile-panel" style="display: none;">
              <div class="floating-toggle">
                <button class="info-toggle-btn">
                  <i class="zmdi zmdi-info"></i>
                </button>
              </div>
              <div class="floating-content" style="display: none;">
                <div class="floating-meta">
                  <div class="meta-item">
                    <label>${getTranslation('lightboxPrompt', 'Prompt')}</label>
                    <div class="prompt-text"></div>
                  </div>
                  <div class="meta-item">
                    <label>${getTranslation('lightboxStyle', 'Image Style')}</label>
                    <div class="style-text"></div>
                  </div>
                  <div class="meta-item">
                    <label>${getTranslation('lightboxRatio', 'Image Aspect Ratio')}</label>
                    <div class="ratio-text"></div>
                  </div>
                </div>
                <div class="floating-actions">
                  <button class="lightbox-btn regenerate-btn" title="${getTranslation('lightboxRegenerate', 'Regenerate Image')}">
                    <i class="zmdi zmdi-refresh"></i>
                  </button>
                  <button class="lightbox-btn copy-btn" title="${getTranslation('lightboxCopy', 'Copy Image')}">
                    <i class="zmdi zmdi-collection-plus"></i>
                  </button>
                  <button class="lightbox-btn download-btn" title="${getTranslation('lightboxDownload', 'Download Image')}">
                    <i class="zmdi zmdi-download"></i>
                  </button>
                </div>
              </div>
            </div>

            <div class="mfp-preloader"></div>
          </div>`,
          titleSrc: function() {
            return window.AIImageGeneration && window.AIImageGeneration.translation
              ? window.AIImageGeneration.translation.lightboxTitle
              : 'AI Generated Image';
          }
        },
        closeOnContentClick: false, // Disable to prevent conflict with info panel
        mainClass: 'mfp-neticrm-aigenimg-infobox enhanced-ai-lightbox',
        callbacks: {
          open: function() {
            self.initLightboxMetadata();
            self.bindLightboxEvents();
          },
          close: function() {
            self.unbindLightboxEvents();
          }
        }
      });
    },

    // Initialize lightbox metadata display
    initLightboxMetadata: function() {
      const $currentItem = $.magnificPopup.instance.currItem;
      if (!$currentItem || !$currentItem.el) return;

      const $trigger = $($currentItem.el);
      const prompt = $trigger.data('prompt') || '';
      const style = $trigger.data('style') || '';
      const ratio = $trigger.data('ratio') || '';

      // Update desktop panel metadata
      $('.mfp-info-panel .prompt-text').text(prompt);
      // Find translated style text for display
      const $styleOption = $(this.config.container).find(`${this.config.selectors.styleOptions}[data-style="${style}"]`);
      const styleDisplayText = $styleOption.length > 0 ? $styleOption.find('.style-label').text() || style : style;
      $('.mfp-info-panel .style-text').text(styleDisplayText);
      $('.mfp-info-panel .ratio-text').text(ratio);

      // Update mobile panel metadata
      $('.mfp-floating-info .prompt-text').text(prompt);
      $('.mfp-floating-info .style-text').text(styleDisplayText);
      $('.mfp-floating-info .ratio-text').text(ratio);

      // Show/hide panels based on screen size
      this.adjustLightboxLayout();

      console.log('Lightbox metadata initialized:', { prompt, style, ratio });
    },

    // Adjust lightbox layout based on screen size
    adjustLightboxLayout: function() {
      const isMobile = window.innerWidth < 768;

      if (isMobile) {
        $('.desktop-panel').hide();
        $('.mobile-panel').show();
      } else {
        $('.desktop-panel').show();
        $('.mobile-panel').hide();
      }
    },

    // Bind lightbox event handlers
    bindLightboxEvents: function() {
      const self = this;

      // Desktop panel actions
      $(document).on('click.lightbox', '.desktop-panel .lightbox-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        self.handleLightboxAction($(this));
      });

      // Mobile panel toggle
      $(document).on('click.lightbox', '.info-toggle-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const $content = $('.floating-content');
        $content.slideToggle(200);
      });

      // Mobile panel actions
      $(document).on('click.lightbox', '.mobile-panel .lightbox-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        self.handleLightboxAction($(this));
      });

      // Handle window resize
      $(window).on('resize.lightbox', function() {
        self.adjustLightboxLayout();
      });

      console.log('Lightbox events bound');
    },

    // Unbind lightbox event handlers
    unbindLightboxEvents: function() {
      $(document).off('.lightbox');
      $(window).off('resize.lightbox');

      // Clear any pending panel messages when closing lightbox
      this.panelMessage.hide();

      console.log('Lightbox events unbound');
    },

    // Handle lightbox action buttons
    handleLightboxAction: function($button) {
      const action = this.getLightboxActionType($button);

      console.log('Lightbox action triggered:', action);

      switch(action) {
        case 'regenerate':
          // Close lightbox and trigger regeneration
          $.magnificPopup.close();
          this.generateImage();
          break;
        case 'copy':
          this.copyImageFromLightbox();
          break;
        case 'download':
          this.downloadImageFromLightbox();
          break;
        default:
          console.warn('Unknown lightbox action:', action);
      }
    },

    // Show message in lightbox context with intelligent routing
    showLightboxMessage: function(message, type = 'success') {
      console.log('Lightbox message:', { message, type });

      // Check if lightbox is currently open
      const isLightboxOpen = $.magnificPopup.instance && $.magnificPopup.instance.isOpen;

      if (isLightboxOpen) {
        // Use dedicated panel message system for lightbox panel-actions
        if (type === 'success') {
          this.panelMessage.showSuccess(message);
        } else {
          this.panelMessage.showError(message);
        }
      } else {
        // Use regular floating message system if lightbox is not open
        // This maintains compatibility with float-actions buttons
        if (type === 'success') {
          this.floatingMessage.showSuccess(message);
        } else {
          this.floatingMessage.showError(message);
        }
      }
    },

    // Get lightbox action type from button
    getLightboxActionType: function($button) {
      if ($button.hasClass('regenerate-btn')) {
        return 'regenerate';
      } else if ($button.hasClass('copy-btn')) {
        return 'copy';
      } else if ($button.hasClass('download-btn')) {
        return 'download';
      }
      return 'unknown';
    },

    // Copy image from lightbox
    copyImageFromLightbox: function() {
      const $currentItem = $.magnificPopup.instance.currItem;
      if (!$currentItem) {
        const message = window.AIImageGeneration && window.AIImageGeneration.translation
          ? window.AIImageGeneration.translation.lightboxActionFailed
          : 'Action failed, please try again';
        this.showLightboxMessage(message, 'error');
        return;
      }

      const imageUrl = $currentItem.src;
      this.copyImageByUrl(imageUrl);
    },

    // Download image from lightbox
    downloadImageFromLightbox: function() {
      const $currentItem = $.magnificPopup.instance.currItem;
      if (!$currentItem) {
        const message = window.AIImageGeneration && window.AIImageGeneration.translation
          ? window.AIImageGeneration.translation.lightboxActionFailed
          : 'Action failed, please try again';
        this.showLightboxMessage(message, 'error');
        return;
      }

      const imageUrl = $currentItem.src;
      this.downloadImageByUrl(imageUrl);
    },

    // Copy image by URL
    copyImageByUrl: function(imageUrl) {
      if (!navigator.clipboard || !navigator.clipboard.write) {
        const message = window.AIImageGeneration && window.AIImageGeneration.translation
          ? window.AIImageGeneration.translation.browserNotSupported
          : 'Your browser does not support image copying feature';
        this.showError(message);
        return;
      }

      const self = this;
      const img = new Image();
      img.crossOrigin = 'anonymous';

      img.onload = function() {
        try {
          const canvas = document.createElement('canvas');
          const ctx = canvas.getContext('2d');

          canvas.width = img.naturalWidth;
          canvas.height = img.naturalHeight;
          ctx.drawImage(img, 0, 0);

          canvas.toBlob(function(blob) {
            if (!blob) {
              const message = window.AIImageGeneration && window.AIImageGeneration.translation
                ? window.AIImageGeneration.translation.imageProcessFailed
                : 'Image processing failed';
              self.showError(message);
              return;
            }

            const clipboardItem = new ClipboardItem({ [blob.type]: blob });
            navigator.clipboard.write([clipboardItem]).then(function() {
              const message = window.AIImageGeneration && window.AIImageGeneration.translation
                ? window.AIImageGeneration.translation.imageCopied
                : 'Image copied to clipboard';
              self.showLightboxMessage(message, 'success');
            }).catch(function(error) {
              console.error('Failed to copy image to clipboard:', error);
              const message = window.AIImageGeneration && window.AIImageGeneration.translation
                ? window.AIImageGeneration.translation.copyFailed
                : 'Failed to copy image, please try again';
              self.showLightboxMessage(message, 'error');
            });
          }, 'image/png');

        } catch (error) {
          console.error('Error processing image for clipboard:', error);
          const message = window.AIImageGeneration && window.AIImageGeneration.translation
            ? window.AIImageGeneration.translation.imageProcessError
            : 'Error occurred during image processing';
          self.showLightboxMessage(message, 'error');
        }
      };

      img.onerror = function() {
        console.error('Failed to load image for copying');
        const message = window.AIImageGeneration && window.AIImageGeneration.translation
          ? window.AIImageGeneration.translation.imageLoadFailed
          : 'Failed to load image, please try again';
        self.showLightboxMessage(message, 'error');
      };

      img.src = imageUrl;
    },

    // Download image by URL
    downloadImageByUrl: function(imageUrl) {
      const getFileExtension = (url) => {
        const match = url.match(/\.([a-zA-Z0-9]+)(?:\?|$)/);
        return match ? match[1] : 'webp';
      };

      const fileExtension = getFileExtension(imageUrl);
      const timestamp = Date.now();
      const fileName = `ai-generated-image-${timestamp}.${fileExtension}`;

      const link = document.createElement('a');
      link.href = imageUrl;
      link.download = fileName;
      link.click();

      console.log('Downloading as:', fileName);
      const message = window.AIImageGeneration && window.AIImageGeneration.translation
        ? window.AIImageGeneration.translation.downloadStarted
        : 'Image download started';
      this.showLightboxMessage(message, 'success');
    },

    // Show success message
    showSuccess: function(message) {
      console.log('Success:', message);

      // Show floating message notification
      this.floatingMessage.showSuccess(message);

      // Trigger custom success event
      $(this.config.container).trigger('aiImageSuccess', [message]);
    },

    // Show error message
    showError: function(message) {
      console.error('Error:', message);

      // Show floating message notification
      this.floatingMessage.showError(message);

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
      const $floatingMessage = $floatingActions.find(this.config.selectors.floatingMessage);

      switch(state) {
        case 'hidden':
          // Hide entire floating actions container (loading or no image)
          $floatingActions.hide();
          $floatingBtns.prop('disabled', true).addClass(this.config.classes.disabled);
          // Also hide any existing floating messages
          this.floatingMessage.hide();
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
      const $imageContainer = $(this.config.container).find('.image-placeholder');
      const $generatedImage = $imageContainer.find('.ai-generated-image');
      const $sampleImage = $imageContainer.find('.ai-sample-image');

      // Check for AI generated images first
      if ($generatedImage.length > 0) {
        const src = $generatedImage.attr('src');
        if (src && !this.isPlaceholderImage(src)) {
          return true;
        }
      }

      // Also check for sample images - they should enable floating actions
      if ($sampleImage.length > 0) {
        const src = $sampleImage.attr('src');
        if (src && !this.isPlaceholderImage(src)) {
          return true;
        }
      }

      return false;
    },

    // Helper method to check if an image URL is a placeholder
    isPlaceholderImage: function(src) {
      return src.includes('thumb-00.png') ||
             src.includes('placeholder') ||
             src.endsWith('thumb-00.png');
    },

    // Sample image loading functionality
    initSampleImageLoading: function() {
      const self = this;

      // Listen to panel activation events
      $(document).on('click', '.nme-setting-panels-tabs a', function() {
        const targetId = $(this).data('target-id');

        if (targetId === 'nme-aiimagegeneration') {
          console.log('AI Image Generation panel activated - checking for sample image load');

          // Wait for DOM to update after tab switch
          setTimeout(() => {
            // Debug context checking
            console.log('🔍 Checking trigger context:', self._aiLinkTriggerContext);
            console.log('🔍 Context exists:', !!self._aiLinkTriggerContext);
            if (self._aiLinkTriggerContext) {
              console.log('🔍 Context source:', self._aiLinkTriggerContext.source);
              console.log('🔍 Context valid:', self._isContextValid());
            }

            // Check if there's a trigger context
            if (self._aiLinkTriggerContext &&
                self._aiLinkTriggerContext.source === 'ai-link' &&
                self._isContextValid()) {

              // Execute context-specific loading
              console.log('✅ Loading sample image with context:', self._aiLinkTriggerContext);
              self._loadSampleImageWithContext();

            } else {
              // Execute default loading logic
              console.log('❌ No valid context found - using default loading logic');
              self.checkAndLoadSampleImage();
            }

            // Clear trigger context
            self._clearTriggerContext();
          }, 100);
        }
      });

      // Check initial state if panel is already active
      const checkInitialState = () => {
        const currentPanel = document.querySelector('.nme-aiimagegeneration.nme-setting-panel');
        if (currentPanel && currentPanel.classList.contains('is-active')) {
          console.log('AI panel is already active on load - checking for sample image');
          setTimeout(() => {
            self.checkAndLoadSampleImage();
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
    },

    // Show sample loading error state
    showSampleError: function() {
      const $container = $(this.config.container);
      const $emptyState = $container.find('.empty-state-content');
      const $sampleError = $container.find('.sample-error-state');
      const $aiImageLink = $container.find('.ai-image-link');

      // Always show error when get-sample request fails
      // Hide other states and show error
      $emptyState.hide();
      $aiImageLink.hide(); // Hide entire ai-image-link if exists
      $sampleError.fadeIn(300);

      // Hide floating actions when showing error state
      this.setFloatingActionsState('hidden');

      console.log('Sample loading error state shown');
    },

    // Hide sample loading error state
    hideSampleError: function() {
      const $container = $(this.config.container);
      const $sampleError = $container.find('.sample-error-state');
      const $aiImageLink = $container.find('.ai-image-link');
      const $emptyState = $container.find('.empty-state-content');
      
      $sampleError.fadeOut(300);
      
      // Determine what state to restore based on existing content
      if ($aiImageLink.length > 0 && $aiImageLink.find('img').attr('src')) {
        // Restore image state if there's a valid image
        $aiImageLink.show();
        this.setFloatingActionsState('visible');
        console.log('Restored to image state');
      } else {
        // Restore empty state if no valid image
        $emptyState.show();
        this.setFloatingActionsState('hidden');
        console.log('Restored to empty state');
      }
      
      console.log('Sample loading error state hidden');
    },

    // Reset to empty state (hide both error and loading states)
    resetToEmptyState: function() {
      const $container = $(this.config.container);
      const $emptyState = $container.find('.empty-state-content');
      const $sampleError = $container.find('.sample-error-state');
      const $aiImageLink = $container.find('.ai-image-link');

      // Hide error and image, show empty state
      $sampleError.hide();
      $aiImageLink.hide(); // Hide entire ai-image-link, not just img
      $emptyState.fadeIn(300);

      // Hide floating actions when in empty state
      this.setFloatingActionsState('hidden');

      console.log('Reset to empty state');
    },

    // Clear sample error state when user performs other actions
    clearSampleErrorIfNeeded: function() {
      const $container = $(this.config.container);
      const $sampleError = $container.find('.sample-error-state');
      
      // If sample error is currently showing, hide it
      if ($sampleError.is(':visible')) {
        console.log('Clearing sample error state due to user action');
        this.hideSampleError();
      }
    },

    // Check if sample image should be loaded and load it
    checkAndLoadSampleImage: function() {
      // Only load if no existing image is present
      if (!this.hasExistingImage()) {
        console.log('No existing image found - loading sample image');
        this.loadSampleImage();
      } else {
        console.log('Existing image found - skipping sample image load');
      }
    },

    // Check if there's already an image displayed
    hasExistingImage: function() {
      const $imageContainer = $(this.config.container).find('.image-placeholder');
      const $image = $imageContainer.find('img');
      const $emptyState = $imageContainer.find('.empty-state-content');

      // Check if we have a visible image that's not the default placeholder
      const hasVisibleImage = $image.length > 0 &&
                             $image.is(':visible') &&
                             $image.attr('src') &&
                             !$image.attr('src').includes('thumb-00.png');

      // Check if empty state is hidden (indicating an image is displayed)
      const emptyStateHidden = $emptyState.length > 0 && !$emptyState.is(':visible');

      return hasVisibleImage || emptyStateHidden;
    },

    // Check if there's a user-generated image that cannot be overwritten
    hasUserGeneratedImage: function() {
      const $imageContainer = $(this.config.container).find('.image-placeholder');
      const $image = $imageContainer.find('img');
      const $emptyState = $imageContainer.find('.empty-state-content');

      // Check if we have a visible image that's not the default placeholder
      const hasVisibleImage = $image.length > 0 &&
                             $image.is(':visible') &&
                             $image.attr('src') &&
                             !$image.attr('src').includes('thumb-00.png');

      // Check if empty state is hidden (indicating an image is displayed)
      const emptyStateHidden = $emptyState.length > 0 && !$emptyState.is(':visible');

      if (!hasVisibleImage && !emptyStateHidden) {
        console.log('🔍 hasUserGeneratedImage: No image found');
        return false; // No image at all
      }

      // If there's an image, check if it's a user-generated image (not a sample)
      // User-generated images have 'ai-generated-image' class but NOT 'ai-sample-image' class
      const hasGeneratedClass = $image.hasClass('ai-generated-image');
      const hasSampleClass = $image.hasClass('ai-sample-image');

      console.log('🔍 hasUserGeneratedImage: Image classes check', {
        hasGeneratedClass: hasGeneratedClass,
        hasSampleClass: hasSampleClass,
        imageClasses: $image.attr('class'),
        src: $image.attr('src')
      });

      // It's a user-generated image if it has ai-generated-image but not ai-sample-image
      const isUserGenerated = hasGeneratedClass && !hasSampleClass;
      console.log('🔍 hasUserGeneratedImage result:', isUserGenerated);

      return isUserGenerated;
    },

    // Get corrected image URL using CiviCRM resource base path
    getCorrectedImageUrl: function(imageUrl, imagePath) {
      // Try to use CiviCRM resource base path first
      if (typeof Drupal !== 'undefined' &&
          Drupal.settings &&
          Drupal.settings.civicrm &&
          Drupal.settings.civicrm.resourceBase) {

        const resourceBase = Drupal.settings.civicrm.resourceBase;
        const correctedUrl = resourceBase + imagePath;
        console.log('Using Drupal.settings.civicrm.resourceBase:', resourceBase);
        console.log('Corrected URL:', correctedUrl);
        return correctedUrl;
      }

      // Fallback: manual path correction
      if (imageUrl && imageUrl.includes('/packages/AIImageGeneration/')) {
        // Insert the missing path part
        const correctedUrl = imageUrl.replace(
          '/packages/AIImageGeneration/',
          '/sites/all/modules/civicrm/packages/AIImageGeneration/'
        );
        console.log('Manual path correction applied:', correctedUrl);
        return correctedUrl;
      }

      // If we have image_path, try to construct URL from current domain
      if (imagePath) {
        const baseUrl = window.location.origin;
        const correctedUrl = baseUrl + '/sites/all/modules/civicrm/' + imagePath;
        console.log('Constructed URL from image_path:', correctedUrl);
        return correctedUrl;
      }

      // Last resort: return original image_url
      console.warn('Could not correct image URL, using original:', imageUrl);
      return imageUrl;
    },

    // Get current UI locale
    getUILocale: function() {
      // Try to get locale from various sources
      let locale = $('html').attr('lang') ||
                   window.navigator.language ||
                   window.navigator.userLanguage ||
                   'en';

      // Convert locale format to what API expects
      // Common conversions for CiviCRM/Drupal locales
      const localeMap = {
        'zh-hant': 'zh_TW',
        'zh-hans': 'zh_CN',
        'zh-tw': 'zh_TW',
        'zh-cn': 'zh_CN',
        'en-us': 'en_US',
        'en-gb': 'en_GB'
      };

      const normalizedLocale = locale.toLowerCase();

      // Check if we have a direct mapping
      if (localeMap[normalizedLocale]) {
        console.log('Locale converted from', locale, 'to', localeMap[normalizedLocale]);
        return localeMap[normalizedLocale];
      }

      // Convert dash format to underscore format (zh-hant -> zh_TW)
      if (locale.includes('-')) {
        const parts = locale.split('-');
        const converted = parts[0] + '_' + parts[1].toUpperCase();
        console.log('Locale converted from', locale, 'to', converted);
        return converted;
      }

      // Default fallback
      console.log('Using locale as-is:', locale);
      return locale;
    },

    // Handle sample retry button click
    handleSampleRetry: function() {
      console.log('Sample retry button clicked');
      
      // Hide error state and show loading
      this.hideSampleError();
      
      // Retry loading sample image
      this.loadSampleImage();
    },

    // Get current selected ratio from UI
    getCurrentRatio: function() {
      const ratioText = $(this.config.container).find(this.config.selectors.ratioText).text();
      const defaultRatio = '4:3';

      // Return current ratio or default if empty
      return ratioText && ratioText.trim() !== '' ? ratioText.trim() : defaultRatio;
    },

    // Load sample image from API
    loadSampleImage: function() {
      const self = this;
      const locale = this.getUILocale();
      const ratio = this.getCurrentRatio();

      console.log('Loading sample image for locale:', locale, 'ratio:', ratio);

      // Show loading state
      this.showSampleImageLoading();

      $.ajax({
        url: '/civicrm/ai/images/get-sample',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
          locale: locale,
          ratio: ratio
        }),
        timeout: 10000,

        success: function(response) {
          // Hide loading state
          self.hideSampleImageLoading();

          if (response.status === 1 && response.data) {
            console.log('Sample image loaded successfully');
            self.applySampleToInterface(response.data);
          } else {
            console.warn('Sample image API returned unexpected format:', response);
          }
        },

        error: function(xhr, status, error) {
          // Hide loading state on error
          self.hideSampleImageLoading();

          console.warn('Failed to load sample image:', {
            status: status,
            error: error,
            responseText: xhr.responseText,
            httpStatus: xhr.status
          });
          
          // Show sample error state instead of silently failing
          self.showSampleError();
        }
      });
    },

    // Apply sample data to interface
    applySampleToInterface: function(sampleData) {
      try {
        // Clear any existing sample error state since we got valid data
        this.clearSampleErrorIfNeeded();
        
        // Update image if provided
        if (sampleData.image_url || sampleData.image_path) {
          const correctedImageUrl = this.getCorrectedImageUrl(sampleData.image_url, sampleData.image_path);
          this.updateSampleImage(correctedImageUrl, sampleData.filename, sampleData);
        }

        // Update prompt text if provided
        if (sampleData.text) {
          this.updatePromptText(sampleData.text);
        }

        // Update style selector if provided
        if (sampleData.style) {
          this.updateStyleSelector(sampleData.style);
        }

        // Update ratio selector if provided
        if (sampleData.ratio) {
          this.updateRatioSelector(sampleData.ratio);
        }

        console.log('Sample data applied to interface successfully');
      } catch (error) {
        console.error('Error applying sample data to interface:', error);
      }
    },

    // Show loading state for sample image
    showSampleImageLoading: function() {
      const $container = $(this.config.container);
      const $imageContainer = $container.find('.image-placeholder');
      const $loadingOverlay = $imageContainer.find('.loading-overlay');
      const $loadingMessage = $loadingOverlay.find('.loading-message');
      const $emptyState = $imageContainer.find('.empty-state-content');

      // Hide empty state if visible
      $emptyState.hide();

      // Set loading message for sample image
      const loadingText = window.AIImageGeneration && window.AIImageGeneration.translation
        ? window.AIImageGeneration.translation.loadingSampleImage
        : 'Loading sample image...';
      $loadingMessage.text(loadingText);

      // Show loading overlay with fade in
      $loadingOverlay.addClass('sample-loading').fadeIn(300);

      console.log('Sample image loading state shown');
    },

    // Hide loading state for sample image
    hideSampleImageLoading: function() {
      const $container = $(this.config.container);
      const $imageContainer = $container.find('.image-placeholder');
      const $loadingOverlay = $imageContainer.find('.loading-overlay');

      // Hide loading overlay with fade out
      $loadingOverlay.removeClass('sample-loading').fadeOut(300, function() {
        // Reset loading message
        $(this).find('.loading-message').text('');
      });

      console.log('Sample image loading state hidden');
    },

    // Update sample image display
    updateSampleImage: function(imageUrl, filename, sampleData) {
      const $imageContainer = $(this.config.container).find('.image-placeholder');
      const $existingImage = $imageContainer.find('img');
      const $emptyState = $imageContainer.find('.empty-state-content');

      if ($existingImage.length > 0) {
        // Hide empty state
        $emptyState.hide();

        // Create new image element with correct classes
        const $img = $('<img>').attr({
          'src': imageUrl,
          'alt': 'AI Generated Image'
        }).addClass('ai-generated-image ai-sample-image');

        // Get metadata from sample data or fallback to form data
        const promptText = (sampleData && sampleData.text) || $(this.config.container).find(this.config.selectors.promptTextarea).val() || '';
        const styleText = (sampleData && sampleData.style) || $(this.config.container).find(this.config.selectors.styleText).text() || '';
        const ratioText = (sampleData && sampleData.ratio) || $(this.config.container).find(this.config.selectors.ratioText).text() || '';

        console.log('Sample image metadata:', {
          promptText: promptText,
          styleText: styleText,
          ratioText: ratioText,
          sampleData: sampleData
        });

        // Create anchor tag to wrap the image for lightbox functionality with metadata
        const $link = $('<a>').attr({
          'href': imageUrl,
          'class': 'ai-image-link',
          'data-prompt': promptText,
          'data-style': styleText,
          'data-ratio': ratioText
        }).append($img);

        // Remove old image and its link wrapper if exists
        const $existingLink = $imageContainer.find('.ai-image-link');
        if ($existingLink.length > 0) {
          $existingLink.remove();
        } else {
          $existingImage.remove();
        }

        // Insert new link before loading-overlay to maintain structure
        const $overlay = $imageContainer.find('.loading-overlay');
        if ($overlay.length > 0) {
          $overlay.before($link);
        } else {
          $imageContainer.prepend($link);
        }

        // Update floating actions state after sample image is loaded
        setTimeout(() => {
          this.updateFloatingActionsBasedOnImage();
        }, 50);

        console.log('Sample image updated successfully:', imageUrl);
      } else {
        console.error('No image element found in container');
      }
    },

    // Update prompt text with auto-resize
    updatePromptText: function(text) {
      const $textarea = $(this.config.container).find(this.config.selectors.promptTextarea);

      if ($textarea.length > 0) {
        $textarea.val(text);

        // Update tooltip based on content
        this.updatePromptTooltip($textarea);

        // Trigger auto-resize
        setTimeout(() => {
          this.autoResizeTextarea($textarea);
        }, 10);

      }
    },

    // Update tooltip based on textarea content
    updatePromptTooltip: function($textarea) {
      const $promptContainer = $textarea.closest('.prompt-container');
      const textContent = $textarea.val().trim();

      if (textContent.length > 0) {
        // Has content - show tooltip
        $promptContainer.addClass('with-sample-prompt');
        this.addPromptTooltip($promptContainer);
      } else {
        // No content - hide tooltip
        $promptContainer.removeClass('with-sample-prompt');
        this.removePromptTooltip($promptContainer);
      }
    },

    // Add HTML tooltip element for prompt
    addPromptTooltip: function($promptContainer) {
      // Remove existing tooltip if any
      $promptContainer.find('.sample-prompt-tooltip').remove();

      // Get translated text
      const tooltipText = window.AIImageGeneration && window.AIImageGeneration.translation && window.AIImageGeneration.translation.editPromptTooltip
        ? window.AIImageGeneration.translation.editPromptTooltip
        : 'Edit prompt: Describe the image you want to generate';

      // Create tooltip element
      const $tooltip = $('<div class="sample-prompt-tooltip">' + tooltipText + '</div>');

      // Add to container
      $promptContainer.append($tooltip);
    },

    // Remove prompt tooltip
    removePromptTooltip: function($promptContainer) {
      // Remove HTML tooltip element
      $promptContainer.find('.sample-prompt-tooltip').remove();
    },

    // Initialize tooltip based on existing content
    initPromptTooltip: function() {
      const $textarea = $(this.config.container).find(this.config.selectors.promptTextarea);
      if ($textarea.length > 0) {
        this.updatePromptTooltip($textarea);
      }
    },

    // Initialize file upload field integration
    initFileUploadIntegration: function() {
      const self = this;

      // Configuration for file upload fields and their ratios
      const uploadFieldConfigs = {
        'uploadBackgroundImage': '4:3',
        'uploadMobileBackgroundImage': '9:16'
      };

      // Check each upload field
      Object.keys(uploadFieldConfigs).forEach(fieldName => {
        const $uploadField = $('.crm-container input[type="file"][name="' + fieldName + '"]');

        if ($uploadField.length > 0) {
          const ratio = uploadFieldConfigs[fieldName];
          self.addAIGenerateLink($uploadField, ratio);
          console.log('Added AI generate link for field:', fieldName, 'with ratio:', ratio);
        }
      });
    },

    // Add AI generate link after file upload field
    addAIGenerateLink: function($uploadField, ratio) {
      // Check if link already exists to avoid duplicates
      if ($uploadField.next('.generate-ai-sample-image').length > 0) {
        return;
      }

      // Get translated text
      const linkText = window.AIImageGeneration && window.AIImageGeneration.translation && window.AIImageGeneration.translation.generateImagesUsingAI
        ? window.AIImageGeneration.translation.generateImagesUsingAI
        : 'Generate images using AI';

      // Create AI generate link
      const $aiLink = $('<a href="#" class="generate-ai-sample-image" data-ratio="' + ratio + '">' + linkText + '</a>');

      // Add click event handler
      $aiLink.on('click', function(e) {
        e.preventDefault();
        const selectedRatio = $(this).data('ratio');
        console.log('AI generate link clicked with ratio:', selectedRatio);

        // Check if nsp-container exists and get its current state
        const $nspContainer = $('.nsp-container');
        if ($nspContainer.length === 0) {
          console.warn('nsp-container not found');
          return;
        }

        const isContainerOpen = $nspContainer.hasClass('is-opened');
        console.log('nsp-container current state - is opened:', isContainerOpen);

        // Function to switch to AI Image Generation panel
        const switchToAIPanel = function() {
          // Set trigger context before switching panel
          NetiAIImageGeneration._aiLinkTriggerContext = {
            source: 'ai-link',
            ratio: selectedRatio,
            forceLoad: false,
            forceLoadSample: true,  // Use forceLoadSample instead of forceLoad
            timestamp: Date.now()
          };
          console.log('Set trigger context:', NetiAIImageGeneration._aiLinkTriggerContext);

          const $aiTabLink = $('.nme-setting-panels-tabs a[data-target-id="nme-aiimagegeneration"]');
          if ($aiTabLink.length > 0) {
            console.log('Switching to AI Image Generation panel');
            $aiTabLink.trigger('click');

            // Update ratio after panel switch
            setTimeout(() => {
              if (window.NetiAIImageGeneration && window.NetiAIImageGeneration.setRatio) {
                window.NetiAIImageGeneration.setRatio(selectedRatio);
                console.log('Set ratio to:', selectedRatio);
              }
            }, 200);
          } else {
            console.warn('AI Image Generation tab link not found');
          }
        };

        if (!isContainerOpen) {
          // Container is closed - first switch panel, then open container
          console.log('Container is closed - switching panel first, then opening container');
          switchToAIPanel();

          // Wait for panel switch to complete, then open container
          setTimeout(() => {
            const $trigger = $('.nsp-trigger');
            if ($trigger.length > 0) {
              console.log('Opening nsp-container');
              $trigger.trigger('click');
            } else {
              console.warn('nsp-trigger not found');
            }
          }, 300);
        } else {
          // Container is already open - just switch panel
          console.log('Container is already open - switching to AI panel directly');
          switchToAIPanel();
        }
      });

      // Insert after the upload field
      $uploadField.after($aiLink);
    },

    // Update style selector
    updateStyleSelector: function(style) {
      const $styleOptions = $(this.config.container).find(this.config.selectors.styleOptions);
      const $styleText = $(this.config.container).find(this.config.selectors.styleText);

      // Remove current selection
      $styleOptions.removeClass(this.config.classes.selected);

      // Find and select target option
      const $targetOption = $styleOptions.filter(`[data-style="${style}"]`);
      if ($targetOption.length > 0) {
        $targetOption.addClass(this.config.classes.selected);
        // Use translated text from style-label or fallback to data-style
        const styleLabel = $targetOption.find('.style-label').text() || style;
        $styleText.text(styleLabel);
      }
    },

    // Update ratio selector
    updateRatioSelector: function(ratio) {
      const $ratioItems = $(this.config.container).find(`${this.config.selectors.ratioDropdown} ${this.config.selectors.dropdownItems}`);
      const $ratioText = $(this.config.container).find(this.config.selectors.ratioText);

      // Remove current selection
      $ratioItems.removeClass(this.config.classes.selected);

      // Find and select target item
      const $targetItem = $ratioItems.filter(`[data-ratio="${ratio}"]`);
      if ($targetItem.length > 0) {
        $targetItem.addClass(this.config.classes.selected);
        $ratioText.text(ratio);
      }
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

    // Context validation method
    _isContextValid: function() {
      if (!this._aiLinkTriggerContext) return false;

      // Check timestamp to avoid expired context (5 seconds timeout)
      const now = Date.now();
      const timeDiff = now - this._aiLinkTriggerContext.timestamp;

      return timeDiff < 5000; // Valid within 5 seconds
    },

    // Context-based loading method
    _loadSampleImageWithContext: function() {
      const context = this._aiLinkTriggerContext;
      const locale = this.getUILocale();

      console.log('Loading sample image with context:', context);

      // Use ratio information from context to load sample image
      // Pass both forceLoad and forceLoadSample for compatibility
      this._loadSampleImageWithRatio(
        locale,
        context.ratio,
        context.forceLoad || false,
        context.forceLoadSample || false
      );
    },

    // Clear trigger context
    _clearTriggerContext: function() {
      this._aiLinkTriggerContext = null;
      console.log('Trigger context cleared');
    },

    // Show replace confirmation dialog
    showReplaceConfirmDialog: function(locale, ratio) {
      const $dialog = $('.netiaiig-confirm-dialog');
      
      if ($dialog.length === 0) {
        console.warn('Confirm dialog element not found');
        return;
      }

      // Store dialog context for later use
      this._dialogContext = {
        locale: locale,
        ratio: ratio
      };

      // Update ratio placeholder in dialog text
      const ratioText = this.getTranslation('confirmDialogMainText').replace('{ratio}', ratio);
      $dialog.find('.dialog-main-text').html(ratioText);
      $dialog.find('.dialog-ratio-placeholder').text(ratio);

      // Show dialog with fade in effect
      $dialog.fadeIn(300);
      
      // Focus on cancel button by default to prevent accidental confirmation
      setTimeout(() => {
        $dialog.find('.dialog-cancel').focus();
      }, 350);

      // Prevent body scroll when dialog is open
      $('body').addClass('dialog-open');

      console.log('Replace confirmation dialog shown with ratio:', ratio);
    },

    // Hide replace confirmation dialog
    hideReplaceConfirmDialog: function() {
      const $dialog = $('.netiaiig-confirm-dialog');
      
      // Hide dialog with fade out effect
      $dialog.fadeOut(300);
      
      // Clear dialog context
      this._dialogContext = null;
      
      // Restore body scroll
      $('body').removeClass('dialog-open');

      console.log('Replace confirmation dialog hidden');
    },

    // Get translation text with fallback
    getTranslation: function(key) {
      if (window.AIImageGeneration && 
          window.AIImageGeneration.translation && 
          window.AIImageGeneration.translation[key]) {
        return window.AIImageGeneration.translation[key];
      }
      
      // Fallback translations
      const fallbacks = {
        'confirmDialogMainText': 'You clicked "Generate images using AI". The system will load a sample image suitable for this field (ratio: {ratio}), but there is an AI image you personally created in the current generation area.',
        'confirmDialogQuestion': 'Do you want to replace the current image with the sample image?',
        'confirmDialogReminder': 'All images you generate are saved in "Generation History" and can be retrieved at any time even if replaced.'
      };
      
      return fallbacks[key] || key;
    },

    // Handle confirm replace action
    handleConfirmReplace: function() {
      console.log('User confirmed replacement');
      
      // Get stored dialog context
      if (!this._dialogContext) {
        console.warn('No dialog context found for replacement');
        this.hideReplaceConfirmDialog();
        return;
      }

      const context = this._dialogContext;
      
      // Hide dialog first
      this.hideReplaceConfirmDialog();
      
      // Proceed with loading sample image
      console.log('Proceeding with sample image loading:', context);
      this._loadSampleImageWithRatio(
        context.locale, 
        context.ratio, 
        true, // forceLoad = true to overwrite user image
        false
      );
    },

    // Handle cancel replace action
    handleCancelReplace: function() {
      console.log('User cancelled replacement');
      
      // Simply hide dialog, no further action needed
      this.hideReplaceConfirmDialog();
    },

    // Handle dialog close action (X button or ESC key)
    handleDialogClose: function() {
      console.log('Dialog closed');
      
      // Same as cancel action
      this.handleCancelReplace();
    },

    // Extended loadSampleImage method with ratio support
    _loadSampleImageWithRatio: function(locale, ratio, forceLoad = false, forceLoadSample = false) {
      const self = this;

      // Determine loading conditions based on force options
      if (forceLoad) {
        // forceLoad: Can overwrite any image (existing behavior)
        console.log('Force loading sample image (can overwrite any image)');
      } else if (forceLoadSample) {
        // forceLoadSample: Can only overwrite sample images, not user-generated images
        if (this.hasUserGeneratedImage()) {
          console.log('User-generated image found - showing confirmation dialog');
          this.showReplaceConfirmDialog(locale, ratio);
          return;
        }
        console.log('Force loading sample image (can overwrite sample images only)');
      } else {
        // Default: Only load if no existing image
        if (this.hasExistingImage()) {
          console.log('Existing image found and no force option - skipping');
          return;
        }
      }

      console.log('Loading sample image with ratio:', ratio);

      // Show loading state
      this.showSampleImageLoading();

      $.ajax({
        url: '/civicrm/ai/images/get-sample',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
          locale: locale,
          ratio: ratio  // Add ratio parameter
        }),
        timeout: 20000,

        success: function(response) {
          // Hide loading state
          self.hideSampleImageLoading();

          if (response.status === 1 && response.data) {
            console.log('Sample image loaded successfully with ratio:', ratio);
            self.applySampleToInterface(response.data);
          }
        },

        error: function(xhr, status, error) {
          // Hide loading state on error
          self.hideSampleImageLoading();

          console.warn('Failed to load sample image with ratio:', ratio, error);
          
          // Show sample error state instead of silently failing
          self.showSampleError();
        }
      });
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
        // Get translation helper function
        const getTranslation = (key) => {
          return window.AIImageGeneration && window.AIImageGeneration.translation
            ? window.AIImageGeneration.translation[key]
            : null;
        };

        // JSON response error mappings
        const jsonErrorMappings = {
          'The request is not a valid JSON format.': getTranslation('errorInvalidJson') || 'Request format error, please refresh the page and try again',
          'The request does not match the expected format.': getTranslation('errorInvalidFormat') || 'Request parameter error, please check input content',
          'Content exceeds the maximum character limit.': getTranslation('errorContentTooLong') || 'Description text is too long, please shorten to within 1000 characters',
          'No corresponding component was found.': getTranslation('errorNoComponent') || 'Page permission error, please refresh the page',
          'Invalid request method or missing data.': getTranslation('errorInvalidMethod') || 'System error, please refresh the page and try again'
        };

        // HTTP status code mappings
        const httpStatusMappings = {
          400: getTranslation('errorBadRequest') || 'Request parameter error, please check input content',
          401: getTranslation('errorUnauthorized') || 'Login expired, please login again',
          403: getTranslation('errorForbidden') || 'Insufficient permissions, please contact administrator',
          404: getTranslation('errorNotFound') || 'Service temporarily unavailable, please try again later',
          408: getTranslation('errorTimeout') || 'Request timeout, please check network connection',
          429: getTranslation('errorTooManyRequests') || 'Usage frequency too high, please try again later',
          500: getTranslation('errorServerError') || 'Server temporarily error, please try again later',
          502: getTranslation('errorBadGateway') || 'Service temporarily unavailable, please try again later',
          503: getTranslation('errorServiceUnavailable') || 'Service temporarily under maintenance, please try again later',
          504: getTranslation('errorGatewayTimeout') || 'Connection timeout, please check network connection'
        };

        // Network connection error mappings
        const networkErrorMappings = {
          'network error': getTranslation('errorNetworkError') || 'Network connection interrupted, please check network status',
          'timeout': getTranslation('errorConnectionTimeout') || 'Connection timeout, please refresh the page and try again',
          'connection refused': getTranslation('errorConnectionRefused') || 'Unable to connect to server, please try again later',
          'dns error': getTranslation('errorDnsError') || 'Network configuration problem, please check network connection'
        };

        // 1. Priority check JSON response error messages
        if (jsonErrorMappings[technicalMessage]) {
          return jsonErrorMappings[technicalMessage];
        }

        // 2. Check for Image generation failed type
        if (technicalMessage.includes('Image generation failed')) {
          return getTranslation('errorGenerationFailed') || 'Image generation failed, please try again later';
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
        return getTranslation('errorDefaultMessage') || 'An error occurred during image generation, please try again later';
      }
    },

    // Floating message manager for action button notifications
    floatingMessage: {
      hideTimer: null,

      // Show floating message with auto-hide
      show: function(message, type = 'success') {
        const $container = $(NetiAIImageGeneration.config.container);
        const $floatingMessage = $container.find(NetiAIImageGeneration.config.selectors.floatingMessage);
        const $messageIcon = $container.find(NetiAIImageGeneration.config.selectors.floatingMessageIcon);
        const $messageText = $container.find(NetiAIImageGeneration.config.selectors.floatingMessageText);

        if ($floatingMessage.length === 0) {
          console.warn('Floating message element not found');
          return;
        }

        // Clear any existing timer
        this.clearTimer();

        // Set message content
        $messageText.text(message);

        // Set icon based on type
        if (type === 'success') {
          $messageIcon.removeClass().addClass('floating-message-icon zmdi zmdi-check-circle');
          $floatingMessage.removeClass('error').addClass('success');
        } else if (type === 'error') {
          $messageIcon.removeClass().addClass('floating-message-icon zmdi zmdi-close-circle');
          $floatingMessage.removeClass('success').addClass('error');
        }

        // Show message with animation
        $floatingMessage.stop(true, true).fadeIn(200);

        // Auto-hide after 5 seconds
        this.hideTimer = setTimeout(() => {
          this.hide();
        }, 5000);

        console.log('Floating message shown:', { message, type });
      },

      // Hide floating message
      hide: function() {
        const $container = $(NetiAIImageGeneration.config.container);
        const $floatingMessage = $container.find(NetiAIImageGeneration.config.selectors.floatingMessage);

        this.clearTimer();
        $floatingMessage.stop(true, true).fadeOut(200);

        console.log('Floating message hidden');
      },

      // Clear hide timer
      clearTimer: function() {
        if (this.hideTimer) {
          clearTimeout(this.hideTimer);
          this.hideTimer = null;
        }
      },

      // Show success message
      showSuccess: function(message) {
        this.show(message, 'success');
      },

      // Show error message
      showError: function(message) {
        this.show(message, 'error');
      }
    },

    // Panel message manager for lightbox panel-actions notifications
    panelMessage: {
      hideTimer: null,

      // Show panel message near action buttons using same styling as floating messages
      show: function(message, type = 'success') {
        // Find panel message element in lightbox (uses same classes as floating-message)
        const $panelMessage = $('.panel-message');

        if ($panelMessage.length === 0) {
          console.warn('Panel message element not found');
          return;
        }

        // Clear any existing timer
        this.clearTimer();

        // Update panel message content using same selectors as floating message
        const $messageIcon = $panelMessage.find('.floating-message-icon');
        const $messageText = $panelMessage.find('.floating-message-text');

        // Set message content
        $messageText.text(message);

        // Set icon and styling based on type (same as floatingMessage)
        if (type === 'success') {
          $messageIcon.removeClass().addClass('floating-message-icon zmdi zmdi-check-circle');
          $panelMessage.removeClass('error').addClass('success');
        } else if (type === 'error') {
          $messageIcon.removeClass().addClass('floating-message-icon zmdi zmdi-close-circle');
          $panelMessage.removeClass('success').addClass('error');
        }

        // Show message with slideDown animation
        $panelMessage.stop(true, true).slideDown(300);

        // Auto-hide after 3.5 seconds
        this.hideTimer = setTimeout(() => {
          this.hide();
        }, 3500);

        console.log('Panel message shown:', { message, type });
      },

      // Hide panel message
      hide: function() {
        const $panelMessage = $('.panel-message');

        this.clearTimer();

        // Hide with slideUp animation
        $panelMessage.stop(true, true).slideUp(300);

        console.log('Panel message hidden');
      },

      // Clear hide timer
      clearTimer: function() {
        if (this.hideTimer) {
          clearTimeout(this.hideTimer);
          this.hideTimer = null;
        }
      },

      // Show success message
      showSuccess: function(message) {
        this.show(message, 'success');
      },

      // Show error message
      showError: function(message) {
        this.show(message, 'error');
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