<?php
/**
 * examples of formatting codes for blocks
 *
 * Following codes are documented on this page:
 * - &#91;indent]...[/indent] - shift text to the right
 * - &#91;center]...[/center] - some centered text
 * - &#91;right]...[/right] - some right-aligned text
 * - &#91;decorated]...[/decorated] - some pretty paragraphs
 * - &#91;caution]...[/caution] - a warning paragraph
 * - &#91;note]...[/note] - a noticeable paragraph
 * - &#91;php]...[/php] - a snippet of php
 * - &#91;snippet]...[/snippet] - a snippet of fixed font data
 * - &#91;quote]...[/quote] - a block of quoted text
 * - &#91;folder]...[/folder] - click to view its content, or to fold it away
 * - &#91;folder=foo bar]...[/folder] - with title 'foo bar'
 * - &#91;sidebar]...[/sidebar] - a nice box aside
 * - &#91;sidebar=foo bar]...[/sidebar] - with title 'foo bar'
 * - &#91;scroller]...[/scroller] - some scrolling text
 *
 * @see codes/index.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load localized strings
i18n::bind('codes');

// load the skin
load_skin('codes');

// the path to this page
$context['path_bar'] = array( 'help/' => i18n::s('Help index'),
	'codes/' => i18n::s('Formatting Codes') );

// the title of the page
$context['page_title'] = i18n::s('Codes to format blocks');

// the date of last modification
if(Surfer::is_associate())
	$context['page_details'] .= '<p class="details">'.sprintf(i18n::s('Edited %s'), Skin::build_date(getlastmod())).'</p>';

// page header
$context['text'] .= '<p>'.i18n::s('On this page we are introducing some formatting codes and live examples of utilization.').'</p>';

// add a toc
$context['text'] .= "\n".'[toc]'."\n";

// [indent]...[/indent]
$context['text'] .= '[title]'.i18n::s('Indentation').' [escape][indent]...[/indent][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...')."\n"
		.'[indent]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/indent]'."\n"
		.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape]</td>'
	.'<td>'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[nl]'
		.'[indent]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/indent][nl]'
		.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'</td></tr>'
	.Skin::table_suffix();

// [center]...[/center]
$context['text'] .= '[title]'.i18n::s('Center').' [escape][center]...[/center][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][center]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/center][/escape]</td>'
	.'<td>[center]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/center]</td></tr>'
	.Skin::table_suffix();

// [right]...[/right]
$context['text'] .= '[title]'.i18n::s('Right').' [escape][right]...[/right][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][right]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/right][/escape]</td>'
	.'<td>[right]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/right]</td></tr>'
	.Skin::table_suffix();

// [decorated]...[/decorated]
$context['text'] .= '[title]'.i18n::s('Decorated').' [escape][decorated]...[/decorated][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][decorated]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/decorated]'."\n"
		.'[decorated]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/decorated]'."\n"
		.'[decorated]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/decorated][/escape]</td>'
	.'<td>[decorated]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/decorated][nl]'
		.'[decorated]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/decorated][nl]'
		.'[decorated]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/decorated]</td></tr>'
	.Skin::table_suffix();

// [caution]...[/caution]
$context['text'] .= '[title]'.i18n::s('Caution').' [escape][caution]...[/caution][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][caution]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/caution][/escape]</td>'
	.'<td>[caution]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/caution]</td></tr>'
	.Skin::table_suffix();

// [note]...[/note]
$context['text'] .= '[title]'.i18n::s('Note').' [escape][note]...[/note][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][note]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/note][/escape]</td>'
	.'<td>[note]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/note]</td></tr>'
	.Skin::table_suffix();

// [php]...[/php]
$context['text'] .= '[title]'.i18n::s('PHP snippet').' [escape][php]...[/php][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('A snippet of load_skin(), from global.php:')."\n"
	.'[php]'."\n"
	.'// maybe we are at the root level'."\n"
	.'if(file_exists($skin.\'.php\')) {'."\n"
	.'	include $skin.\'_library.php\';'."\n"
	.'	include \'shared/codes.php\';'."\n"
	.'	$context[\'path_to_root\'] = \'\';'."\n"
	."\n"
	.'// or we are one level below'."\n"
	.'} else {'."\n"
	.'	include \'../\'.$skin.\'_library.php\';'."\n"
	.'	include \'../shared/codes.php\';'."\n"
	.'	$context[\'path_to_root\'] = \'../\';'."\n"
	.'}'."\n"
	.'[/php][/escape]</td>'."\n"
	.'<td>'.i18n::s('A snippet of load_skin(), from global.php:')."\n"
	.'[php]'."\n"
	.'// maybe we are at the root level'."\n"
	.'if(file_exists($skin.\'.php\')) {'."\n"
	.'	include $skin.\'_library.php\';'."\n"
	.'	include \'shared/codes.php\';'."\n"
	.'	$context[\'path_to_root\'] = \'\';'."\n"
	."\n"
	.'// or we are one level below'."\n"
	.'} else {'."\n"
	.'	include \'../\'.$skin.\'_library.php\';'."\n"
	.'	include \'../shared/codes.php\';'."\n"
	.'	$context[\'path_to_root\'] = \'../\';'."\n"
	.'}'."\n"
	.'[/php]</td></tr>'
	.Skin::table_suffix();

// [snippet]...[/snippet]
$context['text'] .= '[title]'.i18n::s('Pre-formatted').' [escape][snippet]...[/snippet][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('Let assume a standard HTTP request to get it, such as the next one, which has 382 bytes:')."\n"
	.'[snippet]'."\n"
	.'GET /hello.html HTTP/1.1'."\n"
	.'Host: www.server.com'."\n"
	.'Accept: image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, */*'."\n"
	.'Accept-Encoding: gzip, deflate'."\n"
	.'Accept-Language: en-us'."\n"
	.'User-Agent: Mozilla/4.0'."\n"
	.'Connection: Keep-Alive'."\n"
	.'[/snippet][/escape]</td>'
	.'<td>'.i18n::s('Let assume a standard HTTP request to get it, such as the next one, which has 382 bytes:')."\n"
	.'[snippet]'."\n"
	.'GET /hello.html HTTP/1.1'."\n"
	.'Host: www.server.com'."\n"
	.'Accept: image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, */*'."\n"
	.'Accept-Encoding: gzip, deflate'."\n"
	.'Accept-Language: en-us'."\n"
	.'User-Agent: Mozilla/4.0'."\n"
	.'Connection: Keep-Alive'."\n"
	.'[/snippet]</td></tr>'
	.Skin::table_suffix();

