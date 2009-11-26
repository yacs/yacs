<?php
/**
 * configure parameters for the skin
 *
 * @link http://matthewjamestaylor.com/blog/fixed-width-or-liquid-layout.htm The Holy Grail 3 column Liquid Layout or fixed width
 * @link http://www.smashingmagazine.com/2008/03/05/blog-headers-for-free-download/ Beautiful headers provided for free
 * @link http://exploding-boy.com/images/cssmenus2/menus.html tabs provided for free
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../../shared/global.php';
include_once '../../images/image.php'; 	// background handling

// load jscolor library
$context['javascript']['jscolor'] = TRUE;

// where parameters are saved
$parameters_file = 'skins/flexible/parameters.include.php';

// set default values
if(!isset($context['flexible_body_bg']))
	$context['flexible_body_bg'] = '#ffffff';
if(!isset($context['flexible_columns']))
	$context['flexible_columns'] = '1_2_3';

if(!isset($context['flexible_details_color']))
	$context['flexible_details_color'] = '#888888';
if(!isset($context['flexible_details_family']))
	$context['flexible_details_family'] = 'sans-serif';
if(!isset($context['flexible_details_size']))
	$context['flexible_details_size'] = '8px';
if(!isset($context['flexible_details_weight']))
	$context['flexible_details_weight'] = 'normal';

if(!isset($context['flexible_extra_a_bg']))
	$context['flexible_extra_a_bg'] = '';
if(!isset($context['flexible_extra_a_color']))
	$context['flexible_extra_a_color'] = '#336699';
if(!isset($context['flexible_extra_a_decoration']))
	$context['flexible_extra_a_decoration'] = 'underline';
if(!isset($context['flexible_extra_a_family']))
	$context['flexible_extra_a_family'] = 'inherit';
if(!isset($context['flexible_extra_a_size']))
	$context['flexible_extra_a_size'] = 'inherit';
if(!isset($context['flexible_extra_a_weight']))
	$context['flexible_extra_a_weight'] = 'normal';

if(!isset($context['flexible_extra_bg']))
	$context['flexible_extra_bg'] = '#ffff99';
if(!isset($context['flexible_extra_bottom']))
	$context['flexible_extra_bottom'] = 'none';
if(!isset($context['flexible_extra_color']))
	$context['flexible_extra_color'] = '#333333';

if(!isset($context['flexible_extra_dd_bottom']))
	$context['flexible_extra_dd_bottom'] = 'none';
if(!isset($context['flexible_extra_dd_left']))
	$context['flexible_extra_dd_left'] = 'none';
if(!isset($context['flexible_extra_dd_margin']))
	$context['flexible_extra_dd_margin'] = '0 0 1em 0';
if(!isset($context['flexible_extra_dd_padding']))
	$context['flexible_extra_dd_padding'] = '0 6px 6px 6px';
if(!isset($context['flexible_extra_dd_right']))
	$context['flexible_extra_dd_right'] = 'none';
if(!isset($context['flexible_extra_dd_top']))
	$context['flexible_extra_dd_top'] = 'none';

if(!isset($context['flexible_extra_dl_bg']))
	$context['flexible_extra_dl_bg'] = 'transparent';

if(!isset($context['flexible_extra_dt_bg']))
	$context['flexible_extra_dt_bg'] = 'transparent';
if(!isset($context['flexible_extra_dt_bottom']))
	$context['flexible_extra_dt_bottom'] = 'none';
if(!isset($context['flexible_extra_dt_color']))
	$context['flexible_extra_dt_color'] = '#333333';
if(!isset($context['flexible_extra_dt_family']))
	$context['flexible_extra_dt_family'] = 'inherit';
if(!isset($context['flexible_extra_dt_left']))
	$context['flexible_extra_dt_left'] = 'none';
if(!isset($context['flexible_extra_dt_margin']))
	$context['flexible_extra_dt_margin'] = '0';
if(!isset($context['flexible_extra_dt_padding']))
	$context['flexible_extra_dt_padding'] = '0 6px 6px 6px';
if(!isset($context['flexible_extra_dt_right']))
	$context['flexible_extra_dt_right'] = 'none';
if(!isset($context['flexible_extra_dt_size']))
	$context['flexible_extra_dt_size'] = '12px';
if(!isset($context['flexible_extra_dt_top']))
	$context['flexible_extra_dt_top'] = 'none';
if(!isset($context['flexible_extra_dt_weight']))
	$context['flexible_extra_dt_weight'] = 'normal';

if(!isset($context['flexible_extra_family']))
	$context['flexible_extra_family'] = 'inherit';
if(!isset($context['flexible_extra_h_bg']))
	$context['flexible_extra_h_bg'] = '#336699';
if(!isset($context['flexible_extra_h_color']))
	$context['flexible_extra_h_color'] = '#FFFFFF';
if(!isset($context['flexible_extra_h_decoration']))
	$context['flexible_extra_h_decoration'] = 'underline';
if(!isset($context['flexible_extra_left']))
	$context['flexible_extra_left'] = 'none';
if(!isset($context['flexible_extra_padding']))
	$context['flexible_extra_padding'] = '8px 8px 8px 8px';
if(!isset($context['flexible_extra_right']))
	$context['flexible_extra_right'] = 'none';
if(!isset($context['flexible_extra_size']))
	$context['flexible_extra_size'] = '12px';
if(!isset($context['flexible_extra_top']))
	$context['flexible_extra_top'] = 'none';
if(!isset($context['flexible_extra_weight']))
	$context['flexible_extra_weight'] = 'normal';
if(!isset($context['flexible_extra_width']))
	$context['flexible_extra_width'] = '200px';

if(!isset($context['flexible_footer_align']))
	$context['flexible_footer_align'] = 'inherit';
if(!isset($context['flexible_footer_bg']))
	$context['flexible_footer_bg'] = 'transparent';
if(!isset($context['flexible_footer_bottom']))
	$context['flexible_footer_bottom'] = 'none';
if(!isset($context['flexible_footer_color']))
	$context['flexible_footer_color'] = '#333333';
if(!isset($context['flexible_footer_family']))
	$context['flexible_footer_family'] = 'inherit';
if(!isset($context['flexible_footer_height']))
	$context['flexible_footer_height'] = '200px';
if(!isset($context['flexible_footer_left']))
	$context['flexible_footer_left'] = 'none';
if(!isset($context['flexible_footer_padding']))
	$context['flexible_footer_padding'] = '1em';
if(!isset($context['flexible_footer_right']))
	$context['flexible_footer_right'] = 'none';
if(!isset($context['flexible_footer_size']))
	$context['flexible_footer_size'] = 'inherit';
if(!isset($context['flexible_footer_top']))
	$context['flexible_footer_top'] = '1px solid #000';
if(!isset($context['flexible_footer_weight']))
	$context['flexible_footer_weight'] = 'normal';

if(!isset($context['flexible_header_bg']))
	$context['flexible_header_bg'] = '#f3f3f3 url('.$context['url_to_root'].'skins/flexible/headers/m-header-2-m.jpg) no-repeat top center';
if(!isset($context['flexible_header_bottom']))
	$context['flexible_header_bottom'] = 'none';
if(!isset($context['flexible_header_height']))
	$context['flexible_header_height'] = '200px';
if(!isset($context['flexible_header_left']))
	$context['flexible_header_left'] = 'none';
if(!isset($context['flexible_header_right']))
	$context['flexible_header_right'] = 'none';
if(!isset($context['flexible_header_s_color']))
	$context['flexible_header_s_color'] = '#333333';
if(!isset($context['flexible_header_s_family']))
	$context['flexible_header_s_family'] = 'inherit';
if(!isset($context['flexible_header_s_left']))
	$context['flexible_header_s_left'] = '20px';
if(!isset($context['flexible_header_s_size']))
	$context['flexible_header_s_size'] = 'inherit';
if(!isset($context['flexible_header_s_top']))
	$context['flexible_header_s_top'] = '40px';
if(!isset($context['flexible_header_s_weight']))
	$context['flexible_header_s_weight'] = 'normal';
if(!isset($context['flexible_header_t_color']))
	$context['flexible_header_t_color'] = '#333333';
if(!isset($context['flexible_header_t_family']))
	$context['flexible_header_t_family'] = 'inherit';
if(!isset($context['flexible_header_t_left']))
	$context['flexible_header_t_left'] = '20px';
if(!isset($context['flexible_header_t_logo']))
	$context['flexible_header_t_logo'] = 'none';
if(!isset($context['flexible_header_t_size']))
	$context['flexible_header_t_size'] = 'inherit';
if(!isset($context['flexible_header_t_top']))
	$context['flexible_header_t_top'] = '20px';
if(!isset($context['flexible_header_t_weight']))
	$context['flexible_header_t_weight'] = 'normal';
if(!isset($context['flexible_header_top']))
	$context['flexible_header_top'] = 'none';

if(!isset($context['flexible_main_a_bg']))
	$context['flexible_main_a_bg'] = '';
if(!isset($context['flexible_main_a_color']))
	$context['flexible_main_a_color'] = '#336699';
if(!isset($context['flexible_main_a_decoration']))
	$context['flexible_main_a_decoration'] = 'underline';
if(!isset($context['flexible_main_a_family']))
	$context['flexible_main_a_family'] = 'inherit';
if(!isset($context['flexible_main_a_size']))
	$context['flexible_main_a_size'] = 'inherit';
if(!isset($context['flexible_main_a_weight']))
	$context['flexible_main_a_weight'] = 'normal';
if(!isset($context['flexible_main_bg']))
	$context['flexible_main_bg'] = 'transparent';
if(!isset($context['flexible_main_bottom']))
	$context['flexible_main_bottom'] = 'none';
if(!isset($context['flexible_main_color']))
	$context['flexible_main_color'] = '#333333';
if(!isset($context['flexible_main_family']))
	$context['flexible_main_family'] = 'Georgia';
if(!isset($context['flexible_main_h_bg']))
	$context['flexible_main_h_bg'] = '#336699';
if(!isset($context['flexible_main_h_color']))
	$context['flexible_main_h_color'] = '#FFFFFF';
if(!isset($context['flexible_main_h_decoration']))
	$context['flexible_main_h_decoration'] = 'underline';
if(!isset($context['flexible_main_left']))
	$context['flexible_main_left'] = 'none';
if(!isset($context['flexible_main_padding']))
	$context['flexible_main_padding'] = '8px 8px 8px 8px';
if(!isset($context['flexible_main_right']))
	$context['flexible_main_right'] = 'none';
if(!isset($context['flexible_main_size']))
	$context['flexible_main_size'] = '12px';
if(!isset($context['flexible_main_top']))
	$context['flexible_main_top'] = 'none';
if(!isset($context['flexible_main_weight']))
	$context['flexible_main_weight'] = 'normal';

if(!isset($context['flexible_main_h1_bg']))
	$context['flexible_main_h1_bg'] = 'transparent';
if(!isset($context['flexible_main_h1_bottom']))
	$context['flexible_main_h1_bottom'] = 'none';
if(!isset($context['flexible_main_h1_color']))
	$context['flexible_main_h1_color'] = '#FF9834';
if(!isset($context['flexible_main_h1_family']))
	$context['flexible_main_h1_family'] = 'inherit';
if(!isset($context['flexible_main_h1_left']))
	$context['flexible_main_h1_left'] = 'none';
if(!isset($context['flexible_main_h1_margin']))
	$context['flexible_main_h1_margin'] = '0 0 2em 0';
if(!isset($context['flexible_main_h1_padding']))
	$context['flexible_main_h1_padding'] = '0';
if(!isset($context['flexible_main_h1_right']))
	$context['flexible_main_h1_right'] = 'none';
if(!isset($context['flexible_main_h1_size']))
	$context['flexible_main_h1_size'] = '36px';
if(!isset($context['flexible_main_h1_top']))
	$context['flexible_main_h1_top'] = 'none';
if(!isset($context['flexible_main_h1_weight']))
	$context['flexible_main_h1_weight'] = 'normal';

if(!isset($context['flexible_main_h2_bg']))
	$context['flexible_main_h2_bg'] = 'transparent';
if(!isset($context['flexible_main_h2_bottom']))
	$context['flexible_main_h2_bottom'] = 'none';
if(!isset($context['flexible_main_h2_color']))
	$context['flexible_main_h2_color'] = '#FF9834';
if(!isset($context['flexible_main_h2_family']))
	$context['flexible_main_h2_family'] = 'inherit';
if(!isset($context['flexible_main_h2_left']))
	$context['flexible_main_h2_left'] = 'none';
if(!isset($context['flexible_main_h2_margin']))
	$context['flexible_main_h2_margin'] = '1em 0 1em 0';
if(!isset($context['flexible_main_h2_padding']))
	$context['flexible_main_h2_padding'] = '0';
if(!isset($context['flexible_main_h2_right']))
	$context['flexible_main_h2_right'] = 'none';
if(!isset($context['flexible_main_h2_size']))
	$context['flexible_main_h2_size'] = '28px';
if(!isset($context['flexible_main_h2_top']))
	$context['flexible_main_h2_top'] = 'none';
if(!isset($context['flexible_main_h2_weight']))
	$context['flexible_main_h2_weight'] = 'normal';

if(!isset($context['flexible_main_h3_bg']))
	$context['flexible_main_h3_bg'] = 'transparent';
if(!isset($context['flexible_main_h3_bottom']))
	$context['flexible_main_h3_bottom'] = 'none';
if(!isset($context['flexible_main_h3_color']))
	$context['flexible_main_h3_color'] = '#FF9834';
if(!isset($context['flexible_main_h3_family']))
	$context['flexible_main_h3_family'] = 'inherit';
if(!isset($context['flexible_main_h3_left']))
	$context['flexible_main_h3_left'] = 'none';
if(!isset($context['flexible_main_h3_margin']))
	$context['flexible_main_h3_margin'] = '1em 0 0.5em 0';
if(!isset($context['flexible_main_h3_padding']))
	$context['flexible_main_h3_padding'] = '0';
if(!isset($context['flexible_main_h3_right']))
	$context['flexible_main_h3_right'] = 'none';
if(!isset($context['flexible_main_h3_size']))
	$context['flexible_main_h3_size'] = '24px';
if(!isset($context['flexible_main_h3_top']))
	$context['flexible_main_h3_top'] = 'none';
if(!isset($context['flexible_main_h3_weight']))
	$context['flexible_main_h3_weight'] = 'normal';

if(!isset($context['flexible_page_bg']))
	$context['flexible_page_bg'] = 'transparent';

if(!isset($context['flexible_side_a_bg']))
	$context['flexible_side_a_bg'] = '';
if(!isset($context['flexible_side_a_color']))
	$context['flexible_side_a_color'] = '#336699';
if(!isset($context['flexible_side_a_decoration']))
	$context['flexible_side_a_decoration'] = 'underline';
if(!isset($context['flexible_side_a_family']))
	$context['flexible_side_a_family'] = 'inherit';
if(!isset($context['flexible_side_a_size']))
	$context['flexible_side_a_size'] = 'inherit';
if(!isset($context['flexible_side_a_weight']))
	$context['flexible_side_a_weight'] = 'normal';
if(!isset($context['flexible_side_bg']))
	$context['flexible_side_bg'] = '#FFD8B7';
if(!isset($context['flexible_side_bottom']))
	$context['flexible_side_bottom'] = 'none';
if(!isset($context['flexible_side_color']))
	$context['flexible_side_color'] = '#333333';
if(!isset($context['flexible_side_h_bg']))
	$context['flexible_side_h_bg'] = '#336699';
if(!isset($context['flexible_side_h_color']))
	$context['flexible_side_h_color'] = '#FFFFFF';
if(!isset($context['flexible_side_h_decoration']))
	$context['flexible_side_h_decoration'] = 'underline';

if(!isset($context['flexible_side_dd_bottom']))
	$context['flexible_side_dd_bottom'] = 'none';
if(!isset($context['flexible_side_dd_left']))
	$context['flexible_side_dd_left'] = 'none';
if(!isset($context['flexible_side_dd_margin']))
	$context['flexible_side_dd_margin'] = '0 0 1em 0';
if(!isset($context['flexible_side_dd_padding']))
	$context['flexible_side_dd_padding'] = '0 6px 6px 6px';
if(!isset($context['flexible_side_dd_right']))
	$context['flexible_side_dd_right'] = 'none';
if(!isset($context['flexible_side_dd_top']))
	$context['flexible_side_dd_top'] = 'none';

if(!isset($context['flexible_side_dl_bg']))
	$context['flexible_side_dl_bg'] = 'transparent';

if(!isset($context['flexible_side_dt_bg']))
	$context['flexible_side_dt_bg'] = 'transparent';
if(!isset($context['flexible_side_dt_bottom']))
	$context['flexible_side_dt_bottom'] = 'none';
if(!isset($context['flexible_side_dt_color']))
	$context['flexible_side_dt_color'] = '#333333';
if(!isset($context['flexible_side_dt_family']))
	$context['flexible_side_dt_family'] = 'inherit';
if(!isset($context['flexible_side_dt_left']))
	$context['flexible_side_dt_left'] = 'none';
if(!isset($context['flexible_side_dt_margin']))
	$context['flexible_side_dt_margin'] = '0';
if(!isset($context['flexible_side_dt_padding']))
	$context['flexible_side_dt_padding'] = '0 6px 6px 6px';
if(!isset($context['flexible_side_dt_right']))
	$context['flexible_side_dt_right'] = 'none';
if(!isset($context['flexible_side_dt_size']))
	$context['flexible_side_dt_size'] = '12px';
if(!isset($context['flexible_side_dt_top']))
	$context['flexible_side_dt_top'] = 'none';
if(!isset($context['flexible_side_dt_weight']))
	$context['flexible_side_dt_weight'] = 'normal';

if(!isset($context['flexible_side_family']))
	$context['flexible_side_family'] = 'inherit';
if(!isset($context['flexible_side_left']))
	$context['flexible_side_left'] = 'none';
if(!isset($context['flexible_side_padding']))
	$context['flexible_side_padding'] = '8px 8px 8px 8px';
if(!isset($context['flexible_side_right']))
	$context['flexible_side_right'] = 'none';
if(!isset($context['flexible_side_size']))
	$context['flexible_side_size'] = '12px';
if(!isset($context['flexible_side_top']))
	$context['flexible_side_top'] = 'none';
if(!isset($context['flexible_side_weight']))
	$context['flexible_side_weight'] = 'normal';
if(!isset($context['flexible_side_width']))
	$context['flexible_side_width'] = '200px';

if(!isset($context['flexible_tabs_a_color']))
	$context['flexible_tabs_a_color'] = '#666666';
if(!isset($context['flexible_tabs_bg']))
	$context['flexible_tabs_bg'] = 'transparent';
if(!isset($context['flexible_tabs_bg_image']))
	$context['flexible_tabs_bg_image'] = 'tab-left.gif';
if(!isset($context['flexible_tabs_bottom']))
	$context['flexible_tabs_bottom'] = 'none';
if(!isset($context['flexible_tabs_family']))
	$context['flexible_tabs_family'] = 'inherit';
if(!isset($context['flexible_tabs_h_color']))
	$context['flexible_tabs_h_color'] = '#FF9834';
if(!isset($context['flexible_tabs_left']))
	$context['flexible_tabs_left'] = 'none';
if(!isset($context['flexible_tabs_padding']))
	$context['flexible_tabs_padding'] = '0 20px';
if(!isset($context['flexible_tabs_right']))
	$context['flexible_tabs_right'] = 'none';
if(!isset($context['flexible_tabs_size']))
	$context['flexible_tabs_size'] = '10px';
if(!isset($context['flexible_tabs_top']))
	$context['flexible_tabs_top'] = 'none';
if(!isset($context['flexible_tabs_weight']))
	$context['flexible_tabs_weight'] = 'bold';

if(!isset($context['flexible_width']))
	$context['flexible_width'] = '960px';

// some constants

define('DUMMY_TEXT', 'Lorem <a href="#" class="regular">ipsum dolor sit</a> amet, <a href="#" class="current">consectetur adipisicing</a> elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
	.' Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.'
	.' Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


$font_families = array('inherit', 'Georgia', 'Times', 'serif', 'Verdana', 'Arial', 'sans-serif', 'cursive', 'monospace');

$font_sizes = array('inherit', '8px', '10px', '12px', '14px', '16px', '20px', '24px', '28px', '32px', '36px', '40px', '50px');

$text_decorations = array('none', 'underline', 'overline', 'blink');

// the template CSS file
$template_file = 'skins/flexible/template.css';

// the actual CSS file
$styles_file = 'skins/flexible/configured.css';

// load the skin
load_skin('skins');

// page title
$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Configure'), i18n::s('flexible theme'));

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('skins/flexible/configure.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the input form
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'POST')) {

	/**
	 * help to align text
	 */
	function align_helper($name, $sample) {
		global $context;

		$text = '';

		$values = array('inherit', 'left', 'center', 'right', 'justify');
		foreach($values as $value) {

			$checked = '';
			if($context[ $name ] == $value) {
				$checked = ' checked="checked"';
				$text .= JS_PREFIX."$('".$sample."').setStyle({'textAlign': '".$value."'});".JS_SUFFIX;
			}
			$text .= '<input type="radio" name="'.$name.'" size="8" value="'.$value.'" onchange="$(\''.$sample.'\').setStyle({\'textAlign\': this.value})"'.$checked.' />'.$value.' ';
		}

		return $text;
	}

	/**
	 * help to select a background
	 */
	function background_helper($name, $sample, $path='skins/flexible/panels') {
		global $context;

		$text = '<p class="details">'.i18n::s('Select background color first, then background image. The color will appear in the area not covered by the image.').'</p>';

		// transparent
		$checked = '';
		if($context[$name] == 'transparent') {
			$checked = ' checked="checked"';
			$text .= JS_PREFIX."$('".$sample."').setStyle({'background': 'transparent'});".JS_SUFFIX;
		}
		$text .= '<div style="text-align: center; float:left; width: 150px; margin: 0 10px 20px 0; background-color: #ddd"><div style="width: 150px; height: 70px; background: transparent; position:relative;"><div style="position: absolute; top:50%; margin: 0 auto; width:150px">'.i18n::s('Transparent').'</div></div>'
			.'<input type="radio" name="'.$name.'" value="transparent"'.$checked.' onchange="$(\''.$sample.'\').setStyle({\'background\': this.value})" /></div>';

		// referenced several times
		static $count;
		if(!isset($count))
			$count = 1;
		else
			$count++;

		// fixed color
		$checked = '';
		if(substr($context[$name], 0, 1) == '#') {
			if($position = strpos($context[$name], ' ')) {
				$fixed_color = trim(substr($context[$name], 0, $position));
			} else {
				$fixed_color = $context[$name];
				$checked = ' checked="checked"';
				$text .= JS_PREFIX."$('".$sample."').setStyle({'background': '".$fixed_color."'});".JS_SUFFIX;
			}
		} else
			$fixed_color = '';
		$text .= '<div style="text-align: center; float:left; width: 150px; margin: 0 10px 20px 0; background-color: #ddd"><div style="width: 150px; height: 70px; background: transparent; position:relative;"><div style="position: absolute; top:50%; margin: 0 auto; width:150px">'
			.'<input class="color {hash:true,required:false}" name="'.$name.'_color" size="10" value="'.encode_field($fixed_color).'" maxlength="12"  onchange="$(\''.$name.'_handle\').checked=1; $(\''.$name.'_handle\').value=this.value; $(\''.$sample.'\').setStyle({\'background\': this.value})" id="background_fixed_color_'.$count.'" />'
			.'</div></div>'
			.'<input type="radio" name="'.$name.'" value="'.$fixed_color.'"'.$checked.' id="'.$name.'_handle" onchange="this.value = $(\'background_fixed_color_'.$count.'\').value; $(\''.$sample.'\').setStyle({\'background\': this.value})" /></div>';

		// scan files
		if($dir = Safe::opendir($path)) {

			// list files in the skin directory
			$items = array();
			while(($item = Safe::readdir($dir)) !== FALSE) {
				if(($item[0] == '.') || is_dir($context['path_to_root'].$path.'/'.$item))
					continue;
				if(!preg_match('/(\.gif|\.jpeg|\.jpg|\.png)$/i', $item))
					continue;
				$checked = '';
				if(strpos($context[$name], $item)) {
					$checked = ' checked="checked"';
					$text .= JS_PREFIX."$('".$sample."').setStyle({'background': '".$fixed_color." ".Image::as_background($context['url_to_root'].$path.'/'.$item)."'});".JS_SUFFIX;
				}
				$items[] = '<div style="text-align: center; float:left; width: 150px; margin: 0 10px 20px 0; background-color: #ddd"><div style="width: 150px; height: 70px; background: transparent '.Image::as_background($context['url_to_root'].$path.'/'.$item).'">&nbsp;</div>'
					.'<input type="radio" name="'.$name.'" value="'.$fixed_color.' '.Image::as_background($context['url_to_root'].$path.'/'.$item).'"'.$checked.' onchange="this.value = $(\'background_fixed_color_'.$count.'\').value + \' '.Image::as_background($context['url_to_root'].$path.'/'.$item).'\'; $(\''.$sample.'\').setStyle({\'background\': this.value})" />'
					.' '.Skin::build_link('skins/display.php?id='.urlencode($path.'/'.$item), '*', 'help').'</div>';
			}
			Safe::closedir($dir);

			// list items by alphabetical order
			if(@count($items)) {
				natsort($items);
				foreach($items as $item)
					$text .= $item;
			}
		}

		return $text;
	}

	/**
	 * help to change borders
	 */
	function borders_helper($name, $sample=NULL) {
		global $context;

		$updater = '';
		if($sample)
			$updater = ' onchange="$(\''.$sample.'\').setStyle({ \'borderTop\': this.value});"';
		$text =	i18n::s('top').' <input type="text" name="'.$name.'_top" value="'.$context[$name.'_top'].'" size="10"'.$updater.' /> ';

		$updater = '';
		if($sample)
			$updater = ' onchange="$(\''.$sample.'\').setStyle({ \'borderRight\': this.value});"';
		$text .= i18n::s('right').' <input type="text" name="'.$name.'_right" value="'.$context[$name.'_right'].'" size="10"'.$updater.' /> ';

		$updater = '';
		if($sample)
			$updater = ' onchange="$(\''.$sample.'\').setStyle({ \'borderBottom\': this.value});"';
		$text .= i18n::s('bottom').' <input type="text" name="'.$name.'_bottom" value="'.$context[$name.'_bottom'].'" size="10"'.$updater.' /> ';

		$updater = '';
		if($sample)
			$updater = ' onchange="$(\''.$sample.'\').setStyle({ \'borderLeft\': this.value});"';
		$text .= i18n::s('left').' <input type="text" name="'.$name.'_left" value="'.$context[$name.'_left'].'" size="10"'.$updater.' /> <span class="details">(ex: 1px solid #ccc)</span>';

		if($sample)
			$text .= JS_PREFIX.'$(\''.$sample.'\').setStyle({ \'borderTop\': \''.$context[$name.'_top'].'\', \'borderRight\': \''.$context[$name.'_right'].'\', \'borderBottom\': \''.$context[$name.'_bottom'].'\', \'borderLeft\': \''.$context[$name.'_left'].'\'});'.JS_SUFFIX;

		return $text;
	}

	/**
	 * help to change the color
	 */
	function color_helper($name, $sample=NULL) {
		global $context;

		$updater = '';
		if($sample)
			$updater = ' onchange="$(\''.$sample.'\').setStyle({ \'color\': this.value});"';
		$text =	'<input class="color {hash:true,required:false}" name="'.$name.'" size="8" value="'.encode_field($context[$name]).'" maxlength="12" '.$updater.' />';

		if($sample)
			$text .= JS_PREFIX.'$(\''.$sample.'\').setStyle({\'color\': \''.$context[$name].'\'});'.JS_SUFFIX;

		return $text;
	}

	/**
	 * help to select an image
	 */
	function image_helper($name, $sample, $path='skins/flexible/logos', $toggle='') {
		global $context;

		$text = '';

		// none
		$checked = '';
		if($context[$name] == 'none') {
			$checked = ' checked="checked"';
			$text .= JS_PREFIX."$('".$sample."').setStyle({'display': 'none'});".JS_SUFFIX;
		}
		$to_toggle = '';
		if($toggle)
			$to_toggle = '$(\''.$toggle.'\').setStyle({\'display\': \'inline\'})';
		$text .= '<div style="text-align: center; float:left; width: 150px; margin: 0 10px 20px 0; background-color: #ddd"><div style="width: 150px; height: 70px; background: transparent; position:relative;"><div style="position: absolute; top:50%; margin: 0 auto; width:150px">'.i18n::s('None').'</div></div>'
			.'<input type="radio" name="'.$name.'" value="none"'.$checked.' onchange="$(\''.$sample.'\').setStyle({\'display\': \'none\'});'.$to_toggle.'" /></div>';

		// scan files
		if($dir = Safe::opendir($path)) {

			// list files in the skin directory
			$items = array();
			while(($item = Safe::readdir($dir)) !== FALSE) {
				if(($item[0] == '.') || is_dir($context['path_to_root'].$path.'/'.$item))
					continue;
				if(!preg_match('/(\.gif|\.jpeg|\.jpg|\.png)$/i', $item))
					continue;
				$to_toggle = '';
				if($toggle)
					$to_toggle = '$(\''.$toggle.'\').setStyle({\'display\': \'none\'})';
				$checked = '';
				if(strpos($context[$name], $item)) {
					$checked = ' checked="checked"';
					$text .= JS_PREFIX."$('".$sample."').src = '".$context['url_to_root'].$path.'/'.$item."';$('".$sample."').setStyle({'display': 'inline'});".$to_toggle.JS_SUFFIX;
				}
				$items[] = '<div style="text-align: center; float:left; width: 150px; margin: 0 10px 20px 0; background-color: #ddd"><div style="width: 150px; height: 70px; background: transparent url('.$context['url_to_root'].$path.'/'.$item.') no-repeat">&nbsp;</div>'
					.'<input type="radio" name="'.$name.'" value="'.$context['url_to_root'].$path.'/'.$item.'"'.$checked.' onchange="$(\''.$sample.'\').src = \''.$context['url_to_home'].$context['url_to_root'].$path.'/'.$item.'\';$(\''.$sample.'\').setStyle({\'display\': \'inline\'});'.$to_toggle.'" /></div>';
			}
			Safe::closedir($dir);

			// list items by alphabetical order
			if(@count($items)) {
				natsort($items);
				foreach($items as $item)
					$text .= $item;
			}
		}

		return $text;
	}

	/**
	 * help to change some property
	 */
	function property_helper($name, $property='padding', $sample=NULL) {
		global $context;

		$updater = '';
		if($sample)
			$updater = ' onchange="$(\''.$sample.'\').setStyle({ \''.$property.'\': this.value});"';
		$text =	'<input type="text" name="'.$name.'_'.$property.'" value="'.$context[$name.'_'.$property].'" size="20"'.$updater.' />';

		if($sample)
			$text .= JS_PREFIX.'$(\''.$sample.'\').setStyle({\''.$property.'\': \''.$context[$name.'_'.$property].'\'});'.JS_SUFFIX;

		return $text;
	}

	/**
	 * prepare radio buttons
	 */
	function radio_helper($name, $values, $sample, $style) {
		global $context;

		$text = '';

		foreach($values as $value) {

			// transcode some values
			if($value == 'Georgia')
				$actual_value = 'Georgia, serif';
			elseif($value == 'Verdana')
				$actual_value = 'Verdana, Geneva, sans-serif';
			else
				$actual_value = $value;

			$checked = '';
			if($context[ $name ] == $actual_value) {
				$checked = ' checked="checked"';
				$text .= JS_PREFIX."$$('".$sample."').each(function(item){item.setStyle({'".$style."': '".$actual_value."'})}.bind(this));".JS_SUFFIX;
			}
			$text .= '<input type="radio" name="'.$name.'" size="8" value="'.$actual_value.'" onchange="$$(\''.$sample.'\').each(function(item){item.setStyle({\''.$style.'\': this.value})}.bind(this))"'.$checked.' />'.$value.' ';
		}

		return $text;
	}

	/**
	 * prepare a selection list
	 */
	function select_helper($name, $values, $sample, $style) {
		global $context;

		$text = '';
		foreach($values as $value) {

			// transcode some values
			if($value == 'Georgia')
				$actual_value = 'Georgia, serif';
			elseif($value == 'Verdana')
				$actual_value = 'Verdana, Geneva, sans-serif';
			else
				$actual_value = $value;

			$checked = '';
			if($context[ $name ] == $actual_value) {
				$checked = ' selected="selected"';
				$text .= JS_PREFIX."$$('".$sample."').each(function(item){item.setStyle({'".$style."': '".$actual_value."'})}.bind(this));".JS_SUFFIX;
			}
			$text .= '<option value="'.$actual_value.'"'.$checked.' />'.$value.'</option>';
		}

		return '<select name="'.$name.'" onchange="$$(\''.$sample.'\').each(function(item){item.setStyle({\''.$style.'\': this.value})}.bind(this))">'.$text.'</select>';
	}

	// load current parameters, if any
	Safe::load($parameters_file);

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';
	$panels = array();
	$fields = array();

	//
	// page setup  ////////////////////////////////////////////
	//
	$text = '';

	// width
	$label = i18n::s('Width');
	if(!isset($context['flexible_width']) || ($context['flexible_width'] == 'fluid'))
		$checked = ' checked="checked"';
	else
		$checked = '';
	$cell1 = '<div style="text-align: center"><a href="#" onclick="$(\'fluid\').checked=1; return false;"><img src="configuration/fluid.gif" /></a>'
		.BR.'<input type="radio" name="flexible_width"'.$checked.' id="fluid" value="fluid" />'.BR.i18n::s('Fluid').'</div>';
	if(isset($context['flexible_width']) && ($context['flexible_width'] == '960px'))
		$checked = ' checked="checked"';
	else
		$checked = '';
	$cell2 = '<div style="text-align: center"><a href="#" onclick="$(\'960px\').checked=1; return false;"><img src="configuration/fixed.gif" /></a>'
		.BR.'<input type="radio" name="flexible_width"'.$checked.' id="960px" value="960px" />'.BR.Skin::build_link('http://960.gs/', i18n::s('960px')).'</div>';
	if(isset($context['flexible_width']) && ($context['flexible_width'] == '850px'))
		$checked = ' checked="checked"';
	else
		$checked = '';
	$cell3 = '<div style="text-align: center"><a href="#" onclick="$(\'850px\').checked=1; return false;"><img src="configuration/fixed.gif" /></a>'
		.BR.'<input type="radio" name="flexible_width"'.$checked.' id="850px" value="850px" />'.BR.i18n::s('850px').'</div>';
	if(isset($context['flexible_width']) && ($context['flexible_width'] == '640px'))
		$checked = ' checked="checked"';
	else
		$checked = '';
	$cell4 = '<div style="text-align: center"><a href="#" onclick="$(\'640px\').checked=1; return false;"><img src="configuration/fixed.gif" /></a>'
		.BR.'<input type="radio" name="flexible_width"'.$checked.' id="640px" value="640px" />'.BR.i18n::s('640px').'</div>';
	$input = Skin::layout_horizontally($cell1, $cell2, $cell3, $cell4);
	$fields[] = array($label, $input);

	// columns
	$label = i18n::s('Columns');
	if(!isset($context['flexible_columns']) || ($context['flexible_columns'] == '1_2_3'))
		$checked = ' checked="checked"';
	else
		$checked = '';
	$cell1 = '<div style="text-align: center"><a href="#" onclick="$(\'1_2_3\').checked=1; return false;"><img src="configuration/1_2_3.gif" /></a>'
		.BR.'<input type="radio" name="flexible_columns"'.$checked.' id="1_2_3" value="1_2_3" />'.BR.i18n::s('1-2-3').'</div>';
	if(isset($context['flexible_columns']) && ($context['flexible_columns'] == '3_2_1'))
		$checked = ' checked="checked"';
	else
		$checked = '';
	$cell2 = '<div style="text-align: center"><a href="#" onclick="$(\'3_2_1\').checked=1; return false;"><img src="configuration/3_2_1.gif" /></a>'
		.BR.'<input type="radio" name="flexible_columns"'.$checked.' id="3_2_1" value="3_2_1" />'.BR.i18n::s('3-2-1').'</div>';
	if(isset($context['flexible_columns']) && ($context['flexible_columns'] == '2_3_1'))
		$checked = ' checked="checked"';
	else
		$checked = '';
	$cell3 = '<div style="text-align: center"><a href="#" onclick="$(\'2_3_1\').checked=1; return false;"><img src="configuration/2_3_1.gif" /></a>'
		.BR.'<input type="radio" name="flexible_columns"'.$checked.' id="2_3_1" value="2_3_1" />'.BR.i18n::s('2-3-1').'</div>';
	if(isset($context['flexible_columns']) && ($context['flexible_columns'] == '2_1_3'))
		$checked = ' checked="checked"';
	else
		$checked = '';
	$cell4 = '<div style="text-align: center"><a href="#" onclick="$(\'2_1_3\').checked=1; return false;"><img src="configuration/2_1_3.gif" /></a>'
		.BR.'<input type="radio" name="flexible_columns"'.$checked.' id="2_1_3" value="2_1_3" />'.BR.i18n::s('2-1-3').'</div>';
	$input = Skin::layout_horizontally($cell1, $cell2, $cell3, $cell4);
	$fields[] = array($label, $input);

	// add to the form
	$text .= Skin::build_form($fields);
	$fields = array();

	// text sample
	$text .= '<div id="b_sample" style="height:200px; width: 98%; border: 1px solid #ccc; padding: 0; margin: 0 0 1em 0; position:relative; overflow: hidden;"><div id="bp_sample" style="margin: 80px 80px 0; height: 118px">&nbsp;</div></div>';

	// body background
	$text .= Skin::build_folded_box(i18n::s('Body background'), background_helper('flexible_body_bg', 'b_sample', 'skins/flexible/headers')
		.'<br style="clear: left" />'.Skin::build_link('skins/flexible/upload.php?directory=headers', i18n::s('Add a file'), 'span'));

	// page background
	$text .= Skin::build_folded_box(i18n::s('Page background'), background_helper('flexible_page_bg', 'bp_sample', 'skins/flexible/pages')
		.'<br style="clear: left" />'.Skin::build_link('skins/flexible/upload.php?directory=pages', i18n::s('Add a file'), 'span'));

	// finalize this panel
	$panels[] = array('p', i18n::s('Page'), 'p_panel', $text);

	//
	// header parameters ////////////////////////////////////////////
	//

	// minimum site parameters
	if(!$context['site_name'])
		$context['site_name'] = i18n::s('Site name');
	if(!$context['site_slogan'])
		$context['site_slogan'] = i18n::s('Site slogan');

	// text sample
	$text = '<div style="height:120px; width: 98%; border: 1px solid #ccc; padding: 0; margin: 0 0 1em 0; position:relative; overflow: hidden;"><div id="h_sample"><p id="ht_sample" style="position: absolute; top: 20px; left: 10px"><span id="hx_sample">'.$context['site_name'].'</span><img id="hi_sample" /></p><p id="hs_sample" style="position: absolute; top: 40px; left: 10px">'.$context['site_slogan'].'</p></div></div>';

	// logo image
	$text .= Skin::build_folded_box(i18n::s('Logo'), image_helper('flexible_header_t_logo', 'hi_sample', 'skins/flexible/logos', 'hx_sample')
		.'<br style="clear: left" />'.Skin::build_link('skins/flexible/upload.php?directory=logos', i18n::s('Add a file'), 'span'));

	// font
	$label = i18n::s('Font');
	$input = select_helper('flexible_header_t_family', $font_families, 'p#ht_sample', 'fontFamily')
		.' '.select_helper('flexible_header_t_size', $font_sizes, 'p#ht_sample', 'fontSize')
		.' '.select_helper('flexible_header_t_weight', array('normal', 'bold'), 'p#ht_sample', 'fontWeight');
	$fields[] = array($label, $input);

	// colors
	$fields[] = array(i18n::s('Color'), color_helper('flexible_header_t_color', 'ht_sample'));

	// position
	$fields[] = array(i18n::s('Position'), i18n::s('top').' '.property_helper('flexible_header_t', 'top', 'ht_sample')
		.' '.i18n::s('left').' '.property_helper('flexible_header_t', 'left', 'ht_sample'));

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Title'), Skin::build_form($fields));
	$fields = array();

	// font
	$label = i18n::s('Font');
	$input = select_helper('flexible_header_s_family', $font_families, 'p#hs_sample', 'fontFamily')
		.' '.select_helper('flexible_header_s_size', $font_sizes, 'p#hs_sample', 'fontSize')
		.' '.select_helper('flexible_header_s_weight', array('normal', 'bold'), 'p#hs_sample', 'fontWeight');
	$fields[] = array($label, $input);

	// colors
	$fields[] = array(i18n::s('Color'), color_helper('flexible_header_s_color', 'hs_sample'));

	// position
	$fields[] = array(i18n::s('Position'), i18n::s('top').' '.property_helper('flexible_header_s', 'top', 'hs_sample')
		.' '.i18n::s('left').' '.property_helper('flexible_header_s', 'left', 'hs_sample'));

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Slogan'), Skin::build_form($fields));
	$fields = array();

	// the background
	$text .= Skin::build_folded_box(i18n::s('Background'), background_helper('flexible_header_bg', 'h_sample', 'skins/flexible/headers')
		.'<br style="clear: left" />'.Skin::build_link('skins/flexible/upload.php?directory=headers', i18n::s('Add a file'), 'span'));

	// height
	$fields[] = array(i18n::s('Height'), property_helper('flexible_header', 'height', 'h_sample'));

	// borders
	$fields[] = array(i18n::s('Borders'), borders_helper('flexible_header', 'h_sample'));

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Disposition'), Skin::build_form($fields));
	$fields = array();

	// finalize this panel
	$panels[] = array('h', i18n::s('Header'), 'h_panel', $text);

	//
	// tabs parameters ////////////////////////////////////////////
	//
	$text = '';

	// visual sample
	$text = '<div style="height:50px; width: 450px; border: 1px solid #ccc; margin-bottom: 1em; padding: 1em 0; overflow: hidden;">'
		.'<div id="t_sample" class="tabs" style="position: static;"><ul id="tl_sample">'."\n"
		.'	<li><a href="#" title="Link 1" class="regular"><span>Link 1</span></a></li>'."\n"
		.'	<li><a href="#" title="Link 2" class="current"><span>Link 2</span></a></li>'."\n"
		.'	<li><a href="#" title="Link 3" class="regular"><span>Link 3</span></a></li>'."\n"
		.'	<li><a href="#" title="Longer Link Text" class="regular"><span>Longer Link Text</span></a></li>'."\n"
		.'	<li><a href="#" title="Link 5" class="regular"><span>Link 5</span></a></li>'."\n"
		.'	</ul></div>'."\n"
		.'</div>';

	// font
	$label = i18n::s('Font');
	$input = select_helper('flexible_tabs_family', $font_families, 'div#t_sample a span', 'fontFamily')
		.' '.select_helper('flexible_tabs_size', $font_sizes, 'div#t_sample a span', 'fontSize')
		.' '.select_helper('flexible_tabs_weight', array('normal', 'bold'), 'div#t_sample a span', 'fontWeight');
	$fields[] = array($label, $input);

	// color of inactive link
	$label = i18n::s('Link color');
	$input = '<input class="color {hash:true,required:false}" name="flexible_tabs_a_color" size="10" value="'.encode_field($context['flexible_tabs_a_color']).'" maxlength="8" onchange="$$(\'div#t_sample a.regular span\').each(function(link){link.setStyle({ \'color\': this.value})}.bind(this));" />';
	$fields[] = array($label, $input);
	$text .= JS_PREFIX."$$('div#t_sample a.regular span').each(function(link){link.setStyle({'color': '".$context['flexible_tabs_a_color']."'})});".JS_SUFFIX;

	// color of hovered link
	$label = i18n::s('Hover color');
	$input = '<input class="color {hash:true,required:false}" name="flexible_tabs_h_color" size="10" value="'.encode_field($context['flexible_tabs_h_color']).'" maxlength="8" onchange="$$(\'div#t_sample a.current span\').each(function(link){link.setStyle({ \'color\': this.value})}.bind(this));" />';
	$fields[] = array($label, $input);
	$text .= JS_PREFIX."$$('div#t_sample a.current span').each(function(link){link.setStyle({'color': '".$context['flexible_tabs_h_color']."'})});".JS_SUFFIX;

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Text'), Skin::build_form($fields));
	$fields = array();

	// tabs selection
	$input = Skin::table_prefix('layout');

	// use actual images for tabs background
	if($dir = Safe::opendir('skins/flexible/tabs')) {

		// list files in the skin directory
		$items = array();
		while(($item = Safe::readdir($dir)) !== FALSE) {
			if(($item[0] == '.') || is_dir('../skins/flexible/tabs/'.$item))
				continue;
			if(!preg_match('/(\.gif|\.jpeg|\.jpg|\.png)$/i', $item))
				continue;
			if(!$position = strpos($item, '-left'))
				continue;

			$name = substr($item, 0, $position);

			// add styles for these tabs
			$context['page_header'] .= '<style type="text/css" media="screen">'."\n"
				.'body div.tabs_'.$name.' { /* tweak normal positioning */'."\n"
				.'	position: static;'."\n"
				.'	height: 3em;'."\n"
				.'}'."\n"
				."\n"
				.'div.tabs_'.$name.' ul li a { /* tab top left corner */'."\n"
				.'	color: '.$context['flexible_tabs_a_color'].';'."\n"
				.'	background-image: url("tabs/'.$item.'");'."\n"
				.'}'."\n"
				."\n"
				.'div.tabs_'.$name.' ul li a span { /* tab top right corner */'."\n"
				.'	background-image: url("tabs/'.str_replace('-left', '-right', $item).'");'."\n"
				.'}'."\n"
				.'</style>'."\n";

			// prepare fake tabs
			$tabs = '<div style="position: relative"><div class="tabs tabs_'.$name.'">'."\n"
				.'<ul>'."\n"
				.'	<li><a href="#" title="Link 1"><span>Link 1</span></a></li>'."\n"
				.'	<li><a href="#" title="Link 2"><span>Link 2</span></a></li>'."\n"
				.'	<li><a href="#" title="Link 3"><span>Link 3</span></a></li>'."\n"
				.'	<li><a href="#" title="Longer Link Text"><span>Longer Link Text</span></a></li>'."\n"
				.'	<li><a href="#" title="Link 5" class="current"><span>Link 5</span></a></li>'."\n"
				.'	<li><a href="#" title="Link 6"><span>Link 6</span></a></li>'."\n"
				.'	<li><a href="#" title="Link 7"><span>Link 7</span></a></li>'."\n"
				.'	</ul>'."\n"
				.'</div></div>'."\n";

			$checked = '';
			if($context['flexible_tabs_bg_image'] == $item) {
				$checked = ' checked="checked"';
				$text .= JS_PREFIX.'$$(\'div#t_sample a\').each(function(link){link.setStyle({ \'backgroundImage\': \'url(tabs/'.$item.')\'})}.bind(this));$$(\'div#t_sample a span\').each(function(link){link.setStyle({ \'backgroundImage\': \'url(tabs/'.str_replace('-left', '-right', $item).')\'})}.bind(this));'.JS_SUFFIX;
			}
			$items[] = '<tr><td class="west"><input type="radio" name="flexible_tabs_bg_image" value="'.$item.'"'.$checked.' onchange="$$(\'div#t_sample a\').each(function(link){link.setStyle({ \'backgroundImage\': \'url(tabs/'.$item.')\'})}.bind(this));$$(\'div#t_sample a span\').each(function(link){link.setStyle({ \'backgroundImage\': \'url(tabs/'.str_replace('-left', '-right', $item).')\'})}.bind(this));" /></td>'
				.'<td class="east">'.$tabs.'</td></tr>'."\n";
		}
		Safe::closedir($dir);

		// list items by alphabetical order
		if(@count($items)) {
			natsort($items);
			foreach($items as $item)
				$input .= $item;
		}
	}
	// style 'none'
