<?php
/**
 * set a new server or update an existing one
 *
 * A button-based editor is used for the description field.
 * It's aiming to introduce most common [link=codes]codes/index.php[/link] supported by YACS.
 *
 * This script attempts to validate the new or updated article description against a standard PHP XML parser.
 * The objective is to spot malformed or unordered HTML and XHTML tags. No more, no less.
 *
 * Only associates can create or modify some server profile.
 *
 * Accepted calls:
 * - edit.php	add a new server
 * - edit.php/&lt;id&gt;	modify an existing server
 * - edit.php?id=&lt;id&gt; modify an existing server
 *
 * @author Bernard Paques
 * @author Vincent No&euml;l
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Agnes
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../shared/xml.php';	// input validation
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

// do not always show the edition form
$with_form = FALSE;

// load the skin
load_skin('servers');

// the path to this page
$context['path_bar'] = array( 'servers/' => i18n::s('Servers') );

// the title of the page
if($item['id'])
	$context['page_title'] = i18n::s('Edit a server profile');
else
	$context['page_title'] = i18n::s('Add a server profile');

// validate input syntax only if required
if(isset($_REQUEST['option_validate']) && ($_REQUEST['option_validate'] == 'Y')) {
	if(isset($_REQUEST['introduction']))
		xml::validate($_REQUEST['introduction']);
	if(isset($_REQUEST['description']))
		xml::validate($_REQUEST['description']);
}

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// anonymous users are invited to log in or to register
} elseif(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Servers::get_url($id, 'edit')));

// associates only
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// display the form on error
	if($error = Servers::post($_REQUEST)) {
		Logger::error($error);
		$item = $_REQUEST;
		$with_form = TRUE;

	// reward the poster for new posts
	} elseif(!$item['id']) {

		// the follow-up page
		$next = $context['url_to_home'].$context['url_to_root'].Servers::get_url(SQL::get_last_id($context['connection']));

		// the action
		$action = 'server:create';

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// forward to the updated page
		Safe::redirect($next);

	// update of an existing server
	} else {

		// the follow-up page
		$next = $context['url_to_home'].$context['url_to_root'].Servers::get_url($_REQUEST['id']);

		// forward to the updated page
		Safe::redirect($next);
	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// the form to edit an server
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// the last editor
	if(isset($item['edit_name']))
		$context['text'] .= '<p class="details">'.sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date'])).'</p>'."\n";

	// the overview
	$context['text'] .= Skin::build_block(i18n::s('Overview'), 'subtitle');

	// title
	$label = i18n::s('Title');
	$input = '<input type="text" name="title" id="title" size="40" value="'.encode_field(isset($item['title'])?$item['title']:'').'" />';
	$hint = i18n::s('Label used to list this server profile');
	$fields[] = array($label, $input, $hint);

	// the main URL
	$label = i18n::s('Network address');
	$input = '<input type="text" name="main_url" size="40" value="'.encode_field(isset($item['main_url'])?$item['main_url']:'').'" />';
	$hint = i18n::s('The web or email address to be used for a human being');
	$fields[] = array($label, $input, $hint);

	// use the editor if possible
	$label = i18n::s('Description');

	// use the editor if possible
	$input = Surfer::get_editor('description', isset($item['description'])?$item['description']:'');
	$fields[] = array($label, $input);

	// the active flag: Yes/public, Restricted/logged, No/associates
	$label = i18n::s('Access');
	$input = '<input type="radio" name="active" value="Y" accesskey="v"';
	if(!isset($item['active']) || ($item['active'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Public - Access is granted to anonymous surfers')
		.BR.'<input type="radio" name="active" value="R"';
	if(isset($item['active']) && ($item['active'] == 'R'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Community - Access is restricted to authenticated persons')
		.BR.'<input type="radio" name="active" value="N"';
	if(isset($item['active']) && ($item['active'] == 'N'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Private - Access is restricted to selected persons')."\n";
	$fields[] = array($label, $input);

	// build the form
	$context['text'] .= Skin::build_form($fields);
	$fields = array();

	// access remote services
	$context['text'] .= Skin::build_block(i18n::s('Web services used from this server'), 'subtitle');

	// do we have to be feeded by this server?
	$label = i18n::s('Feed');
	$input = '<input type="radio" name="submit_feed" value="N"';
	if(!isset($item['submit_feed']) || ($item['submit_feed'] == 'N'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not read news from this server')
		.BR.'<input type="radio" name="submit_feed" value="Y"';
	if(isset($item['submit_feed']) && ($item['submit_feed'] == 'Y'))
		$input .= ' checked="checked"';
	if(!isset($item['feed_url']) || !$item['feed_url'])
		$item['feed_url'] = 'http://'.(isset($item['host_name'])?$item['host_name']:'__server__').'/feeds/rss.php';
	$input .= '/> '.sprintf(i18n::s('Aggregate news from this server by reading the XML feed at %s'), '<input type="text" name="feed_url" size="50" value="'.encode_field($item['feed_url']).'" />');

	// set a default anchor
	if(!isset($item['anchor']) || !$item['anchor']) {

		// create a default section if necessary
		if(!($anchor = Sections::lookup('external_news'))) {
			$fields = array();
			$fields['nick_name'] = 'external_news';
			$fields['create_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', time());
			$fields['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', time());
			$fields['locked'] = 'Y'; // no direct contributions
			$fields['home_panel'] = 'extra'; // in a side box at the front page
			$fields['rank'] = 40000; // at the end of the list
			$fields['title'] = i18n::c('External News');
			$fields['description'] = i18n::s('Received from feeding servers');
			if($fields['id'] = Sections::post($fields, FALSE))
				$anchor = 'section:'.$fields['id'];
			$fields = array();
		}

		$item['anchor'] = $anchor;
	}

	if($item['anchor'])
		$to_select = $item['anchor'];

	$input .= BR.sprintf(i18n::s('and store data in section %s'), '<select name="anchor">'.Sections::get_options($to_select).'</select>');
	$fields[] = array($label, $input);

	// do we have to ping this server?
	$label = i18n::s('Ping');
	$input = '<input type="radio" name="submit_ping" value="N"';
	if(!isset($item['submit_ping']) || ($item['submit_ping'] == 'N'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not advertise our changes to this remote server')
		.BR.'<input type="radio" name="submit_ping" value="Y"';
	if(isset($item['submit_ping']) && ($item['submit_ping'] == 'Y'))
		$input .= ' checked="checked"';
	if(!isset($item['ping_url']) || !$item['ping_url'])
		$item['ping_url'] = 'http://'.(isset($item['host_name'])?$item['host_name']:'__server__').'/services/ping.php';
	$input .= '/> '.sprintf(i18n::s('On publication, submit XML-RPC call of <code>weblogUpdates.ping</code> at %s'), '<input type="text" name="ping_url" size="50" value="'.encode_field($item['ping_url']).'" />');
	$fields[] = array($label, $input);

	// do we have to search this server?
	$label = i18n::s('Search');
	$input = '<input type="radio" name="submit_search" value="N"';
	if(!isset($item['submit_search']) || ($item['submit_search'] == 'N'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not extend searches to this server')
		.BR.'<input type="radio" name="submit_search" value="Y"';
	if(isset($item['submit_search']) && ($item['submit_search'] == 'Y'))
		$input .= ' checked="checked"';
	if(!isset($item['search_url']) || !$item['search_url'])
		$item['search_url'] = 'http://'.(isset($item['host_name'])?$item['host_name']:'__server__').'/services/search.php';
	$input .= '/> '.sprintf(i18n::s('Submit search queries to this server, by REST calls at %s'), '<input type="text" name="search_url" size="50" value="'.encode_field($item['search_url']).'" />');
	$fields[] = array($label, $input);

	// do we have to monitor this server?
	$label = i18n::s('Monitor');
	$input = '<input type="radio" name="submit_monitor" value="N"';
	if(!isset($item['submit_monitor']) || ($item['submit_monitor'] == 'N'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not monitor this server')
		.BR.'<input type="radio" name="submit_monitor" value="Y"';
	if(isset($item['submit_monitor']) && ($item['submit_monitor'] == 'Y'))
		$input .= ' checked="checked"';
	if(!isset($item['monitor_url']) || !$item['monitor_url'])
		$item['monitor_url'] = 'http://'.(isset($item['host_name'])?$item['host_name']:'__server__').'/services/ping.php';
	$input .= '/> '.sprintf(i18n::s('Submit periodic XML-RPC calls of <code>monitor.ping</code> at %s'), '<input type="text" name="monitor_url" size="50" value="'.encode_field($item['monitor_url']).'" />');
	$fields[] = array($label, $input);

	// build the form
	$context['text'] .= Skin::build_form($fields);
	$fields = array();

	// process remote requests
	$context['text'] .= Skin::build_block(i18n::s('Processing of queries received from this server'), 'subtitle');

	// splash
	$context['text'] .= '<p>'.i18n::s('Remote calls are allowed by default. Uncheck boxes below to ban this server if necessary.').'</p>';

	// the host name -- on creation we will extract it automatically
	if(isset($item['id'])) {
		$label = i18n::s('Host name');
		$input = '<input type="text" name="host_name" size="20" value="'.encode_field($item['host_name']).'" maxlength="64" accesskey="n" />';
		$hint = i18n::s('Checked on each server request to us');
		$fields[] = array($label, $input, $hint);
	}

	// do we allow ping from this server?
	$label = i18n::s('Ping');
	$input = '<input type="checkbox" name="process_ping" value="Y"';
	if(!isset($item['process_ping']) || ($item['process_ping'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.sprintf(i18n::s('Accept and process advertisements (<code>weblogUpdates.ping</code>) transmitted by this server to %s'), Skin::build_link('services/index.php#ping', i18n::s('the ping interface'), 'shortcut'));
	$fields[] = array($label, $input);

	// do we allow search from this server?
	$label = i18n::s('Search');
	$input = '<input type="checkbox" name="process_search" value="Y"';
	if(!isset($item['process_search']) || ($item['process_search'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.sprintf(i18n::s('Accept and process search requests submitted by this server to %s'), Skin::build_link('services/index.php#search', i18n::s('the search interface'), 'shortcut'));
	$fields[] = array($label, $input);

	// do we allow monitoring from this server?
	$label = i18n::s('Monitor');
	$input = '<input type="checkbox" name="process_monitor" value="Y"';
	if(!isset($item['process_monitor']) || ($item['process_monitor'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.sprintf(i18n::s('Allow this server to poll us regularly (<code>monitor.ping</code>) at %s'), Skin::build_link('services/index.php#xml-rpc', i18n::s('the XML-RPC interface'), 'shortcut'));
	$fields[] = array($label, $input);

	// build the form
	$context['text'] .= Skin::build_form($fields);
	$fields = array();

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// associates may decide to not stamp changes -- complex command
	if(isset($item['id']) && Surfer::has_all())
		$context['text'] .= '<p><input type="checkbox" name="silent" value="Y" /> '.i18n::s('Do not change modification date.').'</p>';

	// validate page content
	$context['text'] .= '<p><input type="checkbox" name="option_validate" value="Y" checked="checked" /> '.i18n::s('Ensure this post is valid XHTML.').'</p>';

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
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
		.'$("title").focus();'."\n"
		.JS_SUFFIX."\n";

	// general help on this form
	$help = '<p>'.i18n::s('Use this page to describe network interactions with a peering server, part of the cloud we are in.').'</p>'
		.'<p>'.i18n::s('Edit general-purpose attributes in the overview area.').'</p>'
		.'<p>'.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'help'), Skin::build_link('smileys/', i18n::s('smileys'), 'help')).'</p>'
		.'<p>'.i18n::s('Then configure and trigger web services that we will use remotely.').'</p>'
		.'<p>'.i18n::s('Also, uncheck web services that we should not provide to the target server.').'</p>';
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');

}

// render the skin
render_skin();

?>