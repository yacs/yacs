<?php
/**
 * view a page on a handheld
 *
 * This script conforms to iui AJAX API.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// loaded from articles/view.php
defined('YACS') or exit('Script must be included');

//
// compute main panel -- $context['text']
//

// page title
if(is_object($anchor))
	$context['text'] .= '<div title="'.encode_field($anchor->get_title()).'" style="padding: 0 6px;">'."\n"
		.'<h2>'.$context['page_title'].'</h2>'."\n";

// insert anchor prefix
if(is_object($anchor))
	$context['text'] .= $anchor->get_prefix();

// the introduction text, if any
if(is_object($overlay))
	$context['text'] .= Skin::build_block($overlay->get_text('introduction', $item), 'introduction');
else
	$context['text'] .= Skin::build_block($item['introduction'], 'introduction');

// get text related to the overlay, if any
if(is_object($overlay))
	$context['text'] .= $overlay->get_text('view', $item);

// filter description, if necessary
if(is_object($overlay))
	$description = $overlay->get_text('description', $item);
else
	$description = $item['description'];

// the beautified description, which is the actual page body
if($description) {

	// use adequate label
	if(is_object($overlay) && ($label = $overlay->get_label('description')))
		$context['text'] .= Skin::build_block($label, 'title');

	// provide only the requested page
	$pages = preg_split('/\s*\[page\]\s*/is', $description);
	$page = min(max($page, count($pages)), 1);
	$description = $pages[ $page-1 ];

	// if there are several pages, remove toc and toq codes
	if(count($pages) > 1)
		$description = preg_replace('/\s*\[(toc|toq)\]\s*/is', '', $description);

	// beautify the target page
	$context['text'] .= Skin::build_block($description, 'description', '', $item['options']);

	// if there are several pages, add navigation commands to browse them
	if(count($pages) > 1) {
		$page_menu = array( '_' => i18n::s('Pages') );
		$home =& Sections::get_permalink($item);
		$prefix = Sections::get_url($item['id'], 'navigate', 'pages');
		$page_menu = array_merge($page_menu, Skin::navigate($home, $prefix, count($pages), 1, $page));

		$context['text'] .= Skin::build_list($page_menu, 'menu_bar');
	}
}

// add trailer information from the overlay, if any
if(is_object($overlay))
	$context['text'] .= $overlay->get_text('trailer', $item);

// display a transcript of past comments
include_once $context['path_to_root'].'comments/comments.php';
$items = Comments::list_by_date_for_anchor('article:'.$item['id'], 0, 500, 'excerpt');
if(is_array($items))
	$context['text'] .= Skin::build_list($items, 'rows');
elseif(is_string($items))
	$context['text'] .= $items;

//
// trailer information
//

// add trailer information from this item, if any
if(isset($item['trailer']) && trim($item['trailer']))
	$context['text'] .= Codes::beautify($item['trailer']);

// insert anchor suffix
if(is_object($anchor))
	$context['text'] .= $anchor->get_suffix();

// end the page
$context['text'] .= '</div>';

// actual transmission except on a HEAD request
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $context['text'];

?>