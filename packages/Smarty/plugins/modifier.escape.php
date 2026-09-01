<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty escape modifier plugin
 *
 * Type:     modifier<br>
 * Name:     escape<br>
 * Purpose:  Escape the string according to escapement type
 * @link http://smarty.php.net/manual/en/language.modifier.escape.php
 *          escape (Smarty online manual)
 * @author   Monte Ohrt <monte at ohrt dot com>
 * @param string
 * @param html|htmlall|url|quotes|hex|hexentity|javascript
 * @return string
 */
function smarty_modifier_escape($string, $esc_type = 'html', $char_set = 'ISO-8859-1')
{
    $stringForFunctions = $string === null ? '' : $string;

    switch ($esc_type) {
        case 'html':
            return htmlspecialchars($stringForFunctions, ENT_QUOTES, $char_set);

        case 'htmlall':
            return htmlentities($stringForFunctions, ENT_QUOTES, $char_set);

        case 'url':
            return rawurlencode($stringForFunctions);

        case 'urlpathinfo':
            return str_replace('%2F','/',rawurlencode($stringForFunctions));
            
        case 'quotes':
            // escape unescaped single quotes
            return preg_replace("%(?<!\\\\)'%", "\\'", $stringForFunctions);

        case 'hex':
            // escape every character into hex
            $return = '';
            for ($x=0; $x < strlen($stringForFunctions); $x++) {
                $return .= '%' . bin2hex($stringForFunctions[$x]);
            }
            return $return;
            
        case 'hexentity':
            $return = '';
            for ($x=0; $x < strlen($stringForFunctions); $x++) {
                $return .= '&#x' . bin2hex($stringForFunctions[$x]) . ';';
            }
            return $return;

        case 'decentity':
            $return = '';
            for ($x=0; $x < strlen($stringForFunctions); $x++) {
                $return .= '&#' . ord($stringForFunctions[$x]) . ';';
            }
            return $return;

        case 'javascript':
            // escape quotes and backslashes, newlines, etc.
            return strtr($stringForFunctions, array('\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n','</'=>'<\/'));
            
        case 'mail':
            // safe way to display e-mail address on a web page
            return str_replace(array('@', '.'),array(' [AT] ', ' [DOT] '), $stringForFunctions);
            
        case 'nonstd':
           // escape non-standard chars, such as ms document quotes
           $_res = '';
           for($_i = 0, $_len = strlen($stringForFunctions); $_i < $_len; $_i++) {
               $_ord = ord(substr($stringForFunctions, $_i, 1));
               // non-standard char, escape it
               if($_ord >= 126){
                   $_res .= '&#' . $_ord . ';';
               }
               else {
                   $_res .= substr($stringForFunctions, $_i, 1);
               }
           }
           return $_res;

        default:
            return $string;
    }
}

/* vim: set expandtab: */

?>
