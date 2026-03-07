<?php

/**
 * Tests for Smarty_Compiler.class.php
 *
 * Run with:
 *   docker exec 10.neticrm phpunit modules/civicrm/tests/phpunit/Packages/Smarty/SmartyCompilerTest.php
 *
 * Note: _compile_file() contains hardcoded debug writes to /var/www/html/log/.
 * The full compile pipeline tests require that path to be writable.
 * Pure parsing method tests have no such dependency.
 */

require_once dirname(__FILE__) . '/../../../../packages/Smarty/Smarty.class.php';
require_once dirname(__FILE__) . '/../../../../packages/Smarty/Config_File.class.php';
require_once dirname(__FILE__) . '/../../../../packages/Smarty/Smarty_Compiler.class.php';

use PHPUnit\Framework\TestCase;

class SmartyCompilerTest extends TestCase {

  /** @var Smarty_Compiler */
  protected $compiler;

  protected function setUp(): void {
    $this->compiler = new Smarty_Compiler();
    // Set up a writable temp compile directory so file operations don't fail
    $this->compiler->compile_dir = sys_get_temp_dir();
    $this->compiler->template_dir = sys_get_temp_dir();
    $this->compiler->plugins_dir = [
      dirname(__FILE__) . '/../../../../packages/Smarty/plugins',
    ];
  }

  // ---------------------------------------------------------------------------
  // _dequote() — inherited from Smarty
  // ---------------------------------------------------------------------------

  public function testDequoteDoubleQuoted() {
    $this->assertSame('hello', $this->compiler->_dequote('"hello"'));
  }

  public function testDequoteSingleQuoted() {
    $this->assertSame('world', $this->compiler->_dequote("'world'"));
  }

  public function testDequoteNoQuotes() {
    $this->assertSame('plain', $this->compiler->_dequote('plain'));
  }

  // ---------------------------------------------------------------------------
  // _parse_attrs() — parses "key=value" attribute strings
  // ---------------------------------------------------------------------------

  public function testParseAttrsSimpleString() {
    $attrs = $this->compiler->_parse_attrs("file='index.tpl'");
    $this->assertArrayHasKey('file', $attrs);
    $this->assertSame("'index.tpl'", $attrs['file']);
  }

  public function testParseAttrsDoubleQuotedString() {
    $attrs = $this->compiler->_parse_attrs('name="myblock"');
    $this->assertSame("'myblock'", $attrs['name']);
  }

  public function testParseAttrsMultipleAttrs() {
    $attrs = $this->compiler->_parse_attrs("file='tpl.tpl' once=true");
    $this->assertArrayHasKey('file', $attrs);
    $this->assertArrayHasKey('once', $attrs);
    $this->assertSame('true', $attrs['once']);
  }

  public function testParseAttrsBoolTrue() {
    foreach (['true', 'on', 'yes'] as $val) {
      $attrs = $this->compiler->_parse_attrs("show=$val");
      $this->assertSame('true', $attrs['show'], "Expected 'true' for input '$val'");
    }
  }

  public function testParseAttrsBoolFalse() {
    foreach (['false', 'off', 'no'] as $val) {
      $attrs = $this->compiler->_parse_attrs("show=$val");
      $this->assertSame('false', $attrs['show'], "Expected 'false' for input '$val'");
    }
  }

  public function testParseAttrsNumericValue() {
    $attrs = $this->compiler->_parse_attrs('loop=5');
    $this->assertSame('5', $attrs['loop']);
  }

  public function testParseAttrsNullValue() {
    $attrs = $this->compiler->_parse_attrs('var=null');
    $this->assertSame('null', $attrs['var']);
  }

  public function testParseAttrsVariable() {
    $attrs = $this->compiler->_parse_attrs('from=$myArray');
    $this->assertArrayHasKey('from', $attrs);
    $this->assertStringContainsString('_tpl_vars', $attrs['from']);
  }

  // ---------------------------------------------------------------------------
  // _parse_var_props() — compiles a single token to PHP expression
  // ---------------------------------------------------------------------------

  public function testParseVarPropsSimpleVar() {
    $result = $this->compiler->_parse_var_props('$foo');
    $this->assertSame("\$this->_tpl_vars['foo']", $result);
  }

