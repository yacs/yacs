<?php
/**
 * play all images in a slideshow
 *
 * This script makes a show out of published photos, scans, or images.
 *
 * @link http://javascript.internet.com/miscellaneous/image-slideshow.html
 * @link http://www.barelyfitz.com/projects/slideshow/
 *
 * Optionnally, this script also reads internal information for some file types, in order to enhance the provided listing.
 * This reading is based on the getid3() library, which is loaded if it is available.
 *
 * @link http://getid3.sourceforge.net/ getID3() the PHP media file parser
 *
 * For authentication on protected page this script use basic HTTP authentication.
 * This means that the anonymous surfer will have either to use the regular login page, or to provide name and password on a per-request basis.
 *
 * Accept following invocations:
 * - play_slideshow.php?path=&lt;collection/path/to/browse&gt;
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'collections.php';

// load the skin -- before loading the collection
load_skin('slideshow');

// the path to browse
$id = NULL;
if(isset($_REQUEST['path']))
	$id = urldecode($_REQUEST['path']);
elseif(isset($context['arguments'][1]))
	$id = join('/', $context['arguments']);
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// bind the virtual item to something real
$item = Collections::get($id);

// check network credentials, if any
if($user = Users::authenticate())
	Surfer::empower($user['capability']);

// the path to this page
$context['path_bar'] = array( 'collections/' => i18n::s('File collections') );

// the collection has to exist
if(!isset($item['collection']) || !$item['collection']) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	$context['page_title'] = i18n::s('Unknown collection');
	Skin::error(i18n::s('The collection asked for is unknown.'));

// access is prohibited
} elseif((($item['collection_visibility'] == 'N') && !Surfer::is_empowered())
	|| (($item['collection_visibility'] == 'R') && !Surfer::is_empowered('M'))) {

	// give anonymous surfers a chance for HTTP authentication
	if(!Surfer::is_logged()) {
		Safe::header('WWW-Authenticate: Basic realm="'.utf8::to_iso8859($context['site_name']).'"');
		Safe::header('Status: 401 Unauthorized', TRUE, 401);
	}

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	$context['page_title'] = i18n::s('Restricted access');
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// play files in this location
} else {

	// bread crumbs to upper levels, if any
	$context['path_bar'] = array_merge($context['path_bar'], $item['containers']);

	// list where we are
	if(isset($item['node_label'])) {
		$context['page_title'] = $item['node_label'];

	// houston, we've got a problem
	} else
		$context['page_title'] = i18n::s('Untitled collection');

	// browse the path to list directories and files
	if(!$dir = Safe::opendir ($item['actual_path']))
		$context['text'] .= '<p>'.sprintf(i18n::s('The directory %s does not exist. Please check %s.'), $item['relative_path'], Skin::build_link('collections/browse.php?path='.$collection, i18n::s('the index page'), 'shortcut'))."</p>\n";

	// built the slideshow
	else {

		// look for specific extensions
		$files_in_path = array();
		while(($node = Safe::readdir($dir)) !== FALSE) {

			// skip some files
			if($node == '.' || $node == '..')
				continue;

			// skip directories
			if(is_dir($item['actual_path'].'/'.$node))
				continue;

			// look for audio files only
			if(!preg_match('/\.(bmp|gif|jpe|jpeg|jpg|png)$/i', $node))
				continue;

			$files_in_path[] = $node;
		}
		Safe::closedir($dir);

		// no file has been found
		if(!count($files_in_path))
			$context['text'] .= '<p>'.sprintf(i18n::s('There is no image in the directory %s to run a show. Please check %s.'), $item['relative_path'], Skin::build_link('collections/browse.php?path='.$collection, i18n::s('the index page'), 'shortcut'))."</p>\n";

		// do the show
		else {

			// controls
			$context['text'] .= '<p id="slide_controls">'."\n";

			// the list of slides -- use 'id' instead of 'names' for field
			$index = 0;
			$context['text'] .= '<select id="slide_list" title="'.i18n::s('Select the slide to show').'" onChange="change_slide(this);">'."\n";
			foreach($files_in_path as $file) {

				// enhance image name rendering
				$file = preg_replace('/\.(bmp|gif|jpe|jpeg|jpg|png)$/i', '', $file);
				$file = str_replace(array('.', '_', '%20'), ' ', $file);

				// make an option for this slide
				$context['text'] .= '<option value="slide_'.$index++.'">'.encode_field($file).'</option>'."\n";
			}
			$context['text'] .= '</select>'."\n";

			// slide controls -- use 'id' instead of 'names' for fields
			$context['text'] .= '<input type="button" id="first_button" onclick="javascript:first_slide();" value="|<<" title="'.i18n::s('Beginning').'"'.EOT."\n";
			$context['text'] .= '<input type="button" id="previous_button" onclick="javascript:previous_slide();" value="<<" title="'.i18n::s('Previous').'"'.EOT."\n";
			$context['text'] .= '<input type="button" id="rotate_button" onclick="javascript:start_stop(this);" value="'.i18n::s('Start').'" title="'.i18n::s('Autoplay').'"'.EOT."\n";
			$context['text'] .= '<input type="button" id="next_button" onclick="javascript:next_slide();" value=">>" title="'.i18n::s('Next').'"'.EOT."\n";
			$context['text'] .= '<input type="button" id="last_button" onclick="javascript:last_slide();" value=">>|" title="'.i18n::s('Last').'"'.EOT."\n";

			$context['text'] .= '</p>'."\n";

			// sanity check
			$context['text'] .= '<noscript><p>'.i18n::s('Please activate Javascript to benefit from the slideshow.').'</p></noscript>'."\n";

			// actual slides
			$index = 0;
			foreach($files_in_path as $file) {

				// sanity check
				if(!$data = Safe::GetImageSize($item['actual_path'].'/'.$file))
					continue;

				// assume we don't need to resize
				list($width, $height, $type, $size) = $data;
				$adjusted_width = $width;
				$adjusted_height = $height;
				$standard_width = $context['standard_width'];
				if($standard_width < 10)
					$standard_width = 640;
				$standard_height = $context['standard_height'];
				if($standard_height < 10)
					$standard_height = 480;

				// the image is laid vertically - limit the width
				if($height > $width) {

					// set the adjusted size
					if($height > $standard_height) {
						$adjusted_height = $standard_height;
						$adjusted_width = $width * $adjusted_height / $height;
					}

				// the image is laid horizontally - limit the height
				} else {

					// set the adjusted size
					if($width > $standard_width) {
						$adjusted_width = $standard_width;
						$adjusted_height = $height * $adjusted_width / $width;
					}
				}

				// image to load
				$url = $item['actual_url'].'/'.rawurlencode($file);

				// enhance image name rendering
				$file = preg_replace('/\.(bmp|gif|jpe|jpeg|jpg|png)$/i', '', $file);
				$file = str_replace(array('.', '_', '%20'), ' ', $file);

				// make a div for this slide -- click on the image to get the full size version
				$context['text'] .= '<div id="slide_'.$index.'" class="slide" style="display: none;">'
					.'<a href="'.$url.'" title="'.sprintf(i18n::s('Click to view only the original image %s'), $file).'"><img src="'.$url.'" width="'.$adjusted_width.'" height="'.$adjusted_height.'" alt="'.$file.'"'.EOT.'</a>'
					.'</div>'."\n";

				// next slide
				$index++;

			}

			// js under ie requires iso8859!!
			// the javascript engine
			$context['text'] .= '<script type="text/javascript" >// <![CDATA['."\n"
				.'// index of the current slide'."\n"
				.'var current = 0;'."\n"
				."\n"
				.'// ensure the current slide is visible'."\n"
				.'var handle = document.getElementById(\'slide_\'+current)'."\n"
				.'if(handle)'."\n"
				.'	handle.style.display = \'block\';'."\n"
				."\n"
				.'// update index of slides'."\n"
				.'if(handle = document.getElementById("slide_list"))'."\n"
				.'	handle.selectedIndex = current;'."\n"
				."\n"
				."\n"
				.'// another slide has been selected from the dropping list'."\n"
				.'function change_slide(handle) {'."\n"
				."\n"
				.'	// hide slide displayed previously'."\n"
				.'	var previous = document.getElementById(\'slide_\'+current);'."\n"
				.'	if(previous)'."\n"
				.'		previous.style.display = \'none\';'."\n"
				."\n"
				.'	// show selected slide'."\n"
				.'	current = handle.selectedIndex;'."\n"
				.'	if(handle = document.getElementById(\'slide_\'+current))'."\n"
				.'		handle.style.display = \'block\';'."\n"
				.'}'."\n"
				."\n"
				."\n"
				.'// display the very first slide of the list'."\n"
				.'function first_slide() {'."\n"
				."\n"
				.'	// hide slide displayed previously'."\n"
				.'	var handle = document.getElementById(\'slide_\'+current);'."\n"
				.'	if(handle && (current > 0))'."\n"
				.'		handle.style.display = \'none\';'."\n"
				."\n"
				.'	// show first slide'."\n"
				.'	current = 0;'."\n"
				.'	if(handle = document.getElementById(\'slide_\'+current))'."\n"
				.'		handle.style.display = \'block\';'."\n"
				."\n"
				.'	// update index of slides'."\n"
				.'	if(handle = document.getElementById("slide_list"))'."\n"
				.'		handle.selectedIndex = current;'."\n"
				.'}'."\n"
				."\n"
				."\n"
				.'// display previous slide'."\n"
				.'function previous_slide() {'."\n"
				."\n"
				.'	// show adjacent slide'."\n"
				.'	if(current-1 >= 0) {'."\n"
				."\n"
				.'		// hide slide displayed previously'."\n"
				.'		var handle = document.getElementById(\'slide_\'+current);'."\n"
				.'		if(handle)'."\n"
				.'			handle.style.display = \'none\';'."\n"
				."\n"
				.'		// show previous slide'."\n"
				.'		current--;'."\n"
				.'		if(handle = document.getElementById(\'slide_\'+current))'."\n"
				.'			handle.style.display = \'block\';'."\n"
				."\n"
				.'		// update index of slides'."\n"
				.'		if(handle = document.getElementById("slide_list"))'."\n"
				.'			handle.selectedIndex = current;'."\n"
				."\n"
				.'	// rotate to end of list'."\n"
				.'	} else'."\n"
				.'		last_slide();'."\n"
				.'}'."\n"
				."\n"
				."\n"
				.'// display next slide'."\n"
				.'function next_slide() {'."\n"
				."\n"
				.'	// show adjacent slide'."\n"
				.'	if(current+1 < '.count($files_in_path).') {'."\n"
				."\n"
				.'		// hide slide displayed previously'."\n"
				.'		var handle = document.getElementById(\'slide_\'+current);'."\n"
				.'		if(handle)'."\n"
				.'			handle.style.display = \'none\';'."\n"
				."\n"
				.'		// show next slide'."\n"
				.'		current++;'."\n"
				.'		if(handle = document.getElementById(\'slide_\'+current))'."\n"
				.'			handle.style.display = \'block\';'."\n"
				."\n"
				.'		// update index of slides'."\n"
				.'		if(handle = document.getElementById("slide_list"))'."\n"
				.'			handle.selectedIndex = current;'."\n"
				."\n"
				.'	// rotate to beginning of list'."\n"
				.'	} else'."\n"
				.'		first_slide();'."\n"
				.'}'."\n"
				."\n"
				."\n"
				.'// display the very last slide'."\n"
				.'function last_slide() {'."\n"
				."\n"
				.'	// hide slide displayed previously'."\n"
				.'	var handle = document.getElementById(\'slide_\'+current);'."\n"
				.'	if(handle && (current != '.(count($files_in_path)-1).'))'."\n"
				.'		handle.style.display = \'none\';'."\n"
				."\n"
				.'	// show last slide'."\n"
				.'	current = '.(count($files_in_path)-1).';'."\n"
				.'	if(handle = document.getElementById(\'slide_\'+current))'."\n"
				.'		handle.style.display = \'block\';'."\n"
				."\n"
				.'	// update index of slides'."\n"
				.'	if(handle = document.getElementById("slide_list"))'."\n"
				.'		handle.selectedIndex = current;'."\n"
				.'}'."\n"
				."\n"
				."\n"
				.'// time to sleep between slides, in milliseconds'."\n"
				.'var rotation_delay = 5000;'."\n"
				."\n"
				.'// flagging on-going rotation'."\n"
				.'var rotation_timer = null;'."\n"
				."\n"
				."\n"
				.'// start or stop the slideshow'."\n"
				.'function start_stop(handle) {'."\n"
				."\n"
				.'	// stop the slideshow'."\n"
				.'	if(rotation_timer != null) {'."\n"
				.'		window.clearTimeout(rotation_timer);'."\n"
				.'		rotation_timer = null;'."\n"
				."\n"
				.'		// the button to start the slideshow'."\n"
				.'		handle.value = "'.utf8::to_iso8859(i18n::s('Start')).'";'."\n"
				."\n"
				.'		// enable other controls'."\n"
				.'		if(handle = document.getElementById("slide_list"))'."\n"
				.'			handle.disabled = false;'."\n"
				.'		if(handle = document.getElementById(\'first_button\'))'."\n"
				.'			handle.disabled = false;'."\n"
				.'		if(handle = document.getElementById(\'previous_button\'))'."\n"
				.'			handle.disabled = false;'."\n"
				.'		if(handle = document.getElementById(\'next_button\'))'."\n"
				.'			handle.disabled = false;'."\n"
				.'		if(handle = document.getElementById(\'last_button\'))'."\n"
				.'			handle.disabled = false;'."\n"
				."\n"
				.'	// start the slideshow'."\n"
				.'	} else {'."\n"
				."\n"
				.'		// the button to stop the slideshow'."\n"
				.'		handle.value = "'.utf8::to_iso8859(i18n::s('Stop')).'";'."\n"
				."\n"
				.'		// disable other controls'."\n"
				.'		if(handle = document.getElementById("slide_list"))'."\n"
				.'			handle.disabled = true;'."\n"
				.'		if(handle = document.getElementById(\'first_button\'))'."\n"
				.'			handle.disabled = true;'."\n"
				.'		if(handle = document.getElementById(\'previous_button\'))'."\n"
				.'			handle.disabled = true;'."\n"
				.'		if(handle = document.getElementById(\'next_button\'))'."\n"
				.'			handle.disabled = true;'."\n"
				.'		if(handle = document.getElementById(\'last_button\'))'."\n"
				.'			handle.disabled = true;'."\n"
				."\n"
				.'		// start the rotation'."\n"
				.'		rotate_slide();'."\n"
//				.'		rotation_timer = window.setTimeout("rotate_slide()", rotation_delay);'."\n"
				.'	}'."\n"
				.'}'."\n"
				."\n"
				."\n"
				.'// change to next slide'."\n"
				.'function rotate_slide() {'."\n"
				.'	next_slide();'."\n"
				."\n"
				.'	// chain to next rotation'."\n"
				.'	rotation_timer = window.setTimeout("rotate_slide()", rotation_delay);'."\n"
				.'}'."\n"
				.'// ]]></script>'."\n";

		}
	}

}

// render the skin
render_skin();

?>