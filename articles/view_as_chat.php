<?php
/**
 * real-time chat facility
 *
 * This script is included into [script]articles/view.php[/script], when the
 * option is set to 'view_as_chat'.
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
		$digg = '<div class="rate">'.Skin::build_link(Articles::get_url($item['id'], 'like'), i18n::s('Rate it'), 'basic').'</div>';

	// rendering
	$context['text'] .= '<div class="digg"><div class="votes">'.$rating_label.'</div>'
		.$digg
		.'</div>';

	// signal DIGG
	define('DIGG', TRUE);
}

// the owner profile, if any, at the beginning of the first page
if(isset($owner['id']) && is_object($anchor))
	$context['text'] .= $anchor->get_user_profile($owner, 'prefix', Skin::build_date($item['create_date']));

// the introduction text, if any
if(is_object($overlay))
	$context['text'] .= Skin::build_block($overlay->get_text('introduction', $item), 'introduction');
else
	$context['text'] .= Skin::build_block($item['introduction'], 'introduction');

// get text related to the overlay, if any
if(is_object($overlay))
	$context['text'] .= $overlay->get_text('view', $item);

// description has been formatted in articles/view.php
if(isset($context['page_description']))
	$context['text'] .= $context['page_description'];

// the owner profile, if any, at the bottom of the page
if(isset($owner['id']) && is_object($anchor))
	$context['text'] .= $anchor->get_user_profile($owner, 'suffix', Skin::build_date($item['create_date']));

// special layout for digg
if(defined('DIGG'))
	$context['text'] = '<div class="digg_content">'.$context['text'].'</div>';

//
// comments - the real-time chatting space
//

// default status is to allow for interactive chat
$status = 'chat';

// this can be changed by the overlay
if(is_object($overlay))
	$status = $overlay->get_value('comments_layout', 'chat');

// always stop contributions when the page has been locked
if(isset($item['locked']) && ($item['locked'] == 'Y'))
	$status = 'excerpt';

// adapt the layout to the current situation
include_once $context['path_to_root'].'comments/comments.php';
switch($status) {

// an asynchronous wall
case 'yabb':

	// some space with previous content
	$context['text'] .= '<div style="margin-top: 2em;">';

	// list comments by date
	include_once '../comments/layout_comments_as_yabb.php';
	$layout = new Layout_comments_as_yabb();
	$items = Comments::list_by_date_for_anchor('article:'.$item['id'], 0, 100, $layout, isset($comments_prefix));
	if(is_array($items))
		$context['text'] .= Skin::build_list($items, 'rows');
	elseif(is_string($items))
		$context['text'] .= $items;

	// allow for contribution
	if(Comments::allow_creation($anchor, $item))
		$context['text'] .= Comments::get_form('article:'.$item['id']);

	$context ['text'] .= '</div>';

	break;

// conversation is over
case 'excerpt':

	// display a transcript of past comments
	$items = Comments::list_by_date_for_anchor('article:'.$item['id'], 0, 500, 'excerpt');
	if(is_array($items))
		$context['text'] .= Skin::build_list($items, 'rows');
	elseif(is_string($items))
		$context['text'] .= $items;

	break;

// on-going conversation
case 'chat':
default:

	// start of thread wrapper
	$context['text'] .= '<div id="thread_wrapper">'."\n";

	// text panel
	$context['text'] .= '<div id="thread_text_panel"><img style="padding: 3px;" src="'.$context['url_to_root'].'skins/_reference/ajax/ajax_spinner.gif" alt="loading..." /> &nbsp; </div>'."\n";

	// other surfers are invited to authenticate
	if(!Surfer::get_id()) {

		$context['text'] .= '<div id="thread_input_panel">';

		// commands
		$menu = array();

		// url of this page
		if(isset($item['id']))
			$menu[] = Skin::build_link('users/login.php?url='.urlencode(Articles::get_permalink($item)), i18n::s('Authenticate or register to contribute to this thread'), 'button');

		// display all commands
		$context['text'] .= Skin::finalize_list($menu, 'menu_bar');

		$context['text'] .= '</div>'."\n";

	// surfer cannot contribute
	} elseif(!Comments::allow_creation($anchor, $item))
		;

	// the input panel is where logged surfers can post data
	elseif(Surfer::is_logged()) {
		$context['text'] .= '<form method="post" action="'.$context['url_to_home'].$context['url_to_root'].'comments/edit.php"'
				.' onsubmit="Comments.contribute(); return true;"'
				.' id="thread_input_panel"'
				.' target="upload_frame"'
				.' enctype="multipart/form-data">'."\n"
			.'<textarea rows="1" name="description" id="description" ></textarea>'
			.'<input type="hidden" name="anchor" value="article:'.$item['id'].'" />'
			.'<iframe src="#" width="0" height="0" style="display: none;" id="upload_frame" name="upload_frame"></iframe>';

		// user commands
		$menu = array( '<a id="commands" href="#" ></a>'.i18n::s('Type some text and hit Enter') );

		// option to add a file
		if(Files::allow_creation($anchor, $item, 'article')) {

			// intput field to appear on demand
			$context['text'] .= '<p id="comment_upload" class="details" style="display: none;">'
				.'<input type="file" id="upload" name="upload" size="30" />'
				.' (&lt;&nbsp;'.$context['file_maximum_size'].i18n::s('bytes').')'
				.' <button type="submit">'.i18n::s('Submit').'</button>'
				.'<input type="hidden" name="file_type" value="upload" /></p>';

			// the command to add a file
			Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
			$menu[] = '<a href="#" onclick="$(\'#comment_upload\').slideDown(600); return false;"><span>'.FILES_UPLOAD_IMG.i18n::s('Add a file').'</span></a>';
		}

		// the submit button
//		$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's', 'submit');

		// go to smileys
//		$menu[] = Skin::build_link('smileys/', i18n::s('Smileys'), 'open');

		// view thread history
//		$menu[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'list'), i18n::s('View history'), 'open');

		// display all commands
		$context['text'] .= '<p style="margin: 0.3em 0 0.5em 0">'.join(' &middot; ', $menu).'</p>';

		// end the form
		$context['text'] .= '</form>'."\n";

	}

	// end of the wrapper
	$context['text'] .= '</div>'."\n";

	// the AJAX part
	$context['page_footer'] .= JS_PREFIX
		."\n"
		.'var Comments = {'."\n"
		."\n"
		.'	url: "'.$context['url_to_home'].$context['url_to_root'].Comments::get_url($item['id'], 'thread').'",'."\n"
		.'	timestamp: 0,'."\n"
		."\n"
		.'	initialize: function() { },'."\n"
		."\n"
		.'	contribute: function() {'."\n"
		.'		Yacs.startWorking();'."\n"
		.'		$("#upload_frame").load(Comments.contributed);'."\n"
		.'		return true;'."\n"
		.'	},'."\n"
		."\n"
		.'	contributed: function() {'."\n"
		.'		$("#upload_frame").unbind("load");'."\n"
		.'		$("#comment_upload").slideUp(600);'."\n"
		.'		$("#upload").replaceWith(\'<input type="file" id="upload" name="upload" size="30" />\');'."\n"
		.'		$("#description").val("").focus();'."\n"
		.'		setTimeout(function() {Comments.subscribe(); Yacs.stopWorking();}, 500);'."\n"
		.'		if((typeof OpenTok == "object") && OpenTok.session)'."\n"
		.'			OpenTok.signal();'."\n"
		.'	},'."\n"
		."\n"
		.'	keypress: function(event) {'."\n"
		.'		if(event.which == 13) {'."\n"
		.'			$("#thread_input_panel").submit();'."\n"
		.'			return false;'."\n"
		.'		}'."\n"
		.'	},'."\n"
		."\n"
		.'	showMore: function() {'."\n"
		.'		var options = {};'."\n"
		.'		var newHeight = $("#thread_text_panel").clientHeight + 200;'."\n"
		.'		options.height =  newHeight + "px";'."\n"
		.'		options.maxHeight =  newHeight + "px";'."\n"
		.'		$("#thread_text_panel").css(options);'."\n"
		.'	},'."\n"
		."\n"
		.'	subscribe: function() {'."\n"
		.'		$.ajax(Comments.url, {'."\n"
		.'			type: "get",'."\n"
		.'			dataType: "json",'."\n"
		.'			data: { "timestamp" : Comments.timestamp },'."\n"
		.'			success: Comments.updateOnSuccess'."\n"
		.'		});'."\n"
		."\n"
		.'	},'."\n"
		."\n"
		.'	subscribeToExtraUpdates: function() {'."\n"
		.'		$.ajax("'.$context['url_to_home'].$context['url_to_root'].Users::get_url($item['id'], 'visit').'", {'."\n"
		.'			type: "get",'."\n"
		.'			dataType: "html",'."\n"
		.'			success: function(data) { $("#thread_roster_panel").html(data); }'."\n"
		.'		});'."\n"
		."\n"
		.'		$.ajax("'.$context['url_to_home'].$context['url_to_root'].Files::get_url($item['id'], 'thread').'", {'."\n"
		.'			type: "get",'."\n"
		.'			dataType: "html",'."\n"
		.'			success: function(data) { $("#thread_files_panel").html(data); }'."\n"
		.'		});'."\n"
		."\n"
		.'	},'."\n"
		."\n"
		.'	updateOnSuccess: function(response) {'."\n"
		.'		if(!response) return;'."\n"
		.'		if(response["status"] != "started")'."\n"
		.'			window.location.reload(true);'."\n"
		.'		$("#thread_text_panel").html("<div>" + response["items"] + "</div>");'."\n"
		.'		var div = $("#thread_text_panel")[0];'."\n"
		.'		var scrollHeight = Math.max(div.scrollHeight, div.clientHeight);'."\n"
		.'		div.scrollTop = scrollHeight - div.clientHeight;'."\n"
		.'		if(typeof Comments.windowOriginalTitle != "string")'."\n"
		.'			Comments.windowOriginalTitle = document.title;'."\n"
		.'		document.title = "[" + response["name"] + "] " + Comments.windowOriginalTitle;'."\n"
		.'		Comments.timestamp = response["timestamp"];'."\n"
		.'	}'."\n"
		."\n"
		.'}'."\n"
		."\n"
		.'// wait for new comments and for other updates'."\n"
		.'Comments.subscribeTimer = setInterval("Comments.subscribe()", 5000);'."\n"
		.'Comments.subscribeTimer = setInterval("Comments.subscribeToExtraUpdates()", 59999);'."\n"
		."\n"
		.'// load past contributions asynchronously'."\n"
		.'$(document).ready(function() {'
		.	'Comments.subscribe();'
		.	'location.hash="#thread_text_panel";'
		.	'$("#description").tipsy({gravity: "s", fade: true, title: function () {return "'.i18n::s('Contribute here').'";}, trigger: "manual"});'
		.	'$("#description").tipsy("show");'
		.	'setTimeout("$(\'#description\').tipsy(\'hide\');", 10000);'
		.'});'."\n"
		."\n";

	// only authenticated surfers can contribute
	if(Surfer::is_logged() && Comments::allow_creation($anchor, $item))
		$context['page_footer'] .= "\n"
			.'// load past contributions asynchronously'."\n"
			.'$(document).ready(function() {'
			.	'$("#description").focus();'
			.'});'."\n"
			."\n"
			.'// send contribution on Enter'."\n"
			.'$(\'#description\').keypress( Comments.keypress );'."\n";

	// end of the AJAX part
	$context['page_footer'] .= JS_SUFFIX;

	break;

case 'excluded': // surfer is not

	$context['text'] .= Skin::build_block(i18n::s('You have not been enrolled into this interactive chat.'), 'caution');
	break;

}

//
// trailer information
//

// add trailer information from the overlay, if any --opentok videos come from here
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

// page tools
//

// post an image, if upload is allowed
if(Images::allow_creation($anchor, $item)) {
	Skin::define_img('IMAGES_ADD_IMG', 'images/add.gif');
	$context['page_tools'][] = Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']), IMAGES_ADD_IMG.i18n::s('Add an image'), 'basic', i18n::s('You can upload a camera shot, a drawing, or another image file.'));
}

// modify this page
if(Articles::allow_modification($item, $anchor)) {
	Skin::define_img('ARTICLES_EDIT_IMG', 'articles/edit.gif');
	if(!is_object($overlay) || (!$label = $overlay->get_label('edit_command', 'articles')))
		$label = i18n::s('Edit this page');
	$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'edit'), ARTICLES_EDIT_IMG.$label, 'basic', i18n::s('Press [e] to edit'), FALSE, 'e');
}

// access previous versions, if any
if($has_versions && Articles::is_owned($item, $anchor)) {
	Skin::define_img('ARTICLES_VERSIONS_IMG', 'articles/versions.gif');
	$context['page_tools'][] = Skin::build_link(Versions::get_url('article:'.$item['id'], 'list'), ARTICLES_VERSIONS_IMG.i18n::s('Versions'), 'basic', i18n::s('Restore a previous version if necessary'));
}

// publish this page
if(Articles::allow_publication($anchor, $item)) {

	if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE)) {
		Skin::define_img('ARTICLES_PUBLISH_IMG', 'articles/publish.gif');
		$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'publish'), ARTICLES_PUBLISH_IMG.i18n::s('Publish'));
	}

}

// review command provided to container owners
if(is_object($anchor) && $anchor->is_owned()) {
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

// delete command
if(Articles::allow_deletion($item, $anchor)) {
	Skin::define_img('ARTICLES_DELETE_IMG', 'articles/delete.gif');
	$context['page_tools'][] = Skin::build_link(Articles::get_url($item['id'], 'delete'), ARTICLES_DELETE_IMG.i18n::s('Delete this page'));
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
	$invite = Skin::build_link(Articles::get_url($item['id'], 'invite'), ARTICLES_INVITE_IMG.i18n::s('Invite participants'), 'basic', i18n::s('Spread the word'));
}

// thread participants
if(!isset($item['locked']) || ($item['locked'] != 'Y'))
	$text .= Skin::build_box(i18n::s('Participants'), '<div id="thread_roster_panel"></div>'.$invite, 'boxes', 'roster');

// files
//

// the command to post a new file -- do that in this window, since the surfer will be driven back here
$invite = '';
if(Files::allow_creation($anchor, $item, 'article')) {
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
if(Links::allow_creation($anchor, $item)) {
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

// render the skin
render_skin();

?>
