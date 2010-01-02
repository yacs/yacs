<?php
/**
 * index page on a small screen
 *
 * This script is included into [script]index.php[/script], when Surfer::is_desktop() is FALSE.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// loaded from articles/view.php
defined('YACS') or exit('Script must be included');

// page among fresh articles
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][1]))
	$page = $context['arguments'][1];
else
	$page = 1;
$page = max(1,intval($page));

// stop hackers
if($page > 10)
	$page = 10;

// start the page
if($page == 1)
	$context['text'] = '<ul id="home" title="'.encode_field($context['site_name']).'" selected="true">'."\n";

// process rows, if any
if($items =& Articles::list_by('publication', ($page-1)*ARTICLES_PER_PAGE, ($page*ARTICLES_PER_PAGE)+1, 'iui')) {

	// list articles, and append a link to get more
	if(count($items) > ARTICLES_PER_PAGE) {
		@array_splice($items, ARTICLES_PER_PAGE);
		$context['text'] .= join("\n", $items);

		if($page < 10)
			$context['text'] .= '<li><a href="'.$context['url_to_home'].$context['url_to_root'].'?page='.($page+1).'" target="_replace">More Stories...</a></li>';

	// end of the list
	} else
		$context['text'] .= join("\n", $items);

}

// end the page
if($page == 1)
	$context['text'] .= '</ul>';

// do the actual rendering
if($page > 1) {
	echo $context['text'];
	return;
}
include_once $context['path_to_root'].'skins/_mobile/template.php';

?>