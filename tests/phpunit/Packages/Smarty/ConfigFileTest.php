<?php

/**
 * Tests for Config_File.class.php
 *
 * Run with:
 *   docker exec 10.neticrm phpunit modules/civicrm/tests/phpunit/Packages/Smarty/ConfigFileTest.php
 */

require_once dirname(__FILE__) . '/../../../../packages/Smarty/Config_File.class.php';

use PHPUnit\Framework\TestCase;

class ConfigFileTest extends TestCase {

  /** @var Config_File */
  protected $config;

  protected function setUp(): void {
    $this->config = new Config_File();
  }

  // ---------------------------------------------------------------------------
  // parse_contents() — basic global variables
  // ---------------------------------------------------------------------------

  public function testParseContentsSimpleVar() {
    $result = $this->config->parse_contents("foo = bar\n");
    $this->assertArrayHasKey('vars', $result);
    $this->assertSame('bar', $result['vars']['foo']);
  }

  public function testParseContentsMultipleVars() {
    $contents = "name = Alice\nage = 30\n";
    $result = $this->config->parse_contents($contents);
    $this->assertSame('Alice', $result['vars']['name']);
    $this->assertSame('30', $result['vars']['age']);
  }

  public function testParseContentsQuotedValue() {
    $result = $this->config->parse_contents("greeting = \"Hello World\"\n");
    $this->assertSame('Hello World', $result['vars']['greeting']);
  }

  public function testParseContentsSingleQuotedValue() {
    $result = $this->config->parse_contents("msg = 'Single quoted'\n");
    $this->assertSame('Single quoted', $result['vars']['msg']);
  }

  public function testParseContentsCommentLinesIgnored() {
    $contents = "# this is a comment\nkey = value\n";
    $result = $this->config->parse_contents($contents);
    $this->assertSame('value', $result['vars']['key']);
    $this->assertArrayNotHasKey('# this is a comment', $result['vars']);
  }

  // ---------------------------------------------------------------------------
  // parse_contents() — boolean conversion
  // ---------------------------------------------------------------------------

  public function testParseContentsBooleanTrue() {
    foreach (['on', 'true', 'yes'] as $truthy) {
      $result = $this->config->parse_contents("flag = $truthy\n");
      $this->assertTrue($result['vars']['flag'], "Expected TRUE for value '$truthy'");
    }
  }

  public function testParseContentsBooleanFalse() {
    foreach (['off', 'false', 'no'] as $falsy) {
      $result = $this->config->parse_contents("flag = $falsy\n");
      $this->assertFalse($result['vars']['flag'], "Expected FALSE for value '$falsy'");
    }
  }

  public function testParseContentsBooleanDisabled() {
    $this->config->booleanize = false;
    $result = $this->config->parse_contents("flag = true\n");
    $this->assertSame('true', $result['vars']['flag']);
  }

  // ---------------------------------------------------------------------------
  // parse_contents() — sections
  // ---------------------------------------------------------------------------

  public function testParseContentsSectionVar() {
    $contents = "[MySect]\nkey = sectval\n";
    $result = $this->config->parse_contents($contents);
    $this->assertArrayHasKey('MySect', $result['sections']);
    $this->assertSame('sectval', $result['sections']['MySect']['vars']['key']);
  }

  public function testParseContentsMultipleSections() {
    $contents = "[SecA]\na = 1\n[SecB]\nb = 2\n";
    $result = $this->config->parse_contents($contents);
    $this->assertSame('1', $result['sections']['SecA']['vars']['a']);
    $this->assertSame('2', $result['sections']['SecB']['vars']['b']);
  }

  public function testParseContentsGlobalVarBeforeSection() {
    $contents = "global_key = global_val\n[Sect]\nsect_key = sect_val\n";
    $result = $this->config->parse_contents($contents);
    $this->assertSame('global_val', $result['vars']['global_key']);
    $this->assertSame('sect_val', $result['sections']['Sect']['vars']['sect_key']);
  }

  // ---------------------------------------------------------------------------
  // parse_contents() — multiline values
  // ---------------------------------------------------------------------------

  public function testParseContentsMultilineValue() {
    $contents = "multi = \"\"\"line one\nline two\nline three\"\"\"\n";
    $result = $this->config->parse_contents($contents);
    $this->assertStringContainsString('line one', $result['vars']['multi']);
    $this->assertStringContainsString('line two', $result['vars']['multi']);
    $this->assertStringContainsString('line three', $result['vars']['multi']);
  }

  // ---------------------------------------------------------------------------
  // parse_contents() — overwrite behaviour
  // ---------------------------------------------------------------------------

  public function testParseContentsOverwriteOn() {
    $this->config->overwrite = true;
    $contents = "key = first\nkey = second\n";
    $result = $this->config->parse_contents($contents);
    $this->assertSame('second', $result['vars']['key']);
  }

  public function testParseContentsOverwriteOff() {
    $this->config->overwrite = false;
    $contents = "key = first\nkey = second\n";
    $result = $this->config->parse_contents($contents);
    // Should accumulate into an array
    $this->assertIsArray($result['vars']['key']);
    $this->assertContains('first', $result['vars']['key']);
    $this->assertContains('second', $result['vars']['key']);
  }

  // ---------------------------------------------------------------------------
  // parse_contents() — hidden sections
  // ---------------------------------------------------------------------------

  public function testParseContentsHiddenSectionRead() {
    $this->config->read_hidden = true;
    $contents = "[.hidden]\nhidden_key = secret\n";
    $result = $this->config->parse_contents($contents);
    $this->assertArrayHasKey('hidden', $result['sections']);
    $this->assertSame('secret', $result['sections']['hidden']['vars']['hidden_key']);
  }

