<?php
/**
 *  A theme with out of the box implementation of HTML5 and CSS3,
 *  and responsive design technique
 *
 *  HTML5 Theme with template layout positionning for major yacs'blocks
 *
 *  Build on HTML5boilerplate best pratices
 *  	"A rock-solid default for HTML5 awesome."
 *  http://html5boilerplate.com/
 *
 *  and Knacss by Raphael Goetter
 *  	"A minimalist, responsive and extensible stylesheet
 *  	to kick start your HTML / CSS projects."
 *  http://knacss.com/
 *
 *  /!\ Yacs Lasares RC4 or upper required /!\
 *
 * Positionning blocs with knacss :
 * http://knacss.com/demos/tutoriel.html#positioning
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
defined('YACS') or exit('Script must be included');

// add language information, if known
if(isset($context['page_language']))
	$language = $context['page_language'];
else
	$language = $context['language'];

// start the page
// element html class will be modified by modernizr.js
// paulirish.com/2008/conditional-stylesheets-vs-css-hacks-answer-neither
echo '<!doctype html>'."\n"
	.'<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="'.$language.'"> <![endif]-->'."\n"
	.'<!--[if IE 7]> <html class="no-js lt-ie9 lt-ie8" lang="'.$language.'"> <![endif]-->'."\n"
	.'<!--[if IE 8]> <html class="no-js lt-ie9" lang="'.$language.'"> <![endif]-->'."\n"
	// Consider adding a manifest.appcache: h5bp.com/d/Offline
	.'<!--[if gt IE 8]><!--> '.'<html class="no-js" lang="'.$language.'">'.' <!--<![endif]-->'."\n"
	.'<head>'."\n";

// give the charset to the w3c validator...
echo "\t".'<meta charset="'.$context['charset'].'">'."\n";

//Use the .htaccess and remove these lines to avoid edge case issues.
// More info: h5bp.com/i/378
	// force IE6 to use chrome engine
	echo "\t".'<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">'."\n";

// Mobile viewport optimized: @see h5bp.com/viewport
echo "\t".'<meta name="viewport" content="width=device-width">'."\n";

// Place favicon.ico and apple-touch-icon.png in the root directory:
// @see mathiasbynens.be/notes/touch-icons

// we have one style sheet for everything, implied media=all
echo "\t".'<link rel="stylesheet" href="'.$context['url_to_root'].'skins/starterfive/starterfive.css" >'."\n";

// example of CDN google font
echo "\t".'<link href="http://fonts.googleapis.com/css?family=Open+Sans:400,400italic,600" rel="stylesheet" >'."\n";

// set icons for this site
if(!$context['site_icon']) {
	echo "\t".'<link rel="shortcut icon" href="'.$context['url_to_root'].'skins/starterfive/favicon.ico" type="image/x-icon" />'."\n";
	echo "\t".'<link rel="icon" href="'.$context['url_to_root'].'skins/starterfive/favicon.ico" type="image/x-icon" />'."\n";
}

// other head directives
Page::meta();

// More ideas for your <head> here: h5bp.com/d/head-Tips

// All JavaScript at the bottom, except for Modernizr / Respond.
// Modernizr enables HTML5 elements & feature detects;
// Respond is a polyfill for min/max-width CSS3 Media Queries
// For optimal performance, use a custom Modernizr build: www.modernizr.com/download/
echo "\t".'<script src="'.$context['url_to_root'].'skins/starterfive/js/modernizr-2.6.2.min.js"></script>'."\n";

// end of the header
echo '</head>'."\n";

// start the body
Page::body();

// Prompt IE 6 users to install Chrome Frame. Remove this if you want to support IE 6.
// @see chromium.org/developers/how-tos/chrome-frame-getting-started
echo '<!--[if lt IE 7 ]>'."\n";
echo '<p class="chromeframe">You are using an outdated browser. <a href="http://browsehappy.com/">Upgrade your browser today</a> or <a href="http://www.google.com/chromeframe/?redirect=true">install Google Chrome Frame</a> to better experience this site.</p>'."\n";
echo '<![endif]-->'."\n";

// to support decorations
echo '<div id="upperbody">'."\n";

// wrap content
echo '<div id="container" class="mw960p center">'."\n";

// the header panel comes before everything
echo '<header id="header_panel" class="line mod">'."\n";

// the site name -- can be replaced, through CSS, by an image -- access key 1
if($context['site_name'])
	echo '<p id="header_title" class="left"><a href="'.$context['url_to_root'].'" title="'.encode_field(i18n::s('Front page')).'" accesskey="1"><span>'.$context['site_name'].'</span></a></p>'."\n";

// site slogan -- can be replaced, through CSS, by an image
if(isset($context['site_slogan']))
	echo '<p id="header_slogan" class="item"><span>'.$context['site_slogan']."</span></p>\n";

// horizontal tabs with Home, and second level drop-down menu displayed as smartlist
Page::tabs(TRUE, FALSE, NULL, NULL, 'smartlist');

// end of the header panel
echo '</header>'."\n";

// display bread crumbs if not at the front page; if not defined, only the 'Home' link will be displayed
if($context['skin_variant'] != 'home')
	Page::bread_crumbs(2);

// wrap the 3 colums
echo '<div class="row">'."\n";

// navigation information
echo '<nav id="side_panel" class="col w20 t33 s100 mod item">'."\n";

// display side content
Page::side();

// link to yacs if we are at the front page
if(($context['skin_variant'] == 'home') && is_callable(array('i18n', 's')))
	echo Skin::build_box(NULL, '<p>'.sprintf(i18n::s('Powered by %s'), Skin::build_link(i18n::s('http://www.yacs.fr/'), 'Yacs', 'external')).'</p>', 'extra');

// end of the side panel
echo '</nav>'."\n";

// main content
echo '<div id="main_panel" role="main" class="col w60 t44 s100 mod item">'."\n";

//  anchor to main content accesskey = 1
echo '<a name="main_panel"></a>'."\n";

// display main content
Page::content();

// ensure we have some content
echo '<br style="clear: left;" />&nbsp;'."\n";

// end of main div
echo '</div>'."\n";

// display complementary information, if any
echo '<aside id="extra_panel" class="col w20 t33 s100 mod item">'."\n";
Page::extra_panel(NULL, FALSE);
echo '</aside>'."\n";

// end 3 colums wrapper
echo '</div>'."\n";

// end of container
echo  '</div>'."\n";

// end of upperbody
echo  '</div>'."\n";

// page footer
echo  '<footer id="footer_panel" class="mw960p line mod center">'."\n";

// last <p>
Page::footer();

echo  '</footer>'."\n";

// call jquery
// echo '<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>'."\n";
// echo '<script>window.jQuery || document.write(\'<script src="'.$context['url_to_root'].'skins/boilerplate/js/vendor/jquery-1.8.0.min.js"><\/script>\')</script>'."\n";

// scripts concatenated and minified via ant build script
// <script src="js/plugins.js"></script>
// <script src="js/script.js"></script>
// end scripts

// insert the dynamic footer, if any, including inline scripts
echo $context['page_footer'];

// template layout activation, if needed (http://code.google.com/p/css-template-layout)
// echo '<script>$(function() {$.setTemplateLayout("'.$context['url_to_root'].'skins/starterfive/starterfive.css", "js");});</script>'."\n";

// google analytics tracker, change UA-XXXXX-X to be your site's ID.
/*echo '<script>'."\n"
		."\t"."var _gaq=[['_setAccount','UA-XXXXX-X'],['_trackPageview']];"."\n"
		."\t".'(function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];'."\n"
		."\t"."g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js'"."\n"
		."\t".'s.parentNode.insertBefore(g,s)}(document,'script'))'."\n"
	.'</script>'."\n";*/

// CSS3Pie for decoration with old IE browser
echo '<!--[if lt IE 10]>'."\n";
echo '<script type="text/javascript" src="'.$context['url_to_root'].'skins/starterfive/js/PIE.js"></script>'."\n";
echo '<script type="text/javascript" src="'.$context['url_to_root'].'skins/starterfive/js/pie_enhance_ie.js"></script>'."\n";
echo '<![endif]-->'."\n";


echo '</body>'."\n";
echo '</html>';
?>
