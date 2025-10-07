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
      $(document).on('click', self.config.selectors.generateBtn, function() {
        self.generateImage();
      });

      // Floating action buttons
      $(document).on('click', self.config.selectors.floatingBtn, function(e) {
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

      // Textarea auto-resize
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

      // Set loading state
      $btn.prop('disabled', true)
          .addClass(this.config.classes.loading)
          .text('正在生成圖片...');

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
          if (response.status === 1 && response.data) {
            self.onGenerationComplete(response.data.image_url, response.data);
            self.showSuccess('圖片生成成功！');
          } else {
            self.onGenerationComplete();
            self.showError(response.message || '圖片生成失敗');
          }
        },

        error: function(xhr, status, error) {
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
        
        // Show loading placeholder first
        $imageContainer.html('<div class="image-loading">載入圖片中...</div>');
        
        // Function to handle successful image display
        const showImage = function($img) {
          console.log('Showing image in container');
          $imageContainer.empty().append($img);
        };
        
        // Create new image element
        const $img = $('<img>').attr({
          'alt': 'AI 生成圖片',
          'loading': 'lazy',
          'src': imageUrl
        });
        
        // Check if image is already complete (cached)
        if ($img[0].complete) {
          console.log('Image is already complete');
          if ($img[0].naturalWidth > 0) {
            console.log('Image loaded from cache, displaying immediately');
            showImage($img);
          } else {
            console.error('Image failed to load:', imageUrl);
            $imageContainer.html('<div class="image-error">圖片載入失敗</div>');
          }
        } else {
          // Bind load/error events for non-cached images
          $img.on('load', function() {
            console.log('Image loaded successfully via event');
            showImage($img);
          }).on('error', function() {
            console.error('Image failed to load via event:', imageUrl);
            $imageContainer.html('<div class="image-error">圖片載入失敗</div>');
          });
          
          console.log('Waiting for image to load...');
        }
        
      } else {
        $imageContainer.html('<div class="image-placeholder-text">尚未生成圖片</div>');
      }
    },

    // Insert image to editor
    insertToEditor: function() {
      const $image = $(this.config.container).find('.image-placeholder img');

      if ($image.length > 0) {
        const imageUrl = $image.attr('src');
        console.log('Inserting image to editor:', imageUrl);

        // Trigger custom event for parent component to handle
        $(this.config.container).trigger('insertToEditor', [imageUrl]);

        this.showSuccess('Image inserted successfully');
      } else {
        this.showError('No image to insert');
      }
    },

    // Download generated image
    downloadImage: function() {
      const $image = $(this.config.container).find('.image-placeholder img');

      if ($image.length > 0) {
        const imageUrl = $image.attr('src');
        console.log('Downloading image:', imageUrl);

        // Create download link
        const link = document.createElement('a');
        link.href = imageUrl;
        link.download = `ai-generated-image-${Date.now()}.png`;
        link.click();

        this.showSuccess('Image download started');
      } else {
        this.showError('No image to download');
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

    // Auto-resize textarea
    autoResizeTextarea: function($textarea) {
      const element = $textarea[0];
      element.style.height = 'auto';
      element.style.height = element.scrollHeight + 'px';
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
      // Set prompt text
      setPrompt: function(text) {
        $(NetiAIImageGeneration.config.container)
          .find(NetiAIImageGeneration.config.selectors.promptTextarea)
          .val(text);
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

  // Expose API to global scope
  window.NetiAIImageGeneration = NetiAIImageGeneration.api;

})(jQuery);