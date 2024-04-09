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
 * This class acts as our base controller class and adds additional
 * functionality and smarts to the base QFC. Specifically we create
 * our own action classes and handle the transitions ourselves by
 * simulating a state machine. We also create direct jump links to any
 * page that can be used universally.
 *
 * This concept has been discussed on the PEAR list and the QFC FAQ
 * goes into a few details. Please check
 * http://pear.php.net/manual/en/package.html.html-quickform-controller.faq.php
 * for other useful tips and suggestions
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

require_once 'HTML/QuickForm/Controller.php';
require_once 'HTML/QuickForm/Action/Direct.php';

require_once 'CRM/Core/StateMachine.php';
class CRM_Core_Controller extends HTML_QuickForm_Controller {

  /**
   * the title associated with this controller
   *
   * @var string
   */
  protected $_title;

  /**
   * The key associated with this controller
   *
   * @var string
   */
  public $_key;

  /**
   * the name of the session scope where values are stored
   *
   * @var object
   */
  protected $_scope;

  /**
   * the state machine associated with this controller
   *
   * @var object
   */
  protected $_stateMachine;

  /**
   * Is this object being embedded in another object. If
   * so the display routine needs to not do any work. (The
   * parent object takes care of the display)
   *
   * @var boolean
   */
  protected $_embedded = FALSE;

  /**
   * After entire form execution complete,
   * do we want to skip control redirection.
   * Default - It get redirect to user context.
   *
   * Useful when we run form in non civicrm context
   * and we need to transfer control back.(eg. drupal)
   *
   * @var boolean
   */
  protected $_skipRedirection = FALSE;

  /**
   * Are we in print mode? if so we need to modify the display
   * functionality to do a minimal display :)
   *
   * @var boolean
   */
  public $_print = 0;

  /**
   * cache the smarty template for efficiency reasons
   *
   * @var CRM_Core_Smarty
   */
  static protected $_template;

  /**
   * cache the session for efficiency reasons
   *
   * @var CRM_Core_Session
   */
  static public $_session;

  /**
   * The parent of this form if embedded
   *
   * @var object
   */
  protected $_parent = NULL;

  /**
   * The destination if set will override the destination the code wants to send it to
   *
   * @var string;
   */
  public $_destination = NULL;

  /**
   * All CRM single or multi page pages should inherit from this class.
   *
   * @param string  title        descriptive title of the controller
   * @param boolean whether      controller is modal
   * @param string  scope        name of session if we want unique scope, used only by Controller_Simple
   * @param boolean addSequence  should we add a unique sequence number to the end of the key
   * @param boolean ignoreKey    should we not set a qfKey for this controller (for standalone forms)
   *
   * @access public
   *
   * @return void
   *
   */
  function __construct($title = NULL, $modal = TRUE,
    $mode = NULL, $scope = NULL,
    $addSequence = FALSE, $ignoreKey = FALSE
  ) {
    // this has to true for multiple tab session fix
    $addSequence = TRUE;

    // add a unique validable key to the name
    $name = CRM_Utils_System::getClassName($this);
    $name = $name . '_' . $this->key($name, $addSequence, $ignoreKey);
    $this->_title = $title;
    if ($scope) {
      $this->_scope = $scope;
    }
    else {
      $this->_scope = CRM_Utils_System::getClassName($this);
    }
    $this->_scope = $this->_scope . '_' . $this->_key;

    // only use the civicrm cache if we have a valid key
    // else we clash with other users CRM-7059
    if (!empty($this->_key)) {
      require_once 'CRM/Core/BAO/Cache.php';
      CRM_Core_Session::registerAndRetrieveSessionObjects(array("_{$name}_container", array('CiviCRM', $this->_scope)));
    }

    parent::__construct($name, $modal);

    // let the constructor initialize this, should happen only once
    $this->initTemplate();
    $this->initSession();

    $snippet = CRM_Utils_Array::value('snippet', $_REQUEST);
    //$snippet = CRM_Utils_Request::retrieve( 'snippet', 'Integer', $this, false, null, $_REQUEST );
    if ($snippet) {
      header('X-Robots-Tag: noindex', TRUE);
      if ($snippet == 3) {
        $this->_print = CRM_Core_Smarty::PRINT_PDF;
      }
      elseif ($snippet == 4) {
        $this->_print = CRM_Core_Smarty::PRINT_NOFORM;
        self::$_template->assign('suppressForm', TRUE);
      }
      elseif ($snippet == 5) {
        $this->_print = CRM_Core_Smarty::PRINT_NOFORM;
      }
      else {
        $this->_print = CRM_Core_Smarty::PRINT_SNIPPET;
      }
    }

    // if the request has a reset value, initialize the controller session
    if (CRM_Utils_Array::value('reset', $_GET)) {
      $this->reset();
    }

    // set the key in the session
    // do this at the end so we have initialized the object
    // and created the scope etc
    $this->set('qfKey', $this->_key);
    $this->set('expired', CRM_REQUEST_TIME + CRM_Core_Session::EXPIRED_TIME);

    require_once 'CRM/Utils/Request.php';

    // also retrieve and store destination in session
    $this->_destination = CRM_Utils_Request::retrieve('destination', 'String', $this,
      FALSE, NULL, $_REQUEST
    );
  }

