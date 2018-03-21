<?php
/**
 *test memcached module
 *
 * Only the associate can use this script
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */


 // common libraries
include_once '../shared/global.php';

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
$context['page_title'] = 'Testing memcached';

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
}

if($ram){
    // Memcache is enabled.
    $context['text'] .= tag::_('p','','Memcached module detected !');
    
    // try to get a value 
    $result = $ram->get("blah");

    if ($result) {
        $context['text'] .= tag::_('p','',$result);
        
        // list of current keys old in cache
        $keys = $ram->getAllKeys();
        
        $context['text'] .= tag::_('p','','List of current keys in cache :');
        
        $list = '';
        foreach($keys as $k) {
            $list .= tag::_('li','',$k);
        }
        $context['text'] .= tag::_('ul','',$list);
        
    } else {
        // record the value
        $context['text'] .=  tag::_('p','',"No matching key found.  I'll add that now !");
        if($ram->set("blah", "I am data !  I am held in memcached !", 30)) {
           $context['text'] .= tag::_('p','',"Data successfully added"); 
        } else {
           $context['text'] .= tag::_('p','',"Couldn't save anything to memcached...");
        }
    }
    
    
} else {
    
    $context['text'] .= 'Memcached unavailable';
}

// render the skin
render_skin();