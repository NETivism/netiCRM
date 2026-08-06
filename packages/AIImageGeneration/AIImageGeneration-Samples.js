/**
 * AI Image Generation Sample Gallery Extension
 * Handles the tab switching and the sample gallery independently
 */
(function($) {
  'use strict';

  // Placeholder shown when a sample image fails to load, same as history grid
  const IMAGE_ERROR_PLACEHOLDER = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwIiB5PSI1MCIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+SU1BR0U8L3RleHQ+PC9zdmc+';

  // Create independent sample gallery manager
  const AIImageSamples = {
    // Configuration
    config: {
      container: '.netiaiig-container',
      apiUrl: '/civicrm/ai/images/sample-list',
      selectors: {
        // Tabs
        tab: '.netiaiig-tab',
        tabPane: '.netiaiig-tabpane',
        // Sample gallery elements
        samplesGrid: '.samples-grid',
        samplesLoading: '.samples-loading',
        samplesEmpty: '.samples-empty',
        samplesEmptyText: '.samples-empty .history-empty-text',
        // Filter elements
        styleDropdown: '#samplesStyleDropdown',
        ratioDropdown: '#samplesRatioDropdown',
        styleGrid: '#samplesStyleDropdown .style-grid',
        ratioMenu: '#samplesRatioDropdown .dropdown-menu',
        styleText: '.samples-style-text',
        ratioText: '.samples-ratio-text',
        styleOption: '.samples-style-option',
        ratioOption: '#samplesRatioDropdown .dropdown-item',
        // Style presets of the generator, reused for filter labels and thumbs
        generatorStyleOptions: '#styleDropdown .style-option'
      },
      classes: {
        active: 'active',
        selected: 'selected',
        tabActive: 'is-active'
      }
    },

    // Sample gallery state
    state: {
      items: [],
      styleFilter: '',
      ratioFilter: '',
      isLoading: false,
      isLoaded: false,
      // Original empty state text, kept so the error message can be reverted
      emptyText: ''
    },

    // Initialize sample gallery functionality
    init: function() {
      console.log('Initializing AI Image Samples...');

      this.bindEvents();

      console.log('AI Image Samples initialized successfully');
    },

    // Bind all sample gallery events
    // Delegated from document because the side panel rebuilds the container
    // markup from an html string, which drops handlers bound to the container
    bindEvents: function() {
      const self = this;
      const selectors = this.config.selectors;

      // Tab switching
      $(document).on('click', selectors.tab, function(e) {
        e.preventDefault();
        self.switchTab($(this).data('tab'));
      });

      // Filter dropdown toggles, stop propagation so the global outside click
      // handler of the main component does not close it right away
      $(document).on('click', `${selectors.styleDropdown} .dropdown-toggle`, function(e) {
        e.stopPropagation();
        self.toggleDropdown($(this).closest('.netiaiig-dropdown'));
      });

      $(document).on('click', `${selectors.ratioDropdown} .dropdown-toggle`, function(e) {
        e.stopPropagation();
        self.toggleDropdown($(this).closest('.netiaiig-dropdown'));
      });

      // Style filter selection
      $(document).on('click', selectors.styleOption, function(e) {
        e.stopPropagation();
        self.selectStyleFilter($(this));
      });

      // Ratio filter selection
      $(document).on('click', selectors.ratioOption, function(e) {
        e.stopPropagation();
        self.selectRatioFilter($(this));
      });

      console.log('Sample gallery events bound successfully');
    },

    // Switch between history and sample gallery tabs
    switchTab: function(tabName) {
      if (!tabName) {
        return;
      }

      const $container = $(this.config.container);
      const classes = this.config.classes;

      $container.find(this.config.selectors.tab).each(function() {
        const isTarget = $(this).data('tab') === tabName;
        $(this).toggleClass(classes.tabActive, isTarget).attr('aria-selected', isTarget ? 'true' : 'false');
      });

      $container.find(this.config.selectors.tabPane).each(function() {
        const isTarget = $(this).data('tab') === tabName;
        $(this).toggleClass(classes.tabActive, isTarget).toggle(isTarget);
      });

      // Load the sample list on first activation only
      if (tabName === 'samples' && !this.state.isLoaded) {
        this.loadSamples();
      }
    },

    // Toggle a filter dropdown, closing every other dropdown first
    toggleDropdown: function($dropdown) {
      const isActive = $dropdown.hasClass(this.config.classes.active);

      $(this.config.container).find('.netiaiig-dropdown').removeClass(this.config.classes.active);

      if (!isActive) {
        $dropdown.addClass(this.config.classes.active);
      }
    },

    // Load the full sample list from API
    loadSamples: function() {
      if (this.state.isLoading) {
        console.log('Sample list loading already in progress');
        return;
      }

      console.log('Loading sample list');
      this.state.isLoading = true;
      this.showSamplesLoading();

      $.ajax({
        url: this.config.apiUrl,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({}),
        timeout: 30000
      })
      .done((response) => {
        console.log('Sample list API response:', response);
        this.handleSamplesSuccess(response);
      })
      .fail((xhr, status, error) => {
        console.error('Sample list API error:', status, error, xhr.responseText);
        this.showSamplesError();
      })
      .always(() => {
        this.state.isLoading = false;
        $(this.config.selectors.samplesLoading).hide();
      });
    },

    // Handle successful sample list response
    handleSamplesSuccess: function(response) {
      if (response.status === 1 && response.data && response.data.images && response.data.images.length) {
        this.state.items = response.data.images;
        this.state.isLoaded = true;

        console.log('Sample list loaded:', this.state.items.length, 'items');

        this.buildStyleFilter();
        this.buildRatioFilter();
        this.renderSamplesGrid();
      }
      else {
        console.log('No sample image data found');
        this.showSamplesEmpty();
      }
    },

    // Build style filter options from the loaded data
    buildStyleFilter: function() {
      const self = this;
      const $container = $(this.config.container);
      const $grid = $container.find(this.config.selectors.styleGrid);
      const translation = window.AIImageGeneration?.translation || {};
      const allLabel = translation.samplesAllStyles || 'All Styles';

      // Collect styles that really exist in the data
      const dataStyles = [];
      this.state.items.forEach(function(item) {
        if (item.style && dataStyles.indexOf(item.style) === -1) {
          dataStyles.push(item.style);
        }
      });

      // Follow the generator dropdown order, then append unknown styles
      const orderedStyles = [];
      $container.find(this.config.selectors.generatorStyleOptions).each(function() {
        const style = $(this).data('style');
        if (dataStyles.indexOf(style) !== -1 && orderedStyles.indexOf(style) === -1) {
          orderedStyles.push(style);
        }
      });
      dataStyles.forEach(function(style) {
        if (orderedStyles.indexOf(style) === -1) {
          orderedStyles.push(style);
        }
      });

      $grid.empty();

      // "All styles" option
      $grid.append(this.createStyleOption('', allLabel, '', true));

      orderedStyles.forEach(function(style) {
        const preset = self.getGeneratorStylePreset(style);
        $grid.append(self.createStyleOption(style, preset.label, preset.thumb, false));
      });

      $container.find(this.config.selectors.styleText).text(allLabel);
    },

    // Get label and thumbnail of a style from the generator dropdown
    getGeneratorStylePreset: function(style) {
      // first() because the markup can exist twice, inline and in the side panel
      const $option = $(this.config.container)
        .find(`${this.config.selectors.generatorStyleOptions}[data-style="${style}"]`)
        .first();

      if (!$option.length) {
        return { label: style, thumb: '' };
      }

      return {
        label: $option.find('.style-label').text() || style,
        thumb: $option.find('.style-preview img').attr('src') || ''
      };
    },

    // Create a single style filter option
    createStyleOption: function(style, label, thumb, isSelected) {
      const $option = $('<div>')
        .addClass('samples-style-option')
        .attr('data-filter-style', style)
        .attr('title', label);

      if (isSelected) {
        $option.addClass(this.config.classes.selected);
      }

      const $preview = $('<div>').addClass('style-preview');
      if (thumb) {
        $preview.append($('<img>').attr({ 'src': thumb, 'alt': '' }));
      }
      else {
        $preview.addClass('samples-style-preview-all');
      }

      $option.append($preview);
      $option.append($('<div>').addClass('style-label').text(label));

      return $option;
    },

    // Build ratio filter options from the loaded data
    buildRatioFilter: function() {
      const $container = $(this.config.container);
      const $menu = $container.find(this.config.selectors.ratioMenu);
      const translation = window.AIImageGeneration?.translation || {};
      const allLabel = translation.samplesAllRatios || 'All Aspect Ratios';

      const ratios = [];
      this.state.items.forEach(function(item) {
        if (item.ratio && ratios.indexOf(item.ratio) === -1) {
          ratios.push(item.ratio);
        }
      });
      ratios.sort();

      $menu.empty();

      $menu.append(
        $('<div>')
          .addClass(`dropdown-item ${this.config.classes.selected}`)
          .attr('data-filter-ratio', '')
          .text(allLabel)
      );

      ratios.forEach(function(ratio) {
        $menu.append(
          $('<div>')
            .addClass('dropdown-item')
            .attr('data-filter-ratio', ratio)
            .text(ratio)
        );
      });

      $container.find(this.config.selectors.ratioText).text(allLabel);
    },

    // Handle style filter selection
    selectStyleFilter: function($option) {
      const $container = $(this.config.container);
      const style = $option.attr('data-filter-style') || '';

      $option.siblings().removeClass(this.config.classes.selected);
      $option.addClass(this.config.classes.selected);

      $container.find(this.config.selectors.styleText).text($option.find('.style-label').text());
      $container.find(this.config.selectors.styleDropdown).removeClass(this.config.classes.active);

      this.state.styleFilter = style;
      this.renderSamplesGrid();
    },

    // Handle ratio filter selection
    selectRatioFilter: function($option) {
      const $container = $(this.config.container);
      const ratio = $option.attr('data-filter-ratio') || '';

      $option.siblings().removeClass(this.config.classes.selected);
      $option.addClass(this.config.classes.selected);

      $container.find(this.config.selectors.ratioText).text($option.text());
      $container.find(this.config.selectors.ratioDropdown).removeClass(this.config.classes.active);

      this.state.ratioFilter = ratio;
      this.renderSamplesGrid();
    },

    // Get sample items matching the current filters
    getFilteredItems: function() {
      const styleFilter = this.state.styleFilter;
      const ratioFilter = this.state.ratioFilter;

      return this.state.items.filter(function(item) {
        if (styleFilter && item.style !== styleFilter) {
          return false;
        }
        if (ratioFilter && item.ratio !== ratioFilter) {
          return false;
        }
        return true;
      });
    },

    // Render sample grid with the filtered images
    renderSamplesGrid: function() {
      const $grid = $(this.config.selectors.samplesGrid);
      const items = this.getFilteredItems();

      $grid.empty();

      console.log('Rendering sample grid with', items.length, 'items');

      if (items.length === 0) {
        this.showSamplesEmpty();
        return;
      }

      // Append in one call, the grid can exist twice and every append would
      // otherwise clone the item for the second grid
      $grid.append(items.map((item) => this.createSampleItem(item)[0]));

      $(this.config.selectors.samplesEmpty).hide();
      $grid.show();
    },

    // Create individual sample item, same markup as the history grid so the
    // lightbox and its actions work without extra handlers
    createSampleItem: function(item) {
      const $link = $('<a>').attr({
        'href': item.image_url,
        'class': 'ai-image-link',
        'data-prompt': item.text || '',
        'data-style': item.style || '',
        'data-ratio': item.ratio || '',
        'title': item.text || 'AI Generated Image'
      });

      const $img = $('<img>').attr({
        'src': item.image_url,
        'alt': item.text || 'AI Generated Image',
        'class': 'ai-generated-image',
        'loading': 'lazy'
      });

      // Error handling for image loading
      $img.on('error', function() {
        console.warn('Failed to load sample image:', item.image_url);
        $link.addClass('image-error');
        $(this).attr('src', IMAGE_ERROR_PLACEHOLDER);
      });

      $link.append($img);

      // Reuse the history item wrapper for styling purposes
      return $('<div>').addClass('history-item').append($link);
    },

    // Show sample gallery loading state
    showSamplesLoading: function() {
      $(this.config.selectors.samplesLoading).show();
      $(this.config.selectors.samplesGrid).hide();
      $(this.config.selectors.samplesEmpty).hide();
    },

    // Show sample gallery empty state, optionally with a custom message
    showSamplesEmpty: function(message) {
      const $emptyText = $(this.config.selectors.samplesEmptyText);

      // Keep the default text from the template so an error message can be reverted
      if (!this.state.emptyText) {
        this.state.emptyText = $emptyText.first().text();
      }

      $emptyText.text(message || this.state.emptyText);
      $(this.config.selectors.samplesLoading).hide();
      $(this.config.selectors.samplesGrid).hide();
      $(this.config.selectors.samplesEmpty).show();
    },

    // Show sample gallery error state
    showSamplesError: function() {
      const translation = window.AIImageGeneration?.translation || {};
      const errorMessage = translation.samplesLoadFailed || 'Failed to load sample gallery, please try again';

      console.error('Sample gallery error:', errorMessage);

      this.showSamplesEmpty(errorMessage);
    }
  };

  // Initialize when DOM is ready, events are delegated from document so the
  // container does not have to exist yet
  $(document).ready(function() {
    AIImageSamples.init();
  });

  // Expose to global scope for debugging
  window.AIImageSamples = AIImageSamples;

})(jQuery);
