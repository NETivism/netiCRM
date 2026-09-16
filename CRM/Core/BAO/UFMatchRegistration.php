<?php

/**
 * Internal registration policy: allowlisted profile input, dedupe and fill-only
 * updates. Matching is deliberately unavailable to login/synchronization callers.
 */
class CRM_Core_BAO_UFMatchRegistration {

  /**
   * Field definitions come from enabled registration profiles, never request IDs.
   *
   * @return array Enabled, editable registration field definitions keyed by field name.
   */
  public static function getFields() {
    $fields = CRM_Core_BAO_UFGroup::getRegistrationFields(
      CRM_Core_Action::ADD,
      CRM_Profile_Form::MODE_REGISTER,
      'Individual'
    ) ?: [];
    foreach ($fields as $name => $field) {
      if (!empty($field['is_view']) || in_array($name, [
        'id', 'contact_id', 'uf_id', 'domain_id', 'contact_type',
        'contact_sub_type_hidden', 'hash', 'api_key', 'is_deleted',
      ], TRUE)) {
        unset($fields[$name]);
      }
    }
    return $fields;
  }

  /**
   * Filter before invoking legacy form code. Companion IDs are allowed only for
   * the configured field that owns them, not as arbitrary record identifiers.
   *
   * @param array $submitted Submitted values keyed by form field name.
   * @param array $fields Server-configured registration field definitions.
   * @return array Allowed values with validated shapes and trimmed strings.
   */
  public static function filterValues(array $submitted, array $fields) {
    $allowed = $fields;
    foreach ($fields as $name => $field) {
      if (($field['html_type'] ?? NULL) === 'Autocomplete-Select') {
        $allowed[$name . '_id'] = [];
      }
      if (!empty($field['time_format'])) {
        $allowed[$name . '_time'] = [];
      }
      if (strpos($name, 'im-') === 0) {
        $allowed[$name . '-provider_id'] = [];
      }
    }
    $values = [];
    foreach (array_intersect_key($submitted, $allowed) as $name => $value) {
      $field = $allowed[$name];
      if (($field['data_type'] ?? NULL) === 'File' || $name === 'image_URL') {
        // Files come only from validated PHP uploads, never POST paths or IDs.
        if (!self::isBlank($value)) {
          throw new CRM_Core_Exception(ts('Invalid registration field value.'));
        }
        continue;
      }
      if (is_array($value)) {
        $date = in_array($name, ['birth_date', 'deceased_date'], TRUE)
          || ($field['html_type'] ?? NULL) === 'Select Date';
        $multiple = in_array($name, ['group', 'tag', 'preferred_communication_method'], TRUE)
          || in_array($field['html_type'] ?? NULL, [
            'CheckBox', 'Multi-Select', 'AdvMulti-Select',
            'Multi-Select State/Province', 'Multi-Select Country',
          ], TRUE);
        if (!$date && !$multiple) {
          throw new CRM_Core_Exception(ts('Invalid registration field value.'));
        }
        foreach ($value as $key => $item) {
          if (!is_scalar($item) || ($date
            ? !in_array((string) $key, ['Y', 'M', 'd', 'H', 'i', 's', 'm', 'year', 'month', 'day'], TRUE)
            : !ctype_digit((string) $key))) {
            throw new CRM_Core_Exception(ts('Invalid registration field value.'));
          }
        }
        $values[$name] = $value;
      }
      elseif (is_scalar($value) || $value === NULL) {
        $values[$name] = is_string($value) ? trim($value) : $value;
      }
      else {
        throw new CRM_Core_Exception(ts('Invalid registration field value.'));
      }
    }
    return $values;
  }

  /**
   * Render only the server-configured registration profiles. In particular,
   * GET gid/action and a POST qfKey cannot select a different profile or scope.
   *
   * @param array $submitted Submitted values to redisplay after whitelist filtering.
   * @return string Registration profile HTML.
   */
  public static function render(array $submitted) {
    try {
      $values = self::filterValues($submitted, self::getFields());
    }
    catch (CRM_Core_Exception $e) {
      // The validation callback will report malformed input on submission.
      $values = [];
    }
    $saved = [$_POST, $_GET, $_REQUEST];
    try {
      $_POST = $values;
      $_GET = ['q' => 'user/register'];
      $_REQUEST = $_POST + $_GET + ['ctype' => 'Individual'];
      return CRM_Core_BAO_UFGroup::getEditHTML(NULL, '', CRM_Core_Action::ADD, TRUE, TRUE, NULL, FALSE, 'Individual');
    }
    finally {
      list($_POST, $_GET, $_REQUEST) = $saved;
    }
  }

