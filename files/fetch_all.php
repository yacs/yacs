<?php
/**
 * download up to 20 files attached to some anchor
 *
 * This script builds an archive file and sent it to the user agent.
 * Moreover, files are dynamically compressed.
 *
 * Restrictions apply on this page:
 * - associates and editors are allowed to move forward
 * - access to the article is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access to the article is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - fetch_all.php/article/12
 * - fetch_all.php?anchor=article:12
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Cyril Blondin
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */


 /*

 Internet Explorer and File Download Caching

Our discussion of PDF rendering in Chapter 3, Alternative Content Types explained that issues can arise when you're dealing with caching and file downloads. In serving a file download via a PHP script that uses headers such as Content-Disposition: attachment, filename=myFile.pdf or Content-Disposition: inline, filename=myFile.pdf, you'll have problems with Internet Explorer if you tell the browser not to cache the page.

Internet Explorer handles downloads in a rather unusual manner, making two requests to the Website. The first request downloads the file, and stores it in the cache before making a second request (without storing the response). This request invokes the process of delivering the file to the end user in accordance with the file's type (e.g. it starts Acrobat Reader if the file is a PDF document). This means that, if you send the cache headers that instruct the browser not to cache the page, Internet Explorer will delete the file between the first and second requests, with the result that the end user gets nothing. If the file you're serving through the PHP script will not change, one solution is simply to disable the "don't cache" headers for the download script.

If the file download will change regularly (i.e. you want the browser to download an up-to-date version), you'll need to use the last-modified header, discussed later in this chapter, and ensure that the time of modification remains the same across the two consecutive requests. You should be able to do this without affecting users of browsers that handle downloads correctly. One final solution is to write the file to your Web server and simply provide a link to it, leaving it to the Web server to report the cache headers for you. Of course, this may not be a viable option if the file is supposed to be secured by the PHP script, which requires a valid session in order to provide users access to the file; with this solution, the written file can be downloaded directly.

*/

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'files.php';

// the anchor
$type = $id = '';
if(isset($_REQUEST['anchor']))
	list($type, $id) = explode(':', $_REQUEST['anchor'], 2);
elseif(isset($context['arguments'][0]) && isset($context['arguments'][1])) {
	$type = $context['arguments'][0];
	$id = $context['arguments'][1];
}
$type = strip_tags($type);
$id = strip_tags($id);

// get the related item, if any
$item = array();
if(($type == 'article') && $id)
	$item =& Articles::get($id);

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

// associates and editors can do what they want
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
if(isset($item['id']))
	$context['path_bar'] = array( Articles::get_permalink($item) => $item['title'] );
else
	$context['path_bar'] = array( 'files/' => i18n::s('Files') );

// the title of the page
if(isset($item['title']))
	$context['page_title'] = $item['title'];

// change default behavior
if(isset($item['id']) && is_object($behaviors) && !$behaviors->allow('files/fetch_all.php', 'file:'.$item['id']))
	$permitted = FALSE;

// not found
if(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// package the files
} else {

	// build a zip archive
	include_once '../shared/zipfile.php';
	$zipfile = new zipfile();

	// get related files from the database
	$items = array();
	if(isset($type) && isset($id))
		$items = Files::list_by_date_for_anchor($type.':'.$id, 0, 20, 'raw');

	// archive each file
	$file_path = $context['path_to_root'].'/files/'.$context['virtual_path'].$type.'/'.$id.'/';
	foreach($items as $id => $attributes) {

		// read file content
		if($content = Safe::file_get_contents($file_path.$attributes['file_name'], 'rb')) {

			// add the binary data
			$zipfile->deflate($attributes['file_name'], Safe::filemtime($file_path.$attributes['file_name']), $content);
		}
	}

	//
	// transfer to the user agent
	//

	// send the archive content
	if($archive = $zipfile->get()) {

		// suggest a download
		Safe::header('Content-Type: application/octet-stream');

		// suggest a name for the saved file
		$file_name = str_replace('_', ' ', utf8::to_ascii($item['title']).'.zip');
		Safe::header('Content-Disposition: attachment; filename="'.$file_name.'"');

		// file size
		Safe::header('Content-Length: '.strlen($archive));

		// already encoded
		Safe::header('Content-Transfer-Encoding: binary');

		// enable 30-minute caching (30*60 = 1800), even through https, to help IE on download
		http::expire(1800);

		// strong validator
		$etag = '"'.md5($archive).'"';

		// manage web cache
		if(http::validate(NULL, $etag))
			return;

		// actual transmission except on a HEAD request
		if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
			echo $archive;

		// the post-processing hook, then exit
		finalize_page(TRUE);

	}
}

$context['text'] .= '<p>'.i18n::s('Operation has failed.').'</p>';

// render the skin
render_skin();

?>
