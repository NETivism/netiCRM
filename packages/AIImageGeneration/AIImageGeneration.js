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

      // Enhanced textarea auto-resize with multiple event support
      $(document).on('input paste keydown', self.config.selectors.promptTextarea, function(e) {
        const $textarea = $(this);
        
        // Use requestAnimationFrame for smooth resizing
        requestAnimationFrame(function() {
          self.autoResizeTextarea($textarea);
        });
        
        // Additional handling for paste events
        if (e.type === 'paste') {
          setTimeout(function() {
            self.autoResizeTextarea($textarea);
          }, 10);
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
        
        // Create image element without lazy loading
        const img = new Image();
        const $img = $(img);
        
        // Set up load handler before setting src
        img.onload = function() {
          console.log('Image loaded successfully - displaying now');
          $img.attr('alt', 'AI 生成圖片');
          $imageContainer.empty().append($img);
        };
        
        // Set up error handler
        img.onerror = function() {
          console.error('Image failed to load:', imageUrl);
          $imageContainer.html('<div class="image-error">圖片載入失敗</div>');
        };
        
        // Add timeout protection (10 seconds)
        setTimeout(function() {
          if ($imageContainer.find('.image-loading').length > 0) {
            console.warn('Image loading timeout, checking status...');
            if (img.complete) {
              if (img.naturalWidth > 0) {
                console.log('Image actually loaded but event didn\'t fire - force display');
                $img.attr('alt', 'AI 生成圖片');
                $imageContainer.empty().append($img);
              } else {
                console.error('Image loading timed out');
                $imageContainer.html('<div class="image-error">圖片載入超時</div>');
              }
            } else {
              console.error('Image still loading after timeout');
              $imageContainer.html('<div class="image-error">圖片載入超時</div>');
            }
          }
        }, 10000);
        
        // Start loading - this should trigger onload when ready
        console.log('Starting image load...');
        img.src = imageUrl;
        
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

    // Auto-resize textarea using enhanced implementation
    autoResizeTextarea: function($textarea) {
      if (!$textarea || !$textarea.length) return;

      const element = $textarea[0];
      
      // Reset height to calculate proper scrollHeight
      element.style.height = 'auto';
      
      // Calculate new height with constraints
      const scrollHeight = element.scrollHeight;
      const minHeight = parseInt($textarea.css('min-height')) || 52;
      const maxHeight = parseInt($textarea.css('max-height')) || 300;
      
      const newHeight = Math.min(
        Math.max(scrollHeight, minHeight),
        maxHeight
      );
      
      // Apply new height
      element.style.height = newHeight + 'px';
      
      // Handle overflow for content exceeding max height
      if (scrollHeight > maxHeight) {
        element.style.overflowY = 'auto';
      } else {
        element.style.overflowY = 'hidden';
      }
    },

    // Initialize advanced auto-resize for prompt textarea
    initAutoResizeTextarea: function() {
      const $textarea = $(this.config.selectors.promptTextarea);
      
      if ($textarea.length === 0) return;

      // Calculate single line height (font-size + padding + line-height)
      const fontSize = parseInt($textarea.css('font-size')) || 14;
      const lineHeight = parseFloat($textarea.css('line-height')) || 1.5;
      const paddingTop = parseInt($textarea.css('padding-top')) || 16;
      const paddingBottom = parseInt($textarea.css('padding-bottom')) || 16;
      
      // Single line height = font-size * line-height + padding
      const singleLineHeight = Math.ceil(fontSize * lineHeight) + paddingTop + paddingBottom;

      // Set styles for better resize behavior with single line minimum
      $textarea.css({
        'min-height': singleLineHeight + 'px',
        'max-height': '400px',
        'overflow': 'hidden',
        'box-sizing': 'border-box',
        'resize': 'none'
      });

      // Initial resize for existing content
      setTimeout(() => {
        this.autoResizeTextarea($textarea);
      }, 0);

      console.log('Advanced auto-resize textarea initialized with single line height:', singleLineHeight + 'px');
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