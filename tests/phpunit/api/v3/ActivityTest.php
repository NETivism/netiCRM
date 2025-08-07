<?php
/**
 * Activity Unit Test
 *
 * @docmaker_intro_start
 * @api_title Activity
 * This is a API Document about Activity.
 * @docmaker_intro_end
 */

/**
 *  Include class definitions
 */
require_once 'CiviTest/CiviUnitTestCase.php';


/**
 *  Test APIv3 civicrm_activity_* functions
 *
 *  @package   CiviCRM
 */
class api_v3_ActivityTest extends CiviUnitTestCase {
  public $_individualId;
  public $test_activity_type_id;
  protected $_params;
  protected $_params2;
  protected $_entity = 'activity';
  protected $_apiversion = 3;
  protected $test_activity_type_value;
  public $_eNoticeCompliant = TRUE;
  /**
   * @before
   *  Test setup for every test
   *
   *  Connect to the database, truncate the tables that will be used
   *  and redirect stdin to a temporary file
   */
  public function setUpTest() {
    //  Connect to the database
    parent::setUp();
    $this->_apiversion = 3;
    // local
    // $this->_individualId = 54;
    $this->_individualId = $this->individualCreate();

    //create activity types
    $activityTypes = civicrm_api('option_value', 'create', [
      'version' => $this->_apiversion, 'option_group_id' => 2,
        'name' => 'Test activity type',
        'label' => 'Test activity type',
        'sequential' => 1,
      ]);
    $this->test_activity_type_value = $activityTypes['values'][0]['value'];
    $this->test_activity_type_id = $activityTypes['id'];
    //local
    // $this->test_activity_type_value = '45';
    // $this->test_activity_type_id = '715';
    $this->_params = [
      'source_contact_id' => $this->_individualId,
      'activity_type_id' => $this->test_activity_type_value,
      'subject' => 'test activity type id',
      'activity_date_time' => '2011-06-02 14:36:13',
      'status_id' => 2,
      'priority_id' => 1,
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'version' => $this->_apiversion,
    ];
    $this->_params2 = [
      'source_contact_id' => $this->_individualId,
      'subject' => 'Eat & drink',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Napier',
      'details' => 'discuss & eat',
      'status_id' => 1,
      'activity_type_id' => $this->test_activity_type_value,
      'version' => $this->_apiversion,
    ];
    // create a logged in USER since the code references it for source_contact_id
    // $this->createLoggedInUser();
  }

