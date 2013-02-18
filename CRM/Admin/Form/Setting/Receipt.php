<?php
/**
 * This class generates form components for CiviContribute
 */
class CRM_Admin_Form_Setting_Receipt extends CRM_Admin_Form_Setting
{
    /**
     * Function to build the form
     *
     * @return None
     * @access public
     */
    public function buildQuickForm( ) 
    {
        CRM_Utils_System::setTitle(ts('Settings - Contribution Receipt'));
        $this->addElement('text','receiptPrefix', ts('Prefix of Receipt ID'));
        $this->addElement('textarea','receiptDescription', ts('Description of Receipt Footer'));
        $this->addElement('textarea','receiptOrgInfo', ts('Organization info'));
        $check = true;
        
        // redirect to Administer Section After hitting either Save or Cancel button.
        $session = CRM_Core_Session::singleton( );
        $session->pushUserContext( CRM_Utils_System::url( 'civicrm/admin', 'reset=1' ) );
        
        parent::buildQuickForm( $check );
    }
}

