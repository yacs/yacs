<?php
/**
 * [calendar]
 *
 * @author Alexis Raimbault
 */

class Code_Calendar extends Code {
    
    // [calendar] or [calendar=section:4029]
    var $patterns = array('/\[calendar(?:=([^\]]+?))?\]\n*/is');

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
            
            $anchor = (count($matches))?$matches[0]:'';

            // a list of dates
            include_once $context['path_to_root'].'dates/dates.php';

            // get records
            if($anchor && strpos($anchor, 'section:') === 0)
                    $items = Dates::list_for_prefix(NULL, 'compact', trim($anchor));
            else
                    $items = Dates::list_for_prefix(NULL, 'compact', NULL);

            // build calendar for current month
            $text = Dates::build_months($items, FALSE, TRUE, FALSE, TRUE, gmstrftime('%Y'), gmstrftime('%m'), 'compact calendar');

            // job done
            return $text;
    }
}