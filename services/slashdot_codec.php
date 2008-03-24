<?php
/**
 * encoder and decoder for slashdot messages
 *
 * [title]Messages[/title]
 *
 * At the moment, this script gives following information for each resource:
 * - title - the title of the article
 * - url - the absolute url to fetch the article
 * - time - the date and time of article last modification
 * - author - the last contributor to the article
 * - section - the label of the section from where the article is originated
 * - image - the absolute url to fetch a related image, if any
 *
 * You will find below an excerpt of a real feed from slashdot:
 * [snippet]
 * <?xml version="1.0"?><backslash
 * xmlns:backslash="http://slashdot.org/backslash.dtd">
 *
 *	<story>
 *		<title>Recording Industry Extinction Predicted RSN</title>
 *		<url>http://slashdot.org/article.pl?sid=03/01/23/1236242</url>
 *		<time>2003-01-23 17:00:05</time>
 *		<author>michael</author>
 *		<department>imminent-death-predictions-getting-boring</department>
 *		<category>141</category>
 *		<comments>107</comments>
 *		<section>articles</section>
 *		<image>categorymusic.gif</image>
 *	</story>
 *
 *	...
 *
 *	<story>
 *		<title>Using Redundancies to Find Errors</title>
 *		<url>http://slashdot.org/article.pl?sid=03/01/23/0221242</url>
 *		<time>2003-01-23 04:20:58</time>
 *		<author>timothy</author>
 *		<department>the-error-is-right-around-here</department>
 *		<category>156</category>
 *		<comments>270</comments>
 *		<section>developers</section>
 *		<image>categoryprogramming.gif</image>
 *	</story>
 *
 * </backslash>
 * [/snippet]
 *
 *
 * @see services/codec.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class slashdot_Codec extends Codec {

	/**
	 * build a XML request according to the slashdot API
	 *
	 * Called by export_request() and export_response() to actually build a list of resources.
	 *
	 * Parameters is an array of $url => $attributes describing articles.
	 * Attributes are split according to the following syntax:
	 * [php]
	 * list($time, $title, $author, $section, $image, $description) = $attributes;
	 * [/php]
	 *
	 * @param array of $url => $attributes
	 * @return an array of which the first value indicates call success or failure
	 */
	function export($service, $parameters) {

		// the preamble
		$text = '<?xml version="1.0" encoding="'.$context['charset'].'"?><backslash'."\n"
			.' xmlns:backslash="http://slashdot.org/backslash.dtd">'."\n";

		// process all articles
		if(@count($parameters)) {
			foreach($parameters as $url => $attributes) {
				list($time, $title, $author, $section, $image, $description) = $attributes;

				// output one story
				$text .= "\n	<story>\n"
					.'		<title>'.encode_field(strip_tags($title))."</title>\n"
					."		<url>$url</url>\n";
				if($time)
					$text .= "		<time>$time</time>\n";
				if($author)
					$text .= "		<author>".encode_field(strip_tags($author))."</author>\n";
				if($section)
					$text .= "		<section>".encode_field(strip_tags($section))."</section>\n";
				if($image)
					$text .= "		<image>$image</image>\n";
				$text .= "	</story>\n";

			}
		}

		// the postamble
		$text .= "\n</backslash>";

		// all values are named
		return array(TRUE, $text);
	}

	/**
	 * build a XML request according to the slashdot API
	 *
	 * Example:
	 * [php]
	 * $result = $codec->export_request($service, $parameters);
	 * [/php]
	 *
	 * Parameters is an array of $url => $attributes describing articles.
	 * Attributes are split according to the following syntax:
	 * [php]
	 * list($time, $title, $author, $section, $image, $description) = $attributes;
	 * [/php]
	 *
	 * @param string name of the remote service
	 * @param array of $url => $attributes
	 * @return an array of which the first value indicates call success or failure
	 * @see services/codec.php
	 */
	function export_request($service, $parameters) {
		return slashdot_Codec::export($parameters);
	}

	/**
	 * build a XML response according to the slashdot API
	 *
	 * Example:
	 * [php]
	 * $result = $codec->export_response($values);
	 * [/php]
	 *
	 * Parameters is an array of $url => $attributes describing articles.
	 * Attributes are split according to the following syntax:
	 * [php]
	 * list($time, $title, $author, $section, $image, $description) = $attributes;
	 * [/php]
	 *
	 * @param array of $url => $attributes
	 * @param string name of the remote service
	 * @return an array of which the first value indicates call success or failure
	 * @see services/codec.php
	 */
	function export_response($values=NULL, $service=NULL) {
		return slashdot_Codec::export($parameters);
	}

	/**
	 * parse a XML request
	 *
	 * @param array the received $HTTP_RAW_POST_DATA
	 * @return array the service called and the related input parameters
	 */
	function import_request($data) {

		// create a parser
		$parser = xml_parser_create();
		xml_set_object($parser, $this);
		xml_set_element_handler($parser, 'parse_tag_open', 'parse_tag_close');
		xml_set_character_data_handler($parser, 'parse_cdata');

		// case is meaningful
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, FALSE);

		// parse data
		$this->result = array();
		$this->stack = array();
		if(!xml_parse($parser, $data)) {

			if(isset($context['with_debug']) && ($context['with_debug'] == 'Y'))
				Logger::remember('services/slashdot_codec.php', 'invalid packet to decode', str_replace("\r\n", "\n", $data), 'debug');

			return array(FALSE, 'Parsing error: '.xml_error_string(xml_get_error_code($parser))
				.' at line '.xml_get_current_line_number($parser));
		}
		xml_parser_free($parser);

		// extract the request
		$request = $this->result['YetAnotherCommunitySystem-Request'];
		if(!$request)
			return array(FALSE, 'Invalid request');

		// return parsing result
		return array(TRUE, $request);
	}

	/**
	 * decode a XML response
	 *
	 * @param string the received HTTP body
	 * @param string the received HTTP headers
	 * @param mixed the submitted parameters
	 * @return an array of which the first value indicates call success or failure
	 */
	function import_response($data, $headers=NULL, $parameters=NULL) {

		// create a parser
		$parser = xml_parser_create();
		xml_set_object($parser, $this);
		xml_set_element_handler($parser, 'parse_tag_open', 'parse_tag_close');
		xml_set_character_data_handler($parser, 'parse_cdata');

		// case is meaningful
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, FALSE);

		// parse data
		$this->stories = array();
		if(!xml_parse($parser, $data)) {

			if(isset($context['with_debug']) && ($context['with_debug'] == 'Y'))
				Logger::remember('services/slashdot_codec.php', 'invalid packet to decode', str_replace("\r\n", "\n", $data), 'debug');

			return array(FALSE, 'Parsing error: '.xml_error_string(xml_get_error_code($parser))
				.' at line '.xml_get_current_line_number($parser));
		}
		xml_parser_free($parser);

		// return parsing result
		return array(TRUE, $this->stories);
	}

	function parse_tag_open($parser, $tag, $attributes) {
	}

	var $cdata;

	function parse_cdata($parser, $cdata) {
		if(is_string($this->cdata) || !isset($this->cdata)) {
			$this->cdata = ltrim($this->cdata.$cdata); // preserve embedded hard coded new lines
		}
	}

	var $stories;

	var $title;
	var $url;
	var $time;
	var $author;
	var $department;
	var $category;
	var $comments;
	var $section;
	var $image;

	function parse_tag_close($parser, $tag) {

		switch($tag) {
		case 'title':
			$this->title = $this->cdata;
			unset($this->cdata);
			break;
		case 'url':
			$this->url = $this->cdata;
			unset($this->cdata);
			break;
		case 'time':
			$this->time = $this->cdata;
			unset($this->cdata);
			break;
		case 'author':
			$this->author = $this->cdata;
			unset($this->cdata);
			break;
		case 'department':
			$this->department = $this->cdata;
			unset($this->cdata);
			break;
		case 'category':
			$this->category = $this->cdata;
			unset($this->cdata);
			break;
		case 'comments':
			$this->comments = $this->cdata;
			unset($this->cdata);
			break;
		case 'section':
			$this->section = $this->cdata;
			unset($this->cdata);
			break;
		case 'image':
			$this->image = $this->cdata;
			unset($this->cdata);
			break;
		case 'story':

			// append the story to the set of news, using RSS keywords for member attributes
			$this->stories[] = array('title' => $this->title, 'link' => $this->url,
				'description' => $this->description, 'category' => $this->section,
				'pubDate' => $this->time, 'author' => $this->author, 'image' => $this->image);

			$this->title = $this->url = $this->time = $this->author = $this->department = $this->category = NULL;
			$this->comments = $this->section = $this->image = NULL;
			break;
		}
	}

}

?>