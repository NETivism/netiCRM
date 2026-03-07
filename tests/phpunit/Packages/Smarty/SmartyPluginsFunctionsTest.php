<?php

/**
 * Tests for Smarty function/block/outputfilter plugins.
 *
 * A minimal SmartyStub is used instead of a full Smarty instance so these
 * tests have no filesystem or template-compilation dependency.
 *
 * Run with:
 *   docker exec -w /var/www/html 10.neticrm phpunit modules/civicrm/tests/phpunit/Packages/Smarty/SmartyPluginsFunctionsTest.php
 */

use PHPUnit\Framework\TestCase;

/**
 * Minimal Smarty stand-in for plugin unit tests.
 */
class SmartyStub {
  public $request_use_auto_globals = true;
  public $security = false;
  public $_smarty_vars = [];
  private $_vars = [];

  public function _get_plugin_filepath($type, $name) {
    return dirname(__FILE__) . '/../../../../packages/Smarty/plugins/'
      . $type . '.' . $name . '.php';
  }

  public function trigger_error($msg, $type = E_USER_WARNING) {
    trigger_error($msg, $type);
  }

  public function assign($key, $val = null) {
    if (is_array($key)) {
      foreach ($key as $k => $v) {
        $this->_vars[$k] = $v;
      }
    }
    else {
      $this->_vars[$key] = $val;
    }
  }

  public function get_template_vars($key = null) {
    if ($key === null) {
      return $this->_vars;
    }
    return isset($this->_vars[$key]) ? $this->_vars[$key] : null;
  }
}

$_smarty_plugins_dir = dirname(__FILE__) . '/../../../../packages/Smarty/plugins/';

require_once $_smarty_plugins_dir . 'block.textformat.php';
require_once $_smarty_plugins_dir . 'function.counter.php';
require_once $_smarty_plugins_dir . 'function.cycle.php';
require_once $_smarty_plugins_dir . 'function.html_checkboxes.php';
require_once $_smarty_plugins_dir . 'function.html_options.php';
require_once $_smarty_plugins_dir . 'function.html_radios.php';
require_once $_smarty_plugins_dir . 'function.html_table.php';
require_once $_smarty_plugins_dir . 'function.mailto.php';
require_once $_smarty_plugins_dir . 'function.math.php';
require_once $_smarty_plugins_dir . 'outputfilter.trimwhitespace.php';
// html_select_date and html_select_time are loaded lazily via SmartyStub so
// that their internal require_once calls resolve through _get_plugin_filepath.

class SmartyPluginsFunctionsTest extends TestCase {

  /** @var SmartyStub */
  protected $smarty;

  protected function setUp(): void {
    $this->smarty = new SmartyStub();
  }

  // ---------------------------------------------------------------------------
  // block.textformat
  // ---------------------------------------------------------------------------

  public function testTextformatDefaultWrap() {
    $long = str_repeat('word ', 20); // 100 chars
    $result = smarty_block_textformat([], $long, $this->smarty);
    $lines = explode("\n", trim($result));
    foreach ($lines as $line) {
      $this->assertLessThanOrEqual(80, strlen($line));
    }
  }

  public function testTextformatEmailStyle() {
    $long = str_repeat('word ', 20);
    $result = smarty_block_textformat(['style' => 'email'], $long, $this->smarty);
    $lines = explode("\n", trim($result));
    foreach ($lines as $line) {
      $this->assertLessThanOrEqual(72, strlen($line));
    }
  }

  public function testTextformatIndent() {
    $result = smarty_block_textformat(['indent' => 4, 'wrap' => 100], "hello world", $this->smarty);
    $this->assertStringStartsWith('    ', $result);
  }

  public function testTextformatNullContentReturnsNull() {
    $result = smarty_block_textformat([], null, $this->smarty);
    $this->assertNull($result);
  }

  public function testTextformatAssign() {
    smarty_block_textformat(['assign' => 'myvar', 'wrap' => 100], 'hello', $this->smarty);
    $this->assertSame('hello', $this->smarty->get_template_vars('myvar'));
  }

  // ---------------------------------------------------------------------------
  // function.counter
  // ---------------------------------------------------------------------------

  public function testCounterIncrement() {
    // Use unique name to avoid cross-test static state
    $result1 = smarty_function_counter(['name' => 'tc_inc'], $this->smarty);
    $result2 = smarty_function_counter(['name' => 'tc_inc'], $this->smarty);
    $this->assertSame(1, $result1);
    $this->assertSame(2, $result2);
  }

