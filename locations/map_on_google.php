<?php
/**
 * locate several items on Google Maps -- kept for old links only
 *
 * The mapping engine is no longer chosen by the page but by Locations::map(),
 * through the MAP_PROVIDER constant. The page itself has been renamed to
 * map.php, which is what Locations::get_url() now builds its links on.
 *
 * This script only forwards to it, so that bookmarks, search engines and
 * pages published before the OpenStreetMap integration keep working.
 *
 * @see locations/map.php
 * @see locations/locations.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// forward the call, path arguments included -- e.g. map_on_google.php/section/12
$target = 'locations/map.php';
if(isset($context['arguments']) && $context['arguments'])
	$target .= '/'.implode('/', array_map('rawurlencode', $context['arguments']));

// keep the query string, if any
$query = array();
foreach(array('anchor', 'id') as $name) {
	if(isset($_REQUEST[$name]) && $_REQUEST[$name])
		$query[] = $name.'='.urlencode(strip_tags($_REQUEST[$name]));
}
// a plain ampersand: the string goes to a Location header, not to HTML
if($query)
	$target .= '?'.implode('&', $query);

Safe::redirect($context['url_to_home'].$context['url_to_root'].$target);
