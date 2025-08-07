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



/**
 * This is class to handle address related functions
 */
class CRM_Core_BAO_Address extends CRM_Core_DAO_Address {

  public $_name;
  public $state_name;
  public $state;
  public $country;
  public $world_region;
  /**
   * @var string
   */
  public $display;
  /**
   * @var string
   */
  public $display_text;
  /**
   * Should we overwrite existing address, total hack for now
   * Please do not use this hack in other places, its totally gross
   */
  static $_overwrite = TRUE;

  /**
   * takes an associative array and creates a address
   *
   * @param array  $params (reference ) an assoc array of name/value pairs
   * @param boolean  $fixAddress   true if you need to fix (format) address values
   *                               before inserting in db
   *
   * @return array $blocks array of created address
   * @access public
   * @static
   */
  static function create(&$params, $fixAddress, $entity = NULL) {
    if (!isset($params['address']) ||
      !is_array($params['address'])
    ) {
      return;
    }

    $addresses = [];
    $contactId = NULL;

    $updateBlankLocInfo = CRM_Utils_Array::value('updateBlankLocInfo', $params, FALSE);
    if (!$entity) {
      $contactId = $params['contact_id'];
      //get all the addresses for this contact
      $addresses = self::allAddress($contactId, $updateBlankLocInfo);
    }
    else {
      // get all address from location block
      $entityElements = ['entity_table' => $params['entity_table'],
        'entity_id' => $params['entity_id'],
      ];
      $addresses = self::allEntityAddress($entityElements);
    }

    $isPrimary = $isBilling = TRUE;
    $blocks = [];

    foreach ($params['address'] as $key => $value) {
      if (!is_array($value)) {
        continue;
      }

      $addressExists = self::dataExists($value);

      if ($updateBlankLocInfo) {
        if ((!empty($addresses) || !$addressExists) && CRM_Utils_Array::arrayKeyExists($key, $addresses)) {
          $value['id'] = $addresses[$key];
        }
      }
      else {
        if (!empty($addresses) && CRM_Utils_Array::arrayKeyExists($value['location_type_id'], $addresses)) {
          $value['id'] = $addresses[$value['location_type_id']];
        }
      }

      // Note there could be cases when address info already exist ($value[id] is set) for a contact/entity
      // BUT info is not present at this time, and therefore we should be really careful when deleting the block.
      // $updateBlankLocInfo will help take appropriate decision. CRM-5969
      if (isset($value['id']) && !$addressExists && $updateBlankLocInfo) {
        //delete the existing record
        CRM_Core_BAO_Block::blockDelete('Address', ['id' => $value['id']]);
        continue;
      }
      elseif (!$addressExists) {
        continue;
      }

      if ($isPrimary && $value['is_primary']) {
        $isPrimary = FALSE;
      }
      else {
        $value['is_primary'] = 0;
      }

      if ($isBilling && CRM_Utils_Array::value('is_billing', $value)) {
        $isBilling = FALSE;
      }
      else {
        $value['is_billing'] = 0;
      }
      $value['contact_id'] = $contactId;
      $blocks[] = self::add($value, $fixAddress);
    }

    return $blocks;
  }

  /**
   * takes an associative array and adds phone
   *
   * @param array  $params         (reference ) an assoc array of name/value pairs
   * @param boolean  $fixAddress   true if you need to fix (format) address values
   *                               before inserting in db
   *
   * @return object       CRM_Core_BAO_Address object on success, null otherwise
   * @access public
   * @static
   */
  static function add(&$params, $fixAddress) {
    static $customFields = NULL;
    $address = new CRM_Core_DAO_Address();

    // fixAddress mode to be done
    if ($fixAddress) {
      CRM_Core_BAO_Address::fixAddress($params);
    }

    $address->copyValues($params);

    $address->save();

    if ($address->id) {
      if (!$customFields) {


        $customFields = CRM_Core_BAO_CustomField::getFields('Address', FALSE, TRUE);
      }
      if (!empty($customFields)) {
        $addressCustom = CRM_Core_BAO_CustomField::postProcess($params,
          $customFields,
          $address->id,
          'Address',
          TRUE
        );
      }
      if (!empty($addressCustom)) {
        CRM_Core_BAO_CustomValueTable::store($addressCustom, 'civicrm_address', $address->id);
      }

      //call the function to sync shared address
      self::processSharedAddress($address->id, $params);

      // call the function to create shared relationships
      // we only create create relationship if address is shared by Individual
      if ($address->master_id != 'null') {
        self::processSharedAddressRelationship($address->master_id, $params);
      }
    }

    return $address;
  }

