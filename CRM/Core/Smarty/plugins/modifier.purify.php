<?php
/**
 * Purify HTML content using HTML Purifier.
 *
 * @param string $text HTML content to purify
 * @param string|array|null $allowed_tags allowed tags (defaults to CRM_Utils_String::ALLOWED_TAGS)
 *
 * @return string purified HTML
 */
function smarty_modifier_purify($text, $allowed_tags = NULL) {
  if (!$allowed_tags) {
    $allowed_tags = CRM_Utils_String::ALLOWED_TAGS;
  }
  if (is_string($allowed_tags)) {
    $allowed_tags = explode(',', $allowed_tags);
  }
  return CRM_Utils_String::htmlPurifier($text, $allowed_tags);
}
