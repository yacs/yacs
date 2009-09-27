<?php
/**
 * structure a web page as tabs
 *
 * This script is included into [script]articles/view.php[/script], when the
 * option is set to 'view_as_tabs'.
 *
 * The basic structure is made of following panels:
 * - Information - with details, introduction, and main text. This may be overloaded if required.
 * - Attachments - with files and links
 * - Discussion - A thread of contributions, not in real-time
 *
 * The extra panel has following elements:
 * - extra information from the article itself, if any
 * - toolbox for page author, editors, and associates
 * - The list of twin pages (with the same nick name)
 * - The list of related categories, into a sidebar box
 * - The nearest locations, if any, into a sidebar box
 * - Means to reference this page, into a sidebar box
 * - The top popular referrals, if any
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

// do not mention details to crawlers
if(!Surfer::is_crawler()) {

	// tags, if any
	if(isset($item['tags']))
		$context['page_tags'] =& Skin::build_tags($item['tags'], 'article:'.$item['id']);

	// one detail per line
	$text .= '<p class="details">';
	$details = array();

	// add details from the overlay, if any
	if(is_object($overlay) && ($more = $overlay->get_text('details', $item)))
		$details[] = $more;

	// article rating, if the anchor allows for it, and if no rating has already been registered
	if(!Articles::has_option('without_rating', $anchor, $item) && !Articles::has_option('rate_as_digg', $anchor, $item)) {

		// report on current rating
		$label = '';
		if($item['rating_count'])
			$label .= Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])).' '.sprintf(i18n::ns('%d rate', '%d rates', $item['rating_count']), $item['rating_count']).' ';
		if(!$label)
			$label .= i18n::s('Rate this page');

		// link to the rating page
		$label = Skin::build_link(Articles::get_url($item['id'], 'rate'), $label, 'span', i18n::s('Rate this page'));

		// feature page rating
		$details[] = $label;
	}

	// the source, if any
	if($item['source']) {
		if(preg_match('/(http|https|ftp):\/\/([^\s]+)/', $item['source'], $matches))
			$item['source'] = Skin::build_link($matches[0], $matches[0], 'external');
		elseif(strpos($item['source'], '[') === 0) {
			if($attributes = Links::transform_reference($item['source'])) {
				list($link, $title, $description) = $attributes;
				$item['source'] = Skin::build_link($link, $title);
			}
		}
		$details[] = sprintf(i18n::s('Source: %s'), $item['source']);
	}

	// restricted to logged members
	if($item['active'] == 'R')
		$details[] = RESTRICTED_FLAG.' '.i18n::s('Community - Access is restricted to authenticated members');

	// restricted to associates
	elseif($item['active'] == 'N')
		$details[] = PRIVATE_FLAG.' '.i18n::s('Private - Access is restricted to selected persons');

	// expired article
	$now = gmstrftime('%Y-%m-%d %H:%M:%S');
	if((Surfer::is_associate() || Articles::is_assigned($item['id']) || (is_object($anchor) && $anchor->is_assigned()))
			&& ($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now)) {
		$details[] = EXPIRED_FLAG.' '.sprintf(i18n::s('Page has expired %s'), Skin::build_date($item['expiry_date']));
	}

	// no more details
	if(count($details))
		$text .= ucfirst(implode(BR."\n", $details)).BR."\n";

	// details
	$details =& Articles::build_dates($anchor, $item);

	// signal articles to be published
	if(($item['publish_date'] <= NULL_DATE)) {
		if($publishable)
			$label = Skin::build_link(Articles::get_url($item['id'], 'publish'), i18n::s('not published'));
		else
			$label = i18n::s('not published');
		$details[] = DRAFT_FLAG.' '.$label;
	}

	// the number of hits
	if(($item['hits'] > 1) && (Articles::is_owned($anchor, $item)
		|| ((!isset($context['content_without_details']) || ($context['content_without_details'] != 'Y')) || Articles::has_option('with_details', $anchor, $item)) ) ) {

		// flag popular pages
		$popular = '';
		if($item['hits'] > 100)
			$popular = POPULAR_FLAG;

		// show the number
		if(Articles::is_owned($anchor, $item) || ($item['hits'] < 100))
			$details[] = $popular.Skin::build_number($item['hits'], i18n::s('hits'));

		// other surfers will benefit from a stable ETag
		elseif($popular)
			$details[] = $popular;
	}

	// rank for this article
	if(Articles::is_owned($anchor, $item) && (intval($item['rank']) != 10000))
		$details[] = '{'.$item['rank'].'}';

	// locked article
	if(Surfer::is_member() && isset($item['locked']) && ($item['locked'] == 'Y') )
		$details[] = LOCKED_FLAG.' '.i18n::s('page is locked.');

	// in-line details
	if(count($details))
		$text .= ucfirst(implode(', ', $details));

	// reference this item
	if(Surfer::is_member()) {
		$text .= BR.sprintf(i18n::s('Code to reference this page: %s'), '[article='.$item['id'].']');

		// the nick name
		if($item['nick_name'] && ($link = normalize_shortcut($item['nick_name'], TRUE)))
			$text .= BR.sprintf(i18n::s('Shortcut: %s'), $link);
	}

	// no more details
	$text .= "</p>\n";

	// update page details
	$context['page_details'] = $text;

}

//
// compute main panel -- $context['text']
//

// insert anchor prefix
if(is_object($anchor))
	$context['text'] .= $anchor->get_prefix();

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
	$context['text'] .= '<div class="digg"><div class="votes">'.$rating_label.'</div>'
		.$digg
		.'</div>';

	// signal DIGG
	define('DIGG', TRUE);
}

// special layout for digg
if(defined('DIGG'))
	$context['text'] .= '<div class="digg_content">';

// the poster profile, if any, at the beginning of the first page
if(isset($poster['id']) && is_object($anchor))
	$context['text'] .= $anchor->get_user_profile($poster, 'prefix', Skin::build_date($item['create_date']));

// only at the first page
if($page == 1) {

	// the introduction text, if any
	if(is_object($overlay))
		$context['text'] .= Skin::build_block($overlay->get_text('introduction', $item), 'introduction');
	elseif(isset($item['introduction']) && trim($item['introduction']))
		$context['text'] .= Skin::build_block($item['introduction'], 'introduction');

}

// filter description, if necessary
if(is_object($overlay))
	$description = $overlay->get_text('description', $item);
else
	$description = $item['description'];

// the beautified description, which is the actual page body
if($description) {

	// use adequate label
// 		if(is_object($overlay) && ($label = $overlay->get_label('description')))
// 			$context['text'] .= Skin::build_block($label, 'title');

	// provide only the requested page
	$pages = preg_split('/\s*\[page\]\s*/is', $description);
	if($page > count($pages))
		$page = count($pages);
	if($page < 1)
		$page = 1;
	$description = $pages[ $page-1 ];

	// if there are several pages, remove toc and toq codes
	if(count($pages) > 1)
		$description = preg_replace('/\s*\[(toc|toq)\]\s*/is', '', $description);

	// beautify the target page
	$context['text'] .= Skin::build_block($description, 'description', '', $item['options']);

	// if there are several pages, add navigation commands to browse them
	if(count($pages) > 1) {
		$page_menu = array( '_' => i18n::s('Pages') );
		$home = Articles::get_permalink($item);
		$prefix = Articles::get_url($item['id'], 'navigate', 'page');
		$page_menu = array_merge($page_menu, Skin::navigate($home, $prefix, count($pages), 1, $page));

		$context['text'] .= Skin::build_list($page_menu, 'menu_bar');
	}
}

