<?php
/**
 * send a query
 *
 * This script is to be used by anybody, including anonymous surfers,
 * to submit a query to the webmaster.
 *
 * What it actually does is to post an article into the '[code]queries[/code]' section.
 * Therefore, queries are ordinary articles to be handled by associates.
 *
 * On query submission:
 * - The web page displayed to the surfer displays a special link to bookmark the query page.
 * - An e-mail message is sent to the form submitter, for further reference
 * - A message is logged, site admins being notified of the query by e-mail
 *
 * For anonymous surfers, some user data is saved inside the page itself, including:
 * - surfer name
 * - surfer mail address
 *
 * On subsequent access to the query page, using page handle, these data is restored to surfer environment.
 * With this setup, anonymous surfers may interact with a given web page without registering first.
 *
 * YACS attempts to stop robots by generating a random string and by asking user to type it.
 *
 * @author Bernard Paques
 * @tester fw_crocodile
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once 'shared/global.php';

// do not always show the edition form
$with_form = FALSE;

// load localized strings
i18n::bind('root');

// load the skin
load_skin('query');

// the title of the page
$context['page_title'] = i18n::s('Help');

// get a section for queries
if(!$anchor = Anchors::get('section:queries')) {
	$fields = array();
	$fields['nick_name'] = 'queries';
	$fields['title'] = i18n::c('Queries');
	$fields['introduction'] = i18n::c('Submitted to the webmaster by any surfers');
	$fields['description'] = i18n::c('<p>This section has been created automatically on query submission. It\'s aiming to capture feedback directly from surfers. It is highly recommended to delete pages below after their processing. Of course you can edit submitted queries to assign them to other sections if necessary.</p>');
	$fields['locked'] = 'Y'; // no direct contributions
	$fields['active_set'] = 'N'; // for associates only
	$fields['index_map'] = 'N'; // listed only to associates

	// reference the new section
	if($fields['id'] = Sections::post($fields, FALSE))
		$anchor = Anchors::get('section:'.$fields['id']);
}
$_REQUEST['anchor'] = $anchor->get_reference();

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an anchor is mandatory
} elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No anchor has been found.'));

// post a new query
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// protect from hackers
	if(isset($_REQUEST['edit_name']))
		$_REQUEST['edit_name'] = preg_replace(FORBIDDEN_IN_NAMES, '_', $_REQUEST['edit_name']);
	if(isset($_REQUEST['edit_address']))
		$_REQUEST['edit_address'] = encode_link($_REQUEST['edit_address']);

	// track anonymous surfers
	Surfer::track($_REQUEST);

	// this is the exact copy of what end users has typed
	$item = $_REQUEST;

	// from form fields to record columns
	if(!isset($_REQUEST['edit_id']))
		$_REQUEST['edit_id']	= Surfer::get_id();
	$_REQUEST['create_address'] = $_REQUEST['edit_address'];
	$_REQUEST['create_name'] = $_REQUEST['edit_name'];
	if(!$_REQUEST['create_name'])
		$_REQUEST['create_name'] = $_REQUEST['create_address'];
	if(!$_REQUEST['create_name'])
		$_REQUEST['create_name'] = i18n::c('(anonymous)');

	// always auto-publish queries
	$_REQUEST['publish_date']	= gmstrftime('%Y-%m-%d %H:%M:%S');
	if(isset($_REQUEST['edit_id']))
		$_REQUEST['publish_id'] 	= $_REQUEST['edit_id'];
	$_REQUEST['publish_address'] = $_REQUEST['edit_address'];
	$_REQUEST['publish_name']	= $_REQUEST['edit_name'];

	// show e-mail address of anonymous surfer
	if($_REQUEST['edit_address'] && !Surfer::is_logged()) {
		$_REQUEST['description'] = '<p>'.sprintf(i18n::c('Sent by %s'), ($_REQUEST['edit_name']?$_REQUEST['edit_name'].' - ':i18n::c('e-mail').' : ').' [email]'.$_REQUEST['edit_address'].'[/email]')."</p>\n"
			.$_REQUEST['description'];
        
                
                // provide the page a overlay so anonymous surfer could receive notification
                $overlay                        = Overlay::bind('query');
                $_REQUEST['overlay']            = $overlay->save();
        }

	// stop robots
	if(Surfer::may_be_a_robot()) {
		Logger::error(i18n::s('Please prove you are not a robot.'));
		$with_form = TRUE;

	// display the form on error
	} elseif(!$_REQUEST['id'] = Articles::post($_REQUEST)) {
		$with_form = TRUE;

	// post-processing
	} else {
            
		// do whatever is necessary on page publication
		Articles::finalize_publication($anchor, $_REQUEST);

		// message to the query poster
		$context['page_title'] = i18n::s('Your query has been registered');

		// use the secret handle to access the query
		$link = '';
		$status = '';
		if($item = Articles::get($_REQUEST['id'])) {

			// ensure the article has a private handle
			if(!isset($item['handle']) || !$item['handle']) {
				$item['handle'] = md5(mt_rand());

				// save in the database
				$fields = array();
				$fields['id'] = $item['id'];
				$fields['handle'] = $item['handle'];
				$fields['silent'] = 'Y';
				Articles::put_attributes($fields);
			}

			// the secret link --see users/login.php
			$link = $context['url_to_home'].$context['url_to_root'].Users::get_login_url('edit', 'article:'.$item['id'], $item['create_name'], $item['handle']);

			$status = i18n::s('<p>You can check the status of your query at the following address:</p>')
				.'<p>'.Skin::build_link($link, $link, 'basic', i18n::s('The permanent address for your query')).'</p>';

		}

		$context['text'] .= i18n::s('<p>Your query will now be reviewed by one of the associates of this community. It is likely that this will be done within the next 24 hours at the latest.</p>');
		$context['text'] .= $status;

		// follow-up commands
		$follow_up = i18n::s('Where do you want to go now?');
		$menu = array();
		$menu = array_merge($menu, array($context['url_to_root'] => i18n::s('Front page')));
		$menu = array_merge($menu, array('articles/' => i18n::s('All pages')));
		$menu = array_merge($menu, array('sections/' => i18n::s('Site map')));
		$menu = array_merge($menu, array('search.php' => i18n::s('Search')));
		$menu = array_merge($menu, array('help/' => i18n::s('Help index')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		// send a confirmation message to the surfer
		if(isset($_REQUEST['edit_address']) && preg_match('/.+@.+/', $_REQUEST['edit_address']) && $link) {

			// message recipient
			$to = $_REQUEST['edit_address'];

			// message subject
			$subject = sprintf(i18n::s('Your query: %s'), strip_tags($_REQUEST['title']));

			// message body
			$message = sprintf(i18n::s("<p>Your query will now be reviewed by one of the associates of this community. It is likely that this will be done within the next 24 hours at the latest.</p><p>You can check the status of your query at the following address:</p><p>%s</p><p>We would like to thank you for your interest in our web site.</p>"), '<a href="'.$link.'">'.$link.'</a>');

			// enable threading
			if(isset($item['id']))
				$headers = Mailer::set_thread('article:'.$item['id']);
			else
				$headers = '';

			// actual post - don't stop on error
			Mailer::notify(NULL, $to, $subject, $message, $headers);

		}

		// get the article back
		$article = Anchors::get('article:'.$_REQUEST['id']);

		// log the query submission
		if(is_object($article)) {
			$label = sprintf(i18n::c('New query: %s'), strip_tags($article->get_title()));
			$link = $context['url_to_home'].$context['url_to_root'].$article->get_url();
                        $description = '<a href="'.$link.'">'.$link.'</a>'
				."\n\n".$article->get_teaser('basic');
			Logger::notify('query.php: '.$label, $description);
		}

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// splash message
	$context['text'] .= '<p>'.i18n::s('Please fill out the form and it will be sent automatically to the site managers. Be as precise as possible, and mention your e-mail address to let us a chance to contact you back.')."</p>\n";

	// the form to send a query
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// surfer name
	if(!isset($item['edit_name']))
		$item['edit_name'] = Surfer::get_name();
	$label = i18n::s('Your name').' *';
	$input = '<input type="text" name="edit_name" id="edit_name" size="45" value="'.encode_field($item['edit_name']).'" maxlength="255" />';
	$hint = i18n::s('Let us a chance to know who you are');
	$fields[] = array($label, $input, $hint);

	// surfer address
	if(!isset($item['edit_address']))
		$item['edit_address'] = Surfer::get_email_address();
	$label = i18n::s('Your e-mail address').' *';
	$input = '<input type="email" name="edit_address" size="45" value="'.encode_field($item['edit_address']).'" maxlength="255" />';
	$hint = i18n::s('To be alerted during the processing of your request');
	$fields[] = array($label, $input, $hint);

	// stop robots
	if($field = Surfer::get_robot_stopper())
		$fields[] = $field;

	// the title
	if(!isset($item['title']))
		$item['title'] = '';
	$label = i18n::s('Query object').' *';
	$input = '<textarea name="title" rows="2" cols="50">'.encode_field($item['title']).'</textarea>';
	$hint = i18n::s('The main object of your query');
	$fields[] = array($label, $input, $hint);

	// the description
	if(!isset($item['description']))
		$item['description'] = '';
	$label = i18n::s('Details of your request');
	$input = '<textarea name="description" rows="20" cols="50">'.encode_field($item['description']).'</textarea>';
	$hint = i18n::s('Please mention any reference information required to process the request');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// bottom commands
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// step back
	if(isset($_SERVER['HTTP_REFERER']))
		$menu[] = Skin::build_link($_SERVER['HTTP_REFERER'], i18n::s('Cancel'), 'span');

	// display the menu
	$context['text'] .= Skin::finalize_list($menu, 'menu_bar');

	// end of the form
	$context['text'] .= '</div></form>';

	// append the script used for data checking on the browser
	Page::insert_script(
		// check that main fields are not empty
		'func'.'tion validateDocumentPost(container) {'."\n"
			// edit_name is mandatory'
		.'	if(!container.edit_name.value) {'."\n"
		.'		alert("'.i18n::s('Please give your first and last names').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
			// edit_address is mandatory
		.'	if(!container.edit_address.value) {'."\n"
		.'		alert("'.i18n::s('Please give your e-mail address').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
			// title is mandatory
		.'	if(!container.title.value) {'."\n"
		.'		alert("'.i18n::s('Please provide a meaningful title.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n"
		.'	if(container.description.value.length > 64000){'."\n"
		.'		alert("'.i18n::s('The description should not exceed 64000 characters.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n"
			// successful check
		.'	return true;'."\n"
		.'}'."\n"
		// set the focus on first form field
		.'$("#edit_name").focus();'."\n"
		);

	// general help on this form
	$text = i18n::s('<p>Use this form to submit any kind of request you can have, from simple suggestions to complex questions.</p><p>Hearty discussion and unpopular viewpoints are welcome, but please keep queries civil. Flaming, trolling, and smarmy queries are discouraged and may be deleted. In fact, we reserve the right to delete any post for any reason. Don\'t make us do it.</p>');
	if(Surfer::is_associate())
		$text .= '<p>'.i18n::s('If you paste some existing HTML content and want to avoid the implicit formatting insert the code <code>[formatted]</code> at the very beginning of the description field.');
	else
		$text .= '<p>'.i18n::s('Most HTML tags are removed.');
	$text .= ' '.sprintf(i18n::s('You can use %s to beautify your post'), Skin::build_link('codes/', i18n::s('YACS codes'), 'open')).'.</p>';

	// locate mandatory fields
	$text .= '<p>'.i18n::s('Mandatory fields are marked with a *').'</p>';

	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $text, 'boxes', 'help');

}

// render the skin
render_skin();

?>
