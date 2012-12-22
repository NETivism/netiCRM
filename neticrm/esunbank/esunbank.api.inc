<?php

/**
 * 產生銷帳編號
 */
function esunbank_serial($code, $number, $length){
  $code = (string)$code;
  $number = (string)$number;
  $len = strlen($code.$number);
  if($len <= $length -1){
    // zero padding
    $zero_count = $length - 1 - $len;
    $serial = $code.str_repeat('0', $zero_count).$number;
    $serial .= esunbank_check_serial($serial);
    return $serial;
  }
  else{
    return 'serial too long';
  }
}

/**
 * 產生銷帳編號附帶金額檢驗
 */
function esunbank_serial_price($code, $number, $length, $price){
  $code = (string)$code;
  $number = (string)$number;

  $len = strlen($code.$number);
  if($len <= $length -1){
    // zero padding
    $zero_count = $length - 1 - $len;
    $serial = $code.str_repeat('0', $zero_count).$number;
    $serial .= esunbank_check_serial_price($serial, $price);
    return $serial;
  }
  else{
    return 'serial too long';
  }
}

/**
 * 產生銷帳編號附帶繳款截止日
 * 繳款截止日前30日必須繳款
 */
function esunbank_serial_price_date($code, $number, $length, $price, $timestamp){
  $code = (string)$code;
  $number = (string)$number;

  $len = strlen($code.$number) + 4;
  if($len <= $length -1){
    // zero padding
    $zero_count = $length - 1 - $len;
    $date = date('md', $timestamp);
    $serial = $code.$date.str_repeat('0', $zero_count).$number;
    $serial .= esunbank_check_serial_price($serial, $price);
    return $serial;
  }
  else{
    return 'serial too long';
  }
}

/**
 * 產生銷帳編號附帶繳款截止日與時間
 * 繳款截止日時前240小時必須繳款
 */
function esunbank_serial_price_hour($code, $number, $length, $price, $timestamp){
  $code = (string)$code;
  $number = (string)$number;

  $len = strlen($code.$number) + 5;
  if($len <= $length -1){
    // zero padding
    $zero_count = $length - 1 - $len;

    $date = date('z', $timestamp) + 1;
    $date = str_repeat('0', 3-strlen($date)). $date;
    $hour = date('H', $timestamp);
    $serial = $code.$date.$hour.str_repeat('0', $zero_count).$number;
    $serial .= esunbank_check_serial_price($serial, $price);
    return $serial;
  }
  else{
    return 'serial too long';
  }

}

/**
 * 檢核帳號
 */ 
function esunbank_check_serial($serial){
  return _esunbank_check_calc($serial);
}

/**
 * 檢核帳號與金額
 */
function esunbank_check_serial_price($serial, $price){
  return _esunbank_check_calc($serial, $price);
}

function _esunbank_check_calc($serial, $price = NULL){
  $check = 0;
  $serial = (string)$serial;
  $ary = str_split($serial);
  $ary = array_reverse($ary);
  foreach($ary as $k => $v){
    $plus = $k%9+1;
    $check += $v*$plus;
  }
  if(!empty($price)){
    $price = (string)$price;
    $ary = str_split($price);
    $ary = array_reverse($ary);
    foreach($ary as $k => $v){
      $plus = $k%9+1;
      $check += $v*$plus;
    }
  }
  $check = (string)$check;
  return substr($check, -1);
}

function _esunbank_check_api(){
  $code = 99123;
  $number = 3939889;
  $length = 13;
  $price = 1500;
  $timestamp1 = strtotime('2012-01-19 00:00:00');
  $timestamp2 = strtotime('2013-08-01 10:00:00');
  $output[] = esunbank_serial($code, $number, $length);
  $output[] = esunbank_serial_price($code, $number, $length, $price);
  $output[] = esunbank_serial_price_date($code, 1, $length, $price, $timestamp1);
  $output[] = esunbank_serial_price_hour($code, 89, $length, $price, $timestamp2);
  return $output;
} 

/**
 * 超商條碼
 */
function esunbank_store_a($timestamp, $code){
  $year = _esunbank_year2roc($timestamp);
  $year = substr($year, -2);
  $date = $year . date('md', $timestamp);
  $bar1 = $date.$code;

  // image of barcode
}

function esunbank_store_b($serial){
  $len = 16 - strlen($serial);
  if($len > 0){
    $bar2 = $serial.str_repeat('0', $len);
  }
  else{
    $bar2 = $serial;
  }
}

function esunbank_store_c($timestamp, $price, $a, $b){
  $date = date('md', $timestamp);
  $price = (string) $price;
  $len = 9-strlen($price);
  if($len > 0){
    $price = str_repeat('0', $len).$price;
  }
  $bar3 = $date.'##'.$price;
}

function _esunbank_store_calc($type, $a, $b, $c){
  $map = _esunbank_store_calc_map();
  $run = array($a, $b, $c);
  $sum = array();
  foreach($run as $k => $s){
    $ary = str_split($s);
    $sum[$k] = 0;
    foreach($ary as $key => $char){
      $even = ($key+1)%2 == 0 ? true : false;
      if($type == 'odd' && $even) continue;
      if($type == 'even' && !$even) continue;
      if($char == '#') continue;

      // start calc
      if(!is_numeric($char)){
        $char = $map[$char];
      }
      $sum[$k] += $char;
    }
  }
  $total = array_sum($sum);
  $check = $total%11;
  if($type == 'odd'){
    return $check == 10 ? 'B' : ($check == 0 ? 'A' : $check);
  }
  else{
    return $check == 10 ? 'Y' : ($check == 0 ? 'X' : $check);
  }
}

function _esunbank_store_calc_map(){
  $ary = array(
    'A' => 1,
    'B' => 2,
    'C' => 3,
    'D' => 4,
    'E' => 5,
    'F' => 6,
    'G' => 7,
    'H' => 8,
    'I' => 9,
    'J' => 1,
    'K' => 2,
    'L' => 3,
    'M' => 4,
    'N' => 5,
    'O' => 6,
    'P' => 7,
    'Q' => 8,
    'R' => 9,
    'S' => 2,
    'T' => 3,
    'U' => 4,
    'V' => 5,
    'W' => 6,
    'X' => 7,
    'Y' => 8,
    'Z' => 9,
  );
  return $ary;
}

function _esunbank_year2roc($timestamp){
  $ac = date('Y', $timestamp);
  $ac = (int)$ac;
  $roc = $ac - 1911;
  if($roc < 1){
    return FALSE;
  }
  return $roc;
}

# print_r(_esunbank_check_api());

