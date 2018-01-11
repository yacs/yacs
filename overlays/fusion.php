<?php

/**
 * This overlay give the possibility to merge several actual overlays
 * Merge overlays are provided as parameters (section's page options)
 * 
 * "fusion overlay1 overlay2 overlay3..."
 * 
 * Please be carefull with what you do and check the behaviour of
 * the fusion for the methods you have overrideen in merged overlays.
 * 
 * Be carefull also to not store values with same names.
 * 
 * You may change the order of parameters depending of the behaviour to prioritize
 *  
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Fusion extends Overlay {
    
        //////
        // Will contain a instance of each merged overlay.
        // @see initialize()
        var $merged = array();
        
        // flag for loaded state
        var $loaded = false;
        
        /**
         * Call successively a method for each overlay
         * the result is combined, depending of its type :
         * > logical AND for booleans
         * > addition for numbers
         * > merging for arrays
         * > concatenation for strings
         * 
         * @param string $name of the method to call
         * @param array $parameters to pass to method
         * @param mixed $result the output
         * @return boolean false if a error
         */
        private function fusion_chain_method($name, $parameters, &$result) {
            
            $this->fusion_load_overlays();
            
            while($ov = $this->fusion_get_next_ov()) {
                
                if(!is_callable(array($ov, $name))) continue;
                
                $unit = call_user_func_array(array($ov, $name), $parameters);
                
                if($result === null) {
                    $result_type = 'boolean';
                } else {
                    $result_type = gettype($result);
                }
                
                switch ($result_type) {
                
                    case 'boolean':
                        if($unit === null) continue;
                        
                        if($result === null) 
                            $result = $unit;
                        else
                            $result = $result && $unit;
                        break;

                    case 'integer':
                    case 'double':
                    case 'float' :
                        $result += $unit;
                        break;

                    case 'array':
                        if(is_array($unit)) {
                            $result = array_merge($result, $unit);
                        }
                        break;

                    case 'string':
                        $result .= $unit;
                        break;

                    default :
                        $result = null;
                        return false;
                
                }
                
            }
            
            return true;   
        }
        
        /**
         * Execute a method on each overlay but stops
         * when a result is different than null
         * 
         * @param string $name of the method
         * @param array $parameters to pass
         * @return mixed the result of the method or null
         */
        private function fusion_first_reply($name, $parameters) {
            
            $this->fusion_load_overlays();
            
            while($ov = $this->fusion_get_next_ov()) {
                
                if(!is_callable(array($ov, $name))) continue;
                
                $unit = call_user_func_array(array($ov, $name), $parameters);
                
                if($unit !== null) {
                    return $unit;
                }
                
            }
            
            return null;
            
        }
        
        /**
         * core method used to get merged overlays one by one
         * 
         * @return object overlay
         */
        private function fusion_get_next_ov() {
            
            if($ov = current($this->merged)) {
                
                next($this->merged);
                return $ov;
            } else {
                reset($this->merged);
            } 
            
            return null;
        }
        
        /**
         * Core function to load merged overlays.
         * Reset also the pointer to array of overlays
         * 
         * 
         * @return null
         */
        private function fusion_load_overlays() {
            
            // reset array pointer before any parsing
            reset($this->merged);
            
            // stop if already done before
            if($this->loaded) return;
            
            if($parameters = $this->attributes['overlay_parameters']) {
                
                $parameters = explode(' ', $parameters);
                
                foreach($parameters as $param) {
                    $overlay = Overlay::bind($param);
                    
                    if(is_object($overlay)) {
                        
                        // provide its attributes if any
                        if(isset($this->attributes[$param])) {
                            $overlay->attributes = $this->attributes[$param];
                        }
                        
                        // provide a link to anchor
                        if(is_object($this->anchor)) {
                            $overlay->anchor = $this->anchor;
                        }
                        
                        // active merged flag
                        $overlay->is_merged = true;
                        
                        // store the instance
                        $this->merged[] = $overlay;

                    } else {
                        logger::error('overlay '.$param.' not recognized as fusion parameter');
                    }
                }
                
            } else {
                logger::error('provide at least one parameter for fusion overlay');
            }
            
            // flag not to redo this
            $this->loaded = true;
            
        }
        
        /**
         * Call successively a method for each overlay but the 
         * result is passed as a argument for the next
         * 
         * 
         * @param string $name of the method
         * @param string $arg_name to give to the value we pass
         * @param mixed $arg_value to start with
         * @return mixed a result
         */
        private function fusion_pipe_method($name, $arg_name, $arg_value) {
            
            $this->fusion_load_overlays();
            
            while($ov = $this->fusion_get_next_ov()) {
                
                if(!is_callable(array($ov, $name))) continue;
                
                $parameter = array($arg_name => $arg_value); 
                
                $arg_value = call_user_func(array($ov, $name), $parameter);
                
            }
            
            return $arg_value;
            
        }
        
        
        ///////////////////////:
        
        // OVERLAY NATIVE METHODS OVERRIDEN
        
        ///////////////////////

        /**
	 * allow or block operations
	 *
	 * Ask each sub overlay and combine logically the result (&&)
         * So a false will prevail
         * 
	 * @return TRUE if the operation is accepted, FALSE otherwise,
         *  null to leave decision to yacs core 
	 */
	function allows($action, $type ='') {
            
            $result = null;
            $this->fusion_chain_method('allows', array($action, $type), $result);
            
            return $result;
            
	}
        
        /**
	 * build the list of fields for one overlay
	 *
	 * The fields of each overlay are merged
         * /!\ be aware not to have fields with identical names /!\
	 *
	 * @param the hosting attributes
	 * @return a list of ($label, $input, $hint) to be integrated into the form.
	 */
	function get_fields($host,$field_pos=NULL) {
		
                $result = array();
                
                $this->fusion_chain_method('get_fields', array($host, $field_pos), $result);
                
                return $result;
	}
        
        /**
	 * identify one instance
	 *
	 * take the value of the first overlay which give a overlay_id
         * 
	 * @returns a unique string, or NULL
	 */
	function get_id() {
            
                $id = $this->fusion_first_reply('get_id', array());
		return $id;
	}
        
        /**
	 * get an overlaid label
	 *
	 * return the label from the first overlay to reply
         * 
	 * @param string the target label
	 * @param string the on-going action
	 * @return the label to use, or NULL if no default label has been found
	 */
	function get_label($name, $action='view') {
            
            
                $label = $this->fusion_first_reply('get_label', array($name, $action));
            
		return $label;
	}
        
        
        /**
	 * display the content of one overlay in a list
	 *
	 * concat the rendering from each overlay
         * 
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_list_text($host=NULL) {
		$text = '';
                
                
                $this->fusion_chain_method('get_list_text', array($host), $text);
                
                
		return $text;
	}

	/**
	 * display a live description
	 *
	 * 
         * the fonction is piped trought each overlay
         * 
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_live_description($host=NULL) {
                
                $text = $this->fusion_pipe_method('get_live_description', 'description', $host['description']);
		return $text;
	}

	/**
	 * display a live introduction
	 *
	 * the fonction is piped trought each overlay
         * 
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_live_introduction($host=NULL) {
                
                $text = $this->fusion_pipe_method('get_live_introduction', 'introduction', $host['introduction']);
		return $text;
	}

	/**
	 * display a live title
	 *
         * only the first overlay may override the title
         * 
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_live_title($host=NULL) {
                
                $text = $this->fusion_first_reply('get_live_title', array($host));

		return $text;
	}
        
        /**
	 * add some tabbed panels
	 *
	 * tabs are merged.
         * /!\ be aware not to give tabs identical ids /!\
         * 
	 * @param string the on-going action
	 * @param array the hosting record
	 * @return an array of array('tab_id', 'tab_label', 'panel_id', 'panel_content') or NULL
	 */
	function get_tabs($variant='view', $host=NULL) {
		$output = array();
                
                $this->fusion_chain_method('get_tabs', array($variant, $host), $output);
                
                
		return $output;
	}
        
        /**
	 * display the content of one overlay
	 *
	 * first use standard dispatching
         * but look also in merged overlay for specific function
	 *
	 * @param string the variant code
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_text($variant='view', $host=NULL) {
            
            // standard behaviour
            $text = parent::get_text($variant, $host);
            
            // try something else if nothing founded
            if($text === null) {
                
                // look for specific function get_<variant>_text
                $text = $this->fusion_first_reply('get_'.$variant.'_text', array($host));
                
            }
            
            return $text;
            
        }
        
        /**
	 * text to come after page description
	 *
	 * concat the rendering from each overlay
         * 
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_trailer_text($host=NULL) {
		$text = '';
                
                $this->fusion_chain_method('get_trailer_text', array($host), $text);
                
		return $text;
	}
        
        /** 
         * retrieve url where to go after anchor is deleted.
         * if null then YACS will proceed to default behaviour
         * 
         * the first overlay to reply prevail
         * 
         */
        public function get_url_after_deleting() {
            
            $url = $this->fusion_first_reply('get_url_after_deleting', array());
            
            return $url;
        }
        
        /**
	 * get the value of one attribute
	 *
	 * 
         * first, ask the value to each sub overlay
         * take the first result.
         * 
         * eventually, if no result, ask in this fusion instance
         * 
	 * @param string attribute name
	 * @param mixed default value, if any
	 * @return mixed attribute value, of default value if attribute is not set
	 */
	function get_value($name, $default_value=NULL) {
            
            
                 $value = $this->fusion_first_reply('get_value', array($name, null));
                 
                 if($value === null) {
                     $value = parent::get_value($name, $default_value);
                 }
            
                 return $value;

	}
        
        /**
	 * display the content of one overlay in main view panel
	 *
	 * concat the rendering from each overlay
         * 
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_view_text($host=NULL) {
		
                $text = '';
                
                $this->fusion_chain_method('get_view_text', array($host), $text);
                
		return $text;
	}
        
        /**
	 * capture form content
	 *
	 * call method for each overlay
         * /!\ be aware not to have identical fields /!\
         * 
	 * @param array data transmitted to the server through a web form
	 */
	function parse_fields($fields) {
            
               $result = null; 
            
               // call parse field of each overlay
               $this->fusion_chain_method('parse_fields', array($fields), $result);
               
               // we have now to store their attributes into fusion serialized data
               while($ov = $this->fusion_get_next_ov()) {
                   
                   $this->attributes[$ov->get_value('overlay_type')] = $ov->attributes;
                   
               }
               
	}
        
        /**
	 * remember an action once it's done
	 *
         * call method for each overlay
	 *
	 * @param string the action 'insert', 'update' or 'delete'
	 * @param array the request containing data transmitted to the server through a web form
	 * @param string reference of the hosting record (e.g., 'article:123')
	 * @return FALSE on error, TRUE otherwise
	 */
	function remember($action, $request, $reference) {
		$proceed = true;
                
                $this->fusion_chain_method('remember', array($action, $request, $reference), $proceed);
            
                return $proceed;
	}
        
        /**
	 * render some page component
	 *
	 * return the result of the first overlay to reply
         * 
	 * @param string type of component to render, e.g., 'articles'
	 * @param string anchor reference, such as 'section:123'
	 * @param int page
	 * @return mixed some text, or NULL
	 */
	function render($type, $reference, $page=1) {
            
                $text = $this->fusion_first_reply('render', array($type, $reference, $page));
            
		return $text;
	}
        
        /**
         * This function allow to overide or complete
         * overlayed item behavior while receiving a "touch" event
         * 
         * if false is returned the standard processing will stop
         * 
         * Call method for each overlay
         * 
         * @param string $action code name
         * @param string $origin, usually a ref
         * @param boolean $silently, if the request requires the action to be recorded as a update
         */
        function touch($action, $origin=NULL, $silently=FALSE) {
            
            $proceed = null;
            
            $this->fusion_chain_method('touch', array($action, $origin, $silently), $proceed);
            
            return $proceed;
        }
        
        /**
	 * embed embeddable files or not?
	 *
	 * combine decision of each overlay. A false will prevail.
         * overlay return TRUE by default
	 *
	 * @return boolean
	 */
	function should_embed_files() {
            
                $reply = null;
                
                $this->fusion_chain_method('should_embed_files', array(), $reply);
            
		return $reply;
	}
        
        /**
	 * notify followers or not?
	 *
	 * This function is used in articles/publish.php to prevent notification of followers.
	 *
	 * combine decision of each overlay. A false will prevail.
         * overlay return FALSE by default
	 *
	 * @return boolean
	 */
	function should_notify_followers() {
		$reply = null;
                
                $this->fusion_chain_method('should_notify_followers', array(), $reply);
            
		return $reply;
	}

	/**
	 * notify watchers or not?
	 *
	 * This function is used in various scripts to prevent notification of watchers.
	 *
	 * combine decision of each overlay. A false will prevail.
         * overlay return TRUE by default
	 *
	 * @return boolean
	 */
	function should_notify_watchers($mail=NULL) {
		$reply = null;
                
                $this->fusion_chain_method('should_notify_watchers', array(), $reply);
            
		return $reply;
	}
        
}