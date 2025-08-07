<?php

require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/Contact.php';

require_once 'CRM/Contact/BAO/Query.php';

/**                                                                                                                                                                         
 *  Include dataProvider for tests                                                                                                                                          
 */
require_once 'tests/phpunit/CRM/Contact/BAO/QueryTestDataProvider.php';

class CRM_Contact_BAO_QueryTest extends CiviUnitTestCase 
{

    function get_info( ) 
    {
        return [
                     'name'        => 'Contact BAO Query',
                     'description' => 'Test all Contact_BAO_Query methods.',
                     'group'       => 'CiviCRM BAO Query Tests',
                     ];
    }
    
    public function dataProvider()
    {
        return new CRM_Contact_BAO_QueryTestDataProvider;
    }

    function setUp( ) 
    {
        parent::setUp();
    }
    
    function tearDown()
    {
        $tablesToTruncate = [ 'civicrm_group_contact',
                                   'civicrm_group',
                                   'civicrm_saved_search',
                                   'civicrm_entity_tag',
                                   'civicrm_tag',
                                   'civicrm_contact'
                                   ];
        $this->quickCleanup( $tablesToTruncate );
    }
    
    /**
     *  Test CRM_Contact_BAO_Query::searchQuery()
     *  @dataProvider dataProvider
     */
    function testSearch( $fv, $count, $ids, $full )
    {
        $this->callAPISuccess('SavedSearch', 'create', ['form_values' => 'a:9:{s:5:"qfKey";s:32:"0123456789abcdef0123456789abcdef";s:13:"includeGroups";a:1:{i:0;s:1:"3";}s:13:"excludeGroups";a:0:{}s:11:"includeTags";a:0:{}s:11:"excludeTags";a:0:{}s:4:"task";s:2:"14";s:8:"radio_ts";s:6:"ts_all";s:14:"customSearchID";s:1:"4";s:17:"customSearchClass";s:36:"CRM_Contact_Form_Search_Custom_Group";}']);
        $this->callAPISuccess('SavedSearch', 'create', ['form_values' => 'a:9:{s:5:"qfKey";s:32:"0123456789abcdef0123456789abcdef";s:13:"includeGroups";a:1:{i:0;s:1:"3";}s:13:"excludeGroups";a:0:{}s:11:"includeTags";a:0:{}s:11:"excludeTags";a:0:{}s:4:"task";s:2:"14";s:8:"radio_ts";s:6:"ts_all";s:14:"customSearchID";s:1:"4";s:17:"customSearchClass";s:36:"CRM_Contact_Form_Search_Custom_Group";}']);
        $tag7 = $this->ids['Tag'][7] = $this->tagCreate(['name' => 'Test Tag 7', 'description' => 'Test Tag 7'])['id'];
        $tag9 = $this->ids['Tag'][9] = $this->tagCreate(['name' => 'Test Tag 9', 'description' => 'Test Tag 9'])['id'];
        $this->tagCreate(['name' => 'Test Tag 10']);
        $groups = [
          3 => ['name' => 'Test Group 3'],
          4 => ['name' => 'Test Smart Group 4', 'saved_search_id' => 1],
          5 => ['name' => 'Test Group 5'],
          6 => ['name' => 'Test Smart Group 6', 'saved_search_id' => 2],
        ];
        foreach ($groups as $id => $group) {
          $this->ids['Group'][$id] = $this->groupCreate(array_merge($group, ['title' => $group['name']]));
        }
        $individuals = [
          ['first_name' => 'Test', 'last_name' => 'Test Contact 9', 'gender_id' => 1, 'prefix_id' => 1, 'suffix_id' => 1],
          ['first_name' => 'Test', 'last_name' => 'Test Contact 10', 'gender_id' => 2, 'prefix_id' => 2, 'suffix_id' => 2, 'api.entity_tag.create' => ['tag_id' => $tag9]],
          ['first_name' => 'Test', 'last_name' => 'Test Contact 11', 'gender_id' => 3, 'prefix_id' => 3, 'suffix_id' => 3, 'api.entity_tag.create' => ['tag_id' => $tag7]],
          ['first_name' => 'Test', 'last_name' => 'Test Contact 12', 'gender_id' => 3, 'prefix_id' => 4, 'suffix_id' => 4, 'api.entity_tag.create' => ['tag_id' => $tag9], 'api.entity_tag.create.2' => ['tag_id' => $tag7]],
          ['first_name' => 'Test', 'last_name' => 'Test Contact 13', 'gender_id' => 2, 'prefix_id' => 2, 'suffix_id' => 2],
          ['first_name' => 'Test', 'last_name' => 'Test Contact 14', 'gender_id' => 3, 'prefix_id' => 4, 'suffix_id' => 4, 'api.entity_tag.create' => ['tag_id' => $tag9]],
          ['first_name' => 'Test', 'last_name' => 'Test Contact 15', 'gender_id' => 3, 'prefix_id' => 4, 'suffix_id' => 5, 'api.entity_tag.create' => ['tag_id' => $tag7]],
          ['first_name' => 'Test', 'last_name' => 'Test Contact 16', 'gender_id' => 3, 'prefix_id' => 4, 'suffix_id' => 6, 'api.entity_tag.create' => ['tag_id' => $tag9], 'api.entity_tag.create.2' => ['tag_id' => $tag7]],
          ['first_name' => 'Test', 'last_name' => 'Test Contact 17', 'gender_id' => 2, 'prefix_id' => 4, 'suffix_id' => 7],
          ['first_name' => 'Test', 'last_name' => 'Test Contact 18', 'gender_id' => 2, 'prefix_id' => 4, 'suffix_id' => 4, 'api.entity_tag.create' => ['tag_id' => $tag9]],
          ['first_name' => 'Test', 'last_name' => 'Test Contact 19', 'gender_id' => 2, 'prefix_id' => 4, 'suffix_id' => 6, 'api.entity_tag.create.2' => ['tag_id' => $tag7]],
          ['first_name' => 'Test', 'last_name' => 'Test Contact 20', 'gender_id' => 1, 'prefix_id' => 4, 'suffix_id' => 6, 'api.entity_tag.create' => ['tag_id' => $tag9], 'api.entity_tag.create.2' => ['tag_id' => $tag7]],
          ['first_name' => 'Test', 'last_name' => 'Test Contact 21', 'gender_id' => 3, 'prefix_id' => 1, 'suffix_id' => 6],
          ['first_name' => 'Test', 'last_name' => 'Test Contact 22', 'gender_id' => 1, 'prefix_id' => 1, 'suffix_id' => 1, 'api.entity_tag.create' => ['tag_id' => $tag9]],
          ['first_name' => 'Test', 'last_name' => 'Test Contact 23', 'gender_id' => 3, 'prefix_id' => 1, 'suffix_id' => 1, 'api.entity_tag.create' => ['tag_id' => $tag7]],
          ['first_name' => 'Test', 'last_name' => 'Test Contact 24', 'gender_id' => 3, 'prefix_id' => 3, 'suffix_id' => 2, 'api.entity_tag.create' => ['tag_id' => $tag9], 'api.entity_tag.create.2' => ['tag_id' => $tag7]],
        ];
        foreach ($individuals as $individual) {
          $this->ids['Contact'][$individual['last_name']] = $this->individualCreate($individual);
        }
        $groupContacts = [
          [5 => 13],
          [5 => 14],
          [5 => 15],
          [5 => 16],
          [5 => 21],
          [5 => 22],
          [5 => 23],
          [5 => 24],
          [3 => 17],
          [3 => 18],
          [3 => 19],
          [3 => 20],
          [3 => 21],
          [3 => 22],
          [3 => 23],
          [3 => 24],
        ];
        foreach ($groupContacts as $group) {
          $groupID = $this->ids['Group'][key($group)];
          $contactID = $this->ids['Contact']['Test Contact ' . reset($group)];
          $this->callAPISuccess('GroupContact', 'create', ['group_id' => $groupID, 'contact_id' => $contactID, 'status' => 'Added']);
        }
        // We have migrated from a hard-coded dataset to a dynamic one but are still working with the same
        // dataprovider at this stage -> wrangle.
        foreach ($fv as $key => $value) {
          $entity = ucfirst($key);
          if (!array_key_exists($entity, $this->ids)) {
            continue;
          }
          if (is_numeric($value)) {
            $fv[$key] = $this->ids[$entity][$value];
          }
          elseif (!empty($value[0])) {
            foreach ($value as $index => $oldGroup) {
              $fv[$key][$index] = $this->ids[$entity][$oldGroup];
            }
          }
          else {
            foreach (array_keys($value) as $index) {
              unset($fv[$key][$index]);
              $fv[$key][$this->ids[$entity][$index]] = 1;
            }
          }
        }

        $params = CRM_Contact_BAO_Query::convertFormValues( $fv );
        $obj = new CRM_Contact_BAO_Query( $params );
        $dao = $obj->searchQuery( );

        $contacts = [ ];
        while ( $dao->fetch( ) ) {
            $contacts[] = $dao->contact_id;
        }
        
        sort( $contacts, SORT_NUMERIC );

        $expectedIDs = [];
        foreach ($ids as $id) {
          $expectedIDs[] = $this->ids['Contact']['Test Contact ' . $id];
        }
        $this->assertEquals($expectedIDs, $contacts);
    }

}
