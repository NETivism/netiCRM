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
 * Variables class contains definitions of all the core config settings that are allowed on
 * CRM_Core_Config. If you want a config variable to be present in run time config object,
 * it need to be defined here first.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

require_once 'CRM/Core/Config/Defaults.php';
class CRM_Core_Config_Variables extends CRM_Core_Config_Defaults {

  /**
   * the debug level for civicrm
   * @var int
   */
  public $debug = 0;
  public $backtrace = 0;

  /**
   * the directory where Smarty and plugins are installed
   * @var string
   */
  public $smartyDir = NULL;
  public $pluginsDir = NULL;

  /**
   * the root directory of our template tree
   * @var string
   */
  public $templateDir = NULL;

  /**
   * The resourceBase of our application. Used when we want to compose
   * url's for things like js/images/css
   * @var string
   */
  public $resourceBase = NULL;

  /**
   * The directory to store uploaded files
   */
  public $uploadDir = NULL;

  /**
   * The directory to store uploaded image files
   */
  public $imageUploadDir = NULL;

  /**
   * The directory to store uploaded  files in custom data
   */
  public $customFileUploadDir = NULL;

  /**
   * The url that we can use to display the uploaded images
   */
  public $imageUploadURL = NULL;

  /**
   * Are we generating clean url's and using mod_rewrite
   * @var string
   */
  public $cleanURL = FALSE;

  /**
   * List of country codes limiting the country list.
   * 1228 is an id for United States.
   * @var string
   */
  public $countryLimit = array('1228');

  /**
   * List of country codes limiting the province list.
   * 1228 is an id for United States.
   * @var string
   */
  public $provinceLimit = array('1228');

  /**
   * ISO code of default country for contact.
   * 1228 is an id for United States.
   * @var int
   */
  public $defaultContactCountry = '1228';

  /**
   * ISO code of default currency.
   * @var int
   */
  public $defaultCurrency = 'USD';

  /**
   * Locale for the application to run with.
   * @var string
   */
  public $lcMessages = CRM_Core_Config::SYSTEM_LANG;

  /**
   * String format for date+time
   * @var string
   */
  public $dateformatDatetime = '%B %E%f, %Y %l:%M %P';

  /**
   * String format for a full date (one with day, month and year)
   * @var string
   */
  public $dateformatFull = '%B %E%f, %Y';

  /**
   * String format for a partial date (one with month and year)
   * @var string
   */
  public $dateformatPartial = '%B %Y';

  /**
   * String format for a year-only date
   * @var string
   */
  public $dateformatYear = '%Y';

  /**
   * Display format for time
   * @var string
   */
  public $dateformatTime = '%l:%M %P';

  /**
   * Input format for time
   * @var string
   */
  public $timeInputFormat = 1;

  /**
   * Input format for date plugin
   * @var string
   */
  public $dateInputFormat = 'mm/dd/yy';

  /**
   * Month and day on which fiscal year starts.
   *
   * @var array
   */
  public $fiscalYearStart = array(
    'M' => 01,
    'd' => 01,
  );

  /**
   * String format for monetary amounts
   * @var string
   */
  public $moneyformat = '%c %a';

  /**
   * String format for monetary values
   * @var string
   */
  public $moneyvalueformat = '%!i';

  /**
   * Format for monetary amounts
   * @var string
   */
  public $currencySymbols = '';

  /**
   * Format for monetary amounts
   * @var string
   */
  public $defaultCurrencySymbol = '$';

  /**
   * Monetary decimal point character
   * @var string
   */
  public $monetaryDecimalPoint = '.';

  /**
   * Monetary thousands separator
   * @var string
   */
  public $monetaryThousandSeparator = ',';

  /**
   * Default encoding of strings returned by gettext
   * @var string
   */
  public $gettextCodeset = 'utf-8';

  /**
   * Default name for gettext domain.
   * @var string
   */
  public $gettextDomain = 'civicrm';

  /**
   * Default location of gettext resource files.
   */
  public $gettextResourceDir = NULL;

