<?PHP
/** Examples for PHP-PDB library - Twister - HTML to common format filters
 *
 * Copyright (C) 2001 - PHP-PDB development team
 * Licensed under the GNU LGPL software license.
 * See the doc/LEGAL file for more information
 * See http://php-pdb.sourceforge.net/ for more information about the library
 *
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 * @link http://php-pdb.sourceforge.net/
 */

// Please note that these filters are not perfect, but they do get the
// job done nicely.
//
// Code/ideas from convert.pl by Christopher Heschong <chris@screwdriver.net>
// Special HTML characters from Yannic Bergeron <bergery@videotron.ca>
// and Sam Denton <Sam.Denton@maryville.com>

function FilterHTML($fileData) {
   global $FilterHTMLLinks;

   $Filters = array(
      "'<script[^>]*>(.*)</script>'sie" =>   // Strip out javascript
         "FilterHTMLScript('\\1')",         // (It's very pesky)
      "'<pre[^>]*>(.*)</pre[^>]*>'sie" =>
         "FilterHTMLPre('\\1')",            // <pre> tags
      "'[\r\n\s]'" => ' ',                  // Newlines are now spaces
      "'<a[^>]*href=[\\'\"]?([^\" >]*)[\\'\"]?[^>]*>'ie" =>
         "FilterHTMLRipLink('\\1')",        // Links
      "'<frame[^>]*src=[\\'\"]?([^\" >]*)[\\'\"]?[^>]*>'ie" =>
         "FilterHTMLRipFrame('\\1')",       // Frames
      "'<br[^>]*>'i" => "\n",               // <br> and <li>
      "'<li[^>]*>'i" => "\n* ",             // li
      "'<dt[^>]*>'i" => "\n",               // dt
      "'<dd[^>]*>'i" => "\n* ",             // dd
      "'<th[^>]*>'i" => "",		            // <th>
      "'</th[^>]*>'i" => "\t",              // </th>
      "'<td[^>]*>'i" => "", 	            // <td>
      "'</td[^>]*>'i" => "\t",              // </td>
      "'<tr[^>]*>'i" => "\n",               // <tr>
      "'<p[^>]*>'i" => "\n\n",              // <p>
      "'<hr[^>]*>'i" =>
         "\n---------------------------------------\n",
	                                    // Horizontal rules
      "'<h\d[^>]*>'i" => "\n\n",            // Headers
      "'</h\d[^>]*>'i" => "\n",
      "'<[^>]* alt=[\\'\"]([^\"\\'>]*)[\"\\'][^>]*>'i" =>
         "\\1",                             // Anything with alt tags
      "'<[^>]* alt=([^ >]*)[^>]*>'i" => "\\1",
      "'<[\/\!]*[^<>]*>'si" => "",          // Strip out html & XML tags
      "' +'" => " ",                        // Strip out spaces again
      "'\n '" => "\n",
      "'&quot;'i" => '"',                   // Replace common html entities
      "'&amp;'i" => '&',
      "'&lt;'i" => '<',
      "'&gt;'i" => '>',
      "'&(nbsp|#160);'i" => ' ',
      "'&([^ ;<]*);'e" =>
         "FilterHTMLEntity('\\1')",         // Replace HTML entities
      "'&#(\d+);'e" => "chr(\\1)"           // evaluate &#xxx; as php
   );

   $fileData = preg_replace(array_keys($Filters), array_values($Filters),
                            $fileData);
   $fileData = trim($fileData) . "\n";

   if (isset($FilterHTMLLinks) && is_array($FilterHTMLLinks)) {
      $fileData .= "\n\nLinks found:\n";
      foreach ($FilterHTMLLinks as $index => $Link) {
         $fileData .= "  [" . ($index + 1) . "] ";
	 $fileData .= $Link;  // do some URL processing
	 $fileData .= "\n";
      }
   }

   return $fileData;
}


function FilterHTMLPre($Str) {
   $tempStr = strtolower($Str);
   $pos = strpos($tempStr, "</pre");

   if ($pos === false)
      return nl2br($Str);

   $pre = substr($Str, 0, $pos);
   $pos = strpos($Str, '>', $pos);

   if ($pos === false)
      return nl2br($Str);

   $post = substr($Str, $pos + 1);
   $pre = nl2br($pre);

   $post = preg_replace("'<pre[^>]*>(.*)</pre[^>]*>'sie",
      "FilterHTMLPre('\\1')", $post . '</pre>');

   return $pre . $post;
}

function FilterHTMLScript($Str) {
   $tempStr = strtolower($Str);
   $pos = strpos($tempStr, "</script");

   if ($pos === false)
      return '';

   $pos = strpos($Str, '>', $pos);

   if ($pos === false)
      return '';

   $post = substr($Str, $pos + 1);

   $post = preg_replace("'<script[^>]*>(.*)</script>'sie",
      "FilterHTMLScript('\\1')", $post . '</script>');

   return $post;
}

