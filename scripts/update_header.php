<?php
/**
 * introduce a new update
 *
 * This is included by the update script before proceeding with the actual change of scripts.
 *
 * The file is included directly from the staging store. This means that it will be executed only once,
 * on first staging.
 *
 * @see scripts/update.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
if(count(get_included_files()) < 3) {
	echo 'Script must be included';
	return;
}

// avoid errors on old versions
if(!is_callable(array('i18n', 'bind')))
	return;

// load localized strings
i18n::bind('scripts');

// this is a placeholder for important message to be displayed to webmasters before actual update
$context['text'] .= '<p>'.i18n::s('This script will change running scripts at your server.')."</p>\n";

?>