<?php
/**
 * @package Rdo
 */

require './Clotho.php';

$im = new ItemMapper($conf['adapter']);
$dm = new DependencyMapper($conf['adapter']);
$cm = new CalendarMapper($conf['adapter']);
$rm = new ResourceMapper($conf['adapter']);
$ram = new ResourceAvailabilityMapper($conf['adapter']);

echo count($im) . "\n";
echo count($dm) . "\n";
echo count($cm) . "\n";
echo count($rm) . "\n";
echo count($ram) . "\n";
