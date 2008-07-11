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
 * - 'private' - private pages
 * - 'queries' - pages sent by surfers to submit their queries to the webmaster
 * - 'templates' - models for new articles
 *
 * Additional sections may be created directly from within the content assistant, in [script]control/populate.php[/script].
 *
 * @see control/populate.php
 *
 * Moreover, some sections may be created by other scripts, namely:
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
	$context['page_title'] = i18n::s('Content Assistant');

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
	$text .= sprintf(i18n::s('A section "%s" already exists.'), i18n::c('Covers')).BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'covers';
	$fields['title'] = i18n::c('Covers');
	$fields['introduction'] = i18n::c('The top page here is also displayed at the front page');
	$fields['home_panel'] = 'none'; // special processing at the front page -- see index.php
	$fields['index_map'] = 'N'; // listed with special sections
	$fields['locked'] = 'Y'; // only associates can contribute
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	$fields['content_options'] = 'auto_publish'; // ease the job of newbies
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'default' section
if(Sections::get('default'))
	$text .= sprintf(i18n::s('A section "%s" already exists.'), i18n::c('Pages')).BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'default';
	$fields['title'] = i18n::c('Pages');
	$fields['introduction'] = i18n::c('The default place to post new pages');
	$fields['description'] = '';
	$fields['sections_layout'] = 'decorated';
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'extra_boxes' section
if(Sections::get('extra_boxes'))
	$text .= sprintf(i18n::s('A section "%s" already exists.'), i18n::c('Extra boxes')).BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'extra_boxes';
	$fields['title'] = i18n::c('Extra boxes');
	$fields['introduction'] = i18n::c('Boxes displayed aside the front page');
	$fields['description'] = i18n::c('All [link=codes]codes/[/link] are available to format your boxes. Keep the content as compact as possible because of the small size of any single box. When ready, publish pages to have boxes actually displayed at the front page.');
	$fields['home_panel'] = 'extra_boxes'; // one extra box per article at the front page
	$fields['index_map'] = 'N'; // listed with special sections
	$fields['locked'] = 'Y'; // only associates can contribute
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'gadget_boxes' section
if(Sections::get('gadget_boxes'))
	$text .= sprintf(i18n::s('A section "%s" already exists.'), i18n::c('Gadget boxes')).BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'gadget_boxes';
	$fields['title'] = i18n::c('Gadget boxes');
	$fields['introduction'] = i18n::c('Boxes displayed in the middle of the front page');
	$fields['description'] = i18n::c('All [link=codes]codes/[/link] are available to format your boxes. Keep the content as compact as possible because of the small size of any single box. When ready, publish pages to have boxes actually displayed at the front page.');
	$fields['home_panel'] = 'gadget_boxes'; // one gadget box per article at the front page
	$fields['index_map'] = 'N'; // listed with special sections
	$fields['locked'] = 'Y'; // only associates can contribute
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'global' section
if(Sections::get('global'))
	$text .= sprintf(i18n::s('A section "%s" already exists.'), i18n::c('Global pages')).BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'global';
	$fields['title'] = i18n::c('Global pages');
	$fields['introduction'] = i18n::c('Used from everywhere');
	$fields['home_panel'] = 'none'; // special processing at the front page -- see index.php
	$fields['index_map'] = 'N'; // listed with special sections
	$fields['locked'] = 'Y'; // only associates can contribute
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	$fields['rank'] = '1000'; // before other special sections
	$fields['content_options'] = 'auto_publish'; // these will be reviewed anyway
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'navigation_boxes' section
if(Sections::get('navigation_boxes'))
	$text .= sprintf(i18n::s('A section "%s" already exists.'), i18n::c('Navigation boxes')).BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'navigation_boxes';
	$fields['title'] = i18n::c('Navigation boxes');
	$fields['introduction'] = i18n::c('Boxes displayed aside all pages');
	$fields['description'] = i18n::c('All [link=codes]codes/[/link] are available to format your boxes. Keep the content as compact as possible because of the small size of any single box. When ready, publish pages to have boxes actually displayed at the front page.');
	$fields['home_panel'] = 'none'; // special processing everywhere -- see skins/<skin>/template.php
	$fields['index_map'] = 'N'; // listed with special sections
	$fields['locked'] = 'Y'; // only associates can contribute
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'processed_queries' section
if($section = Sections::get('processed_queries')) {
	$text .= sprintf(i18n::s('A section "%s" already exists.'), i18n::c('Processed queries')).BR."\n";
	$processed_id = $section['id'];
} else {
	$fields = array();
	$fields['nick_name'] = 'processed_queries';
	$fields['title'] = i18n::c('Processed queries');
	$fields['introduction'] =& i18n::c('Saved for history');
	$fields['active_set'] = 'N'; // only associates can access these pages
	$fields['home_panel'] = 'none'; // special processing everywhere -- see skins/<skin>/template.php
	$fields['index_map'] = 'N'; // listed with special sections
	$fields['locked'] = 'Y'; // only associates can contribute
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	$fields['rank'] = '20000'; // pushed at the end
	if($processed_id = Sections::post($fields))
		$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'private' section
if($section = Sections::get('private')) {
	$text .= sprintf(i18n::s('A section "%s" already exists.'), i18n::c('Private pages')).BR."\n";
} else {
	$fields = array();
	$fields['nick_name'] = 'private';
	$fields['title'] =& i18n::c('Private pages');
	$fields['introduction'] =& i18n::c('For on-demand conversations and groups');
	$fields['locked'] = 'N'; // no direct contributions
	$fields['home_panel'] = 'none'; // content is not pushed at the front page
	$fields['index_map'] = 'N'; // this is a special section
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	$fields['articles_layout'] = 'yabb'; // these are threads
	$fields['content_options'] = 'with_deletions with_export_tools'; // allow editors to delete pages here
	$fields['maximum_items'] = 20000; // limit the overall number of threads
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'queries' section --after processed_queries
if(Sections::get('queries'))
	$text .= sprintf(i18n::s('A section "%s" already exists.'), i18n::c('Queries')).BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'queries';
	$fields['title'] = i18n::c('Queries');
	$fields['introduction'] =& i18n::c('Submitted by any surfer');
	$fields['description'] =& i18n::c('Add comments to provide support.');
	$fields['active_set'] = 'N'; // only associates can access these pages
	$fields['home_panel'] = 'none'; // special processing everywhere -- see skins/<skin>/template.php
	$fields['index_map'] = 'N'; // listed with special sections
	$fields['locked'] = 'Y'; // only associates can contribute
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	if($processed_id)
		$fields['behaviors'] = 'move_on_article_access '.$processed_id; // a basic workflow
	$fields['content_options'] = 'auto_publish'; // these will be reviewed anyway
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'templates' section
if(Sections::get('templates'))
	$text .= sprintf(i18n::s('A section "%s" already exists.'), i18n::c('Templates')).BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'templates';
	$fields['title'] = i18n::c('Templates');
	$fields['introduction'] = i18n::c('Models to be duplicated');
	$fields['active_set'] = 'N'; // only associates can access these pages
	$fields['home_panel'] = 'none'; // special processing everywhere -- see skins/<skin>/template.php
	$fields['index_map'] = 'N'; // listed with special sections
	$fields['locked'] = 'Y'; // only associates can contribute
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	$fields['content_options'] = 'auto_publish'; // these will be reviewed anyway
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section "%s" has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// report on actions performed
if($included)
	echo $text;
else {
	$context['text'] .= $text;

	// follow-up commands
	$follow_up = i18n::s('Where do you want to go now?');
	$menu = array();
	$menu = array_merge($menu, array('sections/' => i18n::s('Site map')));
	$menu = array_merge($menu, array('control/populate.php' => i18n::s('Content Assistant')));
	$follow_up .= Skin::build_list($menu, 'page_menu');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

	render_skin();
}
?>