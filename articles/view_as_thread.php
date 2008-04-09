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
 * This script uses AJAX in a COMET architecture. This means that Javascript
 * is mandatory, and that refresh requests made to server are blocked until
 * new contributions are made.
 *
 * @link http://en.wikipedia.org/wiki/Comet_(programming)
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

// stop hackers
if(count(get_included_files()) < 3) {
	echo 'Script must be included';
	return;
}

// load the skin, maybe with a variant
load_skin('view_as_thread', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'articles/' => i18n::s('Articles') );

// page title
if(is_object($overlay) && is_callable(array($overlay, 'get_live_title')))
	$context['page_title'] = $overlay->get_live_title($item);
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

// watch command is provided to logged surfers who are not contributing -- no forget command
if(isset($item['create_id']) && $permitted && Surfer::get_id() && !Surfer::is_creator($item['create_id']) && !$in_watch_list) {
	$link = Users::get_url('article:'.$item['id']);
	Skin::define_img('WATCH_TOOL_IMG', $context['skin'].'/icons/tools/watch.gif');
	$context['page_menu'] = array_merge($context['page_menu'], array( $link => array(NULL, WATCH_TOOL_IMG.i18n::s('Watch'), NULL, 'basic', NULL, i18n::s('Manage your watch list'))));
}

// modify this page
if(isset($item['id']) && $editable) {
	Skin::define_img('EDIT_ARTICLE_IMG', $context['skin'].'/icons/articles/edit.gif');
	$context['page_menu'] = array_merge($context['page_menu'], array( Articles::get_url($item['id'], 'edit') => array(NULL, EDIT_ARTICLE_IMG.i18n::s('Edit'), NULL, 'basic', NULL, i18n::s('Update the content of this page')) ));
}

// mail this page
if(isset($item['id']) && $permitted && Surfer::get_email_address() && isset($context['with_email']) && ($context['with_email'] == 'Y')) {
	Skin::define_img('MAIL_TOOL_IMG', $context['skin'].'/icons/tools/mail.gif');
	$context['page_menu'] = array_merge($context['page_menu'], array( Articles::get_url($item['id'], 'mail') => array(NULL, MAIL_TOOL_IMG.i18n::s('Invite'), NULL, 'basic', NULL, i18n::s('Invite people to review and to contribute')) ));
}

// post an image
if(isset($item['id']) && $permitted && $editable && Images::are_allowed($anchor, $item)) {
	Skin::define_img('IMAGE_TOOL_IMG', $context['skin'].'/icons/tools/image.gif');
	$context['page_menu'] = array_merge($context['page_menu'], array( 'images/edit.php?anchor='.urlencode('article:'.$item['id']) => IMAGE_TOOL_IMG.i18n::s('Add an image') ));
}

// upload a file
if(isset($item['id']) && $permitted && $editable && Files::are_allowed($anchor, $item))
	$context['page_menu'] = array_merge($context['page_menu'], array( 'files/edit.php?anchor='.urlencode('article:'.$item['id']) => FILE_TOOL_IMG.i18n::s('Upload a file') ));

// add a link
if(isset($item['id']) && $permitted && $editable && Links::are_allowed($anchor, $item))
	$context['page_menu'] = array_merge($context['page_menu'], array( 'links/edit.php?anchor='.urlencode('article:'.$item['id']) => LINK_TOOL_IMG.i18n::s('Add a link') ));

// publish, for associates and authenticated editors
if(isset($item['id']) && (Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable()))) {

	if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE)) {
		Skin::define_img('PUBLISH_ARTICLE_IMG', $context['skin'].'/icons/articles/publish.gif');
		$context['page_menu'] = array_merge($context['page_menu'], array( Articles::get_url($item['id'], 'publish') => PUBLISH_ARTICLE_IMG.i18n::s('Publish') ));
	} else {
		Skin::define_img('DRAFT_ARTICLE_IMG', $context['skin'].'/icons/articles/draft.gif');
		$context['page_menu'] = array_merge($context['page_menu'], array( Articles::get_url($item['id'], 'unpublish') => DRAFT_ARTICLE_IMG.i18n::s('Draft') ));
	}
}

