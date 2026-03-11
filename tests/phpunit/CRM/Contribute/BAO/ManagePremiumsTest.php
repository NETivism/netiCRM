<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CRM/Contribute/BAO/ManagePremiums.php';

class CRM_Contribute_BAO_ManagePremiumsTest extends CiviUnitTestCase {

  public function get_info() {
    return [
                 'name'        => 'ManagePremiums BAOs',
                 'description' => 'Test all Contribute_BAO_Contribution methods.',
                 'group'       => 'CiviCRM BAO Tests',
                 ];
  }

  public function setUp() {
    parent::setUp();
  }

  /**
   * check method add()
   */
  public function testAdd() {
    $ids    = [ ];
    $params =  [
                     'name' => 'Test Product',
                     'sku'  => 'TP-10',
                     'imageOption' => 'noImage',
                     'price' => 12,
                     'cost' => 5,
                     'min_contribution' => 5,
                     'is_active' => 1,

                    ];

    $product = CRM_Contribute_BAO_ManagePremiums::add($params, $ids);

    $result = $this->assertDBNotNull(
      'CRM_Contribute_BAO_ManagePremiums',
      $product->id,
      'sku',
      'id',
      'Database check on updated product record.'
    );

    $this->assertEquals($result, 'TP-10', 'Verify products sku.');
  }

  /**
   * check method retrieve( )
   */
  public function testRetrieve() {
    $ids    = [ ];
    $params =  [
                     'name' => 'Test Product',
                     'sku'  => 'TP-10',
                     'imageOption' => 'noImage',
                     'price' => 12,
                     'cost' => 5,
                     'min_contribution' => 5,
                     'is_active' => 1,
                    ];

    $product = CRM_Contribute_BAO_ManagePremiums::add($params, $ids);
    $params  = [ 'id' => $product->id ];
    $default = [ ];
    $result  = CRM_Contribute_BAO_ManagePremiums::retrieve($params, $default);
    $this->assertEquals(empty($result), FALSE, 'Verify products record.');
  }

  /**
   * check method setIsActive( )
   */
  public function testSetIsActive() {
    $ids    = [ ];
    $params =  [
                     'name' => 'Test Product',
                     'sku'  => 'TP-10',
                     'imageOption' => 'noImage',
                     'price' => 12,
                     'cost' => 5,
                     'min_contribution' => 5,
                     'is_active' => 1,
                    ];

    $product = CRM_Contribute_BAO_ManagePremiums::add($params, $ids);
    CRM_Contribute_BAO_ManagePremiums::setIsActive($product->id, 0);

    $isActive = $this->assertDBNotNull(
      'CRM_Contribute_BAO_ManagePremiums',
      $product->id,
      'is_active',
      'id',
      'Database check on updated for product records is_active.'
    );

    $this->assertEquals($isActive, 0, 'Verify product records is_active.');

  }

  /**
   * check method del( )
   */
  public function testDel() {
    $ids    = [ ];
    $params =  [
                     'name' => 'Test Product',
                     'sku'  => 'TP-10',
                     'imageOption' => 'noImage',
                     'price' => 12,
                     'cost' => 5,
                     'min_contribution' => 5,
                     'is_active' => 1,
                    ];

    $product = CRM_Contribute_BAO_ManagePremiums::add($params, $ids);

    CRM_Contribute_BAO_ManagePremiums::del($product->id);

    $params  = ['id' => $product->id ];
    $default = [ ];
    $result  = CRM_Contribute_BAO_ManagePremiums::retrieve($params, $defaults);

    $this->assertEquals(empty($result), TRUE, 'Verify product record deletion.');

  }
}
