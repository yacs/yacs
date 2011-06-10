<?php
/**
 * correct thumbnail_url and image_url after changing 'url_to_root'
 *
 * Only the associate can use this script
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
 
 // common libraries
include_once '../shared/global.php';

// work on sections, categories and articles
include_once '../articles/articles.php';
include_once '../sections/sections.php';
include_once '../categories/categories.php';

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

// the path to this page
$context['path_bar'] = array( 'tools/' => i18n::s('Tools') );

// default page title
$context['page_title'] = i18n::s('Updating thumbnails url');

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

        if(($action == 'confirmed') && isset($_REQUEST['former_url'])) {

            //add "images/" to url, to be sure to replace only begining
            $former_url = $_REQUEST['former_url'].'images/';

            //  I ANALYSE THUMBNAILS IN ARTICLES TABLE
            $text .= Skin::build_block(i18n::s('Analysing thumbnails for articles'),'title');

            // query to update 
            $query = "UPDATE ".SQL::table_name('articles')." SET ";
            $query .= "thumbnail_url= REPLACE(thumbnail_url,'"
                .$former_url."','".$context['url_to_root']."images/')";

            // proceed
            $result = SQL::query($query);            

            // nb of lines
            if($result)
                $text .= '<p>'.$result.' line(s) updated</p>';
            else
                $text .= '<p>No line updated</p>';

             //  II ANALYSE ICONS IN ARTICLES TABLE
            $text .= Skin::build_block(i18n::s('Analysing icons for articles'),'title');

            // query to update 
            $query = "UPDATE ".SQL::table_name('articles')." SET ";
            $query .= "icon_url= REPLACE(icon_url,'"
                .$former_url."','".$context['url_to_root']."images/')";

            // proceed
            $result = SQL::query($query);

            // nb of lines
            if($result)
                $text .= '<p>'.$result.' line(s) updated</p>';
            else
                $text .= '<p>No line updated</p>';

            //  III ANALYSE THUMBNAILS IN SECTIONS TABLE
            $text .= Skin::build_block(i18n::s('Analysing thumbnails for sections'),'title');

            // query to update  
            $query = "UPDATE ".SQL::table_name('sections')." SET ";
            $query .= "thumbnail_url= REPLACE(thumbnail_url,'"
                .$former_url."','".$context['url_to_root']."images/')";

            // proceed
            $result = SQL::query($query);

            // nb of lines
            if($result)
                $text .= '<p>'.$result.' line(s) updated</p>';
            else
                $text .= '<p>No line updated</p>';

            //  IV ANALYSE ICONS IN SECTIONS TABLE
            $text .= Skin::build_block(i18n::s('Analysing icons for sections'),'title');

            // query to update 
            $query = "UPDATE ".SQL::table_name('sections')." SET ";
            $query .= "icon_url= REPLACE(icon_url,'"
                .$former_url."','".$context['url_to_root']."images/')";

            // proceed
            $result = SQL::query($query);

            // nb of lines
            if($result)
                $text .= '<p>'.$result.' line(s) updated</p>';
            else
                $text .= '<p>No line updated</p>';

            //  V ANALYSE THUMBNAILS IN CATEGORIES TABLE
            $text .= Skin::build_block(i18n::s('Analysing thumbnails for categories'),'title');

            // query to update 
            $query = "UPDATE ".SQL::table_name('categories')." SET ";
            $query .= "thumbnail_url= REPLACE(thumbnail_url,'"
                .$former_url."','".$context['url_to_root']."images/')";

            // proceed
            $result = SQL::query($query);

            // nb of lines
            if($result)
                $text .= '<p>'.$result.' line(s) updated</p>';
            else
                $text .= '<p>No line updated</p>';

            //  VI ANALYSE ICONS IN CATEGORIES TABLE
            $text .= Skin::build_block(i18n::s('Analysing icons for categories'),'title');

            // query to update
            $query = "UPDATE ".SQL::table_name('categories')." SET ";
            $query .= "icon_url= REPLACE(icon_url,'"
                .$former_url."','".$context['url_to_root']."images/')";

            // proceed
            $result = SQL::query($query);

            // nb of lines
            if($result)
                $text .= '<p>'.$result.' line(s) updated</p>';
            else
                $text .= '<p>No line updated</p>';
            
            //  VII ANALYSE THUMBNAILS IN FILES TABLE
            $text .= Skin::build_block(i18n::s('Analysing thumbnails for files'),'title');

            // query to update 
            $query = "UPDATE ".SQL::table_name('files')." SET ";
            $query .= "thumbnail_url= REPLACE(thumbnail_url,'"
                .$former_url."','".$context['url_to_root']."images/')";

            // proceed
            $result = SQL::query($query);

            // nb of lines
            if($result)
                $text .= '<p>'.$result.' line(s) updated</p>';
            else
                $text .= '<p>No line updated</p>';

            // END : report
            $context['text'] = $text;
        }
	
} else {


    $context['text'] = '<p>'.i18n::s('This tools correct the urls of thumbnails and icons of pages after having changed "url_to_root" in control panel').'</p>';

   // the form to get the former URL to root and start the process
   $context['text'] .= '<form method="post" enctype="multipart/form-data" action="'
        .$context['script_url'].'" id="main_form"><div>'
        .'<input type="hidden" name="action" value="confirmed" />';

   $fields = array();

   // the former URL
   $label = i18n::s('Please give former URL to root');
   $input = '<input type="text" name="former_url" size="50" value="" />';
   $hint = i18n::s('eg "/yacs/" or "/" or "/my_repo/yacs/"');
   $fields[] = array($label, $input, $hint);
   

   // the current URL
   $label = i18n::s('This will be replaced by current URL');
   $input = '<input type="text" name="current_url" size="50" value="'
        .$context['url_to_root'].'" READONLY />';
   $hint = '';
   $fields[] = array($label, $input, $hint);

   $context['text'] .= Skin::build_form($fields);

   // the submit button
   $context['text'] .= '<p class="assistant_bar">'.
        Skin::build_submit_button(i18n::s('Start')).'</p>'."\n";

   // end of the form
   $context['text'] .= '</div></form>';
}		
	
// render the skin
render_skin();

?>	