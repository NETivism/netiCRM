<?php
include_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

function get_menu_links($name, $plid = 0){
  $result = db_query("SELECT * FROM menu_links WHERE menu_name = '%s' AND plid = %d", $name, $plid);
  $count = 0;
  while($m = db_fetch_array($result)){
    $m['options'] = unserialize($m['options']);
    $items[$count] = array(
      'path' => $m['link_path'],
      'query' => $m['options']['query'],
      'title' => $m['link_title'],
      'menu' => $name,
      'weight' => $m['weight'],
      'module' => 'menu',
      'hidden' => $m['hidden'],
    );
    $has_children = db_result(db_query("SELECT COUNT(*) FROM menu_links WHERE plid = %d", $m['mlid']));
    if($has_children){
      $items[$count]['children'] = get_menu_links($name, $m['mlid']);
    }
    $count++;
  }
  return $items;
}

$menu_name = $_GET['name'] ? $_GET['name'] : 'navigation';
$menu = get_menu_links($menu_name, 0);
$export = var_export($menu, TRUE);
dpr($export);