  /**
   * @after
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @access protected
   */
  function tearDownTest() {
    civicrm_api('option_value', 'delete', ['version' => 3, 'id' => $this->test_activity_type_id]);
  }
  /**
   * Activity Create Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Activity
   * @api_action Create
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Activity&action=create
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Activity&action=get&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   * @group CItesting
   */
  function testCreateActivity() {

    $result = civicrm_api('activity', 'create', $this->_params);
    $this->docMakerRequest($this->_params, __FILE__, __FUNCTION__);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $result = civicrm_api('activity', 'get', $this->_params);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['source_contact_id'], $this->_individualId, 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['duration'], 120, 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['subject'], 'test activity type id', 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['activity_date_time'], '2011-06-02 14:36:13', 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['location'], 'Pensulvania', 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['details'], 'a test activity', 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['status_id'], 2, 'in line ' . __LINE__);
  }

  /**
   * Activity Get Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Activity
   * @api_action Get
   * @http_method GET
   * @request_url <entrypoint>?entity=Activity&action=get&json={$request_body_inline}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Activity&action=get&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   * @group CItesting
   */
  function testGetActivity() {
    $result_create = civicrm_api('activity', 'create', $this->_params);
    $this->assertAPISuccess($result_create, ' in line ' . __LINE__);
    $params = [
      'id' => $result_create['id'],
      'version' => $this->_apiversion,
    ];
    $result_get = civicrm_api('activity', 'get', $params);
    $this->assertAPISuccess($result_get, ' in line ' . __LINE__);
    $this->docMakerRequest($this->_params, __FILE__, __FUNCTION__);
    $this->docMakerResponse($result_get, __FILE__, __FUNCTION__);
  }

  /**
   * Activity Update Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Activity
   * @api_action Update
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Activity&action=create
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Activity&action=create&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   * @group CItesting
   */
  function testUpdateActivity() {
    $activity = civicrm_api('activity', 'create', $this->_params);
    $this->assertAPISuccess($activity, ' in line ' . __LINE__);
    $params = [
      'id' => $activity['id'],
      'subject' => 'Updated Make-it-Happen Meeting',
      'duration' => 120,
      'location' => '21, Park Avenue',
      'details' => 'Lets update Meeting',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      'priority_id' => 1,
      'version' => '3',
    ];
    $result = civicrm_api('activity', 'update', $params);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['subject'], 'Updated Make-it-Happen Meeting', 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['location'], '21, Park Avenue', 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['details'], 'Lets update Meeting', 'in line ' . __LINE__);
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);
  }

  /**
   * Activity Delete Unit Test
   *
   * @docmaker_start
   *
   * @api_entity Activity
   * @api_action Delete
   * @http_method POST
   * @request_content_type application/json
   * @request_url <entrypoint>?entity=Activity&action=delete
   * @request_body {$request_body}
   * @api_explorer /civicrm/apibrowser#/civicrm/ajax/rest?entity=Activity&action=delete&pretty=1&json={$request_body_inline}
   * @response_body {$response_body}
   *
   * @docmaker_end
   * @group CItesting
   */
  public function testDeleteActivity() {
    $activity = civicrm_api('activity', 'create', $this->_params);
    $this->assertAPISuccess($activity, ' in line ' . __LINE__);
    $params = [
      'id' => $activity['id'],
      'version' => '3',
    ];
    $result = civicrm_api('activity', 'delete', $params);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $this->docMakerRequest($params, __FILE__, __FUNCTION__);
    $this->docMakerResponse($result, __FILE__, __FUNCTION__);

  }

  /**
   * check with empty array
   */
  function testActivityCreateEmpty() {
    $params = ['version' => $this->_apiversion];
    $result = civicrm_api('activity', 'create', $params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * check if required fields are not passed
   */
  function testActivityCreateWithoutRequired() {
    $params = [
      'subject' => 'this case should fail',
      'scheduled_date_time' => date('Ymd'),
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('activity', 'create', $params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_create() with mismatched activity_type_id
   *  and activity_name
   */
  function testActivityCreateMismatchNameType() {
    $params = [
      'source_contact_id' => 17,
      'subject' => 'Test activity',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_name' => 'Fubar activity type',
      'activity_type_id' => 5,
      'scheduled_date_time' => date('Ymd'),
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('activity', 'create', $params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_id() with missing source_contact_id is put with the current user.
   */
  function testActivityCreateWithMissingContactId() {
    $params = [
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('activity', 'create', $params);

    // we should use the session contact ID, CRM-8180
    $this->assertEquals($result['is_error'], 0,
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_id() with non-numeric source_contact_id
   */
  function testActivityCreateWithNonNumericContactId() {
    $params = [
      'source_contact_id' => 'fubar',
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('activity', 'create', $params);

    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_id() with non-numeric duration
   *  @todo Come back to this in later stages
   */
  /// we don't offer single parameter correctness checking at the moment
  //function testActivityCreateWithNonNumericDuration( )
  //{
  //    $params = array(
  //                    'source_contact_id'   => 17,
  //                    'subject'             => 'Discussion on Apis for v3',
  //                    'activity_date_time'  => date('Ymd'),
  //                    'duration'            => 'fubar',
  //                    'location'            => 'Pensulvania',
  //                    'details'             => 'a test activity',
  //                    'status_id'           => 1,
  //                    'activity_name'       => 'Test activity type'
  //                    );
  //
  //    $result = civicrm_activity_create($params);
  //
  //    $this->assertEquals( $result['is_error'], 1,
  //                         "In line " . __LINE__ );
  //}

  /**
   * check with incorrect required fields
   */
  function testActivityCreateWithNonNumericActivityTypeId() {
    $params = [
      'source_contact_id' => 17,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_type_id' => 'Test activity type',
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('activity', 'create', $params);

    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * check with incorrect required fields
   */
  function testActivityCreateWithUnknownActivityTypeId() {
    $params = [
      'source_contact_id' => 17,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_type_id' => 699,
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('activity', 'create', $params);

    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  function testActivityCreateWithInvalidPriority() {
    $params = [
      'source_contact_id' => 17,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'priority_id' => 44,
      'activity_type_id' => 1,
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('activity', 'create', $params);
    $this->assertEquals($result['is_error'], 1, "In line " . __LINE__);
    $this->assertEquals('priority_id is not valid', $result['error_message']);
    $this->assertEquals(2001,$result['error_code']);
    $this->assertEquals('priority_id',$result['error_field']);
  }

  function testActivityCreateWithValidStringPriority() {
    $params = [
      'source_contact_id' => 17,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'priority_id' => 'Urgent',
      'activity_type_id' => 1,
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('activity', 'create', $params);
    $this->assertEquals($result['is_error'], 0, "In line " . __LINE__);
    $this->assertEquals(1, $result['values'][$result['id']]['priority_id']);
  }

  function testActivityCreateWithInValidStringPriority() {
    $params = [
      'source_contact_id' => 17,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'priority_id' => 'ergUrgent',
      'activity_type_id' => 1,
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('activity', 'create', $params);
    $this->assertEquals($result['is_error'], 1, "In line " . __LINE__);
    $this->assertEquals('priority_id ergUrgentis not valid', $result['error_message']);
  }

  /**
   *  Test civicrm_activity_create() with valid parameters
   */
  function testActivityCreate() {

    $result = civicrm_api('activity', 'create', $this->_params);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $result = civicrm_api('activity', 'get', $this->_params);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['source_contact_id'], 17, 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['duration'], 120, 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['subject'], 'test activity type id', 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['activity_date_time'], '2011-06-02 14:36:13', 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['location'], 'Pensulvania', 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['details'], 'a test activity', 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['status_id'], 2, 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['id'], $result['id'], 'in line ' . __LINE__);
  }

  function testActivityReturnTargetAssignee() {

    $description = "Example demonstrates setting & retrieving the target & source";
    $subfile     = "GetTargetandAssignee";
    $params      = [
      'source_contact_id' => 17,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => '20110316',
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_type_id' => 1,
      'version' => $this->_apiversion,
      'priority_id' => 1,
      'target_contact_id' => 17,
      'assignee_contact_id' => 17,
    ];

    $result = civicrm_api('activity', 'create', $params);
    //todo test target & assignee are set
    $this->assertEquals($result['is_error'], 0,
      "Error message: " . CRM_Utils_Array::value('error_message', $result) . ' in line ' . __LINE__
    );

    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, $subfile);
    $result = civicrm_api('activity', 'get', ['id' => $result['id'], 'version' => $this->_apiversion, 'return.assignee_contact_id' => 1, 'return.target_contact_id' => 1]);

    $this->assertEquals(17, $result['values'][$result['id']]['assignee_contact_id'][0], 'in line ' . __LINE__);
    $this->assertEquals(17, $result['values'][$result['id']]['target_contact_id'][0], 'in line ' . __LINE__);
  }

  /*
  function testActivityCreateExample() {
    require_once 'api/v3/examples/ActivityCreate.php';
    $result = activity_create_example();
    $expectedResult = activity_create_expectedresult();
    $this->assertEquals($result, $expectedResult);
  }
  */

  /**
   *  Test civicrm_activity_create() with valid parameters for unique fields -
   *  set up to see if unique fields work but activity_subject doesn't

   function testActivityCreateUniqueName( )
   {
   $this->markTestSkipped('test to see if api will take unique names but it doesn\'t yet');
   /*fields with unique names activity_id,
   * activity_subject,activity_duration
   * activity_location, activity_status_id
   * activity_is_test
   * activity_medium_id

   $params = array(
   'source_contact_id'   => 17,
   'activity_subject'             => 'Make-it-Happen Meeting',
   'activity_date_time'  => date('Ymd'),
   'activity_duration'            => 120,
   'activity_location'            => 'Pensulvania',
   'details'             => 'a test activity',
   'activity_status_id'           => 1,
   'activity_name'       => 'Test activity type',
   'version'							=> $this->_apiversion,
   );

   $result =  civicrm_api('activity', 'create' ,  $params );
   $this->assertEquals( $result['is_error'], 0,
   "Error message: " . CRM_Utils_Array::value( 'error_message', $result ) );

   $this->assertEquals( $result['values'][$result['id']]['source_contact_id'], 17 );
   $this->assertEquals( $result['values'][$result['id']]['duration'], 120 );
   // This field gets lost
   $this->assertEquals( $result['values'][$result['id']]['subject'], 'Make-it-Happen Meeting' );
   $this->assertEquals( $result['values'][$result['id']]['activity_date_time'], date('Ymd') . '000000' );
   $this->assertEquals( $result['values'][$result['id']]['location'], 'Pensulvania' );
   $this->assertEquals( $result['values'][$result['id']]['details'], 'a test activity' );
   $this->assertEquals( $result['values'][$result['id']]['status_id'], 1 );

   }
   */

  /**
   *  Test civicrm_activity_create() with valid parameters
   *  and some custom data
   */
  function testActivityCreateCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";
    $result = civicrm_api($this->_entity, 'create', $params);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $result = civicrm_api($this->_entity, 'get', ['return.custom_' . $ids['custom_field_id'] => 1, 'version' => 3, 'id' => $result['id']]);
    $this->assertEquals("custom string", $result['values'][$result['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   *  Test civicrm_activity_create() with valid parameters
   *  and some custom data
   */
  function testActivityCreateCustomContactRefField() {

    civicrm_api('contact', 'create', ['version' => 3, 'id' => 17, 'sort_name' => 'Contact, Test']);
    $subfile     = 'ContactRefCustomField';
    $description = "demonstrates create with Contact Reference Custom Field";
    $ids         = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $params      = [
      'custom_group_id' => $ids['custom_group_id'],
      'name' => 'Worker_Lookup',
      'label' => 'Worker Lookup',
      'html_type' => 'Autocomplete-Select',
      'data_type' => 'ContactReference',
      'weight' => 4,
      'is_searchable' => 1,
      'is_active' => 1,
      'version' => $this->_apiversion,
    ];

    $customField = civicrm_api('custom_field', 'create', $params);
    $params = $this->_params;
    $params['custom_' . $customField['id']] = "17";

    $result = civicrm_api($this->_entity, 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $result = civicrm_api($this->_entity, 'get', ['return.custom_' . $customField => 1, 'version' => 3, 'id' => $result['id']]);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, 'Get with Contact Ref Custom Field', 'ContactRefCustomFieldGet');

    $this->assertEquals('Contact, Test', $result['values'][$result['id']]['custom_' . $customField['id']], ' in line ' . __LINE__);
    $this->assertEquals(17, $result['values'][$result['id']]['custom_' . $customField['id'] . "_id"], ' in line ' . __LINE__);
    $this->assertEquals('Contact, Test', $result['values'][$result['id']]['custom_' . $customField['id'] . '_1'], ' in line ' . __LINE__);
    $this->assertEquals(17, $result['values'][$result['id']]['custom_' . $customField['id'] . "_1_id"], ' in line ' . __LINE__);
    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   *  Test civicrm_activity_create() with an invalid text status_id
   */
  function testActivityCreateBadTextStatus() {

    $params = [
      'source_contact_id' => 17,
      'subject' => 'Discussion on Apis for v3',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 'Invalid',
      'activity_name' => 'Test activity type',
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('activity', 'create', $params);

    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_create() with valid parameters,
   *  using a text status_id
   */
  function testActivityCreateTextStatus() {


    $params = [
      'source_contact_id' => 17,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 'Scheduled',
      'activity_name' => 'Test activity type',
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('activity', 'create', $params);
    $this->assertEquals($result['is_error'], 0,
      "Error message: " . CRM_Utils_Array::value('error_message', $result) . ' in line ' . __LINE__
    );
    $this->assertEquals($result['values'][$result['id']]['source_contact_id'], 17, 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['duration'], 120, 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['subject'], 'Make-it-Happen Meeting', 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['activity_date_time'], date('Ymd') . '000000', 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['location'], 'Pensulvania', 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['details'], 'a test activity', 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['status_id'], 'Scheduled', 'in line ' . __LINE__);
  }

  /**
   *  Test civicrm_activity_get() with no params
   */
  function testActivityGetEmpty() {
    $params = ['version' => $this->_apiversion];
    $result = civicrm_api('activity', 'get', $params);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_activity_get() with a good activity ID
   */
  function testActivityGetGoodID1() {
    //  Insert rows in civicrm_activity creating activities 4 and
    //  13
    $description = "Function demonstrates getting asignee_contact_id & using it to get the contact";
    $subfile    = 'ReturnAssigneeContact';
    $activity   = civicrm_api('activity', 'create', $this->_params);

    $contact = civicrm_api('Contact', 'Create', [
      'first_name' => "The Rock",
       'last_name' =>'roccky',
        'contact_type' => 'Individual',
        'version' => 3,
        'api.activity.create' => [
          'id' => $activity['id'], 'assignee_contact_id' => '$value.id',
        ],
      ]);

    $params = [
      'activity_id' => $activity['id'],
      'version' => $this->_apiversion,
      'sequential' => 1,
      'return.assignee_contact_id' => 1,
      'api.contact.get' => [
        'id' => '$value.source_contact_id',
      ],
    ];

    $result = civicrm_api('Activity', 'Get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, $subfile);

    $this->assertAPISuccess($result);

    $this->assertEquals($activity['id'], $result['id'], 'In line ' . __LINE__);
    $this->assertEquals(17, $result['values'][0]['source_contact_id'], 'In line ' . __LINE__);

    $this->assertEquals($contact['id'], $result['values'][0]['assignee_contact_id'][0], 'In line ' . __LINE__);

    $this->assertEquals(17, $result['values'][0]['api.contact.get']['values'][0]['contact_id'], 'In line ' . __LINE__);
    $this->assertEquals($this->test_activity_type_value, $result['values'][0]['activity_type_id'], 'In line ' . __LINE__);
    $this->assertEquals("test activity type id", $result['values'][0]['subject'], 'In line ' . __LINE__);
  }

  /*
     * test that get functioning does filtering
     */
  function testGetFilter() {
    $params = [
      'source_contact_id' => 17,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => '20110316',
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      'version' => $this->_apiversion,
      'priority_id' => 1,
    ];
    civicrm_api('Activity', 'Create', $params);
    $result = civicrm_api('Activity', 'Get', ['version' => 3, 'subject' => 'Make-it-Happen Meeting']);
    $this->assertEquals(1, $result['count']);
    $this->assertEquals('Make-it-Happen Meeting', $result['values'][$result['id']]['subject']);
    civicrm_api('Activity', 'Delete', ['version' => 3, 'id' => $result['id']]);
  }
  /*
     * test that get functioning does filtering
     */
  function testGetStatusID() {
    $params = [
      'source_contact_id' => 17,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => '20110316',
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      'version' => $this->_apiversion,
      'priority_id' => 1,
    ];
    civicrm_api('Activity', 'Create', $params);
    $result = civicrm_api('Activity', 'Get', [
      'version' => $this->_apiversion,
      'activity_status_id' => '1']);
    $this->assertEquals(1, $result['count'], 'one activity of status 1 should exist');

    $result = civicrm_api('Activity', 'Get', [
      'version' => $this->_apiversion,
      'status_id' => '1']);
    $this->assertEquals(1, $result['count'], 'status_id should also work');

    $result = civicrm_api('Activity', 'Get', [
      'version' => $this->_apiversion,
      'activity_status_id' => '2']);
    $this->assertEquals(0, $result['count'], 'No activities of status 1 should exist');
     $result = civicrm_api('Activity', 'Get', [
      'version' => $this->_apiversion,
      'status_id' => '2']);
    $this->assertEquals(0, $result['count'], 'No activities of status 1 should exist');


 }

  /*
     * test that get functioning does filtering
     */
  function testGetFilterMaxDate() {
    $params = [
      'source_contact_id' => 17,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => '20110101',
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      'version' => $this->_apiversion,
      'priority_id' => 1,
    ];
    $activityOne = civicrm_api('Activity', 'Create', $params);
    $params['activity_date_time'] = 20120216;
    $activityTwo = civicrm_api('Activity', 'Create', $params);
    $result = civicrm_api('Activity', 'Get', [
        'version' => 3,
      ]);
    $description = "demonstrates _low filter (at time of writing doesn't work if contact_id is set";
    $subfile = "DateTimeLow";
    $this->assertEquals(2, $result['count']);
    $params = [
      'version' => 3,
      'filter.activity_date_time_low' => '20120101000000',
      'sequential' => 1,
    ];
    $result = civicrm_api('Activity', 'Get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(1, $result['count'], 'in line ' . __LINE__);
    $description = "demonstrates _high filter (at time of writing doesn't work if contact_id is set";
    $subfile = "DateTimeHigh";
    $this->assertEquals('2012-02-16 00:00:00', $result['values'][0]['activity_date_time'], 'in line ' . __LINE__);
    $params = [
      'source_contact_id' => 17,
      'version' => 3,
      'filter.activity_date_time_high' => '20120101000000',
      'sequential' => 1,
    ];
    $result = civicrm_api('Activity', 'Get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, $subfile);

    $this->assertEquals(1, $result['count']);
    $this->assertEquals('2011-01-01 00:00:00', $result['values'][0]['activity_date_time'], 'in line ' . __LINE__);

    civicrm_api('Activity', 'Delete', ['version' => 3, 'id' => $activityOne['id']]);
    civicrm_api('Activity', 'Delete', ['version' => 3, 'id' => $activityTwo['id']]);
  }

  /**
   *  Test civicrm_activity_get() with a good activity ID which
   *  has associated custom data
   */
  function testActivityGetGoodIDCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = civicrm_api($this->_entity, 'create', $params);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    //  Retrieve the test value
    $params = [
      'activity_type_id' => $this->test_activity_type_value,
      'version' => 3,
      'sequential' => 1,
      'return.custom_' . $ids['custom_field_id'] => 1,
    ];
    $result = civicrm_api('activity', 'get', $params, TRUE);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $this->assertEquals("custom string", $result['values'][0]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);


    $this->assertEquals(17, $result['values'][0]['source_contact_id'], 'In line ' . __LINE__);
    $this->assertEquals($this->test_activity_type_value, $result['values'][0]['activity_type_id'], 'In line ' . __LINE__);
    $this->assertEquals('test activity type id', $result['values'][0]['subject'], 'In line ' . __LINE__);
    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   *  Test civicrm_activity_get() with a good activity ID which
   *  has associated custom data
   */
  function testActivityGetContact_idCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = civicrm_api($this->_entity, 'create', $params);
    //  Retrieve the test value
    $params = [
      'contact_id' => $this->_params['source_contact_id'],
      'activity_type_id' => $this->test_activity_type_value,
      'version' => 3,
      'sequential' => 1,
      'return.custom_' . $ids['custom_field_id'] => 1,
    ];
    $result = civicrm_api('activity', 'get', $params, TRUE);
    $this->assertAPISuccess($result, 'in line ' . __LINE__);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], "Error message: " . CRM_Utils_Array::value('error_message', $result));
    $this->assertEquals("custom string", $result['values'][0]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);

    $this->assertEquals(17, $result['values'][0]['source_contact_id'], 'In line ' . __LINE__);
    $this->assertEquals($this->test_activity_type_value, $result['values'][0]['activity_type_id'], 'In line ' . __LINE__);
    $this->assertEquals('test activity type id', $result['values'][0]['subject'], 'In line ' . __LINE__);
    $this->assertEquals($result['values'][0]['id'], $result['id'], 'in line ' . __LINE__);
  }

  /**
   * check activity deletion with empty params
   */
  function testDeleteActivityForEmptyParams() {
    $params = ['version' => $this->_apiversion];
    $result = civicrm_api('activity', 'delete', $params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * check activity deletion without activity id
   */
  function testDeleteActivityWithoutId() {
    $params = [
      'activity_name' => 'Meeting',
      'version' => $this->_apiversion,
    ];
    $result = civicrm_api('activity', 'delete', $params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * check activity deletion without activity type
   */
  function testDeleteActivityWithoutActivityType() {
    $params = ['id' => 1];
    $result = civicrm_api('activity', 'delete', $params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * check activity deletion with incorrect data
   */
  function testDeleteActivityWithIncorrectActivityType() {
    $params = [
      'id' => 1,
      'activity_name' => 'Test Activity',
    ];

    $result = civicrm_api('activity', 'delete', $params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * check with empty array
   */
  function testActivityUpdateEmpty() {
    $params = [];
    $result = civicrm_api('activity', 'create', $params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * check if required fields are not passed
   */
  function testActivityUpdateWithoutRequired() {
    $params = [
      'subject' => 'this case should fail',
      'scheduled_date_time' => date('Ymd'),
    ];

    $result = civicrm_api('activity', 'create', $params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * check with incorrect required fields
   */
  function testActivityUpdateWithIncorrectData() {
    $params = [
      'activity_name' => 'Meeting',
      'subject' => 'this case should fail',
      'scheduled_date_time' => date('Ymd'),
    ];

    $result = civicrm_api('activity', 'create', $params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * Test civicrm_activity_update() with non-numeric id
   */
  function testActivityUpdateWithNonNumericId() {
    $params = [
      'id' => 'lets break it',
      'activity_name' => 'Meeting',
      'subject' => 'this case should fail',
      'scheduled_date_time' => date('Ymd'),
    ];

    $result = civicrm_api('activity', 'create', $params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   * check with incorrect required fields
   */
  function testActivityUpdateWithIncorrectContactActivityType() {
    $params = [
      'id' => 1,
      'activity_name' => 'Test Activity',
      'subject' => 'this case should fail',
      'scheduled_date_time' => date('Ymd'),
      'version' => $this->_apiversion,
      'source_contact_id' => 17,
    ];

    $result = civicrm_api('activity', 'create', $params);
    $this->assertEquals($result['is_error'], 1, "In line " . __LINE__);
    $this->assertEquals($result['error_message'], 'Invalid Activity Id', "In line " . __LINE__);
  }

  /**
   *  Test civicrm_activity_update() to update an existing activity
   */
  function testActivityUpdate() {
    $result = civicrm_api('activity', 'create', $this->_params);

    $params = [
      'id' => $result['id'],
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => '20091011123456',
      'duration' => 120,
      'location' => '21, Park Avenue',
      'details' => 'Lets update Meeting',
      'status_id' => 1,
      'source_contact_id' => 17,
      'priority_id' => 1,
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('activity', 'create', $params);
    $this->assertAPISuccess($result, 'in line ' . __LINE__);
    //hack on date comparison - really we should make getAndCheck smarter to handle dates
    $params['activity_date_time'] = '2009-10-11 12:34:56';
    $this->getAndCheck($params, $result['id'], 'activity');
  }

  /**
   *  Test civicrm_activity_update() with valid parameters
   *  and some custom data
   */
  function testActivityUpdateCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;

    //  Create an activity with custom data
    //this has been updated from the previous 'old format' function - need to make it work
    $params = [
      'source_contact_id' => 17,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => '2009-10-18',
      'duration' => 120,
      'location' => 'Pensulvania',
      'details' => 'a test activity to check the update api',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      'version' => $this->_apiversion,
      'custom_' . $ids['custom_field_id'] => 'custom string',
    ];
    $result = civicrm_api('activity', 'create', $params);

    $activityId = $result['id'];
    $this->assertEquals(0, $result['is_error'],
      "Error message: " . CRM_Utils_Array::value('error_message', $result)
    );
    $result = civicrm_api($this->_entity, 'get', ['return.custom_' . $ids['custom_field_id'] => 1, 'version' => 3, 'id' => $result['id']]);
    $this->assertEquals("custom string", $result['values'][$result['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);
    $this->assertEquals("2009-10-18 00:00:00", $result['values'][$result['id']]['activity_date_time'], ' in line ' . __LINE__);
    $fields = civicrm_api('activity', 'getfields', ['version' => $this->_apiversion]);
    $this->assertTrue(is_array($fields['values']['custom_' . $ids['custom_field_id']]));

    //  Update the activity with custom data
    $params = [
      'id' => $activityId,
      'source_contact_id' => 17,
      'subject' => 'Make-it-Happen Meeting',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      // add this since dates are messed up
      'activity_date_time' => date('Ymd'),
      'custom_' . $ids['custom_field_id'] => 'Updated my test data',
      'version' => $this->_apiversion,
    ];
    $result = civicrm_api('Activity', 'Create', $params);
    $this->assertEquals(0, $result['is_error'],
      "Error message: " . CRM_Utils_Array::value('error_message', $result)
    );

    $result = civicrm_api($this->_entity, 'get', ['return.custom_' . $ids['custom_field_id'] => 1, 'version' => 3, 'id' => $result['id']]);
    $this->assertEquals("Updated my test data", $result['values'][$result['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);
  }

  /**
   *  Test civicrm_activity_update() for core activity fields
   *  and some custom data
   */
  function testActivityUpdateCheckCoreFields() {
    $params = $this->_params;
    $contact1Params = [
      'first_name' => 'John',
      'middle_name' => 'J.',
      'last_name' => 'Anderson',
      'prefix_id' => 3,
      'suffix_id' => 3,
      'email' => 'john_anderson@civicrm.org',
      'contact_type' => 'Individual',
    ];

    $contact1 = $this->individualCreate($contact1Params);
    $contact2Params = [
      'first_name' => 'Michal',
      'middle_name' => 'J.',
      'last_name' => 'Anderson',
      'prefix_id' => 3,
      'suffix_id' => 3,
      'email' => 'michal_anderson@civicrm.org',
      'contact_type' => 'Individual',
    ];

    $contact2 = $this->individualCreate($contact2Params);

    $params['assignee_contact_id'] = [$contact1, $contact2];
    $params['target_contact_id'] = [$contact2 => $contact2];
    $result = civicrm_api('Activity', 'Create', $params);

    $result = civicrm_api('activity', 'create', $params);
    $activityId = $result['id'];
    $this->assertAPISuccess($result);
    $getParams = [
      'return.assignee_contact_id' => 1,
      'return.target_contact_id' => 1,
      'version' => $this->_apiversion,
      'id' => $activityId,
    ];
    $result = civicrm_api($this->_entity, 'get',$getParams );
    $assignee = $result['values'][$result['id']]['assignee_contact_id'];
    $target = $result['values'][$result['id']]['target_contact_id'];
    $this->assertEquals(2, count($assignee), ' in line ' . __LINE__);
    $this->assertEquals(1, count($target), ' in line ' . __LINE__);
    $this->assertEquals(TRUE, in_array($contact1, $assignee), ' in line ' . __LINE__);
    $this->assertEquals(TRUE, in_array($contact2, $target), ' in line ' . __LINE__);

    $contact3Params = [
      'first_name' => 'Jijo',
      'middle_name' => 'J.',
      'last_name' => 'Anderson',
      'prefix_id' => 3,
      'suffix_id' => 3,
      'email' => 'jijo_anderson@civicrm.org',
      'contact_type' => 'Individual',
    ];

    $contact4Params = [
      'first_name' => 'Grant',
      'middle_name' => 'J.',
      'last_name' => 'Anderson',
      'prefix_id' => 3,
      'suffix_id' => 3,
      'email' => 'grant_anderson@civicrm.org',
      'contact_type' => 'Individual',
    ];

    $contact3 = $this->individualCreate($contact3Params);
    $contact4 = $this->individualCreate($contact4Params);

    $params = [];
    $params['id'] = $activityId;
    $params['version'] = $this->_apiversion;
    $params['assignee_contact_id'] = [$contact3 => $contact3];
    $params['target_contact_id'] = [$contact4 => $contact4];

    $result = civicrm_api('activity', 'create', $params);
    $this->assertEquals(0, $result['is_error'],
      "Error message: " . CRM_Utils_Array::value('error_message', $result)
    );

    $this->assertEquals($activityId, $result['id'], ' in line ' . __LINE__);

    $result = civicrm_api($this->_entity, 'get', ['return.assignee_contact_id' => 1, 'return.target_contact_id' => 1, 'version' => 3, 'id' => $result['id']]);

    $assignee = $result['values'][$result['id']]['assignee_contact_id'];
    $target = $result['values'][$result['id']]['target_contact_id'];

    $this->assertEquals(1, count($assignee), ' in line ' . __LINE__);
    $this->assertEquals(1, count($target), ' in line ' . __LINE__);
    $this->assertEquals(TRUE, in_array($contact3, $assignee), ' in line ' . __LINE__);
    $this->assertEquals(TRUE, in_array($contact4, $target), ' in line ' . __LINE__);

    foreach ($this->_params as $fld => $val) {
      if ($fld == 'version') {
        continue;
      }
      $this->assertEquals($val, $result['values'][$result['id']][$fld], ' in line ' . __LINE__);
    }
  }

  /**
   *  Test civicrm_activity_update() where the DB has a date_time
   *  value and there is none in the update params.
   */
  function testActivityUpdateNotDate() {
    $result = civicrm_api('activity', 'create', $this->_params);

    $params = [
      'id' => $result['id'],
      'subject' => 'Make-it-Happen Meeting',
      'duration' => 120,
      'location' => '21, Park Avenue',
      'details' => 'Lets update Meeting',
      'status_id' => 1,
      'source_contact_id' => 17,
      'priority_id' => 1,
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('activity', 'create', $params);
    $this->assertAPISuccess($result, 'in line ' . __LINE__);
    //hack on date comparison - really we should make getAndCheck smarter to handle dates
    $params['activity_date_time'] = $this->_params['activity_date_time'];
    $this->getAndCheck($params, $result['id'], 'activity');
  }

  /**
   * check activity update with status
   */
  function testActivityUpdateWithStatus() {
    $activity = civicrm_api('activity', 'create', $this->_params);
    $this->assertAPISuccess($activity, "In line " . __LINE__);
    $params = [
      'id' => $activity['id'],
      'source_contact_id' => 17,
      'subject' => 'Hurry update works',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('activity', 'create', $params);
    $this->assertAPISuccess($result, "In line " . __LINE__);
    $this->assertEquals($result['id'], $activity['id'], "In line " . __LINE__);
    $this->assertEquals($result['values'][$activity['id']]['source_contact_id'], 17,
      "In line " . __LINE__
    );
    $this->assertEquals($result['values'][$activity['id']]['subject'], 'Hurry update works',
      "In line " . __LINE__
    );
    $this->assertEquals($result['values'][$activity['id']]['status_id'], 1,
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_update() where the source_contact_id
   *  is not in the update params.
   */
  function testActivityUpdateKeepSource() {
    $activity = civicrm_api('activity', 'create', $this->_params);
    //  Updating the activity but not providing anything for the source contact
    //  (It was set as 17 earlier.)
    $params = [
      'id' => $activity['id'],
      'subject' => 'Updated Make-it-Happen Meeting',
      'duration' => 120,
      'location' => '21, Park Avenue',
      'details' => 'Lets update Meeting',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      'priority_id' => 1,
      'version' => $this->_apiversion,
    ];

    $result = civicrm_api('activity', 'create', $params);
    $this->assertAPISuccess($result, 'in line ' . __LINE__);
    $findactivity = civicrm_api('Activity', 'Get', ['id' => $activity['id'], 'version' => 3]);

    $this->assertAPISuccess($findactivity);
    $this->assertEquals(17, $findactivity['values'][$findactivity['id']]['source_contact_id'], 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_activities_contact_get()
   */
  function testActivitiesContactGet() {
    $activity = civicrm_api('activity', 'create', $this->_params);
    $activity2 = civicrm_api('activity', 'create', $this->_params2);
    //  Get activities associated with contact 17
    $params = [
      'contact_id' => 17,
      'version' => $this->_apiversion,
    ];
    $result = civicrm_api('activity', 'get', $params);

    $this->assertAPISuccess($result, 'in line ' . __LINE__);
    $this->assertEquals(2, $result['count'], 'In line ' . __LINE__);
    $this->assertEquals($this->test_activity_type_value, $result['values'][$activity['id']]['activity_type_id'], 'In line ' . __LINE__);
    $this->assertEquals('Test activity type', $result['values'][$activity['id']]['activity_name'], 'In line ' . __LINE__);
    $this->assertEquals('Test activity type', $result['values'][$activity2['id']]['activity_name'], 'In line ' . __LINE__);
  }
  /*
 * test chained Activity format
 */
  function testchainedActivityGet() {

   $activity = civicrm_api('Contact', 'Create', [
      'version' => $this->_apiversion,
        'display_name' => "bob brown",
        'contact_type' => 'Individual',
        'api.activity_type.create' => [
          'weight' => '2',
          'label' => 'send out letters',
          'filter' => 0,
          'is_active' => 1,
          'is_optgroup' => 1,
          'is_default' => 0,
        ], 'api.activity.create' => ['subject' => 'send letter', 'activity_type_id' => '$value.api.activity_type.create.values.0.value'],
      ]);

    $this->assertAPISuccess($activity, 'in line ' . __LINE__);
    $result = civicrm_api('Activity', 'Get', [
        'version' => 3,
        'id' => $activity['id'],
        'return.assignee_contact_id' => 1,
        'api.contact.get' => ['api.pledge.get' => 1],
      ]);
  }

  /**
   * check civicrm_activities_contact_get() with empty array
   */
  function testActivityContactGetEmpty() {
    $params = [];
    $result = civicrm_api('activity', 'get', $params);
    $this->assertEquals($result['is_error'], 1, "In line " . __LINE__);
  }

  /**
   *  Test  civicrm_activity_contact_get() with missing source_contact_id
   */
  function testActivitiesContactGetWithInvalidParameter() {
    $params = NULL;
    $result = civicrm_api('activity', 'get', $params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_contact_get() with invalid Contact Id
   */
  function testActivitiesContactGetWithInvalidContactId() {
    $params = ['contact_id' => NULL];
    $result = civicrm_api('activity', 'get', $params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );

    $params = ['contact_id' => 'contact'];
    $result = civicrm_api('activity', 'get', $params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );

    $params = ['contact_id' => 2.4];
    $result = civicrm_api('activity', 'get', $params);
    $this->assertEquals($result['is_error'], 1,
      "In line " . __LINE__
    );
  }

  /**
   *  Test civicrm_activity_contact_get() with contact having no Activity
   */
  function testActivitiesContactGetHavingNoActivity() {
    $params = [
      'first_name' => 'dan',
      'last_name' => 'conberg',
      'email' => 'dan.conberg@w.co.in',
      'contact_type' => 'Individual',
      'version' => $this->_apiversion,
    ];

    $contact = civicrm_api('contact', 'create', $params);
    $params = [
      'contact_id' => $contact['id'],
      'version' => $this->_apiversion,
    ];
    $result = civicrm_api('activity', 'get', $params);
    $this->assertEquals($result['is_error'], 0, 'in line ' . __LINE__);
    $this->assertEquals($result['count'], 0, 'in line ' . __LINE__);
  }

  function testGetFields() {
    $params = ['version' => 3, 'action' => 'create'];
    $result = civicrm_api('activity', 'getfields', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, NULL, NULL, 'getfields');
    $this->assertTrue(is_array($result['values']), 'get fields doesnt return values array in line ' . __LINE__);
    // $this->assertTrue(is_array($result['values']['priority_id']['options']));
    foreach ($result['values'] as $key => $value) {
      $this->assertTrue(is_array($value), $key . " is not an array in line " . __LINE__);
    }
  }
}
