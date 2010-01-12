<?php
/**
 * @package Horde_Rdo
 * @subpackage UnitTests
 */

// class definitions.
require './Clotho.php';


// one-to-one
$im = new ItemMapper();

$i = $im->findOne(3);
echo "({$i->item_id}) {$i->item_name} has parent:\n";
echo "  ({$i->parent->item_id}) {$i->parent->item_name}\n";


// one-to-many
$rm = new ResourceMapper();

$r = $rm->findOne(1);
echo "Resource ({$r->resource_id}) {$r->resource_name} has " . count($r->availabilities) . " availabilities:\n";
foreach ($r->availabilities as $ra) {
    echo '  (' . $ra->availability_id . ') ' . $ra->resource->resource_name . " on " . strftime('%x %X', $ra->availability_date) . " (" . $ra->availability_hours . " hours)\n";
}


// many-to-one
$ram = new ResourceAvailabilityMapper();

$ra = $ram->findOne(1);
echo "Resource Availability ({$ra->availability_id}) " . strftime('%x %X', $ra->availability_date) . " has resource:\n";
echo "  ({$ra->resource->resource_id}) {$ra->resource->resource_name}\n";


// many-to-many
echo "Listing all Items and their Resources:\n\n";
$im = new ItemMapper();
foreach ($im->find() as $i) {
    if (count($i->resources)) {
        echo " (" . $i->item_id . ") " . $i->item_name . " has resources:\n";
        foreach ($i->resources as $r) {
            echo '  (' . $r->resource_id . ') ' . $r->resource_name . "\n";
        }
    }
}

echo "\n\nListing all Resources and their Items:\n\n";
$rm = new ResourceMapper();
foreach ($rm->find() as $r) {
    if (count($r->items)) {
        echo " (" . $r->resource_id . ") " . $r->resource_name . " has items:\n";
        foreach ($r->items as $i) {
            echo '  (' . $i->item_id . ') ' . $i->item_name . "\n";
        }
    }
}
