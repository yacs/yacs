<?php
/**
 * the main help index
 *
 * YACS is the acronym of 'Yet Another Community System'.
 * Of course, this name is a joke - Remember the old days of yacc on Unix?
 *
 * [title]What can I do with YACS?[/title]
 *
 * Following patterns should be considered only as examples of what can be achieved with YACS.
 *
 * - Weblogging platform - The powerful templating system of YACS supports various skins,
 * and let web writers focus on what they have to say. Moreover, each weblogger can have one or more weblogs to post in.
 * Actually any YACS section may become a weblog. Of course, an associate may weblog to any section of one site.
 * Also, one or several webloggers can be given editors right to any section.
 * Note that YACS has been built-in features to ping information between servers, and to let surfers comment and
 * discuss any post. Also, posts are automatically archived by weeks and by months.
 *
 * - Original content publishing - Declare managing editors as associates, ask contributing
 * editors to register as regular members. Use the subscription mechanisms to handle the list of
 * readers, and that's it. Note that YACS has built-in features to let managing editors review
 * articles submitted by contributing editors.
 *
 * - Knowledge community - Declare experts as associates, and let interested people register
 * as regular members. Experts will use YACS to structure and to share their knowledge.
 * Each article may be commented by another expert or by any member. Files and links can be attached to
 * any article by any member.
 * Note that YACS has built-in features to encourage people to share with others.
 * For example, each profile lists last contributions of this user.
 *
 * - Passionate members - When several people have a common category of interest, they can decide
 * to setup a YACS server to support their passion, and become associates of this community.
 * Other interested people can register as regular member or as simple e-mail subscribers.
 * Note that YACS has been built-in features to share information between servers, enabling
 * one community to be extended over time.
 *
 * Web masters that are discovering the yacs environment may use following guidelines
 * to reduce the learning curve. We have assumed that you have a quite strong background
 * in php, html, etc.
 *
 * In a ideal world, the three main layers of a good-looking application
 * (i.e., presentation, behaviour, and storage) would be clearly separated.
 * We have tried to support this principle as much as possible with YACS.
 * However, this (good) rule has explicitly been violated where we had to balance with simplicity.
 *
 * [title]The presentation layer[/title]
 *
 * The presentation layer is devoted to php pages. No magic, no complicated
 * template, php has been designed from the outset to do that. Create a subdirectory,
 * put some php files in it, change the main index.php to link it to these files,
 * and that's it.
 *
 * [subtitle]Skins[/subtitle]
 * People that want to make most of their pages look similar will use skins.
 * Once again, there is no magic here. The basic mechanism is to declare
 * a Skin class at the beginning of your file, to assign the page content to some
 * variables, and to include a common php file to deliver the final rendering.
 * To ease the process, we have created two functions. Basically, you only
 * have to call [code]load_skin()[/code] and [code]render_skin()[/code] to do it. Look at the code in
 * the main index.php, this should appear clearly.
 *
 * Do you want to develop your own skin? Fabulous! As a starting point
 * you can copy and rename files from the skins/skeleton directory.
 * Then change the skin name through the configuration panel at control/configure.php and start to tune your
 * new skin until success.
 *
 * See also: [script]skins/index.php[/script]
 *
 * [subtitle]Codes and Smileys[/subtitle]
 * By default text submitted to any yacs server is filtered to protect from hackers.
 * Also, a special set of safe codes are available to beautify your posts.
 *
 * See: [link]codes/index.php[/link] and [link]smileys/index.php[/link]
 *
 * [subtitle]Boxes[/subtitle]
 * What is static and what is dynamic in yacs? Numerous web systems are offering a
 * administration interface to add some boxes on the left or on the right or the main index page.
 * But yacs has no specific administration interface. Boxes are coming from ordinary items
 * of information, articles or whatever.
 *
 * During the setup of the server several sections and categories are created to hold or to flag
 * pages that have to be put in some boxes.
 *
 * By posting articles into following sections you will populate the front page of your server:
 * - 'covers' - the last published article in this section is used as the main cover of your server
 * - 'navigation_boxes' - each article is displayed into its own box on every page of your server
 * - 'extra_boxes' - each article is displayed into its own box, at the site front page only
 * - 'menus' - the last published article in this section is used as the main menu of your server
 *
 * By linking existing articles to following categories you will populate some lists:
 * - i18n:c('featured') - list featured articles
 *
 * See also: [link]sections/index.php[/link], [link]categories/index.php[/link]
 * (you will have to be logged as an associate to view special sections and categories)
 *
 * [subtitle]Blogging[/subtitle]
 * Well, we are quite proud of it: yacs has been fully tested with w.bloggar, and the combination is really fun and handy.
 * Give it a try!
 *
 * See also: [script]services/blog.php[/script], [link]http://wbloggar.com/[/link]
 *
 * [subtitle]Localization[/subtitle]
 * What about localization? In previous developments I used to have a separate
 * file per language to store all strings for this language. While this design
 * has proven to be efficient, it adds a big overhead to the software developer.
 *
 * In yacs, a lightweight mechanism has been selected for localization. When you need
 * a string, declare all variations of this string, but use only one. Take a look
 * at [code]i18n::s()[/code] in [script]shared/global.php[/script] to understand it. The big advantage of this design
 * is that all languages are available at the same time. You may even have some sections
 * of your server in english, and other sections in your native language if you want.
 *
 * See also: [script]shared/global.php[/script]
 *
 * [title]The behaviour layer[/title]
 *
 * As you main know ;-) the behaviour layer of a web server is limited to answering
 * to un-related queries of web components. Therefore, talking about the behaviour
 * of a web server is equivalent to discuss options on submitting requests to the server.
 *
 * [subtitle]Addressing space[/subtitle]
 * Some people have a tendancy to limit the number of scripts, and use complete sets of parameters
 * to build dynamic pages. To handle articles, they will have a single script called
 * articles.php. To create, edit or view an article, they may use something like 'articles.php?op=new',
 * 'articles.php?op=edit&id=12' and 'articles.php?op=view&id=12' respectively.
 *
 * We prefer to create numerous scripts with one or zero parameters.
 * One important reason is that you have to build complete paths to let search engines index your pages
 * extensively. To create, edit or view an article, we are using respectively:
 * 'articles/edit.php', 'articles/edit.php/12', and 'articles/view.php/12'.
 *
 * [subtitle]Module programming interface[/subtitle]
 *
 * Well, apart from common declarations and libraries placed into the shared directory, and of the skin stuff located
 * into the skins directory, there is no special programming interface into yacs.
 *
 * To create you own module, you will to create a new directory and to put callable scripts in it.
 * Of course, it helps to look at existing modules to respect coding standards as much as possible.
 *
 * [subtitle]Overlays[/subtitle]
 * If, during some analysis of your needs, you end up with the conclusion that adding some fields to an
 * article would suffice, then consider overlays as the faster track to deliver.
 *
 * For example, overlays can be used to implement cooking recipes efficiently (i.e., with one single script)
 * while retaining all feature available for articles (e.g., images, files, links, codes, etc.)
 *
 * See: [script]overlays/index.php[/script] for a more complete description of overlays
 *
 * [subtitle]Links and Hooks[/subtitle]
 * Once your scripts are running as expected, you will have to link them with other scripts, and with the site front page.
 * The most simple action is to update the main menu of your site (section 'menus') or to add
 * boxes with adequate information (sections 'navigation_boxes' or 'extra_boxes').
 *
 * You can also use existing hooks to trigger your scripts.
 * This is a very powerful, while straightforward, mechanism, to extend yacs capabilities.
 * For example, the script [script]control/setup.php[/script] has a hook to setup database tables
 * that are not part of the basic set of yacs tables.
 * Likely, the main configuration panel [script]control/index.php[/script] may be extended
 * and reference some of your configuration scripts if necessary.
 *
 * See: [script]control/scan.php[/script] for a more complete description of hooks
 *
 * [title]The storage layer[/title]
 *
 * [subtitle]The database abstraction[/subtitle]
 * Usually, we have separated most of the code related to the database in dedicated php files.
 * This has allowed us to limit the number of scripts interfacing with mySQL.
 *
 * Consider for example lists of articles. They only differ in the amount of information displayed,
 * in the order of rows, and in the number of rows. Moreover, it is very useful to display the same
 * list into several different pages.
 *
 * To make things clearer we have created static classes to support such queries. Do you want a list
 * of the newest articles? Call [code]Articles::list_by_date()[/code] and you will fetch a nice array of labels and related urls.
 *
 * See: [script]articles/articles.php[/script],
 * [script]sections/sections.php[/script], [script]categories/categories.php[/script],
 * [script]files/files.php[/script], [script]links/links.php[/script], [script]comments/comments.php[/script],
 * [script]images/images.php[/script], [script]tables/tables.php[/script],
 * [script]users/users.php[/script]
 *
 * [subtitle]Tables maintenance[/subtitle]
 * The creation and the upgrade of the database schema is made by [script]control/setup.php[/script].
 * This script uses PHP arrays to build adequate mySQL statement.
 * For example, the table storing articles is described in [code]Articles::setup()[/code],
 * in the file [script]articles/articles.php[/script].
 *
 * One innovative feature of yacs is the ability to link items of information on-demand. Therefore,
 * the database schema is simple to handle and to extend, as explained in the next paragrah.
 *
 * [subtitle]Modules and anchors[/subtitle]
 *
 * In yacs a module is simply defined by a set of php files and by a related set of tables in the database.
 * To build a complete page, you will refer to one or several modules by including related php files.
 *
 * That's fine, but how to link modules together? Things are very easy when you are considering independant modules.
 * However, improving the content of your web server usually requires to link the many stored pieces of information.
 * On top of that, you would like to be able to reuse existing modules to link them to new ones with almost no modification.
 *
 * This is where we have introduced anchors. In yacs, anchors are master items that are related to many sub items.
 * Consider for example a section of your site. Hopefully this section will contain several articles. We will say
 * that the section is an anchor. To refer to this anchor, we will use a type (here: 'section') and an id (e.g., '12').
 * Add a colon ':' in the middle, and you will get the full anchor name: 'section:12'.
 *
 * Now that we have an anchor, it is easy to link articles to it. We simply have defined a field named 'anchor' in the article
 * table to store anchor names, and that's it.
 *
 * Six months later. You have created a brand new module named ancestors to handle genealogical data. Of course, you would like
 * to be able to publish articles for each ancestor. How to do that? Well, you will define 'ancestor' as a new type of anchor
 * in your system, and store 'ancestor'-based anchors in the articles table. You don't have to alter the articles table to do it.
 *
 * How to retrieve items related to an anchor? Just ask for it. If you want to list articles in the section 12, select
 * items where anchors = 'section:12'. If you want to list articles related to ancestor 34, select items where
 * anchors = 'ancestor:34'.
 *
 * How to retrieve an anchor related to an item? This is necessary to display a valid context around an item. For example,
 * an article related to a section may mention some introductory words on the section it is in. But an article related
 * to an ancestor may remember the salient dates for this person. Since we don't want to adapt the module that handle articles,
 * we have built a simple interface class named Anchor. This interface, which is the only one used within the articles
 * module, is implemented in derivated classes such as Category or Ancestor. Take a look at shared/anchor.php for more information.
 *
 * As a conclusion, if you want to let items behave as anchors for other modules, you will have:
 * <ul><li> to name the type of your anchor (e.g., 'mytype')</li>
 * <li> to implement the Anchor interface in one php file (e.g., 'mytype.php')</li>
 * <li> to update the load_anchor() function in shared/global.php (e.g., on 'mytype:15', prepare the adequate SELECT statement)</li>
 * </ul>
 *
 * The prefix hook is used to invoke any software extension bound as follows:
 * - id: 'help/index.php#prefix'
 * - type: 'include'
 * - parameters: none
 * Use this hook to include any text right before the main content.
 *
 * The suffix hook is used to invoke any software extension bound as follows:
 * - id: 'help/index.php#suffix'
 * - type: 'include'
 * - parameters: none
 * Use this hook to include any text right after the main content.
 *
 * @author Bernard Paques
 * @tester Lasares
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load localized strings
i18n::bind('help');

// load the skin
load_skin('help');

// the title of the page
$context['page_title'] = i18n::s('Help');

// the date of last modification
if(Surfer::is_associate())
	$context['page_details'] .= '<p class="details">'.sprintf(i18n::s('Edited %s'), Skin::build_date(getlastmod())).'</p>';

// the prefix hook for the help page
if(is_callable(array('Hooks', 'include_scripts')))
	$context['text'] .= Hooks::include_scripts('help/index.php#prefix');

// shortcuts for members
if(Surfer::is_member()) {
	$context['text'] .= Skin::build_block(i18n::s('Selected shortcuts'), 'title')
		.'<ul>'."\n"
		.'<li>'.sprintf(i18n::s('%s and change it if you like.'), Skin::build_link('users/view.php', i18n::s('Review your user profile'), 'shortcut')).'</li>'."\n";
	if(Surfer::is_associate())
		$context['text'] .= '<li>'.sprintf(i18n::s('Use the %s to populate this server.'), Skin::build_link('help/populate.php', i18n::s('Content Assistant'), 'shortcut')).'</li>'."\n"
			.'<li>'.sprintf(i18n::s('%s. Some people would say \'a new blog\'.'), Skin::build_link('sections/edit.php', i18n::s('Add a section'), 'shortcut')).'</li>'."\n";
	$context['text'] .= '<li>'.sprintf(i18n::s('%s. Or call it a \'blog entry\' if you prefer.'), Skin::build_link('articles/edit.php', i18n::s('Add a page'), 'shortcut')).'</li>'."\n"
		.'<li>'.sprintf(i18n::s('%s, the central place to manage this server.'), Skin::build_link('control/', i18n::s('Control Panel'), 'shortcut')).'</li>'."\n"
		.'<li>'.sprintf(i18n::s('%s of this site.'), Skin::build_link($context['url_to_root'], i18n::s('Go to the front page'), 'shortcut')).'</li>'."\n"
		.'</ul>'."\n";
}

// where to look for information
$context['text'] .= Skin::build_block(i18n::s('Where to look for information?'), 'title')
	.'<ul>'
	.'<li>'.Skin::build_link('sections/', i18n::s('Site map')).'</li>'
	.'<li>'.Skin::build_link('categories/', i18n::s('Categories')).'</li>'
	.'<li>'.sprintf(i18n::s('Index of most recent %1$s, %2$s, %3$s and %4$s'), Skin::build_link('articles/', i18n::s('pages')), Skin::build_link('files/', i18n::s('files')), Skin::build_link('comments/', i18n::s('threads')), Skin::build_link('users/', i18n::s('people'))).'</li>'
	.'<li>'.Skin::build_link('search.php', i18n::s('Full-text search')).'</li>'
	.'<li>'.Skin::build_link('control/', i18n::s('Control Panel')).'</li>';
if(!Surfer::is_logged() && (!isset($context['users_without_registration']) || ($context['users_without_registration'] != 'Y')))
	$context['text'] .= '<li> '.sprintf(i18n::s('%s to access more material, and to receive our newsletters'), Skin::build_link('users/edit.php', i18n::s('Register'))).'</li>';
$context['text'] .= '</ul>';

// everybody, but subscriptors, may contribute
if(!Surfer::is_logged() || Surfer::is_member()) {

	// how to contribute
	$context['text'] .= Skin::build_block(i18n::s('How to contribute?'), 'title');

	// introduce contribution links
	$context['text'] .= i18n::s('<p>You will have to be registered and authenticated to submit new articles. Then browse any section, or use your user menu, to post new material.</p>')."\n";

	$context['text'] .= '<ul>';

	// offer anonymous surfer to register
	if(!Surfer::is_logged() && (!isset($context['users_without_registration']) || ($context['users_without_registration'] != 'Y')))
		$context['text'] .= '<li>'.sprintf(i18n::s('%s to be authenticated at each visit'), Skin::build_link('users/edit.php', i18n::s('Register'))).'</li>';

	// actual contributions
	if(Surfer::is_member()) {
		$context['text'] .= '<li>'.sprintf(i18n::s('%s that will be published on this site'), Skin::build_link('articles/edit.php', i18n::s('Add a page'))).'</li>';

		$context['text'] .= '<li>'.i18n::s('While you\'re browsing, don\'t hesitate to comment visited pages, to send images or files, or to share some interesting link you may have').'</li>';
	}

	// special command to associate
	if(Surfer::is_associate())
		$context['text'] .= '<li> '.sprintf(i18n::s('%s to publish them'), Skin::build_link('articles/review.php', i18n::s('Review submitted articles'))).'</li>';

	$context['text'] .= '</ul>';
}

// how to contribute later
if(Surfer::is_member()) {

	// introduce bookmarklets
	$context['text'] .= i18n::s('<p>To install following bookmarklets, right-click over them and add them to your bookmarks. Then recall them at any time while browsing the Internet, to add content to this site.</p>')."\n".'<ul>';

	// the blogging bookmarklet uses YACS codes
	$bookmarklet = "javascript:function findFrame(f){var i;try{isThere=f.document.selection.createRange().text;}catch(e){isThere='';}if(isThere==''){for(i=0;i&lt;f.frames.length;i++){findFrame(f.frames[i]);}}else{s=isThere}return s}"
		."var s='';"
		."d=document;"
		."s=d.selection?findFrame(window):window.getSelection();"
		."window.location='".$context['url_to_home'].$context['url_to_root']."articles/edit.php?"
			."title='+escape(d.title)+'"
			."&amp;text='+escape('%22'+s+'%22%5Bnl]-- %5Blink='+d.title+']'+d.location+'%5B/link]')+'"
			."&amp;source='+escape(d.location);";
	$context['text'] .= '<li><a href="'.$bookmarklet.'">'.sprintf(i18n::s('Blog at %s'), $context['site_name']).'</a></li>'."\n";

	// the bookmarking bookmarklet
	$bookmarklet = "javascript:function findFrame(f){var i;try{isThere=f.document.selection.createRange().text;}catch(e){isThere='';}if(isThere==''){for(i=0;i&lt;f.frames.length;i++){findFrame(f.frames[i]);}}else{s=isThere}return s}"
		."var s='';"
		."d=document;"
		."s=d.selection?findFrame(window):window.getSelection();"
		."window.location='".$context['url_to_home'].$context['url_to_root']."links/edit.php?"
			."link='+escape(d.location)+'"
			."&amp;title='+escape(d.title)+'"
			."&amp;text='+escape(s);";
	$context['text'] .= '<li><a href="'.$bookmarklet.'">'.sprintf(i18n::s('Bookmark at %s'), $context['site_name']).'</a></li>'."\n";

	// end of bookmarklets
	$context['text'] .= '</ul>'."\n";

	// the command to add a side panel
	$context['text'] .= '<p>'.sprintf(i18n::s('If your browser supports side panels and javascript, click on the following link to %s.'), '<a onclick="javascript:addSidePanel()">'.i18n::s('add a blogging panel').'</a>').'</p>'."\n";

	// the actual javascript code to add a panel
	$context['page_footer'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// add a side panel to the current browser instance'."\n"
		.'function addSidePanel() {'."\n"
		.'	// a gecko-based browser: netscape, mozilla, firefox'."\n"
		.'	if((typeof window.sidebar == "object") && (typeof window.sidebar.addPanel == "function")) {'."\n"
		.'		window.sidebar.addPanel("'.strip_tags($context['site_name']).'", "'.$context['url_to_home'].$context['url_to_root'].'panel.php", "");'."\n"
		.'		alert("'.i18n::s('The panel has been added. You may have to ask your browser to make it visible (Ctrl-B for Firefox).').'");'."\n"
		.'	} else {'."\n"
		.'		// internet explorer'."\n"
		.'		if(document.all) {'."\n"
		.'			window.open("'.$context['url_to_home'].$context['url_to_root'].'panel.php?target=_main" ,"_search");'."\n"
		.'		// side panels are not supported'."\n"
		.'		} else {'."\n"
		.'			var rv = alert("'.i18n::s('Your browser do not support side panel. Do you want to upgrade to Mozilla Firefox?').'");'."\n"
		.'			if(rv)'."\n"
		.'				document.location.href = "http://www.mozilla.org/products/firefox/";'."\n"
		.'		}'."\n"
		.'	}'."\n"
		.'}'."\n"
		.'// ]]></script>'."\n";

	// the command to install a bookmaklet into internet explorer
	$context['text'] .= '<p>'.sprintf(i18n::s('If you are running Internet Explorer under Windows, click on the following link to %s triggered on right-click. Accept registry updates, and restart the browser afterwards.</p>'), Skin::build_link('articles/ie_bookmarklet.php', i18n::s('add a contextual bookmarklet'))).'</p>'."\n";

}

// everybody, but subscriptors, may contribute
if(!Surfer::is_logged() || Surfer::is_member()) {

	// how to format text in pages
	$context['text'] .= Skin::build_block(i18n::s('How to format text in pages?'), 'title')
		.i18n::s('<p>To ease the production of text YACS has a little code interpreter. You will find in pages listed below descriptions of these codes, but also examples of their visual rendering on your system.</p>')
		.'<ul>'
		.'<li>'.Skin::build_link('smileys/', i18n::s('Smileys available at this system')).'</li>'
		.'<li>'.sprintf(i18n::s('%s (bold, underline, ...)'), Skin::build_link('codes/basic.php', i18n::s('In-line'))).'</li>'
		.'<li>'.sprintf(i18n::s('%s (and shortcuts, buttons, ...)'), Skin::build_link('codes/links.php', i18n::s('Links'))).'</li>'
		.'<li>'.sprintf(i18n::s('%s (with bullets, numbered, ...)'), Skin::build_link('codes/lists.php', i18n::s('Lists'))).'</li>'
		.'<li>'.sprintf(i18n::s('%s (indentation, script, quote, ...)'), Skin::build_link('codes/blocks.php', i18n::s('Blocks'))).'</li>'
		.'<li>'.sprintf(i18n::s('%s (with headers, with grids, use CSV data, ...)'), Skin::build_link('codes/tables.php', i18n::s('Tables'))).'</li>'
		.'<li>'.sprintf(i18n::s('%s (and table of content)'), Skin::build_link('codes/titles.php', i18n::s('Titles and questions'))).'</li>'
		.'<li>'.sprintf(i18n::s('%s (cloud, locations, ...)'), Skin::build_link('codes/live.php', i18n::s('Dynamic queries'))).'</li>'
		.'<li>'.sprintf(i18n::s('%s (flags, ...)'), Skin::build_link('codes/misc.php', i18n::s('Miscelleanous codes'))).'</li>'
		.'</ul>';

}

// locate a reference server
Safe::load('parameters/scripts.include.php');
if(file_exists($context['path_to_root'].'scripts/reference/footprints.php'))
	$target = '';
elseif(isset($context['reference_server']) && $context['reference_server'])
	$target = 'http://'.$context['reference_server'].'/';
else
	$target = 'http://www.yetanothercommunitysystem.com/';

// where is the documentation
include_once '../scripts/scripts.php';
$context['text'] .= Skin::build_block(i18n::s('Get the source code?'), 'title')
	.'<ul>'
	.'<li>'.Skin::build_link($target.'scripts/', i18n::s('Server software')).'</li>'
	.'<li>'.sprintf(i18n::s('YACS %s and %s'), Skin::build_link($target.Scripts::get_url('authors'), i18n::s('authors')), Skin::build_link('scripts/view.php?script=testers', i18n::s('testers'))).'</li>'
	.'<li>'.sprintf(i18n::s('%s LGPL and variations'), Skin::build_link($target.Scripts::get_url('licenses'), i18n::s('Licenses'))).'</li>'
	.'<li>'.Skin::build_link($target, i18n::s('Contribute to the development')).'</li>'
	.'</ul>';

// the suffix hook for the help page
if(is_callable(array('Hooks', 'include_scripts')))
	$context['text'] .= Hooks::include_scripts('help/index.php#suffix');

// last resort
$context['text'] .= Skin::build_block(i18n::s('Not satisfied yet?'), 'title')
	.'<ul>'
	.'<li>'.sprintf(i18n::s('Use the %s to ask for help'), Skin::build_link('query.php', i18n::s('query form'))).'</li>'
	.'<li>'.sprintf(i18n::s('%s to look for additional support'), Skin::build_link('http://www.yetanothercommunitysystem.com/', i18n::s('Browse yetanothercommunitysystem.com'))).'</li>'
	.'<li>'.Skin::build_link('http://www.google.com/', i18n::s('Search at Google')).'</li>'
	.'</ul>';

// render the skin
render_skin();

?>