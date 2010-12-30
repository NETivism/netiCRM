<?php 
date_default_timezone_set('Asia/Taipei');
require_once 'CRM/Core/Payment.php';

class CRM_Core_Payment_GW extends CRM_Core_Payment { 
    /**
     * mode of operation: live or test
     *
     * @var object
     * @static
     */
    static protected $_mode = null;

    static protected $_params = array();

    /**
     * Constructor
     *
     * @param string $mode the mode of operation: live or test
     *
     * @return void
     */
    function __construct( $mode, &$paymentProcessor ) {
      $this->_mode             = $mode;
      $this->_paymentProcessor = $paymentProcessor;
      $this->_processorName    = ts('Green World');
      $config =& CRM_Core_Config::singleton( );
      $this->_config = $config;
    }

    function setExpressCheckOut( &$params ) {
      CRM_Core_Error::fatal( ts( 'This function is not implemented' ) ); 
    }
    function getExpressCheckoutDetails( $token ) {
      CRM_Core_Error::fatal( ts( 'This function is not implemented' ) ); 
    }
    function doExpressCheckout( &$params ) {
      CRM_Core_Error::fatal( ts( 'This function is not implemented' ) ); 
    }
    function doDirectPayment( &$params ) {
      CRM_Core_Error::fatal( ts( 'This function is not implemented' ) );
    }
    function checkConfig() {
      return null;
    }

    /**  
     * Sets appropriate parameters for checking out to google
     *  
     * @param array $params  name value pair of contribution datat
     *  
     * @return void  
     * @access public 
     *  
     */  
    function doTransferCheckout( &$params, $component ) {
      $output = '';
      $component = strtolower( $component );
      if ( $component != 'contribute' && $component != 'event' ) {
        CRM_Core_Error::fatal( ts('Component is invalid') );
      }

      // to see what instrument for newweb
      //$gw_instrument_id = $params['gw_instrument_id'];
      //$gw_instrument = $this->getInstrument($gw_instrument_id);
      $gw_instrument = 'Credit Card';

      $is_pay_later = TRUE;
      switch($gw_instrument){
        case 'Credit Card':
          $output = $this->gwCreditCard($params, $component, $gw_instrument);
          $is_pay_later = FALSE;
          break;
          /*
        case 'WEBATM':
        case 'ATM':
        case 'CS':
        case 'MMK':
        case 'ALIPAY':
          break;
         */
      }

      // now process contribution to save some default value
      /* don't know if we need this. FIXME later
      require_once 'CRM/Contribute/DAO/Contribution.php';
      $contribution =& new CRM_Contribute_DAO_Contribution();
      $contribution->id = $params['contributionID'];
      $contribution->find(true);
      if($contribution->payment_instrument_id != $params['gw_instrument_id']){
        $contribution->payment_instrument_id = $params['gw_instrument_id'];
      }
      if($contribution->is_pay_later != $is_pay_later){
        $contribution->is_pay_later = $is_pay_later;
      }
      $contribution->trxn_id = $params['contributionID'];
      $contribution->save();
      */

      // record for thank you display
      /* don't know if we need this. FIXME later.
      $_SESSION['newweb']['trxn_id'] = $params['contributionID'];
      $_SESSION['newweb']['is_pay_later'] = $is_pay_later;
      $_SESSION['newweb']['payment_instrument'] = $newweb_instrument;
      */
      
      print $output;
      // move things to CiviCRM cache as needed
      require_once 'CRM/Core/Session.php';
      CRM_Core_Session::storeSessionObjects( );
      exit();
    }

    function gwCreditCard(&$params, $component, $newweb_instrument){
      $config = $this->_config;

      // notify url for receive payment result
      $notify_url = $config->userFrameworkResourceURL."extern/gwipn.php?reset=1&contactID={$params['contactID']}"."&contributionID={$params['contributionID']}"."&module={$component}";

      if ( $component == 'event' ) {
        $notify_url .= "&eventID={$params['eventID']}&participantID={$params['participantID']}";
      }
      else {
        $membershipID = CRM_Utils_Array::value( 'membershipID', $params );
        if ( $membershipID ) {
          $notify_url .= "&membershipID=$membershipID";
        }
        $relatedContactID = CRM_Utils_Array::value( 'related_contact', $params );
        if ($relatedContactID) {
          $notify_url .= "&relatedContactID=$relatedContactID";
          $onBehalfDupeAlert = CRM_Utils_Array::value( 'onbehalf_dupe_alert', $params );
          if ($onBehalfDupeAlert) {
            $notify_url .= "&onBehalfDupeAlert=$onBehalfDupeAlert";
          }
        }
      }
      // if recurring donations, add a few more items
      if ( !empty( $params['is_recur']) ) {
         if ($params['contributionRecurID']) {
           $notify_url .= "&contributionRecurID={$params['contributionRecurID']}&contributionPageID={$params['contributionPageID']}";
         }
         else {
           CRM_Core_Error::fatal( ts( 'Recurring contribution, but no database id' ) );
         }
      }

      // building params
      $amount = $params['currencyID'] == 'TWD' && strstr($params['amount'], '.') ? substr($params['amount'], 0, strpos($params['amount'],'.')) : $params['amount'];
      $name = function_exists('truncate_utf8') ? truncate_utf8($params['item_name'], 10) : $params['item_name'];
      $notify_url .= "&qfKey=".$params['qfKey'];

      $gw_params = array(
        "client" => $this->_paymentProcessor['user_name'],
        "od_sob" => $params['contributionID'],
        "amount" => $amount,
        "roturl" => $notify_url,
        "bk_posturl" => $notify_url.'&bk_posturl=1',
        "qfKey" => $params['qfKey'],
        "#action" => $this->_paymentProcessor['url_site'],
      );

      return $this->formRedirect($gw_params);
    }

    function formRedirect($redirect_params){
      if(is_array($redirect_params)){
        $o .= '<form action="'.$redirect_params['#action'].'" name="redirect" method="post" id="redirect-form">';
        foreach($redirect_params as $k=>$p){
          if($k[0] != '#'){
            $o .= '<input type="hidden" name="'.$k.'" value="'.$p.'" />';
          }
        }
        $o .= '</form>';
      }

      header('Pragma: no-cache');
      header('Cache-Control: no-store, no-cache, must-revalidate');
      header('Expires: 0');
      return '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr"> 
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>'.ts('Redirect to Payment Page').'</title>
</head>
<body>
  '.$o.'
  <script type="text/javascript">
  document.forms.redirect.submit();
  </script>
</body>
<html>
';
    }
}
