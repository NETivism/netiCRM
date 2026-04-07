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
   * the editor using config from ckeditor5-civicrm.js.
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

    // Determine preset based on permission (same logic as CKEditor 4)
    $isFullEditor = CRM_Core_Permission::check('access CiviCRM');
    $configMethod = $isFullEditor ? 'getFullEditorConfig' : 'getBasicEditorConfig';

    // Render textarea element
    $html = parent::toHtml();

    $html .= "\n" . '<link rel="stylesheet" href="' . $config->resourceBase . 'packages/ckeditor5/ckeditor5.css?' . $config->ver . '">' . "\n";

    // Load scripts only once to avoid conflicts when switching editors
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

      // Load CiviCRM-specific config (ExtendSchema plugin, presets)
      $html .= '<script type="text/javascript" src="' . $config->resourceBase . 'packages/ckeditor5/ckeditor5-civicrm.js?' . $config->ver . '"></script>' . "\n";

      $GLOBALS['ckeditor5_script_loaded'] = TRUE;
    }

    // Initialize CKEditor 5 using config from ckeditor5-civicrm.js
    $elementSelector = $elementId
      ? "document.getElementById('{$elementId}')"
      : "document.getElementsByName('{$name}')[0]";

    $html .= "<script type='text/javascript'>
cj(function() {
  var element = {$elementSelector};

  if (!element) {
    console.error('CKEditor 5: Element not found');
    return;
  }

  // Check if already processed
  if (cj(element).hasClass('ckeditor5-processed')) {
    return;
  }
  cj(element).addClass('ckeditor5-processed');

  var config = window.CiviCKEditor5.{$configMethod}();

  window.CKEDITOR_5.ClassicEditor
    .create(element, config)
    .then(function(editor) {
      // Store instance reference for external access (e.g. editor switcher)
      var ckDiv = element.nextElementSibling;
      if (ckDiv && ckDiv.classList.contains('ck-editor')) {
        var editableDiv = ckDiv.querySelector('.ck-editor__editable');
        if (editableDiv) {
          editableDiv.ckeditorInstance = editor;
        }
      }
      // Prevent form navigation on key press
      editor.model.document.on('change:data', function() {
        global_formNavigate = false;
      });
    })
    .catch(function(error) {
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
