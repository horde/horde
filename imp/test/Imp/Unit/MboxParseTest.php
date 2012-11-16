<?php
/**
 * Test the mbox parsing library.
 *
 * PHP version 5
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/gpl GPL
 * @link       http://pear.horde.org/index.php?package=Imp
 * @package    IMP
 * @subpackage UnitTests
 */

/**
 * Test the mbox parsing library.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/gpl GPL
 * @link       http://pear.horde.org/index.php?package=Imp
 * @package    IMP
 * @subpackage UnitTests
 */
class Imp_Unit_MboxParseTest extends PHPUnit_Framework_TestCase
{
    public function testMboxParse()
    {
        $parse = new IMP_Mbox_Parse(__DIR__ . '/../fixtures/test.mbox');

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

    public function testEmlParse()
    {
        $parse = new IMP_Mbox_Parse(__DIR__ . '/../fixtures/test.eml');

        $this->assertEquals(
            0,
            count($parse)
        );

        $val = $parse[0];

        $this->assertInternalType(
            'array',
            $val
        );

        $this->assertEquals(
            "Return-Path: <bugs@horde.org>\r\n",
            fgets($val['data'])
        );
    }

    /**
     * @expectedException IMP_Exception
     */
    public function testBadData()
    {
        new IMP_Mbox_Parse(__DIR__ . '/noexist');
    }

}
