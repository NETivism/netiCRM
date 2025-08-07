<?php
// $Id$

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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




require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Test class for UFGroup API - civicrm_uf_*
 * @todo Split UFGroup and UFJoin tests
 *
 *  @package   CiviCRM
 */
class api_v3_UFFieldTest extends CiviUnitTestCase {
  // ids from the uf_group_test.xml fixture
  protected $_ufGroupId = 11;
  protected $_ufFieldId;
  protected $_contactId = 69;
  protected $_apiversion;
  protected $_params;
  protected $_entity = 'UFField';
  public $_eNoticeCompliant = TRUE;
  protected function setUp() {
    parent::setUp();
    $this->quickCleanup(
      [
        'civicrm_group',
        'civicrm_contact',
        'civicrm_uf_group',
        'civicrm_uf_field',
        'civicrm_uf_join',
        'civicrm_uf_match',
      ]
    );

    $this->_apiversion = 3;
    $op = new PHPUnit_Extensions_Database_Operation_Insert;
    $op->execute(
      $this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(dirname(__FILE__) . '/dataset/uf_group_test.xml')
    );
    $this-> _sethtmlGlobals();

    $this->_params = [
      'field_name' => 'country',
      'field_type' => 'Contact',
      'visibility' => 'Public Pages and Listings',
      'weight' => 1,
      'label' => 'Test Country',
      'is_searchable' => 1,
      'is_active' => 1,
      'location_type_id' => 1,
      'version' => $this->_apiversion,
      'uf_group_id' => $this->_ufGroupId,
    ];
  }

  function tearDown() {
    $this->quickCleanup(
      [
        'civicrm_group',
        'civicrm_contact',
        'civicrm_uf_group',
        'civicrm_uf_join',
        'civicrm_uf_match',
      ]
    );
  }

  /**
   * create / updating field
   */
  public function testCreateUFField() {
    $params = [
      'field_name' => 'country',
      'field_type' => 'Contact',
      'visibility' => 'Public Pages and Listings',
      'weight' => 1,
      'label' => 'Test Country',
      'is_searchable' => 1,
      'is_active' => 1,
      'version' => $this->_apiversion,
      'uf_group_id' => $this->_ufGroupId,
    ];
    $ufField = civicrm_api('uf_field', 'create', $params);
    $this->documentMe($params, $ufField, __FUNCTION__, __FILE__);
    unset($params['version']);
    unset($params['uf_group_id']);
    $this->_ufFieldId = $ufField['id'];
    $this->assertEquals(0, $ufField['is_error'], " in line " . __LINE__);
    foreach ($params as $key => $value) {
      $this->assertEquals($ufField['values'][$ufField['id']][$key], $params[$key]);
    }
  }

  function testCreateUFFieldWithEmptyParams() {
    $params = [];
    $result = civicrm_api('uf_field', 'create', $params);
    $this->assertEquals($result['is_error'], 1);
  }

  function testCreateUFFieldWithWrongParams() {
    $result = civicrm_api('uf_field', 'create', ['field_name' => 'test field']);
    $this->assertEquals($result['is_error'], 1);
    $result = civicrm_api('uf_field', 'create', 'a string');
    $this->assertEquals($result['is_error'], 1);
    $result = civicrm_api('uf_field', 'create', ['label' => 'name-less field']);
    $this->assertEquals($result['is_error'], 1);
  }

  /**
   * deleting field
   */
  public function testDeleteUFField() {

    $ufField = civicrm_api('uf_field', 'create', $this->_params);
    $this->assertAPISuccess($ufField, 'in line' . __LINE__);
    $this->_ufFieldId = $ufField['id'];
    $params = [
      'version' => $this->_apiversion,
      'field_id' => $ufField['id'],
    ];
    $result = civicrm_api('uf_field', 'delete', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 0, 'in line' . __LINE__);
  }

