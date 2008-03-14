<?php
/**
 * handle a zip archive file
 *
 * This script is useful to package several files on the fly into a single downloaded archive,
 * or to extract components of an uploaded archive.
 *
 * Example of the creation of a zip file:
 * [php]
 *	// build a zip archive
 *	include_once 'zipfile.php';
 *	$zipfile = new zipfile();
 *
 *	// place all files into a single directory
 *	$zipfile->store('files/', time());
 *
 *	// archive each file
 *	foreach($items as $id => $name) {
 *
 *		// read file content
 *		if($content = Safe::file_get_contents($file_path.$name)) {
 *
 *			// add the binary data stored in the string 'filedata'
 *			$zipfile->store('files/'.$name, Safe::filemtime($file_path.$name), $content);
 *		}
 *	}
 *
 *	// suggest a download
 *	Safe::header('Content-Type: application/zip');
 *	Safe::header('Content-Disposition: attachment; filename="download.zip"');
 *
 *	// send the archive content
 *	echo $zipfile->get();
 *	return;
 * [/php]
 *
 * Example of an archive explode:
 * [php]
 *	include_once 'zipfile.php';
 *	$zipfile = new zipfile();
 *
 * // extract archive components and save them in mentioned directory
 * $count = $zipfile->explode($uploaded, $target_path);
 * [/php]
 *
 * @link http://www.pkware.com/appnote.txt official ZIP file format
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author Eric Mueller [link]http://www.themepark.com/[/link]
 * @author Denis O.Philippov [email]webmaster@atlant.ru[/email], [link]http://www.atlant.ru[/link]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class zipfile {

	/**
	 * compressed data
	 */
	var $file_data = '';

	/**
	 * file descriptors
	 */
	var $directory_data = '';

	/**
	 * number of entries
	 */
	var $file_count = 0;

	/**
	 * deflate one file
	 *
	 * @param string the file name, with any directory information if any
	 * @param int the time stamp of the file, generally obtained through Safe::filemtime()
	 * @param string the actual content of the file to be compressed
	 */
	function deflate($name, $date, $data=NULL) {

		// get data information
		$data_length = strlen($data);
		$crc = crc32($data);

		// where we are in the archive
		$offset = strlen($this->file_data);

		// use unix-style separator
		$name = str_replace("\\", '/', $name);

		// sanity checks
		if(!$date)
			$date = time();
		if(is_string($date))
			$date = strtotime($date);

		//shift the bits around to MS-DOS format
		$date = getdate($date);
		$date = (($date['year'] - 1980) << 25) | ($date['mon'] << 21) | ($date['mday'] << 16) | ($date['hours'] << 11) | ($date['minutes'] << 5) | ($date['seconds'] >> 1);

		// actually deflate the file
		$zdata = gzcompress($data);
		$zdata = substr($zdata, 2, -4); // fix crc bug
		$zdata_length = strlen($zdata);

		//sanity check
		if($data_length < $zdata_length)
			return($this->store($name, $date, $data));

		//
		// add one file record
		//

		// local file header n with a deflated file
		$file_record = "\x50\x4b\x03\x04";				// local file header signature	   4 bytes	(0x04034b50)
		$file_record .= "\x14\x00"; 					// version needed to extract	   2 bytes
		$file_record .= "\x00\x00"; 					// general purpose bit flag 	   2 bytes
		$file_record .= "\x08\x00"; 					// compression method			   2 bytes - deflate
		$file_record .= pack('V', $date);				// last mod file time			   2 bytes
														// last mod file date			   2 bytes
		$file_record .= pack("V",$crc); 				// crc-32						   4 bytes
		$file_record .= pack("V",$zdata_length);		// compressed size				   4 bytes
		$file_record .= pack("V",$data_length); 		// uncompressed size			   4 bytes
		$file_record .= pack("v", strlen($name));		// file name length 			   2 bytes
		$file_record .= pack("v", 0 );					// extra field length			   2 bytes
		$file_record .= $name;							// file name (variable size)
														// extra field (variable size)

		// file data n
		$file_record .= $zdata;

		// data descriptor n (exists only if bit 3 of the general purpose bit flag is set)
//		$file_record .= pack("V",$crc); 				// crc-32						   4 bytes
//		$file_record .= pack("V",$zdata_length);		// compressed size				   4 bytes
//		$file_record .= pack("V",$data_length); 		// uncompressed size			   4 bytes

		// add this entry to array
		$this->file_data .= $file_record;

		//
		// add one directory entry
		//

		// file header
		$directory_entry = "\x50\x4b\x01\x02";			// central file header signature   4 bytes	(0x02014b50)
		$directory_entry .="\x14\x00";					// version made by				   2 bytes - VFAT
		$directory_entry .="\x14\x00";					// version needed to extract	   2 bytes
		$directory_entry .="\x00\x00";					// general purpose bit flag 	   2 bytes
		$directory_entry .="\x08\x00";					// compression method			   2 bytes - deflate
		$directory_entry .= pack('V', $date);			// last mod file time			   2 bytes
														// last mod file date			   2 bytes
		$directory_entry .= pack("V",$crc); 			// crc-32						   4 bytes
		$directory_entry .= pack("V",$zdata_length);	// compressed size				   4 bytes
		$directory_entry .= pack("V",$data_length); 	// uncompressed size			   4 bytes
		$directory_entry .= pack("v", strlen($name));	// file name length 			   2 bytes
		$directory_entry .= pack("v", 0);				// extra field length			   2 bytes
		$directory_entry .= pack("v", 0);				// file comment length			   2 bytes
		$directory_entry .= pack("v", 0);				// disk number start			   2 bytes
		$directory_entry .= pack("v", 0);				// internal file attributes 	   2 bytes
		$directory_entry .= pack("V", 32);				// external file attributes 	   4 bytes - 'archive' bit set

		$directory_entry .= pack("V", $offset); 		// relative offset of local header 4 bytes

		$directory_entry .= $name;						// file name (variable size)
														// extra field (variable size)
														// file comment (variable size)

		// save to central directory
		$this->directory_data .= $directory_entry;

		$this->file_count += 1;
	}

	/**
	 * explode one archive
	 *
	 * @param string archive to handle
	 * @param string the place where extracted files have to be placed
	 * @param string the prefix to be removed from entry names (typically, 'yacs/')
	 * @return int the number of files that have been successfully extracted
	 */
	function explode($archive, $path='', $remove='') {
		global $context;

		// terminate path, if applicable
		if(strlen($path) && (substr($path, -1) != '/'))
			$path .= '/';

		// ensure we can invoke functions we need
		if(!is_callable('zip_open') || !is_callable('zip_read') || !is_callable('zip_entry_name') || !is_callable('zip_entry_open') || !is_callable('zip_entry_read') || !is_callable('zip_entry_filesize')) {
			Skin::error(i18n::c('Impossible to extract files.'));
			return 0;
		}

		// incorrect file
		if(!$handle = zip_open($archive)) {
			Skin::error(sprintf(i18n::c('Impossible to read %s.'), $archive));
			return 0;
		}

		// read all entries
		$count = 0;
		while($item = zip_read($handle)) {

			// full name, as recorded in the archive
			if(!$name = zip_entry_name($item))
				continue;

			// sanity check
			if((strlen($name) < 1) || (($name[0] != '/') && (($name[0] < 'A') || ($name[0] > 'z'))))
				continue;

			// directories are created on actual content
			if(substr($name, -1) == '/')
				continue;

			// remove path prefix, if any
			if($remove)
				$name = preg_replace('/^'.preg_quote($remove, '/').'/', '', $name);

			// read entry content
			if(!zip_entry_open($handle, $item, 'rb'))
				continue;
			$content = zip_entry_read($item, zip_entry_filesize($item));
			if(is_callable('zip_entry_close'))
				zip_entry_close($item);

			// write the extracted file
			if(Safe::file_put_contents($path.$name, $content))
				$count++;

		}

		// done
		if(is_callable('zip_close'))
			zip_close($handle);

		// everything went well
		return $count;
	}

	/**
	 * store one file without compressing it
	 *
	 * @param string the file name, with any directory information if any
	 * @param int the time stamp of the file, generally obtained through [code]Safe::filemtime()[/code]
	 * @param string the actual content of the file to be compressed
	 * @return void
	 */
	function store($name, $date, $data=NULL) {

		// get data information
		$data_length = strlen($data);
		$crc = crc32($data);

		// where we are in the archive
		$offset = strlen($this->file_data);

		// use unix-style separator
		$name = str_replace("\\", "/", $name);

		//shift the bits around to MS-DOS format
		if(is_string($date))
			$date = strtotime($date);
		$date = getdate($date);
		$date = (($date['year'] - 1980) << 25) | ($date['mon'] << 21) | ($date['mday'] << 16) | ($date['hours'] << 11) | ($date['minutes'] << 5) | ($date['seconds'] >> 1);

		//
		// add one file record
		//

		// local file header n with a stored file
		$file_record = "\x50\x4b\x03\x04";				// local file header signature	   4 bytes	(0x04034b50)
		$file_record .= "\x14\x00"; 					// version needed to extract	   2 bytes
		$file_record .= "\x00\x00"; 					// general purpose bit flag 	   2 bytes
		$file_record .= "\x00\x00"; 					// compression method			   2 bytes - no compression
		$file_record .= pack('V', $date);				// last mod file time			   2 bytes
														// last mod file date			   2 bytes
		$file_record .= pack("V",$crc); 				// crc-32						   4 bytes
		$file_record .= pack("V",$data_length); 		// compressed size				   4 bytes
		$file_record .= pack("V",$data_length); 		// uncompressed size			   4 bytes
		$file_record .= pack("v", strlen($name));		// file name length 			   2 bytes
		$file_record .= pack("v", 0 );					// extra field length			   2 bytes
		$file_record .= $name;							// file name (variable size)
														// extra field (variable size)
		// file data n
		$file_record .= $data;

		// data descriptor n (exists only if bit 3 of the general purpose bit flag is set)
//		$file_record .= pack("V",$crc); 				// crc-32						   4 bytes
//		$file_record .= pack("V",$zdata_length);		// compressed size				   4 bytes
//		$file_record .= pack("V",$data_length); 		// uncompressed size			   4 bytes

		// add this entry to array
		$this->file_data .= $file_record;

		//
		// add one directory entry
		//

		// file header
		$directory_entry = "\x50\x4b\x01\x02";			// central file header signature   4 bytes	(0x02014b50)
		$directory_entry .="\x14\x00";					// version made by				   2 bytes - VFAT
		$directory_entry .="\x14\x00";					// version needed to extract	   2 bytes
		$directory_entry .="\x00\x00";					// general purpose bit flag 	   2 bytes
		$directory_entry .="\x00\x00";					// compression method			   2 bytes - no compression
		$directory_entry .= pack('V', $date);			// last mod file time			   2 bytes
														// last mod file date			   2 bytes
		$directory_entry .= pack("V",$crc); 			// crc-32						   4 bytes
		$directory_entry .= pack("V",$data_length); 	// compressed size				   4 bytes
		$directory_entry .= pack("V",$data_length); 	// uncompressed size			   4 bytes
		$directory_entry .= pack("v", strlen($name));	// file name length 			   2 bytes
		$directory_entry .= pack("v", 0);				// extra field length			   2 bytes
		$directory_entry .= pack("v", 0);				// file comment length			   2 bytes
		$directory_entry .= pack("v", 0);				// disk number start			   2 bytes
		$directory_entry .= pack("v", 0);				// internal file attributes 	   2 bytes
		$directory_entry .= pack("V", 32);				// external file attributes 	   4 bytes - 'archive' bit set

		$directory_entry .= pack("V", $offset); 		// relative offset of local header 4 bytes

		$directory_entry .= $name;						// file name (variable size)
														// extra field (variable size)
														// file comment (variable size)

		// save to central directory
		$this->directory_data .= $directory_entry;

		$this->file_count += 1;
	}

	/**
	 * dump the archive content
	 *
	 * @return the bytes to be saved into a file, or sent accross the network
	 */
	function get() {

		return
			// all file records
			$this->file_data.

			// all directory entries
			$this->directory_data.

			// digital signature
//			"\x50\x4b\x05\x05". 							// header signature 			   4 bytes	(0x05054b50)
//			"\x00\x00". 									// size of data 				   2 bytes
															// signature data (variable size)

			// end of central directory record
			"\x50\x4b\x05\x06\x00\x00\x00\x00". 			// end of central dir signature    4 bytes	(0x06054b50)
															// number of this disk			   2 bytes
															// number of the disk with the
															// start of the central directory  2 bytes

			pack("v", $this->file_count).					// total number of entries in the
															// central directory on this disk  2 bytes
			pack("v", $this->file_count).					// total number of entries in
															// the central directory		   2 bytes
			pack("V", strlen($this->directory_data)).		// size of the central directory   4 bytes
			pack("V", strlen($this->file_data)).			// offset of start of central
															// directory with respect to
															// the starting disk number 	   4 bytes
			"\x00\x00"; 									// .ZIP file comment length 	   2 bytes
															// .ZIP file comment	   (variable size)
	}
}

?>