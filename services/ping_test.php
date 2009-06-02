<?php
/**
 * a simple ping client
 *
 * This script is used to check the behaviour of the [script]services/ping.php[/script].
 * Actually, this will only be of some interest for software developers.
 *
 * @see services/ping.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
include_once '../shared/global.php';
include_once 'call.php';

// load the adequate codec
include_once 'codec.php';
include_once 'xml_rpc_codec.php';
$codec =& new xml_rpc_Codec();

// load localized strings
i18n::bind('services');

// load the skin
load_skin('services');

// the title of the page
$context['page_title'] = i18n::s('Sample ping client');

// we do have a target to process
if(isset($_REQUEST['target'])) {

	// call blog web service
	$url = 'http://'.$_REQUEST['target'].'/services/ping.php';

	// pingback.ping
	if($_REQUEST['action'] == 'pingback.ping') {
		$context['text'] .= Skin::build_block('pingback.ping', 'title');
		$result = Call::invoke($url, 'pingback.ping', array($_REQUEST['source_link'], $_REQUEST['target_link']), 'XML-RPC');
	}

	// weblogUpdates.ping
	if($_REQUEST['action'] == 'weblogUpdates.ping') {
		$context['text'] .= Skin::build_block('weblogUpdates.ping', 'title');
		$result = Call::invoke($url, 'weblogUpdates.ping', array(strip_tags($_REQUEST['name']), $_REQUEST['url']), 'XML-RPC');
	}

	// display call result
	$status = @$result[0];
	$data = @$result[1];
	if(!$status)
		$context['text'] .= $data;
	elseif(is_array($data) && $data['faultString'])
		$context['text'] .= $data['faultString'];
	elseif(is_array($data)) {
		foreach($data as $name => $value)
			$context['text'] .= $name.': '.$value.BR."\n";
	} else
		$context['text'] .= $data;

}

// make a default target
if(!isset($_REQUEST['target']) || !$_REQUEST['target'])
	$_REQUEST['target'] = $context['host_name'];

// test weblogUpdates.ping
$context['text'] .= Skin::build_block(i18n::s('Test weblogUpdates.ping'), 'title');

// the form
$fields = array();
$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';
$label = i18n::s('Server address');
$input = '<input type="text" name="target" id="target" size="30" maxlength="128" value="'.encode_field(isset($_REQUEST['target']) ? $_REQUEST['target'] : '').'" />'."\n"
	.' '.Skin::build_submit_button(i18n::s('Go'));
$hint = i18n::s('The name or the IP address of the yacs server');
$fields[] = array($label, $input, $hint);
$label = i18n::s('Name of the updated server');
$input = '<input type="text" name="name" size="45" maxlength="255" value="'.encode_field(isset($_REQUEST['name']) ? $_REQUEST['name'] : '').'" />';
$fields[] = array($label, $input);
$label = i18n::s('URI of the updated server');
$input = '<input type="text" name="url" size="45" maxlength="255" value="'.encode_field(isset($_REQUEST['url']) ? $_REQUEST['url'] : '').'" />';
$fields[] = array($label, $input);
$context['text'] .= Skin::build_form($fields);
$context['text'] .= '<input type="hidden" name="action" value="weblogUpdates.ping" />';
$context['text'] .= '</div></form>';

// the script used for form handling at the browser
$context['text'] .= JS_PREFIX
	.'// set the focus on first form field'."\n"
	.'$("target").focus();'."\n"
	.JS_SUFFIX."\n";

// test pingback.ping
$context['text'] .= Skin::build_block(i18n::s('Test pingback.ping'), 'title');

// the form
$fields = array();
$context['text'] .= '<form method="post" action="'.$context['script_url'].'"><div>';
$label = i18n::s('Server address');
$input = '<input type="text" name="target" size="30" maxlength="128" value="'.encode_field(isset($_REQUEST['target']) ? $_REQUEST['target'] : '').'" />'."\n"
	.' '.Skin::build_submit_button(i18n::s('Go'));
$hint = i18n::s('The name or the IP address of the yacs server');
$fields[] = array($label, $input, $hint);
$label = i18n::s('URI of the source page');
$input = '<input type="text" name="source_link" size="45" maxlength="255" value="'.encode_field(isset($_REQUEST['source_link']) ? $_REQUEST['source_link'] : '').'" />';
$fields[] = array($label, $input);
$label = i18n::s('URI of the target page');
$input = '<input type="text" name="target_link" size="45" maxlength="255" value="'.encode_field(isset($_REQUEST['target_link']) ? $_REQUEST['target_link'] : '').'" />';
$fields[] = array($label, $input);
$context['text'] .= Skin::build_form($fields);
$context['text'] .= '<input type="hidden" name="action" value="pingback.ping" />';
$context['text'] .= '</div></form>';

render_skin();

?>