  public function testParseVarPropsNestedVar() {
    $result = $this->compiler->_parse_var_props('$foo.bar');
    $this->assertStringContainsString("_tpl_vars['foo']", $result);
    $this->assertStringContainsString("['bar']", $result);
  }

  public function testParseVarPropsDoubleQuotedString() {
    $result = $this->compiler->_parse_var_props('"hello"');
    $this->assertSame("'hello'", $result);
  }

  public function testParseVarPropsSingleQuotedString() {
    $result = $this->compiler->_parse_var_props("'hello'");
    $this->assertSame("'hello'", $result);
  }

  public function testParseVarPropsInteger() {
    $result = $this->compiler->_parse_var_props('42');
    $this->assertSame('42', $result);
  }

  public function testParseVarPropsNegativeNumber() {
    $result = $this->compiler->_parse_var_props('-5');
    $this->assertSame('-5', $result);
  }

  public function testParseVarPropsFloat() {
    $result = $this->compiler->_parse_var_props('3.14');
    $this->assertSame('3.14', $result);
  }

  public function testParseVarPropsPermittedTokenTrue() {
    $result = $this->compiler->_parse_var_props('true');
    $this->assertSame('true', $result);
  }

  public function testParseVarPropsPermittedTokenFalse() {
    $result = $this->compiler->_parse_var_props('false');
    $this->assertSame('false', $result);
  }

  public function testParseVarPropsPermittedTokenNull() {
    $result = $this->compiler->_parse_var_props('null');
    $this->assertSame('null', $result);
  }

  public function testParseVarPropsLiteralStringBecomesQuoted() {
    // A bare unquoted non-keyword word is treated as a literal string
    $result = $this->compiler->_parse_var_props('someword');
    $this->assertSame("'someword'", $result);
  }

  // ---------------------------------------------------------------------------
  // _expand_quoted_text()
  // ---------------------------------------------------------------------------

  public function testExpandQuotedTextPlainString() {
    $result = $this->compiler->_expand_quoted_text('"hello world"');
    $this->assertSame("'hello world'", $result);
  }

  public function testExpandQuotedTextWithVariable() {
    $result = $this->compiler->_expand_quoted_text('"Hello $name"');
    $this->assertStringContainsString('_tpl_vars', $result);
    $this->assertStringContainsString('name', $result);
  }

  public function testExpandQuotedTextWithMultipleVars() {
    $result = $this->compiler->_expand_quoted_text('"$first $last"');
    $this->assertStringContainsString("'first'", $result);
    $this->assertStringContainsString("'last'", $result);
  }

  public function testExpandQuotedTextNoVarsReturnsSimpleString() {
    $result = $this->compiler->_expand_quoted_text('"just text"');
    $this->assertSame("'just text'", $result);
  }

  // ---------------------------------------------------------------------------
  // _parse_var() — compiles $var expressions to PHP
  // ---------------------------------------------------------------------------

  public function testParseVarSimple() {
    $result = $this->compiler->_parse_var('$foo');
    $this->assertSame("\$this->_tpl_vars['foo']", $result);
  }

  public function testParseVarArrayAccess() {
    $result = $this->compiler->_parse_var('$foo[0]');
    $this->assertStringContainsString("_tpl_vars['foo']", $result);
    $this->assertStringContainsString('[0]', $result);
  }

  public function testParseVarDotAccess() {
    $result = $this->compiler->_parse_var('$foo.bar');
    $this->assertStringContainsString("_tpl_vars['foo']", $result);
    $this->assertStringContainsString("['bar']", $result);
  }

  public function testParseVarSmartySuperGlobal() {
    $result = $this->compiler->_parse_var('$smarty.now');
    $this->assertStringContainsString('time()', $result);
  }

  public function testParseVarDynamicArrayIndex() {
    $result = $this->compiler->_parse_var('$foo[$bar]');
    $this->assertStringContainsString("_tpl_vars['foo']", $result);
    $this->assertStringContainsString("_tpl_vars['bar']", $result);
  }

  // ---------------------------------------------------------------------------
  // _parse_is_expr() — handles "is even/odd/div by" expressions
  // ---------------------------------------------------------------------------

