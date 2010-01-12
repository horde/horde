<?php
/**
 * @package Horde_Rdo
 */

require './Clotho.php';

$im = new ItemMapper();
$dm = new DependencyMapper();
$cm = new CalendarMapper();
$rm = new ResourceMapper();
$ram = new ResourceAvailabilityMapper();

echo count($im) . "\n";
echo count($dm) . "\n";
echo count($cm) . "\n";
echo count($rm) . "\n";
echo count($ram) . "\n";
