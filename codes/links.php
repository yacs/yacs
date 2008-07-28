<?php
/**
 * examples of formatting codes for links
 *
 * Following codes are documented on this page:
 * - &lt;url&gt; - &lt;a href="url">url&lt;/a> or &lt;a href="url" class="external">url&lt;/a>
 * - &#91;link]&lt;url&gt;[/link] - &lt;a href="url">url&lt;/a> or &lt;a href="url" class="external">url&lt;/a>
 * - &#91;link=&lt;label&gt;]&lt;url&gt;[/link] - &lt;a href="url">label&lt;/a> or &lt;a href="url" class="external">label&lt;/a>
 * - &#91;url]&lt;url&gt;[/url] - deprecated by [link]
 * - &#91;button=&lt;label&gt;]&lt;url&gt;[/button] - build simple buttons with css
 * - &lt;address&gt; - &lt;a href="mailto:address" class="email">address&lt;/a>
 * - &#91;email]&lt;address&gt;[/email] - &lt;a href="mailto:address" class="email">address&lt;/a>
 * - &#91;email=&lt;name&gt;]&lt;address&gt;[/email] - &lt;a href="mailto:address" class="email">name&lt;/a>
 * - &#91;go=&lt;word&gt;] - to trigger the selector on 'word'
 * - &#91;article=&lt;id>] - use article title as link label
 * - &#91;article=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;next=&lt;id>] - shortcut to next article
 * - &#91;next=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;previous=&lt;id>] - shortcut to previous article
 * - &#91;previous=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;section=&lt;id>] - use section title as link label
 * - &#91;section=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;category=&lt;id>] - use category title as link label
 * - &#91;category=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;user=&lt;id>] - use nick name as link label
 * - &#91;user=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;server=&lt;id>] - use server title as link label
 * - &#91;server=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;file=&lt;id>] - use file title as link label
 * - &#91;file=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;download=&lt;id>] - a link to download a file
 * - &#91;download=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;action=&lt;id>] - use action title as link label
 * - &#91;action=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;comment=&lt;id>] - use comment id in link label
 * - &#91;comment=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;decision=&lt;id>] - use decision id in link label
 * - &#91;decision=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;script]&lt;path/script.php&gt;[/email] - to the phpDoc page for script 'path/script.php'
 * - &#91;search] - a search form
 * - &#91;search=&lt;word&gt;] - hit Enter to search for 'word'
 * - &#91;wikipedia=&lt;keyword] - search Wikipedia
 * - &#91;wikipedia=&lt;keyword, foo bar] - search Wikipedia, with label 'foo bar'
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
$context['page_title'] = i18n::s('Codes to format links');

// the date of last modification
if(Surfer::is_associate())
	$context['page_details'] .= '<p class="details">'.sprintf(i18n::s('Edited %s'), Skin::build_date(getlastmod())).'</p>';

// page header
$context['text'] .= '<p>'.i18n::s('Various methods are presented to link your pages to others.').'</p>';

// add a toc
$context['text'] .= "\n".'[toc]'."\n";


// url
$context['text'] .= '[title]'.i18n::s('External link').' [escape]&lt;url&gt;[/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('You can visit www.cisco.com or http://www.nortel.com, or have a chat at irc://irc.eu.be.ofloo.net/').'[/escape]</td>'
	.'<td>'.i18n::s('You can visit www.cisco.com or http://www.nortel.com, or have a chat at irc://irc.eu.be.ofloo.net/').'</td></tr>'
	.Skin::table_suffix();

// [link]url[/link] - external
$context['text'] .= '[title]'.i18n::s('External link').' [escape][link]&lt;url&gt;[/link][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('You can visit [link]www.cisco.com[/link] or [link]http://www.nortel.com[/link], or have a chat at [link]irc://irc.eu.be.ofloo.net/[/link]').'[/escape]</td>'
	.'<td>'.i18n::s('You can visit [link]www.cisco.com[/link] or [link]http://www.nortel.com[/link], or have a chat at [link]irc://irc.eu.be.ofloo.net/[/link]').'</td></tr>'
	.Skin::table_suffix();

// [link]url[/link] - internal
$context['text'] .= '[title]'.i18n::s('Internal link').' [escape][link]&lt;url&gt;[/link][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('You can visit [link]codes/basic.php[/link] or [link]index.php[/link]').'[/escape]</td>'
	.'<td>'.i18n::s('You can visit [link]codes/basic.php[/link] or [link]index.php[/link]').'</td></tr>'
	.Skin::table_suffix();

// [link=label]url[/link]
$context['text'] .= '[title]'.i18n::s('Labelled link').' [escape][link=&lt;label&gt;]&lt;url&gt;[/link][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('You can visit [link=Cisco On Line]www.cisco.com[/link]').'[/escape]</td>'
	.'<td>'.i18n::s('You can visit [link=Cisco On Line]www.cisco.com[/link]').'</td></tr>'
	.Skin::table_suffix();

// [button=label]url[/button]
$context['text'] .= '[title]'.i18n::s('Labelled button').' [escape][button=&lt;label&gt;]&lt;url&gt;[/button][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('You can visit [button=Cisco On Line]www.cisco.com[/button]').'[/escape]</td>'
	.'<td>'.i18n::s('You can visit [button=Cisco On Line]www.cisco.com[/button]').'</td></tr>'
	.Skin::table_suffix();

// address
$context['text'] .= '[title]'.i18n::s('e-mail address').' [escape]&lt;address&gt;[/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('You can send a message at webmaster@acme.com').'[/escape]</td>'
	.'<td>'.i18n::s('You can send a message at webmaster@acme.com').'</td></tr>'
	.Skin::table_suffix();

// [email]address[/email]
$context['text'] .= '[title]'.i18n::s('e-mail address').' [escape][email]&lt;address&gt;[/email][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('You can send a message at [email]webmaster@acme.com[/email]').'[/escape]</td>'
	.'<td>'.i18n::s('You can send a message at [email]webmaster@acme.com[/email]').'</td></tr>'
	.Skin::table_suffix();

// [email=label]address[/email]
$context['text'] .= '[title]'.i18n::s('Labelled e-mail address').' [escape][email=&lt;label&gt;]&lt;address&gt;[/email][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('You can send a message to our [email=support team]webmaster@acme.com[/email]').'[/escape]</td>'
	.'<td>'.i18n::s('You can send a message to our [email=support team]webmaster@acme.com[/email]').'</td></tr>'
	.Skin::table_suffix();

// [go=monthly, monthly archive]
$context['text'] .= '[title]'.i18n::s('Selector').' [escape][go=&lt;name&gt;, &lt;label&gt;][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('Please use our [go=monthly, monthly archive] for reference').'[/escape]</td>'
	.'<td>'.i18n::s('Please use our [go=monthly, monthly archive] for reference').'</td></tr>'
	.Skin::table_suffix();

// [article=id]
$context['text'] .= '[title]'.i18n::s('Article shortcut').' [escape][article=&lt;id&gt;][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('You can take a look at [article=2]').'[/escape]</td>'
	.'<td>'.i18n::s('You can take a look at [article=2]').'</td></tr>'
	.Skin::table_suffix();

// [article=id, label]
$context['text'] .= '[title]'.i18n::s('Article labelled shortcut').' [escape][article=&lt;id&gt;, &lt;label&gt;][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('You can take a look at [article=2, this article]').'[/escape]</td>'
	.'<td>'.i18n::s('You can take a look at [article=2, this article]').'</td></tr>'
	.Skin::table_suffix();

// [previous=id]
$context['text'] .= '[title]'.i18n::s('Previous article shortcut').' [escape][previous=&lt;id&gt;][/escape], [escape][previous=&lt;id&gt;, &lt;label&gt;][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('You can take a look at [previous=2, the previous page]').'[/escape]</td>'
	.'<td>'.i18n::s('You can take a look at [previous=2, the previous page]').'</td></tr>'
	.Skin::table_suffix();

// [next=id]
$context['text'] .= '[title]'.i18n::s('Next article shortcut').' [escape][next=&lt;id&gt;][/escape], [escape][next=&lt;id&gt;, &lt;label&gt;][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('You can take a look at [next=2, next page]').'[/escape]</td>'
	.'<td>'.i18n::s('You can take a look at [next=2, next page]').'</td></tr>'
	.Skin::table_suffix();

// [section=id]
$context['text'] .= '[title]'.i18n::s('Section shortcut').' [escape][section=&lt;id&gt;][/escape], [escape][section=&lt;id&gt;, &lt;label&gt;][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('You can take a look at [section=2, the manual]').'[/escape]</td>'
	.'<td>'.i18n::s('You can take a look at [section=2, the manual]').'</td></tr>'
	.Skin::table_suffix();

// [category=id]
$context['text'] .= '[title]'.i18n::s('Category shortcut').' [escape][category=&lt;id&gt;][/escape], [escape][category=&lt;id&gt;, &lt;label&gt;][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('You can take a look at [category=featured]').'[/escape]</td>'
	.'<td>'.i18n::s('You can take a look at [category=featured]').'</td></tr>'
	.Skin::table_suffix();

// [user=id]
$context['text'] .= '[title]'.i18n::s('User shortcut').' [escape][user=&lt;id&gt;][/escape], [escape][user=&lt;id&gt;, &lt;label&gt;][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('Click to view the page of [user=2]').'[/escape]</td>'
	.'<td>'.i18n::s('Click to view the page of [user=2]').'</td></tr>'
	.Skin::table_suffix();

// [server=id]
$context['text'] .= '[title]'.i18n::s('Server shortcut').' [escape][server=&lt;id&gt;][/escape], [escape][server=&lt;id&gt;, &lt;label&gt;][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('Click to view the page of [server=2, this server]').'[/escape]</td>'
	.'<td>'.i18n::s('Click to view the page of [server=2, this server]').'</td></tr>'
	.Skin::table_suffix();

// [file=id]
$context['text'] .= '[title]'.i18n::s('File shortcut').' [escape][file=&lt;id&gt;][/escape], [escape][file=&lt;id&gt;, &lt;label&gt;][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('Click to view the page of [file=2, this file]').'[/escape]</td>'
	.'<td>'.i18n::s('Click to view the page of [file=2, this file]').'</td></tr>'
	.Skin::table_suffix();

// [download=id]
$context['text'] .= '[title]'.i18n::s('File download shortcut').' [escape][download=&lt;id&gt;][/escape], [escape][download=&lt;id&gt;, &lt;label&gt;][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('Click to [download=2, download the file]').'[/escape]</td>'
	.'<td>'.i18n::s('Click to [download=2, download the file]').'</td></tr>'
	.Skin::table_suffix();

// [action=id]
$context['text'] .= '[title]'.i18n::s('Action shortcut').' [escape][action=&lt;id&gt;][/escape], [escape][action=&lt;id&gt;, &lt;label&gt;][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('Click to view the page of [action=2, this action]').'[/escape]</td>'
	.'<td>'.i18n::s('Click to view the page of [action=2, this action]').'</td></tr>'
	.Skin::table_suffix();

// [comment=id]
$context['text'] .= '[title]'.i18n::s('Comment shortcut').' [escape][comment=&lt;id&gt;][/escape], [escape][comment=&lt;id&gt;, &lt;label&gt;][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('Click to view the page of [comment=2, this comment]').'[/escape]</td>'
	.'<td>'.i18n::s('Click to view the page of [comment=2, this comment]').'</td></tr>'
	.Skin::table_suffix();

// [decision=id]
$context['text'] .= '[title]'.i18n::s('Decision shortcut').' [escape][decision=&lt;id&gt;][/escape], [escape][decision=&lt;id&gt;, &lt;label&gt;][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('Click to view the page of [decision=2, this decision]').'[/escape]</td>'
	.'<td>'.i18n::s('Click to view the page of [decision=2, this decision]').'</td></tr>'
	.Skin::table_suffix();

// [script]index.php[/script]
$context['text'] .= '[title]'.i18n::s('Script shortcut').' [escape][script]&lt;path/script.php&gt;[/script][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('You can access the documentation for the script [script]shared/codes.php[/script]').'[/escape]</td>'
	.'<td>'.i18n::s('You can access the documentation for the script [script]shared/codes.php[/script]').'</td></tr>'
	.Skin::table_suffix();

// [search=yacs]
$context['text'] .= '[title]'.i18n::s('Search form').' [escape][search][/escape], [escape][search=&lt;words&gt;][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('All you want to know on [search=yacs]').'[/escape]</td>'
	.'<td>'.i18n::s('All you want to know on [search=yacs]').'</td></tr>'
	.Skin::table_suffix();

// [wikipedia=keyword, label]
$context['text'] .= '[title]'.i18n::s('Wikipedia').' [escape][wikipedia=keyword][/escape], [escape][wikipedia=keyword, label][/escape][/title]'
	.Skin::table_prefix('100%')
	.Skin::table_row(array(i18n::s('Example'), i18n::s('Rendering')), 'header')
	.'<tr><td class="sample">[escape]'.i18n::s('All you want to know on [wikipedia=Web_2, the web 2.0]').'[/escape]</td>'
	.'<td>'.i18n::s('All you want to know on [wikipedia=Web_2, the web 2.0]').'</td></tr>'
	.Skin::table_suffix();

// transform the text
$context['text'] = Codes::beautify($context['text']);

// general help on this page
$help = '<p>'.sprintf(i18n::s('Please note that actual rendering depends on the selected %s.'), Skin::build_link('skins/', i18n::s('skin'), 'shortcut')).'</p>';
$context['extra'] .= Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

// render the skin
render_skin();
?>