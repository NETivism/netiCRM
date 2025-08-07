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
 * File for the CiviCRM APIv3 relationship functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Relationship
 *
 * @copyright CiviCRM LLC (c) 2004-2012
 * @version $Id: Relationship.php 30486 2010-11-02 16:12:09Z shot $
 *
 */

/**
 * Add or update a relationship
 *
 * @param  array   $params  input parameters
 *
 * @example RelationshipCreate.php Std Create example
 *
 * @return array API Result Array
 * {@getfields relationship_create}
 * @static void
 * @access public
 *
 */
function civicrm_api3_relationship_create($params) {

  // check entities exist
  $orig_values = _civicrm_api3_relationship_check_params($params);
  $values = [];
  _civicrm_api3_relationship_format_params($params, $values);
  $ids = [];
  require_once 'CRM/Core/Action.php';
  $action = CRM_Core_Action::ADD;
  require_once 'CRM/Utils/Array.php';

  if (CRM_Utils_Array::value('id', $params)) {
    $params = array_merge($params, $orig_values);
    $ids['relationship'] = $params['id'];
    $ids['contactTarget'] = $params['contact_id_b'];
    $action = CRM_Core_Action::UPDATE;
  }

  $values['relationship_type_id'] = $params['relationship_type_id'] . '_a_b';
  $values['contact_check'] = [$params['contact_id_b'] => $params['contact_id_b']];
  $ids['contact'] = $params['contact_id_a'];

  $relationshipBAO = CRM_Contact_BAO_Relationship::create($values, $ids);

  if ($relationshipBAO[1]) {
    return civicrm_api3_create_error('Relationship is not valid');
  }
  elseif ($relationshipBAO[2]) {
    return civicrm_api3_create_error('Relationship already exists');
  }
  CRM_Contact_BAO_Relationship::relatedMemberships($params['contact_id_a'], $values, $ids, $action);
  $relationID = $relationshipBAO[4][0];
  return civicrm_api3_create_success([
    $relationID => ['id' => $relationID,
        'moreIDs' => CRM_Utils_Array::implode(',', $relationshipBAO[4]),
      ]]);
}
/*
 * Adjust Metadata for Create action
 * 
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_relationship_create_spec(&$params) {
  $params['contact_id_a']['api.required'] = 1;
  $params['contact_id_b']['api.required'] = 1;
  $params['relationship_type_id']['api.required'] = 1;
  $params['is_active']['api.default'] = 1;
}

/**
 * Delete a relationship
 *
 * @param  array $params
 *
 * @return array API Result Array
 * {@getfields relationship_delete}
 * @example RelationshipDelete.php Delete Example
 *
 * @static void
 * @access public
 */
function civicrm_api3_relationship_delete($params) {

  require_once 'CRM/Utils/Rule.php';
  if (!CRM_Utils_Rule::integer($params['id'])) {
    return civicrm_api3_create_error('Invalid value for relationship ID');
  }

  $relationBAO = new CRM_Contact_BAO_Relationship();
  $relationBAO->id = $params['id'];
  if (!$relationBAO->find(TRUE)) {
    return civicrm_api3_create_error('Relationship id is not valid');
  }
  else {
    $relationBAO->del($params['id']);
    return civicrm_api3_create_success('Deleted relationship successfully');
  }
}

/**
 * Function to get the relationship
 *
 * @param array   $params input parameters.
 * @todo  Result is inconsistent depending on whether contact_id is passed in :
 * -  if you pass in contact_id - it just returns all relationships for 'contact_id'
 * -  if you don't pass in contact_id then it does a filter on the relationship table (DAO based search)
 *
 * @return  Array API Result Array
 * {@getfields relationship_get}
 * @example RelationshipGet.php
 * @access  public
 */
function civicrm_api3_relationship_get($params) {

  if (!CRM_Utils_Array::value('contact_id', $params)) {
    $relationships = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, FALSE);
  }
  else {
    $relationships = [];
    $relationships = CRM_Contact_BAO_Relationship::getRelationship($params['contact_id'],
      CRM_Utils_Array::value('status_id', $params),
      0,
      0,
      CRM_Utils_Array::value('id', $params), NULL
    );
  }
  foreach ($relationships as $relationshipId => $values) {
    _civicrm_api3_custom_data_get($relationships[$relationshipId], 'Relationship', $relationshipId, NULL, CRM_Utils_Array::value('relationship_type_id',$values));
  }


  return civicrm_api3_create_success($relationships, $params);
}

