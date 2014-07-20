<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for the IMAP-specific capability object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Data_Capability_ImapTest
extends PHPUnit_Framework_TestCase
{
    public function testImpliedExtensions()
    {
        $c = new Horde_Imap_Client_Data_Capability_Imap();
        $c->add('QRESYNC');

        $this->assertTrue($c->query('QRESYNC'));

        /* QRESYNC implies CONDSTORE and ENABLE. */
        $this->assertTrue($c->query('CONDSTORE'));
        $this->assertTrue($c->query('ENABLE'));
    }

    public function testCmdlengthProperty()
    {
        $c = new Horde_Imap_Client_Data_Capability_Imap();

        $this->assertEquals(
            2000,
            $c->cmdlength
        );

        $c->add('CONDSTORE');

        $this->assertEquals(
            8000,
            $c->cmdlength
        );
    }

    public function isEnabled()
    {
        $c = new Horde_Imap_Client_Data_Capability_Imap();
        $c->add('FOO');

        $this->assertFalse($c->isEnabled('FOO'));

        $c->enable('FOO');

        $this->assertTrue($c->isEnabled('FOO'));
    }

    public function testEnable()
    {
        $c = new Horde_Imap_Client_Data_Capability_Imap();
        $c->enable('FOO');

        $this->assertTrue($c->isEnabled('Foo'));

        $c->enable('BAR=BAZ');

        $this->assertTrue($c->isEnabled('bar=baz'));

        $c->enable('FOO', false);

        $this->assertFalse($c->isEnabled('foo'));
    }

    public function testObserver()
    {
        $c = new Horde_Imap_Client_Data_Capability_Imap();
        $mock = $this->getMock('SplObserver');
        $mock->expects($this->once())
            ->method('update')
            ->with($this->equalTo($c));

        $c->attach($mock);

        $c->enable('FOO');
        /* Duplicate enable should not trigger update() again. */
        $c->enable('FOO');
    }

    public function testSerialize()
    {
        $c = new Horde_Imap_Client_Data_Capability_Imap();
        $c->add('FOO', 'A');
        $c->add('FOO', 'B');
        $c->add('BAR');
        $c->enable('BAR');

        $mock = $this->getMock('SplObserver');
        $mock->expects($this->never())
            ->method('update')
            ->with($this->equalTo($c));
        $c->attach($mock);

        $c_copy = unserialize(serialize($c));

        $this->assertTrue($c_copy->query('FOO', 'A'));
        $this->assertTrue($c_copy->query('FOO', 'B'));
        $this->assertTrue($c_copy->query('BAR'));

        $this->assertFalse($c_copy->isEnabled('BAR'));

        $c_copy->add('BAZ');
    }

}
