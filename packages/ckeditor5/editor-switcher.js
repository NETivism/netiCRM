/**
 * CKEditor Switcher Utility for CiviCRM
 * 
 * Handles dynamic switching between CKEditor 4 and CKEditor 5.
 * Supports on-demand loading of CKEditor 5 resources when needed.
 */
(function() {
  'use strict';

  window.CiviEditorSwitcher = {
    /**
     * Switch editor format for a specific textarea
     * 
     * @param {string} format 'cke4' or 'cke5'
     * @param {string} name Name of the textarea
     * @param {object} config CKE4/CKE5 configuration data
     */
    switch: async function(format, name, config) {
      const el = document.querySelector('textarea[name="' + name + '"]');
      if (!el) return;

      const $el = cj(el);
      const currentType = $el.data('current-editor-type') || ($el.hasClass('ckeditor5-processed') ? 'cke5' : 'cke4');
      if (format === currentType) return;

      const $container = $el.closest('.crm-section').find('.editor-switch-status');
      $container.text('切換中...').css('color', '#666');

      try {
        const content = await this.getCurrentContent(el, currentType);
        await this.destroyEditor(el, currentType);
        
        await new Promise(resolve => setTimeout(resolve, 200));

        if (format === 'cke4') {
          await this.initializeCKE4(el, content, config);
        } else {
          await this.initializeCKE5(el, content, config);
        }

        $el.data('current-editor-type', format);
        $container.text('✓ 已切換').css('color', 'green');
      } catch (error) {
        console.error('Switch error:', error);
        $container.text('✗ 失敗: ' + error.message).css('color', 'red');
      }
    },

    getCurrentContent: async function(el, type) {
      if (type === 'cke4' && window.CKEDITOR && window.CKEDITOR.instances[el.name]) {
        return window.CKEDITOR.instances[el.name].getData();
      } else if (type === 'cke5') {
        const container = el.nextElementSibling;
        if (container && container.classList.contains('ck-editor')) {
          const editable = container.querySelector('.ck-editor__editable');
          if (editable && editable.ckeditorInstance) {
            return editable.ckeditorInstance.getData();
          }
        }
      }
      return el.value;
    },

    destroyEditor: async function(el, type) {
      if (type === 'cke4' && window.CKEDITOR && window.CKEDITOR.instances[el.name]) {
        window.CKEDITOR.instances[el.name].destroy();
        cj(el).removeClass('ckeditor-processed');
      } else if (type === 'cke5') {
        const container = el.nextElementSibling;
        if (container && container.classList.contains('ck-editor')) {
          const editable = container.querySelector('.ck-editor__editable');
          if (editable && editable.ckeditorInstance) {
            await editable.ckeditorInstance.destroy();
          }
          container.remove();
        }
        cj(el).removeClass('ckeditor5-processed');
      }
      el.style.display = 'block';
    },

    initializeCKE4: async function(el, content, config) {
      if (!window.CKEDITOR || !window.CKEDITOR.replace) {
        await this.loadScript(config.resourceBase + 'packages/ckeditor/ckeditor.js?' + config.ver);
      }
      
      if (!window.cke4PluginsRegistered && config.extraPluginsCode) {
        const script = document.createElement('script');
        script.text = config.extraPluginsCode;
        document.head.appendChild(script);
        window.cke4PluginsRegistered = true;
      }

      el.value = content;
      cj(el).addClass('ckeditor-processed');

      return new Promise((resolve) => {
        // Build configuration object for replace()
        const cke4Config = {
          extraPlugins: config.extraPluginsList,
          customConfig: config.customConfigPath,
          width: '100%',
          height: '400',
          allowedContent: config.allowedContent,
          fullPage: false,
          toolbar: config.toolbar
        };

        if (config.imceEnabled) {
          cke4Config.filebrowserBrowseUrl = config.imceUrl;
          cke4Config.filebrowserImageBrowseUrl = config.imceUrl + '&type=Images';
        }

        // Pass configuration object directly to replace()
        const instance = window.CKEDITOR.replace(el.name, cke4Config);
        
        instance.on('key', function(evt) {
          window.global_formNavigate = false;
        });

        instance.on('instanceReady', () => resolve(instance));
      });
    },

    initializeCKE5: async function(el, content, config) {
      // Dynamic load CKE5 core files if missing
      if (!window.CKEDITOR_5) {
        // Load CSS
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = config.resourceBase + 'packages/ckeditor5/ckeditor5.css?' + config.ver;
        document.head.appendChild(link);

        // Cache CKE4 and load CKE5 bundle
        const oldCKE4 = window.CKEDITOR;
        await this.loadScript(config.resourceBase + 'packages/ckeditor5/ckeditor5.umd.js?' + config.ver);
        
        // Swap namespace safely
        window.CKEDITOR_5 = window.CKEDITOR;
        if (oldCKE4 !== undefined) {
          window.CKEDITOR = oldCKE4;
        }

        // Load CiviCRM config (presets and custom plugins)
        await this.loadScript(config.resourceBase + 'packages/ckeditor5/ckeditor5-civicrm.js?' + config.ver);
      }

      const CK5 = window.CKEDITOR_5;
      if (!CK5) throw new Error('CKE5 not loaded');
      
      el.value = content;
      const preset = config.toolbar === 'CiviCRM' ? window.CiviCKEditor5.getFullEditorConfig() : window.CiviCKEditor5.getBasicEditorConfig();

      const editor = await CK5.ClassicEditor.create(el, preset);

      const container = el.nextElementSibling;
      if (container && container.classList.contains('ck-editor')) {
        const editable = container.querySelector('.ck-editor__editable');
        if (editable) editable.ckeditorInstance = editor;
      }

      // Handle form navigation
      editor.model.document.on('change:data', function() {
        window.global_formNavigate = false;
      });
    },

    loadScript: function(src) {
      return new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = src; s.onload = resolve; s.onerror = reject;
        document.head.appendChild(s);
      });
    }
  };

})();
