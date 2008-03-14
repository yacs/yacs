<?php
/**
 * to make flash labels disappear
 *
 * "out" functions are required to remove all instances of the shape,
 * including the one that's passed in
 *
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

function fadeout($movie, $shape, $instance, $x, $y) {
 for($j=0; $j<=20; ++$j) {
  	$instance->multColor(1.0, 1.0, 1.0, (20-$j)/20);
  	$movie->nextFrame();
	}

	$movie->remove($instance);
}

function sliderightout($movie, $shape, $instance, $x, $y) {
	for($j=0; $j<=20; ++$j) {
  		$instance->moveTo($x+$j*$j, $y);
  		$instance->multColor(1.0, 1.0, 1.0, (20-$j)/20);
  		$movie->nextFrame();
	}

	$movie->remove($instance);
}

function slideleftout($movie, $shape, $instance, $x, $y) {
	for($j=0; $j<=20; ++$j) {
  		$instance->moveTo($x-$j*$j, $y);
  		$instance->multColor(1.0, 1.0, 1.0, (20-$j)/20);
  		$movie->nextFrame();
	}

	$movie->remove($instance);
}

function zoomout($movie, $shape, $instance, $x, $y) {
	for($j=0; $j<=20; ++$j) {
  		$instance->scaleTo(1+sqrt($j/20));
  		$instance->multColor(1.0, 1.0, 1.0, (20-$j)/20);
  		$movie->nextFrame();
	}

	$movie->remove($instance);
}

function skewout($movie, $shape, $instance, $x, $y) {
	for($j=0; $j<=20; ++$j) {
  		$instance->skewXTo(-$j*$j/200);
  		$instance->multColor(1.0, 1.0, 1.0, (20-$j)/20);
  		$movie->nextFrame();
	}

	$movie->remove($instance);
}

function stretchdownout($movie, $shape, $instance, $x, $y) {
	for($j=0; $j<=20; ++$j) {
  		$instance->moveTo($x, $y+$y*$j/20);
  		$instance->scaleTo(1.0, (20-$j)/20);
  		$movie->nextFrame();
	}

	$movie->remove($instance);
}

function stretchupout($movie, $shape, $instance, $x, $y) {
	for($j=0; $j<=20; ++$j) {
  		$instance->moveTo($x, $y*(20-$j)/20);
  		$instance->scaleTo(1.0, (20-$j)/20);
  		$movie->nextFrame();
	}

	$movie->remove($instance);
}

function rotateout($movie, $shape, $instance, $x, $y) {
	for($j=0; $j<=20; ++$j) {
  		$instance->rotateTo(-$j*$j/30);
  		$instance->multColor(1.0, 1.0, 1.0, (20-$j)/20);
  		$movie->nextFrame();
	}

	$movie->remove($instance);
}

function doubleslideout($movie, $shape, $i1, $x, $y) {
	$i2 = $movie->add($shape);

	for($j=0; $j<=20; ++$j) {
  		$i1->moveTo($x-$j*$j/2, $y);
  		$i2->moveTo($x+$j*$j/2, $y);
  		$i1->multColor(1.0, 1.0, 1.0, (20-$j)*(20-$j)/400);
  		$i2->multColor(1.0, 1.0, 1.0, (20-$j)*(20-$j)/400);
  		$movie->nextFrame();
	}

	$movie->remove($i1);
	$movie->remove($i2);
}

$outfuncs = array('fadeout', 'sliderightout', 'slideleftout', 'zoomout',
	    'doubleslideout', 'skewout', 'rotateout', 'stretchupout',
	    'stretchdownout');
?>