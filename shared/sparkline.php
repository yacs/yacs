<?php
/**
 * create a sparkline
 *
 * @link http://www.edwardtufte.com/bboard/q-and-a-fetch-msg?msg_id=0001OR=1 Sparklines: theory and practice
 * @link http://intepid.com/stuff/sparklines/ A Very Basic Sparkline Builder
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class sparkline {

	function ParseColorValue($col) {
		sscanf($col, "%6x", $val); // read the raw number

		if (strlen($col) == 1)
			$val = $val * 0x111111;

		else if (strlen($col) == 2)
			$val = $val * 0x10101;

		else if (strlen($col) == 3)
			$val = (($val & 0xF00) * 0x1100) + (($val & 0xF0) * 0x110) + (($val & 0xF) * 0x11);

		return $val;
	}

	function ScaleForBitmap($v) {
		return $this->h - $this->ScaleForRange($v) * ($this->h-2) - 1 + $this->fudge; // pixel fudging ;)
	}

	function ScaleForRange($v) {
		return ($v - $this->lower) / ($this->upper - $this->lower);
	}

	/**
	 * create a sparkline
	 *
	 * @param array data to be drawn
	 * @param array parameters
	 */
	function build($series, $options) {

		// 1-line 2-fill 3-line+fill 4-intensity
		if(empty($options['style']))
			$this->style = 2;
		else
			$this->style = $options['style'];

		// heigth
		if(empty($options['h']))
			$this->h = 64;
		else
			$this->h = $options['h'];

		// gap width
		if(empty($options['gap']))
			$this->gap = 0.25;
		else
			$this->gap = $options['gap'];

		// interpolation 0-none 1-linear 2-quadratic
		if(empty($options['order']))
			$this->order = 0;
		else
			$this->order = $options['order'];

		// sample-width
		if(empty($options['sw'])) {
			if($this->gap > 0.1)
				$sw = 5;
			else
				$sw = 6;
		} else
			$sw = $options['sw'];

		// anti-aliasing 0-none 1-yes
		if(empty($options['aa']))
			$this->aa = 0;
		else
			$this->aa = $options['aa'];

		if (!isset($this->h) || !$this->h)
			$this->h = 16;

		if ($this->h > 128) // don't want to tax server/bandwidth too much
			$this->h = 128;

		if(isset($sw) && $sw) // base width on number of samples?
			$w = (count($series) - $this->order) * $sw;

		if(!isset($w) || !$w) // or just guess one if not already here
			$w = $this->h * 4;

		if ($w > 1024) // limit again
			$w = 1024;

		// we'll draw to a 2x res bitmap then downsample... easiest way to antialias for now
		if(isset($this->aa) && $this->aa) {
			$w *= 2;
			$this->h *= 2;
		}

		$im = @imagecreatetruecolor($w, $this->h) or die("Couldn't initialize new GD image stream");

		// we allow multiple ways to define a color, all use hex bumbers
		// B = 0xBBBBBB (greyscale)
		// B9 = 0xB9B9B9 (greyscale)
		// B94 = 0xBB9944
		// B94CD1 = 0xB94CD1

		// solid color
		if(empty($options['fill']))
			$this->fill = 0xcccccc;
		else
			$this->fill = $this->ParseColorValue($options['fill']);

		// allocate inks
		if(empty($options['bg']))
			$this->bg = 0xffffff;
		else
			$this->bg = $this->ParseColorValue($options['bg']);

		if(empty($options['tint'])) // used for optional range bars
			$this->tint = 0xf0f0f0;
		else
			$this->tint = $this->ParseColorValue($options['tint']);

		if(empty($options['line']))
			$this->line = 0x444444;
		else
			$this->line = $this->ParseColorValue($options['line']);

		// clear to background color
		imagefilledrectangle($im, 0, 0, $w, $this->h, $this->bg);

		// get data range
		$this->lower = $this->upper = $series[0];

		for ($i = 1; $i < count($series); $i++) {
			if ($this->lower > $series[$i])
				$this->lower = $series[$i];
			else if ($this->upper < $series[$i])
				$this->upper = $series[$i];
		}

		// if user has supplied additional min and max values [to expand to, not collapse]
		if (!empty($options['min']) && $this->lower > $options['min'])
			$this->lower = $options['min'];
		if (!empty($options['max']) && $this->upper < $options['max'])
			$this->upper = $options['max'];

		if ($this->lower == $this->upper) // avoid Divide by zero error, impose a 1.0 range
		{
			$this->upper += 0.5;
			$this->lower -= 0.5;
		}

		$this->fudge = 0;

		$zero = $this->ScaleForBitmap($zero);

		if (!($zero & 1) && $this->aa)
		{
			$this->fudge = 1;
			$zero ++;
		}

		// we can provide color bands to give some visual indications of scale
		if (!empty($zone))
		{
			$zone = explode(",", $zone);
			for ($i = 0; $i < count($zone) >> 1; $i++)
				imagefilledrectangle($im, 0, $this->ScaleForBitmap($zone[$i*2+1]), $w, $this->ScaleForBitmap($zone[$i*2]), $this->tint);
		}

		if (!$this->gap)
			$this->gap = 0;

		$this->gap *= 0.5; // shave half off either end (see below)

		for ($i = 0; $i < $w; $i++)
		{
			if ($this->order == 2) // quadratic?
			{
				$x = $i * (count($series)-2) / $w;
				$f = $x - (int)$x;
				$y = ($series[$x] * (1-($f*0.5+0.5)) + $series[$x+1] * ($f*0.5+0.5)) * (1-$f) +
				($series[$x+1] * (1-$f*0.5) + $series[$x+2] * $f*0.5) * $f;
			}
			else if ($this->order == 1) // linear?
			{
				$x = $i * (count($series)-1) / $w;
				$f = $x - (int)$x;
				$y = $series[$x] * (1-$f) + $series[$x+1] * $f;
			}
			else // bar
			{
				$x = $i * count($series) / $w;
				$f = $x - (int)$x;
				$y = $series[$x];
			}

			if ($this->gap && ($f < $this->gap || $f > 1-$this->gap)) // per sample gap
				continue;

			$v = $this->ScaleForBitmap($y);

			if ($this->style & 4) // intensity plot
			{
				$color = $this->ScaleForRange($y);

				// mix the colors
				$color =
				((int)(($this->line & 0xff) * $color + ($this->bg & 0xff) * (1-$color)) & 0xff) +
				((int)(($this->line & 0xff00) * $color + ($this->bg & 0xff00) * (1-$color)) & 0xff00) +
				((int)(($this->line & 0xff0000) * $color + ($this->bg & 0xff0000) * (1-$color)) & 0xff0000)
				;

				imagefilledrectangle($im, $i, 0, $i, $this->h, $color);
			}

			if ($this->style & 2) // fill
			{
				if ($v <= $zero) {
					$y1 = $v;
					$y2 = $zero;
				} else {
					$y2 = $v+1;
					$y1 = $zero+1;
				}

				imagefilledrectangle($im, $i, $y1, $i, $y2, $this->fill);
			}

			if (($this->style & 1) || !$this->style) // line?
			{
				if (!empty($last)) // only if we have a last point to draw from
				{
					if ($this->order) // continuous plot
					{
						imageline($im, $i-1, $last, $i, $v, $this->line);
						//imageline($im, $i-1, $last+1, $i, $y+1, $this->line);
						imageline($im, $i, $last, $i+1, $v, $this->line);
					}
					else // square trace
					{
						imageline($im, $i-1, $last, $i-1, $v, $this->line);
						imageline($im, $i-1, $v, $i, $v, $this->line);
					}
				}
			$last = $v;
			}
		}

		if ($this->aa)
		{
			$im2 = @imagecreatetruecolor(intval($w*0.5), intval($this->h*0.5))
			or die("Couldn't initialize new GD image stream");
			imagecopyresampled($im2, $im, 0, 0, 0, 0, imagesx($im2), imagesy($im2), imagesx($im), imagesy($im));
			imagedestroy($im);
			$im = $im2;
		}

		//
		// transfer to the user agent
		//

		// actual transmission except on a HEAD request
		if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD')) {

			// doesn't really need to change at all, but added this just in case the algorithm changes
	//		@header("Last-Modified: " . gmdate("D, d M Y H:i:s", intval(time() / 86400) * 86400) . " GMT");
			Safe::header("Content-type: image/png");

			imagepng($im);

			imagedestroy($im);

		}
	}
}