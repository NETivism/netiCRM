<?php
class CRM_Core_Page_SafariFix extends CRM_Core_Page {
  function run() {
    $redirect = CRM_Utils_Request::retrieve('ref', 'String', $this);
    setcookie('safariCookieFix', 1, CRM_REQUEST_TIME+86400*7, "/");
    $url = parse_url($redirect);
    if ($redirect) {
      CRM_Utils_System::redirect($redirect);
    }
    else {
      CRM_Utils_System::civiExit();
    }
  }
}