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
	 * @param string the type of item, e.g., 'section'
	 * @return boolean TRUE or FALSE
	 */
	public static function allow_creation($anchor=NULL, $item=NULL, $variant=NULL) {
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

		// only in articles
		if($variant == 'article') {

			// 'no_links' option
			if(Articles::has_option('no_locations', $anchor, $item))
				return FALSE;


		// other containers
		} else {

			// locations have to be activated explicitly
			if(isset($item['options']) && is_string($item['options']) && preg_match('/\bwith_locations\b/i', $item['options']))
				;
			elseif(is_object($anchor) && $anchor->has_option('with_locations'))
				;
			else
				return FALSE;

		}

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// submissions have been disallowed
		if(isset($context['users_without_submission']) && ($context['users_without_submission'] == 'Y'))
			return FALSE;

		// only in articles
		if($variant == 'article') {

			// surfer owns this item, or the anchor
			if(Articles::is_owned($item, $anchor))
				return TRUE;

			// surfer is an editor, and the page is not private
			if(isset($item['active']) && ($item['active'] != 'N') && Articles::is_assigned($item['id']))
				return TRUE;

		// only in sections
		} elseif($variant == 'section') {

			// surfer owns this item, or the anchor
			if(Sections::is_owned($item, $anchor, TRUE))
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

		// surfer is a member
		if(Surfer::is_member())
			return TRUE;

		// container is restricted
		if(isset($item['active']) && ($item['active'] == 'R'))
			return FALSE;
		if(is_object($anchor) && !$anchor->is_public())
			return FALSE;

		// authenticated members and subscribers are allowed to add locations
		if(Surfer::is_logged())
			return TRUE;

		// anonymous contributions are allowed for articles
		if($variant == 'article') {
			if(isset($item['options']) && preg_match('/\banonymous_edit\b/i', $item['options']))
				return TRUE;
			if(is_object($anchor) && $anchor->has_option('anonymous_edit'))
				return TRUE;
		}

		// the default is to not allow for new locations
		return FALSE;
	}

	/**
	 * clear cache entries for one item
	 *
	 * @param array item attributes
	 */
	public static function clear(&$item) {

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
	public static function delete($id) {
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
	public static function delete_for_anchor($anchor) {
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
	public static function duplicate_for_anchor($anchor_from, $anchor_to) {
		global $context;

		// look for records attached to this anchor
		$count = 0;
		$query = "SELECT * FROM ".SQL::table_name('locations')." WHERE anchor LIKE '".SQL::escape($anchor_from)."'";
		if(($result = SQL::query($query)) && SQL::count($result)) {

			// the list of transcoded strings
			$transcoded = array();

			// process all matching records one at a time
			while($item = SQL::fetch($result)) {

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
			if($anchor = Anchors::get($anchor_to))
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
	public static function get($id) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// select among available items -- exact match
		$query = "SELECT * FROM ".SQL::table_name('locations')." AS locations "
			." WHERE (locations.id = ".SQL::escape($id).")";

		$output = SQL::query_first($query);
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
	public static function get_url($id, $action='view', $name=NULL) {
		global $context;

		// check the target action
		if(!preg_match('/^(delete|edit|map_on_earth|map_on_google|view)$/', $action))
			return 'locations/'.$action.'.php?id='.urlencode($id).'&action='.urlencode($name);

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
	public static function list_by_date($offset=0, $count=10, $variant='full') {
		global $context;

		// limit the scope of the request
		$query = "SELECT * FROM ".SQL::table_name('locations')." AS locations"
			." ORDER BY locations.edit_date DESC, locations.geo_place_name LIMIT ".$offset.','.$count;

		// the list of locations
		$output = Locations::list_selected(SQL::query($query), $variant);
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
	public static function list_by_date_for_anchor($anchor, $offset=0, $count=20, $variant=NULL) {
		global $context;

		// use the anchor itself as the default variant
		if(!$variant)
			$variant = $anchor;

		// the request
		$query = "SELECT * FROM ".SQL::table_name('locations')." AS locations "
			." WHERE (locations.anchor LIKE '".SQL::escape($anchor)."') "
			." ORDER BY locations.edit_date DESC, locations.geo_place_name LIMIT ".$offset.','.$count;

		// the list of locations
		$output = Locations::list_selected(SQL::query($query), $variant);
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
	public static function list_by_distance($latitude, $longitude, $offset=0, $count=20, $variant='compact') {
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
		$output = Locations::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list selected locations
	 *
	 * If variant is provided as a string, the functions looks for a script featuring this name.
	 * E.g., for variant 'compact', the file 'locations/layout_locations_as_compact.php' is loaded.
	 * If no file matches then the default 'locations/layout_locations.php' script is loaded.
	 *
	 * @param resource result of database query
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
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
			$name = 'layout_locations_as_'.$attributes[0];
			if(is_readable($context['path_to_root'].'locations/'.$name.'.php')) {
				include_once $context['path_to_root'].'locations/'.$name.'.php';
				$layout = new $name;

				// provide parameters to the layout
				if(isset($attributes[1]))
					$layout->set_variant($attributes[1]);

			}
		}

		// use default layout
		if(!$layout) {
			include_once $context['path_to_root'].'locations/layout_locations.php';
			$layout = new Layout_locations();
			$layout->set_variant($variant);
		}

		// do the job
		$output = $layout->layout($result);
		return $output;

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
	public static function list_users_by_date($offset=0, $count=10, $variant='full') {
		global $context;

		// limit the scope of the request
		$query = "SELECT * FROM ".SQL::table_name('locations')." AS locations"
			." WHERE locations.anchor LIKE 'user:%'"
			." ORDER BY locations.edit_date DESC, locations.geo_place_name LIMIT ".$offset.','.$count;

		// the list of locations
		$output = Locations::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * locate some reference
	 *
	 * @param string the anchor (e.g., 'article:123')
	 * @return string longitude and latitude of the anchor, else NULL
	 *
	 * @see articles/layout_articles_as_contents.php
	 * @see articles/layout_articles_as_feed.php
	 */
	public static function locate_anchor($anchor) {
		global $context;

		// the request
		$query = "SELECT CONCAT(location.latitude, ', ', location.longitude) as geolocation FROM ".SQL::table_name('locations')." AS location"
			." WHERE (location.anchor LIKE '".SQL::escape($anchor)."') "
			." ORDER BY location.edit_date DESC, location.geo_place_name LIMIT 0, 1";

		// the location, if any
		$output = SQL::query_scalar($query);
		return $output;
	}

	/**
	 * map at Google
	 *
	 * @link http://www.nabble.com/problem-loading-googlemaps-into-jquery-UI-tabs-td15962881s27240.html
	 *
	 * @param array a list of locations
	 * @param int the scale to use
	 * @param string object width
	 * @param string object height
	 * @return string suitable XHTML to be sent to the browser
	 */
	public static function map_on_google($items, $scale=null, $width=null, $height=null) {
		global $context;

		// default values if not defined in skin
		if(!isset($context['skins_gmap_default_width']))
			$context['skins_gmap_default_width'] = '500px';
		if(!isset($context['skins_gmap_default_height']))
			$context['skins_gmap_default_height'] = '300px';
		if(!isset($context['skins_gmap_default_scale']))
			$context['skins_gmap_default_scale'] = '5';

		// default values from the skin
		if(!$scale)
			$scale = $context['skins_gmap_default_scale'];
		if(!$width)
			$width = $context['skins_gmap_default_width'];
		if(!$height)
			$height = $context['skins_gmap_default_height'];

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
		$text .= '<div id="'.$handle.'" style="border: 1px solid #979797; background-color: #e5e3df; width: '.$width.'; height: '.$height.';">'."\n"
			.'	<div style="padding: 1em; color: gray">'.i18n::s('Loading...').'</div>'."\n"
			.'</div>'."\n";

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

		// create this map
		$text .= JS_PREFIX
			.'var mapOptions = {'."\n"
			.'	zoom: 13,'."\n"
			.'	center: new google.maps.LatLng(parseFloat("'.$latitude_middle.'"), parseFloat("'.$longitude_middle.'")),'."\n"
			.'	mapTypeId: google.maps.MapTypeId.ROADMAP'."\n"
			.'};'."\n"
			.'var map = new google.maps.Map($("#'.$handle.'")[0], mapOptions);'."\n";

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
			if(isset($item['anchor']) && ($anchor = Anchors::get($item['anchor'])) && is_object($anchor)) {

				// insert thumbnail, if any
				if($icon = $anchor->get_thumbnail_url())
					$description = '<a href="'.$context['url_to_root'].$anchor->get_url().'"><img src="'.$icon.'" alt="" title="'.encode_field($anchor->get_title()).'" style="float: left; margin-right: 1em; border: none;" /></a>'.$description;

				// a link to the anchor page
				$description .= BR."\n".Skin::build_link($anchor->get_url(), $anchor->get_title());
			}

			// item type
			if(isset($item['anchor']) && (strpos($item['anchor'], 'user:') === 0))
				$icon = 'iconRed';
			else
				$icon = 'iconBlue';

			// add one marker for this item
			$text .= '	var point = new google.maps.LatLng(parseFloat("'.$item['latitude'].'"), parseFloat("'.$item['longitude'].'"));'."\n"
				.'	var marker'.$map_index.$index.' = new google.maps.Marker({ position: point, map: map });'."\n"
				.'	var infoWindow = new google.maps.InfoWindow();'."\n"
				.'google.maps.event.addDomListener(marker'.$map_index.$index.', "click", function() {'."\n"
				.'	infoWindow.setContent("'.addcslashes($description, '\'\\"'."\n\r").'");'."\n"
				.'	infoWindow.open(map, marker'.$map_index.$index.');'."\n"
				.'	});'."\n"
				.'$("body").bind("yacs", function(e) {'."\n"
				.'	google.maps.event.trigger(map, "resize");'."\n"
				.'	map.setZoom( map.getZoom() );'."\n"
				.'	map.setCenter(point);'."\n"
				.'});'."\n";

			// next index
			$index++;
		}

		// the postamble
		$text .= JS_SUFFIX;

		// job done
		return $text;
	}

	/**
	 * load the google map api
	 *
	 * @return string suitable XHTML to be sent to the browser
	 */
	public static function map_on_google_header($verbose = FALSE) {
		global $context;

		// we return some text
		$text = '';

		// load the header only once
		static $fused;
		if(isset($fused))
			return $text;
		$fused = TRUE;

		// load the google library
		$text .= '<script type="text/javascript" src="http://maps.google.com/maps/api/js?v=3&amp;sensor=false"></script>'."\n";

		// load some icons from Google
		$text .= JS_PREFIX
			.'if(typeof google.maps.Icon != "undefined") {'."\n"
			.'	var iconBlue = new google.maps.Icon();'."\n"
			.'	iconBlue.image = "http://labs.google.com/ridefinder/images/mm_20_blue.png";'."\n"
			.'	iconBlue.shadow = "http://labs.google.com/ridefinder/images/mm_20_shadow.png";'."\n"
			.'	iconBlue.iconSize = new google.maps.Size(12, 20);'."\n"
			.'	iconBlue.shadowSize = new google.maps.Size(22, 20);'."\n"
			.'	iconBlue.iconAnchor = new google.maps.Point(6, 20);'."\n"
			.'	iconBlue.infoWindowAnchor = new google.maps.Point(5, 1);'."\n"
			."\n"
			.'	var iconRed = new google.maps.Icon();'."\n"
			.'	iconRed.image = "http://labs.google.com/ridefinder/images/mm_20_red.png";'."\n"
			.'	iconRed.shadow = "http://labs.google.com/ridefinder/images/mm_20_shadow.png";'."\n"
			.'	iconRed.iconSize = new google.maps.Size(12, 20);'."\n"
			.'	iconRed.shadowSize = new google.maps.Size(22, 20);'."\n"
			.'	iconRed.iconAnchor = new google.maps.Point(6, 20);'."\n"
			.'	iconRed.infoWindowAnchor = new google.maps.Point(5, 1);'."\n"
			.'}'
			.JS_SUFFIX;

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
	public static function post(&$fields) {
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
		Surfer::check_default_editor($fields);

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
				."edit_id=".SQL::escape($fields['edit_id']).", "
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
	public static function setup() {
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
	public static function stat() {
		global $context;

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date"
			." FROM ".SQL::table_name('locations')." AS locations";

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

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date "
			." FROM ".SQL::table_name('locations')." AS locations "
			." WHERE locations.anchor LIKE '".SQL::escape($anchor)."'";

		$output = SQL::query_first($query);
		return $output;
	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('locations');

?>
