<?php

require_once __DIR__ . '/../../../../Date/lib/Horde/Date.php';
require_once __DIR__ . '/../../../../Exception/lib/Horde/Exception.php';
require_once __DIR__ . '/../../../../Exception/lib/Horde/Exception/Wrapped.php';
require_once __DIR__ . '/../../../../Mime/lib/Horde/Mime.php';
require_once __DIR__ . '/../../../../Mime/lib/Horde/Mime/Address.php';
require_once __DIR__ . '/../../../../Support/lib/Horde/Support/Uuid.php';
require_once __DIR__ . '/../../../../Translation/lib/Horde/Translation.php';
require_once __DIR__ . '/../../../../Util/lib/Horde/String.php';
require_once __DIR__ . '/../../../../Util/lib/Horde/Util.php';
require_once __DIR__ . '/../../../lib/Horde/Icalendar.php';

foreach (glob(__DIR__ . '/../../../lib/Horde/Icalendar/*.php') as $val) {
    require_once $val;
}
