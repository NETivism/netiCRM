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
 * $Id: $
 *
 */

/**
 * class to provide simple static functions for file objects
 */
class CRM_Utils_File {

  /**
   * Given a file name, determine if the file contents make it an ascii file
   *
   * @param string $name name of file
   *
   * @return boolean     true if file is ascii
   * @access public
   */
  static function isAscii($name) {
    $fd = fopen($name, "r");
    if (!$fd) {
      return FALSE;
    }

    $ascii = TRUE;
    while (!feof($fd)) {
      $line = fgets($fd, 8192);
      if (!CRM_Utils_String::isAscii($line)) {
        $ascii = FALSE;
        break;
      }
    }

    fclose($fd);
    return $ascii;
  }

  /**
   * Given a file name, determine if the file contents make it an html file
   *
   * @param string $name name of file
   *
   * @return boolean     true if file is html
   * @access public
   */
  static function isHtml($name) {
    $fd = fopen($name, "r");
    if (!$fd) {
      return FALSE;
    }

    $html = FALSE;
    $lineCount = 0;
    while (!feof($fd) & $lineCount <= 5) {
      $lineCount++;
      $line = fgets($fd, 8192);
      if (!CRM_Utils_String::isHtml($line)) {
        $html = TRUE;
        break;
      }
    }

    fclose($fd);
    return $html;
  }

