/**
 * AI Image Generation History Extension
 * Handles history-related functionality
 */
(function($) {
  'use strict';

  // Extend the existing NetiAIImageGeneration object with history functionality
  if (typeof window.NetiAIImageGeneration !== 'undefined') {
    // History configuration
    $.extend(window.NetiAIImageGeneration.config.selectors, {
      historySection: '.history-section',
      historyGrid: '.history-grid',
      historyItem: '.history-item',
      historyLoading: '.history-loading',
      historyEmpty: '.history-empty',
      historyPagination: '.history-pagination',
      historyRefreshBtn: '.history-refresh-btn',
      paginationPrev: '.pagination-prev',
      paginationNext: '.pagination-next',
      paginationCurrent: '.pagination-current',
      paginationTotalPages: '.pagination-total-pages',
      paginationStart: '.pagination-start',
      paginationEnd: '.pagination-end',
      paginationTotal: '.pagination-total'
    });

    // History state management
    $.extend(window.NetiAIImageGeneration, {
      // History state
      historyState: {
        currentPage: 1,
        perPage: 6,
        totalPages: 0,
        totalItems: 0,
        isLoading: false,
        data: []
      },

      // Initialize history functionality
      initHistory: function() {
        this.bindHistoryEvents();
        this.loadHistory();
        console.log('History functionality initialized');
      },

      // Bind history-related events
      bindHistoryEvents: function() {
        const self = this;
        const $container = $(this.config.container);

        // History refresh button
        $container.on('click', this.config.selectors.historyRefreshBtn, function(e) {
          e.preventDefault();
          self.refreshHistory();
        });

        // Pagination previous button
        $container.on('click', this.config.selectors.paginationPrev, function(e) {
          e.preventDefault();
          if (self.historyState.currentPage > 1) {
            self.loadHistory(self.historyState.currentPage - 1);
          }
        });

        // Pagination next button
        $container.on('click', this.config.selectors.paginationNext, function(e) {
          e.preventDefault();
          if (self.historyState.currentPage < self.historyState.totalPages) {
            self.loadHistory(self.historyState.currentPage + 1);
          }
        });

        // History item click
        $container.on('click', this.config.selectors.historyItem, function(e) {
          e.preventDefault();
          const imageData = $(this).data('image');
          if (imageData) {
            self.loadHistoryImage(imageData);
          }
        });
      },

      // Load history from API
      loadHistory: function(page = 1) {
        if (this.historyState.isLoading) {
          return;
        }

        this.historyState.isLoading = true;
        this.historyState.currentPage = page;
        this.showHistoryLoading();

        const requestData = {
          page: page,
          per_page: this.historyState.perPage
        };

        $.ajax({
          url: '/civicrm/ai/images/history',
          method: 'POST',
          contentType: 'application/json',
          data: JSON.stringify(requestData),
          timeout: 30000
        })
        .done((response) => {
          this.handleHistorySuccess(response);
        })
        .fail((xhr, status, error) => {
          this.handleHistoryError(xhr, status, error);
        })
        .always(() => {
          this.historyState.isLoading = false;
          this.hideHistoryLoading();
        });
      },

      // Handle successful history response
      handleHistorySuccess: function(response) {
        if (response.status === 1 && response.data) {
          this.historyState.data = response.data.images || [];
          this.historyState.totalItems = response.data.pagination.total || 0;
          this.historyState.totalPages = response.data.pagination.total_pages || 0;
          this.historyState.currentPage = response.data.pagination.current_page || 1;

          this.renderHistoryGrid();
          this.updatePagination();
          this.showHistoryContent();
        } else {
          this.showHistoryEmpty();
        }
      },

      // Handle history API error
      handleHistoryError: function(xhr, status, error) {
        console.error('History load error:', status, error);
        this.showHistoryError();
      },

      // Render history grid with images
      renderHistoryGrid: function() {
        const $grid = $(this.config.selectors.historyGrid);
        $grid.empty();

        if (this.historyState.data.length === 0) {
          this.showHistoryEmpty();
          return;
        }

        this.historyState.data.forEach((imageData) => {
          const $item = this.createHistoryItem(imageData);
          $grid.append($item);
        });
      },

      // Create individual history item
      createHistoryItem: function(imageData) {
        const $item = $('<div>').addClass('history-item').attr({
          'data-image': JSON.stringify(imageData),
          'title': imageData.original_prompt || '',
          'role': 'button',
          'tabindex': '0'
        });

        // Create image element
        const $img = $('<img>').attr({
          'src': imageData.image_url,
          'alt': imageData.original_prompt || 'AI Generated Image',
          'loading': 'lazy'
        });

        // Error handling for image loading
        $img.on('error', function() {
          $(this).parent().addClass('image-error');
          $(this).attr('src', 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwIiB5PSI1MCIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+SU1BR0U8L3RleHQ+PC9zdmc+');
        });

        $item.append($img);

        // Add hover effect and accessibility
        $item.on('keydown', function(e) {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).click();
          }
        });

        return $item;
      },

      // Load selected history image into main display
      loadHistoryImage: function(imageData) {
        if (!imageData || !imageData.image_url) {
          return;
        }

        // Update main image display
        const $container = $(this.config.container);
        const $placeholder = $container.find('.image-placeholder');
        const $img = $placeholder.find('img');
        const $emptyState = $placeholder.find('.empty-state-content');

        // Create new image element with metadata
        const $newImg = $('<img>').attr({
          'src': imageData.image_url,
          'alt': imageData.original_prompt || 'AI Generated Image'
        });

        // Wrap in link for lightbox functionality
        const $link = $('<a>').attr({
          'href': imageData.image_url,
          'class': 'ai-image-link',
          'data-prompt': imageData.original_prompt || '',
          'data-style': imageData.image_style || '',
          'data-ratio': imageData.image_ratio || ''
        }).append($newImg);

        // Update display
        $img.replaceWith($link);
        $emptyState.hide();
        
        // Update floating actions
        this.updateFloatingActionsBasedOnImage();

        // Update prompt textarea if exists
        const $promptTextarea = $container.find(this.config.selectors.promptTextarea);
        if ($promptTextarea.length && imageData.original_prompt) {
          $promptTextarea.val(imageData.original_prompt);
        }

        // Trigger custom event
        $container.trigger('historyImageLoaded', [imageData]);

        console.log('History image loaded:', imageData.id);
      },

      // Update pagination controls
      updatePagination: function() {
        const $pagination = $(this.config.selectors.historyPagination);
        const $prevBtn = $(this.config.selectors.paginationPrev);
        const $nextBtn = $(this.config.selectors.paginationNext);
        const $current = $(this.config.selectors.paginationCurrent);
        const $totalPages = $(this.config.selectors.paginationTotalPages);
        const $start = $(this.config.selectors.paginationStart);
        const $end = $(this.config.selectors.paginationEnd);
        const $total = $(this.config.selectors.paginationTotal);

        if (this.historyState.totalItems === 0) {
          $pagination.hide();
          return;
        }

        // Calculate display numbers
        const start = ((this.historyState.currentPage - 1) * this.historyState.perPage) + 1;
        const end = Math.min(this.historyState.currentPage * this.historyState.perPage, this.historyState.totalItems);

        // Update pagination info
        $start.text(start);
        $end.text(end);
        $total.text(this.historyState.totalItems);
        $current.text(this.historyState.currentPage);
        $totalPages.text(this.historyState.totalPages);

        // Update button states
        $prevBtn.prop('disabled', this.historyState.currentPage <= 1);
        $nextBtn.prop('disabled', this.historyState.currentPage >= this.historyState.totalPages);

        $pagination.show();
      },

      // Show history loading state
      showHistoryLoading: function() {
        $(this.config.selectors.historyLoading).show();
        $(this.config.selectors.historyGrid).hide();
        $(this.config.selectors.historyEmpty).hide();
        $(this.config.selectors.historyPagination).hide();
      },

      // Hide history loading state
      hideHistoryLoading: function() {
        $(this.config.selectors.historyLoading).hide();
      },

      // Show history content
      showHistoryContent: function() {
        $(this.config.selectors.historyGrid).show();
        this.updatePagination();
      },

      // Show empty history state
      showHistoryEmpty: function() {
        $(this.config.selectors.historyLoading).hide();
        $(this.config.selectors.historyGrid).hide();
        $(this.config.selectors.historyPagination).hide();
        $(this.config.selectors.historyEmpty).show();
      },

      // Show history error state
      showHistoryError: function() {
        this.hideHistoryLoading();
        this.showHistoryEmpty();
        
        // Show error message
        const translation = window.AIImageGeneration?.translation || {};
        const errorMessage = translation.historyLoadFailed || 'Failed to load history, please try again';
        
        if (typeof this.showError === 'function') {
          this.showError(errorMessage);
        } else {
          console.error(errorMessage);
        }
      },

      // Refresh history (reload current page)
      refreshHistory: function() {
        this.loadHistory(this.historyState.currentPage);
      },

      // Add new image to history (called after successful generation)
      addToHistory: function(imageData) {
        // Only add to first page if we're currently viewing it
        if (this.historyState.currentPage === 1) {
          this.historyState.data.unshift(imageData);
          // Keep only the items that fit in current page
          this.historyState.data = this.historyState.data.slice(0, this.historyState.perPage);
          this.renderHistoryGrid();
        }
        
        // Update total count
        this.historyState.totalItems++;
        this.historyState.totalPages = Math.ceil(this.historyState.totalItems / this.historyState.perPage);
        this.updatePagination();
        this.showHistoryContent();
      }
    });

    // Auto-initialize history when the main component initializes
    $(document).ready(function() {
      if (window.NetiAIImageGeneration && typeof window.NetiAIImageGeneration.initHistory === 'function') {
        // Wait for main component to be ready
        setTimeout(function() {
          window.NetiAIImageGeneration.initHistory();
          
          // Listen for generation complete events to refresh history
          $(window.NetiAIImageGeneration.config.container).on('generationComplete', function(event, imageUrl, responseData) {
            if (imageUrl && responseData) {
              // Refresh history after a short delay to ensure backend has processed the record
              setTimeout(function() {
                window.NetiAIImageGeneration.refreshHistory();
              }, 1000);
            }
          });
        }, 100);
      }
    });
  }

})(jQuery);