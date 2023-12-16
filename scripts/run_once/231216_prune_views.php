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
$local['label_en']      = 'Delete activities and comments views in database';
$local['label_fr']      = 'Suppression des vues actitivites et comments dans la base de données';
$local['apply_en']      = 'Execution of the following request : %s';
$local['apply_fr']      = 'Execution de la requete suivante : %s';
$local['success_en']    = 'Queries executed';
$local['success_fr']    = 'Requête executée';
$local['fail_en']       = 'Deletion of views failed';
$local['fail_fr']       = 'Echec de la suppression des vues';


// display the goal
echo get_local('label')."<br />\n";


$views = array(
    'activities_by_anchor_per_month',
    'activities_by_user_per_month',
    'comments_by_anchor_per_month',
    'comments_by_person_per_month');

foreach($views as $v) {
    
    // what to fix
    $query = "DROP VIEW IF EXISTS ".SQL::table_name($v);

    // display the query
    echo sprintf(get_local('apply'), $query)."<br />\n";

    // do the fix
    SQL::query($query);
    
}

$job = true;


// report
if($job) {
    echo get_local('success')."<br />\n";
} else {
    echo get_local('fail')."<br />\n";
}