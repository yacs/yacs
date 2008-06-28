<?php
/**
 * the HTML template to print pages
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
defined('YACS') or exit('Script must be included');

?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<?php // minimum style sheet ?>
	<style type="text/css">
		.details, .menu, .no_print, .page_menu, .path, #navigation_panel, #extra_panel, #header_panel, #footer_panel { display: none; }

		.small, .tiny, .toc_box { font-size: smaller; }

		body, body div, body p, body th, body td, body li, body dd {
			font-family: "Georgia", "Times New Roman", Times, serif;
			font-size: xx-small;		/* false value for WinIE4/5 */
			voice-family: "\"}\"";	/* trick WinIE4/5 into thinking the rule is over */
			voice-family: inherit;	/* recover from trick */
			font-size: x-small; 	/* intended value for better browsers */
		}

		tr { margin: 0.1pt; }

		td { margin: 0; }

		th { text-align: left; }

		.even td, .odd td {
			border-top: 1px solid #333;
		}

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

// display the page image, if any
if($context['page_image'])
	echo ICON_PREFIX.'<img src="'.$context['page_image'].'" class="icon" alt=""'.EOT.ICON_SUFFIX;

// display the title
if($context['page_title'])
	echo Skin::build_block($context['page_title'], 'page_title');

// display error messages, if any
echo Skin::build_error_block();

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