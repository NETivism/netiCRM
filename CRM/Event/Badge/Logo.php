<?php
require_once 'CRM/Event/Badge.php';
require_once 'CRM/Utils/Date.php';
class CRM_Event_Badge_Logo extends CRM_Event_Badge {
  function __construct() {
    parent::__construct();
    $config = CRM_Core_Config::singleton();
    // A4
    $pw = 210;
    $ph = 297;
    $h = 60;
    $w = 90;
    $this->format = array('name' => 'Sigel 3C', 'paper-size' => 'A4', 'metric' => 'mm', 'lMargin' => ($pw - $w * 2) / 2,
      'tMargin' => ($ph - $h * 4) / 2, 'NX' => 2, 'NY' => 5, 'SpaceX' => 0, 'SpaceY' => 0,
      'width' => $w, 'height' => $h, 'font-size' => 12,
    );
    $this->lMarginLogo = 20;
    $this->tMarginName = 20;
    $this->logo = $config->receiptLogo;
  }

  public function generateLabel($participant) {
    $x = $this->pdf->GetAbsX();
    $y = $this->pdf->GetY();
    $y += 2;
    $this->printBackground($this->logo);
    $this->pdf->SetLineStyle(array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(200, 200, 200)));
    $this->pdf->Cell($this->format['width'], $this->format['height'], '', 1);

    $this->pdf->SetFontSize(8);
    $this->pdf->MultiCell($this->pdf->width - $this->lMarginLogo, 0, $participant['event_title'], $this->border, "L", 0, 1, $x + $this->lMarginLogo, $y);

    $this->pdf->SetXY($x, $y + $this->pdf->height - 5);

    $this->pdf->SetFontSize(15);
    $this->pdf->MultiCell($this->pdf->width, 10, $participant['sort_name'], $this->border, "C", 0, 1, $x, $y + $this->tMarginName);
    $this->pdf->SetFontSize(10);
    $this->pdf->MultiCell($this->pdf->width, 0, $participant['current_employer'], $this->border, "C", 0, 1, $x, $this->pdf->getY());
  }
}

