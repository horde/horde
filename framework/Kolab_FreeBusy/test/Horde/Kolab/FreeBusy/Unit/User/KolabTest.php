<?php
/**
 * Test the Kolab user.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the Kolab user.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Unit_User_KolabTest
extends Horde_Kolab_FreeBusy_TestCase
{
    public function testGetPrimaryId()
    {
        $this->assertEquals(
            'mail@example.org', $this->getUser()->getPrimaryId()
        );
    }

    public function testGetDomain()
    {
        $this->assertEquals('example.org', $this->getUser()->getDomain());
    }

    public function testGetGroups()
    {
        $this->assertEquals(
            array('group@example.org'), $this->getKolabUser()->getGroups()
        );
    }

    public function testIsAuthenticated()
    {
        $this->assertTrue($this->getAuthUser()->isAuthenticated());
    }
}