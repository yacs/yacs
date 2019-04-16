<?php

/** 
 * Mu template
 * 
 * Based on a template by Raphael Goetter (Alsacreations)
 * header, footer and 3 columns
 * Uses FlexBox model
 * 
 * Uses some knacss class helpers
 * @see http://knacss.com/KNACSS-cheatsheet.pdf
 * 
 * /!\ Yacs Netgrabber alpha 3 or upper required /!\
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
echo '<!doctype html>'."\n"
	.'<html lang="'.$language.'">'."\n"
	.'<head>'."\n";

// give the charset to the w3c validator...
echo "\t".'<meta charset="'.$context['charset'].'">'."\n";

// Mobile viewport optimized: @see h5bp.com/viewport
echo "\t".'<meta name="viewport" content="width=device-width, initial-scale=1">'."\n";

// set icons for this site
if(!$context['site_icon']) {
	echo "\t".'<link rel="icon" href="'.$context['url_to_root'].$context['skin'].'/favicon32.png" type="image/png" />'."\n";
}

// other head directives (include skin .css)
Page::meta();

// end of the header
echo '</head>'."\n";

// start the body
Page::body();

// header panel, give padding with knacss class helper
echo '<header id="header_panel" class="pas">'."\n";

// the site name -- can be replaced, through CSS, by an image -- access key 1
if($context['site_name'])
	echo '<p id="header_title"><a href="'.$context['url_to_root'].'" title="'.encode_field(i18n::s('Front page')).'" accesskey="1">'.$context['site_name'].'</a></p>'."\n";

// site slogan -- can be replaced, through CSS, by an image
if(isset($context['site_slogan']))
	echo '<p id="header_slogan">'.$context['site_slogan']."</p>\n";

// horizontal tabs, with home and root sections
Page::tabs();

// display bread crumbs if not at the front page; if not defined, only the 'Home' link will be displayed
if($context['skin_variant'] != 'home')
	Page::bread_crumbs(2);

echo '</header>'."\n";

// 3 columns container
echo '<main id="wrapper">'."\n";

// first column, main content
echo '<section id="content_panel" role="main" class="pam">'."\n";

    // display main content (@see pages configuration in control panel)
    Page::content();

echo '</section>'."\n";

// navigation column
echo '<nav id="navigation_panel" class="pas">'."\n";

    // display navigation components (@see pages configuration in control panel)
    Page::side();

echo '</nav>'."\n";

// extra column
echo '<aside id="extra_panel" class="pas">'."\n";

    // display extra components (@see pages configuration in control panel)
    Page::extra_panel(NULL,FALSE);

echo '</aside>'."\n";

echo '</main>'."\n";

// footer panel
echo '<footer id="footer_panel" class="pas">'."\n";

    Page::footer();

echo '</footer>'."\n";


// insert the dynamic footer, if any, including inline scripts
echo $context['page_footer'];

echo '</body>'."\n";
echo '</html>';