<?php
/**
 * integrate meetings
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
defined('YACS') or exit('Script must be included');

// let this module appear in the setup script
global $hooks;
$hooks[] = array(
	'id'		=> 'control/setup.php',
	'type'		=> 'include',
	'script'	=> 'overlays/generic_meeting.php',
	'function'	=> 'Generic_Meeting::setup',
	'label_en'	=> 'Setup tables for enrolments',
	'label_fr'	=> 'Cr&eacute;ation des tables pour les confirmations de r&eacute;unions',
	'source' => 'http://www.yacs.fr/' );

?>