<?php

require_once dirname(__FILE__) . '/common.php';
$ical = new Horde_Icalendar();
$ical->parseVCalendar(file_get_contents(dirname(__FILE__) . '/fixtures/bug10324.ics'));
                               var_dump($ical);