// 	$context['page_header'] .= '<style type="text/css" media="screen">'."\n"
// 		.'body div.tabs_none ul li a { /* a link */'."\n"
// 		.'	font-family: sans-serif;'."\n"
// 		.'	font-size: 10px;'."\n"
// 		.'	font-weight: bold;'."\n"
// 		.'	line-height: 12px;'."\n"
// 		.'	background: transparent;'."\n"
// 		.'	background-image: none;'."\n"
// 		.'	margin: 0 10px 4px;'."\n"
// 		.'	padding-bottom: 0;'."\n"
// 		.'	border-bottom: 4px solid transparent;'."\n"
// 		.'}'."\n"
// 		."\n"
// 		.'body div.tabs_none ul li a:hover,'."\n"
// 		.'body div.tabs_none ul li a.current { /* an active link */'."\n"
// 		.'	margin: 0 10px 4px;'."\n"
// 		.'	padding-bottom: 0;'."\n"
// 		.'	border-bottom: 4px solid #666;'."\n"
// 		.'}'."\n"
// 		."\n"
// 		.'body div.tabs_none ul li a:hover span,'."\n"
// 		.'body div.tabs_none ul li a.current span { /* an active link */'."\n"
// 		.'	color: '.$context['flexible_tabs_h_color'].';'."\n"
// 		.'}'."\n"
// 		."\n"
// 		.'body div.tabs_none ul li a span { /* inside a link */'."\n"
// 		.'	color: '.$context['flexible_tabs_a_color'].';'."\n"
// 		.'	background: transparent;'."\n"
// 		.'	background-image: none;'."\n"
// 		.'}'."\n"
// 		.'body div.tabs_none ul { /* one line below the list */'."\n"
// 		.'	border-bottom: 1px solid #888;'."\n"
// 		.'	color: '.$context['flexible_tabs_a_color'].';'."\n"
// 		.'	margin: 0;'."\n"
// 		.'	padding: 0 0 13px 10px;'."\n"
// 		.'	height: 12px;'."\n"
// 		.'}'."\n"
// 		.'body div.tabs_none { /* div containing tabs */'."\n"
// 		.'	background: transparent;'."\n"
// 		.'	background-image: none;'."\n"
// 		.'}'."\n"
// 		."\n"
// 		.'</style>'."\n";

	// prepare fake tabs
