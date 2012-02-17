<?php
/**
 * @package Image
 */

$context = array(
    'tmpdir' => Horde::getTempdir(),
);
$image = new Horde_Image_Gd(
    array('height' => 400, 'width' => 400),
    array('tmpdir' => Horde::getTempdir())
);

$image->rectangle(30, 30, 100, 60, 'black', 'yellow');
$image->roundedRectangle(30, 30, 100, 60, 15, 'black', 'red');
$image->circle(30, 30, 30, 'black', 'blue');
$image->polygon(array(array('x' => 30, 'y' => 50), array('x' => 40, 'y' => 60), array('x' => 50, 'y' => 40)), 'green', 'green');
$image->arc(100, 100, 100, 0, 70, 'black', 'green');
$image->brush(100, 300, 'red', 'circle');

$image->line(0, 200, 500, 200, 'darkblue', 2);
$image->line(200, 200, 200, 500, 'darkblue', 2);

$image->polyline(array(array('x' => 130, 'y' => 150), array('x' => 140, 'y' => 160), array('x' => 150, 'y' => 140)), 'black', 5);

$image->text('Hello World', 100, 100, 'arial', 'purple');

$image->display();
