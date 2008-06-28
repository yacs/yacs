<?php
/**
 * help to finalize scripts update
 *
 * Library files may have been updated during the update process.
 * This trailer ensures that they are included properly before the finalization of the update page.
 *
 * @see scripts/update.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// do not move forward on validation
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// version 6.5 - some library files have been introduced
if(!class_exists('Safe'))
	include_once $context['path_to_root'].'shared/safe.php';
if(!class_exists('i18n'))
	include_once $context['path_to_root'].'i18n/i18n.php';
if(!class_exists('SQL'))
	include_once $context['path_to_root'].'shared/sql.php';

// version 7.3 - copy parameters files where there are supposed to be --i18n::s does not exist before 6.12
if(file_exists($context['path_to_root'].'shared/hooks.include.php') && !Safe::copy($context['path_to_root'].'shared/hooks.include.php', $context['path_to_root'].'parameters/hooks.include.php'))
	echo sprintf('Please copy %s to %s manually before moving forward', 'shared/hooks.include.php', 'parameters/hooks.include.php').BR;
if(file_exists($context['path_to_root'].'shared/parameters.include.php') && !Safe::copy($context['path_to_root'].'shared/parameters.include.php', $context['path_to_root'].'parameters/control.include.php'))
	echo sprintf('Please copy %s to %s manually before moving forward', 'shared/parameters.include.php', 'parameters/control.include.php').BR;
if(file_exists($context['path_to_root'].'skins/parameters.include.php') && !Safe::copy($context['path_to_root'].'skins/parameters.include.php', $context['path_to_root'].'parameters/skins.include.php'))
	echo sprintf('Please copy %s to %s manually before moving forward', 'skins/parameters.include.php', 'parameters/skins.include.php').BR;
if(file_exists($context['path_to_root'].'switch.on') && !Safe::copy($context['path_to_root'].'switch.on', $context['path_to_root'].'parameters/switch.on'))
	echo sprintf('Please copy %s to %s manually before moving forward', 'switch.on', 'parameters/switch.on').BR;
if(file_exists($context['path_to_root'].'switch.off') && !Safe::copy($context['path_to_root'].'switch.off', $context['path_to_root'].'parameters/switch.off'))
	echo sprintf('Please copy %s to %s manually before moving forward', 'switch.off', 'parameters/switch.off').BR;

// version 7.6 - normalize_url() has been introduced
if(!is_callable('normalize_url')) {

	function normalize_url($prefix, $action, $id, $name=NULL) {
		global $context;

		// sanity check
		if(!$id)
			return NULL;

		// separate args
		if(is_array($prefix))
			$module = $prefix[0];
		else
			$module = $prefix;

		// minimal duty
		return $module.'/'.$action.'.php?id='.urlencode($id);
	}

}

// version 7.10
if(!isset($context['content_type']))
	$context['content_type'] = 'text/html';

// version 8.4
if(!defined('YACS'))
	define('YACS', TRUE);

// version 8.5 - new side menu
if(!isset($context['page_tools']))
	$context['page_tools'] = array();
if(!isset($context['script_url']))
	$context['script_url'] = '';

// version 8.6 - new page components
if(!isset($context['extra_prefix']))
	$context['extra_prefix'] = '';
if(!isset($context['page_tags']))
	$context['page_tags'] = '';

// force a refresh of compacted javascript libraries
if($items=Safe::glob($context['path_to_root'].'temporary/cache_*.js')) {
	foreach($items as $name)
		Safe::unlink($name);
}

// safe copy of footprints.php to the root directory
Safe::unlink($context['path_to_root'].'footprints.php.bak');
Safe::rename($context['path_to_root'].'footprints.php', $context['path_to_root'].'footprints.php.bak');
Safe::copy($context['path_to_root'].'scripts/staging/footprints.php', $context['path_to_root'].'footprints.php');

// remember this as a significant event --i18n::s does not exist before 6.12
Logger::remember('scripts/update_trailer.php', 'update trailer has been executed');

?>