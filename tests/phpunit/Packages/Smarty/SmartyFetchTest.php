<?php

/**
 * Integration tests for Smarty 2 template fetch().
 *
 * Each test writes one or more .tpl files to a temporary template_dir,
 * then calls $smarty->fetch() and asserts the output matches expectations.
 *
 * Covers: variable output, modifiers, {if}/{foreach}/{section},
 * {assign}, {capture}, {math}, {counter}, {cycle}, {include},
 * comments, {literal}, and built-in functions.
 *
 * Run with:
 *   docker exec -w /var/www/html 10.neticrm phpunit modules/civicrm/tests/phpunit/Packages/Smarty/SmartyFetchTest.php
 */

require_once dirname(__FILE__) . '/../../../../packages/Smarty/Smarty.class.php';

use PHPUnit\Framework\TestCase;

class SmartyFetchTest extends TestCase {

  /** @var Smarty */
  private $smarty;

  /** @var string */
  private $tplDir;

  /** @var string */
  private $compileDir;

  protected function setUp(): void {
    $base = sys_get_temp_dir() . '/smarty_fetch_test_' . uniqid();
    $this->tplDir    = $base . '/templates';
    $this->compileDir = $base . '/templates_c';
    mkdir($this->tplDir,    0755, true);
    mkdir($this->compileDir, 0755, true);

    $this->smarty = new Smarty();
    $this->smarty->template_dir  = $this->tplDir;
    $this->smarty->compile_dir   = $this->compileDir;
    $this->smarty->plugins_dir   = [SMARTY_DIR . 'plugins'];
    $this->smarty->caching       = false;
    $this->smarty->compile_check = true;
  }

  protected function tearDown(): void {
    $this->rmdirRecursive(dirname($this->tplDir));
  }

  // ---------------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------------

  /**
   * Write content to a .tpl file in the temporary template directory.
   */
  private function writeTpl(string $name, string $content): void {
    file_put_contents($this->tplDir . '/' . $name, $content);
  }

  /**
   * Assign variables and fetch a named template; returns trimmed output.
   *
   * @param string $tplName  File name relative to template_dir
   * @param array  $vars     Variables to assign before fetch
   */
  private function fetch(string $tplName, array $vars = []): string {
    $this->smarty->clear_all_assign();
    foreach ($vars as $k => $v) {
      $this->smarty->assign($k, $v);
    }
    return trim($this->smarty->fetch($tplName));
  }

  private function rmdirRecursive(string $dir): void {
    if (!is_dir($dir)) {
      return;
    }
    foreach (scandir($dir) as $entry) {
      if ($entry === '.' || $entry === '..') {
        continue;
      }
      $path = $dir . '/' . $entry;
      is_dir($path) ? $this->rmdirRecursive($path) : unlink($path);
    }
    rmdir($dir);
  }

  // ---------------------------------------------------------------------------
  // Variable output
  // ---------------------------------------------------------------------------

  public function testSimpleVariable() {
    $this->writeTpl('var.tpl', 'Hello, {$name}!');
    $this->assertSame('Hello, World!', $this->fetch('var.tpl', ['name' => 'World']));
  }

  public function testEmptyStringVariableOutputsNothing() {
    $this->writeTpl('empty_str.tpl', '[{$s}]');
    $this->assertSame('[]', $this->fetch('empty_str.tpl', ['s' => '']));
  }

  public function testZeroVariableOutputsZero() {
    $this->writeTpl('zero.tpl', '{$n}');
    $this->assertSame('0', $this->fetch('zero.tpl', ['n' => 0]));
  }

  public function testStaticLiteralText() {
    $this->writeTpl('static.tpl', 'Just static text.');
    $this->assertSame('Just static text.', $this->fetch('static.tpl'));
  }

  // ---------------------------------------------------------------------------
  // Modifiers
  // ---------------------------------------------------------------------------

  public function testModifierUpper() {
    $this->writeTpl('upper.tpl', '{$s|upper}');
    $this->assertSame('HELLO', $this->fetch('upper.tpl', ['s' => 'hello']));
  }

