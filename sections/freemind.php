<?php
/**
 * get content as a Freemind map
 *
 * Output of this script can be downloaded as a Freemind map, which is quite
 * useful for off-line browsing of site content.
 *
 * @link http://evamoraga.net/efectokiwano/mm/
 *
 * If following features are enabled, this script will use them:
 * - compression - Through gzip, we have observed a shift from 3566 bytes to 881 bytes, meaning one Ethernet frame rather than three
 * - cache - Cache is supported through ETag and by setting Content-Length; Also, Cache-Control enables caching for some time, even through HTTPS
 *
 * Restrictions apply on this page:
 * - if no section id is provided, access is granted
 * - associates and editors are allowed to move forward
 * - permission is denied if the anchor is not viewable
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - freemind.php - reflect the entire content tree
 * - freemind.php/12/any_name - provide content of section 12
 * - freemind.php?id=12 - provide content of section 12
 *
 * @author Bernard Paques
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../comments/comments.php';
include_once '../files/files.php';
include_once '../links/links.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);
if($id == 'all')
	$id = NULL;

// get the item from the database
$item =& Sections::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// editors have associate-like capabilities
if(Surfer::is_empowered('M') && (isset($item['id']) && isset($user['id']) && (Sections::is_assigned($item['id'], $user['id']))) || (is_object($anchor) && $anchor->is_editable()))
	Surfer::empower('A');

// access to main map is always granted
if(!isset($item['id']))
	$permitted = TRUE;

// associates and editors are always authorized
elseif(Surfer::is_empowered())
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// access is restricted to authenticated member
elseif(isset($item['active']) && ($item['active'] == 'R') && Surfer::is_empowered('M'))
	$permitted = TRUE;

// public access is allowed
elseif(isset($item['active']) && ($item['active'] == 'Y'))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load a skin, maybe with a variant
load_skin('sections', $anchor, isset($item['options']) ? $item['options'] : '');

// the path to this page
$context['path_bar'] = array( 'sections/' => i18n::s('Sections') );

// the title of the page
if(isset($item['title']) && $item['title'])
	$context['page_title'] = $item['title'];
else
	$context['page_title'] = i18n::s('Freemind');

// not found
if($id && !isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.'));

// access denied
} elseif(!$permitted) {

	// give anonymous surfers a chance for HTTP authentication
	if(!Surfer::is_logged()) {
		Safe::header('WWW-Authenticate: Basic realm="'.utf8::to_iso8859($context['site_name']).'"');
		Safe::header('Status: 401 Unauthorized', TRUE, 401);
	}

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// deliver required data
} else {

	// the Freemind applet does not support utf-8 -- do not change charset on scripts/validate.php
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
		$context['charset'] = 'iso-8859-1';

	// top-level data
	if(isset($item['id']))
		$main_id = 'section_'.$item['id'];
	else
		$main_id = str_replace('.', '_', $context['host_name']);

	if(isset($item['title']))
		$title = Codes::beautify_title($item['title']);
	else
		$title = $context['site_name'];

	$icon = '';
	if(!isset($item['id']))
		$icon = "\t".'<icon BUILTIN="gohome" />'."\n";

	// get the list from the cache, if possible
	if(isset($item['id']))
		$cache_id = 'sections/freemind.php?id='.$item['id'];
	else
		$cache_id = 'sections/freemind.php';
	if(!$text =& Cache::get($cache_id)) {

		// default parameter values
		$freemind_main_bgcolor = ' BACKGROUND_COLOR="#ffffff"';
		if(isset($context['skins_freemind_main_bgcolor']) && $context['skins_freemind_main_bgcolor'])
			$freemind_main_bgcolor = ' BACKGROUND_COLOR="'.$context['skins_freemind_main_bgcolor'].'"';

		$freemind_main_color = ' COLOR="#cc0066"';
		if(isset($context['skins_freemind_main_color']) && $context['skins_freemind_main_color'])
			$freemind_main_color = ' COLOR="'.$context['skins_freemind_main_color'].'"';

		if(!isset($context['skins_freemind_edge_color']) || !$context['skins_freemind_edge_color'])
			$context['skins_freemind_edge_color'] = '#cc0066';
		if(!isset($context['skins_freemind_edge_style']) || !$context['skins_freemind_edge_style'])
			$context['skins_freemind_edge_style'] = 'bezier'; // bezier, sharp_bezier, linear, sharp_linear, rectangular
		if(!isset($context['skins_freemind_edge_thickness']) || !$context['skins_freemind_edge_thickness'])
			$context['skins_freemind_edge_thickness'] = 'thin'; // 1, 2, 3, ... or thin

		// initialize variables
		$prefix = $suffix = $rating = '';

		// details only matter for target sections
		if(isset($item['id'])) {

			// flag articles updated recently
			if($context['site_revisit_after'] < 1)
				$context['site_revisit_after'] = 2;
			$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
			$now = gmstrftime('%Y-%m-%d %H:%M:%S');

			// flag expired pages
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$prefix .= EXPIRED_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// details
			$suffix .= '<small>';

			// append page introduction ,if any
			if($item['introduction'] && ($context['skins_with_details'] == 'Y')) {

				// wrap only outside X/HTML tags
				$areas = preg_split('/(<[a-z\/].+?>)/i', trim(Codes::beautify($item['introduction'])), -1, PREG_SPLIT_DELIM_CAPTURE);
				$index = 0;
				foreach($areas as $area) {
					if((++$index)%2)
						$suffix .= wordwrap($area, 70, BR, 0);
					else
						$suffix .= $area;
				}
			}

			// add other details
			$details = array();

			// flag pages updated recently
			if(($item['create_date'] >= $dead_line) || ($item['edit_date'] >= $dead_line))
				$details[] = Skin::build_date($item['edit_date']);

			// count related files, if any
			if($count = Files::count_for_anchor('section:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);

			// count related comments, if any
			if($count = Comments::count_for_anchor('section:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d comment', '%d comments', $count), $count);

			// count related links, if any
			if($count = Links::count_for_anchor('section:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);

			// append details
			if(count($details)) {
				if(trim($suffix))
					$suffix .= BR;
				$suffix .= implode(', ', $details);
			}

			// rating
			if(isset($item['rating_count']) && $item['rating_count'] && is_object($anchor) && !$anchor->has_option('without_rating', FALSE))
				$rating = Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count']));

		}

		// preamble
		$text = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'."\n"
			.'<map version="0.8.FA Alpha 5a">'."\n"
			.'<!-- To view this file, download free mind mapping software FreeMind from http://freemind.sourceforge.net -->'."\n"
			.'<node ID="'.$main_id.'"'
				.$freemind_main_bgcolor
				.$freemind_main_color
				.' TEXT="'.encode_field(utf8::to_hex('<html><center>'.$prefix.$title.$rating.'</center></html>')).'">'."\n"
			.$icon
			."\t".'<font BOLD="true" NAME="SansSerif" SIZE="15" />'."\n"
			."\t".'<edge COLOR="'.$context['skins_freemind_edge_color'].'" STYLE="'.$context['skins_freemind_edge_style'].'" WIDTH="'.$context['skins_freemind_edge_thickness'].'" />'."\n";

		// add details
		if($suffix)
				$text .= '<hook NAME="accessories/plugins/NodeNote.properties"><text>'.encode_field(utf8::to_hex(strip_tags(preg_replace(array('/<br/i', '/<li/i', '/&nbsp;/'), array("\n<br", "\n- <li", ' '), $suffix)))).'</text></hook>';

		// top-level anchor
		$anchor = NULL;
		if(isset($item['id']))
			$anchor = 'section:'.$item['id'];

		// add sub-sections
		$text .= Sections::list_by_title_for_anchor($anchor, 0, 50, 'freemind');

		// add articles
		$text .= Articles::list_for_anchor_by('publication', $anchor, 0, 50, 'freemind');

		// postamble
		$text .= '</node>'."\n"
			.'</map>';

		// save in cache for 5 hours, because of numerous requests - 5*60*60 = 18000
		Cache::put($cache_id, $text, 'stable', 18000);
	}

	//
	// transfer to the user agent
	//

	// handle the output correctly
	render_raw('application/x-freemind');
//	render_raw('text/html');

	// suggest a name on download
	if(!headers_sent()) {
		if(isset($item['id']))
			$file_name = utf8::to_ascii($context['site_name'].' - '.$title.'.mm');
		else
			$file_name = utf8::to_ascii($context['site_name'].'.mm');
		Safe::header('Content-Disposition: inline; filename="'.$file_name.'"');
	}

	// enable 30-minute caching (30*60 = 1800), even through https, to help IE6 on download
	http::expire(1800);

	// strong validator
	$etag = '"'.md5($text).'"';
	
	// manage web cache
	if(http::validate(NULL, $etag))
		return;

	// actual transmission except on a HEAD request
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
		echo $text;

	// the post-processing hook, then exit
	finalize_page(TRUE);

}

// render the skin
render_skin();

?>