  public function testCounterStart() {
    $result = smarty_function_counter(['name' => 'tc_start', 'start' => 5], $this->smarty);
    $this->assertSame(5, $result);
  }

  public function testCounterSkip() {
    smarty_function_counter(['name' => 'tc_skip', 'start' => 0, 'skip' => 5], $this->smarty);
    $result = smarty_function_counter(['name' => 'tc_skip'], $this->smarty);
    $this->assertSame(5, $result);
  }

  public function testCounterDirectionDown() {
    smarty_function_counter(['name' => 'tc_down', 'start' => 10, 'direction' => 'down'], $this->smarty);
    $result = smarty_function_counter(['name' => 'tc_down', 'direction' => 'down'], $this->smarty);
    $this->assertSame(9, $result);
  }

  public function testCounterAssign() {
    smarty_function_counter(['name' => 'tc_assign', 'start' => 7, 'assign' => 'cnt'], $this->smarty);
    $this->assertSame(7, $this->smarty->get_template_vars('cnt'));
  }

  // ---------------------------------------------------------------------------
  // function.cycle
  // ---------------------------------------------------------------------------

  public function testCycleBasic() {
    $r1 = smarty_function_cycle(['name' => 'cy_basic', 'values' => 'a,b,c'], $this->smarty);
    $r2 = smarty_function_cycle(['name' => 'cy_basic'], $this->smarty);
    $r3 = smarty_function_cycle(['name' => 'cy_basic'], $this->smarty);
    $r4 = smarty_function_cycle(['name' => 'cy_basic'], $this->smarty);
    $this->assertSame('a', $r1);
    $this->assertSame('b', $r2);
    $this->assertSame('c', $r3);
    $this->assertSame('a', $r4); // wraps around
  }

  public function testCycleArrayValues() {
    $r1 = smarty_function_cycle(['name' => 'cy_arr', 'values' => ['x', 'y']], $this->smarty);
    $r2 = smarty_function_cycle(['name' => 'cy_arr'], $this->smarty);
    $this->assertSame('x', $r1);
    $this->assertSame('y', $r2);
  }

  public function testCycleReset() {
    smarty_function_cycle(['name' => 'cy_reset', 'values' => 'a,b,c'], $this->smarty);
    smarty_function_cycle(['name' => 'cy_reset'], $this->smarty);
    $result = smarty_function_cycle(['name' => 'cy_reset', 'reset' => true], $this->smarty);
    $this->assertSame('a', $result);
  }

  public function testCycleAssign() {
    smarty_function_cycle(['name' => 'cy_assign', 'values' => 'p,q', 'assign' => 'cyval'], $this->smarty);
    $this->assertSame('p', $this->smarty->get_template_vars('cyval'));
  }

  // ---------------------------------------------------------------------------
  // function.html_checkboxes
  // ---------------------------------------------------------------------------

  public function testHtmlCheckboxesBasic() {
    $result = smarty_function_html_checkboxes([
      'name'   => 'colors',
      'values' => ['r', 'g', 'b'],
      'output' => ['Red', 'Green', 'Blue'],
    ], $this->smarty);
    $this->assertStringContainsString('type="checkbox"', $result);
    $this->assertStringContainsString('value="r"', $result);
    $this->assertStringContainsString('Red', $result);
  }

  public function testHtmlCheckboxesChecked() {
    $result = smarty_function_html_checkboxes([
      'name'    => 'opts',
      'values'  => ['a', 'b'],
      'output'  => ['A', 'B'],
      'checked' => ['b'],
    ], $this->smarty);
    $this->assertStringContainsString('checked="checked"', $result);
  }

  public function testHtmlCheckboxesOptions() {
    $result = smarty_function_html_checkboxes([
      'name'    => 'opts2',
      'options' => ['yes' => 'Yes', 'no' => 'No'],
    ], $this->smarty);
    $this->assertStringContainsString('value="yes"', $result);
    $this->assertStringContainsString('Yes', $result);
  }

  // ---------------------------------------------------------------------------
  // function.html_options
  // ---------------------------------------------------------------------------

  public function testHtmlOptionsBasic() {
    $result = smarty_function_html_options([
      'values' => ['1', '2'],
      'output' => ['One', 'Two'],
    ], $this->smarty);
    $this->assertStringContainsString('<option', $result);
    $this->assertStringContainsString('value="1"', $result);
    $this->assertStringContainsString('One', $result);
  }

