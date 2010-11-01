<?php
/**
 * Fake class to autoload generated Thrift code
 */
interface Horde_Thrift
{
}

$GLOBALS['THRIFT_ROOT'] = 'Horde/Thrift/thrift_root';
include_once $GLOBALS['THRIFT_ROOT'] . '/Thrift.php';
include_once $GLOBALS['THRIFT_ROOT'] . '/transport/TSocket.php';
include_once $GLOBALS['THRIFT_ROOT'] . '/transport/TFramedTransport.php';
include_once $GLOBALS['THRIFT_ROOT'] . '/protocol/TBinaryProtocol.php';