  public function testModifierLower() {
    $this->writeTpl('lower.tpl', '{$s|lower}');
    $this->assertSame('hello', $this->fetch('lower.tpl', ['s' => 'HELLO']));
  }

  public function testModifierCapitalize() {
    $this->writeTpl('cap.tpl', '{$s|capitalize}');
    $this->assertSame('Hello World', $this->fetch('cap.tpl', ['s' => 'hello world']));
  }

  public function testModifierEscapeHtml() {
    $this->writeTpl('esc.tpl', '{$s|escape:"html"}');
    $this->assertSame('&lt;b&gt;bold&lt;/b&gt;', $this->fetch('esc.tpl', ['s' => '<b>bold</b>']));
  }

  public function testModifierDefault() {
    $this->writeTpl('def.tpl', '{$s|default:"fallback"}');
    $this->assertSame('fallback', $this->fetch('def.tpl', ['s' => '']));
  }

  public function testModifierDefaultWithValue() {
    $this->writeTpl('def2.tpl', '{$s|default:"fallback"}');
    $this->assertSame('given', $this->fetch('def2.tpl', ['s' => 'given']));
  }

  public function testModifierTruncate() {
    $this->writeTpl('trunc.tpl', '{$s|truncate:10}');
    $result = $this->fetch('trunc.tpl', ['s' => 'The quick brown fox']);
    $this->assertLessThanOrEqual(10, strlen($result));
    $this->assertStringEndsWith('...', $result);
  }

  public function testModifierReplace() {
    $this->writeTpl('rep.tpl', '{$s|replace:"foo":"bar"}');
    $this->assertSame('bar baz', $this->fetch('rep.tpl', ['s' => 'foo baz']));
  }

  public function testModifierCat() {
    $this->writeTpl('cat.tpl', '{$s|cat:" world"}');
    $this->assertSame('hello world', $this->fetch('cat.tpl', ['s' => 'hello']));
  }

  public function testModifierNl2br() {
    $this->writeTpl('nl2br.tpl', '{$s|nl2br}');
    $result = $this->fetch('nl2br.tpl', ['s' => "a\nb"]);
    $this->assertStringContainsString('<br', $result);
    $this->assertStringContainsString('a', $result);
    $this->assertStringContainsString('b', $result);
  }

  public function testModifierSpacify() {
    $this->writeTpl('spacify.tpl', '{$s|spacify}');
    $this->assertSame('h e l l o', $this->fetch('spacify.tpl', ['s' => 'hello']));
  }

  public function testModifierStripTags() {
    $this->writeTpl('strip_tags.tpl', '{$s|strip_tags:false}');
    $this->assertSame('hello', $this->fetch('strip_tags.tpl', ['s' => '<p>hello</p>']));
  }

  public function testModifierWordwrap() {
    $this->writeTpl('ww.tpl', '{$s|wordwrap:10}');
    $result = $this->fetch('ww.tpl', ['s' => 'The quick brown fox']);
    foreach (explode("\n", $result) as $line) {
      $this->assertLessThanOrEqual(10, strlen($line));
    }
  }

  public function testModifierStringFormat() {
    $this->writeTpl('fmt.tpl', '{$n|string_format:"%.2f"}');
    $this->assertSame('3.14', $this->fetch('fmt.tpl', ['n' => 3.14159]));
  }

  public function testModifierCountWords() {
    $this->writeTpl('cw.tpl', '{$s|count_words}');
    $this->assertSame('3', $this->fetch('cw.tpl', ['s' => 'one two three']));
  }

  public function testModifierChained() {
    // Multiple modifiers chained
    $this->writeTpl('chain.tpl', '{$s|upper|truncate:5:""}');
    $this->assertSame('HELLO', $this->fetch('chain.tpl', ['s' => 'hello world']));
  }

  // ---------------------------------------------------------------------------
  // Control: {if} / {elseif} / {else}
  // ---------------------------------------------------------------------------

  public function testIfTrue() {
    $this->writeTpl('if.tpl', '{if $x}yes{else}no{/if}');
    $this->assertSame('yes', $this->fetch('if.tpl', ['x' => true]));
  }

