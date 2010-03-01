<?php
/**
 * demonstrate YACS capability to build SIMILE timelines from GAN
 *
 * A minimum script based on the YACS framework.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// transform the test file
$text = Files::transform_gan_to_simile('tools/gan2simile_test.gan');

// handle the output correctly
render_raw('text/xml; charset='.$context['charset']);

// actual transmission except on a HEAD request
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $text;

?>