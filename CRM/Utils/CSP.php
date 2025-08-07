<?php

/**
 * CRM_Utils_CSP - Class for Content Security Policy management.
 *
 * This class provides methods to validate and output Content Security Policies (CSP) for web pages.
 */
class CRM_Utils_CSP {

  /**
   * CSP allowed formats defined in constant
   */
  const
    // Directive names
    DIRECTIVES = [
      'DIR_DEFAULT' => "default-src",
      'DIR_CHILD' => "child-src",
      'DIR_CONNECT' => "connect-src",
      'DIR_FONT' => "font-src",
      'DIR_FRAME' => "frame-src",
      'DIR_IMG' => "img-src",
      'DIR_MANIFEST' => "manifest-src",
      'DIR_MEDIA' => "media-src",
      'DIR_OBJECT' => "object-src",
      'DIR_PREFETCH' => "prefetch-src",
      'DIR_SCRIPT' => "script-src",
      'DIR_SCRIPT_ELEM' => "script-src-elem",
      'DIR_SCRIPT_ATTR' => "script-src-attr",
      'DIR_STYLE' => "style-src",
      'DIR_STYLE_ELEM' => "style-src-elem",
      'DIR_STYLE_ATTR' => "style-src-attr",
      'DIR_WORKER' => "worker-src",

      // Document directives
      'DIR_BASE_URI' => "base-uri",

      // Navigation directives
      'DIR_NAVIGATE_TO' => "navigate-to",
      'DIR_FORM_ACTION' => "form-action",

      // Special directives
      'DIR_REPORT' => "report-to",
      'DIR_SANDBOX' => "sandbox",
    ],

    // SOURCES
    SOURCES = [
      'VAL_NONE' => "'none'",
      'VAL_ANY' => "*",
      'VAL_SELF' => "'self'",
      'VAL_UNSAFE_INLINE' => "'unsafe-inline'",
      'VAL_UNSAFE_EVAL' => "'unsafe-eval'",
      'VAL_WASM_UNSAFE_EVIL' => "'wasm-unsafe-eval'",
      'VAL_UNSAFE_HASHES' => "'unsafe-hashes'",

      // Strip dynamic
      'VAL_STRIP_DYNAMIC' => "'strict-dynamic'",

      // Report sample
      'VAL_REPORT_SAMPLE' => "'report-sample'",

      // Nonce
      'VAL_NONCE_PRFX' => "nonce",

      // Hash
      'VAL_HASH_SHA256' => "sha256",
      'VAL_HASH_SHA384' => "sha384",
      'VAL_HASH_SHA512' => "sha512",

      // Scheme
      'VAL_SCHEME_DATA' => 'data:',
      'VAL_SCHEME_MS' => 'mediastream:',
      'VAL_SCHEME_BLOB' => 'blob:',
      'VAL_SCHEME_FS' => 'filesystem:',
    ];


  /**
   * Parsed Policies for CSP
   *
   * @var array
   */
  public $policies = [];

  /**
   * Constructor function that takes an encoded policy as input and parses it into an associative array.
   *
   * @param string $encPolicy Encoded policy string to be parsed
   *
   * @return object Returns the current object instance
   */
  function __construct($encPolicy) {
    $rawDirectives = explode(";", $encPolicy);

    foreach($rawDirectives as $rawDirective) {
      $parts = array_map('trim', explode(" ", trim($rawDirective), 2));

      $name = $this->parseName($parts[0]);
      if (empty($name)) {
        continue;
      }

      if (array_key_exists($name, $this->policies)) {
        continue;
      }
      if (count($parts) == 1) {
        $this->policies[$name] = [];
        continue;
      }
      $sourceList = $this->parseSourceList($parts[1]);
      $this->policies[$name] = $sourceList;
    }
    return $this;
  }

  /**
   * Convert the object to a string
   *
   * @return string
   */
  public function __toString() {
    if (empty($this->policies)) {
      return '';
    }
    $policies = [];
    foreach($this->policies as $directive => $sources) {
      array_unshift($sources, $directive);
      $policies[] = implode(' ', $sources);
    }
    return implode('; ', $policies);
  }

  /**
   * Parse the name of a directive
   *
   * @param string $dir The directive to parse
   * @return string The parsed directive name or an empty string if not found
   */
  private function parseName($dir) {
    $dir = strtolower($dir);
    if (in_array($dir, self::DIRECTIVES)) {
      return $dir;
    }
    return '';
  }

  /**
   * Parse a source list string
   *
   * @param string $val The source list string to parse
   *
   * @return array An array of parsed sources or an empty array if none found
   */
  private function parseSourceList($val) {
    $sl = array_map('trim', explode(" ", trim($val)));
    if (count($sl) == 0) {
      return [];
    }

    $sources = [];
    foreach($sl as $sle) {
      $val = strtolower(trim($sle));
      if (in_array($val, self::SOURCES)) {
        $sources[] = $val;
      }
      else {
        switch (true) {
          case strpos($val, self::SOURCES['VAL_NONCE_PRFX']) === 0:
          case strpos($val, self::SOURCES['VAL_HASH_SHA256']) === 0:
          case strpos($val, self::SOURCES['VAL_HASH_SHA384']) === 0:
          case strpos($val, self::SOURCES['VAL_HASH_SHA512']) === 0:
          case strpos($val, self::SOURCES['VAL_SCHEME_DATA']) === 0:
          case strpos($val, self::SOURCES['VAL_SCHEME_MS']) === 0:
          case strpos($val, self::SOURCES['VAL_SCHEME_BLOB']) === 0:
          case strpos($val, self::SOURCES['VAL_SCHEME_FS']) === 0:
            $sources[] = $val;
            break;
          default:
            // host source
            if (in_array($val, ['http:', 'https:', 'ws:'])) {
              $sources[] = $val;
            }
            elseif (preg_match('/^((http|https|ws):\/\/)?([^:\/]+)([:]\d+)?(\/.*)?$/', $val)) {
              $sources[] = $val;
            }
        }
      }
    }
    return $sources;
  }

}
