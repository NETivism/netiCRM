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

    // System-wide opt-in: allow arbitrary HTML (incl. <script>) in the editor.
    // Default 0: sanitized output. Admins enable via Site Preferences > Display.
    $allowAllContent = (bool) CRM_Core_BAO_Preferences::value('editor_allow_all_content');

    // IMCE integration (Drupal-only). When the imce module is enabled,
    // the IMCEBrowse plugin in ckeditor5-civicrm.js adds a "Browse Server"
    // entry to the Image Insert dropdown.
    $imceEnabled = CRM_Utils_System::moduleExists('imce');
    $imceUrl = $imceEnabled ? CRM_Utils_System::url('imce') : '';

    // Clipboard image plugin (paste / drop image upload). Same
    // permission gate as the CKE4 plugin loader in ckeditor.php and
    // the access_arguments on civicrm/ajax/editor/image-upload.
    $clipboardImageAllowed = CRM_Core_Permission::check('access CiviCRM') ||
      CRM_Core_Permission::check('paste and upload images');
    $clipboardImageUrl = $clipboardImageAllowed
      ? CRM_Utils_System::url('civicrm/ajax/editor/image-upload', NULL, FALSE, NULL, FALSE)
      : '';

    $overrides = ['allowAllContent' => $allowAllContent];
    if ($imceEnabled && !empty($imceUrl)) {
      $overrides['imceEnabled'] = TRUE;
      $overrides['imceUrl'] = $imceUrl;
    }
    if ($clipboardImageAllowed && !empty($clipboardImageUrl)) {
      $overrides['clipboardImageEnabled'] = TRUE;
      $overrides['clipboardImageUrl'] = $clipboardImageUrl;
    }

    // Determine CKEditor 5 UI language from CiviCRM locale.
    // global $civicrm_root is the canonical CiviCRM filesystem root (with trailing slash).
    // Same pattern used in CRM/Core/Config.php:519 and CRM/Core/I18n.php:64.
    global $civicrm_root;
    $cke5Lang = self::getCKEditorLang($config->lcMessages);
    $hasTranslation = ($cke5Lang !== 'en') && file_exists($civicrm_root . 'packages/ckeditor5/translations/' . $cke5Lang . '.umd.js');
    if ($hasTranslation) {
      $overrides['language'] = $cke5Lang;
    }

    $overridesJson = json_encode($overrides);

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
      $html .= '<link rel="stylesheet" href="' . $config->resourceBase . 'packages/ckeditor5/ckeditor5-civicrm.css?' . $config->ver . '">' . "\n";
      $html .= '<script type="text/javascript" src="' . $config->resourceBase . 'packages/ckeditor5/ckeditor5-civicrm.js?' . $config->ver . '"></script>' . "\n";

      // Load translation file for current UI language (registers to window.CKEDITOR_TRANSLATIONS).
      if ($hasTranslation) {
        $html .= '<script type="text/javascript" src="' . $config->resourceBase . 'packages/ckeditor5/translations/' . $cke5Lang . '.umd.js?' . $config->ver . '"></script>' . "\n";
      }

      $GLOBALS['ckeditor5_script_loaded'] = TRUE;
    }

    // Load switcher logic
    if (empty($GLOBALS['editor_switcher_loaded'])) {
      $html .= '<link rel="stylesheet" href="' . $config->resourceBase . 'packages/ckeditor5/editor-switcher.css?' . $config->ver . '">' . "\n";
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

  // Defensive guard (ref #45339): if the libraries were not emitted on this
  // page (e.g. a discarded duplicate render consumed the once-only asset
  // guard), degrade gracefully to a plain textarea instead of throwing a
  // TypeError that would also abort other scripts on the page.
  if (typeof window.CiviCKEditor5 === 'undefined' || typeof window.CKEDITOR_5 === 'undefined') {
    console.error('CKEditor 5 libraries not loaded; leaving plain textarea for: ' + (element.id || element.name));
    return;
  }

  var config = window.CiviCKEditor5.{$configMethod}({$overridesJson});

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
   * Map CiviCRM locale code to CKEditor 5 language code.
   *
   * @param string $lcMessages CiviCRM locale (e.g. 'zh_TW', 'en_US')
   * @return string CKEditor 5 language code (e.g. 'zh', 'en')
   */
  public static function getCKEditorLang($lcMessages) {
    $map = [
      'zh_TW'   => 'zh',
      'zh_CN'   => 'zh-cn',
      'pt_BR'   => 'pt-br',
      'de_CH'   => 'de-ch',
      'en_AU'   => 'en-au',
      'en_GB'   => 'en-gb',
      'es_CO'   => 'es-co',
      'sr_Latn' => 'sr-latn',
    ];
    $lcMessages = $lcMessages ?: 'en_US';
    if (isset($map[$lcMessages])) {
      return $map[$lcMessages];
    }
    $lang = strtolower(substr($lcMessages, 0, 2));
    return $lang ?: 'en';
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
