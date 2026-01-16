<?php
/**
 * Page for displaying stock log for a premium product
 */
class CRM_Contribute_Page_ProductStockLog extends CRM_Core_Page {

  /**
   * The product ID
   * @var int
   */
  protected $_productId;

  /**
   * The product name
   * @var string
   */
  protected $_productName;

  /**
   * Run the page.
   * @return void
   */
  function run() {
    // Get product_id from request
    $this->_productId = CRM_Utils_Request::retrieve('product_id', 'Positive', $this, TRUE);

    // Validate product exists and has stock management enabled
    $product = new CRM_Contribute_DAO_Product();
    $product->id = $this->_productId;

    if (!$product->find(TRUE)) {
      CRM_Core_Error::fatal(ts('Premium product not found.'));
    }

    if (empty($product->stock_status)) {
      CRM_Core_Error::fatal(ts('Stock management is not enabled for this premium product.'));
    }

    $this->_productName = $product->name;

    // Set page title
    CRM_Utils_System::setTitle(ts('Stock Log') . ' - ' . $this->_productName);

    // Set breadcrumb
    $breadcrumb = [
      [
        'title' => ts('Manage Premiums'),
        'url' => CRM_Utils_System::url('civicrm/admin/contribute/managePremiums', 'reset=1'),
      ],
    ];
    CRM_Utils_System::appendBreadCrumb($breadcrumb);

    // Get stock logs
    $logs = CRM_Contribute_BAO_ManagePremiums::getStockLogsByProductId($this->_productId);

    // Process logs for display
    $rows = [];
    foreach ($logs as $log) {
      $row = [
        'modified_date' => CRM_Utils_Date::customFormat($log['modified_date'], '%Y-%m-%d %H:%M:%S'),
        'stock_change' => $this->formatStockChange($log['type'], $log['quantity']),
        'contribution_id' => $log['contribution_id'],
        'contribution_url' => CRM_Utils_System::url('civicrm/contact/view/contribution',
          "action=view&reset=1&id={$log['contribution_id']}&cid=&context=search"),
        'reason' => $log['reason'] ?: '-',
      ];
      $rows[] = $row;
    }

    $this->assign('productName', $this->_productName);
    $this->assign('productId', $this->_productId);
    $this->assign('rows', $rows);

    parent::run();
  }

  /**
   * Format stock change for display
   *
   * @param string $type 'deduct' or 'restock'
   * @param int $quantity
   * @return string Formatted stock change description
   */
  protected function formatStockChange($type, $quantity) {
    if ($type === 'deduct') {
      return ts('Deduct premium: %1x%2', [1 => $this->_productName, 2 => $quantity]);
    }
    else {
      return ts('Restock premium: %1x%2', [1 => $this->_productName, 2 => $quantity]);
    }
  }
}
