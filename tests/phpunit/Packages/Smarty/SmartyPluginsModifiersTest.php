<?php

/**
 * Tests for Smarty modifier plugins and shared helpers.
 *
 * These files have no top-level Smarty object dependency and can be loaded
 * standalone via require_once.
 *
 * Run with:
 *   docker exec -w /var/www/html 10.neticrm phpunit modules/civicrm/tests/phpunit/Packages/Smarty/SmartyPluginsModifiersTest.php
 */

$_smarty_plugins_dir = dirname(__FILE__) . '/../../../../packages/Smarty/plugins/';

require_once $_smarty_plugins_dir . 'shared.make_timestamp.php';
require_once $_smarty_plugins_dir . 'shared.escape_special_chars.php';
require_once $_smarty_plugins_dir . 'modifier.capitalize.php';
require_once $_smarty_plugins_dir . 'modifier.cat.php';
require_once $_smarty_plugins_dir . 'modifier.count_characters.php';
require_once $_smarty_plugins_dir . 'modifier.count_paragraphs.php';
require_once $_smarty_plugins_dir . 'modifier.count_sentences.php';
require_once $_smarty_plugins_dir . 'modifier.count_words.php';
require_once $_smarty_plugins_dir . 'modifier.debug_print_var.php';
require_once $_smarty_plugins_dir . 'modifier.default.php';
require_once $_smarty_plugins_dir . 'modifier.escape.php';
require_once $_smarty_plugins_dir . 'modifier.indent.php';
require_once $_smarty_plugins_dir . 'modifier.lower.php';
require_once $_smarty_plugins_dir . 'modifier.nl2br.php';
require_once $_smarty_plugins_dir . 'modifier.regex_replace.php';
require_once $_smarty_plugins_dir . 'modifier.replace.php';
require_once $_smarty_plugins_dir . 'modifier.spacify.php';
require_once $_smarty_plugins_dir . 'modifier.string_format.php';
require_once $_smarty_plugins_dir . 'modifier.strip.php';
require_once $_smarty_plugins_dir . 'modifier.strip_tags.php';
require_once $_smarty_plugins_dir . 'modifier.truncate.php';
require_once $_smarty_plugins_dir . 'modifier.upper.php';
require_once $_smarty_plugins_dir . 'modifier.wordwrap.php';

use PHPUnit\Framework\TestCase;

class SmartyPluginsModifiersTest extends TestCase {

  // ---------------------------------------------------------------------------
  // shared.make_timestamp
  // ---------------------------------------------------------------------------

  public function testMakeTimestampEmpty() {
    $before = time();
    $result = smarty_make_timestamp('');
    $after = time();
    $this->assertGreaterThanOrEqual($before, $result);
    $this->assertLessThanOrEqual($after, $result);
  }

  public function testMakeTimestampNumericString() {
    $this->assertSame(1000000000, smarty_make_timestamp('1000000000'));
  }

  public function testMakeTimestampMysqlFormat() {
    // YYYYMMDDHHMMSS → mktime(HH, MM, SS, MM, DD, YYYY)
    $expected = mktime(12, 30, 45, 6, 15, 2020);
    $this->assertSame($expected, smarty_make_timestamp('20200615123045'));
  }

  public function testMakeTimestampDateString() {
    $result = smarty_make_timestamp('2020-01-01');
    $this->assertSame('2020', date('Y', $result));
    $this->assertSame('01', date('m', $result));
    $this->assertSame('01', date('d', $result));
  }

  // ---------------------------------------------------------------------------
  // _smarty_strftime_to_date_format (added in shared.make_timestamp.php)
  // ---------------------------------------------------------------------------

  public function testStrftimeToDateFormatYear() {
    $this->assertSame('Y', _smarty_strftime_to_date_format('%Y'));
  }

  public function testStrftimeToDateFormatMonthAbbrev() {
    $this->assertSame('M', _smarty_strftime_to_date_format('%b'));
  }

  public function testStrftimeToDateFormatMonthFull() {
    $this->assertSame('F', _smarty_strftime_to_date_format('%B'));
  }

  public function testStrftimeToDateFormatComplex() {
    $this->assertSame('M j, Y', _smarty_strftime_to_date_format('%b %e, %Y'));
  }