  public function testIfFalse() {
    $this->writeTpl('ifno.tpl', '{if $x}yes{else}no{/if}');
    $this->assertSame('no', $this->fetch('ifno.tpl', ['x' => false]));
  }

  public function testIfElseif() {
    $this->writeTpl('elseif.tpl',
      '{if $n == 1}one{elseif $n == 2}two{else}other{/if}');
    $this->assertSame('one',   $this->fetch('elseif.tpl', ['n' => 1]));
    $this->assertSame('two',   $this->fetch('elseif.tpl', ['n' => 2]));
    $this->assertSame('other', $this->fetch('elseif.tpl', ['n' => 9]));
  }

  public function testIfComparison() {
    $this->writeTpl('cmp.tpl', '{if $score >= 90}A{elseif $score >= 80}B{else}C{/if}');
    $this->assertSame('A', $this->fetch('cmp.tpl', ['score' => 95]));
    $this->assertSame('B', $this->fetch('cmp.tpl', ['score' => 82]));
    $this->assertSame('C', $this->fetch('cmp.tpl', ['score' => 70]));
  }

  public function testIfNested() {
    $tpl = '{if $a}{if $b}both{else}a only{/if}{else}neither{/if}';
    $this->writeTpl('nested_if.tpl', $tpl);
    $this->assertSame('both',    $this->fetch('nested_if.tpl', ['a' => true, 'b' => true]));
    $this->assertSame('a only',  $this->fetch('nested_if.tpl', ['a' => true, 'b' => false]));
    $this->assertSame('neither', $this->fetch('nested_if.tpl', ['a' => false, 'b' => true]));
  }

  // ---------------------------------------------------------------------------
  // Control: {foreach}
  // ---------------------------------------------------------------------------

  public function testForeachBasic() {
    $this->writeTpl('fe.tpl', '{foreach from=$list item=v}{$v},{/foreach}');
    $this->assertSame('a,b,c,', $this->fetch('fe.tpl', ['list' => ['a', 'b', 'c']]));
  }

  public function testForeachWithKey() {
    $this->writeTpl('fek.tpl', '{foreach from=$map key=k item=v}{$k}:{$v} {/foreach}');
    $result = $this->fetch('fek.tpl', ['map' => ['x' => 1, 'y' => 2]]);
    $this->assertStringContainsString('x:1', $result);
    $this->assertStringContainsString('y:2', $result);
  }

  public function testForeachElse() {
    $this->writeTpl('feelse.tpl',
      '{foreach from=$list item=v}{$v}{foreachelse}empty{/foreach}');
    $this->assertSame('empty', $this->fetch('feelse.tpl', ['list' => []]));
  }

  public function testForeachFirst() {
    $tpl = '{foreach from=$list item=v name=loop}'
         . '{if $smarty.foreach.loop.first}[first]{/if}'
         . '{$v}'
         . '{/foreach}';
    $this->writeTpl('fe_first.tpl', $tpl);
    $result = $this->fetch('fe_first.tpl', ['list' => ['a', 'b', 'c']]);
    $this->assertSame('[first]abc', $result);
  }

  public function testForeachLast() {
    $tpl = '{foreach from=$list item=v name=loop}'
         . '{$v}'
         . '{if $smarty.foreach.loop.last}[last]{/if}'
         . '{/foreach}';
    $this->writeTpl('fe_last.tpl', $tpl);
    $result = $this->fetch('fe_last.tpl', ['list' => ['a', 'b', 'c']]);
    $this->assertSame('abc[last]', $result);
  }

  // ---------------------------------------------------------------------------
  // Control: {section}
  // ---------------------------------------------------------------------------

  public function testSectionBasic() {
    $this->writeTpl('sec.tpl',
      '{section name=i loop=$arr}{$arr[i]},{/section}');
    $this->assertSame('x,y,z,', $this->fetch('sec.tpl', ['arr' => ['x', 'y', 'z']]));
  }

  public function testSectionIndex() {
    $this->writeTpl('secidx.tpl',
      '{section name=i loop=$arr}{$smarty.section.i.index},{/section}');
    $this->assertSame('0,1,2,', $this->fetch('secidx.tpl', ['arr' => ['a', 'b', 'c']]));
  }

