<?php
/**
 * help to build final web pages
 *
 * This is a library of function to be used from within skin templates.
 *
 * This template implements following access keys at all pages:
 * - hit 1 to jump to the front page of the site
 * - hit 2 to skip the header and jump to the main area of the page
 * - 9 to go to the control panel
 * - 0 to go to the help page
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author Rod
 * @tester Agnes
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

 Class Page {

	/**
	 * start page body
	 *
	 * This function generates a &lt;body&gt; tag with id and class.
	 * The id is the skin variant, as found in [code]$context['skin_variant'][/code].
	 * The class is set to 'extra' if [code]$context['extra'][/code] is not empty.
	 *
	 */
	function body() {
		global $context;

		// body id is derived from skin variant
		$id = '';
		if($context['skin_variant'])
			$id = ' id="'.$context['skin_variant'].'"';

		// page tools
		if(count($context['page_tools']) > 0)
			$context['extra_prefix'] .= Skin::build_box(i18n::s('Tools'), Skin::finalize_list($context['page_tools'], 'tools'), 'extra', 'page_tools');

		// we do have some extra content to render
		$class = '';
		if($context['extra_prefix'] || $context['extra'])
			$class = ' class="extra"';

		// start the body
		echo '<body'.$id.$class.'>'."\n";

		// shortcuts for text readers
		echo '<p class="away">';

		// skip header -- access key 2
		if(is_callable(array('i18n', 's')))
			echo '<a href="#main_panel" accesskey="2">'.i18n::s('Skip to main content').'</a> ';

		// help page -- access key 0
		if(is_callable(array('i18n', 's')))
			echo '<a href="'.$context['url_to_root'].'help.php" accesskey="0">'.i18n::s('Help').'</a> ';

		// control panel -- access key 9
		if(is_callable(array('i18n', 's')))
			echo '<a href="'.$context['url_to_root'].'control/" accesskey="9">'.i18n::s('Control Panel').'</a> ';

		// end of shortcuts
		echo '</p>'."\n";
	}

	/**
	 * show bread crumbs
	 *
	 * Show the content of [code]$content['path_bar'][/code] as a list of links.
	 *
	 * The length of the list depends of the value of the first parameter:
	 * - 0 - prefix the list with a link to the front page
	 * - 1 - use the list as it is
	 * - 2 - remove first level of the list
	 * - n - remove n-1 levels of the list
	 *
	 * @param int index of the first link to display from list
	 * @param boolean TRUE to display the site slogan when at top level, FALSE otherwise
	 * @return a string to be send to the browser
	 */
	function bread_crumbs($start_level=1, $with_slogan=FALSE) {
		global $context;

		// add a link to the front page
		if(!$start_level && count($context['path_bar']) && is_callable(array('i18n', 's'))) {
			$context['path_bar'] = array_merge(array($context['url_to_root'] => i18n::s('Home')), $context['path_bar']);
		}

		// remove top levels, if required to do so
		if(count($context['path_bar'])) {
			while($start_level-- > 1)
				array_shift($context['path_bar']);
		}

		// actually render bread crumbs
		if(count($context['path_bar']))
				echo Skin::build_list($context['path_bar'], 'crumbs')."\n";

		// no bread crumbs
		elseif($with_slogan) {

			// display site slogan instead
			if(isset($context['site_slogan']))
				echo '<p id="crumbs">'.$context['site_slogan'].'</p>'."\n";

			// fix the layout
			else
				echo '<p id="crumbs">&nbsp;</p>';
		}

	}

	/**
	 * send the main content of the page
	 *
	 * @param boolean TRUE to display the page menu, FALSE otherwise
	 */
	function content($with_page_menu=TRUE) {
		global $context;

		// display the prefix, if any
		if(isset($context['prefix']) && $context['prefix'])
			echo $context['prefix']."\n";

		// display the title
		if(isset($context['page_title']) && $context['page_title'])
			echo Skin::build_block($context['page_title'], 'page_title');

		// display error messages, if any
		if(is_callable(array('Skin', 'build_error_block')))
			echo Skin::build_error_block();

		// display the page image, if any
		if(isset($context['page_image']) && $context['page_image'])
			echo ICON_PREFIX.'<img src="'.$context['page_image'].'" class="icon" alt=""'.EOT.ICON_SUFFIX;

		// render and display the content, if any
		echo $context['text'];
		$context['text'] = '';

		// display the dynamic content, if any
		if(is_callable('send_body'))
			send_body();

		// maybe some additional text has been created in send_body()
		echo $context['text'];

		// tags, if any
		if($context['page_tags']) {
			$tags = explode(',', $context['page_tags']);
			$line = '';
			foreach($tags as $tag) {
				if($category = Categories::get_by_keyword(trim($tag)))
					$line .= Skin::build_link(Categories::get_url($category['id'], 'view', trim($tag)), trim($tag), 'basic').' ';
				else
					$line .= trim($tag).' ';
			}
			$context['page_details'] = '<p class="tags">'.sprintf(i18n::s('Tags: %s'), trim($line)).'</p>'
				.$context['page_details']."\n";
		}

		// display page details, if any
		if(isset($context['page_details']) && $context['page_details'])
			echo '<div id="page_details">'.$context['page_details']."</div>\n";

		// display the menu bar
		if($with_page_menu && isset($context['page_menu']) && (@count($context['page_menu']) > 0))
			echo Skin::build_list($context['page_menu'], 'page_menu');

		// display the suffix, if any
		if(isset($context['suffix']) && $context['suffix'])
			echo $context['suffix']."\n";

		// debug output, if any
		if(is_array($context['debug']) && count($context['debug']))
			echo "\n".'<ul id="debug">'."\n".'<li>'.implode('</li>'."\n".'<li>', $context['debug']).'</li>'."\n".'</ul>'."\n";

	}

	/**
	 * show the extra panel of the page
	 *
	 */
	function extra_panel() {
		global $context;

		// display complementary information, if any
		if($context['extra_prefix'] || $context['extra'])
			echo '<div id="extra_panel">'.$context['extra_prefix'].$context['extra']."</div>\n";

	}

	/**
	 * echo the standard footer
	 *
	 * Note that this one does not echo $context['page_footer'], and you have
	 * to do it yourself.
	 *
	 * @param string footer prefix, if any
	 * @param string footer suffix, if any
	 */
	function footer($prefix='', $suffix='') {
		global $context;

		// the last paragraph
		echo '<p>';

		// add footer prefix
		echo $prefix;

		// execution time and surfer name, for logged user only (not for indexing robots!)
		if(is_callable(array('Surfer', 'get_name')) && Surfer::get_name() && is_callable(array('i18n', 's'))) {
			$execution_time = round(get_micro_time() - $context['start_time'], 2);
			echo sprintf(i18n::s('Page prepared in %.2f seconds for %s'), $execution_time, ucwords(Surfer::get_name())).BR;
		}

		// site copyright
		if(isset($context['site_copyright']))
			echo '&copy; '.$context['site_copyright']."\n";

		// a command to authenticate
		if(is_callable(array('Surfer', 'is_logged')) && !Surfer::is_logged() && is_callable(array('i18n', 's')))
			echo ' - '.Skin::build_link('users/login.php', i18n::s('login'), 'basic').' ';

		// about this site
		if(is_callable(array('i18n', 's')) && is_callable(array('Articles', 'get_url')))
			echo ' - '.Skin::build_link(Articles::get_url('about'), i18n::s('about this site'), 'basic').' ';

		// privacy statement
		if(is_callable(array('i18n', 's')) && is_callable(array('Articles', 'get_url')))
			echo ' - '.Skin::build_link(Articles::get_url('privacy'), i18n::s('privacy statement'), 'basic').' ';

		// a reference to YACS
		if(is_callable(array('i18n', 's')) && ($context['host_name'] != 'www.yetanothercommunitysystem.com'))
			echo ' - '.sprintf(i18n::s('powered by %s'), Skin::build_link(i18n::s('http://www.yetanothercommunitysystem.com/'), i18n::s('yacs'), 'external'));

		// all our feeds
		if(is_callable(array('i18n', 's')))
			echo ' - '.Skin::build_link('feeds/', i18n::s('Information channels'), 'basic');

		// add footer suffix
		echo $suffix;

		// end of the last paragraph
		echo '</p>'."\n";
	}

	/**
	 * build a header panel with background
	 *
	 * This function builds a nice header panel that may include following elements:
	 * - a background image (that can be selected randomly in a set)
	 * - site name
	 * - site slogan
	 * - top-level tabs
	 *
	 * This function can prove handy to change background images randomly.
	 * To put this in place add a couple of images to the sub-directory #"images## of the current skin.
	 * Then list file in an array and pass this as first parameter of this function.
	 * For example:
	 * [php]
	 * // three images to alternate, all placed in sub-directory images
	 * $images = array('1.jpg', '2.jpg', '3.jpg');
	 *
	 * // draw the header panel
	 * Page::header_panel($images);
	 * [/php]
	 *
	 * When tabs are activated, which is the default behavior, it is useless to call Page::tabs() separately.
	 *
	 * @param mixed either an image, or an array of images
	 * @param string image attributes
	 * @param boolean TRUE to display site name, FALSE otherwise
	 * @param boolean TRUE to display site slogan, FALSE otherwise
	 * @param boolean TRUE to display tabs, FALSE otherwise
	 */
	function header_panel($images=NULL, $attributes='top left repeat-x', $with_name=TRUE, $with_slogan=TRUE, $with_tabs=TRUE) {
		global $context;

		// put an image in panel background
		if($images) {

			// select a random image
			if(is_array($images))
				$image = $images[ array_rand($images) ];

			// a fixed image
			else
				$image = $images;

			// get a random index in table
			$index = array_rand($images);

			// the header panel comes before everything
			echo '<div id="header_panel" style="background: transparent url('.$context['url_to_root'].$context['skin'].'/images/'.$images[$index].') '.$attributes.';">'."\n";

		// no image in the background
		} else
			echo '<div id="header_panel">'."\n";

		// the site name -- can be replaced, through CSS, by an image -- access key 1
		if($context['site_name'] && $with_name)
			echo '<p id="header_title"><a href="'.$context['url_to_root'].'" title="'.encode_field(i18n::s('Return to front page')).'" accesskey="1"><span>'.$context['site_name'].'</span></a></p>'."\n";

		// site slogan -- can be replaced, through CSS, by an image
		if(isset($context['site_slogan']) && $with_slogan)
			echo '<p id="header_slogan"><span>'.$context['site_slogan']."</span></p>\n";

		// horizontal tabs
		if($with_tabs)
			Page::tabs();

		// end of the header panel
		echo '</div>'."\n";

	}

	/**
	 * show the side panel of the page
	 *
	 * @param boolean TRUE to also include extra information, FALSE otherwise
	 */
	function side($with_extra=FALSE) {
		global $context;

		// the dynamic menu is displayed at all pages, on regular usage
		$cache_id = 'skins/page.php#menu';
		$text = '';
		if(file_exists($context['path_to_root'].'parameters/switch.on') && (!$text =& Cache::get($cache_id)) && is_callable(array('Articles', 'get')) && is_callable(array('Codes', 'beautify'))) {
			if($item =& Articles::get('menu'))
				$text =& Skin::build_box(Codes::beautify_title($item['title']), Codes::beautify($item['description']), 'navigation', 'main_menu');
			Cache::put($cache_id, $text, 'articles');
		}
		echo $text;

		// the user menu, in a navigation box
		if(is_callable(array('Users', 'get_url')) && ($menu = Skin::build_user_menu('basic')) && is_callable(array('i18n', 's'))) {
			if(Surfer::is_logged()) {
				$box_title = Surfer::get_name();
				$box_url = Users::get_url(Surfer::get_id(), 'view', Surfer::get_name());
				$box_popup = i18n::s('See your user profile');
			} else {
				$box_title = i18n::s('User login');
				$box_url = '';
				$box_popup = '';
			}
			echo Skin::build_box($box_title, $menu, 'navigation', 'user_menu', $box_url, $box_popup)."\n";
		}

		// complementary information, if any and if required to do so
		if($with_extra && ($context['extra_prefix'] || $context['extra']))
			echo $context['extra_prefix'].$context['extra']."\n";

		// categories to display among navigation boxes, after end of setup, and if we have access to the database
		$cache_id = 'skins/page.php#navigation';
		$text = '';
		if(file_exists($context['path_to_root'].'parameters/switch.on') && (!$text =& Cache::get($cache_id)) && !defined('NO_MODEL_PRELOAD')) {

			// navigation boxes in cache
			global $global_navigation_box_index;
			if(!isset($global_navigation_box_index))
				$global_navigation_box_index = 20;
			else
				$global_navigation_box_index += 20;

			// the maximum number of boxes is a global parameter
			if(!isset($context['site_navigation_maximum']) || !$context['site_navigation_maximum'])
				$context['site_navigation_maximum'] = 7;

			// navigation boxes from the dedicated section
			$anchor = Sections::lookup('navigation_boxes');

			if($anchor && ($rows = Articles::list_by_date_for_anchor($anchor, 0, $context['site_navigation_maximum'], 'boxes'))) {

				// one box per article
				foreach($rows as $title => $attributes)
					$text .= "\n".Skin::build_box($title, $attributes['content'], 'navigation', $attributes['id'])."\n";

				// cap the total number of navigation boxes
				$context['site_navigation_maximum'] -= count($rows);
			}

			// navigation boxes made from categories
			include_once $context['path_to_root'].'categories/categories.php';
			if($categories = Categories::list_by_date_for_display('site:all', 0, $context['site_navigation_maximum'], 'raw')) {

				// one box per category
				foreach($categories as $id => $attributes) {

					// box title
					$label = Skin::strip($attributes['title']);

					// link to the category page from box title
					if(is_callable(array('i18n', 's')))
						$label =& Skin::build_box_title($label, Categories::get_url($attributes['id'], 'view', $attributes['title']), i18n::s('View the category'));

					// list sub categories
					$items = Categories::list_by_date_for_anchor('category:'.$id, 0, COMPACT_LIST_SIZE, 'compact');

					// list linked articles
					include_once $context['path_to_root'].'links/links.php';
					if($articles = Members::list_articles_by_date_for_anchor('category:'.$id, 0, COMPACT_LIST_SIZE, 'compact')) {
						if($items)
							$items = array_merge($items, $articles);
						else
							$items = $articles;

					// else list links
					} elseif($links = Links::list_by_date_for_anchor('category:'.$id, 0, COMPACT_LIST_SIZE, 'compact')) {
						if($items)
							$items = array_merge($items, $links);
						else
							$items = $links;
					}

					// display what has to be displayed
					if($items)
						$text .= Skin::build_box($label, Skin::build_list($items, 'articles'), 'navigation')."\n";

				}
			}

			// save on requests
			Cache::put($cache_id, $text, 'various');

		}
		echo $text;

		// append other items to the navigation panel
		if($context['navigation'])
			echo $context['navigation']."\n";

		// list pages visited previously at this site, if any
		if(isset($_SESSION['visited']) && count($_SESSION['visited']) && is_callable(array('i18n', 's'))) {

			// box title
			$title = i18n::s('Visited');

			// box content as a compact list
			$text =& Skin::build_list($_SESSION['visited'], 'compact');

			// the list of recent pages
			echo "\n".Skin::build_box($title, $text, 'navigation', 'visited_pages')."\n";
		}

	}

	/**
	 * show site tabs
	 *
	 * Tabs are derivated by top-level sections of the server.
	 *
	 * Prefix and suffix tabs can be provided as links packaged in arrays of ( $url => array($label_prefix, $label, $label_suffix, $link_class) )
	 *
	 * @param boolean TRUE to add a tab to the front page, FALSE otherwise
	 * @param boolean TRUE to reverse order of tabs, FALSE otherwise
	 * @param array of links to be used as tabs before the regular set
	 * @param array of links to be used as tabs after the regular set
	 */
	function tabs($with_home=TRUE, $with_reverse=FALSE, $prefix=NULL, $suffix=NULL) {
		global $context;

		// cache where possible, and only on a live server
		$cache_id = 'skins/page.php#tabs';
		$text = '';
		if(file_exists($context['path_to_root'].'parameters/switch.on') && (!$text =& Cache::get($cache_id))) {

			// an array of tabs
			$site_bar = array();

			// prefix tabs, if any
			if(is_array($prefix) && count($prefix))
				$site_bar = array_merge($site_bar, $prefix);

			// the first tab links to the front page
			if($with_home && is_callable(array('i18n', 's')))
				$site_bar = array_merge($site_bar, array($context['url_to_root'] => array('', i18n::s('Home'), '', 'home')));

			// default number of sections to list
			if(!isset($context['root_sections_count_at_home']) || ($context['root_sections_count_at_home'] < 1))
				$context['root_sections_count_at_home'] = 5;

			// query the database to get dynamic tabs; do not report on error
			if(is_callable(array('Sections', 'list_by_title_for_anchor')) && ($items = Sections::list_by_title_for_anchor(NULL, 0, $context['root_sections_count_at_home'], 'tabs', NULL, TRUE)))
				$site_bar = array_merge($site_bar, $items);

			// suffix tabs, if any
			if(is_array($suffix) && count($suffix))
				$site_bar = array_merge($site_bar, $suffix);

			// the skin will reverse the order
			if($with_reverse)
				$site_bar = array_reverse($site_bar);

			// shape tabs
			$text =& Skin::build_list($site_bar, 'tabs')."\n";
			Cache::put($cache_id, $text, 'sections');
		}
		echo $text;

	}

	/**
	 * identify top level focus tab
	 *
	 * @param string prefix that applies
	 * @return string for example: 'tab_home', or 'tab_section_123', or NULL
	 */
	function top_focus($prefix='tab_') {
		global $context;

		// not sure there is a focus
		$output = NULL;

		// we are at the topmost page
		if(($context['script_url'] == '/index.php') && ($context['url_to_root'] != '/'))
			$output = 'slash';

		// focus on home tab
		elseif($context['skin_variant'] == 'home')
			$output = 'home';

		// else get top level
		elseif(isset($context['current_focus']) && count($context['current_focus']))
			$output = str_replace(':', '_', $context['current_focus'][0]);

		// prepend the prefix
		if($output && $prefix)
			$output = $prefix.$output;

		// done
		return $output;

	}

}
?>