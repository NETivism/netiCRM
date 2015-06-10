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

  static function domlib($text, $fileName = 'output.pdf', $output = FALSE, $orientation = 'landscape', $paperSize = 'a3') {
    return self::html2pdf($text, $fileName, $orientation, $paperSize, $output);
  }

  static function html2pdf($text, $fileName = 'output.pdf', $orientation = 'landscape', $paperSize = 'a4', $output = FALSE) {
    $config = CRM_Core_Config::singleton();

    // make whole html first
    $values = array();
    if (is_array($text)) {
      $values = &$text;
    }
    else {
      $values = array($text);
    }

    // use system wkhtmltopdf to solve everything
    $html = self::makeHTML($values, FALSE);

    if ($config->wkhtmltopdfPath) {
      $option = "--page-size '$paperSize'";
      $pdf = self::wkhtmltopdf($html, $option);
      if ($output) {
        header('Content-type: application/pdf');
        return file_get_contents($pdf);
      }
      else {
        header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename=' . $fileName);
        header('Pragma: no-cache');
        echo file_get_contents($pdf);
      }
      return;
    }
    else {
      // or fallback to tcpdf version
      require_once 'tcpdf/tcpdf.php';
      $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, $paperSize, TRUE, 'UTF-8', FALSE);

      // set header and footer fonts
      $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
      $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
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

      $html = self::makeHTML($values, TRUE);
      $html = str_replace('src="http://' . $_SERVER['HTTP_HOST'] . "/", 'src="', $html);
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
      $html = $style . "\n" . $html;
      $pdf->writeHTML($html, TRUE, FALSE, TRUE, FALSE, '');
      $pdf->lastPage();

      if ($output) {
        return $pdf->Output($fileName, 'S');
      }
      else {
        $pdf->Output($fileName, 'D');
      }
    }
  }

  /**
   * Generate pdf from static version of wkhtmltopdf
   *
   * @ $html string
   * source html.
   *
   * @ $option string
   * see /usr/local/bin/wkhtmltopdf-i386 --help
   */
  function wkhtmltopdf($html, $option = '-n') {
    $config = CRM_Core_Config::singleton();
    $wkhtmltopdf = $config->wkhtmltopdfPath;

    if (exec("test -x $wkhtmltopdf && echo 1")) {
      $temp_prefix = 'pdf_';
      $temp_dir = '/tmp';
      $dest = tempnam($temp_dir, $temp_prefix);
      if (preg_match('/^http:\/\//i', $html)) {
        $source = $html;
      }
      else {
        $source = tempnam($temp_dir, 'pdfsrc_') . '.htm';
        while (file_exists($source)) {
          $source = tempnam($temp_dir, 'pdfsrc_') . '.htm';
        }
        file_put_contents($source, $html);
        // release memory before wkhtmltopdf
        unset($html);
      }
      $exec = $wkhtmltopdf . escapeshellcmd(" $option $source $dest");
      exec($exec);

      return $dest;
    }
    else {
      return FALSE;
    }
  }

  public function makeHTML($values, $strip_html = FALSE) {
    $html = '';
    foreach ($values as $k => $value) {
      if ($k) {
        $html .= '<br pagebreak="true"/>' . "\n";
      }
      if (is_array($value)) {
        // If needed it should be generated through the message template
        $html .= "<h2>{$value['to']}: {$value['subject']}</h2>";
        if ($strip_html) {
          $html .= self::stripHTML($value['html']);
        }
        else {
          $html .= $value['html'];
        }
      }
      else {
        if ($strip_html) {
          $html .= self::stripHTML($value);
        }
        else {
          $html .= $value;
        }
      }
    }
    return $html;
  }

  public function stripHTML($html) {
    $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $html);
    $html = preg_replace('/(id|style)="[^"].+"/i', "", $html);
    $html = preg_replace("/(id|style)='[^'].+'/i", "", $html);
    $html = preg_replace("/<!--(.*?)-->/Us", "", $html);

    $dom = new DOMDocument();
    $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8"));
    $xpath = new DOMXPath($dom);

    $ele_a = $xpath->query('//style');
    for ($i = 0; $i < $ele_a->length; $i++) {
      self::DOMRemove($ele_a->item($i));
    }
    $html_new = $dom->saveXML($xpath->query('//body')->item(0));
    $html_new = preg_replace("/(<body[^>]+>)|(<\/body>)/i", '', $html_new);
    return $html_new;
  }

  private function DOMRemove(DOMNode$from) {
    $sibling = $from->firstChild;
    do {
      $next = $sibling->nextSibling;
      $from->parentNode->insertBefore($sibling, $from);
    } while ($sibling = $next);
    $from->parentNode->removeChild($from);
  }
}

