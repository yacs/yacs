<?php
/**
 * the template derived from boxesandarrows.com
 *
 * Implements a nice tabbed interface built on actual sections, and on a visual 'you are here' effect.
 *
 * This template implements following access keys at all pages:
 * - 1 to go to the front page of the site
 * - 2 to skip the header and jump to the main area of the page
 * - 9 to go to the control panel
 * - 0 to go to the help page
 *
 * This has been validated as XHTML 1.0 Transitional
 *
 * @link http://keystonewebsites.com/articles/mime_type.php Serving up XHTML with the correct MIME type
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @tester Agnes
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
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
	echo "\t".'<link rel="stylesheet" href="'.$context['url_to_root'].'skins/boxesandarrows/boxesandarrows.css" type="text/css" media="all" />'."\n";

	// implement the 'you are here' feature
	if($focus = Page::top_focus()) {
		echo "\t".'<style type="text/css" media="screen">'."\n"
			."\t\t".'#tabs li#'.$focus.' a {'."\n"
			."\t\t\t".'background-position: 0% -44px;'."\n"
			."\t\t\t".'border-bottom: 1px solid #fff;'."\n"
			."\t\t".'}'."\n"
			."\t\t".'#tabs li#'.$focus.' a span {'."\n"
			."\t\t\t".'color: #039;'."\n"
			."\t\t\t".'background-position: 100% -44px;'."\n"
			."\t\t".'}'."\n"
			."\t".'</style>'."\n";
	}

	// set icons for this site
	if(!$context['site_icon']) {
		echo "\t".'<link rel="shortcut icon" href="'.$context['url_to_root'].'skins/boxesandarrows/boxesandarrows.ico" type="image/x-icon" />'."\n";
		echo "\t".'<link rel="icon" href="'.$context['url_to_root'].'skins/boxesandarrows/boxesandarrows.ico" type="image/x-icon" />'."\n";
	}

	// other head directives
	echo $context['page_header'];

	// end of the header
	echo '</head>'."\n";

	// start the body
	Page::body();

	// the default header panel
	Page::header_panel();

	// the main panel
	echo '<table id="wrapper"><tr><td id="main_panel">'."\n";

	// combine the menu bar and the date in one line
	echo '<div class="dotted metadata" >'."\n";

	// display the date on the right
	echo '<span style="float: right;">'.date('F d, Y').'</span>'."\n";

	// display the menu bar, if any
	if(@count($context['page_menu']) > 0)
		echo Skin::build_list($context['page_menu'], 'page_menu');
	else
		echo '&nbsp; '; // to always have dotted line below floating date

	echo '</div>'."\n";

	// display the last item of the path string
	if(is_array($context['path_bar'])) {

		// browse to the end --sorry, no function to do that in PHP?
		$link = '';
		$label = '';
		foreach($context['path_bar'] as $link => $label)
			;

		// link to the upper container
		if($label && is_callable(array('i18n', 's')))
			echo '<p class="details" style="text-align: right">'.sprintf(i18n::s('More in %s'), Skin::build_link($link, $label)).'</p>';
	}

	// display the main page content, but not the menu bar
	Page::content(FALSE);

// prefix ends here
}

// build the suffix only once
if(!isset($context['embedded']) || ($context['embedded'] == 'suffix')) {

	// end of the main panel
	echo '</td>'."\n";

	// the side panel is displayed on all pages; it includes the menu and other navigation boxes
	echo '<td id="side_panel">'."\n";

	// display side content, including extra data
	Page::side(TRUE);

	// end of the navigation panel
	echo '</td></tr></table>'."\n";

	// the footer panel
	echo '<div id="footer_panel">'."\n";

	// surfer name and execution time, if known
	if(is_callable(array('Surfer', 'get_name')) && Surfer::get_name() && is_callable(array('i18n', 's'))) {
		$execution_time = round(get_micro_time() - $context['start_time'], 2);
		echo sprintf(i18n::s('Page prepared in %.2f seconds for %s'), $execution_time, ucwords(Surfer::get_name())).' ';
	}

	// site copyright
	if(isset($context['site_copyright']))
		echo '<br />Copyright &copy; '.$context['site_copyright']."\n";

	// a command to authenticate
	if(is_callable(array('Surfer', 'is_logged')) && !Surfer::is_logged() && is_callable(array('i18n', 's')))
		echo ' - '.Skin::build_link('users/login.php', i18n::s('login'), 'basic').' ';

	// about this site
	if(is_callable(array('i18n', 's')) && is_callable(array('Articles', 'get_url')))
		echo ' - '.Skin::build_link(Articles::get_url('about'), i18n::s('about this site'), 'basic').' ';

	// privacy statement
	if(is_callable(array('i18n', 's'))) {

		// take URL rewriting into account
		if($context['with_friendly_urls'] == 'R')
			$link = 'privacy';
		else
			$link = 'privacy.php';

		echo ' - '.Skin::build_link($link, i18n::s('privacy statement'), 'basic').' ';
	}

	// a reference to YACS
	if(is_callable(array('i18n', 's')))
		echo BR.sprintf(i18n::s('Powered by %s'), Skin::build_link(i18n::s('http://www.yetanothercommunitysystem.com/'), i18n::s('YACS'), 'external'));

	// end of the footer panel
	echo '</div>'."\n";

	// insert the footer
	echo $context['page_footer'];

	// nice titles
	echo "\t".'<script type="text/javascript" src="'.$context['url_to_root'].'skins/boxesandarrows/nicetitle.js"></script>'."\n";

	// end of page
	echo '</body>'."\n"
		.'</html>';

// suffix ends here
}

?>