// 	$tabs = '<div class="tabs tabs_none">'."\n"
// 		.'<ul>'."\n"
// 		.'	<li><a href="#" title="Link 1"><span>Link 1</span></a></li>'."\n"
// 		.'	<li><a href="#" title="Link 2"><span>Link 2</span></a></li>'."\n"
// 		.'	<li><a href="#" title="Link 3"><span>Link 3</span></a></li>'."\n"
// 		.'	<li><a href="#" title="Longer Link Text"><span>Longer Link Text</span></a></li>'."\n"
// 		.'	<li><a href="#" title="Link 5" class="current"><span>Link 5</span></a></li>'."\n"
// 		.'	<li><a href="#" title="Link 6"><span>Link 6</span></a></li>'."\n"
// 		.'	<li><a href="#" title="Link 7"><span>Link 7</span></a></li>'."\n"
// 		.'	</ul>'."\n"
// 		.'</div>'."\n";
//
// 	$checked = '';
// 	if($context['flexible_tabs_bg_image'] == 'none')
// 		$checked = ' checked="checked"';
// 	$input .= '<tr><td class="west"><input type="radio" name="flexible_tabs_bg_image" value="none"'.$checked.' /></td>'
// 		.'<td class="east">'.$tabs.'</td></tr>'."\n";

	// style 'contrasted'
