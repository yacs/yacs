<?php
/**
 * to let flash labels appear
 *
 * "in" functions leave one instance on the display list
 * (though they can use more than one), and return that instance
 *
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

function fadein($movie, $shape, $x, $y) {
	$i = $movie->add($shape);
	$i->moveTo($x,$y);

	for($j=0; $j<=20; ++$j) {
		$i->multColor(1.0, 1.0, 1.0, $j/20);
		$movie->nextFrame();
	}

	return $i;
}

function slideleftin($movie, $shape, $x, $y) {
	$i = $movie->add($shape);

	for($j=0; $j<=20; ++$j) {
		$i->moveTo($x-($j-20)*($j-20), $y);
		$i->multColor(1.0, 1.0, 1.0, $j/20);
		$movie->nextFrame();
	}

	return $i;
}

function sliderightin($movie, $shape, $x, $y) {
	$i = $movie->add($shape);

	for($j=0; $j<=20; ++$j) {
		$i->moveTo($x+($j-20)*($j-20), $y);
		$i->multColor(1.0, 1.0, 1.0, $j/20);
		$movie->nextFrame();
	}

	return $i;
}

function zoomin($movie, $shape, $x, $y) {
	$i = $movie->add($shape);
	$i->moveTo($x, $y);

	for($j=0; $j<=20; ++$j) {
		$i->scaleTo(sqrt(sqrt($j/20)));
		$i->multColor(1.0, 1.0, 1.0, $j/20);
		$movie->nextFrame();
	}

	return $i;
}

function skewin($movie, $shape, $x, $y) {
	$i = $movie->add($shape);
	$i->moveTo($x, $y);

	for($j=0; $j<=20; ++$j) {
		$i->skewXTo((20-$j)*(20-$j)/200);
		$i->multColor(1.0, 1.0, 1.0, $j/20);
		$movie->nextFrame();
	}

	return $i;
}

function rotatein($movie, $shape, $x, $y) {
	$i = $movie->add($shape);
	$i->moveTo($x, $y);

	for($j=0; $j<=20; ++$j) {
		$i->rotateTo((20-$j)*(20-$j)/30);
		$i->multColor(1.0, 1.0, 1.0, $j/20);
		$movie->nextFrame();
	}

	return $i;
}

function stretchdownin($movie, $shape, $x, $y) {
	$i = $movie->add($shape);

	for($j=0; $j<=20; ++$j) {
		$i->moveTo($x, $y*$j/20);
		$i->scaleTo(1.0, $j/20);
		$movie->nextFrame();
	}

	return $i;
}

function stretchupin($movie, $shape, $x, $y) {
	$i = $movie->add($shape);

	for($j=0; $j<=20; ++$j) {
		$i->moveTo($x,$y+$y*(20-$j)/20);
		$i->scaleTo(1.0, $j/20);
		$movie->nextFrame();
	}

	return $i;
}

function doubleslidein($movie, $shape, $x, $y) {
	$i1 = $movie->add($shape);
	$i2 = $movie->add($shape);

	for($j=0; $j<=20; ++$j) {
		$i1->moveTo($x-($j-20)*($j-20)/2, $y);
		$i2->moveTo($x+($j-20)*($j-20)/2, $y);
		$i1->multColor(1.0, 1.0, 1.0, $j*$j/400);
		$i2->multColor(1.0, 1.0, 1.0, $j*$j/400);
		$movie->nextFrame();
	}

	$movie->remove($i2);
	return $i1;
}

$infuncs = array('fadein', 'sliderightin', 'slideleftin', 'zoomin', 'doubleslidein',
	'skewin', 'rotatein', 'stretchupin', 'stretchdownin');
?>