<?php
/**
 * some useful commands for anchors
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Anchors {

	/**
	 * cascade to children
	 *
	 * @param string referencing of the changed anchor
	 * @param string rights to be cascaded (e.g., 'Y', 'R' or 'N')
	 */
	public static function cascade($reference, $active) {
		global $context;

		// only sections may have sub-sections
		if(strpos($reference, 'section:') === 0) {

			// cascade to sub-sections
			if($items = Sections::list_for_anchor($reference, 'raw')) {

				// cascade to each section individually
				foreach($items as $id => $item) {

					// limit actual rights
					$item['active'] = Anchors::ceil_rights($active, $item['active_set']);
					$query = "UPDATE ".SQL::table_name('sections')." SET active='".SQL::escape($item['active'])."' WHERE id = ".SQL::escape($id);
					SQL::query($query);

					// cascade to children
					Anchors::cascade('section:'.$item['id'], $item['active']);
				}
			}
		}

		// only categories may have sub-categories
		if(strpos($reference, 'category:') === 0) {

			// cascade to sub-categories
			if($items = Categories::list_for_anchor($reference, 'raw')) {

				// cascade to each section individually
				foreach($items as $id => $item) {

					// limit actual rights
					$item['active'] = Anchors::ceil_rights($active, $item['active_set']);
					$query = "UPDATE ".SQL::table_name('categories')." SET active='".SQL::escape($item['active'])."' WHERE id = ".SQL::escape($id);
					SQL::query($query);

					// cascade to children
					Anchors::cascade('category:'.$item['id'], $item['active']);
				}
			}
		}

		// only sections may have articles
		if(strpos($reference, 'section:') === 0) {

			// cascade to articles --up to 3000
			if($items =& Articles::list_for_anchor_by('edition', $reference, 0, 3000, 'raw')) {

				// cascade to each section individually
				foreach($items as $id => $item) {

					// limit actual rights
					$item['active'] = Anchors::ceil_rights($active, $item['active_set']);
					$query = "UPDATE ".SQL::table_name('articles')." SET active='".SQL::escape($item['active'])."' WHERE id = ".SQL::escape($id);
					SQL::query($query);

					// cascade to children
					Anchors::cascade('article:'.$item['id'], $item['active']);
				}
			}
		}

		// cascade to files --up to 3000
		if($items = Files::list_by_date_for_anchor($reference, 0, 3000, 'raw')) {

			// cascade to each section individually
			foreach($items as $id => $item) {

				// limit actual rights
				$item['active'] = Anchors::ceil_rights($active, $item['active_set']);
				$query = "UPDATE ".SQL::table_name('files')." SET active='".SQL::escape($item['active'])."' WHERE id = ".SQL::escape($id);
				SQL::query($query);
			}
		}
	}

	/**
	 * maximise access rights
	 *
	 * @param string inherited from parent (e.g., 'Y', 'R', or 'N')
	 * @param string set locally (e.g., 'Y', 'R', or 'N')
	 * @return string resulting value (e.g., 'Y', 'R', or 'N')
	 */
	public static function ceil_rights($inherited, $set) {

		if($inherited == 'N')
			return 'N';

		if($inherited == 'R')
			if($set == 'N')
				return 'N';
			else
				return 'R';

		return $set;

	}

	/**
	 * delete items related to one anchor
	 *
	 * This function is invoked to ensure that the database is cleaned when some anchor is deleted.
	 *
	 * The delete_related_to hook is used to invoke any software extension bound as follows:
	 * - id: 'shared/anchors.php#delete_related_to'
	 * - type: 'include'
	 * - parameters: string the reference that is deleted (e.g., 'section:123')
	 *
	 * @param string reference of the deleted anchor (e.g., 'article:12')
	 */
	public static function delete_related_to($anchor) {
		global $context;

		// delete related articles
		Articles::delete_for_anchor($anchor);

		// delete related categories
		Categories::delete_for_anchor($anchor);

		// delete related comments
		include_once $context['path_to_root'].'comments/comments.php';
		Comments::delete_for_anchor($anchor);

		// delete related dates
		include_once $context['path_to_root'].'dates/dates.php';
		Dates::delete_for_anchor($anchor);

		// delete related files
		Files::delete_for_anchor($anchor);

		// delete related images
		include_once $context['path_to_root'].'images/images.php';
		Images::delete_for_anchor($anchor);

		// delete related links
		include_once $context['path_to_root'].'links/links.php';
		Links::delete_for_anchor($anchor);

		// delete related locations
		include_once $context['path_to_root'].'locations/locations.php';
		Locations::delete_for_anchor($anchor);

		// delete related sections
		Sections::delete_for_anchor($anchor);

		// delete related tables
		include_once $context['path_to_root'].'tables/tables.php';
		Tables::delete_for_anchor($anchor);

		// delete related versions
		include_once $context['path_to_root'].'versions/versions.php';
		Versions::delete_for_anchor($anchor);

		// delete memberships for this anchor
		Members::unlink_for_reference($anchor);

		// the delete_related_to hook
		if(is_callable(array('Hooks', 'include_scripts')))
			$context['text'] .= Hooks::include_scripts('shared/anchors.php#delete_related_to', $anchor);

	}

	/**
	 * duplicate items related to one anchor
	 *
	 * This function is invoked  when some anchor is duplicated.
	 *
	 * Note: do not refer here to objects that will be duplicated through
	 * overlays, such as Dates.
	 *
	 * The duplicate_related_to hook is used to invoke any software extension bound as follows:
	 * - id: 'shared/anchors.php#duplicate_related_to'
	 * - type: 'include'
	 * - parameters: array of strings referencing origin and target anchors
	 *
	 * @param string reference of the source anchor (e.g., 'article:12')
	 * @param string reference of the target anchor (e.g., 'article:12')
	 */
	public static function duplicate_related_to($from_anchor, $to_anchor) {
		global $context;

		// duplicate related articles
		Articles::duplicate_for_anchor($from_anchor, $to_anchor);

		// duplicate related categories
		Categories::duplicate_for_anchor($from_anchor, $to_anchor);

		// duplicate related comments
		include_once $context['path_to_root'].'comments/comments.php';
		Comments::duplicate_for_anchor($from_anchor, $to_anchor);

		// do not duplicate related dates -- this will be done through overlays

		// duplicate related files
		Files::duplicate_for_anchor($from_anchor, $to_anchor);

		// duplicate related images
		include_once $context['path_to_root'].'images/images.php';
		Images::duplicate_for_anchor($from_anchor, $to_anchor);

		// duplicate related links
		include_once $context['path_to_root'].'links/links.php';
		Links::duplicate_for_anchor($from_anchor, $to_anchor);

		// duplicate related locations
		include_once $context['path_to_root'].'locations/locations.php';
		Locations::duplicate_for_anchor($from_anchor, $to_anchor);

		// duplicate related sections
		Sections::duplicate_for_anchor($from_anchor, $to_anchor);

		// duplicate related tables
		include_once $context['path_to_root'].'tables/tables.php';
		Tables::duplicate_for_anchor($from_anchor, $to_anchor);

		// duplicate memberships for this anchor
		Members::duplicate_for($from_anchor, $to_anchor);

		// the duplicate_related_to hook
		if(is_callable(array('Hooks', 'include_scripts')))
			$context['text'] .= Hooks::include_scripts('shared/anchors.php#duplicate_related_to', array($from_anchor, $to_anchor));

		// clear the cache as well
		Cache::clear();

	}
	

	/**
	 * load one anchor from the database
	 *
	 * You should expand this function to take into account any new module
	 * that may behave as anchors for other information components.
	 *
	 * This function saves on actual SQL requests by caching results locally in static memory.
	 * Use the second parameter to ensure you have a fresh copy of the object.
	 *
	 * @param string a valid anchor (e.g., 'section:12', 'article:34')
	 * @param boolean TRUE to always fetch a fresh instance, FALSE to enable cache
	 * @return object implementing the Anchor interface, or NULL if the anchor is unknown
	 *
	 * @see shared/anchor.php
	 */
	public static function get($id, $mutable=FALSE) {
		global $context;

		// no anchor yet
		$anchor = NULL;

		// find the type
		$attributes = explode(':', $id);

		// if no type has been provided, assume we want a section
		if(!isset($attributes[1]) || !$attributes[1])
			$attributes = array('section', $id);

		// switch on type
		switch($attributes[0]) {
		case 'article':
			include_once $context['path_to_root'].'articles/article.php';
			$anchor = new Article();
			$anchor->load_by_id($attributes[1], $mutable);
			break;;
		case 'category':
			include_once $context['path_to_root'].'categories/category.php';
			$anchor = new Category();
			$anchor->load_by_id($attributes[1], $mutable);
			break;
		case 'file':
			include_once $context['path_to_root'].'files/file.php';
			$anchor = new File();
			$anchor->load_by_id($attributes[1], $mutable);
			break;;
		case 'section':
			include_once $context['path_to_root'].'sections/section.php';
			$anchor = new Section();
			$anchor->load_by_id($attributes[1], $mutable);
			break;
		case 'user':
			include_once $context['path_to_root'].'users/user.php';
			$anchor = new User();
			$anchor->load_by_id($attributes[1], $mutable);
			break;
		default:
			return $anchor;
		}

		// ensure the object actually exists
		if(!isset($anchor->item['id']))
			$anchor = NULL;

		// return by reference
		return $anchor;
	}

	/**
	 * get the label for an action
	 *
	 * Following actions codes have been defined:
	 * - 'article:publish'
	 * - 'article:submit'
	 * - 'article:update'
	 * - 'article:review'
	 * - 'section:create'
	 * - 'section:update'
	 * - 'comment:create'
	 * - 'comment:update'
	 * - 'file:create'
	 * - 'file:update'
	 * - 'link:create'
	 * - 'link:update'
	 * - 'link:stamp'
	 * - 'image:create'
	 * - 'image:update'
	 * - 'image:set_as_icon'
	 * - 'location:create'
	 * - 'location:update'
	 * - 'table:create'
	 * - 'table:update'
	 * - 'user:create'
	 * - 'user:update'
	 *
	 * @param string the action code example: 'article:publish', 'image:create'
	 * @return a string
	 * @see shared/anchor.php#touch
	 */
	public static function get_action_label($action) {

		if(preg_match('/.*:import/i', $action))
			return i18n::s('imported');

		switch($action) {
		case 'article:publish':
			return i18n::s('published');

		case 'article:submit':
			return i18n::s('created');

		case 'article:update':
			return i18n::s('edited');

		case 'article:review':
			return i18n::s('reviewed');

		case 'section:create':
			return i18n::s('section created');

		case 'section:update':
			return i18n::s('section updated');

		case 'comment:create':
			return i18n::s('commented');

		case 'comment:update':
			return i18n::s('edited');

		case 'file:create':
			return i18n::s('file uploaded');

		case 'file:update':
			return i18n::s('file updated');

		case 'file:release':
			return i18n::s('file released');

		case 'file:reserve':
			return i18n::s('file reserved');

		case 'image:create':
			return i18n::s('image uploaded');

		case 'image:update':
			return i18n::s('image updated');

		case 'image:set_as_icon':
			return i18n::s('icon set');

		case 'link:create':
		case 'link:feed':
			return i18n::s('link posted');

		case 'link:update':
			return i18n::s('link updated');

		case 'link:stamp':
			return i18n::s('page updated');

		case 'location:create':
			return i18n::s('location created');

		case 'location:update':
			return i18n::s('location updated');

		case 'table:create':
			return i18n::s('table posted');

		case 'table:update':
			return i18n::s('table updated');

		case 'thread:update':
			return i18n::s('new message');

		case 'user:create':
			return i18n::s('new user');

		case 'user:update':
			return i18n::s('profile updated');

		default:
			return i18n::s('edited');
		}
	}
	
	/**
	 * Create a layout object by searching class file
	 * according to naming convention
	 * 
	 * @see layouts/layout.php 
	 * @see sections/view.php
	 * 
	 * @param string layout name as recorded in database
	 * @param string type of object that will be listed by the layout
	 * @param boolean to flag that we want a layout variant for index.php
	 * @param boolean flag to avoid warning if layout is not founded 
	 * @return object, the layout
	 */
	public static function new_layout($name,$item_type,$home=FALSE,$silent=FALSE) {
	    global $context;

	    // lazy time : layout already loaded
	    if(is_object($name))
		return $name;

	    $layout = $variant = NULL;	    
	    
	    // separate variant from layout name, if any
	    $attributes = explode(' ', $name, 2);
	    if(isset($attributes[1]))
		list($name,$variant) = $attributes;
	    
	    // get listed item groupe name
	    // mainly to deal with former layouts files
	    // be also friend to DEVs and accept singular or plurial item_type writing
	    switch($item_type) {
		case 'articles':
		    $item_type = 'article';
		case 'article':
		    $family = 'articles';
		    break;
		case 'sections':
		    $item_type = 'section';
		case 'section':
		    $family = 'sections';
		    break;
		case 'files':
		    $item_type = 'file';
		case 'file':
		    $family = 'files';
		    break;
		case 'users' :
		    $item_type = 'user';
		case 'user':
		    $family = 'users';
		    break;
		case 'categories' :
		    $item_type = 'category';
		case 'category':
		    $family = 'categories';
		    break;
		default:
		    return NULL;
	    }
	    
	    
	    ////// look for former layout files
	    
	    // folder to files and filename are different for customized layouts on home page
	    $path = ($home)?'skins':$family;
	    $home = ($home)?'home_':'';
	    
	    // lookup for layout file
	    if($name == 'decorated') {
		include_once $context['path_to_root'].$family.'/layout_'.$family.'.php';
		$name = 'Layout_'.$family;
		$layout = new $name();
	    } elseif($name == 'map') {
		include_once $context['path_to_root'].$family.'/layout_'.$family.'_as_yahoo.php';
		$name = 'Layout_'.$family.'_as_yahoo';
		$layout = new $name;
 	    } elseif(is_readable($context['path_to_root'].$path.'/layout_'.$home.$family.'_as_'.$name.'.php')) {
		$name = 'layout_'.$home.$family.'_as_'.$name;
		include_once $context['path_to_root'].$path.'/'.$name.'.php';
		$layout = new $name;
	    } 
	    // look for refactored layout files
	    elseif (is_readable($context['path_to_root'].'layouts/'.$name.'/layout_as_'.$name.'.php')) {
		$name = 'layout_as_'.$name;
		include_once $context['path_to_root'].'layouts/'.$name.'/'.$name;
		$layout = new $name;
		$layout->listed_type = $item_type;
		
	    }
	    
	    // no layout, fallback to default one
	    if(!$layout) {		

		// useful warning for associates
		if(Surfer::is_associate() && !$silent)
			Logger::error(sprintf(i18n::s('Warning: No script exists for the customized layout %s'), $name));

		include_once $context['path_to_root'].$family.'/layout_'.$family.'.php';
		$variant = $name;
		$name = 'Layout_'.$family;
		$layout = new $name();		
	    }
	    
	    if($variant)
		$layout->set_variant($variant);
	    
	    return $layout;
		    
	}

	/**
	 * count related items
	 *
	 * This function draws a nice table to show how many items are related to
	 * the anchor that has the focus.
	 *
	 * @param string the target reference
	 * @param string the label to use, if any
	 * @return string some XHTML snippet to send to the browser
	 */
	public static function stat_related_to($anchor, $label=NULL) {
		global $context;

		// describe related content
		$related = '';
		$lines = 2;

		// stats for related categories, but only within categories
		if(strpos($anchor, 'category:') === 0) {
			if(($stats = Categories::stat_for_anchor($anchor)) && $stats['count']) {
				$cells = array();
				$cells[] = i18n::s('Categories');
				$cells[] = 'center='.$stats['count'];
				$cells[] = 'center='.Skin::build_date($stats['oldest_date']);
				$cells[] = 'center='.Skin::build_date($stats['newest_date']);
				$related .= Skin::table_row($cells, $lines++);
			}
		}

		// stats for related sections, but only within sections
		if(strpos($anchor, 'section:') === 0) {
			if(($stats = Sections::stat_for_anchor($anchor)) && $stats['count']) {
				$cells = array();
				$cells[] = i18n::s('Sections');
				$cells[] = 'center='.$stats['count'];
				$cells[] = 'center='.Skin::build_date($stats['oldest_date']);
				$cells[] = 'center='.Skin::build_date($stats['newest_date']);
				$related .= Skin::table_row($cells, $lines++);
			}
		}

		// stats for related articles, but only within sections
		if(strpos($anchor, 'section:') === 0) {
			if(($stats = Articles::stat_for_anchor($anchor)) && $stats['count']) {
				$cells = array();
				$cells[] = i18n::s('Pages');
				$cells[] = 'center='.$stats['count'];
				$cells[] = 'center='.Skin::build_date($stats['oldest_date']);
				$cells[] = 'center='.Skin::build_date($stats['newest_date']);
				$related .= Skin::table_row($cells, $lines++);
			}
		}

		// stats for related images
		include_once $context['path_to_root'].'images/images.php';
		if(($stats = Images::stat_for_anchor($anchor)) && $stats['count']) {
			$cells = array();
			$cells[] = i18n::s('Images');
			$cells[] = 'center='.$stats['count'];
			$cells[] = 'center='.Skin::build_date($stats['oldest_date']);
			$cells[] = 'center='.Skin::build_date($stats['newest_date']);
			$related .= Skin::table_row($cells, $lines++);
		}

		// stats for related locations
		include_once $context['path_to_root'].'locations/locations.php';
		if(($stats = Locations::stat_for_anchor($anchor)) && $stats['count']) {
			$cells = array();
			$cells[] = i18n::s('Locations');
			$cells[] = 'center='.$stats['count'];
			$cells[] = 'center='.Skin::build_date($stats['oldest_date']);
			$cells[] = 'center='.Skin::build_date($stats['newest_date']);
			$related .= Skin::table_row($cells, $lines++);
		}

		// stats for related tables
		include_once $context['path_to_root'].'tables/tables.php';
		if(($stats = Tables::stat_for_anchor($anchor)) && $stats['count']) {
			$cells = array();
			$cells[] = i18n::s('Tables');
			$cells[] = 'center='.$stats['count'];
			$cells[] = 'center='.Skin::build_date($stats['oldest_date']);
			$cells[] = 'center='.Skin::build_date($stats['newest_date']);
			$related .= Skin::table_row($cells, $lines++);
		}

		// stats for related files
		if(($stats = Files::stat_for_anchor($anchor)) && $stats['count']) {
			$cells = array();
			$cells[] = i18n::s('Files');
			$cells[] = 'center='.$stats['count'];
			$cells[] = 'center='.Skin::build_date($stats['oldest_date']);
			$cells[] = 'center='.Skin::build_date($stats['newest_date']);
			$related .= Skin::table_row($cells, $lines++);
		}

		// stats for related dates
		include_once $context['path_to_root'].'dates/dates.php';
		if(($stats = Dates::stat_for_anchor($anchor)) && $stats['count']) {
			$cells = array();
			$cells[] = i18n::s('Dates');
			$cells[] = 'center='.$stats['count'];
			$cells[] = 'center='.Skin::build_date($stats['oldest_date']);
			$cells[] = 'center='.Skin::build_date($stats['newest_date']);
			$related .= Skin::table_row($cells, $lines++);
		}

		// stats for related comments
		include_once $context['path_to_root'].'comments/comments.php';
		if(($stats = Comments::stat_for_anchor($anchor)) && $stats['count']) {
			$cells = array();
			$cells[] = i18n::s('Comments');
			$cells[] = 'center='.$stats['count'];
			$cells[] = 'center='.Skin::build_date($stats['oldest_date']);
			$cells[] = 'center='.Skin::build_date($stats['newest_date']);
			$related .= Skin::table_row($cells, $lines++);
		}

		// stats for related links
		include_once $context['path_to_root'].'links/links.php';
		if(($stats = Links::stat_for_anchor($anchor)) && $stats['count']) {
			$cells = array();
			$cells[] = i18n::s('Links');
			$cells[] = 'center='.$stats['count'];
			$cells[] = 'center='.Skin::build_date($stats['oldest_date']);
			$cells[] = 'center='.Skin::build_date($stats['newest_date']);
			$related .= Skin::table_row($cells, $lines++);
		}

		// stats for related versions
		include_once $context['path_to_root'].'versions/versions.php';
		if(($stats = Versions::stat_for_anchor($anchor)) && $stats['count']) {
			$cells = array();
			$cells[] = i18n::s('Versions');
			$cells[] = 'center='.$stats['count'];
			$cells[] = 'center='.Skin::build_date($stats['oldest_date']);
			$cells[] = 'center='.Skin::build_date($stats['newest_date']);
			$related .= Skin::table_row($cells, $lines++);
		}

		// ensure we have a label
		if(!$label)
			$label = i18n::s('Following items are attached to this record and will be impacted as well.');

		// stats for related items in a neat table
		if($related) {

			// make a nice table
			$related = '<p>'.$label."</p>\n"
				.Skin::table_prefix('')
				.Skin::table_row(array(i18n::s('Table'), i18n::s('Records'), i18n::s('Creation date'), i18n::s('Last edition')), 'header')
				.$related
				.Skin::table_suffix();

			// put it in a box
			$related = Skin::build_box(i18n::s('Related items'), $related);

		}

		// job done
		return $related;
	}

}

?>