// 	$context['page_header'] .= '<style type="text/css" media="screen">'."\n"
// 		.'body div.tabs_contrasted ul li a { /* a link */'."\n"
// 		.'	font-family: sans-serif;'."\n"
// 		.'	font-size: 10px;'."\n"
// 		.'	font-weight: bold;'."\n"
// 		.'	line-height: 12px;'."\n"
// 		.'	background: transparent;'."\n"
// 		.'	background-image: none;'."\n"
// 		.'	margin: 0 10px 4px;'."\n"
// 		.'	padding-bottom: 0;'."\n"
// 		.'	border-bottom: 4px solid transparent;'."\n"
// 		.'}'."\n"
// 		."\n"
// 		.'body div.tabs_contrasted ul li a span { /* inside a link */'."\n"
// 		.'	color: '.$context['flexible_tabs_a_color'].';'."\n"
// 		.'	background: transparent;'."\n"
// 		.'	background-image: none;'."\n"
// 		.'}'."\n"
// 		."\n"
// 		.'body div.tabs_contrasted ul li a:hover,'."\n"
// 		.'body div.tabs_contrasted ul li a.current { /* an active link */'."\n"
// 		.'	margin: 0 10px 4px;'."\n"
// 		.'	padding-bottom: 0;'."\n"
// 		.'	border-bottom: 4px solid '.$context['flexible_tabs_h_color'].';'."\n"
// 		.'}'."\n"
// 		."\n"
// 		.'body div.tabs_contrasted ul li a:hover span,'."\n"
// 		.'body div.tabs_contrasted ul li a.current span { /* an active link */'."\n"
// 		.'	color: '.$context['flexible_tabs_h_color'].';'."\n"
// 		.'}'."\n"
// 		."\n"
// 		.'body div.tabs_contrasted ul { /* one line below the list */'."\n"
// 		.'	border-bottom: 1px solid #888;'."\n"
// 		.'	color: '.$context['flexible_tabs_a_color'].';'."\n"
// 		.'	margin: 0;'."\n"
// 		.'	padding: 0 0 13px 10px;'."\n"
// 		.'	height: 12px;'."\n"
// 		.'}'."\n"
// 		.'body div.tabs_contrasted { /* div containing tabs */'."\n"
// 		.'	background: transparent;'."\n"
// 		.'	background-image: none;'."\n"
// 		.'}'."\n"
// 		."\n"
// 		.'</style>'."\n";

	// prepare fake tabs
// 	$tabs = '<div class="tabs tabs_contrasted">'."\n"
// 		.'<ul>'."\n"
// 		.'	<li><a href="#" title="Link 1"><span>Link 1</span></a></li>'."\n"
// 		.'	<li><a href="#" title="Link 2"><span>Link 2</span></a></li>'."\n"
// 		.'	<li><a href="#" title="Link 3"><span>Link 3</span></a></li>'."\n"
// 		.'	<li><a href="#" title="Longer Link Text"><span>Longer Link Text</span></a></li>'."\n"
// 		.'	<li><a href="#" title="Link 5" class="current"><span>Link 5</span></a></li>'."\n"
// 		.'	<li><a href="#" title="Link 6"><span>Link 6</span></a></li>'."\n"
// 		.'	<li><a href="#" title="Link 7"><span>Link 7</span></a></li>'."\n"
// 		.'	</ul>'."\n"
// 		.'</div>'."\n";
//
// 	$checked = '';
// 	if($context['flexible_tabs_bg_image'] == 'contrasted')
// 		$checked = ' checked="checked"';
// 	$input .= '<tr><td class="west"><input type="radio" name="flexible_tabs_bg_image" value="contrasted"'.$checked.' /></td>'
// 		.'<td class="east">'.$tabs.'</td></tr>'."\n";

	// style 'inverted'
// 	$context['page_header'] .= '<style type="text/css" media="screen">'."\n"
// 		.'body div.tabs_inverted ul li a { /* a link */'."\n"
// 		.'	font-family: sans-serif;'."\n"
// 		.'	font-size: 10px;'."\n"
// 		.'	font-weight: bold;'."\n"
// 		.'	line-height: 12px;'."\n"
// 		.'	background: transparent;'."\n"
// 		.'	background-image: none;'."\n"
// 		.'	margin: 0 10px 4px;'."\n"
// 		.'	padding-bottom: 0;'."\n"
// 		.'	border-bottom: 4px solid transparent;'."\n"
// 		.'}'."\n"
// 		."\n"
// 		.'body div.tabs_inverted ul li a span { /* inside a link */'."\n"
// 		.'	color: '.$context['flexible_tabs_a_color'].';'."\n"
// 		.'	background: transparent;'."\n"
// 		.'	background-image: none;'."\n"
// 		.'}'."\n"
// 		."\n"
// 		.'body div.tabs_inverted ul li a.current { /* an active link */'."\n"
// 		.'	margin: 0 10px 4px;'."\n"
// 		.'	padding-bottom: 0;'."\n"
// 		.'	border-bottom: 4px solid '.$context['flexible_tabs_a_color'].';'."\n"
// 		.'}'."\n"
// 		."\n"
// 		.'body div.tabs_inverted ul li a:hover { /* hovering the link */'."\n"
// 		.'	border-color: '.$context['flexible_tabs_h_color'].';'."\n"
// 		.'}'."\n"
// 		."\n"
// 		.'body div.tabs_inverted ul li a.current span,'."\n"
// 		.'body div.tabs_inverted ul li a:hover span { /* hovering the link */'."\n"
// 		.'	color: '.$context['flexible_tabs_h_color'].';'."\n"
// 		.'}'."\n"
// 		."\n"
// 		.'body div.tabs_inverted ul { /* one line below the list */'."\n"
// 		.'	color: '.$context['flexible_tabs_a_color'].';'."\n"
// 		.'	border-bottom: 1px solid #888;'."\n"
// 		.'	margin: 0;'."\n"
// 		.'	padding: 0 0 13px 10px;'."\n"
// 		.'	height: 12px;'."\n"
// 		.'}'."\n"
// 		.'body div.tabs_inverted { /* div containing tabs */'."\n"
// 		.'	background: transparent;'."\n"
// 		.'	background-image: none;'."\n"
// 		.'}'."\n"
// 		."\n"
// 		.'</style>'."\n";

	// prepare fake tabs
