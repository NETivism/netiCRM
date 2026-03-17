<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

/**
 * This class generates form components for processing Event
 *
 */
class CRM_Event_Form_ManageEvent_EventInfo extends CRM_Event_Form_ManageEvent {

  public $_cdType;
  public $_showHide;
  public $_type;
  public $_subType;
  public $_groupCount;
  /**
   * Event type
   */
  protected $_eventType = NULL;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   */
  public function preProcess() {
    //custom data related code
    $this->_cdType = CRM_Utils_Array::value('type', $_GET);
    $this->assign('cdType', FALSE);
    if ($this->_cdType) {
      $this->assign('cdType', TRUE);
      return CRM_Custom_Form_CustomData::preProcess($this);
    }
    parent::preProcess();

    if ($this->_id) {
      $this->assign('entityID', $this->_id);
      $eventType = CRM_Core_DAO::getFieldValue(
        'CRM_Event_DAO_Event',
        $this->_id,
        'event_type_id'
      );
    }
    else {
      $eventType = 'null';
    }

    $showLocation = FALSE;
    // when custom data is included in this page
    if (CRM_Utils_Array::value("hidden_custom", $_POST)) {
      $this->set('type', 'Event');
      $this->set('subType', CRM_Utils_Array::value('event_type_id', $_POST));
      $this->set('entityId', $this->_id);

      CRM_Custom_Form_CustomData::preProcess($this);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }
  }

  /**
   * This function sets the default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return array
   */
  public function setDefaultValues() {
    if ($this->_cdType) {
      $tempId = (int) CRM_Utils_Request::retrieve('template_id', 'Integer', $this);
      // set template custom data as a default for event, CRM-5596
      if ($tempId && !$this->_id) {
        $defaults = $this->templateCustomDataValues($tempId);
      }
      else {
        $defaults = CRM_Custom_Form_CustomData::setDefaultValues($this);
      }

      return $defaults;
    }
    $defaults = parent::setDefaultValues();

    // in update mode, we need to set custom data subtype to tpl
    if (CRM_Utils_Array::value('event_type_id', $defaults)) {
      $this->assign('customDataSubType', $defaults["event_type_id"]);
    }

    $this->_showHide = new CRM_Core_ShowHideBlocks();
    // Show waitlist features or event_full_text if max participants set
    if (CRM_Utils_Array::value('max_participants', $defaults)) {
      $this->_showHide->addShow('id-waitlist');
      if (CRM_Utils_Array::value('has_waitlist', $defaults)) {
        $this->_showHide->addShow('id-waitlist-text');
        $this->_showHide->addHide('id-event_full');
      }
      else {
        $this->_showHide->addHide('id-waitlist-text');
        $this->_showHide->addShow('id-event_full');
      }
    }
    else {
      $this->_showHide->addHide('id-event_full');
      $this->_showHide->addHide('id-waitlist');
      $this->_showHide->addHide('id-waitlist-text');
    }

    $this->_showHide->addToTemplate();
    $this->assign('elemType', 'table-row');

    $this->assign('description', CRM_Utils_Array::value('description', $defaults));

    // Provide suggested text for event full and waitlist messages if they're empty
    $defaults['event_full_text'] = CRM_Utils_Array::value('event_full_text', $defaults, ts('This event is currently full.'));

    $defaults['waitlist_text'] = CRM_Utils_Array::value('waitlist_text', $defaults, ts('This event is currently full. However you can register now and get added to a waiting list. You will be notified if spaces become available.'));

    if (!$this->_isTemplate) {
      list($defaults['start_date'], $defaults['start_date_time']) = CRM_Utils_Date::setDateDefaults(CRM_Utils_Array::value('start_date', $defaults), 'activityDateTime');

      if (CRM_Utils_Array::value('end_date', $defaults)) {
        list($defaults['end_date'], $defaults['end_date_time']) = CRM_Utils_Date::setDateDefaults($defaults['end_date'], 'activityDateTime');
      }
    }
    return $defaults;
  }

  /**
   * Prepare editor switcher - CKE5 is loaded by ckeditor5.php, CKE4 will be loaded dynamically
   * This enables dynamic switching between editors for testing compatibility
   *
   * @return void
   * @access private
   */
  private function loadBothEditors() {
    // CKE5 is loaded by ckeditor5.php automatically with namespace swapping
    // CKE4 will be loaded dynamically when user switches to it
    // No need to preload anything here
  }

