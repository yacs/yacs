<?php

/**
 * layout articles as topics handled by jive forums
 *
 * With this layout each entry is followed by a link to post a note.
 *
 * @link http://www.jivesoftware.com/products/forums/  Jive Forums
 *
 * for sections :
 * Moderators are listed below each board, if any.
 * Moderators of a board are the members who have been explicitly assigned as editors of the related section.
 *
 * The script also lists children boards, if any.
 * This helps to provide a comprehensive view to forum surfers.
 *
 * @see sections/view.php
 *
 * This layout has been heavily inspired by TheServerSide.com.
 *
 * @link http://www.theserverside.com/discussions/index.tss
 *
 * @see sections/view.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @author Alexis Raimbault
 * @tester Mordread Wallas
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_as_jive extends Layout_interface {

    /**
     * list entities as topics in a forum
     *
     * @param resource the SQL result
     * @return string the rendered text
     * */
    function layout($result) {
        global $context;

        // we return some text
        $text = '';

        // empty list
        if (!SQL::count($result))
            return $text;

        $items_type = $this->listed_type;

        switch ($items_type) {
            case 'article':
                // start a table
                $text .= Skin::table_prefix('jive');

                // headers
                $text .= Skin::table_row(array(i18n::s('Topic'), i18n::s('Content')), 'header');

                $odd = FALSE;
                break;

            default: // container (section)
                // layout in a table
                $text = Skin::table_prefix('wide');

                // 'even' is used for title rows, 'odd' for detail rows
                $class_title = 'odd';
                $class_detail = 'even';

                // build a list of sections
                $family = '';

                break;
        }

        while ($item = SQL::fetch($result)) {

            // get the object interface, this may load parent and overlay
            $entity = new $items_type($item);

            // get the related overlay, if any
            $overlay = $entity->overlay;

            // get the anchor
            $anchor = $entity->anchor;

            // the url to view this item
            $url = $entity->get_permalink();

            // reset everything
            $prefix = $label = $suffix = $icon = '';

            $title = Codes::beautify_title($entity->get_title());

            // signal restricted and private entities
            if ($item['active'] == 'N')
                $prefix .= PRIVATE_FLAG;
            elseif ($item['active'] == 'R')
                $prefix .= RESTRICTED_FLAG;

            // signal locked articles
            if (isset($item['locked']) && ($item['locked'] == 'Y') && Articles::is_owned($item, $anchor))
                $suffix .= ' ' . LOCKED_FLAG;

            // flag articles updated recently
            if (($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
                $suffix .= ' ' . EXPIRED_FLAG;
            elseif ($item['create_date'] >= $context['fresh'])
                $suffix .= ' ' . NEW_FLAG;
            elseif ($item['edit_date'] >= $context['fresh'])
                $suffix .= ' ' . UPDATED_FLAG;

            switch ($items_type) {
                case 'article':

                    // one row per article
                    $text .= '<tr class="' . ($odd ? 'odd' : 'even') . '"><td>';
                    $odd = !$odd;

                    // signal articles to be published
                    if (!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
                        $prefix .= DRAFT_FLAG;

                    // use the title as a link to the page
                    $text .= Skin::build_link($url, $prefix . '<strong>' . $title . '</strong>' . $suffix, 'basic');

                    break;
                default:

                    // change the family
                    if ($item['family'] != $family) {
                        $family = $item['family'];

                        // show the family
                        $text .= Skin::table_suffix()
                              . '<h2><span>' . $family . '&nbsp;</span></h2>' . "\n"
                              . Skin::table_prefix('wide');
                    }

                    // this is another row of the output
                    $text .= '<tr class="' . $class_title . '"><th>' . $prefix . $title . $suffix . '</th><th>' . i18n::s('Poster') . '</th><th>' . i18n::s('Messages') . '</th><th>' . i18n::s('Last active') . '</th></tr>' . "\n";
                    $count = 1;

                    // get last posts for this board --avoid sticky pages
                    if (preg_match('/\barticles_by_([a-z_]+)\b/i', $item['options'], $matches))
                        $order = $matches[1];
                    else
                        $order = 'edition';

                    if ($articles = Articles::list_for_anchor_by($order, 'section:' . $item['id'], 0, 5, 'raw', TRUE)) {

                        foreach ($articles as $id => $article) {

                            // get the related overlay, if any
                            $article_overlay = Overlay::load($article, 'article:' . $id);

                            // flag articles updated recently
                            if (($article['expiry_date'] > NULL_DATE) && ($article['expiry_date'] <= $context['now']))
                                $flag = EXPIRED_FLAG . ' ';
                            elseif ($article['create_date'] >= $context['fresh'])
                                $flag = NEW_FLAG . ' ';
                            elseif ($article['edit_date'] >= $context['fresh'])
                                $flag = UPDATED_FLAG . ' ';
                            else
                                $flag = '';

                            // use the title to label the link
                            if (is_object($article_overlay))
                                $title = Codes::beautify_title($article_overlay->get_text('title', $article));
                            else
                                $title = Codes::beautify_title($article['title']);

                            // title
                            $title = Skin::build_link(Articles::get_permalink($article), $title, 'article');

                            // poster
                            $poster = Users::get_link($article['create_name'], $article['create_address'], $article['create_id']);

                            // comments
                            $comments = Comments::count_for_anchor('article:' . $article['id']);

                            // last editor
                            $action = '';
                            if ($article['edit_date']) {

                                // label the action
                                if (isset($article['edit_action']))
                                    $action = Anchors::get_action_label($article['edit_action']);
                                else
                                    $action = i18n::s('edited');

                                $action = '<span class="details">' . $action . ' ' . Skin::build_date($article['edit_date']) . '</span>';
                            }

                            // this is another row of the output
                            $text .= '<tr class="' . $class_detail . '"><td>' . $title . $flag . '</td><td>' . $poster . '</td><td style="text-align: center;">' . $comments . '</td><td>' . $action . '</td></tr>' . "\n";
                        }
                    }
            }


            // add details, if any
            $details = array();

            switch ($items_type) {
                case 'article':

                    // poster name
                    if (isset($context['with_author_information']) && ($context['with_author_information'] == 'Y')) {
                        if ($item['create_name'])
                            $details[] = sprintf(i18n::s('posted by %s %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']), Skin::build_date($item['create_date']));
                    }

                    // last update
                    $details[] = sprintf(i18n::s('Updated %s'), Skin::build_date($item['edit_date']));

                    // add details to the title
                    if (count($details))
                        $text .= '<p class="details" style="margin: 3px 0">' . join(', ', $details) . '</p>';

                    // display all tags
                    if ($item['tags'])
                        $text .= '<p class="tags">' . Skin::build_tags($item['tags'], 'article:' . $item['id']) . '</p>';

                    // next cell for the content
                    $text .= '</td><td width="70%">';

                    // the content to be displayed
                    $content = '';

                    // rating
                    if ($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
                        $content .= Skin::build_link(Articles::get_url($item['id'], 'like'), Skin::build_rating_img((int) round($item['rating_sum'] / $item['rating_count'])), 'basic');


                    $content .= Codes::beautify_introduction($entity->get_introduction());

                    // insert overlay data, if any
                    if (is_object($overlay))
                        $content .= $overlay->get_text('list', $item);

                    // the description
                    $content .= Skin::build_block(Codes::beautify($item['description']), 'description', '', $item['options']);

                    // attachment details
                    $details = array();

                    // info on related files
                    if ($count = Files::count_for_anchor('article:' . $item['id'])) {
                        Skin::define_img('FILES_LIST_IMG', 'files/list.gif');
                        $details[] = Skin::build_link($url . '#_attachments', FILES_LIST_IMG . sprintf(i18n::ns('%d file', '%d files', $count), $count), 'span');
                    }

                    // info on related links
                    if ($count = Links::count_for_anchor('article:' . $item['id'], TRUE)) {
                        Skin::define_img('LINKS_LIST_IMG', 'links/list.gif');
                        $details[] = LINKS_LIST_IMG . sprintf(i18n::ns('%d link', '%d links', $count), $count);
                    }

                    // count replies
                    if ($count = Comments::count_for_anchor('article:' . $item['id']))
                        $details[] = Skin::build_link($url . '#_discussion', sprintf(i18n::ns('%d comment', '%d comments', $count), $count), 'span');

                    // the command to reply
                    if ($entity->allows('creation', 'comment')) {
                        Skin::define_img('COMMENTS_ADD_IMG', 'comments/add.gif');
                        $details[] = Skin::build_link(Comments::get_url('article:' . $item['id'], 'comment'), COMMENTS_ADD_IMG . i18n::s('Post a comment'), 'span');
                    }

                    // describe attachments
                    $content .= Skin::finalize_list($details, 'menu_bar');

                    // end the row
                    $text .= $content . '</td></tr>';


                    break;
                default:

                    // board introduction
                    if ($item['introduction'])
                        $details[] = Codes::beautify_introduction($entity->get_introduction());

                    // indicate the total number of threads here
                    if (($count = Articles::count_for_anchor('section:' . $item['id'])) && ($count >= 5))
                        $details[] = sprintf(i18n::s('%d threads'), $count) . '&nbsp;&raquo;';

                    // link to the section index page
                    if ($details)
                        $details = Skin::build_link(Sections::get_permalink($item), join(' -&nbsp;', $details), 'basic');
                    else
                        $details = '';

                    // add a command for new post
                    $poster = '';
                    if (Surfer::is_empowered())
                        $poster = Skin::build_link('articles/edit.php?anchor=' . urlencode('section:' . $item['id']), i18n::s('Add a page') . '&nbsp;&raquo;', 'basic');

                    // insert details in a separate row
                    if ($details || $poster)
                        $text .= '<tr class="' . $class_detail . '"><td colspan="3">' . $details . '</td><td>' . $poster . '</td></tr>' . "\n";

                    // more details
                    $more = array();

                    // board moderators
                    if ($moderators = Sections::list_editors_by_name($item, 0, 7, 'comma5'))
                        $more[] = sprintf(i18n::ns('Moderator: %s', 'Moderators: %s', count($moderators)), $moderators);

                    // children boards
                    if ($children = Sections::list_by_title_for_anchor('section:' . $item['id'], 0, COMPACT_LIST_SIZE, 'compact'))
                        $more[] = sprintf(i18n::ns('Child board: %s', 'Child boards: %s', count($children)), Skin::build_list($children, 'comma'));

                    // as a compact list
                    if (count($more)) {
                        $content = '<ul class="compact">';
                        foreach ($more as $list_item) {
                            $content .= '<li>' . $list_item . '</li>' . "\n";
                        }
                        $content .= '</ul>' . "\n";

                        // insert details in a separate row
                        $text .= '<tr class="' . $class_detail . '"><td colspan="4">' . $content . '</td></tr>' . "\n";
                    }
            }
        }

        // end of processing
        SQL::free($result);

        $this->load_scripts_n_styles();

        // return the table
        $text .= Skin::table_suffix();
        return $text;
    }

}
