<?php
/**
 * a skin inspired by Digital Web Magazine
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
$skins['skins/digital'] = array(
	'label_en' => 'digital',
	'label_fr' => 'digital',
	'description_en' => 'This skin is coming from the Digital Web Magazine, at http://www.digital-web.com/ and'
		.' it features a fluid 3-column layout and dynamic/fixed navigation tabs.'
		.' Left column has a fixed size, and the optional 3rd column may be sized according to the page displayed.',
	'description_fr' => 'Ce style est inspir&eacute; de celui de Digital Web Magazine, &agrave; http://www.digital-web.com/ et'
		.' il est offre jusqu\'&agrave; trois colonnes variables, ainsi que des onglets de navigation.'
		.' La colonne de gauche a une taille fixe, tandis que la troisi&egrave;me colonne, optionnelle, peut s\'ajuster &agrave; la page affich&eacute;e.',
	'thumbnail' => 'preview.jpg',
	'home_url' => 'http://www.yetanothercommunitysystem.com/' );
?>