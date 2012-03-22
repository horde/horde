<?php
/**
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/bsd
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Autoload.php';

/**
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/bsd
 */
class Horde_Support_StringStreamTest extends PHPUnit_Framework_TestCase
{
    public function testMemoryUsage()
    {
        $bytes = 1024 * 1024;
        $string = str_repeat('*', $bytes);
        $memoryUsage = memory_get_usage();

        $stream = new Horde_Support_StringStream($string);
        $memoryUsage2 = memory_get_usage();
        $this->assertLessThan($memoryUsage + $bytes, $memoryUsage2);

        $fp = $stream->fopen();
        $memoryUsage3 = memory_get_usage();
        $this->assertLessThan($memoryUsage + $bytes, $memoryUsage3);

        while (!feof($fp)) { fread($fp, 1024); }
        $memoryUsage4 = memory_get_usage();
        $this->assertLessThan($memoryUsage + $bytes, $memoryUsage4);
    }
}
