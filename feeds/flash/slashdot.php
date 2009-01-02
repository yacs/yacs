<?php
/**
 * display last news as a flash object
 *
 * This script retrieves local news from the local database, and builds a flashy web object out of it.
 *
 * Adapted from a Ming Tutorial. Thank you guys for this amazing piece of software!
 *
 * @link http://ming.sourceforge.net/tutorial/index9.html Ming the Tutorial
 *
 * @link http://www.as220.org/shawn/PGP/examples/example9-10.txt chat client (network reception)
 * @link http://www.as220.org/shawn/PGP/examples/example9-7.txt pre-loader
 * @link http://www.fontimages.org.uk/flash/struc.html Passing structured data to flash
 * @link http://matteo.balocco.free.fr/tfl/ More ideas for future extensions
 *
 * The ETag attribute is computed based on the list get from the database.
 * Therefore, cached objects are correctly validated even if the flash object itself
 * changes on each invocation, because of the random choice of rendering functions.
 * Moreover, the validation occurs before computing any Flash data, meaning a very efficient
 * and fast reply on cache match.
 *
 * @author Bernard Paques
 * @tester Guillaume Perez
 * @tester Nuxwin
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

include_once '../../shared/global.php';

Safe::load('parameters/feeds.flash.include.php');

// load a skin engine
load_skin('articles');

// ensure we see only articles visible at the home page
$context['skin_variant'] = 'home';

// sanity check
if(is_callable('ming_useswfversion'))
	ming_useswfversion(4);

// list fresh news
include_once '../feeds.php';
if(!$items = Feeds::get_local_news(20, 'compact'))
	return;

// keep only titles and ISO8859-1 labels
$titles = array();
$links = array();
$count = 0;
foreach($items as $url => $label) {

	// we are not interested into all attributes
	if(is_array($label))
		$label = $label[1];

	// strip codes
	include_once '../../shared/codes.php';
	$label = Codes::strip($label);

	// remove every html tag
	$label = strip_tags(html_entity_decode($label));

	// make an absolute reference
	$url = $context['url_to_home'].$context['url_to_root'].$url;

	// remember this
	$titles[$count] = $label;
	$links[$count] = $url;
	$count++;
}

// cache handling --except on scripts/validate.php
if(!headers_sent() && (isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'GET'))) {

	// this is a schockwave object
	Safe::header('Content-Type: application/x-shockwave-flash');

	// enable 30-minute caching (30*60 = 1800), even through https, to help IE6 on download
	Safe::header('Expires: '.gmdate("D, d M Y H:i:s", time() + 1800).' GMT');
	Safe::header("Cache-Control: max-age=1800, public");
	Safe::header("Pragma:");

	// the original content
	$page = '';
	for($index = 0; $index < $count; $index++)
		$page .= $titles[$index].':'.$links[$index].':';

	// handle the etag
	$etag = '"'.md5($page).'"';
	Safe::header('ETag: '.$etag);

	// validate the content if input has not been changed
	if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && is_array($if_none_match = explode(',', str_replace('\"', '"', $_SERVER['HTTP_IF_NONE_MATCH'])))) {
		foreach($if_none_match as $target) {
			if(trim($target) == $etag) {
				Safe::header('Status: 304 Not Modified', TRUE, 304);
				return;
			}
		}
	}
}

include_once 'infuncs.php';
include_once 'outfuncs.php';

// silently exists if Ming is broken -- scripts/validate.php
if(!class_exists('SWFMovie'))
	return;
$m =& new SWFMovie();
if(isset($context['flash_width']) && $context['flash_width'])
	$width = $context['flash_width'];
else
	$width = 500;
if(isset($context['flash_height']) && $context['flash_height'])
	$height = $context['flash_height'];
else
	$height = 50;
$m->setDimension($width, $height);

// assume a transparent background, except if explicitly configured
if(isset($context['flash_background_r'])
		&& isset($context['flash_background_g'])
		&& isset($context['flash_background_b'])
		&& ($context['flash_background_r'] + $context['flash_background_g'] + $context['flash_background_b']))
	$m->setBackground(intval($context['flash_background_r']), intval($context['flash_background_g']), intval($context['flash_background_b']));

$m->setRate(24.0);

// make a hit region for the button
$hit =& new SWFShape();
$hit->setRightFill($hit->addFill(0,0,0));
$hit->movePenTo(0, 0);
$hit->drawLine($width, 0);
$hit->drawLine(0, $height);
$hit->drawLine(-$width, 0);
$hit->drawLine(0, -$height);

// actual transmission except on a HEAD request -- and on scripts/validate.php
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD')) {

	// load a font file -- still buggy http://bugs.php.net/bug.php?id=31047&edit=2
	if(!isset($context['flash_font']) || !$context['flash_font'])
		$context['flash_font'] = 'Bimini.fdb';
	if(@is_readable($context['flash_font']))
		$f =& new SWFFont($context['flash_font']);
	else
		$f =& new SWFFont("_sans");
	$font_height = 0;

	if(!isset($context['flash_font_r']) || (!$r = intval($context['flash_font_r'])))
		$r = 0x11;
	if(!isset($context['flash_font_g']) || (!$g = intval($context['flash_font_g'])))
		$g = 0x33;
	if(!isset($context['flash_font_b']) || (!$b = intval($context['flash_font_b'])))
		$b = 0x33;
	if(!isset($context['flash_font_height']) || (!$height = intval($context['flash_font_height'])))
		$height = 40;

	for($i=0; $i<$count; ++$i)
	{
		$t =& new SWFText();
		$t->setFont($f);
		$t->setColor($r, $g, $b);
		$my_height = $height;
		while(true) {
			$t->setHeight($my_height);
			if($t->getWidth($titles[$i]) < 0.90*$width)
				break;
			$my_height = 0.90 * $my_height;
		}
		$x[$i] = 5;
		$y[$i] = ($height - $font_height)/2;
		$t->moveTo($x[$i], $y[$i]);
		$t->addUTF8String($titles[$i]);

		$buttons[$i] =& new SWFButton();
		$buttons[$i]->addShape($hit, SWFBUTTON_HIT);
		$buttons[$i]->addShape($t, SWFBUTTON_OVER | SWFBUTTON_UP | SWFBUTTON_DOWN);
		$buttons[$i]->addAction(new SWFAction("getURL('".$links[$i]."', '');"), SWFBUTTON_MOUSEUP);
	}

	for($n=0; $n<4; ++$n) {
		for($i=0; $i<$count; ++$i) {
			$infunc = $infuncs[rand(0, count($infuncs)-1)];
			$instance = $infunc($m, $buttons[$i], $x[$i], $y[$i]);

			for($j=0; $j<60; ++$j)
				$m->nextFrame();

			$outfunc = $outfuncs[rand(0, count($outfuncs)-1)];
			$outfunc($m, $buttons[$i], $instance, $x[$i], $y[$i]);
		}
	}

	// output the Flash object
	$m->output(9);
}

// the post-processing hook
finalize_page();


?>