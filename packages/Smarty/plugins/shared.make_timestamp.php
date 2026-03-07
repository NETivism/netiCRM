<?php
/**
 * Smarty shared plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Function: smarty_make_timestamp<br>
 * Purpose:  used by other smarty functions to make a timestamp
 *           from a string.
 * @author   Monte Ohrt <monte at ohrt dot com>
 * @param string
 * @return string
 */
function smarty_make_timestamp($string)
{
    if(empty($string)) {
        // use "now":
        $time = time();

    } elseif (preg_match('/^\d{14}$/', $string)) {
        // it is mysql timestamp format of YYYYMMDDHHMMSS?            
        $time = mktime(substr($string, 8, 2),substr($string, 10, 2),substr($string, 12, 2),
                       substr($string, 4, 2),substr($string, 6, 2),substr($string, 0, 4));
        
    } elseif (is_numeric($string)) {
        // it is a numeric string, we handle it as timestamp
        $time = (int)$string;
        
    } else {
        // strtotime should handle it
        $time = strtotime($string);
        if ($time == -1 || $time === false) {
            // strtotime() was not able to parse $string, use "now":
            $time = time();
        }
    }
    return $time;

}

/* vim: set expandtab: */

/**
 * Convert a strftime()-style format string to a date()-style format string.
 *
 * Used to replace deprecated strftime() calls (PHP 8.1+) with date().
 *
 * @param string $fmt  strftime format string
 * @return string      date() format string
 */
function _smarty_strftime_to_date_format($fmt) {
    return strtr($fmt, [
        '%a' => 'D',    '%A' => 'l',
        '%b' => 'M',    '%B' => 'F',
        '%d' => 'd',    '%e' => 'j',
        '%H' => 'H',    '%I' => 'h',
        '%j' => 'z',    '%m' => 'm',
        '%M' => 'i',    '%p' => 'A',    '%P' => 'a',
        '%S' => 's',    '%u' => 'N',    '%w' => 'w',
        '%W' => 'W',    '%Y' => 'Y',    '%y' => 'y',
        '%Z' => 'T',    '%z' => 'O',
        '%n' => "\n",   '%t' => "\t",   '%%' => '%',
    ]);
}

?>
