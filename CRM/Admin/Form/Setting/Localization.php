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

require_once 'CRM/Admin/Form/Setting.php';

/**
 * This class generates form components for Localization
 *
 */
class CRM_Admin_Form_Setting_Localization extends CRM_Admin_Form_Setting {
  // use this variable to store mappings that we compute in buildForm and also
  // use in postProcess (CRM-1496)
  protected $_currencySymbols;

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $config = CRM_Core_Config::singleton();

    $i18n = &CRM_Core_I18n::singleton();
    CRM_Utils_System::setTitle(ts('Settings - Localization'));

    $locales = &CRM_Core_I18n::languages();

    require_once 'CRM/Core/DAO/Domain.php';
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    if ($domain->locales) {
      // for multi-lingual sites, populate default language drop-down with available languages
      $lcMessages = array();
      foreach ($locales as $loc => $lang) {
        if (substr_count($domain->locales, $loc)) {
          $lcMessages[$loc] = $lang;
        }
      }
      $this->addSelect('lcMessages', ts('Default Language'), $lcMessages);

      // add language limiter and language adder
      $this->addCheckBox('languageLimit', ts('Available Languages'), array_flip($lcMessages), NULL, NULL, NULL, NULL, ' &nbsp; ');
      $this->addSelect('addLanguage', ts('Add Language'), array_merge(array('' => ts('- select -')), array_diff($locales, $lcMessages)));

      // add the ability to return to single language
      $warning = ts('WARNING: This will make your CiviCRM installation a single-language one again. THIS WILL DELETE ALL DATA RELATED TO LANGUAGES OTHER THAN THE DEFAULT ONE SELECTED ABOVE (and only that language will be preserved).');
      $this->assign('warning', $warning);
      $this->addElement('checkbox', 'makeSinglelingual', ts('Return to Single Language'),
        NULL, array('onChange' => "if (this.checked) alert('$warning')")
      );
    }
    else {
      // for single-lingual sites, populate default language drop-down with all languages
      $this->addSelect('lcMessages', ts('Default Language'), $locales);

      $warning = ts('WARNING: Enabling multiple languages changes the schema of your database, so make sure you know what you are doing when enabling this function; making a database backup is strongly recommended.');
      $this->assign('warning', $warning);

      // test for create view and trigger permissions and if allowed, add the option to go multilingual
      CRM_Core_Error::ignoreException();
      $dao = new CRM_Core_DAO;
      $dao->query('CREATE OR REPLACE VIEW civicrm_domain_view AS SELECT * FROM civicrm_domain');
      $dao->query('CREATE TRIGGER civicrm_domain_trigger BEFORE INSERT ON civicrm_domain FOR EACH ROW BEGIN END');
      $dao->query('DROP TRIGGER IF EXISTS civicrm_domain_trigger');
      $dao->query('DROP VIEW IF EXISTS civicrm_domain_view');
      CRM_Core_Error::setCallback();

      if (!$dao->_lastError and !$config->logging) {
        $this->addElement('checkbox', 'makeMultilingual', ts('Enable Multiple Languages'),
          NULL, array('onChange' => "if (this.checked) alert('$warning')")
        );
      }
    }

    $this->addElement('checkbox', 'inheritLocale', ts('Inherit CMS Language'));
    $this->addElement('text', 'monetaryThousandSeparator', ts('Thousands Separator'), array('size' => 2));
    $this->addElement('text', 'monetaryDecimalPoint', ts('Decimal Delimiter'), array('size' => 2));
    $this->addElement('text', 'moneyformat', ts('Monetary Amount Display'));
    $this->addSelect('moneyvalueformat', ts('Monetary Value Display'), array(
      '%!.0n' => '1,234 :'. ts('Commas for thousands, no decimal places.'),
      '%!i' => '1,234.56 :'.ts('Commas for thousands, two decimal places.'),
    ));

    $country = array();
    CRM_Core_PseudoConstant::populate($country, 'CRM_Core_DAO_Country', TRUE, 'name', 'is_active');
    $i18n->localizeArray($country, array('context' => 'country'));
    asort($country);

    $this->addSelect('countryLimit', ts('Available Countries'), $country, array(
        'multiple' => 'multipl',
    ));


    $this->addSelect('provinceLimit', ts('Available States and Provinces'), $country, array(
        'multiple' => 'multiple',
    ));

    $this->addSelect('defaultContactCountry', ts('Default Country'), array('' => ts('- select -')) + $country);

    // we do this only to initialize currencySymbols, kinda hackish but works!
    $config->defaultCurrencySymbol();

    $symbol = $config->currencySymbols;
    foreach ($symbol as $key => $value) {
      $this->_currencySymbols[$key] = "$key";
      if ($value) {
        $this->_currencySymbols[$key] .= " ($value)";
      }
    }
    $this->addSelect('defaultCurrency', ts('Default Currency'), $this->_currencySymbols);
    $this->addSelect('currencyLimit', ts('Available Currencies'), $this->_currencySymbols, array(
      'multiple' => 'multiple',
    ));

    $this->addElement('text', 'legacyEncoding', ts('Legacy Encoding'));
    $this->addElement('text', 'customTranslateFunction', ts('Custom Translate Function'));
    $this->addElement('text', 'fieldSeparator', ts('Import / Export Field Separator'), array('size' => 2));

