<?php
/**
 * the database abstraction layer for collections
 *
 * Collections are a web interface to files in a file system accessible from the web server.
 *
 * Each collection is mapped to a different directory.
 *
 * @link http://www.w3schools.com/media/media_mimeref.asp MIME Reference
 * @link http://specs.openoffice.org/appwide/fileIO/FileFormatNames.sxw File Format Names for New OASIS Open Office XML Format
 * @link http://support.microsoft.com/kb/288102 MIME Type Settings for Windows Media Services
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Collections {

	/**
	 * get information for one item
	 *
	 * This function retrieve implementation information, based on virtual path provided.
	 *
	 * @param string the provided path for the item, including the collection prefix
	 * @return a set of attributes for this item, including: 'collection', 'relative_path', etc.
	 */
	function &get($id) {
		global $context;

		// avoid code injection
		$id = strip_tags($id);

		// suppress dangerous strings, namely, '../'
		$id = preg_replace(FORBIDDEN_IN_PATHS, '', $id);

		// sanity check
		if(!trim($id)) {
			$output = NULL;
			return $output;
		}

		// identify path components
		$item = array();
		$item['id'] = $id;

		// parse the virtual path
		$directories = explode('/', trim($id));

		// find the collection
		$collection = array_shift($directories);
		$item['collection'] = $collection;

		// is this a known collection?
		Safe::load('parameters/collections.include.php');
		if(!isset($context['collections'][$collection]))
			return $item;

		// load collection information
		list($title, $path, $url, $introduction, $description, $prefix, $suffix, $visibility) = $context['collections'][$collection];
		$item['collection_title'] = $title;
		$item['collection_path'] = $path;
		$item['collection_url'] = $url;
		$item['collection_introduction'] = $introduction;
		$item['collection_description'] = $description;
		$item['collection_prefix'] = $prefix;
		$item['collection_suffix'] = $suffix;
		$item['collection_visibility'] = $visibility;

		// ensure we have a title for this collection
		if(!trim($item['collection_title']))
			$item['collection_title'] = str_replace(array('.', '_', '%20'), ' ', $item['collection']);

		// signal restricted and private collections
		if($item['collection_visibility'] == 'N')
			$item['collection_title'] = PRIVATE_FLAG.$item['collection_title'];
		elseif($item['collection_visibility'] == 'R')
			$item['collection_title'] = RESTRICTED_FLAG.$item['collection_title'];

		// relative path
		$relative_path = implode('/', $directories);
		$item['relative_path'] = $relative_path;

		// encode the relative url
		$relative_url = '';
		foreach($directories as $directory) {
			if($relative_url)
				$relative_url .= '/';
			$relative_url .= rawurlencode($directory);
		}
		$item['relative_url'] = $relative_url;

		// actual path and url to this item
		$item['actual_path'] = $path;
		if($relative_path)
			$item['actual_path'] .= '/'.$relative_path;
		$item['actual_url'] = $url;
		if($relative_path)
			$item['actual_url'] .= '/'.$relative_url;

		// the target node is a leaf of the branch
		$item['node_name'] = array_pop($directories);

		// attempt to find the extension
		$item['node_extension'] = strtolower(@array_pop(@explode('.', @basename($item['node_name']))));

		// a printable label for this node
		$item['node_label'] = $item['node_name'];
		if(!$item['node_label'])
			$item['node_label'] = $item['collection_title'];

		// containers, if any
		$item['containers'] = array();

		// we are at the collection index page
		if(!$relative_path) {

			// strip prefix and suffix
			$item['collection_prefix'] = '';
			$item['collection_suffix'] = '';

		// somewhere deep in the collection
		} else {

			// strip the description
			$item['collection_description'] = '';

			// collection index page
			if($context['with_friendly_urls'] == 'Y')
				$link = $context['url_to_root'].'collections/browse.php/'.rawurlencode($item['collection']);
			else
				$link = $context['url_to_root'].'collections/browse.php?path='.urlencode($item['collection']);
			$item['containers'] = array_merge($item['containers'], array($link => $item['collection_title']));

			// one shortcut per container
			$path = $collection;
			$url = rawurlencode($collection);
			foreach($directories as $directory) {

				$path .= '/'.$directory;
				$url .= '/'.rawurlencode($directory);
				if($context['with_friendly_urls'] == 'Y')
					$link = $context['url_to_root'].'collections/browse.php/'.$url;
				else
					$link = $context['url_to_root'].'collections/browse.php?path='.urlencode($path);
				$item['containers'] = array_merge($item['containers'], array($link => str_replace(array('.', '_', '%20'), ' ', $directory)));
			}
		}

		// return what we found
		return $item;
	}

	/**
	 * get the img tag for this item
	 *
	 * @param string the item name
	 * @return an anchor to the viewing script
	 */
	function get_icon_img($name) {
		global $context;

		// initialize tables only once
		static $collections_icons;
		if(!is_array($collections_icons)) {

			// path to item icons, relative to server root
			$collections_icons_url = $context['url_to_root'].'skins/images/files_inline/';

			// icons related to file types
			$collections_icons = array(
				'ai' => '<img src="'.$collections_icons_url.'postscript.png" width="16" height="16" alt="" />',
				'ace' => '<img src="'.$collections_icons_url.'zip.gif" width="15" height="16" alt="" />',
				'aif' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt="" />',
				'aiff' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt="" />',
				'arj' => '<img src="'.$collections_icons_url.'zip.gif" width="15" height="16" alt="" />',
				'asf' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt="" />',
				'asx' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt="" />',
				'au' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt="" />',
				'avi' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt="" />',
				'awk' => '<img src="'.$collections_icons_url.'txt.gif" width="13" height="15" alt="" />',
				'bmp' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt="" />',
				'chm' => '<img src="'.$collections_icons_url.'help.gif" width="16" height="16" alt="" />',
				'css' => '<img src="'.$collections_icons_url.'html.gif" width="15" height="15" alt="" />',
				'divx' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt="" />',
				'dll' => '<img src="'.$collections_icons_url.'system.gif" width="13" height="16" alt="" />',
				'doc' => '<img src="'.$collections_icons_url.'word.gif" width="16" height="16" alt="" />',
				'docm' => '<img src="'.$collections_icons_url.'word.gif" width="16" height="16" alt="" />',
				'docx' => '<img src="'.$collections_icons_url.'word.gif" width="16" height="16" alt="" />',
				'dot' => '<img src="'.$collections_icons_url.'word.gif" width="16" height="16" alt="" />',
				'eml' => '<img src="'.$collections_icons_url.'txt.gif" width="13" height="15" alt="" />',
				'eps' => '<img src="'.$collections_icons_url.'postscript.png" width="16" height="16" alt="" />',
				'exe' => '<img src="'.$collections_icons_url.'exe.gif" width="16" height="16" alt="" />',
				'flv' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt="" />',
				'gif' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt="" />',
				'gtar' => '<img src="'.$collections_icons_url.'zip.gif" width="15" height="16" alt="" />',
				'gz' => '<img src="'.$collections_icons_url.'zip.gif" width="15" height="16" alt="" />',
				'htm' => '<img src="'.$collections_icons_url.'html.gif" width="15" height="15" alt="" />',
				'html' => '<img src="'.$collections_icons_url.'html.gif" width="15" height="15" alt="" />',
				'jpe' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt="" />',
				'jpeg' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt="" />',
				'jpg' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt="" />',
				'latex' => '<img src="'.$collections_icons_url.'tex.png" width="16" height="16" alt="" />',
				'm3u' => '<img src="'.$collections_icons_url.'midi.gif" width="16" height="16" alt="" />',
				'mdb' => '<img src="'.$collections_icons_url.'access.gif" width="16" height="16" alt="" />',
				'mid' => '<img src="'.$collections_icons_url.'midi.gif" width="16" height="16" alt="" />',
				'midi' => '<img src="'.$collections_icons_url.'midi.gif" width="16" height="16" alt="" />',
				'mka' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt="" />',
				'mkv' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt="" />',
				'mmap' => '<img src="'.$collections_icons_url.'mmap.gif" width="16" height="16" alt="" />',
				'mmas' => '<img src="'.$collections_icons_url.'mmap.gif" width="16" height="16" alt="" />',
				'mmat' => '<img src="'.$collections_icons_url.'mmap.gif" width="16" height="16" alt="" />',
				'mmmp' => '<img src="'.$collections_icons_url.'mmap.gif" width="16" height="16" alt="" />',
				'mmp' => '<img src="'.$collections_icons_url.'mmap.gif" width="16" height="16" alt="" />',
				'mmpt' => '<img src="'.$collections_icons_url.'mmap.gif" width="16" height="16" alt="" />',
				'mov' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt="" />',
				'mp2' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt="" />',
				'mp3' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt="" />',
				'mp4' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt="" />',
				'mpe' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt="" />',
				'mpeg' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt="" />',
				'mpg' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt="" />',
				'mpga' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt="" />',
				'mpp' => '<img src="'.$collections_icons_url.'project.gif" width="16" height="16" alt="" />',
				'odb' => '<img src="'.$collections_icons_url.'ooo_database_icon.png" width="16" height="16" alt="" />',
				'odg' => '<img src="'.$collections_icons_url.'ooo_draw_icon.png" width="16" height="16" alt="" />',
				'odp' => '<img src="'.$collections_icons_url.'ooo_impress_icon.png" width="16" height="16" alt="" />',
				'ods' => '<img src="'.$collections_icons_url.'ooo_calc_icon.png" width="16" height="16" alt="" />',
				'odt' => '<img src="'.$collections_icons_url.'ooo_writer_icon.png" width="16" height="16" alt="" />',
				'odm' => '<img src="'.$collections_icons_url.'ooo_global_icon.png" width="16" height="16" alt="" />',
				'ogg' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt="" />',
				'otg' => '<img src="'.$collections_icons_url.'ooo_draw_icon.png" width="16" height="16" alt="" />',
				'oth' => '<img src="'.$collections_icons_url.'ooo_html.png" width="16" height="16" alt="" />',
				'otp' => '<img src="'.$collections_icons_url.'ooo_impress_icon.png" width="16" height="16" alt="" />',
				'ots' => '<img src="'.$collections_icons_url.'ooo_calc_icon.png" width="16" height="16" alt="" />',
				'ott' => '<img src="'.$collections_icons_url.'ooo_writer_icon.png" width="16" height="16" alt="" />',
				'pdf' => '<img src="'.$collections_icons_url.'pdf.gif" width="16" height="16" alt="" />',
				'pgp' => '<img src="'.$collections_icons_url.'txt.gif" width="13" height="15" alt="" />',
				'pic' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt="" />',
				'pict' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt="" />',
				'pls' => '<img src="'.$collections_icons_url.'midi.gif" width="16" height="16" alt="" />',
				'png' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt="" />',
				'ppd' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt="" />',
				'pps' => '<img src="'.$collections_icons_url.'ppt.gif" width="16" height="16" alt="" />',
				'ppt' => '<img src="'.$collections_icons_url.'ppt.gif" width="16" height="16" alt="" />',
				'pptm' => '<img src="'.$collections_icons_url.'ppt.gif" width="16" height="16" alt="" />',
				'pptx' => '<img src="'.$collections_icons_url.'ppt.gif" width="16" height="16" alt="" />',
				'ps' => '<img src="'.$collections_icons_url.'postscript.png" width="16" height="16" alt="" />',
				'psd' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt="" />',
				'pub' => '<img src="'.$collections_icons_url.'publisher.gif" width="16" height="16" alt="" />',
				'qt' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt="" />',
				'ra' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt="" />',
				'ram' => '<img src="'.$collections_icons_url.'midi.gif" width="16" height="16" alt="" />',
				'rar' => '<img src="'.$collections_icons_url.'zip.gif" width="15" height="16" alt="" />',
				'rpm' => '<img src="'.$collections_icons_url.'zip.gif" width="15" height="16" alt="" />',
				'snd' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt="" />',
				'shtml' => '<img src="'.$collections_icons_url.'html.gif" width="15" height="15" alt="" />',
				'sql' => '<img src="'.$collections_icons_url.'txt.gif" width="13" height="15" alt="" />',
				'stc' => '<img src="'.$collections_icons_url.'ooo_calc_icon.png" width="16" height="16" alt="" />',
				'std' => '<img src="'.$collections_icons_url.'ooo_draw_icon.png" width="16" height="16" alt="" />',
				'sti' => '<img src="'.$collections_icons_url.'ooo_impress_icon.png" width="16" height="16" alt="" />',
				'stw' => '<img src="'.$collections_icons_url.'ooo_writer_icon.png" width="16" height="16" alt="" />',
				'sxc' => '<img src="'.$collections_icons_url.'ooo_calc_icon.png" width="16" height="16" alt="" />',
				'sxd' => '<img src="'.$collections_icons_url.'ooo_draw_icon.png" width="16" height="16" alt="" />',
				'sxg' => '<img src="'.$collections_icons_url.'ooo_writer_icon.png" width="16" height="16" alt="" />',
				'sxi' => '<img src="'.$collections_icons_url.'ooo_impress_icon.png" width="16" height="16" alt="" />',
				'sxw' => '<img src="'.$collections_icons_url.'ooo_writer_icon.png" width="16" height="16" alt="" />',
				'swf' => '<img src="'.$collections_icons_url.'flash.gif" width="15" height="15" alt="" />',
				'tar' => '<img src="'.$collections_icons_url.'zip.gif" width="15" height="16" alt="" />',
				'tex' => '<img src="'.$collections_icons_url.'tex.png" width="16" height="16" alt="" />',
				'texi' => '<img src="'.$collections_icons_url.'tex.png" width="16" height="16" alt="" />',
				'texinfo' => '<img src="'.$collections_icons_url.'tex.png" width="16" height="16" alt="" />',
				'tif' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt="" />',
				'tiff' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt="" />',
				'tgz' => '<img src="'.$collections_icons_url.'zip.gif" width="15" height="16" alt="" />',
				'txt' => '<img src="'.$collections_icons_url.'txt.gif" width="13" height="15" alt="" />',
				'vob' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt="" />',
				'vsd' => '<img src="'.$collections_icons_url.'visio.gif" width="16" height="16" alt="" />',
				'wav' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt="" />',
				'wax' => '<img src="'.$collections_icons_url.'midi.gif" width="16" height="16" alt="" />',
				'wma' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt="" />',
				'wmv' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt="" />',
				'wri' => '<img src="'.$collections_icons_url.'write.gif" width="16" height="16" alt="" />',
				'wvx' => '<img src="'.$collections_icons_url.'midi.gif" width="16" height="16" alt="" />',
				'xbm' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt="" />',
				'xls' => '<img src="'.$collections_icons_url.'excel.gif" width="16" height="16" alt="" />',
				'xlsm' => '<img src="'.$collections_icons_url.'excel.gif" width="16" height="16" alt="" />',
				'xlsx' => '<img src="'.$collections_icons_url.'excel.gif" width="16" height="16" alt="" />',
				'xml' => '<img src="'.$collections_icons_url.'html.gif" width="15" height="15" alt="" />',
				'zip' => '<img src="'.$collections_icons_url.'zip.gif" width="15" height="16" alt="" />',
				'default' => '<img src="'.$collections_icons_url.'default.gif" width="15" height="16" alt="" />' );
		}

		$extension = @array_pop(@explode('.', @basename(strtolower($name))));
		if($extension && isset($collections_icons[$extension]))
			return $collections_icons[$extension];
		return $collections_icons['default'];
	}

	/**
	 * get the url to view a file
	 *
	 * By default, a relative URL will be provided (e.g. '[code]collections/view.php?id=512[/code]'),
	 * which may be not processed correctly by search engines.
	 * If the parameter '[code]with_friendly_urls[/code]' has been set to '[code]Y[/code]' in the configuration panel,
	 * this function will return an URL parsable by search engines (e.g. '[code]collections/view.php/512[/code]').
	 *
	 * @param int the id of the file to view
	 * @param string the expected action ('view', 'edit', 'delete', ...)
	 * @return an anchor to the viewing script
	 *
	 * @see control/configure.php
	 */
	function get_url($id, $action='view') {
		global $context;

		// sanity check
		if(!$id)
			return NULL;

		// close the URL with a / to avoid NS to save as php, but only if it safe to do so
		if(($action == 'fetch') && ($context['with_friendly_urls'] == 'Y'))
			return 'collections/'.$action.'.php/?id='.urlencode($id);

		// be cool with search engines
		if($context['with_friendly_urls'] == 'Y')
			return 'collections/'.$action.'.php/'.rawurlencode($id);
		else
			return 'collections/'.$action.'.php?id='.urlencode($id);
	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('collections');

?>