  public function testHtmlOptionsSelected() {
    $result = smarty_function_html_options([
      'values'   => ['1', '2', '3'],
      'output'   => ['One', 'Two', 'Three'],
      'selected' => ['2'],
    ], $this->smarty);
    $this->assertStringContainsString('selected="selected"', $result);
  }

  public function testHtmlOptionsWithName() {
    $result = smarty_function_html_options([
      'name'    => 'myselect',
      'options' => ['a' => 'Apple', 'b' => 'Banana'],
    ], $this->smarty);
    $this->assertStringContainsString('<select name="myselect"', $result);
    $this->assertStringContainsString('Apple', $result);
  }

  // ---------------------------------------------------------------------------
  // function.html_radios
  // ---------------------------------------------------------------------------

  public function testHtmlRadiosBasic() {
    $result = smarty_function_html_radios([
      'name'   => 'choice',
      'values' => ['yes', 'no'],
      'output' => ['Yes', 'No'],
    ], $this->smarty);
    $this->assertStringContainsString('type="radio"', $result);
    $this->assertStringContainsString('value="yes"', $result);
    $this->assertStringContainsString('Yes', $result);
  }

  public function testHtmlRadiosChecked() {
    $result = smarty_function_html_radios([
      'name'    => 'choice2',
      'values'  => ['a', 'b'],
      'output'  => ['A', 'B'],
      'checked' => 'b',
    ], $this->smarty);
    $this->assertStringContainsString('checked="checked"', $result);
  }

  // ---------------------------------------------------------------------------
  // function.html_select_date
  // ---------------------------------------------------------------------------

  public function testHtmlSelectDateContainsSelects() {
    // Lazy-load since it internally require_once's via SmartyStub
    require_once dirname(__FILE__) . '/../../../../packages/Smarty/plugins/function.html_select_date.php';
    $result = smarty_function_html_select_date([
      'time'       => '2023-06-15',
      'start_year' => '2020',
      'end_year'   => '2025',
    ], $this->smarty);
    $this->assertStringContainsString('<select', $result);
    $this->assertStringContainsString('Date_Month', $result);
    $this->assertStringContainsString('Date_Day', $result);
    $this->assertStringContainsString('Date_Year', $result);
  }

  public function testHtmlSelectDateMonthNames() {
    require_once dirname(__FILE__) . '/../../../../packages/Smarty/plugins/function.html_select_date.php';
    $result = smarty_function_html_select_date([
      'time'        => '2023-01-01',
      'start_year'  => '2023',
      'end_year'    => '2023',
      'month_format' => '%B',
    ], $this->smarty);
    $this->assertStringContainsString('January', $result);
  }

  public function testHtmlSelectDateRelativeYear() {
    require_once dirname(__FILE__) . '/../../../../packages/Smarty/plugins/function.html_select_date.php';
    $result = smarty_function_html_select_date([
      'time'       => date('Y') . '-01-01',
      'start_year' => '-1',
      'end_year'   => '+1',
    ], $this->smarty);
    $this->assertStringContainsString('<select', $result);
  }

  // ---------------------------------------------------------------------------
  // function.html_select_time
  // ---------------------------------------------------------------------------

  public function testHtmlSelectTimeContainsSelects() {
    require_once dirname(__FILE__) . '/../../../../packages/Smarty/plugins/function.html_select_time.php';
    $result = smarty_function_html_select_time([], $this->smarty);
    $this->assertStringContainsString('<select', $result);
    $this->assertStringContainsString('Time_Hour', $result);
    $this->assertStringContainsString('Time_Minute', $result);
  }

  public function testHtmlSelectTimeCustomPrefix() {
    require_once dirname(__FILE__) . '/../../../../packages/Smarty/plugins/function.html_select_time.php';
    $result = smarty_function_html_select_time(['prefix' => 'MyTime_'], $this->smarty);
    $this->assertStringContainsString('MyTime_Hour', $result);
  }

  // ---------------------------------------------------------------------------
  // function.html_table
  // ---------------------------------------------------------------------------

