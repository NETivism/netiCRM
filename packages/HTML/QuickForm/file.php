<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * HTML class for a file upload field
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
 * @author      Alexey Borzov <avb@php.net>
 * @copyright   2001-2009 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 * @version     CVS: $Id: file.php,v 1.25 2009/04/04 21:34:02 avb Exp $
 * @link        http://pear.php.net/package/HTML_QuickForm
 */

/**
 * Base class for <input /> form elements
 */
require_once 'HTML/QuickForm/input.php';

// register file-related rules
if (class_exists('HTML_QuickForm')) {
    HTML_QuickForm::registerRule('uploadedfile', 'callback', '_ruleIsUploadedFile', 'HTML_QuickForm_file');
    HTML_QuickForm::registerRule('maxfilesize', 'callback', '_ruleCheckMaxFileSize', 'HTML_QuickForm_file');
    HTML_QuickForm::registerRule('mimetype', 'callback', '_ruleCheckMimeType', 'HTML_QuickForm_file');
    HTML_QuickForm::registerRule('filename', 'callback', '_ruleCheckFileName', 'HTML_QuickForm_file');
}

/**
 * HTML class for a file upload field
 * 
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 3.2.11
 * @since       1.0
 */
class HTML_QuickForm_file extends HTML_QuickForm_input
{
    // {{{ properties

   /**
    * Uploaded file data, from $_FILES
    * @var array
    */
    var $_value = null;

    // }}}
    // {{{ constructor

    /**
     * Class constructor
     * 
     * @param     string    Input field name attribute
     * @param     string    Input field label
     * @param     mixed     (optional)Either a typical HTML attribute string 
     *                      or an associative array
     * @since     1.0
     * @access    public
     */
    function __construct($elementName=null, $elementLabel=null, $attributes=null)
    {
        parent::__construct($elementName, $elementLabel, $attributes);
        $this->setType('file');
    } //end constructor
    
    // }}}
    // {{{ setSize()

    /**
     * Sets size of file element
     * 
     * @param     int    Size of file element
     * @since     1.0
     * @access    public
     */
    function setSize($size)
    {
        $this->updateAttributes(array('size' => $size));
    } //end func setSize
    
    // }}}
    // {{{ getSize()

    /**
     * Returns size of file element
     * 
     * @since     1.0
     * @access    public
     * @return    int
     */
    function getSize()
    {
        return $this->getAttribute('size');
    } //end func getSize

    // }}}
    // {{{ freeze()

    /**
     * Freeze the element so that only its value is returned
     * 
     * @access    public
     * @return    bool
     */
    function freeze()
    {
        $this->_flagFrozen = true;
    } //end func freeze

    // }}}
    // {{{ setValue()

    /**
     * Sets value for file element.
     * 
     * Actually this does nothing. The function is defined here to override
     * HTML_Quickform_input's behaviour of setting the 'value' attribute. As
     * no sane user-agent uses <input type="file">'s value for anything 
     * (because of security implications) we implement file's value as a 
     * read-only property with a special meaning.
     * 
     * @param     mixed    Value for file element
     * @since     3.0
     * @access    public
     */
    function setValue($value)
    {
        return null;
    } //end func setValue
    
    // }}}
    // {{{ getValue()

    /**
     * Returns information about the uploaded file
     *
     * @since     3.0
     * @access    public
     * @return    array
     */
    function getValue()
    {
        return $this->_value;
    } // end func getValue