  /**
   * format the address params to have reasonable values
   *
   * @param array  $params         (reference ) an assoc array of name/value pairs
   *
   * @return void
   * @access public
   * @static
   */
  static function fixAddress(&$params) {
    if (CRM_Utils_Array::value('billing_street_address', $params)) {
      //Check address is comming from online contribution / registration page
      //Fixed :CRM-5076
      $billing = ['street_address' => 'billing_street_address',
        'city' => 'billing_city',
        'postal_code' => 'billing_postal_code',
        'state_province' => 'billing_state_province',
        'state_province_id' => 'billing_state_province_id',
        'country' => 'billing_country',
        'country_id' => 'billing_country_id',
      ];

      foreach ($billing as $key => $val) {
        if ($value = CRM_Utils_Array::value($val, $params)) {
          if (CRM_Utils_Array::value($key, $params)) {
            unset($params[$val]);
          }
          else {
            //add new key and removed old
            $params[$key] = $value;
            unset($params[$val]);
          }
        }
      }
    }

    /* Split the zip and +4, if it's in US format */

    if (CRM_Utils_Array::value('postal_code', $params) &&
      preg_match('/^(\d{4,5})[+-](\d{4})$/',
        $params['postal_code'],
        $match
      )
    ) {
      $params['postal_code'] = $match[1];
      $params['postal_code_suffix'] = $match[2];
    }

    // add country id if not set
    if ((!isset($params['country_id']) || !is_numeric($params['country_id'])) &&
      isset($params['country'])
    ) {
      $country = new CRM_Core_DAO_Country();
      $country->name = $params['country'];
      if (!$country->find(TRUE)) {
        $country->name = NULL;
        $country->iso_code = $params['country'];
        $country->find(TRUE);
      }
      $params['country_id'] = $country->id;
    }

    // add state_id if state is set
    if ((!isset($params['state_province_id']) || !is_numeric($params['state_province_id']))
      && (isset($params['state_province']) || isset($params['state_province_name']))
    ) {
      $stateProvince = !empty($params['state_province_name']) ? trim($params['state_province_name']) : trim($params['state_province']);
      $availableStateProvince = CRM_Core_PseudoConstant::stateProvince();
      if ($stateProvinceId = array_search($stateProvince, $availableStateProvince)) {
        $params['state_province_id'] = $stateProvinceId;
      }
      elseif (!empty($stateProvince)) {
        $stateProvinceDao = new CRM_Core_DAO_StateProvince();
        $stateProvinceDao->name = $stateProvince;

        // add country id if present
        if (isset($params['country_id'])) {
          $stateProvinceDao->country_id = $params['country_id'];
        }

        if (!$stateProvinceDao->find(TRUE)) {
          $stateProvinceDao->name = NULL;
          $stateProvinceDao->abbreviation = $stateProvince;
          $stateProvinceDao->find(TRUE);
        }
        if ($stateProvinceDao->id) {
          $params['state_province_id'] = $stateProvinceDao->id;
        }
        else {
          $params['state_province_id'] = 'null';
        }
      }
      else {
        $params['state_province_id'] = 'null';
      }
    }


    // currently copy values populates empty fields with the string "null"
    // and hence need to check for the string null
    if (isset($params['state_province_id']) &&
      is_numeric($params['state_province_id']) &&
      (!isset($params['country_id']) || empty($params['country_id']))
    ) {
      // since state id present and country id not present, hence lets populate it
      // jira issue http://issues.civicrm.org/jira/browse/CRM-56
      $params['country_id'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_StateProvince',
        $params['state_province_id'],
        'country_id'
      );
    }

    //special check to ignore non numeric values if they are not
    //detected by formRule(sometimes happens due to internet latency), also allow user to unselect state/country
    if (isset($params['state_province_id'])) {
      if (!trim($params['state_province_id'])) {
        $params['state_province_id'] = 'null';
      }
      elseif (!is_numeric($params['state_province_id']) ||
        ((int ) $params['state_province_id'] < 1000)
      ) {
        // CRM-3393 ( the hacky 1000 check)
        $params['state_province_id'] = 'null';
      }
    }

    if (isset($params['country_id'])) {
      if (!trim($params['country_id'])) {
        $params['country_id'] = 'null';
      }
      elseif (!is_numeric($params['country_id']) ||
        ((int ) $params['country_id'] < 1000)
      ) {
        // CRM-3393 ( the hacky 1000 check)
        $params['country_id'] = 'null';
      }
    }

    // add state and country names from the ids
    if (isset($params['state_province_id']) && is_numeric($params['state_province_id'])) {
      $params['state_province'] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($params['state_province_id']);
    }

    if (isset($params['country_id']) && is_numeric($params['country_id'])) {
      $params['country'] = CRM_Core_PseudoConstant::country($params['country_id']);
    }

    $config = CRM_Core_Config::singleton();


    $asp = CRM_Core_BAO_Preferences::value('address_standardization_provider');
    // clean up the address via USPS web services if enabled
    if ($asp === 'USPS') {

      CRM_Utils_Address_USPS::checkAddress($params);
    }

    // add latitude and longitude and format address if needed
    if (!empty($config->geocodeMethod)) {
      $className = $config->geocodeMethod;
      $className::format( $params );
    }
  }

