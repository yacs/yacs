<?php
/**
 * the database abstraction layer for locations
 *
 * Locations are geographical coordinates.
 *
 * Look at [code]list_by_distance()[/code] for the selection of locations based on relative distances.
 * Thanks to Eoin for having provided the adequate formula.
 *
 * @author Bernard Paques
 * @author Eoin
 * @author Florent
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Locations {

	/**
	 * check if new locations can be added
	 *
	 * This function returns TRUE if locations can be added to some place,
	 * and FALSE otherwise.
	 *
	 * @param object an instance of the Anchor interface, if any
	 * @param array a set of item attributes, if any
	 * @return TRUE or FALSE
	 */
	function are_allowed($anchor=NULL, $item=NULL) {
		global $context;

		// locations are prevented in item
		if(isset($item['options']) && is_string($item['options']) && preg_match('/\bno_locations\b/i', $item['options']))
			return FALSE;

		// locations are prevented in anchor
		if(is_object($anchor) && is_callable(array($anchor, 'has_option')) && $anchor->has_option('no_locations'))
			return FALSE;

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// submissions have been disallowed
		if(isset($context['users_without_submission']) && ($context['users_without_submission'] == 'Y'))
			return FALSE;

		// container is hidden
		if(isset($item['active']) && ($item['active'] == 'N')) {
		
			// filter editors
			if(!Surfer::is_empowered())
				return FALSE;
				
			// editors will have to unlock the container to contribute
			if(isset($item['locked']) && ($item['locked'] == 'Y'))
				return FALSE;
			return TRUE;
			
		// container is restricted
		} elseif(isset($item['active']) && ($item['active'] == 'R')) {
		
			// filter members
			if(!Surfer::is_member())
				return FALSE;
				
			// editors can proceed
			if(Surfer::is_empowered())
				return TRUE;
				
			// members can contribute except if container is locked
			if(isset($item['locked']) && ($item['locked'] == 'Y'))
				return FALSE;
			return TRUE;
			
		}

		// surfer has special privileges
		if(Surfer::is_empowered())
			return TRUE;

		// item has been locked
		if(isset($item['locked']) && is_string($item['locked']) && ($item['locked'] == 'Y'))
			return FALSE;

		// anchor has been locked --only used when there is no item provided
		if(!isset($item['id']) && is_object($anchor) && $anchor->has_option('locked'))
			return FALSE;

		// authenticated members are allowed to add locations
		if(Surfer::is_member())
			return TRUE;

		// anonymous contributions are allowed for this section
		if(isset($item['content_options']) && preg_match('/\banonymous_edit\b/i', $item['content_options']))
			return TRUE;

		// anonymous contributions are allowed for this item
		if(isset($item['options']) && preg_match('/\banonymous_edit\b/i', $item['options']))
			return TRUE;

		// anonymous contributions are allowed for this anchor
		if(is_object($anchor) && $anchor->is_editable())
			return TRUE;

		// teasers are activated
		if(Surfer::is_teased())
			return TRUE;

		// the default is to not allow for new locations
		return FALSE;
	}

	/**
	 * clear cache entries for one item
	 *
	 * @param array item attributes
	 */
	function clear(&$item) {

		// where this item can be displayed
		$topics = array('locations', 'users');

		// clear anchor page
		if(isset($item['anchor']))
			$topics[] = $item['anchor'];

		// clear this page
		if(isset($item['id']))
			$topics[] = 'location:'.$item['id'];

		// clear the cache
		Cache::clear($topics);

	}

	/**
	 * delete one location in the database and in the file system
	 *
	 * @param int the id of the location to delete
	 * @return boolean TRUE on success, FALSE otherwise
	 *
	 * @see locations/delete.php
	 */
	function delete($id) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return FALSE;

		// delete the record in the database
		$query = "DELETE FROM ".SQL::table_name('locations')." WHERE id = ".SQL::escape($id);
		if(SQL::query($query) === FALSE)
			return FALSE;

		// job done
		return TRUE;
	}

	/**
	 * delete all locations for a given anchor
	 *
	 * @param the anchor to check
	 *
	 * @see shared/anchors.php
	 */
	function delete_for_anchor($anchor) {
		global $context;

		// delete all matching records in the database
		$query = "DELETE FROM ".SQL::table_name('locations')." WHERE anchor LIKE '".SQL::escape($anchor)."'";
		SQL::query($query);
	}

	/**
	 * duplicate all locations for a given anchor
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
		$query = "SELECT * FROM ".SQL::table_name('locations')." WHERE anchor LIKE '".SQL::escape($anchor_from)."'";
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
				if($item['id'] = Locations::post($item)) {

					// more pairs of strings to transcode
					$transcoded[] = array('/\[location='.preg_quote($old_id, '/').'/i', '[location='.$item['id']);

					// duplicate elements related to this item
					Anchors::duplicate_related_to('location:'.$old_id, 'location:'.$item['id']);

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
	 * get one location by id
	 *
	 * @param int the id of the location
	 * @return the resulting $item array, with at least keys: 'id', 'geo_place_name', etc.
	 *
	 * @see locations/delete.php
	 * @see locations/edit.php
	 * @see locations/map_on_earth.php
	 * @see locations/view.php
	 * @see shared/codes.php
	 */
	function &get($id) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// select among available items -- exact match
		$query = "SELECT * FROM ".SQL::table_name('locations')." AS locations "
			." WHERE (locations.id = ".SQL::escape($id).")";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * build a reference to a location
	 *
	 * Depending on parameter '[code]with_friendly_urls[/code]' and on action,
	 * following results can be observed:
	 *
	 * - view - locations/view.php?id=123 or locations/view.php/123 or location-123
	 *
	 * - other - locations/edit.php?id=123 or locations/edit.php/123 or location-edit/123
	 *
	 * @param int the id of the location to handle
	 * @param string the expected action ('view', 'print', 'edit', 'delete', ...)
	 * @param string additional data, such as file name, if any
	 * @return string a normalized reference
	 *
	 * @see control/configure.php
	 */
	function get_url($id, $action='view', $name=NULL) {
		global $context;

		// check the target action
		if(!preg_match('/^(delete|edit|map_on_earth|map_on_google|view)$/', $action))
			$action = 'view';

		// normalize the link
		return normalize_url(array('locations', 'location'), $action, $id, $name);
	}

	/**
	 * list newest locations
	 *
	 * To build a simple box of the newest locations in your main index page, just use
	 * the following example:
	 * [php]
	 * // side bar with the list of most recent locations
	 * include_once 'locations/locations.php';
	 * $items = Locations::list_by_date(0, 10, '');
	 * $text = Skin::build_list($items, 'compact');
	 * $context['text'] .= Skin::build_box($title, $text, 'navigation');
	 * [/php]
	 *
	 * You can also display the newest location separately, using [code]Locations::get_newest()[/code]
	 * In this case, skip the very first location in the list by using
	 * [code]Locations::list_by_date(1, 10, '')[/code]
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see locations/index.php
	 */
	function &list_by_date($offset=0, $count=10, $variant='full') {
		global $context;

		// limit the scope of the request
		$query = "SELECT * FROM ".SQL::table_name('locations')." AS locations"
			." ORDER BY locations.edit_date DESC, locations.geo_place_name LIMIT ".$offset.','.$count;

		// the list of locations
		$output =& Locations::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list newest locations for one anchor
	 *
	 * @param string the anchor (e.g., 'article:123')
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see articles/edit.php
	 * @see users/edit.php
	 */
	function &list_by_date_for_anchor($anchor, $offset=0, $count=20, $variant=NULL) {
		global $context;

		// use the anchor itself as the default variant
		if(!$variant)
			$variant = $anchor;

		// the request
		$query = "SELECT * FROM ".SQL::table_name('locations')." AS locations "
			." WHERE (locations.anchor LIKE '".SQL::escape($anchor)."') "
			." ORDER BY locations.edit_date DESC, locations.geo_place_name LIMIT ".$offset.','.$count;

		// the list of locations
		$output =& Locations::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list newest locations for one author
	 *
	 * @param int the id of the author of the location
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_by_date_for_author($author_id, $offset=0, $count=20, $variant='date') {
		global $context;

		// limit the scope of the request
		$query = "SELECT * FROM ".SQL::table_name('locations')." AS locations "
			." WHERE (locations.edit_id = ".SQL::escape($author_id).")"
			." ORDER BY locations.edit_date DESC, locations.geo_place_name LIMIT ".$offset.','.$count;

		// the list of locations
		$output =& Locations::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list nearest locations to one point
	 *
	 * If you are looking for locations near another one, set the offset to one.
	 *
	 * @param float latitude of the target point
	 * @param float longitude of the target point
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see locations/view.php
	 */
	function &list_by_distance($latitude, $longitude, $offset=0, $count=20, $variant='compact') {
		global $context;

		// select records by distance to the target point, with a limit to 5,000 km
		$query = "SELECT id, anchor, geo_place_name, latitude, longitude, geo_country, description,"
			." edit_name, edit_id, edit_address, edit_date,"
			." abs( 3956 * acos( sin(radians(".$latitude.")) * sin(radians(latitude)) "
			." + cos(radians(".$latitude.")) * cos(radians(latitude)) * cos(radians(longitude - ".$longitude.")) ) ) AS distance"
			." FROM ".SQL::table_name('locations')." AS locations "
			." WHERE latitude BETWEEN ".$latitude." - 45 AND ".$latitude." + 45"
			." AND longitude BETWEEN ".$longitude." - 45 AND ".$longitude." + 45"
			." ORDER BY distance, locations.edit_date DESC, locations.geo_place_name "
			." LIMIT ".$offset.','.$count;

		// the list of locations
		$output =& Locations::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list nearest locations to one anchor
	 *
	 * This function is similar to [code]list_by_distance()[/code],
	 * except that it looks for a location for the given anchor first.
	 *
	 * @param string a reference to the target anchor (eg, 'article:123')
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see locations/view.php
	 */
	function &list_by_distance_for_anchor($anchor, $offset=0, $count=20, $variant='compact') {
		global $context;

		// look for a location for this anchor
		$query = "SELECT latitude, longitude FROM ".SQL::table_name('locations')." AS locations "
			." WHERE (locations.anchor LIKE '".SQL::escape($anchor)."') "
			." ORDER BY locations.edit_date DESC, locations.geo_place_name LIMIT 0, 1";
		if(!$result =& SQL::query($query)) {
			$output = NULL;
			return $output;
		}

		// empty list
		if(!SQL::count($result)) {
			$output = NULL;
			return $output;
		}

		// the first item of the list provides latitude and longitude
		$item =& SQL::fetch($result);
		if(@count($item) != 2) {
			$output = NULL;
			return $output;
		}
		$latitude = trim($item['latitude'], ' ,');
		$longitude = $item['longitude'];

		// select records by distance to the target point, with a limit to 5,000 km
		$output =& Locations::list_by_distance($latitude, $longitude, $offset, $count, $variant);
		return $output;
	}

	/**
	 * list selected locations
	 *
	 * Accept following variants:
	 * - 'compact' - to build short lists in boxes and sidebars (this is the default)
	 * - 'no_anchor' - to build detailed lists in an anchor page
	 * - 'full' - include anchor information
	 *
	 * @param resource result of database query
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_selected(&$result, $layout='compact') {
		global $context;

		// no result
		if(!$result) {
			$output = NULL;
			return $output;
		}

		// special layouts
		if(is_object($layout)) {
			$output =& $layout->layout($result);
			return $output;
		}

		// one of regular layouts
		switch($layout) {

		case 'compact':
			include_once $context['path_to_root'].'locations/layout_locations_as_compact.php';
			$variant =& new Layout_locations_as_compact();
			$output =& $variant->layout($result);
			return $output;

		case 'raw':
			include_once $context['path_to_root'].'locations/layout_locations_as_raw.php';
			$variant =& new Layout_locations_as_raw();
			$output =& $variant->layout($result);
			return $output;

		default:
			include_once $context['path_to_root'].'locations/layout_locations.php';
			$variant =& new Layout_locations();
			$output =& $variant->layout($result, $layout);
			return $output;

		}
	}

	/**
	 * list user locations
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see locations/map_on_google.php
	 */
	function &list_users_by_date($offset=0, $count=10, $variant='full') {
		global $context;

		// limit the scope of the request
		$query = "SELECT * FROM ".SQL::table_name('locations')." AS locations"
			." WHERE locations.anchor LIKE 'user:%'"
			." ORDER BY locations.edit_date DESC, locations.geo_place_name LIMIT ".$offset.','.$count;

		// the list of locations
		$output =& Locations::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * map at Google
	 *
	 * @link http://www.nabble.com/problem-loading-googlemaps-into-jquery-UI-tabs-td15962881s27240.html
	 *
	 * @param array a list of locations
	 * @param int the scale to use
	 * @return string suitable XHTML to be sent to the browser
	 */
	function &map_on_google($items, $scale=5) {
		global $context;

		// we return some text
		$text = '';

		// we can have several maps on the same page
		static $map_index;
		if(!isset($map_index)) {
			$map_index = 0;
			$handle = 'map';

			// ensure we have a header
			$text .= Locations::map_on_google_header();

		// another name for the next map
		} else {
			$map_index++;
			$handle = 'map_'.$map_index;
		}

		// a place holder for the dynamic map
		$text .= '<div id="'.$handle.'" style="border: 1px solid #979797; background-color: #e5e3df; width: 500px; height: 300px; margin-right: auto; margin-top: 2em; margin-bottom: 2em">'."\n"
			.'	<div style="padding: 1em; color: gray">'.i18n::s('Loading...').'</div>'."\n"
			.'</div>'."\n";

		// create this map
		$text .= '<script type="text/javascript">//<![CDATA['."\n"
			.'if((typeof GBrowserIsCompatible != "undefined") && (GBrowserIsCompatible())) {'."\n"
			.'	var map = new GMap2($("'.$handle.'"));'."\n"
			.'	map.addControl(new GSmallMapControl());'."\n"
			.'	map.addControl(new GMapTypeControl());'."\n";

		// frame the map
		$latitudes = $longitudes = 0.00;
		$index = 0;
		foreach($items as $id => $attributes) {
			$latitudes += $attributes['latitude'];
			$longitudes += $attributes['longitude'];
			$index++;
		}

		// center point
		$latitude_middle = $latitudes / max(1, $index);
		$longitude_middle = $longitudes / max(1, $index);
		$text .= '	map.setCenter(new GLatLng(parseFloat("'.$latitude_middle.'"), parseFloat("'.$longitude_middle.'")), '.$scale.');'."\n";

		// add all markers
		$index = 1;
		foreach($items as $id => $item) {

			// ensure we have split coordinates
			if(!$item['latitude'] || !$item['longitude'])
				list($item['latitude'], $item['longitude']) = preg_split('/[\s,;]+/', $item['geo_position']);

			// some HTML description for this item
			$description = '';
			if(isset($item['geo_place_name']))
				$description .= $item['geo_place_name'].BR."\n";
			if($item['description'])
				$description .= Codes::beautify($item['description']);

			// use anchor information
			if(isset($item['anchor']) && ($anchor =& Anchors::get($item['anchor'])) && is_object($anchor)) {

				// insert thumbnail, if any
				if($icon = $anchor->get_thumbnail_url())
					$description = '<a href="'.$context['url_to_root'].$anchor->get_url().'"><img src="'.$icon.'" alt="'.encode_field($anchor->get_title()).'" style="float: left; margin-right: 1em; border: none;" /></a>'.$description;

				// a link to the anchor page
				$description .= BR."\n".Skin::build_link($anchor->get_url(), $anchor->get_title());
			}

			// item type
			if(isset($item['anchor']) && (strpos($item['anchor'], 'user:') === 0))
				$icon = 'iconRed';
			else
				$icon = 'iconBlue';

			// add one marker for this item
			$text .= '	var point = new GLatLng(parseFloat("'.$item['latitude'].'"), parseFloat("'.$item['longitude'].'"));'."\n"
				.'	var marker'.$map_index.$index.' = new GMarker(point, '.$icon.');'."\n"
				.'	GEvent.addListener(marker'.$map_index.$index.', "click", function() {'."\n"
				.'		marker'.$map_index.$index.'.openInfoWindowHtml("'.addcslashes($description, '\'\\"'."\n\r").'");'."\n"
				.'	});'."\n"
				.'	map.addOverlay(marker'.$map_index.$index.');'."\n";

			// next index
			$index++;
		}

		// the postamble
		$text .= '}'."\n"
			.'//]]></script>'."\n";

		// job done
		return $text;
	}

	/**
	 * load the google map api
	 *
	 * @return string suitable XHTML to be sent to the browser
	 */
	function &map_on_google_header($verbose = FALSE) {
		global $context;

		// we return some text
		$text = '';

		// load the header only once
		static $fused;
		if(isset($fused))
			return $text;
		$fused = TRUE;

		// no capability to create an image
		if(!isset($context['google_api_key']) || !$context['google_api_key']) {
			Logger::error(i18n::s('Use the configuration panel for web services to enter your Google API key.'));
			return $text;
		}

		// load the google library
		$text .= '<script type="text/javascript" src="http://maps.google.com/maps?file=api&amp;v=2&amp;key='.$context['google_api_key'].'"></script>'."\n";

		// load some icons from Google
		$text .= '<script type="text/javascript">// <![CDATA['."\n"
			.'if(typeof GIcon != "undefined") {'."\n"
			.'	var iconBlue = new GIcon();'."\n"
			.'	iconBlue.image = "http://labs.google.com/ridefinder/images/mm_20_blue.png";'."\n"
			.'	iconBlue.shadow = "http://labs.google.com/ridefinder/images/mm_20_shadow.png";'."\n"
			.'	iconBlue.iconSize = new GSize(12, 20);'."\n"
			.'	iconBlue.shadowSize = new GSize(22, 20);'."\n"
			.'	iconBlue.iconAnchor = new GPoint(6, 20);'."\n"
			.'	iconBlue.infoWindowAnchor = new GPoint(5, 1);'."\n"
			."\n"
			.'	var iconRed = new GIcon();'."\n"
			.'	iconRed.image = "http://labs.google.com/ridefinder/images/mm_20_red.png";'."\n"
			.'	iconRed.shadow = "http://labs.google.com/ridefinder/images/mm_20_shadow.png";'."\n"
			.'	iconRed.iconSize = new GSize(12, 20);'."\n"
			.'	iconRed.shadowSize = new GSize(22, 20);'."\n"
			.'	iconRed.iconAnchor = new GPoint(6, 20);'."\n"
			.'	iconRed.infoWindowAnchor = new GPoint(5, 1);'."\n"
			.'}'."\n"
			.'// ]]></script>'."\n";

		// done
		return $text;
	}

	/**
	 * post a new location or an updated location
	 *
	 * This function populates the error context, where applicable.
	 *
	 * @param array an array of fields
	 * @return the id of the new location, or FALSE on error
	 *
	 * @see locations/edit.php
	**/
	function post(&$fields) {
		global $context;

		// no geo_place_name
		if(!$fields['geo_place_name']) {
			Logger::error(i18n::s('Please add a geo_place_name for this location'));
			return FALSE;
		}

		// no anchor reference
		if(!$fields['anchor']) {
			Logger::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// set default values for this editor
		$fields = Surfer::check_default_editor($fields);

		// extract latitude and longitude
		if(isset($fields['geo_position']) && $fields['geo_position'])
			list($latitude, $longitude) = preg_split('/[\s,;]+/', $fields['geo_position']);

		// update the existing record
		if(isset($fields['id'])) {

			// id cannot be empty
			if(!isset($fields['id']) || !is_numeric($fields['id'])) {
				Logger::error(i18n::s('No item has the provided id.'));
				return FALSE;
			}

			// update the existing record
			$query = "UPDATE ".SQL::table_name('locations')." SET "
				."geo_place_name='".SQL::escape($fields['geo_place_name'])."', "
				."geo_position='".SQL::escape(isset($fields['geo_position']) ? $fields['geo_position'] : '')."', "
				."longitude='".SQL::escape(isset($longitude) ? $longitude : '0')."', "
				."latitude='".SQL::escape(isset($latitude) ? $latitude : '0')."', "
				."geo_country='".SQL::escape(isset($fields['geo_country']) ? $fields['geo_country'] : '')."', "
				."description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."'";

			// maybe a silent update
			if(!isset($fields['silent']) || ($fields['silent'] != 'Y')) {
				$query .= ", "
				."edit_name='".SQL::escape($fields['edit_name'])."', "
				."edit_id='".SQL::escape($fields['edit_id'])."', "
				."edit_address='".SQL::escape($fields['edit_address'])."', "
				."edit_date='".SQL::escape($fields['edit_date'])."'";
			}

			$query .= " WHERE id = ".SQL::escape($fields['id']);

		// insert a new record
		} else {

			// always remember the date
			$query = "INSERT INTO ".SQL::table_name('locations')." SET "
				."anchor='".SQL::escape($fields['anchor'])."', "
				."geo_place_name='".SQL::escape($fields['geo_place_name'])."', "
				."geo_position='".SQL::escape(isset($fields['geo_position']) ? $fields['geo_position'] : '')."', "
				."longitude='".SQL::escape(isset($longitude) ? $longitude : '')."', "
				."latitude='".SQL::escape(isset($latitude) ? $latitude : '')."', "
				."geo_country='".SQL::escape(isset($fields['geo_country']) ? $fields['geo_country'] : '')."', "
				."description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."', "
				."edit_name='".SQL::escape($fields['edit_name'])."', "
				."edit_id='".SQL::escape($fields['edit_id'])."', "
				."edit_address='".SQL::escape($fields['edit_address'])."', "
				."edit_date='".SQL::escape($fields['edit_date'])."'";

		}

		// actual update query
		if(SQL::query($query) === FALSE)
			return FALSE;

		// remember the id of the new item
		if(!isset($fields['id']))
			$fields['id'] = SQL::get_last_id($context['connection']);

		// clear the cache for locations
		Locations::clear($fields);

		// end of job
		return $fields['id'];
	}

	/**
	 * create or alter tables for locations
	 *
	 * @see control/setup.php
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['anchor']		= "VARCHAR(64) DEFAULT 'article:1' NOT NULL";
		$fields['geo_place_name'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['geo_position'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['longitude']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['latitude'] 	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['geo_country']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['description']	= "TEXT NOT NULL";
		$fields['edit_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_id']		= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['edit_address'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_date']	= "DATETIME";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX anchor']	= "(anchor)";
		$indexes['INDEX edit_date'] = "(edit_date)";
		$indexes['INDEX edit_id']	= "(edit_id)";
		$indexes['INDEX geo_place_name'] = "(geo_place_name)";
		$indexes['INDEX latitude']	= "(latitude)";
		$indexes['INDEX longitude'] = "(longitude)";
		$indexes['FULLTEXT INDEX']	= "full_text(geo_place_name, geo_country, description)";

		return SQL::setup_table('locations', $fields, $indexes);
	}

	/**
	 * get some statistics
	 *
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see locations/index.php
	 */
	function &stat() {
		global $context;

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date"
			." FROM ".SQL::table_name('locations')." AS locations";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get some statistics for one anchor
	 *
	 * @param the selected anchor (e.g., 'article:12')
	 * @return the resulting ($count, $min_date, $max_date) array
	 */
	function &stat_for_anchor($anchor) {
		global $context;

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date "
			." FROM ".SQL::table_name('locations')." AS locations "
			." WHERE locations.anchor LIKE '".SQL::escape($anchor)."'";

		$output =& SQL::query_first($query);
		return $output;
	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('locations');

?>