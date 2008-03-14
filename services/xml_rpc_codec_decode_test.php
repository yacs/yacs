<?php
/**
 * command line test program
 *
 * usage: php.exe -q xml_rpc_codec_test.php <sample_file.xml>
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 * @reference
 */
include_once '../shared/logger.php';

// load the adequate codec
include_once 'codec.php';
include_once 'xml_rpc_codec.php';
$codec =& new xml_rpc_Codec();

// load some xml
//$xml = Safe::file_get_contents('xml-rpc/blogger.getUserInfo.request.xml');
//$xml = Safe::file_get_contents('xml-rpc/blogger.getUserInfo.response.xml');
//$xml = Safe::file_get_contents('xml-rpc/blogger.newPost.fault.xml');
//$xml = Safe::file_get_contents('xml-rpc/blogger.newPost.request.xml');
//$xml = Safe::file_get_contents('xml-rpc/blogger.newPost.response.xml');
//$xml = Safe::file_get_contents('xml-rpc/blogger.getUsersBlogs.request.xml');
//$xml = Safe::file_get_contents('xml-rpc/blogger.getUsersBlogs.response.xml');
//$xml = Safe::file_get_contents('xml-rpc/blogger.getUsersBlogs.response.2.xml');
//$xml = Safe::file_get_contents('xml-rpc/metaWeblog.newPost.request.xml');
$xml = Safe::file_get_contents('xml-rpc/metaWeblog.newPost.request.2.xml');
//$xml = Safe::file_get_contents('xml-rpc/getTemplate.response.xml');
//echo "Request:\n".$xml."\n";

if(!trim($xml))
	return;

// parse parameters
//$result = $codec->decode($xml);
$result = $codec->import_request($xml);
//$result = $codec->import_response($xml);
$status = @$result[0];
$parameters = @$result[1];

// if it was a HEAD request, stop here
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// parse has failed
if(!$status)
	echo "Error: ".$parameters.BR;

// display the message
else
	var_dump($parameters);

?>