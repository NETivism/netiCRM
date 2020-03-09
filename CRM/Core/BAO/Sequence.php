<?php

class CRM_Core_BAO_Sequence extends CRM_Core_DAO_Sequence {

  function save() {
    $key = 'name';
    if ($this->$key) {
      $this->update();
    }
    else {
      $this->insert();
    }
    $this->free();
    return $this;
    
  }

}