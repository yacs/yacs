<?php
/**
 * manage virtual hosts
 *
 * @todo on-going work
 *
 * This script allows the configuration of virtual hosts.
 *
 * Accept following invocations:
 * - virtual.php
 * - virtual.php/virtual_host_name/delete
 * - virtual.php?id=virtual_host_name&action=delete
 * - virtual.php/virtual_host_name/edit
 * - virtual.php?id=virtual_host_name&action=edit
 * - virtual.php/virtual_host_name/new
 * - virtual.php?id=virtual_host_name&action=new
 * - virtual.php/virtual_host_name
 * - virtual.php/virtual_host_name/view
 * - virtual.php?id=virtual_host_name&action=view
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
include_once '../shared/global.php';

// the virtual host name
$id = '';
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
if(isset($context['arguments'][0]))
	$id = $context['arguments'][0];

// avoid potential attacks
$id = preg_replace(FORBIDDEN_IN_PATHS, '', strip_tags($id));

// the action to perform
$action = 'view';
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];
if(isset($context['arguments'][1]))
	$action = $context['arguments'][1];

// avoid potential attacks
if(!preg_match('/^(delete|edit|new|view)$/', $action))
	$action = 'view';

// load localized strings
i18n::bind('control');

// load the skin
load_skin('control');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = i18n::s('Virtual hosts');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('control/virtual.php'));

// only associates can use this tool
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// delete one configuration file
} elseif($id && ($action == 'delete')) {

	// file has to exist
	$file = 'parameters/virtual_'.$id.'.include.php';
	if(!file_exists($context['path_to_root'].$file))
		Logger::error(i18n::s('No configuration file has been found for this virtual host.'));

	// can not delete it
	elseif(!Safe::unlink($file))
		Logger::error(i18n::s('The configuration file cannot be deleted.'));

	// confirmation
	else {
		$context['text'] .= '<p>'.sprintf(i18n::s('The configuration file for virtual host %s has been deleted.'), $id).'</p>';

		// remember the change
		$label = sprintf(i18n::c('%s has been deleted'), $file);
		Logger::remember('control/virtual.php', $label);

	}

	// follow-up commands
	$follow_up = i18n::s('What do you want to do now?');
	$menu = array();
	$menu = array_merge($menu, array('control/virtual.php' => i18n::s('Manage virtual hosts')));
	$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
	$follow_up .= Skin::build_list($menu, 'page_menu');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

// save the content of an updated file
} elseif($id && isset($_REQUEST['content']) && $_REQUEST['content']) {

	// backup the old version, if any
	Safe::unlink($context['path_to_root'].'parameters/virtual_'.$id.'.included.php.bak');
	Safe::rename($context['path_to_root'].'parameters/virtual_'.$id.'.included.php', $context['path_to_root'].'parameters/virtual_'.$id.'.included.php.bak');

	// actual save
	if(Safe::file_put_contents('parameters/virtual_'.$id.'.included.php', $_REQUEST['content']) != strlen($_REQUEST['content']))
		Logger::error(sprintf(i18n::s('The target file %s may have been corrupted. Please check file content manually, and revert to the backup file, with the extension .bak, if necessary.'), 'parameters/virtual_'.$id.'.included.php'));

	// congratulations
	else {
		$context['text'] .= '<p>'.sprintf(i18n::s('The target file %s has been successfully updated.'), 'parameters/virtual_'.$id.'.included.php').'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array('control/virtual.php?id='.urlencode($id) => i18n::s('View the configuration file')));
		$menu = array_merge($menu, array('control/virtual.php' => i18n::s('Manage virtual hosts')));
		$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
		$follow_up .= Skin::build_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	}

// edit one configuration file
} elseif($id && ($action == 'edit')) {

	// file has to exist
	$file = 'parameters/virtual_'.$id.'.include.php';
	if(!$content = Safe::file_get_contents($context['path_to_root'].$file))
		Logger::error(i18n::s('No configuration file has been found for this virtual host.'));

	// offer to change file content
	else {
		// start of the form
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'"><div>'
			.'<input type="hidden" name="id" value="'.encode_field($id).'" />';

		// textarea to edit the file
		$context['text'] .= '<textarea name="content" rows="25" cols="50" accesskey="c">'.encode_field($content).'</textarea>';

		// button to upload changes
		$context['text'] .= BR.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</div></form>'."\n";

	}

// create a new configuration file
} elseif($id && ($action == 'new')) {

	// baseline file has to exist
	$source = 'parameters/control.include.php';
	$target = 'parameters/virtual_'.$id.'.include.php';
	if(!file_exists($context['path_to_root'].$source))
		Logger::error(i18n::s('No configuration file has been found for this virtual host.'));

	// can not create a new file
	elseif(!Safe::copy($source, $target))
		Logger::error(i18n::s('The configuration file cannot be created.'));

	// confirmation
	else {
		$context['text'] .= '<p>'.sprintf(i18n::s('The configuration file for virtual host %s has been created.'), $id).'</p>';

		// remember the change
		$label = sprintf(i18n::c('%s has been created'), $target);
		Logger::remember('control/virtual.php', $label);

	}

	// follow-up commands
	$follow_up = i18n::s('What do you want to do now?');
	$menu = array();
	$menu = array_merge($menu, array('control/virtual.php?id='.urlencode($id) => i18n::s('View the configuration file')));
	$menu = array_merge($menu, array('control/virtual.php' => i18n::s('Manage virtual hosts')));
	$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
	$follow_up .= Skin::build_list($menu, 'page_menu');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

// view one configuration file
} elseif($id && ($action == 'view')) {

	// file has to exist
	$file = 'parameters/virtual_'.$id.'.include.php';
	if(!$content = Safe::file_get_contents($context['path_to_root'].$file))
		Logger::error(i18n::s('No configuration file has been found for this virtual host.'));

	// display its content
	elseif(file_exists('../parameters/switch.on') || file_exists('../parameters/switch.off'))
		$context['text'] .= Skin::build_box(i18n::s('Configuration'), Safe::highlight_string($content), 'folder');
	else
		$context['text'] .= Safe::highlight_string($content);

	// follow-up commands
	$follow_up = i18n::s('What do you want to do now?');
	$menu = array();
	$menu = array_merge($menu, array('control/virtual.php?id='.urlencode($id).'&action=edit' => i18n::s('Configure again')));
	$menu = array_merge($menu, array('control/virtual.php' => i18n::s('Manage virtual hosts')));
	$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
	$follow_up .= Skin::build_list($menu, 'page_menu');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

// no action has been
} else {

	// list available configuration files
	if($items = Safe::glob($context['path_to_root'].'parameters/virtual_*.include.php')) {

		$rows = array();
		foreach($items as $item) {
			if(preg_match('/parameters\/virtual_(.+).include.php/', $item, $matches))
				$rows[] = array($matches[1], $matches[0]);
		}

		$context['text'] .= Skin::build_box(i18n::s('Configured virtual hosts'), Skin::table(NULL, $rows));

	}

	// follow-up commands
	$follow_up = i18n::s('What do you want to do now?');
	$menu = array();
	$menu = array_merge($menu, array('control/virtual.php?id='.urlencode($id).'&action=edit' => i18n::s('Configure again')));
	$menu = array_merge($menu, array('control/virtual.php' => i18n::s('Manage virtual hosts')));
	$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));
	$follow_up .= Skin::build_list($menu, 'page_menu');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

}

// render the skin
render_skin();

?>