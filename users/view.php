<?php
/**
 * view one user profile
 *
 * This script displays one user profile. Depending on who the surfer is, more or less information is provided.
 * - surfer is the user: view any detail, may edit the page
 * - surfer is associate: view any detail, may edit and delete the page
 * - else: view the user profile
 *
 * The script also lists sections where this user may act as a managing editor, if any.
 *
 * [deleted]If the user has a Yahoo! address or ICQ number, this script generates HTML codes required to display messenger status.[/deleted]
 *
 * Addresses are showed to the surfer only if the surfer has been duly authenticated as a member, or if
 * this has been explicitly required (parameter [code]users_with_email_display[/code] set in [script]control/configure.php[/script]).
 *
 * @link http://www.hypothetic.org/docs/msn/index.php MSN Messenger Protocol
 *
 * The list of most recent pages from this user is displayed in the main panel.
 * Only the author and associates can see articles that have not been published yet.
 *
 * Articles appear in the Watch list in following cases:
 * - the user is the initial poster of the page
 * - the user has edited the page
 * - the user has added a comment, or an image, or a file
 * - the surfer has explicitly asked for it
 *
 * The extra panel has following components:
 * - An extra box with shortcuts to contribute to the server, including bookmarklets, if this is the surfer profile
 * - A link to the related rss feed, as an extra box
 * - The nearest locations, if any, into an extra box
 * - Means to reference this page, into a sidebar box
 *
 * Several HTTP headers, or &lt;meta&gt; attributes of the displayed page, are set dynamically here
 * to help advanced web usage. This includes:
 * - a link to a RSS feed for this user profile (e.g., '&lt;link rel="alternate" href="http://127.0.0.1/yacs/users/feed.php/4038" title="RSS" type="application/rss+xml" /&gt;')
 * - a link to a RDF/FOAF description of this page (e.g., '&lt;link rel="meta" href="http://127.0.0.1/yacs/users/describe.php/4310" title="FOAF" type="application/rdf+xml" /&gt;')
 *
 * @link http://wiki.foaf-project.org/Autodiscovery FOAF Autodiscovery
 *
 * Restrictions apply on this page:
 * - associates are allowed to move forward
 * - this is the personal record of the authenticated surfer
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - view.php (show my profile if I am logged)
 * - view.php/12 (view the first page of the user profile)
 * - view.php?id=12 (view the first page of the user profile)
 * - view.php/12/actions/2 (view the page 2 of the list of actions given to this user)
 * - view.php?id=12&actions=2 (view the page 2 of the list of actions given to this user)
 * - view.php/12/articles/2 (view the page 2 of the list of articles contributed by this user)
 * - view.php?id=12&articles=2 (view the page 2 of the list of articles contributed by this user)
 * - view.php/12/files/2 (view the page 2 of the list of files sent by this user)
 * - view.php?id=12&files=2 (view the page 2 of the list of files sent by this user)
 * - view.php/12/links/1 (view the page 1 of the list of links sent by this user)
 * - view.php?id=12&links=1 (view the page 1 of the list of links sent by this user)
 * - view.php/12/bookmarks/2 (view the page 2 of the list of pages bookmarked by this user)
 * - view.php?id=12&bookmarks=2 (view the page 2 of the list of pages bookmarked by this user)
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Moi-meme
 * @tester Guillaume Perez
 * @tester AnsteyER
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../actions/actions.php';
include_once '../links/links.php';
include_once '../locations/locations.php';
include_once 'visits.php';

Safe::define('SECTIONS_PER_PAGE', 10);
Safe::define('ARTICLES_PER_PAGE', 30);

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
elseif(Surfer::is_logged())
	$id = Surfer::get_id();
$id = strip_tags($id);

// no follow-up page yet
$zoom_type = '';
$zoom_index = 1;

// view.php?id=12&actions=2
if(isset($_REQUEST['actions']) && ($zoom_index = $_REQUEST['actions']))
	$zoom_type = 'actions';

// view.php?id=12&articles=2
elseif(isset($_REQUEST['articles']) && ($zoom_index = $_REQUEST['articles']))
	$zoom_type = 'articles';

// view.php?id=12&files=2
elseif(isset($_REQUEST['files']) && ($zoom_index = $_REQUEST['files']))
	$zoom_type = 'files';

// view.php?id=12&links=2
elseif(isset($_REQUEST['links']) && ($zoom_index = $_REQUEST['links']))
	$zoom_type = 'links';

// view.php?id=12&sections=2
elseif(isset($_REQUEST['sections']) && ($zoom_index = $_REQUEST['sections']))
	$zoom_type = 'sections';

// view.php?id=12&users=2
elseif(isset($_REQUEST['users']) && ($zoom_index = $_REQUEST['users']))
	$zoom_type = 'users';

// view.php?id=12&bookmarks=2
elseif(isset($_REQUEST['bookmarks']) && ($zoom_index = $_REQUEST['bookmarks']))
	$zoom_type = 'bookmarks';

// view.php/12/files/2
elseif(isset($context['arguments'][1]) && isset($context['arguments'][2])) {
	$zoom_type = $context['arguments'][1];
	$zoom_index = $context['arguments'][2];
}

// sanity check
if($zoom_index < 1)
	$zoom_index = 1;

// get the item from the database
$item =& Users::get($id);

// get the related overlay, if any
$overlay = NULL;
include_once '../overlays/overlay.php';
if(isset($item['overlay']))
	$overlay = Overlay::load($item);

// actual capability of current surfer
if(isset($item['id']) && Surfer::get_id() && ($item['id'] == Surfer::get_id()) && ($item['capability'] != '?'))
	Surfer::empower();

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// the record of the authenticated surfer
elseif(isset($item['id']) && Surfer::is($item['id']))
	$permitted = TRUE;

// access is restricted to authenticated member
elseif(isset($item['active']) && ($item['active'] == 'R') && Surfer::is_member())
	$permitted = TRUE;

// public access is allowed
elseif(isset($item['active']) && ($item['active'] == 'Y'))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin
load_skin('users');

// current item
if(isset($item['id']))
	$context['current_item'] = 'user:'.$item['id'];

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );

// the title of the page
if(isset($item['active']) && ($item['active'] == 'R'))
	$context['page_title'] .= RESTRICTED_FLAG.' ';
elseif(isset($item['active']) && ($item['active'] == 'N'))
	$context['page_title'] .= PRIVATE_FLAG.' ';
if(isset($item['full_name']) && $item['full_name']) {
	if($item['full_name'] != $item['nick_name'])
		$context['page_title'] .= $item['full_name'].' <span style="font-size: smaller;">- '.$item['nick_name'].'</span>';
	else
		$context['page_title'] .= $item['full_name'];
} elseif(isset($item['nick_name']))
	$context['page_title'] .= $item['nick_name'];

// not found -- help web crawlers
if(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Users::get_permalink($item)));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// re-enforce the canonical link
} elseif(!$zoom_type && $context['self_url'] && ($canonical = $context['url_to_home'].$context['url_to_root'].Users::get_permalink($item)) && strncmp($context['self_url'], $canonical, strlen($canonical))) {
	Safe::header('Status: 301 Moved Permanently', TRUE, 301);
	Safe::header('Location: '.$canonical);
	Logger::error(Skin::build_link($canonical));

// display the user profile
} else {

	// allow back-referencing from overlay
	$item['self_reference'] = 'user:'.$item['id'];
	$item['self_url'] = $context['url_to_root'].Users::get_permalink($item);

	// remember surfer visit
	Surfer::is_visiting(Users::get_permalink($item), $item['full_name']?$item['full_name']:$item['nick_name'], 'user:'.$item['id'], $item['active']);

	// initialize the rendering engine
	Codes::initialize(Users::get_permalink($item));

	//
	// meta-information -- $context['page_header'], etc.
	//

	// add canonical link
	if(!$zoom_type)
		$context['page_header'] .= "\n".'<link rel="canonical" href="'.$context['url_to_home'].$context['url_to_root'].Users::get_permalink($item).'" />';

	// a meta link to a feeding page
	$context['page_header'] .= "\n".'<link rel="alternate" href="'.$context['url_to_root'].Users::get_url($item['id'], 'feed').'" title="RSS" type="application/rss+xml" />';

	// a meta link to a description page (actually, rdf)
	$context['page_header'] .= "\n".'<link rel="meta" href="'.$context['url_to_root'].Users::get_url($item['id'], 'describe').'" title="FOAF" type="application/rdf+xml" />';

	// set specific headers
	if(isset($item['introduction']) && $item['introduction'])
		$context['page_description'] = strip_tags(Codes::beautify_introduction($item['introduction']));
	if(isset($item['create_name']) && $item['create_name'])
		$context['page_author'] = $item['create_name'];
	if(isset($item['edit_date']) && $item['edit_date'])
		$context['page_date'] = $item['edit_date'];

	//
	// page details -- $context['page_details']
	//

	// do not mention details to crawlers
	if(!Surfer::is_crawler()) {

		// tags, if any
		if(isset($item['tags']))
			$context['page_tags'] =& Skin::build_tags($item['tags'], 'user:'.$item['id']);

		// one detail per line
		$context['page_details'] .= '<p class="details">';
		$details = array();

		// add details from the overlay, if any
		if(is_object($overlay) && ($more = $overlay->get_text('details', $item)))
			$details[] = $more;

		// the capability field is displayed only to logged users
		if(!Surfer::is_logged())
			;
		elseif($item['capability'] == 'A')
			$details[] = i18n::s('Associate');

		elseif($item['capability'] == 'M')
			$details[] = i18n::s('Member');

		elseif($item['capability'] == 'S')
			$details[] = i18n::s('Subscriber');

		elseif($item['capability'] == '?') {
			$details[] = EXPIRED_FLAG.i18n::s('Locked');

			// also make it clear to community member
			Skin::error('This profile has been locked and does not allow authentication.');
		}

		// the number of posts
		if(isset($item['posts']) && ($item['posts'] > 1))
			$details[] = sprintf(i18n::s('%d posts'), $item['posts']);

		// the date of last login
		if(Surfer::is_associate() && isset($item['login_date']) && $item['login_date'])
			$details[] = sprintf(i18n::s('last login %s'), Skin::build_date($item['login_date']));

		// the date of registration
		if(isset($item['create_date']) && $item['create_date'])
			$details[] = sprintf(i18n::s('registered %s'), Skin::build_date($item['create_date']));

		// combine these three items into one
		if(count($details))
			$context['page_details'] .= ucfirst(implode(', ', $details));

		// reference this item
		if(Surfer::is_member())
			$context['page_details'] .= BR.sprintf(i18n::s('Code to reference this user: %s'), '[user='.$item['nick_name'].']');

		$context['page_details'] .= '</p>';

	}

	//
	// tabbed panels
	//
	$panels = array();

	//
	// the tab to contributions
	//
	$contributions = '';

	// managed sections
	//

	// the list of assigned sections
	if(!$zoom_type || ($zoom_type == 'sections')) {

		// build a complete box
		$box = array('top' => array(), 'bottom' => array(), 'text' => '');

		// the maximum number of personal sections per user
		if(!isset($context['users_maximum_managed_sections']))
			$context['users_maximum_managed_sections'] = 0;

		// offer to extend personal spaces
		if(Surfer::is($item['id']) && Surfer::is_member() &&
			(Surfer::is_associate() || ($context['users_maximum_managed_sections'] > Sections::count_for_owner())) ) {
			Skin::define_img('SECTIONS_ADD_IMG', 'sections/add.gif');
			$box['top'] += array('sections/new.php' => SECTIONS_ADD_IMG.i18n::s('Add a group or a blog'));
		}

		// associates can assign editors and readers
		if(Surfer::is_associate()) {
			Skin::define_img('SECTIONS_SELECT_IMG', 'sections/select.gif');
			$box['top'] += array('sections/select.php?anchor=user:'.$item['id'] => SECTIONS_SELECT_IMG.i18n::s('Assign sections'));
		}

		// count the number of articles for this user
		$count = Sections::count_for_user($item['id']);
		if($count)
			$box['bottom'] += array('_count' => sprintf(i18n::ns('%d section', '%d sections', $count), $count));

		// navigation commands for articles
		$home = Users::get_permalink($item);
		$prefix = Users::get_url($item['id'], 'navigate', 'sections');
		$box['bottom'] = array_merge($box['bottom'],
			Skin::navigate($home, $prefix, $count, SECTIONS_PER_PAGE, $zoom_index));

		// append a menu bar before the list
		$box['top'] = array_merge($box['top'], $box['bottom']);

		if(count($box['top']))
			$box['text'] .= Skin::build_list($box['top'], 'menu_bar');

		// compute offset from list beginning
		$offset = ($zoom_index - 1) * SECTIONS_PER_PAGE;

		// list assigned by title
		include_once $context['path_to_root'].'sections/layout_sections_as_rights.php';
		$layout = new Layout_sections_as_rights();
		$layout->set_variant($item['id']);
		$items =& Sections::list_by_date_for_user($item['id'], $offset, SECTIONS_PER_PAGE, $layout);
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'compact');
		elseif($items)
			$box['text'] .= $items;

		// append a menu bar below the list
		if(count($box['bottom']) > 1)
			$box['text'] .= Skin::build_list($box['bottom'], 'menu_bar');

		// one box
		if($box['text'])
			$contributions .= Skin::build_box(i18n::s('Sections'), $box['text']);


	}

	// contributed articles
	//

	// the list of contributed articles if not at another follow-up page
	if(!$zoom_type || ($zoom_type == 'articles')) {

		// build a complete box
		$box = array('top' => array(), 'bottom' => array(), 'text' => '');

		// only members can create private pages, and only if private pages are allowed
		if(!$zoom_type && Surfer::is_member() && (!isset($context['users_without_private_pages']) || ($context['users_without_private_pages'] != 'Y'))) {

			// start a new private page
			//
			$text = '<form method="post" enctype="multipart/form-data" action="'.$context['url_to_root'].'users/contact.php" onsubmit="return validateDocumentPost(this)" ><div>';

			// thread title
			$label = i18n::s('What do you want to talk about?');
			$input = '<input type="text" name="title" style="width: 70%" maxlength="255" />';
			$text .= '<p>'.$label.BR.$input.'</p>';

			// on my page, engage with anybody
			if(Surfer::get_id() == $item['id']) {

				// recipients
				$label = i18n::s('Who do you want to involve?');
				$input = '<textarea name="id" id="id" rows="3" cols="50"></textarea><div id="id_choice" class="autocomplete"></div>';
				$text .= '<div>'.$label.BR.$input.'</div>';

			// engage the browsed surfer
			} else
				$text .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

			// thread first contribution
			$label = i18n::s('Provide context, and start the conversation');
			$input = '<textarea name="message" rows="2" cols="50" onfocus="Yacs.growPanel(this);"></textarea>';
			$text .= '<p>'.$label.BR.$input.'</p>';

			// uploads are allowed
			if(Surfer::may_upload()) {
				$label = sprintf(i18n::s('You may attach a file of up to %sbytes'), $context['file_maximum_size']);
				$input = '<input type="file" name="upload" style="width: 30em" />';
				$text .= '<p class="details">'.$label.BR.$input.'</p>';
			}

			// bottom commands
			$menu = array();
			$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');
			$text .= Skin::finalize_list($menu, 'menu_bar');

			// end of the form
			$text .= '</div></form>';

			// in a folded box
			Skin::define_img('ARTICLES_ADD_IMG', 'articles/add.gif');
			if(Surfer::get_id() == $item['id'])
				$box['top'] += array('_new_thread' => Skin::build_sliding_box(ARTICLES_ADD_IMG.i18n::s('Start a thread'), $text, 'new_thread', TRUE));
			else
				$box['top'] += array('_new_thread' => Skin::build_sliding_box(ARTICLES_ADD_IMG.sprintf(i18n::s('Start a thread with %s'), $item['full_name']?$item['full_name']:$item['nick_name']), $text, 'new_thread', TRUE));

			// append the script used for data checking on the browser
			$box['text'] .= JS_PREFIX
				.'// check that main fields are not empty'."\n"
				.'func'.'tion validateDocumentPost(container) {'."\n"
				."\n"
				.'	// title is mandatory'."\n"
				.'	if(!container.title.value) {'."\n"
				.'		alert("'.i18n::s('Please provide a meaningful title.').'");'."\n"
				.'		Yacs.stopWorking();'."\n"
				.'		return false;'."\n"
				.'	}'."\n"
				."\n"
				.'	// successful check'."\n"
				.'	return true;'."\n"
				.'}'."\n"
				."\n"
				.'// enable autocompletion'."\n"
				.'Event.observe(window, "load", function() { new Ajax.Autocompleter("id", "id_choice", "'.$context['url_to_root'].'users/complete.php", { paramName: "q", minChars: 1, frequency: 0.4, tokens: "," }); });'."\n"
				.JS_SUFFIX;
		}

		// count the number of articles for this user
		$count = Articles::count_for_user($item['id']);
		if($count)
			$box['bottom'] += array('_count' => sprintf(i18n::ns('%d page', '%d pages', $count), $count));

		// navigation commands for articles
		$home = Users::get_permalink($item);
		$prefix = Users::get_url($item['id'], 'navigate', 'articles');
		$box['bottom'] = array_merge($box['bottom'],
			Skin::navigate($home, $prefix, $count, ARTICLES_PER_PAGE, $zoom_index));

		// append a menu bar before the list
		$box['top'] = array_merge($box['top'], $box['bottom']);

		if(count($box['top']))
			$box['text'] .= Skin::build_list($box['top'], 'menu_bar');

		// compute offset from list beginning
		$offset = ($zoom_index - 1) * ARTICLES_PER_PAGE;

		// list watched pages by date, not only pages posted by this user
		include_once $context['path_to_root'].'articles/layout_articles_as_rights.php';
		$layout = new Layout_articles_as_rights();
		$layout->set_variant($item['id']);
		$items =& Articles::list_for_user_by('edition', $item['id'], $offset, ARTICLES_PER_PAGE, $layout);
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'compact');
		elseif($items)
			$box['text'] .= $items;

		// append a menu bar below the list
		if(count($box['bottom']) > 1)
			$box['text'] .= Skin::build_list($box['bottom'], 'menu_bar');

		// a complete box
		if($box['text'])
			$contributions .= Skin::build_box(i18n::s('Pages'), $box['text'], 'header1', 'contributed_articles');

	}

	// the list of recent contributed files if not at another follow-up page
	if(!$zoom_type) {

		// build a complete box
		$box['text'] = '';

		// avoid links to this page
		include_once '../files/layout_files_as_simple.php';
		$layout = new Layout_files_as_simple();
		if(is_object($layout))
			$layout->set_variant('user:'.$item['id']);

		// list files by date
		$items = Files::list_by_date_for_author($item['id'], 0, 30, $layout);
		if(is_array($items))
			$items = Skin::build_list($items, 'compact');

		// layout the columns
		$box['text'] .= $items;

		// actually render the html for the section
		if($box['text'])
			$contributions .= Skin::build_box(i18n::s('Files'), $box['text'], 'header1', 'contributed_files');

	}

	// the list of contributed links if not at another follow-up page
// 	if(!$zoom_type) {
//
// 		// build a complete box
// 		$box['text'] = '';
//
// 		// list links by date
// 		$items = Links::list_by_date_for_author($item['id'], 0, 20, 'simple');
//
// 		// actually render the html for the section
// 		if(is_array($items))
// 			$box['text'] .= Skin::build_list($items, 'compact');
// 		if($box['text'])
// 			$contributions .= Skin::build_box(i18n::s('Links'), $box['text'], 'header1', 'contributed_links');
//
// 	}

	// in a separate panel
	if(trim($contributions))
		$panels[] = array('contributions', i18n::s('Contributions'), 'contributions_panel', $contributions);

	//
	// the information tab
	//
	$information = '';

	// if not at another follow-up page
	if(!$zoom_type) {

		// the sidebar
		$sidebar = '';

		// co-browsing
		if(Surfer::get_id() && (Surfer::get_id() != $item['id'])) {
			$visited = array();

			// some page or thread has been visited recently
			if($items = Visits::list_for_user($item['id'])) {
				foreach($items as $url => $label)
					$visited = array_merge($visited, array($url => sprintf(i18n::s('Join %s at %s'), $item['nick_name'], $label)));

			// user is present if active during last 10 minutes (10*60 = 600), but not at some thread
			} elseif(isset($item['click_date']) && ($item['click_date'] >= gmstrftime('%Y-%m-%d %H:%M:%S', time()-600))) {

				// show place of last click
				if(isset($item['click_anchor']) && ($anchor =& Anchors::get($item['click_anchor'])))
					$visited = array_merge($visited, array($anchor->get_url() => sprintf(i18n::s('Join %s at %s'), $item['nick_name'], $anchor->get_title())));

			}

			// make a box
			if(count($visited))
				$sidebar .= Skin::build_box(i18n::s('Co-browsing'), Skin::build_list($visited, 'compact'), 'folded', 'co_browsing');

		}

		// business card
		//
		$text = '';

		// full name
		$text .= '<p>'.$item['full_name'];

		// title, if any
		if(isset($item['vcard_title']) && $item['vcard_title'])
			$text .= BR.$item['vcard_title'];

		// organization, if any
		if(isset($item['vcard_organization']) && $item['vcard_organization'])
			$text .= BR.$item['vcard_organization'];

		// physical address, if any
		if(isset($item['vcard_label']) && $item['vcard_label'])
			$text .= BR.str_replace("\n", BR, $item['vcard_label']);

		$text.= '</p><p>';

		// phone number, if any
		if(isset($item['phone_number']) && $item['phone_number'])
			$text .= sprintf(i18n::s('%s: %s'), i18n::s('Phone number'), $item['phone_number']);

		// alternate number, if any
		if(isset($item['alternate_number']) && $item['alternate_number'])
			$text .= BR.sprintf(i18n::s('%s: %s'), i18n::s('Alternate number'), $item['alternate_number']);

		// email address - not showed to anonymous surfers for spam protection
		if(isset($item['email']) && $item['email'] && (Surfer::is($item['id']) || Surfer::may_contact($item['id']))) {

			if(Surfer::is($item['id']))
				$label = $item['email'];
			elseif(isset($context['with_email']) && ($context['with_email'] == 'Y'))
				$label = Skin::build_link($context['url_to_root'].Users::get_url($id, 'mail'), $item['email'], 'email');
			else
				$label = Skin::build_link('mailto:'.$item['email'], $item['email'], 'email');

			$text .= BR.$label;
		}

		// web address, if any
		if(isset($item['web_address']) && $item['web_address'])
			$text .= BR.Skin::build_link($item['web_address'], $item['web_address'], 'external');

		// agent, if any
		if(isset($item['vcard_agent']) && $item['vcard_agent']) {
			$text .= '</p><p>';
			if($agent =& Users::get($item['vcard_agent']))
				$text .= sprintf(i18n::s('%s: %s'), i18n::s('Alternate contact'), Skin::build_link(Users::get_permalink($agent), $agent['full_name']?$agent['full_name']:$agent['nick_name'], 'user'));
			else
				$text .= sprintf(i18n::s('%s: %s'), i18n::s('Alternate contact'), $item['vcard_agent']);
		}

		$text .= '</p>';

		$sidebar .= Skin::build_box(i18n::s('Business card'), $text, 'unfolded');

		// do not let robots steal addresses
		$box = array( 'bar' => array(), 'text' => '');
		if(Surfer::may_contact()) {

			// put contact addresses in a table
			$rows = array();

			// a clickable twitter address
			if(isset($item['twitter_address']) && $item['twitter_address'])
				$rows[] = array(i18n::s('Twitter'), Skin::build_presence($item['twitter_address'], 'twitter').' '.Skin::build_link('http://www.twitter.com/'.$item['twitter_address'], $item['twitter_address']) );

			// a clickable jabber address
			if(isset($item['jabber_address']) && $item['jabber_address'])
				$rows[] = array(i18n::s('Jabber'), Skin::build_presence($item['jabber_address'], 'jabber').' '.$item['jabber_address']);

			// a clickable skype address
			if(isset($item['skype_address']) && $item['skype_address'])
				$rows[] = array(i18n::s('Skype'), Skin::build_presence($item['skype_address'], 'skype').' '.$item['skype_address']);

			// a clickable yahoo address -- the on-line status indicator requires to be connected to the Internet
			if(isset($item['yahoo_address']) && $item['yahoo_address'])
				$rows[] = array(i18n::s('Yahoo! Messenger'), Skin::build_presence($item['yahoo_address'], 'yahoo').' '.$item['yahoo_address']
				.' <img src="http://opi.yahoo.com/online?u='.$item['yahoo_address'].'&amp;m=g&amp;t=1" alt="Yahoo Online Status Indicator" />');

			// a clickable msn address
			if(isset($item['msn_address']) && $item['msn_address'])
				$rows[] = array(i18n::s('Windows Live Messenger'), Skin::build_presence($item['msn_address'], 'msn').' '.$item['msn_address']);

			// a clickable aim address
			if(isset($item['aim_address']) && $item['aim_address'])
				$rows[] = array(i18n::s('AIM'), Skin::build_presence($item['aim_address'], 'aim').' '.$item['aim_address']);

			// a clickable irc address
			if(isset($item['irc_address']) && $item['irc_address'])
				$rows[] = array(i18n::s('IRC'), Skin::build_presence($item['irc_address'], 'irc').' '.$item['irc_address']);

			// a clickable icq number
			if(isset($item['icq_address']) && $item['icq_address'])
				$rows[] = array(i18n::s('ICQ'), Skin::build_presence($item['icq_address'], 'icq').' '.$item['icq_address']);

			if(count($rows))
				$box['text'] .= Skin::table(NULL, $rows, 'wide');

		}

		// a full box
		if($box['text'])
			$sidebar .= Skin::build_box(i18n::s('Instant communication'), $box['text'], 'folded');

		// pgp key
		if(isset($item['pgp_key']) && $item['pgp_key'])
			$sidebar .= Skin::build_box(i18n::s('Public key'), '<span style="font-size: 50%">'.$item['pgp_key'].'</span>', 'folded');

		// show preferences only to related surfers and to associates
		//
		if((Surfer::get_id() == $item['id']) || Surfer::is_associate()) {
			$box = '';

			// public or restricted or hidden profile
			if(isset($item['active'])) {
				if($item['active'] == 'Y')
					$box .= '<p>'.i18n::s('Anyone may read this profile.').'</p>';
				elseif($item['active'] == 'R')
					$box .= '<p>'.RESTRICTED_FLAG.i18n::s('Community - Access is granted to any identified surfer').'</p>';
				elseif($item['active'] == 'N')
					$box .= '<p>'.PRIVATE_FLAG.i18n::s('Private - Access is restricted to selected persons').'</p>';
			}

			// signature
			if(isset($item['signature']) && $item['signature'])
				$box .= '<p>'.sprintf(i18n::s('%s: %s'), i18n::s('Signature'), BR.Codes::beautify($item['signature'])).'</p>'."\n";

			// e-mail usage
			if((Surfer::get_id() == $item['id']) || Surfer::is_associate()) {

				$items = array();

				// confirm password
				if(!isset($item['without_confirmations']) || ($item['without_confirmations'] != 'Y'))
					$items[] = i18n::s('Confirm every password change.');

				// receive alerts
				if(!isset($item['without_alerts']) || ($item['without_alerts'] != 'Y'))
					$items[] = i18n::s('Alert me when my pages are commented.');

				// receive private messages
				if(!isset($item['without_messages']) || ($item['without_messages'] != 'Y'))
					$items[] = i18n::s('Allow other members to contact me.');

				// explicit newsletter subscription
				if(!isset($item['id']) || !isset($item['with_newsletters']) || ($item['with_newsletters'] == 'Y'))
					$items[] = i18n::s('Send me periodical newsletters.');

				if(count($items))
					$box .= '<dl><dt>'.i18n::s('E-mail usage').'</dt><dd>'
						.'<ul><li>'.join('</li><li>', $items).'</li></ul>'
						.'</dd></dl>';

			}

			// preferred editor
			if(isset($item['editor']) && ($item['editor'] == 'fckeditor'))
				$label = Skin::build_link('http://www.fckeditor.net/', i18n::s('FCKEditor'), 'external');
			elseif(isset($item['editor']) && ($item['editor'] == 'tinymce'))
				$label = Skin::build_link('http://tinymce.moxiecode.com/', i18n::s('TinyMCE'), 'external');
			else
				$label = i18n::s('Textarea');
			$box .= '<p>'.sprintf(i18n::s('Editor: %s'), $label).'</p>'."\n";

			// interface
			if(isset($item['interface'])) {
				if($item['interface'] == 'I')
					$box .= '<p>'.i18n::s('Improved interface').'</p>';
				elseif($item['interface'] == 'C')
					$box .= '<p>'.i18n::s('Complex interface').'</p>';
			}

			// share screen
			if((Surfer::get_id() == $item['id']) || Surfer::is_associate()) {
				if(!isset($item['with_sharing']) || ($item['with_sharing'] == 'N'))
					$box .= '<p>'.i18n::s('Screen is not shared with other people.').'</p>';
				if(isset($item['with_sharing']) && ($item['with_sharing'] == 'V'))
					$box .= '<p>'.i18n::s('Allow remote access using VNC.').'</p>';
				if(isset($item['with_sharing']) && ($item['with_sharing'] == 'M'))
					$box .= '<p>'.i18n::s('Allow remote access with NetMeeting.').'</p>';
			}

			// proxy
			if((Surfer::get_id() == $item['id']) || Surfer::is_associate()) {
				if(isset($item['proxy_address']) && $item['proxy_address'])
					$box .= '<p>'.sprintf(i18n::s('%s: %s'), i18n::s('Network address'), $item['proxy_address']).'</p>';
				elseif(isset($item['login_address']) && $item['login_address'])
					$box.= '<p>'.sprintf(i18n::s('%s: %s'), i18n::s('Network address'), $item['login_address']).'</p>';
			}

			// display workstation time offset
			if(Surfer::get_id() && (Surfer::get_id() == $item['id']) && isset($_COOKIE['TimeZone']))
				$box .= '<p>'.i18n::s('Browser GMT offset:').' UTC '.(($_COOKIE['TimeZone'] > 0) ? '+' : '-').$_COOKIE['TimeZone'].' '.i18n::s('hour(s)')."</p>\n";

			if($box)
				$sidebar .= Skin::build_box(i18n::s('Preferences'), $box, 'folded');

		}

		// finalize the sidebar
		if($sidebar)
			$sidebar = Skin::build_block($sidebar, 'sidecolumn');

		// the main bar
		$mainbar = '';

		// get text related to the overlay, if any
		if(is_object($overlay))
			$mainbar .= $overlay->get_text('view', $item);

		// the full text
		$mainbar .= Skin::build_block($item['description'], 'description');

		// birth date, if any, and only for authenticated surfers
		if(isset($item['birth_date']) && ($item['birth_date'] > NULL_DATE) && Surfer::is_logged())
			$mainbar .= '<p>'.i18n::s('Birth date').' '.substr($item['birth_date'], 0, 10).'</p>';

		// list files
		//
		$items = Files::list_by_date_for_anchor('user:'.$item['id'], 0, FILES_PER_PAGE, 'no_author');
		if(is_array($items))
			$items = Skin::build_list($items, 'decorated');

		// local menu
		$menu = array();

		// the command to post a new file
		if((Surfer::is($item['id']) || Surfer::is_associate()) && Surfer::may_upload()) {
			Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
			$menu[] = Skin::build_link('files/edit.php?anchor=user:'.$item['id'], FILES_UPLOAD_IMG.i18n::s('Upload a file'), 'span');
		}

		if(count($menu))
			$items = Skin::finalize_list($menu, 'menu_bar').$items;

		if($items)
			$mainbar .= Skin::build_box(i18n::s('Files'), $items);

		// layout columns
		if($sidebar)
			$information .= Skin::layout_horizontally($mainbar, $sidebar);
		elseif($mainbar)
			$information .= $mainbar;

	}

	// in a separate tab
	if($information)
		$panels[] = array('information', i18n::s('Information'), 'information_panel', $information);

	// append tabs from the overlay, if any
	//
	if(is_object($overlay) && ($more_tabs = $overlay->get_tabs('view', $item)))
 		$panels = array_merge($panels, $more_tabs);

	// assemble tabs
	//
	if(!$zoom_type)
		$panels[] = array('followers', i18n::s('Followers'), 'followers_panel', NULL, Users::get_url($item['id'], 'element', 'watch'));
	if(!$zoom_type && Surfer::is_member())
		$panels[] = array('actions', i18n::s('Actions'), 'actions_panel', NULL, Users::get_url($item['id'], 'element', 'actions'));

	// let YACS do the hard job
	$context['text'] .= Skin::build_tabs($panels);

	//
	// populate the extra panel
	//

	// page tools
	//

	// tools to maintain my page
	if(Surfer::is_empowered()) {

		// change avatar
		if(Surfer::is_empowered() && isset($item['avatar_url']) && $item['avatar_url']) {
			Skin::define_img('IMAGES_ADD_IMG', 'images/add.gif');
			$label = i18n::s('Change picture');
			$context['page_tools'][] = Skin::build_link(Users::get_url($item['id'], 'select_avatar'), IMAGES_ADD_IMG.$label, 'basic');
		}

		// modify this page
		Skin::define_img('USERS_EDIT_IMG', 'users/edit.gif');
		$context['page_tools'][] = Skin::build_link(Users::get_url($item['id'], 'edit'), USERS_EDIT_IMG.i18n::s('Edit this profile'), 'basic', i18n::s('Press [e] to edit'), FALSE, 'e');

		// change password
		if(!isset($context['users_authenticator']) || !$context['users_authenticator']) {
			Skin::define_img('USERS_PASSWORD_IMG', 'users/password.gif');
			$context['page_tools'][] = Skin::build_link(Users::get_url($item['id'], 'password'), USERS_PASSWORD_IMG.i18n::s('Change password'), 'basic');
		}

		// only associates can delete user profiles; self-deletion may also be allowed
		if(isset($item['id']) && !$zoom_type && $permitted
			&& (Surfer::is_associate()
				|| (Surfer::is($item['id']) && (!isset($context['users_without_self_deletion']) || ($context['users_without_self_deletion'] != 'Y'))))) {

			Skin::define_img('USERS_DELETE_IMG', 'users/delete.gif');
			$context['page_tools'][] = Skin::build_link(Users::get_url($item['id'], 'delete'), USERS_DELETE_IMG.i18n::s('Delete this profile'));
		}

	}

	// user profile aside
	$context['components']['profile'] = Skin::build_profile($item, 'extra');

	// add extra information from the overlay, if any
	if(is_object($overlay))
		$context['components']['overlay'] = $overlay->get_text('extra', $item);

	// add extra information from this item, if any
	if(isset($item['extra']) && $item['extra'])
		$context['components']['boxes'] = Codes::beautify_extra($item['extra']);

	// 'Share' box
	//
	$lines = array();

	// logged users may download the vcard
	if(Surfer::is_logged()) {
		Skin::define_img('USERS_VCARD_IMG', 'users/vcard.gif');
		$lines[] = Skin::build_link(Users::get_url($item['id'], 'fetch_vcard', $item['nick_name']), USERS_VCARD_IMG.i18n::s('Business card'), 'basic');
	}

	// print this page
	if(Surfer::is_logged()) {
		Skin::define_img('TOOLS_PRINT_IMG', 'tools/print.gif');
		$lines[] = Skin::build_link(Users::get_url($id, 'print'), TOOLS_PRINT_IMG.i18n::s('Print this page'), 'basic');
	}

	// in a side box
	if(count($lines))
		$context['components']['share'] = Skin::build_box(i18n::s('Share'), Skin::finalize_list($lines, 'tools'), 'share', 'share');

	// 'Information channels' box
	//
	$lines = array();

	// connect to people
	if(Surfer::get_id() && (Surfer::get_id() != $item['id'])) {

		// a link to toggle the connection
		$link = Users::get_url('user:'.$item['id'], 'track');

		// manage your watch list
		if(Members::check('user:'.$item['id'], 'user:'.Surfer::get_id()))
			$label = i18n::s('Stop notifications');
		else
			$label = i18n::s('Follow this person');

		Skin::define_img('USERS_WATCH_IMG', 'users/watch.gif');
		$lines[] = Skin::build_link($link, USERS_WATCH_IMG.$label, 'basic', i18n::s('Manage your watch list'));

	}

	// get news from rss
	if(isset($item['capability']) && (($item['capability'] == 'A') || ($item['capability'] == 'M')) && (!isset($context['skins_general_without_feed']) || ($context['skins_general_without_feed'] != 'Y')) ) {

		$lines[] = Skin::build_link($context['url_to_home'].$context['url_to_root'].Users::get_url($item['id'], 'feed'), i18n::s('Recent pages'), 'xml');

		// public aggregators
// 		if(!isset($context['without_internet_visibility']) || ($context['without_internet_visibility'] != 'Y'))
// 			$lines[] = join(BR, Skin::build_subscribers($context['url_to_home'].$context['url_to_root'].Users::get_url($item['id'], 'feed'), $item['nick_name']));

	}

	// in a side box
	if(count($lines))
		$context['components']['channels'] = Skin::build_box(i18n::s('Monitor'), join(BR, $lines), 'channels', 'feed');

	// categories attached to this item, if not at another follow-up page
	if(!$zoom_type || ($zoom_type == 'categories')) {

		// build a complete box
		$box = array();
		$box['bar'] = array();
		$box['text'] = '';

		// list categories by title
		$items =& Members::list_categories_by_title_for_member('user:'.$item['id'], 0, COMPACT_LIST_SIZE, 'sidebar');

		// the command to change categories assignments
		if(Categories::allow_creation(NULL, $item))
			$items = array_merge($items, array( Categories::get_url('user:'.$item['id'], 'select') => i18n::s('Assign categories') ));

		// actually render the html for the section
		if(is_array($box['bar']))
			$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'compact');
		if($box['text'])
			$context['components']['categories'] = Skin::build_box(i18n::s('See also'), $box['text'], 'categories', 'categories');

	}

	// referrals, if any
	$context['components']['referrals'] =& Skin::build_referrals(Users::get_url($item['id']));

}

// render the skin -- do not provide Last-Modified header
render_skin(FALSE);

?>
