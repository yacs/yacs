<?php
/**
 * the configuration panel for skins
 *
 * This page is called to change rendering parameters of the YACS server.
 *
 * Parameters are grouped as follows:
 * - meta-information - most are used in template files
 * - general rendering options - enable or disable some components
 * - freemind - parameters specific to mind maps
 * - article pages - parameters specific to articles
 * - image processing
 *
 *
 * Meta-information parameters:
 *
 * [*] [code]site_name[/code] - used everywhere to reference your server.
 * If this parameter is empty, YACS uses the network host name instead (eg, '[code]www.myserver.com[/code]').
 * This parameter is appended to each &lt;title&gt; attribute.
 * It is also used extensively in meta attributes used at the main index page (eg, [code]DC.title[/code]) and
 * in newsfeeds (eg, it is the default value for feed title).
 * Also used at [article=about, the about page], the description page for your site.
 * Lastly, it is also the default value for subject of new newsletters.
 *
 * [*] [code]site_slogan[/code] - may be displayed at every page, in template.php.
 * During YACS installation a default slogan is proposed.
 * This parameter may be set to an empty string if you don't want any slogan to appear.
 *
 * [*] [code]site_description[/code] - appearing in a &lt;meta&gt; tag of all pages.
 * It is also the default value for channel description in newsfeeds.
 * Also used at [article=about, the about page], the description page for your site.
 * During YACS installation a default slogan is proposed.
 * This parameter may be set to an empty string if you don't want any description to appear.
 *
 * [*] [code]site_keywords[/code] - appearing in a &lt;meta&gt; tag
 *
 * [*] [code]site_email[/code] - webmaster mail address - warning, this address may be spammed
 *
 * [*] [code]site_copyright[/code] - used in [article=about, the about page]
 *
 * [*] [code]site_owner[/code] - used in [article=about, the about page]
 *
 * [*] [code]revisit_after[/code] - appearing in a &lt;meta&gt; tag (default: 7 days)
 *
 * [*] [code]site_position[/code] - the latitute and longitude, separated by a comma.
 * See either [link=GeoTags Search Engine]http://geotags.com/[/link]
 * or [link=Free Geocoding Service for 22 Countries]http://www.travelgis.com/geocode/Default.aspx[/link]
 * for more information.
 *
 * @link http://geotags.com/ GeoTags Search Engine
 * @link http://www.travelgis.com/geocode/Default.aspx Free Geocoding Service for 22 Countries
 *
 * [*] [code]site_head[/code] - inserted as-is in the &lt;head&gt; section of each page
 *
 * [*] [code]site_icon[/code] - the icon for favorites; may be displayed at every page, in template.php
 *
 * [*] [code]site_trailer[/code] - text added at the bottom of every page.
 * This is useful to integrate various javascript libraries (page tracking, etc).
 *
 *
 * Components:
 *
 * [*] [code]site_navigation_maximum[/code] - The maximum number of navigation boxes to display on page side.
 * Default value is 7. This parameter should be used in template file.
 *
 * [*] [code]site_extra_maximum[/code] - The maximum number of extra boxes to display on page side.
 * Default value is 7. This parameter is used in various scripts.
 *
 * [*] [code]skins_extra_components[/code] - The list of side components to put in the extra panel.
 *
 *
 * Options:
 *
 * [*] [code]with_export_tools[/code] - Display, or not, conversion tools
 *
 * [*] [code]with_anonymous_export_tools[/code] - Spread published information more largely
 *
 * [*] [code]with_author_information[/code] - Add links to author pages in lists of articles.
 * By default this information is available only at article pages.
 *
 * [*] [code]with_referrals[/code] - if set to Yes, show referrals to everybody.
 * Else show referrals only to associates.
 *
 * [*] [code]skins_general_without_feed[/code] - Do not offer feeds in sections, categories, and articles
 *
 * [*] [code]pages_without_bookmarklets[/code] - Do not offer javascript tools
 *
 * [*] [code]pages_without_history[/code] - To avoid the display of visited pages
 *
 * [*] [code]skins_with_details[/code] - Computing power is enough to provide more details.
 * When this parameter is set to 'Y', many additional requests are submitted
 * to the database server to count files, comments and links attached to sections
 * and to articles.
 *
 *
 * Parameters for image processing:
 *
 * [*] classes_for_avatar_images - styling information to be added to avatars
 *
 * [*] classes_for_icon_images - styling information to be added to page incons
 *
 * [*] classes_for_large_images - styling information to be added to large images
 *
 * [*] classes_for_thumbnail_images - styling information to be added to small images
 *
 * [*] [code]standard_width[/code] - the maximum width, in pixels, for images that have to be resized.
 * Default value is 640 pixels.
 *
 * [*] [code]standard_height[/code] - the maximum height, in pixels, for images that have to be resized.
 * Default value is 640 pixels.
 *
 * [*] [code]avatar_width[/code] - the maximum width, in pixels, for avatars.
 * Default value is 80 pixels.
 *
 * [*] [code]avatar_height[/code] - the maximum height, in pixels, for avatars.
 * Default value is 80 pixels.
 *
 * [*] [code]thumbnail_threshold[/code] - size in bytes that require a thumbnail image.
 * Default value is 20480 bytes.
 *
 * [*] [code]thumbnail_width[/code] - the maximum width, in pixels, for thumbnail images.
 * Default value is 60 pixels.
 *
 * [*] [code]thumbnail_height[/code] - the maximum height, in pixels, for thumbnail images.
 * Default value is 60 pixels.
 *
 * @see images/edit.php
 *
 * [*] [code]thumbnails_without_caption[/code] - If set to 'Y', only a hovering title is provided.
 * Else, which is the default, image title is added to thumbnail as caption.
 *
 *
 * Parameters for freemind:
 *
 * [*] [code]pages_without_freemind[/code] - Do not feature links to download
 * section content as Freemind maps.
 *
 * [*] [code]skins_freemind_canvas_size[/code] - Width and height of embedded
 * mind maps. Default is "100%, 500px".
 *
 * [*] [code]skins_freemind_article_bgcolor[/code] - Background for articles.
 *
 * [*] [code]skins_freemind_article_color[/code] - Foreground for articles.
 *
 * [*] [code]skins_freemind_article_style[/code] - 'fork' or 'bubble'
 *
 * [*] [code]skins_freemind_edge_color[/code] - Default is #6666ff
 *
 * [*] [code]skins_freemind_edge_style[/code] - Default is 'bezier'.
 * Can be either 'bezier', 'sharp_bezier', 'linear', 'sharp_linear', 'rectangular'.
 *
 * [*] [code]skins_freemind_edge_thickness[/code] - '1', '2', '3', ... or 'thin'.
 *
 * [*] [code]skins_freemind_main_bgcolor[/code] - Background for the central
 * area of the map.
 *
 * [*] [code]skins_freemind_main_color[/code] - Foreground for the central
 * area of the map.
 *
 * [*] [code]skins_freemind_section_bgcolor[/code] - Background for sections.
 *
 * [*] [code]skins_freemind_section_color[/code] - Foreground for sections.
 *
 * [*] [code]skins_freemind_section_style[/code] - 'fork' or 'bubble'
 *
 *
 * Access to this page is reserved to associates.
 *
 * Configuration information is saved into [code]parameters/skins.include.php[/code].
 * If YACS is prevented to write to the file, it displays parameters to allow for a manual update.
 *
 * The file [code]parameters/skins.include.php.bak[/code] can be used to restore
 * the active configuration before the last change.
 *
 * If the file [code]parameters/demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode.
 * In this mode the edit form is displayed, but parameters are not saved in the configuration file.
 *
 * @author Bernard Paques
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @author GnapZ
 * @tester Pat
 * @tester Natice
 * @tester FabriceV
 * @tester Aleko
 * @tester Guillaume Perez
 * @tester Mark
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
// common definitions and initial processing
include_once '../shared/global.php';

// load the skin
load_skin('skins');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Configure'), i18n::s('Page factory'));

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('skins/configure.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the input form
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'POST')) {

	// first installation
	if(!file_exists('../parameters/switch.on') && !file_exists('../parameters/switch.off'))
		$context['text'] .= '<p>'.i18n::s('You can use default values and change these later on. Hit the button at the bottom of the page to move forward.')."</p>\n";

	// load current parameters, if any
	Safe::load('parameters/skins.include.php');

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';

	//
	// meta-information
	//
	$meta = '';

	// site name
	$label = i18n::s('Site name');
	$input = '<input type="text" name="site_name" size="50" value="'.encode_field($context['site_name']).'" maxlength="255" />';
	$hint = i18n::s('Short and meaningful title, such as "My little big server", used almost everywhere, and appended to the title of every page of this site');
	$fields[] = array($label, $input, $hint);

	// site slogan
	if(!isset($context['site_slogan']))
		$context['site_slogan'] = '-- just do it. and do it right. and make it free. and let it be. and... (your turn)';
	$label = i18n::s('Site slogan');
	$input = '<input type="text" name="site_slogan" size="50" value="'.encode_field($context['site_slogan']).'" maxlength="255" />';
	$hint = i18n::s('This tag line will be repeated at a number of pages');
	$fields[] = array($label, $input, $hint);

	// site description
	if(!isset($context['site_description']))
		$context['site_description'] = 'YACS Community';
	$label = i18n::s('Site description');
	$input = '<textarea name="site_description" cols="40" rows="2">'.encode_field($context['site_description']).'</textarea>';
	$hint = i18n::s('Up to two lines of text, used in the "description" meta field to help search engines');
	$fields[] = array($label, $input, $hint);

	// keywords
	$label = i18n::s('Keywords');
	$input = '<input type="text" name="site_keywords" size="40" value="'.encode_field(isset($context['site_keywords']) ? $context['site_keywords'] : '').'" maxlength="255" />';
	$hint = i18n::s('Keywords separated with commas, inserted in the "keyword" meta field for search engines');
	$fields[] = array($label, $input, $hint);

	// site_email address
	if(!isset($context['site_email']) || !$context['site_email'])
		$context['site_email'] = 'unknown_webmaster@acme.heaven';
	$label = i18n::s('Contact mail address');
	$input = '<input type="text" name="site_email" size="40" value="'.encode_field($context['site_email']).'" maxlength="255" />';
	$hint = sprintf(i18n::s('May be subject to spam attacks; featured in the %s page and in RSS feeds, at least'),
		Skin::build_link(Articles::get_url('about'), i18n::s('about'), 'shortcut'));
	$fields[] = array($label, $input, $hint);

	// copyright
	$label = i18n::s('Copyright');
	$input = '<input type="text" name="site_copyright" size="50" value="'.encode_field(isset($context['site_copyright']) ? $context['site_copyright'] : '').'" maxlength="255" />';
	$hint = i18n::s('Example: "2002-2008, Acme incorporated"; inserted in the "copyright" meta field');
	$fields[] = array($label, $input, $hint);

	// site owner
	$label = i18n::s('Site owner');
	$input = '<input type="text" name="site_owner" size="50" value="'.encode_field(isset($context['site_owner']) ? $context['site_owner'] : '').'" maxlength="255" />';
	$hint = sprintf(i18n::s('The name of the site owner, e.g. "ACME and company"; Featured in the %s page and in ATOM feeds, at least'),
		Skin::build_link(Articles::get_url('about'), i18n::s('about'), 'shortcut'));
	$fields[] = array($label, $input, $hint);

	// revisit after
	if(!isset($context['site_revisit_after']) || !$context['site_revisit_after'])
		$context['site_revisit_after'] = 7;
	$label = i18n::s('Usual delay between updates');
	$input = '<input type="text" name="site_revisit_after" size="4" value="'.encode_field($context['site_revisit_after']).'" maxlength="4" /> '.i18n::s('days');
	$hint = i18n::s('Please be realistic here. Used to flag new and updated items. Also featured in "revisit-after" meta field.');
	$fields[] = array($label, $input, $hint);

	// position
	$label = i18n::s('Geographical position');
	$input = '<input type="text" name="site_position" size="40" value="'.encode_field(isset($context['site_position']) ? $context['site_position'] : '').'" maxlength="255" />';
	$hint = sprintf(i18n::s('Latitude and longitude, separated by a comma, for example: 47.98481,-71.42124, featured as meta fields "geo.position" and "ICBM" at the front page. See %s or %s'),
		Skin::build_link('http://geotags.com/', i18n::s('GeoTags Search Engine'), 'external'),
		Skin::build_link('http://www.travelgis.com/geocode/Default.aspx', i18n::s('Geocoding Service'), 'external'));
	$fields[] = array($label, $input, $hint);

	// head
	$label = i18n::s('Head');
	$input = '<textarea name="site_head" cols="40" rows="2">'.encode_field(isset($context['site_head']) ? $context['site_head'] : '').'</textarea>';
	$hint = i18n::s('Other tags to be inserted into the head section, as meta fields. Please double check generated code to avoid mistakes.  You can use this field to add meta information to your site, that will be used by search engines or software robots. Example: &lt;meta name="dmoz.id" content="put here the dmoz branch for your site"&gt;');
	$fields[] = array($label, $input, $hint);

	// icon
	$label = i18n::s('Icon');
	$input = '<input type="text" name="site_icon" size="40" value="'.encode_field(isset($context['site_icon']) ? $context['site_icon'] : '').'" maxlength="255" />';
	$hint = i18n::s('The web address of the little image representing your site in favorites or bookmarks. Used to supplement the default "favicon.ico", if any');
	$fields[] = array($label, $input, $hint);

	// trailer
	$label = i18n::s('Trailer');
	$input = '<textarea name="site_trailer" cols="40" rows="2">'.encode_field(isset($context['site_trailer']) ? $context['site_trailer'] : '').'</textarea>';
	$hint = i18n::s('Additional text inserted at the very end of every web page generated by YACS. Use this to integrate page tracking systems, with adequate &lt;script&gt; tags.');
	$fields[] = array($label, $input, $hint);

	// one folded box
	$meta .= Skin::build_form($fields);
	$fields = array();

	//
	// components
	//
	$components = '';

	// search delegation
	$label = i18n::s('Search');
	$input = '<input type="radio" name="skins_delegate_search" value="N"';
	if(!isset($context['skins_delegate_search']) || ($context['skins_delegate_search'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= ' onclick="$(skins_search_form).disabled=1"/> '.i18n::s('Process search requests internally, by requesting the back-end database');
	$input .= BR.'<input type="radio" name="skins_delegate_search" value="Y"';
	if(isset($context['skins_delegate_search']) && ($context['skins_delegate_search'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= ' onclick="$(skins_search_form).disabled=0"/> '.i18n::s('Use the following form to delegate search requests');
	
	// default to Google appliance
	if(!isset($context['skins_search_form']) || !$context['skins_search_form'])
		$context['skins_search_form'] = '<form method="get" action="http://search.mycompany.com/search"><div>'."\n"
			.'   <input type="text" name="q" size="10" maxlength="256" value="%s" />'."\n"
			.'   <input type="submit" name="btnG" value="&raquo;" />'."\n"
			.'   <input type="hidden" name="site" value="default_collection" />'."\n"
			.'   <input type="hidden" name="client" value="default_frontend" />'."\n"
			.'   <input type="hidden" name="output" value="xml_no_dtd" />'."\n"
			.'   <input type="hidden" name="proxystylesheet" value="default_frontend" />'."\n"
			.'</div></form>';
	$input .= BR.'<textarea name="skins_search_form" id="skins_search_form"cols="60" rows="3">'.encode_field($context['skins_search_form']).'</textarea>';
	$components .= '<p>'.$label.BR.$input."</p>\n";

	// components to put in the navigation panel
	$label = i18n::s('Order of navigation components');
	$input = '<textarea name="skins_navigation_components" id="skins_navigation_components"cols="60" rows="3">'.encode_field($context['skins_navigation_components']).'</textarea>';
	$keywords = array();
	$keywords[] = 'menu - '.i18n::s('Site menu');
	$keywords[] = 'user - '.i18n::s('User menu');
	$keywords[] = 'extra - '.i18n::s('Include the extra panel in a 2-column layout');
	$keywords[] = 'navigation - '.i18n::s('Dynamic navigation boxes, if any');
	$hint = i18n::s('You may combine several keywords:').Skin::finalize_list($keywords, 'compact');
	$components .= '<p>'.sprintf(i18n::s('%s: %s'), $label, BR.$input).BR.'<span class="details">'.$hint."</span></p>\n";

	// maximum number of navigation boxes
	if(!isset($context['site_navigation_maximum']) || !$context['site_navigation_maximum'])
		$context['site_navigation_maximum'] = 7;
	$label = i18n::s('Maximum number of navigation boxes');
	$input = '<input type="text" name="site_navigation_maximum" size="2" value="'.encode_field($context['site_navigation_maximum']).'" maxlength="2" />';
	$hint = i18n::s('Navigation boxes are displayed on page side, at all pages of the site.');
	$components .= '<p>'.$label.' '.$input.BR.'<span class="details">'.$hint."</span></p>\n";

	// components to put in the extra panel
	$label = i18n::s('Order of extra components');
	$input = '<textarea name="skins_extra_components" id="skins_extra_components"cols="60" rows="3">'.encode_field($context['skins_extra_components']).'</textarea>';
	$keywords = array();
	$keywords[] = 'profile - '.i18n::s('User profile, if activated');
	$keywords[] = 'tools - '.i18n::s('Page tools');
	$keywords[] = 'news - '.i18n::s('Side news, if any');
	$keywords[] = 'overlay - '.i18n::s('Overlay data, if any');
	$keywords[] = 'boxes - '.i18n::s('Extra boxes, if any');
	$keywords[] = 'share - '.i18n::s('Commands to share the page, if any');
	$keywords[] = 'channels - '.i18n::s('Commands to stay informed, if any');
	$keywords[] = 'twins - '.i18n::s('Pages with the same name, if any');
	$keywords[] = 'neighbours - '.i18n::s('Next and previous, if any');
	$keywords[] = 'contextual - '.i18n::s('Sections around, if any');
	$keywords[] = 'categories - '.i18n::s('Assign categories, for associates');
	$keywords[] = 'bookmarklets - '.i18n::s('Links to contribute, if any');
	$keywords[] = 'servers - '.i18n::s('Feeding servers, for associates');
	$keywords[] = 'download - '.i18n::s('Get section content as a Freemind map');
	$keywords[] = 'referrals - '.i18n::s('Links to this page, if any');
	$keywords[] = 'visited - '.i18n::s('Visited pages, if any');
	$hint = i18n::s('You may combine several keywords:').Skin::finalize_list($keywords, 'compact');
	$components .= '<p>'.sprintf(i18n::s('%s: %s'), $label, BR.$input).BR.'<span class="details">'.$hint."</span></p>\n";

	// maximum number of extra boxes
	if(!isset($context['site_extra_maximum']) || !$context['site_extra_maximum'])
		$context['site_extra_maximum'] = 7;
	$label = i18n::s('Maximum number of extra boxes');
	$input = '<input type="text" name="site_extra_maximum" size="2" value="'.encode_field($context['site_extra_maximum']).'" maxlength="2" />';
	$hint = i18n::s('Extra boxes are displayed on the side of pages to which they have been associated.');
	$components .= '<p>'.$label.' '.$input.BR.'<span class="details">'.$hint."</span></p>\n";

	//
	// options
	//
	$options = '';

	// with details
	$label = i18n::s('Details visibility:');
	$input = '<input type="radio" name="content_without_details" value="N"';
	if(!isset($context['content_without_details']) || ($context['content_without_details'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Display detailed information (author, date, ...) on all pages');
	$input .= BR.'<input type="radio" name="content_without_details" value="Y"';
	if(isset($context['content_without_details']) && ($context['content_without_details'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Details are displayed only in sections with option \'with_details\'');
	$options .= '<p>'.$label.BR.$input."</p>\n";

	// export tools global control
	$label = i18n::s('Export tools convert pages to an alternate format or media (pdf, word):');
	$input = '<input type="radio" name="with_export_tools" value="N"';
	if(!isset($context['with_export_tools']) || ($context['with_export_tools'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Export tools are displayed only in sections with option \'with_export_tools\'');
	$input .= BR.'<input type="radio" name="with_export_tools" value="Y"';
	if(isset($context['with_export_tools']) && ($context['with_export_tools'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Display export tools on all pages');
	$options .= '<p>'.$label.BR.$input."</p>\n";

	// export tools visibility
	$label = i18n::s('Export tools visibility');
	$input = '<input type="radio" name="with_anonymous_export_tools" value="N"';
	if(!isset($context['with_anonymous_export_tools']) || ($context['with_anonymous_export_tools'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Export tools are shown only to members');
	$input .= BR.'<input type="radio" name="with_anonymous_export_tools" value="Y"';
	if(isset($context['with_anonymous_export_tools']) && ($context['with_anonymous_export_tools'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Allow anonymous surfers to use export tools (recommended on intranets)');
	$options .= '<p>'.sprintf(i18n::s('%s: %s'), $label, BR.$input)."</p>\n";

	// author information
	$label = i18n::s('Author information');
	$input = '<input type="radio" name="with_author_information" value="N"';
	if(!isset($context['with_author_information']) || ($context['with_author_information'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not display this item.');
	$input .= BR.'<input type="radio" name="with_author_information" value="Y"';
	if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Add links to author profiles in lists of articles');
	$options .= '<p>'.sprintf(i18n::s('%s: %s'), $label, BR.$input)."</p>\n";

	// feed
	$label = i18n::s('Side box for RSS feeds');
	$input = '<input type="radio" name="skins_general_without_feed" value="N"';
	if(!isset($context['skins_general_without_feed']) || ($context['skins_general_without_feed'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('List RSS feeds in sections, categories, articles, and user profiles');
	$input .= BR.'<input type="radio" name="skins_general_without_feed" value="Y"';
	if(isset($context['skins_general_without_feed']) && ($context['skins_general_without_feed'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not display this item.');
	$options .= '<p>'.sprintf(i18n::s('%s: %s'), $label, BR.$input)."</p>\n";

	// bookmarklets
	$label = i18n::s('Side box for bookmarklets');
	$input = '<input type="radio" name="pages_without_bookmarklets" value="N"';
	if(!isset($context['pages_without_bookmarklets']) || ($context['pages_without_bookmarklets'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Add Javascript bookmarklets to enable further contributions');
	$input .= BR.'<input type="radio" name="pages_without_bookmarklets" value="Y"';
	if(isset($context['pages_without_bookmarklets']) && ($context['pages_without_bookmarklets'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not display this item.');
	$options .= '<p>'.sprintf(i18n::s('%s: %s'), $label, BR.$input)."</p>\n";

	// visited pages
	$label = i18n::s('Side box for visited pages');
	$input = '<input type="radio" name="pages_without_history" value="N"';
	if(!isset($context['pages_without_history']) || ($context['pages_without_history'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('List pages visited during a session');
	$input .= BR.'<input type="radio" name="pages_without_history" value="Y"';
	if(isset($context['pages_without_history']) && ($context['pages_without_history'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not display this item.');
	$options .= '<p>'.sprintf(i18n::s('%s: %s'), $label, BR.$input)."</p>\n";

	// with referrals
	$label = i18n::s('Display referrals:');
	$input = '<input type="radio" name="with_referrals" value="N"';
	if(!isset($context['with_referrals']) || ($context['with_referrals'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('only to associates');
	$input .= BR.'<input type="radio" name="with_referrals" value="Y"';
	if(isset($context['with_referrals']) && ($context['with_referrals'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('to everybody');
	$options .= '<p>'.$label.BR.$input."</p>\n";

	// with details
	$label = i18n::s('Level of details');
	$input = '<input type="radio" name="skins_with_details" value="N"';
	if($context['skins_with_details'] != 'Y')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Compute page elements dynamically');
	$input .= BR.'<input type="radio" name="skins_with_details" value="Y"';
	if($context['skins_with_details'] == 'Y')
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Benefit from extended computing power to provide additional dynamic details');
	$options .= '<p>'.sprintf(i18n::s('%s: %s'), $label, BR.$input)."</p>\n";

	//
	// handling images
	//

	// icons
	$label = i18n::s('Icons');
	if(!isset($context['classes_for_icon_images']) || !$context['classes_for_icon_images'])
		$context['classes_for_icon_images'] = 'reflect rheight10';
	$input = i18n::s('CSS classes').'&nbsp;<input type="text" name="classes_for_icon_images" size="15" value="'.encode_field($context['classes_for_icon_images']).'" maxlength="255" />';
	$fields[] = array($label, $input);

	// profile pictures
	$label = i18n::s('Profile pictures');
	if(!isset($context['avatar_width']) || !$context['avatar_width'])
		$context['avatar_width'] = 80;
	if(!isset($context['avatar_height']) || !$context['avatar_height'])
		$context['avatar_height'] = 80;
	if(!isset($context['classes_for_avatar_images']) || !$context['classes_for_avatar_images'])
		$context['classes_for_avatar_images'] = 'reflect rheight20';
	$input = sprintf(i18n::s('Maximum of %s pixels width by %s pixels height'), 
		'<input type="text" name="avatar_width" size="5" value="'.encode_field($context['avatar_width']).'" maxlength="5" />',
		'<input type="text" name="avatar_height" size="5" value="'.encode_field($context['avatar_height']).'" maxlength="5" />')
		.'<p>'.i18n::s('CSS classes').'&nbsp;<input type="text" name="classes_for_avatar_images" size="15" value="'.encode_field($context['classes_for_large_images']).'" maxlength="255" /></p>';
	$fields[] = array($label, $input);

	// large images
	$label = i18n::s('Large images');
	if(!isset($context['standard_width']) || !$context['standard_width'])
		$context['standard_width'] = 640;
	if(!isset($context['standard_height']) || !$context['standard_height'])
		$context['standard_height'] = 640;
	if(!isset($context['classes_for_large_images']) || !$context['classes_for_large_images'])
		$context['classes_for_large_images'] = 'reflect rheight10';
	$input = sprintf(i18n::s('Maximum of %s pixels width by %s pixels height'), 
		'<input type="text" name="standard_width" size="5" value="'.encode_field($context['standard_width']).'" maxlength="5" />',
		'<input type="text" name="standard_height" size="5" value="'.encode_field($context['standard_height']).'" maxlength="5" />')
		.'<p>'.i18n::s('CSS classes').'&nbsp;<input type="text" name="classes_for_large_images" size="15" value="'.encode_field($context['classes_for_large_images']).'" maxlength="255" /></p>';
	$fields[] = array($label, $input);

	// thumbnail images
	$label = i18n::s('Thumbnail images');
	if(!isset($context['thumbnail_width']) || !$context['thumbnail_width'])
		$context['thumbnail_width'] = 60;
	if(!isset($context['thumbnail_height']) || !$context['thumbnail_height'])
		$context['thumbnail_height'] = 60;
	if(!isset($context['classes_for_thumbnail_images']) || !$context['classes_for_thumbnail_images'])
		$context['classes_for_thumbnail_images'] = '';
	if(!isset($context['thumbnail_threshold']) || !$context['thumbnail_threshold'])
		$context['thumbnail_threshold'] = 20480;
	$input = sprintf(i18n::s('Maximum of %s pixels width by %s pixels height'), 
		'<input type="text" name="thumbnail_width" size="5" value="'.encode_field($context['thumbnail_width']).'" maxlength="5" />',
		'<input type="text" name="thumbnail_height" size="5" value="'.encode_field($context['thumbnail_height']).'" maxlength="5" />')
		.'<p>'.i18n::s('CSS classes').'&nbsp;<input type="text" name="classes_for_thumbnail_images" size="15" value="'.encode_field($context['classes_for_large_images']).'" maxlength="255" /></p>'
		.'<p>'.sprintf(i18n::s('Display the thumbnail image for images of more than %s bytes'), '<input type="text" name="thumbnail_threshold" size="10" value="'.encode_field($context['thumbnail_threshold']).'" maxlength="10" />')
		.BR.'<input type="radio" name="thumbnails_without_caption" value="N"';
	if(!isset($context['thumbnails_without_caption']) || ($context['thumbnails_without_caption'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Use titles as captions below thumbnail images');
	$input .= BR.'<input type="radio" name="thumbnails_without_caption" value="Y"';
	if(isset($context['thumbnails_without_caption']) && ($context['thumbnails_without_caption'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Only provide a hovering title, but no caption').'</p>';
	$fields[] = array($label, $input);

	// build the form
	$images = Skin::build_form($fields);
	$fields = array();

	$images .= '<p class="details">'.i18n::s('YACS uses the GD module of PHP to resize large pictures, and to create thumbnail images.')."</p>\n";

	//
	// handling freemind
	//
	$freemind = '';

	// without freemind
	$label = i18n::s('Download');
	$input = '<input type="radio" name="pages_without_freemind" value="N"';
	if(!isset($context['pages_without_freemind']) || ($context['pages_without_freemind'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Content of sections can be downloaded as Freemind maps');
	$input .= BR.'<input type="radio" name="pages_without_freemind" value="Y"';
	if(isset($context['pages_without_freemind']) && ($context['pages_without_freemind'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Do not offer links to get Freemind maps');
	$fields[] = array($label, $input);

	// freemind canvas
	if(!isset($context['skins_freemind_canvas_height']))
		$context['skins_freemind_canvas_height'] = '500px';
	if(!isset($context['skins_freemind_canvas_width']))
		$context['skins_freemind_canvas_width'] = '100%';
	$label = i18n::s('Canvas');
	$input = sprintf(i18n::s('Width: %s'), '<input type="text" name="skins_freemind_canvas_width" size="8" value="'.encode_field($context['skins_freemind_canvas_width']).'" maxlength="10" />')
		.' '.sprintf(i18n::s('Height: %s'), '<input type="text" name="skins_freemind_canvas_height" size="8" value="'.encode_field($context['skins_freemind_canvas_height']).'" maxlength="10" />');
	$hint = i18n::s('Width and height of Flash or Java canvas used for interactive browsing.');
	$fields[] = array($label, $input, $hint);

	// freemind edge color, style, and thickness
	if(!isset($context['skins_freemind_edge_color']))
		$context['skins_freemind_edge_color'] = '';
	if(!isset($context['skins_freemind_edge_style']))
		$context['skins_freemind_edge_style'] = '';
	if(!isset($context['skins_freemind_edge_thickness']))
		$context['skins_freemind_edge_thickness'] = '';
	$label = i18n::s('Edges');
	$input = sprintf(i18n::s('Color: %s'), '<input type="text" name="skins_freemind_edge_color" size="8" value="'.encode_field($context['skins_freemind_edge_color']).'" maxlength="8" />')
		.' '.sprintf(i18n::s('Thickness: %s'), '<input type="text" name="skins_freemind_edge_thickness" size="8" value="'.encode_field($context['skins_freemind_edge_thickness']).'" maxlength="8" />')
		.' '.sprintf(i18n::s('Style: %s'), '<input type="text" name="skins_freemind_edge_style" size="12" value="'.encode_field($context['skins_freemind_edge_style']).'" maxlength="12" />');
	$hint = i18n::s('Use HTML codes for colors, numbers or "thin" for thickness, and "bezier" or "linear" for style.');
	$fields[] = array($label, $input, $hint);

	// freemind main bgcolor and color
	if(!isset($context['skins_freemind_main_bgcolor']))
		$context['skins_freemind_main_bgcolor'] = '';
	if(!isset($context['skins_freemind_main_color']))
		$context['skins_freemind_main_color'] = '';
	$label = i18n::s('Main node');
	$input = sprintf(i18n::s('Color: %s'), '<input type="text" name="skins_freemind_main_color" size="8" value="'.encode_field($context['skins_freemind_main_color']).'" maxlength="8" />')
		.' '.sprintf(i18n::s('Background color: %s'), '<input type="text" name="skins_freemind_main_bgcolor" size="8" value="'.encode_field($context['skins_freemind_main_bgcolor']).'" maxlength="8" />');
	$hint = i18n::s('Use HTML codes for colors.');
	$fields[] = array($label, $input, $hint);

	// freemind sections bgcolor, color and style
	if(!isset($context['skins_freemind_section_bgcolor']))
		$context['skins_freemind_section_bgcolor'] = '';
	if(!isset($context['skins_freemind_section_color']))
		$context['skins_freemind_section_color'] = '';
	if(!isset($context['skins_freemind_section_style']))
		$context['skins_freemind_section_style'] = '';
	$label = i18n::s('Sections');
	$input = sprintf(i18n::s('Color: %s'), '<input type="text" name="skins_freemind_section_color" size="8" value="'.encode_field($context['skins_freemind_section_color']).'" maxlength="8" />')
		.' '.sprintf(i18n::s('Background color: %s'), '<input type="text" name="skins_freemind_section_bgcolor" size="8" value="'.encode_field($context['skins_freemind_section_bgcolor']).'" maxlength="8" />')
		.' '.sprintf(i18n::s('Style: %s'), '<input type="text" name="skins_freemind_section_style" size="8" value="'.encode_field($context['skins_freemind_section_style']).'" maxlength="8" />');
	$hint = i18n::s('Use HTML codes for colors, and "fork" or "bubble" for style.');
	$fields[] = array($label, $input, $hint);

	// freemind articles bgcolor, color and style
	if(!isset($context['skins_freemind_article_bgcolor']))
		$context['skins_freemind_article_bgcolor'] = '';
	if(!isset($context['skins_freemind_article_color']))
		$context['skins_freemind_article_color'] = '';
	if(!isset($context['skins_freemind_article_style']))
		$context['skins_freemind_article_style'] = '';
	$label = i18n::s('Pages');
	$input = sprintf(i18n::s('Color: %s'), '<input type="text" name="skins_freemind_article_color" size="8" value="'.encode_field($context['skins_freemind_article_color']).'" maxlength="8" />')
		.' '.sprintf(i18n::s('Background color: %s'), '<input type="text" name="skins_freemind_article_bgcolor" size="8" value="'.encode_field($context['skins_freemind_article_bgcolor']).'" maxlength="8" />')
		.' '.sprintf(i18n::s('Style: %s'), '<input type="text" name="skins_freemind_article_style" size="8" value="'.encode_field($context['skins_freemind_article_style']).'" maxlength="8" />');
	$hint = i18n::s('Use HTML codes for colors, and "fork" or "bubble" for style.');
	$fields[] = array($label, $input, $hint);

	// build the form
	$freemind .= Skin::build_form($fields);
	$fields = array();

	//
	// assemble all tabs
	//
	$all_tabs = array(
		array('meta', i18n::s('Meta-information'), 'meta_panel', $meta),
		array('components', i18n::s('Components'), 'components_panel', $components),
		array('options', i18n::s('Options'), 'options_panel', $options),
		array('images', i18n::s('Images'), 'images_panel', $images),
		array('freemind', i18n::s('Freemind'), 'freemind_panel', $freemind)
		);

	// let YACS do the hard job
	$context['text'] .= Skin::build_tabs($all_tabs);

	//
	// bottom commands
	//
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// control panel
	if(file_exists('../parameters/skins.include.php'))
		$menu[] = Skin::build_link('control/', i18n::s('Control Panel'), 'span');

	// all skins
	if(file_exists('../parameters/skins.include.php'))
		$menu[] = Skin::build_link('skins/', i18n::s('Themes'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// end of the form
	$context['text'] .= '</div></form>';

// no modifications in demo mode
} elseif(file_exists($context['path_to_root'].'parameters/demo.flag')) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation in demonstration mode.'));

// save updated parameters
} else {

	// ensure we have a valid revisit_after
	if(!isset($context['revisit_after']) || (intval($context['revisit_after']) < 1))
		$context['revisit_after'] = 1;

	// backup the old version
	Safe::unlink($context['path_to_root'].'parameters/skins.include.php.bak');
	Safe::rename($context['path_to_root'].'parameters/skins.include.php', $context['path_to_root'].'parameters/skins.include.php.bak');

	// build the new configuration file
	$content = '<?php'."\n"
		.'// This file has been created by the configuration script skins/configure.php'."\n"
		.'// on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'. Please do not modify it manually.'."\n"
		.'global $context;'."\n"
		.'$context[\'site_name\']=\''.addcslashes($_REQUEST['site_name'], "\\'")."';\n"
		.'$context[\'site_description\']=\''.addcslashes($_REQUEST['site_description'], "\\'")."';\n"
		.'$context[\'site_keywords\']=\''.addcslashes($_REQUEST['site_keywords'], "\\'")."';\n"
		.'$context[\'site_owner\']=\''.addcslashes($_REQUEST['site_owner'], "\\'")."';\n"
		.'$context[\'site_email\']=\''.addcslashes($_REQUEST['site_email'], "\\'")."';\n"
		.'$context[\'site_copyright\']=\''.addcslashes($_REQUEST['site_copyright'], "\\'")."';\n"
		.'$context[\'site_icon\']=\''.addcslashes($_REQUEST['site_icon'], "\\'")."';\n"
		.'$context[\'site_head\']=\''.addcslashes(str_replace("\r", '', $_REQUEST['site_head']), "\\'")."';\n"
		.'$context[\'site_revisit_after\']=\''.addcslashes($_REQUEST['site_revisit_after'], "\\'")."';\n"
		.'$context[\'site_slogan\']=\''.addcslashes($_REQUEST['site_slogan'], "\\'")."';\n"
		.'$context[\'site_position\']=\''.addcslashes($_REQUEST['site_position'], "\\'")."';\n"
		.'$context[\'site_trailer\']=\''.addcslashes($_REQUEST['site_trailer'], "\\'")."';\n";
	if(isset($_REQUEST['classes_for_avatar_images']))
		$content .= '$context[\'classes_for_avatar_images\']=\''.addcslashes($_REQUEST['classes_for_avatar_images'], "\\'")."';\n";
	if(isset($_REQUEST['classes_for_icon_images']))
		$content .= '$context[\'classes_for_icon_images\']=\''.addcslashes($_REQUEST['classes_for_icon_images'], "\\'")."';\n";
	if(isset($_REQUEST['classes_for_large_images']))
		$content .= '$context[\'classes_for_large_images\']=\''.addcslashes($_REQUEST['classes_for_large_images'], "\\'")."';\n";
	if(isset($_REQUEST['classes_for_thumbnail_images']))
		$content .= '$context[\'classes_for_thumbnail_images\']=\''.addcslashes($_REQUEST['classes_for_thumbnail_images'], "\\'")."';\n";
	if(isset($_REQUEST['content_without_details']))
		$content .= '$context[\'content_without_details\']=\''.addcslashes($_REQUEST['content_without_details'], "\\'")."';\n";
	if(isset($_REQUEST['pages_without_bookmarklets']))
		$content .= '$context[\'pages_without_bookmarklets\']=\''.addcslashes($_REQUEST['pages_without_bookmarklets'], "\\'")."';\n";
	if(isset($_REQUEST['pages_without_freemind']))
		$content .= '$context[\'pages_without_freemind\']=\''.addcslashes($_REQUEST['pages_without_freemind'], "\\'")."';\n";
	if(isset($_REQUEST['pages_without_history']))
		$content .= '$context[\'pages_without_history\']=\''.addcslashes($_REQUEST['pages_without_history'], "\\'")."';\n";
	if(isset($_REQUEST['site_extra_maximum']) && intval($_REQUEST['site_extra_maximum']))
		$content .= '$context[\'site_extra_maximum\']=\''.intval($_REQUEST['site_extra_maximum'])."';\n";
	if(isset($_REQUEST['site_navigation_maximum']) && intval($_REQUEST['site_navigation_maximum']))
		$content .= '$context[\'site_navigation_maximum\']=\''.intval($_REQUEST['site_navigation_maximum'])."';\n";
	if(isset($_REQUEST['skins_general_without_feed']))
		$content .= '$context[\'skins_general_without_feed\']=\''.addcslashes($_REQUEST['skins_general_without_feed'], "\\'")."';\n";
	if(isset($_REQUEST['skins_with_details']))
		$content .= '$context[\'skins_with_details\']=\''.addcslashes($_REQUEST['skins_with_details'], "\\'")."';\n";
	if(isset($_REQUEST['thumbnails_without_caption']))
		$content .= '$context[\'thumbnails_without_caption\']=\''.addcslashes($_REQUEST['thumbnails_without_caption'], "\\'")."';\n";
	if(isset($_REQUEST['with_anonymous_export_tools']))
		$content .= '$context[\'with_anonymous_export_tools\']=\''.addcslashes($_REQUEST['with_anonymous_export_tools'], "\\'")."';\n";
	if(isset($_REQUEST['skins_delegate_search']) && $_REQUEST['skins_delegate_search'])
		$content .= '$context[\'skins_delegate_search\']=\''.addcslashes($_REQUEST['skins_delegate_search'], "\\'")."';\n";
	if(isset($_REQUEST['with_export_tools']))
		$content .= '$context[\'with_export_tools\']=\''.addcslashes($_REQUEST['with_export_tools'], "\\'")."';\n";
	if(isset($_REQUEST['skins_extra_components']) && $_REQUEST['skins_extra_components'])
		$content .= '$context[\'skins_extra_components\']=\''.addcslashes($_REQUEST['skins_extra_components'], "\\'")."';\n";
	if(isset($_REQUEST['skins_freemind_article_bgcolor']) && $_REQUEST['skins_freemind_article_bgcolor'])
		$content .= '$context[\'skins_freemind_article_bgcolor\']=\''.addcslashes($_REQUEST['skins_freemind_article_bgcolor'], "\\'")."';\n";
	if(isset($_REQUEST['skins_freemind_article_color']) && $_REQUEST['skins_freemind_article_color'])
		$content .= '$context[\'skins_freemind_article_color\']=\''.addcslashes($_REQUEST['skins_freemind_article_color'], "\\'")."';\n";
	if(isset($_REQUEST['skins_freemind_article_style']) && $_REQUEST['skins_freemind_article_style'])
		$content .= '$context[\'skins_freemind_article_style\']=\''.addcslashes($_REQUEST['skins_freemind_article_style'], "\\'")."';\n";
	if(isset($_REQUEST['skins_freemind_canvas_height']) && $_REQUEST['skins_freemind_canvas_height'])
		$content .= '$context[\'skins_freemind_canvas_height\']=\''.addcslashes($_REQUEST['skins_freemind_canvas_height'], "\\'")."';\n";
	if(isset($_REQUEST['skins_freemind_canvas_width']) && $_REQUEST['skins_freemind_canvas_width'])
		$content .= '$context[\'skins_freemind_canvas_width\']=\''.addcslashes($_REQUEST['skins_freemind_canvas_width'], "\\'")."';\n";
	if(isset($_REQUEST['skins_freemind_edge_color']) && $_REQUEST['skins_freemind_edge_color'])
		$content .= '$context[\'skins_freemind_edge_color\']=\''.addcslashes($_REQUEST['skins_freemind_edge_color'], "\\'")."';\n";
	if(isset($_REQUEST['skins_freemind_edge_style']) && $_REQUEST['skins_freemind_edge_style'])
		$content .= '$context[\'skins_freemind_edge_style\']=\''.addcslashes($_REQUEST['skins_freemind_edge_style'], "\\'")."';\n";
	if(isset($_REQUEST['skins_freemind_edge_thickness']) && $_REQUEST['skins_freemind_edge_thickness'])
		$content .= '$context[\'skins_freemind_edge_thickness\']=\''.addcslashes($_REQUEST['skins_freemind_edge_thickness'], "\\'")."';\n";
	if(isset($_REQUEST['skins_freemind_main_bgcolor']) && $_REQUEST['skins_freemind_main_bgcolor'])
		$content .= '$context[\'skins_freemind_main_bgcolor\']=\''.addcslashes($_REQUEST['skins_freemind_main_bgcolor'], "\\'")."';\n";
	if(isset($_REQUEST['skins_freemind_main_color']) && $_REQUEST['skins_freemind_main_color'])
		$content .= '$context[\'skins_freemind_main_color\']=\''.addcslashes($_REQUEST['skins_freemind_main_color'], "\\'")."';\n";
	if(isset($_REQUEST['skins_freemind_section_bgcolor']) && $_REQUEST['skins_freemind_section_bgcolor'])
		$content .= '$context[\'skins_freemind_section_bgcolor\']=\''.addcslashes($_REQUEST['skins_freemind_section_bgcolor'], "\\'")."';\n";
	if(isset($_REQUEST['skins_freemind_section_color']) && $_REQUEST['skins_freemind_section_color'])
		$content .= '$context[\'skins_freemind_section_color\']=\''.addcslashes($_REQUEST['skins_freemind_section_color'], "\\'")."';\n";
	if(isset($_REQUEST['skins_freemind_section_style']) && $_REQUEST['skins_freemind_section_style'])
		$content .= '$context[\'skins_freemind_section_style\']=\''.addcslashes($_REQUEST['skins_freemind_section_style'], "\\'")."';\n";
	if(isset($_REQUEST['skins_search_form']) && $_REQUEST['skins_search_form'])
		$content .= '$context[\'skins_search_form\']=\''.addcslashes($_REQUEST['skins_search_form'], "\\'")."';\n";
	if(isset($_REQUEST['skins_navigation_components']) && $_REQUEST['skins_navigation_components'])
		$content .= '$context[\'skins_navigation_components\']=\''.addcslashes($_REQUEST['skins_navigation_components'], "\\'")."';\n";
	$content .= '$context[\'standard_width\']=\''.addcslashes($_REQUEST['standard_width'], "\\'")."';\n"
		.'$context[\'standard_height\']=\''.addcslashes($_REQUEST['standard_height'], "\\'")."';\n"
		.'$context[\'avatar_width\']=\''.addcslashes($_REQUEST['avatar_width'], "\\'")."';\n"
		.'$context[\'avatar_height\']=\''.addcslashes($_REQUEST['avatar_height'], "\\'")."';\n"
		.'$context[\'thumbnail_threshold\']=\''.addcslashes($_REQUEST['thumbnail_threshold'], "\\'")."';\n"
		.'$context[\'thumbnail_width\']=\''.addcslashes($_REQUEST['thumbnail_width'], "\\'")."';\n"
		.'$context[\'thumbnail_height\']=\''.addcslashes($_REQUEST['thumbnail_height'], "\\'")."';\n";
	if(isset($_REQUEST['with_author_information']))
		$content .= '$context[\'with_author_information\']=\''.addcslashes($_REQUEST['with_author_information'], "\\'")."';\n";
	if(isset($_REQUEST['with_referrals']))
		$content .= '$context[\'with_referrals\']=\''.addcslashes($_REQUEST['with_referrals'], "\\'")."';\n";
	$content .= '?>'."\n";

	// update the parameters file
	if(!Safe::file_put_contents('parameters/skins.include.php', $content)) {

		Logger::error(sprintf(i18n::s('ERROR: Impossible to write to the file %s. The configuration has not been saved.'), 'parameters/skins.include.php'));

		// allow for a manual update
		$context['text'] .= '<p style="text-decoration: blink;">'.sprintf(i18n::s('To actually change the configuration, please copy and paste following lines by yourself in file %s.'), 'parameters/skins.include.php')."</p>\n";

	// job done
	} else {

		$context['text'] .= '<p>'.sprintf(i18n::s('The following configuration has been saved into the file %s.'), 'parameters/skins.include.php')."</p>\n";

		// first installation
		if(!file_exists('../parameters/switch.on') && !file_exists('../parameters/switch.off'))
			$context['text'] .= '<p>'.i18n::s('Review provided information and go to the bottom of the page to move forward.')."</a></p>\n";

		// purge the cache
		Cache::clear();

		// remember the change
		$label = sprintf(i18n::c('%s has been updated'), 'parameters/skins.include.php');
		Logger::remember('skins/configure.php', $label);
	}

	// display updated parameters
	$context['text'] .= Skin::build_box(i18n::s('Configuration parameters'), Safe::highlight_string($content), 'folded');

	// first installation
	if(!file_exists('../parameters/switch.on') && !file_exists('../parameters/switch.off')) {
		$context['text'] .= Skin::build_block('<form method="get" action="../control/" id="main_form">'."\n"
			.'<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Switch the server on')).'</p>'."\n"
			.'</form>', 'bottom');

	// ordinary follow-up commands
	} else {

		// follow-up commands
		$follow_up = i18n::s('Where do you want to go now?');
		$menu = array();
		$menu = array_merge($menu, array( 'skins/' => i18n::s('Themes') ));
		$menu = array_merge($menu, array( 'control/' => i18n::s('Control Panel') ));
		$menu = array_merge($menu, array( 'skins/configure.php' => i18n::s('Configure again') ));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	}

}

// render the skin
render_skin();

?>