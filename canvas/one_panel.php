<?php
/**
 * standard canvas for article content :
 *  display only one panel for discussion and attachments
 *
 * canvas are a mean to change display of article content
 *
 *
 * @author Christophe Battarel
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see canvas/standard.php
 */

        // stop here on scripts/validate.php
        if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
                return;

	$text = '';

	// insert anchor prefix
	if($canvas['anchor_prefix'])
		$text .= $canvas['anchor_prefix'];

	// display very few things if we are on a follow-up page (comments, files, etc.)
	if($zoom_type)
		$text .= $canvas['introduction'];

	// else expose full details
	else {

		// buttons to display previous and next pages, if any
		if($canvas['neighbours'])
			$text .= Skin::neighbours($neighbours, 'manual');

		// only at the first page
		if($page == 1) {
			// the owner profile, if any, at the beginning of the first page
		    if ($canvas['owner_profile_prefix'])
				$text .= $canvas['owner_profile_prefix'];
			// article rating
			if ($canvas['rating'])
				$text .= $canvas['rating'];
		}

		// the introduction text, if any
		$text .= $canvas['introduction'];

		// text related to the overlay
		$text .= $canvas['overlay_text'];

		// the main part of the page
		$text .= $canvas['description'];

		// the owner profile, if any, at the end of the page
	    if ($canvas['owner_profile_suffix'])
			$text .= $canvas['owner_profile_suffix'];

	}

	//
	// put all content in one panel + overlay tabs in other panels
	//
	$panels = array();

	$canvas_text = '';

	//
	// comments attached to this article
	//

	// the list of related comments, if not at another follow-up page
	if(!$zoom_type || ($zoom_type == 'comments')) {
		// display the discussion
		if($canvas['comments']) {
		    $canvas_text .= Skin::build_content('comments', i18n::s('Comments'), $canvas['comments']);
		}
	}

	//
	// files attached to this article
	//
	if(!$zoom_type || ($zoom_type == 'files')) {
		if ($canvas['files'])
	    	$canvas_text .= $canvas['files'];
	}

	//
	// links attached to this article
	//
	if(!$zoom_type || ($zoom_type == 'links')) {
		if ($canvas['links'])
	    	$canvas_text .= $canvas['links'];
	}

	$label = i18n::s('Information');
	$panels[] = array('content', $label, 'content_panel', $canvas_text);

	//
	// append tabs from the overlay, if any
	//
	if(isset($context['tabs']) && is_array($context['tabs']))
		$panels = array_merge($panels, $context['tabs']);

	//
	// assemble all tabs
	//
	$text .= Skin::build_tabs($panels);

	//
	// trailer information
	//
	$text .= $canvas['trailer'];
?>