// 	$tabs = '<div class="tabs tabs_inverted">'."\n"
// 		.'<ul>'."\n"
// 		.'	<li><a href="#" title="Link 1"><span>Link 1</span></a></li>'."\n"
// 		.'	<li><a href="#" title="Link 2"><span>Link 2</span></a></li>'."\n"
// 		.'	<li><a href="#" title="Link 3"><span>Link 3</span></a></li>'."\n"
// 		.'	<li><a href="#" title="Longer Link Text"><span>Longer Link Text</span></a></li>'."\n"
// 		.'	<li><a href="#" title="Link 5" class="current"><span>Link 5</span></a></li>'."\n"
// 		.'	<li><a href="#" title="Link 6"><span>Link 6</span></a></li>'."\n"
// 		.'	<li><a href="#" title="Link 7"><span>Link 7</span></a></li>'."\n"
// 		.'	</ul>'."\n"
// 		.'</div>'."\n";
//
// 	$checked = '';
// 	if($context['flexible_tabs_bg_image'] == 'inverted')
// 		$checked = ' checked="checked"';
// 	$input .= '<tr><td class="west"><input type="radio" name="flexible_tabs_bg_image" value="inverted"'.$checked.' /></td>'
// 		.'<td class="east">'.$tabs.'</td></tr>'."\n";

	// nothing more in the table
	$input .= '</table>';

	// select tabs in a folded box
	$text .= Skin::build_box(i18n::s('Tabs'), $input, 'folded');

	// tabs background
	$text .= Skin::build_folded_box(i18n::s('Background'), background_helper('flexible_tabs_bg', 't_sample')
		.'<br style="clear: left" />'.Skin::build_link('skins/flexible/upload.php?directory=panels', i18n::s('Add a file'), 'span'));

	// padding
	$fields[] = array(i18n::s('Padding'), property_helper('flexible_tabs', 'padding', 'tl_sample'));

	// borders
	$fields[] = array(i18n::s('Borders'), borders_helper('flexible_tabs', 't_sample'));

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Disposition'), Skin::build_form($fields));
	$fields = array();

	// finalize this panel
	$panels[] = array('t', i18n::s('Tabs'), 't_panel', $text);

	//
	// content panel ////////////////////////////////////////////
	//
	$text = '';

	// text sample
	$text .= '<p id="p_sample"><span>'.i18n::s('Sample text').' '.DUMMY_TEXT.'</span></p>';

	// font
	$label = i18n::s('Font');
	$input = select_helper('flexible_main_family', $font_families, 'p#p_sample', 'fontFamily')
		.' '.select_helper('flexible_main_size', $font_sizes, 'p#p_sample', 'fontSize')
		.' '.select_helper('flexible_main_weight', array('normal', 'bold'), 'p#p_sample', 'fontWeight');
	$fields[] = array($label, $input);

	// color
	$fields[] = array(i18n::s('Color'), color_helper('flexible_main_color', 'p_sample'));

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Text'), Skin::build_form($fields));
	$fields = array();

	// font
	$label = i18n::s('Font');
	$input = select_helper('flexible_main_a_family', $font_families, 'p#p_sample a', 'fontFamily')
		.' '.select_helper('flexible_main_a_size', $font_sizes, 'p#p_sample a', 'fontSize')
		.' '.select_helper('flexible_main_a_weight', array('normal', 'bold'), 'p#p_sample a', 'fontWeight');
	$fields[] = array($label, $input);

	// color of inactive link
	$label = i18n::s('Link color');
	$input = '<input class="color {hash:true,required:false}" name="flexible_main_a_color" size="10" value="'.encode_field($context['flexible_main_a_color']).'" maxlength="8" onchange="$$(\'p#p_sample a.regular\').each(function(link){link.setStyle({ \'color\': this.value})}.bind(this));" />'
		.' <input class="color {hash:true,required:false}" name="flexible_main_a_bg" size="8" value="'.encode_field($context['flexible_main_a_bg']).'" maxlength="12" onchange="$$(\'p#p_sample a.regular\').each(function(link){link.setStyle({\'backgroundColor\': this.value})}.bind(this))" />'
		.' '.select_helper('flexible_main_a_decoration', $text_decorations, 'p#p_sample a.regular', 'textDecoration');
	$fields[] = array($label, $input);
	$text .= JS_PREFIX."$$('p#p_sample a.regular').each(function(link){link.setStyle({'backgroundColor': '".$context['flexible_main_a_bg']."', 'color': '".$context['flexible_main_a_color']."', 'textDecoration': '".$context['flexible_main_a_decoration']."'})});".JS_SUFFIX;

	// color of hovered link
	$label = i18n::s('Hover color');
	$input = '<input class="color {hash:true,required:false}" name="flexible_main_h_color" size="10" value="'.encode_field($context['flexible_main_h_color']).'" maxlength="8" onchange="$$(\'p#p_sample a.current\').each(function(link){link.setStyle({ \'color\': this.value})}.bind(this));" />'
		.' <input class="color {hash:true,required:false}" name="flexible_main_h_bg" size="8" value="'.encode_field($context['flexible_main_h_bg']).'" maxlength="12" onchange="$$(\'p#p_sample a.current\').each(function(link){link.setStyle({\'backgroundColor\': this.value})}.bind(this))" />'
		.' '.select_helper('flexible_main_h_decoration', $text_decorations, 'p#p_sample a.current', 'textDecoration');
	$fields[] = array($label, $input);
	$text .= JS_PREFIX."$$('p#p_sample a.current').each(function(link){link.setStyle({'backgroundColor': '".$context['flexible_main_h_bg']."', 'color': '".$context['flexible_main_h_color']."', 'textDecoration': '".$context['flexible_main_h_decoration']."'})});".JS_SUFFIX;

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Links'), Skin::build_form($fields));
	$fields = array();

	// details
	//

	// text sample
	$sample = '<p class="details" id="d_sample">'.i18n::s('Sample text').' '.DUMMY_TEXT.'</p>';

	// font
	$label = i18n::s('Font');
	$input = select_helper('flexible_details_family', $font_families, 'p#d_sample', 'fontFamily')
		.' '.select_helper('flexible_details_size', $font_sizes, 'p#d_sample', 'fontSize')
		.' '.select_helper('flexible_details_weight', array('normal', 'bold'), 'p#d_sample', 'fontWeight');
	$fields[] = array($label, $input);

	// colors
	$label = i18n::s('Color');
	$input = '<input class="color {hash:true,required:false}" name="flexible_details_color" size="8" value="'.encode_field($context['flexible_details_color']).'" maxlength="12" onchange="$(\'d_sample\').setStyle({\'color\': this.value})" />';
	$fields[] = array($label, $input);

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Details'), $sample.Skin::build_form($fields));
	$fields = array();

	// Page title - h1
	//

	// text sample
	$sample = '<h1 id="h1_sample"><span>'.i18n::s('Sample text').'</span></h1>';

	// font
	$label = i18n::s('Font');
	$input = select_helper('flexible_main_h1_family', $font_families, 'h1#h1_sample', 'fontFamily')
		.' '.select_helper('flexible_main_h1_size', $font_sizes, 'h1#h1_sample', 'fontSize')
		.' '.select_helper('flexible_main_h1_weight', array('normal', 'bold'), 'h1#h1_sample', 'fontWeight');
	$fields[] = array($label, $input);

	// colors
	$label = i18n::s('Color');
	$input = '<input class="color {hash:true,required:false}" name="flexible_main_h1_color" size="8" value="'.encode_field($context['flexible_main_h1_color']).'" maxlength="12" onchange="$(\'h1_sample\').setStyle({\'color\': this.value})" />';
	$fields[] = array($label, $input);

	// padding
	$fields[] = array(i18n::s('Padding'), property_helper('flexible_main_h1', 'padding', 'h1_sample'));

	// borders
	$fields[] = array(i18n::s('Borders'), borders_helper('flexible_main_h1', 'h1_sample'));

	// margin
	$fields[] = array(i18n::s('Margin'), property_helper('flexible_main_h1', 'margin', 'h1_sample'));

	// background
	$fields[] = array(i18n::s('Background'), background_helper('flexible_main_h1_bg', 'h1_sample', 'skins/flexible/boxes')
		.'<br style="clear: left" />'.Skin::build_link('skins/flexible/upload.php?directory=boxes', i18n::s('Add a file'), 'span'));

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Page title'), $sample.Skin::build_form($fields));
	$fields = array();

	// h2
	//

	// text sample
	$sample = '<h1 id="h2_sample"><span>'.i18n::s('Sample text').'</span></h1>';

	// font
	$label = i18n::s('Font');
	$input = select_helper('flexible_main_h2_family', $font_families, 'h2#h2_sample', 'fontFamily')
		.' '.select_helper('flexible_main_h2_size', $font_sizes, 'h2#h2_sample', 'fontSize')
		.' '.select_helper('flexible_main_h2_weight', array('normal', 'bold'), 'h2#h2_sample', 'fontWeight');
	$fields[] = array($label, $input);

	// colors
	$label = i18n::s('Color');
	$input = '<input class="color {hash:true,required:false}" name="flexible_main_h2_color" size="8" value="'.encode_field($context['flexible_main_h2_color']).'" maxlength="12" onchange="$(\'h2_sample\').setStyle({\'color\': this.value})" />';
	$fields[] = array($label, $input);

	// padding
	$fields[] = array(i18n::s('Padding'), property_helper('flexible_main_h2', 'padding', 'h2_sample'));

	// borders
	$fields[] = array(i18n::s('Borders'), borders_helper('flexible_main_h2', 'h2_sample'));

	// margin
	$fields[] = array(i18n::s('Margin'), property_helper('flexible_main_h2', 'margin', 'h2_sample'));

	// background
	$fields[] = array(i18n::s('Background'), background_helper('flexible_main_h2_bg', 'h2_sample', 'skins/flexible/boxes')
		.'<br style="clear: left" />'.Skin::build_link('skins/flexible/upload.php?directory=boxes', i18n::s('Add a file'), 'span'));

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('level 1 title'), $sample.Skin::build_form($fields));
	$fields = array();

	// h3
	//

	// text sample
	$sample = '<h1 id="h3_sample"><span>'.i18n::s('Sample text').'</span></h1>';

	// font
	$label = i18n::s('Font');
	$input = select_helper('flexible_main_h3_family', $font_families, 'h3#h3_sample', 'fontFamily')
		.' '.select_helper('flexible_main_h3_size', $font_sizes, 'h3#h3_sample', 'fontSize')
		.' '.select_helper('flexible_main_h3_weight', array('normal', 'bold'), 'h3#h3_sample', 'fontWeight');
	$fields[] = array($label, $input);

	// colors
	$label = i18n::s('Color');
	$input = '<input class="color {hash:true,required:false}" name="flexible_main_h3_color" size="8" value="'.encode_field($context['flexible_main_h3_color']).'" maxlength="12" onchange="$(\'h3_sample\').setStyle({\'color\': this.value})" />';	$fields[] = array($label, $input);

	// padding
	$fields[] = array(i18n::s('Padding'), property_helper('flexible_main_h3', 'padding', 'h3_sample'));

	// borders
	$fields[] = array(i18n::s('Borders'), borders_helper('flexible_main_h3', 'h3_sample'));

	// margin
	$fields[] = array(i18n::s('Margin'), property_helper('flexible_main_h3', 'margin', 'h3_sample'));

	// background
	$fields[] = array(i18n::s('Background'), background_helper('flexible_main_h3_bg', 'h3_sample', 'skins/flexible/boxes')
		.'<br style="clear: left" />'.Skin::build_link('skins/flexible/upload.php?directory=boxes', i18n::s('Add a file'), 'span'));

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('level 2 title'), $sample.Skin::build_form($fields));
	$fields = array();

	// disposition
	//

	// padding
	$fields[] = array(i18n::s('Padding'), property_helper('flexible_main', 'padding'));

	// borders
	$fields[] = array(i18n::s('Borders'), borders_helper('flexible_main'));

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Disposition'), Skin::build_form($fields));
	$fields = array();

	// bg for the main panel
	$text .= '<input type="hidden" name="flexible_main_bg" value="'.encode_field($context['flexible_main_bg']).'" />';

	// finalize this panel
	$panels[] = array('c', i18n::s('Content'), 'c_panel', $text);

	//
	// navigation boxes ////////////////////////////////////////////
	//

	// visual sample
	$text = '<div id="s_sample" style="height:150px; width: 400px; border: 1px solid #ccc; padding: 15px; margin: 0 0 1em 0; overflow:hidden;">'
		.'<dl class="navigation_box" id="sl_sample"><dt id="st_sample"><span>'.i18n::s('Sample box').'</span></dt>'
		.'<dd id="sd_sample">'.DUMMY_TEXT.'</dd>'
		.'</dl></div>';

	// font
	$label = i18n::s('Font');
	$input = select_helper('flexible_side_dt_family', $font_families, 'dt#st_sample', 'fontFamily')
		.' '.select_helper('flexible_side_dt_size', $font_sizes, 'dt#st_sample', 'fontSize')
		.' '.select_helper('flexible_side_dt_weight', array('normal', 'bold'), 'dt#st_sample', 'fontWeight');
	$fields[] = array($label, $input);

	// color
	$fields[] = array(i18n::s('Color'), color_helper('flexible_side_dt_color', 'st_sample'));

	// padding
	$fields[] = array(i18n::s('Padding'), property_helper('flexible_side_dt', 'padding', 'st_sample'));

	// borders
	$fields[] = array(i18n::s('Borders'), borders_helper('flexible_side_dt', 'st_sample'));

	// margin
	$fields[] = array(i18n::s('Margin'), property_helper('flexible_side_dt', 'margin', 'st_sample'));

	// background
	$fields[] = array(i18n::s('Background'), background_helper('flexible_side_dt_bg', 'st_sample', 'skins/flexible/boxes')
		.'<br style="clear: left" />'.Skin::build_link('skins/flexible/upload.php?directory=boxes', i18n::s('Add a file'), 'span'));

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Box title'), Skin::build_form($fields));
	$fields = array();

	// font
	$label = i18n::s('Font');
	$input = select_helper('flexible_side_family', $font_families, 'div#s_sample', 'fontFamily')
		.' '.select_helper('flexible_side_size', $font_sizes, 'div#s_sample', 'fontSize')
		.' '.select_helper('flexible_side_weight', array('normal', 'bold'), 'div#s_sample', 'fontWeight');
	$fields[] = array($label, $input);

	// color
	$fields[] = array(i18n::s('Color'), color_helper('flexible_side_color', 's_sample'));

	// padding
	$fields[] = array(i18n::s('Padding'), property_helper('flexible_side_dd', 'padding', 'sd_sample'));

	// borders
	$fields[] = array(i18n::s('Borders'), borders_helper('flexible_side_dd', 'sd_sample'));

	// margin
	$fields[] = array(i18n::s('Margin'), property_helper('flexible_side_dd', 'margin', 'sd_sample'));

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Box content'), Skin::build_form($fields));
	$fields = array();

	// font
	$label = i18n::s('Font');
	$input = select_helper('flexible_side_a_family', $font_families, 'dd#sd_sample a', 'fontFamily')
		.' '.select_helper('flexible_side_a_size', $font_sizes, 'dd#sd_sample a', 'fontSize')
		.' '.select_helper('flexible_side_a_weight', array('normal', 'bold'), 'dd#sd_sample a', 'fontWeight');
	$fields[] = array($label, $input);

	// color of inactive link
	$label = i18n::s('Link color');
	$input = '<input class="color {hash:true,required:false}" name="flexible_side_a_color" size="10" value="'.encode_field($context['flexible_side_a_color']).'" maxlength="8" onchange="$$(\'dd#sd_sample a.regular\').each(function(link){link.setStyle({ \'color\': this.value})}.bind(this));" />'
		.' <input class="color {hash:true,required:false}" name="flexible_side_a_bg" size="8" value="'.encode_field($context['flexible_side_a_bg']).'" maxlength="12" onchange="$$(\'dd#sd_sample a.regular\').each(function(link){link.setStyle({\'backgroundColor\': this.value})}.bind(this))" />'
		.' '.select_helper('flexible_side_a_decoration', $text_decorations, 'dd#sd_sample a.regular', 'textDecoration');
	$fields[] = array($label, $input);
	$text .= JS_PREFIX."$$('dd#sd_sample a.regular').each(function(link){link.setStyle({'backgroundColor': '".$context['flexible_side_a_bg']."', 'color': '".$context['flexible_side_a_color']."', 'textDecoration': '".$context['flexible_side_a_decoration']."'})});".JS_SUFFIX;

	// color of hovered link
	$label = i18n::s('Hover color');
	$input = '<input class="color {hash:true,required:false}" name="flexible_side_h_color" size="10" value="'.encode_field($context['flexible_side_h_color']).'" maxlength="8" onchange="$$(\'dd#sd_sample a.current\').each(function(link){link.setStyle({ \'color\': this.value})}.bind(this));" />'
		.' <input class="color {hash:true,required:false}" name="flexible_side_h_bg" size="8" value="'.encode_field($context['flexible_side_h_bg']).'" maxlength="12" onchange="$$(\'dd#sd_sample a.current\').each(function(link){link.setStyle({\'backgroundColor\': this.value})}.bind(this))" />'
		.' '.select_helper('flexible_side_h_decoration', $text_decorations, 'dd#sd_sample a.current', 'textDecoration');
	$fields[] = array($label, $input);
	$text .= JS_PREFIX."$$('dd#sd_sample a.current').each(function(link){link.setStyle({'backgroundColor': '".$context['flexible_side_h_bg']."', 'color': '".$context['flexible_side_h_color']."', 'textDecoration': '".$context['flexible_side_h_decoration']."'})});".JS_SUFFIX;

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Links'), Skin::build_form($fields));
	$fields = array();

	// box background
	$text .= Skin::build_folded_box(i18n::s('Box background'), background_helper('flexible_side_dl_bg', 'sl_sample', 'skins/flexible/boxes')
		.'<br style="clear: left" />'.Skin::build_link('skins/flexible/upload.php?directory=boxes', i18n::s('Add a file'), 'span'));

	// panel background
	$text .= Skin::build_folded_box(i18n::s('Panel background'), background_helper('flexible_side_bg', 's_sample', 'skins/flexible/panels')
		.'<br style="clear: left" />'.Skin::build_link('skins/flexible/upload.php?directory=panels', i18n::s('Add a file'), 'span'));

	// width
	$fields[] = array(i18n::s('Width'), property_helper('flexible_side', 'width', 's_sample'));

	// padding
	$fields[] = array(i18n::s('Padding'), property_helper('flexible_side', 'padding', 's_sample'));

	// borders
	$fields[] = array(i18n::s('Borders'), borders_helper('flexible_side', 's_sample'));

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Disposition'), Skin::build_form($fields));
	$fields = array();

	// finalize this panel
	$panels[] = array('n', i18n::s('Navigation'), 'n_panel', $text);

	//
	// extra boxes ////////////////////////////////////////////
	//

	// visual sample
	$text = '<div id="e_sample" style="height:150px; width: 400px; border: 1px solid #ccc; padding: 15px; margin: 0 0 1em 0; overflow:hidden;">'
		.'<dl class="extra_box" id="el_sample"><dt id="et_sample"><span>'.i18n::s('Sample box').'</span></dt>'
		.'<dd id="ed_sample">'.DUMMY_TEXT.'</dd>'
		.'</dl></div>';

	// font
	$label = i18n::s('Font');
	$input = select_helper('flexible_extra_dt_family', $font_families, 'dt#et_sample', 'fontFamily')
		.' '.select_helper('flexible_extra_dt_size', $font_sizes, 'dt#et_sample', 'fontSize')
		.' '.select_helper('flexible_extra_dt_weight', array('normal', 'bold'), 'dt#et_sample', 'fontWeight');
	$fields[] = array($label, $input);

	// color
	$fields[] = array(i18n::s('Color'), color_helper('flexible_extra_dt_color', 'et_sample'));

	// padding
	$fields[] = array(i18n::s('Padding'), property_helper('flexible_extra_dt', 'padding', 'et_sample'));

	// borders
	$fields[] = array(i18n::s('Borders'), borders_helper('flexible_extra_dt', 'et_sample'));

	// margin
	$fields[] = array(i18n::s('Margin'), property_helper('flexible_extra_dt', 'margin', 'et_sample'));

	// background
	$fields[] = array(i18n::s('Background'), background_helper('flexible_extra_dt_bg', 'et_sample', 'skins/flexible/boxes')
		.'<br style="clear: left" />'.Skin::build_link('skins/flexible/upload.php?directory=boxes', i18n::s('Add a file'), 'span'));

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Box title'), Skin::build_form($fields));
	$fields = array();

	// font
	$label = i18n::s('Font');
	$input = select_helper('flexible_extra_family', $font_families, 'div#e_sample', 'fontFamily')
		.' '.select_helper('flexible_extra_size', $font_sizes, 'div#e_sample', 'fontSize')
		.' '.select_helper('flexible_extra_weight', array('normal', 'bold'), 'div#e_sample', 'fontWeight');
	$fields[] = array($label, $input);

	// color
	$fields[] = array(i18n::s('Color'), color_helper('flexible_extra_color', 'e_sample'));

	// padding
	$fields[] = array(i18n::s('Padding'), property_helper('flexible_extra_dd', 'padding', 'ed_sample'));

	// borders
	$fields[] = array(i18n::s('Borders'), borders_helper('flexible_extra_dd', 'ed_sample'));

	// margin
	$fields[] = array(i18n::s('Margin'), property_helper('flexible_extra_dd', 'margin', 'ed_sample'));

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Box content'), Skin::build_form($fields));
	$fields = array();

	// font
	$label = i18n::s('Font');
	$input = select_helper('flexible_extra_a_family', $font_families, 'dd#ed_sample a', 'fontFamily')
		.' '.select_helper('flexible_extra_a_size', $font_sizes, 'dd#ed_sample a', 'fontSize')
		.' '.select_helper('flexible_extra_a_weight', array('normal', 'bold'), 'dd#ed_sample a', 'fontWeight');
	$fields[] = array($label, $input);

	// color of inactive link
	$label = i18n::s('Link color');
	$input = '<input class="color {hash:true,required:false}" name="flexible_extra_a_color" size="10" value="'.encode_field($context['flexible_extra_a_color']).'" maxlength="8" onchange="$$(\'dd#ed_sample a.regular\').each(function(link){link.setStyle({ \'color\': this.value})}.bind(this));" />'
		.' <input class="color {hash:true,required:false}" name="flexible_extra_a_bg" size="8" value="'.encode_field($context['flexible_extra_a_bg']).'" maxlength="12" onchange="$$(\'dd#ed_sample a.regular\').each(function(link){link.setStyle({\'backgroundColor\': this.value})}.bind(this))" />'
		.' '.select_helper('flexible_extra_a_decoration', $text_decorations, 'dd#ed_sample a.regular', 'textDecoration');
	$fields[] = array($label, $input);
	$text .= JS_PREFIX."$$('dd#ed_sample a.regular').each(function(link){link.setStyle({'backgroundColor': '".$context['flexible_extra_a_bg']."', 'color': '".$context['flexible_extra_a_color']."', 'textDecoration': '".$context['flexible_extra_a_decoration']."'})});".JS_SUFFIX;

	// color of hovered link
	$label = i18n::s('Hover color');
	$input = '<input class="color {hash:true,required:false}" name="flexible_extra_h_color" size="10" value="'.encode_field($context['flexible_extra_h_color']).'" maxlength="8" onchange="$$(\'dd#ed_sample a.current\').each(function(link){link.setStyle({ \'color\': this.value})}.bind(this));" />'
		.' <input class="color {hash:true,required:false}" name="flexible_extra_h_bg" size="8" value="'.encode_field($context['flexible_extra_h_bg']).'" maxlength="12" onchange="$$(\'dd#ed_sample a.current\').each(function(link){link.setStyle({\'backgroundColor\': this.value})}.bind(this))" />'
		.' '.select_helper('flexible_extra_h_decoration', $text_decorations, 'dd#ed_sample a.current', 'textDecoration');
	$fields[] = array($label, $input);
	$text .= JS_PREFIX."$$('dd#ed_sample a.current').each(function(link){link.setStyle({'backgroundColor': '".$context['flexible_extra_h_bg']."', 'color': '".$context['flexible_extra_h_color']."', 'textDecoration': '".$context['flexible_extra_h_decoration']."'})});".JS_SUFFIX;

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Links'), Skin::build_form($fields));
	$fields = array();

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Box background'), background_helper('flexible_extra_dl_bg', 'el_sample', 'skins/flexible/boxes')
		.'<br style="clear: left" />'.Skin::build_link('skins/flexible/upload.php?directory=boxes', i18n::s('Add a file'), 'span'));

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Panel background'), background_helper('flexible_extra_bg', 'e_sample', 'skins/flexible/panels')
		.'<br style="clear: left" />'.Skin::build_link('skins/flexible/upload.php?directory=panels', i18n::s('Add a file'), 'span'));

	// width
	$fields[] = array(i18n::s('Width'), property_helper('flexible_extra', 'width', 'e_sample'));

	// padding
	$fields[] = array(i18n::s('Padding'), property_helper('flexible_extra', 'padding', 'e_sample'));

	// borders
	$fields[] = array(i18n::s('Borders'), borders_helper('flexible_extra', 'e_sample'));

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Disposition'), Skin::build_form($fields));
	$fields = array();

	// finalize this panel
	$panels[] = array('e', i18n::s('Extras'), 'e_panel', $text);

	//
	// footer parameters ////////////////////////////////////////////
	//
	// text sample
	$text = '<div style="height:120px; width: 98%; border: 1px solid #ccc; padding: 0; margin: 0 0 1em 0; overflow: hidden;"><div id="f_sample"><p>'.substr(DUMMY_TEXT, 0, 150).'</p></div></div>';

	// font
	$label = i18n::s('Font');
	$input = select_helper('flexible_footer_family', $font_families, 'div#f_sample', 'fontFamily')
		.' '.select_helper('flexible_footer_size', $font_sizes, 'div#f_sample', 'fontSize')
		.' '.select_helper('flexible_footer_weight', array('normal', 'bold'), 'div#f_sample', 'fontWeight');
	$fields[] = array($label, $input);

	// colors
	$fields[] = array(i18n::s('Color'), color_helper('flexible_footer_color', 'f_sample'));

	// alignment
	$fields[] = array(i18n::s('Alignment'), align_helper('flexible_footer_align', 'f_sample'));

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Text'), Skin::build_form($fields));
	$fields = array();

	// the background
	$text .= Skin::build_folded_box(i18n::s('Background'), background_helper('flexible_footer_bg', 'f_sample', 'skins/flexible/footers')
		.'<br style="clear: left" />'.Skin::build_link('skins/flexible/upload.php?directory=footers', i18n::s('Add a file'), 'span'));

	// height
	$fields[] = array(i18n::s('Height'), property_helper('flexible_footer', 'height', 'f_sample'));

	// padding
	$fields[] = array(i18n::s('Padding'), property_helper('flexible_footer', 'padding', 'f_sample'));

	// borders
	$fields[] = array(i18n::s('Borders'), borders_helper('flexible_footer', 'f_sample'));

	// put the set of fields in the page
	$text .= Skin::build_folded_box(i18n::s('Disposition'), Skin::build_form($fields));
	$fields = array();

	// finalize this panel
	$panels[] = array('f', i18n::s('Footer'), 'f_panel', $text);

	//
	// assemble all panels
	//

	// let YACS do the hard job
	$context['text'] .= Skin::build_tabs($panels);

	//
	// bottom commands
	//
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// end of the form
	$context['text'] .= '</div></form>';

	// general help on this form
	$help = '<p>'.i18n::s('You are encouraged to check the test page each time you submit some modifications. You may need several iterations to refine the actual rendering of your theme.').'</p>';
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');

// no modifications in demo mode
} elseif(file_exists($context['path_to_root'].'parameters/demo.flag')) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation in demonstration mode.'));

