<?php
/**
 * resize all thumbs
 *
 * Only the associate can use this script
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

 // common libraries
include_once '../shared/global.php';

// use image shrink function
include_once '../images/image.php';

// what to do
$action = '';
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];
if(!$action && isset($context['arguments'][0]))
	$action = $context['arguments'][0];
$action = strip_tags($action);

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin
load_skin('tools');

// do not index this page
$context->sif('robots','noindex');

// the path to this page
$context['path_bar'] = array( 'tools/' => i18n::s('Tools') );

// default page title
$context['page_title'] = i18n::s('resizing thumbnails');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].
				$context['url_to_root'].'users/login.php?url='
				.urlencode('tools/populate.php?action='.$action));

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the control panel
	$menu = array('tools/' => i18n::s('Tools'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// create test data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST'))  {
	$text = '';

	if(($action == 'confirmed') ) {

		// get images list from database
                $query = "SELECT anchor, image_name, thumbnail_name FROM ".SQL::table_name('images')
                        ." WHERE image_size >= ".$context['thumbnail_threshold'];
                
                $result     = SQL::query($query);
                $job        = array();
                $message    = i18n::s('Generate thumbnail for image %s');
                
                while($image = SQL::fetch($result)) {
                    
                    // get image folder
                    $path = $context['path_to_root'].Files::get_path($image['anchor'], 'images').'/';
                    
                    // shrink image
                    if(Image::shrink($path.$image['image_name'], $path.$image['thumbnail_name'])) {
                    
                        // memorize job done
                        $job[]  = sprintf($message, $image['image_name']);
                    }
                    
                }
                
                if($total = count($job)) {
                    
                    $context['text'] .= tag::_('p','',implode(BR, $job));
                    
                    $context['text'] .= tag::_('p','', sprintf(i18n::s('%s thumbnail(s) rebuilded'), $total));
                    
                }
            
	}

} else {


	$context['text'] .= '<p>'.i18n::s('This tools resizes thumbnails of images to dimensions provided in general configuration.').'</p>';
        
        $context['text'] .= tag::_('p', tag::_class('details'), '('.$context['thumbnail_width'].' x '.$context['thumbnail_height'].' px )');   

   // the form to get the former URL to root and start the process
   $context['text'] .= '<form method="post" enctype="multipart/form-data" action="'
		.$context['script_url'].'" id="main_form"><div>'
		.'<input type="hidden" name="action" value="confirmed" />';


	// the submit button
	$context['text'] .= '<p class="assistant_bar">'.
		Skin::build_submit_button(i18n::s('Start')).'</p>'."\n";

	// end of the form
	$context['text'] .= '</div></form>';
}

// render the skin
render_skin();