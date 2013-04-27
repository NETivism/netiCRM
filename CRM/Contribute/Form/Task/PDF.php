<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

require_once 'CRM/Contribute/Form/Task.php';

/**
 * This class provides the functionality to email a group of
 * contacts. 
 */
class CRM_Contribute_Form_Task_PDF extends CRM_Contribute_Form_Task {

    /**
     * Are we operating in "single mode", i.e. updating the task of only
     * one specific contribution?
     *
     * @var boolean
     */
    public $_single = false;

    protected $_tmpreceipt;

    protected $_rows;

    /**
     * build all the data structures needed to build the form
     *
     * @return void
     * @access public
     */
    
    function preProcess( ) {
      $id = CRM_Utils_Request::retrieve( 'id', 'Positive', $this, false );

      if ( $id ) {
          $this->_contributionIds    = array( $id );
          $this->_componentClause = " civicrm_contribution.id IN ( $id ) ";
          $this->_single = true;
          $this->assign( 'totalSelectedContributions', 1 );
      } else {
          parent::preProcess( );
      }

      // check that all the contribution ids have pending status
      $query = " SELECT count(*) FROM civicrm_contribution WHERE  contribution_status_id != 1 AND {$this->_componentClause}";
      $count = CRM_Core_DAO::singleValueQuery( $query, CRM_Core_DAO::$_nullArray );
      if ( $count != 0 ) {
        CRM_Core_Error::statusBounce( ts("Please select only contributions with Completed status.") ); 
      }

      // we have all the contribution ids, so now we get the contact ids
      parent::setContactIDs( );
      $this->assign( 'single', $this->_single );
      
      $qfKey = CRM_Utils_Request::retrieve( 'qfKey', 'String', $this );
      require_once 'CRM/Utils/Rule.php';
      $urlParams = 'force=1';
      if ( CRM_Utils_Rule::qfKey( $qfKey ) ) {
        $urlParams .= "&qfKey=$qfKey";
      }
      
      $url = CRM_Utils_System::url( 'civicrm/contribute/search', $urlParams );
      $breadCrumb = array ( array( 'url'   => $url, 'title' => ts('Search Results') ) );
      
      CRM_Utils_System::appendBreadCrumb( $breadCrumb );
      CRM_Utils_System::setTitle( ts('Print Contribution Receipts') );
    }
    
    /**
     * Build the form
     *
     * @access public
     * @return void
     */
    public function buildQuickForm()
    {
        
        $this->addElement( 'checkbox', 'single_page_letter', ts('Single page with address letter') );
        /*
        $this->addElement( 'radio', 'output', null, ts('Copy Receipts'), 'copy_receipt' ); 
        $this->addElement( 'radio', 'output', null, ts('Original Receipts'), 'original_receipt' );
        $this->addRule( 'output', ts('%1 is a required field.', array(1 => ts('Receipt Type'))), 'required' );
        */
        $this->addButtons( array(
                                 array ( 'type'      => 'next',
                                         'name'      => ts('Download Receipt(s)'),
                                         'isDefault' => true   ),
                                 array ( 'type'      => 'back',
                                         'name'      => ts('Cancel') ),
                                 )
                           );
    }

    /**
     * process the form after the input has been submitted and validated
     *
     * @access public
     * @return None
     */
    public function postProcess() {
      // get all the details needed to generate a receipt
      $contribIDs = implode( ',', $this->_contributionIds );
      $details =& CRM_Contribute_Form_Task_Status::getDetails( $contribIDs );

      $params = $this->controller->exportValues( $this->_name );

      self::makeReceipt($details, $params['single_page_letter']);
      self::makePDF();
      CRM_Utils_System::civiExit( );
    }

    public function pushFile($html) {
      // tmp directory
      file_put_contents($this->_tmpreceipt, $html, FILE_APPEND);
    }
    public function popFile() {
      $return = file_get_contents($this->_tmpreceipt);
      unlink($this->_tmpreceipt);
      return $return;
    }
    
    public function makePDF($output = FALSE){
      $template =& CRM_Core_Smarty::singleton( );
      $pages = self::popFile();
      $template->assign('pages', $pages);
      $pages = $template->fetch('CRM/common/Receipt.tpl');
      $pdf = CRM_Utils_PDF_Utils::domlib( $pages, 'Receipt.pdf', $output, 'portrait', 'a4' );
      if($output){
        print $pdf;
      }
    }

    public function makeReceipt($details, $single_page_letter = NULL){
      $this->_tmpreceipt = tempnam('/tmp', 'receipt');
      if(is_numeric($details)){
        $details =& CRM_Contribute_Form_Task_Status::getDetails( $details );
      }
      if(empty($single_page_letter)){
        $print_type = array(
          'original' => ts('Original Receipts'),
          'copy' => ts('Copy Receipts'),
        );
        $single_page_letter = '';
      }
      else{
        $print_type = array(
          'copy' => ts('Copy Receipts'),
        );
      }
      // domain info
      $domain = CRM_Core_BAO_Domain::getDomain();
      $location = $domain->getLocationValues();

      $baseIPN = new CRM_Core_Payment_BaseIPN();
      $config =& CRM_Core_Config::singleton( );
      $count = 0;

      foreach ( $details as $contribID => $detail ) {
        $input = $ids = $objects = array( );
        $template =& CRM_Core_Smarty::singleton( );
        $template->assign('print_type', $print_type);
        $template->assign('single_page_letter', $single_page_letter);
        $template->assign('domain_name', $domain->name);
        $template->assign('domain_email', $location['email'][1]['email']);
        $template->assign('domain_phone', $location['phone'][1]['phone']);
        $template->assign('domain_address', $location['address'][1]['display_text']);
        $template->assign('receiptOrgInfo', htmlspecialchars_decode($config->receiptOrgInfo));
        $template->assign('receiptDescription', htmlspecialchars_decode($config->receiptDescription));
              
        $input['component'] = $detail['component'];

        $ids['contact'     ]      = $detail['contact'];
        $ids['contribution']      = $contribID;
        $ids['contributionRecur'] = null;
        $ids['contributionPage']  = null;
        $ids['membership']        = $detail['membership'];
        $ids['participant']       = $detail['participant'];
        $ids['event']             = $detail['event'];


        if ( ! $baseIPN->validateData( $input, $ids, $objects, false ) ) {
          CRM_Core_Error::fatal( );
        }
        $contribution =& $objects['contribution'];

        // set some fake input values so we can reuse IPN code
        $input['amount']     = $contribution->total_amount;
        $input['is_test']    = $contribution->is_test;
        $input['fee_amount'] = $contribution->fee_amount;
        $input['net_amount'] = $contribution->net_amount;
        $input['trxn_id']    = $contribution->trxn_id;
        $input['trxn_date']  = isset( $contribution->trxn_date ) ? $contribution->trxn_date : null;

        $values = array( );
        if($count){
          $html = '<div style="page-break-after: always;"></div>';
        }
        $html .= CRM_Contribute_BAO_Contribution::getReceipt( $input, $ids, $objects, $values);
              
        // do not use array to prevent memory exhusting
        self::pushFile($html);
        // dump to file then retrive lately

        // reset template values before processing next transactions
        $template->clearTemplateVars( );
        $count++;
        unset($html);
      }
    }
}
