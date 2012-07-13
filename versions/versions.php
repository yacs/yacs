<?php
/**
 * the database abstraction layer for versions
 *
 * Versions is the storage module that is aiming to implement version control and WiKi within YACS.
 *
 * One version is a textual object used as a snapshot of an article, category, or section.
 * Anchors are used to reference versioned item (i.e., 'article:123' or 'section:314').
 * Also, each version has a date of creation, and reference the surfer who generates it.
 *
 * Basically, YACS maintains a stack of versions. New versions are pushed on the top of the stack.
 * When necessary, an old version may be popped from the stack.
 *
 * A version may be created on item change, like in the following example:
 * [php]
 * // save article content before change, and link it to last editor
 * Versions::save($article, 'article:123');
 *
 * // update the article
 * Articles::put($new_content);
 * [/php]
 *
 * The list of versions related to one item may be retrieved as follows:
 * [php]
 * // retrieve the list of versions
 * Versions::list_for_anchor($anchor);
 * [/php]
 *
 * On revert back to a previous version, this version and most recent versions are suppressed from the database:
 * [php]
 * // restore a past version
 * Versions::restore($id);
 * [/php]
 *
 * It is also possible to prune one version, and remove older versions as well:
 * [php]
 * // clean oldies up to a given version
 * Versions::prune($id);
 * [/php]
 *
 * YACS stores the entire content of each version, and this could lead to huge storage needs because of data replication.
 * In order to limit the impact of versioning a daily pruning scheme has been implemented.
 * The basic idea is that it takes some time to achieve significant changes in a page anyway, and one day of work
 * is the right granularity level for this.
 *
 * This means that each time a version is saved in the database, all previous versions for the same day are deleted.
 * If many updates take place during a single day, only the last one from this day can be restored.
 * Or the last version from a previous day can be restored.
 * Incidentally, this daily pruning scheme also protects against potential overflow attacks on the storage space.
 *
 * @author Bernard Paques
 * @author Florent
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Versions {

	/**
	 * ensure that two arrays are different
	 *
	 * @param array initial version of an object
	 * @param array updated version, or supposed so
	 * @param array list of attributes to check
	 * @return boolean TRUE if the arrays are different, FALSE otherwise
	 */
	public static function are_different($previous, $current, $names=NULL) {

		// sanity check
		if(!is_array($names) || !$names)
			$names = array('description', 'extra', 'introduction', 'overlay', 'tags', 'title', 'trailer');

		// check each attribute
		foreach($names as $name) {

			// skip undefined attributes
			if(!isset($previous[ $name ]))
				continue;
			if(!isset($current[ $name ]))
				continue;

			// stop on first difference found
			if(strcmp($previous[ $name ], $current[ $name ]))
				return TRUE;

		}

		// no difference has been found
		return FALSE;
	}
	/**
	 * count available versions
	 *
	 * @param string the selected anchor (e.g., 'section:12')
	 * @return int the number of stored versions, or FALSE on error
	 *
	 * @see articles/view.php
	 * @see sections/view.php
	 */
	public static function count_for_anchor($anchor) {
		global $context;

		// sanity check
		if(!$anchor)
			return NULL;
		$anchor = SQL::escape($anchor);

		// select among available items
		$query = "SELECT COUNT(*) as count"
			." FROM ".SQL::table_name('versions')." AS versions"
			." WHERE (versions.anchor LIKE '".SQL::escape($anchor)."')";
		$output = SQL::query_first($query);

		// package result
		if(isset($output['count']))
			return $output['count'];
		return FALSE;
	}

	/**
	 * delete all versions for a given anchor
	 *
	 * @param the anchor to check
	 *
	 * @see shared/anchors.php
	 */
	public static function delete_for_anchor($anchor) {
		global $context;

		// delete all records attached to this anchor
		$query = "DELETE FROM ".SQL::table_name('versions')
			." WHERE anchor LIKE '".SQL::escape($anchor)."'";
		SQL::query($query);
	}

	/**
	 * get one version by id
	 *
	 * Unserialize $item['content'] to actually retrieve version content.
	 *
	 * @param int the id of the version
	 * @return the resulting $item array, with at least keys: 'id', 'anchor', 'content', etc.
	 */
	public static function &get($id) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// select among available items -- exact match
		$query = "SELECT * FROM ".SQL::table_name('versions')." AS versions"
			." WHERE (versions.id = ".SQL::escape($id).")";
		$item = SQL::query_first($query);

		// inflate the serialized object if necessary
		if(isset($item['content']) && strncmp($item['content'], 'a:', 2) && is_callable('gzuncompress'))
			$item['content'] = gzuncompress(base64_decode($item['content']));

		return $item;
	}

	/**
	 * build a reference to a version
	 *
	 * Depending on parameter '[code]with_friendly_urls[/code]' and on action,
	 * following results can be observed:
	 *
	 * - view - versions/view.php?id=123 or versions/view.php/123 or version-123
	 *
	 * - other - versions/edit.php?id=123 or versions/edit.php/123 or version-edit/123
	 *
	 * @param int the id of the version to handle
	 * @param string the expected action ('view', 'print', 'edit', 'delete', ...)
	 * @return string a normalized reference
	 *
	 * @see control/configure.php
	 */
	public static function get_url($id, $action='view') {
		global $context;

		// list versions -- the id has to be an anchor (e.g., 'article:15')
		if($action == 'list') {
			if($context['with_friendly_urls'] == 'Y')
				return 'versions/list.php/'.str_replace(':', '/', $id);
			else
				return 'versions/list.php?id='.urlencode($id);
		}

		// check the target action
		if(!preg_match('/^(delete|restore|view)$/', $action))
			return 'versions/'.$action.'.php?id='.urlencode($id);

		// normalize the link
		return normalize_url(array('versions', 'version'), $action, $id);
	}

	/**
	 * list most recent versions
	 *
	 * Actually list versions by date.
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see versions/index.php
	 */
	public static function &list_by_date($offset=0, $count=10, $variant='full') {
		global $context;

		// the list of versions
		$query = "SELECT versions.* FROM ".SQL::table_name('versions')." AS versions"
			." ORDER BY versions.edit_date DESC LIMIT ".$offset.','.$count;

		$output =& Versions::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list most recent versions for one anchor
	 *
	 * @param string the target anchor
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see versions/list.php
	 */
	public static function &list_by_date_for_anchor($anchor, $offset=0, $count=10, $variant=NULL) {
		global $context;

		// locate where we are
		if(!isset($variant))
			$variant = $anchor;

		// the list of versions
		$query = "SELECT * FROM ".SQL::table_name('versions')." AS versions"
			." WHERE (anchor LIKE '".SQL::escape($anchor)."')"
			." ORDER BY versions.edit_date DESC LIMIT ".$offset.','.$count;

		$output =& Versions::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list selected versions
	 *
	 * If variant is provided as a string, the functions looks for a script featuring this name.
	 *
	 * @param resource result of database query
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return NULL on error, else an ordered array with $key => ($prefix, $label, $suffix, $type, $icon)
	 *
	 */
	public static function &list_selected($result, $variant='compact') {
		global $context;

		// no result
		if(!$result) {
			$output = NULL;
			return $output;
		}

		// special layouts
		if(is_object($variant)) {
			$output = $variant->layout($result);
			return $output;
		}

		// build an array of links
		switch($variant) {

		case 'compact':
			include_once $context['path_to_root'].'versions/layout_versions_as_compact.php';
			$layout = new Layout_versions_as_compact();
			$output = $layout->layout($result);
			return $output;

		default:
			include_once $context['path_to_root'].'versions/layout_versions.php';
			$layout = new Layout_versions();
			$output = $layout->layout($result);
			return $output;

		}

	}

	/**
	 * restore an old version
	 *
	 * This function returns the content of a previous version,
	 * and also purges the stack of previous versions.
	 *
	 * This function populates the error context, where applicable.
	 *
	 * @param int the id of the version to restore
	 * @return TRUE on success, FALSE otherwise
	 */
	public static function restore($id) {
		global $context;

		// sanity check
		if(!$id)
			return TRUE;

		// select among available items -- exact match
		$query = "SELECT * FROM ".SQL::table_name('versions')." AS versions"
			." WHERE (versions.id = ".SQL::escape($id).")";
		if(!$item = SQL::query_first($query))
			return FALSE;

		// retrieve the related anchor
		$anchor = Anchors::get($item['anchor']);
		if(!is_object($anchor)) {
			Logger::error(sprintf(i18n::s('Unknown anchor %s'), $item['anchor']));
			return FALSE;
		}

		// inflate the serialized object if necessary
		if(strncmp($item['content'], 'a:', 2) && is_callable('gzuncompress'))
			$item['content'] = gzuncompress(base64_decode($item['content']));

		// restore the anchor
		if(!$anchor->restore(Safe::unserialize($item['content']))) {
			Logger::error(i18n::s('Impossible to restore the previous version.'));
			return FALSE;
		}

		// delete records attached to this anchor after version date
		$query = "DELETE FROM ".SQL::table_name('versions')
			." WHERE (anchor LIKE '".SQL::escape($item['anchor'])."')"
			." AND (edit_date >= '".SQL::escape($item['edit_date'])."')";
		SQL::query($query);

		// anchor has been restored
		return TRUE;

	}

	/**
	 * remember a version
	 *
	 * Save previous version of some object in the database.
	 * It is recommended to call Versions::are_different() before calling Versions::save(), to
	 * ensure that something change has taken place.
	 * This function populates the error context, where applicable.
	 *
	 * @param array an array of fields
	 * @param string the anchor attached to this version
	 * @return the id of the new version, or FALSE on error
	 *
	 * @see versions/edit.php
	**/
	public static function save($fields, $anchor) {
		global $context;

		// anchor cannot be empty
		if(!isset($anchor) || !$anchor) {
			Logger::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// pack arrays, etc.
		$content = serialize($fields);

		// save database space
		if((strlen($content) > 128) && is_callable('gzcompress'))
				$content = base64_encode(gzcompress($content, 6));

		// versioning date
		$versioning_date = isset($fields['edit_date']) ? $fields['edit_date'] : gmstrftime('%Y-%m-%d %H:%M:%S');

		// delete previous versions for this day
// 		$query = "DELETE FROM ".SQL::table_name('versions')
// 			." WHERE (anchor LIKE '".SQL::escape($anchor)."') AND (edit_date LIKE '".substr($versioning_date, 0, 10)."%')";
// 		SQL::query($query);

		// insert a new record
		$query = "INSERT INTO ".SQL::table_name('versions')." SET "
			."anchor='".SQL::escape($anchor)."',"
			."content='".SQL::escape($content)."',"
			."edit_name='".SQL::escape(isset($fields['edit_name']) ? $fields['edit_name'] : Surfer::get_name())."', "
			."edit_id=".SQL::escape(isset($fields['edit_id']) ? $fields['edit_id'] : Surfer::get_id()).", "
			."edit_address='".SQL::escape(isset($fields['edit_address']) ? $fields['edit_address'] : Surfer::get_email_address())."', "
			."edit_date='".SQL::escape($versioning_date)."'";

		// actual insert
		if(SQL::query($query) === FALSE)
			return FALSE;

		// remember the id of the new item
		$id = SQL::get_last_id($context['connection']);

		// clear the cache for versions; update section index as well
		Cache::clear(array('articles', 'versions'));

		// return the id of the new item
		return $id;
	}

	/**
	 * create tables for versions
	 */
	public static function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['anchor']		= "VARCHAR(64) NOT NULL";
		$fields['content']		= "MEDIUMTEXT NOT NULL";
		$fields['edit_address'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_date']	= "DATETIME";
		$fields['edit_id']		= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['edit_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX anchor']	= "(anchor)";
		$indexes['INDEX edit_date'] = "(edit_date)";

		return SQL::setup_table('versions', $fields, $indexes);
	}

	/**
	 * get some statistics for one anchor
	 *
	 * @param the selected anchor (e.g., 'section:12')
	 * @return the resulting ($count, $min_date, $max_date) array
	 */
	public static function &stat_for_anchor($anchor) {
		global $context;

		// sanity check
		if(!$anchor)
			return NULL;
		$anchor = SQL::escape($anchor);

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date"
			." FROM ".SQL::table_name('versions')." AS versions"
			." WHERE (versions.anchor LIKE '".SQL::escape($anchor)."')";

		$output = SQL::query_first($query);
		return $output;
	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('versions');

?>