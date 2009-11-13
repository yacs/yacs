<?php
/**
 * examples of live formatting codes
 *
 * Following codes are documented on this page:
 * - &#91;sections] - site map
 * - &#91;sections=section:&lt;id>] - sub-sections
 * - &#91;sections=self] - sections assigned to current surfer
 * - &#91;sections=user:&lt;id>] - sections assigned to given user
 * - &#91;categories] - category tree
 * - &#91;categories=category:&lt;id>] - sub-categories
 * - &#91;categories=self] - categories assigned to current surfer
 * - &#91;categories=user:&lt;id>] - categories assigned to given user
 * - &#91;published] - most recent published pages, in a compact list
 * - &#91;published=section:&lt;id>] - articles published most recently in the given section
 * - &#91;published=category:&lt;id>] - articles published most recently in the given category
 * - &#91;published=user:&lt;id>] - articles published most recently created by given user
 * - &#91;published.decorated=self, 20] - 20 most recent pages from current surfer, as a decorated list
 * - &#91;updated] - most recent updated pages, in a compact list
 * - &#91;updated=section:&lt;id>] - articles updated most recently in the given section
 * - &#91;updated=category:&lt;id>] - articles updated most recently in the given category
 * - &#91;updated=user:&lt;id>] - articles updated most recently created by given user
 * - &#91;updated.simple=self, 12] - articles updated most recently created by current surfer, as a simple list
 * - &#91;read] - most read articles, in a compact list
 * - &#91;read=section:&lt;id>] - articles of fame in the given section
 * - &#91;read=self] - personal hits
 * - &#91;read=user:&lt;id>] - personal hits
 * - &#91;voted] - most voted articles, in a compact list
 * - &#91;voted=section:&lt;id>] - articles of fame in the given section
 * - &#91;voted=self] - personal hits
 * - &#91;voted=user:&lt;id>] - personal hits
 * - &#91;collections] - list available collections
 * - &#91;users=present] - list of users present on site
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
$context['page_title'] = i18n::s('Live codes');

// the date of last modification
if(Surfer::is_associate())
	$context['page_details'] .= '<p class="details">'.sprintf(i18n::s('Edited %s'), Skin::build_date(getlastmod())).'</p>';

// page header
$context['text'] .= '<p>'.i18n::s('On this page we are introducing codes related to dynamic queries of site content.').'</p>';

// add a toc
$context['text'] .= "\n".'[toc]'."\n";

// [sections]
$context['text'] .= '[title]'.i18n::s('Sections').' [escape][sections] [sections=section:&lt;id&gt;][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][sections][/escape]</td>'
	.'<td>[sections]</td></tr>'
	.Skin::table_suffix();

// [sections=self]
$context['text'] .= '[title]'.i18n::s('Assigned sections').' [escape][sections=self] [sections=user:&lt;id&gt;][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][sections.folded=self][/escape]</td>'
	.'<td>[sections.folded=self]</td></tr>'
	.Skin::table_suffix();

// [categories]
$context['text'] .= '[title]'.i18n::s('Categories').' [escape][categories] [categories=category:&lt;id&gt;][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][categories][/escape]</td>'
	.'<td>[categories]</td></tr>'
	.Skin::table_suffix();

// [categories=self]
$context['text'] .= '[title]'.i18n::s('Assigned categories').' [escape][categories=self] [categories=user:&lt;id&gt;][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][categories=self][/escape]</td>'
	.'<td>[categories=self]</td></tr>'
	.Skin::table_suffix();

// [published]
$context['text'] .= '[title]'.i18n::s('Recent pages').' [escape][published] [published=section:&lt;id&gt;] [published=category:&lt;id&gt;][/escape][/title]'
	.'<p>'.i18n::s('Use the simplest form to display a compact list of pages, or limit the scope.').'</p>'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][published.decorated][/escape]</td>'
	.'<td>[published.decorated]</td></tr>'
	.Skin::table_suffix();

// [published=self]
$context['text'] .= '[title]'.i18n::s('Personal pages').' [escape][published=self] [published=user:&lt;id&gt;][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][published=self, 20][/escape]</td>'
	.'<td>[published=self, 20]</td></tr>'
	.Skin::table_suffix();

// [updated]
$context['text'] .= '[title]'.i18n::s('Recent updates').' [escape][updated] [updated=section:&lt;id&gt;] [updated=category:&lt;id&gt;][/escape][/title]'
	.'<p>'.i18n::s('Use the simplest form to display a compact list of pages, or limit the scope.').'</p>'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][updated.timeline][/escape]</td>'
	.'<td>[updated.timeline]</td></tr>'
	.Skin::table_suffix();

// [updated=self]
$context['text'] .= '[title]'.i18n::s('Personal updates').' [escape][updated=self] [updated=user:&lt;id&gt;][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][updated=self, 12][/escape]</td>'
	.'<td>[updated=self, 12]</td></tr>'
	.Skin::table_suffix();

// [read]
$context['text'] .= '[title]'.i18n::s('Hall of fame').' [escape][read] [read=section:&lt;id&gt;][/escape][/title]'
	.'<p>'.i18n::s('Use the simplest form to display a compact list of pages, or limit the scope.').'</p>'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][read][/escape]</td>'
	.'<td>[read]</td></tr>'
	.Skin::table_suffix();

// [read=self]
$context['text'] .= '[title]'.i18n::s('Personal hits').' [escape][read=self] [read=user:&lt;id&gt;][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][read=self][/escape]</td>'
	.'<td>[read=self]</td></tr>'
	.Skin::table_suffix();

// [voted]
$context['text'] .= '[title]'.i18n::s('Hall of fame').' [escape][voted] [voted=section:&lt;id&gt;][/escape][/title]'
	.'<p>'.i18n::s('Use the simplest form to display a compact list of pages, or limit the scope.').'</p>'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][voted][/escape]</td>'
	.'<td>[voted]</td></tr>'
	.Skin::table_suffix();

// [voted=self]
$context['text'] .= '[title]'.i18n::s('Personal hits').' [escape][voted=self] [voted=user:&lt;id&gt;][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][voted=self][/escape]</td>'
	.'<td>[voted=self]</td></tr>'
	.Skin::table_suffix();

// [collections]
$context['text'] .= '[title]'.i18n::s('Collections').' [escape][collections][/escape][/title]'
	.'<p>'.i18n::s('Use the configuration panel for collections to create new collections of files.').'</p>'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][collections][/escape]</td>'
	.'<td>[collections]</td></tr>'
	.Skin::table_suffix();

// [users=present]
$context['text'] .= '[title]'.i18n::s('Present users').' [escape][users=present][/escape][/title]'
	.Skin::table_prefix('wide')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape][users=present][/escape]</td>'
	.'<td>[users=present]</td></tr>'
	.Skin::table_suffix();

// transform the text
$context['text'] = Codes::beautify($context['text']);

// general help on this page
$help = '<p>'.sprintf(i18n::s('Please note that actual rendering depends on the selected %s.'), Skin::build_link('skins/', i18n::s('skin'), 'shortcut')).'</p>';
$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'extra', 'help');

// render the skin
render_skin();

?>