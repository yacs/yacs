<?php
/**
 * a simple authentication client
 *
 * This script checks drupal.login through XML-RPC
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see services/blog.php
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
$user_name = isset($_REQUEST['user_name']) ? $_REQUEST['user_name'] : '';
$user_password = isset($_REQUEST['user_password']) ? $_REQUEST['user_password'] : '';

// display a specific form
$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';
$label = i18n::s('Server address');
$input = '<input type="text" name="target" id="target" size="30" maxlength="128" value="'.encode_field($target).'" />'."\n"
	.' '.Skin::build_submit_button(i18n::s('Go'));
$hint = i18n::s('The name or the IP address of the yacs server');
$fields[] = array($label, $input, $hint);
$label = i18n::s('User name');
$input = '<input type="text" name="user_name" size="30" maxlength="128" value="'.encode_field($user_name).'" />';
$fields[] = array($label, $input);
$label = i18n::s('User password');
$input = '<input type="password" name="user_password" size="30" maxlength="128" value="'.$user_password.'" />';
$fields[] = array($label, $input);
$context['text'] .= Skin::build_form($fields);
$context['text'] .= '</div></form>';

// set the focus at the first field
$context['text'] .= JS_PREFIX
	.'$("target").focus();'."\n"
	.JS_SUFFIX."\n";

// process provided parameters
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// call blog web service
	$url = 'http://'.$_REQUEST['target'].'/services/xml_rpc.php';

	// drupal.login
	$context['text'] .= Skin::build_block('drupal.login', 'title');
	$parameters = array($user_name, $user_password);
	include_once 'call.php';
	$status = $data = NULL;
	if($result = Call::invoke($url, 'drupal.login', $parameters, 'XML-RPC')) {
		$status = $result[0];
		$data = $result[1];
	}

	// display call result
	if(!$status)
		$context['text'] .= 'status: '.$data;
	elseif(is_array($data) && $data['faultString'])
		$context['text'] .= 'fault: '.$data['faultString'];
	elseif(is_array($data)) {
		$context['text'] .= '?? ';
		foreach($data as $item) {
			$context['text'] .= "<p>".$item."</p>\n";
		}
	} else
		$context['text'] .= '"'.$data.'"';

}

// general help on this page
$help = sprintf(i18n::s('Go to the %s and check debug mode for <code>rpc</code> has been set, if you want to record HTTP requests and responses.'), Skin::build_link('services/configure.php', i18n::s('configuration panel for services'), 'shortcut'))."\n";
$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'extra', 'help');

render_skin();

?>