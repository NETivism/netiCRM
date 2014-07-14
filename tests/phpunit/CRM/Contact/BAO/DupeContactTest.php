<?php

require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/Contact.php';

class CRM_Contact_BAO_DupeContactTest extends CiviUnitTestCase 
{
    
    function setUp( ) 
    {
        parent::setUp();
    }
    
    function testFuzzyDupes( )
    {
        // make dupe checks based on based on following contact sets:
        // FIRST - LAST - EMAIL
        // ---------------------------------
        // robin  - hood - robin@example.com
        // robin  - hood - hood@example.com
        // robin  - dale - robin@example.com
        // little - dale - dale@example.com
        // will   - dale - dale@example.com
        // will   - dale - will@example.com
        // will   - dale - will@example.com

        // create a group to hold contacts, so that dupe checks don't consider any other contacts in the DB
        $params = array( 'name'        => 'Dupe Group',
                         'title'       => 'New Test Dupe Group',
                         'domain_id'   => 1,
                         'is_active'   => 1,
                         'visibility'  => 'Public Pages',
                         'version'     => 3,
                         );
        // TODO: This is not an API test!!
        $result  = &civicrm_api('group', 'create', $params );
        $groupId = $result['id'];

        // contact data set
        // FIXME: move create params to separate function
        $params = array(
                        array('first_name'   => 'robin',
                              'last_name'    => 'hood',
                              'email'        => 'robin@example.com',
                              'contact_type' => 'Individual'),

                        array('first_name'   => 'robin',
                              'last_name'    => 'hood',
                              'email'        => 'hood@example.com',
                              'contact_type' => 'Individual'),

                        array('first_name'   => 'robin',
                              'last_name'    => 'dale',
                              'email'        => 'robin@example.com',
                              'contact_type' => 'Individual'),

                        array('first_name'   => 'little',
                              'last_name'    => 'dale',
                              'email'        => 'dale@example.com',
                              'contact_type' => 'Individual'),

                        array('first_name'   => 'will',
                              'last_name'    => 'dale',
                              'email'        => 'dale@example.com',
                              'contact_type' => 'Individual'),

                        array('first_name'   => 'will',
                              'last_name'    => 'dale',
                              'email'        => 'will@example.com',
                              'contact_type' => 'Individual'),

                        array('first_name'   => 'will',
                              'last_name'    => 'dale',
                              'email'        => 'will@example.com',
                              'contact_type' => 'Individual'),
                         );

        $count = 1;
        // TODO: This is not an API test!!
        foreach ( $params as $param ) {
            $param['version'] = 3;
            $contact =& civicrm_api('contact', 'create', $param );
            $contactIds[$count++] = $contact['id'];

            $grpParams = array( 'contact_id' => $contact['id'],
                                'group_id'   => $groupId,
                                'version'    => 3 );
            $res = civicrm_api('group_contact', 'create', $grpParams );
        }

        // verify that all contacts have been created separately
        $this->assertEquals( count($contactIds), 7, 'Check for number of contacts.' );

        require_once 'CRM/Dedupe/DAO/RuleGroup.php';
        require_once 'CRM/Dedupe/Finder.php';
        $dao = new CRM_Dedupe_DAO_RuleGroup();
        $dao->contact_type = 'Individual';
        $dao->level        = 'Fuzzy';
        $dao->is_default   = 1;
        $dao->find( true );

        // ******** threshold = 20 ************ //
        $dao->threshold = 20;
        $dao->save();
        $foundDupes = CRM_Dedupe_Finder::dupesInGroup($dao->id, $groupId);
        // -------------------------------------------------------------------------
        // threshold = 20 => (First + Last + Email) Matches ( 1 pair )
        // --------------------------------------------------------------------------
        // will   - dale - will@example.com
        // will   - dale - will@example.com
        // so 1 pair for - first + last + mail
        $this->assertEquals( count($foundDupes), 1, 'Check for dupe counts for threshold=20.' );

        // ******** threshold = 17 ************ //
        $dao->threshold = 17;
        $dao->save();
        $foundDupes = CRM_Dedupe_Finder::dupesInGroup($dao->id, $groupId);
        // -------------------------------------------------------------------------
        // threshold = 17 => (Last + Email) Matches ( 2 pairs )
        // --------------------------------------------------------------------------
        // little - dale - dale@example.com
        // will   - dale - dale@example.com
        // will   - dale - will@example.com
        // will   - dale - will@example.com
        // so 2 pairs for - last + email
        $this->assertEquals( count($foundDupes), 2, 'Check for dupe counts for threshold=17.' );

        // ******** threshold = 15 ************ //
        $dao->threshold = 15;
        $dao->save();
        $foundDupes = CRM_Dedupe_Finder::dupesInGroup($dao->id, $groupId);
        // -------------------------------------------------------------------------
        // threshold = 15 => (First + Email) OR (Last + Email) Matches ( 3 pairs )
        // --------------------------------------------------------------------------
        // robin  - hood - robin@example.com
        // robin  - dale - robin@example.com
        // little - dale - dale@example.com
        // will   - dale - dale@example.com
        // will   - dale - will@example.com
        // will   - dale - will@example.com
        // so 3 pairs for - first / last + email
        $this->assertEquals( count($foundDupes), 3, 'Check for dupe counts for threshold=15.' );

        // ******** threshold = 10 ************ //
        $dao->threshold = 10;
        $dao->save();
        $foundDupes = CRM_Dedupe_Finder::dupesInGroup($dao->id, $groupId);
        // ------------------------------------------------------------------------------------------------------
        // threshold = 10 => (Email) OR (First + Email) OR (Last + Email) OR (First + Last) Matches ( 6 pairs )
        // ------------------------------------------------------------------------------------------------------
        // so 6 pairs for - first / last + email
        $this->assertEquals( count($foundDupes), 6, 'Check for dupe counts for threshold=10.' );

        // ******** threshold = 7 ************ //
        $dao->threshold = 7;
        $dao->save();
        $foundDupes = CRM_Dedupe_Finder::dupesInGroup($dao->id, $groupId);
        // ----------------------------------------------------------
        // threshold = 7 => All matches except first name (12 pairs)
        // ----------------------------------------------------------
        $this->assertEquals( count($foundDupes), 12, 'Check for dupe counts for threshold=7.' );

        // ******** threshold = 5 ************ //
        $dao->threshold = 5;
        $dao->save();
        $foundDupes = CRM_Dedupe_Finder::dupesInGroup($dao->id, $groupId);
        // ----------------------------------------------------------
        // threshold = 5 => All matches except first name (13 pairs)
        // ----------------------------------------------------------
        $this->assertEquals( count($foundDupes), 13, 'Check for dupe counts for threshold=5.' );

        // delete all created contacts
        foreach ( $contactIds as $contactId ) {
            Contact::delete( $contactId );
        }
        // delete dupe group
        $params = array( 'id' => $groupId, 'version' => 3 );
        civicrm_api('group', 'delete', $params );
    }

