<?php
/**
 * populate the database
 *
 * This script helps to create records in the database.
 *
 * The script populates the database according to the value of the parameter 'action':
 * - 'blog' -- create a blog
 * - 'book' -- create a book
 * - 'build' -- create default items, like for first installation
 * - 'forum' -- create several structured discussion boards using the yabb layout
 * - 'links' -- create a Yahoo-like directory of links
 * - 'original' -- post original articles
 * - 'partners' - our preferred partners
 * - 'polls' -- create a section for polls
 * - 'recipes' -- create a book of cooking recipes
 * - 'servers' -- sample hosts to be pinged, etc. ([script]servers/populate.php[/script])
 * - 'petition' -- create one petition, or one poll
 * - 'wiki' -- create a wiki
 *
 * If no parameter has been set, the script displays a form to make the surfer select among available options.
 *
 *
 * When the parameter 'action' has the value 'build', this script creates records in following tables:
 * - sections ([script]sections/populate.php[/script]) -- basic sections
 * - categories ([script]categories/populate.php[/script]) -- for featured pages
 * - articles ([script]articles/populate.php[/script]) -- cover page, menu, etc.
 *
 * When the parameter has the value 'build', this script also invokes a hook to populate additional tables:
 * - id: 'control/populate.php'
 * - type: 'include'
 *
 * Only associates can use this script, except if no switch file is present.
 * Invocations are also accepted if the user table is missing or empty.
 *
 * If an associate user profile is created, apply it to the current surfing session.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common libraries
include_once '../shared/global.php';

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
i18n::bind('help');

// load the skin
load_skin('help');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// default page title
$context['page_title'] = i18n::s('Content Assistant');

// permission denied
if(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('help/populate.php?action='.$action));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// create a blog
} elseif($action == 'blog') {

	// page title
	$context['page_title'] = i18n::s('Add a blog');

	// get section parameters
	if(!isset($_REQUEST['title']) || !$_REQUEST['title']) {

		// splash
		$context['text'] .= '<p>'.i18n::s('With YACS, each blog is an independant section, usually featuring one main contributor on some topic. Each post in a blog may include images, photos, files, comments, trackbacks and related links.').'</p>'
			.'<p>'.i18n::s('You can either create one general-purpose blog, or run this script several time and create a set of more specialised blogs.').'</p>';

		// a form to get section parameters
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>'."\n"
			.'<input type="hidden" name="action" value="blog" />';
		$fields = array();

		// the anchor
		$label = i18n::s('Blog anchor');
		$input = '<select name="anchor"><option value="">'.i18n::s('-- Root level')."</option>\n".Sections::get_options('none', NULL).'</select>';
		$hint = i18n::s('Please carefully select a parent section, if any');
		$fields[] = array($label, $input, $hint);

		// the title
		$label = i18n::s('Blog Title');
		$input = '<textarea id="title" name="title" rows="1" cols="50" accesskey="t">'.encode_field(i18n::c('My Blog')).'</textarea>';
		$hint = i18n::s('Please provide a meaningful title.');
		$fields[] = array($label, $input, $hint);

		// the introduction
		$label = i18n::s('Introduction');
		$input = '<textarea name="introduction" rows="5" cols="50" accesskey="i">'.encode_field(i18n::c('The best place to express myself')).'</textarea>';
		$hint = i18n::s('Appears at site map, near section title');
		$fields[] = array($label, $input, $hint);

		// the description
		$label = i18n::s('Description');
		$input = '<textarea name="description" rows="4" cols="50">'.encode_field(i18n::c('A description of what this blog is about.')).'</textarea>';
		$hint = i18n::s('Give a hint to interested people');
		$fields[] = array($label, $input, $hint);

		// the contribution flag: Yes/public, Restricted/logged, No/associates
		$label = i18n::s('Contribution');
		$input = '<input type="radio" name="contribution" value="N" accesskey="c" checked="checked" /> '.i18n::s('Only associates and owners can contribute.').BR;
		$input .= '<input type="radio" name="contribution" value="R" /> '.i18n::s('Any authenticated member can contribute.');
		$fields[] = array($label, $input);

		// the active flag: Yes/public, Restricted/logged, No/associates
		$label = i18n::s('Access');
		$input = '<input type="radio" name="active" value="Y" accesskey="v" checked="checked" /> '.i18n::s('Public - Everybody, including anonymous surfers').BR;
		$input .= '<input type="radio" name="active" value="R" /> '.i18n::s('Community - Access is granted to any identified surfer').BR;
		$input .= '<input type="radio" name="active" value="N" /> '.i18n::s('Private - Access is restricted to selected persons');
		$fields[] = array($label, $input);

		// home panel
		$label = i18n::s('Front page');
		$input = i18n::s('Should content of this section be displayed at the front page?').BR;
		$input .= '<input type="radio" name="index_map" value="Y" checked="checked" /> '.i18n::s('Yes').BR;
		$input .= '<input type="radio" name="index_map" value="N" /> '.i18n::s('No');
		$fields[] = array($label, $input);

		// build the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// the submit button
		$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Add content'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		// append the script used for data checking on the browser
		$context['text'] .= JS_PREFIX
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
			.'// set the focus on first form field'."\n"
			.'$("#title").focus();'."\n"
			.JS_SUFFIX."\n";

	// create a section
	} else {

		$fields = array();
		$fields['anchor'] = $_REQUEST['anchor'];
		$fields['title'] = $_REQUEST['title'];
		$fields['introduction'] = $_REQUEST['introduction'];
		$fields['description'] = $_REQUEST['description'];
		$fields['active_set'] = $_REQUEST['active'];
		$fields['index_map'] = $_REQUEST['index_map'];
		$fields['options'] = 'with_extra_profile articles_by_publication';
		$fields['articles_layout'] = 'daily'; // the preferred layout for blogs
		$fields['content_options'] = 'with_extra_profile'; // show user profiles in a side panel
		if($_REQUEST['contribution'] == 'N')	// only associates and owners can contribute
			$fields['locked'] = 'Y';
		$fields['rank'] = 10000; // default value
		if($fields['id'] = Sections::post($fields)) {

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// splash
			$context['text'] .= '<p>'.i18n::s('Congratulations, you have shared new content.').'</p>';

			// feed-back on contributor scope
			switch($_REQUEST['contribution']) {
			case 'N':
				$context['text'] .= '<p>'.i18n::s('Assign at least one member as editor of this blog to enable contributions. Please remind that associates can always contribute at any place.').'</p>';
				break;

			case 'R':
				$context['text'] .= '<p>'.i18n::s('Any authenticated member of the community is allowed to contribute to this blog. You should control community membership to avoid unappropriate material.').'</p>';
				break;

			}

			// follow-up commands
			$follow_up = i18n::s('What do you want to do now?');
			$menu = array();
			$menu = array_merge($menu, array(Sections::get_permalink($fields) => i18n::s('Access the new blog')));
			if(Surfer::may_upload())
				$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$fields['id']) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('help/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			$follow_up .= Skin::build_list($menu, 'menu_bar');
			$context['text'] .= Skin::build_block($follow_up, 'bottom');

			// new content has been created
			Logger::remember('help/populate.php: content assistant has created new content');

		}
	}

// create a book
} elseif($action == 'book') {

	// page title
	$context['page_title'] = i18n::s('Add a book');

	// get section parameters
	if(!isset($_REQUEST['title']) || !$_REQUEST['title']) {

		// splash
		$context['text'] .= '<p>'.i18n::s('With YACS, a book is an hierarchy of sections and of pages.').'</p>';

		// a form to get section parameters
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>'."\n"
			.'<input type="hidden" name="action" value="book" />';
		$fields = array();

		// the anchor
		$label = i18n::s('Book anchor');
		$input = '<select name="anchor"><option value="">'.i18n::s('-- Root level')."</option>\n".Sections::get_options('none', NULL).'</select>';
		$hint = i18n::s('Please carefully select a parent section, if any');
		$fields[] = array($label, $input, $hint);

		// the title
		$label = i18n::s('Book Title');
		$input = '<textarea id="title" name="title" rows="1" cols="50" accesskey="t">'.encode_field(i18n::c('User Manual')).'</textarea>';
		$hint = i18n::s('Please provide a meaningful title.');
		$fields[] = array($label, $input, $hint);

		// the introduction
		$label = i18n::s('Introduction');
		$input = '<textarea name="introduction" rows="5" cols="50" accesskey="i">'.encode_field(i18n::c('With reader notes')).'</textarea>';
		$hint = i18n::s('Appears at site map, near book title');
		$fields[] = array($label, $input, $hint);

		// the description
		$label = i18n::s('Description');
		$input = '<textarea name="description" rows="4" cols="50">'.encode_field(i18n::c('This book is electronic, meaning its content dynamically evolves over time. Moreover, notes may be added to each page by readers.')).'</textarea>';
		$hint = i18n::s('Give a hint to interested people');
		$fields[] = array($label, $input, $hint);

		// build the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// up to 8 sections
		$context['text'] .= Skin::build_block(i18n::s('Up to 8 sections'), 'title');

		$context['text'] .= '<p>'.i18n::s('Add as many sections you need. You will be able to add new sections afterwards.')."</p>\n";

		// loop
		for($index = 1; $index <= 8; $index++) {
			$context['text'] .= Skin::build_block(sprintf(i18n::s('Section #%d'), $index), 'subtitle');

			$label = i18n::s('Title');
			$input = '<input type="text" name="titles[]" size="50" value="" />';
			$fields[] = array($label, $input);

			$label = i18n::s('Introduction');
			$input = '<textarea name="introductions[]" rows="2" cols="50"></textarea>';
			$fields[] = array($label, $input);

			// build the form
			$context['text'] .= Skin::build_form($fields);
			$fields = array();

		}

		// submit
		$context['text'] .= Skin::build_block(i18n::s('Start'), 'title');

		// the submit button
		$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Add content'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		// append the script used for data checking on the browser
		$context['text'] .= JS_PREFIX
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
			.'// set the focus on first form field'."\n"
			.'$("#title").focus();'."\n"
			.JS_SUFFIX."\n";

	// create a section
	} else {

		$fields = array();
		$fields['anchor'] = $_REQUEST['anchor'];
		$fields['title'] = $_REQUEST['title'];
		$fields['introduction'] = $_REQUEST['introduction'];
		$fields['description'] = $_REQUEST['description'];
		$fields['articles_layout'] = 'none'; // the preferred layout for books
		$fields['sections_layout'] = 'inline'; // the preferred layout for books
		$fields['options'] = 'articles_by_title'; // preserve page ordering over time
		$fields['content_options'] = 'auto_publish view_as_wiki edit_as_simple with_export_tools with_neighbours'; // let surfers convert pages
		$fields['rank'] = 10000; // default value
		if($fields['id'] = Sections::post($fields)) {

			// create new sub-sections
			for($index = 0; $index < count($_REQUEST['titles']); $index++) {

				$sub_section = array();
				$sub_section['anchor'] = 'section:'.$fields['id'];
				$sub_section['title'] = $_REQUEST['titles'][$index];
				$sub_section['introduction'] = $_REQUEST['introductions'][$index];
				$sub_section['articles_layout'] = 'manual'; // the preferred layout for books
				$sub_section['sections_layout'] = 'none'; // the preferred layout for books
				$sub_section['options'] = 'articles_by_title'; // preserve page ordering over time
				$sub_section['content_options'] = 'auto_publish view_as_wiki edit_as_simple with_export_tools with_neighbours'; // let surfers convert pages
				$sub_section['rank'] = ($index+1); //  preserve order
				if($sub_section['title'])
					Sections::post($sub_section, FALSE);

			}

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// splash
			$context['text'] .= '<p>'.i18n::s('Congratulations, you have shared new content.').'</p>';

			// follow-up commands
			$follow_up = i18n::s('What do you want to do now?');
			$menu = array();
			$menu = array_merge($menu, array(Sections::get_permalink($fields) => i18n::s('Access the new book')));
			if(Surfer::may_upload())
				$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$fields['id']) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('help/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			$follow_up .= Skin::build_list($menu, 'menu_bar');
			$context['text'] .= Skin::build_block($follow_up, 'bottom');

			// new content has been created
			Logger::remember('help/populate.php: content assistant has created new content');

		}
	}

// initialize the database with sample data
} elseif($action == 'build') {

	// redo the basic steps of data creation
	include_once '../control/populate.php';

// create a forum
} elseif($action == 'forum') {

	// page title
	$context['page_title'] = i18n::s('Add a forum');

	// get section parameters
	if(!isset($_REQUEST['title']) || !$_REQUEST['title']) {

		// splash
		$context['text'] .= '<p>'.i18n::s('A forum is made of one or several discussion boards, which are aiming to support threads of interaction between community members. Each topic started in a discussion board may feature images, photos, files, and related links.').'</p>'
			.'<p>'.i18n::s('You can either create one general-purpose discussion board, or a set of more specialised boards.').'</p>';

		// a form to get section parameters
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>'."\n"
			.'<input type="hidden" name="action" value="forum" />';
		$fields = array();

		// the anchor
		$label = i18n::s('Forum anchor');
		$input = '<select name="anchor"><option value="">'.i18n::s('-- Root level')."</option>\n".Sections::get_options('none', NULL).'</select>';
		$hint = i18n::s('Please carefully select a parent section, if any');
		$fields[] = array($label, $input, $hint);

		// the title
		$label = i18n::s('Forum Title');
		$input = '<textarea id="title" name="title" rows="1" cols="50" accesskey="t">'.encode_field(i18n::c('Forum')).'</textarea>';
		$hint = i18n::s('Please provide a meaningful title.');
		$fields[] = array($label, $input, $hint);

		// the introduction
		$label = i18n::s('Introduction');
		$input = '<textarea name="introduction" rows="5" cols="50" accesskey="i">'.encode_field(i18n::c('Post your brand new ideas here!')).'</textarea>';
		$hint = i18n::s('Appears at site map, near section title');
		$fields[] = array($label, $input, $hint);

		// the description
		$label = i18n::s('Description');
		$input = '<textarea name="description" rows="4" cols="50">'.encode_field(i18n::c('Registered members of this site may start a thread on any subject here. Use this forum either to ask for support, or to share new ideas with other members of the community. Pages posted in the forum benefit from the full power of YACS, including the ability to create polls, to upload files and to attach links.')).'</textarea>';
		$hint = i18n::s('Give a hint to interested people');
		$fields[] = array($label, $input, $hint);

		// build the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// up to 8 sections
		$context['text'] .= Skin::build_block(i18n::s('Up to 8 boards'), 'title');

		$context['text'] .= '<p>'.i18n::s('You will be able to enhance these boards, or to add new boards, afterwards.')."</p>\n";

		// loop
		for($index = 1; $index <= 8; $index++) {
			$context['text'] .= Skin::build_block(sprintf(i18n::s('Board #%d'), $index), 'subtitle');

			$label = i18n::s('Family');
			$input = '<input type="text" name="families[]" size="50" value="" />';
			$fields[] = array($label, $input, i18n::s('Repeat the same string in successive boards that have to be grouped in the forum.'));

			$label = i18n::s('Title');
			$input = '<input type="text" name="titles[]" size="50" value="" />';
			$fields[] = array($label, $input);

			$label = i18n::s('Introduction');
			$input = '<textarea name="introductions[]" rows="2" cols="50"></textarea>';
			$fields[] = array($label, $input);

			// build the form
			$context['text'] .= Skin::build_form($fields);
			$fields = array();

		}

		// submit
		$context['text'] .= Skin::build_block(i18n::s('Start'), 'title');

		// the submit button
		$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Add content'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		// append the script used for data checking on the browser
		$context['text'] .= JS_PREFIX
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
			.'// set the focus on first form field'."\n"
			.'$("#title").focus();'."\n"
			.JS_SUFFIX."\n";

	// create a section
	} else {

		$fields = array();
		$fields['anchor'] = $_REQUEST['anchor'];
		$fields['title'] = $_REQUEST['title'];
		$fields['introduction'] = $_REQUEST['introduction'];
		$fields['description'] = $_REQUEST['description'];
		$fields['articles_layout'] = 'none'; // the preferred layout for discussion boards
		$fields['sections_layout'] = 'yabb'; // the preferred layout for a forum
		$fields['locked'] = 'Y'; // post in discussion boards
		if($fields['id'] = Sections::post($fields)) {

			// create new sub-sections
			for($index = 0; $index < count($_REQUEST['titles']); $index++) {

				$sub_section = array();
				$sub_section['anchor'] = 'section:'.$fields['id'];
				$sub_section['family'] = $_REQUEST['families'][$index];
				$sub_section['title'] = $_REQUEST['titles'][$index];
				$sub_section['introduction'] = $_REQUEST['introductions'][$index];
				$sub_section['articles_layout'] = 'yabb'; // the preferred layout for discussion boards
				$sub_section['articles_templates'] = 'information_template, question_template';
				$sub_section['content_options'] = 'auto_publish, with_prefix_profile'; // control is a posteriori; show poster avatar, if any
				$sub_section['rank'] = ($index+1); //  preserve order
				if($sub_section['title'])
					Sections::post($sub_section, FALSE);

			}

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// splash
			$context['text'] .= '<p>'.i18n::s('Congratulations, you have shared new content.').'</p>';

			// follow-up commands
			$follow_up = i18n::s('What do you want to do now?');
			$menu = array();
			$menu = array_merge($menu, array(Sections::get_permalink($fields) => i18n::s('Check the new forum')));
			if(Surfer::may_upload())
				$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$fields['id']) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('help/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			$follow_up .= Skin::build_list($menu, 'menu_bar');
			$context['text'] .= Skin::build_block($follow_up, 'bottom');

		}

		// new content has been created
		Logger::remember('help/populate.php: content assistant has created new content');

	}

// create a directory of links
} elseif($action == 'links') {

	// page title
	$context['page_title'] = i18n::s('Add a directory of links');

	// reuse the section 'bookmarks'
	if(!$item = Sections::get('bookmarks')) {

		// or create one
		$item = array();
		$item['nick_name'] = 'bookmarks';
		$item['title'] = i18n::c('Bookmarks');
		$item['introduction'] = i18n::c('Shared links of interest');
		$item['description'] = i18n::c('This section, and related sub-sections, lists links submitted by authenticated persons.');
		$item['sections_layout'] = 'yahoo';
		$item['locked'] = 'Y';
		$item['id'] = Sections::post($item);

	}

	// get parameters
	if(!isset($_REQUEST['main_title']) || !$_REQUEST['main_title']) {

		// splash
		$context['text'] .= '<p>'.i18n::s('YACS lets people share bookmarks and favorites quite easily.').'</p>'
			.'<p>'.i18n::s('Use this assistant to initialize the tree of sections used to classify links at your site. Of course, sections can be changed, deleted or added individually afterwards.').'</p>';

		// a form to get section parameters
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>'."\n"
			.'<input type="hidden" name="action" value="links" />';
		$fields = array();

		// top category
		$context['text'] .= Skin::build_block(i18n::s('Top section'), 'title');

		// the title
		$label = i18n::s('Section title');
		$input = '<input type="text" id="main_title" name="main_title" size="50" accesskey="t" value="'.encode_field($item['title']).'" />';
		$hint = i18n::s('Please provide a meaningful title.');
		$fields[] = array($label, $input, $hint);

		// the introduction
		$label = i18n::s('Introduction');
		$input = '<textarea name="main_introduction" rows="2" cols="50" accesskey="i">'.encode_field($item['introduction']).'</textarea>';
		$hint = i18n::s('Appears at site map, near section title');
		$fields[] = array($label, $input, $hint);

		// the description
		$label = i18n::s('Description');
		$input = '<textarea name="main_description" rows="4" cols="50">'.encode_field($item['description']).'</textarea>';
		$hint = i18n::s('To introduce the list of sections below');
		$fields[] = array($label, $input, $hint);

		// update the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// up to 8 sections
		$context['text'] .= Skin::build_block(i18n::s('Up to 8 sections'), 'title');

		$context['text'] .= '<p>'.i18n::s('Add as many sections you need. We suggest to declare an even number of sections.')."</p>\n";

		// loop
		for($index = 1; $index <= 8; $index++) {
			$label = sprintf(i18n::s('Section #%d'), $index);
			$input = '<input type="text" name="titles[]" size="50" value="" />';
			$fields[] = array($label, $input);

			$label = i18n::s('Introduction');
			$input = '<textarea name="introductions[]" rows="2" cols="50"></textarea>';
			$fields[] = array($label, $input);
		}

		// update the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// submit
		$context['text'] .= Skin::build_block(i18n::s('Start'), 'title');

		// the submit button
		$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Add content'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		// append the script used for data checking on the browser
		$context['text'] .= JS_PREFIX
			.'// check that main fields are not empty'."\n"
			.'func'.'tion validateDocumentPost(container) {'."\n"
			."\n"
			.'	// title is mandatory'."\n"
			.'	if(!container.main_title.value) {'."\n"
			.'		alert("'.i18n::s('Please provide a meaningful title.').'");'."\n"
			.'		Yacs.stopWorking();'."\n"
			.'		return false;'."\n"
			.'	}'."\n"
			."\n"
			.'	// successful check'."\n"
			.'	return true;'."\n"
			.'}'."\n"
			."\n"
			.'// set the focus on first form field'."\n"
			.'$("#main_title").focus();'."\n"
			.JS_SUFFIX."\n";

	// create the stuff
	} else {

		// update the main section
		$item['title'] = $_REQUEST['main_title'];
		$item['introduction'] = $_REQUEST['main_introduction'];
		$item['description'] = $_REQUEST['main_description'];
		Sections::put($item);

		// create new sections
		for($index = 0; $index < count($_REQUEST['titles']); $index++) {

			$fields = array();
			$fields['anchor'] = 'section:'.$item['id'];
			$fields['title'] = $_REQUEST['titles'][$index];
			$fields['introduction'] = $_REQUEST['introductions'][$index];
			$fields['articles_layout'] = 'none'; // no articles here
			$fields['options'] = 'with_links';
			if($fields['title'])
				Sections::post($fields, FALSE);
		}

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// splash
		$context['text'] .= '<p>'.i18n::s('Congratulations, you have shared new content.').'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array(Sections::get_permalink($items) => i18n::s('Access the directory')));
		$menu = array_merge($menu, array('help/populate.php' => i18n::s('Launch the Content Assistant again')));
		$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		// new content has been created
		Logger::remember('help/populate.php: content assistant has created new content');

	}

// publish original work
} elseif($action == 'original') {

	// page title
	$context['page_title'] = i18n::s('Add a section');

	// get section parameters
	if(!isset($_REQUEST['title']) || !$_REQUEST['title']) {

		// splash
		$context['text'] .= '<p>'.i18n::s('Use YACS to publish pages you have carefully crafted, and look at your name proudly listed on your work. Each page may feature images, photos, files, comments and related links.').'</p>';

		// a form to get section parameters
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>'."\n"
			.'<input type="hidden" name="action" value="original" />';
		$fields = array();

		// the anchor
		$label = i18n::s('Section anchor');
		$input = '<select name="anchor"><option value="">'.i18n::s('-- Root level')."</option>\n".Sections::get_options('none', NULL).'</select>';
		$hint = i18n::s('Please carefully select a parent section, if any');
		$fields[] = array($label, $input, $hint);

		// the title
		$label = i18n::s('Section title');
		$input = '<textarea id="title" name="title" rows="1" cols="50" accesskey="t">'.encode_field(i18n::c('Original pages')).'</textarea>';
		$hint = i18n::s('Please provide a meaningful title.');
		$fields[] = array($label, $input, $hint);

		// the introduction
		$label = i18n::s('Introduction');
		$input = '<textarea name="introduction" rows="5" cols="50" accesskey="i">'.encode_field(i18n::c('Read, learn, and react!')).'</textarea>';
		$hint = i18n::s('Appears at site map, near section title');
		$fields[] = array($label, $input, $hint);

		// the description
		$label = i18n::s('Description');
		$input = '<textarea name="description" rows="2" cols="50"></textarea>';
		$hint = i18n::s('Give a hint to interested people');
		$fields[] = array($label, $input, $hint);

		// author profile
		$label = i18n::s('Author\'s profile');
		$input = '<input type="radio" name="profile" value="prefix" checked="checked" /> '.i18n::s('Below page title').BR;
		$input .= '<input type="radio" name="profile" value="suffix" /> '.i18n::s('At page bottom').BR;
		$input .= '<input type="radio" name="profile" value="extra" /> '.i18n::s('On page side').BR;
		$fields[] = array($label, $input);

		// the active flag: Yes/public, Restricted/logged, No/associates
		$label = i18n::s('Access');
		$input = '<input type="radio" name="active" value="Y" accesskey="v" checked="checked" /> '.i18n::s('Public - Everybody, including anonymous surfers').BR;
		$input .= '<input type="radio" name="active" value="R" /> '.i18n::s('Community - Access is granted to any identified surfer').BR;
		$input .= '<input type="radio" name="active" value="N" /> '.i18n::s('Private - Access is restricted to selected persons');
		$fields[] = array($label, $input);

		// home panel
		$label = i18n::s('Front page');
		$input = i18n::s('Should content of this section be displayed at the front page?').BR;
		$input .= '<input type="radio" name="index_map" value="Y" checked="checked" /> '.i18n::s('Yes').BR;
		$input .= '<input type="radio" name="index_map" value="N" /> '.i18n::s('No');
		$fields[] = array($label, $input);

		// build the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// the submit button
		$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Add content'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		// append the script used for data checking on the browser
		$context['text'] .= JS_PREFIX
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
			.'// set the focus on first form field'."\n"
			.'$("#title").focus();'."\n"
			.JS_SUFFIX."\n";

	// create a section
	} else {

		$fields = array();
		$fields['anchor'] = $_REQUEST['anchor'];
		$fields['title'] = $_REQUEST['title'];
		$fields['introduction'] = $_REQUEST['introduction'];
		$fields['description'] = $_REQUEST['description'];
		$fields['active_set'] = $_REQUEST['active'];
		$fields['index_map'] = $_REQUEST['index_map'];
		if($_REQUEST['profile'] == 'extra')
			$fields['content_options'] = 'with_extra_profil with_export_tools';
		elseif($_REQUEST['profile'] == 'prefix')
			$fields['content_options'] = 'with_prefix_profile with_export_tools';
		else
			$fields['content_options'] = 'with_suffix_profile with_export_tools';
		$fields['rank'] = 10000; // default value
		if($fields['id'] = Sections::post($fields)) {

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// splash
			$context['text'] .= '<p>'.i18n::s('Congratulations, you have shared new content.').'</p>';

			// follow-up commands
			$follow_up = i18n::s('What do you want to do now?');
			$menu = array();
			$menu = array_merge($menu, array(Sections::get_permalink($fields) => i18n::s('Access the new section')));
			if(Surfer::may_upload())
				$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$fields['id']) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('help/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			$follow_up .= Skin::build_list($menu, 'menu_bar');
			$context['text'] .= Skin::build_block($follow_up, 'bottom');

			// new content has been created
			Logger::remember('help/populate.php: content assistant has created new content');

		}
	}

// dedicate a section to partners
} elseif($action == 'partners') {

	// page title
	$context['page_title'] = i18n::s('Add a section');

	// a section already exists for partners
	if($item = Sections::get('partners')) {

		// splash
		$context['text'] .= '<p>'.i18n::s('A section already exists for partners.').'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array(Sections::get_permalink($item) => i18n::s('View the section')));
		$menu = array_merge($menu, array('help/populate.php' => i18n::s('Launch the Content Assistant again')));
		$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// get section parameters
	} elseif(!isset($_REQUEST['title']) || !$_REQUEST['title']) {

		// splash
		$context['text'] .=  '<p>'.i18n::s('Partners are listed at the front page, in a scrollable area.').'</p>'
			.'<p>'.i18n::s('Change the value of the ranking field to prioritize partners.').'</p>';

		// a form to get section parameters
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>'."\n"
			.'<input type="hidden" name="action" value="partners" />';
		$fields = array();

		// the title
		$label = i18n::s('Section title');
		$input = '<textarea id="title" name="title" rows="1" cols="50" accesskey="t">'.encode_field(i18n::c('Partners')).'</textarea>';
		$hint = i18n::s('Adapt the title to the kinds of partnership you are developing');
		$fields[] = array($label, $input, $hint);

		// the introduction
		$label = i18n::s('Introduction');
		$input = '<textarea name="introduction" rows="5" cols="50" accesskey="i">'.encode_field(i18n::c('They are trusting us')).'</textarea>';
		$hint = i18n::s('Appears at site map, near section title');
		$fields[] = array($label, $input, $hint);

		// build the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// the submit button
		$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Add content'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		// append the script used for data checking on the browser
		$context['text'] .= JS_PREFIX
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
			.'// set the focus on first form field'."\n"
			.'$("#title").focus();'."\n"
			.JS_SUFFIX."\n";

	// create a section
	} else {

		$fields = array();
		$fields['nick_name'] = 'partners';
		$fields['title'] = $_REQUEST['title'];
		$fields['introduction'] = $_REQUEST['introduction'];
		$fields['index_map'] = 'N'; // new pages are not pushed at the front page
		$fields['rank'] = 50000; // towards the end of the list
		$fields['content_options'] = 'without_rating';
		if($fields['id'] = Sections::post($fields)) {

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// splash
			$context['text'] .= '<p>'.i18n::s('Congratulations, you have shared new content.').'</p>';

			// follow-up commands
			$follow_up = i18n::s('What do you want to do now?');
			$menu = array();
			$menu = array_merge($menu, array(Sections::get_permalink($fields) => i18n::s('Access the new section')));
			if(Surfer::may_upload())
				$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$fields['id']) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('help/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			$follow_up .= Skin::build_list($menu, 'menu_bar');
			$context['text'] .= Skin::build_block($follow_up, 'bottom');

			// new content has been created
			Logger::remember('help/populate.php: content assistant has created new content');

		}
	}

// dedicate a section to polls
} elseif($action == 'polls') {

	// page title
	$context['page_title'] = i18n::s('Add a section');

	// get section parameters
	if(!isset($_REQUEST['title']) || !$_REQUEST['title']) {

		// splash
		$context['text'] .= '<p>'.i18n::s('With YACS, polls are ordinary articles that have extended attributes to handle votes and to display bar graphs.').'</p>'
			.'<p>'.i18n::s('You can either create a single polling section, or run this script several time and create a set of more specialised sections.').'</p>';

		// a form to get section parameters
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>'."\n"
			.'<input type="hidden" name="action" value="polls" />';
		$fields = array();

		// the anchor
		$label = i18n::s('Section anchor');
		$input = '<select name="anchor"><option value="">'.i18n::s('-- Root level')."</option>\n".Sections::get_options('none', NULL).'</select>';
		$hint = i18n::s('Please carefully select a parent section, if any');
		$fields[] = array($label, $input, $hint);

		// the title
		$label = i18n::s('Section title');
		$input = '<textarea id="title" name="title" rows="1" cols="50" accesskey="t">'.encode_field(i18n::c('Polls')).'</textarea>';
		$hint = i18n::s('Use either a global name (eg, "the voting machine") or, narrow it and create multiple sections');
		$fields[] = array($label, $input, $hint);

		// the introduction
		$label = i18n::s('Introduction');
		$input = '<textarea name="introduction" rows="5" cols="50" accesskey="i">'.encode_field(i18n::c('The current active poll, plus previous ones')).'</textarea>';
		$hint = i18n::s('Appears at site map, near section title');
		$fields[] = array($label, $input, $hint);

		// the active flag: Yes/public, Restricted/logged, No/associates
		$label = i18n::s('Access');
		$input = '<input type="radio" name="active" value="Y" accesskey="v" checked="checked" /> '.i18n::s('Public - Everybody, including anonymous surfers').BR;
		$input .= '<input type="radio" name="active" value="R" /> '.i18n::s('Community - Access is granted to any identified surfer').BR;
		$input .= '<input type="radio" name="active" value="N" /> '.i18n::s('Private - Access is restricted to selected persons');
		$fields[] = array($label, $input);

		// home panel
		$label = i18n::s('Front page');
		$input = i18n::s('Should content of this section be displayed at the front page?').BR;
		$input .= '<input type="radio" name="index_map" value="Y" checked="checked" /> '.i18n::s('Yes').BR;
		$input .= '<input type="radio" name="index_map" value="N" /> '.i18n::s('No');
		$fields[] = array($label, $input);

		// build the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// the submit button
		$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Add content'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		// append the script used for data checking on the browser
		$context['text'] .= JS_PREFIX
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
			.'// set the focus on first form field'."\n"
			.'$("#title").focus();'."\n"
			.JS_SUFFIX."\n";

	// create a section
	} else {

		$fields = array();
		$fields['anchor'] = $_REQUEST['anchor'];
		$fields['title'] = $_REQUEST['title'];
		$fields['introduction'] = $_REQUEST['introduction'];
		$fields['active_set'] = $_REQUEST['active'];
		$fields['index_map'] = $_REQUEST['index_map'];
		$fields['overlay'] = 'poll'; // poll management
		$fields['rank'] = 10000; // default value
		$fields['content_options'] = 'without_rating';
		if($fields['id'] = Sections::post($fields)) {

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// splash
			$context['text'] .= '<p>'.i18n::s('Congratulations, you have shared new content.').'</p>';

			// follow-up commands
			$follow_up = i18n::s('What do you want to do now?');
			$menu = array();
			$menu = array_merge($menu, array(Sections::get_permalink($fields) => i18n::s('Access the new section')));
			if(Surfer::may_upload())
				$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$fields['id']) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('help/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			$follow_up .= Skin::build_list($menu, 'menu_bar');
			$context['text'] .= Skin::build_block($follow_up, 'bottom');

			// new content has been created
			Logger::remember('help/populate.php: content assistant has created new content');

		}
	}

// dedicate a section to cooking recipes
} elseif($action == 'recipes') {

	// page title
	$context['page_title'] = i18n::s('Add a section');

	// get section parameters
	if(!isset($_REQUEST['title']) || !$_REQUEST['title']) {

		// splash
		$context['text'] .= '<p>'.i18n::s('With YACS, recipes are ordinary articles that have some extended attributes such as the list of ingredients, or the number of people to serve, etc.').'</p>'
			.'<p>'.i18n::s('You can either create only one section to host every cooking recipe posted at your site, or run this script several time and create a set of more specialised sections.').'</p>';

		// a form to get section parameters
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>'."\n"
			.'<input type="hidden" name="action" value="recipes" />';
		$fields = array();

		// the anchor
		$label = i18n::s('Section anchor');
		$input = '<select name="anchor"><option value="">'.i18n::s('-- Root level')."</option>\n".Sections::get_options('none', NULL).'</select>';
		$hint = i18n::s('Please carefully select a parent section, if any');
		$fields[] = array($label, $input, $hint);

		// the title
		$label = i18n::s('Section title');
		$input = '<textarea id="title" name="title" rows="1" cols="50" accesskey="t"></textarea>';
		$hint = i18n::s('Use either a global name (eg, "the family cookbook") or, narrow it and create multiple sections (eg, "Famous starters", "Main dishes", ...)');
		$fields[] = array($label, $input, $hint);

		// the introduction
		$label = i18n::s('Introduction');
		$input = '<textarea name="introduction" rows="5" cols="50" accesskey="i"></textarea>';
		$hint = i18n::s('Appears at site map, near section title');
		$fields[] = array($label, $input, $hint);

		// the active flag: Yes/public, Restricted/logged, No/associates
		$label = i18n::s('Access');
		$input = '<input type="radio" name="active" value="Y" accesskey="v" checked="checked" /> '.i18n::s('Public - Everybody, including anonymous surfers').BR;
		$input .= '<input type="radio" name="active" value="R" /> '.i18n::s('Community - Access is granted to any identified surfer').BR;
		$input .= '<input type="radio" name="active" value="N" /> '.i18n::s('Private - Access is restricted to selected persons');
		$fields[] = array($label, $input);

		// home panel
		$label = i18n::s('Front page');
		$input = i18n::s('Should content of this section be displayed at the front page?').BR;
		$input .= '<input type="radio" name="index_map" value="Y" checked="checked" /> '.i18n::s('Yes').BR;
		$input .= '<input type="radio" name="index_map" value="N" /> '.i18n::s('No');
		$fields[] = array($label, $input);

		// build the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// the submit button
		$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Add content'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		// append the script used for data checking on the browser
		$context['text'] .= JS_PREFIX
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
			.'// set the focus on first form field'."\n"
			.'$("#title").focus();'."\n"
			.JS_SUFFIX."\n";

	// create a section
	} else {

		$fields = array();
		$fields['anchor'] = $_REQUEST['anchor'];
		$fields['title'] = $_REQUEST['title'];
		$fields['introduction'] = $_REQUEST['introduction'];
		$fields['active_set'] = $_REQUEST['active'];
		$fields['index_map'] = $_REQUEST['index_map'];
		$fields['content_options'] = 'with_export_tools';
		$fields['overlay'] = 'recipe'; // recipe management
		$fields['rank'] = 10000; // default value
		if($fields['id'] = Sections::post($fields)) {

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// splash
			$context['text'] .= '<p>'.i18n::s('Congratulations, you have shared new content.').'</p>';

			// follow-up commands
			$follow_up = i18n::s('What do you want to do now?');
			$menu = array();
			$menu = array_merge($menu, array(Sections::get_permalink($fields) => i18n::s('Access the new section')));
			if(Surfer::may_upload())
				$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$fields['id']) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('help/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			$follow_up .= Skin::build_list($menu, 'menu_bar');
			$context['text'] .= Skin::build_block($follow_up, 'bottom');

			// new content has been created
			Logger::remember('help/populate.php: content assistant has created new content');

		}
	}

// create sample server profiles
} elseif($action == 'servers') {

	// page title
	$context['page_title'] = i18n::s('Add server profiles');

	// ask for confirmation
	if(!isset($_REQUEST['confirmation']) || !$_REQUEST['confirmation']) {

		// splash message
		$context['text'] .= '<p>'.i18n::s('This script adds some public servers to be pinged when content is added to your server. Do not trigger this if your host is not visible from the Internet.').'</p>';

		// the form to get confirmation
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>'."\n"
			.'<input type="hidden" name="action" value="servers" />'
			.'<input type="hidden" name="confirmation" value="Y" />';

		// the submit button
		$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Start')).'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		// append the script used for data checking on the browser
		$context['text'] .= JS_PREFIX
			.'// check that main fields are not empty'."\n"
			.'func'.'tion validateDocumentPost(container) {'."\n"
			."\n"
			.'	// successful check'."\n"
			.'	return true;'."\n"
			.'}'."\n"
			."\n"
			.JS_SUFFIX;

	// actual data creation
	} else {

		/**
		 * dynamically generate the page
		 *
		 * @see skins/index.php
		 */
		function send_body() {
			global $context;

			// populate tables for servers
			if(is_readable('../servers/populate.php'))
				include_once '../servers/populate.php';

			// splash
			echo '<h3>'.i18n::s('What do you want to do now?').'</h3>';

			// follow-up commands
			$menu = array();
			$menu = array_merge($menu, array('servers/' => i18n::s('Servers')));
			$menu = array_merge($menu, array('help/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			echo Skin::build_list($menu, 'menu_bar');

			// new content has been created
			Logger::remember('help/populate.php: content assistant has created new content');

		}
	}

// create sample items
} elseif($action == 'test') {
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'tools/populate.php');

