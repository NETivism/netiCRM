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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

/**
 * form to process actions for adding premium combination to contribution page
 */
class CRM_Contribute_Form_ContributionPage_AddPremiumsCombination extends CRM_Contribute_Form_ContributionPage_AddProduct {

  static $_combinations;
  static $_combination_id;
  protected $_action;
  protected $_isEdit = FALSE;

  /**
   * Function to pre process the form
   *
   * @access public
   *
   * @return None
   */
  public function preProcess() {
    parent::preProcess();

    $this->_combination_id = CRM_Utils_Request::retrieve('combination_id', 'Positive', $this, FALSE, 0);
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, CRM_Core_Action::ADD);

    // Check if this is an edit action
    if ($this->_combination_id) {
      $this->_isEdit = TRUE;
    }
    // For adding existing combinations to page
    if ($this->_action == CRM_Core_Action::ADD && !$this->_isEdit) {
      $this->_combinations = CRM_Contribute_BAO_PremiumsCombination::getCombinations($this->_id, TRUE, TRUE);
    }

    // If no combination_id parameter and action is add, treat it as creating new combination
    if ($this->_action == CRM_Core_Action::ADD && !$this->_combination_id) {
      $this->_isEdit = TRUE;
    }
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    $defaults = [];

    if ($this->_isEdit && $this->_combination_id) {
      // For editing existing combination
      $defaults = $this->_loadCombinationDefaults();
    } elseif (!$this->_isEdit && $this->_combination_id) {
      // For adding existing combination to page
      $defaults = $this->_loadSelectionDefaults();
    } else {
      // For creating new combination
      $defaults = $this->_loadNewCombinationDefaults();
    }
    $defaults = $this->_setDefaultWeight($defaults);

