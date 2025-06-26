
<?php

require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CRM/Contact/BAO/Relationship.php';
require_once 'CRM/Contact/BAO/RelationshipType.php';
require_once 'CiviTest/Contact.php';
require_once 'CRM/Contact/BAO/ContactType.php';

class CRM_Contact_BAO_ContactType_RelationshipTest extends CiviUnitTestCase 
{
    
    function get_info( ) 
    {
        return [
                     'name'        => 'Relationship Subtype',
                     'description' => 'Test Relattionship for subtype.',
                     'group'       => 'CiviCRM BAO Tests',
                     ];
    }
    
    function setUp( ) 
    {        
        parent::setUp();

        //create contact subtypes
        $params = [ 'label'    => 'indivi_student',
                         'name'      => 'indivi_student',
                         'parent_id' => 1,//Individual
                         'is_active' => 1
                         ];
        $result  = CRM_Contact_BAO_ContactType::add( $params );
        $this->student = $params['name']; 
        
        $params = [ 'label'     => 'indivi_parent',
                         'name'      => 'indivi_parent',
                         'parent_id' => 1,//Individual
                         'is_active' => 1
                         ];
        $result  = CRM_Contact_BAO_ContactType::add( $params );
        $this->parent = $params['name']; 

        $params = [ 'label'     => 'org_sponsor',
                         'name'      => 'org_sponsor',
                         'parent_id' => 3,//Organization
                         'is_active' => 1
                         ];
        $result  = CRM_Contact_BAO_ContactType::add( $params );
        $this->sponsor =  $params['name'];

        //create contacts
        $params = [ 'first_name'   => 'Anne',     
                         'last_name'    => 'Grant',
                         'contact_type' => 'Individual',
                         ];
        $this->individual = Contact::create( $params );
        
        $params = [ 'first_name'   => 'Bill',     
                         'last_name'    => 'Adams',
                         'contact_type' => 'Individual',
                         'contact_sub_type' => $this->student
                         ];
        $this->indivi_student = Contact::create( $params );
        
        $params = [ 'first_name'   => 'Alen',     
                         'last_name'    => 'Adams',
                         'contact_type' => 'Individual',
                         'contact_sub_type' => $this->parent
                         ];
        $this->indivi_parent = Contact::create( $params );
        
        $params = [ 'organization_name' => 'Compumentor' ,     
                         'contact_type'      => 'Organization',
                         ];
        $this->organization = Contact::create( $params );  
        
        $params = [ 'organization_name' => 'Conservation Corp' ,     
                         'contact_type'      => 'Organization',
                         'contact_sub_type'  => $this->sponsor
                         ];
        $this->organization_sponsor = Contact::create( $params );
        
    }

    function tearDown ( )
    {
        $this->quickCleanup( [ 'civicrm_contact' ] );
                   
        $query = "
DELETE FROM civicrm_contact_type 
      WHERE name IN ('{$this->student}','{$this->parent}','{$this->sponsor}');
    ";
        require_once 'CRM/Core/DAO.php';
        CRM_Core_DAO::executeQuery( $query );
    }
    /**
     * methods create relationshipType with valid data
     * success expected
     * 
     */
    function testRelationshipTypeAddIndiviParent( )
    {
        //check Individual to Parent RelationshipType 
        $params = [ 'name_a_b'           => 'indivToparent',
                         'name_b_a'           => 'parentToindiv',
                         'contact_type_a'     => 'Individual',
                         'contact_type_b'     => 'Individual',
                         'contact_sub_type_b' => $this->parent,
                         ];
        $ids    = [ ];
        $result = CRM_Contact_BAO_RelationshipType::add( $params, $ids );
        $this->assertEquals( $result->name_a_b , 'indivToparent' );
        $this->assertEquals( $result->contact_type_a , 'Individual' );
        $this->assertEquals( $result->contact_type_b , 'Individual' );
        $this->assertEquals( $result->contact_sub_type_b , $this->parent );
        $this->relationshipTypeDelete( $result->id );
    }

    function testRelationshipTypeAddSponcorIndivi( )
    {
        //check Sponcor to Individual RelationshipType
        $params = [ 'name_a_b'           => 'SponsorToIndiv',
                         'name_b_a'           => 'IndivToSponsor',
                         'contact_type_a'     => 'Organization',
                         'contact_sub_type_a' => $this->sponsor,
                         'contact_type_b'     => 'Individual',
                         ];
        $ids    = [ ];
        $result = CRM_Contact_BAO_RelationshipType::add( $params, $ids );
        $this->assertEquals( $result->name_a_b , 'SponsorToIndiv' );
        $this->assertEquals( $result->contact_type_a , 'Organization' );
        $this->assertEquals( $result->contact_sub_type_a ,  $this->sponsor );
        $this->assertEquals( $result->contact_type_b , 'Individual' );
        $this->relationshipTypeDelete( $result->id );
    }
    