// save updated parameters
} else {

	/**
	 * box helper
	 */
	function box_helper($value) {
		$values = explode(' ', $value);
		if(count($values) == 1)
			$values = array($value, $value, $value, $value);
		elseif(count($values) == 2)
			$values = array($values[0], $values[1], $values[0], $values[1]);
		elseif(count($values) == 3)
			$values = array($values[0], $values[1], $values[2], $values[1]);
		else
			$values = array($values[0], $values[1], $values[2], $values[3]);

		return $values;
	}

	/**
	 * substract pixels
	 */
	function minus_helper($x, $y) {
		if($p = strpos($x, 'em'))
			$x = intval(substr($x, 0, $p))*10;
		elseif($p = strpos($x, 'px'))
			$x = intval(substr($x, 0, $p));

		if($p = strpos($y, 'em'))
			$y = intval(substr($y, 0, $p))*10;
		elseif($p = strpos($y, 'px'))
			$y = intval(substr($y, 0, $p));

		return ($x-$y).'px';
	}

	/**
	 * add pixels
	 */
	function plus_helper($x, $y) {
		if($p = strpos($x, 'em'))
			$x = intval(substr($x, 0, $p))*10;
		elseif($p = strpos($x, 'px'))
			$x = intval(substr($x, 0, $p));

		if($p = strpos($y, 'em'))
			$y = intval(substr($y, 0, $p))*10;
		elseif($p = strpos($y, 'px'))
			$y = intval(substr($y, 0, $p));

		return ($x+$y).'px';
	}

	// backup the old version
	Safe::unlink($context['path_to_root'].$parameters_file.'.bak');
	Safe::rename($context['path_to_root'].$parameters_file, $context['path_to_root'].$parameters_file.'.bak');

	// build the new configuration file
	$content = '<?php'."\n"
		.'// This file has been created by the configuration script skins/flexible/configure.php'."\n"
		.'// on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'. Please do not modify it manually.'."\n"
		.'global $context;'."\n"
		.'$context[\'flexible_body_bg\']=\''.addcslashes($_REQUEST['flexible_body_bg'], "\\'")."';\n"
		.'$context[\'flexible_columns\']=\''.addcslashes($_REQUEST['flexible_columns'], "\\'")."';\n"
		.'$context[\'flexible_details_color\']=\''.addcslashes($_REQUEST['flexible_details_color'], "\\'")."';\n"
		.'$context[\'flexible_details_family\']=\''.addcslashes($_REQUEST['flexible_details_family'], "\\'")."';\n"
		.'$context[\'flexible_details_size\']=\''.addcslashes($_REQUEST['flexible_details_size'], "\\'")."';\n"
		.'$context[\'flexible_details_weight\']=\''.addcslashes($_REQUEST['flexible_details_weight'], "\\'")."';\n"
		.'$context[\'flexible_extra_a_bg\']=\''.addcslashes($_REQUEST['flexible_extra_a_bg'], "\\'")."';\n"
		.'$context[\'flexible_extra_a_color\']=\''.addcslashes($_REQUEST['flexible_extra_a_color'], "\\'")."';\n"
		.'$context[\'flexible_extra_a_decoration\']=\''.addcslashes($_REQUEST['flexible_extra_a_decoration'], "\\'")."';\n"
		.'$context[\'flexible_extra_a_family\']=\''.addcslashes($_REQUEST['flexible_extra_a_family'], "\\'")."';\n"
		.'$context[\'flexible_extra_a_size\']=\''.addcslashes($_REQUEST['flexible_extra_a_size'], "\\'")."';\n"
		.'$context[\'flexible_extra_a_weight\']=\''.addcslashes($_REQUEST['flexible_extra_a_weight'], "\\'")."';\n"
		.'$context[\'flexible_extra_bg\']=\''.addcslashes($_REQUEST['flexible_extra_bg'], "\\'")."';\n"
		.'$context[\'flexible_extra_bottom\']=\''.addcslashes($_REQUEST['flexible_extra_bottom'], "\\'")."';\n"
		.'$context[\'flexible_extra_color\']=\''.addcslashes($_REQUEST['flexible_extra_color'], "\\'")."';\n"
		.'$context[\'flexible_extra_dd_bottom\']=\''.addcslashes($_REQUEST['flexible_extra_dd_bottom'], "\\'")."';\n"
		.'$context[\'flexible_extra_dd_left\']=\''.addcslashes($_REQUEST['flexible_extra_dd_left'], "\\'")."';\n"
		.'$context[\'flexible_extra_dd_margin\']=\''.addcslashes($_REQUEST['flexible_extra_dd_margin'], "\\'")."';\n"
		.'$context[\'flexible_extra_dd_padding\']=\''.addcslashes($_REQUEST['flexible_extra_dd_padding'], "\\'")."';\n"
		.'$context[\'flexible_extra_dd_right\']=\''.addcslashes($_REQUEST['flexible_extra_dd_right'], "\\'")."';\n"
		.'$context[\'flexible_extra_dd_top\']=\''.addcslashes($_REQUEST['flexible_extra_dd_top'], "\\'")."';\n"
		.'$context[\'flexible_extra_dl_bg\']=\''.addcslashes($_REQUEST['flexible_extra_dl_bg'], "\\'")."';\n"
		.'$context[\'flexible_extra_dt_bg\']=\''.addcslashes($_REQUEST['flexible_extra_dt_bg'], "\\'")."';\n"
		.'$context[\'flexible_extra_dt_bottom\']=\''.addcslashes($_REQUEST['flexible_extra_dt_bottom'], "\\'")."';\n"
		.'$context[\'flexible_extra_dt_color\']=\''.addcslashes($_REQUEST['flexible_extra_dt_color'], "\\'")."';\n"
		.'$context[\'flexible_extra_dt_family\']=\''.addcslashes($_REQUEST['flexible_extra_dt_family'], "\\'")."';\n"
		.'$context[\'flexible_extra_dt_left\']=\''.addcslashes($_REQUEST['flexible_extra_dt_left'], "\\'")."';\n"
		.'$context[\'flexible_extra_dt_margin\']=\''.addcslashes($_REQUEST['flexible_extra_dt_margin'], "\\'")."';\n"
		.'$context[\'flexible_extra_dt_padding\']=\''.addcslashes($_REQUEST['flexible_extra_dt_padding'], "\\'")."';\n"
		.'$context[\'flexible_extra_dt_right\']=\''.addcslashes($_REQUEST['flexible_extra_dt_right'], "\\'")."';\n"
		.'$context[\'flexible_extra_dt_size\']=\''.addcslashes($_REQUEST['flexible_extra_dt_size'], "\\'")."';\n"
		.'$context[\'flexible_extra_dt_top\']=\''.addcslashes($_REQUEST['flexible_extra_dt_top'], "\\'")."';\n"
		.'$context[\'flexible_extra_dt_weight\']=\''.addcslashes($_REQUEST['flexible_extra_dt_weight'], "\\'")."';\n"
		.'$context[\'flexible_extra_family\']=\''.addcslashes($_REQUEST['flexible_extra_family'], "\\'")."';\n"
		.'$context[\'flexible_extra_h_bg\']=\''.addcslashes($_REQUEST['flexible_extra_h_bg'], "\\'")."';\n"
		.'$context[\'flexible_extra_h_color\']=\''.addcslashes($_REQUEST['flexible_extra_h_color'], "\\'")."';\n"
		.'$context[\'flexible_extra_h_decoration\']=\''.addcslashes($_REQUEST['flexible_extra_h_decoration'], "\\'")."';\n"
		.'$context[\'flexible_extra_left\']=\''.addcslashes($_REQUEST['flexible_extra_left'], "\\'")."';\n"
		.'$context[\'flexible_extra_padding\']=\''.addcslashes($_REQUEST['flexible_extra_padding'], "\\'")."';\n"
		.'$context[\'flexible_extra_right\']=\''.addcslashes($_REQUEST['flexible_extra_right'], "\\'")."';\n"
		.'$context[\'flexible_extra_size\']=\''.addcslashes($_REQUEST['flexible_extra_size'], "\\'")."';\n"
		.'$context[\'flexible_extra_top\']=\''.addcslashes($_REQUEST['flexible_extra_top'], "\\'")."';\n"
		.'$context[\'flexible_extra_weight\']=\''.addcslashes($_REQUEST['flexible_extra_weight'], "\\'")."';\n"
		.'$context[\'flexible_extra_width\']=\''.addcslashes($_REQUEST['flexible_extra_width'], "\\'")."';\n"
		.'$context[\'flexible_footer_align\']=\''.addcslashes($_REQUEST['flexible_footer_align'], "\\'")."';\n"
		.'$context[\'flexible_footer_bg\']=\''.addcslashes($_REQUEST['flexible_footer_bg'], "\\'")."';\n"
		.'$context[\'flexible_footer_bottom\']=\''.addcslashes($_REQUEST['flexible_footer_bottom'], "\\'")."';\n"
		.'$context[\'flexible_footer_color\']=\''.addcslashes($_REQUEST['flexible_footer_color'], "\\'")."';\n"
		.'$context[\'flexible_footer_family\']=\''.addcslashes($_REQUEST['flexible_footer_family'], "\\'")."';\n"
		.'$context[\'flexible_footer_height\']=\''.addcslashes($_REQUEST['flexible_footer_height'], "\\'")."';\n"
		.'$context[\'flexible_footer_left\']=\''.addcslashes($_REQUEST['flexible_footer_left'], "\\'")."';\n"
		.'$context[\'flexible_footer_padding\']=\''.addcslashes($_REQUEST['flexible_footer_padding'], "\\'")."';\n"
		.'$context[\'flexible_footer_right\']=\''.addcslashes($_REQUEST['flexible_footer_right'], "\\'")."';\n"
		.'$context[\'flexible_footer_size\']=\''.addcslashes($_REQUEST['flexible_footer_size'], "\\'")."';\n"
		.'$context[\'flexible_footer_top\']=\''.addcslashes($_REQUEST['flexible_footer_top'], "\\'")."';\n"
		.'$context[\'flexible_footer_weight\']=\''.addcslashes($_REQUEST['flexible_footer_weight'], "\\'")."';\n"
		.'$context[\'flexible_header_bg\']=\''.addcslashes($_REQUEST['flexible_header_bg'], "\\'")."';\n"
		.'$context[\'flexible_header_bottom\']=\''.addcslashes($_REQUEST['flexible_header_bottom'], "\\'")."';\n"
		.'$context[\'flexible_header_height\']=\''.addcslashes($_REQUEST['flexible_header_height'], "\\'")."';\n"
		.'$context[\'flexible_header_left\']=\''.addcslashes($_REQUEST['flexible_header_left'], "\\'")."';\n"
		.'$context[\'flexible_header_right\']=\''.addcslashes($_REQUEST['flexible_header_right'], "\\'")."';\n"
		.'$context[\'flexible_header_s_color\']=\''.addcslashes($_REQUEST['flexible_header_s_color'], "\\'")."';\n"
		.'$context[\'flexible_header_s_family\']=\''.addcslashes($_REQUEST['flexible_header_s_family'], "\\'")."';\n"
		.'$context[\'flexible_header_s_left\']=\''.addcslashes($_REQUEST['flexible_header_s_left'], "\\'")."';\n"
		.'$context[\'flexible_header_s_size\']=\''.addcslashes($_REQUEST['flexible_header_s_size'], "\\'")."';\n"
		.'$context[\'flexible_header_s_top\']=\''.addcslashes($_REQUEST['flexible_header_s_top'], "\\'")."';\n"
		.'$context[\'flexible_header_s_weight\']=\''.addcslashes($_REQUEST['flexible_header_s_weight'], "\\'")."';\n"
		.'$context[\'flexible_header_t_color\']=\''.addcslashes($_REQUEST['flexible_header_t_color'], "\\'")."';\n"
		.'$context[\'flexible_header_t_family\']=\''.addcslashes($_REQUEST['flexible_header_t_family'], "\\'")."';\n"
		.'$context[\'flexible_header_t_left\']=\''.addcslashes($_REQUEST['flexible_header_t_left'], "\\'")."';\n"
		.'$context[\'flexible_header_t_logo\']=\''.addcslashes($_REQUEST['flexible_header_t_logo'], "\\'")."';\n"
		.'$context[\'flexible_header_t_size\']=\''.addcslashes($_REQUEST['flexible_header_t_size'], "\\'")."';\n"
		.'$context[\'flexible_header_t_top\']=\''.addcslashes($_REQUEST['flexible_header_t_top'], "\\'")."';\n"
		.'$context[\'flexible_header_t_weight\']=\''.addcslashes($_REQUEST['flexible_header_t_weight'], "\\'")."';\n"
		.'$context[\'flexible_header_top\']=\''.addcslashes($_REQUEST['flexible_header_top'], "\\'")."';\n"
		.'$context[\'flexible_main_a_bg\']=\''.addcslashes($_REQUEST['flexible_main_a_bg'], "\\'")."';\n"
		.'$context[\'flexible_main_a_color\']=\''.addcslashes($_REQUEST['flexible_main_a_color'], "\\'")."';\n"
		.'$context[\'flexible_main_a_decoration\']=\''.addcslashes($_REQUEST['flexible_main_a_decoration'], "\\'")."';\n"
		.'$context[\'flexible_main_a_family\']=\''.addcslashes($_REQUEST['flexible_main_a_family'], "\\'")."';\n"
		.'$context[\'flexible_main_a_size\']=\''.addcslashes($_REQUEST['flexible_main_a_size'], "\\'")."';\n"
		.'$context[\'flexible_main_a_weight\']=\''.addcslashes($_REQUEST['flexible_main_a_weight'], "\\'")."';\n"
		.'$context[\'flexible_main_bg\']=\''.addcslashes($_REQUEST['flexible_main_bg'], "\\'")."';\n"
		.'$context[\'flexible_main_bottom\']=\''.addcslashes($_REQUEST['flexible_main_bottom'], "\\'")."';\n"
		.'$context[\'flexible_main_color\']=\''.addcslashes($_REQUEST['flexible_main_color'], "\\'")."';\n"
		.'$context[\'flexible_main_family\']=\''.addcslashes($_REQUEST['flexible_main_family'], "\\'")."';\n"
		.'$context[\'flexible_main_h_bg\']=\''.addcslashes($_REQUEST['flexible_main_h_bg'], "\\'")."';\n"
		.'$context[\'flexible_main_h_color\']=\''.addcslashes($_REQUEST['flexible_main_h_color'], "\\'")."';\n"
		.'$context[\'flexible_main_h_decoration\']=\''.addcslashes($_REQUEST['flexible_main_h_decoration'], "\\'")."';\n"
		.'$context[\'flexible_main_h1_bg\']=\''.addcslashes($_REQUEST['flexible_main_h1_bg'], "\\'")."';\n"
		.'$context[\'flexible_main_h1_bottom\']=\''.addcslashes($_REQUEST['flexible_main_h1_bottom'], "\\'")."';\n"
		.'$context[\'flexible_main_h1_color\']=\''.addcslashes($_REQUEST['flexible_main_h1_color'], "\\'")."';\n"
		.'$context[\'flexible_main_h1_family\']=\''.addcslashes($_REQUEST['flexible_main_h1_family'], "\\'")."';\n"
		.'$context[\'flexible_main_h1_left\']=\''.addcslashes($_REQUEST['flexible_main_h1_left'], "\\'")."';\n"
		.'$context[\'flexible_main_h1_margin\']=\''.addcslashes($_REQUEST['flexible_main_h1_margin'], "\\'")."';\n"
		.'$context[\'flexible_main_h1_padding\']=\''.addcslashes($_REQUEST['flexible_main_h1_padding'], "\\'")."';\n"
		.'$context[\'flexible_main_h1_right\']=\''.addcslashes($_REQUEST['flexible_main_h1_right'], "\\'")."';\n"
		.'$context[\'flexible_main_h1_size\']=\''.addcslashes($_REQUEST['flexible_main_h1_size'], "\\'")."';\n"
		.'$context[\'flexible_main_h1_top\']=\''.addcslashes($_REQUEST['flexible_main_h1_top'], "\\'")."';\n"
		.'$context[\'flexible_main_h1_weight\']=\''.addcslashes($_REQUEST['flexible_main_h1_weight'], "\\'")."';\n"
		.'$context[\'flexible_main_h2_bg\']=\''.addcslashes($_REQUEST['flexible_main_h2_bg'], "\\'")."';\n"
		.'$context[\'flexible_main_h2_bottom\']=\''.addcslashes($_REQUEST['flexible_main_h2_bottom'], "\\'")."';\n"
		.'$context[\'flexible_main_h2_color\']=\''.addcslashes($_REQUEST['flexible_main_h2_color'], "\\'")."';\n"
		.'$context[\'flexible_main_h2_family\']=\''.addcslashes($_REQUEST['flexible_main_h2_family'], "\\'")."';\n"
		.'$context[\'flexible_main_h2_left\']=\''.addcslashes($_REQUEST['flexible_main_h2_left'], "\\'")."';\n"
		.'$context[\'flexible_main_h2_margin\']=\''.addcslashes($_REQUEST['flexible_main_h2_margin'], "\\'")."';\n"
		.'$context[\'flexible_main_h2_padding\']=\''.addcslashes($_REQUEST['flexible_main_h2_padding'], "\\'")."';\n"
		.'$context[\'flexible_main_h2_right\']=\''.addcslashes($_REQUEST['flexible_main_h2_right'], "\\'")."';\n"
		.'$context[\'flexible_main_h2_size\']=\''.addcslashes($_REQUEST['flexible_main_h2_size'], "\\'")."';\n"
		.'$context[\'flexible_main_h2_top\']=\''.addcslashes($_REQUEST['flexible_main_h2_top'], "\\'")."';\n"
		.'$context[\'flexible_main_h2_weight\']=\''.addcslashes($_REQUEST['flexible_main_h2_weight'], "\\'")."';\n"
		.'$context[\'flexible_main_h3_bg\']=\''.addcslashes($_REQUEST['flexible_main_h3_bg'], "\\'")."';\n"
		.'$context[\'flexible_main_h3_bottom\']=\''.addcslashes($_REQUEST['flexible_main_h3_bottom'], "\\'")."';\n"
		.'$context[\'flexible_main_h3_color\']=\''.addcslashes($_REQUEST['flexible_main_h3_color'], "\\'")."';\n"
		.'$context[\'flexible_main_h3_family\']=\''.addcslashes($_REQUEST['flexible_main_h3_family'], "\\'")."';\n"
		.'$context[\'flexible_main_h3_left\']=\''.addcslashes($_REQUEST['flexible_main_h3_left'], "\\'")."';\n"
		.'$context[\'flexible_main_h3_margin\']=\''.addcslashes($_REQUEST['flexible_main_h3_margin'], "\\'")."';\n"
		.'$context[\'flexible_main_h3_padding\']=\''.addcslashes($_REQUEST['flexible_main_h3_padding'], "\\'")."';\n"
		.'$context[\'flexible_main_h3_right\']=\''.addcslashes($_REQUEST['flexible_main_h3_right'], "\\'")."';\n"
		.'$context[\'flexible_main_h3_size\']=\''.addcslashes($_REQUEST['flexible_main_h3_size'], "\\'")."';\n"
		.'$context[\'flexible_main_h3_top\']=\''.addcslashes($_REQUEST['flexible_main_h3_top'], "\\'")."';\n"
		.'$context[\'flexible_main_h3_weight\']=\''.addcslashes($_REQUEST['flexible_main_h3_weight'], "\\'")."';\n"
		.'$context[\'flexible_main_left\']=\''.addcslashes($_REQUEST['flexible_main_left'], "\\'")."';\n"
		.'$context[\'flexible_main_padding\']=\''.addcslashes($_REQUEST['flexible_main_padding'], "\\'")."';\n"
		.'$context[\'flexible_main_right\']=\''.addcslashes($_REQUEST['flexible_main_right'], "\\'")."';\n"
		.'$context[\'flexible_main_size\']=\''.addcslashes($_REQUEST['flexible_main_size'], "\\'")."';\n"
		.'$context[\'flexible_main_top\']=\''.addcslashes($_REQUEST['flexible_main_top'], "\\'")."';\n"
		.'$context[\'flexible_main_weight\']=\''.addcslashes($_REQUEST['flexible_main_weight'], "\\'")."';\n"
		.'$context[\'flexible_page_bg\']=\''.addcslashes($_REQUEST['flexible_page_bg'], "\\'")."';\n"
		.'$context[\'flexible_side_a_bg\']=\''.addcslashes($_REQUEST['flexible_side_a_bg'], "\\'")."';\n"
		.'$context[\'flexible_side_a_color\']=\''.addcslashes($_REQUEST['flexible_side_a_color'], "\\'")."';\n"
		.'$context[\'flexible_side_a_decoration\']=\''.addcslashes($_REQUEST['flexible_side_a_decoration'], "\\'")."';\n"
		.'$context[\'flexible_side_a_family\']=\''.addcslashes($_REQUEST['flexible_side_a_family'], "\\'")."';\n"
		.'$context[\'flexible_side_a_size\']=\''.addcslashes($_REQUEST['flexible_side_a_size'], "\\'")."';\n"
		.'$context[\'flexible_side_a_weight\']=\''.addcslashes($_REQUEST['flexible_side_a_weight'], "\\'")."';\n"
		.'$context[\'flexible_side_bg\']=\''.addcslashes($_REQUEST['flexible_side_bg'], "\\'")."';\n"
		.'$context[\'flexible_side_bottom\']=\''.addcslashes($_REQUEST['flexible_side_bottom'], "\\'")."';\n"
		.'$context[\'flexible_side_color\']=\''.addcslashes($_REQUEST['flexible_side_color'], "\\'")."';\n"
		.'$context[\'flexible_side_dd_bottom\']=\''.addcslashes($_REQUEST['flexible_side_dd_bottom'], "\\'")."';\n"
		.'$context[\'flexible_side_dd_left\']=\''.addcslashes($_REQUEST['flexible_side_dd_left'], "\\'")."';\n"
		.'$context[\'flexible_side_dd_margin\']=\''.addcslashes($_REQUEST['flexible_side_dd_margin'], "\\'")."';\n"
		.'$context[\'flexible_side_dd_padding\']=\''.addcslashes($_REQUEST['flexible_side_dd_padding'], "\\'")."';\n"
		.'$context[\'flexible_side_dd_right\']=\''.addcslashes($_REQUEST['flexible_side_dd_right'], "\\'")."';\n"
		.'$context[\'flexible_side_dd_top\']=\''.addcslashes($_REQUEST['flexible_side_dd_top'], "\\'")."';\n"
		.'$context[\'flexible_side_dl_bg\']=\''.addcslashes($_REQUEST['flexible_side_dl_bg'], "\\'")."';\n"
		.'$context[\'flexible_side_dt_bg\']=\''.addcslashes($_REQUEST['flexible_side_dt_bg'], "\\'")."';\n"
		.'$context[\'flexible_side_dt_bottom\']=\''.addcslashes($_REQUEST['flexible_side_dt_bottom'], "\\'")."';\n"
		.'$context[\'flexible_side_dt_color\']=\''.addcslashes($_REQUEST['flexible_side_dt_color'], "\\'")."';\n"
		.'$context[\'flexible_side_dt_family\']=\''.addcslashes($_REQUEST['flexible_side_dt_family'], "\\'")."';\n"
		.'$context[\'flexible_side_dt_left\']=\''.addcslashes($_REQUEST['flexible_side_dt_left'], "\\'")."';\n"
		.'$context[\'flexible_side_dt_margin\']=\''.addcslashes($_REQUEST['flexible_side_dt_margin'], "\\'")."';\n"
		.'$context[\'flexible_side_dt_padding\']=\''.addcslashes($_REQUEST['flexible_side_dt_padding'], "\\'")."';\n"
		.'$context[\'flexible_side_dt_right\']=\''.addcslashes($_REQUEST['flexible_side_dt_right'], "\\'")."';\n"
		.'$context[\'flexible_side_dt_size\']=\''.addcslashes($_REQUEST['flexible_side_dt_size'], "\\'")."';\n"
		.'$context[\'flexible_side_dt_top\']=\''.addcslashes($_REQUEST['flexible_side_dt_top'], "\\'")."';\n"
		.'$context[\'flexible_side_dt_weight\']=\''.addcslashes($_REQUEST['flexible_side_dt_weight'], "\\'")."';\n"
		.'$context[\'flexible_side_family\']=\''.addcslashes($_REQUEST['flexible_side_family'], "\\'")."';\n"
		.'$context[\'flexible_side_h_bg\']=\''.addcslashes($_REQUEST['flexible_side_h_bg'], "\\'")."';\n"
		.'$context[\'flexible_side_h_color\']=\''.addcslashes($_REQUEST['flexible_side_h_color'], "\\'")."';\n"
		.'$context[\'flexible_side_h_decoration\']=\''.addcslashes($_REQUEST['flexible_side_h_decoration'], "\\'")."';\n"
		.'$context[\'flexible_side_left\']=\''.addcslashes($_REQUEST['flexible_side_left'], "\\'")."';\n"
		.'$context[\'flexible_side_padding\']=\''.addcslashes($_REQUEST['flexible_side_padding'], "\\'")."';\n"
		.'$context[\'flexible_side_right\']=\''.addcslashes($_REQUEST['flexible_side_right'], "\\'")."';\n"
		.'$context[\'flexible_side_size\']=\''.addcslashes($_REQUEST['flexible_side_size'], "\\'")."';\n"
		.'$context[\'flexible_side_top\']=\''.addcslashes($_REQUEST['flexible_side_top'], "\\'")."';\n"
		.'$context[\'flexible_side_weight\']=\''.addcslashes($_REQUEST['flexible_side_weight'], "\\'")."';\n"
		.'$context[\'flexible_side_width\']=\''.addcslashes($_REQUEST['flexible_side_width'], "\\'")."';\n"
		.'$context[\'flexible_tabs_a_color\']=\''.addcslashes($_REQUEST['flexible_tabs_a_color'], "\\'")."';\n"
		.'$context[\'flexible_tabs_bg\']=\''.addcslashes($_REQUEST['flexible_tabs_bg'], "\\'")."';\n"
		.'$context[\'flexible_tabs_bg_image\']=\''.addcslashes($_REQUEST['flexible_tabs_bg_image'], "\\'")."';\n"
		.'$context[\'flexible_tabs_bottom\']=\''.addcslashes($_REQUEST['flexible_tabs_bottom'], "\\'")."';\n"
		.'$context[\'flexible_tabs_family\']=\''.addcslashes($_REQUEST['flexible_tabs_family'], "\\'")."';\n"
		.'$context[\'flexible_tabs_h_color\']=\''.addcslashes($_REQUEST['flexible_tabs_h_color'], "\\'")."';\n"
		.'$context[\'flexible_tabs_left\']=\''.addcslashes($_REQUEST['flexible_tabs_left'], "\\'")."';\n"
		.'$context[\'flexible_tabs_padding\']=\''.addcslashes($_REQUEST['flexible_tabs_padding'], "\\'")."';\n"
		.'$context[\'flexible_tabs_right\']=\''.addcslashes($_REQUEST['flexible_tabs_right'], "\\'")."';\n"
		.'$context[\'flexible_tabs_size\']=\''.addcslashes($_REQUEST['flexible_tabs_size'], "\\'")."';\n"
		.'$context[\'flexible_tabs_top\']=\''.addcslashes($_REQUEST['flexible_tabs_top'], "\\'")."';\n"
		.'$context[\'flexible_tabs_weight\']=\''.addcslashes($_REQUEST['flexible_tabs_weight'], "\\'")."';\n"
		.'$context[\'flexible_width\']=\''.addcslashes($_REQUEST['flexible_width'], "\\'")."';\n"
		.'?>'."\n";

	// update the parameters file
	if(!Safe::file_put_contents($parameters_file, $content)) {

		Logger::error(sprintf(i18n::s('ERROR: Impossible to write to the file %s. The configuration has not been saved.'), $parameters_file));

		// allow for a manual update
		$context['text'] .= '<p style="text-decoration: blink;">'.sprintf(i18n::s('To actually change the configuration, please copy and paste following lines by yourself in file %s.'), $parameters_file)."</p>\n";

	// job done
	} else {

		$context['text'] .= '<p>'.sprintf(i18n::s('The following configuration has been saved into the file %s.'), $parameters_file)."</p>\n";

		// purge the cache
		Cache::clear();

		// remember the change
		$label = sprintf(i18n::c('%s has been updated'), $parameters_file);
		Logger::remember('skins/flexible/configure.php', $label);

	}

	// display updated parameters
	$context['text'] .= Skin::build_box(i18n::s('Configuration parameters'), Safe::highlight_string($content), 'folded');

	// reload current parameters, to be sure
	Safe::load($parameters_file, TRUE);

	// read the template file
	if(!$content = Safe::file_get_contents($template_file))
		Logger::error(sprintf(i18n::s('ERROR: Impossible to read the file %s.'), $template_file));

	// do the transformation, and save updated styles sheet
	else {

		// prepare the transformation
		$needles = array();
		$values = array();

		// page-level parameters
		$needles[] = '!!body_background!!';
		$values[] = $context['flexible_body_bg'];

		$needles[] = '!!page_background!!';
		$values[] = $context['flexible_page_bg'];

		// page width
		$needles[] = '!!width!!';
		if($context['flexible_width'] == '960px')
			$values[] = '/* fixed width */'."\n"
				.'div#page {'."\n"
				.'	width: 960px;'."\n"
				.'	text-align: left;'."\n"
				.'	margin: 0 auto;'."\n"
				.'}'."\n";

		elseif($context['flexible_width'] == '850px')
			$values[] = '/* fixed width */'."\n"
				.'div#page {'."\n"
				.'	width: 850px;'."\n"
				.'	text-align: left;'."\n"
				.'	margin: 0 auto;'."\n"
				.'}'."\n";

		elseif($context['flexible_width'] == '640px')
			$values[] = '/* fixed width */'."\n"
				.'div#page {'."\n"
				.'	width: 640px;'."\n"
				.'	text-align: left;'."\n"
				.'	margin: 0 auto;'."\n"
				.'}'."\n";

		else
			$values[] = '/* fluid layout */'."\n"
				.'div#page {'."\n"
				.'	width: 100%;'."\n"
				.'	text-align:left;'."\n"
				.'	margin:0;'."\n"
				.'}'."\n";

		// footer panel
		$needles[] = '!!header_panel!!';
		$values[] = 'div#header_panel {'."\n"
 			.'	background: '.$context['flexible_header_bg'].';'."\n"
 			.'	border-bottom: '.$context['flexible_header_bottom'].';'."\n"
 			.'	border-left: '.$context['flexible_header_left'].';'."\n"
 			.'	border-right: '.$context['flexible_header_right'].';'."\n"
 			.'	border-top: '.$context['flexible_header_top'].';'."\n"
 			.'	height: '.$context['flexible_header_height'].';'."\n"
			.'	position: relative;'."\n"
			.'}'."\n\n"
			.'p#header_title {'."\n"
			.'	position: absolute;'."\n"
			.'	left: '.$context['flexible_header_t_left'].';'."\n"
			.'	top: '.$context['flexible_header_t_top'].';'."\n"
			.'	margin: 0;'."\n"
			.'	padding: 0;'."\n"
			.'}'."\n\n"
			.'p#header_title,'."\n"
			.'p#header_title a {'."\n"
			.'	color: '.$context['flexible_header_t_color'].';'."\n"
			.'	font-family: '.$context['flexible_header_t_family'].';'."\n"
			.'	font-size: '.$context['flexible_header_t_size'].';'."\n"
			.'	font-weight: '.$context['flexible_header_t_weight'].';'."\n"
			.'}'."\n\n"
			.'p#header_slogan {'."\n"
			.'	color: '.$context['flexible_header_s_color'].';'."\n"
			.'	font-family: '.$context['flexible_header_s_family'].';'."\n"
			.'	font-size: '.$context['flexible_header_s_size'].';'."\n"
			.'	font-weight: '.$context['flexible_header_s_weight'].';'."\n"
			.'	position: absolute;'."\n"
			.'	left: '.$context['flexible_header_s_left'].';'."\n"
			.'	top: '.$context['flexible_header_s_top'].';'."\n"
			.'	margin: 0;'."\n"
			.'	padding: 0;'."\n"
			.'}'."\n";

		// tabs
		//
		$needles[] = '!!tabs!!';
		if($position = strpos($context['flexible_tabs_bg_image'], '-left')) {
			$label = substr($context['flexible_tabs_bg_image'], 0, $position);
			$values[] = 'div.tabs { /* all tabs */'."\n"
				.'	position: absolute;'."\n"
				.'	left: 0;'."\n"
				.'	bottom: 0;'."\n"
				.'	background: '.$context['flexible_tabs_bg'].';'."\n"
				.'	border-bottom: '.$context['flexible_tabs_bottom'].';'."\n"
				.'	border-left: '.$context['flexible_tabs_left'].';'."\n"
				.'	border-right: '.$context['flexible_tabs_right'].';'."\n"
				.'	border-top: '.$context['flexible_tabs_top'].';'."\n"
				.'}'."\n"
				."\n"
				.'div.tabs ul { /* the full list */'."\n"
				.'	padding: '.$context['flexible_tabs_padding'].';'."\n"
				.'}'."\n"
				."\n"
				.'div.tabs ul li a { /* tab top left corner */'."\n"
				.'	background: transparent url("tabs/'.$context['flexible_tabs_bg_image'].'") no-repeat left top;'."\n"
				.'}'."\n"
				."\n"
				.'div.tabs ul li a span { /* tab top right corner */'."\n"
				.'	color: '.$context['flexible_tabs_a_color'].';'."\n"
				.'	background: transparent url("tabs/'.str_replace('-left', '-right', $context['flexible_tabs_bg_image']).'") no-repeat right top;'."\n"
				.'	font-family: '.$context['flexible_tabs_family'].';'."\n"
				.'	font-size: '.$context['flexible_tabs_size'].';'."\n"
				.'	font-weight: '.$context['flexible_tabs_weight'].';'."\n"
				.'}'."\n"
				."\n"
				.'div.tabs ul li a.hover span { /* mouse is hovering */'."\n"
				.'	color: '.$context['flexible_tabs_h_color'].';'."\n"
				.'}';
		} else
			$values[] = '';

		// main panel
		//
		$needles[] = '!!main_panel!!';
		$values[] = 'body {'."\n"
			.'	color: '.$context['flexible_main_color'].';'."\n"
			.'	font-family: '.$context['flexible_main_family'].';'."\n"
			.'	font-size: '.$context['flexible_main_size'].';'."\n"
			.'	font-weight: '.$context['flexible_main_weight'].';'."\n"
			.'}'."\n"
			."\n"
			.'body a {'."\n"
			.'	background: '.($context['flexible_main_a_bg']?$context['flexible_main_a_bg']:'transparent').';'."\n"
			.'	color: '.$context['flexible_main_a_color'].';'."\n"
			.'	font-family: '.$context['flexible_main_a_family'].';'."\n"
			.'	font-size: '.$context['flexible_main_a_size'].';'."\n"
			.'	font-weight: '.$context['flexible_main_a_weight'].';'."\n"
			.'	text-decoration: '.$context['flexible_main_a_decoration'].';'."\n"
			.'}'."\n"
			."\n"
			.'body a:hover {'."\n"
			.'	background: '.($context['flexible_main_h_bg']?$context['flexible_main_h_bg']:'transparent').';'."\n"
			.'	color: '.$context['flexible_main_h_color'].';'."\n"
			.'	text-decoration: '.$context['flexible_main_h_decoration'].';'."\n"
			.'}'."\n"
			."\n"
			.'div#main_panel {'."\n"
			.'	background: '.($context['flexible_main_bg']?$context['flexible_main_bg']:'transparent').';'."\n"
			.'	border-bottom: '.$context['flexible_main_bottom'].';'."\n"
			.'	border-left: '.$context['flexible_main_left'].';'."\n"
			.'	border-right: '.$context['flexible_main_right'].';'."\n"
			.'	border-top: '.$context['flexible_main_top'].';'."\n"
			.'	padding: '.$context['flexible_main_padding'].';'."\n"
			.'}'."\n";

		// details
		//
		$needles[] = '!!details!!';
		$values[] = 'p.details, p.details a, p.details a:hover,'."\n"
			.'span.details, span.details a, span.details a:hover {'."\n"
			.'	color: '.$context['flexible_details_color'].';'."\n"
			.'	font-family: '.$context['flexible_details_family'].';'."\n"
			.'	font-size: '.$context['flexible_details_size'].';'."\n"
			.'	font-weight: '.$context['flexible_details_weight'].';'."\n"
			.'}'."\n";

		// h1
		//
		$needles[] = '!!h1!!';
		$values[] = 'h1 {'."\n"
			.'	background: '.($context['flexible_main_h1_bg']?$context['flexible_main_h1_bg']:'transparent').';'."\n"
			.'	border-bottom: '.$context['flexible_main_h1_bottom'].';'."\n"
			.'	border-left: '.$context['flexible_main_h1_left'].';'."\n"
			.'	border-right: '.$context['flexible_main_h1_right'].';'."\n"
			.'	border-top: '.$context['flexible_main_h1_top'].';'."\n"
			.'	color: '.$context['flexible_main_h1_color'].';'."\n"
			.'	font-family: '.$context['flexible_main_h1_family'].';'."\n"
			.'	font-size: '.$context['flexible_main_h1_size'].';'."\n"
			.'	font-weight: '.$context['flexible_main_h1_weight'].';'."\n"
			.'	margin: '.$context['flexible_main_h1_margin'].';'."\n"
			.'	padding: '.$context['flexible_main_h1_padding'].';'."\n"
			.'}'."\n";

		// h2
		//
		$needles[] = '!!h2!!';
		$values[] = 'h2 {'."\n"
			.'	background: '.($context['flexible_main_h2_bg']?$context['flexible_main_h2_bg']:'transparent').';'."\n"
			.'	border-bottom: '.$context['flexible_main_h2_bottom'].';'."\n"
			.'	border-left: '.$context['flexible_main_h2_left'].';'."\n"
			.'	border-right: '.$context['flexible_main_h2_right'].';'."\n"
			.'	border-top: '.$context['flexible_main_h2_top'].';'."\n"
			.'	color: '.$context['flexible_main_h2_color'].';'."\n"
			.'	font-family: '.$context['flexible_main_h2_family'].';'."\n"
			.'	font-size: '.$context['flexible_main_h2_size'].';'."\n"
			.'	font-weight: '.$context['flexible_main_h2_weight'].';'."\n"
			.'	margin: '.$context['flexible_main_h2_margin'].';'."\n"
			.'	padding: '.$context['flexible_main_h2_padding'].';'."\n"
			.'}'."\n";

		// h3
		//
		$needles[] = '!!h3!!';
		$values[] = 'h3 {'."\n"
			.'	background: '.($context['flexible_main_h3_bg']?$context['flexible_main_h3_bg']:'transparent').';'."\n"
			.'	border-bottom: '.$context['flexible_main_h3_bottom'].';'."\n"
			.'	border-left: '.$context['flexible_main_h3_left'].';'."\n"
			.'	border-right: '.$context['flexible_main_h3_right'].';'."\n"
			.'	border-top: '.$context['flexible_main_h3_top'].';'."\n"
			.'	color: '.$context['flexible_main_h3_color'].';'."\n"
			.'	font-family: '.$context['flexible_main_h3_family'].';'."\n"
			.'	font-size: '.$context['flexible_main_h3_size'].';'."\n"
			.'	font-weight: '.$context['flexible_main_h3_weight'].';'."\n"
			.'	margin: '.$context['flexible_main_h3_margin'].';'."\n"
			.'	padding: '.$context['flexible_main_h3_padding'].';'."\n"
			.'}'."\n";

		// maybe the extra and side panels are inverted
		if(($context['flexible_columns'] == '3_2_1') || ($context['flexible_columns'] == '2_3_1')) {
			$actual_side = 'extra';
			$actual_extra = 'side';
		} else {
			$actual_side = 'side';
			$actual_extra = 'extra';
		}
		$side_padding = box_helper($context['flexible_side_padding']);
		$extra_padding = box_helper($context['flexible_extra_padding']);

		// side panel
		$needles[] = '!!side_panel!!';
		$values[] = '#'.$actual_side.'_panel {'."\n"
			.'	border-bottom: '.$context['flexible_side_bottom'].';'."\n"
			.'	border-left: '.$context['flexible_side_left'].';'."\n"
			.'	border-right: '.$context['flexible_side_right'].';'."\n"
			.'	border-top: '.$context['flexible_side_top'].';'."\n"
			.'	color: '.$context['flexible_side_color'].';'."\n"
			.'	font-family: '.$context['flexible_side_family'].';'."\n"
			.'	font-size: '.$context['flexible_side_size'].';'."\n"
			.'	font-weight: '.$context['flexible_side_weight'].';'."\n"
			.'	padding-bottom: '.$side_padding[3].';'."\n"
			.'	padding-top: '.$side_padding[0].';'."\n"
			.'}'."\n"
			."\n"
			.'#'.$actual_side.'_panel a {'."\n"
			.'	background: '.($context['flexible_side_a_bg']?$context['flexible_side_a_bg']:'transparent').';'."\n"
			.'	color: '.$context['flexible_side_a_color'].';'."\n"
			.'	font-family: '.$context['flexible_side_a_family'].';'."\n"
			.'	font-size: '.$context['flexible_side_a_size'].';'."\n"
			.'	font-weight: '.$context['flexible_side_a_weight'].';'."\n"
			.'	text-decoration: '.$context['flexible_side_a_decoration'].';'."\n"
			.'}'."\n"
			."\n"
			.'#'.$actual_side.'_panel a:hover {'."\n"
			.'	background: '.($context['flexible_side_h_bg']?$context['flexible_side_h_bg']:'transparent').';'."\n"
			.'	color: '.$context['flexible_side_h_color'].';'."\n"
			.'	text-decoration: '.$context['flexible_side_h_decoration'].';'."\n"
			.'}'."\n"
			."\n";

		$needles[] = '!!'.$actual_side.'_background!!';
		$values[] = $context['flexible_side_bg'];

		// navigation boxes in side panel
		//
		$needles[] = '!!navigation_box!!';
		$values[] = 'dl.navigation_box { /* one box */'."\n"
			.'	background: '.$context['flexible_side_dl_bg'].';'."\n"
			.'}'."\n\n"
			.'dl.navigation_box dt { /* box title */'."\n"
			.'	background: '.$context['flexible_side_dt_bg'].';'."\n"
			.'	border-bottom: '.$context['flexible_side_dt_bottom'].';'."\n"
			.'	border-left: '.$context['flexible_side_dt_left'].';'."\n"
			.'	border-right: '.$context['flexible_side_dt_right'].';'."\n"
			.'	border-top: '.$context['flexible_side_dt_top'].';'."\n"
			.'	color: '.$context['flexible_side_dt_color'].';'."\n"
			.'	font-family: '.$context['flexible_side_dt_family'].';'."\n"
			.'	font-size: '.$context['flexible_side_dt_size'].';'."\n"
			.'	font-weight: '.$context['flexible_side_dt_weight'].';'."\n"
			.'	margin: '.$context['flexible_side_dt_margin'].';'."\n"
			.'	padding: '.$context['flexible_side_dt_padding'].';'."\n"
			.'}'."\n\n"
			.'dl.navigation_box dd { /* box content */'."\n"
			.'	border-bottom: '.$context['flexible_side_dd_bottom'].';'."\n"
			.'	border-left: '.$context['flexible_side_dd_left'].';'."\n"
			.'	border-right: '.$context['flexible_side_dd_right'].';'."\n"
			.'	border-top: '.$context['flexible_side_dd_top'].';'."\n"
			.'	margin: '.$context['flexible_side_dd_margin'].';'."\n"
			.'	padding: '.$context['flexible_side_dd_padding'].';'."\n"
			.'}'."\n";

		// extra panel
		$needles[] = '!!extra_panel!!';
		$values[] = '#'.$actual_extra.'_panel {'."\n"
			.'	border-bottom: '.$context['flexible_extra_bottom'].';'."\n"
			.'	border-left: '.$context['flexible_extra_left'].';'."\n"
			.'	border-right: '.$context['flexible_extra_right'].';'."\n"
			.'	border-top: '.$context['flexible_extra_top'].';'."\n"
			.'	color: '.$context['flexible_extra_color'].';'."\n"
			.'	font-family: '.$context['flexible_extra_family'].';'."\n"
			.'	font-size: '.$context['flexible_extra_size'].';'."\n"
			.'	font-weight: '.$context['flexible_extra_weight'].';'."\n"
			.'	padding-bottom: '.$extra_padding[3].';'."\n"
			.'	padding-top: '.$extra_padding[0].';'."\n"
			.'}'."\n"
			."\n"
			.'#'.$actual_extra.'_panel a {'."\n"
			.'	background: '.($context['flexible_extra_a_bg']?$context['flexible_extra_a_bg']:'transparent').';'."\n"
			.'	color: '.$context['flexible_extra_a_color'].';'."\n"
			.'	font-family: '.$context['flexible_extra_a_family'].';'."\n"
			.'	font-size: '.$context['flexible_extra_a_size'].';'."\n"
			.'	font-weight: '.$context['flexible_extra_a_weight'].';'."\n"
			.'	text-decoration: '.$context['flexible_extra_a_decoration'].';'."\n"
			.'}'."\n"
			."\n"
			.'#'.$actual_extra.'_panel a:hover {'."\n"
			.'	background: '.($context['flexible_extra_h_bg']?$context['flexible_extra_h_bg']:'transparent').';'."\n"
			.'	color: '.$context['flexible_extra_h_color'].';'."\n"
			.'	text-decoration: '.$context['flexible_extra_h_decoration'].';'."\n"
			.'}'."\n"
			."\n";

		$needles[] = '!!'.$actual_extra.'_background!!';
		$values[] = $context['flexible_extra_bg'];

		// extra boxes in extra panel
		//
		$needles[] = '!!extra_box!!';
		$values[] = 'dl.extra_box { /* one box */'."\n"
			.'	background: '.$context['flexible_extra_dl_bg'].';'."\n"
			.'}'."\n\n"
			.'dl.extra_box dt { /* box title */'."\n"
			.'	background: '.$context['flexible_extra_dt_bg'].';'."\n"
			.'	border-bottom: '.$context['flexible_extra_dt_bottom'].';'."\n"
			.'	border-left: '.$context['flexible_extra_dt_left'].';'."\n"
			.'	border-right: '.$context['flexible_extra_dt_right'].';'."\n"
			.'	border-top: '.$context['flexible_extra_dt_top'].';'."\n"
			.'	color: '.$context['flexible_extra_dt_color'].';'."\n"
			.'	font-family: '.$context['flexible_extra_dt_family'].';'."\n"
			.'	font-size: '.$context['flexible_extra_dt_size'].';'."\n"
			.'	font-weight: '.$context['flexible_extra_dt_weight'].';'."\n"
			.'	margin: '.$context['flexible_extra_dt_margin'].';'."\n"
			.'	padding: '.$context['flexible_extra_dt_padding'].';'."\n"
			.'}'."\n\n"
			.'dl.extra_box dd { /* box content */'."\n"
			.'	border-bottom: '.$context['flexible_extra_dd_bottom'].';'."\n"
			.'	border-left: '.$context['flexible_extra_dd_left'].';'."\n"
			.'	border-right: '.$context['flexible_extra_dd_right'].';'."\n"
			.'	border-top: '.$context['flexible_extra_dd_top'].';'."\n"
			.'	margin: '.$context['flexible_extra_dd_margin'].';'."\n"
			.'	padding: '.$context['flexible_extra_dd_padding'].';'."\n"
			.'}'."\n";

		// combined width of size and extra panels
		$needles[] = '!!combined_width!!';
		$values[] = plus_helper($context['flexible_side_width'], $context['flexible_extra_width']);

		// position the side panel
		$needles[] = '!!'.$actual_side.'_width!!';
		$values[] = $context['flexible_side_width'];
		$needles[] = '!!'.$actual_side.'_inner_width!!';
		$values[] = minus_helper(minus_helper($context['flexible_side_width'], $side_padding[1]), $side_padding[3]);
		$needles[] = '!!'.$actual_side.'_left_padding!!';
		$values[] = $side_padding[3];
		$needles[] = '!!'.$actual_side.'_right_padding!!';
		$values[] = $side_padding[1];
		$needles[] = '!!side_left!!';
		if($actual_side == 'side')
			$values[] = minus_helper($context['flexible_side_width'], $side_padding[1]);
		else
			$values[] = minus_helper($context['flexible_extra_width'], $extra_padding[1]);

		// position the extra panel
		$needles[] = '!!'.$actual_extra.'_width!!';
		$values[] = $context['flexible_extra_width'];
		$needles[] = '!!'.$actual_extra.'_inner_width!!';
		$values[] = minus_helper(minus_helper($context['flexible_extra_width'], $extra_padding[1]), $extra_padding[3]);
		$needles[] = '!!'.$actual_extra.'_left_padding!!';
		$values[] = $extra_padding[3];
		$needles[] = '!!'.$actual_extra.'_right_padding!!';
		$values[] = $extra_padding[1];
		$needles[] = '!!extra_left!!';
		if($actual_side == 'side')
			$values[] = minus_helper(minus_helper(minus_helper(plus_helper(plus_helper($context['flexible_side_width'], $context['flexible_side_width']), $context['flexible_extra_width']), $side_padding[1]), $side_padding[3]), $extra_padding[1]);
		else
			$values[] = minus_helper(minus_helper(minus_helper(plus_helper(plus_helper($context['flexible_extra_width'], $context['flexible_extra_width']), $context['flexible_side_width']), $extra_padding[1]), $extra_padding[3]), $side_padding[1]);

		// more positioning
		$main_padding = box_helper($context['flexible_main_padding']);
		$needles[] = '!!main_holly_margin!!';
		if($actual_side == 'side') {
			$values[] = '0 '
				.plus_helper($context['flexible_extra_width'], $main_padding[1])
				.' 0 '
				.plus_helper($context['flexible_side_width'], $main_padding[3]);
		} else {
			$values[] = '0 '
				.plus_helper($context['flexible_side_width'], $main_padding[1])
				.' 0 '
				.plus_helper($context['flexible_extra_width'], $main_padding[3]);
		}
		$needles[] = '!!main_blog_margin!!';
		$values[] = '0 '
			.$main_padding[1]
			.' 0 '
			.plus_helper(plus_helper($context['flexible_side_width'], $context['flexible_extra_width']), $main_padding[3]);
		$needles[] = '!!combined_right_margin!!';
		if($actual_side == 'side')
			$values[] = plus_helper($side_padding[1], $side_padding[3]);
		else
			$values[] = plus_helper($extra_padding[1], $extra_padding[3]);

		// footer panel
		$needles[] = '!!footer_panel!!';
		$values[] = 'div#footer_panel {'."\n"
 			.'	text-align: '.$context['flexible_footer_align'].';'."\n"
 			.'	background: '.$context['flexible_footer_bg'].';'."\n"
 			.'	border-bottom: '.$context['flexible_footer_bottom'].';'."\n"
 			.'	border-left: '.$context['flexible_footer_left'].';'."\n"
 			.'	border-right: '.$context['flexible_footer_right'].';'."\n"
 			.'	border-top: '.$context['flexible_footer_top'].';'."\n"
 			.'	color: '.$context['flexible_footer_color'].';'."\n"
 			.'	font-family: '.$context['flexible_footer_family'].';'."\n"
 			.'	font-size: '.$context['flexible_footer_size'].';'."\n"
 			.'	font-weight: '.$context['flexible_footer_weight'].';'."\n"
 			.'	height: '.$context['flexible_footer_height'].';'."\n"
 			.'	padding: '.$context['flexible_footer_padding'].';'."\n"
			.'}'."\n";

		// do the transformation
		$content = str_replace($needles, $values, $content);

		// backup old styles
		Safe::unlink($context['path_to_root'].$styles_file.'.bak');
		Safe::rename($context['path_to_root'].$styles_file, $context['path_to_root'].$styles_file.'.bak');

		// save updated styles
		if(!Safe::file_put_contents($styles_file, $content)) {

			Logger::error(sprintf(i18n::s('ERROR: Impossible to write to the file %s. Styles sheet has not been saved.'), $styles_file));

			// allow for a manual update
			$context['text'] .= '<p style="text-decoration: blink;">'.sprintf(i18n::s('To actually change the configuration, please copy and paste following lines by yourself in file %s.'), $styles_file)."</p>\n";

			// display updated styles
			$context['text'] .= Skin::build_box(i18n::s('Styles'), Skin::build_block($content, 'code'), 'folded');

		} else {

			$context['text'] .= '<p>'.sprintf(i18n::s('Styles sheet has been saved into the file %s.'), $styles_file)."</p>\n";

			// display updated styles
			$context['text'] .= Skin::build_box(i18n::s('Styles'), Skin::build_block($content, 'code'), 'folded');

			// purge the cache
			Cache::clear();

			// remember the change
			$label = sprintf(i18n::c('%s has been updated'), $styles_file);
			Logger::remember('skins/flexible/configure.php', $label);

		}
	}

	// follow-up commands
	$follow_up = i18n::s('Where do you want to go now?');
	$menu = array();
	$menu = array_merge($menu, array( 'skins/test.php' => i18n::s('Test page') ));
	$menu = array_merge($menu, array( 'control/' => i18n::s('Control Panel') ));
	$menu = array_merge($menu, array( 'skins/flexible/configure.php' => i18n::s('Configure again') ));
	$follow_up .= Skin::build_list($menu, 'menu_bar');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

}

// render the skin
render_skin();

?>