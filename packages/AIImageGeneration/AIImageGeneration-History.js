/**
 * AI Image Generation History Extension
 * Handles history-related functionality independently
 */
(function($) {
  'use strict';

  // Create independent history manager
  const AIImageHistory = {
    // Configuration
    config: {
      container: '.netiaiig-container',
      apiUrl: '/civicrm/ai/images/history',
      selectors: {
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
        paginationTotal: '.pagination-total',
        // Main image elements
        imagePlaceholder: '.image-placeholder',
        mainImage: '.image-placeholder img',
        emptyState: '.empty-state-content',
        promptTextarea: '.prompt-textarea'
      }
    },

    // History state
    state: {
      currentPage: 1,
      perPage: 30,
      totalPages: 0,
      totalItems: 0,
      isLoading: false,
      data: []
    },

    // Initialize history functionality
    init: function() {
      console.log('Initializing AI Image History...');
      
      // Check if container exists
      if (!$(this.config.container).length) {
        console.warn('AI Image container not found');
        return;
      }

      this.bindEvents();
      this.loadHistory();
      this.listenForGenerationEvents();
      
      console.log('AI Image History initialized successfully');
    },

    // Bind all history-related events
    bindEvents: function() {
      const self = this;
      const $container = $(this.config.container);

      // History refresh button
      $container.on('click', this.config.selectors.historyRefreshBtn, function(e) {
        e.preventDefault();
        console.log('History refresh button clicked');
        self.refreshHistory();
      });

      // Pagination previous button
      $container.on('click', this.config.selectors.paginationPrev, function(e) {
        e.preventDefault();
        if (!$(this).prop('disabled') && self.state.currentPage > 1) {
          console.log('Loading previous page:', self.state.currentPage - 1);
          self.loadHistory(self.state.currentPage - 1);
        }
      });

      // Pagination next button
      $container.on('click', this.config.selectors.paginationNext, function(e) {
        e.preventDefault();
        if (!$(this).prop('disabled') && self.state.currentPage < self.state.totalPages) {
          console.log('Loading next page:', self.state.currentPage + 1);
          self.loadHistory(self.state.currentPage + 1);
        }
      });

      // Note: History items now use lightbox directly through ai-image-link class
      // No need for custom click handlers as Magnific Popup handles the lightbox functionality

      console.log('History events bound successfully');
    },

    // Listen for generation complete events
    listenForGenerationEvents: function() {
      const self = this;
      const $container = $(this.config.container);

      // Listen for generation complete events
      $container.on('generationComplete', function(event, imageUrl, responseData) {
        console.log('Generation complete event received:', imageUrl, responseData);
        if (imageUrl && responseData) {
          // Refresh history after a short delay
          setTimeout(function() {
            console.log('Auto-refreshing history after generation');
            self.refreshHistory();
          }, 1500);
        }
      });

      console.log('Generation event listener registered');
    },

    // Load history from API
    loadHistory: function(page = 1) {
      if (this.state.isLoading) {
        console.log('History loading already in progress');
        return;
      }

      console.log('Loading history page:', page);
      this.state.isLoading = true;
      this.state.currentPage = page;
      this.showHistoryLoading();

      const requestData = {
        page: page,
        per_page: this.state.perPage
      };

      console.log('Sending history request:', requestData);

      $.ajax({
        url: this.config.apiUrl,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(requestData),
        timeout: 30000
      })
      .done((response) => {
        console.log('History API response:', response);
        this.handleHistorySuccess(response);
      })
      .fail((xhr, status, error) => {
        console.error('History API error:', status, error, xhr.responseText);
        this.handleHistoryError(xhr, status, error);
      })
      .always(() => {
        this.state.isLoading = false;
        this.hideHistoryLoading();
      });
    },

    // Handle successful history response
    handleHistorySuccess: function(response) {
      console.log('Processing history response:', response);
      
      if (response.status === 1 && response.data) {
        this.state.data = response.data.images || [];
        this.state.totalItems = response.data.pagination.total || 0;
        this.state.totalPages = response.data.pagination.total_pages || 0;
        this.state.currentPage = response.data.pagination.current_page || 1;

        console.log('History state updated:', this.state);

        this.renderHistoryGrid();
        this.updatePagination();
        this.showHistoryContent();
      } else {
        console.log('No history data found');
        this.showHistoryEmpty();
      }
    },

    // Handle history API error
    handleHistoryError: function(xhr, status, error) {
      console.error('History load error:', status, error);
      console.error('XHR response:', xhr.responseText);
      this.showHistoryError();
    },

    // Render history grid with images
    renderHistoryGrid: function() {
      const $grid = $(this.config.selectors.historyGrid);
      $grid.empty();

      console.log('Rendering history grid with', this.state.data.length, 'items');

      if (this.state.data.length === 0) {
        this.showHistoryEmpty();
        return;
      }

      this.state.data.forEach((imageData, index) => {
        const $item = this.createHistoryItem(imageData, index);
        $grid.append($item);
      });

      console.log('History grid rendered successfully');
    },

    // Create individual history item
    createHistoryItem: function(imageData, index) {
      // Create link element for lightbox functionality
      const $link = $('<a>').attr({
        'href': imageData.image_url,
        'class': 'ai-image-link',
        'data-prompt': imageData.original_prompt || '',
        'data-style': imageData.image_style || '',
        'data-ratio': imageData.image_ratio || '',
        'title': imageData.original_prompt || 'AI Generated Image'
      });

      // Create image element
      const $img = $('<img>').attr({
        'src': imageData.image_url,
        'alt': imageData.original_prompt || 'AI Generated Image',
        'class': 'ai-generated-image',
        'loading': 'lazy'
      });

      // Error handling for image loading
      $img.on('error', function() {
        console.warn('Failed to load history image:', imageData.image_url);
        $link.addClass('image-error');
        $(this).attr('src', 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwIiB5PSI1MCIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+SU1BR0U8L3RleHQ+PC9zdmc+');
      });

      $img.on('load', function() {
        console.log('History image loaded successfully:', index);
      });

      $link.append($img);

      // Create wrapper div for styling purposes
      const $item = $('<div>').addClass('history-item').append($link);

      return $item;
    },

    // Note: loadHistoryImage function removed as history items now use lightbox directly

    // Update pagination controls
    updatePagination: function() {
      const $pagination = $(this.config.selectors.historyPagination);
      
      if (this.state.totalItems === 0) {
        $pagination.hide();
        return;
      }

      const $prevBtn = $(this.config.selectors.paginationPrev);
      const $nextBtn = $(this.config.selectors.paginationNext);
      const $current = $(this.config.selectors.paginationCurrent);
      const $totalPages = $(this.config.selectors.paginationTotalPages);
      const $start = $(this.config.selectors.paginationStart);
      const $end = $(this.config.selectors.paginationEnd);
      const $total = $(this.config.selectors.paginationTotal);

      // Calculate display numbers
      const start = ((this.state.currentPage - 1) * this.state.perPage) + 1;
      const end = Math.min(this.state.currentPage * this.state.perPage, this.state.totalItems);

      // Update pagination info
      $start.text(start);
      $end.text(end);
      $total.text(this.state.totalItems);
      $current.text(this.state.currentPage);
      $totalPages.text(this.state.totalPages);

      // Update button states
      $prevBtn.prop('disabled', this.state.currentPage <= 1);
      $nextBtn.prop('disabled', this.state.currentPage >= this.state.totalPages);

      $pagination.show();
      console.log('Pagination updated:', this.state);
    },

    // Show history loading state
    showHistoryLoading: function() {
      console.log('Showing history loading state');
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
      console.log('Showing history empty state');
      $(this.config.selectors.historyLoading).hide();
      $(this.config.selectors.historyGrid).hide();
      $(this.config.selectors.historyPagination).hide();
      $(this.config.selectors.historyEmpty).show();
    },

    // Show history error state
    showHistoryError: function() {
      this.hideHistoryLoading();
      this.showHistoryEmpty();
      
      // Show error message using global translation if available
      const translation = window.AIImageGeneration?.translation || {};
      const errorMessage = translation.historyLoadFailed || 'Failed to load history, please try again';
      
      console.error('History error:', errorMessage);
      
      // Try to show error via existing error display mechanism
      if (window.NetiAIImageGeneration && typeof window.NetiAIImageGeneration.showError === 'function') {
        window.NetiAIImageGeneration.showError(errorMessage);
      }
    },

    // Refresh history (reload current page)
    refreshHistory: function() {
      console.log('Refreshing history');
      this.loadHistory(this.state.currentPage);
    }
  };

  // Initialize when DOM is ready and main component is loaded
  $(document).ready(function() {
    // Wait for main component to be initialized
    const initHistory = function() {
      if ($(AIImageHistory.config.container).length > 0) {
        console.log('Main container found, initializing history...');
        AIImageHistory.init();
      } else {
        console.log('Main container not found, retrying...');
        setTimeout(initHistory, 500);
      }
    };

    // Start initialization after a short delay
    setTimeout(initHistory, 1000);
  });

  // Expose to global scope for debugging
  window.AIImageHistory = AIImageHistory;

})(jQuery);