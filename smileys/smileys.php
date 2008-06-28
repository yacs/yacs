<?php
/**
 * the library of smileys
 *
 * Smileys are these little icons that we are using to briefly report on our feeling.
 *
 * To add a new smiley on this system, you will have:
 * - to prepare a new icon file
 * - to name it
 * - to update smileys.php to bind the name to the file
 * - to update the page below to show it working
 *
 * @link smileys/index.php The current list of supported smileys on this server
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Lucrecius
 * @tester Agnes
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

class Smileys {

	/**
	 * called from within a preg_replace_callback() in Smileys::render_smileys()
	 *
	 */
	function parse_match($matches) {
		global $context;

		// target tag
		$tag = $matches[1];

		// ensure a file exists for this tag
		if(!file_exists($context['path_to_root'].'skins/images/smileys/'.$tag.'.gif'))
			return $matches[0];

		// make a valid reference to an image
		return ' <img src="'.$context['url_to_root'].'skins/images/smileys/'.$tag.'.gif" alt="'.$tag.'" /> ';

	}

	/**
	 * transform some text to load related images
	 *
	 * @param string the input text
	 * @return the tansformed text
	 */
	function &render_smileys($text) {
		global $context;

		// no content on HEAD request --see scripts/validate.php
		if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
			return $text;

		// sanity check
		$text = trim($text);
		if(!$text)
			return $text;

		// the list of codes to be interpreted --initialize only once
		static $pattern, $replace;
		if(!isset($pattern)) {

			$pattern = array();
			$replace = array();

			$prefix = ' <img src="'.$context['url_to_root'].'skins/images/smileys/';
			$suffix = '" alt=""'.EOT.' ';

			$pattern[] = ' >:(';
			$replace[] = $prefix.'angry.gif'.$suffix;
			$pattern[] = '>:-\(';
			$replace[] = $prefix.'angry.gif'.$suffix;

			$pattern[] = ' :D';
			$replace[] = $prefix.'cheesy.gif'.$suffix;
			$pattern[] = ':-D';
			$replace[] = $prefix.'cheesy.gif'.$suffix;

			$pattern[] = " :'(";
			$replace[] = $prefix.'cry.gif'.$suffix;
			$pattern[] = ":'-(";
			$replace[] = $prefix.'cry.gif'.$suffix;

			$pattern[] = ' 8)';
			$replace[] = $prefix.'cool.gif'.$suffix;
			$pattern[] = '8-)';
			$replace[] = $prefix.'cool.gif'.$suffix;

			$pattern[] = ' :(';
			$replace[] = $prefix.'frown.gif'.$suffix;
			$pattern[] = ':-(';
			$replace[] = $prefix.'frown.gif'.$suffix;

			$pattern[] = '???';
			$replace[] = $prefix.'confused.gif'.$suffix;

			$pattern[] = ' :[';
			$replace[] = $prefix.'embarassed.gif'.$suffix;
			$pattern[] = ':-[';
			$replace[] = $prefix.'embarassed.gif'.$suffix;

			$pattern[] = ':blush:';
			$replace[] = $prefix.'blushing.gif'.$suffix;

			$pattern[] = ' :X';
			$replace[] = $prefix.'sealed.gif'.$suffix;
			$pattern[] = ':-X';
			$replace[] = $prefix.'sealed.gif'.$suffix;

			$pattern[] = ' :P';
			$replace[] = $prefix.'tongue.gif'.$suffix;
			$pattern[] = ':-P';
			$replace[] = $prefix.'tongue.gif'.$suffix;

			$pattern[] = ':medal:';
			$replace[] = $prefix.'medal_full.gif'.$suffix;

			$pattern[] = ':half_medal:';
			$replace[] = $prefix.'medal_half.gif'.$suffix;

			$pattern[] = ' ::)';
			$replace[] = $prefix.'rolleyes.gif'.$suffix;
			$pattern[] = '::-)';
			$replace[] = $prefix.'rolleyes.gif'.$suffix;

			$pattern[] = ' :)';
			$replace[] = $prefix.'smile.gif'.$suffix;
			$pattern[] = ':-)';
			$replace[] = $prefix.'smile.gif'.$suffix;

			$pattern[] = ' :o';
			$replace[] = $prefix.'shocked.gif'.$suffix;
			$pattern[] = ':-o';
			$replace[] = $prefix.'shocked.gif'.$suffix;

			$pattern[] = ' :/';
			$replace[] = $prefix.'undecided.gif'.$suffix;
			$pattern[] = ':-/';
			$replace[] = $prefix.'undecided.gif'.$suffix;

			$pattern[] = ' ;)';
			$replace[] = $prefix.'winkgrin.gif'.$suffix;
			$pattern[] = ';-)';
			$replace[] = $prefix.'winkgrin.gif'.$suffix;

			$pattern[] = ':party:';
			$replace[] = $prefix.'partygirl.gif'.$suffix;

			$pattern[] = ':*:';
			$replace[] = $prefix.'star.gif'.$suffix;

			$pattern[] = ' :*';
			$replace[] = $prefix.'kiss.gif'.$suffix;
			$pattern[] = ':-*';
			$replace[] = $prefix.'kiss.gif'.$suffix;

			$pattern[] = ' :+';
			$replace[] = $prefix.'thumbsup.gif'.$suffix;
			$pattern[] = ':up:';
			$replace[] = $prefix.'thumbsup.gif'.$suffix;

			$pattern[] = ' :-';
			$replace[] = $prefix.'thumbsdown.gif'.$suffix;
			$pattern[] = ':down:';
			$replace[] = $prefix.'thumbsdown.gif'.$suffix;

			$pattern[] = ':?!';
			$replace[] = $prefix.'idea.gif'.$suffix;

			$pattern[] = ' :?2';
			$replace[] = $prefix.'question2.gif'.$suffix;

			$pattern[] = ' :?';
			$replace[] = $prefix.'question.gif'.$suffix;

			$pattern[] = ' :!2';
			$replace[] = $prefix.'exclamation2.gif'.$suffix;

			$pattern[] = ' :!';
			$replace[] = $prefix.'exclamation.gif'.$suffix;

		}

		// ensure we have enough processing time
		Safe::set_time_limit(30);

		// process dotted smileys --insert a space for smileys at the very beginning of the string
		$text = str_replace($pattern, $replace,  ' '.$text);

		// process any image file
		$text = preg_replace_callback('/:([\w_]+):/', array('smileys', 'parse_match'),	$text);
		return $text;
	}
}
?>