<?php
/**
 * the posting form as a browser sidebar
 *
 * @todo add an option to make it post bookmarks
 * @link http://quikonnex.com/start/ Channel Viever and Bookmark Server
 *
 * This is a simple form to ease blogger work. Put text here while browsing.
 * Then hit the submit button to push it at your own web site.
 *
 * The panel is always displayed, even to anonymous surfers, since access rules are enforced by the
 * edit form in the main panel anyway.
 *
 * @see articles/edit.php
 *
 * This panel supports Gecko-based browsers, including Mozilla and Firefox, and Internet Explorer.
 *
 * For Mozilla and Firefox, links will target the panel '[code]_content[/code]'. This is the default.
 * Else the target frame can be overriden by passing the parameter '[code]target[/code]'.
 * To make Internet Explorer work correctly this parameter should have the value '[code]_main[/code]'.
 *
 * This panel may be installed from the Control Panel.
 *
 * @see control/index.php
 *
 * Color and style inspired by [link=Live Sidebar Note-it]http://livesidebar.com/lsbtabs/notes[/link].
 *
 * @link http://livesidebar.com/lsbtabs/notes Live Sidebar Note-it
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once 'shared/global.php';

// look for the target
$target = '_content';
if(isset($_REQUEST['target']))
	$target = $_REQUEST['target'];
elseif(isset($context['arguments'][0]))
	$target = $context['arguments'][0];
$target = strip_tags($target);

// load localized strings
i18n::bind('root');

// load the skin
load_skin('panel');

// the title of the page
$context['page_title'] = i18n::s('New page');

// the form to edit an article
$context['text'] .= '<form id="edit_form" method="post"'
	.' action="'.$context['url_to_root'].'articles/edit.php" target="'.htmlspecialchars($target).'" id="main_form">';

// the section
$context['text'] .= '<p>'.i18n::s('Section').BR
	.'<select name="anchor">'.Sections::get_options().'</select>'
	.'</p>'."\n";

// the title
$context['text'] .= '<p>'.i18n::s('Title').BR
	.'<textarea name="title" id="title" rows="2" cols="20" accesskey="t"></textarea>'
	.'</p>'."\n";

// the introduction
$context['text'] .= '<p>'.i18n::s('Introduction').BR
	.'<textarea name="introduction" rows="2" cols="20" accesskey="i"></textarea>'
	.'</p>'."\n";

// the description label
$context['text'] .= '<p>'.i18n::s('Page content').BR
	.'<textarea name="description" id="edit_area" rows="10" cols="20" accesskey="c"></textarea>'
	.'</p>'."\n";

// the submit and reset buttons
$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's', NULL, 'no_spin_on_click')
	.' <input type="reset" value="'.encode_field(i18n::s('Reset')).'" accesskey="r" title="'.encode_field(i18n::s('Reset')).'" /></p>'."\n";

// end of the form
$context['text'] .= '</form>';

// handle the output correctly
render_raw();

// if it was a HEAD request, stop here
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// do our own rendering
echo "<html>\n<head>\n";

// the title
if(isset($context['page_title']))
	echo '<title>'.ucfirst(strip_tags($context['page_title'])).'</title>';

// styles
echo '<style type="text/css" media="screen">'."\n"
	.'<!--'."\n"
	.'	body, body p {'."\n"
	.'		font-family: Verdana, Arial, Helvetica, sans-serif;'."\n"
	.'		font-size:	  x-small;'."\n"
	.'		voice-family: "\"}\"";'."\n"
	.'		voice-family: inherit;'."\n"
	.'		font-size:	  small;'."\n"
	.'	}'."\n"
	."\n"
	.'	body {'."\n"
	.'		scrollbar-face-color: #FFFFAA;'."\n"
	.'	}'."\n"
	."\n"
	.'	textarea {'."\n"
	.'		border: 0px none;'."\n"
	.'		width: 99%;'."\n"
	.'		background-color: rgb(255, 255, 170);'."\n"
	.'	}'."\n"
	.'-->'."\n"
	.'</style>'."\n";

// end of meta information
echo "</head>\n<body>\n";

// display error messages, if any
echo Skin::build_error_block();

// render and display the content
if(isset($context['text']))
	echo $context['text'];

// debug output, if any
if(is_array($context['debug']) && count($context['debug']))
	echo "\n".'<ul id="debug">'."\n".'<li>'.implode('</li>'."\n".'<li>', $context['debug']).'<li>'."\n".'</ul>'."\n";

echo "\n</body>\n</html>";

?>