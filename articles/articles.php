<?php
/**
 * the database abstraction layer for articles
 *
 * @todo add a field to count words in a post
 * @todo add new monitoring layout http://www.socialtext.com/products/tour/recentchanges
 * @todo add a read-only ticket for protected pages
 * @todo place in bin on deletion
 *
 * The several versions of article content are now saved for history, and may be restored at any time.
 *
 * @see versions/versions.php
 *
 * [title]How to lock an article?[/title]
 *
 * An article can be locked to prevent modification.
 * This feature only concerns regular members of the community, as associates and editors are always allowed to add, change of remove any page.
 *
 * [title]How to manage options for articles?[/title]
 *
 * The options field is a convenient place to save attributes for any article without extending the database schema.
 * As articles are commonly used to anchor some pages, their options can be also checked through the [code]has_option()[/code]
 * member function of the [code]Anchor[/code] interface. Check [script]shared/anchor.php[/script] for more information.
 *
 * This means that some options are used within the context of one article (eg, [code]no_links[/code]),
 * while others can be used with related items as well.
 *
 * Specific options to be processed by advanced overlays are not described hereafter.
 * One example of this is the optional keyword '[code]home_with_results[/code]' for the rendering of polls.
 * Please check documentation pages for any overlay you use, like [script]overlays/poll.php[/script].
 *
 * You can combine any of following keywords in the field for options, with the separator (spaces, tabs, commas) of your choice:
 *
 * [*] [code]files_by_title[/code] - When viewing articles, order attached files by alphabetical order instead of using edition time information.
 * This option may prove useful to structure a list of files.
 * For example, on a page describing a complex project, you would like to offer an introduction to the project ('[code]1.introduction.doc[/code]'),
 * then a report on initial issue ('[code]2.the issue.ppt[/code]'), and a business case for the solution ('[code]3.profit_and_loss.xls[/code]').
 * By adjusting file names and titles as shown, and by setting the option [code]files_by_title[/code], you would achieve a nice and logical thing.
 *
 * [*] [code]formatted[/code] - The YACS page factory is disabled, since the description contains formatting tags.
 * Use this option if you copy the source of a HTML or of a XHTML page, and paste it into an article at your server.
 * Note that this keyword is also accepted if it is formatted as a YACS code ('[code]&#91;formatted]'[/code])
 * at the very beginning of the description field.
 *
 * [*] [code]hardcoded[/code] - The YACS page factory is disabled, except that new lines are changed to (X)HTML breaks.
 * Use this option if you copy some raw text file (including a mail message) and make a page out of it.
 * Note that this keyword is also accepted if it is formatted as a YACS code ('[code]&#91;hardcoded]'[/code])
 * at the very beginning of the description field.
 *
 * [*] [code]links_by_title[/code] - When wiewing articles, order attached links by alphabetical order instead of using edition time information.
 * This options works like [code]files_by_title[/code], except that it applies to link.
 * Use it to create nice and ordered bookmarks.
 *
 * [*] [code]no_comments[/code] - New comments cannot be posted on this page.
 *
 * [*] [code]no_files[/code] - New files cannot be attached to this page.
 *
 * [*] [code]no_links[/code] - New links cannot be posted to this article.
 *
 * [*] [code]skin_&lt;xxxx&gt;[/code] - Select one skin explicitly.
 * Use this option to apply a specific skin to a page.
 * This setting is the most straightforward way of introducing some skin to web surfers.
 *
 * [*] [code]variant_&lt;xxxx&gt;[/code] - Select one skin variant explicitly.
 * Usually only the variant '[code]articles[/code]' is used throughout articles.
 * This can be changed to '[code]xxxx[/code]' by using the option [code]variant_&lt;xxxx&gt;[/code].
 * Then the underlying skin may adapt to this code by looking at [code]$context['skin_variant'][/code].
 * Basically, use variants to change the rendering of individual articles of your site, if the skin allows it.
 *
 *
 * Also, a specific option is available to handle the article at the front page:
 *
 * [*] [code]none[/code] - Don't mention this published article at the site front page.
 * Use this option to avoid that special pages add noise to the front page.
 * For example, while building the on-line manual of YACS this option has been set to intermediate pages,
 * that are only featuring lists of following pages.
 *
 *
 * [title]How to order articles and to manage sticky pages?[/title]
 *
 * Usually articles are ranked by edition date, with the most recent page coming first.
 * You can change this 'natural' order by modifying the value of the rank field.
 *
 * What is the result obtained, depending on the value set?
 *
 * [*] 10000 - This is the default value. All articles created by YACS are ranked equally.
 *
 * [*] Less than 10000 - Useful to create sticky and ordered pages.
 * Sticky, since these pages will always come first.
 * Ordered, since the lower rank values come before higher rank values.
 * Pages that have the same rank value are ordered by dates, with the newest item coming first.
 * This lets you arrange precisely the order of sticky pages.
 *
 * [*] More than 10000 - To reject pages at the end of lists.
 *
 *
 * @author Bernard Paques
 * @author Florent
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Mark
 * @tester Fernand Le Chien
 * @tester NickR
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Articles {

	/**
	 * check if new articles can be added
	 *
	 * This function returns TRUE if articles can be added to some place,
	 * and FALSE otherwise.
	 *
	 * The function prevents the creation of new articles when:
	 * - the global parameter 'users_without_submission' has been set to 'Y'
	 * - provided item has been locked --but not its parent anchor!
	 * - item has some option 'no_articles' that prevents new articles
	 * - the anchor has some option 'no_articles' that prevents new articles
	 *
	 * Then the function allows for new articles when:
	 * - surfer has been authenticated as a valid member
	 * - or parameter 'users_without_teasers' has not been set to 'Y'
	 *
	 * Then, ultimately, the default is not allow for the creation of new
	 * articles.
	 *
	 * @param object an instance of the Anchor interface, if any
	 * @param array a set of item attributes, if any
	 * @return TRUE or FALSE
	 */
	function are_allowed($anchor=NULL, $item=NULL) {
		global $context;

		// articles are prevented in anchor
		if(is_object($anchor) && $anchor->has_option('no_articles'))
			return FALSE;

		// articles are prevented in item
		if(isset($item['options']) && is_string($item['options']) && preg_match('/\bno_articles\b/i', $item['options']))
			return FALSE;

		// articles are prevented in item, through layout
		if(isset($item['articles_layout']) && ($item['articles_layout'] == 'none'))
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

		// no regular articles in this section
		if(isset($item['articles_layout']) && ($item['articles_layout'] == 'none'))
			return FALSE;

		// surfer screening
		if(isset($item['active']) && ($item['active'] == 'N') && !Surfer::is_empowered())
			return FALSE;
		if(isset($item['active']) && ($item['active'] == 'R') && !Surfer::is_logged())
			return FALSE;

		// item has been locked
		if(isset($item['locked']) && is_string($item['locked']) && ($item['locked'] == 'Y'))
			return FALSE;

		// anchor has been locked --only used when there is no item provided
		if(!isset($item['id']) && is_object($anchor) && $anchor->has_option('locked'))
			return FALSE;

		// authenticated members are allowed to add articles
		if(Surfer::is_member())
			return TRUE;

		// anonymous contributions are allowed for this anchor
		if(is_object($anchor) && $anchor->is_editable())
			return TRUE;

		// anonymous contributions are allowed for this section
		if(isset($item['content_options']) && preg_match('/\banonymous_edit\b/i', $item['content_options']))
			return TRUE;

		// anonymous contributions are allowed for this item
		if(isset($item['options']) && preg_match('/\banonymous_edit\b/i', $item['options']))
			return TRUE;

		// teasers are activated
		if(!Surfer::is_logged() && (!isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y')))
			return TRUE;

		// the default is to not allow for new articles
		return FALSE;
	}

	/**
	 * clear cache entries for one item
	 *
	 * @param array item attributes
	 */
	function clear(&$item) {

		// where this item can be displayed
		$topics = array('articles', 'sections', 'categories', 'users');

		// clear anchor page
		if(isset($item['anchor']))
			$topics[] = $item['anchor'];

		// clear this page
		if(isset($item['id']))
			$topics[] = 'article:'.$item['id'];

		// clear the cache
		Cache::clear($topics);

	}

	/**
	 * count records for one anchor
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - article is protected (active='N'), but surfer is an associate, and we are not feeding someone
	 * - surfer is anonymous or the variant is 'boxes', and article has been officially published
	 * - logged surfers are restricted to their own articles, plus published articles
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param string the selected anchor (e.g., 'section:12')
	 * @param boolean FALSE to include sticky pages, TRUE otherwise
	 * @return int the resulting count, or NULL on error
	 */
	function count_for_anchor($anchor, $without_sticky=FALSE) {
		global $context;

		// sanity check
		if(!$anchor)
			return NULL;

		// profiling mode
		if($context['with_profile'] == 'Y')
			logger::profile('articles::count_for_anchor');

		// select among active items
		$where = "articles.active='Y'";

		// add restricted items to authenticated surfers, or if teasers are allowed
		if(Surfer::is_logged()
			|| !isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			$where .= " OR articles.active='R'";

		// associates, editors and readers may see everything
		if(Surfer::is_empowered('S'))
			$where .= " OR articles.active='N'";

		$where = '('.$where.')';

		// avoid sticky articles
		if($without_sticky)
			$where .= " AND (articles.rank >= 10000)";

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// anonymous surfers and subscribers will see only published articles
		if(!Surfer::is_member()) {
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$now."')";

		// logged surfers that are non-associates are restricted to their own articles, plus published articles
		} elseif(!Surfer::is_empowered()) {
			$where .= " AND ((articles.create_id='".Surfer::get_id()."') OR (NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$now."')))";
		}

		// only consider live articles
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$now."'))";

		// select among available items
		$query = "SELECT COUNT(*) as count"
			." FROM ".SQL::table_name('articles')." AS articles"
			." WHERE (articles.anchor LIKE '".SQL::escape($anchor)."') AND (".$where.")";

		return SQL::query_scalar($query);
	}

	/**
	 * delete one article
	 *
	 * @param int the id of the article to delete
	 * @return boolean TRUE on success, FALSE otherwise
	 *
	 * @see articles/delete.php
	 * @see services/blog.php
	 */
	function delete($id) {
		global $context;

		// load the record
		$item =& Articles::get($id);
		if(!isset($item['id']) || !$item['id']) {
			Skin::error(i18n::s('No item has the provided id.'));
			return FALSE;
		}

		// delete related items
		Anchors::delete_related_to('article:'.$item['id']);

		// delete the record in the database
		$query = "DELETE FROM ".SQL::table_name('articles')." WHERE id = ".SQL::escape($item['id']);
		if(SQL::query($query) === FALSE)
			return FALSE;

		// remember overlay deletion
		include_once '../overlays/overlay.php';
		if(isset($item['overlay']) && ($overlay = Overlay::load($item))) {
			$item['self_reference'] = 'article:'.$item['id'];
			$item['self_url'] = Articles::get_permalink($item);
			$overlay->remember('delete', $item);
		}

		// job done
		return TRUE;
	}

	/**
	 * delete all articles for a given anchor
	 *
	 * @param string the anchor to check (e.g., 'section:123')
	 * @return void
	 *
	 * @see shared/anchors.php
	 */
	function delete_for_anchor($anchor) {
		global $context;

		// seek all records attached to this anchor
		$query = "SELECT id FROM ".SQL::table_name('articles')." AS articles "
			." WHERE articles.anchor LIKE '".SQL::escape($anchor)."'";
		if(!$result =& SQL::query($query))
			return;

		// empty list
		if(!SQL::count($result))
			return;

		// delete silently all matching items
		while($row =& SQL::fetch($result))
			Articles::delete($row['id']);
	}

	/**
	 * duplicate all articles for a given anchor
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
		$query = "SELECT * FROM ".SQL::table_name('articles')." WHERE anchor LIKE '".SQL::escape($anchor_from)."'";
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
				if($new_id = Articles::post($item)) {

					// more pairs of strings to transcode
					$transcoded[] = array('/\[article='.preg_quote($old_id, '/').'/i', '[article='.$new_id);
					$transcoded[] = array('/\[next='.preg_quote($old_id, '/').'/i', '[next='.$new_id);
					$transcoded[] = array('/\[previous='.preg_quote($old_id, '/').'/i', '[previous='.$new_id);

					// duplicate elements related to this item
					Anchors::duplicate_related_to('article:'.$old_id, 'article:'.$new_id);

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
	 * get one article by id, nick name or by handle
	 *
	 * @param int the id of the article
	 * @param boolean TRUE to always fetch a fresh instance, FALSE to enable cache
	 * @return the resulting $item array, with at least keys: 'id', 'title', 'description', etc.
	 */
	function &get($id, $mutable=FALSE) {
		$output = Articles::get_attributes($id, '*', $mutable);
		return $output;
	}

	/**
	 * get only some attributes
	 *
	 * @param int the id of the article
	 * @param mixed names of the attributes to return
	 * @param boolean TRUE to always fetch a fresh instance, FALSE to enable cache
	 * @return the resulting $item array, with at least keys: 'id', 'title', 'description', etc.
	 */
	function &get_attributes($id, $attributes, $mutable=FALSE) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// ensure proper unicode encoding
		$id = (string)$id;
		$id = utf8::encode($id);

		// strip extra text from enhanced ids '3-page-title' -> '3'
		if($position = strpos($id, '-'))
			$id = substr($id, 0, $position);

		// cache previous answers
		static $cache;
		if(!is_array($cache))
			$cache = array();

		// cache hit, but only for immutable objects
		if(!$mutable && isset($cache[$id]))
			return $cache[$id];

		// search by id
		if(is_numeric($id))
			$query = "SELECT ".SQL::escape($attributes)." FROM ".SQL::table_name('articles')
				." WHERE (id = ".SQL::escape((integer)$id).")";

		// or look for given name of handle
		else
			$query = "SELECT ".SQL::escape($attributes)." FROM ".SQL::table_name('articles')
				." WHERE (nick_name LIKE '".SQL::escape($id)."') OR (handle LIKE '".SQL::escape($id)."')"
				." ORDER BY publish_date DESC LIMIT 1";

		// do the job
		$output =& SQL::query_first($query);

		// save in cache
		if(isset($output['id']))
			$cache[$id] = $output;

		// return by reference
		return $output;
	}

	/**
	 * list articles with a given overlay identifier
	 *
	 * @param string the target overlay identifier
	 * @return array of page ids that match the provided identifier, else NULL
	 */
	function get_ids_for_overlay($overlay_id) {
		global $context;

		// display active items
		$active = "(articles.active='Y'";

		// add restricted items to members, or if teasers are allowed
		if(Surfer::is_logged() || !isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			$active .= " OR articles.active='R'";

		// include hidden sections for associates
		if(Surfer::is_associate())
			$active .= " OR articles.active='N'";

		// end of active filter
		$active .= ")";

// 		// use only live sections
// 		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
// 		$criteria[] = "((sections.activation_date is NULL)"
// 			." OR (sections.activation_date <= '".$now."'))"
// 			." AND ((sections.expiry_date is NULL)"
// 			." OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$now."'))";

		// list up to 200 sections
		$query = "SELECT articles.id FROM ".SQL::table_name('articles')." AS articles"
			." WHERE overlay_id LIKE '".SQl::escape($overlay_id)."' AND ".$active
			." LIMIT 200";
		if(!$result =& SQL::query($query)) {
			$output = NULL;
			return $output;
		}

		// process all matching records
		$ids = array();
		while($item =& SQL::fetch($result))
			$ids[] = $item['id'];

		// return a list of ids
		return $ids;
	}

	/**
	 * get the newest article for one anchor
	 *
	 * This function is to be used while listing articles for one anchor.
	 * It provides the last edited article for this anchor.
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but surfer is a logged user
	 * - article is protected (active='N'), but surfer is an associate
	 * - article has been officially published
	 * - an expiry date has not been defined, or is not yet passed
	 * - (if 2nd parameter is TRUE) article is not sticky (rank >= 10000)
	 *
	 * @param int the id of the anchor
	 * @param boolean FALSE to include sticky pages, TRUE otherwise
	 * @return the resulting $item array, with at least keys: 'id', 'title', 'description', etc.
	 *
	 * @see index.php
	 */
	function &get_newest_for_anchor($anchor, $without_sticky=FALSE) {
		global $context;

		// select among active and restricted items
		$where = "articles.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR articles.active='R'";
		if(Surfer::is_associate())
			$where .= " OR articles.active='N'";
		$where = "(".$where.")";

		// just get the newest page
		if($anchor)
			$where = "(articles.anchor LIKE '".SQL::escape($anchor)."') AND ".$where;

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// always only consider published articles
		$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
			." AND (articles.publish_date < '".$now."')";

		// only consider live articles
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$now."'))";

		// avoid sticky articles
		if($without_sticky)
			$where .= " AND (articles.rank >= 10000)";

		// the list of articles
		$query = "SELECT * FROM ".SQL::table_name('articles')." AS articles"
			." WHERE ".$where
			." ORDER BY articles.rank, articles.edit_date DESC, articles.title LIMIT 0,1";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get url of next article
	 *
	 * This function is used to build navigation bars.
	 *
	 * @param array the current item
	 * @param string reference to the current anchor (e.g., 'section:123')
	 * @param string the order, either 'date' or 'title' or 'rank'
	 * @return an array ($url, $title)
	 *
	 * @see sections/section.php
	 */
	function get_next_url(&$item, $anchor, $order='date') {
		global $context;

		// sanity check
		if(!is_array($item))
			return $item;

		// limit the scope of the request
		$where = "articles.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR articles.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR articles.active='N'";
		$where = '('.$where.')';

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// always only consider published articles, except for associates and editors
		if(!Surfer::is_empowered())
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$now."')";

		// only consider live articles
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$now."'))";

		// depending on selected sequence
		if($order == 'edition') {
			$match = "articles.rank >= ".SQL::escape($item['rank'])." AND articles.edit_date < '".SQL::escape($item['edit_date'])."'";
			$order = 'articles.rank, articles.edit_date DESC, articles.title';
		} elseif($order == 'publication') {
			$match = "articles.rank >= ".SQL::escape($item['rank'])." AND articles.publish_date < '".SQL::escape($item['publish_date'])."'";
			$order = 'articles.rank, articles.publish_date DESC, articles.title';
		} elseif($order == 'rating') {
			$match = "articles.rank >= ".SQL::escape($item['rank'])." AND articles.rating_sum < ".SQL::escape($item['rating_sum']);
			$order = 'articles.rank, articles.rating_sum DESC, articles.publish_date DESC';
		} elseif($order == 'title') {
			$match = "articles.title > '".SQL::escape($item['title'])."'";
			$order = 'articles.title';
		} else
			return "unknown order '".$order."'";

		// query the database
		$query = "SELECT id, title, nick_name FROM ".SQL::table_name('articles')." AS articles "
			." WHERE (articles.anchor LIKE '".SQL::escape($anchor)."') AND (".$match.") AND (".$where.")"
			." ORDER BY ".$order." LIMIT 0, 1";
		if(!$result =& SQL::query($query))
			return NULL;

		// no result
		if(!SQL::count($result))
			return NULL;

		// return url of the first item of the list
		$item =& SQL::fetch($result);
		return array(Articles::get_permalink($item), $next['title']);
	}

	/**
	 * get permanent address
	 *
	 * @param array page attributes
	 * @return string the permalink
	 */
	function &get_permalink($item) {
		$output =& Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']);
		return $output;
	}

	/**
	 * get url of previous article
	 *
	 * This function is used to build navigation bars.
	 *
	 * @param array the current item
	 * @param string reference to the anchor (e.g., 'section:123')
	 * @param string the order, either 'date' or 'title'
	 * @return an array($url, $title)
	 *
	 * @see sections/section.php
	 */
	function get_previous_url(&$item, $anchor, $order='date') {
		global $context;

		// sanity check
		if(!is_array($item))
			return $item;

		// limit the scope of the request
		$where = "articles.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR articles.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR articles.active='N'";
		$where = '('.$where.')';

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// always only consider published articles, except for associates and editors
		if(!Surfer::is_empowered())
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$now."')";

		// only consider live articles
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$now."'))";

		// depending on selected sequence
		if($order == 'edition') {
			$match = "articles.rank <= ".SQL::escape($item['rank'])." AND articles.edit_date > '".SQL::escape($item['edit_date'])."'";
			$order = 'articles.rank DESC, articles.edit_date, articles.title';
		} elseif($order == 'publication') {
			$match = "articles.rank <= ".SQL::escape($item['rank'])." AND articles.publish_date > '".SQL::escape($item['publish_date'])."'";
			$order = 'articles.rank DESC, articles.publish_date, articles.title';
		} elseif($order == 'rating') {
			$match = "articles.rank <= ".SQL::escape($item['rank'])." AND articles.rating_sum > ".SQL::escape($item['rating_sum']);
			$order = 'articles.rank DESC, articles.rating_sum, articles.publish_date';
		} elseif($order == 'title') {
			$match = "articles.title < '".SQL::escape($item['title'])."'";
			$order = 'articles.title DESC';
		} else
			return "unknown order '".$order."'";

		// query the database
		$query = "SELECT id, title, nick_name FROM ".SQL::table_name('articles')." AS articles "
			." WHERE (articles.anchor LIKE '".SQL::escape($anchor)."') AND (".$match.") AND (".$where.")"
			." ORDER BY ".$order." LIMIT 0, 1";
		if(!$result =& SQL::query($query))
			return NULL;

		// no result
		if(!SQL::count($result))
			return NULL;

		// return url of the first item of the list
		$item =& SQL::fetch($result);
		return array(Articles::get_permalink($item), $previous['title']);
	}

	/**
	 * build a reference to an article
	 *
	 * Depending on parameter '[code]with_friendly_urls[/code]' and on action,
	 * following results can be observed:
	 *
	 * - view - articles/view.php?id=123 or articles/view.php/123 or article-123
	 *
	 * - other - articles/edit.php?id=123 or articles/edit.php/123 or article-edit/123
	 *
	 * If a third parameter is provided, it may be used to achieve a nice link,
	 * such as the following:
	 * [php]
	 * Articles::get_url(123, 'view', 'A very nice page');
	 * [/php]
	 * will result to
	 * [snippet]
	 * http://server/article-123-a-very-nice-page
	 * [/snippet]
	 *
	 * If a fourth parameter is provided, it will take over the third one. This
	 * is used to leverage nick names in YACS, as per the following invocation:
	 * [php]
	 * Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']);
	 * [/php]
	 *
	 * @param int the id of the article to handle
	 * @param string the expected action ('view', 'print', 'edit', 'delete', ...)
	 * @param string additional data, such as page name, if any
	 * @param string alternate name, if any, to take over on previous parameter
	 * @return string a normalized reference
	 *
	 * @see control/configure.php
	 */
	function get_url($id, $action='view', $name=NULL, $alternate_name=NULL) {
		global $context;

		// use alternate name instead of regular name, if one is provided
		if($alternate_name)
			$name = str_replace('_', ' ', $alternate_name);

		// the service to check for updates
		if($action == 'check') {
			if($context['with_friendly_urls'] == 'Y')
				return 'services/check.php/article/'.rawurlencode($id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'services/check.php?id='.urlencode('article:'.$id);
			else
				return 'services/check.php?id='.urlencode('article:'.$id);
		}

		// the prefix for navigation links --name references the things to page
		if($action == 'navigate') {
			if($context['with_friendly_urls'] == 'Y')
				return 'articles/view.php/'.rawurlencode($id).'/'.rawurlencode($name).'/';
			elseif($context['with_friendly_urls'] == 'R')
				return 'articles/view.php/'.rawurlencode($id).'/'.rawurlencode($name).'/';
			else
				return 'articles/view.php?id='.urlencode($id).'&amp;'.urlencode($name).'=';
		}

		// check the target action
		if(!preg_match('/^(delete|describe|duplicate|edit|export|fetch_as_msword|fetch_as_pdf|fetch_for_palm|lock|mail|move|print|publish|rate|stamp|unpublish|view)$/', $action))
			$action = 'view';

		// normalize the link
		return normalize_url(array('articles', 'article'), $action, $id, $name);
	}

	/**
	 * set the hits counter - errors are not reported, if any
	 *
	 * @param the id of the article to update
	 */
	function increment_hits($id) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return;

		// do the job
		$query = "UPDATE ".SQL::table_name('articles')." SET hits=hits+1 WHERE id = ".SQL::escape($id);
		SQL::query($query);
	}

	/**
	 * has the surfer been assigned to this article?
	 *
	 * This would be the case either:
	 * - if he is a member and has been granted the editor privilege
	 * - if he is a subscriber and has been granted the reader privilege
	 *
	 * @param int the id of the target article
	 * @param int optional id to impersonate
	 * @return TRUE or FALSE
	 */
	function is_assigned($id, $surfer_id=NULL) {
		global $context;

		// no impersonation
		if(!$surfer_id) {

			// a managed article requires an authenticated user
			if(!Surfer::is_logged())
				return FALSE;

			// use surfer profile
			$surfer_id = Surfer::get_id();

		}

		// ensure this article has been linked to this user
		return Members::check('user:'.$surfer_id, 'article:'.$id);
	}

	/**
	 * list most recent articles
	 *
	 * Items order is provided by the layout.
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param mixed the list variant, if any
	 * @param string stamp of the minimum publication date to be considered
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_($offset=0, $count=10, $layout='full', $since=NULL) {
		global $context;

		// define items order
		if(is_callable(array($layout, 'items_order')))
			$order = $layout->items_order();
		if(!isset($order) || !$order)
			$order = 'publication';

		// ask for ordered articles
		$output =& Articles::list_by($order, $offset, $count, $layout, $since);
		return $output;
	}

	/**
	 * list articles assigned to one surfer
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - or article is restricted (active='R'), but surfer is a logged user
	 * - or article is hidden (active='N'), but surfer is an associate
	 *
	 * @param int surfer id
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_assigned_by_date($surfer_id, $offset=0, $count=20, $variant='full', $shared_page = FALSE) {
		global $context;

		// return by reference
		$output =& Articles::list_assigned_by_date_for_anchor(NULL, $surfer_id, $offset, $count, $variant, $shared_page);
		return $output;
	}

	/**
	 * list articles assigned to one surfer in one anchor
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - or article is restricted (active='R'), but surfer is a logged user
	 * - or article is hidden (active='N'), but surfer is an associate
	 *
	 * @param mixed, either a string the target anchor, or an array of anchors
	 * @param int surfer id
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see users/view.php
	 */
	function &list_assigned_by_date_for_anchor($anchor, $surfer_id, $offset=0, $count=20, $variant='full', $shared_page = FALSE) {
		global $context;

		// display active items
		$where = "(articles.active='Y'";

		// add restricted items to logged members, or if teasers are allowed
		if(Surfer::is_logged() || !isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			$where .= " OR articles.active='R'";

		// list hidden articles to associates, editors and to readers
		if($shared_page)
			$where .= " OR articles.active='N'";
		elseif(is_callable(array('Surfer', 'is_empowered')) && Surfer::is_empowered('S'))
			$where .= " OR articles.active='N'";
		elseif(Surfer::get_id() == $surfer_id)
			$where .= " OR articles.active='N'";

		// end of scope
		$where .= ")";

		// several anchors
		if(is_array($anchor)) {
			$items = array();
			foreach($anchor as $token)
				$items[] = "articles.anchor LIKE '".SQL::escape($token)."'";
			$where = '('.join(' OR ', $items).') AND '.$where;

		// or only one
		} elseif($anchor)
			$where = "(articles.anchor LIKE '".SQL::escape($anchor)."') AND ".$where;

		// limit to pages shared with this surfer
		if($shared_page)
			$query = "SELECT articles.*"
				." FROM (".SQL::table_name('articles')." AS articles"
				.", ".SQL::table_name('members')." AS members"
				.", ".SQL::table_name('members')." AS members2)"
				." WHERE ((members.anchor LIKE 'user:".SQL::escape($surfer_id)."')"
				."	AND (members.member_type LIKE 'article') AND (members.member_id = articles.id))"
				."	AND ((members2.anchor LIKE 'user:".SQL::escape(Surfer::get_id())."')"
				."	AND (members2.member_type LIKE 'article') AND (members2.member_id = articles.id))"
				."	AND ".$where
				." GROUP BY articles.id"
				." ORDER BY articles.edit_date DESC LIMIT ".$offset.','.$count;

		// else enlarge the scope
		else
			$query = "SELECT articles.* FROM ".SQL::table_name('articles')." AS articles"
				.", ".SQL::table_name('members')." AS members"
				." WHERE ((members.anchor LIKE 'user:".SQL::escape($surfer_id)."')"
				."	AND (members.member_type LIKE 'article') AND (members.member_id = articles.id))"
				."	AND ".$where
				." ORDER BY articles.edit_date DESC LIMIT ".$offset.','.$count;

		$output =& Articles::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list articles
	 *
	 * The ordering method is provided as first parameter:
	 * - 'draft' - order by reverse date of modification, but only draft pages
	 * - 'edition' - order by reverse date of modification
	 * - 'expiry' - order by reverse expiry date, and consider only expired articles
	 * - 'future' - order by reverse date of publication, and consider only future publication dates
	 * - 'hits' - order by reverse count of hits
	 * - 'publication' - order by reverse date of publication
	 * - 'random' - use random order
	 * - 'rating' - order by reverse number of points
	 * - 'review' - order by MAX(date of last modification, date of last review)
	 * - 'unread' - order by count of hits
	 *
	 * @param string order of resulting set
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param mixed the list variant, if any
	 * @param string stamp of the minimum publication date to be considered
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_by($order=NULL, $offset=0, $count=10, $layout='full', $since=NULL) {
		global $context;

		// select among active items
		$where = "(articles.active='Y'";

		// add restricted items to members, or if teasers are allowed
		if(Surfer::is_logged() || !isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			$where .= " OR articles.active='R'";

		// associates can access hidden articles
		if(Surfer::is_associate() && !( is_string($layout) && (($layout == 'feeds') || ($layout == 'contents')) ) )
			$where .= " OR articles.active='N'";

		// include articles from managed sections
		if(count($my_sections = Surfer::assigned_sections()))
			$where .= " OR articles.anchor='section:".join("' OR articles.anchor='section", $my_sections)."'";

		$where .= ")";

		// bracket OR statements
		$where = '('.$where.')';

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// list only draft articles
		if($order == 'draft')
			$where .= " AND ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))";

		// list only articles published in the future
		elseif($order == 'future')
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date > '".$now."')";

		// list only published articles, if not associate or if looking for less popular
		elseif(!Surfer::is_associate() || ($order != 'unread'))
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$now."')";

		// list articles published after some date
		if($since && ($since > NULL_DATE))
			$where .= " AND (articles.publish_date > '".$since."')";

		// consider only dead articles
		if($order == 'expiry')
			$where .= " AND ((articles.expiry_date > '".NULL_DATE."') AND (articles.expiry_date <= '".$now."'))";

		// else consider live articles
		else
			$where .= " AND ((articles.expiry_date is NULL) "
					."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$now."'))";

		// avoid articles pushed away from the front page
		$sections_where = '';
		if(isset($context['skin_variant']) && ($context['skin_variant'] == 'home')) {
			$sections_where .= " AND (sections.home_panel LIKE 'main')"
				." AND (sections.index_map LIKE 'Y')"
				." AND (articles.home_panel LIKE 'main')";
		}

		// order of the resulting set
		$more_fields = '';
		switch($order) {
		case 'draft':
		case 'edition':
			$order = 'articles.edit_date DESC, articles.title';
			break;
		case 'expiry':
			$order = 'articles.expiry_date DESC, articles.edit_date DESC, articles.title';
			break;
		case 'future':
		case 'publication':
		default:
			$order = 'articles.publish_date DESC, articles.title';
			break;
		case 'hits':
			$order = 'articles.hits DESC, articles.title';
			break;
		case 'random':
			$order = 'RAND()';
			break;
		case 'rating':
			$order = 'rating_sum DESC, articles.publish_date DESC, articles.title';
			break;
		case 'review':
			$more_fields = ', GREATEST(articles.edit_date, articles.review_date) AS stamp';
			$order = 'stamp, articles.title';
			break;
		case 'unread':
			$order = 'articles.hits, articles.edit_date DESC, articles.title';
			break;
		}

		// reference sections
		if($sections_where)
			$query = "SELECT articles.*".$more_fields
				." FROM (".SQL::table_name('articles')." AS articles"
				.", ".SQL::table_name('sections')." AS sections)"
				." WHERE ((articles.anchor_type LIKE 'section') AND (articles.anchor_id = sections.id)) AND ".$where.$sections_where
				." GROUP BY articles.id"
				." ORDER BY ".$order." LIMIT ".$offset.','.$count;

		// only select articles
		else
			$query = "SELECT articles.*".$more_fields
				." FROM ".SQL::table_name('articles')." AS articles"
				." WHERE ".$where
				." ORDER BY ".$order." LIMIT ".$offset.','.$count;

		// actual request to the database
		$output =& Articles::list_selected(SQL::query($query), $layout);
		return $output;
	}

	/**
	 * list most recent articles
	 *
	 * Actually list articles by publishing date, then by title.
	 *
	 * To build a simple box of the newest articles in your main index page, just use:
	 * [php]
	 * $items = Articles::list_by_date(0, 10);
	 * $context['text'] .= Skin::build_list($items, 'decorated');
	 * [/php]
	 *
	 * You can also display the newest article separately, using [code]Articles::get_newest()[/code]
	 * In this case, skip the very first article in the list by using
	 * [code]Articles::list_by_date(1, 10)[/code]
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - article is protected (active='N'), but surfer is an associate, and we are not feeding someone
	 * - article has been officially published
	 * - an expiry date has not been defined, or is not yet passed
	 * - related section is regularly displayed at the front page
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param mixed the layout, if any
	 * @param string stamp of the minimum publication date to be considered
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see index.php
	 * @see actions/index.php
	 * @see articles/index.php
	 * @see comments/index.php
	 * @see dates/day.php
	 * @see dates/index.php
	 * @see dates/month.php
	 * @see dates/year.php
	 * @see decisions/index.php
	 * @see feeds/feeds.php
	 * @see images/index.php
	 * @see letters/new.php
	 * @see locations/index.php
	 * @see shared/codes.php
	 * @see tables/index.php
	 */
	function &list_by_date($offset=0, $count=10, $layout='full', $since=NULL) {
		global $context;

		// order by reverse date of publication
		$result =& Articles::list_by('publication', $offset, $count, $layout, $since);
		return $result;
	}

	/**
	 * list most recent publications for one anchor
	 *
	 * Actually list articles by rank, then by publishing date, then by title.
	 * If you select to not use the ranking system, articles will be ordered by date only.
	 * Else articles with a low ranking mark will appear first,
	 * and articles with a high ranking mark will be put at the end of the list.
	 *
	 * Example:
	 * [php]
	 * $items = Articles::list_by_date_for_anchor('section:12');
	 * $context['text'] .= Skin::build_list($items, 'decorated');
	 * [/php]
	 *
	 * Use the variant 'boxes' to fetch articles within boxes. This is the standard way
	 * of displaying navigation, gadgets and extra boxes in yacs.
	 *
	 * [php]
	 * // load the navigation boxes, if any
	 * $anchor = Sections::lookup('navigation_boxes');
	 * if($items = Articles::list_by_date_for_anchor($anchor, 0, 5, 'boxes')) {
	 *	 foreach($items as $title => $content)
	 *	   echo "<p></p>\n".Skin::build_box($title, $content, 'navigation')."\n";
	 * }
	 * [/php]
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - article is protected (active='N'), but surfer is an associate, and we are not feeding someone
	 * - surfer is anonymous, and article has been officially published
	 * - logged surfers are restricted to their own articles, plus published articles
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param mixed, either a string the target anchor, or an array of anchors
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param mixed the layout, if any
	 * @param boolean FALSE to include sticky pages, TRUE otherwise
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see articles/review.php
	 * @see index.php
	 * @see letters/index.php
	 * @see sections/view.php
	 * @see shared/codes.php
	 * @see skins/page.php
	 */
	function &list_by_date_for_anchor($anchor, $offset=0, $count=10, $layout='no_anchor', $without_sticky=FALSE) {
		global $context;

		// order by rank, then by reverse date of publication
		$result =& Articles::list_for_anchor_by('publication', $anchor, $offset, $count, $layout, $without_sticky);
		return $result;
	}

	/**
	 * list most recent articles for one author
	 *
	 * Articles are sorted by edition dates, to let updated articles appear at the top of lists
	 * in user pages.
	 *
	 * Example:
	 * [php]
	 * $items = Articles::list_by_date_for_author(12, 0, 10);
	 * $context['text'] .= Skin::build_list($items, 'decorated');
	 * [/php]
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but surfer is a logged user
	 * - article is not visible (active='N'), but surfer is an associate
	 * - article has been officially published, or the surfer is a logged user
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param int the id of the author of the article
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param mixed the layout, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see users/view.php
	 */
	function &list_by_date_for_author($author_id, $offset=0, $count=10, $layout='no_author') {
		global $context;

		// sanity check
		if(!$author_id)
			return NULL;
		$author_id = SQL::escape($author_id);

		// select among active and restricted items
		$where = "articles.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR articles.active='R'";

		// show hidden articles to author and to associates only
		if(is_string($layout) && ($layout == 'feeds'))
			;
		elseif(is_string($layout) && ($layout == 'contents'))
			;
		elseif(Surfer::is_associate() || (Surfer::get_id() && (Surfer::get_id() == $author_id)))
			$where .= " OR articles.active='N'";

		$where = '('.$where.')';

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// list only articles contributed by this author
		$where .= " AND (articles.create_id LIKE '$author_id')";

		// only original author and associates will see draft articles
		if(!Surfer::is_member() || (!Surfer::is_associate() && (Surfer::get_id() != $author_id)))
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$now."')";

		// only consider live articles
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$now."'))";

		// the list of articles
		$query = "SELECT articles.*"
			." FROM ".SQL::table_name('articles')." AS articles"
			." WHERE (".$where.")"
			." ORDER BY articles.edit_date DESC, articles.title LIMIT ".$offset.','.$count;

		$output =& Articles::list_selected(SQL::query($query), $layout);
		return $output;
	}

	/**
	 * list most recent updates for one anchor
	 *
	 * Actually list articles by rank, then by edition date, then by title.
	 * If you select to not use the ranking system, articles will be ordered by date only.
	 * Else articles with a low ranking mark will appear first,
	 * and articles with a high ranking mark will be put at the end of the list.
	 *
	 * Using edition dates enables updated articles to appear at the top of list in sections.
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - article is protected (active='N'), but surfer is an associate, and we are not feeding someone
	 * - surfer is anonymous, and article has been officially published
	 * - logged surfers are restricted to their own articles, plus published articles
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param mixed, either a string the target anchor, or an array of anchors
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param mixed the layout, if any
	 * @param boolean FALSE to include sticky pages, TRUE otherwise
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see index.php
	 * @see sections/feed.php
	 * @see sections/layout_sections.php
	 * @see sections/layout_sections_as_folded.php
	 * @see sections/layout_sections_as_inline.php
	 * @see sections/layout_sections_as_jive.php
	 * @see sections/layout_sections_as_yahoo.php
	 * @see sections/print.php
	 * @see sections/slideshow.php
	 * @see sections/view.php
	 * @see services/blog.php
	 * @see shared/codes.php
	 */
	function &list_by_edition_date_for_anchor($anchor, $offset=0, $count=10, $layout='no_anchor', $without_sticky=FALSE) {
		global $context;

		// order by rank, then by reverse date of modification
		$result =& Articles::list_for_anchor_by('edition', $anchor, $offset, $count, $layout, $without_sticky);
		return $result;

	}

	/**
	 * list most read articles
	 *
	 * To build a simple box of the most read articles in your main index page, just use:
	 * [php]
	 * $items = Articles::list_by_hits(0, COMPACT_LIST_SIZE);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 * [/php]
	 *
	 * You can also display the most read article separately, using Articles::get_most_read()
	 * In this case, skip the very first article in the list by using
	 * Articles::list_by_hits(1, 10)
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but surfer is a logged user
	 * - article is not visible (active='N'), but surfer is an associate
	 * - article has been officially published
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param mixed the layout, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see index.php
	 * @see articles/index.php
	 * @see shared/codes.php
	 */
	function &list_by_hits($offset=0, $count=10, $layout='hits') {
		global $context;

		// order by reverse count of hits
		$result =& Articles::list_by('hits', $offset, $count, $layout);
		return $result;
	}

	/**
	 * list most read articles for one anchor
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - article is protected (active='N'), but surfer is an associate, and we are not feeding someone
	 * - surfer is anonymous, and article has been officially published
	 * - logged surfers are restricted to their own articles, plus published articles
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param mixed, either a string the target anchor, or an array of anchors
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param mixed the layout, if any
	 * @param boolean FALSE to include sticky pages, TRUE otherwise
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see shared/codes.php
	 */
	function &list_by_hits_for_anchor($anchor, $offset=0, $count=10, $layout='no_anchor', $without_sticky=FALSE) {
		global $context;

		// order by reverse number of hits, then by reverse date of publication
		$result =& Articles::list_for_anchor_by('hits', $anchor, $offset, $count, $layout, $without_sticky);
		return $result;
	}

	/**
	 * list most read articles for one author
	 *
	 * To build a simple box of the most read articles on the author page, just use:
	 * [php]
	 * $items = Articles::list_by_hits_for_author(12, 0, 10);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 * [/php]
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but surfer is a logged user
	 * - article is not visible (active='N'), but surfer is an associate
	 * - article has been officially published, or the surfer is a logged user
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param int the id of the author of the article
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param mixed the layout, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see users/view.php
	 */
	function &list_by_hits_for_author($author_id, $offset=0, $count=10, $layout='no_author') {
		global $context;

		// select among active and restricted items
		$where = "articles.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR articles.active='R'";

		// show hidden articles to author and to associates only
		if(is_string($layout) && ($layout == 'feeds'))
			;
		elseif(is_string($layout) && ($layout == 'contents'))
			;
		elseif(Surfer::is_associate() || (Surfer::get_id() && (Surfer::get_id() == $author_id)))
			$where .= " OR articles.active='N'";

		$where = '('.$where.')';

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// anonymous surfers and subscribers will see only published articles
		if(!Surfer::is_member())
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$now."')";

		// list only articles contributed by this author
		$where .= " AND (articles.create_id='".SQL::escape($author_id)."')";

		// only consider live articles
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$now."'))";

		// the list of articles
		$query = "SELECT articles.* FROM ".SQL::table_name('articles')." AS articles"
			." WHERE (".$where.")"
			." ORDER BY articles.hits DESC, articles.title LIMIT ".$offset.','.$count;

		$output =& Articles::list_selected(SQL::query($query), $layout);
		return $output;
	}

	/**
	 * list these articles
	 *
	 * The first parameter can be either a string containing several ids or nick
	 * names separated by commas, or it can be an array of ids or nick names.
	 *
	 * The second parameter can be either a string accepted by Articles::list_selected(),
	 * or an instance of the Layout interface.
	 *
	 * @param mixed a list of ids or nick names
	 * @param mixed the layout to apply
	 * @return string to be inserted into the resulting page
	 */
	function &list_by_title_for_ids($ids, $layout='select') {
		global $context;

		// turn a string to an array
		if(!is_array($ids))
			$ids = preg_split('/,\s*/', (string)$ids);

		// check every id
		$items = array();
		foreach($ids as $id) {

			// we need some id
			if(!$id)
				continue;

			// look by id or by nick name
			if(is_numeric($id))
				$items[] = "articles.id = ".SQL::escape($id);
			else
				$items[] = "articles.nick_name LIKE '".SQL::escape($id)."'";

		}

		// no valid id has been found
		if(!count($items)) {
			$output = NULL;
			return $output;
		}

		// the list of articles
		$query = "SELECT articles.*"
			." FROM ".SQL::table_name('articles')." AS articles"
			." WHERE (".join(' OR ', $items).")"
			." ORDER BY articles.title";

		// query and layout
		$output =& Articles::list_selected(SQL::query($query), $layout);
		return $output;
	}

	/**
	 * list articles attached to one anchor
	 *
	 * The ordering method is provided by layout.
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - article is protected (active='N'), but surfer is an associate, and we are not feeding someone
	 * - surfer is anonymous, and article has been officially published
	 * - logged surfers are restricted to their own articles, plus published articles
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param mixed, either a string the target anchor, or an array of anchors
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @param boolean FALSE to include sticky pages, TRUE otherwise
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_for_anchor($anchor, $offset=0, $count=10, $layout='no_anchor', $without_sticky=FALSE) {
		global $context;

		// define items order
		if(is_callable(array($layout, 'items_order')))
			$order = $layout->items_order();
		if(!isset($order) || !$order)
			$order = 'edition';

		// ask for ordered items
		$output =& Articles::list_for_anchor_by($order, $anchor, $offset, $count, $layout, $without_sticky);
		return $output;
	}

	/**
	 * list articles attached to one anchor
	 *
	 * The ordering method is provided as first parameter:
	 * - 'draft' - order by reverse date of modification, but only draft pages
	 * - 'edition' - order by rank, then by reverse date of modification
	 * - 'hits' - order by reverse number of hits, then by reverse date of publication
	 * - 'overlay' - order by overlay_id
	 * - 'publication' - order by rank, then by reverse date of publication
	 * - 'rating' - order by rank, then by reverse number of points
	 * - 'title' - order by rank, then by titles
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - article is protected (active='N'), but surfer is an associate, and we are not feeding someone
	 * - surfer is anonymous, and article has been officially published
	 * - logged surfers are restricted to their own articles, plus published articles
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param string order of resulting set
	 * @param mixed, either a string the target anchor, or an array of anchors
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param mixed the layout, if any
	 * @param boolean FALSE to include sticky pages, TRUE otherwise
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_for_anchor_by($order, $anchor, $offset=0, $count=10, $layout='no_anchor', $without_sticky=FALSE) {
		global $context;

		// select among active items
		$where = "articles.active='Y'";

		// add restricted items to members, or if teasers are allowed
		if(Surfer::is_logged()
			|| !isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			$where .= " OR articles.active='R'";

		// associates, editors and readers may see everything
		if(Surfer::is_empowered('S'))
			$where .= " OR articles.active='N'";

		// a dynamic where clause
		$where = '('.$where.')';

		// avoid sticky articles
		if($without_sticky)
			$where .= " AND (articles.rank >= 10000)";

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// list only draft articles
		if($order == 'draft')
			$where .= " AND ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))";

		// provide published pages to anonymous surfers
		elseif(!Surfer::is_logged()) {
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$now."')";

		// logged surfers that are non-associates are restricted to their own articles, plus published articles
		} elseif(!is_callable(array('Surfer', 'is_empowered')) || !Surfer::is_empowered()) {
			$where .= " AND ((articles.create_id='".Surfer::get_id()."') OR (NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$now."')))";
		}

		// only consider live articles, except for associates and editors
		if(is_callable(array('Surfer', 'is_empowered')) && !Surfer::is_empowered()) {
			$where .= " AND ((articles.expiry_date is NULL) "
					."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$now."'))";
		}

		// several anchors
		if(is_array($anchor)) {
			$items = array();
			foreach($anchor as $token)
				$items[] = "articles.anchor LIKE '".SQL::escape($token)."'";
			$where_anchor = join(' OR ', $items);

		// or only one
		} else
			$where_anchor = "articles.anchor LIKE '".SQL::escape($anchor)."'";

		// order items
		switch($order) {
		case 'draft':
			$order = 'articles.edit_date DESC, articles.title';
			break;

		case 'edition': // order by rank, then by reverse date of modification

			// avoid side effects of ranking across several sections
			if(is_array($anchor) && (count($anchor) > 1))
				$order = 'articles.edit_date DESC, articles.title';
			else
				$order = 'articles.rank, articles.edit_date DESC, articles.title';
			break;

		case 'hits':	// order by reverse number of hits, then by reverse date of publication

			$order = 'hits DESC, articles.publish_date DESC';
			break;

		case 'overlay': // order by overlay_id, then by number of points

			// avoid side effects of ranking across several sections
			if(is_array($anchor) && (count($anchor) > 1))
				$order = 'articles.overlay_id, rating_sum DESC, articles.publish_date DESC';
			else
				$order = 'articles.overlay_id, articles.rank, rating_sum DESC, articles.publish_date DESC';
			break;

		case 'publication': // order by rank, then by reverse date of publication
		default:

			// avoid side effects of ranking across several sections
			if(is_array($anchor) && (count($anchor) > 1))
				$order = 'articles.publish_date DESC, articles.title';
			else
				$order = 'articles.rank, articles.publish_date DESC, articles.title';
			break;

		case 'rating':	// order by rank, then by number of points

			// avoid side effects of ranking across several sections
			if(is_array($anchor) && (count($anchor) > 1))
				$order = 'rating_sum DESC, articles.publish_date DESC';
			else
				$order = 'articles.rank, rating_sum DESC, articles.publish_date DESC';
			break;

		case 'reverse_rank':	// order by rank, then by date of publication

			$order = 'articles.rank DESC, articles.publish_date DESC';
			break;

		case 'title':	// order by rank, then by title

			// avoid side effects of ranking across several sections
			if(is_array($anchor) && (count($anchor) > 1))
				$order = 'articles.title';
			else
				$order = 'articles.rank, articles.title';
			break;

		}

		// the list of articles
		$query = "SELECT articles.*"
			." FROM ".SQL::table_name('articles')." AS articles"
			." WHERE (".$where_anchor.") AND (".$where.")"
			." ORDER BY ".$order." LIMIT ".$offset.','.$count;

		$output =& Articles::list_selected(SQL::query($query), $layout);
		return $output;
	}

	/**
	 * list named articles
	 *
	 * This function lists all articles with the same nick name.
	 *
	 * This is used by the page locator to offer alternatives when several pages have the same nick names.
	 * It is also used to link a page to twins, these being, most of the time, translations.
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - article is protected (active='N'), but surfer is an associate or an editor
	 * - article has been officially published, or surfer is an associate or an editor
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param string the nick name
	 * @param int the id of the current page, which will not be listed
	 * @param mixed the layout, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_for_name($name, $exception=NULL, $layout='compact') {
		global $context;

		// select among active items
		$where = "articles.active='Y'";

		// add restricted items to members, or if teasers are allowed
		if(Surfer::is_logged() || !isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			$where .= " OR articles.active='R'";

		// add hidden items to associates, editors and readers
		if(Surfer::is_empowered('S'))
			$where .= " OR articles.active='N'";

		// bracket OR statements
		$where = '('.$where.')';

		// avoid exception, if any
		if($exception)
			$where .= " AND (articles.id != ".SQL::escape($exception).")";

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// list draft pages only to associates and editors
		if(!Surfer::is_empowered())
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$now."')";

		// only consider live articles
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$now."'))";

		// articles by title -- no more than 100 pages with the same name
		$query = "SELECT articles.*"
			." FROM ".SQL::table_name('articles')." AS articles"
			." WHERE (articles.nick_name LIKE '".SQL::escape($name)."') AND ".$where
			." ORDER BY articles.title LIMIT 100";

		$output =& Articles::list_selected(SQL::query($query), $layout);
		return $output;
	}

	/**
	 * list selected articles
	 *
	 * Accept following layouts:
	 * - 'boxes' - a special variant to build boxes on index pages
	 * - 'compact' - to build short lists in boxes and sidebars (this is the default)
	 * - 'contents' - an array of $url => array($time, $label, $author, $section, $icon, $introduction, $content, $trackback) for feeds and search
	 * - 'daily' - for blogs
	 * - 'digest' - for newsletters
	 * - 'feeds' - an array of $url => array($time, $label, $author, $section, $icon, $introduction, $content, $trackback) for feeds and search
	 * - 'freemind' - to create Freemind maps
	 * - 'news' - to build a list of news or of featured pages
	 * - 'raw'
	 * - 'review'
	 * - 'rpc'
	 * - 'select' - to select an article among several templates
	 * - 'simple' - to build line-based lists
	 * - 'thumbnails'
	 *
	 * Options can be provided to the selected layout by adding them after a space
	 * character. For example: 'simple no_anchor' when listing private conversations.
	 *
	 * @param resource result of database query
	 * @param mixed string e.g., 'full', or an instance of Layout_Interface
	 * @return NULL on error, else the outcome of the selected layout
	 *
	 * @see services/rss_codec.php
	 * @see skins/skin_skeleton.php
	 * @see index.php
	 */
	function &list_selected(&$result, $variant='compact') {
		global $context;

		// no result
		if(!$result) {
			$output = NULL;
			return $output;
		}

		// use the provided layout interface
		if(is_object($variant)) {
			$output =& $variant->layout($result);
			return $output;
		}

		// separate options from layout name
		$attributes = explode(' ', $variant, 2);

		// select a suitable layout
		switch($attributes[0]) {

		case 'boxes':
			include_once $context['path_to_root'].'articles/layout_articles_as_boxes.php';
			$layout =& new Layout_articles_as_boxes();
			break;

		case 'compact':
			include_once $context['path_to_root'].'articles/layout_articles_as_compact.php';
			$layout =& new Layout_articles_as_compact();
			break;

		case 'contents':
			include_once $context['path_to_root'].'articles/layout_articles_as_contents.php';
			$layout =& new Layout_articles_as_contents();
			break;

		case 'daily':
			include_once $context['path_to_root'].'articles/layout_articles_as_daily.php';
			$layout =& new Layout_articles_as_daily();
			break;

		case 'digest':
			include_once $context['path_to_root'].'articles/layout_articles_as_digest.php';
			$layout =& new Layout_articles_as_digest();
			break;

		case 'feeds':
			include_once $context['path_to_root'].'articles/layout_articles_as_feed.php';
			$layout =& new Layout_articles_as_feed();
			break;

		case 'freemind':
			include_once $context['path_to_root'].'articles/layout_articles_as_freemind.php';
			$layout =& new Layout_articles_as_freemind();
			break;

		case 'news':
			include_once $context['path_to_root'].'articles/layout_articles_as_news.php';
			$layout =& new Layout_articles_as_news();
			break;

		case 'raw':
			include_once $context['path_to_root'].'articles/layout_articles_as_raw.php';
			$layout =& new Layout_articles_as_raw();
			break;

		case 'review':
			include_once $context['path_to_root'].'articles/layout_articles_as_review.php';
			$layout =& new Layout_articles_as_review();
			break;

		case 'rpc':
			include_once $context['path_to_root'].'articles/layout_articles_as_rpc.php';
			$layout =& new Layout_articles_as_rpc();
			break;

		case 'select':
			include_once $context['path_to_root'].'articles/layout_articles_as_select.php';
			$layout =& new Layout_articles_as_select();
			break;

		case 'simple':
			include_once $context['path_to_root'].'articles/layout_articles_as_simple.php';
			$layout =& new Layout_articles_as_simple();
			break;

		case 'thread':
			include_once $context['path_to_root'].'articles/layout_articles_as_thread.php';
			$layout =& new Layout_articles_as_thread();
			break;

		case 'thumbnails':
			include_once $context['path_to_root'].'articles/layout_articles_as_thumbnails.php';
			$layout =& new Layout_articles_as_thumbnails();
			break;

		default:

			// allow for overload in skin
			if(is_callable(array('skin', 'layout_article'))) {

				// build an array of links
				$items = array();
				while($item =& SQL::fetch($result)) {

					// url to read the full article
					$url =& Articles::get_permalink($item);

					// reset the rendering engine between items
					if(is_callable(array('Codes', 'initialize')))
						Codes::initialize($url);

					// format the resulting string depending on layout
					$items[$url] = Skin::layout_article($item, $variant);

				}

				// end of processing
				SQL::free($result);
				return $items;

			// else use an external layout
			} else {
				include_once $context['path_to_root'].'articles/layout_articles.php';
				$layout =& new Layout_articles();
				$layout->set_variant($variant);
			}

		}

		// set variant, if any
		if(isset($attributes[1]))
			$layout->set_variant($attributes[1]);

		// do the job
		$output =& $layout->layout($result);
		return $output;
	}

	/**
	 * lock/unlock an article
	 *
	 * @param int the id of the article to update
	 * @param string the previous locking state
	 * @return TRUE on success toggle, FALSE otherwise
	 */
	function lock($id, $status='Y') {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return FALSE;

		// toggle status
		if($status == 'Y')
			$status = 'N';
		else
			$status = 'Y';

		// do the job
		$query = "UPDATE ".SQL::table_name('articles')." SET locked='".SQL::escape($status)."' WHERE id = ".SQL::escape($id);
		if(SQL::query($query) === FALSE)
			return FALSE;
		return TRUE;
	}

	/**
	 * get the id of one article knowing its nick name
	 *
	 * @param string the nick name looked for
	 * @return string either 'article:&lt;id&gt;', or NULL
	 */
	function lookup($nick_name) {
		if($item =& Articles::get($nick_name))
			return 'article:'.$item['id'];
		return NULL;
	}

	/**
	 * post a new article
	 *
	 * This function populates the error context, where applicable.
	 *
	 * @param array an array of fields
	 * @return the id of the new article, or FALSE on error
	 *
	 * @see articles/edit.php
	**/
	function post(&$fields) {
		global $context;

		// title cannot be empty
		if(!isset($fields['title']) || !$fields['title']) {
			Skin::error(i18n::s('No title has been provided.'));
			return FALSE;
		}

		// anchor cannot be empty
		if(!isset($fields['anchor']) || !$fields['anchor'] || (!$anchor = Anchors::get($fields['anchor']))) {
			Skin::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// protect from hackers
		if(isset($fields['icon_url']))
			$fields['icon_url'] =& encode_link($fields['icon_url']);
		if(isset($fields['thumbnail_url']))
			$fields['thumbnail_url'] =& encode_link($fields['thumbnail_url']);

		// set default values for this editor
		$fields = Surfer::check_default_editor($fields);

		// reinforce date formats
		if(!isset($fields['create_date']) || ($fields['create_date'] <= NULL_DATE))
			$fields['create_date'] = $fields['edit_date'];
		if(!isset($fields['publish_date']) || ($fields['publish_date'] <= NULL_DATE))
			$fields['publish_date'] = NULL_DATE;

		// set conservative default values
		if(!isset($fields['active_set']))
			$fields['active_set'] = 'Y';
		if(isset($fields['edit_action']) && $fields['edit_action'])
			$fields['edit_action'] = preg_replace('/import$/i', 'update', $fields['edit_action']);
		if(!isset($fields['rank']))
			$fields['rank'] = 10000;
		if(!isset($fields['nick_name']))
			$fields['nick_name'] = '';

		// clean provided tags
		if(isset($fields['tags']))
			$fields['tags'] = trim($fields['tags'], " \t.:,!?");

		// cascade anchor access rights
		$fields['active'] = $anchor->ceil_rights($fields['active_set']);

		// all row updates
		$query = array();

		// on import
		if(isset($fields['id']))
			$query[] = "id='".SQL::escape($fields['id'])."'";

		// fields that are visible only to associates -- see articles/edit.php
		if(Surfer::is_associate()) {
			$query[] = "prefix='".SQL::escape(isset($fields['prefix']) ? $fields['prefix'] : '')."'";
			$query[] = "suffix='".SQL::escape(isset($fields['suffix']) ? $fields['suffix'] : '')."'";
		}

		// fields that are visible only to associates and to authenticated editors -- see articles/edit.php
		if(Surfer::is_empowered() && Surfer::is_member()) {
			$query[] = "nick_name='".SQL::escape(isset($fields['nick_name']) ? $fields['nick_name'] : '')."'";
			$query[] = "behaviors='".SQL::escape(isset($fields['behaviors']) ? $fields['behaviors'] : '')."'";
			$query[] = "extra='".SQL::escape(isset($fields['extra']) ? $fields['extra'] : '')."'";
			$query[] = "icon_url='".SQL::escape(isset($fields['icon_url']) ? $fields['icon_url'] : '')."'";
			$query[] = "thumbnail_url='".SQL::escape(isset($fields['thumbnail_url']) ? $fields['thumbnail_url'] : '')."'";
			$query[] = "rank='".SQL::escape($fields['rank'])."'";
			$query[] = "meta='".SQL::escape(isset($fields['meta']) ? $fields['meta'] : '')."'";
			$query[] = "options='".SQL::escape(isset($fields['options']) ? $fields['options'] : '')."'";
			$query[] = "trailer='".SQL::escape(isset($fields['trailer']) ? $fields['trailer'] : '')."'";
		} else {
			$query[] = "nick_name=''";
			$query[] = "behaviors=''";
			$query[] = "extra=''";
			$query[] = "icon_url=''";
			$query[] = "thumbnail_url=''";
			$query[] = "rank=10000";
			$query[] = "meta=''";
			$query[] = "options=''";
			$query[] = "trailer=''";
		}

		// controlled fields
		$query[] = "active='".SQL::escape($fields['active'])."'";
		$query[] = "active_set='".SQL::escape($fields['active_set'])."'";

		// fields visible to authorized member
		$query[] = "anchor='".SQL::escape($fields['anchor'])."'";
		$query[] = "anchor_type=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', 1)";
		$query[] = "anchor_id=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', -1)";
		$query[] = "home_panel='".SQL::escape(isset($fields['home_panel']) ? $fields['home_panel'] : 'main')."'";
		$query[] = "title='".SQL::escape($fields['title'])."'";
		$query[] = "source='".SQL::escape(isset($fields['source']) ? $fields['source'] : '')."'";
		$query[] = "introduction='".SQL::escape(isset($fields['introduction']) ? $fields['introduction'] : '')."'";
		$query[] = "description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."'";
		$query[] = "language='".SQL::escape(isset($fields['language']) ? $fields['language'] : '')."'";
		$query[] = "locked='".SQL::escape(isset($fields['locked']) ? $fields['locked'] : 'N')."'";
		$query[] = "overlay='".SQL::escape(isset($fields['overlay']) ? $fields['overlay'] : '')."'";
		$query[] = "overlay_id='".SQL::escape(isset($fields['overlay_id']) ? $fields['overlay_id'] : '')."'";
		$query[] = "tags='".SQL::escape(isset($fields['tags']) ? $fields['tags'] : '')."'";
		$query[] = "hits=0";
		$query[] = "create_name='".SQL::escape(isset($fields['create_name']) ? $fields['create_name'] : $fields['edit_name'])."'";
		$query[] = "create_id=".SQL::escape(isset($fields['create_id']) ? $fields['create_id'] : (isset($fields['edit_id']) ? $fields['edit_id'] : '0'));
		$query[] = "create_address='".SQL::escape(isset($fields['create_address']) ? $fields['create_address'] : $fields['edit_address'])."'";
		$query[] = "create_date='".SQL::escape($fields['create_date'])."'";
		$query[] = "edit_name='".SQL::escape($fields['edit_name'])."'";
		$query[] = "edit_id=".SQL::escape(isset($fields['edit_id']) ? $fields['edit_id'] : '0');
		$query[] = "edit_address='".SQL::escape($fields['edit_address'])."'";
		$query[] = "edit_action='".SQL::escape(isset($fields['edit_action']) ? $fields['edit_action'] : 'article:create')."'";
		$query[] = "edit_date='".SQL::escape($fields['edit_date'])."'";

		// set or change the publication date
		if(isset($fields['publish_date']) && ($fields['publish_date'] > NULL_DATE)) {
			$query[] = "publish_name='".SQL::escape(isset($fields['publish_name']) ? $fields['publish_name'] : $fields['edit_name'])."'";
			if(isset($fields['publish_id']) || isset($fields['edit_id']))
				$query[] = "publish_id=".SQL::escape(isset($fields['publish_id']) ? $fields['publish_id'] : $fields['edit_id']);
			$query[] = "publish_address='".SQL::escape(isset($fields['publish_address']) ? $fields['publish_address'] : $fields['edit_address'])."'";
			$query[] = "publish_date='".SQL::escape($fields['publish_date'])."'";
		}

		// create a random handle for this article
		if(!isset($fields['handle']))
			$fields['handle'] = md5(mt_rand());
		$query[] = "handle='".SQL::escape($fields['handle'])."'";

		// allow surfer to access this page during his session
		Surfer::add_handle($fields['handle']);

		// insert a new record
		$query = "INSERT INTO ".SQL::table_name('articles')." SET ".implode(', ', $query);

		// actual insert
		if(SQL::query($query) === FALSE)
			return FALSE;

		// remember the id of the new item
		$id = SQL::get_last_id($context['connection']);

		// assign the page to related categories
		include_once $context['path_to_root'].'categories/categories.php';
		Categories::remember('article:'.$id, isset($fields['publish_date']) ? $fields['publish_date'] : NULL_DATE, isset($fields['tags']) ? $fields['tags'] : '');

		// turn author to page editor
		if(isset($fields['edit_id']) && $fields['edit_id'])
			Members::assign('user:'.$fields['edit_id'], 'article:'.$id);

		// return the id of the new item
		return $id;
	}

	/**
	 * limit the number of articles for one anchor
	 *
	 * This function deletes oldest pages going beyond the given threshold.
	 *
	 * @param int the maximum number of pages to keep in the database
	 * @return void
	 */
	function purge_for_anchor($anchor, $limit=1000) {
		global $context;

		// lists oldest entries beyond the limit
		$query = "SELECT articles.* FROM ".SQL::table_name('articles')." AS articles "
			." WHERE (articles.anchor LIKE '".SQL::escape($anchor)."')"
			." ORDER BY articles.edit_date DESC LIMIT ".$limit.', 10';

		// no result
		if(!$result =& SQL::query($query))
			return;

		// empty list
		if(!SQL::count($result))
			return;

		// delete silently all matching items
		while($item =& SQL::fetch($result))
			Articles::delete($item['id']);

		// end of processing
		SQL::free($result);

	}

	/**
	 * put an updated article in the database
	 *
	 * @param array an array of fields
	 * @return TRUE on success, or FALSE on error
	 *
	 * @see articles/edit.php
	 * @see services/blog.php
	**/
	function put(&$fields) {
		global $context;

		// id cannot be empty
		if(!isset($fields['id']) || !is_numeric($fields['id'])) {
			Skin::error(i18n::s('No item has the provided id.'));
			return FALSE;
		}

		// title cannot be empty
		if(!isset($fields['title']) || !$fields['title']) {
			Skin::error(i18n::s('No title has been provided.'));
			return FALSE;
		}

		// anchor cannot be empty
		if(!isset($fields['anchor']) || !$fields['anchor'] || (!$anchor = Anchors::get($fields['anchor']))) {
			Skin::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// protect from hackers
		if(isset($fields['icon_url']))
			$fields['icon_url'] = preg_replace('/[^\w\/\.,:%&\?=-]+/', '_', $fields['icon_url']);
		if(isset($fields['thumbnail_url']))
			$fields['thumbnail_url'] = preg_replace('/[^\w\/\.,:%&\?=-]+/', '_', $fields['thumbnail_url']);

		// set default values for this editor
		$fields = Surfer::check_default_editor($fields);

		// reinforce date formats
		if(!isset($fields['publish_date']) || ($fields['publish_date'] <= NULL_DATE))
			$fields['publish_date'] = NULL_DATE;

		// set conservative default values
		if(!isset($fields['active_set']))
			$fields['active_set'] = 'Y';
		if(!isset($fields['rank']))
			$fields['rank'] = 10000;

		// clean provided tags
		if(isset($fields['tags']))
			$fields['tags'] = trim($fields['tags'], " \t.:,!?");

		// cascade anchor access rights
		$fields['active'] = $anchor->ceil_rights($fields['active_set']);

		// all row updates
		$query = array();

		// fields that are visible only to associates -- see articles/edit.php
		if(Surfer::is_associate()) {
			$query[] = "prefix='".SQL::escape(isset($fields['prefix']) ? $fields['prefix'] : '')."'";
			$query[] = "suffix='".SQL::escape(isset($fields['suffix']) ? $fields['suffix'] : '')."'";
		}

		// fields that are visible only to associates and to editors -- see articles/edit.php
		if(Surfer::is_empowered() && Surfer::is_member()) {
			$query[] = "nick_name='".SQL::escape(isset($fields['nick_name']) ? $fields['nick_name'] : '')."'";
			$query[] = "behaviors='".SQL::escape(isset($fields['behaviors']) ? $fields['behaviors'] : '')."'";
			$query[] = "extra='".SQL::escape(isset($fields['extra']) ? $fields['extra'] : '')."'";
			$query[] = "icon_url='".SQL::escape(isset($fields['icon_url']) ? $fields['icon_url'] : '')."'";
			$query[] = "thumbnail_url='".SQL::escape(isset($fields['thumbnail_url']) ? $fields['thumbnail_url'] : '')."'";
			$query[] = "rank='".SQL::escape($fields['rank'])."'";
			$query[] = "locked='".SQL::escape(isset($fields['locked']) ? $fields['locked'] : 'N')."'";
			$query[] = "meta='".SQL::escape(isset($fields['meta']) ? $fields['meta'] : '')."'";
			$query[] = "options='".SQL::escape(isset($fields['options']) ? $fields['options'] : '')."'";
			$query[] = "trailer='".SQL::escape(isset($fields['trailer']) ? $fields['trailer'] : '')."'";
			$query[] = "active='".SQL::escape($fields['active'])."'";
			$query[] = "active_set='".SQL::escape($fields['active_set'])."'";
		}

		// fields visible to authorized member
		$query[] = "anchor='".SQL::escape($fields['anchor'])."'";
		$query[] = "anchor_type=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', 1)";
		$query[] = "anchor_id=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', -1)";
		$query[] = "home_panel='".SQL::escape(isset($fields['home_panel']) ? $fields['home_panel'] : 'main')."'";
		$query[] = "title='".SQL::escape($fields['title'])."'";
		$query[] = "source='".SQL::escape(isset($fields['source']) ? $fields['source'] : '')."'";
		$query[] = "introduction='".SQL::escape(isset($fields['introduction']) ? $fields['introduction'] : '')."'";
		$query[] = "description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."'";
		$query[] = "language='".SQL::escape(isset($fields['language']) ? $fields['language'] : '')."'";
		$query[] = "overlay='".SQL::escape(isset($fields['overlay']) ? $fields['overlay'] : '')."'";
		$query[] = "overlay_id='".SQL::escape(isset($fields['overlay_id']) ? $fields['overlay_id'] : '')."'";
		$query[] = "tags='".SQL::escape(isset($fields['tags']) ? $fields['tags'] : '')."'";

		// set or change the publication date
		if(isset($fields['publish_date']) && ($fields['publish_date'] > NULL_DATE)) {
			$query[] = "publish_name='".SQL::escape(isset($fields['publish_name']) ? $fields['publish_name'] : $fields['edit_name'])."'";
			if(isset($fields['publish_id']) || isset($fields['edit_id']))
				$query[] = "publish_id=".SQL::escape(isset($fields['publish_id']) ? $fields['publish_id'] : $fields['edit_id']);
			$query[] = "publish_address='".SQL::escape(isset($fields['publish_address']) ? $fields['publish_address'] : $fields['edit_address'])."'";
			$query[] = "publish_date='".SQL::escape($fields['publish_date'])."'";
		}

		// maybe a silent update
		if(!isset($fields['silent']) || ($fields['silent'] != 'Y') || !Surfer::is_empowered()) {
			$query[] = "edit_name='".SQL::escape($fields['edit_name'])."'";
			$query[] = "edit_id=".SQL::escape(isset($fields['edit_id']) ? $fields['edit_id'] : '0');
			$query[] = "edit_address='".SQL::escape($fields['edit_address'])."'";
			$query[] = "edit_action='article:update'";
			$query[] = "edit_date='".SQL::escape($fields['edit_date'])."'";
		}

		// update an existing record
		$query = "UPDATE ".SQL::table_name('articles')." SET ".implode(', ', $query)." WHERE id = ".SQL::escape($fields['id']);
		if(SQL::query($query) === FALSE)
			return FALSE;

		// list the article in categories
		include_once $context['path_to_root'].'categories/categories.php';
		Categories::remember('article:'.$fields['id'], isset($fields['publish_date']) ? $fields['publish_date'] : NULL_DATE, isset($fields['tags']) ? $fields['tags'] : '');

		// end of job
		return TRUE;
	}

	/**
	 * change only some attributes
	 *
	 * @param array an array of fields
	 * @return TRUE on success, or FALSE on error
	**/
	function put_attributes(&$fields) {
		global $context;

		// id cannot be empty
		if(!isset($fields['id']) || !is_numeric($fields['id'])) {
			Skin::error(i18n::s('No item has the provided id.'));
			return FALSE;
		}

		// set default values for this editor
		$fields = Surfer::check_default_editor($fields);

		// quey components
		$query = array();

		// anchor this page to another place
		if(isset($fields['anchor'])) {
			$query[] = "anchor='".SQL::escape($fields['anchor'])."'";
			$query[] = "anchor_type=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', 1)";
			$query[] = "anchor_id=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', -1)";
		}
		if(isset($fields['prefix']) && Surfer::is_associate())
			$query[] = "prefix='".SQL::escape($fields['prefix'])."'";
		if(isset($fields['suffix']) && Surfer::is_associate())
			$query[] = "suffix='".SQL::escape($fields['suffix'])."'";

		// fields that are visible only to associates and to editors -- see articles/edit.php
		if(isset($fields['nick_name']) && Surfer::is_empowered() && Surfer::is_member())
			$query[] = "nick_name='".SQL::escape($fields['nick_name'])."'";
		if(isset($fields['behaviors']) && Surfer::is_empowered() && Surfer::is_member())
			$query[] = "behaviors='".SQL::escape($fields['behaviors'])."'";
		if(isset($fields['extra']) && Surfer::is_empowered() && Surfer::is_member())
			$query[] = "extra='".SQL::escape($fields['extra'])."'";
		if(isset($fields['icon_url']) && Surfer::is_empowered() && Surfer::is_member())
			$query[] = "icon_url='".SQL::escape(preg_replace('/[^\w\/\.,:%&\?=-]+/', '_', $fields['icon_url']))."'";
		if(isset($fields['rank']) && Surfer::is_empowered() && Surfer::is_member())
			$query[] = "rank='".SQL::escape($fields['rank'])."'";
		if(isset($fields['thumbnail_url']) && Surfer::is_empowered() && Surfer::is_member())
			$query[] = "thumbnail_url='".SQL::escape(preg_replace('/[^\w\/\.,:%&\?=-]+/', '_', $fields['thumbnail_url']))."'";
		if(isset($fields['locked']) && Surfer::is_empowered() && Surfer::is_member())
			$query[] = "locked='".SQL::escape($fields['locked'])."'";
		if(isset($fields['meta']) && Surfer::is_empowered() && Surfer::is_member())
			$query[] = "meta='".SQL::escape($fields['meta'])."'";
		if(isset($fields['options']) && Surfer::is_empowered() && Surfer::is_member())
			$query[] = "options='".SQL::escape($fields['options'])."'";
		if(isset($fields['trailer']) && Surfer::is_empowered() && Surfer::is_member())
			$query[] = "trailer='".SQL::escape($fields['trailer'])."'";
//		if(Surfer::is_empowered() && Surfer::is_member())
//			$query[] = "active='".SQL::escape($fields['active'])."',";
//		if(Surfer::is_empowered() && Surfer::is_member())
//			$query[] = "active_set='".SQL::escape($fields['active_set'])."',";

		// fields visible to authorized member
		if(isset($fields['home_panel']))
			$query[] = "home_panel='".SQL::escape($fields['home_panel'])."'";
		if(isset($fields['title']))
			$query[] = "title='".SQL::escape($fields['title'])."'";
		if(isset($fields['source']))
			$query[] = "source='".SQL::escape($fields['source'])."'";
		if(isset($fields['introduction']))
			$query[] = "introduction='".SQL::escape($fields['introduction'])."'";
		if(isset($fields['description']))
			$query[] = "description='".SQL::escape($fields['description'])."'";
		if(isset($fields['language']))
			$query[] = "language='".SQL::escape($fields['language'])."'";
		if(isset($fields['overlay']))
			$query[] = "overlay='".SQL::escape($fields['overlay'])."'";
		if(isset($fields['overlay_id']))
			$query[] = "overlay_id='".SQL::escape($fields['overlay_id'])."'";
		if(isset($fields['publish_date']) && Surfer::is_empowered()) {
			$query[] = "publish_name='".SQL::escape(isset($fields['publish_name']) ? $fields['publish_name'] : $fields['edit_name'])."'";
			$query[] = "publish_id='".SQL::escape(isset($fields['publish_id']) ? $fields['publish_id'] : $fields['edit_id'])."'";
			$query[] = "publish_address='".SQL::escape(isset($fields['publish_address']) ? $fields['publish_address'] : $fields['edit_address'])."'";
			$query[] = "publish_date='".SQL::escape($fields['publish_date'])."'";
		}

		if(isset($fields['tags']))
			$query[] = "tags='".SQL::escape($fields['tags'])."'";

		// nothing to update
		if(!count($query))
			return TRUE;

		// maybe a silent update
		if(!isset($fields['silent']) || ($fields['silent'] != 'Y') || !Surfer::is_empowered()) {
			$query[] = "edit_name='".SQL::escape($fields['edit_name'])."'";
			$query[] = "edit_id='".SQL::escape($fields['edit_id'])."'";
			$query[] = "edit_address='".SQL::escape($fields['edit_address'])."'";
			$query[] = "edit_action='article:update'";
			$query[] = "edit_date='".SQL::escape($fields['edit_date'])."'";
		}

		// actual update query
		$query = "UPDATE ".SQL::table_name('articles')
			." SET ".implode(', ', $query)
			." WHERE id = ".SQL::escape($fields['id']);
		if(!SQL::query($query))
			return FALSE;

		// end of job
		return TRUE;
	}

	/**
	 * rate a page
	 *
	 * Errors are not reported, if any
	 *
	 * @param int the id of the article to rate
	 * @param int the rate
	 */
	function rate($id, $rating) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return i18n::s('No item has the provided id.');

		// update rating in database
		$query = "UPDATE ".SQL::table_name('articles')
			." SET rating_sum = rating_sum + ".SQL::escape($rating).", rating_count = rating_count + 1"
			." WHERE id = ".SQL::escape($id);
		SQL::query($query);

	}

	/**
	 * search for some keywords in all articles
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - article is restricted (active='N'), but surfer is an associate
	 * - article has been officially published, or the surfer is a logged user
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @see search.php
	 * @see services/search.php
	 * @see categories/set_keyword.php
	 *
	 * @param the search string
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param mixed the layout, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &search($pattern, $offset=0, $count=50, $layout='full') {
		global $context;

		return Articles::search_in_section(NULL, $pattern, $offset, $count, $layout);
	}

	/**
	 * search for some keywords articles anchored to one precise section
	 *
	 * This function also searches in sub-sections, with up to three levels of depth.
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - article is restricted (active='N'), but surfer is an associate
	 * - article has been officially published, or the surfer is a logged user
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @see search.php
	 *
	 * @param the id of the section to look in
	 * @param the search string
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param mixed the layout, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &search_in_section($section_id, $pattern, $offset=0, $count=10, $layout='full') {
		global $context;

		// search is restricted to one section
		$sections_where = '';
		if($section_id) {
			$sections_where = "sections.id LIKE '".SQL::escape($section_id)."'";

			// look for children
			$anchors = array();

			// first level of depth
			$topics =& Sections::get_children_of_anchor('section:'.$section_id, 'main');
			$anchors = array_merge($anchors, $topics);

			// second level of depth
			if(count($topics) && (count($anchors) < 50)) {
				$topics =& Sections::get_children_of_anchor($topics, 'main');
				$anchors = array_merge($anchors, $topics);
			}

			// third level of depth
			if(count($topics) && (count($anchors) < 50)) {
				$topics =& Sections::get_children_of_anchor($topics, 'main');
				$anchors = array_merge($anchors, $topics);
			}

			// extend the search clause
			foreach($anchors as $reference)
				$sections_where .= " OR sections.id LIKE '".str_replace('section:', '', $reference)."'";

			//include managed sections
			if(count($my_sections = Surfer::assigned_sections()))
				$sections_where .= " OR sections.id LIKE ".join(" OR sections.id LIKE ", $my_sections);

		}

		// select among active sections
		if($sections_where)
			$sections_where = " AND (".$sections_where.")";

		// select among active articles
		$where = "articles.active='Y'";

		// add restricted items to authenticated surfers, or if teasers are allowed
		if(Surfer::is_logged() || !isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			$where .= " OR articles.active='R'";

		// associates can access hidden articles
		if(is_string($layout) && ($layout == 'feeds'))
			;
		elseif(Surfer::is_associate())
			$where .= " OR articles.active='N'";

		$where = "(".$where.")";

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// anonymous surfers and subscribers will see only published articles
		if(!Surfer::is_member())
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$now."')";

		// only consider live articles
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$now."'))";

		// match
		$match = '';
		$words = preg_split('/\s/', $pattern);
		while($word = each($words))
			$match .=  " AND MATCH(articles.title, articles.source, articles.introduction, articles.overlay, articles.description) AGAINST('".SQL::escape($word['value'])."')";

		// the list of articles
		$query = "SELECT articles.*"
			." FROM (".SQL::table_name('articles')." AS articles"
			.", ".SQL::table_name('sections')." AS sections)"
			." WHERE ((articles.anchor_type LIKE 'section') AND (articles.anchor_id = sections.id))"
			."	AND (".$where.")".$match.$sections_where
			." GROUP BY articles.id"
			." ORDER BY articles.edit_date DESC"
			." LIMIT ".$offset.','.$count;

		$output =& Articles::list_selected(SQL::query($query), $layout);
		return $output;
	}

	/**
	 * create tables for articles
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['active']		= "ENUM('Y','R','N') DEFAULT 'Y' NOT NULL";
		$fields['active_set']	= "ENUM('Y','R','N') DEFAULT 'Y' NOT NULL";
		$fields['anchor']		= "VARCHAR(64) DEFAULT 'section:1' NOT NULL";
		$fields['anchor_type']	= "VARCHAR(64) DEFAULT 'section' NOT NULL";
		$fields['anchor_id']	= "MEDIUMINT UNSIGNED NOT NULL";
		$fields['behaviors']	= "TEXT NOT NULL";
		$fields['create_address']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['create_date']	= "DATETIME";
		$fields['create_id']	= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['create_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['description']	= "MEDIUMTEXT NOT NULL";
		$fields['edit_action']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_address'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_date']	= "DATETIME";
		$fields['edit_id']		= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['edit_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['expiry_date']	= "DATETIME";
		$fields['extra']		= "TEXT NOT NULL";
		$fields['handle']		= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['hits'] 		= "INT UNSIGNED DEFAULT 0 NOT NULL";
		$fields['home_panel']	= "VARCHAR(255) DEFAULT 'main' NOT NULL";
		$fields['icon_url'] 	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['introduction'] = "TEXT NOT NULL";
		$fields['language'] 	= "VARCHAR(64) DEFAULT '' NOT NULL";
		$fields['locked']		= "ENUM('Y', 'N') DEFAULT 'N' NOT NULL";
		$fields['meta'] 		= "TEXT NOT NULL";
		$fields['nick_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['options']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['overlay']		= "TEXT NOT NULL";
		$fields['overlay_id']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['prefix']		= "TEXT NOT NULL";
		$fields['publish_address']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['publish_date'] = "DATETIME";
		$fields['publish_id']	= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['publish_name'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['rank'] 		= "INT UNSIGNED DEFAULT 10000 NOT NULL";
		$fields['rating_sum']	= "INT UNSIGNED DEFAULT 0 NOT NULL";
		$fields['rating_count'] = "INT UNSIGNED DEFAULT 0 NOT NULL";
		$fields['review_date']	= "DATETIME";
		$fields['source']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['suffix']		= "TEXT NOT NULL";
		$fields['tags'] 		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['thumbnail_url']= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['title']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['trailer']		= "TEXT NOT NULL";

		$indexes = array();
		$indexes['PRIMARY KEY'] 		= "(id)";
		$indexes['INDEX active']		= "(active)";
		$indexes['INDEX anchor']		= "(anchor)";
		$indexes['INDEX anchor_id'] 	= "(anchor_id)";
		$indexes['INDEX anchor_type']	= "(anchor_type)";
		$indexes['INDEX create_date']	= "(create_date)";
		$indexes['INDEX create_id'] 	= "(create_id)";
		$indexes['INDEX edit_date'] 	= "(edit_date)";
		$indexes['INDEX edit_id']		= "(edit_id)";
		$indexes['INDEX expiry_date']	= "(expiry_date)";
		$indexes['INDEX handle']		= "(handle)";
		$indexes['INDEX hits']			= "(hits)";
		$indexes['INDEX home_panel']	= "(home_panel)";
		$indexes['INDEX language']		= "(language)";
		$indexes['INDEX locked']		= "(locked)";
		$indexes['INDEX nick_name'] 	= "(nick_name)";
		$indexes['INDEX overlay_id']	= "(overlay_id)";
		$indexes['INDEX publish_date']	= "(publish_date)";
		$indexes['INDEX publish_id']	= "(publish_id)";
		$indexes['INDEX rank']			= "(rank)";
		$indexes['INDEX rating_sum']	= "(rating_sum)";
		$indexes['INDEX review_date']	= "(review_date)";
		$indexes['INDEX title'] 		= "(title(255))";
		$indexes['FULLTEXT INDEX']		= "full_text(title, source, introduction, overlay, description)";

		return SQL::setup_table('articles', $fields, $indexes);

	}

	/**
	 * stamp an article
	 *
	 * This function is used to change various dates for one article.
	 *
	 * [*] If a publication date is provided, it is saved along the article.
	 * An optional expiry date will be saved as well.
	 *
	 * [*] If only an expiry date is provided, it is saved along the article.
	 *
	 * [*] If no date is provided, the review field is updated to the current date and time.
	 *
	 * Dates are supposed to be in UTC time zone.
	 *
	 * The name of the surfer is registered as the official publisher.
	 * As an alternative, publisher attributes ('name', 'id' and 'address') can be provided
	 * in parameters.
	 *
	 * @param int the id of the item to publish
	 * @param string the target publication date, if any
	 * @param string the target expiration date, if any
	 * @param array attributes of the publisher, if any
	 * @return string either a null string, or some text describing an error to be inserted into the html response
	 *
	 * @see articles/publish.php
	 * @see sections/manage.php
	**/
	function stamp($id, $publication=NULL, $expiry=NULL, $publisher=NULL) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return i18n::s('No item has the provided id.');

		// server offset
		$server_offset = 0;
		if(isset($context['gmt_offset']))
			$server_offset = intval($context['gmt_offset']);

		// surfer offset
		$surfer_offset = Surfer::get_gmt_offset();

		// no publication time is provided
		if(!isset($publication) || !$publication)
			$publication_stamp = 0;

		// YYMMDD-HH:MM:SS GMT -- this one is natively GMT
		elseif(preg_match('/GMT$/', $publication) && (strlen($publication) == 19)) {

			// YYMMDD-HH:MM:SS GMT -> HH, MM, SS, MM, DD, YY
			$publication_stamp = gmmktime(intval(substr($publication, 7, 2)), intval(substr($publication, 10, 2)), intval(substr($publication, 13, 2)),
				intval(substr($publication, 2, 2)), intval(substr($publication, 4, 2)), intval(substr($publication, 0, 2)));

		// time()-like stamp
		} elseif(intval($publication) > 1000000000) {

			// adjust to UTC time zone
			$publication_stamp = intval($publication) + ($context['gmt_offset'] * 3600);

		// YYYY-MM-DD HH:MM:SS, or a string that can be readed
		} elseif(($publication_stamp = SQL::strtotime($publication)) != -1)
			;

		// invalid date
		else
			return sprintf(i18n::s('"%s" is not a valid date'), $publication);

		// no expiration date
		if(!isset($expiry) || !$expiry)
			$expiry_stamp = 0;

		// YYMMDD-HH:MM:SS GMT -- this one is natively GMT
		elseif(preg_match('/GMT$/', $expiry) && (strlen($expiry) == 19)) {

			// YYMMDD-HH:MM:SS GMT -> HH, MM, SS, MM, DD, YY
			$expiry_stamp = gmmktime(substr($expiry, 7, 2), substr($expiry, 10, 2), substr($expiry, 13, 2),
				substr($expiry, 2, 2), substr($expiry, 4, 2), substr($expiry, 0, 2));

		// time()-like stamp
		} elseif(intval($expiry) > 1000000000) {

			// adjust to server time zone
			$expiry_stamp = intval($expiry) + ($context['gmt_offset'] * 3600);

		// YYYY-MM-DD HH:MM:SS, or a string that can be readed
		} elseif(($expiry_stamp = SQL::strtotime($expiry)) != -1)
			;

		// invalid date
		else
			return sprintf(i18n::s('"%s" is not a valid date'), $expiry);

		// review date
		$review_stamp = 0;
		if(!$publication_stamp && !$expiry_stamp)
			$review_stamp = time();

		// shape the query
		$query = array();

		if($publication_stamp > 0)
			$query[] = "publish_name='".SQL::escape(isset($publisher['name']) ? $publisher['name'] : Surfer::get_name())."',"
				."publish_id='".SQL::escape(isset($publisher['id']) ? $publisher['id'] : Surfer::get_id())."',"
				."publish_address='".SQL::escape(isset($publisher['address']) ? $publisher['address'] : Surfer::get_email_address())."',"
				."publish_date='".gmstrftime('%Y-%m-%d %H:%M:%S', $publication_stamp)."',"
				."edit_name='".SQL::escape(isset($publisher['name']) ? $publisher['name'] : Surfer::get_name())."',"
				."edit_id='".SQL::escape(isset($publisher['id']) ? $publisher['id'] : Surfer::get_id())."',"
				."edit_address='".SQL::escape(isset($publisher['address']) ? $publisher['address'] : Surfer::get_email_address())."',"
				."edit_action='article:publish',"
				."edit_date='".gmstrftime('%Y-%m-%d %H:%M:%S')."'";
		if($expiry_stamp > 0)
			$query[] = "expiry_date='".gmstrftime('%Y-%m-%d %H:%M:%S', $expiry_stamp)."'";
		if($review_stamp > 0)
			$query[] = "review_date='".gmstrftime('%Y-%m-%d %H:%M:%S', $review_stamp)."'";

		// update an existing record
		$query = "UPDATE ".SQL::table_name('articles')." SET ".implode(',', $query)." WHERE id = ".SQL::escape($id);
		if(SQL::query($query) === FALSE)
			return NULL;

		// remember the publication in weekly and monthly categories
		if($publication_stamp > 0) {
			include_once $context['path_to_root'].'categories/categories.php';
			Categories::remember('article:'.$id, gmstrftime('%Y-%m-%d %H:%M:%S', $publication_stamp));
		}

		// end of job
		return NULL;
	}

	/**
	 * get some statistics
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - article is protected (active='N'), but surfer is an associate, and we are not feeding someone
	 * - article has been officially published
	 * - an expiry date has not been defined, or is not yet passed
	 * - related section is regularly displayed at the front page
	 * - article is allowed to be displayed at the front page of the server (home_panel != 'none')
	 *
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see articles/index.php
	 */
	function &stat() {
		global $context;

		// select among active items
		$where = "articles.active='Y'";

		// add restricted items to authenticated surfers, or if teasers are allowed
		if(Surfer::is_logged() || !isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			$where .= " OR articles.active='R'";

		// associates can access hidden articles
		if(Surfer::is_associate())
			$where .= " OR articles.active='N'";

		$where = '('.$where.')';

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// list only published articles
		$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
			." AND (articles.publish_date < '".$now."')";

		// only consider live articles
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$now."'))";

		// avoid articles pushed away from the front page
		$where .= ' AND ((sections.home_panel = "main") OR (sections.home_panel = "none"))'
			.' AND (sections.index_map = "Y")'
			.' AND (articles.home_panel = "main")';


		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(articles.edit_date) as oldest_date, MAX(articles.edit_date) as newest_date"
			." FROM ".SQL::table_name('articles')." AS articles"
			.", ".SQL::table_name('sections')." AS sections"
			." WHERE ((articles.anchor_type LIKE 'section') AND (articles.anchor_id = sections.id))  AND ".$where;

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get some statistics for one anchor
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - article is protected (active='N'), but surfer is an associate, and we are not feeding someone
	 * - surfer is anonymous or the variant is 'boxes', and article has been officially published
	 * - logged surfers are restricted to their own articles, plus published articles
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param the selected anchor (e.g., 'section:12')
	 * @param boolean FALSE to include sticky pages, TRUE otherwise
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see sections/view.php
	 */
	function &stat_for_anchor($anchor, $without_sticky=FALSE) {
		global $context;

		// sanity check
		if(!$anchor)
			return NULL;

		// select among active items
		$where = "articles.active='Y'";

		// add restricted items to authenticated surfers, or if teasers are allowed
		if(Surfer::is_logged()
			|| !isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			$where .= " OR articles.active='R'";

		// associates, editors and readers may see everything
		if(Surfer::is_empowered('S'))
			$where .= " OR articles.active='N'";

		$where = '('.$where.')';

		// avoid sticky articles
		if($without_sticky)
			$where .= " AND (articles.rank >= 10000)";

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// anonymous surfers and subscribers will see only published articles
		if(!Surfer::is_member()) {
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$now."')";

		// logged surfers that are non-associates are restricted to their own articles, plus published articles
		} elseif(!Surfer::is_empowered()) {
			$where .= " AND ((articles.create_id='".Surfer::get_id()."') OR (NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$now."')))";
		}

		// only consider live articles
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$now."'))";

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date"
			." FROM ".SQL::table_name('articles')." AS articles"
			." WHERE (articles.anchor LIKE '".SQL::escape($anchor)."') AND (".$where.")";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get some statistics for one author
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but surfer is a logged user
	 * - article is not visible (active='N'), but surfer is an associate
	 * - article has been officially published, or the surfer is a logged user
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param the selected author (e.g., '12')
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see users/view.php
	 */
	function &stat_for_author($author_id) {
		global $context;

		// sanity check
		if(!$author_id)
			return NULL;
		$author_id = SQL::escape($author_id);

		// select among active and restricted items
		$where = "articles.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR articles.active='R'";

		// associates can access hidden articles
		if(Surfer::is_associate())
			$where .= " OR articles.active='N'";

		$where = '('.$where.')';

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// list only articles contributed by this author
		$where .= " AND (articles.create_id LIKE '$author_id')";

		// only original author and associates will see draft articles
		if(!Surfer::is_member() || (!Surfer::is_associate() && (Surfer::get_id() != $author_id)))
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$now."')";

		// only consider live articles
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$now."'))";

		// select among available items
		$query = "SELECT COUNT(*) as count,"
			."	MIN(articles.edit_date) as oldest_date,"
			."	MAX(articles.edit_date) as newest_date"
			." FROM ".SQL::table_name('articles')." AS articles"
			." WHERE (".$where.")";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * unpublish an article
	 *
	 * Clear all publishing information
	 *
	 * @param int the id of the item to unpublish
	 * @return string either a null string, or some text describing an error to be inserted into the html response
	 * @see articles/unpublish.php
	**/
	function unpublish($id) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return i18n::s('No item has the provided id.');

		// set default values
		$fields = Surfer::check_default_editor(array());

		// update an existing record, except the date
		$query = "UPDATE ".SQL::table_name('articles')." SET "
			." publish_name='',"
			." publish_id='',"
			." publish_address='',"
			." publish_date='',"
			." edit_name='".SQL::escape($fields['edit_name'])."',"
			." edit_id='".SQL::escape($fields['edit_id'])."',"
			." edit_address='".SQL::escape($fields['edit_address'])."',"
			." edit_action='article:update'"
			." WHERE id = ".SQL::escape($id);
		SQL::query($query);

		// end of job
		return NULL;
	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('articles');

?>