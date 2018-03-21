<?php
/**
 * finalize javascript libraries
 *
 * This script reads all javascript files from selected directories, compresses content,
 * and generates compacted .js files.
 * 
 * Those libs are used when server is in production mode (with_debug = 'N')
 *
 * It processes files from following directories:
 * - included/browser/js_header
 * - included/browser/js_endpage
 *
 * @see included/minifier for minification lib
 * @see shared/js_css.php for yacs js/css management
 *
 * To run this script, the surfer has to be an associate, or no switch file exists.
 *
 * @author Bernard Paques
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../scripts/scripts.php';

// load localized strings
i18n::bind('tools');

// load the skin
load_skin('tools');

// the path to this page
$context['path_bar'] = array( 'tools/' => i18n::s('Tools') );

// the title of the page
$context['page_title'] = i18n::s('Finalize Javascript libraries');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('included/browser/build.php'));

// only associates can proceed when a switch file exists
elseif(!Surfer::is_associate() && !(file_exists($context['path_to_root'].'parameters/switch.on') || file_exists($context['path_to_root'].'parameters/switch.off'))) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the index page
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'confirm')) {


	// list running scripts
	$context['text'] .= '<p>'.i18n::s('Compressing Javascript files...').BR."\n";

	// script to not compress (provide name1.ext, name2.ext...)
	$to_avoid = array(

		);

	// process all js files in included/browser/js_header
	$count      = 0;
	$minified   = '';
        $names      = '';
        $folder     = $context['path_to_root'].'included/browser/js_header/';
	$files      = Safe::glob("$folder*.js");
        
	if(is_array($files)  && count($files))
	    foreach( $files as $name) {

		    if(in_array(basename($name), $to_avoid))
			    continue;

		    $context['text'] .= 'included/browser/js_header/'.basename($name).BR."\n";

		    // we do have some content
		    if($text = Safe::file_get_contents($name)) {

			    // actual compression, if file name does not have .min in name
			    if(!preg_match('/\.min\./', basename($name))) {
                                    
				Js_css::minify($text, $minified, 'js');
                            } else {
                                // just concat content
				$minified .= ";\n".$text;
                            }

			    // one file has been included
			    $count++;

		    }
                    
                    $names .= basename($name);
	    }
            
        // unlink previous names checksum file
        array_map('unlink', glob( "$folder*.auto.sum"));    
            
	// save the library to call in page header
	$file_min = $context['path_to_root'].'temporary/library_js_header.min.js';
	if($minified) {
	    Safe::file_put_contents($file_min, $minified);
            
            // write new checksum file
            Safe::file_put_contents($folder.md5($names).'.auto.sum', 'Checksum of all javascript libs names');
            
	} else {
	   Safe::unlink ($file_min);
	}

	// do the same with included/browser/js_endpage, including shared/yacs.js
	$minified ='';
        $names      = '';
        $folder     = $context['path_to_root'].'included/browser/js_endpage/';
	$files      = Safe::glob("$folder*.js");

	if(is_array($files)  && count($files))
	    foreach( $files as $name) {

		    if(in_array(basename($name), $to_avoid))
			    continue;

		    $context['text'] .= 'included/browser/js_endpage/'.basename($name).BR."\n";

		    // we do have some content
		    if($text = Safe::file_get_contents($name)) {

			    // actual compression			    
			    if(!preg_match('/\.min\./', basename($name))) {
                                    
				Js_css::minify($text, $minified, 'js');
                            } else {
                                // just concat content
				$minified .= ";\n".$text;
                            }

			    // one file has been included
			    $count++;

		    }
                    
                    $names .= basename($name);
	    }
	// include shared/yacs.js library
	if(file_exists($context['path_to_root'].'shared/yacs.js')) {
	    $context['text'] .= 'shared/yacs.js'.BR."\n";
	    $text = Safe::file_get_contents($context['path_to_root'].'shared/yacs.js');
            Js_css::minify($text, $minified, 'js');
	    $count++;
	}
        
        // unlink previous names checksum file
        array_map('unlink', glob( "$folder*.auto.sum"));    
        
        
	// save the library to call in page footer
	$file_min = $context['path_to_root'].'temporary/library_js_endpage.min.js';
	if($minified) {
	    Safe::file_put_contents($file_min, $minified);
            
            // write new checksum file
            Safe::file_put_contents($folder.md5($names).'.auto.sum', 'Checksum of all javascript libs names');
            
	} else {
	    Safe:unlink ($file_min);
	}


	// report to surfer
	if($count)
		$context['text'] .= sprintf(i18n::s('%d files have been minified.'), $count)."\n";
	$context['text'] .= "</p>\n";

	$context['text'] .= '<p>'.sprintf('%s has %d %s', 'temporary/library_js_header.min.js', Safe::filesize($context['path_to_root'].'temporary/library_js_header.min.js'), i18n::s('bytes')).'</p>';
	$context['text'] .= '<p>'.sprintf('%s has %d %s', 'temporary/library_js_endpage.min.js', Safe::filesize($context['path_to_root'].'temporary/library_js_endpage.min.js'), i18n::s('bytes')).'</p>';

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

// confirmation is required
} else {

	// the confirmation question
	$context['text'] .= '<b>'.sprintf(i18n::s('You are about to compress and assemble Javascript files. Are you sure?'), $context['file_mask'])."</b>\n";

	// the menu for this page
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'"><p>'
		.Skin::build_submit_button(i18n::s('Yes, I do want to compress and assemble Javascript files'))
		.'<input type="hidden" name="action" value="confirm" />'
		.'</p></form>'."\n";

	// this may take several minutes
	$context['text'] .= '<p>'.i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.')."</p>\n";

}

// render the skin
render_skin();