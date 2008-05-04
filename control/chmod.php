<?php
/**
 * change file permissions of all scripts
 *
 * This script applies the ##chmod## command to all files and directories. It
 * is useful to fix some internal errors at some ISPs.
 *
 * To run this script, the surfer has to be an associate, or no switch file
 * exists.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../scripts/scripts.php';

// load localized strings
i18n::bind('control');

// load the skin
load_skin('control');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = i18n::s('Update file permissions');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('control/chmod.php'));

// only associates can proceed when a switch file exists
elseif(!Surfer::is_associate() && !(file_exists($context['path_to_root'].'parameters/switch.on') || file_exists($context['path_to_root'].'parameters/switch.off'))) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	$context['text'] .= '<p>'.i18n::s('You are not allowed to perform this operation.')."</p>\n";

	// forward to the index page
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// do the action
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'confirm')) {

	// list running scripts
	$context['text'] .= '<p>'.i18n::s('Listing all running PHP scripts...').BR."\n";

	// locate script files starting at root
	$scripts = Scripts::list_scripts_at(NULL);
	if(is_array($scripts))
		$context['text'] .= BR.sprintf(i18n::s('%d scripts have been found.'), count($scripts))."\n";
	$context['text'] .= "</p>\n";

	// chmod each file
	$context['text'] .= '<p>'.i18n::s('Updating file permissions...').BR."\n";

	// analyse each script
	$count = 0;
	foreach($scripts as $script) {

		// check file content
		list($module, $name) = $script;
		if($module)
			$file = $module.'/'.$name;
		else
			$file = $name;

		// this will be filtered by umask anyway
		Safe::chmod($context['path_to_root'].$file, $context['file_mask']);
		$count++;

		// avoid timeouts
		if(!($count%50)) {
			Safe::set_time_limit(30);
			SQL::ping();
		}

	}

	if($count)
		$context['text'] .= sprintf(i18n::s('%d files have been updated.'), $count)."\n";
	$context['text'] .= "</p>\n";

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the index page
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

	// remember the operation
	$label = sprintf(i18n::c('chmod %s has been applied to scripts'), $context['file_mask']);
	Logger::remember('control/chmod.php', $label);

// confirmation is required
} else {

	// the confirmation question
	$context['text'] .= '<b>'.sprintf(i18n::s('You are about to chmod(%d) all running scripts of this server. Are you sure?'), $context['file_mask'])."</b>\n";

	// the menu for this page
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'"><p>'
		.Skin::build_submit_button(i18n::s('Yes, I do want to change permissions of running scripts'))
		.'<input type="hidden" name="action" value="confirm" />'
		.'</p></form>'."\n";

	// this may take several minutes
	$context['text'] .= '<p>'.i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.')."</p>\n";

}

// render the skin
render_skin();

?>