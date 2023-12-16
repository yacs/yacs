<?php

/** 
 * Fix column name in sections table
 * 
 * sections_overlay has to be plurial, 
 * because it is related to any sub_sections, not the section itself.
 * Plus the fact that part of the code was expecting the plurial form.
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en']      = 'Fix a column name in table sections';
$local['label_fr']      = 'Correction d\'un nom de colonne dans la table sections';
$local['apply_en']      = 'Execution of the following request : %s';
$local['apply_fr']      = 'Execution de la requete suivante : %s';
$local['success_en']    = 'Fix has been successfully applied :)';
$local['success_fr']    = 'Le correctif a été appliqué avec succès :)';
$local['fail_en']       = 'Failed to apply the fix !';
$local['fail_fr']       = 'Echec de mise en place du correctif !';


// display the goal
echo get_local('label')."<br />\n";

// the reference server to use
include_once $context['path_to_root'].'scripts/parameters.include.php';
if(!isset($context['reference_server']) || !$context['reference_server'])
	$context['reference_server'] = 'www.yacs.fr';

// what to fix
$query = "ALTER TABLE ".SQL::table_name('sections')." CHANGE section_overlay sections_overlay VARCHAR(64)";

// display the query
echo sprintf(get_local('apply'), $query)."<br />\n";

// do the fix
$job = SQL::query($query);


// report
if($job) {
    echo get_local('success')."<br />\n";
} else {
    echo get_local('fail')."<br />\n";
}