<?php

function neticrm_preset_home(){
  global $user;
  if($_GET['t'] == 'preview'){
    $_SESSION['PREVIEW'] = 1;
  }
  if($_GET['t'] == 'reset'){
    $_SESSION['PREVIEW'] = 0;
  }

  if(!$user->uid || $_SESSION['PREVIEW']){
    $nid = variable_get("neticrm_preset_home", 0);
    if(!$nid){
      $node = new stdClass();
      $node->type = "page";
      $node->title = "歡迎！";
      $node->body = "這裡是 ".variable_get("site_name", '')."，系統管理者請從右欄登入。\n\n其他朋友，可回到組織的官方網站，了解電子報訂閱、志工參與、活動報名、募款專案等資訊。謝謝！";
      $node->status = 1;
      $node->revision = 1;
      $node->language = "zh-hant";
      $node->comment = 0;
      $node->uid = 1;
      $node->format = 2;
      $node->path = "homepage";
      node_save($node);
      $nid = $node->nid;
      variable_set('neticrm_preset_home', $node->nid);
    }
    else{
      $node = node_load($nid);
    }
    if($user->uid){
      $output = '<div>&raquo; '.l(t('Back to dashboard'), '<front>', array('query' => 't=reset')).'</div>';
      if(node_access('update', $node->nid)){
        $edit_link = '<span style="font-size:50%;">('.l(t("Edit"), "node/$node->nid/edit", array('query' => 'destination=<front>')).')</span>';
      }
    }
    else{
      $block = module_invoke("user", "block", 'view', "0");
    }
    $output .= '<div class="front-left-block"><h2>'.$node->title.' '.$edit_link.'</h2><div class="content">'.nl2br($node->body).'</div></div><div class="front-right-block">'.$block['content'].'</div>';

    $output = theme('neticrm_preset_home', $output);
    return $output;
  }
  else{
    $output = '<div>&raquo; '.l(t('Show welcome message'), '<front>', array('query' => 't=preview')).'</div>';
    // display the navigation function icon and description.
    $path = array(
      'civicrm/contact/search' => t('Find out your contact, add or edit their profile, classify contacts in group or tag, make relationships between contacts, import or export contacts data.'),
      'civicrm/contribute' => t('Handling contribution record, make online contribution page, define your contribution types, config priceset of contributions.'),
      'civicrm/event' => t('Setup online event registeration page, manage registeration status of participant, export or import participant data.'),
      'civicrm/mailing/browse' => t('Schedule mass mailing newsletters, check report of mailing, define your newsletter templates.'),
      'civicrm/member' => t('Define membership types, manage membership renew status, import or export membership data'),
      'civicrm/report/list' => t('Customize report for all of above function. Such as monthly contribution chart, or event income status.'),
    );
    foreach($path as $p => $description){
      $router_item = menu_get_item($p);
      if ($router_item['access']) {
        // now, start process the dashboard
        $link = db_fetch_array(db_query("SELECT * FROM {menu_links} WHERE link_path = '%s'", $p));
        $link['description'] = $description;
        $link['options']  = unserialize($link['options']);
        $output .= theme('neticrm_preset_home_item', $link);
      }
    }
    $output = theme('neticrm_preset_home', $output);
  }
  return $output;
}

function theme_neticrm_preset_home($items){
  return '<div id="neticrm-home" class="clear-block">'.$items.'</div>';
}

function theme_neticrm_preset_home_item($item){
  list($arg1, $arg2) = explode('/', $item['link_path']);
  $class = $arg1.'-'.$arg2;
  $options = $item['options'] ? $item['options'] : array();
  $options['html'] = true;
  $options['attributes']['class'] = 'neticrm-dashboard-link link-'.$class;
  $link = l('<span class="icon"></span>'.$item['link_title'], $item['link_path'], $options);
  return '<div class="neticrm-dashboard item-'.$item['mlid'].'">'.$link.'<div class="description">'.$item['description'].'</div></div>';
}