  function initSession() {
    if (!isset(self::$_session)) {
      self::$_session = CRM_Core_Session::singleton();
    }
  }
  function initTemplate() {
    if (!isset(self::$_template)) {
      self::$_template = CRM_Core_Smarty::singleton();
    }
  }

  function fini() {
    require_once 'CRM/Core/BAO/Cache.php';
    CRM_Core_BAO_Cache::storeSessionToCache(array("_{$this->_name}_container", array('CiviCRM', $this->_scope)), TRUE);
  }

  function key($name, $addSequence = FALSE, $ignoreKey = FALSE) {
    $config = CRM_Core_Config::singleton();

    if ($ignoreKey ||
      (isset($config->keyDisable) && $config->keyDisable)
    ) {
      return NULL;
    }

    require_once 'CRM/Core/Key.php';

    $qfKey = CRM_Utils_Array::value('qfKey', $_REQUEST, NULL);
    if (!$qfKey) {
      $key = CRM_Core_Key::get($name, $addSequence);
    }
    else {
      $key = CRM_Core_Key::validate($qfKey, $name, $addSequence);
    }

    if (!$key) {
      // refs #27780 check if is POST request and is payment related page
      // give another message for user to check email to get result of payment
      if (!empty($_GET['_qf_ThankYou_display']) && !empty($qfKey)) {
        CRM_Core_Error::fatal('missing cookie on thank-you page', '', ts("Because of the missing cookie from your browser, we don't know the result of your form submission. If you enter payment information about this form submission, there should be an email notification in your inbox. Please use that for your final payment result."));
      }
      else {
        $msg = ts('We can\'t load the requested web page. This page requires cookies to be enabled in your browser settings. Please check this setting and enable cookies (if they are not enabled). Then try again. If this error persists, contact the site adminstrator for assistance.') . '<br /><br />' . ts('Site Administrators: This error may indicate that users are accessing this page using a domain or URL other than the configured Base URL. EXAMPLE: Base URL is http://example.org, but some users are accessing the page via http://www.example.org or a domain alias like http://myotherexample.org.') . '<br /><br />' . ts('Error type: Could not find a valid session key.');
        CRM_Core_Error::fatal($msg);
      }
    }

    $this->_key = $key;

    return $key;
  }

  /**
   * Process the request, overrides the default QFC run method
   * This routine actually checks if the QFC is modal and if it
   * is the first invalid page, if so it call the requested action
   * if not, it calls the display action on the first invalid page
   * avoids the issue of users hitting the back button and getting
   * a broken page
   *
   * This run is basically a composition of the original run and the
   * jump action
   *
   */
  function run() {
    // the names of the action and page should be saved
    // note that this is split into two, because some versions of
    // php 5.x core dump on the triple assignment :)
    $this->_actionName = $this->getActionName();
    list($pageName, $action) = $this->_actionName;

    if ($this->isModal()) {
      if (!$this->isValid($pageName)) {
        $pageName = $this->findInvalid();
        $action = 'display';
      }
    }

    // note that based on action, control might not come back!!
    // e.g. if action is a valid JUMP, u basically do a redirect
    // to the appropriate place
    $this->wizardHeader($pageName);
    $this->_pages[$pageName]->handle($action);
    return;
  }

