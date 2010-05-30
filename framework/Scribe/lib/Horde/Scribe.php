<?php
/**
 * Fake class to autoload Scribe and required Thrift classes
 */
class Horde_Scribe extends Horde_Thrift
{
}

include_once $GLOBALS['THRIFT_ROOT'] . '/scribe.php';
