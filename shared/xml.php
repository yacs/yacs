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
	 * check HTML/XHTML syntax
	 *
	 * This function uses some PHP XML parser to validate the provided string.
	 * The objective is to spot malformed or unordered HTML and XHTML tags. No more, no less.
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
	function validate($input) {
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

		// beautify YACS codes
		$input = Codes::beautify($input);

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
			Skin::error($text);
			return FALSE;
		}
		return TRUE;
	}
}

?>