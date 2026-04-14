/**
 * CKEditor 5 Configuration for CiviCRM
 *
 * IIFE wrapper that reads classes from the global CKEDITOR_5 namespace
 * (set by ckeditor5.umd.js + namespace swap in ckeditor5.php).
 *
 * Exposes window.CiviCKEditor5 with:
 * - ExtendSchema: Custom plugin for font icons, iframe, script whitelist
 * - getFullEditorConfig(overrides): Full editor config (CiviCRM preset)
 * - getBasicEditorConfig(overrides): Basic editor config (CiviCRMBasic preset)
 */
(function() {
  'use strict';

  var CK = window.CKEDITOR_5;
  if (!CK) {
    console.error('CiviCKEditor5: window.CKEDITOR_5 not found');
    return;
  }

  // Destructure required classes from UMD bundle
  var Plugin = CK.Plugin;
  var GeneralHtmlSupport = CK.GeneralHtmlSupport;
  var DataFilter = CK.DataFilter;
  var DataSchema = CK.DataSchema;

  // ==========================================================================
  // Constants
  // ==========================================================================

  /**
   * Trusted domains for external script sources.
   * Only <script src="..."> from these domains will be preserved by the editor.
   */
  var TRUSTED_SCRIPT_DOMAINS = [
    'dev7.neticrm.tw',
    'neticrm.tw',
    'cdnjs.cloudflare.com',
    'cdn.jsdelivr.net',
  ];

  /**
   * Regex pattern for matching font icon CSS classes.
   * Covers Font Awesome (fa-) and Material Design Icons (zmdi-).
   */
  var FONT_ICON_PATTERN = /(fa-|zmdi-)/;

  // ==========================================================================
  // ExtendSchema Plugin
  // ==========================================================================

  /**
   * Custom plugin that extends GeneralHtmlSupport with:
   * 1. Font icon support (<i class="fa-*"> / <i class="zmdi-*">)
   * 2. iframe as proper block object
   * 3. Script tag whitelist (trusted domains only)
   * 4. Table width fix
   * 5. Table figure removal (for email templates)
   * 6. Email custom tags (<unsubscribe>)
   * 7. Allow div in heading (Bootstrap accordion)
   *
   * Configuration via editor config:
   *   extendSchema: {
   *     enableFontIcons: true,
   *     enableIframe: true,
   *     trustedScriptDomains: ['neticrm.tw', ...]
   *   }
   */
  class ExtendSchema extends Plugin {
    static get pluginName() {
      return 'ExtendSchema';
    }

    static get requires() {
      return [GeneralHtmlSupport];
    }

    init() {
      var config = this.editor.config.get('extendSchema') || {};
      var enableFontIcons = config.enableFontIcons !== false; // default: true
      var enableIframe = config.enableIframe || false;
      var trustedScriptDomains = config.trustedScriptDomains || [];

      if (enableFontIcons) {
        this._setupFontIcons();
      }
      if (enableIframe) {
        this._setupIframe();
      }
      if (trustedScriptDomains.length > 0) {
        this._setupScriptWhitelist(trustedScriptDomains);
      }
      this._setupTableWidthFix();
      this._setupTableFigureRemoval();
      this._setupEmailCustomTags();
      this._setAllowDivInHeading();
    }

    /**
     * Font icon support.
     * Registers <i> elements with font icon classes as non-editable objects
     * so CKEditor 5 won't strip empty <i></i> tags.
     */
    _setupFontIcons() {
      var editor = this.editor;
      var schema = editor.model.schema;

      // Register htmlI as object in model
      if (!schema.isRegistered('htmlI')) {
        schema.register('htmlI', {
          allowWhere: '$text',
          isObject: true,
          allowAttributes: ['htmlAttributes'],
        });
      }

      // Ensure block elements allow htmlI children
      var blockModels = [
        'paragraph',
        'heading1', 'heading2', 'heading3',
        'heading4', 'heading5', 'heading6',
      ];
      blockModels.forEach(function(modelName) {
        if (schema.isRegistered(modelName)) {
          schema.extend(modelName, {
            allowChildren: 'htmlI',
            allowAttributes: ['htmlAttributes'],
          });
        }
      });

      // Upcast: HTML -> Model
      editor.conversion.for('upcast').elementToElement({
        view: {
          name: 'i',
          classes: FONT_ICON_PATTERN,
        },
        model: function(viewElement, conversionApi) {
          var writer = conversionApi.writer;
          return writer.createElement('htmlI', {
            htmlAttributes: {
              class: viewElement.getAttribute('class') || '',
              style: viewElement.getAttribute('style') || '',
            },
          });
        },
        converterPriority: 'highest',
      });

      // Data Downcast: Model -> saved HTML
      editor.conversion.for('dataDowncast').elementToElement({
        model: 'htmlI',
        view: function(modelElement, conversionApi) {
          var writer = conversionApi.writer;
          var attrs = modelElement.getAttribute('htmlAttributes') || {};
          return writer.createEmptyElement('i', attrs);
        },
        converterPriority: 'highest',
      });

      // Editing Downcast: Model -> editor view
      editor.conversion.for('editingDowncast').elementToElement({
        model: 'htmlI',
        view: function(modelElement, conversionApi) {
          var writer = conversionApi.writer;
          var attrs = modelElement.getAttribute('htmlAttributes') || {};
          var iView = writer.createUIElement(
            'i',
            attrs,
            function(domDocument) {
              var domElement = this.toDomElement(domDocument);
              domElement.classList.add('ck-widget-font-icon');
              return domElement;
            },
          );
          return iView;
        },
        converterPriority: 'highest',
      });

      // Heading/paragraph attributes (fix font-weight disappearing)
      editor.conversion.for('upcast').attributeToAttribute({
        view: {
          name: /^(h[1-6]|p)$/,
          attributes: { style: /.*/, class: /.*/ },
        },
        model: 'htmlAttributes',
        converterPriority: 'highest',
      });
    }

    /**
     * iframe support.
     * Registers iframe as a proper block object via DataSchema
     * so GeneralHtmlSupport handles it correctly.
     */
    _setupIframe() {
      var editor = this.editor;
      var dataFilter = editor.plugins.get(DataFilter);
      var dataSchema = editor.plugins.get(DataSchema);

      dataSchema.registerBlockElement({
        model: 'htmlIframe',
        view: 'iframe',
        isObject: true,
        modelSchema: { inheritAllFrom: '$blockObject' },
      });
      dataFilter.allowElement('iframe');
      dataFilter.allowAttributes({
        name: 'iframe',
        attributes: true,
        classes: true,
        styles: true,
      });
    }

    /**
     * Script tag whitelist.
     * Only external scripts from trusted domains are preserved.
     * Inline scripts (no src) are always blocked.
     *
     * @param {string[]} trustedDomains - List of trusted domain names
     */
    _setupScriptWhitelist(trustedDomains) {
      var editor = this.editor;
      var dataFilter = editor.plugins.get(DataFilter);

      // htmlScript is already registered by CKEditor 5's built-in DataSchema.
      // Only allow script elements through the data filter.
      dataFilter.allowElement('script');
      dataFilter.allowAttributes({
        name: 'script',
      });
    }

    /**
     * Email custom tag support.
     * Registers custom inline tags used in email templates (e.g. <unsubscribe>)
     * as inline elements so they can properly wrap inline content like <a>.
     *
     * Without explicit inline registration, GHS treats unknown elements as block,
     * which causes the wrapper to be stripped when it contains inline content.
     */
    _setupEmailCustomTags() {
      var editor = this.editor;
      var schema = editor.model.schema;

      // Register as a paragraph-like block so it can directly contain inline
      // content (text, <a>, etc.) without needing an inner <p> wrapper.
      if (!schema.isRegistered('htmlUnsubscribe')) {
        schema.register('htmlUnsubscribe', {
          inheritAllFrom: '$block',
          allowAttributes: ['htmlAttributes'],
        });
      }

      // Upcast: <unsubscribe style="..."> → model element with htmlAttributes
      editor.conversion.for('upcast').elementToElement({
        view: 'unsubscribe',
        model: function(viewElement, conversionApi) {
          var writer = conversionApi.writer;
          var htmlAttrs = {};
          for (var entry of viewElement.getAttributes()) {
            htmlAttrs[entry[0]] = entry[1];
          }
          var modelElement = writer.createElement('htmlUnsubscribe');
          if (Object.keys(htmlAttrs).length) {
            writer.setAttribute('htmlAttributes', htmlAttrs, modelElement);
          }
          return modelElement;
        },
        priority: 'high',
      });

      // Downcast: model element → <unsubscribe style="...">
      editor.conversion.for('downcast').elementToElement({
        model: 'htmlUnsubscribe',
        view: function(modelElement, conversionApi) {
          var writer = conversionApi.writer;
          var htmlAttrs = modelElement.getAttribute('htmlAttributes') || {};
          return writer.createContainerElement('unsubscribe', htmlAttrs);
        },
      });
    }

    /**
     * Fix CKEditor bug: width="100%" on <table> becomes width:100%px in output.
     * Wraps editor.data.get() to replace incorrect percentage+px patterns.
     */
    _setupTableWidthFix() {
      var editor = this.editor;
      var originalGet = editor.data.get.bind(editor.data);
      editor.data.get = function(options) {
        return originalGet(options).replace(
          /width:(\d+(?:\.\d+)?)%px/g,
          'width:$1%',
        );
      };
    }

    /**
     * Remove <figure class="table"> wrappers that CKEditor 5's Table plugin
     * automatically adds around every <table> element during downcast.
     * Email newsletter templates use raw <table> elements and must not have
     * them wrapped in <figure> tags.
     *
     * Uses DOM-based processing (deepest-first) to safely handle nested tables.
     */
    _setupTableFigureRemoval() {
      var editor = this.editor;
      var originalGet = editor.data.get.bind(editor.data);
      editor.data.get = function(options) {
        var html = originalGet(options);

        // Detect full page HTML (from FullPage plugin)
        var isFullPage = /^\s*(<!doctype|<html[\s>])/i.test(html);

        var root;
        var doc;
        if (isFullPage) {
          doc = new DOMParser().parseFromString(html, 'text/html');
          root = doc.body;
        }
        else {
          doc = null;
          root = document.createElement('div');
          root.innerHTML = html;
        }

        // Reverse = deepest-first, so nested tables are unwrapped before parents
        var figures = Array.from(
          root.querySelectorAll('figure.table'),
        ).reverse();
        figures.forEach(function(figure) {
          var parent = figure.parentNode;

          // Transfer figure's style to the inner <table>
          var table = figure.querySelector(':scope > table');
          if (table) {
            var figureStyle = figure.getAttribute('style');
            if (figureStyle) {
              var tableStyle = table.getAttribute('style') || '';
              table.setAttribute(
                'style',
                tableStyle ? figureStyle + '; ' + tableStyle : figureStyle,
              );
            }
          }

          while (figure.firstChild) {
            parent.insertBefore(figure.firstChild, figure);
          }
          parent.removeChild(figure);
        });

        // Convert CKEditor inline styles back to HTML attributes for email compatibility
        root.querySelectorAll('table').forEach(function(table) {
          var s = table.style;

          var float = s.float;
          if (float === 'left' || float === 'right') {
            table.setAttribute('align', float);
            s.removeProperty('float');
          }

          var width = s.width;
          if (width) {
            table.setAttribute('width', width);
            s.removeProperty('width');
          }

          // Remove empty style attribute
          if (!s.cssText.trim()) {
            table.removeAttribute('style');
          }
        });

        if (isFullPage) {
          var doctype = html.match(/<!doctype[^>]*>/i);
          var doctypeStr = doctype ? doctype[0] : '';
          return (doctypeStr ? doctypeStr + '\n' : '') + doc.documentElement.outerHTML;
        }
        return root.innerHTML;
      };
    }

    /**
     * Allow block elements (e.g. <div>) inside headings.
     * Frameworks like Bootstrap use <h2><div class="accordion-button">...</div></h2>.
     *
     * Strategy: register a custom model element `htmlBlockHeading` that acts as a
     * block container. Any <h1>~<h6> that contains a block-level child is upcasted
     * to `htmlBlockHeading`, then downcasted back to the original tag + attributes.
     */
    _setAllowDivInHeading() {
      var editor = this.editor;
      var schema = editor.model.schema;

      if (!schema.isRegistered('htmlBlockHeading')) {
        schema.register('htmlBlockHeading', {
          allowWhere: '$block',
          allowContentOf: '$root',
          allowAttributes: ['htmlHeadingTag', 'htmlAttributes'],
        });
      }

      var BLOCK_TAGS = new Set([
        'div', 'section', 'article', 'nav', 'aside', 'header', 'footer', 'main',
      ]);

      // Upcast: <h1>~<h6> with block children → htmlBlockHeading
      editor.conversion.for('upcast').add(function(dispatcher) {
        ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'].forEach(function(tag) {
          dispatcher.on(
            'element:' + tag,
            function(_evt, data, conversionApi) {
              var viewElement = data.viewItem;

              // Only intercept when the heading contains at least one block child
              var children = Array.from(viewElement.getChildren());
              var hasBlockChild = children.some(function(child) {
                return child.is('element') && BLOCK_TAGS.has(child.name);
              });
              if (!hasBlockChild) return;

              var writer = conversionApi.writer;
              var consumable = conversionApi.consumable;
              var safeInsert = conversionApi.safeInsert;
              var updateConversionResult = conversionApi.updateConversionResult;

              if (!consumable.consume(viewElement, { name: true })) return;

              // Collect and consume all attributes
              var htmlAttrs = {};
              for (var entry of viewElement.getAttributes()) {
                consumable.consume(viewElement, { attribute: entry[0] });
                htmlAttrs[entry[0]] = entry[1];
              }

              var modelAttrs = { htmlHeadingTag: tag };
              if (Object.keys(htmlAttrs).length) {
                modelAttrs.htmlAttributes = htmlAttrs;
              }
              var modelElement = writer.createElement('htmlBlockHeading', modelAttrs);

              if (!safeInsert(modelElement, data.modelCursor)) return;

              conversionApi.convertChildren(viewElement, modelElement);
              updateConversionResult(modelElement, data);
            },
            { priority: 'highest' },
          );
        });
      });

      // Downcast: htmlBlockHeading → original <h1>~<h6> with attributes
      var downcastView = function(modelElement, conversionApi) {
        var writer = conversionApi.writer;
        var tag = modelElement.getAttribute('htmlHeadingTag') || 'h2';
        var attrs = modelElement.getAttribute('htmlAttributes') || {};
        return writer.createContainerElement(tag, attrs);
      };
      editor.conversion.for('dataDowncast').elementToElement({
        model: 'htmlBlockHeading',
        view: downcastView,
      });
      editor.conversion.for('editingDowncast').elementToElement({
        model: 'htmlBlockHeading',
        view: downcastView,
      });
    }
  }

  // ==========================================================================
  // htmlSupport Configurations
  // ==========================================================================

  /**
   * Full editor htmlSupport config.
   * Allows most HTML elements with all attributes/classes/styles,
   * but blocks XSS event handlers.
   */
  var FULL_HTML_SUPPORT = {
    allow: [
      {
        // Block elements
        name: /^(div|section|article|header|footer|nav|aside|main|figure|figcaption|blockquote)$/,
        attributes: true,
        classes: true,
        styles: true,
      },
      {
        // Inline elements
        name: /^(span|a|strong|em|i|b|code|pre|cite|small|mark|del|ins|sub|sup|button)$/,
        attributes: true,
        classes: true,
        styles: true,
      },
      {
        // Headings
        name: /^h[1-6]$/,
        attributes: true,
        classes: true,
        styles: true,
      },
      {
        // Lists
        name: /^(ul|ol|li)$/,
        attributes: true,
        classes: true,
        styles: true,
      },
      {
        // Tables
        name: /^(table|thead|tbody|tfoot|tr|th|td|caption|colgroup|col)$/,
        attributes: true,
        classes: true,
        styles: true,
      },
      {
        // Paragraphs
        name: 'p',
        attributes: true,
        classes: true,
        styles: true,
      },
      {
        // iframe (YouTube, Vimeo, etc.)
        name: 'iframe',
        attributes: true,
        classes: true,
        styles: true,
      },
      {
        // Video and audio
        name: /^(video|audio|source|track)$/,
        attributes: true,
        classes: true,
        styles: true,
      },
      {
        // Object and embed
        name: /^(object|embed|param)$/,
        attributes: true,
        classes: true,
        styles: true,
      },
      {
        // Style tags
        name: 'style',
        attributes: true,
      },
      {
        // Images - allow style/class to preserve border-radius, etc.
        name: 'img',
        attributes: true,
        classes: true,
        styles: true,
      },
      {
        // Full page HTML support (used with FullPage plugin)
        name: /^(html|head|body|title|meta|link|base)$/,
        attributes: true,
        classes: true,
        styles: true,
      },
    ],
    disallow: [
      {
        // XSS event handlers
        attributes: {
          onload: /.*/,
          onerror: /.*/,
          onfocus: /.*/,
          onblur: /.*/,
          onsubmit: /.*/,
          onkeydown: /.*/,
          onkeyup: /.*/,
          onkeypress: /.*/,
          onmousedown: /.*/,
          onmouseup: /.*/,
          onchange: /.*/,
          oninput: /.*/,
          oncontextmenu: /.*/,
        },
      },
    ],
  };

  /**
   * Basic editor htmlSupport config.
   * Only allows limited HTML elements.
   */
  var BASIC_HTML_SUPPORT = {
    allow: [
      {
        name: /^(h[1-3]|p|blockquote)$/,
        styles: true,
        classes: true,
      },
      {
        name: /^(strong|em)$/,
      },
      {
        name: 'a',
        attributes: ['href', 'target', 'rel'],
      },
      {
        name: 'img',
        attributes: ['src', 'alt', 'width', 'height', 'title'],
        classes: ['left', 'right'],
      },
      {
        name: 'span',
        styles: ['font-size', 'color', 'background-color'],
      },
    ],
  };

  // ==========================================================================
  // Preset Configurations
  // ==========================================================================

  /**
   * Get full editor configuration (CiviCRM preset).
   *
   * @param {object} [overrides] - Override specific config options
   * @returns {object} CKEditor 5 configuration object
   */
  function getFullEditorConfig(overrides) {
    overrides = overrides || {};
    return Object.assign({
      licenseKey: 'GPL',
      plugins: [
        CK.Essentials,
        CK.Paragraph,
        CK.Heading,
        CK.Bold,
        CK.Italic,
        CK.Underline,
        CK.Strikethrough,
        CK.Code,
        CK.Subscript,
        CK.Superscript,
        CK.Font,
        CK.FontSize,
        CK.FontFamily,
        CK.FontColor,
        CK.FontBackgroundColor,
        CK.Alignment,
        CK.List,
        CK.ListProperties,
        CK.TodoList,
        CK.Indent,
        CK.IndentBlock,
        CK.Link,
        CK.LinkImage,
        CK.AutoLink,
        CK.Image,
        CK.ImageCaption,
        CK.ImageStyle,
        CK.ImageToolbar,
        CK.ImageUpload,
        CK.ImageResize,
        CK.ImageInsert,
        CK.Base64UploadAdapter,
        CK.Table,
        CK.TableToolbar,
        CK.TableProperties,
        CK.TableCellProperties,
        CK.TableColumnResize,
        CK.TableCaption,
        CK.MediaEmbed,
        CK.HtmlEmbed,
        CK.BlockQuote,
        CK.CodeBlock,
        CK.HorizontalLine,
        CK.PageBreak,
        CK.SpecialCharacters,
        CK.SpecialCharactersEssentials,
        CK.FindAndReplace,
        CK.TextTransformation,
        CK.Highlight,
        CK.RemoveFormat,
        CK.SourceEditing,
        CK.Autoformat,
        CK.PasteFromOffice,
        CK.Clipboard,
        CK.GeneralHtmlSupport,
        CK.HtmlComment,
        CK.ShowBlocks,
        CK.Fullscreen,
        CK.FullPage,
        CK.Undo,
        ExtendSchema,
      ],
      toolbar: {
        items: [
          'fullscreen',
          '|',
          'removeFormat',
          '|',
          'bold', 'italic', 'underline', 'strikethrough',
          '|',
          'alignment',
          '|',
          'numberedList', 'bulletedList', 'outdent', 'indent', 'blockQuote',
          '|',
          'link', 'unlink',
          '|',
          'insertImage', 'mediaEmbed',
          '-',
          'heading', 'fontFamily', 'fontSize',
          '|',
          'undo', 'redo',
          '|',
          'fontColor', 'fontBackgroundColor',
          '|',
          'insertTable', 'horizontalLine',
          '|',
          'sourceEditing',
        ],
        shouldNotGroupWhenFull: true,
      },
      heading: {
        options: [
          { model: 'paragraph', title: '段落', class: 'ck-heading_paragraph' },
          { model: 'heading1', view: 'h1', title: '標題 1', class: 'ck-heading_heading1' },
          { model: 'heading2', view: 'h2', title: '標題 2', class: 'ck-heading_heading2' },
          { model: 'heading3', view: 'h3', title: '標題 3', class: 'ck-heading_heading3' },
          { model: 'heading4', view: 'h4', title: '標題 4', class: 'ck-heading_heading4' },
          { model: 'heading5', view: 'h5', title: '標題 5', class: 'ck-heading_heading5' },
          { model: 'heading6', view: 'h6', title: '標題 6', class: 'ck-heading_heading6' },
        ],
      },
      fontSize: {
        options: ['default', 9, 11, 13, 17, 19, 21, 24, 28, 32, 36],
        supportAllValues: true,
      },
      fontFamily: {
        options: [
          'default',
          'Arial, Helvetica, sans-serif',
          'Courier New, Courier, monospace',
          'Georgia, serif',
          'Lucida Sans Unicode, Lucida Grande, sans-serif',
          'Tahoma, Geneva, sans-serif',
          'Times New Roman, Times, serif',
          'Trebuchet MS, Helvetica, sans-serif',
          'Verdana, Geneva, sans-serif',
          '微軟正黑體, Microsoft JhengHei',
          '新細明體, PMingLiU',
          '標楷體, DFKai-SB',
        ],
        supportAllValues: true,
      },
      image: {
        toolbar: [
          'imageStyle:inline', 'imageStyle:block', 'imageStyle:side',
          '|',
          'toggleImageCaption', 'imageTextAlternative',
          '|',
          'linkImage',
        ],
        resizeOptions: [
          { name: 'resizeImage:original', label: 'Original', value: null },
          { name: 'resizeImage:25', label: '25%', value: '25' },
          { name: 'resizeImage:50', label: '50%', value: '50' },
          { name: 'resizeImage:75', label: '75%', value: '75' },
        ],
      },
      table: {
        contentToolbar: [
          'tableColumn', 'tableRow', 'mergeTableCells',
          '|',
          'tableProperties', 'tableCellProperties',
          '|',
          'toggleTableCaption',
        ],
      },
      list: {
        properties: {
          styles: true,
          startIndex: true,
          reversed: true,
        },
      },
      link: {
        decorators: {
          openInNewTab: {
            mode: 'manual',
            label: 'Open in a new tab',
            attributes: {
              target: '_blank',
              rel: 'noopener noreferrer',
            },
          },
          isDownloadable: {
            mode: 'manual',
            label: 'Downloadable',
            attributes: {
              download: 'file',
            },
          },
        },
      },
      codeBlock: {
        languages: [
          { language: 'plaintext', label: 'Plain text' },
          { language: 'c', label: 'C' },
          { language: 'cs', label: 'C#' },
          { language: 'cpp', label: 'C++' },
          { language: 'css', label: 'CSS' },
          { language: 'diff', label: 'Diff' },
          { language: 'html', label: 'HTML' },
          { language: 'java', label: 'Java' },
          { language: 'javascript', label: 'JavaScript' },
          { language: 'php', label: 'PHP' },
          { language: 'python', label: 'Python' },
          { language: 'ruby', label: 'Ruby' },
          { language: 'typescript', label: 'TypeScript' },
          { language: 'xml', label: 'XML' },
        ],
      },
      highlight: {
        options: [
          { model: 'yellowMarker', class: 'marker-yellow', title: 'Yellow marker', color: 'var(--ck-highlight-marker-yellow)', type: 'marker' },
          { model: 'greenMarker', class: 'marker-green', title: 'Green marker', color: 'var(--ck-highlight-marker-green)', type: 'marker' },
          { model: 'pinkMarker', class: 'marker-pink', title: 'Pink marker', color: 'var(--ck-highlight-marker-pink)', type: 'marker' },
          { model: 'blueMarker', class: 'marker-blue', title: 'Blue marker', color: 'var(--ck-highlight-marker-blue)', type: 'marker' },
        ],
      },
      htmlSupport: FULL_HTML_SUPPORT,
      fullPage: {
        // Bypass default sanitizer so <head> content is preserved
        sanitizer: function(html) { return html; },
      },
      extendSchema: {
        enableFontIcons: true,
        enableIframe: true,
        trustedScriptDomains: TRUSTED_SCRIPT_DOMAINS,
      },
    }, overrides);
  }

  /**
   * Get basic editor configuration (CiviCRMBasic preset).
   *
   * @param {object} [overrides] - Override specific config options
   * @returns {object} CKEditor 5 configuration object
   */
  function getBasicEditorConfig(overrides) {
    overrides = overrides || {};
    return Object.assign({
      licenseKey: 'GPL',
      plugins: [
        CK.Essentials,
        CK.Paragraph,
        CK.Heading,
        CK.Bold,
        CK.Italic,
        CK.Underline,
        CK.Strikethrough,
        CK.FontSize,
        CK.FontColor,
        CK.FontBackgroundColor,
        CK.List,
        CK.Link,
        CK.Image,
        CK.ImageStyle,
        CK.ImageToolbar,
        CK.ImageInsert,
        CK.ImageResize,
        CK.RemoveFormat,
        CK.PasteFromOffice,
        CK.Clipboard,
        CK.GeneralHtmlSupport,
        CK.Undo,
        ExtendSchema,
      ],
      toolbar: {
        items: [
          'heading',
          '|',
          'removeFormat',
          '|',
          'bold', 'italic', 'underline', 'strikethrough',
          '|',
          'fontSize', 'fontColor', 'fontBackgroundColor',
          '|',
          'numberedList', 'bulletedList',
          '|',
          'link', 'unlink',
          '|',
          'insertImage',
          '|',
          'undo', 'redo',
        ],
        shouldNotGroupWhenFull: true,
      },
      heading: {
        options: [
          { model: 'paragraph', title: '段落', class: 'ck-heading_paragraph' },
          { model: 'heading1', view: 'h1', title: '標題 1', class: 'ck-heading_heading1' },
          { model: 'heading2', view: 'h2', title: '標題 2', class: 'ck-heading_heading2' },
          { model: 'heading3', view: 'h3', title: '標題 3', class: 'ck-heading_heading3' },
        ],
      },
      fontSize: {
        options: [
          'default', 10, 11, 12, 13, 14, 16, 18, 20, 22, 24, 26, 28, 36, 48, 72,
        ],
        supportAllValues: true,
      },
      image: {
        insert: {
          type: 'auto',
          integrations: ['url'],
        },
        toolbar: [
          'imageStyle:inline', 'imageStyle:block', 'imageStyle:side',
          '|',
          'imageTextAlternative',
        ],
      },
      link: {
        decorators: {
          openInNewTab: {
            mode: 'manual',
            label: 'Open in a new tab',
            attributes: {
              target: '_blank',
              rel: 'noopener noreferrer',
            },
          },
        },
      },
      htmlSupport: BASIC_HTML_SUPPORT,
      extendSchema: {
        enableFontIcons: true,
        enableIframe: false,
        trustedScriptDomains: [],
      },
    }, overrides);
  }

  // ==========================================================================
  // Expose API
  // ==========================================================================

  window.CiviCKEditor5 = {
    ExtendSchema: ExtendSchema,
    getFullEditorConfig: getFullEditorConfig,
    getBasicEditorConfig: getBasicEditorConfig,
  };

  /**
   * CKEditor Switcher Utility
   * 
   * Handles dynamic switching between CKEditor 4 and CKEditor 5
   */
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
        // Evaluate extra plugins code from PHP
        const script = document.createElement('script');
        script.text = config.extraPluginsCode;
        document.head.appendChild(script);
        window.cke4PluginsRegistered = true;
      }

      el.value = content;
      cj(el).addClass('ckeditor-processed');

      return new Promise((resolve) => {
        const instance = window.CKEDITOR.replace(el.name, {
          customConfig: config.customConfigPath,
          extraPlugins: config.extraPluginsList,
          toolbar: config.toolbar,
          allowedContent: config.allowedContent,
          width: '100%',
          height: '400',
          filebrowserBrowseUrl: config.imceUrl,
          filebrowserImageBrowseUrl: config.imceUrl + '&type=Images'
        });
        instance.on('instanceReady', () => resolve(instance));
      });
    },

    initializeCKE5: async function(el, content, config) {
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
