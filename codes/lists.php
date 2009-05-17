<?php
/**
 * examples of formatting codes for lists
 *
 * Following codes are documented on this page:
 * - &#91;*] - for simple lists
 * - &#91;list]...[/list] - bulleted list
 * - &#91;list=1]...[/list] - numbered list, use numbers
 * - &#91;list=a]...[/list] - numbered list, use letters
 * - &#91;list=A]...[/list] - numbered list, use capital letters
 * - &#91;list=i]...[/list] - numbered list, use roman numbers
 * - &#91;list=I]...[/list] - numbered list, use upper case roman numbers
 *
 * @see codes/index.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Agnes
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
$context['page_title'] = i18n::s('Codes to format lists');

// the date of last modification
if(Surfer::is_associate())
	$context['page_details'] .= '<p class="details">'.sprintf(i18n::s('Edited %s'), Skin::build_date(getlastmod())).'</p>';

// page header
$context['text'] .= '<p>'.i18n::s('On this page we are introducing some formatting codes and live examples of utilization.').'</p>';

// add a toc
$context['text'] .= "\n".'[toc]'."\n";

// [*]
$context['text'] .= '[title]'.i18n::s('List item').' [escape][*][/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('A simple list:')."\n\n".'[*]'.i18n::s('First item')."\n\n".'[*]'.i18n::s('Second item (after one empty line)')."\n".'[*]'.i18n::s('Third item (next the previous one)').'[/escape]</td>'
	.'<td>'.i18n::s('A simple list:')."\n\n".'[*]'.i18n::s('First item')."\n\n".'[*]'.i18n::s('Second item (after one empty line)')."\n".'[*]'.i18n::s('Third item (next the previous one)').'</td></tr>'
	.Skin::table_suffix();

// [list]...[/list]
$context['text'] .= '[title]'.i18n::s('Bulleted list').' [escape][list]...[/list][/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('A list with bulleted items:')."\n".'[list]'."\n".'[*]'.i18n::s('First item')."\n".'[*]'.i18n::s('Second item')."\n".'[/list][/escape]</td>'
	.'<td>'.i18n::s('A list with bulleted items:')."\n".'[list]'."\n".'[*]'.i18n::s('First item')."\n".'[*]'.i18n::s('Second item')."\n".'[/list]</td></tr>'
	.Skin::table_suffix();

// [list=1]...[/list]
$context['text'] .= '[title]'.i18n::s('Numbered list').' [escape][list=1]...[/list][/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('A list with numbered items:')."\n".'[list=1]'."\n".'[*]'.i18n::s('First item')."\n".'[*]'.i18n::s('Second item')."\n".'[/list][/escape]</td>'
	.'<td>'.i18n::s('A list with numbered items:')."\n".'[list=1]'."\n".'[*]'.i18n::s('First item')."\n".'[*]'.i18n::s('Second item')."\n".'[/list]</td></tr>'
	.Skin::table_suffix();

// [list=a]...[/list]
$context['text'] .= '[title]'.i18n::s('Numbered list').' [escape][list=a]...[/list][/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('A list with alphabetically numbered items:')."\n".'[list=a]'."\n".'[*]'.i18n::s('First item')."\n".'[*]'.i18n::s('Second item')."\n".'[/list][/escape]</td>'
	.'<td>'.i18n::s('A list with alphabetically numbered items:')."\n".'[list=a]'."\n".'[*]'.i18n::s('First item')."\n".'[*]'.i18n::s('Second item')."\n".'[/list]</td></tr>'
	.Skin::table_suffix();

// [list=A]...[/list]
$context['text'] .= '[title]'.i18n::s('Numbered list').' [escape][list=A]...[/list][/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('Another list with alphabetically numbered items:')."\n".'[list=A]'."\n".'[*]'.i18n::s('First item')."\n".'[*]'.i18n::s('Second item')."\n".'[/list][/escape]</td>'
	.'<td>'.i18n::s('Another list with alphabetically numbered items:')."\n".'[list=A]'."\n".'[*]'.i18n::s('First item')."\n".'[*]'.i18n::s('Second item')."\n".'[/list]</td></tr>'
	.Skin::table_suffix();

// [list=i]...[/list]
$context['text'] .= '[title]'.i18n::s('Numbered list').' [escape][list=i]...[/list][/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('A list with roman numbers:')."\n".'[list=i]'."\n".'[*]'.i18n::s('First item')."\n".'[*]'.i18n::s('Second item')."\n".'[/list][/escape]</td>'
	.'<td>'.i18n::s('A list with roman numbers:')."\n".'[list=i]'."\n".'[*]'.i18n::s('First item')."\n".'[*]'.i18n::s('Second item')."\n".'[/list]</td></tr>'
	.Skin::table_suffix();

// [list=I]...[/list]
$context['text'] .= '[title]'.i18n::s('Numbered list').' [escape][list=I]...[/list][/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('Another list with roman numbers:')."\n".'[list=I]'."\n".'[*]'.i18n::s('First item')."\n".'[*]'.i18n::s('Second item')."\n".'[/list][/escape]</td>'
	.'<td>'.i18n::s('Another list with roman numbers:')."\n".'[list=I]'."\n".'[*]'.i18n::s('First item')."\n".'[*]'.i18n::s('Second item')."\n".'[/list]</td></tr>'
	.Skin::table_suffix();

// transform the text
$context['text'] = Codes::beautify($context['text']);

// general help on this page
$help = '<p>'.sprintf(i18n::s('Please note that actual rendering depends on the selected %s.'), Skin::build_link('skins/', i18n::s('skin'), 'shortcut')).'</p>';
$context['aside']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

// render the skin
render_skin();

?>