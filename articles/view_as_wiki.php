<?php
/**
 * structure a wiki page, with comments in a separate tab
 *
 * This script is included into [script]articles/view.php[/script], when the
 * option is set to 'view_as_tabs'.
 *
 * The basic structure is made of following panels:
 * - Information - with details, introduction, main text, files and links. This may be overloaded if required.
 * - Discussion - A thread of contributions, not in real-time
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// loaded from articles/view.php
defined('YACS') or exit('Script must be included');

//
// rewrite $context['page_details'] because information is split in tabs
//
$text = '';

//
// panels
//
$panels = array();

//
// article panel
//

$article = '';

// put page title there
if($context['page_title'])
	$article .= Skin::build_block($context['page_title'], 'page_title');
$context['page_title'] = '';

// insert anchor prefix
if(is_object($anchor))
	$article .= $anchor->get_prefix();

// links to previous and next pages, if any
if(isset($neighbours) && $neighbours)
	$article .= Skin::neighbours($neighbours, 'manual');

// article rating, if the anchor allows for it, and if no rating has already been registered
if(!Articles::has_option('without_rating', $anchor, $item) && Articles::has_option('rate_as_digg', $anchor, $item)) {

	// rating
	if($item['rating_count'])
		$rating_label = sprintf(i18n::ns('%s vote', '%s votes', $item['rating_count']), '<span class="big">'.$item['rating_count'].'</span>'.BR);
	else
		$rating_label = i18n::s('No vote');

	// a rating has already been registered
	$digg = '';
	if(isset($_COOKIE['rating_'.$item['id']]))
		Cache::poison();

	// where the surfer can rate this item
	else
		$digg = '<div class="rate">'.Skin::build_link(Articles::get_url($item['id'], 'rate'), i18n::s('Rate it'), 'basic').'</div>';

	// rendering
	$article .= '<div class="digg"><div class="votes">'.$rating_label.'</div>'
		.$digg
		.'</div>';

	// signal DIGG
	define('DIGG', TRUE);
}

// special layout for digg
if(defined('DIGG'))
	$article .= '<div class="digg_content">';

// the poster profile, if any, at the beginning of the first page
if(isset($poster['id']) && is_object($anchor))
	$article .= $anchor->get_user_profile($poster, 'prefix', Skin::build_date($item['create_date']));

// only at the first page
if($page == 1) {

	// the introduction text, if any
	if(is_object($overlay))
		$article .= Skin::build_block($overlay->get_text('introduction', $item), 'introduction');
	elseif(isset($item['introduction']) && trim($item['introduction']))
		$article .= Skin::build_block($item['introduction'], 'introduction');

}

// get text related to the overlay, if any
if(is_object($overlay))
	$article .= $overlay->get_text('view', $item);

// filter description, if necessary
if(is_object($overlay))
	$description = $overlay->get_text('description', $item);
else
	$description = $item['description'];

// the beautified description, which is the actual page body
if($description) {

	// provide only the requested page
	$pages = preg_split('/\s*\[page\]\s*/is', $description);
	$page = min(max($page, count($pages)), 1);
	$description = $pages[ $page-1 ];

	// if there are several pages, remove toc and toq codes
	if(count($pages) > 1)
		$description = preg_replace('/\s*\[(toc|toq)\]\s*/is', '', $description);

	// beautify the target page
	$article .= Skin::build_block($description, 'description', '', $item['options']);

	// if there are several pages, add navigation commands to browse them
	if(count($pages) > 1) {
		$page_menu = array( '_' => i18n::s('Pages') );
		$home = Articles::get_permalink($item);
		$prefix = Articles::get_url($item['id'], 'navigate', 'page');
		$page_menu = array_merge($page_menu, Skin::navigate($home, $prefix, count($pages), 1, $page));

		$article .= Skin::build_list($page_menu, 'menu_bar');
	}
}

// special layout for digg
if(defined('DIGG'))
	$article .= '</div>';

