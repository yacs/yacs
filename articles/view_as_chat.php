<?php
/**
 * real-time chat facility
 *
 * This script is included into [script]articles/view.php[/script], when the
 * option is set to 'view_as_chat' or 'view_as_thread'.
 *
 * On a regular page, it allows for dynamic conversations involving two people
 * or more. When the page is locked, only a transcript is displayed.
 *
 * The main panel has following elements:
 * - The article itself, with details, introduction, and main text. This may be overloaded if required.
 * - The real-time chatting space, built upon a specialized layout for comments
 * - The list of related links
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
 * @author Bernard Paques
 * @tester NickR
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// loaded from articles/view.php
defined('YACS') or exit('Script must be included');

//
// compute main panel -- $context['text']
//

// insert anchor prefix
if(is_object($anchor))
	$context['text'] .= $anchor->get_prefix();

// article rating, if the anchor allows for it, and if no rating has already been registered
if(is_object($anchor) && !$anchor->has_option('without_rating') && $anchor->has_option('rate_as_digg')) {

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

// the poster profile, if any, at the beginning of the first page
if(isset($poster['id']) && is_object($anchor))
	$context['text'] .= $anchor->get_user_profile($poster, 'prefix', Skin::build_date($item['create_date']));

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
		$home =& Sections::get_permalink($item);
		$prefix = Sections::get_url($item['id'], 'navigate', 'pages');
		$page_menu = array_merge($page_menu, Skin::navigate($home, $prefix, count($pages), 1, $page));

		$context['text'] .= Skin::build_list($page_menu, 'menu_bar');
	}
}

// add trailer information from the overlay, if any
if(is_object($overlay))
	$context['text'] .= $overlay->get_text('trailer', $item);

// the poster profile, if any, at the bottom of the page
if(isset($poster['id']) && is_object($anchor))
	$context['text'] .= $anchor->get_user_profile($poster, 'suffix', Skin::build_date($item['create_date']));

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
	$context['text'] .= '<div id="thread_text_panel"><img style="padding: 3px;" src="'.$context['url_to_root'].'skins/_reference/ajax/ajax_spinner.gif" alt="loading..."/></div>'."\n";

	// surfer cannot contribute
	if(!Comments::are_allowed($anchor, $item))
		;

	// the input panel is where logged surfers can post data
	elseif(Surfer::is_logged()) {
		$context['text'] .= '<form method="post" action="#" onsubmit="Comments.contribute($(\'contribution\').value);return false;" id="thread_input_panel">'."\n"
			.'<textarea rows="2" name="contribution" id="contribution" ></textarea>';

		// user commands
		$menu = array();

		// the submit button
		$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's', 'submit', 'no_spin_on_click');

		// upload a file
		if(Files::are_allowed($anchor, $item)) {
			Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
			$menu[] = Skin::build_link('files/edit.php?anchor='.urlencode('article:'.$item['id']), FILES_UPLOAD_IMG.i18n::s('Upload a file'), 'span');
		}

		// group other commands in a submenu
		$submenu = array();

		// augment panel size
		$submenu[] = '<a href="#" onclick="Comments.showMore(); return false;"><span>'.i18n::s('Show more lines').'</span></a>';

		// go to smileys
		$submenu[] = Skin::build_link('smileys/', i18n::s('Smileys'), 'help');

		// view thread history
		$submenu[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'list'), i18n::s('View history'), 'span');

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
			$submenu[] = '<a href="#" onclick="Comments.contribute(\''.str_replace('"', '&quot;', htmlspecialchars($link)).'\');return false;" title="'.i18n::s('Share screen with VNC').'"><span>'.i18n::s('Share screen with VNC').'</span></a>';
		}

		// share my netmeeting session
		if(isset($_SESSION['with_sharing']) && ($_SESSION['with_sharing'] == 'M')) {

			// link to the session descriptor
			$link = $context['url_to_home'].$context['url_to_root'].Users::get_url(Surfer::get_id(), 'share');

			// open the link in an external window
			$link = '<a href="'.$link.'" title="'.i18n::s('Browse in a separate window').'" onclick="window.open(this.href); return false;"><span>'.sprintf(i18n::s('Shared screen of %s'), Surfer::get_name()).'</span></a>';

			// append the command to the menu
			$submenu[] = '<a href="#" onclick="Comments.contribute(\''.str_replace('"', '&quot;', htmlspecialchars($link)).'\');return false;" title="'.i18n::s('Share screen with NetMeeting').'"><span>'.i18n::s('Share screen with NetMeeting').'</span></a>';
		}

		// group commands together
		$menu[] = Skin::build_box(i18n::s('More'), Skin::finalize_list($submenu, 'compact'), 'sliding');

		// display all commands
		$context['text'] .= Skin::finalize_list($menu, 'menu_bar');

		// an option to submit with the Enter key
		$context['text'] .= '<input type="checkbox" id="submitOnEnter" checked="checked" /> '.i18n::s('Submit text when Enter is pressed.');

		// end the form
		$context['text'] .= '</form>'."\n";

	// other surfers are invited to authenticate
	} else {

		$context['text'] .= '<div id="thread_input_panel">';

		// commands
		$menu = array();

		// url of this page
		if(isset($item['id']))
			$menu = array('users/login.php?url='.urlencode(Articles::get_permalink($item))=> i18n::s('Authenticate or register to contribute to this thread'));

		// view thread history
		$menu[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'list'), i18n::s('View history'), 'span');

		// augment panel size
		$menu[] = '<a href="#" onclick="Comments.showMore(); return false;"><span>'.i18n::s('Show more lines').'</span></a>';

		// display all commands
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

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
// extra panel -- most content is cached, except commands specific to current surfer
//

// page tools
//

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

// review command provided to container owners
if(Articles::is_owned($anchor, NULL)) {
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

// assign command provided to owners
if(Articles::is_owned($anchor, $item)) {
	Skin::define_img('ARTICLES_ASSIGN_IMG', 'articles/assign.gif');
	$context['page_tools'][] = Skin::build_link(Users::get_url('article:'.$item['id'], 'select'), ARTICLES_ASSIGN_IMG.i18n::s('Manage editors'));
}

// several extra boxes in a row
//
$text = '';

// participants
//

// advertise this thread
$invite = '';
if(isset($context['with_email']) && ($context['with_email'] == 'Y')) {
	Skin::define_img('ARTICLES_INVITE_IMG', 'articles/invite.gif');
	$invite = Skin::build_link(Articles::get_url($item['id'], 'invite'), ARTICLES_INVITE_IMG.i18n::s('Invite participants'), 'basic', i18n::s('Spread the word'), TRUE);
}

// thread participants
if(!isset($item['locked']) || ($item['locked'] != 'Y'))
	$text .= Skin::build_box(i18n::s('Participants'), '<div id="thread_roster_panel"></div>'.$invite, 'boxes', 'roster');

// files
//

// the command to post a new file -- do that in this window, since the surfer will be driven back here
$invite = '';
if(Files::are_allowed($anchor, $item)) {
	Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
	$link = 'files/edit.php?anchor='.urlencode('article:'.$item['id']);
	$invite = Skin::build_link($link, FILES_UPLOAD_IMG.i18n::s('Upload a file'), 'basic').BR;
}

// list files by date (default) or by title (option files_by_title)
if(Articles::has_option('files_by_title', $anchor, $item))
	$items = Files::list_by_title_for_anchor('article:'.$item['id'], 0, 20, 'compact');
else
	$items = Files::list_by_date_for_anchor('article:'.$item['id'], 0, 20, 'compact');

// actually render the html
if(is_array($items)) {

	// the command to list all files
	if(count($items))
		$items = array_merge($items, array(Files::get_url('article:'.$item['id'], 'list') => i18n::s('All files')));

	$items = Skin::build_list($items, 'compact');
}

// display this aside the thread
if($items.$invite)
	$text .= Skin::build_box(i18n::s('Files'), '<div id="thread_files_panel">'.$items.'</div>'.$invite, 'boxes', 'files');

// links
//

// new links are allowed
$invite = '';
if(Links::are_allowed($anchor, $item)) {
	Skin::define_img('LINKS_ADD_IMG', 'links/add.gif');
	$link = 'links/edit.php?anchor='.urlencode('article:'.$item['id']);
	$invite = Skin::build_link($link, LINKS_ADD_IMG.i18n::s('Add a link'));
}

// list links by date (default) or by title (option links_by_title)
if(Articles::has_option('links_by_title', $anchor, $item))
	$items = Links::list_by_title_for_anchor('article:'.$item['id'], 0, 20, 'compact');
else
	$items = Links::list_by_date_for_anchor('article:'.$item['id'], 0, 20, 'compact');

// actually render the html
if(is_array($items))
	$items = Skin::build_list($items, 'compact');

// display this aside the thread
if($items.$invite)
	$text .= Skin::build_box(i18n::s('Links'), '<div>'.$items.'</div>'.$invite, 'boxes', 'links');

// add extra information from this item, if any
if(isset($item['extra']) && $item['extra'])
	$text .= Codes::beautify_extra($item['extra']);

// add extra information from the overlay, if any
if(is_object($overlay))
	$text .= $overlay->get_text('extra', $item);

// update the extra panel
$context['components']['boxes'] = $text;

//
// the AJAX part
//

if(!isset($item['locked']) || ($item['locked'] != 'Y')) {

	$context['page_footer'] .= JS_PREFIX
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
		.'		$("thread_text_panel").update("<div>" + response["items"] + "</div>");'."\n"
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
	$context['page_footer'] .= JS_SUFFIX;
}


// render the skin
render_skin();

?>