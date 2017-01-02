<?php
/**
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2011-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for the String data format object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Data_Format_StringTest
extends Horde_Imap_Client_Data_Format_String_TestBase
{
    protected $cname = 'Horde_Imap_Client_Data_Format_String';

    protected function getTestObs()
    {
        return array(
            new $this->cname('Foo'),
            new $this->cname('Foo('),
            /* This is an invalid atom, but valid string. */
            new $this->cname('Foo]'),
            /* This string requires a literal. */
            new $this->cname("Foo\n]"),
            /* This string requires a binary literal. */
            new $this->cname("12\x00\n3")
        );
    }

    public function stringRepresentationProvider()
    {
        return $this->createProviderArray(array(
            'Foo',
            'Foo(',
            'Foo]',
            "Foo\n]",
            "12\x00\n3"
        ));
    }

    public function escapeProvider()
    {
        return $this->createProviderArray(array(
            '"Foo"',
            '"Foo("',
            '"Foo]"',
            false,
            false
        ));
    }

    public function verifyProvider()
    {
        return $this->createProviderArray(array(
            true,
            true,
            true,
            true,
            true
        ));
    }

    public function binaryProvider()
    {
        return $this->createProviderArray(array(
            false,
            false,
            false,
            false,
            true
        ));
    }

    public function literalProvider()
    {
        return $this->createProviderArray(array(
            false,
            false,
            false,
            true,
            true
        ));
    }

    public function quotedProvider()
    {
        return $this->createProviderArray(array(
            true,
            true,
            true,
            false,
            false
        ));
    }

    public function escapeStreamProvider()
    {
        return $this->escapeProvider();
    }

    public function nonasciiInputProvider()
    {
        return array(
            array(false)
        );
    }

}
