<?php
/**
 * the file anchor
 *
 * This class implements the Anchor interface for posted files.
 *
 * @see shared/anchor.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class File extends Anchor {

	/**
	 * get next and previous items, if any
	 *
	 * @param string the item type (eg, 'image', 'file', etc.)
	 * @param array the anchored item asking for neighbours
	 * @return an array($previous_url, $previous_label, $next_url, $next_label, $option_url, $option_label), or NULL
	 *
	 * @see shared/anchor.php
	 */
	function get_neighbours($type, $item) {
		global $context;

		// no item bound
		if(!isset($this->item['id']))
			return NULL;

		// initialize components
		$previous_url = $previous_label = $next_url = $next_label = $option_url = $option_label ='';

		// previous and next images
		if($type == 'image') {

			// load the adequate library
			include_once $context['path_to_root'].'images/images.php';

			// extract all images references from the description
			preg_match_all('/\[image=(\d+)/', $this->item['description'], $matches);

			// locate the previous image, if any
			$previous = NULL;
			reset($matches[1]);
			for($index=0; $index < count($matches[1]);$index++) {

                            $value      = current($matches[1]); 
                            $next       = next($matches[1]);
                            
                            // stop loop if value equals current image
                            if($item['id'] === $value) {
                                break;
                            }
                            
                            // loop continues, previous might be this current value
                            $previous   = $value;
                        }

			// make a link to the previous image
			if($previous) {
				$previous_url = Images::get_url($previous);
				$previous_label = i18n::s('Previous');
			}

			// make a link to the next image
			if($next) {
				$next_url = Images::get_url($next);
				$next_label = i18n::s('Next');
			}

			// add a label
			$option_label = sprintf(i18n::s('Image %d of %d'), $index, count($matches[1]));
		}

		// return navigation info
		return array($previous_url, $previous_label, $next_url, $next_label, $option_url, $option_label);
	}

	/**
	 * get the title for this anchor
	 *
	 * @return a string, or NULL if no file is available
	 *
	 * @see shared/anchor.php
	 */
	 function get_title($use_overlay=true) {
		if(!isset($this->item['id']))
			return NULL;
		if(isset($this->item['title']) && $this->item['title'])
			return $this->item['title'];
		if(isset($this->item['file_name']) && $this->item['file_name'])
			return $this->item['file_name'];
	 }

	/**
	 * get the url to display the main page for this anchor
	 *
	 * @param string the targeted action ('view', 'print', 'edit', 'delete', ...)
	 * @return an anchor to the viewing script
	 *
	 * @see shared/anchor.php
	 */
	function get_url($action='view') {
		if(isset($this->item['id']))
			return Files::get_url($this->item['id'], $action, $this->item['file_name']);
		return NULL;
	}

	/**
	 * load the related item
	 *
	 * @param int the id of the record to load
	 * @param boolean TRUE to always fetch a fresh instance, FALSE to enable cache
	 *
	 * @see shared/anchor.php
	 */
	function load_by_id($id, $mutable=FALSE) {
		$this->item = Files::get($id, $mutable);
	}
	
	/**
	 * get permalink to item
	 */
	function get_permalink() {
	    if(!isset($this->item['id']))
		    return NULL;
	    
	    $link = Files::get_permalink($this->item);
	    return $link;
	}
	
	/**
	 * provide classe name with all static functions on this kind of anchor
	 * 
	 * @return a class name
	 */
	function get_static_group_class() {
	    return 'Files';
	}
        
        /**
	 * change some attributes of an anchor
	 *
	 * @see shared/anchor.php
	 *
	 * @param array of (name, value)
	 * @return TRUE on success, FALSE otherwise
	 */
	function set_values($fields) {

		// add our id
		$fields['id'] = $this->item['id'];

		// save in the database
		return Files::put_attributes($fields);

	}

	/**
	 * remember the last action for this file
	 *
	 * This function is called by related items. What does it do?
	 * - On image creation, the adequate code is added to the description field to let the image be displayed inline
	 * - On icon selection, the icon field is updated
	 * - On thumbnail image selection, the thumbnail image field is updated
	 *
	 * Moreover, on any change that impact the edition date (i.e., not in silent mode),
	 * a message is sent to the file creator, if different from the current surfer
	 * and a message is sent to watchers as well.
	 *
	 * @param string one of the pre-defined action code
	 * @param string the id of the item related to this update
	 * @param boolean TRUE to not change the edit date of this anchor, default is FALSE
	 *
	 * @see files/file.php
	 * @see files/edit.php
	 * @see shared/anchor.php
	 */
	function touch($action, $origin=NULL, $silently=FALSE) {
		global $context;

		// don't go further on import
		if(preg_match('/import$/i', $action))
			return;

		// no file bound
		if(!$this->item['id'])
			return;

		// sanity check
		if(!$origin) {
			logger::remember('files/file.php: unexpected NULL origin at touch()');
			return;
		}

		// components of the query
		$query = array();

		// append a reference to a new image to the description
		if($action == 'image:create') {

			// make it the main image for this file
			include_once $context['path_to_root'].'images/images.php';
			if(($image = Images::get($origin)) && ($url = Images::get_icon_href($image)))
				$query[] = "icon_url = '".SQL::escape($url)."'";

			// also use it as thumnail
			if(($image = Images::get($origin)) && ($url = Images::get_thumbnail_href($image)))
				$query[] = "thumbnail_url = '".SQL::escape($url)."'";

			// refresh stamp only if file update occurs within 6 hours after last edition
			if(SQL::strtotime($this->item['edit_date']) + 6*60*60 < time())
				$silently = TRUE;

		// suppress a reference to an image that has been deleted
		} elseif($action == 'image:delete') {

			// suppress reference in main description field
			$query[] = "description = '".SQL::escape(Codes::delete_embedded($this->item['description'], 'image', $origin))."'";

			// suppress references as icon and thumbnail as well
			include_once $context['path_to_root'].'images/images.php';
			if($image = Images::get($origin)) {

				if($url = Images::get_icon_href($image)) {
					if($this->item['icon_url'] == $url)
						$query[] = "icon_url = ''";
					if($this->item['thumbnail_url'] == $url)
						$query[] = "thumbnail_url = ''";
				}

				if($url = Images::get_thumbnail_href($image)) {
					if($this->item['icon_url'] == $url)
						$query[] = "icon_url = ''";
					if($this->item['thumbnail_url'] == $url)
						$query[] = "thumbnail_url = ''";
				}
			}

		// set an existing image as the file icon
		} elseif($action == 'image:set_as_icon') {
			include_once $context['path_to_root'].'images/images.php';
			if($image = Images::get($origin)) {
				if($url = Images::get_icon_href($image))
					$query[] = "icon_url = '".SQL::escape($url)."'";

				// also use it as thumnail if none has been defined yet
				if(!(isset($this->item['thumbnail_url']) && trim($this->item['thumbnail_url'])) && ($url = Images::get_thumbnail_href($image)))
					$query[] = "thumbnail_url = '".SQL::escape($url)."'";

			} elseif($origin) {
				$query[] = "icon_url = '".SQL::escape($origin)."'";
			}

		// set an existing image as the file thumbnail
		} elseif($action == 'image:set_as_thumbnail') {
			include_once $context['path_to_root'].'images/images.php';
			if($image = Images::get($origin)) {
				if($url = Images::get_thumbnail_href($image))
					$query[] = "thumbnail_url = '".SQL::escape($url)."'";
			} elseif($origin) {
				$query[] = "thumbnail_url = '".SQL::escape($origin)."'";
			}

			// do not remember minor changes
			$silently = TRUE;

		// append a new image, and set it as the file thumbnail
		} elseif($action == 'image:set_as_both') {
			if(!Codes::check_embedded($this->item['description'], 'image', $origin))
				$query[] = "description = '".SQL::escape($this->item['description'].' [image='.$origin.']')."'";

			include_once $context['path_to_root'].'images/images.php';
			if($image = Images::get($origin)) {
				if($url = Images::get_thumbnail_href($image))
					$query[] = "thumbnail_url = '".SQL::escape($url)."'";
			} elseif($origin) {
				$query[] = "thumbnail_url = '".SQL::escape($origin)."'";
			}

			// do not remember minor changes
			$silently = TRUE;

		}

		// stamp the update
		if(!$silently)
			$query[] = "edit_name='".SQL::escape(Surfer::get_name())."',"
				."edit_id=".SQL::escape(Surfer::get_id()).","
				."edit_address='".SQL::escape(Surfer::get_email_address())."',"
				."edit_action='".SQL::escape($action)."',"
				."edit_date='".gmdate('Y-m-d H:i:s')."'";

		// ensure we have a valid update query
		if(!@count($query))
			return;

		// update the anchor file
		$query = "UPDATE ".SQL::table_name('files')." SET ".implode(', ',$query)
			." WHERE id = ".SQL::escape($this->item['id']);
		if(SQL::query($query) === FALSE)
			return;

		// always clear the cache, even on no update
		Files::clear($this->item);

		// get the parent
		if(!$this->anchor)
			$this->anchor = Anchors::get($this->item['anchor']);

		// propagate the touch upwards silently -- we only want to purge the cache
		if(is_object($this->anchor))
			$this->anchor->touch('file:update', $this->item['id'], TRUE);

	}

	/**
	 * transcode some references
	 *
	 * @param array of pairs of strings to be used in preg_replace()
	 *
	 * @see images/images.php
	 */
	function transcode($transcoded) {
		global $context;

		// no item bound
		if(!isset($this->item['id']))
			return;

		// prepare preg_replace()
		$from = array();
		$to = array();
		foreach($transcoded as $pair) {
			$from[] = $pair[0];
			$to[] = $pair[1];
		}

		// transcode various fields
		$this->item['description'] = preg_replace($from, $to, $this->item['description']);

		// update the database
		$query = "UPDATE ".SQL::table_name('files')." SET "
			." description = '".SQL::escape($this->item['description'])."'"
			." WHERE id = ".SQL::escape($this->item['id']);
		SQL::query($query);

		// always clear the cache, even on no update
		Files::clear($this->item);

	}

}

// stop hackers
defined('YACS') or exit('Script must be included');

?>
