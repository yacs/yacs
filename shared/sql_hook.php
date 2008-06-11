<?php
/**
 * optimize growing tables in the background
 *
 * @see control/scan.php
 * @see shared/sql.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// trigger on publishing
$hooks[] = array(
	'id'		=> 'tick',
	'type'		=> 'include',
	'script'	=> 'shared/sql.php',
	'function'	=> 'SQL::purge',
	'label_en'	=> 'Database purge',
	'label_fr'	=> 'Purge de la base de donn&eacute;es',
	'description_en' => 'Recover the unused disk space',
	'description_fr' => 'Restitution de l\'espace disque inutilis&eacute;',
	'source' => 'http://www.yetanothercommunitysystem.com/'
);

// stop hackers
defined('YACS') or exit('Script must be included');

?>