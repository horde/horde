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
 * Tests for UTF7-IMAP <-> UTF-8 conversions.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Utf7ConvertTest extends PHPUnit_Framework_TestCase
{
    public function testBasicConversion()
    {
        $orig = 'Envoyé';

        $utf7_imap = Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($orig);
        $this->assertEquals(
            'Envoy&AOk-',
            $utf7_imap
        );

        $utf8 = Horde_Imap_Client_Utf7imap::Utf7ImapToUtf8($utf7_imap);
        $this->assertEquals(
            $orig,
            $utf8
        );

        $orig = 'Töst-';

        $utf7_imap = Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($orig);
        $this->assertEquals(
            'T&APY-st-',
            $utf7_imap
        );

        $utf8 = Horde_Imap_Client_Utf7imap::Utf7ImapToUtf8($utf7_imap);
        $this->assertEquals(
            $orig,
            $utf8
        );
    }

    public function testAmpersandConversion()
    {
        $orig = '&';

        $utf7_imap = Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($orig);
        $this->assertEquals(
            '&-',
            $utf7_imap
        );

        $utf8 = Horde_Imap_Client_Utf7imap::Utf7ImapToUtf8($utf7_imap);
        $this->assertEquals(
            $orig,
            $utf8
        );

        $orig = '&-';
        $utf7_imap = Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($orig, false);
        $this->assertEquals(
            '&-',
            $utf7_imap
        );

        $orig = 'Envoy&AOk-';
        $utf7_imap = Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($orig, false);
        $this->assertEquals(
            'Envoy&AOk-',
            $utf7_imap
        );

        $orig = 'T&APY-st-';
        $utf7_imap = Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($orig, false);
        $this->assertEquals(
            'T&APY-st-',
            $utf7_imap
        );

        // Bug #10133
        $orig = 'Entw&APw-rfe';

        $utf7_imap = Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($orig, false);
        $this->assertEquals(
            $orig,
            $utf7_imap
        );
    }

    public function testBug10093()
    {
        $orig = 'Foo&Bar-2011';

        $utf7_imap = Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap(Horde_Imap_Client_Mailbox::get($orig));
        $this->assertEquals(
            'Foo&-Bar-2011',
            $utf7_imap
        );

        $utf8 = Horde_Imap_Client_Utf7imap::Utf7ImapToUtf8($utf7_imap);
        $this->assertEquals(
            $orig,
            $utf8
        );
    }

}
