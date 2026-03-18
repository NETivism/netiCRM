<?php

require_once('HTML/QuickForm/textarea.php');

/**
 * HTML Quickform element for CKEditor 5
 *
 * CKEditor 5 is a modern WYSIWYG editor with modular architecture.
 * This implementation uses self-hosted files from packages/ckeditor5/
 * following the same pattern as CKEditor 4 for consistency.
 *
 * Installation: CKEditor 5 files are installed locally in packages/ckeditor5/
 * Updates: Download new version and replace files in packages/ckeditor5/
 *
 * @author       netiCRM Development Team
 * @access       public
 */
class HTML_QuickForm_CKEditor5 extends HTML_QuickForm_textarea
{
  /**
   * The width of the editor in pixels or percent
   *
   * @var string
   * @access public
   */
  var $width = '100%';

  /**
   * The height of the editor in pixels or percent
   *
   * @var string
   * @access public
   */
  var $height = '400';

  /**
   * Class constructor
   *
   * @param   string  Element name
   * @param   string  Element label
   * @param   array   Attributes for the textarea
   * @param   array   Config options (not used in test version)
   * @access  public
   * @return  void
   */
  function __construct($elementName=null, $elementLabel=null, $attributes=null, $options=array())
  {
    parent::__construct($elementName, $elementLabel, $attributes);
    $this->_persistantFreeze = true;
    $this->_type = 'CKEditor5';

    // Set smaller height if schema defines rows as 4 or less
    if (is_array($attributes) && array_key_exists('rows', $attributes) && $attributes['rows'] <= 4) {
      $this->height = 175;
    }
  }

