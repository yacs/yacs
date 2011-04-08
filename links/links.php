<?php
/**
 * the database abstraction layer for links
 *
 * @author Bernard Paques
 * @author Florent
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Links {

	/**
	 * check if new links can be added
	 *
	 * This function returns TRUE if links can be added to some place,
	 * and FALSE otherwise.
	 *
	 * @param object an instance of the Anchor interface, if any
	 * @param array a set of item attributes, if any
	 * @param string the type of item, e.g., 'section'
	 * @return boolean TRUE or FALSE
	 */
	function allow_creation($anchor=NULL, $item=NULL, $variant=NULL) {
		global $context;

		// guess the variant
		if(!$variant) {

			// most frequent case
			if(isset($item['id']))
				$variant = 'article';

			// we have no item, look at anchor type
			elseif(is_object($anchor))
				$variant = $anchor->get_type();

			// sanity check
			else
				return FALSE;
		}

		// only in articles
		if($variant == 'article') {

			// 'no_links' option
			if(Articles::has_option('no_links', $anchor, $item))
				return FALSE;


		// other containers
		} else {

			// links have to be activated
			if(isset($item['options']) && is_string($item['options']) && preg_match('/\bwith_links\b/i', $item['options']))
				;
			elseif(!isset($item['id']) && is_object($anchor) && $anchor->has_option('with_links', FALSE))
				;
			else
				return FALSE;

		}

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// submissions have been disallowed
		if(isset($context['users_without_submission']) && ($context['users_without_submission'] == 'Y'))
			return FALSE;

		// only in articles
		if($variant == 'article') {

			// surfer owns this item, or the anchor
			if(Articles::is_owned($item, $anchor))
				return TRUE;

			// surfer is an editor, and the page is not private
			if(isset($item['active']) && ($item['active'] != 'N') && Articles::is_assigned($item['id']))
				return TRUE;

		// only in sections
		} elseif($variant == 'section') {

			// surfer owns this item, or the anchor
			if(Sections::is_owned($item, $anchor, TRUE))
				return TRUE;

			// surfer is an editor, and the section is not private
			if(isset($item['active']) && ($item['active'] != 'N') && Sections::is_assigned($item['id']))
				return TRUE;

		}

		// surfer is an editor, and container is not private
		if(isset($item['active']) && ($item['active'] != 'N') && is_object($anchor) && $anchor->is_assigned())
			return TRUE;
		if(!isset($item['id']) && is_object($anchor) && !$anchor->is_hidden() && $anchor->is_assigned())
			return TRUE;

		// item has been locked
		if(isset($item['locked']) && ($item['locked'] == 'Y'))
			return FALSE;

		// anchor has been locked --only used when there is no item provided
		if(!isset($item['id']) && is_object($anchor) && $anchor->has_option('locked'))
			return FALSE;

		// surfer is an editor (and item has not been locked)
		if(($variant == 'article') && isset($item['id']) && Articles::is_assigned($item['id']))
			return TRUE;
		if(($variant == 'section') && isset($item['id']) && Sections::is_assigned($item['id']))
			return TRUE;
		if(is_object($anchor) && $anchor->is_assigned())
			return TRUE;

		// container is hidden
		if(isset($item['active']) && ($item['active'] == 'N'))
			return FALSE;
		if(is_object($anchor) && $anchor->is_hidden())
			return FALSE;

		// authenticated members and subscribers are allowed to add links
		if(Surfer::is_logged())
			return TRUE;

		// container is restricted
		if(isset($item['active']) && ($item['active'] == 'R'))
			return FALSE;
		if(is_object($anchor) && !$anchor->is_public())
			return FALSE;

		// anonymous contributions are allowed for articles
		if($variant == 'article') {
			if(isset($item['options']) && preg_match('/\banonymous_edit\b/i', $item['options']))
				return TRUE;
			if(is_object($anchor) && $anchor->has_option('anonymous_edit'))
				return TRUE;
		}

		// the default is to not allow for new links
		return FALSE;
	}

	/**
	 * check if trackback is allowed
	 *
	 * This function returns TRUE either if the surfer has been authenticated,
	 * or if the site is visible from the Internet.
	 *
	 * @return boolean TRUE or FALSE
	 */
	function allow_trackback() {
		global $context;

		// site is visible from the Internet
		if(!isset($context['without_internet_visibility']) || ($context['without_internet_visibility'] != 'Y'))
			return TRUE;

		// surfer has been authenticated, provide trackback shortcut
		if(Surfer::is_logged())
			return TRUE;
	}

	/**
	 * clean link label
	 *
	 * @param string link title, if any
	 * @param string link URI
	 * @param int maximum number of chars
	 * @return string a cleaned label
	 */
	function clean($title='', $reference='', $size=100) {
		global $context;

		// use provided title
		$label = Skin::strip($title, $size);

		// or extract a label from the link itself
		if(!$label) {
			$label = basename($reference, '.htm');
			if(($position = strpos($label, '?')) > 0)
				$label = substr($label, 0, $position);
			$label = str_replace('_', ' ', str_replace('%20', ' ', $label));

			if(strlen($label) > $size)
				$label = '...'.substr($label, -$size);
		}

		// sanity check
		if(!$label)
			$label = '...';

		return $label;

	}

	/**
	 * clear cache entries for one item
	 *
	 * @param array item attributes
	 */
	function clear(&$item) {

		// where this item can be displayed
		$topics = array('articles', 'categories', 'links', 'sections', 'users');

		// clear anchor page
		if(isset($item['anchor']))
			$topics[] = $item['anchor'];

		// clear this page
		if(isset($item['id']))
			$topics[] = 'link:'.$item['id'];

		// clear the cache
		Cache::clear($topics);

	}

	/**
	 * record a click
	 *
	 * @param string the external url that is targeted
	 *
	 */
	function click($url) {
		global $context;

		// we record only GET requests
		if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'GET'))
			return;

		// do not count crawling
		if(isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(blo\.gs|\bblog|bot\b|crawler\b|frontier\b|slurp\b|spider\b)/i', $_SERVER['HTTP_USER_AGENT']))
			return;

		// record the activity
		include_once $context['path_to_root'].'users/activities.php';
		Activities::post($url, 'click');

		// do not record clicks driving to search engines
		if(preg_match('/\b(google|yahoo)\b/i', $url))
			return;

		// if this url is known
		$query = "SELECT * FROM ".SQL::table_name('links')." AS links"
			." WHERE links.link_url LIKE '".SQL::escape($url)."'";
		if($item =& SQL::query_first($query)) {

			// increment the number of clicks
			$query = "UPDATE ".SQL::table_name('links')." SET hits=hits+1 WHERE id = ".SQL::escape($item['id']);
			SQL::query($query);

		// else create a new record with a count of one click
		} else {

			// get the section for clicks
			$anchor = Sections::lookup('clicks');

			// no section yet, create one
			if(!$anchor) {

				$fields['nick_name'] = 'clicks';
				$fields['title'] = i18n::c('Clicks');
				$fields['introduction'] = i18n::c('Clicked links are referenced here.');
				$fields['description'] = i18n::c('YACS ties automatically external links to this section on use. Therefore, you will have below a global picture of external sites that are referenced through your site.');
				$fields['active_set'] = 'N'; // for associates only
				$fields['locked'] = 'Y'; // no direct contributions
				$fields['home_panel'] = 'none'; // content is not pushed at the front page
				$fields['index_map'] = 'N'; // listd only to associates
				$fields['rank'] = 20000; // towards the end of the list

				// reference the new section
				if($fields['id'] = Sections::post($fields))
					$anchor = 'section:'.$fields['id'];

			}

			// create a new link in the database
			$fields = array();
			$fields['anchor'] = $anchor;
			$fields['link_url'] = $url;
			$fields['hits'] = 1;
			Surfer::check_default_editor($fields);
			if($fields['id'] = Links::post($fields)) {
				Links::clear($fields);
			}

		}
	}

	/**
	 * count record for one anchor
	 *
	 * @param the selected anchor (e.g., 'article:12')
	 * @param boolean TRUE if this can be optionnally avoided
	 * @return the resulting count, or NULL on error
	 */
	function count_for_anchor($anchor, $optional=FALSE) {
		global $context;

		// sanity check
		if(!$anchor)
			return NULL;

		// request the database only in hi-fi mode
		if($optional && ($context['skins_with_details'] != 'Y'))
			return NULL;

		// select among available items
		$query = "SELECT COUNT(*) as count"
			." FROM ".SQL::table_name('links')." AS links"
			." WHERE links.anchor LIKE '".SQL::escape($anchor)."'";

		return SQL::query_scalar($query);
	}

	/**
	 * delete one link
	 *
	 * @param int the id of the link to delete
	 * @return boolean TRUE on success, FALSE otherwise
	 *
	 * @see links/delete.php
	 */
	function delete($id) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return FALSE;

		// delete the record in the database
		$query = "DELETE FROM ".SQL::table_name('links')." WHERE id = ".SQL::escape($id);
		if(SQL::query($query) === FALSE)
			return FALSE;

		// job done
		return TRUE;
	}

	/**
	 * delete all links for a given anchor
	 *
	 * @param the anchor to check
	 *
	 * @see shared/anchors.php
	 */
	function delete_for_anchor($anchor) {
		global $context;

		// delete all matching records in the database
		$query = "DELETE FROM ".SQL::table_name('links')." WHERE anchor LIKE '".SQL::escape($anchor)."'";
		SQL::query($query);
	}

	/**
	 * duplicate all links for a given anchor
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
		$query = "SELECT * FROM ".SQL::table_name('links')." WHERE anchor LIKE '".SQL::escape($anchor_from)."'";
		if(($result =& SQL::query($query)) && SQL::count($result)) {

			// process all matching records one at a time
			while($item =& SQL::fetch($result)) {

				// a new id will be allocated
				$old_id = $item['id'];
				unset($item['id']);

				// target anchor
				$item['anchor'] = $anchor_to;

				// actual duplication
				if($item['id'] = Links::post($item)) {

					// duplicate elements related to this item
					Anchors::duplicate_related_to('link:'.$old_id, 'link:'.$item['id']);

					// stats
					$count++;
				}
			}

		}

		// number of duplicated records
		return $count;
	}

	/**
	 * get one link by id or by url
	 *
	 * @param int the id of the link, or the target url
	 * @return the resulting $item array, with at least keys: 'id', 'title', 'description', etc.
	 *
	 * @see links/delete.php
	 * @see links/edit.php
	 */
	function &get($id) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// search by id
		if(is_numeric($id))
			$query = "SELECT * FROM ".SQL::table_name('links')." AS links"
				." WHERE (links.id = ".SQL::escape((integer)$id).")";

		// or look for given name of handle
		else
			$query = "SELECT * FROM ".SQL::table_name('links')." AS links"
				." WHERE (links.link_url LIKE '".SQL::escape($id)."')"
				." ORDER BY edit_date DESC LIMIT 1";

		// do the job
		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * check for link existence
	 *
	 * This function can be used either to see if a link exists in the database, or to check if one link has been
	 * attached to a particular anchor.
	 *
	 * To query the whole database, use:
	 * [php]
	 * if(Links::have($that_beautiful_url))
	 *	  ...
	 * [/php]
	 *
	 * To check that an article has a link attached, use:
	 * [php]
	 * $anchor = 'article:'.$article['id'];
	 * if(Links::have($that_beautiful_url, $anchor))
	 *	  ...
	 * [/php]
	 *
	 * If additional attributes are provided, they are used to update
	 * link description if one exists.
	 *
	 * @param string the external url that is targeted
	 * @param string an internal anchor, if any
	 * @param array updated link attributes, if any
	 * @return either TRUE or FALSE
	 *
	 * @see feeds/feeds.php
	 * @see links/trackback.php
	 * @see services/ping.php
	 */
	function have($url, $anchor=NULL, $attributes=NULL) {
		global $context;

		// does this (link, anchor) tupple exists?
		$query = "SELECT id FROM ".SQL::table_name('links')." AS links "
			." WHERE links.link_url LIKE '".SQL::escape($url)."'";
		if($anchor)
			$query .= " AND links.anchor = '$anchor'";
		$query .= " LIMIT 1";

		// no, this does not exist
		if(!$row = SQL::query_first($query))
			return FALSE;

		// update the link, if any
		if(isset($row['id']) && is_array($attributes)) {
			$attributes['id'] = $row['id'];
			Links::put($attributes);
		}

		// the link does exist
		return TRUE;
	}

	/**
	 * list newest links
	 *
	 * To build a simple box of the newest links in your main index page, just use
	 * the following example:
	 * [php]
	 * // side bar with the list of most recent links
	 * include_once 'links/links.php';
	 * $items = Links::list_by_date(0, 10);
	 * $text = Skin::build_list($items, 'compact');
	 * $context['text'] .= Skin::build_box($title, $text, 'navigation');
	 * [/php]
	 *
	 * You can also display the newest link separately, using [code]Links::get_newest()[/code]
	 * In this case, skip the very first link in the list by using
	 * [code]Links::list_by_date(1, 10)[/code].
	 *
	 * This function masks links fetched from external feeds.
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see index.php
	 * @see links/check.php
	 * @see links/index.php
	 */
	function &list_by_date($offset=0, $count=10, $variant='dates') {
		global $context;

		// if not associate, restrict to links attached to public published not expired pages
		if(!Surfer::is_associate()) {
			$where = ", ".SQL::table_name('articles')." AS articles "
				." WHERE ((links.anchor_type LIKE 'article') AND (links.anchor_id = articles.id))"
				." AND (articles.active='Y')"
				." AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."'))"
				." AND (links.edit_id > 0)";
		} else {
			$where = "WHERE (links.edit_id > 0)";
		}

		// the list of links
		$query = "SELECT links.* FROM ".SQL::table_name('links')." AS links ".$where
			." ORDER BY links.edit_date DESC, links.title LIMIT ".$offset.','.$count;

		$output =& Links::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list newest links for one anchor
	 *
	 * Example:
	 * [php]
	 * include_once 'links/links.php';
	 * $items = Links::list_by_date_for_anchor('section:12', 0, 10);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 * [/php]
	 *
	 * @param int the id of the anchor
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see articles/fetch_as_msword.php
	 * @see articles/print.php
	 * @see articles/view.php
	 * @see categories/print.php
	 * @see categories/print.php
	 * @see sections/print.php
	 * @see sections/view.php
	 * @see users/print.php
	 * @see users/view.php
	 */
	function &list_by_date_for_anchor($anchor, $offset=0, $count=20, $variant='no_anchor') {
		global $context;

		// the list of links
		$query = "SELECT * FROM ".SQL::table_name('links')." AS links "
			." WHERE links.anchor LIKE '".SQL::escape($anchor)."'"
			." ORDER BY links.edit_date DESC, links.title LIMIT ".$offset.','.$count;

		$output =& Links::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list newest links for one author
	 *
	 * Example:
	 * [php]
	 * include_once 'links/links.php';
	 * $items = Links::list_by_date_for_author(12, 0, 10);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 * [/php]
	 *
	 * @param int the id of the author of the link
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_by_date_for_author($author_id, $offset=0, $count=20, $variant='no_author') {
		global $context;

		// the list of links
		$query = "SELECT * FROM ".SQL::table_name('links')." AS links "
			." WHERE (links.edit_id = ".SQL::escape($author_id).")"
			." ORDER BY links.edit_date DESC, links.title LIMIT ".$offset.','.$count;

		$output =& Links::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list most read links
	 *
	 * To build a simple box of the most read links in your main index page, just use
	 * Links::list_by_hits(0, 10)
	 *
	 * Example:
	 * [php]
	 * include_once '../links/links.php';
	 * $context['text'] .= Skin::build_list(Links::list_by_hits(), 'compact');
	 * [/php]
	 *
	 * You can also display the most read link separately, using Links::get_most_read()
	 * In this case, skip the very first link in the list by using
	 * Links::list_by_hits(1)
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see index.php
	 */
	function &list_by_hits($offset=0, $count=10, $variant='hits') {
		global $context;

		// the list of links
		$query = "SELECT * FROM ".SQL::table_name('links')." AS links"
			." GROUP BY links.link_url"
			." ORDER BY links.hits DESC, links.title LIMIT ".$offset.','.$count;

		$output =& Links::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list most popular links for one author
	 *
	 * Example:
	 * [php]
	 * include_once 'links/links.php';
	 * $items = Links::list_by_hits_for_author(12, 0, 10);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 * [/php]
	 *
	 * @param int the id of the author of the link
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_by_hits_for_author($author_id, $offset=0, $count=10, $variant='hits') {
		global $context;

		// the list of links
		$query = "SELECT * FROM ".SQL::table_name('links')." AS links"
			." WHERE (links.edit_id = ".SQL::escape($author_id).")"
			." ORDER BY links.hits DESC, links.title LIMIT ".$offset.','.$count;

		$output =& Links::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list links by title for one anchor
	 *
	 * Example:
	 * [php]
	 * include_once '../links/links.php';
	 * $items = Links::list_by_title_for_anchor('article:12');
	 * $context['text'] .= Skin::build_list($items, 'decorated');
	 * [/php]
	 *
	 * @param int the id of the anchor
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see articles/fetch_as_msword.php
	 * @see articles/print.php
	 * @see articles/view.php
	 * @see categories/print.php
	 * @see categories/print.php
	 * @see sections/print.php
	 * @see sections/view.php
	 * @see users/print.php
	 * @see users/view.php
	 */
	function &list_by_title_for_anchor($anchor, $offset=0, $count=10, $variant='no_anchor') {
		global $context;

		// the list of links
		$query = "SELECT * FROM ".SQL::table_name('links')." AS links"
			." WHERE (links.anchor LIKE '".SQL::escape($anchor)."')"
			." ORDER BY links.title, links.edit_date DESC LIMIT ".$offset.','.$count;

		$output =& Links::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list links received from newsfeeders
	 *
	 * This function is used to show most recent news received from the net.
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see feeds/feeds.php
	 */
	function &list_news($offset=0, $count=10, $variant='dates') {
		global $context;

		// the list of links
		$query = "SELECT links.* FROM ".SQL::table_name('links')." AS links "
			." WHERE (links.edit_id = 0)"
			." GROUP BY links.link_url"
			." ORDER BY links.edit_date DESC, links.title LIMIT ".$offset.','.$count;

		$output =& Links::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list selected links
	 *
	 * If variant is provided as a string, the functions looks for a script featuring this name.
	 * E.g., for variant 'compact', the file 'links/layout_links_as_compact.php' is loaded.
	 * If no file matches then the default 'links/layout_links.php' script is loaded.
	 *
	 * @param resource result of database query
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return an array of $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_selected(&$result, $variant='compact') {
		global $context;

		// no result
		if(!$result) {
			$output = NULL;
			return $output;
		}

		// special layouts
		if(is_object($variant)) {
			$output =& $variant->layout($result);
			return $output;
		}

		// no layout yet
		$layout = NULL;

		// separate options from layout name
		$attributes = explode(' ', $variant, 2);

		// instanciate the provided name
		if($attributes[0]) {
			$name = 'layout_links_as_'.$attributes[0];
			if(is_readable($context['path_to_root'].'links/'.$name.'.php')) {
				include_once $context['path_to_root'].'links/'.$name.'.php';
				$layout = new $name;

				// provide parameters to the layout
				if(isset($attributes[1]))
					$layout->set_variant($attributes[1]);

			}
		}

		// use default layout
		if(!$layout) {
			include_once $context['path_to_root'].'links/layout_links.php';
			$layout = new Layout_links();
			$layout->set_variant($variant);
		}

		// do the job
		$output =& $layout->layout($result);
		return $output;

	}

	/**
	 * ping back links referenced in some text
	 *
	 * This is the client implementation of
	 * [link=trackback]http://www.movabletype.org/docs/mttrackback.html[/link]
	 * and [link=pingback]http://www.hixie.ch/specs/pingback/pingback[/link] specifications.
	 *
	 * This function is triggered by publishing scripts, either [script]articles/publish.php[/script],
	 * [script]services/blog.php[/script], [script]agents/messages.php[/script] or [script]agents/uploads.php[/script].
	 *
	 * @see articles/publish.php
	 * @see services/blog.php
	 * @see agents/messages.php
	 * @see agents/uploads.php
	 *
	 * It is used to efficiently link pages across a set of web sites according to the following mechanism:
	 * - The list of external links is built for this page
	 * - Only the 7 first links are kept from the list; others are stripped
	 * - If links do not exist, create additional records in the table used for links
	 * - Each link (actually, only the 7 most recent) is checked, to see if it's trackback- or pingback-enabled or not
	 * - Each trackback-/pingback-enabled link is activated, providing the full URL of the anchor page
	 *
	 * We are claiming to support most of the trackback client interface here, as described in the [link=trackback]http://www.movabletype.org/docs/mttrackback.html[/link] specification.
	 * A foreign page is considered as being trackback-enabled if it has a special RDF section
	 * linking its reference (i.e., URL) to a Trackback Ping URL.
	 *
	 * Note that YACS also implements the server part of the trackback specification in [script]links/trackback.php[/script],
	 * which supports POST REST calls.
	 *
	 * @see links/trackback.php
	 *
	 * We are claiming to fully support the pingback client interface here, as described in the [link=pingback]http://www.hixie.ch/specs/pingback/pingback[/link] specification.
	 * A foreign page is considered to be pingback-enabled if it has a meta link to a Pingback Ping URL.
	 *
	 * Note that YACS also implements the server part of the pingback specification in [script]services/ping.php[/script],
	 * which supports XML-RPC calls.
	 *
	 * @see services/ping.php
	 *
	 * This function transforms every YACS codes into HTML before extracting links,
	 * and before submitting the excerpt to remote site.
	 *
	 * @param string the referencing text that has to be scanned
	 * @param string the local anchor of the referencing text (e.g., 'article:124')
	 * @return array list($links, $advertised, $skipped)
	 *
	 * @link http://www.movabletype.org/docs/mttrackback.html TrackBack Technical Specification
	 * @link http://www.hixie.ch/specs/pingback/pingback Pingback specification
	 */
	function ping($text, $anchor) {
		global $context;

		// render all codes
		if(is_callable(array('Codes', 'beautify')))
			$text = Codes::beautify($text);

		// suppress all links not coming from anchors (eg, <img src=...)
		$text = strip_tags($text, '<a>');

		// extract all links from the text, including those that have been encoded by YACS
		preg_match_all('/((http:\/\/|http%3A%2F%2F)[^ <"]+)/i', $text, $links);

		// nothing to do
		if(!@count($links[1]))
			return;

		// process each link only once
		$unique_links = array();
		foreach($links[1] as $url) {

			// decode raw url encoding, if any
			$url = rawurldecode($url);

			// strip the clicking indirection, if any
			$url = rawurldecode(preg_replace('/^'.preg_quote($context['url_to_home'].$context['url_to_root'].'links/click.php?url=', '/').'/i', '', $url));

			$unique_links[$url] = 1;
		}

		// analyze found links
		$links_processed = array();
		$links_advertised = array();
		$links_skipped = array();
		foreach($unique_links as $url => $dummy) {

			// analyze no more than 7 links
			if(@count($links_processed) >= 7)
				break;

			// skip links that point to ourself, and not to an article
			if(preg_match('/^'.preg_quote($context['url_to_home'], '/').'\b/i', $url) && !preg_match('/\/article(-|s\/view.php)/i', $url)) {
				$links_skipped[] = $url;
				continue;
			}

			// skip invalid links
			if(($content = http::proceed($url, '', '', 'links/links.php')) === FALSE) {
				$links_skipped[] = $url;
				continue;
			}

			// we will use the content to locate pingback and trackback interfaces
			$pages[$url] = $content;

			// ensure enough execution time
			Safe::set_time_limit(30);

			// stats
			$links_processed[] = $url;

		}

		// locate the anchor object for this text, we need its url
		$anchor =& Anchors::get($anchor);
		if(!is_object($anchor))
			return;

		// build an excerpt from anchor
		$excerpt = $anchor->get_teaser('basic');

		// find blog name for anchor
		if($parent = $anchor->get_value('anchor')) {
			$blog =& Anchors::get($parent);
			if(is_object($blog))
				$blog_name = $blog->get_title();
		}

		// build an absolute URL for the source
		$source = $context['url_to_home'].$context['url_to_root'].$anchor->get_url();

		// process each link
		if(@count($pages)) {
			foreach($pages as $target => $content) {

				// try trackback, if implemented
				if(Links::ping_as_trackback($content, $source, $target, $anchor->get_title(), $excerpt, $blog_name))
					$links_advertised[] = $target;

				// then try pingback, if implemented
				elseif(Links::ping_as_pingback($content, $source, $target))
					$links_advertised[] = $target;

			}
		}

		return array($links_processed, $links_advertised, $links_skipped);
	}

	/**
	 * attempt to use the pingback interface
	 *
	 * @param string - some text, extracted from the target site, to extract the broker URL, if any
	 * @param string - the source address
	 * @param string - the target address from which the text has been extracted
	 * @return TRUE if the target site has been pinged back, FALSE otherwise
	 *
	 * @link http://www.hixie.ch/specs/pingback/pingback Pingback specification
	 */
	function ping_as_pingback($text, $source, $target) {
		global $context;

		// extract all <link... /> tags
		preg_match_all('/<link(.+?)\/?>/mi', $text, $links);

		// nothing to do
		if(!@count($links[1]))
			return FALSE;

		// look for the broker
		$broker = array();
		foreach($links[1] as $link) {

			// seek the pingback interface
			if(!preg_match('/rel="pingback"/mi', $link))
				continue;

			// extract the broker link
			if(preg_match('/href="([^"]+)"/mi', $link, $broker))
				break;
		}

		// pingback interface not supported here
		if(!isset($broker[1]))
			return FALSE;

		// actual pingback, through XML-RPC
		include_once $context['path_to_root'].'services/call.php';
		$result = Call::invoke($broker[1], 'pingback.ping', array($source, $target), 'XML-RPC');
		return TRUE;
	}

	/**
	 * attempt to use the trackback interface
	 *
	 * @param string some text, extracted from the target site, to extract the broker URL, if any
	 * @param string the source address
	 * @param string the target address from which the text has been extracted
	 * @param string title of the source page
	 * @param string excerpt of the source page
	 * @param string blog name of the source page
	 * @return TRUE if the target site has been pinged back, FALSE otherwise
	 *
	 * @link http://www.movabletype.org/docs/mttrackback.html TrackBack Technical Specification
	 */
	function ping_as_trackback($text, $source, $target, $title='', $excerpt='', $blog_name='') {
		global $context;

		// extract all rdf blocks
		preg_match_all('/<rdf:RDF(.*)<\/rdf:RDF>/iUs', $text, $blocks);

		// nothing to do
		if(!@count($blocks[1]))
			return FALSE;

		// look for the broker
		$broker = array();
		foreach($blocks[1] as $block) {

			// seek the trackback interface
			if(!preg_match('/(dc:identifier|about)="'.preg_quote($target, '/').'/mi', $block))
				continue;

			// extract the broker link
			if(preg_match('/trackback:ping="([^"]+)"/mi', $block, $broker))
				break;
		}

		// trackback interface not supported at this page
		if(!isset($broker[1]))
			return FALSE;

		// parse the broker URL
		$items = @parse_url($broker[1]);

		// no host, assume it's us
		if(!$host = $items['host'])
			$host = $context['host_name'];

		// no port, assume the standard
		if(!isset($items['port']) || (!$port = $items['port']))
			$port = 80;

		// outbound web is not authorized
		if(isset($context['without_outbound_http']) && ($context['without_outbound_http'] == 'Y')) {
			if(isset($context['debug_trackback']) && ($context['debug_trackback'] == 'Y'))
				Logger::remember('links/links.php', 'Links::ping_as_trackback()', 'Outbound HTTP is not authorized.', 'debug');
			return FALSE;
		}

		// connect to the server
		if(!$handle = Safe::fsockopen($host, $port, $errno, $errstr, 30)) {
			if(isset($context['debug_trackback']) && ($context['debug_trackback'] == 'Y'))
				Logger::remember('links/links.php', 'Links::ping_as_trackback()', sprintf('Impossible to connect to %s.', $host.':'.$port), 'debug');
			return FALSE;
		}

		// ensure enough execution time
		Safe::set_time_limit(30);

		// build the path, including any query
		$path = $items['path'];
		if(isset($items['query']) && $items['query'])
			$path .= '?'.$items['query'];

		// encode the content
		$data = 'title='.urlencode($title)
			.'&url='.urlencode($source)
			.'&excerpt='.urlencode($excerpt)
			.'&blog_name='.urlencode($blog_name);
		$headers = 'Content-Type: application/x-www-form-urlencoded'."\015\012"
			.'Content-Length: '.strlen($data)."\015\012";

		// actual trackback, through HTTP POST
		$request = "POST ".$path." HTTP/1.0\015\012"
			.'Host: '.$host."\015\012"
			."User-Agent: YACS (www.yacs.fr)\015\012"
			."Connection: close\015\012"
			.$headers
			."\015\012"
			.$data;

		// save the request if debug mode
		if(isset($context['debug_trackback']) && ($context['debug_trackback'] == 'Y'))
			Logger::remember('links/links.php', 'Links::ping_as_trackback() request', str_replace("\r\n", "\n", $request), 'debug');

		// submit the request
		fputs($handle, $request);

		// we are interested only in the very first bytes of the response
		$code = fread($handle, 15);
		fclose($handle);

		// save the response if debug mode
		if(isset($context['debug_trackback']) && ($context['debug_trackback'] == 'Y'))
			Logger::remember('links/links.php', 'Links::ping_as_trackback() response', $code.'...', 'debug');

		// check HTTP status
		if(!preg_match('/^HTTP\/[0-9\.]+ 200/', $code))
			return FALSE;

		// successful trackback
		if(isset($context['debug_trackback']) && ($context['debug_trackback'] == 'Y'))
			Logger::remember('links/links.php', 'Links::ping_as_trackback() success', $broker[1], 'debug');
		return TRUE;
	}

	/**
	 * post a new link
	 *
	 * This function populates the error context, where applicable.
	 *
	 * @param array an array of fields
	 * @return the id of the new link, or FALSE on error
	 *
	 * @see feeds/feeds.php
	 * @see links/edit.php
	 * @see links/trackback.php
	 * @see services/ping.php
	**/
	function post(&$fields) {
		global $context;

		// suppress invalid chars, if any
		$fields['link_url'] = trim(preg_replace(FORBIDDEN_IN_URLS, '_', $fields['link_url']), '_');

		// no link
		if(!$fields['link_url']) {
			Logger::error(i18n::s('No link URL has been provided.'));
			return FALSE;
		}

		// no anchor reference
		if(!$fields['anchor']) {
			Logger::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// set default values for this editor
		Surfer::check_default_editor($fields);

		// always remember the date
		$query = "INSERT INTO ".SQL::table_name('links')." SET "
			."anchor='".SQL::escape($fields['anchor'])."', "
			."anchor_id=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', -1),"
			."anchor_type=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', 1),"
			."link_url='".SQL::escape($fields['link_url'])."', "
			."link_target='".SQL::escape(isset($fields['link_target']) ? $fields['link_target'] : '')."', "
			."link_title='".SQL::escape(isset($fields['link_title']) ? $fields['link_title'] : '')."', "
			."title='".SQL::escape(isset($fields['title']) ? $fields['title'] : '')."', "
			."description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."', "
			."edit_name='".SQL::escape($fields['edit_name'])."', "
			."edit_id=".SQL::escape($fields['edit_id']).", "
			."edit_address='".SQL::escape($fields['edit_address'])."', "
			."edit_action='".SQL::escape(isset($fields['edit_action']) ? $fields['edit_action'] : 'link:create')."', "
			."edit_date='".SQL::escape($fields['edit_date'])."', "
			."hits=".SQL::escape(isset($fields['hits']) ? $fields['hits'] : 0);

		// actual update query
		if(SQL::query($query) === FALSE)
			return FALSE;

		// remember the id of the new item
		$fields['id'] = SQL::get_last_id($context['connection']);

		// clear the cache for links
		Links::clear($fields);

		// end of job
		return $fields['id'];
	}

	/**
	 * cap the number of news in the database
	 *
	 * This function deletes oldest entries going beyond the given threshold.
	 *
	 * @param int the maximum number of news entries to keep in the database
	 * @return int count of deleted items
	 *
	 * @see feeds/configure.php
	 * @see feeds/feeds.php
	 */
	function purge_old_news($limit=1000) {
		global $context;

		// lists oldest entries beyond the limit
		$query = "SELECT links.* FROM ".SQL::table_name('links')." AS links "
			." WHERE (links.edit_id = 0)"
			." ORDER BY links.edit_date DESC, links.title LIMIT ".$limit.', 10000';

		// no result
		if(!$result =& SQL::query($query))
			return 0;

		// empty list
		if(!SQL::count($result))
			return 0;

		// build an array of links
		$count = 0;
		while($item =& SQL::fetch($result)) {

			// delete the record in the database
			$query = "DELETE FROM ".SQL::table_name('links')." WHERE id = ".SQL::escape($item['id']);
			SQL::query($query);
			$count++;

		}

		// end of processing
		SQL::free($result);

		// clear the cache for links
		Cache::clear();

		// job done
		return $count;
	}

	/**
	 * update a link
	 *
	 * @param array an array of fields
	 * @return boolean TRUE on success, FALSE on error
	**/
	function put(&$fields) {
		global $context;

		// id cannot be empty
		if(!isset($fields['id']) || !is_numeric($fields['id'])) {
			Logger::error(i18n::s('No item has the provided id.'));
			return FALSE;
		}

		// no link
		if(!$fields['link_url']) {
			Logger::error(i18n::s('No link URL has been provided.'));
			return FALSE;
		}

		// no anchor reference
		if(!$fields['anchor']) {
			Logger::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// set default values for this editor
		Surfer::check_default_editor($fields);

		// update the existing record
		$query = "UPDATE ".SQL::table_name('links')." SET "
			."anchor='".SQL::escape($fields['anchor'])."', "
			."anchor_id=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', -1),"
			."anchor_type=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', 1),"
			."link_url='".SQL::escape($fields['link_url'])."', "
			."link_target='".SQL::escape(isset($fields['link_target']) ? $fields['link_target'] : '')."', "
			."link_title='".SQL::escape(isset($fields['link_title']) ? $fields['link_title'] : '')."', "
			."title='".SQL::escape(isset($fields['title']) ? $fields['title'] : '')."', "
			."description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."'";

		// maybe a silent update
		if(!isset($fields['silent']) || ($fields['silent'] != 'Y')) {
			$query .= ", "
				."edit_name='".SQL::escape($fields['edit_name'])."', "
				."edit_id=".SQL::escape($fields['edit_id']).", "
				."edit_address='".SQL::escape($fields['edit_address'])."', "
				."edit_action='link:update', "
				."edit_date='".SQL::escape($fields['edit_date'])."'";
		}

		// update only one record
		$query .= " WHERE id = ".SQL::escape($fields['id']);

		// do the job
		if(!SQL::query($query))
			return FALSE;

		// clear the cache for links
		Links::clear($fields);

		// report on result
		return TRUE;

	}

	/**
	 * search for some keywords in all links
	 *
	 * @param the search string
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see search.php
	 */
	function &search($pattern, $offset=0, $count=50, $variant='search') {
		global $context;

		// sanity check
		if(!$pattern = trim($pattern)) {
			$output = NULL;
			return $output;
		}

		// match
		$match = "MATCH(title, link_url, description) AGAINST('".SQL::escape($pattern)."' IN BOOLEAN MODE)";

		// the list of links
		$query = "SELECT * FROM ".SQL::table_name('links')." AS links "
			." WHERE $match "
			." ORDER BY links.edit_date DESC"
			." LIMIT ".$offset.','.$count;

		$output =& Links::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * create tables for links
	 *
	 * @see control/setup.php
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['anchor']		= "VARCHAR(64) DEFAULT 'section:1' NOT NULL";
		$fields['anchor_id']	= "MEDIUMINT UNSIGNED NOT NULL";
		$fields['anchor_type']	= "VARCHAR(64) DEFAULT 'section' NOT NULL";
		$fields['link_url'] 	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['link_target']	= "ENUM('I','B') DEFAULT 'I' NOT NULL";
		$fields['link_title']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['title']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['description']	= "TEXT NOT NULL";
		$fields['keywords'] 	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['hits'] 		= "INT UNSIGNED DEFAULT 0 NOT NULL";
		$fields['edit_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_id']		= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['edit_address'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_action']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_date']	= "DATETIME";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX anchor']	= "(anchor)";
		$indexes['INDEX anchor_id'] 	= "(anchor_id)";
		$indexes['INDEX anchor_type']	= "(anchor_type)";
		$indexes['INDEX edit_date'] = "(edit_date)";
		$indexes['INDEX edit_id']	= "(edit_id)";
		$indexes['INDEX hits']		= "(hits)";
		$indexes['INDEX link_url']	= "(link_url)";
		$indexes['INDEX title'] 	= "(title(255))";
		$indexes['FULLTEXT INDEX']	= "full_text(title, link_url, description)";

		return SQL::setup_table('links', $fields, $indexes);
	}

	/**
	 * get some statistics
	 *
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see links/index.php
	 */
	function &stat() {
		global $context;

		// if not associate, restrict to links attached to public published not expired pages
		if(!Surfer::is_associate()) {
			$where = ", ".SQL::table_name('articles')." AS articles "
				." WHERE ((links.anchor_type LIKE 'article') AND (links.anchor_id = articles.id))"
				." AND (articles.active='Y')"
				." AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."'))"
				." AND (links.edit_id > 0)";
		} else {
			$where = "WHERE (links.edit_id > 0)";
		}

		// select among available items
		$query = "SELECT COUNT(links.link_url) as count, MIN(links.edit_date) as oldest_date, MAX(links.edit_date) as newest_date"
			." FROM ".SQL::table_name('links')." AS links ".$where;

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get some statistics for one anchor
	 *
	 * @param the selected anchor (e.g., 'article:12')
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see articles/delete.php
	 * @see articles/view.php
	 * @see categories/delete.php
	 * @see categories/view.php
	 * @see sections/delete.php
	 * @see sections/sections.php
	 * @see sections/view.php
	 * @see skins/layout_home_articles_as_alistapart.php
	 * @see skins/layout_home_articles_as_hardboiled.php
	 * @see skins/layout_home_articles_as_daily.php
	 * @see skins/layout_home_articles_as_newspaper.php
	 * @see skins/layout_home_articles_as_slashdot.php
	 * @see skins/skin_skeleton.php
	 * @see users/delete.php
	 */
	function &stat_for_anchor($anchor) {
		global $context;

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date"
			." FROM ".SQL::table_name('links')." AS links"
			." WHERE links.anchor LIKE '".SQL::escape($anchor)."'";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * reference another page at this site
	 *
	 * The function transforms a local reference (e.g;, [code][user=2][/code])
	 * to an actual link relative to the YACS directory (e.g., [code]users/view.php/2[/code]),
	 * adds a title and, sometimes, set a description as well.
	 *
	 * @param string any string, maybe with a local reference in it
	 * @return an array($url, $title, $description) or NULL
	 *
	 * @see images/view.php
	 * @see links/edit.php
	 * @see shared/codes.php
	 */
	function transform_reference($text) {
		global $context;

		// translate this reference to an internal link
		if(preg_match("/^\[(article|section|file|image|category|user)=(.+?)\]/i", $text, $matches)) {

			switch($matches[1]) {

			// article link
			case 'article':
				if($item =& Articles::get($matches[2]))
					return array(Articles::get_permalink($item), $item['title'], $item['introduction']);
				return array('', $text, '');

			// section link
			case 'section':
				if($item =& Sections::get($matches[2]))
					return array(Sections::get_permalink($item), $item['title'], $item['introduction']);
				return array('', $text, '');

			// file link
			case 'file':
				if($item =& Files::get($matches[2]))
					return array(Files::get_url($matches[2]), $item['title']?$item['title']:str_replace('_', ' ', ucfirst($item['file_name'])));
				return array('', $text, '');

			// image link
			case 'image':
				include_once $context['path_to_root'].'images/images.php';
				if($item =& Images::get($matches[2]))
					return array(Images::get_url($matches[2]), $item['title']?$item['title']:str_replace('_', ' ', ucfirst($item['image_name'])));
				return array('', $text, '');

			// category link
			case 'category':
				if($item =& Categories::get($matches[2]))
					return array(Categories::get_permalink($item), $item['title'], $item['introduction']);
				return array('', $text, '');

			// user link
			case 'user':
				if($item =& Users::get($matches[2]))
					return array(Users::get_permalink($item), $item['full_name']?$item['full_name']:$item['nick_name']);
				return array('', $text, '');

			}
		}

		return array('', $text, '');
	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('links');

?>
