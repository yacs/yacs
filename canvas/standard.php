<?php
/**
 * standard canvas for article content :
 *  display one panel for discussion and one panel for attachments
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
	// put additional content in different panels
	//
	$panels = array();

	//
	// append tabs from the overlay, if any, before discussion panel
	//
	if(isset($context['tabs']) && is_array($context['tabs']))
		$panels = array_merge($panels, $context['tabs']);

	//
	// comments attached to this article
	//

	// the list of related comments, if not at another follow-up page
	if(!$zoom_type || ($zoom_type == 'comments')) {
		// put the discussion in a separate panel
		if($canvas['comments']) {
			$label = i18n::s('Discussion');
			if($canvas['comments_count'])
				$label .= ' ('.$canvas['comments_count'].')';
			$panels[] = array('discussion', $label, 'discussion_panel', $canvas['comments']);
		}
	}

	//
	// files attached to this article
	//
	$attachments = '';
	$attachments_count = 0;
	
	if(!$zoom_type || ($zoom_type == 'files')) {
		if ($canvas['files'])
		    $attachments .= $canvas['files'];
		$attachments_count += $canvas['files_count'];
	}

	//
	// links attached to this article
	//
	if(!$zoom_type || ($zoom_type == 'links')) {
		if ($canvas['links'])
		    $attachments .= $canvas['links'];
		$attachments_count += $canvas['links_count'];
	}
	
	// build the full panel
	if($attachments) {
		$label = i18n::s('Attachments');
		if($attachments_count)
			$label .= ' ('.$attachments_count.')';
		$panels[] = array('attachments', $label, 'attachments_panel', $attachments);
	}

	//
	// assemble all tabs
	//
	$text .= Skin::build_tabs($panels);

	//
	// trailer information
	//
	$text .= $canvas['trailer'];
?>