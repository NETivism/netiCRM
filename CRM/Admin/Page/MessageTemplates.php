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

require_once 'CRM/Core/Page/Basic.php';

/**
 * Page for displaying list of message templates
 */
class CRM_Admin_Page_MessageTemplates extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  // ids of templates which diverted from the default ones and can be reverted
  protected $_revertible = array();

  // set to the id that we’re reverting at the given moment (if we are)
  protected $_revertedId;
  function __construct($title = NULL, $mode = NULL) {
    parent::__construct($title, $mode);

    // fetch the ids of templates which diverted from defaults and can be reverted –
    // these templates have the same workflow_id as the defaults; defaults are reserved
    $sql = '
            SELECT diverted.id, orig.id orig_id
            FROM civicrm_msg_template diverted JOIN civicrm_msg_template orig ON (
                diverted.workflow_id = orig.workflow_id AND
                orig.is_reserved = 1                    AND (
                    diverted.msg_subject != orig.msg_subject OR
                    diverted.msg_text    != orig.msg_text    OR
                    diverted.msg_html    != orig.msg_html
                )
            )
            WHERE diverted.workflow_id IS NOT NULL AND orig.workflow_id IS NOT NULL
        ';
    $dao = &CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $this->_revertible[$dao->id] = $dao->orig_id;
    }
  }

  /**
   * Get BAO Name
   *
   * @return string Classname of BAO.
   */
  function getBAOName() {
    return 'CRM_Core_BAO_MessageTemplates';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {
    if (!(self::$_links)) {
      $confirm = ts('Are you sure you want to revert this template to the default for this workflow? You will lose any customizations you have made.', array('escape' => 'js')) . '\n\n' . ts('We recommend that you save a copy of the your customized Text and HTML message content to a text file before reverting so you can combine your changes with the system default messages as needed.', array('escape' => 'js'));
      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/messageTemplates/add',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit this message template'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Core_BAO_MessageTemplates' . '\',\'' . 'enable-disable' . '\' );"',
          'ref' => 'disable-action',
          'title' => ts('Disable this message template'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Core_BAO_MessageTemplates' . '\',\'' . 'disable-enable' . '\' );"',
          'ref' => 'enable-action',
          'title' => ts('Enable this message template'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/messageTemplates',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete this message template'),
        ),
        CRM_Core_Action::REVERT => array(
          'name' => ts('Revert to Default'),
          'extra' => "onclick = 'return confirm(\"$confirm\");'",
          'url' => 'civicrm/admin/messageTemplates',
          'qs' => 'action=revert&id=%%id%%&selectedChild=workflow',
          'title' => ts('Revert this workflow message template to the system default'),
        ),
        CRM_Core_Action::VIEW => array(
          'name' => ts('View Default'),
          'url' => 'civicrm/admin/messageTemplates',
          'qs' => 'action=view&id=%%orig_id%%&reset=1',
          'title' => ts('View the system default for this workflow message template'),
        ),
      );
    }
    return self::$_links;
  }

  function action(&$object, $action, &$values, &$links, $permission, $forceAction = null){
    if ($object->workflow_id) {
      // do not expose action link for reverting to default if the template did not diverge or we just reverted it now
      if (!in_array($object->id, array_keys($this->_revertible)) or
        ($this->_action & CRM_Core_Action::REVERT and $object->id == $this->_revertedId)
      ) {
        $action &= ~CRM_Core_Action::REVERT;
        $action &= ~CRM_Core_Action::VIEW;
      }

      // default workflow templates shouldn’t be deletable
      // workflow templates shouldn’t have disable/enable actions (at least for CiviCRM 3.1)
      if ($object->workflow_id) {
        $action &= ~CRM_Core_Action::DISABLE;
        $action &= ~CRM_Core_Action::DELETE;
      }

      // rebuild the action links HTML, as we need to handle %%orig_id%% for revertible templates
      $values['action'] = CRM_Core_Action::formLink($links, $action, array('id' => $object->id,
          'orig_id' => CRM_Utils_Array::value($object->id, $this->_revertible),
        ));
    }
    else {
      $action &= ~CRM_Core_Action::REVERT;
      $action &= ~CRM_Core_Action::VIEW;
      parent::action($object, $action, $values, $links, $permission);
    }
  }

  function run($args = NULL, $pageArgs = NULL, $sort = NULL) {
    // handle the revert action and offload the rest to parent
    if (CRM_Utils_Request::retrieve('action', 'String', $this) & CRM_Core_Action::REVERT) {

      $id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
      if (!$this->checkPermission($id, NULL)) {
        return CRM_Core_Error::statusBounce(ts('You do not have permission to revert this template.'));
      }

      $this->_revertedId = $id;

      require_once 'CRM/Core/BAO/MessageTemplates.php';
      CRM_Core_BAO_MessageTemplates::revert($id);
    }

    $this->assign('selectedChild', CRM_Utils_Request::retrieve('selectedChild', 'String', $this));

    return parent::run($args, $pageArgs, $sort);
  }

  /**
   * Get name of edit form
   *
   * @return string Classname of edit form.
   */
  function editForm() {
    return 'CRM_Admin_Form_MessageTemplates';
  }

  /**
   * Get edit form name
   *
   * @return string name of this page.
   */
  function editName() {
    return ts('Message Template');
  }

  /**
   * Get user context.
   *
   * @return string user context.
   */
  function userContext($mode = NULL) {
    return 'civicrm/admin/messageTemplates';
  }

  /**
   * browse all entities.
   *
   * @param int $action
   *
   * @return void
   * @access public
   */
  function browse($action = NULL, $sort = NULL) {
    $config = CRM_Core_Config::singleton();
    if ($this->_action & CRM_Core_Action::ADD) {
      return;
    }
    $links = $this->links();
    if ($action == NULL) {
      if (!empty($links)) {
        $action = array_sum(array_keys($links));
      }
    }

    if ($action & CRM_Core_Action::DISABLE) {
      $action -= CRM_Core_Action::DISABLE;
    }

    if ($action & CRM_Core_Action::ENABLE) {
      $action -= CRM_Core_Action::ENABLE;
    }

    $messageTemplate = new CRM_Core_BAO_MessageTemplates();
    $messageTemplate->select('id, msg_title, msg_subject, workflow_id, is_reserved, is_active');
    $messageTemplate->orderBy('msg_title' . ' asc');

    $userTemplates = array();
    $workflowTemplates = array();

    // find all objects
    $messageTemplate->find();
    while ($messageTemplate->fetch()) {
      $values[$messageTemplate->id] = array();
      CRM_Core_DAO::storeValues($messageTemplate, $values[$messageTemplate->id]);
      // populate action links
      $this->action($messageTemplate, $action, $values[$messageTemplate->id], $links, CRM_Core_Permission::EDIT);

      if (!$messageTemplate->workflow_id) {
        $userTemplates[$messageTemplate->id] = $values[$messageTemplate->id];
      }
      elseif (!$messageTemplate->is_reserved) {
        $workflowTemplates[$messageTemplate->id] = $values[$messageTemplate->id];
        if ($config->debug) {
          $workflow = CRM_Core_BAO_MessageTemplates::getMessageTemplateNames($messageTemplate->workflow_id);
          $workflowTemplates[$messageTemplate->id]['workflow'] = $workflow;
        }
      }
    }

    $rows = array(
      'userTemplates' => $userTemplates,
      'workflowTemplates' => $workflowTemplates,
    );

    $this->assign('rows', $rows);
  }
}

