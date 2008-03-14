<?php
/**
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
if(count(get_included_files()) < 3) {
	echo 'Script must be included';
	return;
}

// describe this locale using Unicode or HTML entities, and add English name in parenthesis
global $locales;
$locales['fr'] = 'Fran&ccedil;ais (French)';

?>