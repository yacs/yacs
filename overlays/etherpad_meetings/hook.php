<?php
/**
 * integrate Etherpad meetings
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
defined('YACS') or exit('Script must be included');

// let this module appear in the control panel
global $hooks;
$hooks[] = array(
	'id'		=> 'control/index.php#configure',
	'type'		=> 'link',
	'script'	=> 'overlays/etherpad_meetings/configure.php',
	'label_en'	=> 'Configure Etherpad services',
	'label_fr'	=> 'Configurer les services Etherpad',
	'description_en' => 'To add real-time collaboration to your server.',
	'description_fr' => 'Pour ajouter la collaboration en temps r&eacute;el &agrave; votre serveur.',
	'source' => 'http://www.yacs.fr/' );

?>