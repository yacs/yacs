<?php
/**
 * stream a file
 *
 * This script turns a YACS server into a pseudo-streaming server.
 * On intranets or at home, with VLC or Winamp or Windows Media Player installed at workstations, it allows people to view films on-demand.
 *
 * @link http://www.videolan.org/vlc/  VLC media player
 *
 * This script acts as a redirector for well-known types:
 * - [code].aif[/code] (through a [code].m3u[/code] redirector)
 * - [code].aiff[/code] (through a [code].m3u[/code] redirector)
 * - [code].asf[/code] (through a [code].m3u[/code] redirector)
 * - [code].au[/code] (through a [code].m3u[/code] redirector)
 * - [code].avi[/code] (through a [code].m3u[/code] redirector)
 * - [code].divx[/code] (through a [code].m3u[/code] redirector)
 * - [code].mka[/code] (through a [code].m3u[/code] redirector)
 * - [code].mkv[/code] (through a [code].m3u[/code] redirector)
 * - [code].mov[/code] (through a [code].m3u[/code] redirector)
 * - [code].mp3[/code] (through a [code].m3u[/code] redirector)
 * - [code].mp4[/code] (through a [code].m3u[/code] redirector)
 * - [code].mpe[/code] (through a [code].m3u[/code] redirector)
 * - [code].mpeg[/code] (through a [code].m3u[/code] redirector)
 * - [code].mpg[/code] (through a [code].m3u[/code] redirector)
 * - [code].snd[/code] (through a [code].m3u[/code] redirector)
 * - [code].vob[/code] (through a [code].m3u[/code] redirector)
 * - [code].wav[/code] (through a [code].m3u[/code] redirector)
 * - [code].wma[/code] (through a [code].wax[/code] redirector)
 * - [code].wmv[/code] (through a [code].wvx[/code] redirector)
 * - [code].ra[/code] (through a [code].ram[/code] redirector)
 *
 * @link http://www.spartanicus.utvinternet.ie/streaming.htm Streaming audio/video from a web server
 * @link http://forums.winamp.com/showthread.php?threadid=65772 The Unofficial M3U and PLS Specification
 * @link http://www.panix.com/web/faq/multimedia/streamed.html Streaming Examples
 * @link http://www.scriptwell.net/howtoplaysound.htm How To Play Sound
 * @link http://empegbbs.com/ubbthreads/showflat.php/Cat/0/Number/63847/Main/63330 How to get Winamp to show id3 info when streaming from the empeg via http
 *
 * The downloaded object is always cacheable, to avoid IE to remove it too early from temporary directory.
 *
 * @link http://www.webmasterworld.com/forum88/5891.htm Internet Explorer download problem
 *
 * For authentication on protected page this script use basic HTTP authentication.
 * This means that the anonymous surfer will have either to use the regular login page, or to provide name and password on a per-request basis.
 *
 * Accept following invocations:
 * - stream.php/collection/path/to/file
 * - stream.php?file=&lt;collection/path/to/file&gt;
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'collections.php';

// load the skin -- do it before loading the collection
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

// stream this entry
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
		$context['text'] .= '<p>'.sprintf(i18n::s('The file %s does not exist. Please check %s.'), $id, Skin::build_link('collections/browse.php?path='.$item['collection'], i18n::s('the index page'), 'shortcut'))."</p>\n";

	// build streaming redirector
	else {

		// depending on the file type
		switch($item['node_extension']) {

		case 'aif':
		case 'aiff':
		case 'au':
		case 'mka':
		case 'mp3':
		case 'snd':
		case 'wav':
			// we are returning a .m3u
			$type = 'm3u';
			$mime = 'audio/x-mpegurl';

			// protect file origin, and set winamp headers
			if($context['with_friendly_urls'] == 'Y')
				$text = $context['url_to_home'].$context['url_to_root'].'collections/fetch.php/'.rawurlencode($item['collection']).'/'.$item['relative_url'];
			else
				$text = $context['url_to_home'].$context['url_to_root'].'collections/fetch.php?path='.urlencode($item['id']);

			break;

		case 'asf':
		case 'avi':
		case 'divx':
		case 'mkv':
		case 'mov':
		case 'mp4':
		case 'mpe':
		case 'mpeg':
		case 'mpg':
		case 'vob':
			// we are returning a .m3u
			$type = 'm3u';
			$mime = 'audio/x-mpegurl';

			// where the file actually is
			$text = '#EXTM3U'."\n"
				.'#EXTINF:-1,'.utf8::to_iso8859(utf8::transcode($item['node_label']))."\n"
				.$item['actual_url'];

			break;

		case 'wma':
			// we are returning a .wax
			$type = 'wax';
			$mime = 'audio/x-ms-wax';

			// where the file actually is
			$text = '<ASX VERSION="3.0">'."\n"
				.'	<ENTRY>'."\n"
				.'		<REF HREF="'.$item['actual_url'].'" />'."\n"
				.'	</ENTRY>'."\n"
				.'</ASX>';
			break;

		case 'wmv':
			// we are returning a .wvx
			$type = 'wvx';
			$mime = 'video/x-ms-wvx';

			// where the file actually is
			$text = '<ASX VERSION="3.0">'."\n"
				.'	<ENTRY>'."\n"
				.'		<REF HREF="'.$item['actual_url'].'" />'."\n"
				.'	</ENTRY>'."\n"
				.'</ASX>';
			break;

		case 'ra':
			// we are returning a .ram
			$type = 'ram';
			$mime = 'audio/x-pn-realaudio';

			// where the file actually is
			$text = $item['actual_url'];
			break;

		// default
		default:
			Logger::error(i18n::s('Do not know how to stream this file type.'));
			break;

		}

		//
		// transfer to the user agent
		//

		// if we have a valid redirector
		if($type && $mime && $text) {

			// no encoding, no compression and no yacs handler...
			if(!headers_sent()) {
				Safe::header('Content-Type: '.$mime);
				Safe::header('Content-Length: '.strlen($text));
			}

			// suggest a download
			if(!headers_sent()) {
				$file_name = utf8::to_ascii($id.'.'.$type);
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

	}

	// the suffix
	if($item['collection_suffix'])
		$context['text'] .= '<p>'.Codes::beautify($item['collection_suffix'])."</p>\n";

}

// render the skin
render_skin();

?>