  /**
   * Check if there is data to create the object
   *
   * @param array  $params    (reference ) an assoc array of name/value pairs
   *
   * @return boolean
   *
   * @access public
   * @static
   */
  static function dataExists(&$params) {
    //check if location type is set if not return false
    if (!isset($params['location_type_id'])) {
      return FALSE;
    }

    $config = CRM_Core_Config::singleton();
    foreach ($params as $name => $value) {
      if (in_array($name, ['is_primary', 'location_type_id', 'id', 'contact_id', 'is_billing', 'display', 'master_id'])) {
        continue;
      }
      elseif (!CRM_Utils_System::isNull($value)) {
        // name could be country or country id
        if (substr($name, 0, 7) == 'country') {
          // make sure its different from the default country
          // iso code
          $defaultCountry = &$config->defaultContactCountry();
          // full name
          $defaultCountryName = &$config->defaultContactCountryName();

          if ($defaultCountry) {
            if ($value == $defaultCountry ||
              $value == $defaultCountryName ||
              $value == $config->defaultContactCountry
            ) {
              return TRUE;
            }
            else {
              return TRUE;
            }
          }
          else {
            // return if null default
            return TRUE;
          }
        }
        else {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array   $entityBlock   associated array of fields
   * @param boolean $microformat   if microformat output is required
   * @param int     $fieldName     conditional field name
   *
   * @return array  $addresses     array with address fields
   * @access public
   * @static
   */
  static function &getValues(&$entityBlock, $microformat = FALSE, $fieldName = 'contact_id') {
    if (empty($entityBlock)) {
      return NULL;
    }
    $addresses = [];
    $address = new CRM_Core_BAO_Address();

    if (!CRM_Utils_Array::value('entity_table', $entityBlock)) {
      $address->$fieldName = CRM_Utils_Array::value($fieldName, $entityBlock);
    }
    else {
      $addressIds = [];
      $addressIds = self::allEntityAddress($entityBlock);

      if (!empty($addressIds[1])) {
        $address->id = $addressIds[1];
      }
      else {
        return $addresses;
      }
    }
    //get primary address as a first block.
    $address->orderBy('is_primary desc, id');

    $address->find();

    $count = 1;
    while ($address->fetch()) {
      // deprecate reference.
      if ($count > 1) {
        foreach (['state', 'state_name', 'country', 'world_region'] as $fld) {
          if (isset($address->$fld))unset($address->$fld);
        }
      }
      $stree = $address->street_address;
      $values = [];
      CRM_Core_DAO::storeValues($address, $values);

      // add state and country information: CRM-369
      if (!empty($address->state_province_id)) {
        $address->state = CRM_Core_PseudoConstant::stateProvinceAbbreviation($address->state_province_id);
        $address->state_name = CRM_Core_PseudoConstant::stateProvince($address->state_province_id);
        $values['state_province_name'] = $address->state_name;
        $values['state_province'] = $address->state;
        $address->state = CRM_Core_PseudoConstant::stateProvinceAbbreviation($address->state_province_id, FALSE);
        $address->state_name = CRM_Core_PseudoConstant::stateProvince($address->state_province_id, FALSE);
      }

      if (!empty($address->country_id)) {
        $address->country = CRM_Core_PseudoConstant::country($address->country_id);
        $values['country'] = $address->country;

        //get world region
        $regionId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Country', $address->country_id, 'region_id');

        $address->world_region = CRM_Core_PseudoConstant::worldregion($regionId);
        $values['world_region'] = $address->world_region;
      }

      $address->addDisplay($microformat);

      $values['display'] = $address->display;
      $values['display_text'] = $address->display_text;

      if (is_numeric($address->master_id)) {
        $values['use_shared_address'] = 1;
      }

      $addresses[$count] = $values;

      //unset is_primary after first block. Due to some bug in earlier version
      //there might be more than one primary blocks, hence unset is_primary other than first
      if ($count > 1) {
        unset($addresses[$count]['is_primary']);
      }

      $count++;
    }

    return $addresses;
  }

  /**
   * Add the formatted address to $this-> display
   *
   * @param NULL
   *
   * @return void
   *
   * @access public
   *
   */
  function addDisplay($microformat = FALSE) {

    $fields = [
      // added this for CRM 1200
      'address_id' => $this->id,
      // CRM-4003
      'address_name' => !empty($this->_name) ? str_replace('', ' ', $this->name) : '',
      'street_address' => $this->street_address,
      'supplemental_address_1' => $this->supplemental_address_1,
      'supplemental_address_2' => $this->supplemental_address_2,
      'city' => $this->city,
      'state_province_name' => $this->state_name ?? "",
      'state_province' => $this->state ?? "",
      'postal_code' => $this->postal_code ?? "",
      'postal_code_suffix' => $this->postal_code_suffix ?? "",
      'country' => $this->country ?? "",
      'world_region' => $this->world_region ?? "",
    ];

    if (isset($this->county_id) && $this->county_id) {
      $fields['county'] = CRM_Core_PseudoConstant::county($this->county_id);
    }
    else {
      $fields['county'] = NULL;
    }

    $this->display = CRM_Utils_Address::format($fields, NULL, $microformat);
    $this->display_text = CRM_Utils_Address::format($fields);
  }

  /**
   *
   *
   *
   */
  static function setOverwrite($overwrite) {
    self::$_overwrite = $overwrite;
  }

  /**
   * Get all the addresses for a specified contact_id, with the primary address being first
   *
   * @param int $id the contact id
   *
   * @return array  the array of adrress data
   * @access public
   * @static
   */
  static function allAddress($id, $updateBlankLocInfo = FALSE) {
    if (!$id) {
      return NULL;
    }

    $query = "
SELECT civicrm_address.id as address_id, civicrm_address.location_type_id as location_type_id
FROM civicrm_contact, civicrm_address 
WHERE civicrm_address.contact_id = civicrm_contact.id AND civicrm_contact.id = %1
ORDER BY civicrm_address.is_primary DESC, address_id ASC";
    $params = [1 => [$id, 'Integer']];

    $addresses = [];
    $dao = &CRM_Core_DAO::executeQuery($query, $params);
    $count = 1;
    while ($dao->fetch()) {
      if ($updateBlankLocInfo) {
        $addresses[$count++] = $dao->address_id;
      }
      else {
        $addresses[$dao->location_type_id] = $dao->address_id;
      }
    }
    return $addresses;
  }

  /**
   * Get all the addresses for a specified location_block id, with the primary address being first
   *
   * @param array $entityElements the array containing entity_id and
   * entity_table name
   *
   * @return array  the array of adrress data
   * @access public
   * @static
   */
  static function allEntityAddress(&$entityElements) {
    if (empty($entityElements)) {
      return $addresses;
    }

    $entityId = $entityElements['entity_id'];
    $entityTable = $entityElements['entity_table'];

    $sql = "
SELECT civicrm_address.id as address_id    
FROM civicrm_loc_block loc, civicrm_location_type ltype, civicrm_address, {$entityTable} ev
WHERE ev.id = %1
  AND loc.id = ev.loc_block_id
  AND civicrm_address.id IN (loc.address_id, loc.address_2_id)
  AND ltype.id = civicrm_address.location_type_id
ORDER BY civicrm_address.is_primary DESC, civicrm_address.location_type_id DESC, address_id ASC ";

    $params = [1 => [$entityId, 'Integer']];
    $addresses = [];
    $dao = &CRM_Core_DAO::executeQuery($sql, $params);
    $locationCount = 1;
    while ($dao->fetch()) {
      $addresses[$locationCount] = $dao->address_id;
      $locationCount++;
    }
    return $addresses;
  }

  static function addStateCountryMap(&$stateCountryMap,
    $defaults = NULL
  ) {
    // first fix the statecountry map if needed
    if (empty($stateCountryMap)) {
      return;
    }

    $config = CRM_Core_Config::singleton();
    if (!isset($config->stateCountryMap)) {
      $config->stateCountryMap = [];
    }

    $config->stateCountryMap = array_merge($config->stateCountryMap,
      $stateCountryMap
    );
  }

  static function fixAllStateSelects(&$form, &$defaults) {
    $config = CRM_Core_Config::singleton();

    if (!empty($config->stateCountryMap)) {
      foreach ($config->stateCountryMap as $index => $match) {
        if (CRM_Utils_Array::arrayKeyExists('state_province', $match) &&
          CRM_Utils_Array::arrayKeyExists('country', $match)
        ) {

          CRM_Contact_Form_Edit_Address::fixStateSelect($form,
            $match['country'],
            $match['state_province'],
            CRM_Utils_Array::value($match['country'],
              $defaults
            )
          );
        }
        else {
          unset($config->stateCountryMap[$index]);
        }
      }
    }
  }

  /* Function to get address sequence
     *
     * @return  array of address sequence.
     */

  static function addressSequence() {
    $config = CRM_Core_Config::singleton();
    $addressSequence = $config->addressSequence();

    $countryState = $cityPostal = FALSE;
    foreach ($addressSequence as $key => $field) {
      if (in_array($field, ['country', 'state_province']) && !$countryState) {
        $countryState = TRUE;
        $addressSequence[$key] = 'country_state_province';
      }
      elseif (in_array($field, ['city', 'postal_code']) && !$cityPostal) {
        $cityPostal = TRUE;
        $addressSequence[$key] = 'city_postal_code';
      }
      elseif (in_array($field, ['country', 'state_province', 'city', 'postal_code'])) {
        unset($addressSequence[$key]);
      }
    }

    return $addressSequence;
  }

  /**
   * Parse given street address string in to street_name,
   * street_unit, street_number and street_number_suffix
   * eg "54A Excelsior Ave. Apt 1C", or "917 1/2 Elm Street"
   *
   * NB: civic street formats for en_CA and fr_CA used by default if those locales are active
   *     otherwise en_US format is default action
   *
   * @param  string   Street address including number and apt
   * @param  string   Locale - to set locale used to parse address
   *
   * @return array    $parseFields    parsed fields values.
   * @access public
   * @static
   */
  static function parseStreetAddress($streetAddress, $locale = NULL) {
    $config = CRM_Core_Config::singleton();

    /* locales supported include:
    	 *  en_US - http://pe.usps.com/cpim/ftp/pubs/pub28/pub28.pdf
    	 *  en_CA - http://www.canadapost.ca/tools/pg/manual/PGaddress-e.asp
    	 *  fr_CA - http://www.canadapost.ca/tools/pg/manual/PGaddress-f.asp
    	 *          NB: common use of comma after street number also supported
    	 *  default is en_US
         */


    $supportedLocalesForParsing = ['en_US', 'en_CA', 'fr_CA'];
    if (!$locale) {
      $locale = $config->lcMessages;
    }
    // as different locale explicitly requested but is not available, display warning message and set $locale = 'en_US'
    if (!in_array($locale, $supportedLocalesForParsing)) {
      CRM_Core_Session::setStatus(ts('Unsupported locale specified to parseStreetAddress: %1. Proceeding with en_US locale.', [1 => $locale]));
      $locale = 'en_US';
    }
    $parseFields = ['street_name' => '',
      'street_unit' => '',
      'street_number' => '',
      'street_number_suffix' => '',
    ];

    if (empty($streetAddress)) {
      return $parseFields;
    }

    $streetAddress = trim($streetAddress);

    $matches = [];
    if (in_array($locale, ['en_CA', 'fr_CA']) && preg_match('/^([A-Za-z0-9]+)[ ]*\-[ ]*/', $streetAddress, $matches)) {
      $parseFields['street_unit'] = $matches[1];
      // unset from rest of street address
      $streetAddress = preg_replace('/^([A-Za-z0-9]+)[ ]*\-[ ]*/', '', $streetAddress);
    }

    // get street number and suffix.
    $matches = [];
    if (preg_match('/^[A-Za-z0-9]+([\W]+)/', $streetAddress, $matches)) {
      $steetNumAndSuffix = $matches[0];

      // get street number.
      $matches = [];
      if (preg_match('/^(\d+)/', $steetNumAndSuffix, $matches)) {
        $parseFields['street_number'] = $matches[0];
        $suffix = preg_replace('/^(\d+)/', '', $steetNumAndSuffix);
        $suffix = trim($suffix);
        $matches = [];
        if (preg_match('/^[A-Za-z0-9]+/', $suffix, $matches)) {
          $parseFields['street_number_suffix'] = $matches[0];
        }
      }

      // unset from main street address.
      $streetAddress = preg_replace('/^[A-Za-z0-9]+([\W]+)/', '', $streetAddress);
      $streetAddress = trim($streetAddress);
    }
    elseif (preg_match('/^(\d+)/', $streetAddress, $matches)) {
      $parseFields['street_number'] = $matches[0];
      // unset from main street address.
      $streetAddress = preg_replace('/^(\d+)/', '', $streetAddress);
      $streetAddress = trim($streetAddress);
    }

    // suffix might be like 1/2
    $matches = [];
    if (preg_match('/^\d\/\d/', $streetAddress, $matches)) {
      $parseFields['street_number_suffix'] .= $matches[0];

      // unset from main street address.
      $streetAddress = preg_replace('/^\d+\/\d+/', '', $streetAddress);
      $streetAddress = trim($streetAddress);
    }

    // now get the street unit.
    // supportable street unit formats.
    $streetUnitFormats = ['APT', 'APARTMENT', 'BSMT', 'BASEMENT', 'BLDG', 'BUILDING',
      'DEPT', 'DEPARTMENT', 'FL', 'FLOOR', 'FRNT', 'FRONT',
      'HNGR', 'HANGER', 'LBBY', 'LOBBY', 'LOWR', 'LOWER',
      'OFC', 'OFFICE', 'PH', 'PENTHOUSE', 'TRLR', 'TRAILER',
      'UPPR', 'RM', 'ROOM', 'SIDE', 'SLIP', 'KEY',
      'LOT', 'PIER', 'REAR', 'SPC', 'SPACE',
      'STOP', 'STE', 'SUITE', 'UNIT', '#', 'ST',
    ];

    // overwriting $streetUnitFormats for 'en_CA' and 'fr_CA' locale
    if (in_array($locale, ['en_CA', 'fr_CA'])) {
      $streetUnitFormats = ['APT', 'APP', 'SUITE', 'BUREAU', 'UNIT'];
    }

    $streetUnitPreg = '/(' . CRM_Utils_Array::implode('\s|\s', $streetUnitFormats) . ')(.+)?/i';
    $matches = [];
    if (preg_match($streetUnitPreg, $streetAddress, $matches)) {
      $parseFields['street_unit'] = $matches[0];
      $streetAddress = str_replace($matches[0], '', $streetAddress);
      $streetAddress = trim($streetAddress);
    }

    // consider remaining string as street name.
    $parseFields['street_name'] = $streetAddress;

    return $parseFields;
  }

  /**
   * Validate the address fields based on the address options enabled
   * in the Address Settings
   *
   * @param  array   $fields an array of importable/exportable contact fields
   *
   * @return array   $fields an array of contact fields and only the enabled address options
   * @access public
   * @static
   */
  static function validateAddressOptions($fields) {
    static $addressOptions = NULL;
    if (!$addressOptions) {

      $addressOptions = CRM_Core_BAO_Preferences::valueOptions('address_options', TRUE, NULL, TRUE);
    }

    if (is_array($fields) && !empty($fields)) {
      foreach ($addressOptions as $key => $value) {
        if (!$value && isset($fields[$key])) {
          unset($fields[$key]);
        }
      }
    }
    return $fields;
  }

  /**
   * Check if current address is used by any other contacts
   *
   * @param int $addressId address id
   *
   * @return count of contacts that use this shared address
   * @access public
   * @static
   */
  static function checkContactSharedAddress($addressId) {
    $query = 'SELECT count(id) FROM civicrm_address WHERE master_id = %1';
    return CRM_Core_DAO::singleValueQuery($query, [1 => [$addressId, 'Integer']]);
  }

  /**
   * Function to update the shared addresses if master address is modified
   *
   * @param int    $addressId address id
   * @param array  $params    associated array of address params
   *
   * @return void
   * @access public
   * @static
   */
  static function processSharedAddress($addressId, $params) {
    $query = 'SELECT id FROM civicrm_address WHERE master_id = %1';
    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$addressId, 'Integer']]);

    // unset contact id
    $skipFields = ['is_primary', 'location_type_id', 'is_billing', 'master_id', 'contact_id'];
    foreach ($skipFields as $value) {
      unset($params[$value]);
    }

    $addressDAO = new CRM_Core_DAO_Address();
    while ($dao->fetch()) {
      $addressDAO->copyValues($params);
      $addressDAO->id = $dao->id;
      $addressDAO->save();
      $addressDAO->free();
    }
  }

  /**
   * Function to create relationship between contacts who share an address
   *
   * Note that currently we create relationship only for Individual contacts
   * Individual + Household and Individual + Orgnization
   *
   * @param int    $masterAddressId master address id
   * @param array  $params          associated array of submitted values
   *
   * @return void
   * @access public
   * @static
   */
  static function processSharedAddressRelationship($masterAddressId, $params) {
    if (!$masterAddressId) {
      return;
    }

    // get the contact type of contact being edited / created
    $currentContactType = CRM_Contact_BAO_Contact::getContactType($params['contact_id']);
    $currentContactId = $params['contact_id'];

    // if current contact is not of type individual return
    if ($currentContactType != 'Individual') {
      return;
    }

    // get the contact id and contact type of shared contact
    // check the contact type of shared contact, return if it is of type Individual

    $query = 'SELECT cc.id, cc.contact_type 
                 FROM civicrm_contact cc INNER JOIN civicrm_address ca ON cc.id = ca.contact_id
                 WHERE ca.id = %1';

    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$masterAddressId, 'Integer']]);

    $dao->fetch();

    // if current contact is not of type individual return, since we don't create relationship between
    // 2 individuals
    if ($dao->contact_type == 'Individual') {
      return;
    }
    $sharedContactType = $dao->contact_type;
    $sharedContactId = $dao->id;

    // create relationship between ontacts who share an address
    if ($sharedContactType == 'Organization') {

      return CRM_Contact_BAO_Contact_Utils::createCurrentEmployerRelationship($currentContactId, $sharedContactId);
    }
    else {
      // get the relationship type id of "Household Member of"
      $relationshipType = 'Household Member of';
    }

    $cid = ['contact' => $currentContactId];

    $relTypeId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', $relationshipType, 'id', 'name_a_b');

    if (!$relTypeId) {
       return CRM_Core_Error::statusBounce(ts("You seem to have deleted the relationship type '%1'", [1 => $relationshipType]));
    }

    // create relationship
    $relationshipParams = ['is_active' => TRUE,
      'relationship_type_id' => $relTypeId . '_a_b',
      'contact_check' => [$sharedContactId => TRUE],
    ];


    list($valid, $invalid, $duplicate,
      $saved, $relationshipIds
    ) = CRM_Contact_BAO_Relationship::create($relationshipParams, $cid);
  }

  /**
   * Function to check and set the status for shared address delete
   *
   * @param int     $addressId address id
   * @param int     $contactId contact id
   * @param boolean $returnStatus by default false
   *
   * @return string $statusMessage
   * @access public
   * @static
   */
  static function setSharedAddressDeleteStatus($addressId = NULL, $contactId = NULL, $returnStatus = FALSE) {
    // check if address that is being deleted has any shared
    if ($addressId) {
      $entityId = $addressId;
      $query = 'SELECT cc.id, cc.display_name 
                 FROM civicrm_contact cc INNER JOIN civicrm_address ca ON cc.id = ca.contact_id
                 WHERE ca.master_id = %1';
    }
    else {
      $entityId = $contactId;
      $query = 'SELECT cc.id, cc.display_name 
                FROM civicrm_address ca1 
                    INNER JOIN civicrm_address ca2 ON ca1.id = ca2.master_id
                    INNER JOIN civicrm_contact cc  ON ca2.contact_id = cc.id 
                WHERE ca1.contact_id = %1';
    }

    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$entityId, 'Integer']]);

    $deleteStatus = [];
    $sharedContactList = [];
    $statusMessage = NULL;
    $addressCount = 0;
    while ($dao->fetch()) {
      if (empty($deleteStatus)) {
        $deleteStatus[] = ts('The following contact(s) have address records which were shared with the address you removed from this contact. These address records are no longer shared - but they have not been removed or altered.');
      }

      $contactViewUrl = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$dao->id}");
      $sharedContactList[] = "<a href='{$contactViewUrl}'>{$dao->display_name}</a>";
      $deleteStatus[] = "<a href='{$contactViewUrl}'>{$dao->display_name}</a>";

      $addressCount++;
    }

    if (!empty($deleteStatus)) {
      $statusMessage = CRM_Utils_Array::implode('<br/>', $deleteStatus) . '<br/>';
    }

    if (!$returnStatus) {
      CRM_Core_Session::setStatus($statusMessage);
    }
    else {
      return ['contactList' => $sharedContactList,
        'count' => $addressCount,
      ];
    }
  }

  /**
   * @addresses array from CRM_BAO_Address::getValues()
   *
   * @type is_primary or is_billing
   *
   * @return return if type exists, or return first address
   */
  static function getAddressByDefault($addresses, $type) {
    $locationTypes = CRM_Core_PseudoConstant::locationType(FALSE, 'name');
    $billingLocationTypeId = array_search('Billing', $locationTypes);
    $billingLocationAddr = NULL;
    if (is_array($addresses)) {
      foreach($addresses as $addr) {
        if ($addr['location_type_id'] == $billingLocationTypeId) {
          $billingLocationAddr = $addr;
        }
        if (isset($addr[$type]) && $addr[$type]) {
          return $addr;
        }
      }
      if ($type == 'is_billing' && $billingLocationAddr) {
        return $billingLocationAddr;
      }
      else {
        return reset($addresses);
      }
    }
    return [];
  }

  /**
   * Get current exists id from value
   *
   * Only effect when id not provided. Id will be added into params.
   * 
   * @param array $params referenced array to be add exists id
   * @return void
   */
  static function valueExists(&$params) {
    if (empty($params['id']) && !empty($params['contact_id'])) {
      $values = [];
      $checkFields = [
        'postal_code',
        'state_province_id',
        'city',
        'street_address',
        'name',
        'contact_id',
      ];
      foreach($checkFields as $key) {
        if(!empty($params[$key])) {
          $values[$key] = $params[$key];
        }
      }
      if (count($values) > 2 || !empty($values['street_address'])) {
        $dao = new CRM_Core_BAO_Address;
        $dao->copyValues($values);
        if ($dao->find(TRUE)) {
          $params['id'] = $dao->id;
        }
      }
    }
  }
}