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


/*
* Copyright (C) 2010 Tech To The People
* Licensed to CiviCRM under the Academic Free License version 3.0.
*
*/

/**
 *
 * @package CRM
 *
 */

require_once 'CRM/Event/Badge.php';
require_once 'CRM/Utils/Date.php';
class CRM_Event_Badge_Simple extends CRM_Event_Badge {
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
    $this->tMarginName = 20;
  }

  public function generateLabel($participant) {
    $x = $this->pdf->GetAbsX();
    $y = $this->pdf->GetY();
    $this->pdf->SetLineStyle(array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(200, 200, 200)));
    $this->pdf->Cell($this->format['width'], $this->format['height'], '', 1);

    $this->pdf->SetFontSize(8);
    $this->pdf->MultiCell($this->pdf->width - 4, 0, $participant['event_title'], $this->border, "L", 0, 1, $x + 2, $y + 2);

    $this->pdf->SetXY($x, $y + $this->pdf->height - 5);

    $this->pdf->SetFontSize(15);
    $this->pdf->MultiCell($this->pdf->width, 10, $participant['sort_name'], $this->border, "C", 0, 1, $x, $y + $this->tMarginName);
    $this->pdf->SetFontSize(10);
    $this->pdf->MultiCell($this->pdf->width, 0, $participant['current_employer'], $this->border, "C", 0, 1, $x, $this->pdf->getY());
  }
}

