--TEST--
Import of all-day events
--FILE--
<?php

class Driver {
    public $calendar = 'foo';
}
class Prefs {
    function getValue()
    {
        return 0;
    }
}
$prefs = new Prefs;

require 'Date/Calc.php';
require 'Horde/Date.php';
require 'Horde/String.php';
require 'Horde/Icalendar.php';

$iCal = new Horde_Icalendar();
$iCal->parsevCalendar(file_get_contents(dirname(__FILE__) . '/allday.ics'));
$components = $iCal->getComponents();

define('KRONOLITH_BASE', dirname(__FILE__) . '/../..');
require KRONOLITH_BASE . '/lib/Kronolith.php';
require KRONOLITH_BASE . '/lib/Driver.php';
require KRONOLITH_BASE . '/lib/Event.php';
require KRONOLITH_BASE . '/lib/Event/Sql.php';
foreach ($components as $content) {
    if ($content instanceof Horde_Icalendar_Vevent) {
        $event = new Kronolith_Event_Sql(new Driver);
        $event->fromiCalendar($content);
        echo (string)$event->start . "\n";
        echo (string)$event->end . "\n";
        var_export($event->isAllDay());
        echo "\n";
    }
}

?>
--EXPECT--
2006-10-23 00:00:00
2006-10-24 00:00:00
true
2006-10-23 00:00:00
2006-10-24 00:00:00
true
2006-10-23 12:00:00
2006-10-23 13:00:00
false