  /**
   * Add editor switcher UI and JavaScript for dynamic switching
   *
   * @return void
   * @access private
   */
  private function addEditorSwitcher() {
    $config = CRM_Core_Config::singleton();
    $cke4Path = $config->resourceBase . 'packages/ckeditor/ckeditor.js?' . $config->ver;

    // Detect which editor the system actually loaded via addWysiwyg
    $systemEditor = strtolower(CRM_Utils_Array::value(
      CRM_Core_BAO_Preferences::value('editor_id'),
      CRM_Core_PseudoConstant::wysiwygEditor()
    ));
    $defaultEditorType = ($systemEditor === 'ckeditor5') ? 'cke5' : 'cke4';

    // Prepare CKE4 configuration (matching ckeditor.php logic)
    $plugins = array('widget', 'lineutils', 'mediaembed', 'tableresize', 'image2');

    // Add clipboard_image plugin only if user has permission
    // Permission check follows the same logic as ckeditor.php:71-74
    if (CRM_Core_Permission::check('access CiviCRM') ||
        CRM_Core_Permission::check('paste and upload images')) {
      $plugins[] = 'clipboard_image';
    }

    // Determine toolbar and allowedContent based on permission
    // Follows the same logic as ckeditor.php:78-85
    if (CRM_Core_Permission::check('access CiviCRM')) {
      $toolbar = 'CiviCRM';
      $allowedContent = 'true';
    }
    else {
      $toolbar = 'CiviCRMBasic';
      $allowedContent = "'h1 h2 h3 p blockquote; strong em; a[!href]; img(left,right)[!src,alt,width,height,title]; span{font-size,color,background-color}'";
    }

    // Build extra plugins registration code
    $extraPluginsCode = array();
    foreach ($plugins as $name) {
      $extraPluginsCode[] = "CKEDITOR.plugins.addExternal('{$name}', '{$config->resourceBase}packages/ckeditor/extraplugins/{$name}/', 'plugin.js');";
    }

    // Check if IMCE module is enabled (matching civicrm.module:620)
    $imceEnabled = FALSE;
    $imceUrl = '';
    if (CRM_Utils_System::moduleExists('imce')) {
      $imceEnabled = TRUE;
      $imceUrl = CRM_Utils_System::url('imce');
    }

    // Prepare configuration data for JavaScript
    $cke4Config = array(
      'resourceBase' => $config->resourceBase,
      'ver' => $config->ver,
      'plugins' => $plugins,
      'extraPluginsCode' => implode("\n      ", $extraPluginsCode),
      'extraPluginsList' => implode(',', $plugins),
      'toolbar' => $toolbar,
      'allowedContent' => $allowedContent,
      'customConfigPath' => $config->resourceBase . 'js/ckeditor.config.js',
      'width' => '94%',
      'height' => '400',
      'imceEnabled' => $imceEnabled,
      'imceUrl' => $imceUrl
    );

    $html = '
    <div class="crm-section editor-switcher-section" style="margin-top: 10px; padding: 10px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">
      <div class="label">
        <label>編輯器測試模式</label>
      </div>
      <div class="content">
        <select id="editor-format-switcher" onchange="switchEditorFormat(this.value)" style="padding: 4px 8px;">
          <option value="">請選擇編輯器...</option>
          <option value="cke4"' . ($defaultEditorType === 'cke4' ? ' selected' : '') . '>CKEditor 4 (傳統版)</option>
          <option value="cke5"' . ($defaultEditorType === 'cke5' ? ' selected' : '') . '>CKEditor 5 (新版)</option>
        </select>
        <span id="editor-switch-status" style="margin-left: 10px; color: #666;"></span>
      </div>
      <div class="description" style="margin-top: 5px; font-size: 11px; color: #666;">
        💡 此功能用於測試 CKEditor 4 和 5 之間的內容相容性。切換編輯器時，請檢查原始碼是否一致。
      </div>
    </div>

    <script type="text/javascript">
    (function() {
      // CKE4 configuration from PHP (matching ckeditor.php)
      const cke4Config = ' . json_encode($cke4Config) . ';

      // IMCE integration (matching civicrm.module:628-638)
      if (cke4Config.imceEnabled) {
        window.civicrmImceCkeditSendTo = function (file, win) {
          var parts = /\?(?:.*&)?CKEditorFuncNum=(\d+)(?:&|$)/.exec(win.location.href);
          if (parts && parts.length > 1) {
            var url = file.getUrl();
            win.opener.CKEDITOR.tools.callFunction(parts[1], url);
            win.close();
          }
          else {
            throw "CKEditorFuncNum parameter not found or invalid: " + win.location.href;
          }
        };
      }

      let currentEditor = null;
      let currentEditorType = "' . $defaultEditorType . '";
      const editorElement = document.querySelector("[name=description]");
      const statusSpan = document.getElementById("editor-switch-status");

      // Wait for page to load before detecting initial editor status
      cj(document).ready(function() {
        setTimeout(function() {
          if (currentEditorType === "cke5" && window.CKEDITOR_5 && window.CKEDITOR_5.ClassicEditor) {
            statusSpan.textContent = "✓ CKEditor 5 已載入";
            statusSpan.style.color = "green";
          }
          else if (currentEditorType === "cke4" && window.CKEDITOR && window.CKEDITOR.instances && window.CKEDITOR.instances[editorElement.name]) {
            statusSpan.textContent = "✓ CKEditor 4 已載入";
            statusSpan.style.color = "green";
          }
        }, 1000);
      });

      window.switchEditorFormat = async function(format) {
        if (!format || format === currentEditorType) {
          return;
        }

        statusSpan.textContent = "切換中...";
        statusSpan.style.color = "#666";

        try {
          // Save current content
          const currentContent = await getCurrentContent();

          // Destroy current editor
          await destroyCurrentEditor();

          // Wait a bit for cleanup
          await new Promise(resolve => setTimeout(resolve, 200));

          // Initialize new editor
          if (format === "cke4") {
            await initializeCKE4(currentContent);
          } else if (format === "cke5") {
            await initializeCKE5(currentContent);
          }

          currentEditorType = format;
          statusSpan.textContent = "✓ 切換完成";
          statusSpan.style.color = "green";

        } catch (error) {
          console.error("Editor switch error:", error);
          statusSpan.textContent = "✗ 切換失敗: " + error.message;
          statusSpan.style.color = "red";
        }
      };

      async function getCurrentContent() {
        if (!editorElement) return "";

        // Try to get content from CKE5
        if (currentEditorType === "cke5") {
          // Find CKE5 instance by iterating through all editors
          const editors = document.querySelectorAll(".ck-editor");
          for (let editorDiv of editors) {
            const textarea = editorDiv.previousElementSibling;
            if (textarea && textarea === editorElement) {
              // Found the matching editor, get data from it
              const editorInstance = editorDiv.querySelector(".ck-editor__editable");
              if (editorInstance && editorInstance.ckeditorInstance) {
                return editorInstance.ckeditorInstance.getData();
              }
            }
          }
          // Fallback to textarea value
          return editorElement.value;
        }
        // Try to get content from CKE4
        else if (currentEditorType === "cke4" && window.CKEDITOR && window.CKEDITOR.instances) {
          const instance = window.CKEDITOR.instances[editorElement.name];
          if (instance) {
            return instance.getData();
          }
        }

        return editorElement.value;
      }

      async function destroyCurrentEditor() {
        if (currentEditorType === "cke4" && window.CKEDITOR && window.CKEDITOR.instances) {
          const instance = window.CKEDITOR.instances[editorElement.name];
          if (instance) {
            instance.destroy();
            cj(editorElement).removeClass("ckeditor-processed");
          }
        } else if (currentEditorType === "cke5") {
          // Find and destroy CKE5 instance
          const editors = document.querySelectorAll(".ck-editor");
          for (let editorDiv of editors) {
            const textarea = editorDiv.previousElementSibling;
            if (textarea && textarea === editorElement) {
              const editorInstance = editorDiv.querySelector(".ck-editor__editable");
              if (editorInstance && editorInstance.ckeditorInstance) {
                await editorInstance.ckeditorInstance.destroy();
              }
              editorDiv.remove();
            }
          }
          cj(editorElement).removeClass("ckeditor5-processed");
        }

        // Show textarea
        editorElement.style.display = "block";
        currentEditor = null;
      }

      async function initializeCKE4(content) {
        // Load CKE4 script dynamically if not already loaded
        if (!window.CKEDITOR || !window.CKEDITOR.replace) {
          const cke4Path = "' . $cke4Path . '";
          await loadScript(cke4Path);

          // Wait for CKEDITOR to be available
          let attempts = 0;
          while ((!window.CKEDITOR || !window.CKEDITOR.replace) && attempts < 50) {
            await new Promise(resolve => setTimeout(resolve, 100));
            attempts++;
          }

          if (!window.CKEDITOR || !window.CKEDITOR.replace) {
            throw new Error("CKEditor 4 failed to load");
          }
        }

        // Register extra plugins (matching ckeditor.php logic)
        // This must be done before replace() to ensure plugins are available
        if (!window.cke4PluginsRegistered) {
          ' . implode("\n          ", $extraPluginsCode) . '
          window.cke4PluginsRegistered = true;
        }

        // Set content to textarea first
        editorElement.value = content;

        // Add ckeditor-processed class to prevent double initialization
        if (!cj(editorElement).hasClass("ckeditor-processed")) {
          cj(editorElement).addClass("ckeditor-processed");
        }

        // Destroy any existing CKE4 instance to prevent editor-element-conflict
        if (window.CKEDITOR.instances && window.CKEDITOR.instances[editorElement.name]) {
          window.CKEDITOR.instances[editorElement.name].destroy();
        }

        // Initialize CKE4 (matching ckeditor.php:109)
        const editor = window.CKEDITOR.replace(editorElement.name);
        if (!editor) {
          throw new Error("CKEDITOR.replace returned null for " + editorElement.name);
        }

        // Get the editor instance
        const instance = window.CKEDITOR.instances[editorElement.name];

        if (instance) {
          // Configure editor immediately after replace (matching ckeditor.php:111-122)
          instance.on("key", function(evt) {
            global_formNavigate = false;
          });

          // Set configuration (matching ckeditor.php line by line)
          instance.config.extraPlugins = cke4Config.extraPluginsList;
          instance.config.customConfig = cke4Config.customConfigPath;
          instance.config.width = cke4Config.width;
          instance.config.height = cke4Config.height;

          // Set allowedContent based on permission
          if (cke4Config.allowedContent === "true") {
            instance.config.allowedContent = true;
          } else {
            instance.config.allowedContent = cke4Config.allowedContent;
          }

          // Set toolbar
          instance.config.toolbar = cke4Config.toolbar;

          // Set IMCE filebrowser URL if enabled (matching civicrm.module:645)
          if (cke4Config.imceEnabled) {
            instance.config.filebrowserImageBrowseUrl = cke4Config.imceUrl + "?sendto=civicrmImceCkeditSendTo";
          }

          // Note: fullPage is not used in EventInfo form, so we don\'t set it
        }

        return new Promise((resolve, reject) => {
          editor.on("instanceReady", function() {
            console.log("CKEditor 4 initialized with config:", {
              toolbar: this.config.toolbar,
              extraPlugins: this.config.extraPlugins,
              allowedContent: this.config.allowedContent,
              height: this.config.height,
              filebrowserImageBrowseUrl: this.config.filebrowserImageBrowseUrl
            });
            currentEditor = editor;
            resolve();
          });
          editor.on("error", function(evt) {
            reject(new Error("CKE4 initialization failed: " + evt.data.message));
          });
        });
      }

      // Helper function to load script dynamically
      function loadScript(src) {
        return new Promise((resolve, reject) => {
          const existing = document.querySelector(`script[src="${src}"]`);
          if (existing) {
            resolve();
            return;
          }

          const script = document.createElement("script");
          script.src = src;
          script.type = "text/javascript";
          script.onload = resolve;
          script.onerror = () => reject(new Error(`Failed to load ${src}`));
          document.head.appendChild(script);
        });
      }

      async function initializeCKE5(content) {
        if (!window.CKEDITOR_5 || !window.CKEDITOR_5.ClassicEditor) {
          throw new Error("CKEditor 5 not loaded at window.CKEDITOR_5");
        }

        // Set content to textarea first
        editorElement.value = content;

        const {
          ClassicEditor,
          Essentials,
          Bold,
          Italic,
          Underline,
          Strikethrough,
          Paragraph,
          Heading,
          Link,
          List,
          Alignment,
          Font,
          RemoveFormat,
          SourceEditing,
          FullPage,
          GeneralHtmlSupport,
          Undo
        } = window.CKEDITOR_5;

        const editor = await ClassicEditor.create(editorElement, {
          licenseKey: "GPL",
          plugins: [
            Essentials, Bold, Italic, Underline, Strikethrough,
            Paragraph, Heading, Link, List, Alignment, Font,
            RemoveFormat, SourceEditing, FullPage, GeneralHtmlSupport, Undo
          ],
          toolbar: [
            "undo", "redo", "|",
            "heading", "|",
            "bold", "italic", "underline", "strikethrough", "|",
            "link", "|",
            "bulletedList", "numberedList", "|",
            "alignment", "|",
            "fontSize", "fontFamily", "fontColor", "fontBackgroundColor", "|",
            "removeFormat", "|",
            "sourceEditing"
          ],
          htmlSupport: {
            allow: [
              {
                name: /^(html|head|body|title|meta|style|script|div|span|p|h[1-6]|table|thead|tbody|tr|td|th|ul|ol|li|a|img|br|hr)$/,
                attributes: true,
                classes: true,
                styles: true
              }
            ]
          },
          height: "400px"
        });

        console.log("CKEditor 5 initialized");
        currentEditor = editor;

        // Store instance reference for later retrieval
        const ckEditorDiv = editorElement.nextElementSibling;
        if (ckEditorDiv && ckEditorDiv.classList.contains("ck-editor")) {
          const editableDiv = ckEditorDiv.querySelector(".ck-editor__editable");
          if (editableDiv) {
            editableDiv.ckeditorInstance = editor;
          }
        }

        return editor;
      }
    })();
    </script>
    ';

    // Assign to template variable for rendering
    $this->assign('editorSwitcherUI', $html);
  }

