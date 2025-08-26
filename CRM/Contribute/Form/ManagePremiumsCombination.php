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
 * This class generates form components for Premium Combinations
 *
 */
class CRM_Contribute_Form_ManagePremiumsCombination extends CRM_Contribute_Form {

  /**
   * Function to pre process the form
   *
   * @access public
   *
   * @return None
   */
  public function preProcess() {
    parent::preProcess();
  }

  /**
   * This function sets the default values for the form. Manage Premium Combinations that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    if ($this->_id) {
      $dao = new CRM_Contribute_DAO_PremiumsCombination();
      $dao->id = $this->_id;
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

      $imageUrl = (isset($dao->image)) ? $dao->image : "";
      if (isset($dao->image) && isset($dao->thumbnail)) {
        $defaults['imageUrl'] = $dao->image;
        $defaults['thumbnailUrl'] = $dao->thumbnail;
        $defaults['imageOption'] = 'thumbnail';
        $this->assign('thumbnailUrl', $defaults['thumbnailUrl']);
      }
      else {
        $defaults['imageOption'] = 'noImage';
      }
      
      if (isset($dao->thumbnail) && isset($dao->image)) {
        $this->assign('thumbURL', $dao->thumbnail);
        $this->assign('imageURL', $dao->image);
      }
      $productDao = new CRM_Contribute_DAO_PremiumsCombinationProducts();
      $productDao->combination_id = $this->_id;
      $productDao->find();
      $selectedProducts = [];
      while ($productDao->fetch()) {
        $defaults["product_{$productDao->product_id}"] = 1;
        $defaults["quantity_{$productDao->product_id}"] = $productDao->quantity;
      }
    }
    else {
      if ($this->_action & CRM_Core_Action::ADD) {
        $defaults['installments'] = 12;
        $defaults['is_active'] = 1;
        $config = CRM_Core_Config::singleton();
        $defaults['currency'] = $config->defaultCurrency;
      }
    }

    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_action & CRM_Core_Action::PREVIEW) {
      // TODO: Preview
      $this->addButtons([
          ['type' => 'next',
            'name' => ts('Done with Preview'),
            'isDefault' => TRUE,
          ],
        ]
      );
      return;
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons([
          ['type' => 'next',
            'name' => ts('Delete'),
            'isDefault' => TRUE,
          ],
          ['type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );
      return;
    }

    $this->applyFilter('__ALL__', 'trim');

    // 組合名稱
    $this->add('text', 'combination_name', ts('Combination Name'), 
      CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_PremiumsCombination', 'combination_name'), TRUE);
    $this->addRule('combination_name', ts('A premium combination with this name already exists. Please select another name.'), 
      'objectExists', ['CRM_Contribute_DAO_PremiumsCombination', $this->_id]);

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

    $this->addElement('text', 'weight', ts('Weight'), 
      CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_PremiumsCombination', 'weight'));

    $this->add('checkbox', 'is_active', ts('Enabled?'));

    $this->addFormRule(['CRM_Contribute_Form_ManagePremiumsCombination', 'formRule']);

    $js = ['data' => 'click-once'];
    $this->addButtons([
        [
          'type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
          'js' => $js,
        ],
        [
          'type' => 'cancel',
          'name' => ts('取消'),
        ],
      ]
    );

    $this->assign('combinationId', $this->_id);
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

    $hasSelectedProduct = FALSE;
    foreach ($params as $key => $value) {
      if (strpos($key, 'product_') === 0 && $value) {
        $hasSelectedProduct = TRUE;
        break;
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
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::PREVIEW) {
      return;
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      // Remove the associated products.
      $productDao = new CRM_Contribute_DAO_PremiumsCombinationProducts();
      $productDao->combination_id = $this->_id;
      $productDao->delete();

      // Remove combination
      $dao = new CRM_Contribute_DAO_PremiumsCombination();
      $dao->id = $this->_id;
      $dao->delete();

      CRM_Core_Session::setStatus(ts('Selected Premium Combination has been deleted.'));
    }
    else {
      $params = $this->controller->exportValues($this->_name);
      $imageFile = CRM_Utils_Array::value('uploadFile', $params);
      $imageFile = $imageFile['name'];

      $config = &CRM_Core_Config::singleton();

      $ids = [];
      $error = FALSE;

      if (CRM_Utils_Array::value('imageOption', $params, FALSE)) {
        $value = CRM_Utils_Array::value('imageOption', $params, FALSE);
        if ($value == 'image') {
          if ($imageFile) {
            $imageFileName = preg_replace('/\\.[^.\\s]{3,4}$/', '', $imageFile);
            $ext = str_replace($imageFileName, '', $imageFile);
            $gdSupport = CRM_Utils_System::getModuleSetting('gd', 'GD Support');
            if($gdSupport) {
              $error = false;
              $params['image'] = $this->_resizeImage($imageFile, $imageFileName."_full".$ext, 1200, 1200);
              $params['thumbnail'] = $this->_resizeImage($imageFile, $imageFileName."_thumb".$ext, 480, 480);
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

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $ids['combination'] = $this->_id;
      }

      foreach (['min_contribution', 'min_contribution_recur'] as $f) {
        $params[$f] = CRM_Utils_Rule::cleanMoney($params[$f]);
      }

      if ($params['calculate_mode'] !== 'cumulative') {
        $params['installments'] = 0;
      }

      $dao = new CRM_Contribute_DAO_PremiumsCombination();
      if ($this->_id) {
        $params['id'] = $this->_id;
      }
      $dao->copyValues($params);
      $dao->save();
      $combinationId = $dao->id;

      $deleteDao = new CRM_Contribute_DAO_PremiumsCombinationProducts();
      $deleteDao->combination_id = $combinationId;
      $deleteDao->delete();


      foreach ($params as $key => $value) {
        if (strpos($key, 'new_product_') === 0 && !strpos($key, '_quantity') && $value) {
          $index = str_replace('new_product_', '', $key);
          $quantityKey = "new_product_quantity_{$index}";

          $productId = $value;
          $quantity = CRM_Utils_Array::value($quantityKey, $params, 1);
          
          // Only save when products have been selected.
          if ($productId) {
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
}