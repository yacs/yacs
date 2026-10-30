<?php

/**
 * render table entities
 * - dynamic tables, recorded in "tables" table in yacs' database [table=id]
 * - static tables, formatted manually within the text [table]...[/table]
 * 
 * @author Bernard Paques (rendering)
 * @author Alexis Raimbault (factoring)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Code_table extends Code {
    
    var $patterns = array(
            '/\[table(?:=([^\]]+?))?\](.*?)\[\/(table)\]/is',  // [table]...[/table] [table=variant]...[/table] ( must be declared before )
            '/\[table(?:\.([^=\]]+?))?=([^\]]+?)\]/is'       // [table=<id>] [table.json=<id>]
        );
    
    public function render($matches) {
        
        $text = '';
        list($variant,$id_content) = $matches;
        
        if(!$variant) $variant = 'inline';
        
        if(isset($matches[2])) {
            $text = self::render_static_table($id_content, $variant);
        } else {
            $text = self::render_dynamic_table($id_content, $variant);
        }
        
        return $text;
    }
    
    /**
    * render a dynamic table
    *
    * @param string the table content
    * @param string the variant, if any
    * @return string the rendered text
    **/
    public static function render_dynamic_table($id, $variant='inline') {
           global $context;

           // refresh on every page load
           Cache::poison();

           // get actual content
           include_once $context['path_to_root'].'tables/tables.php';

           // the filtered view relied on SIMILE Exhibit, which is not available anymore
           if($variant == 'filter')
                   $variant = 'sortable';

           // build sparkline
           if($variant == 'bars') {
                   $text = '<img border="0" align="baseline" hspace="0" src="'.$context['url_to_root'].Tables::get_url($id, 'fetch_as_png').'&order=0&gap;0.5" alt="" />';

           
           // build sparkline
           } elseif($variant == 'line') {
                   $text = '<img border="0" align="baseline" hspace="0" src="'.$context['url_to_root'].Tables::get_url($id, 'fetch_as_png').'&order=2&gap=0.0" alt="" />';

           // we do the rendering ourselves
           } else
                   $text = Tables::build($id, $variant);

           // put that into the web page
           return $text;
    }
    
    /**
    * render a table
    *
    * @param string the table content
    * @param string the variant, if any
    * @return string the rendered text
    **/
    public static function render_static_table($content, $variant='') {
           global $context;

           // we are providing inline tables
           if($variant)
                   $variant = 'inline '.$variant;
           else
                   $variant = 'inline';

           // do we have headers to proceed?
           $in_body = !preg_match('/\[body\]/i', $content);

           // start at first line, except if headers have to be printed first
           if($in_body)
                   $count = 1;
           else
                   $count = 2;

           // split lines
           $rows = explode("\n", $content);
           if(!is_array($rows))
                   return '';

           // one row per line - cells are separated by |, \t, or 2 spaces
           $text = Skin::table_prefix($variant);
           foreach($rows as $row) {

                   // skip blank lines
                   if(!$row)
                           continue;

                   // header row
                   if(!$in_body) {
                           if(preg_match('/\[body\]/i', $row))
                                   $in_body = true;
                           else
                                   $text .= Skin::table_row(preg_split("/([\|\t]| "." )/", $row), 'header');

                   // body row
                   } else
                           $text .= Skin::table_row(preg_split("/([\|\t]| "." )/", $row), $count++);

           }

           // return the complete table
           $text .= Skin::table_suffix();
           return $text;
    }
}

