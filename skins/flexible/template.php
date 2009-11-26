<?php
/**
 * a 3-column layout
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// this one has to be included
if(count(get_included_files()) < 3) {
	echo 'Script must be included';
	return;
}

// load skins parameters
include_once $context['path_to_root'].'skins/flexible/parameters.include.php';

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
	echo "\t".'<meta http-equiv="Content-Type" content="text/html; charset='.$context['charset'].'" />'."\n";

	// set icons for this site
	if(!$context['site_icon']) {
		echo "\t".'<link rel="shortcut icon" href="'.$context['url_to_root'].'skins/flexible/yacs.ico" type="image/x-icon" />'."\n";
		echo "\t".'<link rel="icon" href="'.$context['url_to_root'].'skins/flexible/yacs.ico" type="image/x-icon" />'."\n";
	}

	// we have one style sheet for everything -- media="all" means it is not loaded by Netscape Navigator 4
	echo "\t".'<link rel="stylesheet" href="'.$context['url_to_root'].'skins/flexible/flexible.css" type="text/css" media="all" />'."\n";

	// implement the 'you are here' feature
	if($focus = Page::top_focus()) {
		echo "\t".'<style type="text/css" media="screen">'."\n"
			."\n"
			."\t\t".'div.tabs ul li a.'.substr($focus,4).'  {'."\n"
			."\t\t\t".'background-position: 0% -42px; '."\n"
			."\t\t".'}'."\n"
			."\n"
			."\t\t".'div.tabs ul li a.'.substr($focus,4).' span  {'."\n"
			."\t\t\t".'color: '.$context['flexible_tabs_h_color'].'; '."\n"
			."\t\t\t".'background-position: 100% -42px; '."\n"
			."\t\t".'}'."\n"
			."\n"
			."\t".'</style>'."\n";
	}
	// other head directives
	echo $context['page_header'];

	// end of the header
	echo '</head>'."\n";

	// the body tag, plus some hidden tags
	Page::body();

	// div#page defines page width - fixes or liquid layout
	echo '<div id="page">'."\n";

	// the header panel comes before everything
	echo '<div id="header_panel">'."\n";

	// the site name, or site logo -- access key 1
	$name = '';
	if(isset($context['flexible_header_t_logo']) && $context['flexible_header_t_logo'])
		$name = '<img src="'.$context['flexible_header_t_logo'].'" />';
	elseif($context['site_name'])
		$name = $context['site_name'];
	$title = '';
	if(is_callable(array('i18n', 's')))
		$title = i18n::s('Return to front page');
	if($name)
		echo "\t".'<p id="header_title"><a href="'.$context['url_to_root'].'" title="'.encode_field($title).'" accesskey="1">'.$name.'</a></p>'."\n";

	// site slogan
	if($context['site_slogan'])
		echo "\t".'<p id="header_slogan">'.$context['site_slogan'].'</p>';

	// horizontal tabs -- add a tab for the front page, and reverse order
	Page::tabs(FALSE, FALSE);

	// end of the header panel
	echo '</div>'."\n";

	// concatenate side columns
	if(isset($context['flexible_columns']) && (($context['flexible_columns'] == '2_1_3') || ($context['flexible_columns'] == '2_3_1')))
		$class = 'blogstyle';
	else
		$class = 'holygrail';

	// do the magic
	echo '<div class="colmask '.$class.'">'
		.'<div class="colmid">'
			.'<div class="colleft">'
				.'<div class="col1wrap">'
					.'<div id="main_panel">';

	// display bread crumbs if not at the front page; if not defined, only the 'Home' link will be displayed
	Page::bread_crumbs(1);

	// display main content
	Page::content();

// prefix ends here
}

// build the suffix only once
if(!isset($context['embedded']) || ($context['embedded'] == 'suffix')) {

	// move to second column
	echo '</div>'	// main_panel
		.'</div>';	// col1wrap

	// invert side and extra panels
	if(isset($context['flexible_columns']) && (($context['flexible_columns'] == '3_2_1') || ($context['flexible_columns'] == '2_3_1'))) {

		// display complementary information, in div#side_panel
		echo '<div id="side_panel">';
		Page::extra_panel(NULL, FALSE);
		echo '</div>';

		// display side content, in div#extra_panel
		echo '<div id="extra_panel">';
		Page::side();
		echo '</div>';

	} else {

		// display side content, in div#side_panel
		echo '<div id="side_panel">';
		Page::side();
		echo '</div>';

		// display complementary information, in div#extra_panel
		echo '<div id="extra_panel">';
		Page::extra_panel(NULL, FALSE);
		echo '</div>';

	}
	// end layout
	echo '<span style="font-size:1px; line-height: 0px;">&nbsp;</span></div>'	// colleft
		.'</div>'	// colmid
		.'</div>';	// colmask

	// the footer panel comes after everything else
	echo '<div id="footer"><div id="footer_panel">';

	// display standard footer
	Page::footer();

	// end of the footer panel
	echo '</div></div>';

	// end of the page wrapper
	echo '</div>';

	// insert the dynamic footer, if any, including inline scripts
	echo $context['page_footer'];

	// end of page
	echo '</body>'."\n"
		.'</html>';

// suffix ends here
}

?>