    return $defaults;
  }

  /**
   * load Combination Defaults
   */
  private function _loadCombinationDefaults() {
    $defaults = [];
    $dao = new CRM_Contribute_DAO_PremiumsCombination();
    $dao->id = $this->_combination_id;
    $dao->find(TRUE);

    $defaults['combination_name'] = $dao->combination_name;
    $defaults['description'] = $dao->description;
    $defaults['sku'] = $dao->sku;
    $defaults['min_contribution'] = $dao->min_contribution;
    $defaults['min_contribution_recur'] = $dao->min_contribution_recur;
    $defaults['currency'] = $dao->currency;
    $defaults['is_active'] = $dao->is_active;
    $defaults['weight'] = $dao->weight;
    $defaults['calculate_mode'] = $dao->calculate_mode;
    $defaults['installments'] = $dao->installments;
    $defaults['image'] = $dao->image;
    $defaults['thumbnail'] = $dao->thumbnail;

    // Handle image options
    if ($dao->image && $dao->thumbnail) {
      $defaults['imageUrl'] = $dao->image;
      $defaults['thumbnailUrl'] = $dao->thumbnail;
      $defaults['imageOption'] = 'thumbnail';
      $this->assign('thumbnailUrl', $dao->thumbnail);
      $this->assign('thumbURL', $dao->thumbnail);
      $this->assign('imageURL', $dao->image);
    } else {
      $defaults['imageOption'] = 'noImage';
    }

    // Load associated products (using 1-based indexing to match form structure)
    $productDao = new CRM_Contribute_DAO_PremiumsCombinationProducts();
    $productDao->combination_id = $this->_combination_id;
    $productDao->find();
    $index = 1; // Start from 1 to match product[1], product[2], etc.
    while ($productDao->fetch() && $index <= 6) {
      $defaults['product'][$index] = $productDao->product_id;
      $defaults['quantity'][$index] = $productDao->quantity;
      $index++;
    }

    return $defaults;
  }

  /**
   * load Selection Defaults
   */
  private function _loadSelectionDefaults() {
    $defaults = [];
    $dao = new CRM_Contribute_DAO_PremiumsCombination();
    $dao->id = $this->_combination_id;
    $dao->find(TRUE);
    $defaults['combination_id'] = $dao->id;
    $defaults['weight'] = $dao->weight;

    return $defaults;
  }

  /**
   * load New Combination Defaults
   */
  private function _loadNewCombinationDefaults() {
    $defaults = [];
    $defaults['installments'] = 12;
    $defaults['is_active'] = 1;
    $config = CRM_Core_Config::singleton();
    $defaults['currency'] = $config->defaultCurrency;

    return $defaults;
  }

  /**
   * set Default Weight
   */
  private function _setDefaultWeight($defaults) {
    if (!isset($defaults['weight']) || !$defaults['weight']) {
      $pageID = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, 0);
      $dao = new CRM_Contribute_DAO_Premium();
      $dao->entity_table = 'civicrm_contribution_page';
      $dao->entity_id = $pageID;
      $dao->find(TRUE);
      $premiumID = $dao->id;

      $sql = 'SELECT max( weight ) as max_weight FROM civicrm_premiums_combination WHERE premiums_id = %1';
      $params = [1 => [$premiumID, 'Integer']];
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      $dao->fetch();
      $defaults['weight'] = ($dao->max_weight ?? 0) + 1;
    }

    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    // Handle DELETE and PREVIEW actions using parent logic
    if ($this->_action & (CRM_Core_Action::DELETE | CRM_Core_Action::PREVIEW)) {
      $this->_handleSpecialActions();
      return;
    }

    // If editing or creating a combination, a combination form is required.
    if ($this->_isEdit) {
      $this->_buildCombinationForm();
      $this->_addCombinationButtons();
    } else {
      $this->_buildSelectionForm();
      parent::buildQuickForm();
    }
    $this->assign('isEditMode', $this->_isEdit);
  }

  /**
   * Handle DELETE, PREVIEW action
   */
  private function _handleSpecialActions() {
    $urlParams = 'civicrm/admin/contribute/premium';

    if ($this->_action & CRM_Core_Action::DELETE) {
      $session = CRM_Core_Session::singleton();
      $url = CRM_Utils_System::url($urlParams, 'reset=1&action=update&id=' . $this->_id);
      $session->pushUserContext($url);

      if (CRM_Utils_Request::retrieve('confirmed', 'Boolean',
          CRM_Core_DAO::$_nullObject, '', '', 'GET'
        )) {
        if ($this->_isEdit) {
          // Delete combination and products
          CRM_Contribute_BAO_PremiumsCombination::del($this->_combination_id);
          CRM_Core_Session::setStatus(ts('Selected Premium Combination has been deleted.'));
        } else {
          // Only remove the page association
          $dao = new CRM_Contribute_DAO_PremiumsCombination();
          $dao->id = $this->_combination_id;
          $dao->premiums_id = NULL;
          $dao->save();
          CRM_Core_Session::setStatus(ts('Selected Premium Combination has been removed from this Contribution Page.'));
        }
        CRM_Utils_System::redirect($url);
      }

      $this->addButtons([
          ['type' => 'next',
            'name' => ts('Delete'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
          ],
          ['type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );
    }
    elseif ($this->_action & CRM_Core_Action::PREVIEW) {
      CRM_Contribute_BAO_Premium::buildCombinationPreviewBlock($this, $this->_combination_id);
      $this->addButtons([
          ['type' => 'next',
            'name' => ts('Done with Preview'),
            'isDefault' => TRUE,
          ],
        ]
      );
    }
  }

  /**
   * build Selection Form
   */
  private function _buildSelectionForm() {
    $session = CRM_Core_Session::singleton();
    $urlParams = 'civicrm/admin/contribute/premium';
    $url = CRM_Utils_System::url($urlParams, 'reset=1&action=update&id=' . $this->_id);
    $session->pushUserContext($url);

    $this->add('select', 'combination_id', ts('Select Premium Combination'), $this->_combinations, TRUE);
    $this->addElement('text', 'weight', ts('Weight'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_PremiumsCombination', 'weight'));
    $this->addRule('weight', ts('Please enter integer value for weight'), 'integer');
  }

  /**
   * add Combination Buttons
   */
  private function _addCombinationButtons() {
    $session = CRM_Core_Session::singleton();
    $urlParams = 'civicrm/admin/contribute/premium';
    $url = CRM_Utils_System::url($urlParams, 'reset=1&action=update&id=' . $this->_id);
    $session->pushUserContext($url);

    $this->addElement('text', 'weight', ts('Weight'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_PremiumsCombination', 'weight'));
    $this->addRule('weight', ts('Please enter integer value for weight'), 'integer');
    $this->addButtons([
        [
          'type' => 'upload',
          'name' => ts('Save'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );
  }

  /**
   * Build form for editing combination details
   *
   * @return void
   * @access private
   */
  private function _buildCombinationForm() {
    $this->applyFilter('__ALL__', 'trim');
    $this->add('text', 'combination_name', ts('Premium Combination Name'), 
      CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_PremiumsCombination', 'combination_name'), TRUE);

    $this->add('text', 'sku', ts('SKU'), 
      CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_PremiumsCombination', 'sku'));
    $this->add('textarea', 'description', ts('Description'), 'rows=3, cols=60');

    $image['image'] = $this->createElement('radio', NULL, NULL, ts('Upload from my computer'), 'image', 
      'onclick="add_upload_file_block(\'image\');"');
    $image['thumbnail'] = $this->createElement('radio', NULL, NULL, ts('Display image and thumbnail from these locations on the web:'), 'thumbnail', 
      'onclick="add_upload_file_block(\'thumbnail\');"');
    $image['noImage'] = $this->createElement('radio', NULL, NULL, ts('Do not display an image'), 'noImage', 
      'onclick="add_upload_file_block(\'noImage\');"');

    $this->addGroup($image, 'imageOption', ts('Premium Image'));
    $this->addRule('imageOption', ts('Please select an option for the premium image.'), 'required');

    $this->addElement('text', 'imageUrl', ts('Image URL'));
    $this->addRule('imageUrl', ts('Please enter the valid URL to display this image.'), 'url');
    $this->addElement('text', 'thumbnailUrl', ts('Thumbnail URL'));
    $this->addRule('thumbnailUrl', ts('Please enter the valid URL to display a thumbnail of this image.'), 'url');

    $this->add('file', 'uploadFile', ts('Image File Name'), 'onChange="select_option();"');
    $this->addRule('uploadFile', ts('Image could not be uploaded due to invalid type extension.'), 'imageFile', '1000x1000');
    // Set upload element for correct file handling
    $this->addUploadElement('uploadFile');

    $this->addNumber('min_contribution', ts('Min Contribution'), 
      CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_PremiumsCombination', 'min_contribution'), TRUE);
    $this->addRule('min_contribution', ts('Please enter the Minimum Contribution Amount for this combination.'), 'money');

    $options = [
      'first' => ts('Non-Cumulative Mode'),
      'cumulative' => ts('Cumulative Mode'),
    ];
    $this->addRadio('calculate_mode', ts('Calculate Mode'), $options, NULL, '<br>', TRUE);

    $this->addNumber('min_contribution_recur', ts('Min Contribution Recur'), NULL, TRUE);
    $this->addNumber('installments', '', ['placeholder' => 12]);

    $availableProducts = CRM_Contribute_PseudoConstant::products();
    $this->assign('availableProducts', $availableProducts);

    // Create array-based form elements for products (up to 6 products)
    for ($i = 1; $i <= 6; $i++) {
      $this->add('select', "product[{$i}]", ts('Product'), 
        ['' => ts('-- Select --')] + $availableProducts, FALSE);
      $this->add('text', "quantity[{$i}]", ts('Quantity'), [
        'size' => 3, 
        'maxlength' => 3,
        'value' => '1'
      ]);
    }

    $this->add('checkbox', 'is_active', ts('Enabled?'));

    $this->addFormRule(['CRM_Contribute_Form_ContributionPage_AddPremiumsCombination', 'formRule']);

    $this->assign('combinationId', $this->_combination_id);
    $this->assign('isEditMode', $this->_isEdit);

    // Set correct upload directory for image files
    $config = CRM_Core_Config::singleton();
    $uploadNames = $this->get('uploadNames');
    if (!empty($uploadNames)) {
      $this->controller->addUploadAction($config->imageUploadDir, $uploadNames);
    }
  }

  /**
   * Function for validation
   *
   * @param array $params (ref.) an assoc array of name/value pairs
   * @param $files
   *
   * @return mixed true or array of errors
   * @access public
   * @static
   */
  public static function formRule($params, $files) {
    $errors = [];

    // Always check for products since we're always in edit mode now
    $hasSelectedProduct = FALSE;
    if (!empty($params['product']) && is_array($params['product'])) {
      foreach ($params['product'] as $productId) {
        if ($productId) {
          $hasSelectedProduct = TRUE;
          break;
        }
      }
    }

    if (!$hasSelectedProduct) {
      $errors['_qf_default'] = ts('Please select at least one product for the combination.');
    }

    if (isset($params['imageOption'])) {
      if ($params['imageOption'] == 'thumbnail') {
        if (!$params['imageUrl']) {
          $errors['imageUrl'] = ts('Image URL is required');
        }
        if (!$params['thumbnailUrl']) {
          $errors['thumbnailUrl'] = ts('Thumbnail URL is required');
        }
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    if ($this->_action & CRM_Core_Action::PREVIEW) {
      $this->_handlePreviewAction();
      return;
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->_handleDeleteAction();
      return;
    }
    if ($this->_isEdit) {
      $this->_processCombinationEdit($params);
    } else {
      $this->_processCombinationSelection($params);
    }
  }

  private function _handlePreviewAction() {
    $urlParams = 'civicrm/admin/contribute/premium';
    $url = CRM_Utils_System::url($urlParams, 'reset=1&action=update&id=' . $this->_id);
    CRM_Utils_System::redirect($url);
  }

  private function _handleDeleteAction() {
    $urlParams = 'civicrm/admin/contribute/premium';
    $url = CRM_Utils_System::url($urlParams, 'reset=1&action=update&id=' . $this->_id);
    CRM_Contribute_BAO_PremiumsCombination::del($this->_combination_id);
    CRM_Core_Session::setStatus(ts('Selected Premium Combination has been deleted.'));
    CRM_Utils_System::redirect($url);
  }

  private function _processCombinationEdit($params) {
    $pageID = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, 0);

    $dao = new CRM_Contribute_DAO_Premium();
    $dao->entity_table = 'civicrm_contribution_page';
    $dao->entity_id = $pageID;
    $dao->find(TRUE);
    $premiumID = $dao->id;

    // Save combination details
    $this->_saveCombinationDetails($params, $premiumID);
    
    $urlParams = 'civicrm/admin/contribute/premium';
    $url = CRM_Utils_System::url($urlParams, 'reset=1&action=update&id=' . $this->_id);
    CRM_Utils_System::redirect($url);
  }

  private function _processCombinationSelection($params) {
    $pageID = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, 0);
    $urlParams = 'civicrm/admin/contribute/premium';
    $url = CRM_Utils_System::url($urlParams, 'reset=1&action=update&id=' . $this->_id);

    // Get premiums_id
    $dao = new CRM_Contribute_DAO_Premium();
    $dao->entity_table = 'civicrm_contribution_page';
    $dao->entity_id = $pageID;
    $dao->find(TRUE);
    $premiumID = $dao->id;

    // Update the selected combination and assign it to this page
    $combinationDao = new CRM_Contribute_DAO_PremiumsCombination();
    $combinationDao->id = $params['combination_id'];
    $combinationDao->premiums_id = $premiumID;
    $combinationDao->weight = $params['weight'];
    $combinationDao->save();

    CRM_Core_Session::setStatus(ts('Premium combination has been added to this contribution page.'));
    CRM_Utils_System::redirect($url);
  }

  /**
   * Save combination details including products and images
   *
   * @param array $params
   * @param int $premiumID
   *
   * @return void
   * @access private
   */
  private function _saveCombinationDetails($params, $premiumID) {
    $params = $this->controller->exportValues($this->_name);
    $imageFile = CRM_Utils_Array::value('uploadFile', $params);
    $imageFileName = isset($imageFile['name']) ? basename($imageFile['name']) : '';
    $imageFile = $imageFile['name'] ?? '';

    $config = &CRM_Core_Config::singleton();
    $error = FALSE;

    // Handle image upload
    if (CRM_Utils_Array::value('imageOption', $params, FALSE)) {
      $value = CRM_Utils_Array::value('imageOption', $params, FALSE);
      if ($value == 'image') {
        if ($imageFile) {
          $imageFileName = preg_replace('/\\.[^.\\s]{3,4}$/', '', $imageFileName);
          $ext = str_replace($imageFileName, '', basename($imageFile));
          $gdSupport = CRM_Utils_System::getModuleSetting('gd', 'GD Support');
          if($gdSupport) {
            $error = false;
            $params['image'] = $this->_resizeImage($imageFile, $config->imageUploadDir . $imageFileName."_full".$ext, 1200, 1200);
            $params['thumbnail'] = $this->_resizeImage($imageFile, $config->imageUploadDir . $imageFileName."_thumb".$ext, 480, 480);
          }
          else {
            $error = true;
            $params['image'] = $config->resourceBase . 'i/contribute/default_premium.jpg';
            $params['thumbnail'] = $config->resourceBase . 'i/contribute/default_premium_thumb.jpg';
          }
        }
      }
      elseif ($value == 'thumbnail') {
        $params['image'] = $params['imageUrl'];
        $params['thumbnail'] = $params['thumbnailUrl'];
      }
      else {
        $params['image'] = "";
        $params['thumbnail'] = "";
      }
    }

    // Clean money fields
    foreach (['min_contribution', 'min_contribution_recur'] as $f) {
      $params[$f] = CRM_Utils_Rule::cleanMoney($params[$f]);
    }

    if ($params['calculate_mode'] !== 'cumulative') {
      $params['installments'] = 0;
    }

    // Save combination
    $dao = new CRM_Contribute_DAO_PremiumsCombination();
    if ($this->_combination_id) {
      $params['id'] = $this->_combination_id;
    } else {
      // Set premiums_id for new combinations
      $params['premiums_id'] = $premiumID;
    }
    $dao->copyValues($params);
    $dao->save();
    $combinationId = $dao->id;

    // Clear existing products
    $deleteDao = new CRM_Contribute_DAO_PremiumsCombinationProducts();
    $deleteDao->combination_id = $combinationId;
    $deleteDao->delete();

    // Save selected products
    if (!empty($params['product']) && is_array($params['product'])) {
      foreach ($params['product'] as $index => $productId) {
        if ($productId) {
          $quantity = CRM_Utils_Array::value($index, $params['quantity'], 1);
          
          $productDao = new CRM_Contribute_DAO_PremiumsCombinationProducts();
          $productDao->combination_id = $combinationId;
          $productDao->product_id = $productId;
          $productDao->quantity = $quantity;
          $productDao->save();
        }
      }
    }

    if ($error) {
      CRM_Core_Session::setStatus(ts('NOTICE: No thumbnail of your image was created because the GD image library is not currently compiled in your PHP installation. Combination is currently configured to use default thumbnail image. If you have a local thumbnail image you can upload it separately and input the thumbnail URL by editing this premium combination.'));
    }
    else {
      CRM_Core_Session::setStatus(ts("The Premium Combination '%1' has been saved.", [1 => $dao->combination_name]));
    }
  }

  /**
   * Resize a premium image to a different size
   *
   * @access private
   *
   * @param string $filename
   * @param string $resizedName
   * @param $width
   * @param $height
   *
   * @return Path to image
   */
  private function _resizeImage($fileName, $resizedName, $w, $h) {
    $image = new CRM_Utils_Image($fileName, $resizedName);
    $resized = $image->scale($w, $h);
    $config = CRM_Core_Config::singleton();
    return $config->imageUploadURL.basename($resizedName);
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Add Premium Combination to Contribution Page');
  }
}