<?php
/**
 * test phpdoc comments for one running script
 *
 * Use this script during php comments editing, just to check that everything is going fine
 * before actually building the complete set of reference files and documentation pages.
 *
 * Accept following invocations:
 * - parse.php/skins/skeleton/skin.php
 * - parse.php?script=/skins/skeleton/skin.php
 *
 * @author Bernard Paques
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
}
$script = preg_replace(FORBIDDEN_IN_PATHS, '', strip_tags($script));

// map this name on the actual file system
$translated = NULL;
if($script)
	$translated = $context['path_to_root'].'scripts/reference/'.$script;

// load localized strings
i18n::bind('scripts');

// load the skin
load_skin('scripts');

// the path to this page
$context['path_bar'] = array( 'scripts/' => i18n::s('Server software') );

// the title of the page
if($script)
	$context['page_title'] = $script;
else
	$context['page_title'] = i18n::s('View PHP documentation');

// menu bar
if($script) {
	$context['page_menu'] = array( 'scripts/browse.php?script='.$script => i18n::s('Browse'),
		'scripts/fetch.php?script='.$script => i18n::s('Fetch'),
		'scripts/' => i18n::s('Server software') );
}

// no script has been provided
if(!$script) {
	Skin::error(i18n::s('No script has been provided.'));

// the script has to be there
} elseif(!file_exists($translated))
	Skin::error(i18n::s('Script does not exist.'));

// display script content
else {

	include_once 'phpdoc.php';
	$tool =& new PhpDoc;

	// parse the file
	$context['text'] .= $tool->parse($script, '');

	// generate the php documentation for this script
//	$context['page_title'] = $tool->index[$script];
	$context['text'] .= Codes::beautify('[toc] '.$tool->comments[$script]);

}

// render the skin
render_skin();

?>