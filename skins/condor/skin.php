<?php

/**
 * 
 * Use this class to overide skins/skin_skeleton method
 * 
 * @see skins/skin_skeleton
 * 
 * @author Alexis Raimbault
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Skin extends Skin_Skeleton {
    
    public static function initialize() {
        
        //define('NO_YACSS', true);
    }
    
    public static function insert_scripts() {
        global $context;

        // Call parent method to include other scripts
        parent::insert_scripts();
    }

    public static function tabs() {
        global $context;

        // Cache ID for the header menu
        $cache_id = 'skins/condor/header_menu';

        // Try to get content from cache
        if(!$text = Cache::get($cache_id)) {
            // Content not in cache, generate it
            
            // Get top-level sections (e.g., 5 items)
            $top_sections = Sections::list_by_title_for_anchor(NULL, 0, 5);

            if (!empty($top_sections)) {
                // Use 'header_menu' layout for sections
                $header_menu_layout = Layouts::new_('header_menu', 'section');
                if ($header_menu_layout) {
                    $text .= '<nav id="custom_header_menu">'.PHP_EOL;
                    $text .= $header_menu_layout->layout($top_sections);
                    $text .= '</nav>'.PHP_EOL;
                }
            }
            // Store generated content in cache
            Cache::put($cache_id, $text, 'sections'); // 'sections' is a common cache group
        }
        // Echo the content (from cache or newly generated)
        echo $text;
    }
}