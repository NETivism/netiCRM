-- =====================================================
-- Add CKEditor5 option to wysiwyg_editor option group
-- =====================================================
--
-- Purpose: Add CKEditor 5 as a new WYSIWYG editor option
--          for QuickForm loading mechanism test
--
-- Usage: Execute this SQL in your CiviCRM database
--
-- Note: This is for testing the QuickForm element loading.
--       The actual CKEditor 5 library integration is NOT
--       included in this test phase.
--
-- Date: 2026-01-15
-- =====================================================

-- Get the option_group_id for wysiwyg_editor
SELECT @option_group_id_we := id
FROM civicrm_option_group
WHERE name = 'wysiwyg_editor';

-- Insert CKEditor5 option
-- IMPORTANT: label must be 'CKEditor5' (no spaces) to match the PHP class name
INSERT INTO civicrm_option_value
  (option_group_id, label, value, name, grouping, filter, is_default, weight, description, is_optgroup, is_reserved, is_active, component_id, visibility_id)
VALUES
  (@option_group_id_we, 'CKEditor5', 4, NULL, NULL, 0, NULL, 4, 'CKEditor 5 (Test Version - QuickForm Loading Test)', 0, 0, 1, NULL, NULL);

-- Verify the insertion
SELECT
  og.name as option_group_name,
  ov.label,
  ov.value,
  ov.weight,
  ov.description,
  ov.is_active
FROM civicrm_option_value ov
INNER JOIN civicrm_option_group og ON ov.option_group_id = og.id
WHERE og.name = 'wysiwyg_editor'
ORDER BY ov.weight;
