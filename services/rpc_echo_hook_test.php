<?php
/**
 * test JSON echo web service
 *
 * This script checks echo through JSON-RPC
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
include_once '../shared/global.php';

// load localized strings
i18n::bind('services');

// load the skin
load_skin('services');

// the title of the page
$context['page_title'] = i18n::s('Web service test');

// make a target
$target = '';
if(isset($_REQUEST['target']))
	$target = $_REQUEST['target'];
elseif(isset($context['host_name']))
	$target = $context['host_name'];
$message = isset($_REQUEST['message']) ? $_REQUEST['message'] : 'hello world';

// display a specific form
$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';
$label = i18n::s('Server address');
$input = '<input type="text" name="target" id="target" size="30" maxlength="128" value="'.encode_field($target).'" />'."\n"
	.' '.Skin::build_submit_button(i18n::s('Go'));
$hint = i18n::s('The name or the IP address of the yacs server');
$fields[] = array($label, $input, $hint);
$label = i18n::s('Message');
$input = '<input type="text" name="message" size="30" maxlength="128" value="'.encode_field($message).'" />';
$fields[] = array($label, $input);
$context['text'] .= Skin::build_form($fields);
$context['text'] .= '</div></form>';

// set the focus at the first field
$context['text'] .= JS_PREFIX
	.'$("target").focus();'."\n"
	.JS_SUFFIX."\n";

// process provided parameters
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// call json back-end
	$url = 'http://'.$_REQUEST['target'].'/services/json_rpc.php';

	// echo
	$context['text'] .= Skin::build_block('echo', 'title');
	$parameters = array('message' => $message);
	include_once 'call.php';
	$status = $data = NULL;
	if($result = Call::invoke($url, 'echo', $parameters, 'JSON-RPC')) {
		$status = $result[0];
		$data = $result[1];
	}

	// display call result
	if(!$status)
		$context['text'] .= 'status: '.$data;
	elseif(!empty($data['error']))
		$context['text'] .= 'error: '.$data['error'];
	elseif(is_array($data)) {
		foreach($data as $name => $value) {
			if(is_array($value)) {
				$context['text'] .= "<p>".$name.':</p><ul>';
				foreach($value as $name => $value) {
					$context['text'] .= "<li>".$name.': '.$value."</li>\n";
				}
				$context['text'] .= "</ul>\n";
			} else
				$context['text'] .= "<p>".$name.': '.$value."</p>\n";
		}
	} else
		$context['text'] .= '"'.$data.'"';

}

// general help on this page
$help = sprintf(i18n::s('Go to the %s and check debug mode for <code>rpc</code> has been set, if you want to record HTTP requests and responses.'), Skin::build_link('services/configure.php', i18n::s('configuration panel for services'), 'shortcut'))."\n";
$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');

render_skin();

?>