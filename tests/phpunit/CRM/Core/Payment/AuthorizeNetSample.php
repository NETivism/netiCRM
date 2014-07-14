<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/AuthorizeNet.php';
require_once 'CiviTest/Contact.php';
require_once 'CRM/Contribute/BAO/Contribution.php';
require_once 'CRM/Contribute/BAO/ContributionRecur.php';

class CRM_Core_Payment_AuthorizeNetTest extends CiviUnitTestCase 
{
    function get_info( ) 
    {
        return array(
                     'name'        => 'Authorize.net processing',
                     'description' => 'Test Authorize.ne methods.',
                     'group'       => 'Payment Processor Tests',
                     );
    }
   
    function setUp( ) 
    {
        parent::setUp();
        require_once 'CRM/Core/Payment/AuthorizeNet.php';
        require_once 'CRM/Core/BAO/PaymentProcessorType.php';
        $this->paymentProcessor  = new AuthorizeNet( );
        $this->processorParams   = $this->paymentProcessor->create( );

        $paymentProcessor = array( 'user_name' => $this->processorParams->user_name,
                                   'password'  => $this->processorParams->password,
                                   'url_recur' => $this->processorParams->url_recur );  
        
        $this->processor = new CRM_Core_Payment_AuthorizeNet( 'Contribute', $paymentProcessor );
        $this->_contributionTypeId = $this->contributionTypeCreate( );
    }

    function tearDown( )
    {
        $this->processorParams->delete( );
        $tablesToTruncate = array( 'civicrm_contribution_type', 'civicrm_contribution', 'civicrm_contribution_recur' );
        $this->quickCleanup( $tablesToTruncate );
    }
    
    /**
     * create a single post dated payment as a recurring transaction.
     * 
     * Test works but not both due to some form of caching going on in the SmartySingleton 
     */
    function testCreateSingleNowDated( )
    {
        $contactId = Contact::createIndividual( );
        $ids       = array( 'contribution' => null );
        
        $contributionRecurParams = array( 'contact_id'             => $contactId,
                                          'amount'                 => 150.00,
                                          'currency'               => 'USD',
                                          'frequency_unit'         => 'week',
                                          'frequency_interval'     => 1,
                                          'installments'           => 2,
                                          'start_date'             => date( 'Ymd' ),
                                          'create_date'            => date( 'Ymd' ),
                                          'invoice_id'             => 'c8acb91e080ad7bd8a2adc119c192885',
                                          'contribution_status_id' => 2,
                                          'is_test'                => 1,
                                          'payment_processor_id'   => $this->processorParams->id );
        
        $recur = CRM_Contribute_BAO_ContributionRecur::add( $contributionRecurParams, $ids );
                                          
        $contributionParams = array( 'contact_id'             => $contactId,
                                     'contribution_type_id'   => $this->_contributionTypeId,
                                     'recieve_date'           => date( 'Ymd' ),
                                     'total_amount'           => 150.00,
                                     'invoice_id'             => 'c8acb91e080ad7bd8a2adc119c192885',
                                     'currency'               => 'USD',
                                     'contribution_recur_id'  => $recur->id,
                                     'is_test'                => 1,
                                     'contribution_status_id' => 2,
                                     );
        $contribution = CRM_Contribute_BAO_Contribution::add( $contributionParams, $ids );
        
        $params = array(     
                        'qfKey' => '08ed21c7ca00a1f7d32fff2488596ef7_4454',
                        'hidden_CreditCard' => 1,
                        'billing_first_name' => 'Frodo',
                        'billing_middle_name' => "",
                        'billing_last_name' => 'Baggins',
                        'billing_street_address-5' => '8 Hobbitton Road',
                        'billing_city-5'  => 'The Shire',
                        'billing_state_province_id-5' => 1012,
                        'billing_postal_code-5' => 5010,
                        'billing_country_id-5' => 1228,
                        'credit_card_number' => '4007000000027',
                        'cvv2' => 123,
                        'credit_card_exp_date' => Array(
                                                        'M' => 10,
                                                        'Y' => 2019
                                                        ),
                        
                        'credit_card_type' => 'Visa',
                        'is_recur' => 1,
                        'frequency_interval' => 1,
                        'frequency_unit' => 'week',
                        'installments' => 2,
                        'contribution_type_id' => $this->_contributionTypeId,
                        'is_email_receipt' => 1,
                        'from_email_address' => 'gandalf',
                        'receive_date' => date( 'Ymd' ),
                        'receipt_date_time' => '',
                        'payment_processor_id' => $this->processorParams->id,
                        'price_set_id' => '',
                        'total_amount' => 150.00,
                        'currency' => 'USD',
                        'source' => "Mordor",
                        'soft_credit_to' => '', 
                        'soft_contact_id' =>  '',
                        'billing_state_province-5' => 'IL',
                        'state_province-5' => 'IL',
                        'billing_country-5' => 'US',
                        'country-5' => 'US',
                        'year' => 2019,
                        'month' => 10,
                        'ip_address' => '127.0.0.1',
                        'amount' => 7,
                        'amount_level' => 0,
                        'currencyID' => 'USD',
                        'pcp_display_in_roll' => "",
                        'pcp_roll_nickname' => "",
                        'pcp_personal_note' => "",
                        'non_deductible_amount' => "",
                        'fee_amount' => "",
                        'net_amount' => "",
                        'invoiceID'  => "c8acb91e080ad7bd8a2adc119c192885",
                        'contribution_page_id'  => "",
                        'thankyou_date' => null,
                        'honor_contact_id' => null,
                        'invoiceID' => '',
                        'first_name' => 'Frodo',
                        'middle_name' => 'bob',
                        'last_name' => 'Baggins',
                        'street_address' => '8 Hobbiton Road',
                        'city' => 'The Shire',
                        'state_province' => 'IL',
                        'postal_code' => 5010,
                        'country' => 'US',
                        'contributionType_name' => 'My precious',
                        'contributionType_accounting_code' => '',
                        'contributionPageID' => '',
                        'email' => 'john@doe.com',
                        'contactID' => $contactId,
                        'contributionID' => $contribution->id,
                        'contributionTypeID' => $this->_contributionTypeId,
                        'contributionRecurID' => $recur->id,
                             );

        $result = $this->processor->doDirectPayment($params);
        
        $this->assertNotType('CRM_Core_Error', $result, "In line " . __LINE__ . " " .$result->_errors[0]['message']);
        //cancel it or the transaction will be rejected by A.net if the test is re-run
        $this->processor->cancelSubscription( ) ;
        Contact::delete( $contactId );
    }  
        