  /**
   * Function to build the form
   *
   * @return void
   */
  public function buildQuickForm() {
    // Load both CKEditor 4 and CKEditor 5 for dynamic switching
    $this->loadBothEditors();

    $config = CRM_Core_Config::singleton();

    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::buildQuickForm($this);
    }
    //need to assign custom data type and subtype to the template
    $this->assign('customDataType', 'Event');
    if ($this->_eventType) {
      $this->assign('customDataSubType', $this->_eventType);
    }
    $this->assign('entityId', $this->_id);

    $this->_first = TRUE;
    $this->applyFilter('__ALL__', 'trim');
    $attributes = CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event');

    if ($this->_isTemplate) {
      $this->add('text', 'template_title', ts('Template Title'), $attributes['template_title'], TRUE);
    }

    if ($this->_action & CRM_Core_Action::ADD) {

      $eventTemplates = &CRM_Event_PseudoConstant::eventTemplates();
      if (CRM_Utils_System::isNull($eventTemplates)) {
        $this->assign('noEventTemplates', TRUE);
      }
      else {
        $this->add(
          'select',
          'template_id',
          ts('From Template'),
          ['' => ts('- select -')] + $eventTemplates,
          FALSE,
          ['onchange' => "reloadWindow( this.value );"]
        );
      }
    }