  /**
   * Build htmlSupport config for Full Editor (CiviCRM preset).
   *
   * Allows most HTML elements but blocks XSS event handlers.
   * Source: ckeditor5-config.js FULL_HTML_SUPPORT
   *
   * @param bool $fullPage Whether to include fullpage elements
   * @return string JavaScript object literal for htmlSupport config
   */
  function getFullHtmlSupport($fullPage = FALSE) {
    $allowRules = "
        {
          // Block elements
          name: /^(div|section|article|header|footer|nav|aside|main|figure|figcaption|blockquote)$/,
          attributes: true,
          classes: true,
          styles: true
        },
        {
          // Inline elements
          name: /^(span|a|strong|em|i|b|code|pre|cite|small|mark|del|ins|sub|sup|button)$/,
          attributes: true,
          classes: true,
          styles: true
        },
        {
          // Headings
          name: /^h[1-6]$/,
          attributes: true,
          classes: true,
          styles: true
        },
        {
          // Lists
          name: /^(ul|ol|li)$/,
          attributes: true,
          classes: true,
          styles: true
        },
        {
          // Tables
          name: /^(table|thead|tbody|tfoot|tr|th|td|caption|colgroup|col)$/,
          attributes: true,
          classes: true,
          styles: true
        },
        {
          // Paragraphs
          name: 'p',
          attributes: true,
          classes: true,
          styles: true
        },
        {
          // Video and audio
          name: /^(video|audio|source|track)$/,
          attributes: true,
          classes: true,
          styles: true
        },
        {
          // Object and embed
          name: /^(object|embed|param)$/,
          attributes: true,
          classes: true,
          styles: true
        },
        {
          // Style tags
          name: 'style',
          attributes: true
        }";

    // FullPage mode: allow html/head/body/title/meta/link
    if ($fullPage) {
      $allowRules .= ",
        {
          name: /^(html|head|body|title|meta|link)$/,
          attributes: true,
          classes: true,
          styles: true
        }";
    }

    return "{
      allow: [" . $allowRules . "
      ],
      disallow: [
        {
          // XSS event handlers
          attributes: {
            onclick: /.*/,
            onload: /.*/,
            onerror: /.*/,
            onmouseover: /.*/,
            onfocus: /.*/,
            onblur: /.*/,
            onsubmit: /.*/,
            onkeydown: /.*/,
            onkeyup: /.*/,
            onkeypress: /.*/,
            onmousedown: /.*/,
            onmouseup: /.*/,
            onmouseout: /.*/,
            onchange: /.*/,
            oninput: /.*/,
            oncontextmenu: /.*/
          }
        }
      ]
    }";
  }

  /**
   * Build htmlSupport config for Basic Editor (CiviCRMBasic preset).
   *
   * Only allows limited HTML elements.
   * Source: ckeditor5-config.js BASIC_HTML_SUPPORT
   *
   * @return string JavaScript object literal for htmlSupport config
   */
  function getBasicHtmlSupport() {
    return "{
      allow: [
        {
          name: /^(h[1-3]|p|blockquote)$/,
          styles: true,
          classes: true
        },
        {
          name: /^(strong|em)$/
        },
        {
          name: 'a',
          attributes: ['href', 'target', 'rel']
        },
        {
          name: 'img',
          attributes: ['src', 'alt', 'width', 'height', 'title'],
          classes: ['left', 'right']
        },
        {
          name: 'span',
          styles: ['font-size', 'color', 'background-color']
        }
      ]
    }";
  }

  /**
   * Initialize CKEditor 5 on the textarea element
   *
   * Loads CKEditor 5 from local files (packages/ckeditor5/) and initializes
   * ClassicEditor with configured plugins and toolbar.
   * Uses permission-based preset system matching CKEditor 4 behavior.
   *
   * @access public
   * @return string HTML output
   */
  function toHtml()
  {
    if ($this->_flagFrozen) {
      return $this->getFrozenHtml();
    }

    $config = CRM_Core_Config::singleton();
    $name = $this->getAttribute('name');
    $elementId = $this->getAttribute('id');
    $fullPage = $this->getAttribute('fullpage');

    // Determine preset based on permission (same logic as CKEditor 4)
    $isFullEditor = CRM_Core_Permission::check('access CiviCRM');
    $htmlSupportConfig = $isFullEditor
      ? $this->getFullHtmlSupport($fullPage)
      : $this->getBasicHtmlSupport();

    // Build plugins list
    $plugins = "
        Essentials,
        Bold,
        Italic,
        Underline,
        Strikethrough,
        Paragraph,
        Heading,
        Link,
        List,
        Alignment,
        Font,
        RemoveFormat,
        SourceEditing,
        GeneralHtmlSupport,
        Undo";

    // FullPage plugin only for full editor with fullpage attribute
    if ($isFullEditor && $fullPage) {
      $plugins .= ",
        FullPage";
    }

    // Build toolbar based on preset
    if ($isFullEditor) {
      $toolbar = "
        'undo', 'redo', '|',
        'heading', '|',
        'bold', 'italic', 'underline', 'strikethrough', '|',
        'link', '|',
        'bulletedList', 'numberedList', '|',
        'alignment', '|',
        'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
        'removeFormat', '|',
        'sourceEditing'";
    }
    else {
      $toolbar = "
        'heading', '|',
        'removeFormat', '|',
        'bold', 'italic', 'underline', 'strikethrough', '|',
        'fontSize', 'fontColor', 'fontBackgroundColor', '|',
        'numberedList', 'bulletedList', '|',
        'link', 'unlink', '|',
        'undo', 'redo'";
    }

    // Render textarea element
    $html = parent::toHtml();

    $html .= "\n" . '<link rel="stylesheet" href="' . $config->resourceBase . 'packages/ckeditor5/ckeditor5.css?' . $config->ver . '">' . "\n";

    // Load script only once to avoid conflicts when switching editors
    if (empty($GLOBALS['ckeditor5_script_loaded'])) {
      $html .= '<script type="text/javascript" src="' . $config->resourceBase . 'packages/ckeditor5/ckeditor5.umd.js?' . $config->ver . '"></script>' . "\n";

      // Immediately swap namespace after loading to avoid conflicts
      $html .= '<script type="text/javascript">
if (window.CKEDITOR && !window.CKEDITOR_5) {
  window.CKEDITOR_5 = window.CKEDITOR;
  window.CKEDITOR = undefined;
  console.log("CKE5 namespace swap complete: window.CKEDITOR_5");
}
</script>' . "\n";

      $GLOBALS['ckeditor5_script_loaded'] = TRUE;
    }

    // Initialize CKEditor 5 ClassicEditor
    $html .= "<script type='text/javascript'>
cj(function() {
  // Get element by id (preferred) or name (fallback)
  var element = " . ($elementId ? "document.getElementById('{$elementId}')" : "document.getElementsByName('{$name}')[0]") . ";

  if (!element) {
    console.error('CKEditor 5: Element not found');
    return;
  }

  // Check if already processed
  if (cj(element).hasClass('ckeditor5-processed')) {
    return;
  }
  cj(element).addClass('ckeditor5-processed');

  // Destructure required classes from CKEDITOR_5 (to avoid conflict with CKEditor 4)
  const {
    ClassicEditor,
    Essentials,
    Bold,
    Italic,
    Underline,
    Strikethrough,
    Paragraph,
    Heading,
    Link,
    List,
    Alignment,
    Font,
    RemoveFormat,
    SourceEditing,
    FullPage,
    GeneralHtmlSupport,
    Undo
  } = window.CKEDITOR_5;

  // Initialize CKEditor 5
  ClassicEditor
    .create(element, {
      licenseKey: 'GPL',
      plugins: [{$plugins}
      ],
      toolbar: [{$toolbar}
      ],
      htmlSupport: {$htmlSupportConfig},
      height: '{$this->height}px'
    })
    .then(editor => {
      // Store instance reference for external access (e.g. editor switcher)
      var ckDiv = element.nextElementSibling;
      if (ckDiv && ckDiv.classList.contains('ck-editor')) {
        var editableDiv = ckDiv.querySelector('.ck-editor__editable');
        if (editableDiv) {
          editableDiv.ckeditorInstance = editor;
        }
      }
      // Prevent form navigation on key press
      editor.model.document.on('change:data', () => {
        global_formNavigate = false;
      });
    })
    .catch(error => {
      console.error('CKEditor 5 initialization failed:', error);
    });
});
</script>";

    return $html;
  }

  /**
   * Returns the textarea content in HTML when frozen
   *
   * @access public
   * @return string
   */
  function getFrozenHtml()
  {
    return $this->getValue();
  }
}

?>