// create a petition or a poll
} elseif($action == 'petition') {

	// page title
	$context['page_title'] = i18n::s('Add a page');

	// get section parameters
	if(!isset($_REQUEST['type']) || !$_REQUEST['type']) {

		// splash message
		$context['text'] .= '<p>'.i18n::s('This script will create one single page to capture collective feed-back. What kind of support are you looking for?').'</p>';

		// a form to get section parameters
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>'."\n"
			.'<input type="hidden" name="action" value="petition" />';
		$fields = array();

		// petition
		$label = i18n::s('Petition');
		$input = '<input type="radio" name="type" value="petition" /> '.i18n::s('Ideal to express a broad support of some idea. Every signature can be commented.');
		$fields[] = array($label, $input);

		// poll
		$label = i18n::s('Poll');
		$input = '<input type="radio" name="type" value="poll" /> '.i18n::s('The quickest way to identify trends. Any surfer can select among offered options, and YACS will sum up all clicks.');
		$fields[] = array($label, $input);

		// build the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// the submit button
		$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Start'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

	// create a page
	} else
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'articles/edit.php?variant='.urlencode(strip_tags($_REQUEST['type'])));

// create a wiki
} elseif($action == 'wiki') {

	// page title
	$context['page_title'] = i18n::s('Add a wiki');

	// get section parameters
	if(!isset($_REQUEST['title']) || !$_REQUEST['title']) {

		// splash
		$context['text'] .= '<p>'.i18n::s('With YACS, each wiki is an independant section, with options to limit the number of contributors or readers. Each post in a wiki may feature images, photos, files, comments, trackbacks and related links.').'</p>'
			.'<p>'.i18n::s('You can either create one general-purpose wiki, or run this script several time and create a set of more specialised wikis.').'</p>';

		// a form to get section parameters
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>'."\n"
			.'<input type="hidden" name="action" value="wiki" />';
		$fields = array();

		// the anchor
		$label = i18n::s('Wiki anchor');
		$input = '<select name="anchor"><option value="">'.i18n::s('-- Root level')."</option>\n".Sections::get_options('none', NULL).'</select>';
		$hint = i18n::s('Please carefully select a parent section, if any');
		$fields[] = array($label, $input, $hint);

		// the title
		$label = i18n::s('Wiki Title');
		$input = '<textarea id="title" name="title" rows="1" cols="50" accesskey="t">'.encode_field(i18n::c('Our wiki')).'</textarea>';
		$hint = i18n::s('Please provide a meaningful title.');
		$fields[] = array($label, $input, $hint);

		// the introduction
		$label = i18n::s('Introduction');
		$input = '<textarea name="introduction" rows="5" cols="50" accesskey="i">'.encode_field(i18n::c('Our collaborative place')).'</textarea>';
		$hint = i18n::s('Appears at site map, near section title');
		$fields[] = array($label, $input, $hint);

		// the description
		$label = i18n::s('Description');
		$input = '<textarea name="description" rows="4" cols="50">'.encode_field(i18n::c('A description of what information is developed at this wiki.')).'</textarea>';
		$hint = i18n::s('Give a hint to interested people');
		$fields[] = array($label, $input, $hint);

		// the contribution flag: Yes/public, Restricted/logged, No/associates
		$label = i18n::s('Contribution');
		$input = '<input type="radio" name="contribution" value="Y" accesskey="c" checked="checked" /> '.i18n::s('Anyone, including anonymous surfer, may contribute to this wiki.').BR;
		$input .= '<input type="radio" name="contribution" value="R" /> '.i18n::s('Any authenticated member can contribute.').BR;
		$input .= '<input type="radio" name="contribution" value="N" /> '.i18n::s('Only associates and owners can contribute.');
		$fields[] = array($label, $input);

		// the active flag: Yes/public, Restricted/logged, No/associates
		$label = i18n::s('Access');
		$input = '<input type="radio" name="active" value="Y" accesskey="v" checked="checked" /> '.i18n::s('Public - Everybody, including anonymous surfers').BR;
		$input .= '<input type="radio" name="active" value="R" /> '.i18n::s('Community - Access is granted to any identified surfer').BR;
		$input .= '<input type="radio" name="active" value="N" /> '.i18n::s('Private - Access is restricted to selected persons');
		$fields[] = array($label, $input);

		// home panel
		$label = i18n::s('Front page');
		$input = i18n::s('Should content of this section be displayed at the front page?').BR;
		$input .= '<input type="radio" name="index_map" value="Y" checked="checked" /> '.i18n::s('Yes').BR;
		$input .= '<input type="radio" name="index_map" value="N" /> '.i18n::s('No');
		$fields[] = array($label, $input);

		// build the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// the submit button
		$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Add content'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		// append the script used for data checking on the browser
		$context['text'] .= JS_PREFIX
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
			.'// set the focus on first form field'."\n"
			.'$("#title").focus();'."\n"
			.JS_SUFFIX."\n";

	// create a section
	} else {

		$fields = array();
		$fields['anchor'] = $_REQUEST['anchor'];
		$fields['title'] = $_REQUEST['title'];
		$fields['introduction'] = $_REQUEST['introduction'];
		$fields['description'] = $_REQUEST['description'];
		$fields['active_set'] = $_REQUEST['active'];
		$fields['index_map'] = $_REQUEST['index_map'];
		$fields['articles_layout'] = 'tagged'; // the preferred layout for wikis
		$fields['options'] = 'articles_by_title'; // alphabetical order
		$fields['content_options'] = 'view_as_wiki auto_publish edit_as_simple with_export_tools';
		if($_REQUEST['contribution'] == 'Y')		// anyone can contribute
			$fields['content_options'] .= ' anonymous_edit';
		elseif($_REQUEST['contribution'] == 'R')	// only members can contribute
			$fields['content_options'] .= ' members_edit';
		elseif($_REQUEST['contribution'] == 'N')	// only associates and owners can contribute
			$fields['locked'] = 'Y';
		$fields['rank'] = 10000; // default value
		if($fields['id'] = Sections::post($fields)) {

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// splash
			$context['text'] .= '<p>'.i18n::s('Congratulations, you have shared new content.').'</p>';

			// feed-back on contributor scope
			switch($_REQUEST['contribution']) {
			case 'Y':
				$context['text'] .= '<p>'.i18n::s('Any surfer is allowed to contribute anonymously to this wiki. Internet robots are prevented to submit new posts, however you should continuously review content to avoid unappropriate material.').'</p>';
				break;

			case 'R':
				$context['text'] .= '<p>'.i18n::s('Any authenticated member of the community is allowed to contribute to this wiki. You should control community membership to avoid unappropriate material.').'</p>';
				break;

			case 'N':
				$context['text'] .= '<p>'.i18n::s('You have selected to implement a private wiki. Assign individual members as editors of this wiki to enable contributions beyond associates.').'</p>';
				break;

			}

			// follow-up commands
			$follow_up = i18n::s('What do you want to do now?');
			$menu = array();
			$menu = array_merge($menu, array(Sections::get_permalink($fields) => i18n::s('Access the new wiki')));
			if(Surfer::may_upload())
				$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$fields['id']) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('help/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			$follow_up .= Skin::build_list($menu, 'menu_bar');
			$context['text'] .= Skin::build_block($follow_up, 'bottom');

			// new content has been created
			Logger::remember('help/populate.php: content assistant has created new content');

		}
	}

// make the user select an option
} else {

	// the splash message
	$context['text'] .= '<p>'.i18n::s('This script will help to structure content for your server. Please select below the action you would like to perform.Depending on your choice, the assistant may ask for additional parameters on successive panels.').'</p>';

	// the form
	$context['text'] .= '<form method="get" action="'.$context['script_url'].'" id="main_form">'."\n";

	// sollicitate users for feed-back
	$context['text'] .= '<p><input type="radio" name="action" value="petition" /> '.i18n::s('Sollicitate users input -- create a petition, or a poll').'</p>'."\n";

	// create a forum
	$context['text'] .= '<p><input type="radio" name="action" value="forum" /> '.i18n::s('Add a forum -- let people interact').'</p>'."\n";

	// create a blog
	$context['text'] .= '<p><input type="radio" name="action" value="blog" /> '.i18n::s('Add a blog -- and share your feeling, your findings, your soul').'</p>'."\n";

	// create a wiki
	$context['text'] .= '<p><input type="radio" name="action" value="wiki" /> '.i18n::s('Add a wiki -- to support collaborative work in a team of peers').'</p>'."\n";

	// post original work
	$context['text'] .= '<p><input type="radio" name="action" value="original" /> '.i18n::s('Post original work -- in a section that will feature author\'s profiles').'</p>'."\n";

	// create a book
	$context['text'] .= '<p><input type="radio" name="action" value="book" /> '.i18n::s('Add an electronic book, or a manual -- actually, a structured set of pages').'</p>'."\n";

	// create a section for polls
	$context['text'] .= '<p><input type="radio" name="action" value="polls" /> '.i18n::s('Add a section for polls -- the most recent is active; previous polls are still listed').'</p>'."\n";

	// create a section for partners if none exists
	if(!Sections::get('partners'))
		$context['text'] .= '<p><input type="radio" name="action" value="partners" /> '.i18n::s('Add a section for partners -- the special people or sites that are supporting your site').'</p>'."\n";

	// create a cookbook
	$context['text'] .= '<p><input type="radio" name="action" value="recipes" /> '.i18n::s('Add a cookbook -- cooking recipes, the French secret to happiness').'</p>'."\n";

	// create a directory of links
	$context['text'] .= '<p><input type="radio" name="action" value="links" /> '.i18n::s('Add a directory of links -- a small version of Yahoo!, or of DMOZ').'</p>'."\n";

	// create sample server profiles
	$context['text'] .= '<p><input type="radio" name="action" value="servers" /> '.i18n::s('Add sample server profiles -- ping well-known news aggregator and become famous').'</p>'."\n";

	// create basic records
	$context['text'] .= '<p><input type="radio" name="action" value="build" /> '.i18n::s('Add basic items -- in case you would need to replay some steps of the setup process').'</p>'."\n";

	// create a test environment
	if(file_exists($context['path_to_root'].'tools/populate.php'))
		$context['text'] .= '<p><input type="radio" name="action" value="test" /> '.i18n::s('Add sample items -- for test purpose').'</p>'."\n";

	// the submit button
	$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Next step'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// end of the form
	$context['text'] .= '</form>'."\n";

	// the help panel
	$help = '<p>'.i18n::s('Turn any regular section to a photo album by adding images to posted pages.').'</p>'
		.'<p>'.i18n::s('YACS creates weekly and monthly archives automatically. No specific action is required to create these.').'</p>';
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');

	// contribution shortcuts
	if(Surfer::is_member()) {
		$label = '<p>'.i18n::s('Of course, you may also use regular editors to create simple items:')."</p>\n";

		$label .= '<ul>'."\n";

		if(Surfer::is_associate())
			$label .= '<li>'.Skin::build_link('sections/edit.php', i18n::s('Add a section'), 'shortcut').'</li>'."\n"
				.'<li>'.Skin::build_link('categories/edit.php', i18n::s('Add a category'), 'shortcut').'</li>'."\n";

		$label .= '<li> '.Skin::build_link('articles/edit.php', i18n::s('Add a page'), 'shorcut').'</li>'."\n";

		$label .= '</ul>'."\n";

		$context['components']['boxes'] .= Skin::build_box(i18n::s('Shortcuts'), $label, 'boxes');
	}

}

// flush the cache
Cache::clear();

// render the skin
render_skin();

?>
