<?php
class CRM_Core_Page_SafariFix extends CRM_Core_Page {
  function run() {
    $redirect = CRM_Utils_Request::retrieve('ref', 'String', $this);
    setcookie('safariCookieFix', 1, CRM_REQUEST_TIME+86400*7, "/");
    if ($_SERVER['HTTP_REFERER']) {
      $referrer = parse_url($_SERVER['HTTP_REFERRER']);
      if ($referrer['host'] == $_SERVER['HTTP_HOST']) {
        $url = parse_url($redirect);
        if ($redirect) {
          CRM_Utils_System::redirect($redirect);
        }
      }
    }
    CRM_Utils_System::civiExit();
  }
}