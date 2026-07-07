<?php

/**
 * render [php]...[/php] and [snippet]...[/snippet] blocks
 *
 * These codes are pure PRESENTATION: they display a piece of code, either
 * syntax-highlighted PHP ([php]) or a verbatim snippet ([snippet]), wrapped in
 * a <pre> element. They do NOT execute anything -- unlike the former [execute]
 * code that has been moved to codes/unused/.
 *
 * They are packaged as a movable extension so that a site which never displays
 * code can drop this file into codes/unused/ (and delete codes/patterns.auto.php
 * to force a rebuild) to spare two rendering passes on every piece of content.
 *
 * Ordering note: these codes MUST run early, right after [escape], so that the
 * other codes do not alter the code being displayed (e.g. // ## [section=x] a
 * URL or an e-mail address found inside the block). codes/codes.php loads this
 * extension at that early point and the generic code_*.php scan skips it.
 *
 * The actual rendering lives in Codes::render_pre(), a shared utility also used
 * outside the codes engine (scripts/browse.php); this class only binds the tags.
 *
 * @author Bernard Paques (rendering)
 * @author Alexis Raimbault (extension mechanism)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class code_pre extends Code {

    var $patterns = array(
        '/\[(php)\](.*?)\[\/php\]/is',              // [php]...[/php]
        '/\[(snippet)\](.*?)\[\/snippet\]/is',      // [snippet]...[/snippet]
    );

    public function render($matches) {

        // first captured group is the tag name, i.e. the rendering variant
        $variant = $matches[0];

        // second captured group is the code to display, if any
        $text = isset($matches[1]) ? $matches[1] : '';

        return Codes::render_pre($text, $variant);
    }
}
?>
