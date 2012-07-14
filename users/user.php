<?php
/**
 * the implementation of anchor for users
 *
 * This class implements the Anchor interface for community members.
 *
 * Following behaviour has been defined for member functions:
 * get_icon_url() -- reuse the user icon url, if any
 * get_path_bar() -- one link to the user page
 * get_prefix() -- provides the user introduction
 * get_reference() -- returns 'user:&lt;id&gt;'
 * get_suffix() -- the null string
 * get_title() -- provide the user full name
 * get_thumbnail_url() -- reuse the user thumbnail url, if any
 * get_url() -- link to users/view.php/id
 * has_option()
 * is_assigned()
 * touch() -- remember the last action in the user record
 *
 * @author Bernard Paques
 * @author GnapZ
 * @see shared/anchor.php
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class User extends Anchor {

	/**
	 * get the url to display the icon for this anchor
	 *
	 * @return an anchor to the icon image
	 *
	 * @see shared/anchor.php
	 */
	function get_icon_url() {
		if(isset($this->item['avatar_url']))
			return $this->item['avatar_url'];
		return NULL;
	}

	/**
	 * get next and previous items, if any
	 *
	 * @param string the item type (eg, 'image', 'file', etc.)
	 * @param array the anchored item asking for neighbours
	 * @return an array($previous_url, $previous_label, $next_url, $next_label, $option_url, $option_label)
	 *
	 * @see shared/anchor.php
	 */
	function get_neighbours($type, $item) {
		global $context;

		// no item bound
		if(!isset($this->item['id']))
			return NULL;

		// load localized strings
		i18n::bind('users');

		// initialize components
		$previous_url = $previous_label = $next_url = $next_label = $option_url = $option_label ='';

		// previous and next actions
		if($type == 'action') {

			// load the adequate library
			include_once $context['path_to_root'].'actions/actions.php';

			$order = 'date';

			// get previous url
			if($previous_url = Actions::get_previous_url($item, 'user:'.$this->item['id'], $order))
				$previous_label = i18n::s('Previous action');

			// get next url
			if($next_url = Actions::get_next_url($item, 'user:'.$this->item['id'], $order))
				$next_label = i18n::s('Next action');

		// previous and next comments
		} elseif($type == 'comment') {

			// load the adequate library
			include_once $context['path_to_root'].'comments/comments.php';

			$order = 'date';

			// get previous url
			if($previous_url = Comments::get_previous_url($item, 'user:'.$this->item['id'], $order))
				$previous_label = i18n::s('Previous comment');

			// get next url
			if($next_url = Comments::get_next_url($item, 'user:'.$this->item['id'], $order))
				$next_label = i18n::s('Next comment');

		// previous and next files
		} elseif($type == 'file') {

			// select appropriate order
			if(preg_match('/\bfiles_by_title\b/', $this->item['options']))
				$order = 'title';
			else
				$order = 'date';

			// get previous url
			if($previous_url = Files::get_previous_url($item, 'user:'.$this->item['id'], $order))
				$previous_label = i18n::s('Previous file');

			// get next url
			if($next_url = Files::get_next_url($item, 'user:'.$this->item['id'], $order))
				$next_label = i18n::s('Next file');

		// previous and next images
		} elseif($type == 'image') {

			// load the adequate library
			include_once $context['path_to_root'].'images/images.php';

			// extract all images references from the description
			preg_match_all('/\[image=(\d+)/', $this->item['description'], $matches);

			// locate the previous image, if any
			$previous = NULL;
			reset($matches[1]);
			$index = 0;
			while(list($key, $value) = each($matches[1])) {
				$index++;
				if($item['id'] == $value)
					break;
				$previous = $value;
			}

			// make a link to the previous image
			if($previous) {
				$previous_url = Images::get_url($previous);
				$previous_label = i18n::s('Previous image');
			}

			// locate the next image, if any
			if(!list($key, $next) = each($matches[1]))
				$next = NULL;

			// make a link to the next image
			else {
				$next_url = Images::get_url($next);
				$next_label = i18n::s('Next image');
			}

			// add a label
			$option_label = sprintf(i18n::s('Image %d of %d'), $index, count($matches[1]));

		// previous and next location
		} elseif($type == 'location') {

			// load the adequate library
			include_once $context['path_to_root'].'locations/locations.php';

			// extract all location references from the description
			preg_match_all('/\[location=(\d+)/', $this->item['description'], $matches);

			// locate the previous location, if any
			$previous = NULL;
			reset($matches[1]);
			$index = 0;
			while(list($key, $value) = each($matches[1])) {
				$index++;
				if($item['id'] == $value)
					break;
				$previous = $value;
			}

			// make a link to the previous location
			if($previous) {
				$previous_url = Locations::get_url($previous);
				$previous_label = i18n::s('Previous location');
			}

			// locate the next location, if any
			if(!list($key, $next) = each($matches[1]))
				$next = NULL;

			// make a link to the next image
			else {
				$next_url = Locations::get_url($next);
				$next_label = i18n::s('Next location');
			}

			// add a label
			$option_label = sprintf(i18n::s('Location %d of %d'), $index, count($matches[1]));

		}

		// return navigation info
		return array($previous_url, $previous_label, $next_url, $next_label, $option_url, $option_label);
	}

	/**
	 * get the path bar for this anchor
	 *
	 * For users, the path bar is made of one stem for all users, then one stem for the user himself.
	 *
	 * @return an array of $url => $label
	 *
	 * @see shared/anchor.php
	 */
	function get_path_bar() {

		// load localized strings
		i18n::bind('users');

		// the index of users
		$output = array('users/' => i18n::s('People'));

		// then this user
		if(isset($this->item['id'])) {
			$url = $this->get_url();
			if(isset($this->item['full_name']) && $this->item['full_name'])
				$label = $this->item['full_name'];
			else
				$label = $this->item['nick_name'];
			$output = array_merge($output, array($url => $label));
		}

		// return an array of ($url => $label)
		 return $output;
	 }

	/**
	 * get the reference for this anchor
	 *
	 * @return 'user:&lt;id&gt;', or NULL
	 *
	 * @see shared/anchor.php
	 */
	function get_reference() {
		if(isset($this->item['id']))
			return 'user:'.$this->item['id'];
		return NULL;
	}

	/**
	 * get the title for this anchor
	 *
	 * @return a string
	 *
	 * @see shared/anchor.php
	 */
	 function get_title() {
		if(!isset($this->item['id']))
			return '???';

		if($this->item['full_name'])
			return str_replace('&', '&amp;', $this->item['full_name']);

		return str_replace('&', '&amp;', $this->item['nick_name']);
	 }

	/**
	 * get the url to display the thumbnail for this anchor
	 *
	 * @return an anchor to the thumbnail image
	 *
	 * @see shared/anchor.php
	 */
	function get_thumbnail_url() {
		if(isset($this->item['avatar_url']))
			return $this->item['avatar_url'];
		return NULL;
	}

	/**
	 * get the url to display the main page for this anchor
	 *
	 * @param string the targeted action ('view', 'print', 'edit', 'delete', ...)
	 * @return an anchor to the viewing script, or NULL
	 *
	 * @see shared/anchor.php
	 */
	function get_url($action='view') {

		// sanity check
		if(!isset($this->item['id']))
			return NULL;

		switch($action) {

		// list of files
		case 'files':
			return Users::get_permalink($this->item).'#_information';

		// the permalink page
		case 'view':
			return Users::get_permalink($this->item);

		// another action
		default:
			return Users::get_url($this->item['id'], $action, $this->item['nick_name']);

		}

	 }

	/**
	 * check that the surfer is an editor of a user profile
	 *
	 * @param int optional reference to some user profile
	 * @return TRUE if the surfer is browsing his/her own profile
	 *
	 * @param int optional reference to some user profile
	 * @param boolean TRUE to climb the list of containers up to the top
	 * @return TRUE or FALSE
	 *
	 * @see shared/anchor.php
	 */
	function is_assigned($user_id=NULL, $cascade=TRUE) {

		// id of requesting user
		if(!$user_id && Surfer::get_id())
			$user_id = Surfer::get_id();

		if(isset($this->item['id']) && ($user_id == $this->item['id']))
			return TRUE;

		return FALSE;
	 }

	/**
	 * check that the surfer owns an anchor
	 *
	 * To be overloaded into derived class if field has a different name
	 *
	 * @param int optional reference to some user profile
	 * @param boolean TRUE to not cascade the check to parent containers
	 * @return TRUE or FALSE
	 *
	 * @see shared/anchor.php
	 */
	function is_owned($user_id=NULL, $strict=FALSE) {
		global $context;

		// id of requesting user
		if(!$user_id) {
			if(!Surfer::get_id())
				return FALSE;
			$user_id = Surfer::get_id();
		}

		// associates can always do it, except in strict mode
		if(!$strict && ($user_id == Surfer::get_id()) && Surfer::is_associate())
			return TRUE;

		// surfer owns this item
		if(isset($this->item['id']) && ($user_id == $this->item['id']))
			return TRUE;

		// sorry
		return FALSE;
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
		$this->item = Users::get($id, $mutable);
	}

	/**
	 * remember the last action for this user
	 *
	 * @param string the description of the last action
	 * @param string the id of the item related to this update
	 * @param boolean TRUE to not change the edit date of this anchor, default is FALSE
	 * @param boolean TRUE to notify section watchers, default is FALSE
	 * @param boolean TRUE to notify poster followers, default is FALSE
	 *
	 * @see shared/anchor.php
	 */
	function touch($action, $origin=NULL, $silently=FALSE, $to_watchers=FALSE, $to_followers=FALSE) {
		global $context;

		// don't go further on import
		if(preg_match('/import$/i', $action))
			return;

		// no item bound
		if(!isset($this->item['id']))
			return;

		// sanity check
		if(!$origin) {
			logger::remember('users/user.php', 'unexpected NULL origin at touch()');
			return;
		}

		// components of the query
		$query = array();

		// append a reference to a new image to the description
		if($action == 'image:create') {
			if(!Codes::check_embedded($this->item['description'], 'image', $origin)) {

				// list has already started
				if(preg_match('/\[image=[^\]]+?\]\s*$/', $this->item['description']))
					$query[] = "description = '".SQL::escape($this->item['description'].' [image='.$origin.']')."'";

				// starting a new list of images
				else
					$query[] = "description = '".SQL::escape($this->item['description']."\n\n".'[image='.$origin.']')."'";
			}

			// refresh stamp only if image update occurs within 6 hours after last edition
			if(SQL::strtotime($this->item['edit_date']) + 6*60*60 < time())
				$silently = TRUE;

		// suppress a reference to an image that has been deleted
		} elseif($action == 'image:delete') {

			// suppress reference in main description field
			$query[] = "description = '".SQL::escape(Codes::delete_embedded($this->item['description'], 'image', $origin))."'";

			// suppress references as icon and thumbnail as well
			include_once $context['path_to_root'].'images/images.php';
			if($image =& Images::get($origin)) {

				if($url = Images::get_icon_href($image)) {
					if($this->item['avatar_url'] == $url)
						$query[] = "avatar_url = ''";
				}

				if($url = Images::get_thumbnail_href($image)) {
					if($this->item['avatar_url'] == $url)
						$query[] = "avatar_url = ''";
				}
			}

		// set an existing image as the user avatar
		} elseif($action == 'image:set_as_avatar') {
			include_once $context['path_to_root'].'images/images.php';
			if($image =& Images::get($origin)) {
				if($url = Images::get_icon_href($image))
					$query[] = "avatar_url = '".SQL::escape($url)."'";
			}
			$silently = TRUE;

		// set an existing image as the user thumbnail
		} elseif($action == 'image:set_as_thumbnail') {
			include_once $context['path_to_root'].'images/images.php';
			if($image =& Images::get($origin)) {
				if($url = Images::get_thumbnail_href($image))
					$query[] = "avatar_url = '".SQL::escape($url)."'";
			}
			$silently = TRUE;

		// append a new image
		} elseif($action == 'image:set_as_both') {
			if(!Codes::check_embedded($this->item['description'], 'image', $origin))
				$query[] = "description = '".SQL::escape($this->item['description'].' [image='.$origin.']')."'";

			// do not remember minor changes
			$silently = TRUE;

		// add a reference to a location in the article description
		} elseif($action == 'location:create') {
			if(!Codes::check_embedded($this->item['description'], 'location', $origin))
				$query[] = "description = '".SQL::escape($this->item['description'].' [location='.$origin.']')."'";

		// suppress a reference to a location that has been deleted
		} elseif($action == 'location:delete') {
			$query[] = "description = '".SQL::escape(Codes::delete_embedded($this->item['description'], 'location', $origin))."'";

		// add a reference to a new table in the user description
		} elseif($action == 'table:create') {
			if(!Codes::check_embedded($this->item['description'], 'table', $origin))
				$query[] = "description = '".SQL::escape($this->item['description'].' [table='.$origin.']')."'";

		// suppress a reference to a table that has been deleted
		} elseif($action == 'table:delete') {
			$query[] = "description = '".SQL::escape(Codes::delete_embedded($this->item['description'], 'table', $origin))."'";

		}

		// stamp the update
		if(!$silently)
			$query[] = "edit_name='".SQL::escape(Surfer::get_name())."',"
				."edit_id=".SQL::escape(Surfer::get_id()).","
				."edit_address='".SQL::escape(Surfer::get_email_address())."',"
				."edit_action='$action',"
				."edit_date='".SQL::escape(gmstrftime('%Y-%m-%d %H:%M:%S'))."'";

		// clear the cache for users, even for minor updates (e.g., image deletion)
		Users::clear($this->item);

		// ensure we have a valid update query
		if(!@count($query))
			return;

		// update the anchor user
		$query = "UPDATE ".SQL::table_name('users')." SET ".implode(', ',$query)
			." WHERE id = ".SQL::escape($this->item['id']);
		SQL::query($query, FALSE, $context['users_connection']);

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
		$this->item['introduction'] = preg_replace($from, $to, $this->item['introduction']);
		$this->item['description'] = preg_replace($from, $to, $this->item['description']);

		// update the database
		$query = "UPDATE ".SQL::table_name('users')." SET "
			." introduction = '".SQL::escape($this->item['introduction'])."',"
			." description = '".SQL::escape($this->item['description'])."'"
			." WHERE id = ".SQL::escape($this->item['id']);
		SQL::query($query, FALSE, $context['users_connection']);

		// always clear the cache, even on no update
		Users::clear($this->item);

	}

}

// stop hackers
defined('YACS') or exit('Script must be included');

?>
