<?php
function smarty_modifier_purify($text, $allowed_tags = NULL) {
  if (!$allowed_tags) {
    $allowed_tags = CRM_Utils_String::ALLOWED_TAGS;
  }
  if (is_string($allowed_tags)) {
    $allowed_tags = explode(',', $allowed_tags);
  }
  return CRM_Utils_String::htmlPurifier($text, $allowed_tags);
}