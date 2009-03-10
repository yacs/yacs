<?php
/**
 * integrate outbound mail processing
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see control/scan.php
 */

// stop hackers
defined('YACS') or exit('Script must be included');

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
	'source' => 'http://www.yacs.fr/' );

?>