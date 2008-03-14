<?php
/**
 * web service encoder and decoder
 *
 * @see services/codec.php
 * @see services/xml_rpc.php
 *
 * @link http://www.xmlrpc.com/spec XML-RPC Specification
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class XML_RPC_Codec extends Codec {

	/**
	 * parse a XML request according to the XML-RPC specification
	 *
	 * This script uses the standard XML parser included in the PHP library.
	 * The objective of the decoding functions is to transform the XML tree into stemming PHP arrays.
	 *
	 * Following tags are used for cdata conversion
	 * - &lt;base64&gt;
	 * - &lt;boolean&gt;
	 * - &lt;date&gt;
	 * - &lt;double&gt;
	 * - &lt;integer&gt;
	 * - &lt;string&gt;
	 *
	 * Following tags are processed as leaves of the tree:
	 * - &lt;/value&gt;
	 * - &lt;/methodName&gt;
	 *
	 * Following tags are processed as nodes of the tree
	 * - &lt;methodCall&gt;: push 'methodCall' (stems 'methodName' and 'params')
	 * - &lt;/methodCall&gt;: pop 'methodCall'
	 * - &lt;methodResponse&gt;: push 'methodResponse' (stem 'params' or 'fault')
	 * - &lt;/methodResponse&gt;: pop 'methodResponse'
	 * - &lt;fault&gt;: push 'fault' (stems 'faultCode' and 'faultString')
	 * - &lt;/fault&gt;: pop 'fault'
	 * - &lt;params&gt;: push 'params', then '-1' (list of anonymous stems)
	 * - &lt;/params&gt;: pop index, then pop 'params'
	 * - &lt;value&gt; under an index: increment index (works for &lt;params&gt; and for &lt;array&gt;)
	 * - &lt;/name&gt;: push cdata (named stem)
	 * - &lt;/member&gt;: pop cdata
	 * - &lt;array&gt;: push '-1' (list of anonymous stems)
	 * - &lt;/array&gt;: pop index
	 *
	 * @param array the received $HTTP_RAW_POST_DATA
	 * @return array a status code (TRUE is ok) and the parsing result
	 */
	function decode($data) {

		// create a parser
		$parser = xml_parser_create();
		xml_set_object($parser, $this);
		xml_set_element_handler($parser, 'parse_tag_open', 'parse_tag_close');
		xml_set_character_data_handler($parser, 'parse_cdata');

		// case is meaningful
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, FALSE);

		// parse data
		$this->result = array();
		$this->stack = array();
		if(!xml_parse($parser, $data)) {

			if(isset($context['with_debug']) && ($context['with_debug'] == 'Y'))
				Logger::remember('services/xml_rpc_codec.php', 'invalid packet to decode', str_replace("\r\n", "\n", $data), 'debug');

			return array(FALSE, 'Parsing error: '.xml_error_string(xml_get_error_code($parser))
				.' at line '.xml_get_current_line_number($parser));
		}
		xml_parser_free($parser);

		// return parsing result
		return array(TRUE, $this->result);
	}

	/**
	 * encode some PHP value into XML
	 *
	 * This function tries to guess the adequate type to use.
	 * Following types are supported:
	 * - array
	 * - base64 - type has to be set explicitly for the encoding to take place
	 * - boolean
	 * - date - type has to be set explicitly to get a &lt;dateTime.iso8601> element
	 * - double
	 * - integer
	 * - struct
	 * - string - type has to be set explicitly for the encoding to take place
	 *
	 * @param mixed the parameter to encode
	 * @param type, if any
	 * @return some XML
	 */
	function encode($parameter, $type='') {

		// a date
		if($type == 'date') {
			if(is_string($parameter))
				$parameter = strtotime($parameter);
			$items = getdate($parameter);
			return '<dateTime.iso8601>'.sprintf('%02d%02d%02dT%02d:%02d:%02d', $items['year'], $items['mon'], $items['mday'], $items['hours'], $items['minutes'], $items['seconds']).'</dateTime.iso8601>';
		}

		// base 64
		if($type == 'base64')
			return '<base64>'.base64_encode($parameter).'</base64>';

		// a string --also fix possible errors in HTML image references
		if($type == 'string')
			return '<string>'.encode_field(trim(preg_replace('|<img (.+?[^/])>|mi', '<img $1 />', $parameter))).'</string>';

		// a boolean
		if($parameter === true || $parameter === false)
			return '<boolean>'.(($parameter) ? '1' : '0').'</boolean>';

		// an integer
		if(is_integer($parameter))
			return '<int>'.$parameter.'</int>';

		// a double
		if(is_double($parameter))
			return '<double>'.$parameter.'</double>';

		// an array
		if(is_array($parameter)) {

			// it's a struct
			if(key($parameter)) {

				$text = '';
				foreach($parameter as $name => $value)
					$text .= "\t".'<member><name>'.$name.'</name><value>'.XML_RPC_Codec::encode($value)."</value></member>\n";
				return '<struct>'."\n".$text.'</struct>';

			// else it's a plain array
			} else {
				$text = '';
				foreach($parameter as $item)
					$text .= "\t".'<value>'.XML_RPC_Codec::encode($item)."</value>\n";
				if($text)
					return '<array><data>'."\n".$text.'</data></array>';
				else
					return '<array><data /></array>';
			}
		}

		// encode strings
		if(is_string($parameter) && ($parameter = trim($parameter)) && (substr($parameter, 0, 1) != '<'))
			return '<string>'.$parameter.'</string>';

		// do not encode possibly encoded strings
		return $parameter;
	}

	/**
	 * build a request according to the XML-RPC specification
	 *
	 * Example:
	 * [php]
	 * $service = 'search';
	 * $parameter = array( 'search' => $_REQUEST['search']);
	 * $result = $codec->export_request($service, $parameter);
	 * if(!$result[0])
	 * 	echo $result[1]; // print error code
	 * else
	 * 	... // send xml data from $result[1] to the remote web server
	 * [/php]
	 *
	 * Resulting xml:
	 * [snippet]
	 * <?xml version="1.0"?>
	 * <methodCall>
	 * <methodName>search</methodName>
	 * <params>
	 * <param><value><struct>
	 * 	<member><name>search</name><value><string>...</string></value></member>
	 * </struct></value></param>
	 * </params>
	 * </methodCall>
	 * [/snippet]
	 *
	 * @param string name of the remote service
	 * @param mixed transmitted parameters, if any
	 * @return an array of which the first value indicates call success or failure
	 * @see services/codec.php
	 */
	function export_request($service, $parameters = NULL) {

		// xml header
		$xml = '<?xml version="1.0"?>'."\n"
			.'<methodCall>'."\n"
			.'<methodName>'.$service.'</methodName>'."\n"
			.'<params>'."\n";

		// encode values only
		if(is_array($parameters))
			foreach($parameters as $parameter)
				$xml .= '<param><value>'.XML_RPC_Codec::encode($parameter)."</value></param>\n";
		else
			$xml .= '<param><value>'.XML_RPC_Codec::encode($parameters)."</value></param>\n";

		// xml tail
		$xml .= '</params></methodCall>';

		// xml tail
		return array(TRUE, $xml);

	}

	/**
	 * build a response according to the XML-RPC specification
	 *
	 * Example to generate a response with a single string:
	 * [php]
	 * $value = $codec->encode('hello world', 'string');
	 * $result = $codec->export_response($value);
	 * [/php]
	 *
	 * Resulting xml:
	 * [snippet]
	 * <?xml version="1.0"?>
	 * <methodResponse>
	 * <params>
	 * <param><value><string>hello world</string></value></param>
	 * </params>
	 * </methodResponse>
	 * [/snippet]
	 *
	 * Example to generate an error response:
	 * [php]
	 * $values = array('faultCode' => 123, 'faultString' => 'hello world');
	 * $result = $codec->export_response($values);
	 * [/php]
	 *
	 * Resulting xml:
	 * [snippet]
	 * <?xml version="1.0"?>
	 * <methodResponse>
	 * <fault>
	 * <value><struct>
	 * 	<member><name>faultCode</name><value><int>...</int></value></member>
	 * 	<member><name>faultString</name><value>...</value></member>
	 * </struct></value>
	 * </fault>
	 * </methodResponse>
	 * [/snippet]
	 *
	 * @param mixed transmitted values, if any
	 * @param string name of the remote service, if any
	 * @return an array of which the first value indicates call success or failure
	 * @see services/codec.php
	 */
	function export_response($values=NULL, $service=NULL) {

		// request header
		$xml = '<methodResponse>'."\n";

		// encode the response
		if(is_array($values) && isset($values['faultCode']) && $values['faultCode'])
			$xml .= '<fault><value>'.XML_RPC_Codec::encode($values).'</value></fault>';
		else
			$xml .= '<params><param><value>'.XML_RPC_Codec::encode($values).'</value></param></params>';

		// request tail
		$xml .= '</methodResponse>';

		// request tail
		return array(TRUE, $xml);

	}

	/**
	 * parse a XML request according to the XML-RPC specification
	 *
	 * @param array the received $HTTP_RAW_POST_DATA
	 * @return an array of which the first value indicates call success or failure
	 */
	function import_request($data) {

		// parse the input packet
		$result = $this->decode($data);
		$status = @$result[0];
		$values = @$result[1];
		if(!$status)
			return array($status, $values);

		// ensure we have a valid request
		if(!isset($values['methodCall']) || !($values = $values['methodCall']))
			return array(FALSE, 'no methodCall');
		if(!isset($values['methodName']) || !$values['methodName'])
			return array(FALSE, 'no methodName');

		// return methodName and params
		return array(TRUE, $values);
	}

	/**
	 * decode a XML response
	 *
	 * @param string the received HTTP body
	 * @param string the received HTTP headers
	 * @param mixed the submitted parameters
	 * @return an array of which the first value indicates call success or failure
	 */
	function import_response($data, $headers=NULL, $parameters=NULL) {

		// parse the input packet
		$result = $this->decode($data);
		$status = @$result[0];
		$values = @$result[1];
		if(!$status)
			return array($status, $values);

		// ensure we have a valid response
		if(!$values = $values['methodResponse'])
			return array(FALSE, 'no methodResponse');
		if(isset($values['params']) && is_array($values['params']) && (count($values['params']) == 1))
			return array(TRUE, $values['params'][0]);
		if(isset($values['params']) && is_array($values['params']))
			return array(TRUE, $values['params']);
		if(isset($values['fault']) && is_array($values['fault']))
			return array(TRUE, $values['fault']);
		return array(FALSE, 'no params nor fault');
	}


	var $stack;

	/**
	 * update the stack on opening tags
	 *
	 * Following tags are processed as nodes of the tree
	 * - &lt;methodCall&gt;: push 'methodCall' (stems 'methodName' and 'params')
	 * - &lt;methodResponse&gt;: push 'methodResponse' (stem 'params')
	 * - &lt;fault&gt;: push 'fault' (stems 'faultCode' and 'faultString')
	 * - &lt;params&gt;: push 'params', then push '-1' (list of anonymous stems)
	 * - &lt;array&gt;: push '-1' (list of anonymous stems)
	 * - &lt;value&gt; under an index: increment index (works for &lt;params&gt; and for &lt;array&gt;)
	 *
	 */
	function parse_tag_open($parser, $tag, $attributes) {
		if(preg_match('/^(methodCall|methodResponse|fault)$/', $tag)) {
			array_push($this->stack, $tag);
		} elseif($tag == 'array') {
			array_push($this->stack, -1);
		} elseif($tag == 'params') {
			array_push($this->stack, 'params');
			array_push($this->stack, -1);
		} elseif($tag == 'value') {
			// if we are in an array, increment the element index
			if(is_int($this->stack[count($this->stack)-1])) {
				$index = array_pop($this->stack);
				array_push($this->stack, ++$index);
			}
		}
	}

	var $cdata;

	/**
	 * capture cdata for further processing
	 */
	function parse_cdata($parser, $cdata) {

		// preserve embedded hard coded new lines
		if(isset($this->cdata))
			$this->cdata = ltrim($this->cdata.$cdata);
		else
			$this->cdata = ltrim($cdata);
	}

	var $result;

	var $name;

	/**
	 * update the stack on closing tags
	 *
	 * The value of cdata is converted explicitly on following tags:
	 * - &lt;/base64&gt;
	 * - &lt;/boolean&gt;
	 * - &lt;/date&gt;
	 * - &lt;/double&gt;
	 * - &lt;/integer&gt;
	 * - &lt;/string&gt;
	 *
	 * The result is updated on following tags:
	 * - &lt;/value&gt;
	 * - &lt;/methodName&gt;
	 *
	 * The stack is updated on following tags:
	 * - &lt;/methodCall&gt;: pop 'methodCall'
	 * - &lt;/methodResponse&gt;: pop 'methodResponse'
	 * - &lt;/fault&gt;: pop 'fault'
	 * - &lt;/params&gt;: pop index, then pop 'params'
	 * - &lt;/name&gt;: push cdata (named stem)
	 * - &lt;/member&gt;: pop cdata
	 * - &lt;/array&gt;: pop index
	 *
	 */
	function parse_tag_close($parser, $tag) {
		global $context;

		if(isset($this->cdata) && is_string($this->cdata))
			$this->cdata = trim($this->cdata);

		// expand the stack
		if($tag == 'name') {
			array_push($this->stack, $this->cdata);
			unset($this->cdata);
			return;
		}

		// convert cdata
		switch($tag) {
		case 'base64':
			$this->cdata = base64_decode($this->cdata);
			return;
		case 'boolean':
			if(preg_match('/^(1|true)$/i', $this->cdata))
				$this->cdata = TRUE;
			else
				$this->cdata = FALSE;
			return;
		case 'dateTime.iso8601':
			$value = (string)$this->cdata;
			$year = (int)substr($value, 0, 4);
			$month = (int)substr($value, 4, 2);
			$day = (int)substr($value, 6, 2);
			$hour = (int)substr($value, 9, 2);
			$minute = (int)substr($value, 12, 2);
			$second = (int)substr($value, 15, 2);
			$this->cdata = mktime($hour, $minute, $second, $month, $day, $year );
			return;
		case 'double':
			$this->cdata = (double)$this->cdata;
			return;
		case 'i4':
		case 'int':
			$this->cdata = (int)$this->cdata;
			return;
		case 'string':

			// transcode to our internal charset, if unicode
			if($context['charset'] == 'utf-8')
				$this->cdata = utf8::to_unicode($this->cdata);

			return;
		}

		// sanity check
		if(!isset($this->cdata))
			$this->cdata = '';

		// update the result tree
		if(($tag == 'value') || ($tag == 'methodName')){

			// browse containers
			$here =& $this->result;
			$container_id = NULL;
			foreach($this->stack as $container_id) {
				if(!isset($here[$container_id]))
					$here[$container_id] = array();
				$here =& $here[$container_id];
			}

			// update a leaf
			if($tag == 'value' && !@count($here)) {
				$here = $this->cdata;
				unset($this->cdata);
			} elseif($tag == 'methodName') {
				$here[$tag] = $this->cdata;
				unset($this->cdata);
			}
		}

		// update the stack
		if(preg_match('/^(array|fault|member|methodCall|methodResponse)$/', $tag)) {
			array_pop($this->stack);
		} elseif($tag == 'params') {
			array_pop($this->stack);
			array_pop($this->stack);
		}
	}

}

?>