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
 * This class generates form components for Premiums
 *
 */
class CRM_Contribute_Form_ManagePremiums extends CRM_Contribute_Form {

  /**
   * Function to pre  process the form
   *
   * @access public
   *
   * @return None
   */
  public function preProcess() {
    parent::preProcess();
  }

  /**
   * This function sets the default values for the form. Manage Premiums that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {

    $defaults = parent::setDefaultValues();
    if ($this->_id) {
      $params = ['id' => $this->_id];
      CRM_Contribute_BAO_ManagePremiums::retrieve($params, $tempDefaults);
      $imageUrl = (isset($tempDefaults['image'])) ? $tempDefaults['image'] : "";
      if (isset($tempDefaults['image']) && isset($tempDefaults['thumbnail'])) {
        $defaults['imageUrl'] = $tempDefaults['image'];
        $defaults['thumbnailUrl'] = $tempDefaults['thumbnail'];
        $defaults['imageOption'] = 'thumbnail';
        // assign thumbnailUrl to template so we can display current image in update mode
        $this->assign('thumbnailUrl', $defaults['thumbnailUrl']);
      }
      else {
        $defaults['imageOption'] = 'noImage';
      }
      if (isset($tempDefaults['thumbnail']) && isset($tempDefaults['image'])) {
        $this->assign('thumbURL', $tempDefaults['thumbnail']);
        $this->assign('imageURL', $tempDefaults['image']);
      }
      if (isset($tempDefaults['period_type'])) {
        $this->assign("showSubscriptions", TRUE);
      }
    }
    else {
      if ($this->_action & CRM_Core_Action::ADD) {
        $defaults['installments'] = 12;
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
    //parent::buildQuickForm( );

    if ($this->_action & CRM_Core_Action::PREVIEW) {

      CRM_Contribute_BAO_Premium::buildPremiumPreviewBlock($this, $this->_id);

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
    $this->add('text', 'name', ts('Name'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'name'), TRUE);
    $this->addRule('name', ts('A product with this name already exists. Please select another name.'), 'objectExists', ['CRM_Contribute_DAO_Product', $this->_id]);
    $this->add('text', 'sku', ts('SKU'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'sku'));

    $this->add('textarea', 'description', ts('Description'), 'rows=3, cols=60');

    $image['image'] = $this->createElement('radio', NULL, NULL, ts('Upload from my computer'), 'image', 'onclick="add_upload_file_block(\'image\');');
    $image['thumbnail'] = $this->createElement('radio', NULL, NULL, ts('Display image and thumbnail from these locations on the web:'), 'thumbnail', 'onclick="add_upload_file_block(\'thumbnail\');');
    $image['noImage'] = $this->createElement('radio', NULL, NULL, ts('Do not display an image'), 'noImage', 'onclick="add_upload_file_block(\'noImage\');');

    $this->addGroup($image, 'imageOption', ts('Premium Image'));
    $this->addRule('imageOption', ts('Please select an option for the premium image.'), 'required');

    $this->addElement('text', 'imageUrl', ts('Image URL'));
    $this->addRule('imageUrl', ts('Please enter the valid URL to display this image.'), 'url');
    $this->addElement('text', 'thumbnailUrl', ts('Thumbnail URL'));
    $this->addRule('thumbnailUrl', ts('Please enter the valid URL to display a thumbnail of this image.'), 'url');

    $this->add('file', 'uploadFile', ts('Image File Name'), 'onChange="select_option();"');
    $this->addRule('uploadFile', ts('Image could not be uploaded due to invalid type extension.'), 'imageFile', '1000x1000');


    $this->addNumber('price', ts('Market Value'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'price'), TRUE);
    $this->addRule('price', ts('Please enter the Market Value for this product.'), 'money');

    $this->addNumber('cost', ts('Actual Cost of Product'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'cost'));
    $this->addRule('price', ts('Please enter the Actual Cost of Product.'), 'money');

    $this->addNumber('min_contribution', ts('Non-Recurring Contribution').' - '.ts('Threshold'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'min_contribution'), TRUE);
    $this->addRule('min_contribution', ts('Please enter a monetary value for the Minimum Contribution Amount.'), 'money');

    $options = [
      'first' => ts('Non-Cumulative Mode'),
      'cumulative' => ts('Cumulative Mode'),
    ];
    $this->addRadio('calculate_mode', ts('Threshold').' - '.ts('Recurring Contribution'), $options, NULL, '<br>', TRUE);
    $this->addNumber('min_contribution_recur', ts('Threshold').' - '.ts('Recurring Contribution'), NULL, TRUE);
    $this->addNumber('installments', '', ['placeholder' => 99]);

    $this->add('textarea', 'options', ts('Options'), 'rows=3, cols=60');

    $this->add('select', 'period_type', ts('Period Type'), ['' => ts('- select -'), 'rolling' => ts('rolling'), 'fixed' => ts('fixed')]);

    $this->add('text', 'fixed_period_start_day', ts('Fixed Period Start Day'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'fixed_period_start_day'));


    $this->add('Select', 'duration_unit', ts('Duration Unit'), ['' => ts('- select -'), 'day' => ts('day'), 'week' => ts('week'), 'month' => ts('month'), 'year' => ts('year')]);

    $this->add('text', 'duration_interval', ts('Duration'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'duration_interval'));

    $this->add('Select', 'frequency_unit', ts('Frequency Unit'), ['' => ts('- select -'), 'day' => ts('day'), 'week' => ts('week'), 'month' => ts('month'), 'year' => ts('year')]);

    $this->add('text', 'frequency_interval', ts('Frequency Interval'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Product', 'frequency_interval'));

    $this->add('checkbox', 'stock_status', ts('Enabled?'));
    $this->addNumber('stock_qty', ts('Total inventory'));

    $this->add('checkbox', 'is_active', ts('Enabled?'));

    $this->addFormRule(['CRM_Contribute_Form_ManagePremiums', 'formRule']);

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
          'name' => ts('Cancel'),
        ],
      ]
    );

    $this->assign('productId', $this->_id);
  }

  /**
   * Function for validation
   *
   * @param array $params (ref.) an assoc array of name/value pairs
   *
   * @param $files
   *
   * @return mixed true or array of errors
   * @access public
   * @static
   */
  public static function formRule($params, $files) {
    if (isset($params['imageOption'])) {
      if ($params['imageOption'] == 'thumbnail') {
        if (!$params['imageUrl']) {
          $errors['imageUrl'] = "Image URL is Reqiured ";
        }
        if (!$params['thumbnailUrl']) {
          $errors['thumbnailUrl'] = "Thumbnail URL is Reqiured ";
        }
      }
    }

    if (!$params['period_type']) {
      if ($params['fixed_period_start_day'] || $params['duration_unit'] || $params['duration_interval'] ||
        $params['frequency_unit'] || $params['frequency_interval']
      ) {
        $errors['period_type'] = ts('Please select the Period Type for this subscription or service.');
      }
    }

    if ($params['period_type'] == 'fixed' && !$params['fixed_period_start_day']) {
      $errors['fixed_period_start_day'] = ts('Please enter a Fixed Period Start Day for this subscription or service.');
    }

    if ($params['duration_unit'] && !$params['duration_interval']) {
      $errors['duration_interval'] = ts('Please enter the Duration Interval for this subscription or service.');
    }

    if ($params['duration_interval'] && !$params['duration_unit']) {
      $errors['duration_unit'] = ts('Please enter the Duration Unit for this subscription or service.');
    }

    if ($params['frequency_interval'] && !$params['frequency_unit']) {
      $errors['frequency_unit'] = ts('Please enter the Frequency Unit for this subscription or service.');
    }

    if ($params['frequency_unit'] && !$params['frequency_interval']) {
      $errors['frequency_interval'] = ts('Please enter the Frequency Interval for this subscription or service.');
    }

    if (!empty($params['stock_status']) && empty($params['stock_qty'])) {
      $errors['stock_qty'] = ts('Please enter the total inventory quantity when inventory management is enabled.');
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
      CRM_Contribute_BAO_ManagePremiums::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected Premium Product type has been deleted.'));
    }
    else {
      $params = $this->controller->exportValues($this->_name);
      $imageFile = CRM_Utils_Array::value('uploadFile', $params);
      $imageFile = $imageFile['name'];

      $config = &CRM_Core_Config::singleton();

      $ids = [];
      $error = FALSE;
      // store the submitted values in an array

      // FIX ME
      if (CRM_Utils_Array::value('imageOption', $params, FALSE)) {
        $value = CRM_Utils_Array::value('imageOption', $params, FALSE);
        if ($value == 'image') {
          if ($imageFile) {
            $imageFileName = preg_replace('/\\.[^.\\s]{3,4}$/', '', $imageFile);
            $ext = str_replace($imageFileName, '', $imageFile);
            // to check wether GD is installed or not
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
        $ids['premium'] = $this->_id;
      }

      // fix the money fields
      foreach (['cost', 'price', 'min_contribution', 'min_contribution_recur'] as $f) {
        $params[$f] = CRM_Utils_Rule::cleanMoney($params[$f]);
      }

      if ($params['calculate_mode'] !== 'cumulative') {
        $params['installments'] = 0;
      }

      $premium = CRM_Contribute_BAO_ManagePremiums::add($params, $ids);
      if ($error) {
        CRM_Core_Session::setStatus(ts('NOTICE: No thumbnail of your image was created because the GD image library is not currently compiled in your PHP installation. Product is currently configured to use default thumbnail image. If you have a local thumbnail image you can upload it separately and input the thumbnail URL by editing this premium.'));
      }
      else {
        CRM_Core_Session::setStatus(ts("The Premium '%1' has been saved.", [1 => $premium->name]));
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

