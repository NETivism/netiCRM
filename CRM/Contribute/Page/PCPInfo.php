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
 * PCP Info Page - Summary about the PCP
 */
class CRM_Contribute_Page_PCPInfo extends CRM_Core_Page {

  /**
   * id for current pcp page
   *
   * @var int
   */
  private $_id;

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   *
   * @return void
   * @access public
   *
   */
  function run() {
    $session = CRM_Core_Session::singleton();
    $config = CRM_Core_Config::singleton();
    $statusMessage = '';
    $permissionCheck = CRM_Core_Permission::check('administer CiviCRM');

    //get the pcp id.
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);

    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE);
    $isEmbed = CRM_Utils_Request::retrieve('embed', 'Boolean', $this);

    // Convert the 'isEmbed' variable to a proper Boolean value.
    // The strtobool method is used here because even if we specify 'Boolean' as the data type,
    // the value retrieved from an HTTP request could still be a string (like "true", "false", "1", "0").
    // The strtobool method ensures that these string representations are correctly interpreted as Boolean values.
    $isEmbed = CRM_Utils_String::strtobool($isEmbed);

    $this->assign('is_embed', $isEmbed);

    $prms = ['id' => $this->_id];

    CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_PCP', $prms, $pcpInfo);
    if (empty($pcpInfo)) {
      $statusMessage = ts('The personal campaign page you requested is currently unavailable.');
      return CRM_Core_Error::statusBounce($statusMessage,
        $config->userFrameworkBaseURL
      );
    }

    $contributionPageParams = ['id' => $pcpInfo['contribution_page_id']];
    CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_ContributionPage', $contributionPageParams, $contributionPageInfo);
    $this->assign('contribution_page', $contributionPageInfo);

    CRM_Utils_System::setTitle($pcpInfo['title']);
    $this->assign('pcp', $pcpInfo);

    $currentUrl = CRM_Utils_System::url(CRM_Utils_System::currentPath(), 'id=' . $this->_id . '&reset=1', ['absolute' => TRUE]);
    $this->assign('currentUrl', $currentUrl);

    $shareData = [
      'url' => urlencode($currentUrl),
      'title' => urlencode($pcpInfo['title']),
    ];
    $this->assign('share_data', $shareData);

    $pcpStatus = CRM_Contribute_PseudoConstant::pcpStatus();
    $approvedId = CRM_Core_OptionGroup::getValue('pcp_status', 'Approved', 'name');

    // check if PCP has status message
    if (!empty(CRM_Core_Session::singleton()->getStatus())) {
      $this->assign('anonMessage', TRUE);
    }
    $statusMessage = ts('The personal campaign page you requested is currently unavailable. However you can still support the campaign by making a contribution here.');

    // check if PCP needs preview for specific user session
    $userID = $session->get('userID');
    $validated = FALSE;
    if (!$permissionCheck && !$userID && $pcpInfo['status_id'] != $approvedId) {
      $qfKeyFromPCPController = CRM_Utils_Request::retrieve('key', 'String', $this);
      $anonyContactId = $session->get('pcpAnonymousContactId');
      $validated = CRM_Core_Key::validate($qfKeyFromPCPController, 'CRM_Contribute_Controller_PCP', TRUE);

      if (empty($validated) || $anonyContactId != $pcpInfo['contact_id']) {
        return CRM_Core_Error::statusBounce($statusMessage, CRM_Utils_System::url('civicrm/contribute/transact',
          "reset=1&id={$pcpInfo['contribution_page_id']}",
          FALSE, NULL, FALSE, TRUE
        ));
      }
      $userID = $anonyContactId;
    }
    elseif ($pcpInfo['status_id'] != $approvedId || !$pcpInfo['is_active']) {
      if ($pcpInfo['contact_id'] != $session->get('userID') && !$permissionCheck) {
        return CRM_Core_Error::statusBounce($statusMessage, CRM_Utils_System::url('civicrm/contribute/transact',
          "reset=1&id={$pcpInfo['contribution_page_id']}",
          FALSE, NULL, FALSE, TRUE
        ));
      }
    }
    else {
      $getStatus = CRM_Contribute_BAO_PCP::getStatus($this->_id);
      if (!$getStatus) {
        // PCP not enabled for this contribution page. Forward everyone to main contribution page
        return CRM_Core_Error::statusBounce($statusMessage, CRM_Utils_System::url('civicrm/contribute/transact',
          "reset=1&id={$pcpInfo['contribution_page_id']}",
          FALSE, NULL, FALSE, TRUE
        ));
      }
    }

    $default = [];

    CRM_Core_DAO::commonRetrieveAll('CRM_Contribute_DAO_ContributionPage', 'id',
      $pcpInfo['contribution_page_id'], $default, ['start_date', 'end_date']
    );

    $this->assign('pageName', CRM_Contribute_PseudoConstant::contributionPage($pcpInfo['contribution_page_id'], TRUE));

    if ($pcpInfo['contact_id'] == $session->get('userID')) {
      $owner = $default[$pcpInfo['contribution_page_id']];
      $owner['status'] = CRM_Utils_Array::value($pcpInfo['status_id'], $pcpStatus);
      $this->assign('owner', $owner);

      $link = CRM_Contribute_BAO_PCP::pcpLinks();
      unset($link['all'][CRM_Core_Action::DELETE]);
      if ($pcpInfo['status_id'] == $approvedId) {
        if ($pcpInfo['is_active']) {
          unset($link['all'][CRM_Core_Action::ENABLE]);
        }
        else {
          unset($link['all'][CRM_Core_Action::DISABLE]);
        }
      }
      else {
        unset($link['all'][CRM_Core_Action::DISABLE]);
        unset($link['all'][CRM_Core_Action::ENABLE]);
      }
      $hints = [
        CRM_Core_Action::UPDATE => ts('Change the content and appearance of your page'),
        CRM_Core_Action::DETACH => ts('Send emails inviting your friends to support your campaign!'),
        CRM_Core_Action::BROWSE => ts('Update your personal contact information'),
        CRM_Core_Action::DISABLE => ts('De-activate the page (you can re-activate it later)'),
      ];
      CRM_Core_DAO::commonRetrieveAll('CRM_Contribute_DAO_PCPBlock', $pcpInfo['contribution_page_id'],
        'entity_id', $blockValues, ['is_tellfriend_enabled']
      );

      $blockId = array_pop($blockValues);
      $replace = ['id' => $this->_id,
        'block' => $blockId['id'],
      ];
      if (!CRM_Utils_Array::value('is_tellfriend_enabled', $blockId) ||
        CRM_Utils_Array::value('status_id', $pcpInfo) != $approvedId
      ) {
        unset($link['all'][CRM_Core_Action::DETACH]);
      }

      $this->assign('links', $link['all']);
      $this->assign('hints', $hints);
      $this->assign('replace', $replace);
    }

    $honor = CRM_Contribute_BAO_PCP::honorRoll($this->_id);

    $images = CRM_Contribute_BAO_PCP::getPcpImages($this->_id);
    if (!empty($images)) {
      $img = reset($images);
      $src = $img[0];
      if ($src) {
        $bgFile = basename($src);
        $encodedSrc = str_replace($bgFile, urlencode($bgFile), $src);
        $meta = [
          'tag' => 'meta',
          'attributes' => [
            'property' => 'og:image',
            'content' => $encodedSrc,
          ],
        ];
        CRM_Utils_System::addHTMLHead($meta);
        $this->assign('pcp_image_src', $encodedSrc);
      }
    }

    $totalAmount = CRM_Contribute_BAO_PCP::thermoMeter($this->_id);
    $achieved = round($totalAmount / $pcpInfo['goal_amount'] * 100, 2);

    $linkDisplay = FALSE;
    if ($linkText = CRM_Contribute_BAO_PCP::getPcpBlockStatus($pcpInfo['contribution_page_id'])) {
      $linkTextUrl = CRM_Utils_System::url('civicrm/contribute/campaign',
        "action=add&reset=1&pageId={$pcpInfo['contribution_page_id']}",
        TRUE, NULL, TRUE,
        TRUE
      );
      $this->assign('linkTextUrl', $linkTextUrl);
      $this->assign('linkText', $linkText);
    }

    $this->assign('honor', $honor);
    $this->assign('total', $totalAmount ? $totalAmount : '0.0');
    $this->assign('achieved', floor($achieved));
    $this->assign('achievedPercent', $achieved <= 100 ? $achieved : 100);

    if ($achieved <= 100) {
      $this->assign('remaining', 100 - $achieved);
    }

    $contributionText = ts('Contribute Now');
    if (CRM_Utils_Array::value('donate_link_text', $pcpInfo)) {
      $contributionText = $pcpInfo['donate_link_text'];
    }

    $this->assign('contribution_text', $contributionText);

    // we always generate urls for the front end in joomla
    if ($action == CRM_Core_Action::PREVIEW) {
      $contributeURL = CRM_Utils_System::url('civicrm/contribute/transact',
        "id={$pcpInfo['contribution_page_id']}&pcpId={$this->_id}&reset=1&action=preview",
        TRUE, NULL, TRUE,
        TRUE
      );
    }
    else {
      $contributeURL = CRM_Utils_System::url('civicrm/contribute/transact',
        "id={$pcpInfo['contribution_page_id']}&pcpId={$this->_id}&reset=1",
        TRUE, NULL, TRUE,
        TRUE
      );
    }
    $this->assign('contribute_url', $contributeURL);

    $progress = [
      'type' => 'amount',
      'label' => ts('Goal Amount'),
      'goal' => $pcpInfo['goal_amount'],
      'currency' => $pcpInfo['currency'],
      'fullwidth' => TRUE,
      'display' => $pcpInfo['is_thermometer'],
      'current' => $totalAmount,
      'achieved_percent' => floor($achieved),
      'achieved_status'=> floor($achieved) >= 100 ? TRUE : FALSE,
      'contribution_page_is_active' => $contributionPageInfo['is_active']
    ];

    if ($contributeURL) {
      $linkDisplay = TRUE;
      $progress['link_display'] = $linkDisplay;
      $progress['link_url'] = $contributeURL;
      $progress['link_text'] = $contributionText;
    }
    $this->assign('progress', $progress);
    $this->assign('link_display', $linkDisplay);

    // we do not want to display recently viewed items, so turn off
    $this->assign('displayRecent', FALSE);


    $single = $permission = FALSE;
    switch ($action) {
      case CRM_Core_Action::BROWSE:
        $subForm = 'PCPAccount';
        $form = "CRM_Contribute_Form_PCP_$subForm";
        $single = TRUE;
        break;

      case CRM_Core_Action::UPDATE:
        $subForm = 'Campaign';
        $form = "CRM_Contribute_Form_PCP_$subForm";
        $single = TRUE;
        break;
    }

    //make sure the user has "administer CiviCRM" permission
    //OR has created the PCP
    if (CRM_Core_Permission::check('administer CiviCRM') ||
      ($userID && ($pcpInfo['contact_id'] == $userID)) ||
      !empty($validated) && ($pcpInfo['contact_id'] == $userID) && $pcpInfo['id'] == $session->get('pcpAnonymousPageId')
    ) {
      $permission = TRUE;
    }

    if ($single && $permission) {
      $controller = new CRM_Core_Controller_Simple($form, $subForm, $action);
      $controller->set('id', $this->_id);
      $controller->set('single', TRUE);
      $controller->process();
      return $controller->run();
    }
    $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(), 'reset=1&id=' . $this->_id));
    parent::run();
  }

  function getTemplateFileName() {
    if ($this->_id) {
      $templateFile = "CRM/Contribute/Page/{$this->_id}/PCPInfo.tpl";
      $template = &CRM_Core_Page::getTemplate();
      if ($template->template_exists($templateFile)) {
        return $templateFile;
      }
    }
    return parent::getTemplateFileName();
  }
}

