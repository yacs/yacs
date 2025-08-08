<?php

/** 
 * Intégration de la lib Altcha dans yacs
 * Permet un antirobot dans les formulaires
 * 
 * @see https://github.com/altcha-org/altcha-lib-php/tree/main
 * 
 * @reference
 * @author devalxr
 */


// include altcha files
foreach (glob($context['path_to_root']."included/altcha/src/*.php") as $filename)
{
    include_once $filename;
}

foreach (glob($context['path_to_root']."included/altcha/src/Hasher/*.php") as $filename)
{
    include_once $filename;
}


define('CHALLENGEURL','included/altcha/getchallenge.ajax.php');
define('HMACK','Please custom your key');
define('INPUTNAME','altcha');

Class yaltcha {
    
    /**
     *  Create a new challenge for javascript client
     *  @see getchallenge.ajax.php
     */
    static function get_challenge() {
        
        $options = new \AltchaOrg\Altcha\ChallengeOptions(
            maxNumber: 5000, // the maximum random number // si trop élevé cela augemente le temps de check
            expires: (new \DateTimeImmutable())->add(new \DateInterval('PT3600S')),
        );
        
        $altcha     = new \AltchaOrg\Altcha\Altcha(HMACK);
        $challenge  = $altcha->createChallenge($options);
        
        return json_encode($challenge);
    }
    
    /**
     * return the html code to insert in a <form> 
     * you want to protect
     * @see /global/surfer.php, get_robot_stopper()
     * 
     * @global type $context
     * @return string
     */
    static function insert_widget() {
        global $context;
        
        $widget = '<altcha-widget challengeurl="'
                .$context['url_to_home']
                .$context['url_to_root']
                .CHALLENGEURL
                .'" auto="onload"></altcha-widget>';
        
        return $widget;
    }
    
     /**
     * load js lib for altcha on client side
     */
    static function loadjs() {
        global $context;
        
        $context['page_footer'] .= '<script async defer src="'
                .$context['url_to_home'].$context['url_to_root']
                .'included/altcha/altcha.min.js'
                .'" type="module"></script>';
    }
    
    /**
     * verify a challenge result within a POST request
     * @see /global/surfer.php, may_be_a_robot()
     * 
     * @return boolean
     */
    static function verify_challenge() {
        
        // à incorporer dans un traitement de soumission de formulaire
        // prendre l'argument du champ altcha soumis au formulaire
        
        $payload = NULL;
        if(!empty($_REQUEST[INPUTNAME]) && is_string($_REQUEST[INPUTNAME])) {
            $payload = $_REQUEST[INPUTNAME];
        }
        
        $altcha     = new \AltchaOrg\Altcha\Altcha(HMACK);
        
        return $altcha->verifySolution($payload);
    }
    
}