  public function testParseContentsHiddenSectionNotRead() {
    $this->config->read_hidden = false;
    $contents = "[.hidden]\nhidden_key = secret\n";
    $result = $this->config->parse_contents($contents);
    // hidden section should not appear when read_hidden=false
    $this->assertArrayNotHasKey('hidden', $result['sections']);
  }

  // ---------------------------------------------------------------------------
  // parse_contents() — newline fixing
  // ---------------------------------------------------------------------------

  public function testParseContentsFixesWindowsNewlines() {
    $contents = "key = value\r\n";
    $result = $this->config->parse_contents($contents);
    $this->assertSame('value', $result['vars']['key']);
  }

  public function testParseContentsFixesMacNewlines() {
    $contents = "key = value\r";
    $result = $this->config->parse_contents($contents);
    $this->assertSame('value', $result['vars']['key']);
  }

  // ---------------------------------------------------------------------------
  // set_file_contents() / get()
  // ---------------------------------------------------------------------------

  public function testSetFileContentsAndGetGlobal() {
    $this->config->set_file_contents('virtual.conf', "color = blue\n");
    $this->assertSame('blue', $this->config->get('virtual.conf', null, 'color'));
  }

  public function testSetFileContentsAndGetSection() {
    $this->config->set_file_contents('virtual.conf', "[Theme]\nbg = red\n");
    $this->assertSame('red', $this->config->get('virtual.conf', 'Theme', 'bg'));
  }

  public function testSetFileContentsGetAllGlobalVars() {
    $this->config->set_file_contents('virtual.conf', "a = 1\nb = 2\n");
    $vars = $this->config->get('virtual.conf');
    $this->assertArrayHasKey('a', $vars);
    $this->assertArrayHasKey('b', $vars);
  }

  public function testSetFileContentsGetAllSectionVars() {
    $this->config->set_file_contents('virtual.conf', "[S]\nx = 10\ny = 20\n");
    $vars = $this->config->get('virtual.conf', 'S');
    $this->assertArrayHasKey('x', $vars);
    $this->assertArrayHasKey('y', $vars);
  }

  public function testGetNonExistentSectionVar() {
    $this->config->set_file_contents('virtual.conf', "[S]\nx = 10\n");
    $result = $this->config->get('virtual.conf', 'NoSuchSection', 'x');
    $this->assertSame([], $result);
  }

  // ---------------------------------------------------------------------------
  // get_file_names()
  // ---------------------------------------------------------------------------

  public function testGetFileNames() {
    $this->config->set_file_contents('file_a.conf', "k = v\n");
    $this->config->set_file_contents('file_b.conf', "k = v\n");
    $names = $this->config->get_file_names();
    $this->assertContains('file_a.conf', $names);
    $this->assertContains('file_b.conf', $names);
  }

  public function testGetFileNamesEmptyInitially() {
    $fresh = new Config_File();
    $this->assertSame([], $fresh->get_file_names());
  }

  // ---------------------------------------------------------------------------
  // clear()
  // ---------------------------------------------------------------------------

  public function testClearSpecificFile() {
    $this->config->set_file_contents('to_clear.conf', "k = v\n");
    $this->config->clear('to_clear.conf');
    // After clear, the file entry exists but has no vars
    $this->assertSame([], $this->config->get('to_clear.conf'));
  }

  public function testClearAll() {
    $this->config->set_file_contents('a.conf', "k = v\n");
    $this->config->set_file_contents('b.conf', "k = v\n");
    $this->config->clear();
    $this->assertSame([], $this->config->get_file_names());
  }

  public function testClearNonExistentFileDoesNothing() {
    $this->config->set_file_contents('real.conf', "k = v\n");
    // Clearing a file that was never loaded should not error
    $this->config->clear('nonexistent.conf');
    // real.conf should be unaffected - its key still appears in file names
    $this->assertContains('real.conf', $this->config->get_file_names());
  }

  // ---------------------------------------------------------------------------
  // load_file() — uses filesystem via temp file
  // ---------------------------------------------------------------------------

  public function testLoadFile() {
    $tmpFile = tempnam(sys_get_temp_dir(), 'cfg_test_');
    file_put_contents($tmpFile, "loaded_key = loaded_val\n");
    $result = $this->config->load_file($tmpFile, false);
    $this->assertTrue($result);
    $val = $this->config->get($tmpFile, null, 'loaded_key');
    $this->assertSame('loaded_val', $val);
    unlink($tmpFile);
  }

  public function testLoadFileNonExistentReturnsFalse() {
    $result = @$this->config->load_file('/no/such/file.conf', false);
    $this->assertFalse($result);
  }

  // ---------------------------------------------------------------------------
  // get_section_names() — via set_file_contents
  // ---------------------------------------------------------------------------

  public function testGetSectionNames() {
    $contents = "[Alpha]\na=1\n[Beta]\nb=2\n";
    $this->config->set_file_contents('sects.conf', $contents);
    $sections = $this->config->get_section_names('sects.conf');
    $this->assertContains('Alpha', $sections);
    $this->assertContains('Beta', $sections);
  }

  // ---------------------------------------------------------------------------
  // get_var_names() — via set_file_contents
  // ---------------------------------------------------------------------------

  public function testGetVarNamesGlobal() {
    $this->config->set_file_contents('vars.conf', "x = 1\ny = 2\n");
    $names = $this->config->get_var_names('vars.conf');
    $this->assertContains('x', $names);
    $this->assertContains('y', $names);
  }

  public function testGetVarNamesSection() {
    $contents = "[MySec]\nfoo = 1\nbar = 2\n";
    $this->config->set_file_contents('vars.conf', $contents);
    $names = $this->config->get_var_names('vars.conf', 'MySec');
    $this->assertContains('foo', $names);
    $this->assertContains('bar', $names);
  }
}
