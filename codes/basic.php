<?php
/**
 * examples of in-line formatting codes
 *
 * Following codes are documented on this page:
 * - **...** - bold text
 * - &#91;b]...[/b] - bold text
 * - //...// - italics
 * - &#91;i]...[/i] - italics
 * - __...__ - underlined
 * - &#91;u]...[/u] - underlined
 * - ##...## - monospace
 * - &#91;code]...[/code] - a short sample of fixed-size text (e.g. a file name)
 * - &#91;color]...[/color] - change font color
 * - &#91;tiny]...[/tiny] - tiny size
 * - &#91;small]...[/small] - small size
 * - &#91;big]...[/big] - big size
 * - &#91;huge]...[/huge] - huge size
 * - &#91;subscript]...[/subscript] - subscript
 * - &#91;superscript]...[/superscript] - superscript
 * - ++...++ - inserted
 * - &#91;inserted]...[/inserted] - inserted
 * - --...-- - deleted
 * - &#91;deleted]...[/deleted] - deleted
 * - &#91;flag]...[/flag] - draw attention
 * - &#91;lang=xy]...[/lang] - show some text only on matching language
 * - &#91;style=sans-serif]...[/style] - use a sans-serif font
 * - &#91;style=serif]...[/style] - use a serif font
 * - &#91;style=cursive]...[/style] - mimic hand writing
 * - &#91;style=comic]...[/style] - make it funny
 * - &#91;style=fantasy]...[/style] - guess what will appear
 * - &#91;style=my_style]...[/style] - translated to &lt;span class="my_style"&gt;...&lt;/span&gt;
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
$context['page_title'] = i18n::s('In-line formatting codes');

// the date of last modification
if(Surfer::is_associate())
	$context['page_details'] .= '<p class="details">'.sprintf(i18n::s('Edited %s'), Skin::build_date(getlastmod())).'</p>';

// page header
$context['text'] .= '<p>'.i18n::s('On this page we are introducing some formatting codes and live examples of utilization.').'</p>';

// add a toc
$context['text'] .= "\n".'[toc]'."\n";

// **...**
$context['text'] .= '[title]'.i18n::s('Wiki bold').' [escape]**...**[/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('This is very **important**, isn\'t it?').'[/escape]</td>'
	.'<td>'.i18n::s('This is very **important**, isn\'t it?').'</td></tr>'
	.Skin::table_suffix();


// [b]...[/b]
$context['text'] .= '[title]'.i18n::s('Bold').' [escape][b]...[/b][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('This is very [b]important[/b], isn\'t it?').'[/escape]</td>'
	.'<td>'.i18n::s('This is very [b]important[/b], isn\'t it?').'</td></tr>'
	.Skin::table_suffix();

// //...//
$context['text'] .= '[title]'.i18n::s('Wiki italics').'  [escape]//...//[/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('This is really //meaningful//!').'[/escape]</td>'
	.'<td>'.i18n::s('This is really //meaningful//!').'</td></tr>'
	.Skin::table_suffix();

// [i]...[/i]
$context['text'] .= '[title]'.i18n::s('Italics').'	[escape][i]...[/i][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('This is really [i]meaningful[/i]!').'[/escape]</td>'
	.'<td>'.i18n::s('This is really [i]meaningful[/i]!').'</td></tr>'
	.Skin::table_suffix();

// __...__
$context['text'] .= '[title]'.i18n::s('Wiki underline').'  [escape]__...__[/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('I would like to __insist__ on this point...').'[/escape]</td>'
	.'<td>'.i18n::s('I would like to __insist__ on this point...').'</td></tr>'
	.Skin::table_suffix();

// [u]...[/u]
$context['text'] .= '[title]'.i18n::s('Underlined').'  [escape][u]...[/u][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('I would like to [u]insist[/u] on this point...').'[/escape]</td>'
	.'<td>'.i18n::s('I would like to [u]insist[/u] on this point...').'</td></tr>'
	.Skin::table_suffix();

// ##...##
$context['text'] .= '[title]'.i18n::s('Wiki monospace').'  [escape]##...##[/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('Type ##cwd ../foo/bar## to visit an interesting directory.').'[/escape]</td>'
	.'<td>'.i18n::s('Type ##cwd ../foo/bar## to visit an interesting directory.').'</td></tr>'
	.Skin::table_suffix();

// [code]...[/code]
$context['text'] .= '[title]'.i18n::s('Code').'  [escape][code]...[/code][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('Type [code]cwd ../foo/bar[/code] to visit an interesting directory.').'[/escape]</td>'
	.'<td>'.i18n::s('Type [code]cwd ../foo/bar[/code] to visit an interesting directory.').'</td></tr>'
	.Skin::table_suffix();

// [color]...[/color]
$context['text'] .= '[title]'.i18n::s('Color').'  [escape][color]...[/color][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('Here is some text in [color=red]red[/color] and some text in [color=green]green[/color]').'[/escape]</td>'
	.'<td>'.i18n::s('Here is some text in [color=red]red[/color] and some text in [color=green]green[/color]').'</td></tr>'
	.Skin::table_suffix();

// [tiny]...[/tiny]
$context['text'] .= '[title]'.i18n::s('Tiny').' [escape][tiny]...[/tiny][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('This is really [tiny]tiny![/tiny]').'[/escape]</td>'
	.'<td>'.i18n::s('This is really [tiny]tiny![/tiny]').'</td></tr>'
	.Skin::table_suffix();

// [small]...[/small]
$context['text'] .= '[title]'.i18n::s('Small').' [escape][small]...[/small][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('This is really [small]small![/small]').'[/escape]</td>'
	.'<td>'.i18n::s('This is really [small]small![/small]').'</td></tr>'
	.Skin::table_suffix();

// [big]...[/big]
$context['text'] .= '[title]'.i18n::s('Big').' [escape][big]...[/big][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('This is really [big]meaningful[/big]').'[/escape]</td>'
	.'<td>'.i18n::s('This is really [big]meaningful[/big]').'</td></tr>'
	.Skin::table_suffix();

// [huge]...[/huge]
$context['text'] .= '[title]'.i18n::s('Huge').' [escape][huge]...[/huge][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('I would like to [huge]insist[/huge] on this point...').'[/escape]</td>'
	.'<td>'.i18n::s('I would like to [huge]insist[/huge] on this point...').'</td></tr>'
	.Skin::table_suffix();

// [superscript]...[/superscript]
$context['text'] .= '[title]'.i18n::s('Superscript').' [escape][superscript]...[/superscript][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('This text is [superscript]superscript[/superscript]. Interesting...').'[/escape]</td>'
	.'<td>'.i18n::s('This text is [superscript]superscript[/superscript]. Interesting...').'</td></tr>'
	.Skin::table_suffix();

// [subscript]...[/subscript]
$context['text'] .= '[title]'.i18n::s('Subscript').' [escape][subscript]...[/subscript][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('This text is [subscript]subscript[/subscript]. Interesting...').'[/escape]</td>'
	.'<td>'.i18n::s('This text is [subscript]subscript[/subscript]. Interesting...').'</td></tr>'
	.Skin::table_suffix();

// ++...++
$context['text'] .= '[title]'.i18n::s('Wiki insertion').' [escape]++...++[/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('This text ++has been inserted++.').'[/escape]</td>'
	.'<td>'.i18n::s('This text ++has been inserted++.').'</td></tr>'
	.Skin::table_suffix();

// [inserted]...[/inserted]
$context['text'] .= '[title]'.i18n::s('Inserted').' [escape][inserted]...[/inserted][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('This text [inserted]has been inserted[/inserted].').'[/escape]</td>'
	.'<td>'.i18n::s('This text [inserted]has been inserted[/inserted].').'</td></tr>'
	.Skin::table_suffix();

// --...--
$context['text'] .= '[title]'.i18n::s('Wiki deletion').' [escape]--...--[/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('This text --has been deleted--.').'[/escape]</td>'
	.'<td>'.i18n::s('This text --has been deleted--.').'</td></tr>'
	.Skin::table_suffix();

// [deleted]...[/deleted]
$context['text'] .= '[title]'.i18n::s('Deleted').' [escape][deleted]...[/deleted][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('This text [deleted]has been deleted[/deleted].').'[/escape]</td>'
	.'<td>'.i18n::s('This text [deleted]has been deleted[/deleted].').'</td></tr>'
	.Skin::table_suffix();

// [flag]...[/flag]
$context['text'] .= '[title]'.i18n::s('Flag').' [escape][flag]...[/flag][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('[flag]important![/flag] Don\'t forget to give something to your cat today.').'[/escape]</td>'
	.'<td>'.i18n::s('[flag]important![/flag] Don\'t forget to give something to your cat today.').'</td></tr>'
	.Skin::table_suffix();

// [lang=xy]...[/lang]
$context['text'] .= '[title]'.i18n::s('Language').' [escape][lang=xy]...[/lang][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][lang=en]This is in English[/lang][lang=fr]Ceci est en fran&ccedil;ais[/lang][/escape]</td>'
	.'<td>[lang=en]This is in English[/lang][lang=fr]Ceci est en fran&ccedil;ais[/lang]</td></tr>'
	.Skin::table_suffix();

// [style=serif]...[/style]
$context['text'] .= '[title]'.i18n::s('Serif').' [escape][style=serif]...[/style][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][style=serif]'.i18n::s('This text is in Serif.').'[/style][/escape]</td>'
	.'<td>[style=serif]'.i18n::s('This text is in Serif.').'[/style]</td></tr>'
	.Skin::table_suffix();

// [style=sans-serif]...[/style]
$context['text'] .= '[title]'.i18n::s('Sans-Serif').' [escape][style=sans-serif]...[/style][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][style=sans-serif]'.i18n::s('This text is in Sans-Serif.').'[/style][/escape]</td>'
	.'<td>[style=sans-serif]'.i18n::s('This text is in Sans-Serif.').'[/style]</td></tr>'
	.Skin::table_suffix();

// [style=cursive]...[/style]
$context['text'] .= '[title]'.i18n::s('Cursive').' [escape][style=cursive]...[/style][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][style=cursive]'.i18n::s('This text is in Cursive.').'[/style][/escape]</td>'
	.'<td>[style=cursive]'.i18n::s('This text is in Cursive.').'[/style]</td></tr>'
	.Skin::table_suffix();

// [style=fantasy]...[/style]
$context['text'] .= '[title]'.i18n::s('Fantasy').' [escape][style=fantasy]...[/style][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][style=fantasy]'.i18n::s('This text is in Fantasy.').'[/style][/escape]</td>'
	.'<td>[style=fantasy]'.i18n::s('This text is in Fantasy.').'[/style]</td></tr>'
	.Skin::table_suffix();

// [style=comic]...[/style]
$context['text'] .= '[title]'.i18n::s('Comic').' [escape][style=comic]...[/style][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][style=comic]'.i18n::s('This text is in Comic.').'[/style][/escape]</td>'
	.'<td>[style=comic]'.i18n::s('This text is in Comic.').'[/style]</td></tr>'
	.Skin::table_suffix();

// [style]...[/style]
$context['text'] .= '[title]'.i18n::s('Use any style').' [escape][style=&lt;style name&gt;]...[/style][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][style=my_style]'.i18n::s('But where will this rabbit be in some minutes? I don\'t know, but it depends also on the turtle speed...').'[/style][/escape]</td>'
	.'<td>[style=my_style]'.i18n::s('But where will this rabbit be in some minutes? I don\'t know, but it depends also on the turtle speed...').'[/style]</td></tr>'
	.Skin::table_suffix();

// transform the text
$context['text'] = Codes::beautify($context['text']);

// general help on this page
$help = '<p>'.sprintf(i18n::s('Please note that actual rendering depends on the selected %s.'), Skin::build_link('skins/', i18n::s('skin'), 'shortcut')).'</p>';
$context['extra'] .= Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

// render the skin
render_skin();

?>