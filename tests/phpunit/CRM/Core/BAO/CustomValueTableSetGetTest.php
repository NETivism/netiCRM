<?php

require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/Contact.php';
require_once 'CiviTest/Custom.php';

class CRM_Core_BAO_CustomValueTableSetGetTest extends CiviUnitTestCase 
{
    function get_info( ) 
    {
        return [
                     'name'        => 'Custom Value Table BAO setValues and getValues',
                     'description' => 'Test setValues and getValues Core_BAO_CustomValueTable methods.',
                     'group'       => 'CiviCRM BAO Tests',
                     ];
    }
    
    function setUp( ) 
    {
        parent::setUp();
    }

    /*
     * Test setValues() and GetValues() methods with custom Date field
     *
     */
    function testSetGetValuesDate()
    {
        $params      = [];
        $contactID   = Contact::createIndividual();

        //create Custom Group
        $customGroup = Custom::createGroup( $params ,'Individual', true );
        
        //create Custom Field of data type Date
        $fields      =  [
                              'groupId'  => $customGroup->id,
                              'dataType' => 'Date',
                              'htmlType' => 'Select Date'
                              ];
        $customField = Custom::createField( $params, $fields );
        
        // Retrieve the field ID for sample custom field 'test_Date'
        $params = [ 'label'   => 'test_Date'];
        $field  = [ ];
        
        require_once 'CRM/Core/BAO/CustomField.php';
        CRM_Core_BAO_CustomField::retrieve( $params, $field );
        $fieldID = $field['id'];

        // Set test_Date to a valid date value
        $date = '20080608000000';
        $params = [ 'entityID'           => $contactID,
                         'custom_' . $fieldID => $date ];
        require_once 'CRM/Core/BAO/CustomValueTable.php';
        $result = CRM_Core_BAO_CustomValueTable::setValues( $params );
        $this->assertEquals( $result['is_error'], 0, 'Verify that is_error = 0 (success).');

        // Check that the date value is stored
        $values = [ ];
        $params = [ 'entityID'           => $contactID,
                         'custom_' . $fieldID => 1 ];
        $values = CRM_Core_BAO_CustomValueTable::getValues( $params );

        $this->assertEquals( $values['is_error'], 0, 'Verify that is_error = 0 (success).');
        require_once 'CRM/Utils/Date.php';
        $this->assertEquals( $values['custom_' . $fieldID . '_1'], 
                             CRM_Utils_Date::mysqlToIso($date), 
                             'Verify that the date value is stored for contact ' . $contactID);
        
        // Now set test_Date to an invalid date value and try to reset
        $badDate = '20080631000000';
        $params   = [ 'entityID'           => $contactID,
                           'custom_' . $fieldID => $badDate ];
        require_once 'CRM/Core/BAO/CustomValueTable.php';
        $result = CRM_Core_BAO_CustomValueTable::setValues( $params );
        
        // Check that the error flag is set AND that custom date value has not been modified
        $this->assertEquals( $result['is_error'], 1, 'Verify that is_error = 1 when bad date is passed.');
        
        $params = [ 'entityID'               => $contactID,
                         'custom_' . $fieldID => 1];
        $values = CRM_Core_BAO_CustomValueTable::getValues( $params );
        $this->assertEquals( $values['custom_' . $fieldID .'_1'], 
                             CRM_Utils_Date::mysqlToIso($date), 
                             'Verify that the date value has NOT been updated for contact ' . $contactID);
        
        // Test setting test_Date to null
        $params = [ 'entityID'           => $contactID,
                         'custom_' . $fieldID => null ];
        require_once 'CRM/Core/BAO/CustomValueTable.php';
        $result = CRM_Core_BAO_CustomValueTable::setValues( $params );
        
        // Check that the date value is empty
        $params = [ 'entityID'           => $contactID,
                         'custom_' . $fieldID => 1];
        $values = CRM_Core_BAO_CustomValueTable::getValues( $params );
        $this->assertEquals( $values['is_error'], 0, 'Verify that is_error = 0 (success).');
        
        // Cleanup 
        Custom::deleteField( $customField );        
        Custom::deleteGroup( $customGroup );
        Contact::delete( $contactID );
    }
    
     /*
     * Test setValues() and getValues() methods with custom field YesNo(Boolean) Radio
     *
     */
    function testSetGetValuesYesNoRadio()
    {
      $params      = [];
      $contactID   = Contact::createIndividual();
      
      //create Custom Group
      $customGroup = Custom::createGroup( $params ,'Individual', true );
      
      //create Custom Field of type YesNo(Boolean) Radio
      $fields      =  [
			    'groupId'  => $customGroup->id,
			    'dataType' => 'Boolean',
			    'htmlType' => 'Radio'
			    ];
      $customField = Custom::createField( $params, $fields );

      // Retrieve the field ID for sample custom field 'test_Boolean'
      $params = [ 'label'   => 'test_Boolean'];
      $field  = [ ];
      
      //get field Id
      require_once 'CRM/Core/BAO/CustomField.php';
      CRM_Core_BAO_CustomField::retrieve( $params, $field );
      
      $fieldID = $field['id'];
      
      // valid boolean value '1' for Boolean Radio
      $yesNo = '1';
      $params = [ 'entityID'           => $contactID,
                       'custom_' . $fieldID => $yesNo ];
      require_once 'CRM/Core/BAO/CustomValueTable.php';
      $result = CRM_Core_BAO_CustomValueTable::setValues( $params );
	
      $this->assertEquals( $result['is_error'], 0, 'Verify that is_error = 0 (success).');
      
      // Check that the YesNo radio value is stored
      $values = [ ];
      $params = [ 'entityID'           => $contactID,
                       'custom_' . $fieldID => 1];
      $values = CRM_Core_BAO_CustomValueTable::getValues( $params );
            
      $this->assertEquals( $values['is_error'], 0, 'Verify that is_error = 0 (success).');      
      $this->assertEquals( $values['custom_2_1'], $yesNo, 
                           'Verify that the date value is stored for contact ' . $contactID );
      
      
      // Now set YesNo radio to an invalid boolean value and try to reset
      $badYesNo = '20';
      $params   = [ 'entityID'           => $contactID,
                         'custom_' . $fieldID => $badYesNo ];
      require_once 'CRM/Core/BAO/CustomValueTable.php';
      $result = CRM_Core_BAO_CustomValueTable::setValues( $params );
      
      // Check that the error flag is set AND that custom date value has not been modified
      $this->assertEquals( $result['is_error'], $yesNo, 'Verify that is_error = 1 when bad boolen value is passed.');
      
      $params = [ 'entityID'               => $contactID,
                       'custom_' . $fieldID     => 1];
      $values = CRM_Core_BAO_CustomValueTable::getValues( $params );
            
      $this->assertEquals( $values['custom_2_1'], $yesNo,
                           'Verify that the date value has NOT been updated for contact ' . $contactID);
      
      // Cleanup 
      Custom::deleteField( $customField );        
      Custom::deleteGroup( $customGroup );
      Contact::delete( $contactID );
    }    
}
