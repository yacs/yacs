<?php
/**
 * layout articles as a carrousel of thumbnail images
 *
 * @see articles/articles.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_carrousel extends Layout_interface {

	/**
	 * list articles
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;

		// sanity check
		if(!isset($this->layout_variant))
			$this->layout_variant = 'map';

		// put in cache
		$cache_id = Cache::hash('articles/layout_articles_as_carrousel:'.$this->layout_variant).'.xml';

		// save for one minute
		if(!file_exists($context['path_to_root'].$cache_id) || (filemtime($context['path_to_root'].$cache_id)+60 < time())) {

			// content of the slideshow
			$content = '<?xml version="1.0" encoding="utf-8"?><!-- fhShow Carousel 2.0 configuration file Please visit http://www.flshow.net/ -->'."\n"
				.'<slide_show>'."\n"
				.'	<options>'."\n"
				.'		<debug>false</debug>				  <!-- true, false -->'."\n"
				.'		<background>transparent</background>	  <!-- #RRGGBB, transparent -->'."\n"
				.'		<friction>5</friction>			  <!-- [1,100] -->'."\n"

				.'		<fullscreen>false</fullscreen>	  <!-- true, false -->'."\n"

				.'		<margins>'."\n"
				.'			<top>0</top>								  <!-- [-1000,1000] pixels -->'."\n"
				.'			<left>0</left>							  <!-- [-1000,1000] pixels -->'."\n"
				.'			<bottom>0</bottom>						  <!-- [-1000,1000] pixels -->'."\n"
				.'			<right>0</right>							  <!-- [-1000,1000] pixels -->'."\n"
				.'			<horizontal_ratio>20%</horizontal_ratio>	  <!-- [1,50] a photo may occupy at most horizontalRatio percent of the Carousel width -->'."\n"
				.'			<vertical_ratio>90%</vertical_ratio>		  <!-- [1,100] a photo may occupy at most verticalRatio percent of the Carousel height -->'."\n"
				.'		</margins>'."\n"

				.'		<interaction>'."\n"
				.'			<rotation>mouse</rotation>			<!-- auto, mouse, keyboard -->'."\n"
				.'			<view_point>none</view_point>		   <!-- none, mouse, keyboard -->'."\n"
				.'			<speed>15</speed>						<!-- [-360,360] degrees per second -->'."\n"
				.'			<default_speed>15</default_speed>				<!-- [-360,360] degrees per second -->'."\n"
				.'			<default_view_point>20%</default_view_point>	<!-- [0,100] percentage -->'."\n"
				.'			<reset_delay>20</reset_delay>					<!-- [0,600] seconds, 0 means never reset -->'."\n"
				.'		</interaction>'."\n"

				.'		<far_photos>'."\n"
				.'			<size>50%</size>					<!-- [0,100] percentage -->'."\n"
				.'			<amount>50%</amount>				<!-- [0,100] percentage -->'."\n"
				.'			<blur>10</blur>					<!-- [0,100] amount -->'."\n"
				.'			<blur_quality>3</blur_quality>	<!-- [1,3] 1=low - 3=high -->'."\n"
				.'		</far_photos>'."\n"

				.'		<reflection>'."\n"
				.'			<amount>25</amount>	   <!-- [0,1000] pixels -->'."\n"
				.'			<blur>2</blur>			<!-- [0,100] blur amount -->'."\n"
				.'			<distance>0</distance>	<!-- [-1000,1000] pixels -->'."\n"
				.'			<alpha>40%</alpha>		<!-- [0,100] percentage -->'."\n"
				.'		</reflection>'."\n"

				.'		<titles>'."\n"
				.'			<style>font-size: 14px; font-family: Verdana, _serif; color: #000000;</style>'."\n"
				.'			<position>above center</position>			  <!-- [above, below] [left,center,right]-->'."\n"
				.'			<background>'.$context['url_to_home'].$context['url_to_root'].'skins/_reference/layouts/carrousel_bubble.png</background>	   <!-- image url -->'."\n"
				.'			<scale9>35 35 35 35</scale9>				 <!-- [0,1000] pixels -->'."\n"
				.'			<padding>8 15 10 15</padding>					<!-- [-1000,1000] pixels -->'."\n"
				.'		</titles>'."\n"
				.'	</options>'."\n";

			// get a default image
			if(Safe::GetImageSize($context['path_to_root'].$context['skin'].'/layouts/map.gif'))
				$default_href = $context['url_to_root'].$context['skin'].'/layouts/map.gif';
			elseif($size = Safe::GetImageSize($context['path_to_root'].'skins/_reference/layouts/map.gif'))
				$default_href = $context['url_to_root'].'skins/_reference/layouts/map.gif';
			else
				$default_href = NULL;

			// process all items in the list
			while($item =& SQL::fetch($result)) {

				// get the related overlay
				$overlay = Overlay::load($item);

				// get the anchor
				$anchor =& Anchors::get($item['anchor']);

				// this is visual
				if(isset($item['icon_url']) && $item['icon_url'])
					$image = $item['icon_url'];
				elseif(isset($item['thumbnail_url']) && $item['thumbnail_url'])
					$image = $item['thumbnail_url'];
				elseif(is_object($anchor) && ($image = $anchor->get_thumbnail_url()))
					;
				elseif($default_href)
					$image = $default_href;
				else
					continue;

				// fix relative path
				if(!preg_match('/^(\/|http:|https:|ftp:)/', $image))
					$image = $context['url_to_root'].$image;

				// build a title
				if(is_object($overlay) && is_callable(array($overlay, 'get_live_title')))
					$title = $overlay->get_live_title($item);
				else
					$title = Codes::beautify_title($item['title']);

				// the url to view this item
				$url =& Articles::get_permalink($item);

				// add to the list
				$content .= '	<photo>'."\n"
					.'		<title>'.$title.'</title>'."\n"
					.'		<src>'.$context['url_to_home'].$image.'</src>'."\n"
					.'		<href>'.$context['url_to_home'].$context['url_to_root'].$url.'</href>'."\n"
					.'		<target>_self</target>'."\n"
					.'	</photo>'."\n";

			}

			// finalize slideshow content
			$content .= '</slide_show>';

			// put in cache
			Safe::file_put_contents($cache_id, $content);

		}

		// allow multiple instances
		static $count;
		if(!isset($count))
			$count = 1;
		else
			$count++;

		// load the right file
		$text = '<div id="articles_as_carrousel_'.$count.'"></div>'."\n"
			.JS_PREFIX
			.'swfobject.embedSWF("'.$context['url_to_home'].$context['url_to_root'].'included/browser/carrousel.swf",'."\n"  // flash file
			.'"articles_as_carrousel_'.$count.'",'."\n"		// div id
			.'"100%",'."\n"			// width
			.'"150",'."\n"			// height
			.'"9.0.0",'."\n"		// flash player version
			.'false,'."\n"			// autoinstall
			.'{xmlfile:"'.$context['url_to_home'].$context['url_to_root'].$cache_id.'", loaderColor:"0x666666"},'."\n"		// flashvars
			.'{wmode: "transparent"},'."\n" // parameter
			.'{});'."\n"			// attributes
			.JS_SUFFIX;

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>