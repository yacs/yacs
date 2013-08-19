<?php

/**
 * integrate issues
 *
 * This script demonstrates how to use hooks to integrate into the setup script
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
if(count(get_included_files()) < 3) {
	echo 'Script must be included';
	return;
}

// let this module appear in the setup script
global $hooks;
$hooks[] = array(
	'id'		=> 'control/setup.php',
	'type'		=> 'include',
	'script'	=> 'shared/js_css.php',
	'function'	=> 'js_css::setup',
	'label_en'	=> 'Setup table to count js/css files calls',
	'label_fr'	=> 'Cr&eacute;ation de la table pour compter les chargements de fichiers js/css'
	);
?>
