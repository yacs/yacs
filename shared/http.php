<?php
/**
 * handle the web protocol
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class http {

	/**
	 * use cache by expiration
	 *
	 * Ask the user agent to cache data for some time.
	 * If the provided parameter is set to 0, ask for systematic validation instead.
	 *
	 * @param int number of seconds to cache (default: 30 minutes)
	 */
	function expire($time=1800) {
	
		// ask for revalidation - 'no-cache' is mandatory for IE6 !!!
		if(!$time || ($time < 1)) {
			Safe::header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
			Safe::header('Cache-Control: private, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
			Safe::header('Pragma:');
		} else {
			Safe::header('Expires: '.gmdate("D, d M Y H:i:s", time() + $time).' GMT');
			Safe::header('Cache-Control: max-age='.$time.', public');
			Safe::header('Pragma: ');
		}
	}
	
	/**
	 * validate data from user agent
	 *
	 * @param string the date of last modification
	 * @param string the opaque string characterizing the target object
	 * @return boolean TRUE if the client has provided the right headers, FALSE otherwise
	 */
	function validate($last_modified, $etag=NULL) {
	
		// not cached yet
		$cached = FALSE;
		
		// web cache is not managed
		if(isset($context['without_http_cache']) && ($context['without_http_cache'] == 'Y'))
			return FALSE;
	
		// validate the content if date of last modification is the same
		if($last_modified && isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && ($if_modified_since = preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']))) {
			if(($if_modified_since == $last_modified) && !isset($_SERVER['HTTP_IF_NONE_MATCH']))
				$cached = TRUE;
		}

		// validate the content if hash is ok
		if($etag && isset($_SERVER['HTTP_IF_NONE_MATCH']) && is_array($if_none_match = explode(',', str_replace('\"', '"', $_SERVER['HTTP_IF_NONE_MATCH'])))) {
			foreach($if_none_match as $target) {
				if(trim($target) == $etag) {
					$cached = TRUE;
					$break;
				}
			}
		}

		// client has the right data
		if($cached) {

			// set the date for last modification
			if($last_modified)
				Safe::header('If-Modified-Since: '.$last_modified);

			// set the opaque string for this object
			if($etag)
				Safe::header('If-None-Match: '.$etag);

			// the client should use data in cache
			Safe::header('Status: 304 Not Modified', TRUE, 304);
			
		// set meta information to allow for cache
		} else {

			// set the date for last modification
			if($last_modified)
				Safe::header('Last-Modified: '.$last_modified);

			// set the opaque string for this object
			if($etag)
				Safe::header('ETag: '.$etag);

		}

		// what we have found
		return $cached;
	}
}

?>