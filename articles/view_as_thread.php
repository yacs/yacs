<?php
/**
 * real-time chat facility
 *
 * This script is included into [script]articles/view.php[/script], when the
 * option is set to 'view_as_thread'.
 *
 * On a regular page, it allows for dynamic conversations involving two people
 * or more. When the page is locked, only a transcript is displayed.
 *
 * The main panel has following elements:
 * - The article itself, with details, introduction, and main text. This may be overloaded if required.
 * - The real-time chatting space, built upon a specialized layout for comments
 * - The list of related links
 *
 * Details about this page are displayed if:
 * - surfer is a site associate or a section editor
 * - surfer is a member and ( ( global parameter content_without_details != Y ) or ( section option with_details == Y ) )
 *
 * The extra panel has following elements:
 * - extra information from the article itself, if any
 * - toolbox for page author, editors, and associates
 * - List of current participants
 * - The list of related files
 * - A list of related links
 * - The list of twin pages (with the same nick name)
 * - The list of related categories, into a sidebar box
 * - The nearest locations, if any, into a sidebar box
 * - Means to reference this page, into a sidebar box
 * - The top popular referrals, if any
 *
 * Several HTTP headers, or &lt;meta&gt; attributes of the displayed page, are set dynamically here
 * to help advanced web usage. This includes:
 * - a link to the section page (e.g., '&lt;link rel="contents" href="http://127.0.0.1/yacs/sections/view.php/4038" title="Ma cat&eacute;gorie" type="text/html" /&gt;')
 * - a link to a RDF description of this page (e.g., '&lt;link rel="meta" href="http://127.0.0.1/yacs/articles/describe.php/4310" title="rdf" type="application/rdf+xml" /&gt;')
 * - a rdf section implementing the [link=trackback]http://www.movabletype.org/docs/mttrackback.html[/link] interface
 * - a [link=pingback]http://www.hixie.ch/specs/pingback/pingback[/link] link (e.g., '&lt;link rel="pingback" href="http://here/yacs/services/pingback.php" /&gt;')
 * - a link to the [link=Comment API]http://wellformedweb.org/CommentAPI/[/link] for this page
 *
 * Meta information also includes:
 * - page description, which is a copy of the introduction, if any, or the default general description parameter
 * - page author, who is the original creator
 * - page publisher, if any
 *
 * The displayed article is saved into the history of visited pages if the global parameter
 * [code]pages_without_history[/code] has not been set to 'Y'.
 *
 * @see skins/configure.php
 *
 * The design has been influenced by the beautiful service from Campfire, in
 * case you would need more insights on YACS history.
 *
 * @link http://www.campfirenow.com/
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// loaded from articles/view.php
defined('YACS') or exit('Script must be included');

// load the skin, maybe with a variant
load_skin('view_as_thread', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'articles/' => i18n::s('All pages') );

// page title
if(is_object($overlay))
	$context['page_title'] = $overlay->get_text('title', $item);
elseif(isset($item['title']) && $item['title'])
	$context['page_title'] = $item['title'];
else
	$context['page_title'] = i18n::s('No title has been provided.');

// page language, if any
if(isset($item['language']) && $item['language'] && ($item['language'] != 'none'))
	$context['page_language'] = $item['language'];

// change default behavior
if(isset($item['id']) && is_object($behaviors) && !$behaviors->allow('articles/view.php', 'article:'.$item['id']))
	$permitted = FALSE;

// modify this page
if(isset($item['id']) && $editable) {
	Skin::define_img('EDIT_ARTICLE_IMG', 'icons/articles/edit.gif');
	$context['page_menu'] = array_merge($context['page_menu'], array( Articles::get_url($item['id'], 'edit') => array('', EDIT_ARTICLE_IMG.i18n::s('Edit this page'), '', 'basic', '', i18n::s('Update the content of this page')) ));
}

// access previous versions, if any
if(isset($item['id']) && $editable && $has_versions) {
	Skin::define_img('HISTORY_TOOL_IMG', 'icons/tools/history.gif');
	$context['page_menu'] = array_merge($context['page_menu'], array( Versions::get_url('article:'.$item['id'], 'list') => array('', HISTORY_TOOL_IMG.i18n::s('History'), '', 'basic', '', i18n::s('Previous versions of this page')) ));
}

// publish, for associates and authenticated editors
if(isset($item['id']) && $editable) {

	if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE)) {
		Skin::define_img('PUBLISH_ARTICLE_IMG', 'icons/articles/publish.gif');
		$context['page_menu'] = array_merge($context['page_menu'], array( Articles::get_url($item['id'], 'publish') => PUBLISH_ARTICLE_IMG.i18n::s('Publish') ));
	} else {
		Skin::define_img('DRAFT_ARTICLE_IMG', 'icons/articles/draft.gif');
		$context['page_menu'] = array_merge($context['page_menu'], array( Articles::get_url($item['id'], 'unpublish') => DRAFT_ARTICLE_IMG.i18n::s('Draft') ));
	}
}

// lock command provided to associates and authenticated editors
if(isset($item['id']) && (Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable()))) {

	if(!isset($item['locked']) || ($item['locked'] == 'N')) {
		Skin::define_img('LOCK_TOOL_IMG', 'icons/tools/lock.gif');
		$context['page_menu'] = array_merge($context['page_menu'], array( Articles::get_url($item['id'], 'lock') => LOCK_TOOL_IMG.i18n::s('Lock') ));
	} else {
		Skin::define_img('UNLOCK_TOOL_IMG', 'icons/tools/unlock.gif');
		$context['page_menu'] = array_merge($context['page_menu'], array( Articles::get_url($item['id'], 'lock') => UNLOCK_TOOL_IMG.i18n::s('Unlock') ));
	}
}

// assign command provided to associates and authenticated editors
if(isset($item['id']) && Surfer::is_associate()) {
	Skin::define_img('ASSIGN_TOOL_IMG', 'icons/tools/assign.gif');
	$context['page_menu'] = array_merge($context['page_menu'], array( Users::get_url('article:'.$item['id'], 'select') => ASSIGN_TOOL_IMG.i18n::s('Assign') ));
}

// review command provided to associates and section editors
if(isset($item['id']) && $editable) {
	Skin::define_img('STAMP_ARTICLE_IMG', 'icons/articles/stamp.gif');
	$context['page_menu'] = array_merge($context['page_menu'], array( Articles::get_url($item['id'], 'stamp') => STAMP_ARTICLE_IMG.i18n::s('Stamp') ));
}

// delete command provided to associates and section editors
if(isset($item['id']) && $editable) {
	Skin::define_img('DELETE_ARTICLE_IMG', 'icons/articles/delete.gif');
	$context['page_menu'] = array_merge($context['page_menu'], array( Articles::get_url($item['id'], 'delete') => DELETE_ARTICLE_IMG.i18n::s('Delete') ));
}

// not found -- help web crawlers
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name'])));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// display the thread
} else {

	// behaviors can change page menu
	if(is_object($behaviors))
		$context['page_menu'] =& $behaviors->add_commands('articles/view.php', 'article:'.$item['id'], $context['page_menu']);

	// remember surfer visit
	Surfer::click('article:'.$item['id'], $item['active']);

	// increment silently the hits counter if not associate, nor creator -- editors are taken into account
	if(Surfer::is_associate())
		;
	elseif(Surfer::get_id() && isset($item['create_id']) && (Surfer::get_id() == $item['create_id']))
		;
	else {
		$item['hits'] = isset($item['hits'])?($item['hits']+1):1;
		Articles::increment_hits($item['id']);
	}

	// initialize the rendering engine
	Codes::initialize(Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']));

	// add meta information, if any
	if(isset($item['meta']) && $item['meta'])
		$context['page_header'] .= $item['meta'];

	// set specific headers
	if(isset($item['introduction']) && $item['introduction'])
		$context['page_description'] = $item['introduction'];
	if(isset($item['create_name']) && $item['create_name'])
		$context['page_author'] = $item['create_name'];
	if(isset($item['publish_name']) && $item['publish_name'])
		$context['page_publisher'] = $item['publish_name'];

	// the article icon, if any --no section icon here...
	if(isset($item['icon_url']) && $item['icon_url'])
		$context['page_image'] = $item['icon_url'];

	//
	// compute page details -- $context['page_details']
	//

	// cache page details because of tags
	$cache_id = 'articles/view_as_thread.php?id='.$item['id'].'#page_details';
	if(!$text =& Cache::get($cache_id)) {

		// tags, if any
		if(isset($item['tags']) && $item['tags']) {
			$tags = explode(',', $item['tags']);
			$line = '';
			foreach($tags as $tag) {
				if($category = Categories::get_by_keyword(trim($tag)))
					$line .= Skin::build_link(Categories::get_url($category['id'], 'view', trim($tag)), trim($tag), 'basic').' ';
				else
					$line .= trim($tag).' ';
			}
			$text .= '<p class="tags">'.sprintf(i18n::s('Tags: %s'), trim($line)).'</p>'."\n";
		}

		// one detail per line
		$text .= '<p class="details">';
		$details = array();

		// article rating, if the anchor allows for it, and if no rating has already been registered
		if(is_object($anchor) && $anchor->has_option('with_rating') && !$anchor->has_option('rate_as_digg')) {

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
			$details[] = RESTRICTED_FLAG.' '.i18n::s('Access is restricted to authenticated members');

		// restricted to associates
		elseif($item['active'] == 'N')
			$details[] = PRIVATE_FLAG.' '.i18n::s('Access is restricted to associates and editors');

		// home panel
		if(Surfer::is_associate()) {
			if(isset($item['home_panel']) && ($item['home_panel'] == 'none'))
				$details[] = i18n::s('This page is NOT displayed at the front page.');
		}

		// expired article
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		if((Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable()))
				&& ($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now)) {
			$details[] = EXPIRED_FLAG.' '.sprintf(i18n::s('Article has expired %s'), Skin::build_date($item['expiry_date']));
		}

		// article editors, for associates and section editors
		if((Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable())) && ($items = Members::list_users_by_posts_for_member('article:'.$item['id'], 0, USERS_LIST_SIZE, 'compact'))) {

			// list all assigned users
			$details[] = Skin::build_link(Users::get_url('article:'.$item['id'], 'select'), i18n::s('Editors:'), 'basic').' '.Skin::build_list($items, 'comma');

		}

		// no more details
		if(count($details))
			$text .= ucfirst(implode(BR."\n", $details)).BR."\n";

		// other details
		$details = array();

		// the nick name
		if($item['nick_name'] && (Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable())))
			$details[] = '"'.$item['nick_name'].'"';

		// the creator of this article, if associate or if editor or if not prevented globally or if section option
		if($item['create_date']
			&& (Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable())
					|| ((!isset($context['content_without_details']) || ($context['content_without_details'] != 'Y')) || (is_object($anchor) && $anchor->has_option('with_details')) ) ) ) {

			if($item['create_name'])
				$details[] = sprintf(i18n::s('posted by %s %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']), Skin::build_date($item['create_date']));
			else
				$details[] = Skin::build_date($item['create_date']);

		}

		// the publisher of this article, if any
		if(($item['publish_date'] > NULL_DATE)
			&& !strpos($item['edit_action'], ':publish')
			&& (Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable()))) {

			if($item['publish_name'])
				$details[] = sprintf(i18n::s('published by %s %s'), Users::get_link($item['publish_name'], $item['publish_address'], $item['publish_id']), Skin::build_date($item['publish_date']));
			else
				$details[] = Skin::build_date($item['publish_date']);
		}

		// last modification by creator, and less than 24 hours between creation and last edition
		if(($item['create_date'] > NULL_DATE) && ($item['create_id'] == $item['edit_id'])
				&& (SQL::strtotime($item['create_date'])+24*60*60 >= SQL::strtotime($item['edit_date'])))
			;
		// publication is the last action
		elseif(strpos($item['edit_action'], ':publish'))
			;
		elseif(Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable())
			|| ((!isset($context['content_without_details']) || ($context['content_without_details'] != 'Y')) || (is_object($anchor) && $anchor->has_option('with_details')) ) ) {

			if($item['edit_action'])
				$action = get_action_label($item['edit_action']).' ';
			else
				$action = i18n::s('edited');

			if($item['edit_name'])
				$details[] = sprintf(i18n::s('%s by %s %s'), $action, Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));
			else
				$details[] = $action.' '.Skin::build_date($item['edit_date']);

		}

		// last revision, if any
		if(isset($item['review_date']) && ($item['review_date'] > NULL_DATE) && Surfer::is_associate())
			$details[] = sprintf(i18n::s('reviewed %s'), Skin::build_date($item['review_date'], 'no_hour'));

		// signal articles to be published
		if(($item['publish_date'] <= NULL_DATE)) {
			if(Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable()))
				$label = Skin::build_link(Articles::get_url($item['id'], 'publish'), i18n::s('not published'));
			else
				$label = i18n::s('not published');
			$details[] = DRAFT_FLAG.' '.$label;
		}

		// the number of hits
		if(($item['hits'] > 1)
			&& (Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable())
					|| ((!isset($context['content_without_details']) || ($context['content_without_details'] != 'Y')) || (is_object($anchor) && $anchor->has_option('with_details')) ) ) ) {

			// flag popular pages
			$popular = '';
			if($item['hits'] > 100)
				$popular = POPULAR_FLAG;

			// actually show numbers only to associates and editors
			if(Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable()) )
				$details[] = $popular.sprintf(i18n::s('%d hits'), $item['hits']);

			// show first hits
			elseif($item['hits'] < 100)
				$details[] = $popular.sprintf(i18n::s('%d hits'), $item['hits']);

			// other surfers will benefit from a stable ETag
			elseif($popular)
				$details[] = $popular;
		}

		// rank for this article
		if((Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable())) && (intval($item['rank']) != 10000))
			$details[] = '{'.$item['rank'].'}';

		// locked article
		if(Surfer::is_member() && isset($item['locked']) && ($item['locked'] == 'Y') )
			$details[] = LOCKED_FLAG.' '.i18n::s('page is locked.');

		// in-line details
		if(count($details))
			$text .= ucfirst(implode(', ', $details))."\n";

		$text .= "</p>\n";

		// save in cache
		Cache::put($cache_id, $text, 'article:'.$item['id']);
	}

	// update page details
	$context['page_details'] .= $text;

	//
	// compute main panel -- $context['text']
	//

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// article rating, if the anchor allows for it, and if no rating has already been registered
	if(is_object($anchor) && $anchor->has_option('with_rating') && $anchor->has_option('rate_as_digg')) {

		// rating
		if($item['rating_count'])
			$rating_label = sprintf(i18n::ns('%s vote', '%s votes', $item['rating_count']), '<span class="big">'.$item['rating_count'].'</span>'.BR);
		else
			$rating_label = i18n::s('No vote');

		// present results
		$context['text'] .= '<div class="digg"><div class="votes">'.$rating_label.'</div>'
			.'<div class="rate">'.Skin::build_link(Articles::get_url($item['id'], 'rate'), i18n::s('Rate it'), 'basic').'</div>'
			.'</div>';

		// signal DIGG
		define('DIGG', TRUE);
	}

	// the introduction text, if any
	if(is_object($overlay))
		$context['text'] .= Skin::build_block($overlay->get_text('introduction', $item), 'introduction');
	elseif(isset($item['introduction']) && trim($item['introduction']))
		$context['text'] .= Skin::build_block($item['introduction'], 'introduction');
	else
		$context['text'] .= BR;

	// get text related to the overlay, if any
	if(is_object($overlay))
		$context['text'] .= $overlay->get_text('view', $item);

	// the beautified description, which is the actual page body
	if(trim($item['description']))
		$context['text'] .= '<div id="description">'.Codes::beautify($item['description'], $item['options'])."</div>\n";

	// special layout for digg
	if(defined('DIGG'))
		$context['text'] = '<div class="digg_content">'.$context['text'].'</div>';

	//
	// comments - the real-time chatting space
	//

	// conversation is over
	if(isset($item['locked']) && ($item['locked'] == 'Y')) {

		// display a transcript of past comments
		include_once $context['path_to_root'].'comments/comments.php';
		$items = Comments::list_by_date_for_anchor('article:'.$item['id'], 0, 500, 'excerpt');
		if(is_array($items))
			$context['text'] .= Skin::build_list($items, 'rows');
		elseif(is_string($items))
			$context['text'] .= $items;

	// on-going conversation
	} else {

		// start of thread wrapper
		$context['text'] .= '<div id="thread_wrapper">'."\n";

		// text panel
		$context['text'] .= '<div id="thread_text_panel"><img src="'.$context['url_to_root'].'skins/_reference/ajax_spinner.gif" /></div>'."\n";

		// surfer cannot contribute
		if(!Comments::are_allowed($anchor, $item))
			;

		// the input panel is where logged surfers can post data
		elseif(Surfer::is_logged()) {
			$context['text'] .= '<form method="post" action="#" onsubmit="javascript:Comments.contribute($(\'contribution\').value);return false;" id="thread_input_panel">'."\n"
				.'<p style="width: 100%; margin: 0 0 0.3em 0; padding: 0;"><textarea rows="2" cols="50" name="contribution" id="contribution" ></textarea></p>'."\n"
				.'<p style="width: 100%; margin: 0 0 0.3em 0; padding: 0;">';

			// user commands
			$menu = array();

			// the submit button
			$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's', 'submit', 'no_spin_on_click');

			// upload a file
			if(Files::are_allowed($anchor, $item))
				$menu[] = Skin::build_link('files/edit.php?anchor='.urlencode('article:'.$item['id']), FILE_TOOL_IMG.i18n::s('Upload a file'), 'span');

			// view thread history
			$menu[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'list'), i18n::s('View history'), 'span');

			// display my VNC session
			if(isset($_SESSION['with_sharing']) && ($_SESSION['with_sharing'] == 'V')) {

				// use either explicit or implicit address
				if(isset($_SESSION['proxy_address']) && $_SESSION['proxy_address'])
					$link = 'http://'.$_SESSION['proxy_address'].':5800';
				elseif(isset($_SESSION['workstation_id']) && $_SESSION['workstation_id'])
					$link = 'http://'.$_SESSION['workstation_id'].':5800';
				else
					$link = 'http://'.$context['host_name'].':5800';

				// open the link in an external window
				$link = '<a href="'.$link.'" title="'.i18n::s('Browse in a separate window').'" onclick="window.open(this.href); return false;"><span>'.sprintf(i18n::s('Screen shared by %s'), Surfer::get_name()).'</span></a>';

				// append the command to the menu
				$menu[] = '<a href="#" onclick="javascript:Comments.contribute(\''.str_replace('"', '&quot;', $link).'\');return false;" title="'.i18n::s('Share screen with VNC').'"><span>'.i18n::s('Share screen with VNC').'</span></a>';
			}

			// share my netmeeting session
			if(isset($_SESSION['with_sharing']) && ($_SESSION['with_sharing'] == 'M')) {

				// link to the session descriptor
				$link = $context['url_to_home'].$context['url_to_root'].Users::get_url(Surfer::get_id(), 'share');

				// open the link in an external window
				$link = '<a href="'.$link.'" title="'.i18n::s('Browse in a separate window').'" onclick="window.open(this.href); return false;"><span>'.sprintf(i18n::s('Shared screen of %s'), Surfer::get_name()).'</span></a>';

				// append the command to the menu
				$menu[] = '<a href="#" onclick="javascript:Comments.contribute(\''.str_replace('"', '&quot;', $link).'\');return false;" title="'.i18n::s('Share screen with NetMeeting').'"><span>'.i18n::s('Share screen with NetMeeting').'</span></a>';
			}

			// augment panel size
			$menu[] = '<a href="#" onclick="Comments.showMore(); return false;">'.i18n::s('Show more lines').'</a>';

			// display all commands
			$context['text'] .= Skin::finalize_list($menu, 'menu_bar');

			// an option to submit with the Enter key
			$context['text'] .= '<input type="checkbox" id="submitOnEnter" checked="checked" /> '.i18n::s('Submit text when Enter is pressed.');

			// end the form
			$context['text'] .= '</form>'."\n";

		// other surfers are invited to authenticate
		} else {

			$context['text'] .= '<div id="thread_input_panel">';

			// url of this page
			if(isset($item['id'])) {
				$menu = array('users/login.php?url='.urlencode(Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']))=> i18n::s('Authenticate or register to contribute to this thread'));
				$context['text'] .= Skin::build_list($menu, 'menu_bar');
			}

			$context['text'] .= '</div>'."\n";

		}

		// end of the wrapper
		$context['text'] .= '</div>'."\n";
	}

	//
	// trailer information
	//

	// add trailer information from the overlay, if any
	if(is_object($overlay))
		$context['text'] .= $overlay->get_text('trailer', $item);

	// add trailer information from this item, if any
	if(isset($item['trailer']) && trim($item['trailer']))
		$context['text'] .= Codes::beautify($item['trailer']);

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	//
	// extra panel -- most content is cached, except commands specific to current surfer
	//

	// cache content
	$cache_id = 'articles/view_as_thread.php?id='.$item['id'].'#extra#head';
	if(!$text =& Cache::get($cache_id)) {

		// participants
		//

		// advertise this thread
		$invite = '';
		if(isset($context['with_email']) && ($context['with_email'] == 'Y')) {
			Skin::define_img('MAIL_TOOL_IMG', 'icons/tools/mail.gif');
			$invite = Skin::build_link(Articles::get_url($item['id'], 'mail', 'invite'), MAIL_TOOL_IMG.i18n::s('Invite people'), 'basic', i18n::s('Spread the word'), TRUE);
		}

		// thread participants
		if(!isset($item['locked']) || ($item['locked'] != 'Y'))
			$text .= Skin::build_box(i18n::s('Participants'), '<div id="thread_roster_panel"></div>'.$invite, 'extra', 'roster');

		// files
		//

		// the command to post a new file -- do that in this window, since the surfer will be driven back here
		$invite = '';
		if(Files::are_allowed($anchor, $item)) {
			$link = 'files/edit.php?anchor='.urlencode('article:'.$item['id']);
			$invite = Skin::build_link($link, FILE_TOOL_IMG.i18n::s('Upload a file'), 'basic').BR;
		}

		// list files by date (default) or by title (option files_by_title)
		if(preg_match('/\bfiles_by_title\b/i', $item['options']))
			$items = Files::list_by_title_for_anchor('article:'.$item['id'], 0, 20, 'compact');
		else
			$items = Files::list_by_date_for_anchor('article:'.$item['id'], 0, 20, 'compact');

		// actually render the html
		if(is_array($items))
			$items = Skin::build_list($items, 'compact');

		// display this aside the thread
		if($items.$invite)
			$text .= Skin::build_box(i18n::s('Files'), '<div id="thread_files_panel">'.$items.'</div>'.$invite, 'extra', 'files');

		// links
		//

		// new links are allowed
		$invite = '';
		if(Links::are_allowed($anchor, $item)) {
			Skin::define_img('NEW_LINK_IMG', 'icons/links/new.gif');
			$link = 'links/edit.php?anchor='.urlencode('article:'.$item['id']);
			$invite = Skin::build_link($link, NEW_LINK_IMG.i18n::s('Add a link'));
		}

		// list links by date (default) or by title (option links_by_title)
		if(preg_match('/\blinks_by_title\b/i', $item['options']))
			$items = Links::list_by_title_for_anchor('article:'.$item['id'], 0, 20, 'compact');
		else
			$items = Links::list_by_date_for_anchor('article:'.$item['id'], 0, 20, 'compact');

		// actually render the html
		if(is_array($items))
			$items = Skin::build_list($items, 'compact');

		// display this aside the thread
		if($items.$invite)
			$text .= Skin::build_box(i18n::s('Links'), '<div>'.$items.'</div>'.$invite, 'extra', 'links');

		// add extra information from this item, if any
		if(isset($item['extra']) && $item['extra'])
			$text .= Codes::beautify_extra($item['extra']);

		// add extra information from the overlay, if any
		if(is_object($overlay))
			$text .= $overlay->get_text('extra', $item);

		// save in cache
		Cache::put($cache_id, $text, 'article:'.$item['id']);
	}

	// update the extra panel
	$context['extra'] .= $text;

	// page tools
	//

	// update tools
	if(!$zoom_type && $editable) {

		// modify this page
		Skin::define_img('EDIT_ARTICLE_IMG', 'icons/articles/edit.gif');
		$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'edit'), EDIT_ARTICLE_IMG.i18n::s('Edit this page'), 'basic', i18n::s('Update the content of this page'));

		// post an image, if upload is allowed
		if(Images::are_allowed($anchor, $item)) {
			Skin::define_img('IMAGE_TOOL_IMG', 'icons/tools/image.gif');
			$context['page_tools'][] = Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']), IMAGE_TOOL_IMG.i18n::s('Add an image'), 'basic', i18n::s('You can upload a camera shot, a drawing, or any image file, to illustrate this page.'));
		}

		// attach a file, if upload is allowed
		if(Files::are_allowed($anchor, $item))
			$context['page_tools'][] = Skin::build_link('files/edit.php?anchor='.urlencode('article:'.$item['id']), FILE_TOOL_IMG.i18n::s('Upload a file'), 'basic', i18n::s('Do not hesitate to attach files related to this page.'));

		// comment this page if anchor does not prevent it
		if(Comments::are_allowed($anchor, $item))
			$context['page_tools'][] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'comment'), COMMENT_TOOL_IMG.i18n::s('Add a comment'), 'basic', i18n::s('Express yourself, and say what you think.'));

		// add a link
		if(Links::are_allowed($anchor, $item))
			$context['page_tools'][] = Skin::build_link('links/edit.php?anchor='.urlencode('article:'.$item['id']), LINK_TOOL_IMG.i18n::s('Add a link'), 'basic', i18n::s('Contribute to the web and link to relevant pages.'));
	}

	// spreading tools
	//

	// mail this page
	if(!$zoom_type && $editable && Surfer::get_email_address() && isset($context['with_email']) && ($context['with_email'] == 'Y')) {
		Skin::define_img('MAIL_TOOL_IMG', 'icons/tools/mail.gif');
		$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'mail'), MAIL_TOOL_IMG.i18n::s('Invite people'), 'basic', '', i18n::s('Spread the word'));
	}

	// the command to track back -- complex command
	if(Surfer::is_logged() && Surfer::has_all()) {
		Skin::define_img('TRACKBACK_IMG', 'icons/links/trackback.gif');
		$context['page_tools'][] = Skin::build_link('links/trackback.php?anchor='.urlencode('article:'.$item['id']), TRACKBACK_IMG.i18n::s('Reference'), 'basic', i18n::s('Various means to link to this page'));
	}

	// more tools
	if(((isset($context['with_bottom_tools']) && ($context['with_bottom_tools'] == 'Y'))
		|| (is_object($anchor) && $anchor->has_option('with_bottom_tools')))) {

		// get a PDF version
		if(Surfer::is_member() || ($context['with_anonymous_bottom_tools'] === 'Y')) {
			Skin::define_img('PDF_TOOL_IMG', 'icons/tools/pdf.gif');
			$context['page_tools'][] = Skin::build_link(Articles::get_url($id, 'fetch_as_pdf'), PDF_TOOL_IMG.i18n::s('PDF'), 'basic', i18n::s('Download this page as a PDF file.'));
		}

		// open in Word
		if(Surfer::is_member() || ($context['with_anonymous_bottom_tools'] === 'Y')) {
			Skin::define_img('MSWORD_TOOL_IMG', 'icons/tools/word.gif');
			$context['page_tools'][] = Skin::build_link(Articles::get_url($id, 'fetch_as_msword'), MSWORD_TOOL_IMG.i18n::s('MS-Word'), 'basic', i18n::s('Copy this page in Microsoft MS-Word.'));
		}

		// get a palm version
		if(Surfer::is_member() || ($context['with_anonymous_bottom_tools'] === 'Y')) {
			Skin::define_img('PALM_TOOL_IMG', 'icons/tools/palm.gif');
			$context['page_tools'][] = Skin::build_link(Articles::get_url($id, 'fetch_for_palm'), PALM_TOOL_IMG.i18n::s('Palm'), 'basic', i18n::s('Fetch this page as a Palm memo.'));
		}

	}

	// print this page
	if(Surfer::is_logged()) {
		Skin::define_img('PRINT_TOOL_IMG', 'icons/tools/print.gif');
		$context['page_tools'][] = Skin::build_link(Articles::get_url($id, 'print'), PRINT_TOOL_IMG.i18n::s('Print'), 'basic', i18n::s('Get a paper copy of this page.'));
	}

	// export to XML command provided to associates -- complex command
	if(!$zoom_type && Surfer::is_associate() && Surfer::has_all()) {
		Skin::define_img('EXPORT_TOOL_IMG', 'icons/tools/export.gif');
		$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'export'), EXPORT_TOOL_IMG.i18n::s('Export'), 'basic');
	}

	// how to stay tuned
	$lines = array();

	// watch command is provided to logged surfers
	if(Surfer::get_id() && !$zoom_type) {

		$link = Users::get_url('article:'.$item['id'], 'track');

		if($in_watch_list) {
			$label = i18n::s('Forget');
			Skin::define_img('WATCH_TOOL_IMG', 'icons/tools/watch.gif');
			$lines[] = Skin::build_link($link, WATCH_TOOL_IMG.$label, 'basic', i18n::s('Manage your watch list'));
		}
	}

	// get news from rss
	if(isset($item['id']) && (!isset($context['skins_general_without_feed']) || ($context['skins_general_without_feed'] != 'Y')) ) {

		// list of attached files
		$lines[] = Skin::build_link($context['url_to_home'].$context['url_to_root'].Files::get_url('article:'.$item['id'], 'feed'), i18n::s('Recent files'), 'xml');

		// comments are allowed
		if(Comments::are_allowed($anchor, $item)) {
			$lines[] = Skin::build_link($context['url_to_home'].$context['url_to_root'].Comments::get_url('article:'.$item['id'], 'feed'), i18n::s('Recent comments'), 'xml');

			// public aggregators
			if(!isset($context['without_internet_visibility']) || ($context['without_internet_visibility'] != 'Y'))
				$lines[] = join(BR, Skin::build_subscribers($context['url_to_home'].$context['url_to_root'].Comments::get_url('article:'.$item['id'], 'feed'), $item['title']));
		}
	}

	// in a side box
	if(count($lines))
		$text .= Skin::build_box(i18n::s('Stay tuned'), join(BR, $lines), 'extra', 'feeds');

	// cache content
	$cache_id = 'articles/view_as_thread.php?id='.$item['id'].'#extra#tail';
	if(!$text =& Cache::get($cache_id)) {

		// twin pages
		if(isset($item['nick_name']) && $item['nick_name']) {

			// build a complete box
			$box['text'] = '';

			// list pages with same name
			$items = Articles::list_for_name($item['nick_name'], $item['id'], 'compact');

			// actually render the html for the section
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'compact');
			if($box['text'])
				$text .= Skin::build_box(i18n::s('Related'), $box['text'], 'extra', 'twins');

		}

		// links to previous and next pages in this section, if any
		if(is_object($anchor) && !$anchor->has_option('no_neighbours') && ($context['skin_variant'] != 'mobile')) {

			// build a nice sidebar box
			if(isset($neighbours) && ($content = Skin::neighbours($neighbours, 'sidebar')))
				$text .= Skin::build_box(i18n::s('Navigation'), $content, 'navigation', 'neighbours');

		}

		// the contextual menu, in a navigation box, if this has not been disabled
		if( (!is_object($anchor) || !$anchor->has_option('no_contextual_menu'))
			&& isset($context['current_focus']) && ($menu =& Skin::build_contextual_menu($context['current_focus']))) {

			// use title from topmost level
			if(count($context['current_focus']) && ($top_anchor = Anchors::get($context['current_focus'][0]))) {
				$box_title = $top_anchor->get_title();
				$box_url = $top_anchor->get_url();

			// generic title
			} else {
				$box_title = i18n::s('Navigation');
				$box_url = '';
			}

			// in a navigation box
			$box_popup = '';
			$text .= Skin::build_box($box_title, $menu, 'navigation', 'contextual_menu', $box_url, $box_popup);
		}

		// categories attached to this article, if not at another follow-up page
		if(!$zoom_type || ($zoom_type == 'categories')) {

			// build a complete box
			$box['bar'] = array();
			$box['text'] = '';

			// list categories by title
			$offset = ($zoom_index - 1) * CATEGORIES_PER_PAGE;
			$items = Members::list_categories_by_title_for_member('article:'.$item['id'], $offset, CATEGORIES_PER_PAGE, 'sidebar');

			// the command to change categories assignments
			if(Categories::are_allowed($anchor, $item))
				$items = array_merge($items, array( Categories::get_url('article:'.$item['id'], 'select') => i18n::s('Assign categories') ));

			// actually render the html for the section
			if(is_array($box['bar']))
				$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'compact');
			if($box['text'])
				$text .= Skin::build_box(i18n::s('See also'), $box['text'], 'navigation', 'categories');

		}

		// nearby locations, if any
		if(!$zoom_type) {

			// locate up to 5 neighbours
			$items = Locations::list_by_distance_for_anchor('article:'.$item['id'], 0, COMPACT_LIST_SIZE);
			if(@count($items))
				$text .= Skin::build_box(i18n::s('Neighbours'), Skin::build_list($items, 'compact'), 'navigation', 'locations');

		}

		// referrals, if any
		if(!$zoom_type && (Surfer::is_associate() || (isset($context['with_referrals']) && ($context['with_referrals'] == 'Y')))) {

			// in a sidebar box
			include_once '../agents/referrals.php';
			if($content = Referrals::list_by_hits_for_url($context['url_to_root_parameter'].Articles::get_url($item['id'])))
				$text .= Skin::build_box(i18n::s('Referrals'), $content, 'navigation', 'referrals');

		}

		// save in cache
		Cache::put($cache_id, $text, 'article:'.$item['id']);

	}

	// update the extra panel
	$context['extra'] .= $text;

	//
	// meta information
	//

	// a meta link to the section front page
	if(is_object($anchor))
		$context['page_header'] .= "\n".'<link rel="contents" href="'.$context['url_to_root'].$anchor->get_url().'" title="'.encode_field($anchor->get_title()).'" type="text/html"'.EOT;

	// a meta link to a description page (actually, rdf)
	$context['page_header'] .= "\n".'<link rel="meta" href="'.$context['url_to_root'].Articles::get_url($item['id'], 'describe').'" title="Meta Information" type="application/rdf+xml"'.EOT;

	// implement the trackback interface
	$permanent_link = $context['url_to_home'].$context['url_to_root'].Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']);
	if($context['with_friendly_urls'] == 'Y')
		$trackback_link = $context['url_to_home'].$context['url_to_root'].'links/trackback.php/article/'.$item['id'];
	else
		$trackback_link = $context['url_to_home'].$context['url_to_root'].'links/trackback.php?anchor=article:'.$item['id'];
	$context['page_header'] .= "\n".'<!--'
		."\n".'<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"'
		."\n".' 		xmlns:dc="http://purl.org/dc/elements/1.1/"'
		."\n".' 		xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">'
		."\n".'<rdf:Description'
		."\n".' trackback:ping="'.$trackback_link.'"'
		."\n".' dc:identifier="'.$permanent_link.'"'
		."\n".' rdf:about="'.$permanent_link.'" />'
		."\n".'</rdf:RDF>'
		."\n".'-->';

	// implement the pingback interface
	$context['page_header'] .= "\n".'<link rel="pingback" href="'.$context['url_to_root'].'services/ping.php" title="Pingback Interface"'.EOT;

	// implement the Comment API interface
	$context['page_header'] .= "\n".'<link rel="service.comment" href="'.$context['url_to_root'].Comments::get_url('article:'.$item['id'], 'service.comment').'" title="Comment Interface" type="text/xml"'.EOT;

	// show the secret handle at an invisible place, and only to associates
	if(Surfer::is_associate() && $item['handle'])
		$context['page_header'] .= "\n".'<meta name="handle" content="'.$item['handle'].'"'.EOT;

	//
	// the AJAX part
	//

	if(!isset($item['locked']) || ($item['locked'] != 'Y')) {

		$context['page_footer'] .= '<script type="text/javascript">// <![CDATA['."\n"
			."\n"
			.'var Comments = {'."\n"
			."\n"
			.'	url: "'.$context['url_to_home'].$context['url_to_root'].Comments::get_url($item['id'], 'thread').'",'."\n"
			.'	timestamp: 0,'."\n"
			."\n"
			.'	initialize: function() { },'."\n"
			."\n"
			.'	contribute: function(request) {'."\n"
			.'		// contribute to the thread'."\n"
			.'		new Ajax.Request(Comments.url, {'."\n"
			.'			method: "post",'."\n"
			.'			parameters: { "message" : request },'."\n"
			.'			onSuccess: function(transport) {'."\n"
			.'				$("contribution").value="";'."\n"
			.'				$("contribution").focus();'."\n"
			.'				setTimeout("Comments.subscribe()", 1000);'."\n"
			.'			},'."\n"
			.'			onFailure: function(transport) {'."\n"
			.'				var response = transport.responseText;'."\n"
			.'				if(!response) {'."\n"
			.'					response = "'.i18n::s('Your contribution has not been posted.').'";'."\n"
			.'				}'."\n"
			.'				response += "\n\n'.i18n::s('Do you agree to reload this page?').'";'."\n"
			.'				if(confirm(response)) {'."\n"
			.'					window.location.reload();'."\n"
			.'				}'."\n"
			.'			}'."\n"
			.'		});'."\n"
			.'	},'."\n"
			."\n"
			.'	keypress: function(event) {'."\n"
			.'		if(($("submitOnEnter").checked) && (event.keyCode == Event.KEY_RETURN)) {'."\n"
			.'			Comments.contribute($(\'contribution\').value);'."\n"
			.'		}'."\n"
			.'	},'."\n"
			."\n"
			.'	showMore: function() {'."\n"
			.'		var options = {};'."\n"
			.'		var newHeight = $("thread_text_panel").clientHeight + 200;'."\n"
			.'		options.height =  newHeight + "px";'."\n"
			.'		options.maxHeight =  newHeight + "px";'."\n"
			.'		$("thread_text_panel").setStyle(options);'."\n"
			.'	},'."\n"
			."\n"
			.'	subscribe: function() {'."\n"
			.'		Comments.subscribeAjax = new Ajax.Request(Comments.url, {'."\n"
			.'			method: "get",'."\n"
			.'			parameters: { "timestamp" : this.timestamp },'."\n"
			.'			requestHeaders: {Accept: "application/json"},'."\n"
			.'			onSuccess: Comments.updateOnSuccess'."\n"
			.'		});'."\n"
			.'	},'."\n"
			."\n"
			.'	updateOnSuccess: function(transport) {'."\n"
			.'		var response = transport.responseText.evalJSON(true);'."\n"
			.'		$("thread_text_panel").innerHTML = "<div>" + response["items"] + "</div>";'."\n"
			.'		$("thread_text_panel").scrollTop = $("thread_text_panel").scrollHeight;'."\n"
			.'		if(typeof this.windowOriginalTitle != "string")'."\n"
			.'			this.windowOriginalTitle = document.title;'."\n"
			.'		document.title = "[" + response["name"] + "] " + this.windowOriginalTitle;'."\n"
			.'		Comments.timestamp = response["timestamp"];'."\n"
			.'	}'."\n"
			."\n"
			.'}'."\n"
			."\n"
			.'// wait for new comments'."\n"
			.'Comments.subscribeTimer = setInterval("Comments.subscribe()", 15000);'."\n"
			."\n"
			.'// update the roster, in the background'."\n"
			.'new Ajax.PeriodicalUpdater("thread_roster_panel", "'.$context['url_to_home'].$context['url_to_root'].Users::get_url($item['id'], 'visit').'",'."\n"
			.'	{ method: "get", frequency: 59, decay: 1 });'."\n"
			."\n"
			.'// update attached files, in the background'."\n"
			.'new Ajax.PeriodicalUpdater("thread_files_panel", "'.$context['url_to_home'].$context['url_to_root'].Files::get_url($item['id'], 'thread').'",'."\n"
			.'	{ method: "get", frequency: 181, decay: 1 });'."\n";

		// only authenticated surfers can contribute
		if(Surfer::is_logged() && Comments::are_allowed($anchor, $item))
			$context['page_footer'] .= "\n"
				.'// ready to type something'."\n"
				.'Event.observe(window, \'load\', function() { $(\'contribution\').focus(); Comments.subscribe(); });'."\n"
				."\n"
				.'// send contribution on Enter'."\n"
				.'Event.observe(\'contribution\', \'keypress\', Comments.keypress);'."\n";

		// end of the AJAX part
		$context['page_footer'] .= '// ]]></script>'."\n";
	}

	//
	// put this page in visited items
	//

	if(!isset($context['pages_without_history']) || ($context['pages_without_history'] != 'Y')) {

		// put at top of stack
		if(!isset($_SESSION['visited']))
			$_SESSION['visited'] = array();
		$_SESSION['visited'] = array_merge(array(Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']) => Codes::beautify($item['title'])), $_SESSION['visited']);

		// limit to 7 most recent pages
		if(count($_SESSION['visited']) > 7)
			array_pop($_SESSION['visited']);

	}

}

// render the skin
render_skin();

?>