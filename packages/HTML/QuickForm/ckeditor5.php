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
   * Render the textarea with a visual indicator for testing
   *
   * This test version renders a normal textarea with a green banner
   * to confirm that the CKEditor5 QuickForm element is being loaded.
   *
   * @access public
   * @return string HTML output
   */
  function toHtml()
  {
    if ($this->_flagFrozen) {
      return $this->getFrozenHtml();
    }

    // Render normal textarea (maintains form functionality)
    $html = parent::toHtml();

    // Add visual indicator for testing purposes
    $name = $this->getAttribute('name');
    $html .= '
<div style="border: 2px solid #4CAF50; background: #E8F5E9; padding: 12px; margin: 10px 0; border-radius: 4px; font-family: sans-serif;">
  <strong style="color: #2E7D32; font-size: 14px;">✓ CKEditor 5 QuickForm Element Loaded Successfully</strong>
  <p style="margin: 8px 0 0 0; font-size: 13px; color: #555; line-height: 1.5;">
    <strong>Test Information:</strong><br>
    • Textarea ID: <code style="background: #fff; padding: 2px 6px; border-radius: 3px;">' . htmlspecialchars($name) . '</code><br>
    • Class Name: <code style="background: #fff; padding: 2px 6px; border-radius: 3px;">HTML_QuickForm_CKEditor5</code><br>
    • File Path: <code style="background: #fff; padding: 2px 6px; border-radius: 3px;">/civicrm/packages/HTML/QuickForm/ckeditor5.php</code>
  </p>
  <p style="margin: 8px 0 0 0; font-size: 12px; color: #666; font-style: italic;">
    ℹ️ Currently in "QuickForm Loading Mechanism Test" phase. The actual CKEditor 5 library is NOT integrated yet.
  </p>
</div>';

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