    function testRelationshipTypeAddStudentSponcor( )
    {
        //check Student to Sponcer RelationshipType
        $params = [ 'name_a_b'           => 'StudentToSponser',
                         'name_b_a'           => 'SponsorToStudent',
                         'contact_type_a'     => 'Individual',
                         'contact_sub_type_a' => $this->student,
                         'contact_type_b'     => 'Organization',
                         'contact_sub_type_b' => $this->sponsor,
                         ];
        $ids    = [ ];
        $result = CRM_Contact_BAO_RelationshipType::add( $params, $ids );
        $this->assertEquals( $result->name_a_b , 'StudentToSponser' );
        $this->assertEquals( $result->contact_type_a , 'Individual' );
        $this->assertEquals( $result->contact_sub_type_a , $this->student );
        $this->assertEquals( $result->contact_type_b , 'Organization' );
        $this->assertEquals( $result->contact_sub_type_b , $this->sponsor );
        $this->relationshipTypeDelete( $result->id );
    }

    /**
     * methods create relationshipe within same contact type with invalid Relationships
     * 
     */
    function testRelationshipCreateInvalidWithinSameType( ) 
    {
        //check for Individual to Parent
        $relTypeParams = [ 'name_a_b'           => 'indivToparent',
                                'name_b_a'           => 'parentToindiv',
                                'contact_type_a'     => 'Individual',
                                'contact_type_b'     => 'Individual',
                                'contact_sub_type_b' => $this->parent,
                                ];
        $relTypeIds = [ ];
        $relType    = CRM_Contact_BAO_RelationshipType::add( $relTypeParams, $relTypeIds );
        $params     = [ 'relationship_type_id' => $relType->id.'_a_b',
                             'contact_check'        => [ $this->indivi_student => 1 ]
                             ];
        $ids = ['contact' => $this->individual ];

        list( $valid, $invalid, $duplicate, $saved, $relationshipIds)  
            = CRM_Contact_BAO_Relationship::create( $params, $ids );
 
        $this->assertEquals( $invalid, 1, 'In line '. __LINE__ );
        $this->assertEquals( empty($relationshipIds), true , 'In line '. __LINE__ );
        $this->relationshipTypeDelete( $relType->id );
    }
    
    /**
     * methods create relationshipe within diff contact type with invalid Relationships
     * 
     */
    function testRelCreateInvalidWithinDiffTypeSpocorIndivi( ) 
    {
        //check for Sponcer to Individual
        $relTypeParams = [ 'name_a_b'           => 'SponsorToIndiv',
                                'name_b_a'           => 'IndivToSponsor',
                                'contact_type_a'     => 'Organization',
                                'contact_sub_type_a' => $this->sponsor,
                                'contact_type_b'     => 'Individual',
                                ];
        $relTypeIds = [ ];
        $relType    = CRM_Contact_BAO_RelationshipType::add( $relTypeParams, $relTypeIds );
        $params     = [ 'relationship_type_id' => $relType->id.'_a_b',
                             'contact_check'        => [ $this->individual => 1 ]
                             ];
        $ids = ['contact' => $this->indivi_parent ];

        list( $valid, $invalid, $duplicate, $saved, $relationshipIds)  
            = CRM_Contact_BAO_Relationship::create( $params, $ids );

        $this->assertEquals( $invalid, 1, 'In line '. __LINE__ );
        $this->assertEquals( empty($relationshipIds), true , 'In line '. __LINE__ );
        $this->relationshipTypeDelete( $relType->id );
    }
    
