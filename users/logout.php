<?php
/**
 * break a session
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// if it was a HEAD request, stop here
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// clear permanent name
Safe::setcookie('surfer_name', '', time()+60*60*24*500, '/');

// destroy surfer session
Surfer::reset();

// redirect to another page
if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] && !preg_match('/login\.php/i', $_SERVER['HTTP_REFERER'])) {
    
        
    
        //// intercept the following urls of a object in edition
        // section-edit/4259
        // sections/edit.php?id=4259
        // sections/edit.php/4259
        // idem for articles, categories, images, files ...
        $matches = array();
    
        if(preg_match('/(section|article|category|categorie|file|image|user)(?:s\/edit\.php|-edit)(?:\?id=|\/)([0-9]+)/', $_SERVER['HTTP_REFERER'], $matches)) {
           
            $object_type = $matches[1];
            $object_id   = $matches[2];
            
            // special case
            if($object_type == 'categorie') $object_type = 'category';
            
            // get object interface
            $object = Anchors::get($object_type.':'.$object_id);
            // check if access is granted
            if($object->allows('access')) {
                // redirect to view mode
                Safe::redirect($object->get_permalink());
            } else {
                // reditect to root
                Safe::redirect($context['url_to_home'].$context['url_to_root']);
            }
                
        } else {
            // redirect to last script
            Safe::redirect($_SERVER['HTTP_REFERER']);
        }
        
        
} else
	Safe::redirect($context['url_to_home'].$context['url_to_root']);