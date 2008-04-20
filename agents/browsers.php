<?php
/**
 * log stats on user agents
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

class Browsers {

	/**
	 * process one single HTTP request
	 *
	 * This script adds a line to agents/visits.txt.
	 * Each line is made of fields separated by tabulations:
	 * - time stamp (year-month-day hour:minutes:seconds)
	 * - the label
	 *
	 * @return void
	 */
	function check_request() {
		global $context;

		// we capture agent information
		if(!isset($_SERVER['HTTP_USER_AGENT']) || !$_SERVER['HTTP_USER_AGENT'])
			return;

		// we only analyze GET requests
		if(!isset($_SERVER['REQUEST_METHOD']) || ($_SERVER['REQUEST_METHOD'] != 'GET'))
			return;

		// ensure we have a valid database resource
		if(!isset($context['connection']) || !$context['connection'])
			return;

		// only on regular operation
		if(!file_exists($context['path_to_root'].'parameters/switch.on'))
			return;

		// the browser
		if(stristr($_SERVER['HTTP_USER_AGENT'], 'MSIE'))
			$browser = 'MSIE';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'Netscape'))
			$browser = 'Netscape';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'Opera'))
			$browser = 'Opera';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'Konqueror'))
			$browser = 'Konqueror';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'WebTV'))
			$browser = 'WebTV';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'Lynx'))
			$browser = 'Lynx';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'Mozilla'))
			$browser = 'Mozilla';

		elseif(is_callable(array('surfer', 'is_crawler')) && Surfer::is_crawler())
			$browser = 'bot';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'Googlebot'))		// Google
			$browser = 'bot';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'Slurp'))			// Inktomi or Yahoo (Slurp)
			$browser = 'bot';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'antibot')) 		// Antibot
			$browser = 'bot';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'Frontier'))		// Userland
			$browser = 'bot';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'ping.blo.gs')) 	// blo.gs
			$browser = 'bot';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'organica'))		// Organica
			$browser = 'bot';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'Blogosphere')) 	// Blogosphere
			$browser = 'bot';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'blogging ecosystem crawler'))	// Blogging ecosystem
			$browser = 'bot';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'WebCrawler'))	// Fast
			$browser = 'bot';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'timboBot'))		// Breaking Blogs (timboBot)
			$browser = 'bot';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'NITLE Blog Spider'))	// NITLE
			$browser = 'bot';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'The World as a Blog')) // The World as a Blog
			$browser = 'bot';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'daypopbot'))		// DayPop
			$browser = 'bot';

		else
			$browser = 'Other';

		// the os
		if(stristr($_SERVER['HTTP_USER_AGENT'], 'Win'))
			$os = 'Windows';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'Mac'))
			$os = 'Mac';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'PPC'))
			$os = 'Mac';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'Linux'))
			$os = 'Linux';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'FreeBSD'))
			$os = 'FreeBSD';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'SunOS'))
			$os = 'SunOS';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'BeOS'))
			$os = 'BeOS';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'AIX'))
			$os = 'AIX';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'HP-UX'))
			$os = 'HP-UX';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'IRIX'))
			$os = 'IRIX';
		elseif(stristr($_SERVER['HTTP_USER_AGENT'], 'OS/2'))
			$os = 'OS/2';
		else
			$os = 'Other';

		// try a single request for speed
		$query = "UPDATE ".SQL::table_name('counters')." SET hits=hits+1"
			." WHERE (type='total' and variable='hits')"
			." OR (variable='$browser' and type='browser') OR (variable='$os' and type='os')";
		if(SQL::query($query) == 3)
			return;

		// we've got a problem, populate the table
		$query = "DELETE FROM ".SQL::table_name('counters');
		SQL::query($query);
		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='total', variable='hits', hits=0";
		SQL::query($query);
		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='browser', variable='MSIE', hits=0";
		SQL::query($query);
		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='browser', variable='Mozilla', hits=0";
		SQL::query($query);
		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='browser', variable='Netscape', hits=0";
		SQL::query($query);
		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='browser', variable='Opera', hits=0";
		SQL::query($query);
		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='browser', variable='Konqueror', hits=0";
		SQL::query($query);
		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='browser', variable='WebTV', hits=0";
		SQL::query($query);
		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='browser', variable='Lynx', hits=0";
		SQL::query($query);
		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='browser', variable='bot', hits=0";
		SQL::query($query);
		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='browser', variable='Other', hits=0";
		SQL::query($query);

		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='os', variable='Windows', hits=0";
		SQL::query($query);
		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='os', variable='Mac', hits=0";
		SQL::query($query);
		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='os', variable='Linux', hits=0";
		SQL::query($query);
		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='os', variable='FreeBSD', hits=0";
		SQL::query($query);
		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='os', variable='SunOS', hits=0";
		SQL::query($query);
		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='os', variable='BeOS', hits=0";
		SQL::query($query);
		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='os', variable='AIX', hits=0";
		SQL::query($query);
		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='os', variable='HP-UX', hits=0";
		SQL::query($query);
		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='os', variable='IRIX', hits=0";
		SQL::query($query);
		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='os', variable='OS/2', hits=0";
		SQL::query($query);
		$query = "INSERT INTO ".SQL::table_name('counters')." SET type='os', variable='Other', hits=0";
		SQL::query($query);
	}

	/**
	 * create tables for counters
	 *
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['type'] 		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['variable'] 	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['hits'] 		= "INT UNSIGNED DEFAULT 0 NOT NULL";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX type']		= "(type)";
		$indexes['INDEX variable']	= "(variable)";

		return SQL::setup_table('counters', $fields, $indexes);
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
		$query = "SELECT COUNT(*) as count FROM ".SQL::table_name('counters');

		$output =& SQL::query_first($query);
		return $output;
	}

}

i18n::bind('agents');
?>