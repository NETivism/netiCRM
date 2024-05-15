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
    return array(
      array(10, 'Int', 10),
      array('145E+3', 'Int', NULL),
      array('10', 'Integer', 10),
      array(-10, 'Int', -10),
      array('-10', 'Integer', -10),
      array('-10foo', 'Int', NULL),
      array(10, 'Positive', 10),
      array('145.0E+3', 'Positive', NULL),
      array('10', 'Positive', 10),
      array(-10, 'Positive', NULL),
      array('-10', 'Positive', NULL),
      array('-10foo', 'Positive', NULL),
      array('a string', 'String', 'a string'),
      array('{"contact":{"contact_id":205}}', 'Json', '{"contact":{"contact_id":205}}'),
      array('{"contact":{"contact_id":!n†rude®}}', 'Json', NULL),
    );
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
    return array(
      array(10, 'Int', 10),
      array('145E+3', 'Int', NULL),
      array('10', 'Integer', 10),
      array(-10, 'Int', -10),
      array(array(), 'Integer', NULL),
      array('-10foo', 'Int', NULL),
      array(10, 'Positive', 10),
      array('145.0E+3', 'Positive', NULL),
      array('10', 'Positive', 10),
      array(-10, 'Positive', NULL),
      array('-10', 'Positive', NULL),
      array('-10foo', 'Positive', NULL),
      array('', 'Timestamp', ''),
      array('', 'ContactReference', ''),
      array('3', 'ContactReference', 3),
      array('-3', 'ContactReference', NULL),
      // Escape function is meant for sql, not xss
      array('<p onclick="alert(\'xss\');">Hello</p>', 'Memo', '<p onclick=\\"alert(\\\'xss\\\');\\">Hello</p>'),

      # directory name
      array("abc.def.com/../", 'DirectoryName', 'abc.def.com'),
      array("abc.中テ험def.com/../", 'DirectoryName', 'abc.def.com'),

      # filename
      // Strings containing file system reserved characters
      array("file|name.txt", 'FileName', 'filename.txt'),
      array("file/name.txt", 'FileName', 'filename.txt'),
      array("file<name>.txt", 'FileName', 'filename.txt'),
      array("file:name.txt", 'FileName', 'filename.txt'),
      array("file\"name.txt", 'FileName', 'filename.txt'),
      array("file*name.txt", 'FileName', 'filename.txt'),
      array("file?name.txt", 'FileName', 'filename.txt'),
      array("file/../name.txt", 'FileName', 'filename.txt'),

      // Strings containing control characters
      array("file\x00name.txt", 'FileName', 'filename.txt'),
      array("file\x1Fname.txt", 'FileName', 'filename.txt'),

      // Strings containing URI reserved characters
      array("file#name.txt", 'FileName', 'filename.txt'),
      array("file[name].txt", 'FileName', 'filename.txt'),
      array("file@name.txt", 'FileName', 'filename.txt'),
      array("file!name.txt", 'FileName', 'filename.txt'),
      array('file$name.txt', 'FileName', 'filename.txt'),
      array("file&name.txt", 'FileName', 'filename.txt'),
      array("file'name.txt", 'FileName', 'filename.txt'),
      array("file(name).txt", 'FileName', 'filename.txt'),
      array("file+name.txt", 'FileName', 'filename.txt'),
      array("file,name.txt", 'FileName', 'filename.txt'),
      array("file;name.txt", 'FileName', 'filename.txt'),
      array("file=name.txt", 'FileName', 'filename.txt'),

      // Strings containing URL unsafe characters
      array("file{name}.txt", 'FileName', 'filename.txt'),
      array("file^name.txt", 'FileName', 'filename.txt'),
      array("file~name.txt", 'FileName', 'filename.txt'),
      array("file`name.txt", 'FileName', 'filename.txt'),

      // Strings starting with a dot, hyphen, or a combination of both
      array(".filename.txt", 'FileName', 'filename.txt'),
      array("..filename.txt", 'FileName', 'filename.txt'),
      array("-filename.txt", 'FileName', 'filename.txt'),
      array(".-filename.txt", 'FileName', 'filename.txt'),

      // multi language
      array("中文測試.txt", 'FileName', '中文測試.txt'),
      array("日本語テスト.txt", 'FileName', '日本語テスト.txt'),
      array("韓한국어 시험.txt", 'FileName', '韓한국어 시험.txt'),
    );
  }

}
