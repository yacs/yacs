<?php
/**
 * handle one image
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Adivo
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Image {

	/**
	 * maintain image size within limits
	 *
	 * Maximum sizes for standard images and for avatars are set in the configuration panel for rendering.
	 *
	 * @see skins/configure.php
	 *
	 * @param string the full path to the original file
	 * @param boolean TRUE to set error messages, if any
	 * @param string 'standard' or 'avatar'
	 * @return TRUE on resize, FALSE otherwise
	 */
	function adjust($original, $verbose=TRUE, $variant='standard') {
		global $context;

		// get file name
		$file_name = basename($original);

		// ensure this is a valid file
		if(!$image_information = Safe::GetImageSize($original)) {
			if($verbose)
				Skin::error(sprintf(i18n::s('No image information in %s.'), $file_name));
			return FALSE;
		}

		// GIF image
		if(($image_information[2] == 1) && is_callable('ImageCreateFromGIF'))
			$image = ImageCreateFromGIF($original);

		// JPEG image
		elseif(($image_information[2] == 2) && is_callable('ImageCreateFromJPEG'))
			$image = ImageCreateFromJPEG($original);

		// PNG image
		elseif(($image_information[2] == 3) && is_callable('ImageCreateFromPNG'))
			$image = ImageCreateFromPNG($original);

		// sanity check
		if(!isset($image)) {
			if($verbose)
				Skin::error(sprintf(i18n::s('No GD support, or unknown image type in %s.'), $file_name));
			return FALSE;
		}

		// actual width
		$width = $image_information[0];

		// actual height
		$height = $image_information[1];

		// maximum width
		$maximum_width = 512;
		if(($variant == 'avatar') && isset($context['avatar_width']) && ($context['avatar_width'] > 10))
			$maximum_width = $context['avatar_width'];
		elseif(isset($context['standard_width']) && ($context['standard_width'] > 10))
			$maximum_width = $context['standard_width'];

		// maximum height
		$maximum_height = 512;
		if(($variant == 'avatar') && isset($context['avatar_height']) && ($context['avatar_height'] > 10))
			$maximum_height = $context['avatar_height'];
		elseif(isset($context['standard_height']) && ($context['standard_height'] > 10))
			$maximum_height = $context['standard_height'];

		// assume resize is not necessary
		$adjust_height = $height;
		$adjust_width = $width;

		// the image is laid vertically
		if($height > $width) {

			// set adjusted dimensions
			if($height > $maximum_height) {
				$adjust_height = $maximum_height;
				$adjust_width = $width * $adjust_height / $height;
			}

		// the image is laid horizontally
		} else {

			// set adjusted dimensions
			if($width > $maximum_width) {
				$adjust_width = $maximum_width;
				$adjust_height = $height * $adjust_width / $width;
			}

		}

		// the image already fits in
		if(($adjust_width == $width) && ($adjust_height == $height))
			return FALSE;

		// create the adjusted image in memory
		$adjusted = NULL;
		if(($image_information[2] == 2) && is_callable('ImageCreateTrueColor') && ($adjusted = ImageCreateTrueColor($adjust_width, $adjust_height))) {
			ImageCopyResampled($adjusted, $image, 0, 0, 0, 0, $adjust_width, $adjust_height, $width, $height);
		}
		if((!$adjusted) && is_callable('ImageCreate') && ($adjusted = ImageCreate($adjust_width, $adjust_height))) {
			ImageCopyResized($adjusted, $image, 0, 0, 0, 0, $adjust_width, $adjust_height, $width, $height);
		}

		// sanity check
		if(!$adjusted) {
			if($verbose)
				Skin::error(sprintf(i18n::s('Impossible to adjust image %s.'), $file_name));
			return FALSE;
		}

		// save the adjusted image in the file system
		$result = FALSE;
		if(($image_information[2] == 1) && is_callable('ImageGIF'))
			ImageGIF($adjusted, $original);
		elseif(($image_information[2] == 2) && is_callable('ImageJPEG'))
			ImageJPEG($adjusted, $original, 70);
		elseif((($image_information[2] == 1) || ($image_information[2] == 3)) && is_callable('ImagePNG'))
			ImagePNG($adjusted, $original);
		else {
			if($verbose)
				Skin::error(sprintf(i18n::s('Cannot write adjusted image to %s.'), $target));
			return FALSE;
		}

		// job done
		ImageDestroy($adjusted);
		return TRUE;

	}

	/**
	 * create a thumbnail
	 *
	 * @param string the full path to the original file
	 * @param string the pull path to write the thumbnail
	 * @param boolean TRUE to see error messages, if any
	 * @return TRUE on success, FALSE on error
	 */
	function shrink($original, $target, $verbose=TRUE) {
		global $context;

		// get file name
		$file_name = basename($original);

		// ensure this is a valid file
		if(!$image_information = Safe::GetImageSize($original)) {
			if($verbose)
				Skin::error(sprintf(i18n::s('No image information in %s'), $file_name));
			return FALSE;
		}

		// GIF image
		if(($image_information[2] == 1) && is_callable('ImageCreateFromGIF'))
			$image = ImageCreateFromGIF($original);

		// JPEG image
		elseif(($image_information[2] == 2) && is_callable('ImageCreateFromJPEG'))
			$image = ImageCreateFromJPEG($original);

		// PNG image
		elseif(($image_information[2] == 3) && is_callable('ImageCreateFromPNG'))
			$image = ImageCreateFromPNG($original);

		// sanity check
		if(!isset($image)) {
			if($verbose)
				Skin::error(sprintf(i18n::s('Unknown image type in %s.'), $file_name));
			return FALSE;
		}

		// actual width
		$width = $image_information[0];

		// standard width
		if(!isset($context['thumbnail_width']) || ($context['thumbnail_width'] < 32))
			$context['thumbnail_width'] = 60;

		// actual height
		$height = $image_information[1];

		// standard height
		if(!isset($context['thumbnail_height']) || ($context['thumbnail_height'] < 32))
			$context['thumbnail_height'] = 60;

		// assume resize is not necessary
		$thumbnail_height = $height;
		$thumbnail_width = $width;

		// the image is laid vertically
		if($height > $width) {

			// set the thumbnail size
			if($height > $context['thumbnail_height']) {
				$thumbnail_height = $context['thumbnail_height'];
				$thumbnail_width = $width * $thumbnail_height / $height;
			}

		// the image is laid horizontally
		} else {

			// set the thumbnail size
			if($width > $context['thumbnail_width']) {
				$thumbnail_width = $context['thumbnail_width'];
				$thumbnail_height = $height * $thumbnail_width / $width;
			}

		}

		// we already have a small image
		if(($thumbnail_width == $width) && ($thumbnail_height == $height)) {

			// copy file content to the thumbnail
			if(!copy($original, $target)) {
				if($verbose)
					Skin::error(sprintf(i18n::s('Cannot copy image to %s'), $target));
				return FALSE;
			}

			// this will be filtered by umask anyway
			Safe::chmod($target, $context['file_mask']);

			// job done
			return TRUE;

		}

		// create the thumbnail in memory
		$thumbnail = NULL;
		if(($image_information[2] == 2) && is_callable('ImageCreateTrueColor') && ($thumbnail = ImageCreateTrueColor($thumbnail_width, $thumbnail_height))) {
			ImageCopyResampled($thumbnail, $image, 0, 0, 0, 0, $thumbnail_width, $thumbnail_height, $width, $height);
		}
		if((!$thumbnail) && is_callable('ImageCreate') && ($thumbnail = ImageCreate($thumbnail_width, $thumbnail_height))) {
			ImageCopyResized($thumbnail, $image, 0, 0, 0, 0, $thumbnail_width, $thumbnail_height, $width, $height);
		}

		// sanity check
		if(!$thumbnail) {
			if($verbose)
				Skin::error(sprintf(i18n::s('Impossible to skrink image %s'), $file_name));
			return FALSE;
		}

		// save the thumbnail in the file system
		$result = FALSE;
		if(($image_information[2] == 1) && is_callable('ImageGIF'))
			ImageGIF($thumbnail, $target);
		elseif(($image_information[2] == 2) && is_callable('ImageJPEG'))
			ImageJPEG($thumbnail, $target, 70);
		elseif((($image_information[2] == 1) || ($image_information[2] == 3)) && is_callable('ImagePNG'))
			ImagePNG($thumbnail, $target);
		else {
			if($verbose)
				Skin::error(sprintf(i18n::s('Impossible to write to %s.'), $target));
			return FALSE;
		}

		// this will be filtered by umask anyway
		Safe::chmod($target, $context['file_mask']);

		// job done
		ImageDestroy($thumbnail);
		return TRUE;

	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('images');

?>