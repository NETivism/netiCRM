<?php
/**
 * netiCRM license header auto-update script
 *
 * Usage:
 *   php header-update.php [--dry-run] [file.php]
 *
 * Options:
 *   --dry-run   Show diff of what would change without writing any files
 *   file.php    Process only the specified file instead of CRM/, extern/, api/
 *
 * Processes CRM/, extern/, and api/ directories (or a single file):
 * - Replaces CiviCRM AGPL headers with header-legacy.tpl (NETivism + CiviCRM)
 * - Inserts header.tpl (NETivism only) into files with no license block
 * - Adds @copyright NETivism Co., Ltd. to PHPDoc blocks
 */

if (PHP_SAPI !== 'cli') {
  die("This script can only be run from command line.\n");
}
error_reporting(0);

// --- Parse arguments ---
$dryRun = FALSE;
$singleFile = NULL;

foreach (array_slice($argv, 1) as $arg) {
  if ($arg === '--dry-run') {
    $dryRun = TRUE;
  }
  elseif (substr($arg, 0, 2) !== '--') {
    $singleFile = realpath($arg);
    if ($singleFile === FALSE) {
      die("Error: file not found: {$arg}\n");
    }
    if (pathinfo($singleFile, PATHINFO_EXTENSION) !== 'php') {
      die("Error: specified file is not a .php file: {$arg}\n");
    }
  }
}

if ($dryRun) {
  echo "[dry-run] No files will be written.\n\n";
}

// --- Smarty setup ---
$scriptDir = __DIR__;
$civiRoot = dirname($scriptDir, 2);

ini_set('include_path', $civiRoot . '/packages' . PATH_SEPARATOR . ini_get('include_path'));

require_once 'Smarty/Smarty.class.php';
$smarty = new Smarty();
$smarty->template_dir = $scriptDir;
$smarty->plugins_dir = [$civiRoot . '/packages/Smarty/plugins'];
$compileDir = sys_get_temp_dir() . '/header_update_templates_c';
if (!is_dir($compileDir)) {
  mkdir($compileDir, 0755, TRUE);
}
$smarty->compile_dir = $compileDir;
$smarty->clear_all_cache();
$smarty->assign('currentYear', date('Y'));
// For pure netiCRM files (no CiviCRM heritage), startYear is fixed to 2013
$smarty->assign('startYear', '2011');

// header-legacy.tpl uses $copyrightCiviCRM/$startYear (per-file), rendered inside the loop
$headerNew = trim($smarty->fetch('header-php.tpl'));

$netivismCopyright = ' * @copyright NETivism Co., Ltd. https://neticrm.tw/about/licensing';

// --- Build file iterator ---
function buildFileList($singleFile, $civiRoot) {
  if ($singleFile !== NULL) {
    return [$singleFile];
  }

  $searchDirs = [
    $civiRoot . '/CRM',
    $civiRoot . '/extern',
    $civiRoot . '/api',
  ];

  $files = [];
  foreach ($searchDirs as $dir) {
    if (!is_dir($dir)) {
      continue;
    }
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    foreach (new RecursiveIteratorIterator($it) as $file) {
      if ($file->getExtension() === 'php') {
        $files[] = $file->getPathname();
      }
    }
  }
  return $files;
}

// --- Diff helper (via system diff -uw, writes temp file) ---
function unifiedDiff($new, $filePath) {
  $hash    = substr(md5($filePath), 0, 8);
  $tmpFile = sys_get_temp_dir() . '/' . basename($filePath, '.php') . '.' . $hash . '.php';
  file_put_contents($tmpFile, $new);
  $orig = escapeshellarg($filePath);
  $tmp  = escapeshellarg($tmpFile);
  $out  = shell_exec("diff -uw {$orig} {$tmp}");
  unlink($tmpFile);
  return $out ?? '';
}

// --- Main loop ---
$fileList = buildFileList($singleFile, $civiRoot);

