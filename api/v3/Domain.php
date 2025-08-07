<?php
// $Id$

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * File for the CiviCRM APIv3 domain functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Domain
 *
 * @copyright CiviCRM LLC (c) 2004-2012
 * @version $Id: Domain.php 30171 2010-10-14 09:11:27Z mover $
 *
 */

/**
 * Get CiviCRM domain details
 * {@getfields domain_create}
 * @example DomainGet.php
 */
function civicrm_api3_domain_get($params) {

  $params['version'] = CRM_Utils_Array::value('domain_version', $params);
  unset($params['version']);

  $bao = new CRM_Core_BAO_Domain();
  if (CRM_Utils_Array::value('current_domain', $params)) {
    $domainBAO = CRM_Core_Config::domainID();
    $params['id'] = $domainBAO;
  }
  
  _civicrm_api3_dao_set_filter($bao, $params, true, 'domain');
  $domains = _civicrm_api3_dao_to_array($bao, $params, true,'domain');

  foreach ($domains as $domain) {
    $values = [];
    $locparams = [
      'entity_id'    => $domain['id'],
      'entity_table' => 'civicrm_domain'
    ];
    require_once 'CRM/Core/BAO/Location.php';
    $values['location'] = CRM_Core_BAO_Location::getValues($locparams, TRUE);

    $address_array = [
      'street_address', 'supplemental_address_1', 'supplemental_address_2',
      'city', 'state_province_id', 'postal_code', 'country_id',
      'geo_code_1', 'geo_code_2',
    ];
    
    require_once 'CRM/Core/OptionGroup.php';
    
    if ( !empty( $values['location']['email'] ) ) {
    $domain['domain_email'] = CRM_Utils_Array::value('email', $values['location']['email'][1]);
    }

    if ( !empty( $values['location']['phone'] ) ) {
    $domain['domain_phone'] = [
        'phone_type' => CRM_Core_OptionGroup::getLabel(
          'phone_type',
          CRM_Utils_Array::value(
            'phone_type_id',
          $values['location']['phone'][1]
        )
      ),
        'phone' => CRM_Utils_Array::value(
          'phone',
        $values['location']['phone'][1]
        )
    ];
    }

    if ( !empty( $values['location']['address'] ) ) {
    foreach ($address_array as $value) {
      $domain['domain_address'][$value] = CRM_Utils_Array::value($value,
        $values['location']['address'][1]
      );
    }
    }

    list($domain['from_name'],
      $domain['from_email']
    ) = CRM_Core_BAO_Domain::getNameAndEmail(TRUE);
    $domains[$domain['id']] = array_merge($domains[$domain['id']], $domain);
  }


  return civicrm_api3_create_success($domains, $params, 'domain', 'get', $bao);
}
/*
 * Adjust Metadata for Get action
 * 
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_domain_get_spec(&$params) {
  $params['current_domain'] = ['title' => "get loaded domain"];
}

/**
 * Create a new domain
 *
 * @param array $params
 *
 * @return array
 * @example DomainCreate.php
 * {@getfields domain_create}
 */
function civicrm_api3_domain_create($params) {

  require_once 'CRM/Core/BAO/Domain.php';

  civicrm_api3_verify_mandatory($params, 'CRM_Core_BAO_Domain');
  $params['version'] = CRM_Utils_Array::value('domain_version', $params);
  $domain            = CRM_Core_BAO_Domain::create($params);
  $domain_array      = [];
  _civicrm_api3_object_to_array($domain, $domain_array[$domain->id]);
  return civicrm_api3_create_success($domain_array, $params);
}
/*
 * Adjust Metadata for Create action
 * 
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_domain_create_spec(&$params) {
  $params['domain_version'] = $params['version'];
  unset($params['version']);
}

