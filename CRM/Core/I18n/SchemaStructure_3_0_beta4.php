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
class CRM_Core_I18n_SchemaStructure_3_0_beta4 {
  static function &columns() {
    static $result = NULL;
    if (!$result) {
      $result = [
        'civicrm_option_group' => [
          'label' => 'varchar(255)',
          'description' => 'varchar(255)',
        ],
        'civicrm_price_set' => [
          'title' => 'varchar(255)',
          'help_pre' => 'text',
          'help_post' => 'text',
        ],
        'civicrm_contact' => [
          'sort_name' => 'varchar(128)',
          'display_name' => 'varchar(128)',
          'first_name' => 'varchar(64)',
          'middle_name' => 'varchar(64)',
          'last_name' => 'varchar(64)',
          'email_greeting_display' => 'varchar(255)',
          'postal_greeting_display' => 'varchar(255)',
          'addressee_display' => 'varchar(255)',
          'household_name' => 'varchar(128)',
          'organization_name' => 'varchar(128)',
        ],
        'civicrm_mailing_component' => [
          'name' => 'varchar(64)',
          'subject' => 'varchar(255)',
          'body_html' => 'text',
          'body_text' => 'text',
        ],
        'civicrm_mailing' => [
          'name' => 'varchar(128)',
          'from_name' => 'varchar(128)',
          'subject' => 'varchar(128)',
          'body_text' => 'longtext',
          'body_html' => 'longtext',
        ],
        'civicrm_premiums' => [
          'premiums_intro_title' => 'varchar(255)',
          'premiums_intro_text' => 'text',
        ],
        'civicrm_product' => [
          'name' => 'varchar(255)',
          'description' => 'text',
          'options' => 'text',
        ],
        'civicrm_membership_type' => [
          'name' => 'varchar(128)',
          'description' => 'varchar(255)',
        ],
        'civicrm_membership_status' => [
          'name' => 'varchar(128)',
        ],
        'civicrm_participant_status_type' => [
          'label' => 'varchar(255)',
        ],
        'civicrm_tell_friend' => [
          'title' => 'varchar(255)',
          'intro' => 'text',
          'suggested_message' => 'text',
          'thankyou_title' => 'varchar(255)',
          'thankyou_text' => 'text',
        ],
        'civicrm_custom_group' => [
          'title' => 'varchar(64)',
          'help_pre' => 'text',
          'help_post' => 'text',
        ],
        'civicrm_custom_field' => [
          'label' => 'varchar(255)',
          'help_pre' => 'text',
          'help_post' => 'text',
        ],
        'civicrm_option_value' => [
          'label' => 'varchar(255)',
          'description' => 'varchar(255)',
        ],
        'civicrm_price_field' => [
          'label' => 'varchar(255)',
          'help_pre' => 'text',
          'help_post' => 'text',
        ],
        'civicrm_contribution_page' => [
          'title' => 'varchar(255)',
          'intro_text' => 'text',
          'pay_later_text' => 'text',
          'pay_later_receipt' => 'text',
          'thankyou_title' => 'varchar(255)',
          'thankyou_text' => 'text',
          'thankyou_footer' => 'text',
          'for_organization' => 'text',
          'receipt_from_name' => 'varchar(255)',
          'receipt_text' => 'text',
          'footer_text' => 'text',
          'honor_block_title' => 'varchar(255)',
          'honor_block_text' => 'text',
        ],
        'civicrm_membership_block' => [
          'new_title' => 'varchar(255)',
          'new_text' => 'text',
          'renewal_title' => 'varchar(255)',
          'renewal_text' => 'text',
        ],
        'civicrm_uf_group' => [
          'title' => 'varchar(64)',
          'help_pre' => 'text',
          'help_post' => 'text',
        ],
        'civicrm_uf_field' => [
          'help_post' => 'text',
          'label' => 'varchar(255)',
        ],
        'civicrm_address' => [
          'street_address' => 'varchar(96)',
          'supplemental_address_1' => 'varchar(96)',
          'supplemental_address_2' => 'varchar(96)',
          'supplemental_address_3' => 'varchar(96)',
          'city' => 'varchar(64)',
          'name' => 'varchar(255)',
        ],
        'civicrm_event' => [
          'title' => 'varchar(255)',
          'summary' => 'text',
          'description' => 'text',
          'registration_link_text' => 'varchar(255)',
          'event_full_text' => 'text',
          'fee_label' => 'varchar(255)',
          'intro_text' => 'text',
          'footer_text' => 'text',
          'confirm_title' => 'varchar(255)',
          'confirm_text' => 'text',
          'confirm_footer_text' => 'text',
          'confirm_email_text' => 'text',
          'confirm_from_name' => 'varchar(255)',
          'thankyou_title' => 'varchar(255)',
          'thankyou_text' => 'text',
          'thankyou_footer_text' => 'text',
          'pay_later_text' => 'text',
          'pay_later_receipt' => 'text',
          'waitlist_text' => 'text',
          'approval_req_text' => 'text',
          'template_title' => 'varchar(255)',
        ],
      ];
    }
    return $result;
  }
  static function &indices() {
    static $result = NULL;
    if (!$result) {
      $result = [
        'civicrm_price_set' => [
          'UI_title' => [
            'name' => 'UI_title',
            'field' => [
              'title',
            ],
            'unique' => 1,
          ],
        ],
        'civicrm_contact' => [
          'index_sort_name' => [
            'name' => 'index_sort_name',
            'field' => [
              'sort_name',
            ],
          ],
          'index_first_name' => [
            'name' => 'index_first_name',
            'field' => [
              'first_name',
            ],
          ],
          'index_last_name' => [
            'name' => 'index_last_name',
            'field' => [
              'last_name',
            ],
          ],
          'index_household_name' => [
            'name' => 'index_household_name',
            'field' => [
              'household_name',
            ],
          ],
          'index_organization_name' => [
            'name' => 'index_organization_name',
            'field' => [
              'organization_name',
            ],
          ],
        ],
        'civicrm_custom_group' => [
          'UI_title_extends' => [
            'name' => 'UI_title_extends',
            'field' => [
              'title',
              'extends',
            ],
            'unique' => 1,
          ],
        ],
        'civicrm_custom_field' => [
          'UI_label_custom_group_id' => [
            'name' => 'UI_label_custom_group_id',
            'field' => [
              'label',
              'custom_group_id',
            ],
            'unique' => 1,
          ],
        ],
        'civicrm_address' => [
          'index_city' => [
            'name' => 'index_city',
            'field' => [
              'city',
            ],
          ],
        ],
      ];
    }
    return $result;
  }
  static function &tables() {
    static $result = NULL;
    if (!$result) {
      $result = array_keys(self::columns());
    }
    return $result;
  }
}