  function validate() {
    $this->_actionName = $this->getActionName();
    list($pageName, $action) = $this->_actionName;

    $page = &$this->_pages[$pageName];

    $data = &$this->container();
    $this->applyDefaults($pageName);
    $page->isFormBuilt() or $page->buildForm();
    // We use defaults and constants as if they were submitted
    $data['values'][$pageName] = $page->exportValues();
    $page->loadValues($data['values'][$pageName]);
    // Is the page now valid?
    if (TRUE === ($data['valid'][$pageName] = $page->validate())) {
      return TRUE;
    }
    return $page->_errors;
  }

  function findValid() {
    // the names of the action and page should be saved
    // note that this is split into two, because some versions of
    // php 5.x core dump on the triple assignment :)
    $this->_actionName = $this->getActionName();
    list($pageNameOld, $action) = $this->_actionName;
    $pageNames = array_flip(array_keys($this->_pages));

    if ($this->isModal()) {
      $pageNameCorrect = $this->findInvalid();
      $action = 'display';
    }

    // note that based on action, control might not come back!!
    // e.g. if action is a valid JUMP, u basically do a redirect
    // to the appropriate place
    if($pageNames[$pageNameCorrect] > $pageNames[$pageNameOld]) {
      $redirect = '_qf_'.$pageNameCorrect.'_'.$action.'=true&qfKey='.$this->_key;
      return $redirect;
    }
    return FALSE;
  }

  /**
   * Helper function to add all the needed default actions. Note that the framework
   * redefines all of the default QFC actions
   *
   * @param string   directory to store all the uploaded files
   * @param array    names for the various upload buttons (note u can have more than 1 upload)
   *
   * @access private
   *
   * @return void
   *
   */
  function addActions($uploadDirectory = NULL, $uploadNames = NULL) {
    $names = array(
      'display' => 'CRM_Core_QuickForm_Action_Display',
      'next' => 'CRM_Core_QuickForm_Action_Next',
      'back' => 'CRM_Core_QuickForm_Action_Back',
      'process' => 'CRM_Core_QuickForm_Action_Process',
      'cancel' => 'CRM_Core_QuickForm_Action_Cancel',
      'refresh' => 'CRM_Core_QuickForm_Action_Refresh',
      'done' => 'CRM_Core_QuickForm_Action_Done',
      'jump' => 'CRM_Core_QuickForm_Action_Jump',
      'submit' => 'CRM_Core_QuickForm_Action_Submit',
    );

    foreach ($names as $name => $classPath) {
      $action = new $classPath($this->_stateMachine);
      $this->addAction($name, $action);
    }

    $this->addUploadAction($uploadDirectory, $uploadNames);
  }

  /**
   * getter method for stateMachine
   *
   * @return object
   * @access public
   */
  function getStateMachine() {
    return $this->_stateMachine;
  }

  /**
   * setter method for stateMachine
   *
   * @param object a stateMachineObject
   *
   * @return void
   * @access public
   */
  function setStateMachine($stateMachine) {
    $this->_stateMachine = $stateMachine;
  }

