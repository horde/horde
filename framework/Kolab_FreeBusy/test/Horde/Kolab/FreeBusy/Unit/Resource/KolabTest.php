<?php
/**
 * Test the Kolab resource handler.
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
 * Test the Kolab resource handler.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_FreeBusy_Unit_Resource_KolabTest
extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->markTestIncomplete('Needs to be adapted to the newer Kolab_Storage API');
    }

    public function testMethodGetnameHasResultStringTheNameOfTheResource()
    {
        $folder = $this->getMock('Horde_Kolab_Storage_Folder_Base');
        $folder->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('name'));
        $resource = new Horde_Kolab_FreeBusy_Resource_Kolab($folder);
        $this->assertEquals('name', $resource->getName());
    }

    public function testMethodGetrelevanceThrowsException()
    {
        $folder = $this->getMock('Horde_Kolab_Storage_Folder');
        $resource = new Horde_Kolab_FreeBusy_Resource_Kolab($folder);
        try {
            $resource->getRelevance();
            $this->fail('The resource did not fail!');
        } catch (Horde_Kolab_FreeBusy_Exception $e) {
            $this->assertEquals(
                'There is no generic definition for relevance available!',
                $e->getMessage()
            );
        }
    }

    public function testMethodGetaclHasResultArrayTheResourcePermissions()
    {
        $perms = new stdClass;
        $perms->acl = array('a' => 'a');
        $folder = $this->getMock('Horde_Kolab_Storage_Folder');
        $folder->expects($this->once())
            ->method('getPermission')
            ->will($this->returnValue($perms));
        $resource = new Horde_Kolab_FreeBusy_Resource_Kolab($folder);
        $this->assertEquals($perms->acl, $resource->getAcl());
    }

    public function testMethodGetattributeaclThrowsException()
    {
        $folder = $this->getMock('Horde_Kolab_Storage_Folder');
        $resource = new Horde_Kolab_FreeBusy_Resource_Kolab($folder);
        try {
            $resource->getAttributeAcl();
            $this->fail('The resource did not fail!');
        } catch (Horde_Kolab_FreeBusy_Exception $e) {
            $this->assertEquals(
                'There is no generic definition for attribute ACL available!',
                $e->getMessage()
            );
        }
    }
}