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
    public function testQueryWithDependencies()
    {
        /* LANGUAGE requires NAMESPACE */
        $c = new Horde_Imap_Client_Data_Capability_Imap();
        $c->add('LANGUAGE');

        $this->assertFalse($c->query('LANGUAGE'));

        $c->add('NAMESPACE');

        $this->assertTrue($c->query('LANGUAGE'));
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

}
