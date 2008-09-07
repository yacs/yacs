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
 * - [code].flv[/code] (load a flash player in full screen)
 * - [code].mm[/code] (load a flash or java player)
 * - [code].mov[/code] (through a [code].m3u[/code] redirector)
 * - [code].mp3[/code] (through a [code].m3u[/code] redirector)
 * - [code].mp4[/code] (through a [code].m3u[/code] redirector)
 * - [code].mpe[/code] (through a [code].m3u[/code] redirector)
 * - [code].mpeg[/code] (through a [code].m3u[/code] redirector)
 * - [code].mpg[/code] (through a [code].m3u[/code] redirector)
 * - [code].snd[/code] (through a [code].m3u[/code] redirector)
 * - [code].swf[/code]
 * - [code].vob[/code] (through a [code].m3u[/code] redirector)
 * - [code].wav[/code] (through a [code].m3u[/code] redirector)
 * - [code].wma[/code] (through a [code].wax[/code] redirector)
 * - [code].wmv[/code] (through a [code].wvx[/code] redirector)
 * - [code].ra[/code] (through a [code].ram[/code] redirector)
 *
 * @link http://www.spartanicus.utvinternet.ie/streaming.htm Streaming audio/video from a web server
 * @link http://forums.winamp.com/showthread.php?s=dbec47f3a05d10a3a77959f17926d39c&threadid=65772 The Unofficial M3U and PLS Specification
 *
 * The downloaded object is always cacheable, to avoid IE to remove it too early from temporary directory.
 *
 * @link http://www.webmasterworld.com/forum88/5891.htm Internet Explorer download problem
 *
 * For authentication on protected page this script use basic HTTP authentication.
 * This means that the anonymous surfer will have either to use the regular login page, or to provide name and password on a per-request basis.
 *
 * Per-request authentication is based on HTTP basic authentication mechanism, as explained in
 * [link=RFC2617]http://www.faqs.org/rfcs/rfc2617.html[/link].
 *
 * @link http://www.faqs.org/rfcs/rfc2617.html HTTP Authentication: Basic and Digest Access Authentication
 *
 * Accept following invocations:
 * - stream.php/12
 * - stream.php?id=12
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
	$behaviors =& new Behaviors($item, $anchor);

// check network credentials, if any -- used by winamp and other media players
if($user = Users::authenticate())
	Surfer::empower($user['capability']);

// associates and editors can do what they want
if(Surfer::is_empowered() || (is_object($anchor) && $anchor->is_editable()))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// access is restricted to authenticated member
elseif(isset($item['active']) && ($item['active'] == 'R') && Surfer::is_empowered('M'))
	$permitted = TRUE;

// access is restricted to authenticated associate
elseif(isset($item['active']) && ($item['active'] == 'N') && Surfer::is_empowered())
	$permitted = TRUE;

// public access is allowed
elseif(($item['active'] == 'Y') || ($item['active'] == 'X'))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// disable change commands
$editable = FALSE;

// except for associates and editors
if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable()))
	$editable = TRUE;

// and except for poster
if(Surfer::is($item['create_id']))
	$editable = TRUE;

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
if(isset($item['id']) && is_object($behaviors) && !$behaviors->allow('files/stream.php', 'file:'.$item['id']))
	$permitted = FALSE;

// back to the anchor page
if(is_object($anchor) && $anchor->is_viewable())
	$context['page_menu'] = array_merge($context['page_menu'], array( $anchor->get_url().'#files' => i18n::s('Back to main page') ));

// download command
if($item['id'] && $permitted)
	$context['page_menu'] = array_merge($context['page_menu'], array( Files::get_url($item['id'], 'view', $item['file_name']) => i18n::s('Download') ));

// edit command, if allowed to do so
if($item['id'] && $editable)
	$context['page_menu'] = array_merge($context['page_menu'], array( Files::get_url($item['id'], 'edit') => i18n::s('Edit') ));

// delete command provided to associates and editors
if($item['id'] && (Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable())))
	$context['page_menu'] = array_merge($context['page_menu'], array( Files::get_url($item['id'], 'delete') => i18n::s('Delete') ));