    function testRelCreateInvalidWithinDiffTypeStudentSponcor( ) 
    {
        //check for Student to Sponcer
        $relTypeParams =  [ 'name_a_b'           => 'StudentToSponser',
                                 'name_b_a'           => 'SponsorToStudent',
                                 'contact_type_a'     => 'Individual',
                                 'contact_sub_type_a' => $this->student,
                                 'contact_type_b'     => 'Organization',
                                 'contact_sub_type_b' => 'Sponser',
                                 ];
        $relTypeIds = [ ];
        $relType    = CRM_Contact_BAO_RelationshipType::add( $relTypeParams, $relTypeIds );
        $params     = [ 'relationship_type_id' => $relType->id.'_a_b',
                             'contact_check'        => [ $this->individual => 1 ]
                             ];
        $ids = ['contact' => $this->indivi_parent ];

        list( $valid, $invalid, $duplicate, $saved, $relationshipIds)  
            = CRM_Contact_BAO_Relationship::create( $params, $ids );

        $this->assertEquals( $invalid, 1, 'In line '. __LINE__ );
        $this->assertEquals( empty($relationshipIds), true , 'In line '. __LINE__ );
        $this->relationshipTypeDelete( $relType->id );
    }
    
    /**
     * methods create relationshipe within same contact type with valid data
     * success expected 
     * 
     */
    function testRelationshipCreateWithinSameType( ) 
    {
        //check for Individual to Parent
        $relTypeParams = [ 'name_a_b'           => 'indivToparent',
                                'name_b_a'           => 'parentToindiv',
                                'contact_type_a'     => 'Individual',
                                'contact_type_b'     => 'Individual',
                                'contact_sub_type_b' => $this->parent,
                                ];
        $relTypeIds = [ ];
        $relType    = CRM_Contact_BAO_RelationshipType::add( $relTypeParams, $relTypeIds );
        $params     = [ 'relationship_type_id' => $relType->id.'_a_b',
                             'is_active'            => 1,
                             'contact_check'        => [ $this->indivi_parent => $this->indivi_parent ]
                             ];
        $ids = ['contact' => $this->individual ];
        list( $valid, $invalid, $duplicate, $saved, $relationshipIds)  
            = CRM_Contact_BAO_Relationship::create( $params, $ids );

        $this->assertEquals( $valid, 1 , 'In line '. __LINE__ );
        $this->assertEquals( empty($relationshipIds), false , 'In line '. __LINE__ );
        $this->relationshipTypeDelete( $relType->id );
    }

    /**
     * methods create relationshipe within different contact type with valid data
     * success expected 
     * 
     */
    function testRelCreateWithinDiffTypeSponsorIndivi( ) 
    { 
        //check for Sponcer to Individual
        $relTypeParams = [ 'name_a_b'           => 'SponsorToIndiv',
                                'name_b_a'           => 'IndivToSponsor',
                                'contact_type_a'     => 'Organization',
                                'contact_sub_type_a' => $this->sponsor,
                                'contact_type_b'     => 'Individual',
                                ];
        $relTypeIds = [ ];
        $relType    = CRM_Contact_BAO_RelationshipType::add( $relTypeParams, $relTypeIds );
        $params     = [ 'relationship_type_id' => $relType->id.'_a_b',
                             'is_active'            => 1,
                             'contact_check'        => [ $this->indivi_student => 1 ]
                             ];
        $ids = ['contact' => $this->organization_sponsor ];
        list( $valid, $invalid, $duplicate, $saved, $relationshipIds)  
            = CRM_Contact_BAO_Relationship::create( $params, $ids );
       
        $this->assertEquals( $valid, 1 , 'In line '. __LINE__ );
        $this->assertEquals( empty($relationshipIds), false , 'In line '. __LINE__ );
        $this->relationshipTypeDelete( $relType->id );
    } 

    function testRelCreateWithinDiffTypeStudentSponsor( ) 
    { 
        //check for Student to Sponcer
        $relTypeParams =  [ 'name_a_b'           => 'StudentToSponsor',
                                 'name_b_a'           => 'SponsorToStudent',
                                 'contact_type_a'     => 'Individual',
                                 'contact_sub_type_a' => $this->student,
                                 'contact_type_b'     => 'Organization',
                                 'contact_sub_type_b' => $this->sponsor,
                                 ];
        $relTypeIds = [ ];
        $relType    = CRM_Contact_BAO_RelationshipType::add( $relTypeParams, $relTypeIds );
        $params     = [ 'relationship_type_id' => $relType->id.'_a_b',
                             'is_active'            => 1,
                             'contact_check'        => [ $this->organization_sponsor => 1 ]
                             ];
        $ids = ['contact' => $this->indivi_student ];
        list( $valid, $invalid, $duplicate, $saved, $relationshipIds)  
            = CRM_Contact_BAO_Relationship::create( $params, $ids );

        $this->assertEquals( $valid, 1 , 'In line '. __LINE__ );
        $this->assertEquals( empty($relationshipIds), false , 'In line '. __LINE__ );
        $this->relationshipTypeDelete( $relType->id );
    }
    
}

?>