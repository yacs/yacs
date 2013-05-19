<?php
/**
 * layout articles as events for SIMILE Timeline
 *
 * @see articles/articles.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_simile extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * @return int 1000 - this layout has no navigation bar
	 *
	 * @see skins/layout.php
	 */
	function items_per_page() {
		return 1000;
	}

	/**
	 * list articles
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function layout($result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;

		// load the SIMILE Timeline javascript library in shared/global.php
		$context['javascript']['timeline'] = TRUE;

		// sanity check
		if(!isset($this->layout_variant))
			$this->layout_variant = 'default';

		// put in cache
		$cache_id = Cache::hash('articles/layout_articles_as_simile:'.$this->layout_variant).'.xml';

		// save for one minute
		if(!file_exists($context['path_to_root'].$cache_id) || (filemtime($context['path_to_root'].$cache_id)+60 < time())) {

			// content of the slideshow
			$content = '<?xml version="1.0" encoding="utf-8"?>'."\n"
				.'<data>'."\n";

			// get a default image
			if(Safe::GetImageSize($context['path_to_root'].$context['skin'].'/layouts/map.gif'))
				$default_href = $context['url_to_root'].$context['skin'].'/layouts/map.gif';
			elseif($size = Safe::GetImageSize($context['path_to_root'].'skins/_reference/layouts/map.gif'))
				$default_href = $context['url_to_root'].'skins/_reference/layouts/map.gif';
			else
				$default_href = NULL;

			// process all items in the list
			while($item = SQL::fetch($result)) {

				// get the related overlay
				$overlay = Overlay::load($item, 'article:'.$item['id']);

				// get the anchor
				$anchor = Anchors::get($item['anchor']);

				// start
				if($item['publish_date'] > $item['create_date'])
					$first = Skin::build_date($item['publish_date'], 'plain');
				else
					$first = Skin::build_date($item['create_date'], 'plain');

				// end
				$last = Skin::build_date($item['edit_date'], 'plain');
				if($last != $first)
					$last = ' end="'.$last.'"';
				else
					$last = '';

				// build a title
				if(is_object($overlay))
					$title = Codes::beautify_title($overlay->get_text('title', $item));
				else
					$title = Codes::beautify_title($item['title']);

				// the url to view this item
				$url = str_replace('&', '&amp;', Articles::get_permalink($item));

				// this is visual
				if(isset($item['icon_url']) && $item['icon_url'])
					$image = $item['icon_url'];
				elseif(isset($item['thumbnail_url']) && $item['thumbnail_url'])
					$image = $item['thumbnail_url'];
				else
					$image = '';;

				// fix relative path
				if($image && !preg_match('/^(\/|http:|https:|ftp:)/', $image))
					$image = $context['url_to_root'].$image;

				if($image)
					$image = ' image="'.$image.'"';

				// introduction
				$introduction = '';
				if(is_object($overlay))
					$introduction = $overlay->get_text('introduction', $item);
				else
					$introduction = $item['introduction'];

				// insert overlay data, if any
				if(is_object($overlay) && ($data = $overlay->get_text('list', $item))) {
					if($introduction)
						$introduction .= BR;
					$introduction .= $data;
				}

				// ampersands kill SIMILE Timeline
 				if($introduction)
 					$introduction = encode_field(str_replace(array('&nbsp;', '&'), ' ', Codes::beautify($introduction)));

				// details
				$details = array();

				// info on related comments
				if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE))
					$details[] = sprintf(i18n::ns('%d comment', '%d comments', $count), $count);

				// info on related files
				if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
					$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);

				// info on related links
				if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
					$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);

				// combine in-line details
				if(count($details)) {
					if($introduction)
						$introduction .= BR;
					$introduction .= '<span class="details">'.trim(implode(', ', $details)).'</span>';
				}

				// escape the introduction, if any
				if($introduction)
					$introduction = str_replace(array('<', '&'), array('&lt;', '&amp;'), $introduction);

				// add to the list
				$content .= '	<event start="'.$first.'"'.$last.' title="'.encode_field(str_replace(array("&nbsp;", '"'), ' ', $title)).'" link="'.$url.'"'.$image.'>'."\n"
					.'		'.$introduction."\n"
					.'	</event>'."\n";

			}

			// finalize slideshow content
			$content .= '</data>';

			// put in cache
			Safe::file_put_contents($cache_id, $content);

		}

		// allow multiple instances
		static $count;
		if(!isset($count))
			$count = 1;
		else
			$count++;

		// 1 week ago
		$now = gmdate('M d Y H:i:s', time()-7*24*60*60);

		// load the right file
		$text = '<div id="articles_as_simile_'.$count.'" style="height: 300px; border: 1px solid #aaa; font-size: 10px"></div>'."\n"
			.JS_PREFIX
			.'var simile_handle_'.$count.';'."\n"
			.'function onLoad'.$count.'() {'."\n"
			.'  var eventSource = new Timeline.DefaultEventSource();'."\n"
			.'  var bandInfos = ['."\n"
			.'    Timeline.createBandInfo({'."\n"
			.'        eventSource:    eventSource,'."\n"
			.'        date:           "'.$now.'",'."\n"
			.'        width:          "80%",'."\n"
			.'        intervalUnit:   Timeline.DateTime.WEEK,'."\n"
			.'        intervalPixels: 200'."\n"
			.'    }),'."\n"
			.'    Timeline.createBandInfo({'."\n"
			.'        showEventText: false,'."\n"
			.'        trackHeight: 0.5,'."\n"
			.'        trackGap: 0.2,'."\n"
			.'        eventSource:    eventSource,'."\n"
			.'        date:           "'.$now.'",'."\n"
			.'        width:          "20%",'."\n"
			.'        intervalUnit:   Timeline.DateTime.MONTH,'."\n"
			.'        intervalPixels: 50'."\n"
			.'    })'."\n"
			.'  ];'."\n"
			.'  bandInfos[1].syncWith = 0;'."\n"
			.'  bandInfos[1].highlight = true;'."\n"
			.'  bandInfos[1].eventPainter.setLayout(bandInfos[0].eventPainter.getLayout());'."\n"
			.'  simile_handle_'.$count.' = Timeline.create(document.getElementById("articles_as_simile_'.$count.'"), bandInfos);'."\n"
			.'  Timeline.loadXML("'.$context['url_to_home'].$context['url_to_root'].$cache_id.'", function(xml, url) { eventSource.loadXML(xml, url); });'."\n"
			.'}'."\n"
			."\n"
			.'var resizeTimerID'.$count.' = null;'."\n"
			.'function onResize'.$count.'() {'."\n"
			.'    if (resizeTimerID'.$count.' == null) {'."\n"
			.'        resizeTimerID'.$count.' = window.setTimeout(function() {'."\n"
			.'            resizeTimerID'.$count.' = null;'."\n"
			.'            simile_handle_'.$count.'.layout();'."\n"
			.'        }, 500);'."\n"
			.'    }'."\n"
			.'}'."\n"
			."\n"
			.'// observe page major events'."\n"
			.'$(document).ready( onLoad'.$count.');'."\n"
			.'$(window).resize(onResize'.$count.');'."\n"
			.JS_SUFFIX;

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>
