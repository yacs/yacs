<?php
/**
 * the database abstraction layer for actions
 *
 * Actions are like post-it notes, or reminders.
 * They are a very straightforward way of managing short-lived and task-oriented items of information.
 *
 * The life cycle of an action is quite simple. An action is created, then closed.
 * An action record is created when some community member is tasked to do something. Like for paper reminders, you can create actions for yourself or for others.
 * Then actions can be either rejected or completed by the person to which they have been assigned.
 *
 * While being really simplistic, this implementation of actions is quite enough to support following patterns of workflow:
 *
 * [*] [b]sticky notes[/b] - Any member may create, edit, and accept action records for his own eyes.
 * On-going actions are listed at login time, and this proves to be an efficient way to remind things to do.
 * New actions can be created from the user profile. Click on 'My Profile' once authenticated.
 *
 * [*] [b]team working[/b] - Since YACS is intended to support communities, it is likely that several associates will
 * have to collaborate. By posting actions to a peer, any associate may ask another person to contribute explicitly.
 * The tasked person will be warned of the new action by e-mail, and also on the next authentication to the web site.
 *
 * [*] [b]automatic triggers[/b] - Ultimately, actions can be triggered by another YACS module on specific events.
 * For example, you may have created a PHP script that tracks important information.
 * You can use actions to submit the outcome of this script to some human being for further
 * processing.
 *
 * Every action record has following attributes:
 * - an anchor - usually, some user profile
 * - a title and a description
 * - an optional target designation - usually, the URL of another page
 * - a status that can be: 'on-going', 'completed' or 'rejected'
 * - dates of creation and last modification are recorded as well
 *
 * @author Bernard Paques
 * @author Florent
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Actions {

	/**
	 * accept an action
	 *
	 * The new status can be either:
	 * - 'on-going' - the action has been started
	 * - 'completed' - nothing more to do
	 * - 'rejected' - the task has been cancelled for some reason
	 *
	 * The name of the surfer and the modification date is recorded as well.
	 *
	 * @param int the id of the item to accept
	 * @param string the new status
	 *
	 * @see actions/accept.php
	**/
	function accept($id, $status = 'on-going') {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return i18n::s('No item has the provided id.');

		// status
		if($status == 'on-going')
			$status = 'O';
		elseif($status == 'completed')
			$status = 'C';
		elseif($status == 'rejected')
			$status = 'R';
		else {
			return sprintf(i18n::s('Unknown status %s'), $status);
		}

		// set default values
		$fields = array();
		Surfer::check_default_editor($fields);

		// update an existing record
		$query = "UPDATE ".SQL::table_name('actions')." SET "
			." status='".SQL::escape($status)."',"
			." edit_name='".SQL::escape($fields['edit_name'])."',"
			." edit_id=".SQL::escape($fields['edit_id']).","
			." edit_address='".SQL::escape($fields['edit_address'])."',"
			." edit_date='".SQL::escape($fields['edit_date'])."'"
			." WHERE id = ".SQL::escape($id);
		SQL::query($query);
	}

	/**
	 * check if new actions can be added
	 *
	 * This function returns TRUE if actions can be added to some place,
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

		// actions are prevented in item
		if(isset($item['options']) && is_string($item['options']) && preg_match('/\bno_actions\b/i', $item['options']))
			return FALSE;

		// actions are prevented in anchor
		if(is_object($anchor) && is_callable(array($anchor, 'has_option')) && $anchor->has_option('no_actions'))
			return FALSE;

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// submissions have been disallowed
		if(isset($context['users_without_submission']) && ($context['users_without_submission'] == 'Y'))
			return FALSE;

		// only in articles
		if($variant == 'article') {

			// surfer owns this item, or the anchor
			if(Articles::is_owned($anchor, $item))
				return TRUE;

			// surfer is an editor, and the page is not private
			if(isset($item['active']) && ($item['active'] != 'N') && Articles::is_assigned($item['id']))
				return TRUE;

		// only in sections
		} elseif($variant == 'section') {

			// surfer owns this item, or the anchor
			if(Sections::is_owned($anchor, $item, TRUE))
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

		// authenticated members are allowed to add actions
		if(Surfer::is_member())
			return TRUE;

		// container is restricted
		if(isset($item['active']) && ($item['active'] == 'R'))
			return FALSE;
		if(is_object($anchor) && !$anchor->is_public())
			return FALSE;

		// authenticated members and subscribers are allowed to add files
		if(Surfer::is_logged())
			return TRUE;

		// anonymous contributions are allowed for articles
		if($variant == 'article') {
			if(isset($item['options']) && preg_match('/\banonymous_edit\b/i', $item['options']))
				return TRUE;
			if(is_object($anchor) && $anchor->has_option('anonymous_edit'))
				return TRUE;
		}

		// the default is to not allow for new actions
		return FALSE;
	}

	/**
	 * clear cache entries for one item
	 *
	 * @param array item attributes
	 */
	function clear(&$item) {

		// where this item can be displayed
		$topics = array('actions', 'articles', 'users');

		// clear anchor page
		if(isset($item['anchor']))
			$topics[] = $item['anchor'];

		// clear this page
		if(isset($item['id']))
			$topics[] = 'action:'.$item['id'];

		// clear the cache
		Cache::clear($topics);

	}

	/**
	 * delete one action
	 *
	 * @param int the id of the action to delete
	 * @return boolean TRUE on success, FALSE otherwise
	 *
	 * @see actions/delete.php
	 */
	function delete($id) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return FALSE;

		// delete the record in the database
		$query = "DELETE FROM ".SQL::table_name('actions')." WHERE id = ".SQL::escape($id);
		if(SQL::query($query) === FALSE)
			return FALSE;

		// job done
		return TRUE;
	}

	/**
	 * delete all actions for a given anchor
	 *
	 * @param string the anchor to check
	 *
	 * @see shared/anchors.php
	 */
	function delete_for_anchor($anchor) {
		global $context;

		// delete all matching records in the database
		$query = "DELETE FROM ".SQL::table_name('actions')." WHERE anchor LIKE '".SQL::escape($anchor)."'";
		SQL::query($query);
	}

	/**
	 * duplicate all actions for a given anchor
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
		$query = "SELECT * FROM ".SQL::table_name('actions')." WHERE anchor LIKE '".SQL::escape($anchor_from)."'";
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
				if($item['id'] = Actions::post($item)) {

					// more pairs of strings to transcode
					$transcoded[] = array('/\[action='.preg_quote($old_id, '/').'/i', '[action='.$item['id']);

					// duplicate elements related to this item
					Anchors::duplicate_related_to('action:'.$old_id, 'action:'.$item['id']);

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
	 * get one action by id
	 *
	 * @param int the id of the action
	 * @return the resulting $item array, with at least keys: 'id', 'title', etc.
	 */
	function &get($id) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// select among available items -- exact match
		$query = "SELECT * FROM ".SQL::table_name('actions')." AS actions "
			." WHERE (actions.id = ".SQL::escape($id).")";
		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get id of next action
	 *
	 * This function is used to build navigation bars.
	 *
	 * @param array the current item
	 * @param string the anchor of the current item
	 * @param string the order, either 'date' or 'reverse'
	 * @return some text
	 *
	 * @see articles/article.php
	 */
	function get_next_url($item, $anchor, $order='date') {
		global $context;

		// sanity check
		if(!is_array($item))
			return $item;

		// depending on selected sequence
		if($order == 'date') {
			$match = "actions.create_date > '".SQL::escape($item['create_date'])."'";
			$order = 'actions.create_date';
		} elseif($order == 'reverse') {
			$match = "actions.create_date < '".SQL::escape($item['create_date'])."'";
			$order = 'actions.create_date DESC';
		} else
			return "unknown order '".$order."'";


		// query the database
		$query = "SELECT id FROM ".SQL::table_name('actions')." AS actions "
			." WHERE (actions.anchor LIKE '".SQL::escape($anchor)."') AND (".$match.") AND (actions.status='O')"
			." ORDER BY ".$order." LIMIT 0, 1";
		if(!$item =& SQL::query_first($query))
			return NULL;

		// return url of the first item of the list
		return Actions::get_url($item['id']);
	}

	/**
	 * get id of previous action
	 *
	 * This function is used to build navigation bars.
	 *
	 * @param array the current item
	 * @param string the anchor of the current item
	 * @param string the order, either 'date' or 'reverse'
	 * @return some text
	 *
	 * @see articles/article.php
	 */
	function get_previous_url($item, $anchor, $order='date') {
		global $context;

		// sanity check
		if(!is_array($item))
			return $item;

		// depending on selected sequence
		if($order == 'date') {
			$match = "actions.create_date < '".$item['create_date']."'";
			$order = 'actions.create_date DESC';
		} elseif($order == 'reverse') {
			$match = "actions.create_date > '".$item['create_date']."'";
			$order = 'actions.create_date';
		} else
			return "unknown order '".$order."'";

		// query the database
		$query = "SELECT id FROM ".SQL::table_name('actions')." AS actions "
			." WHERE (actions.anchor LIKE '".SQL::escape($anchor)."') AND (".$match.") AND (actions.status='O')"
			." ORDER BY ".$order." LIMIT 0, 1";
		if(!$item =& SQL::query_first($query))
			return NULL;

		// return url of the first item of the list
		return Actions::get_url($item['id']);
	}

	/**
	 * build a reference to an action
	 *
	 * Depending on parameter '[code]with_friendly_urls[/code]' and on action,
	 * following results can be observed:
	 *
	 * - view - actions/view.php?id=123 or actions/view.php/123 or action-123
	 *
	 * - other - actions/edit.php?id=123 or actions/edit.php/123 or action-edit/123
	 *
	 * @param int the id of the action to handle
	 * @param string the expected action ('view', 'edit', 'delete', ...)
	 * @param string additional data, such as page name, if any
	 * @return string a normalized reference
	 *
	 * @see control/configure.php
	 */
	function get_url($id, $action='view', $name=NULL) {
		global $context;

		// the prefix for navigation links --name references the things to page
		if($action == 'navigate') {
			if($context['with_friendly_urls'] == 'Y')
				return 'actions/view.php/'.rawurlencode($id).'/'.rawurlencode($name).'/';
			elseif($context['with_friendly_urls'] == 'R')
				return 'actions/view.php/'.rawurlencode($id).'/'.rawurlencode($name).'/';
			else
				return 'actions/view.php?id='.urlencode($id).'&amp;'.urlencode($name).'=';
		}

		// check the target action
		if(!preg_match('/^(accept|delete|edit|view)$/', $action))
			return 'actions/'.$action.'.php?id='.urlencode($id).'&action='.urlencode($name);

		// normalize the link
		return normalize_url(array('actions', 'action'), $action, $id, $name);
	}

	/**
	 * list newest actions
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_by_date($offset=0, $count=10, $variant='full') {
		global $context;

		// limit the scope of the request
		$query = "SELECT * FROM ".SQL::table_name('actions')." AS actions"
			." ORDER BY actions.edit_date DESC, actions.title LIMIT ".$offset.','.$count;

		// the list of actions
		$output =& Actions::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list newest on-going actions for one anchor
	 *
	 * @param string the anchor (e.g., 'user:123')
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_by_date_for_anchor($anchor, $offset=0, $count=20, $variant=NULL) {
		global $context;

		// use the anchor itself as the default variant
		if(!$variant)
			$variant = $anchor;

		// the request
		$query = "SELECT * FROM ".SQL::table_name('actions')." AS actions"
			." WHERE (actions.anchor LIKE '".SQL::escape($anchor)."') AND (actions.status='O')"
			." ORDER BY actions.create_date LIMIT ".$offset.','.$count;

		// the list of actions
		$output =& Actions::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list newest actions for one author
	 *
	 * Example:
	 * [php]
	 * include_once 'actions/actions.php';
	 * $items = actions::list_by_date_for_author(0, COMPACT_LIST_SIZE, '', 12);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 * [/php]
	 *
	 * @param int the id of the author of the action
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_by_date_for_author($author_id, $offset=0, $count=20, $variant='date') {
		global $context;

		// limit the scope of the request
		$query = "SELECT * FROM ".SQL::table_name('actions')." AS actions "
			." WHERE (actions.edit_id = ".SQL::escape($author_id).") "
			." ORDER BY actions.edit_date DESC, actions.title LIMIT ".$offset.','.$count;

		// the list of actions
		$output =& Actions::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list newest completed actions for one anchor
	 *
	 * @param string the anchor (e.g., 'user:123')
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_completed_for_anchor($anchor, $offset=0, $count=20, $variant=NULL) {
		global $context;

		// use the anchor itself as the default variant
		if(!$variant)
			$variant = $anchor;

		// the request
		$query = "SELECT * FROM ".SQL::table_name('actions')." AS actions"
			." WHERE (actions.anchor LIKE '".SQL::escape($anchor)."') AND (actions.status='C')"
			." ORDER BY actions.edit_date DESC, actions.title LIMIT ".$offset.','.$count;

		// the list of actions
		$output =& Actions::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list newest rejected actions for one anchor
	 *
	 * @param string the anchor (e.g., 'user:123')
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_rejected_for_anchor($anchor, $offset=0, $count=20, $variant=NULL) {
		global $context;

		// use the anchor itself as the default variant
		if(!$variant)
			$variant = $anchor;

		// the request
		$query = "SELECT * FROM ".SQL::table_name('actions')." AS actions"
			." WHERE (actions.anchor LIKE '".SQL::escape($anchor)."') AND (actions.status='R')"
			." ORDER BY actions.edit_date DESC, actions.title LIMIT ".$offset.','.$count;

		// the list of actions
		$output =& Actions::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list selected actions
	 *
	 * If variant is provided as a string, the functions looks for a script featuring this name.
	 * E.g., for variant 'compact', the file 'actions/layout_actions_as_compact.php' is loaded.
	 * If no file matches then the default 'actions/layout_actions.php' script is loaded.
	 *
	 * @param resource result of database query
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_selected(&$result, $variant='compact') {
		global $context;

		// no result
		if(!$result) {
			$output = NULL;
			return $output;
		}

		// special layout
		if(is_object($variant)) {
			$output =& $variant->layout($result);
			return $output;
		}

		// regular layout
		include_once $context['path_to_root'].'actions/layout_actions.php';
		$layout = new Layout_actions();
		$output =& $layout->layout($result);
		return $output;
	}

	/**
	 * post a new action or an updated action
	 *
	 * This function populates the error context, where applicable.
	 *
	 * @param array an array of fields
	 * @return the id of the new action, or FALSE on error
	**/
	function post(&$fields) {
		global $context;

		// no title
		if(!isset($fields['title']) || !trim($fields['title'])) {
			Logger::error(i18n::s('No title has been provided.'));
			return FALSE;
		}

		// no anchor reference
		if(!isset($fields['anchor']) || !trim($fields['anchor'])) {
			Logger::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// set default values
		if(!isset($fields['status']) || !preg_match('/'.preg_quote($fields['status'], '/').'/', 'OCR'))
			$fields['status'] = '0';

		// set default values for this editor
		Surfer::check_default_editor($fields);

		// reinforce date formats
		if(!isset($fields['create_date']) || ($fields['create_date'] <= NULL_DATE))
			$fields['create_date'] = $fields['edit_date'];
		if(!isset($fields['status_date']) || ($fields['status_date'] <= NULL_DATE))
			$fields['status_date'] = $fields['edit_date'];

		// update the existing record
		if(isset($fields['id'])) {

			// id cannot be empty
			if(!$fields['id'] || !is_numeric($fields['id']))
				return FALSE;

			// update the existing record
			$query = "UPDATE ".SQL::table_name('actions')." SET "
				."title='".SQL::escape($fields['title'])."', "
				."description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."', "
				."target_url='".SQL::escape(isset($fields['target_url']) ? $fields['target_url'] : '')."'";
			if($fields['status'])
				$query .= ", status='".SQL::escape($fields['status'])."', "
					."status_date='".SQL::escape($fields['status_date'])."'";

			// maybe a silent update
			if(!isset($fields['silent']) || ($fields['silent'] != 'Y')) {
				$query .= ", "
				."edit_name='".SQL::escape($fields['edit_name'])."', "
				."edit_id=".SQL::escape($fields['edit_id']).", "
				."edit_address='".SQL::escape($fields['edit_address'])."', "
				."edit_date='".SQL::escape($fields['edit_date'])."'";
			}

			$query .= " WHERE id = ".SQL::escape($fields['id']);

		// insert a new record
		} else {

			// always remember the date
			$query = "INSERT INTO ".SQL::table_name('actions')." SET ";
			$query .= "anchor='".SQL::escape($fields['anchor'])."', "
				."title='".SQL::escape($fields['title'])."', "
				."description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."', "
				."status='".SQL::escape($fields['status'])."', "
				."status_date='".SQL::escape($fields['status_date'])."', "
				."target_url='".SQL::escape(isset($fields['target_url']) ? $fields['target_url'] : '')."', "
				."create_name='".SQL::escape(isset($fields['create_name']) ? $fields['create_name'] : $fields['edit_name'])."', "
				."create_id=".SQL::escape(isset($fields['create_id']) ? $fields['create_id'] : $fields['edit_id']).", "
				."create_address='".SQL::escape(isset($fields['create_address']) ? $fields['create_address'] : $fields['edit_address'])."', "
				."create_date='".SQL::escape($fields['create_date'])."', "
				."edit_name='".SQL::escape($fields['edit_name'])."', "
				."edit_id=".SQL::escape($fields['edit_id']).", "
				."edit_address='".SQL::escape($fields['edit_address'])."', "
				."edit_date='".SQL::escape($fields['edit_date'])."'";

		}

		// actual update query
		if(SQL::query($query) === FALSE)
			return FALSE;

		// remember the id of the new item
		if(!isset($fields['id']))
			$fields['id'] = SQL::get_last_id($context['connection']);

		// clear the cache for actions
		Actions::clear($fields);

		// end of job
		return $fields['id'];

	}

	/**
	 * create or alter tables for actions
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['anchor']		= "VARCHAR(64) DEFAULT 'article:1' NOT NULL";
		$fields['title']		= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['target_url']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['status']		= "ENUM('O','C','R') DEFAULT 'O' NOT NULL";
		$fields['status_date']	= "DATETIME";
		$fields['description']	= "TEXT NOT NULL";
		$fields['create_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['create_id']	= "MEDIUMINT UNSIGNED DEFAULT 1 NOT NULL";
		$fields['create_address']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['create_date']	= "DATETIME";
		$fields['edit_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_id']		= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['edit_address'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_date']	= "DATETIME";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX anchor']	= "(anchor)";
		$indexes['INDEX create_date']	= "(create_date)";
		$indexes['INDEX edit_date'] = "(edit_date)";
		$indexes['INDEX edit_id']	= "(edit_id)";
		$indexes['INDEX status']	= "(status)";
		$indexes['INDEX title'] 	= "(title)";
		$indexes['FULLTEXT INDEX']	= "full_text(title, description)";

		return SQL::setup_table('actions', $fields, $indexes);
	}

	/**
	 * get some statistics
	 *
	 * @return the resulting ($count, $min_date, $max_date) array
	 */
	function &stat() {
		global $context;

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date
			FROM ".SQL::table_name('actions')." AS actions";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get some statistics for one anchor
	 *
	 * We are computing this only for completed actions, since on-going and rejected actions
	 * should be kept to a minimum.
	 *
	 * @param the selected anchor (e.g., 'article:12')
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see actions/list.php
	 */
	function &stat_for_anchor($anchor) {
		global $context;

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date"
			." FROM ".SQL::table_name('actions')." AS actions"
			." WHERE (actions.anchor LIKE '".SQL::escape($anchor)."') AND (actions.status='C')";

		$output =& SQL::query_first($query);
		return $output;
	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('actions');

?>