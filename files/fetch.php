<?php
/**
 * download one file
 *
 * @todo allow for download resuming as in http://w-shadow.com/blog/2007/08/12/how-to-force-file-download-with-php/

 * By default the script provides content of the target file.
 * Depending of the optional action parameter, behaviour is changed as follows:
 * - 'clear' - assignment information is cleared, and no download takes place
 * - 'confirm' - force the download of a file
 * - 'detach' - the file is assigned to the member who has downloaded the file
 *
 * File content is provided in pass-through mode most of the time, meaning this
 * script does not unveil the real web path to target file.
 *
 * Restrictions apply on this page:
 * - associates and editors are allowed to move forward
 * - permission is denied if the anchor is not viewable
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y' or 'X')
 * - permission denied is the default
 *
 * Moreover, detach operations require the surfer to be an authenticated member.
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
 * - fetch.php/12/clear
 * - fetch.php?id=12&action=clear
 * - fetch.php/12/confirm
 * - fetch.php?id=12&action=confirm
 * - fetch.php/12/detach
 * - fetch.php?id=12&action=detach
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
include_once '../shared/global.php';
include_once 'files.php';

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
	$anchor = Anchors::get($item['anchor']);

// get related behaviors, if any
$behaviors = NULL;
include_once '../behaviors/behaviors.php';
if(isset($item['id']))
	$behaviors =& new Behaviors($item, $anchor);

// check network credentials, if any -- used by winamp and other media players
if($user = Users::authenticate())
	Surfer::empower($user['capability']);

// readers have additional rights
if(is_object($anchor) && $anchor->is_assigned())
	Surfer::empower('S');

// associates, editors and readers can view the page
if(Surfer::is_empowered('S') || (is_object($anchor) && $anchor->is_editable()))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// access is restricted to authenticated member
elseif(($item['active'] == 'R') && Surfer::is_empowered('M'))
	$permitted = TRUE;

// public access is allowed
elseif(($item['active'] == 'Y') || ($item['active'] == 'X'))
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
	Skin::error(i18n::s('No item has been found.'));

// permission denied
} elseif(!$permitted) {

	// give anonymous surfers a chance for HTTP authentication
	if(!Surfer::is_logged()) {
		Safe::header('WWW-Authenticate: Basic realm="'.utf8::to_iso8859($context['site_name']).'"');
		Safe::header('Status: 401 Unauthorized', TRUE, 401);
	}

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// enable remote updates through webDAV
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'OPTIONS')) {

	// file has to be mapped locally
	if((isset($item['file_href']) && $item['file_href']) || (isset($item['active']) && ($item['active'] == 'X'))) {
		Safe::header('412 Precondition Failed');
		Skin::error('Cannot read file locally');

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
	if((isset($item['file_href']) && $item['file_href']) || (isset($item['active']) && ($item['active'] == 'X'))) {
		Safe::header('412 Precondition Failed');
		Skin::error('Cannot read file locally');

	// no content
	} elseif(!$input_handle = Safe::fopen("php://input", "rb")) {
		Safe::header('400 Client Error');
		Skin::error('Nothing to process');

	// ensure same MIME type
	} elseif(isset($_SERVER["CONTENT_TYPE"]) && ($_SERVER["CONTENT_TYPE"] != Files::get_mime_type($item['file_name']))) {
		Safe::header('409 Conflict');
		Skin::error('Unexpected Content-Type');

	// not allowed to write
	} elseif(!$output_handle = Safe::fopen($context['path_to_root'].'files/'.$context['virtual_path'].str_replace(':', '/', $item['anchor']).'/'.rawurlencode($item['file_name']), "wb")) {
		Safe::header('500 Internal Server Error');
		Skin::error('Not allowed to write to local file');

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
				Skin::error('Impossible to write to local file');
				break;
			}

		}
		fclose($output_handle);

		// update the associate record in the database
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
} elseif(isset($item['assign_id']) && ($item['assign_id'] >= 1) && ($action == 'clear') && (Surfer::is_empowered() || ($item['assign_id'] == Surfer::get_id()))) {

	// clear assignment information
	if(!Files::assign($item['id'], NULL))
		Skin::error(i18n::s('An error has been encountered while clearing file assignment.'));

	// feed-back to surfer
	else
		$context['text'] .= '<p>'.i18n::s('The assignment has been successfully cleared, and the file is again available for download.').'</p>';

	// follow-up commands
	$follow_up = '<p>'.i18n::s('Where do you want to go now?').'</p>';
	$menu = array();
	if(is_object($anchor))
		$menu = array_merge($menu, array($anchor->get_url() => i18n::s('Main page')));
	$menu = array_merge($menu, array(Files::get_url($item['id'], 'view', $item['file_name']) => i18n::s('Download page')));
	$follow_up .= Skin::build_list($menu, 'page_menu');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

// file has not been assigned, and surfer has not confirmed the detach yet
} elseif((!isset($item['assign_id']) || !$item['assign_id']) && ($action == 'detach') && Surfer::is_member()) {

	// help on detach
	$context['text'] .= '<p>'.i18n::s('You have asked to detach the file, and this will be recorded by the server. You are expected to change the provided document, and then to upload an updated version here. In the meantime other surfers will be advised to delay their downloads.').'</p>';

	// help on download
	$context['text'] .= '<p>'.i18n::s('Alternatively, you may download a copy of this file for you own usage.').'</p>';

	// commands
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Detach this file'), NULL, NULL, 'confirmed');
	$menu[] = Skin::build_link(Files::get_url($item['id'], 'fetch', $item['file_name']), i18n::s('Download a copy'));
	if(isset($_SERVER['HTTP_REFERER']))
		$referer = $_SERVER['HTTP_REFERER'];
	else
		$referer = Files::get_url($item['id'], 'view', $item['file_name']);
	$menu[] = Skin::build_link($referer, i18n::s('Cancel'), 'span');

	// to get the actual file
	$target_href = $context['url_to_home'].$context['url_to_root'].Files::get_url($item['id'], 'fetch', $item['file_name']);

	// render commands
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="window.open(\''.encode_field($target_href).'\',\'_blank\',\'width=0,height=0\'); return true;" id="main_form"><div>'."\n"
		.Skin::finalize_list($menu, 'assistant_bar')
		.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
		.'<input type="hidden" name="action" value="confirm" />'."\n"
		.'</div></form>'."\n";

	// set the focus
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// set the focus on first form field'."\n"
		.'$("confirmed").focus();'."\n"
		.'// ]]></script>'."\n";

// file has not been assigned, and surfer has confirmed the detach
} elseif((!isset($item['assign_id']) || !$item['assign_id']) && ($action == 'confirm') && Surfer::is_member()) {

	// to get the actual file
	$target_href = $context['url_to_home'].$context['url_to_root'].Files::get_url($item['id'], 'fetch', $item['file_name']);

	// help the surfer
	$context['text'] .= '<p>'.i18n::s('You are requesting the following file:').'</p>'."\n";

	$context['text'] .= '<p><a href="'.encode_field($target_href).'">'.$item['file_name'].'</a></p>'."\n";

	// automatic or not
	$context['text'] .= '<p>'.i18n::s('The download should start automatically within seconds. Else hit the provided link to trigger it manually.').'</p>'."\n";

	// assign the file to this surfer
	$user = array('nick_name' => Surfer::get_name(), 'id' => Surfer::get_id(), 'email' => Surfer::get_email_address());
	if(Files::assign($item['id'], $user))
		$context['text'] .= '<p>'.i18n::s('Since the file has been assigned to you, other surfers will be discouraged to download copies from the server until you upload an updated version.').'</p>'."\n";

	// follow-up commands
	$follow_up = '<p>'.i18n::s('Where do you want to go now?').'</p>';
	$menu = array();
	if(is_object($anchor))
		$menu = array_merge($menu, array($anchor->get_url() => i18n::s('Main page')));
	$menu = array_merge($menu, array(Files::get_url($item['id'], 'view', $item['file_name']) => i18n::s('Download page')));
	$follow_up .= Skin::build_list($menu, 'page_menu');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

// file has been detached, and download has not been confirmed, and surfer is not owner
} elseif(isset($item['assign_id']) && ($item['assign_id'] >= 1) && !(($action == 'confirm') || ($item['assign_id'] == Surfer::get_id()))) {

	// inform surfer
	$context['text'] .= '<p>'.sprintf(i18n::s('This file has been assigned to %s %s, and it is likely that an updated version will be made available soon.'), Users::get_link($item['assign_name'], $item['assign_address'], $item['assign_id']), Skin::build_date($item['assign_date'])).'</p>';

	$context['text'] .= '<p>'.i18n::s('You are encouraged to wait for a fresher version to be available before moving forward.').'</p>';

	$context['text'] .= '<p>'.sprintf(i18n::s('Please note that the current shared version is %s if you absolutely require it.'), Skin::build_link(Files::get_url($item['id'], 'confirm'), i18n::s('still available for download'), 'basic')).'</p>'."\n";

	// follow-up commands
	$follow_up = '<p>'.i18n::s('Where do you want to go now?').'</p>';
	$menu = array();
	if(is_object($anchor))
		$menu = array_merge($menu, array($anchor->get_url() => i18n::s('Main page')));
	$menu = array_merge($menu, array(Files::get_url($item['id'], 'view', $item['file_name']) => i18n::s('Download page')));
	$follow_up .= Skin::build_list($menu, 'page_menu');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

// actual transfer
} elseif($item['id'] && $item['anchor']) {

	// increment the number of downloads
	Files::increment_hits($item['id']);

	// if we have an external reference, use it
	if(isset($item['file_href']) && $item['file_href']) {
		$target_href = $item['file_href'];

	// we have direct access to the file
	} else {

		// ensure a valid file name
		$file_name = utf8::to_ascii($item['file_name']);

		// where the file is
		$path = 'files/'.$context['virtual_path'].str_replace(':', '/', $item['anchor']).'/'.rawurlencode($item['file_name']);

		// file attributes
		$attributes = array();

		// map the file on the ftp server
		if($item['active'] == 'X') {
			Safe::load('parameters/files.include.php');
			$url_prefix = str_replace('//', '/', $context['files_url'].'/');

		// or map the file on the regular web space
		} else {
			$url_prefix = $context['url_to_home'].$context['url_to_root'];

			// maybe we will pass the file through
			if(!headers_sent() && ($handle = Safe::fopen($context['path_to_root'].$path, "rb")) && ($stat = Safe::fstat($handle))) {

				// suggest a name for the saved file
				$file_name = str_replace('_', ' ', utf8::to_ascii($item['file_name']));
				Safe::header('Content-Disposition: attachment; filename="'.$file_name.'"');

				// file size
				Safe::header('Content-Length: '.$stat['size']);

				// serve the right type
				Safe::header('Content-Type: '.Files::get_mime_type($item['file_name']));

				// load some file parser if one is available
				$analyzer = NULL;
				if(is_readable($context['path_to_root'].'included/getid3/getid3.php')) {
					include_once $context['path_to_root'].'included/getid3/getid3.php';
					$analyzer =& new getid3();
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

				// ETag will be weak if modification is too recent
				if(time() < $stat['mtime'] + 5)
					$weak = 'W/';
				else
					$weak = '';

				// an ETag similar to what Apache is doing -- http://search.cpan.org/src/TBONE/Apache-File-Resumable-1.1.1.1/Resumable.pm
				if($stat['mode'] != 0)
					$etag = sprintf('%s"%x-%x-%x"', $weak, $stat['ino'], $stat['size'], $stat['mtime']);
				else
					$etag = sprintf('%s"%x"', $weak, $stat['mtime']);
				Safe::header('ETag: '.$etag);

				// validate the content if hash is ok
				if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && is_array($if_none_match = explode(',', str_replace('\"', '"', $_SERVER['HTTP_IF_NONE_MATCH'])))) {
					foreach($if_none_match as $target) {
						if(trim($target) == $etag) {
							Safe::header('Status: 304 Not Modified', TRUE, 304);
							return;
						}
					}
				}

				// weak validator
				$last_modified = gmdate('D, d M Y H:i:s', $stat['mtime']).' GMT';
				Safe::header('Last-Modified: '.$last_modified);

				// validate the content if date of last modification is the same
				if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && ($if_modified_since = preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']))) {
					if(($if_modified_since == $last_modified) && !isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
						Safe::header('Status: 304 Not Modified', TRUE, 304);
						return;
					}
				}

				// actual transmission except on a HEAD request
				if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
					fpassthru($handle);
				fclose($handle);

				// the post-processing hook, then exit
				finalize_page(TRUE);

			}

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
		$context['site_head'] .= '<meta http-equiv="Refresh" content="1;url='.$target_href.'"'.EOT."\n";

	// help the surfer
	$context['text'] .= '<p>'.i18n::s('You are requesting the following file:').'</p>'."\n";

	$context['text'] .= '<p><a href="'.encode_field($target_href).'">'.basename($target_href).'</a></p>'."\n";

	// automatic or not
	$context['text'] .= '<p>'.i18n::s('The download should start automatically within seconds. Else hit the provided link to trigger it manually.').'</p>'."\n";

	// file has been detached
	if($file_has_been_detached)
		$context['text'] .= '<p>'.i18n::s('Since the file has been assigned to you, other surfers will be discouraged to download copies from the server until you upload an updated version.').'</p>'."\n";

}

// render the skin
render_skin();

?>