<?php
/**
 * examples of miscellaneous formatting codes
 *
 * This page presents implicit formatting rules.
 *
 * Following codes are documented on this page:
 * - &#91;hint=&lt;help popup]...[/hint] - &lt;abbr tite="help popup">...&lt;/abbr>
 * - &#91;nl] - new line
 * - ----... - line break
 * - &#91;---] or &#91;___] - horizontal rule
 * - &#91;new] - something new
 * - &#91;popular] - people love it
 * - &#91;be] - country flag
 * - &#91;ca] - country flag
 * - &#91;ch] - country flag
 * - &#91;de] - country flag
 * - &#91;en] - country flag
 * - &#91;es] - country flag
 * - &#91;fr] - country flag
 * - &#91;gb] - country flag
 * - &#91;gr] - country flag
 * - &#91;it] - country flag
 * - &#91;pt] - country flag
 * - &#91;us] - country flag
 *
 * @see codes/index.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Canardo69
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
$context['page_title'] = i18n::s('Miscellaneous codes');

// the date of last modification
if(Surfer::is_associate())
	$context['page_details'] .= '<p class="details">'.sprintf(i18n::s('Edited %s'), Skin::build_date(getlastmod())).'</p>';

// page header
$context['text'] .= '<p>'.i18n::s('On this page we are introducing some formatting codes and live examples of utilization.').'</p>';

// add a toc
$context['text'] .= "\n".'[toc]'."\n";

// multiple lines
$chart_data = '{ "elements": [ 
	
	{ "type": "line", "values": [ 2, 2, 2, 6, 3, 4, 1, 3, 5 ], "dot-style": { "type": "dot", "dot-size": 5, "colour": "#DFC329" }, "width": 4, "colour": "#DFC329", "text": "Line 1", "font-size": 10 }, 
	
	{ "type": "line", "values": [ 7, 8, 8, 7, 11, 12, 13, 7, 12 ], "dot-style": { "type": "star", "dot-size": 5 }, "width": 1, "colour": "#6363AC", "text": "Line 2", "font-size": 10 }, 
	
	{ "type": "line", "values": [ 16, 17, 14, 17, 18, 18, 18, 16, 16 ], "width": 1, "colour": "#5E4725", "text": "Line 3", "font-size": 10 } ], 
	
	"title": { "text": "Three lines example" }, 
	"y_axis": { "min": 0, "max": 20, "steps": 5 } }';

// [chart]...[/chart]
$context['text'] .= '[title]'.i18n::s('Static chart').' [escape][chart]...[/chart][/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][chart=320, 256]'."\n".$chart_data."\n".'[/chart][/escape]</td>'
	.'<td>[chart=320, 256]'."\n".$chart_data."\n".'[/chart]</td></tr>'
	.Skin::table_suffix();

// a sketched chart
$chart_data = '{ "elements": [ { "type": "bar_sketch", "colour": "#81AC00", "outline-colour": "#567300", "offset": 5, "values": [ 6, 7, { "top": 3, "tip": "Hello #val#" }, 3, 4, { "top": 3, "tip": "Hello #val#" }, { "top": 3, "tip": "Hello #val#" }, 7, { "top": 3, "tip": "Hello #val#" }, { "top": 3, "tip": "Hello #val#" }, 10, 11 ] } ], 
"title": { "text": "Wed Aug 26 2009", "style": "{color: #567300; font-size: 14px}" } }';
	
// [chart]...[/chart]
$context['text'] .= '[title]'.i18n::s('Static chart').' [escape][chart]...[/chart][/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][chart=320, 256]'."\n".$chart_data."\n".'[/chart][/escape]</td>'
	.'<td>[chart=320, 256]'."\n".$chart_data."\n".'[/chart]</td></tr>'
	.Skin::table_suffix();

// a glassy barchart
$chart_data = '{"elements":[{"type":"bar_glass","values":[9,8,7,6,5,4,3,2,1]}],"title":{"text":"Wed Jan 21 2009"}}';

// [chart]...[/chart]
$context['text'] .= '[title]'.i18n::s('Static chart').' [escape][chart]...[/chart][/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][chart=320, 256]'."\n".$chart_data."\n".'[/chart][/escape]</td>'
	.'<td>[chart=320, 256]'."\n".$chart_data."\n".'[/chart]</td></tr>'
	.Skin::table_suffix();

// a radar chart
$chart_data = '{ "elements": [ 

{ "type": "line", "values": [ 3, 4, 5, 4, 3, 3, 2.5 ], "dot-style": { "type": "hollow-dot", "colour": "#FBB829", "dot-size": 4 }, "width": 1, "colour": "#FBB829", "tip": "Gold #val#", "text": "Mr Gold", "font-size": 10 }, 

{ "type": "line", "values": [ 2, 2, 2, 2, 2, 2, 2 ], "dot-style": { "type": "star", "colour": "#8000FF", "dot-size": 4 }, "width": 1, "colour": "#8000FF", "tip": "Purple #val#", "text": "Mr Purple", "font-size": 10, "loop": true } ], 

"title": { "text": "Radar Chart" }, 

"radar_axis": { "max": 5, "colour": "#DAD5E0", "grid-colour": "#DAD5E0", "labels": { "labels": [ "Zero", "", "", "Middle", "", "High" ], "colour": "#9F819F" }, "spoke-labels": { "labels": [ "Strength", "Smarts", "Sweet Tooth", "Armour", "Max Hit Points", "foo", "bar" ], "colour": "#9F819F" } }, "tooltip": { "mouse": 1 }, 

"bg_colour": "#ffffff" }';

// [chart]...[/chart]
$context['text'] .= '[title]'.i18n::s('Static chart').' [escape][chart]...[/chart][/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][chart=320, 256]'."\n".$chart_data."\n".'[/chart][/escape]</td>'
	.'<td>[chart=320, 256]'."\n".$chart_data."\n".'[/chart]</td></tr>'
	.Skin::table_suffix();

// implicit formating
$context['text'] .= '[title]'.i18n::s('Implicit formatting').'[/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('hello')."\n"
	.i18n::s('world')."\n"
	."\n"
	.i18n::s('how are')."\n"
	.i18n::s('you doing?')."\n"
	.'----'."\n"
	."\n"
	.'- '.i18n::s('http://www.cisco.com')."\n"
	.'- '.i18n::s('one bulleted item')."\n"
	."\n"
	.'- '.i18n::s('another one')."\n"
	.'- '.i18n::s('www.php.net')."\n"
	."\n"
	.'* '.i18n::s('foo.bar@foo.com')."\n"
	."\n"
	.i18n::s('this one - should not create bullets. not one, nor two')."\n"
	."\n"
	.' > '.i18n::s('quoted from')."\n"
	.' | '.i18n::s('a previous message').'[/escape]</td>'."\n"
	.'<td>'.i18n::s('hello')."\n"
	.i18n::s('world')."\n"
	."\n"
	.i18n::s('how are')."\n"
	.i18n::s('you doing?')."\n"
	.'----'."\n"
	."\n"
	.'- '.i18n::s('http://www.cisco.com')."\n"
	.'- '.i18n::s('one bulleted item')."\n"
	."\n"
	.'- '.i18n::s('another one')."\n"
	.'- '.i18n::s('www.php.net')."\n"
	."\n"
	.'* '.i18n::s('foo.bar@foo.com')."\n"
	."\n"
	.i18n::s('this one - should not create bullets. not one, nor two')."\n"
	."\n"
	.' > '.i18n::s('quoted from')."\n"
	.' | '.i18n::s('a previous message').'</td></tr>'."\n"
	.Skin::table_suffix();

// [hint=help]...[/hint]
$context['text'] .= '[title]'.i18n::s('Hint').' [escape][hint=&lt;help popup&gt;]...[/hint][/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('YACS is based on international standards, including [hint=eXtended Markup Language]XML[/hint].').'[/escape]</td>'
	.'<td>'.i18n::s('YACS is based on international standards, including [hint=eXtended Markup Language]XML[/hint].').'</td></tr>'
	.Skin::table_suffix();

// [nl]
$context['text'] .= '[title]'.i18n::s('Newline').' [escape]...[nl]...[/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'[/escape]</td>'
	.'<td>'.i18n::s('But where will this rabbit be in some minutes?[nl]I don\'t know, but it depends also on the turtle speed...').'</td></tr>'
	.Skin::table_suffix();

// [---]
$context['text'] .= '[title]'.i18n::s('Ruler').' [escape]...[---] or [___]...[/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('But where will this rabbit be in some minutes?[---]I don\'t know, but it depends also on the turtle speed...').'[/escape]</td>'
	.'<td>'.i18n::s('But where will this rabbit be in some minutes?[---]I don\'t know, but it depends also on the turtle speed...').'</td></tr>'
	.Skin::table_suffix();

// [new]
$context['text'] .= '[title]'.i18n::s('New').' [escape]...[new]...[/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('Our newsletter has been published!').' [new][/escape]</td>'
	.'<td>'.i18n::s('Our newsletter has been published!').' [new]</td></tr>'
	.Skin::table_suffix();

// [popular]
$context['text'] .= '[title]'.i18n::s('Popular').' [escape]...[popular]...[/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('Numerous surfers like this section of our site').' [popular][/escape]</td>'
	.'<td>'.i18n::s('Numerous surfers like this section of our site').' [popular]</td></tr>'
	.Skin::table_suffix();

// flags
$context['text'] .= '[title]'.i18n::s('Flags').' [escape][be] [ca] [ch] [de] [en] [es] [fr] [gb] [gr] [it] [pt] [us][/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][be] [ca] [ch] [de] [en] [es] [fr] [gb] [gr] [it] [pt] [us][/escape]</td>'
	.'<td>[be] [ca] [ch] [de] [en] [es] [fr] [gb] [gr] [it] [pt] [us]</td></tr>'
	.Skin::table_suffix();

// transform the text
$context['text'] = Codes::beautify($context['text']);

// general help on this page
$help = '<p>'.sprintf(i18n::s('Please note that actual rendering depends on the selected %s.'), Skin::build_link('skins/', i18n::s('skin'), 'shortcut')).'</p>';
$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

// render the skin
render_skin();

?>