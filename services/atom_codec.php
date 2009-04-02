<?php
/**
 * atom encoder and decoder
 *
 * We are not providing the &lt;author&gt; field anymore because of the risk to expose e-mail addresses to spammers.
 *
 * @link http://www.ietf.org/rfc/rfc4287.txt Atom Format
 * @link http://postneo.com/icbm/ ICBM atom module
 *
 * @see articles/feed.php
 * @see categories/feed.php
 * @see comments/feed.php
 * @see feeds/feeds.php
 * @see feeds/atom_2.0.php
 * @see sections/feed.php
 * @see services/call.php
 * @see services/codec.php
 * @see services/search.php
 * @see users/feed.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Atom_Codec extends Codec {

	var $current_entry	= array();	// entry currently being parsed
	var $entries		= array();	// collection of parsed entries
	var $feed			= array();	// hash of feed fields
	var $textinput		= array();
	var $image			= array();

	var $elements_stack = array('atom_stream');
	var $current_field	= '';
	var $current_name_space = false;

	/**
	 * parse some news
	 *
	 * @param string raw data received
	 * @return array a status code (TRUE is ok) and the parsing result
	 */
	function decode($data) {
		global $context;

		// create a parser with proper character encoding
		$this->encoding = 'ISO-8859-1';
		if(preg_match('/^<\?xml .+ encoding="utf-8".*\?>/i', $data))
			$this->encoding = 'UTF-8';
		$parser = xml_parser_create($this->encoding);

		// parser setup
		xml_set_object($parser, $this);
		xml_set_element_handler($parser, 'parse_start_element', 'parse_end_element');
		xml_set_character_data_handler($parser, 'parse_cdata');

		// case is meaningful
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, FALSE);

		// reset parsing data
		$this->current_entry = array();	// entry currently being parsed
		$this->entries = array(); // collection of parsed entries
		$this->feed = array();	// hash of feed fields
		$this->textinput = array();
		$this->image = array();
		$this->elements_stack = array('atom_stream');
		$this->current_field	= '';
		$this->current_name_space	= false;

		// parse data
		if(!xml_parse($parser, $data)) {

			if($context['with_debug'] == 'Y')
				Logger::remember('services/atom_codec.php', 'invalid packet to decode', str_replace("\r\n", "\n", $data), 'debug');

			return array(FALSE, 'Parsing error: '.xml_error_string(xml_get_error_code($parser))
				.' at line '.xml_get_current_line_number($parser));
		}
		xml_parser_free($parser);

		// return parsing result
		return array(TRUE, $this->entries);
	}

	function parse_cdata($parser, $text) {

		// transcode non-UTF-8 data
		if($this->encoding != 'UTF-8')
			$text = utf8::from_iso8859($text);

		// skip entry, feed, entries first time we see them
		if($this->elements_stack[0] == $this->current_field or !$this->current_field)
			return;

		// we are describing a feed
		if($this->elements_stack[0] == 'feed') {
			if(isset($this->current_name_space) && $this->current_name_space) {
				if(isset($this->feed[$this->current_name_space][$this->current_field]))
					$this->feed[$this->current_name_space][$this->current_field] .= $text;
				else
					$this->feed[$this->current_name_space][$this->current_field] = $text;
			} else {
				if(isset($this->feed[$this->current_field]))
					$this->feed[$this->current_field] .= $text;
				else
					$this->feed[$this->current_field] = $text;
			}

		// we are describing one information entry
		} elseif($this->elements_stack[0] == 'entry') {
			if(isset($this->current_name_space) && $this->current_name_space) {
				if(isset($this->current_entry[$this->current_name_space][$this->current_field]))
					$this->current_entry[$this->current_name_space][$this->current_field] .= $text;
				else
					$this->current_entry[$this->current_name_space][$this->current_field] = $text;
			} else {
				if(isset($this->current_entry[$this->current_field]))
					$this->current_entry[$this->current_field] .= $text;
				else
					$this->current_entry[$this->current_field] = $text;
			}

//			echo '.'.$this->current_entry[$this->current_field].BR;

		// we are describing a textinput
		} elseif($this->elements_stack[0] == 'textinput') {
			if(isset($this->current_name_space) && $this->current_name_space) {
				if(isset($this->textinput[$this->current_name_space][$this->current_field]))
					$this->textinput[$this->current_name_space][$this->current_field] .= $text;
				else
					$this->textinput[$this->current_name_space][$this->current_field] = $text;
			} else {
				if(isset($this->textinput[$this->current_field]))
					$this->textinput[$this->current_field] .= $text;
				else
					$this->textinput[$this->current_field] = $text;
			}

		// we are describing an image
		} elseif($this->elements_stack[0] == 'image') {
			if(isset($this->current_name_space) && $this->current_name_space) {
				if(isset($this->image[$this->current_name_space][$this->current_field]))
					$this->image[$this->current_name_space][$this->current_field] .= $text;
				else
					$this->image[$this->current_name_space][$this->current_field] = $text;
			} else {
				if(isset($this->image[$this->current_field]))
					$this->image[$this->current_field] .= $text;
				else
					$this->image[$this->current_field] = $text;
			}
		}
	}


	function parse_end_element($parser, $element) {

//		logger::debug('[/'.$element.']');

		if($element == 'entry') {
		
			// transcode to expected labels
			$entry = array();
			foreach($this->current_entry as $label => $value) {
				if($label == 'feedburner:origLink')
					$entry['link'] = $value;
				elseif($label == 'content')
					$entry['description'] = $value;
				else
					$entry[ $label ] = $value;
				
			}
			$this->entries[] = $entry;
			
			$this->current_entry = array();
			array_shift($this->elements_stack);
		} elseif($element == 'feed' or $element == 'author' or $element == 'textinput' or $element == 'image')
			array_shift( $this->elements_stack );

		$this->current_field = '';
		$this->current_name_space = false;
	}

	function parse_start_element($parser, $element, $attributes) {

//		logger::debug('['.$element.' '.$attributes.']', 'start');

		$this->current_field = $element;
		if($element == 'feed')
			array_unshift($this->elements_stack, 'feed');
		elseif($element == 'author')
			array_unshift($this->elements_stack, 'author');
		elseif($element == 'entry')
			array_unshift($this->elements_stack, 'entry');
		elseif($element == 'textinput')
			array_unshift($this->elements_stack, 'textinput');
		elseif($element == 'image')
			array_unshift($this->elements_stack, 'image');
	}

	/**
	 * suppress tags and codes from a string
	 *
	 * This function will remove any yacs code, and also any html tag, from the input string.
	 *
	 * @param string a label to check
	 * @param allowed html tags
	 * @return a clean string
	 */
	function clean($label, $allowed='') {
		// strip all yacs codes
		$label = preg_replace(array('/\[(.*?)\]/s', '/\[\/(.*?)\]/s'), ' ', $label);

		// reintroduce new lines
		$label = preg_replace('/<br\s*\/>/i', "\n", $label);

		// make some room around titles, paragraphs, and divisions
		$label = preg_replace('/<(code|div|h1|h2|h3|ol|li|p|pre|ul)>/i', ' <\\1>', $label);
		$label = preg_replace('/<\/(code|div|h1|h2|h3|ol|li|p|pre|ul)>/i', '</\\1> ', $label);

		// strip all html tags and encode
		$label = strip_tags($label, $allowed);

		// transform Unicode entities
		$label = utf8::transcode($label);

		// strip all html tags and encode
		return encode_field($label);
	}

	/**
	 * encode some PHP value into XML
	 *
	 * Accept following values:
	 * - $values['feed']['title'] is a string
	 * - $values['feed']['link'] is a string
	 * - $values['feed']['description'] is a string
	 * - $values['feed']['image'] is a string to a feed image, if any
	 * - $values['entries'] is an array of $url => array($time, $title, $author, $section, $image, $introduction, $description, $comments, $trackback, $comment_post, $comment_atom)
	 *
	 * @param mixed the parameter to encode
	 * @return some XML
	 */
	function encode(&$values) {
		global $context;

		// ensure we have a feed title
		if(isset($values['feed']['title']) && $values['feed']['title'])
			$feed_title = $values['feed']['title'];
		elseif(isset($context['server_title']) && $context['server_title'])
			$feed_title = $context['server_title'];
		else
			$feed_title = $context['host_name'];

		// ensure we have a feed link
		if(isset($values['feed']['link']) && $values['feed']['link'])
			$feed_link = $values['feed']['link'];
		else
			$feed_link = $context['url_to_home'].$context['url_to_root'];

		// allowed HTML in description
		$allowed = '<a><b><blockquote><form><hr><input><li><ol><p><strong><u><ul>';

		// the preamble
		$text = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'."\n"
			.'<feed xmlns="http://www.w3.org/2005/Atom" xmlns:openSearch="http://a9.com/-/spec/opensearch/1.1/" xmlns:gd="http://schemas.google.com/g/2005" '."\n"
				.'	xmlns:content="http://purl.org/atom/1.0/modules/content/" '."\n"
				.'	xmlns:dc="http://purl.org/dc/elements/1.1/" '."\n"
				.'	xmlns:icbm="http://postneo.com/icbm/" '."\n"
				.'	xmlns:slash="http://purl.org/atom/1.0/modules/slash/" '."\n"
				.'	xmlns:trackback="http://madskills.com/public/xml/atom/module/trackback/" '."\n"
				.'	xmlns:wfw="http://wellformedweb.org/CommentAPI/" >'."\n"

			."\n"
			.'	<title>'.atom_codec::clean($feed_title).'</title>'."\n"
			.'	<link>'.encode_link($feed_link).'</link>'."\n";

// 		if(isset($values['feed']['image']) && $values['feed']['image'] && ($size = Safe::GetImageSize($values['feed']['image']))) {
// 
// 			$text .= '	<image>'."\n"
// 				.'		<url>'.encode_link($values['feed']['image']).'</url>'."\n"
// 				.'		<width>'.$size[0].'</width>'."\n"
// 				.'		<height>'.$size[1].'</height>'."\n"
// 				.'		<title>'.atom_codec::clean($feed_title).'</title>'."\n"
// 				.'		<link>'.encode_link($feed_link).'</link>'."\n"
// 				.'	</image>'."\n";
// 
// 		}

// 		if(isset($context['preferred_language']) && $context['preferred_language'])
// 			$text .= '	<language>'.$context['preferred_language'].'</language>'."\n";
// 		if(isset($context['site_copyright']) && $context['site_copyright'])
// 			$text .= '	<copyright>'.atom_codec::clean($context['site_copyright']).'</copyright>'."\n";
// 		if(isset($context['site_email']) && $context['site_email'])
// 			$text .= '	<managingEditor>'.atom_codec::clean($context['site_email']).'</managingEditor>'."\n";
// 		if(isset($context['webmaster_address']) && $context['webmaster_address'])
// 			$text .= '	<webMaster>'.atom_codec::clean($context['site_email']).'</webMaster>'."\n";

		// encode icbm position
		if(isset($context['site_position']) && $context['site_position']) {
			list($latitude, $longitude) = preg_split('/[ ,\t]+/', $context['site_position']);
			$text .= '	<icbm:latitude>'.atom_codec::clean($latitude).'</icbm:latitude>'."\n";
			$text .= '	<icbm:longitude>'.atom_codec::clean($longitude).'</icbm:longitude>'."\n";
		}

		$text .= '	<updated>'.gmdate('D, d M Y H:i:s').' GMT</updated>'."\n"
			.'	<generator>yacs</generator>'."\n";

		// process rows, if any
		if(isset($values['entries']) && is_array($values['entries'])) {

			// for each entry
			foreach($values['entries'] as $url => $attributes) {
				$time = $attributes[0];
				$title = $attributes[1];
				$author = $attributes[2];
				$section = $attributes[3];
				$image = $attributes[4];
				$introduction = $attributes[5];
				$description = $attributes[6];
				$extensions = $attributes[7];

				// output one story
				$text .= "\n".' <entry>'."\n";

				if($title)
					$text .= '		<title>'.atom_codec::clean($title)."</title>\n";

				if($url)
					$text .= '		<link>'.encode_link($url)."</link>\n";

				if($introduction)
					$text .= '		<content type="text">'.atom_codec::clean($introduction)."</content>\n";
				elseif($description)
					$text .= '		<content type="text">'.atom_codec::clean($description)."</content>\n";

// use unicode entities, and escape & chars that are not part of an entity
// 				if($description)
// 					$text .= '		<body xmlns="http://www.w3.org/1999/xhtml">'.preg_replace('/&(?!(amp|#\d+);)/i', '&amp;', utf8::transcode($description))."</body>\n";
// //					$text .= '		<content:encoded><![CDATA[ '.$description." ]]></content:encoded>\n";
// 
// do not express mail addresses, but only creator name, which is between ()
// 				if(preg_match('/\((.*?)\)/', $author, $matches)) {
// //					$text .= '		<author>'.atom_codec::clean($author)."</author>\n";
// 					$text .= '		<dc:creator>'.atom_codec::clean($matches[1])."</dc:creator>\n";
// 				}
// 
// do not put any attribute, it would kill FeedReader
// 				if($section)
// 					$text .= '		<category>'.encode_field(strip_tags($section))."</category>\n";
// 
// 				if(intval($time))
// 					$text .= '		<pubDate>'.gmdate('D, d M Y H:i:s', intval($time))." GMT</pubDate>\n";
// 
				// add any extension (eg, slash:comments, wfw:commentatom, trackback:ping)
				if(isset($extensions)) {

					if(is_array($extensions)) {
						foreach($extensions as $extension)
							$text .= '		'.$extension."\n";
					} else
						$text .= '		'.$extensions."\n";

				}

				$text .= "	</entry>\n";

			}
		}

		// the postamble
		$text .= "\n</feed>";

		return array(TRUE, $text);
	}

	/**
	 * build a request according to the atom specification
	 *
	 * @param string name of the remote service
	 * @param mixed transmitted parameters, if any
	 * @return an array of which the first value indicates call success or failure
	 * @see services/codec.php
	 */
	function export_request($service, $parameters = NULL) {

		// do the job
		return atom_Codec::encode($values);
	}

	/**
	 * build a response according to the atom specification
	 *
	 * @param mixed transmitted values, if any
	 * @param string name of the remote service, if any
	 * @return an array of which the first value indicates call success or failure
	 * @see services/codec.php
	 */
	function export_response($values=NULL, $service=NULL) {

		// do the job
		return atom_Codec::encode($values);
	}

	/**
	 * parse a XML submitted packet according to the atom specification
	 *
	 * @param string raw data received
	 * @return array the service called and the related input parameters
	 */
	function import_request($data) {

		// parse the input packet
		return $this->decode($data);
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

		// parse the input packet
		return $this->decode($data);
	}

}

?>