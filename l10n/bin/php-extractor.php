#!/usr/bin/php
<?php

/* $Id$ */

/**
 * ts() calls extractor
 *
 * Drupal's t() extractor from http://drupal.org/project/drupal-pot
 * modified to suit CiviCRM's ts() calls
 *
 * Extracts translatable strings from specified function calls, plus adds some
 * file specific strings. Only literal strings with no embedded variables can
 * be extracted. Outputs a POT file on STDOUT, errors on STDERR
 *
 * @author Jacobo Tarrio <jtarrio [at] alfa21.com>
 * @author Gabor Hojtsy <goba [at] php.net>
 * @author Piotr Szotkowski <shot@caltha.pl>
 * @copyright 2003, 2004 Alfa21 Outsourcing
 * @license http://www.gnu.org/licenses/gpl.html  GNU General Public License
 */



/**
 * tsCallType return values
 */
define('TS_CALL_TYPE_INVALID', 0);
define('TS_CALL_TYPE_SINGLE', 1);
define('TS_CALL_TYPE_PLURAL', 2);



/**
 * Checks the type of the ts() call
 *
 * TS_CALL_TYPE_SINGLE  for a call resulting in calling gettext() (singular)
 * TS_CALL_TYPE_PLURAL  for a call resulting in calling ngettext() (plural)
 * TS_CALL_TYPE_INVALID for an invalid call
 *
 * @param array $tokens  the array with tokens from token_get_all()
 *
 * @return int  the integer representing the type of the call
 */
function tsCallType($tokens)
{

    // $tokens[0] == 'ts', $tokens[1] == '('
    $mid = $tokens[2];
    $rig = $tokens[3];

    // $mid has to be a T_CONSTANT_ENCAPSED_STRING
    if (!is_array($mid) or ($mid[0] != T_CONSTANT_ENCAPSED_STRING)) {
        return TS_CALL_TYPE_INVALID;
    }

    // if $rig is a closing paren, it's a valid call with no variables,
    // else $rig has to be a comma
    if ($rig == ')') {
        return TS_CALL_TYPE_SINGLE;
    } elseif ($rig != ',') {
        return TS_CALL_TYPE_INVALID;
    }

    // if $rig is a comma the next token must be a T_ARRAY call
    // and the next one must be an opening paren
    if ($tokens[4][0] != T_ARRAY or $tokens[5] != '(') {
        return TS_CALL_TYPE_INVALID;
    }

    // if there's an array, it cannot be empty
    // i.e. no ts('string', array()) calls
    if ($tokens[6] == ')') {
        return TS_CALL_TYPE_INVALID;
    }

    // let's iterate through the ts()'s array(...) contents
    $i = 6;
    $haveCount = false;
    $havePlural = false;

    while($i < count($tokens)) {
        $key = $tokens[$i];
        $doubleArrow = $tokens[$i + 1];
        $value = $tokens[$i + 2];

        // if it's not a => in the middle, it's not an array, really
        if ($doubleArrow[0] != T_DOUBLE_ARROW) {
            return TS_CALL_TYPE_INVALID;
        }

        if ($key[1] == "'count'" or $key[1] == '"count"') {
            // no double count declarations
            if ($haveCount) {
                return TS_CALL_TYPE_INVALID;
            }
            $haveCount = true;

        } elseif ($key[1] == "'plural'" or $key[1] == '"plural"') {
            // no double plural declarations
            if ($havePlural) {
                return TS_CALL_TYPE_INVALID;
            }
            $havePlural = true;
            // plural value must be a string
            if ($value[0] != T_CONSTANT_ENCAPSED_STRING) {
                return TS_CALL_TYPE_INVALID;
            }

        // ‘escape’ is a valid param, so accept it
        } elseif ($key[1] == "'escape'" or $key[1] == '"escape"') {
            // no-op

        // Drupal uses bang-prepended placeholders, so accept them
        } elseif (preg_match('/^[\'"]!\d+[\'"]$/', $key[1])) {
            // no-op

        // Drupal also uses words as placeholders, so accept them
        } elseif (preg_match('/^[\'"]%[a-z]+[\'"]$/', $key[1])) {
            // no-op

        // no non-number keys (except count and plural, above)
        } elseif ($key[0] != T_LNUMBER) {
            return TS_CALL_TYPE_INVALID;

        }

        // let's find where is the next ts()'s array(...) element
        $i += 3;
        // counter for paren pairs *inside* the element's value
        $parenCount = 0;
        while ($i < count($tokens) and ($parenCount > 0 or $tokens[$i] != ',')) {
            if ($parenCount < 1 and ($tokens[$i] == ')' or ($tokens[$i] == ',' and $tokens[$i + 1] == ')'))) {
                // we've reached the last element of the ts()'s array(...)
                break 2;
            }
            if ($tokens[$i] == '(') {
                $parenCount++;
            } elseif ($tokens[$i] == ')') {
                $parenCount--;
            }
            // we're still parsing the current element's value, as it can be multi-token:
            // ts('string with a %1 variable', array(1 => $object->method())
            $i++;
        }
        // let's move to the first token of the next element
        $i++;


    }

    // both present - we have a plural!
    if ($haveCount and $havePlural) {
        return TS_CALL_TYPE_PLURAL;

    // only one present - no deal
    } elseif ($haveCount or $havePlural) {
        return TS_CALL_TYPE_INVALID;

    // all of the array's keys are of type T_LNUMBER - it's a single call
    } else {
        return TS_CALL_TYPE_SINGLE;

    }

}