    // add event title, make required if this is not a template
    $this->add('text', 'title', ts('Event Title'), $attributes['event_title'], !$this->_isTemplate);

    $event = CRM_Core_OptionGroup::values('event_type');

    $this->add(
      'select',
      'event_type_id',
      ts('Event Type'),
      ['' => ts('- select -')] + $event,
      TRUE,
      ['onChange' => "buildCustomData( 'Event', this.value );"]
    );

    $participantRole = CRM_Core_OptionGroup::values('participant_role');
    $this->add(
      'select',
      'default_role_id',
      ts('Participant Role'),
      $participantRole,
      TRUE
    );

    $participantListing = CRM_Core_OptionGroup::values('participant_listing');
    $this->add(
      'select',
      'participant_listing_id',
      ts('Participant Listing'),
      ['' => ts('Disabled')] + $participantListing,
      FALSE
    );

    $this->add('textarea', 'summary', ts('Event Summary'), $attributes['summary']);
    $this->addWysiwyg('description', ts('Complete Description'), $attributes['event_description']);

    // Add editor switcher for testing CKEditor 4/5 compatibility
    $this->addEditorSwitcher();

    $this->addElement('checkbox', 'is_public', ts('Public Event?'));
    $this->addElement('checkbox', 'is_map', ts('Include Map to Event Location?'));

