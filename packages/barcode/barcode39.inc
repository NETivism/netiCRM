<?php
/**
 * Barcode39 - Code 39 Barcode Image Generator
 * 
 * @package Barcode39
 * @category Barcode39
 * @name Barcode39
 * @version 1.0
 * @author Shay Anderson 05.11
 * @link http://www.shayanderson.com/php/php-barcode-generator-class-code-39.htm
 * @license http://www.gnu.org/licenses/gpl.html GPL License
 * This is free software and is distributed WITHOUT ANY WARRANTY
 */
final class Barcode39 {
	/**
	 * Code 39 format 2 specifications
	 */
	const f2B = "11";
	const f2W = "00";
	const f2b = "10";
	const f2w = "01";

	/**
	 * Barcode code
	 *
	 * @var array $_code
	 */
	private $_code = array();

	/**
	 * Code 39 matrix
	 *
	 * @var array $_codes_39
	 */
	private $_codes_39 = array(
		32 => 100011011001110110,
		36 => 100010001000100110,
		37 => 100110001000100010,
		42 => 100010011101110110,
		43 => 100010011000100010,
		45 => 100010011001110111,
		46 => 110010011001110110,
		47 => 100010001001100010,
		48 => 100110001101110110,
		49 => 110110001001100111,
		50 => 100111001001100111,
		51 => 110111001001100110,
		52 => 100110001101100111,
		53 => 110110001101100110,
		54 => 100111001101100110,
		55 => 100110001001110111,
		56 => 110110001001110110,
		57 => 100111001001110110,
		65 => 110110011000100111,
		66 => 100111011000100111,
		67 => 110111011000100110,
		68 => 100110011100100111,
		69 => 110110011100100110,
		70 => 100111011100100110,
		71 => 100110011000110111,
		72 => 110110011000110110,
		73 => 100111011000110110,
		74 => 100110011100110110,
		75 => 110110011001100011,
		76 => 100111011001100011,
		77 => 110111011001100010,
		78 => 100110011101100011,
		79 => 110110011101100010,
		80 => 100111011101100010,
		81 => 100110011001110011,
		82 => 110110011001110010,
		83 => 100111011001110010,
		84 => 100110011101110010,
		85 => 110010011001100111,
		86 => 100011011001100111,
		87 => 110011011001100110,
		88 => 100010011101100111,
		89 => 110010011101100110,
		90 => 100011011101100110
	);

	/**
	 * Width of wide bars in barcode (should be 3:1)
	 *
	 * @var int $barcode_bar_thick
	 */
	public $barcode_bar_thick = 3;

	/**
	 * Width of thin bars in barcode (should be 3:1)
	 *
	 * @var int $barcode_bar_thin
	 */
	public $barcode_bar_thin = 1;

	/**
	 * Barcode background color (RGB)
	 *
	 * @var array $barcode_bg_rgb
	 */
	public $barcode_bg_rgb = array(255, 255, 255);

	/**
	 * Barcode height
	 *
	 * @var int $barcode_height
	 */
	public $barcode_height = 80;

	/**
	 * Barcode padding
	 *
	 * @var int $barcode_padding
	 */
	public $barcode_padding = 5;

	/**
	 * Use barcode text flag
	 *
	 * @var bool $barcode_text
	 */
	public $barcode_text = true;

	/**
	 * Barcode text size
	 *
	 * @var int $barcode_text_size
	 */
	public $barcode_text_size = 3;

	/**
	 * Use dynamic barcode width (will auto set width)
	 *
	 * @var bool $barcode_use_dynamic_width
	 */
	public $barcode_use_dynamic_width = true;

	/**
	 * Barcode width (if not using dynamic width)
	 *
	 * @var int $barcode_width
	 */
	public $barcode_width = 400;

	/**
	 * Set and format params
	 *
	 * @param string $code
	 */
	public function  __construct($code = null) {
		// format and code
		$code = (string)strtoupper($code);

		// convert code to code array
		$i = 0;
		while(isset($code[$i])) {
			$this->_code[] = $code[$i++];
		}

		// add start and stop symbols
		array_unshift($this->_code, "*");
		array_push($this->_code, "*");
	}

