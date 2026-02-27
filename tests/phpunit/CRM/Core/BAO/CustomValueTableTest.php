<?php

require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/Contact.php';
require_once 'CiviTest/Custom.php';

class CRM_Core_BAO_CustomValueTableTest extends CiviUnitTestCase 
{
    public function get_info( ) 
    {
        return [
                     'name'        => 'Custom Value Table BAOs',
                     'description' => 'Test all Core_BAO_CustomValueTable methods.',
                     'group'       => 'CiviCRM BAO Tests',
                     ];
    }
    
    public function setUp( ) 
    {
        parent::setUp();
    }


    /*
     * function to test store function for country
     *
     */
    public function testStoreCountry()
    {
        $params      = [];
        $contactID   = Contact::createIndividual();
        $customGroup = Custom::createGroup( $params ,'Individual' );
        $fields      =  [
                              'groupId'  => $customGroup->id,
                              'dataType' => 'Country',
                              'htmlType' => 'Select Country'
                              ];
        
        $customField = Custom::createField( $params, $fields );
        
        $params[] = [ $customField->id =>  [
						      'value'            => 1228,
						      'type'             => 'Country',
						      'custom_field_id'  => $customField->id,
						      'custom_group_id'  => $customGroup->id,
						      'table_name'       => 'civicrm_value_test_group_'.$customGroup->id,
						      'column_name'      => 'test_Country_'.$customField->id,
						      'file_id'          => ''
						      ]];

        require_once 'CRM/Core/BAO/CustomValueTable.php';
        CRM_Core_BAO_CustomValueTable::store( $params, 'civicrm_contact', $contactID );
        //        $this->assertDBCompareValue('CRM_Custom_DAO_CustomValue', )
        
        Custom::deleteField( $customField );        
        Custom::deleteGroup( $customGroup );
        Contact::delete( $contactID );
    }

    /*
     * function to test store function for file
     *
     */
    public function atestStoreFile()
    {
        $params      = [];
        $contactID   = Contact::createIndividual();
        $customGroup = Custom::createGroup( $params ,'Individual' );
        $fields      =  [
                              'groupId'  => $customGroup->id,
                              'dataType' => 'File',
                              'htmlType' => 'File'
                              ];
        
        $customField = Custom::createField( $params, $fields );
        
        $params[] = [ $customField->id =>  [
						      'value'            => 'i/contact_house.png',
						      'type'             => 'File',
						      'custom_field_id'  => $customField->id,
						      'custom_group_id'  => $customGroup->id,
						      'table_name'       => 'civicrm_value_test_group_'.$customGroup->id,
						      'column_name'      => 'test_File_'.$customField->id,
						      'file_id'          => 1
						      ]];

        require_once 'CRM/Core/BAO/CustomValueTable.php';
        CRM_Core_BAO_CustomValueTable::store( $params, 'civicrm_contact', $contactID );
        //        $this->assertDBCompareValue('CRM_Custom_DAO_CustomValue', )
        
        Custom::deleteField( $customField );        
        Custom::deleteGroup( $customGroup );
        Contact::delete( $contactID );
    }

    /*
     * function to test store function for state province
     *
     */
    public function testStoreStateProvince()
    {
        $params      = [];
        $contactID   = Contact::createIndividual();
        $customGroup = Custom::createGroup( $params ,'Individual' );
        $fields      =  [
                              'groupId'  => $customGroup->id,
                              'dataType' => 'StateProvince',
                              'htmlType' => 'Select State/Province'
                              ];
        
        $customField = Custom::createField( $params, $fields );
        
        $params[] = [ $customField->id =>  [
						      'value'            => 1029,
						      'type'             => 'StateProvince',
						      'custom_field_id'  => $customField->id,
						      'custom_group_id'  => $customGroup->id,
						      'table_name'       => 'civicrm_value_test_group_'.$customGroup->id,
						      'column_name'      => 'test_StateProvince_'.$customField->id,
						      'file_id'          => 1
						      ]];
	
        require_once 'CRM/Core/BAO/CustomValueTable.php';
        CRM_Core_BAO_CustomValueTable::store( $params, 'civicrm_contact', $contactID );
        //        $this->assertDBCompareValue('CRM_Custom_DAO_CustomValue', )
        
        Custom::deleteField( $customField );        
        Custom::deleteGroup( $customGroup );
        Contact::delete( $contactID );
    }

