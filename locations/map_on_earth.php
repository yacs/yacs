<?php
/**
 * locate geographical coordinates on earth
 *
 * This script computes an image with a dot on the earth map.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'locations.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Locations::get($id);

// load the skin
load_skin('locations');

// the path to this page
$context['path_bar'] = array( 'locations/' => i18n::s('Locations') );

// the title of the page
if(isset($item['geo_place_name']))
	$context['page_title'] = $item['geo_place_name'];

// not found
if(!isset($item['id']))
	Logger::error(i18n::s('No item has the provided id.'));

// no capability to create an image
elseif(!is_callable('ImageCreateFromJpeg'))
	Logger::error(i18n::s('Not capable to generate dynamic images.'));

// we need a file to draw the map
elseif(!file_exists($context['path_to_root'].'locations/images/earth_310.jpg'))
	Logger::error(i18n::s('No image to use as a map.'));

// display the map for this location
else {

	// return the finished image as PNG
	if(!headers_sent())
		Safe::header("Content-type: image/png");

	// enable 30-minute caching (30*60 = 1800), even through https, to help IE6 on download
	http::expire(1800);

	// strong validator
	$etag = '"'.md5($item['geo_place_name'].$item['longitude'].$item['latitude']).'"';
	
	// manage web cache
	if(http::validate(NULL, $etag))
		return;

	// load the main image
	$image = ImageCreateFromJpeg($context['path_to_root'].'locations/images/earth_310.jpg');
	$width = ImageSx($image);
	$height = ImageSy($image);

	// ensure we have split coordinates
	if(!$item['latitude'] || !$item['longitude'])
		list($item['latitude'], $item['longitude']) = preg_split('/[\s,;]+/', $item['geo_position']);

	// scale coordinates
	$x = round(($item['longitude'] + 180) * ($width / 360));
	$y = round((($item['latitude'] * -1) + 90) * ($height / 180));

	// mark the point on the map using a red 4 pixel rectangle
	$red = ImageColorAllocate ($image, 255,0,0);
	ImageFilledRectangle($image, $x-2, $y-2, $x+2, $y+2, $red);

	// actual transmission except on a HEAD request
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
		ImagePng($image);
	ImageDestroy($image);

	// the post-processing hook, then exit
	finalize_page(TRUE);

}

// render the skin
render_skin();

?>