	/**
	 * Draw barcode (and save as file if filename set)
	 *
	 * @param string $filename (optional)
	 * @return bool
	 */
	public function draw($filename = null) {
		// check if GB library functions installed
		if(!function_exists("imagegif")) {
			return false;
		}

		// check for valid code
		if(!is_array($this->_code) || !count($this->_code)) {
			return false;
		}

		// bars coordinates and params
		$bars = array();

		// position pointer
		$pos = $this->barcode_padding;

		// barcode text
		$barcode_string = null;

		// set code 39 codes
		$i = 0;
		foreach($this->_code as $k => $v) {
			// check for valid code
			if(isset($this->_codes_39[ord($v)])) {
				// valid code add code 39, also add separator between characters if not first character
				$code = ( $i ? self::f2w : null ) . $this->_codes_39[ord($v)];

				// check for valid code 39 code
				if($code) {
					// add to barcode text
					$barcode_string .= " {$v}";

					// init params
					$w = 0;
					$f2 = $fill = null;

					// add each bar coordinates and params
					for($j = 0; $j < strlen($code); $j++) {
						// format 2 code
						$f2 .= (string)$code[$j];

						// valid format 2 code
						if(strlen($f2) == 2) {
							// set bar fill
							$fill = $f2 == self::f2B || $f2 == self::f2b ? "_000" : "_fff";

							// set bar width
							$w = $f2 == self::f2B || $f2 == self::f2W ? $this->barcode_bar_thick : $this->barcode_bar_thin;

							// check for valid bar params
							if($w && $fill) {
								// add bar coordinates and params
								$bars[] = array($pos, $this->barcode_padding, $pos - 1 + $w,
									$this->barcode_height - $this->barcode_padding - 1, $fill);

								// move position pointer
								$pos += $w;
							}

							// reset params
							$f2 = $fill = null;
							$w = 0;
						}
					}
				}
				$i++;
			// invalid code, remove character from code
			} else {
				unset($this->_code[$k]);
			}
		}

		// check for valid bar coordinates and params
		if(!count($bars)) {
			// no valid bar coordinates and params
			return false;
		}

		// set barcode width
		$bc_w = $this->barcode_use_dynamic_width ? $pos + $this->barcode_padding : $this->barcode_width;

		// if not dynamic width check if barcode wider than barcode image width
		if(!$this->barcode_use_dynamic_width && $pos > $this->barcode_width) {
			return false;
		}

		// initialize image
		$img = imagecreate($bc_w, $this->barcode_height);
		$_000 = imagecolorallocate($img, 0, 0, 0);
		$_fff = imagecolorallocate($img, 255, 255, 255);
		$_bg = imagecolorallocate($img, $this->barcode_bg_rgb[0], $this->barcode_bg_rgb[1], $this->barcode_bg_rgb[2]);

		// fill background
		imagefilledrectangle($img, 0, 0, $bc_w, $this->barcode_height, $_bg);

		// add bars to barcode
		for($i = 0; $i < count($bars); $i++) {
			imagefilledrectangle($img, $bars[$i][0], $bars[$i][1], $bars[$i][2], $bars[$i][3], $$bars[$i][4]);
		}

		// check if using barcode text
		if($this->barcode_text) {
			// set barcode text box
			$barcode_text_h = 10 + $this->barcode_padding;
			imagefilledrectangle($img, $this->barcode_padding, $this->barcode_height - $this->barcode_padding - $barcode_text_h,
				$bc_w - $this->barcode_padding, $this->barcode_height - $this->barcode_padding, $_fff);

			// set barcode text font params
			$font_size = $this->barcode_text_size;
			$font_w = imagefontwidth($font_size);
			$font_h = imagefontheight($font_size);

			// set text position
			$txt_w = $font_w * strlen($barcode_string);
			$pos_center = ceil((($bc_w - $this->barcode_padding) - $txt_w) / 2);

			// set text color
			$txt_color = imagecolorallocate($img, 0, 255, 255);

			// draw barcod text
			imagestring($img, $font_size, $pos_center, $this->barcode_height - $barcode_text_h - 2,
				$barcode_string, $_000);
		}

		// check if writing image
		if($filename) {
			imagegif($img, $filename);
		// display image
		} else {
			header("Content-type: image/gif");
			imagegif($img);
		}
		
		imagedestroy($img);

		// valid barcode
		return true;
	}
}
?>