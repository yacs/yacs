<?php
/**
 * examples of formatting codes for titles
 *
 * Following codes are documented on this page:
 * - ==...==- level 1 header
 * - ===...=== - level 2 header
 * - ====...==== - level 3 header
 * - =====...===== - level 4 header
 * - ======...====== - level 5 header
 * - &#91;toc] - table of contents
 * - &#91;title]...[/title] - a level 1 headline, put in the table of contents
 * - &#91;subtitle]...[/subtitle] - a level 2 headline
 * - &#91;header1]...[/header1] - a level 1 headline
 * - &#91;header2]...[/header2] - a level 2 headline
 * - &#91;header3]...[/header3] - a level 3 headline
 * - &#91;header4]...[/header4] - a level 4 headline
 * - &#91;header5]...[/header5] - a level 5 headline
 * - &#91;toq] - the table of questions for this page
 * - &#91;question]...[/question] - a question-title
 * - &#91;question] - a simple question
 * - &#91;answer] - some answer in a FAQ
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
$context['page_title'] = i18n::s('Codes to format titles and questions');

// the date of last modification
if(Surfer::is_associate())
	$context['page_details'] .= '<p class="details">'.sprintf(i18n::s('Edited %s'), Skin::build_date(getlastmod())).'</p>';

// page header
$context['text'] .= '<p>'.i18n::s('On this page we are showing how to add headlines, questions, and related table of contents.').'</p>';

// add a toc
$context['text'] .= "\n".'[toc]'."\n";


// [toc]
$context['text'] .= '[title]'.i18n::s('Table of content').' [escape][toc][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][toc][/escape]</td>'
	.'<td>[toc]</td></tr>'
	.Skin::table_suffix();

// [title]...[/title]
$context['text'] .= '[title]'.i18n::s('Title').' [escape][title]...[/title][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape][nl][nl]'
		.'[escape][title]'.i18n::s('Rabbit and Turtle').'[/title][/escape][nl]'
		.'[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape]</td>'
	.'<td>'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[nl]'
		.'[title]'.i18n::s('Rabbit and Turtle').'[/title]'
		.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'</td></tr>'
	.Skin::table_suffix();

// [subtitle]...[/subtitle]
$context['text'] .= '[title]'.i18n::s('Subtitle').' [escape][subtitle]...[/subtitle][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape][nl][nl]'
		.'[escape][subtitle]'.i18n::s('Rabbit and Turtle').'[/subtitle][/escape][nl]'
		.'[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape]</td>'
	.'<td>'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[nl]'
		.'[subtitle]'.i18n::s('Rabbit and Turtle').'[/subtitle]'
		.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'</td></tr>'
	.Skin::table_suffix();

// ==...==
$context['text'] .= '[title]'.i18n::s('Header level 1').' [escape]==...==[/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape][nl]'
		.'[escape]=='.i18n::s('Rabbit and Turtle').'==[/escape][nl]'
		.'[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape]</td>'
	.'<td>'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...')."\n"
		.'=='.i18n::s('Rabbit and Turtle').'=='."\n"
		.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'</td></tr>'
	.Skin::table_suffix();

// [header1]...[/header1]
$context['text'] .= '[title]'.i18n::s('Header level 1').' [escape][header1]...[/header1][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape][nl][nl]'
		.'[escape][header1]'.i18n::s('Rabbit and Turtle').'[/header1][/escape][nl]'
		.'[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape]</td>'
	.'<td>'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[nl]'
		.'[header1]'.i18n::s('Rabbit and Turtle').'[/header1]'
		.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'</td></tr>'
	.Skin::table_suffix();

// ===...===
$context['text'] .= '[title]'.i18n::s('Header level 2').' [escape]===...===[/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape][nl]'
		.'[escape]==='.i18n::s('Rabbit and Turtle').'===[/escape][nl]'
		.'[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape]</td>'
	.'<td>'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...')."\n"
		.'==='.i18n::s('Rabbit and Turtle').'==='."\n"
		.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'</td></tr>'
	.Skin::table_suffix();

// [header2]...[/header2]
$context['text'] .= '[title]'.i18n::s('Header level 2').' [escape][header2]...[/header2][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape][nl][nl]'
		.'[escape][header2]'.i18n::s('Rabbit and Turtle').'[/header2][/escape][nl]'
		.'[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape]</td>'
	.'<td>'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[nl]'
		.'[header2]'.i18n::s('Rabbit and Turtle').'[/header2]'
		.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'</td></tr>'
	.Skin::table_suffix();

// ====...====
$context['text'] .= '[title]'.i18n::s('Header level 3').' [escape]====...====[/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape][nl]'
		.'[escape]===='.i18n::s('Rabbit and Turtle').'====[/escape][nl]'
		.'[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape]</td>'
	.'<td>'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...')."\n"
		.'===='.i18n::s('Rabbit and Turtle').'===='."\n"
		.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'</td></tr>'
	.Skin::table_suffix();

// [header3]...[/header3]
$context['text'] .= '[title]'.i18n::s('Header level 3').' [escape][header3]...[/header3][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape][nl][nl]'
		.'[escape][header3]'.i18n::s('Rabbit and Turtle').'[/header3][/escape][nl]'
		.'[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape]</td>'
	.'<td>'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[nl]'
		.'[header3]'.i18n::s('Rabbit and Turtle').'[/header3]'
		.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'</td></tr>'
	.Skin::table_suffix();

// =====...=====
$context['text'] .= '[title]'.i18n::s('Header level 4').' [escape]=====...=====[/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape][nl]'
		.'[escape]====='.i18n::s('Rabbit and Turtle').'=====[/escape][nl]'
		.'[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape]</td>'
	.'<td>'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...')."\n"
		.'====='.i18n::s('Rabbit and Turtle').'====='."\n"
		.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'</td></tr>'
	.Skin::table_suffix();

// [header4]...[/header4]
$context['text'] .= '[title]'.i18n::s('Header level 4').' [escape][header4]...[/header4][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape][nl][nl]'
		.'[escape][header4]'.i18n::s('Rabbit and Turtle').'[/header4][/escape][nl]'
		.'[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape]</td>'
	.'<td>'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[nl]'
		.'[header4]'.i18n::s('Rabbit and Turtle').'[/header4]'
		.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'</td></tr>'
	.Skin::table_suffix();

// ======...======
$context['text'] .= '[title]'.i18n::s('Header level 5').' [escape]======...======[/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape][nl]'
		.'[escape]======'.i18n::s('Rabbit and Turtle').'======[/escape][nl]'
		.'[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape]</td>'
	.'<td>'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...')."\n"
		.'======'.i18n::s('Rabbit and Turtle').'======'."\n"
		.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'</td></tr>'
	.Skin::table_suffix();

// [header5]...[/header5]
$context['text'] .= '[title]'.i18n::s('Header level 5').' [escape][header5]...[/header5][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape][nl][nl]'
		.'[escape][header5]'.i18n::s('Rabbit and Turtle').'[/header5][/escape][nl]'
		.'[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape]</td>'
	.'<td>'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[nl]'
		.'[header5]'.i18n::s('Rabbit and Turtle').'[/header5]'
		.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'</td></tr>'
	.Skin::table_suffix();

// [toq]
$context['text'] .= '[title]'.i18n::s('Table of Questions').' [escape][toq][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][toq][/escape]</td>'
	.'<td>[toq]</td></tr>'
	.Skin::table_suffix();

// [question]...[/question]
$context['text'] .= '[title]'.i18n::s('Question-title').' [escape][question]...[/question][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][question]'.i18n::s('But where will this rabbit be in some minutes?').'[/question][/escape]</td>'
	.'<td>[question]'.i18n::s('But where will this rabbit be in some minutes?').'[/question]</td></tr>'
	.Skin::table_suffix();

// [question]
$context['text'] .= '[title]'.i18n::s('Question').' [escape][question][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][question]'.i18n::s('But where is this rabbit right now?').'[/escape]</td>'
	.'<td>[question]'.i18n::s('But where is this rabbit right now?').'</td></tr>'
	.Skin::table_suffix();

// [answer]
$context['text'] .= '[title]'.i18n::s('Answer').' [escape][answer][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][answer]'.i18n::s('I do not know, but it depends also on the turtle speed...').'[/escape]</td>'
	.'<td>[answer]'.i18n::s('I do not know, but it depends also on the turtle speed...').'</td></tr>'
	.Skin::table_suffix();

// transform the text
$context['text'] = Codes::beautify($context['text']);

// general help on this page
$help = '<p>'.sprintf(i18n::s('Please note that actual rendering depends on the selected %s.'), Skin::build_link('skins/', i18n::s('skin'), 'shortcut')).'</p>';
$context['extra'] .= Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

// render the skin
render_skin();

?>