// special layout for digg
if(defined('DIGG'))
	$context['text'] .= '</div>';

// the poster profile, if any, at the end of the page
if(isset($poster['id']) && is_object($anchor))
	$context['text'] .= $anchor->get_user_profile($poster, 'suffix', Skin::build_date($item['create_date']));

//
// panels
//
$panels = array();

//
// information tab
//
$information = '';

// get text related to the overlay, if any
if(is_object($overlay))
	$information .= $overlay->get_text('view', $item);

// add trailer information from the overlay, if any
if(is_object($overlay))
	$information .= $overlay->get_text('trailer', $item);

// add trailer information from this item, if any
if(isset($item['trailer']) && trim($item['trailer']))
	$information .= Codes::beautify($item['trailer']);

// insert anchor suffix
if(is_object($anchor))
	$information .= $anchor->get_suffix();

// display in a separate panel
if($information)
	$panels[] = array('information', i18n::s('Information'), 'information_panel', $information);

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
	if(Comments::are_allowed($anchor, $item)) {

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


		// new comments are allowed
		if(isset($comments_suffix)) {
			Skin::define_img('COMMENTS_ADD_IMG', 'comments/add.gif');
			$box['bottom'] += array( Comments::get_url('article:'.$item['id'], 'comment') => array('', COMMENTS_ADD_IMG.i18n::s('Post a comment'), '', 'basic', '', i18n::s('Post a comment')));
		}
	}

	// build a box
	if($box['text'])
		$discussion .= Skin::build_content('comments', '', $box['text'], $box['top'], $box['bottom']);

}