  public function testSectionRownum() {
    // rownum is 1-based
    $this->writeTpl('secrow.tpl',
      '{section name=i loop=$arr}{$smarty.section.i.rownum},{/section}');
    $this->assertSame('1,2,3,', $this->fetch('secrow.tpl', ['arr' => ['a', 'b', 'c']]));
  }

  public function testSectionElse() {
    $this->writeTpl('secelse.tpl',
      '{section name=i loop=$arr}{$arr[i]}{sectionelse}empty{/section}');
    $this->assertSame('empty', $this->fetch('secelse.tpl', ['arr' => []]));
  }

  // ---------------------------------------------------------------------------
  // Built-in tags
  // ---------------------------------------------------------------------------

  public function testAssignTag() {
    $this->writeTpl('assign.tpl', '{assign var="x" value="computed"}{$x}');
    $this->assertSame('computed', $this->fetch('assign.tpl'));
  }

  public function testAssignTagOverwrite() {
    $this->writeTpl('assign2.tpl',
      '{assign var="v" value="first"}{assign var="v" value="second"}{$v}');
    $this->assertSame('second', $this->fetch('assign2.tpl'));
  }

  public function testCaptureTag() {
    $this->writeTpl('capture.tpl',
      '{capture name="block"}captured content{/capture}[{$smarty.capture.block}]');
    $this->assertSame('[captured content]', $this->fetch('capture.tpl'));
  }

  public function testCaptureTagWithModifier() {
    $this->writeTpl('cap_mod.tpl',
      '{capture name="c"}hello{/capture}{$smarty.capture.c|upper}');
    $this->assertSame('HELLO', $this->fetch('cap_mod.tpl'));
  }

  public function testComment() {
    $this->writeTpl('comment.tpl', '{* this is a comment *}result');
    $this->assertSame('result', $this->fetch('comment.tpl'));
  }

  public function testLiteralBlock() {
    $this->writeTpl('literal.tpl', '{literal}{$not_replaced}{/literal}');
    $this->assertSame('{$not_replaced}', $this->fetch('literal.tpl'));
  }

  // ---------------------------------------------------------------------------
  // Built-in functions (plugins)
  // ---------------------------------------------------------------------------

  public function testMathFunction() {
    $this->writeTpl('math.tpl', '{math equation="x*y" x=6 y=7}');
    $this->assertSame('42', $this->fetch('math.tpl'));
  }

  public function testMathFunctionFloat() {
    $this->writeTpl('mathf.tpl', '{math equation="x/y" x=1 y=3 format="%.4f"}');
    $this->assertSame('0.3333', $this->fetch('mathf.tpl'));
  }

  public function testCounterFunction() {
    // Use unique counter names to avoid static-state cross-test interference
    $this->writeTpl('counter.tpl',
      '{counter name="fetch_c1" start=1}'
      . '{counter name="fetch_c1"}'
      . '{counter name="fetch_c1"}');
    $this->assertSame('123', $this->fetch('counter.tpl'));
  }

  public function testCounterSkip() {
    $this->writeTpl('counter_skip.tpl',
      '{counter name="fetch_skip" start=0 skip=5}'
      . '{counter name="fetch_skip"}'
      . '{counter name="fetch_skip"}');
    $this->assertSame('0510', $this->fetch('counter_skip.tpl'));
  }

  public function testCycleFunction() {
    $this->writeTpl('cycle.tpl',
      '{cycle name="fetch_cy1" values="red,blue,green"}'
      . '{cycle name="fetch_cy1"}'
      . '{cycle name="fetch_cy1"}'
      . '{cycle name="fetch_cy1"}');
    $this->assertSame('redbluegreen' . 'red', $this->fetch('cycle.tpl'));
  }

  public function testTextformatBlock() {
    $long = str_repeat('word ', 20);
    $this->writeTpl('textformat.tpl', '{textformat wrap=30}' . $long . '{/textformat}');
    $result = $this->fetch('textformat.tpl');
    foreach (explode("\n", $result) as $line) {
      $this->assertLessThanOrEqual(30, strlen($line));
    }
  }

