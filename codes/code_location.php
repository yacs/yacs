<?php
/**
 * [calendar]
 *
 * @author Bernard Paques (rendering)
 * @author Alexis Raimbault (factoring)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

class Code_Location extends Code {
    
    // [locations=<id>] or [location=<id>]
    var $patterns = array('/\[location(s)*=([^\]]+?)\]/is');

    /**
     * render location(s)
     *
     *
     * @param string the anchor (e.g. 'section:123')
     * @return string the rendered text
    **/
    public function render($matches) {
        
            list($multiple, $id) = $matches;

            if($multiple) {
                $text = self::render_locations($id);
            } else {
                $text = self::render_location($id);
            }


            // job done
            return $text;
    }
    
    /**
    * render a location
    *
    * @param string the id, with possible options or variant
    * @return string the rendered text
   **/
   public static function render_location($id) {
           global $context;
           
           // the required library
            include_once $context['path_to_root'].'locations/locations.php';

           // check all args
           $attributes = preg_split("/\s*,\s*/", $id, 3);

           // [location=latitude, longitude, label]
           if(count($attributes) === 3) {
                   $item = array();
                   $item['latitude'] = $attributes[0];
                   $item['longitude'] = $attributes[1];
                   $item['description'] = $attributes[2];

           // [location=id, label] or [location=id]
           } else {
                   $id = $attributes[0];

                   // a record is mandatory
                   if(!$item = Locations::get($id)) {
                           if(Surfer::is_member()) {
                                   $output = '&#91;location='.$id.']';
                                   return $output;
                           } else {
                                   $output = '';
                                   return $output;
                           }
                   }

                   // build a small dynamic image if we cannot use Google maps
                   if(!isset($context['google_api_key']) || !$context['google_api_key']) {
                           $output = BR.'<img src="'.$context['url_to_root'].'locations/map_on_earth.php?id='.$item['id'].'" width="310" height="155" alt="'.$item['geo_position'].'" />'.BR;
                           return $output;
                   }

                   // use provided text, if any
                   if(isset($attributes[1]))
                           $item['description'] = $attributes[1].BR.$item['description'];

           }

           // map on Google
           $output = Locations::map_on_google(array($item));
           return $output;

   }
   
   /**
    * render several locations
    *
    * @param string 'all' or 'users'
    * @return string the rendered text
    **/
    public static function render_locations($id='all') {
           global $context;

           // the required library
           include_once $context['path_to_root'].'locations/locations.php';

           // get markers
           $items = array();
           switch($id) {
           case 'all':
                   $items = Locations::list_by_date(0, 100, 'raw');
                   break;

           case 'users':
                   $items = Locations::list_users_by_date(0, 100, 'raw');
                   break;

           default:
                   if(Surfer::is_member()) {
                           $output = '&#91;locations='.$id.']';
                           return $output;
                   } else {
                           $output = '';
                           return $output;
                   }
           }

           // integrate with google maps
           $output = Locations::map_on_google($items);
           return $output;

    }
}