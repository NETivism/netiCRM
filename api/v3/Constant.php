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
 * File for CiviCRM APIv3 pseudoconstants
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Constant
 * @copyright CiviCRM LLC (c) 2004-2012
 * @version $Id: Constant.php 30171 2010-10-14 09:11:27Z mover $
 *
 */

/**
 * Generic file to retrieve all the constants and
 * pseudo constants used in CiviCRM
 *
 *  @param  string  Name of a public static method of
 *                  CRM_Core_PseudoContant: one of
 *  <ul>
 *    <li>activityStatus</li>
 *    <li>activityType</li>
 *    <li>addressee</li>
 *    <li>allGroup</li>
 *    <li>country</li>
 *    <li>countryIsoCode</li>
 *    <li>county</li>
 *    <li>currencyCode</li>
 *    <li>currencySymbols</li>
 *    <li>customGroup</li>
 *    <li>emailGreeting</li>
 *    <li>fromEmailAddress</li>
 *    <li>gender</li>
 *    <li>group</li>
 *    <li>groupIterator</li>
 *    <li>honor</li>
 *    <li>IMProvider</li>
 *    <li>individualPrefix</li>
 *    <li>individualSuffix</li>
 *    <li>locationType</li>
 *    <li>locationVcardName</li>
 *    <li>mailProtocol</li>
 *    <li>mappingTypes</li>
 *    <li>paymentProcessor</li>
 *    <li>paymentProcessorType</li>
 *    <li>pcm</li>
 *    <li>phoneType</li>
 *    <li>postalGreeting</li>
 *    <li>priority</li>
 *    <li>relationshipType</li>
 *    <li>stateProvince</li>
 *    <li>stateProvinceAbbreviation</li>
 *    <li>stateProvinceForCountry</li>
 *    <li>staticGroup</li>
 *    <li>tag</li>
 *    <li>tasks</li>
 *    <li>ufGroup</li>
 *    <li>visibility</li>
 *    <li>worldRegion</li>
 *    <li>wysiwygEditor</li>
 *  </ul>
 *  @example ConstantGet.php
 *  {@getfields constant_get}
 */
function civicrm_api3_constant_get($params) {

  $name = $params['name'];
  //  require_once 'CRM/Core/PseudoConstant.php';
  $className = 'CRM_Core_PseudoConstant';
  if (isset ($params['class']) && (($params['class'] == 'CRM_Event_PseudoConstant') || $params['class'] == 'CRM_Contribute_PseudoConstant') || $params['class'] == 'CRM_Member_PseudoConstant') {
    $className = $params['class'];
  }
  $callable = "$className::$name";
  if (is_callable($callable)) {
    // #41306 only few constant return name instead label, dirty hack it
    if ($params['class'] == 'CRM_Event_PseudoConstant' && $name == 'participantStatus') {
      $values = call_user_func(array($className, $name), NULL, NULL, 'label');
    }
    else {
      $values = call_user_func(array($className, $name));
    }
    return civicrm_api3_create_success($values, $params);
  }

  return civicrm_api3_create_error('Unknown civicrm constant or method not callable');
}

function _civicrm_api3_constant_get_spec(&$params) {

  $params = (array
    ('name' => array(
      'api.required' => 1,
        'options' =>
        'activityStatus',
        'activityType',
        'addressee',
        'allGroup',
        'country',
        'countryIsoCode',
        'county',
        'currencyCode',
        'currencySymbols',
        'customGroup',
        'emailGreeting',
        'fromEmailAddress',
        'gender',
        'group',
        'groupIterator',
        'honor',
        'IMProvider',
        'individualPrefix',
        'individualSuffix',
        'locationType',
        'locationVcardName',
        'mailProtocol',
        'mappingTypes',
        'paymentProcessor',
        'paymentProcessorType',
        'pcm',
        'phoneType',
        'postalGreeting',
        'priority',
        'relationshipType',
        'stateProvince',
        'stateProvinceAbbreviation',
        'stateProvinceForCountry',
        'staticGroup',
        'tag',
        'tasks',
        'ufGroup',
        'visibility',
        'worldRegion',
        'wysiwygEditor',
      ))
  );
}