// the poster profile, if any, at the end of the page
if(isset($poster['id']) && is_object($anchor))
	$article .= $anchor->get_user_profile($poster, 'suffix', Skin::build_date($item['create_date']));

// build a complete box
$box = array('bar' => array(), 'text' => '');

// a navigation bar for these files
if($count = Files::count_for_anchor('article:'.$item['id'])) {
	if($count > 20)
		$box['bar'] += array('_count' => sprintf(i18n::ns('%d file', '%d files', $count), $count));

	// list files by date (default) or by title (option files_by_title)
	if(Articles::has_option('files_by_title', $anchor, $item))
		$items = Files::list_by_title_for_anchor('article:'.$item['id'], 0, 100, 'no_anchor');
	else
		$items = Files::list_by_date_for_anchor('article:'.$item['id'], 0, 100, 'no_anchor');

	// actually render the html
	if(is_array($items))
		$box['text'] .= Skin::build_list($items, 'decorated');
	elseif(is_string($items))
		$box['text'] .= $items;

	// the command to post a new file
	if(Files::allow_creation($anchor, $item, 'article')) {
		Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
		$box['bar'] += array('files/edit.php?anchor='.urlencode('article:'.$item['id']) => FILES_UPLOAD_IMG.i18n::s('Upload a file'));
	}

}

// display files
if($box['text'])
	$article .= Skin::build_content('files', i18n::s('Files'), $box['text'], $box['bar']);

// build a complete box
$box = array('bar' => array(), 'text' => '');

// a navigation bar for these links
if($count = Links::count_for_anchor('article:'.$item['id'])) {
	if($count > 20)
		$box['bar'] += array('_count' => sprintf(i18n::ns('%d link', '%d links', $count), $count));

	// list links by date (default) or by title (option links_by_title)
	if(Articles::has_option('links_by_title', $anchor, $item))
		$items = Links::list_by_title_for_anchor('article:'.$item['id'], 0, 50, 'no_anchor');
	else
		$items = Links::list_by_date_for_anchor('article:'.$item['id'], 0, 50, 'no_anchor');

	// actually render the html
	if(is_array($items))
		$box['text'] .= Skin::build_list($items, 'rows');
	elseif(is_string($items))
		$box['text'] .= $items;

	// new links are allowed
	if(Links::allow_creation($anchor, $item)) {
		Skin::define_img('LINKS_ADD_IMG', 'links/add.gif');
		$box['bar'] += array('links/edit.php?anchor='.urlencode('article:'.$item['id']) => LINKS_ADD_IMG.i18n::s('Add a link'));
	}

}

// display links
if($box['text'])
	$article .= Skin::build_content('links', i18n::s('Links'), $box['text'], $box['bar']);

// add trailer information from the overlay, if any
if(is_object($overlay))
	$article .= $overlay->get_text('trailer', $item);

// add trailer information from this item, if any
if(isset($item['trailer']) && trim($item['trailer']))
	$article .= Codes::beautify($item['trailer']);

// links to previous and next pages, if any
if(isset($neighbours) && $neighbours)
	$article .= Skin::neighbours($neighbours, 'manual');

// insert anchor suffix
if(is_object($anchor))
	$article .= $anchor->get_suffix();

// display in a separate panel
if($article)
	$panels[] = array('article', i18n::s('Article'), 'article_panel', $article);

//
// append tabs from the overlay, if any -- they have been captured in articles/view.php
//
if(isset($context['tabs']) && is_array($context['tabs']))
	$panels = array_merge($panels, $context['tabs']);

//
// discussion tab - a near real-time interaction area
//
$discussion = '';
$discussion_count = 0;

