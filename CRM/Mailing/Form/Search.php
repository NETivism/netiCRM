<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */


class CRM_Mailing_Form_Search extends CRM_Core_Form {

  public function preProcess() {
    parent::preProcess();
  }

  public function buildQuickForm() {
    $this->add('text', 'mailing_name', ts('Mailing Name'),
      CRM_Core_DAO::getAttribute('CRM_Mailing_DAO_Mailing', 'title')
    );
    $this->add('text', 'mailing_subject', ts('Mailing Subject'), CRM_Core_DAO::getAttribute('CRM_Mailing_DAO_Mailing', 'subject'));

    $this->addDate('mailing_from', ts('From'), FALSE, ['formatType' => 'searchDate']);
    $this->addDate('mailing_to', ts('To'), FALSE, ['formatType' => 'searchDate']);

    $this->add('text', 'sort_name', ts('Created or Sent by'));

    /*

    CRM_Campaign_BAO_Campaign::addCampaignInComponentSearch($this);
*/


    $status = [
      '' => ts('- none -'),
      'Scheduled' => ts('Scheduled'),
      'Complete' => ts('Complete'),
      'Running' => ts('Running'),
    ];
    $this->addElement('select', 'mailing_status', NULL, $status);

    $this->addButtons([
        [
          'type' => 'refresh',
          'name' => ts('Search'),
          'isDefault' => TRUE,
        ],
      ]);
  }

  function setDefaultValues() {
    $defaults = [];
    foreach ([
        'Scheduled', 'Complete', 'Running',
      ] as $status) {
      $defaults['mailing_status'][$status] = 1;
    }
    return $defaults;
  }

  function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    $parent = $this->controller->getParent();
    if (!empty($params)) {
      $fields = ['mailing_name', 'mailing_from', 'mailing_to', 'sort_name', 'campaign_id', 'mailing_status', 'mailing_subject'];
      foreach ($fields as $field) {
        if (isset($params[$field]) &&
          !CRM_Utils_System::isNull($params[$field])
        ) {
          if (in_array($field, [
                'mailing_from', 'mailing_to',
              ])) {
            $time = ($field == 'mailing_to') ? '235959' : NULL;
            $parent->set($field, CRM_Utils_Date::processDate($params[$field], $time));
          }
          else {
            $parent->set($field, $params[$field]);
          }
        }
        else {
          $parent->set($field, NULL);
        }
      }
    }
  }
}