  public function testStrftimeToDateFormatLiteralPercent() {
    $this->assertSame('100%', _smarty_strftime_to_date_format('100%%'));
  }

  // ---------------------------------------------------------------------------
  // shared.escape_special_chars
  // ---------------------------------------------------------------------------

  public function testEscapeSpecialCharsLtGt() {
    $this->assertSame('&lt;p&gt;', smarty_function_escape_special_chars('<p>'));
  }

  public function testEscapeSpecialCharsPreservesEntities() {
    // Existing &amp; should not be double-encoded
    $this->assertSame('&amp;', smarty_function_escape_special_chars('&amp;'));
  }

  public function testEscapeSpecialCharsArray() {
    $arr = ['a', 'b'];
    $this->assertSame($arr, smarty_function_escape_special_chars($arr));
  }

  // ---------------------------------------------------------------------------
  // modifier.capitalize
  // ---------------------------------------------------------------------------

  public function testCapitalizeBasic() {
    $this->assertSame('Hello World', smarty_modifier_capitalize('hello world'));
  }

  public function testCapitalizeAlreadyCapitalized() {
    $this->assertSame('Hello World', smarty_modifier_capitalize('Hello World'));
  }

  public function testCapitalizeDigitsNotCapitalized() {
    // digits not capitalized by default
    $this->assertSame('Hello 123 World', smarty_modifier_capitalize('hello 123 world'));
  }

  // ---------------------------------------------------------------------------
  // modifier.cat
  // ---------------------------------------------------------------------------

  public function testCatBasic() {
    $this->assertSame('foobar', smarty_modifier_cat('foo', 'bar'));
  }

  public function testCatEmptyString() {
    $this->assertSame('foo', smarty_modifier_cat('foo', ''));
  }

  // ---------------------------------------------------------------------------
  // modifier.count_characters
  // ---------------------------------------------------------------------------

  public function testCountCharactersWithoutSpaces() {
    $this->assertSame(10, smarty_modifier_count_characters('hello world'));
  }

  public function testCountCharactersWithSpaces() {
    $this->assertSame(11, smarty_modifier_count_characters('hello world', true));
  }

  public function testCountCharactersEmpty() {
    $this->assertSame(0, smarty_modifier_count_characters(''));
  }

  // ---------------------------------------------------------------------------
  // modifier.count_paragraphs
  // ---------------------------------------------------------------------------

  public function testCountParagraphsOneLine() {
    $this->assertSame(1, smarty_modifier_count_paragraphs('hello'));
  }

  public function testCountParagraphsThreeLines() {
    // splits on \r or \n — each line is a "paragraph"
    $this->assertSame(3, smarty_modifier_count_paragraphs("line1\nline2\nline3"));
  }

  public function testCountParagraphsTwoNewlines() {
    $this->assertSame(2, smarty_modifier_count_paragraphs("para1\n\npara2"));
  }

  // ---------------------------------------------------------------------------
  // modifier.count_sentences
  // ---------------------------------------------------------------------------

  public function testCountSentencesBasic() {
    $this->assertSame(3, smarty_modifier_count_sentences('Hello. World. Done.'));
  }

  public function testCountSentencesNone() {
    $this->assertSame(0, smarty_modifier_count_sentences('no sentence'));
  }

  public function testCountSentencesOne() {
    $this->assertSame(1, smarty_modifier_count_sentences('Only one.'));
  }

  // ---------------------------------------------------------------------------
  // modifier.count_words
  // ---------------------------------------------------------------------------

  public function testCountWordsBasic() {
    $this->assertSame(3, smarty_modifier_count_words('hello world foo'));
  }

  public function testCountWordsWithExtraSpaces() {
    $this->assertSame(2, smarty_modifier_count_words('hello  world'));
  }

  public function testCountWordsEmpty() {
    $this->assertSame(0, smarty_modifier_count_words(''));
  }

  // ---------------------------------------------------------------------------
  // modifier.debug_print_var
  // ---------------------------------------------------------------------------

  public function testDebugPrintVarString() {
    $result = smarty_modifier_debug_print_var('hello');
    $this->assertStringContainsString('hello', $result);
  }

  public function testDebugPrintVarInteger() {
    $result = smarty_modifier_debug_print_var(42);
    $this->assertSame('42', $result);
  }

  public function testDebugPrintVarBoolTrue() {
    $result = smarty_modifier_debug_print_var(true);
    $this->assertStringContainsString('true', $result);
  }

