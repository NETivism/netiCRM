<?php

require_once('HTML/QuickForm/textarea.php');

/**
 * HTML Quickform element for CKeditor
 *
 * CKeditor is a WYSIWYG HTML editor which can be obtained from
 * http://ckeditor.com. I tried to resemble the integration instructions
 * as much as possible, so the examples from the docs should work with this one.
 * 
 * @author       Kurund Jalmi 
 * @access       public
 */
class HTML_QuickForm_CKeditor extends HTML_QuickForm_textarea
{
    /**
     * The width of the editor in pixels or percent
     *
     * @var string
     * @access public
     */
    var $width = '94%';
    
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
     * @param   string  FCKeditor instance name
     * @param   string  FCKeditor instance label
     * @param   array   Config settings for FCKeditor
     * @param   string  Attributes for the textarea
     * @access  public
     * @return  void
     */
    function __construct($elementName=null, $elementLabel=null, $attributes=null, $options=array())
    {
        parent::__construct($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->_type = 'CKeditor';
        // set editor height smaller if schema defines rows as 4 or less
        if ( is_array($attributes) && array_key_exists( 'rows', $attributes ) && $attributes['rows'] <= 4 ) {
            $this->height = 175;
        }
    }    

    /**
     * Add js to to convert normal textarea to ckeditor
     *
     * @access public
     * @return string
     */
    function toHtml()
    {
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        }
        else {
          $config = CRM_Core_Config::singleton();
          if (CRM_Utils_System::isUserLoggedIn()) {
            $plugins = array('widget', 'lineutils',  'mediaembed', 'tableresize', 'image2');
            // Add clipboard_image plugin only if user has permission
            // Permission check follows the same logic as CRM/Core/Page/AJAX/EditorImageUpload.php:23-24
            if (CRM_Core_Permission::check('access CiviCRM') || 
                CRM_Core_Permission::check('paste and upload images')) {
              $plugins[] = 'clipboard_image';
            }
            foreach($plugins as $name){
              $extraPlugins[] = 'CKEDITOR.plugins.addExternal("'.$name.'", "'.$config->resourceBase.'/packages/ckeditor/extraplugins/'.$name.'/", "plugin.js");';
            }
            if (CRM_Core_Permission::check('access CiviCRM')) {
              $toolbar = 'CiviCRM';
              $allowedContent = 'editor.config.allowedContent = true;';
            }
            else {
              $allowedContent = "editor.config.allowedContent = 'h1 h2 h3 p blockquote; strong em; a[!href]; img(left,right)[!src,alt,width,height,title]; span{font-size,color,background-color}';";
              $toolbar =  'CiviCRMBasic';
            }
            $fullPage = $this->getAttribute('fullpage');
            if ($fullPage) {
              $fullPage = "editor.config.fullPage = true;";
            }
            else {
              $fullPage = "editor.config.fullPage = false;";
            }
            $name = $this->getAttribute('name');
            $html = '';
            $html .= parent::toHtml();
            if (empty($GLOBALS['civcirm_ckeditor_script'])) {
              $html .= "\n".'<script type="text/javascript" src="'.$config->resourceBase.'packages/ckeditor/ckeditor.js?'.$config->ver.'"></script>'."\n";
              $GLOBALS['civicrm_ckeditor_script'] = TRUE;
            }

            // Load switcher logic
            if (empty($GLOBALS['editor_switcher_loaded'])) {
              $html .= '<script type="text/javascript" src="' . $config->resourceBase . 'packages/ckeditor5/editor-switcher.js?' . $config->ver . '"></script>' . "\n";
              $GLOBALS['editor_switcher_loaded'] = TRUE;
            }

            $html .= "<script type='text/javascript'>
".implode("\n", $extraPlugins)."
cj( function( ) {
  if (cj('#{$name}').hasClass('ckeditor-processed')) {
    return;
  }
  else {
    cj('#{$name}').addClass('ckeditor-processed');
  }
  CKEDITOR.replace('{$name}');
  var editor = CKEDITOR.instances['{$name}'];
  if ( editor ) {
    editor.on( 'key', function( evt ){
      global_formNavigate = false;
    });
    editor.config.extraPlugins = '".implode(',', $plugins)."';
    editor.config.customConfig = '".$config->resourceBase."js/ckeditor.config.js';
    editor.config.width  = '".$this->width."';
    editor.config.height = '".$this->height."';
    ".$allowedContent."
    ".$fullPage."
    editor.config.toolbar = '".$toolbar."';
  }
}); 
</script>";

            // Add Switcher UI
            $systemEditorId = CRM_Core_BAO_Preferences::value('editor_id');
            $isCke5Default = ($systemEditorId == 4 || (is_array($systemEditorId) && in_array(4, $systemEditorId))); // 4 is CKE5 ID from add_ckeditor5_option.sql
            
            $cke4Config = array(
              'resourceBase' => $config->resourceBase,
              'ver' => $config->ver,
              'plugins' => $plugins,
              'extraPluginsCode' => implode("\n", $extraPlugins),
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
                <option value="cke4" selected>CKEditor 4 (傳統)</option>
                <option value="cke5">CKEditor 5 (新版)</option>
              </select>
              <span class="editor-switch-status" style="font-size: 11px; color: #666;"></span>
            </div>';
            
            $html = $switcherHtml . $html;
          }
          else {
            $toolbar = NULL;
            $html = parent::toHtml();
          }
          return $html;
        }
    }
    
    /**
     * Returns the htmlarea content in HTML
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