    $this->addFormRule(array('CRM_Admin_Form_Setting_Localization', 'formRule'));

    parent::buildQuickForm();
  }

  static function formRule($fields) {
    $errors = array();
    if (CRM_Utils_Array::value('monetaryThousandSeparator', $fields) ==
      CRM_Utils_Array::value('monetaryDecimalPoint', $fields)
    ) {
      $errors['monetaryThousandSeparator'] = ts('Thousands Separator and Decimal Delimiter can not be the same.');
    }

    if (strlen($fields['monetaryThousandSeparator']) > 1) {
      $errors['monetaryThousandSeparator'] = ts('Thousands Separator can not have more than 1 character.');
    }

    if (strlen($fields['monetaryDecimalPoint']) > 1) {
      $errors['monetaryDecimalPoint'] = ts('Decimal Delimiter can not have more than 1 character.');
    }

    if (trim($fields['customTranslateFunction']) &&
      !function_exists(trim($fields['customTranslateFunction']))
    ) {
      $errors['customTranslateFunction'] = ts('Please define the custom translation function first.');
    }
    return empty($errors) ? TRUE : $errors;
  }

  function setDefaultValues() {
    parent::setDefaultValues();

    // CRM-1496
    // retrieve default values for currencyLimit
    $this->_defaults['currencyLimit'] = array_keys(CRM_Core_OptionGroup::values('currencies_enabled'));

    // CRM-5111: unset these two unconditionally, we don’t want them to stick – ever
    unset($this->_defaults['makeMultilingual']);
    unset($this->_defaults['makeSinglelingual']);
    return $this->_defaults;
  }

  public function postProcess() {
    $values = $this->exportValues();

    // FIXME: stupid QF not submitting unchecked checkboxen…
    if (!isset($values['inheritLocale'])) {
      $values['inheritLocale'] = 0;
    }

    //cache contact fields retaining localized titles
    //though we changed localization, so reseting cache.
    require_once 'CRM/Core/BAO/Cache.php';
    CRM_Core_BAO_Cache::deleteGroup('contact fields');

    // we do this only to initialize monetary decimal point and thousand separator
    $config = CRM_Core_Config::singleton();

    // set default Currency Symbol
    $values['defaultCurrencySymbol'] = $config->defaultCurrencySymbol($values['defaultCurrency']);

    // save enabled currencies and defaul currency in option group 'currencies_enabled'
    // CRM-1496
    if (empty($values['currencyLimit'])) {
      $values['currencyLimit'] = $values['defaultCurrency'];
    }
    elseif (!in_array($values['defaultCurrency'],
        $values['currencyLimit']
      )) {
      $values['currencyLimit'][] = $values['defaultCurrency'];
      // sort so that when we display drop down, weights have right value
      sort($values['currencyLimit']);
    }


    // get labels for all the currencies
    $options = array();
    for ($i = 0; $i < count($values['currencyLimit']); $i++) {
      $options[] = array('label' => $this->_currencySymbols[$values['currencyLimit'][$i]],
        'value' => $values['currencyLimit'][$i],
        'weight' => $i + 1,
        'is_active' => 1,
        'is_default' => $values['currencyLimit'][$i] == $values['defaultCurrency'],
      );
    }

    $dontCare = NULL;
    CRM_Core_OptionGroup::createAssoc('currencies_enabled',
      $options,
      $dontCare
    );

    // unset currencyLimit so we dont store there
    unset($values['currencyLimit']);

    // make the site multi-lang if requested
    if (CRM_Utils_Array::value('makeMultilingual', $values)) {
      require_once 'CRM/Core/I18n/Schema.php';
      CRM_Core_I18n_Schema::makeMultilingual($values['lcMessages']);
      $values['languageLimit'][$values['lcMessages']] = 1;
      // make the site single-lang if requested
    }
    elseif (CRM_Utils_Array::value('makeSinglelingual', $values)) {
      require_once 'CRM/Core/I18n/Schema.php';
      CRM_Core_I18n_Schema::makeSinglelingual($values['lcMessages']);
      $values['languageLimit'] = '';
    }

    // add a new db locale if the requested language is not yet supported by the db
    if (!CRM_Utils_Array::value('makeSinglelingual', $values) and CRM_Utils_Array::value('addLanguage', $values)) {
      require_once 'CRM/Core/DAO/Domain.php';
      $domain = new CRM_Core_DAO_Domain();
      $domain->find(TRUE);
      if (!substr_count($domain->locales, $values['addLanguage'])) {
        require_once 'CRM/Core/I18n/Schema.php';
        CRM_Core_I18n_Schema::addLocale($values['addLanguage'], $values['lcMessages']);
      }
      $values['languageLimit'][$values['addLanguage']] = 1;
    }

    // if we manipulated the language list, return to the localization admin screen
    $return = (bool)(CRM_Utils_Array::value('makeMultilingual', $values) or CRM_Utils_Array::value('addLanguage', $values));

    // save all the settings
    parent::commonProcess($values);

    if ($return) {
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/setting/localization', 'reset=1'));
    }
  }
}

