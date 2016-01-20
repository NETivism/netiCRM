<?php
require_once(dirname(__FILE__).'../packages/barcode/barcode39.inc');

if(!empty($_GET['c']) && !empty($_GET['t'])){
  $code = preg_replace('/[^a-z0-9*]/i', '', $_GET['c']);
  $type = $_GET['t'];
  if($type == 'barcode39'){
    $bar = new Barcode39($code);
    $bar->barcode_text = !empty($_GET['u']) ? TRUE : FALSE;
    $bar->barcode_bar_thick = 3;
    $bar->barcode_bar_thin = 1;
    $bar->barcode_height = !empty(intval($_GET['h'])) ? intval($_GET['h']) : 40;
    $bar->draw();
  }
}
exit();