  /**
   * create a directory given a path name, creates parent directories
   * if needed
   *
   * @param string $path  the path name
   * @param boolean $abort should we abort or just return an invalid code
   *
   * @return void
   * @access public
   * @static
   */
  static function createDir($path, $abort = TRUE) {
    if (is_dir($path) || empty($path)) {
      return;
    }

    CRM_Utils_File::createDir(dirname($path), $abort);
    if (@mkdir($path, 0777) == FALSE) {
      if ($abort) {
        $docLink = CRM_Utils_System::docURL2('Moving an Existing Installation to a New Server or Location', FALSE, 'Moving an Existing Installation to a New Server or Location');
        echo "Error: Could not create directory: $path.<p>If you have moved an existing CiviCRM installation from one location or server to another there are several steps you will need to follow. They are detailed on this CiviCRM wiki page - {$docLink}. A fix for the specific problem that caused this error message to be displayed is to set the value of the config_backend column in the civicrm_domain table to NULL. However we strongly recommend that you review and follow all the steps in that document.</p>";


        CRM_Utils_System::civiExit();
      }
      else {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * delete a directory given a path name, delete children directories
   * and files if needed
   *
   * @param string $path  the path name
   *
   * @return void
   * @access public
   * @static
   */
  public static function cleanDir($target, $rmdir = TRUE) {
    static $exceptions = ['.', '..'];

    if ($sourcedir = @opendir($target)) {
      while (FALSE !== ($sibling = readdir($sourcedir))) {
        if (!in_array($sibling, $exceptions)) {
          $object = $target . DIRECTORY_SEPARATOR . $sibling;

          if (is_dir($object)) {
            CRM_Utils_File::cleanDir($object, $rmdir);
          }
          elseif (is_file($object)) {
            $result = @unlink($object);
          }
        }
      }
      closedir($sourcedir);

      if ($rmdir) {
        $result = @rmdir($target);
      }
    }
  }

  public static function copyDir($source, $destination) {

    $dir = opendir($source);
    @mkdir($destination);
    while (FALSE !== ($file = readdir($dir))) {
      if (($file != '.') && ($file != '..')) {
        if (is_dir($source . DIRECTORY_SEPARATOR . $file)) {
          CRM_Utils_File::copyDir($source . DIRECTORY_SEPARATOR . $file, $destination . DIRECTORY_SEPARATOR . $file);
        }
        else {
          copy($source . DIRECTORY_SEPARATOR . $file, $destination . DIRECTORY_SEPARATOR . $file);
        }
      }
    }
    closedir($dir);
  }

  /**
   * Given a file name, recode it (in place!) to UTF-8
   *
   * @param string $name name of file
   *
   * @return boolean  whether the file was recoded properly
   * @access public
   */
  static function toUtf8($name) {

    static $config = NULL;
    static $legacyEncoding = NULL;
    if ($config == NULL) {
      $config = CRM_Core_Config::singleton();
      $legacyEncoding = $config->legacyEncoding;
    }

    if (!function_exists('iconv')) {

      return FALSE;

    }

    $contents = file_get_contents($name);
    if ($contents === FALSE) {
      return FALSE;
    }

    if (function_exists('mb_convert_encoding')) {
      $contents = mb_convert_encoding($contents, 'UTF-8', $legacyEncoding);
    }
    else {
      $contents = iconv($legacyEncoding, 'UTF-8', $contents);
    }
    if ($contents === FALSE) {
      return FALSE;
    }

    $file = fopen($name, 'w');
    if ($file === FALSE) {
      return FALSE;
    }

    $written = fwrite($file, $contents);
    $closed = fclose($file);
    if ($written === FALSE or !$closed) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Appends trailing slashed to paths
   *
   * @return string
   * @access public
   * @static
   */
  static function addTrailingSlash($name, $separator = NULL) {
    if (!$separator) {
      $separator = DIRECTORY_SEPARATOR;
    }

    if (substr($name, -1, 1) != $separator) {
      $name .= $separator;
    }
    return $name;
  }

  static function sourceSQLFile($dsn, $fileName, $prefix = NULL, $isQueryString = FALSE, $dieOnErrors = TRUE) {


    $db = &DB::connect($dsn);
    if (PEAR::isError($db)) {
      die("Cannot open $dsn: " . $db->getMessage());
    }

    if (!$isQueryString) {
      $string = $prefix . file_get_contents($fileName);
    }
    else {
      // use filename as query string
      $string = $prefix . $fileName;
    }

    //get rid of comments starting with # and --

    $string = preg_replace("/^#[^\n]*$/m", "\n", $string);
    $string = preg_replace("/^(--[^-]).*/m", "\n", $string);

    $queries = preg_split('/;$/m', $string);
    foreach ($queries as $query) {
      $query = trim($query);
      if (!empty($query)) {
        $res = &$db->query($query);
        if (PEAR::isError($res)) {
          if ($dieOnErrors) {
            die("Cannot execute $query: " . $res->getMessage());
          }
          else {
            echo "Cannot execute $query: " . $res->getMessage() . "<p>";
          }
        }
      }
    }
  }

  static function isExtensionSafe($ext) {
    static $extensions = NULL;
    if (!$extensions) {

      $extensions = CRM_Core_OptionGroup::values('safe_file_extension', TRUE);

      //make extensions to lowercase
      $extensions = array_change_key_case($extensions, CASE_LOWER);
      // allow html/htm extension ONLY if the user is admin
      // and/or has access CiviMail

      if (!CRM_Core_Permission::check('access CiviMail') &&
        !CRM_Core_Permission::check('administer CiviCRM')
      ) {
        unset($extensions['html']);
        unset($extensions['htm']);
      }
    }
    //support lower and uppercase file extensions
    return isset($extensions[strtolower($ext)]) ? TRUE : FALSE;
  }

  /**
   * Determine whether a given file is listed in the PHP include path
   *
   * @param string $name name of file
   *
   * @return boolean  whether the file can be include()d or require()d
   */
  static function isIncludable($name) {
    $x = @fopen($name, 'r', TRUE);
    if ($x) {
      fclose($x);
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * remove the 32 bit md5 we add to the fileName
   * also remove the unknown tag if we added it
   */
  static function cleanFileName($name) {
    // replace the last 33 character before the '.' with null
    $name = preg_replace('/(_\w{8,32})\./', '.', $name);
    return $name;
  }

  static function makeFileName($name) {
    $uniqID = CRM_Utils_String::createRandom(8, CRM_Utils_String::ALPHANUMERIC);
    $info = pathinfo($name);
    $basename = mb_substr($info['basename'], 0, -(strlen(CRM_Utils_Array::value('extension', $info)) + (CRM_Utils_Array::value('extension', $info) == '' ? 0 : 1)));
    if (!self::isExtensionSafe(CRM_Utils_Array::value('extension', $info))) {
      // munge extension so it cannot have an embbeded dot in it
      // The maximum length of a filename for most filesystems is 255 chars.
      // We'll truncate at 240 to give some room for the extension.
      return CRM_Utils_String::munge("{$basename}_" . CRM_Utils_Array::value('extension', $info) . "_{$uniqID}", '_', 240) . ".unknown";
    }
    else {
      $basename = CRM_Utils_String::safeFilename($basename);
      if ($basename && mb_strlen($basename) <= 225) {
      // do not use munge to preserve original filename
        return "{$basename}_{$uniqID}".".".CRM_Utils_Array::value('extension', $info);
      }
      else {
        return CRM_Utils_String::munge("{$basename}_{$uniqID}", '_', 240) . "." . CRM_Utils_Array::value('extension', $info);
      }
    }
  }

  static function getFilesByExtension($path, $ext) {
    $path = self::addTrailingSlash($path);
    $dh = opendir($path);
    $files = [];
    while (FALSE !== ($elem = readdir($dh))) {
      if (substr($elem, -(strlen($ext) + 1)) == '.' . $ext) {
        $files[] .= $path . $elem;
      }
    }
    closedir($dh);
    return $files;
  }

  /**
   * Restrict access to a given directory (by planting there a restrictive .htaccess file)
   *
   * @param string $dir  the directory to be secured
   */
  static function restrictAccess($dir) {
    // note: empty value for $dir can play havoc, since that might result in putting '.htaccess' to root dir
    // of site, causing site to stop functioning.
    // FIXME: we should do more checks here -
    if (!empty($dir)) {
      $htaccess = <<<HTACCESS
      <Files "*">
        Order allow,deny
        Deny from all
      </Files>

      HTACCESS;
      $file = $dir . '.htaccess';
      if (file_put_contents($file, $htaccess) === FALSE) {

        CRM_Core_Error::movedSiteError($file);
      }
    }
  }

  /**
   * Create the base file path from which all our internal directories are
   * offset. This is derived from the cms public dir
   */
  static function baseFilePath($cmsDir = NULL) {
    static $path = NULL;
    if (!$path) {
      if ($cmsDir == NULL) {
        $cmsDir = CRM_Utils_System::cmsDir('public');
      }
      $path = CRM_Utils_System::cmsRootPath() . DIRECTORY_SEPARATOR . $cmsDir . DIRECTORY_SEPARATOR . CRM_Core_Config::SYSTEM_FILEDIR;
    }
    return self::addTrailingSlash($path);
  }

  static function relativeDirectory($directory) {
    // Do nothing on windows
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      return $directory;
    }

    // check if directory is relative, if so return immediately
    if (substr($directory, 0, 1) != DIRECTORY_SEPARATOR) {
      return $directory;
    }

    // make everything relative from the baseFilePath
    $basePath = self::baseFilePath();
    // check if basePath is a substr of $directory, if so
    // return rest of string
    if (substr($directory, 0, strlen($basePath)) == $basePath) {
      return substr($directory, strlen($basePath));
    }

    // return the original value
    return $directory;
  }

  static function absoluteDirectory($directory) {
    // Do nothing on windows - config will need to specify absolute path
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      return $directory;
    }

    // check if directory is already absolute, if so return immediately
    if (substr($directory, 0, 1) == DIRECTORY_SEPARATOR) {
      return $directory;
    }

    // make everything absolute from the baseFilePath
    $basePath = self::baseFilePath();

    return $basePath . $directory;
  }

  static function chmod($dir, $mode) {
    chmod($dir, $mode);
  }

  static function existsRename($destination) {
    $basename = basename($destination);
    $directory = str_replace($basename, '', $destination);
		// Strip control characters (ASCII value < 32). Though these are allowed in
		// some filesystems, not many applications handle them well.
		$basename = preg_replace('/[\x00-\x1F]/u', '_', $basename);
		if (substr(PHP_OS, 0, 3) == 'WIN') {
			// These characters are not allowed in Windows filenames
			$basename = str_replace([':', '*', '?', '"', '<', '>', '|'], '_', $basename);
		}

		if (file_exists($destination)) {
			// Destination file already exists, generate an alternative.
			$pos = strrpos($basename, '.');
			if ($pos !== FALSE) {
				$name = substr($basename, 0, $pos);
				$ext = substr($basename, $pos);
			}
			else {
				$name = $basename;
				$ext = '';
			}

			$counter = 0;
			do {
				$destination = $directory . $name . '_' . $counter++ . $ext;
			} while (file_exists($destination));
		}

		return $destination;
  }

  /**
   * Encrypt Xlsx content or file.
   * @param String $filePath file absolute path in the file system
   * 
   * @return Void
   */
  public static function encryptXlsxFile($filePath = NULL) {
    ini_set('memory_limit', '2048M');
    $config = CRM_Core_Config::singleton();
    $outputFile = $filePath;
    if (!$config->decryptExcelOption) {
      $outputFile = $filePath;
    }
    else {

      // Get the directory path of the file
      $dirPath = dirname($filePath);

      // Check if the file exists
      if (!file_exists($filePath)) {
        $msg = "[xlsx encrypt]: {$filePath} does not exist.";
      }

      // Check if the file is readable
      elseif (!is_readable($filePath)) {
        $msg = "[xlsx encrypt]: {$filePath} cannot be read.";
      }

      // Check if the file is in xlsx format
      elseif (pathinfo($filePath, PATHINFO_EXTENSION) !== 'xlsx') {
        $msg = "[xlsx encrypt]: {$filePath} is not in xlsx format.";
      }

      // Check if the directory has write permission
      elseif (!is_writable($dirPath)) {
        $msg = "[xlsx encrypt]: {$dirPath} does not have write permission.";
      }
      if (!empty($msg)) {
        CRM_Core_Error::debug_log_message($msg);
        $outputFile = $filePath;
      }
      else {
        $outputFile = preg_replace('/\.xlsx$/', "_encrypt.xlsx", $filePath);
        require_once 'secure-spreadsheet/autoload.php';
        if ($config->decryptExcelOption == 1) {
          // Get the user's primary email address
          $session = CRM_Core_Session::singleton();
          $contactId = $session->get('userID');
          $emails = CRM_Core_BAO_Email::allEmails($contactId);
          $i = 0;
          foreach ($emails as $emailArray) {
            $i++;
            if ($emailArray['is_primary'] || $i == 1) {
              $userEmail = $emailArray['email'];
              break;
            }
          }
    
          // Use SecureSpreadsheet to encrypt the file by user Email
          $encrypt = new \Nick\SecureSpreadsheet\Encrypt();
          $encrypt->input($filePath)
            ->password($userEmail)
            ->output($outputFile);
          unlink($filePath);
          rename($outputFile, $filePath);
        }
        else if ($config->decryptExcelOption == 2) {
          // Use SecureSpreadsheet to decrypt the file by custom password
          $encrypt = new \Nick\SecureSpreadsheet\Encrypt();
          $encrypt->input($filePath)
            ->password($config->decryptExcelPwd)
            ->output($outputFile);
          unlink($filePath);
          rename($outputFile, $filePath);
        }
      }
    }
  }

  /**
   * Sanitize Directory Name before use
   *
   * @param string $name
   * @return string
   */
  public static function sanitizeDirectoryName($name) {
    if (empty($name)) {
      return '';
    }
    $dirName = str_replace(['/', '\\', '..'], '', $name);
    $dirName = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $dirName);
    return $dirName;
  }

  /**
   * Sanitize File Name before use
   *
   * @param string $name
   * @return string
   */
  public static function sanitizeFileName($name) {
    if (empty($name)) {
      return '';
    }
    $filename = preg_replace(
      '~
      [<>:"/\\\|?*]|       # file system reserved
      [\x00-\x1F]|         # control characters
      [\x7F\xA0\xAD]|      # non-printing characters
      [#\[\]@!$&\'()+,;=]| # URI reserved
      [{}^\~`]|            # URL unsafe characters
      \.\.                 # Double dot for path traversal
      ~xu',
      '', $name);

    // avoids ".", ".." or ".hiddenFiles"
    $filename = ltrim($filename, '.-');

    // maximize filename length to 255 bytes
    if (strstr($filename, '.')) {
      $parts = explode('.', $filename);
      $ext = array_pop($parts);
      $basename = implode('.', $parts);
      $filename = mb_strcut($basename, 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($filename)) . ($ext ? '.' . $ext : '');
    }
    return $filename;
  }
}

