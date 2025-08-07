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
class api_v3_UFJoinTest extends CiviUnitTestCase {
  // ids from the uf_group_test.xml fixture
  protected $_ufGroupId = 11;
  protected $_ufFieldId;
  protected $_contactId = 69;
  protected $_apiversion;
  public $_eNoticeCompliant = TRUE;
  protected function setUp() {
    parent::setUp();
    //  Truncate the tables
    $this->quickCleanup(
      [
        'civicrm_group',
        'civicrm_contact',
        'civicrm_uf_group',
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

    // FIXME: something NULLs $GLOBALS['_HTML_QuickForm_registered_rules'] when the tests are ran all together
    $GLOBALS['_HTML_QuickForm_registered_rules'] = [
      'required' => ['html_quickform_rule_required', 'HTML/QuickForm/Rule/Required.php'],
      'maxlength' => ['html_quickform_rule_range', 'HTML/QuickForm/Rule/Range.php'],
      'minlength' => ['html_quickform_rule_range', 'HTML/QuickForm/Rule/Range.php'],
      'rangelength' => ['html_quickform_rule_range', 'HTML/QuickForm/Rule/Range.php'],
      'email' => ['html_quickform_rule_email', 'HTML/QuickForm/Rule/Email.php'],
      'regex' => ['html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'],
      'lettersonly' => ['html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'],
      'alphanumeric' => ['html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'],
      'numeric' => ['html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'],
      'nopunctuation' => ['html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'],
      'nonzero' => ['html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'],
      'callback' => ['html_quickform_rule_callback', 'HTML/QuickForm/Rule/Callback.php'],
      'compare' => ['html_quickform_rule_compare', 'HTML/QuickForm/Rule/Compare.php'],
    ];
    // FIXME: …ditto for $GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']
    $GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES'] = [
      'group' => ['HTML/QuickForm/group.php', 'HTML_QuickForm_group'],
      'hidden' => ['HTML/QuickForm/hidden.php', 'HTML_QuickForm_hidden'],
      'reset' => ['HTML/QuickForm/reset.php', 'HTML_QuickForm_reset'],
      'checkbox' => ['HTML/QuickForm/checkbox.php', 'HTML_QuickForm_checkbox'],
      'file' => ['HTML/QuickForm/file.php', 'HTML_QuickForm_file'],
      'image' => ['HTML/QuickForm/image.php', 'HTML_QuickForm_image'],
      'password' => ['HTML/QuickForm/password.php', 'HTML_QuickForm_password'],
      'radio' => ['HTML/QuickForm/radio.php', 'HTML_QuickForm_radio'],
      'button' => ['HTML/QuickForm/button.php', 'HTML_QuickForm_button'],
      'submit' => ['HTML/QuickForm/submit.php', 'HTML_QuickForm_submit'],
      'select' => ['HTML/QuickForm/select.php', 'HTML_QuickForm_select'],
      'hiddenselect' => ['HTML/QuickForm/hiddenselect.php', 'HTML_QuickForm_hiddenselect'],
      'text' => ['HTML/QuickForm/text.php', 'HTML_QuickForm_text'],
      'textarea' => ['HTML/QuickForm/textarea.php', 'HTML_QuickForm_textarea'],
      'fckeditor' => ['HTML/QuickForm/fckeditor.php', 'HTML_QuickForm_FCKEditor'],
      'dojoeditor' => ['HTML/QuickForm/dojoeditor.php', 'HTML_QuickForm_dojoeditor'],
      'link' => ['HTML/QuickForm/link.php', 'HTML_QuickForm_link'],
      'advcheckbox' => ['HTML/QuickForm/advcheckbox.php', 'HTML_QuickForm_advcheckbox'],
      'date' => ['HTML/QuickForm/date.php', 'HTML_QuickForm_date'],
      'static' => ['HTML/QuickForm/static.php', 'HTML_QuickForm_static'],
      'header' => ['HTML/QuickForm/header.php', 'HTML_QuickForm_header'],
      'html' => ['HTML/QuickForm/html.php', 'HTML_QuickForm_html'],
      'hierselect' => ['HTML/QuickForm/hierselect.php', 'HTML_QuickForm_hierselect'],
      'autocomplete' => ['HTML/QuickForm/autocomplete.php', 'HTML_QuickForm_autocomplete'],
      'xbutton' => ['HTML/QuickForm/xbutton.php', 'HTML_QuickForm_xbutton'],
      'advmultiselect' => ['HTML/QuickForm/advmultiselect.php', 'HTML_QuickForm_advmultiselect'],
    ];
  }

  function tearDown() {
    //  Truncate the tables
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
   * find uf join group id
   */
  public function testFindUFGroupId() {
    $params = [
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $this->_ufGroupId,
      'is_active' => 1,
      'version' => $this->_apiversion,
    ];
    $ufJoin = civicrm_api('uf_join', 'create', $params);

    $searchParams = [
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'version' => $this->_apiversion,
    ];
    $result = civicrm_api('uf_join', 'get', $searchParams);

    foreach ($result['values'] as $key => $value) {
      $this->assertEquals($value['uf_group_id'], $this->_ufGroupId, 'In line ' . __LINE__);
    }
  }


  public function testUFJoinEditWrongParamsType() {
    $params = 'a string';
    $result = civicrm_api('uf_join', 'create', $params);

    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Input variable `params` is not an array', 'In line ' . __LINE__);
  }

  public function testUFJoinEditEmptyParams() {
    $params = [];
    $result = civicrm_api('uf_join', 'create', $params);

    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: version, module, weight, uf_group_id', 'In line ' . __LINE__);
  }

  public function testUFJoinEditWithoutUFGroupId() {
    $params = [
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'is_active' => 1,
      'version' => $this->_apiversion,
    ];
    $result = civicrm_api('uf_join', 'create', $params);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: uf_group_id', 'In line ' . __LINE__);
  }

  /**
   * create/update uf join
   */
  public function testCreateUFJoin() {
    $params = [
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $this->_ufGroupId,
      'is_active' => 1,
      'version' => $this->_apiversion,
      'sequential' => 1,
    ];
    $ufJoin = civicrm_api('uf_join', 'create', $params);
    $this->documentMe($params, $ufJoin, __FUNCTION__, __FILE__);
    $this->assertEquals($ufJoin['values'][0]['module'], $params['module'], 'In line ' . __LINE__);
    $this->assertEquals($ufJoin['values'][0]['uf_group_id'], $params['uf_group_id'], 'In line ' . __LINE__);
    $this->assertEquals($ufJoin['values'][0]['is_active'], $params['is_active'], 'In line ' . __LINE__);

    $params = [
      'id' => $ufJoin['id'],
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $this->_ufGroupId,
      'is_active' => 0,
      'version' => $this->_apiversion,
      'sequential' => 1,
    ];
    $ufJoinUpdated = civicrm_api('uf_join', 'create', $params);
    $this->assertEquals($ufJoinUpdated['values'][0]['module'], $params['module'], 'In line ' . __LINE__);
    $this->assertEquals($ufJoinUpdated['values'][0]['uf_group_id'], $params['uf_group_id'], 'In line ' . __LINE__);
    $this->assertEquals($ufJoinUpdated['values'][0]['is_active'], $params['is_active'], 'In line ' . __LINE__);
  }


  public function testFindUFJoinWrongParamsType() {
    $params = 'a string';
    $result = civicrm_api('uf_join', 'create', $params);

    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'Input variable `params` is not an array', 'In line ' . __LINE__);
  }

  public function testFindUFJoinEmptyParams() {
    $params = [];
    $result = civicrm_api('uf_join', 'create', $params);

    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: version, module, weight, uf_group_id', 'In line ' . __LINE__);
  }

  public function testFindUFJoinWithoutUFGroupId() {
    $params = [
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'is_active' => 1,
      'version' => $this->_apiversion,
    ];
    $result = civicrm_api('uf_join', 'create', $params);

    $this->assertEquals($result['is_error'], 1);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: uf_group_id', 'In line ' . __LINE__);
  }

  /**
   * find uf join id
   */
  public function testGetUFJoinId() {
    $params = [
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $this->_ufGroupId,
      'is_active' => 1,
      'version' => $this->_apiversion,
    ];

    $ufJoin = civicrm_api('uf_join', 'create', $params);
    $searchParams = [
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'version' => $this->_apiversion,
      'sequential' => 1,
    ];

    $result = civicrm_api('uf_join', 'get', $searchParams);
    $this->documentMe($searchParams, $result, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][0]['module'], $params['module'], 'In line ' . __LINE__);
    $this->assertEquals($result['values'][0]['uf_group_id'], $params['uf_group_id'], 'In line ' . __LINE__);
    $this->assertEquals($result['values'][0]['entity_id'], $params['entity_id'], 'In line ' . __LINE__);
  }
}