// conversation is over
if(isset($item['locked']) && ($item['locked'] == 'Y')) {

	// display a transcript of past comments
	include_once $context['path_to_root'].'comments/comments.php';
	$items = Comments::list_by_date_for_anchor('article:'.$item['id'], 0, 500, 'excerpt');
	if(is_array($items))
		$discussion .= Skin::build_list($items, 'rows');
	elseif(is_string($items))
		$discussion .= $items;

// on-going conversation
} else {

	// new comments are allowed
	if(Comments::allow_creation($anchor, $item)) {

		// we have a wall
		if(Articles::has_option('comments_as_wall', $anchor, $item))
			$comments_prefix = TRUE;

		// editors and associates can always contribute to a thread
		else
			$comments_suffix = TRUE;
	}

	// get a layout for these comments
	$layout =& Comments::get_layout($anchor, $item);

	// provide author information to layout
	if(is_object($layout) && $item['create_id'])
		$layout->set_variant('user:'.$item['create_id']);

	// the maximum number of comments per page
	if(is_object($layout))
		$items_per_page = $layout->items_per_page();
	else
		$items_per_page = COMMENTS_PER_PAGE;

	// the first comment to list
	$offset = ($zoom_index - 1) * $items_per_page;
	if(is_object($layout) && method_exists($layout, 'set_offset'))
		$layout->set_offset($offset);

	// build a complete box
	$box = array('top' => array(), 'bottom' => array(), 'text' => '');

	// feed the wall
	if(isset($comments_prefix))
		$box['text'] .= Comments::get_form('article:'.$item['id']);

	// a navigation bar for these comments
	if($count = Comments::count_for_anchor('article:'.$item['id'])) {
		$discussion_count = $count;
		$box['bottom'] += array('_count' => sprintf(i18n::ns('%d comment', '%d comments', $count), $count));

		// list comments by date
		$items = Comments::list_by_date_for_anchor('article:'.$item['id'], $offset, $items_per_page, $layout, isset($comments_prefix));

		// actually render the html
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'rows');
		elseif(is_string($items))
			$box['text'] .= $items;

		// navigation commands for comments
		$prefix = Comments::get_url('article:'.$item['id'], 'navigate');
		$box['bottom'] += Skin::navigate(NULL, $prefix, $count, $items_per_page, $zoom_index, FALSE, TRUE);

	}

		// new comments are allowed
		if(isset($comments_suffix)) {
			Skin::define_img('COMMENTS_ADD_IMG', 'comments/add.gif');
			$box['bottom'] += array( Comments::get_url('article:'.$item['id'], 'comment') => array('', COMMENTS_ADD_IMG.i18n::s('Post a comment'), '', 'basic', '', i18n::s('Post a comment')));
		}

	// build a box
	$discussion .= Skin::build_content('comments', '', $box['text'], $box['top'], $box['bottom']);

}

// display in a separate panel
if($discussion) {
	$label = i18n::s('Discussion');
	if($discussion_count)
		$label .= ' ('.$discussion_count.')';
	$panels[] = array('discussion', $label, 'discussion_panel', $discussion);
}

// let YACS do the hard job
$context['text'] .= Skin::build_tabs($panels);

//
// extra panel -- most content is cached, except commands specific to current surfer
//

// the poster profile, if any, aside
if(isset($poster['id']) && is_object($anchor))
	$context['components']['profile'] = $anchor->get_user_profile($poster, 'extra', Skin::build_date($item['create_date']));

// page tools
//

// comment this page if anchor does not prevent it
if(Comments::allow_creation($anchor, $item)) {
	Skin::define_img('COMMENTS_ADD_IMG', 'comments/add.gif');
	$context['page_tools'][] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'comment'), COMMENTS_ADD_IMG.i18n::s('Post a comment'), 'basic', i18n::s('Express yourself, and say what you think.'));
}

// attach a file, if upload is allowed
if(Files::allow_creation($anchor, $item, 'article')) {
	Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
	$context['page_tools'][] = Skin::build_link('files/edit.php?anchor='.urlencode('article:'.$item['id']), FILES_UPLOAD_IMG.i18n::s('Upload a file'), 'basic', i18n::s('Attach related files.'));
}

