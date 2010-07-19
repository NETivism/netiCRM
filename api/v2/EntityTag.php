<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
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
 * File for the CiviCRM APIv2 entity tag functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_EntityTag
 * 
 * @copyright CiviCRM LLC (c) 2004-2010
 * @version $Id: EntityTag.php 26284 2010-02-17 17:58:00Z shot $
 */

/**
 * Include utility functions
 */
require_once 'api/v2/utils.php';

/**
 *
 * @param <type> $params
 * @return <type>
 */
function civicrm_entity_tag_get( &$params ) {
    if ( !is_array($params) || ! array_key_exists( 'contact_id', $params ) ) {
        return civicrm_create_error( ts( 'contact_id is a required field' ) );
    }

    require_once 'CRM/Core/BAO/EntityTag.php';
    $values =& CRM_Core_BAO_EntityTag::getTag( $params['contact_id'] );
    $result = array( );
    foreach ( $values as $v ) {
        $result[] = array( 'tag_id' => $v );
    }
    return $result;
}

/**
 *
 * @param <type> $params
 * @return <type>
 */
function civicrm_entity_tag_display( &$params ) {
    if ( ! array_key_exists( 'contact_id', $params ) ) {
        return civicrm_create_error( ts( 'contact_id is a required field' ) );
    }

    require_once 'CRM/Core/BAO/EntityTag.php';
    $values =& CRM_Core_BAO_EntityTag::getTag( $params['contact_id'] );
    $result = array( );
    $tags   = CRM_Core_PseudoConstant::tag( );
    foreach ( $values as $v ) {
        $result[] = $tags[$v];
    }
    return implode( ',', $result );
}

/**
 * Returns all entities assigned to a specific Tag.
 * @param  $params      Array   an array valid Tag id                               
 * @return $entities    Array   An array of entity ids.
 * @access public
 */
function civicrm_tag_entities_get( &$params )
{
    require_once 'CRM/Core/BAO/Tag.php';
    require_once 'CRM/Core/BAO/EntityTag.php';
    $tag      = new CRM_Core_BAO_Tag();
    $tag->id  = $params['tag_id'] ? $params['tag_id'] : null;
    $entities =& CRM_Core_BAO_EntityTag::getEntitiesByTag($tag);    
    return $entities;   
}

/**
 *
 * @param <type> $params
 * @return <type>
 */
function civicrm_entity_tag_add( &$params ) {
    return civicrm_entity_tag_common( $params, 'add' );
}

/**
 *
 * @param <type> $params
 * @return <type>
 */
function civicrm_entity_tag_remove( &$params ) {
    return civicrm_entity_tag_common( $params, 'remove' );
}

/**
 *
 * @param <type> $params
 * @param <type> $op
 * @return <type> 
 */
function civicrm_entity_tag_common( &$params, $op = 'add' ) {
    $contactIDs = array( );
    $tagsIDs    = array( );
    if (is_array($params)) {
        foreach ( $params as $n => $v ) {
            if ( substr( $n, 0, 10 ) == 'contact_id' ) {
                $contactIDs[] = $v;
            } else if ( substr( $n, 0, 6 ) == 'tag_id' ) {
                $tagIDs[] = $v;
            }
        }
    }
    if ( empty( $contactIDs ) ) {
        return civicrm_create_error( ts( 'contact_id is a required field' ) );
    }

    if ( empty( $tagIDs ) ) {
        return civicrm_create_error( ts( 'tag_id is a required field' ) );
    }

    require_once 'CRM/Core/BAO/EntityTag.php';
    $values = array( 'is_error' => 0 );
    if ( $op == 'add' ) {
        $values['total_count'] = $values['added'] = $values['not_added'] = 0;
        foreach ( $tagIDs as $tagID ) {
            list( $tc, $a, $na ) = 
                CRM_Core_BAO_EntityTag::addContactsToTag( $contactIDs, $tagID );
            $values['total_count'] += $tc;
            $values['added']       += $a;
            $values['not_added']   += $na;
        }
    } else {
        $values['total_count'] = $values['removed'] = $values['not_removed'] = 0;
        foreach ( $tagIDs as $tagID ) {
            list( $tc, $r, $nr ) = 
                CRM_Core_BAO_EntityTag::removeContactsFromTag( $contactIDs, $tagID );
            $values['total_count'] += $tc;
            $values['removed']     += $r;
            $values['not_removed'] += $nr;
        }
    }
    return $values;
}
