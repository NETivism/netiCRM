<?php

require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/Contact.php';

require_once 'CRM/Contact/BAO/Query.php';

/**                                                                                                                                                                         
 *  Include dataProvider for tests                                                                                                                                          
 */
require_once 'tests/phpunit/CRM/Mailing/BAO/QueryTestDataProvider.php';

class CRM_Mailing_BAO_QueryTest extends CiviUnitTestCase 
{

    use CRMTraits_Mailing_MailingTrait;

    function get_info( ) 
    {
        return [
                     'name'        => 'Mailing BAO Query',
                     'description' => 'Test all Mailing_BAO_Query methods.',
                     'group'       => 'CiviMail BAO Query Tests',
                     ];
    }
    
    public function dataProvider()
    {
        return new CRM_Mailing_BAO_QueryTestDataProvider;
    }

    function setUp( ) 
    {
        parent::setUp();
    }
    
    function tearDown()
    {
        $tablesToTruncate = [ 
                                   'civicrm_mailing_event_bounce',
                                   'civicrm_mailing_event_delivered',
                                   'civicrm_mailing_event_opened',
                                   'civicrm_mailing_event_reply',
                                   'civicrm_mailing_event_trackable_url_open',
                                   'civicrm_mailing_event_queue',
                                   'civicrm_mailing_trackable_url',
                                   'civicrm_mailing_job',
                                   'civicrm_mailing',
                                   'civicrm_email',
                                   'civicrm_contact',
                                   ];
        $this->quickCleanup( $tablesToTruncate );
    }
    
    /**
     *  Test CRM_Contact_BAO_Query::searchQuery()
     *  @dataProvider dataProvider
     */
    function testSearch( $fv, $count, $ids, $full )
    {
        $this->loadMailingDeliveryDataSet();

        $params = CRM_Contact_BAO_Query::convertFormValues( $fv );
        $obj = new CRM_Contact_BAO_Query( $params );
        $dao = $obj->searchQuery( );

        $contacts = [ ];
        while ( $dao->fetch( ) ) {
            $contacts[] = $dao->contact_id;
        }
        
        sort( $contacts, SORT_NUMERIC );

        $this->assertEquals( $ids, $contacts, 'In line ' . __LINE__ );
    }

}
