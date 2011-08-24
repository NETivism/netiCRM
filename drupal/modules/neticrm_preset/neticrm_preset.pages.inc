<?php

function neticrm_preset_home(){
  global $user;
  if(!$user->uid){
    // for now, we redirect anonymous user to "user/login" page.
    drupal_goto('user');
  }
  else{
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
