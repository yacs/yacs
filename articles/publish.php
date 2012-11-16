<?php
/**
 * publish an article
 *
 * Publishing an article means that the surfer takes the ownership of the publication. Therefore,
 * his/her name is registered in the database with the publishing date.
 *
 * Various publishing options can be set as well.
 * Then the actual publication takes place on confirmation.
 *
 * One option is to trackback links embedded into the article. Links are also extracted and inserted in the database separately.
 *
 * This script explicitly pings server profiles that have been configured for this purpose,
 * but only on following conditions:
 * - the section is public (see [script]sections/sections.php[/script])
 * - the page has not been flagged to not appear at the front page
 * - the page is active (not restricted nor hidden)
 *
 * This page is to be used by associates and editors, while they are reviewing queued articles.
 * In sections where option 'auto_publish' has been set, authors are also allowed
 * to publish their pages.
 *
 * Accept following invocations:
 * - publish.php/12
 * - publish.php?id=12
 *
 * If this article, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Olivier
 * @tester Pat
 * @tester Alain Lesage (Lasares)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../links/links.php'; // used for link processing
include_once '../servers/servers.php'; // servers to be advertised
include_once '../articles/article.php'; // servers to be advertised

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item = Articles::get($id);

// get the related overlay, if any
$overlay = NULL;
if(isset($item['overlay']))
	$overlay = Overlay::load($item, 'article:'.$item['id']);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']))
	$anchor = Anchors::get($item['anchor']);

// surfer can proceed
if(Articles::allow_publication($anchor, $item)) {
	Surfer::empower();
	$permitted = TRUE;

// disallow access
} else
	$permitted = FALSE;

// do not always show the edition form
$with_form = FALSE;

// load the skin, maybe with a variant
load_skin('articles', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// path to this page
$context['path_bar'] = Surfer::get_path_bar($anchor);
if(isset($item['id']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Articles::get_permalink($item) => $item['title']));

// page title
if(isset($item['id']))
	$context['page_title'] = sprintf(i18n::s('Publish: %s'), $item['title']);

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	include '../error.php';

// an anchor is mandatory
} elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No anchor has been found.'));

// publication is not available to everybody
} elseif(!$permitted) {

	// anonymous users are invited to log in
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_url($item['id'], 'publish')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// page has already been published
} elseif(isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE)) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// publication is confirmed
} elseif(isset($_REQUEST['publish_date']) && ($_REQUEST['publish_date'] > NULL_DATE)) {

	// convert dates from surfer time zone to UTC time zone
 	$_REQUEST['publish_date'] = Surfer::to_GMT($_REQUEST['publish_date']);
	if(isset($_REQUEST['expiry_date']) && ($_REQUEST['expiry_date'] > NULL_DATE))
	 	$_REQUEST['expiry_date'] = Surfer::to_GMT($_REQUEST['expiry_date']);

	// update the database
	if($error = Articles::stamp($item['id'], $_REQUEST['publish_date'], isset($_REQUEST['expiry_date']) ? $_REQUEST['expiry_date'] : NULL_DATE))
		Logger::error($error);

	// post-processing tasks
	else {

		// send to watchers of this page, and to watchers upwards
		$watching_context = new Article();
		$watching_context->load_by_content($item, $anchor);

		// do whatever is necessary on page publication
		Articles::finalize_publication($watching_context, $item, $overlay,
			isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'),
			isset($_REQUEST['notify_followers']) && ($_REQUEST['notify_followers'] == 'Y'));

		// splash messages
		$context['text'] .= '<p>'.i18n::s('The page has been successfully published.')."</p>\n";

		// list persons that have been notified
		$context['text'] .= Mailer::build_recipients('article:'.$item['id']);

		// clear the cache
		Articles::clear($item);

		// follow-up commands
		$follow_up = i18n::s('Where do you want to go now?');
		$menu = array();
		$menu = array_merge($menu, array(Articles::get_permalink($item) => i18n::s('Back to main page')));
		if(Surfer::is_associate())
			$menu = array_merge($menu, array('articles/review.php' => i18n::s('Review queue')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';

	// encode fields
	$fields = array();

	// the publication date, if any
	$label = i18n::s('Publication date');

	// adjust date from server time zone to surfer time zone
	$value = strftime('%Y-%m-%d %H:%M:%S', time() + ((Surfer::get_gmt_offset() - intval($context['gmt_offset'])) * 3600));
	$input = Skin::build_input('publish_date', $value, 'date_time');
	$hint = i18n::s('Indicate a date (YYYY-MM-DD) in the future and let YACS make the page appear automatically.');
	$fields[] = array($label, $input, $hint);

	// advertise public pages
	$ping_option = FALSE;
	$trackback_option = FALSE;
	if($anchor->is_public()
			&& (isset($item['active']) && ($item['active'] == 'Y')) ) {
		$ping_option = TRUE;
		$trackback_option = TRUE;
	}

	// trackback option
	$label = i18n::s('Trackback');
	$input = '<input type="radio" name="trackback_option" value="N"';
	if(!$trackback_option)
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not contact referenced servers')
		.BR.'<input type="radio" name="trackback_option" value="Y"';
	if($trackback_option)
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Parse links and trackback referenced pages');
	$fields[] = array($label, $input);

	// ping option
	$label = i18n::s('Ping');

	// list servers to be advertised
	if($servers = Servers::list_for_ping(0, COMPACT_LIST_SIZE, 'ping')) {

		// list targeted servers
		$list = '<ul>';
		foreach($servers as $server_url => $attributes) {
			list($server_ping, $server_label) = $attributes;
			$list .= '<li>'.Skin::build_link($server_url, $server_label, 'external').'</li>';
		}
		$list .= '</ul>';

		$input = '<input type="radio" name="ping_option" value="N"';
		if(!$ping_option)
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('Do not contact aggregation servers')
			.BR.'<input type="radio" name="ping_option" value="Y"';
		if($ping_option)
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('This publication should be advertised at:').$list;

	} elseif(Surfer::is_associate())
		$input = sprintf(i18n::s('Use the %s to populate this server.'), Skin::build_link('help/populate.php?action=servers', i18n::s('Content Assistant'), 'shortcut'));

	$fields[] = array($label, $input);

	// the expiry date, if any
	$label = i18n::s('Expiry date');

	// adjust date from UTC time zone to surfer time zone
	$value = '';
	if(isset($item['expiry_date']))
		$value = Surfer::from_GMT($item['expiry_date']);

	$input = Skin::build_input('expiry_date', $value, 'date_time');
	$hint = i18n::s('Use this field to limit the life time of published pages.');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);
	$fields = array();

	// submit or cancel
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');
	$menu[] = Skin::build_link(Articles::get_permalink($item), i18n::s('Cancel'), 'span');
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// notify watchers --updating a file, or uploading a new file, should generate a notification
	$context['text'] .= '<input type="checkbox" name="notify_watchers" value="Y" checked="checked" /> '.i18n::s('Notify watchers').BR;

	// notify people following me
	if(Surfer::get_id() && !$anchor->is_hidden())
		$context['text'] .= '<input type="checkbox" name="notify_followers" value="Y" /> '.i18n::s('Notify my followers').BR;

	// article id and confirmation
	$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// end of the form
	$context['text'] .= '</div></form>';


}

// render the skin
render_skin();

?>
