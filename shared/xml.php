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

	/**
	 * check HTML/XHTML syntax
	 *
	 * This function uses some PHP XML parser to validate the provided string.
	 * The objective is to spot malformed or unordered HTML and XHTML tags. No more, no less.
	 *
	 * This function does not expand yacs codes anymore, because this could confuse end users,
	 * and also because of potential side effects (eg, redirection to another page).
	 *
	 * The error context is populated, if required.
	 *
	 * @param string the string to check
	 * @return boolean TRUE on success, FALSE otherwise
	 *
	 * @see actions/edit.php
	 * @see articles/edit.php
	 * @see comments/edit.php
	 * @see locations/edit.php
	 * @see sections/edit.php
	 * @see servers/edit.php
	 * @see tables/edit.php
	 * @see users/edit.php
	 */
	public static function validate($input) {
		global $context;

		// assume syntax is ok
		$text = '';

		// sanity check
		if(!is_callable('create_function'))
			return TRUE;

		// obvious
		$input = trim($input);
		if(!$input)
			return TRUE;

		// do not validate code nor snippet --do it in two steps to make it work
		$input = preg_replace('/<code>(.+?)<\/code>/ise', "'<code>'.str_replace('<', '&lt;', '$1').'</code>'", $input);
		$input = preg_replace('/<pre>(.+?)<\/pre>/ise', "'<pre>'.str_replace('<', '&lt;', '$1').'</pre>'", $input);

		// make a supposedly valid xml snippet
		$snippet = '<?xml version=\'1.0\'?>'."\n".'<snippet>'."\n".preg_replace(array('/&(?!(amp|#\d+);)/i', '/ < /i', '/ > /i'), array('&amp;', ' &lt; ', ' &gt; '), $input)."\n".'</snippet>'."\n";

		// remember tags during parsing
		global $validation_stack;
		$validation_stack = array();

		// create a parser
		$xml_parser = xml_parser_create();
		$startElement = create_function( '$parser, $name, $attrs', 'global $validation_stack; array_push($validation_stack, $name);' );
		$endElement = create_function( '$parser, $name', 'global $validation_stack; array_pop($validation_stack);' );
		xml_set_element_handler($xml_parser, $startElement, $endElement);

		// spot errors, if any
		if(!xml_parse($xml_parser, $snippet, TRUE)) {

			$text .= sprintf(i18n::s('XML error: %s at line %d'), xml_error_string(xml_get_error_code($xml_parser)),
				xml_get_current_line_number($xml_parser)-2).BR."\n";

			$lines = explode("\n", $snippet);
			$line = $lines[xml_get_current_line_number($xml_parser)-1];
			if(strpos($line, '</snippet>') === FALSE)
				$text .= htmlentities($line).BR."\n";

			$element = array_pop($validation_stack);
			if(!preg_match('/snippet/i', $element))
				$text .= sprintf(i18n::s('Last stacking element: %s'), $element);
		}

		// clear resources
		xml_parser_free($xml_parser);

		// return parsing result
		if($text) {
			Logger::error($text);
			return FALSE;
		}
		return TRUE;
	}
}

?>
