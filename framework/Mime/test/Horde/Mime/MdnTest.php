<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_Mime_Mdn object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @internal
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_MdnTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getMdnReturnAddrProvider
     */
    public function testGetMdnReturnAddr($h, $expected)
    {
        $ob = new Horde_Mime_Mdn($h);
        $this->assertEquals($expected, $ob->getMdnReturnAddr());
    }

    public function getMdnReturnAddrProvider()
    {
        $out = array();

        $h = new Horde_Mime_Headers();
        $out[] = array(clone $h, null);

        $email = 'foo@example.com';
        $h->addHeader(Horde_Mime_Mdn::MDN_HEADER, $email);
        $out[] = array(clone $h, $email);

        return $out;
    }

    /**
     * @dataProvider UserConfirmationNeededProvider
     */
    public function testUserConfirmationNeeded($h, $expected)
    {
        $ob = new Horde_Mime_Mdn($h);
        if ($expected) {
            $this->assertTrue($ob->userConfirmationNeeded());
        } else {
            $this->assertFalse($ob->userConfirmationNeeded());
        }
    }

    public function userConfirmationNeededProvider()
    {
        $out = array();

        $h = new Horde_Mime_Headers();
        $out[] = array(clone $h, true);

        $h->addHeader('Return-Path', 'foo@example.com');
        $out[] = array(clone $h, false);

        $h->addHeader('Return-Path', 'foo2@example.com');
        $out[] = array(clone $h, true);

        $h->replaceHeader('Return-Path', 'foo@example.com');

        $h->addHeader(Horde_Mime_Mdn::MDN_HEADER, 'FOO@example.com');
        $out[] = array(clone $h, true);

        $h->replaceHeader(Horde_Mime_Mdn::MDN_HEADER, 'foo@EXAMPLE.com');
        $out[] = array(clone $h, false);

        return $out;
    }

}
