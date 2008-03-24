<?php
/**
 * the database abstraction layer for categories
 *
 * [title]Layouts[/title]
 *
 * In YACS, categories are lists of elements: users, sections, sub-categories,
 * and articles. Each category can be configured separately to layout these
 * lists appropriately.
 *
 * More specifically, every category record in the database has specific fields
 * to remember which layout should be used for each class of items.
 *
 * [title]Overlays[/title]
 *
 * Categories can be overlaid, like articles or user profiles. Select which
 * script you want to use to overlay sub-categories.
 *
 * [title]How to manage options for categories?[/title]
 *
 * The options field is a convenient place to save attributes for any category without extending the database schema.
 *
 * You can combine any of following keywords in the field for options, with the separator (spaces, tabs, commas) of your choice:
 *
 * [*] [code]articles_by_title[/code] - Order pages by alphabetical order instead of using edition time information.
 *
 * [*] [code]categories_by_title[/code] - Order sub-categories by alphabetical order instead of using edition time information.
 *
 * [*] [code]files_by_title[/code] - Order files by alphabetical order instead of using edition time information.
 * To be used jointly with '[code]with_files[/code]', to activate the posting of files.
 *
 * [*] [code]layout_as_inline[/code] - A special layout to list the content of sub-categories.
 * Each sub-category, with related pages, is a section bow in the main panel.
 *
 * @see categories/layout_categories_as_inline.php
 *
 * [*] [code]layout_as_yahoo[/code] - A 2-column and decorated layout, with up to 3 entries per category.
 *
 * @see categories/layout_categories_as_yahoo.php
 *
 * [*] [code]links_by_title[/code] - Order links by alphabetical order instead of using edition time information.
 *
 * [*] [code]skin_&lt;xxxx&gt;[/code] - Select one skin explicitly.
 * Use this option to apply a specific skin to a category page.
 *
 * [*] [code]variant_&lt;xxxx&gt;[/code] - Select one skin variant explicitly.
 * Usually the variant '[code]categories[/code]' is used throughout categories.
 * This can be changed to '[code]xxxx[/code]' by using the option [code]variant_&lt;xxxx&gt;[/code].
 * Then the underlying skin may adapt to this code by looking at [code]$context['skin_variant'][/code].
 * Basically, use variants to change the rendering of individual categories of your site, if the skin allows it.
 *
 * [*] [code]with_comments[/code] - This category can be commented.
 * By default YACS does not allow comments in categories.
 * However, in some situations you may ned to capture surfers feed-back directly at some particular category.
 * Set the option [code]with_comments[/code] to activate the commenting system.
 * Please note that threads based on categories differ from threads based on articles.
 * For example, they are not listed at the front page.
 *
 * [*] [code]with_files[/code] - Files can be attached to this category.
 * By default within YACS libraries of files are supposed to be based on articles and attached files.
 * But you may have to create a special set of files out of a category.
 * If this is the case, add the option [code]with_files[/code] manually and upload shared files.
 *
 * [*] [code]no_links[/code] - Links cannot be posted to this category.
 * You can also create lists of bookmarks based on articles and attached links.
 * But you may have to create a special set of bookmarks out of a category.
 *
 *
 * [title]Where to display categories?[/title]
 *
 * Each category can be embedded, or displayed, into one other page.
 * Use this feature to display categories as sidebars throughout your site.
 *
 * Each sidebar will feature:
 * - the category title (this is the box title)
 * - a limited and compact list of top pages in the category
 * - if there is no page, a limited and compact list of top links in the category
 * - a follow-up link to the category page ('more pages')
 *
 * This is achieved through the field 'display', which is actually an anchor to the target page.
 * Following anchors are recognized for categories:
 * - 'site:all' - build one navigation box for the category, in skins/your_skin/template.php
 * - 'home:extra' - build one extra box for the category at the front page, in index.php
 * - 'home:gadget' - build one gadget box in the middle of the front page, in index.php
 * - 'article:index' - a sidebar at articles/index.php
 * - 'file:index' - a sidebar at files/index.php
 * - 'link:index' - a sidebar at links/index.php
 * - 'section:index' - a sidebar at sections/index.php
 * - 'user:index' - a sidebar at users/index.php
 *
 *
 * [title]How to order sub-categories?[/title]
 *
 * Usually sub-categories are ranked by edition date, with the most recent page coming first.
 * You can change this 'natural' order by modifying the value of the rank field.
 *
 * What is the result obtained, depending on the value set?
 *
 * [*] 10000 - This is the default value. All categories created by YACS are ranked equally.
 *
 * [*] Less than 10000 - Useful to create sticky and ordered subcategories.
 * Sticky, since these pages will always come first.
 * Ordered, since the lower rank values come before higher rank values.
 * Pages that have the same rank value are ordered by dates, with the newest item coming first.
 * This lets you arrange precisely the order of sticky categories.
 *
 * [*] More than 10000 - To reject categories at the end of lists.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester ThierryP
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Categories {

	/**
	 * check if new categories can be added
	 *
	 * This function returns TRUE if categories can be added to some place,
	 * and FALSE otherwise.
	 *
	 * The function prevents the creation of new categories when:
	 * - the global parameter 'users_without_submission' has been set to 'Y'
	 * - provided item has been locked
	 * - item has some option 'no_categories' that prevents new categories
	 * - the anchor has some option 'no_categories' that prevents new categories
	 *
	 * Then the function allows for new categories when:
	 * - surfer has been authenticated as a valid member
	 * - or parameter 'users_without_teasers' has not been set to 'Y'
	 *
	 * Then, ultimately, the default is not allow for the creation of new
	 * categories.
	 *
	 * @param object an instance of the Anchor interface, if any
	 * @param array a set of item attributes, if any
	 * @return TRUE or FALSE
	 */
	function are_allowed($anchor=NULL, $item=NULL) {
		global $context;

		// categories are prevented in item
		if(isset($item['options']) && is_string($item['options']) && preg_match('/\bno_categories\b/i', $item['options']))
			return FALSE;

		// categories are prevented in anchor
		if(is_object($anchor) && is_callable(array($anchor, 'has_option')) && $anchor->has_option('no_categories'))
			return FALSE;

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// submissions have been disallowed
		if(isset($context['users_without_submission']) && ($context['users_without_submission'] == 'Y'))
			return FALSE;

		// surfer has special privileges
		if(Surfer::is_empowered())
			return TRUE;

		// surfer screening
		if(isset($item['active']) && ($item['active'] == 'N') && !Surfer::is_empowered())
			return FALSE;
		if(isset($item['active']) && ($item['active'] == 'R') && !Surfer::is_logged())
			return FALSE;

		// anchor has been locked
		if(is_object($anchor) && is_callable(array($anchor, 'has_option')) && $anchor->has_option('locked'))
			return FALSE;

		// item has been locked
		if(isset($item['locked']) && is_string($item['locked']) && ($item['locked'] == 'Y'))
			return FALSE;

		// authenticated members are allowed to add categories, except to categories
		if(Surfer::is_member() && (!is_object($anchor) || (strpos($anchor->get_reference(), 'category:') !== 0)))
			return TRUE;

		// contributions are allowed for this anchor
		if(is_object($anchor) && $anchor->is_editable())
			return TRUE;

		// anonymous contributions are allowed for this item
		if(isset($item['options']) && preg_match('/\banonymous_edit\b/i', $item['options']))
			return TRUE;

		// teasers are activated
		if(!isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			return TRUE;

		// the default is to not allow for new categories
		return FALSE;
	}

	/**
	 * build the path from top-level category
	 *
	 * @param string reference to an anchor (i.e., 'category:423')
	 * @return a string to be used in &lt;option&gt;
	 */
	function build_path($reference) {
		$anchor = Anchors::get($reference);
		if(is_object($anchor)) {
			if(preg_match('/category:(.+?)$/', $reference, $matches) && ($category =& Categories::get($matches[1])) && $category['anchor'] && ($category['anchor'] != $reference))
				return Categories::build_path($category['anchor']).'|'.strip_tags($anchor->get_title());
			return strip_tags($anchor->get_title());
		}
	}

	/**
	 * delete one category
	 *
	 * @param int the id of the category to delete
	 * @return TRUE on success, FALSE otherwise
	 */
	function delete($id) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return FALSE;

		// delete related items
		Anchors::delete_related_to('category:'.$id);

		// delete the record in the database
		$query = "DELETE FROM ".SQL::table_name('categories')." WHERE id = ".SQL::escape($id);
		if(SQL::query($query) === FALSE)
			return FALSE;

		// clear the cache for categories
		Cache::clear(array('categories', 'category:'.$id));

		// job done
		return TRUE;
	}

	/**
	 * delete all categories for a given anchor
	 *
	 * @param string the anchor to check (e.g., 'category:123')
	 * @return void
	 *
	 * @see shared/anchors.php
	 */
	function delete_for_anchor($anchor) {
		global $context;

		// seek all records attached to this anchor
		$query = "SELECT id FROM ".SQL::table_name('categories')." AS categories "
			." WHERE categories.anchor LIKE '".SQL::escape($anchor)."'";
		if(!$result =& SQL::query($query))
			return;

		// empty list
		if(!SQL::count($result))
			return;

		// silently delete everything
		while($row =& SQL::fetch($result))
			Categories::delete($row['id']);
	}

	/**
	 * duplicate all categories for a given anchor
	 *
	 * This function duplicates records in the database, and changes anchors
	 * to attach new records as per second parameter.
	 *
	 * @param string the source anchor
	 * @param string the target anchor
	 * @return int the number of duplicated records
	 *
	 * @see shared/anchors.php
	 */
	function duplicate_for_anchor($anchor_from, $anchor_to) {
		global $context;

		// look for records attached to this anchor
		$count = 0;
		$query = "SELECT * FROM ".SQL::table_name('categories')." WHERE anchor LIKE '".SQL::escape($anchor_from)."'";
		if(($result =& SQL::query($query)) && SQL::count($result)) {

			// the list of transcoded strings
			$transcoded = array();

			// process all matching records one at a time
			while($item =& SQL::fetch($result)) {

				// a new id will be allocated
				$old_id = $item['id'];
				unset($item['id']);

				// target anchor
				$item['anchor'] = $anchor_to;

				// actual duplication
				if($new_id = Categories::post($item)) {

					// more pairs of strings to transcode
					$transcoded[] = array('/\[category='.preg_quote($old_id, '/').'/i', '[category='.$new_id);

					// duplicate elements related to this item
					Anchors::duplicate_related_to('category:'.$old_id, 'category:'.$new_id);

					// stats
					$count++;
				}
			}

			// transcode in anchor
			if($anchor = Anchors::get($anchor_to))
				$anchor->transcode($transcoded);

		}

		// number of duplicated records
		return $count;
	}

	/**
	 * get one category by id
	 *
	 * @param int the id or nick name of the target category
	 * @param boolean TRUE to always fetch a fresh instance, FALSE to enable cache
	 * @return the resulting $item array, with at least keys: 'id', 'title', 'description', etc.
	 */
	function &get($id, $mutable=FALSE) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// ensure proper unicode encoding
		$id = (string)$id;
		$id = utf8::to_unicode($id);

		// strip extra text from enhanced ids '3-topic' -> '3'
		if($position = strpos($id, '-'))
			$id = substr($id, 0, $position);

		// cache previous answers
		static $cache;
		if(!is_array($cache))
			$cache = array();

		// cache hit, but only for immutable objects
		if(!$mutable && isset($cache[$id]))
			return $cache[$id];

		// select among available items -- exact match
		$query = "SELECT * FROM ".SQL::table_name('categories')." AS categories"
			." WHERE (categories.id LIKE '".SQL::escape($id)."') OR (categories.nick_name LIKE '".SQL::escape($id)."')";
		$output =& SQL::query_first($query);

		// save in cache
		if(isset($output['id']))
			$cache[$id] = $output;

		// return by reference
		return $output;
	}

	/**
	 * get one category by keyword
	 *
	 * @param string the keyword of the category
	 * @return the resulting $item array, with at least keys: 'id', 'title', 'description', etc.
	 */
	function &get_by_keyword($keyword) {
		global $context;

		// ensure proper unicode encoding
		$keyword = utf8::to_unicode($keyword);

		// select among available items
		$query = "SELECT * FROM ".SQL::table_name('categories')." AS categories"
			." WHERE categories.keywords = '".SQL::escape($keyword)."' LIMIT 1";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get one category by title
	 *
	 * @param string the title of the category
	 * @return the resulting $item array, with at least keys: 'id', 'title', 'description', etc.
	 */
	function &get_by_title($title) {
		global $context;

		// ensure proper unicode encoding
		$title = utf8::to_unicode($title);

		// select among available items
		$query = "SELECT * FROM ".SQL::table_name('categories')." AS categories"
			." WHERE categories.title = '".SQL::escape($title)."' OR categories.path = '".str_replace('/', '|', $title)."'";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get the most read category
	 *
	 * Only categories matching following criteria are returned:
	 * - category is visible (active='Y')
	 * - category is restricted (active='R'), but surfer is a logged user
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @return the resulting $item array, with at least keys: 'id', 'title', 'description', etc.
	 */
	function &get_most_read() {
		global $context;

		// select among active and restricted items
		$where = "categories.active='Y'";
		if(Surfer::is_member())
			$where .= " OR categories.active='R'";

		// only consider live categories
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where = '('.$where.')'
			.' AND ((categories.expiry_date is NULL)'
			."	OR (categories.expiry_date <= '".NULL_DATE."') OR (categories.expiry_date > '".$now."'))";

		// look in the database
		$query = "SELECT * FROM ".SQL::table_name('categories')." AS categories"
			." WHERE ".$where
			." ORDER BY categories.hits DESC, categories.title LIMIT 0,1";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get the newest category
	 *
	 * Only categories matching following criteria are returned:
	 * - category is visible (active='Y')
	 * - category is restricted (active='R'), but surfer is a logged user
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @return the resulting $item array, with at least keys: 'id', 'title', 'description', etc.
	 */
	function &get_newest() {
		global $context;

		// select among active and restricted items
		$where = "categories.active='Y'";
		if(Surfer::is_member())
			$where .= " OR categories.active='R'";

		// only consider live categories
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where = '('.$where.')'
			.' AND ((categories.expiry_date is NULL)'
			."	OR (categories.expiry_date <= '".NULL_DATE."') OR (categories.expiry_date > '".$now."'))";

		// the list of categories
		$query = "SELECT * FROM ".SQL::table_name('categories')." AS categories"
			." WHERE ".$where
			." ORDER BY categories.edit_date DESC, categories.title LIMIT 0,1";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get categories as options of a &lt;SELECT&gt; field
	 *
	 * This function is used to assign sub-categories to categories, or articles to categories.
	 *
	 * For the assignment of articles, the argument is an array of categories already used, that won't be listed at all.
	 *
	 * For the assignment of sub-categories, the current anchor has to be highlighted in the list. But the subcategory itself
	 * should not appear in the list (self-anchoring is forbidden).
	 *
	 * Only categories matching following criteria are returned:
	 * - category is visible (active='Y')
	 * - category is restricted (active='R'), but surfer is a logged member
	 * - category is hidden (active='N'), but surfer is an associate
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @see categories/edit.php
	 * @see categories/select.php
	 *
	 * @param array a list of categories ($reference => $attributes) currently assigned to the item, or a category reference (i.e., 'category:234')
	 * @param string the anchor currently selected, if any
	 * @return the HTML to insert in the page
	 */
	function &get_options($to_avoid=NULL, $to_select=NULL) {
		global $context;

		// return the final result
		$content =& Categories::get_options_for_anchor(null, $to_avoid, $to_select);
		return $content;
	}

	/**
	 * get sub-categories as options of a &lt;SELECT&gt; field
	 *
	 * @param string an anchor reference, or NULL
	 * @param array a list of categories ($reference => $attributes) currently assigned to the item, or a category reference (i.e., 'category:234')
	 * @param string the anchor currently selected, if any
	 * @return the HTML to insert in the page
	 */
	function &get_options_for_anchor($anchor=NULL, $to_avoid=NULL, $to_select=NULL) {
		global $context;

		// an option to put the category at the root level
		$content = '<option value="">'.i18n::s('-- Root level')."</option>\n";

		// we have a default or previous setting
		$current = '';
		if(isset($to_select)) {

			// extract the nick name, if any
			$nick_name = str_replace('category:', '', $to_select);

			// create a default category if it does not exist
			if($nick_name && (!$current = Categories::lookup($nick_name))) {

				$fields = array();
				$fields['nick_name'] = $nick_name;
				$fields['title'] = ucfirst($nick_name);

				if($id = Categories::post($fields))
					$current = 'category:'.$id;
			}

		}

		// display active and restricted items
		$where = "categories.active='Y'";
		if(Surfer::is_member())
			$where .= " OR categories.active='R'";
		if(Surfer::is_associate())
			$where .= " OR categories.active='N'";
		$where = '('.$where.')';

		// limit the request to sub-categories only
		if(isset($anchor))
			$where .= " AND (categories.anchor = '".$anchor."')";

		// only consider live categories
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where .= ' AND ((categories.expiry_date is NULL)'
			."	OR (categories.expiry_date <= '".NULL_DATE."') OR (categories.expiry_date > '".$now."'))";

		// avoid weekly and monthly publications if accessed remotely
		$where .= " AND (categories.nick_name NOT LIKE 'week%') AND (categories.nick_name NOT LIKE 'month%')";

		// make an array of assigned categories
		if(is_string($to_avoid) && $to_avoid) {
			$to_avoid_as_string = $to_avoid;
			$to_avoid = array();
			$to_avoid[$to_avoid_as_string] = 'only one';
		} elseif(!is_array($to_avoid))
			$to_avoid = array();

		// skip categories already assigned
		foreach($to_avoid as $reference => $dummy) {
			$id = str_replace('category:', '', $reference);
			$where .= " AND (categories.id NOT LIKE '".$id."')";
		}

		// do not limit the query to top level
		$query = "SELECT categories.id, categories.nick_name, categories.path, categories.title"
			." FROM ".SQL::table_name('categories')." AS categories"
			." WHERE (".$where.")"
			." ORDER BY categories.path, categories.title LIMIT 0, 500";
		if(!$result =& SQL::query($query))
			return $content;

		// parse request results
		while($result && list($option_id, $option_nick_name, $option_path, $option_label) = SQL::fetch_row($result)) {

			// maybe we are in the selected line
			$selected = '';
			if($current && ($current == 'category:'.$option_id))
				$selected = ' selected="selected"';

			if($option_path) {
				$path = explode('|', $option_path);
				$label = join(CATEGORY_PATH_SEPARATOR, $path);
			} else
				$label = $option_label;

			$content .= '<option value="category:'.$option_id.'"'.$selected.'>'.$label."</option>\n";
		}

		// return the final result
		return $content;
	}

	/**
	 * build a reference to a category
	 *
	 * Depending on parameter '[code]with_friendly_urls[/code]' and on action,
	 * following results can be observed:
	 *
	 * - view - categories/view.php?id=123 or categories/view.php/123 or categorie-123
	 *
	 * - other - categories/edit.php?id=123 or categories/edit.php/123 or category-edit/123
	 *
	 * @param int the id of the category to handle
	 * @param string the expected action ('view', 'print', 'edit', 'delete', ...)
	 * @param string additional data, such as category name, if any
	 * @return string a normalized reference
	 *
	 * @see control/configure.php
	 */
	function get_url($id, $action='view', $name=NULL) {
		global $context;

		// the prefix for navigation links --name references the things to page
		if($action == 'navigate') {
			if(isset($context['with_friendly_urls']) && ($context['with_friendly_urls'] == 'Y'))
				return 'categories/view.php/'.rawurlencode($id).'/'.rawurlencode($name).'/';
			elseif(isset($context['with_friendly_urls']) && ($context['with_friendly_urls'] == 'R'))
				return 'categories/view.php/'.rawurlencode($id).'/'.rawurlencode($name).'/';
			else
				return 'categories/view.php?id='.urlencode($id).'&amp;'.urlencode($name).'=';
		}

		// select a category for an anchor
		if($action == 'select')
			return 'categories/select.php?anchor='.urlencode($id);

		// check the target action
		if(!preg_match('/^(delete|describe|edit|feed|print|view)$/', $action))
			$action = 'view';

		// normalize the link
		return normalize_url(array('categories', 'category'), $action, $id, $name);
	}

	/**
	 * list most recent categories
	 *
	 * Actually list categories by rank, then by date, then by title.
	 * If you select to not use the ranking system, categories will be ordered by date only.
	 * Else categories with a low ranking mark will appear first,
	 * and categories with a high ranking mark will be put at the end of the list.
	 *
	 * To build a simple box of the newest categories in your main index page, just use:
	 * [php]
	 * include_once 'categories/categories.php';
	 * $items = Categories::list_by_date(0, 10);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 * [/php]
	 *
	 * You can also display the newest category separately, using Categories::get_newest()
	 * In this case, skip the very first category in the list by using
	 * Categories::list_by_date(1, 10)
	 *
	 * Only categories matching following criteria are returned:
	 * - category is visible (active='Y')
	 * - category is restricted (active='R'), but surfer is a logged user
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1 - default is 0
	 * @param int the number of items to display - default is 10
	 * @param string the list variant, if any - default is 'full'
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_by_date($offset=0, $count=10, $variant='full') {
		global $context;

		// restricted to active and restricted items
		$where = "categories.active='Y'";
		if(Surfer::is_member())
			$where .= " OR categories.active='R'";

		// only consider live categories
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where = '('.$where.')'
			.' AND ((categories.expiry_date is NULL)'
			."	OR (categories.expiry_date <= '".NULL_DATE."') OR (categories.expiry_date > '".$now."'))";

		// the list of categories
		$query = "SELECT categories.* FROM ".SQL::table_name('categories')." AS categories"
			." WHERE (".$where.")"
			." ORDER BY categories.rank, categories.edit_date DESC, categories.title LIMIT ".$offset.','.$count;

		$output =& Categories::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list categories by date for a given anchor
	 *
	 * Actually list categories by rank, then by date, then by title.
	 * If you select to not use the ranking system, categories will be ordered by date only.
	 * Else categories with a low ranking mark will appear first,
	 * and categories with a high ranking mark will be put at the end of the list.
	 *
	 * Only categories matching following criteria are returned:
	 * - category is visible (active='Y')
	 * - category is restricted (active='R'), but surfer is a logged user
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_by_date_for_anchor($anchor, $offset=0, $count=10, $variant='full') {
		global $context;

		// restricted to active and restricted items
		$where = "categories.active='Y'";
		if(Surfer::is_member())
			$where .= " OR categories.active='R'";

		// only consider live categories
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where = '('.$where.')'
			.' AND ((categories.expiry_date is NULL)'
			."	OR (categories.expiry_date <= '".NULL_DATE."') OR (categories.expiry_date > '".$now."'))";

		// limit the query to one level only
		if($anchor)
			$where = "(categories.anchor LIKE '".SQL::escape($anchor)."') AND (".$where.')';
		else
			$where = "(categories.anchor LIKE '' OR categories.anchor is NULL) AND (".$where.')';

		// the list of categories
		$query = "SELECT categories.* FROM ".SQL::table_name('categories')." AS categories"
			." WHERE (".$where.")"
			." ORDER BY categories.rank, categories.edit_date DESC, categories.title LIMIT ".$offset.','.$count;

		$output =& Categories::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list categories by date for a given anchor
	 *
	 * Actually list categories by rank, then by date, then by title.
	 * If you select to not use the ranking system, categories will be ordered by date only.
	 * Else categories with a low ranking mark will appear first,
	 * and categories with a high ranking mark will be put at the end of the list.
	 *
	 * Only categories matching following criteria are returned:
	 * - category is visible (active='Y')
	 * - category is restricted (active='R'), but surfer is a logged user
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param string an anchor
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_by_date_for_display($display='site:index', $offset=0, $count=10, $variant='decorated') {
		global $context;

		// restricted to active and restricted items
		$where = "categories.active='Y'";
		if(Surfer::is_member())
			$where .= " OR categories.active='R'";

		// only consider live categories
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where = '('.$where.')'
			.' AND ((categories.expiry_date is NULL)'
			."	OR (categories.expiry_date <= '".NULL_DATE."') OR (categories.expiry_date > '".$now."'))";

		// limit the query to the target display only
		if($display)
			$where = "(categories.display LIKE '".SQL::escape($display)."') AND (".$where.')';

		// the list of categories
		$query = "SELECT * FROM ".SQL::table_name('categories')." AS categories"
			." WHERE (".$where.")"
			." ORDER BY categories.rank, categories.edit_date DESC, categories.title LIMIT ".$offset.','.$count;

		$output =& Categories::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list most read categories
	 *
	 * To build a simple box of the most read categories in your main index page, just use:
	 * [php]
	 * include_once 'categories/categories.php';
	 * $items = Categories::list_by_hits(0, 10);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 * [/php]
	 *
	 * You can also display the most read category separately, using Categories::get_most_read()
	 * In this case, skip the very first category in the list by using
	 * Categories::list_by_hits(1, 10)
	 *
	 * Only categories matching following criteria are returned:
	 * - category is visible (active='Y')
	 * - category is restricted (active='R'), but surfer is a logged user
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1 - default is 0
	 * @param int the number of items to display - default is 10
	 * @param string the list variant, if any - default is 'hits'
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_by_hits($offset=0, $count=10, $variant='hits') {
		global $context;

		// display active and restricted items
		$where = "categories.active='Y'";
		if(Surfer::is_member())
			$where .= " OR categories.active='R'";

		// only consider live categories
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where = '('.$where.')'
			.' AND ((categories.expiry_date is NULL)'
			."	OR (categories.expiry_date <= '".NULL_DATE."') OR (categories.expiry_date > '".$now."'))";

		// the list of categories
		$query = "SELECT categories.* FROM ".SQL::table_name('categories')." AS categories"
			." WHERE (".$where.")"
			." ORDER BY categories.hits DESC, categories.title LIMIT ".$offset.','.$count;

		$output =& Categories::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list categories by path
	 *
	 * Only categories matching following criteria are returned:
	 * - category is visible (active='Y')
	 * - category is restricted (active='R'), but surfer is a logged user
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 * @see services/blog.php
	 */
	function &list_by_path($offset=0, $count=10, $variant='full') {
		global $context;

		// display active and restricted items
		$where = "active='Y'";
		if(Surfer::is_logged())
			$where .= " OR active='R'";
		if(Surfer::is_empowered())
			$where .= " OR active='N'";

		// only consider live categories
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where = '('.$where.')'
			.' AND ((expiry_date is NULL) '
			.'	OR (expiry_date <= \'0000-00-01\') OR (expiry_date > \''.$now.'\'))';

		// avoid weekly publications and keywords
		$where = "(".$where.") AND nick_name NOT LIKE 'week%' "
			." AND nick_name NOT LIKE 'month%' AND keywords=''";

		// do not limit the query to top level only
		$query = "SELECT * FROM ".SQL::table_name('categories')
			." WHERE ".$where
			." ORDER BY path, title LIMIT ".$offset.', '.$count;

		$output =& Categories::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list categories by title at a given level in the categories tree
	 *
	 * Actually list categories by rank, then by title, then by edition date.
	 * If you select to not use the ranking system, categories will be ordered by title only.
	 * Else categories with a low ranking mark will appear first,
	 * and categories with a high ranking mark will be put at the end of the list.
	 *
	 * To build a simple box of the root level categories in your main index page, just use:
	 * [php]
	 * include 'categories/categories.php';
	 * if($items = Categories::list_by_title_for_anchor(NULL))
	 *	 $context['text'] .= Skin::build_list($items, '2-columns');
	 * [/php]
	 *
	 * Only categories matching following criteria are returned:
	 * - category is visible (active='Y')
	 * - category is restricted (active='R'), but surfer is a logged user
	 * - an anchor has been provided and category is hidden (active='N'), but surfer is an associate
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param the category anchor to which these categories are linked
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_by_title_for_anchor($anchor, $offset=0, $count=10, $variant='full') {
		global $context;

		// display active and restricted items
		$where = "categories.active='Y'";
		if(Surfer::is_member())
			$where .= " OR categories.active='R'";

		// list hidden categories to associates, but not on the category tree
		// they will be listed through a call to list_inactive_by_title() -- see categories/index.php
		if($anchor && Surfer::is_associate())
			$where .= " OR categories.active='N'";

		// only consider live categories
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where = "(".$where.")"
			." AND ((categories.expiry_date is NULL)"
			."	OR (categories.expiry_date <= '".NULL_DATE."') OR (categories.expiry_date > '".$now."'))";

		// limit the query to one level
		if($anchor)
			$where = "(categories.anchor LIKE '".SQL::escape($anchor)."') AND (".$where.')';
		else
			$where = "(categories.anchor='' OR categories.anchor is NULL) AND (".$where.')';

		// the list of categories
		$query = "SELECT categories.* FROM ".SQL::table_name('categories')." AS categories"
			." WHERE ".$where
			." ORDER BY categories.rank, categories.title, categories.edit_date DESC LIMIT ".$offset.','.$count;

		$output =& Categories::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list all categories for a given parent
	 *
	 * This function is suitable is you want to handle all sub-categories
	 * of a category.
	 *
	 * Only categories matching following criteria are returned:
	 * - category is visible (active='Y')
	 * - or category is restricted (active='R'), but surfer is a logged user
	 * - or category is hidden (active='N'), but surfer is an associate
	 *
	 * @param string reference to the parent category
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return array an ordered array with $url => ($prefix, $label, $suffix, $icon), else NULL on error
	 */
	function &list_for_anchor($anchor, $variant='decorated') {
		global $context;

		// limit the scope to one section
		$where = "(categories.anchor LIKE '".SQL::escape($anchor)."')";

		// display active items
		$where .= " AND (categories.active='Y'";

		// add restricted items to logged members, or if teasers are allowed
		if(Surfer::is_logged() || !isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			$where .= " OR categories.active='R'";

		// list hidden categories to associates and to editors
		if(Surfer::is_empowered())
			$where .= " OR categories.active='N'";

		// end of scope
		$where .= ')';

		// the list of categories
		$query = "SELECT categories.* FROM ".SQL::table_name('categories')." AS categories"
			." WHERE ".$where
			." ORDER BY categories.rank, categories.title, categories.edit_date DESC LIMIT 0, 500";

		$output =& Categories::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list keywords
	 *
	 * This function is used to list all keywords starting with provided letters.
	 *
	 * @param string prefix to consider
	 * @return an array of matching $keyword => $introduction
	 *
	 * @see categories/complete.php
	 */
	function &list_keywords($prefix) {
		global $context;

		// we return an array
		$output = array();

		// ensure proper unicode encoding
		$prefix = utf8::to_unicode($prefix);

		// select among available items
		$query = "SELECT keywords, introduction FROM ".SQL::table_name('categories')." AS categories"
			." WHERE categories.keywords LIKE '".SQL::escape($prefix)."%'"
			." ORDER BY keywords LIMIT 100";
		$result =& SQL::query($query);

		// populate the returned array
		while($row =& SQL::fetch($result))
			$output[ $row['keywords'] ] = $row['introduction'];

		// return by reference
		return $output;
	}

	/**
	 * list inactive categories by title
	 *
	 * Actually list categories by rank, then by title, then by edition date.
	 * If you select to not use the ranking system, categories will be ordered by title only.
	 * Else categories with a low ranking mark will appear first,
	 * and categories with a high ranking mark will be put at the end of the list.
	 *
	 * To be used by associates to access special categories (featured, etc.)
	 *
	 * Only categories matching following criteria are returned:
	 * - category is not visible (active='N')
	 * - an expiry date has been defined, and the category is now dead
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1 - default is 0
	 * @param int the number of items to display - default is 10
	 * @param string the list variant, if any - default is 'full'
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_inactive_by_title($offset=0, $count=10, $variant='full') {
		global $context;

		// for associates only
		if(!Surfer::is_associate())
			return NULL;

		// only inactive categories
		$where = "categories.active='N'";

		// or dead categories
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where = '('.$where.')'
			." OR ((categories.expiry_date > '".NULL_DATE."') AND (categories.expiry_date <= '".$now."'))";

		// the list of categories
		$query = "SELECT categories.* FROM ".SQL::table_name('categories')." AS categories"
			." WHERE (".$where.")"
			." ORDER BY categories.rank, categories.title, categories.edit_date LIMIT ".$offset.','.$count;

		$output =& Categories::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * build the site cloud
	 *
	 * This function lists tags based on created most recently.
	 *
	 * Only categories matching following criteria are returned:
	 * - category is visible (active='Y')
	 * - category is restricted (active='R'), but surfer is a logged user
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1 - default is 0
	 * @param int the number of items to display - default is 10
	 * @param string the list variant, if any - default is 'full'
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see search.php
	 */
	function &list_keywords_by_count($offset=0, $count=10, $variant='full') {
		global $context;

		// restricted to active and restricted items
		$where = "categories.active='Y'";
		if(Surfer::is_member())
			$where .= " OR categories.active='R'";

		// only consider live categories
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where = '('.$where.')'
			.' AND ((categories.expiry_date is NULL)'
			."	OR (categories.expiry_date <= '".NULL_DATE."') OR (categories.expiry_date > '".$now."'))";

		// the list of categories
		$query = "SELECT categories.* FROM ".SQL::table_name('categories')." AS categories"
			." WHERE (".$where.") AND LENGTH(categories.keywords) > 0"
			." ORDER BY categories.edit_date DESC, categories.title LIMIT ".$offset.','.$count;

		$output =& Categories::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list most recent keywords
	 *
	 * This function lists tags created most recently.
	 *
	 * Only categories matching following criteria are returned:
	 * - category is visible (active='Y')
	 * - category is restricted (active='R'), but surfer is a logged user
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1 - default is 0
	 * @param int the number of items to display - default is 10
	 * @param string the list variant, if any - default is 'full'
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see search.php
	 */
	function &list_keywords_by_date($offset=0, $count=10, $variant='full') {
		global $context;

		// restricted to active and restricted items
		$where = "categories.active='Y'";
		if(Surfer::is_member())
			$where .= " OR categories.active='R'";

		// only consider live categories
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where = '('.$where.')'
			.' AND ((categories.expiry_date is NULL)'
			."	OR (categories.expiry_date <= '".NULL_DATE."') OR (categories.expiry_date > '".$now."'))";

		// the list of categories
		$query = "SELECT categories.* FROM ".SQL::table_name('categories')." AS categories"
			." WHERE (".$where.") AND LENGTH(categories.keywords) > 0"
			." ORDER BY categories.edit_date DESC, categories.title LIMIT ".$offset.','.$count;

		$output =& Categories::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list selected categories
	 *
	 * Accept following variants:
	 * - 'cloud' - to draw a nice cloud of tags
	 * - 'compact' - to build short lists in boxes and sidebars (this is the default)
	 * - 'full' - include anchor information
	 * - 'index' - to display sub categories as well
	 * - 'raw' - to build lists with item ids
	 * - 'search' - to build result lists
	 *
	 * @param resource result of database query
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_selected(&$result, $layout='compact') {
		global $context;

		// no result
		if(!$result) {
			$output = NULL;
			return $output;
		}

		// special layouts
		if(is_object($layout)) {
			$output =& $layout->layout($result);
			return $output;
		}

		// build an array of categories
		switch($layout) {

		case 'cloud':
			include_once $context['path_to_root'].'categories/layout_categories_as_cloud.php';
			$variant =& new Layout_categories_as_cloud();
			$output =& $variant->layout($result);
			return $output;

		case 'compact':
			include_once $context['path_to_root'].'categories/layout_categories_as_compact.php';
			$variant =& new Layout_categories_as_compact();
			$output =& $variant->layout($result);
			return $output;

		case 'raw':
		case 'rpc':
			include_once $context['path_to_root'].'categories/layout_categories_as_raw.php';
			$variant =& new Layout_categories_as_raw();
			$output =& $variant->layout($result);
			return $output;

		case 'tabs':
			include_once $context['path_to_root'].'categories/layout_categories_as_tabs.php';
			$layout =& new Layout_categories_as_tabs();
			$output =& $layout->layout($result);
			return $output;

		default:
			include_once $context['path_to_root'].'categories/layout_categories.php';
			$variant =& new Layout_categories();
			$output =& $variant->layout($result, $layout);
			return $output;

		}

	}

	/**
	 * get the id of one category knowing its nick name
	 *
	 * @param string the nick name looked for
	 * @return string either 'category:&lt;id&gt;', or NULL
	 */
	function lookup($nick_name) {
		if($item =& Categories::get($nick_name))
			return 'category:'.$item['id'];
		return NULL;
	}

	/**
	 * post a new category
	 *
	 * This function populates the error context, where applicable.
	 *
	 * @param array an array of fields
	 * @return the id of the new category, or FALSE on error
	 *
	 * @see categories/edit.php
	 * @see categories/populate.php
	 * @see categories/set_keyword.php
	 * @see control/import.php
	**/
	function post($fields) {
		global $context;

		// title cannot be empty
		if(!isset($fields['title']) || !$fields['title']) {
			Skin::error(i18n::s('No title has been provided.'));
			return FALSE;
		}

		// protect from hackers
		if(isset($fields['bullet_url']))
			$fields['bullet_url'] = preg_replace(FORBIDDEN_CHARS_IN_URLS, '_', $fields['bullet_url']);
		if(isset($fields['icon_url']))
			$fields['icon_url'] = preg_replace(FORBIDDEN_CHARS_IN_URLS, '_', $fields['icon_url']);
		if(isset($fields['thumbnail_url']))
			$fields['thumbnail_url'] = preg_replace(FORBIDDEN_CHARS_IN_URLS, '_', $fields['thumbnail_url']);

		// set default values
		if(!isset($fields['active_set']))
			$fields['active_set'] = 'Y';
		if(!isset($fields['rank']))
			$fields['rank'] = 10000;
		if(isset($fields['edit_action'])) {
			$fields['edit_action'] = preg_replace('/feed$/i', 'create', $fields['edit_action']);
			$fields['edit_action'] = preg_replace('/import$/i', 'update', $fields['edit_action']);
		}

		// cascade anchor access rights
		if(isset($fields['anchor']) && ($anchor = Anchors::get($fields['anchor'])))
			$fields['active'] = $anchor->ceil_rights($fields['active_set']);
		else
			$fields['active'] = $fields['active_set'];

		// set default values for this editor
		$fields = Surfer::check_default_editor($fields);

		// reinforce date formats
		if(!isset($fields['create_date']) || ($fields['create_date'] <= NULL_DATE))
			$fields['create_date'] = $fields['edit_date'];
		if(!isset($fields['expiry_date']) || ($fields['expiry_date'] <= NULL_DATE))
			$fields['expiry_date'] = NULL_DATE;

		// build path information
		$path = '';
		if(isset($fields['anchor']) && $fields['anchor'])
			$path .= Categories::build_path($fields['anchor']).'|';
		$path .= $fields['title'];

		// set layout for categories
		if(!isset($fields['categories_layout']) || !$fields['categories_layout'])
			$fields['categories_layout'] = 'decorated';
		elseif($fields['categories_layout'] == 'custom') {
			if(isset($fields['categories_custom_layout']) && $fields['categories_custom_layout'])
				$fields['categories_layout'] = $fields['categories_custom_layout'];
			else
				$fields['categories_layout'] = 'decorated';
		}

		// set layout for sections
		if(!isset($fields['sections_layout']) || !$fields['sections_layout'])
			$fields['sections_layout'] = 'decorated';
		elseif($fields['sections_layout'] == 'custom') {
			if(isset($fields['sections_custom_layout']) && $fields['sections_custom_layout'])
				$fields['sections_layout'] = $fields['sections_custom_layout'];
			else
				$fields['sections_layout'] = 'decorated';
		}

		// set layout for articles
		if(!isset($fields['articles_layout']) || !$fields['articles_layout'])
			$fields['articles_layout'] = 'decorated';
		elseif($fields['articles_layout'] == 'custom') {
			if(isset($fields['articles_custom_layout']) && $fields['articles_custom_layout'])
				$fields['articles_layout'] = $fields['articles_custom_layout'];
			else
				$fields['articles_layout'] = 'decorated';
		}

		// insert a new record
		$query = "INSERT INTO ".SQL::table_name('categories')." SET ";
		if(isset($fields['id']) && $fields['id'])
			$query .= "id='".SQL::escape($fields['id'])."', ";
		if(isset($fields['nick_name']) && $fields['nick_name'])
			$query .= "nick_name='".SQL::escape($fields['nick_name'])."',";
		$query .= "anchor='".SQL::escape(isset($fields['anchor']) ? $fields['anchor'] : '')."',"
			."active='".SQL::escape($fields['active'])."',"
			."active_set='".SQL::escape($fields['active_set'])."',"
			."articles_layout='".SQL::escape($fields['articles_layout'])."',"
			."categories_count=".SQL::escape(isset($fields['categories_count'])?$fields['categories_count']:5).","
			."categories_layout='".SQL::escape($fields['categories_layout'])."',"
			."categories_overlay='".SQL::escape(isset($fields['categories_overlay']) ? $fields['categories_overlay'] : '')."',"
			."create_address='".SQL::escape(isset($fields['create_address']) ? $fields['create_address'] : $fields['edit_address'])."',"
			."create_date='".SQL::escape($fields['create_date'])."',"
			."create_id='".SQL::escape(isset($fields['create_id']) ? $fields['create_id'] : $fields['edit_id'])."',"
			."create_name='".SQL::escape(isset($fields['create_name']) ? $fields['create_name'] : $fields['edit_name'])."',"
			."description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."',"
			."display='".SQL::escape(isset($fields['display']) ? $fields['display'] : '')."',"
			."edit_action='".SQL::escape(isset($fields['edit_action']) ? $fields['edit_action'] : 'category:create')."',"
			."edit_address='".SQL::escape($fields['edit_address'])."',"
			."edit_date='".SQL::escape($fields['edit_date'])."',"
			."edit_id='".SQL::escape($fields['edit_id'])."',"
			."edit_name='".SQL::escape($fields['edit_name'])."',"
			."expiry_date='".SQL::escape($fields['expiry_date'])."',"
			."hits=".SQL::escape(isset($fields['hits']) ? $fields['hits'] : '0').","
			."icon_url='".SQL::escape(isset($fields['icon_url']) ? $fields['icon_url'] : '')."',"
			."introduction='".SQL::escape(isset($fields['introduction']) ? $fields['introduction'] : '')."',"
			."keywords='".SQL::escape(isset($fields['keywords']) ? $fields['keywords'] : '')."',"
			."options='".SQL::escape(isset($fields['options']) ? $fields['options'] : '')."',"
			."overlay='".SQL::escape(isset($fields['overlay']) ? $fields['overlay'] : '')."',"
			."overlay_id='".SQL::escape(isset($fields['overlay_id'])?$fields['overlay_id']:'')."',"
			."path='".SQL::escape($path)."',"
			."prefix='".SQL::escape(isset($fields['prefix']) ? $fields['prefix'] : '')."',"
			."rank='".SQL::escape($fields['rank'])."',"
			."sections_count='".SQL::escape(isset($fields['sections_count']) ? $fields['sections_count'] : 5)."',"
			."sections_layout='".SQL::escape($fields['sections_layout'])."',"
			."suffix='".SQL::escape(isset($fields['suffix']) ? $fields['suffix'] : '')."',"
			."thumbnail_url='".SQL::escape(isset($fields['thumbnail_url']) ? $fields['thumbnail_url'] : '')."',"
			."title='".SQL::escape($fields['title'])."'";

		// actual insert
		if(SQL::query($query) === FALSE)
			return FALSE;

		// remember the id of the new item
		$id = SQL::get_last_id($context['connection']);

		// clear the whole cache, because a rendering option for things anchored to this category could being changed
		Cache::clear();

		// return the id of the new item
		return $id;
	}

	/**
	 * put an updated category in the database
	 *
	 * @param array an array of fields
	 * @return string either a null string, or some text describing an error to be inserted into the html response
	**/
	function put($fields) {
		global $context;

		// id cannot be empty
		if(!$fields['id'] || !is_numeric($fields['id']))
			return i18n::s('No item has the provided id.');

		// title cannot be empty
		if(!$fields['title'])
			return i18n::s('No title has been provided.');

		// protect from hackers
		if(isset($fields['bullet_url']))
			$fields['bullet_url'] = preg_replace(FORBIDDEN_CHARS_IN_URLS, '_', $fields['bullet_url']);
		if(isset($fields['icon_url']))
			$fields['icon_url'] = preg_replace(FORBIDDEN_CHARS_IN_URLS, '_', $fields['icon_url']);
		if(isset($fields['thumbnail_url']))
			$fields['thumbnail_url'] = preg_replace(FORBIDDEN_CHARS_IN_URLS, '_', $fields['thumbnail_url']);

		// set default values for this editor
		$fields = Surfer::check_default_editor($fields);

		// reinforce date formats
		if(!isset($fields['expiry_date']) || ($fields['expiry_date'] <= NULL_DATE))
			$fields['expiry_date'] = NULL_DATE;

		// set layout for categories
		if(!isset($fields['categories_layout']) || !$fields['categories_layout'])
			$fields['categories_layout'] = 'decorated';
		elseif($fields['categories_layout'] == 'custom') {
			if(isset($fields['categories_custom_layout']) && $fields['categories_custom_layout'])
				$fields['categories_layout'] = $fields['categories_custom_layout'];
			else
				$fields['categories_layout'] = 'decorated';
		}

		// set layout for sections
		if(!isset($fields['sections_layout']) || !$fields['sections_layout'])
			$fields['sections_layout'] = 'decorated';
		elseif($fields['sections_layout'] == 'custom') {
			if(isset($fields['sections_custom_layout']) && $fields['sections_custom_layout'])
				$fields['sections_layout'] = $fields['sections_custom_layout'];
			else
				$fields['sections_layout'] = 'decorated';
		}

		// set layout for articles
		if(!isset($fields['articles_layout']) || !$fields['articles_layout'])
			$fields['articles_layout'] = 'decorated';
		elseif($fields['articles_layout'] == 'custom') {
			if(isset($fields['articles_custom_layout']) && $fields['articles_custom_layout'])
				$fields['articles_layout'] = $fields['articles_custom_layout'];
			else
				$fields['articles_layout'] = 'decorated';
		}

		// set default values
		if(!isset($fields['active_set']))
			$fields['active_set'] = 'Y';

		// cascade anchor access rights
		if(isset($fields['anchor']) && ($anchor = Anchors::get($fields['anchor'])))
			$fields['active'] = $anchor->ceil_rights($fields['active_set']);
		else
			$fields['active'] = $fields['active_set'];

		// build path information
		$path = '';
		if(isset($fields['anchor']) && $fields['anchor'])
			$path .= Categories::build_path($fields['anchor']).'|';
		$path .= $fields['title'];

		// update an existing record
		$query = "UPDATE ".SQL::table_name('categories')." SET ";
		if($fields['nick_name'])
			$query .= "nick_name='".SQL::escape($fields['nick_name'])."',";
		$query .= "anchor='".SQL::escape(isset($fields['anchor']) ? $fields['anchor'] : '')."',"
			."active='".SQL::escape($fields['active'])."',"
			."active_set='".SQL::escape($fields['active_set'])."',"
			."articles_layout='".SQL::escape($fields['articles_layout'])."',"
			."categories_count='".SQL::escape($fields['categories_count'])."' ,"
			."categories_layout='".SQL::escape($fields['categories_layout'])."',"
			."categories_overlay='".SQL::escape(isset($fields['categories_overlay']) ? $fields['categories_overlay'] : '')."',"
			."description='".SQL::escape($fields['description'])."',"
			."display='".SQL::escape($fields['display'])."',"
			."expiry_date='".SQL::escape($fields['expiry_date'])."',"
			."icon_url='".SQL::escape($fields['icon_url'])."',"
			."introduction='".SQL::escape($fields['introduction'])."',"
			."keywords='".SQL::escape($fields['keywords'])."',"
			."options='".SQL::escape($fields['options'])."',"
			."overlay='".SQL::escape(isset($fields['overlay']) ? $fields['overlay'] : '')."',"
			."overlay_id='".SQL::escape($fields['overlay_id'])."',"
			."path='".SQL::escape($path)."',"
			."prefix='".SQL::escape($fields['prefix'])."',"
			."rank='".SQL::escape($fields['rank'])."',"
			."sections_count='".SQL::escape($fields['sections_count'])."',"
			."sections_layout='".SQL::escape($fields['sections_layout'])."',"
			."suffix='".SQL::escape($fields['suffix'])."',"
			."thumbnail_url='".SQL::escape($fields['thumbnail_url'])."',"
			."title='".SQL::escape($fields['title'])."'";

		// maybe a silent update
		if(!isset($fields['silent']) || ($fields['silent'] != 'Y')) {
			$query .= ",\n"
				."edit_name='".SQL::escape($fields['edit_name'])."',\n"
				."edit_id='".SQL::escape($fields['edit_id'])."',\n"
				."edit_address='".SQL::escape($fields['edit_address'])."',\n"
				."edit_action='category:update',\n"
				."edit_date='".SQL::escape($fields['edit_date'])."'";
		}

		// actual update query
		$query .= " WHERE id = ".SQL::escape($fields['id']);
		SQL::query($query);

		// clear the cache for categories
		Cache::clear(array('categories', 'category:'.$fields['id'], 'articles', 'comments', 'files', 'links', 'sections', 'users'));

		// end of job
		return NULL;
	}

	/**
	 * remember publications and tags
	 *
	 * This function links the provided reference to categories, based
	 * on publication time and tags.
	 *
	 * The reference is linked to weekly and monthly categories, except if the
	 * global parameter 'users_without_archiving' has been set to 'Y'.
	 *
	 * @see users/configure.php
	 *
	 * Tags can be provided either as a string of keywords separated by commas,
	 * or as an array of strings.
	 *
	 * @param string a reference to the published material (e.g., 'article:12')
	 * @param string the publication date and time, if any
	 * @param mixed a list of related tags, if any
	 *
	 * @see articles/articles.php
	 * @see categories/check.php
	 * @see services/blog.php
	 */
	function remember($reference, $stamp=NULL, $tags=NULL) {
		global $context;

		// if automatic archiving has not been disabled
		if(!isset($context['users_without_archiving']) || ($context['users_without_archiving'] != 'Y')) {

			// if the stamp has a value, this is a valid publication
			if(is_string($stamp) && ($stamp > NULL_DATE) && ($stamp = strtotime($stamp)) && ($stamp = getdate($stamp))) {

				// weeks are starting on Monday
				$week = mktime(0,0,0, $stamp['mon'], $stamp['mday']-$stamp['wday']+1, $stamp['year']);

				// create the category for this week if it does not exist
				if(!($category =& Categories::lookup('week '.date('y/m/d', $week))) && ($anchor =& Categories::get(i18n::c('weekly')))) {

					$fields = array();
					$fields['anchor'] = 'category:'.$anchor['id'];
					$fields['nick_name'] = 'week '.date('y/m/d', $week);
					$fields['create_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $week);
					$fields['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $week);
					$fields['title'] = sprintf(i18n::c('Week of&nbsp;%s'), date(i18n::c('m/d/y'), $week));
					$fields['description'] = i18n::c('Articles published during this week');
					$fields['options'] = 'no_links';
					if($id = Categories::post($fields))
						$category = 'category:'.$id;
				}

				// link the reference to this weekly category
				if($category) {
					if($error = Members::assign($category, $reference))
						Skin::error($error);
				}

				// months are starting on day 1
				$month = mktime(0,0,0, $stamp['mon'], 1, $stamp['year']);

				// create the category for this month if it does not exist
				if(!($category =& Categories::lookup('month '.date('M Y', $month))) && ($anchor =& Categories::get(i18n::c('monthly')))) {
					$fields = array();
					$fields['anchor'] = 'category:'.$anchor['id'];
					$fields['nick_name'] = 'month '.date('M Y', $month);
					$fields['create_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $month);
					$fields['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $month);
					$fields['title'] = Skin::build_date($month, 'month', $context['preferred_language']);
					$fields['description'] = i18n::c('Articles published during this month');
					$fields['options'] = 'no_links';
					if($id = Categories::post($fields))
						$category = 'category:'.$id;
				}

				// link the reference to this monthly category
				if($category) {
					if($error = Members::assign($category, $reference))
						Skin::error($error);
				}
			}
		}

		// link to selected categories --do not accept ; as separator, because this conflicts with UTF-8 encoding
		if(is_string($tags) && $tags)
			$tags = preg_split('/[ \t]*,\s*/', $tags);
		if(is_array($tags) && count($tags)) {

			// create a category to host keywords, if none exists
			if(!$root_category =& Categories::lookup('keywords')) {
				$fields = array();
				$fields['nick_name'] = 'keywords';
				$fields['title'] = i18n::c('Keywords');
				$fields['introduction'] = i18n::c('Classified pages');
				$fields['description'] = i18n::c('This category is a specialized glossary of terms, made out of tags added to pages, and out of search requests.');
				$fields['rank'] = 29000;
				$fields['options'] = 'no_links';
				if($id = Categories::post($fields))
					$root_category = 'category:'.$id;
			}

			// one category per tag
			foreach($tags as $title) {

				// create a category if tag is unknown
				if(!$category =& Categories::get_by_keyword($title)) {
					$fields = array();
					$fields['title'] = ucfirst($title);
					$fields['keywords'] = $title;
					if($root_category)
						$fields['anchor'] = $root_category;
					if($id = Categories::post($fields))
						$category = 'category:'.$id;
				} else
					$category = 'category:'.$category['id'];

				// link page to the category
				if($category) {
					Members::assign($category, $reference);
				}
			}
		}

	}

	/**
	 * search for some keywords in all categories
	 *
	 * Only categories matching following criteria are returned:
	 * - category is visible (active='Y')
	 * - category is restricted (active='R'), but surfer is a logged user
	 * - category is restricted (active='N'), but surfer is an associate
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param the search string
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 * @see #list_selected for $variant description
	 */
	function &search($pattern, $offset=0, $count=50, $variant='search') {
		global $context;

		// limit the scope of the request
		$where = "categories.active='Y'";
		if(Surfer::is_member())
			$where .= " OR categories.active='R'";
		if(Surfer::is_associate())
			$where .= " OR categories.active='N'";
		$where = '('.$where.')';

		// only consider live categories
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where .= ' AND ((categories.expiry_date is NULL)'
			."	OR (categories.expiry_date <= '".NULL_DATE."') OR (categories.expiry_date > '".$now."'))";

		// match
		$match = '';
		$words = preg_split('/\s/', $pattern);
		while($word = each($words)) {
			if($match)
				$match .= ' AND ';
			$match .=  "MATCH(title, introduction, description) AGAINST('".SQL::escape($word['value'])."')";
		}

		// look in keywords as well
		$match = "((keywords LIKE '".SQL::escape($pattern)."%') OR (".$match."))";

		// the list of categories
		$query = "SELECT categories.* FROM ".SQL::table_name('categories')." AS categories"
			." WHERE ".$where." AND $match"
			." ORDER BY categories.edit_date DESC"
			." LIMIT ".$offset.','.$count;

		$output =& Categories::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * set the hits counter - errors are not reported, if any
	 *
	 * @param the id of the category to update
	 */
	function increment_hits($id) {
		global $context;

		// sanity check
		if(!$id)
			return;

		// do the job
		$query = "UPDATE ".SQL::table_name('categories')." SET hits=hits+1 WHERE (id LIKE '$id')";
		SQL::query($query);

	}

	/**
	 * create table for categories
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['active']		= "ENUM('Y','R','N') DEFAULT 'Y' NOT NULL"; 				// Yes, Restricted or No
		$fields['active_set']	= "ENUM('Y','R','N') DEFAULT 'Y' NOT NULL"; 				// set locally
		$fields['anchor']		= "VARCHAR(64)";											// up to 64 chars
		$fields['articles_layout']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['bullet_url']	= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars
		$fields['categories_count'] = "INT UNSIGNED NOT NULL";
		$fields['categories_layout'] = "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['categories_overlay'] = "VARCHAR(64) DEFAULT '' NOT NULL";
		$fields['create_address']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['create_date']	= "DATETIME";
		$fields['create_id']	= "MEDIUMINT UNSIGNED DEFAULT '1' NOT NULL";
		$fields['create_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";						// item creation
		$fields['description']	= "TEXT NOT NULL";											// up to 64k chars
		$fields['display']		= "VARCHAR(255) DEFAULT '' NOT NULL";						// the anchor of the page to display this category
		$fields['edit_action']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_address'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_date']	= "DATETIME";
		$fields['edit_id']		= "MEDIUMINT UNSIGNED DEFAULT '1' NOT NULL";
		$fields['edit_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";						// item modification
		$fields['expiry_date']	= "DATETIME";
		$fields['hits'] 		= "INT UNSIGNED DEFAULT '0' NOT NULL";						// counter
		$fields['icon_url'] 	= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars
		$fields['introduction'] = "TEXT NOT NULL";											// up to 64k chars
		$fields['keywords'] 	= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars
		$fields['nick_name']	= "VARCHAR(64) DEFAULT '' NOT NULL";						// up to 32 chars
		$fields['options']		= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars
		$fields['overlay']		= "TEXT NOT NULL";											// up to 64k chars
		$fields['overlay_id']	= "VARCHAR(128) DEFAULT '' NOT NULL";						// to find the page by its overlay
		$fields['path'] 		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['prefix']		= "TEXT NOT NULL";											// up to 64k chars
		$fields['rank'] 		= "MEDIUMINT UNSIGNED DEFAULT '10000' NOT NULL";			// 1 to 64k
		$fields['sections_count']	= "INT UNSIGNED NOT NULL";
		$fields['sections_layout']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['suffix']		= "TEXT NOT NULL";											// up to 64k chars
		$fields['thumbnail_url']= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars
		$fields['title']		= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars

		$indexes = array();
		$indexes['PRIMARY KEY id']		= "(id)";
		$indexes['INDEX nick_name'] 	= "(nick_name)";
		$indexes['INDEX anchor']		= "(anchor)";
		$indexes['INDEX path']			= "(path(255))";
		$indexes['INDEX title'] 		= "(title(255))";
		$indexes['INDEX rank']			= "(rank)";
		$indexes['INDEX active']		= "(active)";
		$indexes['INDEX hits']			= "(hits)";
		$indexes['INDEX display']		= "(display)";
		$indexes['INDEX keywords']		= "(keywords(255))";
		$indexes['INDEX create_name']	= "(create_name)";
		$indexes['INDEX create_id'] 	= "(create_id)";
		$indexes['INDEX create_date']	= "(create_date)";
		$indexes['INDEX edit_name'] 	= "(edit_name)";
		$indexes['INDEX edit_id']		= "(edit_id)";
		$indexes['INDEX edit_date'] 	= "(edit_date)";
		$indexes['INDEX expiry_date']	= "(expiry_date)";
		$indexes['FULLTEXT INDEX']	= "full_text(title, introduction, description)";

		return SQL::setup_table('categories', $fields, $indexes);

	}

	/**
	 * get some statistics for some categories
	 *
	 * Only categories matching following criteria are returned:
	 * - category is visible (active='Y')
	 * - category is restricted (active='R'), but surfer is a logged user
	 * - an anchor has been provided and category is hidden (active='N'), but surfer is an associate
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param the selected anchor (e.g., 'category:12')
	 * @return the resulting ($count, $min_date, $max_date) array
	 */
	function &stat_for_anchor($anchor) {
		global $context;

		// limit the scope of the request
		$where = "categories.active='Y'";
		if(Surfer::is_member())
			$where .= " OR categories.active='R'";

		// list hidden categories to associates, but not on the category tree
		// they will be listed through a call to list_inactive_by_title() -- see categories/index.php
		if($anchor && Surfer::is_associate())
			$where .= " OR categories.active='N'";

		// only consider live categories
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where = "(".$where.")"
			." AND ((categories.expiry_date is NULL)"
			."	OR (categories.expiry_date <= '".NULL_DATE."') OR (categories.expiry_date > '".$now."'))";

		// limit the query to one level
		if($anchor)
			$where = "(categories.anchor LIKE '".SQL::escape($anchor)."') AND (".$where.')';
		else
			$where = "(categories.anchor='' OR categories.anchor is NULL) AND (".$where.')';

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date"
			." FROM ".SQL::table_name('categories')." AS categories"
			." WHERE ".$where;

		$output =& SQL::query_first($query);
		return $output;
	}

}

// ensure this library has been fully localized
i18n::bind('categories');

?>