// display in a separate panel
if($discussion) {
	$label = i18n::s('Discussion');
	if($discussion_count)
		$label .= ' ('.$discussion_count.')';
	$panels[] = array('discussion', $label, 'discussion_panel', $discussion);
}

//
// attachments tab
//
$attachments = '';
$attachments_count = 0;

// build a complete box
$box = array('bar' => array(), 'text' => '');

// a navigation bar for these files
if($count = Files::count_for_anchor('article:'.$item['id'])) {
	$attachments_count += $count;
	if($count > 5)
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
	if(Files::are_allowed($anchor, $item)) {
		Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
		$box['bar'] += array('files/edit.php?anchor='.urlencode('article:'.$item['id']) => FILES_UPLOAD_IMG.i18n::s('Upload a file'));
	}

}

// display this in tab
if($box['text'])
	$attachments .= Skin::build_content('files', i18n::s('Files'), $box['text'], $box['bar']);

// build a complete box
$box = array('bar' => array(), 'text' => '');

// a navigation bar for these links
if($count = Links::count_for_anchor('article:'.$item['id'])) {
	$attachments_count += $count;
	if($count > 5)
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
	if(Links::are_allowed($anchor, $item)) {
		Skin::define_img('LINKS_ADD_IMG', 'links/add.gif');
		$box['bar'] += array('links/edit.php?anchor='.urlencode('article:'.$item['id']) => LINKS_ADD_IMG.i18n::s('Add a link'));
	}

}

// display this aside the thread
if($box['text'])
	$attachments .= Skin::build_content('links', i18n::s('Links'), $box['text'], $box['bar']);

// display in a separate panel
if($attachments) {
	$label = i18n::s('Attachments');
	if($attachments_count)
		$label .= ' ('.$attachments_count.')';
	$panels[] = array('attachments', $label, 'attachments_panel', $attachments);
}

//
// users
//
$users = '';
$users_count = 0;

// the list of related users if not at another follow-up page
if(!$zoom_type || ($zoom_type == 'users')) {

	// build a complete box
	$box = array('bar' => array(), 'text' => '');

	// count the number of users
	$estats = Members::stat_users_for_member('article:'.$item['id']);
	$wstats = Members::stat_users_for_anchor('article:'.$item['id']);
	$users_count = max($estats['count'], $wstats['count']);

	// spread the list over several pages
	if($estats['count'] > 1)
		$box['bar'] += array('_count' => sprintf(i18n::ns('%d person', '%d persons', $estats['count']), $estats['count']));

	// send a message to an article
	if(($estats['count'] > 1) && Surfer::is_empowered() && Surfer::is_logged() && isset($context['with_email']) && ($context['with_email'] == 'Y')) {
		Skin::define_img('ARTICLES_EMAIL_IMG', 'articles/email.gif');
		$box['bar'] += array(Articles::get_url($item['id'], 'mail') => ARTICLES_EMAIL_IMG.i18n::s('Send a message'));
	}

	// assign command provided to associates and authenticated editors
	if(Articles::is_owned($anchor, $item) && isset($context['with_email']) && ($context['with_email'] == 'Y')) {
		Skin::define_img('ARTICLES_INVITE_IMG', 'articles/invite.gif');
		$box['bar'] += array(Articles::get_url($item['id'], 'invite') => ARTICLES_INVITE_IMG.i18n::s('Invite participants'));

	// assign command provided to owners
	} elseif(Articles::is_owned($anchor, $item)) {
		Skin::define_img('ARTICLES_ASSIGN_IMG', 'articles/assign.gif');
		$box['bar'] += array(Users::get_url('article:'.$item['id'], 'select') => ARTICLES_ASSIGN_IMG.i18n::s('Manage editors'));

	// allow editors to leave their position
	} elseif(Articles::is_assigned($item['id'])) {
		Skin::define_img('ARTICLES_ASSIGN_IMG', 'sections/assign.gif');
		$box['bar'] += array(Users::get_url('article:'.$item['id'], 'select') => ARTICLES_ASSIGN_IMG.i18n::s('Leave this page'));
	}

	// navigation commands for users
	$home = Articles::get_permalink($item);
	$prefix = Articles::get_url($item['id'], 'navigate', 'users');
	$box['bar'] = array_merge($box['bar'], Skin::navigate($home, $prefix, $estats['count'], USERS_LIST_SIZE, $zoom_index));

	// list editors
	$offset = ($zoom_index - 1) * USERS_LIST_SIZE;
	if($items =& Members::list_editors_for_member('article:'.$item['id'], $offset, USERS_LIST_SIZE, 'watch')) {
		if(is_array($items))
			$items = Skin::build_list($items, 'decorated');
		$items = i18n::s('Following persons are entitled to manage content:').$items;
	}

	// watchers
	if($watchers =& Members::list_watchers_by_posts_for_anchor('article:'.$item['id'], 0, 500, 'compact')) {
		if(is_array($watchers))
			$watchers = Skin::build_list($watchers, 'compact');
		$watchers = i18n::s('Following persons are watching this page:').$watchers;
	}

	// add to the watch list -- $in_wath_list is set in sections/view.php
	if(Surfer::get_id() && !$in_watch_list) {
		Skin::define_img('TOOLS_WATCH_IMG', 'tools/watch.gif');
		$watchers .= '<p style="margin: 1em 0;">'.Skin::build_link(Users::get_url('article:'.$item['id'], 'track'), TOOLS_WATCH_IMG.i18n::s('Watch this page'), 'button', i18n::s('To be notified when new content is added')).'</p>';
	}

	// layout columns
	if($watchers)
		$box['text'] .= Skin::layout_horizontally($items, Skin::build_block($watchers, 'sidecolumn'));
	else
		$box['text'] .= $items;

	// actually render the html
	$users .= Skin::build_content(NULL, NULL, $box['text'], $box['bar']);

}

