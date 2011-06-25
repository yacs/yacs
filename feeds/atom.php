<?php
/**
 * provide news in the atom format
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load the rendering skin
load_skin('feeds');

// get the list from the cache, if possible
$cache_id = Cache::hash('feeds/atom').'.xml';

// save for 5 minutes
if(!file_exists($context['path_to_root'].$cache_id) || (filemtime($context['path_to_root'].$cache_id)+300 < time()) || (!$text = Safe::file_get_contents($context['path_to_root'].$cache_id))) {
	$text = '';

	// loads feeding parameters
	Safe::load('parameters/feeds.include.php');

	// set default values
	if(!isset($context['channel_title']) || $context['channel_title'])
		$context['channel_title'] = $context['site_name'];
	if(!isset($context['channel_description']) || !$context['channel_description'])
		$context['channel_description'] = $context['site_description'];
	if(!isset($context['webmaster_address']) || !$context['webmaster_address'])
		$context['webmaster_address'] = $context['site_email'];

	// the preamble
	$text = '<?xml version="1.0" encoding="'.$context['charset'].'" ?>'."\n"
		.'<?xml-stylesheet type="text/css" href="'.$context['url_to_home'].$context['url_to_root'].'feeds/atom.css" ?>'."\n"
		.'<feed'
		.' xml:lang="'.$context['preferred_language'].'"'
		.' xmlns:dc="http://purl.org/dc/elements/1.1/"'
		.' xmlns="http://www.w3.org/2005/Atom">'."\n"
		.'	<title>'.encode_field(strip_tags($context['channel_title'])).'</title>'."\n"
		.'	<subtitle type="text">'.encode_field($context['channel_description']).'</subtitle>'."\n"
		.'	<id>'.encode_field($context['self_url']).'</id>'."\n"
		.'	<link rel="self" href="'.$context['self_url'].'/" />'."\n"
		.'	<link rel="alternate" type="text/html" href="'.$context['url_to_home'].'/" />'."\n"
		.'	<updated>'.gmdate('Y-m-d\TH:i:s\Z').'</updated>'."\n"
		.'	<generator uri="http://www.yacs.fr/">yacs</generator>'."\n";

	if($context['site_owner'])
		$text .= '	<author><name>'.encode_field($context['site_owner']).'</name></author>'."\n";

	// the image for this channel
	if(isset($context['powered_by_image']) && $context['powered_by_image'])
		$text .= '	<icon>'.encode_field($context['url_to_home'].$context['url_to_root'].strip_tags($context['powered_by_image'])).'</icon>'."\n";

	// get local news
	include_once 'feeds.php';
	$rows = Feeds::get_local_news();

	// process rows, if any
	if(is_array($rows)) {

		// for each item
		foreach($rows as $url => $attributes) {
			list($time, $title, $author, $section, $image, $introduction, $description, $trackback) = $attributes;

			// author is a comma-separated list of e-mail_address (name)
			if(preg_match('/\((.+?)\)/', $author, $matches))
				$author = $matches[1];

			// output one story
			$text .= "\n".' <entry>'."\n";

			if($title)
				$text .= '		<title>'.encode_field(strip_tags($title))."</title>\n";

			if($url)
				$text .= '		<link rel="alternate" type="text/html" href="'.str_replace('&', '&amp;', $url).'" />'."\n"
					.'		<id>'.str_replace('&', '&amp;', $url).'</id>'."\n";

			if($author)
				$text .= '		<author><name>'.encode_field($author).'</name></author>'."\n";

			if($introduction)
				$text .= '		<summary type="xhtml"><div xmlns="http://www.w3.org/1999/xhtml">
'.Feeds::encode_text($introduction)."</div></summary>\n";
			else
				$text .= '		<summary type="xhtml"><div xmlns="http://www.w3.org/1999/xhtml">
'."</div></summary>\n";

			if($description)
				$text .= '		<content type="xhtml"><div xmlns="http://www.w3.org/1999/xhtml">
'.Feeds::encode_text($description)."</div></content>\n";

			if(intval($time))
				$text .= '		<updated>'.gmdate('Y-m-d\TH:i:s\Z', intval($time))."</updated>\n";

			if($section)
				$text .= '		<dc:source>'.encode_field($section).'</dc:source>'."\n";

			if($author)
				$text .= '		<dc:creator>'.encode_field($author).'</dc:creator>'."\n";

			$text .= '		<dc:publisher>'.encode_field($context['site_owner']).'</dc:publisher>'."\n"
				.'		<dc:format>text/html</dc:format>'."\n"
				.'		<dc:language>'.$context['preferred_language'].'</dc:language>'."\n"
				.'		<dc:rights>Copyright '.encode_field($context['site_copyright']).'</dc:rights>'."\n"
				."	</entry>\n";

		}
	}

	// the postamble
	$text .= "\n".'</feed>';

	// put in cache
	Safe::file_put_contents($cache_id, $text);

}

//
// transfer to the user agent
//

// handle the output correctly
render_raw('text/xml; charset='.$context['charset']);

// suggest a name on download
if(!headers_sent()) {
	$file_name = utf8::to_ascii($context['site_name'].'.atom.xml');
	Safe::header('Content-Disposition: inline; filename="'.$file_name.'"');
}

// enable 30-minute caching (30*60 = 1800), even through https, to help IE6 on download
http::expire(1800);

// strong validator
$etag = '"'.md5($text).'"';

// manage web cache
if(http::validate(NULL, $etag))
	return;

// actual transmission except on a HEAD request
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $text;

// the post-processing hook
finalize_page();

?>