  public function testDebugPrintVarBoolFalse() {
    $result = smarty_modifier_debug_print_var(false);
    $this->assertStringContainsString('false', $result);
  }

  public function testDebugPrintVarNull() {
    $result = smarty_modifier_debug_print_var(null);
    $this->assertStringContainsString('null', $result);
  }

  public function testDebugPrintVarArray() {
    $result = smarty_modifier_debug_print_var(['a' => 1]);
    $this->assertStringContainsString('Array', $result);
  }

  // ---------------------------------------------------------------------------
  // modifier.default
  // ---------------------------------------------------------------------------

  public function testDefaultNonEmpty() {
    $this->assertSame('hello', smarty_modifier_default('hello', 'fallback'));
  }

  public function testDefaultEmpty() {
    $this->assertSame('fallback', smarty_modifier_default('', 'fallback'));
  }

  public function testDefaultNull() {
    $this->assertSame('fallback', smarty_modifier_default(null, 'fallback'));
  }

  // ---------------------------------------------------------------------------
  // modifier.escape
  // ---------------------------------------------------------------------------

  public function testEscapeHtml() {
    $this->assertSame('&lt;p&gt;', smarty_modifier_escape('<p>', 'html'));
  }

  public function testEscapeUrl() {
    $this->assertSame('hello%20world', smarty_modifier_escape('hello world', 'url'));
  }

  public function testEscapeJavascript() {
    $result = smarty_modifier_escape("say 'hi'", 'javascript');
    $this->assertStringContainsString("\\'", $result);
  }

  public function testEscapeHex() {
    $result = smarty_modifier_escape('AB', 'hex');
    $this->assertSame('%41%42', $result);
  }

  public function testEscapeDefault() {
    // unknown type → passthrough
    $this->assertSame('hello', smarty_modifier_escape('hello', 'unknown'));
  }

  // ---------------------------------------------------------------------------
  // modifier.indent
  // ---------------------------------------------------------------------------

  public function testIndentDefault() {
    $result = smarty_modifier_indent("line1\nline2");
    $this->assertSame("    line1\n    line2", $result);
  }

  public function testIndentCustomChars() {
    $result = smarty_modifier_indent("line1\nline2", 2);
    $this->assertSame("  line1\n  line2", $result);
  }

  public function testIndentCustomChar() {
    $result = smarty_modifier_indent("line1\nline2", 2, "\t");
    $this->assertSame("\t\tline1\n\t\tline2", $result);
  }

  // ---------------------------------------------------------------------------
  // modifier.lower
  // ---------------------------------------------------------------------------

  public function testLower() {
    $this->assertSame('hello world', smarty_modifier_lower('HELLO WORLD'));
  }

  public function testLowerAlreadyLower() {
    $this->assertSame('hello', smarty_modifier_lower('hello'));
  }

  // ---------------------------------------------------------------------------
  // modifier.nl2br
  // ---------------------------------------------------------------------------

  public function testNl2br() {
    $result = smarty_modifier_nl2br("hello\nworld");
    $this->assertStringContainsString('<br', $result);
    $this->assertStringContainsString('hello', $result);
    $this->assertStringContainsString('world', $result);
  }

  public function testNl2brNoNewline() {
    $this->assertSame('hello', smarty_modifier_nl2br('hello'));
  }

  // ---------------------------------------------------------------------------
  // modifier.regex_replace
  // ---------------------------------------------------------------------------

  public function testRegexReplaceBasic() {
    $this->assertSame('bar', smarty_modifier_regex_replace('foo', '/foo/', 'bar'));
  }

  public function testRegexReplacePartial() {
    $this->assertSame('hello bar', smarty_modifier_regex_replace('hello foo', '/foo/', 'bar'));
  }

  public function testRegexReplaceStripEvalModifier() {
    // 'e' modifier must be stripped — result is plain replacement, not eval
    $result = smarty_modifier_regex_replace('hello', '/hello/e', 'world');
    $this->assertSame('world', $result);
  }

  // ---------------------------------------------------------------------------
  // modifier.replace
  // ---------------------------------------------------------------------------

  public function testReplaceBasic() {
    $this->assertSame('bar', smarty_modifier_replace('foo', 'foo', 'bar'));
  }

