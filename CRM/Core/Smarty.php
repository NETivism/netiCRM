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
 * CiviCRM Smarty template engine initialization and configuration
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

/**
 * Fix for bug CRM-392. Not sure if this is the best fix or it will impact
 * other similar PEAR packages. doubt it
 */
if (!class_exists('Smarty')) {
  require_once 'Smarty/Smarty.class.php';
}

/**
 *
 */
class CRM_Core_Smarty extends Smarty {
  public const PRINT_PAGE = 1, PRINT_SNIPPET = 2, PRINT_PDF = 3, PRINT_NOFORM = 4;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  private static $_singleton = NULL;

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();

    $config = CRM_Core_Config::singleton();

    if (isset($config->customTemplateDir) && $config->customTemplateDir) {
      $this->template_dir = array_merge(
        [$config->customTemplateDir],
        $config->templateDir
      );
    }
    else {
      $this->template_dir = $config->templateDir;
    }
    $this->compile_dir = $config->templateCompileDir;

    //Check for safe mode CRM-2207
    if (ini_get('safe_mode')) {
      $this->use_sub_dirs = FALSE;
    }
    else {
      $this->use_sub_dirs = TRUE;
    }

    $customPluginsDir = NULL;
    if (isset($config->customPHPPathDir)) {
      $customPluginsDir = $config->customPHPPathDir . DIRECTORY_SEPARATOR . 'CRM' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Smarty' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR;
      if (!file_exists($customPluginsDir)) {
        $customPluginsDir = NULL;
      }
    }

    if ($customPluginsDir) {
      $this->plugins_dir = [$customPluginsDir, $config->smartyDir . 'plugins', $config->pluginsDir];
    }
    else {
      $this->plugins_dir = [$config->smartyDir . 'plugins', $config->pluginsDir];
    }

    // add the session and the config here
    $session = CRM_Core_Session::singleton();

    $this->assign_by_ref('config', $config);
    $this->assign_by_ref('session', $session);

    // check default editor and assign to template, store it in session to reduce db calls
    $defaultWysiwygEditor = $session->get('defaultWysiwygEditor');
    if (!$defaultWysiwygEditor && !CRM_Core_Config::isUpgradeMode()) {

      $defaultWysiwygEditor = CRM_Core_BAO_Preferences::value('editor_id');
      $session->set('defaultWysiwygEditor', $defaultWysiwygEditor);
    }

    $this->assign('defaultWysiwygEditor', $defaultWysiwygEditor);

    global $tsLocale;
    $this->assign('tsLocale', $tsLocale);

    // CRM-7163 hack: we don’t display langSwitch on upgrades anyway
    if (CRM_Utils_Array::value($config->userFrameworkURLVar, $_REQUEST) != 'civicrm/upgrade') {
      $this->assign('langSwitch', CRM_Core_I18n::languages(TRUE));
    }

    //check if logged in use has access CiviCRM permission and build menu
    $uid = CRM_Utils_System::getLoggedInUfID();
    $buildNavigation = 0;
    if ($uid) {
      $buildNavigation = $uid == 1 || CRM_Core_Permission::check('administer CiviCRM') ? TRUE : FALSE;
    }
    else {
      $buildNavigation = CRM_Core_Permission::check('access CiviCRM');
    }
    $this->assign('buildNavigation', $buildNavigation);

    if (!CRM_Core_Config::isUpgradeMode() && $buildNavigation) {

      $contactID = $session->get('userID');
      if ($contactID) {
        $navigation = CRM_Core_BAO_Navigation::createNavigation($contactID);
        $this->assign('navigation', $navigation);
      }
    }

    $this->register_function('crmURL', ['CRM_Utils_System', 'crmURL']);

