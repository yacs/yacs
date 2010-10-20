<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * [calendar]
 *
 * @author Alexis Raimbault
 */

class Code_Calendar extends Code {
    
    function get_pattern_replace(&$pattern,&$replace) {

        // [calendar]
        $pattern[] = '/\[calendar\]\n*/ise'; 
        $replace[] = "Code_Calendar::render()";

        // [calendar=section:4029]
        $pattern[] = '/\[calendar=([^\]]+?)\]\n*/ise';
        $replace[] = "Code_Calendar::render('$1')";
    }

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
    function &render($anchor='') {
            global $context;

		// a list of dates
		include_once $context['path_to_root'].'dates/dates.php';

		// sanity check
		$anchor = trim($anchor);

		// get records
		if(strpos($anchor, 'section:') === 0)
			$items =& Dates::list_for_prefix(NULL, 'compact', $anchor);
		else
			$items =& Dates::list_for_prefix(NULL, 'compact', NULL);

		// build calendar for current month
		$text =& Dates::build_months($items, FALSE, TRUE, FALSE, TRUE, gmstrftime('%Y'), gmstrftime('%m'), 'compact calendar');

		// job done
		return $text;
    }
}
?>