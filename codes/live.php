<?php
/**
 * examples of live formatting codes
 *
 * Following codes are documented on this page:
 * - &#91;cloud] - the tags used at this site
 * - &#91;cloud=12] - the tags used at this site
 * - &#91;locations=all] - newest locations
 * - &#91;locations=users] - map user locations on Google maps
 * - &#91;location=latitude, longitude, label] - to build a map on-the-fly
 * - &#91;collections] - list available collections
 * - &#91;read] - most read articles, in a compact list
 * - &#91;read=section:&lt;id>] - articles of fame in the given section
 * - &#91;published] - most recent published pages, in a compact list
 * - &#91;published=section:&lt;id>] - articles published most recently in the given section
 * - &#91;published=category:&lt;id>] - articles published most recently in the given category
 * - &#91;published=user:&lt;id>] - articles published most recently created by given user
 * - &#91;edited] - most recent edited pages, in a compact list
 * - &#91;edited=section:&lt;id>] - articles edited most recently in the given section
 * - &#91;edited=category:&lt;id>] - articles edited most recently in the given category
 * - &#91;edited=user:&lt;id>] - articles edited most recently created by given user
 * - &#91;commented] - most fresh threads, in a compact list
 * - &#91;commented=section:&lt;id>] - articles commented most recently in the given section
 * - &#91;contributed] - most contributed articles, in a compact list
 * - &#91;contributed=section:&lt;id>] - most contributed articles in the given section
 * - &#91;freemind] - a Freemind map of site content
 * - &#91;freemind=section:&lt;id>] - a Freemind map of a section and its content
 *
 * @see codes/index.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load the skin
load_skin('codes');

// the path to this page
$context['path_bar'] = array( 'help.php' => i18n::s('Help index'),
	'codes/' => i18n::s('Formatting Codes') );

// the title of the page
$context['page_title'] = i18n::s('Live codes');

// the date of last modification
if(Surfer::is_associate())
	$context['page_details'] .= '<p class="details">'.sprintf(i18n::s('Edited %s'), Skin::build_date(getlastmod())).'</p>';

// page header
$context['text'] .= '<p>'.i18n::s('On this page we are introducing codes related to dynamic queries of site content.').'</p>';

// add a toc
$context['text'] .= "\n".'[toc]'."\n";

// [cloud]
$context['text'] .= '[title]'.i18n::s('Cloud of tags').' [escape][cloud] or [cloud=&lt;40&gt;][/escape][/title]'
	.i18n::s('Use the parameter to adjust the number of tags listed.')
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][cloud][/escape]</td>'
	.'<td>[cloud]</td></tr>'
	.Skin::table_suffix();

// [locations=all]
$context['text'] .= '[title]'.i18n::s('Newest locations').' [escape][locations=all][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][locations=all][/escape]</td>'
	.'<td>[locations=all]</td></tr>'
	.Skin::table_suffix();

// [locations=users]
$context['text'] .= '[title]'.i18n::s('Newest locations').' [escape][locations=users][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][locations=users][/escape]</td>'
	.'<td>[locations=users]</td></tr>'
	.Skin::table_suffix();

// [location=latitude, longitude, label]
$context['text'] .= '[title]'.i18n::s('Direct location').' [escape][location=latitude, longitude, label][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][location=48.871264, 2.307558, Paris][/escape]</td>'
	.'<td>[location=48.871264, 2.307558, Paris]</td></tr>'
	.Skin::table_suffix();

// [collections]
$context['text'] .= '[title]'.i18n::s('List of collections').' [escape][collections][/escape][/title]'
	.i18n::s('Use the configuration panel for collections to create new collections of files.')
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][collections][/escape]</td>'
	.'<td>[collections]</td></tr>'
	.Skin::table_suffix();

// [read]
$context['text'] .= '[title]'.i18n::s('Most read articles').' [escape][read] or [read=section:&lt;id&gt;][/escape][/title]'
	.i18n::s('Use the simplest form to display a compact list of articles of fame, or limit the scope to one section and related sub-sections.')
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][read][/escape]</td>'
	.'<td>[read]</td></tr>'
	.Skin::table_suffix();

// [published]
$context['text'] .= '[title]'.i18n::s('Most recent publications').' [escape][published] or [published=section:&lt;id&gt;][/escape][/title]'
	.i18n::s('Use the simplest form to display a compact list of newest publications, or limit the scope to: one section and related sub-sections, one category, or one author.')
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][published][/escape]</td>'
	.'<td>[published]</td></tr>'
	.Skin::table_suffix();

// [edited]
$context['text'] .= '[title]'.i18n::s('Most recent updates').' [escape][edited] or [edited=section:&lt;id&gt;][/escape][/title]'
	.i18n::s('Use the simplest form to display a compact list of edited pages, or limit the scope to : one section and related sub-sections, one category, or one author.')
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][edited][/escape]</td>'
	.'<td>[edited]</td></tr>'
	.Skin::table_suffix();

// [commented]
$context['text'] .= '[title]'.i18n::s('Freshest threads').' [escape][commented] or [commented=section:&lt;id&gt;][/escape][/title]'
	.i18n::s('Use the simplest form to display a compact list of freshest threads, or limit the scope to one section and related sub-sections.')
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][commented][/escape]</td>'
	.'<td>[commented]</td></tr>'
	.Skin::table_suffix();

// [contributed]
$context['text'] .= '[title]'.i18n::s('Most active pages').' [escape][contributed] or [contributed=section:&lt;id&gt;][/escape][/title]'
	.i18n::s('Use the simplest form to display a compact list of most contributed pages, or limit the scope to one section and related sub-sections.')
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][contributed][/escape]</td>'
	.'<td>[contributed]</td></tr>'
	.Skin::table_suffix();

// [freemind]
$context['text'] .= '[title]'.i18n::s('A dynamic Freemind map').' [escape][freemind] or [freemind=section:&lt;id&gt;][/escape][/title]'
	.i18n::s('Use the simplest form to navigate site content is a mind map, or limit the scope to one section and related sub-sections.')
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][freemind=100%, 300px][/escape]</td>'
	.'<td>[freemind=100%, 300px]</td></tr>'
	.Skin::table_suffix();

// transform the text
$context['text'] = Codes::beautify($context['text']);

// general help on this page
$help = '<p>'.sprintf(i18n::s('Please note that actual rendering depends on the selected %s.'), Skin::build_link('skins/', i18n::s('skin'), 'shortcut')).'</p>';
$context['extra'] .= Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

// render the skin
render_skin();

?>