  public function testHtmlTableBasic() {
    $result = smarty_function_html_table([
      'loop' => ['a', 'b', 'c', 'd'],
      'cols' => 2,
    ], $this->smarty);
    $this->assertStringContainsString('<table', $result);
    $this->assertStringContainsString('<tr', $result);
    $this->assertStringContainsString('<td', $result);
    $this->assertStringContainsString('a', $result);
  }

  public function testHtmlTableWithColumnHeaders() {
    $result = smarty_function_html_table([
      'loop' => ['one', 'two', 'three', 'four'],
      'cols' => 'First,Second',
    ], $this->smarty);
    $this->assertStringContainsString('<th', $result);
    $this->assertStringContainsString('First', $result);
  }

  // ---------------------------------------------------------------------------
  // function.mailto
  // ---------------------------------------------------------------------------

  public function testMailtoNoEncoding() {
    $result = smarty_function_mailto(['address' => 'test@example.com'], $this->smarty);
    $this->assertStringContainsString('href="mailto:test@example.com"', $result);
    $this->assertStringContainsString('test@example.com', $result);
  }

  public function testMailtoCustomText() {
    $result = smarty_function_mailto([
      'address' => 'test@example.com',
      'text'    => 'Contact Us',
    ], $this->smarty);
    $this->assertStringContainsString('Contact Us', $result);
  }

  public function testMailtoHexEncoding() {
    $result = smarty_function_mailto([
      'address' => 'me@example.com',
      'encode'  => 'hex',
    ], $this->smarty);
    $this->assertStringContainsString('&#109;&#97;&#105;&#108;&#116;&#111;&#58;', $result);
  }

  public function testMailtoJavascriptEncoding() {
    $result = smarty_function_mailto([
      'address' => 'js@example.com',
      'encode'  => 'javascript',
    ], $this->smarty);
    $this->assertStringContainsString('<script', $result);
  }

  public function testMailtoWithSubject() {
    $result = smarty_function_mailto([
      'address' => 'test@example.com',
      'subject' => 'Hello There',
    ], $this->smarty);
    $this->assertStringContainsString('subject=', $result);
    $this->assertStringContainsString('Hello', $result);
  }

  // ---------------------------------------------------------------------------
  // function.math
  // ---------------------------------------------------------------------------

  public function testMathSimple() {
    $result = smarty_function_math(['equation' => '1+1'], $this->smarty);
    $this->assertSame(2, $result);
  }

  public function testMathWithVariables() {
    $result = smarty_function_math(['equation' => 'x+y', 'x' => 3, 'y' => 4], $this->smarty);
    $this->assertSame(7, $result);
  }

  public function testMathMultiply() {
    $result = smarty_function_math(['equation' => 'x*y', 'x' => 6, 'y' => 7], $this->smarty);
    $this->assertSame(42, $result);
  }

  public function testMathFloor() {
    $result = smarty_function_math(['equation' => 'floor(x)', 'x' => 3.7], $this->smarty);
    $this->assertSame(3.0, $result);
  }

  public function testMathAssign() {
    smarty_function_math(['equation' => '2+2', 'assign' => 'mathresult'], $this->smarty);
    $this->assertSame(4, $this->smarty->get_template_vars('mathresult'));
  }

  // ---------------------------------------------------------------------------
  // outputfilter.trimwhitespace
  // ---------------------------------------------------------------------------

  public function testTrimwhitespaceLead() {
    $input = "<div>\n    <p>Hello</p>\n</div>";
    $result = smarty_outputfilter_trimwhitespace($input, $this->smarty);
    // Leading whitespace on non-first lines should be trimmed
    $this->assertStringNotContainsString("\n    <p>", $result);
    $this->assertStringContainsString('Hello', $result);
  }

  public function testTrimwhitespacePreservesPre() {
    $input = "<pre>  keep  spaces  </pre>";
    $result = smarty_outputfilter_trimwhitespace($input, $this->smarty);
    $this->assertStringContainsString('  keep  spaces  ', $result);
  }

  public function testTrimwhitespacePreservesScript() {
    $input = "<script>\n    var x = 1;\n</script>";
    $result = smarty_outputfilter_trimwhitespace($input, $this->smarty);
    $this->assertStringContainsString('    var x = 1;', $result);
  }

  public function testTrimwhitespaceBasicOutput() {
    $input = "line1\n    line2\n    line3";
    $result = smarty_outputfilter_trimwhitespace($input, $this->smarty);
    $this->assertStringContainsString('line1', $result);
    $this->assertStringContainsString('line2', $result);
  }

}
