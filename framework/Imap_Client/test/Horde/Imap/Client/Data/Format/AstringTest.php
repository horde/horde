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
 * Tests for the Astring data format object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Data_Format_AstringTest
extends Horde_Imap_Client_Data_Format_String_TestBase
{
    protected $cname = 'Horde_Imap_Client_Data_Format_Astring';

    protected function getTestObs()
    {
        return array(
            new $this->cname('Foo'),
            new $this->cname('Foo('),
            /* This is an invalid atom, but valid (non-quoted) astring. */
            new $this->cname('Foo]'),
            new $this->cname('')
        );
    }

    public function stringRepresentationProvider()
    {
        return $this->createProviderArray(array(
            'Foo',
            'Foo(',
            'Foo]',
            ''
        ));
    }

    public function escapeProvider()
    {
        return $this->createProviderArray(array(
            'Foo',
            '"Foo("',
            'Foo]',
            '""'
        ));
    }

    public function verifyProvider()
    {
        return $this->createProviderArray(array(
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
            false
        ));
    }

    public function literalProvider()
    {
        return $this->binaryProvider();
    }

    public function quotedProvider()
    {
        return $this->createProviderArray(array(
            false,
            true,
            false,
            true
        ));
    }

    public function escapeStreamProvider()
    {
        return $this->createProviderArray(array(
            '"Foo"',
            '"Foo("',
            '"Foo]"',
            '""'
        ));
    }

    public function nonasciiInputProvider()
    {
        return array(
            array(false)
        );
    }

}
