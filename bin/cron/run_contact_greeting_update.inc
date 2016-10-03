<?php
/**
 * Update contact greeting
 */
function run_contact_greeting_update($limit = 100){
  date_default_timezone_set("Asia/Taipei");
  require_once 'CRM/Utils/Request.php';
  require_once 'CRM/Core/PseudoConstant.php';
  require_once 'CRM/Contact/BAO/Contact.php';
  require_once 'CRM/Core/OptionGroup.php';
  require_once 'CRM/Mailing/BAO/Mailing.php';
  require_once 'CRM/Utils/Token.php';

  $config = CRM_Core_Config::singleton();
  $process = array(
     'Individual' =>  array( 'email_greeting', 'postal_greeting', 'addressee' ),
     'Household' =>  array( 'email_greeting', 'postal_greeting', 'addressee' ),
     'Organization' =>  array( 'addressee' ),
  );
    
  foreach($process as $contactType => $greetings){
    foreach($greetings as $greeting){
      // if valueID is not passed use default value 
      $contactTypeFilters = array( 1 => 'Individual', 2 => 'Household', 3 => 'Organization' );
      $filter = CRM_Utils_Array::key( $contactType, $contactTypeFilters );
      $defaulValueID = CRM_Core_OptionGroup::values( $greeting, null, null, null, " AND is_default = 1 AND ( filter = {$filter} OR filter = 0 )", "value");
      $valueID = array_pop( $defaulValueID );
      $filter =  array( 'contact_type'  => $contactType, 'greeting_type' => $greeting );
      $allGreetings   = CRM_Core_PseudoConstant::greeting( $filter );            
      $originalGreetingString = $greetingString = CRM_Utils_Array::value( $valueID, $allGreetings );
      if ( !$greetingString ) {
        // CRM_Core_Error::fatal( ts('Incorrect greeting value id %1.', array( 1 => $valueID ) ) );
      }

      // build return properties based on tokens
      $greetingTokens = CRM_Utils_Token::getTokens( $greetingString );
      $tokens = CRM_Utils_Array::value( 'contact', $greetingTokens );
      $greetingsReturnProperties = array( );
      if ( is_array( $tokens ) ) {
        $greetingsReturnProperties = array_fill_keys( array_values( $tokens ), 1 );
      }

      //FIXME : apiQuery should handle these clause.
      $filterContactFldIds = $filterIds = array( );
      $idFldName = $displayFldName = null;
      if ( $greeting == 'email_greeting' || $greeting == 'postal_greeting' ||  $greeting == 'addressee' ) {
        $idFldName = $greeting . '_id';
        $displayFldName = $greeting . '_display';
      }

      $filterContactFldIds = array();
      if ( $idFldName ) {
        $sql = "SELECT DISTINCT id, $idFldName FROM civicrm_contact WHERE contact_type = %1 AND ( {$idFldName} IS NULL OR ( {$idFldName} IS NOT NULL AND {$displayFldName} IS NULL ) )";
        if($limit > 0){
          $sql .= ' LIMIT %2';
        }

        $dao = CRM_Core_DAO::executeQuery( $sql, array( 1 => array( $contactType, 'String' ) , 2 => array($limit, 'Integer')) );
        while ( $dao->fetch( ) ) {
          $filterContactFldIds[$dao->id] = $dao->$idFldName;
        }
      }

      // retrieve only required contact information
      $extraParams[] = array( 'contact_type', '=', $contactType, 0, 0 );
      list($greetingDetails) = CRM_Mailing_BAO_Mailing::getDetails( array_keys( $filterContactFldIds ), $greetingsReturnProperties, false, false, $extraParams );
      // perform token replacement and build update SQL
      $contactIds = $allContactIds = array( );
      $cacheFieldQuery = "UPDATE civicrm_contact SET {$greeting}_display = CASE id ";

      foreach($filterContactFldIds as $contactID => $greetingSetting){
        $first_name = preg_replace("/[a-z0-9]/i", "", $greetingDetails[$contactID]['first_name']);
        $last_name = preg_replace("/[a-z0-9]/i", "", $greetingDetails[$contactID]['last_name']);
        if(!$first_name && !$last_name && $contactType == 'Individual'){ // is english name
          $greetingString = '親愛的 {contact.first_name} {contact.last_name}';
        }
        elseif ( $greetingBuffer = CRM_Utils_Array::value($greetingSetting, $allGreetings) ) {
          $greetingString = $greetingBuffer;
        }
        else{
          $greetingString = $originalGreetingString;
        }

        if($greetingString){
          $allContactIds[] = $contactID;
          CRM_Utils_Token::replaceGreetingTokens($greetingString, $contactDetails, $contactID );
          $greetingString = CRM_Core_DAO::escapeString( $greetingString );
          $cacheFieldQuery .= " WHEN {$contactID} THEN '{$greetingString}' ";
        }
      }
      
      if ( !empty( $allContactIds ) ) {
        $cacheFieldQuery .= " ELSE {$greeting}_display END;"; 
        if ( !empty( $contactIds ) ) {
            // need to update greeting _id field.
          $queryString = "UPDATE civicrm_contact SET {$greeting}_id = {$valueID} WHERE id IN (" . implode( ',', $contactIds ) . ")";
          CRM_Core_DAO::executeQuery( $queryString );
        }
        // now update cache field
        CRM_Core_DAO::executeQuery( $cacheFieldQuery );
      }
    }
  }
    
}
