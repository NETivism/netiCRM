<?php


class CRM_Event_Badge_Logo extends CRM_Event_Badge {
  public $format;
  public $lMarginLogo;
  public $tMarginName;
  public $logo;
  public $pdf;
  public $border;
  function __construct() {
    parent::__construct();
    $config = CRM_Core_Config::singleton();
    // A4
    $pw = 210;
    $ph = 297;
    $h = 60;
    $w = 90;
    $this->format = ['name' => 'Sigel 3C', 'paper-size' => 'A4', 'metric' => 'mm', 'lMargin' => ($pw - $w * 2) / 2,
      'tMargin' => ($ph - $h * 4) / 2, 'NX' => 2, 'NY' => 4, 'SpaceX' => 0, 'SpaceY' => 0,
      'width' => $w, 'height' => $h, 'font-size' => 12,
    ];
    $this->lMarginLogo = 20;
    $this->tMarginName = 20;
    $receipt_logo = $config->receiptLogo;
    if ($receipt_logo && !(substr($receipt_logo, 0, 7) == 'http://' || substr($receipt_logo, 0, 8) == 'https://')) {
      $receipt_logo = $config->imageUploadDir . $receipt_logo;
    }
    $this->logo = $receipt_logo;
  }

  public function generateLabel($participant) {
    $x = $this->pdf->GetAbsX();
    $y = $this->pdf->GetY();
    $y += 2;
    $this->printBackground($this->logo);
    $this->pdf->SetLineStyle(['width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => [200, 200, 200]]);
    $this->pdf->Cell($this->format['width'], $this->format['height'], '', 1);

    $this->pdf->SetFontSize(8);
    $this->pdf->MultiCell($this->pdf->width - $this->lMarginLogo, 0, $participant['event_title'], $this->border, "R", 0, 1, $x + $this->lMarginLogo, $y); // Add 0.5 cm padding between event title and logo.

    $this->pdf->SetXY($x, $y + $this->pdf->height - 5);

    $this->pdf->SetFontSize(26);
    $this->pdf->MultiCell($this->pdf->width, 10, $participant['sort_name'], $this->border, "C", 0, 1, $x, $y + $this->tMarginName);
    $this->pdf->SetFontSize(14);
    $this->pdf->MultiCell($this->pdf->width, 0, $participant['current_employer'], $this->border, "C", 0, 1, $x, $this->pdf->getY());
  }
}

