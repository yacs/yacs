<?php

/* 
 * Class to help building font awesome tags,
 * that is a icon implemented as a font
 * @see http://fontawesome.io
 * 
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class fa {
    
    /**
     * build a font awesome <i> tag to make nice icons
     * 
     * omit fa- while providing icon and option. It would be added automaticaly
     * 
     * examples :
     * fa::_('camera-retro', '3x')
     * fa::_('circle-o-notch', '3x spin', 'Loading...', true);
     * 
     * @param string $icon choosen to be displayed. You must at least provide that.
     * @see http://fontawesome.io/icons/ for a list
     * 
     * @param string $options space separated, would be integrated as prefixed 
     * classes to the tag @see http://fontawesome.io/examples/
     * 
     * @param string $title to add as a attribute. Would be displayed while overing
     * or as a remplacement for accessibility
     * 
     * @param boolean $aria_hide to hide this icon for surfer browsing with aria
     * system. @see http://fontawesome.io/accessibility/
     * 
     * @return string the html tag that would display the fontawesome icon
     */
    public static function _($icon, $options='', $title='',$aria_hide=false) {
        
        
        // icon and options are the same deal,
        // add a "fa" default option
        $options = 'fa '.$icon.' '.$options;
        
        // prefix everything with fa
        $options = preg_replace('/(?<![^ ])(?=[^ ])(?!fa)/', 'fa-', $options);
        
        // maybe more attributes
        $more = '';
        
        // add a title if any
        if($title) {
            $more .= tag::_attr('title', $title);
        }
        
        // accessibility option
        if($aria_hide) {
            $more .= tag::_attr('aria-hidden', 'true');
        }
        
        // build the tag
        $fa = tag::_('i', tag::_class($options, true).$more);
        
        // provide a span replacement for accessibility if needed
        if($aria_hide && $title) {
            $fa .= tag::_('span', tag::_class('sr-only', true), $title);
        }
        
        return $fa;
    }
    
}

