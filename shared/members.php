<?php
/**
 * the database abstraction layer for membership
 *
 * In YACS, membership is a link between one anchor and some related element.
 * Both the anchor and the element are designated by a reference made of a type, a colon character ':',
 * and an id, like for example 'section:123'.
 *
 * [title]Use cases for 1:N relations[/title]
 *
 * Membership is useful to implement one-to-many relations, also known as 1:N links in database theory.
 * Most often this complements natural hierarchical links between items, as in following situations:
 * - Content categorization
 * - Content monitoring
 * - Access management
 *
 * [subtitle]Content categorization[/subtitle]
 * Categorization means that articles or sections (members) are assigned to categories (anchors).
 * Assignments is implemented in categories/select.php, and rendered in various scripts.
 *
 * [subtitle]Content monitoring[/subtitle]
 * Each member may maintain his own watch list, built by assigning users (members) to articles and sections (anchors).
 * Assignment is implemented in users/track.php, and used on content creation.
 *
 * [subtitle]Access management[/subtitle]
 * Editorial responsibilities are given to editors by assigning sections (members) to users (anchors).
 * Assignment is implemented in sections/select.php, and used in scripts related to sections.
 *
 * [title]Sample calls[/title]
 *
 * [php]
 * // link a member to an anchor
 * Members::assign($anchor, $member);
 *
 * // break the association of one member with one anchor
 * Members::free($anchor, $member);
 *
 * // to get ordered articles linked with some anchor
 * Members::list_articles_by_date_for_anchor($anchor, $offset, $count, $variant);
 * Members::list_articles_by_title_for_anchor($anchor, $offset, $count, $variant);
 *
 * // to get the list of anchors for one member, ordered by id
 * Members::list_anchors_for_member($member);
 *
 * // to get ordered categories linked with some member
 * Members::list_categories_by_title_for_member($member, $offset, $count, $variant);
 *
 * // to get ordered sections linked with some member
 * Members::list_sections_by_title_for_anchor($anchor, $offset, $count, $variant);
 * [/php]
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Members {

	/**
	 * link one anchor with another item
	 *
	 * @param string the anchor id (e.g., 'category:123')
	 * @param string the member id (e.g., 'article:456')
	 * @param string the father id, if any (e.g., 'category:456')
	 * @return string either a null string, or some text describing an error to be inserted into the html response
	 *
	 * @see articles/articles.php
	 * @see categories/categories.php
	 * @see categories/set_keyword.php
	 * @see control/import.php
	 * @see sections/select.php
	 * @see services/blog.php
	**/
	function assign($anchor, $member, $father=NULL) {
		global $context;

		// anchor cannot be empty
		if(!$anchor)
			return i18n::s('An anchor is required for this operation.');

		// member cannot be empty
		if(!$member)
			return i18n::s('A member is required for this operation.');

		// don't go further if the membership already exists
		$query = "SELECT id  FROM ".SQL::table_name('members')
			." WHERE (anchor LIKE '".SQL::escape($anchor)."') AND (member LIKE '".SQL::escape($member)."') LIMIT 0, 1";
		if(SQL::query_count($query))
			return NULL;

		// clear the cache
		Cache::clear(array($anchor, $member));

		// boost further queries
		list($member_type, $member_id) = explode(':', $member, 2);

		// insert one new record
		$query = "INSERT INTO ".SQL::table_name('members')." SET"
			." anchor='".SQL::escape($anchor)."',"
			." member='".SQL::escape($member)."',"
			." member_type='".SQL::escape($member_type)."',"
			." member_id='".SQL::escape($member_id)."',"
			." edit_date='".SQL::escape(gmstrftime('%Y-%m-%d %H:%M:%S'))."'";
		SQL::query($query);

		// delete father membership, if instructed to do so
		if($father) {
			$query = "DELETE FROM ".SQL::table_name('members')
				." WHERE (anchor LIKE '".SQL::escape($father)."') AND (member LIKE '".SQL::escape($member)."')";
			SQL::query($query);
		}

		// end of job
		return NULL;
	}

	/**
	 * check whether an anchor is linked to another item
	 *
	 * Some typical cases:
	 * - anchor = 'article:123', member = 'user:456' - watch list of this user
	 * - anchor = 'user:456', member = 'article:456' - page has been assigned to this user
	 *
	 * @param string the anchor id (e.g., 'article:12')
	 * @param string the member id (e.g., 'file:23')
	 * @return boolean either TRUE or FALSE
	 *
	 * @see articles/view.php
	 * @see articles/layout_articles.php
	 * @see articles/layout_articles_as_jive.php
	 * @see users/track.php
	**/
	function check($anchor, $member) {
		global $context;

		// sanity check
		if(!$anchor || !$member)
			return FALSE;

		// cache previous answers
		static $cache;
		if(!is_array($cache))
			$cache = array();

		// cache hit
		if(isset($cache[$anchor.':'.$member]))
			return $cache[$anchor.':'.$member];

		// don't go further if the membership already exists
		$query = "SELECT id  FROM ".SQL::table_name('members')
			." WHERE (anchor LIKE '".SQL::escape($anchor)."') AND (member LIKE '".SQL::escape($member)."') LIMIT 0, 1";
		if(SQL::query_count($query))
			return $cache[$anchor.':'.$member] = TRUE;

		// end of job
		return $cache[$anchor.':'.$member] = FALSE;
	}

	/**
	 * duplicate all members for a given anchor
	 *
	 * This function duplicates records in the database, and changes anchors
	 * to attach new records as per second parameter.
	 *
	 * @param string the source anchor
	 * @param string the target anchor
	 * @return int the number of duplicated records
	 */
	function duplicate_for_anchor($anchor_from, $anchor_to) {
		global $context;

		// look for records attached to this anchor
		$count = 0;
		$query = "SELECT * FROM ".SQL::table_name('members')." WHERE anchor LIKE '".SQL::escape($anchor_from)."'";
		if(($result =& SQL::query($query)) && SQL::count($result)) {

			// process all matching records one at a time
			while($item =& SQL::fetch($result)) {

				// actual duplication
				$query = "INSERT INTO ".SQL::table_name('members')." SET"
					." anchor='".SQL::escape($anchor_to)."',"
					." member='".SQL::escape($item['member'])."',"
					." member_type='".SQL::escape($item['member_type'])."',"
					." member_id='".SQL::escape($item['member_id'])."',"
					." edit_date='".SQL::escape(gmstrftime('%Y-%m-%d %H:%M:%S'))."'";
				if(SQL::query($query))
					$count++;

			}

			// clear the cache for members
			Cache::clear(array($anchor_from, $anchor_to));

		}

		// number of duplicated records
		return $count;
	}

	/**
	 * list all anchors for one member
	 *
	 * @param mixed the member (e.g., 'article:42') or a list of members
	 * @param the offset from the beginning of the list
	 * @param the maximum size of the returned list
	 * @return an array of members anchors
	 */
	function list_anchors_for_member($member, $offset=0, $count=500) {
		global $context;

		// several members
		if(is_array($member)) {
			$items = array();
			foreach($member as $token)
				$items[] = "member LIKE '".SQL::escape($token)."'";
			$where = '('.join(' OR ', $items).')';

		// or only one
		} elseif($member)
			$where = "(member LIKE '".SQL::escape($member)."')";

		// the list of members
		$query = "SELECT anchor FROM ".SQL::table_name('members')
			." WHERE ".$where
			." ORDER BY anchor LIMIT ".$offset.','.$count;
		if(!$result =& SQL::query($query))
			return NULL;

		// empty list
		if(!SQL::count($result))
			return array();

		// build an array of ids
		while($row =& SQL::fetch($result))
			$anchors[] = $row['anchor'];

		// ensure each anchor is represented only once
		$anchors = array_unique($anchors);

		// return the list of ids linked to this member
		return $anchors;
	}

	/**
	 * list most recent articles related to a given category or to any other anchor
	 *
	 * Actually list articles by date, then by title. Note that articles are not ordered within a category list.
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but surfer is a logged user
	 * - article is restricted (active='N'), but surfer is an associate
	 * - article has been officially published
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param the target anchor
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see index.php
	 * @see articles/index.php
	 * @see categories/feed.php
	 * @see categories/print.php
	 * @see categories/view.php
	 * @see files/index.php
	 * @see links/index.php
	 * @see sections/index.php
	 * @see users/index.php
	 * @see users/print.php
	 * @see users/view.php
	 */
	function &list_articles_by_date_for_anchor($anchor, $offset=0, $count=10, $variant=NULL) {
		global $context;

		// locate where we are
		if(!isset($variant))
			$variant = $anchor;

		// limit the scope of the request
		$where = "(articles.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR articles.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR articles.active='N'";

		// include managed sections
		if(count($my_sections = Surfer::assigned_sections()))
			$where .= " OR articles.anchor='section:".join("' OR articles.anchor='section", $my_sections)."'";

		$where .= ")";

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// show only published articles
		$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
			." AND (articles.publish_date < '".$now."')";

		// strip dead pages
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$now."'))";

		// avoid overlap with original articles in user profile
		if(is_string($anchor) && preg_match('/user:(.+)$/i', $anchor, $matches))
			$where .= " AND (articles.create_id NOT LIKE '".$matches[1]."') AND (articles.publish_id NOT LIKE '".$matches[1]."')";

		// articles in sections versus articles in category
		$sections_where = array();
		$categories_where = array();

		// several anchors
		if(is_array($anchor)) {
			foreach($anchor as $token) {
				if(strpos($token, 'category:') === 0)
					$categories_where[] = "members.anchor LIKE '".SQL::escape($token)."'";
				else
					$sections_where[] = "articles.anchor LIKE '".SQL::escape($token)."'";
			}
		} elseif(strpos($anchor, 'section:') === 0)
			$sections_where[] = "articles.anchor LIKE '".SQL::escape($anchor)."'";
		else
			$categories_where[] = "members.anchor LIKE '".SQL::escape($anchor)."'";

		// integrate sections, if any
		$query = '';
		if(count($sections_where)) {
			$query .= "(SELECT articles.*"
			." FROM ".SQL::table_name('articles')." AS articles"
			." WHERE (".join(' OR ', $sections_where).")"
			."	AND ".$where
			." ORDER BY articles.edit_date DESC, articles.title LIMIT ".$offset.','.$count.")"
			." UNION (";
		}

		// articles attached to categories
		$query .= "SELECT articles.*"
			." FROM (".SQL::table_name('members')." AS members"
			.", ".SQL::table_name('articles')." AS articles)"
			." WHERE (".join(' OR ', $categories_where).")"
			."	AND (members.member_type LIKE 'article')"
			."	AND (articles.id = members.member_id)"
			."	AND ".$where
			." ORDER BY articles.edit_date DESC, articles.title LIMIT ".$offset.','.$count;

		if(count($sections_where))
			$query .= ")";

		// use existing listing facility
		$output =& Articles::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list articles watched by a user
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but surfer is a logged user
	 * - article is restricted (active='N'), but surfer is an associate
	 * - article has been officially published
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param the target member
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see users/print.php
	 * @see users/view.php
	 */
	function &list_articles_by_date_for_member($member, $offset=0, $count=10, $variant='full') {
		global $context;

		// limit the scope of the request
		$where = "(articles.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR articles.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR articles.active='N'";

		// include managed sections
		if(count($my_sections = Surfer::assigned_sections()))
			$where .= " OR articles.anchor='section:".join("' OR articles.anchor='section", $my_sections)."'";

		$where .= ")";

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// show only published articles
		$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
			." AND (articles.publish_date < '".$now."')";

		// strip dead pages
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$now."'))";

		// avoid overlap with original articles in user profile
		if(is_string($member) && preg_match('/user:(.+)$/i', $member, $matches))
			$where .= " AND (articles.create_id NOT LIKE '".$matches[1]."') AND (articles.publish_id NOT LIKE '".$matches[1]."')";

		// articles attached to this member
		$query = "SELECT articles.*"
			." FROM (".SQL::table_name('members')." AS members"
			.", ".SQL::table_name('articles')." AS articles)"
			." WHERE (members.member LIKE '".SQL::escape($member)."')"
			."	AND (members.anchor LIKE 'article:%')"
			."	AND (articles.id = SUBSTRING(members.anchor, 9))"
			."	AND ".$where
			." ORDER BY articles.edit_date DESC, articles.title LIMIT ".$offset.','.$count;

		// use existing listing facility
		$output =& Articles::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list alphabetically the articles related to a given category or to any other anchor
	 *
	 * Actually list articles by title, then by date. Note that articles are never ranked into a category list.
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but surfer is a logged user
	 * - article is restricted (active='N'), but surfer is an associate
	 * - article has been officially published
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param the target anchor
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see categories/print.php
	 * @see categories/view.php
	 */
	function &list_articles_by_title_for_anchor($anchor, $offset=0, $count=10, $variant=NULL) {
		global $context;

		// locate where we are
		if(!$variant)
			$variant = $anchor;

		// limit the scope of the request
		$where = "(articles.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR articles.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR articles.active='N'";

		// include managed sections
		if(count($my_sections = Surfer::assigned_sections()))
			$where .= " OR articles.anchor='section:".join("' OR articles.anchor='section", $my_sections)."'";

		$where .= ")";

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// see only published articles in categories
		$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
			." AND (articles.publish_date < '".$now."')";

		// only consider live articles
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$now."'))";

		// the list of articles
		$query = "SELECT articles.*"
			." FROM (".SQL::table_name('members')." AS members"
			.", ".SQL::table_name('articles')." AS articles)"
			." WHERE (members.anchor LIKE '".SQL::escape($anchor)."')"
			."	AND (members.member_type LIKE 'article')"
			."	AND (articles.id = members.member_id)"
			."	AND ".$where
			." ORDER BY articles.title, articles.edit_date DESC LIMIT ".$offset.','.$count;

		// use existing listing facility
		$output =& Articles::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * build the site cloud
	 *
	 * This function lists tags based on their popularity.
	 *
	 * Only categories matching following criteria are returned:
	 * - category is visible (active='Y')
	 * - category is restricted (active='R'), but surfer is a logged user
	 * - category is restricted (active='N'), but surfer is an associate
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param the target anchor
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_categories_by_count_for_anchor($anchor, $offset=0, $count=10, $variant='full') {
		global $context;

		// display active and restricted items
		$where = "categories.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR categories.active='R'";
		if(Surfer::is_empowered())
			$where .= " OR categories.active='N'";
		$where = '('.$where.')';

		// only consider live categories
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where .= " AND ((categories.expiry_date is NULL)"
			."	OR (categories.expiry_date <= '".NULL_DATE."') OR (categories.expiry_date > '".$now."'))";

		// scope is limited to one anchor
		if($anchor)
			$where .= " AND (categories.anchor LIKE ''".SQL::escape($anchor)."')";

		// avoid weekly and monthly publications if overall request
		else
			$where .= " AND (categories.nick_name NOT LIKE 'week%') AND (categories.nick_name NOT LIKE '".i18n::c('weekly')."')"
				." AND (categories.nick_name NOT LIKE 'month%') AND (categories.nick_name NOT LIKE '".i18n::c('monthly')."')";

		// the list of categories
		$query = "SELECT COUNT(members.id) as importance, categories.*	FROM ".SQL::table_name('members')." AS members"
			.", ".SQL::table_name('categories')." AS categories"
			." WHERE (members.anchor LIKE 'category:%')"
			."	AND (".$where.")"
			."	AND (categories.id = SUBSTRING(members.anchor, 10))"
			." GROUP BY members.anchor"
			." ORDER BY importance DESC, categories.title, categories.edit_date DESC LIMIT ".$offset.','.$count;

		// use existing listing facility
		include_once $context['path_to_root'].'categories/categories.php';
		$output =& Categories::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list alphabetically the categories related to a given article or to any other anchor
	 *
	 * Actually list categories by rank, then by title, then by date.
	 * If you select to not use the ranking system, categories will be ordered by title only.
	 * Else categories with a low ranking mark will appear first,
	 * and categories with a high ranking mark will be put at the end of the list.
	 *
	 * Only categories matching following criteria are returned:
	 * - category is visible (active='Y')
	 * - category is restricted (active='R'), but surfer is a logged user
	 * - category is restricted (active='N'), but surfer is an associate
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param the target member
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see articles/view.php
	 * @see articles/layout_articles.php
	 * @see articles/layout_articles_as_boxesandarrows.php
	 * @see articles/layout_articles_as_daily.php
	 * @see articles/layout_articles_as_slashdot.php
	 * @see categories/select.php
	 * @see services/blog.php
	 */
	function &list_categories_by_title_for_member($member, $offset=0, $count=10, $variant='full') {
		global $context;

		// display active and restricted items
		$where = "categories.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR categories.active='R'";
		if(Surfer::is_empowered())
			$where .= " OR categories.active='N'";
		$where = '('.$where.')';

		// only consider live categories
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where .= " AND ((categories.expiry_date is NULL)"
			."	OR (categories.expiry_date <= '".NULL_DATE."') OR (categories.expiry_date > '".$now."'))";

		// avoid weekly and monthly publications in list of articles
		if($variant == 'raw')
			$where .= " AND (categories.nick_name NOT LIKE 'week%') AND (categories.nick_name NOT LIKE '".i18n::c('weekly')."')"
				." AND (categories.nick_name NOT LIKE 'month%') AND (categories.nick_name NOT LIKE '".i18n::c('monthly')."')";

		// the list of categories
		$query = "SELECT categories.*	FROM ".SQL::table_name('members')." AS members"
			.", ".SQL::table_name('categories')." AS categories"
			." WHERE (members.member LIKE '".SQL::escape($member)."')"
			."	AND (members.anchor LIKE 'category:%')"
			."	AND (categories.id = SUBSTRING(members.anchor, 10))"
			."	AND (".$where.")"
			." ORDER BY categories.rank, categories.title, categories.edit_date DESC LIMIT ".$offset.','.$count;

		// use existing listing facility
		include_once $context['path_to_root'].'categories/categories.php';
		$output =& Categories::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list alphabetically the editors assigned to a given member
	 *
	 * Actually list users by title, then by date.
	 *
	 * Only users matching following criteria are returned:
	 * - user is visible (active='Y')
	 * - user is restricted (active='R'), but surfer is a logged user
	 * - user is restricted (active='N'), but surfer is an associate
	 * - user is a member or an associate
	 *
	 * @param the target anchor
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see sections/view.php
	 */
	function &list_editors_by_name_for_member($member, $offset=0, $count=10, $variant=NULL) {
		global $context;

		// display active and restricted items
		$where = "users.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR users.active='R'";
		if(Surfer::is_empowered())
			$where .= " OR users.active='N'";

		// the list of users
		$query = "SELECT users.*	FROM ".SQL::table_name('members')." AS members"
			.", ".SQL::table_name('users')." AS users"
			." WHERE (members.member LIKE '".SQL::escape($member)."')"
			."	AND (members.anchor LIKE 'user:%')"
			."	AND (users.id = SUBSTRING(members.anchor, 6))"
			."	AND ((users.capability = 'M') OR (users.capability = 'A'))"
			."	AND (".$where.")"
			." ORDER BY users.nick_name, users.edit_date DESC LIMIT ".$offset.','.$count;

		// use existing listing facility
		$output =& Users::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list alphabetically the sections related to any anchor
	 *
	 * Actually list sections by rank, then by title, then by date.
	 * If you select to not use the ranking system, sections will be ordered by title only.
	 * Else sections with a low ranking mark will appear first,
	 * and sections with a high ranking mark will be put at the end of the list.
	 *
	 * Only sections matching following criteria are returned:
	 * - section is visible (active='Y')
	 * - section is restricted (active='R'), but surfer is a logged user
	 * - section is restricted (active='N'), but surfer is an associate
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param the target anchor
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see sections/select.php
	 */
	function &list_sections_by_title_for_anchor($anchor, $offset=0, $count=10, $variant='compact') {
		global $context;

		// display active and restricted items
		$where = "sections.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR sections.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR sections.active='N'";
		$where = '('.$where.')';

		// only consider live sections
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where .= " AND ((sections.expiry_date is NULL)"
			."OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$now."'))";

		// the list of sections
		$query = "SELECT sections.*"
			."	FROM (".SQL::table_name('members')." AS members"
			.", ".SQL::table_name('sections')." AS sections)"
			." WHERE (members.anchor LIKE '".SQL::escape($anchor)."')"
			."	AND (members.member_type = 'section')"
			."	AND (members.member_id = sections.id)"
			."	AND (".$where.")"
			." ORDER BY sections.title, sections.edit_date DESC LIMIT ".$offset.','.$count;

		// use existing listing facility
		$output =& Sections::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list most recent sections related to a given user
	 *
	 * Only sections matching following criteria are returned:
	 * - section is visible (active='Y')
	 * - section is restricted (active='R'), but surfer is a logged user
	 * - section is restricted (active='N'), but surfer is an associate
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param the target member
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see users/print.php
	 * @see users/view.php
	 */
	function &list_sections_by_date_for_member($member, $offset=0, $count=10, $variant='full') {
		global $context;

		// limit the scope of the request
		$where = "sections.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR sections.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR sections.active='N'";

		// include managed sections
		if(is_callable(array('Surfer', 'assigned_sections')) && count($my_sections = Surfer::assigned_sections()))
			$where .= " OR sections.id LIKE ".join(" OR sections.id LIKE ", $my_sections);

		$where = '('.$where.')';

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// strip dead pages
		$where .= " AND ((sections.expiry_date is NULL) "
				."OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$now."'))";

		// sections attached to users
		$query = "SELECT sections.*"
			."	FROM (".SQL::table_name('members')." AS members"
			.", ".SQL::table_name('sections')." AS sections)"
			." WHERE (members.member LIKE '".SQL::escape($member)."')"
			."	AND (members.anchor LIKE 'section:%')"
			."	AND (sections.id = SUBSTRING(members.anchor, 9))"
			."	AND ".$where
			." ORDER BY sections.edit_date DESC, sections.title LIMIT ".$offset.','.$count;

		// use existing listing facility
		$output =& Sections::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list alphabetically the readers related to a given member
	 *
	 * Actually list users by title, then by date.
	 *
	 * Only users matching following criteria are returned:
	 * - user is visible (active='Y')
	 * - user is restricted (active='R'), but surfer is a logged user
	 * - user is restricted (active='N'), but surfer is an associate
	 * - user is a subscriptor
	 *
	 * @param the target anchor
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see sections/view.php
	 */
	function &list_readers_by_name_for_member($member, $offset=0, $count=10, $variant=NULL) {
		global $context;

		// display active and restricted items
		$where = "users.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR users.active='R'";
		if(Surfer::is_empowered())
			$where .= " OR users.active='N'";

		// the list of users
		$query = "SELECT users.*	FROM ".SQL::table_name('members')." AS members"
			.", ".SQL::table_name('users')." AS users"
			." WHERE (members.member LIKE '".SQL::escape($member)."')"
			."	AND (members.anchor LIKE 'user:%')"
			."	AND (users.id = SUBSTRING(members.anchor, 6))"
			."	AND (users.capability = 'S')"
			."	AND (".$where.")"
			." ORDER BY users.nick_name, users.edit_date DESC LIMIT ".$offset.','.$count;

		// use existing listing facility
		$output =& Users::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list alphabetically users assigned to an anchor
	 *
	 * Only users matching following criteria are returned:
	 * - user is visible (active='Y')
	 * - user is restricted (active='R'), but surfer is a logged user
	 * - user is restricted (active='N'), but surfer is an associate
	 *
	 * @param the target anchor
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @param string an id to avoid, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see categories/view.php
	 */
	function &list_users_by_posts_for_anchor($anchor, $offset=0, $count=10, $variant=NULL, $to_avoid=NULL) {
		global $context;

		// locate where we are
		if(!$variant)
			$variant = $anchor;

		// limit the scope of the request
		$where = "users.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR users.active='R'";
		if(Surfer::is_associate())
			$where .= " OR users.active='N'";
		$where = '('.$where.')';

		// avoid this one
		if($to_avoid)
			$where .= " AND (users.id != '".SQL::escape($to_avoid)."')";

		// the list of users
		$query = "SELECT users.*	FROM ".SQL::table_name('members')." AS members"
			.", ".SQL::table_name('users')." AS users"
			." WHERE (members.anchor LIKE '".SQL::escape($anchor)."')"
			."	AND (members.member_type LIKE 'user')"
			."	AND (users.id = members.member_id)"
			."	AND ".$where
			." ORDER BY users.posts DESC, users.nick_name LIMIT ".$offset.','.$count;

		// use existing listing facility
		$output =& Users::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list alphabetically users with some member
	 *
	 * Only users matching following criteria are returned:
	 * - user is visible (active='Y')
	 * - user is restricted (active='R'), but surfer is a logged user
	 * - user is restricted (active='N'), but surfer is an associate
	 *
	 * @param string reference to the associated item
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @param string an id to avoid, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see users/select.php
	 */
	function &list_users_by_posts_for_member($member, $offset=0, $count=10, $variant='compact', $to_avoid=NULL) {
		global $context;

		// return by reference
		$output = NULL;

		// list all anchors for this member
		$query = "SELECT anchor FROM ".SQL::table_name('members')
			." WHERE (member LIKE '".SQL::escape($member)."')"
			."	AND (anchor like 'user:%')"
			." ORDER BY anchor LIMIT ".$offset.','.$count;
		if(!$result =& SQL::query($query))
			return $output;

		// empty list
		if(!SQL::count($result))
			return $output;

		// build an array of ids
		$ids = array();
		while($row =& SQL::fetch($result)) {

			// avoid this one
			if($to_avoid && ($row['anchor'] == 'user:'.$to_avoid))
				continue;

			// remember this id
			$ids[] = str_replace('user:', '', $row['anchor']);

		}

		// ensure each member is represented only once
		$ids = array_unique($ids);

		// sanity check
		if(!count($ids))
			return $output;

		// display active and restricted items
		$where = "users.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR users.active='R'";
		if(Surfer::is_empowered())
			$where .= " OR users.active='N'";
		$where = '('.$where.')';

		// only include users who want to receive mail messages
		if($variant == 'mail')
			$where .= " AND (without_messages != 'Y')";

		// the list of users
		$query = "SELECT *	FROM ".SQL::table_name('users')." AS users"
			." WHERE (id = ".join(" OR id = ", $ids).")"
			."	AND ".$where
			." ORDER BY users.posts DESC, users.nick_name LIMIT ".$offset.','.$count;

		// use existing listing facility
		$output =& Users::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list watchers of given anchor
	 *
	 * Actually list users by decreasing level of contribution.
	 *
	 * @param mixed, either a string the target anchor, or an array of anchors
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @param array users assigned to the reference, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_watchers_by_posts_for_anchor($anchor, $offset=0, $count=200, $variant='raw', $restricted=NULL) {
		global $context;

		// several anchors
		if(is_array($anchor)) {
			$items = array();
			foreach($anchor as $token)
				$items[] = "members.anchor LIKE '".SQL::escape($token)."'";
			$where = '('.join(' OR ', $items).')';

		// or only one
		} elseif($anchor)
			$where = "(members.anchor LIKE '".SQL::escape($anchor)."')";

		// security constraint
		if($restricted && is_array($restricted))
			$where .= " AND (users.id = ".join(" OR users.id = ", $restricted).")";

		// the list of users
		$query = "SELECT users.* FROM ".SQL::table_name('members')." AS members"
			.", ".SQL::table_name('users')." AS users"
			." WHERE ".$where
			."	AND (members.member_type LIKE 'user')"
			."	AND (users.id = members.member_id)"
			." GROUP BY users.id ORDER BY users.posts DESC, users.edit_date DESC LIMIT ".$offset.','.$count;

		// use existing listing facility
		$output =& Users::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * create tables for members
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['anchor']		= "VARCHAR(64) NOT NULL";
		$fields['member']		= "VARCHAR(64) NOT NULL";
		$fields['member_type']	= "VARCHAR(64) NOT NULL";
		$fields['member_id']	= "VARCHAR(64) NOT NULL";
		$fields['edit_date']	= "DATETIME";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX anchor']	= "(anchor)";
		$indexes['INDEX member']	= "(member)";
		$indexes['INDEX member_type']	= "(member_type)";
		$indexes['INDEX member_id'] = "(member_id)";
		$indexes['INDEX edit_date'] 	= "(edit_date)";

		return SQL::setup_table('members', $fields, $indexes);
	}

	/**
	 * get some statistics for articles linked to one anchor
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but surfer is a logged user
	 * - article is restricted (active='N'), but surfer is an associate
	 * - article has been officially published
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param the selected anchor (e.g., 'category:12')
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see categories/layout_categories.php
	 * @see categories/layout_categories_as_yahoo.php
	 * @see categories/view.php
	 */
	function &stat_articles_for_anchor($anchor) {
		global $context;

		// limit the scope of the request
		$where = "(articles.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR articles.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR articles.active='N'";

		// include managed sections
		if(count($my_sections = Surfer::assigned_sections()))
			$where .= " OR articles.anchor='section:".join("' OR articles.anchor='section", $my_sections)."'";

		$where .= ")";

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// always only consider published articles
		$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
			." AND (articles.publish_date < '".$now."')";

		// only consider live articles
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$now."'))";

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(articles.edit_date) as oldest_date, MAX(articles.edit_date) as newest_date"
			." FROM ".SQL::table_name('members')." AS members"
			.", ".SQL::table_name('articles')." AS articles"
			." WHERE (members.anchor LIKE '".SQL::escape($anchor)."')"
			."	AND (members.member_type LIKE 'article')"
			."	AND (articles.id = members.member_id)"
			."	AND (".$where.")";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get some statistics for sections linked to one anchor
	 *
	 * Only sections matching following criteria are returned:
	 * - section is visible (active='Y')
	 * - section is restricted (active='R'), but surfer is a logged user
	 * - section is restricted (active='N'), but surfer is an associate
	 * - section has been officially published
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param the selected anchor (e.g., 'category:12')
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see categories/layout_categories.php
	 * @see categories/layout_categories_as_yahoo.php
	 * @see categories/view.php
	 */
	function &stat_sections_for_anchor($anchor) {
		global $context;

		// limit the scope of the request
		$where = "sections.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR sections.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR sections.active='N'";

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// only consider live sections
		$where = "(".$where.") "
			."AND ((sections.expiry_date is NULL) "
				."OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$now."'))";

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(sections.edit_date) as oldest_date, MAX(sections.edit_date) as newest_date"
			." FROM ".SQL::table_name('members')." AS members"
			.", ".SQL::table_name('sections')." AS sections"
			." WHERE (members.anchor LIKE '".SQL::escape($anchor)."')"
			."	AND (members.member_type LIKE 'section')"
			."	AND (sections.id = members.member_id)"
			."	AND (".$where.")";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get some statistics for users linked to one anchor
	 *
	 * Only users matching following criteria are returned:
	 * - user is visible (active='Y')
	 * - user is restricted (active='R'), but surfer is a logged user
	 * - user is restricted (active='N'), but surfer is an associate
	 *
	 * @param the selected anchor (e.g., 'category:12')
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see categories/view.php
	 */
	function &stat_users_for_anchor($anchor) {
		global $context;

		// limit the scope of the request
		$where = "users.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR users.active='R'";
		if(Surfer::is_associate())
			$where .= " OR users.active='N'";

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(users.edit_date) as oldest_date, MAX(users.edit_date) as newest_date"
			." FROM ".SQL::table_name('members')." AS members"
			.", ".SQL::table_name('users')." AS users"
			." WHERE (members.anchor LIKE '".SQL::escape($anchor)."')"
			."	AND (members.member_type LIKE 'user')"
			."	AND (users.id = members.member_id)"
			."	AND (".$where.")";
		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * toggle a membership
	 *
	 * The father parameter is used to specialize a membership to a sub-category.
	 *
	 * @param string the anchor id (e.g., 'category:123')
	 * @param string the member id (e.g., 'article:456')
	 * @param string the father id, if any (e.g., 'category:456')
	 * @return string either a null string, or some text describing an error to be inserted into the html response
	 *
	 * @see categories/select.php
	 * @see users/track.php
	**/
	function toggle($anchor, $member, $father=NULL) {
		global $context;

		// anchor cannot be empty
		if(!$anchor)
			return i18n::s('An anchor is required for this operation.');

		// member cannot be empty
		if(!$member)
			return i18n::s('A member is required for this operation.');

		// clear the cache
		Cache::clear(array($anchor, $member, $father));

		// check if the membership already exists
		$query = "SELECT id  FROM ".SQL::table_name('members')
			." WHERE (anchor LIKE '".SQL::escape($anchor)."') AND (member LIKE '".SQL::escape($member)."') LIMIT 0, 1";

		// delete an existing membership
		if(SQL::query_count($query)) {
			$query = "DELETE FROM ".SQL::table_name('members')
				." WHERE (anchor LIKE '".SQL::escape($anchor)."') AND (member LIKE '".SQL::escape($member)."')";

		// insert one new record
		} else {

			// boost further queries
			list($member_type, $member_id) = explode(':', $member, 2);

			// insert one new record
			$query = "INSERT INTO ".SQL::table_name('members')." SET"
				." anchor='".SQL::escape($anchor)."',"
				." member='".SQL::escape($member)."',"
				." member_type='".SQL::escape($member_type)."',"
				." member_id='".SQL::escape($member_id)."',"
				." edit_date='".SQL::escape(gmstrftime('%Y-%m-%d %H:%M:%S'))."'";
		}

		// update the database
		if(SQL::query($query) === FALSE)
			return NULL;

		// delete the father membership, if any
		if($father) {
			$query = "DELETE FROM ".SQL::table_name('members')
				." WHERE (anchor LIKE '".SQL::escape($father)."') AND (member LIKE '".SQL::escape($member)."')";
			SQL::query($query);
		}

		// end of job
		return NULL;
	}

	/**
	 * unlink one anchor with another item
	 *
	 * @param string the anchor id (e.g., 'article:12')
	 * @param string the member id (e.g., 'file:23')
	 * @return string either a null string, or some text describing an error to be inserted into the html response
	**/
	function free($anchor, $member) {
		global $context;

		// anchor cannot be empty
		if(!$anchor)
			return i18n::s('An anchor is required for this operation.');

		// member cannot be empty
		if(!$member)
			return i18n::s('A member is required for this operation.');

		// delete all matching records in the database
		$query = "DELETE FROM ".SQL::table_name('members')
			." WHERE (anchor LIKE '".SQL::escape($anchor)."') AND (member LIKE '".SQL::escape($member)."')";
		SQL::query($query);

		// clear the cache
		Cache::clear(array($anchor, $member));

		// end of job
		return NULL;
	}

	/**
	 * delete all membership information for one reference
	 *
	 * @param string the suppressed reference
	 */
	function unlink_for_reference($reference=NULL) {
		global $context;

		// delete all uses of this reference
		$query = "DELETE FROM ".SQL::table_name('members')
			." WHERE (anchor LIKE '".SQL::escape($reference)."') OR (member LIKE '".SQL::escape($reference)."')";
		SQL::query($query);

	}

}
?>