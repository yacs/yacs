<?php
/**
 * the skeleton skin
 *
 * As its name implies, the skeleton skin is quite simple. It may be used as a good starting point by skin designers.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
if(count(get_included_files()) < 3) {
	echo 'Script must be included';
	return;
}

// let share this skin
global $skins;
$skins['skins/skeleton'] = array(
	'label_en' => 'skeleton',
	'label_fr' => 'skeleton',
	'description_en' => 'As its name implies, the skeleton skin is quite simple. It may be used as a good starting point by skin designers.',
	'description_fr' => 'Comme son nom l\'indique, ce style est assez simple. Suffisamment pour servir de point de d&eacute;part aux concepteurs de style en tout cas.',
	'thumbnail' => 'preview.jpg',
	'home_url' => 'http://www.yetanothercommunitysystem.com/' );
?>