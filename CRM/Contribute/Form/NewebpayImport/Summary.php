<?php

/**
 * Form for displaying the summary of Newebpay contribution imports.
 */
class CRM_Contribute_Form_NewebpayImport_Summary extends CRM_Core_Form {

  protected $_successedContribution;

  /**
   * Set up variables before the form is built.
   *
   * This method retrieves the results of the import process (successes,
   * status modifications, and errors) from the session and assigns them
   * to the template for display. It also sets up download links for erroneous data.
   *
   * @return void
   */
  public function preProcess() {
    $successedTableHeader = $this->get('successedTableHeader');
    $this->_successedContribution = $this->get('successedContribution');
    $this->assign('successedTableHeader', $successedTableHeader);
    $this->assign('successedContribution', $this->_successedContribution);
    $this->assign('successedHeaderText', ts("Contribution data that matches"));

    $statusContent = $this->get('modifyStatusContribution');
    if (!empty($statusContent)) {
      $statusHeader = $this->get('modifyStatusHeader');
      foreach ($statusContent as &$row) {
        $row[ts('Contribution Status')] = ts('Completed');
      }
      $this->assign('modifyStatusHeader', $statusHeader);
      $this->assign('modifyStatusContribution', $statusContent);
      $this->assign('modifyStatusBlockHeaderText', ts("Contribution data with inconsistent 'status' (import after modifying status)"));
    }

    $errorContent = $this->get('errorContribution');
    if (!empty($errorContent)) {
      $errorHeader = $this->get('errorHeader');
      $this->assign('errorTableHeader', $errorHeader);
      $this->assign('errorContribution', $errorContent);
      $this->assign('errorBlockHeaderText', ts("Erroneous contribution data (cannot be imported)"));
    }

    $query = "_qf_Preview_display=true&qfKey={$this->controller->_key}&downloadType=";
    $queryError = $query.'error';
    $downloadErrorUrl = CRM_Utils_System::url('civicrm/contribute/newebpay/import', $queryError);
    $this->assign('downloadErrorUrl', $downloadErrorUrl);

    $queryStatus = $query.'status';
    $downloadStatusUrl = CRM_Utils_System::url('civicrm/contribute/newebpay/import', $queryStatus);
    $this->assign('downloadStatusUrl', $downloadStatusUrl);
  }

  /**
   * Actually build the form components.
   *
   * Adds a 'Done' button to finish the import wizard.
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->addButtons(
      [
        ['type' => 'cancel',
          'name' => ts('Done'),
        ],
      ]
    );
  }

  /**
   * Process the form submission.
   *
   * This method is a placeholder as no specific logic is needed after
   * displaying the summary.
   *
   * @return void
   */
  public function postProcess() {
  }

  /**
   * Return a descriptive name for the page, used in wizard header.
   *
   * @return string the descriptive page title
   */
  public function getTitle() {
    return ts('Summary');
  }
}
