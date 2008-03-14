<?php
/**
 * integrate outbound mail processing
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
	'script'	=> 'shared/mailer.php',
	'function'	=> 'Mailer::tick_hook',
	'label_en'	=> 'Process outbound mailbox in the background',
	'label_fr'	=> 'Traitement d\'arri&egrave;re-plan des messages envoy&eacute;s',
	'description_en' => 'To advertise the world',
	'description_fr' => 'Pour avertir le monde entier',
	'source' => 'http://www.yetanothercommunitysystem.com/' );

?>