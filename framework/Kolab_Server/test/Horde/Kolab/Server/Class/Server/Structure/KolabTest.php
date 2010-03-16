<?php
/**
 * Test the LDAP driver.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Require our basic test case definition
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the LDAP backend.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Class_Server_Structure_KolabTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $server = $this->getMock('Horde_Kolab_Server_Interface');
        $this->composite = new Horde_Kolab_Server_Composite(
            $server,
            $this->getMock('Horde_Kolab_Server_Objects_Interface'),
            new Horde_Kolab_Server_Structure_Kolab(),
            $this->getMock('Horde_Kolab_Server_Search_Interface'),
            $this->getMock('Horde_Kolab_Server_Schema_Interface')
        );
    }

    public function testMethodGetsupportedobjectsHasResultArrayTheObjectTypesSupportedByThisStructure()
    {
        $this->assertType('array', $this->composite->structure->getSupportedObjects());
    }

    public function testMethodDeterminetypeHasResultStringTheObjectclassOfTheGivenGuid1()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('read')
            ->with('guid')
            ->will($this->returnValue(array('objectClass' => array('kolabGroupOfNames'))));
        $this->assertEquals('Horde_Kolab_Server_Object_Kolabgroupofnames', $this->composite->structure->determineType('guid'));
    }

    public function testMethodDeterminetypeHasResultStringTheObjectclassOfTheGivenGuid2()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('read')
            ->with('guid')
            ->will($this->returnValue(array('objectClass' => array('kolabExternalPop3Account'))));
        $this->assertEquals('Horde_Kolab_Server_Object_Kolabpop3account', $this->composite->structure->determineType('guid'));
    }

    public function testMethodDeterminetypeHasResultStringTheObjectclassOfTheGivenGuid3()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('read')
            ->with('guid')
            ->will($this->returnValue(array('objectClass' => array('kolabSharedFolder'))));
        $this->assertEquals('Horde_Kolab_Server_Object_Kolabsharedfolder', $this->composite->structure->determineType('guid'));
    }

    public function testMethodDeterminetypeHasResultStringTheObjectclassOfTheGivenGuid4()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('read')
            ->with('guid')
            ->will($this->returnValue(array('objectClass' => array('top'))));
        $this->assertEquals('Horde_Kolab_Server_Object_Top', $this->composite->structure->determineType('guid'));
    }

    public function testMethodDeterminetypeHasResultStringTheObjectclassOfTheGivenGuid5()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('read')
            ->with('guid')
            ->will(
                $this->returnValue(
                    array(
                        'objectClass' =>
                        array(
                            'kolabinetorgperson',
                        )
                    )
                )
            );
        $this->composite->search->expects($this->exactly(1))
            ->method('__call')
            ->with('searchGroupsForMember', array('guid'))
            ->will(
                $this->returnValue(
                    array(
                    )
                )
            );
        $this->assertEquals(
            'Horde_Kolab_Server_Object_Kolab_User',
            $this->composite->structure->determineType('guid')
        );
    }

    public function testMethodDeterminetypeHasResultStringTheObjectclassOfTheGivenGuid6()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('read')
            ->with('guid')
            ->will(
                $this->returnValue(
                    array(
                        'objectClass' =>
                        array(
                            'kolabinetorgperson',
                        )
                    )
                )
            );
        $this->composite->server->expects($this->exactly(1))
            ->method('getBaseGuid')
            ->will($this->returnValue('base'));
        $this->composite->search->expects($this->exactly(1))
            ->method('__call')
            ->with('searchGroupsForMember', array('guid'))
            ->will(
                $this->returnValue(
                    array(
                        'cn=admin,cn=internal,base'
                    )
                )
            );
        $this->assertEquals(
            'Horde_Kolab_Server_Object_Kolab_Administrator',
            $this->composite->structure->determineType('guid')
        );
    }

    public function testMethodDeterminetypeHasResultStringTheObjectclassOfTheGivenGuid7()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('read')
            ->with('guid')
            ->will(
                $this->returnValue(
                    array(
                        'objectClass' =>
                        array(
                            'kolabinetorgperson',
                        )
                    )
                )
            );
        $this->composite->server->expects($this->exactly(1))
            ->method('getBaseGuid')
            ->will($this->returnValue('base'));
        $this->composite->search->expects($this->exactly(1))
            ->method('__call')
            ->with('searchGroupsForMember', array('guid'))
            ->will(
                $this->returnValue(
                    array(
                        'cn=maintainer,cn=internal,base'
                    )
                )
            );
        $this->assertEquals(
            'Horde_Kolab_Server_Object_Kolab_Maintainer',
            $this->composite->structure->determineType('guid')
        );
    }

    public function testMethodDeterminetypeHasResultStringTheObjectclassOfTheGivenGuid8()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('read')
            ->with('guid')
            ->will(
                $this->returnValue(
                    array(
                        'objectClass' =>
                        array(
                            'kolabinetorgperson',
                        )
                    )
                )
            );
        $this->composite->server->expects($this->exactly(1))
            ->method('getBaseGuid')
            ->will($this->returnValue('base'));
        $this->composite->search->expects($this->exactly(1))
            ->method('__call')
            ->with('searchGroupsForMember', array('guid'))
            ->will(
                $this->returnValue(
                    array(
                        'cn=domain-maintainer,cn=internal,base'
                    )
                )
            );
        $this->assertEquals(
            'Horde_Kolab_Server_Object_Kolab_Domainmaintainer',
            $this->composite->structure->determineType('guid')
        );
    }

    public function testMethodDeterminetypeHasResultStringTheObjectclassOfTheGivenGuid9()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('read')
            ->with('guid,cn=external')
            ->will(
                $this->returnValue(
                    array(
                        'objectClass' =>
                        array(
                            'kolabinetorgperson',
                        )
                    )
                )
            );
        $this->composite->search->expects($this->exactly(1))
            ->method('__call')
            ->with('searchGroupsForMember', array('guid,cn=external'))
            ->will(
                $this->returnValue(
                    array(
                        'unknown'
                    )
                )
            );
        $this->assertEquals(
            'Horde_Kolab_Server_Object_Kolab_Address',
            $this->composite->structure->determineType('guid,cn=external')
        );
    }

    public function testMethodGenerateserverguidHasResultStringTheGuid1()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('getBaseGuid')
            ->will($this->returnValue('base'));
        $this->assertEquals('id,base', $this->composite->structure->generateServerGuid('Horde_Kolab_Server_Object_Kolabgroupofnames', 'id', array()));
    }

    public function testMethodGenerateserverguidHasResultStringTheGuid2()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('getBaseGuid')
            ->will($this->returnValue('base'));
        $this->assertEquals('id,cn=internal,base', $this->composite->structure->generateServerGuid('Horde_Kolab_Server_Object_Kolabgroupofnames', 'id', array('visible' => false)));
    }

    public function testMethodGenerateserverguidHasResultStringTheGuid3()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('getBaseGuid')
            ->will($this->returnValue('base'));
        $this->assertEquals('id,base', $this->composite->structure->generateServerGuid('Horde_Kolab_Server_Object_Kolabsharedfolder', 'id', array('visible' => false)));
    }

    public function testMethodGenerateserverguidHasResultStringTheGuid4()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('getBaseGuid')
            ->will($this->returnValue('base'));
        $this->assertEquals('id,cn=external,base', $this->composite->structure->generateServerGuid('Horde_Kolab_Server_Object_Kolab_Address', 'id', array()));
    }

    public function testMethodGenerateserverguidHasResultStringTheGuid5()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('getBaseGuid')
            ->will($this->returnValue('base'));
        $this->assertEquals(
            'id,cn=internal,base',
            $this->composite->structure->generateServerGuid(
                'Horde_Kolab_Server_Object_Kolab_User', 'id',
                array('user_type' => Horde_Kolab_Server_Object_Kolab_User::USERTYPE_INTERNAL)
            )
        );
    }

    public function testMethodGenerateserverguidHasResultStringTheGuid6()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('getBaseGuid')
            ->will($this->returnValue('base'));
        $this->assertEquals(
            'id,cn=groups,base',
            $this->composite->structure->generateServerGuid(
                'Horde_Kolab_Server_Object_Kolab_User', 'id',
                array('user_type' => Horde_Kolab_Server_Object_Kolab_User::USERTYPE_GROUP)
            )
        );
    }

    public function testMethodGenerateserverguidHasResultStringTheGuid7()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('getBaseGuid')
            ->will($this->returnValue('base'));
        $this->assertEquals(
            'id,cn=resources,base',
            $this->composite->structure->generateServerGuid(
                'Horde_Kolab_Server_Object_Kolab_User', 'id',
                array('user_type' => Horde_Kolab_Server_Object_Kolab_User::USERTYPE_RESOURCE)
            )
        );
    }

    public function testMethodGenerateserverguidHasResultStringTheGuid8()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('getBaseGuid')
            ->will($this->returnValue('base'));
        $this->assertEquals('id,base', $this->composite->structure->generateServerGuid('Horde_Kolab_Server_Object_Kolab_User', 'id', array()));
    }

    public function testMethodGenerateserverguidHasResultStringTheGuid9()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('getBaseGuid')
            ->will($this->returnValue('base'));
        $this->assertEquals(
            'id,base',
            $this->composite->structure->generateServerGuid(
                'Horde_Kolab_Server_Object_Kolab_User', 'id',
                array('user_type' => 'undefined')
            )
        );
    }

}
