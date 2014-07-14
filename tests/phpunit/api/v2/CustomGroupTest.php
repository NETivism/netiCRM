<?php

/**
 *  Include class definitions
 */
require_once 'tests/phpunit/CiviTest/CiviUnitTestCase.php';
require_once 'api/v2/CustomGroup.php';

/**
 *  Test APIv2 civicrm_create_custom_group
 *
 *  @package   CiviCRM
 */
class api_v2_CustomGroupTest extends CiviUnitTestCase {
  function get_info() {
    return array(
      'name' => 'Custom Group Create',
      'description' => 'Test all Custom Group Create API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    parent::setUp();
  }

  function tearDown() {
    // truncate a few tables
    $tablesToTruncate = array();
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  ///////////////// civicrm_custom_group_create methods

  /**
   * check with empty array
   */
  function testCustomGroupCreateNoParam() {
    $params = array();
    $customGroup = &civicrm_custom_group_create($params);
    $this->assertEquals($customGroup['is_error'], 1);
    $this->assertEquals($customGroup['error_message'], 'Params must include either \'class_name\' (string) or \'extends\' (array).');
  }

  /**
   * check with empty array
   */
  function testCustomGroupCreateNoExtends() {
    $params = array(
      'domain_id' => 1,
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Tab',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
    );

    $customGroup = &civicrm_custom_group_create($params);
    $this->assertEquals($customGroup['error_message'], 'Params must include either \'class_name\' (string) or \'extends\' (array).');
    $this->assertEquals($customGroup['is_error'], 1);
  }

  /**
   * check with empty array
   */
  function testCustomGroupCreateInvalidExtends() {
    $params = array(
      'domain_id' => 1,
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Tab',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
      'extends' => array(),
    );

    $customGroup = &civicrm_custom_group_create($params);
    $this->assertEquals($customGroup['error_message'], 'First item in params[\'extends\'] must be a class name (e.g. \'Contact\').');
    $this->assertEquals($customGroup['is_error'], 1);
  }

  /**
   * check with create fields
   */
  function testCustomGroupCreateWithFields() {
    $params = array(
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'extends' => array('Individual'),
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
      'html_type' => 'Select',
      'data_type' => 'String',
      'option_label' => array('Label1', 'Label2'),
      'option_value' => array('value1', 'value2'),
      'option_name' => array('name_1', 'name_2'),
      'option_weight' => array(1, 2),
      'label' => 'Country',
    );

    $customGroup = &civicrm_custom_group_create($params);
    $this->assertEquals($customGroup['is_error'], 0);
    $this->assertNotNull($customGroup['id']);
    $this->assertNotNull($customGroup['customFieldId']);
    $this->assertEquals($customGroup['extends'], 'Individual');
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * check with valid array
   */
  function testCustomGroupCreate() {
    $params = array(
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'extends' => array('Individual'),
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
    );

    $customGroup = &civicrm_custom_group_create($params);
    $this->assertEquals($customGroup['is_error'], 0);
    $this->assertNotNull($customGroup['id']);
    $this->assertEquals($customGroup['extends'], 'Individual');
    $this->customGroupDelete($customGroup['id']);

    unset($params['style']);
    $customGroup = &civicrm_custom_group_create($params);
    $this->assertEquals($customGroup['is_error'], 0);
    $this->assertNotNull($customGroup['id']);
    $this->assertEquals($customGroup['style'], 'Inline');
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * check with not array
   */
  function testCustomGroupCreatNotArray() {
    $params = NULL;
    $customGroup = &civicrm_custom_group_create($params);
    $this->assertEquals($customGroup['is_error'], 1);
    $this->assertEquals($customGroup['error_message'], 'params is not an array');
  }

  /**
   * check without title
   */
  function testCustomGroupCreateNoTitle() {
    $params = array('extends' => array('Contact'),
      'weight' => 5,
      'collapse_display' => 1,
      'style' => 'Tab',
      'help_pre' => 'This is Pre Help For Test Group 2',
      'help_post' => 'This is Post Help For Test Group 2',
    );

    $customGroup = &civicrm_custom_group_create($params);
    $this->assertEquals($customGroup['error_message'], 'Title parameter is required.');
    $this->assertEquals($customGroup['is_error'], 1);
  }

  /**
   * check for household without weight
   */
  function testCustomGroupCreateHouseholdNoWeight() {
    $params = array(
      'title' => 'Test_Group_3',
      'name' => 'test_group_3',
      'extends' => array('Household'),
      'collapse_display' => 1,
      'style' => 'Tab',
      'help_pre' => 'This is Pre Help For Test Group 3',
      'help_post' => 'This is Post Help For Test Group 3',
      'is_active' => 1,
    );

    $customGroup = &civicrm_custom_group_create($params);
    $this->assertEquals($customGroup['is_error'], 0);
    $this->assertNotNull($customGroup['id']);
    $this->assertEquals($customGroup['extends'], 'Household');
    $this->assertEquals($customGroup['style'], 'Tab');
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * check for Contribution Donation
   */
  function testCustomGroupCreateContributionDonation() {
    $params = array(
      'title' => 'Test_Group_6',
      'name' => 'test_group_6',
      'extends' => array('Contribution', array(1)),
      'weight' => 6,
      'collapse_display' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 6',
      'help_post' => 'This is Post Help For Test Group 6',
      'is_active' => 1,
    );

    $customGroup = &civicrm_custom_group_create($params);
    $this->assertEquals($customGroup['is_error'], 0);
    $this->assertNotNull($customGroup['id']);
    $this->assertEquals($customGroup['extends'], 'Contribution');
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * check with valid array
   */
  function testCustomGroupCreateGroup() {
    $params = array(
      'domain_id' => 1,
      'title' => 'Test_Group_8',
      'name' => 'test_group_8',
      'extends' => array('Group'),
      'weight' => 7,
      'collapse_display' => 1,
      'is_active' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 8',
      'help_post' => 'This is Post Help For Test Group 8',
    );

    $customGroup = &civicrm_custom_group_create($params);
    $this->assertEquals($customGroup['is_error'], 0);
    $this->assertNotNull($customGroup['id']);
    $this->assertEquals($customGroup['extends'], 'Group');
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * check with Activity - Meeting Type
   */
  function testCustomGroupCreateActivityMeeting() {
    $params = array(
      'title' => 'Test_Group_10',
      'name' => 'test_group_10',
      'extends' => array('Activity', array(1)),
      'weight' => 8,
      'collapse_display' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 10',
      'help_post' => 'This is Post Help For Test Group 10',
    );

    $customGroup = &civicrm_custom_group_create($params);
    $this->assertEquals($customGroup['is_error'], 0);
    $this->assertNotNull($customGroup['id']);
    $this->assertEquals($customGroup['extends'], 'Activity');
    $this->customGroupDelete($customGroup['id']);
  }

  ///////////////// civicrm_custom_group_delete methods

  /**
   * check without GroupID
   */
  function testCustomGroupDeleteWithoutGroupID() {
    $params = array();
    $customGroup = &civicrm_custom_group_delete($params);
    $this->assertEquals($customGroup['is_error'], 1);
    $this->assertEquals($customGroup['error_message'], 'Invalid or no value for Custom group ID');
  }

  /**
   * check with no array
   */
  function testCustomGroupDeleteNoArray() {
    $params = NULL;
    $customGroup = &civicrm_custom_group_delete($params);
    $this->assertEquals($customGroup['is_error'], 1);
    $this->assertEquals($customGroup['error_message'], 'Params is not an array');
  }

  /**
   * check with empty array
   */
  function testCustomFieldCreateNoParam() {
    $params = array();
    $customField = &civicrm_custom_field_create($params);
    $this->assertEquals($customField['is_error'], 1);
    $this->assertEquals($customField['error_message'], 'Missing Required field :custom_group_id');
  }

  /**
   * check with valid custom group id
   */
  function testCustomGroupDelete() {
    $customGroup = $this->customGroupCreate('Individual', 'test_group');
    $params      = array('id' => $customGroup['id']);
    $customGroup = civicrm_custom_group_delete($params);
    $this->assertEquals($customGroup['is_error'], 0);
  }

  //////////////// civicrm_custom_field_create methods

  /**
   * check with no array
   */
  function testCustomFieldCreateNoArray() {
    $fieldParams = NULL;

    $customField = &civicrm_custom_field_create($fieldParams);
    $this->assertEquals($customField['is_error'], 1);
    $this->assertEquals($customField['error_message'], 'params is not an array ');
  }

  /**
   * check with no label
   */
  function testCustomFieldCreateWithoutLabel() {
    $customGroup = $this->customGroupCreate('Individual', 'text_test_group');
    $params = array(
      'custom_group_id' => $customGroup['id'],
      'name' => 'test_textfield2',
      'html_type' => 'Text',
      'data_type' => 'String',
      'default_value' => 'abc',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    );

    $customField = &civicrm_custom_field_create($params);
    $this->assertEquals($customField['is_error'], 1);
    $this->assertEquals($customField['error_message'], 'Missing Required field :label');
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * check with edit
   */
  function testCustomFieldCreateWithEdit() {
    $customGroup = $this->customGroupCreate('Individual', 'text_test_group');
    $params = array(
      'custom_group_id' => $customGroup['id'],
      'name' => 'test_textfield2',
      'label' => 'Name1',
      'html_type' => 'Text',
      'data_type' => 'String',
      'default_value' => 'abc',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    );

    $customField  = &civicrm_custom_field_create($params);
    $params['id'] = $customField['result']['customFieldId'];
    $customField  = &civicrm_custom_field_create($params);

    $this->assertEquals($customField['is_error'], 0);
    $this->assertNotNull($customField['result']['customFieldId']);
    $this->customFieldDelete($customField['result']['customFieldId']);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * check without groupId
   */
  function testCustomFieldCreateWithoutGroupID() {
    $fieldParams = array(
      'name' => 'test_textfield1',
      'label' => 'Name',
      'html_type' => 'Text',
      'data_type' => 'String',
      'default_value' => 'abc',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    );

    $customField = &civicrm_custom_field_create($fieldParams);
    $this->assertEquals($customField['is_error'], 1);
    $this->assertEquals($customField['error_message'], 'Missing Required field :custom_group_id');
  }

  /**
   * check with data type - Text array
   */
  function testCustomTextFieldCreate() {
    $customGroup = $this->customGroupCreate('Individual', 'text_test_group');
    $params = array(
      'custom_group_id' => $customGroup['id'],
      'name' => 'test_textfield2',
      'label' => 'Name1',
      'html_type' => 'Text',
      'data_type' => 'String',
      'default_value' => 'abc',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    );

    $customField = &civicrm_custom_field_create($params);
    $this->assertEquals($customField['is_error'], 0);
    $this->assertNotNull($customField['result']['customFieldId']);
    $this->customFieldDelete($customField['result']['customFieldId']);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * check with data type - Date array
   */
  function testCustomDateFieldCreate() {
    $customGroup = $this->customGroupCreate('Individual', 'date_test_group');
    $params = array(
      'custom_group_id' => $customGroup['id'],
      'name' => 'test_date',
      'label' => 'test_date',
      'html_type' => 'Select Date',
      'data_type' => 'Date',
      'default_value' => '20071212',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    );
    $customField = &civicrm_custom_field_create($params);
    $this->assertEquals($customField['is_error'], 0);
    $this->assertNotNull($customField['result']['customFieldId']);
    $this->customFieldDelete($customField['result']['customFieldId']);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * check with data type - Country array
   */
  function testCustomCountryFieldCreate() {
    $customGroup = $this->customGroupCreate('Individual', 'Country_test_group');
    $params = array(
      'custom_group_id' => $customGroup['id'],
      'name' => 'test_country',
      'label' => 'test_country',
      'html_type' => 'Select Country',
      'data_type' => 'Country',
      'default_value' => '1228',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    );

    $customField = &civicrm_custom_field_create($params);
    $this->assertEquals($customField['is_error'], 0);
    $this->assertNotNull($customField['result']['customFieldId']);
    $this->customFieldDelete($customField['result']['customFieldId']);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * check with data type - Note array
   */
  function testCustomNoteFieldCreate() {
    $customGroup = $this->customGroupCreate('Individual', 'Country2_test_group');
    $params = array(
      'custom_group_id' => $customGroup['id'],
      'name' => 'test_note',
      'label' => 'test_note',
      'html_type' => 'TextArea',
      'data_type' => 'Memo',
      'default_value' => 'Hello',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    );

    $customField = &civicrm_custom_field_create($params);
    $this->assertEquals($customField['is_error'], 0);
    $this->assertNotNull($customField['result']['customFieldId']);
    $this->customFieldDelete($customField['result']['customFieldId']);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * check with data type - Options array
   */
  function testCustomFieldOptionValueCreate() {
    $customGroup = $this->customGroupCreate('Contact', 'select_test_group');
    $params = array(
      'custom_group_id' => 1,
      'label' => 'Country',
      'html_type' => 'Select',
      'data_type' => 'String',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      'option_label' => array('Label1', 'Label2'),
      'option_value' => array('val1', 'val2'),
      'option_weight' => array(1, 2),
      'option_status' => array(1, 1),
    );

    $customField = &civicrm_custom_field_create($params);

    $this->assertEquals($customField['is_error'], 0);
    $this->assertNotNull($customField['result']['customFieldId']);
    $this->customFieldDelete($customField['result']['customFieldId']);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * check with data type - Options with option_values
   */
  function testCustomFieldCreateWithOptionValues() {
    $customGroup = $this->customGroupCreate('Contact', 'select_test_group');

    $option_values = array(
      array('weight' => 1,
        'label' => 'Label1',
        'value' => 1,
        'is_active' => 1,
      ),
      array(
        'weight' => 2,
        'label' => 'Label2',
        'value' => 2,
        'is_active' => 1,
      ),
    );

    $params = array(
      'custom_group_id' => 1,
      'label' => 'Country',
      'html_type' => 'Select',
      'data_type' => 'String',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      'option_values' => $option_values,
    );

    $customField = &civicrm_custom_field_create($params);

    $this->assertEquals($customField['is_error'], 0);
    $this->assertNotNull($customField['result']['customFieldId']);
    $this->customFieldDelete($customField['result']['customFieldId']);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * check with data type - Select Option array
   */
  function testCustomFieldSelectOptionValueCreate() {
    $customGroup = $this->customGroupCreate('Contact', 'select_test_group');
    $params = array(
      'custom_group_id' => 1,
      'label' => 'PriceSelect',
      'html_type' => 'Select',
      'data_type' => 'Int',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      'option_label' => array('Label1', 'Label2'),
      'option_value' => array('10', '20'),
      'option_weight' => array(1, 2),
      'option_status' => array(1, 1),
    );
    $customField = &civicrm_custom_field_create($params);

    $this->assertEquals($customField['is_error'], 0);
    $this->assertNotNull($customField['result']['customFieldId']);
    $this->customFieldDelete($customField['result']['customFieldId']);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * check with data type - Checkbox Options array
   */
  function testCustomFieldCheckBoxOptionValueCreate() {
    $customGroup = $this->customGroupCreate('Contact', 'CheckBox_test_group');
    $params = array(
      'custom_group_id' => $customGroup['id'],
      'label' => 'PriceChk',
      'html_type' => 'CheckBox',
      'data_type' => 'String',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      'option_label' => array('Label1', 'Label2'),
      'option_value' => array('10', '20'),
      'option_weight' => array(1, 2),
      'option_status' => array(1, 1),
      'default_checkbox_option' => array(1),
    );

    $customField = &civicrm_custom_field_create($params);

    $this->assertEquals($customField['is_error'], 0);
    $this->assertNotNull($customField['result']['customFieldId']);
    $this->customFieldDelete($customField['result']['customFieldId']);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * check with data type - Radio Options array
   */
  function testCustomFieldRadioOptionValueCreate() {
    $customGroup = $this->customGroupCreate('Contact', 'Radio_test_group');
    $params = array(
      'custom_group_id' => $customGroup['id'],
      'label' => 'PriceRadio',
      'html_type' => 'Radio',
      'data_type' => 'String',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      'option_label' => array('radioLabel1', 'radioLabel2'),
      'option_value' => array(10, 20),
      'option_weight' => array(1, 2),
      'option_status' => array(1, 1),
    );

    $customField = &civicrm_custom_field_create($params);

    $this->assertEquals($customField['is_error'], 0);
    $this->assertNotNull($customField['result']['customFieldId']);
    $this->customFieldDelete($customField['result']['customFieldId']);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * check with data type - Multi-Select Options array
   */
  function testCustomFieldMultiSelectOptionValueCreate() {
    $customGroup = $this->customGroupCreate('Contact', 'MultiSelect_test_group');
    $params = array(
      'custom_group_id' => $customGroup['id'],
      'label' => 'PriceMufdlti',
      'html_type' => 'Multi-Select',
      'data_type' => 'String',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      'option_label' => array('MultiLabel1', 'MultiLabel2'),
      'option_value' => array(10, 20),
      'option_weight' => array(1, 2),
      'option_status' => array(1, 1),
    );

    $customField = &civicrm_custom_field_create($params);

    $this->assertEquals($customField['is_error'], 0);
    $this->assertNotNull($customField['result']['customFieldId']);
    $this->customFieldDelete($customField['result']['customFieldId']);
    $this->customGroupDelete($customGroup['id']);
  }

  ///////////////// civicrm_custom_field_delete methods

  /**
   * check with no array
   */
  function testCustomFieldDeleteNoArray() {
    $params = NULL;
    $customField = &civicrm_custom_field_delete($params);
    $this->assertEquals($customField['is_error'], 1);
    $this->assertEquals($customField['error_message'], 'Params is not an array');
  }

  /**
   * check without Field ID
   */
  function testCustomFieldDeleteWithoutFieldID() {
    $params = array();
    $customField = &civicrm_custom_field_delete($params);
    $this->assertEquals($customField['is_error'], 1);
    $this->assertEquals($customField['error_message'], 'Invalid or no value for Custom Field ID');
  }

  /**
   * check without valid array
   */
  function testCustomFieldDelete() {
    $customGroup = $this->customGroupCreate('Individual', 'test_group');
    $customField = $this->customFieldCreate($customGroup['id'], 'test_name');
    $this->assertNotNull($customField['id'], 'in line ' . __LINE__);

    $params = array('id' => $customField['id']);
    $customField = civicrm_custom_field_delete($params);
    $this->assertEquals($customField['is_error'], 0);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * check for Option Value
   */
  function testCustomFieldOptionValueDelete() {
    $customGroup = $this->customGroupCreate('Contact', 'ABC');
    $customOptionValueFields = $this->customFieldOptionValueCreate($customGroup, 'fieldABC');

    $params = array('id' => $customOptionValueFields['id']);
    $customField = &civicrm_custom_field_delete($params);
    $this->assertEquals($customField['is_error'], 0);
    $this->customGroupDelete($customGroup['id']);
  }
}