// lock command provided to associates and authenticated editors
if(isset($item['id']) && (Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable()))) {

	if(!isset($item['locked']) || ($item['locked'] == 'N')) {
		Skin::define_img('LOCK_TOOL_IMG', $context['skin'].'/icons/tools/lock.gif');
		$context['page_menu'] = array_merge($context['page_menu'], array( Articles::get_url($item['id'], 'lock') => LOCK_TOOL_IMG.i18n::s('Lock') ));
	} else {
		Skin::define_img('UNLOCK_TOOL_IMG', $context['skin'].'/icons/tools/unlock.gif');
		$context['page_menu'] = array_merge($context['page_menu'], array( Articles::get_url($item['id'], 'lock') => UNLOCK_TOOL_IMG.i18n::s('Unlock') ));
	}
}

// assign command provided to associates and authenticated editors
if(isset($item['id']) && (Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable()))) {
	Skin::define_img('ASSIGN_TOOL_IMG', $context['skin'].'/icons/tools/assign.gif');
	$context['page_menu'] = array_merge($context['page_menu'], array( Users::get_url('article:'.$item['id'], 'select') => ASSIGN_TOOL_IMG.i18n::s('Assign') ));
}

// review command provided to associates and section editors
if(isset($item['id']) && (Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable()))) {
	Skin::define_img('STAMP_ARTICLE_IMG', $context['skin'].'/icons/articles/stamp.gif');
	$context['page_menu'] = array_merge($context['page_menu'], array( Articles::get_url($item['id'], 'stamp') => STAMP_ARTICLE_IMG.i18n::s('Stamp') ));
}

// delete command provided to associates and section editors
if(isset($item['id']) && (Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable()))) {
	Skin::define_img('DELETE_ARTICLE_IMG', $context['skin'].'/icons/articles/delete.gif');
	$context['page_menu'] = array_merge($context['page_menu'], array( Articles::get_url($item['id'], 'delete') => DELETE_ARTICLE_IMG.i18n::s('Delete') ));
}

