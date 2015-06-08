<?php
/**
 * create a new article or edit an existing one
 *
 * This is the main script used to post a new page, or to modify an existing one.
 *
 * On anonymous usage YACS attempts to stop robots by generating a random string and by asking user to type it.
 *
 * A button-based editor is used for the description field.
 * It's aiming to introduce most common [link=codes]codes/[/link] supported by YACS.
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
 * If no anchor data is provided, the new page will be posted in the section named 'theads'.
 *
 * There is also a special invocation format to be used for direct blogging from bookmarklets,
 * such as the one provided by YACS for each section (see [script]sections/view.php[/script]).
 *
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
 * @tester Manuel Lopez Gallego
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
include_once '../behaviors/behaviors.php';	// input validation
include_once '../images/images.php';
include_once '../links/links.php';
include_once '../locations/locations.php';
include_once '../servers/servers.php';
include_once '../tables/tables.php';
include_once '../versions/versions.php'; // roll-back
include_once '../articles/article.php';

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
$item = Articles::get($id);

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

// the default is to create a thread
if(!is_object($anchor) && !isset($item['id']) && ($reference = Sections::lookup('threads')))
	$anchor = Anchors::get($reference);

// reflect access rights from anchor
if(!isset($item['active']) && is_object($anchor))
	$item['active'] = $anchor->get_active();

// get the related overlay, if any -- overlay_type will be considered later on
$overlay = NULL;
if(isset($item['overlay']) && $item['overlay'])
	$overlay = Overlay::load($item, 'article:'.$item['id']);
elseif(isset($_REQUEST['variant']) && $_REQUEST['variant'])
	$overlay = Overlay::bind($_REQUEST['variant']);
elseif(isset($_SESSION['pasted_variant']) && $_SESSION['pasted_variant']) {
	$overlay = Overlay::bind($_SESSION['pasted_variant']);
	unset($_SESSION['pasted_variant']);

// set a new overlay instance, except if some template has been defined for this anchor
} elseif(!isset($item['id']) && is_object($anchor) && !$anchor->get_value('articles_templates'))
	$overlay = $anchor->get_overlay('content_overlay');

// current edited article as object
$cur_article = new article();
$cur_article->item      = $item;
$cur_article->anchor    = $anchor;
$cur_article->overlay   = $overlay;

// get related behaviors, if any
$behaviors = NULL;
if(isset($item['id']))
	$behaviors = new Behaviors($item, $anchor);

// change default behavior
if(isset($item['id']) && is_object($behaviors) && !$behaviors->allow('articles/edit.php', 'article:'.$item['id']))
	$permitted = FALSE;

// we are allowed to add a new page
elseif(!isset($item['id']) && $anchor->allows('creation','article'))
	$permitted = TRUE;

// we are allowed to modify an existing page
elseif(isset($item['id']) && $cur_article->allows('modification'))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

global $render_overlaid;
$whole_rendering = !$render_overlaid;

// cascade empowerment
if($cur_article->is_owned() || Surfer::is_associate())
	Surfer::empower();

// do not always show the edition form
$with_form = FALSE;

// load the skin, maybe with a variant
load_skin('articles', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// current item
if(isset($item['id']))
	$context['current_item'] = 'article:'.$item['id'];

if($whole_rendering) {
    // path to this page
    $context['path_bar'] = Surfer::get_path_bar($anchor);
    if(isset($item['id']) && isset($item['title']))
	    $context['path_bar'] = array_merge($context['path_bar'], array(Articles::get_permalink($item) => $item['title']));
}    

// page title
if(isset($item['id']))
	$context['page_title'] = sprintf(i18n::s('Edit: %s'), $item['title']);
elseif(!is_object($overlay) || (!$context['page_title'] = $overlay->get_label('new_command', 'articles')))
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

// validate input syntax only if required
if(isset($_REQUEST['option_validate']) && ($_REQUEST['option_validate'] == 'Y')) {
	if(isset($_REQUEST['introduction']))
		xml::validate($_REQUEST['introduction']);
	if(isset($_REQUEST['description']))
		xml::validate($_REQUEST['description']);
}

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an anchor is mandatory
} elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No anchor has been found.'));

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
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// page has been assigned to another person during the last 5 minutes
} elseif(isset($item['assign_id']) && $item['assign_id'] && !Surfer::is($item['assign_id'])
	&& (SQL::strtotime($item['assign_date'])+5*60 >= time())) {

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	$context['text'] .= Skin::build_block(sprintf(i18n::s('This page is currently edited by %s. You have to wait for a new version to be released.'), Users::get_link($item['assign_name'], $item['assign_address'], $item['assign_id'])), 'caution');

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// protect from hackers
	if(isset($_REQUEST['edit_name']))
		$_REQUEST['edit_name'] = preg_replace(FORBIDDEN_IN_NAMES, '_', $_REQUEST['edit_name']);
	if(isset($_REQUEST['edit_address']))
		$_REQUEST['edit_address'] = encode_link($_REQUEST['edit_address']);

	// track anonymous surfers
	Surfer::track($_REQUEST);

	// set options
	if(!isset($_REQUEST['options']))
		$_REQUEST['options'] = '';
	if(isset($_REQUEST['option_formatted']) && ($_REQUEST['option_formatted'] == 'Y'))
		$_REQUEST['options'] .= ' formatted';
	if(isset($_REQUEST['option_hardcoded']) && ($_REQUEST['option_hardcoded'] == 'Y'))
		$_REQUEST['options'] .= ' hardcoded';

	// overlay may have changed
	if(isset($_REQUEST['overlay_type']) && $_REQUEST['overlay_type']) {

		// associates are allowed to change overlay types -- see overlays/select.php
		if(!Surfer::is_associate() && isset($_REQUEST['id']))
			unset($_REQUEST['overlay_type']);

		// overlay type has not changed
		elseif(is_object($overlay) && ($overlay->get_type() == $_REQUEST['overlay_type']))
			unset($_REQUEST['overlay_type']);
	}

	// new overlay type
	if(isset($_REQUEST['overlay_type']) && $_REQUEST['overlay_type']) {

		// delete the previous version, if any
		if(is_object($overlay) && isset($_REQUEST['id']))
			$overlay->remember('delete', $_REQUEST, 'article:'.$_REQUEST['id']);

		// new version of page overlay
		$overlay = Overlay::bind($_REQUEST['overlay_type']);
	}

	// when the page has been overlaid
	if(is_object($overlay)) {

		// allow for change detection
		$overlay->snapshot();

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
		Logger::error(i18n::s('Please prove you are not a robot.'));
		$item = $_REQUEST;
		$with_form = TRUE;

	// branch to another script to save data
	} elseif(isset($_REQUEST['options']) && preg_match('/\bedit_as_[a-zA-Z0-9_\.]+?\b/i', $_REQUEST['options'], $matches) && is_readable($matches[0].'.php')) {
		include $matches[0].'.php';
		return;
	} elseif(is_object($overlay) && ($deputy = $overlay->get_value('edit_as')) && is_readable('edit_as_'.$deputy.'.php')) {
		include 'edit_as_'.$deputy.'.php';
		return;
	} elseif(is_object($anchor) && ($deputy = $anchor->has_option('edit_as')) && is_readable('edit_as_'.$deputy.'.php')) {
		include 'edit_as_'.$deputy.'.php';
		return;

	// update an existing page
	} elseif(isset($_REQUEST['id'])) {

		// remember the previous version
		if($item['id'] && Versions::are_different($item, $_REQUEST))
			Versions::save($item, 'article:'.$item['id']);

		// overlay has been inserted or updated
		if(isset($_REQUEST['overlay_type']) && $_REQUEST['overlay_type'])
			$action = 'insert';
		else
			$action = 'update';

		// stop on error
		if(!Articles::put($_REQUEST) || (is_object($overlay) && !$overlay->remember($action, $_REQUEST, 'article:'.$_REQUEST['id']))) {
			$item = $_REQUEST;
			$with_form = TRUE;

		// else display the updated page
		} else {

			// do whatever is necessary on page update
			Articles::finalize_update($anchor, $_REQUEST, $overlay,
				isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'),
				isset($_REQUEST['notify_watchers']) && ($_REQUEST['notify_watchers'] == 'Y'),
				isset($_REQUEST['notify_followers']) && ($_REQUEST['notify_followers'] == 'Y'));

			// cascade changes on access rights
			if($_REQUEST['active'] != $item['active'])
				Anchors::cascade('article:'.$item['id'], $_REQUEST['active']);

			// the page has been modified
			$context['text'] .= '<p>'.i18n::s('The page has been successfully updated.').'</p>';

			$recipients = Mailer::build_recipients('article:'.$item['id']);
			
			if($render_overlaid) {
			    echo 'post done';
			    die;
			}
			
			// display the updated page
			if(!$recipients)
				Safe::redirect(Articles::get_permalink($item));
						
			// list persons that have been notified
			$context['text'] .= $recipients;

			// follow-up commands
			$follow_up = i18n::s('What do you want to do now?');
			$menu = array();
			$menu = array_merge($menu, array(Articles::get_permalink($_REQUEST) => i18n::s('View the page')));
			if(Surfer::may_upload())
				$menu = array_merge($menu, array('files/edit.php?anchor='.urlencode('article:'.$item['id']) => i18n::s('Add a file')));
			if((!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE)) && Surfer::is_empowered())
				$menu = array_merge($menu, array(Articles::get_url($item['id'], 'publish') => i18n::s('Publish the page')));
			$follow_up .= Skin::build_list($menu, 'menu_bar');
			$context['text'] .= Skin::build_block($follow_up, 'bottom');

		}


	// create a new page
	} elseif(!$_REQUEST['id'] = Articles::post($_REQUEST)) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// successful post
	} else {

		// page title
		$context['page_title'] = i18n::s('Thank you for your contribution');

		// the page has been published
		if(isset($_REQUEST['publish_date']) && ($_REQUEST['publish_date'] > NULL_DATE))
			$context['text'] .= '<p>'.i18n::s('The page has been successfully posted. Please review it now to ensure that it reflects your mind.').'</p>';

		// remind that the page has to be published
		elseif(Surfer::is_empowered())
			$context['text'] .= i18n::s('<p>Don\'t forget to publish the new page someday. Review the page, enhance it and then click on the Publish command to make it publicly available.</p>');

		// section ask for auto-publish, but the surfer has posted a draft document
		elseif((isset($context['users_with_auto_publish']) && ($context['users_with_auto_publish'] == 'Y')) || (is_object($anchor) && $anchor->has_option('auto_publish')))
			$context['text'] .= i18n::s('<p>Don\'t forget to publish the new page someday. Review the page, enhance it and then click on the Publish command to make it publicly available.</p>');

		// reward regular members
		else
			$context['text'] .= i18n::s('<p>The new page will now be reviewed before its publication. It is likely that this will be done within the next 24 hours at the latest.</p>');

		// update the overlay, with the new article id --don't stop on error
		if(is_object($overlay))
			$overlay->remember('insert', $_REQUEST, 'article:'.$_REQUEST['id']);

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// do whatever is necessary on page publication
		if(isset($_REQUEST['publish_date']) && ($_REQUEST['publish_date'] > NULL_DATE)) {

			Articles::finalize_publication($anchor, $_REQUEST, $overlay,
				isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'),
				isset($_REQUEST['notify_followers']) && ($_REQUEST['notify_followers'] == 'Y'));

		// else do whatever is necessary on page submission
		} else
			Articles::finalize_submission($anchor, $_REQUEST, $overlay);

		// get the new item
		$article = Anchors::get('article:'.$_REQUEST['id'], TRUE);

		// list persons that have been notified
		$context['text'] .= Mailer::build_recipients('article:'.$_REQUEST['id']);

		// list endpoints that have been notified
		$context['text'] .= Servers::build_endpoints(i18n::s('Servers that have been notified'));

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array($article->get_url() => i18n::s('View the page')));
		$menu = array_merge($menu, array($article->get_url('edit') => i18n::s('Edit the page')));
		if((!isset($_REQUEST['publish_date']) || ($_REQUEST['publish_date'] <= NULL_DATE)) && Surfer::is_empowered())
			$menu = array_merge($menu, array(Articles::get_url($_REQUEST['id'], 'publish') => i18n::s('Publish the page')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$menu = array();
		if(Surfer::may_upload()) {
			$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('article:'.$_REQUEST['id']) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('files/edit.php?anchor='.urlencode('article:'.$_REQUEST['id']) => i18n::s('Add a file')));
			$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('article:'.$_REQUEST['id']).'&amp;action=thumbnail' => i18n::s('Add a thumbnail')));
		}
		if(is_object($anchor) && Surfer::is_empowered())
			$menu = array_merge($menu, array('articles/edit.php?anchor='.urlencode($anchor->get_reference()) => i18n::s('Add another page')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	}

// we have to duplicate some template page
} elseif(!isset($item['id']) && !is_object($overlay) && is_object($anchor) && isset($_REQUEST['template']) && ($item = Articles::get($_REQUEST['template']))) {

	// ensure we are not duplicating outside regular templates
	if((!$templates = Anchors::get($item['anchor'])) || ($templates->get_nick_name() != 'templates')) {
		Safe::header('Status: 401 Unauthorized', TRUE, 401);
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

	// also duplicate the provided overlay, if any
	$overlay = Overlay::load($item, NULL);

	// let the surfer do the rest
	$with_form = TRUE;

// select among available templates
} elseif(!isset($item['id']) && !is_object($overlay) && is_object($anchor) && ($templates = $anchor->get_templates_for('article')) && ($items =& Articles::list_for_ids($templates, 'select'))) {

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

	// put in $context some elements that can be re-used in articles/edit_as_simple.php and the like
	//

	// build standard assistant-like page bottom
	//

	// available commands
	$menu = array();

        if($whole_rendering) {
            // the submit button
            $menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

            // cancel button
            if(isset($item['id']))
                    $menu[] = Skin::build_link(Articles::get_permalink($item), i18n::s('Cancel'), 'span');
            elseif(is_object($anchor))
                    $menu[] = Skin::build_link($anchor->get_url(), i18n::s('Cancel'), 'span');

        }

        // several options to check
	$suffix = array();

	// keep as draft
	if(!isset($item['id'])) {

		// page would have been published
		if((isset($context['users_with_auto_publish']) && ($context['users_with_auto_publish'] == 'Y'))
			|| (is_object($anchor) && $anchor->has_option('auto_publish')))
			$more = '';

		// page won't be published anyway
		else
			$more = 'disabled="disabled" checked="checked"';

		// the full radio button
		$suffix[] = '<input type="checkbox" name="option_draft" value="Y" '.$more.'/> '.i18n::s('This is a draft document. Do not notify watchers nor followers.');

	// notify watchers
	} else {

		// notify watchers, but not on draft pages
		$with_watchers = (isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE));

		// allow surfer to uncheck notifications
		if($with_watchers)
			$more = 'checked="checked"';

		// no notifications anyway
		else
			$more = 'disabled="disabled"';

		$suffix[] = '<input type="checkbox" name="notify_watchers" value="Y" '.$more.'/> '.i18n::s('Notify watchers');
	}

	// do not remember changes on existing pages -- complex command
	if(isset($item['id']) && Surfer::is_empowered() && Surfer::has_all())
		$suffix[] = '<input type="checkbox" name="silent" value="Y" /> '.i18n::s('Do not change modification date.');

	// validate page content
	if(Surfer::is_associate())
		$suffix[] = '<input type="checkbox" name="option_validate" value="Y" checked="checked" /> '.i18n::s('Ensure this post is valid XHTML.');

	// an assistant-like rendering at page bottom
	$context['page_bottom'] = Skin::build_assistant_bottom('', $menu, $suffix, isset($item['tags'])?$item['tags']:'');
		
	// content of the help box
	//
	if($whole_rendering) {
	    $help = '';

	    // capture help messages from the overlay, if any
	    if(is_object($overlay))
		    $help .= $overlay->get_label('help', isset($item['id'])?'edit':'new');

	    // splash message for new pages
	    if(!isset($item['id']))
		    $help .= '<p>'.i18n::s('Please type the text of your new page and hit the submit button. You will then be able to post images, files and links on subsequent forms.').'</p>';

	    // html and codes
	    $help .= '<p>'.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'open'), Skin::build_link('smileys/', i18n::s('smileys'), 'open')).'</p>';

	    // locate mandatory fields
	    $help .= '<p>'.i18n::s('Mandatory fields are marked with a *').'</p>';

	    // change to another editor
	    $help .= '<form action=""><p><select name="preferred_editor" id="preferred_editor" onchange="Yacs.setCookie(\'surfer_editor\', this.value); window.location = window.location;">';
	    $selected = '';
	    if(!isset($_SESSION['surfer_editor']) || ($_SESSION['surfer_editor'] == 'tinymce'))
		    $selected = ' selected="selected"';
	    $help .= '<option value="tinymce"'.$selected.'>'.i18n::s('TinyMCE')."</option>\n";
	    $selected = '';
	    if(isset($_SESSION['surfer_editor']) && ($_SESSION['surfer_editor'] == 'yacs'))
		    $selected = ' selected="selected"';
	    $help .= '<option value="yacs"'.$selected.'>'.i18n::s('Textarea')."</option>\n";
	    $help .= '</select></p></form>';

	    // in a side box
	    $context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');
	
	}

	// the script used for form handling at the browser
	//	
	$js_script = 	
		// check that main fields are not empty
		'func'.'tion validateDocumentPost(container) {'."\n"
			// title is mandatory
		.'	if(!Yacs.trim(container.title.value)) {'."\n"
		.'		alert("'.i18n::s('Please provide a meaningful title.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		$("#title").focus();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
			// extend validation --used in overlays
		.'	if(typeof validateOnSubmit == "function") {'."\n"
		.'		return validateOnSubmit(container);'."\n"
		.'	}'."\n";

	// warning on jumbo size, but only on first post
	if(!isset($item['id']))
		$js_script .= '	if(container.description.value.length > 64000){'."\n"
			.'		return confirm("'.i18n::s('Page content exceeds 64,000 characters. Do you confirm you are intended to post a jumbo page?').'");'."\n"
			.'	}'."\n"
			."\n";

	$js_script .= 
			// successful check
		'	return true;'."\n"
		.'}'."\n"
		."\n"
		// disable editor selection on change in form
                .'$("#main_form textarea, #main_form input, #main_form select").change(function() {'."\n"
                .'      $("#preferred_editor").attr("disabled",true);'."\n"
                .'});'."\n"
		.'$(function() {'."\n"
		.'	$("#title").focus();'."\n" // set the focus on first form field
		.'  Yacs.autocomplete_m("tags", "'.$context['url_to_root'].'categories/complete.php");'."\n" // enable autocompletion
		.'});';
	
	Page::insert_script($js_script);
		

	// branch to another script to display form fields, tabs, etc
	//
	$branching = '';
	if(isset($item['options']) && preg_match('/\bedit_as_[a-zA-Z0-9_\.]+?\b/i', $item['options'], $matches) && is_readable($matches[0].'.php'))
		$branching = $matches[0].'.php';
    elseif(is_object($overlay) && ($deputy = $overlay->get_value('edit_as')) && is_readable('edit_as_'.$deputy.'.php'))
        $branching = 'edit_as_'.$deputy.'.php';
	elseif(is_object($anchor) && ($deputy = $anchor->has_option('edit_as')) && is_readable('edit_as_'.$deputy.'.php'))
		$branching = 'edit_as_'.$deputy.'.php';

	// branching out
	if($branching) {
		include $branching;
		return;
	}

	// the form to edit an article
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form" enctype="multipart/form-data"><div>';
	$fields = array();

	//
	// panels
	//
	$panels = array();

	//
	// information tab
	//
	$text = '';

	// additional fields for anonymous surfers
	if(!Surfer::is_logged()) {

		// splash
		if(isset($item['id']))
			$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('articles/edit.php?id='.$item['id']);
		elseif(is_object($anchor))
			$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('articles/edit.php?anchor='.$anchor->get_reference());
		else
			$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('articles/edit.php');
		$text .= '<p>'.sprintf(i18n::s('If you have previously registered to this site, please %s. Then the server will automatically put your name and address in following fields.'), Skin::build_link($login_url, i18n::s('authenticate')))."</p>\n";

		// the name, if any
		$label = i18n::s('Your name');
		$input = '<input type="text" name="edit_name" size="45" maxlength="128" accesskey="n" value="'.encode_field(Surfer::get_name(' ')).'" />';
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
	if(!is_object($overlay) || !($label = $overlay->get_label('introduction', isset($item['id'])?'edit':'new')))
		$label = i18n::s('Introduction');
	$value = '';
	if(isset($item['introduction']) && $item['introduction'])
		$value = $item['introduction'];
	elseif(isset($_SESSION['pasted_introduction']))
		$value = $_SESSION['pasted_introduction'];
	$input = '<textarea name="introduction" rows="5" cols="50" accesskey="i">'.encode_field($value).'</textarea>';
	if(!is_object($overlay) || !($hint = $overlay->get_label('introduction_hint', isset($item['id'])?'edit':'new')))
		$hint = i18n::s('Also complements the title in lists featuring this page');
	$fields[] = array($label, $input, $hint);

	// include overlay fields, if any
	if(is_object($overlay))
		$fields = array_merge($fields, $overlay->get_fields($item));

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
	if(!is_object($overlay) || !($hint = $overlay->get_label('description_hint', isset($item['id'])?'edit':'new')))
		$hint = '';
	$fields[] = array($label, $input, $hint);

	// end of regular fields
	$text .= Skin::build_form($fields);
	$fields = array();

	// trailer information
	$label = i18n::s('Trailer');
	$input = Surfer::get_editor('trailer', isset($item['trailer'])?$item['trailer']:'');
	$hint = i18n::s('Text to be appended at the bottom of the page, after all other elements attached to this page.');
	$fields[] = array($label, $input, $hint);

	// the icon url may be set after the page has been created
	if(isset($item['id']) && Surfer::is_empowered() && Surfer::is_member()) {
		$label = i18n::s('Image');
		$input = '';
		$hint = '';

		// show the current icon
		if(isset($item['icon_url']) && $item['icon_url']) {
			$input .= '<img src="'.preg_replace('/\/images\/article\/[0-9]+\//', "\\0thumbs/", $item['icon_url']).'" alt="" />'.BR;
			$command = i18n::s('Change');
		} else {
			$hint .= i18n::s('Image to be displayed in the panel aside the page.');
			$command = i18n::s('Add an image');
		}

		$value = '';
		if(isset($item['icon_url']) && $item['icon_url'])
			$value = $item['icon_url'];
		$input .= '<input type="text" name="icon_url" size="55" value="'.encode_field($value).'" maxlength="255" />';
		if(Surfer::may_upload())
			$input .= ' <span class="details">'.Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']).'&amp;action=icon', $command, 'button').'</span>';
		$fields[] = array($label, $input, $hint);
	}

	// extra information
	$label = i18n::s('Extra');
	$input = Surfer::get_editor('extra', isset($item['extra'])?$item['extra']:'');
	$hint = i18n::s('Text to be inserted in the panel aside the page. Use [box.extra=title]content[/box] or plain HTML.');
	$fields[] = array($label, $input, $hint);

	// add a folded box
	$text .= Skin::build_box(i18n::s('More content'), Skin::build_form($fields), 'folded');
	$fields = array();

	// display in a separate panel
	if($text)
		$panels[] = array('information', i18n::s('Information'), 'information_panel', $text);

	//
	// append tabs from the overlay, if any
	//
	if(is_object($overlay) && ($more_tabs = $overlay->get_tabs('edit', $item)))
 		$panels = array_merge($panels, $more_tabs);

	//
	// resources tab
	//
	$text = '';

	// splash message for new items
	if(!isset($item['id']))
		$text .= '<p>'.i18n::s('Submit the new item, and you will be able to add resources afterwards.');

	// resources attached to this anchor
	else {

		// images
		$box = '';
		if($cur_article->allows('creation','image')) {
			$menu = array( 'images/edit.php?anchor='.urlencode('article:'.$item['id']) => i18n::s('Add an image') );
			$box .= Skin::build_list($menu, 'menu_bar');
		}
		if($items = Images::list_by_date_for_anchor('article:'.$item['id']))
			$box .= Skin::build_list($items, 'decorated');
		if($box)
			$text .= Skin::build_box(i18n::s('Images'), $box, 'folded');

		// files
		$box = '';
		if($cur_article->allows('creation','file')) {
			$menu = array( 'files/edit.php?anchor='.urlencode('article:'.$item['id']) => i18n::s('Add a file') );
			$box .= Skin::build_list($menu, 'menu_bar');
		}
		if($items = Files::list_embeddable_for_anchor('article:'.$item['id'], 0, 50))
			$box .= Skin::build_list($items, 'decorated');
		if($box)
			$text .= Skin::build_box(i18n::s('Files'), $box, 'folded');

		// locations
		$box = '';
		if($cur_article->allows('creation','location')) {
			$menu = array( 'locations/edit.php?anchor='.urlencode('article:'.$item['id']) => i18n::s('Add a location') );
			$box .= Skin::build_list($menu, 'menu_bar');
		}
		if($items = Locations::list_by_date_for_anchor('article:'.$item['id'], 0, 50, 'article:'.$item['id']))
			$box .= Skin::build_list($items, 'decorated');
		if($box)
			$text .= Skin::build_box(i18n::s('Locations'), $box, 'folded');

		// tables
		$box = '';
		if($cur_article->allows('creation','table')) {
			$menu = array( 'tables/edit.php?anchor='.urlencode('article:'.$item['id']) => i18n::s('Add a table') );
			$box .= Skin::build_list($menu, 'menu_bar');
		}
		if($items = Tables::list_by_date_for_anchor('article:'.$item['id'], 0, 50, 'article:'.$item['id']))
			$box .= Skin::build_list($items, 'decorated');
		if($box)
			$text .= Skin::build_box(i18n::s('Tables'), $box, 'folded');

	}

	// display in a separate panel
	if($text)
		$panels[] = array('resources', i18n::s('Resources'), 'resources_panel', $text);

	//
	// options tab
	//
	$text = '';

	// provide information to section owner
	if(isset($item['id'])) {

		// owner
		$label = i18n::s('Owner');
		$input = '';
		if(isset($item['owner_id'])) {
			if($owner = Users::get($item['owner_id']))
				$input = Users::get_link($owner['full_name'], $owner['email'], $owner['id']);
			else
				$input = i18n::s('No owner has been found.');
		}

		// change the owner
		if(Articles::is_owned($item, $anchor) || Surfer::is_associate())
			$input .= ' <span class="details">'.Skin::build_link(Articles::get_url($item['id'], 'own'), i18n::s('Change'), 'button').'</span>';

		$fields[] = array($label, $input);

	}

	// the active flag: Yes/public, Restricted/logged, No/associates --we don't care about inheritance, to enable security changes afterwards
	if( !isset($item['id']) || $cur_article->is_owned() || Surfer::is_associate()) {
		$label = i18n::s('Access');
		$input = Skin::build_active_set_input($item);
		$hint = Skin::build_active_set_hint($anchor);
		$fields[] = array($label, $input, $hint);
	}

	// locked: Yes / No
	$label = i18n::s('Locker');
	$input = '<input type="radio" name="locked" value="N"';
	if(!isset($item['locked']) || ($item['locked'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Contributions are accepted').' '
		.BR.'<input type="radio" name="locked" value="Y"';
	if(isset($item['locked']) && ($item['locked'] == 'Y'))
		$input .= ' checked="checked"';
	if(isset($item['active']) && ($item['active'] == 'N'))
		$input .= '/> '.i18n::s('Only owners and associates can add content');
	else
		$input .= '/> '.i18n::s('Only assigned persons, owners and associates can add content');
	$fields[] = array($label, $input);

	// append fields
	$text .= Skin::build_form($fields);
	$fields = array();

	// the thumbnail url may be set after the page has been created
	if(isset($item['id']) && Surfer::is_empowered() && Surfer::is_member()) {
		$label = i18n::s('Thumbnail');
		$input = '';
		$hint = '';

		// show the current thumbnail
		if(isset($item['thumbnail_url']) && $item['thumbnail_url']) {
			$input .= '<img src="'.$item['thumbnail_url'].'" alt="" />'.BR;
			$command = i18n::s('Change');
		} else {
			$hint .= i18n::s('Upload a small image to illustrate this page when it is listed into parent page.');
			$command = i18n::s('Add an image');
		}

		$input .= '<input type="text" name="thumbnail_url" size="55" value="'.encode_field(isset($item['thumbnail_url']) ? $item['thumbnail_url'] : '').'" maxlength="255" />';
		if(Surfer::may_upload())
			$input .= ' <span class="details">'.Skin::build_link('images/edit.php?anchor='.urlencode('article:'.$item['id']).'&amp;action=thumbnail', $command, 'button').'</span>';
		$fields[] = array($label, $input, $hint);
	}

	// the rank
	if($cur_article->is_owned() || Surfer::is_associate()) {

		// the default value
		if(!isset($item['rank']))
			$item['rank'] = 10000;

		$label = i18n::s('Rank');
		$input = '<input type="text" name="rank" id="rank" size="10" value="'.encode_field($item['rank']).'" maxlength="255" />';
		$hint = sprintf(i18n::s('For %s pages; regular pages are ranked at %s.'),
			'<a href="#" onclick="$(\'#rank\').val(10); return false;">'.i18n::s('sticky').'</a>',
			'<a href="#" onclick="$(\'#rank\').val(10000); return false;">'.i18n::s('10000').'</a>');
		$fields[] = array($label, $input, $hint);
	}

	// the publication date
	$label = i18n::s('Publication date');
	if(isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE))
		$input = Surfer::from_GMT($item['publish_date']);
	elseif(isset($item['id']) && (Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_assigned()))) {
		Skin::define_img('ARTICLES_PUBLISH_IMG', 'articles/publish.gif');
		$input = Skin::build_link(Articles::get_url($item['id'], 'publish'), ARTICLES_PUBLISH_IMG.i18n::s('Publish'), 'basic');
	} else {
		Skin::define_img('ARTICLES_UNPUBLISH_IMG', 'articles/unpublish.gif');
		$input = ARTICLES_UNPUBLISH_IMG.i18n::s('not published');
	}
	$fields[] = array($label, $input);

	// the expiry date
	$label = i18n::s('Expiry date');
	if(isset($item['expiry_date']) && ($item['expiry_date'] > NULL_DATE))
		$input = Surfer::from_GMT($item['expiry_date']);
	else
		$input = i18n::s('never');
	$fields[] = array($label, $input);

	// the parent section
	if(is_object($anchor)) {

		if(isset($item['id']) && $cur_article->is_owned()) {
			$label = i18n::s('Section');
			$input =& Skin::build_box(i18n::s('Select parent container'), Sections::get_radio_buttons($anchor->get_reference()), 'folded');
			$fields[] = array($label, $input);
		} else
			$text .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';

	}

	// append fields
	if(is_object($anchor))
		$label = sprintf(i18n::s('Contribution to "%s"'), $anchor->get_title());
	else
		$label = i18n::s('Contribution to parent container');
	$text .= Skin::build_box($label, Skin::build_form($fields), 'folded');
	$fields = array();

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
	if($cur_article->is_owned() || Surfer::is_associate()) {
		$label = i18n::s('Nick name');
		$value = '';
		if(isset($item['nick_name']) && $item['nick_name'])
			$value = $item['nick_name'];
		elseif(isset($_SESSION['pasted_name']))
			$value = $_SESSION['pasted_name'];
		$input = '<input type="text" name="nick_name" size="32" value="'.encode_field($value).'" maxlength="64" accesskey="n" />';
		$hint = sprintf(i18n::s('To designate a page by its name in the %s'), Skin::build_link('go.php', 'page selector', 'open'));
		$fields[] = array($label, $input, $hint);
	}

	// rendering options
	if($cur_article->is_owned() || Surfer::is_associate()) {
		$label = i18n::s('Rendering');
		$input = Articles::build_options_input($item);
		$hint = Articles::build_options_hint($item);
		$fields[] = array($label, $input, $hint);
	}

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

// 	// behaviors
// 	if(Surfer::is_empowered() && Surfer::is_member()) {
// 		$label = i18n::s('Behaviors');
// 		$input = '<textarea name="behaviors" rows="2" cols="50">'.encode_field(isset($item['behaviors']) ? $item['behaviors'] : '').'</textarea>';
// 		$hint = sprintf(i18n::s('One %s per line'), Skin::build_link('behaviors/', i18n::s('behavior'), 'open'));
// 		$fields[] = array($label, $input, $hint);
// 	}

	// the prefix
// 	if(Surfer::is_associate()) {
// 		$label = i18n::s('Prefix');
// 		$input = '<textarea name="prefix" rows="2" cols="50">'.encode_field(isset($item['prefix']) ? $item['prefix'] : '').'</textarea>';
// 		$hint = i18n::s('To be inserted at the top of related pages.');
// 		$fields[] = array($label, $input, $hint);
// 	}

	// the suffix
// 	if(Surfer::is_associate()) {
// 		$label = i18n::s('Suffix');
// 		$input = '<textarea name="suffix" rows="2" cols="50">'.encode_field(isset($item['suffix']) ? $item['suffix'] : '').'</textarea>';
// 		$hint = i18n::s('To be inserted at the bottom of related pages.');
// 		$fields[] = array($label, $input, $hint);
// 	}

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
				if(($file[0] == '.') || is_dir($context['path_to_root'].'overlays/'.$file))
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
				natsort($overlays);
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
	} elseif(is_object($overlay))
		$text .= '<input type="hidden" name="overlay_type" value="'.encode_field($overlay->get_type()).'" />';

	// reflect content canvas from anchor
	if(!isset($item['canvas']) && is_object($anchor))
		$item['canvas'] = $anchor->get_articles_canvas();

	// associates can change the canvas --complex interface
	if(Surfer::is_associate() && Surfer::has_all()) {

		// list canvas available on this system
		$label = i18n::s('Change the canvas');
		$input = '<select name="canvas">';
		$hint = sprintf(i18n::s('%s used for this article'), Skin::build_link('canvas/', i18n::s('Canvas'), 'open'));
		$canvas = array();
		if ($dir = Safe::opendir($context['path_to_root'].'canvas')) {

			// every php script is an overlay, except index.php, canvas.php, and hooks
			while(($file = Safe::readdir($dir)) !== FALSE) {
				if(($file[0] == '.') || is_dir($context['path_to_root'].'canvas/'.$file))
					continue;
				if($file == 'index.php')
					continue;
				if($file == 'canvas.php')
					continue;
				if(preg_match('/hook\.php$/i', $file))
					continue;
				if(!preg_match('/(.*)\.php$/i', $file, $matches))
					continue;
				$canvas[] = $matches[1];
			}
			Safe::closedir($dir);
			if(@count($canvas)) {
				natsort($canvas);
				foreach($canvas as $canvas_name) {
					$selected = '';
					if($canvas_name == $item['canvas'])
						$selected = ' selected="selected"';
					$input .= '<option value="'.$canvas_name.'"'.$selected.'>'.$canvas_name."</option>\n";
				}
			}
		}
		$input .= '</select>';
		$fields[] = array($label, $input, $hint);

	// remember canvas
	} else
		$text .= '<input type="hidden" name="canvas" value="'.encode_field($item['canvas']).'" />';

	// add a folded box
	$text .= Skin::build_box(i18n::s('More options'), Skin::build_form($fields), 'folded');
	$fields = array();

	// display in a separate panel
	if($text)
		$panels[] = array('options', i18n::s('Options'), 'options_panel', $text);

	//
	// assemble all tabs
	//
	$context['text'] .= Skin::build_tabs($panels);

	//
	// bottom commands
	//
	// assistant-like bottom of the page
	$context['text'] .= $context['page_bottom'];

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// clear session data now that we have populated the form
	unset($_SESSION['anchor_reference']);
	unset($_SESSION['pasted_blogid']);
	unset($_SESSION['pasted_introduction']);
	unset($_SESSION['pasted_name']);
	unset($_SESSION['pasted_section']);
	unset($_SESSION['pasted_source']);
	unset($_SESSION['pasted_text']);
	unset($_SESSION['pasted_title']);

	// assign the page to the surfer
	if(isset($item['id']) && Surfer::get_id()) {
		$query = "UPDATE ".SQL::table_name('articles')." SET "
			." assign_name = '".SQL::escape(Surfer::get_name())."',"
			." assign_id = ".SQL::escape(Surfer::get_id()).","
			." assign_address = '".SQL::escape(Surfer::get_email_address())."',"
			." assign_date = '".SQL::escape(gmstrftime('%Y-%m-%d %H:%M:%S'))."'"
			." WHERE (id  = ".SQL::escape($item['id']).")";

		// do not stop on error
		SQL::query($query);

		// for subsequent heartbits
		$_SESSION['assigned'] = $item['id'];

		// current item
		$context['current_action'] = 'edit';

	}

}

// render the skin
render_skin();

?>
