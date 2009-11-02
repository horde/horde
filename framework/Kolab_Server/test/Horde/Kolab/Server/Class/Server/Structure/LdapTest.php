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
require_once dirname(__FILE__) . '/../../../LdapTestCase.php';

/**
 * Test the LDAP backend.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Class_Server_Structure_LdapTest extends Horde_Kolab_Server_LdapTestCase
{
    public function setUp()
    {
        $server = $this->getMock('Horde_Kolab_Server');
        $this->composite = new Horde_Kolab_Server_Composite(
            $server,
            $this->getMock('Horde_Kolab_Server_Objects'),
            new Horde_Kolab_Server_Structure_Ldap(),
            $this->getMock('Horde_Kolab_Server_Search_Interface'),
            $this->getMock('Horde_Kolab_Server_Schema')
        );
    }

    public function testMethodFindHasResultServerResultTheSearchResult()
    {
        $this->skipIfNoLdap();
        $result = $this->getMock('Horde_Kolab_Server_Result');
        $this->composite->server->expects($this->exactly(1))
            ->method('find')
            ->with('(objectClass=equals)', array())
            ->will($this->returnValue($result));
        $equals = new Horde_Kolab_Server_Query_Element_Equals('Objectclass', 'equals');
        $this->assertType(
            'Horde_Kolab_Server_Result',
            $this->composite->structure->find($equals, array())
        );
    }

    public function testMethodFindBelowHasResultServerResultTheSearchResult()
    {
        $this->skipIfNoLdap();
        $result = $this->getMock('Horde_Kolab_Server_Result');
        $this->composite->server->expects($this->exactly(1))
            ->method('findBelow')
            ->with('(objectClass=equals)', 'base', array())
            ->will($this->returnValue($result));
        $equals = new Horde_Kolab_Server_Query_Element_Equals('Objectclass', 'equals');
        $this->assertType(
            'Horde_Kolab_Server_Result',
            $this->composite->structure->findBelow($equals, 'base', array())
        );
    }

    public function testMethodGetsupportedobjectsHasResultArrayTheObjectTypesSupportedByThisStructure()
    {
        $this->assertEquals(array('Horde_Kolab_Server_Object'), $this->composite->structure->getSupportedObjects());
    }

    public function testMethodDeterminetypeHasParameterStringGuid()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('read')
            ->with('guid')
            ->will($this->returnValue(array('objectClass' => array('TOP'))));
        $this->composite->structure->determineType('guid');
    }

    public function testMethodDeterminetypeHasResultStringTheObjectclassOfTheGivenGuid()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('read')
            ->with('guid')
            ->will($this->returnValue(array('objectClass' => array('TOP'))));
        $this->assertEquals('Horde_Kolab_Server_Object_Top', $this->composite->structure->determineType('guid'));
    }

    public function testMethodDeterminetypeHasResultStringTheObjectclassOfTheGivenGuid2()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('read')
            ->with('guid')
            ->will($this->returnValue(array('objectClass' => array('person'))));
        $this->assertEquals('Horde_Kolab_Server_Object_Person', $this->composite->structure->determineType('guid'));
    }

    public function testMethodDeterminetypeThrowsExceptionIfTheGuidHasNoAttributeObjectclass()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('read')
            ->with('guid')
            ->will($this->returnValue(array()));
        try {
            $this->composite->structure->determineType('guid');
        } catch (Exception $e) {
            $this->assertEquals('The object guid has no objectClass attribute!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::SYSTEM, $e->getCode());
        }
    }

    public function testMethodDeterminetypeThrowsExceptionIfTheTypeIsUnknown()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('read')
            ->with('guid')
            ->will($this->returnValue(array('objectClass' => array('UNKNOWN'))));
        try {
            $this->composite->structure->determineType('guid');
        } catch (Exception $e) {
            $this->assertEquals('Unknown object type for GUID guid.', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::SYSTEM, $e->getCode());
        }
    }

    public function testMethodGenerateserverguidHasParameterStringType()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('getBaseGuid')
            ->will($this->returnValue('base'));
        $this->composite->structure->generateServerGuid('type', '', array());
    }

    public function testMethodGenerateserverguidHasParameterStringId()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('getBaseGuid')
            ->will($this->returnValue('base'));
        $this->composite->structure->generateServerGuid('', 'id', array());
    }

    public function testMethodGenerateserverguidHasParameterArrayObjectData()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('getBaseGuid')
            ->will($this->returnValue('base'));
        $this->composite->structure->generateServerGuid('', '', array('object' => 'data'));
    }

    public function testMethodGenerateserverguidHasResultStringTheGuid()
    {
        $this->composite->server->expects($this->exactly(1))
            ->method('getBaseGuid')
            ->will($this->returnValue('base'));
        $this->assertEquals('id,base', $this->composite->structure->generateServerGuid('', 'id', array()));
    }

    public function testMethodGetinternalattributeThrowsExceptionForUndefinedAttributeName()
    {
        $structure = new Horde_Kolab_Server_Structure_Ldap();
        try {
            $structure->getInternalAttribute('undefined');
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals(
                'Undefined internal attribute "undefined"',
                $e->getMessage()
            );
        }
    }
}
