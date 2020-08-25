<?php
/**
 * Programmable extensions of sections, articles, and files
 *
 * Behaviors are software extensions used from within sections, articles, and files to change YACS basic behavior on key event, such as:
 *
 * - Content creation - to allow for controlled article creation or file attachment (tel2pay, etc.)
 *
 * - Content access - to allow for controlled access (tel2pay, license agreement, etc.)
 *
 * - Content change - the main goal is to implement workflows on approvals
 *
 *
 * Behaviors are described in specialized fields either in sections, articles, or files.
 * These multi-line fields allow for the definition of several behaviors per item.
 *
 * Each line describes one behavior, according to following formats:
 *
 * [snippet]behavior_name[/snippet]
 *
 * or
 *
 * [snippet]behavior_name behavior_parameters[/snippet]
 *
 * The behavior name references one PHP script that extends the behavior interface, defined in [script]behaviors/behavior.php[/script].
 * Parameters consist of optional data provided to the behavior script at run-time.
 *
 * Some examples of behavior invocations:
 *
 * [snippet]
 * // agree on creative common license prior file download (at section, article, or file level)
 * agree_on_file_access creative_common.txt
 *
 * // ask to pay for each new article posted in a section
 * paypal_on_article_creation email@mysite.com 5 EUR
 *
 * // ask to pay for article access (at section or article level)
 * paypal_on_article_access email@mysite.com 0.5 EUR
 *
 * // ask to pay for file download (at section, article, or file level)
 * paypal_on_file_access email@mysite 1 EUR
 * [/snippet]
 *
 * How do behaviors compare to overlays?
 *
 * Overlays are aiming to store additional structured data in articles, where behaviors are stateless, and are scoping sections.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Behaviors {

	/**
	 * all registered behaviors
	 */
	var $items;

	/**
	 * constructor
	 *
	 * This function parses behavior declarations put in some item, and in related anchors.
	 *
	 * @param array reference item behaviors, if any
	 * @param object reference anchor behaviors, if any
	 */
	function __construct(&$item, &$anchor) {

		// get behaviors description
		$text = '';

		// get behaviors from anchor, if any
		if(isset($anchor) && is_object($anchor) && method_exists($anchor, 'get_behaviors'))
			$text = trim($anchor->get_behaviors());

		// get behaviors from this instance, if any
		if(isset($item) && is_array($item) && array_key_exists('behaviors', $item))
			$text = trim($text . "\n" . $item['behaviors']);

		// parse text
		$this->parse($text);
	}

	/**
	 * extend the page menu
	 *
	 * @param string script name
	 * @param string target anchor, if any
	 * @param array current menu
	 * @return array updated menu
	 */
	function add_commands($script, $anchor, $menu=array()) {
		global $context;

		// check every behavior in sequence
		for($index = count($this->items) -1; $index >= 0; $index--) {

			// extend the menu
			$behavior = $this->items[$index];
			$menu = $behavior->add_commands($script, $anchor, $menu);

		}

		// return result
		return $menu;
	}

	/**
	 * allow script execution, or block it
	 *
	 * @param string script name
	 * @param string target anchor, if any
	 * @return boolean FALSE if execution is cancelled, TRUE otherwise
	 */
	function allow($script, $anchor) {
		global $context;

		// resulting permission
		$permitted = TRUE;

		// check every behavior in sequence
		foreach($this->items as $behavior) {

			// remember any deny
			$permitted = $permitted && $behavior->allow($script, $anchor);

		}

		// return result
		return $permitted;
	}

	/**
	 * bind behavior declarations to real code
	 *
	 * @param string behavior declarations
	 *
	 */
	function parse(&$text) {
		global $context;

		// no registered behaviors yet
		$this->items = array();

		// sanity check
		if(!$text)
			return;

		// one behavior per line
		$lines = explode("\n", $text);

		// parse each line
		include_once $context['path_to_root'].'behaviors/behavior.php';
		foreach($lines as $line) {

			// trim every line
			$line = trim($line);

			// skip comments and null lines
			if(!$line || ($line[0] < 'a') || ($line[0] > 'z'))
				continue;

			// split tokens
			$tokens = explode(' ', $line, 2);
			$behavior = $tokens[0];
			if(count($tokens) == 2)
				$parameters = $tokens[1];
			else
				$parameters = '';

			// load script implementing the behavior
			$script = $context['path_to_root'].'behaviors/'.$behavior.'.php';
			if(is_readable($script)) {
				include_once $script;

				// append the behavior at the end of the list
				if(class_exists($behavior))
					$this->items[] = new $behavior($parameters);

				// bad script content
				elseif(is_callable(array('Skin', 'error')))
					Logger::error(sprintf(i18n::s('No behavior %s has been found.'), $behavior));
				else
					die(sprintf(i18n::s('No behavior %s has been found.'), $behavior));

			// bad behavior declaration
			} elseif(is_callable(array('Skin', 'error')))
				Logger::error(sprintf(i18n::s('No behavior %s has been found.'), $behavior));
			else
				die(sprintf(i18n::s('No behavior %s has been found.'), $behavior));

		}

		// strip behavior ids
		$this->items = array_values($this->items);

	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('behaviors');

?>