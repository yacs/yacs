<?php
/**
 * list all audio files in a playlist
 *
 * This script turns a YACS server to a pseudo-streaming server.
 * On intranets or at home, with VLC, Winamp or Windows Media Player installed at workstations, it allows people to view films on-demand.
 *
 * @link http://www.videolan.org/vlc/  VLC media player
 *
 * This script builds a paylist for following extensions:
 * - [code].au[/code]
 * - [code].mid[/code]
 * - [code].mp3[/code]
 * - [code].wav[/code]
 * - [code].wma[/code]
 * - [code].ra[/code]
 * - [code].snd[/code]
 *
 * The downloaded object is always cacheable, to avoid IE to remove it too early from temporary directory.
 *
 * @link http://www.webmasterworld.com/forum88/5891.htm Internet Explorer download problem
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
 * - play_audio.php?path=&lt;collection/path/to/browse&gt;
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Rodney Morison
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'collections.php';

// load the skin -- before loading the collection
load_skin('collections');

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

// icons used to depict files and folders
$icons = array();
$icons['folder_icon'] = '<img src="'.$context['url_to_root'].'skins/images/files_inline/folder.png" width="13" height="16" alt="" />';
$icons['folder_up_icon'] = '<img src="'.$context['url_to_root'].'skins/images/files_inline/folder_up.gif" width="15" height="16" alt="" />';

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

		// list parent containers, if any
		foreach($item['containers'] as $link => $label) {
			$context['prefix'] .= BR.'<a href="'.$link.'">'.$icons['folder_up_icon'].'</a> <a href="'.$link.'">'.$label.'</a>';
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

	// browse the path to list directories and files
	if(!$dir = Safe::opendir ($item['actual_path']))
		$context['text'] .= '<p>'.sprintf(i18n::s('The directory %s does not exist. Please check %s.'), $item['relative_path'], Skin::build_link('collections/browse.php?path='.$collection, i18n::s('the index page'), 'shortcut'))."</p>\n";

	// built the playlist
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
			if(!preg_match('/\.(au|mid|mp3|wav|wma|ra|snd)$/i', $node))
				continue;

			$files_in_path[] = $node;
		}
		Safe::closedir($dir);

		// fix relative url
		if($item['relative_url'])
			$item['relative_url'] .= '/';

		// load some file parser if one is available
		$analyzer = NULL;
		if(is_readable($context['path_to_root'].'included/getid3/getid3.php')) {
			include_once $context['path_to_root'].'included/getid3/getid3.php';
			$analyzer =& new getid3();
		}

		// list found files in a playlist
		sort($files_in_path);
		$text = '#EXTM3U'."\n";

		$index = 0;
		foreach($files_in_path as $file) {

			// parse file content, and streamline information
			$data = array();
			if(is_object($analyzer)) {
				$data = $analyzer->analyze($item['actual_path'].'/'.$file);
				getid3_lib::CopyTagsToComments($data);
			}

			// display a friendly name
			$target_name = $file;
			$name = array();
			if($value = @implode(' & ', @$data['comments_html']['artist']))
				$name[] = $value;
			if($value = @implode(', ', @$data['comments_html']['title']))
				$name[] = $value;
			if($name = @implode(' - ', $name))
				$target_name = $name;

			// duration
			if($target_duration = @$data['playtime_seconds'])
				$target_duration = round($target_duration);
			else
				$target_duration = '-1';

			// protect file origin, and set winamp headers
			if($context['with_friendly_urls'] == 'Y')
				$target_url = $context['url_to_home'].$context['url_to_root'].'collections/fetch.php/'.rawurlencode($item['collection']).'/'.$item['relative_url'].rawurlencode($file);
			else
				$target_url = $context['url_to_home'].$context['url_to_root'].'collections/fetch.php?path='.urlencode($item['collection'].'/'.$item['relative_url'].$file);

			$index++;
			$text .= '#EXTINF:'.$target_duration.','.utf8::to_iso8859(utf8::transcode($target_name))."\n"
				.$target_url."\n";
		}

		//
		// transfer to the user agent
		//

		// no encoding, no compression and no yacs handler...
		if(!headers_sent()) {
			Safe::header('Content-Type: audio/x-mpegurl');
			Safe::header('Content-Length: '.strlen($text));
		}

		// suggest a download
		if(!headers_sent()) {
			$file_name = utf8::to_ascii($id.'.m3u');
			Safe::header('Content-Disposition: attachment; filename="'.$file_name.'"');
		}

		// enable 30-minute caching (30*60 = 1800), even through https, to help IE6 on download
		if(!headers_sent()) {
			Safe::header('Expires: '.gmdate("D, d M Y H:i:s", time() + 1800).' GMT');
			Safe::header("Cache-Control: max-age=1800, public");
			Safe::header("Pragma: ");
		}

		// strong validation
		if((!isset($context['without_http_cache']) || ($context['without_http_cache'] != 'Y')) && !headers_sent()) {

			// generate some strong validator
			$etag = '"'.md5($text).'"';
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
		}

		// actual transmission except on a HEAD request
		if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
			echo $text;

		// the post-processing hook, then exit
		finalize_page(TRUE);

	}

	// the suffix
	if($item['collection_suffix'])
		$context['text'] .= '<p>'.Codes::beautify($item['collection_suffix'])."</p>\n";

}

// render the skin
render_skin();

?>