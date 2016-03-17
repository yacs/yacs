<?php
/**
 * the database abstraction layer for images
 *
 * Images are saved into the file system of the web server. Each image also has a related record in the database.
 *
 * @author Bernard Paques
 * @author Florent
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Guillaume Perez
 * @tester Anatoly
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Images {

	/**
	 * check if new images can be added
	 *
	 * This function returns TRUE if images can be added to some place,
	 * and FALSE otherwise.
	 *
	 * @param object an instance of the Anchor interface, if any
	 * @param array a set of item attributes, if any
	 * @param string the type of item, e.g., 'section'
	 * @return TRUE or FALSE
	 */
	public static function allow_creation($item=NULL, $anchor=NULL, $variant=NULL) {
		global $context;
                
                // backward compatibility, reverse parameters : 
                // $anchor is always a object and $item a array
                if(is_object($item) || is_array($anchor)) {
                    $permute    = $anchor;
                    $anchor     = $item;
                    $item       = $permute;
                }

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

			// 'no images' option
			if(Articles::has_option('no_images', $anchor, $item))
				return FALSE;

		// other containers
		} else {

			// in item
			if(isset($item['options']) && is_string($item['options']) && preg_match('/\bno_images\b/i', $item['options']))
				return FALSE;

			// in container
			if(is_object($anchor) && $anchor->has_option('no_images', FALSE))
				return FALSE;

		}

		// surfer is not allowed to upload a file
		if(!Surfer::may_upload())
			return FALSE;

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// submissions have been disallowed
		if(isset($context['users_without_submission']) && ($context['users_without_submission'] == 'Y'))
			return FALSE;

		// only in articles
		if($variant == 'article') {

			// surfer is entitled to change content
			if(Articles::allow_modification($item, $anchor))
				return TRUE;

			// surfer is an editor, and the page is not private
			if(isset($item['active']) && ($item['active'] != 'N') && Articles::is_assigned($item['id']))
				return TRUE;
			if(is_object($anchor) && !$anchor->is_hidden() && $anchor->is_assigned())
				return TRUE;

		// only in iles
		} elseif($variant == 'file') {

			// surfer owns the anchor
			if(is_object($anchor) && $anchor->is_owned())
				return TRUE;

		// only in sections
		} elseif($variant == 'section') {

			// surfer is entitled to change content
			if(Sections::allow_modification($item, $anchor))
				return TRUE;

		// only in user profiles
		} elseif($variant == 'user') {

			// the item is anchored to the profile of this member
			if(Surfer::get_id() && is_object($anchor) && !strcmp($anchor->get_reference(), 'user:'.Surfer::get_id()))
				return TRUE;

			// should not happen...
			if(isset($item['id']) && Surfer::is($item['id']))
				return TRUE;

		}

		// item has been locked
		if(isset($item['locked']) && ($item['locked'] == 'Y'))
			return FALSE;

		// anchor has been locked --only used when there is no item provided
		if(!isset($item['id']) && is_object($anchor) && $anchor->has_option('locked'))
			return FALSE;

		// not for subscribers
		if(Surfer::is_member()) {

			// surfer is an editor (and item has not been locked)
			if(($variant == 'article') && isset($item['id']) && Articles::is_assigned($item['id']))
				return TRUE;

			// surfer is assigned to parent container
			if(is_object($anchor) && $anchor->is_assigned())
				return TRUE;

		}

		// container is hidden
		if(isset($item['active']) && ($item['active'] == 'N'))
			return FALSE;
		if(is_object($anchor) && $anchor->is_hidden())
			return FALSE;

		// authenticated members are allowed to add images to pages
		if(($variant == 'article') && Surfer::is_logged())
			return TRUE;

		// container is restricted
		if(isset($item['active']) && ($item['active'] == 'R'))
			return FALSE;
		if(is_object($anchor) && !$anchor->is_public())
			return FALSE;

		// anonymous contributions are allowed for articles
		if($variant == 'article') {
			if(isset($item['options']) && preg_match('/\banonymous_edit\b/i', $item['options']))
				return TRUE;
			if(is_object($anchor) && $anchor->has_option('anonymous_edit'))
				return TRUE;
		}

		// the default is to not allow for new images
		return FALSE;
	}

	/**
	 * check if an image can be modified
	 *
	 * This function returns TRUE if an image can be edited to some place,
	 * and FALSE otherwise.
	 *
	 * @param object an instance of the Anchor interface, if any
	 * @param array a set of item attributes, if any
	 * @return TRUE or FALSE
	 */
	public static function allow_modification($anchor, $item) {
		global $context;

		// associates can do what they want
		if(Surfer::is_associate())
			return TRUE;

		// the item is anchored to the profile of this member
 		if(Surfer::is_member() && isset($item['anchor']) && !strcmp($item['anchor'], 'user:'.Surfer::get_id()))
 			return TRUE;

		// you can handle your own images
		if(isset($item['edit_id']) && Surfer::is($item['edit_id']) && is_object($anchor) && !$anchor->has_option('locked'))
			return TRUE;

		// owner
		if(is_object($anchor) && $anchor->is_owned())
			return TRUE;

		// editor of a public page
		if(is_object($anchor) && !$anchor->is_hidden() && $anchor->is_assigned())
			return TRUE;

		// the default is to not allow modifications
		return FALSE;
	}

	/**
	 * clear cache entries for one item
	 *
	 * @param array item attributes
	 */
	public static function clear(&$item) {

		// where this item can be displayed
		$topics = array('articles', 'categories', 'files', 'images', 'sections', 'users');

		// clear anchor page
		if(isset($item['anchor']))
			$topics[] = $item['anchor'];

		// clear this page
		if(isset($item['id']))
			$topics[] = 'image:'.$item['id'];

		// clear the cache
		Cache::clear($topics);

	}

	/**
	 * delete one image in the database and in the file system
	 *
	 * @param int the id of the image to delete
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	public static function delete($id) {
		global $context;

		// load the row
		$item = Images::get($id);
		if(!$item['id']) {
			Logger::error(i18n::s('No item has been found.'));
			return FALSE;
		}

		// delete the image files silently
		$file_path = $context['path_to_root'].Files::get_path($item['anchor'], 'images');
		Safe::unlink($file_path.'/'.$item['image_name']);
		Safe::unlink($file_path.'/'.$item['thumbnail_name']);
		Safe::rmdir($file_path.'/thumbs');
		Safe::rmdir($file_path);
		Safe::rmdir(dirname($file_path));

		// delete related items
		Anchors::delete_related_to('image:'.$id);

		// delete the record in the database
		$query = "DELETE FROM ".SQL::table_name('images')." WHERE id = ".SQL::escape($item['id']);
		if(SQL::query($query) === FALSE)
			return FALSE;

		// job done
		return TRUE;
	}

	/**
	 * delete all images for a given anchor
	 *
	 * @param the anchor to check
	 *
	 * @see shared/anchors.php
	 */
	public static function delete_for_anchor($anchor) {
		global $context;

		// seek all records attached to this anchor
		$query = "SELECT id FROM ".SQL::table_name('images')." AS images "
			." WHERE images.anchor LIKE '".SQL::escape($anchor)."'";
		if(!$result = SQL::query($query))
			return;

		// delete silently all matching images
		while($row = SQL::fetch($result))
			Images::delete($row['id']);
	}

	/**
	 * duplicate all images for a given anchor
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
		$query = "SELECT * FROM ".SQL::table_name('images')." WHERE anchor LIKE '".SQL::escape($anchor_from)."'";
		if(($result = SQL::query($query)) && SQL::count($result)) {

			// create target folders
			$file_to = $context['path_to_root'].Files::get_path($item['anchor'], 'images');
			if(!Safe::make_path($file_to.'/thumbs'))
				Logger::error(sprintf(i18n::s('Impossible to create path %s.'), $file_to.'/thumbs'));

			$file_to = $context['path_to_root'].$file_to.'/';

			// the list of transcoded strings
			$transcoded = array();

			// process all matching records one at a time
			$file_from = $context['path_to_root'].Files::get_path($anchor_from, 'images');
			while($item = SQL::fetch($result)) {

				// sanity check
				if(!file_exists($context['path_to_root'].$file_from.'/'.$item['image_name']))
					continue;

				// duplicate image file
				if(!copy($context['path_to_root'].$file_from.'/'.$item['image_name'], $file_to.$item['image_name'])) {
					Logger::error(sprintf(i18n::s('Impossible to copy file %s.'), $item['image_name']));
					continue;
				}

				// this will be filtered by umask anyway
				Safe::chmod($file_to.$item['image_name'], $context['file_mask']);

				// copy the thumbnail as well
				Safe::copy($context['path_to_root'].$file_from.'/'.$item['thumbnail_name'], $file_to.$item['thumbnail_name']);

				// this will be filtered by umask anyway
				Safe::chmod($file_to.$item['thumbnail_name'], $context['file_mask']);

				// a new id will be allocated
				$old_id = $item['id'];
				unset($item['id']);

				// target anchor
				$item['anchor'] = $anchor_to;

				// actual duplication
				if($new_id = Images::post($item)) {

					// more pairs of strings to transcode --no automatic solution for [images=...]
					$transcoded[] = array('/\[image='.preg_quote($old_id, '/').'/i', '[image='.$new_id);

					// duplicate elements related to this item
					Anchors::duplicate_related_to('image:'.$old_id, 'image:'.$new_id);

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
	 * get one image by id
	 *
	 * @param int the id of the image
	 * @return the resulting $item array, with at least keys: 'id', 'title', etc.
	 */
	public static function get($id) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// select among available items -- exact match
		$query = "SELECT * FROM ".SQL::table_name('images')." AS images "
			." WHERE (images.id = ".SQL::escape($id).")";

		$output = SQL::query_first($query);
		return $output;
	}

	/**
	 * get one image by anchor and name
	 *
	 * @param string the anchor
	 * @param string the image name
	 * @return the resulting $item array, with at least keys: 'id', 'title', etc.
	 */
	public static function &get_by_anchor_and_name($anchor, $name) {
		global $context;

		// select among available items
		$query = "SELECT * FROM ".SQL::table_name('images')." AS images "
			." WHERE images.anchor LIKE '".SQL::escape($anchor)."' AND images.image_name='".SQL::escape($name)."'";

		$output = SQL::query_first($query);
		return $output;
	}

	/**
	 * get the web address of an image
	 *
	 * @param array the image description (i.e., Images::get($id))
	 * @return string the target href, or FALSE if there is an error
	 */
	public static function get_icon_href($item) {
		global $context;

		// sanity check
		if(!isset($item['anchor']) || !isset($item['image_name']))
			return FALSE;

		// file does not exist
		if(!file_exists($context['path_to_root'].Files::get_path($item['anchor'], 'images').'/'.$item['image_name']))
			return FALSE;

		// web address of the image
		return $context['url_to_root'].Files::get_path($item['anchor'], 'images').'/'.rawurlencode($item['image_name']);
	}

	/**
	 * get the newest image for a given anchor
	 *
	 * @param string the anchor
	 * @return the resulting $item array, with at least keys: 'id', 'title', etc.
	 */
	public static function &get_newest_for_anchor($anchor) {
		global $context;

		// select among available items
		$query = "SELECT * FROM ".SQL::table_name('images')." AS images "
			." WHERE images.anchor LIKE '".SQL::escape($anchor)."' ORDER BY images.edit_date DESC LIMIT 0, 1";

		$output = SQL::query_first($query);
		return $output;
	}

	/**
	 * get the web address of one thumbnail
	 *
	 * @param array the image description (i.e., Images::get($id))
	 * @return string the thumbnail href, or FALSE on error
	 */
	public static function get_thumbnail_href($item) {
		global $context;

		// sanity check
		if(!isset($item['anchor']) || !isset($item['thumbnail_name']))
			return FALSE;

		// file does not exist
		if(!file_exists($context['path_to_root'].Files::get_path($item['anchor'], 'images').'/'.$item['thumbnail_name']))
			return FALSE;

		// returns href
		return $context['url_to_root'].Files::get_path($item['anchor'], 'images').'/'.str_replace('thumbs%2F', 'thumbs/', rawurlencode($item['thumbnail_name']));

	}

	/**
	 * build a reference to a image
	 *
	 * Depending on parameter '[code]with_friendly_urls[/code]' and on action,
	 * following results can be observed:
	 *
	 * - view - images/view.php?id=123 or images/view.php/123 or image-123
	 *
	 * - other - images/edit.php?id=123 or images/edit.php/123 or image-edit/123
	 *
	 * @param int the id of the image to handle
	 * @param string the expected action ('view', 'print', 'edit', 'delete', ...)
	 * @return string a normalized reference
	 *
	 * @see control/configure.php
	 */
	public static function get_url($id, $action='view') {
		global $context;

		// check the target action
		if(!preg_match('/^(delete|edit|set_as_icon|set_as_thumbnail|view)$/', $action))
			return 'images/'.$action.'.php?id='.urlencode($id);

		// normalize the link
		return normalize_url(array('images', 'image'), $action, $id);
	}

	/**
	 * list newest images
	 *
	 * To build a simple box of the newest images in your main index page, just use
	 * the following example:
	 * [php]
	 * // side bar with the list of most recent images
	 * include_once 'images/images.php';
	 * $items = Images::list_by_date(0, 10, '');
	 * $text = Skin::build_list($items, 'compact');
	 * $context['text'] .= Skin::build_box($title, $text, 'navigation');
	 * [/php]
	 *
	 * You can also display the newest image separately, using [code]Images::get_newest()[/code]
	 * In this case, skip the very first image in the list by using
	 * [code]Images::list_by_date(1, 10, '')[/code]
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 * @see images/images.php#list_selected for $variant description
	 */
	public static function &list_by_date($offset=0, $count=10, $variant='full') {
		global $context;

		// limit the scope of the request
		$query = "SELECT * FROM ".SQL::table_name('images')." AS images"
			." ORDER BY images.edit_date DESC, images.title LIMIT ".$offset.','.$count;

		// the list of images
		$output =& Images::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list newest images for one anchor
	 *
	 * Example:
	 * [php]
	 * include_once 'images/images.php';
	 * $items = Images::list_by_date_for_anchor('section:12', 0, 10);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 * [/php]
	 *
	 * @param string the anchor (e.g., 'article:123')
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 * @see images/images.php#list_selected for $variant description
	 */
	public static function &list_by_date_for_anchor($anchor, $offset=0, $count=50, $variant=NULL) {
		global $context;

		// use the anchor itself as the default variant
		if(!$variant)
			$variant = $anchor;

		// the request
		$query = "SELECT * FROM ".SQL::table_name('images')." AS images "
			." WHERE (images.anchor LIKE '".SQL::escape($anchor)."') "
			." ORDER BY images.edit_date DESC, images.title LIMIT ".$offset.','.$count;

		// the list of images
		$output =& Images::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list newest images for one author
	 *
	 * Example:
	 * [code]
	 * include_once 'images/images.php';
	 * $items = Images::list_by_date_for_author(12, 0, 10);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 * [/code]
	 *
	 * @param int the id of the author of the image
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 * @see images/images.php#list_selected for $variant description
	 */
	public static function &list_by_date_for_author($author_id, $offset=0, $count=20, $variant='date') {
		global $context;

		// limit the scope of the request
		$query = "SELECT * FROM ".SQL::table_name('images')." AS images "
			." WHERE (images.edit_id = ".SQL::escape($author_id).")"
			." ORDER BY images.edit_date DESC, images.title LIMIT ".$offset.','.$count;

		// the list of images
		$output =& Images::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list biggest images
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	public static function &list_by_size($offset=0, $count=10, $variant='full') {
		global $context;

		// limit the scope of the request
		$query = "SELECT * FROM ".SQL::table_name('images')." AS images"
			." ORDER BY images.image_size DESC, images.title LIMIT ".$offset.','.$count;

		// the list of images
		$output =& Images::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list selected images
	 *
	 * If variant is provided as a string, the functions looks for a script featuring this name.
	 * E.g., for variant 'compact', the file 'images/layout_images_as_compact.php' is loaded.
	 * If no file matches then the default 'images/layout_images.php' script is loaded.
	 *
	 * @param resource result of database query
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	public static function &list_selected($result, $variant='compact') {
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
			$name = 'layout_images_as_'.$attributes[0];
			if(is_readable($context['path_to_root'].'images/'.$name.'.php')) {
				include_once $context['path_to_root'].'images/'.$name.'.php';
				$layout = new $name;

				// provide parameters to the layout
				if(isset($attributes[1]))
					$layout->set_variant($attributes[1]);

			}
		}

		// use default layout
		if(!$layout) {
			include_once $context['path_to_root'].'images/layout_images.php';
			$layout = new Layout_images();
			$layout->set_variant($variant);
		}

		// do the job
		$output = $layout->layout($result);
		return $output;

	}

	/**
	 * post a new image or an updated image
	 *
	 * Accept following situations:
	 * - id+image: update an existing entry in the database
	 * - id+no image: only update the database
	 * - no id+image: create a new entry in the database
	 * - no id+no image: create a new entry in the database
	 *
	 * This function populates the error context, where applicable.
	 *
	 * @param array an array of fields
	 * @return the id of the image, or FALSE on error
	**/
	public static function post(&$fields) {
		global $context;

		// no anchor reference
		if(!isset($fields['anchor']) || !$fields['anchor']) {
			Logger::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// get the anchor
		if(!$anchor = Anchors::get($fields['anchor'])) {
			Logger::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// set default values
		if(!isset($fields['use_thumbnail']) || !Surfer::get_id())
			$fields['use_thumbnail'] = 'Y'; 	// only authenticated users can select to not moderate image sizes

		// set default values for this editor
		Surfer::check_default_editor($fields);

		// update the existing record
		if(isset($fields['id'])) {

			// id cannot be empty
			if(!isset($fields['id']) || !is_numeric($fields['id'])) {
				Logger::error(i18n::s('No item has the provided id.'));
				return FALSE;
			}

			$query = "UPDATE ".SQL::table_name('images')." SET ";
			if(isset($fields['image_name']) && ($fields['image_name'] != 'none')) {
				$query .= "image_name='".SQL::escape($fields['image_name'])."',"
					."thumbnail_name='".SQL::escape($fields['thumbnail_name'])."',"
					."image_size='".SQL::escape($fields['image_size'])."',"
					."edit_name='".SQL::escape($fields['edit_name'])."',"
					."edit_id=".SQL::escape($fields['edit_id']).","
					."edit_address='".SQL::escape($fields['edit_address'])."',"
					."edit_date='".SQL::escape($fields['edit_date'])."',";
			}
			$query .= "title='".SQL::escape(isset($fields['title']) ? $fields['title'] : '')."',"
				."use_thumbnail='".SQL::escape($fields['use_thumbnail'])."',"
				."description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."',"
				."source='".SQL::escape(isset($fields['source']) ? $fields['source'] : '')."',"
				."link_url='".SQL::escape(isset($fields['link_url']) ? $fields['link_url'] : '')."'";
                        
                        if(isset($fields['tags']))
                            $query .= ",tags='".SQL::escape($fields['tags'])."'";
                        
			$query .= " WHERE id = ".SQL::escape($fields['id']);

			// actual update
			if(SQL::query($query) === FALSE)
				return FALSE;

		// insert a new record
		} elseif(isset($fields['image_name']) && $fields['image_name'] && isset($fields['image_size']) && $fields['image_size']) {

			$query = "INSERT INTO ".SQL::table_name('images')." SET ";
			$query .= "anchor='".SQL::escape($fields['anchor'])."',"
				."image_name='".SQL::escape($fields['image_name'])."',"
				."image_size='".SQL::escape($fields['image_size'])."',"
				."title='".SQL::escape(isset($fields['title']) ? $fields['title'] : '')."',"
				."use_thumbnail='".SQL::escape($fields['use_thumbnail'])."',"
				."description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."',"
				."source='".SQL::escape(isset($fields['source']) ? $fields['source'] : '')."',"
				."thumbnail_name='".SQL::escape(isset($fields['thumbnail_name']) ? $fields['thumbnail_name'] : '')."',"
				."link_url='".SQL::escape(isset($fields['link_url']) ? $fields['link_url'] : '')."',"
				."edit_name='".SQL::escape($fields['edit_name'])."',"
				."edit_id=".SQL::escape($fields['edit_id']).","
				."edit_address='".SQL::escape($fields['edit_address'])."',"
				."edit_date='".SQL::escape($fields['edit_date'])."',"
                                ."tags='".SQL::escape(isset($fields['tags']) ? $fields['tags'] : '')."'";

			// actual update
			if(SQL::query($query) === FALSE)
				return FALSE;

			// remember the id of the new item
			$fields['id'] = SQL::get_last_id($context['connection']);

		// nothing done
		} else {
			Logger::error(i18n::s('No image has been added.'));
			return FALSE;
		}
                
                if(isset($fields['tags'])) {
                    // assign the image to related categories, but not archiving categories
                    Categories::remember('image:'.$fields['id'], NULL_DATE, $fields['tags']);
                }

		// clear the cache
		Images::clear($fields);

		// end of job
		return $fields['id'];
	}

	/**
	 * create or alter tables for images
	 */
	public static function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['anchor']		= "VARCHAR(64) DEFAULT 'section:1' NOT NULL";
		$fields['image_name']           = "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['image_size']           = "INT UNSIGNED DEFAULT 0 NOT NULL";
		$fields['title']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['description']          = "TEXT NOT NULL";
		$fields['source']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['thumbnail_name']       = "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['link_url']             = "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['use_thumbnail']        = "ENUM('A', 'Y','N') DEFAULT 'Y' NOT NULL";
		$fields['edit_name']            = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_id']		= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['edit_address']         = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_date']            = "DATETIME";
                $fields['tags'] 		= "TEXT DEFAULT '' NOT NULL";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX anchor']	= "(anchor)";
		$indexes['INDEX edit_date']     = "(edit_date)";
		$indexes['INDEX edit_id']	= "(edit_id)";
		$indexes['INDEX image_size']    = "(image_size)";
		$indexes['INDEX title'] 	= "(title(255))";
		$indexes['FULLTEXT INDEX']	= "full_text(title, source, description)";

		return SQL::setup_table('images', $fields, $indexes);
	}

	/**
	 * get some statistics
	 *
	 * @return the resulting ($count, $oldest_date, $newest_date, $total_size) array
	 */
	public static function stat() {
		global $context;

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date"
			.", SUM(image_size) as total_size"
			." FROM ".SQL::table_name('images')." AS images";

		$output = SQL::query_first($query);
		return $output;
	}

	/**
	 * get some statistics for one anchor
	 *
	 * @param the selected anchor (e.g., 'article:12')
	 * @return the resulting ($count, $oldest_date, $newest_date, $total_size) array
	 */
	public static function stat_for_anchor($anchor) {
		global $context;

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date"
			.", SUM(image_size) as total_size"
			." FROM ".SQL::table_name('images')." AS images "
			." WHERE images.anchor LIKE '".SQL::escape($anchor)."'";

		$output = SQL::query_first($query);
		return $output;
	}
        
        /** 
         * record several image than may have been uploaded with standard or ajax post request
         * 
         * @param object $anchor that will host the images
         * @param bool $postnow order to record it strait in the database or only feed $_RESQUEST
         * if the post will be done later
         */
        public static function upload_bunch($anchor, $postnow=false) {
            
            $count = Files::count_uploaded();
            for($i=0 ; $i < $count ; $i++ ) {
                
                $indice = ($i==0)?'':(string) $i;
                
                if($img = Files::get_uploaded('upload'.$indice)) {
                    $as_thumb = ($indice=='')?true:false;
                    Images::upload_to($anchor, $img, $as_thumb, $postnow);
                }
            }
        }
        
        /**
         * upload a file as a image attach to a given anchor
         * to be used in custom "edit_as" script
         * 
         * @global string $context
         * @param object $anchor
         * @param array $file (from $_FILES)
         * @param bool $set_as_thumb
         * @param bool $put
         */
        public static function upload_to($anchor, $file, $set_as_thumb=false, $put=false) {
            global $context;
            
            // attach some image
            $path = Files::get_path($anchor->get_reference(),'images');
            // $_REQUEST['action'] = 'set_as_icon'; // instruction for image::upload
            if(isset($file) && ($uploaded = Files::upload($file, $path, array('Image', 'upload')))) {

                    // prepare image informations
                    $image = array();
                    $image['image_name'] = $uploaded;
                    $image['image_size'] = $file['size'];
                    $image['thumbnail_name'] = 'thumbs/'.$uploaded;
                    $image['anchor'] = $anchor->get_reference();
                    //$combined = array_merge($image, $_FILES);

                    // post the image which was uploaded
                    if($image['id'] = Images::post($image)) {

                            // successfull post
                            $context['text'] .= '<p>'.i18n::s('Following image has been added:').'</p>'
                                    .Codes::render_object('image', $image['id']).'<br style="clear:left;" />'."\n";

                            // set image as icon and thumbnail
                            if($set_as_thumb) {
                                
                                // delete former icon if any
                                /*if(isset($anchor->item['icon_url'])
                                        && $anchor->item['icon_url']
                                        && $match = Images::get_by_anchor_and_name($anchor->get_reference(), pathinfo($anchor->item['icon_url'],PATHINFO_BASENAME))) {


                                    if($match['id'] != $image['id'])
                                        Images::delete($match['id']);
                                }*/
                                
                                $fields = array(
                                    'thumbnail_url' => Images::get_thumbnail_href($image),
                                    'icon_url'      => Images::get_icon_href($image)
                                    );
                                if($put) {
                                    $fields['id'] = $_REQUEST['id'];
                                    $class = $anchor->get_static_group_class();
                                    $class::put_attributes($fields);
                                } else
                                    $_REQUEST = array_merge($_REQUEST, $fields);
                            
                            }
                    }

            }
        }

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('images');

?>