    $this->addDateTime('start_date', ts('Start Date'), FALSE, ['formatType' => 'activityDateTime']);
    $this->addDateTime('end_date', ts('End Date / Time'), FALSE, ['formatType' => 'activityDateTime']);

    $this->add(
      'text',
      'max_participants',
      ts('Max Number of Participants'),
      ['onchange' => "if (this.value != '') {show('id-waitlist','table-row'); showHideByValue('has_waitlist','0','id-waitlist-text','table-row','radio',false); showHideByValue('has_waitlist','0','id-event_full','table-row','radio',true); return;} else {hide('id-event_full','table-row'); hide('id-waitlist','table-row'); hide('id-waitlist-text','table-row');cj('#has_waitlist').attr('checked',false); return;}"]
    );
    $this->addRule('max_participants', ts('Max participants should be a positive number'), 'positiveInteger');
    $this->addRule('max_participants', ts('Max participants should be a positive number'), 'nonzero');

    $participantStatuses = &CRM_Event_PseudoConstant::participantStatus();
    if (in_array('On waitlist', $participantStatuses) and in_array('Pending from waitlist', $participantStatuses) && !$this->_eventInfo['requires_approval']) {
      $this->addElement('checkbox', 'has_waitlist', ts('Offer a Waitlist?'), NULL, ['onclick' => "showHideByValue('has_waitlist','0','id-event_full','table-row','radio',true); showHideByValue('has_waitlist','0','id-waitlist-text','table-row','radio',false);"]);
      $this->add('textarea', 'waitlist_text', ts('Waitlist Message'), $attributes['waitlist_text']);
    }