  public function testParseIsExprEven() {
    $tokens = ['even'];
    $result = $this->compiler->_parse_is_expr('$foo', $tokens);
    $this->assertStringContainsString('!(1 &', $result[0]);
    $this->assertStringContainsString('$foo', $result[0]);
  }

  public function testParseIsExprOdd() {
    $tokens = ['odd'];
    $result = $this->compiler->_parse_is_expr('$foo', $tokens);
    $this->assertStringContainsString('(1 &', $result[0]);
    $this->assertStringContainsString('$foo', $result[0]);
  }

  public function testParseIsExprDiv() {
    $tokens = ['div', 'by', '3'];
    $result = $this->compiler->_parse_is_expr('$n', $tokens);
    $this->assertStringContainsString('%', $result[0]);
    $this->assertStringContainsString('$n', $result[0]);
  }

  public function testParseIsExprNotEven() {
    $tokens = ['not', 'even'];
    $result = $this->compiler->_parse_is_expr('$x', $tokens);
    // "not even" negates the expression
    $this->assertStringContainsString('!', $result[0]);
  }

  public function testParseIsExprEvenBy() {
    $tokens = ['even', 'by', '2'];
    $result = $this->compiler->_parse_is_expr('$x', $tokens);
    $this->assertStringContainsString('/', $result[0]);
    $this->assertStringContainsString('2', $result[0]);
  }

  public function testParseIsExprRemainingTokensReturned() {
    $tokens = ['even', 'extra_token'];
    $result = $this->compiler->_parse_is_expr('$x', $tokens);
    // The first element is the expression, the rest are remaining tokens
    $this->assertCount(2, $result);
    $this->assertSame('extra_token', $result[1]);
  }

  // ---------------------------------------------------------------------------
  // _parse_conf_var() — config variable syntax #varname#
  // ---------------------------------------------------------------------------

  public function testParseConfVar() {
    $result = $this->compiler->_parse_conf_var('#myvar#');
    $this->assertSame("\$this->_config[0]['vars']['myvar']", $result);
  }

  public function testParseConfVarWithModifier() {
    $result = $this->compiler->_parse_conf_var('#myvar#|upper');
    // Modifier wraps the output
    $this->assertStringContainsString("_config[0]['vars']['myvar']", $result);
  }

  // ---------------------------------------------------------------------------
  // _parse_section_prop() — section variable syntax %section.property%
  // ---------------------------------------------------------------------------

  public function testParseSectionProp() {
    $result = $this->compiler->_parse_section_prop('%mysection.index%');
    $this->assertSame("\$this->_sections['mysection']['index']", $result);
  }

  public function testParseSectionPropCustomProperty() {
    $result = $this->compiler->_parse_section_prop('%loop.iteration%');
    $this->assertSame("\$this->_sections['loop']['iteration']", $result);
  }

  // ---------------------------------------------------------------------------
  // _parse_modifiers() — modifier pipeline output transformation
  // ---------------------------------------------------------------------------

  public function testParseModifiersWithPhpFunction() {
    // Pre-register a PHP built-in as a modifier
    $this->compiler->_plugins['modifier']['upper'] = ['strtoupper', null, null, false];
    $output = "\$this->_tpl_vars['name']";
    $this->compiler->_parse_modifiers($output, 'upper');
    $this->assertStringContainsString('strtoupper', $output);
  }

  public function testParseModifiersChained() {
    $this->compiler->_plugins['modifier']['trim_mod'] = ['trim', null, null, false];
    $this->compiler->_plugins['modifier']['upper'] = ['strtoupper', null, null, false];
    $output = "\$this->_tpl_vars['val']";
    $this->compiler->_parse_modifiers($output, 'trim_mod|upper');
    $this->assertStringContainsString('strtoupper', $output);
    $this->assertStringContainsString('trim', $output);
  }

  public function testParseModifiersWithArgument() {
    $this->compiler->_plugins['modifier']['truncate'] = ['smarty_modifier_truncate', null, null, false];
    // Register manually without needing the plugin file
    $this->compiler->_plugins['modifier']['substr'] = ['substr', null, null, false];
    $output = "\$this->_tpl_vars['text']";
    $this->compiler->_parse_modifiers($output, 'substr:0:5');
    $this->assertStringContainsString('substr', $output);
    $this->assertStringContainsString('0', $output);
    $this->assertStringContainsString('5', $output);
  }

