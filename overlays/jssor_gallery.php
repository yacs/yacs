<?php

/** 
 * Images of anchor are displayed as a jssor diaporama
 * Standard display of images within description (code yacs) is canceled
 * 
 * 
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */


Class jssor_gallery extends Overlay {
    
    
    var $min_images             = 2; // minimal number of images to make a slideshow;
    var $auto_thumbs_threshold  = 5; // if more than this number of image, would display thumbs navigation 
    
    var $nb_images              = 0; // number of images detected.
    
    /*
     * Edit slideshow options
     */
    public function get_fields($host, $field_pos = NULL) {
        
        $fields = array();
        
        // bullet navigation
        // or thumbnail navigation
        // or automatic depending of number of images
        // or none
        $label    = i18n::s('Display of main navigation');
        $mainnav  = $this->get_value('mainnav','auto');
        $input    = '<input type="radio" id="mainnav1" name="mainnav" value="bullets" '.(($mainnav=='bullets')?'checked':'').' />&nbsp;<label for="mainnav1">'.i18n::s('With bullets').'</label>'.BR;
        $input   .= '<input type="radio" id="mainnav2" name="mainnav" value="thumbs" '.(($mainnav=='thumbs')?'checked':'').' />&nbsp;<label for="mainnav2">'.i18n::s('Width thumbnails').'</label>'.BR;
        $input   .= '<input type="radio" id="mainnav3" name="mainnav" value="auto" '.(($mainnav=='auto')?'checked':'').' />&nbsp;<label for="mainnav3">'
                    .sprintf(i18n::s('With bullets, but automatic switch to thumbnails for more than %s images to show'),$this->auto_thumbs_threshold ).'</label>'.BR;
        $input   .= '<input type="radio" id="mainnav4" name="mainnav" value="none" '.(($mainnav=='none')?'checked':'').' />&nbsp;<label for="mainnav4">'.i18n::s('Do not display it').'</label>';
        $fields[] = array($label, $input);
        
        ///// set of options
        $label      = 'Navigation options';
        // arrows navigation yes/no
        $input      = '<input type="checkbox" name="arrowsnav" id="arrowsnav" value="Y" '.( ($this->get_value('arrowsnav','Y')==='Y' )?'checked':'').'/>';
        $input     .= '&nbsp;<label for="arrowsnav">'.i18n::s('Display navigation arrows while hovering').'</label>'.BR;
        // drag sliding
        //$input     .= '<input type="checkbox" name="dragnav" id="dragnav" value="Y" '.( ($this->get_value('dragnav','Y')==='Y' )?'checked':'').'/>';
        //$input     .= '&nbsp;<label for="dragnav">'.i18n::s('Allow draging on images to slide them').'</label>'.BR;
        // autoplay
        $input     .= '<input type="checkbox" name="autoplay" id="autoplay" value="Y" '.( ($this->get_value('autoplay','Y')==='Y' )?'checked':'').'/>';
        $input     .= '&nbsp;<label for="autoplay">'.i18n::s('Autoplay slideshow at page loading').'</label>'.BR;
        $fields[]   = array($label, $input);

        
        // option to size slideshow to its parent
        $label      = 'Display options';
        $input      = '<input type="checkbox" name="fit2parent" id="fit2parent" value="Y" '.( ($this->get_value('fit2parent','Y')==='Y' )?'checked':'').'/>';
        $input     .= '&nbsp;<label for="fit2parent">'.i18n::s('Fit slideshow\'s width to its parent').'</label>'.BR;
        $fields[]   = array($label, $input);
        
        return $fields;
    }
    
    /*
     * Display the slideshow
     */
    public function &get_view_text($host = NULL) {
        global $context;
        
        $text   = '';
        
        // gather anchor images
        $images = Images::list_by_date_for_anchor($this->anchor, 0, 50, 'raw');
        $this->nb_images = count($images);
        $path   = $context['url_to_root'].Files::get_path($this->anchor,'images');
        
        // make a slideshow only for minimal number of images
        if($this->nb_images >= $this->min_images) {

            // include jssor lib and load its scripts
            include_once $context['path_to_root'].'included/jssor/jssor.php'; 
            Jssor::Load();
            
            // prepare gallery
            $slides = array();
            foreach($images as $image) {
                $slide = array();
                $slide['image_src'] = $path.'/'.$image['image_name'];
                if($desc = $image['description']) {
                    $slide['caption'] = $desc;
                }
                $slides[] = $slide;
            }
            
            /////// gallery options
            $options = array();
    
            if($this->get_value('fit2parent')==='Y')
                $options['fullwidth'] = 'parent';
            
            if($this->get_value('arrowsnav')==='Y')
                $options['arrows'] = 1;
            
            Switch($this->get_value('mainnav','none')) {
                
                case 'bullets':
                    $options['bullets'] = 1;
                    break;
                case 'thumbs':
                    $options['thumbnails'] = 1;
                    break;
                case 'auto';
                    if($this->nb_images > $this->auto_thumbs_threshold)
                        $options['thumbnails'] = 1;
                    else
                        $options['bullets'] = 1;
                default:
                case 'none':
                        
                    break;
            }
            
            if($this->get_value('autoplay')==='Y')
                $options['autoplay'] = 1;
            
            // build the gallery
            $text .= Jssor::Make($slides, $options);
            
        }
        
        return $text;
    }
	
    /* 
     * Don't show images already included within description 
     */
    public function &get_live_description($host=null) {


            if($this->nb_images > 1 && $host && $desc = $host['description']) {

                    $desc = preg_replace('\[image=[0-9]*\]','',$desc);
                    $desc = Codes::beautify(trim($desc));

            }

            return $desc;
    }
    
    /*
     * record overlay fields
     */
    public function parse_fields($fields) {
        
        $this->attributes['mainnav']        = isset($fields['mainnav']) ? $fields['mainnav'] : 'none';
        $this->attributes['arrowsnav']      = isset($fields['arrowsnav']) ? $fields['arrowsnav'] : 'N';
        //$this->attributes['dragnav']        = isset($fields['dragnav']) ? $fields['dragnav'] : 'N';
        $this->attributes['autoplay']       = isset($fields['autoplay']) ? $fields['autoplay'] : 'N';
        $this->attributes['fit2parent']     = isset($fields['fit2parent']) ? $fields['fit2parent'] : 'N';
    }
    
    
    /*
     *  do not add image in description
     */
    public function should_embed_files() {
        return false;
    }
    
}

