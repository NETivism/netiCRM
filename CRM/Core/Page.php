<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */







/**
 * A Page is basically data in a nice pretty format.
 *
 * Pages should not have any form actions / elements in them. If they
 * do, make sure you use CRM_Core_Form and the related structures. You can
 * embed simple forms in Page and do your own form handling.
 *
 */
class CRM_Core_Page {

  /**
   * The name of the page (auto generated from class name)
   *
   * @var string
   * @access protected
   */
  protected $_name;

  /**
   * session scope of this page
   *
   * @var string
   * @access protected
   */
  protected $_scope;

  /**
   * quickform key of this page
   *
   * @var string
   * @access protected
   */
  protected $_qfKey = NULL;

  /**
   * the title associated with this page
   *
   * @var object
   * @access protected
   */
  protected $_title;

  /**
   * A page can have multiple modes. (i.e. displays
   * a different set of data based on the input
   * @var int
   * @access protected
   */
  protected $_mode;

  /**
   * Is this object being embedded in another object. If
   * so the display routine needs to not do any work. (The
   * parent object takes care of the display)
   *
   * @var boolean
   * @access protected
   */
  protected $_embedded = FALSE;

  /**
   * Are we in print mode? if so we need to modify the display
   * functionality to do a minimal display :)
   *
   * @var boolean
   * @access protected
   */
  protected $_print = FALSE;

  /**
   * cache the smarty template for efficiency reasons
   *
   * @var CRM_Core_Smarty
   * @access protected
   * @static
   */
  static protected $_template;

  /**
   * cache the session for efficiency reasons
   *
   * @var CRM_Core_Session
   * @access protected
   * @static
   */
  static protected $_session;

  /**
   * class constructor
   *
   * @param string $title title of the page
   * @param int    $mode  mode of the page
   *
   * @return CRM_Core_Page
   */
  function __construct($title = NULL, $mode = NULL) {
    // #16953, hack for page key
    global $pageKey;
    
    $this->_name = CRM_Utils_System::getClassName($this);
    $this->_title = $title;
    $this->_mode = $mode;
    $pageKey = CRM_Utils_Array::value('pageKey', $_REQUEST, NULL);
    if (empty($pageKey)) {
      // use qfkey to get pagekey
      $qfKey = CRM_Utils_Array::value('qfKey', $_REQUEST, NULL);
      if (!empty($qfKey)) {
        $session = CRM_Core_Session::singleton();
        $scope = $session->lookupScope($this->_name, 'qfKey', $qfKey);
        if ($scope) {
          $pageKey = $scope;
        }
      }
    }
    if (empty($pageKey)) {
      $pageKey = $this->_name . '_' . CRM_Utils_String::createRandom(8, CRM_Utils_String::ALPHANUMERIC);
    }
    $this->_scope = $pageKey;

    // only use the civicrm cache if we have a valid key
    if (!empty($this->_scope) && strstr($this->_scope, 'CRM_Contribute_Page_Tab')) {
      CRM_Core_Session::registerAndRetrieveSessionObjects([['CiviCRM', $this->_scope]]);
    }

    // let the constructor initialize this, should happen only once
    if (!isset(self::$_template)) {
      self::$_template = CRM_Core_Smarty::singleton();
      self::$_session = CRM_Core_Session::singleton();
    }

    if (isset($_GET['snippet']) && $_GET['snippet']) {
      header('X-Robots-Tag: noindex', TRUE);
      if ($_GET['snippet'] == 3) {
        $this->_print = CRM_Core_Smarty::PRINT_PDF;
      }
      else {
        $this->_print = CRM_Core_Smarty::PRINT_SNIPPET;
      }
    }

    // if the request has a reset value, initialize the controller session
    if (CRM_Utils_Array::value('reset', $_GET)) {
      $this->reset();
    }
  }

