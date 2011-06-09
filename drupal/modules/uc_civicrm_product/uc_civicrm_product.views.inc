<?php

/**
 * @file
 *
 * Provide views data and handlers UC CiviCRM Product features.
 */

/**
 * Implementation of hook_views_data()
 */
function uc_civicrm_product_views_data() {
  $data = array();

  $data['uc_civievent'] = array(
    'table' => array(
      'join' => array(
        'node' => array(
          'field' => 'nid',
          'left_field' => 'nid',
        ),
        'civicrm_event' => array(
          'field' => 'event_id',
          'left_field' => 'id',
        ),
      ),
    ),
  );

  $data['uc_civicontribute_order_products'] = array(
    'table' => array(
      'join' => array(
        'civicrm_contribution' => array(
          'field' => 'contribution_id',
          'left_field' => 'id',
        ),
        'uc_order_products' => array(
          'field' => 'order_product_id',
          'left_field' => 'order_product_id',
        ),
      ),
    ),
  );

  $data['uc_civievent_order_products'] = array(
    'table' => array(
      'join' => array(
        'civicrm_participant' => array(
          'field' => 'participant_id',
          'left_field' => 'id',
        ),
        'uc_order_products' => array(
          'field' => 'order_product_id',
          'left_field' => 'order_product_id',
        ),
      ),
    ),
  );

  return $data;
}

/**
 * Implementation of hook_views_data_alter().
 */
function uc_civicrm_product_views_data_alter(&$data) {
  $data['civicrm_event']['product'] = array(
    'title' => 'Product',
    'help' => 'The products that trigger participation for an event',
    'relationship' => array(
      'base' => 'node',
      'base field' => 'nid',
      'label' => t('Product node'),
    ),
  );

  $data['civicrm_contribution']['order_product'] = array(
    'title' => 'Ordered product',
    'help' => 'The product ordered that trigger contribution',
    'relationship' => array(
      'base' => 'uc_order_products',
      'join table' => 'uc_civicontribute_order_products',
      'handler' => 'uc_civicrm_product_handler_relationship',
      'label' => t('Ordered product'),
    ),
  );

  $data['civicrm_participant']['order_product'] = array(
    'title' => 'Ordered product',
    'help' => 'The product ordered that trigger participation',
    'relationship' => array(
      'base' => 'uc_order_products',
      'join table' => 'uc_civievent_order_products',
      'handler' => 'uc_civicrm_product_handler_relationship',
      'label' => t('Ordered product'),
    ),
  );

  $data['uc_order_products']['contributions'] = array(
    'title' => 'Contributions',
    'help' => 'The contribution triggered by the ordered product',
    'relationship' => array(
      'base' => 'civicrm_contribution',
      'join table' => 'uc_civicontribute_order_products',
      'handler' => 'uc_civicrm_product_handler_relationship',
      'label' => t('Contributions'),
    ),
  );

  $data['uc_order_products']['participants'] = array(
    'title' => 'Participants',
    'help' => 'The participant triggered by the ordered product',
    'relationship' => array(
      'base' => 'civicrm_participant',
      'join table' => 'uc_civievent_order_products',
      'handler' => 'uc_civicrm_product_handler_relationship',
      'label' => t('Participants'),
    ),
  );
}

function uc_civicrm_product_views_handlers() {
  return array(
    'info' => array(
      'path' => drupal_get_path('module', 'uc_civicrm_product'),
    ),
    'handlers' => array(
      'uc_civicrm_product_handler_relationship' => array(
        'parent' => 'views_handler_relationship',
      ),
    ),
  );
}
