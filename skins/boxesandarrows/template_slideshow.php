<?php
/**
 * the HTML template for slideshows
 *
 * @see collections/play_slideshow.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
defined('YACS') or exit('Script must be included');

// xml prefix
echo '<'.'?xml version="1.0" encoding="'.$context['charset'].'"?'.'>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<?php

// give the charset to the w3c validator...
echo "\t".'<meta http-equiv="Content-Type" content="text/html; charset='.$context['charset'].'" />'."\n";

// we have one style sheet for everything -- media="all" means it is not loaded by Netscape Navigator 4
echo "\t".'<link rel="stylesheet" href="'.$context['url_to_root'].'skins/boxesandarrows/boxesandarrows.css" type="text/css" media="all" />'."\n";

// styles specific to slide shows
echo "\t".'<style type="text/css" media="all">'."\n"
	."\t\t".'body#slideshow a img {'."\n"
	."\t\t\t".'border: none;'."\n"
	."\t\t".'}'."\n"
	."\n"
	."\t\t".'body#slideshow div.slide {'."\n"
	."\t\t\t".'text-align: center;'."\n"
	."\t\t\t".'margin: 0;'."\n"
	."\t\t\t".'padding: 0;'."\n"
	."\t\t".'}'."\n"
	."\n"
	."\t\t".'body#slideshow p#crumbs {'."\n"
	."\t\t\t".'text-align: center;'."\n"
	."\t\t\t".'margin: 0;'."\n"
	."\t\t\t".'padding: 0;'."\n"
	."\t\t".'}'."\n"
	."\t".'</style>'."\n";

// other head directives
echo $context['page_header'];
?>
</head>
<body id="slideshow">
<?php // center in the page ?>
<div style="text-align: center;">

<?php

// display bread crumbs if not at the front page; if not defined, only the 'Home' link will be displayed
if($context['skin_variant'] != 'home') {

	// insert the home link
	$context['path_bar'] = array_merge(array('index.php' => i18n::s('Home')), $context['path_bar']);

	// at the head of the main panel
	echo Skin::build_list($context['path_bar'], 'crumbs')."\n";
}

// display the prefix, if any
if($context['prefix'])
	echo $context['prefix']."\n";

// display the title
if($context['page_title'])
	echo Skin::build_block($context['page_title'], 'page_title');

// display the menu bar
if(@count($context['page_menu']) > 0)
	echo Skin::build_list($context['page_menu'], 'page_menu');

// display error messages, if any
echo Skin::build_error_block();

// display the page image, if any
if($context['page_image'])
	echo ICON_PREFIX.'<img src="'.$context['page_image'].'" class="icon" alt=""'.EOT.ICON_SUFFIX;

// render and display the content, if any
echo $context['text'];
$context['text'] = '';

// display the dynamic content, if any
if(is_callable('send_body'))
	send_body();

// maybe some additional text has been created in send_body()
echo $context['text'];

// display the suffix, if any
if($context['suffix'])
	echo '<p>'.$context['suffix']."</p>\n";

// debug output, if any
if($context['debug'])
	echo '<p>'.$context['debug']."</p>\n";


// end of the wrapper ?>
</div>
</body>
</html>