<?php
/**
 * command line test program
 *
 * usage: php.exe -q xml_rpc_codec_test.php <sample_file.xml>
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// load the adequate codec
include_once 'codec.php';
include_once 'xml_rpc_codec.php';
$codec =& new xml_rpc_Codec();

$values = $codec->encode('hello world', 'string');
//$values = array('hello world', $codec->encode('hello world', 'base64'), $codec->encode(time(), 'date'));
//$values = array('faultCode' => 123, 'faultString' => 'hello world');

//$result = $codec->export_request('subject.action', $values);
$result = $codec->export_response($values);

// actual transmission except on a HEAD request
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// parse has failed
if(!$result[0])
	echo "Error: ".$result[1];

// display the message
else
	echo $result[1]."\n";

?>