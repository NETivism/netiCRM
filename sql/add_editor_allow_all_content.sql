-- =====================================================
-- Add editor_allow_all_content to civicrm_preferences
-- =====================================================
--
-- Purpose: Add the Allow All Content opt-in flag used by
--          the CKEditor 5 admin preference (Issue #45339).
--          When set (1), the WYSIWYG editor saves arbitrary
--          HTML including <script> tags and inline event
--          handlers. Default (0) sanitizes script/event attrs.
--
-- Usage: Execute this SQL in your CiviCRM database.
--
-- Date: 2026-04-21
-- =====================================================

ALTER TABLE civicrm_preferences
  ADD COLUMN editor_allow_all_content tinyint DEFAULT 0
  COMMENT 'If set, WYSIWYG editors allow all HTML content including script tags (XSS risk).';

-- Verify the column was added
SHOW COLUMNS FROM civicrm_preferences LIKE 'editor_allow_all_content';
