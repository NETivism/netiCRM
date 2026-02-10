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
   * Initialize CKEditor 5 on the textarea element
   *
   * Loads CKEditor 5 from local files (packages/ckeditor5/) and initializes
   * ClassicEditor with configured plugins and toolbar.
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

    // Render textarea element
    $html = parent::toHtml();

    // Load CKEditor 5 assets only once (similar to CKEditor 4 pattern)
    if (empty($GLOBALS['civicrm_ckeditor5_assets'])) {
      $html .= "\n" . '<link rel="stylesheet" href="' . $config->resourceBase . 'packages/ckeditor5/ckeditor5.css?' . $config->ver . '">' . "\n";
      $html .= '<script type="text/javascript" src="' . $config->resourceBase . 'packages/ckeditor5/ckeditor5.umd.js?' . $config->ver . '"></script>' . "\n";
      $GLOBALS['civicrm_ckeditor5_assets'] = TRUE;
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

  // Destructure required classes from CKEDITOR global
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
    Undo
  } = CKEDITOR;

  // Initialize CKEditor 5
  ClassicEditor
    .create(element, {
      licenseKey: 'GPL',  // Use GPL license for open source projects
      plugins: [
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
        Undo
      ],
      toolbar: [
        'undo', 'redo', '|',
        'heading', '|',
        'bold', 'italic', 'underline', 'strikethrough', '|',
        'link', '|',
        'bulletedList', 'numberedList', '|',
        'alignment', '|',
        'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
        'removeFormat', '|',
        'sourceEditing'
      ],
      height: '{$this->height}px'
    })
    .then(editor => {
      console.log('CKEditor 5 initialized successfully for element:', element.id || element.name);
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