// [quote]...[/quote]
$context['text'] .= '[title]'.i18n::s('Quote').' [escape][quote]...[/quote][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][quote]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/quote][/escape]</td>'
	.'<td>[quote]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/quote]</td></tr>'
	.Skin::table_suffix();

// [folder]...[/folder]
$context['text'] .= '[title]'.i18n::s('Folder').' [escape][folder]...[/folder][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][folder]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/folder][/escape]</td>'
	.'<td>[folder]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/folder]</td></tr>'
	.Skin::table_suffix();

// [folder=...]...[/folder]
$context['text'] .= '[title]'.i18n::s('Folder with title').' [escape][folder=&lt;title&gt;]...[/folder][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][folder='.i18n::s('A Rabbit Story').']'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/folder][/escape]</td>'
	.'<td>[folder='.i18n::s('A Rabbit Story').']'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/folder]</td></tr>'
	.Skin::table_suffix();

// [sidebar]...[/sidebar]
$context['text'] .= '[title]'.i18n::s('Sidebar').' [escape][sidebar]...[/sidebar][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][sidebar]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/sidebar][/escape]</td>'
	.'<td>[sidebar]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/sidebar]</td></tr>'
	.Skin::table_suffix();

// [sidebar=...]...[/sidebar]
$context['text'] .= '[title]'.i18n::s('Sidebar with title').' [escape][sidebar=&lt;title&gt;]...[/sidebar][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][sidebar='.i18n::s('A Rabbit Story').']'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/sidebar][/escape]</td>'
	.'<td>[sidebar='.i18n::s('A Rabbit Story').']'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/sidebar]</td></tr>'
	.Skin::table_suffix();

// [scroller]...[/scroller]
$context['text'] .= '[title]'.i18n::s('Scroller').' [escape][scroller]...[/scroller][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][scroller]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/scroller][/escape]</td>'
	.'<td>[scroller]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/scroller]</td></tr>'
	.Skin::table_suffix();

// transform the text
$context['text'] = Codes::beautify($context['text']);

// general help on this page
$help = '<p>'.sprintf(i18n::s('Please note that actual rendering depends on the selected %s.'), Skin::build_link('skins/', i18n::s('skin'), 'shortcut')).'</p>';
$context['extra'] .= Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

// render the skin
render_skin();

?>