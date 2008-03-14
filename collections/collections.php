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
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
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
		$id = preg_replace(FORBIDDEN_STRINGS_IN_PATHS, '', $id);

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
			if(isset($context['with_friendly_urls']) && ($context['with_friendly_urls'] == 'Y'))
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
				if(isset($context['with_friendly_urls']) && ($context['with_friendly_urls'] == 'Y'))
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
				'ai' => '<img src="'.$collections_icons_url.'postscript.png" width="16" height="16" alt=""'.EOT,
				'ace' => '<img src="'.$collections_icons_url.'zip.gif" width="15" height="16" alt=""'.EOT,
				'aif' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt=""'.EOT,
				'aiff' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt=""'.EOT,
				'arj' => '<img src="'.$collections_icons_url.'zip.gif" width="15" height="16" alt=""'.EOT,
				'asf' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt=""'.EOT,
				'asx' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt=""'.EOT,
				'au' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt=""'.EOT,
				'avi' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt=""'.EOT,
				'awk' => '<img src="'.$collections_icons_url.'txt.gif" width="13" height="15" alt=""'.EOT,
				'bmp' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt=""'.EOT,
				'chm' => '<img src="'.$collections_icons_url.'help.gif" width="16" height="16" alt=""'.EOT,
				'css' => '<img src="'.$collections_icons_url.'html.gif" width="15" height="15" alt=""'.EOT,
				'divx' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt=""'.EOT,
				'dll' => '<img src="'.$collections_icons_url.'system.gif" width="13" height="16" alt=""'.EOT,
				'doc' => '<img src="'.$collections_icons_url.'word.gif" width="16" height="16" alt=""'.EOT,
				'dot' => '<img src="'.$collections_icons_url.'word.gif" width="16" height="16" alt=""'.EOT,
				'eml' => '<img src="'.$collections_icons_url.'txt.gif" width="13" height="15" alt=""'.EOT,
				'eps' => '<img src="'.$collections_icons_url.'postscript.png" width="16" height="16" alt=""'.EOT,
				'exe' => '<img src="'.$collections_icons_url.'exe.gif" width="16" height="16" alt=""'.EOT,
				'flv' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt=""'.EOT,
				'gif' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt=""'.EOT,
				'gtar' => '<img src="'.$collections_icons_url.'zip.gif" width="15" height="16" alt=""'.EOT,
				'gz' => '<img src="'.$collections_icons_url.'zip.gif" width="15" height="16" alt=""'.EOT,
				'htm' => '<img src="'.$collections_icons_url.'html.gif" width="15" height="15" alt=""'.EOT,
				'html' => '<img src="'.$collections_icons_url.'html.gif" width="15" height="15" alt=""'.EOT,
				'jpe' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt=""'.EOT,
				'jpeg' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt=""'.EOT,
				'jpg' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt=""'.EOT,
				'latex' => '<img src="'.$collections_icons_url.'tex.png" width="16" height="16" alt=""'.EOT,
				'm3u' => '<img src="'.$collections_icons_url.'midi.gif" width="16" height="16" alt=""'.EOT,
				'mdb' => '<img src="'.$collections_icons_url.'access.gif" width="16" height="16" alt=""'.EOT,
				'mid' => '<img src="'.$collections_icons_url.'midi.gif" width="16" height="16" alt=""'.EOT,
				'midi' => '<img src="'.$collections_icons_url.'midi.gif" width="16" height="16" alt=""'.EOT,
				'mka' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt=""'.EOT,
				'mkv' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt=""'.EOT,
				'mmap' => '<img src="'.$collections_icons_url.'mmap.gif" width="16" height="16" alt=""'.EOT,
				'mmas' => '<img src="'.$collections_icons_url.'mmap.gif" width="16" height="16" alt=""'.EOT,
				'mmat' => '<img src="'.$collections_icons_url.'mmap.gif" width="16" height="16" alt=""'.EOT,
				'mmmp' => '<img src="'.$collections_icons_url.'mmap.gif" width="16" height="16" alt=""'.EOT,
				'mmp' => '<img src="'.$collections_icons_url.'mmap.gif" width="16" height="16" alt=""'.EOT,
				'mmpt' => '<img src="'.$collections_icons_url.'mmap.gif" width="16" height="16" alt=""'.EOT,
				'mov' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt=""'.EOT,
				'mp2' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt=""'.EOT,
				'mp3' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt=""'.EOT,
				'mp4' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt=""'.EOT,
				'mpe' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt=""'.EOT,
				'mpeg' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt=""'.EOT,
				'mpg' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt=""'.EOT,
				'mpga' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt=""'.EOT,
				'mpp' => '<img src="'.$collections_icons_url.'project.gif" width="16" height="16" alt=""'.EOT,
				'odb' => '<img src="'.$collections_icons_url.'ooo_database_icon.png" width="16" height="16" alt=""'.EOT,
				'odg' => '<img src="'.$collections_icons_url.'ooo_draw_icon.png" width="16" height="16" alt=""'.EOT,
				'odp' => '<img src="'.$collections_icons_url.'ooo_impress_icon.png" width="16" height="16" alt=""'.EOT,
				'ods' => '<img src="'.$collections_icons_url.'ooo_calc_icon.png" width="16" height="16" alt=""'.EOT,
				'odt' => '<img src="'.$collections_icons_url.'ooo_writer_icon.png" width="16" height="16" alt=""'.EOT,
				'odm' => '<img src="'.$collections_icons_url.'ooo_global_icon.png" width="16" height="16" alt=""'.EOT,
				'ogg' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt=""'.EOT,
				'otg' => '<img src="'.$collections_icons_url.'ooo_draw_icon.png" width="16" height="16" alt=""'.EOT,
				'oth' => '<img src="'.$collections_icons_url.'ooo_html.png" width="16" height="16" alt=""'.EOT,
				'otp' => '<img src="'.$collections_icons_url.'ooo_impress_icon.png" width="16" height="16" alt=""'.EOT,
				'ots' => '<img src="'.$collections_icons_url.'ooo_calc_icon.png" width="16" height="16" alt=""'.EOT,
				'ott' => '<img src="'.$collections_icons_url.'ooo_writer_icon.png" width="16" height="16" alt=""'.EOT,
				'pdf' => '<img src="'.$collections_icons_url.'pdf.gif" width="16" height="16" alt=""'.EOT,
				'pgp' => '<img src="'.$collections_icons_url.'txt.gif" width="13" height="15" alt=""'.EOT,
				'pic' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt=""'.EOT,
				'pict' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt=""'.EOT,
				'pls' => '<img src="'.$collections_icons_url.'midi.gif" width="16" height="16" alt=""'.EOT,
				'png' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt=""'.EOT,
				'ppd' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt=""'.EOT,
				'pps' => '<img src="'.$collections_icons_url.'ppt.gif" width="16" height="16" alt=""'.EOT,
				'ppt' => '<img src="'.$collections_icons_url.'ppt.gif" width="16" height="16" alt=""'.EOT,
				'ps' => '<img src="'.$collections_icons_url.'postscript.png" width="16" height="16" alt=""'.EOT,
				'psd' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt=""'.EOT,
				'pub' => '<img src="'.$collections_icons_url.'publisher.gif" width="16" height="16" alt=""'.EOT,
				'qt' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt=""'.EOT,
				'ra' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt=""'.EOT,
				'ram' => '<img src="'.$collections_icons_url.'midi.gif" width="16" height="16" alt=""'.EOT,
				'rar' => '<img src="'.$collections_icons_url.'zip.gif" width="15" height="16" alt=""'.EOT,
				'rpm' => '<img src="'.$collections_icons_url.'zip.gif" width="15" height="16" alt=""'.EOT,
				'snd' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt=""'.EOT,
				'shtml' => '<img src="'.$collections_icons_url.'html.gif" width="15" height="15" alt=""'.EOT,
				'sql' => '<img src="'.$collections_icons_url.'txt.gif" width="13" height="15" alt=""'.EOT,
				'stc' => '<img src="'.$collections_icons_url.'ooo_calc_icon.png" width="16" height="16" alt=""'.EOT,
				'std' => '<img src="'.$collections_icons_url.'ooo_draw_icon.png" width="16" height="16" alt=""'.EOT,
				'sti' => '<img src="'.$collections_icons_url.'ooo_impress_icon.png" width="16" height="16" alt=""'.EOT,
				'stw' => '<img src="'.$collections_icons_url.'ooo_writer_icon.png" width="16" height="16" alt=""'.EOT,
				'sxc' => '<img src="'.$collections_icons_url.'ooo_calc_icon.png" width="16" height="16" alt=""'.EOT,
				'sxd' => '<img src="'.$collections_icons_url.'ooo_draw_icon.png" width="16" height="16" alt=""'.EOT,
				'sxg' => '<img src="'.$collections_icons_url.'ooo_writer_icon.png" width="16" height="16" alt=""'.EOT,
				'sxi' => '<img src="'.$collections_icons_url.'ooo_impress_icon.png" width="16" height="16" alt=""'.EOT,
				'sxw' => '<img src="'.$collections_icons_url.'ooo_writer_icon.png" width="16" height="16" alt=""'.EOT,
				'swf' => '<img src="'.$collections_icons_url.'flash.gif" width="15" height="15" alt=""'.EOT,
				'tar' => '<img src="'.$collections_icons_url.'zip.gif" width="15" height="16" alt=""'.EOT,
				'tex' => '<img src="'.$collections_icons_url.'tex.png" width="16" height="16" alt=""'.EOT,
				'texi' => '<img src="'.$collections_icons_url.'tex.png" width="16" height="16" alt=""'.EOT,
				'texinfo' => '<img src="'.$collections_icons_url.'tex.png" width="16" height="16" alt=""'.EOT,
				'tif' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt=""'.EOT,
				'tiff' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt=""'.EOT,
				'tgz' => '<img src="'.$collections_icons_url.'zip.gif" width="15" height="16" alt=""'.EOT,
				'txt' => '<img src="'.$collections_icons_url.'txt.gif" width="13" height="15" alt=""'.EOT,
				'vob' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt=""'.EOT,
				'vsd' => '<img src="'.$collections_icons_url.'visio.gif" width="16" height="16" alt=""'.EOT,
				'wav' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt=""'.EOT,
				'wax' => '<img src="'.$collections_icons_url.'midi.gif" width="16" height="16" alt=""'.EOT,
				'wma' => '<img src="'.$collections_icons_url.'sound.gif" width="15" height="15" alt=""'.EOT,
				'wmv' => '<img src="'.$collections_icons_url.'video.gif" width="16" height="16" alt=""'.EOT,
				'wri' => '<img src="'.$collections_icons_url.'write.gif" width="16" height="16" alt=""'.EOT,
				'wvx' => '<img src="'.$collections_icons_url.'midi.gif" width="16" height="16" alt=""'.EOT,
				'xbm' => '<img src="'.$collections_icons_url.'image.gif" width="16" height="16" alt=""'.EOT,
				'xls' => '<img src="'.$collections_icons_url.'excel.gif" width="16" height="16" alt=""'.EOT,
				'xml' => '<img src="'.$collections_icons_url.'html.gif" width="15" height="15" alt=""'.EOT,
				'zip' => '<img src="'.$collections_icons_url.'zip.gif" width="15" height="16" alt=""'.EOT,
				'default' => '<img src="'.$collections_icons_url.'default.gif" width="15" height="16" alt=""'.EOT );
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
		if(($action == 'fetch') && (isset($context['with_friendly_urls']) && ($context['with_friendly_urls'] == 'Y')))
			return 'collections/'.$action.'.php/?id='.urlencode($id);

		// check the target action
		if(!preg_match('/^(delete|edit|fetch|stream|view)$/', $action))
			$action = 'view';

		// be cool with search engines
		if(isset($context['with_friendly_urls']) && ($context['with_friendly_urls'] == 'Y'))
			return 'collections/'.$action.'.php/'.rawurlencode($id);
		else
			return 'collections/'.$action.'.php?id='.urlencode($id);
	}

}

?>