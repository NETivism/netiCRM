<?php

/**
* Function to send a tax receipt
* 
* @param object  $contribution            CRM_Contribute_BAO_Contribution object
* @param boolean $original_receipt        whether to send an original copy
* @author Matt Langeman
*
* @return void
* @access public
* @static
*/
function civicrm_pdf_receipts_generateTaxReceipt( $contribution, $original_receipt=true, $print_instead_of_email=false ) 
{ 
    $contactID = $contribution->contact_id;
    $pdf_receipt_files_path = dirname(__FILE__) . '/pdf_receipt_files/';
    
    // get Address information via contact
    $params = array('contact_id'=>$contactID);
    $defaults = array();

    require_once 'CRM/Contact/BAO/Contact.php';
    $contact = CRM_Contact_BAO_Contact::retrieve( $params, $defaults);
    $locations = $contact->address;

    // choose first location by default
    $address = $locations[1];

    // add actual names for province and country instead of just having ids
    require_once 'CRM/Core/BAO/Address.php';
    CRM_Core_BAO_Address::fixAddress($address);

    $address_line_1 = $address['street_address'];
    $address_line_2 = $address['city'] . ' ' . $address['state_province'] . ' ' . $address['postal_code'];
    $address_line_3 = $address['country'];

    list( $displayName, $email ) = CRM_Contact_BAO_Contact::getContactDetails( $contactID );
    $pos = strpos($contribution->receive_date, '-');
    if($pos === false) {
        $date = substr($contribution->receive_date, 0, 8);
        $display_date = substr($date,0,4). '-'.substr($date,4,2).'-'.substr($date,6,2);
    } else {
        $display_date = substr($contribution->receive_date, 0, 10);
    }
        
    $line_1 = "This is your Official Receipt for income tax purposes.";
    $padded_number = str_pad($contribution->id, 8, 0, STR_PAD_LEFT);
    $receipt_number = variable_get('pdf_receipts_prefix', '') . $padded_number;
    $pdf_file = file_directory_temp() . '/pdf-receipt-' . $padded_number . '.pdf';
    $user_friendly = 'receipt-' . $padded_number . '.pdf';

    require('ufpdf/ufpdf.php');

    $pdf = new UFPDF();
    $pdf->Open();
    $pdf->SetAuthor(variable_get('pdfreceipt_org_name', ''));
    $pdf->AddPage();

    $x_offset = 0;
    $y_offset = -7;
    if(!$original_receipt) $pdf->Image($pdf_receipt_files_path . 'duplicate_copy.png', $x_offset + 40, $y_offset + 35, 112, 90);
    $pdf->Image(variable_get('pdf_receipts_logo', $pdf_receipt_files_path . 'your-logo.png'), $x_offset + 10, $y_offset + 14, 70, 30);
    $pdf->SetFont('Arial', 'B', 8);

    $pdf->SetXY($x_offset + 85, $y_offset + 20);
    $pdf->Write(10, $line_1);
    $pdf->SetXY($x_offset + 130, $y_offset + 24);
    $pdf->Write(10, 'Thank You!');
    $pdf->SetXY($x_offset + 95, $y_offset + 28);
    $pdf->Write(10, 'Charitable Registration No. ' . variable_get('pdf_receipts_charitable_no', ''));
    $pdf->SetXY($x_offset + 20, $y_offset + 50);
    $pdf->Write(10, "Receipt No: " . $receipt_number);
    $pdf->SetX($x_offset + 95);

    $amount = $contribution->total_amount - $contribution->non_deductible_amount;
    $pdf->Write(10, "The Sum of:  $" . number_format($amount,2));

    $pdf->SetXY($x_offset + 20, $y_offset + 56);
    $pdf->Write(10, "Issue Date: ". $display_date);

    $pdf->SetX($x_offset + 95);
    $pdf->Write(10, "Received on: " . $display_date);

    $pdf->Image(variable_get('pdf_receipts_signature', $pdf_receipt_files_path . 'authorized-signature.png'), $x_offset + 25, $y_offset + 78, 30, 15);
    $pdf->Line($x_offset + 20, $y_offset + 88, $x_offset + 60, $y_offset + 88);
    $pdf->SetXY($x_offset + 22, $y_offset + 85);
    $pdf->SetFont("Arial", "I", 7);
    $pdf->Write(10, 'Authorized Signature');

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetXY($x_offset + 95, $y_offset + 62);
    $pdf->Write(10, "Received from: " . $displayName);
    $pdf->SetXY($x_offset + 128, $y_offset + 66);
    $pdf->Write(10, $address_line_1);
    $pdf->SetXY($x_offset + 128, $y_offset + 70);
    $pdf->Write(10, $address_line_2);
    $pdf->SetXY($x_offset + 128, $y_offset + 74);
    $pdf->Write(10, $address_line_3);

    $pdf->SetXY($x_offset + 30, $y_offset + 102);
    $pdf->Write(10, variable_get('pdf_receipts_org_name', ''));
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY($x_offset + 30, $y_offset + 106);
    $pdf->Write(10, variable_get('pdf_receipts_address_line1', ''));
    $pdf->SetXY($x_offset + 30, $y_offset + 110);
    $pdf->Write(10, variable_get('pdf_receipts_address_line2', ''));

    $pdf->SetXY($x_offset + 150, $y_offset + 102);
    $pdf->Write(10, 'Tel: ' . variable_get('pdf_receipts_tel', ''));
    $pdf->SetXY($x_offset + 149, $y_offset + 106);
    $pdf->Write(10, 'Fax: ' . variable_get('pdf_receipts_fax', ''));

    $pdf->SetXY($x_offset + 30, $y_offset + 116);
    $pdf->Write(10, 'Email: ' . variable_get('pdf_receipts_email', ''));
    $pdf->SetXY($x_offset + 30, $y_offset + 120);
    $pdf->Write(10, 'Web Site: ' . variable_get('pdf_receipts_web', ''));

    $pdf->SetXY($x_offset + 50, $y_offset + 130);
    $pdf->Write(10, 'Canadian Revenue Agency: www.cra-arc.gc.ca/charites');

    $pdf->Line($x_offset + 10, $y_offset + 145, $x_offset + 200, $y_offset + 145);

    // if original receipt, add a duplicate copy
    if( $original_receipt ) {
        $x_offset = 0;
        $y_offset = 133;

        $pdf->Image($pdf_receipt_files_path . 'duplicate_copy.png', $x_offset + 40, $y_offset + 35, 112, 90);
        $pdf->Image(variable_get('pdf_receipts_logo', $pdf_receipt_files_path . 'your-logo.png'), $x_offset + 10, $y_offset + 14, 70, 30);
        $pdf->SetFont('Arial', 'B', 8);

        $pdf->SetXY($x_offset + 85, $y_offset + 20);
        $pdf->Write(10, $line_1);
        $pdf->SetXY($x_offset + 130, $y_offset + 24);
        $pdf->Write(10, 'Thank You!');
        $pdf->SetXY($x_offset + 95, $y_offset + 28);
        $pdf->Write(10, 'Charitable Registration No. ' . variable_get('pdf_receipts_charitable_no', ''));

        $pdf->SetXY($x_offset + 20, $y_offset + 50);
        $pdf->Write(10, "Receipt No: " . $receipt_number);
    
        $pdf->SetX($x_offset + 95);
        $amount = $contribution->total_amount - $contribution->non_deductible_amount;
        $pdf->Write(10, "The Sum of:  $" . number_format($amount,2));

        $pdf->SetXY($x_offset + 20, $y_offset + 56);
        $pdf->Write(10, "Issue Date: ". $display_date);

        $pdf->SetX($x_offset + 95);
        $pdf->Write(10, "Received on: " . $display_date);

        $pdf->Image(variable_get('pdf_receipts_signature', $pdf_receipt_files_path . 'authorized-signature.png'), $x_offset + 25, $y_offset + 78, 30, 15);
        $pdf->Line($x_offset + 20, $y_offset + 88, $x_offset + 60, $y_offset + 88);
        $pdf->SetXY($x_offset + 22, $y_offset + 85);
        $pdf->SetFont("Arial", "I", 7);
        $pdf->Write(10, 'Authorized Signature');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetXY($x_offset + 95, $y_offset + 62);
        $pdf->Write(10, "Received from: " . $displayName);
        $pdf->SetXY($x_offset + 128, $y_offset + 66);
        $pdf->Write(10, $address_line_1);
        $pdf->SetXY($x_offset + 128, $y_offset + 70);
        $pdf->Write(10, $address_line_2);
        $pdf->SetXY($x_offset + 128, $y_offset + 74);
        $pdf->Write(10, $address_line_3);

        $pdf->SetXY($x_offset + 30, $y_offset + 102);
        $pdf->Write(10, variable_get('pdf_receipts_org_name', ''));
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetXY($x_offset + 30, $y_offset + 106);
        $pdf->Write(10, variable_get('pdf_receipts_address_line1', ''));
        $pdf->SetXY($x_offset + 30, $y_offset + 110);
        $pdf->Write(10, variable_get('pdf_receipts_address_line2', ''));

        $pdf->SetXY($x_offset + 150, $y_offset + 102);
        $pdf->Write(10, 'Tel: ' . variable_get('pdf_receipts_tel', ''));
        $pdf->SetXY($x_offset + 149, $y_offset + 106);
        $pdf->Write(10, 'Fax: ' . variable_get('pdf_receipts_fax', ''));
    
        $pdf->SetXY($x_offset + 30, $y_offset + 116);
        $pdf->Write(10, 'Email: ' . variable_get('pdf_receipts_email', ''));
        $pdf->SetXY($x_offset + 30, $y_offset + 120);
        $pdf->Write(10, 'Web Site: ' . variable_get('pdf_receipts_web', ''));
    
        $pdf->SetXY($x_offset + 50, $y_offset + 130);
        $pdf->Write(10, 'Canadian Revenue Agency: www.cra-arc.gc.ca/charites');
    }

    $pdf->Close();

    if(!$print_instead_of_email) {
        $pdf->Output($pdf_file, 'F');
        return array($pdf_file, $user_friendly);
    } else {
        while(@ob_end_clean());
        $pdf->Output($pdf_file, 'D');
        exit();
    }
}

