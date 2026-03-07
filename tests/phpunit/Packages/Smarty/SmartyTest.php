<?php

/**
 * Tests for Smarty.class.php
 *
 * Run with:
 *   docker exec 10.neticrm phpunit modules/civicrm/tests/phpunit/Packages/Smarty/SmartyTest.php
 */

require_once dirname(__FILE__) . '/../../../../packages/Smarty/Smarty.class.php';
require_once dirname(__FILE__) . '/../../../../packages/Smarty/Config_File.class.php';

use PHPUnit\Framework\TestCase;

class SmartyTest extends TestCase {

  /** @var Smarty */
  protected $smarty;

  protected function setUp(): void {
    $this->smarty = new Smarty();
  }

  // ---------------------------------------------------------------------------
  // assign()
  // ---------------------------------------------------------------------------

  public function testAssignString() {
    $this->smarty->assign('foo', 'bar');
    $this->assertSame('bar', $this->smarty->get_template_vars('foo'));
  }

  public function testAssignInteger() {
    $this->smarty->assign('count', 42);
    $this->assertSame(42, $this->smarty->get_template_vars('count'));
  }

  public function testAssignArray() {
    $this->smarty->assign(['a' => 1, 'b' => 2]);
    $this->assertSame(1, $this->smarty->get_template_vars('a'));
    $this->assertSame(2, $this->smarty->get_template_vars('b'));
  }

  public function testAssignOverwrite() {
    $this->smarty->assign('x', 'first');
    $this->smarty->assign('x', 'second');
    $this->assertSame('second', $this->smarty->get_template_vars('x'));
  }

  public function testAssignEmptyKeyIgnored() {
    $this->smarty->assign('', 'ignored');
    $vars = $this->smarty->get_template_vars();
    // SCRIPT_NAME is assigned in constructor; empty key must not be stored
    $this->assertArrayNotHasKey('', $vars);
  }

  public function testAssignArrayEmptyKeyIgnored() {
    $this->smarty->assign(['' => 'ignored', 'valid' => 'ok']);
    $vars = $this->smarty->get_template_vars();
    $this->assertArrayNotHasKey('', $vars);
    $this->assertSame('ok', $vars['valid']);
  }

  // ---------------------------------------------------------------------------
  // assign_by_ref()
  // ---------------------------------------------------------------------------

  public function testAssignByRef() {
    $value = 'original';
    $this->smarty->assign_by_ref('ref_var', $value);
    $this->assertSame('original', $this->smarty->get_template_vars('ref_var'));
    // Mutating the original should be reflected via reference
    $value = 'changed';
    $this->assertSame('changed', $this->smarty->get_template_vars('ref_var'));
  }

  public function testAssignByRefEmptyKeyIgnored() {
    $value = 'test';
    $this->smarty->assign_by_ref('', $value);
    $this->assertNull($this->smarty->get_template_vars(''));
  }

  // ---------------------------------------------------------------------------
  // append()
  // ---------------------------------------------------------------------------

  public function testAppendCreatesArray() {
    $this->smarty->append('list', 'first');
    $this->smarty->append('list', 'second');
    $result = $this->smarty->get_template_vars('list');
    $this->assertIsArray($result);
    $this->assertContains('first', $result);
    $this->assertContains('second', $result);
  }

  public function testAppendWithMerge() {
    $this->smarty->append('merged', ['a' => 1], true);
    $this->smarty->append('merged', ['b' => 2], true);
    $result = $this->smarty->get_template_vars('merged');
    $this->assertArrayHasKey('a', $result);
    $this->assertArrayHasKey('b', $result);
  }

  public function testAppendWithoutMergeKeepsNumericKeys() {
    $this->smarty->append('list2', ['x', 'y'], false);
    $this->smarty->append('list2', ['z'], false);
    $result = $this->smarty->get_template_vars('list2');
    $this->assertCount(2, $result);
  }

  public function testAppendArrayInput() {
    $this->smarty->append(['key1' => 'v1', 'key2' => 'v2']);
    $this->assertContains('v1', $this->smarty->get_template_vars('key1'));
    $this->assertContains('v2', $this->smarty->get_template_vars('key2'));
  }

  public function testAppendIgnoresNullValue() {
    $before = $this->smarty->get_template_vars('nokey');
    $this->smarty->append('nokey', NULL);
    $after = $this->smarty->get_template_vars('nokey');
    $this->assertSame($before, $after);
  }

  // ---------------------------------------------------------------------------
  // append_by_ref()
  // ---------------------------------------------------------------------------

