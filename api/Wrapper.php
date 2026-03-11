<?php

interface API_Wrapper {

  /**
   * @return modified $apiRequest
   */
  public function fromApiInput($apiRequest);

  /**
   * @return modified $result
   */
  public function toApiOutput($apiRequest, $result);
}
