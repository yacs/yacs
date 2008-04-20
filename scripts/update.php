<?php
/**
 * update scripts from this server
 *
 * If [code]$context['home_at_root'][/code] is set to '[code]Y[/code]' in [code]parameters/skins.include.php[/code], then
 * on update on [code]index.php[/code] the staging file will be duplicated as [code]../index.php[/code] as well.
 *
 * Also, all php scripts from [code]scripts/run_once[/code] are renamed before copying files
 * in order to avoid being stucked because of obsolete scripts there.
 *
 * If some file has been actually updated, the script attempts to include the special file [code]scripts/update_trailer.php[/code].
 * This file, which may be part of the update, bridges the old and the new set of scripts.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'scripts.php';

// what to do
$action = '';
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];
if(!$action && isset($context['arguments'][0]))
	$action = $context['arguments'][0];
$action = strip_tags($action);

// load the staging index, if any
Safe::load('scripts/staging/footprints.php');

// load localized strings
i18n::bind('scripts');

// load the skin
load_skin('scripts');

// the path to this page
$context['path_bar'] = array( 'scripts/' => i18n::s('Server software') );

// the title of the page
$context['page_title'] = i18n::s('Update scripts');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('scripts/update.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// invalid staging index
} elseif(!isset($generation['date']) || !$generation['date'] || !$generation['server'] || !is_array($footprints)) {
		$context['text'] .= '<p>'.i18n::s('Invalid reference footprints. Update has been cancelled.')."</p>\n";

		// forward to the index page
		$menu = array('scripts/' => i18n::s('Server software'));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

// actual update
} elseif($action == 'confirmed') {

	// the splash message
	$context['text'] .= '<p>'.i18n::s('Scripts of your server are now updated from the staging store. Please do not close your browser until the end of this process').'</p>';

	// switch the server off
	if(Safe::rename($context['path_to_root'].'parameters/switch.on', $context['path_to_root'].'parameters/switch.off')) {
		$context['text'] .= '<p><strong>'.i18n::s('The server has been switched OFF. Switch it back on as soon as possible.').'</strong>'.BR."\n";

		// remember the change
		$label = i18n::c('The server has been switched off.');
		$description = $context['url_to_home'].$context['url_to_root'].'scripts/update.php';
		Logger::remember('scripts/update.php', $label, $description);
	}

	// purge the scripts/run_once directory from previous content
	Scripts::purge_run_once();

	// list of updated scripts
	$context['text'] .= '<p>'.i18n::s('Updating scripts...').BR."\n";

	// use the footprints of reference files to locate updates
	$updated_files = 0;
	$missing_files = 0;
	$failures = 0;
	foreach($footprints as $file => $attributes) {

		// if the target script has been terminated by run_once.php, skip the update
		if(is_readable($context['path_to_root'].$file.'.done'))
			continue;

		// skip existing exact copies
		if(is_readable($context['path_to_root'].$file) && ($result = Scripts::hash($file))) {
			if($attributes[1] == $result[1])
				continue;

			// in case content has been compressed
			if(isset($attributes[2]) && ($attributes[2] == $result[3]))
				continue;
		}

		// we should have an updated file in the staging directory
		if(file_exists($context['path_to_root'].'scripts/staging/'.$file)) {
			;

		// we don't care about missing run-once scripts
		} elseif(preg_match('/\brun_once\b/i', $file)) {
			continue;

		// report on the missing file
		} else {
			$context['text'] .= sprintf(i18n::s('Error! Missing staging file %s. This update will be partial only.'), $file).BR."\n";
			$missing_files++;
			continue;
		}

		// this will be filtered by umask anyway
		Safe::chmod($context['path_to_root'].'scripts/staging/'.$file, $context['file_mask']);

		// maybe we have to update the front page
		if(($file == 'index.php') && isset($context['home_at_root']) && ($context['home_at_root'] == 'Y')) {
			if(Safe::copy($context['path_to_root'].'scripts/staging/index.php', $context['path_to_root'].'../index.php')) {
				$context['text'] .= sprintf(i18n::s('%s has been updated'), '../index.php').' ('.$attributes[0].' '.i18n::s('lines').')'.BR."\n";
				$updated_files++;

			// failed update
			} else {
				$context['text'] .= sprintf(i18n::s('Error! Unable to update %s.'), '../index.php').BR."\n";
				$failures++;
			}

		}

		// backup the old version, if any
		Safe::unlink($context['path_to_root'].$file.'.bak');
		Safe::rename($context['path_to_root'].$file, $context['path_to_root'].$file.'.bak');

		// ensure all folders exist
		if(!Safe::make_path(dirname($file))) {
			$context['text'] .= sprintf(i18n::s('Error! Unable to create path to %s.'), $file).BR."\n";
			$failures++;

		// actual update by moving the staging script
		} elseif(Safe::rename($context['path_to_root'].'scripts/staging/'.$file, $context['path_to_root'].$file)) {
			$context['text'] .= sprintf(i18n::s('%s has been updated'), $file.' ('.$attributes[0].' '.i18n::s('lines').')').BR."\n";
			$updated_files++;

		// failed update
		} else {
			$context['text'] .= sprintf(i18n::s('Error! Unable to update %s.'), $file).BR."\n";
			$failures++;
		}

		// ensure enough execution time
		Safe::set_time_limit(30);

	}

	// server is up to date
	if(!$updated_files && !$missing_files && !$failures) {
		$context['text'] .= i18n::s('Scripts on your server are exact copies of the reference set.')."</p>\n";

		// forward to the index page
		$menu = array('scripts/' => i18n::s('Server software'));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

	// no script has been modified
	} elseif(!$updated_files)
		$context['text'] .= i18n::s('No file has been updated.')."</p>\n";

	// scripts have been updated
	else {

		// report on updates
		$context['text'] .= sprintf(i18n::ns('%d file has been updated.', '%d files have been updated.', $updated_files), $updated_files)."</p>\n";

		// safe copy of footprints.php to the root directory
		Safe::unlink($context['path_to_root'].'footprints.php.bak');
		Safe::rename($context['path_to_root'].'footprints.php', $context['path_to_root'].'footprints.php.bak');
		Safe::copy($context['path_to_root'].'scripts/staging/footprints.php', $context['path_to_root'].'footprints.php');

		// load the special update follow-up, if any
		Safe::load('scripts/update_trailer.php');

		// next step
		$context['text'] .= '<p>'.i18n::s('Now that new scripts have been copied to your server, you should update the database as well. Please click on the link below before switching your server on again.').BR."\n";

		// look for hooks
		$context['text'] .= '<form method="post" action="'.$context['url_to_root'].'control/scan.php"><p>'
			.Skin::build_submit_button(i18n::s('Install all hooks'))
			.'<input type="hidden" name="action" value="build" />'
			.'</p></form>'."\n";

		// this may take several minutes
		$context['text'] .= '<p>'.i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.').'</p>';

		// purge the cache
		Cache::clear();

		// also delete regular temporary files -- see Cache::hash()
		if($items=Safe::glob($context['path_to_root'].'temporary/cache_*')) {
			foreach($items as $name)
				Safe::unlink($name);
		}

	}

	// report on failures, if any
	if($failures)
		$context['text'] .= '<p>'.i18n::s('Warning! Some files have not been updated.')."</p>\n";

	// report on missing files, if any
	if($missing_files) {
		$context['text'] .= '<p>'.i18n::s('Some updated files are missing. Please check the reference server below.')."</p>\n";

		// forward to the staging script
		$menu = array('scripts/stage.php' => i18n::s('Download updates from the reference server'));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');
	}

// list staging files
} else {

	// load the special update prefix, if any
	Safe::load('scripts/staging/scripts/update_header.php');

	// splash message
	$context['text'] .= '<p>'.sprintf(i18n::s('Reference set of %d files built on %s on server %s'), count($footprints), $generation['date'], $generation['server'])."</p>\n";

	// sort footprints by alphabetical order
	ksort($footprints);
	reset($footprints);

	// use footprints of reference files to locate updates
	$staging_files = 0;
	$missing_files = 0;
	$box = '';
	foreach($footprints as $file => $attributes) {

		// only consider php scripts at the moment
		if(!preg_match('/\.php$/', $file))
			continue;

		// is the current file version ok?
		if(is_readable($context['path_to_root'].$file) && ($result = Scripts::hash($file))) {
			if($attributes[1] == $result[1])
				continue;

			// in case content has been compressed
			if(isset($attributes[2]) && ($attributes[2] == $result[3]))
				continue;
		}

		// maybe the script has already been executed -- never expect an exact copy
		if(is_readable($context['path_to_root'].$file.'.done') && ($result = Scripts::hash($file.'.done')))
			continue;

		// we should have an updated file in the staging directory
		if(!is_readable($context['path_to_root'].'scripts/staging/'.$file)) {
			$box .= sprintf(i18n::s('Error! Missing staging file %s. Update has been cancelled.'), $file).BR."\n";
			$missing_files++;
			continue;
		}

		// file should have some content --useful when space quota is full
		if(!Safe::filesize($context['path_to_root'].'scripts/staging/'.$file)) {
			$box .= sprintf(i18n::s('Error! Empty staging file %s. Update has been cancelled.'), $file).BR."\n";
			$missing_files++;
			continue;
		}

		// ensure we have an exact copy
		$result = Scripts::hash('scripts/staging/'.$file);
		if(($attributes[1] != $result[1]) && (isset($attributes[2]) && ($attributes[2] != $result[3]))) {
			$box .= sprintf(i18n::s('Error! File %s has been corrupted. Update has been cancelled.'), $file).BR."\n";
			$missing_files++;
			continue;
		}

		// report on the updated script
		$context['file_bar'] = array( 'scripts/browse.php?script='.$file.'&store=staging' => i18n::s('Review'),
			 'scripts/compare.php?original='.$file.'&updated=scripts/staging/'.$file.'&format=gdiff' => i18n::s('Diff') );
		$box .= $file.' ('.$attributes[0].' '.i18n::s('lines').') '.Skin::build_list($context['file_bar'], 'menu').BR."\n";
		$staging_files++;

		// ensure enough execution time
		Safe::set_time_limit(30);

	}

	// make a folded box
	if($box)
		$context['text'] .= Skin::build_box(i18n::s('Staging scripts'), $box, 'folder');

	// missing files
	if($missing_files) {
		$context['text'] .= '<p>'.i18n::s('Some updated files are missing. Please check the reference server.')."</p>\n";

		// forward to the staging script
		$menu = array('scripts/stage.php' => i18n::s('Stage updated scripts'));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

	// server is up to date
	} elseif(!$staging_files) {
		$context['text'] .= '<p>'.i18n::s('No file has been downloaded. Scripts on your server are exact copies of the reference set.')."</p>\n";

		// forward to the index page
		$menu = array('scripts/' => i18n::s('Server software'));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

	// scripts are ready for update
	} else {
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d script has to be updated.', '%d scripts have to be updated.', $staging_files), $staging_files)."</p>\n";

		// the splash message
		$context['text'] .= '<p>'.i18n::s('Click on the button below to actually update running scripts on your server. Please note that your server will be temporarily switched off, and that you will also have to refresh the database.')."</p>\n";

		// propose to update the server
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'"><p>'
			.Skin::build_submit_button(i18n::s('Yes, I have checked every updates and want to update scripts on this server'))
			.'<input type="hidden" name="action" value="confirmed" />'
			.'</p></form>'."\n";

	}

}

// render the skin
render_skin();

?>