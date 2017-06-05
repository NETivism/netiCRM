<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

function smarty_modifier_replacecharbystar($str) {
  if(strpos($str, '@') !== false){
    $glue = '@';
  }else{
    $glue = ' ';
  }
  $str_array = explode($glue, $str);
  $return_array = array();
  foreach ($str_array as $str2) {
    $return_array[] = _doAddStar($str2);
  }
  $return = implode($glue, $return_array);
  return $return;
}

function _doAddStar($str) {
   if(mb_strlen($str) > 2){
    $return = mb_substr($str,0,1);
    for ($i=1; $i < mb_strlen($str)-1 ; $i++) {
      $cha = mb_substr($str,$i,1);
      if($cha == ' '){
        $return .= ' ';
      }else if(ord($cha)> 0xa0){
        $return .= '＊';
      }else{
        $return .= '*';
      }
    }
    $return .= mb_substr($str,-1,1);
  }else{
   $return = preg_replace('/.$/u', '＊', $str);
  }
  return $return;
}



