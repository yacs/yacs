<?php
/**
 * add a new filter
 *
 * @author CityTech
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
include_once '../shared/global.php';

// number of tab to add
if(!isset($_GET['tab_row']))
	return;

$tab_row=$_GET['tab_row'];

// hint
$hint = i18n::user('Please enter array name with index like context|technical_area');


$content = BR.BR.'New Tab #'.($tab_row).'<hr>'
	.'<table class="form" border="0" style="border:none;" width="100%">'
	.'<tbody><tr class="odd" style="border:none;"><td style="border:none;" width="200">Filter Title<span style="color:#FF0000">*</span></td><td style="border:none;"><input type="text" name="option_name_new_'.$tab_row.'" id="option_name_new_'.$tab_row.'" value ="" size="55" maxlength="255" /></td></tr>'
	.'<tr class="even" style="border:none;"><td style="border:none;" width="200">Is populate from file ?</td><td style="border:none;">'
		.'<table width="100%" style="border:none;"><tr style="border:none;"><td style="border:none;"><input type="radio" name="is_file_new_'.$tab_row.'"  value ="Yes" id="is_file_new_yes_'.$tab_row.'" onclick="showhide(\''.$tab_row.'\',this.value,\'1\')"/>Yes</td><td width="5" style="border:none;">&nbsp;</td><td style="border:none;"><input type="radio" name="is_file_new_'.$tab_row.'" id="is_file_new_'.$tab_row.'" value ="No"   checked="checked" onclick="showhide(\''.$tab_row.'\',this.value,\'1\')" />No</td></tr></table>'
	.'</td></tr>'
	.'<tr class="odd" style="border:none;"><td style="border:none;" width="200">File path</td><td style="border:none;"><input type="text" name="file_path_new_'.$tab_row.'" id="file_path_new_'.$tab_row.'" value ="" size="55" maxlength="255" disabled style="background-color:#64605B"/><br style="clear: both;"><span class="tiny">'.i18n::user('Provide location of the file like overlays/sgs/parameters.include.php').'</span></td></tr>'
	.'<tr class="even" style="border:none;"><td style="border:none;" width="200">Array Name</td><td style="border:none;">
<input type="text" name="array_name_new_'.$tab_row.'" id="array_name_new_'.$tab_row.'" value ="" size="55" maxlength="255" disabled style="background-color:#64605B"/><br style="clear: both;"><span class="tiny">'.i18n::user('Please enter array name with index like context|technical_area').'</span></td></tr>'
	.'<tr class="even" style="border:none;"><td style="border:none;" >Populate drop-down filter</td><td style="border:none;"><textarea name="option_sql_new_'.$tab_row.'" id="option_sql_new_'.$tab_row.'" rows="15" cols="50" ></textarea><div><input type="button" value="Check Query" name="btncheck" onclick="checkquery2(\''.$tab_row.'\')"><span id="check_message_new_'.$tab_row.'"></span></div> <br style="clear: both;"><span class="tiny">'.i18n::user('Please enter valid SQL for populating drop-down').'</span></td></tr>'
	.'<tr class="odd" style="border:none;"><td style="border:none;" >Filter Alies</td><td style="border:none;"><input type="text" name="table_alies_new_'.$tab_row.'" id="table_alies_new_'.$tab_row.'" value ="" size="55" maxlength="255" /></td></tr>'
	.'<tr class="even" style="border:none;"><td style="border:none;" >Drop-Down Name<span style="color:#FF0000">*</span></td><td style="border:none;"><input type="text" name="option_code_new_'.$tab_row.'" id="option_code_new_'.$tab_row.'" value ="" size="55" maxlength="255" /></td></tr>'
	.'<tr class="odd" style="border:none;"><td style="border:none;" >Filter Order</td><td style="border:none;"><input type="text" name="filter_order_new_'.$tab_row.'" id="filter_order_new_'.$tab_row.'" value ="" size="5" maxlength="3" /></td></tr>'
	.'<tr class="even" style="border:none;"><td style="border:none;" ><span style="color:#FF0000">Remove</span></td><td style="border:none;"><input type="checkbox" name="is_remove_new_'.$tab_row.'" id="is_remove_new_'.$tab_row.'" value ="Yes"  /></td></tr>'
	.'</tbody></table>'
	.'<div id="more_tb_'.($tab_row+1).'"><input type="hidden" name="count_new_tb" value ="'.($tab_row+1).'" id="count_new_tb" size="10" /></div>';

if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $content;

?>