<?php
/**
 * add an approval comment
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// force comment type
$_REQUEST['type'] = 'approval';

// delegate everything else to the regular commenting form
include 'edit.php';

?>