// print this page
if(isset($item['id']) && $permitted && Surfer::is_logged()) {
	Skin::define_img('PRINT_TOOL_IMG', $context['skin'].'/icons/tools/print.gif');
	$context['page_menu'] = array_merge($context['page_menu'], array( Articles::get_url($item['id'], 'print') => array(NULL, PRINT_TOOL_IMG.i18n::s('Print'), NULL, 'basic', NULL, i18n::s('Get a paper copy of this page.')) ));
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

	// remember surfer visit
	Surfer::click('article:'.$item['id'], $item['active']);

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
	// page details
	//

	// increment silently the hits counter if not associate, nor creator -- editors are taken into account
	if(Surfer::is_associate())
		;
	elseif(Surfer::get_id() && isset($item['create_id']) && (Surfer::get_id() == $item['create_id']))
		;
	else {
		$item['hits'] = isset($item['hits'])?($item['hits']+1):1;
		Articles::increment_hits($item['id']);
	}

	// details
	$context['page_details'] = '<p class="details">';
	$details = array();

	// the nick name
	if($item['nick_name'] && (Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable())))
		$details[] = '"'.$item['nick_name'].'"';

	// the publisher of this article, if any
	if(($item['publish_date'] > NULL_DATE)
			&& ( ($item['create_id'] != $item['publish_id']) || (SQL::strtotime($item['create_date'])+24*60*60 < SQL::strtotime($item['publish_date'])) )
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
	elseif(($item['publish_date'] > NULL_DATE) && ($item['edit_date'] == $item['publish_date']))
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

//	// signal articles to be published
//	if(($item['publish_date'] <= NULL_DATE)) {
//		if(Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable()))
//			$label = Skin::build_link(Articles::get_url($item['id'], 'publish'), i18n::s('not published'));
//		else
//			$label = i18n::s('not published');
//		$details[] = DRAFT_FLAG.' '.$label;
//	}

//	// the number of hits
//	if(($item['hits'] > 1)
//			&& (Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable())
//					|| ((!isset($context['content_without_details']) || ($context['content_without_details'] != 'Y')) || (is_object($anchor) && $anchor->has_option('with_details')) ) ) ) {

//			// flag popular pages
//		$popular = '';
//		if($item['hits'] > 100)
//			$popular = POPULAR_FLAG;

//		// actually show numbers only to associates and editors
//			if(Surfer::is_associate() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable()) )
//			$details[] = $popular.sprintf(i18n::s('%d hits'), $item['hits']);

//		// show first hits
//		elseif($item['hits'] < 100)
//			$details[] = $popular.sprintf(i18n::s('%d hits'), $item['hits']);

//		// other surfers will benefit from a stable ETag
//		elseif($popular)
//			$details[] = $popular;
//	}

	// in-line details
	if(count($details))
		$context['page_details'] .= ucfirst(implode(', ', $details)).BR."\n";

	// one detail per line
	$details = array();

	// restricted to logged members
	if($item['active'] == 'R')
		$details[] = RESTRICTED_FLAG.' '.i18n::s('Access is restricted to authenticated members');

	// restricted to associates
	elseif($item['active'] == 'N')
		$details[] = PRIVATE_FLAG.' '.i18n::s('Access is restricted to associates and editors');

	// locked article
	if(Surfer::is_member() && isset($item['locked']) && ($item['locked'] == 'Y') ) {
		if(is_object($anchor) && $anchor->has_layout('yabb'))
			$details[] = LOCKED_FLAG.' '.i18n::s('Topic is locked');
		else
			$details[] = LOCKED_FLAG.' '.i18n::s('Page cannot be modified');
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

	// article rating, if the anchor allows for it, and if no rating has already been registered
	if(is_object($anchor) && $anchor->has_option('with_rating')) {

		// report on current rating
		$label = '';
		if($item['rating_count'])
			$label .= Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])).' '.sprintf(i18n::ns('%d&nbsp;rate', '%d&nbsp;rates', $item['rating_count']), $item['rating_count']).' ';

		// user has not rated previously
		if(!isset($_COOKIE['rating_'.$item['id']])) {

			// ensure we have a label
			if(!$label)
				$label .= i18n::s('Rate this page');

			// link to the rating page
			$label = Skin::build_link(Articles::get_url($item['id'], 'rate'), $label, 'basic', i18n::s('Rate this page'));

		}

		// feature page rating
		$details[] = $label;
	}

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
		$details[] = i18n::s('Tags:').' '.trim($line);
	}

	// lines details
	if(count($details))
		$context['page_details'] .= ucfirst(implode(BR."\n", $details)).BR."\n";
	$context['page_details'] .= "</p>\n";

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// the introduction text, if any
	if(isset($item['introduction']) && trim($item['introduction']))
		$context['text'] .= Skin::build_block($item['introduction'], 'introduction');
	else
		$context['text'] .= BR;

	// get text related to the overlay, if any
	if(is_object($overlay))
		$context['text'] .= $overlay->get_text('view', $item);

	// the beautified description, which is the actual page body
	if(trim($item['description']))
		$context['text'] .= '<div id="description">'.Codes::beautify($item['description'], $item['options'])."</div>\n";

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
		$context['text'] .= '<div id="thread_text_panel">'.'</div>'."\n";

		// surfer cannot contribute
		if(!Comments::are_allowed($anchor, $item))
			;

		// the input panel is where logged surfers can post data
		elseif(Surfer::is_logged()) {
			$context['text'] .= '<form method="post" action="#" onsubmit="javascript:Comments.contribute($(\'contribution\').value);$(\'contribution\').value=\'\';$(\'contribution\').focus();return false;" id="thread_input_panel">'."\n"
				.'<p style="width: 100%; margin: 0 0 0.3em 0; padding: 0;"><textarea rows="2" cols="50" name="contribution" id="contribution" ></textarea></p>'."\n"
				.'<p style="width: 100%; margin: 0 0 0.3em 0; padding: 0;">';

			// user commands
			$menu = array();

			// the submit button
			$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's', 'submit', 'no_spin_on_click');

			// upload a file
			if(Files::are_allowed($anchor, $item))
				$menu[] = Skin::build_link('files/edit.php?anchor='.urlencode('article:'.$item['id']), FILE_TOOL_IMG.i18n::s('Upload a file'), 'span');

