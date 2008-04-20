<?php
/**
 * the HTML template to print pages
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
defined('YACS') or exit('Script must be included');

?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<?php // use styles only to mask content ?>
	<style type="text/css">
		.details, .menu, .no_print, .page_menu, .path, #navigation_panel, #extra_panel, #header_panel, #footer_panel { display: none; }
		.small, .tiny, .toc_box { font-size: smaller; }
	</style>
<?php // other components of the head
echo $context['page_header'];
?>
</head>
<body onLoad='javascript:print();'>
<?php
// display the prefix, if any
if($context['prefix'])
	echo $context['prefix'];

// display the title
if($context['page_title'])
	echo Skin::build_block($context['page_title'], 'page_title');

// display error messages, if any
echo Skin::build_error_block();

// display the page image, if any
if($context['page_image'])
	echo ICON_PREFIX.'<img src="'.$context['page_image'].'" class="icon" alt=""'.EOT.ICON_SUFFIX;

// display the dynamic content, if any
if(is_callable('send_body'))
	send_body();

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