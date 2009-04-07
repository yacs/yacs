<?php
/**
 * the database abstraction layer for files
 *
 * @todo support .m4v for iPod videos video/x-m4v
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
	 * The function prevents the creation of new files when:
	 * - surfer cannot upload
	 * - the global parameter 'users_without_submission' has been set to 'Y'
	 * - provided item has been locked
	 * - item has some option 'no_files' that prevents new files
	 * - the anchor has some option 'no_files' that prevents new files
	 *
	 * Then the function allows for new files when:
	 * - surfer has been authenticated as a valid member
	 * - or parameter 'users_without_teasers' has not been set to 'Y'
	 *
	 * Then, ultimately, the default is not allow for the creation of new
	 * files.
	 *
	 * @param object an instance of the Anchor interface, if any
	 * @param array a set of item attributes, if any
	 * @param boolean TRUE to ask for option 'with_files'
	 * @return TRUE or FALSE
	 */
	function are_allowed($anchor=NULL, $item=NULL, $explicit=FALSE) {
		global $context;

		// files are prevented in item
		if(!$explicit && isset($item['options']) && is_string($item['options']) && preg_match('/\bno_files\b/i', $item['options']))
			return FALSE;

		// files are not explicitly activated in item
		if($explicit && isset($item['options']) && is_string($item['options']) && !preg_match('/\bwith_files\b/i', $item['options']))
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

		// editors can always upload files to public containers
		if(Surfer::is_empowered())
			return TRUE;
			
		// item has been locked
		if(isset($item['locked']) && is_string($item['locked']) && ($item['locked'] == 'Y'))
			return FALSE;

		// anchor has been locked --only used when there is no item provided
		if(!isset($item['id']) && is_object($anchor) && $anchor->has_option('locked'))
			return FALSE;

		// authenticated members are allowed to add files
		if(Surfer::is_member())
			return TRUE;

		// anonymous contributions are allowed for this container
		if(isset($item['content_options']) && preg_match('/\banonymous_edit\b/i', $item['content_options']))
			return TRUE;

		// anonymous contributions are allowed for this container
		if(isset($item['options']) && preg_match('/\banonymous_edit\b/i', $item['options']))
			return TRUE;

		// anonymous contributions are allowed for this anchor
		if(is_object($anchor) && $anchor->is_editable())
			return TRUE;

		// teasers are activated
		if(Surfer::is_teased())
			return TRUE;

		// the default is to not allow for new files
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
	function assign($id, $user=NULL) {
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
	function clear(&$item) {

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
	 * @return int the resulting count, or NULL on error
	 */
	function count_for_anchor($anchor, $optional=FALSE) {
		global $context;

		// sanity check
		if(!$anchor)
			return NULL;

		// request the database only in hi-fi mode
		if($optional && ($context['skins_with_details'] != 'Y'))
			return NULL;

		// profiling mode
		if($context['with_profile'] == 'Y')
			logger::profile('files::count_for_anchor');

		// limit the scope of the request
		$where = "files.active='Y' OR files.active='X'";
		if(Surfer::is_member())
			$where .= " OR files.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR files.active='N'";

		// select among available items
		$query = "SELECT COUNT(*) as count"
			." FROM ".SQL::table_name('files')." AS files"
			." WHERE files.anchor LIKE '".SQL::escape($anchor)."' AND (".$where.")";

		return SQL::query_scalar($query);
	}

	/**
	 * delete one file in the database and in the file system
	 *
	 * @param int the id of the file to delete
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	function delete($id) {
		global $context;

		// load the row
		$item =& Files::get($id);
		if(!$item['id']) {
			Logger::error(i18n::s('No item has the provided id.'));
			return FALSE;
		}

		// the file is in the external space
		if($item['active'] == 'X') {
			Safe::load('parameters/files.include.php');
			$file_path = $context['files_path'].'/';

		// or in the web space
		} else
			$file_path = $context['path_to_root'];

		// actual deletion of the file
		list($anchor_type, $anchor_id) = explode(':', $item['anchor'], 2);
		Safe::unlink($context['path_to_root'].'files/'.$context['virtual_path'].$anchor_type.'/'.$anchor_id.'/'.$item['file_name']);
		Safe::rmdir($context['path_to_root'].'files/'.$context['virtual_path'].$anchor_type.'/'.$anchor_id);
		Safe::rmdir($context['path_to_root'].'files/'.$context['virtual_path'].$anchor_type);

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
	function delete_for_anchor($anchor) {
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
	function duplicate_for_anchor($anchor_from, $anchor_to) {
		global $context;

		// look for records attached to this anchor
		$count = 0;
		$query = "SELECT * FROM ".SQL::table_name('files')." WHERE anchor LIKE '".SQL::escape($anchor_from)."'";
		if(($result =& SQL::query($query)) && SQL::count($result)) {

			// create target folders
			$file_path = 'files/'.$context['virtual_path'].str_replace(':', '/', $anchor_to);
			if(!Safe::make_path($file_path))
				Logger::error(sprintf(i18n::s('Impossible to create path %s.'), $file_path));
			$file_path = $context['path_to_root'].$file_path.'/';

			// the list of transcoded strings
			$transcoded = array();

			// process all matching records one at a time
			while($item =& SQL::fetch($result)) {

				// duplicate file
				if(!copy($context['path_to_root'].'files/'.$context['virtual_path'].str_replace(':', '/', $anchor_from).'/'.$item['file_name'],
					$file_path.$item['file_name'])) {
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
					$transcoded[] = array('/\[file='.preg_quote($old_id, '/').'/i', '[file='.$new_id);
					$transcoded[] = array('/\[flash='.preg_quote($old_id, '/').'/i', '[flash='.$new_id);
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
	function &get($id, $mutable=FALSE) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// ensure proper unicode encoding
		$id = (string)$id;
		$id = utf8::encode($id);

		// strip extra text from enhanced ids '3-page-title' -> '3'
		if($position = strpos($id, '-'))
			$id = substr($id, 0, $position);

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
		if(isset($output['id']))
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
	function &get_by_anchor_and_name($anchor, $name) {
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
	function get_icon_url($name) {

		// initialize tables only once
		static $files_icons;
		if(!is_array($files_icons)) {

			// path to file icons, relative to server root
			$files_icons_url = 'skins/images/files/';

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
				'dot' => $files_icons_url.'word_icon.gif',
				'eml' => $files_icons_url.'text_icon.gif',
				'eps' => $files_icons_url.'postscript_icon.gif',
				'exe' => $files_icons_url.'exe_icon.gif',
				'flv' => $files_icons_url.'flash_icon.gif', 		// flash video
				'gif' => $files_icons_url.'image_icon.gif',
				'gg' => $files_icons_url.'google_icon.gif',
				'gpx' => $files_icons_url.'gpx_icon.gif',
				'gtar' => $files_icons_url.'zip_icon.gif',
				'gz' => $files_icons_url.'zip_icon.gif',
				'htm' => $files_icons_url.'html_icon.gif',
				'html' => $files_icons_url.'html_icon.gif',
				'jpe' => $files_icons_url.'image_icon.gif',
				'jpeg' => $files_icons_url.'image_icon.gif',
				'jpg' => $files_icons_url.'image_icon.gif',
				'latex' => $files_icons_url.'tex_icon.gif',
				'm3u' => $files_icons_url.'midi_icon.gif',			// playlist
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
	 * @return a string describing the MIME type
	 */
	function get_mime_type($name) {
		global $context;

		// get the list of supported extensions
		$file_types =& Files::get_mime_types();

		// a name with no extension
		if(($position = strrpos($name, '.')) === FALSE)
			return 'application/download';

		// extract the extension
		if(!$extension = substr($name, $position+1))
			return 'application/download';

		// match the list
		if(isset($file_types[$extension]))
			return $file_types[$extension];

		// always return some type
		return 'application/download';
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
	function &get_mime_types() {

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
				'chm' => 'application/download',		// windows help file
				'css' => 'text/css',
				'divx' => 'video/vnd.divx ',
				'dll' => 'application/download',
				'doc' => 'application/msword',
				'dot' => 'application/msword',
				'eml' => 'text/html',		// message/rfc822
				'eps' => 'application/postscript',	// postscript
				'exe' => 'application/download',
				'flv' => 'video/x-flv', 	// flash video
				'gg' => 'app/gg',	// google desktop gadget
				'gif' => 'image/gif',
				'gpx' => 'application/download',	// GPS XML data
				'gtar' => 'application/x-gtar',
				'gz' => 'application/x-gzip',
				'htm' => 'text/html',
				'html' => 'text/html',
				'jpe' => 'image/jpeg',
				'jpeg' => 'image/jpeg',
				'jpg' => 'image/jpeg',
				'latex' => 'application/x-latex',
				'm3u' => 'audio/x-mpegurl', // playlist
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
				'mo' => 'application/download', 	// machine object (i.e., some translated file)
				'mov' => 'video/quicktime',
				'mp2' => 'video/mpeg',
				'mp3' => 'audio/mpeg',
				'mp4' => 'video/mpeg',
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
				'pcast' => 'application/download',	// Apple podcast
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
				'prc' => 'application/palmpilot',	// palm resource
				'ps' => 'application/postscript',	// postscript
				'psd' => 'image/photoshop', 		// photoshop
				'pub' => 'application/x-mspublisher',
				'qt' => 'video/quicktime',
				'ra' => 'audio/x-pn-realaudio', 	// real audio
				'ram' => 'audio/x-pn-realaudio',	// real audio
				'rar' => 'application/rar',
				'rmp' => 'application/download',	// open workbench
				'rtf' => 'application/msword',
				'shtml' => 'text/html',
				'snd' => 'audio/basic',
				'sql' => 'text/plain',
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
				'vob' => 'application/download',
				'vsd' => 'application/visio',
				'wav' => 'audio/x-wav',
				'wax' => 'audio/x-ms-wax',	// windows media player audio playlist
				'wma' => 'audio/x-ms-wma',	// windows media player audio
				'wmv' => 'video/x-ms-wmv',	// windows media player video
				'wri' => 'application/x-mswrite',
				'wvx' => 'video/x-ms-wvx',	// windows media player video playlist
				'xbm' => 'image/x-xbitmap',
				'xls' => 'application/vnd.ms-excel',
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
	function &get_newest() {
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
		$query .= "(files.active='Y' OR files.active='X'";
		if(Surfer::is_member())
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
	function get_next_url($item, $anchor, $order='date') {
		global $context;

		// sanity check
		if(!is_array($item))
			return $item;

		// limit the scope of the request
		$where = "files.active='Y' OR files.active='X'";
		if(Surfer::is_member())
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
		return Files::get_url($item['id'], 'view', $item['file_name']);
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
	function get_previous_url($item, $anchor, $order='date') {
		global $context;

		// sanity check
		if(!is_array($item))
			return $item;

		// limit the scope of the request
		$where = "files.active='Y' OR files.active='X'";
		if(Surfer::is_member())
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
		return Files::get_url($item['id'], 'view', $item['file_name']);
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
	function get_url($id, $action='view', $name=NULL) {
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
		if($action == 'clear') {
			$action = 'fetch';
			$name = 'clear';
		}

		// detach the file
		if($action == 'detach') {
			$action = 'fetch';
			$name = 'detach';
		}

		// check the target action
		if(!preg_match('/^(author|delete|edit|fetch|list|stream|thread|view)$/', $action))
			$action = 'view';

		// normalize the link
		return normalize_url(array('files', 'file'), $action, $id, $name);
	}

	/**
	 * set the hits counter - errors are not reported, if any
	 *
	 * @param the id of the file to update
	 */
	function increment_hits($id) {
		global $context;

		// sanity check
		if(!$id)
			return;

		// do the job
		$query = "UPDATE ".SQL::table_name('files')." SET hits=hits+1 WHERE (id = ".SQL::escape($id).")";
		SQL::query($query);

	}

	/**
	 * look for potential audio streams
	 *
	 * Meta-data types are not matched (e.g., m3u).
	 *
	 * This function returns TRUE for following file types:
	 * - aif	audio/aiff
	 * - aiff	audio/aiff
	 * - au
	 * - mp2	audio
	 * - mp3	audio
	 * - ra 	real audio
	 * - snd	audio/basic
	 * - wav
	 * - wma	audio/x-ms-wma
	 *
	 * @param string file name, including extension
	 * @return TRUE or FALSE
	 *
	 */
	function is_audio_stream($name) {
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
	function is_authorized($name) {
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
	 * should this file be streamed?
	 *
	 * @param string file name, including extension
	 * @return TRUE or FALSE
	 *
	 */
	function is_stream($name) {
		return Files::is_audio_stream($name) || Files::is_video_stream($name) || preg_match('/\.(mm|swf)$/i', $name);
	}

	/**
	 * look for potential video streams
	 *
	 * Meta-data types are not matched (e.g., m3u).
	 *
	 * This function returns TRUE for following file types:
	 * - asf
	 * - avi
	 * - divx
	 * - mov
	 * - mp4
	 * - mpe
	 * - mpeg
	 * - mpg
	 * - wmv	audio/x-ms-wmv
	 *
	 * @param string file name, including extension
	 * @return TRUE or FALSE
	 *
	 */
	function is_video_stream($name) {
		return preg_match('/\.(asf|avi|divx|mkv|mov|mp4|mpe|mpeg|mpg|vob|wmv)$/i', $name);
	}

	/**
	 * list newest files
	 *
	 * To build a simple box of the newest files, just use
	 * the following example:
	 * [php]
	 * // side bar with the list of most recent files
	 * include_once 'files/files.php';
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
	function &list_by_date($offset=0, $count=10, $variant='dates') {
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
		$query .= "(files.active='Y' OR files.active='X'";
		if(Surfer::is_member())
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
	 * include_once 'files/files.php';
	 * $items = Files::list_by_date_for_anchor('section:12', 0, 10);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 * [/php]
	 *
	 * @param int the id of the anchor
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
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
	function &list_by_date_for_anchor($anchor, $offset=0, $count=20, $variant='no_anchor') {
		global $context;

		// limit the scope of the request
		$where = "files.active='Y' OR files.active='X'";
		if(Surfer::is_member())
			$where .= " OR files.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR files.active='N'";

		// list items attached to this anchor, or to articles anchored to this anchor, or to articles anchored to sub-sections of this anchor
		if($anchor && ($variant == 'feeds')) {

				// files attached directly to this anchor
			$query = "(SELECT files.* FROM ".SQL::table_name('files')." AS files"
				." WHERE (files.anchor LIKE '".SQL::escape($anchor)."') AND (".$where."))"

				." UNION"

				// files attached to sections attached to this anchor
				." (SELECT files.* FROM ".SQL::table_name('sections')." AS sections "
				." LEFT JOIN ".SQL::table_name('files')." AS files "
				." ON ((files.anchor_type LIKE 'section') AND (files.anchor_id = sections.id))"
				." WHERE (sections.anchor LIKE '".SQL::escape($anchor)."')"
				." AND (".$where."))"

				." UNION"

				// files attached to articles attached to this anchor
				." (SELECT files.* FROM ".SQL::table_name('articles')." AS articles "
				." LEFT JOIN ".SQL::table_name('files')." AS files "
				." ON ((files.anchor_type LIKE 'article') AND (files.anchor_id = articles.id))"
				." WHERE (articles.anchor LIKE '".SQL::escape($anchor)."')"
				." AND (".$where.")"
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
				." AND (".$where.")"
				// restrict to files attached to published not expired pages
				." AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."')))"

				." ORDER BY edit_date DESC, title LIMIT ".$offset.','.$count;

		// list items attached directly to this anchor
		} else
			$query = "SELECT * FROM ".SQL::table_name('files')." AS files "
				." WHERE (files.anchor LIKE '".SQL::escape($anchor)."') AND (".$where.")"
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
	 * include_once 'files/files.php';
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
	function &list_by_date_for_author($author_id, $offset=0, $count=20, $variant='no_author') {
		global $context;

		// limit the scope of the request
		$where = "files.active='Y' OR files.active='X'";
		if(Surfer::is_member())
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
	 * include_once '../files/files.php';
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
	function &list_by_hits($offset=0, $count=10, $variant='hits') {
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
		$where .= "(files.active='Y' OR files.active='X'";
		if(Surfer::is_member())
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
	 * include_once 'files/files.php';
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
	function &list_by_hits_for_author($author_id, $offset=0, $count=10, $variant='hits') {
		global $context;

		// limit the scope of the request
		$where = "files.active='Y' OR files.active='X'";
		if(Surfer::is_member())
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
	function &list_by_oldest_date($offset=0, $count=10, $variant='full') {
		global $context;

		// limit the scope of the request
		$where = "files.active='Y' OR files.active='X'";
		if(Surfer::is_member())
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
	function &list_by_size($offset=0, $count=10, $variant='full') {
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
		$where .= "(files.active='Y' OR files.active='X'";
		if(Surfer::is_member())
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
	 * include_once '../files/files.php';
	 * $items = Files::list_by_title_for_anchor('article:12');
	 * $context['text'] .= Skin::build_list($items, 'decorated');
	 * [/php]
	 *
	 * @param int the id of the anchor
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
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
	function &list_by_title_for_anchor($anchor, $offset=0, $count=10, $variant='no_anchor') {
		global $context;

		// limit the scope of the request
		$where = "files.active='Y' OR files.active='X'";
		if(Surfer::is_member())
			$where .= " OR files.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR files.active='N'";

		// list items attached to this anchor, or to articles anchored to this anchor, or to articles anchored to sub-sections of this anchor
		if($anchor && ($variant == 'feeds')) {

			// files attached directly to this anchor
			$query = "(SELECT files.* FROM ".SQL::table_name('files')." AS files"
				." WHERE (files.anchor LIKE '".SQL::escape($anchor)."') AND (".$where."))"

				." UNION"

				// files attached to sections attached to this anchor
				." (SELECT files.* FROM ".SQL::table_name('sections')." AS sections "
				." LEFT JOIN ".SQL::table_name('files')." AS files "
				." ON ((files.anchor_type LIKE 'section') AND (files.anchor_id = sections.id))"
				." WHERE (sections.anchor LIKE '".SQL::escape($anchor)."')"
				." AND (".$where."))"

				." UNION"

				// files attached to articles attached to this anchor
				." (SELECT files.* FROM ".SQL::table_name('articles')." AS articles "
				." LEFT JOIN ".SQL::table_name('files')." AS files "
				." ON ((files.anchor_type LIKE 'article') AND (files.anchor_id = articles.id))"
				." WHERE (articles.anchor LIKE '".SQL::escape($anchor)."')"
				." AND (".$where.")"
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
				." AND (".$where.")"
				// restrict to files attached to published not expired pages
				." AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND ((articles.expiry_date is NULL)"
				."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".gmstrftime('%Y-%m-%d %H:%M:%S')."')))"

				." ORDER BY  files.title, files.file_name, files.edit_date DESC LIMIT ".$offset.','.$count;

		// list items attached directly to this anchor
		} else
			$query = "SELECT * FROM ".SQL::table_name('files')." AS files "
				." WHERE (files.anchor LIKE '".SQL::escape($anchor)."') AND (".$where.")"
				." ORDER BY files.title, files.file_name, files.edit_date DESC LIMIT ".$offset.','.$count;

		// the list of files
		$output =& Files::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list selected files
	 *
	 * Accept following variants:
	 * - 'compact' - to build short lists in boxes and sidebars (this is the default)
	 * - 'hits' - short lists with hits information
	 * - 'dates' - short lists with dates information
	 * - 'no_anchor' - to build detailed lists in an anchor page
	 * - 'no_author' - to build detailed lists in a user page
	 * - 'full' - include anchor information
	 * - 'simple' - more than compact, less than decorated
	 * - 'raw' - an array of $id => $attributes
	 * - 'search' - include anchor information
	 *
	 * @param resource result of database query
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @param string '?' or 'A', to support editors and to impersonate associates, where applicable
	 * @return an array of $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see skins/skin_skeleton.php
	 * @see files/fetch_all.php
	 */
	function &list_selected(&$result, $layout='compact') {
		global $context;

		// no result
		if(!$result) {
			$output = NULL;
			return $output;
		}

		// special layout
		if(is_object($layout)) {
			$output =& $layout->layout($result);
			return $output;
		}

		// one of regular layouts
		switch($layout) {

		case 'compact':
			include_once $context['path_to_root'].'files/layout_files_as_compact.php';
			$variant =& new Layout_files_as_compact();
			$output =& $variant->layout($result);
			return $output;

		case 'dates':
			include_once $context['path_to_root'].'files/layout_files_as_dates.php';
			$variant =& new Layout_files_as_dates();
			$output =& $variant->layout($result);
			return $output;

		case 'feeds':
			include_once $context['path_to_root'].'files/layout_files_as_feed.php';
			$variant =& new Layout_files_as_feed();
			$output =& $variant->layout($result);
			return $output;

		case 'hits':
			include_once $context['path_to_root'].'files/layout_files_as_hits.php';
			$variant =& new Layout_files_as_hits();
			$output =& $variant->layout($result);
			return $output;

		case 'raw':
			include_once $context['path_to_root'].'files/layout_files_as_raw.php';
			$variant =& new Layout_files_as_raw();
			$output =& $variant->layout($result);
			return $output;

		case 'simple':
			include_once $context['path_to_root'].'files/layout_files_as_simple.php';
			$variant =& new Layout_files_as_simple();
			$output =& $variant->layout($result);
			return $output;

		default:
			include_once $context['path_to_root'].'files/layout_files.php';
			$variant =& new Layout_files();
			$output =& $variant->layout($result, $layout);
			return $output;

		}

	}

	/**
	 * list less downloaded files
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1 - default is 0
	 * @param int the number of items to display - default is 10
	 * @param string the list variant, if any - default is 'hits'
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_unused($offset=0, $count=10, $variant='full') {
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
		$where .= "(files.active='Y' OR files.active='X'";
		if(Surfer::is_member())
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
	function post(&$fields) {
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
		$fields = Surfer::check_default_editor($fields);

		// reinforce date formats
		if(!isset($fields['create_date']) || ($fields['create_date'] <= NULL_DATE))
			$fields['create_date'] = $fields['edit_date'];

		// update the existing record
		if(isset($fields['id'])) {

			// id cannot be empty
			if(!isset($fields['id']) || !is_numeric($fields['id'])) {
				Logger::error(i18n::s('No item has the provided id.'));
				return FALSE;
			}

			$query = "UPDATE ".SQL::table_name('files')." SET ";

			// an actual upload has taken place --change modification date and reset detach data
			if(isset($fields['file_name']) && ($fields['file_name'] != 'none')) {
				$query .= "file_name='".SQL::escape($fields['file_name'])."',"
					."file_size='".SQL::escape($fields['file_size'])."',"
					."create_name='".SQL::escape($fields['edit_name'])."',"
					."create_id='".SQL::escape($fields['edit_id'])."',"
					."create_address='".SQL::escape($fields['edit_address'])."',"
					."create_date='".SQL::escape($fields['edit_date'])."',"
					."edit_name='".SQL::escape($fields['edit_name'])."',"
					."edit_id='".SQL::escape($fields['edit_id'])."',"
					."edit_address='".SQL::escape($fields['edit_address'])."',"
					."edit_action='file:update',"
					."edit_date='".SQL::escape($fields['edit_date'])."',"
					."assign_name='',"
					."assign_id='',"
					."assign_address='',"
					."assign_date='',";
			}

			// fields that are visible only to authenticated associates and editors
			if(Surfer::is_empowered() && Surfer::is_member())
				$query .= "active='".SQL::escape($fields['active'])."',"
					."active_set='".SQL::escape($fields['active_set'])."',"
					."icon_url='".SQL::escape(isset($fields['icon_url']) ? $fields['icon_url'] : '')."',"
					."thumbnail_url='".SQL::escape(isset($fields['thumbnail_url']) ? $fields['thumbnail_url'] : '')."',";

			// regular fields
			$query .= "title='".SQL::escape(isset($fields['title']) ? $fields['title'] : '')."',"
				."alternate_href='".SQL::escape(isset($fields['alternate_href']) ? $fields['alternate_href'] : '')."',"
				."behaviors='".SQL::escape(isset($fields['behaviors']) ? $fields['behaviors'] : '')."',"
				."file_href='".SQL::escape(isset($fields['file_href']) ? $fields['file_href'] : '')."',"
				."description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."',"
				."keywords='".SQL::escape(isset($fields['keywords']) ? $fields['keywords'] : '')."',"
				."source='".SQL::escape(isset($fields['source']) ? $fields['source'] : '')."'"
				." WHERE id = ".SQL::escape($fields['id']);

			// actual insert
			if(SQL::query($query) === FALSE)
				return FALSE;

		// insert a new record
		} elseif(isset($fields['file_name']) && $fields['file_name'] && isset($fields['file_size']) && $fields['file_size']) {

			$query = "INSERT INTO ".SQL::table_name('files')." SET ";
			$query .= "anchor='".SQL::escape($fields['anchor'])."',"
				."anchor_id=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', -1),"
				."anchor_type=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', 1),"
				."file_name='".SQL::escape($fields['file_name'])."',"
				."file_size='".SQL::escape($fields['file_size'])."',"
				."title='".SQL::escape(isset($fields['title']) ? $fields['title'] : '')."',"
				."alternate_href='".SQL::escape(isset($fields['alternate_href']) ? $fields['alternate_href'] : '')."',"
				."behaviors='".SQL::escape(isset($fields['behaviors']) ? $fields['behaviors'] : '')."',"
				."file_href='".SQL::escape(isset($fields['file_href']) ? $fields['file_href'] : '')."',"
				."description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."',"
				."keywords='".SQL::escape(isset($fields['keywords']) ? $fields['keywords'] : '')."',"
				."source='".SQL::escape(isset($fields['source']) ? $fields['source'] : '')."',";

			// fields that are visible only to authenticated associates and editors
			if(Surfer::is_empowered() && Surfer::is_member())
				$query .= "active='".SQL::escape($fields['active'])."',"
					."active_set='".SQL::escape($fields['active_set'])."',"
					."icon_url='".SQL::escape(isset($fields['icon_url']) ? $fields['icon_url'] : '')."',"
					."thumbnail_url='".SQL::escape(isset($fields['thumbnail_url']) ? $fields['thumbnail_url'] : '')."',";

			// always stamp the first upload
			$query .= "create_name='".SQL::escape(isset($fields['create_name']) ? $fields['create_name'] : $fields['edit_name'])."',"
				."create_id='".SQL::escape(isset($fields['create_id']) ? $fields['create_id'] : $fields['edit_id'])."',"
				."create_address='".SQL::escape(isset($fields['create_address']) ? $fields['create_address'] : $fields['edit_address'])."',"
				."create_date='".SQL::escape($fields['create_date'])."',"
				."edit_name='".SQL::escape($fields['edit_name'])."',"
				."edit_id='".SQL::escape($fields['edit_id'])."',"
				."edit_address='".SQL::escape($fields['edit_address'])."',"
				."edit_action='file:create',"
				."edit_date='".SQL::escape($fields['edit_date'])."',"
				."hits=0";

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
	 * @param the search string
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &search($pattern, $offset=0, $count=50, $variant='search') {
		global $context;

		// sanity check
		if(!$pattern = trim($pattern)) {
			$output = NULL;
			return $output;
		}
		
		// limit the scope of the request
		$where = "files.active='Y' OR files.active='X'";
		if(Surfer::is_member())
			$where .= " OR files.active='R'";
		if(Surfer::is_associate())
			$where .= " OR files.active='N'";
		$where = '('.$where.')';

		// match
		$match = '';
		$words = preg_split('/\s/', $pattern);
		while($word = each($words)) {
			if($match)
				$match .= ' AND ';
			$match .=  "MATCH(title, source, description, keywords) AGAINST('".SQL::escape($word['value'])."')";
		}

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
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['active']		= "ENUM('Y','R','N', 'X') DEFAULT 'Y' NOT NULL";
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
	function &stat() {
		global $context;

		// limit the scope of the request
		$where = "(files.active='Y' OR files.active='X'";
		if(Surfer::is_member())
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
	function &stat_for_anchor($anchor) {
		global $context;

		// sanity check
		if(!$anchor)
			return NULL;

		// profiling mode
		if($context['with_profile'] == 'Y')
			logger::profile('files::stat_for_anchor');

		// limit the scope of the request
		$where = "files.active='Y' OR files.active='X'";
		if(Surfer::is_member())
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

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('files');

?>