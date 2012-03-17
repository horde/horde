<?php
/**
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Support_CombineStreamTest extends PHPUnit_Framework_TestCase
{
    public function testUsage()
    {
        $fp = fopen('php://temp', 'r+');
        fwrite($fp, '12345');

        $data = array('ABCDE', $fp, 'fghij');
        $ob = new Horde_Support_CombineStream($data);
        $stream = $ob->fopen();

        $this->assertEquals('ABCDE12345fghij', fread($stream, 1024));
        $this->assertEquals(true, feof($stream));
        $this->assertEquals(0, fseek($stream, 0));
        $this->assertEquals(-1, fseek($stream, 0));
        $this->assertEquals(0, ftell($stream));
        $this->assertEquals(0, fseek($stream, 5, SEEK_CUR));
        $this->assertEquals(5, ftell($stream));
        $this->assertEquals(10, fwrite($stream, '0000000000'));
        $this->assertEquals(0, fseek($stream, 0, SEEK_END));
        $this->assertEquals(20, ftell($stream));
        $this->assertEquals(false, feof($stream));

        fclose($stream);
    }
}
