<?php
/**
 * Ask for surfer agreement before loading a file
 *
 * To activate this behavior, add following line in the target section:
 *
 * [code]agree_on_file_access gnu-lgpl.txt[/code]
 *
 * You can change ##gnu-lgpl.txt## to any file in ##behaviors/agreements##
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Agree_on_file_access extends Behavior {

	/**
	 * check access rights
	 *
	 * @param string script name
	 * @paral string target anchor, if any
	 * @return boolean FALSE if access is denied, TRUE otherwise
	 */
	function allow($script, $anchor = NULL) {
		global $context;

		// load localized strings
		i18n::bind('behaviors');

		// limit the scope of our check
		if(($script != 'files/view.php') && ($script != 'files/fetch.php') && ($script != 'files/fetch_all.php') && ($script != 'files/stream.php'))
			return TRUE;

		// sanity check
		if(!$anchor)
			die(i18n::s('No anchor has been found.'));

		// stop here if the agreement has been gathered previously
		if(isset($_SESSION['agreements']) && is_array($agreements = $_SESSION['agreements']))
			foreach($agreements as $agreement)
				if($agreement == $anchor)
					return TRUE;

		// which agreement?
		if(!$this->parameters)
			die(sprintf(i18n::s('No parameter has been provided to %s'), 'behaviors/agree_on_file_access'));

		// do we have a related file to display?
		if(!is_readable($context['path_to_root'].'behaviors/agreements/'.$this->parameters))
			die(sprintf(i18n::s('Bad parameter to behavior <code>%s %s</code>'), 'agree_on_file_access', $this->parameters));

		// splash message
		$context['text'] .= '<p class="agreement">'.i18n::s('Before moving forward, please read following text and express yourself at the end of the page.').'</p><hr/>'."\n";

		// load and display the file to be displayed
		$context['text'] .= Codes::beautify(Safe::file_get_contents($context['path_to_root'].'behaviors/agreements/'.$this->parameters));

		// target link to record agreement
		if($context['with_friendly_urls'] == 'Y')
			$agree_link = 'behaviors/agreements/agree.php/'.rawurlencode($anchor);
		else
			$agree_link = 'behaviors/agreements/agree.php?id='.urlencode($anchor);

		// display confirmation buttons at the end of the agreement
		$context['text'] .= '<hr/><p class="agreement">'.i18n::s('Do you agree?');
		$context['text'] .= ' '.Skin::build_link($agree_link, i18n::s('Yes'), 'button');
		$context['text'] .= ' '.Skin::build_link('behaviors/agreements/deny.php', i18n::s('No'), 'button').'</p>'."\n";

		// render the skin based only on text provided by this behavior
		render_skin();
		exit();
	}


}

?>