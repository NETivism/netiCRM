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
 * The Contact Wrapper is a wrapper class which is called by
 * contact.module after it parses the menu path.
 *
 * The key elements of the wrapper are the controller and the
 * run method as explained below.
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id: $
 *
 */

class CRM_Utils_Wrapper {

  /**
   * Simple Controller
   *
   * The controller which will handle the display and processing of this page.
   *
   * @access protected
   */
  protected $_controller;

  /**
   * Instantiate and run the simple controller for the given form.
   *
   * This is the main callback entry point invoked by the CiviCRM menu router.
   * It creates a CRM_Core_Controller_Simple instance, copies URL parameters
   * into the session when requested, and then processes and renders the form.
   *
   * @param string     $formName   Fully-qualified class name of the form to run.
   * @param string     $formLabel  Human-readable label displayed as the page title.
   * @param array|null $arguments  Optional configuration array supporting keys:
   *   - mode         (int)    Controller mode constant.
   *   - imageUpload  (bool)   Enable image upload handling.
   *   - addSequence  (bool)   Add a sequence number to the controller key.
   *   - ignoreKey    (bool)   Ignore the form key validation.
   *   - attachUpload (bool)   Enable attachment upload handling.
   *   - urlToSession (array)  List of URL-variable-to-session mappings.
   *   - setEmbedded  (bool)   Render the form in embedded (no-wrapper) mode.
   *
   * @return void
   */
  public function run($formName, $formLabel, $arguments = NULL) {
    if (is_array($arguments)) {
      $mode = CRM_Utils_Array::value('mode', $arguments);
      $imageUpload = (bool) CRM_Utils_Array::value('imageUpload', $arguments, FALSE);
      $addSequence = (bool) CRM_Utils_Array::value('addSequence', $arguments, FALSE);
      $attachUpload = (bool) CRM_Utils_Array::value('attachUpload', $arguments, FALSE);
      $ignoreKey = (bool) CRM_Utils_Array::value('ignoreKey', $arguments, FALSE);
    }
    else {
      $arguments = [];
      $mode = NULL;
      $addSequence = $ignoreKey = $imageUpload = $attachUpload = FALSE;
    }

    $this->_controller = new CRM_Core_Controller_Simple(
      $formName,
      $formLabel,
      $mode,
      $imageUpload,
      $addSequence,
      $ignoreKey,
      $attachUpload
    );

    if (CRM_Utils_Array::arrayKeyExists('urlToSession', $arguments)) {
      if (is_array($arguments['urlToSession'])) {
        foreach ($arguments['urlToSession'] as $params) {
          $urlVar = CRM_Utils_Array::value('urlVar', $params);
          $sessionVar = CRM_Utils_Array::value('sessionVar', $params);
          $type = CRM_Utils_Array::value('type', $params);
          $default = CRM_Utils_Array::value('default', $params);

          $value = NULL;
          $value = CRM_Utils_Request::retrieve(
            $urlVar,
            $type,
            $this->_controller,
            $default
          );
          $this->_controller->set($sessionVar, $value);
        }
      }
    }

    if (CRM_Utils_Array::arrayKeyExists('setEmbedded', $arguments)) {
      $this->_controller->setEmbedded(TRUE);
    }

    $this->_controller->process();
    $this->_controller->run();
  }
}
