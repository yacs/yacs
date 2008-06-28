<?php
/**
 * integrate messaging agent
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
	'script'	=> 'agents/messages.php',
	'function'	=> 'Messages::tick_hook',
	'label_en'	=> 'Process inbound mailbox in the background',
	'label_fr'	=> 'Traitement d\'arri&egrave;re-plan des messages re&ccedil;us',
	'description_en' => 'To integrate messages into the database',
	'description_fr' => 'Pour int&eacute;gration dans la base de donn&eacute;es',
	'source' => 'http://www.yetanothercommunitysystem.com/' );

?>