<?php

/**
 * @file
 * Views' relationship handlers.
 */

class uc_civicrm_product_handler_relationship extends views_handler_relationship  {

  /**
   * Called to implement a relationship in a query.
   */
  function query() {
    // Figure out what base table this relationship brings to the party.
    $table_data = views_fetch_data($this->definition['base']);
    $base_field = empty($this->definition['base field']) ? $table_data['table']['base']['field'] : $this->definition['base field'];

    $this->ensure_my_table();

    $join_table = $this->query->add_table($this->definition['join table'], $this->relationship);
    $join_table_data = views_fetch_data($this->definition['join table']);

    $def['table'] = $this->definition['base'];
    $def['left_table'] = $join_table;
    $def['left_field'] = $join_table_data['table']['join'][$this->definition['base']]['field'];
    $def['field'] = $base_field;
    if (!empty($this->options['required'])) {
      $def['type'] = 'INNER';
    }

    if (!empty($def['join_handler']) && class_exists($def['join_handler'])) {
      $join = new $def['join_handler'];
    }
    else {
      $join = new views_join();
    }

    $join->definition = $def;
    $join->construct();
    $join->adjusted = TRUE;

    // use a short alias for this:
    $alias = $def['table'] . '_' . $this->table;

    $this->alias = $this->query->add_relationship($alias, $join, $this->definition['base'], $this->relationship);
  }
}
