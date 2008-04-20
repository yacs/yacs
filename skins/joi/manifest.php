<?php
/**
 * a nice centered 2-column layout inspired by Joi Ito's web site
 *
 * @link http://joi.ito.com/
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
defined('YACS') or exit('Script must be included');

// let share this skin
global $skins;
$skins['skins/joi'] = array(
	'label_en' => 'Joi',
	'label_fr' => 'Joi',
	'description_en' => 'A nice centered 2-column layout, inspired originally by web design fom Joi Ito ([link]http://joi.ito.com[/link]).'
		.' Derive this skin and change CSS to create your own site.',
	'description_fr' => 'Un joli style centr&eacute; &agrave; 2 colonnes, d\'apr&egrave;s le site web de Joi Ito ([link]http://joi.ito.com[/link]).'
		.' Inspirez-vous de ce style et modifiez le CSS pour cr&eacute;er votre propre site.',
	'thumbnail' => 'preview.jpg',
	'home_url' => 'http://www.yetanothercommunitysystem.com/' );
?>