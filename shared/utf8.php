<?php
/**
 * encode and decode utf-8 strings
 *
 * @link http://www.ietf.org/rfc/rfc2279.txt UTF-8, a transformation format of ISO 10646
 * @link http://www.randomchaos.com/document.php?source=php_and_unicode How to develop multilingual, Unicode applications with PHP
 * @link http://www.cs.tut.fi/~jkorpela/chars.html#typing A tutorial on character code issues
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Utf8 {

	/**
	 * transcode HTML and ISO8859 to UTF-8
	 *
	 * @param string the original string
	 * @return a UTF-8 encoded string
	 */
	function &encode(&$input) {

		// transcode explicit unicode entities %u2019 -> &#8217;
		$output = preg_replace('/%u([0-9a-z]{4})/ise', "'&#'.hexdec('$1').';'", $input);

		// transcode HTML entities to Unicode entities
		$output =& utf8::transcode($output);

		// translate everything, including ISO8859-1 chars, to utf-8
// 		$output = utf8_encode($output);

		// return the translated string
		return $output;

	}

	/**
	 * transcode from ISO 8859
	 *
	 * @param string a complex string using ISO8859 entities
	 * @return a UTF-8 string
	 */
	function &from_iso8859($text) {

		// iso-8859-15 + Microsoft extensions cp1252
		list($iso_entities, $unicode_entities) = Utf8::get_iso8859();

		// transcode ISO8859 to Unicode entities
		$text = str_replace($iso_entities, $unicode_entities, $text);

		// done
		return $text;
	}

	/**
	 * restore UTF-8 from HTML Unicode entities
	 *
	 * This function is triggered by the YACS handler during page rendering.
	 * It is aiming to transcode HTML Unicode entities (eg, &amp;#8364;) back to actual UTF-8 encoding (eg, €).
	 *
	 * @param string a string with a mix of UTF-8 and of HTML Unicode entities
	 * @return an UTF-8 string
	 */
	function &from_unicode($text) {
		global $context;

		// translate Unicode entities
		$areas = preg_split('/&[#u](\d+);/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		$text = '';
		$index = 0;
		foreach($areas as $area) {
			switch($index%2) {
			case 0: // before entity
				$text .= $area;
				break;
			case 1: // the entity itself

				// get the integer value
				$unicode = intval($area);

				// one byte
				if($unicode < 0x7F) {

					$text .= chr($unicode);

				// forbidden elements
				} elseif($unicode < 0x0A0) {
					;

				// two bytes
				} elseif($unicode < 0x800) {

					$text .= chr( 0xC0 +  ( ( $unicode - ( $unicode % 0x40 ) ) / 0x40 ) );
					$text .= chr( 0x80 + ( $unicode % 0x40 ) );

				// three bytes
				} elseif($unicode < 0x10000) {

					$text .= chr( 0xE0 + ( ( $unicode - ( $unicode % 0x1000 ) ) / 0x1000 ) );
					$text .= chr( 0x80 + ( ( ( $unicode % 0x1000 ) - ( $unicode % 0x40 ) ) / 0x40 ) );
					$text .= chr( 0x80 + ( $unicode % 0x40 ) );

				// more bytes, keep it as it is...
				} else
					$text .= '&#'.$unicode.';';

				break;
			}
			$index++;
		}

		// the updated string
		return $text;
	}

	/**
	 * get ISO 8859 transcoding table
	 *
	 * Actually, the iso-8859-15 encoding table, extended with windows-1252 for control chars
	 *
	 * @link http://www.unicode.org/Public/MAPPINGS/ISO8859/8859-15.TXT
	 * @link http://www.unicode.org/Public/MAPPINGS/VENDORS/MICSFT/WINDOWS/CP1252.TXT
	 *
	 * @return ISO 8859 transcoding arrays
	 */
	function get_iso8859() {

		// iso-8859-15 + Microsoft extensions cp1252 -- initialize tables only once
		static $iso_entities, $unicode_entities;
		if(!is_array($iso_entities)) {

			// numerical order
			$codes = array(
				// the 27 differeces from windows-1252 to iso-8859-1
				"\x80"	=> '&#x20AC;',	// &euro;
//				"\x81"	=> '',	// 
				"\x82"	=> '&#x201A;',	// &sbquo;
				"\x83"	=> '&#x192;',	// &fnof;
				"\x84"	=> '&#x201E;',	// &bdquo;
				"\x85"	=> '&#x2026;',	// &hellip;
				"\x86"	=> '&#x2020;',	// &dagger;
				"\x87"	=> '&#x2021;',	// &Dagger;
				"\x88"	=> '&#x2C6;',	// &circ;
				"\x89"	=> '&#x2030;',	// &permil;
				"\x8A"	=> '&#x160;',	// &Scaron;
				"\x8B"	=> '&#x2039;',	// &lsaquo;
				"\x8C"	=> '&#x152;',	// &OElig;
//				"\x8D"	=> '',	// 
				"\x8D"	=> '&#x17D;',	// &#x17D;
//				"\x8F"	=> '',	// 
//				"\x90"	=> '',	// 
				"\x91"	=> '&#x2018;',	// &lsquo;
				"\x92"	=> '&#x2019;',	// &rsquo;
				"\x93"	=> '&#x201C;',	// &ldquo;
				"\x94"	=> '&#x201D;',	// &rdquo;
				"\x95"	=> '&#x2022;',	// &bull;
				"\x96"	=> '&#x2013;',	// &ndash;
				"\x97"	=> '&#x2014;',	// &mdash;
				"\x98"	=> '&#x2DC;',	// &tilde;
				"\x99"	=> '&#x2122;',	// &trade;
				"\x9A"	=> '&#x161;',	// &scaron;
				"\x9B"	=> '&#x203A;',	// &rsaquo;
				"\x9C"	=> '&#x153;',	// &oelig;
//				"\x9D"	=> '',	// 
				"\x9E"	=> '&#x17E;',	// &#x17E;
				"\x9F"	=> '&#x178;',	// &Yuml;
				
				"\xA0"	=> '&#160;',	// non-breaking space
				"\xA1"	=> '&#161;',	// inverted exclamation mark
				"\xA2"	=> '&#162;',	// cent sign
				"\xA3"	=> '&#163;',	// pound sign
				"\xA4"	=> '&#8364;',	// EURO SIGN
				"\xA5"	=> '&#165;',	// yen sign
				"\xA6"	=> '&#352;',	// LATIN CAPITAL LETTER S WITH CARON
				"\xA7"	=> '&#167;',	// section sign
				"\xA8"	=> '&#353;',	// LATIN SMALL LETTER S WITH CARON
				"\xA9"	=> '&#169;',	// copyright sign
				"\xAA"	=> '&#170;',	// feminine ordinal indicator
				"\xAB"	=> '&#171;',	// left-pointing double angle quotation mark
				"\xAC"	=> '&#172;',	// not sign
				"\xAD"	=> '&#173;',	// soft hyphen
				"\xAE"	=> '&#174;',	// registered sign
				"\xAF"	=> '&#175;',	// macron
				"\xB0"	=> '&#176;',	// degree sign
				"\xB1"	=> '&#177;',	// plus-minus sign
				"\xB2"	=> '&#178;',	// superscript two
				"\xB3"	=> '&#179;',	// superscript three
				"\xB4"	=> '&#381;',	// LATIN CAPITAL LETTER Z WITH CARON
				"\xB5"	=> '&#181;',	// micro sign
				"\xB6"	=> '&#182;',	// pilcrow sign
				"\xB7"	=> '&#183;',	// middle dot
				"\xB8"	=> '&#382;',	// LATIN SMALL LETTER Z WITH CARON
				"\xB9"	=> '&#185;',	// superscript one
				"\xBA"	=> '&#186;',	// masculine ordinal indicator
				"\xBB"	=> '&#187;',	// right-pointing double angle quotation mark
				"\xBC"	=> '&#338;',	// LATIN CAPITAL LIGATURE OE
				"\xBD"	=> '&#339;',	// LATIN SMALL LIGATURE OE
				"\xBE"	=> '&#376;',	// LATIN CAPITAL LETTER Y WITH DIAERESIS
				"\xBF"	=> '&#191;',	// inverted question mark
				"\xC0"	=> '&#192;',	// latin capital letter A with grave
				"\xC1"	=> '&#193;',	// latin capital letter A with acute
				"\xC2"	=> '&#194;',	// latin capital letter A with circumflex
				"\xC3"	=> '&#195;',	// latin capital letter A with tilde
				"\xC4"	=> '&#196;',	// latin capital letter A with diaeresis
				"\xC5"	=> '&#197;',	// latin capital letter A with ring above
				"\xC6"	=> '&#198;',	// latin capital letter AE
				"\xC7"	=> '&#199;',	// latin capital letter C with cedilla
				"\xC8"	=> '&#200;',	// latin capital letter E with grave
				"\xC9"	=> '&#201;',	// latin capital letter E with acute
				"\xCA"	=> '&#202;',	// latin capital letter E with circumflex
				"\xCB"	=> '&#203;',	// latin capital letter E with diaeresis
				"\xCC"	=> '&#204;',	// latin capital letter I with grave
				"\xCD"	=> '&#205;',	// latin capital letter I with acute
				"\xCE"	=> '&#206;',	// latin capital letter I with circumflex
				"\xCF"	=> '&#207;',	// latin capital letter I with diaeresis
				"\xD0"	=> '&#208;',	// latin capital letter ETH
				"\xD1"	=> '&#209;',	// latin capital letter N with tilde
				"\xD2"	=> '&#210;',	// latin capital letter O with grave
				"\xD3"	=> '&#211;',	// latin capital letter O with acute
				"\xD4"	=> '&#212;',	// latin capital letter O with circumflex
				"\xD5"	=> '&#213;',	// latin capital letter O with tilde
				"\xD6"	=> '&#214;',	// latin capital letter O with diaeresis
				"\xD7"	=> '&#215;',	// multiplication sign
				"\xD8"	=> '&#216;',	// latin capital letter O with stroke
				"\xD9"	=> '&#217;',	// latin capital letter U with grave
				"\xDA"	=> '&#218;',	// latin capital letter U with acute
				"\xDB"	=> '&#219;',	// latin capital letter U with circumflex
				"\xDC"	=> '&#220;',	// latin capital letter U with diaeresis
				"\xDD"	=> '&#221;',	// latin capital letter Y with acute
				"\xDE"	=> '&#222;',	// latin capital letter THORN
				"\xDF"	=> '&#223;',	// latin small letter sharp s
				"\xE0"	=> '&#224;',	// latin small letter a with grave
				"\xE1"	=> '&#225;',	// latin small letter a with acute
				"\xE2"	=> '&#226;',	// latin small letter a with circumflex
				"\xE3"	=> '&#227;',	// latin small letter a with tilde
				"\xE4"	=> '&#228;',	// latin small letter a with diaeresis
				"\xE5"	=> '&#229;',	// latin small letter a with ring above
				"\xE6"	=> '&#230;',	// latin small letter ae
				"\xE7"	=> '&#231;',	// latin small letter c with cedilla
				"\xE8"	=> '&#232;',	// latin small letter e with grave
				"\xE9"	=> '&#233;',	// latin small letter e with acute
				"\xEA"	=> '&#234;',	// latin small letter e with circumflex
				"\xEB"	=> '&#235;',	// latin small letter e with diaeresis
				"\xEC"	=> '&#236;',	// latin small letter i with grave
				"\xED"	=> '&#237;',	// latin small letter i with acute
				"\xEE"	=> '&#238;',	// latin small letter i with circumflex
				"\xEF"	=> '&#239;',	// latin small letter i with diaeresis
				"\xF0"	=> '&#240;',	// latin small letter eth
				"\xF1"	=> '&#241;',	// latin small letter n with tilde
				"\xF2"	=> '&#242;',	// latin small letter o with grave
				"\xF3"	=> '&#243;',	// latin small letter o with acute
				"\xF4"	=> '&#244;',	// latin small letter o with circumflex
				"\xF5"	=> '&#245;',	// latin small letter o with tilde
				"\xF6"	=> '&#246;',	// latin small letter o with diaeresis
				"\xF7"	=> '&#247;',	// division sign
				"\xF8"	=> '&#248;',	// latin small letter o with stroke
				"\xF9"	=> '&#249;',	// latin small letter u with grave
				"\xFA"	=> '&#250;',	// latin small letter u with acute
				"\xFB"	=> '&#251;',	// latin small letter u with circumflex
				"\xFC"	=> '&#252;',	// latin small letter u with diaeresis
				"\xFD"	=> '&#253;',	// latin small letter y with acute
				"\xFE"	=> '&#254;',	// latin small letter thorn
				"\xFF"	=> '&#255;' 	// latin small letter y with diaeresis
				);

			// split entities for use in str_replace()
			foreach($codes as  $iso_entity => $unicode_entity) {
				$iso_entities[] = $iso_entity;
				$unicode_entities[] = $unicode_entity;
			}
		}

		// here are the tables
		return array($iso_entities, $unicode_entities);
	}

	/**
	 * transcode to US ASCII
	 *
	 * This function is primarily used to build strings matching RFC 2183 and RFC 822
	 * requirements on MIME data.
	 *
	 * You should use it to build valid file names for downloads.
	 * According to the RFC:
	 * &quot;Current [RFC 2045] grammar restricts parameter values (and hence
	 *	Content-Disposition filenames) to US-ASCII.&quot;
	 *
	 * @link http://www.ietf.org/rfc/rfc2183.txt The Content-Disposition Header Field
	 *
	 * For example:
	 * [php]
	 * // get a valid file name
	 * $file_name = utf8::to_ascii($context['page_title'].'.xml');
	 *
	 * // suggest a download
	 * Safe::header('Content-Disposition: attachment; filename="'.$file_name.'"');
	 * [/php]
	 *
	 * This function can also be used to enforce the ASCII character set in texts.
	 * For this kind of usage it is recommended to add spaces to the optional parameters, like in:
	 * [php]
	 * // enforce ascii
	 * $text = utf8::to_ascii($text, ' ');
	 * [/php]
	 *
	 * @param string a complex string using HTML entities
	 * @param string optional characters to accept
	 * @return a US-ASCII string
	 *
	 * @see articles/export.php
	 * @see articles/fetch_as_msword.php
	 * @see articles/fetch_as_pdf.php
	 * @see articles/ie_bookmarklet.php
	 * @see files/edit.php
	 * @see files/fetch.php
	 * @see files/fetch_all.php
	 * @see images/edit.php
	 * @see tables/fetch_as_csv.php
	 * @see tables/fetch_as_xml.php
	 * @see users/fetch_vcard.php
	 */
	function &to_ascii($utf, $options='') {

		// http://jeppesn.dk/utf-8.html -- initialize tables only once
		static $utf_entities, $safe_entities;
		if(!is_array($utf_entities)) {

			// numerical order
			$codes = array(
				"\xC3\x80"	=> 'A',
				"\xC3\x81"	=> 'A',
				"\xC3\x82"	=> 'A',
				"\xC3\x83"	=> 'A',
				"\xC3\x84"	=> 'A',
				"\xC3\x85"	=> 'A',
				"\xC3\x86"	=> 'AE',
				"\xC3\x87"	=> 'C',
				"\xC3\x88"	=> 'E',
				"\xC3\x89"	=> 'E',
				"\xC3\x8A"	=> 'E',
				"\xC3\x8B"	=> 'E',
				"\xC3\x8C"	=> 'I',
				"\xC3\x8D"	=> 'I',
				"\xC3\x8E"	=> 'I',
				"\xC3\x8F"	=> 'I',

				"\xC3\x90"	=> 'D',
				"\xC3\x91"	=> 'N',
				"\xC3\x92"	=> 'O',
				"\xC3\x93"	=> 'O',
				"\xC3\x94"	=> 'O',
				"\xC3\x95"	=> 'O',
				"\xC3\x96"	=> 'O',
				"\xC3\x97"	=> 'x',
				"\xC3\x98"	=> 'O',
				"\xC3\x99"	=> 'U',
				"\xC3\x9A"	=> 'U',
				"\xC3\x9B"	=> 'U',
				"\xC3\x9C"	=> 'U',
				"\xC3\x9D"	=> 'Y',
				"\xC3\x9E"	=> '',
				"\xC3\x9F"	=> '',

				"\xC3\xA0"	=> 'a',
				"\xC3\xA1"	=> 'a',
				"\xC3\xA2"	=> 'a',
				"\xC3\xA3"	=> 'a',
				"\xC3\xA4"	=> 'a',
				"\xC3\xA5"	=> 'a',
				"\xC3\xA6"	=> 'ae',
				"\xC3\xA7"	=> 'c',
				"\xC3\xA8"	=> 'e',
				"\xC3\xA9"	=> 'e',
				"\xC3\xAA"	=> 'e',
				"\xC3\xAB"	=> 'e',
				"\xC3\xAC"	=> 'i',
				"\xC3\xAD"	=> 'i',
				"\xC3\xAE"	=> 'i',
				"\xC3\xAF"	=> 'i',

				"\xC3\xB0"	=> 'o',
				"\xC3\xB1"	=> 'n',
				"\xC3\xB2"	=> 'o',
				"\xC3\xB3"	=> 'o',
				"\xC3\xB4"	=> 'o',
				"\xC3\xB5"	=> 'o',
				"\xC3\xB6"	=> 'o',
				"\xC3\xB7"	=> '',
				"\xC3\xB8"	=> '',
				"\xC3\xB9"	=> 'u',
				"\xC3\xBA"	=> 'u',
				"\xC3\xBB"	=> 'u',
				"\xC3\xBC"	=> 'u',
				"\xC3\xBD"	=> 'y',
				"\xC3\xBE"	=> '',
				"\xC3\xBF"	=> ''

				);

			// split entities for use in str_replace()
			foreach($codes as  $utf_entity => $safe_entity) {
				$utf_entities[] = $utf_entity;
				$safe_entities[] = $safe_entity;
			}
		}

		// transcode iso 8859 chars to safer ascii entities
		$text = str_replace($utf_entities, $safe_entities, $utf);

		// turn invalid chars to dashes (for proper indexation by Google)
		$text = preg_replace("/[^a-zA-Z_\d\.".preg_quote($options)."]+/i", '-', $text);

		// compact dashes
		$text = preg_replace('/-+/', '-', $text);

		// done
		return $text;
	}

	/**
	 * transcode Unicode entities from decimal to hex
	 *
	 * This function is used in specific occasions, for example for better support
	 * of Freemind Flash viewer.
	 *
	 * @param string a complex string using unicode entities
	 * @return a transcoded string
	 */
	function &to_hex($utf) {
		global $context;

		// transcode all entities from decimal to hexa
		$text = preg_replace('/&#([0-9]+);/se', "'&#x'.dechex('\\1').';'", $utf);

		// job done
		return $text;
	}

	/**
	 * transcode to ISO 8859
	 *
	 * To be used only when there is no other alternative.
	 *
	 * @link http://www.unicode.org/Public/MAPPINGS/ISO8859/8859-15.TXT ISO/IEC 8859-15:1999 to Unicode
	 *
	 * @param string a complex string using unicode entities
	 * @param string optional characters to accept
	 * @return a ISO 8859 string
	 *
	 * @see feeds/flash/slashdot.php
	 */
	function &to_iso8859(&$utf, $options='') {

		// iso-8859-15 + Microsoft extensions cp1252
		list($iso_entities, $unicode_entities) = Utf8::get_iso8859();

		// transcode Unicode entities to iso 8859
		$text = str_replace($unicode_entities, $iso_entities, $utf);

		// translate only 1-byte entities
		$areas = preg_split('/&#(\d+);/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		$text = '';
		$index = 0;
		foreach($areas as $area) {
			switch($index%2) {
			case 0: // before entity
				$text .= $area;
				break;
			case 1: // the entity itself

				// get the integer value
				$unicode = intval($area);

				// one ASCII byte
				if($unicode < 0xFF)
					$text .= chr($unicode);

				// two or more bytes
				 else
					$text .= '_';

				break;
			}
			$index++;
		}

		// done
		return $text;
	}

	/**
	 * transcode multi-byte characters to HTML representations for Unicode
	 *
	 * This function is aiming to preserve Unicode characters through storage in a ISO-8859-1 compliant system.
	 *
	 * Every multi-byte UTF-8 character is transformed to its equivalent HTML numerical entity (eg, &amp;#4568;)
	 * that may be handled safely by PHP and by MySQL.
	 *
	 * Of course, this solution does not allow for full-text search in the database and therefore, is not a
	 * definitive solution to internationalization issues.
	 * It does enable, however, practical use of Unicode to build pages in foreign languages.
	 *
	 * Also, this function transforms HTML entities into their equivalent Unicode entities.
	 * For example, w.bloggar posts pages using HTML entities.
	 * If you have to modify these pages using web forms, you would like to get UTF-8 instead.
	 *
	 * @link http://www.evolt.org/article/A_Simple_Character_Entity_Chart/17/21234/ A Simple Character Entity Chart
	 *
	 * @param string the original UTF-8 string
	 * @return a string acceptable in an ISO-8859-1 storage system (ie., PHP4 + MySQL 3)
	 */
	function &to_unicode(&$input) {
		global $context;

		// profiling mode
		if($context['with_profile'] == 'Y')
			logger::profile('utf8::to_unicode', 'start');

		// scan the whole string
		$output = '';
		$index = 0;
		$tick = 0;
		while($index < strlen($input)) {

			// for jumbo pages --observed 167 seconds processing time for 414kbyte input
			$tick++;
			if(!($tick%25000))
				Safe::set_time_limit(30);

			// look at one char
			$char = ord($input[$index]);

			// one byte (0xxxxxxx)
			if($char < 0x80) {

				// some chars may be undefined
				$output .= chr($char);
				$index += 1;

			// two bytes (110xxxxx 10xxxxxx)
			} elseif($char < 0xE0) {

				// strip weird sequences (eg, C0 80 -> NUL)
				if($value = (($char % 0x20) * 0x40) + (ord($input[$index + 1]) % 0x40))
					$output .= '&#' . $value . ';';
				$index += 2;

			// three bytes (1110xxxx 10xxxxxx 10xxxxxx) example: euro sign = \xE2\x82\xAC -> &#8364;
			} elseif($char < 0xF0) {

				// strip weird sequences
				if($value = (($char % 0x10) * 0x1000) + ((ord($input[$index + 1]) % 0x40) * 0x40) + (ord($input[$index + 2]) % 0x40))
					$output .= '&#' . $value . ';';
				$index += 3;

			// four bytes (11110xxx 10xxxxxx 10xxxxxx 10xxxxxx)
			} elseif($char < 0xF8) {

				// strip weird sequences
				if($value = (($char % 0x08) * 0x40000) + ((ord($input[$index + 1]) % 0x40) * 0x1000) + ((ord($input[$index + 2]) % 0x40) * 0x40)
					+ (ord($input[$index + 3]) % 0x40))
					$output .= '&#' . $value . ';';
				$index += 4;

			// five bytes (111110xx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx)
			} elseif($char < 0xFC) {

				// strip weird sequences
				if($value = (($char % 0x04) * 0x1000000) + ((ord($input[$index + 1]) % 0x40) * 0x40000) + ((ord($input[$index + 2]) % 0x40) * 0x1000)
					+ ((ord($input[$index + 3]) % 0x40) * 0x40) + (ord($input[$index + 4]) % 0x40))
					$output .= '&#' . $value . ';';
				$index += 5;

			// six bytes (1111110x 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx)
			} else {

				// strip weird sequences
				if($value = (($char % 0x02) * 0x40000000) + ((ord($input[$index + 1]) % 0x40) * 0x1000000) + ((ord($input[$index + 2]) % 0x40) * 0x40000)
					+ ((ord($input[$index + 3]) % 0x40) * 0x1000) + ((ord($input[$index + 4]) % 0x40) * 0x40) + (ord($input[$index + 4]) % 0x40))
					$output .= '&#' . $value . ';';
				$index += 6;
			}

		}

		// transcode explicit unicode entities %u2019 -> &#8217;
		$output = preg_replace('/%u([0-9a-z]{4})/ise', "'&#'.hexdec('$1').';'", $output);

		// transcode HTML entities to Unicode entities
		$output =& utf8::transcode($output);

		// translate extended ISO8859-1 chars, if any, to utf-8
		$output = utf8_encode($output);

		// profiling mode
		if($context['with_profile'] == 'Y')
			logger::profile('utf8::to_unicode', 'stop');

		// return the translated string
		return $output;

	}

	/**
	 * transcode unicode entities to/from HTML entities
	 *
	 * Also, this function transforms HTML entities into their equivalent Unicode entities.
	 * For example, w.bloggar posts pages using HTML entities.
	 * If you have to modify these pages using web forms, you would like to get UTF-8 instead.
	 *
	 * @link http://www.evolt.org/article/A_Simple_Character_Entity_Chart/17/21234/ A Simple Character Entity Chart
	 *
	 * @param string the string to be transcoded
	 * @param boolean TRUE to transcode to Unicode, FALSE to transcode to HTML
	 * @return a transcoded string
	 */
	function &transcode(&$input, $to_unicode=TRUE) {

		// initialize tables only once
		static $html_entities, $unicode_entities;
		if(!is_array($html_entities)) {

			// numerical order
			$codes = array(
				'&#34;' 	=> '&quot;',	// quotation mark
				'&#160;'	=> '&nbsp;',	// non-breaking space
				'&#161;'	=> '&iexcl;',	// inverted exclamation mark
				'&#162;'	=> '&cent;',	// cent sign
				'&#163;'	=> '&pound;',	// pound sign
				'&#164;'	=> '&curren;',	// currency sign
				'&#165;'	=> '&yen;', 	// yen sign
				'&#166;'	=> '&brvbar;',	// broken bar
				'&#167;'	=> '&sect;',	// section sign
				'&#168;'	=> '&uml;', 	// diaeresis
				'&#169;'	=> '&copy;',	// copyright sign
				'&#170;'	=> '&ordf;',	// feminine ordinal indicator
				'&#171;'	=> '&laquo;',	// left-pointing double angle quotation mark
				'&#172;'	=> '&not;', 	// not sign
				'&#173;'	=> '&shy;', 	// soft hyphen
				'&#174;'	=> '&reg;', 	// registered sign
				'&#175;'	=> '&macr;',	// macron
				'&#176;'	=> '&deg;', 	// degree sign
				'&#177;'	=> '&plusmn;',	// plus-minus sign
				'&#178;'	=> '&sup2;',	// superscript two
				'&#179;'	=> '&sup3;',	// superscript three
				'&#180;'	=> '&acute;',	// acute accent
				'&#181;'	=> '&micro;',	// micro sign
				'&#182;'	=> '&para;',	// pilcrow sign
				'&#183;'	=> '&middot;',	// middle dot
				'&#184;'	=> '&cedil;',	// cedilla
				'&#185;'	=> '&sup1;',	// superscript one
				'&#186;'	=> '&ordm;',	// masculine ordinal indicator
				'&#187;'	=> '&raquo;',	// right-pointing double angle quotation mark
				'&#188;'	=> '&frac14;',	// vulgar fraction one quarter
				'&#189;'	=> '&frac12;',	// vulgar fraction one half
				'&#190;'	=> '&frac34;',	// vulgar fraction three quarters
				'&#191;'	=> '&iquest;',	// inverted question mark
				'&#192;'	=> '&Agrave;',	// latin capital letter A with grave
				'&#193;'	=> '&Aacute;',	// latin capital letter A with acute
				'&#194;'	=> '&Acirc;',	// latin capital letter A with circumflex
				'&#195;'	=> '&Atilde;',	// latin capital letter A with tilde
				'&#196;'	=> '&Auml;',	// latin capital letter A with diaeresis
				'&#197;'	=> '&Aring;',	// latin capital letter A with ring above
				'&#198;'	=> '&AElig;',	// latin capital letter AE
				'&#199;'	=> '&Ccedil;',	// latin capital letter C with cedilla
				'&#200;'	=> '&Egrave;',	// latin capital letter E with grave
				'&#201;'	=> '&Eacute;',	// latin capital letter E with acute
				'&#202;'	=> '&Ecirc;',	// latin capital letter E with circumflex
				'&#203;'	=> '&Euml;',	// latin capital letter E with diaeresis
				'&#204;'	=> '&Igrave;',	// latin capital letter I with grave
				'&#205;'	=> '&Iacute;',	// latin capital letter I with acute
				'&#206;'	=> '&Icirc;',	// latin capital letter I with circumflex
				'&#207;'	=> '&Iuml;',	// latin capital letter I with diaeresis
				'&#208;'	=> '&ETH;', 	// latin capital letter ETH
				'&#209;'	=> '&Ntilde;',	// latin capital letter N with tilde
				'&#210;'	=> '&Ograve;',	// latin capital letter O with grave
				'&#211;'	=> '&Oacute;',	// latin capital letter O with acute
				'&#212;'	=> '&Ocirc;',	// latin capital letter O with circumflex
				'&#213;'	=> '&Otilde;',	// latin capital letter O with tilde
				'&#214;'	=> '&Ouml;',	// latin capital letter O with diaeresis
				'&#215;'	=> '&times;',	// multiplication sign
				'&#216;'	=> '&Oslash;',	// latin capital letter O with stroke
				'&#217;'	=> '&Ugrave;',	// latin capital letter U with grave
				'&#218;'	=> '&Uacute;',	// latin capital letter U with acute
				'&#219;'	=> '&Ucirc;',	// latin capital letter U with circumflex
				'&#220;'	=> '&Uuml;',	// latin capital letter U with diaeresis
				'&#221;'	=> '&Yacute;',	// latin capital letter Y with acute
				'&#222;'	=> '&THORN;',	// latin capital letter THORN
				'&#223;'	=> '&szlig;',	// latin small letter sharp s
				'&#224;'	=> '&agrave;',	// latin small letter a with grave
				'&#225;'	=> '&aacute;',	// latin small letter a with acute
				'&#226;'	=> '&acirc;',	// latin small letter a with circumflex
				'&#227;'	=> '&atilde;',	// latin small letter a with tilde
				'&#228;'	=> '&auml;',	// latin small letter a with diaeresis
				'&#229;'	=> '&aring;',	// latin small letter a with ring above
				'&#230;'	=> '&aelig;',	// latin small letter ae
				'&#231;'	=> '&ccedil;',	// latin small letter c with cedilla
				'&#232;'	=> '&egrave;',	// latin small letter e with grave
				'&#233;'	=> '&eacute;',	// latin small letter e with acute
				'&#234;'	=> '&ecirc;',	// latin small letter e with circumflex
				'&#235;'	=> '&euml;',	// latin small letter e with diaeresis
				'&#236;'	=> '&igrave;',	// latin small letter i with grave
				'&#237;'	=> '&iacute;',	// latin small letter i with acute
				'&#238;'	=> '&icirc;',	// latin small letter i with circumflex
				'&#239;'	=> '&iuml;',	// latin small letter i with diaeresis
				'&#240;'	=> '&eth;', 	// latin small letter eth
				'&#241;'	=> '&ntilde;',	// latin small letter n with tilde
				'&#242;'	=> '&ograve;',	// latin small letter o with grave
				'&#243;'	=> '&oacute;',	// latin small letter o with acute
				'&#244;'	=> '&ocirc;',	// latin small letter o with circumflex
				'&#245;'	=> '&otilde;',	// latin small letter o with tilde
				'&#246;'	=> '&ouml;',	// latin small letter o with diaeresis
				'&#247;'	=> '&divide;',	// division sign
				'&#248;'	=> '&oslash;',	// latin small letter o with stroke
				'&#249;'	=> '&ugrave;',	// latin small letter u with grave
				'&#250;'	=> '&uacute;',	// latin small letter u with acute
				'&#251;'	=> '&ucirc;',	// latin small letter u with circumflex
				'&#252;'	=> '&uuml;',	// latin small letter u with diaeresis
				'&#253;'	=> '&yacute;',	// latin small letter y with acute
				'&#254;'	=> '&thorn;',	// latin small letter thorn
				'&#255;'	=> '&yuml;',	//
				'&#338;'	=> '&OElig;',	// latin capital ligature OE
				'&#339;'	=> '&oelig;',	// latin small ligature oe
				'&#352;'	=> '&Scaron;',	// latin capital letter S with caron
				'&#353;'	=> '&scaron;',	// latin small letter s with caron
				'&#376;'	=> '&Yuml;',	// latin capital letter Y with diaeresis
				'&#402;'	=> '&fnof;' ,	// latin small f with hook
				'&#710;'	=> '&circ;',	// modifier letter circumflex accent
				'&#732;'	=> '&tilde;',	// small tilde
				'&#913;'	=> '&Alpha;',	// greek capital letter alpha
				'&#914;'	=> '&Beta;',	// greek capital letter beta
				'&#915;'	=> '&Gamma;',	// greek capital letter gamma
				'&#916;'	=> '&Delta;',	// greek capital letter delta
				'&#917;'	=> '&Epsilon;', // greek capital letter epsilon
				'&#918;'	=> '&Zeta;',	// greek capital letter zeta
				'&#919;'	=> '&Eta;', 	// greek capital letter eta
				'&#920;'	=> '&Theta;',	// greek capital letter theta
				'&#921;'	=> '&Iota;',	// greek capital letter iota
				'&#922;'	=> '&Kappa;',	// greek capital letter kappa
				'&#923;'	=> '&Lambda;',	// greek capital letter lambda
				'&#924;'	=> '&Mu;',		// greek capital letter mu
				'&#925;'	=> '&Nu;',		// greek capital letter nu
				'&#926;'	=> '&Xi;',		// greek capital letter xi
				'&#927;'	=> '&Omicron;', // greek capital letter omicron
				'&#928;'	=> '&Pi;',		// greek capital letter pi
				'&#929;'	=> '&Rho;', 	// greek capital letter rho
				'&#931;'	=> '&Sigma;',	// greek capital letter sigma
				'&#932;'	=> '&Tau;', 	// greek capital letter tau
				'&#933;'	=> '&Upsilon;', // greek capital letter upsilon
				'&#934;'	=> '&Phi;', 	// greek capital letter phi
				'&#935;'	=> '&Chi;', 	// greek capital letter chi
				'&#936;'	=> '&Psi;', 	// greek capital letter psi
				'&#937;'	=> '&Omega;',	// greek capital letter omega
				'&#945;'	=> '&alpha;',	// greek small letter alpha
				'&#946;'	=> '&beta;',	// greek small letter beta
				'&#947;'	=> '&gamma;',	// greek small letter gamma
				'&#948;'	=> '&delta;',	// greek small letter delta
				'&#949;'	=> '&epsilon;', // greek small letter epsilon
				'&#950;'	=> '&zeta;',	// greek small letter zeta
				'&#951;'	=> '&eta;', 	// greek small letter eta
				'&#952;'	=> '&theta;',	// greek small letter theta
				'&#953;'	=> '&iota;',	// greek small letter iota
				'&#954;'	=> '&kappa;',	// greek small letter kappa
				'&#955;'	=> '&lambda;',	// greek small letter lambda
				'&#956;'	=> '&mu;',		// greek small letter mu
				'&#957;'	=> '&nu;',		// greek small letter nu
				'&#958;'	=> '&xi;',		// greek small letter xi
				'&#959;'	=> '&omicron;', // greek small letter omicron
				'&#960;'	=> '&pi;',		// greek small letter pi
				'&#961;'	=> '&rho;', 	// greek small letter rho
				'&#962;'	=> '&sigmaf;',	// greek small letter final sigma
				'&#963;'	=> '&sigma;',	// greek small letter sigma
				'&#964;'	=> '&tau;', 	// greek small letter tau
				'&#965;'	=> '&upsilon;', // greek small letter upsilon
				'&#966;'	=> '&phi;', 	// greek small letter phi
				'&#967;'	=> '&chi;', 	// greek small letter chi
				'&#968;'	=> '&psi;', 	// greek small letter psi
				'&#969;'	=> '&omega;',	// greek small letter omega
				'&#977;'	=> '&thetasym;',	// greek small letter theta symbol
				'&#978;'	=> '&upsih;',	// greek upsilon with hook symbol
				'&#982;'	=> '&piv;', 	// greek pi symbol
				'&#8194;'	=> '&ensp;',	// en space
				'&#8195;'	=> '&emsp;',	// em space
				'&#8201;'	=> '&thinsp;',	// thin space
				'&#8204;'	=> '&zwnj;',	// zero width non-joiner
				'&#8205;'	=> '&zwj;', 	// zero width joiner
				'&#8206;'	=> '&lrm;', 	// left-to-right mark
				'&#8207;'	=> '&rlm;', 	// right-to-left mark
				'&#8211;'	=> '&ndash;',	// en dash
				'&#8212;'	=> '&mdash;',	// em dash
				'&#8216;'	=> '&lsquo;',	// left single quotation mark
				'&#8217;'	=> '&rsquo;',	// right single quotation mark
				'&#8218;'	=> '&sbquo;',	// single low-9 quotation mark
				'&#8220;'	=> '&ldquo;',	// left double quotation mark
				'&#8221;'	=> '&rdquo;',	// right double quotation mark
				'&#8222;'	=> '&bdquo;',	// double low-9 quotation mark
				'&#8224;'	=> '&dagger;',	// dagger
				'&#8225;'	=> '&Dagger;',	// double dagger
				'&#8226;'	=> '&bull;',	// bullet
				'&#8230;'	=> '&hellip;',	// horizontal ellipsis
				'&#8240;'	=> '&permil;',	// per mille sign
				'&#8242;'	=> '&prime;',	// primeminutes
				'&#8243;'	=> '&Prime;',	// double prime
				'&#8249;'	=> '&lsaquo;',	// single left-pointing angle quotation mark
				'&#8250;'	=> '&rsaquo;',	// single right-pointing angle quotation mark
				'&#8254;'	=> '&oline;',	// overline
				'&#8260;'	=> '&frasl;',	// fraction slash
				'&#8364;'	=> '&euro;',	// euro sign
				'&#8465;'	=> '&image;',	// blackletter capital I
				'&#8472;'	=> '&weierp;',	// script capital P
				'&#8476;'	=> '&real;',	// blackletter capital R
				'&#8482;'	=> '&trade;',	// trade mark sign
				'&#8501;'	=> '&alefsym;', // alef symbol
				'&#8592;'	=> '&larr;',	// leftwards arrow
				'&#8593;'	=> '&uarr;',	// upwards arrow
				'&#8594;'	=> '&rarr;',	// rightwards arrow
				'&#8595;'	=> '&darr;',	// downwards arrow
				'&#8596;'	=> '&harr;',	// left right arrow
				'&#8629;'	=> '&crarr;',	// downwards arrow with corner leftwards
				'&#8656;'	=> '&lArr;',	// leftwards double arrow
				'&#8657;'	=> '&uArr;',	// upwards double arrow
				'&#8658;'	=> '&rArr;',	// rightwards double arrow
				'&#8659;'	=> '&dArr;',	// downwards double arrow
				'&#8660;'	=> '&hArr;',	// left right double arrow
				'&#8704;'	=> '&forall;',	// for all
				'&#8706;'	=> '&part;',	// partial differential
				'&#8707;'	=> '&exist;',	// there exists
				'&#8709;'	=> '&empty;',	// empty set
				'&#8711;'	=> '&nabla;',	// nabla
				'&#8712;'	=> '&isin;',	// element of
				'&#8713;'	=> '&notin;',	// not an element of
				'&#8715;'	=> '&ni;',		// contains as member
				'&#8719;'	=> '&prod;',	// n-ary product
				'&#8721;'	=> '&sum;', 	// n-ary sumation
				'&#8722;'	=> '&minus;',	// minus sign
				'&#8727;'	=> '&lowast;',	// asterisk operator
				'&#8730;'	=> '&radic;',	// square root
				'&#8733;'	=> '&prop;',	// proportional to
				'&#8734;'	=> '&infin;',	// infinity
				'&#8736;'	=> '&ang;', 	// angle
				'&#8743;'	=> '&and;', 	// logical and
				'&#8744;'	=> '&or;',		// logical or
				'&#8745;'	=> '&cap;', 	// intersection
				'&#8746;'	=> '&cup;', 	// union
				'&#8747;'	=> '&int;', 	// integral
				'&#8756;'	=> '&there4;',	// therefore
				'&#8764;'	=> '&sim;', 	// tilde operator
				'&#8773;'	=> '&cong;',	// approximately equal to
				'&#8776;'	=> '&asymp;',	// almost equal to
				'&#8800;'	=> '&ne;',		// not equal to
				'&#8801;'	=> '&equiv;',	// identical to
				'&#8804;'	=> '&le;',		// less-than or equal to
				'&#8805;'	=> '&ge;',		// greater-than or equal to
				'&#8834;'	=> '&sub;', 	// subset of
				'&#8835;'	=> '&sup;', 	// superset of
				'&#8836;'	=> '&nsub;',	// not a subset of
				'&#8838;'	=> '&sube;',	// subset of or equal to
				'&#8839;'	=> '&supe;',	// superset of or equal to
				'&#8853;'	=> '&oplus;',	// circled plus
				'&#8855;'	=> '&otimes;',	// circled times
				'&#8869;'	=> '&perp;',	// up tack
				'&#8901;'	=> '&sdot;',	// dot operator
				'&#8968;'	=> '&lceil;',	// left ceiling
				'&#8969;'	=> '&rceil;',	// right ceiling
				'&#8970;'	=> '&lfloor;',	// left floor
				'&#8971;'	=> '&rfloor;',	// right floor
				'&#9001;'	=> '&lang;',	// left-pointing angle bracket
				'&#9002;'	=> '&rang;',	// right-pointing angle bracket
				'&#9674;'	=> '&loz;', 	// lozenge
				'&#9824;'	=> '&spades;',	// black spade suit
				'&#9827;'	=> '&clubs;',	// black club suit
				'&#9829;'	=> '&hearts;',	// black heart suit
				'&#9830;'	=> '&diams;'	// black diam suit
				);

			// split entities for use in str_replace()
			foreach($codes as  $unicode_entity => $html_entity) {
				$unicode_entities[] = $unicode_entity;
				$html_entities[] = $html_entity;
			}
		}

		// transcode HTML entities to Unicode
		if($to_unicode)
			$output = str_replace($html_entities, $unicode_entities, $input);

		// transcode Unicode entities to HTML entities
		else
			$output = str_replace($unicode_entities, $html_entities, $input);

		// return by reference
		return $output;
	}


}

?>