function FilterHTMLEntity($Ent) {
   // Sam got most of the following HTML 4.0 names from
   // http://spectra.eng.hawaii.edu/~msmith/ASICs/HTML/Style/allChar.htm
   // If not found in this array, convert to all lowercase and try again.
   $Replace = array(
      'emsp' => chr(129),    //  em space (HTML 2.0)
      'sbquo' => chr(130),   // single low-9 (bottom) quotation mark (U+201A)
      'fnof' => chr(131),    // Florin or Guilder (currency) (U+0192)
      'bdquo' => chr(132),   // double low-9 (bottom) quotation mark (U+201E)
      'hellip' => chr(133),  // horizontal ellipsis (U+2026)
      'dagger' => chr(134),  // dagger (U+2020)
      'Dagger' => chr(135),  // double dagger (U+2021)
      'circ' => chr(136),    // modifier letter circumflex accent
      'permil' => chr(137),  // per mill sign (U+2030)
      'Scaron' => chr(138),  // latin capital letter S with caron (U+0160)
      'lsaquo' => chr(139),  // left single angle quotation mark (U+2039)
      'OElig' => chr(140),   // latin capital ligature OE (U+0152)
      'diams' => chr(141),   // diamond suit (U+2666)
      'clubs' => chr(142),   // club suit (U+2663)
      'hearts' => chr(143),  // heart suit (U+2665)
      'spades' => chr(144),  // spade suit (U+2660)
      'lsquo' => chr(145),   // left single quotation mark (U+2018)
      'rsquo' => chr(146),   // right single quotation mark (U+2019)
      'ldquo' => chr(147),   // left double quotation mark (U+201C)
      'rdquo' => chr(148),   // right double quotation mark (U+201D)
      // 149?  Also - careful for the duplicates
      'endash' => chr(150),  // dash the width of ensp (Lynx)
      'ndash' => chr(150),   // dash the width of ensp (HTML 2.0)
      'emdash' => chr(151),  // dash the width of emsp (Lynx)
      'mdash' => chr(151),   // dash the width of emsp (HTML 2.0)
      'tilde' => chr(152),   // small tilde
      'trade' => chr(153),   // trademark sign (HTML 2.0)
      'scaron' => chr(154),  // latin small letter s with caron (U+0161)
      'rsaquo' => chr(155),  // right single angle quotation mark (U+203A)
      'oelig' => chr(156),   // latin small ligature oe (U+0153)
      // 157?  158?
      'Yuml' => chr(159),    // latin capital letter Y with diaeresis (U+0178)
      'ensp' => chr(160),    // en space (HTML 2.0)
      'thinsp' => chr(160), // thin space (Lynx)
      // from this point on, we're all (but 2) HTML 2.0
      'nbsp' => ' ', // non breaking space
      'iexcl' => chr(161), // inverted exclamation mark
      'cent' => chr(162), // cent (currency)
      'pound' => chr(163), // pound sterling (currency)
      'curren' => chr(164), // general currency sign (currency)
      'yen' => chr(165), // yen (currency)
      'brkbar' => chr(166), // broken vertical bar (Lynx)
      'brvbar' => chr(166), // broken vertical bar
      'sect' => chr(167), // section sign
      'die' => chr(168), // spacing dieresis (Lynx)
      'uml' => chr(168), // spacing dieresis
      'copy' => chr(169), // copyright sign
      'ordf' => chr(170), // feminine ordinal indicator
      'laquo' => chr(171), // angle quotation mark, left
      'not' => chr(172), // negation sign
      'shy' => chr(173), // soft hyphen
      'reg' => chr(174), // circled R registered sign
      'hibar' => chr(175), // spacing macron (Lynx)
      'macr' => chr(175), // spacing macron
      'deg' => chr(176), // degree sign
      'plusmn' => chr(177), // plus-or-minus sign
      'sup2' => chr(178), // superscript 2
      'sup3' => chr(179), // superscript 3
      'acute' => chr(180), // spacing acute
      'micro' => chr(181), // micro sign
      'para' => chr(182), // paragraph sign
      'middot' => chr(183), // middle dot
      'cedil' => chr(184), // spacing cedilla
      'sup1' => chr(185), // superscript 1
      'ordm' => chr(186), // masculine ordinal indicator
      'raquo' => chr(187), // angle quotation mark, right
      'frac14' => chr(188), // fraction 1/4
      'frac12' => chr(189), // fraction 1/2
      'frac34' => chr(190), // fraction 3/4
      'iquest' => chr(191), // inverted question mark
      'Agrave' => chr(192), // capital A, grave accent
      'Aacute' => chr(193), // capital A, acute accent
      'Acirc' => chr(194), // capital A, circumflex accent
      'Atilde' => chr(195), // capital A, tilde
      'Auml' => chr(196), // capital A, dieresis or umlaut mark
      'Aring' => chr(197), // capital A, ring
      'AElig' => chr(198), // capital AE diphthong (ligature)
      'Ccedil' => chr(199), // capital C, cedilla
      'Egrave' => chr(200), // capital E, grave accent
      'Eacute' => chr(201), // capital E, acute accent
      'Ecirc' => chr(202), // capital E, circumflex accent
      'Euml' => chr(203), // capital E, dieresis or umlaut mark
      'Igrave' => chr(204), // capital I, grave accent
      'Iacute' => chr(205), // capital I, acute accent
      'Icirc' => chr(206), // capital I, circumflex accent
      'Iuml' => chr(207), // capital I, dieresis or umlaut mark
      'Dstrok' => chr(208), // capital Eth, Icelandic (Lynx)
      'ETH' => chr(208), // capital Eth, Icelandic
      'Ntilde' => chr(209), // capital N, tilde
      'Ograve' => chr(210), // capital O, grave accent
      'Oacute' => chr(211), // capital O, acute accent
      'Ocirc' => chr(212), // capital O, circumflex accent
      'Otilde' => chr(213), // capital O, tilde
      'Ouml' => chr(214), // capital O, dieresis or umlaut mark
      'times' => chr(215), // multiplication sign
      'Oslash' => chr(216), // capital O, slash
      'Ugrave' => chr(217), // capital U, grave accent
      'Uacute' => chr(218), // capital U, acute accent
      'Ucirc' => chr(219), // capital U, circumflex accent
      'Uuml' => chr(220), // capital U, dieresis or umlaut mark
      'Yacute' => chr(221), // capital Y, acute accent
      'THORN' => chr(222), // capital THORN, Icelandic
      'szlig' => chr(223), // small sharp s, German (sz ligature)
      'agrave' => chr(224), // small a, grave accent
      'aacute' => chr(225), // small a, acute accent
      'acirc' => chr(226), // small a, circumflex accent
      'atilde' => chr(227), // small a, tilde
      'auml' => chr(228), // small a, dieresis or umlaut mark
      'aring' => chr(229), // small a, ring
      'aelig' => chr(230), // small ae diphthong (ligature)
      'ccedil' => chr(231), // small c, cedilla
      'egrave' => chr(232), // small e, grave accent
      'eacute' => chr(233), // small e, acute accent
      'ecirc' => chr(234), // small e, circumflex accent
      'euml' => chr(235), // small e, dieresis or umlaut mark
      'igrave' => chr(236), // small i, grave accent
      'iacute' => chr(237), // small i, acute accent
      'icirc' => chr(238), // small i, circumflex accent
      'iuml' => chr(239), // small i, dieresis or umlaut mark
      'dstrok' => chr(240), // small eth, Icelandic (Lynx)
      'eth' => chr(240), // small eth, Icelandic
      'ntilde' => chr(241), // small n, tilde
      'ograve' => chr(242), // small o, grave accent
      'oacute' => chr(243), // small o, acute accent
      'ocirc' => chr(244), // small o, circumflex accent
      'otilde' => chr(245), // small o, tilde
      'ouml' => chr(246), // small o, dieresis or umlaut mark
      'divide' => chr(247), // division sign
      'oslash' => chr(248), // small o, slash
      'ugrave' => chr(249), // small u, grave accent
      'uacute' => chr(250), // small u, acute accent
      'ucirc' => chr(251), // small u, circumflex accent
      'uuml' => chr(252), // small u, dieresis or umlaut mark
      'yacute' => chr(253), // small y, acute accent
      'thorn' => chr(254), // small thorn, Icelandic
      'yuml' => chr(255) // small y, dieresis or umlaut mark
   );
   if (isset($Replace[$Ent]))
      return $Replace[$Ent];
   $Ent = strtolower($Ent);
   if (isset($Replace[$Ent]))
      return $Replace[$Ent];
   return '';
}


function FilterHTMLRipLink($Link) {
   global $FilterHTMLLinks;

   if (! isset($FilterHTMLLinks))
      $FilterHTMLLinks = array();

   $FilterHTMLLinks[] = $Link;
   return '[' . count($FilterHTMLLinks) . ']';
}


function FilterHTMLRipFrame($Src) {
   global $FilterHTMLLinks;

   if (! isset($FilterHTMLLinks))
      $FilterHTMLLinks = array();

   $FilterHTMLLinks[] = $Src;
   return '[' . count($FilterHTMLLinks) . ' -- Frame:  ' . $Src . ']';
}

?>