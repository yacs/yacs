<?php
/**
 * profile requests
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

class Profiles {

	/**
	 * process one single HTTP request
	 *
	 * @return void
	 */
	function check_request() {
		global $context;

		// ensure we know where we are
		if(!isset($context['script_url']) || !$context['script_url'])
			return;

		// don't bother with HEAD requests
		if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
			return;

		// ensure we have a valid database resource
		if(!isset($context['connection']) || !$context['connection'])
			return;

		// only on regular operation
		if(!file_exists($context['path_to_root'].'parameters/switch.on'))
			return;

		// script used for this request
		$url = $context['script_url'];

		// execution time up to now
		$execution_time = get_micro_time() - $context['start_time'];

		// if a record exists for this url
		$query = "SELECT * FROM ".SQL::table_name('profiles')." AS profiles WHERE profiles.url = '$url'";
		$item =& SQL::query_first($query);

		// update figures
		if($item['id']) {
			$query = "UPDATE ".SQL::table_name('profiles')." SET "
				."total_hits='".($item['total_hits']+1)."', "
				."total_time='".($item['total_time']+$execution_time)."', "
				."minimum_time='".min($item['minimum_time'], $execution_time)."', "
				."maximum_time='".max($item['maximum_time'], $execution_time)."' "
				." WHERE id = ".$item['id'];
		} else {
			$query = "INSERT INTO ".SQL::table_name('profiles')." SET "
				."url='".$url."', "
				."total_hits='1', "
				."total_time='".$execution_time."', "
				."minimum_time='".$execution_time."', "
				."maximum_time='".$execution_time."'";
		}

		SQL::query($query);
	}

	/**
	 * list profiles
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1 - default is 0
	 * @param int the number of items to display - default is 10
	 */
	function list_by_hits($offset=0, $count=10) {
		global $context;

		// the list of profiles
		$query = "SELECT * FROM ".SQL::table_name('profiles')." "
			."ORDER BY total_hits DESC LIMIT ".$offset.', '.$count;
		if($result =& SQL::query($query))
			while($row =& SQL::fetch($result))
				$rows[] = array('left='.$row['url'], 'left='.$row['total_hits'], 'left='.round($row['total_time']/$row['total_hits'], 3), 'left='.$row['minimum_time'], 'left='.$row['maximum_time']);

		return $rows;
	}

	/**
	 * create tables for profiles
	 *
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['url']			= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars
		$fields['total_hits']	= "INT UNSIGNED DEFAULT '0' NOT NULL";
		$fields['total_time']	= "DOUBLE UNSIGNED";
		$fields['minimum_time'] = "FLOAT UNSIGNED";
		$fields['maximum_time'] = "FLOAT UNSIGNED";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX url']		= "(url)";
		$indexes['INDEX hits']		= "(total_hits)";

		return SQL::setup_table('profiles', $fields, $indexes);
	}

	/**
	 * get some statistics
	 *
	 * @return the number of rows in table
	 *
	 * @see control/index.php
	 */
	function &stat() {
		global $context;

		// select among available items
		$query = "SELECT COUNT(*) as count FROM ".SQL::table_name('profiles');

		$output =& SQL::query_first($query);
		return $output;
	}

}
?>