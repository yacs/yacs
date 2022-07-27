<?php
/**
 * the database abstraction layer for enrolment
 *
 * An enrolment is a qualified link between one anchor and some end user. The anchor is identified
 * by its reference, for example 'article:123'. The user is identified either by its id (if
 * this is a registered person) or by his e-mail address (for external visitors).
 *
 * This library is aiming to be used by other scripts and overlays.
 *
 * @see overlays/event.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Enrolments {

	/**
	 * count participants
	 *
	 * @param string to designate the target anchor
	 * @return int number of people enrolled, or 0
	 */
	public static function count_enrolled($reference) {
		global $context;

		// count records
		$query = "SELECT id FROM ".SQL::table_name('enrolments')." WHERE (anchor LIKE '".$reference."')";
		return SQL::query_count($query);

	}

	/**
	 * get enrolment record
	 *
	 * @param string to designate the target anchor
	 * @param int target user, or NULL for current surfer
	 * @return array enrolment attributes, or NULL
	 */
	public static function get_record($reference, $id = NULL) {
		global $context;

		// which surfer?
		if(!$id)
			$id = Surfer::get_id();

		// look for surfer id, if any
		if($id)
			$where = "user_id LIKE '".SQL::escape($id)."'";

		// look for this e-mail address
		elseif(isset($_REQUEST['surfer_address']) && $_REQUEST['surfer_address'])
			$where = "user_email LIKE '".SQL::escape($_REQUEST['surfer_address'])."'";
		elseif($email = Surfer::get_email_address())
			$where = "user_email LIKE '".SQL::escape($email)."'";

		// sanity check
		else
			return NULL;

		// get at least one record
		$query = "SELECT * FROM ".SQL::table_name('enrolments')." WHERE (anchor LIKE '".$reference."') AND ".$where;
		return SQL::query_first($query);

	}

	/**
	 * get a seat if possible
	 *
	 * @param string to designate the target anchor
	 * @param int the initial number of available seats
	 * @return boolean TRUE if you there is enough room, FALSE otherwise
	 */
	public static function get_seat($reference, $offer=20) {
		global $context;

		// number of seats is not really managed
		if(!$offer || ($offer < 3))
			return TRUE;

		// compute the number of confirmed attendees
		$query = "SELECT id FROM ".SQL::table_name('enrolments')." WHERE (anchor LIKE '".$reference."') AND (approved LIKE 'Y')";
		if(($approved = SQL::query_count($query)) && ($approved >= $offer))
			return FALSE;

		// some seats are available
		return TRUE;
	}

	/**
	 * remember that surfer is enrolled in a meeting
	 *
	 * @param string reference of the target page
	 */
	public static function confirm($reference) {
		global $context;

		// sanity check
		if(!$reference)
			return;

		// ensure that the joiner has been enrolled...
		if(!$item = enrolments::get_record($reference)) {

			if(Surfer::get_id()) {

				// fields to save
				$query = array();
				$query[] = "anchor = '".$reference."'";
				$query[] = "approved = 'Y'";
				$query[] = "edit_date = '".SQL::escape(gmdate('%Y-%m-%d %H:%M:%S'))."'";
				$query[] = "user_id = ".SQL::escape(Surfer::get_id());
				$query[] = "user_email = '".SQL::escape(Surfer::get_email_address())."'";

				// insert a new record
				$query = "INSERT INTO ".SQL::table_name('enrolments')." SET ".implode(', ', $query);
				SQL::query($query);

			}

		// each joiner takes one seat
		} else {
			$query = "UPDATE ".SQL::table_name('enrolments')." SET approved = 'Y' WHERE id = ".SQL::escape($item['id']);
			SQL::query($query);
		}
	}

	/**
	 * create tables for enrolment
	 *
	 * @see control/setup.php
	 */
	public static function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['anchor']		= "VARCHAR(64) DEFAULT '' NOT NULL";
		$fields['approved']		= "ENUM('Y', 'N') DEFAULT 'N' NOT NULL";
		$fields['edit_date']	= "DATETIME";
		$fields['user_email']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['user_id']		= "MEDIUMINT UNSIGNED DEFAULT 0 NOT NULL";

		$indexes = array();
		$indexes['PRIMARY KEY'] 		= "(id)";
		$indexes['INDEX anchor']		= "(anchor)";
		$indexes['INDEX user_email']	= "(user_email)";
		$indexes['INDEX user_id']		= "(user_id)";

		return SQL::setup_table('enrolments', $fields, $indexes);
	}

}

?>
