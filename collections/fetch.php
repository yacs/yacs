<?php
/**
 * download one file
 *
 * This script provides the file, with advanced HTTP headers.
 *
 * Optionnally, this script also read internal information for some file types, in order to enhance the provided listing.
 * This reading is based on the getid3() library, which is loaded if it is available.
 *
 * @link http://getid3.sourceforge.net/ getID3() the PHP media file parser
 *
 * For authentication on protected page this script use basic HTTP authentication.
 * This means that the anonymous surfer will have either to use the regular login page, or to provide name and password on a per-request basis.
 *
 * Accept following invocations:
 * - fetch.php/collection/path/to/file
 * - fetch.php?file=&lt;collection/path/to/file&gt;
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'collections.php';

// check network credentials, if any -- used by winamp and other media players
if($user = Users::authenticate())
	Surfer::empower($user['capability']);

// load the skin -- before loading the collection
load_skin('collections');

// the item to browse
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

// icons used to depict files and folders
$icons = array();
$icons['folder_icon'] = '<img src="'.$context['url_to_root'].'skins/_reference/files_inline/folder.png" width="13" height="16" alt="" />';
$icons['folder_up_icon'] = '<img src="'.$context['url_to_root'].'skins/_reference/files_inline/folder_up.gif" width="15" height="16" alt="" />';

// the path to this page
$context['path_bar'] = array( 'collections/' => i18n::s('File collections') );

// the collection has to exist
if(!isset($item['collection']) || !$item['collection']) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	$context['page_title'] = i18n::s('Unknown collection');
	Logger::error(i18n::s('The collection asked for is unknown.'));

// access is prohibited
} elseif((($item['collection_visibility'] == 'N') && !Surfer::is_empowered())
	|| (($item['collection_visibility'] == 'R') && !Surfer::is_empowered('M'))) {

	// give anonymous surfers a chance for HTTP authentication
	if(!Surfer::is_logged()) {
		Safe::header('WWW-Authenticate: Basic realm="'.utf8::to_iso8859($context['site_name']).'"');
		Safe::header('Status: 401 Unauthorized', TRUE, 401);
	}

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	$context['page_title'] = i18n::s('Restricted access');
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// fetch this file
} else {

	// bread crumbs to upper levels, if any
	$context['path_bar'] = array_merge($context['path_bar'], $item['containers']);

	// list where we are
	if(isset($item['node_label'])) {
		$context['page_title'] = $item['node_label'];

		// list parent containers, if any
		foreach($item['containers'] as $link => $label) {
			$context['prefix'] .= '<a href="'.$link.'">'.$icons['folder_up_icon'].'</a> <a href="'.$link.'">'.$label.'</a>';
		}

	// houston, we've got a problem
	} else
		$context['page_title'] = i18n::s('Untitled collection');

	// the description is set at the collection index page
	if($item['collection_description'])
		$context['text'] .= '<p>'.Codes::beautify($item['collection_description'])."</p>\n";

	// the prefix on non-index pages
	if($item['collection_prefix'])
		$context['text'] .= '<p>'.Codes::beautify($item['collection_prefix'])."</p>\n";

	// ensure the target file exists
	if(!is_readable($item['actual_path']))
		$context['text'] .= '<p>'.sprintf(i18n::s('The file %s does not exist. Please check %s.'), $id, Skin::build_link('collections/browse.php?path='.$collection, i18n::s('the index page'), 'shortcut'))."</p>\n";

	// provides the file
	else {

		// load some file parser if one exists
		$analyzer = NULL;
		if(is_readable($context['path_to_root'].'included/getid3/getid3.php')) {
			include_once $context['path_to_root'].'included/getid3/getid3.php';
			$analyzer = new getid3();
		}

		// parse file content, and streamline information
		$data = array();
		if(is_object($analyzer)) {
			$data = $analyzer->analyze($item['actual_path']);
			getid3_lib::CopyTagsToComments($data);
		}

		// provides audio directly
		if(preg_match('%audio/(basic|mpeg|x-aiff|x-wave)%i', $data['mime_type']) && ($handle = Safe::fopen($item['actual_path'], "rb"))) {

			// standard HTTP
			Safe::header("Content-Type: ".$data['mime_type']);

			// file size
			if($size = Safe::filesize($item['actual_path']))
				Safe::header('Content-Length: '.$size);

			// provide a valid file name
			$file_name = utf8::to_ascii($item['node_name']);
			Safe::header('Content-Disposition: inline; filename="'.$file_name.'"');

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
			if(!$name = implode(' - ', $name))
				$name = $item['node_label'];
			Safe::header("icy-name: ".utf8::to_iso8859(utf8::transcode($name)));

			// genre
			if($value = implode(', ', @$data['comments_html']['genre']))
				Safe::header("icy-genre: ".utf8::to_iso8859(utf8::transcode($value)));

			// audio bitrate
			if($value = @$data['audio']['bitrate'])
				Safe::header("icy-br: ".substr($value, 0, -3));

			// this server
			Safe::header("icy-url: ".$context['url_to_home'].$context['url_to_root']);

			// actual transmission except on a HEAD request
			if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
				fpassthru($handle);
			fclose($handle);

			// the post-processing hook, then exit
			finalize_page(TRUE);

		}

		// let the web server provide the actual file
		if(!headers_sent()) {
			Safe::header('Status: 301 Moved Permanently', TRUE, 301);
			Safe::header('Location: '.$item['actual_url']);

		// this one may be blocked by anti-popup software
		} else
			$context['site_head'] .= '<meta http-equiv="Refresh" content="1;url='.$item['actual_url'].'" />'."\n";

		// help the surfer
		$context['text'] .= '<p>'.i18n::s('You are requesting the following file:').'</p>'."\n";

		$context['text'] .= '<p>'.Skin::build_link($item['actual_url']).'</p>'."\n";

		// automatic or not
		$context['text'] .= '<p>'.i18n::s('The download should start automatically within seconds. Else hit the provided link to trigger it manually.').'</p>'."\n";

	}

	// the suffix
	if($item['collection_suffix'])
		$context['text'] .= '<p>'.Codes::beautify($item['collection_suffix'])."</p>\n";

}

// render the skin
render_skin();

?>