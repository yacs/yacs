<?php
/**
 * display one file in situation
 *
 * @todo Google Earth files http://www.booking.com/general.html?label=gog235jc;sid=e020ebf52ae94f62a347aa52f32e8998;tmpl=docs/google_earth
 *
 * This page is useful for displaying information on files to be downloaded.
 * Also, for some extensions YACS offers to stream file content as an alternative to the download.
 *
 * If several files have been posted to a single anchor, a navigation bar will be built to jump
 * directly to previous and next neighbours.
 * This is displayed as a sidebar box in the extra panel.
 *
 * The extra panel also features top popular referrals in a sidebar box, if applicable.
 *
 * Free icons used for OpenOffice.org files have been found at KDE-look.org.
 *
 * @link http://www.kde-look.org/content/show.php?content=6077 OpenOffice.org Slick style Icons
 *
 * Optionnally, this script also read internal information for some file types, in order to enhance the provided listing.
 * This reading is based on the getid3() library, which is loaded if it is available.
 *
 * @link http://getid3.sourceforge.net/ getID3() the PHP media file parser
 *
 * When the shared file is a Freemind map, and if the related Java applet is available,
 * YACS adds a link to use it from within the current browser window.
 *
 * Restrictions apply on this page:
 * - associates and editors are allowed to move forward
 * - permission is denied if the anchor is not viewable
 * - permission is granted if the anchor is the profile of this member
 * - authenticated users may view their own posts
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - view.php/12
 * - view.php?id=12
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Alain Lesage (Lasares)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../images/images.php';
include_once '../links/links.php';
include_once '../versions/versions.php';	// back in history
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

// associates, editors and readers can view this file
if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_assigned()))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// the item is anchored to the profile of this member
elseif(Surfer::is_member() && !strcmp($item['anchor'], 'user:'.Surfer::get_id()))
	$permitted = TRUE;

// authenticated users may view their own posts
elseif(isset($item['create_id']) && Surfer::is($item['create_id']))
	$permitted = TRUE;

// access is restricted to authenticated member
elseif(($item['active'] == 'R') && Surfer::is_member())
	$permitted = TRUE;

// public access is allowed
elseif($item['active'] == 'Y')
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// nothing to change
if(!isset($item['id']))
	$editable = FALSE;

// associates and editors are allowed to change the file
elseif(Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable()))
	$editable = TRUE;

// the original poster can change the file as well
elseif(Surfer::is($item['create_id']))
	$editable = TRUE;

// authenticated members are allowed to modify files from others
elseif(Surfer::is_member() && (!isset($context['users_without_file_overloads']) || ($context['users_without_file_overloads'] != 'Y')))
	$editable = TRUE;

// the default is to disable change commands
else
	$editable = FALSE;

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
if(isset($item['title']) && $item['title'])
	$context['page_title'] = $item['title'];
elseif(isset($item['file_name']))
	$context['page_title'] = str_replace('_', ' ', $item['file_name']);

// editors have associate-like capabilities
if(is_object($anchor) && $anchor->is_editable())
	Surfer::empower();

// change default behavior
if(isset($item['id']) && is_object($behaviors) && !$behaviors->allow('files/view.php', 'file:'.$item['id']))
	$permitted = FALSE;

// has this page some versions?
$has_versions = FALSE;
if(isset($item['id']) && (Surfer::is_empowered() && Surfer::is_logged()) && Versions::count_for_anchor('file:'.$item['id']))
	$has_versions = TRUE;

// not found -- help web crawlers
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Files::get_url($item['id'])));

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the page that describes the file
} else {

	// remember surfer visit
	Surfer::is_visiting(Files::get_url($item['id'], 'view', $item['file_name']), Codes::beautify_title($item['title']), 'file:'.$item['id'], $item['active']);

	// initialize the rendering engine
	Codes::initialize(Files::get_url($item['id'], 'view', $item['file_name']));

	// the file icon, if any
	if(isset($item['icon_url']) && $item['icon_url'])
		$context['page_image'] = $item['icon_url'];

	// do not mention details to crawlers
	if(!Surfer::is_crawler()) {

		// display the source, if any, but only to authenticated surfers
		if($item['source']) {
			if(preg_match('/http:\/\/([^\s]+)/', $item['source'], $matches))
				$item['source'] = Skin::build_link($matches[0], $matches[0], 'external');
			else {
				if($attributes = Links::transform_reference($item['source'])) {
					list($link, $title, $description) = $attributes;
					$item['source'] = Skin::build_link($link, $title);
				}
			}
			$context['page_details'] .= '<p>'.sprintf(i18n::s('Source: %s'), $item['source'])."</p>\n";
		}
	
		// all details
		$context['page_details'] .= '<p class="details">';
	
		// warns associate, poster and editor if not active
		if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable()) || Surfer::is($item['create_id'])) {
	
			// restricted to logged members
			if($item['active'] == 'R')
				$context['page_details'] .= RESTRICTED_FLAG.' '.i18n::s('Community - Access is restricted to authenticated members').BR."\n";
	
			// restricted to associates
			elseif($item['active'] == 'N')
				$context['page_details'] .= PRIVATE_FLAG.' '.i18n::s('Private - Access is restricted to selected persons').BR."\n";
	
		}
	
		$details = array();
	
		// information on upload
		if(Surfer::is_logged())
			$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));
		else
			$details[] = Skin::build_date($item['edit_date']);
	
		// all details
		$context['page_details'] .= ucfirst(implode(', ', $details));
	
		// reference this item
		if(Surfer::is_member())
			$context['page_details'] .= BR.sprintf(i18n::s('Use following codes to reference this item: %s'), '[file='.$item['id'].'] or [download='.$item['id'].']');
	
		// end of details
		$context['page_details'] .= '</p>';
		
	}

	// file details
	$context['text'] .= '<p>';

	// the file name
	if($item['title'])
		$context['text'] .= str_replace('_', ' ', $item['file_name']).BR;

	// some details
	$details = array();

	// file size
	if($item['file_size'] > 1)
		$details[] = Skin::build_number($item['file_size'], i18n::s('bytes'));

	// hits
	if($item['hits'] > 1)
		$details[] = Skin::build_number($item['hits'], i18n::s('downloads'));

	// file has been assigned
	if(isset($item['assign_id']) && $item['assign_id'])
		$details[] = DRAFT_FLAG.i18n::s('pending refresh');

	// display these details
	$context['text'] .= '<span class="details">'.ucfirst(implode(', ', $details)).'</span></p>';

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// if we have a local file
	if(!isset($item['file_href']) || !$item['file_href']) {

		// where the file is
		$path = $context['path_to_root'].'files/'.$context['virtual_path'].str_replace(':', '/', $item['anchor']).'/'.rawurlencode(utf8::to_ascii($item['file_name']));

		//load some file parser if one is available
		$analyzer = NULL;
		if(is_readable($context['path_to_root'].'included/getid3/getid3.php')) {
			include_once $context['path_to_root'].'included/getid3/getid3.php';
			$analyzer =& new getid3();
		}

		// parse file content, and streamline information
		$data = array();
		if(is_object($analyzer) && Files::is_stream($item['file_name']) ) {
			$data = $analyzer->analyze($path);
			getid3_lib::CopyTagsToComments($data);
		}

		// details
		$rows = array();

		// artist
		if($value = @implode(' & ', @$data['comments_html']['artist']))
			$rows[] = array(i18n::s('Artist'), $value);

		// title
		if($value = @implode(', ', @$data['comments_html']['title']))
			$rows[] = array(i18n::s('Title'), $value);

		// genre
		if($value = @implode(', ', @$data['comments_html']['genre']))
			$rows[] = array(i18n::s('Genre'), $value);

		// duration in minutes:seconds
		if($value = @$data['playtime_string'])
			$rows[] = array(i18n::s('Duration'), $value);

		// audio bitrate
		if($value = @$data['audio']['bitrate']) {

			if(preg_match('/000$/', $value))
				$value = substr($value, 0, -3).' kbps';
			else
				$value .= ' bps';

			$rows[] = array(i18n::s('Bitrate'), $value);
		}

		// something to display
		if(count($rows))
			$context['text'] .= Skin::table(NULL, $rows);

	}

	// display the full text
	$context['text'] .= Skin::build_block($item['description'], 'description');

	//
	// plugins
	//

	// offer audio streaming, where applicable
	if(Files::is_audio_stream($item['file_name'])) {

		// explain what streaming is about
		$description = '<p>'.sprintf(i18n::s('This file may be accessed on-demand. You may have to use an advanced media player such as %s (open source) or %s (free).'), Skin::build_link(i18n::s('http://www.videolan.org/vlc/'), i18n::s('VLC media player'), 'external'), Skin::build_link(i18n::s('www.winamp.com'), i18n::s('Winamp'), 'external')).'</p>';

		// the label
		Skin::define_img('FILES_PLAY_IMG', 'files/play.gif');
		$label = FILES_PLAY_IMG.' '.i18n::s('Play').' '.str_replace('_', ' ', $item['file_name']);

		// use a definition list to enable customization of the download box
		$context['text'] .= '<dl class="download">'
			.'<dt>'.Skin::build_link(Files::get_url($item['id'], 'stream', $item['file_name']), $label, 'basic', i18n::s('Start')).'</dt>'
			.'<dd>'.$description.'</dd></dl>'."\n";

	}

	// offer video streaming, where applicable
	if(Files::is_video_stream($item['file_name'])) {

		// explain what streaming is about
		$description = '<p>'.sprintf(i18n::s('This file may be accessed on-demand. You may have to use an advanced media player such as %s (open source) or %s (free).'), Skin::build_link(i18n::s('http://www.videolan.org/vlc/'), i18n::s('VLC media player'), 'external'), Skin::build_link(i18n::s('www.winamp.com'), i18n::s('Winamp'), 'external')).'</p>';

		// the label
		Skin::define_img('FILES_PLAY_IMG', 'files/play.gif');
		$label = FILES_PLAY_IMG.' '.i18n::s('Play').' '.str_replace('_', ' ', $item['file_name']);

		// use a definition list to enable customization of the download box
		$context['text'] .= '<dl class="download">'
			.'<dt>'.Skin::build_link(Files::get_url($item['id'], 'stream', $item['file_name']), $label, 'basic', i18n::s('Start')).'</dt>'
			.'<dd>'.$description.'</dd></dl>'."\n";

	}

	// start a Flash show
	if(preg_match('/\.swf$/i', $item['file_name'])) {

		// explain what a Flash show is
		$description = '<p>'.i18n::s('This file allows for interactions over the web. If some Flash player has been installed at your workstation, click on the link to start the show.').'</p>';

		// the label
		Skin::define_img('FILES_PLAY_IMG', 'files/play.gif');
		$label = FILES_PLAY_IMG.' '.i18n::s('Play').' '.str_replace('_', ' ', $item['file_name']);

		// where the file is
//		$path = $context['url_to_home'].$context['url_to_root'].'files/'.$context['virtual_path'].str_replace(':', '/', $item['anchor']).'/'.rawurlencode(utf8::to_ascii($item['file_name']));

		// use a definition list to enable customization of the download box
		$context['text'] .= '<dl class="download">'
			.'<dt>'.Skin::build_link(Files::get_url($item['id'], 'stream', $item['file_name']), $label, 'help', i18n::s('Start')).'</dt>'
			.'<dd>'.$description.'</dd></dl>'."\n";

	}

	// if a viewer exists, use it to display a freemind map
	if(preg_match('/\.mm$/i', $item['file_name']) && file_exists($context['path_to_root'].'included/browser/visorFreemind.swf')) {

		// explain what a Freemind file is
		$description = '<p>'.i18n::s('If Flash or Java has been installed at your workstation, click on the link to browse this mind map.').'</p>';

		// the label
		Skin::define_img('FILES_PLAY_IMG', 'files/play.gif');
		$label = FILES_PLAY_IMG.' '.sprintf(i18n::s('Browse %s'), str_replace('_', ' ', $item['file_name']));

		// hovering the link
		$title = i18n::s('Open this map');

		// use a definition list to enable customization of the download box
		$context['text'] .= '<dl class="download">'
			.'<dt>'.Skin::build_link(Files::get_url($item['id'], 'stream', $item['file_name']), $label, 'help', $title).'</dt>'
			.'<dd>'.$description.'</dd></dl>'."\n";

	}

	//
	// link to modify the file in-place
	//

//	// a MS-Word document
//	if(preg_match('/\.doc$/i', $item['file_name'])) {

//		// explain what in-place edition is
//		$description = '<p>'.i18n::s('This file can be modified directly over the web. If a recent version of Microsoft Word has been installed at your workstation, click on the link to launch it.).'</p>';

//		// the label
//		Skin::define_img('FILES_PLAY_IMG', 'files/play.gif');
//		$label = FILES_PLAY_IMG.' '.sprintf(i18n::s('Edit %s'), str_replace('_', ' ', $item['file_name']));

//		// hovering the link
//		$title = i18n::s('Start Microsoft Word');

//		// webdav access to the file
//		$path = $context['url_to_home'].$context['url_to_root'].Files::get_url($item['id'], 'author');

//		$context['text'] .= JS_PREFIX
//				.'// only for Windows users, sorry'."\n"
//				.'if(window.ActiveXObject)'."\n"
//				.'{'."\n"
//				."\n"
//			.'	function open_application(application_id, target_href)'."\n"
//			.'	{'."\n"
//			.'		var handle = new ActiveXObject(application_id);'."\n"
//			.'		if(handle != null)'."\n"
//			.'		{'."\n"
//				.'			handle.Visible = true;'."\n"
//				.'			handle.Documents.Open(target_href);'."\n"
//			.'		}'."\n"
//				.'	}'."\n"
//				."\n"
//				.'	function edit_in_word()'."\n"
//				.'	{'."\n"
//				.'		open_application(\'Word.Application\', \''.$path.'\');'."\n"
//				.'	}'."\n"
//				."\n"
//			.'	// use a definition list to enable customization of the download box'."\n"
//				.'	document.write(\'<dl class="download">\');'."\n"
//				.'	document.write(\'<dt><a onclick="edit_in_word()" title="'.addcslashes($title, "'").'">'.addcslashes($label, "'").'</a></dt>\');'."\n"
//				.'	document.write(\'<dd>'.addcslashes($description, "'").'</dd></dl>\');'."\n"
//			.'}'."\n"
//			.JS_SUFFIX."\n";

//	}

	//
	// link to download the file
	//

	// file has been assigned
	if(isset($item['assign_id']) && $item['assign_id']) {

		// surfer is the owner
		if(Surfer::is_member() && (Surfer::get_id() == $item['assign_id'])) {
			$context['text'] .= Skin::build_block(sprintf(i18n::s('This file has been assigned to you %s, and you are encouraged to %s as soon as possible.'), Skin::build_date($item['assign_date']), Skin::build_link(Files::get_url($item['id'], 'edit'), i18n::s('upload an updated version'), 'basic')), 'note');

		// file has been assigned to another surfer
		} else {
			$context['text'] .= Skin::build_block(sprintf(i18n::s('This file has been assigned to %s %s, and it is likely that an updated version will be made available soon.'), Users::get_link($item['assign_name'], $item['assign_address'], $item['assign_id']), Skin::build_date($item['assign_date']))
				.' '.i18n::s('You are encouraged to wait for a fresher version to be available before moving forward.'), 'caution');
		}

	}

	// download description
	$description = '';

	// add some help depending on the file type
	$extension = strtolower(@array_pop(@explode('.', @basename($item['file_name']))));
	switch($extension) {

	case '3gp': 	// video/3gpp
		$description = '<p>'.sprintf(i18n::s('You are about to download a small video. To take the most of it we recommend you to use %s (open source).'), Skin::build_link(i18n::s('http://www.videolan.org/vlc/'), i18n::s('VLC media player'), 'external')).'</p>';
		break;

	case 'ai':
	case 'eps':
	case 'ps':
		$description = '<p>'.sprintf(i18n::s('You are about to download a Postscript file. %s is a popular rendering platform (free for non-commercial use).'), Skin::build_link(i18n::s('http://www.cs.wisc.edu/~ghost/'), i18n::s('Ghostscript, Ghostview and GSview'), 'external')).'</p>';
		break;

	case 'ace':
	case 'arj':
	case 'bz':
	case 'bz2':
	case 'gz':
	case 'gtar':
	case 'rar':
	case 'tar':
	case 'tgz':
	case 'zip':
		$description = '<p>'.sprintf(i18n::s('You are about to download an archive file. Popular software to handle such file include %s (open source) and %s (freeware).'), Skin::build_link(i18n::s('http://www.7-zip.org/'), i18n::s('7-zip'), 'external'), Skin::build_link(i18n::s('www.ultimatezip.com'), i18n::s('ultimatezip'), 'external')).'</p>';
		break;

	case 'aif': 	// audio/aiff
	case 'aiff':	// audio/aiff
	case 'au':		// audio/basic
	case 'mid':
	case 'midi':
	case 'mka': 	// audio/x-matroska
	case 'mp2':
	case 'mp3':
	case 'mpga':
	case 'snd': 	// audio/basic
	case 'wav': 	// audio/x-wave
	case 'wma': 	// audio/x-ms-wma
		$description = '<p>'.sprintf(i18n::s('You are about to download a sound or music file. To take the most of it we recommend you to use %s (open source) or, alternatively, %s (free).'), Skin::build_link(i18n::s('http://www.videolan.org/vlc/'), i18n::s('VLC media player'), 'external'), Skin::build_link(i18n::s('www.winamp.com'), i18n::s('Winamp'), 'external')).'</p>';
		break;

	case 'asf': 	// video/x-ms-asf
	case 'avi':
	case 'divx':
	case 'mkv': 	// video/x-matroska
	case 'mpe': 	// video/mpeg
	case 'mpeg':	// video/mpeg
	case 'mpg': 	// video/mpeg
	case 'wmv': 	// video/x-ms-wmv
		$description = '<p>'.sprintf(i18n::s('You are about to download a movie file. To take the most of it please check the %s (open source) or, alternatively, the %s (free).'), Skin::build_link(i18n::s('http://www.videolan.org/vlc/'), i18n::s(' VLC media player'), 'external'), Skin::build_link(i18n::s('www.divx.com'), i18n::s('Divx Player'), 'external')).'</p>';
		break;

	case 'awk':
		$description = '<p>'.sprintf(i18n::s('You are about to download a AWK script file.')).'</p>';
		break;

	case 'cer':
		$description = '<p>'.sprintf(i18n::s('You are about to download a X.509 certificate.')).'</p>';
		break;

	case 'doc':		// application/msword
	case 'dot':
	case 'rtf':
		$description = '<p>'.sprintf(i18n::s('You are about to download a Microsoft Word document. If you do not have this software, you may download a free vizualiser from %s. Or you may prefer to use the free office solution from the %s.'), Skin::build_link(i18n::s('www.microsoft.com'), i18n::s('Microsoft web server'), 'external'), Skin::build_link(i18n::s('www.openoffice.org'), i18n::s('Open Office web server'), 'external')).'</p>';
		break;

	case 'dll':
	case 'exe':
		$description = '<p>'.sprintf(i18n::s('You are about to download an executable file.')).'</p>';
		break;

	case 'bmp':
	case 'gif': 	// image/gif
	case 'jpe': 	// image/jpeg
	case 'jpeg':	// image/jpeg
	case 'jpg': 	// image/jpeg
	case 'pic': 	// image/pict
	case 'pict':	// image/pict
	case 'png':
	case 'tif': 	// image/tiff
	case 'tiff':	// image/tiff
	case 'xbm': 	// image/x-xbitmap
		$description = '<p>'.sprintf(i18n::s('You are about to download an image file. Popular software to handle such file include %s (shareware) and %s (freeware).'), Skin::build_link(i18n::s('www.acdsee.com'), i18n::s('Acdsee'), 'external'), Skin::build_link(i18n::s('www.xnview.com'), i18n::s('xnview'), 'external')).'</p>';
		break;

	case 'css':
	case 'htm':
	case 'html':
	case 'xml':
		$description = '<p>'.sprintf(i18n::s('You are about to download a web page. This HTML document will be displayed within your browser as usual.')).'</p>';
		break;

	case 'eml':
		$description = '<p>'.sprintf(i18n::s('You are about to download a RFC822 message.')).'</p>';
		break;

	case 'gg':		// app/gg -- http://dev.w3.org/cvsweb/2006/waf/WAPF/WD-WAPF-REQ-20060726.html?rev=1.3
		$description = '<p>'.sprintf(i18n::s('You are about to download a Google Desktop gadget. Obviously this would require to use %s.'), Skin::build_link(i18n::s('http://desktop.google.com/'), i18n::s('Google Desktop'), 'external')).'</p>';
		break;

	case 'gpx':
		$description = '<p>'.sprintf(i18n::s('You are about to download a set of GPS points. To handle it, you may have to download %s.'), Skin::build_link(i18n::s('http://www.easygps.com/'), i18n::s('EasyGPS'), 'external')).'</p>';
		break;

	case 'latex':
		$description = '<p>'.sprintf(i18n::s('You are about to download some %s document.'), Skin::build_link(i18n::s('http://en.wikipedia.org/wiki/LaTeX'), i18n::s('LaTeX'))).'</p>';
		break;

	// playlists
	case 'm3u':
	case 'pls':
	case 'ram':
	case 'wax':
	case 'wvx':
		$description = '<p>'.sprintf(i18n::s('You are about to download a playlist. To take the most of it please check the %s (open source) or, alternatively, %s (free).'), Skin::build_link(i18n::s('http://www.videolan.org/vlc/'), i18n::s('VLC media player'), 'external'), Skin::build_link(i18n::s('www.winamp.com'), i18n::s('Winamp'), 'external')).'</p>';
		break;

	case 'mdb':
		$description = '<p>'.sprintf(i18n::s('You are about to download a Microsoft Access document. If you do not have this software, you can get more information at the %s.'), Skin::build_link(i18n::s('www.microsoft.com'), i18n::s('Microsoft web server'), 'external')).'</p>';
		break;

	case 'mm':
		$description = '<p>'.sprintf(i18n::s('You are about to download a %s file.'), Skin::build_link(i18n::s('http://freemind.sourceforge.net/wiki/index.php/Main_Page'), i18n::s('Freemind'), 'external')).'</p>';
		break;

	case 'mmap':
	case 'mmas':
	case 'mmat':
	case 'mmmp':
	case 'mmp':
	case 'mmpt':
		$description = '<p>'.sprintf(i18n::s('You are about to download a %s file.'), Skin::build_link(i18n::s('http://www.mindjet.com/'), i18n::s('MindManager'), 'external')).'</p>';
		break;

	case 'mo':
		$description = '<p>'.sprintf(i18n::s('You are about to download a set of translated strings.')).'</p>';
		break;

	case 'mov': 	// video/quicktime
	case 'qt':		// video/quicktime
		$description = '<p>'.sprintf(i18n::s('You are about to download a movie film. To use it, you may download the free QuickTime vizualiser from %s.'), Skin::build_link(i18n::s('www.apple.com'), i18n::s('Apple web server'), 'external')).'</p>';
		break;

	case 'mpp':
		$description = '<p>'.sprintf(i18n::s('You are about to download a Microsoft Project document. If you do not have this software, you can get more information at the %s.'), Skin::build_link(i18n::s('www.microsoft.com'), i18n::s('Microsoft web server'), 'external')).'</p>';
		break;

	case 'odb': // open document database
	case 'odc': // open document chart
	case 'odf': // open document formula
	case 'odg': // open document drawing
	case 'odi': // open document image
	case 'odp': // open document presentation
	case 'ods': // open document spreadsheet
	case 'odt': // open document text
	case 'odm': // open document master document
	case 'otg': // open document drawing template
	case 'oth': // open document html template
	case 'otp': // open document presentation template
	case 'ots': // open document spreadsheet template
	case 'ott': // open document text template
	case 'stc': // open document spreadsheet template
	case 'std': // open document drawing template
	case 'sti': // open document presentation template
	case 'stw': // open document text template
	case 'sxc': // open document spreadsheet
	case 'sxd': // open document drawing
	case 'sxg': // open document master document
	case 'sxi': // open document presentation
	case 'sxm': // open document formula
	case 'sxw': // open document text
		$description = '<p>'.sprintf(i18n::s('You are about to download an OpenDocument document or a StarOffice document. If you do not have a software able to handle it, you can get more information at the %s.'), Skin::build_link(i18n::s('www.openoffice.org'), i18n::s('OpenOffice web server'), 'external')).'</p>';
		break;

	case 'p12':
	case 'pfx':
		$description = '<p>'.sprintf(i18n::s('You are about to download a PKCS#12 certificate.')).'</p>';
		break;

	case 'pcap': // ethereal, wireshark
		$description = '<p>'.sprintf(i18n::s('You are about to download captured network packets in pcap format. To handle it, you may have to download %s, which has succeeded Ethereal as most popular open network protocol analyzer.'), Skin::build_link(i18n::s('http://www.wireshark.org/'), i18n::s('Wireshark'), 'external')).'</p>';
		break;

	case 'pcast': // apple itunes podcast
		$description = '<p>'.sprintf(i18n::s('You are about to download a podcast subscription file. To handle it, you may have to download %s.'), Skin::build_link(i18n::s('http://www.apple.com/'), i18n::s('iTunes'), 'external')).'</p>';
		break;

	case 'pdb':
	case 'prc':
		$description = '<p>'.sprintf(i18n::s('You are about to download a file for your Palm OS handheld device.')).'</p>';
		break;

	case 'pdf':
		$description = '<p>'.sprintf(i18n::s('You are about to download a PDF document. To use it, we recommend you to use the lightweight and free %s. Alternatively, you may download the original Acrobat reader software from %s.'), Skin::build_link(i18n::s('www.foxitsoftware.com'), i18n::s('Foxit Reader'), 'external'), Skin::build_link(i18n::s('www.adobe.com'), i18n::s('Adobe web server'), 'external')).'</p>'
			.'<p>'.i18n::s('If the Adobe plug-in reports an error, please save the file on some hard drive and use this local copy to launch the Acrobat software.').'</p>';
		break;

	case 'pgp':
		$description = '<p>'.sprintf(i18n::s('You are about to download a message signed by PGP.')).'</p>';
		break;

	case 'po':
		$description = '<p>'.sprintf(i18n::s('You are about to download a set of translated strings.')).'</p>';
		break;

	case 'pot':
		$description = '<p>'.sprintf(i18n::s('You are about to download a template of strings to be translated.')).'</p>';
		break;

	case 'ppd':
	case 'psd':
		$description = '<p>'.sprintf(i18n::s('You are about to download a Photoshop image file. Popular software to handle such file include %s (shareware) and %s (freeware).'), Skin::build_link(i18n::s('www.acdsee.com'), i18n::s('Acdsee'), 'external'), Skin::build_link(i18n::s('www.xnview.com'), i18n::s('xnview'), 'external')).'</p>';
		break;

	case 'ppt':
	case 'pps':
		$description = '<p>'.sprintf(i18n::s('You are about to download a Microsoft Powerpoint document. If you do not have this software, you may download a free vizualiser from %s. Or you may prefer to use the free office solution from the %s.'), Skin::build_link(i18n::s('www.microsoft.com'), i18n::s('Microsoft web server'), 'external'), Skin::build_link(i18n::s('www.openoffice.org'), i18n::s('Open Office web server'), 'external')).'</p>';
		break;

	case 'pub':
		$description = '<p>'.sprintf(i18n::s('You are about to download a Microsoft Publisher document. If you do not have this software, you can get more information at the %s.'), Skin::build_link(i18n::s('www.microsoft.com'), i18n::s('Microsoft web server'), 'external')).'</p>';
		break;

	case 'ra':
		$description = '<p>'.sprintf(i18n::s('You are about to download a Real Audio file.')).'</p>';
		break;

	case 'flv':
	case 'rf':
	case 'swc':
	case 'swf':
		$description = '<p>'.sprintf(i18n::s('You are about to download a Shockwave Flash file. A player is available for free at the %s.'), Skin::build_link(i18n::s('http://www.adobe.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash'), i18n::s('Adobe web server'), 'external')).'</p>';
		break;

	case 'rmp':
		$description = '<p>'.sprintf(i18n::s('You are about to download an %s file. Open Workbench is an open source desktop application that provides project scheduling and management functions. It is a free alternative to programs like Microsoft Project.'), Skin::build_link(i18n::s('http://www.openworkbench.org/'), i18n::s('Open Workbench'), 'external')).'</p>';
		break;

	case 'sql':
		$description = '<p>'.sprintf(i18n::s('You are about to download some SQL commands.')).'</p>';
		break;

	case 'tex':
		$description = '<p>'.sprintf(i18n::s('You are about to download some %s document.'), Skin::build_link(i18n::s('http://en.wikipedia.org/wiki/TeX'), i18n::s('TeX'))).'</p>';
		break;

	case 'texi':
	case 'texinfo':
		$description = '<p>'.sprintf(i18n::s('You are about to download some %s file. This format is used to document %s projects.'), Skin::build_link(i18n::s('http://www.gnu.org/software/texinfo/texinfo.html'), i18n::s('Texinfo')), Skin::build_link(i18n::s('http://www.gnu.org/'), i18n::s('GNU'))).'</p>';
		break;

	case 'txt':
		$description = '<p>'.sprintf(i18n::s('You are about to download some text.')).'</p>';
		break;

	case 'vsd':
		$description = '<p>'.sprintf(i18n::s('You are about to download a Microsoft Visio document. If you do not have this software, you can get more information at the %s.'), Skin::build_link(i18n::s('www.microsoft.com'), i18n::s('Microsoft web server'), 'external')).'</p>';
		break;

	case 'wri':
		$description = '<p>'.sprintf(i18n::s('You are about to download a Microsoft Write document. If you do not have this software, you can get more information at the %s.'), Skin::build_link(i18n::s('www.microsoft.com'), i18n::s('Microsoft web server'), 'external')).'</p>';
		break;

	case 'xls':
		$description = '<p>'.sprintf(i18n::s('You are about to download a Microsoft Excel document. If you do not have this software, you may download a free vizualiser from %s. Or you may prefer to use the free office solution from the %s.'), Skin::build_link(i18n::s('www.microsoft.com'), i18n::s('Microsoft web server'), 'external'), Skin::build_link(i18n::s('www.openoffice.org'), i18n::s('Open Office web server'), 'external')).'</p>';
		break;

	default:
		$description = '';
		break;
	}

	// estimated download time
	if($item['file_size'] > 0) {
		$description .= '<p style="clear: left;">'.i18n::s('Estimated download time:')."</p><ul>\n";

		// download time at 512k
		if($item['file_size'] > 3072000) {
			$minutes = round($item['file_size'] * 8 / (0.8*512000*60), 0);
			$description .= '<li>'.sprintf(i18n::s('%d minute(s) at %s'), $minutes, '512 kbps')."</li>\n";
		} else {
			$seconds = max(round($item['file_size'] * 8 / (0.8*512000), 0), 2);
			$description .= '<li>'.sprintf(i18n::s('%d second(s) at %s'), $seconds, '512 kbps')."</li>\n";
		}

		// download time at 56k
		if($item['file_size'] > 336000) {
			$minutes = round($item['file_size'] * 8 / (0.8*56000*60), 0);
			$description .= '<li>'.sprintf(i18n::s('%d minute(s) at %s'), $minutes, '56 kbps')."</li>\n";
		} else {
			$seconds = max(round($item['file_size'] * 8 / (0.8*56000), 0), 2);
			$description .= '<li>'.sprintf(i18n::s('%d second(s) at %s'), $seconds, '56 kbps')."</li>\n";
		}

		// download time at 28.8k
		if($item['file_size'] > 172800) {
			$minutes = round($item['file_size'] * 8 / (0.8*28800*60), 0);
			$description .= '<li>'.sprintf(i18n::s('%d minute(s) at %s'), $minutes, '28.8 kbps')."</li>\n";
		} else {
			$seconds = max(round($item['file_size'] * 8 / (0.8*28800), 0), 2);
			$description .= '<li>'.sprintf(i18n::s('%d second(s) at %s'), $seconds, '28.8 kbps')."</li>\n";
		}

		$description .= "</ul>\n";
	}

	// the download link
	$link = $context['url_to_root'].Files::get_url($item['id'], 'fetch', $item['file_name']);

	// hovering the link
	$title = i18n::s('Get a copy of this file');

	// file is available to download
	Skin::define_img('DOWNLOAD_IMG', 'files/download.gif');
	$label = '<a href="'.$link.'" title="'.encode_field($title).'" id="file_download">'.DOWNLOAD_IMG.' '.sprintf(i18n::s('Download %s'), str_replace('_', ' ', $item['file_name'])).'</a>';

	// use a definition list to enable customization of the download box
	$context['text'] .= '<dl class="download">'
		.'<dt>'.$label.'</dt>'
		.'<dd>'.$description.'</dd></dl>'."\n";

	// file is also available for detach
	if(isset($item['id']) && $editable && Surfer::may_upload() && Surfer::is_member() && (!isset($item['assign_id']) || ($item['assign_id'] < 1))) {

		// the detach link
		$link = $context['url_to_root'].Files::get_url($item['id'], 'detach');

		// hovering the link
		$title = i18n::s('Signal others that you are working on this file');

		// surfer is allowed to change the file
		$label = '<a href="'.$link.'" title="'.encode_field($title).'" id="file_detach">'.DOWNLOAD_IMG.' '.i18n::s('Detach the file if you are intended to change its content').'</a>';

		// add some explanations
		$description = i18n::s('You will get a copy of the file, and other surfers will be warned that you are working on it until next upload.');

		// use a definition list to enable customization of the detach box
		$context['text'] .= '<dl class="download detach">'
			.'<dt>'.$label.'</dt>'
			.'<dd>'.$description.'</dd></dl>'."\n";

	}

	// alternate link, if any
	if(isset($item['alternate_href']) && $item['alternate_href']) {

		// ed2k
		if(preg_match('/^ed2k:\/\//', $item['alternate_href']))
			$context['text'] .= '<p>'.sprintf(i18n::s('If you have installed some Overnet, Emule, or Edonkey client software, you may prefer click on the following ed2k link: %s'), Skin::build_link($item['alternate_href'], i18n::s('Download from peers'), 'external')).'</p>'."\n";

		// .torrent
		elseif(preg_match('/\.torrent$/', $item['alternate_href']))
			$context['text'] .= '<p>'.sprintf(i18n::s('If you have installed some BitTorrent client software, you may prefer click on the following torrent link: %s'), Skin::build_link($item['alternate_href'], i18n::s('Download from peers'), 'external')).'</p>'."\n";

		// something else
		else
			$context['text'] .= '<p>'.sprintf(i18n::s('You may prefer to use the following link: %s'), Skin::build_link($item['alternate_href'], 'Alternate link', 'external')).'</p>'."\n";

	}

	// anti-virus manifest
	$context['text'] .= "<p>".i18n::s('While every care has been taken to ensure that files published on this server have not been infected by any known virus, please always use and activate specialized software on your computer to achieve an optimal protection.')."</p>\n";

	// keywords for members
	if($item['keywords'] && Surfer::is_logged())
		$context['text'] .= "<p>".sprintf(i18n::s('Keywords: %s'), $item['keywords'])."</p>\n";

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	// page tools
	//

	// back to the anchor page
// 	if(is_object($anchor) && $anchor->is_viewable())
// 		$context['page_tools'][] = Skin::build_link($anchor->get_url().'#files', i18n::s('Back to main page'));

	// update tools
	if($editable) {

		// modify this page
		Skin::define_img('FILES_EDIT_IMG', 'files/edit.gif');
		$context['page_tools'][] = Skin::build_link(Files::get_url($item['id'], 'edit'), FILES_EDIT_IMG.i18n::s('Update this file'), 'basic', i18n::s('Press [e] to edit'), FALSE, 'e');

		// post an image, if upload is allowed
		if(Images::are_allowed($anchor, $item)) {
			Skin::define_img('IMAGES_ADD_IMG', 'images/add.gif');
			$context['page_tools'][] = Skin::build_link('images/edit.php?anchor='.urlencode('file:'.$item['id']), IMAGES_ADD_IMG.i18n::s('Add an image'), 'basic');
		}

	}

	// restore a previous version, if any
	if($has_versions && (Surfer::is_empowered() && Surfer::is_logged())) {
		Skin::define_img('FILES_VERSIONS_IMG', 'files/versions.gif');
		$context['page_tools'][] = Skin::build_link(Versions::get_url('file:'.$item['id'], 'list'), FILES_VERSIONS_IMG.i18n::s('Versions'));
	}
	
	// delete command provided to associates and editors
	if(isset($item['id']) && (Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable()))) {
		Skin::define_img('FILES_DELETE_IMG', 'files/delete.gif');
		$context['page_tools'][] = Skin::build_link(Files::get_url($item['id'], 'delete'), FILES_DELETE_IMG.i18n::s('Delete this file'));
	}

	// the navigation sidebar
	if(is_object($anchor)) {
		$neighbours = $anchor->get_neighbours('file', $item);
		$text .= Skin::neighbours($neighbours, 'sidebar');
	}

	// build a nice sidebar box
	if($text)
		$text =& Skin::build_box(i18n::s('Navigation'), $text, 'navigation', 'neighbours');

	$context['components']['neighbours'] = $text;

	// referrals, if any
	$context['components']['referrals'] =& Skin::build_referrals(Files::get_url($item['id']));

}

// render the skin
render_skin();

?>