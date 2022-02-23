<?php

/** 
 * Client class for matomo server.
 * For server side tracking features.
 * 
 * 
 * @see https://developer.matomo.org/api-reference/PHP-Matomo-Tracker
 * 
 * @author devalxr
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */


Class tracker {
    
    private $tracker;
    private $active = 'N';
    private $idsite = 1;
    private $url = '';
    private $token = '';
    private $trackAll = 'N';
    
    /**
     * load parameters for tracking
     * reply false if parameters aren't valid
     * 
     * @global type $context
     * @return bool
     */
    private function _checkContext() : bool {
        global $context;
        
        Safe::load('parameters/matomo.include.php');
        
        if(!isset($context['matomo_active'])) {
            
            logger::remember('matomo', 'missing configuration');
            return false;
        }
        
        $this->active           = $context['matomo_active'];
        if($this->active !== 'Y') return false;
        
        $this->url              = $context['matomo_url'];
        $this->idsite           = (int) $context['matomo_idsite'];
        $this->token            = $context['matomo_token'];
        $this->trackAll         = $context['matomo_trackAll'];
        
        
        return true;
    }
    
    /**
     * initialize matomo php client tracker
     * to be called prior to any recording
     * 
     * @global type $context
     */
    private function _init() {
        global $context;
        
        // -- Matomo Tracking API init -- 
        require_once 'matomo.php';
        
        MatomoTracker::$URL = $this->url;
        
        $matomoTracker = new MatomoTracker( $this->idsite );
        // Specify an API token with at least Write permission, so the Visitor IP address can be recorded 
        // Learn more about token_auth: https://matomo.org/faq/general/faq_114/
        $matomoTracker->setTokenAuth($this->token);
        
        // TODO
        // set latitude and longitude if well known (hmtl5)
        
        $this->tracker = $matomoTracker;
    }
    
    /**
     * public function to load parameters
     * @see configure.php
     */
    public function loadConf() {
        $this->_checkContext();
    }
    
    public function ping() {
        if(!$this->_checkContext()) return false;
        
        $this->_init();
        
        return $this->tracker->doPing();
    }
    
    /**
     * to be called by heartbeat hook
     * @see control/scan.php
     * @see users/heartbeat.php
     * 
     * @return string
     */
    public static function pingHook() {
        
        $tracker = new tracker();
        
        $job = $tracker->ping();
        
        return 'Ping triggered with Matomo : ' .(($job)?'pass':'blocked');
    }
    
    /**
     * track a page
     * use page title setted in $context
     * 
     * @global type $context
     * @return boolean
     */
    public function trackPage() {
        global $context;
        
        if(!$this->_checkContext()) return false;
        
        // by default do not track page without "index" mention
        $block = !isset($context['robots']) || $context['robots'] !== 'index,follow';
        if( $this->trackAll === 'N' && $block ) return false;
        
        $this->_init();
        
        // Sends Tracker request via http
        return $this->tracker->doTrackPageView($context['page_title']);
    }
    
    /**
     * to be called by finalize hook, for automatic tracking of page
     * @see shared/global.php::render_skin() and shared/global.php::finalize_page()
     * @see control/scan.php
     * 
     * @return string
     */
    public static function trackHook() {
        
        $tracker = new tracker();
        
        $job = $tracker->trackPage();
        
        return 'Page tracking triggered with Matomo : ' .(($job)?'pass':'blocked');
    }
    
    /**
     * record a event in matomo
     * 
     * @param string $category
     * @param string $action
     * @param string|bool $name
     * @param float|bool $value
     * @return boolean
     */
    public function trackEvent($category, $action, $name = false, $value = false) {
        
        if(!$this->_checkContext()) return false;
        
        $this->_init();
        
        return $this->tracker->doTrackEvent($category, $action, $name, $value);
    }
    
}