  /**
   * Validate in an isolated QuickForm context. Drupal retains responsibility for
   * its own registration validation/CSRF. No client profile ID or qfKey is used.
   *
   * @param array $submitted Raw form input to filter before QuickForm validation.
   * @return array Keys: values, fields, uploads (validated file elements), errors.
   */
  public static function validate(array $submitted) {
    $fields = self::getFields();
    $values = self::filterValues($submitted, $fields);
    $saved = [$_POST, $_GET, $_REQUEST, $_FILES];
    try {
      $_POST = $values + ['_qf_default' => 'Dynamic:upload', 'edit' => ['civicrm_dummy_field' => 1]];
      $_GET = ['q' => 'user/register'];
      $_REQUEST = $_POST + $_GET + ['ctype' => 'Individual'];
      // Uploaded file IDs/paths must not be accepted as ordinary POST values.
      $_FILES = array_intersect_key($_FILES, $fields);
      $controller = new CRM_Core_Controller_Simple(
        'CRM_Profile_Form_Dynamic',
        ts('Registration'),
        CRM_Core_Action::ADD,
        FALSE,
        FALSE,
        TRUE
      );
      $controller->reset();
      $controller->set('id', NULL);
      $controller->set('register', 1);
      $controller->set('ctype', 'Individual');
      $controller->set('skipPermission', 1);
      $controller->process();
      $errors = $controller->validate();
      $exported = $controller->exportValues('Dynamic');
      // Export filters select/checkbox options; don't carry defaults for fields
      // that were not submitted into the dedupe or fill-only update.
      $values = self::filterValues(array_intersect_key($exported, $values), $fields);
      $page = $controller->getPage('Dynamic');
      // Keep upload elements in request-local memory. Moving files is deferred
      // until registration succeeds and the mapping transaction owns the contact.
      $uploads = [];
      if ($errors === TRUE) {
        foreach ($fields as $name => $field) {
          if (($field['data_type'] ?? NULL) === 'File' || $name === 'image_URL') {
            $element = $page->getElement($name);
            if (!PEAR::isError($element) && $element->getType() === 'file' && $element->isUploadedFile()) {
              $uploads[$name] = $element;
            }
          }
        }
      }
      return ['values' => $values, 'fields' => $fields, 'uploads' => $uploads, 'errors' => $errors === TRUE ? [] : $errors];
    }
    finally {
      list($_POST, $_GET, $_REQUEST, $_FILES) = $saved;
    }
  }

