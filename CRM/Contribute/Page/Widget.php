<?php
class CRM_Contribute_Page_Widget extends CRM_Core_Page {
  function run() {
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE, 0, 'GET');
    $embed = CRM_Utils_Request::retrieve('embed', 'Boolean', $this, TRUE, 0, 'GET');
    $page = array();
    CRM_Contribute_BAO_ContributionPage::setValues($id, $page);
    $widget = new CRM_Contribute_DAO_Widget();
    $widget->contribution_page_id = $id;
    if ($widget->find(TRUE)) {
      $this->assign('widgetId', $widget->id);
      $this->assign('cpageId', $id);
      $this->assign('pageTitle', $page['title']);
      $form = array();
      foreach($widget as $k => $v) {
        if (strstr($k, 'color')) {
          $form[$k]['value'] = $v;
        }
      }
      $this->assign('form', $form);
      $template = CRM_Core_Smarty::singleton();
      $widgetCode = $template->fetch('CRM/Contribute/Page/Widget.tpl');

      $this->assign('embedBody', $widgetCode);
      $widgetCode = $template->fetch('CRM/common/Embed.tpl');
      echo $widgetCode;
      // do not output drupal theme
      return;
    }
    parent::run();
  }
}
