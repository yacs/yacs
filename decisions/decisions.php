<?php
/**
 * the database abstraction layer for decisions
 *
 * Decisions are a way to record signatures added to pages.
 * Information stored in a decision includes:
 * - the decision itself, which can be a Yes, or a No
 * - identification of the person who has signed (typically, id and e-mail address of the surfer)
 * - the date of the decision
 * - identification of the signed page (typically, and anchor to some article)
 * - identification of the container of the signed page (typically, an anchor to some section)
 * - comment to the decision (optional)
 *
 * At the moment YACS supports following decision types:
 * - yes - this is an approval
 * - no - this is a reject
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Decisions {

	/**
	 * clear cache entries for one item
	 *
	 * @param array item attributes
	 */
	function clear(&$item) {

		// where this item can be displayed
		$topics = array('decisions');

		// clear anchor page
		if(isset($item['anchor']))
			$topics[] = $item['anchor'];

		// clear this page
		if(isset($item['id']))
			$topics[] = 'decision:'.$item['id'];

		// clear the cache
		Cache::clear($topics);

	}

	/**
	 * delete one decision
	 *
	 * @param int the id of the decision to delete
	 * @return boolean TRUE on success, FALSE otherwise
	 *
	 * @see decisions/delete.php
	 */
	function delete($id) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return FALSE;

		// delete the record in the database
		$query = "DELETE FROM ".SQL::table_name('decisions')." WHERE id = ".SQL::escape($id);
		if(SQL::query($query) === FALSE)
			return FALSE;

		// job done
		return TRUE;
	}

	/**
	 * delete all decisions for a given anchor
	 *
	 * @param the anchor to check
	 *
	 * @see overlays/petition.php
	 * @see overlays/vote.php
	 * @see shared/anchors.php
	 */
	function delete_for_anchor($anchor) {
		global $context;

		// delete all matching records in the database
		$query = "DELETE FROM ".SQL::table_name('decisions')." WHERE anchor LIKE '".SQL::escape($anchor)."'";
		SQL::query($query);
	}

	/**
	 * duplicate all decisions for a given anchor
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
		$query = "SELECT * FROM ".SQL::table_name('decisions')." WHERE anchor LIKE '".SQL::escape($anchor_from)."'";
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
				if($new_id = Decisions::post($item)) {

					// more pairs of strings to transcode
					$transcoded[] = array('/\[decision='.preg_quote($old_id, '/').'/i', '[decision='.$new_id);

					// duplicate elements related to this item
					Anchors::duplicate_related_to('decision:'.$old_id, 'decision:'.$new_id);

					// stats
					$count++;
				}
			}

			// transcode in anchor
			if($anchor =& Anchors::get($anchor_to))
				$anchor->transcode($transcoded);

		}

		// number of duplicated records
		return $count;
	}

	/**
	 * get one decision by id
	 *
	 * @param int the id of the decision
	 * @return the resulting $item array, with at least keys: 'id', 'type', 'description', etc.
	 *
	 * @see decisions/delete.php
	 * @see decisions/edit.php
	 * @see decisions/view.php
	 */
	function &get($id) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// select among available items -- exact match
		$query = "SELECT * FROM ".SQL::table_name('decisions')." AS decisions "
			." WHERE (decisions.id = ".SQL::escape($id).")";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * link to surfer ballot, if any
	 *
	 * @param string the anchor of the vote
	 * @return either NULL, or the link of the ballot
	 */
	function get_ballot($anchor) {
		global $context;

		// sanity check
		if(!$anchor)
			return NULL;
		$anchor = SQL::escape($anchor);

		// sanity check, again
		if(!Surfer::get_id())
			return NULL;

		// select among available items -- exact match
		$query = "SELECT id FROM ".SQL::table_name('decisions')." AS decisions "
			." WHERE (decisions.anchor LIKE '".$anchor."') AND (decisions.create_id = ".SQL::escape(Surfer::get_id()).")";

		// a link to the existing ballot
		if($row =& SQL::query_first($query))
			return $row['id'];

		// no ballot
		return NULL;
	}

	/**
	 * get a <img> element
	 *
	 * @param the type ('yes', etc.')
	 * @return a suitable HTML element
	 *
	 */
	function get_img($type) {
		global $context;

		switch($type) {

		// reject
		case 'no':

			// use skin declaration if any
			if(!defined('NO_IMG')) {

				// else use default image file
				$file = 'skins/images/decisions/no.gif';
				if($size = Safe::GetImageSize($context['path_to_root'].$file))
					define('NO_IMG', '<img src="'.$context['url_to_root'].$file.'" '.$size[3].' alt="No" /> ');
				else
					define('NO_IMG', '');
			}
			return NO_IMG;

		// approval
		case 'yes':
		default:

			// use skin declaration if any
			if(!defined('YES_IMG')) {

				// else use default image file
				$file = 'skins/images/decisions/yes.gif';
				if($size = Safe::GetImageSize($context['path_to_root'].$file))
					define('YES_IMG', '<img src="'.$context['url_to_root'].$file.'" '.$size[3].' alt="" /> ');
				else
					define('YES_IMG', '');
			}
			return YES_IMG;
		}
	}

	/**
	 * get id of next decision
	 *
	 * This function is used to build navigation bars.
	 *
	 * @param array the current item
	 * @param string the anchor of the current item
	 * @param string the order, either 'date' or 'reverse'
	 * @return some text
	 *
	 * @see articles/article.php
	 * @see users/user.php
	 */
	function get_next_url($item, $anchor, $order='date') {
		global $context;

		// sanity check
		if(!is_array($item))
			return $item;

		// depending on selected sequence
		if($order == 'date') {
			$match = "decisions.create_date > '".SQL::escape($item['create_date'])."'";
			$order = 'decisions.create_date';
		} elseif($order == 'reverse') {
			$match = "decisions.create_date < '".SQL::escape($item['create_date'])."'";
			$order = 'decisions.create_date DESC';
		} else
			return "unknown order '".$order."'";


		// query the database
		$query = "SELECT id FROM ".SQL::table_name('decisions')." AS decisions "
			." WHERE (decisions.anchor LIKE '".SQL::escape($anchor)."') AND (".$match.")"
			." ORDER BY ".$order." LIMIT 0, 1";
		if(!$result =& SQL::query($query))
			return NULL;

		// no result
		if(!SQL::count($result))
			return NULL;

		// return url of the first item of the list
		$item =& SQL::fetch($result);
		return Decisions::get_url($item['id']);
	}

	/**
	 * get types as options of a &lt;SELECT&gt; field
	 *
	 * @param string the current type
	 * @return the HTML to insert in the page
	 *
	 * @see decisions/edit.php
	 */
	function get_options($type) {
		global $context;

		// approval
		$content .= '<option value="yes"';
		if($type == 'yes')
			$content .= ' selected';
		$content .= '>'.i18n::s('Approved')."</option>\n";

		// reject
		$content .= '<option value="no"';
		if($type == 'no')
			$content .= ' selected';
		$content .= '>'.i18n::s('Rejected')."</option>\n";

		return $content;
	}

	/**
	 * get id of previous decision
	 *
	 * This function is used to build navigation bars.
	 *
	 * @param array the current item
	 * @param string the anchor of the current item
	 * @param string the order, either 'date' or 'reverse'
	 * @return some text
	 *
	 * @see articles/article.php
	 * @see users/user.php
	 */
	function get_previous_url($item, $anchor, $order='date') {
		global $context;

		// sanity check
		if(!is_array($item))
			return $item;

		// depending on selected sequence
		if($order == 'date') {
			$match = "decisions.create_date < '".SQL::escape($item['create_date'])."'";
			$order = 'decisions.create_date DESC';
		} elseif($order == 'reverse') {
			$match = "decisions.create_date > '".SQL::escape($item['create_date'])."'";
			$order = 'decisions.create_date';
		} else
			return "unknown order '".$order."'";

		// query the database
		$query = "SELECT id FROM ".SQL::table_name('decisions')." AS decisions "
			." WHERE (decisions.anchor LIKE '".SQL::escape($anchor)."') AND (".$match.")"
			." ORDER BY ".$order." LIMIT 0, 1";
		if(!$result =& SQL::query($query))
			return NULL;

		// no result
		if(!SQL::count($result))
			return NULL;

		// return url of the first item of the list
		$item =& SQL::fetch($result);
		return Decisions::get_url($item['id']);
	}

	/**
	 * get types as radio buttons
	 *
	 * @param string the current type
	 * @return the HTML to insert in the page
	 *
	 * @see decisions/edit.php
	 */
	function get_radio_buttons($name, $type) {
		global $context;

		$content = '';

		// approved
		$content .= '<input type="radio" name="'.$name.'" value="yes"';
		if(($type == 'yes') || !trim($type))
			$content .= ' checked="checked"';
		$content .='>'.i18n::s('Approved').' '.Decisions::get_img('yes').BR;

		// rejected
		$content .= '<input type="radio" name="'.$name.'" value="no"';
		if($type == 'no')
			$content .= ' checked="checked"';
		$content .='>'.i18n::s('Rejected').' '.Decisions::get_img('no').BR;

		return $content;
	}

	/**
	 * sum up all decisions for one anchor
	 *
	 * @param the selected anchor (e.g., 'article:12')
	 * @return the resulting ($total_count, $yes_count, $no_count) array
	 */
	function get_results_for_anchor($anchor) {
		global $context;

		// sanity check
		if(!$anchor)
			return NULL;

		// total number of decisions
		$total_count = 0;
		$query = "SELECT COUNT(*) as count"
			." FROM ".SQL::table_name('decisions')." AS decisions "
			." WHERE decisions.anchor LIKE '".SQL::escape($anchor)."'";
		if($row =& SQL::query_first($query))
			$total_count = $row['count'];

		// number of yes
		$yes_count = 0;
		$query = "SELECT COUNT(*) as count"
			." FROM ".SQL::table_name('decisions')." AS decisions "
			." WHERE decisions.anchor LIKE '".SQL::escape($anchor)."' AND decisions.type LIKE 'yes'";
		if($row =& SQL::query_first($query))
			$yes_count = $row['count'];

		// number of no
		$no_count = 0;
		$query = "SELECT COUNT(*) as count"
			." FROM ".SQL::table_name('decisions')." AS decisions "
			." WHERE decisions.anchor LIKE '".SQL::escape($anchor)."' AND decisions.type LIKE 'no'";
		if($row =& SQL::query_first($query))
			$no_count = $row['count'];

		// vote result
		return array($total_count, $yes_count, $no_count);
	}

	/**
	 * sum up all decisions for one anchor
	 *
	 * @param the selected anchor (e.g., 'article:12')
	 * @return string some text to summarize decisions
	 */
	function &get_results_label_for_anchor($anchor) {
		global $context;
		
		// no text yet
		$text = '';
		
		// decisions for this vote
		if(!$results = Decisions::get_results_for_anchor($anchor))
			return $text;
			
		list($total, $yes, $no) = $results;
		if(!$total)
			return $text;

		// total number of votes
		$text .= sprintf(i18n::ns('%d signature', '%d signatures', $total), $total);

		// balanced signatures
		if($yes * $no)
			$text .= ' ('.sprintf(i18n::s('%d%% yes, %d%% no'), (int)($yes*100/$total), (int)($no*100/$total)).')';

		// done
		return $text;

	}

	
	/**
	 * get a default title from the type selected
	 *
	 * @param the type ('suggestion', etc.')
	 * @return a suitable title
	 */
	function get_title($type) {
		global $context;
		switch($type) {
		case 'no':
			return i18n::s('Reject');
		case 'yes':
		default:
			return i18n::s('Approval');
		}
	}

	/**
	 * build a reference to a decision
	 *
	 * Depending on parameter '[code]with_friendly_urls[/code]' and on action,
	 * following results can be observed:
	 *
	 * - view - decisions/view.php?id=123 or decisions/view.php/123 or decision-123
	 *
	 * - other - decisions/edit.php?id=123 or decisions/edit.php/123 or decision-edit/123
	 *
	 * @param int the id of the decision to handle
	 * @param string the expected action ('view', 'print', 'edit', 'delete', ...)
	 * @return string a normalized reference
	 *
	 * @see control/configure.php
	 */
	function get_url($id, $action='view') {
		global $context;

		// add a decision -- the id has to be an anchor (e.g., 'article:15')
		if($action == 'decision') {
			if($context['with_friendly_urls'] == 'Y')
				return 'decisions/edit.php/'.str_replace(':', '/', $id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'decisions/edit.php/'.str_replace(':', '/', $id);
			else
				return 'decisions/edit.php?anchor='.urlencode($id);
		}

		// get decisions in rss -- the id has to be an anchor (e.g., 'article:15')
		if($action == 'feed') {
			if($context['with_friendly_urls'] == 'Y')
				return 'decisions/feed.php/'.str_replace(':', '/', $id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'decisions/feed.php/'.str_replace(':', '/', $id);
			else
				return 'decisions/feed.php?id='.urlencode($id);
		}

		// list decisions -- the id has to be an anchor (e.g., 'article:15')
		if($action == 'list') {
			if($context['with_friendly_urls'] == 'Y')
				return 'decisions/list.php/'.str_replace(':', '/', $id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'decisions/list.php/'.str_replace(':', '/', $id);
			else
				return 'decisions/list.php?anchor='.urlencode($id);
		}

		// mail for decision -- the id has to be an anchor (e.g., 'article:15')
		if($action == 'mail') {
			if($context['with_friendly_urls'] == 'Y')
				return 'decisions/mail.php/'.str_replace(':', '/', $id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'decisions/mail.php/'.str_replace(':', '/', $id);
			else
				return 'decisions/mail.php?anchor='.urlencode($id);
		}

		// navigate decisions -- the id has to be an anchor (e.g., 'article:15')
		if($action == 'navigate') {
			if($context['with_friendly_urls'] == 'Y')
				return 'decisions/list.php/'.str_replace(':', '/', $id).'/';
			elseif($context['with_friendly_urls'] == 'R')
				return 'decisions/list.php/'.str_replace(':', '/', $id).'/';
			else
				return 'decisions/list.php?anchor='.urlencode($id).'&amp;page=';
		}

		// check the target action
		if(!preg_match('/^(delete|edit|view)$/', $action))
			$action = 'view';

		// normalize the link
		return normalize_url(array('decisions', 'decision'), $action, $id);
	}

	/**
	 * list newest decisions
	 *
	 * To build a simple box of the newest decisions in your main index page, just use
	 * the following example:
	 * [php]
	 * // side bar with the list of most recent decisions
	 * include_once 'decisions/decisions.php';
	 * $items = Decisions::list_by_date(0, 10);
	 * $text = Skin::build_list($items, 'compact');
	 * $context['text'] .= Skin::build_box($title, $text, 'navigation');
	 * [/php]
	 *
	 * You can also display the newest decision separately, using Decisions::get_newest()
	 * In this case, skip the very first decision in the list by using
	 * Decisions::list_by_date(1, 10)
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see decisions/feed.php
	 */
	function &list_by_date($offset=0, $count=10, $variant='date') {
		global $context;

		// if not associate, restrict to decisions at public published not expired pages
		if(!Surfer::is_associate())
			$query = "SELECT decisions.* FROM ".SQL::table_name('articles')." AS articles"
				." LEFT JOIN ".SQL::table_name('decisions')." AS decisions"
				."	ON ((decisions.anchor_type LIKE 'article') AND (decisions.anchor_id = articles.id))"
				." WHERE (articles.active='Y')"
				." AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."'))"
				." ORDER BY decisions.create_date DESC LIMIT ".$offset.','.$count;

		// the list of decisions
		else
			$query = "SELECT decisions.* FROM ".SQL::table_name('decisions')." AS decisions "
				." ORDER BY decisions.create_date DESC LIMIT ".$offset.','.$count;

		$output =& Decisions::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list newest decisions for one anchor
	 *
	 * If variant is 'compact', the list start with the most recent decisions.
	 * Else decisions are ordered depending of their edition date.
	 *
	 * Example:
	 * [php]
	 * include_once 'decisions/decisions.php';
	 * $items = Decisions::list_by_date_for_anchor('section:12', 0, 10);
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
	 * @see articles/fetch_as_pdf.php
	 * @see articles/fetch_for_palm.php
	 * @see articles/print.php
	 * @see articles/view.php
	 * @see decisions/feed.php
	 */
	function &list_by_date_for_anchor($anchor, $offset=0, $count=20, $variant='no_anchor') {
		global $context;

		// the list of decisions
		$query = "SELECT * FROM ".SQL::table_name('decisions')." AS decisions "
			." WHERE decisions.anchor LIKE '".SQL::escape($anchor)."'"
			." ORDER BY decisions.create_date LIMIT ".$offset.','.$count;

		$output =& Decisions::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list newest decisions for one author
	 *
	 * Example:
	 * include_once 'decisions/decisions.php';
	 * $items = Decisions::list_by_date_for_author(12, 0, 10);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 *
	 * @param int the id of the author of the decision
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 */
	function &list_by_date_for_author($author_id, $offset=0, $count=20, $variant='date') {
		global $context;

		// the list of decisions
		$query = "SELECT * FROM ".SQL::table_name('decisions')." AS decisions "
			." WHERE (decisions.create_id = ".SQL::escape($author_id).")"
			." ORDER BY decisions.create_date DESC LIMIT ".$offset.','.$count;

		$output =& Decisions::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list selected decisions
	 *
	 * Accept following layouts:
	 * - 'compact' - to build short lists in boxes and sidebars (this is the default)
	 * - 'no_anchor' - to build detailed lists in an anchor page
	 * - 'full' - include anchor information
	 * - 'search' - include anchor information
	 * - 'feeds'
	 *
	 * @param resource result of database query
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return an array of $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_selected(&$result, $layout='compact') {
		global $context;

		// no result
		if(!$result) {
			$output = NULL;
			return $output;
		}

		// use an external layout
		if(is_object($layout)) {
			$output =& $layout->layout($result);
			return $output;
		}

		// build an array of links
		switch($layout) {

		case 'feeds':
			include_once $context['path_to_root'].'decisions/layout_decisions_as_feed.php';
			$variant =& new Layout_decisions_as_feed();
			$output =& $variant->layout($result);
			return $output;

		default:

			// allow for overload in skin -- see skins/import.php
			if(is_callable(array('skin', 'layout_decision'))) {

				// build an array of links
				$items = array();
				while($item =& SQL::fetch($result)) {

					// url to read the full article
					$url = Decisions::get_url($item['id']);

					// reset the rendering engine between items
					if(is_callable(array('Codes', 'initialize')))
						Codes::initialize($url);

					// format the resulting string depending on layout
					$items[$url] = Skin::layout_decision($item, $layout);

				}

				// end of processing
				SQL::free($result);
				return $items;

			// else use an external layout
			} else {
				include_once $context['path_to_root'].'decisions/layout_decisions.php';
				$variant =& new Layout_decisions();
				$output =& $variant->layout($result, $layout);
				return $output;
			}

		}

	}

	/**
	 * thread newest decisions
	 *
	 * Result of this query should be processed with a layout adapted to articles
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see decisions/index.php
	 */
	function &list_threads_by_date($offset=0, $count=10, $variant='date') {
		global $context;

		// a dynamic where clause
		$where = '';

		// if not associate, restrict to decisions at public published not expired pages
		if(!Surfer::is_associate()) {
			$where .= " AND (articles.active='Y')"
				." AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."'))";
		}

		// the list of decisions
		$query = "SELECT articles.* FROM ".SQL::table_name('decisions')." AS decisions,"
			." ".SQL::table_name('articles')." AS articles"
			." WHERE ((decisions.anchor_type LIKE 'article') AND (decisions.anchor_id = articles.id))"
			.$where
			." GROUP BY decisions.anchor"
			." ORDER BY decisions.edit_date DESC LIMIT ".$offset.','.$count;

		$output =& Articles::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * post a new decision or an updated decision
	 *
	 * This function populates the error context, where applicable.
	 *
	 * @param array an array of fields
	 * @return the id of the new decision, or FALSE on error
	 *
	 * @see decisions/edit.php
	**/
	function post(&$fields) {
		global $context;

		// no anchor reference
		if(!$fields['anchor']) {
			Logger::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// get the anchor
		if(!$anchor =& Anchors::get($fields['anchor'])) {
			Logger::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// protect from hackers
		if(isset($fields['edit_name']))
			$fields['edit_name'] = preg_replace(FORBIDDEN_IN_NAMES, '_', $fields['edit_name']);
		if(isset($fields['edit_address']))
			$fields['edit_address'] =& encode_link($fields['edit_address']);

		// set default values for this editor
		$fields = Surfer::check_default_editor($fields);

		// reinforce date formats
		if(!isset($fields['create_date']) || ($fields['create_date'] <= NULL_DATE))
			$fields['create_date'] = $fields['edit_date'];

		// update the existing record
		if(isset($fields['id'])) {

			// id cannot be empty
			if(!is_numeric($fields['id'])) {
				Logger::error(i18n::s('No item has the provided id.'));
				return FALSE;
			}

			// update the existing record, except type
			$query = "UPDATE ".SQL::table_name('decisions')." SET "
				."description='".SQL::escape($fields['description'])."'";

			// maybe another anchor
			if($fields['anchor'])
				$query .= ", anchor='".SQL::escape($fields['anchor'])."', "
					."anchor_type=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', 1), "
					."anchor_id=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', -1)";

			// maybe a silent update
			if(!isset($fields['silent']) || ($fields['silent'] != 'Y')) {
				$query .= ", "
					."edit_name='".SQL::escape($fields['edit_name'])."', "
					."edit_id='".SQL::escape($fields['edit_id'])."', "
					."edit_address='".SQL::escape($fields['edit_address'])."', "
					."edit_action='decision:update', "
					."edit_date='".SQL::escape($fields['edit_date'])."'";
			}

			$query .= " WHERE id = ".SQL::escape($fields['id']);

		// insert a new record
		} else {

			$query = "INSERT INTO ".SQL::table_name('decisions')." SET "
				."anchor='".SQL::escape($fields['anchor'])."', "
				."anchor_type=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', 1), "
				."anchor_id=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', -1), "
				."type='".SQL::escape(isset($fields['type']) ? $fields['type'] : 'attention')."', "
				."description='".SQL::escape($fields['description'])."', "
				."create_name='".SQL::escape($fields['edit_name'])."', "
				."create_id='".SQL::escape($fields['edit_id'])."', "
				."create_address='".SQL::escape($fields['edit_address'])."', "
				."create_date='".SQL::escape($fields['create_date'])."', "
				."edit_name='".SQL::escape($fields['edit_name'])."', "
				."edit_id='".SQL::escape($fields['edit_id'])."', "
				."edit_address='".SQL::escape($fields['edit_address'])."', "
				."edit_action='decision:create', "
				."edit_date='".SQL::escape($fields['edit_date'])."'";

		}

		// actual update query
		if(SQL::query($query) === FALSE)
			return FALSE;

		// remember the id of the new item
		if(!isset($fields['id']))
			$fields['id'] = SQL::get_last_id($context['connection']);

		// clear the cache for decisions
		Decisions::clear($fields);

		// end of job
		return $fields['id'];
	}

	/**
	 * search for some keywords in all decisions
	 *
	 * @param the search string
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 */
	function &search($pattern, $offset=0, $count=30, $variant='search') {
		global $context;

		// sanity check
		if(!$pattern = trim($pattern)) {
			$output = NULL;
			return $output;
		}
		
		// match
		$match = '';
		$words = preg_split('/\s/', $pattern);
		while($word = each($words)) {
			if($match)
				$match .= ' AND ';
			$match .=  "MATCH(description) AGAINST('".SQL::escape($word['value'])."')";
		}

		// the list of decisions
		$query = "SELECT * FROM ".SQL::table_name('decisions')." AS decisions "
			." WHERE ".$match
			." ORDER BY decisions.edit_date DESC"
			." LIMIT ".$offset.','.$count;

		$output =& Decisions::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * create tables for decisions
	 *
	 * @see control/setup.php
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['anchor']		= "VARCHAR(64) DEFAULT 'section:1' NOT NULL";
		$fields['anchor_type']	= "VARCHAR(64) DEFAULT 'section' NOT NULL";
		$fields['anchor_id']	= "MEDIUMINT UNSIGNED NOT NULL";
		$fields['type'] 		= "VARCHAR(64) DEFAULT 'yes' NOT NULL";
		$fields['description']	= "TEXT NOT NULL";
		$fields['create_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['create_id']	= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['create_address']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['create_date']	= "DATETIME";
		$fields['edit_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_id']		= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['edit_address'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_action']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_date']	= "DATETIME";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX anchor']	= "(anchor)";
		$indexes['INDEX anchor_id'] = "(anchor_id)";
		$indexes['INDEX anchor_type']	= "(anchor_type)";
		$indexes['INDEX create_date'] = "(create_date)";
		$indexes['INDEX create_id'] = "(create_id)";
		$indexes['INDEX edit_date'] = "(edit_date)";
		$indexes['INDEX edit_id']	= "(edit_id)";
		$indexes['INDEX type']		= "(type)";
		$indexes['FULLTEXT INDEX']	= "full_text(description)";

		return SQL::setup_table('decisions', $fields, $indexes);
	}

	/**
	 * get some statistics
	 *
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see decisions/index.php
	 */
	function &stat() {
		global $context;

		// if not associate, restrict to decisions at public published not expired pages
		if(!Surfer::is_associate())
			$query = "SELECT COUNT(*) as count, MIN(decisions.create_date) as oldest_date, MAX(decisions.create_date) as newest_date FROM ".SQL::table_name('articles')." AS articles"
				." LEFT JOIN ".SQL::table_name('decisions')." AS decisions"
				."	ON ((decisions.anchor_type LIKE 'article') AND (decisions.anchor_id = articles.id))"
				." WHERE (articles.active='Y')"
				." AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."'))";

		// the list of decisions
		else
			$query = "SELECT COUNT(*) as count, MIN(decisions.create_date) as oldest_date, MAX(decisions.create_date) as newest_date FROM ".SQL::table_name('decisions')." AS decisions ";

		// select among available items
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
	 */
	function &stat_for_anchor($anchor) {
		global $context;

		// sanity check
		if(!$anchor)
			return NULL;

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(create_date) as oldest_date, MAX(create_date) as newest_date"
			." FROM ".SQL::table_name('decisions')." AS decisions "
			." WHERE decisions.anchor LIKE '".SQL::escape($anchor)."'";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get some statistics on threads
	 *
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see decisions/index.php
	 */
	function &stat_threads() {
		global $context;

		// if not associate, restrict to decisions at public published not expired pages
		if(!Surfer::is_associate())
			$query = "SELECT COUNT(DISTINCT decisions.anchor) as count FROM ".SQL::table_name('articles')." AS articles"
				." LEFT JOIN ".SQL::table_name('decisions')." AS decisions"
				."	ON ((decisions.anchor_type LIKE 'article') AND (decisions.anchor_id = articles.id))"
				." WHERE (articles.active='Y')"
				." AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."'))";

		// the list of decisions
		else
			$query = "SELECT COUNT(DISTINCT decisions.anchor) as count FROM ".SQL::table_name('decisions')." AS decisions ";

		// select among available items
		$output =& SQL::query_first($query);
		return $output;
	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('decisions');

?>