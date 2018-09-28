<?php
/**
 * provide extract of data about users profile
 *
 * @see query_privacy.php
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_users_as_excerpt extends Layout_interface {

	/**
	 * list users
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see layouts/layout.php
	**/
	function layout($result) {
                
                $text = '';

		// empty list
		if(!SQL::count($result)) {
			return $text;
		}

         

		// process all items in the list
		while($item = SQL::fetch($result)) {
                        $data = '';
                        
                       
                        // layout all fields
			foreach($item as $key => $value) {
                            
                            
                            // special case
                            switch ($key) {
                                
                                case 'password' :
                                case 'handle'   :
                                    $value      = "***";
                                    break;
                                case 'overlay' :
                                    continue;
                                
                            }
                            
                            // data sample
                            $data .= tag::_div(tag::_span($key, 'details k/pas').tag::_span($value, 'k/pas'), '/grid-2');
                            
                            
                        }
                        
                        // data in overlay if any
                        $overlay = overlay::load($item, 'user:'.$item['id']);
                        $overlay_data = '';
                        if(is_object($overlay)) {
                            
                            foreach($overlay->attributes as $key => $value) {
                                
                                if(is_array($value)) $value = json_encode($value);
                                
                                $overlay_data .= tag::_div(tag::_span($key, 'details k/pas').tag::_span($value, 'k/pas'), '/grid-2');
                                
                            }
                            
                        }
                        
                        // add overlay data
                        if($overlay_data) {
                           $data .= $overlay_data; 
                        }
                        
                        // wrap all
                        $text .= tag::_div($data,'/grid-3 /has-gutter');
		}

		// end of processing
		SQL::free($result);
		return $text;
	}

}