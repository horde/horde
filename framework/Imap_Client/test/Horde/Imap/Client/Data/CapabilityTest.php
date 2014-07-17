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
 * Tests for the Capability object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Data_CapabilityTest
extends PHPUnit_Framework_TestCase
{
    public function testQuery()
    {
        $c = new Horde_Imap_Client_Data_Capability();
        $c->add('FOO');

        $this->assertTrue($c->query('FOO'));
        $this->assertTrue($c->query('foo'));

        $this->assertFalse($c->query('BAR'));

        $c->add('bar');

        $this->assertTrue($c->query('bar'));
        $this->assertTrue($c->query('BAR'));
    }

    public function testQueryParameters()
    {
        $c = new Horde_Imap_Client_Data_Capability();
        $c->add('FOO', array('A', 'B'));

        $this->assertTrue($c->query('FOO'));
        $this->assertTrue($c->query('foo'));
        $this->assertTrue($c->query('FOO', 'A'));
        $this->assertTrue($c->query('FOO', 'B'));
        $this->assertTrue($c->query('FOO', 'a'));
        $this->assertTrue($c->query('FOO', 'b'));
        $this->assertTrue($c->query('foo', 'a'));
        $this->assertTrue($c->query('foo', 'b'));

        $this->assertFalse($c->query('FOO', 'C'));
    }

    public function testIncrementalParameterAddition()
    {
        $c = new Horde_Imap_Client_Data_Capability();
        $c->add('FOO', 'A');
        $c->add('FOO', 'B');

        $this->assertTrue($c->query('FOO'));
        $this->assertTrue($c->query('FOO', 'A'));
        $this->assertTrue($c->query('FOO', 'B'));
        $this->assertTrue($c->query('FOO', 'a'));
        $this->assertTrue($c->query('FOO', 'b'));
        $this->assertTrue($c->query('foo', 'a'));
        $this->assertTrue($c->query('foo', 'b'));

        $this->assertFalse($c->query('FOO', 'C'));

        /* This should not affect the current parameter list. */
        $c->add('FOO');

        $this->assertTrue($c->query('FOO', 'A'));
        $this->assertTrue($c->query('FOO', 'B'));

        $c->add('FOO', array('C', 'D'));

        $this->assertTrue($c->query('FOO', 'A'));
        $this->assertTrue($c->query('FOO', 'B'));
        $this->assertTrue($c->query('FOO', 'C'));
        $this->assertTrue($c->query('FOO', 'D'));
    }

    public function testRemoval()
    {
        $c = new Horde_Imap_Client_Data_Capability();
        $c->add('FOO');

        $this->assertTrue($c->query('FOO'));

        $c->remove('FOO');

        $this->assertFalse($c->query('FOO'));

        $c->add('BAR', array('A', 'B', 'C'));
        $c->remove('BAR', array('A', 'C'));

        $this->assertTrue($c->query('BAR'));
        $this->assertFalse($c->query('BAR', 'A'));
        $this->assertTrue($c->query('BAR', 'B'));
        $this->assertFalse($c->query('BAR', 'C'));

        $c->remove('BAR', 'b');

        $this->assertFalse($c->query('BAR'));
        $this->assertFalse($c->query('BAR', 'A'));
        $this->assertFalse($c->query('BAR', 'B'));
        $this->assertFalse($c->query('BAR', 'C'));
    }

    public function testGetParams()
    {
        $c = new Horde_Imap_Client_Data_Capability();
        $c->add('FOO', 'A');
        $c->add('FOO', 'B');
        $c->add('BAR');

        $this->assertNotEmpty($c->getParams('FOO'));
        $this->assertEquals(
            array('A', 'B'),
            $c->getParams('FOO')
        );
        $this->assertEmpty($c->getParams('BAR'));
        $this->assertEmpty($c->getParams('BAZ'));
    }

}
