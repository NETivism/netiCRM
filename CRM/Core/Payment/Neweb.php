<?php
date_default_timezone_set('Asia/Taipei');
require_once 'CRM/Core/Payment.php';

class CRM_Core_Payment_Neweb extends CRM_Core_Payment {
    /**
     * mode of operation: live or test
     *
     * @var object
     * @static
     */
    static protected $_mode = null;

    /**
     * We only need one instance of this object. So we use the singleton
     * pattern and cache the instance in this variable
     *
     * @var object
     * @static
     */
    static private $_singleton = null;
    
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
      $this->_processorName    = ts('Neweb');
      $config =& CRM_Core_Config::singleton( );
      $this->_config = $config;
    }

    /** 
     * singleton function used to manage this object 
     * 
     * @param string $mode the mode of operation: live or test
     *
     * @return object 
     * @static 
     * 
     */ 
    static function &singleton( $mode, &$paymentProcessor ) {
        $processorName = $paymentProcessor['name'];
        if (self::$_singleton[$processorName] === null ) {
            self::$_singleton[$processorName] = new CRM_Core_Payment_Neweb( $mode, $paymentProcessor );
        }
        return self::$_singleton[$processorName];
    }

    /** 
     * This function checks to see if we have the right config values 
     * 
     * @return string the error message if any 
     * @public 
     */ 
    function checkConfig( ) {
        $config = CRM_Core_Config::singleton( );

        $error = array( );

        if ( !empty( $this->_paymentProcessor['user_name'] ) xor !empty( $this->_paymentProcessor['signature'] )) {
            $error[] = ts( 'Credit Card Payment is not set in the Administer CiviCRM &raquo; Payment Processor.' );
        }

        if ( !empty( $this->_paymentProcessor['password'] ) xor !empty( $this->_paymentProcessor['subject'] )) {
            $error[] = ts( 'ECPay is not set in the Administer CiviCRM &raquo; Payment Processor.' );
        }
        
        if ( ! empty( $error ) ) {
            return implode( '<p>', $error );
        } else {
            return null;
        }
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

    function doTransferCheckout(&$params, $component) {
      $component = strtolower( $component );
      if ( $component != 'contribute' && $component != 'event' ) {
        CRM_Core_Error::fatal( ts('Component is invalid') );
      }

      // to see what instrument for neweb
      $neweb_instrument_id = $params['civicrm_instrument_id'];
      $neweb_instrument = $this->getInstrument($neweb_instrument_id);

      $is_pay_later = TRUE;
      switch($neweb_instrument){
        case 'Credit Card':
          $params_form = $this->newebCreditCard($params, $component, $neweb_instrument);
          $is_pay_later = FALSE;
          break;
        case 'ATM':
        case 'WEBATM':
        case 'CS':
        case 'MMK':
        case 'ALIPAY':
          $params_form = $this->newebEZPay($params, $component, $neweb_instrument);
          break;
      }

      // now process contribution to save some default value
      require_once 'CRM/Contribute/DAO/Contribution.php';
      $contribution =& new CRM_Contribute_DAO_Contribution();
      $contribution->id = $params['contributionID'];
      $contribution->find(true);
      if($params['civicrm_instrument_id']){
        $contribution->payment_instrument_id = $params['civicrm_instrument_id'];
      }
      if($contribution->is_pay_later != $is_pay_later){
        $contribution->is_pay_later = $is_pay_later;
      }
      $contribution->trxn_id = $params['is_recur'] ? $params['contributionID'] + 900000000  : $params['contributionID'];
      $contribution->save();

      // Inject in quickform sessions
      // Special hacking for display trxn_id after thank you page.
      $_SESSION['CiviCRM']['CRM_Contribute_Controller_Contribution_'.$params['qfKey']]['params']['trxn_id'] = $contribution->trxn_id;

      // making redirect form
      print $this->formRedirect($params_form, $neweb_instrument);
      // move things to CiviCRM cache as needed
      require_once 'CRM/Core/Session.php';
      CRM_Core_Session::storeSessionObjects( );
      exit();
    }

    function newebCreditCard(&$params, $component, $neweb_instrument){
      $config = $this->_config;
      $civi_base_url = $component == 'event' ? 'civicrm/event/register' : 'civicrm/contribute/transact';
      $cancel_url = CRM_Utils_System::url($civi_base_url,"_qf_Confirm_display=true&qfKey={$params['qfKey']}",false, null, false );
      $return_url = CRM_Utils_System::url($civi_base_url,"_qf_ThankYou_display=1&qfKey={$params['qfKey']}",true, null, false );

      // notify url for receive payment result
      $notify_url = "http://".$_SERVER['HTTP_HOST']."/neweb/ipn?reset=1&contactID={$params['contactID']}"."&contributionID={$params['contributionID']}"."&module={$component}";

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
      $amount = $params['currencyID'] == 'TWD' && strstr($params['amount'], '.') ? substr($params['amount'], 0, strpos($params['amount'],'.')) .'.00' : $params['amount'];
      $name = function_exists('truncate_utf8') ? truncate_utf8($params['item_name'], 10) : $params['item_name'];

      $neweb_params = array(
        "MerchantNumber" => $this->_paymentProcessor['user_name'],
        "OrderNumber"    => $params['is_recur'] ? $params['contributionID'] + 990000000  : $params['contributionID'],
        "Amount"         => $amount,
        "OrgOrderNumber" => $params['contributionID'],
        "ApproveFlag"    => 1,
        "DepositFlag"    => 0,
        "Englishmode"    => 0,
        "OrderURL"       => $notify_url,
        "ReturnURL"      => $return_url,
        "op"             => "AcceptPayment",
        "#action"        => $this->_paymentProcessor['url_site'],
        "#paymentProcessor" => $this->_paymentProcessor,
      );
      $neweb_params["checksum"] = md5($this->_paymentProcessor['user_name'].$neweb_params['OrderNumber'].$this->_paymentProcessor['signature'].$amount);

      return $neweb_params;
    }

    function newebEZPay(&$params, $component, $neweb_instrument){
      require_once 'CRM/Contact/DAO/Contact.php';
      $contact =& new CRM_Contact_DAO_Contact( );
      $contact->id = $params['contact'];
      $contact->find(true);

      if(strpos($params['amount'],'.') ){
        $amount = substr($params['amount'], 0, strpos($params['amount'],'.'));
      }
      else{
        $amount = $params['amount'];
      }
      
      $post = array();
      $post['merchantnumber'] = $this->_paymentProcessor['password'];
      $post['ordernumber'] = $params['contributionID'];
      $post['amount'] = $amount;
      $post['paymenttype'] = $neweb_instrument;
      $post['paytitle'] = $params['item_name'];
      $post['bankid'] = '004';
      $post['duedate'] = date('Ymd', time()+86400*7);
      if($neweb_instrument == 'CS'){
        $post['payname'] = $params['last_name']." ".$params['first_name'];
        if($params['phone']){
          $post['payphone'] = preg_replace("/[^\d]+/i", $params['phone']);
        }
      }
      $post['returnvalue'] = 0;
      $post['hash'] = md5($post['merchantnumber'].$this->_paymentProcessor['subject'].$amount.$post['ordernumber']);
      $civi_base_url = $component == 'event' ? 'civicrm/event/register' : 'civicrm/contribute/transact';
      $post['nexturl'] = CRM_Utils_System::url($civi_base_url,"_qf_ThankYou_display=1&qfKey={$params['qfKey']}&instrument={$neweb_instrument}",true, null, false );
      $post["#action"] = rtrim($this->_paymentProcessor['url_api'],'/')."/Payment";

      return $post;
    }

    function getInstrument($id = NULL, $type = 'name'){
      $instruments = array(
        'Credit Card' => 'Credit Card',
        'ATM' => 'ATM',
        'Web ATM' => 'WEBATM',
        'Convenient Store' => 'CS',
        'Convenient Store (Code)' => 'MMK',
        'Alipay' => 'ALIPAY',
      );
      $name = CRM_Core_DAO::singleValueQuery("SELECT cov.name FROM civicrm_option_group cog INNER JOIN civicrm_option_value cov ON cog.id = cov.option_group_id WHERE cog.name LIKE 'payment_instrument' AND cov.value = $id");
      return $instruments[$name];
    }

    function formRedirect($redirect_params, $instrument){
      header('Pragma: no-cache');
      header('Cache-Control: no-store, no-cache, must-revalidate');
      header('Expires: 0');

      switch($instrument){
        case 'Credit Card':
        case 'WEBATM':
        case 'ALIPAY':
          $js = 'document.forms.redirect.submit();';
          $o .= '<form action="'.$redirect_params['#action'].'" name="redirect" method="post" id="redirect-form">';
          foreach($redirect_params as $k=>$p){
            if($k[0] != '#'){
              $o .= '<input type="hidden" name="'.$k.'" value="'.$p.'" />';
            }
          }
          $o .= '</form>';
          break;
        case 'ATM':
        case 'CS':
        case 'MMK':
          $js = '
    function print_redirect(){
      // creating the "newebresult" window with custom features prior to submitting the form
      window.open("", "newebresult", "scrollbars=yes,menubar=no,height=600,width=800,resizable=yes,toolbar=no,status=no,left=150,top=150");
      document.forms.print.submit();
      window.location = "'.$redirect_params['nexturl'].'";
    }
          ';

          $o .= '<form action="'.$redirect_params['#action'].'" name="print" method="post" id="redirect-form" target="newebresult">';
          foreach($redirect_params as $k=>$p){
            if($k[0] != '#'){
              $o .= '<input type="hidden" name="'.$k.'" value="'.$p.'" />';
            }
          }
          $o .= '</form>';
          $o .= '<div align="center"><p>若網頁沒有自動跳出付款資訊，您可自行按下「取得付款資訊」按鈕以獲得繳款訊息</p><div><input type="button" value="取得付款資訊" onclick="print_redirect();" /></div></div>';
          break;
      }
      return '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr"> 
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
  '.$o.'
  <script type="text/javascript">
  '.$js.'
  </script>
</body>
<html>
';
    }
}

