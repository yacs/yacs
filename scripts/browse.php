<?php
/**
 * browse one reference or one staging script
 *
 * Accept following invocations:
 * - browse.php/files/delete.php
 * - browse.php?script=/files/delete.php&store=reference
 * - browse.php?script=/files/delete.php&store=staging
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @tester Manuel López Gallego
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'scripts.php';

// the target script
$script = '';
if(isset($_REQUEST['script']))
	$script = $_REQUEST['script'];
elseif(isset($context['arguments'][0])) {
	$script = $context['arguments'][0];
	if(isset($context['arguments'][1]))
		$script .= '/'.$context['arguments'][1];
	if(isset($context['arguments'][2]))
		$script .= '/'.$context['arguments'][2];
	if(isset($context['arguments'][3]))
		$script .= '/'.$context['arguments'][3];
	if(isset($context['arguments'][4]))
		$script .= '/'.$context['arguments'][4];
	if(isset($context['arguments'][5]))
		$script .= '/'.$context['arguments'][5];
}

// fight hackers
$script = preg_replace(FORBIDDEN_STRINGS_IN_PATHS, '', strip_tags($script));

// either in the reference or in the staging store
$store = NULL;
if(isset($_REQUEST['store']))
	$store = $_REQUEST['store'];
$store = preg_replace(FORBIDDEN_STRINGS_IN_PATHS, '', strip_tags($store));
if(($store != 'reference') && ($store != 'staging'))
	$store = 'reference';

// map this name on the actual reference file
$translated = '';
if($script)
	$translated = $context['path_to_root'].'scripts/'.$store.'/'.$script;

// load localized strings
i18n::bind('scripts');

// load the skin
load_skin('scripts');

// the path to this page
$context['path_bar'] = array( 'scripts/' => i18n::s('Scripts') );

// the title of the page
if($script && ($store == 'reference'))
	$context['page_title'] = sprintf(i18n::s('Reference script: %s'), $script);
elseif($script && ($store == 'staging'))
	$context['page_title'] = sprintf(i18n::s('Staging script: %s'), $script);
else
	$context['page_title'] = i18n::s('Script view');

// no script has been provided
if(!$script)
	Skin::error(i18n::s('No script has been provided.'));

// the script has to be there
elseif(!file_exists($translated))
	Skin::error(i18n::s('Script does not exist.'));

// display script content
else {

	// lookup for information inside the file
	$content = Safe::file_get_contents($translated);

	// protect from spammers and robots
	$content = preg_replace('/\[email\].+\[\/email\]/i', '', $content);

	// menu bar for reference scripts
	if($content && ($store == 'reference')) {

		// browsing is safe
		$context['page_menu'] = array_merge($context['page_menu'], array( Scripts::get_url($script, 'view') => i18n::s('View the documentation page') ));

		// protect from spammers and robots
		if(Surfer::is_logged())
			$context['page_menu'] = array_merge($context['page_menu'], array( Scripts::get_url($script, 'fetch') => i18n::s('Fetch the script file') ));
	}

	// highlight php code
	$context['text'] .= "\n".Codes::render_pre($content);

}

// render the skin
render_skin();

?>