<?php

require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/Contact.php';
require_once 'CiviTest/Custom.php';

class CRM_Core_BAO_CustomFieldTest extends CiviUnitTestCase 
{
      function get_info( ) 
    {
        return array(
                     'name'        => 'Custom Field BAOs',
                     'description' => 'Test all Core_BAO_CustomField methods.',
                     'group'       => 'CiviCRM BAO Tests',
                     );
    }

    function setUp()
    {
        parent::setUp();
    }    
   
    function testCreateCustomfield()
    {
        $customGroup = Custom::createGroup( array(), 'Individual' );
        $fields = array(
                        'label'            => 'testFld',
                        'data_type'        => 'String',
                        'html_type'        => 'Text',
                        'custom_group_id'  => $customGroup->id,
                        );
        $customField = CRM_Core_BAO_CustomField::create( $fields );
        $customFieldID = $this->assertDBNotNull( 'CRM_Core_DAO_CustomField',  $customGroup->id , 'id','custom_group_id' ,
                                           'Database check for created CustomField.' );
        $fields = array(
                        'id'               => $customFieldID,
                        'label'            => 'editTestFld',
                        'is_active'        => 1,
                        'data_type'        => 'String',
                        'html_type'        => 'Text',
                        'custom_group_id'  => $customGroup->id,
                        );
           
        $customField = CRM_Core_BAO_CustomField::create( $fields );
        $this->assertDBNotNull( 'CRM_Core_DAO_CustomField',1 , 'id','is_active' ,'Database check for edited CustomField.' );
        $this->assertDBNotNull( 'CRM_Core_DAO_CustomField' ,$fields['label'], 'id','label' ,'Database check for edited CustomField.' );
        
        Custom::deleteGroup( $customGroup );
    }
    
    function testGetFields()
    {
        $customGroup = Custom::createGroup( array(), 'Individual' );
        $fields = array(
                        'label'            => 'testFld1',
                        'data_type'        => 'String',
                        'html_type'        => 'Text',
                        'is_active'        => 1,
                        'custom_group_id'  => $customGroup->id,
                        );
        $customField1 = CRM_Core_BAO_CustomField::create( $fields );
        $customFieldID1 = $this->assertDBNotNull( 'CRM_Core_DAO_CustomField',  $customGroup->id , 'id','custom_group_id' ,
                                           'Database check for created CustomField.' );
        $fields = array(
                        'label'            => 'testFld2',
                        'data_type'        => 'String',
                        'html_type'        => 'Text',
                        'is_active'        => 1,
                        'custom_group_id'  => $customGroup->id,
                        );
        $customField2 = CRM_Core_BAO_CustomField::create( $fields );
        $customFieldID2 = $this->assertDBNotNull( 'CRM_Core_DAO_CustomField',  $customGroup->id , 'id','custom_group_id' ,
                                           'Database check for created CustomField.' );
        $getCustomFields=array();
        $getCustomFields = CRM_Core_BAO_CustomField::getFields('Individual', true, true);
        //CRM_Core_Error::debug('fdf',$getCustomFields);
        //$this->assertEquals( 'testFld1',  $getCustomFields[$customFieldID1][0], 'Confirm First Custom field label' );
        //$this->assertEquals( 'testFld2',  $getCustomFields[$customFieldID2][0], 'Confirm Second Custom field label' );
        
       
        Custom::deleteGroup( $customGroup );
    }
    
    function testGetDisplayedValues()
    {
        $customGroup = Custom::createGroup( array(), 'Individual' );
        $fields = array(
                        'label'            => 'testCountryFld1',
                        'data_type'        => 'Country',
                        'html_type'        => 'Select Country',
                        'is_active'        => 1,
                        'default_value'    => 1228,
                        'custom_group_id'  => $customGroup->id,
                        );
        $customField1 = CRM_Core_BAO_CustomField::create( $fields );
        $customFieldID1 = $this->assertDBNotNull( 'CRM_Core_DAO_CustomField',  $customGroup->id , 'id','custom_group_id' ,
                                           'Database check for created CustomField.' );
        $options=array();
        $options[$customFieldID1]['attributes']=  array(
                        'label'            => 'testCountryFld1',
                        'data_type'        => 'Country',
                        'html_type'        => 'Select Country',
                        );
        $display = CRM_Core_BAO_CustomField::getDisplayValue($fields['default_value'], $customFieldID1,$options );
       
        $this->assertEquals( 'United States',  $display, 'Confirm Country display Name' );
              
        Custom::deleteGroup( $customGroup );
    }
    function testDeleteCustomfield()
    {
        $customGroup = Custom::createGroup( array(), 'Individual' );
        $fields      = array (
                              'groupId'  => $customGroup->id,
                              'dataType' => 'Memo',
                              'htmlType' => 'TextArea'
                              );
                              
        $customField = Custom::createField( array(), $fields );
        $this->assertNotNull( $customField );
        CRM_Core_BAO_CustomField::deleteField($customField );
        $this->assertDBNull( 'CRM_Core_DAO_CustomField', $customGroup->id, 'id', 
                                             'custom_group_id', 'Database check for deleted Custom Field.' );
        Custom::deleteGroup( $customGroup );
    }
}
?>