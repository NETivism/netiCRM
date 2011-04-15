#!/usr/bin/php
<?php
/**
 * smarty-extractor.php - rips gettext strings from Smarty {ts} calls
 *
 * ------------------------------------------------------------------------- *
 * This library is free software; you can redistribute it and/or             *
 * modify it under the terms of the GNU Lesser General Public                *
 * License as published by the Free Software Foundation; either              *
 * version 2.1 of the License, or (at your option) any later version.        *
 *                                                                           *
 * This library is distributed in the hope that it will be useful,           *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of            *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU         *
 * Lesser General Public License for more details.                           *
 *                                                                           *
 * You should have received a copy of the GNU Lesser General Public          *
 * License along with this library; if not, write to the Free Software       *
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA *
 * ------------------------------------------------------------------------- *
 *
 * This command line script rips gettext strings from smarty file, and prints
 * them to stdout; this can later be used with the standard gettext tools.
 *
 * Usage:
 * ./smarty-extractor.php <filename or directory> [file2, ...]
 *
 * If a parameter is a directory, the template files within will be parsed.
 *
 * @version   $Id$
 * @link      http://smarty-gettext.sf.net/
 * @author    Sagi Bashari <sagi@boom.org.il>
 * @author    Piotr Szotkowski <shot@civicrm.org>
 * @copyright 2004 Sagi Bashari
 * @license   http://www.gnu.org/licenses/lgpl.html  GNU Lesser General Public License
 */

$root = $_SERVER['argv'][1];
array_splice($_SERVER['argv'], 1, 1);

// smarty open tag
$ldq = preg_quote('{');

// smarty close tag
$rdq = preg_quote('}');

// smarty command
$cmd = preg_quote('ts');

// extensions of smarty files, used when going through a directory
$extensions = array('tpl');

// "fix" string - strip slashes, escape and convert new lines to \n
function fs($str)
{
    $str = stripslashes($str);
    $str = str_replace('"', '\"', $str);
    $str = str_replace("\n", '\n', $str);
    return $str;
}

// rips gettext strings from $file and prints them in C format
function do_file($file)
{
    $content = @file_get_contents($file);

    if (empty($content)) {
        return;
    }

    global $ldq, $rdq, $cmd, $root;

    // if there’s a {php} tag, fetch its contents into a file and parse it with php-extractor.php
    $phpTagMatches = array();
    preg_match_all("/{$ldq}\s*(php)\s*([^{$rdq}]*){$rdq}([^{$ldq}]*){$ldq}\/\\1{$rdq}/", $content, $phpTagMatches);
    $phpCode = $phpTagMatches[3][0];
    if ($phpCode) {
        // we want to create a file with the same path and name, but under the
        // tempdir; this allows the php-extractor.php to add the right comment
        $tempdir  = sys_get_temp_dir();
        $filedir  = substr(dirname($file), strlen($root) + 1);
        $filename = basename($file);
        @mkdir("$tempdir/$filedir", 0777, true);
        file_put_contents("$tempdir/$filedir/$filename", "<?php $phpCode ?>");
        passthru("bin/php-extractor.php $tempdir $tempdir/$filedir/$filename");
    }

    preg_match_all("/{$ldq}\s*({$cmd})\s*([^{$rdq}]*){$rdq}([^{$ldq}]*){$ldq}\/\\1{$rdq}/", $content, $matches);
    
    for ($i=0; $i < count($matches[0]); $i++) {
        $output = array();
        $output[] = '#: ' . substr($file, strlen($root) + 1);
        if (preg_match('/plural\s*=\s*["\']?\s*(.[^\"\']*)\s*["\']?/', $matches[2][$i], $match)) {
            $output[] = 'msgid "' . fs($matches[3][$i]) . '"';
            $output[] = 'msgid_plural "' . fs($match[1]) . '"';
            $output[] = 'msgstr[0] ""';
            $output[] = 'msgstr[1] ""';
        } else {
            $output[] = 'msgid "' . fs($matches[3][$i]) . '"';
            $output[] = 'msgstr ""';
        }
        print implode("\n", $output) . "\n\n";
    }

    preg_match_all("/{$ldq}\s*(docURL)\s*([^{$rdq}]*){$rdq}/", $content, $matches);

    for ($i=0; $i < count($matches[0]); $i++) {
        if (preg_match('/text\s*=\s*["\']?\s*(.[^\"\']*)\s*["\']?/', $matches[2][$i], $match)) {
            print '#: ' . substr($file, strlen($root) + 1) . "\n";
            print 'msgid "' . fs($match[1]) . "\"\n";
            print "msgstr \"\"\n\n";
        }
        if (preg_match('/title\s*=\s*["\']?\s*(.[^\"\']*)\s*["\']?/', $matches[2][$i], $match)) {
            print '#: ' . substr($file, strlen($root) + 1) . "\n";
            print 'msgid "' . fs($match[1]) . "\"\n";
            print "msgstr \"\"\n\n";
        }
    }
}

// go through a directory
function do_dir($dir)
{
    $d = dir($dir);

    while (false !== ($entry = $d->read())) {
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        $entry = $dir.'/'.$entry;

        if (is_dir($entry)) {
            // if a directory, go through it
            do_dir($entry);
        } else {
            // if file, parse only if extension is matched
            $pi = pathinfo($entry);
            
            if (isset($pi['extension']) && in_array($pi['extension'], $GLOBALS['extensions'])) {
                do_file($entry);
            }
        }
    }

    $d->close();
}

for ($ac=1; $ac < $_SERVER['argc']; $ac++) {
    if (is_dir($_SERVER['argv'][$ac])) {
        do_dir($_SERVER['argv'][$ac]);
    } else {
        do_file($_SERVER['argv'][$ac]);
    }
}


