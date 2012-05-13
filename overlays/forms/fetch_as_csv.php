<?php
/**
 * download form content as CSV
 *
 * The permission assessment is based upon following rules applied in this order:
 * - associates and editors are allowed to move forward
 * - creator is allowed to view the page
 * - permission is denied if the anchor is not viewable
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - get_as_csv.php/12
 * - get_as_csv.php?id=12
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../../shared/global.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// encode ISO-8859-1 argument, if any
if(isset($_SERVER['HTTP_ACCEPT_CHARSET']) && preg_match('/^iso-8859-1/i', $_SERVER['HTTP_ACCEPT_CHARSET']))
	$id = utf8_encode($id);

// get the item from the database
$item =& Articles::get($id);

// get the related overlay, if any
$overlay = NULL;
if(isset($item['overlay']))
	$overlay = Overlay::load($item, 'article:'.$item['id']);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']))
	$anchor =& Anchors::get($item['anchor']);

// editors can do what they want on items anchored here
if(Surfer::is_member() && is_object($anchor) && $anchor->is_assigned())
	Surfer::empower();
elseif(isset($item['id']) && Articles::is_assigned($item['id']) && Surfer::is_member())
	Surfer::empower();

// readers have additional rights
elseif(Surfer::is_logged() && is_object($anchor) && $anchor->is_assigned())
	Surfer::empower('S');
elseif(isset($item['id']) && Articles::is_assigned($item['id']) && Surfer::is_logged())
	Surfer::empower('S');

// anonymous edition is allowed here
elseif(isset($item['options']) && $item['options'] && preg_match('/\banonymous_edit\b/i', $item['options']))
	Surfer::empower();

// members edition is allowed here
elseif(Surfer::is_member() && isset($item['options']) && $item['options'] && preg_match('/\bmembers_edit\b/i', $item['options']))
	Surfer::empower();

// maybe this anonymous surfer is allowed to handle this item
elseif(isset($item['handle']) && Surfer::may_handle($item['handle']))
	Surfer::empower();

//
// is this surfer allowed to browse the page?
//

// associates, editors and readers can read this page
if(Surfer::is_empowered('S'))
	$permitted = TRUE;

// poster can always view the page
elseif(isset($item['create_id']) && Surfer::is($item['create_id']))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// access is restricted to authenticated surfer
elseif(isset($item['active']) && ($item['active'] == 'R') && Surfer::is_logged())
	$permitted = TRUE;

// public access is allowed
elseif(isset($item['active']) && ($item['active'] == 'Y'))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load localized strings
i18n::bind('overlays');

// load the skin, maybe with a variant
load_skin('articles', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// path to this page
$context['path_bar'] = Surfer::get_path_bar($anchor);
if(isset($item['id']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Articles::get_permalink($item) => $item['title']));

// the title of the page
if(isset($item['title']) && $item['title'])
	$context['page_title'] = $item['title'];
else
	$context['page_title'] = i18n::s('Fetch as CSV');

// not found
if(!isset($item['id'])) {
	include '../../error.php';

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the table in CSV
} else {

	// force the character set
	$context['charset'] = 'iso-8859-15';

	// content of the final file
	$text = '';
	$separator = ";";

	// re-enforce CSV standard
	function filter($text) {
		$text = trim(preg_replace('/(\s|&nbsp;)/', ' ', ucfirst($text)));
		$text = Utf8::to_unicode($text);
		$text = Utf8::to_iso8859($text);
		return $text;
	}

	// one row for the title
	if($item['title'])
		$text .= '"'.filter($item['title']).'"'."\n";

	// add a link back to the web page
	$text .= '"'.$context['url_to_home'].$context['url_to_root'].Articles::get_permalink($item).'"'."\n\n";

	// one row per field
	foreach($overlay->attributes as $name => $field) {
		if(!is_array($field))
			continue;
		foreach($field as $name => $value) {

			$text .= '"'.filter($name).'"'.$separator;

			if(is_string($value))
				$text .= '"'.filter($value).'"'."\n";

			elseif(is_array($value)) {

				$labels = '';
				$index = 0;
				foreach($value as $option => $label) {
					if($index++)
						$text .= '""'.$separator;
					$text .= '"'.filter(($option+1).' - '.$label).'"'."\n";
				}

			}
		}
	}

	//
	// transfer to the user agent
	//

	// handle the output correctly
	render_raw('application/vnd.ms-excel; charset='.$context['charset']);

	// suggest a download
	if(!headers_sent()) {
		$file_name = utf8::to_ascii(Skin::strip($item['title']).'.csv');
		Safe::header('Content-Disposition: attachment; filename="'.str_replace('"', '', $file_name).'"');
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

// render the normal skin in case of error
render_skin();

?>
