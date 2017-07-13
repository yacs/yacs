<?php
/**
 * [fa=xx]
 * render a font awesome icon
 * @see included/font_awesome/fa.php
 * @see http://fontawesome.io/
 * 
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

class Code_fa extends Code {
    
    // [fa=a_name]
    // [fa=a_name,option1 option2]
    var $patterns = array('/\[fa=([^,\]]+?)(?:,([a-z0-9 ]+))?\]\n*/is');

    /**
     * render a calendar
     *
     * The provided anchor can reference:
     * - a section 'section:123'
     * - nothing
     *
     * @param string the anchor (e.g. 'section:123')
     * @return string the rendered text
    **/
    public function render($matches) {
            global $context;
            $text = $options = '';
            
            
            $icon = (count($matches))?$matches[0]:'';
            
            if(isset($matches[1])) $options = $matches[1];

            if(!$icon) return $text;
            
            // a list of dates
            include_once $context['path_to_root'].'included/font_awesome/fa.php';

            $text = fa::_($icon, $options);

            // job done
            return $text;
    }
}