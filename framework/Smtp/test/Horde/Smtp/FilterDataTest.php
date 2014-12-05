<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Smtp
 * @subpackage UnitTests
 */

/**
 * Test for the SMTP DATA filter.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Smtp
 * @subpackage UnitTests
 */
class Horde_Smtp_FilterDataTest extends Horde_Test_Case
{
    const FILTER_ID = 'horde_smtp_data';

    public function setUp()
    {
        stream_filter_register(self::FILTER_ID, 'Horde_Smtp_Filter_Data');
    }

    /**
     * @dataProvider escapeProvider
     */
    public function testEscape($in, $expected)
    {
        $stream = fopen('php://temp', 'r+');
        stream_filter_append($stream, self::FILTER_ID, STREAM_FILTER_READ);

        fwrite($stream, $in);
        rewind($stream);

        $this->assertEquals(
            $expected,
            stream_get_contents($stream)
        );
    }

    public function escapeProvider()
    {
        return array(
            array(
                "Foo\nBar",
                "Foo\r\nBar"
            ),
            array(
                "Foo\rBar",
                "Foo\r\nBar"
            ),
            array(
                "Foo\r\nBar",
                "Foo\r\nBar"
            ),
            array(
                "Foo\r\n.\r\nBar\r\n",
                "Foo\r\n..\r\nBar\r\n"
            ),
            array(
                "Foo\r.\r\n\n .Foo\n\r\nBaz",
                "Foo\r\n..\r\n\r\n .Foo\r\n\r\nBaz"
            )
        );
    }

}