  public function testAppendByRef() {
    $val = 'ref_item';
    $this->smarty->append_by_ref('reflist', $val);
    $result = $this->smarty->get_template_vars('reflist');
    $this->assertContains('ref_item', $result);
  }

  public function testAppendByRefWithMerge() {
    $val = ['k' => 'v'];
    $this->smarty->append_by_ref('refmap', $val, true);
    $result = $this->smarty->get_template_vars('refmap');
    $this->assertArrayHasKey('k', $result);
  }

  // ---------------------------------------------------------------------------
  // clear_assign()
  // ---------------------------------------------------------------------------

  public function testClearAssignSingle() {
    $this->smarty->assign('to_clear', 'yes');
    $this->smarty->clear_assign('to_clear');
    $this->assertNull($this->smarty->get_template_vars('to_clear'));
  }

  public function testClearAssignArray() {
    $this->smarty->assign('var_a', 1);
    $this->smarty->assign('var_b', 2);
    $this->smarty->assign('var_c', 3);
    $this->smarty->clear_assign(['var_a', 'var_b']);
    $this->assertNull($this->smarty->get_template_vars('var_a'));
    $this->assertNull($this->smarty->get_template_vars('var_b'));
    $this->assertSame(3, $this->smarty->get_template_vars('var_c'));
  }

  // ---------------------------------------------------------------------------
  // clear_all_assign()
  // ---------------------------------------------------------------------------

  public function testClearAllAssign() {
    $this->smarty->assign('p', 1);
    $this->smarty->assign('q', 2);
    $this->smarty->clear_all_assign();
    $this->assertSame([], $this->smarty->get_template_vars());
  }

  // ---------------------------------------------------------------------------
  // get_template_vars()
  // ---------------------------------------------------------------------------

  public function testGetTemplateVarsAll() {
    $this->smarty->clear_all_assign();
    $this->smarty->assign('m', 10);
    $this->smarty->assign('n', 20);
    $vars = $this->smarty->get_template_vars();
    $this->assertArrayHasKey('m', $vars);
    $this->assertArrayHasKey('n', $vars);
  }

  public function testGetTemplateVarsNonExistentReturnsNull() {
    $result = $this->smarty->get_template_vars('does_not_exist');
    $this->assertNull($result);
  }

  // ---------------------------------------------------------------------------
  // get_config_vars()
  // ---------------------------------------------------------------------------

  public function testGetConfigVarsReturnsArrayByDefault() {
    $result = $this->smarty->get_config_vars();
    $this->assertIsArray($result);
  }

  public function testGetConfigVarsNonExistentReturnsNull() {
    $result = $this->smarty->get_config_vars('nonexistent_cfg');
    $this->assertNull($result);
  }

  // ---------------------------------------------------------------------------
  // clear_config()
  // ---------------------------------------------------------------------------

  public function testClearConfigSpecificVar() {
    $this->smarty->_config[0]['vars']['mykey'] = 'myval';
    $this->smarty->clear_config('mykey');
    $this->assertArrayNotHasKey('mykey', $this->smarty->_config[0]['vars']);
  }

  public function testClearConfigAll() {
    $this->smarty->_config[0]['vars']['a'] = 1;
    $this->smarty->_config[0]['vars']['b'] = 2;
    $this->smarty->clear_config();
    $this->assertSame([], $this->smarty->_config[0]['vars']);
  }

  // ---------------------------------------------------------------------------
  // register_function() / unregister_function()
  // ---------------------------------------------------------------------------

  public function testRegisterFunction() {
    $this->smarty->register_function('myfunc', 'strtoupper');
    $this->assertArrayHasKey('myfunc', $this->smarty->_plugins['function']);
    $plugin = $this->smarty->_plugins['function']['myfunc'];
    $this->assertSame('strtoupper', $plugin[0]);
  }

  public function testUnregisterFunction() {
    $this->smarty->register_function('myfunc2', 'strtolower');
    $this->smarty->unregister_function('myfunc2');
    $this->assertArrayNotHasKey('myfunc2', $this->smarty->_plugins['function']);
  }

  // ---------------------------------------------------------------------------
  // register_block() / unregister_block()
  // ---------------------------------------------------------------------------

  public function testRegisterBlock() {
    $this->smarty->register_block('myblock', 'trim');
    $this->assertArrayHasKey('myblock', $this->smarty->_plugins['block']);
    $this->assertSame('trim', $this->smarty->_plugins['block']['myblock'][0]);
  }

