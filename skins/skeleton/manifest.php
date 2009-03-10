<?php
/**
 * the skeleton skin
 *
 * As its name implies, the skeleton skin is quite simple. It may be used as a good starting point by skin designers.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
defined('YACS') or exit('Script must be included');

// let share this skin
global $skins;
$skins['skins/skeleton'] = array(
	'label_en' => 'skeleton',
	'label_fr' => 'skeleton',
	'description_en' => 'As its name implies, the skeleton skin is quite simple. It may be used as a good starting point by skin designers.',
	'description_fr' => 'Comme son nom l\'indique, ce style est assez simple. Suffisamment pour servir de point de d&eacute;part aux concepteurs de style en tout cas.',
	'thumbnail' => 'preview.jpg',
	'home_url' => 'http://www.yacs.fr/' );
?>