<?php
/**
 * share webcam sessions at mebeam
 *
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = trim(strip_tags($id));

// create a random id if none has been provided
if(!is_string($id) || !$id)
	$id = 'mebeam'.rand(10000, 99999);
	
// load localized strings
i18n::bind('tools');

// load the skin
load_skin('tools');

// the path to this page
$context['path_bar'] = array( 'tools/' => i18n::s('Tools') );

// populate page attributes
$context['page_title'] = sprintf(i18n::s('Video session by MeBeam: %s'), $id);

// a link to share the 
// insert the mebeam Flash agent
$context['text'] .= '<object  width="512" height="460" id="mebeam_control" align="middle">'."\n"
	.' <param name="allowScriptAccess" value="always" >'."\n"
	.' <param name="movie" value="http://www.mebeam.com/mebeam.swf">'."\n"
	.' <param name="quality" value="best" >'."\n"
	.' <param name="scale" value="noscale" >'."\n"
	."\n"
	.' <param name="salign" value="t" >'."\n"
	.' <param name="flashvars" value="room='.$id.'">'."\n"
	.' <embed  flashvars="room='.$id.'" src="http://www.mebeam.com/mebeam.swf" quality="best" scale="noscale" salign="t"  width="512" height="460" name="mebeam_control"  align="middle" allowScriptAccess="always" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" >'."\n"
	.'</object>';

// render the page according to the loaded skin
render_skin();

?>