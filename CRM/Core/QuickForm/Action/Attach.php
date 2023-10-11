<?php
/**
 * Another upload action when form needs 2 upload buttons
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

class CRM_Core_QuickForm_Action_Attach extends CRM_Core_QuickForm_Action {

  /**
   * the array of uploaded file names
   * @var array
   */
  protected $_uploadNames;

  /**
   * The directory to store the uploaded files
   * @var string
   */
  protected $_uploadDir;

  /**
   * class constructor
   *
   * @param object $stateMachine reference to state machine object
   * @param string $uploadDir    directory to store the uploaded files
   * @param array  $uploadNames  element names of the various uploadable files
   *
   * @return object
   * @access public
   */
  function __construct(&$stateMachine, $uploadDir, $uploadNames) {
    parent::__construct($stateMachine);

    $this->_uploadDir = $uploadDir;
    $this->_uploadNames = $uploadNames;
  }

  /**
   * upload and move the file if valid to the uploaded directory
   *
   * @param object $page       the CRM_Core_Form object
   * @param object $data       the QFC data container
   * @param string $pageName   the name of the page which index the data container with
   * @param string $uploadName the name of the uploaded file
   *
   * @return void
   * @access private
   */
  function attach(&$page, &$data, $pageName, $uploadName) {
    // make sure uploadName exists in the QF array
    // else we skip, CRM-3427
    if (empty($uploadName) ||
      !isset($page->_elementIndex[$uploadName])
    ) {
      return;
    }

    // get the element containing the upload
    $element = &$page->getElement($uploadName);
    if ('file' == $element->getType()) {
      if ($element->isUploadedFile()) {
        // rename the uploaded file with a unique number at the end
        $value = $element->getValue();

        if (is_array($value['name'])) {
          $newName = array();
          foreach($value['name'] as $idx => $name) {
            $newName[$idx] = CRM_Utils_File::makeFileName($name);
          }
        }
        else {
          $newName = CRM_Utils_File::makeFileName($value['name']);
        }
        $movedFilename = $element->moveUploadedFile($this->_uploadDir, $newName);
        if (!$movedFilename) {
          return CRM_Core_Error::statusBounce(ts('We could not move the uploaded file %1 to the upload directory %2. Please verify that the \'Temporary Files\' setting points to a valid path which is writable by your web server.', array(1 => $newName, 2 => $this->_uploadDir)));
        }
        if (is_array($newName)) {
          foreach($newName as $idx => $name) {
            $data['values'][$pageName][$uploadName][$idx] = array(
              'name' => $this->_uploadDir . $name,
              'type' => $value['type'][$idx],
            );
          }
        }
        else {
          $data['values'][$pageName][$uploadName] = array(
            'name' => $this->_uploadDir . $newName,
            'type' => $value['type'],
          );
        }
      }
    }
  }

  /**
   * Processes the request.
   *
   * @param  object    $page       CRM_Core_Form the current form-page
   * @param  string    $actionName Current action name, as one Action object can serve multiple actions
   *
   * @return void
   * @access public
   */
  function perform(&$page, $actionName) {
    // like in Action_Next
    $page->isFormBuilt() or $page->buildForm();

    // so this is a brain-seizure moment, so hang tight (real tight!)
    // the above buildForm potentially changes the action function with different args
    // so basically the rug might have been pulled from us, so we actually just check
    // and potentially call the right one
    // this allows standalong form uploads to work nicely
    $page->controller->_actions['attach']->realPerform($page, $actionName);
  }

  function realPerform(&$page, $actionName) {
    $pageName = $page->getAttribute('name');
    $data = &$page->controller->container();
    $data['values'][$pageName] = $page->exportValues();
    $data['valid'][$pageName] = $page->validate();

    if (!$data['valid'][$pageName]) {
      return $page->handle('display');
    }

    foreach ($this->_uploadNames as $name) {
      $this->attach($page, $data, $pageName, $name);
    }

    $state = &$this->_stateMachine->getState($pageName);
    if (empty($state)) {
      return $page->handle('display');
    }

    // the page is valid, process it before we jump to the next state
    $page->mainProcess();

    $state->handleNextState($page);
  }
}