    // }}}
    // {{{ onQuickFormEvent()

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param     string    Name of event
     * @param     mixed     event arguments
     * @param     object    calling object
     * @since     1.0
     * @access    public
     * @return    bool
     */
    function onQuickFormEvent($event, $arg, &$caller)
    {
        switch ($event) {
            case 'updateValue':
                if ($caller->getAttribute('method') == 'get') {
                    return PEAR::raiseError('Cannot add a file upload field to a GET method form');
                }
                $value = $this->_findValue();
                if (null === $value) {
                  $value = $this->_findValue($caller->_defaultValues);
                }
                $this->_value = $value;
                $caller->updateAttributes(array('enctype' => 'multipart/form-data'));
                $caller->setMaxFileSize();
                break;
            case 'addElement':
                $this->onQuickFormEvent('createElement', $arg, $caller);
                return $this->onQuickFormEvent('updateValue', null, $caller);
                break;
            case 'createElement':
                $this->__construct($arg[0], $arg[1], $arg[2]);
                break;
        }
        return true;
    } // end func onQuickFormEvent

    // }}}
    // {{{ moveUploadedFile()

    /**
     * Moves an uploaded file into the destination 
     * 
     * @param    string  Destination directory path
     * @param    string  New file name
     * @access   public
     * @return   bool    Whether the file was moved successfully
     */
    function moveUploadedFile($dest, $fileName = '')
    {
        if ($dest != ''  && substr($dest, -1) != '/') {
            $dest .= '/';
        }
        if (is_array($this->_value['name'])) {
            foreach($this->_value['name'] as $idx => $name) {
                $filename = is_array($fileName) && !empty($fileName[$idx]) ? $fileName[$idx] : basename($name);
                $moved = move_uploaded_file($this->_value['tmp_name'][$idx], $dest . $filename);
                if (!$moved) {
                    break;
                }
            }
        }
        else {
            $filename = $fileName ? $fileName : basename($this->_value['name']);
            $moved = move_uploaded_file($this->_value['tmp_name'], $dest . $filename); 
        }

        return $moved;
    } // end func moveUploadedFile
    
    // }}}
    // {{{ isUploadedFile()

    /**
     * Checks if the element contains an uploaded file
     *
     * @access    public
     * @return    bool      true if file has been uploaded, false otherwise
     */
    function isUploadedFile()
    {
        return HTML_QuickForm_file::_ruleIsUploadedFile($this->_value);
    } // end func isUploadedFile

    // }}}
    // {{{ _ruleIsUploadedFile()

    /**
     * Checks if the given element contains an uploaded file
     *
     * @param     array     Uploaded file info (from $_FILES)
     * @access    private
     * @return    bool      true if file has been uploaded, false otherwise
     */
    static function _ruleIsUploadedFile($elementValue)
    {
        if (is_array($elementValue['error'])) {
            foreach($elementValue['error'] as $idx => $err) {
                if ($err != 0) {
                    unset($elementValue['error'][$idx]);
                    unset($elementValue['tmp_name'][$idx]);
                }
            }
            if (count($elementValue['tmp_name']) > 0) {
                $tmpName = reset($elementValue['tmp_name']);
                return is_uploaded_file($tmpName);
            }
        }
        else{
            if ((isset($elementValue['error']) && $elementValue['error'] == 0) ||
                (!empty($elementValue['tmp_name']) && $elementValue['tmp_name'] != 'none')) {
                return is_uploaded_file($elementValue['tmp_name']);
            }
        }
        return false;
    } // end func _ruleIsUploadedFile
    
    // }}}
    // {{{ _ruleCheckMaxFileSize()

    /**
     * Checks that the file does not exceed the max file size
     *
     * @param     array     Uploaded file info (from $_FILES)
     * @param     int       Max file size
     * @access    private
     * @return    bool      true if filesize is lower than maxsize, false otherwise
     */
    static function _ruleCheckMaxFileSize($elementValue, $maxSize)
    {
        if (!empty($elementValue['error']) && 
            (UPLOAD_ERR_FORM_SIZE == $elementValue['error'] || UPLOAD_ERR_INI_SIZE == $elementValue['error'])) {
            return false;
        }
        if (!HTML_QuickForm_file::_ruleIsUploadedFile($elementValue)) {
            return true;
        }
        if (is_array($elementValue['tmp_name'])) {
          foreach($elementValue['tmp_name'] as $file) {
            $size += @filesize($file);
          }
        }
        else {
          $size = @filesize($elementValue['tmp_name']);
        }
        return ($maxSize >= $size);
    } // end func _ruleCheckMaxFileSize

