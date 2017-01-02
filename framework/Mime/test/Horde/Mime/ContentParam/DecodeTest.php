<?php
/**
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @copyright  2010-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_Mime_ContentParam_Decode class.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2010-2016 Horde LLC
 * @internal
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_ContentParam_DecodeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider decodeProvider
     */
    public function testDecode($string, $expected)
    {
        $decode = new Horde_Mime_ContentParam_Decode();
        $res = $decode->decode($string);

        ksort($res);

        $this->assertEquals(
            $expected,
            $res
        );
    }

    public function decodeProvider()
    {
        return array(
            array(
                'foo=bar',
                array(
                    'foo' => 'bar'
                )
            ),
            array(
                'foofoo=b',
                array(
                    'foofoo' => 'b'
                )
            ),
            array(
                'f=barbar',
                array(
                    'f' => 'barbar'
                )
            ),
            array(
                'foo=bar; a=b',
                array(
                    'a' => 'b',
                    'foo' => 'bar'
                )
            ),
            array(
                '  foo =    bar    ; a     =b ;c   =   d   ',
                array(
                    'a' => 'b',
                    'c' => 'd',
                    'foo' => 'bar'
                )
            )
        );
    }

}
