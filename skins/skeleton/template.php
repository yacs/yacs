<?php
/**
 * A liquid 2-column XHTML tabbed interface
 *
 * The layout makes things natural if styles are disactivated. It is made of following components:
 * <ul>
 * <li>div#header_panel</li>
 *	 <ul>
 *	 <li>p#header_title</li>
 *	 <li>p#header_slogan</li>
 *	 <li>ul#tabs</li>
 *	 </ul>
 * <li>div#wrapper</li>
 *	<ul>
 *	<li>div#wrapper_prefix - top background decoration</li>
 *	<li>div#main_panel</li>
 *	 <ul>
 *	 <li>p#crumbs - path to this page</li>
 *	 <li>img.icon - the main image for this page</li>
 *	 <li>h1 - page title</li>
 *	 <li>p#page_menu - commands for this page</li>
 *	 <li>div.error - to report on errors, if any</li>
 *	 <li>(page main content)</li>
 *	 </ul>
 *	<li>div#side_panel</li>
 *	<li>div#wrapper_suffix - bottom background decoration</li>
 *	</ul>
 * <li>div#footer_panel</li>
 * </ul>
 *
 * @link http://www.ilovejackdaniels.com/design/faux-columns-for-liquid-layouts/ Faux Columns for Liquid Layouts
 *
 * Also, the main [code]body[/code] has an id, which is the skin variant, and a class, which is "extra" when $context['extra'] is not empty.
 *
 * These can be combined in the style sheet to match particular situations. Some examples:
 * - body#home -- at the front page
 * - body#sections -- section page, or section index
 * - body#articles -- regular page, or index of articles
 *
 * Background images have been submitted gracefully by [link=Dave Child]http://www.ilovejackdaniels.com/[/link], who is really a gentleman.
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
 * @author Dave Child [link]http://www.ilovejackdaniels.com/[/link]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
defined('YACS') or exit('Script must be included');

// build the prefix only once
if(!isset($context['embedded']) || ($context['embedded'] == 'prefix')) {

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
	echo "\t".'<link rel="stylesheet" href="'.$context['url_to_root'].'skins/skeleton/skeleton.css" type="text/css" media="all" />'."\n";

	// nice titles
	echo "\t".'<link rel="stylesheet" href="'.$context['url_to_root'].'skins/skeleton/nicetitle.css" type="text/css" media="all" />'."\n";

	// implement the 'you are here' feature
	if($focus = Page::top_focus()) {
		echo "\t".'<style type="text/css" media="screen">'."\n"
			."\t\t".'div.tabs ul li#'.$focus.' {'."\n"
			."\t\t\t".'background-position: 100% -200px;'."\n"
			."\t\t\t".'border-bottom: 1px solid #fff;'."\n"
			."\t\t".'}'."\n"
			."\t\t".'div.tabs ul li#'.$focus.' a,'."\n"
			."\t\t".'div.tabs ul li#'.$focus.' a span {'."\n"
			."\t\t\t".'background-position: 0% -200px;'."\n"
			."\t\t\t".'color: #039;'."\n"
			."\t\t".'}'."\n"
			."\t\t".'div.tabs ul li#'.$focus.':hover {'."\n"
			."\t\t\t".'background-position: 100% 0%;'."\n"
			."\t\t".'}'."\n"
			."\t\t".'div.tabs ul li#'.$focus.':hover a,'."\n"
			."\t\t".'div.tabs ul li#'.$focus.':hover a span {'."\n"
			."\t\t\t".'background-position: 0% 0%;'."\n"
			."\t\t\t".'color: #333;'."\n"
			."\t\t".'}'."\n"
			."\t".'</style>'."\n";
	}

	// set icons for this site
	if(!$context['site_icon']) {
		echo "\t".'<link rel="shortcut icon" href="'.$context['url_to_root'].'skins/skeleton/squares.ico" type="image/x-icon" />'."\n";
		echo "\t".'<link rel="icon" href="'.$context['url_to_root'].'skins/skeleton/squares.ico" type="image/x-icon" />'."\n";
	}

	// other head directives
	echo $context['page_header'];

	// end of the header
	echo '</head>'."\n";

	// start the body
	Page::body();

	// the header panel comes before everything
	echo '<div id="header_panel">'."\n";

	// the site name -- can be replaced, through CSS, by an image -- access key 1
	if($context['site_name'] && is_callable(array('i18n', 's')))
		echo '<p id="header_title"><a href="'.$context['url_to_root'].'" title="'.encode_field(i18n::s('Front page')).'" accesskey="1">'.$context['site_name'].'</a></p>'."\n";

	// site slogan
	if(isset($context['site_slogan']))
		echo '<p id="header_slogan">'.$context['site_slogan']."</p>\n";

	// horizontal tabs -- add a tab for the front page, and reverse order
	Page::tabs(TRUE, TRUE);

	// end of the header panel
	echo '</div>'."\n";

	// several columns in the middle
	echo '<div id="wrapper">'."\n";

	// add a drop shadow
	echo '<div id="wrapper_prefix"></div>'."\n";

	// the main panel
	echo '<div id="main_panel">'."\n";

	// display bread crumbs if not at the front page; if not defined, only the 'Home' link will be displayed
	if($context['skin_variant'] != 'home')
		Page::bread_crumbs(0);

	// display main content
	Page::content();

// prefix ends here
}

// build the suffix only once
if(!isset($context['embedded']) || ($context['embedded'] == 'suffix')) {

	// end of the main panel
	echo '</div>'."\n";

	// the side panel
	echo '<div id="side_panel">'."\n";

	// display side content
	Page::side();

	// link to yacs if we are at the front page
	if(($context['skin_variant'] == 'home') && is_callable(array('i18n', 's')))
		echo Skin::build_box(NULL, '<p>'.sprintf(i18n::s('Powered by %s'), Skin::build_link(i18n::s('http://www.yacs.fr/'), 'Yacs', 'external')).'</p>', 'extra');

	// end of the side panel
	echo '</div>'."\n";

	// add a drop shadow
	echo '<div id="wrapper_suffix"></div>'."\n";

	// end of the wrapper
	echo '</div>'."\n";

	// the footer panel comes after everything else
	echo '<div id="footer_panel">';

	// display standard footer
	Page::footer();

	// yes, this has been validated
	echo '<a href="http://validator.w3.org/check?uri=referer"><img src="'.$context['url_to_root'].'skins/skeleton/images/buttons_xhtmlw3c.gif" alt="XHTML 1.0!" height="15" width="80" /></a>'."\n";

	// end of the footer panel
	echo '</div>'."\n";

	// insert the dynamic footer, if any, including inline scripts
	echo $context['page_footer'];

	// nice titles
	echo "\t".'<script type="text/javascript" src="'.$context['url_to_root'].'skins/skeleton/nicetitle.js"></script>'."\n";

	// end of page
	echo '</body>'."\n"
		.'</html>';

// suffix ends here
}

?>