<?php
/**
 * populate the database
 *
 * This script helps to create default records in the database.
 *
 * Creates following sections for web management purposes:
 * - 'covers' - the most recent article is displayed at the front page of the site
 * - 'default' - the default place to post new content
 * - 'extra_boxes' - boxes only displayed at the front page, in the extra panel
 * - 'gadget_boxes' - boxes only displayed at the front page, as gadgets
 * - 'global' - global pages
 * - 'navigation_boxes' - displayed at every page of the site, in the navigation panel
 * - 'processed_queries' - the archive of old queries
 * - 'private' - private pages
 * - 'queries' - pages sent by surfers to submit their queries to the webmaster
 * - 'templates' - models for new articles
 *
 * Additional sections may be created directly from within the content assistant, in [script]control/populate.php[/script].
 *
 * Moreover, some sections may be created by other scripts, namely:
 * - 'clicks' - to put orphan links when clicked - created automatically in [script]links/links.php[/script]
 * - 'external_news' - for links gathered from feeding servers in [script]feeds/feeds.php[/script] and [script]servers/edit.php[/script]
 * - 'letters' - pages sent by e-mail to subscribers - created automatically in [script]letters/new.php[/script]
 *
 * @see letters/new.php
 * @see links/links.php
 * @see query.php
 *
 * Creates following categories for web management purposes:
 * - 'featured' - the articles to display at the front page
 * - 'monthly' - for articles published each month
 * - 'weekly' - for articles published each week
 *
 * Creates articles with following nick names:
 * - 'about' - a page providing an overview of this site
 * - 'cover' - a cover article for the front page
 * - 'extra_rss' - a sample extra box to link to our XML RSS feed
 * - 'menu' - a sample general menu displayed on all pages
 * - 'privacy' - to provide clear statements to community members
 *
 * If there is no [code]parameters/switch.on[/code] nor [code]parameters/switch.off[/code] file, the script
 * asks for a user name and password to create an associate user profile.
 * This is usually what happen on first installation.
 *
 * This can be used also if one webmaster losts his password.
 * In this case, delete the switch file (normally, [code]parameters/switch.on[/code])
 * and trigger this script to recreate a new associate user profile.
 *
 * This script also invokes a hook to populate additional tables:
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
include_once '../categories/categories.php';

// force the creation of a user profile if the user table does not exists, or is empty
$query = "SELECT count(*) FROM ".SQL::table_name('users');
if(!SQL::query_scalar($query, FALSE, $context['users_connection']))
	$permitted = TRUE;

// force the creation of a user profile if there is no switch file
elseif(!(file_exists('../parameters/switch.on') || file_exists('../parameters/switch.off')))
	$permitted = TRUE;

// associates can do what they want
elseif(Surfer::is_associate())
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
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// ask for parameters
} elseif(!isset($_REQUEST['nick_name']) || !$_REQUEST['nick_name']) {

	// splash message
	$context['text'] .= '<p>'.i18n::s('Please indicate below the name and password you will use to authenticate to this server.').'</p>'
		.'<p>'.i18n::s('DO NOT FORGET THIS LOGIN!! There is no default administrative login created when YACS is installed, so if you lose your login, you will have to purge the database and trigger the setup script again.').'</p>'."\n";

	// the form to get user attributes
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" name="main_form"><div>'."\n"
		.'<input type="hidden" name="action" value="associate" />';

	// the nick name
	$label = i18n::s('Nick name');
	$input = '<input type="text" id="nick_name" name="nick_name" size="40" />';
	$hint = i18n::s('Please carefully select a meaningful nick name.');
	$fields[] = array($label, $input, $hint);

	// the password
	$label = i18n::s('Password');
	$input = '<input type="text" name="password" size="20" />';
	$hint = i18n::s('We recommend at least 4 letters, two digits, and a punctuation sign - in any order');
	$fields[] = array($label, $input, $hint);

	// the password has to be repeated for confirmation
	$label = i18n::s('Password confirmation');
	$input = '<input type="text" name="confirm" size="20" />';
	$fields[] = array($label, $input);

	// build the form
	$context['text'] .= Skin::build_form($fields);
	$fields = array();

	// the submit button
	$context['text'] .= '<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// end of the form
	$context['text'] .= '</div></form>';

	// append the script used for data checking on the browser
	$context['text'] .= JS_PREFIX
		.'// check that main fields are not empty'."\n"
		.'func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'	// name is mandatory'."\n"
		.'	if(!container.nick_name.value) {'."\n"
		.'		alert("'.i18n::s('You must provide a nick name.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n"
		.'	// password is mandatory'."\n"
		.'	if(!container.password.value) {'."\n"
		.'		alert("'.i18n::s('You must provide a password.').'");'."\n"
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
		.'$("nick_name").focus();'."\n"
		.JS_SUFFIX;

	// this may take some time
	$context['text'] .= '<p>'.i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.')."</p>\n";

// actual data creation
} else {

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
	$user['interface']	= 'C';	// access all configuration panels
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

		$context['text'] .= '<p>'.sprintf(i18n::s('One associate profile "%s" has been created.'), $user['nick_name'])."</p>\n";

		// impersonate the new created user profile on first installation
		if(!file_exists('../parameters/switch.on') && !file_exists('../parameters/switch.off'))
			Surfer::set($user);

	}

	// create reference sections
	$text = '';

	// 'covers' section
	if(Sections::get('covers'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), i18n::c('Covers')).BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = 'covers';
		$fields['title'] = i18n::c('Covers');
		$fields['introduction'] = i18n::c('The top page here is also displayed at the front page');
		$fields['home_panel'] = 'none'; // special processing at the front page -- see index.php
		$fields['index_map'] = 'N'; // listed with special sections
		$fields['locked'] = 'Y'; // only associates can contribute
		$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
		$fields['content_options'] = 'auto_publish'; // ease the job of newbies
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['title']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'default' section
	if(Sections::get('default'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), i18n::c('Pages')).BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = 'default';
		$fields['title'] = i18n::c('Pages');
		$fields['introduction'] = i18n::c('The default place for new pages');
		$fields['description'] = '';
		$fields['sections_layout'] = 'decorated';
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['title']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'extra_boxes' section
	if(Sections::get('extra_boxes'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), i18n::c('Extra boxes')).BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = 'extra_boxes';
		$fields['title'] = i18n::c('Extra boxes');
		$fields['introduction'] = i18n::c('Boxes displayed aside the front page');
		$fields['description'] = i18n::c('All [link=codes]codes/[/link] are available to format boxes. Keep the content as compact as possible because of the small size of any single box. When ready, publish pages to actually show boxes to everybody.');
		$fields['home_panel'] = 'extra_boxes'; // one extra box per article at the front page
		$fields['index_map'] = 'N'; // listed with special sections
		$fields['locked'] = 'Y'; // only associates can contribute
		$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['title']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'gadget_boxes' section
	if(Sections::get('gadget_boxes'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), i18n::c('Gadget boxes')).BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = 'gadget_boxes';
		$fields['title'] = i18n::c('Gadget boxes');
		$fields['introduction'] = i18n::c('Boxes displayed in the middle of the front page');
		$fields['description'] = i18n::c('All [link=codes]codes/[/link] are available to format boxes. Keep the content as compact as possible because of the small size of any single box. When ready, publish pages to actually show boxes to everybody.');
		$fields['home_panel'] = 'gadget_boxes'; // one gadget box per article at the front page
		$fields['index_map'] = 'N'; // listed with special sections
		$fields['locked'] = 'Y'; // only associates can contribute
		$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['title']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'global' section
	if(Sections::get('global'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), i18n::c('Global pages')).BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = 'global';
		$fields['title'] = i18n::c('Global pages');
		$fields['home_panel'] = 'none'; // special processing at the front page -- see index.php
		$fields['index_map'] = 'N'; // listed with special sections
		$fields['locked'] = 'Y'; // only associates can contribute
		$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
		$fields['rank'] = '1000'; // before other special sections
		$fields['content_options'] = 'auto_publish'; // these will be reviewed anyway
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['title']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'navigation_boxes' section
	if(Sections::get('navigation_boxes'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), i18n::c('Navigation boxes')).BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = 'navigation_boxes';
		$fields['title'] = i18n::c('Navigation boxes');
		$fields['introduction'] = i18n::c('Boxes displayed aside all pages');
		$fields['description'] = i18n::c('All [link=codes]codes/[/link] are available to format boxes. Keep the content as compact as possible because of the small size of any single box. When ready, publish pages to actually show boxes to everybody.');
		$fields['home_panel'] = 'none'; // special processing everywhere -- see skins/<skin>/template.php
		$fields['index_map'] = 'N'; // listed with special sections
		$fields['locked'] = 'Y'; // only associates can contribute
		$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['title']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'processed_queries' section
	if($section = Sections::get('processed_queries')) {
		$text .= sprintf(i18n::s('A section "%s" already exists.'), i18n::c('Processed queries')).BR."\n";
		$processed_id = $section['id'];
	} else {
		$fields = array();
		$fields['nick_name'] = 'processed_queries';
		$fields['title'] = i18n::c('Processed queries');
		$fields['introduction'] =& i18n::c('Saved for history');
		$fields['active_set'] = 'N'; // only associates can access these pages
		$fields['home_panel'] = 'none'; // special processing everywhere -- see skins/<skin>/template.php
		$fields['index_map'] = 'N'; // listed with special sections
		$fields['locked'] = 'Y'; // only associates can contribute
		$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
		$fields['rank'] = '20000'; // pushed at the end
		if($processed_id = Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['title']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'private' section
	if($section = Sections::get('private')) {
		$text .= sprintf(i18n::s('A section "%s" already exists.'), i18n::c('Private pages')).BR."\n";
	} else {
		$fields = array();
		$fields['nick_name'] = 'private';
		$fields['title'] =& i18n::c('Private pages');
		$fields['introduction'] =& i18n::c('For on-demand conversations and groups');
		$fields['locked'] = 'N'; // no direct contributions
		$fields['home_panel'] = 'none'; // content is not pushed at the front page
		$fields['index_map'] = 'N'; // this is a special section
		$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
		$fields['articles_layout'] = 'yabb'; // these are threads
		$fields['content_options'] = 'with_deletions with_export_tools'; // allow editors to delete pages here
		$fields['maximum_items'] = 20000; // limit the overall number of threads
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['title']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'queries' section --after processed_queries
	if(Sections::get('queries'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), i18n::c('Queries')).BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = 'queries';
		$fields['title'] = i18n::c('Queries');
		$fields['introduction'] =& i18n::c('Submitted by any surfer');
		$fields['description'] =& i18n::c('Add comments to provide support.');
		$fields['active_set'] = 'N'; // only associates can access these pages
		$fields['home_panel'] = 'none'; // special processing everywhere -- see skins/<skin>/template.php
		$fields['index_map'] = 'N'; // listed with special sections
		$fields['locked'] = 'Y'; // only associates can contribute
		$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
		if($processed_id)
			$fields['behaviors'] = 'move_on_article_access '.$processed_id; // a basic workflow
		$fields['content_options'] = 'auto_publish'; // these will be reviewed anyway
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['title']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'templates' section
	if(Sections::get('templates'))
		$text .= sprintf(i18n::s('A section "%s" already exists.'), i18n::c('Templates')).BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = 'templates';
		$fields['title'] = i18n::c('Templates');
		$fields['introduction'] = i18n::c('Models to be duplicated');
		$fields['active_set'] = 'N'; // only associates can access these pages
		$fields['home_panel'] = 'none'; // special processing everywhere -- see skins/<skin>/template.php
		$fields['index_map'] = 'N'; // listed with special sections
		$fields['locked'] = 'Y'; // only associates can contribute
		$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
		$fields['content_options'] = 'auto_publish'; // these will be reviewed anyway
		if(Sections::post($fields))
			$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['title']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// report to surfer
	$context['text'] .= Skin::build_box(i18n::s('Sections'), $text);

	// create reference categories
	$text = '';

	// 'featured' category
	if(Categories::get(i18n::c('featured')))
		$text .= sprintf(i18n::s('A category "%s" already exists.'), i18n::c('Featured')).BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = i18n::c('featured');
		$fields['title'] = i18n::c('Featured');
		$fields['introduction'] = i18n::c('Pages to display at the front page');
		$fields['description'] = i18n::c('Pages attached to this category are featured at the front page in a compact list aside.');
		$fields['active_set'] = 'N';
		$fields['active'] = 'N';
		$fields['options'] = 'no_links';
		if(Categories::post($fields))
			$text .= sprintf(i18n::s('A category "%s" has been created.'), $fields['title']).BR."\n";
	}

	// 'monthly' category
	if(Categories::get(i18n::c('monthly')))
		$text .= sprintf(i18n::s('A category "%s" already exists.'), i18n::c('Publications by month')).BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = i18n::c('monthly');
		$fields['title'] = i18n::c('Publications by month');
		$fields['introduction'] = '';
		$fields['rank'] = 22000;
		$fields['options'] = 'no_links';
		if(Categories::post($fields))
			$text .= sprintf(i18n::s('A category "%s" has been created.'), $fields['title']).BR."\n";
	}

	// 'weekly' category
	if(Categories::get(i18n::c('weekly')))
		$text .= sprintf(i18n::s('A category "%s" already exists.'), i18n::c('Publications by week')).BR."\n";
	else {
		$fields = array();
		$fields['nick_name'] = i18n::c('weekly');
		$fields['title'] = i18n::c('Publications by week');
		$fields['introduction'] = '';
		$fields['rank'] = 21000;
		$fields['options'] = 'no_links';
		if(Categories::post($fields))
			$text .= sprintf(i18n::s('A category "%s" has been created.'), $fields['title']).BR."\n";
	}

	// report to surfer
	$context['text'] .= Skin::build_box(i18n::s('Categories'), $text);

	// create reference articles
	$text = '';

	// 'about' article
	if(Articles::get('about'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), i18n::c('About this site')).BR."\n";
	elseif($anchor = Sections::lookup('global')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'about';
		$fields['title'] = i18n::c('About this site');
		$fields['description'] = '[toc]'."\n"
			.'[title]'.i18n::c('What is [parameter=site_name] about?').'[/title]'."\n"
			.'<p>'.i18n::c('Welcome to this server! Here are attributes transmitted in the header of each page:').'</p>'."\n"
			.'<table class="wide">'."\n"
			.'<tr><td>'.i18n::c('Site name').'</td><td>[parameter=site_name]</td></tr>'."\n"
			.'<tr><td>'.i18n::c('Description').'</td><td>[parameter=site_description]</td></tr>'."\n"
			.'<tr><td>'.i18n::c('Keywords').'</td><td>[parameter=site_keywords]</td></tr>'."\n"
			.'<tr><td>'.i18n::c('Geographical position').'</td><td>[parameter=site_position]</td></tr>'."\n"
			.'<tr><td>'.i18n::c('Copyright').'</td><td>[parameter=site_copyright]</td></tr>'."\n"
			.'<tr><td>'.i18n::c('Owner').'</td><td>[parameter=site_owner]</td></tr>'."\n"
			.'</table>'."\n"
			.'<p>'.i18n::c('This site is powered by [link=YACS]http://www.yacs.fr/[/link], [link=PHP]http://www.php.net/[/link], and [link=MySQL]http://www.mysql.com/[/link] - a fast, easy-to-use site that lets you access, review, and download information that matters for you.').'</p>'."\n"
			.'[title]'.i18n::c('Contact').'[/title]'."\n"
			.'<p>'.i18n::c('The preferred mean to join us is to use the general-purpose query form. This is a convenient tool for you and for us, because your request and related answers will be listed at a single place accessible from your web browser. Moreover, you will be informed by e-mail of any progressing step to your query.').'</p>'."\n"
			.'<p class="indent">'.i18n::c('Query: [link=Use the on-line form]query.php[/link]').'</p>'."\n"
			.'<p>'.i18n::c('If you have something vitally important to tell us, send a message at the following address.').'</p>'."\n"
			.'<p class="indent">'.i18n::c('Webmaster: [parameter=site_email]').'</p>'."\n"
			.'[note]'.i18n::c('Due to the large amount of e-mail that we get, we can NOT guarantee a response! This is especially true if your e-mail concerns issues we can\'t really help you with.').'[/note]'."\n"
			.'[title]'.i18n::c('How you can help').'[/title]'."\n"
			.'<p>'.i18n::c('We are proud of this server, but it certainly isn\'t perfect. We\'re always looking for ways to improve the site. So, if you\'ve got any great ideas, then please let us know about them. Or, if you know of some important information that isn\'t listed here, email the author and tell them you want it listed on this site! It\'ll help make this server a better site for the whole community - and that\'s who we\'re here to serve.').'</p>'."\n"
			.'[title]'.i18n::c('Legal Stuff').'[/title]'."\n"
			.'<p>'.i18n::c('Be nice to other people, don\'t spam them, and don\'t do anything bad with any information you might get from this site.').'</p>'."\n"
			.'<p>'.i18n::c('All content, including but not limited to user reviews, application listings and information contained therein, is owned by [parameter=site_owner] and may not be copied without permission. However, the above content my be freely modified and/or removed by the person or persons responsible for submitting it.').'</p>'."\n"
			.'<p>'.i18n::c('[parameter=site_owner] is not responsible for the content of information submitted to the site. [parameter=site_owner] is not responsible for any content contained on other sites that may link to or be linked to [parameter=site_name]. As such, the existence of a link to another site on [parameter=site_name] does not express endorsement by [parameter=site_owner] of said site or its contents.').'</p>'."\n"
			.'<p>'.i18n::c('All other trademarks, icons, and logos, shown or mentioned, are the property of their respective owners, including those associated with any solutions listed in [parameter=site_name]. Although [parameter=site_owner] does not own and is not responsible for all of the content on the site, we reserve the right to edit or remove any content at any time in any way we deem necessary, without any notification whatselver to the owner(s) of that content, to keep it better in line with our stated and/or unstated policies. [parameter=site_owner] is not responsible for any copyright laws violated by the applications listed herein, although we will do everything we can do resolve any disputes that may arise in this area.').'</p>'."\n";
		$fields['locked'] = 'Y'; // only associates can change this page
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['title']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'cover' article - basic data
	if(Articles::get('cover'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), i18n::c('Welcome!')).BR."\n";
	elseif($anchor = Sections::lookup('covers')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'cover';
		$fields['title'] = i18n::c('Welcome!');
		$fields['locked'] = 'Y'; // only associates can change this page
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['title']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'extra_rss' article - basic data
	if(Articles::get('extra_rss'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), i18n::c('Information channels')).BR."\n";
	elseif($anchor = Sections::lookup('extra_boxes')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'extra_rss';
		$fields['title'] = i18n::c('Information channels');
		$fields['introduction'] = '';
		$fields['description'] = Skin::build_link('feeds/rss_2.0.php', i18n::c('Recent pages'), 'xml')
			.BR.Skin::build_link('feeds/', i18n::c('Information channels'), 'shortcut');
		$fields['locked'] = 'Y'; // only associates can change this page
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['title']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'menu' article - basic data
	if(Articles::get('menu'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), i18n::c('Menu')).BR."\n";
	elseif($anchor = Sections::lookup('global')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'menu';
		$fields['title'] = i18n::c('Menu');
		$fields['active_set'] = 'N'; // this page is integrated into every page anyway
		$fields['introduction'] = '';
		$fields['description'] = '[search]'."\n"
			.'[menu='.i18n::c('Home').']'.$context['url_to_root'].'[/menu]'."\n"
			.'[submenu='.i18n::c('Site map').']sections/[/submenu]'."\n"
			.'[submenu='.i18n::c('Categories').']categories/[/submenu]'."\n"
			.'[submenu='.i18n::c('People').']users/[/submenu]'."\n"
			.'[submenu='.i18n::c('Help').']help/[/submenu]'."\n";
		$fields['locked'] = 'Y'; // only associates can change this page
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['title']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// 'privacy' article
	if(Articles::get('privacy'))
		$text .= sprintf(i18n::s('A page "%s" already exists.'), i18n::c('Privacy statement')).BR."\n";
	elseif($anchor = Sections::lookup('global')) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['nick_name'] = 'privacy';
		$fields['title'] = i18n::c('Privacy statement');
		$fields['description'] = '[toc]'."\n"
			.'<p>'.i18n::c('We respect your privacy! Any and all information collected at this site will be kept strictly confidential and will not be sold, reused, rented, loaned or otherwise disclosed. Any information you give to us will be held with the utmost care, and will not be used in ways that you have not consented to. Read on for more specific information.').'</p>'."\n"
			.'[title]'.i18n::c('User Information').'[/title]'."\n"
			.'<p>'.i18n::c('In order to make certain parts of this server work, some sections of the site require you to give us your e-mail address and/or other types of personal information (or may do so in the future). We do not sell, rent, loan, trade, or lease any personal information collected at our site, including survey forms or email lists. In other words, your information is safe with us.').'</p>'."\n"
			.'[title]'.i18n::c('Mailings').'[/title]'."\n"
			.'<p>'.i18n::c('In some cases we will use your email address internally, both for identification purposes and to email you updates related to your pages. Your explicit approval is required to mail you our periodic newsletter. At any time you can easily configure your account to enable or to disable such mailings. We <i>may</i>, in certain cases, send you email updates you haven\'t specifically requested to receive; if and when we do this, we won\'t do it without a good reason (and this certainly won\'t serve as our excuse to spam you.)').'</p>'."\n"
			.'[title]'.i18n::c('Web Logs').'[/title]'."\n"
			.'<p>'.i18n::c('We occasionally analyzes our website logs to constantly improve the value of the content available on our website. Our website logs are not personally identifiable, and we make no attempt to link them with the individuals that actually browse the site.').'</p>'."\n"
			.'[title]'.i18n::c('Cookies').'[/title]'."\n"
			.'<p>'.i18n::c('Although some Internet users think cookies are a serious privacy issue, as web designers we think that they come in darned handy. This site uses cookies for basic account identification purposes, but that\'s as far as we go. We won\'t use any information from cookies to track your browsing sessions, attempt to extract personal information from them that we wouldn\'t otherwise have access to, or do any other naughty things with your cookies. If you don\'t agree with our use of cookies, you can configure most web browsers not to accept them. Even without a cookie, a significant part of this server will still be accessible to you (although you will lose the ability to do anything that requires you to be logged into the server).').'</p>'."\n"
			.'[title]'.i18n::c('Passwords').'[/title]'."\n"
			.'<p>'.i18n::c('This server requires you to define and enter passwords to access certain areas of the site. In case this conjures up images of crackers breaking into our databases and getting your passwords, you don\'t have to worry about it. Passwords are stored in an encrypted format, so that <i>we</i> can\'t even look at them. This encryption <i>is</i> breakable, but it takes a lot of effort and computing time (days or weeks to crack a single password) so for all intents and purposes, your passwords are safe here. The downside to this, of course, is that if you lose or forget your password we can\'t get it back for you. We\'ve provided a method to reset your password if this happens, but as with any other password, it\'s best not to forget in the first place.').'</p>'."\n"
			.'[title]'.i18n::c('Posted material').'[/title]'."\n"
			.'<p>'.i18n::c('Posted material is, of course, not private; several persons can look at them. But this is as good a time as any to point out that we own and retain control of whatever records you enter into the system. This means that we reserve the right to view and modify your articles, files, links, comments, etc. and we exercise this right. Most of the time we do this to fix trivial things; for example, if you post a page into an incorrect section, made a really obvious typo, or put in something really inappropriate, then we might modify it. But in any case, you should be aware that this could happen. FYI, <i>we</i> get to decide what\'s inappropriate, but you should all know your netiquette by now, right?  :-)').'</p>'."\n"
			.'<hr/><p>'.i18n::c('If you have any questions or comments about our privacy policy, or would like more information about a particular category, please [article=about, get in touch] with us.').'</p>'."\n";
		$fields['locked'] = 'Y'; // only associates can change this page
		$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(Articles::post($fields))
			$text .= sprintf(i18n::s('A page "%s" has been created.'), $fields['title']).BR."\n";
		else
			$text .= Logger::error_pop().BR."\n";
	}

	// report to surfer
	$context['text'] .= Skin::build_box(i18n::s('Pages'), $text);

	// the populate hook
	if(is_callable(array('Hooks', 'include_scripts')) && ($text = Hooks::include_scripts('control/populate.php')))
		$context['text'] .= Skin::build_box(i18n::s('Extensions'), $text);

	// configure the interface on first installation
	if(!file_exists('../parameters/switch.on') && !file_exists('../parameters/switch.off')) {
		$context['text'] .= Skin::build_block('<form method="get" action="../skins/configure.php">'."\n"
			.'<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Configure the page factory')).'</p>'."\n"
			.'</form>', 'bottom');

	// or back to the control panel
	} else {
		$menu = array('control/' => i18n::s('Control Panel'));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');
	}

	// an associate account has been created
	Logger::remember('control/populate.php', 'database has been populated');

}

// flush the cache
Cache::clear();

// render the skin
render_skin();

?>