  // ---------------------------------------------------------------------------
  // {include} sub-template
  // ---------------------------------------------------------------------------

  public function testIncludeSubTemplate() {
    $this->writeTpl('_sub.tpl', 'Hello from sub: {$subvar}');
    $this->writeTpl('parent.tpl',
      'Before. {include file="_sub.tpl" subvar="included!"} After.');
    $this->assertSame('Before. Hello from sub: included! After.',
      $this->fetch('parent.tpl'));
  }

  public function testIncludeInheritsParentVars() {
    $this->writeTpl('_child.tpl', '[{$shared}]');
    $this->writeTpl('main.tpl', 'X{include file="_child.tpl"}X');
    $this->assertSame('X[parentval]X',
      $this->fetch('main.tpl', ['shared' => 'parentval']));
  }

  // ---------------------------------------------------------------------------
  // Comprehensive .tpl: realistic template combining multiple features
  // ---------------------------------------------------------------------------

  /**
   * A realistic "order summary" template that exercises:
   *   variables, modifiers, {if}, {foreach}, {assign}, {math}, comments.
   */
  public function testComprehensiveOrderTemplate() {
    $tpl = <<<'TPL'
{* Order summary template *}
<h1>{$title|escape:"html"|upper}</h1>
{assign var="total" value=0}
{if $items}
<ul>
{foreach from=$items item=item}
  <li>{$item.name|escape:"html"|capitalize} — ${$item.price|string_format:"%.2f"}{if $item.qty > 1} x{$item.qty}{/if}</li>
{assign var="total" value=$total+$item.price*$item.qty}
{/foreach}
</ul>
<p>Total: ${$total|string_format:"%.2f"}</p>
{else}
<p>No items.</p>
{/if}
{if $note}<p class="note">{$note|escape:"html"}</p>{/if}
TPL;

    $this->writeTpl('order.tpl', $tpl);

    $result = $this->fetch('order.tpl', [
      'title' => 'your order',
      'items' => [
        ['name' => 'widget',  'price' => 9.99,  'qty' => 2],
        ['name' => 'gadget',  'price' => 19.99, 'qty' => 1],
      ],
      'note' => 'Ships in 3-5 days.',
    ]);

    // Title: escaped + uppercased
    $this->assertStringContainsString('<h1>YOUR ORDER</h1>', $result);

    // Items appear capitalized
    $this->assertStringContainsString('Widget', $result);
    $this->assertStringContainsString('Gadget', $result);

    // Price formatting
    $this->assertStringContainsString('$9.99', $result);
    $this->assertStringContainsString('$19.99', $result);

    // qty > 1 suffix
    $this->assertStringContainsString('x2', $result);

    // Total: 9.99*2 + 19.99*1 = 39.97
    $this->assertStringContainsString('$39.97', $result);

    // Note appears escaped
    $this->assertStringContainsString('Ships in 3-5 days.', $result);
  }

  /**
   * Same template with no items should render the fallback branch.
   */
  public function testComprehensiveOrderTemplateEmpty() {
    $tpl = <<<'TPL'
{* Order summary template *}
<h1>{$title|upper}</h1>
{if $items}
<ul>
{foreach from=$items item=item}<li>{$item.name}</li>{/foreach}
</ul>
{else}
<p>No items.</p>
{/if}
TPL;

    $this->writeTpl('order_empty.tpl', $tpl);
    $result = $this->fetch('order_empty.tpl', ['title' => 'empty', 'items' => []]);
    $this->assertStringContainsString('<p>No items.</p>', $result);
    $this->assertStringNotContainsString('<li>', $result);
  }

  // ---------------------------------------------------------------------------
  // Re-compile check: editing a .tpl and re-fetching produces new output
  // ---------------------------------------------------------------------------

  public function testRecompileOnChange() {
    $this->writeTpl('recompile.tpl', 'version one');
    $this->assertSame('version one', $this->fetch('recompile.tpl'));

    // Touch the file (force mtime change) and rewrite content
    sleep(1);
    $this->writeTpl('recompile.tpl', 'version two');

    $this->assertSame('version two', $this->fetch('recompile.tpl'));
  }

}
