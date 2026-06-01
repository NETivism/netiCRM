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
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

class CRM_Core_BAO_FinancialTrxn extends CRM_Core_DAO_FinancialTrxn {
  public function __construct() {
    parent::__construct();
  }

  /**
   * Create a financial transaction record and associate it with an entity (e.g., contribution).
   *
   * @param array &$params associative array of financial transaction data
   *
   * @return CRM_Core_BAO_FinancialTrxn|bool the created trxn object, or FALSE on error
   */
  public static function create(&$params) {
    $trxn = new CRM_Core_DAO_FinancialTrxn();
    $trxn->copyValues($params);

    if (!CRM_Utils_Rule::currencyCode($trxn->currency)) {

      $config = CRM_Core_Config::singleton();
      $trxn->currency = $config->defaultCurrency;
    }

    // if a transaction already exists for a contribution id, lets get the finTrxnId and entityFinTrxId
    $fids = self::getFinancialTrxnIds($params['contribution_id'], 'civicrm_contribution');
    if ($fids['financialTrxnId']) {
      $trxn->id = $fids['financialTrxnId'];
    }
    else {
      $existsId = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_financial_trxn WHERE trxn_id = %1", [
        1 => [$trxn->trxn_id, 'String'],
      ]);
      if ($existsId) {
        // #37595, something may wrong here, make sure override exists records is correct
        $conflictContribution = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution WHERE trxn_id = %1 AND id != %2", [
          1 => [$trxn->trxn_id, 'String'],
          2 => [$params['contribution_id'], 'Integer'],
        ]);
        if ($conflictContribution) {
          CRM_Core_Error::debug_log_message(sprintf("Failed to add transaction record into civicrm_financial_trxn due to conflict between contribution %d:%d.", $conflictContribution, $params['contribution_id']));
          return FALSE;
        }
        else {
          $trxn->id = $existsId;
        }
      }
    }

    $trxn->save();

    $contributionAmount = CRM_Utils_Array::value('net_amount', $params);
    if (!$contributionAmount && isset($params['total_amount'])) {
      $contributionAmount = $params['total_amount'];
    }
    // save to entity_financial_trxn table
    $entity_financial_trxn_params = [
      'entity_table' => "civicrm_contribution",
      'entity_id' => $params['contribution_id'],
      'financial_trxn_id' => $trxn->id,
      //use net amount to include all received amount to the contribution
      'amount' => $contributionAmount,
      'currency' => $trxn->currency,
    ];
    $entity_trxn = new CRM_Core_DAO_EntityFinancialTrxn();
    $entity_trxn->copyValues($entity_financial_trxn_params);
    if ($fids['entityFinancialTrxnId']) {
      $entity_trxn->id = $fids['entityFinancialTrxnId'];
    }
    $entity_trxn->save();
    return $trxn;
  }

  /**
   * Get the IDs of the financial transaction and entity association for a specific entity.
   *
   * @param int $entity_id entity ID
   * @param string $entity_table name of the entity table (defaults to 'civicrm_contribution')
   *
   * @return array<string, mixed> [entityFinancialTrxnId, financialTrxnId]
   */
  public static function getFinancialTrxnIds($entity_id, $entity_table = 'civicrm_contribution') {
    $ids = ['entityFinancialTrxnId' => NULL, 'financialTrxnId' => NULL];

    $query = "
            SELECT id, financial_trxn_id
            FROM civicrm_entity_financial_trxn
            WHERE entity_id = %1
            AND entity_table = %2
        ";

    $sqlParams = [1 => [$entity_id, 'Integer'], 2 => [$entity_table, 'String']];
    $dao = CRM_Core_DAO::executeQuery($query, $sqlParams);
    if ($dao->fetch()) {
      $ids['entityFinancialTrxnId'] = $dao->id;
      $ids['financialTrxnId'] = $dao->financial_trxn_id;
    }

    return $ids;
  }

  /**
   * Delete a financial transaction and its associated entity record.
   *
   * @param int $entity_id entity ID
   * @param string $entity_table name of the entity table (defaults to 'civicrm_contribution')
   *
   * @return bool TRUE on success, FALSE otherwise
   */
  public static function deleteFinancialTrxn($entity_id, $entity_table = 'civicrm_contribution') {
    $fids = self::getFinancialTrxnIds($entity_id, $entity_table);

    if ($fids['financialTrxnId']) {
      // delete enity financial transaction before financial transaction since financial_trxn_id will be set to null if financial transaction deleted first
      $query = "
                DELETE FROM civicrm_entity_financial_trxn
	            WHERE financial_trxn_id = %1";
      CRM_Core_DAO::executeQuery($query, [1 => [$fids['financialTrxnId'], 'Integer']]);

      // delete financial transaction
      $query = "
	            DELETE FROM civicrm_financial_trxn
                WHERE id = %1";
      CRM_Core_DAO::executeQuery($query, [1 => [$fids['financialTrxnId'], 'Integer']]);

      return TRUE;
    }
    else {
      return FALSE;
    }
  }
}
