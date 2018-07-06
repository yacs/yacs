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
            '/\[table(?:\.([^=\]]+?))?=([^\]]+?)\]/is'       // [table=<id>] [table.json=<id>]  [table.timeplot=<id>]
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

           // use SIMILE Exhibit
           if($variant == 'filter') {

                   // load the SIMILE Exhibit javascript library in shared/global.php
                   $context['javascript']['exhibit'] = TRUE;

                   // load data
                   $context['page_header'] .= "\n".'<link href="'.$context['url_to_root'].Tables::get_url($id, 'fetch_as_json').'" type="application/json" rel="exhibit/data" />';

                   // exhibit data in a table
                   $text = '<div ex:role="exhibit-view" ex:viewClass="Exhibit.TabularView" ex:columns="'.Tables::build($id, 'json-labels').'" ex:columnLabels="'.Tables::build($id, 'json-titles').'" ex:border="0" ex:cellSpacing="0" ex:cellPadding="0" ex:showToolbox="true" ></div>'."\n";

                   // allow for filtering
                   $facets = '<div class="exhibit-facet">'
                           .'<div class="exhibit-facet-header"><span class="exhibit-facet-header-title">'.i18n::s('Filter').'</span></div>'
                           .'<div class="exhibit-facet-body-frame" style="margin: 0 2px 1em 0;">'
                           .'<div ex:role="facet" ex:facetClass="TextSearch" style="display: block;"></div>'
                           .'</div></div>';

                   // facets from first columns
                   $facets .= Tables::build($id, 'json-facets');

                   // filter and facets aside
                   $context['components']['boxes'] .= $facets;

           // build sparkline
           } elseif($variant == 'bars') {
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