/**
 * Gets the plural string from the ts()'s array
 *
 * @param array $tokens  the array with tokens from token_get_all()
 *
 * @return string  the string containing the "plural" string from the ts()'s array
 */
function getPluralString($tokens)
{
    $plural = "";
    if (tsCallType($tokens) == TS_CALL_TYPE_PLURAL) {
        $i = 6;
        while($i < count($tokens)) {
            $key = $tokens[$i];
            $doubleArrow = $tokens[$i + 1];
            $value = $tokens[$i + 2];
            if ($key[1] == "'plural'" or $key[1] == '"plural"' and $doubleArrow[0] == T_DOUBLE_ARROW) {
                $plural = $value[1];
                break;
            }
            $i++;
        }
    }
    return $plural;
}



/**
 * Find all of the ts() calls
 *
 * @param array  $tokens  the array with tokens from token_get_all()
 * @param string $file    the string containing the file name
 *
 * @return void
 */
function findTsCalls($tokens, $file)
{

    global $strings;

    // iterate through all the tokens while there's still a chance for
    // a ts() call
    while (count($tokens) > 3) {

        list($ctok, $par, $mid, $rig, $arr) = $tokens;

        // the first token has to be a T_STRING (with a function name)
        if (!is_array($ctok)) {
            array_shift($tokens);
            continue;
        }

        // check whether we're at ts(
        list($type, $string, $line) = $ctok;
        if (($type == T_STRING) && ($string == 'ts' or $string == 't') && ($par == '(')) {

            switch (tsCallType($tokens)) {

            case TS_CALL_TYPE_SINGLE:
                $strings[formatQuotedString($mid[1])][$file][] = $line;
                break;

            case TS_CALL_TYPE_PLURAL:
                $plural = getPluralString($tokens);
                $strings[formatQuotedString($mid[1]) . "\0" . formatQuotedString($plural)][$file][] = $line;
                break;

            case TS_CALL_TYPE_INVALID:
                markerError($file, $line, 'ts', $tokens);
                break;

            default:
                break;

            }

        }

        array_shift($tokens);

    }

}



/**
 * gets the exact version number from the file, so we can push that into the POT
 *
 * @param string $code  the string with the contents of the file
 * @param string $file  the string with the file name
 *
 * @return void
 */
