<?php
/**
 * format dates in ICS format
 *
 * @see dates/dates.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_dates_as_ics extends Layout_interface {

	/**
	 * list dates
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function layout($result) {
		global $context;

		// build the calendar
		$text = 'BEGIN:VCALENDAR'.CRLF
			.'VERSION:2.0'.CRLF
			.'PRODID:YACS'.CRLF
			.'METHOD:PUBLISH'.CRLF;

		// organization, if any
		if(isset($context['site_name']) && $context['site_name'])
			$text .= 'X-WR-CALNAME:'.$context['site_name'].CRLF;

		// process all items in the list
		while($item = SQL::fetch($result)) {

			// one event at a time
			$text .= 'BEGIN:VEVENT'.CRLF;

			// the event spans limited time
			if(isset($item['duration']) && $item['duration']) {
				$text .= 'DTSTART:'.gmdate('Ymd\THis\Z', SQL::strtotime($item['date_stamp'])).CRLF;
				$text .= 'DTEND:'.gmdate('Ymd\THis\Z', SQL::strtotime($item['date_stamp'])+($item['duration'])*60).CRLF;

			// a full-day event
			} else {
				$text .= 'DTSTART;VALUE=DATE:'.date('Ymd', SQL::strtotime($item['date_stamp'])).CRLF;
				$text .= 'DTEND;VALUE=DATE:'.date('Ymd', SQL::strtotime($item['date_stamp'])+86400).CRLF;
			}

			// url to view the date
			$text .= 'URL:'.$context['url_to_home'].$context['url_to_root'].Articles::get_permalink($item).CRLF;;

			// organization, if any
			if(isset($item['introduction']) && $item['introduction'])
				$text .= 'DESCRIPTION:'.str_replace(array("\n", "\r"), ' ', strip_tags($item['introduction'])).CRLF;

			// build a valid title
			if(isset($item['title']) && $item['title'])
				$text .= 'SUMMARY:'.Codes::beautify_title($item['title']).CRLF;

			// required by Outlook 2003
			if(isset($item['id']) && $item['id'])
				$text .= 'UID:'.$item['id'].CRLF;

			// date of creation
			if(isset($item['create_date']) && $item['create_date'])
				$text .= 'CREATED:'.gmdate('Ymd\THis\Z', SQL::strtotime($item['create_date'])).CRLF;

			// date of last modification
			if(isset($item['edit_date']) && $item['edit_date'])
				$text .= 'DTSTAMP:'.gmdate('Ymd\THis\Z', SQL::strtotime($item['edit_date'])).CRLF;

			// next event
			$text .= 'SEQUENCE:0'.CRLF
				.'END:VEVENT'.CRLF;

		}

		// date of last update
		$text .= 'END:VCALENDAR'.CRLF;

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>