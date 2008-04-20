<?php
/**
 * view php documentation for one reference script
 *
 * The extra panel has following elements:
 * - The top popular referrals, if any
 *
 * Accept following invocations:
 * - view.php/skins/skeleton/skin.php
 * - view.php?script=/skins/skeleton/skin.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// the target script
$script = NULL;
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

// fight against hackers
$script = preg_replace(FORBIDDEN_STRINGS_IN_PATHS, '', strip_tags($script));

// get the actual page
include_once 'scripts.php';
include_once 'phpdoc.php';
$row = PhpDoc::get($script);
if(!$row)
	$row = PhpDoc::get($script.'/index.php');

// load localized strings
i18n::bind('scripts');

// load the skin
load_skin('scripts');

// the path to this page
$context['path_bar'] = array( 'scripts/' => i18n::s('Server software') );

// the title of the page
if($script == 'todo')
	$context['page_title'] = i18n::s('To-do list');
elseif($script == 'authors')
	$context['page_title'] = i18n::s('Authors of all those scripts');
elseif($script == 'testers')
	$context['page_title'] = i18n::s('Testers of all those scripts');
elseif($script == 'licenses')
	$context['page_title'] = i18n::s('Licenses for all those scripts');
elseif($script)
	$context['page_title'] = i18n::s('Documentation:').' '.$script;
else
	$context['page_title'] = i18n::s('View PHP documentation');

// menu bar
if($script) {

	// a regular script
	if(($script != 'todo') && ($script != 'authors') && ($script != 'testers') && ($script != 'licenses')) {

		// browsing is safe
		$context['page_menu'] = array_merge($context['page_menu'], array( Scripts::get_url($script, 'browse') => i18n::s('Browse the source of this script') ));

		// protect from spammers and robots
		if(Surfer::is_logged())
			$context['page_menu'] = array_merge($context['page_menu'], array( Scripts::get_url($script, 'fetch') => i18n::s('Fetch the script file') ));
	}

	// back to the index
	$context['page_menu'] = array_merge($context['page_menu'], array( 'scripts/' => i18n::s('Server software') ));
}

// no script has been provided -- help web crawlers
if(!$script) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No script has been provided'));

// the script has to be there
} elseif(!$row) {
	if(preg_match('/(todo|authors|testers|licenses)/i', $script))
		Skin::error(i18n::s('The reference repository is empty. Please (re)build it.'));
	else
		Skin::error(i18n::s('Script does not exist'));


// display script content
} else {

	$context['text'] = Codes::beautify($row['content']);

	// referrals, if any
	if(Surfer::is_associate() || (isset($context['with_referrals']) && ($context['with_referrals'] == 'Y'))) {

		$cache_id = 'scripts/view.php?script='.$script.'#referrals#';
		if(!$text =& Cache::get($cache_id)) {

			// box content in a sidebar box
			include_once '../agents/referrals.php';
			if($text = Referrals::list_by_hits_for_url($context['url_to_root_parameter'].Scripts::get_url($script)))
				$text =& Skin::build_box(i18n::s('Referrals'), $text, 'navigation', 'referrals');

			// save in cache for one hour 60 * 60 = 3600
			Cache::put($cache_id, $text, 'referrals', 3600);

		}

		// in the extra panel
		$context['extra'] .= $text;
	}

}

// render the skin
render_skin();

?>