    /**
     * create a single post dated payment as a recurring transaction
     */
    function testCreateSinglePostDated( )
    {
        $start_date = date('Ymd',strtotime("+ 1 week") );
        
        $contactId = Contact::createIndividual( );
        $ids       = array( 'contribution' => null );
        
        $contributionRecurParams = array( 'contact_id'             => $contactId,
                                          'amount'                 => 100.00,
                                          'currency'               => 'USD',
                                          'frequency_unit'         => 'month',
                                          'frequency_interval'     => 1,
                                          'installments'           => 3,
                                          'start_date'             => $start_date,
                                          'create_date'            => date( 'Ymd' ),
                                          'invoice_id'             => 'f72ee3de0a877bfdc03ca1daf4a1d757',
                                          'contribution_status_id' => 2,
                                          'is_test'                => 1,
                                          'payment_processor_id'   => $this->processorParams->id );
        
        $recur = CRM_Contribute_BAO_ContributionRecur::add( $contributionRecurParams, $ids );
                                          
        $contributionParams = array( 'contact_id'             => $contactId,
                                     'contribution_type_id'   => $this->_contributionTypeId,
                                     'recieve_date'           => $start_date,
                                     'total_amount'           => 100.00,
                                     'invoice_id'             => 'f72ee3de0a877bfdc03ca1daf4a1d757',
                                     'currency'               => 'USD',
                                     'contribution_recur_id'  => $recur->id,
                                     'is_test'                => 1,
                                     'contribution_status_id' => 2,
                                     );
        $contribution = CRM_Contribute_BAO_Contribution::add( $contributionParams, $ids );

        $params = array(     
                        'qfKey' => '00ed21c7ca00a1f7d555555596ef7_4454',
                        'hidden_CreditCard' => 1,
                        'billing_first_name' => 'Frodowina',
                        'billing_middle_name' => "",
                        'billing_last_name' => 'Baggins',
                        'billing_street_address-5' => '8 Hobbitton Road',
                        'billing_city-5'  => 'The Shire',
                        'billing_state_province_id-5' => 1012,
                        'billing_postal_code-5' => 5010,
                        'billing_country_id-5' => 1228,
                        'credit_card_number' => '4007000000027',
                        'cvv2' => 123,
                        'credit_card_exp_date' => array(
                                                        'M' => 11,
                                                        'Y' => 2019
                                                        ),
                        
                        'credit_card_type' => 'Visa',
                        'is_recur' => 1,
                        'frequency_interval' => 1,
                        'frequency_unit' => 'month',
                        'installments' => 3,
                        'contribution_type_id' => $this->_contributionTypeId,
                        'is_email_receipt' => 1,
                        'from_email_address' => 'gandalf',
                        'receive_date' => $start_date,
                        'receipt_date_time' => '',
                        'payment_processor_id' => $this->processorParams->id,
                        'price_set_id' => '',
                        'total_amount' => 100.00,
                        'currency' => 'USD',
                        'source' => "Mordor",
                        'soft_credit_to' => '', 
                        'soft_contact_id' =>  '',
                        'billing_state_province-5' => 'IL',
                        'state_province-5' => 'IL',
                        'billing_country-5' => 'US',
                        'country-5' => 'US',
                        'year' => 2019,
                        'month' => 10,
                        'ip_address' => '127.0.0.1',
                        'amount' => 70,
                        'amount_level' => 0,
                        'currencyID' => 'USD',
                        'pcp_display_in_roll' => "",
                        'pcp_roll_nickname' => "",
                        'pcp_personal_note' => "",
                        'non_deductible_amount' => "",
                        'fee_amount' => "",
                        'net_amount' => "",
                        'invoice_id'  => "",
                        'contribution_page_id'  => "",
                        'thankyou_date' => null,
                        'honor_contact_id' => null,
                        'invoiceID' => 'f72ee3de0a877bfdc03ca1daf4a1d757',
                        'first_name' => 'Frodowina',
                        'middle_name' => 'bob',
                        'last_name' => 'Baggins',
                        'street_address' => '8 Hobbiton Road',
                        'city' => 'The Shire',
                        'state_province' => 'IL',
                        'postal_code' => 5010,
                        'country' => 'US',
                        'contributionType_name' => 'My precious',
                        'contributionType_accounting_code' => '',
                        'contributionPageID' => '',
                        'email' => 'backhome@frommordor.com',
                        'contactID' => $contactId,
                        'contributionID' => $contribution->id,
                        'contributionTypeID' => $this->_contributionTypeId,
                        'contributionRecurID' => $recur->id,
                             );

        $result = $this->processor->doDirectPayment( $params );
        
        $this->assertNotType('CRM_Core_Error', $result,"In line " . __LINE__ . " " .$result->_errors[0]['message']);
        //cancel it or the transaction will be rejected by A.net if the test is re-run
        $this->processor->cancelSubscription( ) ;
        Contact::delete( $contactId );
    }  
}
 ?>
