<?php
/**
 * speed up things by caching information
 * 
 * The objective of this cache module is to save on database requests and on
 * computing time.
 * 
 * The standard mechanism will relay on database (a table named "cache") but
 * if memcached service is available and configured, it will be used.
 *
 * Any other module may use it freely by calling member functions as described in the following example.
 *
 * From within the page that has to cache information:
 * [php]
 * // the id for the cache combines the script name, plus unique information from within this script
 * $cache_id = 'my_module/index.php#items_by_date';
 *
 * // retrieve the information from cache if possible
 * if(!$text = Cache::get($cache_id)) {
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
 * When using database, a period of 20 seconds is allowed to preserve transient items.
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
 * @author Alexis Raimbault
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
	public static function clear($topic=NULL) {
		global $context, $ram;

		// always disable cache when server is not switched on
		if(!file_exists($context['path_to_root'].'parameters/switch.on'))
			return;

		if($ram) {
                    Cache::clear_in_ram($topic);
                } else {
                    Cache::clear_in_db($topic);
                }
                    

	}
        
        private static function clear_in_db($topic) {
            
                // the sql back-end may be not available during software updates or on NO_MODEL_PRELOAD
		if(!is_callable(array('SQL', 'query')))
			return;
                
                // clear the entire cache
		if(!$topic)
			$query = "DELETE FROM ".SQL::table_name('cache');

		// clear everything, except transient and stable items.
		elseif(is_string($topic) && ($topic === 'global')) {

			// clear expired items
			$where = "(expiry_date < '".gmdate('Y-m-d H:i:s')."')";

			// clear global items
			$where .= " OR (topic LIKE 'global')";

			// clear not-stable non-transient items
			$where .= " OR ((topic NOT LIKE 'stable') AND (expiry_date > '".gmdate('Y-m-d H:i:s', time() + 20)."'))";

			// the comprehensive query
			$query = "DELETE FROM ".SQL::table_name('cache')." WHERE ".$where;

		// clear only part of the cache
		} else {

			// clear expired items
			$where = "(expiry_date < '".gmdate('Y-m-d H:i:s')."')";

			// clear non-transient global items
			$where .= " OR ((topic LIKE 'global') AND (expiry_date > '".gmdate('Y-m-d H:i:s', time() + 20)."'))";

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
        
        private static function clear_in_ram($topic) {
            global $ram;
            
            // clear the entire cache
            if(!$topic) {
                $ram->flush();
                
            // clear everything, except stable items.    
            } elseif(is_string ($topic) && $topic === 'global') {
                
                // get the list of known topics (array)
                if($topics = $ram->get('yacs_topics')) {

                    Cache::ram_clear($topics);
                    
                } else {
                    // at least clear global topic
                   Cache::ram_clear('global'); 
                }
                
            // clear only part of the cache    
            } else {
                Cache::ram_clear($topic);
                
                // clear also global topic
                Cache::ram_clear('global');
            }
            
        }

	/**
	 * retrieve cached information
	 *
	 * @param string the id of the text to be retrieved
	 * @return string cached information, or NULL if the no accurate information is available for this id
	 */
	public static function get($id, $f_capa=true, $f_lang=true,$f_gmt_off=true) {
		global $context, $ram;

		// return by reference
		$output = NULL;

		// recover from previous poisoining, if any
		$context['cache_has_been_poisoned'] = FALSE;

		// always disable cache when server is not switched on
		if(!file_exists($context['path_to_root'].'parameters/switch.on'))
			return $output;

		// maybe we don't have to cache
		if(isset($context['without_cache']) && ($context['without_cache'] == 'Y'))
			return $output;

		// sanity check
		if(!$id)
			return $output;

		// cached content depends on surfer capability
                if($f_capa)
                    $id .= '/'.Surfer::get_capability();

		// cached content depends on selected language
                if($f_lang)
                    $id .= '/'.$context['language'];

		// cached content depends on time offset
                if($f_gmt_off)
                    $id .= '/'.Surfer::get_gmt_offset();

		// get it form RAM or DB
                $output = ($ram)?Cache::get_from_ram($id):Cache::get_from_db($id);
                  
		return $output;
	}
        
        /**
         * Retrieve a recorded value in database
         * 
         * @param string $id
         * @return string value of recorded value
         */
        private static function get_from_db($id) {
            
                $output = NULL;
            
                // the sql back-end may be not available during software updates or on NO_MODEL_PRELOAD
                if(!is_callable(array('SQL', 'query')))
                        return $output;

                // select among available items -- exact match
		$query = "SELECT * FROM ".SQL::table_name('cache')." AS cache"
			." WHERE (cache.id LIKE '".SQL::escape($id)."')";

		// do not report on error
		if(!$item = SQL::query_first($query, TRUE))
			return $output;

		// check item validity
		if($item['expiry_date'] < gmdate('Y-m-d H:i:s'))
			return $output;

		// we have a valid cached item
		$output = $item['text'];
            
                return $output;       
        }
        
        /**
         * Retrieve a recorded value in ram storage service
         * This function needs three get operations 
         * because the "topic" of the value is encoded within
         * its id.
         * 
         * @global memcached $ram
         * @param string $id
         * @return mixed cached value
         */
        private static function get_from_ram($id) {
                global $ram;
            
                $output = NULL;
                
                // retrieve topic in reverse record
                if(!$topic = $ram->get('rev::'.$id)) 
                        return $output; // nothing found
                
                // get topic key increment
                if(!$key = $ram->get($topic))
                        return $output; // nothing found
                
                // get value with id prefixed by topic
                $output = $ram->get($topic.'%'.$key.'::'.$id);
                
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
	public static function hash($id) {

		$output = NULL;
		if($id)
			$output = 'temporary/cache_'.str_replace(array(':', '/', '\\', '#'), '_', $id);
		return $output;
	}

	/**
	 * poison the cache
	 *
	 * Call this function when some generated content is specific to one surfer.
	 */
	public static function poison() {
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
	public static function purge($type='*') {
		global $context;

		// delete files cached by yacs
		if($items=Safe::glob($context['path_to_root'].'temporary/cache_*.'.$type)) {
			foreach($items as $name)
				Safe::unlink($name);
		}

		// also delete files cached by SimplePie
		if($items=Safe::glob($context['path_to_root'].'temporary/*.spc')) {
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
	public static function put($id, &$text, $topic='global', $duration=1200, $f_capa=true, $f_lang=true,$f_gmt_off=true) {
		global $context, $ram;
                
                // server must be on
                if(!$context->has('server_on'))
                        return;

		// maybe we don't have to cache
		if(isset($context['without_cache']) && ($context['without_cache'] == 'Y'))
			return;

		// cache has been poisoned
		if(isset($context['cache_has_been_poisoned']) && $context['cache_has_been_poisoned'])
			return;

		// cached content depends on surfer capability
                if($f_capa)
                    $id .= '/'.Surfer::get_capability();

		// cached content depends on selected language
                if($f_lang)
                    $id .= '/'.$context['language'];

		// cached content depends on time offset
                if($f_gmt_off)
                    $id .= '/'.Surfer::get_gmt_offset();

		// cache also empty content
		if(!$text)
			$text = ' ';
                
                
                // put the value
                if($ram) {
                    Cache::put_in_ram($id, $text, $topic, $duration);
                } else {
                    Cache::put_in_db($id, $text, $topic, $duration);
                }

		
	}
        
        /**
         * Store a value in database cache table
         * 
         * @param string $id
         * @param string $text
         * @param strnig $topic
         * @param int $duration
         */
        private static function put_in_db($id, $text, $topic, $duration) {
                
                // the sql back-end may be not available during software updates or on NO_MODEL_PRELOAD
		if(!is_callable(array('SQL', 'query')))
			return;
                
                // don't cache more than expected
		$expiry = gmdate('Y-m-d H:i:s', time() + $duration);
                
                // update the database; do not report on error
		$query = "REPLACE INTO ".SQL::table_name('cache')." SET"
			." id='".SQL::escape($id)."',"
			." text='".SQL::escape($text)."',"
			." topic='".SQL::escape($topic)."',"
			." expiry_date='".$expiry."',"
			." edit_date='".gmdate('Y-m-d H:i:s')."'";
		SQL::query($query, TRUE);
            
        }
        
        /**
         * Store a value in ram storage.
         * We also store a reverse record $id=>$topic
         * in order to be able to retrieve the topic later
         * 
         * @global memcached $ram
         * @param string $id
         * @param string $text
         * @param string $topic
         * @param string $duration
         */
        private static function put_in_ram($id, $text, $topic, $duration) {
                global $ram;
                
                // get a topic/key combination
                $topickey = Cache::ram_ctopic($topic);
                
                // ensure duration is not over 30 days
                $duration = min($duration, 2592000);
                
                // set the value and a reverse record of the topic
                $ram->setMulti(array(
                    $topickey.'::'.$id  => $text,
                    'rev::'.$id         => $topic
                ), $duration);
        }
        
        /**
         * initialize a interface to memcached, if available
         * for a RAM storage
         * 
         * @return \Memcached
         */
        public static function ram_init() {
                
                $mem = null;
            
                if(class_exists('Memcached')){
                   
                    $mem = new Memcached();
                    // local server only and default port
                    $op = $mem->addServer("127.0.0.1", 11211);
                    if(!$op) return null;
                }
                
                // return interface to memcached
                return $mem;
        }
        
        /**
         * Clear a topic in ram storage.
         * Than means incrementing the key
         * associated with the topic so the 
         * values stored with the former 
         * topic/key combination become expired
         * 
         * @global memcached $ram
         * @param mixed $topics (string or array)
         */
        public static function ram_clear($topics) {
                global $ram;
                        
                // we need a array
                if(is_string($topics))
                    $topics = array($topics);
                
                // increment the key value if exist
                foreach($topics as $t) {
                    $ram->increment($t);
                }
        }
        
        
        /**
         * Retrieve or create a topic in ram storage.
         * As memcached does not handle topic naturaly,
         * we simulate them by storing a random key.
         * when we want to clear a topic, the key will be
         * incremented.
         * 
         * @see ram_clear
         * 
         * @global memcached $ram
         * @param string $topic
         * @return string
         */
        public static function ram_ctopic($topic) {
                global $ram;  
                
                $topic_key = $topic;
            
                // check if key exist
                if(!$key = $ram->get($topic)) {
                    // random new
                    $key = rand(1, 10000);
                    // create the topic, with no expiration
                    $ram->set($topic, $key, 0);
                    // memorise the topics list, except "stable"
                    // in order to be able to clear them with global keyword
                    if($topic !== 'stable') {
                        $yacs_topics = $ram->get('yacs_topics');
                        if(!$yacs_topics) $yacs_topics = array();
                        $yacs_topics[] = $topic;
                        $ram->set('yacs_topics', $yacs_topics, 0);
                    }
                }
                
                $topic_key .= '%'.$key;
                
                return $topic_key;
        }

	/**
	 * create tables for the cache
	 */
	public static function setup() {

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

// the global interface to memcached.
// Could be used by any script of your own
global $ram;
$ram = Cache::ram_init();