//			// augment panel size
//			$menu[] = array('<a href="#" onclick="alert($(\'thread_text_panel\').style.height); $(\'thread_text_panel\').style.height = ($(\'thread_text_panel\').style.height + 200) + \'px\'; return false;"'.i18n::s('Show more lines').'">'.i18n::s('Show more lines').'</a>', NULL);

			// view thread history
			$menu[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'list'), i18n::s('View history'), 'span');

			// display my VNC session
			if(Surfer::is_empowered()) {

				// use either explicit or implicit address
				if(isset($_SESSION['proxy_address']) && $_SESSION['proxy_address'])
					$link = 'http://'.$_SESSION['proxy_address'].':5800';
				elseif(isset($_SESSION['workstation_id']) && $_SESSION['workstation_id'])
					$link = 'http://'.$_SESSION['workstation_id'].':5800';
				else
					$link = 'http://'.$context['host_name'].':5800';

				// open the link in an external window
				$link = '<a href="'.$link.'" title="'.i18n::s('Browse in a separate window').'" onclick="window.open(this.href); return false;"><span>'.sprintf(i18n::s('%s workstation'), Surfer::get_name()).'</span></a>';

				// append the command to the menu
				$menu[] = '<a href="#" onclick="javascript:Comments.contribute(\''.str_replace('"', '&quot;', $link).'\');return false;" title="'.i18n::s('Share my VNC session').'"><span>'.i18n::s('Share my VNC session').'</span></a>';
			}

			// display all commands
			$context['text'] .= Skin::finalize_list($menu, 'menu_bar');

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

	// add trailer information from this item, if any
	if(isset($item['trailer']) && trim($item['trailer']))
		$context['text'] .= Codes::beautify($item['trailer']);

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	//
	// the extra panel
	//

	// add extra information from this item, if any
	if(isset($item['extra']) && $item['extra'])
		$context['extra'] .= Codes::beautify($item['extra']);

	// advertise this thread
	$invite = '';
	if(isset($context['with_email']) && ($context['with_email'] == 'Y')) {
		Skin::define_img('MAIL_TOOL_IMG', $context['skin'].'/icons/tools/mail.gif');
		$invite = Skin::build_link(Articles::get_url($item['id'], 'mail', 'invite'), MAIL_TOOL_IMG.i18n::s('Invite other people'), 'basic', i18n::s('Send a mail notification to people'), TRUE).BR;
	}

	// thread participants
	if(!isset($item['locked']) || ($item['locked'] != 'Y'))
		$context['extra'] .= Skin::build_box(i18n::s('Participants'), '<div id="thread_roster_panel"></div>'.$invite, 'extra', 'roster');

	// files attached to this thread
	$text = '';

	// list files by date (default) or by title (option files_by_title)
	if(preg_match('/\bfiles_by_title\b/i', $item['options']))
		$items = Files::list_by_title_for_anchor('article:'.$item['id'], 0, 20, 'compact');
	else
		$items = Files::list_by_date_for_anchor('article:'.$item['id'], 0, 20, 'compact');

	// actually render the html
	if(is_array($items))
		$text .= Skin::build_list($items, 'compact');
	elseif(is_string($items))
		$text .= $items;

	// this may be updated in the background
	$text = '<div id="thread_files_panel">'.$text.'</div>';

	// the command to post a new file -- do that in this window, since the surfer will be driven back here
	if(Files::are_allowed($anchor, $item)) {
		$link = 'files/edit.php?anchor='.urlencode('article:'.$item['id']);
		$text .= Skin::build_link($link, FILE_TOOL_IMG.i18n::s('Upload a file'), 'basic').BR;
	}

	// display this aside the thread
	if($text)
		$context['extra'] .= Skin::build_box(i18n::s('Related files'), $text, 'extra', 'files');

	// links attached to this article
	$cache_id = 'articles/view_as_thread.php?id='.$item['id'].'#links';
	if(!$text =& Cache::get($cache_id)) {

		// list links by date (default) or by title (option links_by_title)
		if(preg_match('/\blinks_by_title\b/i', $item['options']))
			$items = Links::list_by_title_for_anchor('article:'.$item['id'], 0, 20, 'compact');
		else
			$items = Links::list_by_date_for_anchor('article:'.$item['id'], 0, 20, 'compact');

		// actually render the html
		if(is_array($items))
			$text .= Skin::build_list($items, 'compact');
		elseif(is_string($items))
			$text .= $items;

		// display this aside the thread
		if($text) {

			// new links are allowed
			if(Links::are_allowed($anchor, $item)) {

				// add a link
				Skin::define_img('NEW_LINK_IMG', $context['skin'].'/icons/links/new.gif');
				$link = 'links/edit.php?anchor='.urlencode('article:'.$item['id']);
				$text .= BR.Skin::build_link($link, NEW_LINK_IMG.i18n::s('Add a link') );

			}

			$text = Skin::build_box(i18n::s('Related links'), $text, 'extra');
		}

		// save in cache
		Cache::put($cache_id, $text, 'links');

	}
	$context['extra'] .= $text;

	// twin pages
	if(isset($item['nick_name']) && $item['nick_name']) {

		// cache the section
		$cache_id = 'articles/view_as_thread.php?id='.$item['id'].'#twins';
		if(!$text =& Cache::get($cache_id)) {

			// build a complete box
			$box['text'] = '';

			// list pages with same name
			$items = Articles::list_for_name($item['nick_name'], $item['id'], 'compact');

			// actually render the html for the section
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'compact');
			if($box['text'])
				$text =& Skin::build_box(i18n::s('Related'), $box['text'], 'extra', 'twins');

			// save in cache
			Cache::put($cache_id, $text, 'articles');
		}

		// embed the box in the page
		$context['extra'] .= $text;
	}

	// categories attached to this article
	$cache_id = 'articles/view_as_thread.php?id='.$item['id'].'#categories';
	if(!$text =& Cache::get($cache_id)) {

		// build a complete box
		$box['bar'] = array();
		$box['text'] = '';

		// list categories by title
		$items = Members::list_categories_by_title_for_member('article:'.$item['id'], 0, 12, 'sidebar');

		// the command to change categories assignments
		if(Categories::are_allowed($anchor, $item))
			$items = array_merge($items, array( Categories::get_url('article:'.$item['id'], 'select') => i18n::s('Assign categories') ));

		// actually render the html for the section
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'compact');
		if($box['text'])
			$text =& Skin::build_box(i18n::s('See also'), $box['text'], 'navigation', 'categories');

		// save in cache
		Cache::put($cache_id, $text, 'categories');
	}
	$context['extra'] .= $text;

	// nearby locations, if any
	$cache_id = 'articles/view_as_thread.php?id='.$item['id'].'#nearby';
	if(!$text =& Cache::get($cache_id)) {

		// locate up to 5 neighbours
		include_once '../locations/locations.php';
		$items = Locations::list_by_distance_for_anchor('article:'.$item['id'], 1, 7);
		if(@count($items))
			$text .= Skin::build_box(i18n::s('Neighbours'), Skin::build_list($items, 'compact'), 'navigation', 'locations');

		Cache::put($cache_id, $text, 'locations');

	}
	$context['extra'] .= $text;

	// how to reference this page
	if(Surfer::is_member() && (!isset($context['pages_without_reference']) || ($context['pages_without_reference'] != 'Y')) ) {

		// in a sidebar box
		$context['extra'] .= Skin::build_box(i18n::s('Reference this page'), Codes::beautify(sprintf(i18n::s('Here, use code [escape][article=%s][/escape][nl]Elsewhere, bookmark the [link=full link]%s[/link]'), $item['id'], $context['url_to_root'].Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']))), 'navigation', 'reference');

	}

	// referrals, if any
	if(Surfer::is_associate() || (isset($context['with_referrals']) && ($context['with_referrals'] == 'Y'))) {

		// in a sidebar box
		include_once '../agents/referrals.php';
		if($text = Referrals::list_by_hits_for_url($context['url_to_root_parameter'].Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name'])))
			$context['extra'] .= Skin::build_box(i18n::s('Referrals'), $text, 'navigation', 'referrals');

	}

	//
	// feed for this page
	//

	// comments as a RSS feed
	if(isset($item['id']) && (!isset($context['pages_without_feed']) || ($context['pages_without_feed'] != 'Y')) ) {

		$content = '';

		// a feed to shared files
		$content .= Skin::build_link($context['url_to_home'].$context['url_to_root'].Files::get_url('article:'.$item['id'], 'feed'), i18n::s('podcasted files'), 'xml').BR;

		// comments are allowed
		if(Comments::are_allowed($anchor, $item)) {
			$content .= Skin::build_link($context['url_to_home'].$context['url_to_root'].Comments::get_url('article:'.$item['id'], 'feed'), i18n::s('recent comments'), 'xml').BR
				.join(BR, Skin::build_subscribers($context['url_to_home'].$context['url_to_root'].Comments::get_url('article:'.$item['id'], 'feed'), $item['title']));
		}

		// in a side box
		if($content)
			$context['extra'] .= Skin::build_box(i18n::s('Stay tuned'), $content, 'extra', 'feeds');
	}

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
			.'		new Ajax.Request(this.url, {'."\n"
			.'			method: "post",'."\n"
			.'			parameters: { "message" : request },'."\n"
			.'			onSuccess: function(transport) {'."\n"
			.'				setTimeout(function(){ Comments.subscribe() }, 1000);'."\n"
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
			.'	subscribe: function() {'."\n"
			.'		var now = new Date();'."\n"
			.'		if((typeof Comments.subscribeStamp == "object") && (now.getTime() < (Comments.subscribeStamp.getTime() + 1000)))'."\n"
			.'			return;'."\n"
			.'		Comments.subscribeStamp = now;'."\n"
			.'		if(Comments.subscribeAjax)'."\n"
			.'			return;'."\n"
			.'		if((typeof Comments.subscribeTimer == "number") && (Comments.subscribeTimer > 0)) {'."\n"
			.'			clearTimeout(Comments.subscribeTimer);'."\n"
			.'			Comments.subscribeTimer = 0;'."\n"
			.'		}'."\n"
			.'		Comments.subscribeAjax = new Ajax.Request(this.url, {'."\n"
			.'			method: "get",'."\n"
			.'			parameters: { "timestamp" : this.timestamp },'."\n"
			.'			requestHeaders: {Accept: "application/json"},'."\n"
			.'			onSuccess: function(transport) {'."\n"
			.'				var response = transport.responseText.evalJSON(true);'."\n"
			.'				Comments.handleResponse(response);'."\n"
			.'				Comments.timestamp = response["timestamp"];'."\n"
			.'				Comments.subscribeTimer = setTimeout(function(){ Comments.subscribe() }, 20000);'."\n"
			.'				Comments.subscribeAjax = null;'."\n"
			.'			},'."\n"
			.'			onFailure: function(transport) {'."\n"
			.'				if(Yacs.hasFocus) {'."\n"
			.'					Comments.subscribeTimer = setTimeout(function(){ Comments.subscribe() }, 30000);'."\n"
			.'				} else {'."\n"
			.'					Comments.subscribeTimer = setTimeout(function(){ Comments.subscribe() }, 40000);'."\n"
			.'				}'."\n"
			.'				Comments.subscribeAjax = null;'."\n"
			.'			}'."\n"
			.'		});'."\n"
			.'	},'."\n"
			.'	subscribeTimer: 0,'."\n"
			."\n"
			.'	handleResponse: function(response) {'."\n"
			.'		$("thread_text_panel").innerHTML = "<div>" + response["items"] + "</div>";'."\n"
			.'		$("thread_text_panel").scrollTop = $("thread_text_panel").scrollHeight;'."\n"
			.'		if(typeof this.windowOriginalTitle != "string")'."\n"
			.'			this.windowOriginalTitle = document.title;'."\n"
			.'		document.title = "[" + response["name"] + "] " + this.windowOriginalTitle;'."\n"
			."\n"
			.'	}'."\n"
			."\n"
			.'}'."\n"
			."\n"
			.'// wait for new comments'."\n"
			.'Event.observe(window, "load", function() { Comments.subscribe(); Event.observe(window, "focus", function() { Comments.subscribe(); }); });'."\n"
			."\n"
			.'// update the roster, in the background'."\n"
			.'new Ajax.PeriodicalUpdater("thread_roster_panel", "'.$context['url_to_home'].$context['url_to_root'].Users::get_url($item['id'], 'visit').'",'."\n"
			.'	{ method: "get", frequency: 59, decay: 1 });'."\n"
			."\n"
			.'// update attached files, in the background'."\n"
			.'new Ajax.PeriodicalUpdater("thread_files_panel", "'.$context['url_to_home'].$context['url_to_root'].Files::get_url($item['id'], 'thread').'",'."\n"
			.'	{ method: "get", frequency: 61, decay: 1 });'."\n";

		// only authenticated surfers can contribute
		if(Surfer::is_logged() && Comments::are_allowed($anchor, $item))
			$context['page_footer'] .= "\n"
				.'// ready to type something'."\n"
				.'Event.observe(window, \'load\', function() { $(\'contribution\').focus() });'."\n";
//				."\n"
//				.'// send contribution on Enter'."\n"
//				.'Event.observe(\'contribution\', \'keypress\', function(event) { if(event.keyCode == Event.KEY_RETURN) { Comments.contribute($(\'contribution\').value);setTimeout(function(){ $(\'contribution\').value=\'\' }, 5); } });'."\n";

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