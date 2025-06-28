(function($) {
  'use strict';

  // RFM State Manager Class
  var RFMStateManager = function() {
    // Initialize segment descriptions from backend data
    this.segmentDescriptions = this.initializeSegmentDescriptions();

    // Helper methods for RFM code conversion
    this.rfmCodeToNumericId = function(rfmCode) {
      if (!rfmCode || rfmCode.length !== 6) {
        return 0; // Default fallback
      }

      // Extract R, F, M values (l=0, h=1)
      var r = (rfmCode.charAt(1) === 'h') ? 1 : 0;  // Position 1: R value
      var f = (rfmCode.charAt(3) === 'h') ? 1 : 0;  // Position 3: F value
      var m = (rfmCode.charAt(5) === 'h') ? 1 : 0;  // Position 5: M value

      // Binary to decimal: R×4 + F×2 + M×1
      return (r * 4) + (f * 2) + (m * 1);
    };

    this.numericIdToRfmCode = function(numericId) {
      if (numericId < 0 || numericId > 7) {
        return 'RlFlMl'; // Default fallback
      }

      // Convert to binary and extract R, F, M
      var r = (numericId & 4) ? 'h' : 'l';  // Bit 2 (4)
      var f = (numericId & 2) ? 'h' : 'l';  // Bit 1 (2)
      var m = (numericId & 1) ? 'h' : 'l';  // Bit 0 (1)

      return 'R' + r + 'F' + f + 'M' + m;
    };

    // Cube to segment mapping - calculated dynamically
    this.cubeSegmentMap = {
      'cube-1': this.rfmCodeToNumericId('RlFhMh').toString(),  // At Risk Big
      'cube-2': this.rfmCodeToNumericId('RhFhMh').toString(),  // Champions
      'cube-3': this.rfmCodeToNumericId('RlFlMh').toString(),  // Hibernating Big
      'cube-4': this.rfmCodeToNumericId('RhFlMh').toString(),  // New Big
      'cube-5': this.rfmCodeToNumericId('RlFhMl').toString(),  // At Risk Small
      'cube-6': this.rfmCodeToNumericId('RhFhMl').toString(),  // Loyal Small
      'cube-7': this.rfmCodeToNumericId('RlFlMl').toString(),  // Hibernating Small
      'cube-8': this.rfmCodeToNumericId('RhFlMl').toString()   // New Small
    };

    // Current state
    this.currentState = {
      activeCube: null,
      activeNumericId: null,
      hasActiveElement: false
    };

    // DOM elements
    this.cubeContainer = $('.cube-container');
    this.cubes = $('.small-cube');
    this.segmentItems = $('.segment-item');
    this.descriptionPanel = $('#segmentDescription');
    this.descriptionTitle = $('#descriptionTitle');
    this.descriptionContent = $('#descriptionContent');
    this.descriptionRfm = $('#descriptionRfm');

    this.init();
  };

  RFMStateManager.prototype = {
    /**
     * Initialize segment descriptions from backend data
     * Read from window.rfmSegmentData and provide fallback if not available
     *
     * @return {Object} Segment descriptions object
     */
    initializeSegmentDescriptions: function() {
      // Try to read from backend data
      if (typeof window.rfmSegmentData !== 'undefined' && window.rfmSegmentData !== null) {

        // Handle array format (convert to object with numeric keys)
        if (Array.isArray(window.rfmSegmentData)) {
          console.log('RFM: Converting array format to object format');
          var convertedData = {};
          for (var i = 0; i < window.rfmSegmentData.length; i++) {
            convertedData[i.toString()] = window.rfmSegmentData[i];
          }
          return convertedData;
        }

        // Handle object format (already correct)
        if (typeof window.rfmSegmentData === 'object' && Object.keys(window.rfmSegmentData).length > 0) {
          console.log('RFM: Successfully loaded backend segment data');
          return window.rfmSegmentData;
        }
      }

      // Fallback to default data if backend data not available
      console.warn('RFM: Backend segment data not found or invalid format, using fallback data');
      return this.getFallbackSegmentDescriptions();
    },

    /**
     * Provide fallback segment descriptions when backend data is not available
     * This ensures the system continues to work even if backend integration fails
     *
     * @return {Object} Fallback segment descriptions
     */
    getFallbackSegmentDescriptions: function() {
      return {
        '0': {
          title: 'RFM Hibernating Small',
          content: 'These donors have not participated in donations for a long time, with low donation frequency and amounts.',
          rfm: { r: 'low', f: 'low', m: 'low' }
        },
        '1': {
          title: 'RFM Hibernating Big',
          content: 'Although they have not donated for a long time and have infrequent donations, they previously provided larger amounts of support.',
          rfm: { r: 'low', f: 'low', m: 'high' }
        },
        '2': {
          title: 'RFM At Risk Small',
          content: 'Previously stable small-amount donors who participated regularly but recently stopped donating.',
          rfm: { r: 'low', f: 'high', m: 'low' }
        },
        '3': {
          title: 'RFM At Risk Big',
          content: 'Former important supporters who donated frequently with high amounts but recently stopped participating.',
          rfm: { r: 'low', f: 'high', m: 'high' }
        },
        '4': {
          title: 'RFM New Small',
          content: 'New friends who just started following the organization with small donation amounts and infrequent donations.',
          rfm: { r: 'high', f: 'low', m: 'low' }
        },
        '5': {
          title: 'RFM New Big',
          content: 'Although donation frequency is low, they are willing to provide larger amounts of support at once.',
          rfm: { r: 'high', f: 'low', m: 'high' }
        },
        '6': {
          title: 'RFM Loyal Small',
          content: 'The organization\'s most stable foundation, continuously and frequently providing small support.',
          rfm: { r: 'high', f: 'high', m: 'low' }
        },
        '7': {
          title: 'RFM Champions',
          content: 'The organization\'s most valuable partners, performing excellently in all aspects.',
          rfm: { r: 'high', f: 'high', m: 'high' }
        }
      };
    },

    init: function() {
      var self = this;

      // Cube events
      this.cubes.on('mouseenter', function(e) { self.handleCubeHover(e); });
      this.cubes.on('mouseleave', function(e) { self.handleCubeLeave(e); });
      this.cubes.on('click', function(e) { self.handleCubeClick(e); });

      // Segment events
      this.segmentItems.on('mouseenter', function(e) { self.handleSegmentHover(e); });
      this.segmentItems.on('mouseleave', function(e) { self.handleSegmentLeave(e); });

      // Hide description when clicking outside
      $(document).on('click', function(e) {
        if (!$(e.target).closest('.segment-description').length &&
            !$(e.target).closest('.small-cube').length &&
            !$(e.target).closest('.segment-item').length) {
          self.hideDescription();
        }
      });
    },

    handleCubeHover: function(event) {
      var cubeElement = $(event.currentTarget);
      var cubeClass = this.getCubeClass(cubeElement);
      var numericId = this.cubeSegmentMap[cubeClass];

      this.setState({
        activeCube: cubeClass,
        activeNumericId: numericId,
        hasActiveElement: true
      });

      this.showDescription(numericId);
    },

    handleCubeLeave: function(event) {
      var self = this;
      var relatedTarget = event.relatedTarget;
      if (relatedTarget && $(relatedTarget).closest('.segment-description').length) {
        return;
      }

      this.setState({
        activeCube: null,
        activeNumericId: null,
        hasActiveElement: false
      });

      setTimeout(function() {
        if (!self.currentState.hasActiveElement) {
          self.hideDescription();
        }
      }, 100);
    },

    handleSegmentHover: function(event) {
      var segmentElement = $(event.currentTarget);
      var numericId = segmentElement.data('segment');
      var cubeClass = this.getCorrespondingCube(numericId);

      this.setState({
        activeCube: cubeClass,
        activeNumericId: numericId,
        hasActiveElement: true
      });

      this.showDescription(numericId);
    },

    handleSegmentLeave: function(event) {
      var self = this;
      var relatedTarget = event.relatedTarget;
      if (relatedTarget && $(relatedTarget).closest('.segment-description').length) {
        return;
      }

      this.setState({
        activeCube: null,
        activeNumericId: null,
        hasActiveElement: false
      });

      setTimeout(function() {
        if (!self.currentState.hasActiveElement) {
          self.hideDescription();
        }
      }, 100);
    },

    handleCubeClick: function(event) {
      event.stopPropagation();
      var cubeElement = $(event.currentTarget);
      var cubeClass = this.getCubeClass(cubeElement);
      var numericId = this.cubeSegmentMap[cubeClass];

      // Find the corresponding segment link and trigger click
      var segmentLink = $('.segment-item[data-segment="' + numericId + '"] .segment-link');
      if (segmentLink.length) {
        window.location.href = segmentLink.attr('href');
      }
    },

    showDescription: function(numericId) {
      // Ensure numericId is a string for consistent lookup
      var numericIdStr = numericId.toString();
      var description = this.segmentDescriptions[numericIdStr];

      if (!description) {
        console.warn('RFM: Segment description not found for ID:', numericIdStr);
        return;
      }

      this.descriptionTitle.text(description.title);
      this.descriptionContent.text(description.content);

      // Generate RFM indicators
      var rfmHtml =
        '<span class="rfm-tag ' + description.rfm.r + '">' +
          '<span class="triangle ' + (description.rfm.r === 'high' ? 'up' : 'down') + '"></span>R' +
        '</span>' +
        '<span class="rfm-tag ' + description.rfm.f + '">' +
          '<span class="triangle ' + (description.rfm.f === 'high' ? 'up' : 'down') + '"></span>F' +
        '</span>' +
        '<span class="rfm-tag ' + description.rfm.m + '">' +
          '<span class="triangle ' + (description.rfm.m === 'high' ? 'up' : 'down') + '"></span>M' +
        '</span>';

      this.descriptionRfm.html(rfmHtml);
      this.descriptionPanel.attr('data-segment', numericIdStr);
      this.descriptionPanel.addClass('show');
    },

    hideDescription: function() {
      this.descriptionPanel.removeClass('show');
    },

    setState: function(newState) {
      this.currentState = $.extend(this.currentState, newState);
      this.syncUI();
    },

    syncUI: function() {
      var activeCube = this.currentState.activeCube;
      var activeNumericId = this.currentState.activeNumericId;
      var hasActiveElement = this.currentState.hasActiveElement;

      // Update cube container state
      if (hasActiveElement) {
        this.cubeContainer.addClass('has-active');
      } else {
        this.cubeContainer.removeClass('has-active');
      }

      // Update cube states
      var self = this;
      this.cubes.each(function() {
        var cube = $(this);
        var cubeClass = self.getCubeClass(cube);
        if (cubeClass === activeCube) {
          cube.addClass('is-active');
        } else {
          cube.removeClass('is-active');
        }
      });

      // Update segment states
      this.segmentItems.each(function() {
        var item = $(this);
        var numericId = item.data('segment').toString(); // Ensure string comparison
        if (numericId === activeNumericId) {
          item.addClass('is-active');
        } else {
          item.removeClass('is-active');
        }
      });
    },

    getCubeClass: function(cubeElement) {
      var classList = cubeElement.attr('class').split(/\s+/);
      for (var i = 0; i < classList.length; i++) {
        if (classList[i].indexOf('cube-') === 0) {
          return classList[i];
        }
      }
      return null;
    },

    getCorrespondingCube: function(numericId) {
      for (var cube in this.cubeSegmentMap) {
        if (this.cubeSegmentMap[cube] == numericId) {
          return cube;
        }
      }
      return null;
    }
  };

  // Enhanced RFM popup functionality
  let originalValues = {
    recency: 0,
    frequency: 0,
    monetary: 0
  };
  let valuesSaved = false;

  function updateThresholdValues() {
    var rValue = $('#rfm_r_value').val();
    $('output[data-threshold-type="recency"]').text(rValue);

    var fValue = $('#rfm_f_value').val();
    $('output[data-threshold-type="frequency"]').text(fValue);

    var mValue = $('#rfm_m_value').val();
    var formattedMValue = Number(mValue).toLocaleString('zh-TW');
    $('output[data-threshold-type="monetary"]').text(formattedMValue);
  }

  function saveOriginalValues() {
    originalValues.recency = $('#rfm_r_value').val();
    originalValues.frequency = $('#rfm_f_value').val();
    originalValues.monetary = $('#rfm_m_value').val();
    valuesSaved = false;
  }

  function restoreOriginalValues() {
    $('#rfm_r_value').val(originalValues.recency);
    $('#rfm_f_value').val(originalValues.frequency);
    $('#rfm_m_value').val(originalValues.monetary);
  }

  $(function() {
    // Initialize 3D RFM visualization
    if ($('.cube-container').length) {
      window.rfmStateManager = new RFMStateManager();

      // Debug: Log initialization status
      if (typeof window.rfmSegmentData !== 'undefined') {
        if (Array.isArray(window.rfmSegmentData)) {
          console.log('RFM: Backend data converted from array to object format for', window.rfmSegmentData.length, 'segments');
        } else if (typeof window.rfmSegmentData === 'object' && window.rfmSegmentData !== null) {
          console.log('RFM: Successfully initialized with backend object data for', Object.keys(window.rfmSegmentData).length, 'segments');
        } else {
          console.warn('RFM: Backend data is invalid format:', typeof window.rfmSegmentData, window.rfmSegmentData);
        }
      } else {
        console.warn('RFM: window.rfmSegmentData is undefined, using fallback data');
      }
    }

    // Initialize accordion
    $().crmaccordions();

    // Initialize RFM popup
    if ($.fn.magnificPopup && $('#rfm-popup').length) {
      $('.crm-container').on('click', '.rfm-popup-open-link', function(e) {
        e.preventDefault();
        saveOriginalValues();

        $.magnificPopup.open({
          items: { src: '#rfm-popup' },
          type: 'inline',
          mainClass: 'mfp-rfm-popup',
          preloader: true,
          showCloseBtn: false,
          callbacks: {
            open: function() {
              $('body').addClass('rfm-popup-active mfp-is-active');
            },
            beforeClose: function() {
              if (!valuesSaved) {
                restoreOriginalValues();
              }
            },
            close: function() {
              $('body').removeClass('rfm-popup-active mfp-is-active');
            }
          }
        });
      });

      $('body').on('click', '.rfm-save-btn', function() {
        updateThresholdValues();
        valuesSaved = true;
        $.magnificPopup.close();
      });

      $('body').on('click', '.rfm-cancel-btn, .rfm-popup-close', function() {
        $.magnificPopup.close();
      });
    }
  });
})(jQuery);