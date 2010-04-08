<?php
/**
 * Display pages on mobile devices
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
defined('YACS') or exit('Script must be included');

// actual transmission except on a HEAD request
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

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

// display page title
if($context['page_title'])
	echo '<title>'.strip_tags($context['page_title']).'</title>';
else
	echo '<title>'.strip_tags($context['site_name']).'</title>';

?>
<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;"/>
	<link rel="apple-touch-icon" href="<?php echo $context['url_to_root']; ?>skins/_mobile/iui/iui-logo-touch-icon.png" />
	<meta name="apple-touch-fullscreen" content="YES" />
<!--	<style type="text/css" media="screen">@import "<?php echo $context['url_to_root']; ?>skins/_reference/yacs.css";</style> -->
	<style type="text/css" media="screen">@import "<?php echo $context['url_to_root']; ?>skins/_mobile/iui/iuix.css";</style>
	<script type="application/x-javascript" src="<?php echo $context['url_to_root']; ?>skins/_mobile/iui/iuix.js"></script>
<?php // other components of the head
echo $context['page_header'];
?>
	<meta name="HandheldFriendly" content="True">

</head>

<body>
    <div class="toolbar">
        <h1 id="pageTitle"></h1>
        <a id="backButton" class="button" href="#"></a>
<!--        <a class="button" href="#searchForm">Search</a> -->
    </div>
<?php

// display the prefix, if any
if($context['prefix'])
	echo $context['prefix'];

// display error messages, if any
echo Skin::build_error_block();

// display the dynamic content, if any
if(is_callable('send_body'))
	send_body();

// suppress menu information, if any
//$context['text'] = preg_replace(array('/<p class="(details|no_print|page_menu)">.+?<\/p>/is',
//	'/<div class="menu">.+?<\/div>/is'), '', $context['text']);

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