foreach ($fileList as $filePath) {
  $content = file_get_contents($filePath);
  $originalContent = $content;

  // --- Step A: Handle top license block (/* ... */, not /** ... */) ---
  // Skip optional single-line comments (e.g. // $Id$) between <?php and the block
  $hasLicenseBlock = preg_match(
    '/^<\?php\s*(?:\/\/[^\n]*\n\s*)*(\/\*(?!\*).*?\*\/)/s',
    $content,
    $matches
  );

  if ($hasLicenseBlock) {
    $topComment = $matches[1];
    $hasAgpl = (bool) preg_match('/Affero General Public License|AGPL/i', $topComment);
    $hasCiviCrm = stripos($topComment, 'CiviCRM LLC') !== FALSE;
    $hasNetiCrm = stripos($topComment, 'NETivism') !== FALSE
      || stripos($topComment, 'neticrm.tw') !== FALSE;

    if ($hasAgpl && $hasCiviCrm) {
      $copyrightCiviCRM = '';
      if (preg_match('/\|\s*(Copyright CiviCRM LLC[^|\n]+)\|/i', $topComment, $cpMatch)) {
        $copyrightCiviCRM = rtrim($cpMatch[1]);
      }
      $smarty->assign('copyrightCiviCRM', str_pad($copyrightCiviCRM, 56));

      // NETivism start year = CiviCRM end year + 1, minimum 2011 (netiCRM founding year)
      $startYear = 2011;
      if (preg_match('/\d{4}-(\d{4})/', $copyrightCiviCRM, $yearMatch)) {
        $startYear = max(2011, (int) $yearMatch[1] + 1);
      }
      $smarty->assign('startYear', $startYear);

      // Render header-legacy.tpl with per-file copyright, replace if different
      $headerLegacy = trim($smarty->fetch('header-php-legacy.tpl'));
      if (trim($topComment) !== $headerLegacy) {
        $content = str_replace($topComment, $headerLegacy, $content);
      }
    }
    elseif ($hasAgpl && $hasNetiCrm && !$hasCiviCrm) {
      // Already has a netiCRM-only header (headerNew) - re-render and update if outdated
      if (trim($topComment) !== $headerNew) {
        $content = str_replace($topComment, $headerNew, $content);
      }
    }
  }
  else {
    // No license block - insert header.tpl
    $content = preg_replace('/^<\?php/', "<?php\n" . $headerNew, $content);
  }

  // --- Step B: Handle PHPDoc @copyright ---
  // Target only file-level or class-level PHPDoc blocks (not method/function docblocks).
  // Priority 0: /** */ that already contains @copyright — definitively file/class-level.
  // Priority 1: /** */ immediately before a class/interface/trait declaration.
  // Priority 2: /** */ before the first class definition that is not a function docblock.
  // Priority 3: no suitable docblock found — create and insert one.

  // Matches a single PHPDoc block (stops at first */, won't span multiple blocks)
  $docPattern = '\/\*\*(?:[^*]|\*(?!\/))*\*\/';

  $docBlock = NULL;
  $docOffset = NULL;

  // Priority 0: any /** */ that already contains @copyright — definitively file/class-level
  $offset0 = 0;
  while (preg_match('/(' . $docPattern . ')/s', $content, $docMatches, PREG_OFFSET_CAPTURE, $offset0)) {
    if (stripos($docMatches[1][0], '@copyright') !== FALSE) {
      $docBlock  = $docMatches[1][0];
      $docOffset = $docMatches[1][1];
      break;
    }
    $offset0 = $docMatches[1][1] + strlen($docMatches[1][0]);
  }

  // Priority 1: class/interface/trait-level docblock
  if ($docBlock === NULL && preg_match(
    '/(' . $docPattern . ')(\s*(?:(?:abstract|final|readonly)\s+)*(?:class|interface|trait)[ \t{])/s',
    $content,
    $docMatches,
    PREG_OFFSET_CAPTURE
  )) {
    $docBlock = $docMatches[1][0];
    $docOffset = $docMatches[1][1];
  }

  // Priority 2: file-level docblock (before first class/interface/trait, not a function docblock)
  if ($docBlock === NULL) {
    $firstClassPos = PHP_INT_MAX;
    if (preg_match('/\n[ \t]*(?:(?:abstract|final|readonly)\s+)*(?:class|interface|trait)[ \t{]/s', $content, $m, PREG_OFFSET_CAPTURE)) {
      $firstClassPos = $m[0][1];
    }
    $searchContent = $firstClassPos < PHP_INT_MAX ? substr($content, 0, $firstClassPos) : $content;

    $searchOffset = 0;
    while (preg_match('/(' . $docPattern . ')/s', $searchContent, $docMatches, PREG_OFFSET_CAPTURE, $searchOffset)) {
      $candidateBlock  = $docMatches[1][0];
      $candidateOffset = $docMatches[1][1];
      $after = substr($searchContent, $candidateOffset + strlen($candidateBlock));
      // Skip if immediately followed by a function declaration
      if (!preg_match('/^\s*(?:(?:abstract|final|readonly|static|public|protected|private)\s+)*function\b/s', $after)) {
        $docBlock  = $candidateBlock;
        $docOffset = $candidateOffset;
        break;
      }
      $searchOffset = $candidateOffset + strlen($candidateBlock);
    }
  }

  // Priority 3: no file/class-level docblock found — insert a new one
  if ($docBlock === NULL) {
    $basename    = basename($filePath, '.php');
    $newDocBlock = "/**\n * {$basename}\n *\n" . $netivismCopyright . "\n */";
    // Insert immediately before the first class/interface/trait/function definition
    if (preg_match('/(\n)([ \t]*(?:(?:abstract|final|readonly)\s+)*(?:class|interface|trait|function)[ \t])/s', $content, $m, PREG_OFFSET_CAPTURE)) {
      $content = substr_replace($content, "\n" . $newDocBlock, $m[0][1], 0);
    }
    else {
      $content = rtrim($content) . "\n" . $newDocBlock . "\n";
    }
  }
  else {
    // Add @copyright to the found docblock if not already present
    // (Check within the docblock itself — Step A may have already inserted neticrm.tw/about/licensing
    // in the license block above)
    if (stripos($docBlock, 'neticrm.tw/about/licensing') === FALSE) {
      $newDocBlock = $docBlock;
      if (stripos($docBlock, '@copyright CiviCRM LLC') !== FALSE) {
        // Insert NETivism copyright on the line before @copyright CiviCRM LLC
        $newDocBlock = preg_replace(
          '/(\n\s*\*\s*@copyright CiviCRM LLC)/i',
          "\n" . $netivismCopyright . '$1',
          $docBlock
        );
      }
      elseif (stripos($docBlock, '@copyright') === FALSE) {
        // No @copyright at all - append NETivism copyright before the closing */
        $newDocBlock = preg_replace(
          '/(\n\s*\*\/)$/',
          "\n" . $netivismCopyright . '$1',
          $docBlock
        );
      }
      if ($newDocBlock !== $docBlock) {
        $content = substr_replace($content, $newDocBlock, $docOffset, strlen($docBlock));
      }
    }
  }

  // --- Step C: Save or diff ---
  if ($content !== $originalContent) {
    if ($dryRun) {
      echo unifiedDiff($content, $filePath);
    }
    else {
      file_put_contents($filePath, $content);
      echo "Updated: {$filePath}\n";
    }
  }
}

echo "\nDone!\n";
