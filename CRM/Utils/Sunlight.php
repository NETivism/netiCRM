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
 * Utility class for interacting with the Sunlight Labs API.
 *
 * Provides methods to look up U.S. congressional representatives and
 * senators by city, state, or zipcode using the Sunlight Labs web service.
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 */

class CRM_Utils_Sunlight {
  public static $_apiURL = 'http://api.sunlightlabs.com/';
  public static $_apiKey = NULL;

  /**
   * Make an API call to the Sunlight Labs API.
   *
   * @param string $uri
   *   The URI path to append to the API base URL.
   *
   * @return \SimpleXMLElement|false
   *   The parsed XML response, or FALSE on parse failure.
   */
  public static function makeAPICall($uri) {

    $params = ['method' => HTTP_REQUEST_METHOD_GET,
      'allowRedirects' => FALSE,
    ];

    $request = new HTTP_Request(self::$_apiURL . $uri, $params);
    $result = $request->sendRequest();
    if (PEAR::isError($result)) {
      CRM_Core_Error::fatal($result->getMessage());
    }
    if ($request->getResponseCode() != 200) {
      CRM_Core_Error::fatal(ts(
        'Invalid response code received from Sunlight servers: %1',
        [1 => $request->getResponseCode()]
      ));
    }
    $string = $request->getResponseBody();
    return simplexml_load_string($string);
  }

  /**
   * Get city and state for a given zipcode.
   *
   * @param string $zipcode
   *   The zipcode to look up.
   *
   * @return array
   *   A two-element array containing [city, state] as SimpleXMLElement values.
   */
  public static function getCityState($zipcode) {
    $key = self::$_apiKey;
    $uri = "places.getCityStateFromZip.php?zip={$zipcode}&apikey={$key}&output=xml";
    $xml = self::makeAPICall($uri);

    return [$xml->city, $xml->state];
  }

  /**
   * Get detailed information for a given person ID.
   *
   * @param string|\SimpleXMLElement $peopleID
   *   The Sunlight person ID.
   *
   * @return array<string, string>
   *   Associative array of person details with keys: title, first_name,
   *   last_name, gender, party, address, phone, email, url, image_url,
   *   contact_url.
   */
  public static function getDetailedInfo($peopleID) {
    $key = self::$_apiKey;
    $uri = "people.getPersonInfo.php?id={$peopleID}&apikey={$key}&output=xml";
    $xml = self::makeAPICall($uri);

    $result = [];
    $fields = ['title' => 'title',
      'firstname' => 'first_name',
      'lastname' => 'last_name',
      'gender' => 'gender',
      'party' => 'party',
      'congress_office' => 'address',
      'phone' => 'phone',
      'email' => 'email',
      'congresspedia' => 'url',
      'photo' => 'image_url',
      'webform' => 'contact_url',
    ];

    foreach ($fields as $old => $new) {
      $result[$new] = (string ) $xml->$old;
    }

    $result['image_url'] = 'http://sunlightlabs.com/widgets/popuppoliticians/resources/images/' . $result['image_url'];

    return $result;
  }

  /**
   * Get people information from a given API URI.
   *
   * @param string $uri
   *   The API URI to call.
   *
   * @return array<int, array<string, string>>
   *   Array of person detail arrays as returned by getDetailedInfo().
   */
  public static function getPeopleInfo($uri) {
    $xml = self::makeAPICall($uri);

    $result = [];
    foreach ($xml->entity_id_list->entity_id as $key => $value) {
      $result[] = self::getDetailedInfo($value);
    }
    return $result;
  }

  /**
   * Get representative information for a given city and state.
   *
   * @param string $city
   *   The city name.
   * @param string $state
   *   The two-letter state abbreviation.
   *
   * @return array<int, array<string, string>>|null
   *   Array of representative details, or NULL if city or state is empty.
   */
  public static function getRepresentativeInfo($city, $state) {
    if (!$city ||
      !$state
    ) {
      return NULL;
    }
    $key = self::$_apiKey;
    $city = urlencode($city);
    $uri = "people.reps.getRepsFromCityState.php?city={$city}&state={$state}&apikey={$key}&output=xml";
    return self::getPeopleInfo($uri);
  }

  /**
   * Get senator information for a given state.
   *
   * @param string $state
   *   The two-letter state abbreviation.
   *
   * @return array<int, array<string, string>>|null
   *   Array of senator details, or NULL if state is empty.
   */
  public static function getSenatorInfo($state) {
    if (!$state) {
      return NULL;
    }

    $key = self::$_apiKey;
    $uri = "people.sens.getSensFromState.php?state={$state}&apikey={$key}&output=xml";
    return self::getPeopleInfo($uri);
  }

  /**
   * Get combined representative and senator information for a location.
   *
   * If a zipcode is provided, city and state are resolved from it
   * via the Sunlight API, overriding the passed-in values.
   *
   * @param string $city
   *   The city name.
   * @param string $state
   *   The two-letter state abbreviation.
   * @param string|null $zipcode
   *   Optional zipcode to resolve city and state from.
   *
   * @return array<int, array<string, string>>
   *   Array of combined representative and senator details.
   */
  public static function getInfo($city, $state, $zipcode = NULL) {
    if ($zipcode) {
      list($city, $state) = self::getCityState($zipcode);
    }

    $reps = self::getRepresentativeInfo($city, $state);
    $sens = self::getSenatorInfo($state);

    $result = [];
    if (is_array($reps)) {
      $result = array_merge($result, $reps);
    }
    if (is_array($sens)) {
      $result = array_merge($result, $sens);
    }

    return $result;
  }
}