    if (CRM_Utils_System::isUserLoggedIn() || $this->isAssigned('browserPrint')) {
      $printerFriendly = CRM_Utils_System::makeURL('snippet', FALSE, FALSE) . '2';
      $printerFriendly = str_replace(['&#60;', '&#62;', '#gt;', '&lt;', '<', '>'], '', $printerFriendly);
    }
    else {
      $printerFriendly = 'javascript:window.print()';
    }
    $this->assign('printerFriendly', $printerFriendly);

    // Add class to crm container
    $crm_container_class_arr = ["crm-container"];
    $current_path = CRM_Utils_System::currentPath();

    $md_allow_path = [
      "civicrm/event/register",
      "civicrm/event/info",
      "civicrm/contribute/transact",
      "civicrm/profile/create",
      "civicrm/event/confirm",
      "civicrm/event/cancel"
    ];

    if (in_array($current_path, $md_allow_path)) {
      $crm_container_class_arr[] = 'crm-container-md';
    }

    $crm_container_class = CRM_Utils_Array::implode(" ", $crm_container_class_arr);
    $this->assign('crm_container_class', $crm_container_class);
  }

  /**
   * Static instance provider.
   *
   * @return CRM_Core_Smarty
   */
  public static function &singleton() {
    if (!isset(self::$_singleton)) {
      self::$_singleton = new CRM_Core_Smarty();
    }
    return self::$_singleton;
  }

  /**
   * Marker prefix for the exception fetchUntrusted() raises on itself, so that
   * unrelated exceptions thrown while rendering are not swallowed.
   */
  public const UNTRUSTED_VIOLATION = 'untrusted template violation: ';

  /**
   * Stream wrappers refused inside the tags of an untrusted template.
   *
   * These hide what a template actually does: data: and php://filter carry
   * their payload base64 encoded, phar: and zip: bury it in an archive. None
   * of them has a legitimate use inside a message template tag, and a reviewer
   * cannot tell what they contain by reading the template.
   *
   * http and https are deliberately absent. Links are ordinary content, and
   * the one tag that could have opened them, {fetch}, is already replaced.
   *
   * @var array
   */
  private static $_untrustedWrappers = [
    'data', 'php', 'phar', 'file', 'glob', 'expect',
    'zip', 'rar', 'ogg', 'compress.zlib', 'compress.bzip2',
  ];

  /**
   * Function plugin tags refused in an untrusted template, lower cased.
   *
   * Smarty's secure mode covers {php}, {if} calls, modifiers and includes, but
   * it never looks at function plugins, so every one of these is reachable
   * from a stored template today. {crmAPI} is the sharp one: it builds a
   * require_once path out of its entity attribute and calls the api with no
   * permission check. The rest either read data a message has no business
   * reading ({crmDBTpl}, {crmKey}, {help}, {config_load}, {insert}) or dump
   * the whole variable scope into the mail ({debug}, {assign_debug_info}).
   *
   * None of them appears in any shipped message template.
   *
   * @var array
   */
  private static $_untrustedBlockedTags = [
    'crmapi', 'crmdbtpl', 'crmkey', 'help', 'fetch',
    'eval', 'debug', 'assign_debug_info', 'config_load', 'insert',
  ];

  /**
   * Render template source that came from user editable storage.
   *
   * Message templates are edited in the admin UI and stored in the database,
   * so their source is untrusted. netiCRM never turns Smarty security on, and
   * on the shared singleton {php}, {if system(...)} and php function modifiers
   * all run, so render on a secure copy instead.
   *
   * Smarty reports a violation with trigger_error(E_USER_ERROR) and carries
   * on, assuming php halts. Drupal's handler does not halt, so at some sites
   * the rejected call still reaches the compiled output. Those sites, listed
   * in $executionViolations, abort the render. Every other refusal is logged
   * and rendering continues, because the compiler has already made it
   * harmless: {php} and {include_php} emit nothing, a php call in {if} becomes
   * array(), {include} outside template_dir renders nothing at run time, and
   * {fetch} is blockedFetch().
   *
   * Scope is code execution, not confidentiality. {$smarty.const.X},
   * superglobals and private members are reported but still emitted, so they
   * do work; the $config whitelist in untrustedCopy() is what keeps the
   * database credentials out of a message.
   *
   * refs #32614, disable smarty evaluation functions
   *
   * @param string $resourceName template resource, normally a 'string:' one
   * @param Smarty|null $smarty instance holding the assigned variables
   *
   * @return string rendered output, empty if the source was rejected
   */
  public static function fetchUntrusted($resourceName, $smarty = NULL) {
    $copy = self::untrustedCopy($smarty);

    // Refusals where php would otherwise run, matched on the message because
    // that is all an error handler is given. Smarty_Compiler:1988 emits the
    // modifier call after reporting it, :1051 {include_php} runs a php file,
    // and untrustedPrefilter() only reports, so {crmAPI}, {insert} and {eval}
    // still reach php. The other tags it refuses cannot execute anything.
    $executionViolations = [
      '~\(secure mode\) modifier ~',
      '~\(secure mode\) include_php not permitted~',
      '~\(secure mode\) \'(?:crmapi|insert|eval)\' is not permitted in a database stored template~i',
    ];

    $handler = function ($errno, $errstr) use ($executionViolations) {
      if ($errno == E_USER_ERROR && strpos($errstr, '(secure mode)') !== FALSE) {
        // trigger_error() html encodes the message, quotes included on php 8.1+
        $message = html_entity_decode($errstr, ENT_QUOTES, 'UTF-8');
        foreach ($executionViolations as $pattern) {
          if (preg_match($pattern, $message)) {
            // Plain Exception: CRM_Core_Exception needs PEAR_Exception, and a
            // class failing to load inside an error handler would be the worst
            // possible way for this check to break.
            throw new Exception(CRM_Core_Smarty::UNTRUSTED_VIOLATION . $errstr);
          }
        }
        // Harmless already. Not handed to Drupal, whose handler writes into the
        // output buffer we are rendering into when error display is on.
        CRM_Core_Error::debug_log_message('Ignored in a database stored template: ' . $message);
        return TRUE;
      }
      // Anything else is none of our business, let php handle it as usual.
      return FALSE;
    };
    // E_USER_ERROR only, so nothing else even reaches the handler.
    set_error_handler($handler, E_USER_ERROR);

    // fetch() renders inside ob_start() and drops its error_reporting override
    // only on the way out. Throwing from the handler skips both, so remember
    // where we were and restore it in the finally.
    $bufferLevel = ob_get_level();
    $errorLevel = error_reporting();

    try {
      return $copy->fetch($resourceName);
    }
    catch (Exception $e) {
      if (strpos($e->getMessage(), self::UNTRUSTED_VIOLATION) !== 0) {
        throw $e;
      }
      CRM_Core_Error::debug_log_message('Refused to render a database stored template. ' . $e->getMessage());
      return '';
    }
    finally {
      restore_error_handler();
      while (ob_get_level() > $bufferLevel) {
        ob_end_clean();
      }
      error_reporting($errorLevel);
    }
  }

  /**
   * Check template source for the constructs fetchUntrusted() would refuse.
   *
   * Meant for validating admin input before it is stored, so the author gets
   * told at once instead of finding out when a receipt goes out empty. This
   * compiles the source with the very same secure instance rather than
   * matching a separate list of bad patterns, so the two can never drift
   * apart. Compiling does not run the template, the compiled output is
   * discarded.
   *
   * Only security violations are reported. Ordinary compile noise is ignored,
   * because stored templates still hold their CiviCRM tokens at this point and
   * Smarty has no idea what {contact.display_name} means.
   *
   * refs #32614, disable smarty evaluation functions
   *
   * @param string $source template source as the author typed it
   *
   * @return array human readable problems, empty when the source is fine
   */
  public static function validateUntrusted($source) {
    if (!is_string($source) || $source === '') {
      return [];
    }

    // Tokens are substituted long before the template reaches Smarty, so mask
    // them out. The replacement keeps the source on the same lines, which lets
    // us report a line number the author can act on.
    $masked = preg_replace('/(?<!\{|\\\\)\{\w+\.\w+\}(?!\})/', 'token', $source);

    $problems = [];
    $collect = function ($errno, $errstr) use (&$problems) {
      if (strpos($errstr, '(secure mode)') !== FALSE) {
        $message = html_entity_decode($errstr, ENT_QUOTES, 'UTF-8');
        $line = preg_match('/\[in \S+ line (\d+)\]/', $message, $match) ? $match[1] : NULL;
        // Drop the "(Smarty_Compiler.class.php, line 1417)" tail; it points at
        // our own code, which means nothing to whoever wrote the template.
        $detail = preg_match('/\(secure mode\)\s*(.+?)(?:\s*\([A-Za-z0-9_.]+\.php, line \d+\))?$/', $message, $match)
          ? $match[1] : $message;
        $problems[] = $line ? ts('line %1: %2', [1 => $line, 2 => $detail]) : $detail;
      }
      // Anything else is expected here: unrecognized CiviCRM tokens, undefined
      // variables and the like. Swallow it, it must not block the save.
      return TRUE;
    };

    $copy = self::untrustedCopy();
    set_error_handler($collect);
    try {
      $compiled = '';
      $copy->_compile_source('validation', $masked, $compiled);
    }
    finally {
      restore_error_handler();
    }

    // Smarty resolves {include} paths while rendering, not while compiling, so
    // the compile pass above cannot see them. Sending already refuses anything
    // outside the template directory; spotting the literal ones here just
    // moves the complaint to where the author can act on it. Missing a
    // computed path costs nothing, the send path still stops it.
    if (preg_match_all('/\{\s*include(?:_php)?\s[^}]*file\s*=\s*(["\']?)([^"\'\s}]+)\1/i', $masked, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        $path = $match[2];
        if (substr($path, 0, 1) === '/' || strpos($path, '..') !== FALSE || strpos($path, '://') !== FALSE) {
          $problems[] = ts('including "%1" is not allowed, only files under the template directory can be included', [1 => $path]);
        }
      }
    }

    return array_values(array_unique($problems));
  }

  /**
   * Get a copy of a Smarty instance that compiles template source in secure mode.
   *
   * The copy inherits every assigned variable and registered plugin, so the
   * templates still render; only the compiler gets stricter. Templates loaded
   * from disk keep using the singleton, where {php} is still needed by
   * CRM/common/chartist.tpl, CRM/Admin/Form/Options.tpl and a few others.
   *
   * @param Smarty|null $smarty instance to copy, defaults to the singleton
   *
   * @return Smarty
   */
  public static function untrustedCopy($smarty = NULL) {
    if (!is_object($smarty)) {
      $smarty = self::singleton();
    }

    $copy = clone $smarty;
    $copy->security = TRUE;
    $copy->security_settings['PHP_TAGS'] = FALSE;
    $copy->security_settings['PHP_HANDLING'] = FALSE;
    $copy->security_settings['INCLUDE_ANY'] = FALSE;
    $copy->security_settings['ALLOW_CONSTANTS'] = FALSE;
    $copy->security_settings['ALLOW_SUPER_GLOBALS'] = FALSE;

    // IF_FUNCS stays on the Smarty default; {if} only ever calls in_array,
    // which that list already allows.

    // MODIFIER_FUNCS does not. Every modifier the shipped templates use
    // (crmMoney, crmDate, truncate, date_format, nl2br, escape, string_format)
    // resolves as a plugin and never reaches this check, but templates written
    // in the admin UI reach for plain php functions, and _parse_modifiers()
    // refuses any that is not listed here (Smarty_Compiler.class.php:1988).
    // The Smarty default is count alone, so |number_format:0 in a receipt
    // aborts the whole render. These are formatting only: no file, network,
    // process or eval reachable through any of them.
    $copy->security_settings['MODIFIER_FUNCS'] = [
      'count', 'number_format', 'sprintf', 'trim',
      'strtolower', 'strtoupper', 'ucfirst', 'ucwords', 'strlen',
    ];

    // INCLUDE_ANY is off, but {include file="CRM/..."} still resolves because
    // smarty_core_is_secure() treats template_dir as a trusted base path.

    // Secure mode still lets {fetch} read http:// and ftp://, which would turn
    // a message template into an SSRF primitive. Nothing uses it.
    $copy->register_function('fetch', ['CRM_Core_Smarty', 'blockedFetch']);

    // Refuse the stream wrappers that let a template hide what it is doing.
    $copy->register_prefilter(['CRM_Core_Smarty', 'untrustedPrefilter']);

    // The constructor assigns $config and $session by reference for the page
    // templates. Secure mode puts no restriction on reading a property of an
    // assigned object, and CRM_Core_Config holds the database credentials in a
    // public $dsn, so {$config->dsn} in a message body would mail them out.
    // Nothing in a message template needs the session; $config is handed over
    // as a copy carrying only the display settings, which keeps
    // {$config->dateformatDatetime} working in the case activity templates.
    // Unsetting first drops the by-reference binding before we rebind the name.
    unset($copy->_tpl_vars['session'], $copy->_tpl_vars['config']);
    $copy->assign('config', self::untrustedConfig());

    return $copy;
  }

  /**
   * The part of CRM_Core_Config a database stored template may read.
   *
   * A whitelist rather than a blacklist: getting it wrong here means either a
   * date renders blank, or a credential leaves the building.
   *
   * @return stdClass
   */
  private static function untrustedConfig() {
    $names = [
      'dateformatDatetime', 'dateformatFull', 'dateformatPartial',
      'dateformatYear', 'dateformatTime',
      'defaultCurrency', 'defaultCurrencySymbol', 'currencySymbols',
      'moneyformat', 'moneyvalueformat',
      'monetaryDecimalPoint', 'monetaryThousandSeparator',
      'lcMessages', 'resourceBase', 'userFrameworkResourceURL',
    ];

    $config = CRM_Core_Config::singleton();
    $safe = new stdClass();
    foreach ($names as $name) {
      $safe->$name = $config->$name ?? NULL;
    }

    return $safe;
  }

  /**
   * Reject stream wrappers used inside the tags of an untrusted template.
   *
   * Registered as a prefilter so both the send path and the save time check go
   * through it, and so it sees the source before Smarty has taken it apart.
   *
   * Only tag contents are examined. A data: url in the surrounding html is
   * ordinary content, an inline image for instance, and has to keep working;
   * inside a tag it is a value on its way to a php function.
   *
   * refs #32614, disable smarty evaluation functions
   *
   * @param string $source template source
   * @param Smarty_Compiler &$compiler the compiler running this filter
   *
   * @return string the source, unchanged
   */
  public static function untrustedPrefilter($source, &$compiler) {
    $ldq = preg_quote($compiler->left_delimiter, '~');
    $rdq = preg_quote($compiler->right_delimiter, '~');

    // Blank out comments and {literal} blocks the way _compile_file() does,
    // keeping the newlines so reported line numbers still line up. Without
    // this, css braces inside a {literal} would look like template tags.
    $scanned = preg_replace_callback(
      "~{$ldq}\*.*?\*{$rdq}|{$ldq}\s*literal\s*{$rdq}.*?{$ldq}\s*/literal\s*{$rdq}~s",
      function ($match) {
        return str_repeat("\n", substr_count($match[0], "\n"));
      },
      $source
    );

    $wrappers = [];
    foreach (self::$_untrustedWrappers as $wrapper) {
      $wrappers[] = preg_quote($wrapper, '~');
    }
    $wrapperRegex = '~(?<![a-z0-9.+-])(' . CRM_Utils_Array::implode('|', $wrappers) . ')\s*:~i';

    if (preg_match_all("~{$ldq}\s*(.*?)\s*{$rdq}~s", $scanned, $matches, PREG_OFFSET_CAPTURE)) {
      foreach ($matches[1] as $match) {
        // _current_line_no is still 1 here, prefilters run before the tag loop
        // advances it, so point at the offending tag ourselves.
        $line = substr_count(substr($scanned, 0, $match[1]), "\n") + 1;

        if (preg_match($wrapperRegex, $match[0], $found)) {
          $compiler->_current_line_no = $line;
          $compiler->_syntax_error(
            "(secure mode) '{$found[1]}:' stream wrapper not permitted in a template tag",
            E_USER_ERROR,
            __FILE__,
            __LINE__
          );
        }

        // Secure mode does not reach function plugins at all:
        // _compile_custom_tag() resolves a tag against plugins_dir without ever
        // consulting $security, and {crmAPI} feeds its entity attribute
        // straight into require_once "api/v2/{$fnGroup}.php". Checking here
        // rather than at run time means the save time rule sees it too.
        if (preg_match('~^(\w+)~', $match[0], $command)
          && in_array(strtolower($command[1]), self::$_untrustedBlockedTags, TRUE)
        ) {
          $compiler->_current_line_no = $line;
          $compiler->_syntax_error(
            "(secure mode) '{$command[1]}' is not permitted in a database stored template",
            E_USER_ERROR,
            __FILE__,
            __LINE__
          );
        }
      }
    }

    return $source;
  }

  /**
   * Stand-in for {fetch}, which is not allowed in untrusted templates.
   *
   * @param array $params
   * @param Smarty &$smarty
   *
   * @return string
   */
  public static function blockedFetch($params, &$smarty) {
    CRM_Core_Error::debug_log_message(
      'Smarty {fetch} is not allowed in database stored templates, ignored: ' .
      CRM_Utils_Array::value('file', $params)
    );
    return '';
  }

  /**
   * Executes & returns or displays the template results.
   *
   * @param string $resource_name
   * @param string|null $cache_id
   * @param string|null $compile_id
   * @param bool $display
   *
   * @return string|null
   */
  public function fetch($resource_name, $cache_id = NULL, $compile_id = NULL, $display = FALSE) {

    $all_vars = &$this->get_template_vars();
    CRM_Utils_Hook::alterTemplateVars($resource_name, $all_vars);
    return parent::fetch($resource_name, $cache_id, $compile_id, $display);
  }

  /**
   * Appends a value to a template variable.
   *
   * @param string $name
   * @param mixed $value
   */
  public function appendValue($name, $value) {
    $currentValue = $this->get_template_vars($name);
    if (!$currentValue) {
      $this->assign($name, $value);
    }
    else {
      if (strpos($currentValue, $value) === FALSE) {
        $this->assign($name, $currentValue . $value);
      }
    }
  }

  /**
   * Clears all template variables except config and session.
   */
  public function clearTemplateVars() {
    foreach (array_keys($this->_tpl_vars) as $key) {
      if ($key == 'config' || $key == 'session') {
        continue;
      }
      unset($this->_tpl_vars[$key]);
    }
  }

  /**
   * Check if a template variable is assigned.
   *
   * @param string $var
   * @param mixed $value
   *
   * @return bool
   */
  public function isAssigned($var, $value = NULL) {
    if (isset($this->_tpl_vars[$var])) {
      $exists = $this->_tpl_vars[$var];
      if ($value) {
        if ($value === $exits) {
          return TRUE;
        }
      }
      else {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Add template directories.
   *
   * @param array $dirs
   */
  public function addTemplateDirs($dirs = []) {
    if (!empty($dirs) && is_array($dirs)) {
      $this->template_dir = array_merge($dirs, $this->template_dir);
    }
  }
}
