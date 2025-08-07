<?php

/**
 * layout des articles en full + vignettes
 * en grille 2 colonnes * + ancre nommée
 * trailer text en full width
 *
 * @author Actupro Christian Loubechine
 * @author Alexis Raimbault
 */
Class Layout_as_grid4 extends Layout_interface {

    /**
     * the preferred number of items for this layout
     *
     * @return int the optimised count of items for this layout
     *
     * @see layouts/layout.php
     */
    function items_per_page() {
        return 600;
    }

    /**
     * list items
     *
     *  Accept following variants (you can mix them):
     *  - 'no_icon', not to show icons of sections
     *  - 'no_intro', not to show intro of sections
     *
     * @param resource the SQL result
     * @return string the rendered text
     *
     * @see layouts/layout.php
     * */
    function layout($result) {
        global $context;

        // empty list
        if (!SQL::count($result)) {
            $output = array();
            return $output;
        }
        // getting variants
        $show_icon = !$this->has_variant('no_icon');
        $show_intro = !$this->has_variant('no_intro');
        $show_update = !$this->has_variant('no_flag');
        $show_desc = !$this->has_variant('no_desc');
        $show_h2 = $this->has_variant('with_h2');
        $show_simple = $this->has_variant('show_simple');
        $first=true;


        if ($show_simple) 
            $text ='<div class="grid-1-small-1 has-gutter grille">';
        else
            $text ='<div class="inner"><div class="grid-4-small-2 has-gutter grille">';

        // process all items in the list
        while ($item = SQL::fetch($result)) {

            // get the object interface, this may load parent anchor and overlay
            $entity = new $this->listed_type($item);

            // the url to view this item
            // SI lien interne existe on dirige vers ce lien
            if (is_object($entity->overlay))
                if ($lien=$entity->overlay->get_text('lien', $item))
                    $url = $lien;
                else
                    $url = $entity->get_permalink();
            else
                $url = $entity->get_permalink();

            // initialize variables
            $prefix = $label = $suffix = $icon = $introduction = $description='';


            // use the title to label the link
            if (is_object($entity->overlay))
                $label = Codes::beautify_title($entity->overlay->get_text('title', $item));
            else
                $label = Codes::beautify_title($item['title']);

            // strip label and uppercase first letter
            $label = ucfirst(Skin::strip($label, 10));


            // get introduction
            if ($show_intro) {
                if (is_object($entity->overlay))
                    $introduction = $entity->overlay->get_text('introduction', $item);
                else
                    $introduction = $item['introduction'];

                // the introductory text, strip to 10 words, preserve Yacs Code
                $introduction=Codes::beautify_introduction(Skin::strip($introduction, 40, NULL, '<a><br><img><span>', TRUE));
            }
            // get description
            if ($show_desc) {
                $description = '';
                if (is_object($entity->overlay))
                    $description = $entity->overlay->get_text('description', $item);
                else
                    $description = $item['description'];

                // the introductory text, strip to 10 words, preserve Yacs Code
                $description=Codes::beautify($description);
            }

            // the icon
            if (strlen($item['thumbnail_url'])==0)
                $icon = $context['url_to_root'].$context['skin'].'/images/logo-300.jpg';
            else
                $icon = $item['thumbnail_url'];
            if ($show_icon) {
                //$icon = str_replace('thumbs/','',$icon);
                $thumb = '<a href="'.$url.'" title="'.$label.'"><figure><img src="'. $icon. '" alt="'.$label.'"/></figure></a>';
            }
            else {
                $thumb = '<a href="'.$url.'" title="'.$label.'"><figure><img src="'.$icon.' alt="Saint-Prim"></figure></a>';
               
            }
            
            $content  = tag::_('div', tag::_class('/act-thumb') . tag::_attr('style', 'background-image:url('. $icon . '); background-size:cover;' ) , '<div class="act_article"><h2>'. $label . '</h2>' );

            // ancre nommée
            if( $item['nick_name']) {
                $content .= tag::_('a', tag::_class('/grid-anchor').tag::_attr('name', $item['nick_name']));    
            }

            // intro & desc
            if ($show_intro) 
                $content .= '<span class="intro">'.$introduction . '</span>'. "\n";
            $content .= '</div>'. "\n";
            //if ($show_desc) 
           //     $content .= '<span class="desc">'.$description . '</span>'. "\n";
            $content = '<a href="'.$url.'" title="'.$label.'">'.$content.'</a>';
       // if ($first) {
        //   $text .= tag::_('div', tag::_class('/grid-content /one-half /act_une'), $content);
        //    $first = false;                       
        //} else {
           $text .= tag::_('div', tag::_class('/grid-content /one-quarter /act-second /act_une'), $content);
        //}

            // vignette
 
            // ligne additionnelle si trailer
           // if($item['trailer'])
           //     $text .=  tag::_('div',tag::_class('/grid-trailer'),Codes::beautify($item['trailer']));

        }
        
        
        // end of processing
        SQL::free($result);

        
        // we have bounded styles and scripts
        $this->load_scripts_n_styles();

        if ($show_simple) 
        $text .='</div>';
            else
        $text .='</div></div>';

        return $text;
    }
}
