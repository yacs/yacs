<?php
/**
 * [sample]
 * [sample.layout]
 * [sample.layout,variant]
 *
 *    		-> render a sample text
 *
 * It has been originally coded for gresivaudan.org and altashop
 *
 * Author: Christophe Battarel - altairis - christophe@altairis.fr
 *
 */
 
 // merge in first place to save some cycles if at the beginning
  $pattern = array_merge(array(
				'/\[sample\]\n*/ise',						  // [sample]
				'/\[sample\.([^\]]+?)\]\n*/ise'   // [sample.layout] [sample.layout,variant]
    ), $pattern);
  $replace = array_merge(array(
				"render_sample()",						    // [sample]
				"render_sample('$1')"					    // [sample.layout] [sample.layout,variant]
    ), $replace);

	/**
	 * render sample with altasample module
	 *
	 * @param string the layout
	 * @return string the rendered sample
	**/
	function &render_sample($layout='resume') {
		global $context;

		// we return some text;
		$text = '';
		
    $text .= 'sample_'.$layout;

		return $text;
  }

?>
