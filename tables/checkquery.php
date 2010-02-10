<?php
/**
 * test SQL query
 *
 * @author CityTech
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
include_once '../shared/global.php';
include_once '../shared/surfer.php';


if(isset($_GET['query']) && ($query=urldecode($_GET['query'])) && ($result=& SQL::debug($query,true)))
	echo '&nbsp;&nbsp;&nbsp;<span style="color:#0D801C; background-color:#F8F7CD; font-weight:bold">&nbsp;Valid&nbsp;</span>';
elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo '&nbsp;&nbsp;&nbsp;<span style="color:#FF0000; background-color:#F8F7CD; font-weight:bold">&nbsp;Invalid&nbsp;</span>';
?>