  /**
   * Default user framework
   */
  public $userFramework = 'Drupal';
  public $userFrameworkUsersTableName = 'users';
  public $userFrameworkClass = 'CRM_Utils_System_Drupal';
  public $userHookClass = 'CRM_Utils_Hook_Drupal';
  public $userPermissionClass = 'CRM_Core_Permission_Drupal';
  public $userFrameworkURLVar = 'q';
  public $userFrameworkDSN = NULL;
  public $userFrameworkBaseURL = NULL;
  public $userFrameworkResourceURL = NULL;
  public $userFrameworkFrontend = FALSE;
  public $userFrameworkLogging = FALSE;

  /**
   * the handle for import file size
   * @var int
   */
  public $maxImportFileSize = 1048576;
  public $maxAttachments = 3;
  public $maxFileSize = 2;

  /**
   * The custom locale strings. Note that these locale strings are stored
   * in a separate column in civicrm_domain
   * @var array
   */
  public $localeCustomStrings = NULL;

  /**
   * Map Provider
   *
   * @var boolean
   */
  public $mapProvider = NULL;

  /**
   * Map API Key
   *
   * @var boolean
   */
  public $mapAPIKey = NULL;

  /**
   * How should we get geo code information if google map support needed
   *
   * @var boolean
   */
  public $geocodeMethod = '';

  /**
   *
   *
   * @var boolean
   */
  public $mapGeoCoding = 1;

  /**
   * Whether deleted contacts should be moved to trash instead
   * @var boolean
   */
  public $contactUndelete = TRUE;

  /**
   * Whether database-level logging should be performed
   * @var boolean
   */
  public $logging = FALSE;

  /**
   * Whether CiviCRM should check for newer versions
   *
   * @var boolean
   */
  public $versionCheck = TRUE;

  /**
   * Array of enabled add-on components (e.g. CiviContribute, CiviMail...)
   *
   * @var array
   */
  public $enableComponents = array('CiviContribute', 'CiviPledge', 'CiviMember',
    'CiviEvent', 'CiviMail', 'CiviReport',
  );
  public $enableComponentIDs = array(1, 6, 2, 3, 4, 8);

  /**
   * Should payments be accepted only via SSL?
   *
   * @var boolean
   */
  public $enableSSL = FALSE;

  /**
   * error template to use for fatal errors
   *
   * @var string
   */
  public $fatalErrorTemplate = 'CRM/common/fatal.tpl';

  /**
   * fatal error handler
   *
   * @var string
   */
  public $fatalErrorHandler = NULL;

  /**
   * legacy encoding for file encoding conversion
   *
   * @var string
   */
  public $legacyEncoding = 'Windows-1252';

  /**
   * field separator for import/export csv file
   *
   * @var string
   */
  public $fieldSeparator = ',';

  /**
   * max location blocks in address
   *
   * @var integer
   */
  public $maxLocationBlocks = 2;

  /**
   * the font path where captcha fonts are stored
   *
   * @var string
   */
  public $captchaFontPath = '/usr/X11R6/lib/X11/fonts/';

  /**
   * the font to use for captcha
   *
   * @var string
   */
  public $captchaFont = 'HelveticaBold.ttf';

  /**
   * Some search settings
   */
  public $includeWildCardInName = 1;
  public $includeEmailInName = 1;
  public $includeNickNameInName = 0;
  public $smartGroupCacheTimeout = 60;

  public $defaultSearchProfileID = NULL;

  /**
   * Dashboard timeout
   */
  public $dashboardCacheTimeout = 1440;

  /**
   * flag to indicate if acl cache is NOT to be reset
   */
  public $doNotResetCache = 0;

  /**
   * Optimization related variables
   */
  public $includeAlphabeticalPager = 1;
  public $includeOrderByClause = 1;
  public $oldInputStyle = 1;

  /**
   * should we disbable key generation for forms
   *
   * @var boolean
   */
  public $formKeyDisable = FALSE;

  /**
   * to determine wether the call is from cms or civicrm
   */
  public $inCiviCRM = FALSE;

  /**
   * component registry object (of CRM_Core_Component type)
   */
  public $componentRegistry = NULL;

  /**
   * PDF reciept as attachment is enabled
   */
  public $doNotAttachPDFReceipt = TRUE;

  /**
   * Path to wkhtmltopdf if available
   */
  public $wkhtmltopdfPath = FALSE;

