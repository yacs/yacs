<?php
/**
 * A liquid 3-column XHTML tabbed interface
 *
 * This skin features:
 * - fixed and dynamic tabs
 * - a fixed left column
 * - an optional 3rd column on the right
 * - size of the 3rd column may adjusted through CSS depending on the URL
 *
 * The layout is made of following components:
 * <ul>
 * <li>div#header_panel</li>
 *	 <ul>
 *	 <li>p#header_title</li>
 *	 <li>p#header_slogan</li>
 *	 <li>div.tabs</li>
 *	 </ul>
 * <li>div#wrapper</li>
 *	<ul>
 *	<li>div#extra_panel</li>
 *	<li>div#main_panel</li>
 *	 <ul>
 *	 <li>p#crumbs - path to this page</li>
 *	 <li>img.icon - the main image for this page</li>
 *	 <li>h1 - page title</li>
 *	 <li>p#page_menu - commands for this page</li>
 *	 <li>div.error - to report on errors, if any</li>
 *	 <li>(page main content)</li>
 *	 </ul>
 *	<li>div#footer_panel</li>
 *	</ul>
 * <li>div#side_panel</li>
 * </ul>
 *
 * Also, the main [code]body[/code] has an id, which is the skin variant, and a class, which is "extra" when $context['extra'] is not empty.
 *
 * These can be combined in the style sheet to match particular situations. Some examples:
 * - body#home -- at the front page
 * - body#sections -- section page, or section index
 * - body#articles -- regular page, or index of articles
 * - body.extra -- when some extra content has been inserted in the page
 *
 * This has been validated as XHTML 1.0 Transitional
 *
 * @link http://keystonewebsites.com/articles/mime_type.php Serving up XHTML with the correct MIME type
 *
 * This template implements following access keys at all pages:
 * - 1 to go to the front page of the site
 * - 2 to skip the header and jump to the main area of the page
 * - 9 to go to the control panel
 * - 0 to go to the help page
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
defined('YACS') or exit('Script must be included');

// add language information, if known
if(isset($context['page_language']))
	$language = ' xml:lang="'.$context['page_language'].'" ';
else
	$language = '';

// start the page
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'."\n"
	.'<html '.$language.' xmlns="http://www.w3.org/1999/xhtml">'."\n"
	.'<head>'."\n";

// give the charset to the w3c validator...
echo "\t".'<meta http-equiv="Content-Type" content="'.$context['content_type'].'; charset='.$context['charset'].'" />'."\n";

// we have one style sheet for everything -- media="all" means it is not loaded by Netscape Navigator 4
echo "\t".'<link rel="stylesheet" href="'.$context['url_to_root'].'skins/digital/digital.css" type="text/css" media="all" />'."\n";

// implement the 'you are here' feature
if($focus = Page::top_focus()) {
	echo "\t".'<style type="text/css" media="screen">'."\n"
		."\t\t".'ul li#'.$focus.' a {'."\n"
		."\t\t\t".'background-position: 0% -44px;'."\n"
		."\t\t\t".'border-bottom: 1px solid #fff;'."\n"
		."\t\t".'}'."\n"
		."\t\t".'ul li#'.$focus.' a span {'."\n"
		."\t\t\t".'background-position: 100% -44px;'."\n"
		."\t\t\t".'color: #039;'."\n"
		."\t\t".'}'."\n"
		."\t".'</style>'."\n";
}

// set icons for this site
if(!$context['site_icon']) {
	echo "\t".'<link rel="shortcut icon" href="'.$context['url_to_root'].'skins/digital/squares.ico" type="image/x-icon" />'."\n";
	echo "\t".'<link rel="icon" href="'.$context['url_to_root'].'skins/digital/squares.ico" type="image/x-icon" />'."\n";
}

// other head directives
Page::meta();

// end of the header
echo '</head>'."\n";

// start the body
Page::body();

// the default header panel
Page::header_panel();

// several columns in the middle
echo '<div id="wrapper">'."\n";

// the main panel
echo '<div id="main_panel">'."\n";

// display bread crumbs if not at the front page; if not defined, only the 'Home' link will be displayed
if($context['skin_variant'] != 'home')
	Page::bread_crumbs(2);

// display main content
Page::content();

// ensure we have some content
echo '<br style="clear: left;" />&nbsp;'."\n";

// end of the main panel
echo '</div>'."\n";

// display complementary information, if any
Page::extra_panel();

// the footer panel
echo '<div id="footer_panel">';

// page footer --we have been validated
Page::footer('<a href="http://validator.w3.org/check?uri=referer"><img src="'.$context['url_to_root'].$context['skin'].'/images/buttons_xhtmlw3c.gif" alt="XHTML 1.0!" height="15" width="80" /></a><br />');

// end of the footer panel
echo '</div>'."\n";

// end of the wrapper
echo '</div>'."\n";

// navigation information
echo '<div id="side_panel">'."\n";

// display side content
Page::side();

// link to yacs if we are at the front page
if(($context['skin_variant'] == 'home') && is_callable(array('i18n', 's')))
	echo Skin::build_box(NULL, '<p>'.sprintf(i18n::s('Powered by %s'), Skin::build_link(i18n::s('http://www.yacs.fr/'), 'Yacs', 'external')).'</p>', 'extra');

// end of the side panel
echo '</div>'."\n";

// insert the dynamic footer, if any, including inline scripts
echo $context['page_footer'];

// end of the page
echo '</body>'."\n"
	.'</html>';

?>
