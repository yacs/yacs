<?php
/**
 * handle XML stuff
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

class xml {

	/**
	 * prepare a PHP variable
	 *
	 * @param mixed an array of variables
	 * @param we escape strings only at level 1
	 * @return string the corresponding XML string
	 */
	public static function encode($content, $level=0) {

		// the new representation
		$text = '';


		// a string
		if(is_string($content))
			$text .= $content;

		// a number
		elseif(is_int($content))
			$text .= $content;

		// an array
		elseif(is_array($content)) {
			foreach($content as $name => $value) {
				$name = str_replace('/', 'j', encode_field($name));
				$text .= "\t".'<'.$name.'>'.self::encode($value, $level+1).'</'.$name.'>'."\n";
			}
		}

		// we need to escape the string
		if(($level == 1) && ((strpos($text, '<') !== FALSE) || (strpos($text, '>') !== FALSE)))
			$text = str_replace(array('<', '>', '&'), array('&lt;', '&gt;', '&amp;'), $text);

		// job done
		return $text;

	}

	/**
	 * turn a PHP array to an XML string
	 *
	 * @param array the PHP variable
	 * @return string its XML representation
	 */
	public static function encode_array($content) {

		$text = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
			.'<data>'."\n"
			.self::encode($content)
			."\n".'</data>';

		return $text;
	}

	/**
	 * turn a PHP array to a DOMDocument
	 *
	 * @param array the PHP variable
	 * @return DOMDocument the related DOM representation, or FALSE on error
	 */
	public static function load_array($content) {
		$output = FALSE;
		if(method_exists('DOMDocument', 'loadXML')) {
			$output = new DOMDocument();
			$output->loadXML(self::encode_array($content), LIBXML_NOERROR);
		}
		return $output;
	}

	/**
	 * strip invisible HTML tags
	 *
	 * @param string the text to filter
	 * @return string the resulting text
	 */
	public static function strip_invisible_tags($text) {

		// remove invisible tags
		$text = preg_replace(array(
			'#<applet[^>]*?>.*?</applet>#siu',
			'#<comment[^>]*?>.*?</comment>#siu',
            '#<embed[^>]*?>.*?</embed>#siu',
			'#<head[^>]*?>.*?</head>#siu',
            '#<link[^>]*?>.*?</link>#siu',
            '#<listing[^>]*?>.*?</listing>#siu',
            '#<meta[^>]*?>.*?</meta>#siu',
            '#<noembed[^>]*?>.*?</noembed>#siu',
            '#<noframes[^>]*?>.*?</noframes>#siu',
            '#<noscript[^>]*?>.*?</noscript>#siu',
            '#<object[^>]*?>.*?</object>#siu',
            '#<plaintext[^>]*?>.*?</plaintext>#siu',
            '#<script[^>]*?>.*?</script>#siu',
            '#<style[^>]*?>.*?</style>#siu',
            '#<xmp[^>]*?>.*?</xmp>#siu'), '', $text);

		// job done
		return $text;

	}

	/**
	 * strip visible HTML tags
	 *
	 * @param string the text to filter
	 * @param string the set of tags that should be preserved
	 * @return string the resulting text
	 */
	public static function strip_visible_tags($text, $allowed='<a><b><br><i><img><strong><u>') {

		// insert hard newlines before we remove html tags
		$text = preg_replace('#<(br */{0,1}|h1|/h1|h2|/h2|h3|/h3|h4|/h4|h5|/h5|p|/p|/td)>#i', "<$1>\n", $text);

		// strip html tags
		$text = strip_tags($text, $allowed);

		// remove new lines after HTML breaks
		$text = preg_replace('#<(br */{0,1})>\n*#i', "<$1>", $text);

		// job done
		return $text;

	}

	/**
	 * transform some XML data
	 *
	 * [php]
	 * $data = xml::load_array($context);
	 * $text = xml::transform($data, 'template.xsl');
	 * [/php]
	 *
	 * @param DOMDocument input data
	 * @param string file that contains XSL declarations
	 * @return string the resulting XML, or FALSE on error
	 */
	public static function transform($data, $styles) {

		$text = FALSE;

		// we need the XSLT processor
		if(!method_exists('XSLTProcessor', 'importStyleSheet'))
			return $text;

		// we need the DOM loader
		if(!method_exists('DOMDocument', 'load'))
			return $text;

		// load styles that will be processed
		$dom = new DOMDocument();
		if(!$dom->load($styles))
			return $text;

		// initialize the XSLT engine
		$engine = new XSLTProcessor();
		$engine->importStyleSheet($dom);

		// do the job
		$text = $engine->transformToXML($data);
		return $text;

	}

	/**
	 * transform some XML data
	 *
	 * [php]
	 * $text = xml::transform_file('data.xml', 'template.xsl');
	 * [/php]
	 *
	 * @param string file that contains data
	 * @param string file that contains XSL declarations
	 * @return string the resulting XML, or FALSE on error
	 */
	public static function transform_file($data, $styles) {

		$text = FALSE;

		// we need the DOM loader
		if(!method_exists('DOMDocument', 'load'))
			return $text;

		// load data that will be processed
		$dom = new DOMDocument();
		if(!$dom->load($data))
			return $text;

		// do the job
		$text = xml::transform($dom, $styles);
		return $text;

	}

}