  public function testUnregisterBlock() {
    $this->smarty->register_block('myblock2', 'trim');
    $this->smarty->unregister_block('myblock2');
    $this->assertArrayNotHasKey('myblock2', $this->smarty->_plugins['block']);
  }

  // ---------------------------------------------------------------------------
  // register_modifier() / unregister_modifier()
  // ---------------------------------------------------------------------------

  public function testRegisterModifier() {
    $this->smarty->register_modifier('mymod', 'strtoupper');
    $this->assertArrayHasKey('mymod', $this->smarty->_plugins['modifier']);
    $this->assertSame('strtoupper', $this->smarty->_plugins['modifier']['mymod'][0]);
  }

  public function testUnregisterModifier() {
    $this->smarty->register_modifier('mymod2', 'strtolower');
    $this->smarty->unregister_modifier('mymod2');
    $this->assertArrayNotHasKey('mymod2', $this->smarty->_plugins['modifier']);
  }

  // ---------------------------------------------------------------------------
  // register_resource() / unregister_resource()
  // ---------------------------------------------------------------------------

  public function testRegisterResourceFourFunctions() {
    $funcs = ['res_source', 'res_timestamp', 'res_secure', 'res_trusted'];
    $this->smarty->register_resource('myresource', $funcs);
    $this->assertArrayHasKey('myresource', $this->smarty->_plugins['resource']);
  }

  public function testUnregisterResource() {
    $funcs = ['rs', 'rt', 'rsec', 'rtrus'];
    $this->smarty->register_resource('myresource2', $funcs);
    $this->smarty->unregister_resource('myresource2');
    $this->assertArrayNotHasKey('myresource2', $this->smarty->_plugins['resource']);
  }

  // ---------------------------------------------------------------------------
  // register_prefilter() / unregister_prefilter()
  // ---------------------------------------------------------------------------

  public function testRegisterPrefilter() {
    $this->smarty->register_prefilter('strtolower');
    $this->assertArrayHasKey('strtolower', $this->smarty->_plugins['prefilter']);
  }

  public function testUnregisterPrefilter() {
    $this->smarty->register_prefilter('strtolower');
    $this->smarty->unregister_prefilter('strtolower');
    $this->assertArrayNotHasKey('strtolower', $this->smarty->_plugins['prefilter']);
  }

  public function testRegisterPrefilterArray() {
    $cb = ['SmartyTest', 'dummyFilter'];
    $this->smarty->register_prefilter($cb);
    $key = 'SmartyTest_dummyFilter';
    $this->assertArrayHasKey($key, $this->smarty->_plugins['prefilter']);
  }

  // ---------------------------------------------------------------------------
  // register_postfilter() / unregister_postfilter()
  // ---------------------------------------------------------------------------

  public function testRegisterPostfilter() {
    $this->smarty->register_postfilter('trim');
    $this->assertArrayHasKey('trim', $this->smarty->_plugins['postfilter']);
  }

  public function testUnregisterPostfilter() {
    $this->smarty->register_postfilter('trim');
    $this->smarty->unregister_postfilter('trim');
    $this->assertArrayNotHasKey('trim', $this->smarty->_plugins['postfilter']);
  }

  // ---------------------------------------------------------------------------
  // register_outputfilter() / unregister_outputfilter()
  // ---------------------------------------------------------------------------

  public function testRegisterOutputfilter() {
    $this->smarty->register_outputfilter('htmlspecialchars');
    $this->assertArrayHasKey('htmlspecialchars', $this->smarty->_plugins['outputfilter']);
  }

  public function testUnregisterOutputfilter() {
    $this->smarty->register_outputfilter('htmlspecialchars');
    $this->smarty->unregister_outputfilter('htmlspecialchars');
    $this->assertArrayNotHasKey('htmlspecialchars', $this->smarty->_plugins['outputfilter']);
  }

  // ---------------------------------------------------------------------------
  // register_object() / unregister_object()
  // ---------------------------------------------------------------------------

  public function testRegisterObject() {
    $obj = new stdClass();
    $this->smarty->register_object('myobj', $obj);
    $this->assertArrayHasKey('myobj', $this->smarty->_reg_objects);
    $this->assertSame($obj, $this->smarty->_reg_objects['myobj'][0]);
  }

  public function testUnregisterObject() {
    $obj = new stdClass();
    $this->smarty->register_object('myobj2', $obj);
    $this->smarty->unregister_object('myobj2');
    $this->assertArrayNotHasKey('myobj2', $this->smarty->_reg_objects);
  }

  // ---------------------------------------------------------------------------
  // _dequote()
  // ---------------------------------------------------------------------------