  /**
   * add pages to the controller. Note that the controller does not really care
   * the order in which the pages are added
   *
   * @param object $stateMachine  the state machine object
   * @param int    $action        the mode in which the state machine is operating
   *                              typicaly this will be add/view/edit
   *
   * @return void
   * @access public
   *
   */
  function addPages(&$stateMachine, $action = CRM_Core_Action::NONE) {
    $pages = $stateMachine->getPages();

    foreach ($pages as $name => $value) {
      $className = CRM_Utils_Array::value('className', $value, $name);
      $title = CRM_Utils_Array::value('title', $value);
      $options = CRM_Utils_Array::value('options', $value);
      $stateName = CRM_Utils_String::getClassName($className);
      if (CRM_Utils_Array::value('className', $value)) {
        $formName = $name;
      }
      else {
        $formName = CRM_Utils_String::getClassName($name);
      }

      require_once 'CRM/Core/Extensions.php';
      $ext = new CRM_Core_Extensions();
      if ($ext->isExtensionClass($className)) {
        require_once ($ext->classToPath($className));
      }
      else {
        if (!class_exists($className)) {
          require_once (str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php');
        }
      }
      $$stateName = new $className($stateMachine->find($className), $action, 'post', $formName);
      if ($title) {
        $$stateName->setTitle($title);
      }
      if ($options) {
        $$stateName->setOptions($options);
      }
      $this->addPage($$stateName);
      $this->addAction($stateName, new HTML_QuickForm_Action_Direct());

      //CRM-6342 -we need kill the reference here,
      //as we have deprecated reference object creation.
      unset($$stateName);
    }
  }

  /**
   * Get current page
   * 
   * Get current form object for further usage.
   * In most case, pages defined in statemachine and it's form object.
   *
   * @return CRM_Core_Form
   */
  public function getCurrentPage() {
    $this->_actionName = $this->getActionName();
    list($pageName, $action) = $this->_actionName;
    return $this->getPage($pageName);
  }

  public function nextPage(){
    $this->_actionName = $this->getActionName();
    list($pageName, $action) = $this->_actionName;
    $this->resetPage($pageName, TRUE);
    $state = $this->_stateMachine->getState($pageName);
    $page = &$this->_pages[$pageName];
    $state->handleNextState($page);
  }

  /**
   * QFC does not provide native support to have different 'submit' buttons.
   * We introduce this notion to QFC by using button specific data. Thus if
   * we have two submit buttons, we could have one displayed as a button and
   * the other as an image, both are of type 'submit'.
   *
   * @return string the name of the button that has been pressed by the user
   * @access public
   */
  function getButtonName() {
    $data = &$this->container();
    return CRM_Utils_Array::value('_qf_button_name', $data);
  }

  /**
   * function to destroy all the session state of the controller.
   *
   * @access public
   *
   * @return void
   */
  function reset() {
    $data = &$this->container(TRUE);
    $class = get_class($this);
    if (strstr($class, 'Form')) {
      $data['expired'] = CRM_REQUEST_TIME + CRM_Core_Session::EXPIRED_TIME_LONG;
    }
    else {
      $data['expired'] = CRM_REQUEST_TIME + CRM_Core_Session::EXPIRED_TIME;
    }
    self::$_session->resetScope($this->_scope);
  }

  /**
   * virtual function to do any processing of data.
   * Sometimes it is useful for the controller to actually process data.
   * This is typically used when we need the controller to figure out
   * what pages are potentially involved in this wizard. (this is dynamic
   * and can change based on the arguments
   *
   * @return void
   * @access public
   */
  function process() {}

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

  /**
   * Create the header for the wizard from the list of pages
   * Store the created header in smarty
   *
   * @param string $currentPageName name of the page being displayed
   *
   * @return array
   * @access public
   */
  function wizardHeader($currentPageName) {
    $wizard = array();
    $wizard['steps'] = array();
    $count = 0;
    foreach ($this->_pages as $name => $page) {
      $count++;
      $wizard['steps'][] = array('name' => $name,
        'title' => $page->getTitle(),
        //'link'      => $page->getLink ( ),
        'link' => NULL,
        'step' => TRUE,
        'valid' => TRUE,
        'stepNumber' => $count,
        'collapsed' => FALSE,
      );

      if ($name == $currentPageName) {
        $wizard['currentStepNumber'] = $count;
        $wizard['currentStepName'] = $name;
        $wizard['currentStepTitle'] = $page->getTitle();
      }
    }

    $wizard['stepCount'] = $count;

    $this->addWizardStyle($wizard);

    $this->assign('wizard', $wizard);
    return $wizard;
  }

  function addWizardStyle(&$wizard) {
    $wizard['style'] = array('barClass' => '',
      'stepPrefixCurrent' => '&raquo;',
      'stepPrefixPast' => '&#x2714;',
      'stepPrefixFuture' => ' ',
      'subStepPrefixCurrent' => '&nbsp;&nbsp;',
      'subStepPrefixPast' => '&nbsp;&nbsp;',
      'subStepPrefixFuture' => '&nbsp;&nbsp;',
      'showTitle' => 1,
    );
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

  function assign_by_ref($var, &$value) {
    self::$_template->assign_by_ref($var, $value);
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
   * setter for skipRedirection
   *
   * @param boolean $skipRedirection
   *
   * @return void
   * @access public
   */
  function setSkipRedirection($skipRedirection) {
    $this->_skipRedirection = $skipRedirection;
  }

  /**
   * getter for skipRedirection
   *
   * @return boolean return the skipRedirection value
   * @access public
   */
  function getSkipRedirection() {
    return $this->_skipRedirection;
  }

  function setWord($fileName = NULL) {
    //Mark as a CSV file.
    header('Content-Type: application/vnd.ms-word');

    //Force a download and name the file using the current timestamp.
    if (!$fileName) {
      $fileName = 'Contacts_' . CRM_REQUEST_TIME . '.doc';
    }
    header("Content-Disposition: attachment; filename=Contacts_$fileName");
  }

  function setExcel($fileName = NULL) {
    //Mark as an excel file.
    header('Content-Type: application/vnd.ms-excel');

    //Force a download and name the file using the current timestamp.
    if (!$fileName) {
      $fileName = 'Contacts_' . CRM_REQUEST_TIME . '.xls';
    }

    header("Content-Disposition: attachment; filename=Contacts_$fileName");
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
    if ($print == "xls") {
      $this->setExcel();
    }
    elseif ($print == "doc") {
      $this->setWord();
    }
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

  function getTemplateFile() {
    if ($this->_print) {
      if ($this->_print == CRM_Core_Smarty::PRINT_PAGE) {
        return 'CRM/common/print.tpl';
      }
      elseif ($this->_print == 'xls' || $this->_print == 'doc') {
        return 'CRM/Contact/Form/Task/Excel.tpl';
      }
      else {
        return 'CRM/common/snippet.tpl';
      }
    }
    else {
      $config = CRM_Core_Config::singleton();
      return 'CRM/common/' . strtolower($config->userFramework) . '.tpl';
    }
  }

  public function addUploadAction($uploadDir, $uploadNames) {
    if (empty($uploadDir)) {
      $config = CRM_Core_Config::singleton();
      $uploadDir = empty($config->uploadDir) ? CRM_Utils_System::cmsDir('temp') .'/' : $config->uploadDir;
    }

    if (empty($uploadNames)) {
      $uploadNames = $this->get('uploadNames');
      if (!empty($uploadNames)) {
        $uploadNames = array_merge($uploadNames,
          CRM_Core_BAO_File::uploadNames()
        );
      }
      else {
        $uploadNames = CRM_Core_BAO_File::uploadNames();
      }
    }

    $action = new CRM_Core_QuickForm_Action_Upload($this->_stateMachine,
      $uploadDir,
      $uploadNames
    );
    $this->addAction('upload', $action);
    $action = new CRM_Core_QuickForm_Action_Attach($this->_stateMachine,
      $uploadDir,
      $uploadNames
    );
    $this->addAction('attach', $action);
  }

  public function setParent($parent) {
    $this->_parent = $parent;
  }

  public function getParent() {
    return $this->_parent;
  }

  public function getDestination() {
    return $this->_destination;
  }

  public function setDestination($url = NULL, $setToReferer = FALSE) {
    if (empty($url)) {
      if ($setToReferer) {
        $url = $_SERVER['HTTP_REFERER'];
      }
      else {
        $config = CRM_Core_Config::singleton();
        $url = $config->userFrameworkBaseURL;
      }
    }

    $this->_destination = $url;
    $this->set('destination', $this->_destination);
  }
}

