<?php
/**
 * integrate messaging agent
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see control/scan.php
 */

// stop hackers
if(count(get_included_files()) < 3) {
	echo 'Script must be included';
	return;
}

// trigger the post-processing function
$hooks[] = array(
	'id'		=> 'tick',
	'type'		=> 'include',
	'script'	=> 'agents/messages.php',
	'function'	=> 'Messages::tick_hook',
	'label_en'	=> 'Process inbound mailbox in the background',
	'label_fr'	=> 'Traitement d\'arri&egrave;re-plan des messages re&ccedil;us',
	'description_en' => 'To integrate messages into the database',
	'description_fr' => 'Pour int&eacute;gration dans la base de donn&eacute;es',
	'source' => 'http://www.yetanothercommunitysystem.com/' );

?>