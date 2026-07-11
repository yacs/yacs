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
 * Besides the codes engine, render() is also called directly by scripts/browse.php
 * to highlight a whole script file.
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
        $variant = isset($matches[0]) ? $matches[0] : 'snippet';

        // second captured group is the code to display, if any
        $text = isset($matches[1]) ? $matches[1] : '';

        $text = Codes::fix_tags($text);

        // change new lines
        $text = trim(str_replace("\r", '', str_replace(array("<br>\n", "<br/>\n", "<br />\n", '<br>', '<br/>', '<br />'), "\n", $text)));

        // caught from tinymce
        if(preg_match('/<p>(.*)<\/p>$/s', $text, $tags)) {
            $text = $tags[1];
            $text = str_replace(array('&amp;', '<p>', '</p>'), array('&', '', "\n"), $text);
        }

        // match some php code
        $explicit = FALSE;
        if(preg_match('/<\?php\s/', $text))
            $variant = 'php';
        elseif(($variant == 'php') && !preg_match('/<\?'.'php.+'.'\?'.'>/', $text)) {
            $text = '<?'.'php'."\n".$text."\n".'?'.'>';
            $explicit = TRUE;
        }

        // highlight php code, if any
        if($variant == 'php') {

            // fix some chars set by wysiwig editors
            $text = str_replace(array('&lt;', '&gt;', '&nbsp;', '&quot;'), array('<', '>', ' ', '"'), $text);

            // handle newlines and indentations properly
            $text = str_replace(array("\n<span", "\n</code", "\n</pre", "\n</span"), array('<span', '</code', '</pre', '</span'), Safe::highlight_string($text));

            // remove explicit php prefix and suffix -- dependant of highlight_string() evolution
            if($explicit)
                $text = preg_replace(array('/&lt;\?php<br\s*\/>/', '/\?&gt;/'), '', $text);

        // or prevent html rendering
        } else
            $text = str_replace(array('<', "\n"), array('&lt;', '<br/>'), $text);

        // prevent additional transformations
        $search = array(	'[',		']',		':',		'//',			'##',			'**',			'++',			'--',			'__');
        $replace = array(	'&#91;',	'&#93;',	'&#58;',	'&#47;&#47;',	'&#35;&#35;',	'&#42;&#42;',	'&#43;&#43;',	'&#45;&#45;',	'&#95;&#95;');
        $output = '<pre>'.str_replace($search, $replace, $text).'</pre>';

        return $output;
    }
}
?>
