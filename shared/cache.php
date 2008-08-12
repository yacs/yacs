<?php
/**
 * speed up things by caching information
 *
 * @todo add ability to rely on memory cache http://www.rooftopsolutions.nl/article/107
 *
 * The objective of this cache module is to save on database requests and on
 * computing time.
 *
 * Any other module may use it freely by calling member functions as described in the following example.
 *
 * From within the page that has to cache information:
 * [php]
 * // the id for the cache combines the script name, plus unique information from within this script
 * $cache_id = 'my_module/index.php#items_by_date';
 *
 * // retrieve the information from cache if possible
 * if(!$text =& Cache::get($cache_id)) {
 *
 *	 // else build the page dynamically
 *	 $result = Items::list_by_date();
 *	 $text = Skin::build_list($result, 'decorated');
 *
 *	 // cache information for next request
 *	 Cache::put($cache_id, $text, 'items');
 * }
 * [/php]
 *
 * The cache will be killed in case of updates:
 * [php]
 * // post a new item
 * function post($fields) {
 *	 // the UPDATE statement here
 *	 ...
 *
 *	 // kill cached items
 *	 Cache::clear('items');
 * }
 * [/php]
 *
 * Cached items are defined by some id, and by a topic. The id is used to
 * retrieve a cached item from the database. The topic is used to destroy items
 * collectively.
 *
 * Any label may be used for the topic, but some values have a special meaning:
 *
 * - 'article:123', 'user:456', etc. - cached items are related to given anchors
 *
 * - 'files', 'links', etc. - cached items are related to lists of records
 *
 * - 'global' - an item that cannot be related to one record nor to one table
 * of the database - this is also the default topic value
 *
 * - 'stable' - an item that should not be deleted, except on full cache clear
 *
 * Cached items may add a lot of burden to the database engine, and several
 * features have been put in place to improve on scalability and efficiency.
 *
 * A period of 20 seconds is allowed to preserve transient items.
 *
 * Transient items are those with a reduced life time, and that will
 * expire shortly anyway. Preserving these items is efficient because it saves
 * on database requests to re-create new cache entries, etc.
 *
 * For example, when items related to 'article:123' are deleted, all 'global'
 * items will be deleted as well, except those that will expire within the next
 * 20 seconds.
 *
 * For this to work properly on very busy servers the caching duration used
 * for 'global' items has to not go beyond the transient period. Else these
 * cached entries would be deleted just after their creation, which would impact
 * badly the overall efficiency of the cache.
 *
 * The transient period has been set to 20 seconds because this value
 * minimizes the risk to frustrate end users because of non-updated pages.
 * Most of the time, reloading a page after half-a-minute is enough to fix
 * any content discrepency.
 *
 * The caching duration is limited to 20 minutes for ordinary items. This can
 * be extended for specific items while calling Cache::put(), if necessary.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Cache {

	/**
	 * suppress some content from the cache
	 *
	 * This function clears all records directly related to the provided topic(s).
	 * Other items can be suppressed indirectly as well.
	 *
	 * If no topic is provided the full cache is cleared.
	 *
	 * If the topic 'global' is provided, all 'global' items are suppressed,
	 * but other transient and stable items are preserved.
	 *
	 * When a target topic is provided (e.g., 'article:123' or 'files'), related
	 * items are suppressed, along with non-transient 'global' items.
	 *
	 * Transient items are those with a reduced life time, and that will
	 * expire shortly anyway (i.e., within the next 20 seconds.)
	 *
	 * Stable items are those with the topic 'stable', and that will be preserved
	 * until their end of life, except on full cache clear.
	 *
	 * @param mixed the topic(s) to be deleted; if NULL, clear all cache entries
	 */
	function clear($topic=NULL) {
		global $context;

		// always disable cache when server is not switched on
		if(!file_exists($context['path_to_root'].'parameters/switch.on'))
			return;

		// the sql back-end may be not available during software updates or on NO_MODEL_PRELOAD
		if(!is_callable(array('SQL', 'query')))
			return;

		// clear the entire cache
		if(!$topic)
			$query = "DELETE FROM ".SQL::table_name('cache');

		// clear everything, except transient and stable items.
		elseif(is_string($topic) && ($topic == 'global')) {

			// clear expired items
			$where = "(expiry_date < '".gmstrftime('%Y-%m-%d %H:%M:%S')."')";

			// clear global items
			$where .= " OR (topic LIKE 'global')";

			// clear not-stable non-transient items
			$where .= " OR ((topic NOT LIKE 'stable') AND (expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S', time() + 20)."'))";

			// the comprehensive query
			$query = "DELETE FROM ".SQL::table_name('cache')." WHERE ".$where;

		// clear only part of the cache
		} else {

			// clear expired items
			$where = "(expiry_date < '".gmstrftime('%Y-%m-%d %H:%M:%S')."')";

			// clear non-transient global items
			$where .= " OR ((topic LIKE 'global') AND (expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S', time() + 20)."'))";

			// if several topics have been given, delete all of them
			if(is_array($topic)) {
				foreach($topic as $item) {
					if(is_string($item) && $item)
						$where .= " OR (topic LIKE '".SQL::escape($item)."')";
				}

			// if a topic has been provided, delete related records
			} elseif(is_string($topic) && $topic)
				$where .= " OR (topic LIKE '".SQL::escape($topic)."')";

			// the comprehensive query
			$query = "DELETE FROM ".SQL::table_name('cache')." WHERE ".$where;

		}

		// do the job
		SQL::query($query, TRUE);

	}

	/**
	 * retrieve cached information
	 *
	 * @param string the id of the text to be retrieved
	 * @return string cached information, or NULL if the no accurate information is available for this id
	 */
	function &get($id) {
		global $context;

		// return by reference
		$output = NULL;

		// recover from previous poisoining, if any
		$context['cache_has_been_poisoned'] = FALSE;

		// always disable cache when server is not switched on
		if(!file_exists($context['path_to_root'].'parameters/switch.on'))
			return $output;

		// the sql back-end may be not available during software updates or on NO_MODEL_PRELOAD
		if(!is_callable(array('SQL', 'query')))
			return $output;

		// maybe we don't have to cache
		if(isset($context['without_cache']) && ($context['without_cache'] == 'Y'))
			return $output;

		// sanity check
		if(!$id)
			return $output;

		// cached content depends on surfer capability
		$id .= '/'.Surfer::get_capability();

		// cached content depends on selected language
		$id .= '/'.$context['language'];

		// cached content depends on time offset
		$id .= '/'.Surfer::get_gmt_offset();

		// select among available items -- exact match
		$query = "SELECT * FROM ".SQL::table_name('cache')." AS cache"
			." WHERE (cache.id LIKE '".SQL::escape($id)."')";

		// do not report on error
		if(!$item =& SQL::query_first($query, TRUE))
			return $output;

		// check item validity
		if($item['expiry_date'] < gmstrftime('%Y-%m-%d %H:%M:%S'))
			return $output;

		// we have a valid cached item
		$output = $item['text'];
		return $output;
	}

	/**
	 * build a temporary file name
	 *
	 * This function helps to turn the directory of temporary files to a flat
	 * space. Files created there can be deleted using Cache::purge().
	 *
	 * @param string target file path and name
	 * @return string a suitable name for the temporary directory, or NULL
	 */
	function &hash($id) {
		global $context;

		$output = NULL;
		if($id)
			$output = $context['path_to_root'].'temporary/cache_'.str_replace(array('/', '\\', '#'), '_', $id);
		return $output;
	}

	/**
	 * poison the cache
	 *
	 * Call this function when some generated content is specific to one surfer.
	 */
	function poison() {
		global $context;
		$context['cache_has_been_poisoned'] = TRUE;
	}

	/**
	 * purge some temporary files
	 *
	 * This function works in conjunction with Cache::hash().
	 *
	 * @param string extension of files to purge
	 */
	function purge($type='js') {
		global $context;

		if($items=Safe::glob($context['path_to_root'].'temporary/cache_*.'.$type)) {
			foreach($items as $name)
				Safe::unlink($name);
		}
	}

	/**
	 * put something into the cache
	 *
	 * You can store an array or another kind of structured object, but after
	 * serialization.
	 *
	 * The default caching period is 20 minutes (actually, 20m*60s = 1,200s)
	 *
	 * @param string the id of this item
	 * @param string the content to store
	 * @param string the topic related to this item
	 * @param int the maximum time before expiration, in seconds
	 */
	function put($id, &$text, $topic='global', $duration=1200) {
		global $context;

		// maybe we don't have to cache
		if(isset($context['without_cache']) && ($context['without_cache'] == 'Y'))
			return;

		// cache has been poisoned
		if(isset($context['cache_has_been_poisoned']) && $context['cache_has_been_poisoned'])
			return;

		// the sql back-end may be not available during software updates or on NO_MODEL_PRELOAD
		if(!is_callable(array('SQL', 'query')))
			return;

		// cached content depends on surfer capability
		$id .= '/'.Surfer::get_capability();

		// cached content depends on selected language
		$id .= '/'.$context['language'];

		// cached content depends on time offset
		$id .= '/'.Surfer::get_gmt_offset();

		// don't cache more than expected
		$expiry = gmstrftime('%Y-%m-%d %H:%M:%S', time() + $duration);

		// cache also empty content
		if(!$text)
			$text = ' ';

		// update the database; do not report on error
		$query = "REPLACE INTO ".SQL::table_name('cache')." SET"
			." id='".SQL::escape($id)."',"
			." text='".SQL::escape($text)."',"
			." topic='".SQL::escape($topic)."',"
			." expiry_date='".$expiry."',"
			." edit_date='".gmstrftime('%Y-%m-%d %H:%M:%S')."'";
		SQL::query($query, TRUE);
	}

	/**
	 * create tables for the cache
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "VARCHAR(255) DEFAULT '' NOT NULL";		// up to 255 chars
		$fields['text'] 		= "MEDIUMTEXT NOT NULL";					// up to 16M chars
		$fields['topic']		= "VARCHAR(64) DEFAULT '' NOT NULL";		// up to 64 chars
		$fields['edit_date']	= "DATETIME";								// modification date
		$fields['expiry_date']	= "DATETIME";								// expiry date

		$indexes = array();
		$indexes['PRIMARY KEY'] 		= "(id)";
		$indexes['INDEX topic'] 		= "(topic)";
		$indexes['INDEX edit_date'] 	= "(edit_date)";
		$indexes['INDEX expiry_date']	= "(expiry_date)";

		return SQL::setup_table('cache', $fields, $indexes);
	}
}

?>