// display in a separate panel
if($users) {
	$label = i18n::s('Persons');
	if($users_count)
		$label .= ' ('.$users_count.')';
	$panels[] = array('users', $label, 'users_panel', $users);
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
if(Comments::are_allowed($anchor, $item)) {
	Skin::define_img('COMMENTS_ADD_IMG', 'comments/add.gif');
	$context['page_tools'][] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'comment'), COMMENTS_ADD_IMG.i18n::s('Post a comment'), 'basic', i18n::s('Express yourself, and say what you think.'));
}

// attach a file, if upload is allowed
if(Files::are_allowed($anchor, $item)) {
	Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
	$context['page_tools'][] = Skin::build_link('files/edit.php?anchor='.urlencode('article:'.$item['id']), FILES_UPLOAD_IMG.i18n::s('Upload a file'), 'basic', i18n::s('Attach related files.'));
}

// add a link
if(Links::are_allowed($anchor, $item)) {
	Skin::define_img('LINKS_ADD_IMG', 'links/add.gif');
	$context['page_tools'][] = Skin::build_link('links/edit.php?anchor='.urlencode('article:'.$item['id']), LINKS_ADD_IMG.i18n::s('Add a link'), 'basic', i18n::s('Contribute to the web and link to relevant pages.'));
}

// post an image, if upload is allowed
if(Images::are_allowed($anchor, $item)) {
	Skin::define_img('IMAGES_ADD_IMG', 'images/add.gif');
	$context['page_tools'][] = Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']), IMAGES_ADD_IMG.i18n::s('Add an image'), 'basic', i18n::s('You can upload a camera shot, a drawing, or another image file.'));
}

// modify this page
if($editable) {
	Skin::define_img('ARTICLES_EDIT_IMG', 'articles/edit.gif');
	if(!is_object($overlay) || (!$label = $overlay->get_label('edit_command')))
		$label = i18n::s('Edit this page');
	$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'edit'), ARTICLES_EDIT_IMG.$label, 'basic', i18n::s('Press [e] to edit'), FALSE, 'e');
}

// access previous versions, if any
if($has_versions && Articles::is_owned($anchor, $item)) {
	Skin::define_img('ARTICLES_VERSIONS_IMG', 'articles/versions.gif');
	$context['page_tools'][] = Skin::build_link(Versions::get_url('article:'.$item['id'], 'list'), ARTICLES_VERSIONS_IMG.i18n::s('Versions'), 'basic', i18n::s('Restore a previous version if necessary'));
}

// publish this page
if($publishable) {

	if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE)) {
		Skin::define_img('ARTICLES_PUBLISH_IMG', 'articles/publish.gif');
		$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'publish'), ARTICLES_PUBLISH_IMG.i18n::s('Publish'));
	}

}

