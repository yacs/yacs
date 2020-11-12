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
 * @author Bernard Paques
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
			return $this->store($name, $date, $data);

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
        
        function errmsg($errno) {
            
            // using constant name as a string to make this function PHP4 compatible
            $zipFileFunctionsErrors = array(
              'ZIPARCHIVE::ER_MULTIDISK'    => 'Multi-disk zip archives not supported.',
              'ZIPARCHIVE::ER_RENAME'       => 'Renaming temporary file failed.',
              'ZIPARCHIVE::ER_CLOSE'        => 'Closing zip archive failed',
              'ZIPARCHIVE::ER_SEEK'         => 'Seek error',
              'ZIPARCHIVE::ER_READ'         => 'Read error',
              'ZIPARCHIVE::ER_WRITE'        => 'Write error',
              'ZIPARCHIVE::ER_CRC'          => 'CRC error',
              'ZIPARCHIVE::ER_ZIPCLOSED'    => 'Containing zip archive was closed',
              'ZIPARCHIVE::ER_NOENT'        => 'No such file.',
              'ZIPARCHIVE::ER_EXISTS'       => 'File already exists',
              'ZIPARCHIVE::ER_OPEN'         => 'Can\'t open file',
              'ZIPARCHIVE::ER_TMPOPEN'      => 'Failure to create temporary file.',
              'ZIPARCHIVE::ER_ZLIB'         => 'Zlib error',
              'ZIPARCHIVE::ER_MEMORY'       => 'Memory allocation failure',
              'ZIPARCHIVE::ER_CHANGED'      => 'Entry has been changed',
              'ZIPARCHIVE::ER_COMPNOTSUPP'  => 'Compression method not supported.',
              'ZIPARCHIVE::ER_EOF'          => 'Premature EOF',
              'ZIPARCHIVE::ER_INVAL'        => 'Invalid argument',
              'ZIPARCHIVE::ER_NOZIP'        => 'Not a zip archive',
              'ZIPARCHIVE::ER_INTERNAL'     => 'Internal error',
              'ZIPARCHIVE::ER_INCONS'       => 'Zip archive inconsistent',
              'ZIPARCHIVE::ER_REMOVE'       => 'Can\'t remove file',
              'ZIPARCHIVE::ER_DELETED'      => 'Entry has been deleted',
            );
            
            foreach ($zipFileFunctionsErrors as $constName => $errorMessage) {
              if (defined($constName) and constant($constName) === $errno) {
                return 'Error: '.$errorMessage;
              }
            }
            return 'Error: unknown';
          }

	/**
	 * explode one archive
	 *
	 * @param string archive to handle
	 * @param string the place where extracted files have to be placed
	 * @param string the prefix to be removed from entry names (typically, 'yacs/')
	 * @param function to be called on any file extracted
	 * @return int the number of files that have been successfully extracted
	 */
	function explode($archive, $path='', $remove='', $callback=NULL) {
		global $context;

		// terminate path, if applicable
		if(strlen($path) && (substr($path, -1) != '/'))
			$path .= '/';

		// ensure we can invoke functions we need
		if(!is_callable('zip_open') || !is_callable('zip_read') || !is_callable('zip_entry_name') || !is_callable('zip_entry_open') || !is_callable('zip_entry_read') || !is_callable('zip_entry_filesize')) {
			Logger::error(i18n::c('Impossible to extract files.'));
			return 0;
		}

                $handle = zip_open($archive);    
		// incorrect file
		if(!is_resource($handle)) {
			Logger::error(sprintf(i18n::c('Impossible to read %s.').' '.zipfile::errmsg($handle), $archive));
			return 0;
		}

		// read all entries
		$count = 0;
		while($item = zip_read($handle)) {

			// full name, as recorded in the archive
			if(!$name = zip_entry_name($item))
				continue;

			// sanity check
			if((strlen($name) < 1) || (($name[0] != '/') && (($name[0] < ' ') || ($name[0] > 'z'))))
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
			if($size = zip_entry_filesize($item))
				$content = zip_entry_read($item, $size);
			else
				$content = '';

			// write the extracted file
			if(Safe::file_put_contents($path.$name, $content)) {
				$count++;

				// callback function
				if($callback)
					$callback($path.$name);
			}

			// make room for next item
			if(is_callable('zip_entry_close'))
				zip_entry_close($item);

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
