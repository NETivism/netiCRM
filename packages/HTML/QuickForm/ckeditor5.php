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

    // Load switcher logic
    if (empty($GLOBALS['editor_switcher_loaded'])) {
      $html .= '<script type="text/javascript" src="' . $config->resourceBase . 'packages/ckeditor5/editor-switcher.js?' . $config->ver . '"></script>' . "\n";
      $GLOBALS['editor_switcher_loaded'] = TRUE;
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

    // Add Switcher UI
    $systemEditorId = CRM_Core_BAO_Preferences::value('editor_id');
    $isCke5Default = ($systemEditorId == 4 || (is_array($systemEditorId) && in_array(4, $systemEditorId)));
    
    // CKE4 config needs some logic to match ckeditor.php
    $plugins = array('widget', 'lineutils', 'mediaembed', 'tableresize', 'image2');
    if (CRM_Core_Permission::check('access CiviCRM') || CRM_Core_Permission::check('paste and upload images')) {
      $plugins[] = 'clipboard_image';
    }
    $extraPluginsCode = [];
    foreach($plugins as $pname){
      $extraPluginsCode[] = "CKEDITOR.plugins.addExternal('{$pname}', '{$config->resourceBase}packages/ckeditor/extraplugins/{$pname}/', 'plugin.js');";
    }
    $toolbar = CRM_Core_Permission::check('access CiviCRM') ? 'CiviCRM' : 'CiviCRMBasic';
    $allowedContent = CRM_Core_Permission::check('access CiviCRM') ? 'true' : "'h1 h2 h3 p blockquote; strong em; a[!href]; img(left,right)[!src,alt,width,height,title]; span{font-size,color,background-color}'";

    $cke4Config = array(
      'resourceBase' => $config->resourceBase,
      'ver' => $config->ver,
      'plugins' => $plugins,
      'extraPluginsCode' => implode("\n", $extraPluginsCode),
      'extraPluginsList' => implode(',', $plugins),
      'toolbar' => $toolbar,
      'allowedContent' => $allowedContent,
      'customConfigPath' => $config->resourceBase . 'js/ckeditor.config.js',
      'imceEnabled' => CRM_Utils_System::moduleExists('imce'),
      'imceUrl' => CRM_Utils_System::moduleExists('imce') ? CRM_Utils_System::url('imce') : ''
    );

    $switcherHtml = '
    <div class="crm-section editor-switcher-container" style="margin-top: 5px; margin-bottom: 5px; padding: 5px; background: #f0f0f0; border: 1px solid #ccc; border-radius: 4px; display: flex; align-items: center; gap: 10px;">
      <span style="font-size: 11px; font-weight: bold; color: #444;">' . ($isCke5Default ? '退回模式' : '試用模式') . ':</span>
      <select class="editor-format-switcher" onchange="CiviEditorSwitcher.switch(this.value, \'' . $name . '\', ' . htmlspecialchars(json_encode($cke4Config)) . ')" style="padding: 2px 4px; font-size: 11px; height: 24px; min-width: 140px;">
        <option value="cke4">CKEditor 4 (傳統)</option>
        <option value="cke5" selected>CKEditor 5 (新版)</option>
      </select>
      <span class="editor-switch-status" style="font-size: 11px; color: #666;"></span>
    </div>';

    return $switcherHtml . $html;
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