// add a link
if(Links::allow_creation($anchor, $item)) {
	Skin::define_img('LINKS_ADD_IMG', 'links/add.gif');
	$context['page_tools'][] = Skin::build_link('links/edit.php?anchor='.urlencode('article:'.$item['id']), LINKS_ADD_IMG.i18n::s('Add a link'), 'basic', i18n::s('Contribute to the web and link to relevant pages.'));
}

// post an image, if upload is allowed
if(Images::allow_creation($anchor, $item)) {
	Skin::define_img('IMAGES_ADD_IMG', 'images/add.gif');
	$context['page_tools'][] = Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']), IMAGES_ADD_IMG.i18n::s('Add an image'), 'basic', i18n::s('You can upload a camera shot, a drawing, or another image file.'));
}

// modify this page
if(Articles::allow_modification($anchor, $item)) {
	Skin::define_img('ARTICLES_EDIT_IMG', 'articles/edit.gif');
	if(!is_object($overlay) || (!$label = $overlay->get_label('edit_command')))
		$label = i18n::s('Edit this page');
	$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'edit'), ARTICLES_EDIT_IMG.$label, 'basic', i18n::s('Press [e] to edit'), FALSE, 'e');
}

// access previous versions, if any
if($has_versions && Articles::is_owned($item, $anchor)) {
	Skin::define_img('ARTICLES_VERSIONS_IMG', 'articles/versions.gif');
	$context['page_tools'][] = Skin::build_link(Versions::get_url('article:'.$item['id'], 'list'), ARTICLES_VERSIONS_IMG.i18n::s('Versions'), 'basic', i18n::s('Restore a previous version if necessary'));
}

// publish this page
if((!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE)) && Articles::allow_publication($anchor, $item)) {
	Skin::define_img('ARTICLES_PUBLISH_IMG', 'articles/publish.gif');
	$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'publish'), ARTICLES_PUBLISH_IMG.i18n::s('Publish'));
}

// review command provided to container owners
if(Articles::is_owned(NULL, $anchor)) {
	Skin::define_img('ARTICLES_STAMP_IMG', 'articles/stamp.gif');
	$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'stamp'), ARTICLES_STAMP_IMG.i18n::s('Stamp'));
}

// lock command provided to associates and authenticated editors
if(Articles::is_owned($item, $anchor)) {

	if(!isset($item['locked']) || ($item['locked'] == 'N')) {
		Skin::define_img('ARTICLES_LOCK_IMG', 'articles/lock.gif');
		$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'lock'), ARTICLES_LOCK_IMG.i18n::s('Lock'));
	} else {
		Skin::define_img('ARTICLES_UNLOCK_IMG', 'articles/unlock.gif');
		$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'lock'), ARTICLES_UNLOCK_IMG.i18n::s('Unlock'));
	}
}

// delete command provided to page owners
if(Articles::is_owned($item, $anchor)) {
	Skin::define_img('ARTICLES_DELETE_IMG', 'articles/delete.gif');
	$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'delete'), ARTICLES_DELETE_IMG.i18n::s('Delete this page'));
}

// duplicate command provided to container owners
if(isset($item['id']) && is_object($anchor) && $anchor->is_owned()) {
	Skin::define_img('ARTICLES_DUPLICATE_IMG', 'articles/duplicate.gif');
	$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'duplicate'), ARTICLES_DUPLICATE_IMG.i18n::s('Duplicate this page'));
}

// assign command provided to page owners
if(Articles::is_owned($item, $anchor)) {
	Skin::define_img('ARTICLES_ASSIGN_IMG', 'articles/assign.gif');
	$context['page_tools'][] = Skin::build_link(Users::get_url('article:'.$item['id'], 'select'), ARTICLES_ASSIGN_IMG.i18n::s('Manage editors'));
}

// use date of last modification into etag computation
if(isset($item['edit_date']))
	$context['etag'] = $item['edit_date'];

// render the skin
render_skin();

?>