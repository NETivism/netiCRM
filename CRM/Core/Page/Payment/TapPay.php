<?php
class CRM_Core_Page_Payment_TapPay extends CRM_Core_Page {
  function run() {
    $qfKey = CRM_Utils_Array::value('qfKey', $_REQUEST, NULL);
    $id = CRM_Utils_Array::value('id', $_REQUEST, NULL);

    $component = CRM_Utils_Array::value('component', $_REQUEST, NULL);
    if ($component == 'contribute') {
      $className = 'CRM_Contribute_Controller_Contribution';  // these class use to store session variable name
    }
    elseif($component == 'event') {
      $className = 'CRM_Event_Controller_Registration'; 
    }
    else {
      $className = $component;
    }

    $payment = CRM_Core_Payment_TapPay::getAssociatedSession($qfKey, $className);
    if ($payment) {
      // we still need payment processor to go
      // payment processor build by CRM_Core_BAO_PaymentProcessor::getPayment
      if (empty($payment['paymentProcessor'])) {
        return CRM_Utils_System::notFound();
      }
      else {
        // needs payment processor keys
        $this->assign('payment_processor', $payment['paymentProcessor']);

        // we needs these to process payByPrime
        $this->assign('contribution_id', $payment['contributionID']);
        $this->assign('class_name', $className);
        $this->assign('qfKey', $qfKey);

        // we popup user context to handling payment submit redirect
        $session = CRM_Core_Session::singleton();
        $array = $session->get(CRM_Core_Session::USER_CONTEXT);
        $redirect = end($array);
        $this->assign('redirect', str_replace('&amp;', '&', $redirect));

        // back url when double submitted
        $url = parse_url($redirect);
        $this->assign('page_id', $payment['values']['id']);
        $this->assign('backlink', $url['path']."?reset=1&id=".$payment['values']['id']);
        return parent::run();
      }
    }
    return CRM_Utils_System::notFound();
  }
}
