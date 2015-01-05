<?php
/**
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @category   Horde
 * @copyright  2011-2015 Horde LLC
 * @license    http://www.horde.org/licenses/bsd New BSD License
 * @package    Mail
 * @subpackage UnitTests
 */

/**
 * Test the mbox parsing objecct.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/bsd New BSD License
 * @package    Mail
 * @subpackage UnitTests
 */
class Horde_Mail_MboxParseTest extends PHPUnit_Framework_TestCase
{
    public function testMboxParse()
    {
        $parse = new Horde_Mail_Mbox_Parse(__DIR__ . '/fixtures/test.mbox');

        $this->assertEquals(
            2,
            count($parse)
        );

        $i = 0;
        foreach ($parse as $key => $val) {
            $this->assertEquals(
                $i++,
                $key
            );

            $this->assertInternalType(
                'array',
                $val
            );

            $this->assertEquals(
                "Return-Path: <bugs@horde.org>\r\n",
                fgets($val['data'])
            );
        }
    }

    /**
     * @dataProvider emlParseProvider
     */
    public function testEmlParse($data, $first_line)
    {
        $parse = new Horde_Mail_Mbox_Parse($data);

        $this->assertEquals(
            1,
            count($parse)
        );

        $val = $parse[0];

        $this->assertInternalType(
            'array',
            $val
        );

        $this->assertEquals(
            $first_line . "\r\n",
            fgets($val['data'])
        );
    }

    public function emlParseProvider()
    {
        return array(
            array(
                __DIR__ . '/fixtures/test.eml',
                'Return-Path: <bugs@horde.org>'
            ),
            array(
                __DIR__ . '/fixtures/test2.eml',
                'Return-Path: <test@example.com>'
            )
        );
    }

    /**
     * @expectedException Horde_Mail_Exception
     */
    public function testBadData()
    {
        new Horde_Mail_Mbox_Parse(__DIR__ . '/noexist');
    }

}
