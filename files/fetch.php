<?php
/**
 * download one file
 *
 * By default the script provides content of the target file.
 * Depending of the optional action parameter, behaviour is changed as follows:
 * - 'release' - assignment information is cleared, and no download takes place
 * - 'confirm' - force the download of a file
 * - 'reserve' - the file is assigned to the member who has downloaded the file
 *
 * File content is provided in pass-through mode most of the time, meaning this
 * script does not unveil the real web path to target file.
 *
 * This script is able to serve partial requests (e.g., from iPhone, iPod and iPad) if necessary
 *
 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.35.1 Byte Ranges
 *
 * Optionnally, this script also read internal information for some file types,
 * in order to enhance the provided listing.
 * This reading is based on the getid3() library, which is loaded if it is available.
 *
 * @link http://getid3.sourceforge.net/ getID3() the PHP media file parser
 *
 * In-place edition is provided through minimal WebDAV implementation.
 *
 * @link http://support.microsoft.com/kb/838028 How documents are opened from a Web site in Office 2003
 *
 * For authentication on protected page this script use basic HTTP authentication.
 * This means that the anonymous surfer will have either to use the regular
 * login page, or to provide name and password on a per-request basis.
 *
 * Per-request authentication is based on HTTP basic authentication mechanism,
 * as explained in [link=RFC2617]http://www.faqs.org/rfcs/rfc2617.html[/link].
 *
 * @link http://www.faqs.org/rfcs/rfc2617.html HTTP Authentication: Basic and Digest Access Authentication
 *
 * Accept following invocations:
 * - fetch.php/12
 * - fetch.php?id=12
 * - fetch.php/12/release
 * - fetch.php?id=12&action=release
 * - fetch.php/12/confirm
 * - fetch.php?id=12&action=confirm
 * - fetch.php/12/detach
 * - fetch.php?id=12&action=detach
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'files.php';
include_once '../users/activities.php'; // record file fetch

// check network credentials, if any -- used by winamp and other media players
if($user = Users::authenticate())
	Surfer::empower($user['capability']);

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// additional action, if any
$action = NULL;
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];
elseif(isset($context['arguments'][1]))
	$action = $context['arguments'][1];
$action = strip_tags($action);

// get the item from the database
$item =& Files::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// get related behaviors, if any
$behaviors = NULL;
include_once '../behaviors/behaviors.php';
if(isset($item['id']))
	$behaviors = new Behaviors($item, $anchor);

// public access is allowed
if(isset($item['active']) && ($item['active'] == 'Y'))
	$permitted = TRUE;

// access is restricted to authenticated member
elseif(isset($item['active']) && ($item['active'] == 'R') && Surfer::is_logged())
	$permitted = TRUE;

// the item is anchored to the profile of this member
elseif(Surfer::is_member() && !strcmp($item['anchor'], 'user:'.Surfer::get_id()))
	$permitted = TRUE;

// associates, editors and readers can view the page
elseif(Surfer::is_associate() || (is_object($anchor) && $anchor->is_assigned()))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('files', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'files/' => i18n::s('Files') );

// the title of the page
if($item['title'])
	$context['page_title'] = $item['title'];
else
	$context['page_title'] = str_replace('_', ' ', $item['file_name']);

// change default behavior
if(isset($item['id']) && is_object($behaviors) && !$behaviors->allow('files/fetch.php', 'file:'.$item['id']))
	$permitted = FALSE;

// not found
if(!isset($item['id']) || !$item['id']) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has been found.'));

// an anchor is mandatory
} elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No anchor has been found.'));

// permission denied
} elseif(!$permitted) {

	// give anonymous surfers a chance for HTTP authentication
	if(!Surfer::is_logged()) {
		Safe::header('WWW-Authenticate: Basic realm="'.utf8::to_iso8859($context['site_name']).'"');
		Safe::header('Status: 401 Unauthorized', TRUE, 401);
	}

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// enable remote updates through webDAV
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'OPTIONS')) {

	// file has to be mapped locally
	if(isset($item['file_href']) && $item['file_href']) {
		Safe::header('412 Precondition Failed');
		Logger::error('Cannot read file locally');

	// describe our capabilities
	} else {

		// WebDAV compliance -- either "1", or "1, 2", if locking is supported
		Safe::header('DAV: 1');

		// methods allowed --look at the switch above
		Safe::header('Allow: GET,HEAD,OPTIONS,PUT');

		// drive Microsoft clients to WebDAV instead of Frontpage protocol
		Safe::header('MS-Author-Via: DAV');

	}

// remote updates through webDAV
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'PUT')) {

	// file has to be mapped locally
	if(isset($item['file_href']) && $item['file_href']) {
		Safe::header('412 Precondition Failed');
		Logger::error('Cannot read file locally');

	// no content
	} elseif(!$input_handle = Safe::fopen("php://input", "rb")) {
		Safe::header('400 Client Error');
		Logger::error('Nothing to process');

	// ensure same MIME type
	} elseif(isset($_SERVER["CONTENT_TYPE"]) && ($_SERVER["CONTENT_TYPE"] != Files::get_mime_type($item['file_name']))) {
		Safe::header('409 Conflict');
		Logger::error('Unexpected Content-Type');

	// not allowed to write
	} elseif(!$output_handle = Safe::fopen($context['path_to_root'].Files::get_path($item['anchor']).'/'.rawurlencode($item['file_name']), "wb")) {
		Safe::header('500 Internal Server Error');
		Logger::error('Not allowed to write to local file');

	// do the write
	} else {

		// count every byte
		$new_size = 0;

		// process all the input stream
		while(!feof($input_handle)) {

			// read one chunk
			$buffer = fread($input_handle, 65536);
			$new_size += strlen($buffer);

			// write it
			if(!fwrite($output_handle, $buffer)) {
				Safe::header('500 Internal Server Error');
				Logger::error('Impossible to write to local file');
				break;
			}

		}
		fclose($output_handle);

		// also update the database
		$item['file_size'] = $new_size;
		$item['edit_name'] = $user['nick_name'];
		$item['edit_id'] = $user['id'];
		$item['edit_address'] = $user['email'];
		if(!$item['id'] = Files::post($item, 'A'))
			Safe::header('500 Internal Server Error');
		else {
			Files::clear($item);
			Safe::header('200 OK');
		}

	}

// clear assignment information, if any
} elseif(($action == 'release') && ($anchor->is_assigned() || (isset($item['assign_id']) && Surfer::is($item['assign_id'])))) {

	// change page title
	$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Release reservation'), $context['page_title']);

	// clear assignment information
	if(Files::assign($item['id'], NULL)) {

		// inform surfer
		$context['text'] .= '<p>'.i18n::s('You have released this file, and other surfers can reserve it for revision.').'</p>';

	// help the surfer
	} else
		Logger::error(i18n::s('Operation has failed.'));

	// follow-up commands
	$context['text'] .= Skin::build_block(Skin::build_link($anchor->get_url('files'), i18n::s('Done'), 'button'), 'bottom');

// file has not been assigned, and surfer has not confirmed the detach yet
} elseif(($action == 'reserve') && (!isset($item['assign_id']) || !$item['assign_id']) && Surfer::get_id()) {

	// change page title
	$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Reserve'), $context['page_title']);

	// assign the file to this surfer
	$user = array('nick_name' => Surfer::get_name(), 'id' => Surfer::get_id(), 'email' => Surfer::get_email_address());
	if(Files::assign($item['id'], $user)) {

		// inform surfer
		$context['text'] .= '<p>'.sprintf(i18n::s('You have reserved this file, and you are encouraged to %s as soon as possible, or to %s.'), Skin::build_link(Files::get_url($item['id'], 'edit'), i18n::s('upload an updated version'), 'basic'), Skin::build_link(Files::get_url($item['id'], 'fetch', 'release'), i18n::s('release reservation'), 'basic')).'</p>';

	// help the surfer
	} else
		Logger::error(i18n::s('Operation has failed.'));

	// follow-up commands
	$context['text'] .= Skin::build_block(Skin::build_link($anchor->get_url('files'), i18n::s('Done'), 'button'), 'bottom');

// file has been reserved, and surfer is not owner
} elseif(($action != 'confirm') && isset($item['assign_id']) && $item['assign_id'] && !Surfer::is($item['assign_id'])) {

	// inform surfer
	$context['text'] .= Skin::build_block(sprintf(i18n::s('This file has been assigned to %s %s, and it is likely that an updated version will be made available soon.'), Users::get_link($item['assign_name'], $item['assign_address'], $item['assign_id']), Skin::build_date($item['assign_date'])), 'caution');

	// commands
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Download this file'), NULL, NULL, 'confirmed', 'no_spin_on_click');
	$menu[] = Skin::build_link($anchor->get_url('files'), i18n::s('Cancel'), 'span');

	// to get the actual file
	$target_href = $context['url_to_home'].$context['url_to_root'].Files::get_url($item['id'], 'fetch', $item['file_name']);

	// render commands
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>'."\n"
		.Skin::finalize_list($menu, 'assistant_bar')
		.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
		.'<input type="hidden" name="action" value="confirm" />'."\n"
		.'</div></form>'."\n";

	// set the focus
	$context['text'] .= JS_PREFIX
		.'// set the focus on first form field'."\n"
		.'$("#confirmed").focus();'."\n"
		.JS_SUFFIX."\n";

//actual transfer
} elseif($item['id'] && $item['anchor']) {

	// increment the count of downloads
	if(!Surfer::is_crawler())
		Files::increment_hits($item['id']);

	// record surfer activity
	Activities::post('file:'.$item['id'], 'fetch');

	// if we have an external reference, use it
	if(isset($item['file_href']) && $item['file_href']) {
		$target_href = $item['file_href'];

	// we have direct access to the file
	} else {

		// ensure a valid file name
		$file_name = utf8::to_ascii($item['file_name']);

		// where the file is located
		$path = Files::get_path($item['anchor']).'/'.rawurlencode($item['file_name']);

		// file attributes
		$attributes = array();

		// map the file onto the regular web space
		$url_prefix = $context['url_to_home'].$context['url_to_root'];

		// transmit file content
		if(!headers_sent() && ($handle = Safe::fopen($context['path_to_root'].$path, "rb")) && ($stat = Safe::fstat($handle))) {

			// stream FLV files if required to do so
			if((substr($item['file_name'], -4) == '.flv') && isset($_REQUEST['position']) && ($_REQUEST['position'] > 0) && ($_REQUEST['position'] < $stat['size'])) {
				Safe::header('Content-Length: '.($stat['size']-$_REQUEST['position']+13));

				echo 'FLV'.pack('C', 1).pack('C', 1).pack('N', 9).pack('N', 9);
				fseek($handle, $_REQUEST['position']);
				echo fread($handle, ($stat['size']-$_REQUEST['position']));
				fclose($handle);
				return;
			}

			// load some file parser if one is available
			$analyzer = NULL;
			if(is_readable($context['path_to_root'].'included/getid3/getid3.php')) {
				include_once $context['path_to_root'].'included/getid3/getid3.php';
				$analyzer = new getid3();
			}

			// parse file content, and streamline information
			$data = array();
			if(is_object($analyzer)) {
				$data = $analyzer->analyze($context['path_to_root'].$path);
				getid3_lib::CopyTagsToComments($data);
			}

			// specific to audio files
			if(isset($data['mime_type']) && preg_match('%audio/(basic|mpeg|x-aiff|x-wave)%i', $data['mime_type'])) {

				// specific to Winamp
				Safe::header("icy-notice1: this requires winamp<BR>");
				Safe::header("icy-notice2: provided by a YACS server<BR>");
				Safe::header("icy-pub: 1");

				// name
				$name = array();
				if($value = implode(' & ', @$data['comments_html']['artist']))
					$name[] = $value;
				if($value = implode(', ', @$data['comments_html']['title']))
					$name[] = $value;
				if($name = implode(' - ', $name))
				Safe::header("icy-name: ".utf8::to_iso8859(utf8::transcode($name)));

				// genre
				if($value = implode(', ', @$data['comments_html']['genre']))
					Safe::header("icy-genre: ".utf8::to_iso8859(utf8::transcode($value)));

				// audio bitrate
				if($value = @$data['audio']['bitrate'])
					Safe::header("icy-br: ".substr($value, 0, -3));

				// this server
				Safe::header("icy-url: ".$context['url_to_home'].$context['url_to_root']);

			}

			// serve the right type
			Safe::header('Content-Type: '.Files::get_mime_type($item['file_name'], TRUE));

			// suggest a name for the saved file
			$file_name = str_replace('_', ' ', utf8::to_ascii($item['file_name']));
			Safe::header('Content-Disposition: attachment; filename="'.$file_name.'"');

			// we accepted (limited) range requests
			Safe::header('Accept-Ranges: bytes');

			// provide only a slice of the file
			if(isset($_SERVER['HTTP_RANGE']) && !strncmp('bytes=', $_SERVER['HTTP_RANGE'], 6)) {

				// maybe several ranges
				$range = substr($_SERVER['HTTP_RANGE'], 6);

				// process only the first range, if several are specified
				if($position = strpos($range, ','))
					$range = substr($range, 0, $position);

				// beginning and end of the range
				list($offset, $end) = explode('-', $range);
				$offset = intval($offset);
				if(!$end)
					$length = $stat['size'] - $offset;
				else
					$length = intval($end) - $offset + 1;

				// describe what is returned
				Safe::header('HTTP/1.1 206 Partial Content');
				Safe::header('Content-Range: bytes '.$offset .'-'.($offset + $length - 1).'/'.$stat['size']);

				// slice size
				Safe::header('Content-Length: '.$length);

				// actual transmission except on a HEAD request
				if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD')) {
					fseek($handle, $offset);
					$slice = fread($handle, $length);
					echo $slice;
				}
				fclose($handle);

			// regular download
			} else {

				// file size
				Safe::header('Content-Length: '.$stat['size']);

				// weak validator
				$last_modified = gmdate('D, d M Y H:i:s', $stat['mtime']).' GMT';

				// validate content in cache
				if(http::validate($last_modified))
					return;

				// actual transmission except on a HEAD request
				if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
					fpassthru($handle);
				fclose($handle);

			}

			// the post-processing hook, then exit even on HEAD
			finalize_page();
			return;

		}

		// redirect to the actual file
		$target_href = $url_prefix.$path;
	}

	// let the web server provide the actual file
	if(!headers_sent()) {
		Safe::header('Status: 302 Found', TRUE, 302);
		Safe::header('Location: '.$target_href);

	// this one may be blocked by anti-popup software
	} else
		$context['site_head'] .= '<meta http-equiv="Refresh" content="1;url='.$target_href.'" />'."\n";

	// help the surfer
	$context['text'] .= '<p>'.i18n::s('You are requesting the following file:').'</p>'."\n";

	$context['text'] .= '<p><a href="'.encode_field($target_href).'">'.basename($target_href).'</a></p>'."\n";

	// automatic or not
	$context['text'] .= '<p>'.i18n::s('The download should start automatically within seconds. Else hit the provided link to trigger it manually.').'</p>'."\n";

}

// render the skin
render_skin();

?>
