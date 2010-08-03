--TEST--
Bug #7068: Single EXDATE properties not imported
--FILE--
<?php

class Driver {
    public $calendar = 'foo';
}

require 'Date/Calc.php';
require 'Horde/Date.php';
require 'Horde/Date/Recurrence.php';
require 'Horde/String.php';
require 'Horde/Icalendar.php';

$iCal = new Horde_Icalendar();
$iCal->parsevCalendar(file_get_contents(dirname(__FILE__) . '/bug7068.ics'));
$components = $iCal->getComponents();

define('KRONOLITH_BASE', dirname(__FILE__) . '/../..');
require KRONOLITH_BASE . '/lib/Kronolith.php';
require KRONOLITH_BASE . '/lib/Driver.php';
require KRONOLITH_BASE . '/lib/Event.php';
require KRONOLITH_BASE . '/lib/Event/Sql.php';
$event = new Kronolith_Event_Sql(new Driver);
foreach ($components as $content) {
    if ($content instanceof Horde_Icalendar_vevent) {
        $event->fromiCalendar($content);
        var_export($event->recurrence->exceptions);
        echo "\n";
    }
}

?>
--EXPECT--
array (
  0 => '20080729',
)
array (
  0 => '20080722',
  1 => '20080729',
)
