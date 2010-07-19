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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */


/**
 * This class contains the funtions for Component export
 *
 */
class CRM_Export_BAO_Export
{
    /**
     * Function to get the list the export fields
     *
     * @param int    $selectAll user preference while export
     * @param array  $ids  contact ids
     * @param array  $params associated array of fields
     * @param string $order order by clause
     * @param array  $associated array of fields
     * @param array  $moreReturnProperties additional return fields
     * @param int    $exportMode export mode
     * @param string $componentClause component clause
     *
     * @static
     * @access public
     */
    static function exportComponents( $selectAll, $ids, $params, $order = null, 
                                      $fields = null, $moreReturnProperties = null, 
                                      $exportMode = CRM_Export_Form_Select::CONTACT_EXPORT,
                                      $componentClause = null )
    {
        $headerRows       = array();
        $primary          = false;
        $returnProperties = array( );
        $origFields       = $fields;
        $queryMode        = null; 
        $paymentFields    = false;

        $phoneTypes = CRM_Core_PseudoConstant::phoneType();
        $imProviders = CRM_Core_PseudoConstant::IMProvider();
        $contactRelationshipTypes = CRM_Contact_BAO_Relationship::getContactRelationshipType( null, null, null, null, true, 'label', false );
        $queryMode = CRM_Contact_BAO_Query::MODE_CONTACTS;
        
        switch ( $exportMode )  {
        case CRM_Export_Form_Select::CONTRIBUTE_EXPORT :
            $queryMode = CRM_Contact_BAO_Query::MODE_CONTRIBUTE;
            break;
        case CRM_Export_Form_Select::EVENT_EXPORT :
            $queryMode = CRM_Contact_BAO_Query::MODE_EVENT;
            break;
        case CRM_Export_Form_Select::MEMBER_EXPORT :
            $queryMode = CRM_Contact_BAO_Query::MODE_MEMBER;
            break;
        case CRM_Export_Form_Select::PLEDGE_EXPORT :
            $queryMode = CRM_Contact_BAO_Query::MODE_PLEDGE;
            break;
        case CRM_Export_Form_Select::CASE_EXPORT :
            $queryMode = CRM_Contact_BAO_Query::MODE_CASE;
            break;
        case CRM_Export_Form_Select::GRANT_EXPORT :
            $queryMode = CRM_Contact_BAO_Query::MODE_GRANT;
            break;
            
        }
        require_once 'CRM/Core/BAO/CustomField.php';
        if ( $fields ) {
            //construct return properties 
            $locationTypes =& CRM_Core_PseudoConstant::locationType();
            $locationTypeFields =  array ('street_address','supplemental_address_1', 'supplemental_address_2', 'city', 'postal_code', 'postal_code_suffix', 'geo_code_1', 'geo_code_2', 'state_province', 'country', 'phone', 'email', 'im' );
            foreach ( $fields as $key => $value) {
                $phoneTypeId  = null;
                $imProviderId = null;
                $relationshipTypes = $fieldName   = CRM_Utils_Array::value( 1, $value );
                if ( ! $fieldName ) {
                    continue;
                }
                // get phoneType id and IM service provider id seperately
                if ( $fieldName == 'phone' ) { 
                    $phoneTypeId = CRM_Utils_Array::value( 3, $value );
                } else if ( $fieldName == 'im' ) { 
                    $imProviderId = CRM_Utils_Array::value( 3, $value );
                }
                
                if ( array_key_exists ( $relationshipTypes, $contactRelationshipTypes )  ) {
                    if ( CRM_Utils_Array::value( 2, $value ) ) {
                        $relationField = CRM_Utils_Array::value( 2, $value );
                        if ( trim ( CRM_Utils_Array::value( 3, $value ) ) ) {
                            $relLocTypeId = CRM_Utils_Array::value( 3, $value );
                        } else {
                            $relLocTypeId = 1;
                        }

                        if ( $relationField == 'phone' ) { 
                            $relPhoneTypeId  = CRM_Utils_Array::value( 4, $value );                            
                        } else if ( $relationField == 'im' ) {
                            $relIMProviderId = CRM_Utils_Array::value( 4, $value );
                        }
                    } else if ( CRM_Utils_Array::value( 4, $value ) ) {
                        $relationField  = CRM_Utils_Array::value( 4, $value );
                        $relLocTypeId   = CRM_Utils_Array::value( 5, $value );
                        if ( $relationField == 'phone' ) { 
                            $relPhoneTypeId  = CRM_Utils_Array::value( 6, $value );                            
                        } else if ( $relationField == 'im' ) {
                            $relIMProviderId = CRM_Utils_Array::value( 6, $value );
                        }
                    }                    
                }

                $contactType       = CRM_Utils_Array::value( 0, $value );
                $locTypeId         = CRM_Utils_Array::value( 2, $value );
                $phoneTypeId       = CRM_Utils_Array::value( 3, $value );

                
                if ( $relationField ) {
                    if ( in_array ( $relationField, $locationTypeFields ) ) {
                        if ( $relPhoneTypeId ) {                            
                            $returnProperties[$relationshipTypes]['location'][$locationTypes[$relLocTypeId]]['phone-' .$relPhoneTypeId] = 1;
                        } else if ( $relIMProviderId ) {                            
                            $returnProperties[$relationshipTypes]['location'][$locationTypes[$relLocTypeId]]['im-' .$relIMProviderId] = 1;
                        } else {
                            $returnProperties[$relationshipTypes]['location'][$locationTypes[$relLocTypeId]][$relationField] = 1;
                        } 
                        $relPhoneTypeId = $relIMProviderId = null;                       
                    } else {
                        $returnProperties[$relationshipTypes][$relationField]  = 1;
                    }                    
                } else if ( is_numeric($locTypeId) ) {
                    if ($phoneTypeId) {
                        $returnProperties['location'][$locationTypes[$locTypeId]]['phone-' .$phoneTypeId] = 1;
                    } else if ( isset($imProviderId) ) { 
                        //build returnProperties for IM service provider
                        $returnProperties['location'][$locationTypes[$locTypeId]]['im-' .$imProviderId] = 1;
                    } else {
                        $returnProperties['location'][$locationTypes[$locTypeId]][$fieldName] = 1;
                    }
                } else {
                    //hack to fix component fields
                    if ( $fieldName == 'event_id' ) {
                        $returnProperties['event_title'] = 1;
                    } else {
                        $returnProperties[$fieldName] = 1;
                    }
                }
            }

            // hack to add default returnproperty based on export mode
            if ( $exportMode == CRM_Export_Form_Select::CONTRIBUTE_EXPORT ) {
                $returnProperties['contribution_id'] = 1;
            } else if ( $exportMode == CRM_Export_Form_Select::EVENT_EXPORT ) {
                $returnProperties['participant_id'] = 1;
            } else if ( $exportMode == CRM_Export_Form_Select::MEMBER_EXPORT ) {
                $returnProperties['membership_id'] = 1;
            } else if ( $exportMode == CRM_Export_Form_Select::PLEDGE_EXPORT ) {
                $returnProperties['pledge_id'] = 1;
            } else if ( $exportMode == CRM_Export_Form_Select::CASE_EXPORT ) {
                $returnProperties['case_id'] = 1;
            } else if ( $exportMode == CRM_Export_Form_Select::GRANT_EXPORT ) {
                $returnProperties['grant_id'] = 1;
            }
            
         } else {
            $primary = true;
            $fields = CRM_Contact_BAO_Contact::exportableFields( 'All', true, true );
            foreach ($fields as $key => $var) { 
                if ( $key &&
                     ( substr($key,0, 6) !=  'custom' ) ) { //for CRM=952
                    $returnProperties[$key] = 1;
                }
            }
            
            if ( $primary ) {
                $returnProperties['location_type'   ] = 1;
                $returnProperties['im_provider'     ] = 1;
                $returnProperties['phone_type_id'   ] = 1;
                $returnProperties['provider_id'     ] = 1;
                $returnProperties['current_employer'] = 1;
            }
            
            $extraReturnProperties = array( );
            $paymentFields = false;
            
            switch ( $queryMode )  {
            case CRM_Contact_BAO_Query::MODE_EVENT :
                $paymentFields  = true;
                $paymentTableId = "participant_id";
                break;
            case CRM_Contact_BAO_Query::MODE_MEMBER :
                $paymentFields  = true;
                $paymentTableId = "membership_id";
                break;
            case CRM_Contact_BAO_Query::MODE_PLEDGE :
                require_once 'CRM/Pledge/BAO/Query.php';
                $extraReturnProperties = CRM_Pledge_BAO_Query::extraReturnProperties( $queryMode );
                $paymentFields  = true;
                $paymentTableId = "pledge_payment_id";
                break;
            case CRM_Contact_BAO_Query::MODE_CASE :
                require_once 'CRM/Case/BAO/Query.php';
                $extraReturnProperties = CRM_Case_BAO_Query::extraReturnProperties( $queryMode );
                break;
            }
            
            if ( $queryMode != CRM_Contact_BAO_Query::MODE_CONTACTS ) {
                $componentReturnProperties =& CRM_Contact_BAO_Query::defaultReturnProperties( $queryMode );
                $returnProperties          = array_merge( $returnProperties, $componentReturnProperties );
        
                if ( !empty( $extraReturnProperties ) ) {
                    $returnProperties = array_merge( $returnProperties, $extraReturnProperties );
                }
        
                // unset groups, tags, notes for components
                foreach ( array( 'groups', 'tags', 'notes' ) as $value ) {
                    unset( $returnProperties[$value] );
                }
            }
        }
        
        if ( $moreReturnProperties ) {
            $returnProperties = array_merge( $returnProperties, $moreReturnProperties );
        }

        $query =& new CRM_Contact_BAO_Query( 0, $returnProperties, null, false, false, $queryMode );

        list( $select, $from, $where ) = $query->query( );
        $allRelContactArray = $relationQuery = array();
        foreach ( $contactRelationshipTypes as $rel => $dnt ) {
            if (  $relationReturnProperties = CRM_Utils_Array::value( $rel, $returnProperties ) ) {
                $allRelContactArray[$rel] = array();
                // build Query for each relationship
                $relationQuery[$rel] =& new CRM_Contact_BAO_Query( 0, $relationReturnProperties,
                                                             null, false, false, $queryMode );
                list( $relationSelect, $relationFrom, $relationWhere ) = $relationQuery[$rel]->query( );
                
                list( $id, $direction ) = explode( '_', $rel, 2 );
                // identify the relationship direction
                $contactA = 'contact_id_a';
                $contactB = 'contact_id_b'; 
                if ( $direction == 'b_a' ) {
                    $contactA = 'contact_id_b';
                    $contactB = 'contact_id_a';
                }
                
                $relIDs = implode(',', $ids);
                $relSQL = "SELECT {$contactB} as relContact,{$contactA} as refContact  FROM civicrm_relationship 
                           WHERE  relationship_type_id = $id AND
                                  {$contactA} IN ({$relIDs})
                           GROUP BY {$contactA}";
                // Get the related contacts
                $relContactDAO   = CRM_Core_DAO::executeQuery( $relSQL );
                $relContactArray = array();
                while ( $relContactDAO->fetch() ) {
                    $relContactArray[$relContactDAO->refContact] = $relContactDAO->relContact;
                }
                $uniqueContacts      = array_unique($relContactArray);
                if ( !empty ($uniqueContacts ) ) {
                    $relationWhere       = " WHERE contact_a.id IN (". implode(',', $uniqueContacts ) .") GROUP BY contact_id";
                    
                    $relationQueryString = "$relationSelect $relationFrom $relationWhere";
                    
                    $allRelContactDAO    = CRM_Core_DAO::executeQuery( $relationQueryString );
                    while ( $allRelContactDAO->fetch() ) {
                        foreach ( $relContactArray as $k => $v ) {
                            if ($allRelContactDAO->contact_id == $v ) {
                                //$allRelContactArray[$rel][$k] = array();
                                // build the array of all related contacts
                                $allRelContactArray[$rel][$k] = clone($allRelContactDAO);
                            }
                        }
                    }
                }
            }
        }
        // make sure the groups stuff is included only if specifically specified
        // by the fields param (CRM-1969), else we limit the contacts outputted to only
        // ones that are part of a group
        if ( CRM_Utils_Array::value( 'groups', $returnProperties ) ) {
            $oldClause = "contact_a.id = civicrm_group_contact.contact_id";
            $newClause = " ( $oldClause AND civicrm_group_contact.status = 'Added' OR civicrm_group_contact.status IS NULL ) ";
            // total hack for export, CRM-3618
            $from = str_replace( $oldClause,
                                 $newClause,
                                 $from );
        }

        if ( $componentClause ) {
            if ( empty( $where ) ) {
                $where = "WHERE $componentClause";
            } else {
                $where .= " AND $componentClause";
            }
        }
        
        $queryString = "$select $from $where";
        
        if ( CRM_Utils_Array::value( 'tags'  , $returnProperties ) || 
             CRM_Utils_Array::value( 'groups', $returnProperties ) ||
             CRM_Utils_Array::value( 'notes' , $returnProperties ) ||
             $query->_useGroupBy ) { 
            $queryString .= " GROUP BY contact_a.id";
        }
        
        if ( $order ) {
            list( $field, $dir ) = explode( ' ', $order, 2 );
            $field = trim( $field );
            if ( CRM_Utils_Array::value( $field, $returnProperties ) ) {
                $queryString .= " ORDER BY $order";
            }
        }

        //hack for student data
        require_once 'CRM/Core/OptionGroup.php';
        $multipleSelectFields = array( 'preferred_communication_method' => 1 );
        
        if ( CRM_Core_Permission::access( 'Quest' ) ) { 
            require_once 'CRM/Quest/BAO/Student.php';
            $studentFields = array( );
            $studentFields = CRM_Quest_BAO_Student::$multipleSelectFields;
            $multipleSelectFields = array_merge( $multipleSelectFields, $studentFields );
        }
        $dao =& CRM_Core_DAO::executeQuery( $queryString, CRM_Core_DAO::$_nullArray );
        $header = false;
        
        $addPaymentHeader = false;
        if ( $paymentFields ) {
            $addPaymentHeader = true;
            //special return properties for event and members
            $paymentHeaders = array( ts('Total Amount'), ts('Contribution Status'), ts('Received Date'),
                                     ts('Payment Instrument'), ts('Transaction ID'));
            
            // get payment related in for event and members
            require_once 'CRM/Contribute/BAO/Contribution.php';
            $paymentDetails = CRM_Contribute_BAO_Contribution::getContributionDetails( $exportMode, $ids );
        }

        $componentDetails = $headerRows = array( );
        $setHeader = true;
        while ( $dao->fetch( ) ) {
            $row = array( );
            //first loop through returnproperties so that we return what is required, and in same order.
            $relationshipField = 0;
            foreach( $returnProperties as $field => $value ) {
                //we should set header only once
                if ( $setHeader ) { 
                    if ( isset( $query->_fields[$field]['title'] ) ) {
                        $headerRows[] = $query->_fields[$field]['title'];
                    } else if ($field == 'phone_type_id'){
                        $headerRows[] = 'Phone Type';
                    } else if ( $field == 'provider_id' ) { 
                        $headerRows[] = 'Im Service Provider'; 
                    } else if ( is_array( $value ) && $field == 'location' ) {
                        // fix header for location type case
                        foreach ( $value as $ltype => $val ) {
                            foreach ( array_keys($val) as $fld ) {
                                $type = explode('-', $fld );
                                $hdr = "{$ltype}-" . $query->_fields[$type[0]]['title'];
                                
                                if ( CRM_Utils_Array::value( 1, $type ) ) {
                                    if ( CRM_Utils_Array::value( 0, $type ) == 'phone' ) {
                                        $hdr .= "-" . CRM_Utils_Array::value( $type[1], $phoneTypes );
                                    } else if ( CRM_Utils_Array::value( 0, $type ) == 'im' ) {
                                        $hdr .= "-" . CRM_Utils_Array::value( $type[1], $imProviders );
                                    }
                                }
                                $headerRows[] = $hdr;
                            }
                        }
                    } else if ( substr( $field, 0, 5 ) == 'case_' ) {
                        if (  $query->_fields['case'][$field]['title'] ) {
                            $headerRows[] = $query->_fields['case'][$field]['title'];
                        } else if ( $query->_fields['activity'][$field]['title'] ){
                            $headerRows[] = $query->_fields['activity'][$field]['title'];
                        }
                    } else if ( array_key_exists( $field, $contactRelationshipTypes ) ) {
                        $relName = CRM_Utils_Array::value($field, $contactRelationshipTypes);
                        foreach ( $value as $relationField => $relationValue ) {
                            // below block is same as primary block (duplicate)
                            if ( isset( $relationQuery[$field]->_fields[$relationField]['title'] ) ) {
                                $headerRows[] = $relName .'-' . $relationQuery[$field]->_fields[$relationField]['title'];
                            } else if ($relationField == 'phone_type_id'){
                                $headerRows[] = $relName .'-' . 'Phone Type';
                            } else if ( $relationField == 'provider_id' ) { 
                                $headerRows[] = $relName .'-' . 'Im Service Provider'; 
                            } else if ( is_array( $relationValue ) && $relationField == 'location' ) {
                                // fix header for location type case
                                foreach ( $relationValue as $ltype => $val ) {
                                    foreach ( array_keys($val) as $fld ) {
                                        $type = explode('-', $fld );
                                        $hdr = "{$ltype}-" . $relationQuery[$field]->_fields[$type[0]]['title'];

                                        if ( CRM_Utils_Array::value( 1, $type ) ) {
                                            if ( CRM_Utils_Array::value( 0, $type ) == 'phone' ) {
                                                $hdr .= "-" . CRM_Utils_Array::value( $type[1], $phoneTypes );
                                            } else if ( CRM_Utils_Array::value( 0, $type ) == 'im' ) {
                                                $hdr .= "-" . CRM_Utils_Array::value( $type[1], $imProviders );
                                            }
                                        }
                                        $headerRows[] = $relName .'-' . $hdr;
                                    }
                                }
                            }
                        }
                    } else {
                        $headerRows[] = $field;
                    }
                }

                //build row values (data)
                if ( property_exists( $dao, $field ) ) {
                    $fieldValue = $dao->$field;
                    // to get phone type from phone type id
                    if ( $field == 'phone_type_id' ) {
                        $fieldValue = $phoneTypes[$fieldValue];
                    } else if ( $field == 'provider_id' ) {
                        $fieldValue = CRM_Utils_Array::value( $fieldValue , $imProviders );  
                    }
                } else {
                    $fieldValue = '';
                }
                
                if ( $field == 'id' ) {
                    $row[$field] = $dao->contact_id;
                } else if ( $field == 'pledge_balance_amount' ) { //special case for calculated field
                    $row[$field] = $dao->pledge_amount - $dao->pledge_total_paid;
                } else if ( $field == 'pledge_next_pay_amount' ) { //special case for calculated field
                    $row[$field] = $dao->pledge_next_pay_amount + $dao->pledge_outstanding_amount;
                } else if ( is_array( $value ) && $field == 'location' ) {
                    // fix header for location type case
                    foreach ( $value as $ltype => $val ) {
                        foreach ( array_keys($val) as $fld ) {
                            $type = explode('-', $fld );
                            $fldValue = "{$ltype}-" . $type[0];
                            
                            if ( CRM_Utils_Array::value( 1, $type ) ) {
                                $fldValue .= "-" . $type[1];
                            }
                            
                            $row[$fldValue] = $dao->$fldValue;
                        }
                    }
                } else if ( array_key_exists( $field, $contactRelationshipTypes ) ) {
                    $relDAO = $allRelContactArray[$field][$dao->contact_id];

                    foreach ( $value as $relationField => $relationValue ) {
                        if ( is_object($relDAO) && property_exists( $relDAO, $relationField ) ) {
                            $fieldValue = $relDAO->$relationField;
                            if ( $relationField == 'phone_type_id' ) {
                                $fieldValue = $phoneTypes[$relationValue];
                            } else if ( $relationField == 'provider_id' ) {
                                $fieldValue = CRM_Utils_Array::value( $relationValue, $imProviders );  
                            }
                        } else {
                            $fieldValue = '';
                        }
                        if ( $relationField == 'id' ) {
                            $row[$field . $relationField] = $relDAO->contact_id;
                        } else  if ( is_array( $relationValue ) && $relationField == 'location' ) {
                            foreach ( $relationValue as $ltype => $val ) {
                                foreach ( array_keys($val) as $fld ) {
                                    $type     = explode('-', $fld );
                                    $fldValue = "{$ltype}-" . $type[0];
                                    if ( CRM_Utils_Array::value( 1, $type ) ) {
                                        $fldValue .= "-" . $type[1];
                                    }
                                    $row[$field . $fldValue] = $relDAO->$fldValue;
                                }
                            }
                        } else if ( isset( $fieldValue ) && $fieldValue != '' ) {
                            //check for custom data
                            if ( $cfID = CRM_Core_BAO_CustomField::getKeyID( $relationField ) ) {
                                $row[$field . $relationField] = 
                                    CRM_Core_BAO_CustomField::getDisplayValue( $fieldValue, $cfID, 
                                                                               $relationQuery[$field]->_options );
                            } else if ( in_array( $relationField , array( 'email_greeting', 'postal_greeting', 'addressee' ) ) ) {
                                //special case for greeting replacement
                                $fldValue    = "{$relationField}_display";
                                $row[$field . $relationField] = $relDAO->$fldValue;
                            } else {
                                //normal relationship fields
                                $row[$field . $relationField] = $fieldValue;
                            }
                        } else {
                            // if relation field is empty or null
                            $row[$field . $relationField] = '';             
                        }
                    }
                } else if ( isset( $fieldValue ) && $fieldValue != '' ) {
                    //check for custom data
                    if ( $cfID = CRM_Core_BAO_CustomField::getKeyID( $field ) ) {
                        $row[$field] = CRM_Core_BAO_CustomField::getDisplayValue( $fieldValue, $cfID, $query->_options );
                    } else if ( array_key_exists( $field, $multipleSelectFields ) ) {
                        //option group fixes
                        $paramsNew = array( $field => $fieldValue );
                        if ( $field == 'test_tutoring') {
                            $name = array( $field => array('newName' => $field ,'groupName' => 'test' ));
                        } else if (substr( $field, 0, 4) == 'cmr_') { //for  readers group
                            $name = array( $field => array('newName' => $field, 'groupName' => substr($field, 0, -3) ));
                        } else {
                            $name = array( $field => array('newName' => $field ,'groupName' => $field ));
                        }
                        CRM_Core_OptionGroup::lookupValues( $paramsNew, $name, false );
                        $row[$field] = $paramsNew[$field];
                    } else if ( in_array( $field , array( 'email_greeting', 'postal_greeting', 'addressee' ) ) ) {
                        //special case for greeting replacement
                        $fldValue    = "{$field}_display";
                        $row[$field] = $dao->$fldValue;
                    } else {
                        //normal fields
                        $row[$field] = $fieldValue;
                    }
                } else {
                    // if field is empty or null
                    $row[$field] = '';             
                }
            }

            //build header only once
            $setHeader = false;
        
            // add payment headers if required
            if ( $addPaymentHeader && $paymentFields ) {
                $headerRows = array_merge( $headerRows, $paymentHeaders );
                $addPaymentHeader = false;
            }

            // add payment related information
            if ( $paymentFields && isset( $paymentDetails[ $row[$paymentTableId] ] ) ) {
                $row = array_merge( $row, $paymentDetails[ $row[$paymentTableId] ] );
            }

            //remove organization name for individuals if it is set for current employer
            if ( CRM_Utils_Array::value('contact_type', $row ) && $row['contact_type'] == 'Individual' ) {
                $row['organization_name'] = '';
            }

            // CRM-3157: localise the output
            // FIXME: we should move this to multilingual stack some day
            require_once 'CRM/Core/I18n.php';
            $i18n =& CRM_Core_I18n::singleton();
            $translatable = array('preferred_communication_method', 'preferred_mail_format', 'gender', 'state_province', 'country', 'world_region');
            foreach ($translatable as $column) {
                if (isset($row[$column]) and $row[$column]) {
                    $row[$column] = $i18n->translate($row[$column]);
                }
            }
            // add component info
            $componentDetails[] = $row;
        }

        require_once 'CRM/Core/Report/Excel.php';
        CRM_Core_Report_Excel::writeCSVFile( self::getExportFileName( 'csv', $exportMode ), $headerRows, $componentDetails );
        exit();
    }