function findVersionNumber($code, $file)
{
    global $file_versions;
    // Prevent CVS from replacing this pattern with actual info
    if (preg_match('!\\$I' . 'd: ([^\\$]+) \\$!', $code, $version_info)) {
        $file_versions[$file] = $version_info[1];
    }
}



/**
 * formats a string for using it as a $strings array key
 *
 * @param string $str  the string up for formatting
 *
 * @return string  the string after formatting
 */
function formatQuotedString($str)
{
    $quo = substr($str, 0, 1);
    $str = substr($str, 1, -1);
    if ($quo == '"') {
        $str = stripcslashes($str);
    } else {
        $str = strtr($str, array("\\'" => "'", "\\\\" => "\\"));
    }
    return addcslashes($str, "\0..\37\\\"");
}
  


/**
 * writes an error string to STDERR
 *
 * @param string $file    the string containing the file the error's in
 * @param string $line    the string containing the line the error's in
 * @param string $marker  the string with the erroneous function name
 * @param array  $tokens  the array with the function's tokens
 *
 * @return void
 */
function markerError($file, $line, $marker, $tokens)
{
    fwrite(STDERR, "Invalid marker content in $file:$line\n* $marker(");
    array_shift($tokens);
    array_shift($tokens);
    $par = 1;
    while (count($tokens) && $par) {
        if (is_array($tokens[0])) {
            fwrite(STDERR, $tokens[0][1]);
        } else {
            fwrite(STDERR, $tokens[0]);
            if ($tokens[0] == "(") {
                $par++;
            }
            if ($tokens[0] == ")") {
                $par--;
            }
        }
        array_shift($tokens);
    }
    fwrite(STDERR, "\n\n");
}



/**
 * stores the string information
 *
 * @param string  $file      the string containing the filename the string's in
 * @param string  $input     the string containing the msgid/msgstr block
 * @param array   $filelist  the array containing the version info of the files
 * @param boolean $get       the boolean switch whether the call is storing
 *                           something or trying to get the whole storage back
 *
 * @return array  the array with the whole storage; only when $get == true
 */
function store($file = 0, $input = 0, $filelist = array(), $get = false)
{
    static $storage = array();
    if (!$get) {
        if (isset($storage[$file])) {
            $storage[$file][1] = array_unique(array_merge($storage[$file][1], $filelist));
            $storage[$file][] = $input;
        } else {
            $storage[$file] = array();
            $storage[$file][0] = '';
            $storage[$file][1] = $filelist;
            $storage[$file][2] = $input;
        }
    } else {
        return $storage;
    }
}



/**
 * writes the POT file
 *
 * this function writes the general.pot file
 *
 * @return void
 */
function writeFiles()
{

    // read the storage
    $output = store(0, 0, array(), true);

    // iterate through the files and merge the information for the same strings
    foreach ($output as $file => $content) {
        // the original created separate .pot files for each source file
        // that containted over 11 strings, we've dropped this rule
        //if (count($content) <= 11 && $file != 'general') {
        if ($file != 'general') {
            @$output['general'][1] = array_unique(array_merge($output['general'][1], $content[1]));
            if (!isset($output['general'][0])) {
                $output['general'][0] = $content[0];
            }
            unset($content[0]);
            unset($content[1]);
            foreach ($content as $msgid) {
                $output['general'][] = $msgid;
            }
            unset($output[$file]);
        }
    }

    // create the POT file
    foreach ($output as $file => $content) {

        $tmp = preg_replace('<[/]?([a-z]*/)*>', '', $file);
        $tmp = preg_replace('/^\.+/', '', $tmp);
        $file = str_replace('.', '-', $tmp) . '.pot';
        $filelist = $content[1];
        unset($content[1]);

        // source file and version information (from the Id tags)
        //if (count($filelist) > 1) {
        //    $filelist = "Generated from files:\n#  " . join("\n#  ", $filelist);
        //} elseif (count($filelist) == 1) {
        //    $filelist = "Generated from file: " . join("", $filelist);
        //} else {
        //    $filelist = "No version information was available in the source files.";
        //}

        // writing the final POT to the proper file(s) / STDOUT
        //$fp = fopen($file, 'w');
        fwrite(STDOUT, str_replace("--VERSIONS--", $filelist, join("", $content)));
        //fclose($fp);

    }

}



