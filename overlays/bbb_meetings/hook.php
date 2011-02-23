<?php
/**
 * integrate bbb meetings
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
	'script'	=> 'overlays/bbb_meetings/configure.php',
	'label_en'	=> 'Configure BigBlueButton services',
	'label_fr'	=> 'Configurer les services BigBlueButton',
	'description_en' => 'To add web conferencing to your server.',
	'description_fr' => 'Pour ajouter les conf&eacute;rences web &agrave; votre serveur.',
	'source' => 'http://www.yacs.fr/' );

?>