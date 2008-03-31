<?php
/**
 * use collections to extend the scope of your server to existing file heaps
 *
 * A collection is defined by following parameters
 * - a nick name that has to be unique (e.g., 'library')
 * - a label to be used as a title in pages browsing the collection
 * - a path prefix to related files into the local file system (e.g. '/usr/lib')
 * - an url prefix to be used to access files remotely (e.g. 'ftp://ftp.mydomain.com/library')
 * - an introduction to be used on collections index page
 * - a description to be used on one collection index page, before the list of folders and/or files
 * - a prefix to be used on each browsing page of the collection, before the list of folders and/or files
 * - a suffix to be used on each browsing page of the collection, after the list of folders and/or files
 *
 * Folders and files in the collection are identified by some path information (e.g., 'mysoft/users-guide.pdf').
 * The actual folder or file is accessed locally by using the path prefix (e.g., '/usr/lib/mysoft/users-guide.pdf').
 * The URL for the file is built using the url prefix (e.g., 'ftp://ftp.mydomain.com/library/mysoft/users-guide.pdf').
 *
 * If HTTP authentication credentials are provided, this script will also include protected collections.
 *
 * This module uses following scripts:
 * - index.php the main index page for collections
 * - browse.php to browse one collection
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load localized strings
i18n::bind('collections');

// load the skin
load_skin('collections');

// check network credentials, if any
if($user = Users::authenticate())
	Surfer::empower($user['capability']);

// the title of the page
$context['page_title'] = i18n::s('Collections');

// configure collections
if(Surfer::is_associate())
	$context['page_menu'] = array_merge($context['page_menu'], array( 'collections/configure.php' => i18n::s('Configure') ));

// list existing collections
Safe::load('parameters/collections.include.php');
if(isset($context['collections']) && is_array($context['collections'])) {
	$context['text'] .= '<ul class="collections">'."\n";
	foreach($context['collections'] as $name => $attributes) {

		// retrieve collection information
		list($title, $path, $url, $introduction, $description, $prefix, $suffix, $visibility) = $attributes;

		// skip protected collections
		if(($visibility == 'N') && !Surfer::is_empowered())
			continue;
		if(($visibility == 'R') && !Surfer::is_empowered('M'))
			continue;

		// ensure we have a title for this collection
		if(!trim($title))
			$title = str_replace(array('.', '_', '%20'), ' ', $name);

		// signal restricted and private collections
		if($visibility == 'N')
			$title = PRIVATE_FLAG.$title;
		elseif($visibility == 'R')
			$title = RESTRICTED_FLAG.$title;

		if($context['with_friendly_urls'] == 'Y')
			$link = 'collections/browse.php/'.rawurlencode($name);
		else
			$link = 'collections/browse.php?path='.urlencode($name);

		$context['text'] .= '<li><a href="'.$context['url_to_root'].$link.'">'.$title.'</a>';

		if($introduction)
			$context['text'] .= ' - '.Codes::beautify($introduction);

		$context['text'] .= "</li>\n";
	}
	$context['text'] .= "</ul>\n";

// no collection has been configured yet
} else
	$context['text'] .= i18n::s('No collection has been defined. Please configure.');

// referrals, if any
$context['extra'] .= Skin::build_referrals('collections/index.php');

// render the skin
render_skin();

?>