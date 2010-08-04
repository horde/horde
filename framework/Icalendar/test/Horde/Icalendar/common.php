<?php

require_once dirname(__FILE__) . '/../../../../Date/lib/Horde/Date.php';
require_once dirname(__FILE__) . '/../../../../Exception/lib/Horde/Exception.php';
require_once dirname(__FILE__) . '/../../../../Exception/lib/Horde/Exception/Prior.php';
require_once dirname(__FILE__) . '/../../../../Mime/lib/Horde/Mime.php';
require_once dirname(__FILE__) . '/../../../../Mime/lib/Horde/Mime/Address.php';
require_once dirname(__FILE__) . '/../../../../Support/lib/Horde/Support/Uuid.php';
require_once dirname(__FILE__) . '/../../../../Util/lib/Horde/String.php';
require_once dirname(__FILE__) . '/../../../../Util/lib/Horde/Util.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/Icalendar.php';

foreach (glob(dirname(__FILE__) . '/../../../lib/Horde/Icalendar/*.php') as $val) {
    require_once $val;
}
