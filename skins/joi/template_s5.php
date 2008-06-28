<?php
/**
 * the HTML template for slideshow based on S5
 *
 * The S5 theme to use is specified in constant S5_THEME that is defined
 * in skin.php. Default value is set in skin_skeleton.php to 'default'.
 *
 * This template uses specific variables as follows:
 * - $context['footer'] - content of slide footer
 * - $context['header'] - content of slide header
 * - $context['text'] - the list of slides, which is the main content of the page
 *
 * Slide footer is made of up to two successive headers, as per following pattern:
 * [snippet]
 * <h1>[location/date of presentation]</h1>
 * <h2>[slide show title here]</h2>
 * [/snippet]
 *
 * Main content of the page is a list of slides, as per following pattern:
 * [snippet]
 * <div class="slide">
 * <h1>[slide show title here]</h1>
 * <h3>[name of presenter]</h3>
 * <h4>[affiliation of presenter]</h4>
 * </div>
 *
 * <div class="slide">
 * <h1>[slide title]</h1>
 * <ul>
 * <li>[point one]</li>
 * <li>[point two]</li>
 * <li>[point three]</li>
 * <li>[point four]</li>
 * <li>[point five]</li>
 * </ul>
 * <div class="handout">
 * [any material that should appear in print but not on the slide]
 * </div>
 * </div>
 *
 * ...
 * [/snippet]
 *
 *
 * @see sections/slideshow.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
defined('YACS') or exit('Script must be included');

// sanity check -- scripts/validate.php
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// xml prefix
echo '<'.'?xml version="1.0" encoding="'.$context['charset'].'"?'.'>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<?php

// give the charset to the w3c validator...
echo "\t".'<meta http-equiv="Content-Type" content="text/html; charset='.$context['charset'].'" />'."\n";

// load S5
echo "\t".'<!-- configuration parameters -->'."\n";
echo "\t".'<meta name="defaultView" content="slideshow" />'."\n";
echo "\t".'<meta name="controlVis" content="hidden" />'."\n";
echo "\t".'<!-- style sheet links -->'."\n";
echo "\t".'<link rel="stylesheet" href="'.$context['url_to_root'].$context['skin'].'/s5/'.S5_THEME.'/slides.css" type="text/css" media="projection" id="slideProj" />'."\n";
echo "\t".'<link rel="stylesheet" href="'.$context['url_to_root'].$context['skin'].'/s5/default/outline.css" type="text/css" media="screen" id="outlineStyle" />'."\n";
echo "\t".'<link rel="stylesheet" href="'.$context['url_to_root'].$context['skin'].'/s5/default/print.css" type="text/css" media="print" id="slidePrint" />'."\n";
echo "\t".'<link rel="stylesheet" href="'.$context['url_to_root'].$context['skin'].'/s5/default/opera.css" type="text/css" media="projection" id="operaFix" />'."\n";
echo "\t".'<!-- S5 JS -->'."\n";
echo "\t".'<script src="'.$context['url_to_root'].$context['skin'].'/s5/default/slides.js" type="text/javascript"></script>'."\n";

// other head directives
echo $context['page_header'];
?>
</head>
<body id="slideshow">
<?php // layout elements
echo '<div class="layout">'."\n";
echo '<div id="controls"><!-- DO NOT EDIT --></div>'."\n";
echo '<div id="currentSlide"><!-- DO NOT EDIT --></div>'."\n";
if(!isset($context['header']))
	$context['header'] = '';
echo '<div id="header">'.$context['header'].'&nbsp;</div>'."\n";
if(!isset($context['footer']))
	$context['footer'] = '';
echo '<div id="footer">'.$context['footer'].'&nbsp;</div>'."\n";
echo '</div>'."\n";

// slides ?>
<div class="presentation">

<?php // render and display the content, if any
echo $context['text'];
$context['text'] = '';

// end of slides ?>
</div>

<?php // one slide to mask everything ?>
<div id="black" style="position:absolute; top: 0; left:0; z-index: 1000; height: 100%; width: 100%; background-color: #000; display: none;"></div>


</body>
</html>