  // ---------------------------------------------------------------------------
  // _parse_vars_props() — batch version of _parse_var_props
  // ---------------------------------------------------------------------------

  public function testParseVarsProps() {
    $tokens = ['$foo', '$bar', '"literal"'];
    $this->compiler->_parse_vars_props($tokens);
    $this->assertStringContainsString("_tpl_vars['foo']", $tokens[0]);
    $this->assertStringContainsString("_tpl_vars['bar']", $tokens[1]);
    $this->assertSame("'literal'", $tokens[2]);
  }

  // ---------------------------------------------------------------------------
  // _get_filter_name() — inherited from Smarty
  // ---------------------------------------------------------------------------

  public function testGetFilterNameString() {
    $this->assertSame('my_filter', $this->compiler->_get_filter_name('my_filter'));
  }

  public function testGetFilterNameArray() {
    $this->assertSame('MyClass_myMethod', $this->compiler->_get_filter_name(['MyClass', 'myMethod']));
  }

  // ---------------------------------------------------------------------------
  // _get_auto_id() — inherited from Smarty
  // ---------------------------------------------------------------------------

  public function testGetAutoIdBoth() {
    $this->assertSame('c|d', $this->compiler->_get_auto_id('c', 'd'));
  }

  public function testGetAutoIdNone() {
    $this->assertNull($this->compiler->_get_auto_id(null, null));
  }

  // ---------------------------------------------------------------------------
  // Full compile pipeline via _compile_file() — requires /var/www/html/log/
  // ---------------------------------------------------------------------------

  public function testCompileFileSimpleText() {
    $compiled = '';
    $result = $this->compiler->_compile_file(
      'test_resource',
      'Hello World',
      $compiled
    );
    $this->assertTrue($result);
    $this->assertStringContainsString('Hello World', $compiled);
  }

  public function testCompileFileWithVariable() {
    $compiled = '';
    $result = $this->compiler->_compile_file(
      'test_resource',
      'Hello {$name}',
      $compiled
    );
    $this->assertTrue($result);
    $this->assertStringContainsString("_tpl_vars['name']", $compiled);
  }

  public function testCompileFileWithIfTag() {
    $compiled = '';
    $source = '{if $show}visible{/if}';
    $result = $this->compiler->_compile_file('test_resource', $source, $compiled);
    $this->assertTrue($result);
    $this->assertStringContainsString('if', $compiled);
    $this->assertStringContainsString('visible', $compiled);
  }

  public function testCompileFileWithForeachTag() {
    $compiled = '';
    $source = '{foreach from=$items item=item}{$item}{/foreach}';
    $result = $this->compiler->_compile_file('test_resource', $source, $compiled);
    $this->assertTrue($result);
    $this->assertStringContainsString('foreach', $compiled);
  }

  public function testCompileFileWithLiteralBlock() {
    $compiled = '';
    $source = '{literal}{$not_a_var}{/literal}';
    $result = $this->compiler->_compile_file('test_resource', $source, $compiled);
    $this->assertTrue($result);
    $this->assertStringContainsString('{$not_a_var}', $compiled);
  }

  public function testCompileFileWithComment() {
    $compiled = '';
    $source = '{* this is a comment *}visible';
    $result = $this->compiler->_compile_file('test_resource', $source, $compiled);
    $this->assertTrue($result);
    $this->assertStringNotContainsString('this is a comment', $compiled);
    $this->assertStringContainsString('visible', $compiled);
  }

  public function testCompileFileEmptyTemplate() {
    $compiled = '';
    $result = $this->compiler->_compile_file('test_resource', '', $compiled);
    $this->assertTrue($result);
  }

  public function testCompileFileHeaderIncluded() {
    $compiled = '';
    $this->compiler->_compile_file('my_template.tpl', 'content', $compiled);
    $this->assertStringContainsString('Smarty version', $compiled);
    $this->assertStringContainsString('my_template.tpl', $compiled);
  }
}
