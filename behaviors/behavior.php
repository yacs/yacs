<?php
/**
 * Programmable extensions of sections, articles, and files
 *
 * Behaviors are software extensions used from within sections to change YACS basic behavior on key event, such as:
 *
 * - Content creation - to allow for controlled article creation or file attachment (tel2pay, etc.)
 *
 * - Content access - to allow for controlled access (tel2pay, license agreement, etc.)
 *
 * - Content change - the main goal is to implement workflows on approvals
 *
 *
 * What are member functions?
 *
 * Behavior has one single constructor, that supports run-time parameters:
 * - [code]Behavior()[/code] -- create an instance from scratch
 *
 * The interface itself is made of following member functions,
 * that have to be overloaded into derivated classes:
 * - [code]allow()[/code] -- check access permission
 *
 * How do behaviors compare to overlays?
 *
 * Overlays are aiming to store additional structured data in articles, where behaviors are stateless, and apply equally to all pages of some kind.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Behavior {

	/**
	 * parameters specific to this behavior
	 */
	var $parameters;

	/**
	 * extend the page menu
	 *
	 * @param string script name
	 * @param string target anchor, if any
	 * @param array current menu
	 * @return array updated menu
	 */
	function &add_commands($script, $anchor, $menu=array()) {
		return $menu;
	}

	/**
	 * check access rights
	 *
	 * @param string script name
	 * @paral string target anchor, if any
	 * @return boolean FALSE if access is denied, TRUE otherwise
	 */
	function allow($script, $anchor = NULL) {
		return TRUE;
	}


	/**
	 * initialize one behavior instance
	 *
	 * This function is invoked by behavior loader to allow for transmission of run-time parameters.
	 *
	 * To be overloaded in derivated class.
	 *
	 * @param string behavior parameter
	 */
	function Behavior($parameters='') {
		$this->parameters = $parameters;
	}

}

?>