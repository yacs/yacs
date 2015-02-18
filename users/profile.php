<?php

/* 
 * Redirect automaticaly a user to its profile
 */

include_once '../shared/global.php';

if(!Surfer::is_logged())
    Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode($context['url_to_home'].$context['url_to_root'].'users/profile.php'));

else
    Safe::redirect(Surfer::get_permalink ());