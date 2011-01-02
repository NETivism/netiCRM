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
    function HTML_QuickForm_ckeditor($elementName=null, $elementLabel=null, $attributes=null, $options=array())
    {
        HTML_QuickForm_element::HTML_QuickForm_element($elementName, $elementLabel, $attributes);
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
        // using function_exists not module_exists to prevent non-drupal conflit
        if(function_exists('module_exists')){
          if(module_exists('civicrm_ckeditor')){
            $ckeditor_config = base_path().drupal_get_path('module', 'civicrm_ckeditor')."/ckeditor.config.js";
          }
        }
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        } else {
            $name = $this->getAttribute('name');
            $html = parent::toHtml() . "<script type='text/javascript'>
                cj( function( ) {
                    cj('#{$name}').removeClass();
                    if ( CKEDITOR.instances['{$name}'] ) {
                        CKEDITOR.remove(CKEDITOR.instances['{$name}']);
                    }";
                    if($ckeditor_config){
                      $html .="
                      CKEDITOR.replace( '{$name}',
                      {
                        customConfig : '{$ckeditor_config}',
                        toolbar: 'CiviCRM'
                      });";
                    }
                    else{
                      $html .= "CKEDITOR.replace( '{$name}' )";
                    }
                    $html .= "
                    var editor = CKEDITOR.instances['{$name}'];
                    if ( editor ) {
                        editor.on( 'key', function( evt ){
                            global_formNavigate = false;
                        } );
                        editor.config.width  = '".$this->width."';
                        editor.config.height = '".$this->height."';
                    }
                }); 
            </script>";
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