    $this->add('textarea', 'event_full_text', ts('Message if Event Is Full'), $attributes['event_full_text']);

    $this->addElement('checkbox', 'is_active', ts('Is this Event Active?'));

    $this->addFormRule(['CRM_Event_Form_ManageEvent_EventInfo', 'formRule']);

    if ($config->nextEnabled) {
      $this->assign('ai_completion_default', CRM_AI_BAO_AICompletion::getDefaultTemplate('CiviEvent'));
      $this->assign('ai_completion_url_basepath', $config->userSystem->languageNegotiationURL('/'));
      $this->assign('ai_completion_component', 'CiviEvent');
    }

    parent::buildQuickForm();
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @return array<string, mixed> list of errors to be posted back to the form
   */
  public static function formRule($values) {
    $errors = [];

    if (!$values['is_template']) {
      if (CRM_Utils_System::isNull($values['start_date'])) {
        $errors['start_date'] = ts('Start Date and Time are required fields');
      }
      else {
        $start = CRM_Utils_Date::processDate($values['start_date']);
        $end = CRM_Utils_Date::processDate($values['end_date']);
        if (($end < $start) && ($end != 0)) {
          $errors['end_date'] = ts('End date should be after Start date');
        }
      }
    }

    //CRM-4286
    if (strstr($values['title'], '/')) {
      $errors['title'] = ts("Please do not use '/' in Event Title.");
    }

    return $errors;
  }