    /*
     * function to test store function for date
     *
     */
    public function testStoreDate()
    {
        $params      = [];
        $contactID   = Contact::createIndividual();
        $customGroup = Custom::createGroup( $params ,'Individual' );
        $fields      =  [
                              'groupId'  => $customGroup->id,
                              'dataType' => 'Date',
                              'htmlType' => 'Select Date'
                              ];
        
        $customField = Custom::createField( $params, $fields );
        
        $params[] = [ $customField->id =>  [
						      'value'            => '20080608000000',
						      'type'             => 'Date',
						      'custom_field_id'  => $customField->id,
						      'custom_group_id'  => $customGroup->id,
						      'table_name'       => 'civicrm_value_test_group_'.$customGroup->id,
						      'column_name'      => 'test_Date_'.$customField->id,
						      'file_id'          => ''
						      ]];

        require_once 'CRM/Core/BAO/CustomValueTable.php';
        CRM_Core_BAO_CustomValueTable::store( $params, 'civicrm_contact', $contactID );
        //        $this->assertDBCompareValue('CRM_Custom_DAO_CustomValue', )
        
        Custom::deleteField( $customField );        
        Custom::deleteGroup( $customGroup );
        Contact::delete( $contactID );
    }

    /*
     * function to test store function for rich text editor
     *
     */
    public function testStoreRichTextEditor()
    {
        $params      = [];
        $contactID   = Contact::createIndividual();
        $customGroup = Custom::createGroup( $params ,'Individual' );
        $fields      =  [
                              'groupId'  => $customGroup->id,
                              'htmlType' => 'RichTextEditor',
                              'dataType' => 'Memo'
                              ];
        
        $customField = Custom::createField( $params, $fields );
        
        $params[] = [ $customField->id =>  [
						      'value'            => '<p><strong>This is a <u>test</u></p>',
						      'type'             => 'Memo',
						      'custom_field_id'  => $customField->id,
						      'custom_group_id'  => $customGroup->id,
						      'table_name'       => 'civicrm_value_test_group_'.$customGroup->id,
						      'column_name'      => 'test_Memo_'.$customField->id,
						      'file_id'          => ''
						      ]];
        
        require_once 'CRM/Core/BAO/CustomValueTable.php';
        CRM_Core_BAO_CustomValueTable::store( $params, 'civicrm_contact', $contactID );
        //        $this->assertDBCompareValue('CRM_Custom_DAO_CustomValue', )
        
        Custom::deleteField( $customField );        
        Custom::deleteGroup( $customGroup );
        Contact::delete( $contactID );
    }

    /*
     * function to test getEntityValues function for stored value
     *
     */
    public function testgetEntityValues()
    {

        $params      = [];
        $contactID   = Contact::createIndividual();
        $customGroup = Custom::createGroup( $params ,'Individual' );
        $fields      =  [
                              'groupId'  => $customGroup->id,
                              'htmlType' => 'RichTextEditor',
                              'dataType' => 'Memo'
                              ];
        
        $customField = Custom::createField( $params, $fields );
        
        $params[] = [ $customField->id =>  [
						      'value'            => '<p><strong>This is a <u>test</u></p>',
						      'type'             => 'Memo',
						      'custom_field_id'  => $customField->id,
						      'custom_group_id'  => $customGroup->id,
						      'table_name'       => 'civicrm_value_test_group_'.$customGroup->id,
						      'column_name'      => 'test_Memo_'.$customField->id,
						      'file_id'          => ''
						      ]];
        
        require_once 'CRM/Core/BAO/CustomValueTable.php';
        CRM_Core_BAO_CustomValueTable::store( $params, 'civicrm_contact', $contactID );
        //        $this->assertDBCompareValue('CRM_Custom_DAO_CustomValue', )

        require_once 'CRM/Core/BAO/CustomValueTable.php';
        $entityValues =  CRM_Core_BAO_CustomValueTable::getEntityValues( $contactID, 'Individual' );
              
        $this->assertEquals( $entityValues[$customField->id] ,'<p><strong>This is a <u>test</u></p>',
                            'Checking same for returned value.' );    
        Custom::deleteField( $customField );        
        Custom::deleteGroup( $customGroup );
        Contact::delete( $contactID );
    }

    public function testCustomGroupMultiple( ) {
        $params      = [];
        $contactID   = Contact::createIndividual();
        $customGroup = Custom::createGroup( $params ,'Individual' );

        $fields      =  [
                              'groupId'  => $customGroup->id,
                              'dataType' => 'String',
                              'htmlType' => 'Text'
                              ];

        $customField = Custom::createField( $params, $fields );

        $params = [ 'entityID'                       => $contactID,
                         'custom_'.$customField->id.'_-1' => 'First String',
                         ];
        $error = CRM_Core_BAO_CustomValueTable::setValues( $params );
        
        $newParams = [ 'entityID'                  => $contactID,
                            'custom_'.$customField->id  => 1 ];
        $result = CRM_Core_BAO_CustomValueTable::getValues( $newParams );
        
        $this->assertEquals( $params['custom_'.$customField->id.'_-1'], $result['custom_'.$customField->id] );
        $this->assertEquals( $params['entityID'], $result['entityID'] );
	
	Custom::deleteField( $customField );        
        Custom::deleteGroup( $customGroup );
        Contact::delete( $contactID );
    }
    
}
