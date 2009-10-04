<?php
/**
 * transmit the content of one feed
 *
 * This script helps to integrate remote feeds in a yacs page.
 *
 * Method to call is 'feed.proxy'.
 *
 * Parameters:
 * - 'url' - the complete address of the feed to parse
 *
 * Return:
 * - 'text' - the HTML to put in the target page
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// server hook to some web service
global $hooks;
$hooks[] = array(
	'id'		=> 'feed.proxy',
	'type'		=> 'serve',
	'script'	=> 'feeds/proxy_hook.php',
	'function'	=> 'Proxy_hook::serve',
	'label_en'	=> 'Transmit newsfeed content',
	'label_fr'	=> 'Transmet un fil de nouvelles',
	'source' => 'http://www.yacs.fr/'
);

class Proxy_hook {

	function serve($parameters) {
		global $context;

		// the output of this function
		$output = '';

		// we need a valid url
		if(!isset($parameters['url']))
			return $output;

		// read the newsfeed
		include_once $context['path_to_root'].'included/simplepie.inc';
		$feed = new SimplePie($parameters['url'], $context['path_to_root'].'temporary');
		$feed->init();

		// make a string
		$output['text'] = '';
		foreach($feed->get_items() as $item) {

			$content = '<strong><a href="'.$item->get_permalink().'">'.$item->get_title().'</a></strong>'."\n";

			if($enclosure = $item->get_enclosure())
				$content .= '<a href="'.$item->get_permalink().'"><img src="'.$enclosure->get_thumbnail().'" class="left_image" style="margin-right: 1em;"/></a>';

			$content .= '<div>'.$item->get_description()."\n"
				.BR.'<span class="details">'.Skin::build_date($item->get_date('U')).'</span></div>';

			$output['text'] .= '<div class="newsfeed_item">'."\n".$content.'<br clear="left" /></div>'."\n";

		}

		// return everything
		return $output;
	}

}

?>