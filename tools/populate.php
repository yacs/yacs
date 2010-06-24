<?php
/**
 * populate the database with test data
 *
 * @todo 'retrspective_template' http://www.eu.socialtext.net/open/index.cgi?retrospective_template
 * @todo 'case_study' http://www.eu.socialtext.net/cases2/index.cgi?case_template
 *
 * This script helps to create a suitable environment for demonstration and for
 * non-regression tests.
 *
 * Only associates can use this script.
 *
 * Following accounts are created:
 * - 'Alice' - an associate
 * - 'Bob' - a member
 * - 'Carol' - a member
 * - 'Sophie' - a subscriber
 *
 * Sample elements for the front page:
 * - 'extra_box' - a sample extra box at the front page
 * - 'gadget_cloud' - a sample gadget box featuring the cloud at the front page
 * - 'gadget_collections' - a sample gadget box featuring available collections at the front page
 * - 'navigation_box' - a sample navigation box
 *
 * Sample global pages:
 * - 'coffee_machine' - a permanent thread for unformal conversations
 *
 * Sample calendar:
 * - 'events' - a sample calendar of planned events
 * - 'event_template' - for pages to be added to the event calendar
 *
 * Sample forums:
 * - 'forums' - top-level section for forums
 * - 'channels' - sample interactive places
 * - 'support_chat' - a sample thread to better support your users
 * - 'yabb_board' - a sample discussion board
 * - 'yabb_thread' - a sample thread made of one article, with a number of comments
 * - 'jive_board' - a sample discussion board
 * - 'jive_thread' - a sample thread made of one article, with a number of comments
 *
 * Sample blog:
 * - 'blog' - a sample blog
 * - 'blog_page' - some blogging sample
 *
 * Sample project web space:
 * - 'project' - the containing section, assigned to Bobby and to Carol
 * - 'project_public_page' - a public page that describes the project
 * - 'project_private' - a private sub-section
 * - 'project_private_page' - a private page only for section editors
 *
 * Sample electronic publication:
 * - 'book' - a sample electronic book
 * - 'book_chapter' - chapter 1 of the sample electronic book
 * - 'book_page' - a sample page of an electronic book
 *
 * Sample items for wikis:
 * - 'wikis' - top-level section for wiki samples
 * - 'wiki_anonymous' - a section that can be modified by anonymous surfers
 * - 'wiki_anonymous_page' - an article in 'wiki_anonymous'
 * - 'wiki_members' - a section that can be modified by authenticated persons
 * - 'wiki_members_page' - an article in 'wiki_anonymous'
 * - 'wiki_template' - to create a wiki page
 *
 * Following sections are created:
 * - 'files' - a sample library of files
 * - 'links' - a sample library of files
 * - 'my_section' - a sample plain section
 *
 * Following categories are created:
 * - 'my_category' - a sample top-level category
 * - 'my_sub_category' - a sample category
 *
 * Following articles are created:
 * - 'my_article' - a sample plain article
 *
 * Comments are added to following pages : for 'my_article' page, many comments
 * to 'blog_page', 'book_page', 'jive_thread', 'wiki_anonymous_page', and 'yabb_thread' pages.
 *
 * Creates following sample tables:
 * - 'my_articles' - A simple table listing newest articles
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common libraries
include_once '../shared/global.php';
include_once '../comments/comments.php';
include_once '../tables/tables.php';

// what to do
$action = '';
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];
if(!$action && isset($context['arguments'][0]))
	$action = $context['arguments'][0];
$action = strip_tags($action);

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load localized strings
i18n::bind('tools');

// load the skin
load_skin('tools');

// the path to this page
$context['path_bar'] = array( 'tools/' => i18n::s('Tools') );

// default page title
$context['page_title'] = i18n::s('Content Assistant');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('tools/populate.php?action='.$action));

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the control panel
	$menu = array('tools/' => i18n::s('Tools'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// create test data
} elseif($action == 'confirmed') {
	$text = '';

	// users
	//
	$text .= Skin::build_block(i18n::s('Users'), 'subtitle');

	// 'Alice' is an associate
	if(Users::get(i18n::c('Alice')))
		$text .= sprintf(i18n::s('An account already exists for "%s".'), i18n::c('Alice')).BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = i18n::c('Alice');
		$fields['password'] = 'test';
		$fields['confirm'] = 'test';
		$fields['introduction'] = i18n::c('Sample associate profile');
		$fields['capability'] = 'A';
		if(Users::post($fields))
			$text .= sprintf(i18n::s('A user profile "%s" has been created, with the password "%s".'), $fields['nick_name'], $fields['confirm']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'Bob' is a member
	if(Users::get(i18n::c('Bob')))
		$text .= sprintf(i18n::s('An account already exists for "%s".'), i18n::c('Bob')).BR."\n";
	else {
		$fields = array();
		$fields['full_name'] = i18n::c('Sponge Bob');
		$fields['nick_name'] = i18n::c('Bob');
		$fields['password'] = 'test';
		$fields['confirm'] = 'test';
		$fields['introduction'] = i18n::c('Sample person profile');
		$fields['capability'] = 'M';
		if(Users::post($fields))
			$text .= sprintf(i18n::s('A user profile "%s" has been created, with the password "%s".'), $fields['nick_name'], $fields['confirm']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'Carol' is a member
	if(Users::get(i18n::c('Carol')))
		$text .= sprintf(i18n::s('An account already exists for "%s".'), i18n::c('Carol')).BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = i18n::c('Carol');
		$fields['password'] = 'test';
		$fields['confirm'] = 'test';
		$fields['introduction'] = i18n::c('Sample person profile');
		$fields['capability'] = 'M';
		if(Users::post($fields))
			$text .= sprintf(i18n::s('A user profile "%s" has been created, with the password "%s".'), $fields['nick_name'], $fields['confirm']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'Sophie' is a subscriber
	if(Users::get(i18n::c('Sophie')))
		$text .= sprintf(i18n::s('An account already exists for "%s".'), i18n::c('Sophie')).BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = i18n::c('Sophie');
		$fields['password'] = 'test';
		$fields['confirm'] = 'test';
		$fields['introduction'] = i18n::c('Sample subscriber profile');
		$fields['capability'] = 'S';
		if(Users::post($fields))
			$text .= sprintf(i18n::s('A user profile "%s" has been created, with the password "%s".'), $fields['nick_name'], $fields['confirm']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// distribute names
	$names = array( i18n::c('Alice'), i18n::c('Bob'), i18n::c('Carol') );

	// front page
	//
	$text .= Skin::build_block(i18n::s('Front page'), 'subtitle');

	// 'extra_box' article
	if(Articles::get('extra_box'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), 'extra_box').BR."\n";
	elseif($anchor = Sections::lookup('extra_boxes')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'extra_box';
		$fields['title'] = i18n::c('Hello again');
		$fields['introduction'] = '';
		$fields['description'] = i18n::c("This is a sample extra box.\nVisit the [link=extra section]sections/view.php?id=extra_boxes[/link] to review all extra boxes.");;
		$fields['locked'] = 'Y'; // only associates can change this page
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'gadget_cloud' article
	if(Articles::get('gadget_cloud'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), 'gadget_cloud').BR."\n";
	elseif($anchor = Sections::lookup('gadget_boxes')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'gadget_cloud';
		$fields['title'] = i18n::c('Tags cloud');
		$fields['introduction'] = '';
		$fields['description'] = '[cloud]';
		$fields['locked'] = 'Y'; // only associates can change this page
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'gadget_collections' article
	if(Articles::get('gadget_collections'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), 'gadget_collections').BR."\n";
	elseif($anchor = Sections::lookup('gadget_boxes')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'gadget_collections';
		$fields['title'] = i18n::c('Collections');
		$fields['introduction'] = '';
		$fields['description'] = '[collections]';
		$fields['locked'] = 'Y'; // only associates can change this page
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'navigation_box' article
	if(Articles::get('navigation_box'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), 'navigation_box').BR."\n";
	elseif($anchor = Sections::lookup('navigation_boxes')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'navigation_box';
		$fields['title'] = i18n::c('Hello world');
		$fields['introduction'] = '';
		$fields['description'] = i18n::c("This is a sample navigation box.\nVisit the [link=navigation section]sections/view.php?id=navigation_boxes[/link] to review all navigation boxes.");
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// global pages
	//
	$text .= Skin::build_block(i18n::s('Global pages'), 'subtitle');

	// 'coffee_machine' article
	if(Articles::get('coffee_machine'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), 'coffee_machine').BR."\n";
	elseif($anchor = Sections::lookup('global')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'coffee_machine';
		$fields['title'] = i18n::c('Coffee machine');
		$fields['introduction'] = i18n::c('Take a break, and discuss important things');
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		$fields['options'] = 'view_as_chat';
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// calendar
	//
	$text .= Skin::build_block(i18n::s('Calendar'), 'subtitle');

	// 'events' section
	if(Sections::get('events'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), 'events').BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = 'events';
		$fields['title'] = i18n::c('Events');
		$fields['introduction'] = i18n::c('A calendar of planned activities');
		$fields['description'] = i18n::c('Every page in this section is featured in a nice-looking calendar.');
		$fields['home_panel'] = 'none'; // special processing at the front page -- see index.php
		$fields['content_options'] = 'auto_publish'; // ease the job
		$fields['content_overlay'] = 'day'; // calendar layout
		$fields['articles_templates'] = 'event_template';
		$fields['maximum_items'] = 1000; // limit the overall number of events
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'event_template' article
	if(Articles::get('event_template'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), 'event_template').BR."\n";
	elseif($anchor = Sections::lookup('templates')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'event_template';
		$fields['title'] = i18n::c('Event');
		$fields['introduction'] = i18n::c('Use this page model to add a new event to the calendar');
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');

		include_once '../overlays/overlay.php';
		$overlay = Overlay::bind('day');
		$fields['overlay'] = $overlay->save();
		$fields['overlay_id'] = $overlay->get_id();

		if($fields['id'] = Articles::post($fields)) {
			$overlay->remember('insert', $fields);
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['nick_name']).BR."\n";
		} else
			$text .= Logger::error_pop().BR."\n";
	}

	// forums
	//
	$text .= Skin::build_block(i18n::s('Forums'), 'subtitle');

	// 'forums' top-level section
	if(Sections::get('forums'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), 'forums').BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = 'forums';
		$fields['title'] = i18n::c('Forums');
		$fields['introduction'] = i18n::c('Sample discussion places');
		$fields['sections_layout'] = 'yabb';
		$fields['articles_layout'] = 'none';
		$fields['locked'] = 'Y';
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'channels' section
	if(Sections::get('channels'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), 'channels').BR."\n";
	elseif($anchor = Sections::lookup('forums')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'channels';
		$fields['title'] = i18n::c('Channels');
		$fields['introduction'] = i18n::c('Real-time collaboration');
		$fields['description'] = i18n::c('Every page in this section supports interactive discussion and file sharing.');
		$fields['home_panel'] = 'none'; // special processing at the front page -- see index.php
		$fields['articles_layout'] = 'map'; // list threads appropriately
		$fields['content_options'] = 'view_as_chat'; // change the rendering script for articles
		$fields['maximum_items'] = 1000; // limit the overall number of threads
		if(Sections::post($fields, FALSE))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'support_chat' article
	if(Articles::get('support_chat'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), 'support_chat').BR."\n";
	elseif($anchor = Sections::lookup('channels')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'support_chat';
		$fields['title'] = i18n::c('Interactive support');
		$fields['introduction'] = i18n::c('To seek for help from other members of the community');
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'yabb_board' section
	if(Sections::get('yabb_board'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), 'yabb_board').BR."\n";
	elseif($anchor = Sections::lookup('forums')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'yabb_board';
		$fields['title'] = i18n::c('My yabb discussion board');
		$fields['introduction'] = i18n::c('Sample discussion board');
		$fields['articles_layout'] = 'yabb';
		$fields['sections_layout'] = 'yabb';
		$fields['content_options'] = 'auto_publish, with_extra_profile';
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'yabb_thread' article
	if(Articles::get('yabb_thread'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), 'yabb_thread').BR."\n";
	elseif($anchor = Sections::lookup('yabb_board')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'yabb_thread';
		$fields['title'] = i18n::c('Sample yabb thread');
		$fields['introduction'] = i18n::c('Sample article with its set of comments');
		$fields['description'] = i18n::c('This page demonstrates the rendering of the ##yabb## layout.');
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// add sample comments to 'yabb_thread'
	if($anchor = Articles::lookup('yabb_thread')) {

		// add a bunch of comments
		$stats = Comments::stat_for_anchor($anchor);
		if($stats['count'] < 50) {
			for($index = 1; $index <= 50; $index++) {
				$fields = array();
				$fields['anchor'] = $anchor;
				$fields['description'] = sprintf(i18n::c('Comment #%d'), $index);
				$fields['edit_name'] = $names[ rand(0, 2) ];
				if(!Comments::post($fields)) {
					$text .= Logger::error_pop().BR."\n";
					break;
				}
			}

			if($index > 1)
				$text .= sprintf(i18n::s('Comments have been added to "%s".'), 'yabb_thread').BR."\n";
		}
	}

	// 'jive_board' section
	if(Sections::get('jive_board'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), 'jive_board').BR."\n";
	elseif($anchor = Sections::lookup('forums')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'jive_board';
		$fields['title'] = i18n::c('My jive discussion board');
		$fields['introduction'] = i18n::c('Sample discussion board');
		$fields['articles_layout'] = 'jive'; // a threading layout
		$fields['content_options'] = 'auto_publish'; // let surfers rate their readings
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'jive_thread' article
	if(Articles::get('jive_thread'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), 'jive_thread').BR."\n";
	elseif($anchor = Sections::lookup('jive_board')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'jive_thread';
		$fields['title'] = i18n::c('Sample jive thread');
		$fields['introduction'] = i18n::c('Sample article with its set of comments');
		$fields['description'] = i18n::c('This page demonstrates the rendering of the ##jive## layout.');
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// add sample comments to 'jive_thread'
	if($anchor = Articles::lookup('jive_thread')) {

		// add a bunch of comments
		$stats = Comments::stat_for_anchor($anchor);
		if($stats['count'] < 50) {
			for($index = 1; $index <= 50; $index++) {
				$fields = array();
				$fields['anchor'] = $anchor;
				$fields['description'] = sprintf(i18n::c('Reply #%d'), $index);
				$fields['edit_name'] = $names[ rand(0, 2) ];
				if(!Comments::post($fields)) {
					$text .= Logger::error_pop().BR."\n";
					break;
				}
			}

			if($index > 1)
				$text .= sprintf(i18n::s('Comments have been added to "%s".'), 'jive_thread').BR."\n";
		}
	}

	// blog
	//
	$text .= Skin::build_block(i18n::s('Blog'), 'subtitle');

	// 'blog' section
	if(Sections::get('blog'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), 'blog').BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = 'blog';
		$fields['title'] = i18n::c('Blog');
		$fields['introduction'] = i18n::c('Sample blogging place');
		$fields['options'] = 'with_extra_profile articles_by_publication';
		$fields['articles_layout'] = 'daily'; // that's a blog
		$fields['content_options'] = 'with_extra_profile'; // let surfers rate their readings
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'blog_page' article
	if(Articles::get('blog_page'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), 'blog_page').BR."\n";
	elseif($anchor = Sections::lookup('blog')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'blog_page';
		$fields['title'] = i18n::c('Sample page of the blog');
		$fields['introduction'] = i18n::c('Sample content with its set of notes');
		$fields['description'] = i18n::c('This page demonstrates the rendering of the ##daily## layout.');
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// add sample comments to 'blog_page'
	if($anchor = Articles::lookup('blog_page')) {

		// add a bunch of comments
		$stats = Comments::stat_for_anchor($anchor);
		if($stats['count'] < 50) {
			for($index = 1; $index <= 50; $index++) {
				$fields = array();
				$fields['anchor'] = $anchor;
				$fields['description'] = sprintf(i18n::c('Comment #%d'), $index);
				$fields['edit_name'] = $names[ rand(0, 2) ];
				if(!Comments::post($fields)) {
					$text .= Logger::error_pop().BR."\n";
					break;
				}
			}

			if($index > 1)
				$text .= sprintf(i18n::s('Comments have been added to "%s".'), 'blog_page').BR."\n";
		}
	}

	// project
	//
	$text .= Skin::build_block(i18n::s('Project'), 'subtitle');

	// 'project' top-level section
	if(Sections::get('project'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), 'project').BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = 'project';
		$fields['title'] = i18n::c('Project');
		$fields['introduction'] = i18n::c('Sample project web space');
		$fields['options'] = 'view_as_tabs';
		$fields['sections_layout'] = 'folded'; // show many articles in sections tab
		if($id = Sections::post($fields)) {
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['nick_name']).BR."\n";

			// bobby and carol as section editors
			if($user = Users::get(i18n::c('Bob')))
				Members::assign('user:'.$user['id'], 'section:'.$id);
			if($user = Users::get(i18n::c('Carol')))
				Members::assign('user:'.$user['id'], 'section:'.$id);

		} else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'project_public_page' article
	if(Articles::get('project_public_page'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), 'project_public_page').BR."\n";
	elseif($anchor = Sections::lookup('project')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'project_public_page';
		$fields['title'] = i18n::c('Project description');
		$fields['introduction'] = i18n::c('This is a public page that describes the project.');
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'project_private' section
	if(Sections::get('project_private'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), 'project_private').BR."\n";
	elseif($anchor = Sections::lookup('project')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'project_private';
		$fields['title'] = i18n::c('Project private area');
		$fields['active_set'] = 'N'; // for editors only
		$fields['articles_layout'] = 'yabb'; // list threads appropriately
		$fields['content_options'] = 'with_extra_profile'; // put poster profile aside
		$fields['home_panel'] = 'none'; // special processing at the front page -- see index.php
		$fields['introduction'] = i18n::c('For project members only');
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'project_private_page' article
	if(Articles::get('project_private_page'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), 'project_private_page').BR."\n";
	elseif($anchor = Sections::lookup('project_private')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'project_private_page';
		$fields['title'] = i18n::c('Project contribution');
		$fields['introduction'] = i18n::c('This is a private page that is part of project internal discussions.');
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// publication
	//
	$text .= Skin::build_block(i18n::s('Publication'), 'subtitle');

	// 'book' section
	if(Sections::get('book'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), 'book').BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = 'book';
		$fields['title'] = i18n::c('My manual');
		$fields['introduction'] = i18n::c('Sample electronic book');
		$fields['sections_layout'] = 'inline'; // list content of sub-sections
		$fields['articles_layout'] = 'manual'; // the default value
		$fields['locked'] = 'Y'; // post in underlying chapters
		$fields['options'] = ''; //
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'book_chapter' section
	if(Sections::get('book_chapter'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), 'book_chapter').BR."\n";
	elseif($parent = Sections::lookup('book')) {
		$fields = array();
		$fields['nick_name'] = 'book_chapter';
		$fields['title'] = i18n::c('Chapter 1 - The very first chapter of my manual');
		$fields['introduction'] = i18n::c('Post pages here to populate chapter 1');
		$fields['anchor'] = $parent; // anchor to parent
		$fields['sections_layout'] = 'inline'; // list content of sub-sections
		$fields['articles_layout'] = 'manual';
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'book_page' article
	if(Articles::get('book_page'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), 'book_page').BR."\n";
	elseif($anchor = Sections::lookup('book_chapter')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'book_page';
		$fields['title'] = i18n::c('Sample page of the manual');
		$fields['introduction'] = i18n::c('Sample content with its set of notes');
		$fields['description'] = i18n::c('This page demonstrates the rendering of the ##manual## layout.');
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// add sample comments to 'book_page'
	if($anchor = Articles::lookup('book_page')) {

		// add a bunch of comments
		$stats = Comments::stat_for_anchor($anchor);
		if($stats['count'] < 50) {
			for($index = 1; $index <= 50; $index++) {
				$fields = array();
				$fields['anchor'] = $anchor;
				$fields['description'] = sprintf(i18n::c('Note #%d'), $index);
				$fields['edit_name'] = $names[ rand(0, 2) ];
				if(!Comments::post($fields)) {
					$text .= Logger::error_pop().BR."\n";
					break;
				}
			}

			if($index > 1)
				$text .= sprintf(i18n::s('Comments have been added to "%s".'), 'book_page').BR."\n";
		}
	}

	// wikis
	//
	$text .= Skin::build_block(i18n::s('Wikis'), 'subtitle');

	// 'wikis' top-level section
	if(Sections::get('wikis'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), 'wikis').BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = 'wikis';
		$fields['title'] = i18n::c('Wikis');
		$fields['sections_layout'] = 'map';
		$fields['articles_layout'] = 'none';
		$fields['locked'] = 'Y';
		$fields['articles_templates'] = 'wiki_template';
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'wiki_anonymous ' section
	if(Sections::get('wiki_anonymous'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), 'wiki_anonymous').BR."\n";
	elseif($anchor = Sections::lookup('wikis')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'wiki_anonymous';
		$fields['title'] = i18n::c('Anonymous wiki');
		$fields['introduction'] = i18n::c('Anyone can update pages in this section');
		$fields['articles_layout'] = 'tagged'; // a wiki
		$fields['content_options'] = 'view_as_wiki anonymous_edit auto_publish with_export_tools';
		if(Sections::post($fields, FALSE))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'wiki_anonymous_page' article
	if(Articles::get('wiki_anonymous_page'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), 'wiki_anonymous_page').BR."\n";
	elseif($anchor = Sections::lookup('wiki_anonymous')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'wiki_anonymous_page';
		$fields['title'] = i18n::c('Sample wiki page');
		$fields['description'] = i18n::c("Use the command 'Edit this page' to add some text or to change this content.");
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// add sample comments to 'wiki_anonymous_page'
	if($anchor = Articles::lookup('wiki_anonymous_page')) {

		// add a bunch of comments
		$stats = Comments::stat_for_anchor($anchor);
		if($stats['count'] < 10) {
			for($index = 1; $index <= 10; $index++) {
				$fields = array();
				$fields['anchor'] = $anchor;
				$fields['description'] = sprintf(i18n::c("Note #%d\nfoo bar"), $index);
				$fields['edit_name'] = $names[ rand(0, 2) ];
				if(!Comments::post($fields)) {
					$text .= Logger::error_pop().BR."\n";
					break;
				}
			}

			if($index > 1)
				$text .= sprintf(i18n::s('Comments have been added to "%s".'), 'wiki_anonymous_page').BR."\n";
		}
	}

	// 'wiki_members' section
	if(Sections::get('wiki_members'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), 'wiki_members').BR."\n";
	elseif($anchor = Sections::lookup('wikis')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'wiki_members';
		$fields['title'] = i18n::c('Restricted wiki');
		$fields['introduction'] = i18n::c('Authenticated persons can update pages in this section');
		$fields['articles_layout'] = 'tagged'; // a wiki
		$fields['content_options'] = 'view_as_wiki members_edit auto_publish with_export_tools';
		if(Sections::post($fields, FALSE))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'wiki_members_page' article
	if(Articles::get('wiki_members_page'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), 'wiki_members_page').BR."\n";
	elseif($anchor = Sections::lookup('wiki_members')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'wiki_members_page';
		$fields['title'] = i18n::c('Sample wiki page');
		$fields['description'] = i18n::c("Use the command 'Edit this page' to add some text or to change this content.");
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'wiki_template' article
	if(Articles::get('wiki_template'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), 'wiki_template').BR."\n";
	elseif($anchor = Sections::lookup('templates')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'wiki_template';
		$fields['title'] = i18n::c('Wiki page');
		$fields['introduction'] = i18n::c('Use this page model to add a page that can be modified by any surfer');
		$fields['options'] = 'view_as_wiki edit_as_simple';
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// sections
	//
	$text .= Skin::build_block(i18n::s('Sections'), 'subtitle');

	// 'files' section
	if(Sections::get('files'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), 'files').BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = 'files';
		$fields['title'] = i18n::c('Files');
		$fields['introduction'] = i18n::c('Sample download section');
		$fields['options'] = 'with_files'; // enable file attachments
		$fields['articles_layout'] = 'none';
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'links' section
	if(Sections::get('links'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), 'links').BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = 'links';
		$fields['title'] = i18n::c('Links');
		$fields['introduction'] = i18n::c('Social bookmarking section');
		$fields['options'] = 'with_links'; // enable link upload
		$fields['articles_layout'] = 'none';
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'my_section' section
	if(Sections::get('my_section'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), 'my_section').BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = 'my_section';
		$fields['title'] = i18n::c('My Section');
		$fields['introduction'] = i18n::c('Sample plain section');
		$fields['content_options'] = 'with_export_tools';
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// categories
	//
	$text .= Skin::build_block(i18n::s('Categories'), 'subtitle');

	// 'my_category' category
	if(Categories::get('my_category'))
		$text .= sprintf(i18n::s('Category "%s" already exists.'), 'my_category').BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = 'my_category';
		$fields['title'] = i18n::c('My category');
		$fields['introduction'] = i18n::c('Sample plain category');
		$fields['description'] = i18n::c('This category has been created for experimentation purpose. Feel free to change this text, to add some images, to play with codes, etc. Have you checked the help link on the side of this page? Once you will feel more comfortable with the handling of categories, just delete this one and create other categories of your own.');
		if(Categories::post($fields))
			$text .= sprintf(i18n::s('A category "%s" has been created.'), $fields['nick_name']).BR."\n";
	}

	// 'my_sub_category' category
	if(Categories::get('my_sub_category'))
		$text .= sprintf(i18n::s('Category "%s" already exists.'), 'my_sub_category').BR."\n";
	elseif($anchor = Categories::lookup('my_category')) {
		$fields = array();
		$fields['nick_name'] = 'my_sub_category';
		$fields['anchor'] = $anchor;
		$fields['title'] = i18n::c('My sub-category');
		$fields['introduction'] = i18n::c('Sample sub category');
		$fields['description'] = i18n::c('This category has been created for experimentation purpose. Feel free to change this text, to add some images, to play with codes, etc. Have you checked the help link on the side of this page? Once you will feel more comfortable with the handling of categories, just delete this one and create other categories of your own.');
		if(Categories::post($fields))
			$text .= sprintf(i18n::s('A category "%s" has been created.'), $fields['nick_name']).BR."\n";
	}

	// articles
	//
	$text .= Skin::build_block(i18n::s('Pages'), 'subtitle');

	// 'my_article' article
	if(Articles::get('my_article'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), 'my_article').BR."\n";
	elseif($anchor = Sections::lookup('my_section')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'my_article';
		$fields['title'] = i18n::c('Edit me');
		$fields['introduction'] = i18n::c('Sample article');
		$fields['description'] = i18n::c('This is a sample article to let you learn and practice.')
			."\n\nabcdefghijklmnopqrstuvwxyz"
			." &&eacute;\"'(-&egrave;_&ccedil;&agrave; )=~#{[|`\\x^@]}"
			." ^â&ecirc;&icirc;&ocirc;û¨äëïöü£&ugrave;%*µ,;:!?./§¤"
			."\a\b\c\d\e\f\g\h\i\j\k\l\m\o\p\q\r\s\t\u\v\w\x\y\z"
			."\n:) 8) :D :O ;) :( X( !! ?? ?! -- ++ >> §§"
			."\n[b]bold[/b] and [i]italic[/i][nl][u]underlined[/u]"
			."\n<a href=\"http://myweb/mypage.html\">anchor</a><!-- comment -->"
			."\nCheck my [link=document]http://myweb/mypage.html[/link] on this subject;"
			." more info [email=here>>]me@dummy.com[/email]"
			."\n[code]// say hello\necho \"hello world\";[/code]"
			."\n[quote]Once upon a time...[/quote]"
			."\n[*]First item[nl][nl][*]Second item"
			."\n[list][*]First item [*]Second item[/list]"
			."\nLorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. "
			."\nLorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. ";;
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// comments
	//
	$text .= Skin::build_block(i18n::s('Comments'), 'subtitle');

	// add a sample comment to 'my_article'
	if($anchor = Articles::lookup('my_article')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['description'] = i18n::c('Hello world');
		$fields['edit_name'] = $names[ rand(0, 2) ];
		if(Comments::post($fields))
			$text .= sprintf(i18n::s('Comments have been added to "%s".'), 'my_article').BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// tables
	//
	$text .= Skin::build_block(i18n::s('Tables'), 'subtitle');

	// 'my_articles' article
	if(Tables::get('my_articles'))
		$text .= i18n::s('A sample "my_articles" table already exists.').BR."\n";
	elseif($anchor = Articles::lookup('my_article')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'my_articles';
		$fields['title'] = i18n::c('My Articles');
		$fields['description'] = i18n::c('This is a sample table to let you learn and practice.');
		$fields['query'] = "SELECT \n"
			."articles.title as titre, \n"
			."articles.id as 'id', \n"
			."articles.introduction as introduction, \n"
			."articles.edit_name as 'last editor', \n"
			."articles.edit_date as 'Date' \n"
			."FROM ".SQL::table_name('articles')." AS articles \n"
			."WHERE (articles.active='Y') \n"
			."ORDER BY articles.rank, articles.edit_date DESC, articles.title LIMIT 0,10";
		if(Tables::post($fields))
			$text .= sprintf(i18n::s('A table "%s" has been created.'), $fields['nick_name']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// job done
	$context['text'] .= $text;

	// follow-up commands
	$menu = array();
	$menu = array_merge($menu, array('sections/' => i18n::s('Check the updated Site Map')));
	$menu = array_merge($menu, array('help/populate.php' => i18n::s('Launch the Content Assistant again')));
	$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
	$context['text'] .= Skin::build_box(i18n::s('What do you want to do now?'), Skin::build_list($menu, 'menu_bar'), 'page_bottom');

	// flush the cache
	Cache::clear();

// ask for confirmation
} else {

	// splash message
	$context['text'] .= '<p>'.i18n::s('This script adds various sample records, including sections, categories, articles, comments and tables, to the database.').'</p>'
		.'<p>'.i18n::s('You should use this script only for learning, for demonstration or for testing purposes.').'</p>';

	// the form to get confirmation
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>'."\n"
		.'<input type="hidden" name="action" value="confirmed" />';

	// the submit button
	$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Start')).'</p>'."\n";

	// end of the form
	$context['text'] .= '</div></form>';

}

// render the skin
render_skin();

?>