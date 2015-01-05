<?php
/**
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2011-2015 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for the ListMailbox data format object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Data_Format_Mailbox_ListMailboxTest
extends Horde_Imap_Client_Data_Format_Mailbox_TestBase
{
    protected $cname = 'Horde_Imap_Client_Data_Format_ListMailbox';

    public function stringRepresentationProvider()
    {
        return array(
            array('Foo', 'Foo'),
            array('Foo(', 'Foo('),
            array('Foo]', 'Foo]'),
            array('Foo%Bar', 'Foo%Bar'),
            array('Foo*Bar', 'Foo*Bar'),
            array('Envoyé', 'Envoyé')
        );
    }

    public function escapeProvider()
    {
        return array(
            array('Foo', 'Foo'),
            array('Foo(', '"Foo("'),
            array('Foo]', 'Foo]'),
            /* Don't escape '%'. */
            array('Foo%Bar', 'Foo%Bar'),
            /* Don't escape '*'. */
            array('Foo*Bar', 'Foo*Bar'),
            array('Envoyé', 'Envoy&AOk-')
        );
    }

    public function verifyProvider()
    {
        return array(
            array('Foo', false),
            array('Foo(', false),
            array('Foo]', false),
            array('Foo%Bar', false),
            array('Foo*Bar', false),
            array('Envoyé', false)
        );
    }

    public function binaryProvider()
    {
        return array(
            array('Foo', false),
            array('Foo(', false),
            array('Foo]', false),
            array('Foo%Bar', false),
            array('Foo*Bar', false),
            array('Envoyé', false)
        );
    }

    public function literalProvider()
    {
        return array(
            array('Foo', false),
            array('Foo(', false),
            array('Foo]', false),
            array('Foo%Bar', false),
            array('Foo*Bar', false),
            array('Envoyé', false)
        );
    }

    public function quotedProvider()
    {
        return array(
            array('Foo', false),
            array('Foo(', true),
            array('Foo]', false),
            array('Foo%Bar', false),
            array('Foo*Bar', false),
            array('Envoyé', false)
        );
    }

    public function escapeStreamProvider()
    {
        return array(
            array('Foo', '"Foo"'),
            array('Foo(', '"Foo("'),
            array('Foo]', '"Foo]"'),
            array('Foo%Bar', '"Foo%Bar"'),
            array('Foo*Bar', '"Foo*Bar"'),
            array('Envoyé', '"Envoy&AOk-"')
        );
    }

}