  /**
   * Explicit entry point for a successfully validated Drupal registration.
   *
   * @param int|string $uid ID of the newly registered CMS account.
   * @param string $email Email obtained from the CMS account, used for dedupe.
   * @param array $values Registration values exported by validate().
   * @param HTML_QuickForm_file[] $uploads Validated upload elements keyed by registration field name.
   * @return array Keys: ufMatch (DAO or NULL), created (whether a contact was created).
   */
  public static function register($uid, $email, array $values, array $uploads = []) {
    $empty = ['ufMatch' => NULL, 'created' => FALSE];
    if ((int) $uid <= 0 || !CRM_Utils_Rule::email($email)) {
      return $empty;
    }
    $fields = self::getFields();
    // Re-read the whitelist at write time as well as at form validation time.
    $values = self::filterValues($values, $fields);
    foreach (['birth_date', 'deceased_date'] as $name) {
      if (isset($values[$name]) && is_array($values[$name])) {
        $values[$name] = CRM_Utils_Date::processDate(CRM_Utils_Date::format($values[$name]));
      }
    }
    $matchValues = $values;
    foreach (array_keys($matchValues) as $key) {
      if ($key === 'email' || strpos($key, 'email-') === 0) {
        unset($matchValues[$key]);
      }
    }
    $matchValues['email'] = $email;
    $transaction = new CRM_Core_Transaction();
    $paths = [];
    $saved = FALSE;
    try {
      // Serialize registration for this account only.
      if (!$transaction->acquireLock('ufmatch.' . (int) $uid)) {
        throw new CRM_Core_Exception(ts('Unable to link the account to a contact.'));
      }
      if (CRM_Core_BAO_UFMatch::getPersistentUFMatch($uid)) {
        $transaction->commit();
        return ['ufMatch' => CRM_Core_BAO_UFMatch::getPersistentUFMatch($uid), 'created' => FALSE];
      }
      if (CRM_Core_BAO_UFMatch::hasUFMatchConflict($uid, $email)) {
        $transaction->commit();
        return $empty;
      }
      $candidate = self::findCandidate($matchValues);
      $defaults = [];
      if ($candidate && $uploads) {
        CRM_Core_BAO_UFGroup::setProfileDefaults($candidate, $fields, $defaults, TRUE);
      }
      $files = [];
      foreach (array_intersect_key($uploads, $fields) as $name => $element) {
        if (!self::isBlank($defaults[$name] ?? NULL)) {
          continue;
        }
        // These objects were produced by the validated QuickForm, not POST.
        if (!($element instanceof HTML_QuickForm_file) || !$element->isUploadedFile()) {
          throw new CRM_Core_Exception(ts('Invalid registration field value.'));
        }
        $file = $element->getValue();
        if (!is_string($file['name'])) {
          throw new CRM_Core_Exception(ts('Invalid registration field value.'));
        }
        $filename = CRM_Utils_File::makeFileName($file['name']);
        $directory = CRM_Core_Config::singleton()->customFileUploadDir;
        if (!$element->moveUploadedFile($directory, $filename)) {
          throw new CRM_Core_Exception(ts('Unable to save the uploaded file.'));
        }
        $path = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        $paths[] = $path;
        $mimeType = function_exists('mime_content_type') ? mime_content_type($path) : FALSE;
        $files[$name] = ['name' => $path, 'type' => $mimeType ?: 'application/octet-stream'];
      }
      if (isset($files['image_URL']) && !CRM_Contact_BAO_Contact::processImageParams($files)) {
        throw new CRM_Core_Exception(ts('Image could not be uploaded due to invalid type extension.'));
      }
      // Subscription confirmation is handled separately, after persistence;
      // createProfileContact() would otherwise add selected groups directly.
      $profileValues = array_merge($values, $files);
      unset($profileValues['group']);
      if ($candidate) {
        self::fillEmptyFields($candidate, $profileValues, $fields);
        $contactID = $candidate;
      }
      else {
        $contactID = CRM_Core_BAO_UFMatch::createContactForUFMatch($email, $profileValues, $fields);
      }
      CRM_Core_BAO_UFMatch::saveUFMatch($uid, $email, $contactID);
      $transaction->commit();
      // Never use the just-written DAO as Session identity.
      $result = ['ufMatch' => CRM_Core_BAO_UFMatch::getPersistentUFMatch($uid), 'created' => !$candidate];
      $saved = !empty($result['ufMatch']);
      if ($saved) {
        CRM_Core_Error::debug_log_message('UFMatch registration: ' . ($result['created'] ? 'created' : 'linked'));
        self::finishRegistration($result['ufMatch']->contact_id, $email, $values, $fields, $result['created']);
      }
      return $result;
    }
    catch (Throwable $e) {
      $transaction->rollback();
      $transaction->commit();
      throw $e;
    }
    finally {
      if (!$saved) {
        foreach ($paths as $path) {
          if (is_file($path)) {
            unlink($path);
          }
        }
      }
    }
  }

