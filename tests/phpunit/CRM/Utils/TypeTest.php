<?php

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class CRM_Utils_TypeTest
 * @package CiviCRM
 * @subpackage CRM_Utils_Type
 * @group headless
 */
class CRM_Utils_TypeTest extends CiviUnitTestCase {

  /**
   * @before
   */
  public function setUpTest() {
    parent::setUp();
  }

  /**
   * @after
   */
  public function tearDownTest() {
  }

  /**
   * @dataProvider validateDataProvider
   * @param $inputData
   * @param $inputType
   * @param $expectedResult
   */
  public function testValidate($inputData, $inputType, $expectedResult) {
    $validatedResult = CRM_Utils_Type::validate($inputData, $inputType, FALSE);
    $this->assertTrue($expectedResult === $validatedResult, "$inputData:::$validatedResult");
  }

  /**
   * @return array
   */
  public static function validateDataProvider() {
    return [
      [10, 'Int', 10],
      ['145E+3', 'Int', NULL],
      ['10', 'Integer', 10],
      [-10, 'Int', -10],
      ['-10', 'Integer', -10],
      ['-10foo', 'Int', NULL],
      [10, 'Positive', 10],
      ['145.0E+3', 'Positive', NULL],
      ['10', 'Positive', 10],
      [-10, 'Positive', NULL],
      ['-10', 'Positive', NULL],
      ['-10foo', 'Positive', NULL],
      ['a string', 'String', 'a string'],
      ['{"contact":{"contact_id":205}}', 'Json', '{"contact":{"contact_id":205}}'],
      ['{"contact":{"contact_id":!n†rude®}}', 'Json', NULL],
    ];
  }

  /**
   * @dataProvider escapeDataProvider
   * @param $inputData
   * @param $inputType
   * @param $expectedResult
   */
  public function testEscape($inputData, $inputType, $expectedResult) {
    $escapedResult = CRM_Utils_Type::escape($inputData, $inputType, FALSE);
    $this->assertTrue($expectedResult === $escapedResult, "$inputData:::$escapedResult");
  }

  /**
   * @return array
   */
  public static function escapeDataProvider() {
    return [
      [10, 'Int', 10],
      ['145E+3', 'Int', NULL],
      ['10', 'Integer', 10],
      [-10, 'Int', -10],
      [[], 'Integer', NULL],
      ['-10foo', 'Int', NULL],
      [10, 'Positive', 10],
      ['145.0E+3', 'Positive', NULL],
      ['10', 'Positive', 10],
      [-10, 'Positive', NULL],
      ['-10', 'Positive', NULL],
      ['-10foo', 'Positive', NULL],
      ['', 'Timestamp', ''],
      ['', 'ContactReference', ''],
      ['3', 'ContactReference', 3],

      # MysqlOrderBy / MysqlOrderByDirection
      ['asc', 'MysqlOrderByDirection', 'asc'],
      ['DESC', 'MysqlOrderByDirection', 'DESC'],
      ['DESCc', 'MysqlOrderByDirection', NULL],
      ['weight', 'MysqlOrderBy', '`weight`'],
      ['table.civicrm_column_name desc', 'MysqlOrderBy', '`table`.`civicrm_column_name` desc'],
      ['field(contribution_status_id,4,5,6) asc', 'MysqlOrderBy', 'field(`contribution_status_id`,4,5,6) asc'],
      ['field(contribution_status_id,4,5,6) asc, contact_id asc', 'MysqlOrderBy', 'field(`contribution_status_id`,4,5,6) asc, `contact_id` asc'],
      ['table.civicrm_column_name desc,other_column,another_column desc', 'MysqlOrderBy', '`table`.`civicrm_column_name` desc, `other_column`, `another_column` desc'],
      // SA-2026-33: subquery / function-call / stacked-query payloads must be rejected, not passed through.
      ['(SELECT sleep(5))', 'MysqlOrderBy', NULL],
      ['weight, (SELECT sleep(5))', 'MysqlOrderBy', NULL],
      ['weight; DROP TABLE civicrm_contact;', 'MysqlOrderBy', NULL],
      ['-3', 'ContactReference', NULL],
      // Escape function is meant for sql, not xss
      ['<p onclick="alert(\'xss\');">Hello</p>', 'Memo', '<p onclick=\\"alert(\\\'xss\\\');\\">Hello</p>'],

      # directory name
      ["abc.def.com/../", 'DirectoryName', 'abc.def.com'],
      ["abc.中テ험def.com/../", 'DirectoryName', 'abc.def.com'],

      # filename
      // Strings containing file system reserved characters
      ["file|name.txt", 'FileName', 'filename.txt'],
      ["file/name.txt", 'FileName', 'filename.txt'],
      ["file<name>.txt", 'FileName', 'filename.txt'],
      ["file:name.txt", 'FileName', 'filename.txt'],
      ["file\"name.txt", 'FileName', 'filename.txt'],
      ["file*name.txt", 'FileName', 'filename.txt'],
      ["file?name.txt", 'FileName', 'filename.txt'],
      ["file/../name.txt", 'FileName', 'filename.txt'],

      // Strings containing control characters
      ["file\x00name.txt", 'FileName', 'filename.txt'],
      ["file\x1Fname.txt", 'FileName', 'filename.txt'],

      // Strings containing URI reserved characters
      ["file#name.txt", 'FileName', 'filename.txt'],
      ["file[name].txt", 'FileName', 'filename.txt'],
      ["file@name.txt", 'FileName', 'filename.txt'],
      ["file!name.txt", 'FileName', 'filename.txt'],
      ['file$name.txt', 'FileName', 'filename.txt'],
      ["file&name.txt", 'FileName', 'filename.txt'],
      ["file'name.txt", 'FileName', 'filename.txt'],
      ["file(name).txt", 'FileName', 'filename.txt'],
      ["file+name.txt", 'FileName', 'filename.txt'],
      ["file,name.txt", 'FileName', 'filename.txt'],
      ["file;name.txt", 'FileName', 'filename.txt'],
      ["file=name.txt", 'FileName', 'filename.txt'],

      // Strings containing URL unsafe characters
      ["file{name}.txt", 'FileName', 'filename.txt'],
      ["file^name.txt", 'FileName', 'filename.txt'],
      ["file~name.txt", 'FileName', 'filename.txt'],
      ["file`name.txt", 'FileName', 'filename.txt'],

      // Strings starting with a dot, hyphen, or a combination of both
      [".filename.txt", 'FileName', 'filename.txt'],
      ["..filename.txt", 'FileName', 'filename.txt'],
      ["-filename.txt", 'FileName', 'filename.txt'],
      [".-filename.txt", 'FileName', 'filename.txt'],

      // multi language
      ["中文測試.txt", 'FileName', '中文測試.txt'],
      ["日本語テスト.txt", 'FileName', '日本語テスト.txt'],
      ["韓한국어 시험.txt", 'FileName', '韓한국어 시험.txt'],
    ];
  }

}