  public function testReplaceNoMatch() {
    $this->assertSame('hello', smarty_modifier_replace('hello', 'foo', 'bar'));
  }

  // ---------------------------------------------------------------------------
  // modifier.spacify
  // ---------------------------------------------------------------------------

  public function testSpacifyDefault() {
    $this->assertSame('h e l l o', smarty_modifier_spacify('hello'));
  }

  public function testSpacifyCustomChar() {
    $this->assertSame('h-e-l-l-o', smarty_modifier_spacify('hello', '-'));
  }

  // ---------------------------------------------------------------------------
  // modifier.string_format
  // ---------------------------------------------------------------------------

  public function testStringFormatFloat() {
    $this->assertSame('3.14', smarty_modifier_string_format(3.14159, '%.2f'));
  }

  public function testStringFormatInt() {
    $this->assertSame('042', smarty_modifier_string_format(42, '%03d'));
  }

  // ---------------------------------------------------------------------------
  // modifier.strip
  // ---------------------------------------------------------------------------

  public function testStripMultipleSpaces() {
    $this->assertSame('hello world', smarty_modifier_strip('hello   world'));
  }

  public function testStripTabsAndNewlines() {
    $this->assertSame('hello world', smarty_modifier_strip("hello\t\nworld"));
  }

  public function testStripCustomChar() {
    $this->assertSame('hello_world', smarty_modifier_strip('hello   world', '_'));
  }

  // ---------------------------------------------------------------------------
  // modifier.strip_tags
  // ---------------------------------------------------------------------------

  public function testStripTagsBasic() {
    // Default replaces tags with a space
    $result = smarty_modifier_strip_tags('<p>hello world</p>');
    $this->assertStringContainsString('hello world', $result);
    $this->assertStringNotContainsString('<p>', $result);
  }

  public function testStripTagsNested() {
    $result = smarty_modifier_strip_tags('<b><i>hello</i></b>');
    $this->assertStringContainsString('hello', $result);
    $this->assertStringNotContainsString('<b>', $result);
  }

  public function testStripTagsNoReplace() {
    // replace_with_space=false uses PHP strip_tags
    $this->assertSame('hello world', smarty_modifier_strip_tags('<p>hello world</p>', false));
  }

  public function testStripTagsNoTags() {
    $this->assertSame('hello', smarty_modifier_strip_tags('hello'));
  }

  // ---------------------------------------------------------------------------
  // modifier.truncate
  // ---------------------------------------------------------------------------

  public function testTruncateShortString() {
    $this->assertSame('hi', smarty_modifier_truncate('hi', 80));
  }

  public function testTruncateLongString() {
    $result = smarty_modifier_truncate('The quick brown fox jumps over the lazy dog', 20);
    $this->assertLessThanOrEqual(20, strlen($result));
    $this->assertStringEndsWith('...', $result);
  }

  public function testTruncateBreakWords() {
    $result = smarty_modifier_truncate('abcdefghijklmnopqrstuvwxyz', 10, '...', true);
    $this->assertSame('abcdefg...', $result);
  }

  public function testTruncateMiddle() {
    $result = smarty_modifier_truncate('abcdefghijklmnopqrstuvwxyz', 10, '...', false, true);
    $this->assertStringContainsString('...', $result);
    $this->assertLessThanOrEqual(10, strlen($result));
  }

  public function testTruncateZeroLength() {
    $this->assertSame('', smarty_modifier_truncate('hello', 0));
  }

  // ---------------------------------------------------------------------------
  // modifier.upper
  // ---------------------------------------------------------------------------

  public function testUpper() {
    $this->assertSame('HELLO WORLD', smarty_modifier_upper('hello world'));
  }

  public function testUpperAlreadyUpper() {
    $this->assertSame('HELLO', smarty_modifier_upper('HELLO'));
  }

  // ---------------------------------------------------------------------------
  // modifier.wordwrap
  // ---------------------------------------------------------------------------

  public function testWordwrapBasic() {
    $result = smarty_modifier_wordwrap('The quick brown fox', 10);
    $lines = explode("\n", $result);
    foreach ($lines as $line) {
      $this->assertLessThanOrEqual(10, strlen($line));
    }
  }

  public function testWordwrapShortString() {
    $this->assertSame('hi', smarty_modifier_wordwrap('hi', 80));
  }

}