    /**
     * name of the export file based on mode
     *
     * @param string $output type of output
     * @param int    $mode export mode
     * @return string name of the file
     */
    function getExportFileName( $output = 'csv', $mode = CRM_Export_Form_Select::CONTACT_EXPORT ) 
    {
        switch ( $mode ) {
        case CRM_Export_Form_Select::CONTACT_EXPORT : 
            return ts('CiviCRM Contact Search');
            
        case CRM_Export_Form_Select::CONTRIBUTE_EXPORT : 
            return ts('CiviCRM Contribution Search');
            
        case CRM_Export_Form_Select::MEMBER_EXPORT : 
            return ts('CiviCRM Member Search');
            
        case CRM_Export_Form_Select::EVENT_EXPORT : 
            return ts('CiviCRM Participant Search');

        case CRM_Export_Form_Select::PLEDGE_EXPORT : 
            return ts('CiviCRM Pledge Search');
            
        case CRM_Export_Form_Select::CASE_EXPORT : 
            return ts('CiviCRM Case Search');
            
        case CRM_Export_Form_Select::GRANT_EXPORT : 
            return ts('CiviCRM Grant Search');

        }
    }


    /**
     * handle the export case. this is a hack, so please fix soon
     *
     * @param $args array this array contains the arguments of the url
     *
     * @static
     * @access public
     */
    static function invoke( $args ) 
    {
        // FIXME:  2005-06-22 15:17:33 by Brian McFee <brmcfee@gmail.com>
        // This function is a dirty, dirty hack.  It should live in its own
        // file.
        $session =& CRM_Core_Session::singleton();
        $type = $_GET['type'];
        
        if ($type == 1) {
            $varName = 'errors';
            $saveFileName = 'Import_Errors.csv';
        } else if ($type == 2) {
            $varName = 'conflicts';
            $saveFileName = 'Import_Conflicts.csv';
        } else if ($type == 3) {
            $varName = 'duplicates';
            $saveFileName = 'Import_Duplicates.csv';
        } else if ($type == 4) {
            $varName = 'mismatch';
            $saveFileName = 'Import_Mismatch.csv';
        } else if ($type == 5) {
            $varName = 'pledgePaymentErrors';
            $saveFileName = 'Import_Pledge_Payment_Errors.csv';
        } else if ($type == 6) {
            $varName = 'softCreditErrors';
            $saveFileName = 'Import_Soft_Credit_Errors.csv';
        } else {
            /* FIXME we should have an error here */
            return;
        }
        
        // FIXME: a hack until we have common import
        // mechanisms for contacts and contributions
        $realm = CRM_Utils_Array::value('realm',$_GET);
        if ($realm == 'contribution') {
            $controller = 'CRM_Contribute_Import_Controller';
        } else if ( $realm == 'membership' ) {
            $controller = 'CRM_Member_Import_Controller';
        } else if ( $realm == 'event' ) {
            $controller = 'CRM_Event_Import_Controller';
        } else if ( $realm == 'activity' ) {
            $controller = 'CRM_Activity_Import_Controller';
        } else {
            $controller = 'CRM_Import_Controller';
        }
        
        require_once 'CRM/Core/Key.php';
        $qfKey = CRM_Core_Key::get( $controller );
        
        $fileName = $session->get($varName . 'FileName', "{$controller}_{$qfKey}");
        
        $config =& CRM_Core_Config::singleton( ); 
        
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Description: File Transfer');
        header('Content-Type: text/csv');
        header('Content-Length: ' . filesize($fileName) );
        header('Content-Disposition: attachment; filename=' . $saveFileName);
        
        readfile($fileName);
        
        exit();
    }

