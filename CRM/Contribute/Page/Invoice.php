<?php
class CRM_Contribute_Page_Invoice extends CRM_Core_Page {
  function run() {
    // template
    if(!empty($_GET['ii'])){
      $contribution = new CRM_Contribute_DAO_Contribution();
      $contribution->invoice_id = preg_replace('/[^0-9a-z]/i', '', $_GET['ii']);
      if($contribution->find(TRUE)) {
        $contribution_id = $contribution->id;
        $tplParams = array();
        $message = NULL;
        CRM_Utils_Hook::prepareInvoice($contribution_id, $tplParams, $message);
        $html = CRM_Contribute_BAO_Contribution::getInvoice($contribution_id, $tplParams, $message);
        echo $html;
        CRM_Utils_System::civiExit();
      }
    }
    CRM_Utils_System::notFound();
  }
}
