<?php
/**
 * the implementation of anchor for categories
 *
 * This class implements the Anchor interface for categories.
 *
 * Following behaviour has been defined for member functions:
 * - get_icon_url() -- reuse the category icon url, if any
 * - get_path_bar() -- one link to the category page
 * - get_prefix() -- provides the category prefix field
 * - get_reference() -- returns 'category:&lt;id&gt;'
 * - get_suffix() -- provides the category suffix field
 * - get_title() -- provide the category title
 * - get_thumbnail_url() -- reuse the category thumbnail url, if any
 * - get_url() -- link to categories/view.php/id
 * - has_option() -- depending on the content of the editors field
 * - touch() -- remember the last action in the category record
 *
 * @see shared/anchor.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Category extends Anchor {

	/**
	 * get the url to display the icon for this anchor
	 *
	 * @return an anchor to the icon image
	 *
	 * @see shared/anchor.php
	 */
	function get_icon_url() {
		if(isset($this->item['icon_url']) && $this->item['icon_url'])
			return $this->item['icon_url'];
		return $this->get_thumbnail_url();
	}

	/**
	 * get the path bar for this anchor
	 *
	 * This function is used to build a path bar relative to the anchor.
	 * For example, if you are displaying an article related to a category,
	 * the path bar has to mention the category. You can use following code
	 * to do that:
	 * [php]
	 * $anchor = Anchors::get($article['anchor']);
	 * $context['path_bar'] = array_merge($context['path_bar'], $anchor->get_path_bar());
	 * [/php]
	 *
	 * @return an array of $url => $label, or NULL on error
	 *
	 * @see shared/anchor.php
	 */
	function get_path_bar() {

		// no item bound
		if(!isset($this->item['id']))
			return NULL;

		// the parent level
		$anchor = Anchors::get($this->item['anchor']);
		if(is_object($anchor))
			$top_bar = $anchor->get_path_bar();
		else
			$top_bar = array( 'categories/' => i18n::s('Categories') );

		// then the category title
		$url = $this->get_url();
		$label = $this->get_title();

		// return an array of ($url => $label)
		if(is_array($top_bar))
			return array_merge($top_bar, array($url => $label));
		return array($url => $label);
	}

	/**
	 * get the reference for this anchor
	 *
	 * @return 'category:&lt;id&gt;', or NULL on error
	 *
	 * @see shared/anchor.php
	 */
	function get_reference() {
		if(isset($this->item['id']))
			return 'category:'.$this->item['id'];
		return NULL;
	}

	/**
	 * get the suffix text
	 *
	 * @param string a variant string transmitted by the caller
	 * @return a string to be inserted in the final page
	 *
	 * @see shared/anchor.php
	 */
	function get_suffix($variant='') {
		if(isset($this->item['suffix']))
			return $this->item['suffix'];
		return '';
	}

	/**
	 * get the url to display the thumbnail for this anchor
	 *
	 * @return an anchor to the thumbnail image, or NULL
	 *
	 * @see shared/anchor.php
	 */
	function get_thumbnail_url() {
		if(isset($this->item['thumbnail_url']))
			return $this->item['thumbnail_url'];
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
		if(isset($this->item['id'])) {
			if($action == 'view')
				return Categories::get_permalink($this->item);
			else
				return Categories::get_url($this->item['id'], $action, $this->item['title']);
		}
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
		$this->item = Categories::get($id, $mutable);
	}

	/**
	 * remember the last action for this category
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

		// no category bound
		if(!isset($this->item['id']))
			return;

		// sanity check
		if(!$origin) {
			logger::remember('categories/category.php', 'unexpected NULL origin at touch()');
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

			// also use it as thumnail if none has been defined yet
			if(!isset($this->item['thumbnail_url']) || !trim($this->item['thumbnail_url'])) {
				include_once $context['path_to_root'].'images/images.php';
				if(($image = Images::get($origin)) && ($url = Images::get_thumbnail_href($image)))
					$query[] = "thumbnail_url = '".SQL::escape($url)."'";
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

		// set an existing image as the category icon
		} elseif($action == 'image:set_as_icon') {
			include_once $context['path_to_root'].'images/images.php';
			if($image = Images::get($origin)) {
				if($url = Images::get_icon_href($image))
					$query[] = "icon_url = '".SQL::escape($url)."'";

				// also use it as thumnail if none has been defined yet
				if(!(isset($this->item['thumbnail_url']) && trim($this->item['thumbnail_url'])) && ($url = Images::get_thumbnail_href($image)))
					$query[] = "thumbnail_url = '".SQL::escape($url)."'";

			}
			$silently = TRUE;

		// set an existing image as the category thumbnail
		} elseif($action == 'image:set_as_thumbnail') {
			include_once $context['path_to_root'].'images/images.php';
			if($image = Images::get($origin)) {
				if($url = Images::get_thumbnail_href($image))
					$query[] = "thumbnail_url = '".SQL::escape($url)."'";
			}
			$silently = TRUE;

		// append a new image, and set it as the article thumbnail
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

		// add a reference to a new table in the category description
		} elseif($action == 'table:create') {
			if(!Codes::check_embedded($this->item['description'], 'table', $origin))
				$query[] = "description = '".SQL::escape($this->item['description'].' [table='.$origin.']')."'";

		// suppress a reference to a table that has been deleted
		} elseif($action == 'table:delete') {
			$query[] = "description = '".SQL::escape(Codes::delete_embedded($this->item['description'], 'table', $origin))."'";

		}

		// stamp the update
		if(!$silently)
			$query[] = "edit_name='".Surfer::get_name()."',"
				."edit_id=".Surfer::get_id().","
				."edit_address='".Surfer::get_email_address()."',"
				."edit_action='$action',"
				."edit_date='".strftime('%Y-%m-%d %H:%M:%S')."'";

		// ensure we have a valid update query
		if(!@count($query))
			return;

		// update the anchor category
		$query = "UPDATE ".SQL::table_name('categories')." SET ".implode(', ',$query)
			." WHERE id = ".SQL::escape($this->item['id']);
		if(SQL::query($query) === FALSE)
			return;

		// always clear the cache, even on no update
		Categories::clear($this->item);

		// get the parent
		if(!$this->anchor)
			$this->anchor = Anchors::get($this->item['anchor']);

		// propagate the touch upwards silently -- we only want to purge the cache
		if(is_object($this->anchor))
			$this->anchor->touch('category:update', $this->item['id'], TRUE, $to_watchers, $to_followers);

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
		$query = "UPDATE ".SQL::table_name('articles')." SET "
			." introduction = '".SQL::escape($this->item['introduction'])."',"
			." description = '".SQL::escape($this->item['description'])."'"
			." WHERE id = ".SQL::escape($this->item['id']);
		SQL::query($query);

		// always clear the cache, even on no update
		Categories::clear($this->item);

	}


}

// stop hackers
defined('YACS') or exit('Script must be included');

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('categories');

?>