    function exportCustom( $customSearchClass, $formValues, $order ) 
    {
        require_once( str_replace( '_', DIRECTORY_SEPARATOR, $customSearchClass ) . '.php' );
        eval( '$search = new ' . $customSearchClass . '( $formValues );' );
      
        $includeContactIDs = false;
        if ( $formValues['radio_ts'] == 'ts_sel' ) {
            $includeContactIDs = true;
        }

        $sql    = $search->all( 0, 0, $order, $includeContactIDs );

        $columns = $search->columns( );

        $header = array_keys  ( $columns );
        $fields = array_values( $columns );

        $rows = array( );
        $dao =& CRM_Core_DAO::executeQuery( $sql,
                                            CRM_Core_DAO::$_nullArray );
        $alterRow = false;
        if ( method_exists( $search, 'alterRow' ) ) {
            $alterRow = true;
        }
        while ( $dao->fetch( ) ) {
            $row = array( );

            foreach ( $fields as $field ) {
                $row[$field] = $dao->$field;
            }
            if ( $alterRow ) {
                $search->alterRow( $row );
            }
            $rows[] = $row;
        }

        require_once 'CRM/Core/Report/Excel.php';
        CRM_Core_Report_Excel::writeCSVFile( self::getExportFileName( ), $header, $rows );
        exit();
    }
}

