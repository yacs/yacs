<?php
/**
 * provide images as thumbs gallery
 *
 * @see images/images.php
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_images_as_thumb extends Layout_interface {

	/**
	 * list images
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see layouts/layout.php
	**/
	function layout($result) {
		global $context;

		

		// we return html text
		$text = '';
                
                // empty list
		if(!SQL::count($result)) {
			return $text;
		}
                
                $variant = 'thumbnail';

		// process all items in the list
		while($image = SQL::fetch($result)) {
                    

			// a title for the image --do not force a title
			if(isset($image['title']))
				$title = $image['title'];
			else
				$title = '';

			// provide thumbnail if not defined, or forced, or for large images
			if(!$image['use_thumbnail']
				|| ($image['use_thumbnail'] == 'A')
				|| (($image['use_thumbnail'] == 'Y') && ($image['image_size'] > $context['thumbnail_threshold'])) ) {


				// where to fetch the image file
				$href = Images::get_thumbnail_href($image);

				// to drive to plain image
				$link = Images::get_icon_href($image);

			// add an url, if any
			} elseif($image['link_url']) {

				// flag large images
				if($image['image_size'] > $context['thumbnail_threshold'])
					$variant = rtrim('large '.$variant);

				// where to fetch the image file
				$href = Images::get_icon_href($image);

				// transform local references, if any
				include_once $context['path_to_root'].'/links/links.php';
				$attributes = Links::transform_reference($image['link_url']);
				if($attributes[0])
					$link = $context['url_to_root'].$attributes[0];

				// direct use of this link
				else
					$link = $image['link_url'];

			// get the <img ... /> element
			} else {

				// do not append poor titles to inline images
				if($variant == 'thumbnail')
					$title = '';

				// flag large images
				if($image['image_size'] > $context['thumbnail_threshold'])
					$variant = rtrim('large '.$variant);

				// where to fetch the image file
				$href = Images::get_icon_href($image);

				// no link
				$link = '';

			}

			// use the skin
			if(Images::allow_modification($image['anchor'],$image['id']))
			   // build editable image
			   $text .= Skin::build_image($variant, $href, $title, $link, $image['id']);
			else
			   $text .= Skin::build_image($variant, $href, $title, $link);

		}
                
                $text .= '<div class="clear"></div>'."\n";

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>