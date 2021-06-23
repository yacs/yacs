<?php

/**
 * Utility to defer some scripts after the page being sent to surfers.
 * The scripts execution will be launched asynchroniously using reactPHP lib.
 * 
 * To queue a job (script), use Defer::queue(relative_path_to_script?args=xx)
 * the script will be called with an http get query from the server itself
 * 
 * By default the excecution is triggered by "finalize" hook, which is call by
 * finalise_page() from shared/global.php
 * 
 * If the environment where you are queuing script is not a standard 
 * yacs page rendering, consider calling Defer::run() manually, but 
 * don't forget you need global $context, initialized in shared/global.php
 * 
 * @Author Devalx
 * @Reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Defer {
    
    /**
     * queue a script at the end of php cycle, after the page being sent to surfer
     * Provide as argument a relative path to the script, with optionnal 
     * parameters like for an url
     * 
     * @global type $context
     * @param string $script the relative path to the script from yacs root
     */
    public static function queue($script) {
        global $context;
        
        // check that we have a valid script
        // i.e. that point to a file inside yacs. 
        // Prune parameters for the check 
        $tocheck = strstr($script, '?', true);
        if(!Safe::file($tocheck)) {
            logger::debug("not found: $tocheck", 'Defer');
            return false;
        }
        
        // create stack in needed
        if(!isset($context['defer']))
            $context['defer'] = array();
 
        // stack the defered script
        $context['defer'][] = $script;
        
        return true;
    }
    
    /**
     * run all script that have been defered
     * this is triggered by hook::finalize()
     * 
     * Activate this hook with control/scan.php
     * 
     * @global type $context
     * @return type
     */
    public static function run() {
        global $context;
        
        // retrieve stack of jobs todo
        $scripts = $context['defer'];
        
        // holidays time
        if(!count($scripts)) return;
        
        // load reactPHP
        require $context['path_to_root'].'/included/reactphp/vendor/autoload.php';

        // create base reactphp component for asynchroniousity
        $loop = React\EventLoop\Factory::create();

        // create a connector that allow no ssl
        $connector = new React\Socket\Connector($loop, array(
            'tls' => array(
                'verify_peer' => false,
                'verify_peer_name' => false
            )
        ));
        
        // create a browser object
        $client = new React\Http\Browser($loop, $connector);
        
        // create "promise" of a HTTP GET for each defered script
        while($script = array_shift($scripts)) {
            
            // build url to the script
            $target = $context['url_to_master'].$context['url_to_root'].$script;
            
            // create the promise and action in case of success or failure
            $client->get($target)->then(
                function (Psr\Http\Message\ResponseInterface $response) use ($script) {
                    logger::debug('Success :'.$script,'Defer');
                },
                function (Exception $error) use ($script){
                    logger::debug('fail : '.$script.' || '.$error->getMessage(),'Defer');
                }
            );
        }
        
        logger::debug('Start','Defer');
        ///// 
        // actually launch the jobs asynchroniously
        //
        $loop->run();
        ////
        logger::debug('End','Defer');
        
        }
        
    
}

// stop hackers
defined('YACS') or exit('Script must be included');