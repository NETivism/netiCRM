<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * HTML class for a radio type element
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @copyright   2001-2009 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 * @version     CVS: $Id: radio.php,v 1.20 2009/04/04 21:34:04 avb Exp $
 * @link        http://pear.php.net/package/HTML_QuickForm
 */

/**
 * Base class for <input /> form elements
 */
require_once 'HTML/QuickForm/input.php';

/**
 * HTML class for a radio type element
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @version     Release: 3.2.11
 * @since       1.0
 */
class HTML_QuickForm_radio extends HTML_QuickForm_input
{
    // {{{ properties

    /**
     * Radio display text
     * @var       string
     * @since     1.1
     * @access    private
     */
    var $_text = '';

    // }}}
    // {{{ constructor

    /**
     * Class constructor
     *
     * @param     string    Input field name attribute
     * @param     mixed     Label(s) for a field
     * @param     string    Text to display near the radio
     * @param     string    Input field value
     * @param     mixed     Either a typical HTML attribute string or an associative array
     * @since     1.0
     * @access    public
     * @return    void
     */
    function __construct($elementName=null, $elementLabel=null, $text=null, $value=null, $attributes=null)
    {

        parent::__construct($elementName, $elementLabel, $attributes);
        if (isset($value)) {
            $this->setValue($value);
        }
        $this->_persistantFreeze = true;
        $this->setType('radio');
        $this->_text = $text;
        // $this->_generateId();
        if ( ! $this->getAttribute('id') ) {
            //hack to add 'id' for checkbox
            static $idTextStr = 1;
            $this->updateAttributes( array('id' => CRM_Utils_String::munge( "CIVICRM_QFID_{$value}_{$idTextStr}" ) ) );
            $idTextStr++;
        }
    } //end constructor

    // }}}
    // {{{ setChecked()

    /**
     * Sets whether radio button is checked
     *
     * @param     bool    $checked  Whether the field is checked or not
     * @since     1.0
     * @access    public
     * @return    void
     */
    function setChecked($checked)
    {
        if (!$checked) {
            $this->removeAttribute('checked');
        } else {
            $this->updateAttributes(array('checked'=>'checked'));
        }
    } //end func setChecked

    // }}}
    // {{{ getChecked()

    /**
     * Returns whether radio button is checked
     *
     * @since     1.0
     * @access    public
     * @return    string
     */
    function getChecked()
    {
        return $this->getAttribute('checked');
    } //end func getChecked

    // }}}
    // {{{ toHtml()

    /**
     * Returns the radio element in HTML
     *
     * @since     1.0
     * @access    public
     * @return    string
     */
    function toHtml()
    {
        $output = '';

        if (0 == strlen($this->_text)) {
            $label = '';
            $output = HTML_QuickForm_input::toHtml();
        }
        elseif ($this->_flagFrozen) {
            $class = $this->getChecked() ? 'checked' : 'unchecked';
            $output = '<label class="freeze-'.$class.' crm-form-elem md-elem">'
                . HTML_QuickForm_input::toHtml()
                . '<span class="elem-label">' . $this->_text . '</span>'
                . '</label>';
        }
        else {
            $output = '<label class="crm-form-elem crm-form-radio" for="' . $this->getAttribute('id') . '">'
                . HTML_QuickForm_input::toHtml()
                . '<span class="elem-label">' . $this->_text . '</span>'
                . '</label>';
        }

        return $output;
    } //end func toHtml

    // }}}
    // {{{ getFrozenHtml()

    /**
     * Returns the value of field without HTML tags
     *
     * @since     1.0
     * @access    public
     * @return    string
     */
    function getFrozenHtml()
    {
        if ($this->getChecked()) {
            return '<span class="freeze-icon freeze-radio-checked"></span>'.
                   $this->_getPersistantData();
        } else {
            return '<span class="freeze-icon freeze-radio"></span>';
        }
    } //end func getFrozenHtml

    // }}}
    // {{{ setText()

    /**
     * Sets the radio text
     *
     * @param     string    $text  Text to display near the radio button
     * @since     1.1
     * @access    public
     * @return    void
     */
    function setText($text)
    {
        $this->_text = $text;
    } //end func setText

    // }}}
    // {{{ getText()

    /**
     * Returns the radio text
     *
     * @since     1.1
     * @access    public
     * @return    string
     */
    function getText()
    {
        return $this->_text;
    } //end func getText

    // }}}
    // {{{ onQuickFormEvent()

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param     string    $event  Name of event
     * @param     mixed     $arg    event arguments
     * @param     object    &$caller calling object
     * @since     1.0
     * @access    public
     * @return    void
     */
    function onQuickFormEvent($event, $arg, $caller = null)
    {
        switch ($event) {
            case 'updateValue':
                // constant values override both default and submitted ones
                $value = $this->_findValue($caller->_constantValues);
                if (null === $value) {
                    // we should retrieve value from submitted values when form is submitted,
                    // else set value from defaults values
                    if ( $caller->isSubmitted( ) ) {
                        $value = $this->_findValue($caller->_submitValues);
                    } else {
                        $value = $this->_findValue($caller->_defaultValues);
                    }
                }
                if (!is_null($value) && $value == $this->getValue()) {
                    $this->setChecked(true);
                } else {
                    $this->setChecked(false);
                }
                break;
            case 'setGroupValue':
                if ($arg == $this->getValue()) {
                    $this->setChecked(true);
                } else {
                    $this->setChecked(false);
                }
                break;
            default:
                parent::onQuickFormEvent($event, $arg, $caller);
        }
        return true;
    } // end func onQuickFormLoad

    // }}}
    // {{{ exportValue()

   /**
    * Returns the value attribute if the radio is checked, null if it is not
    */
    function exportValue(&$submitValues, $assoc = false)
    {
        $value = $this->_findValue($submitValues);
        if (null === $value) {
            // fix to return blank value when all radio's are unselected / not selected
            // always use submitted values rather than defaults
            //$value = $this->getChecked()? $this->getValue(): null;
            $value = '';
        } elseif ($value != $this->getValue()) {
            $value = null;
        }
        return $this->_prepareValue($value, $assoc);
    }

    // }}}
} //end class HTML_QuickForm_radio
?>