    // }}}
    // {{{ _ruleCheckMimeType()

    /**
     * Checks if the given element contains an uploaded file of the right mime type
     *
     * @param     array     Uploaded file info (from $_FILES)
     * @param     mixed     Mime Type (can be an array of allowed types)
     * @access    private
     * @return    bool      true if mimetype is correct, false otherwise
     */
    static function _ruleCheckMimeType($elementValue, $mimeType)
    {
        if (!HTML_QuickForm_file::_ruleIsUploadedFile($elementValue)) {
            return true;
        }
        $valid = TRUE;
        if (is_array($elementValue['type'])) {
            foreach($elementValue['type'] as $type)  {
                if (is_array($mimeType) && !in_array($type, $mimeType)) {
                   $valid = FALSE;
                }
                elseif($type != $mimeType) {
                   $valid = FALSE;
                }
            }
        }
        else {
            if (is_array($mimeType) && !in_array($elementValue['type'], $mimeType)) {
               $valid = FALSE;
            }
            elseif($elementValue['type'] != $mimeType) {
               $valid = FALSE;
            }
        }
        return $valid;
    } // end func _ruleCheckMimeType

    // }}}
    // {{{ _ruleCheckFileName()

    /**
     * Checks if the given element contains an uploaded file of the filename regex
     *
     * @param     array     Uploaded file info (from $_FILES)
     * @param     string    Regular expression
     * @access    private
     * @return    bool      true if name matches regex, false otherwise
     */
    static function _ruleCheckFileName($elementValue, $regex)
    {
        if (!HTML_QuickForm_file::_ruleIsUploadedFile($elementValue)) {
            return true;
        }
        $valid = TRUE;
        if (is_array($elementValue['name'])) {
            foreach($elementValue['name'] as $name) {
                if (!preg_match($regex, $name)) {
                    $valid = FALSE;
                }
            }
        }
        else {
            if (!preg_match($regex, $elementValue['name'])) {
                $valid = FALSE;
            }
        }
        return $valid;
    } // end func _ruleCheckFileName
    
    // }}}
    // {{{ _findValue()

   /**
    * Tries to find the element value from the values array
    * 
    * Needs to be redefined here as $_FILES is populated differently from 
    * other arrays when element name is of the form foo[bar]
    * 
    * @access    private
    * @return    mixed
    */
    function _findValue(&$values = NULL)
    {
        $elementName = $this->getName();
        $elementName = preg_replace('/\[\]$/', '', $elementName);
        if (!empty($values)) {
          if (isset($values[$elementName])) {
            if (is_array($values[$elementName])) {
              if (!empty($values[$elementName]['name'])) {
                return $values[$elementName]['name'];
              }
            }
            if (!empty($values[$elementName])) {
              return $values[$elementName];
            }
          }
        }
        if (empty($_FILES)) {
          return null;
        }
        if (isset($_FILES[$elementName])) {
            return $_FILES[$elementName];
        } elseif (false !== ($pos = strpos($elementName, '['))) {
            $base = substr($elementName, 0, $pos);
            $idx = explode('][', str_replace(["['", "']", '["', '"]'], ['[', ']', '[', ']'], substr($elementName, $pos + 1, -1)));
            $idx = array_merge([$base, 'name'], $idx);
            if (!CRM_Utils_Array::pathIsset($_FILES, $idx)) {
                return NULL;
            }
            else {
                $props = ['name', 'type', 'size', 'tmp_name', 'error'];
                $value = [];
                foreach ($props as $prop) {
                    $idx[1] = $prop;
                    $value[$prop] = CRM_Utils_Array::pathGet($_FILES, $idx);
                }
                return $value;
             }
        } else {
            return null;
        }
    }
    // }}}
} // end class HTML_QuickForm_file
?>
