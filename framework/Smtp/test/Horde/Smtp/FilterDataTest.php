<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
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
    private $stream;

    public function setUp()
    {
        $this->stream = fopen('php://temp', 'r+');
        stream_filter_register('horde_smtp_data', 'Horde_Smtp_Filter_Data');
        stream_filter_append($this->stream, 'horde_smtp_data', STREAM_FILTER_READ);
    }

    public function tearDown()
    {
        fclose($this->stream);
    }

    public function testLeadingPeriodsEscape()
    {
        fwrite($this->stream, "Foo\r\n.\r\nFoo\r\n");
        rewind($this->stream);

        $this->assertEquals(
            "Foo\r\n..\r\nFoo\r\n",
            stream_get_contents($this->stream)
        );
    }

}
