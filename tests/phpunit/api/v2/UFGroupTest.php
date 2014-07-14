<?php
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
require_once 'api/v2/UFGroup.php';
require_once 'api/v2/UFJoin.php';

/**
 * Test class for UFGroup API - civicrm_uf_*
 * @todo Split UFGroup and UFJoin tests
 *
 *  @package   CiviCRM
 */
class api_v2_UFGroupTest extends CiviUnitTestCase {
  // ids from the uf_group_test.xml fixture
  protected $_ufGroupId = 11;
  protected $_ufFieldId;
  protected $_contactId = 69; function tearDown() {

    //  Truncate the tables
    $op = new PHPUnit_Extensions_Database_Operation_Truncate();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/../../CiviTest/truncate-ufgroup.xml'
      )
    );
  }

  protected function setUp() {
    parent::setUp();


    $op = new PHPUnit_Extensions_Database_Operation_Insert;
    $op->execute(
      $this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(dirname(__FILE__) . '/dataset/uf_group_test.xml')
    );

    // FIXME: something NULLs $GLOBALS['_HTML_QuickForm_registered_rules'] when the tests are ran all together
    $GLOBALS['_HTML_QuickForm_registered_rules'] = array(
      'required' => array('html_quickform_rule_required', 'HTML/QuickForm/Rule/Required.php'),
      'maxlength' => array('html_quickform_rule_range', 'HTML/QuickForm/Rule/Range.php'),
      'minlength' => array('html_quickform_rule_range', 'HTML/QuickForm/Rule/Range.php'),
      'rangelength' => array('html_quickform_rule_range', 'HTML/QuickForm/Rule/Range.php'),
      'email' => array('html_quickform_rule_email', 'HTML/QuickForm/Rule/Email.php'),
      'regex' => array('html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'),
      'lettersonly' => array('html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'),
      'alphanumeric' => array('html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'),
      'numeric' => array('html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'),
      'nopunctuation' => array('html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'),
      'nonzero' => array('html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'),
      'callback' => array('html_quickform_rule_callback', 'HTML/QuickForm/Rule/Callback.php'),
      'compare' => array('html_quickform_rule_compare', 'HTML/QuickForm/Rule/Compare.php'),
    );
    // FIXME: …ditto for $GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']
    $GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES'] = array(
      'group' => array('HTML/QuickForm/group.php', 'HTML_QuickForm_group'),
      'hidden' => array('HTML/QuickForm/hidden.php', 'HTML_QuickForm_hidden'),
      'reset' => array('HTML/QuickForm/reset.php', 'HTML_QuickForm_reset'),
      'checkbox' => array('HTML/QuickForm/checkbox.php', 'HTML_QuickForm_checkbox'),
      'file' => array('HTML/QuickForm/file.php', 'HTML_QuickForm_file'),
      'image' => array('HTML/QuickForm/image.php', 'HTML_QuickForm_image'),
      'password' => array('HTML/QuickForm/password.php', 'HTML_QuickForm_password'),
      'radio' => array('HTML/QuickForm/radio.php', 'HTML_QuickForm_radio'),
      'button' => array('HTML/QuickForm/button.php', 'HTML_QuickForm_button'),
      'submit' => array('HTML/QuickForm/submit.php', 'HTML_QuickForm_submit'),
      'select' => array('HTML/QuickForm/select.php', 'HTML_QuickForm_select'),
      'hiddenselect' => array('HTML/QuickForm/hiddenselect.php', 'HTML_QuickForm_hiddenselect'),
      'text' => array('HTML/QuickForm/text.php', 'HTML_QuickForm_text'),
      'textarea' => array('HTML/QuickForm/textarea.php', 'HTML_QuickForm_textarea'),
      'fckeditor' => array('HTML/QuickForm/fckeditor.php', 'HTML_QuickForm_FCKEditor'),
      'tinymce' => array('HTML/QuickForm/tinymce.php', 'HTML_QuickForm_TinyMCE'),
      'dojoeditor' => array('HTML/QuickForm/dojoeditor.php', 'HTML_QuickForm_dojoeditor'),
      'link' => array('HTML/QuickForm/link.php', 'HTML_QuickForm_link'),
      'advcheckbox' => array('HTML/QuickForm/advcheckbox.php', 'HTML_QuickForm_advcheckbox'),
      'date' => array('HTML/QuickForm/date.php', 'HTML_QuickForm_date'),
      'static' => array('HTML/QuickForm/static.php', 'HTML_QuickForm_static'),
      'header' => array('HTML/QuickForm/header.php', 'HTML_QuickForm_header'),
      'html' => array('HTML/QuickForm/html.php', 'HTML_QuickForm_html'),
      'hierselect' => array('HTML/QuickForm/hierselect.php', 'HTML_QuickForm_hierselect'),
      'autocomplete' => array('HTML/QuickForm/autocomplete.php', 'HTML_QuickForm_autocomplete'),
      'xbutton' => array('HTML/QuickForm/xbutton.php', 'HTML_QuickForm_xbutton'),
      'advmultiselect' => array('HTML/QuickForm/advmultiselect.php', 'HTML_QuickForm_advmultiselect'),
    );
  }

  /**
   * fetch profile title by its id
   */
  function testGetUFProfileTitle() {
    $ufProfile = civicrm_uf_profile_title_get($this->_ufGroupId);
    $this->assertEquals($ufProfile, 'Test Profile', 'In line ' . __LINE__);
  }

  function testGetUFProfileTitleWithEmptyParam() {
    $result = civicrm_uf_profile_title_get(array());
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
  }

  function testGetUFProfileTitleWithWrongParam() {
    $result = civicrm_uf_profile_title_get('a string');
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
  }

  /**
   * fetch profile HTML by contact id and profile title
   */
  function testGetUFProfileHTML() {
    $profileHTML = civicrm_uf_profile_html_get($this->_contactId, 'Test Profile');
    // check if html / content is returned
    $this->assertNotNull($profileHTML, 'In line ' . __LINE__);
  }

  function testGetUFProfileHTMLWithWrongParams() {
    $result = civicrm_uf_profile_html_get($this->_contactId, 42);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $result = civicrm_uf_profile_html_get('a string', 'Test Profile');
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
  }

  /**
   * fetch profile HTML by contact id and profile id
   */
  function testGetUFProfileHTMLById() {
    $profileHTML = civicrm_uf_profile_html_by_id_get($this->_contactId, $this->_ufGroupId);
    // check if html / content is returned
    $this->assertNotNull($profileHTML, 'In line ' . __LINE__);
  }

  function testGetUFProfileHTMLByIdWithWrongParams() {
    $result = civicrm_uf_profile_html_by_id_get('a string', $this->_ufGroupId);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $result = civicrm_uf_profile_html_by_id_get($this->_contactId, 'a string');
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
  }



  public function testUFJoinEditWrongParamsType() {
    $params = 'a string';
    $result = civicrm_uf_join_edit($params);

    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'params is not an array', 'In line ' . __LINE__);
  }

  public function testUFJoinEditEmptyParams() {
    $params = array();
    $result = civicrm_uf_join_edit($params);

    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'params is an empty array', 'In line ' . __LINE__);
  }

  public function testUFJoinEditWithoutUFGroupId() {
    $params = array(
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'is_active' => 1,
    );
    $result = civicrm_uf_join_edit($params);

    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'uf_group_id is required field', 'In line ' . __LINE__);
  }

  /**
   * fetch profile HTML with group id
   */
  public function testGetUFProfileCreateHTML() {
    $fieldsParams = array(
      'field_name' => 'first_name',
      'field_type' => 'Individual',
      'visibility' => 'Public Pages and Listings',
      'weight' => 1,
      'label' => 'Test First Name',
      'is_searchable' => 1,
      'is_active' => 1,
    );
    $ufField = civicrm_uf_field_create($this->_ufGroupId, $fieldsParams);

    $joinParams = array(
      'is_active' => 1,
      'module' => 'Profile',
      'weight' => 1,
      'uf_group_id' => $this->_ufGroupId,
    );
    $ufJoin = civicrm_uf_join_edit($joinParams);

    $profileHTML = civicrm_uf_create_html_get($this->_ufGroupId, TRUE);
    $this->assertNotNull($profileHTML, 'In line ' . __LINE__);
  }

  function testGetUFProfileCreateHTMLWithWrongParam() {
    $result = civicrm_uf_create_html_get('a string');
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
  }

  /**
   * creating profile fields / fetch profile fields
   */
  public function testGetUFProfileFields() {
    $params = array(
      'field_name' => 'country',
      'field_type' => 'Contact',
      'visibility' => 'Public Pages and Listings',
      'weight' => 1,
      'label' => 'Test Country',
      'is_searchable' => 1,
      'is_active' => 1,
    );

    $ufField = civicrm_uf_field_create($this->_ufGroupId, $params);
    $this->_ufFieldId = $ufField['id'];

    foreach ($params as $key => $value) {
      $this->assertEquals($ufField[$key], $params[$key], 'In line ' . __LINE__);
    }

    $ufProfile = civicrm_uf_profile_fields_get($this->_ufGroupId);
    $this->assertEquals($ufProfile['country-Primary']['field_type'], $params['field_type'], 'In line ' . __LINE__);
    $this->assertEquals($ufProfile['country-Primary']['title'], $params['label'], 'In line ' . __LINE__);
    $this->assertEquals($ufProfile['country-Primary']['visibility'], $params['visibility'], 'In line ' . __LINE__);
    $this->assertEquals($ufProfile['country-Primary']['group_id'], $this->_ufGroupId, 'In line ' . __LINE__);
    $this->assertEquals($ufProfile['country-Primary']['groupTitle'], 'Test Profile', 'In line ' . __LINE__);
    $this->assertEquals($ufProfile['country-Primary']['groupHelpPre'], 'Profile to Test API', 'In line ' . __LINE__);
  }

  function testGetUFProfileFieldsWithEmptyParam() {
    $result = civicrm_uf_profile_fields_get(array());
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
  }

  function testGetUFProfileFieldsWithWrongParam() {
    $result = civicrm_uf_profile_fields_get('a string');
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
  }

  /**
   * fetch contact id by uf id
   */
  public function testGetUFMatchID() {
    $ufMatchId = civicrm_uf_match_id_get(42);
    $this->assertEquals($ufMatchId, 69, 'In line ' . __LINE__);
  }

  function testGetUFMatchIDWrongParam() {
    $result = civicrm_uf_match_id_get('a string');
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
  }

  /**
   * fetch uf id by contact id
   */
  public function testGetUFID() {
    $ufIdFetced = civicrm_uf_id_get(69);
    $this->assertEquals($ufIdFetced, 42, 'In line ' . __LINE__);
  }

  function testGetUFIDWrongParam() {
    $result = civicrm_uf_id_get('a string');
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
  }

  /**
   * updating group
   */
  public function testUpdateUFGroup() {
    $params = array(
      'title' => 'Edited Test Profile',
      'help_post' => 'Profile Pro help text.',
      'is_active' => 1,
    );

    $updatedGroup = civicrm_uf_group_update($params, $this->_ufGroupId);
    foreach ($params as $key => $value) {
      $this->assertEquals($updatedGroup[$key], $params[$key], 'In line ' . __LINE__);
    }
  }

  /**
   * create / updating field
   */
  public function testCreateUFField() {
    $params = array(
      'field_name' => 'country',
      'field_type' => 'Contact',
      'visibility' => 'Public Pages and Listings',
      'weight' => 1,
      'label' => 'Test Country',
      'is_searchable' => 1,
      'is_active' => 1,
    );
    $ufField = civicrm_uf_field_create($this->_ufGroupId, $params);
    $this->_ufFieldId = $ufField['id'];
    foreach ($params as $key => $value) {
      $this->assertEquals($ufField[$key], $params[$key], 'In line ' . __LINE__);
    }

    $params = array(
      'field_name' => 'country',
      'label' => 'Edited Test Country',
      'weight' => 1,
      'is_active' => 1,
    );

    $updatedField = civicrm_uf_field_update($params, $ufField['id']);
    foreach ($params as $key => $value) {
      $this->assertEquals($updatedField[$key], $params[$key], 'In line ' . __LINE__);
    }
  }

  function testCreateUFFieldWithEmptyParams() {
    $result = civicrm_uf_field_create($this->_ufGroupId, array());
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
  }

  function testCreateUFFieldWithWrongParams() {
    $result = civicrm_uf_field_create('a string', array('field_name' => 'test field'));
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $result = civicrm_uf_field_create($this->_ufGroupId, 'a string');
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $result = civicrm_uf_field_create($this->_ufGroupId, array('label' => 'name-less field'));
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
  }

  /**
   * deleting field
   */
  public function testDeleteUFField() {
    $params = array(
      'field_name' => 'country',
      'field_type' => 'Contact',
      'visibility' => 'Public Pages and Listings',
      'weight' => 1,
      'location_type_id' => 1,
      'label' => 'Test Country',
      'is_searchable' => 1,
      'is_active' => 1,
    );
    $ufField = civicrm_uf_field_create($this->_ufGroupId, $params);
    $this->_ufFieldId = $ufField['id'];
    foreach ($params as $key => $value) {
      $this->assertEquals($ufField[$key], $params[$key], 'In line ' . __LINE__);
    }
    $result = civicrm_uf_field_delete($ufField['id']);
    $this->assertEquals($result, TRUE, 'In line ' . __LINE__);
  }

  /**
   * validate profile html
   */
  public function testValidateProfileHTML() {
    $result = civicrm_profile_html_validate($this->_contactId, 'Test Profile', NULL, NULL);
    $this->assertEquals($result, TRUE, 'In line ' . __LINE__);
  }

  /**
   * create/update uf join
   */
  public function testEditUFJoin() {
    $params = array(
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $this->_ufGroupId,
      'is_active' => 1,
    );
    $ufJoin = civicrm_uf_join_edit($params);
    foreach ($params as $key => $value) {
      $this->assertEquals($ufJoin[$key], $params[$key], 'In line ' . __LINE__);
    }
    $params = array(
      'id' => $ufJoin['id'],
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $this->_ufGroupId,
      'is_active' => 0,
    );
    $ufJoinUpdated = civicrm_uf_join_edit($params);
    foreach ($params as $key => $value) {
      $this->assertEquals($ufJoinUpdated[$key], $params[$key], 'In line ' . __LINE__);
    }
  }


  public function testFindUFJoinWrongParamsType() {
    $params = 'a string';
    $result = civicrm_uf_join_add($params);

    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'params is not an array', 'In line ' . __LINE__);
  }

  public function testFindUFJoinEmptyParams() {
    $params = array();
    $result = civicrm_uf_join_add($params);

    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'params is an empty array', 'In line ' . __LINE__);
  }

  public function testFindUFJoinWithoutUFGroupId() {
    $params = array(
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'is_active' => 1,
    );
    $result = civicrm_uf_join_add($params);

    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'uf_group_id is required field', 'In line ' . __LINE__);
  }

  /**
   * find uf join id
   */
  public function testFindUFJoinId() {
    $params = array(
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $this->_ufGroupId,
      'is_active' => 1,
    );
    $ufJoin = civicrm_uf_join_add($params);
    $searchParams = array(
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
    );
    $ufJoinId = civicrm_uf_join_id_find($searchParams);
    $this->assertEquals($ufJoinId, $ufJoin['id'], 'In line ' . __LINE__);
  }

  /**
   * find uf join group id
   */
  public function testFindUFGroupId() {
    $params = array(
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $this->_ufGroupId,
      'is_active' => 1,
    );
    $ufJoin = civicrm_uf_join_add($params);
    $searchParams = array(
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
    );
    $ufGroupId = civicrm_uf_join_UFGroupId_find($searchParams);
    $this->assertEquals($ufGroupId, $this->_ufGroupId, 'In line ' . __LINE__);
  }

  /**
   * fetch all profiles
   */
  public function testGetUFProfileGroups() {
    $ufProfileGroup = civicrm_uf_profile_groups_get();
    $this->assertEquals(1, count($ufProfileGroup), 'In line ' . __LINE__);
  }

  function testGroupCreate() {
    $params = array(
      'add_captcha' => 1,
      'add_contact_to_group' => 2,
      'cancel_URL' => 'http://example.org/cancel',
      'created_date' => '2009-06-27',
      'created_id' => 69,
      'group' => 2,
      'group_type' => 'Individual,Contact',
      'help_post' => 'help post',
      'help_pre' => 'help pre',
      'is_active' => 0,
      'is_cms_user' => 1,
      'is_edit_link' => 1,
      'is_map' => 1,
      'is_reserved' => 1,
      'is_uf_link' => 1,
      'is_update_dupe' => 1,
      'name' => 'Test_Group',
      'notify' => 'admin@example.org',
      'post_URL' => 'http://example.org/post',
      'title' => 'Test Group',
    );
    $group = civicrm_uf_group_create($params);
    foreach ($params as $key => $value) {
      if ($key == 'add_contact_to_group' or $key == 'group') {
        continue;
      }
      $this->assertEquals($group[$key], $params[$key], 'In line ' . __LINE__);
    }
    $this->assertEquals($group['add_to_group_id'], $params['add_contact_to_group'], 'In line ' . __LINE__);
    $this->assertEquals($group['limit_listings_group_id'], $params['group'], 'In line ' . __LINE__);
  }

  function testGroupCreateWithEmptyParams() {
    $result = civicrm_uf_group_create(array());
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
  }

  function testGroupCreateWithWrongParams() {
    $result = civicrm_uf_group_create('a string');
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $result = civicrm_uf_group_create(array('name' => 'A title-less group'));
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
  }

  function testGroupUpdate() {
    $params = array(
      'add_captcha' => 1,
      'add_contact_to_group' => 2,
      'cancel_URL' => 'http://example.org/cancel',
      'created_date' => '2009-06-27',
      'created_id' => 69,
      'group' => 2,
      'group_type' => 'Individual,Contact',
      'help_post' => 'help post',
      'help_pre' => 'help pre',
      'is_active' => 0,
      'is_cms_user' => 1,
      'is_edit_link' => 1,
      'is_map' => 1,
      'is_reserved' => 1,
      'is_uf_link' => 1,
      'is_update_dupe' => 1,
      'name' => 'test_group',
      'notify' => 'admin@example.org',
      'post_URL' => 'http://example.org/post',
      'title' => 'Test Group',
    );
    $group = civicrm_uf_group_update($params, $this->_ufGroupId);
    foreach ($params as $key => $value) {
      if ($key == 'add_contact_to_group' or $key == 'group') {
        continue;
      }
      $this->assertEquals($group[$key], $params[$key], 'In line ' . __LINE__);
    }
    $this->assertEquals($group['add_to_group_id'], $params['add_contact_to_group'], 'In line ' . __LINE__);
    $this->assertEquals($group['limit_listings_group_id'], $params['group'], 'In line ' . __LINE__);
  }

  function testGroupUpdateWithEmptyParams() {
    $result = civicrm_uf_group_update(array(), $this->_ufGroupId);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
  }

  function testGroupUpdateWithWrongParams() {
    $result = civicrm_uf_group_update('a string', $this->_ufGroupId);
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
    $result = civicrm_uf_group_update(array('title' => 'Title'), 'a string');
    $this->assertEquals($result['is_error'], 1, 'In line ' . __LINE__);
  }
}

