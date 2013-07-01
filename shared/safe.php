<?php
/**
 * safe PHP library
 *
 * PHP is very powerful and versatile, and this explains its phenomenal on-going success.
 * The drawback of PHP openness and richness is that some run-time may miss the promise.
 * This class attempts to compensate for such discrepancies as smoothly as possible.
 *
 * For example, some ISP (e.g., Free in France) forbid some function calls, such as
 * [code]set_time_limit()[/code]. In such a case, original code has to be modified to
 * [code]Safe::set_time_limit()[/code] instead.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Nuxwin
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Safe {

	/**
	 * change current directory
	 *
	 * @param string target directory
	 * @return TRUE on success, FALSE on failure
	 */
	public static function chdir($path) {

		// translate the path
		$path = Safe::realpath($path);

		// sanity check
		if(!is_dir($path))
			return FALSE;

		// ensure call is allowed
		if(is_callable('chdir'))
			return chdir($path);

		// tough luck
		return FALSE;

	}

	/**
	 * change file mode
	 *
	 * @param string file name
	 * @param int mode
	 * @return TRUE on success, FALSE on failure
	 */
	public static function chmod($file_name, $mode=0) {
		global $context;

		// use default mask
		if(!$mode)
			$mode = $context['file_mask'];

		// ensure call is allowed
		if(is_callable('chmod'))
			return chmod(Safe::realpath($file_name), $mode);

		// tough luck
		return FALSE;

	}

	/**
	 * release a directory resource
	 *
	 * @param resource to close
	 */
	public static function closedir($handle) {

		// sanity check
		if(!is_resource($handle))
			return;

		// ensure call is allowed
		if(is_callable('closedir'))
			closedir($handle);

	}

	/**
	 * copy a file
	 *
	 * @param string the source file
	 * @param string the destination file
	 * @return TRUE on success, FALSE on failure
	 */
	public static function copy($source, $destination) {

		// translate paths
		$source = Safe::realpath($source);
		$destination = Safe::realpath($destination);

		// sanity checks
		if(!file_exists($source) || file_exists($destination))
			return FALSE;

		// ensure call is allowed
		if(is_callable('copy'))
			return copy($source, $destination);

		// tough luck
		return FALSE;

	}

	/**
	 * define a constant only if it is not defined yet
	 *
	 * @param string constant name to be defined
	 * @param mixed the value
	 */
	public static function define($name, $value) {

		if(!defined($name))
			define($name, $value);

	}

	/**
	 * change reporting level
	 *
	 * @param int new reporting level
	 * @return int previous reporting level, or FALSE on error
	 */
	public static function error_reporting($level) {

		// ensure call is allowed
		if(is_callable('error_reporting'))
			return error_reporting($level);

		// tough luck
		return FALSE;

	}

	/**
	 * make an array out of a file
	 *
	 * @param string the file
	 * @return array on success, FALSE on failure
	 */
	public static function file($path) {

		// translate the path
		$path = Safe::realpath($path);

		// sanity check
		if(!file_exists($path))
			return FALSE;

		// ensure call is allowed
		if(is_callable('file'))
			return file($path);

		// tough luck
		return FALSE;

	}

	/**
	 * read one file in a string
	 *
	 * @param string file to read
	 * @return a string, or ''
	 */
	public static function file_get_contents($file) {

		// translate the path
		$file = Safe::realpath($file);

		// sanity check
		if(!file_exists($file))
			return '';

		// use the library
		if(is_callable('file_get_contents'))
			return file_get_contents($file);

		// access has been disallowed
		if(function_exists('file_get_contents'))
			return '';

		// attempt a direct access
		if(!$handle = Safe::fopen($file, 'rb'))
			return '';
		$content = fread($handle, Safe::filesize($file));
		fclose($handle);
		return $content;
	}

	/**
	 * write one string in a file
	 *
	 * This function also creates the path, if necessary.
	 *
	 * Warning: this function expects path information relative to the YACS
	 * installation directory. This behavior differs from the original PHP function.
	 *
	 * @param string file to write, with a path relative to the installation directory
	 * @param string new content
	 * @return int the number of bytes written to the file, or 0 on failure
	 */
	public static function file_put_contents($file, $content) {
		global $context;

		// sanity check
		if(!$file)
			return 0;

		// ensure that target folder exists
		if(!Safe::make_path(dirname($file)))
			return 0;

		// use the library
		if(is_callable('file_put_contents'))
			return file_put_contents(Safe::realpath($file), $content);

		// access has been disallowed
		if(function_exists('file_put_contents'))
			return 0;

		// attempt a direct access
		if(!$handle = Safe::fopen(Safe::realpath($file), 'wb'))
			return 0;
		$count = fwrite($handle, $content);
		fclose($handle);
		return $count;
	}

	/**
	 * date of last modification
	 *
	 * @param string name of the file to consider
	 * @return date of last modification, FALSE on failure
	 */
	public static function filemtime($file) {

		// translate the path
		$file = Safe::realpath($file);

		// sanity check
		if(!file_exists($file))
			return FALSE;

		// ensure call is allowed
		if(is_callable('filemtime'))
			return filemtime($file);

		// tough luck
		return FALSE;

	}

	/**
	 * get file size
	 *
	 * @param string the file to consider
	 * @return int on success, FALSE on failure
	 */
	public static function filesize($file) {

		// translate the path
		$file = Safe::realpath($file);

		// sanity check
		if(!file_exists($file))
			return FALSE;

		// ensure call is allowed
		if(is_callable('filesize'))
			return filesize($file);

		// tough luck
		return FALSE;

	}

	/**
	 * open a file
	 *
	 * @param string name of the file to open
	 * @param string mode
	 * @return resource on success, FALSE on failure
	 */
	public static function fopen($file, $mode) {

		// translate the path
		$file = Safe::realpath($file);

		// sanity check
		if(($mode[0] == 'r') && !file_exists($file))
			return FALSE;

		// ensure call is allowed
		if(is_callable('fopen'))
			return fopen($file, $mode);

		// tough luck
		return FALSE;

	}

	/**
	 * open a network stream
	 *
	 * @param string host name
	 * @param int socket number
	 * @param int error index, if any
	 * @param string error description, if any
	 * @param int maximum delay to wait
	 * @return resource on success, FALSE on failure
	 */
	public static function fsockopen($server, $port, &$errno, &$errstr, $timeout) {
		global $context;

		// ensure call is allowed
		if(is_callable('fsockopen') && ($handle = @fsockopen($server, $port, $errno, $errstr, $timeout))) {

			// stop network transfers on 7 second time-out
			if(is_callable('stream_set_timeout'))
				@stream_set_timeout($handle, 7);
			elseif(is_callable('socket_set_timeout'))
				@socket_set_timeout($handle, 7);

			// return a handle to the stream
			return $handle;

		}

		// tough luck
		return FALSE;

	}

	/**
	 * describe a file
	 *
	 * @param int file handle
	 * @return mixed on success, FALSE on failure
	 */
	public static function fstat($handle) {

		// sanity check
		if(!$handle)
			return FALSE;

		// ensure call is allowed -- mask warning, if any
		if(is_callable('fstat'))
			return @fstat($handle);

		// tough luck
		return FALSE;

	}

	/**
	 * get one run-time parameter
	 *
	 * @param string parameter name
	 * @return string parameter value, or FALSE on failure
	 */
	public static function get_cfg_var($parameter) {

		// use the library
		if(is_callable('get_cfg_var'))
			return get_cfg_var($parameter);

		// tough luck
		return FALSE;
	}

	/**
	 * get image meta-data
	 *
	 * @param string file name
	 * @return an array or FALSE
	 */
	public static function GetImageSize($file) {
		global $context;

		// translate the path
		$file = Safe::realpath($file);

		// ensure call is allowed
		if(is_readable($file) && is_callable('GetImageSize'))
			$output = @GetImageSize($file);
		else
			$output = FALSE;

		// end of job
		return $output;

	}

	/**
	 * list matching files
	 *
	 * @param string path and file pattern
	 * @return array fi some files have been found, FALSE otherwise
	 */
	public static function glob($pattern) {

		// ensure call is allowed
		if(is_callable('glob') && ($output = glob($pattern)) && is_array($output) && count($output))
			return $output;

		// tough luck
		return FALSE;

	}

	/**
	 * change the web response
	 *
	 * @param string a new or updated response attribute
	 * @param boolean TRUE to replace, FALSE to append
	 * @param int HTTP status code to return, if any
	 *
	 */
	public static function header($attribute, $replace=NULL, $status=NULL) {
		global $context;

 		// CGI and FastCGI error parsing headers
 		if(substr(php_sapi_name(), 0, 3) == 'cgi')
 			$attribute = str_replace('Status:', 'HTTP/1.0', $attribute);

		// in case we are validating all scripts
		if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
			return;

		// too late
		if(headers_sent($file, $line)) {

			// help on development machine
			if($context['with_debug'] == 'Y')
				Logger::remember('shared/safe.php: Can not add HTTP header', 'Headers already sent in '.$file.' on line '.$line, 'debug');

			// don't call header(), this would raise an error
			return;
		}

		// function has been allowed
		if(is_callable('header')) {
			if($status)
				header($attribute, $replace, $status);
			elseif(is_bool($replace))
				header($attribute, $replace);
			else
				header($attribute);
		}
	}

	/**
	 * beautify some code
	 *
	 * @param string the code
	 * @return string to be sent to the browser
	 */
	public static function highlight_string($text) {

		// actual highlight
		$result = highlight_string($text, TRUE);
		return $result;
	}

	/**
	 * convert all HTML entities to their applicable characters
	 *
	 * This function wraps around bugs of PHP4
	 */
	public static function html_entity_decode($text, $style=ENT_COMPAT, $charset='ISO-8859-1') {

		// php4 is buggy on this
		if((version_compare( phpversion(), '5.0' ) < 0))
			return html_entity_decode($text);

		// php5 does a better job
		return html_entity_decode($text, $style, $charset);

	}

	/**
	 * ignore user abort
	 *
	 * @param boolean new value
	 * @return previous setting, or FALSE on error
	 */
	public static function ignore_user_abort($value) {

		// change setting
		if(is_callable('ignore_user_abort'))
			return @ignore_user_abort($value);

		// tough luck
		return FALSE;
	}

	/**
	 * ensure a file has been properly uploaded
	 *
	 * @param string the file
	 * @return array on success, FALSE on failure
	 */
	public static function is_uploaded_file($path) {

		// sanity check
		if(!file_exists($path))
			return FALSE;

		// ensure call is allowed
		if(is_callable('is_uploaded_file'))
			return is_uploaded_file($path);

		// smooth operation
		return TRUE;

	}

	/**
	 * check if a file or a path is writable
	 *
	 * @param string the target path
	 * @return TRUE if the file can be written, FALSE otherwise
	 */
	public static function is_writable($path) {

		// translate the path
		$path = Safe::realpath($path);

		// the library function works on existing files only, and do not support safe mode
		if(file_exists($path) && is_callable('is_writable') && !is_writable($path))
			return FALSE;

		// no complementary test on existing directory
		if(is_dir($path))
			return TRUE;

		// complementary test uses the native PHP library
		if(!is_callable('fopen'))
			return TRUE;

		// test has to work even if file already exists
		if(!$handle = @fopen($path, 'ab'))
			return FALSE;

		// clean up
		fclose($handle);

		// delete empty file, but don't kill files that exist previously
		if(Safe::filesize($path) < 10)
			Safe::unlink($path);

		// positive test
		return TRUE;

	}

	/**
	 * get the value of a configuration option
	 *
	 * @param string configuration name
	 * @return a string or ''
	 */
	public static function ini_get($name) {

		// ensure call is allowed
		if(is_callable('ini_get'))
			return ini_get($name);

		// tough luck
		return '';

	}

	/**
	 * set the value of a configuration option
	 *
	 * @param string configuration name
	 * @param string the new value
	 * @return a string or FALSE
	 */
	public static function ini_set($name, $value) {

		// ensure call is allowed
		if(is_callable('ini_set'))
			return @ini_set($name, $value);

		// tough luck
		return FALSE;

	}

	/**
	 * unserialize an array the JSON way
	 *
	 * @param string the serialized version
	 * @return mixed the resulting array, or FALSE on error
	 */
	public static function json_decode($text) {
		global $context;

		// maybe we have a native extension --return an associative array
//		if(is_callable('json_decode'))
//			return json_decode($text, TRUE);

		// load the PHP library
		if(file_exists($context['path_to_root'].'included/json.php')) {
			include_once $context['path_to_root'].'included/json.php';
			return json_decode2($text);
		}

		// tough luck
		return FALSE;
	}

	/**
	 * serialize an array the JSON way
	 *
	 * @param mixed the array to serialize
	 * @return mixed a string, or FALSE on error
	 */
	public static function json_encode($data) {
		global $context;

		// maybe we have a native extension
//		if(is_callable('json_encode'))
//			return json_encode($data);

		// load the PHP library
		if(file_exists($context['path_to_root'].'included/json.php')) {
			include_once $context['path_to_root'].'included/json.php';
			return json_encode2($data);
		}

		// tough luck
		return FALSE;
	}

	/**
	 * include one script
	 *
	 * If you include some file with this function, and miss some variables afterwards,
	 * ensure that these variables are globally defined in the included file.
	 *
	 * @param string file name, relative to installation directory
	 * @return boolean TRUE if file has been included, FALSE otherwise
	 * @param boolean TRUE to force a reload, FALSE to load the file only once
	 */
	public static function load($file, $reload=FALSE) {
		global $context;

		// translate the path
		$file = Safe::realpath($file);

		// ensure that the file exists
		if(!is_readable($file))
			return FALSE;

		// include it as many times as necessary
		if($reload)
			include $file;
		else
			include_once $file;

		return TRUE;
	}

	/**
	 * create a complete path to a file
	 *
	 * The target path can be relative to YACS, or an absolute path pointing
	 * almost anywhere.
	 *
	 * @param the target path
	 * @return TRUE on success, or FALSE on failure
	 */
	public static function make_path($path) {
		global $context;

		// sanity check
		if(!$path)
			return TRUE;

		// translate path
		$translated = Safe::realpath($path);

		// the path exists
		if(is_dir($translated))
			return TRUE;

		// create upper level first
		$dir_name = dirname($path);

		if(($dir_name != $path) && preg_match('|/|', $dir_name)) {

			// it is mandatory to have upper level
			if(!Safe::make_path($dir_name))
				return FALSE;

		}

		// create last level directory
		return Safe::mkdir($translated);

	}

	/**
	 * create a directory
	 *
	 * @param string path name
	 * @param int mode
	 * @return TRUE on success, FALSE on failure
	 */
	public static function mkdir($path_name, $mode=0) {
		global $context;

		// use default mask
		if(!$mode)
			$mode = $context['directory_mask'];

		// maybe path already exists
		if(is_dir($path_name))
			return TRUE;

		// if a file has the same name
		if(file_exists($path_name))
			return FALSE;

		// ensure call is allowed
		if(is_callable('mkdir') && mkdir($path_name, $mode)) {

			// create an index file to avoid browsing --direct call of file_put_contents because of absolute path
			if(is_callable('file_put_contents'))
				file_put_contents($path_name.'/index.php', Safe::mkdir_index_content());

			// mkdir has been successful
			return TRUE;
		}

		// tough luck
		return FALSE;

	}

	/**
	 * content of index.php created by yacs
	 *
	 * @return string
	 */
	public static function mkdir_index_content() {
		return '<'.'?php echo "Browsing is not allowed here."; ?'.'>';
	}

	/**
	 * move a new file
	 *
	 * @param string the source file
	 * @param string the destination file
	 * @return TRUE on success, FALSE on failure
	 */
	public static function move_uploaded_file($source, $destination) {

		// translate the path
		$destination = Safe::realpath($destination);

		// ensure call is allowed
		if(is_callable('move_uploaded_file'))
			return @move_uploaded_file($source, $destination);

		// tough luck
		return FALSE;

	}

	/**
	 * start output buffering
	 *
	 * @param string handler to use
	 */
	public static function ob_start($handler='ob_gz_handler') {

		// call only once
		static $fuse;
		if(isset($fuse))
			return;
		$fuse = TRUE;

		// sanity check
		if(headers_sent())
			return;

		// avoid blank pages
		$starter = '';
		if(is_callable('ob_get_contents') && is_callable('ob_end_clean') && ($starter = ob_get_contents()))
			ob_end_clean();

		// install handler
		if( (!is_callable('ob_list_handlers') || (array_search($handler, ob_list_handlers()) === FALSE))
			&& is_callable('ob_start') && is_callable($handler) )
			ob_start($handler);

		// print error messages, if any
		if($starter)
			echo $starter;

	}


	/**
	 * prepare to read content of a directory
	 *
	 * @param string path to read
	 * @return resource on success, FALSE on failure
	 */
	public static function opendir($path) {

		// translate the path
		$path = Safe::realpath($path);

		// sanity check
		if(!is_dir($path))
			return FALSE;

		// ensure call is allowed
		if(is_callable('opendir'))
			return opendir($path);

		// tough luck
		return FALSE;

	}

	/**
	 * get next entry in directory
	 *
	 * @param resource handle to browsed directory
	 * @return string on success, FALSE on failure
	 */
	public static function readdir($handle) {

		// sanity check
		if(!is_resource($handle))
			return FALSE;

		// ensure call is allowed
		if(is_callable('readdir'))
			return readdir($handle);

		// tough luck
		return FALSE;

	}

	/**
	 * locate some file
	 *
	 * This function translates a path relative to YACS installation directory
	 * to an absolute path to the target file, if it exists.
	 *
	 * Note: this function does not invoke the PHP ##realpath()## function at
	 * all, and has a different behavior.
	 *
	 * @param string path to some file
	 * @return string the translated string
	 */
	public static function realpath($path) {
		global $context;

		// sanity check
		if(!$path)
			return $path;

		// an absolute path
		if(($path[0] == '/') || ($path[0] == '.'))
			;

		// an URI, or a MS-Windows drive
		elseif(preg_match('/^[a-zA-Z]+:/', $path))
			;

		// a network UNC
		elseif(strpos($path, '\\') === 0)
			;

		// maybe a relative path
		else
			$path = $context['path_to_root'].$path;

		// the translated path
		return $path;

	}

	/**
	 * jump to another web page
	 *
	 * This function never returns.
	 *
	 * @param string the target full web address
	 */
	public static function redirect($reference) {
		global $context;

		// the actual redirection directive
		Safe::header('Location: '.$reference);

		// a message for human beings
		if(!is_callable(array('i18n', 's')))
			exit();
		exit(sprintf(i18n::s('Redirecting to %s'), '<a href="'.$reference.'">'.$reference.'</a>'));

	}

	/**
	 * rename a file
	 *
	 * @param string the original file
	 * @param string the target file
	 * @return TRUE on success, FALSE on failure
	 */
	public static function rename($original, $target) {

		// translate paths
		$original = Safe::realpath($original);
		$target = Safe::realpath($target);

		// sanity checks
		if(!file_exists($original) || file_exists($target))
			return FALSE;

		// ensure call is allowed
		if(is_callable('rename'))
			return rename($original, $target);

		// tough luck
		return FALSE;

	}

	/**
	 * remove a directory
	 *
	 * @param string path to directory to delete
	 * @return TRUE on success, FALSE on failure
	 */
	public static function rmdir($path) {

		// translate the path
		$path = Safe::realpath($path);

		// maybe path has been already removed
		if(!is_dir($path))
			return TRUE;

		// ensure call is allowed
		if(is_callable('rmdir')) {

			// remove 'index.php', if any
			Safe::unlink($path.'/index.php');

			// do remove the directory
			return @rmdir($path);
		}

		// tough luck
		return FALSE;

	}

	/**
	 * extends execution time
	 *
	 * Set the number of seconds a script is allowed to run.
	 *
	 * @param int number of seconds
	 */
	public static function set_time_limit($duration) {

		// ensure call is allowed -- safe mode is a special case
		if((is_callable('set_time_limit')) && (!Safe::ini_get('safe_mode')))
			@set_time_limit($duration);

	}

	/**
	 * save data on browser side
	 *
	 * @return TRUE on success, FALSE otherwise
	 */
	public static function setcookie($name, $value, $expire, $path, $domain=NULL) {
		global $context;

		// no way to send something back
		if(headers_sent())
			return FALSE;

		// tough luck
		if(!is_callable('setcookie'))
			return FALSE;

		// sanity check
		if(!$domain)
			$domain = $context['host_name'];

		// enable sub-domains, using only last two name components: www.mydomain.com -> .mydomain.com
		if(($parent_domain = strstr($domain, '.')) && strpos($parent_domain, '.', 1))
			$domain = $parent_domain;

		// do the job
		return setcookie($name, $value, $expire, $path, $domain);

	}

	/**
	 * invoke system shell
	 *
	 * On a Linux system, you can launch background processing with the following call:
	 *
	 * [php]
	 * Safe::shell_exec("/path/to/program > /dev/null &");
	 * [/php]
	 *
	 * @param string command
	 * @return the output of the command, or NULL
	 */
	public static function shell_exec($text) {

		// ensure call is allowed
		if(!is_callable('shell_exec'))
			return NULL;

		// do the job
		$outcome = shell_exec($text);
		return $outcome;
	}

	/**
	 * delay execution for some time
	 *
	 * @param int number of seconds
	 */
	public static function sleep($duration) {

		// ensure call is allowed
		if(is_callable('sleep'))
			sleep($duration);

	}

	/**
	 * describe a file
	 *
	 * @param string path to file to consider
	 * @return mixed on success, FALSE on failure
	 */
	public static function stat($file) {

		// translate the path
		$file = Safe::realpath($file);

		// maybe node has been already removed
		if(!file_exists($file))
			return FALSE;

		// ensure call is allowed
		if(is_callable('stat'))
			return stat($file);

		// tough luck
		return FALSE;

	}

	/**
	 * generate a system log message
	 *
	 * @param int priority
	 * @param string message to save
	 * @return an integer
	 */
	public static function syslog($priority, $message) {

		// ensure call is allowed
		if(is_callable('syslog'))
			return @syslog($priority, $message);

		// tough luck
		return 0;

	}

	/**
	 * touch a file
	 *
	 * @param string the target file
	 * @param int time of last modification
	 * @return TRUE on success, FALSE on failure
	 */
	public static function touch($file, $modification_stamp=NULL) {

		// translate the path
		$file = Safe::realpath($file);

		// ensure call is allowed
		if(is_callable('touch')) {

			// ensure we have a stamp
			if(!$modification_stamp)
				$modification_stamp = time();

			return touch($file, $modification_stamp);

		}

		// tough luck
		return FALSE;

	}

	/**
	 * remove a file
	 *
	 * @param string path to file to delete
	 * @return TRUE on success, FALSE on failure
	 */
	public static function unlink($file) {

		// translate the path
		$file = Safe::realpath($file);

		// maybe node has been already removed
		if(!file_exists($file))
			return TRUE;

		// ensure call is allowed
		if(is_callable('unlink'))
			return @unlink($file);

		// tough luck
		return FALSE;

	}

	/**
	 * restore a serialized entity
	 *
	 * Unicode entities are restored before unserializing, which is required
	 * for proper support of UTF-8 database engine.
	 *
	 * @param string data to be unserialized
	 * @return mixed restored object, or FALSE on error
	 */
	public static function unserialize($text) {
		global $context;

		// the returned value
		$output = FALSE;

		// do the job
		if(!is_callable('unserialize'))
			return $output;

		// attempt to restore Unicode entities before de-serializing -- avoid notification message, if any
		if(isset($context['database_is_utf8']) && $context['database_is_utf8'] && is_callable('utf8', 'from_unicode'))
			$output = @unserialize(utf8::to_unicode($text));

		// direct unserialization -- avoid notification message, if any
		if(!$output)
			$output = @unserialize($text);

		// job done
		return $output;

	}
}

// yes, we are proud of this piece of software
Safe::header('X-Powered-By: yacs (http://www.yacs.fr/)');

?>
