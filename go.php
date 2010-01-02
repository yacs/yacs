<?php
/**
 * locate a page by name
 *
 * The page locator is aiming to streamline the usage of nick names.
 *
 * YACS is supporting nick names for a long-time for articles, categories, sections and also for user profiles.
 * Nick names are good, except that you have to remember the kind of things they are referencing.
 * This is because the URL used to view user profiles is different from the URL used to access
 * other kinds of things, including categories or server profiles.
 *
 * For example to view the user profile associated with the nick name 'foo' you have to use
 * the URL '[code]/yacs/users/view.php/foo[/code]'.
 *
 * But to view the category with the nick name 'bar' you would use '[code]/yacs/categories/view.php/bar[/code]' instead.
 *
 * The page locator will take care of nick names at a central place.
 * It attempts to search the provided name in: sections, categories, articles and user profiles.
 *
 * This means that to look for user 'foo' or for category 'bar' now you just have to submit nick names
 * to the page locator with either '[code]/yacs/go.php/foo[/code]' or '[code]/yacs/go.php/bar[/code]'.
 *
 * On successful search this script will redirect the surfer to the matching page.
 * Else it will offer to redirect to the regular search engine.
 *
 * Please note that you can invoke this script from within any article, using the yacs code [code]&#91;go=&lt;name&gt;, &lt;label&gt;][/code], like in
 * the following example:
 *
 * [snippet]
 * Please use our &#91;go=monthly, monthly archive] for reference
 * [/snippet]
 *
 * In the case of twin sections or of twin pages, this script lists all items that have the same nick name to allow
 * the surfer to make his own choice.
 *
 * If only one item has the given name the locator redirects directly to it.
 *
 * If no item has the name, the resulting page offers several choices:
 * - Create a new page with the name
 * - Look for another name
 * - Trigger the search engine on the given name
 *
 * This page locator has been largely inspired from the Cisco web site, featuring the famous [code]cisco.com/go[/code] URLs
 *
 * @link http://www.cisco.com/ Cisco home page
 *
 * Note that the [code]mod_rewrite[/code] module is not required to run this script.
 *
 * However, you can add ad hoc directives to [code].htaccess[/code] configuration file.
 * If the mod_rewrite module is available, '[code]go.php[/code]' can be rewritten to '[code]go[/code]', making this script
 * strictly equivalent to the Cisco's GO link.
 *
 * @author Bernard Paques
 * @tester Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see search.php
 */

// common definitions and initial processing
include_once 'shared/global.php';
include_once 'forms/forms.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// load localized strings
i18n::bind('root');

// load the skin
load_skin('go');

// the title of the page
$context['page_title'] = i18n::s('Page locator');

// ensure we have a non-empty string
if(!($id = trim($id)) || !preg_match('/\w/', $id)) {
	$context['text'] .= '<p>'.i18n::s('Please indicate a nick name to look for.')."</p>\n";

// look in sections
} elseif($items =& Sections::list_for_name($id, NULL, 'full')) {

		// only one section has this name
		if(count($items) == 1) {
			list($url, $attributes) = each($items);
			Safe::redirect($context['url_to_home'].$context['url_to_root'].$url);
		}

		// splash
		$context['text'] .= '<p>'.i18n::s('Select below among available sections.').'</p>';

		// several pages
		$context['text'] .= Skin::build_list($items, 'decorated');

// look in categories
} elseif(($item =& Categories::get($id)) || ($item =& Categories::get_by_keyword($id))) {
		Safe::redirect($context['url_to_home'].$context['url_to_root'].Categories::get_permalink($item));

// look in articles
} elseif($items =& Articles::list_for_name($id, NULL, 'full')) {

		// only one page has this name
		if(count($items) == 1) {
			list($url, $attributes) = each($items);
			Safe::redirect($context['url_to_home'].$context['url_to_root'].$url);
		}

		// splash
		$context['text'] .= '<p>'.i18n::s('Select below among available pages.').'</p>';

		// several pages
		$context['text'] .= Skin::build_list($items, 'decorated');

// look in forms
} elseif($items =& Forms::list_for_name($id, NULL, 'full')) {

		// only one page has this name
		if(count($items) == 1) {
			list($url, $attributes) = each($items);
			Safe::redirect($context['url_to_home'].$context['url_to_root'].$url);
		}

		// splash
		$context['text'] .= '<p>'.i18n::s('Select below among available pages.').'</p>';

		// several pages
		$context['text'] .= Skin::build_list($items, 'decorated');

// look in user profiles
} elseif($item =& Users::get($id)) {
		Safe::redirect($context['url_to_home'].$context['url_to_root'].Users::get_permalink($item));

// not found
} else {
	$context['text'] .= '<p>'.sprintf(i18n::s('Sorry, no page has the provided name: %s'), $id).'</p>'."\n";

	// offer to create a new page
	$context['text'] .= '<p>'.Skin::build_link('articles/edit.php?name='.urlencode($id), i18n::s('Add a page with this name'), 'shortcut').'</p>'."\n";

}


// the form to submit a new search
if($id)
	$label = i18n::s('Look for');
else
	$label = i18n::s('Nick name');
$input = '<input type="text" name="id" id="id" size="25" value="'.encode_field($id).'" maxlength="64" />'
	.' '.Skin::build_submit_button(i18n::s('Search'));
$context['text'] .= '<form method="get" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><p>'
	.$label.' '.$input.'</p></form>';

// the script used for form handling at the browser
$context['text'] .= JS_PREFIX
	.'	// check that main fields are not empty'."\n"
	.'	func'.'tion validateDocumentPost(container) {'."\n"
	."\n"
	.'		// search is mandatory'."\n"
	.'		if(!container.id.value) {'."\n"
	.'			alert("'.i18n::s('Please type something to search for').'");'."\n"
	.'			Yacs.stopWorking();'."\n"
	.'			return false;'."\n"
	.'		}'."\n"
	."\n"
	.'		// successful check'."\n"
	.'		return true;'."\n"
	.'	}'."\n"
	."\n"
	.'// set the focus on first form field'."\n"
	.'$("id").focus();'."\n"
	.JS_SUFFIX."\n";

// extend the process to the search engine
if($id) {

	// submit the request to our search engine
	$context['text'] .= '<p>'.sprintf(i18n::s('Submit "%s" to our %s in case some pages could match this keyword.'), $id, Skin::build_link('search.php?search='.urlencode($id), i18n::s('search engine'), 'basic')).'</p>'."\n";

}

// render the skin
render_skin();

?>