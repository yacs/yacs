<?php
/**
 * Display pages on mobile devices
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// this one has to be included
if(count(get_included_files()) < 3) {
	echo 'Script must be included';
	return;
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<?php // use styles only to mask content ?>
	<style type="text/css">
		.small, .tiny, .toc_box { font-size: smaller; }
	</style>
<?php // other components of the head
echo $context['page_header'];
?>
	<meta name="HandheldFriendly" content="True">
</head>
<body>
<?php
// display the prefix, if any
if($context['prefix'])
	echo $context['prefix'];

// display the title
if($context['page_title'])
	echo Skin::build_block($context['page_title'], 'page_title');
else
	echo Skin::build_block($context['site_name'], 'page_title');

// display error messages, if any
echo Skin::build_error_block();

// display the page image, if any
if($context['page_image'])
	echo ICON_PREFIX.'<img src="'.$context['page_image'].'" class="icon" alt=""'.EOT.ICON_SUFFIX;

// display the dynamic content, if any
if(is_callable('send_body'))
	send_body();

// suppress menu information, if any
$context['text'] = preg_replace(array('/<p class="(details|no_print|page_menu)">.+?<\/p>/is',
	'/<div class="menu">.+?<\/div>/is'), '', $context['text']);

// render and display the content, if any
echo $context['text'];

// debug output, if any
if($context['debug'])
	echo '<p>'.$context['debug']."</p>\n";

// display the suffix, if any
if($context['suffix'])
	echo $context['suffix'];

?>
</body>
</html>