// not found
if(!$item['id']) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// give anonymous surfers a chance for HTTP authentication
	if(!Surfer::is_logged()) {
		Safe::header('WWW-Authenticate: Basic realm="'.utf8::to_iso8859($context['site_name']).'"');
		Safe::header('Status: 401 Unauthorized', TRUE, 401);
	}

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// do provide the file
} else {

	// increment the number of downloads
	Files::increment_hits($item['id']);

	// if we have an external reference, use it
	if(isset($item['file_href']) && $item['file_href']) {
		$target_href = $item['file_href'];

	// else redirect to ourself
	} else {

		// ensure a valid file name
		$file_name = utf8::to_ascii($item['file_name']);

		// where the file is
		$path = 'files/'.$context['virtual_path'].str_replace(':', '/', $item['anchor']).'/'.rawurlencode($item['file_name']);

		// map the file on the ftp server
		if($item['active'] == 'X') {
			Safe::load('parameters/files.include.php');
			$url_prefix = str_replace('//', '/', $context['files_url'].'/');

		// or map the file on the regular web space
		} else
			$url_prefix = $context['url_to_home'].$context['url_to_root'];


		// redirect to the actual file
		$target_href = $url_prefix.$path;
	}

	// determine attribute for this item
	$type = $mime = $text = '';

	// embed the file depending on the file type
	$extension = strtolower(@array_pop(@explode('.', @basename($item['file_name']))));
	switch($extension) {

	case 'aif':
	case 'aiff':
	case 'au':
	case 'mka':
	case 'mp3':
	case 'snd':
	case 'wav':
		// we are returning a .m3u
		$type = '.m3u';
		$mime = 'audio/x-mpegurl';

		// protect file origin, and set winamp headers
		$text = $context['url_to_home'].$context['url_to_root'].Files::get_url($item['id'], 'fetch', $item['file_name']);
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
		$type = '.m3u';
		$mime = 'audio/x-mpegurl';

		// where the file actually is
		$text = $target_href;

		break;

	case 'mm':
		// we are invoking some freemind viewer
		$type = '';
		$mime = 'text/html';

		// use the compressed format if possible
		if(file_exists($context['path_to_root'].'included/browser/swfobject.js.jsmin'))
			$script = 'included/browser/swfobject.js.jsmin';
		else
			$script = 'included/browser/swfobject.js';

		// page preamble
		$text = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML//EN">'."\n"
			.'<html>'."\n"
			.'<head>'."\n"
			.'<title>'.$item['title'].'</title>'."\n"
			.'<script type="text/javascript" src="'.$context['url_to_root'].$script.'"></script>'."\n"
			.'</head>'."\n"
			.'<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">'."\n";

		// render object full size
		$text .= Codes::render_freemind($target_href.', 100%, 100%');

		// page postamble
		$text .= '</body>'."\n"
				.'</html>'."\n";

		break;

	case 'flv':
	case 'swf':
		// display a large Flash file
		$type = '';
		$mime = 'text/html';

		// window title
		if(isset($item['title']) && $item['title'])
			$title = $item['title'];
		elseif(isset($item['file_name']) && $item['file_name'])
			$title = $item['file_name'];
		else
			$title = i18n::s('Stream');

		// page preamble
		$text = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML//EN">'."\n"
			.'<html>'."\n"
			.'<head>'."\n"
			.'<title>'.$title.'</title>'."\n";

		// use the compressed format if possible
		if(file_exists($context['path_to_root'].'included/browser/swfobject.js.jsmin'))
			$script = 'included/browser/swfobject.js.jsmin';
		else
			$script = 'included/browser/swfobject.js';
		$text .= '<script type="text/javascript" src="'.$context['url_to_root'].$script.'"></script>'."\n";

		// load javascript files from the skin directory -- e.g., Global Crossing js extensions, etc.
		if(isset($context['skin'])) {

			foreach(Safe::glob($context['path_to_root'].$context['skin'].'/*.js') as $name)
	 			$text .= '<script type="text/javascript" src="'.$context['url_to_root'].$context['skin'].'/'.basename($name).'"></script>'."\n";

		}

		// load skin style sheet
// 		if(isset($context['skin']))
// 			$text .= '<link rel="stylesheet" href="'.$context['url_to_root'].$context['skin'].'/'.str_replace('skins/', '', $context['skin']).'.css" type="text/css" media="all" />'."\n";

		// full screen
		$text .= '</head>'."\n"
			.'<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">'."\n"
			.'<div id="live_flash">'."\n";

		// render object full size
		$text .= Codes::render_object('flash', $item['id'].', 100%, 90%');

		// add a link to close the window
		$text .= '</div>'."\n"
			.'<p style="text-align: center; margin: 0.5em 0 1em 0;"><button type="button" onclick="self.close()">'.i18n::s('Close').'</button></p>'."\n";

		// page postamble
		$text .= '</body>'."\n"
				.'</html>'."\n";

		break;

	case 'wma':
		// we are returning a .wax
		$type = '.wax';
		$mime = 'audio/x-ms-wax';

		// where the file actually is
		$text = '<ASX VERSION="3.0">'."\n"
			.'	<ENTRY>'."\n"
			.'		<REF HREF="'.$target_href.'" />'."\n"
			.'	</ENTRY>'."\n"
			.'</ASX>';
		break;

	case 'wmv':
		// we are returning a .wvx
		$type = '.wvx';
		$mime = 'video/x-ms-wvx';

		// where the file actually is
		$text = '<ASX VERSION="3.0">'."\n"
			.'	<ENTRY>'."\n"
			.'		<REF HREF="'.$target_href.'" />'."\n"
			.'	</ENTRY>'."\n"
			.'</ASX>';
		break;

	case 'ra':
		// we are returning a .ram
		$type = '.ram';
		$mime = 'audio/x-pn-realaudio';

		// where the file actually is
		$text = $target_href;
		break;

	// default
	default:
		Skin::error(i18n::s('Do not know how to stream this file type'));
		break;

	}

	//
	// transfer to the user agent
	//

	// if we have a valid redirector
	if($mime && $text) {

		// no encoding, no compression and no yacs handler...
		if(!headers_sent()) {
			Safe::header('Content-Type: '.$mime);
			Safe::header('Content-Length: '.strlen($text));
		}

		// suggest a download
		if(!headers_sent()) {
			$file_name = utf8::to_ascii($item['file_name'].$type);
			Safe::header('Content-Disposition: inline; filename="'.$file_name.'"');
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

// render the skin
render_skin();

?>