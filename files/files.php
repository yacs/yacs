<?php
/**
 * the database abstraction layer for files
 *
 * @todo allow for one-time download files
 * @todo allow for a limited period of time for sharing
 * @todo allow for password-control files
 * @todo support KOffice file extensions
 * @todo support .dia for Dia http://live.gnome.org/Dia
 * @todo support .sla for scribus http://www.scribus.net/
 * @todo support .sif for Synfig http://synfig.org/
 * @todo support .gan for Ganttproject http://ganttproject.sourceforge.net/
 * @todo support .kml and .kmz for Google Earth http://earth.google.com/kml/kml_tut.html
 * @todo add a flag to obsolete files
 *
 * Files are saved into the file system of the web server. Each file also has a related record in the database.
 *
 * @link http://www.w3schools.com/media/media_mimeref.asp MIME Reference
 * @link http://specs.openoffice.org/appwide/fileIO/FileFormatNames.sxw File Format Names for New OASIS Open Office XML Format
 * @link http://support.microsoft.com/kb/288102 MIME Type Settings for Windows Media Services
 *
 * [title]Simple Document Management System[/title]
 *
 * YACS takes note of files that are assigned to individuals for modification.
 *
 * On detach, the file is provided for download as usual, and surfer name is recorded as well.
 * Other surfers are then warned that someone has taken the ownership of the file, and that
 * shared copies will be obsoleted soon.
 *
 * On assignment, the file is only assigned to one community member for modifications.
 * Other surfers are then warned that someone has taken the ownership of the file, and that
 * shared copies will be obsoleted soon.
 *
 * File refresh, through upload, is still available to members, as usual, except that
 * YACS adds a warning to all surfers, except the one that has been assigned, that they are
 * not considered as current file owners.
 *
 * @author Bernard Paques
 * @author Florent
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Geasm
 * @tester Mat0806
 * @tester Olivier
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Files {

	/**
	 * check if new files can be added
	 *
	 * This function returns TRUE if files can be added to some place,
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

		// attach a file to an article
		if($variant == 'article') {

			// 'no files' option
			if(Articles::has_option('no_files', $anchor, $item))
				return FALSE;


		// attach a file to a user profile
		} elseif($variant == 'user') {

			// associates can always proceed
			if(Surfer::is_associate())
				;

			// you can't attach a file to the profile of someone else
			elseif(!is_object($anchor) || !Surfer::get_id())
				return FALSE;
			elseif($anchor->get_reference() != 'user:'.Surfer::get_id())
				return FALSE;

		// other containers
		} else {

			// files have to be activated explicitly
			if(isset($item['options']) && is_string($item['options']) && preg_match('/\bwith_files\b/i', $item['options']))
				;
			elseif(!isset($item['id']) && is_object($anchor) && $anchor->has_option('with_files', FALSE))
				;
			else
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

			// surfer is an editor, and the section is not private
			if(isset($item['active']) && ($item['active'] != 'N') && Sections::is_assigned($item['id']))
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

		// authenticated members and subscribers are allowed to add files
		if(Surfer::is_logged())
			return TRUE;

		// container is restricted
		if(isset($item['active']) && ($item['active'] == 'R'))
			return FALSE;
		if(is_object($anchor) && !$anchor->is_public())
			return FALSE;

		// anonymous contributions are allowed for articles and for sections
		if(($variant == 'article') || ($variant == 'section')) {
			if(isset($item['options']) && preg_match('/\banonymous_edit\b/i', $item['options']))
				return TRUE;
			if(is_object($anchor) && $anchor->has_option('anonymous_edit'))
				return TRUE;
		}

		// the default is to not allow for new files
		return FALSE;
	}

	/**
	 * check if a comment can be modified
	 *
	 * This function returns TRUE if the file can be modified,
	 * and FALSE otherwise.
	 *
	 * @param object an instance of the Anchor interface
	 * @param array a set of item attributes, aka, the target comment
	 * @return TRUE or FALSE
	 */
	public static function allow_modification($anchor, $item) {
		global $context;

		// sanit check
		if(!isset($item['id']))
			return FALSE;

		// surfer is not allowed to upload a file
		if(!Surfer::may_upload())
			return FALSE;

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// submissions have been disallowed
		if(isset($context['users_without_submission']) && ($context['users_without_submission'] == 'Y'))
			return FALSE;

		// surfer owns the container
		if(is_object($anchor) && $anchor->is_owned())
			return TRUE;

		// anchor has been locked
		if(is_object($anchor) && $anchor->has_option('locked'))
			return FALSE;

		// surfer owns the file
		if(isset($item['create_id']) && Surfer::is($item['create_id']))
			return TRUE;

		// authenticated members may be allowed to modify files from others
 		if(Surfer::is_member() && (!isset($context['users_without_file_overloads']) || ($context['users_without_file_overloads'] != 'Y')))
 			return TRUE;

		// anonymous contributions may be allowed, but only in articles
		if(is_object($anchor) && ($anchor->get_type() == 'article') && $anchor->has_option('anonymous_edit'))
			return TRUE;

		// the default is to not allow for new files
		return FALSE;
	}

	/**
	 * check if a file can be deleted
	 *
	 * This function returns TRUE if the file can be deleted,
	 * and FALSE otherwise.
	 *
	 * @param array a set of item attributes, aka, the target file
	 * @param object an instance of the Anchor interface
	 * @return TRUE or FALSE
	 */
	public static function allow_deletion($item, $anchor) {
		global $context;

		// sanity check
		if(!isset($item['id']))
			return FALSE;

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// surfer owns the container
		if(is_object($anchor) && $anchor->is_owned())
			return TRUE;

		// allow container editors --not subscribers-- to manage content, except on private sections
		if(Surfer::is_member() && is_object($anchor) && !$anchor->is_hidden() && $anchor->is_assigned())
			return TRUE;

		// the file is anchored to the profile of this member
		if(Surfer::get_id() && is_object($anchor) && !strcmp($anchor->get_reference(), 'user:'.Surfer::get_id()))
			return TRUE;

		// surfer has created the file
		if(isset($item['create_id']) && Surfer::is($item['create_id']))
			return TRUE;

		// surfer has changed the file
		if(isset($item['edit_id']) && Surfer::is($item['edit_id']))
			return TRUE;

		// default case
		return FALSE;
	}

	/**
	 * assign the file to a member
	 *
	 * When a surfer downloads a file for explicit modification,
	 * this function is invoked to record his name, and date of detach.
	 *
	 * On file assignment the function is called as well to
	 * remember the member on duty.
	 *
	 * These data are used afterwards to inform other surfers
	 * that file is under modification, and that they should avoid
	 * making copies of the file.
	 *
	 * Information related to file assignment is automatically deleted
	 * on next upload, whoever submits a new copy of the target file.
	 *
	 * If no attributes are provided, then assignment data is deleted.
	 *
	 * @param int the id of the detached file
	 * @param array user attributes, if any
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	public static function assign($id, $user=NULL) {
		global $context;

		// sanity check
		if(!$id)
			return FALSE;

		// assign file to someone -- don't do it if already assigned
		if(is_array($user) && isset($user['nick_name']) && isset($user['id'])) {

			$query = "UPDATE ".SQL::table_name('files')." SET "
				." assign_name = '".SQL::escape($user['nick_name'])."',"
				." assign_id = ".SQL::escape($user['id']).","
				." assign_address = '".SQL::escape(isset($user['email']) ? $user['email'] : '')."',"
				." assign_date = '".SQL::escape(gmstrftime('%Y-%m-%d %H:%M:%S'))."'"
				." WHERE (id  = ".SQL::escape($id).") AND (assign_id < 1)";

		// clear the assignment
		} else {

			$query = "UPDATE ".SQL::table_name('files')." SET "
				." assign_name = '',"
				." assign_id = 0,"
				." assign_address = '',"
				." assign_date = '".SQL::escape(NULL_DATE)."'"
				." WHERE (id = ".SQL::escape($id).")";

		}

		// do the job
		if(SQL::query($query) == 1)
			return TRUE;
		return FALSE;


	}

	/**
	 * clear cache entries for one item
	 *
	 * @param array item attributes
	 */
	public static function clear(&$item) {

		// where this item can be displayed
		$topics = array('articles', 'categories', 'files', 'sections', 'users');

		// clear anchor page
		if(isset($item['anchor']))
			$topics[] = $item['anchor'];

		// clear this page
		if(isset($item['id']))
			$topics[] = 'file:'.$item['id'];

		// clear the cache
		Cache::clear($topics);

	}

	/**
	 * count records for one anchor
	 *
	 * @param string the selected anchor (e.g., 'article:12')
	 * @param boolean TRUE if this can be optionnally avoided
	 * @param array list of ids to avoid, if any
	 * @return int the resulting count, or NULL on error
	 */
	public static function count_for_anchor($anchor, $optional=FALSE, $avoid=NULL) {
		global $context;

		// sanity check
		if(!$anchor)
			return NULL;

		// request the database only in hi-fi mode
		if($optional && ($context['skins_with_details'] != 'Y'))
			return NULL;

		// limit the scope of the request
		$where = "files.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR files.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR files.active='N'";
		$where = '('.$where.')';

		// ids to avoid
		if($avoid)
			$where .= ' AND (files.id NOT IN ('.join(',', $avoid).'))';

		// select among available items
		$query = "SELECT COUNT(*) as count"
			." FROM ".SQL::table_name('files')." AS files"
			." WHERE files.anchor LIKE '".SQL::escape($anchor)."' AND ".$where;

		return SQL::query_scalar($query);
	}

	/**
	 * delete one file in the database and in the file system
	 *
	 * @param int the id of the file to delete
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	public static function delete($id) {
		global $context;

		// load the row
		$item =& Files::get($id);
		if(!$item['id']) {
			Logger::error(i18n::s('No item has the provided id.'));
			return FALSE;
		}

		// actual deletion of the file
		$file_path = $context['path_to_root'].Files::get_path($item['anchor']);
		Safe::unlink($file_path.'/'.$item['file_name']);
		Safe::unlink($file_path.'/thumbs/'.$item['file_name']);
		Safe::rmdir($file_path.'/thumbs');
		Safe::rmdir($file_path);
		Safe::rmdir(dirname($file_path));

		// delete related items
		Anchors::delete_related_to('file:'.$id);

		// delete the record in the database
		$query = "DELETE FROM ".SQL::table_name('files')." WHERE id = ".SQL::escape($item['id']);
		if(SQL::query($query) === FALSE)
			return FALSE;

		// job done
		return TRUE;
	}

	/**
	 * delete all files for a given anchor
	 *
	 * @param the anchor to check
	 *
	 * @see shared/anchors.php
	 */
	public static function delete_for_anchor($anchor) {
		global $context;

		// seek all records attached to this anchor
		$query = "SELECT id FROM ".SQL::table_name('files')." AS files"
			." WHERE files.anchor LIKE '".SQL::escape($anchor)."'";
		if(!$result =& SQL::query($query))
			return;

		// delete silently all matching files
		while($row =& SQL::fetch($result))
			Files::delete($row['id']);
	}

	/**
	 * compute a thumbnail image for a file
	 *
	 * This function builds a preview image where possible, and returns its URL to caller, or NULL.
	 * That result can be saved directly as attribute ##thumbnail_url## or associated file record.
	 *
	 * @param string path to the file, including trailing slash (e.g., 'files/article/123/')
	 * @param string file name (e.g., 'document.pdf')
	 * @return string web address of the thumbnail that has been built, or NULL
	 *
	 * @see files/edit.php
	 */
	public static function derive_thumbnail($file_path, $file_name) {
		global $context;

		// if the file is an image, create a thumbnail for it
		if(($image_information = Safe::GetImageSize($file_path.$file_name)) && ($image_information[2] >= 1) && ($image_information[2] <= 3)) {

			// derive a thumbnail image
			$thumbnail_name = 'thumbs/'.$file_name;
			include_once $context['path_to_root'].'images/image.php';
			Image::shrink($context['path_to_root'].$file_path.$file_name, $context['path_to_root'].$file_path.$thumbnail_name, FALSE, TRUE);

			// remember the address of the thumbnail
			return $context['url_to_root'].$file_path.$thumbnail_name;

		// if this is a PDF that can be converted by Image Magick, then compute a thumbnail for the file
		} else if(preg_match('/\.pdf$/i', $file_name) && class_exists('Imagick') && ($handle=new Imagick($context['path_to_root'].$file_path.$file_name))) {

			// derive a thumbnail image
			$thumbnail_name = 'thumbs/'.$file_name.'.png';
			Safe::mkdir($context['path_to_root'].$file_path.'thumbs');

			// consider only the first page
			$handle->setIteratorIndex(0);

			$handle->setImageCompression(Imagick::COMPRESSION_LZW);
			$handle->setImageCompressionQuality(90);
			$handle->stripImage(90);
			$handle->thumbnailImage(100, NULL);
			$handle->writeImage($context['path_to_root'].$file_path.$thumbnail_name);

			// remember the address of the thumbnail
			return $context['url_to_root'].$file_path.$thumbnail_name;

		}

		// no thumbnail
		return NULL;

	}

	/**
	 * duplicate all files for a given anchor
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
		$query = "SELECT * FROM ".SQL::table_name('files')." WHERE anchor LIKE '".SQL::escape($anchor_from)."'";
		if(($result =& SQL::query($query)) && SQL::count($result)) {

			// create target folders
			$file_to = Files::get_path($anchor_to);
			if(!Safe::make_path($file_to))
				Logger::error(sprintf(i18n::s('Impossible to create path %s.'), $file_to));
			$file_to = $context['path_to_root'].$file_to.'/';

			// the list of transcoded strings
			$transcoded = array();

			// process all matching records one at a time
			$file_from = Files::get_path($anchor_from);
			while($item =& SQL::fetch($result)) {

				// sanity check
				if(!file_exists($context['path_to_root'].$file_from.'/'.$item['file_name']))
					continue;

				// duplicate file
				if(!copy($context['path_to_root'].$file_from.'/'.$item['file_name'], $file_to.$item['file_name'])) {
					Logger::error(sprintf(i18n::s('Impossible to copy file %s.'), $item['file_name']));
					continue;
				}

				// this will be filtered by umask anyway
				Safe::chmod($file_path.$item['file_name'], $context['file_mask']);

				// a new id will be allocated
				$old_id = $item['id'];
				unset($item['id']);

				// target anchor
				$item['anchor'] = $anchor_to;

				// actual duplication
				if($new_id = Files::post($item)) {

					// more pairs of strings to transcode
					$transcoded[] = array('/\[embed='.preg_quote($old_id, '/').'/i', '[embed='.$new_id);
					$transcoded[] = array('/\[file='.preg_quote($old_id, '/').'/i', '[file='.$new_id);
					$transcoded[] = array('/\[flash='.preg_quote($old_id, '/').'/i', '[flash='.$new_id); // obsolete, to be removed by end 2009
					$transcoded[] = array('/\[download='.preg_quote($old_id, '/').'/i', '[download='.$new_id);
					$transcoded[] = array('/\[sound='.preg_quote($old_id, '/').'/i', '[sound='.$new_id);

					// duplicate elements related to this item
					Anchors::duplicate_related_to('file:'.$old_id, 'file:'.$new_id);

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
	 * get one file by id
	 *
	 * @param int the id of the file
	 * @param boolean TRUE to always fetch a fresh instance, FALSE to enable cache
	 * @return the resulting $row array, with at least keys: 'id', 'title', 'description', etc.
	 */
	public static function &get($id, $mutable=FALSE) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// ensure proper unicode encoding
		$id = (string)$id;
		$id = utf8::encode($id);

		// cache previous answers
		static $cache;
		if(!is_array($cache))
			$cache = array();

		// cache hit, but only for immutable objects
		if(!$mutable && isset($cache[$id]))
			return $cache[$id];

		// select among available items -- exact match
		$query = "SELECT * FROM ".SQL::table_name('files')." AS files"
			." WHERE (files.id = ".SQL::escape($id).")";
		$output =& SQL::query_first($query);

		// save in cache
		if(!$mutable && isset($output['id']))
			$cache[$id] = $output;

		// return by reference
		return $output;
	}

	/**
	 * get one file by anchor and name
	 *
	 * @param string the anchor
	 * @param string the file name
	 * @return the resulting $row array, with at least keys: 'id', 'title', 'description', etc.
	 */
	public static function &get_by_anchor_and_name($anchor, $name) {
		global $context;

		// select among available items
		$query = "SELECT * FROM ".SQL::table_name('files')." AS files "
			." WHERE files.anchor LIKE '".SQL::escape($anchor)."' AND files.file_name='".SQL::escape($name)."'";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get the url to the icon for this file
	 *
	 * @param string the file name
	 * @return an anchor to the viewing script
	 */
	public static function get_icon_url($name) {

		// initialize tables only once
		static $files_icons;
		if(!is_array($files_icons)) {

			// path to file icons, relative to server root
			$files_icons_url = 'skins/_reference/files/';

			// icons related to file types
			$files_icons = array(
				'3gp' => $files_icons_url.'film_icon.gif',
				'ace' => $files_icons_url.'zip_icon.gif',
				'ai' => $files_icons_url.'postscript_icon.gif',
				'aif' => $files_icons_url.'sound_icon.png', 		// audio/aiff
				'aiff' => $files_icons_url.'sound_icon.png',		// audio/aiff
				'arj' => $files_icons_url.'zip_icon.gif',
				'asf' => $files_icons_url.'film_icon.gif',			// video/x-ms-asf
				'asx' => $files_icons_url.'film_icon.gif',
				'au' => $files_icons_url.'sound_icon.png',			// audio/basic
				'avi' => $files_icons_url.'film_icon.gif',
				'awk' => $files_icons_url.'text_icon.gif',
				'bmp' => $files_icons_url.'image_icon.gif',
				'bz' => $files_icons_url.'zip_icon.gif',
				'bz2' => $files_icons_url.'zip_icon.gif',
				'cer' => $files_icons_url.'security_icon.png',
				'css' => $files_icons_url.'html_icon.gif',
				'divx' => $files_icons_url.'film_icon.gif',
				'dll' => $files_icons_url.'exe_icon.gif',
				'doc' => $files_icons_url.'word_icon.gif',
				'docm' => $files_icons_url.'word_icon.gif',
				'docx' => $files_icons_url.'word_icon.gif',
				'dot' => $files_icons_url.'word_icon.gif',
				'eml' => $files_icons_url.'text_icon.gif',
				'eps' => $files_icons_url.'postscript_icon.gif',
				'exe' => $files_icons_url.'exe_icon.gif',
				'flv' => $files_icons_url.'flash_icon.gif', 		// flash video
				'gan' => $files_icons_url.'gantt_icon.gif',
				'gif' => $files_icons_url.'image_icon.gif',
				'gg' => $files_icons_url.'google_icon.gif',
				'gpx' => $files_icons_url.'gpx_icon.gif',
				'gtar' => $files_icons_url.'zip_icon.gif',
				'gz' => $files_icons_url.'zip_icon.gif',
				'htm' => $files_icons_url.'html_icon.gif',
				'html' => $files_icons_url.'html_icon.gif',
				'ics' => $files_icons_url.'calendar_icon.gif',
				'jar' => $files_icons_url.'java_icon.gif',
				'jnlp' => $files_icons_url.'java_icon.gif',
				'jpe' => $files_icons_url.'image_icon.gif',
				'jpeg' => $files_icons_url.'image_icon.gif',
				'jpg' => $files_icons_url.'image_icon.gif',
				'latex' => $files_icons_url.'tex_icon.gif',
				'm3u' => $files_icons_url.'midi_icon.gif',			// playlist
				'm4v' => $files_icons_url.'mov_icon.gif',			// video/x-m4v
				'mdb' => $files_icons_url.'access_icon.gif',
				'mid' => $files_icons_url.'midi_icon.gif',
				'midi' => $files_icons_url.'midi_icon.gif',
				'mka' => $files_icons_url.'sound_icon.png', 		// audio/x-matroska
				'mkv' => $files_icons_url.'film_icon.gif',			// video/x-matroska
				'mm' => $files_icons_url.'freemind_icon.gif',		// application freemind
				'mmap' => $files_icons_url.'mmap_icon.gif', 		// application mindmanager
				'mmas' => $files_icons_url.'mmap_icon.gif', 		// application mindmanager
				'mmat' => $files_icons_url.'mmap_icon.gif', 		// application mindmanager
				'mmmp' => $files_icons_url.'mmap_icon.gif', 		// application mindmanager
				'mmp' => $files_icons_url.'mmap_icon.gif',			// application mindmanager
				'mmpt' => $files_icons_url.'mmap_icon.gif', 		// application mindmanager
				'mov' => $files_icons_url.'mov_icon.gif',			// video/quicktime
				'mp2' => $files_icons_url.'sound_icon.png',
				'mp3' => $files_icons_url.'sound_icon.png',
				'mp4' => $files_icons_url.'film_icon.gif',			// video/mpeg
				'mpe' => $files_icons_url.'film_icon.gif',			// video/mpeg
				'mpeg' => $files_icons_url.'film_icon.gif', 		// video/mpeg
				'mpg' => $files_icons_url.'film_icon.gif',			// video/mpeg
				'mpga' => $files_icons_url.'sound_icon.png',
				'mpp' => $files_icons_url.'project_icon.gif',
				'odb' => $files_icons_url.'ooo_database_icon.png',	// open document database
				'odc' => $files_icons_url.'ooo_chart_icon.png', 	// open document chart
				'odf' => $files_icons_url.'ooo_math_icon.png',		// open document formula
				'odg' => $files_icons_url.'ooo_draw_icon.png',		// open document drawing
				'odi' => $files_icons_url.'ooo_draw_icon.png',		// open document image
				'odp' => $files_icons_url.'ooo_impress_icon.png',	// open document presentation
				'ods' => $files_icons_url.'ooo_calc_icon.png',		// open document spreadsheet
				'odt' => $files_icons_url.'ooo_writer_icon.png',	// open document text
				'odm' => $files_icons_url.'ooo_global_icon.png',	// open document master document
				'ogg' => $files_icons_url.'sound_icon.png',
				'otg' => $files_icons_url.'ooo_draw_icon.png',		// open document drawing template
				'oth' => $files_icons_url.'ooo_html_icon.png',		// open document html template
				'otp' => $files_icons_url.'ooo_impress_icon.png',	// open document presentation template
				'ots' => $files_icons_url.'ooo_calc_icon.png',		// open document spreadsheet template
				'ott' => $files_icons_url.'ooo_writer_icon.png',	// open document text template
				'p12' => $files_icons_url.'security_icon.png',
				'pcap' => $files_icons_url.'default_icon.png',		// Ethereal and Wireshark
				'pcast' => $files_icons_url.'sound_icon.png',
				'pdb' => $files_icons_url.'palm_icon.gif',
				'pdf' => $files_icons_url.'pdf_icon.gif',
				'pfx' => $files_icons_url.'security_icon.png',
				'pgp' => $files_icons_url.'text_icon.gif',
				'pic' => $files_icons_url.'image_icon.gif',
				'pict' => $files_icons_url.'image_icon.gif',
				'pls' => $files_icons_url.'midi_icon.gif',			// playlist
				'png' => $files_icons_url.'image_icon.gif',
				'po' => $files_icons_url.'text_icon.gif',			// portable object (some translation file)
				'pot' => $files_icons_url.'text_icon.gif',			// portable object template (some translation file)
				'ppd' => $files_icons_url.'image_icon.gif',
				'pps' => $files_icons_url.'powerpoint_icon.gif',
				'ppt' => $files_icons_url.'powerpoint_icon.gif',
				'pptm' => $files_icons_url.'powerpoint_icon.gif',
				'pptx' => $files_icons_url.'powerpoint_icon.gif',
				'prc' => $files_icons_url.'palm_icon.gif',
				'ps' => $files_icons_url.'postscript_icon.gif',
				'psd' => $files_icons_url.'image_icon.gif',
				'pub' => $files_icons_url.'publisher_icon.gif',
				'qt' => $files_icons_url.'mov_icon.gif',			// video/quicktime
				'ra' => $files_icons_url.'sound_icon.png',
				'ram' => $files_icons_url.'midi_icon.gif',			// playlist
				'rar' => $files_icons_url.'zip_icon.gif',
				'rmp' => $files_icons_url.'open_workbench_icon.gif',
				'rpm' => $files_icons_url.'zip_icon.gif',
				'rtf' => $files_icons_url.'word_icon.gif',
				'shtml' => $files_icons_url.'html_icon.gif',
				'snd' => $files_icons_url.'sound_icon.png', 		// audio/basic
				'sql' => $files_icons_url.'text_icon.gif',
				'srt' => $files_icons_url.'text_icon.gif',			// video/subtitle
				'stc' => $files_icons_url.'ooo_calc_icon.png',		// open document spreadsheet template
				'std' => $files_icons_url.'ooo_draw_icon.png',		// open document drawing template
				'sti' => $files_icons_url.'ooo_impress_icon.png',	// open document presentation template
				'stw' => $files_icons_url.'ooo_writer_icon.png',	// open document text template
				'sxc' => $files_icons_url.'ooo_calc_icon.png',		// open document spreadsheet
				'sxd' => $files_icons_url.'ooo_draw_icon.png',		// open document drawing
				'sxg' => $files_icons_url.'ooo_writer_icon.png',	// open document master document
				'sxi' => $files_icons_url.'ooo_impress_icon.png',	// open document presentation
				'sxm' => $files_icons_url.'ooo_math_icon.png',		// open document formula
				'sxw' => $files_icons_url.'ooo_writer_icon.png',	// open document text
				'swf' => $files_icons_url.'flash_icon.gif', 		// flash
				'tar' => $files_icons_url.'zip_icon.gif',
				'tex' => $files_icons_url.'tex_icon.gif',
				'texi' => $files_icons_url.'tex_icon.gif',
				'texinfo' => $files_icons_url.'tex_icon.gif',
				'tif' => $files_icons_url.'image_icon.gif',
				'tiff' => $files_icons_url.'image_icon.gif',
				'tgz' => $files_icons_url.'zip_icon.gif',
				'txt' => $files_icons_url.'text_icon.gif',
				'vob' => $files_icons_url.'film_icon.gif',			// video/
				'vsd' => $files_icons_url.'visio_icon.gif',
				'wav' => $files_icons_url.'sound_icon.png', 		// audio/x-wav
				'wax' => $files_icons_url.'midi_icon.gif',			// playlist
				'wma' => $files_icons_url.'sound_icon.png', 		// audio/x-ms-wma
				'wmv' => $files_icons_url.'film_icon.gif',			// video/x-ms-wmv
				'wri' => $files_icons_url.'write_icon.gif',
				'wvx' => $files_icons_url.'midi_icon.gif',			// playlist
				'xbm' => $files_icons_url.'image_icon.gif', 		// image/x-xbitmap
				'xls' => $files_icons_url.'excel_icon.gif',
				'xlsm' => $files_icons_url.'excel_icon.gif',
				'xlsx' => $files_icons_url.'excel_icon.gif',
				'xml' => $files_icons_url.'html_icon.gif',
				'zip' => $files_icons_url.'zip_icon.gif',
				'default' => $files_icons_url.'default_icon.gif' );
		}

		// a name with no extension
		if(($position = strrpos($name, '.')) === FALSE)
			return $files_icons['default'];

		// extract the extension
		if(!$extension = substr($name, $position+1))
			return $files_icons['default'];

		// match the list
		if(isset($files_icons[$extension]))
			return $files_icons[$extension];

		// always return some icon
		return $files_icons['default'];
	}

	/**
	 * get the mime type for a file
	 *
	 * We don't use the internal function from PHP library, which has proven to be boggus.
	 *
	 * @param string the file name
	 * @param boolean force download
	 * @return a string describing the MIME type
	 */
	public static function get_mime_type($name, $download=FALSE) {
		global $context;

		// get the list of supported extensions
		$file_types =& Files::get_mime_types();

		// a name with no extension
		if(($position = strrpos($name, '.')) === FALSE)
			return 'application/octet-stream';

		// extract the extension
		if(!$extension = substr($name, $position+1))
			return 'application/octet-stream';

		// match the list
		if(isset($file_types[$extension]) && ($type = $file_types[$extension])) {

			// people could forgot to save this to their hard drives
			if(in_array($type, array('application/vnd.ms-project')))
				return 'application/octet-stream';

			// use the regular type
			return $type;
		}

		// always return some type
		return 'application/octet-stream';
	}

	/**
	 * get supported MIME types
	 *
	 * @link http://www.iana.org/assignments/media-types/application/
	 * @link http://filext.com/ File Extensions reference
	 * @link http://books.evc-cit.info/odbook/ch01.html#mimetype-table MIME Types and Extensions for OpenDocument Documents
	 * @link http://www.macromedia.com/cfusion/knowledgebase/index.cfm?id=tn_4151  Web server MIME types required for serving Flash movies
	 *
	 * @return array describing supported MIME types ($extension1 => $mime_type1, $extension2 => $mime_type2, ...)
	 */
	public static function &get_mime_types() {

		// initialize tables only once
		static $file_types;
		if(!is_array($file_types)) {

			// file types
			$file_types = array(
				'3gp' => 'video/3gpp',
				'ace' => 'application/x-ace',
				'ai' => 'application/postscript',	// postscript
				'aif' => 'audio/aiff',
				'aiff' => 'audio/aiff',
				'arj' => 'application/arj',
				'asf' => 'video/x-ms-asf',	// windows media player
				'asx' => 'audio/x-ms-wax',
				'au' => 'audio/basic',
				'avi' => 'video/x-msvideo',
				'awk' => 'text/plain',
				'bmp' => 'image/bmp',
				'bz' => 'application/x-bzip',
				'bz2' => 'application/x-bzip2',
				'cer' => 'application/x-x509-ca-cert',	// a X509 certificate
				'chm' => 'application/octet-stream',		// windows help file
				'css' => 'text/css',
				'divx' => 'video/vnd.divx ',
				'dll' => 'application/octet-stream',
				'doc' => 'application/msword',
				'docm' => 'application/msword',
				'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'dot' => 'application/msword',
				'eml' => 'text/html',		// message/rfc822
				'eps' => 'application/postscript',	// postscript
				'exe' => 'application/octet-stream',
				'flv' => 'video/x-flv', 	// flash video
				'gan' => 'application/x-ganttproject',
				'gg' => 'app/gg',	// google desktop gadget
				'gif' => 'image/gif',
				'gpx' => 'application/octet-stream',	// GPS XML data
				'gtar' => 'application/x-gtar',
				'gz' => 'application/x-gzip',
				'htm' => 'text/html',
				'html' => 'text/html',
				'ics' => 'text/calendar',
				'jar' => 'application/java-archive',
				'jnlp' => 'application/x-java-jnlp-file',
				'jpe' => 'image/jpeg',
				'jpeg' => 'image/jpeg',
				'jpg' => 'image/jpeg',
				'latex' => 'application/x-latex',
				'm3u' => 'audio/x-mpegurl', // playlist
				'm4v' => 'video/x-m4v',
				'mdb' => 'application/x-msaccess',
				'mid' => 'audio/mid',
				'midi' => 'audio/mid',
				'mka' => 'audio/x-matroska',
				'mkv' => 'video/x-matroska',
				'mm' => 'application/x-freemind',
				'mmap' => 'application/vnd.mindjet.mindmanager',
				'mmas' => 'application/vnd.mindjet.mindmanager',
				'mmat' => 'application/vnd.mindjet.mindmanager',
				'mmmp' => 'application/vnd.mindjet.mindmanager',
				'mmp' => 'application/vnd.mindjet.mindmanager',
				'mmpt' => 'application/vnd.mindjet.mindmanager',
				'mo' => 'application/octet-stream', 	// machine object (i.e., some translated file)
				'mov' => 'video/quicktime',
				'mp2' => 'video/mpeg',
				'mp3' => 'audio/mpeg',
				'mp4' => 'video/mp4',
				'mpe' => 'video/mpeg',
				'mpeg' => 'video/mpeg',
				'mpg' => 'video/mpeg',
				'mpga' => 'audio/mpeg',
				'mpp' => 'application/vnd.ms-project',
				'odb' => 'application/vnd.oasis.opendocument.database', // open document database
				'odc' => 'application/vnd.oasis.opendocument.chart',		// open document chart
				'odf' => 'application/vnd.oasis.opendocument.formula',		// open document formula
				'odg' => 'application/vnd.oasis.opendocument.graphics', 	// open document drawing
				'odi' => 'application/vnd.oasis.opendocument.image',		// open document image
				'odp' => 'application/vnd.oasis.opendocument.presentation', // open document presentation
				'ods' => 'application/vnd.oasis.opendocument.spreadsheet',	// open document spreadsheet
				'odt' => 'application/vnd.oasis.opendocument.text', 		// open document text
				'odm' => 'application/vnd.oasis.opendocument.text-master ', // open document master document
				'ogg' => 'application/ogg',
				'otg' => 'application/vnd.oasis.opendocument.graphics-template',		// open document drawing template
				'oth' => 'application/vnd.oasis.opendocument.text-web', 	// open document html template
				'otp' => 'application/vnd.oasis.opendocument.presentation-template',	// open document presentation template
				'ots' => 'application/vnd.oasis.opendocument.spreadsheet-template', 	// open document spreadsheet template
				'ott' => 'application/vnd.oasis.opendocument.text-template',	// open document text template
				'p12' => 'application/x-pkcs12',	// a PKCS certificate
				'pcap' => 'application/pcap',		// Ethereal and Wireshark
				'pcast' => 'application/octet-stream',	// Apple podcast
				'pdb' => 'application/vnd.palm',	// palm database
				'pdf' => 'application/pdf',
				'pfx' => 'application/x-pkcs12',	// a PKCS certificate
				'pgp' => 'text/plain',				// a message signed by PGP
				'pic' => 'image/pict',
				'pict' => 'image/pict',
				'pls' => 'audio/x-scpls',			// playlist
				'png' => 'image/png',
				'po' => 'text/plain',				// a portable object (i.e., some translated file)
				'pot' => 'text/plain',				// a portable object template
				'ppd' => 'application/pagemaker',
				'pps' => 'application/vnd.ms-powerpoint',
				'ppt' => 'application/vnd.ms-powerpoint',
				'pptm' => 'application/vnd.ms-powerpoint',
				'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
				'prc' => 'application/palmpilot',	// palm resource
				'ps' => 'application/postscript',	// postscript
				'psd' => 'image/photoshop', 		// photoshop
				'pub' => 'application/x-mspublisher',
				'qt' => 'video/quicktime',
				'ra' => 'audio/x-pn-realaudio', 	// real audio
				'ram' => 'audio/x-pn-realaudio',	// real audio
				'rar' => 'application/rar',
				'rmp' => 'application/octet-stream',	// open workbench
				'rtf' => 'application/msword',
				'shtml' => 'text/html',
				'snd' => 'audio/basic',
				'sql' => 'text/plain',
				'srt' => 'video/subtitle',
				'stc' => 'application/vnd.sun.xml.calc.template',		// open document spreadsheet template
				'std' => 'application/vnd.sun.xml.draw.template',		// open document drawing template
				'sti' => 'application/vnd.sun.xml.impress.template',	// open document presentation template
				'stw' => 'application/vnd.sun.xml.writer.template', 	// open document text template
				'sxc' => 'application/vnd.sun.xml.calc',		// open document spreadsheet
				'sxd' => 'application/vnd.sun.xml.draw',		// open document drawing
				'sxg' => 'application/vnd.sun.xml.writer.global',	// open document master document
				'sxi' => 'application/vnd.sun.xml.impress', 	// open document presentation
				'sxm' => 'application/vnd.sun.xml.math',		// open document formula
				'sxw' => 'application/vnd.sun.xml.writer',		// open document text
				'swf' => 'application/x-shockwave-flash',		// flash
				'tar' => 'application/x-tar',
				'tex' => 'application/x-tex',
				'texi' => 'application/x-texinfo',
				'texinfo' => 'application/x-texinfo',
				'tif' => 'image/tiff',
				'tiff' => 'image/tiff',
				'tgz' => 'application/x-gzip',
				'txt' => 'text/plain',
				'vob' => 'application/octet-stream',
				'vsd' => 'application/visio',
				'wav' => 'audio/x-wav',
				'wax' => 'audio/x-ms-wax',	// windows media player audio playlist
				'wma' => 'audio/x-ms-wma',	// windows media player audio
				'wmv' => 'video/x-ms-wmv',	// windows media player video
				'wri' => 'application/x-mswrite',
				'wvx' => 'video/x-ms-wvx',	// windows media player video playlist
				'xbm' => 'image/x-xbitmap',
				'xls' => 'application/vnd.ms-excel',
				'xlsm' => 'application/vnd.ms-excel',
				'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'xml' => 'text/html',
				'zip' => 'application/zip' );
		}

		return $file_types;
	}

	/**
	 * get newest file
	 *
	 * @return the resulting $item array, with at least keys: 'id', 'file_name', etc.
	 */
	public static function &get_newest() {
		global $context;

		// restrict to files attached to published and not expired pages
		$query = "SELECT files.* FROM ".SQL::table_name('articles')." AS articles "
			."LEFT JOIN ".SQL::table_name('files')." AS files "
			." ON ((files.anchor_type LIKE 'article') AND (files.anchor_id = articles.id))"
			." WHERE NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
			." AND ((articles.expiry_date is NULL)"
			."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."'))"
			." AND ";

		// limit the scope of the request
		$query .= "(files.active='Y'";
		if(Surfer::is_logged())
			$query .= " OR files.active='R'";
		if(Surfer::is_associate())
			$query .= " OR files.active='N'";
		$query .= ")";

		// list freshest files
		$query .= " ORDER BY files.edit_date DESC, files.title LIMIT 0, 1";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get url of next file
	 *
	 * This function is used to build navigation bars.
	 *
	 * @param array the current item
	 * @param string the anchor of the current item
	 * @param string the order, either 'date' or 'title'
	 * @return some text
	 *
	 * @see articles/article.php
	 */
	public static function get_next_url($item, $anchor, $order='date') {
		global $context;

		// sanity check
		if(!is_array($item))
			return $item;

		// limit the scope of the request
		$where = "files.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR files.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR files.active='N'";

		if($order == 'date') {
			$match = "files.edit_date > '".SQL::escape($item['edit_date'])."'";
			$order = 'files.edit_date DESC';
		} elseif($order == 'title') {
			$match = "files.file_name > '".SQL::escape($item['file_name'])."'";
			$order = 'files.file_name';
		} else
			return "unknown order '".$order."'";

		// query the database
		$query = "SELECT id, file_name FROM ".SQL::table_name('files')." AS files "
			." WHERE (files.anchor LIKE '".SQL::escape($anchor)."') AND (".$match.") AND (".$where.")"
			." ORDER BY ".$order." LIMIT 0, 1";
		if(!$result =& SQL::query($query))
			return NULL;

		// no result
		if(!SQL::count($result))
			return NULL;

		// return url of the first item of the list
		$item =& SQL::fetch($result);
		return Files::get_permalink($item);
	}

	/**
	 * get the location for files attached to a given reference
	 *
	 * @param string the reference (e.g., 'article:123')
	 * @param string the name space (e.g., 'files' or 'images')
	 * @return string path to files (e.g., 'files/article/123')
	 *
	 * @see files/edit.php
	 */
	public static function get_path($reference, $space='files') {
		global $context;

		return $space.'/'.$context['virtual_path'].str_replace(':', '/', $reference);
	}

	/**
	 * get permanent address
	 *
	 * @param array page attributes
	 * @return string the permalink
	 */
	public static function get_permalink($item) {
		$output = Files::get_url($item['id'], 'view', $item['file_name']);
		return $output;
	}

	/**
	 * get url of previous file
	 *
	 * This function is used to build navigation bars.
	 *
	 * @param array the current item
	 * @param string the anchor of the current item
	 * @param string the order, either 'date' or 'title'
	 * @return some text
	 *
	 * @see articles/article.php
	 */
	public static function get_previous_url($item, $anchor, $order='date') {
		global $context;

		// sanity check
		if(!is_array($item))
			return $item;

		// limit the scope of the request
		$where = "files.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR files.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR files.active='N'";

		// depending on selected sequence
		if($order == 'date') {
			$match = "files.edit_date < '".SQL::escape($item['edit_date'])."'";
			$order = 'files.edit_date';
		} elseif($order == 'title') {
			$match = "files.file_name < '".SQL::escape($item['file_name'])."'";
			$order = 'files.file_name DESC';
		} else
			return "unknown order '".$order."'";

		// query the database
		$query = "SELECT id, file_name FROM ".SQL::table_name('files')." AS files "
			." WHERE (files.anchor LIKE '".SQL::escape($anchor)."') AND (".$match.") AND (".$where.")"
			." ORDER BY ".$order." LIMIT 0, 1";
		if(!$result =& SQL::query($query))
			return NULL;

		// no result
		if(!SQL::count($result))
			return NULL;

		// return url of the first item of the list
		$item =& SQL::fetch($result);
		return Files::get_permalink($item);
	}

	/**
	 * build a reference to a file
	 *
	 * Depending on parameter '[code]with_friendly_urls[/code]' and on action,
	 * following results can be observed:
	 *
	 * - view - files/view.php?id=123 or files/view.php/123 or file-123
	 *
	 * - other - files/edit.php?id=123 or files/edit.php/123 or file-edit/123
	 *
	 * @param int the id of the file to handle
	 * @param string the expected action ('view', 'print', 'edit', 'delete', ...)
	 * @param string additional data, such as file name, if any
	 * @return string a normalized reference
	 *
	 * @see control/configure.php
	 */
	public static function get_url($id, $action='view', $name=NULL) {
		global $context;

		// get files in rss -- the id has to be an anchor (e.g., 'article:15')
		if($action == 'feed') {
			if($context['with_friendly_urls'] == 'Y')
				return 'files/feed.php/'.str_replace(':', '/', $id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'files/feed.php/'.str_replace(':', '/', $id);
			else
				return 'files/feed.php?anchor='.urlencode($id);
		}

		// add a file -- the id has to be an anchor (e.g., 'article:15')
		if($action == 'file') {
			if($context['with_friendly_urls'] == 'Y')
				return 'files/edit.php/'.str_replace(':', '/', $id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'files/edit.php/'.str_replace(':', '/', $id);
			else
				return 'files/edit.php?anchor='.urlencode($id);
		}

		// confirm the download
		if($action == 'confirm') {
			$action = 'fetch';
			$name = 'confirm';
		}

		// clear assignment
		if($action == 'release') {
			$action = 'fetch';
			$name = 'release';
		}

		// reserve the file
		if($action == 'reserve') {
			$action = 'fetch';
			$name = 'reserve';
		}

		// check the target action
		if(!preg_match('/^(author|delete|edit|fetch|list|stream|thread|view)$/', $action))
			return 'files/'.$action.'.php?id='.urlencode($id).'&action='.urlencode($name);

		// normalize the link
		return normalize_url(array('files', 'file'), $action, $id, $name);
	}

	/**
	 * scan a file for viruses
	 *
	 * This function connects to ClamAV daemon, if possible, to scan the referred file.
	 *
	 * @param string absolute path of the file to scan
	 * @return string 'Y' if the file has been infected, '?' if clamav is not available, or 'N' if no virus has been found
	 */
	public static function has_virus($file) {
		global $context;

		// we can't connect to clamav daemon
		$server = 'localhost';
		if(!$handle = Safe::fsockopen($server, 3310, $errno, $errstr, 1)) {
			if($context['with_debug'] == 'Y')
				Logger::remember('files/files.php', 'Unable to connect to CLAMAV daemon', '', 'debug');
			return '?';
		}

		// ensure enough execution time
		Safe::set_time_limit(30);

		// scan uploaded file
		$request = 'SCAN '.$file;
		fputs($handle, $request.CRLF);
		if($context['with_debug'] == 'Y')
			Logger::remember('files/files.php', 'CLAMAV ->', $request, 'debug');

		// expecting an OK
		if(($reply = fgets($handle)) === FALSE) {
			Logger::remember('files/files.php', 'No reply to SCAN command at '.$server);
			fclose($handle);
			return '?';
		}
//		if($context['with_debug'] == 'Y')
			Logger::remember('files/files.php', 'CLAMAV <-', $reply, 'debug');

		// file has been infected!
		if(!stripos($reply, ': ok')) {
			Logger::remember('files/files.php', 'Infected upload by '.Surfer::get_name());
			fclose($handle);
			return 'Y';
		}

		// everything is ok
		fclose($handle);
		return 'N';

	}

	/**
	 * set the hits counter - errors are not reported, if any
	 *
	 * @param the id of the file to update
	 */
	public static function increment_hits($id) {
		global $context;

		// sanity check
		if(!$id)
			return;

		// do the job
		$query = "UPDATE ".SQL::table_name('files')." SET hits=hits+1 WHERE (id = ".SQL::escape($id).")";
		SQL::query($query);

	}

	/**
	 * integrate some player for the file, if any
	 *
	 * @param array the file to look at
	 * @param int width for video player
	 * @param int height of video player
	 * @return string tags to be put in the HTML flow, or an empty string
	 */
	public static function interact($item, $width=320, $height=240, $flashvars='', $with_icon=TRUE) {
		global $context;

		static $counter;
		if(!isset($counter))
			$counter = 1;
		else
			$counter++;

		// several ways to play flash
		switch(strtolower(substr($item['file_name'], -3))) {

		// audio file handled by dewplayer
		case 'mp3':

			// only if the player is available
			if(file_exists($context['path_to_root'].'included/browser/dewplayer.swf')) {

				// the player
				$dewplayer_url = $context['url_to_root'].'included/browser/dewplayer.swf';

				// the mp3 file
				if(isset($item['file_href']) && $item['file_href'])
					$mp3_url = $item['file_href'];
				else
					$mp3_url = $context['url_to_root'].Files::get_url($item['id'], 'fetch', $item['file_name']);
				$flashvars = 'son='.$mp3_url;

				// combine the two in a single object
				return '<div id="interact_'.$counter.'" class="no_print">Flash plugin or Javascript are turned off. Activate both and reload to view the object</div>'."\n"
					.JS_PREFIX
					.'var params = {};'."\n"
					.'params.base = "'.dirname($mp3_url).'/";'."\n"
					.'params.quality = "high";'."\n"
					.'params.wmode = "transparent";'."\n"
					.'params.menu = "false";'."\n"
					.'params.flashvars = "'.$flashvars.'";'."\n"
					.'swfobject.embedSWF("'.$dewplayer_url.'", "interact_'.$counter.'", "200", "20", "6", "'.$context['url_to_home'].$context['url_to_root'].'included/browser/expressinstall.swf", false, params);'."\n"
					.JS_SUFFIX.BR."\n";

			}

		// native flash
		case 'swf':

			// where to get the file
			if(isset($item['file_href']) && $item['file_href'])
				$url = $item['file_href'];

			// we provide the native file because of basename
			else
				$url = $context['url_to_home'].$context['url_to_root'].'files/'.str_replace(':', '/', $item['anchor']).'/'.rawurlencode($item['file_name']);

			$output = '<div id="interact_'.$counter.'" class="no_print">Flash plugin or Javascript are turned off. Activate both and reload to view the object</div><br />'."\n"
				.JS_PREFIX
				.'var params = {};'."\n"
				.'params.base = "'.dirname($url).'/";'."\n"
				.'params.quality = "high";'."\n"
				.'params.wmode = "transparent";'."\n"
				.'params.allowfullscreen = "true";'."\n"
				.'params.allowscriptaccess = "always";'."\n"
				.'params.flashvars = "'.$flashvars.'";'."\n"
				.'swfobject.embedSWF("'.$url.'", "interact_'.$counter.'", "'.$width.'", "'.$height.'", "6", "'.$context['url_to_home'].$context['url_to_root'].'included/browser/expressinstall.swf", false, params);'."\n"
				.JS_SUFFIX;
			return $output;

		// stream a video
		case '3gp':
		case 'flv':
		case 'm4v':
		case 'mov':
		case 'mp4':

			// a flash player to stream a flash video
			$flvplayer_url = $context['url_to_home'].$context['url_to_root'].'included/browser/player_flv_maxi.swf';

			// file is elsewhere
			if(isset($item['file_href']) && $item['file_href'])
				$url = $item['file_href'];

			// prevent leeching (the flv player will provide session cookie, etc)
			else
				$url = $context['url_to_home'].$context['url_to_root'].Files::get_url($item['id'], 'fetch', $item['file_name']);

			// pass parameters to the player
			if($flashvars)
				$flashvars = str_replace('autostart=true', 'autoplay=1', $flashvars).'&';
			$flashvars .= 'width='.$width.'&height='.$height;

			// rely on Flash
			if(Surfer::has_flash()) {

				// the full object is built in Javascript --see parameters at http://flv-player.net/players/maxi/documentation/
				$output = '<div id="interact_'.$counter.'" class="no_print">Flash plugin or Javascript are turned off. Activate both and reload to view the object</div>'."\n"
					.JS_PREFIX
					.'var flashvars = { flv:"'.$url.'", '.str_replace(array('&', '='), array('", ', ':"'), $flashvars).'", autoload:0, margin:1, showiconplay:1, playeralpha:50, iconplaybgalpha:30, showloading:"always", ondoubleclick:"fullscreen" }'."\n"
					.'var params = { allowfullscreen: "true", allowscriptaccess: "always" }'."\n"
					.'var attributes = { id: "interact_'.$counter.'", name: "file_'.$item['id'].'"}'."\n"
					.'swfobject.embedSWF("'.$flvplayer_url.'", "interact_'.$counter.'", "'.$width.'", "'.$height.'", "9", "'.$context['url_to_home'].$context['url_to_root'].'included/browser/expressinstall.swf", flashvars, params);'."\n"
					.JS_SUFFIX."\n";

			// native support
			} else {

				$output = '<object width="'.$width.'" height="'.$height.'" data="'.$url.'" type="'.Files::get_mime_type($item['file_name']).'">'."\n"
					.'	<param value="'.$url.'" name="movie" />'."\n"
					.'	<param value="true" name="allowFullScreen" />'."\n"
					.'	<param value="always" name="allowscriptaccess" />'."\n"
					.'	<a href="'.$url.'">No video playback capabilities, please download the file</a>'."\n"
					.'</object>'."\n";

			}

			return $output;

		}

		if(!$with_icon)
			return '';

		// explicit icon
		if($item['thumbnail_url'])
			$icon = $item['thumbnail_url'];

		// or reinforce file type
		else
			$icon = $context['url_to_root'].Files::get_icon_url($item['file_name']);

		// a clickable image to access the file
		if($icon) {
			$icon = '<img src="'.$icon.'" />';
			return Skin::build_link(Files::get_permalink($item), $icon, 'basic').BR;
		}

		// nothing special
		return '';

	}

	public static function is_audio_stream($name) {
		return preg_match('/\.(aif|aiff|au|mka|mp2|mp3|ra|snd|wav|wma)$/i', $name);
	}

	/**
	 * check if a file type is authorized
	 *
	 * This function is based on the growing list of extensions officially supported by YACS.
	 * It also considers additional extensions set in the configuration panel for files.
	 *
	 * @see files/configure.php
	 *
	 * @param string file name, including extension
	 * @return TRUE or FALSE
	 *
	 * @see agents/messages.php
	 * @see files/edit.php
	 * @see files/view.php
	 */
	public static function is_authorized($name) {
		global $context;

		// create the pattern only once
		static $extensions_pattern;
		if(!is_string($extensions_pattern))
			$extensions_pattern = implode(', ', array_keys(Files::get_mime_types()));

		// we do need some extension
		if(($position = strrpos($name, '.')) === FALSE)
			return FALSE;

		// extract the extension
		if(!$extension = substr($name, $position+1))
			return FALSE;

		// match official extensions
		if(preg_match('/\b'.preg_quote($extension, '/').'\b/i', $extensions_pattern))
			return TRUE;

		// load parameters related to files
		Safe::load('parameters/files.include.php');

		// match additional extensions
		if(isset($context['files_extensions']) && $context['files_extensions'] && preg_match('/\b'.preg_quote($extension, '/').'\b/i', $context['files_extensions']))
			return TRUE;

		// no match
		return FALSE;
	}

	/**
	 * the file can be embedded in a page
	 *
	 * This function returns TRUE for following file types:
	 * - flv
	 * - m4v
	 * - mov
	 * - mp4
	 * - swf
	 *
	 * @param string file name, including extension
	 * @return TRUE or FALSE
	 *
	 */
	public static function is_embeddable($name) {
		return preg_match('/\.(flv|gan|mov|m4v|mp4|swf)$/i', $name);
	}

	/**
	 * is this file can be streamed separately
	 *
	 * @param string file name, including extension
	 * @return TRUE or FALSE
	 *
	 */
	public static function is_stream($name) {
		return Files::is_audio_stream($name) || Files::is_video_stream($name) || preg_match('/\.(gan|mm|swf)$/i', $name);
	}

	/**
	 * look for potential video streams
	 *
	 * This function returns TRUE for following file types:
	 * - 3gp
	 * - flv
	 * - m4v
	 * - mov
	 * - mp4
	 *
	 * @param string file name, including extension
	 * @return TRUE or FALSE
	 *
	 */
	public static function is_video_stream($name) {
		return preg_match('/\.(3gp|flv|m4v|mov|mp4)$/i', $name);
	}

	/**
	 * list newest files
	 *
	 * To build a simple box of the newest files, just use
	 * the following example:
	 * [php]
	 * // side bar with the list of most recent files
	 * $items = Files::list_by_date(0, 10);
	 * $text = Skin::build_list($items, 'compact');
	 * $context['text'] .= Skin::build_box($title, $text, 'navigation');
	 * [/php]
	 *
	 * You can also display the newest file separately, using [code]Files::get_newest()[/code]
	 * In this case, skip the very first file in the list by using
	 * [code]Files::list_by_date(1, 10)[/code]
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1 - default is 0
	 * @param int the number of items to display - default is 10
	 * @param string the list variant, if any - default is 'dates'
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see index.php
	 * @see files/feed.php
	 * @see files/index.php
	 */
	public static function &list_by_date($offset=0, $count=10, $variant='dates') {
		global $context;

		// if not associate, restrict to files attached to published and not expired pages
		if(!Surfer::is_associate())
			$query = "SELECT files.* FROM ".SQL::table_name('articles')." AS articles "
				."LEFT JOIN ".SQL::table_name('files')." AS files "
				." ON ((files.anchor_type LIKE 'article') AND (files.anchor_id = articles.id))"
				." WHERE NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."'))"
				." AND ";

		else
			$query = "SELECT files.* FROM ".SQL::table_name('files')." AS files "
				."WHERE ";

		// limit the scope of the request
		$query .= "(files.active='Y'";
		if(Surfer::is_logged())
			$query .= " OR files.active='R'";
		if(Surfer::is_associate())
			$query .= " OR files.active='N'";
		$query .= ")";

		// list freshest files
		$query .= " ORDER BY files.edit_date DESC, files.title LIMIT ".$offset.','.$count;

		$output =& Files::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list newest files for one anchor
	 *
	 * Files are sorted according to creation date, therefore file replacement or update does not change the order.
	 * With this scheme newest files are listed first, which makes sense most of the time for things attached to a page.
	 *
	 * Example:
	 * [php]
	 * $items = Files::list_by_date_for_anchor('section:12', 0, 10);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 * [/php]
	 *
	 * @param int the id of the anchor
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @param array some ids to not list, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see articles/fetch_as_msword.php
	 * @see articles/fetch_as_pdf.php
	 * @see articles/layout_articles_as_alistapart.php
	 * @see articles/print.php
	 * @see articles/view.php
	 * @see categories/layout_categories_as_inline.php
	 * @see categories/print.php
	 * @see categories/view.php
	 * @see files/feed.php
	 * @see files/fetch_all.php
	 * @see sections/layout_sections.php
	 * @see sections/layout_sections_as_folded.php
	 * @see sections/layout_sections_as_inline.php
	 * @see sections/layout_sections_as_yahoo.php
	 * @see sections/print.php
	 * @see sections/view.php
	 * @see users/print.php
	 * @see users/view.php
	 */
	public static function &list_by_date_for_anchor($anchor, $offset=0, $count=20, $variant='no_anchor', $avoid=NULL) {
		global $context;

		// limit the scope of the request
		$where = "files.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR files.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR files.active='N'";
		$where = '('.$where.')';

		// ids to avoid
		if($avoid)
			$where .= ' AND (files.id NOT IN ('.join(',', $avoid).'))';

		// list items attached to this anchor, or to articles anchored to this anchor, or to articles anchored to sub-sections of this anchor
		if($anchor && ($variant == 'feed')) {

				// files attached directly to this anchor
			$query = "(SELECT files.* FROM ".SQL::table_name('files')." AS files"
				." WHERE (files.anchor LIKE '".SQL::escape($anchor)."') AND ".$where.")"

				." UNION"

				// files attached to sections attached to this anchor
				." (SELECT files.* FROM ".SQL::table_name('sections')." AS sections "
				." LEFT JOIN ".SQL::table_name('files')." AS files "
				." ON ((files.anchor_type LIKE 'section') AND (files.anchor_id = sections.id))"
				." WHERE (sections.anchor LIKE '".SQL::escape($anchor)."')"
				." AND ".$where.")"

				." UNION"

				// files attached to articles attached to this anchor
				." (SELECT files.* FROM ".SQL::table_name('articles')." AS articles "
				." LEFT JOIN ".SQL::table_name('files')." AS files "
				." ON ((files.anchor_type LIKE 'article') AND (files.anchor_id = articles.id))"
				." WHERE (articles.anchor LIKE '".SQL::escape($anchor)."')"
				." AND ".$where
				// restrict to files attached to published not expired pages
				." AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."')))"

				." UNION"

				// files attached to articles attached to sections attached to this anchor
				." (SELECT files.* FROM ".SQL::table_name('files')." AS files USE INDEX (anchor)"
				." LEFT JOIN ".SQL::table_name('articles')." AS articles "
				." ON ((files.anchor_type LIKE 'article') AND (files.anchor_id = articles.id))"
				." LEFT JOIN ".SQL::table_name('sections')." AS sections "
				." ON ((articles.anchor_type LIKE 'section') AND (articles.anchor_id = sections.id))"
				." WHERE (sections.anchor LIKE '".SQL::escape($anchor)."')"
				." AND ".$where
				// restrict to files attached to published not expired pages
				." AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."')))"

				." ORDER BY edit_date DESC, title LIMIT ".$offset.','.$count;

		// list items attached directly to this anchor
		} else
			$query = "SELECT * FROM ".SQL::table_name('files')." AS files "
				." WHERE (files.anchor LIKE '".SQL::escape($anchor)."') AND ".$where
				." ORDER BY edit_date DESC, files.title LIMIT ".$offset.','.$count;

		// the list of files
		$output =& Files::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list newest files for one author
	 *
	 * This function lists most recent files uploaded by a member.
	 *
	 * Example:
	 * [php]
	 * $items = Files::list_by_date_for_author(12, 0, 10);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 * [/php]
	 *
	 * @param int the id of the author of the file
	 * @param int the offset from the start of the list; usually, 0 or 1 - default is 0
	 * @param int the number of items to display - default is 20
	 * @param string the list variant, if any - default is 'no_author'
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see users/view.php
	 */
	public static function &list_by_date_for_author($author_id, $offset=0, $count=20, $variant='no_author') {
		global $context;

		// limit the scope of the request
		$where = "files.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR files.active='R'";
		if(Surfer::is_associate())
			$where .= " OR files.active='N'";

		// the list of files
		$query = "SELECT * FROM ".SQL::table_name('files')." AS files "
			." WHERE (files.edit_id = ".SQL::escape($author_id).") AND (".$where.")"
			." ORDER BY files.edit_date DESC, files.title LIMIT ".$offset.','.$count;

		$output =& Files::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list most popular files
	 *
	 * To build a simple box of most read files, use following example:
	 * [php]
	 * $context['text'] .= Skin::build_list(Files::list_by_hits(), 'compact');
	 * [/php]
	 *
	 * You can also display the most read file separately, using Files::get_most_read()
	 * In this case, skip the very first file in the list by using
	 * Files::list_by_hits(1)
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1 - default is 0
	 * @param int the number of items to display - default is 10
	 * @param string the list variant, if any - default is 'hits'
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see index.php
	 * @see files/index.php
	 */
	public static function &list_by_hits($offset=0, $count=10, $variant='hits') {
		global $context;

		// if not associate, restrict to files attached to published not expired pages
		if(!Surfer::is_associate()) {
			$where = "LEFT JOIN ".SQL::table_name('articles')." AS articles "
				." ON ((files.anchor_type LIKE 'article') AND (files.anchor_id = articles.id))"
				." WHERE NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."'))"
				." AND ";
		} else
			$where = "WHERE ";

		// limit the scope of the request
		$where .= "(files.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR files.active='R'";
		if(Surfer::is_associate())
			$where .= " OR files.active='N'";
		$where .= ")";

		// the list of files
		$query = "SELECT * FROM ".SQL::table_name('files')." AS files ".$where
			." ORDER BY files.hits DESC, files.title LIMIT ".$offset.','.$count;

		$output =& Files::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list most popular files for one author
	 *
	 * Example:
	 * [php]
	 * $items = Files::list_by_hits_for_author(12, 0, 10);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 * [/php]
	 *
	 * @param int the id of the author of the file
	 * @param int the offset from the start of the list; usually, 0 or 1 - default is 0
	 * @param int the number of items to display - default is 10
	 * @param string the list variant, if any - default is 'hits'
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see users/view.php
	 */
	public static function &list_by_hits_for_author($author_id, $offset=0, $count=10, $variant='hits') {
		global $context;

		// limit the scope of the request
		$where = "files.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR files.active='R'";
		if(Surfer::is_associate())
			$where .= " OR files.active='N'";

		// the list of files
		$query = "SELECT * FROM ".SQL::table_name('files')." AS files "
			." WHERE (files.edit_id = ".SQL::escape($author_id).") AND (".$where.")"
			." ORDER BY files.hits DESC, files.edit_date DESC, files.title LIMIT ".$offset.','.$count;

		$output =& Files::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list oldest files
	 *
	 * This function never lists inactive files. It is aiming to provide a simple list
	 * of the most old files to put in simple boxes.
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1 - default is 0
	 * @param int the number of items to display - default is 10
	 * @param string the list variant, if any - default is 'full'
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see files/review.php
	 */
	public static function &list_by_oldest_date($offset=0, $count=10, $variant='full') {
		global $context;

		// limit the scope of the request
		$where = "files.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR files.active='R'";
		if(Surfer::is_associate())
			$where .= " OR files.active='N'";

		// the list of files
		$query = "SELECT * FROM ".SQL::table_name('files')." AS files "
			." WHERE ".$where
			." ORDER BY files.edit_date, files.title LIMIT ".$offset.','.$count;

		$output =& Files::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list biggest files
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see files/review.php
	 */
	public static function &list_by_size($offset=0, $count=10, $variant='full') {
		global $context;

		// if not associate, restrict to files attached to published not expired pages
		if(!Surfer::is_associate()) {
			$where = "LEFT JOIN ".SQL::table_name('articles')." AS articles "
				." ON ((files.anchor_type LIKE 'article') AND (files.anchor_id = articles.id))"
				." WHERE NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."'))"
				." AND ";
		} else
			$where = "WHERE ";

		// limit the scope of the request
		$where .= "(files.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR files.active='R'";
		if(Surfer::is_associate())
			$where .= " OR files.active='N'";
		$where .= ")";

		// the list of files
		$query = "SELECT files.* FROM ".SQL::table_name('files')." AS files ".$where
			." ORDER BY files.file_size DESC, files.title LIMIT ".$offset.','.$count;

		$output =& Files::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list files by title for one anchor
	 *
	 * Example:
	 * [php]
	 * $items = Files::list_by_title_for_anchor('article:12');
	 * $context['text'] .= Skin::build_list($items, 'decorated');
	 * [/php]
	 *
	 * @param int the id of the anchor
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @param array some ids to not list, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see articles/fetch_as_msword.php
	 * @see articles/fetch_as_pdf.php
	 * @see articles/layout_articles_as_alistapart.php
	 * @see articles/print.php
	 * @see articles/view.php
	 * @see categories/layout_categories_as_inline.php
	 * @see categories/print.php
	 * @see categories/view.php
	 * @see files/feed.php
	 * @see files/fetch_all.php
	 * @see sections/layout_sections_as_folded.php
	 * @see sections/layout_sections_as_inline.php
	 * @see sections/print.php
	 * @see sections/view.php
	 */
	public static function &list_by_title_for_anchor($anchor, $offset=0, $count=10, $variant='no_anchor', $avoid=NULL) {
		global $context;

		// limit the scope of the request
		$where = "files.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR files.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR files.active='N'";
		$where = '('.$where.')';

		// ids to avoid
		if($avoid)
			$where .= ' AND (files.id NOT IN ('.join(',', $avoid).'))';

		// list items attached to this anchor, or to articles anchored to this anchor, or to articles anchored to sub-sections of this anchor
		if($anchor && ($variant == 'feed')) {

			// files attached directly to this anchor
			$query = "(SELECT files.* FROM ".SQL::table_name('files')." AS files"
				." WHERE (files.anchor LIKE '".SQL::escape($anchor)."') AND ".$where.")"

				." UNION"

				// files attached to sections attached to this anchor
				." (SELECT files.* FROM ".SQL::table_name('sections')." AS sections "
				." LEFT JOIN ".SQL::table_name('files')." AS files "
				." ON ((files.anchor_type LIKE 'section') AND (files.anchor_id = sections.id))"
				." WHERE (sections.anchor LIKE '".SQL::escape($anchor)."')"
				." AND ".$where.")"

				." UNION"

				// files attached to articles attached to this anchor
				." (SELECT files.* FROM ".SQL::table_name('articles')." AS articles "
				." LEFT JOIN ".SQL::table_name('files')." AS files "
				." ON ((files.anchor_type LIKE 'article') AND (files.anchor_id = articles.id))"
				." WHERE (articles.anchor LIKE '".SQL::escape($anchor)."')"
				." AND ".$where
				// restrict to files attached to published not expired pages
				." AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."')))"

				." UNION"

				// files attached to articles attached to sections attached to this anchor
				." (SELECT files.* FROM ".SQL::table_name('files')." AS files USE INDEX (anchor)"
				." LEFT JOIN ".SQL::table_name('articles')." AS articles "
				." ON ((files.anchor_type LIKE 'article') AND (files.anchor_id = articles.id))"
				." LEFT JOIN ".SQL::table_name('sections')." AS sections "
				." ON ((articles.anchor_type LIKE 'section') AND (articles.anchor_id = sections.id))"
				." WHERE (sections.anchor LIKE '".SQL::escape($anchor)."')"
				." AND ".$where
				// restrict to files attached to published not expired pages
				." AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."')))"

				." ORDER BY  files.title, files.file_name, files.edit_date DESC LIMIT ".$offset.','.$count;

		// list items attached directly to this anchor
		} else
			$query = "SELECT * FROM ".SQL::table_name('files')." AS files "
				." WHERE (files.anchor LIKE '".SQL::escape($anchor)."') AND ".$where
				." ORDER BY files.title, files.file_name, files.edit_date DESC LIMIT ".$offset.','.$count;

		// the list of files
		$output =& Files::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list embeddable files for one anchor
	 *
	 * @param string reference to the anchor
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see articles/edit.php
	 * @see sections/edit.php
	 */
	public static function &list_embeddable_for_anchor($anchor, $offset=0, $count=20, $variant='embeddable') {
		global $context;

		// limit the scope of the request
		$where = "files.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR files.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR files.active='N'";
		$where = '('.$where.')';

		// list items attached directly to this anchor
		$query = "SELECT * FROM ".SQL::table_name('files')." AS files "
			." WHERE (files.anchor LIKE '".SQL::escape($anchor)."') AND ".$where
			." ORDER BY edit_date DESC, files.title LIMIT ".$offset.','.$count;

		// the list of files
		$output =& Files::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list selected files
	 *
	 * If variant is provided as a string, the functions looks for a script featuring this name.
	 * E.g., for variant 'compact', the file 'files/layout_files_as_compact.php' is loaded.
	 * If no file matches then the default 'files/layout_files.php' script is loaded.
	 *
	 * @param resource result of database query
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @param string '?' or 'A', to support editors and to impersonate associates, where applicable
	 * @return an array of $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see skins/skin_skeleton.php
	 * @see files/fetch_all.php
	 */
	public static function &list_selected(&$result, $variant='compact') {
		global $context;

		// no result
		if(!$result) {
			$output = NULL;
			return $output;
		}

		// special layout
		if(is_object($variant)) {
			$output =& $variant->layout($result);
			return $output;
		}

		// no layout yet
		$layout = NULL;

		// separate options from layout name
		$attributes = explode(' ', $variant, 2);

		// instanciate the provided name
		if($attributes[0]) {
			$name = 'layout_files_as_'.$attributes[0];
			if(is_readable($context['path_to_root'].'files/'.$name.'.php')) {
				include_once $context['path_to_root'].'files/'.$name.'.php';
				$layout = new $name;

				// provide parameters to the layout
				if(isset($attributes[1]))
					$layout->set_variant($attributes[1]);

			}
		}

		// use default layout
		if(!$layout) {
			include_once $context['path_to_root'].'files/layout_files.php';
			$layout = new Layout_files();
			$layout->set_variant($variant);
		}

		// do the job
		$output =& $layout->layout($result);
		return $output;

	}

	/**
	 * list less downloaded files
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1 - default is 0
	 * @param int the number of items to display - default is 10
	 * @param string the list variant, if any - default is 'hits'
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	public static function &list_unused($offset=0, $count=10, $variant='full') {
		global $context;

		// if not associate, restrict to files attached to published not expired pages
		if(!Surfer::is_associate()) {
			$where = ", ".SQL::table_name('articles')." AS articles "
				." WHERE ((files.anchor_type LIKE 'article') AND (files.anchor_id = articles.id))"
				." AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."'))"
				." AND ";
		} else
			$where = "WHERE ";

		// limit the scope of the request - hidden files are never listed here
		$where .= "(files.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR files.active='R'";
		$where .= ")";

		// the list of files
		$query = "SELECT files.* FROM ".SQL::table_name('files')." AS files ".$where
			." ORDER BY files.hits, files.title LIMIT ".$offset.','.$count;

		$output =& Files::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * post a new file or an updated file
	 *
	 * This function populates the error context, where applicable.
	 *
	 * @param array an array of fields
	 * @param string to support editors -- see files/edit.php
	 * @return the id of the new file, or FALSE on error
	 *
	 * @see agents/messages.php
	 * @see files/author.php
	 * @see files/edit.php
	**/
	public static function post(&$fields) {
		global $context;

		// no anchor reference
		if(!isset($fields['anchor']) || !$fields['anchor'] || (!$anchor =& Anchors::get($fields['anchor']))) {
			Logger::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// protect from hackers
		if(isset($fields['icon_url']))
			$fields['icon_url'] =& encode_link($fields['icon_url']);
		if(isset($fields['thumbnail_url']))
			$fields['thumbnail_url'] =& encode_link($fields['thumbnail_url']);

		// protect access from anonymous users
		if(!isset($fields['active_set']))
			$fields['active_set'] = 'Y';

		// cascade anchor access rights
		$fields['active'] = $anchor->ceil_rights($fields['active_set']);

		// set default values for this editor
		Surfer::check_default_editor($fields);

		// reinforce date formats
		if(!isset($fields['create_date']) || ($fields['create_date'] <= NULL_DATE))
			$fields['create_date'] = $fields['edit_date'];

		// columns updated
		$query = array();

		// update the existing record
		if(isset($fields['id'])) {

			// id cannot be empty
			if(!isset($fields['id']) || !is_numeric($fields['id'])) {
				Logger::error(i18n::s('No item has the provided id.'));
				return FALSE;
			}

			// an actual upload has taken place --change modification date and reset detach data
			if(isset($fields['file_name']) && ($fields['file_name'] != 'none')) {
				$query[] = "assign_address=''";
				$query[] = "assign_date=''";
				$query[] = "assign_id=''";
				$query[] = "assign_name=''";
				$query[] = "create_address='".SQL::escape($fields['edit_address'])."'";
				$query[] = "create_date='".SQL::escape($fields['edit_date'])."'";
				$query[] = "create_id=".SQL::escape($fields['edit_id']);
				$query[] = "create_name='".SQL::escape($fields['edit_name'])."'";
				$query[] = "edit_address='".SQL::escape($fields['edit_address'])."'";
				$query[] = "edit_action='file:update'";
				$query[] = "edit_date='".SQL::escape($fields['edit_date'])."'";
				$query[] = "edit_id=".SQL::escape($fields['edit_id']);
				$query[] = "edit_name='".SQL::escape($fields['edit_name'])."'";
				$query[] = "file_name='".SQL::escape($fields['file_name'])."'";
				$query[] = "file_size='".SQL::escape($fields['file_size'])."'";
			}

			// fields that are visible only to people allowed to update a file
			if(Surfer::is_member()) {
				$query[] = "active='".SQL::escape($fields['active'])."'";
				$query[] = "active_set='".SQL::escape($fields['active_set'])."'";
				$query[] = "icon_url='".SQL::escape(isset($fields['icon_url']) ? $fields['icon_url'] : '')."'";
				$query[] = "thumbnail_url='".SQL::escape(isset($fields['thumbnail_url']) ? $fields['thumbnail_url'] : '')."'";
			}

			// regular fields
			$query[] = "alternate_href='".SQL::escape(isset($fields['alternate_href']) ? $fields['alternate_href'] : '')."'";
			$query[] = "behaviors='".SQL::escape(isset($fields['behaviors']) ? $fields['behaviors'] : '')."'";
			if(isset($fields['description']))
				$query[] = "description='".SQL::escape($fields['description'])."'";
			$query[] = "file_href='".SQL::escape(isset($fields['file_href']) ? $fields['file_href'] : '')."'";
			$query[] = "keywords='".SQL::escape(isset($fields['keywords']) ? $fields['keywords'] : '')."'";
			$query[] = "source='".SQL::escape(isset($fields['source']) ? $fields['source'] : '')."'";
			$query[] = "title='".SQL::escape(isset($fields['title']) ? $fields['title'] : '')."'";

			// build the full query
			$query = "UPDATE ".SQL::table_name('files')." SET ".join(', ', $query)." WHERE id = ".SQL::escape($fields['id']);

			// actual insert
			if(SQL::query($query) === FALSE)
				return FALSE;

		// insert a new record
		} elseif(isset($fields['file_name']) && $fields['file_name'] && isset($fields['file_size']) && $fields['file_size']) {

			$query[] = "active='".SQL::escape($fields['active'])."'";
			$query[] = "active_set='".SQL::escape($fields['active_set'])."'";
			$query[] = "alternate_href='".SQL::escape(isset($fields['alternate_href']) ? $fields['alternate_href'] : '')."'";
			$query[] = "anchor='".SQL::escape($fields['anchor'])."'";
			$query[] = "anchor_id=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', -1)";
			$query[] = "anchor_type=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', 1)";
			$query[] = "behaviors='".SQL::escape(isset($fields['behaviors']) ? $fields['behaviors'] : '')."'";
			$query[] = "create_name='".SQL::escape(isset($fields['create_name']) ? $fields['create_name'] : $fields['edit_name'])."'";
			$query[] = "create_id=".SQL::escape(isset($fields['create_id']) ? $fields['create_id'] : $fields['edit_id']);
			$query[] = "create_address='".SQL::escape(isset($fields['create_address']) ? $fields['create_address'] : $fields['edit_address'])."'";
			$query[] = "create_date='".SQL::escape($fields['create_date'])."'";
			$query[] = "description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."'";
			$query[] = "edit_name='".SQL::escape($fields['edit_name'])."'";
			$query[] = "edit_id=".SQL::escape($fields['edit_id']);
			$query[] = "edit_address='".SQL::escape($fields['edit_address'])."'";
			$query[] = "edit_action='file:create'";
			$query[] = "edit_date='".SQL::escape($fields['edit_date'])."'";
			$query[] = "file_name='".SQL::escape($fields['file_name'])."'";
			$query[] = "file_href='".SQL::escape(isset($fields['file_href']) ? $fields['file_href'] : '')."'";
			$query[] = "file_size='".SQL::escape($fields['file_size'])."'";
			$query[] = "hits=0";
			$query[] = "icon_url='".SQL::escape(isset($fields['icon_url']) ? $fields['icon_url'] : '')."'";
			$query[] = "keywords='".SQL::escape(isset($fields['keywords']) ? $fields['keywords'] : '')."'";
			$query[] = "source='".SQL::escape(isset($fields['source']) ? $fields['source'] : '')."'";
			$query[] = "thumbnail_url='".SQL::escape(isset($fields['thumbnail_url']) ? $fields['thumbnail_url'] : '')."'";
			$query[] = "title='".SQL::escape(isset($fields['title']) ? $fields['title'] : '')."'";

			// build the full query
			$query = "INSERT INTO ".SQL::table_name('files')." SET ".join(', ', $query);

			// actual insert
			if(SQL::query($query) === FALSE)
				return FALSE;

			// remember the id of the new item
			$fields['id'] = SQL::get_last_id($context['connection']);

		// nothing done
		} else {
			Logger::error(i18n::s('Nothing has been received. Ensure you are below size limits set for this server.'));
			return FALSE;
		}

		// clear the cache for files
		Files::clear($fields);

		// end of job
		return $fields['id'];
	}

	/**
	 * search for some keywords in all files
	 *
	 * Only files matching following criteria are returned:
	 * - file is visible (active='Y')
	 * - file is restricted (active='R'), but surfer is a logged user
	 * - file is restricted (active='N'), but surfer is an associate
	 *
	 * @param string searched tokens
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	public static function &search($pattern, $offset=0, $count=50, $variant='search') {
		global $context;

		// sanity check
		if(!$pattern = trim($pattern)) {
			$output = NULL;
			return $output;
		}

		// limit the scope of the request
		$where = "files.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR files.active='R'";
		if(Surfer::is_associate())
			$where .= " OR files.active='N'";
		$where = '('.$where.')';

		// match
		$match = "MATCH(title, source, description, keywords) AGAINST('".SQL::escape($pattern)."' IN BOOLEAN MODE)";

		// the list of files
		$query = "SELECT * FROM ".SQL::table_name('files')." AS files "
			." WHERE ".$where." AND $match"
			." ORDER BY files.edit_date DESC"
			." LIMIT ".$offset.','.$count;

		$output =& Files::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * create tables for files
	 */
	public static function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['active']		= "ENUM('Y','R','N') DEFAULT 'Y' NOT NULL";
		$fields['active_set']	= "ENUM('Y','R','N') DEFAULT 'Y' NOT NULL";
		$fields['alternate_href']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['anchor']		= "VARCHAR(64) DEFAULT 'section:1' NOT NULL";
		$fields['anchor_id']	= "MEDIUMINT UNSIGNED NOT NULL";
		$fields['anchor_type']	= "VARCHAR(64) DEFAULT 'section' NOT NULL";
		$fields['assign_address']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['assign_date']	= "DATETIME";
		$fields['assign_id']	= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['assign_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['behaviors']	= "TEXT NOT NULL";
		$fields['create_address']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['create_date']	= "DATETIME";
		$fields['create_id']	= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['create_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['description']	= "TEXT NOT NULL";
		$fields['edit_action']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_address'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_date']	= "DATETIME";
		$fields['edit_id']		= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['edit_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['file_href']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['file_name']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['file_size']	= "INT UNSIGNED DEFAULT 0 NOT NULL";
		$fields['hits'] 		= "INT UNSIGNED DEFAULT 0 NOT NULL";
		$fields['icon_url'] 	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['keywords'] 	= "TEXT NOT NULL";
		$fields['source']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['thumbnail_url']= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['title']		= "VARCHAR(255) DEFAULT '' NOT NULL";

		$indexes = array();
		$indexes['PRIMARY KEY'] 		= "(id)";
		$indexes['INDEX active']		= "(active)";
		$indexes['INDEX anchor']		= "(anchor)";
		$indexes['INDEX anchor_id'] 	= "(anchor_id)";
		$indexes['INDEX anchor_type']	= "(anchor_type)";
		$indexes['INDEX create_date']	= "(create_date)";
		$indexes['INDEX create_id'] 	= "(create_id)";
		$indexes['INDEX edit_date'] 	= "(edit_date)";
		$indexes['INDEX edit_id']		= "(edit_id)";
		$indexes['INDEX file_size'] 	= "(file_size)";
		$indexes['INDEX hits']			= "(hits)";
		$indexes['INDEX title'] 		= "(title(255))";
		$indexes['FULLTEXT INDEX']		= "full_text(title, source, description, keywords)";

		return SQL::setup_table('files', $fields, $indexes);
	}

	/**
	 * get some statistics for all files
	 *
	 * @return the resulting ($count, $oldest_date, $newest_date, $total_size) array
	 *
	 * @see files/index.php
	 */
	public static function &stat() {
		global $context;

		// limit the scope of the request
		$where = "(files.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR files.active='R'";
		if(Surfer::is_associate())
			$where .= " OR files.active='N'";
		$where .= ")";

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(files.edit_date) as oldest_date, MAX(files.edit_date) as newest_date"
			.", SUM(file_size) as total_size"
			." FROM ".SQL::table_name('files')." AS files WHERE ".$where;

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get some statistics for one anchor
	 *
	 * @param the selected anchor (e.g., 'article:12')
	 * @return the resulting ($count, $oldest_date, $newest_date, $total_size) array
	 */
	public static function &stat_for_anchor($anchor) {
		global $context;

		// sanity check
		if(!$anchor)
			return NULL;

		// limit the scope of the request
		$where = "files.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR files.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR files.active='N'";

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date"
			.", SUM(file_size) as total_size"
			." FROM ".SQL::table_name('files')." AS files"
			." WHERE files.anchor LIKE '".SQL::escape($anchor)."' AND (".$where.")";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * adapt GanttProject file to SIMILE Timeline format
	 *
	 * @param string file location
	 * @return string transformation result, or FALSE
	 */
	public static function transform_gan_to_simile($file_path) {
		global $context;

		// load the file
		$content = Safe::file_get_contents($file_path);

		// used by parsing functions
		$context['gan2simile'] = array();
		$context['gan2simile']['depth'] = 0;
		$context['gan2simile']['tasks'] = array();
		$context['gan2simile']['last_id'] = 0;
		$context['gan2simile']['current_id'] = 0;

		// one tag at a time
		function g2s_startElement($parser, $name, $attrs) {
			global $context;

			// remember task basic attributes
			if(!strcmp($name, 'TASK')) {

				if($context['gan2simile']['depth'] < 5) {

					// flag duration if not milestone
					if($attrs['DURATION'] > 0)
						$duration = TRUE;
					else
						$duration = FALSE;

					// remember this task
					$context['gan2simile']['tasks'][ $attrs['ID'] ] = array(
						'title' => $attrs['NAME'],
						'start' => $attrs['START'],
						'duration' => $attrs['DURATION'],
						'complete' => $attrs['COMPLETE'],
						'isDuration' => $duration,
						'notes' => ''
						);
					$context['gan2simile']['current_id'] = $attrs['ID'];

					// move to children
					if(!$context['gan2simile']['depth']) {
						$context['gan2simile']['last_id'] = $attrs['ID'];
					}

				}
				$context['gan2simile']['depth']++;

			}
		}

		// close a tag
		function g2s_endElement($parser, $name) {
			global $context;

			// we check only tasks
			if(!strcmp($name, 'TASK')) {
				$context['gan2simile']['depth']--;
			}
		}

		// parse the GAN file
		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, "g2s_startElement", "g2s_endElement");
		if (!xml_parse($xml_parser, $content, TRUE)) {
			die(sprintf("XML error: %s at line %d",
						xml_error_string(xml_get_error_code($xml_parser)),
						xml_get_current_line_number($xml_parser)));
		}
		xml_parser_free($xml_parser);

		// the resulting text
		$text = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'."\n"
						.'<data>'."\n";

		// process each task
		foreach($context['gan2simile']['tasks'] as $task) {

			// transcode start date
			$start = strtotime($task['start']);

			// format start date as per SIMILE expectation
			$task['start'] = date('M j Y G:i:s', $start).' GMT';

			// which day in week?
			$info = getdate($start);

			// add two days for the first week-end
			if(($info['wday'] > 0) && ($info['wday'] < 6) && ($info['wday'] + $task['duration'] > 6))
				$task['duration'] += 2;

			// take week-ends into consideration
			$task['duration'] += intval($task['duration']/7)*2;

			// compute and format end date date as per SIMILE expectation
			$end = $start + ($task['duration'] * 24*60*60);
			$task['end'] = date('M j Y G:i:s', $end).' GMT';

			$earliestEnd = ' earliestEnd="'.$task['start'].'"';
			if($task['complete'] > 0) {

				// from percentage to number of days
				$task['complete'] = intval($task['complete'] * $task['duration'] / 100);

				// add two days for the first week-end
				if(($info['wday'] > 0) && ($info['wday'] < 6) && ($info['wday'] + $task['complete'] > 6))
					$task['complete'] += 2;

				// take week-ends into consideration
				$task['complete'] += intval($task['complete']/7)*2;

				// current completion
				$end = $start + ($task['complete'] * 24*60*60);
				$earliestEnd = ' earliestEnd="'.date('M j Y G:i:s', $end).' GMT"';

			}

			// has this one several children?
			$duration = '';
			if($task['isDuration'])
				$duration = ' isDuration="true"';

			// one event per task
			$text .= '	<event title="'.encode_field(str_replace(array("&nbsp;", '"'), ' ', $task['title'])).'" start="'.$task['start'].'"'.$earliestEnd.' end="'.$task['end'].'" '.$duration.'/>'."\n";
		}

		// no more events
		$text .= '</data>';

		// job done
		return $text;
	}

	/**
	 * process uploaded file
	 *
	 * This function processes files from the temporary directory, and put them at their definitive
	 * place.
	 *
	 *
	 * It returns FALSE if there is a disk error, or if some virus has been detected, or if
	 * the operation fails for some other reason (e.g., file size).
	 *
	 * @param array usually, $_FILES['upload']
	 * @param string target location for the file
	 * @param mixed reference to the target anchor, of a function to parse every file individually
	 * @return mixed actual file name or embedding string if everything went fine, FALSE if an error has occured
	 */
	public static function upload($input, $file_path, $target=NULL) {
		global $context, $_FILES, $_REQUEST;

		// do we have a file?
		if(!isset($input['name']) || !$input['name'] || ($input['name'] == 'none'))
			return FALSE;

		// access the temporary uploaded file
		$file_upload = $input['tmp_name'];

		// $_FILES transcoding to utf8 is not automatic
		$input['name'] = utf8::encode($input['name']);

		// enhance file name
		$file_name = $input['name'];
		$file_extension = '';
		$position = strrpos($input['name'], '.');
		if($position !== FALSE) {
			$file_name = substr($input['name'], 0, $position);
			$file_extension = strtolower(substr($input['name'], $position+1));
		}
		$input['name'] = $file_name;
		if($file_extension)
			$input['name'] .= '.'.$file_extension;

		// ensure we have a file name
		$file_name = utf8::to_ascii($input['name']);

		// uploads are not allowed
		if(!Surfer::may_upload())
			Logger::error(i18n::s('You are not allowed to perform this operation.'));

		// ensure type is allowed
		elseif(!Files::is_authorized($input['name']))
			Logger::error(i18n::s('This type of file is not allowed.'));

		// size exceeds php.ini settings -- UPLOAD_ERR_INI_SIZE
		elseif(isset($input['error']) && ($input['error'] == 1))
			Logger::error(i18n::s('The size of this file is over limit.'));

		// size exceeds form limit -- UPLOAD_ERR_FORM_SIZE
		elseif(isset($input['error']) && ($input['error'] == 2))
			Logger::error(i18n::s('The size of this file is over limit.'));

		// partial transfer -- UPLOAD_ERR_PARTIAL
		elseif(isset($input['error']) && ($input['error'] == 3))
			Logger::error(i18n::s('No file has been transmitted.'));

		// no file -- UPLOAD_ERR_NO_FILE
		elseif(isset($input['error']) && ($input['error'] == 4))
			Logger::error(i18n::s('No file has been transmitted.'));

		// zero bytes transmitted
		elseif(!$input['size'])
			Logger::error(i18n::s('No file has been transmitted.'));

		// check provided upload name
		elseif(!Safe::is_uploaded_file($file_upload))
			Logger::error(i18n::s('Possible file attack.'));

		// process uploaded data
		else {

			// create folders
			if($file_path)
				Safe::make_path($file_path);

			// sanity check
			if($file_path && ($file_path[ strlen($file_path)-1 ] != '/'))
				$file_path .= '/';

			// move the uploaded file
			if(!Safe::move_uploaded_file($file_upload, $context['path_to_root'].$file_path.$file_name))
				Logger::error(sprintf(i18n::s('Impossible to move the upload file to %s.'), $file_path.$file_name));

			// continue the processing
			else {

				// check viruses
				$result = Files::has_virus($context['path_to_root'].$file_path.'/'.$file_name);

				// no virus has been found in this file
				if($result == 'N')
					$context['text'] .= Skin::build_block(i18n::s('No virus has been found in this file.'), 'note');

				// this file has been infected!
				if($result == 'Y') {

					// delete this file immediately
					Safe::unlink($file_path.'/'.$file_name);

					Logger::error(i18n::s('This file has been infected by a virus and has been rejected!'));
					return FALSE;

				}

				// this will be filtered by umask anyway
				Safe::chmod($context['path_to_root'].$file_path.$file_name, $context['file_mask']);

				// invoke post-processing function
				if($target && is_callable($target)) {
					call_user_func($target, $file_name, $context['path_to_root'].$file_path);

				// we have to update an anchor page
				} elseif($target && is_string($target)) {

					// update an existing record for this anchor
					if($match =& Files::get_by_anchor_and_name($target, $file_name))
						$fields = $match;

					// create a new file record
					else {
						$fields = array();
						$fields['file_name'] = $file_name;
						$fields['file_size'] = filesize($context['path_to_root'].$file_path.$file_name);
						$fields['file_href'] = '';
						$fields['anchor'] = $target;
					}

					// create the record in the database, and remember this post in comment
					if($fields['id'] = Files::post($fields)) {
						return "\n[file=".$fields['id'].']';
					} else
						return FALSE;
				}

				// so far so good
				return $file_name;

			}

		}

		// some error has occured
		return FALSE;

	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('files');

// the maximum size for uploads
global $context;
$context['file_maximum_size'] = str_replace('M', ' M', Safe::get_cfg_var('upload_max_filesize'));
if(!$context['file_maximum_size'])
	$context['file_maximum_size'] = '2 M';

?>