  /**
   * The default value for Content Security Policy recover.
   */
  public $defaultCSP = "default-src 'self'; img-src 'self' data: https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://analytics.neticrm.tw https://*.googletagmanager.com https://*.google-analytics.com https://www.google.com/recaptcha/api.js https://*.facebook.net https://*.facebook.com https://*.twitter.com https://*.tappaysdk.com https://www.gstatic.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.gstatic.com; font-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com; frame-src 'self' https://neticrm.tw https://*.facebook.net https://*.facebook.com https://*.twitter.com https://*.youtube.com https://*.vimeo.com https://*.tappaysdk.com https://www.google.com; object-src 'none'; connect-src 'self' https://neti.cc";

  /**
   * The default value for Content Security Policy setting.
   */
  public $cspRules = "";

  /**
   *  AI Organization profile
   */
  public $aiOrganizationIntro = '';

  /**
   * Provide addressSequence
   *
   * @param
   *
   * @return string
   */
  public function addressSequence() {
    require_once 'CRM/Core/BAO/Preferences.php';
    return CRM_Core_BAO_Preferences::value('address_sequence');
  }

  /**
   * Provide cached default currency symbol
   *
   * @param
   *
   * @return string
   */
  public function defaultCurrencySymbol($defaultCurrency = NULL) {
    static $cachedSymbol = NULL;
    if (!$cachedSymbol || $defaultCurrency) {
      if ($this->defaultCurrency || $defaultCurrency) {
        require_once "CRM/Core/PseudoConstant.php";
        $currencySymbolName = CRM_Core_PseudoConstant::currencySymbols('name');
        $currencySymbol = CRM_Core_PseudoConstant::currencySymbols();

        $this->currencySymbols = array_combine($currencySymbolName, $currencySymbol);
        $currency = $defaultCurrency ? $defaultCurrency : $this->defaultCurrency;
        $cachedSymbol = CRM_Utils_Array::value($currency, $this->currencySymbols, '');
      }
      else {
        $cachedSymbol = '$';
      }
    }
    return $cachedSymbol;
  }

  /**
   * Provide cached default currency symbol
   *
   * @param
   *
   * @return string
   */
  public function defaultContactCountry() {
    static $cachedContactCountry = NULL;
    if (!$cachedContactCountry) {
      $countryIsoCodes = CRM_Core_PseudoConstant::countryIsoCode();
      $cachedContactCountry = $countryIsoCodes[$this->defaultContactCountry];
    }
    return $cachedContactCountry;
  }

  /**
   * Provide cached default country name
   *
   * @param
   *
   * @return string
   */
  public function defaultContactCountryName() {
    static $cachedContactCountryName = NULL;
    if (!$cachedContactCountryName) {
      $countryCodes = CRM_Core_PseudoConstant::country();
      $cachedContactCountryName = $countryCodes[$this->defaultContactCountry];
    }
    return $cachedContactCountryName;
  }

  /**
   * Provide cached country limit translated to names
   *
   * @param
   *
   * @return array
   */
  public function countryLimit() {
    static $cachedCountryLimit = NULL;
    if (!$cachedCountryLimit) {
      $countryIsoCodes = CRM_Core_PseudoConstant::countryIsoCode();
      $country = array();
      if (is_array($this->countryLimit)) {
        foreach ($this->countryLimit as $val) {
          $country[] = $countryIsoCodes[$val];
        }
      }
      else {
        $country[] = $countryIsoCodes[$this->countryLimit];
      }
      $cachedCountryLimit = $country;
    }
    return $cachedCountryLimit;
  }

  /**
   * Provide cached province limit translated to names
   *
   * @param
   *
   * @return array
   */
  public function provinceLimit() {
    static $cachedProvinceLimit = NULL;
    if (!$cachedProvinceLimit) {
      $countryIsoCodes = CRM_Core_PseudoConstant::countryIsoCode();
      $country = array();
      if (is_array($this->provinceLimit)) {
        foreach ($this->provinceLimit as $val) {
          $country[] = $countryIsoCodes[$val];
        }
      }
      else {
        $country[] = $countryIsoCodes[$this->provinceLimit];
      }
      $cachedProvinceLimit = $country;
    }
    return $cachedProvinceLimit;
  }
}
// end CRM_Core_Config

