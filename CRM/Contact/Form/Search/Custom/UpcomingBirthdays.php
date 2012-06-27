<?php

/*
 +--------------------------------------------------------------------+
 | |                                                    |
 +--------------------------------------------------------------------+
 | Copyright Sarah Gladstone (c) 2004-2010                             |
 +--------------------------------------------------------------------+
 | This is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 .   |
 |                                                                    |
 | This is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.                         |
 +--------------------------------------------------------------------+                |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * $Id$
 *
 */

require_once 'CRM/Contact/Form/Search/Interface.php';


class CRM_Contact_Form_Search_Custom_UpcomingBirthdays
   implements CRM_Contact_Form_Search_Interface {

    protected $_formValues;
    protected $_tableName = null;

    function __construct( &$formValues ) {     
        $this->_formValues = $formValues;

        /**
         * Define the columns for search result rows
         */
        $this->_columns = array( 
				 ts('Name') => 'name',	
				 ts('Date') => 'sort_name',
				 ts('Occasion Type' ) => 'oc_type'
				 );
    }



    function buildForm( &$form ) {
        /**
         * You can define a custom title for the search form
         */
        $this->setTitle('Find Upcoming Birthdays');

        /**
         * Define the search form fields here
         */


$month =
            array( ''   => ' - select month - ' , '1' => 'January', '2' => 'February', '3' => 'March',
	 '4' => 'April', '5' => 'May' , '6' => 'June', '7' => 'July', '8' => 'August' , '9' => 'September' , '10' => 'October' , '11' => 'November' , '12' => 'December') ;
            
            
        $form->add  ('select', 'oc_month_start', ts('Start With Month'),
                     $month,
                     false);

 	$form->add  ('select', 'oc_month_end', ts('Ends With Month'),
                     $month,
                     false);
       

	$form->add( 'text',
                    'oc_day_start',
                    ts( ' Start With day' ) );

	$form->add( 'text',
                    'oc_day_end',
                    ts( ' End With day' ) );

/*

 	$form->add( 'date',
                    'oc_start_date',
                    ts('Date From'),
                    CRM_Core_SelectValues::date('custom', 10, 3 ) );
        $form->addRule('oc_start_date', ts('Select a valid date.'), 'qfDate');

        $form->add( 'date',
                    'oc_end_date',
                    ts('...through'),
                    CRM_Core_SelectValues::date('custom', 10, 0 ) );
        $form->addRule('oc_end_date', ts('Select a valid date.'), 'qfDate');

*/
        /**
         * If you are using the sample template, this array tells the template fields to render
         * for the search form.
         */
        $form->assign( 'elements', array( 'oc_month_start', 'oc_month_end', 'oc_day_start', 'oc_day_end') );


    }

    /**
     * Define the smarty template used to layout the search form and results listings.
     */
    function templateFile( ) {
       return 'CRM/Contact/Form/Search/Custom/Sample.tpl';
    }
       
    /**
      * Construct the search query
      */       
    function all( $offset = 0, $rowcount = 0, $sort = null,
                  $includeContactIDs = false, $onlyIDs = false ) {
        
        // SELECT clause must include contact_id as an alias for civicrm_contact.id
        
  
	
	/******************************************************************************/
	// Get data for contacts 

	if ( $onlyIDs ) {
        	$select  = "DISTINCT civicrm_contact.id as contact_id, civicrm_contact.display_name as name";
    	} else {
		$select = "DISTINCT civicrm_contact.id as contact_id, CONCAT( monthname(civicrm_contact.birth_date) , ' ',  day(civicrm_contact.birth_date)) as sort_name , civicrm_contact.display_name as name, 'birthday' as oc_type" ;

	}
	
	$from  = $this->from( );
 	$where = $this->where( $includeContactIDs ) ; 

	//$days_after_today = ($date_range_start_tmp + $date_range_end_tmp);
	//echo "<!--  date_range: " . $date_range . " -->";
        $sql = "
SELECT $select
FROM  $from
WHERE $where ";
//order by month(birth_date), oc_day";
	
	//for only contact ids ignore order.
      if ( !$onlyIDs ) {
          // Define ORDER BY for query in $sort, with default value
          if ( ! empty( $sort ) ) {
              if ( is_string( $sort ) ) {
                  $sql .= " ORDER BY $sort ";
              } else {
                  $sql .= " ORDER BY " . trim( $sort->orderBy() );
              }
          } else {
              $sql .=   "ORDER BY month(birth_date), day(civicrm_contact.birth_date)";
          }
      }

  	if ( $rowcount > 0 && $offset >= 0 ) {
            $sql .= " LIMIT $offset, $rowcount ";
        }


        return $sql;
    }
    
  function from(){
	return " civicrm_contact ";
}
 
  function where($includeContactIDs = false){ 

	$clauses = array( );

	$oc_month_start = $this->_formValues['oc_month_start'] ;
	$oc_month_end = $this->_formValues['oc_month_end'] ;	
	
	$oc_day_start = $this->_formValues['oc_day_start'];
	$oc_day_end = $this->_formValues['oc_day_end'];

	
	if( ($oc_month_start <> '' ) && is_numeric ($oc_month_start)){
		$clauses[] =  "month(birth_date) >= ".$oc_month_start ;
	}


	if( ($oc_month_end <> '' ) && is_numeric ($oc_month_end)){
		$clauses[]  = "month(birth_date) <= ".$oc_month_end;
	}



	if( ( $oc_day_start <> '') && is_numeric($oc_day_start) ){
		$clauses[] =  "day(birth_date) >= ".$oc_day_start;

	}

	if( ( $oc_day_end <> '') && is_numeric($oc_day_end) ){
		$clauses[] = "day(birth_date) <= ".$oc_day_end;

	}

	$clauses[] =  "birth_date IS NOT NULL";

	if ( $includeContactIDs ) {
         $contactIDs = array( );
         foreach ( $this->_formValues as $id => $value ) {
             if ( $value &&
                  substr( $id, 0, CRM_Core_Form::CB_PREFIX_LEN ) == CRM_Core_Form::CB_PREFIX ) {
                 $contactIDs[] = substr( $id, CRM_Core_Form::CB_PREFIX_LEN );
             }
         }

         if ( ! empty( $contactIDs ) ) {
                $contactIDs = implode( ', ', $contactIDs );
                $clauses[] = "contact_a.id IN ( $contactIDs )";
            }
        }
        
	 $partial_where_clause = implode( ' AND ', $clauses );

	return $partial_where_clause ;


 }	

    /* 
     * Functions below generally don't need to be modified
     */
    function count( ) {
           $sql = $this->all( );
           
           $dao = CRM_Core_DAO::executeQuery( $sql,
                                             CRM_Core_DAO::$_nullArray );
           return $dao->N;
    }
       
    function contactIDs( $offset = 0, $rowcount = 0, $sort = null) { 
        return $this->all( $offset, $rowcount, $sort, false, true );
    }
       
    function &columns( ) {
        return $this->_columns;
    }

   function setTitle( $title ) {
       if ( $title ) {
           CRM_Utils_System::setTitle( $title );
       } else {
           CRM_Utils_System::setTitle(ts('Search'));
       }
   }

   function summary( ) {
       return null;
   }

}