  /**
   * This function takes care of all the things common to all
   * pages. This typically involves assigning the appropriate
   * smarty variable :)
   *
   * @return string The content generated by running this page
   */
  function run() {
    if ($this->_embedded) {
      return;
    }

    self::$_template->assign('mode', $this->_mode);
    $pageTemplateFile = $this->getHookedTemplateFileName();
    self::$_template->assign('tplFile', $pageTemplateFile);

    // invoke the pagRun hook, CRM-3906

    CRM_Utils_Hook::pageRun($this);

    if ($this->_print) {
      if ($this->_print == CRM_Core_Smarty::PRINT_SNIPPET) {
        $content = self::$_template->fetch('CRM/common/snippet.tpl');
      }
      else {
        $content = self::$_template->fetch('CRM/common/print.tpl');
      }
      CRM_Utils_Hook::alterContent($content, 'page', $pageTemplateFile, $this);
      echo $content;
      CRM_Utils_System::civiExit();
    }
    $config = CRM_Core_Config::singleton();
    $content = self::$_template->fetch('CRM/common/' . strtolower($config->userFramework) . '.tpl');
    CRM_Utils_Hook::alterContent($content, 'page', $pageTemplateFile, $this);
    return CRM_Utils_System::theme($content);
  }

  /**
   * Store the variable with the value in the form scope
   *
   * @param  string|array $name  name  of the variable or an assoc array of name/value pairs
   * @param  mixed        $value value of the variable if string
   *
   * @access public
   *
   * @return void
   *
   */
  function set($name, $value = NULL) {
    self::$_session->set($name, $value, $this->_scope);
  }

  /**
   * Get the variable from the form scope
   *
   * @param  string name  : name  of the variable
   *
   * @access public
   *
   * @return mixed
   *
   */
  function get($name) {
    return self::$_session->get($name, $this->_scope);
  }

  function changeScope($qfKey = NULL){
    $qfKey = $qfKey ? $qfKey : $this->_qfKey;
    $this->_qfKey = $qfKey;

    $newScope = $this->_name . '_'. $qfKey;
    if($newScope != $this->_scope && $qfKey) {
      self::$_session->changeScope($this->_scope, $newScope);
      $this->_scope = $newScope;
    }
  }

  /**
   * assign value to name in template
   *
   * @param array|string $name  name  of variable
   * @param mixed $value value of varaible
   *
   * @return void
   * @access public
   */
  function assign($var, $value = NULL) {
    self::$_template->assign($var, $value);
  }

  /**
   * assign value to name in template by reference
   *
   * @param array|string $name  name  of variable
   * @param mixed $value (reference) value of varaible
   *
   * @return void
   * @access public
   */
  function assign_by_ref($var, &$value) {
    self::$_template->assign_by_ref($var, $value);
  }

  /**
   * function to destroy all the session state of this page.
   *
   * @access public
   *
   * @return void
   */
  function reset() {
    self::$_session->resetScope($this->_scope);
  }

  /**
   * Use the form name to create the tpl file name
   *
   * @return string
   * @access public
   */
  function getTemplateFileName() {
    return str_replace('_',
      DIRECTORY_SEPARATOR,
      CRM_Utils_System::getClassName($this)
    ) . '.tpl';
  }

  /**
   * A wrapper for getTemplateFileName that includes calling the hook to
   * prevent us from having to copy & paste the logic of calling the hook
   */
  function getHookedTemplateFileName() {
    $pageTemplateFile = $this->getTemplateFileName();
    CRM_Utils_Hook::alterTemplateFile(get_class($this), $this, 'page', $pageTemplateFile);
    return $pageTemplateFile;
  }

  /**
   * setter for embedded
   *
   * @param boolean $embedded
   *
   * @return void
   * @access public
   */
  function setEmbedded($embedded) {
    $this->_embedded = $embedded;
  }

  /**
   * getter for embedded
   *
   * @return boolean return the embedded value
   * @access public
   */
  function getEmbedded() {
    return $this->_embedded;
  }

  /**
   * setter for print
   *
   * @param boolean $print
   *
   * @return void
   * @access public
   */
  function setPrint($print) {
    $this->_print = $print;
  }

  /**
   * getter for print
   *
   * @return boolean return the print value
   * @access public
   */
  function getPrint() {
    return $this->_print;
  }

  static function &getTemplate() {
    return self::$_template;
  }

  function getVar($name) {
    return $this->$name ?? NULL;
  }

  function setVar($name, $value) {
    $this->$name = $value;
  }
}

