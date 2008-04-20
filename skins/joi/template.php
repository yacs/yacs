<?php
/**
 * A nice centered 2-column layout inspired by Joi Ito's web site
 *
 * @link http://joi.ito.com/
 *
 * The layout makes things natural if styles are disactivated. It is made of following components:
 * <ul>
 * <li>div#wrapper - limit the horizontal size of everything, and center it in the page</li>
 *	<ul>
 *	<li>div#header_panel</li>
 *	 <ul>
 *	 <li>p#header_title</li>
 *	 <li>p#header_slogan</li>
 *	 </ul>
 *	<li>div#main_panel</li>
 *	 <ul>
 *	 <li>p#crumbs - path to this page</li>
 *	 <li>h1 - page title</li>
 *	 <li>p#page_menu - commands for this page</li>
 *	 <li>div.error - to report on errors, if any</li>
 *	 <li>img.icon - the main image for this page</li>
 *	 <li>(page main content)</li>
 *	 </ul>
 *	<li>div#side_panel</li>
 *	<li>div#footer_panel</li>
 *	</ul>
 * </ul>
 *
 * This template implements following access keys at all pages:
 * - hit 1 to jump to the front page of the site
 * - hit 2 to skip the header and jump to the main area of the page
 * - 9 to go to the control panel
 * - 0 to go to the help page
 *
 * Also, the main [code]body[/code] has an id, which is the skin variant, and a class, which is "extra" when $context['extra'] is not empty.
 *
 * These can be combined in the style sheet to match particular situations. Some examples:
 * - body#home -- at the front page
 * - body#sections -- section page, or section index
 * - body#articles -- regular page, or index of articles
 * - body.extra -- when some extra content has been inserted in the page
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
defined('YACS') or exit('Script must be included');

// build the prefix only once
if(!isset($context['embedded']) || ($context['embedded'] == 'prefix')) {

	// add language information, if known
	if(isset($context['page_language']))
		$language = ' lang="'.$context['page_language'].'" xml:lang="'.$context['page_language'].'" ';
	else
		$language = '';

	// xml prefix
	echo '<'.'?xml version="1.0" encoding="'.$context['charset'].'"?'.'>'."\n"
		.'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n"
		.'<html '.$language.' xmlns="http://www.w3.org/1999/xhtml">'."\n"
		.'<head>'."\n";

	// give the charset to the w3c validator...
	echo "\t".'<meta http-equiv="Content-Type" content="'.$context['content_type'].'; charset='.$context['charset'].'" />'."\n";

	// we have one style sheet for everything -- media="all" means it is not loaded by Netscape Navigator 4
	echo "\t".'<link rel="stylesheet" href="'.$context['url_to_root'].'skins/joi/joi.css" type="text/css" media="all" />'."\n";

	// other head directives
	echo $context['page_header'];

	// end of the header
	echo '</head>'."\n";

	// start the body
	Page::body();

	// limit the horizontal size of everything, and center it in the page
	echo '<div id="wrapper">'."\n";

	// the header panel comes before everything
	echo '<div id="header_panel">'."\n";

	// the site name -- can be replaced, through CSS, by an image -- access key 1
	if($context['site_name'] && is_callable(array('i18n', 's')))
		echo "\t".'<p id="header_title"><a href="'.$context['url_to_root'].'" title="'.encode_field(i18n::s('Return to front page')).'" accesskey="1">'.$context['site_name'].'</a></p>'."\n";

	// site slogan
	if($context['site_slogan'])
		echo "\t".'<p id="header_slogan">'.$context['site_slogan'].'</p>'."\n";

	// end of the header panel
	echo '</div>'."\n";

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

	// separator for text readers
	echo '<hr class="hide" />'."\n";

	// the side panel
	echo '<div id="side_panel">'."\n";

	// display side content, including extra data
	Page::side(TRUE);

	// end of the side panel
	echo '</div>'."\n";

	// the footer panel comes after everything else
	echo '<div id="footer_panel">';

	// display standard footer
	Page::footer();

	// end of the footer panel
	echo '</div>'."\n";

	// end of the wrapper
	echo '</div>'."\n";

	// insert the dynamic footer, if any, including inline scripts
	echo $context['page_footer'];

	// nice titles
	echo "\t".'<script type="text/javascript" src="'.$context['url_to_root'].'skins/joi/nicetitle.js"></script>'."\n";

	// end of page
	echo '</body>'."\n"
		.'</html>';

// suffix ends here
}

?>