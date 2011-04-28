<?php
/**
 * handling PHP scripts
 *
 * While YACS is aiming to ease content publishing and sharing, we are thinking that the same objective
 * should be targeted for the software itself.
 *
 * More specifically, we have designed this module with the manipulation of numerous php script files in mind:
 * - to derive on-line documentation from php comments
 * - to list reference scripts that are shared on a server
 * - to visualize differences between reference and running scripts
 * - to suggest on software updates from a reference server
 *
 * This module is based on several simple patterns:
 * - the remote reference pattern is used to synchronize one server with one reference server
 * - the local reference pattern is used to build the documentation and to publish shared scripts
 *
 * [title]Update your scripts from a remote reference repository[/title]
 *
 * The remote reference pattern consists of scanning a remote server to build a set of staging files.
 * After having setup the address of the reference server (through [script]scripts/configure.php[/script]), the webmaster
 * will have to activate the staging script ([script]scripts/stage.php[/script]).
 * The script will fetch the index of reference scripts (in [code]footprints.php[/code]), and compare its content
 * to the actual set of running scripts.
 * For each running script having a diverging md5 signature, the script will cache the reference version
 * into [code]scripts/staging[/code].
 * As a result, at the end of the staging process:
 * - all running scripts will have been compared to the reference scripts
 * - for each non-matching file, a staging version will have been prepared
 *
 * After the staging step the update itself can take place by launching [script]scripts/update.php[/script].
 * First of all, this script displays the list of staging files, and offer the opportunity to read the new version
 * or to list differences.
 * The diff algorithm has been implemented into [script]scripts/scripts.php[/script] for that purpose.
 * To actually update the system, [script]scripts/update.php[/script] rename old versions as '.bak', and move
 * new versions from the staging directory to their final location.
 *
 * Aside from the new scripts other updates will take place thanks to following steps:
 * - [script]scripts/update.php[/script] also switches the server off
 * - [script]control/scan.php[/script] will rebuild all hooks, to possibly take into account new hooks downloaded from the reference server
 * - [script]control/setup.php[/script] will check the database structure, and create it if necessary
 * - [script]scripts/run_once.php[/script] is launched, to possibly apply big changes to an existing database
 * - [script]control/index.php[/script] displays the control panel and gives the opportunity to switch the server on again.
 *
 * [title]Make a reference repository of your running scripts[/title]
 *
 * The local reference pattern consists of building a safe repository of scripts and of related information
 * page. Once one server has been setup correctly, maybe after some script modifications, skin updates, etc., the
 * webmaster (actually, any associate) may wish to freeze this configuration as the reference one.
 * By activating the building script ([script]scripts/build.php[/script]), he (or she) will copy reference scripts to a safe place
 * (scripts/reference). These scripts will be scanned for structured comments (see [script]scripts/phpdoc.php[/script]) and the
 * related documentation will be generated automatically.
 * An index of reference scripts, including file names, dates and an md5 hash code, will be generated as well
 * for further checking by other servers.
 * As a result, at the end of the building process:
 * - a complete set of reference scripts is saved in their 'sand box'
 * - the documentation for these scripts is published
 * - an inventory of these files is published (scripts/reference/footprints.php)
 *
 * How to define that a script is to be included in the reference set? Simple: put the keyword &arobas;reference
 * on a single line of the first php comment of the script.
 *
 * [title]How to use these mechanisms in your particular situation?[/title]
 *
 * Despite the simplicity of these patterns, we are quite confindent that most webmasters will appreciate them over time.
 * At least, they enable smooth updates of running scripts for most (minor) modifications.
 * Moreover, these patterns will successfully frequent models of web deployments:
 *
 * - Standalone servers will benefit from the building script to at least generate and update a (tiny?) set
 * of useful pages to document the software. Note that these pages are fully indexed to enable free-text searches.
 *
 * - Servers connected to the Internet will have the opportunity to track YACS enhancements quite easily, simply by
 * checking from time to time one the public reference servers.
 *
 * - Groups of servers managed by a single webmasters may be linked to a single private reference server. It is likely
 * that various organizations will develop additional scripts on the YACS platform. They may use the remote reference
 * pattern to distribute these scripts internally to slave servers.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// include the global declarations
include_once '../shared/global.php';
include_once 'scripts.php';

// address of the reference server, if any
Safe::load('parameters/scripts.include.php');

// load localized strings
i18n::bind('scripts');

// load the skin
load_skin('scripts');

// the title of the page
$context['page_title'] = i18n::s('Server software');

// associates may trigger incremental upgrades, but not at reference servers
if(Surfer::is_associate() && !file_exists('reference/footprints.php')) {

	// upgrade
	$context['text'] .= Skin::build_block(i18n::s('Incremental upgrades'), 'title');

	// the status message
	Safe::load('footprints.php');
	if(isset($generation['date']) && $generation['date'])
		$context['text'] .= '<p>'.sprintf(i18n::s('Last update took place on %s'), $generation['date']).'</p>'."\n";
	unset($generation);

	// ensure we have a reference server
	if(!isset($context['reference_server']) || !$context['reference_server'])
		$context['reference_server'] = i18n::s('www.yacs.fr');

	// offer to upgrade
	$context['text'] .= '<p>'.Skin::build_link('scripts/stage.php', i18n::s('Update the software'))."</p>\n";

}

// get the page from the php documentation, if any
include_once 'phpdoc.php';
$item = PhpDoc::get('index');
if($item) {

	$items = array();

	// the list of things to do
	$items[] = Skin::build_link(Scripts::get_url('todo'), i18n::s('To do'), 'basic');

	// the list of testers
	$items[] = Skin::build_link(Scripts::get_url('testers'), i18n::s('Testers'), 'basic');

	// the list of authors
	$items[] = Skin::build_link(Scripts::get_url('authors'), i18n::s('Authors'), 'basic');

	// the list of licenses
	$items[] = Skin::build_link(Scripts::get_url('licenses'), i18n::s('Licenses'), 'basic');

	$context['components']['tools'] = Skin::build_box(i18n::s('See also'), Skin::finalize_list($items, 'newlines'), 'extra');

	// splash message
	$text = '<p>'.i18n::s('Click on any link below to access the documentation extracted from each script (phpDoc).')."</p>\n";

	// tree of links to documentation pages
	$text .= Codes::beautify($item['content']);

// link to some other server
} else {

	$text = '<p>'.i18n::s('The complete documentation is available at the following server:').'</p>';

	// link to the existing reference server, or to the original server
	if(!isset($context['reference_server']) || !$context['reference_server'])
		$context['reference_server'] = i18n::s('www.yacs.fr');
	$text .= '<p>'.Skin::build_link('http://'.$context['reference_server'].'/', $context['reference_server'], 'external')."</p>\n";

}

// documentation box
$context['text'] .= Skin::build_box(i18n::s('On-line Documentation'), $text);

// page tools
if(Surfer::is_associate()) {

	// patch the server
	$context['page_tools'][] = Skin::build_link('scripts/upload.php', i18n::s('Apply a patch'), 'basic');

	// signal scripts to run once, if any
	if(Safe::glob($context['path_to_root'].'scripts/run_once/*.php') !== FALSE)
		$context['page_tools'][] = Skin::build_link('scripts/run_once.php', i18n::s('Run once'), 'basic');

	// stage from reference server
	$context['page_tools'][] = Skin::build_link('scripts/stage.php', i18n::s('Update the software'), 'basic');

	// set the reference server
	$context['page_tools'][] = Skin::build_link('scripts/configure.php', i18n::s('Configure'), 'basic');

	// check script signatures
	$context['page_tools'][] = Skin::build_link('scripts/check.php', i18n::s('Check software integrity'), 'basic');

	// validate scripts
	$context['page_tools'][] = Skin::build_link('scripts/validate.php', i18n::s('Validate PHP syntax'), 'basic');

	// build the reference here
	$context['page_tools'][] = Skin::build_link('scripts/build.php', i18n::s('Build the software'), 'basic');

}

// referrals, if any
$context['components']['referrals'] = Skin::build_referrals('scripts/index.php');

// render the skin
render_skin();

?>
