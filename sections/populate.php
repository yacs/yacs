<?php
/**
 * populate sections
 *
 * Creates following sections for web management purposes:
 * - 'covers' - the most recent article is displayed at the front page of the site
 * - 'default' - the default place to post new content
 * - 'extra_boxes' - boxes only displayed at the front page, in the extra panel
 * - 'gadget_boxes' - boxes only displayed at the front page, as gadgets
 * - 'global' - global pages
 * - 'navigation_boxes' - displayed at every page of the site, in the navigation panel
 * - 'processed_queries' - the archive of old queries
 * - 'queries' - pages sent by surfers to submit their queries to the webmaster
 * - 'templates' - models for new articles
 *
 * Additional sections may be created directly from within the content assistant, in [script]control/populate.php[/script].
 *
 * @see control/populate.php
 *
 * Moreover, some sections may be created by other scripts, namely:
 * - 'chats' - private interactive discussions - created automatically in [script]users/contact.php[/script]
 * - 'clicks' - to put orphan links when clicked - created automatically in [script]links/links.php[/script]
 * - 'external_news' - for links gathered from feeding servers in [script]feeds/feeds.php[/script] and [script]servers/edit.php[/script]
 * - 'letters' - pages sent by e-mail to subscribers - created automatically in [script]letters/new.php[/script]
 *
 * @see letters/new.php
 * @see links/links.php
 * @see query.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// if it was a HEAD request, stop here
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// this script will be included most of the time
$included = TRUE;

// but direct call is also allowed
if(!defined('YACS')) {
	$included = FALSE;

	// include the global declarations
	include_once '../shared/global.php';

	// load the skin
	load_skin('sections');

	// the path to this page
	$context['path_bar'] = array( 'sections/' => i18n::s('Sections') );

	// the title of the page
	$context['page_title'] = i18n::s('Add default sections');

	// stop hackers the hard way
	if(!Surfer::is_associate())
		exit('You are not allowed to perform this operation.');

}

// clear the cache for sections
Cache::clear('sections');

// this page is dedicated to sections
$text = '';

// 'covers' section
if(Sections::get('covers'))
	$text .= i18n::s('A section already exists for cover pages.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'covers';
	$fields['title'] = i18n::c('Covers');
	$fields['introduction'] = i18n::c('Enter your cover page here');
	$fields['description'] = i18n::c('The most recent published article in this section is used as the cover page of the site.');
	$fields['home_panel'] = 'none'; // special processing at the front page -- see index.php
	$fields['index_map'] = 'N'; // listed with special sections
	$fields['locked'] = 'Y'; // only associates can contribute
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'default' section
if(Sections::get('default'))
	$text .= i18n::s('A default section already exists.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'default';
	$fields['title'] = i18n::c('Pages');
	$fields['introduction'] = i18n::c('The default place to post new pages');
	$fields['description'] = '';
	$fields['sections_layout'] = 'decorated';
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'extra_boxes' section
if(Sections::get('extra_boxes'))
	$text .= i18n::s('A section already exists for extra boxes displayed at the front page.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'extra_boxes';
	$fields['title'] = i18n::c('Extra boxes');
	$fields['introduction'] = i18n::c('Describe here all boxes put in the extra panel at the front page.');
	$fields['description'] = i18n::c('Put in this section specific articles used to build the extra boxes of your site, those that will appear only on the front page of your site. All [link=codes]codes/[/link] are available to format your boxes. Of course, keep the content as compact as possible because of the small size of any single box. Use the field \'title\' to define box titles. When ready, publish your pages to let boxes actually appear. You may use the field \'rank\' to define the display order of boxes.');
	$fields['home_panel'] = 'extra_boxes'; // one extra box per article at the front page
	$fields['index_map'] = 'N'; // listed with special sections
	$fields['locked'] = 'Y'; // only associates can contribute
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'gadget_boxes' section
if(Sections::get('gadget_boxes'))
	$text .= i18n::s('A section already exists for gadget boxes displayed at the front page.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'gadget_boxes';
	$fields['title'] = i18n::c('Gadget Boxes');
	$fields['introduction'] = i18n::c('Describe here all boxes displayed as gadget boxes in the middle of the front page');
	$fields['description'] = i18n::c('Put in this section specific articles used to build the gadget boxes of your site, those that will appear only on the front page of your site. All [link=codes]codes/[/link] are available to format your boxes. Of course, keep the content as compact as possible because of the small size of any single box. Use the field \'title\' to define box titles. When ready, Publish your pages to let boxes actually appear. You may use the field \'rank\' to define the display order of boxes.');
	$fields['home_panel'] = 'gadget_boxes'; // one gadget box per article at the front page
	$fields['index_map'] = 'N'; // listed with special sections
	$fields['locked'] = 'Y'; // only associates can contribute
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'global' section
if(Sections::get('global'))
	$text .= i18n::s('A section already exists for global pages.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'global';
	$fields['title'] = i18n::c('Global pages');
	$fields['description'] = i18n::c('This section contains pages that are referenced globally.');
	$fields['home_panel'] = 'none'; // special processing at the front page -- see index.php
	$fields['index_map'] = 'N'; // listed with special sections
	$fields['locked'] = 'Y'; // only associates can contribute
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	$fields['rank'] = '1000'; // before other special sections
	$fields['content_options'] = 'auto_publish'; // these will be reviewed anyway
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'navigation_boxes' section
if(Sections::get('navigation_boxes'))
	$text .= i18n::s('A section already exists for navigation boxes.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'navigation_boxes';
	$fields['title'] = i18n::c('Navigation Boxes');
	$fields['introduction'] = i18n::c('Describe all navigation boxes here');
	$fields['description'] = i18n::c('Put in this section specific articles used to build the navigation boxes of your site. All [link=codes]codes/[/link] are available to format your boxes. Of course, keep the content as compact as possible because of the small size of any single box. Use the field \'title\' to define box titles. When ready, Publish your pages to let boxes actually appear. You may use the field \'rank\' to define the display order of boxes.');
	$fields['home_panel'] = 'none'; // special processing everywhere -- see skins/<skin>/template.php
	$fields['index_map'] = 'N'; // listed with special sections
	$fields['locked'] = 'Y'; // only associates can contribute
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'processed_queries' section
if($section = Sections::get('processed_queries')) {
	$text .= i18n::s('A section already exists for processed queries.').BR."\n";
	$processed_id = $section['id'];
} else {
	$fields = array();
	$fields['nick_name'] = 'processed_queries';
	$fields['title'] = i18n::c('Processed Queries');
	$fields['introduction'] =& i18n::c('Saved for history');
	$fields['active_set'] = 'N'; // only associates can access these pages
	$fields['home_panel'] = 'none'; // special processing everywhere -- see skins/<skin>/template.php
	$fields['index_map'] = 'N'; // listed with special sections
	$fields['locked'] = 'Y'; // only associates can contribute
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	$fields['rank'] = '20000'; // pushed at the end
	if($processed_id = Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'queries' section
if(Sections::get('queries'))
	$text .= i18n::s('A section already exists for surfer queries.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'queries';
	$fields['title'] = i18n::c('Queries');
	$fields['introduction'] =& i18n::c('Submitted to the webmaster by any surfers');
	$fields['description'] =& i18n::c('This section is aiming to capture feedback directly from surfers. It is highly recommended to move pages below after their processing.');
	$fields['active_set'] = 'N'; // only associates can access these pages
	$fields['home_panel'] = 'none'; // special processing everywhere -- see skins/<skin>/template.php
	$fields['index_map'] = 'N'; // listed with special sections
	$fields['locked'] = 'Y'; // only associates can contribute
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	if($processed_id)
		$fields['behaviors'] = 'move_on_article_access '.$processed_id; // a basic workflow
	$fields['content_options'] = 'auto_publish'; // these will be reviewed anyway
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'templates' section
if(Sections::get('templates'))
	$text .= i18n::s('A section already exists for templates.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'templates';
	$fields['title'] = i18n::c('Templates');
	$fields['introduction'] = i18n::c('Models to be duplicated');
	$fields['description'] = i18n::c('Put in this section special pages to be replicated elsewhere.');
	$fields['active_set'] = 'N'; // only associates can access these pages
	$fields['home_panel'] = 'none'; // special processing everywhere -- see skins/<skin>/template.php
	$fields['index_map'] = 'N'; // listed with special sections
	$fields['locked'] = 'Y'; // only associates can contribute
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	$fields['content_options'] = 'auto_publish'; // these will be reviewed anyway
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// report on actions performed
if($included)
	echo $text;
else {
	$context['text'] .= $text;

	// follow-up commands
	$follow_up = i18n::s('What do you want to do now?');
	$menu = array();
	$menu = array_merge($menu, array('sections/' => i18n::s('Go back to the site map')));
	$menu = array_merge($menu, array('control/populate.php' => i18n::s('Add additional content')));
	$follow_up .= Skin::build_list($menu, 'page_menu');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

	render_skin();
}
?>