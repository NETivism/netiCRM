<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
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

/*
* Copyright (C) 2010 Tech To The People
* Licensed to CiviCRM under the Academic Free License version 3.0.
*
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */




/**
 * This class print the name badges for the participants
 * It isn't supposed to be called directly, but is the parent class of the classes in CRM/Event/Badges/XXX.php
 *
 */
class CRM_Event_Badge {
  /**
   * @var array<string, int[]|float|string>
   */
  public $style;
  /**
   * @var string
   */
  public $format;
  public $imgExtension;
  public $imgRes;
  public $event;
  public $debug;
  /**
   * @var int|string
   */
  public $border;
  public $pdf;
  /**
   * @var float|int
   */
  public $lMarginLogo;
  function __construct() {
    $this->style = ['width' => 0.1, 'cap' => 'round', 'join' => 'round', 'dash' => '2,2', 'color' => [0, 0, 200]];
    $this->format = '5160';
    $this->imgExtension = 'png';
    $this->imgRes = 300;
    $this->event = NULL;
    $this->setDebug(FALSE);
  }

  function setDebug($debug = TRUE) {
    if (!$debug) {
      $this->debug = FALSE;
      $this->border = 0;
    }
    else {
      $this->debug = TRUE;
      $this->border = "LTRB";
    }
  }

  /**
   * function to create the labels (pdf)
   * It assumes the participants are from the same event
   *
   * @param   array    $participants
   *
   * @return  null
   * @access  public
   */
  public function run(&$participants) {
    // fetch the 1st participant, and take her event to retrieve its attributes
    $participant = reset($participants);
    $eventID = $participant['event_id'];
    $this->event = self::retrieveEvent($eventID);
    //call function to create labels
    $this->createLabels($participants);
    CRM_Utils_System::civiExit(1);
  }

  protected function retrieveEvent($eventID) {

    $bao = new CRM_Event_BAO_Event();
    if ($bao->get('id', $eventID)) {
      return $bao;
    }
    return NULL;
  }

  function getImageFileName($eventID, $img = FALSE) {
    global $civicrm_root;

    $config = CRM_Core_Config::singleton();
    $path = "CRM/Event/Badge";
    if ($img === FALSE) {
      return FALSE;
    }
    elseif ($img === TRUE) {
      $img = get_class($this) . "." . $this->imgExtension;
    }
    elseif (preg_match('/^https?:\/\//i', $img)) {
      $imgContent = file_get_contents($img);
      if ($imgContent) {
        $imgFile = CRM_Utils_System::cmsDir('temp').'/badge_'.basename($img);
        file_put_contents($imgFile, $imgContent);
        return $imgFile;
      }
      return FALSE;
    }

    $imgFile = $config->customTemplateDir . "/$path/$eventID/$img";
    if (file_exists($imgFile)) {
      return $imgFile;
    }
    $imgFile = $config->customTemplateDir . "/$path/$img";
    if (file_exists($imgFile)) {
      return $imgFile;
    }

    $imgFile = "$civicrm_root/templates/$path/$eventID/$img";
    if (file_exists($imgFile)) {
      return $imgFile;
    }
    $imgFile = "$civicrm_root/templates/$path/$img";
    if (!file_exists($imgFile) && !$this->debug) {
      return FALSE;
    }

    // not sure it exists, but at least will display a meaniful fatal error in debug mode
    return $imgFile;
  }

  function printBackground($img = FALSE) {
    $x = $this->pdf->GetAbsX();
    $y = $this->pdf->GetY();
    if ($this->debug) {
      $this->pdf->Rect($x, $y, $this->pdf->width, $this->pdf->height, 'D', ['all' => ['width' => 1, 'cap' => 'round', 'join' => 'round', 'dash' => '2,10', 'color' => [255, 0, 0]]]);
    }
    $img = $this->getImageFileName($this->event->id, $img);
    if ($img) {
      $imgsize = @getimagesize($img);
      if (!empty($imgsize)) {
        // mm
        $f = $this->imgRes / 25.4;
        $w = $imgsize[0] / $f;
        $h = $imgsize[1] / $f;
        $newH = 10; // Use 1 cm height for the card.
        $newW = $w * $newH / $h;
        $this->lMarginLogo = $newW + 4;
        $this->pdf->Image($img, $this->pdf->GetAbsX()+2, $this->pdf->GetY()+2, $newW, $newH, strtoupper($this->imgExtension), '', '', FALSE, 75, '', FALSE, FALSE, $this->debug, TRUE, FALSE, FALSE);
      }
      else {
        $this->lMarginLogo = 2;
      }
    }
    $this->pdf->SetXY($x, $y);
  }

  /**
   * this is supposed to be overrided
   **/
  public function generateLabel($participant) {
    $txt = "{$this->event['title']}
{$participant['first_name']} {$participant['last_name']}
{$participant['current_employer']}";

    $this->pdf->MultiCell($this->pdf->width, $this->pdf->lineHeight, $txt);
  }

  function pdfExtraFormat() {}

  /**
   * function to create labels (pdf)
   *
   * @param   array    $contactRows   assciated array of contact data
   * @param   string   $format   format in which labels needs to be printed
   *
   * @return  null
   * @access  public
   */
  function createLabels(&$participants) {


    $this->pdf = new CRM_Utils_PDF_Label($this->format, 'mm');
    $this->pdfExtraFormat();
    $this->pdf->Open();
    $this->pdf->setPrintHeader(FALSE);
    $this->pdf->setPrintFooter(FALSE);
    $this->pdf->AddPage();
    //$this->pdf->AddFont( 'DejaVu Sans', '', 'DejaVuSans.php' );
    //$this->pdf->SetFont( 'DejaVu Sans' );
    $this->pdf->SetGenerator($this, "generateLabel");

    foreach ($participants as $participant) {
      $this->pdf->AddPdfLabel($participant);
    }
    $this->pdf->Output($this->event->title . '.pdf', 'I');
  }
}

