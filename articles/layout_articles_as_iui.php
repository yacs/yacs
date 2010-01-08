<?php
/**
 * conforms to iui format
 *
 * This is a special layout to accomodate to small screens of iPhone, iPod, and the like.
 *
 * @see articles/articles.php
 * @see articles/view_on_mobile.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_iui extends Layout_interface {

	/**
	 * list articles
	 *
	 * @param resource the SQL result
	 * @return array
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// we return some text
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// get the related overlay
			$overlay = Overlay::load($item);

			// output one story
			$text = "\n".' <li>'."\n";

			$url = $context['url_to_home'].$context['url_to_root'].Articles::get_permalink($item);

			$text .= '		<a href="'.str_replace('&', '&amp;', $url).'">'.encode_field(strip_tags($item['title']));

			// get the introduction
			if(is_object($overlay))
				$introduction = $overlay->get_text('introduction', $item);
			else
				$introduction = $item['introduction'];

			// layout the introduction
			if($introduction)
				$text .= BR.'<span style="font-family:verdana;font-size:11px;font-weight:normal;margin-top:5px;">'.strip_tags(Codes::beautify_introduction($introduction)).'</span>';

			$text .= "</a>\n";

	// 		if($author)
	// 			$text .= '		<author><name>'.encode_field($author).'</name></author>'."\n";
	//
	// 		if($introduction)
	// 			$text .= '		<summary type="text/html" mode="escaped"><![CDATA[ '.$introduction." ]]></summary>\n";
	//
	// 		if($description)
	// 			$text .= '		<content type="text/html" mode="escaped"><![CDATA[ '.$description." ]]></content>\n";
	//
	// 		if(intval($time))
	// 			$text .= '		<updated>'.gmdate('Y-m-d\TG:i:s\Z', intval($time))."</updated>\n";
	//
	// 		if($section)
	// 			$text .= '		<dc:source>'.encode_field($section).'</dc:source>'."\n";
	//
	// 		if($author)
	// 			$text .= '		<dc:creator>'.encode_field($author).'</dc:creator>'."\n";

			$text .= "	</li>\n";

			// another row
			$items[] = $text;

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>