    function testDupesByParams( )
    {
        // make dupe checks based on based on following contact sets:
        // FIRST - LAST - EMAIL
        // ---------------------------------
        // robin  - hood - robin@example.com
        // robin  - hood - hood@example.com
        // robin  - dale - robin@example.com
        // little - dale - dale@example.com
        // will   - dale - dale@example.com
        // will   - dale - will@example.com
        // will   - dale - will@example.com

        // contact data set
        // FIXME: move create params to separate function
        $params = array( 
                        array('first_name'   => 'robin',     
                              'last_name'    => 'hood',
                              'email'        => 'robin@example.com',
                              'contact_type' => 'Individual'),

                        array('first_name'   => 'robin',     
                              'last_name'    => 'hood',
                              'email'        => 'hood@example.com',
                              'contact_type' => 'Individual'),

                        array('first_name'   => 'robin',     
                              'last_name'    => 'dale',
                              'email'        => 'robin@example.com',
                              'contact_type' => 'Individual'),

                        array('first_name'   => 'little',     
                              'last_name'    => 'dale',
                              'email'        => 'dale@example.com',
                              'contact_type' => 'Individual'),

                        array('first_name'   => 'will',     
                              'last_name'    => 'dale',
                              'email'        => 'dale@example.com',
                              'contact_type' => 'Individual'),

                        array('first_name'   => 'will',     
                              'last_name'    => 'dale',
                              'email'        => 'will@example.com',
                              'contact_type' => 'Individual'),

                        array('first_name'   => 'will',     
                              'last_name'    => 'dale',
                              'email'        => 'will@example.com',
                              'contact_type' => 'Individual'),
                         );

        $count = 1;
        // TODO: This is not an API test!!
        foreach ( $params as $param ) {
            $param['version'] = 3;
            $contact =& civicrm_api('contact', 'create', $param );
            $contactIds[$count++] = $contact['id'];
        }

        // verify that all contacts have been created separately
        $this->assertEquals( count($contactIds), 7, 'Check for number of contacts.' );

        require_once 'CRM/Dedupe/DAO/RuleGroup.php';
        require_once 'CRM/Dedupe/Finder.php';
        $dao = new CRM_Dedupe_DAO_RuleGroup();
        $dao->contact_type = 'Individual';
        $dao->level        = 'Fuzzy';
        $dao->is_default   = 1;
        $dao->find( true );

        // ******** threshold = 20 ************ //
        $dao->threshold = 20;
        $dao->save();
        $fields = array( 'first_name' => 'robin',
                         'last_name'  => 'hood', 
                         'email'      => 'hood@example.com' );
        $dedupeParams = CRM_Dedupe_Finder::formatParams($fields, 'Individual');
        $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual', 'Fuzzy' );
        // threshold 20 can only find full match
        $this->assertEquals( count($ids), 1, 'Check for dupe counts for threshold=20.' );

        // ******** threshold = 17 ************ //
        $dao->threshold = 17;
        $dao->save();
        $fields = array( 'last_name'  => 'dale', 
                         'email'      => 'dale@example.com' );
        $dedupeParams = CRM_Dedupe_Finder::formatParams($fields, 'Individual');
        $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual', 'Fuzzy' );
        $this->assertEquals( count($ids), 2, 'Check for dupe counts for threshold=17.' );

        // ******** threshold = 15 ************ //
        $dao->threshold = 15;
        $dao->save();
        $fields = array( 'first_name' => 'will', 
                         'email'      => 'will@example.com' );
        $dedupeParams = CRM_Dedupe_Finder::formatParams($fields, 'Individual');
        $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual', 'Fuzzy' );
        $this->assertEquals( count($ids), 2, 'Check for dupe counts for threshold=15.' );

        // ******** threshold = 12 ************ //
        $dao->threshold = 12;
        $dao->save();
        $fields = array( 'email'  => 'dale@example.com' );
        $dedupeParams = CRM_Dedupe_Finder::formatParams($fields, 'Individual');
        $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual', 'Fuzzy' );
        $this->assertEquals( count($ids), 0, 'Check for dupe counts for threshold=12.' );

        // ******** threshold = 10 ************ //
        $dao->threshold = 10;
        $dao->save();
        $fields = array( 'email'  => 'dale@example.com' );
        $dedupeParams = CRM_Dedupe_Finder::formatParams($fields, 'Individual');
        $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual', 'Fuzzy' );
        $this->assertEquals( count($ids), 2, 'Check for dupe counts for threshold=10.' );

        // ******** threshold = 7 ************ //
        $dao->threshold = 7;
        $dao->save();
        $fields = array( 'last_name'  => 'dale' );
        $dedupeParams = CRM_Dedupe_Finder::formatParams($fields, 'Individual');
        $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual', 'Fuzzy' );
        $this->assertEquals( count($ids), 5, 'Check for dupe counts for threshold=7.' );

        // delete all created contacts
        foreach ( $contactIds as $contactId ) {
            Contact::delete( $contactId );
        }
    }
}

?>