function civicrm_pdf_receipts_sendTaxReceipt( &$contribution, $original_receipt = false )
{
    // grab the various objects that are relevant to the mail message...
    require_once 'CRM/Contribute/DAO/ContributionType.php';
    $contributionType = & new CRM_Contribute_DAO_ContributionType();
    $contributionType->id = $contribution->contribution_type_id;
    if ( ! $contributionType->find( true ) ) {
      CRM_Core_Error::fatal( "Could not find corresponding contribution type." );
      return;
    }

    require_once 'CRM/Contribute/DAO/ContributionPage.php';
    $contributionPage = & new CRM_Contribute_DAO_ContributionPage();
    $contributionPage->id = $contribution->contribution_page_id;
    if ( ! $contributionPage->find( true ) ) {
      CRM_Core_Error::fatal( "Could not find corresponding contribution page." );
      return;
    }

    require_once 'CRM/Contact/BAO/Contact.php';
    list( $displayName, $email ) = CRM_Contact_BAO_Contact::getContactDetails( $contribution->contact_id );

    // generate the receipt
    list($pdf_file, $user_friendly) = civicrm_pdf_receipts_generateTaxReceipt( $contribution, $original_receipt );
    $attachment = array(
        'fullPath' => $pdf_file,
        'mime_type' => 'application/pdf',
        'cleanName' => $user_friendly,
    );

    // form a mailParams array to pass to the CiviCRM mail utility
    $mailParams = array(
        'from' => $contributionPage->receipt_from_name . ' <' . $contributionPage->receipt_from_email . '> ',
        'toName' => $displayName,
        'toEmail' => $email,
        'attachments' => array( $attachment ),
        'subject' => t('Your Receipt'),
        'text' => $contributionPage->receipt_text,
        'returnPath' => $contributionPage->receipt_from_email,
    );

    $ret = CRM_Utils_Mail::send( $mailParams );
    civicrm_pdf_receipts_cleanup( $pdf_file );
    if ( $ret === false ) {
        CRM_Core_Error::fatal( "Could not send email.  Check your mail settings." );
        return;
    }
}

function civicrm_pdf_receipts_cleanup($pdf_file)
{
    unlink($pdf_file);
}

function civicrm_pdf_receipts_taxReceiptSent( &$contribution ) {

    $query = "
SELECT contribution_id
  FROM civicrm_pdf_receipts
 WHERE contribution_id = %1 AND tax_receipt_date IS NOT NULL";

    $dao =& CRM_Core_DAO::executeQuery( $query, array( 1 => array( $contribution->id, "Integer" ) ) );
    return $dao->fetch();

}

function civicrm_pdf_receipts_recordTaxReceipt( &$contribution ) {

    $query = "
INSERT INTO civicrm_pdf_receipts (contribution_id, tax_receipt_date)
VALUES (%1, %2)";

    $params = array( 1 => array( $contribution->id, "Integer" ),
                     2 => array( CRM_Utils_Date::isoToMysql( $contribution->receive_date ), "Timestamp" ) );
    $dao =& CRM_Core_DAO::executeQuery( $query, $params );

}

?>
