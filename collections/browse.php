<?php
/**
 * browse a collection
 *
 * This script lists directories and files available at some path of one collection.
 *
 * Most files are provided for download, while some extensions (e.g., video and audio files) are redirected to the streaming script.
 *
 * @see collections/stream.php
 *
 * If the directory contains some audio files, a link is added to download a playlist that includes all audio files.
 *
 * @see collections/play_audio.php
 *
 * If the directory contains some image files, a link is added to a slideshow includes all images.
 *
 * @see collections/play_slideshow.php
 *
 * Optionnally, this script also read internal information for some file types, in order to enhance the provided listing.
 * This reading is based on the getid3() library, which is loaded if it is available.
 *
 * @link http://getid3.sourceforge.net/ getID3() the PHP media file parser
 *
 * You can provide additional information to surfers for each directory by adding
 * [code].header[/code] and [code].footer[/code] files in directories to be documented.
 * If present, these files will be included by YACS at top and bottom of the index page, respectively.
 *
 * Following files are never listed by YACS:
 * - files with names prefixed by '.' (like [code].htaccess[/code])
 * - files starting with a '~'
 * - thumbs.db
 * - pspbrwse.jbf
 * - index.php
 *
 * For authentication on protected page this script use basic HTTP authentication.
 * This means that the anonymous surfer will have either to use the regular login page, or to provide name and password on a per-request basis.
 *
 * Accept following invocations:
 * - browse.php?path=&lt;collection/path/to/browse&gt;
 * - browse.php/collection/path/to/browse;
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'collections.php';

// check network credentials, if any
if($user = Users::authenticate())
	Surfer::empower($user['capability']);

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
$icons['folder_icon'] = '<img src="'.$context['url_to_root'].'skins/_reference/files_inline/folder.png" width="13" height="16" alt="" />';
$icons['folder_up_icon'] = '<img src="'.$context['url_to_root'].'skins/_reference/files_inline/folder_up.gif" width="15" height="16" alt="" />';

// bullets used to describe streams and download
$bullets = array();
$bullets['download'] = '<img src="'.$context['url_to_root'].'skins/_reference/files_inline/qb_download.gif" width="11" height="11" alt=">>" />';
$bullets['movie'] = '<img src="'.$context['url_to_root'].'skins/_reference/files_inline/qb_movie.gif" width="13" height="11" alt=">>" />';
$bullets['sound'] = '<img src="'.$context['url_to_root'].'skins/_reference/files_inline/qb_sound.gif" width="11" height="11" alt=">>" />';

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
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	$context['page_title'] = i18n::s('Restricted access');
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// browse this place
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

	// cache the directory
	$cache_id = 'collections/browse.php?path='.$id;
	if(!$text = Cache::get($cache_id)) {

		// the description is set at the collection index page
		if($item['collection_description'])
			$text .= '<p>'.Codes::beautify($item['collection_description'])."</p>\n";

		// the prefix on non-index pages
		if($item['collection_prefix'])
			$text .= '<p>'.Codes::beautify($item['collection_prefix'])."</p>\n";

		// include the header file, if any
		$text .= Safe::file_get_contents($item['actual_path'].'/.header');

		// browse the path to list directories and files
		if(!$dir = Safe::opendir($item['actual_path'])) {
			$label = sprintf(i18n::s('The directory %s does not exist. Please check %s'), $item['actual_path'], Skin::build_link('collections/browse.php?path='.$item['collection'], i18n::s('the index page'), 'shortcut'));
			if(Surfer::is_associate())
				$label .= ' '.sprintf(i18n::s('Or check %s'), Skin::build_link('collections/configure.php', i18n::s('the configuration panel for collections'), 'shortcut'));
			$text .= '<p>'.$label."</p>\n";

		// list directories and files separately
		} else {

			// build the lists
			$directories_in_path = array();
			$files_in_path = array();
			$with_audio = FALSE;
			$with_slideshow = FALSE;
			while(($node = Safe::readdir($dir)) !== FALSE) {

				// skip protected files
				if(($node[0] == '.') || ($node[0] == '~'))
					continue;

				// skip special files
				if(preg_match('/^(index\.php|pspbrwse\.jbf|thumbs\.db)$/i', $node))
					continue;

				if(is_dir($item['actual_path'].'/'.$node))
					$directories_in_path[] = $node;
				else {
					$files_in_path[] = $node;

					// look for audio files
					$with_audio |= Files::is_audio_stream($node);

					// look for image files only
					if(preg_match('/\.(bmp|gif|jpe|jpeg|jpg|png)$/i', $node))
						$with_slideshow = TRUE;

				}
			}
			Safe::closedir($dir);

			// begin the list
			$text .= Skin::table_prefix('collections');

			// the header row
			$cells = array(' ', i18n::s('Name'), i18n::s('Size'), i18n::s('Modified'));
			$text .= Skin::table_row($cells, 'header');
			$count = 0;

			// list sub directories
			if(count($directories_in_path)) {

				// parse the list
				natsort($directories_in_path);
				foreach($directories_in_path as $node) {

					// one line per folder
					if($context['with_friendly_urls'] == 'Y')
						$link = 'browse.php/'.str_replace('//', '/', rawurlencode($item['collection']).'/'.$item['relative_url'].'/'.rawurlencode($node));
					else
						$link = 'browse.php?path='.urlencode(str_replace('//', '/', $item['collection'].'/'.$item['relative_path'].'/'.$node));
					$col1 = '<a href="'.$link.'" title="'.i18n::s('Browse this directory').'">'.$icons['folder_icon'].'</a>';
					$col2 = '<a href="'.$link.'" title="'.i18n::s('Browse this directory').'">'.str_replace(array('.', '_', '%20'), ' ', $node).'</a>';
					$text .= '<tr';
					if($count%2)
						$text .= ' class="odd"';
					$count++;
					$text .= '><td>'.$col1.'</td><td colspan="3">'.$col2.'</td></tr>';

				}
			}

			// play audio files, if any
			if($with_audio) {

				// use a friendly link if applicable
				if($context['with_friendly_urls'] == 'Y')
					$link = 'play_audio.php/'.str_replace('//', '/', rawurlencode($item['collection']).'/'.$item['relative_url']);
				else
					$link = 'play_audio.php?path='.urlencode(str_replace('//', '/', $item['collection'].'/'.$item['relative_path']));

				$col1 = '<a href="'.$link.'">'.Collections::get_icon_img('.mp3').'</a>';
				$col2 = '<a href="'.$link.'" title="'.i18n::s('Start a play-on-demand session').'">'.i18n::s('Play all music of this folder').'</a> <a href="'.$link.'" title="'.i18n::s('Start a play-on-demand session').'">'.$bullets['sound'].'</a>';
				$text .= '<tr';
				if($count%2)
					$text .= ' class="odd"';
				$count++;
				$text .= '><td>'.$col1.'</td><td colspan="3">'.$col2.'</td></tr>';
			}

			// slideshow, if any
			if($with_slideshow) {

				// use a friendly link if applicable
				if($context['with_friendly_urls'] == 'Y')
					$link = 'play_slideshow.php/'.str_replace('//', '/', rawurlencode($item['collection']).'/'.$item['relative_url']);
				else
					$link = 'play_slideshow.php?path='.urlencode(str_replace('//', '/', $item['collection'].'/'.$item['relative_path']));

				$col1 = '<a href="'.$link.'">'.Collections::get_icon_img('.mpg').'</a>';
				$col2 = '<a href="'.$link.'" title="'.i18n::s('A pleasant way to watch photos and artwork').'">'.i18n::s('Start a slideshow with all images of this folder').'</a> <a href="'.$link.'" title="'.i18n::s('A pleasant way to watch photos and artwork').'"></a>';
				$text .= '<tr';
				if($count%2)
					$text .= ' class="odd"';
				$count++;
				$text .= '><td>'.$col1.'</td><td colspan="3">'.$col2.'</td></tr>';
			}

			// list files
			if(count($files_in_path)) {

				// load the external file parser, if available
				$analyzer = NULL;
				if(is_readable($context['path_to_root'].'included/getid3/getid3.php')) {
					include_once $context['path_to_root'].'included/getid3/getid3.php';
					$analyzer = new getid3();
				}

				// parse the list
				natsort($files_in_path);
				foreach($files_in_path as $node) {

					// process file name
					$file_name = $node;
					$file_extension = '';

					// find the type
					$position = strrpos($node, '.');
					if($position !== FALSE) {
						$file_name = substr($node, 0, $position);
						$file_extension = strtolower(substr($node, $position+1));
					}

					// enhance the name
					$target_name = str_replace(array('.', '_', '%20'), ' ', $file_name);
					if($file_extension)
						$target_name .= '.'.$file_extension;

					// where the file is in the file path
					$target_path = $item['actual_path'].'/'.$node;

					// in-line additional elements
					$prefix = $suffix = $hover = '';

					// flag new files
					if(Safe::filemtime($target_path) >= $context['fresh'])
						$prefix .= NEW_FLAG;

					// stream audio
					if(Files::is_audio_stream($node)) {

						// parse file content, and streamline information
						$data = array();
						if(is_object($analyzer)) {
							$data = $analyzer->analyze($target_path);
							getid3_lib::CopyTagsToComments($data);
						}

						// display a friendly name
						$name = array();
						if($value = @implode(' & ', @$data['comments_html']['artist']))
							$name[] = $value;
						if($value = @implode(', ', @$data['comments_html']['title']))
							$name[] = $value;
						if($name = implode(' - ', $name))
						$target_name = $name;

						// hovering details
						$details = array();

						// playtime
						if(isset($data['playtime_string']))
							$details[] = sprintf(i18n::s('Duration: %s'), $data['playtime_string']);

						// show codec
						if(isset($data['audio']['codec']))
							$details[] = sprintf(i18n::s('Codec: %s'), $data['audio']['codec']);

						// build the hovering title
						if(count($details))
							$hover = implode(', ', $details);
						else
							$hover = i18n::s('Start a play-on-demand session');

						// use a friendly link if applicable
						if($context['with_friendly_urls'] == 'Y')
							$target_url = 'stream.php/'.str_replace('//', '/', rawurlencode($item['collection']).'/'.$item['relative_url'].'/'.rawurlencode($node));
						else
							$target_url = 'stream.php?path='.urlencode(str_replace('//', '/', $item['collection'].'/'.$item['relative_path'].'/'.$node));

						// change title for subsequent links
// 						$trailer = ' <a href="'.$target_url.'" title="'.i18n::s('Start a play-on-demand session').'">'.$bullets['sound'].'</a>';

						// also allow for download
						$target_url = $item['actual_url'].'/'.rawurlencode($node);
						$trailer = ' <a href="'.$target_url.'" title="'.i18n::s('Download this file').'">'.$bullets['download'].'</a>';

					// this is a video video
					} elseif(preg_match('/\.(3gp|asf|avi|flv|mkv|mp4|m4v|mov|mp4|mpg|wmv)$/i', $node)) {

						// parse file content, and streamline information
						$data = array();
						if(is_object($analyzer)) {
							$data = $analyzer->analyze($target_path);
							getid3_lib::CopyTagsToComments($data);
						}

						// display a friendly name
						$name = array();
						if($value = @implode(' & ', @$data['comments_html']['artist']))
							$name[] = $value;
						if($value = @implode(', ', @$data['comments_html']['title']))
							$name[] = $value;
						if($name = implode(' - ', $name))
						$target_name = $name;

						// hovering details
						$details = array();

						// playtime
						if(isset($data['playtime_string']))
							$details[] = sprintf(i18n::s('Duration: %s'), $data['playtime_string']);

						// show resolution
						if(isset($data['video']['resolution_x']) && isset($data['video']['resolution_y']))
							$details[] = $data['video']['resolution_x'].'x'.$data['video']['resolution_y'];

						// show codec
						if(isset($data['video']['codec']))
							$details[] = $data['video']['codec'];

						// build the hovering title
						if(count($details))
							$hover = implode(', ', $details);
						else
							$hover = i18n::s('Start a video-on-demand session');

						// use a friendly link if applicable
						if($context['with_friendly_urls'] == 'Y')
							$target_url = 'stream.php/'.str_replace('//', '/', rawurlencode($item['collection']).'/'.$item['relative_url'].'/'.rawurlencode($node));
						else
							$target_url = 'stream.php?path='.urlencode(str_replace('//', '/', $item['collection'].'/'.$item['relative_url'].'/'.$node));

						// insist on streaming
// 						$suffix = ' <a href="'.$target_url.'" title="'.i18n::s('Start a video-on-demand session').'">'.$bullets['movie'].'</a>';

						// also allow for download
						$target_url = $item['actual_url'].'/'.rawurlencode($node);
						$suffix .= ' <a href="'.$target_url.'" title="'.i18n::s('Download this file').'">'.$bullets['movie'].'</a>';

					// else link directly to the file
					} else {
						$target_url = $item['actual_url'].'/'.rawurlencode($node);
						$hover = i18n::s('Fetch this file');
					}

					// first column - the icon
					$col1 = '<a href="'.$target_url.'" title="'.$hover.'">'.Collections::get_icon_img($node).'</a>';

					// second column - the name of the file
					$col2 = $prefix.'<a href="'.$target_url.'" title="'.$hover.'">'.$target_name.'</a>'.$suffix;

					// third column - size of this file
					$file_size = Safe::filesize($target_path);
					$col3 = number_format($file_size);

					// fourth column - stamp of this file
					$col4 = Skin::build_date(Safe::filemtime($target_path), 'no_hour');

					// layout everything
					$text .= Skin::table_row(array($col1, $col2, $col3, $col4), $count++);

				}
			}

			// end the list
			$text .= Skin::table_suffix();
		}

		// include the footer file, if any
		$text .= Safe::file_get_contents($item['actual_path'].'/.footer');

		// the suffix on non-index pages
		if($item['collection_suffix'])
			$text .= '<p>'.Codes::beautify($item['collection_suffix'])."</p>\n";

		// up to one minute before file change is reflected on web page
		Cache::put($cache_id, $text, 'collections', 60);

	}

	// in the main panel
	$context['text'] .= $text;

	// help associates to update this directory
	if(Surfer::is_associate()) {

		if($context['with_friendly_urls'] == 'Y')
			$link = 'collections/upload.php/'.str_replace('//', '/', rawurlencode($item['collection']).'/'.$item['relative_url']);
		else
			$link = 'collections/upload.php?path='.urlencode(str_replace('//', '/', $item['collection'].'/'.$item['relative_path']));

		$context['page_tools'][] = Skin::build_link($link, i18n::s('Add a file'));
	}

	// general help
	$help = '<p>'.i18n::s('Click on file names to transfer them to your workstation, or to start a Play-on-demand session.').'</p>'
		.'<p>'.sprintf(i18n::s('If you are looking for a good piece of software to manage streaming music and video, download %s or %s.'), Skin::build_link(i18n::s('http://www.videolan.org/vlc/'), i18n::s('VLC media player'), 'external'), Skin::build_link(i18n::s('http://www.winamp.com/'), i18n::s('Winamp player'), 'external')).'</p>';
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');

}

// render the skin
render_skin();

?>
