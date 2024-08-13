<?php
/**
 * check the database integrity for categories
 *
 * This page is used to check and update the database. Its usage is restricted to associates.
 * Following commands have been implemented:
 *
 * - Remember publication dates
 * - Rebuild title paths
 * - Look for orphans
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// include the global declarations
include_once '../shared/global.php';
include_once 'categories.php';

// load the skin
load_skin('categories');

// do not crawl this page
$context->sif('robots','noindex');

// the path to this page
$context['path_bar'] = array( 'categories/' => i18n::s('Categories') );

// the title of the page
$context['page_title'] = i18n::s('Maintenance');

// the user has to be an associate
if(!Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the index page
	$menu = array('categories/' => i18n::s('Categories'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// remember publication dates
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'remember')) {

	// scan categories
	$context['text'] .= Skin::build_block(sprintf(i18n::s('Analyzing table %s...'), SQL::table_name('articles')), 'title');

	// scan only published articles
	$where = 'NOT ((articles.publish_date is NULL) OR (articles.publish_date <= \'0000-00-00\'))';

	// only consider live articles
	$where = '('.$where.')'
		.' AND ((articles.expiry_date is NULL)'
		."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$context['now']."'))";

	// list up to 10000 most recent articles from active sections
	$query = "SELECT articles.id, articles.publish_date FROM ".SQL::table_name('articles')." AS articles"
		." WHERE ".$where
		." ORDER BY articles.rank, articles.edit_date DESC LIMIT 0, 10000";
	if($result = SQL::query($query)) {

		// scan the list
		$count = 0;
		$errors_count = 0;
		while($row = SQL::fetch($result)) {
			$count++;
			if($error = Categories::remember('article:'.$row['id'], $row['publish_date'])) {
				$context['text'] .= $error.BR."\n";
				if(++$errors_count >= 5) {
					$context['text'] .= i18n::s('Too many successive errors. Aborted').BR."\n";
					break;
				}
			} else
				$errors_count = 0;

			// animate user screen and take care of time
			if(!($count%100)) {
				$context['text'] .= sprintf(i18n::s('%d records have been processed'), $count).BR."\n";

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

		}
	}

	// ending message
	$context['text'] .= sprintf(i18n::s('%d records have been processed'), $count).BR."\n";

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the index page
	$menu = array('categories/' => i18n::s('Categories'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// rebuild title paths
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'paths')) {

	// scan categories
	$context['text'] .= Skin::build_block(sprintf(i18n::s('Analyzing table %s...'), SQL::table_name('categories')), 'title');

	// scan up to 1000 categories (52 weekly + 12 monthly = 60 per year)
	$count = 0;
	if($items = Categories::list_by_date(0, 1000, 'raw')) {

		// retrieve the id and all attributes
		$errors_count = 0;
		foreach($items as $id => $item) {

			// animate user screen and take care of time
			$count++;
			if(!($count%100)) {
				$context['text'] .= sprintf(i18n::s('%d records have been processed'), $count).BR."\n";

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			// rebuild titles path
			$path = '';
			if($item['anchor'])
				$path .= Categories::build_path($item['anchor']).'|';
			$path .= strip_tags($item['title']);

			// save in the database
			$query = "UPDATE ".SQL::table_name('categories')." SET "
				." path='".SQL::escape($path)."'"
				." WHERE id = ".SQL::escape($item['id']);
			if(SQL::query($query) === FALSE) {
				if(++$errors_count >= 5) {
					$context['text'] .= i18n::s('Too many successive errors. Aborted').BR."\n";
					break;
				}
			} else
				$errors_count = 0;

		}
	}

	// ending message
	$context['text'] .= sprintf(i18n::s('%d records have been processed'), $count).BR."\n";

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the index page
	$menu = array('categories/' => i18n::s('Categories'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// look for orphans
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'orphans')) {

	// scan categories
	$context['text'] .= Skin::build_block(sprintf(i18n::s('Analyzing table %s...'), SQL::table_name('categories')), 'title');

	// scan up to 10000 categories
	$count = 0;
	if($items = Categories::list_by_date(0, 10000, 'raw')) {

		// retrieve the id and all attributes
		$errors_count = 0;
		foreach($items as $id => $item) {

			// animate user screen and take care of time
			$count++;
			if(!($count%100)) {
				$context['text'] .= sprintf(i18n::s('%d records have been processed'), $count).BR."\n";

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			// check that the anchor exists, if any
			if($item['anchor'] && !Anchors::get($item['anchor'])) {
				$context['text'] .= sprintf(i18n::s('Orphan: %s'), 'category '.Skin::build_link(Categories::get_permalink($item), $id.' '.$label, 'category')).BR."\n";
				if(++$errors_count >= 5) {
					$context['text'] .= i18n::s('Too many successive errors. Aborted').BR."\n";
					break;
				}

			} else
				$errors_count = 0;

		}
	}

	// ending message
	$context['text'] .= sprintf(i18n::s('%d records have been processed'), $count).BR."\n";

	// scan members
	$context['text'] .= Skin::build_block(sprintf(i18n::s('Analyzing table %s...'), SQL::table_name('members')), 'title');

	// scan up to 50000 members
	$count = 0;
	$query = "SELECT id, anchor, member FROM ".SQL::table_name('members')." LIMIT 0, 50000";
	if(!($result = SQL::query($query)))
		return;

	// parse the whole list
	else {

		// fetch one anchor and the linked member
		$errors_count = 0;
		while($row = SQL::fetch($result)) {

			// animate user screen and take care of time
			$count++;
			if(!($count%100)) {
				$context['text'] .= sprintf(i18n::s('%d records have been processed'), $count).BR."\n";

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			// fetch the member
			if($row['member'] && !$item = Anchors::get($row['member'])) {

				// delete this entry
				$query = "DELETE FROM ".SQL::table_name('members')." WHERE id = ".SQL::escape($row['id']);
				SQL::query($query);

				$context['text'] .= sprintf(i18n::s('Unknown member %s, record has been deleted'), $row['member']).BR."\n";
				if(++$errors_count >= 50) {
					$context['text'] .= i18n::s('Too many successive errors. Aborted').BR."\n";
					break;
				}

			// check that the anchor exists, if any
			} elseif($row['anchor'] && !Anchors::get($row['anchor'])) {

				// delete this entry
				$query = "DELETE FROM ".SQL::table_name('members')." WHERE id = ".SQL::escape($row['id']);
				SQL::query($query);

				$context['text'] .= sprintf(i18n::s('Unknown anchor %s, record has been deleted'), $row['anchor']).BR."\n";
				if(++$errors_count >= 50) {
					$context['text'] .= i18n::s('Too many successive errors. Aborted').BR."\n";
					break;
				}

			} else
				$errors_count = 0;

		}
	}

	// ending message
	$context['text'] .= sprintf(i18n::s('%d records have been processed'), $count).BR."\n";

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the index page
	$menu = array('categories/' => i18n::s('Categories'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');
        
// look for orphans
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'keywords')) {
    
    /* 1 parcours des cat, liste des mots clé avec catégorie associée confirmée (la plus ancienne)
		réserver la cat en doublon avec mot clé associé
	2 pour chaque entité avec un mot clé donné, attribuer la cat confirmée

	3 pour chaque cat en doublon, supprimer le mot clé
		si plus de tag, supprimer la catégorie
     * 
     */
    
    // gather keywords in database
    $query              = "SELECT id, keywords FROM ".SQL::table_name('categories')
                         ." WHERE keywords != '' ORDER BY id";
    $result             = SQL::query($query);
    $keywords_lines     = Categories::list_selected($result,'raw');
    $keywords_all       = array();  // unique keywords
    $keywords_dupli     = array();  // duplicate keywords by cats
    $cats_dupli         = array();  // duplicate cats by keywords
    
    // counters for job done
    $count_records      = 0;
    $count_reassign     = 0;
    $count_doubletag    = 0;
    $count_dupli        = 0;
    $count_delete       = 0;
    
    // find unique and duplicate
    foreach($keywords_lines as $id => $words) {
        
        $words = explode(',', $words['keywords']);
        
        foreach ($words as $w) {

            if(empty($keywords_all[$w]))
               $keywords_all[$w] = $id;
            else { 
             
                if(empty($keywords_dupli[$id]))
                    $keywords_dupli[$id] = array($w);
                else
                    $keywords_dupli[$id][] = $w;
                
                if(empty($cats_dupli[$w]))
                    $cats_dupli[$w] = array($id);
                else
                    $cats_dupli[$w][] = $id; 
            }
        }
    }
    
    // assign entities with a keyword to the right category
    foreach ($keywords_all as $w=>$id) {
        
        // get references of entities using this keyword
        // in articles, sections, files, images
        $query = "SELECT CONCAT('article:',id) as ref FROM ".SQL::table_name('articles')
                . " WHERE tags RLIKE '\\\b".$w."\\\b'"
                . " UNION"
                . " SELECT CONCAT('section:',id) as ref FROM ".SQL::table_name('sections')
                . " WHERE tags RLIKE '\\\b".$w."\\\b'"
                 . " UNION"
                . " SELECT CONCAT('file:',id) as ref FROM ".SQL::table_name('files')
                . " WHERE keywords RLIKE '\\\b".$w."\\\b'"
                 . " UNION"
                . " SELECT CONCAT('image:',id) as ref FROM ".SQL::table_name('images')
                . " WHERE tags RLIKE '\\\b".$w."\\\b'";
        
        $result     = SQL::query($query);
        $references = SQL::fetch_all($result);
        
        foreach ($references as $r) {
            
            $count_records += 1;
            
            $done = members::assign('category:'.$id, $r['ref']);
            if($done) {
                $count_reassign +=1;
                
                if(empty($cats_dupli[$w])) continue;
                // free the reference of any doplicate cat for this keyword
                foreach($cats_dupli[$w] as $duplicat) {
                    $free = members::free('category:'.$duplicat, $r['ref']);
                }
                
            }
            
            // clean double keyword usage in reference
            $anchor     = anchors::get($r['ref']);
            $keywords   = (isset($anchor->item['tags']))?$anchor->item['tags']:$anchor->item['keywords'];
            $keywords   = preg_split('/[ \t]*,\s*/', $keywords);
            if(count($keywords) == 1) continue;    // no need to go further
            $newk       = array_unique($keywords);
            if(count($newk) < count($keywords)) {
                $anchor->set_values(array('tags' => implode(', ', $newk) ));
                $count_doubletag +=1;
            }
        }
        
    }
    
    // remove duplicate keywords in cats
    // delete cat if no keyword left
    foreach ($keywords_dupli as $id => $dupliwords) {
        
        $count_dupli += 1;
        
        $duplicat = categories::get($id);
        $keywords =  preg_split('/[ \t]*,\s*/', $duplicat['keywords']);
        $newk     = array_diff($keywords, $dupliwords);
        
        if(count($newk)) {
            // there are still keywords so keep the cat, update it
            $fields = array(
                'id'        => $duplicat['id'],
                'keywords'  => implode(', ', $newk)
            );
            categories::put_attributes($fields);
            
        } else {
            // no more keyword left so delete this cat
            categories::delete ($duplicat['id']);
            $count_delete +=1;
        }
    }
    
    $context['text'] .= sprintf(i18n::s('%d records have been processed'), $count_records).BR."\n";
    $context['text'] .= sprintf(i18n::s('%d records have been reassigned'), $count_reassign).BR."\n";
    $context['text'] .= sprintf(i18n::s('%d records have been cleaned of double tags'), $count_doubletag).BR."\n";
    $context['text'] .= sprintf(i18n::s('%d categories had duplicate keywords'), $count_dupli).BR."\n";
    $context['text'] .= sprintf(i18n::s('%d categories have been deleted'), $count_delete).BR."\n";
    

// which check?
} else {
	// the splash message
	$context['text'] .= '<p>'.i18n::s('Please select the action to perform.')."</p>\n";

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form">';

	// remember previous publications
	$context['text'] .= '<p><input type="radio" name="action" id="action" value="remember" /> '.i18n::s('Scan pages and remember publication dates').'</p>';

	// rebuild path information
	$context['text'] .= '<p><input type="radio" name="action" value="paths" /> '.i18n::s('Rebuild title paths').'</p>';

	// look for orphan articles
	$context['text'] .= '<p><input type="radio" name="action" value="orphans" /> '.i18n::s('Look for orphan records').'</p>';
        
        // streamline categorization by keywords
        $context['text'] .= '<p><input type="radio" name="action" value="keywords" /> '.i18n::s('Rebuild categorization by keywords').'</p>';

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Start')).'</p>'."\n";

	// end of the form
	$context['text'] .= '</form>';

	// set the focus on the button
	Page::insert_script('$("#action").focus();');

}

// render the skin
render_skin();