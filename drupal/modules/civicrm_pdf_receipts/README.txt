This module provides Canadian Tax receipts for donors.

Drupal Installation:
---------------------
1.  Unzip the module in your Drupal sites/default/modules
2.  Enable the module in Drupal
3.  Go to Admin > Site Configuration > CiviCRM PDF Receipts
    a. enter your organization's information, including charitiable
       registration number
    b. upload your organization's logo (.png or .jpg)
    c. upload a scanned signature (.png or .jpg) of your financial
       director or someone otherwise authorized to sign tax receipts

CiviCRM Setup:
--------------------
Tax receipts are sent as PDF attachments to standard CiviCRM receipts.

To configure tax receipts in CiviCRM:
1.  Go to CiviCRM > Administer > CiviContribute > Contribution Types
2.  Make sure any tax-eligible donation types are marked "Deductible"
    (no PDF tax receipt will be sent for non-deductible contributions)
3.  Go to your contribution page (CiviCRM > Contributions > Manage
    Contribution Pages > Configure)
4.  Under "Thank You and Receipting":
    a. ensure "email receipt to contributor?" is checked
    b. enter a receipt message (this will be the content of the email
       message to which the PDF is attached)

Use:
--------------------
Tax receipts are automatically emailed when a donation is submitted
through a contribution page for a tax-deductible contribution type.

You can print or re-send tax receipts by viewing the contribution
(e.g. Contributions > Dashboard > View) and clicking the "Print
Receipt" or "Send Tax Receipt" button.

