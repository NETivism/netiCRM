<?php

require_once('HTML/QuickForm/textarea.php');

/**
 * HTML Quickform element for CKEditor 5 (Test Version)
 *
 * This is a minimal implementation to test QuickForm loading mechanism.
 * This file only validates that the QuickForm element can be loaded correctly
 * by the system. It does NOT integrate the actual CKEditor 5 library.
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
   * Loads CKEditor 5 via CDN and initializes ClassicEditor
   * with basic configuration.
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

    // Render textarea element
    $html = parent::toHtml();

    // Load CKEditor 5 CSS and JS only once
    if (empty($GLOBALS['civicrm_ckeditor5_assets'])) {
      $html .= "\n" . '<link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.css">' . "\n";
      $html .= '<script src="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.umd.js"></script>' . "\n";
      $GLOBALS['civicrm_ckeditor5_assets'] = TRUE;
    }

    // Initialize CKEditor 5 ClassicEditor
    $html .= "<script type='text/javascript'>
cj(function() {
  // Check if already processed
  if (cj('#{$name}').hasClass('ckeditor5-processed')) {
    return;
  }
  cj('#{$name}').addClass('ckeditor5-processed');

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
    .create(document.querySelector('#{$name}'), {
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
      console.log('CKEditor 5 initialized successfully for #{$name}');
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