  public function testDequoteDoubleQuoted() {
    $result = $this->smarty->_dequote('"hello"');
    $this->assertSame('hello', $result);
  }

  public function testDequoteSingleQuoted() {
    $result = $this->smarty->_dequote("'world'");
    $this->assertSame('world', $result);
  }

  public function testDequoteUnquotedString() {
    $result = $this->smarty->_dequote('plain');
    $this->assertSame('plain', $result);
  }

  public function testDequoteMismatchedQuotes() {
    $result = $this->smarty->_dequote('"mismatch\'');
    $this->assertSame('"mismatch\'', $result);
  }

  public function testDequoteEmptyQuotedString() {
    $result = $this->smarty->_dequote('""');
    $this->assertSame('', $result);
  }

  // ---------------------------------------------------------------------------
  // _get_auto_id()
  // ---------------------------------------------------------------------------

  public function testGetAutoIdBoth() {
    $result = $this->smarty->_get_auto_id('cache123', 'compile456');
    $this->assertSame('cache123|compile456', $result);
  }

  public function testGetAutoIdCacheOnly() {
    $result = $this->smarty->_get_auto_id('cache123', null);
    $this->assertSame('cache123', $result);
  }

  public function testGetAutoIdCompileOnly() {
    $result = $this->smarty->_get_auto_id(null, 'compile456');
    $this->assertSame('compile456', $result);
  }

  public function testGetAutoIdNeither() {
    $result = $this->smarty->_get_auto_id(null, null);
    $this->assertNull($result);
  }

  // ---------------------------------------------------------------------------
  // _get_auto_filename()
  // ---------------------------------------------------------------------------

  public function testGetAutoFilenameBase() {
    $result = $this->smarty->_get_auto_filename('/tmp/compile');
    $this->assertSame('/tmp/compile' . DIRECTORY_SEPARATOR, $result);
  }

  public function testGetAutoFilenameWithAutoId() {
    $result = $this->smarty->_get_auto_filename('/tmp/compile', null, 'en');
    $this->assertStringContainsString('en', $result);
    $this->assertStringStartsWith('/tmp/compile', $result);
  }

  public function testGetAutoFilenameWithSource() {
    $result = $this->smarty->_get_auto_filename('/tmp/compile', 'index.tpl', null);
    $this->assertStringContainsString('index.tpl', $result);
    $this->assertStringContainsString('%%', $result);
  }

  public function testGetAutoFilenameWithBothIds() {
    $result = $this->smarty->_get_auto_filename('/base', 'template.tpl', 'myid');
    $this->assertStringStartsWith('/base/', $result);
    $this->assertStringContainsString('myid', $result);
    $this->assertStringContainsString('template.tpl', $result);
  }

  // ---------------------------------------------------------------------------
  // _get_filter_name()
  // ---------------------------------------------------------------------------

  public function testGetFilterNameString() {
    $result = $this->smarty->_get_filter_name('my_filter');
    $this->assertSame('my_filter', $result);
  }

  public function testGetFilterNameArrayWithObject() {
    $obj = new stdClass();
    $result = $this->smarty->_get_filter_name([$obj, 'myMethod']);
    $this->assertSame('stdClass_myMethod', $result);
  }

  public function testGetFilterNameArrayWithClassName() {
    $result = $this->smarty->_get_filter_name(['MyClass', 'myMethod']);
    $this->assertSame('MyClass_myMethod', $result);
  }

  // ---------------------------------------------------------------------------
  // _read_file()
  // ---------------------------------------------------------------------------

  public function testReadFileReturnsContent() {
    $tmpFile = tempnam(sys_get_temp_dir(), 'smarty_test_');
    file_put_contents($tmpFile, 'test content');
    $result = $this->smarty->_read_file($tmpFile);
    $this->assertSame('test content', $result);
    unlink($tmpFile);
  }

  public function testReadFileNonExistentReturnsFalse() {
    $result = $this->smarty->_read_file('/nonexistent/path/file.tpl');
    $this->assertFalse($result);
  }

  public function testReadFileEmpty() {
    $tmpFile = tempnam(sys_get_temp_dir(), 'smarty_test_');
    file_put_contents($tmpFile, '');
    $result = $this->smarty->_read_file($tmpFile);
    // empty file returns empty string or false depending on implementation
    $this->assertTrue($result === '' || $result === false);
    unlink($tmpFile);
  }

  // ---------------------------------------------------------------------------
  // Helper (used as callable in prefilter tests)
  // ---------------------------------------------------------------------------

  public static function dummyFilter($source, &$smarty) {
    return $source;
  }
}
