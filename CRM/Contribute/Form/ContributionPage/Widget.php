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


class CRM_Contribute_Form_ContributionPage_Widget extends CRM_Contribute_Form_ContributionPage {
  public $_fields;
  public $_colorFields;
  public $_refreshButtonName;
  protected $_colors;

  protected $_widget;
  
  function preProcess() {
    parent::preProcess();


    $this->_widget = new CRM_Contribute_DAO_Widget();
    $this->_widget->contribution_page_id = $this->_id;
    if (!$this->_widget->find(TRUE)) {
      $this->_widget = NULL;
    }
    else {
      $this->assign('widget_id', $this->_widget->id);

      // check of home url is set, if set then it flash widget might be in use.
      $this->assign('showStatus', FALSE);
      if ($this->_widget->url_homepage) {
        $this->assign('showStatus', TRUE);
      }
      // set iframe code
      $template = CRM_Core_Smarty::singleton();
      $template->assign("iframeSrc", CRM_Utils_System::url('civicrm/contribute/widget', 'embed=1&id='.$this->_id, TRUE, NULL, FALSE));
      $template->assign("iframeWidth", '300px');
      $iframeCode = $template->fetch('CRM/common/iframe.tpl');
      $this->assign('iframeCode', $iframeCode);
      $this->assign('iframePlain', htmlentities($iframeCode, ENT_NOQUOTES, 'UTF-8'));
    }

    $this->assign('cpageId', $this->_id);

    $config = CRM_Core_Config::singleton();
    $title = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage',
      $this->_id,
      'title'
    );

    $this->_fields = ['title' => [ts('Title'),
        'text',
        FALSE,
        $title,
      ],
      'url_logo' => [ts('URL to Logo Image'),
        'text',
        FALSE,
        NULL,
      ],
      'button_title' => [ts('Button Title'),
        'text',
        FALSE,
        ts('Contribute!'),
      ],
    ];

    $this->_colorFields = ['color_title' => [ts('Title Text Color'),
        'text',
        FALSE,
        '#555555',
      ],
      'color_bar' => [ts('Progress Bar Color'),
        'text',
        FALSE,
        '#76CC1E',
      ],
      'color_main_text' => [ts('Additional Text Color'),
        'text',
        FALSE,
        '#999',
      ],
      'color_main' => [ts('Background Color'),
        'text',
        FALSE,
        '#EEF2EA',
      ],
      'color_main_bg' => [ts('Background Color Top Area'),
        'text',
        FALSE,
        '#FFFFFF',
      ],
      'color_bg' => [ts('Border Color'),
        'text',
        FALSE,
        '#EEF2EA',
      ],
      'color_about_link' => [ts('Button Link Color'),
        'text',
        FALSE,
        '#FFFFFF',
      ],
      'color_button' => [ts('Button Background Color'),
        'text',
        FALSE,
        '#78EB05',
      ],
      'color_homepage_link' => [ts('Homepage Link Color'),
        'text',
        FALSE,
        '#555555',
      ],
    ];
  }

  function setDefaultValues() {
    $defaults = [];
    // check if there is a widget already created
    if ($this->_widget) {
      CRM_Core_DAO::storeValues($this->_widget, $defaults);
    }
    else {
      foreach ($this->_fields as $name => $val) {
        $defaults[$name] = $val[3];
      }
      foreach ($this->_colorFields as $name => $val) {
        $defaults[$name] = $val[3];
      }
      $defaults['about'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage',
        $this->_id,
        'intro_text'
      );
    }


    $showHide = new CRM_Core_ShowHideBlocks();
    $showHide->addHide("id-colors");
    $showHide->addToTemplate();
    return $defaults;
  }

  function buildQuickForm() {
    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Widget');

    $this->addElement('checkbox',
      'is_active',
      ts('Enable Widget?'),
      NULL,
      ['onclick' => "widgetBlock(this)"]
    );

    $this->addWysiwyg('about', ts('About'), $attributes['about']);

    foreach ($this->_fields as $name => $val) {
      $this->add($val[1],
        $name,
        $val[0],
        $attributes[$name],
        $val[2]
      );
    }
    foreach ($this->_colorFields as $name => $val) {
      $this->add($val[1],
        $name,
        $val[0],
        $attributes[$name],
        $val[2]
      );
    }

    $this->assign_by_ref('fields', $this->_fields);
    $this->assign_by_ref('colorFields', $this->_colorFields);

    $this->_refreshButtonName = $this->getButtonName('refresh');
    $this->addElement('submit',
      $this->_refreshButtonName,
      ts('Save and Preview')
    );
    parent::buildQuickForm();
    $this->addFormRule(['CRM_Contribute_Form_ContributionPage_Widget', 'formRule'], $this);
  }

  /**
   * Function for validation
   *
   * @param array $params (ref.) an assoc array of name/value pairs
   *
   * @return mixed true or array of errors
   * @access public
   * @static
   */
  public static function formRule($params, $files, $self) {
    $errors = [];
    if (CRM_Utils_Array::value('is_active', $params)) {
      if (!CRM_Utils_Array::value('title', $params)) {
        $errors['title'] = ts('Title is a required field.');
      }
      if (!CRM_Utils_Array::value('about', $params)) {
        $errors['about'] = ts('About is a required field.');
      }

      foreach ($params as $key => $val) {
        if (substr($key, 0, 6) == 'color_' && !CRM_Utils_Array::value($key, $params)) {
          $errors[$key] = ts('%1 is a required field.', [1 => $self->_colorFields[$key][0]]);
        }
      }
    }
    return empty($errors) ? TRUE : $errors;
  }

  function postProcess() {
    //to reset quickform elements of next (pcp) page.
    if ($this->controller->getNextName('Widget') == 'PCP') {
      $this->controller->resetPage('PCP');
    }

    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    if ($this->_widget) {
      $params['id'] = $this->_widget->id;
    }
    $params['contribution_page_id'] = $this->_id;
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['url_homepage'] = 'null';


    $widget = new CRM_Contribute_DAO_Widget();
    $widget->copyValues($params);
    $widget->save();

    $buttonName = $this->controller->getButtonName();
    if ($buttonName = $this->_refreshButtonName) {
      return;
    }
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Widget Settings');
  }
}

