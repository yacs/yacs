<?php
/**
 * populate the database
 *
 * @todo allow for the creation of a calendar in a section
 * @todo allow for the creation of a s5 presentation
 * @todo allow for the capture of a remote rss feed in a new section
 *
 * This script helps to create records in the database.
 *
 * If there is no [code]parameters/switch.on[/code] nor [code]parameters/switch.off[/code] file, the script
 * asks for a user name and password to create an associate user profile.
 * This is usually what happen on first installation.
 *
 * This can be used also if one webmaster losts his password.
 * In this case, delete the switch file (normally, [code]parameters/switch.on[/code])
 * and trigger this script to recreate a new associate user profile.
 *
 * Else the script populates the database according to the value of the parameter 'action':
 * - 'associate' -- create an associate profile, plus default items for first installation
 * - 'blog' -- create a blog
 * - 'book' -- create a book
 * - 'build' -- create default items, like for first installation
 * - 'collection' -- create a collection to share files
 * - 'composite' -- with scrolling news, plus gadget and extra boxes
 * - 'forum' -- create several structured discussion boards using the yabb layout
 * - 'links' -- create a Yahoo-like directory of links
 * - 'original' -- post original articles
 * - 'partners' - our preferred partners
 * - 'polls' -- create a section for polls
 * - 'recipes' -- create a book of cooking recipes
 * - 'samples' -- create samples items to learn by exemple
 * - 'servers' -- sample hosts to be pinged, etc. ([script]servers/populate.php[/script])
 * - 'vote' -- create one vote, or one poll
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
 * When the parameter 'action' has the value 'samples', this script creates records in following tables:
 * - sections ([script]sections/populate.php[/script]) -- sample sections
 * - categories ([script]categories/populate.php[/script]) -- sample categories
 * - articles ([script]articles/populate.php[/script]) -- sample pages
 * - comments ([script]comments/populate.php[/script]) -- sample comments
 * - tables ([script]tables/populate.php[/script]) -- sample tables
 *
 * When the parameter has the value 'associate', 'build' or 'samples', this script also invokes a hook to populate additional tables:
 * - id: 'control/populate.php'
 * - type: 'include'
 *
 * Only associates can use this script, except if no switch file is present.
 * Invocations are also accepted if the user table is missing or empty.
 *
 * If an associate user profile is created, apply it to the current surfing session.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// include the global declarations
include_once '../shared/global.php';

// include explicitly some libraries
include_once '../categories/categories.php';

// what to do
$action = '';
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];
if(!$action && isset($context['arguments'][0]))
	$action = $context['arguments'][0];
$action = strip_tags($action);

// force the creation of a user profile if the user table does not exists, or is empty
$query = "SELECT count(*) FROM ".SQL::table_name('users');
if(!SQL::query_scalar($query, FALSE, $context['users_connection'])) {
	$permitted = TRUE;
	$action = 'associate';

// force the creation of a user profile if there is no switch file
} elseif(!(file_exists('../parameters/switch.on') || file_exists('../parameters/switch.off'))) {
	$permitted = TRUE;
	$action = 'associate';

// associates can do what they want
} elseif(Surfer::is_associate())
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load localized strings
i18n::bind('control');

// load the skin
load_skin('control');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// default page title
$context['page_title'] = i18n::s('Content Assistant');

// permission denied
if(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('control/populate.php?action='.$action));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// create one associate profile
} elseif($action == 'associate') {

	// ask for parameters
	if(!isset($_REQUEST['nick_name']) || !$_REQUEST['nick_name']) {

		// page title
		$context['page_title'] = i18n::s('New associate');

		// splash message
		$context['text'] .= '<p>'.i18n::s('Please indicate below the name and password you will use to authenticate to this server.').'</p>'
			.'<p>'.i18n::s('DO NOT FORGET THIS LOGIN!! There is no default administrative login created when YACS is installed, so if you lose your login, you will have to purge the database and trigger the setup script again.').'</p>'."\n";

		// the form to get user attributes
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" name="main_form"><div>'."\n"
			.'<input type="hidden" name="action" value="associate"'.EOT;

		// the nick name
		$label = i18n::s('Nick name');
		$input = '<input type="text" id="nick_name" name="nick_name" size="40" />';
		$hint = i18n::s('Please carefully select a meaningful nick name.');
		$fields[] = array($label, $input, $hint);

		// the password
		$label = i18n::s('Password');
		$input = '<input type="password" name="password" size="20" />';
		$hint = i18n::s('We recommend at least 4 letters, two digits, and a punctuation sign - in any order');
		$fields[] = array($label, $input, $hint);

		// the password has to be repeated for confirmation
		$label = i18n::s('Password confirmation');
		$input = '<input type="password" name="confirm" size="20" />';
		$fields[] = array($label, $input);

		// build the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// the submit button
		$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		// append the script used for data checking on the browser
		$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
			.'// check that main fields are not empty'."\n"
			.'func'.'tion validateDocumentPost(container) {'."\n"
			."\n"
			.'	// name is mandatory'."\n"
			.'	if(!container.nick_name.value) {'."\n"
			.'		alert("'.i18n::s('You must set a name to this user.').'");'."\n"
			.'		Yacs.stopWorking();'."\n"
			.'		return false;'."\n"
			.'	}'."\n"
			."\n"
			.'	// password is mandatory'."\n"
			.'	if(!container.password.value) {'."\n"
			.'		alert("'.i18n::s('You must type a password for this user.').'");'."\n"
			.'		Yacs.stopWorking();'."\n"
			.'		return false;'."\n"
			.'	}'."\n"
			."\n"
			.'	// password should be confirmed'."\n"
			.'	if(container.password.value != container.confirm.value) {'."\n"
			.'		alert("'.i18n::s('You must confirm the password.').'");'."\n"
			.'		Yacs.stopWorking();'."\n"
			.'		return false;'."\n"
			.'	}'."\n"
			."\n"
			.'	// successful check'."\n"
			.'	return true;'."\n"
			.'}'."\n"
			."\n"
			.'// set the focus on first form field'."\n"
			.'document.getElementById("nick_name").focus();'."\n"
			.'// ]]></script>';

		// this may take some time
		$context['text'] .= '<p>'.i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.')."</p>\n";

	// actual data creation
	} else {

		// page title
		$context['page_title'] = i18n::s('Add basic items');

		// on first installation
		if(!file_exists('../parameters/switch.on') && !file_exists('../parameters/switch.off'))
			$context['text'] .= '<p>'.i18n::s('Review provided information and go to the bottom of the page to move forward.')."</a></p>\n";

		// create an associate user account
		if(isset($_REQUEST['nick_name']))
			$user['nick_name']	= $_REQUEST['nick_name'];
		else
			$user['nick_name']	= 'admin';
		if(isset($_REQUEST['password'])) {
			$user['password']	= $_REQUEST['password'];
			$user['confirm']	= $_REQUEST['confirm'];
		} else {
			list($usec, $sec) = explode(' ', microtime(), 2);
			srand((float) $sec + ((float) $usec * 100000));
			$user['password']	= 'az'.rand(1000, 9999);
			$user['confirm']	= $user['password'];
		}
		$user['with_newsletters'] = 'Y';
		$user['capability'] = 'A';	// make this user profile an associate
		$user['active'] 	= 'Y';
		$user['create_name'] = 'setup';
		$user['create_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		$user['edit_name']	= 'setup';
		$user['edit_date']	= gmstrftime('%Y-%m-%d %H:%M:%S');
		$user['login_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');

		// display error, if any
		if(!Users::post($user)) {

			// but do not mention that admin already exists...
			if($user['nick_name'] == 'admin')
				$context['error'] = array();

		// remember the new user profile
		} elseif($user =& Users::get($_REQUEST['nick_name'])) {

			// we will create additional items on first installation
			if(!file_exists('../parameters/switch.on') && !file_exists('../parameters/switch.off'))
				$context['text'] .= Skin::build_block(i18n::s('Users'), 'subtitle');

			$context['text'] .= '<p>'.sprintf(i18n::s('Congratulations, one associate profile %s has been created with the password %s'), $user['nick_name'], $_REQUEST['password'])."</p>\n";

		}

		// on first installation
		if(!file_exists('../parameters/switch.on') && !file_exists('../parameters/switch.off')) {

			// impersonate the new created user profile
			if(isset($user['id']))
				Surfer::set($user);

			// also initialize the database -- exact copy of what is achieved for the 'build' command
			function send_body() {
				global $context;

				// populate tables for sections
				echo Skin::build_block(i18n::s('Sections'), 'subtitle');
				include_once '../sections/populate.php';

				// populate tables for categories
				echo Skin::build_block(i18n::s('Categories'), 'subtitle');
				include_once '../categories/populate.php';

				// populate tables for articles
				echo Skin::build_block(i18n::s('Articles'), 'subtitle');
				include_once '../articles/populate.php';

				// the populate hook
				if(is_callable(array('Hooks', 'include_scripts')))
					echo Hooks::include_scripts('control/populate.php');

				// configure the interface on first installation
				if(!file_exists('../parameters/switch.on') && !file_exists('../parameters/switch.off')) {
					echo '<form method="get" action="../skins/configure.php">'."\n"
						.'<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Configure the page factory')).'</p>'."\n"
						.'</form>'."\n";

				// or back to the control panel
				} else {
					$menu = array('control/' => i18n::s('Control Panel'));
					echo Skin::build_list($menu, 'menu_bar');
				}

			}

			// an associate account has been created
			Logger::remember('control/populate.php', 'database has been populated');

		// ordinary follow-up
		} else {

			// splash
			$context['text'] .= '<h3>'.i18n::s('What do you want to do now?').'</h3>';

			// follow-up commands
			$menu = array();
			if(isset($user['id']))
				$menu = array_merge($menu, array(Users::get_url($user['id']) => i18n::s('Check the new associate profile')));
			$menu = array_merge($menu, array('users/select_avatar.php?id='.urlencode($user['id']) => i18n::s('Select an avatar')));
			$menu = array_merge($menu, array('control/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			$context['text'] .= Skin::build_list($menu, 'menu_bar');

			// an associate account has been created
			Logger::remember('control/populate.php', 'an associate account has been created');
		}
	}

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
			.'<input type="hidden" name="action" value="blog"'.EOT;

		// the anchor
		$label = i18n::s('Blog anchor');
		$input = '<select name="anchor">'.'<option value="">'.i18n::s('-- Root level')."</option>\n".Sections::get_options('none', NULL).'</select>';
		$hint = i18n::s('Please carefully select a parent section, if any');
		$fields[] = array($label, $input, $hint);

		// the title
		$label = i18n::s('Blog Title');
		$input = '<textarea id="title" name="title" rows="1" cols="50" accesskey="t">'.encode_field(i18n::c('My Blog')).'</textarea>';
		$hint = i18n::s('Please provide a meaningful title.');
		$fields[] = array($label, $input, $hint);

		// the introduction
		$label = i18n::s('Introduction');
		$input = '<textarea name="introduction" rows="2" cols="50" accesskey="i">'.encode_field(i18n::c('The best place to express myself')).'</textarea>';
		$hint = i18n::s('Appears at site map, near section title');
		$fields[] = array($label, $input, $hint);

		// the description
		$label = i18n::s('Description');
		$input = '<textarea name="description" rows="4" cols="50">'.encode_field(i18n::c('A description of what this blog is about.')).'</textarea>';
		$hint = i18n::s('Give a hint to interested people');
		$fields[] = array($label, $input, $hint);

		// the contribution flag: Yes/public, Restricted/logged, No/associates
		$label = i18n::s('Contribution');
		$input = '<input type="radio" name="contribution" value="N" accesskey="c" checked="checked"'.EOT.' '.i18n::s('Only associates and editors can contribute.').BR;
		$input .= '<input type="radio" name="contribution" value="R"'.EOT.' '.i18n::s('Any authenticated member can contribute.');
		$fields[] = array($label, $input);

		// the active flag: Yes/public, Restricted/logged, No/associates
		$label = i18n::s('Visibility');
		$input = '<input type="radio" name="active" value="Y" accesskey="v" checked="checked"'.EOT.' '.i18n::s('Anyone may read pages posted here').BR;
		$input .= '<input type="radio" name="active" value="R"'.EOT.' '.i18n::s('Access is restricted to authenticated members').BR;
		$input .= '<input type="radio" name="active" value="N"'.EOT.' '.i18n::s('Access is restricted to associates and editors');
		$fields[] = array($label, $input);

		// home panel
		$label = i18n::s('Front page');
		$input = i18n::s('Posts to this blog should be:').BR;
		$input .= '<input type="radio" name="home_panel" value="main" checked="checked"'.EOT.' '.i18n::s('displayed in the main panel, as usual').BR;
		$input .= '<input type="radio" name="home_panel" value="gadget"'.EOT.' '.i18n::s('listed in the main panel, in a gadget box').BR;
		$input .= '<input type="radio" name="home_panel" value="extra"'.EOT.' '.i18n::s('listed at page side, in an extra box').BR;
		$input .= '<input type="radio" name="home_panel" value="none"'.EOT.' '.i18n::s('not displayed at the front page');
		$fields[] = array($label, $input);

		// build the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// the submit button
		$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Add content'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		// append the script used for data checking on the browser
		$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
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
			.'document.getElementById("title").focus();'."\n"
			.'// ]]></script>'."\n";

	// create a section
	} else {

		$fields = array();
		$fields['anchor'] = $_REQUEST['anchor'];
		$fields['title'] = $_REQUEST['title'];
		$fields['introduction'] = $_REQUEST['introduction'];
		$fields['description'] = $_REQUEST['description'];
		$fields['active_set'] = $_REQUEST['active'];
		$fields['home_panel'] = $_REQUEST['home_panel'];
		$fields['index_map'] = 'Y'; // listed with ordinary sections
		$fields['articles_layout'] = 'daily'; // the preferred layout for blogs
		$fields['content_options'] = 'with_extra_profile'; // show user profiles in a side panel
		if($_REQUEST['contribution'] == 'N')	// only associates and editors can contribute
			$fields['locked'] = 'Y';
		$fields['rank'] = 10000; // default value
		if($new_id = Sections::post($fields)) {

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// splash
			$context['text'] .= '<p>'.i18n::s('Congratulations, one blog has been added to your site.').'</p>';

			// feed-back on contributor scope
			switch($_REQUEST['contribution']) {
			case 'N':
				$context['text'] .= '<p>'.i18n::s('Assign at least one member as editor of this blog to enable contributions. Please remind that associates can always contribute at any place.').'</p>';
				break;

			case 'R':
				$context['text'] .= '<p>'.i18n::s('Any authenticated member of the community is allowed to contribute to this blog. You should control community membership to avoid unappropriate material.').'</p>';
				break;

			}

			// follow-up
			$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';

			// follow-up commands
			$menu = array();
			$menu = array_merge($menu, array(Sections::get_url($new_id) => i18n::s('Access the new blog')));
			if(Surfer::may_upload())
				$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$new_id) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('control/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			$context['text'] .= Skin::build_list($menu, 'menu_bar');

			// new content has been created
			Logger::remember('control/populate.php', 'content assistant has created new content');

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
			.'<input type="hidden" name="action" value="book"'.EOT;

		// the anchor
		$label = i18n::s('Book anchor');
		$input = '<select name="anchor">'.'<option value="">'.i18n::s('-- Root level')."</option>\n".Sections::get_options('none', NULL).'</select>';
		$hint = i18n::s('Please carefully select a parent section, if any');
		$fields[] = array($label, $input, $hint);

		// the title
		$label = i18n::s('Book Title');
		$input = '<textarea id="title" name="title" rows="1" cols="50" accesskey="t">'.encode_field(i18n::c('User Manual')).'</textarea>';
		$hint = i18n::s('Please provide a meaningful title.');
		$fields[] = array($label, $input, $hint);

		// the introduction
		$label = i18n::s('Introduction');
		$input = '<textarea name="introduction" rows="2" cols="50" accesskey="i">'.encode_field(i18n::c('With reader notes')).'</textarea>';
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
			$input = '<input type="text" name="titles[]" size="50" value=""'.EOT;
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
		$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
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
			.'document.getElementById("title").focus();'."\n"
			.'// ]]></script>'."\n";

	// create a section
	} else {

		$fields = array();
		$fields['anchor'] = $_REQUEST['anchor'];
		$fields['title'] = $_REQUEST['title'];
		$fields['introduction'] = $_REQUEST['introduction'];
		$fields['description'] = $_REQUEST['description'];
		$fields['home_panel'] = 'none'; // new pages are not pushed at the front page
		$fields['index_map'] = 'Y'; // listed with ordinary sections
		$fields['index_panel'] = 'none'; // new pages are not pushed at the parent section index
		$fields['articles_layout'] = 'manual'; // the preferred layout for books
		$fields['sections_layout'] = 'inline'; // the preferred layout for books
		$fields['options'] = 'articles_by_title'; // preserve page ordering over time
		$fields['content_options'] = 'with_rating, with_bottom_tools'; // let surfers rate pages and convert pages
		$fields['rank'] = 10000; // default value
		if($new_id = Sections::post($fields)) {

			// create new sub-sections
			for($index = 0; $index < count($_REQUEST['titles']); $index++) {

				$fields = array();
				$fields['anchor'] = 'section:'.$new_id;
				$fields['title'] = $_REQUEST['titles'][$index];
				$fields['introduction'] = $_REQUEST['introductions'][$index];
				$fields['home_panel'] = 'none'; // not pushed to the front page
				$fields['index_map'] = 'Y'; // listed with ordinary sections
				$fields['index_panel'] = 'none'; // not listed at parent section
				$fields['articles_layout'] = 'manual'; // the preferred layout for books
				$fields['sections_layout'] = 'inline'; // the preferred layout for books
				$fields['options'] = 'articles_by_title'; // preserve page ordering over time
				$fields['content_options'] = 'with_rating, with_bottom_tools'; // let surfers rate pages and convert pages
				$fields['rank'] = ($index+1); //  preserve order
				if($fields['title'])
					Sections::post($fields);
			}

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// splash
			$context['text'] .= '<p>'.i18n::s('Congratulations, one electronic book has been added to your site.').'</p>';

			// follow-up commands
			$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';
			$menu = array();
			$menu = array_merge($menu, array(Sections::get_url($new_id) => i18n::s('Access the new book')));
			if(Surfer::may_upload())
				$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$new_id) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('control/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			$context['text'] .= Skin::build_list($menu, 'menu_bar');

			// new content has been created
			Logger::remember('control/populate.php', 'content assistant has created new content');

		}
	}

// initialize the database with sample data
} elseif($action == 'build') {

	/**
	 * dynamically generate the page
	 *
	 * @see skins/index.php
	 */
	function send_body() {
		global $context;

		// first installation
		if(!file_exists('../parameters/switch.on') && !file_exists('../parameters/switch.off'))
			echo '<p>'.i18n::s('Review provided information and go to the bottom of the page to move forward.')."</a></p>\n";

		// populate tables for sections
		echo Skin::build_block(i18n::s('Sections'), 'subtitle');
		include_once '../sections/populate.php';

		// populate tables for categories
		echo Skin::build_block(i18n::s('Categories'), 'subtitle');
		include_once '../categories/populate.php';

		// populate tables for articles
		echo Skin::build_block(i18n::s('Articles'), 'subtitle');
		include_once '../articles/populate.php';

		// the populate hook
		if(is_callable(array('Hooks', 'include_scripts')))
			echo Hooks::include_scripts('control/populate.php');

		// configure the interface on first installation
		if(!file_exists('../parameters/switch.on') && !file_exists('../parameters/switch.off')) {
			echo '<form method="get" action="../skins/configure.php">'."\n"
				.'<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Configure the page factory')).'</p>'."\n"
				.'</form>'."\n";

		// or back to the control panel
		} else {

			// splash
			echo '<h3>'.i18n::s('What do you want to do now?').'</h3>';

			// follow-up commands
			$menu = array();
			$menu = array_merge($menu, array('sections/' => i18n::s('Check the updated Site Map')));
			$menu = array_merge($menu, array('control/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			echo Skin::build_list($menu, 'menu_bar');

		}

		// new content has been created
		Logger::remember('control/populate.php', 'content assistant has created new content');

	}

// create a collection
} elseif($action == 'collection') {

	// page title
	$context['page_title'] = i18n::s('Add a collection');

	// get collection parameters
	if(!isset($_REQUEST['name']) || !$_REQUEST['name']) {

		// splash
		$context['text'] .= '<p>'.i18n::s('YACS may turn your web site to a straightforward media server.').'</p>'
			.'<p>'.i18n::s('Define a collection to publicly share some directory of the server hard drive. Then YACS will build nice index pages to list available files, and allow for immediate download. Where applicable, files will also be streamed to workstations through automatic playlists.').'</p>';

		// if some collections has already been defined, use the dedicated configuration panel
		Safe::load('parameters/collections.include.php');
		if(@count($context['collections'])) {

			$context['text'] .= '<p>'.sprintf(i18n::s('This assistant is aiming to help you create your very first collection. Since some collection has already been defined, please use %s and add a new definition.'), Skin::build_link('collections/configure.php', i18n::s('the dedicated configuration panel'), 'shortcut')).'</p>';

		// or create a configuration file from scratch
		} else {

			// a form to get collection parameters
			$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>'."\n"
				.'<input type="hidden" name="action" value="collection"'.EOT;

			// the name
			$label = i18n::s('Collection nick name');
			$input = '<input type="text" id="name" name="name" size="32" value="'.encode_field(i18n::c('my_collection')).'" maxlength="32" />';
			$hint = i18n::s('Prepended to the path of each file of the collection. Short and meaningful');
			$fields[] = array($label, $input, $hint);

			// the title
			$label = i18n::s('Label');
			$input = '<input type="text" name="title" size="45" value="'.encode_field(i18n::c('My Collection')).'" maxlength="255" />';
			$hint = i18n::s('Used at index pages, and at the top of each page generated for this collection');
			$fields[] = array($label, $input, $hint);

			// the path
			$label = i18n::s('Path prefix');
			$input = '<input type="text" name="path" size="45" value="c:" maxlength="255" />';
			$hint = sprintf(i18n::s('Local access to files; YACS installation directory is at "%s"'), $context['path_to_root']);
			$fields[] = array($label, $input, $hint);

			// the url
			$label = i18n::s('URL prefix');
			$input = '<input type="text" name="url" size="45" value="'.encode_field($context['url_to_home']).'" maxlength="255" />';
			$hint = i18n::s('The ftp:// or http:// address used to access the collection, pointing to the same place than the path prefix');
			$fields[] = array($label, $input, $hint);

			// introduction
			$label = i18n::s('Introduction');
			$input = '<textarea name="introduction" cols="40" rows="2">'.encode_field(i18n::c('Public files to download')).'</textarea>';
			$hint = i18n::s('To be used at the front page and on the collections index page');
			$fields[] = array($label, $input, $hint);

			// description
			$label = i18n::s('Description');
			$input = '<textarea name="description" cols="40" rows="3">'.encode_field(i18n::c('Click on file names to transfer them to your workstation, or to start a Video-on-demand session.')).'</textarea>';
			$hint = i18n::s('To be inserted in the index page of this collection');
			$fields[] = array($label, $input, $hint);

			// build the form
			$context['text'] .= Skin::build_form($fields);
			$fields = array();

			// the submit button
			$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Add content'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

			// end of the form
			$context['text'] .= '</div></form>';

			// append the script used for data checking on the browser
			$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
				.'// check that main fields are not empty'."\n"
				.'func'.'tion validateDocumentPost(container) {'."\n"
				."\n"
				.'	// name is mandatory'."\n"
				.'	if(!container.name.value) {'."\n"
				.'		alert("'.i18n::s('You must name this collection.').'");'."\n"
				.'		Yacs.stopWorking();'."\n"
				.'		return false;'."\n"
				.'	}'."\n"
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
				.'document.getElementById("name").focus();'."\n"
				.'// ]]></script>'."\n";

		}

	// create a collection
	} else {

		// build the new configuration file
		$content = '<?php'."\n"
			.'// This file has been created by the configuration script control/populate.php'."\n"
			.'// on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'. Please do not modify it manually.'."\n"
			.'global $context;'."\n";
		$name	= addcslashes($_REQUEST['name'], "\\'");
		$title	= addcslashes($_REQUEST['title'], "\\'");
		$path	= addcslashes($_REQUEST['path'], "\\'");
		$url	= addcslashes($_REQUEST['url'], "\\'");
		$introduction	= addcslashes($_REQUEST['introduction'], "\\'");
		$description	= addcslashes($_REQUEST['description'], "\\'");
		if($name && $path && $url) {
			$content .= '$context[\'collections\'][\''.$name.'\']=array(\''.$title.'\', \''
				.$path.'\', \''.$url.'\', \''
				.$introduction.'\', \''.$description.'\', \'\', \'\', \'Y\');'."\n";
		}
		$content .= '?>'."\n";

		// update the parameters file
		if(!Safe::file_put_contents('parameters/collections.include.php', $content)) {

			Skin::error(sprintf(i18n::s('ERROR: Impossible to write to the file %s. The configuration has not been saved.'), 'parameters/collections.include.php'));

			// allow for a manual update
			$context['text'] .= '<p style="text-decoration: blink;">'.sprintf(i18n::s('To actually change the configuration, please copy and paste following lines by yourself in file %s.'), 'parameters/collections.include.php')."</p>\n";

			// display updated parameters
			$context['text'] .= Skin::build_box(i18n::s('Configuration parameters'), Safe::highlight_string($content), 'folder');

		// job done
		} else {

			// purge the cache
			Cache::clear();

			// remember the change
			$label = sprintf(i18n::c('%s has been updated'), 'parameters/collections.include.php');
			$description = $context['url_to_home'].$context['url_to_root'].'collections/configure.php';
			Logger::remember('control/populate.php', $label, $description);

			// splash
			$context['text'] .= '<p>'.i18n::s('Congratulations, one collection has been added to your site.').'</p>';

			// follow-up commands
			$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';
			$menu = array();
			$menu = array_merge($menu, array('collections/browse.php?path='.urlencode($_REQUEST['name']) => i18n::s('Access the new collection')));
			$menu = array_merge($menu, array('collections/' => i18n::s('File collections')));
			$menu = array_merge($menu, array('control/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			$context['text'] .= Skin::build_list($menu, 'menu_bar');

		}
	}

// create a composite section
} elseif($action == 'composite') {

	// page title
	$context['page_title'] = i18n::s('Add a section');

	// get parameters
	if(!isset($_REQUEST['main_title']) || !$_REQUEST['main_title']) {

		// splash
		$context['text'] .= '<p>'.i18n::s('YACS allows for composite section index pages.').'</p>'
			.'<p>'.i18n::s('Use this assistant to create one main section, plus companion components, to better suit your needs. Of course, sections can be changed, deleted or added individually afterwards.').'</p>';

		// a form to get section parameters
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>'."\n"
			.'<input type="hidden" name="action" value="composite"'.EOT;

		// section title
		$context['text'] .= Skin::build_block(i18n::s('Section'), 'title');

		// the anchor
		$label = i18n::s('Section anchor');
		$input = '<select name="anchor">'.'<option value="">'.i18n::s('-- Root level')."</option>\n".Sections::get_options('none', NULL).'</select>';
		$hint = i18n::s('Please carefully select a parent section, if any');
		$fields[] = array($label, $input, $hint);

		// the title
		$label = i18n::s('Section title');
		$input = '<input type="text" id="main_title" name="main_title" size="50" accesskey="t" value="'.encode_field($item['title']).'"'.EOT;
		$hint = i18n::s('Please provide a meaningful title.');
		$fields[] = array($label, $input, $hint);

		// the introduction
		$label = i18n::s('Introduction');
		$input = '<textarea name="main_introduction" rows="2" cols="50" accesskey="i">'.encode_field($item['introduction']).'</textarea>';
		$hint = i18n::s('Appears at site map, near section title');
		$fields[] = array($label, $input, $hint);

		// the description
		$label = i18n::s('Description');
		$input = '<textarea name="main_description" rows="4" cols="50" >'.encode_field($item['description']).'</textarea>';
		$hint = i18n::s('Appears at the section index page');
		$fields[] = array($label, $input, $hint);

		// update the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// companions sections
		$context['text'] .= Skin::build_block(i18n::s('Companion sections'), 'title');

		$context['text'] .= '<p>'.i18n::s('Add one or several specialized sub-sections, depending of your requirements. It is safe to create all suggested sections, even if you do not need them right now.')."</p>\n";

		// scrolling news
		$context['text'] .= Skin::build_block(i18n::s('Side news'), 'subtitle');

		$input = '<input type="checkbox" name="news_check" checked="checked"'.EOT.' '.i18n::s('Add a companion section to post articles that will appear in the news panel of the parent section');
		$context['text'] .= '<p>'.$input.'</p>';

		$label = i18n::s('Title');
		$input = '<input type="text" name="news_title" size="50" value="'.encode_field(i18n::c('Flashy news')).'"'.EOT;
		$fields[] = array($label, $input);

		$label = i18n::s('Introduction');
		$input = '<textarea name="news_introduction" rows="2" cols="50">'.encode_field(i18n::c('Post here articles that will appear in the news panel of the parent section')).'</textarea>';
		$fields[] = array($label, $input);

		// update the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// gadget boxes
		$context['text'] .= Skin::build_block(i18n::s('Gadget boxes'), 'subtitle');

		$input = '<input type="checkbox" name="gadget_check" checked="checked"'.EOT.' '.i18n::s('Add a companion section to post articles that will appear in individual gadget boxes');
		$context['text'] .= '<p>'.$input.'</p>';

		$label = i18n::s('Title');
		$input = '<input type="text" name="gadget_title" size="50" value="'.encode_field(i18n::c('Gadget boxes')).'"'.EOT;
		$fields[] = array($label, $input);

		$label = i18n::s('Introduction');
		$input = '<textarea name="gadget_introduction" rows="2" cols="50">'.encode_field(i18n::c('Post here articles that will appear in individual gadget boxes, in the middle of the index page of the parent section')).'</textarea>';
		$fields[] = array($label, $input);

		// update the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// extra boxes
		$context['text'] .= Skin::build_block(i18n::s('Extra boxes'), 'subtitle');

		$input = '<input type="checkbox" name="extra_check" checked="checked"'.EOT.' '.i18n::s('Add a companion section to post articles that will appear in individual extra boxes');
		$context['text'] .= '<p>'.$input.'</p>';

		$label = i18n::s('Title');
		$input = '<input type="text" name="extra_title" size="50" value="'.encode_field(i18n::c('Extra boxes')).'"'.EOT;
		$fields[] = array($label, $input);

		$label = i18n::s('Introduction');
		$input = '<textarea name="extra_introduction" rows="2" cols="50">'.encode_field(i18n::c('Post here articles that will appear in individual extra boxes, aside the index of the parent section')).'</textarea>';
		$fields[] = array($label, $input);

		// update the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// next step
		$context['text'] .= Skin::build_block(i18n::s('Next step'), 'title');

		// the submit button
		$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Add content'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		// append the script used for data checking on the browser
		$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
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
			.'document.getElementById("main_title").focus();'."\n"
			.'// ]]></script>'."\n";

	// create the stuff
	} else {

		// update the main section
		$item = array();
		$item['anchor'] = $_REQUEST['anchor'];
		$item['title'] = $_REQUEST['main_title'];
		$item['introduction'] = $_REQUEST['main_introduction'];
		$item['description'] = $_REQUEST['main_description'];
		$item['id'] = Sections::post($item);

		// news section
		if($_REQUEST['news_check']) {

			$section = array();
			$section['anchor'] = 'section:'.$item['id'];
			$section['title'] = $_REQUEST['news_title'];
			$section['introduction'] = $_REQUEST['news_introduction'];
			$section['sections_layout'] = 'none';
			$section['articles_layout'] = 'decorated';
			$section['index_panel'] = 'scroller';
			$section['home_panel'] = 'none';	// new pages are not pushed at the front page
			if($section['title'])
				$section['id'] = Sections::post($section);

			// add one sample article
			if($section['id']) {
				$article = array();
				$article['anchor'] = 'section:'.$section['id'];
				$article['title'] = i18n::c('sample');
				$article['description'] = i18n::c('This is a sample scrolling news.');
				$article['id'] = Articles::post($article);
			}

		}

		// gadget section
		if($_REQUEST['gadget_check']) {

			$section = array();
			$section['anchor'] = 'section:'.$item['id'];
			$section['title'] = $_REQUEST['gadget_title'];
			$section['introduction'] = $_REQUEST['gadget_introduction'];
			$section['sections_layout'] = 'none';
			$section['articles_layout'] = 'decorated';
			$section['index_panel'] = 'gadget_boxes';
			$section['home_panel'] = 'none';	// new pages are not pushed at the front page
			if($section['title'])
				$section['id'] = Sections::post($section);

			// add one sample article
			if($section['id']) {
				$article = array();
				$article['anchor'] = 'section:'.$section['id'];
				$article['title'] = i18n::c('Gadget box');
				$article['description'] = i18n::c('This is a sample gadget box.');
				$article['id'] = Articles::post($article);
			}

		}

		// extra section
		if($_REQUEST['extra_check']) {

			$section = array();
			$section['anchor'] = 'section:'.$item['id'];
			$section['title'] = $_REQUEST['extra_title'];
			$section['introduction'] = $_REQUEST['extra_introduction'];
			$section['sections_layout'] = 'none';
			$section['articles_layout'] = 'decorated';
			$section['index_panel'] = 'extra_boxes';
			$section['home_panel'] = 'none';	// new pages are not pushed at the front page
			if($section['title'])
				$section['id'] = Sections::post($section);

			// add one sample article
			if($section['id']) {
				$article = array();
				$article['anchor'] = 'section:'.$section['id'];
				$article['title'] = i18n::c('Sample box');
				$article['description'] = i18n::c('This is a sample extra box.');
				$article['id'] = Articles::post($article);
			}

		}

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// splash
		$context['text'] .= '<p>'.i18n::s('Congratulations, a new feature-rich section has been added to your site.').'</p>';

		// follow-up commands
		$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';
		$menu = array();
		$menu = array_merge($menu, array(Sections::get_url($item['id']) => i18n::s('Access the new section')));
		$menu = array_merge($menu, array('control/populate.php' => i18n::s('Launch the Content Assistant again')));
		$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

		// new content has been created
		Logger::remember('control/populate.php', 'content assistant has created new content');

	}

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
			.'<input type="hidden" name="action" value="forum"'.EOT;

		// the anchor
		$label = i18n::s('Forum anchor');
		$input = '<select name="anchor">'.'<option value="">'.i18n::s('-- Root level')."</option>\n".Sections::get_options('none', NULL).'</select>';
		$hint = i18n::s('Please carefully select a parent section, if any');
		$fields[] = array($label, $input, $hint);

		// the title
		$label = i18n::s('Forum Title');
		$input = '<textarea id="title" name="title" rows="1" cols="50" accesskey="t">'.encode_field(i18n::c('Forum')).'</textarea>';
		$hint = i18n::s('Please provide a meaningful title.');
		$fields[] = array($label, $input, $hint);

		// the introduction
		$label = i18n::s('Introduction');
		$input = '<textarea name="introduction" rows="2" cols="50" accesskey="i">'.encode_field(i18n::c('Post your brand new ideas here!')).'</textarea>';
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
			$input = '<input type="text" name="families[]" size="50" value=""'.EOT;
			$fields[] = array($label, $input, i18n::s('Repeat the same string in successive boards that have to be grouped in the forum.'));

			$label = i18n::s('Title');
			$input = '<input type="text" name="titles[]" size="50" value=""'.EOT;
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
		$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
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
			.'document.getElementById("title").focus();'."\n"
			.'// ]]></script>'."\n";

	// create a section
	} else {

		$fields = array();
		$fields['anchor'] = $_REQUEST['anchor'];
		$fields['title'] = $_REQUEST['title'];
		$fields['introduction'] = $_REQUEST['introduction'];
		$fields['description'] = $_REQUEST['description'];
		$fields['home_panel'] = 'none'; // not pushed to the front page
		$fields['index_map'] = 'Y'; // listed with ordinary sections
		$fields['index_panel'] = 'none'; // not listed at parent section
		$fields['articles_layout'] = 'yabb'; // the preferred layout for discussion boards
		$fields['sections_layout'] = 'yabb'; // the preferred layout for a forum
		$fields['content_options'] = 'auto_publish, with_prefix_profile'; // control is a posteriori; show poster avatar, if any
		$fields['locked'] = 'Y'; // post in discussion boards
		if($new_id = Sections::post($fields)) {

			// create new sub-sections
			for($index = 0; $index < count($_REQUEST['titles']); $index++) {

				$fields = array();
				$fields['anchor'] = 'section:'.$new_id;
				$fields['family'] = $_REQUEST['families'][$index];
				$fields['title'] = $_REQUEST['titles'][$index];
				$fields['introduction'] = $_REQUEST['introductions'][$index];
				$fields['home_panel'] = 'none'; // not pushed to the front page
				$fields['index_map'] = 'Y'; // listed with ordinary sections
				$fields['index_panel'] = 'none'; // not listed at parent section
				$fields['articles_layout'] = 'yabb'; // the preferred layout for discussion boards
				$fields['sections_layout'] = 'yabb'; // the preferred layout for a forum
				$fields['content_options'] = 'auto_publish, with_prefix_profile'; // control is a posteriori; show poster avatar, if any
				$fields['rank'] = ($index+1); //  preserve order
				if($fields['title'])
					Sections::post($fields);
			}

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// splash
			$context['text'] .= '<p>'.i18n::s('Congratulations, one forum has been added to your site.').'</p>';

			// follow-up commands
			$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';
			$menu = array();
			$menu = array_merge($menu, array(Sections::get_url($new_id) => i18n::s('Check the new forum')));
			if(Surfer::may_upload())
				$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$new_id) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('control/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			$context['text'] .= Skin::build_list($menu, 'menu_bar');

		}

		// new content has been created
		Logger::remember('control/populate.php', 'content assistant has created new content');

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
		$item['description'] = i18n::c('This section, and related sub-sections, lists links submitted by authenticated members.');
		$item['sections_layout'] = 'yahoo';
		$item['locked'] = 'Y';
		if($new_id = Sections::post($item))
			$item['id'] = $new_id;

	}

	// get parameters
	if(!isset($_REQUEST['main_title']) || !$_REQUEST['main_title']) {

		// splash
		$context['text'] .= '<p>'.i18n::s('YACS lets people share bookmarks and favorites quite easily.').'</p>'
			.'<p>'.i18n::s('Use this assistant to initialize the tree of sections used to classify links at your site. Of course, sections can be changed, deleted or added individually afterwards.').'</p>';

		// a form to get section parameters
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>'."\n"
			.'<input type="hidden" name="action" value="links"'.EOT;

		// top category
		$context['text'] .= Skin::build_block(i18n::s('Top section'), 'title');

		// the title
		$label = i18n::s('Section title');
		$input = '<input type="text" id="main_title" name="main_title" size="50" accesskey="t" value="'.encode_field($item['title']).'"'.EOT;
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
			$input = '<input type="text" name="titles[]" size="50" value=""'.EOT;
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
		$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
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
			.'document.getElementById("main_title").focus();'."\n"
			.'// ]]></script>'."\n";

	// create the stuff
	} else {

		// update the main section
		$item['title'] = $_REQUEST['main_title'];
		$item['introduction'] = $_REQUEST['main_introduction'];
		$item['description'] = $_REQUEST['main_description'];
		if($error = Sections::put($item))
			Skin::error($error);

		// create new sections
		for($index = 0; $index < count($_REQUEST['titles']); $index++) {

			$fields = array();
			$fields['anchor'] = 'section:'.$item['id'];
			$fields['title'] = $_REQUEST['titles'][$index];
			$fields['introduction'] = $_REQUEST['introductions'][$index];
			$fields['articles_layout'] = 'none'; // no articles here
			$fields['options'] = 'with_links';
			if($fields['title'])
				Sections::post($fields);
		}

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// splash
		$context['text'] .= '<p>'.i18n::s('Congratulations, your site has been structured to host a directory of web links. Review each section individually to enhance it, to add images, etc.').'</p>';

		// follow-up commands
		$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';
		$menu = array();
		$menu = array_merge($menu, array(Sections::get_url('bookmarks') => i18n::s('Access the directory')));
		$menu = array_merge($menu, array('control/populate.php' => i18n::s('Launch the Content Assistant again')));
		$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

		// new content has been created
		Logger::remember('control/populate.php', 'content assistant has created new content');

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
			.'<input type="hidden" name="action" value="original"'.EOT;

		// the anchor
		$label = i18n::s('Section anchor');
		$input = '<select name="anchor">'.'<option value="">'.i18n::s('-- Root level')."</option>\n".Sections::get_options('none', NULL).'</select>';
		$hint = i18n::s('Please carefully select a parent section, if any');
		$fields[] = array($label, $input, $hint);

		// the title
		$label = i18n::s('Section title');
		$input = '<textarea id="title" name="title" rows="1" cols="50" accesskey="t">'.encode_field(i18n::c('Original pages')).'</textarea>';
		$hint = i18n::s('Please provide a meaningful title.');
		$fields[] = array($label, $input, $hint);

		// the introduction
		$label = i18n::s('Introduction');
		$input = '<textarea name="introduction" rows="2" cols="50" accesskey="i">'.encode_field(i18n::c('Read, learn, and react!')).'</textarea>';
		$hint = i18n::s('Appears at site map, near section title');
		$fields[] = array($label, $input, $hint);

		// the description
		$label = i18n::s('Description');
		$input = '<textarea name="description" rows="2" cols="50"></textarea>';
		$hint = i18n::s('Give a hint to interested people');
		$fields[] = array($label, $input, $hint);

		// author profile
		$label = i18n::s('Author\'s profile');
		$input = '<input type="radio" name="profile" value="prefix" checked="checked"'.EOT.' '.i18n::s('Below page title').BR;
		$input .= '<input type="radio" name="profile" value="suffix"'.EOT.' '.i18n::s('At page bottom').BR;
		$input .= '<input type="radio" name="profile" value="extra"'.EOT.' '.i18n::s('On page side').BR;
		$fields[] = array($label, $input);

		// the active flag: Yes/public, Restricted/logged, No/associates
		$label = i18n::s('Visibility');
		$input = '<input type="radio" name="active" value="Y" accesskey="v" checked="checked"'.EOT.' '.i18n::s('Anyone may read pages posted here').BR;
		$input .= '<input type="radio" name="active" value="R"'.EOT.' '.i18n::s('Access is restricted to authenticated members').BR;
		$input .= '<input type="radio" name="active" value="N"'.EOT.' '.i18n::s('Access is restricted to associates and editors');
		$fields[] = array($label, $input);

		// home panel
		$label = i18n::s('Front page');
		$input = i18n::s('Content of this section should be:').BR;
		$input .= '<input type="radio" name="home_panel" value="main" checked="checked"'.EOT.' '.i18n::s('displayed in the main panel, as usual').BR;
		$input .= '<input type="radio" name="home_panel" value="gadget"'.EOT.' '.i18n::s('listed in a gadget box, in the main panel').BR;
		$input .= '<input type="radio" name="home_panel" value="extra"'.EOT.' '.i18n::s('listed in an extra box, on page side').BR;
		$input .= '<input type="radio" name="home_panel" value="none"'.EOT.' '.i18n::s('not displayed at the front page');
		$fields[] = array($label, $input);

		// build the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// the submit button
		$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Add content'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		// append the script used for data checking on the browser
		$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
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
			.'document.getElementById("title").focus();'."\n"
			.'// ]]></script>'."\n";

	// create a section
	} else {

		$fields = array();
		$fields['anchor'] = $_REQUEST['anchor'];
		$fields['title'] = $_REQUEST['title'];
		$fields['introduction'] = $_REQUEST['introduction'];
		$fields['description'] = $_REQUEST['description'];
		$fields['active_set'] = $_REQUEST['active'];
		$fields['home_panel'] = $_REQUEST['home_panel'];
		$fields['index_map'] = 'Y'; // listed with ordinary sections
		if($_REQUEST['profile'] == 'extra')
			$fields['content_options'] = 'with_extra_profile, with_rating, with_bottom_tools'; // let surfers rate pages in all cases
		elseif($_REQUEST['profile'] == 'prefix')
			$fields['content_options'] = 'with_prefix_profile, with_rating, with_bottom_tools';
		else
			$fields['content_options'] = 'with_suffix_profile, with_rating, with_bottom_tools';
		$fields['rank'] = 10000; // default value
		if($new_id = Sections::post($fields)) {

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// splash
			$context['text'] .= '<p>'.i18n::s('Congratulations, one new section has been added to your site.').'</p>';

			// follow-up commands
			$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';
			$menu = array();
			$menu = array_merge($menu, array(Sections::get_url($new_id) => i18n::s('Check the new section')));
			if(Surfer::may_upload())
				$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$new_id) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('control/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			$context['text'] .= Skin::build_list($menu, 'menu_bar');

			// new content has been created
			Logger::remember('control/populate.php', 'content assistant has created new content');

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
		$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';
		$menu = array();
		$menu = array_merge($menu, array(Sections::get_url($item['id']) => i18n::s('Access the new section')));
		$menu = array_merge($menu, array('control/populate.php' => i18n::s('Launch the Content Assistant again')));
		$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

	// get section parameters
	} elseif(!isset($_REQUEST['title']) || !$_REQUEST['title']) {

		// splash
		$context['text'] .=  '<p>'.i18n::s('Partners are listed at the front page, in a scrollable area.').'</p>'
			.'<p>'.i18n::s('Change the value of the ranking field to prioritize partners.').'</p>';

		// a form to get section parameters
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>'."\n"
			.'<input type="hidden" name="action" value="partners"'.EOT;

		// the title
		$label = i18n::s('Section title');
		$input = '<textarea id="title" name="title" rows="1" cols="50" accesskey="t">'.encode_field(i18n::c('Partners')).'</textarea>';
		$hint = i18n::s('Adapt the title to the kinds of partnership you are developing');
		$fields[] = array($label, $input, $hint);

		// the introduction
		$label = i18n::s('Introduction');
		$input = '<textarea name="introduction" rows="2" cols="50" accesskey="i">'.encode_field(i18n::c('They are trusting us')).'</textarea>';
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
		$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
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
			.'document.getElementById("title").focus();'."\n"
			.'// ]]></script>'."\n";

	// create a section
	} else {

		$fields = array();
		$fields['nick_name'] = 'partners';
		$fields['title'] = $_REQUEST['title'];
		$fields['introduction'] = $_REQUEST['introduction'];
		$fields['home_panel'] = 'none'; // new pages are not pushed at the front page
		$fields['index_map'] = 'Y'; // listed with ordinary sections
		$fields['rank'] = 50000; // towards the end of the list
		$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
		if($new_id = Sections::post($fields)) {

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// splash
			$context['text'] .= '<p>'.i18n::s('Congratulations, one section dedicated to partners has been added to your site.').'</p>';

			// follow-up commands
			$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';
			$menu = array();
			$menu = array_merge($menu, array(Sections::get_url($new_id) => i18n::s('Access the new section')));
			if(Surfer::may_upload())
				$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$new_id) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('control/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			$context['text'] .= Skin::build_list($menu, 'menu_bar');

			// new content has been created
			Logger::remember('control/populate.php', 'content assistant has created new content');

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
			.'<input type="hidden" name="action" value="polls"'.EOT;

		// the anchor
		$label = i18n::s('Section anchor');
		$input = '<select name="anchor">'.'<option value="">'.i18n::s('-- Root level')."</option>\n".Sections::get_options('none', NULL).'</select>';
		$hint = i18n::s('Please carefully select a parent section, if any');
		$fields[] = array($label, $input, $hint);

		// the title
		$label = i18n::s('Section title');
		$input = '<textarea id="title" name="title" rows="1" cols="50" accesskey="t">'.encode_field(i18n::c('Polls')).'</textarea>';
		$hint = i18n::s('Use either a global name (eg, "the voting machine") or, narrow it and create multiple sections');
		$fields[] = array($label, $input, $hint);

		// the introduction
		$label = i18n::s('Introduction');
		$input = '<textarea name="introduction" rows="2" cols="50" accesskey="i">'.encode_field(i18n::c('The current active poll, plus previous ones')).'</textarea>';
		$hint = i18n::s('Appears at site map, near section title');
		$fields[] = array($label, $input, $hint);

		// the active flag: Yes/public, Restricted/logged, No/associates
		$label = i18n::s('Visibility');
		$input = '<input type="radio" name="active" value="Y" accesskey="v" checked="checked"'.EOT.' '.i18n::s('Anyone may read pages posted here').BR;
		$input .= '<input type="radio" name="active" value="R"'.EOT.' '.i18n::s('Access is restricted to authenticated members').BR;
		$input .= '<input type="radio" name="active" value="N"'.EOT.' '.i18n::s('Access is restricted to associates and editors');
		$fields[] = array($label, $input);

		// home panel
		$label = i18n::s('Front page');
		$input = i18n::s('Normally, YACS only display a panel for the most recent poll at the front page. To depart from this standard behaviour you can specify if polls should be:').BR;
		$input .= '<input type="radio" name="home_panel" value="main" checked="checked"'.EOT.' '.i18n::s('displayed in the main panel, as usual').BR;
		$input .= '<input type="radio" name="home_panel" value="gadget"'.EOT.' '.i18n::s('listed in the main panel, in a gadget box').BR;
		$input .= '<input type="radio" name="home_panel" value="extra"'.EOT.' '.i18n::s('listed at page side, in an extra box').BR;
		$input .= '<input type="radio" name="home_panel" value="none"'.EOT.' '.i18n::s('not displayed at the front page - use the Control Panel for Skins to ensure the most recent poll is displayed');
		$fields[] = array($label, $input);

		// build the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// the submit button
		$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Add content'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		// append the script used for data checking on the browser
		$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
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
			.'document.getElementById("title").focus();'."\n"
			.'// ]]></script>'."\n";

	// create a section
	} else {

		$fields = array();
		$fields['anchor'] = $_REQUEST['anchor'];
		$fields['title'] = $_REQUEST['title'];
		$fields['introduction'] = $_REQUEST['introduction'];
		$fields['active_set'] = $_REQUEST['active'];
		$fields['home_panel'] = $_REQUEST['home_panel'];
		$fields['index_map'] = 'Y'; // listed with ordinary sections
		$fields['overlay'] = 'poll'; // poll management
		$fields['rank'] = 10000; // default value
		$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
		if($new_id = Sections::post($fields)) {

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// splash
			$context['text'] .= '<p>'.i18n::s('Congratulations, one section specialized in polls has been added to your site.').'</p>';

			// follow-up commands
			$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';
			$menu = array();
			$menu = array_merge($menu, array(Sections::get_url($new_id) => i18n::s('Access the new section')));
			if(Surfer::may_upload())
				$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$new_id) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('control/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			$context['text'] .= Skin::build_list($menu, 'menu_bar');

			// new content has been created
			Logger::remember('control/populate.php', 'content assistant has created new content');

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
			.'<input type="hidden" name="action" value="recipes"'.EOT;

		// the anchor
		$label = i18n::s('Section anchor');
		$input = '<select name="anchor">'.'<option value="">'.i18n::s('-- Root level')."</option>\n".Sections::get_options('none', NULL).'</select>';
		$hint = i18n::s('Please carefully select a parent section, if any');
		$fields[] = array($label, $input, $hint);

		// the title
		$label = i18n::s('Section title');
		$input = '<textarea id="title" name="title" rows="1" cols="50" accesskey="t"></textarea>';
		$hint = i18n::s('Use either a global name (eg, "the family cookbook") or, narrow it and create multiple sections (eg, "Famous starters", "Main dishes", ...)');
		$fields[] = array($label, $input, $hint);

		// the introduction
		$label = i18n::s('Introduction');
		$input = '<textarea name="introduction" rows="2" cols="50" accesskey="i"></textarea>';
		$hint = i18n::s('Appears at site map, near section title');
		$fields[] = array($label, $input, $hint);

		// the active flag: Yes/public, Restricted/logged, No/associates
		$label = i18n::s('Visibility');
		$input = '<input type="radio" name="active" value="Y" accesskey="v" checked="checked"'.EOT.' '.i18n::s('Anyone may read pages posted here').BR;
		$input .= '<input type="radio" name="active" value="R"'.EOT.' '.i18n::s('Access is restricted to authenticated members').BR;
		$input .= '<input type="radio" name="active" value="N"'.EOT.' '.i18n::s('Access is restricted to associates and editors');
		$fields[] = array($label, $input);

		// home panel
		$label = i18n::s('Front page');
		$input = i18n::s('Published recipes should be:').BR;
		$input .= '<input type="radio" name="home_panel" value="main" checked="checked"'.EOT.' '.i18n::s('displayed in the main panel, as usual').BR;
		$input .= '<input type="radio" name="home_panel" value="gadget"'.EOT.' '.i18n::s('listed in the main panel, in a gadget box').BR;
		$input .= '<input type="radio" name="home_panel" value="extra"'.EOT.' '.i18n::s('listed at page side, in an extra box').BR;
		$input .= '<input type="radio" name="home_panel" value="none"'.EOT.' '.i18n::s('not displayed at the front page');
		$fields[] = array($label, $input);

		// build the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// the submit button
		$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Add content'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		// append the script used for data checking on the browser
		$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
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
			.'document.getElementById("title").focus();'."\n"
			.'// ]]></script>'."\n";

	// create a section
	} else {

		$fields = array();
		$fields['anchor'] = $_REQUEST['anchor'];
		$fields['title'] = $_REQUEST['title'];
		$fields['introduction'] = $_REQUEST['introduction'];
		$fields['active_set'] = $_REQUEST['active'];
		$fields['home_panel'] = $_REQUEST['home_panel'];
		$fields['index_map'] = 'Y'; // listed with ordinary sections
		$fields['content_options'] = 'wih_rating, with_bottom_tools'; // let surfers select their preferred recipe
		$fields['overlay'] = 'recipe'; // recipe management
		$fields['rank'] = 10000; // default value
		if($new_id = Sections::post($fields)) {

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// splash
			$context['text'] .= '<p>'.i18n::s('Congratulations, one section specialized in cooking recipes has been added to your site.').'</p>';

			// follow-up commands
			$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';
			$menu = array();
			$menu = array_merge($menu, array(Sections::get_url($new_id) => i18n::s('Access the new section')));
			if(Surfer::may_upload())
				$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$new_id) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('control/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			$context['text'] .= Skin::build_list($menu, 'menu_bar');

			// new content has been created
			Logger::remember('control/populate.php', 'content assistant has created new content');

		}
	}

// add samples to learn by example
} elseif($action == 'samples') {

	// page title
	$context['page_title'] = i18n::s('Add sample content');

	// ask for confirmation
	if(!isset($_REQUEST['confirmation']) || !$_REQUEST['confirmation']) {

		// splash message
		$context['text'] .= '<p>'.i18n::s('This script adds various records, including sections, categories, articles, comments and tables, to the database. We recommend you to proceed if you are discovering YACS and wish to learn by example.').'</p>'
			.'<p>'.i18n::s('If you have a running server, it could be a better choice to go back and to consider other options of the Content Assistant.').'</p>';

		// the form to get confirmation
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>'."\n"
			.'<input type="hidden" name="action" value="samples"'.EOT
			.'<input type="hidden" name="confirmation" value="Y"'.EOT;

		// the submit button
		$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Start')).'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

	// actual data creation
	} else {

		// create samples only
		$context['populate'] = 'samples';

		/**
		 * dynamically generate the page
		 *
		 * @see skins/index.php
		 */
		function send_body() {
			global $context;

			// populate tables for users
			echo Skin::build_block(i18n::s('Users'), 'subtitle');
			include_once '../users/populate.php';

			// populate tables for sections
			echo Skin::build_block(i18n::s('Sections'), 'subtitle');
			include_once '../sections/populate.php';

			// populate tables for categories
			echo Skin::build_block(i18n::s('Categories'), 'subtitle');
			include_once '../categories/populate.php';

			// populate tables for articles
			echo Skin::build_block(i18n::s('Articles'), 'subtitle');
			include_once '../articles/populate.php';

			// populate tables for comments
			echo Skin::build_block(i18n::s('Comments'), 'subtitle');
			include_once '../comments/populate.php';

			// populate tables for tables
			echo Skin::build_block(i18n::s('Tables'), 'subtitle');
			include_once '../tables/populate.php';

			// the populate hook
			if(is_callable(array('Hooks', 'include_scripts')))
				echo Hooks::include_scripts('control/populate.php');

			// configure the interface on first installation
			if(!file_exists('../parameters/switch.on') && !file_exists('../parameters/switch.off')) {
				echo '<form method="get" action="../skins/configure.php">'."\n"
					.'<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Configure the page factory')).'</p>'."\n"
					.'</form>'."\n";

			// or back to the control panel
			} else {

				// splash
				echo '<h3>'.i18n::s('What do you want to do now?').'</h3>';

				// follow-up commands
				$menu = array();
				$menu = array_merge($menu, array('sections/' => i18n::s('Check the updated Site Map')));
				$menu = array_merge($menu, array('control/populate.php' => i18n::s('Launch the Content Assistant again')));
				$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
				echo Skin::build_list($menu, 'menu_bar');

			}

			// new content has been created
			Logger::remember('control/populate.php', 'content assistant has created new content');

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
			.'<input type="hidden" name="action" value="servers"'.EOT
			.'<input type="hidden" name="confirmation" value="Y"'.EOT;

		// the submit button
		$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Start')).'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		// append the script used for data checking on the browser
		$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
			.'// check that main fields are not empty'."\n"
			.'func'.'tion validateDocumentPost(container) {'."\n"
			."\n"
			.'	// successful check'."\n"
			.'	return true;'."\n"
			.'}'."\n"
			."\n"
			.'// ]]></script>';

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
			$menu = array_merge($menu, array('control/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			echo Skin::build_list($menu, 'menu_bar');

			// new content has been created
			Logger::remember('control/populate.php', 'content assistant has created new content');

		}
	}

// create a vote or a poll
} elseif($action == 'vote') {

	// page title
	$context['page_title'] = i18n::s('Add a page');

	// get section parameters
	if(!isset($_REQUEST['type']) || !$_REQUEST['type']) {

		// splash message
		$context['text'] .= '<p>'.i18n::s('This script will create one single page to capture collective feed-back. What kind of support are you looking for?').'</p>';

		// a form to get section parameters
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>'."\n"
			.'<input type="hidden" name="action" value="vote"'.EOT;

		// vote
		$label = i18n::s('Vote');
		$input = '<input type="radio" name="type" value="vote" checked="checked"'.EOT.' '.i18n::s('The best way to formalize collective decisions. Every voter can expressed a Yes or No, and comment its ballot.');
		$fields[] = array($label, $input);

		// petition
		$label = i18n::s('Petition');
		$input = '<input type="radio" name="type" value="petition"'.EOT.' '.i18n::s('Ideal to express a broad support of some idea. Every signature can be commented.');
		$fields[] = array($label, $input);

		// poll
		$label = i18n::s('Poll');
		$input = '<input type="radio" name="type" value="poll"'.EOT.' '.i18n::s('The quickest way to identify trends. Any surfer can select among offered options, and YACS will sum up all clicks.');
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
			.'<input type="hidden" name="action" value="wiki"'.EOT;

		// the anchor
		$label = i18n::s('Wiki anchor');
		$input = '<select name="anchor">'.'<option value="">'.i18n::s('-- Root level')."</option>\n".Sections::get_options('none', NULL).'</select>';
		$hint = i18n::s('Please carefully select a parent section, if any');
		$fields[] = array($label, $input, $hint);

		// the title
		$label = i18n::s('Wiki Title');
		$input = '<textarea id="title" name="title" rows="1" cols="50" accesskey="t">'.encode_field(i18n::c('Our wiki')).'</textarea>';
		$hint = i18n::s('Please provide a meaningful title.');
		$fields[] = array($label, $input, $hint);

		// the introduction
		$label = i18n::s('Introduction');
		$input = '<textarea name="introduction" rows="2" cols="50" accesskey="i">'.encode_field(i18n::c('Our collaborative place')).'</textarea>';
		$hint = i18n::s('Appears at site map, near section title');
		$fields[] = array($label, $input, $hint);

		// the description
		$label = i18n::s('Description');
		$input = '<textarea name="description" rows="4" cols="50">'.encode_field(i18n::c('A description of what information is developed at this wiki.')).'</textarea>';
		$hint = i18n::s('Give a hint to interested people');
		$fields[] = array($label, $input, $hint);

		// the contribution flag: Yes/public, Restricted/logged, No/associates
		$label = i18n::s('Contribution');
		$input = '<input type="radio" name="contribution" value="Y" accesskey="c" checked="checked"'.EOT.' '.i18n::s('Anyone, including anonymous surfer, may contribute to this wiki.').BR;
		$input .= '<input type="radio" name="contribution" value="R"'.EOT.' '.i18n::s('Any authenticated member can contribute.').BR;
		$input .= '<input type="radio" name="contribution" value="N"'.EOT.' '.i18n::s('Only associates and editors can contribute.');
		$fields[] = array($label, $input);

		// the active flag: Yes/public, Restricted/logged, No/associates
		$label = i18n::s('Visibility');
		$input = '<input type="radio" name="active" value="Y" accesskey="v" checked="checked"'.EOT.' '.i18n::s('Anyone may read pages posted here').BR;
		$input .= '<input type="radio" name="active" value="R"'.EOT.' '.i18n::s('Access is restricted to authenticated members').BR;
		$input .= '<input type="radio" name="active" value="N"'.EOT.' '.i18n::s('Access is restricted to associates and editors');
		$fields[] = array($label, $input);

		// home panel
		$label = i18n::s('Front page');
		$input = i18n::s('Posts to this wiki should be:').BR;
		$input .= '<input type="radio" name="home_panel" value="main" checked="checked"'.EOT.' '.i18n::s('displayed in the main panel, as usual').BR;
		$input .= '<input type="radio" name="home_panel" value="gadget"'.EOT.' '.i18n::s('listed in the main panel, in a gadget box').BR;
		$input .= '<input type="radio" name="home_panel" value="extra"'.EOT.' '.i18n::s('listed at page side, in an extra box').BR;
		$input .= '<input type="radio" name="home_panel" value="none"'.EOT.' '.i18n::s('not displayed at the front page');
		$fields[] = array($label, $input);

		// build the form
		$context['text'] .= Skin::build_form($fields);
		$fields = array();

		// the submit button
		$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Add content'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		// append the script used for data checking on the browser
		$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
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
			.'document.getElementById("title").focus();'."\n"
			.'// ]]></script>'."\n";

	// create a section
	} else {

		$fields = array();
		$fields['anchor'] = $_REQUEST['anchor'];
		$fields['title'] = $_REQUEST['title'];
		$fields['introduction'] = $_REQUEST['introduction'];
		$fields['description'] = $_REQUEST['description'];
		$fields['active_set'] = $_REQUEST['active'];
		$fields['home_panel'] = $_REQUEST['home_panel'];
		$fields['index_map'] = 'Y'; // listed with ordinary sections
		$fields['articles_layout'] = 'wiki'; // the preferred layout for wikis
		$fields['options'] = 'articles_by_title'; // alphabetical order
		$fields['content_options'] = 'auto_publish with_rating with_bottom_tools';
		if($_REQUEST['contribution'] == 'Y')		// anyone can contribute
			$fields['content_options'] .= ' anonymous_edit';
		elseif($_REQUEST['contribution'] == 'R')	// only members can contribute
			$fields['content_options'] .= ' members_edit';
		elseif($_REQUEST['contribution'] == 'N')	// only associates and editors can contribute
			$fields['locked'] = 'Y';
		$fields['rank'] = 10000; // default value
		if($new_id = Sections::post($fields)) {

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// splash
			$context['text'] .= '<p>'.i18n::s('Congratulations, one wiki has been added to your site.').'</p>';

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

			// follow-up
			$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';

			// follow-up commands
			$menu = array();
			$menu = array_merge($menu, array(Sections::get_url($new_id) => i18n::s('Access the new wiki')));
			if(Surfer::may_upload())
				$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$new_id) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('control/populate.php' => i18n::s('Launch the Content Assistant again')));
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
			$context['text'] .= Skin::build_list($menu, 'menu_bar');

			// new content has been created
			Logger::remember('control/populate.php', 'content assistant has created new content');

		}
	}

// make the user select an option
} else {

	// the splash message
	$context['text'] .= '<p>'.i18n::s('This script will help to structure content for your server. Please select below the action you would like to perform.Depending on your choice, the assistant may ask for additional parameters on successive panels.').'</p>';

	// the form
	$context['text'] .= '<form method="get" action="'.$context['script_url'].'" id="main_form">'."\n";

	// create sample records
	$context['text'] .= '<p><input type="radio" name="action" value="samples" /> '.i18n::s('Add sample records -- add sections, categories, and articles to your site, and learn by example').'</p>'."\n";

	// create a collection
	$context['text'] .= '<p><input type="radio" name="action" value="collection" /> '.i18n::s('Add a collection -- and share available files, enable audio and video on-demand, and slideshows').'</p>'."\n";

	// create a blog
	$context['text'] .= '<p><input type="radio" name="action" value="blog" /> '.i18n::s('Add a blog -- and share your feeling, your findings, your soul').'</p>'."\n";

	// create a wiki
	$context['text'] .= '<p><input type="radio" name="action" value="wiki" /> '.i18n::s('Add a wiki -- to support collaborative work in a team of peers').'</p>'."\n";

	// post original work
	$context['text'] .= '<p><input type="radio" name="action" value="original" /> '.i18n::s('Post original work -- in a section that will feature author\'s profiles').'</p>'."\n";

	// create a forum
	$context['text'] .= '<p><input type="radio" name="action" value="forum" /> '.i18n::s('Add a forum -- let people interact').'</p>'."\n";

	// create a book
	$context['text'] .= '<p><input type="radio" name="action" value="book" /> '.i18n::s('Add an electronic book, or a manual -- actually, a structured set of pages').'</p>'."\n";

	// create a composite section
	$context['text'] .= '<p><input type="radio" name="action" value="composite" /> '.i18n::s('Add a composite section -- with scrolling news, plus gadget and extra boxes').'</p>'."\n";

	// sollicitate users for feed-back
	$context['text'] .= '<p><input type="radio" name="action" value="vote" /> '.i18n::s('Sollicitate users input -- create one single vote, a petition, or a poll').'</p>'."\n";

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
	$context['text'] .= '<p><input type="radio" name="action" value="build" /> '.i18n::s('Add basic records -- in case you would need to replay some steps of the setup process').'</p>'."\n";

	// the submit button
	$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Next step'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// end of the form
	$context['text'] .= '</form>'."\n";

	// contribution shortcuts, in a section box
	if(Surfer::is_member()) {
		$label = '<p>'.i18n::s('Of course, you may also use regular editors to create simple items:')."</p>\n";

		$label .= '<ul>'."\n";

		if(Surfer::is_associate())
			$label .= '<li>'.Skin::build_link('sections/edit.php', i18n::s('Add a section'), 'shortcut').'</li>'."\n"
				.'<li>'.Skin::build_link('categories/edit.php', i18n::s('Add a category'), 'shortcut').'</li>'."\n";

		$label .= '<li> '.Skin::build_link('articles/edit.php', i18n::s('Add a page'), 'shorcut').'</li>'."\n";

		$label .= '</ul>'."\n";

		$context['text'] .= Skin::build_box(i18n::s('Shortcuts'), $label);
	}

	// the help panel
	$help = '<p>'.i18n::s('Turn any regular section to a photo album by adding images to posted pages.').'</p>'
		.'<p>'.i18n::s('YACS creates weekly and monthly archives automatically. No specific action is required to create these.').'</p>';
	$context['extra'] .= Skin::build_box(i18n::s('Tips'), $help, 'navigation', 'help');

}

// flush the cache
Cache::clear();

// render the skin
render_skin();

?>