<?php
/**
 * Copyright 2011-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for the Number data format object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Data_Format_NumberTest
extends Horde_Imap_Client_Data_Format_TestBase
{
    protected function getTestObs()
    {
        return array(
            new Horde_Imap_Client_Data_Format_Number(1),
            new Horde_Imap_Client_Data_Format_Number('1'),
            /* Invalid number. */
            new Horde_Imap_Client_Data_Format_Number('Foo')
        );
    }

    /**
     * @dataProvider stringRepresentationProvider
     */
    public function testStringRepresentation($ob, $expected)
    {
        $this->assertEquals(
            $expected,
            strval($ob)
        );
    }

    public function stringRepresentationProvider()
    {
        return $this->createProviderArray(array(
            '1',
            '1',
            '0'
        ));
    }

    /**
     * @dataProvider stringRepresentationProvider
     */
    public function testEscape($ob, $expected)
    {
        $this->assertEquals(
            $expected,
            $ob->escape()
        );
    }

    /**
     * @dataProvider verifyProvider
     */
    public function testVerify($ob, $expected)
    {
        try {
            $ob->verify();
            if ($expected) {
                $this->fail();
            }
        } catch (Horde_Imap_Client_Data_Format_Exception $e) {
            if (!$expected) {
                $this->fail();
            }
        }
    }

    public function verifyProvider()
    {
        return $this->createProviderArray(array(
            false,
            false,
            true
        ));
    }

}