/**
 * take the input parameter list as specified in the data model and
 * convert it into the same format that we use in QF and BAO object
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new contact.
 * @param array  $values       The reformatted properties that we can use internally
 *                            '
 *
 * @return array|CRM_Error
 * @access public
 */
function _civicrm_api3_relationship_format_params($params, &$values) {
  // copy all the relationship fields as is

  $fields = CRM_Contact_DAO_Relationship::fields();
  _civicrm_api3_store_values($fields, $params, $values);

  $relationTypes = CRM_Core_PseudoConstant::relationshipType('name');

  foreach ($params as $key => $value) {
    // ignore empty values or empty arrays etc
    require_once 'CRM/Utils/System.php';
    if (CRM_Utils_System::isNull($value)) {
      continue;
    }

    switch ($key) {
      case 'contact_id_a':
      case 'contact_id_b':
        require_once 'CRM/Utils/Rule.php';
        if (!CRM_Utils_Rule::integer($value)) {
          throw new Exception("contact_id not valid: $value");
        }
        $dao     = new CRM_Core_DAO();
        $qParams = [];
        $svq     = $dao->singleValueQuery("SELECT id FROM civicrm_contact WHERE id = $value",
          $qParams
        );
        if (!$svq) {
          throw new Exception("Invalid Contact ID: There is no contact record with contact_id = $value.");
        }
        break;

      case 'relationship_type':
        foreach ($relationTypes as $relTypId => $relValue) {
          if (CRM_Utils_Array::key(ucfirst($value), $relValue)) {
            $relationshipTypeId = $relTypId;
            break;
          }
        }

        if ($relationshipTypeId) {
          if (CRM_Utils_Array::value('relationship_type_id', $values) &&
            $relationshipTypeId != $values['relationship_type_id']
          ) {
            throw new Exception('Mismatched Relationship Type and Relationship Type Id');
          }
          $values['relationship_type_id'] = $params['relationship_type_id'] = $relationshipTypeId;
        }
        else {
          throw new Exception('Invalid Relationship Type');
        }
      case 'relationship_type_id':
        if ($key == 'relationship_type_id' && !CRM_Utils_Array::arrayKeyExists($value, $relationTypes)) {
          throw new Exception("$key not a valid: $value");
        }

        // execute for both relationship_type and relationship_type_id
        $relation = $relationTypes[$params['relationship_type_id']];
        if ($relation['contact_type_a'] &&
          $relation['contact_type_a'] != CRM_Contact_BAO_Contact::getContactType($params['contact_id_a'])
        ) {
          throw new Exception("Contact ID :{$params['contact_id_a']} is not of contact type {$relation['contact_type_a']}");
        }
        if ($relation['contact_type_b'] &&
          $relation['contact_type_b'] != CRM_Contact_BAO_Contact::getContactType($params['contact_id_b'])
        ) {
          throw new Exception("Contact ID :{$params['contact_id_b']} is not of contact type {$relation['contact_type_b']}");
        }
        break;

      default:
        break;
    }
  }

  if (CRM_Utils_Array::arrayKeyExists('note', $params)) {
    $values['note'] = $params['note'];
  }
  _civicrm_api3_custom_format_params($params, $values, 'Relationship');

  return [];
}
/*
 * @deprecated - checking to be moved to wrapper
 */
function _civicrm_api3_relationship_check_params(&$params) {


  // check params for validity of Relationship id
  if (CRM_Utils_Array::value('id', $params)) {
    $relation = new CRM_Contact_BAO_Relationship();
    $relation->id = $params['id'];
    if (!$relation->find(TRUE)) {
      throw new Exception('Relationship id is not valid');
    }
    else {
      if ((isset($params['contact_id_a']) && $params['contact_id_a'] != $relation->contact_id_a) ||
        (isset($params['contact_id_b']) && $params['contact_id_b'] != $relation->contact_id_b)
      ) {
        throw new Exception('Cannot change the contacts once relationship has been created');
      }
      else {
        // since the BAO function is not std & won't accept just 'id' (aargh) let's
        // at least return our BAO here
        $values = [];
        _civicrm_api3_object_to_array($relation, $values);
        return $values;
      }
    }
  }

  return [];
}

