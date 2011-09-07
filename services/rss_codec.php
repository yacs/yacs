<?php
/**
 * rss 2.0 encoder and decoder
 *
 * We are not providing the &lt;author&gt; field anymore because of the risk to expose e-mail addresses to spammers.
 *
 * @link http://blogs.law.harvard.edu/tech/rss RSS 2.0 Specification
 * @link http://www.disobey.com/detergent/2002/extendingrss2/ Extending RSS 2.0 With Namespaces
 * @link http://magpierss.sourceforge.net MagpieRSS: a simple RSS integration tool, from Kellan Elliott-McCrea
 *
 * If the geographical position of the server has been set through the configuration panel for skins,
 * it is included into the feed as well.
 *
 * @link http://postneo.com/icbm/ ICBM RSS module
 *
 * @see articles/feed.php
 * @see categories/feed.php
 * @see comments/feed.php
 * @see feeds/feeds.php
 * @see feeds/rss.php
 * @see sections/feed.php
 * @see services/call.php
 * @see services/codec.php
 * @see services/search.php
 * @see users/feed.php
 *
 * @author Bernard Paques
 * @author Dobliu
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class rss_Codec extends Codec {

	var $current_item	= array();	// item currently being parsed
	var $items			= array();	// collection of parsed items
	var $channel		= array();	// hash of channel fields
	var $textinput		= array();
	var $image			= array();

	var $elements_stack = array('rss_stream');
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
		$this->current_item = array();	// item currently being parsed
		$this->items = array(); // collection of parsed items
		$this->channel = array();	// hash of channel fields
		$this->textinput = array();
		$this->image = array();
		$this->elements_stack = array('rss_stream');
		$this->current_field	= '';
		$this->current_name_space	= false;

		// parse data
		if(!xml_parse($parser, $data)) {

			if($context['with_debug'] == 'Y')
				Logger::remember('services/rss_codec.php', 'invalid packet to decode', str_replace("\r\n", "\n", $data), 'debug');

			return array(FALSE, 'Parsing error: '.xml_error_string(xml_get_error_code($parser))
				.' at line '.xml_get_current_line_number($parser));
		}
		xml_parser_free($parser);

		// return parsing result
		return array(TRUE, $this->items);
	}

	function parse_cdata($parser, $text) {

		// transcode non-UTF-8 data
		if($this->encoding != 'UTF-8')
			$text = utf8::from_iso8859($text);

		// skip item, channel, items first time we see them
		if($this->elements_stack[0] == $this->current_field or !$this->current_field)
			return;

		// we are describing a channel
		if($this->elements_stack[0] == 'channel') {
			if(isset($this->current_name_space) && $this->current_name_space) {
				if(isset($this->channel[$this->current_name_space][$this->current_field]))
					$this->channel[$this->current_name_space][$this->current_field] .= $text;
				else
					$this->channel[$this->current_name_space][$this->current_field] = $text;
			} else {
				if(isset($this->channel[$this->current_field]))
					$this->channel[$this->current_field] .= $text;
				else
					$this->channel[$this->current_field] = $text;
			}

		// we are describing one information item
		} elseif($this->elements_stack[0] == 'item') {
			if(isset($this->current_name_space) && $this->current_name_space) {
				if(isset($this->current_item[$this->current_name_space][$this->current_field]))
					$this->current_item[$this->current_name_space][$this->current_field] .= $text;
				else
					$this->current_item[$this->current_name_space][$this->current_field] = $text;
			} else {
				if(isset($this->current_item[$this->current_field]))
					$this->current_item[$this->current_field] .= $text;
				else
					$this->current_item[$this->current_field] = $text;
			}

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


	/**
	 * parse closing tag
	 */
	function parse_end_element($parser, $element) {

		if($element == 'item') {
			$this->items[] = $this->current_item;
			$this->current_item = array();
			array_shift($this->elements_stack);
		} elseif($element == 'channel' or $element == 'items' or $element == 'textinput' or $element == 'image')
			array_shift( $this->elements_stack );

		$this->current_field = '';
		$this->current_name_space = false;
	}

	/**
	 * parse opening tag
	 */
	function parse_start_element($parser, $element, $attributes) {

		// check for a name_space, and split if found
		$name_space = false;
		if(strpos($element, ':'))
			list($name_space, $element) = explode(':', $element, 2);
		$this->current_field = $element;
		if($name_space and $name_space != 'rdf')
			$this->current_name_space = $name_space;

		if($element == 'channel')
			array_unshift($this->elements_stack, 'channel');
		elseif($element == 'items')
			array_unshift($this->elements_stack, 'items');
		elseif($element == 'item')
			array_unshift($this->elements_stack, 'item');
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
	 * encode PHP data into RSS
	 *
	 * Accept following values:
	 * - $values['channel']['title'] is a string
	 * - $values['channel']['link'] is a string
	 * - $values['channel']['description'] is a string
	 * - $values['channel']['image'] is a string to a channel image, if any
	 * - $values['items'] is an array of $url => array($time, $title, $author, $section, $image, $introduction, $description, $comments, $trackback, $comment_post, $comment_rss)
	 *
	 * @param mixed the parameter to encode
	 * @return some XML
	 */
	function encode(&$values) {
		global $context;

		// ensure we have a channel title
		if(isset($values['channel']['title']) && $values['channel']['title'])
			$channel_title = $values['channel']['title'];
		elseif(isset($context['server_title']) && $context['server_title'])
			$channel_title = $context['server_title'];
		else
			$channel_title = $context['host_name'];

		// ensure we have a channel link
		if(isset($values['channel']['link']) && $values['channel']['link'])
			$channel_link = $values['channel']['link'];
		else
			$channel_link = $context['url_to_home'].$context['url_to_root'];

		// allowed HTML in description
		$allowed = '<a><b><blockquote><form><hr><input><li><ol><p><strong><u><ul>';

		// the preamble
		$text = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'."\n"
			.'<rss version="2.0" '."\n"
				.'	xmlns:atom="http://www.w3.org/2005/Atom" '."\n"
				.'	xmlns:content="http://purl.org/rss/1.0/modules/content/" '."\n"
				.'	xmlns:dc="http://purl.org/dc/elements/1.1/" '."\n"
				.'	xmlns:georss="http://www.georss.org/georss" '."\n"
				.'	xmlns:icbm="http://postneo.com/icbm" '."\n"
				.'	xmlns:slash="http://purl.org/rss/1.0/modules/slash/" '."\n"
				.'	xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/" '."\n"
				.'	xmlns:wfw="http://wellformedweb.org/CommentAPI/" >'."\n"

			."\n"
			.'<channel>'."\n"
			.'	<title>'.rss_codec::clean($channel_title).'</title>'."\n"
			.'	<link>'.encode_link($channel_link).'</link>'."\n"
			.'	<atom:link href="'.encode_link($context['self_url']).'"  rel="self" type="application/rss+xml" />'."\n"
			.'	<description>'.rss_codec::clean($values['channel']['description'], $allowed).'</description>'."\n";

		if(isset($values['channel']['image']) && $values['channel']['image'] && ($size = Safe::GetImageSize($values['channel']['image']))) {

			$text .= '	<image>'."\n"
				.'		<url>'.encode_link($context['url_to_home'].$context['url_to_root'].$values['channel']['image']).'</url>'."\n"
				.'		<width>'.$size[0].'</width>'."\n"
				.'		<height>'.$size[1].'</height>'."\n"
				.'		<title>'.rss_codec::clean($channel_title).'</title>'."\n"
				.'		<link>'.encode_link($channel_link).'</link>'."\n"
				.'	</image>'."\n";

		}

		if(isset($context['preferred_language']) && $context['preferred_language'])
			$text .= '	<language>'.$context['preferred_language'].'</language>'."\n";
		if(isset($context['site_copyright']) && $context['site_copyright'])
			$text .= '	<copyright>'.rss_codec::clean($context['site_copyright']).'</copyright>'."\n";
		if(isset($context['site_email']) && $context['site_email'])
			$text .= '	<managingEditor>'.rss_codec::clean($context['site_email']).'</managingEditor>'."\n";
		if(isset($context['webmaster_address']) && $context['webmaster_address'])
			$text .= '	<webMaster>'.rss_codec::clean($context['site_email']).'</webMaster>'."\n";

		// encode icbm position
		if(isset($context['site_position']) && $context['site_position']) {
			list($latitude, $longitude) = preg_split('/[ ,\t]+/', $context['site_position']);
			$text .= '	<icbm:latitude>'.rss_codec::clean($latitude).'</icbm:latitude>'."\n";
			$text .= '	<icbm:longitude>'.rss_codec::clean($longitude).'</icbm:longitude>'."\n";
			$text .= '	<georss:point>'.str_replace(',', ' ', $context['site_position']).'</georss:point>'."\n";
		}

		$text .= '	<lastBuildDate>'.gmdate('D, d M Y H:i:s').' GMT</lastBuildDate>'."\n"
			.'	<generator>yacs</generator>'."\n"
			.'	<docs>http://blogs.law.harvard.edu/tech/rss</docs>'."\n";
		if(isset($context['time_to_live']) && ($context['time_to_live'] > 0))
			$text .= '	<ttl>'.$context['time_to_live'].'</ttl>'."\n";
		else
			$text .= '	<ttl>70</ttl>'."\n";

		// process rows, if any
		if(isset($values['items']) && is_array($values['items'])) {

			// for each item
			foreach($values['items'] as $url => $attributes) {
				$time = $attributes[0];
				$title = $attributes[1];
				$author = $attributes[2];
				$section = $attributes[3];
				$image = $attributes[4];
				$introduction = $attributes[5];
				$description = $attributes[6];
				$extensions = $attributes[7];

				// output one story
				$text .= "\n".' <item>'."\n";

				if($title)
					$text .= '		<title>'.rss_codec::clean($title)."</title>\n";

				if($url)
					$text .= '		<link>'.encode_link($url)."</link>\n"
					.'		<guid isPermaLink="true">'.encode_link($url)."</guid>\n";

				if($introduction)
					$text .= '		<description>'.rss_codec::clean($introduction)."</description>\n";
				elseif($description)
					$text .= '		<description>'.rss_codec::clean($description)."</description>\n";

				// use unicode entities, and escape & chars that are not part of an entity
				if($description)
					$text .= '		<content:encoded><![CDATA[ '.str_replace(']]>', ']]]]><![CDATA[>', $description)." ]]></content:encoded>\n";

				// do not express mail addresses, but only creator name, which is between ()
				if(preg_match('/\((.*?)\)/', $author, $matches))
					$text .= '		<dc:creator>'.rss_codec::clean($matches[1])."</dc:creator>\n";

				// do not put any attribute, it would kill FeedReader
				if($section)
					$text .= '		<category>'.encode_field(strip_tags($section))."</category>\n";

				if(intval($time))
					$text .= '		<pubDate>'.gmdate('D, d M Y H:i:s', intval($time))." GMT</pubDate>\n";

				// add any extension (eg, slash:comments, wfw:commentRss, trackback:ping)
				if(isset($extensions)) {

					if(is_array($extensions)) {
						foreach($extensions as $extension)
							$text .= '		'.$extension."\n";
					} else
						$text .= '		'.$extensions."\n";

				}

				$text .= "	</item>\n";

			}
		}

		// the postamble
		$text .= "\n</channel>\n"
			.'</rss>';

		return array(TRUE, $text);
	}

	/**
	 * build a request according to the RSS 2.0 specification
	 *
	 * @param string name of the remote service
	 * @param mixed transmitted parameters, if any
	 * @return an array of which the first value indicates call success or failure
	 * @see services/codec.php
	 */
	function export_request($service, $parameters = NULL) {

		// do the job
		return rss_Codec::encode($parameters);
	}

	/**
	 * build a response according to the RSS 2.0 specification
	 *
	 * @param mixed transmitted values, if any
	 * @param string name of the remote service, if any
	 * @return an array of which the first value indicates call success or failure
	 * @see services/codec.php
	 */
	function export_response($values=NULL, $service=NULL) {

		// do the job
		return rss_Codec::encode($values);
	}

	/**
	 * parse a XML submitted packet according to the RSS 2.0 specification
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
