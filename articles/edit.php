<?php
/**
 * create a new article or edit an existing one
 *
 * @todo add a toggle to not display the introduction in the main page
 * @todo add some hook to validate posts (TheAlchemist)
 *
 * This is the main script used to post a new page, or to modify an existing one.
 *
 * On anonymous usage YACS attempts to stop robots by generating a random string and by asking user to type it.
 *
 * A button-based editor is used for the description field.
 * It's aiming to introduce most common [link=codes]codes/index.php[/link] supported by YACS.
 * Also, sample smilies are displayed, and may be used to introduce related codes into the description field.
 *
 * This script attempts to validate the new or updated article description against a standard PHP XML parser.
 * The objective is to spot malformed or unordered HTML and XHTML tags. No more, no less.
 *
 * Tags may be freely added at the bottom of the page, and these are automatically converted to permanent categories
 * for easy taxonomies.
 *
 * Advanced options are available in a folded division.
 *
 * If a publication date is provided, YACS will wait for the given date before listing the page - automatically.
 * Note that no external server will be pinged with this way of publishing pages.
 * Pings require either to use the publishing script ([script]articles/publish.php[/script]), or
 * to trigger explicit ping ([script]servers/ping.php[/script]).
 *
 * Note that in Wiki mode (i.e. [code]with_auto_publish[/code] set to 'Y'),
 * or in forum sections (i.e. with option '[code]auto_publish[/code]'), posted pages are published as well.
 *
 * Optional publication and expiration dates are automatically translated from and to the surfer time zone and the
 * server time zone.
 *
 * The permission assessment is based upon following rules applied in the provided orders:
 * - associates and editors are allowed to move forward
 * - surfer created the page and the page has not been published
 * - surfer created the published page and revisions are allowed
 * - permission is denied if the anchor is not viewable
 * - this is a new post and the surfer is an authenticated member and submission is allowed
 * - permission denied is the default
 *
 * Accepted calls:
 * - edit.php create a new article, start by selecting a section
 * - edit.php?name=&lt;nick_name&gt; create a new article and name it
 * - edit.php?anchor=section:&lt;id&gt; create a new article within the given section
 * - edit.php?anchor=section:&lt;id&gt;&variant=&lt;overlay&gt; create a new overlaid article within the given section
 * - edit.php/&lt;id&gt; modify an existing article
 * - edit.php?id=&lt;id&gt; modify an existing article
 *
 * If no anchor data is provided, a list of sections is proposed to let the surfer select one of them.
 *
 * There is also a special invocation format to be used for direct blogging from bookmarklets,
 * such as the one provided by YACS for each section (see [script]sections/view.php[/script]),
 * or the one dedicated to users of Internet Explorer (see [script]articles/ie_bookmarklet.php[/script]).
 *
 * @see articles/ie_bookmarklet.php
 * @see sections/view.php
 *
 * This format is aiming to provide to YACS every necessary parameters, but through a single GET or POST call.
 * Following parameters have to be provided:
 * - [code]account[/code] - the nickname to log in
 * - [code]password[/code] - the related password
 * - [code]section[/code], or [code]blogid[/code] - the optional id of the section where the new page will be placed
 * - [code]title[/code] - an optional title for the new page
 * - [code]introduction[/code] - some optional introduction text
 * - [code]text[/code] - some optional text to be blogged
 * - [code]source[/code] - the optional source
 *
 * If no authentication data is provided ([code]account[/code] and [code]password[/code]),
 * the surfer is redirected to the login page at [script]users/login.php[/script].
 *
 * Data submitted from a bookmarklet is saved as session data.
 * Therefore information is preserved through any additional steps, including user authentication and section selection.
 *
 * Also, session data is purged on successful article post.
 *
 * This design allows for a generic bookmarklet (i.e., not restricted to a section) bound only to the web site.
 *
 * This bookmarklet is proposed as a direct link to any authenticated member:
 * - at the control panel ([script]control/index.php[/script])
 * - at any user page ([script]users/view.php[/script])
 * - at the main help page ([script]help/index.php[/script])
 *
 * @see control/index.php
 * @see users/view.php
 * @see help/index.php
 *
 * If this article, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author Vincent No&euml;l
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester GnapZ
 * @tester Pascal
 * @tester Guillaume Perez
 * @tester Manuel López Gallego
 * @tester J&eacute;r&ocirc;me Douill&eacute;
 * @tester Jan Boen
 * @tester Olivier
 * @tester Geoffroy Raimbault
 * @tester Pat
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../shared/xml.php';	// input validation

// allow for direct login
if(isset($_REQUEST['account']) && isset($_REQUEST['password'])) {

	// authenticate the surfer and start a session
	if($user = Users::login($_REQUEST['account'], $_REQUEST['password']))
		Surfer::set($user);
}

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Articles::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);
elseif(isset($_REQUEST['anchor']) && $_REQUEST['anchor'])
	$anchor = Anchors::get($_REQUEST['anchor']);
elseif(isset($_REQUEST['section']) && $_REQUEST['section'])
	$anchor = Anchors::get('section:'.$_REQUEST['section']);
elseif(isset($_REQUEST['blogid']) && $_REQUEST['blogid'])
	$anchor = Anchors::get('section:'.$_REQUEST['blogid']);
elseif(isset($_SESSION['anchor_reference']) && $_SESSION['anchor_reference'])
	$anchor = Anchors::get($_SESSION['anchor_reference']);

// get the related overlay, if any
$overlay = NULL;
include_once '../overlays/overlay.php';
if(isset($item['overlay']) && $item['overlay'])
	$overlay = Overlay::load($item);
elseif(isset($_REQUEST['variant']) && $_REQUEST['variant'])
	$overlay = Overlay::bind($_REQUEST['variant']);
elseif(isset($_REQUEST['overlay_type']) && $_REQUEST['overlay_type'])
	$overlay = Overlay::bind($_REQUEST['overlay_type']);
elseif(isset($_SESSION['pasted_variant']) && $_SESSION['pasted_variant']) {
	$overlay = Overlay::bind($_SESSION['pasted_variant']);
	unset($_SESSION['pasted_variant']);
} elseif(!isset($item['id']) && is_object($anchor) && ($overlay_class = $anchor->get_overlay()))
	$overlay = Overlay::bind($overlay_class);

// section editors can do what they want
if(is_object($anchor) && $anchor->is_editable())
	Surfer::empower();

// article editors can also do what they want
elseif(isset($item['id']) && Articles::is_assigned($item['id']))
	Surfer::empower();

// surfer has been explicitly invited to collaborate
elseif(isset($item['handle']) && Surfer::may_handle($item['handle']))
	Surfer::empower();

// anonymous edition is allowed here
elseif(isset($item['options']) && $item['options'] && preg_match('/\banonymous_edit\b/i', $item['options']))
	Surfer::empower();

// members edition is allowed here
elseif(Surfer::is_member() && isset($item['options']) && $item['options'] && preg_match('/\bmembers_edit\b/i', $item['options']))
	Surfer::empower();

// associates and editors can do what they want
if(Surfer::is_empowered())
	$permitted = TRUE;

// surfer created the page and the page has not been published
elseif(Surfer::get_id() && isset($item['create_id']) && ($item['create_id'] == Surfer::get_id())
	&& (!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE)) )
	$permitted = TRUE;

// surfer has created the published page and revisions are allowed
elseif(Surfer::get_id() && isset($item['create_id']) && ($item['create_id'] == Surfer::get_id())
	&& isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE)
	&& (!isset($context['users_without_revision']) || ($context['users_without_revision'] != 'Y')) )
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// only authenticated members can post new articles, and only if submissions are accepted
elseif(!isset($item['id']) && Surfer::is_member() && (!isset($context['users_without_submission']) || ($context['users_without_submission'] != 'Y')))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// do not always show the edition form
$with_form = FALSE;

// load the skin, maybe with a variant
load_skin('articles', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'articles/' => i18n::s('All pages') );
if(isset($item['id']) && isset($item['title']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Articles::get_permalink($item) => $item['title']));

// page title
if(isset($item['id']))
	$context['page_title'] = sprintf(i18n::s('Edit: %s'), $item['title']);
elseif(!is_object($overlay) || (!$context['page_title'] = $overlay->get_label('new_command')))
	$context['page_title'] = i18n::s('Add a page');

// save data in session, if any, to pass through login step or through section selection step
if(!Surfer::is_logged() || !is_object($anchor)) {
	if(isset($_REQUEST['anchor']) && $_REQUEST['anchor'])
		$_SESSION['anchor_reference'] = $_REQUEST['anchor'];
	if(isset($_REQUEST['blogid']) && $_REQUEST['blogid'])
		$_SESSION['pasted_blogid'] = $_REQUEST['blogid'];
	if(isset($_REQUEST['introduction']) && $_REQUEST['introduction'])
		$_SESSION['pasted_introduction'] = utf8::encode($_REQUEST['introduction']);
	if(isset($_REQUEST['name']) && $_REQUEST['name'])
		$_SESSION['pasted_name'] = $_REQUEST['name'];
	if(isset($_REQUEST['section']) && $_REQUEST['section'])
		$_SESSION['pasted_section'] = $_REQUEST['section'];
	if(isset($_REQUEST['source']) && $_REQUEST['source'])
		$_SESSION['pasted_source'] = utf8::encode($_REQUEST['source']);
	if(isset($_REQUEST['text']) && $_REQUEST['text'])
		$_SESSION['pasted_text'] = utf8::encode($_REQUEST['text']);
	if(isset($_REQUEST['title']) && $_REQUEST['title'])
		$_SESSION['pasted_title'] = utf8::encode($_REQUEST['title']);
	if(isset($_REQUEST['variant']) && $_REQUEST['variant'])
		$_SESSION['pasted_variant'] = $_REQUEST['variant'];
}

// validate input syntax
if(!Surfer::is_associate() || (isset($_REQUEST['option_validate']) && ($_REQUEST['option_validate'] == 'Y'))) {
	if(isset($_REQUEST['introduction']))
		xml::validate($_REQUEST['introduction']);
	if(isset($_REQUEST['description']))
		xml::validate($_REQUEST['description']);
}

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged()) {

		if(isset($item['id']))
			$link = Articles::get_url($item['id'], 'edit');
		elseif(isset($_REQUEST['anchor']))
			$link = 'articles/edit.php?anchor='.$_REQUEST['anchor'];
		else
			$link = 'articles/edit.php';

		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode($link));
	}

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// an anchor is mandatory
} elseif(!is_object($anchor) && !isset($item['id'])) {
	$context['text'] .= '<p>'.i18n::s('Please carefully select a section for your page.')."</p>\n";

	// no need for a title yet
	$with_title = FALSE;

	// list assigned sections, if any
	include_once '../sections/layout_sections_as_select.php';
	$layout =& new Layout_sections_as_select();
	if(($assigned = Surfer::assigned_sections()) && count($assigned)) {

		// one section at a time
		$items = array();
		foreach($assigned as $assigned_id) {
			if($item = Sections::get($assigned_id)) {

				// strip locked sections, except to associates and editors
				if(isset($item['locked']) && ($item['locked'] == 'Y') && !Surfer::is_empowered())
					continue;

				// format this item
				$items = array_merge($items, $layout->one($item));

			}
		}

		// one box for all sections
		if(count($items)) {
			$context['text'] .= Skin::build_box(i18n::s('Your sections'), Skin::build_list($items, '2-columns'), 'header1', 'assigned_sections');
			$with_title = TRUE;

		}

	}

	// list regular top-level sections
	if($items = Sections::list_by_title_for_anchor(NULL, 0, 20, $layout)) {

		if(count($items))
			$items = Skin::build_list($items, '2-columns');

		$title = '';
		if($with_title)
			$title = i18n::s('Regular sections');

		$context['text'] .= Skin::build_box($title, $items, 'header1', 'regular_sections');

	} else
		$context['text'] .= '<p>'.sprintf(i18n::s('Use the %s to populate this server.'), Skin::build_link('help/populate.php', i18n::s('Content Assistant'), 'shortcut')).'</p>';

	// also list special sections to associates
	if(Surfer::is_associate()) {

		// query the database and layout that stuff
		if($text = Sections::list_inactive_by_title_for_anchor(NULL, 0, 50, $layout)) {

			// we have an array to format
			if(is_array($text))
				$text =& Skin::build_list($text, '2-columns');

			// displayed as another box
			$context['text'] .= Skin::build_box(i18n::s('Other sections'), $text, 'header1', 'other_sections');

		}
	}


// maybe posts are not allowed here
} elseif(!isset($item['id']) && (is_object($anchor) && $anchor->has_option('locked')) && !Surfer::is_empowered()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('This web space has been locked, and you cannot submit a new page.'));

// maybe this page cannot be modified anymore
} elseif(isset($item['locked']) && ($item['locked'] == 'Y') && !Surfer::is_empowered()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('This page has been locked and you are not allowed to modify it.'));

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// protect from hackers
	if(isset($_REQUEST['edit_name']))
		$_REQUEST['edit_name'] = preg_replace(FORBIDDEN_IN_NAMES, '_', $_REQUEST['edit_name']);
	if(isset($_REQUEST['edit_address']))
		$_REQUEST['edit_address'] =& encode_link($_REQUEST['edit_address']);

	// track anonymous surfers
	Surfer::track($_REQUEST);

	// only authenticated surfers are allowed to post links
	if(!Surfer::is_logged())
		$_REQUEST['description'] = preg_replace('/(http:|https:|ftp:|mailto:)[\w@\/\.]+/', '!!!', $_REQUEST['description']);

	// set options
	if(!isset($_REQUEST['options']))
		$_REQUEST['options'] = '';
	if(isset($_REQUEST['option_formatted']) && ($_REQUEST['option_formatted'] == 'Y'))
		$_REQUEST['options'] .= ' formatted';
	if(isset($_REQUEST['option_hardcoded']) && ($_REQUEST['option_hardcoded'] == 'Y'))
		$_REQUEST['options'] .= ' hardcoded';

	// associates are allowed to change overlay types -- see overlays/select.php
	if(isset($_REQUEST['overlay_type']) && $_REQUEST['overlay_type'] && Surfer::is_associate()) {

		// delete the previous version, if any
		if(is_object($overlay)) {

			// allow back-referencing from overlay
			if(isset($_REQUEST['id'])) {
				$_REQUEST['self_reference'] = 'article:'.$_REQUEST['id'];
				$_REQUEST['self_url'] = $context['url_to_root'].Articles::get_permalink($_REQUEST);
				$overlay->remember('delete', $_REQUEST);
			}
		}

		// new version of page overlay
		$overlay = Overlay::bind($_REQUEST['overlay_type']);
	}

	// when the page has been overlaid
	if(is_object($overlay)) {

		// update the overlay from form content
		$overlay->parse_fields($_REQUEST);

		// save content of the overlay in this item
		$_REQUEST['overlay'] = $overlay->save();
		$_REQUEST['overlay_id'] = $overlay->get_id();
	}

	// this is an explicit draft
	if(isset($_REQUEST['option_draft']) && ($_REQUEST['option_draft'] == 'Y'))
		unset($_REQUEST['publish_date']);

	// this is a modification
	elseif(isset($_REQUEST['id']))
		unset($_REQUEST['publish_date']);

	// auto-publish, if requested to do so
	elseif((isset($context['users_with_auto_publish']) && ($context['users_with_auto_publish'] == 'Y')) || (is_object($anchor) && $anchor->has_option('auto_publish')))
		$_REQUEST['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');

	// this is an implicit draft page
	else
		unset($_REQUEST['publish_date']);

	// stop robots
	if(Surfer::may_be_a_robot()) {
		Skin::error(i18n::s('Please prove you are not a robot.'));
		$item = $_REQUEST;
		$with_form = TRUE;

	// update an existing page
	} elseif(isset($_REQUEST['id'])) {

		// allow back-referencing from overlay
		$_REQUEST['self_reference'] = 'article:'.$_REQUEST['id'];
		$_REQUEST['self_url'] = $context['url_to_root'].Articles::get_permalink($_REQUEST);

		// remember the previous version
		if($item['id']) {
			include_once '../versions/versions.php';
			Versions::save($item, 'article:'.$item['id']);
		}

		// change to another overlay
		if(isset($_REQUEST['overlay_type']) && $_REQUEST['overlay_type'] && Surfer::is_associate())
			$action = 'insert';

		// regular update
		else
			$action = 'update';

		// stop on error
		if(!Articles::put($_REQUEST) || (is_object($overlay) && !$overlay->remember($action, $_REQUEST))) {
			$item = $_REQUEST;
			$with_form = TRUE;

		// else display the updated page
		} else {

			// cascade changes on access rights
			if($_REQUEST['active'] != $item['active'])
				Anchors::cascade('article:'.$item['id'], $_REQUEST['active']);

			// touch the related anchor
			if(is_object($anchor))
				$anchor->touch('article:update', $item['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y') );

			// clear cache
			Articles::clear($_REQUEST);

			// add this page to poster watch list
			if(Surfer::get_id())
				Members::assign('article:'.$item['id'], 'user:'.Surfer::get_id());

			// display the updated page
			Safe::redirect($context['url_to_home'].$context['url_to_root'].Articles::get_permalink($item));
		}


	// create a new page
	} elseif(!$_REQUEST['id'] = Articles::post($_REQUEST)) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// successful post
	} else {

		// allow back-referencing from overlay
		$_REQUEST['self_reference'] = 'article:'.$_REQUEST['id'];
		$_REQUEST['self_url'] = $context['url_to_root'].Articles::get_permalink($_REQUEST);

		// post an overlay, with the new article id --don't stop on error
		if(is_object($overlay))
			$overlay->remember('insert', $_REQUEST);

		// touch the related anchor
		$anchor->touch('article:create', $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

		// clear cache
		Articles::clear($_REQUEST);

		// if poster is a registered user
		if(Surfer::get_id()) {

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// add this page to watch list
			Members::assign('article:'.$_REQUEST['id'], 'user:'.Surfer::get_id());

		}

		// get the new item
		$article = Anchors::get('article:'.$_REQUEST['id'], TRUE);

		// page title
		$context['page_title'] = i18n::s('Thank you for your contribution');

		// the page has been published
		if(isset($_REQUEST['publish_date']) && ($_REQUEST['publish_date'] > NULL_DATE))
			$context['text'] .= i18n::s('<p>The new page has been successfully published. Please review it now to ensure that it reflects your mind.</p>');

		// remind that the page has to be published
		elseif(Surfer::is_empowered())
			$context['text'] .= i18n::s('<p>Don\'t forget to publish the new page someday. Review the page, enhance it and then click on the Publish command to make it publicly available.</p>');

		// section has for auto-publish, but the surfer has posted a draft document
		elseif((isset($context['users_with_auto_publish']) && ($context['users_with_auto_publish'] == 'Y')) || (is_object($anchor) && $anchor->has_option('auto_publish')))
			$context['text'] .= i18n::s('<p>Don\'t forget to publish the new page someday. Review the page, enhance it and then click on the Publish command to make it publicly available.</p>');

		// reward regular members
		else
			$context['text'] .= i18n::s('<p>The new page will now be reviewed before its publication. It is likely that this will be done within the next 24 hours at the latest.</p>');

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array($article->get_url() => i18n::s('View the page')));
		if(Surfer::may_upload()) {
			$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('article:'.$_REQUEST['id']) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('files/edit.php?anchor='.urlencode('article:'.$_REQUEST['id']) => i18n::s('Upload a file')));
		}
		$menu = array_merge($menu, array('links/edit.php?anchor='.urlencode('article:'.$_REQUEST['id']) => i18n::s('Add a link')));
		if((!isset($_REQUEST['publish_date']) || ($_REQUEST['publish_date'] <= NULL_DATE)) && Surfer::is_empowered())
			$menu = array_merge($menu, array(Articles::get_url($_REQUEST['id'], 'publish') => i18n::s('Publish the page')));
		if(Surfer::get_email_address() && isset($context['with_email']) && ($context['with_email'] == 'Y'))
			$menu = array_merge($menu, array(Articles::get_url($_REQUEST['id'], 'mail') => i18n::s('Invite people')));
		if(is_object($anchor) && Surfer::is_empowered())
			$menu = array_merge($menu, array('articles/edit.php?anchor='.urlencode($anchor->get_reference()) => i18n::s('Add another page')));
		$follow_up .= Skin::build_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		// log the creation of a new page
		if(!Surfer::is_empowered())
			$label = sprintf(i18n::c('New submission: %s'), strip_tags($article->get_title()));
		else
			$label = sprintf(i18n::c('New page: %s'), strip_tags($article->get_title()));

		// poster and target section
		if(is_object($anchor))
			$description = sprintf(i18n::s('Sent by %s in %s'), Surfer::get_name(), $anchor->get_title())."\n\n";
		else
			$description = sprintf(i18n::s('Sent by %s'), Surfer::get_name())."\n\n";

		// title and link
		if($title = $article->get_title())
			$description .= $title."\n";
		$description = $context['url_to_home'].$context['url_to_root'].$article->get_url()."\n\n";

		// teaser
		if($teaser = $article->get_teaser('basic'))
			$description .= "\n\n".$teaser."\n\n";

		// notify sysops
		Logger::notify('articles/edit.php', $label, $description);

	}

// we have to duplicate some template page
} elseif(!isset($item['id']) && is_object($anchor) && isset($_REQUEST['template']) && ($item = Articles::get($_REQUEST['template']))) {

	// ensure we are not duplicating outside regular templates
	if((!$templates = Anchors::get($item['anchor'])) || ($templates->get_nick_name() != 'templates')) {
		Safe::header('Status: 403 Forbidden', TRUE, 403);
		die(i18n::s('You are not allowed to perform this operation.'));
	}

	// we will get a new id, a new title and a new handle
	unset($item['title']);
	unset($item['id']);
	unset($item['handle']);

	// set the anchor
	$item['anchor'] = $anchor->get_reference();

	// the duplicator becomes the author
	unset($item['create_address']);
	unset($item['create_date']);
	unset($item['create_id']);
	unset($item['create_name']);

	unset($item['edit_address']);
	unset($item['edit_date']);
	unset($item['edit_id']);
	unset($item['edit_name']);

	// drop model introduction and nick name
	unset($item['introduction']);
	unset($item['nick_name']);

	// also duplicate the provided overlay, if any -- re-use 'overlay_type' only
	$overlay = Overlay::load($item);

	// let the surfer do the rest
	$with_form = TRUE;

// select among available templates
} elseif(!isset($item['id']) && is_object($anchor) && ($templates = $anchor->get_templates_for('article')) && ($items =& Articles::list_by_title_for_ids($templates, 'select'))) {

	// remember current anchor, it will not be part of next click
	$_SESSION['anchor_reference'] = $anchor->get_reference();

	// we have only one model available, use it
	if(count($items) == 1) {
		foreach($items as $url => $attributes)
			Safe::redirect($context['url_to_home'].$context['url_to_root'].$url);

	// let the surfer select among available models
	} else {
		$context['text'] .= '<p>'.i18n::s('Please carefully select a model for your page.')."</p>\n";

		$context['text'] .= Skin::build_list($items, '2-columns');
	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// the form to edit an article
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// form fields
	$fields = array();

	//
	// information tab - the main part of the form
	//
	$information = '';

	// additional fields for anonymous surfers
	if(!Surfer::is_logged()) {

		// splash
		if(isset($item['id']))
			$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('articles/edit.php?id='.$item['id']);
		elseif(is_object($anchor))
			$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('articles/edit.php?anchor='.$anchor->get_reference());
		else
			$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('articles/edit.php');
		$information .= '<p>'.sprintf(i18n::s('If you have previously registered to this site, please %s. Then the server will automatically put your name and address in following fields.'), Skin::build_link($login_url, 'authenticate'))."</p>\n";

		// the name, if any
		$label = i18n::s('Your name').' *';
		$input = '<input type="text" name="edit_name" size="45" maxlength="128" accesskey="n" value="'.encode_field(Surfer::get_name()).'" />';
		$hint = i18n::s('Let us a chance to know who you are');
		$fields[] = array($label, $input, $hint);

		// the address, if any
		$label = i18n::s('Your e-mail address');
		$input = '<input type="text" name="edit_address" size="45" maxlength="128" accesskey="a" value="'.encode_field(Surfer::get_email_address()).'" />';
		$hint = i18n::s('Put your e-mail address to receive feed-back');
		$fields[] = array($label, $input, $hint);

		// stop robots
		if($field = Surfer::get_robot_stopper())
			$fields[] = $field;

	}

	// the title
	if(!is_object($overlay) || !($label = $overlay->get_label('title', isset($item['id'])?'edit':'new')))
		$label = i18n::s('Title').' *';
	$value = '';
	if(isset($item['title']) && $item['title'])
		$value = $item['title'];
	elseif(isset($_SESSION['pasted_title']))
		$value = $_SESSION['pasted_title'];
	$input = '<textarea name="title" id="title" rows="2" cols="50" accesskey="t">'.encode_field($value).'</textarea>';
	if(!is_object($overlay) || !($hint = $overlay->get_label('title_hint', isset($item['id'])?'edit':'new')))
		$hint = i18n::s('Please provide a meaningful title.');
	$fields[] = array($label, $input, $hint);

	// the introduction
	$label = i18n::s('Introduction');
	$value = '';
	if(isset($item['introduction']) && $item['introduction'])
		$value = $item['introduction'];
	elseif(isset($_SESSION['pasted_introduction']))
		$value = $_SESSION['pasted_introduction'];
	$input = '<textarea name="introduction" rows="3" cols="50" accesskey="i">'.encode_field($value).'</textarea>';
	$hint = i18n::s('Also complements the title in lists featuring this page');
	$fields[] = array($label, $input, $hint);

	// include overlay fields, if any
	if(is_object($overlay)) {

		// append editing fields for this overlay
		$fields = array_merge($fields, $overlay->get_fields($item));

	}

	// the description label
	if(!is_object($overlay) || !($label = $overlay->get_label('description', isset($item['id'])?'edit':'new')))
		$label = i18n::s('Description');

	// use the editor if possible
	$value = '';
	if(isset($item['description']) && $item['description'])
		$value = $item['description'];
	elseif(isset($_SESSION['pasted_text']))
		$value = $_SESSION['pasted_text'];
	$input = Surfer::get_editor('description', $value);
	$fields[] = array($label, $input);

	// tags
	$label = i18n::s('Tags');
	$input = '<input type="text" name="tags" id="tags" value="'.encode_field(isset($item['tags'])?$item['tags']:'').'" size="45" maxlength="255" accesskey="t" /><div id="tags_choices" class="autocomplete"></div>';
	$hint = i18n::s('A comma-separated list of keywords');
	$fields[] = array($label, $input, $hint);

	// end of regular fields
	$information .= Skin::build_form($fields);
	$fields = array();

	// splash message for new items
	if(!isset($item['id']))
		$information .= '<p>'.i18n::s('Hit the submit button and post images afterwards.').'</p>';

	// images
	else {
		$box = '';

		if(Surfer::may_upload()) {

			// an horizontal table
			$cells = array();

			// the command to upload a regular image
			$cells[] = Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']), i18n::s('Add an image'), 'basic')
				.BR.'<span class="details">'.i18n::s('Upload an image file and integrate it into the page. Big images will be rendered as clickable thumbnails.').'</span>';

			// the command to upload page thumbnail
			if(isset($item['thumbnail_url']) && $item['thumbnail_url'])
				$cells[] = Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']).'&amp;action=thumbnail', i18n::s('Change page thumbnail').BR.'<img src="'.$item['thumbnail_url'].'" alt="" />', 'basic');
			else
				$cells[] = Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']).'&amp;action=thumbnail', i18n::s('Add page thumbnail'), 'basic')
					.BR.'<span class="details">'.i18n::s('Upload a small image to illustrate this page when it is listed into parent page.').'</span>';

			// the command to upload page icon
			if(isset($item['icon_url']) && $item['icon_url'])
				$cells[] = Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']).'&amp;action=icon', i18n::s('Change page icon').BR.'<img src="'.preg_replace('/\/images\/article\/[0-9]+\//', "\\0thumbs/", $item['icon_url']).'" alt="" />', 'basic');
			else
				$cells[] = Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']).'&amp;action=icon', i18n::s('Add page icon'), 'basic')
					.BR.'<span class="details">'.i18n::s('Upload an image to be displayed at page top. This will also be the default icon image for items attached to this page.').'</span>';

			// display all commands
			$box .= Skin::table_prefix('form').'<tr><td style="width: 200px">'.implode('</td><td style="padding-left: 3em; width: 200px">', $cells).'</td></tr></table>';

			// the list of images
			include_once '../images/images.php';
			if($items = Images::list_by_date_for_anchor('article:'.$item['id'], 0, 50, NULL)) {

				// help to insert in textarea
				if(!isset($_SESSION['surfer_editor']) || ($_SESSION['surfer_editor'] == 'yacs'))
					$box .= '<p>'.i18n::s('Click on codes to insert images in the page.')."</p>\n";
				else
					$box .= '<p>'.i18n::s('Use codes to insert images in the page.')."</p>\n";

				$box .= Skin::build_list($items, 'decorated');
			}

		}

		// in a folded box
		if($box)
			$information .= Skin::build_box(i18n::s('Images'), $box, 'folder', 'edit_images');

	}

	// if we are editing an existing article
	if(isset($item['id'])) {

		// locations are reserved to authenticated members
		if(Surfer::is_member()) {

			include_once '../locations/locations.php';
			$menu = array( 'locations/edit.php?anchor='.urlencode('article:'.$item['id']) => i18n::s('Add a location') );
			$items = Locations::list_by_date_for_anchor('article:'.$item['id'], 0, 50, NULL);
			$information .= Skin::build_box(i18n::s('Locations'), Skin::build_list($menu, 'menu_bar').Skin::build_list($items, 'decorated'), 'folder');

		}

		// tables are reserved to associates
		if(Surfer::is_associate()) {

			include_once '../tables/tables.php';
			$menu = array( 'tables/edit.php?anchor='.urlencode('article:'.$item['id']) => i18n::s('Add a table') );
			$items = Tables::list_by_date_for_anchor('article:'.$item['id'], 0, 50, NULL);
			$information .= Skin::build_box(i18n::s('Tables'), Skin::build_list($menu, 'menu_bar').Skin::build_list($items, 'decorated'), 'folder');

		}
	}

	// rendering options
	if(Surfer::is_empowered() && Surfer::is_member()) {
		$label = i18n::s('Rendering');
		$input = '<input type="text" name="options" id="options" size="55" value="'.encode_field(isset($item['options']) ? $item['options'] : '').'" maxlength="255" accesskey="o" />'
			.'<script type="text/javascript">// <![CDATA['."\n"
			.'function append_to_options(keyword) {'."\n"
			.'	var target = $("options");'."\n"
			.'	target.value = target.value + " " + keyword;'."\n"
			.'}'."\n"
			.'// ]]></script>'."\n";
		$keywords = array();
		$keywords[] = '<a onclick="append_to_options(\'anonymous_edit\')" style="cursor: pointer;">anonymous_edit</a> - '.i18n::s('Allow anonymous surfers to edit content');
		$keywords[] = '<a onclick="append_to_options(\'members_edit\')" style="cursor: pointer;">members_edit</a> - '.i18n::s('Allow members to edit content');
		$keywords[] = '<a onclick="append_to_options(\'no_files\')" style="cursor: pointer;">no_files</a> - '.i18n::s('Prevent the upload of new files');
		$keywords[] = '<a onclick="append_to_options(\'files_by_title\')" style="cursor: pointer;">files_by_title</a> - '.i18n::s('Sort files by title (and not by date)');
		$keywords[] = '<a onclick="append_to_options(\'no_links\')" style="cursor: pointer;">no_links</a> - '.i18n::s('Prevent the addition of related links');
		$keywords[] = '<a onclick="append_to_options(\'links_by_title\')" style="cursor: pointer;">links_by_title</a> - '.i18n::s('Sort links by title (and not by date)');
		$keywords[] = '<a onclick="append_to_options(\'no_comments\')" style="cursor: pointer;">no_comments</a> - '.i18n::s('Prevent the addition of comments');
		$keywords[] = '<a onclick="append_to_options(\'view_as_thread\')" style="cursor: pointer;">view_as_thread</a> - '.i18n::s('Real-time collaboration');
		$keywords[] = '<a onclick="append_to_options(\'view_as_tabs\')" style="cursor: pointer;">view_as_tabs</a> - '.i18n::s('Tabbed panels');
		$keywords[] = 'view_as_foo_bar - '.sprintf(i18n::s('Branch out to %s'), 'articles/view_as_foo_bar.php');
		$keywords[] = 'skin_foo_bar - '.i18n::s('Apply a specific skin (in skins/foo_bar) here');
		$keywords[] = 'variant_foo_bar - '.i18n::s('To load template_foo_bar.php instead of the regular skin template');
		$hint = i18n::s('You may combine several keywords:').Skin::finalize_list($keywords, 'compact');
		$fields[] = array($label, $input, $hint);
	}

	// trailer information
	$label = i18n::s('Trailer');
	$input = Surfer::get_editor('trailer', isset($item['trailer'])?$item['trailer']:'');
	$hint = i18n::s('Text to be appended at the bottom of the page, after all other elements attached to this page.');
	$fields[] = array($label, $input, $hint);

	// extra information
	$label = i18n::s('Extra');
	$input = '<textarea name="extra" rows="6" cols="50">'.encode_field(isset($item['extra']) ? $item['extra'] : '').'</textarea>';
	$hint = i18n::s('Text to be inserted in the panel aside the page.');
	$fields[] = array($label, $input, $hint);

	// add a folded box
	$information .= Skin::build_box(i18n::s('More content'), Skin::build_form($fields), 'folder');
	$fields = array();

	//
	// content tab - management of the content tree
	//
	$content = '';

	// this page
	//

	// the section, if one has not been defined yet or when an associate may move one
	if(!is_object($anchor) || (isset($item['id']) && Surfer::is_associate())) {
		$label = i18n::s('Section');
		$input = '<select name="anchor">'.Sections::get_options($item['anchor'] ? $item['anchor'] : $_REQUEST['anchor']).'</select>';
		$hint = i18n::s('Please carefully select a section for your page.');
		$fields[] = array($label, $input, $hint);
	} elseif(is_object($anchor))
		$content .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';

	// the source
	$label = i18n::s('Source');
	$value = '';
	if(isset($item['source']) && $item['source'])
		$value = $item['source'];
	elseif(isset($_SESSION['pasted_source']))
		$value = $_SESSION['pasted_source'];
	$input = '<input type="text" name="source" value="'.encode_field($value).'" size="45" maxlength="255" accesskey="e" />';
	$hint = i18n::s('Mention your source, if any. Web link (http://...), internal reference ([user=tom]), or free text.');
	$fields[] = array($label, $input, $hint);

	// the nick name
	if(Surfer::is_empowered() && Surfer::is_member()) {
		$label = i18n::s('Nick name');
		$value = '';
		if(isset($item['nick_name']) && $item['nick_name'])
			$value = $item['nick_name'];
		elseif(isset($_SESSION['pasted_name']))
			$value = $_SESSION['pasted_name'];
		$input = '<input type="text" name="nick_name" size="32" value="'.encode_field($value).'" maxlength="64" accesskey="n" />';
		$hint = sprintf(i18n::s('To designate a page by its name in the %s'), Skin::build_link('go.php', 'page selector', 'help'));
		$fields[] = array($label, $input, $hint);
	}

// 	// behaviors
// 	if(Surfer::is_empowered() && Surfer::is_member()) {
// 		$label = i18n::s('Behaviors');
// 		$input = '<textarea name="behaviors" rows="2" cols="50">'.encode_field(isset($item['behaviors']) ? $item['behaviors'] : '').'</textarea>';
// 		$hint = sprintf(i18n::s('One %s per line'), Skin::build_link('behaviors/', i18n::s('behavior'), 'help'));
// 		$fields[] = array($label, $input, $hint);
// 	}

	// fields related to this page
	$content .= Skin::build_form($fields);
	$fields = array();

	// the prefix
	if(Surfer::is_associate()) {
		$label = i18n::s('Prefix');
		$input = '<textarea name="prefix" rows="2" cols="50">'.encode_field(isset($item['prefix']) ? $item['prefix'] : '').'</textarea>';
		$hint = i18n::s('To be inserted at the top of related pages.');
		$fields[] = array($label, $input, $hint);
	}

	// the suffix
	if(Surfer::is_associate()) {
		$label = i18n::s('Suffix');
		$input = '<textarea name="suffix" rows="2" cols="50">'.encode_field(isset($item['suffix']) ? $item['suffix'] : '').'</textarea>';
		$hint = i18n::s('To be inserted at the bottom of related pages.');
		$fields[] = array($label, $input, $hint);
	}

	// add a folded box
	if(count($fields))
		$content .= Skin::build_box(i18n::s('Attachments'), Skin::build_form($fields), 'folder');
	$fields = array();

	// home panel
	$front = '';
	if(!isset($item['active']) || ($item['active'] == 'Y')) {
		$front = i18n::s('This page should be:').BR;
		$front .= '<input type="radio" name="home_panel" value="main"';
		if(!isset($item['home_panel']) || ($item['home_panel'] == '') || ($item['home_panel'] == 'main'))
			$front .= ' checked="checked"';
		$front .= '/> '.i18n::s('processed as usual, according to section settings')
			.BR.'<input type="radio" name="home_panel" value="none"';
		if(isset($item['home_panel']) && ($item['home_panel'] == 'none'))
			$front .= ' checked="checked"';
		$front .= '/> '.i18n::s('not displayed at the front page').' ';

	}

	// add a folded box
	if($front)
		$content .= Skin::build_box(i18n::s('Contribution to the site front page'), $front, 'folder');
	$fields = array();

	//
	// options tab
	//
	$options = '';

	// editors
	if(Surfer::is_associate()) {
		if(isset($item['id']))
			$label = Skin::build_link(Users::get_url('article:'.$item['id'], 'select'), i18n::s('Editors'), 'basic');
		else
			$label = i18n::s('Editors');
		if(isset($item['id']) && ($items =& Members::list_editors_by_name_for_member('article:'.$item['id'], 0, USERS_LIST_SIZE, 'compact')))
			$input =& Skin::build_list($items, 'comma');
		else
			$input = i18n::s('Nobody has been assigned to this page.');
		$hint = i18n::s('Invite people');
		$fields[] = array($label, $input, $hint);
	}

	// the active flag: Yes/public, Restricted/logged, No/associates --we don't care about inheritance, to enable security changes afterwards
	if(Surfer::is_empowered() && Surfer::is_member()) {
		$label = i18n::s('Visibility');

		// maybe a public page
		$input = '<input type="radio" name="active_set" value="Y" accesskey="v"';
		if(!isset($item['active_set']) || ($item['active_set'] == 'Y'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('Anyone may read this page').BR;

		// maybe a restricted page
		$input .= '<input type="radio" name="active_set" value="R"';
		if(isset($item['active_set']) && ($item['active_set'] == 'R'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('Access is restricted to authenticated members').BR;

		// or a hidden page
		$input .= '<input type="radio" name="active_set" value="N"';
		if(isset($item['active_set']) && ($item['active_set'] == 'N'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('Access is restricted to associates and editors')."\n";

		$fields[] = array($label, $input);
	}

	// locked: Yes / No
	$label = i18n::s('Locked');
	$input = '<input type="radio" name="locked" value="N"';
	if(!isset($item['locked']) || ($item['locked'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('No - Contributions are accepted').' '
		.BR.'<input type="radio" name="locked" value="Y"';
	if(isset($item['locked']) && ($item['locked'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Yes - Only associates and editors can modify content');
	$fields[] = array($label, $input);

	// the rank
	if(Surfer::is_empowered() && Surfer::is_member()) {

		// the default value
		if(!isset($item['rank']))
			$item['rank'] = 10000;

		$label = i18n::s('Rank');
		$input = '<input type="text" name="rank" id="rank" size="10" value="'.encode_field($item['rank']).'" maxlength="255" />';
		$hint = sprintf(i18n::s('For %s pages; regular pages are ranked at %s.'),
			'<a href="#" onclick="$(\'rank\').value=10; return false;">'.i18n::s('sticky').'</a>',
			'<a href="#" onclick="$(\'rank\').value=10000; return false;">'.i18n::s('10000').'</a>');
		$fields[] = array($label, $input, $hint);
	}

	// the publication date
	$label = i18n::s('Publication date');
	if(isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE))
		$input = Surfer::from_GMT($item['publish_date']);
	elseif(isset($item['id']) && (Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable()))) {
		Skin::define_img('PUBLISH_ARTICLE_IMG', 'icons/articles/publish.gif');
		$input = Skin::build_link(Articles::get_url($item['id'], 'publish'), PUBLISH_ARTICLE_IMG.i18n::s('Publish'), 'basic');
	} else {
		Skin::define_img('DRAFT_ARTICLE_IMG', 'icons/articles/draft.gif');
		$input = DRAFT_ARTICLE_IMG.i18n::s('not published');
	}
	$fields[] = array($label, $input);

	// the expiry date
	$label = i18n::s('Expiry date');
	if(isset($item['expiry_date']) && ($item['expiry_date'] > NULL_DATE))
		$input = Surfer::from_GMT($item['expiry_date']);
	else
		$input = i18n::s('never');
	$fields[] = array($label, $input);

	// append fields
	$options .= Skin::build_form($fields);
	$fields = array();

	// language of this page
	$label = i18n::s('Language');
	$input = i18n::get_languages_select(isset($item['language'])?$item['language']:'');
	$hint = i18n::s('Select the language used for this page');
	$fields[] = array($label, $input, $hint);

	// meta information
	$label = i18n::s('Meta information');
	$input = '<textarea name="meta" rows="10" cols="50">'.encode_field(isset($item['meta']) ? $item['meta'] : '').'</textarea>';
	$hint = i18n::s('Type here any XHTML tags to be put in page header.');
	$fields[] = array($label, $input, $hint);

	// the thumbnail url may be set after the page has been created
	if(isset($item['id']) && Surfer::is_empowered() && Surfer::is_member()) {
		$label = i18n::s('Thumbnail URL');
		$input = '<input type="text" name="thumbnail_url" size="55" value="'.encode_field(isset($item['thumbnail_url']) ? $item['thumbnail_url'] : '').'" maxlength="255" />';
		if(Surfer::may_upload())
			$input .= ' <span class="details">'.Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']).'&amp;action=thumbnail', i18n::s('Add an image'), 'basic').'</span>';
		$hint = i18n::s('The image that illustrates the page at parent level.');
		$fields[] = array($label, $input, $hint);
	}

	// the icon url may be set after the page has been created
	if(isset($item['id']) && Surfer::is_empowered() && Surfer::is_member()) {
		$label = i18n::s('Icon URL');
		$value = '';
		if(isset($item['icon_url']) && $item['icon_url'])
			$value = $item['icon_url'];
		$input = '<input type="text" name="icon_url" size="55" value="'.encode_field($value).'" maxlength="255" />';
		if(Surfer::may_upload())
			$input .= ' <span class="details">'.Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']).'&amp;action=icon', i18n::s('Add an image'), 'basic').'</span>';
		$hint = i18n::s('The image displayed at page top, and used as the default icon for related items.');
		$fields[] = array($label, $input, $hint);
	}

	// associates can change the overlay --complex interface
	if(Surfer::is_associate() && Surfer::has_all()) {

		// current type
		$overlay_type = '';
		if(is_object($overlay))
			$overlay_type = $overlay->get_type();

		// list overlays available on this system
		$label = i18n::s('Change the overlay');
		$input = '<select name="overlay_type">';
		if($overlay_type) {
			$input .= '<option value="none">('.i18n::s('none').")</option>\n";
			$hint = i18n::s('If you change the overlay you may loose some data.');
		} else {
			$hint = i18n::s('No overlay has been selected yet.');
			$input .= '<option value="" selected="selected">'.i18n::s('none')."</option>\n";
		}
		if ($dir = Safe::opendir($context['path_to_root'].'overlays')) {

			// every php script is an overlay, except index.php, overlay.php, and hooks
			while(($file = Safe::readdir($dir)) !== FALSE) {
				if($file == '.' || $file == '..' || is_dir($context['path_to_root'].'overlays/'.$file))
					continue;
				if($file == 'index.php')
					continue;
				if($file == 'overlay.php')
					continue;
				if(preg_match('/hook\.php$/i', $file))
					continue;
				if(!preg_match('/(.*)\.php$/i', $file, $matches))
					continue;
				$overlays[] = $matches[1];
			}
			Safe::closedir($dir);
			if(@count($overlays)) {
				sort($overlays);
				foreach($overlays as $overlay_name) {
					$selected = '';
					if($overlay_name == $overlay_type)
						$selected = ' selected="selected"';
					$input .= '<option value="'.$overlay_name.'"'.$selected.'>'.$overlay_name."</option>\n";
				}
			}
		}
		$input .= '</select>';
		$fields[] = array($label, $input, $hint);

	// remember overlay type
	} else
		$options .= '<input type="hidden" name="overlay_type" value="'.encode_field($overlay->get_type()).'" />';


	// add a folded box
	$options .= Skin::build_box(i18n::s('More options'), Skin::build_form($fields), 'folder');
	$fields = array();

	//
	// assemble all tabs
	//

	// front-end tabs
	$all_tabs = array(
		array('information_tab', i18n::s('Information'), 'information_panel', $information)
		);

	// append tabs from the overlay, if any
	if(is_object($overlay) && ($more_tabs = $overlay->get_tabs('edit', $item)))
 		$all_tabs = array_merge($all_tabs, $more_tabs);

	// back-end tabs
	$all_tabs = array_merge($all_tabs, array(
		array('content_tab', i18n::s('Content tree'), 'content_panel', $content),
		array('options_tab', i18n::s('Options'), 'options_panel', $options)
		));

	// let YACS do the hard job
	$context['text'] .= Skin::build_tabs($all_tabs);

	//
	// bottom commands
	//
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// cancel button
	if(isset($item['id']))
		$menu[] = Skin::build_link(Articles::get_permalink($item), i18n::s('Cancel'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// several options to check
	$input = array();

	// keep as draft
	if(!isset($item['id']))
		$input[] = '<input type="checkbox" name="option_draft" value="Y" /> '.i18n::s('This is a draft document. Do not publish the page, even if auto-publish has been enabled.');

	// do not remember changes on existing pages -- complex command
	if(isset($item['id']) && Surfer::is_empowered() && Surfer::has_all())
		$input[] = '<input type="checkbox" name="silent" value="Y" /> '.i18n::s('Do not change modification date.');

	// hardcoded
	if(!isset($item['id']))
		$input[] = '<input type="checkbox" name="option_hardcoded" value="Y" /> '.i18n::s('Preserve carriage returns and newlines.');

	// do not apply implicit transformations
	if(!isset($item['id']))
		$input[] = '<input type="checkbox" name="option_formatted" value="Y" /> '.i18n::s('The text has been entirely tagged, and implicit transformations do not apply. YACS codes are processed as usual.');

	// validate page content
	if(Surfer::is_associate())
		$input[] = '<input type="checkbox" name="option_validate" value="Y" checked="checked" /> '.i18n::s('Ensure this post is valid XHTML.');

	// append post-processing options
	if($input)
		$context['text'] .= '<p>'.implode(BR, $input).'</p>';

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	$context['page_footer'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// check that main fields are not empty'."\n"
		.'func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'	// title is mandatory'."\n"
		.'	if(!container.title.value) {'."\n"
		.'		alert("'.i18n::s('Please provide a meaningful title.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n";

	// warning on jumbo size, but only on first post
	if(!isset($item['id']))
		$context['page_footer'] .= '	if(container.description.value.length > 64000){'."\n"
			.'		return confirm("'.i18n::s('Page content exceeds 64,000 characters. Do you confirm you are intended to post a jumbo page?').'");'."\n"
			.'	}'."\n"
			."\n";

	$context['page_footer'] .= '	// successful check'."\n"
		.'	return true;'."\n"
		.'}'."\n"
		."\n"
		.'// detect changes in form'."\n"
		.'func'.'tion detectChanges() {'."\n"
		."\n"
		.'	var nodes = $$("form#main_form input");'."\n"
		.'	for(var index = 0; index < nodes.length; index++) {'."\n"
		.'		var node = nodes[index];'."\n"
		.'		Event.observe(node, "change", function() { $("preferred_editor").disabled = true; });'."\n"
		.'	}'."\n"
		."\n"
		.'	nodes = $$("form#main_form textarea");'."\n"
		.'	for(var index = 0; index < nodes.length; index++) {'."\n"
		.'		var node = nodes[index];'."\n"
		.'		Event.observe(node, "change", function() { $("preferred_editor").disabled = true; });'."\n"
		.'	}'."\n"
		.'}'."\n"
		."\n"
		.'// observe changes in form'."\n"
		.'Event.observe(window, "load", detectChanges);'."\n"
		."\n"
		.'// set the focus on first form field'."\n"
		.'Event.observe(window, "load", function() { $("title").focus() });'."\n"
		."\n"
		.'// enable tags autocompletion'."\n"
		.'Event.observe(window, "load", function() { new Ajax.Autocompleter("tags", "tags_choices", "'.$context['url_to_root'].'categories/complete.php", { paramName: "q", minChars: 1, frequency: 0.4, tokens: "," }); });'."\n"
		.'// ]]></script>'."\n";

	// clear session data now that we have populated the form
	unset($_SESSION['anchor_reference']);
	unset($_SESSION['pasted_blogid']);
	unset($_SESSION['pasted_introduction']);
	unset($_SESSION['pasted_name']);
	unset($_SESSION['pasted_section']);
	unset($_SESSION['pasted_source']);
	unset($_SESSION['pasted_text']);
	unset($_SESSION['pasted_title']);

	// content of the help box
	$help = '';

	// capture help messages from the overlay, if any
	if(is_object($overlay))
		$help .= $overlay->get_label('help', isset($item['id'])?'edit':'new');

	// splash message for new pages
	if(!isset($item['id']))
		$help .= '<p>'.i18n::s('Please type the text of your new page and hit the submit button. You will then be able to post images, files and links on subsequent forms.').'</p>';

	// html and codes
	$help .= '<p>'.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'help'), Skin::build_link('smileys/', i18n::s('smileys'), 'help')).'</p>';

 	// locate mandatory fields
 	$help .= '<p>'.i18n::s('Mandatory fields are marked with a *').'</p>';

 	// change to another editor
	$help .= '<form action=""><p><select name="preferred_editor" id="preferred_editor" onchange="Yacs.setCookie(\'surfer_editor\', this.value); window.location = window.location;">';
	$selected = '';
	if(!isset($_SESSION['surfer_editor']) || ($_SESSION['surfer_editor'] == 'tinymce'))
		$selected = ' selected="selected"';
	$help .= '<option value="tinymce"'.$selected.'>'.i18n::s('TinyMCE')."</option>\n";
	$selected = '';
	if(isset($_SESSION['surfer_editor']) && ($_SESSION['surfer_editor'] == 'fckeditor'))
		$selected = ' selected="selected"';
	$help .= '<option value="fckeditor"'.$selected.'>'.i18n::s('FCKEditor')."</option>\n";
	$selected = '';
	if(isset($_SESSION['surfer_editor']) && ($_SESSION['surfer_editor'] == 'yacs'))
		$selected = ' selected="selected"';
	$help .= '<option value="yacs"'.$selected.'>'.i18n::s('Textarea')."</option>\n";
	$help .= '</select></p></form>';

	// drive associates to the Content Assistant
	if(Surfer::is_associate() && !isset($item['id']))
		$help .= '<p>'.sprintf(i18n::s('Use the %s to populate this server.'), Skin::build_link('help/populate.php', i18n::s('Content Assistant'), 'shortcut')).'</p>'."\n";

	// in a side box
	$context['extra'] .= Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

}

// render the skin
render_skin();

?>