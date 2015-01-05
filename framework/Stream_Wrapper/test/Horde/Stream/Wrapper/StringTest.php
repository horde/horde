<?php
/**
 * Copyright 2008-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @category   Horde
 * @copyright  2008-2015 Horde LLC
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Stream_Wrapper
 * @subpackage UnitTests
 */

/**
 * Tests for the String wrapper.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2008-2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Stream_Wrapper
 * @subpackage UnitTests
 */
class Horde_Stream_Wrapper_StringTest extends PHPUnit_Framework_TestCase
{
    public function testUsage()
    {
        $string = 'ABCDE12345fghij';

        $stream = Horde_Stream_Wrapper_String::getStream($string);

        $this->assertEquals('ABCDE12345fghij', fread($stream, 1024));
        $this->assertEquals(true, feof($stream));
        $this->assertEquals(0, fseek($stream, 0));
        $this->assertEquals(0, fseek($stream, 0));
        $this->assertEquals(0, ftell($stream));
        $this->assertEquals(0, fseek($stream, 5, SEEK_CUR));
        $this->assertEquals(5, ftell($stream));
        $this->assertEquals(10, fwrite($stream, '0000000000'));
        $this->assertEquals(0, fseek($stream, 0, SEEK_END));
        $this->assertEquals(15, ftell($stream));
        $this->assertEquals(false, feof($stream));

        fclose($stream);
    }

    public function testMemoryUsage()
    {
        $bytes = 1024 * 1024;
        $string = str_repeat('*', $bytes);
        $memoryUsage = memory_get_usage();

        $stream = Horde_Stream_Wrapper_String::getStream($string);
        $memoryUsage2 = memory_get_usage();
        $this->assertLessThan($memoryUsage + $bytes, $memoryUsage2);

        while (!feof($stream)) {
            fread($stream, 1024);
        }
        $memoryUsage3 = memory_get_usage();
        $this->assertLessThan($memoryUsage + $bytes, $memoryUsage3);
    }

}
