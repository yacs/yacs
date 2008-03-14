<?php
/**
 * catch referral information
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @see control/scan.php
 * @see agents/referrals.php
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
if(count(get_included_files()) < 3) {
	echo 'Script must be included';
	return;
}

// let this module appear in the setup script
$hooks[] = array(
	'id'		=> 'control/setup.php',
	'type'		=> 'include',
	'script'	=> 'agents/referrals.php',
	'function'	=> 'Referrals::setup',
	'label_en'	=> 'Setup tables for stats on referrals',
	'label_fr'	=> 'Cr&eacute;ation des tables pour les statistiques de r&eacute;f&eacute;rencement',
	'description_en' => 'To list back links',
	'description_fr' => 'Pour le suivi des liens de retour',
	'source' => 'http://www.yetanothercommunitysystem.com/' );

// trigger the post-processing function
$hooks[] = array(
	'id'		=> 'finalize',
	'type'		=> 'include',
	'script'	=> 'agents/referrals.php',
	'function'	=> 'Referrals::check_request',
	'label_en'	=> 'Update stats on referral',
	'label_fr'	=> 'Mise &agrave; jour des statistiques sur les r&eacute;f&eacute;rencements',
	'description_en' => 'To list back links',
	'description_fr' => 'Pour le suivi des liens de retour',
	'source' => 'http://www.yetanothercommunitysystem.com/' );

?>