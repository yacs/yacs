<?php
/**
 * examples of formatting codes for tables
 *
 * Following codes are documented on this page:
 * - &#91;table]...&#91;/table]
 * - &#91;table=grid]...&#91;/table]
 * - &#91;table]...&#91;body]...&#91;/table]
 * - &#91;table]...&#91;csv]...&#91;/csv]...&#91;/table]
 *
 * @see codes/index.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @tester Egide
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
$context['path_bar'] = array( 'help.php' => i18n::s('Help index'),
	'codes/' => i18n::s('Formatting Codes') );

// the title of the page
$context['page_title'] = i18n::s('Codes to format tables');

// the date of last modification
if(Surfer::is_associate())
	$context['page_details'] .= '<p class="details">'.sprintf(i18n::s('Edited %s'), Skin::build_date(getlastmod())).'</p>';

// page header
$context['text'] .= '<p>'.i18n::s('On this page we are showing how to build simple tables. Use the char |, or tab, or two successive spaces, to separate column elements.').'</p>';

// add a toc
$context['text'] .= "\n".'[toc]'."\n";


// [table]...[/table]
$context['text'] .=  '[title]'.i18n::s('Table').' [escape][table]...[/table][/escape][/title]'
	.'<p>'.i18n::s('In this example cells are separated by tabulation characters or by 2 spaces.').'</p>'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][table]'."\n"
		.i18n::s('Rabbit')."\t".i18n::s('Turtle')."\n"
		.i18n::s('Stories').'  '.i18n::s('Jean[nl]de la Fontaine')."\n"
		.'[/table][/escape]</td>'
	.'<td>[table]'."\n"
		.i18n::s('Rabbit')."\t".i18n::s('Turtle')."\n"
		.i18n::s('Stories').'  '.i18n::s('Jean[nl]de la Fontaine')."\n"
		.'[/table]</td></tr>'
	.Skin::table_suffix();

// [table=grid]...[/table]
$context['text'] .= '[title]'.i18n::s('Table').' [escape][table=grid]...[/table][/escape][/title]'
	.'<p>'.i18n::s('In this example cells are separated by the | character.').'</p>'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][table=grid]'."\n"
		.i18n::s('Rabbit|Turtle')."\n"
		.i18n::s('Stories|Jean[nl]de la Fontaine')."\n"
		.'[/table][/escape]</td>'
	.'<td>[table=grid]'."\n"
		.i18n::s('Rabbit|Turtle')."\n"
		.i18n::s('Stories|Jean[nl]de la Fontaine')."\n"
		.'[/table]</td></tr>'
	.Skin::table_suffix();

// [table=tiny]...[/table]
$context['text'] .= '[title]'.i18n::s('Table').' [escape][table=tiny]...[/table][/escape][/title]'
	.'<p>Actually any style can be applied to the generated table.</p>'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][table=tiny]'."\n"
		.i18n::s('Rabbit|Turtle')."\n"
		.i18n::s('Stories|Jean[nl]de la Fontaine')."\n"
		.'[/table][/escape]</td>'
	.'<td>[table=tiny]'."\n"
		.i18n::s('Rabbit|Turtle')."\n"
		.i18n::s('Stories|Jean[nl]de la Fontaine')."\n"
		.'[/table]</td></tr>'
	.Skin::table_suffix();

// [table]...[body]...[/table]
$context['text'] .= '[title]'.i18n::s('Separate headers from the body').' [escape][table]...[body]...[/table][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][table=grid]'."\n"
		.i18n::s('First Name|Last Name')."\n"
		.'[body]'."\n"
		.i18n::s('Rabbit|Turtle')."\n"
		.i18n::s('Stories|Jean[nl]de la Fontaine')."\n"
		.'[/table][/escape]</td>'
	.'<td>[table=grid]'."\n"
		.i18n::s('First Name|Last Name')."\n"
		.'[body]'."\n"
		.i18n::s('Rabbit|Turtle')."\n"
		.i18n::s('Stories|Jean[nl]de la Fontaine')."\n"
		.'[/table]</td></tr>'
	.Skin::table_suffix();

// cells alignment
$context['text'] .= '[title]'.i18n::s('Explicit cells alignment').'[/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][table=grid]'."\n"
		.'left='.i18n::s('Full Name').'| center='.i18n::s('Birth Year').'| right='.i18n::s('Net income')."\n"
		.'[body]'."\n"
		.'right='.i18n::s('Speedy Rabbit').'|center=1888|$11,230'."\n"
		.'center='.i18n::s('Jean de la Fontaine').'|center=1675|$234,567'."\n"
		.'[/table][/escape]</td>'
	.'<td>[table=grid]'."\n"
		.'left='.i18n::s('Full Name').'| center='.i18n::s('Birth Year').'| right='.i18n::s('Net income')."\n"
		.'[body]'."\n"
		.'right='.i18n::s('Speedy Rabbit').'|center=1888|$11,230'."\n"
		.'center='.i18n::s('Jean de la Fontaine').'|center=1675|$234,567'."\n"
		.'[/table]</td></tr>'
	.Skin::table_suffix();

// [table]...[csv]...[/csv]...[/table]
$context['text'] .= '[title]'.i18n::s('Comma-separated values').' [escape][table][csv]...[/csv][/table][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][table=grid]'."\n"
		.'[csv]'."\n"
		.i18n::s('Net income for the year,$2.310')."\n"
		.i18n::s('Net income for last year,$2.100')."\n"
		.i18n::s('Net income increase,10%')."\n"
		.'[/csv]'."\n"
		.'[/table][/escape]</td>'
	.'<td>[table=grid]'."\n"
		.'[csv]'."\n"
		.i18n::s('Net income for the year,$2.310')."\n"
		.i18n::s('Net income for last year,$2.100')."\n"
		.i18n::s('Net income increase,10%')."\n"
		.'[/csv]'."\n"
		.'[/table]</td></tr>'
	.Skin::table_suffix();

// [table]...[csv=;]...[/csv]...[/table]
$context['text'] .= '[title]'.i18n::s('Comma-separated values').' [escape][table][csv=;]...[/csv][/table][/escape][/title]'
	.'<p>'.i18n::s('Using a different separator between cells.').'</p>'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][table=grid]'."\n"
		.'[csv=;]'."\n"
		.i18n::s('Net income for the year;$2.310')."\n"
		.i18n::s('Net income for last year;$2.100')."\n"
		.i18n::s('Net income increase;10%')."\n"
		.'[/csv]'."\n"
		.'[/table][/escape]</td>'
	.'<td>[table=grid]'."\n"
		.'[csv=;]'."\n"
		.i18n::s('Net income for the year;$2.310')."\n"
		.i18n::s('Net income for last year;$2.100')."\n"
		.i18n::s('Net income increase;10%')."\n"
		.'[/csv]'."\n"
		.'[/table]</td></tr>'
	.Skin::table_suffix();

// transform the text
$context['text'] = Codes::beautify($context['text']);

// general help on this page
$help = '<p>'.sprintf(i18n::s('Please note that actual rendering depends on the selected %s.'), Skin::build_link('skins/', i18n::s('skin'), 'shortcut')).'</p>';
$context['extra'] .= Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

// render the skin
render_skin();

?>