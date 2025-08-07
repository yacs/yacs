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

// Determine user status
$is_logged_in = class_exists('Surfer') && Surfer::is_logged();
$is_member_or_associate = class_exists('Surfer') && (Surfer::is_member() || Surfer::is_associate());

// Add a class to the body for styling based on login status
$body_class = '';
if (!$is_logged_in) {
    $body_class = 'visitor-mode';
} else {
    $body_class = 'member-mode';
}

// start the body
echo '<body class="'.$body_class.'">'.PHP_EOL;

// header panel, give padding with knacss class helper
echo '<header id="header_panel" class="pas">'.PHP_EOL;

echo '<div class="header-left">'.PHP_EOL;
// Logo
if($context['site_name'])
	echo '<p id="header_title"><a href="'.$context['url_to_root'].'" title="'.encode_field(i18n::s('Front page')).'" accesskey="1"><img src="'.$context['url_to_root'].$context['skin'].'/images/logo-elcondor.jpg" alt="'.encode_field($context['site_name']).'" id="site_logo" /></a></p>'.PHP_EOL;
echo '</div>'.PHP_EOL;

echo '<div class="header-right">'.PHP_EOL;
// Site slogan
if(isset($context['site_slogan']))
	echo '<p id="header_slogan">'.$context['site_slogan']."</p>\n";

// Custom menu items from sections
Skin::tabs();

echo '</div>'.PHP_EOL;

// display bread crumbs if not at the front page; if not defined, only the 'Home' link will be displayed
if($context['skin_variant'] != 'home')
	Page::bread_crumbs(2);

echo '</header>'."\n";

// 3 columns container
echo '<main id="wrapper" class="grid">'.PHP_EOL;

// Left column (navigation) - visible only for members/associates
if ($is_member_or_associate) {
    echo '<nav id="navigation_panel" class="grid-col pas w15">'.PHP_EOL;
        // Move extra panel content to navigation panel
        Page::extra_panel(NULL,FALSE);
        // display navigation components (@see pages configuration in control panel)
        Page::side();
    echo '</nav>'.PHP_EOL;
}

// Sidebar toggle button for members/associates
if ($is_member_or_associate) {
    echo '<button id="sidebar_toggle" onclick="toggleSidebar()" title="Toggle Sidebar"><i class="fa fa-bars"></i></button>'.PHP_EOL;
}

// Main content column
$content_class = 'grid-col pam';
if (!$is_member_or_associate) {
    $content_class .= ' w100'; // Full width for visitors
} else {
    $content_class .= ' w85'; // Adjusted width for members/associates (100% - 15% for left sidebar)
}
echo '<section id="content_panel" role="main" class="'.$content_class.'">'.PHP_EOL;
    // display main content (@see pages configuration in control panel)
    Page::content();
echo '</section>'.PHP_EOL;

echo '</main>'.PHP_EOL;

// footer panel
echo '<footer id="footer_panel" class="pas">'."\n";

    Page::footer();

echo '</footer>'."\n";


// insert the dynamic footer, if any, including inline scripts
echo $context['page_footer'];

echo '</body>'."\n";
echo '</html>';