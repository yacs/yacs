<?php

/* 
 * Redirect automaticaly a user to its profile
 * or invite him to log in (and then redirect)
 * can be a usefull target for a link in email for example.
 *	
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

include_once '../shared/global.php';

if(!Surfer::is_logged())
    Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode($context['url_to_home'].$context['url_to_root'].'users/profile.php'));

else
    Safe::redirect(Surfer::get_permalink ());