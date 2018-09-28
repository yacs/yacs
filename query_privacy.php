<?php
/**
 * send a query specifically about privacy data (RGPD european law context)
 *
 * This script is to be used by a logged member
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
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once 'shared/global.php';

// anonymous users are invited to log in or to register
if(!Surfer::is_logged()) {

        $link = 'query_privacy.php';

        Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode($link));
}

// do not always show the edition form
$with_form = FALSE;

// load localized strings
i18n::bind('root');

// load the skin
load_skin('query');

// the title of the page
$context['page_title'] = i18n::s('Your private data');

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

	// this is the exact copy of what end users has typed
	$item = $_REQUEST;
        
        // surfer name & id
        $surfer_name = Surfer::get_name().' (user:'.Surfer::get_id().') ';
        
        // Action depending on choice
        $privacy_query = $item['privacy_query'];
        Switch($privacy_query) {
            case 'extract':
                $item['description'] = tag::_('h3','',$surfer_name.i18n::s('ask for a excerpt of personnal data stored in database'));
                break;
            case 'forget':
                $item['description'] = tag::_('h3','',$surfer_name.i18n::s('invoke the right to be forgotten'));
                break;
            case 'special':
                
                break;
        }

	// from form fields to record columns
	if(!isset($item['edit_id']))
		$item['edit_id']	= Surfer::get_id();
	$item['create_address'] = $item['edit_address'];
	if(!isset($item['create_name']))
                $item['create_name'] = $item['edit_name'];
	if(!$item['create_name'])
		$item['create_name'] = $item['create_address'];
	if(!$item['create_name'])
		$item['create_name'] = i18n::c('(anonymous)');

	// always auto-publish queries
	$item['publish_date']	= gmstrftime('%Y-%m-%d %H:%M:%S');
	if(isset($item['edit_id']))
		$item['publish_id'] 	= $item['edit_id'];
	$item['publish_address'] = $item['edit_address'];
	$item['publish_name']	= $item['edit_name'];
        

        // title
        $item['title'] = i18n::s('Data privacy request');
        
	// description
        $item['description'] = '<p>'.sprintf(i18n::c('Sent by %s'), ($item['edit_name']?$item['edit_name'].' - ':i18n::c('e-mail').' : ').' [email]'.$item['edit_address'].'[/email]')."</p>\n"
                .$item['description'];


        // provide the page a overlay so anonymous surfer could receive notification
        $overlay                        = Overlay::bind('query');
        $item['overlay']            = $overlay->save();
        

        if(!$item['id'] = Articles::post($item)) {
		$with_form = TRUE;

	// post-processing
	} else {
            
		// do whatever is necessary on page publication
		Articles::finalize_publication($anchor, $item);

		// message to the query poster
		$context['page_title'] = i18n::s('Your query has been registered');

		// use the secret handle to access the query
		$link = '';
		$status = '';
		if($item = Articles::get($item['id'])) {

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

		$context['text'] .= i18n::s('<p>Your query will now be reviewed by one of the administrator of this website. It is likely that this will be done within the next 24 hours at the latest.</p>');
		$context['text'] .= $status;
                
                switch ($privacy_query) {
                    case 'extract':
                        
                        $context['text'] .= tag::_p(i18n::s('This is a extraction of your profile data from database. Website administrator will provide you other data if any.'));
                        
                        // get profile info
                        $info = Users::list_for_ids(Surfer::get_id(),'excerpt');
                       
                        
                        $context['text'] .= $info;
                    break;
                    case 'forget':
                        if ($context['users_without_self_deletion'] === 'N') {
                            $context['text'] .= tag::_p(i18n::s('Website configuration enable you to delete your profile. You may proceed following the link below.'));
                            $context['text'] .= tag::_p(Skin::build_link(Users::get_url(Surfer::get_id(), 'delete'), i18n::s('Profile deletion page'), 'button'), 'k/mal k/txtcenter');
                        }
                    break;
                    
                    
                }

		// follow-up commands
		$follow_up = i18n::s('Where do you want to go now?');
		$menu = array();
		$menu = array_merge($menu, array($context['url_to_root'] => i18n::s('Front page')));
		$menu = array_merge($menu, array('sections/' => i18n::s('Site map')));
		$menu = array_merge($menu, array('query.php' => i18n::s('Contact website administrator')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		// send a confirmation message to the surfer
		if(isset($item['edit_address']) && preg_match('/.+@.+/', $item['edit_address']) && $link) {

			// message recipient
			$to = $item['edit_address'];

			// message subject
			$subject = sprintf(i18n::s('Your query: %s'), strip_tags($item['title']));

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
		$article = Anchors::get('article:'.$item['id']);

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
	$context['text'] .= '<p>'.i18n::s('According to GDPR European law article 15 you are in right to ask the review of stored data stored concerning you and the usage of it. And according to article 17 you can query to erase those data.')."</p>\n";

	// the form to send a query
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// surfer name
	$label = i18n::s('Your name');
	$input = '<input type="hidden" name="edit_name" value="'.encode_field(Surfer::get_name()).'" />';
        $input .= Surfer::get_name();
	$fields[] = array($label, $input);

	// surfer address
	$label = i18n::s('Your e-mail address');
	$input = '<input type="hidden" name="edit_address" value="'.encode_field(Surfer::get_email_address()).'" />';
        $input .= Surfer::get_email_address();
	$fields[] = array($label, $input);

	$label = i18n::s('What is your need ?');
        $input  = '<input type=radio name=privacy_query value=extract id=privacy_extract checked /><label for=privacy_extract>'.i18n::s('Get a list of your private data').'</label></br>';
        $input .= '<input type=radio name=privacy_query value=forget id=privacy_forget /><label for=privacy_forget>'.i18n::s('Invoke your right to be forgotten').'</label></br>';
        //$input .= '<input type=radio name=privacy_query value=special id=privacy_special /><label for=privacy_special>'.i18n::s('Other specific question about personnal data and its usage').'</label>';
        $fields[] = array($label, $input);

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

}

// render the skin
render_skin();
