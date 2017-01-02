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
 * Tests for the Nstring data format object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Data_Format_NstringTest
extends Horde_Imap_Client_Data_Format_String_TestBase
{
    protected $cname = 'Horde_Imap_Client_Data_Format_Nstring';

    protected function getTestObs()
    {
        return array(
            new $this->cname('Foo'),
            new $this->cname('Foo('),
            /* This is an invalid atom, but valid nstring. */
            new $this->cname('Foo]'),
            new $this->cname()
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
            '"Foo"',
            '"Foo("',
            '"Foo]"',
            'NIL'
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
            true,
            true,
            true,
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
