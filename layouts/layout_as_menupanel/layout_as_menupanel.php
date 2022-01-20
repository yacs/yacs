<?php

/**
 * Layout to use as a second-level for top-menu [ Page::tabs() ]
 * 
 * Display sections in a template grid. The number of columns can be provided with parameter.
 * Can display extra information in a column, from named article or skin.php function
 *
 * Warning ! the number of subsections displayed is caped by TABS_DROP_LIST_SIZE const.
 * Increase it if needed in the initialiaze() method of skin.php from your skin folder.
 * 
 * Warning ! the Page::tabs() output is cached. Purge server Cache while doing tests
 * 
 * fixed number of column : 
 * add "col_N" param to the call, with N the number of column.
 * note : extra info will add one more column
 *  
 * extra information on panel :
 * general : create a echo_menupanel_extra_all() method in your skin.php, 
 * providing html to display on each subsections panel
 * 
 * specific : create a echo_menupanel_<focus>() method in your skin.php, 
 * with <focus> equal to section_<id> or <nick_name> of mother section to add extra
 * information only on the subsections panel of this given mother section.
 * 
 * You can do the same without skin.php functions but named articles
 * 'menupanel_extra_all and/or menupanel_<focus>.
 * The description will be used for display.
 * Skin.php methods are priorized over named articles
 * 
 * yet an alternative is to set $context['components']['menupanel_extra_all'] 
 * (for ex.) with html to display.  
 * 
 * When used by Page::tabs(), please insert this call within 
 * the <head> section of your template, after Page::meta() :
 *  Page::load_style('layouts/layout_as_menupanel/layout_as_menupanel.scss','now');
 * 
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// constant that defined thresholds for automatic number of columns,
// depending the number of sections to layout
CONST COL_1_MAX = 3;
CONST COL_2_MAX = 6;
CONST COL_3_MAX = 12;
CONST COL_4_MAX = 20;

Class layout_as_menupanel extends Layout_interface {
    
    /** 
     * capture Page::component() echo output
     * @return string
     */
    private function _capture_component($name) {
        
        ob_start();
        Page::component($name, 'rawdiv');
        $capture = ob_get_contents();
        ob_end_clean();
        
        return $capture;
    }
    
    /** 
     * get any extra information to display on panel
     * @return string
     */
    private function _get_extra() {
        $extra = '';
        
        // get current focus, id or nickname
        $mother     = Sections::get($this->focus);
        if($mother && $mother['nick_name'])
            $focus  = $mother['nick_name'];
        else
            $focus  = str_replace(':', '_', $this->focus);
        
        
        // check if a function define extra for current focus
        $specif      = $this->_capture_component('menupanel_'.$focus);
        $extra      .= ($specif)? $specif : '';
        
        // check if we have a general extra
        $general     =  $this->_capture_component('menupanel_extra_all');
        $extra      .= ($general)? $general : '';
        
        return $extra;
    }
    
    /**
     * automatic choice of number of columns to use for grid
     * 
     * @param int $count
     * @param string $extra
     * @return int
     */
    private function _get_nbcol($count, $extra = '') {
        
        // fixed form parameter
        if($nb = $this->has_variant('col')) {
                return $nb;
        }
                
        // automatic from count
        if($count <= COL_1_MAX) 
            $nb = 1;
        elseif($count <= COL_2_MAX)
            $nb = 2;
        elseif($count <= COL_3_MAX)
            $nb = 3;
        elseif($count <= COL_4_MAX)
            $nb = 4;
        else {
            $nb = 5;
        }
        
        return $nb;
    }
    
    /**
     * Layout the subsections
     * 
     * @param object $result
     * @return string
     */
    public function layout($result) {
        
        // count
        $count = SQL::count($result);
        
        if(!$count) return '';
        
        // check extra information
        $extra  = $this->_get_extra();
       
        
        // get nb of columns for grid
        $nb     = $this->_get_nbcol($count, $extra);
        
        // build a grid using knacss, use short titles
        // each element of grid as an unique #id, allowing css handle 
        $grid = '';
        while($item = SQL::fetch($result)) {
            
            $label  = ($item['index_title'])? $item['index_title'] : $item['title'];
            $url    = Sections::get_permalink($item);
            
            $id     = ($item['nick_name'])? $item['nick_name'] : 'section_'.$item['id'];
            
            $grid  .= tag::_div(Skin::build_link($url, $label, 'basic'),'/menup_entry #entry_'.$id);
        }
        $gridw  = tag::_div(tag::_div($grid,"/grid-$nb-small-1 /has-gutter-l /col-$nb"));
        
        SQL::free($result);
        
        // container, with extra if any
        if($extra)
            $cont = tag::_div(tag::_div($extra,'/menup-extra').$gridw,'/menup-wrapper /grid-'.($nb+1).'-small-1 /has-gutter-l /pam');
        else 
            $cont = tag::_div($gridw, '/menup-wrapper /pam');
        
        // warning : in case of top menu use with call from page::tabs()
        // scss has to be load specifically in template of skin
        $this->load_scripts_n_styles();
        
        return $cont;
    }
}