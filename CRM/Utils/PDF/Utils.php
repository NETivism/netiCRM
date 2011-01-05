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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */


class CRM_Utils_PDF_Utils {

    static function domlib( $text,
                            $fileName = 'civicrm.pdf',
                            $output = false,
                            $orientation = 'landscape',
                            $paperSize   = 'a3' ) {
                            print 'domlib!!!!!!!!!!!!!!!!!!!!!!';
        /*
        require_once 'packages/dompdf/dompdf_config.inc.php';
        $dompdf = new DOMPDF( );
        
        $values = array( );
        if ( ! is_array( $text ) ) {
            $values =  array( $text );
        } else {
            $values =& $text;
        }

        $first = true;
        
        $html = '
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title></title>
    <style>
      .page_break {
        page-break-before: always;
      }
    </style>
  </head>
  <body>';

        $htmlElementstoStrip = array(
                                     '@<head[^>]*?>.*?</head>@siu',
                                     '@<body>@siu',
                                     '@</body>@siu',
                                     '@<html[^>]*?>@siu',
                                     '@</html>@siu',
                                     '@<!DOCTYPE[^>]*?>@siu',
                                     );
        $htmlElementsInstead = array("","","","","","");                     
        
        foreach ( $values as $value ) {
            if ( $first ) {
                $first = false;
                $pattern = '@<html[^>]*?>.*?<body>@siu';
                preg_match($pattern, $value['html'], $matches);
                //If there is a header in the template it will be used instead of the default header
                $html = $matches[0] ? $matches[0] : nothing;
                //$html .= "<h2>{$value['to']}: {$value['subject']}</h2><p>"; //If needed it should be generated through the message template
            } else {
                $html .= "<div style=\"page-break-after: always\"></div>";
                //$html .= "<h2 class=\"page_break\">{$value['to']}: {$value['subject']}</h2><p>"; //If needed it should be generated through the message template
            }
            if ( $value['html'] ) {
                //Strip the header from the template to avoid multiple headers within one document causing invalid html
                $value['html'] = preg_replace( $htmlElementstoStrip,
                                               $htmlElementsInstead,
                                               $value['html'] );
                $html .= "{$value['html']}\n";              
            } else {
                $html .= "{$value['body']}\n";
            }
        }

        $html .= '
          </body>
        </html>';
                        
        $dompdf->load_html( $html );
        $dompdf->set_paper ($paperSize, $orientation);
        $dompdf->render( );
        
        if ( $output ) {
            return $dompdf->output( );
        } else {
            $dompdf->stream( $fileName );
        }
        */
    }

    static function html2pdf( $text,
                              $fileName = 'civicrm.pdf',
                              $orientation = 'landscape',
                              $paperSize   = 'a4',
                              $output = false ) {
        require_once 'tcpdf/tcpdf.php';
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, $paperSize, true, 'UTF-8', false);

        // set default header data
        //$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 049', PDF_HEADER_STRING);

        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        //set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        //set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        //set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set font
        $pdf->SetFont('arialunicid0', '', 10);

        // add a page
        $pdf->AddPage();

        $values = array( );
        if ( ! is_array( $text ) ) {
            $values =  array( $text );
        } else {
            $values =& $text;
        }


        $htmlElementstoStrip = array(
          '@<script[^>]*?>.*?</script>@si',
          '@<style[^>]*?>.*?</style>@siU', 
          '/font-family:[^;]+;/iU',
          '/font:[^;]+;/iU',
        );
        
        foreach ( $values as $value ) {
            $html .= "{$value}\n";
            $html = preg_replace( $htmlElementstoStrip, "", $value );
        }
        $html = str_replace('src="http://'.$_SERVER['HTTP_HOST']."/", 'src="', $html);
        $style = '
<style>
h1 {
  color: #000000;
  font-size: 18pt;
  text-decoration: underline;
  text-align: center;
  padding: 0;
  margin: 0;
}
table { 
  color: #333333;
  font-size: 10pt;
  border: 1px solid #aaaaaa;
  background-color: #efefef;
}
td {
  font-size: 10pt;
  padding: 3px;
  border: 1px solid #cccccc;
  background-color: #ffffff;
}
th {
  font-size: 10pt;
  text-align: center;
  padding: 3px;
  background-color: #efefef;
}
</style>';
        $html = $style."\n".$html;

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->lastPage();

        if($output){
          return $pdf->Output( $fileName ,'S');
        }
        else{
          $pdf->Output( $fileName ,'D');
        }
    }
}