  public function testGetUFFieldSuccess() {

    civicrm_api($this->_entity, 'create', $this->_params);
    $params = ['version' => 3];
    $result = civicrm_api($this->_entity, 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 0, 'in line' . __LINE__);
    $this->getAndCheck($this->_params, $result['id'], $this->_entity);
  }

/*
 *  FIXME: something NULLs $GLOBALS['_HTML_QuickForm_registered_rules'] when the tests are ran all together
 * (NB unclear if this is still required)
 */
  function _sethtmlGlobals() {
   $GLOBALS['_HTML_QuickForm_registered_rules'] = [
      'required' => [
        'html_quickform_rule_required',
        'HTML/QuickForm/Rule/Required.php'
      ],
      'maxlength' => [
        'html_quickform_rule_range',
        'HTML/QuickForm/Rule/Range.php'
      ],
      'minlength' => [
        'html_quickform_rule_range',
        'HTML/QuickForm/Rule/Range.php'
      ],
      'rangelength' => [
        'html_quickform_rule_range',
        'HTML/QuickForm/Rule/Range.php'
      ],
      'email' => [
        'html_quickform_rule_email',
        'HTML/QuickForm/Rule/Email.php'
      ],
      'regex' => [
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php'
      ],
      'lettersonly' => [
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php'
      ],
      'alphanumeric' => [
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php'
      ],
      'numeric' => [
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php'
      ],
      'nopunctuation' => [
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php'
      ],
      'nonzero' => [
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php'
      ],
      'callback' => [
        'html_quickform_rule_callback',
        'HTML/QuickForm/Rule/Callback.php'
      ],
      'compare' => [
        'html_quickform_rule_compare',
        'HTML/QuickForm/Rule/Compare.php'
      ]
    ];
    // FIXME: …ditto for $GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']
    $GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES'] = [
      'group' => [
        'HTML/QuickForm/group.php',
        'HTML_QuickForm_group'
      ],
      'hidden' => [
        'HTML/QuickForm/hidden.php',
        'HTML_QuickForm_hidden'
      ],
      'reset' => [
        'HTML/QuickForm/reset.php',
        'HTML_QuickForm_reset'
      ],
      'checkbox' => [
        'HTML/QuickForm/checkbox.php',
        'HTML_QuickForm_checkbox'
      ],
      'file' => [
        'HTML/QuickForm/file.php',
        'HTML_QuickForm_file'
      ],
      'image' => [
        'HTML/QuickForm/image.php',
        'HTML_QuickForm_image'
      ],
      'password' => [
        'HTML/QuickForm/password.php',
        'HTML_QuickForm_password'
      ],
      'radio' => [
        'HTML/QuickForm/radio.php',
        'HTML_QuickForm_radio'
      ],
      'button' => [
        'HTML/QuickForm/button.php',
        'HTML_QuickForm_button'
      ],
      'submit' => [
        'HTML/QuickForm/submit.php',
        'HTML_QuickForm_submit'
      ],
      'select' => [
        'HTML/QuickForm/select.php',
        'HTML_QuickForm_select'
      ],
      'hiddenselect' => [
        'HTML/QuickForm/hiddenselect.php',
        'HTML_QuickForm_hiddenselect'
      ],
      'text' => [
        'HTML/QuickForm/text.php',
        'HTML_QuickForm_text'
      ],
      'textarea' => [
        'HTML/QuickForm/textarea.php',
        'HTML_QuickForm_textarea'
      ],
      'fckeditor' => [
        'HTML/QuickForm/fckeditor.php',
        'HTML_QuickForm_FCKEditor'
      ],
      'tinymce' => [
        'HTML/QuickForm/tinymce.php',
        'HTML_QuickForm_TinyMCE'
      ],
      'dojoeditor' => [
        'HTML/QuickForm/dojoeditor.php',
        'HTML_QuickForm_dojoeditor'
      ],
      'link' => [
        'HTML/QuickForm/link.php',
        'HTML_QuickForm_link'
      ],
      'advcheckbox' => [
        'HTML/QuickForm/advcheckbox.php',
        'HTML_QuickForm_advcheckbox'
      ],
      'date' => [
        'HTML/QuickForm/date.php',
        'HTML_QuickForm_date'
      ],
      'static' => [
        'HTML/QuickForm/static.php',
        'HTML_QuickForm_static'
      ],
      'header' => [
        'HTML/QuickForm/header.php',
        'HTML_QuickForm_header'
      ],
      'html' => [
        'HTML/QuickForm/html.php',
        'HTML_QuickForm_html'
      ],
      'hierselect' => [
        'HTML/QuickForm/hierselect.php',
        'HTML_QuickForm_hierselect'
      ],
      'autocomplete' => [
        'HTML/QuickForm/autocomplete.php',
        'HTML_QuickForm_autocomplete'
      ],
      'xbutton' => [
        'HTML/QuickForm/xbutton.php',
        'HTML_QuickForm_xbutton'
      ],
      'advmultiselect' => [
        'HTML/QuickForm/advmultiselect.php',
        'HTML_QuickForm_advmultiselect'
      ]
    ];
  }
}