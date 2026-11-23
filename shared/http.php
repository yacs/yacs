<?php

/**
 * handle the web protocol
 *
 * You can use the Resource Expert Droid (RED) web service to validate headers produced by your scripts.
 *
 * @link http://redbot.org/
 *
 * @author Bernard Paques
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class http {

    /**
     * add a parameter to a given url
     * 
     * @param string $url with or without existing parameters
     * @param mixed the parameter name to add or an array of name=>value
     * @param string the parameter value (if param name is a string)
     * @return string to new formed url
     */
    public static function add_url_param($url, $param_name, $param_value=null) {
        
        //sanity check
        if(!$url) return null;

        // recursive call if an array is provided
        if(is_array($param_name)) {
            
            // array must be associative
            if((array() === $param_name) 
                    || array_keys($param_name) === range(0, count($param_name) -1)) {
                return null;
            }
            
            // recursive call for each param
            foreach($param_name as $name => $value) {
                $new_url = Self::add_url_param($url, $name, $value);
            }
            return $new_url;
        }
        
        $new_url = $url; // at least

        // if param already present, just replace it
        $matches = array();
        if (preg_match("/(\?|&)$param_name(\[\])*=/", $new_url, $matches)) {
            
            $insert_pos = strpos($url, $matches[0]);
            $insert_end = strpos($url,'&',$insert_pos);
            
            if($insert_end) {
                $new_url = substr_replace($url, $matches[0] . rawurlencode($param_value), $insert_pos, $insert_end - $insert_pos);
            } else {
                $new_url = substr_replace($url, $matches[0] . rawurlencode($param_value), $insert_pos);
            }
            
        } else {

            // determine & or ? separator to add param
            $separator  = (strpos($url, "?") !== false) ? '&' : '?';

            // determine param insertion, in case of #anchor in url
            $insert_pos = (strpos($url, "#") !== false) ? strpos($url, "#") : strlen($url);

            // Build the new url
            $new_url    = substr_replace($url, $separator . rawurlencode($param_name) . '=' . rawurlencode($param_value) , $insert_pos, 0);
        }

        return $new_url;
    }

    /**
     * remember headers of the last response
     *
     * This is a call-back function used in proceed_natively() and in proceed_using_curl() as well.
     *
     * @link http://fr.php.net/manual/en/function.curl-setopt.php
     *
     * @param the channel considered
     * @param string received HTTP header
     * @return sizeof headers
     */
    public static function callback_headers($dummy, $header) {

        // remember this header
        if (rtrim($header)) {
            global $http_fetch_headers;
            $http_fetch_headers[] = rtrim($header);
        }

        // mandatory information
        return strlen($header);
    }

    /**
     * use cache by expiration
     *
     * Ask the user agent to cache data for some time.
     * If the provided parameter is set to 0, ask for systematic validation instead.
     *
     * [*] Internet Explorer may have strange behaviour with the [code]Expire[/code] attribute.
     * It does not take into account very short-term expiration date and does not validate after the deadline.
     * On the other hand, setting an expiration date is useful to fix the 'download a .zip file directly from the browser' bug.
     * We recommend to set this attribute in all scripts related to file transfers and download, and to not set it
     * at all in every other script.
     *
     * [*] The [code]Cache-Control[/code] attribute allows for cache-control.
     * It has been primarily designed for HTTP/1.1 agents, and few proxies seem to handle it correctly at the moment.
     * However to explicitly declare that the output of some script may be cached for three hours by intermediate proxies,
     * you can use [code]Safe::header("Cache-Control: private, max-age=10800");[/code].
     * On the other hand, if only the user-agent (i.e., the browser) is allowed to cache something,
     * you can use [code]Safe::header("Cache-Control: no-cache, max-age=10800");[/code].
     *
     * [*] What to do with [code]Pragma:[/code]? Well, almost nothing; this is used only by some legacy browsers.
     * If you want an old browser to cache some object, use [code]Safe::header("Pragma: no-cache");[/code].
     *
     * @param int number of seconds to cache (default: 30 minutes)
     */
    public static function expire($time = 1800) {

        // ask for revalidation - 'no-cache' is mandatory for IE6 !!!
        if (!$time || ($time < 1)) {
            Safe::header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
            Safe::header('Cache-Control: no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
            Safe::header('Pragma: no-cache');
        } else {
            Safe::header('Expires: ' . gmdate("D, d M Y H:i:s", time() + $time) . ' GMT');
            Safe::header('Cache-Control: private, max-age=' . $time);
            Safe::header('Pragma: no-cache');
        }
    }

    /**
     * get base part of the URI to the current script
     *
     * This should be used for users passing through proxies.
     *
     * @return string the base URI
     */
    public static function get_base_uri() {
        global $context, $_SERVER;

        // host name
        if (isset($_SERVER['HTTP_VIA']) && ($tags = explode(' ', $_SERVER['HTTP_VIA'], 3)) && isset($tags[1]))
            $host_name = rtrim($tags[1], ' ,'); // from outer proxy
        else
            $host_name = $context['host_name']; // from running configuration

            
// URL
        $root_url = '';
        if (isset($_SERVER['X_FORWARDED_BASE']) && $_SERVER['X_FORWARDED_BASE'])
            $root_url .= $_SERVER['X_FORWARDED_BASE']; // from reverse proxy
        $root_url .= $context['url_to_root']; // from running configuration
        // done.
        return 'http://' . $host_name . $root_url;
    }

    /**
     * returns the last error description, if any
     */
    public static function get_error() {
        global $http_internal_error;
        return $http_internal_error;
    }

    /**
     * returns headers of the last response, if any
     */
    public static function get_headers() {
        global $http_fetch_headers;
        return implode("\n", $http_fetch_headers);
    }

    /**
     * just terminate the web transaction
     *
     * Useful to AJAX responses when nothing has to be transmitted back to the browser.
     */
    public static function no_content() {
        Safe::header('Status: 204 No Content', TRUE, 204);
        Safe::header('Content-Length: 0', true);
        Safe::header('Content-Type: text/html', true);
    }

    /**
     * send a request and get a response
     *
     * @todo on successful fetch put Last-Modified and ETag in the cache
     *
     * @param string the link to fetch
     * @param array of strings optional headers (eg, 'array("Content-Type: text/xml")')
     * @param string optional data to send
     * @param string cookie, if any
     * @return the actual content, of FALSE on error
     */
    public static function proceed($url, $headers = '', $data = '', $cookie = '') {
        global $context;

        // target content
        $body = FALSE;

        // advanced and optimized download
        if (is_callable('curl_init'))
            $body = http::proceed_using_curl($url, $headers, $data, $cookie);

        // plan B, in case curl has not done the job properly
        if (!$body)
            $body = http::proceed_natively($url, $headers, $data, $cookie);

        // compensate for network time
        Safe::set_time_limit(30);

        // call http::get_headers() to get meta information returned by the server
        return $body;
    }

    /**
     * check that a link uses a web protocol
     *
     * Other schemes, such as file:// or gopher://, could reach local resources.
     *
     * @param string the link to check
     * @return boolean FALSE if the link asks for another protocol than http:// or https://
     */
    public static function is_web_url($url) {
        if (!is_string($url) || (($items = parse_url(trim($url))) === FALSE))
            return FALSE;

        // no scheme at all, as in www.example.com -- curl is limited to web protocols anyway
        if (!isset($items['scheme']))
            return TRUE;

        $scheme = strtolower($items['scheme']);
        return ($scheme == 'http') || ($scheme == 'https');
    }

    /**
     * list network addresses of a host
     *
     * @param string the host name, or an IPv4 or IPv6 address
     * @return array IPv4 and IPv6 addresses, empty if the host is unknown
     */
    public static function get_addresses($host) {

        // [::1] in a link is ::1 for the network
        $host = trim((string)$host, '[]');
        if (!$host)
            return array();

        // already an address
        if (filter_var($host, FILTER_VALIDATE_IP))
            return array($host);

        // IPv4 addresses, including alternate forms such as 2130706433 or 127.1
        $addresses = array();
        if (is_callable('gethostbynamel') && ($items = @gethostbynamel($host)))
            $addresses = $items;

        // IPv6 addresses
        if (is_callable('dns_get_record') && ($records = @dns_get_record($host, DNS_AAAA))) {
            foreach ($records as $record) {
                if (isset($record['ipv6']) && filter_var($record['ipv6'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
                    $addresses[] = $record['ipv6'];
            }
        }

        return array_values(array_unique($addresses));
    }

    /**
     * check that a network address is routed on the public Internet
     *
     * Loopback, private, link-local, shared, multicast and reserved ranges are refused,
     * as well as IPv6 forms that carry such an IPv4 address.
     *
     * @param string an IPv4 or IPv6 address
     * @return boolean TRUE if the address is public, FALSE otherwise
     */
    public static function is_public_address($address) {

        if (!filter_var($address, FILTER_VALIDATE_IP) || (($packed = @inet_pton($address)) === FALSE))
            return FALSE;

        // IPv4-mapped (::ffff:a.b.c.d) and NAT64 (64:ff9b::a.b.c.d) addresses carry an IPv4 address
        if (strlen($packed) == 16) {
            $prefix = substr($packed, 0, 12);
            if (($prefix === str_repeat("\0", 10) . "\xff\xff") || ($prefix === "\x00\x64\xff\x9b" . str_repeat("\0", 8)))
                return http::is_public_address(inet_ntop(substr($packed, 12)));
        }

        // ranges that are not reachable from the Internet, or that should not be asked for
        static $ranges = array(
            '0.0.0.0/8', '10.0.0.0/8', '100.64.0.0/10', '127.0.0.0/8', '169.254.0.0/16', '172.16.0.0/12',
            '192.0.0.0/24', '192.0.2.0/24', '192.168.0.0/16', '198.18.0.0/15', '198.51.100.0/24',
            '203.0.113.0/24', '224.0.0.0/4', '240.0.0.0/4',
            '::/96', '2001:db8::/32', '2002::/16', 'fc00::/7', 'fe80::/10', 'fec0::/10', 'ff00::/8');

        foreach ($ranges as $range) {
            list($network, $bits) = explode('/', $range);
            $network = inet_pton($network);
            if (strlen($network) != strlen($packed))
                continue;

            // compare whole bytes, then the remaining bits
            $bytes = intdiv((int)$bits, 8);
            if (substr($packed, 0, $bytes) !== substr($network, 0, $bytes))
                continue;
            if ($rest = (int)$bits % 8) {
                $mask = (0xff << (8 - $rest)) & 0xff;
                if ((ord($packed[$bytes]) & $mask) != (ord($network[$bytes]) & $mask))
                    continue;
            }
            return FALSE;
        }

        return TRUE;
    }

    /**
     * check the target of an outbound request
     *
     * Requests are sent only to hosts that resolve to public addresses, so that a link
     * provided by a visitor cannot reach the server itself or the local network.
     * The returned address has to be used for the connection, else another name
     * resolution could lead somewhere else.
     *
     * @param string the host to connect to
     * @return string the address to connect to, or FALSE if the host is not allowed
     */
    public static function get_public_address($host) {
        global $http_internal_error;

        // every address has to be public, since any of them could be used
        $addresses = http::get_addresses($host);
        foreach ($addresses as $address) {
            if (!http::is_public_address($address)) {
                $http_internal_error = 'Address ' . $address . ' of ' . $host . ' is not allowed';
                return FALSE;
            }
        }

        if (!$addresses) {
            $http_internal_error = 'Unknown host ' . $host;
            return FALSE;
        }

        // prefer IPv4, which every server can reach
        foreach ($addresses as $address) {
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
                return $address;
        }
        return $addresses[0];
    }

    /**
     * build an absolute link from a Location header
     *
     * @param string the link that has been fetched
     * @param string the value of the Location header
     * @return string an absolute link
     */
    public static function resolve_location($url, $location) {

        $location = trim($location);

        // already an absolute link
        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $location))
            return $location;

        $items = parse_url($url);
        $scheme = isset($items['scheme']) ? $items['scheme'] : 'http';

        // same scheme, another host
        if (strncmp($location, '//', 2) == 0)
            return $scheme . ':' . $location;

        $base = $scheme . '://' . $items['host'] . (isset($items['port']) ? ':' . $items['port'] : '');

        // same host, absolute path
        if (strncmp($location, '/', 1) == 0)
            return $base . $location;

        // same host, relative path
        $path = isset($items['path']) ? $items['path'] : '/';
        return $base . substr($path, 0, strrpos($path, '/') + 1) . $location;
    }

    /**
     * add the missing scheme to a link
     *
     * Links are often saved without a scheme, as in www.example.com.
     * A path alone, as in /index.php, designates this server.
     *
     * @param string the link to fetch
     * @return string a link that starts with http:// or https://
     */
    public static function with_scheme($url) {
        global $context;

        $url = trim($url);
        if (preg_match('/^https?:\/\//i', $url))
            return $url;

        if (strncmp($url, '//', 2) == 0)
            return 'http:' . $url;

        if (strncmp($url, '/', 1) == 0)
            return 'http://' . $context['host_name'] . $url;

        return 'http://' . $url;
    }

    /**
     * handle HTTP natively
     *
     * This function only requires the network library.
     * It accomodates SSL/TLS requests, and chunked coding.
     *
     * Provided data will be POSTed to the target url, else GET is used.
     * If data is an array of named values, it is encoded as x-www-urlform-encoded before sending.
     *
     * @todo add proxy support to fetch_directly()
     *
     * @param string the link to fetch
     * @param array of strings optional headers (eg, 'array("Content-Type: text/xml")')
     * @param mixed optional data to send
     * @param string cookie, if any
     * @param int to manage a maximum number of redirections
     * @return the actual content, of FALSE on error
     */
    public static function proceed_natively($url, $headers = '', $data = '', $cookie = '', $limit = 3) {
        global $context;

        // only web protocols are allowed, including after a redirection
        if (!http::is_web_url($url)) {
            $GLOBALS['http_internal_error'] = 'Only http:// and https:// links are allowed';
            return FALSE;
        }

        // outbound web is not authorized
        if (isset($context['without_outbound_http']) && ($context['without_outbound_http'] == 'Y')) {
            if ($context['with_debug'] == 'Y')
                Logger::remember('shared/http.php: Outbound HTTP is not authorized.', '', 'debug');
            return FALSE;
        }

        // no headers yet
        global $http_fetch_headers;
        $http_fetch_headers = array();

        // remember errors, if any
        global $http_internal_error;
        $http_internal_error = '';

        // parse this url
        $url = http::with_scheme($url);
        if ((!$items = parse_url($url)) || empty($items['host'])) {
            $http_internal_error = 'Invalid link ' . $url;
            return FALSE;
        }
        $scheme = strtolower($items['scheme']);

        // sometime parse_url() adds a '_'
        $host = rtrim($items['host'], '_');

        // only public addresses, and the very one that has been checked
        if (!$address = http::get_public_address($host)) {
            if ($context['with_debug'] == 'Y')
                Logger::remember('shared/http.php: ' . $http_internal_error, $url, 'debug');
            return FALSE;
        }

        // headers of the caller, to be sent again after a redirection
        $caller_headers = $headers;

        if (is_array($headers))
            $headers = implode(CRLF, $headers) . CRLF;

        // set target host
        if (strpos($headers, 'Host: ') === FALSE)
            $headers .= 'Host: ' . $host . CRLF;

        // set user agent
        if (strpos($headers, 'User-Agent: ') === FALSE)
            $headers .= 'User-Agent: yacs' . CRLF;

        // no port, assume the standard
        if (isset($items['port']) && $items['port'])
            $port = $items['port'];
        elseif ($scheme == 'https')
            $port = 443;
        else
            $port = 80;

        // build the path
        $path = (isset($items['path']) && $items['path'])?$items['path']:'/';

        // sometime parse_url() adds a '_'
        $path = rtrim($path, '_');

        // include any query
        if (isset($items['query']) && $items['query'])
            $path .= '?' . $items['query'];

        // encode provided data, if any
        if (is_array($data) && count($data)) {
            $items = array();
            foreach ($data as $name => $value)
                $items[] = urlencode($name) . '=' . urlencode($value);
            $data = implode('&', $items);
        }

        // remember encoding in meta-data
        if ($data && (strpos($headers, 'Content-Type: ') === FALSE))
            $headers .= "Content-Type: application/x-www-form-urlencoded; charset=UTF-8" . CRLF;

        // remember content length in meta-data
        if ($data && (strpos($headers, 'Content-Length: ') === FALSE))
            $headers .= "Content-Length: " . strlen($data) . CRLF;

        // set request date, RFC822 format
        if (strpos($headers, 'Date: ') === FALSE)
            $headers .= 'Date: ' . gmdate('D, d M Y H:i:s T') . CRLF;

        // append cookies, if any
        if (is_array($cookie))
            $headers .= 'Cookie: ' . join('; ', $cookie) . CRLF;
        elseif ($cookie)
            $headers .= 'Cookie: ' . $cookie . CRLF;

        // pool of connections
        static $handles;
        if (!isset($handles))
            $handles = array();

        // no handle yet
        $handle = NULL;

        // reuse an existing connection to this server socket
        $pool = $scheme . '://' . $host;
        if (isset($handles[$pool][$port]) && $handles[$pool][$port]) {
            $handle = $handles[$pool][$port];

            // drop the connection if closed
            if (!$status = stream_get_meta_data($handle))
                $handle = $handles[$pool][$port] = NULL;
            elseif ((isset($status['timedout']) && $status['timedout']) || (isset($status['eof']) && $status['eof']))
                $handle = $handles[$pool][$port] = NULL;
        }

        // open a network connection to the checked address -- wait for up to 10 seconds for the TCP connection
        if (!$handle) {
            $target = (strpos($address, ':') !== FALSE) ? '[' . $address . ']' : $address;
            $target = (($scheme == 'https') ? 'ssl://' : 'tcp://') . $target . ':' . $port;

            // the certificate is checked against the host name, not against the address
            $options = array('ssl' => array('peer_name' => $host, 'verify_peer' => TRUE, 'verify_peer_name' => TRUE));

            if (!is_callable('stream_socket_client')
                || (!$handle = @stream_socket_client($target, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, stream_context_create($options)))) {
                $http_internal_error = sprintf('Impossible to connect to %s.', $host . ':' . $port);
                if ($context['with_debug'] == 'Y')
                    Logger::remember('shared/http.php: ' . $http_internal_error, '', 'debug');
                return FALSE;
            }

            // stop network transfers on 7 second time-out
            stream_set_timeout($handle, 7);
        }

        // ensure enough execution time
        Safe::set_time_limit(30);

        // build an HTTP request
        if ($data)
            $request = 'POST';
        else
            $request = 'GET';
        $request .= ' ' . $path . " HTTP/1.1" . CRLF;

        // use the connection pool
        $request .= "Connection: keep-alive" . CRLF;

        // enable compression
//		if(is_callable('gzinflate'))
//			$request .= "Accept-Encoding: gzip".CRLF;
        // finalize the request
        $request .= $headers . CRLF . $data;
        if ($context['with_debug'] == 'Y')
            Logger::remember('shared/http.php: http request', $request, 'debug');

        // submit the request
        fwrite($handle, $request);

        // read HTTP status
        if (($status = fgets($handle, 1024)) === FALSE) {
            $http_internal_error = 'Impossible to get HTTP status from ' . $url;
            return FALSE;
        }

        // ensure we have a valid HTTP status line
        if (!preg_match('/^HTTP\/[0-9\.]+ (\d\d\d) /', $status, $matches)) {
            $http_internal_error = 'Unexpected HTTP status "' . $status . '" from ' . $url;
            return FALSE;
        }
        $status_code = (int)$matches[1];

        // read response headers, up to 5120k
        $r_headers = '';
        while (!feof($handle) && (strlen($r_headers) < 5242880)) {

            // one header at a time
            if (!$header = fgets($handle, 10240))
                break;

            // empty line marks end of headers
            if (!trim($header))
                break;

            // conform to curl sequencing
            http::callback_headers('*dummy*', $header);

            // remember this header
            $r_headers .= $header;
        }

        // redirect to another place -- the new link is checked as the first one
        if (($status_code >= 300) && ($status_code < 400) && preg_match('/^Location:\s*(.+)$/mi', $r_headers, $matches)) {

            // the response body is not read, so the connection cannot be reused
            fclose($handle);
            $handles[$pool][$port] = NULL;

            if (--$limit <= 0) {
                $http_internal_error = 'Too many redirections';
                return FALSE;
            }

            // as browsers do, a form is not posted again after 301, 302 or 303
            if (($status_code != 307) && ($status_code != 308))
                $data = '';

            $location = http::resolve_location($url, $matches[1]);
            if ($context['with_debug'] == 'Y')
                Logger::remember('shared/http.php: redirecting to ' . $location, '', 'debug');
            return http::proceed_natively($location, $caller_headers, $data, $cookie, $limit);
        }

        // get content length, if provided in header --maximum is 5120k
        $length = 5242880;
        if (preg_match('/Content-Length:\s+([0-9]+)\s+/', $r_headers, $matches))
            $length = $matches[1];

        // maybe a chunked element -- get size from body
        if (preg_match('/Transfer-Encoding:\s+chunked\s+/', $r_headers) && ($header = fgets($handle, 10240))) {

            // decodes from hexa to decimal
            if (preg_match('/^([0-9a-f]+)/i', $header, $matches))
                $length = hexdec($matches[1]);
        }

        // get everything
        $body = '';
        while (!feof($handle) && ($length > strlen($body)))
            $body .= fread($handle, min($length - strlen($body), 10240));

        // uncompress payload if necessary --sometimes gzinflate produces errors
        if ($body && preg_match('/Content-Encoding: \s*gzip/i', $r_headers) && is_callable('gzinflate'))
            $body = gzinflate(substr($body, 10));

        // server asks for closing
        if (preg_match('/Connection: \s+close\s+/', $r_headers))
            fclose($handle);

        // put in pool for future use
        else
            $handles[$pool][$port] = $handle;

        // log the response
        if ($context['with_debug'] == 'Y')
            Logger::remember('shared/http.php: http response', http::get_headers() . "\n\n" . $body, 'debug');

        // return the fetched object
        return $body;
    }

    /**
     * delegate a web transaction to the CURL library
     *
     * As it name implies, this function requires the library to be available.
     * It inherently supports https.
     * It also supports proxy settings, if any.
     *
     * @param string the link to fetch
     * @param array of strings optional headers (eg, 'array("Content-Type: text/xml")')
     * @param string optional data to send
     * @param string cookie, if any
     * @param int to manage a maximum number of redirections
     * @return the actual content, of FALSE on error
     */
    public static function proceed_using_curl($url, $headers = '', $data = '', $cookie = '', $limit = 3) {
        global $context;

        // only web protocols are allowed, including after a redirection
        if (!http::is_web_url($url)) {
            $GLOBALS['http_internal_error'] = 'Only http:// and https:// links are allowed';
            return FALSE;
        }

        // outbound web is not authorized
        if (isset($context['without_outbound_http']) && ($context['without_outbound_http'] == 'Y')) {
            if ($context['with_debug'] == 'Y')
                Logger::remember('shared/http.php: Outbound HTTP is not authorized.', '', 'debug');
            return FALSE;
        }

        // sanity check
        if (!is_callable('curl_init')) {
            if ($context['with_debug'] == 'Y')
                Logger::remember('shared/http.php: CURL is not implemented"', '', 'debug');
            return FALSE;
        }

        // no headers yet
        global $http_fetch_headers;
        $http_fetch_headers = array();

        // remember errors, if any
        global $http_internal_error;
        $http_internal_error = '';

        // parse this url
        $url = http::with_scheme($url);
        if ((!$items = parse_url($url)) || empty($items['host'])) {
            $http_internal_error = 'Invalid link ' . $url;
            return FALSE;
        }
        if (!isset($items['path']) || !$items['path'])
            $url .= '/';

        // only public addresses, and the very one that has been checked
        $host = rtrim($items['host'], '_');
        if (!$address = http::get_public_address($host)) {
            if ($context['with_debug'] == 'Y')
                Logger::remember('shared/http.php: ' . $http_internal_error, $url, 'debug');
            return FALSE;
        }

        // create a new curl resource
        $handle = curl_init();

        // set URL and other appropriate options
        curl_setopt($handle, CURLOPT_URL, $url);

        // only web protocols, including after a redirection
        curl_setopt($handle, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($handle, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);

        // redirections are followed below, so that each new link is checked as the first one
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, FALSE);

        // set timeouts
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);

        // pass through a proxy, maybe
        if (isset($context['proxy_server']) && $context['proxy_server']) {
            curl_setopt($handle, CURLOPT_PROXY, $context['proxy_server']);

            // authenticate to the proxy
            if (isset($context['proxy_user']) && $context['proxy_user']) {
                curl_setopt($handle, CURLOPT_PROXYUSERPWD, $context['proxy_user'] . ':' . $context['proxy_password']);
            }

        // connect to the checked address, without another name resolution
        } else {
            if (isset($items['port']) && $items['port'])
                $port = $items['port'];
            elseif (strtolower($items['scheme']) == 'https')
                $port = 443;
            else
                $port = 80;
            $pinned = (strpos($address, ':') !== FALSE) ? '[' . $address . ']' : $address;
            curl_setopt($handle, CURLOPT_RESOLVE, array($host . ':' . $port . ':' . $pinned));
        }

        // let CURL adapt to the encoding
        curl_setopt($handle, CURLOPT_ENCODING, '');

        // check the certificate of the server, and its name
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);

        // parse the returned error code as well
        curl_setopt($handle, CURLOPT_FAILONERROR, TRUE);

        // add cookie information, if any
        if ($cookie)
            curl_setopt($handle, CURLOPT_COOKIE, $cookie);

        // set headers, if any
        if (isset($headers) && is_array($headers))
            curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);

        // encode provided data, if any
        if (is_array($data) && count($data)) {
            $items = array();
            foreach ($data as $name => $value)
                $items[] = urlencode($name) . '=' . urlencode($value);
            $data = implode('&', $items);
        }

        // finalize the HTTP request
        if ($data) {
            curl_setopt($handle, CURLOPT_HTTPHEADER, Array('Content-type: application/x-www-form-urlencoded'));
            curl_setopt($handle, CURLOPT_POST, TRUE);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
        }

        // we would like to look at headers by ourselves
        curl_setopt($handle, CURLOPT_HEADER, FALSE);
        curl_setopt($handle, CURLOPT_HEADERFUNCTION, array('http', 'callback_headers'));

        // of course we need the content
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);

        // do the job
        $response = curl_exec($handle);

        // remember errors, if any
        if (curl_errno($handle) == 22)
            $http_internal_error = 'HTTP server returns an error code that is >= 400';
        elseif (curl_error($handle))
            $http_internal_error = curl_error($handle) . ' ' . curl_errno($handle);

        // a redirection to follow
        $status_code = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $location = ($status_code >= 300) && ($status_code < 400) ? curl_getinfo($handle, CURLINFO_REDIRECT_URL) : '';

        // close curl resource, and free up system resources
        curl_close($handle);

        // follow the redirection -- the new link is checked as the first one
        if ($location) {
            if (--$limit <= 0) {
                $http_internal_error = 'Too many redirections';
                return FALSE;
            }

            // as browsers do, a form is not posted again after 301, 302 or 303
            if (($status_code != 307) && ($status_code != 308))
                $data = '';

            if ($context['with_debug'] == 'Y')
                Logger::remember('shared/http.php: redirecting to ' . $location, '', 'debug');
            return http::proceed_using_curl($location, $headers, $data, $cookie, $limit);
        }

        // return the entire reply
        return $response;
    }

    /**
     * validate data cached by user agent
     *
     * @param string the date of last modification, as per HTTP specification
     * @param string the opaque string characterizing the target object
     * @return boolean TRUE if the client has provided the right headers, FALSE otherwise
     */
    public static function validate($last_modified, $etag = NULL) {

        // not cached yet
        $cached = FALSE;

        // web cache is not managed
        if (isset($context['without_http_cache']) && ($context['without_http_cache'] == 'Y'))
            return FALSE;

        // validate the content if date of last modification is the same
        if ($last_modified && isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && ($if_modified_since = preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']))) {
            if (($if_modified_since == $last_modified) && !isset($_SERVER['HTTP_IF_NONE_MATCH']))
                $cached = TRUE;
        }

        // validate the content if hash is ok
        if ($etag && isset($_SERVER['HTTP_IF_NONE_MATCH']) && is_array($if_none_match = explode(',', str_replace('\"', '"', $_SERVER['HTTP_IF_NONE_MATCH'])))) {
            foreach ($if_none_match as $target) {
                if (trim($target) == $etag) {
                    $cached = TRUE;
                    $break;
                }
            }
        }

        // client has the right data
        if ($cached)
            Safe::header('Status: 304 Not Modified', TRUE, 304);

        // else inform the browser
        else {

            // set the date of last modification
            if ($last_modified)
                Safe::header('Last-Modified: ' . $last_modified);

            // set the opaque string for this object
            if ($etag)
                Safe::header('ETag: ' . $etag);
        }

        // what we have found
        return $cached;
    }

}

?>