  /**
   * Function to process the form
   *
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    //format params
    $params['start_date'] = CRM_Utils_Date::processDate($params['start_date'], $params['start_date_time']);
    $params['end_date'] = CRM_Utils_Date::processDate($params['end_date'], $params['end_date_time'], TRUE);
    $params['has_waitlist'] = CRM_Utils_Array::value('has_waitlist', $params, FALSE);
    $params['is_map'] = CRM_Utils_Array::value('is_map', $params, FALSE);
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['is_public'] = CRM_Utils_Array::value('is_public', $params, FALSE);
    $params['default_role_id'] = CRM_Utils_Array::value('default_role_id', $params, FALSE);
    $params['id'] = $this->_id;

    //new event, so lets set the created_id
    if ($this->_action & CRM_Core_Action::ADD) {
      $session = CRM_Core_Session::singleton();
      $params['created_id'] = $session->get('userID');
      $params['created_date'] = date('YmdHis');
    }

    $customFields = CRM_Core_BAO_CustomField::getFields(
      'Event',
      FALSE,
      FALSE,
      CRM_Utils_Array::value('event_type_id', $params)
    );
    $params['custom'] = CRM_Core_BAO_CustomField::postProcess(
      $params,
      $customFields,
      $this->_id,
      'Event'
    );

    // copy all not explicitely set $params keys from the template (if it should be sourced)
    if (CRM_Utils_Array::value('template_id', $params)) {
      $defaults = [];
      $templateParams = ['id' => $params['template_id']];
      CRM_Event_BAO_Event::retrieve($templateParams, $defaults);
      unset($defaults['id']);
      unset($defaults['default_fee_id']);
      unset($defaults['default_discount_fee_id']);
      foreach ($defaults as $key => $value) {
        if (!isset($params[$key])) {
          $params[$key] = $value;
        }
      }
    }

    if (empty($params['is_template'])) {
      $params['is_template'] = 0;
    }

    $event = CRM_Event_BAO_Event::create($params);

    // now that we have the event’s id, do some more template-based stuff
    if (CRM_Utils_Array::value('template_id', $params)) {
      // copy event fees
      $ogParams = ['name' => "civicrm_event.amount.$event->id"];
      $defaults = [];

      if (is_null(CRM_Core_BAO_OptionGroup::retrieve($ogParams, $defaults))) {

        // Copy the Main Event Fees
        CRM_Core_BAO_OptionGroup::copyValue('event', $params['template_id'], $event->id);

        // Copy the Discount option Group and Values

        $optionGroupIds = CRM_Core_BAO_Discount::getOptionGroup($params['template_id'], "civicrm_event");
        foreach ($optionGroupIds as $id) {
          $discountSuffix = '.discount.' . CRM_Core_DAO::getFieldValue(
            'CRM_Core_DAO_OptionGroup',
            $id,
            'label'
          );
          CRM_Core_BAO_OptionGroup::copyValue(
            'event',
            $params['template_id'],
            $event->id,
            FALSE,
            $discountSuffix
          );
        }
      }

      // copy price sets if any

      $priceSetId = CRM_Price_BAO_Set::getFor('civicrm_event', $params['template_id']);
      if ($priceSetId) {
        CRM_Price_BAO_Set::addTo('civicrm_event', $event->id, $priceSetId);
      }

      // link profiles if none linked
      $ufParams = ['entity_table' => 'civicrm_event', 'entity_id' => $event->id];

      if (!CRM_Core_BAO_UFJoin::findUFGroupId($ufParams)) {
        CRM_Core_DAO::copyGeneric(
          'CRM_Core_DAO_UFJoin',
          ['entity_id' => $params['template_id'], 'entity_table' => 'civicrm_event'],
          ['entity_id' => $event->id]
        );
      }

      // if no Tell-a-Friend defined, check whether there’s one for template and copy if so
      $tafParams = ['entity_table' => 'civicrm_event', 'entity_id' => $event->id];

      if (!CRM_Friend_BAO_Friend::getValues($tafParams)) {
        $tafParams['entity_id'] = $params['template_id'];
        if (CRM_Friend_BAO_Friend::getValues($tafParams)) {
          $tafParams['entity_id'] = $event->id;
          CRM_Friend_BAO_Friend::addTellAFriend($tafParams);
        }
      }
    }

    $this->set('id', $event->id);

    if ($this->_action & CRM_Core_Action::ADD) {
      $url = 'civicrm/event/manage/location';
      $urlParams = "action=update&reset=1&id={$event->id}";
      // special case for 'Save and Done' consistency.
      if ($this->controller->getButtonName('submit') == "_qf_EventInfo_upload_done") {
        $url = 'civicrm/event/manage';
        $urlParams = 'reset=1';
        CRM_Core_Session::setStatus(ts(
          "'%1' information has been saved.",
          [1 => $this->getTitle()]
        ));
      }

      CRM_Utils_System::redirect(CRM_Utils_System::url($url, $urlParams));
    }

    parent::endPostProcess();
  }
  //end of function

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Event Information and Settings');
  }

  /**
   * Retrieve event template custom data values
   * and set as default values for current new event.
   *
   * @param int $templateId event template id.
   *
   * @return array $defaults an array of custom data defaults.
   */
  public function templateCustomDataValues($templateId) {
    $defaults = [];
    if (!$templateId) {
      return $defaults;
    }

    // pull template custom data as a default for event, CRM-5596
    $groupTree = CRM_Core_BAO_CustomGroup::getTree($this->_type, $this, $templateId, NULL, $this->_subType);
    $groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, $this->_groupCount, $this);
    $customValues = [];
    CRM_Core_BAO_CustomGroup::setDefaults($groupTree, $customValues);
    foreach ($customValues as $key => $val) {
      if ($fieldKey = CRM_Core_BAO_CustomField::getKeyID($key)) {
        $defaults["custom_{$fieldKey}_-1"] = $val;
      }
    }

    return $defaults;
  }
}