// review command provided to associates and section editors
if(Articles::is_owned($anchor, $item)) {
	Skin::define_img('ARTICLES_STAMP_IMG', 'articles/stamp.gif');
	$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'stamp'), ARTICLES_STAMP_IMG.i18n::s('Stamp'));
}

// lock command provided to associates and authenticated editors
if(Articles::is_owned($anchor, $item)) {

	if(!isset($item['locked']) || ($item['locked'] == 'N')) {
		Skin::define_img('ARTICLES_LOCK_IMG', 'articles/lock.gif');
		$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'lock'), ARTICLES_LOCK_IMG.i18n::s('Lock'));
	} else {
		Skin::define_img('ARTICLES_UNLOCK_IMG', 'articles/unlock.gif');
		$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'lock'), ARTICLES_UNLOCK_IMG.i18n::s('Unlock'));
	}
}

// delete command provided to page owners
if(Articles::is_owned($anchor, $item)) {
	Skin::define_img('ARTICLES_DELETE_IMG', 'articles/delete.gif');
	$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'delete'), ARTICLES_DELETE_IMG.i18n::s('Delete this page'));
}

// duplicate command provided to associates and section editors
if(isset($item['id']) && !$zoom_type && is_object($anchor) && $anchor->is_assigned()) {
	Skin::define_img('ARTICLES_DUPLICATE_IMG', 'articles/duplicate.gif');
	$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'duplicate'), ARTICLES_DUPLICATE_IMG.i18n::s('Duplicate'));
}

// assign command provided to page owners
if(Articles::is_owned($anchor, $item)) {
	Skin::define_img('ARTICLES_ASSIGN_IMG', 'articles/assign.gif');
	$context['page_tools'][] = Skin::build_link(Users::get_url('article:'.$item['id'], 'select'), ARTICLES_ASSIGN_IMG.i18n::s('Manage editors'));
}

//
// reload this page if it changes
//
$context['page_footer'] .= JS_PREFIX
	."\n"
	.'// reload this page on update'."\n"
	.'var PeriodicalCheck = {'."\n"
	."\n"
	.'	url: "'.$context['url_to_home'].$context['url_to_root'].Articles::get_url($item['id'], 'check').'",'."\n"
	.'	timestamp: '.SQL::strtotime($item['edit_date']).','."\n"
	."\n"
	.'	initialize: function() { },'."\n"
	."\n"
	.'	subscribe: function() {'."\n"
	.'		this.ajax = new Ajax.Request(PeriodicalCheck.url, {'."\n"
	.'			method: "get",'."\n"
	.'			requestHeaders: {Accept: "application/json"},'."\n"
	.'			onSuccess: PeriodicalCheck.updateOnSuccess,'."\n"
	.'			onFailure: PeriodicalCheck.updateOnFailure });'."\n"
	.'	},'."\n"
	."\n"
	.'	updateOnSuccess: function(transport) {'."\n"
	.'		var response = transport.responseText.evalJSON(true);'."\n"
	.'		// page has been updated'."\n"
	.'		if(PeriodicalCheck.timestamp && response["timestamp"] && (PeriodicalCheck.timestamp != response["timestamp"])) {'."\n"
	.'			// reflect updater name in window title'."\n"
	.'			if(typeof this.windowOriginalTitle != "string")'."\n"
	.'				this.windowOriginalTitle = document.title;'."\n"
	.'			document.title = "[" + response["name"] + "] " + this.windowOriginalTitle;'."\n"
	.'			// smart reload of the page'."\n"
	.'			new Ajax.Updater( { success: $$("body")[0] }, window.location, { method: "get" } );'."\n"
	.'		}'."\n"
	.'		// wait for more time'."\n"
	.'		setTimeout("PeriodicalCheck.subscribe()", 120000);'."\n"
	.'	},'."\n"
	."\n"
	.'	updateOnFailure: function(transport) {'."\n"
	.'		setTimeout("PeriodicalCheck.subscribe()", 600000);'."\n"
	.'	}'."\n"
	."\n"
	.'}'."\n"
	."\n"
	.'// look for some page update'."\n"
	.'setTimeout("PeriodicalCheck.subscribe()", 120000);'."\n"
	."\n"
	.JS_SUFFIX;

// stamp the page
$last_modified = SQL::strtotime($item['edit_date']);

// at the minimum, consider the date of the last configuration change
if($last_configured = Safe::filemtime('../parameters/control.include.php'))
	$last_modified = max($last_modified, $last_configured);

// render the skin
render_skin($last_modified);

?>