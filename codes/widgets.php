<?php
/**
 * examples of widgets
 *
 * Following codes are documented on this page:
 * - &#91;newsfeed=url] - integrate a newsfeed dynamically
 * - &#91;twitter=id] - twitter updates of one person
 * - &#91;tsearch=token] - twitter search on a given topic
 * - &#91;freemind] - a Freemind map of site content
 * - &#91;freemind=section:&lt;id>] - a Freemind map of a section and its content
 * - &#91;freemind=section:&lt;id>, width, height] - a Freemind map of a section and its content
 * - &#91;cloud] - the tags used at this site
 * - &#91;cloud=12] - maximum count of tags used at this site
 * - &#91;calendar] - events for this month
 * - &#91;calendar=section:&lt;id>] - dates in one section
 * - &#91;locations=all] - newest locations
 * - &#91;locations=users] - map user locations on Google maps
 * - &#91;location=latitude, longitude, label] - to build a dynamic map
 *
 * @see codes/index.php
 *
 * @author Bernard Paques
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
$context['page_title'] = i18n::s('Widgets');

// the date of last modification
if(Surfer::is_associate())
	$context['page_details'] .= '<p class="details">'.sprintf(i18n::s('Edited %s'), Skin::build_date(getlastmod())).'</p>';

// page header
$context['text'] .= '<p>'.i18n::s('On this page we are introducing codes related to widgets and badges.').'</p>';

// add a toc
$context['text'] .= "\n".'[toc]'."\n";

// [newsfeed]
$context['text'] .= '[title]'.i18n::s('Newsfeed').' [escape][newsfeed=&lt;url&gt;][/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][newsfeed=http://www.yacs.fr/feeds/rss.php][/escape]</td>'
	.'<td>[newsfeed=http://www.yacs.fr/feeds/rss.php]</td></tr>'
	.Skin::table_suffix();

// [newsfeed.embed]
$context['text'] .= '[title]'.i18n::s('Newsfeed').' [escape][newsfeed.embed=&lt;url&gt;][/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][newsfeed.embed=http://www.yacs.fr/feeds/rss.php][/escape]</td>'
	.'<td>[newsfeed.embed=http://www.yacs.fr/feeds/rss.php]</td></tr>'
	.Skin::table_suffix();

// [twitter]
$context['text'] .= '[title]'.i18n::s('Twitter profile').' [escape][twitter=&lt;id&gt;][/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][twitter=bernard357][/escape]</td>'
	.'<td>[twitter=bernard357]</td></tr>'
	.Skin::table_suffix();

// [tsearch]
$context['text'] .= '[title]'.i18n::s('Twitter search').' [escape][tsearch=&lt;keyword&gt;][/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][tsearch=#yacs, 300, 200, subject: \'yacs\'][/escape]</td>'
	.'<td>[tsearch=#yacs, 300, 200, subject: \'yacs\']</td></tr>'
	.Skin::table_suffix();

// [freemind]
$context['text'] .= '[title]'.i18n::s('A dynamic Freemind map').' [escape][freemind] [freemind=section:&lt;id&gt;][/escape][/title]'
	.'<p>'.i18n::s('Use the simplest form to navigate site content is a mind map, or limit the scope to one section and related sub-sections.').'</p>'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][freemind=section:default, 100%, 300px][/escape]</td>'
	.'<td>[freemind=section:default, 100%, 300px]</td></tr>'
	.Skin::table_suffix();

// [cloud]
$context['text'] .= '[title]'.i18n::s('Cloud of tags').' [escape][cloud] [cloud=&lt;40&gt;][/escape][/title]'
	.'<p>'.i18n::s('Use the parameter to adjust the number of tags listed.').'</p>'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][cloud][/escape]</td>'
	.'<td>[cloud]</td></tr>'
	.Skin::table_suffix();

// [calendar]
$context['text'] .= '[title]'.i18n::s('Events').' [escape][calendar][/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][calendar][/escape]</td>'
	.'<td>[calendar]</td></tr>'
	.Skin::table_suffix();

// [location=latitude, longitude, label]
$context['text'] .= '[title]'.i18n::s('Direct location').' [escape][location=latitude, longitude, label][/escape][/title]'
	.Skin::table_prefix('wide grid')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][location=48.871264, 2.307558, Paris][/escape]</td>'
	.'<td>[location=48.871264, 2.307558, Paris]</td></tr>'
	.Skin::table_suffix();

// transform the text
$context['text'] = Codes::beautify($context['text']);

// general help on this page
$help = '<p>'.sprintf(i18n::s('Please note that actual rendering depends on the selected %s.'), Skin::build_link('skins/', i18n::s('skin'), 'shortcut')).'</p>';
$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

// render the skin
render_skin();

?>