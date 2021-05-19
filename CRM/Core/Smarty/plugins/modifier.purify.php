<?php
function smarty_modifier_purify($text, $allowed_tags = NULL) {
  if (!$allowed_tags) {
    $allowed_tags = CRM_Utils_String::ALLOWED_TAGS;
  }
  return CRM_Utils_String::htmlPurifier($text, $allowed_tags);
}