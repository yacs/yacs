<?php
/**
 * delete a server
 *
 * This script calls for confirmation, then actually deletes the server.
 * The script updates the database, then redirects to the referer URL, or to the index page.
 *
 * This page is to be used only by associates.
 *
 * Accepted calls:
 * - delete.php/12
 * - delete.php?id=12
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'servers.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Servers::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// load the skin
load_skin('servers');

// the path to this page
$context['path_bar'] = array( 'servers/' => i18n::s('Servers') );

// the title of the page
$context['page_title'] = i18n::s('Delete a server');

// not found
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.'));

// deletion is restricted to associates
} elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// deletion is confirmed
} elseif(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'yes')) {

	// delete and go back to the index page
	if(Servers::delete($item['id'])) {
		Servers::clear($item);
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'servers/');
	}

// deletion has to be confirmed
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
	Logger::error(i18n::s('The deletion has not been confirmed.'));

}

// deletion is restricted to associates
if($item['id'] && Surfer::is_associate()) {

	// the submit button
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
		.Skin::build_submit_button(i18n::s('Yes, I want to suppress this server'), NULL, NULL, 'confirmed')."\n"
		.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
		.'<input type="hidden" name="confirm" value="yes" />'."\n"
		.'</p></form>'."\n";

	// set the focus
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// set the focus on first form field'."\n"
		.'$("confirmed").focus();'."\n"
		.'// ]]></script>'."\n";

	// the title of the server
	$context['text'] .= Skin::build_block($item['title'], 'title');

	// the nick name
	if($item['host_name'] && Surfer::is_associate())
		$details[] = '"'.$item['host_name'].'"';

		// information on last update
	if($item['edit_name'])
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

	// restricted to logged members
	if($item['active'] == 'R')
		$details[] = RESTRICTED_FLAG.' '.i18n::s('Access is restricted to authenticated members').BR."\n";

	// restricted to associates
	elseif($item['active'] == 'N')
		$details[] = PRIVATE_FLAG.' '.i18n::s('Access is restricted to associates').BR."\n";

	// all details
	$context['text'] .= '<p class="details">'.ucfirst(implode(', ', $details))."</p>\n";

	// main url
	if($item['main_url'])
		$context['text'] .= '<p>'.sprintf(i18n::s('Main URL: %s'), Skin::build_link($item['main_url'], NULL, 'external'))."</p>\n";

	// feed url
	if($item['feed_url'])
		$context['text'] .= '<p>'.sprintf(i18n::s('Feed URL: %s'), Skin::build_link($item['feed_url'], NULL, 'external'))."</p>\n";

	// the related anchor
	if(is_object($anchor))
		$context['text'] .= '<p>'.sprintf(i18n::s('Related to %s'), Skin::build_link($anchor->get_url(), $anchor->get_title(), 'category'))."</p>\n";

	// a section for remote services
	$context['text'] .= Skin::build_block(i18n::s('Services accessed remotely'), 'subtitle');

	// feed submission
	if($item['submit_feed'] == 'Y')
		$context['text'] .= '<p>'.sprintf(i18n::s('News published at this server will be fetched periodically from %s'), $item['feed_url'])."</p>\n";
	else
		$context['text'] .= '<p>'.i18n::s('Do not check news from this server.')."</p>\n";

	// ping submission
	if($item['submit_ping'] == 'Y')
		$context['text'] .= '<p>'.sprintf(i18n::s('This server has to be pinged on updates, by using XML-RPC calls <code>weblogUpdates.ping</code> at %s'), $item['ping_url'])."</p>\n";
	else
		$context['text'] .= '<p>'.i18n::s('Updates are not transmitted to this server.')."</p>\n";

	// monitoring
	if($item['submit_monitor'] == 'Y')
		$context['text'] .= '<p>'.sprintf(i18n::s('This server has to be polled, by using XML-RPC calls <code>monitor.ping</code> at %s'), $item['monitor_url'])."</p>\n";
	else
		$context['text'] .= '<p>'.i18n::s('Do not poll this server to check its state.')."</p>\n";

	// search submission
	if($item['submit_search'] == 'Y')
		$context['text'] .= '<p>'.sprintf(i18n::s('This server has to be included into searches, by using REST calls at %s'), $item['search_url'])."</p>\n";
	else
		$context['text'] .= '<p>'.i18n::s('Do not submit search requests to this server.')."</p>\n";

	// a section for remote requests
	$context['text'] .= Skin::build_block(i18n::s('Allowed queries from this server'), 'subtitle');

	// ping processing
	if($item['process_ping'] == 'Y')
		$context['text'] .= '<p>'.sprintf(i18n::s('This server is allowed to advertise changes (<code>weblogUpdates.ping</code>) at %s'), $context['url_to_root'].'services/ping.php')."</p>\n";
	else
		$context['text'] .= '<p>'.i18n::s('This server is not allowed to advertise changes.')."</p>\n";

	// monitoring
	if($item['process_monitor'] == 'Y')
		$context['text'] .= '<p>'.sprintf(i18n::s('This server is allowed to submit monitoring requests (<code>monitor.ping</code>) at %s'), $context['url_to_root'].'services/ping.php')."</p>\n";
	else
		$context['text'] .= '<p>'.i18n::s('This server is not allowed to submit monitoring requests.')."</p>\n";

	// search processing
	if($item['process_search'] == 'Y')
		$context['text'] .= '<p>'.sprintf(i18n::s('This server is allowed to submit search requests at %s'), $context['url_to_root'].'services/search.php')."</p>\n";
	else
		$context['text'] .= '<p>'.i18n::s('This server is not allowed to submit search requests.')."</p>\n";

	// a section for the description
	if($item['description']) {

		// display the full text
		$context['text'] .= Skin::build_block(i18n::s('Server description'), 'subtitle');

		// beautify the text
		$context['text'] .= Skin::build_block($item['description'], 'description');

	}

}

// render the skin
render_skin();

?>