<?php

/**
 * Layout items as a unordered list
 * with icon (thumbs) title and introduction
 *
 * @see skins/page.php, skin/skin_skeleton.php
 *
 * Created to implement horizontal drop down menu (improved tabs feature)
 * within tabs, but could be used to list sections everywhere.
 *
 * You can specify 'no_icon' or 'no_intro' parameters to the layout
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_as_smartlist extends Layout_interface {

    /**
     * the preferred number of items for this layout
     *
     * @return int the optimised count of items for this layout
     *
     * @see layouts/layout.php
     */
    function items_per_page() {
        return COMPACT_LIST_SIZE;
    }

    /**
     * list items
     *
     *  Accept following variants (you can mix them):
     *  - 'no_icon', not to show icons of sections
     *  - 'no_intro', not to show intro of sections
     *
     * @param resource the SQL result
     * @return string the rendered text
     *
     * @see layouts/layout.php
     * */
    function layout($result) {
        global $context;

        // empty list
        if (!SQL::count($result)) {
            $output = array();
            return $output;
        }

        // getting variants
        $show_icon = !$this->has_variant('no_icon');
        $show_intro = !$this->has_variant('no_intro');
        $show_update = !$this->has_variant('no_flag');

        // we calculate an array of ($url => $attributes)
        $items = array();

        // type of listed object
        $items_type = $this->listed_type;

        // process all items in the list
        include_once $context['path_to_root'] . 'overlays/overlay.php';
        while ($item = SQL::fetch($result)) {

            // get the object interface, this may load parent anchor and overlay
            $entity = new $items_type($item);

            // the url to view this item
            $url = $entity->get_permalink();

            // initialize variables
            $prefix = $label = $suffix = $icon = '';

            // flag sections that are draft or dead
            if (isset($item['activation_date']) && $item['activation_date'] >= $context['now'])
                $prefix .= DRAFT_FLAG;
            // signal entity to be published
	        if(isset($item['publish_date']) && (($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > $context['now'])))
                $prefix .= DRAFT_FLAG;
            if (($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
                $prefix .= EXPIRED_FLAG;

            // signal restricted and private sections
            if ($item['active'] == 'N')
                $prefix .= PRIVATE_FLAG;
            elseif ($item['active'] == 'R')
                $prefix .= RESTRICTED_FLAG;

            if ($show_update) {
                // flag items updated recently
                if ($item['create_date'] >= $context['fresh'])
                    $suffix .= NEW_FLAG;
                elseif ($item['edit_date'] >= $context['fresh'])
                    $suffix .= UPDATED_FLAG;
            }

            // use the title to label the link
            if (is_object($entity->overlay))
                $label = Codes::beautify_title($entity->overlay->get_text('title', $item));
            else
                $label = Codes::beautify_title($item['title']);

            // strip label and uppercase first letter
            $label = ucfirst(Skin::strip($label, 4));


            // get introduction
            if ($show_intro) {
                $introduction = '';
                if (is_object($entity->overlay))
                    $introduction = $entity->overlay->get_text('introduction', $item);
                else
                    $introduction = $item['introduction'];

                // the introductory text, strip to 10 words, preserve Yacs Code
                if ($introduction)
                    $suffix .= BR . '<span class="details">'
                          //. Codes::beautify_introduction(Skin::strip($introduction, 10, NULL, NULL, TRUE))
                          . Codes::beautify_introduction(Skin::strip($introduction, 10, NULL, '<a><br><img><span>', TRUE))
                          . '</span>';
            }

            // the icon
            if (($item['thumbnail_url']) && $show_icon)
                $icon = $item['thumbnail_url'];

            // list all components for this item
            $items[$url] = array($prefix, $label, $suffix, 'basic', $icon, NULL);
        }
        
        // merge prefix and suffix if any
        if(isset($this->data['prefix']) && is_array($this->data['prefix'])) $items = array_merge ($this->data['prefix'], $items);
        if(isset($this->data['suffix']) && is_array($this->data['suffix'])) $items = array_merge ($items, $this->data['suffix']);
        
        // end of processing
        SQL::free($result);

        //prepare HTML result, give default icon if required, provide callback function for final rendering
        $text = Skin::build_list($items, $this->layout_variant, ($show_icon) ? DECORATED_IMG : NULL, FALSE, 'Layout_as_smartlist::finalize_list');
        
        // we have bounded styles and scripts
        $this->load_scripts_n_styles();

        return $text;
    }

    /**
     * Finalize list rendering
     *
     * to be called back from skin::build_list
     * @return text the html rendering of drop down menu list
     *
     * @param array of items
     * @param string list variant (not used here)
     */
    public static function finalize_list($list, $variant = '') {
        global $context;

        $text = '';

        if ($list) {
            
            // use a layout to parse variant
            $lay = new Layout_as_smartlist;
            $lay->layout_variant = $variant;
            $li_class   = $lay->has_variant("li_class");
            $ul_id      = $lay->has_variant("ul_id");
            
            $first = TRUE;
            foreach ($list as $label) {

                $class = ($li_class)?$li_class:'';
                
                if ($first) {
                    $class .= ' first';
                    $first = FALSE;
                }
                
                if(trim($class))
                    $class = ' class="'.$class.'"';

                $icon = '';
                if (is_array($label))
                    list($label, $icon) = $label;

                $text .= '<li' . $class . '><div class="icon">' . $icon . '</div><div class="label">' . $label . '</div></li>' . "\n";
            }

            $text = '<ul class="smartlist" '.(($ul_id)?' id="'.$ul_id.'" ':'').'>' . "\n" . $text . '</ul>' . "\n";
        }

        return $text;
    }

}
