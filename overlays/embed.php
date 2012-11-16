<?php
/**
 * embed some object in the page
 *
 * This overlay is aiming to facilitate the sharing of YouTube videos, Slideshare presentations
 * and the like. It captures a web address and translate it to HTML through the oEmbed protocol.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Embed extends Overlay {

	/**
	 * build the list of fields for this overlay
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the hosting attributes
	 * @return a list of ($label, $input, $hint)
	 */
	function get_fields($host) {
		global $context;

		$fields = array();

		// the file itself
		$label = i18n::s('Share');
		$input = '';

		// a surfer is sharing something
		if(!isset($host['id'])) {

			// share a link
			Skin::define_img('EMBED_HREF_IMG', 'thumbnails/video.gif');
			$cell1 = '<div style="text-align: center"><a href="#" onclick="$(\'#share_href\').attr(\'checked\', \'checked\').trigger(\'change\'); return false;">'.EMBED_HREF_IMG.'</a>'
				.BR.'<input type="radio" name="embed_type" id="share_href" value="href" checked="checked" />'.i18n::s('a web address').'</div>';

			// share a file
			$cell2 = '';
			if(Surfer::may_upload()) {
				Skin::define_img('EMBED_UPLOAD_IMG', 'thumbnails/download.gif');
				$cell2 = '<div style="text-align: center"><a href="#" onclick="$(\'#share_upload\').attr(\'checked\', \'checked\').trigger(\'change\'); return false;">'.EMBED_UPLOAD_IMG.'</a>'
					.BR.'<input type="radio" name="embed_type" id="share_upload" value="upload" />'.i18n::s('a file').'</div>';
			}

			// share an idea
			Skin::define_img('EMBED_NONE_IMG', 'thumbnails/information.gif');
			$cell3 = '<div style="text-align: center"><a href="#" onclick="$(\'#share_none\').attr(\'checked\', \'checked\').trigger(\'change\'); return false;">'.EMBED_NONE_IMG.'</a>'
				.BR.'<input type="radio" name="embed_type" id="share_none" value="none" />'.i18n::s('some information').'</div>';

			// three controls in a row
			$input = Skin::layout_horizontally($cell1, $cell2, $cell3);

			// sharing a web address
			$input .= '<div id="embed_a_link" style="padding: 1em 0 1em 0;">'
				.i18n::s('Paste the address of a web page that you have visited').BR
				.'<input type="text" name="embed_href" id="embed_href" size="60" width="100%" value="" maxlength="255" />'
				.'<p class="details">'.sprintf(i18n::s('Some sites are recognized automatically: %s'),
					'<span id="provider_ticker" style="">'
					.'<span>YouTube</span>'
					.'<span>DailyMotion</span>'
					.'<span>Vimeo</span>'
					.'<span>Slideshare</span>'
					.'<span>Scribd</span>'
					.'<span>Flickr</span>'
					.'<span>PhotoBucket</span>'
					.'<span>DeviantArt</span>'
					.'<span>blip.tv</span>'
					.'<span>Viddler</span>'
					.'<span>revision3</span>'
					.'<span>5min.com</span>'
					.'<span>dotsub</span>'
					.'<span>hulu</span>'
					.'<span>yfrog</span>'
					.'<span>smugmug</span>'
					.'<span>soundcloud</span>'
					.'<span>official.fm</span>'
					.'<span>rd.io</span>'
					.'</span>')
				.'</p>'
				.'</div>'
				.JS_PREFIX
				.'$(function() {'."\n"
				.'	var obj = $("#provider_ticker");'."\n"
				.'	var list = obj.children();'."\n"
				.'	list.not(":first").hide();'."\n"
				.'	setInterval(function(){'."\n"
				.'		list = obj.children();'."\n"
				.'		list.not(":first").hide();'."\n"
				.'		var first_li = list.eq(0);'."\n"
				.'		var second_li = list.eq(1);'."\n"
				.'		first_li.fadeOut(function(){'."\n"
				.'			obj.css("height",second_li.height());'."\n"
				.'			second_li.fadeIn();'."\n"
				.'			first_li.remove().appendTo(obj);'."\n"
				.'		});'."\n"
				.'	}, 2000);'."\n"
				.'});'."\n"
				.JS_SUFFIX;

			// uploading a file
			if(Surfer::may_upload())
				$input .= '<div id="embed_a_file" style="display: none; padding: 1em 0 1em 0;">'
					.'<input type="file" name="upload" id="upload" size="30" />'
					.'<p class="details">'.sprintf(i18n::s('Select a file of less than %s'), $context['file_maximum_size'].i18n::s('bytes')).'</p>'
					.'</div>';

			// change the display on selection
			$input .= JS_PREFIX
				.'$(function() {'."\n"
				.'	$("input[name=embed_type]").change(function() {'."\n"
				.'		if($("#share_href").attr("checked")) {'."\n"
				.'			$("#embed_a_link").slideDown();'."\n"
				.'			$("#embed_a_file").slideUp();'."\n"
				.'		}'."\n"
				.'		if($("#share_upload").attr("checked")) {'."\n"
				.'			$("#embed_a_link").slideUp();'."\n"
				.'			$("#embed_a_file").slideDown();'."\n"
				.'		}'."\n"
				.'		if($("#share_none").attr("checked")) {'."\n"
				.'			$("#embed_a_link").slideUp();'."\n"
				.'			$("#embed_a_file").slideUp();'."\n"
				.'		}'."\n"
				.'	});'."\n"
				.'});'."\n"
				.JS_SUFFIX;

		// nothing to do
		} elseif(!isset($this->attributes['embed_type']))
			;

		// display the address of the embedded object
		elseif(($this->attributes['embed_type'] == 'href') && trim($this->attributes['embed_href']))
			$input .= $this->attributes['embed_href'];

		// a complex field
		if($input)
			$fields[] = array($label, $input);

		return $fields;
	}

	/**
	 * display content in a list of pages
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_list_text($host=NULL) {
		$text = '';

		// if we have a valid thumbnail, use it as a link to the anchor page
		if(isset($this->attributes['thumbnail_url']) && trim($this->attributes['thumbnail_url'])
			&& isset($this->attributes['thumbnail_width']) && isset($this->attributes['thumbnail_height'])) {

			$text .= '<div style="margin: 0.3em auto">'
				.Skin::build_link($this->anchor->get_url(), '<img src="'.$this->attributes['thumbnail_url'].'" width="'.$this->attributes['thumbnail_width'].'" height="'.$this->attributes['thumbnail_height'].'" alt="" />', 'basic')
				.'</div>';
		}

		return $text;
	}

	/**
	 * display content below main panel
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_view_text($host=NULL) {
		$text = '';

		// do we have something embedded?
		if(isset($this->attributes['embed_type'])) {
			switch($this->attributes['embed_type']) {

			// a link has been shared
			case 'href':
				if(!isset($this->attributes['type']))
					return $text;

				// maybe some description has been added to this object
				if(isset($this->attributes['description']))
					$text .= '<div style="margin: 0.5em 0">'.skin::strip($this->attributes['description'], 50).'</div>'."\n";

				// default url is the original one
				if(!isset($this->attributes['url']))
					$this->attributes['url'] = $this->attributes['embed_href'];

				// empty url
				if(!trim($this->attributes['url']))
					return $text;

				// what has to be embedded?
				switch($this->attributes['type']) {

				// display a link in a button, and add a nice preview aside
				case 'link':
				default:

					// label this link
					$label = '';
					if(isset($this->attributes['label']))
						$label = trim($this->attributes['label']);
					if(!$label)
						$label = $this->attributes['embed_href'];

					// render this link
					$text .= '<div style="margin: 80px auto 90px auto">'
						.'<a class="button tipsy_showme" href="'.$this->attributes['embed_href'].'"><span>'.$label.'</span></a>'
						.'</div>'
						.JS_PREFIX
						.'// load the link in a scaled-down iframe'."\n"
						.'$(function() {'."\n"
						.'	$("a.tipsy_showme").each(function() {'."\n"
						.'		$(this).tipsy({fallback: \'<div class="tipsy_thumbnail"><iframe class="tipsy_iframe" src="\'+$(this).attr("href")+\'" /></div>\','."\n"
						.	'		 html: true,'."\n"
						.	'		 gravity: $.fn.tipsy.autoWE,'."\n"
						.	'		 fade: true,'."\n"
						.	'		 offset: 8,'."\n"
						.	'		 trigger: "manual",'."\n"
						.	'		 opacity: 1.0}).tipsy("show");'."\n"
						.'	});'."\n"
						.'});'."\n"
						.JS_SUFFIX;

					break;

				// display the photo itself
				case 'photo':
					if(isset($this->attributes['width']) && isset($this->attributes['height']))
						$text .= '<img src="'.$this->attributes['url'].'" width="'.$this->attributes['width'].'" height="'.$this->attributes['height'].'" alt="" />';
					else
						$text .= '<img src="'.$this->attributes['url'].'" alt="" />';
					break;

				// true object embedding
				case 'rich':
				case 'video':
					if($this->attributes['html'])
						$text .= $this->attributes['html'];
					break;

				}
				break;

			// a file has been shared -- laid out with other files
			case 'upload':
				break;

			}
		}

		if($text)
			$text = '<div style="margin: 1em 0 2em 0">'.$text.'</div>';
		return $text;
	}

	/**
	 * use oEmbed to embed some object
	 *
	 * @link http://oembed.com/ the specification of the oEmbed protocol
	 *
	 * The array returned reflects the outcome of the oEmbed transaction.
	 * If no provider has been found, the type is set to 'unknown'.
	 * If a network error takes place, the type is set to 'error'.
	 *
	 * @param string the address of the object to embed
	 * @return array attributes returned by provider
	 */
	public static function oembed($url) {
		global $context;

		// we return an array of results
		$result = array();

		// the list of known endpoints
		$endpoints = array();

		// type: video
		$endpoints['http://www\.youtube\.com/watch\?'] = 'http://www.youtube.com/oembed';
		$endpoints['http://youtu\.be/'] = 'http://www.youtube.com/oembed';
		$endpoints['http://vimeo\.com/'] = 'http://vimeo.com/api/oembed.json';
		$endpoints['http://revision3\.com/'] = 'http://revision3.com/api/oembed/';
		$endpoints['http://www\.5min\.com/video/'] = 'http://api.5min.com/oembed.json';
		$endpoints['http://dotsub\.com/view/'] = 'http://dotsub.com/services/oembed';
		$endpoints['http://www\.hulu\.com/watch/'] = 'http://www.hulu.com/api/oembed.json';
		$endpoints['http://[^\.]+\.dailymotion\.com/'] = 'http://www.dailymotion.com/api/oembed/';
		$endpoints['http://[^\.]+\.blip\.tv/'] = 'http://blip.tv/oembed/';
		$endpoints['http://blip\.tv/'] = 'http://blip.tv/oembed/';
		$endpoints['http://www\.viddler\.com/'] = 'http://lab.viddler.com/services/oembed/';

		// type: photo (or file)
		$endpoints['http://www\.flickr\.com/'] = 'http://www.flickr.com/services/oembed/';
		$endpoints['http://flic\.kr/'] = 'http://www.flickr.com/services/oembed/';
		$endpoints['http://[^\.]+\.deviantart\.com/'] = 'http://backend.deviantart.com/oembed';
		$endpoints['http://yfrog\.'] = 'http://www.yfrog.com/api/oembed';
		$endpoints['http://[^\.]+\.smugmug\.com/'] = 'http://api.smugmug.com/services/oembed/';
		$endpoints['http://[^\.]+\.photobucket\.com/'] = 'http://smedia.photobucket.com/oembed';

		// type: rich
		$endpoints['https://[^\.]+\.twitter\.com/'] = 'https://api.twitter.com/1/statuses/oembed.json';
		$endpoints['https://twitter\.com/'] = 'https://api.twitter.com/1/statuses/oembed.json';
		$endpoints['http://official\.fm/'] = 'http://official.fm/services/oembed.json';
		$endpoints['http://soundcloud\.com/'] = 'http://soundcloud.com/oembed';
		$endpoints['http://rd\.io/'] = 'http://www.rdio.com/api/oembed/';

		$endpoints['http://www\.slideshare\.net/'] = 'http://www.slideshare.net/api/oembed/2';
		$endpoints['http://[^\.]+\.scribd\.com/'] = 'http://www.scribd.com/services/oembed';

		// look at each providers
		$endpoint = null;
		foreach($endpoints as $pattern => $api) {

			// stop when one provider has been found
			if(preg_match('/'.str_replace('/', '\/', $pattern).'/i', $url)) {
				$endpoint = $api;
				break;
			}
		}

		// finalize the query for the matching endpoint
		if($endpoint) {

			// prepare the oEmbed request
			$parameters = array();
			$parameters[] = 'url='.urlencode($url);
			$parameters[] = 'maxwidth=500';
			$parameters[] = 'format=json';

			// encode provided data, if any
			$endpoint .= '?'.implode('&', $parameters);

		// else try to auto-detect an endpoint
		} elseif($content = http::proceed_natively($url)) {

			// it is not necessary to look at page content
			$content = substr($content, 0, stripos($content, '</head>'));

			// if the endpoint signature is found
			if(stripos($content, 'application/json+oembed') !== FALSE) {

				// extract all links
				if(preg_match_all('/<link([^<>]+)>/i', $content, $links)) {

					// look at each of them sequentially
					foreach($links[1] as $link) {

						// not the oEmbed endpoint!
						if(!stripos($link, 'application/json+oembed'))
							continue;

						// got it!
						if(preg_match('/href="([^"]+)"/i', $link, $matches)) {
							$endpoint = trim($matches[1]);
							break;
						}

					}
				}
			}
		}

		// no provider has been found
		if(!$endpoint) {
			$result['type'] = 'unknown';
			return $result;
		}

		// do the transaction
		if(!$response = http::proceed_natively($endpoint)) {
			$result['type'] = 'error';
			return $result;

		// decode the received snippet
		} else {
			include_once $context['path_to_root'].'included/json.php';
			if(!$data = json_decode2($response)) {
				$result['type'] = 'error';
				return $result;
			}

			// return data to caller
			foreach($data as $name => $value)
				$result[ $name ] = $value;

			// ensure that type is set
			if(!isset($result['type']))
				$result['type'] = 'error';
		}

		// job done
		return $result;
	}

	/**
	 * parse form fields
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the fields as filled by the end user
	 */
	function parse_fields($fields) {
		if(isset($fields['embed_type'])) {

			switch($fields['embed_type']) {

			// the overlay is useless, all the information is in the description field
			case 'none':
				$this->attributes['embed_type'] = 'none';
				break;

			// save the web address, that will be analyzed in remember()
			case 'href':
				$this->attributes['embed_type'] = 'href';
				$this->attributes['embed_href'] = $fields['embed_href'];
				break;

			// the file will be processed separately
			case 'upload':
				$this->attributes['embed_type'] = 'upload';
				break;

			}
		}
	}

	/**
	 * remember an action once it's done
	 *
	 * @see articles/delete.php
	 * @see articles/edit.php
	 *
	 * @param string the action 'insert', 'update' or 'delete'
	 * @param array the hosting record
	 * @param string reference of the hosting record (e.g., 'article:123')
	 * @return FALSE on error, TRUE otherwise
	 */
	function remember($action, $host, $reference) {
		global $context;

		// set default values for this editor
		Surfer::check_default_editor($this->attributes);

		// add a notification to the anchor page
		$comments = array();

		// on page creation
		if($action == 'insert') {

			// expose all of the anchor interface to the contained overlay
			$this->anchor = Anchors::get($reference);

			// embed an object referenced by address
			if($this->attributes['embed_type'] == 'href') {

				// ask some oEmbed provider to tell us more about this
				if($this->attributes['embed_href'] && ($fields = $this->oembed($this->attributes['embed_href']))) {

					// we do want a photo, right?
					if(preg_match('/\.(gif|jpg|jpeg|png)$/i', $this->attributes['embed_href']))
						$fields['type'] = 'photo';

					// because deviant-art returns non-standard type 'file' ???
					if(isset($fields['url']) && preg_match('/\.(gif|jpg|jpeg|png)$/i', $fields['url']))
						$fields['type'] = 'photo';

					// save meta data in the overlay itself
					$fields['id'] = $host['id'];
					$this->set_values($fields);

					// notify this contribution
					switch($this->attributes['type']) {
					case 'link':
						$comments[] = sprintf(i18n::s('%s has shared a link'), Surfer::get_name());
						break;

					case 'photo':
						$comments[] = sprintf(i18n::s('%s has shared a photo'), Surfer::get_name());
						break;

					case 'rich':
						$comments[] = sprintf(i18n::s('%s has shared some information'), Surfer::get_name());
						break;

					case 'video':
						$comments[] = sprintf(i18n::s('%s has shared a video'), Surfer::get_name());
						break;

					default:

						// default label is the link itself
						$label = $this->attributes['embed_href'];

						// fetch page title if possible
						if($this->attributes['embed_href'] && ($content = http::proceed($this->attributes['embed_href']))) {
							if(preg_match('/<title>(.*)<\/title>/siU', $content, $matches))
								$label = trim(strip_tags(preg_replace('/\s+/', ' ', $matches[1])));
						}

						// update the record
						$fields = array();
						$fields['type'] = 'link';
						$fields['label'] = $label;
						$this->set_values($fields);

						$comments[] = sprintf(i18n::s('%s has shared a link'), Surfer::get_name());
						break;

					}
				}

			// uploaded files are turned to comments automatically in articles/article.php
			}
		}

		// add a comment
		if($comments) {
			include_once $context['path_to_root'].'comments/comments.php';
			$fields = array();
			$fields['anchor'] = $reference;
			$fields['description'] = join(BR, $comments);
			$fields['type'] = 'notification';
			Comments::post($fields);
		}

		// job done
		return TRUE;

	}

	/**
	 * we don't want to embed uploaded files in description field
	 *
	 * @see overlays/overlay.php
	 *
	 * @return boolean TRUE by default, but can be changed in derived overlay
	 */
	function should_embed_files() {
		return FALSE;
	}

}

?>
