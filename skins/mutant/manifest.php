<?php
/**
 * A base theme for integrator to build a specific one,
 * 
 * Based on a template by Raphael Goetter (Alsacreations)
 * header, footer and 3 columns
 * Uses FlexBox model
 * 
 * /!\ Yacs Netgrabber alpha 3 or upper required /!\
 * 
 * @author Alexis Raimbault
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
defined('YACS') or exit('Script must be included');

// let share this skin
global $skins;
$skins['skins/mutant'] = array(
	'label_en' => 'mutant',
	'label_fr' => 'mutant',
	'description_en' => 'A base theme for integrator to build a specific one',
	'description_fr' => 'Un thème de base pour les intégrateurs, pour en contruire un spécifique',
	'thumbnail' => 'preview.jpg',
	'home_url' => 'http://www.yacs.fr' );
?>