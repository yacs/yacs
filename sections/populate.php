<?php
/**
 * populate sections
 *
 * Creates following sections for web management purposes:
 * - 'channels' - sample interactive places
 * - 'default' - the default place to post new content
 * - 'extra_boxes' - boxes only displayed at the front page, in the extra panel
 * - 'gadget_boxes' - boxes only displayed at the front page, as gadgets
 * - 'global' - global pages
 * - 'navigation_boxes' - displayed at every page of the site, in the navigation panel
 * - 'processed_queries' - the archive of old queries
 * - 'queries' - pages sent by surfers to submit their queries to the webmaster
 * - 'templates' - models for new articles
 *
 * Also, if the parameter $context['populate'] is set to 'samples', additional articles will be created:
 * - 'files' - a sample library of files
 * - 'my_blog' - a sample blog
 * - 'my_jive_board' - a sample discussion board
 * - 'my_manual' - a sample electronic book
 * - 'my_manual_chapter' - chapter 1 of the sample electronic book
 * - 'my_section' - a sample plain section
 * - 'my_wiki' - a sample wiki
 * - 'my_yabb_board' - a sample discussion board
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
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
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

// 'channels' section - interactive places
if(Sections::get('channels'))
	$text .= i18n::s('A section already exists for channels.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'channels';
	$fields['title'] = i18n::c('Channels');
	$fields['introduction'] = i18n::c('Real-time collaboration');
	$fields['description'] = i18n::c('Every page in this section supports interactive discussion and file sharing.');
	$fields['home_panel'] = 'none'; // special processing at the front page -- see index.php
	$fields['index_map'] = 'Y'; // listed with regular sections
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	$fields['articles_layout'] = 'map'; // list threads appropriately
	$fields['content_options'] = 'view_as_thread'; // change the rendering script for articles
	$fields['maximum_items'] = 1000; // limit the overall number of threads
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'default' section - basic data
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

// 'extra_boxes' section - basic data
if(Sections::get('extra_boxes'))
	$text .= i18n::s('A section already exists for extra boxes displayed at the front page.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'extra_boxes';
	$fields['title'] = i18n::c('Extra boxes');
	$fields['introduction'] = i18n::c('Describe here all boxes put in the extra panel at the front page.');
	$fields['description'] = i18n::c('Put in this section specific articles used to build the extra boxes of your site, those that will appear only on the front page of your site. All [link=codes]codes/[/link] are available to format your boxes. Of course, keep the content as compact as possible because of the small size of any single box. Use the field \'title\' to define box titles. When ready, publish your pages to let boxes actually appear. You may use the field \'rank\' to define the display order of boxes.');
	$fields['active_set'] = 'N'; // only associates can access these pages
	$fields['home_panel'] = 'extra_boxes'; // one extra box per article at the front page
	$fields['index_map'] = 'N'; // listed with special sections
	$fields['locked'] = 'Y'; // only associates can contribute
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'files' section -- sample data
if(!isset($context['populate']) || ($context['populate'] != 'samples'))
	;
elseif(Sections::get('files'))
	$text .= i18n::s('A sample section already exists for simple file downloads.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'files';
	$fields['title'] = i18n::c('Files');
	$fields['introduction'] = i18n::c('Sample download section');
	$fields['description'] = i18n::c('This section has been created by the populate script for experimentation purpose. It is devoted to the download of files. [hidden]Compared to a standard section, this one has been configured via the \'options\' field to enable files attachments.[/hidden] If you have more than some files to download, you may prefer to attach files to separate articles in one or several regular sections.');
	$fields['options'] = 'with_files no_articles'; // enable file attachments
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'gadget_boxes' section - basic data
if(Sections::get('gadget_boxes'))
	$text .= i18n::s('A section already exists for gadget boxes displayed at the front page.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'gadget_boxes';
	$fields['title'] = i18n::c('Gadget Boxes');
	$fields['introduction'] = i18n::c('Describe here all boxes displayed as gadget boxes in the middle of the front page');
	$fields['description'] = i18n::c('Put in this section specific articles used to build the gadget boxes of your site, those that will appear only on the front page of your site. All [link=codes]codes/[/link] are available to format your boxes. Of course, keep the content as compact as possible because of the small size of any single box. Use the field \'title\' to define box titles. When ready, Publish your pages to let boxes actually appear. You may use the field \'rank\' to define the display order of boxes.');
	$fields['active_set'] = 'N'; // only associates can access these pages
	$fields['home_panel'] = 'gadget_boxes'; // one gadget box per article at the front page
	$fields['index_map'] = 'N'; // listed with special sections
	$fields['locked'] = 'Y'; // only associates can contribute
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'global' section - basic data
if(Sections::get('global'))
	$text .= i18n::s('A section already exists for global pages.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'global';
	$fields['title'] = i18n::c('Global pages');
	$fields['description'] = i18n::c('This section contains pages that are referenced globally.');
	$fields['home_panel'] = 'none'; // special processing at the front page -- see index.php
	$fields['index_map'] = 'N'; // listed with special sections to not put it in a tab
	$fields['locked'] = 'Y'; // only associates can contribute
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	$fields['rank'] = '90000'; // towards the end of the site map
	$fields['content_options'] = 'auto_publish'; // these will be reviewed anyway
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'my_blog' section -- sample data
if(!isset($context['populate']) || ($context['populate'] != 'samples'))
	;
elseif(Sections::get('my_blog'))
	$text .= i18n::s('A sample section already exists for blogging.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'my_blog';
	$fields['title'] = i18n::c('My blog');
	$fields['introduction'] = i18n::c('Sample blogging place');
	$fields['description'] = i18n::c('This section has been created by the populate script for experimentation purpose on available layouts. Feel free to change this text, to add some images, to play with codes, etc. Have you checked the help link on the side of this page? Once you will feel more comfortable with the handling of sections, just delete this one and create tons of sections of your own.');
	$fields['section_layout'] = 'map';
	$fields['options'] = 'with_creator_profile articles_by_publication';
	$fields['articles_layout'] = 'daily'; // that's a blog
	$fields['content_options'] = 'with_extra_profile with_rating'; // let surfers rate their readings
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'my_jive_board' section -- sample data
if(!isset($context['populate']) || ($context['populate'] != 'samples'))
	;
elseif(Sections::get('my_jive_board'))
	$text .= i18n::s('A sample section already exists for sample jive discussion board.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'my_jive_board';
	$fields['title'] = i18n::c('My jive discussion board');
	$fields['introduction'] = i18n::c('Sample discussion board');
	$fields['description'] = i18n::c('This section has been created by the populate script for experimentation purpose on available layouts. Feel free to change this text, to add some images, to play with codes, etc. Have you checked the help link on the side of this page? Once you will feel more comfortable with the handling of sections, just delete this one and create tons of sections of your own.');
	$fields['articles_layout'] = 'jive'; // a threading layout
	$fields['content_options'] = 'auto_publish with_rating'; // let surfers rate their readings
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'my_manual' section -- sample data
if(!isset($context['populate']) || ($context['populate'] != 'samples'))
	;
elseif(Sections::get('my_manual'))
	$text .= i18n::s('A sample section already exists for sample electronic manual.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'my_manual';
	$fields['title'] = i18n::c('My manual');
	$fields['introduction'] = i18n::c('Sample electronic book');
	$fields['description'] = i18n::c('This section has been created by the populate script for experimentation purpose on available layouts. Feel free to change this text, to add some images, to play with codes, etc. Have you checked the help link on the side of this page? Once you will feel more comfortable with the handling of sections, just delete this one and create tons of sections of your own.');
	$fields['sections_layout'] = 'inline'; // list content of sub-sections
	$fields['articles_layout'] = 'manual'; // the default value
	$fields['content_options'] = 'with_rating'; // let surfers rate their readings
	$fields['locked'] = 'Y'; // post in underlying chapters
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'my_manual_chapter' section -- sample data
if(!isset($context['populate']) || ($context['populate'] != 'samples'))
	;
elseif(Sections::get('my_manual_chapter'))
	$text .= i18n::s('A sample chapter already exists for electronic manual.').BR."\n";
elseif($parent = Sections::lookup('my_manual')) {
	$fields = array();
	$fields['nick_name'] = 'my_manual_chapter';
	$fields['title'] = i18n::c('Chapter 1 - The very first chapter of my manual');
	$fields['introduction'] = i18n::c('Post pages here to populate chapter 1');
	$fields['description'] = i18n::c('This section has been created by the populate script for experimentation purpose on available layouts. Feel free to change this text, to add some images, to play with codes, etc. Have you checked the help link on the side of this page? Once you will feel more comfortable with the handling of sections, just delete this one and create tons of sections of your own.');
	$fields['anchor'] = $parent; // anchor to parent
	$fields['sections_layout'] = 'inline'; // list content of sub-sections
	$fields['articles_layout'] = 'manual';
	$fields['content_options'] = 'with_rating'; // let surfers rate their readings
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'my_section' section -- sample data
if(!isset($context['populate']) || ($context['populate'] != 'samples'))
	;
elseif(Sections::get('my_section'))
	$text .= i18n::s('A sample section already exists for sample articles.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'my_section';
	$fields['title'] = i18n::c('My Section');
	$fields['introduction'] = i18n::c('Sample plain section');
	$fields['description'] = i18n::c('This section has been created by the populate script for experimentation purpose. Feel free to change this text, to add some images, to play with codes, etc. Have you checked the help link on the side of this page? Once you will feel more comfortable with the handling of sections, just delete this one and create tons of sections of your own.');
	$fields['content_options'] = 'with_rating, with_bottom_tools'; // let surfers rate their readings
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'my_wiki' section -- sample data
if(!isset($context['populate']) || ($context['populate'] != 'samples'))
	;
elseif(Sections::get('my_wiki'))
	$text .= i18n::s('A sample wiki section already exists.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'my_wiki';
	$fields['title'] = i18n::c('My wiki');
	$fields['introduction'] = i18n::c('Sample wiki');
	$fields['description'] = i18n::c('This section has been created by the populate script for experimentation purpose on available layouts. Feel free to change this text, to add some images, to play with codes, etc. Have you checked the help link on the side of this page? Once you will feel more comfortable with the handling of sections, just delete this one and create tons of sections of your own.');
	$fields['section_layout'] = 'inline'; // that's a wiki
	$fields['articles_layout'] = 'wiki'; // a wiki
	$fields['content_options'] = 'anonymous_edit, auto_publish, with_rating, with_bottom_tools'; // let surfers rate their readings
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'my_yabb_board' section -- sample data
if(!isset($context['populate']) || ($context['populate'] != 'samples'))
	;
elseif(Sections::get('my_yabb_board'))
	$text .= i18n::s('A sample section already exists for sample yabb discussion board.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'my_yabb_board';
	$fields['title'] = i18n::c('My yabb discussion board');
	$fields['introduction'] = i18n::c('Sample discussion board');
	$fields['description'] = i18n::c('This section has been created by the populate script for experimentation purpose on available layouts. Feel free to change this text, to add some images, to play with codes, etc. Have you checked the help link on the side of this page? Once you will feel more comfortable with the handling of sections, just delete this one and create tons of sections of your own.');
	$fields['articles_layout'] = 'yabb';
	$fields['sections_layout'] = 'yabb';
	$fields['content_options'] = 'auto_publish, with_prefix_profile'; // publish every submission
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'navigation_boxes' section - basic data
if(Sections::get('navigation_boxes'))
	$text .= i18n::s('A section already exists for navigation boxes.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'navigation_boxes';
	$fields['title'] = i18n::c('Navigation Boxes');
	$fields['introduction'] = i18n::c('Describe all navigation boxes here');
	$fields['description'] = i18n::c('Put in this section specific articles used to build the navigation boxes of your site. All [link=codes]codes/[/link] are available to format your boxes. Of course, keep the content as compact as possible because of the small size of any single box. Use the field \'title\' to define box titles. When ready, Publish your pages to let boxes actually appear. You may use the field \'rank\' to define the display order of boxes.');
	$fields['active_set'] = 'N'; // only associates can access these pages
	$fields['home_panel'] = 'none'; // special processing everywhere -- see skins/<skin>/template.php
	$fields['index_map'] = 'N'; // listed with special sections
	$fields['locked'] = 'Y'; // only associates can contribute
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	if(Sections::post($fields))
		$text .= sprintf(i18n::s('A section %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'processed_queries' section - basic data
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

// 'queries' section - basic data
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

// 'templates' section - basic data
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
	$fields['content_overlay'] = 'select'; // the overlay may change from one page to another one
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
	$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';
	$menu = array();
	$menu = array_merge($menu, array('sections/' => i18n::s('Go back to the site map')));
	$menu = array_merge($menu, array('control/populate.php' => i18n::s('Add additional content')));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

	render_skin();
}
?>