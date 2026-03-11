<?php

require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CRM/Utils/String.php';

class CRM_Utils_StringTest extends CiviUnitTestCase {

  public function get_info() {
    return [
                 'name'        => 'String Test',
                 'description' => 'Test String Functions',
                 'group'       => 'CiviCRM BAO Tests',
                 ];
  }

  public function setUp() {
    parent::setUp();
  }

  public function testStripPathChars() {
    $testSet = [ '' => '',
                      NULL => NULL,
                      'civicrm' => 'civicrm',
                      'civicrm/dashboard' => 'civicrm/dashboard',
                      'civicrm/contribute/transact' => 'civicrm/contribute/transact',
                      'civicrm/<hack>attempt</hack>' => 'civicrm/_hack_attempt_/hack_',
                      'civicrm dashboard & force = 1,;' => 'civicrm_dashboard___force___1__'
                      ];

    foreach ($testSet as $in => $expected) {
      $out = CRM_Utils_String::stripPathChars($in);
      $this->assertEquals($out, $expected, "Output does not match");
    }
  }

  public function testExtractName() {
    $cases = [
                   [
                         'full_name' => 'Alan',
                         'first_name' => 'Alan',
                         ],
                   [
                         'full_name' => 'Alan Arkin',
                         'first_name' => 'Alan',
                         'last_name' => 'Arkin',
                         ],
                   [
                         'full_name' => '"Alan Arkin"',
                         'first_name' => 'Alan',
                         'last_name' => 'Arkin',
                         ],
                   [
                         'full_name' => 'Alan A Arkin',
                         'first_name' => 'Alan',
                         'middle_name' => 'A',
                         'last_name' => 'Arkin',
                         ],
                   [
                         'full_name' => 'Adams, Amy',
                         'first_name' => 'Amy',
                         'last_name' => 'Adams',
                         ],
                   [
                         'full_name' => 'Adams, Amy A',
                         'first_name' => 'Amy',
                         'middle_name' => 'A',
                         'last_name' => 'Adams',
                         ],
                   [
                         'full_name' => '"Adams, Amy A"',
                         'first_name' => 'Amy',
                         'middle_name' => 'A',
                         'last_name' => 'Adams',
                         ],
                   ];
    foreach ($cases as $case) {
      $actual = [];
      CRM_Utils_String::extractName($case['full_name'], $actual);
      $this->assertEquals($actual['first_name'], $case['first_name']);
      $this->assertEquals($actual['last_name'], $case['last_name']);
      $this->assertEquals($actual['middle_name'], $case['middle_name']);
    }
  }

}
