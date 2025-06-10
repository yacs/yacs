<?php
/**
 * the database abstraction layer for dates
 *
 * Dates are an abstraction for time-related information.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

/** 
 * different first day of week among countries, 
 * @see i18n/i18n.php
 */

if(!defined('WEEK_START_MONDAY')) // @see i18n
    define('WEEK_START_MONDAY',TRUE); 

define('W_START_DAY',((WEEK_START_MONDAY)?1:0));
define('W_END_DAY',((WEEK_START_MONDAY)?8:7));


Class Dates {

	/**
	 * start a calendar for one month
	 */
	public static function build_month_prefix($year, $month, $day, $style="month calendar", $caption=NULL, $with_headers=TRUE) {
		global $context;

		// cache labels for days
		static $days;
		if(!isset($days)) {
			$days = array();
			$days[0] = i18n::s('sunday');
			$days[1] = i18n::s('monday');
			$days[2] = i18n::s('tuesday');
			$days[3] = i18n::s('wednesday');
			$days[4] = i18n::s('thursday');
			$days[5] = i18n::s('friday');
			$days[6] = i18n::s('saturday');
			
			// change day order according to ISO-8601 if required
			if(WEEK_START_MONDAY) 
			    $days[] = array_shift($days);  
		}
		

		// one table per month
		$text = '<table class="'.$style.'">';

		// add caption
		if($caption)
			$text .= '<caption>'.$caption.'</caption>';

		// display labels for days
		if($with_headers) {
			$text .= '<tr>';
			foreach($days as $index => $label)
				$text .= '<th>'.ucfirst($label).'</th>';
			$text .= '</tr>';
		}

		// first week
		$text .= '<tr>';

		// first day of this month
		$first_of_month = gmmktime(0, 0, 0, $month, 1, $year);

		// day in week for the first day of the month
                $day = (int)gmdate('w', $first_of_month);
                if(WEEK_START_MONDAY && $day===0) $day=7;
                
		// draw empty cells at the beginning of the month
		for($index = W_START_DAY; $index < $day; $index++)
			$text .= '<td>&nbsp;</td>';

		// job done
		return $text;
	}

	/**
	 * end of one month
	 */
	public static function build_month_suffix($year, $month, $day) {
		global $context;

		$text = '';

		// number of days in this month
		$days_in_month = (int)gmdate('t', gmmktime(0, 0, 0, $month, 1, $year));

		// day in week for the current date
                $day_in_week = (int)gmdate('w', gmmktime(0, 0, 0, $month, $day, $year));
                if(WEEK_START_MONDAY && $day_in_week===0) $day_in_week=7;

		// start a new week on next row
		if(($day_in_week == W_START_DAY) && ($day <= $days_in_month))
			$text .= '</tr><tr>';

		// complement empty days for this month
		for(; $day <= $days_in_month; $day++) {
			$text .= '<td>'.$day.'</td>';

			// start a new week on next row
			if(++$day_in_week >= W_END_DAY) {
				$day_in_week = W_START_DAY;
				$text .= '</tr><tr>';
			}

		}

		// draw empty cells at the end of the month
		if($day_in_week > W_START_DAY) {
			while($day_in_week++ < W_END_DAY)
				$text .= '<td>&nbsp;</td>';
		}

		// close the last week
		$text .= '</tr></table>';

		// done
		return $text;
	}

	public static function build_day($day, $content, $panel_id, $compact) {
		global $context;

		// content of each day
		static $day_content_index;
		if(!isset($day_content_index))
			$day_content_index = 1;

		// a compact list of items for the day
		if($compact)
			$content = Skin::finalize_list($content, 'compact');
		else
			$content = join(BR, $content);

		if($compact) {
			$id = 'day_content_'.$day_content_index++;

			$text = '<a href="#" onclick="$(\'#'.$panel_id.'\').html($(\'#'.$id.'\').html()).effect(\'highlight\', {}, 3000); return false;">'.$day.'</a>'
				.'<div id="'.$id.'" style="display: none">'.$content.'</div>';

		} else
			$text = $day.BR.$content;

		return $text;
	}

	/**
	 * produce monthly views for provided items
	 *
	 * @parameter array of $url => ($prefix, $label, $suffix, $type, $icon, $date)
	 * @parameter boolean if TRUE, add links to yearly and monthly views
	 * @parameter boolean if FALSE, do no display month caption
	 * @parameter boolean if FALSE, do not label days in week
	 * @parameter boolean if TRUE, display day content below the calendar
	 * @parameter int forced year, in case no dates are provided
	 * @parameter int forced month, in case no dates are provided
	 * @return a string to be put in the web page
	 */
	public static function build_months($dates, $with_zoom=FALSE, $with_caption=TRUE, $with_headers=TRUE, $compact=FALSE, $forced_year=NULL, $forced_month=NULL, $style="month calendar") {
		global $context;

		// we return some text
		$text = '';

		// nothing done yet
		$current_year = $current_month = $current_day = NULL;
		$day_content = array();

		// day details
		static $day_panel_index;
		if(!isset($day_panel_index))
			$day_panel_index = 1;
		$day_panel_id = 'day_panel_'.$day_panel_index++;

		// process all dates
		foreach($dates as $date_link => $date_attributes) {

			// look at this date
			list($prefix, $label, $suffix, $type, $icon, $date) = $date_attributes;
			$year = intval(substr($date, 0, 4));
			$month = intval(substr($date, 5, 2));
			$day = intval(substr($date, 8, 2));

			// flush previous day, if any
			if($day_content && (($day != $current_day) || ($month != $current_month) || ($year != $current_year))) {

				$text .= '<td class="spot">'.Dates::build_day($current_day, $day_content, $day_panel_id, $compact).'</td>';
				$current_day++;
				if(++$day_in_week >= W_END_DAY) {
					$day_in_week = W_START_DAY;
					$text .= '</tr><tr>';
				}
				$day_content = array();
			}

			// use the image as a link to the target page
			if($icon) {

				// fix relative path
				if(!preg_match('/^(\/|http:|https:|ftp:)/', $icon))
					$icon = $context['url_to_root'].$icon;

				// build the complete HTML element
				$icon = Skin::build_link($date_link, '<img src="'.$icon.'" alt="" title="'.encode_field($label).'" />', 'overlaid').BR;
			}


			// content for this date
			$day_content[] = $icon.$prefix.Skin::build_link($date_link, $label, 'overlaid').$suffix;

			// close current month
			if($current_month && ($month != $current_month))
				$text .= Dates::build_month_suffix($current_year, $current_month, $current_day);

			// move to month for this date
			while((!$current_year && !$current_month && !$current_day) || ($month != $current_month)) {

				if(!$current_month) {
					$current_year = $year;
					$current_month = $month;
				} else {
					if(++$current_month > 12) {
						$current_year++;
						$current_month = 1;
					}
				}

				// add a caption
				$title = '';
				if($with_caption) {

					// month title
					$title = ucfirst(Dates::get_month_label($current_month)).' '.$current_year;

					// zoom to the monthly view
					if($with_zoom)
						$title = Skin::build_link(Dates::get_url($current_year.'/'.$current_month, 'month'), $title, 'month');

				}

				// first day of this month
				$first_of_month = gmmktime(0, 0, 0, $month, 1, $year);

                                $day_in_week = (int)gmdate('w', $first_of_month);
                                if(WEEK_START_MONDAY && $day_in_week===0) $day_in_week=7;

				// start a new month
				$current_day = 1;
				$text .= Dates::build_month_prefix($current_year, $current_month, $day_in_week, $style, $title, $with_headers);

				// not yet at the target month, close an empty month
				if($month != $current_month)
					$text .= Dates::build_month_suffix($current_year, $current_month, $current_day);

			}

			// fill in gaps
			while($current_day < $day) {
				$text .= '<td>'.$current_day++.'</td>';

				// start a new week on next row
				if(++$day_in_week >= W_END_DAY) {
					$day_in_week = W_START_DAY;
					$text .= '</tr><tr>';
				}
			}

		}

		// flush previous day
		if($day_content)
			$text .= '<td class="spot">'.Dates::build_day($current_day++, $day_content, $day_panel_id, $compact).'</td>';

		// close last month, if any
		if($current_month)
			$text .= Dates::build_month_suffix($current_year, $current_month, $current_day);

		// draw an empty calendar, if required
		if(!$text && $forced_year) {

			// one single month
			if($forced_month > 0) {

				// add a caption
				$title = '';
				if($with_caption) {

					// month title
					$title = ucfirst(Dates::get_month_label($forced_month)).' '.$forced_year;

					// zoom to the monthly view
					if($with_zoom)
						$title = Skin::build_link(Dates::get_url($forced_year.'/'.$forced_month, 'month'), $title, 'month');

				}

				$text .= Dates::build_month_prefix($forced_year, $forced_month, -1, $style, $title, $with_headers)
					.Dates::build_month_suffix($forced_year, $forced_month, 1);

			}

		}


		// empty rows are not allowed
		$text = str_replace('<tr></tr>', '', $text);

		// an area to display date details
		$text .= '<div id="'.$day_panel_id.'" class="day_panel" ></div>';

		// done
		return $text;
	}

	/**
	 * clear cache entries for one item
	 *
	 * @param array item attributes
	 */
	public static function clear(&$item) {

		// where this item can be displayed
		$topics = array('articles', 'dates');

		// clear anchor page
		if(isset($item['anchor']))
			$topics[] = $item['anchor'];

		// clear this page
		if(isset($item['id']))
			$topics[] = 'date:'.$item['id'];

		// clear the cache
		Cache::clear($topics);

	}

	/**
	 * count dates attached to some anchor
	 *
	 * @param string the selected anchor (e.g., 'article:12')
	 * @return int the resulting count, or NULL on error
	 */
	public static function count_for_anchor($anchor) {
		global $context;

		// restrict the query to addressable content
		$where = Articles::get_sql_where();

		// put only published pages in boxes
		if(isset($variant) && ($variant == 'boxes')) {
			$where = " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// provide published pages to anonymous surfers
		} elseif(!Surfer::is_logged()) {
			$where = " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// logged surfers that are non-associates are restricted to their own articles, plus published articles
		} elseif(!Surfer::is_empowered()) {
			$where = " AND ((articles.create_id=".Surfer::get_id().") OR (NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')))";
		}

		// now
		$match = gmdate('Y-m-d H:i:s');

		// select among available items
		$query = "SELECT COUNT(*) as count"
			." FROM ".SQL::table_name('dates')." as dates "
			.", ".SQL::table_name('articles')." AS articles"
			." WHERE ((dates.anchor_type LIKE 'article') AND (dates.anchor_id = articles.id))"
			."	AND (articles.anchor = '".SQL::escape($anchor)."') AND (".$where.")";

		return SQL::query_scalar($query);
	}

	/**
	 * delete one date in the database and in the file system
	 *
	 * @param int the id of the date to delete
	 * @return boolean TRUE on success, FALSE otherwise
	 *
	 * @see dates/delete.php
	 */
	public static function delete($id) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return FALSE;

		// delete the record in the database
		$query = "DELETE FROM ".SQL::table_name('dates')." WHERE id = ".SQL::escape($id);
		if(SQL::query($query) === FALSE)
			return FALSE;

		// job done
		return TRUE;
	}

	/**
	 * delete all dates for a given anchor
	 *
	 * @param the anchor to check
	 *
	 * @see overlays/day.php
	 * @see shared/anchors.php
	 */
	public static function delete_for_anchor($anchor) {
		global $context;

		// delete all matching records in the database
		$query = "DELETE FROM ".SQL::table_name('dates')." WHERE anchor LIKE '".SQL::escape($anchor)."'";
		SQL::query($query);
	}

	/**
	 * get one date by id
	 *
	 * @param int the id of the date
	 * @return the resulting $item array, with at least keys: 'id', 'date_stamp', etc.
	 *
	 * @see dates/delete.php
	 * @see dates/edit.php
	 * @see dates/view.php
	 */
	public static function get($id) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// select among available items -- exact match
		$query = "SELECT * FROM ".SQL::table_name('dates')." as dates "
			." WHERE (dates.id = ".SQL::escape($id).")";

		$output = SQL::query_first($query);
		return $output;
	}

	/**
	 * get first date for one anchor
	 *
	 * @param string the anchor
	 * @return the resulting $item array, with at least keys: 'id', 'date_stamp', etc.
	 */
	public static function get_for_anchor($anchor) {
		global $context;

		// sanity check
		if(!$anchor)
			return NULL;

		// select among available items -- exact match
		$query = "SELECT * FROM ".SQL::table_name('dates')." as dates "
			." WHERE (dates.anchor LIKE '".SQL::escape($anchor)."')";

		$output = SQL::query_first($query);
		return $output;
	}

	/**
	 * label for one month
	 *
	 * @parameter string the target month (e.g., '1999-03' or '03')
	 * @parameter the target language, if any
	 * @return a string to be used in web page
	 */
	public static function get_month_label($month, $language=NULL) {
		global $context;

		// the default is to use surfer language
		if(!$language)
			$language = $context['language'];

		// we don't care about year information
		$suffix = '';
		if(strlen($month) > 2) {
			$suffix = ' '.substr($month, 0, 4);

			if(strlen($month) == 7)
				$month = substr($month, -2);
			else
				$month = substr($month, -1);
		}

		// ensure a valid integer
		$month = (int)$month;
		if(($month < 1) || ($month > 12))
			return '***';

		// labels for months
		static $months;
		if(!isset($months))
			$months = array();

		// define only once
		if(!isset($months[1])) {
			$months[1] = i18n::s('January');
			$months[2] = i18n::s('February');
			$months[3] = i18n::s('March');
			$months[4] = i18n::s('April');
			$months[5] = i18n::s('May');
			$months[6] = i18n::s('June');
			$months[7] = i18n::s('July');
			$months[8] = i18n::s('August');
			$months[9] = i18n::s('September');
			$months[10] = i18n::s('October');
			$months[11] = i18n::s('November');
			$months[12] = i18n::s('December');
		}

		// here we are
		return $months[$month].$suffix;
	}

	/**
	 * build a reference to a date
	 *
	 * Depending on parameter '[code]with_friendly_urls[/code]' and on action,
	 * following results can be observed:
	 *
	 * - view - dates/view.php?id=123 or dates/view.php/123 or date-123
	 *
	 * - other - dates/edit.php?id=123 or dates/edit.php/123 or date-edit/123
	 *
	 * @param int the id of the date to handle
	 * @param string the expected action ('view', 'print', 'edit', 'delete', ...)
	 * @return string a normalized reference
	 *
	 * @see control/configure.php
	 */
	public static function get_url($id, $action='view') {
		global $context;

		// get a one-year calendar -- id is the target year (e.g., '1999')
		if($action == 'year') {
			if($context['with_friendly_urls'] == 'Y')
				return 'dates/year.php/'.rawurlencode($id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'dates/year.php/'.rawurlencode($id);
			else
				return 'dates/year.php?year='.urlencode($id);
		}

		// get a one-month calendar -- id is the target month (e.g., '199903', '1999-03' or '1999/03')
		if($action == 'month') {

			// do not accept more than 7 chars
			if(strlen($id) > 7)
				$id = substr($id, 0, 7);

			// expand the compact form (e.g., '199903' -> '1999/03')
			if(strlen($id) == 6)
				$id = substr($id, 0, 4).'/'.substr($id, 5, 2);

			// normalize separator
			$id = str_replace('-', '/', $id);


			if($context['with_friendly_urls'] == 'Y')
				return 'dates/month.php/'.$id;
			elseif($context['with_friendly_urls'] == 'R')
				return 'dates/month.php/'.$id;
			else
				return 'dates/month.php?month='.urlencode($id);
		}

		// get a one-day calendar -- id is the target day (e.g., '19990325', '1999-03-25' or '1999/03/25')
		if($action == 'day') {

			// do not accept more than 10 chars
			if(strlen($id) > 10)
				$id = substr($id, 0, 10);

			// expand the compact form (e.g., '19990325' -> '1999/03/25')
			if(strpos($id, '/') === FALSE)
				$id = substr($id, 0, 4).'/'.substr($id, 4, 2).'/'.substr($id, 6, 2);

			// normalize separator
			$id = str_replace('-', '/', $id);

			if($context['with_friendly_urls'] == 'Y')
				return 'dates/day.php/'.$id;
			elseif($context['with_friendly_urls'] == 'R')
				return 'dates/day.php/'.$id;
			else
				return 'dates/day.php?day='.urlencode($id);
		}

		// check the target action
		if(!preg_match('/^(delete|edit|view)$/', $action))
			return 'dates/'.$action.'.php?id='.urlencode($id);

		// normalize the link
		return normalize_url(array('dates', 'date'), $action, $id);
	}

	/**
	 * list past events
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see dates/index.php
	 */
	public static function list_by_date($offset=0, $count=10, $variant='full') {
		global $context;

		// limit the scope of the request
		$query = "SELECT * FROM ".SQL::table_name('dates')." as dates"
			." ORDER BY dates.date_stamp DESC LIMIT ".$offset.','.$count;

		// the list of dates
		$output = Dates::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list newest dates for one anchor
	 *
	 * @param string the anchor (e.g., 'article:123')
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	public static function list_by_date_for_anchor($anchor, $offset=0, $count=20, $variant=NULL) {
		global $context;

		// use the anchor itself as the default variant
		if(!$variant)
			$variant = $anchor;

		// the request
		$query = "SELECT * FROM ".SQL::table_name('dates')." as dates "
			." WHERE (dates.anchor='".SQL::escape($anchor)."') "
			." ORDER BY dates.date_stamp DESC LIMIT ".$offset.','.$count;

		// the list of dates
		$output = Dates::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list all dates attached to some anchor
	 *
	 * @param string the anchor (e.g., 'section:123')
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $type, $icon, $date)
	 */
	public static function list_for_anchor($anchor, $offset=0, $count=100, $variant='family') {
		global $context;

		// restrict the query to addressable content
		$where = Articles::get_sql_where();

		// put only published pages in boxes
		if($variant == 'boxes') {
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// provide published pages to anonymous surfers
		} elseif(!Surfer::is_logged()) {
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// logged surfers that are non-associates are restricted to their own articles, plus published articles
		} elseif(!Surfer::is_empowered()) {
			$where .= " AND ((articles.create_id=".Surfer::get_id().") OR (NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')))";
		}

		// the request
		$query = "SELECT dates.date_stamp as date_stamp, articles.* FROM ".SQL::table_name('dates')." as dates"
			.", ".SQL::table_name('articles')." AS articles"
			." WHERE ((dates.anchor_type LIKE 'article') AND (dates.anchor_id = articles.id))"
			."	AND (articles.anchor LIKE '".SQL::escape($anchor)."') AND ".$where
			." ORDER BY dates.date_stamp LIMIT ".$offset.','.$count;

		// the list of dates
		$output = Dates::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list all dates for one day
	 *
	 * @param int the year
	 * @param int the month, from 1 to 12
	 * @param int the day, from 1 to 31
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $type, $icon, $date)
	 */
	public static function list_for_day($year, $month, $day, $variant='links') {
		global $context;

		// restrict the query to addressable content
		$where = Articles::get_sql_where();

		// put only published pages in boxes
		if($variant == 'boxes') {
			$where = "(".$where.") AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// provide published pages to anonymous surfers
		} elseif(!Surfer::is_logged()) {
			$where = "(".$where.") AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// logged surfers that are non-associates are restricted to their own articles, plus published articles
		} elseif(!Surfer::is_empowered()) {
			$where = "(".$where.") AND ((articles.create_id=".Surfer::get_id().") OR (NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')))";
		}

		// check the year
		if($year < 1970)
			$year = (int)gmdate('Y');

		// check the month
		if(($month < 1) || ($month > 12))
			$month = (int)gmdate('m');

		// check the day
		if(($day < 1) || ($day > 31))
			$day = (int)gmdate('d');

		// prefix to match
		$match = gmdate('Y-m-d', gmmktime(0, 0, 0, $month, $day, $year)).'%';

		// the request
		$query = "SELECT dates.date_stamp as date_stamp, articles.* FROM ".SQL::table_name('dates')." as dates"
			.", ".SQL::table_name('articles')." AS articles"
			." WHERE ((dates.anchor_type LIKE 'article') AND (dates.anchor_id = articles.id))"
			."	AND (dates.date_stamp LIKE '".SQL::escape($match)."') AND ".$where
			." ORDER BY dates.date_stamp LIMIT 100";

		// the list of dates
		$output = Dates::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list all dates for one month
	 *
	 * Provide an anchor reference to limit the scope of the list.
	 *
	 * @param int the year
	 * @param int the month, from 1 to 12, or -1 for the full year
	 * @param string the list variant, if any
	 * @param string reference an target anchor (e.g., 'section:123'), if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $type, $icon, $date)
	 */
	public static function list_for_month($year, $month, $variant='links', $anchor=NULL) {
		global $context;

		// check the year
		if($year < 1970)
			$year = (int)date('Y');

		// check the month
		if(($month >= 1) && ($month <= 12))
			$prefix = date('Y-m-', mktime(0, 0, 0, $month, 1, $year));

		// check the full year
		else
			$prefix = date('Y-', mktime(0, 0, 0, 1, 1, $year));

		// the list of dates
		$output = Dates::list_for_prefix($prefix, $variant, $anchor);
		return $output;
	}

	/**
	 * list all dates for one prefix
	 *
	 * Provide an anchor reference to limit the scope of the list.
	 *
	 * @param string prefix for matching stamps
	 * @param string the list variant, if any
	 * @param string reference an target anchor (e.g., 'section:123'), if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $type, $icon, $date)
	 */
	public static function list_for_prefix($prefix=NULL, $variant='links', $anchor=NULL) {
		global $context;

		// default is current month
		if(!$prefix)
			$prefix = gmdate('Y-m-');

		// restrict the query to addressable content
		$where = Articles::get_sql_where();

		// limit to this anchor, if any
		if($anchor)
			$where = "(articles.anchor LIKE '".SQL::escape($anchor)."') AND ".$where;

		// put only published pages in boxes
		if($variant == 'boxes') {
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// provide published pages to anonymous surfers
		} elseif(!Surfer::is_logged()) {
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// logged surfers that are non-associates are restricted to their own articles, plus published articles
		} elseif(!Surfer::is_empowered()) {
			$where .= " AND ((articles.create_id=".Surfer::get_id().") OR (NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')))";
		}

		// the request
		$query = "SELECT dates.date_stamp as date_stamp, articles.* FROM ".SQL::table_name('dates')." as dates"
			.", ".SQL::table_name('articles')." AS articles"
			." WHERE ((dates.anchor_type LIKE 'article') AND (dates.anchor_id = articles.id))"
			."	AND (dates.date_stamp LIKE '".SQL::escape($prefix)."%') AND ".$where
			." ORDER BY dates.date_stamp LIMIT 1000";

		// the list of dates
		$output = Dates::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list future dates
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $type, $icon, $date)
	 *
	 * @see dates/index.php
	 */
	public static function list_future($offset=0, $count=100, $variant='family') {
		$output = Dates::list_future_for_anchor(NULL, $offset, $count, $variant, TRUE);
		return $output;
	}

	/**
	 * list future dates attached to some anchor
	 *
	 * @param string the anchor (e.g., 'section:123')
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @param boolean trackback to first day of current month
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $type, $icon, $date)
	 */
	public static function list_future_for_anchor($anchor, $offset=0, $count=100, $variant='family', $back_to_first = FALSE) {
		global $context;

		// restrict the query to addressable content
		$where = Articles::get_sql_where();

		// put only published pages in boxes
		if($variant == 'boxes') {
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// provide published pages to anonymous surfers
		} elseif(!Surfer::is_logged()) {
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// logged surfers that are non-associates are restricted to their own articles, plus published articles
		} elseif(!Surfer::is_empowered()) {
			$where .= " AND ((articles.create_id=".Surfer::get_id().") OR (NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')))";
		}

		// starting this month
		if($back_to_first)
			$match = gmdate('Y-m-01');
		else
			$match = gmdate('Y-m-d');

		// only for one anchor
		if($anchor)
			$where = "(articles.anchor LIKE '".SQL::escape($anchor)."') AND ".$where;

		// the request
		$query = "SELECT dates.date_stamp as date_stamp, articles.id as id, articles.title as title, articles.nick_name as nick_name, articles.active, articles.edit_date, articles.publish_date, articles.introduction, articles.thumbnail_url, articles.anchor FROM ".SQL::table_name('dates')." as dates"
			.", ".SQL::table_name('articles')." AS articles"
			." WHERE ((dates.anchor_type LIKE 'article') AND (dates.anchor_id = articles.id))"
			."	AND (dates.date_stamp >= '".SQL::escape($match)."') AND ".$where
			." ORDER BY dates.date_stamp LIMIT ".$offset.','.$count;

		// the list of dates
		$output = Dates::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list past dates attached to some anchor
	 *
	 * @param string the anchor (e.g., 'section:123')
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $type, $icon, $date)
	 */
	public static function list_past_for_anchor($anchor, $offset=0, $count=100, $variant='family') {
		global $context;

		// restrict the query to addressable content
		$where = Articles::get_sql_where();

		// put only published pages in boxes
		if($variant == 'boxes') {
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// provide published pages to anonymous surfers
		} elseif(!Surfer::is_logged()) {
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// logged surfers that are non-associates are restricted to their own articles, plus published articles
		} elseif(!Surfer::is_empowered()) {
			$where .= " AND ((articles.create_id=".Surfer::get_id().") OR (NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')))";
		}

		// now
		$match = gmdate('Y-m-d');

		// the request
		$query = "SELECT dates.date_stamp as date_stamp, articles.* FROM ".SQL::table_name('dates')." as dates"
			.", ".SQL::table_name('articles')." AS articles"
			." WHERE ((dates.anchor_type LIKE 'article') AND (dates.anchor_id = articles.id))"
			."	AND (dates.date_stamp < '".SQL::escape($match)."') AND (articles.anchor LIKE '".SQL::escape($anchor)."') AND ".$where
			." ORDER BY dates.date_stamp DESC LIMIT ".$offset.','.$count;

		// the list of dates
		$output = Dates::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list selected dates
	 *
	 * If variant is provided as a string, the functions looks for a script featuring this name.
	 * If no file matches then the default 'dates/layout_dates.php' script is loaded.
	 *
	 * @param resource result of database query
	 * @param string 'full', etc or object, i.e., an instance of the layout interface
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $type, $icon, $date)
	 */
	public static function list_selected($result, $variant='compact') {
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

		// no layout yet
		$layout = NULL;

		// separate options from layout name
		$attributes = explode(' ', $variant, 2);

		// instanciate the provided name
		if($attributes[0]) {
			$name = 'layout_dates_as_'.$attributes[0];
			if(is_readable($context['path_to_root'].'dates/'.$name.'.php')) {
				include_once $context['path_to_root'].'dates/'.$name.'.php';
				$layout = new $name;

				// provide parameters to the layout
				if(isset($attributes[1]))
					$layout->set_variant($attributes[1]);

			}
		}

		// use default layout
		if(!$layout) {
			include_once $context['path_to_root'].'dates/layout_dates.php';
			$layout = new Layout_dates();
			$layout->set_variant($variant);
		}

		// do the job
		$output = $layout->layout($result);
		return $output;

	}

	/**
	 * post a new date or an updated date
	 *
	 * This function populates the error context, where applicable.
	 *
	 * @param array an array of fields
	 * @return integer the id of the new or updated record, else 0 on error
	 *
	 * @see dates/edit.php
	**/
	public static function post(&$fields) {
		global $context;

		// no date
		if(!$fields['date_stamp']) {
			Logger::error(i18n::s('Please provide a date.'));
			return 0;
		}

		// no anchor reference
		if(!$fields['anchor']) {
			Logger::error(i18n::s('No anchor has been found.'));
			return 0;
		}

		// set default values for this editor
		Surfer::check_default_editor($fields);

		// update the existing record
		if(isset($fields['id'])) {

			// id cannot be empty
			if(!isset($fields['id']) || !is_numeric($fields['id'])) {
				Logger::error(i18n::s('No item has the provided id.'));
				return FALSE;
			}

			// update the existing record
			$query = "UPDATE ".SQL::table_name('dates')." SET "
				."date_stamp='".SQL::escape($fields['date_stamp'])."'";

			// maybe a silent update
			if(!isset($fields['silent']) || ($fields['silent'] != 'Y')) {
				$query .= ", "
				."edit_name='".SQL::escape($fields['edit_name'])."', "
				."edit_id=".SQL::escape($fields['edit_id']).", "
				."edit_address='".SQL::escape($fields['edit_address'])."', "
				."edit_date='".SQL::escape($fields['edit_date'])."'";
			}

			$query .= " WHERE id = ".SQL::escape($fields['id']);

			if(SQL::query($query) === FALSE)
				return 0;

		// insert a new record
		} else {

			// always remember the date
			$query = "INSERT INTO ".SQL::table_name('dates')." SET "
				."anchor='".SQL::escape($fields['anchor'])."', "
				."anchor_id=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', -1),"
				."anchor_type=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', 1),"
				."date_stamp='".SQL::escape($fields['date_stamp'])."', "
				."edit_name='".SQL::escape($fields['edit_name'])."', "
				."edit_id=".SQL::escape($fields['edit_id']).", "
				."edit_address='".SQL::escape($fields['edit_address'])."', "
				."edit_date='".SQL::escape($fields['edit_date'])."'";

			if(SQL::query($query) === FALSE)
				return 0;

			// id of the new record
			$fields['id'] = SQL::get_last_id($context['connection']);

		}

		// clear the cache for dates
		Dates::clear($fields);

		// end of job
		return $fields['id'];
	}

	/**
	 * create or alter tables for dates
	 *
	 * @see control/setup.php
	 */
	public static function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['anchor']		= "VARCHAR(64) DEFAULT 'article:1' NOT NULL";
		$fields['anchor_id']	= "MEDIUMINT UNSIGNED NOT NULL";
		$fields['anchor_type']	= "VARCHAR(64) DEFAULT 'section' NOT NULL";
		$fields['date_stamp']	= "DATETIME";
		$fields['edit_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_id']		= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['edit_address'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_date']	= "DATETIME";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX anchor']	= "(anchor)";
		$indexes['INDEX anchor_id'] 	= "(anchor_id)";
		$indexes['INDEX anchor_type']	= "(anchor_type)";
		$indexes['INDEX date_stamp'] = "(date_stamp)";

		return SQL::setup_table('dates', $fields, $indexes);
	}

	/**
	 * get some statistics
	 *
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see dates/index.php
	 */
	public static function stat() {
		global $context;

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date"
			." FROM ".SQL::table_name('dates')." as dates";

		$output = SQL::query_first($query);
		return $output;
	}

	/**
	 * get some statistics for one anchor
	 *
	 * @param the selected anchor (e.g., 'article:12')
	 * @return the resulting ($count, $min_date, $max_date) array
	 */
	public static function stat_for_anchor($anchor) {
		global $context;

		// restrict the query to addressable content
		$where = Articles::get_sql_where();

		// put only published pages in boxes
		if(isset($variant) && ($variant == 'boxes')) {
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// provide published pages to anonymous surfers
		} elseif(!Surfer::is_logged()) {
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// logged surfers that are non-associates are restricted to their own articles, plus published articles
		} elseif(!Surfer::is_empowered()) {
			$where .= " AND ((articles.create_id=".Surfer::get_id().") OR (NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')))";
		}

		// now
		$match = gmdate('Y-m-d H:i:s');

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(articles.edit_date) as oldest_date, MAX(articles.edit_date) as newest_date "
			." FROM ".SQL::table_name('dates')." as dates "
			.", ".SQL::table_name('articles')." AS articles"
			." WHERE ((dates.anchor_type LIKE 'article') AND (dates.anchor_id = articles.id))"
			."	AND (dates.anchor = '".SQL::escape($anchor)."') AND ".$where;

		$output = SQL::query_first($query);
		return $output;
	}

	/**
	 * get some statistics for one anchor
	 *
	 * @param the selected anchor (e.g., 'article:12')
	 * @return the resulting ($count, $min_date, $max_date) array
	 */
	public static function stat_past_for_anchor($anchor) {
		global $context;

		// restrict the query to addressable content
		$where = Articles::get_sql_where();

		// put only published pages in boxes
		if(isset($variant) && ($variant == 'boxes')) {
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// provide published pages to anonymous surfers
		} elseif(!Surfer::is_logged()) {
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// logged surfers that are non-associates are restricted to their own articles, plus published articles
		} elseif(!Surfer::is_empowered()) {
			$where .= " AND ((articles.create_id=".Surfer::get_id().") OR (NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')))";
		}

		// now
		$match = gmdate('Y-m-d H:i:s');

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(articles.edit_date) as oldest_date, MAX(articles.edit_date) as newest_date "
			." FROM ".SQL::table_name('dates')." as dates "
			.", ".SQL::table_name('articles')." AS articles"
			." WHERE ((dates.anchor_type LIKE 'article') AND (dates.anchor_id = articles.id))"
			."	AND (dates.date_stamp < '".SQL::escape($match)."') AND	(articles.anchor = '".SQL::escape($anchor)."') AND ".$where;

		$output = SQL::query_first($query);
		return $output;
	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('dates');

?>
