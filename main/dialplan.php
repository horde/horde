<?php
if (!defined(SHOUT_BASE)) {
    define(SHOUT_BASE, dirname(__FILE__));
}
$dialplan = $shout->getDialplan($context);
require SHOUT_TEMPLATES . "/dialplan/dialplanlist.inc";