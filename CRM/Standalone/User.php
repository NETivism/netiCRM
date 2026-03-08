<?php
/**
 * A standalone user object.
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 */
class CRM_Standalone_User {

  /**
   * The user's ID.
   * @var int
   */
  public $id;

  /**
   * The user's identity URL.
   * @var string
   */
  public $identity_url;

  /**
   * The user's email address.
   * @var string
   */
  public $email;

  /**
   * The user's first name.
   * @var string
   */
  public $first_name;

  /**
   * The user's last name.
   * @var string
   */
  public $last_name;

  /**
   * The user's full name.
   * @var string
   */
  public $name;

  /**
   * The user's street address.
   * @var string
   */
  public $street_address;

  /**
   * The user's city.
   * @var string
   */
  public $city;

  /**
   * The user's postal code.
   * @var string
   */
  public $postal_code;

  /**
   * The user's state or province.
   * @var string
   */
  public $state_province;

  /**
   * The user's country.
   * @var string
   */
  public $country;

  /**
   * Constructor.
   *
   * @param string $identityUrl
   *   The user's identity URL.
   * @param string $email
   *   The user's email address.
   * @param string $firstName
   *   The user's first name.
   * @param string $lastName
   *   The user's last name.
   * @param string $streetAddr
   *   The user's street address.
   * @param string $city
   *   The user's city.
   * @param string $postalCode
   *   The user's postal code.
   * @param string $stateProvince
   *   The user's state or province.
   * @param string $country
   *   The user's country.
   */
  public function __construct($identityUrl, $email = NULL, $firstName = NULL, $lastName = NULL, $streetAddr = NULL, $city = NULL, $postalCode = NULL, $stateProvince = NULL, $country = NULL) {
    $this->identity_url = $identityUrl;
    $this->email = $email;
    $this->first_name = $firstName;
    $this->last_name = $lastName;
    $this->name = $firstName . ' ' . $lastName;
    $this->street_address = $streetAddr;
    $this->city = $city;
    $this->postal_code = $postalCode;
    $this->state_province = $stateProvince;
    $this->country = $country;
  }
}
