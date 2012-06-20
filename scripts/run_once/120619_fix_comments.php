<?php
/**
 * add comments for page creation and file upload
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 */

// splash message
global $local;
$local['label_en'] = 'Fix comments';
echo get_local('label')."<br />\n";
$count = 0;

// purpose is to add comments
include_once $context['path_to_root'].'comments/comments.php';

// select files attached to articles, grouped by anchor
$query = "SELECT * FROM ".SQL::table_name('files')
			." WHERE anchor_type = 'article' ORDER BY anchor_id DESC LIMIT 100000";
if(!($result = SQL::query($query))) {
		echo Logger::error_pop().BR."\n";
		return;
}

// parse the whole list
$reference = NULL;
$count_anchors = 0;
$index = 0;
while($row = SQL::fetch($result)) {

	// animate user screen and take care of time
	$index++;
	if(!($index%10))
		echo '.';
	if(!($index%500)) {
		echo BR."\n";

		// ensure enough execution time
		Safe::set_time_limit(30);

	}

	// for each anchor
	if($row['anchor'] != $reference) {
		$reference = $row['anchor'];
		$count_anchors++;
		$update_flag = TRUE;

		// update only if the anchor has no comment yet
		if(Comments::count_for_anchor($reference))
			$update_flag = FALSE;

		// add a comment for article creation
		if($update_flag && ($anchor = Anchors::get($reference))) {
			$fields = array();
			$fields['anchor'] = $reference;
			$fields['create_date'] = $anchor->get_value('create_date');
			$fields['edit_address'] = $anchor->get_value('edit_address');
			$fields['edit_date'] = $anchor->get_value('edit_date');
			$fields['edit_id'] = $anchor->get_value('edit_id');
			$fields['edit_name'] = $anchor->get_value('edit_name');
			$fields['description'] = i18n::c('Page has been created');
			$fields['type'] = 'notification';
			Comments::post($fields);
			$count++;
		}

	}

	// add a comment for each file attached to this article
	if($update_flag) {
		$fields = array();
		$fields['anchor'] = $reference;
		$fields['create_date'] = $row['create_date'];
		$fields['edit_address'] = $row['edit_address'];
		$fields['edit_date'] = $row['edit_date'];
		$fields['edit_id'] = $row['edit_id'];
		$fields['edit_name'] = $row['edit_name'];
		$fields['description'] = '[file='.$row['id'].']';
		Comments::post($fields);
		$count++;
	}
}

echo BR."\n";

// basic reporting
$local['label_en'] = '%d records have been added, %d pages have been processed';
$local['label_fr'] = '%d enregistrements ont &eacute;t&eacute; trait&eacute;s, dans %d pages';
echo sprintf(get_local('label'), $count, $count_anchors)."<br />\n";

?>