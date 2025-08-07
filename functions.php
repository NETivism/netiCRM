<?php

/**
 * Short-named function for string translation, defined in global scope so it's available everywhere.
 *
 * @param  $text   string  string for translating
 * @param  $params array   an array of additional parameters
 *
 * @return         string  the translated string
 */
function ts($text, $params = []) {
  static $function;
  static $locale;
  global $tsLocale;

  if (empty($tsLocale)) {
    return $text;
  }

  if (empty($text)) {
    return '';
  }

  $i18n = CRM_Core_I18n::singleton();
  if($locale != $tsLocale){
    $locale = $tsLocale;
    if (!empty($i18n->_customTranslateFunction) && $function === NULL) {
      if (function_exists($i18n->_customTranslateFunction)) {
        $function = $i18n->_customTranslateFunction;
      }
      else {
        $function = FALSE;
      }
    }
  }

  if ($function) {
    return $function($text, $params);
  }
  else {
    return $i18n->crm_translate($text, $params);
  }
}