/*
 * the main code
 */

set_time_limit(0);
if (!defined("STDERR")) {
    define("STDERR", fopen("php://stderr", "w"));
}
  
$argv = $GLOBALS['argv'];
array_shift ($argv);
if (!count($argv)) {
    print "Usage: extractor.php file1 [file2 [...]]\n\n";
    return 1;
}

$strings = $file_versions = array();

// let's iterate through the files provided as commandline call parameters
foreach (array_slice($argv, 1) as $file) {

    $code = file_get_contents($file);
    
    // Extract raw tokens
    $raw_tokens = token_get_all($code);

    // Remove whitespace and HTML
    $tokens = array();
    $lineno = 1;
    foreach ($raw_tokens as $tok) {
        if ((!is_array($tok)) || (($tok[0] != T_WHITESPACE) && ($tok[0] != T_INLINE_HTML))) {
            if (is_array($tok)) {
                $tok[] = $lineno;
            }
            $tokens[] = $tok;
        }
        if (is_array($tok)) {
            $lineno += count(split("\n", $tok[1])) - 1;
        } else {
            $lineno += count(split("\n", $tok)) - 1;
        }
    }

    // let's find all of the ts() calls and put the results in $strings
    findTsCalls($tokens, $file);
    
    // find any occurences of a file's CVS/SVN version number
    findVersionNumber($code, $file);

}

// let's iterate through all of the strings and build the comment lines
foreach ($strings as $str => $fileinfo) {

    $occured = $filelist = array();

    foreach ($fileinfo as $file => $lines) {
//      $occured[] = "$file:" . join(";", $lines);
        $occured[] = substr($file, strlen($argv[0]) + 1);
        if (isset($file_versions[$file])) {
            $filelist[] = $file_versions[$file];
        }
    }
    
    $output = "#: " . join(" ", $occured) . "\n";
    $filename = ((count($occured) > 1) ? 'general' : $file);

    // if there's no \0 inside the $str string, it's a singular
    // else it's a plural
    if (strpos($str, "\0") === FALSE) {
        $output .= "msgid \"$str\"\n";
        $output .= "msgstr \"\"\n";
    } else {
        list ($singular, $plural) = explode("\0", $str);
        $output .= "msgid \"$singular\"\n";
        $output .= "msgid_plural \"$plural\"\n";
        $output .= "msgstr[0] \"\"\n";
        $output .= "msgstr[1] \"\"\n";
    }
    $output .= "\n";

    store($filename, $output, $filelist);

}

writeFiles();

return;



// These are never executed, you can run extractor.php on itself to test it
// $b, $f, $n, $s3 and $s4 should break
$a = ts("Test string 1" );
//$b = ts("Test string 2 %string", array("%string" => "how do you do"));
$c = ts('Test string 3');
$d = ts("Special\ncharacters");
$e = ts('Special\ncharacters');
//$f = ts("Embedded $variable");
$g = ts('Embedded $variable');
$h = ts("more \$special characters");
$i = ts('even more \$special characters');
$j = ts("Mixed 'quote' \"marks\"");
$k = ts('Mixed "quote" \'marks\'');
$l = ts('This is some repeating text');
$m = ts("This is some repeating text");
function embedded_function_call() { return 12; }
//$n = ts(embedded_function_call());
$s1 = ts('a test with a %1 variable, and %2 another one', array(1 => 'one', 2 => 'two'));
$s2 = ts('%3 – a plural test, %count frog', array('count' => 7, "plural" => 'a plural test, %count frogs', 3 => 'three'));
//$s3 = ts('a test – no count', array('plural' => 'No count here'));
//$s4 = ts('a test – no plural', array('count' => 42));
$s5 = ts('a test for multitoken element value', array(1 => $c . $d));