  /**
   * Preserve profile subscriptions/notifications without replaying raw input or
   * writing the profile again. Delivery failures must not undo a saved account.
   *
   * @param int|string $contactID Contact associated with the registered account.
   * @param string $email CMS account email used for subscriptions.
   * @param array $values Validated registration values, including selected groups.
   * @param array $fields Registration field definitions containing profile/group settings.
   * @param bool $created Whether this registration created the contact.
   * @return void
   */
  private static function finishRegistration($contactID, $email, array $values, array $fields, $created) {
    try {
      $hasGroups = CRM_Core_DAO::singleValueQuery(
        "SELECT id FROM civicrm_group_contact WHERE contact_id = %1 AND status = 'Added' LIMIT 1",
        [1 => [$contactID, 'Integer']]
      );
      $profiles = $automaticGroups = [];
      foreach ($fields as $field) {
        $profiles[$field['group_id']] = TRUE;
        if (!empty($field['add_to_group_id'])) {
          $automaticGroups[$field['add_to_group_id']] = TRUE;
        }
      }
      if ($created || !$hasGroups) {
        $contactIDs = [$contactID];
        foreach ($automaticGroups as $groupID => $unused) {
          CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIDs, $groupID, 'Web');
        }
        $selected = array_keys(array_filter($values['group'] ?? []));
        $selected = array_values(array_diff($selected, array_keys($automaticGroups)));
        $profile = ['email' => $email];
        if ($selected && CRM_Core_Config::singleton()->profileDoubleOptIn) {
          CRM_Mailing_Event_BAO_Subscribe::commonSubscribe($selected, $profile, $contactID);
        }
        else {
          foreach ($selected as $groupID) {
            $subscription = CRM_Mailing_Event_BAO_Subscribe::subscribe($groupID, $email, $contactID);
            CRM_Mailing_Event_BAO_Confirm::confirm($contactID, $subscription->id, $subscription->hash);
          }
        }
      }
      foreach (array_keys($profiles) as $profileID) {
        if (CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $profileID, 'notify')) {
          $notification = CRM_Core_BAO_UFGroup::checkFieldsEmptyValues($profileID, $contactID, NULL);
          $profileFields = CRM_Core_BAO_UFGroup::getFields($profileID, FALSE, CRM_Core_Action::VIEW);
          CRM_Core_BAO_UFGroup::verifySubmittedValue($profileFields, $notification, $values);
          CRM_Core_BAO_UFGroup::commonSendMail($contactID, $notification);
        }
      }
    }
    catch (Throwable $e) {
      CRM_Core_Error::debug_log_message('UFMatch registration: profile subscription/notification failed for contact '
        . (int) $contactID . ': ' . get_class($e) . ': ' . $e->getMessage());
    }
  }

  /**
   * Rule thresholds (including partial matches) keep their existing semantics.
   *
   * @param array $values Dedupe input with profile email aliases replaced by the CMS account email.
   * @return int|null The unique eligible contact ID, or NULL if none or ambiguous.
   */
  public static function findCandidate(array $values) {
    $group = new CRM_Dedupe_BAO_RuleGroup();
    $group->contact_type = 'Individual';
    $group->level = 'Strict';
    $group->is_default = 1;
    if (!$group->find(TRUE)) {
      return NULL;
    }
    $rule = new CRM_Dedupe_BAO_Rule();
    $rule->dedupe_rule_group_id = $group->id;
    $rule->find();
    $formatted = CRM_Dedupe_Finder::formatParams($values, 'Individual');
    $params = [];
    while ($rule->fetch()) {
      if (isset($formatted[$rule->rule_table][$rule->rule_field])) {
        $params[$rule->rule_table][$rule->rule_field] = $formatted[$rule->rule_table][$rule->rule_field];
        if ($rule->rule_table === 'civicrm_address' && isset($formatted['civicrm_address']['location_type_id'])) {
          $params['civicrm_address']['location_type_id'] = $formatted['civicrm_address']['location_type_id'];
        }
      }
    }
    if (!$params) {
      return NULL;
    }
    // Internal candidates only. No candidate data is exposed to the registrant.
    $params['check_permission'] = FALSE;
    $ids = CRM_Dedupe_Finder::dupesByParams($params, 'Individual', 'Strict', [], $group->id);
    if (defined('CIVICRM_UNIQ_EMAIL_PER_SITE') && CIVICRM_UNIQ_EMAIL_PER_SITE) {
      $ids = array_intersect($ids, CRM_Core_BAO_Domain::getContactList());
    }
    $eligible = [];
    foreach (array_unique($ids) as $id) {
      $found = CRM_Core_DAO::singleValueQuery(
        "SELECT c.id FROM civicrm_contact c
         WHERE c.id = %1 AND c.contact_type = 'Individual' AND c.is_deleted = 0
         AND NOT EXISTS (SELECT 1 FROM civicrm_uf_match m WHERE m.contact_id = c.id AND m.domain_id = %2)",
        [1 => [$id, 'Integer'], 2 => [CRM_Core_Config::domainID(), 'Integer']]
      );
      if ($found) {
        $eligible[] = (int) $found;
      }
    }
    if (count($eligible) !== 1) {
      return NULL;
    }
    // Recheck candidates in case rule fields changed during eligibility checks.
    $verified = CRM_Dedupe_Finder::dupesByParams($params, 'Individual', 'Strict', [], $group->id);
    if (defined('CIVICRM_UNIQ_EMAIL_PER_SITE') && CIVICRM_UNIQ_EMAIL_PER_SITE) {
      $verified = array_intersect($verified, CRM_Core_BAO_Domain::getContactList());
    }
    if (array_diff($verified, $ids) || array_diff($ids, $verified)) {
      return NULL;
    }
    return $eligible[0];
  }

  /**
   * Zero and FALSE are populated values, including privacy preferences.
   *
   * @param mixed $value Field value to check; zero and FALSE count as populated.
   * @return bool Whether the value is NULL, an empty string, or an empty array.
   */
  public static function isBlank($value) {
    return $value === NULL || $value === '' || $value === [];
  }

  /**
   * Called inside the registration transaction for the selected contact.
   *
   * @param int|string $contactID Existing contact selected for registration.
   * @param array $values Validated profile values and prepared uploads; group subscriptions are excluded.
   * @param array $fields Enabled registration field definitions keyed by field name.
   * @return void
   */
  public static function fillEmptyFields($contactID, array $values, array $fields) {
    $defaults = [];
    CRM_Core_BAO_UFGroup::setProfileDefaults($contactID, $fields, $defaults, TRUE);
    foreach ($fields as $name => $field) {
      $populated = !self::isBlank($defaults[$name] ?? NULL);
      // QuickForm sometimes exports checkbox defaults as field[option] keys.
      foreach ($defaults as $key => $value) {
        if (strpos($key, $name . '[') === 0 && !self::isBlank($value)) {
          $populated = TRUE;
        }
      }
      if ($populated) {
        unset($values[$name], $values[$name . '_id'], $values[$name . '_time'], $values[$name . '-provider_id']);
      }
    }
    $values = array_filter($values, function ($value) { return !self::isBlank($value); });
    if (!$values) {
      return;
    }
    CRM_Utils_Hook::pre('edit', 'Profile', $contactID, $values);
    list($data) = CRM_Contact_BAO_Contact::formatProfileContactParams($values, $fields, $contactID);
    $contact = new CRM_Contact_DAO_Contact();
    $contact->id = $contactID;
    $contact->find(TRUE);
    $data['contact_id'] = $contactID;
    $data['contact_type'] = $contact->contact_type;
    CRM_Utils_Hook::pre('edit', $contact->contact_type, $contactID, $data);
    if (isset($data['preferred_communication_method']) && is_array($data['preferred_communication_method'])) {
      $selected = array_keys(array_filter($data['preferred_communication_method']));
      $data['preferred_communication_method'] = $selected
        ? CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $selected) . CRM_Core_DAO::VALUE_SEPARATOR
        : NULL;
    }
    // The formatter may include existing greetings or derived defaults. Never
    // write a scalar already populated in the database.
    foreach ($data as $name => $value) {
      if (!is_array($value) && $name !== 'contact_id' && !self::isBlank($contact->$name ?? NULL)) {
        unset($data[$name]);
      }
    }
    // Bind location updates to a unique server-selected row. Strip generated
    // primary/billing flags; don't let a partial profile change those flags.
    $classes = [
      'address' => 'CRM_Core_DAO_Address', 'email' => 'CRM_Core_DAO_Email',
      'phone' => 'CRM_Core_DAO_Phone', 'im' => 'CRM_Core_DAO_IM',
      'openid' => 'CRM_Core_DAO_OpenID', 'website' => 'CRM_Core_DAO_Website',
    ];
    foreach ($classes as $block => $class) {
      if (empty($data[$block])) {
        continue;
      }
      foreach ($data[$block] as $index => &$record) {
        $table = 'civicrm_' . $block;
        $params = [1 => [$contactID, 'Integer']];
        $where = 'contact_id = %1';
        foreach (['location_type_id', 'phone_type_id', 'website_type_id'] as $key) {
          if (isset($record[$key])) {
            $number = count($params) + 1;
            $where .= " AND $key = %$number";
            $params[$number] = [$record[$key], 'Integer'];
          }
        }
        $dao = CRM_Core_DAO::executeQuery("SELECT * FROM $table WHERE $where", $params);
        if ($dao->N > 1) {
          unset($data[$block][$index]);
          continue;
        }
        unset($record['is_primary'], $record['is_billing']);
        if ($dao->fetch()) {
          foreach ($record as $key => $value) {
            if (!self::isBlank($dao->$key ?? NULL)) {
              unset($record[$key]);
            }
          }
          $record['id'] = $dao->id;
        }
        else {
          // Adding an empty location must not promote it over an existing one.
          $record['is_primary'] = 0;
          $record['is_billing'] = 0;
        }
        $record['contact_id'] = $contactID;
      }
      unset($record);
    }
    // Save exact patches instead of Contact::create(): its location layer
    // repairs primary/billing flags and its new-contact defaults can overwrite
    // unrelated data even when given only a partial profile.
    $scalars = [];
    foreach ($data as $name => $value) {
      if (!is_array($value) && $name !== 'contact_id' && $name !== 'contact_type') {
        $scalars[$name] = $value;
      }
    }
    $nameValues = array_intersect_key($scalars, array_flip(['first_name', 'middle_name', 'last_name', 'prefix_id', 'suffix_id']));
    if ($nameValues && $contact->contact_type === 'Individual') {
      // Names are derived values: regenerate them from the accepted name patch
      // and existing name fields, rather than preserving an old email fallback.
      $nameValues['contact_type'] = $contact->contact_type;
      $formattedContact = clone $contact;
      CRM_Contact_BAO_Individual::format($nameValues, $formattedContact);
      $scalars['display_name'] = $formattedContact->display_name;
      $scalars['sort_name'] = $formattedContact->sort_name;
    }
    if ($scalars) {
      $patch = new CRM_Contact_DAO_Contact();
      $patch->copyValues($scalars);
      $patch->id = $contactID;
      $patch->modified_date = date('YmdHis');
      $patch->save();
    }
    foreach ($classes as $block => $class) {
      foreach ($data[$block] ?? [] as $record) {
        $patch = new $class();
        $patch->copyValues($record);
        $patch->save();
      }
    }
    if (!empty($values['current_employer']) && self::isBlank($contact->employer_id)) {
      CRM_Contact_BAO_Contact_Utils::createCurrentEmployerRelationship($contactID, $values['current_employer']);
    }
    if (!empty($values['note'])) {
      $note = [
        'entity_table' => 'civicrm_contact', 'entity_id' => $contactID,
        'note' => $values['note'], 'contact_id' => $contactID,
      ];
      CRM_Core_BAO_Note::add($note, CRM_Core_DAO::$_nullArray);
    }
    if (!empty($data['custom'])) {
      CRM_Core_BAO_CustomValueTable::store($data['custom'], 'civicrm_contact', $contactID);
    }
    $contactIDs = [$contactID];
    foreach ($values['tag'] ?? [] as $id => $selected) {
      if ($selected) {
        CRM_Core_BAO_EntityTag::addEntitiesToTag($contactIDs, $id);
      }
    }
    // Refresh greeting caches from persisted values, preserving custom greetings.
    $updatedContact = new CRM_Contact_DAO_Contact();
    $updatedContact->id = $contactID;
    $updatedContact->find(TRUE);
    CRM_Contact_BAO_Contact::processGreetings($updatedContact);
    // Give integrations the final contact, including the refreshed greetings.
    $updatedContact = new CRM_Contact_DAO_Contact();
    $updatedContact->id = $contactID;
    $updatedContact->find(TRUE);
    CRM_Utils_Hook::post('edit', $updatedContact->contact_type, $contactID, $updatedContact);
    CRM_Core_BAO_Log::register($contactID, 'civicrm_contact', $contactID, NULL, ts('Updated contact'));
    CRM_Utils_Hook::post('edit', 'Profile', $contactID, $values);
    CRM_ACL_BAO_Cache::resetCache();
  }
}
