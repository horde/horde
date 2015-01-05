<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (PHP). If you
 * did not receive this file, see http://www.php.net/license/3_01.txt.
 *
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @license    http://www.php.net/license/3_01.txt PHP 3.01
 * @package    horde_lz4
 * @subpackage UnitTests
 */

/**
 * Tests for the horde_lz4 extension.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @ignore
 * @license    http://www.php.net/license/3_01.txt PHP 3.01
 * @package    horde_lz4
 * @subpackage UnitTests
 */
class horde_lz4_UnitTest extends PHPUnit_Framework_TestCase
{
    private $data;

    public function setUp()
    {
        if (!extension_loaded('horde_lz4')) {
            $this->markTestSkipped('horde_lz4 extension not installed.');
        }

        $this->data = file_get_contents(__DIR__ . '/fixtures/data.txt');
    }

    // Test horde_lz4_compress() function : basic functionality
    public function testCompressBasic()
    {
        // Compressing a big string
        $output = horde_lz4_compress($this->data);
        $this->assertEquals(
            0,
            strcmp(horde_lz4_uncompress($output), $this->data)
        );

        // Compressing a smaller string
        $smallstring = "A small string to compress\n";
        $output = horde_lz4_compress($smallstring);
        $this->assertEquals(
            0,
            strcmp(horde_lz4_uncompress($output), $smallstring)
        );
    }

    // Test horde_lz4_compress() function : error conditions
    public function testCompressErrorConditionOne()
    {
        $this->setExpectedException('PHPUnit_Framework_Error_Warning');

        // Zero arguments
        horde_lz4_compress();
    }

    // Test horde_lz4_compress() function : error conditions
    public function testCompressErrorConditionTwo()
    {
        $this->setExpectedException('PHPUnit_Framework_Error_Warning');

        // Test horde_lz4_compress with one more than the expected number
        // of arguments
        $data = 'string_val';
        $extra_arg = 10;
        horde_lz4_compress($data, false, $extra_arg);
    }

    // Test horde_lz4_compress() function : error conditions
    public function testCompressErrorConditionThree()
    {
        $this->setExpectedException('PHPUnit_Framework_Error_Warning');

        horde_lz4_compress(new stdClass);
    }

    // Test horde_lz4_compress() function : variation
    public function testCompressVariation()
    {
        $output = horde_lz4_compress($this->data);

        $this->assertNotEquals(
            md5($output),
            md5(horde_lz4_compress($output))
        );
    }

    // Test horde_lz4_uncompress() function : basic functionality
    public function testUncompressBasic()
    {
        $compressed = horde_lz4_compress($this->data);
        $this->assertEquals(
            0,
            strcmp($this->data, horde_lz4_uncompress($compressed))
        );
    }

    // Test horde_lz4_uncompress() function : error conditions
    public function testUncompressErrorConditionOne()
    {
        $this->setExpectedException('PHPUnit_Framework_Error_Warning');

        // Zero arguments
        horde_lz4_uncompress();
    }

    // Test horde_lz4_uncompress() function : error conditions
    public function testUncompressErrorConditionTwo()
    {
        $this->setExpectedException('PHPUnit_Framework_Error_Warning');

        // Test horde_lz4_uncompress with one more than the expected number
        // of arguments
        $data = 'string_val';
        $extra_arg = 10;
        horde_lz4_uncompress($data, $extra_arg);
    }

    // Test horde_lz4_uncompress() function : error conditions
    public function testUncompressErrorConditionThree()
    {
        $this->setExpectedException('PHPUnit_Framework_Error_Warning');

        horde_lz4_uncompress(new stdClass);
    }

    // Test horde_lz4_uncompress() function : error conditions
    public function testUncompressErrorConditionFour()
    {
        $this->setExpectedException('PHPUnit_Framework_Error_Warning');

        // Testing with incorrect arguments
        horde_lz4_uncompress(123);
    }

    // Test horde_lz4_compress() function : high compression
    public function testHighCompression()
    {
        // Compressing a big string
        $output = horde_lz4_compress($this->data, true);
        $this->assertEquals(
            0,
            strcmp(horde_lz4_uncompress($output), $this->data)
        );

        // Compressing a smaller string
        $smallstring = "A small string to compress\n";
        $output = horde_lz4_compress($smallstring, true);
        $this->assertEquals(
            0,
            strcmp(horde_lz4_uncompress($output), $smallstring)
        );
    }

    // Test horde_lz4_uncompress() function : bad input (non-lz4 data)
    public function testUncompressBadInput()
    {
        // Bad data is missing the Horde-LZ4 header and is not LZ4 data.
        $bad_data = "12345678";
        $this->assertFalse(horde_lz4_uncompress($bad_data));
    }

}
