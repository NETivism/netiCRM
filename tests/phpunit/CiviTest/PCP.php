<?php
class PCPBlock extends PHPUnit_Framework_Testcase
{
    /*
     * Helper function to create a PCP Block for Contribution Page
     *
     * @param  int $contributionPageId - id of the Contribution Page
     * to be deleted
     * @return array of created pcp block
     *
     */
    function create( $contributionPageId ) {
        $profileParams = [
                               'group_type' => 'Individual,Contact',
                               'title'      => 'Test Supprorter Profile',
                               'help_pre'   => 'Profle to PCP Contribution',
                               'is_active'  => 1,
                               'is_cms_user' => 2
                               ];
        
        $ufGroup   = civicrm_api('uf_group', 'create', $profileParams );
        $profileId = $ufGroup['id'];

        $fieldsParams =  [
                                [
                                      'field_name'       => 'first_name',
                                      'field_type'       => 'Individual',
                                      'visibility'       => 'Public Pages and Listings',
                                      'weight'           => 1,
                                      'label'            => 'First Name',
                                      'is_required'      => 1,
                                      'is_active'        => 1 ],
                                [
                                      'field_name'       => 'last_name',
                                      'field_type'       => 'Individual',
                                      'visibility'       => 'Public Pages and Listings',
                                      'weight'           => 2,
                                      'label'            => 'Last Name',
                                      'is_required'      => 1,
                                      'is_active'        => 1 ],
                                [
                                      'field_name'       => 'email',
                                      'field_type'       => 'Contact',
                                      'visibility'       => 'Public Pages and Listings',
                                      'weight'           => 3,
                                      'label'            => 'Email',
                                      'is_required'      => 1,
                                      'is_active'        => 1 ]
                               ];
        
        civicrm_api_include('uf_field');
        foreach( $fieldsParams as $value ){
            $api_version = civicrm_get_api_version();
            if ($api_version === 2) {
                $ufField = civicrm_uf_field_create($profileId , $value );
            }
            else {
                // we assume api v3.
                // TODO: Update this when api/v3/UFField.php is finished.
                $ufField = civicrm_uf_field_create($profileId , $value );
            }
        }
        $joinParams =  [
                             'module'       => 'Profile',
                             'entity_table' => 'civicrm_contribution_page',
                             'entity_id'    => 1,
                             'weight'       => 1,
                             'uf_group_id'  => $profileId ,
                             'is_active'    => 1
                             ];
        $ufJoin = civicrm_api('uf_join', 'create', $joinParams );
        
        $params = [
                        'entity_table'          => 'civicrm_contribution_page',
                        'entity_id'             => $contributionPageId,
                        'supporter_profile_id'  => $profileId,
                        'is_approval_needed'    => 0,
                        'is_tellfriend_enabled' => 0,
                        'tellfriend_limit'      => 0,
                        'link_text'             => 'Create your own Personal Campaign Page!',
                        'is_active'             => 1,
                        'notify_email'          => 'info@civicrm.org'
                        ];
        require_once 'CRM/Contribute/BAO/PCP.php';
        $blockPCP = CRM_Contribute_BAO_PCP::add( $params);
        return [ 'blockId' => $blockPCP->id, 'profileId' => $profileId ];
    }
    /*
     * Helper function to delete a PCP related stuff viz. Profile, PCP Block Entry
     *
     * @param  array key value pair
     * pcpBlockId - id of the PCP Block Id, profileID - id of Supporter Profile
     * to be deleted
     * @return boolean true if success, false otherwise
     *
     */
    function delete( $params )
    {
        $api_version = civicrm_get_api_version();
        if ($api_version === 2) {
            civicrm_api_include('uf_group');
            $resulProfile = civicrm_uf_group_delete($params['profileId']);
        }
        else {
            $delete_params = ['id' => $params['profileId']];
            $resulProfile = civicrm_api('uf_group', 'delete', $delete_params );
        }

        require_once 'CRM/Contribute/DAO/PCPBlock.php';
        $dao     = new CRM_Contribute_DAO_PCPBlock( );
        $dao->id = $params['blockId'];
        if ( $dao->find( true ) ) {
            $resultBlock = $dao->delete( );
        }
        if ( $id = CRM_Utils_Array::value( 'pcpId', $params ) ){
            require_once 'CRM/Contribute/BAO/PCP.php';
            CRM_Contribute_BAO_PCP::deleteById( $id );
        }
        if ( $resulProfile && $resultBlock ) {
            return true;
        }
        return false;
    }
}
?>
