<?php
/**
 * A theme with out of the box implementation of HTML5 and CSS3,
 * and responsive design technique
 *
 * templating based on HTML5 boilerplate (html5boilerplate.com)
 * and knacss style sheet by Raphaël Goetter (knacss.com)
 *
 * features :
 * normalize.css : reset css (https://github.com/necolas/normalize.css)
 * html5bp and knacss style sheets, mixed without conflict
 * modernizr : detection library
 * css3pie : render css3 on old IE browsers (http://css3pie.com/)
 * a 3 columns based template, responsive to small screens
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
defined('YACS') or exit('Script must be included');

// let share this skin
global $skins;
$skins['skins/starterfive'] = array(
	'label_en' => 'Starter Five',
	'label_fr' => 'Starter Five',
	'description_en' => 'A theme with out of the box implementation of HTML5 and CSS3 standards, and responsive design technique',
	'description_fr' => 'Un thème avec une implémentation toute prête des standards HTML5 et CSS3, ainsi qu\'une technique de reponsive design',
	'thumbnail' => 'preview.png',
	'home_url' => 'http://www.yacs.fr' );
?>