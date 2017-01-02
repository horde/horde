<?php
/**
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @copyright  2014-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_Mime_Mdn object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014-2016 Horde LLC
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
    public function testGetMdnReturnAddr($email)
    {
        $h = new Horde_Mime_Headers();
        $ob = new Horde_Mime_Mdn($h);

        if (!is_null($email)) {
            $ob->addMdnRequestHeaders($email);
        }

        $this->assertEquals(
            strval($email),
            $ob->getMdnReturnAddr()
        );
    }

    public function getMdnReturnAddrProvider()
    {
        $email = 'foo1@example.com, Test <foo2@example.com>';

        $rfc822 = new Horde_Mail_Rfc822();
        $mail_ob = $rfc822->parseAddressList($email);

        return array(
            array(null),
            array('foo@example.com'),
            array($email),
            array($mail_ob)
        );
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
