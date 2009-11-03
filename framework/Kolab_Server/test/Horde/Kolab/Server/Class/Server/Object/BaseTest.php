<?php
/**
 * Test the base object.
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
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../TestCase.php';

/**
 * Test the base object.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Class_Server_Object_BaseTest extends Horde_Kolab_Server_TestCase
{
    public function setUp()
    {
    }

    public function testMethodConstructHasParameterCompositeWhichIsTheLinkToTheServer()
    {
        $composite = $this->getComposite();
        $object = new Object_Mock($composite, '');
    }

    public function testMethodConstructHasParameterStringTheGuidOfTheObject()
    {
        $composite = $this->getComposite();
        $object = new Object_Mock($composite, 'guid');
    }

    public function testGetguidHasResultStringGuidTheObjectIdOnTheServer()
    {
        $composite = $this->getComposite();
        $object = new Object_Mock($composite, 'guid');
        $this->assertEquals('guid', $object->getGuid());
    }

    public function testGetguidThrowsExceptionIfGuidHasNotBeenSetYet()
    {
        $composite = $this->getComposite();
        $object = new Object_Mock($composite);
        try {
            $this->assertEquals('newGuid', $object->getGuid());
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals(
                'Uninitialized object is missing GUID!', $e->getMessage()
            );
        }
    }

    public function testGetexternalattributesHasResultArrayTheExternalAttributesSupportedByTheObject()
    {
        $composite = $this->getMockedComposite();
        $composite->schema->expects($this->once())
            ->method('getExternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will($this->returnValue(array('external')));
        $object = new Object_Mock($composite, 'guid');
        $this->assertEquals(array('external'), $object->getExternalAttributes());
    }

    public function testGetinternalattributesHasResultArrayTheInternalAttributesSupportedByTheObject()
    {
        $composite = $this->getMockedComposite();
        $composite->schema->expects($this->once())
            ->method('getInternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will($this->returnValue(array('internal' => 'Internal')));
        $object = new Object_Mock($composite, 'guid');
        $this->assertEquals(array('internal' => 'Internal'), $object->getInternalAttributes());
    }

    public function testGetinternalattributesHasResultBooleanFalseIfTheGuidIsNotSpecified()
    {
        $composite = $this->getMockedComposite();
        $object = new Object_Mock($composite);
        $this->assertFalse($object->exists());
    }

    public function testGetinternalattributesHasResultBooleanFalseIfTheServerReturnedAnError()
    {
        $composite = $this->getMockedComposite();
        $composite->schema->expects($this->once())
            ->method('getInternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will($this->returnValue(array('internal' => 'Internal')));
        $composite->server->expects($this->once())
            ->method('readAttributes')
            ->with('guid', array('internal'))
            ->will($this->throwException(new Horde_Kolab_Server_Exception('')));
        $object = new Object_Mock($composite, 'guid');
        $this->assertFalse($object->exists());
    }

    public function testGetinternalattributesHasResultBooleanTrueIfTheServerReturnedData()
    {
        $composite = $this->getMockedComposite();
        $composite->schema->expects($this->once())
            ->method('getInternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will($this->returnValue(array('internal' => 'Internal')));
        $composite->server->expects($this->once())
            ->method('readAttributes')
            ->with('guid', array('internal'))
            ->will($this->returnValue(array('a' => 'a')));
        $object = new Object_Mock($composite, 'guid');
        $this->assertTrue($object->exists());
    }

    public function testReadinternalHasResultArrayDataTheInternalObjectData()
    {
        $composite = $this->getMockedComposite();
        $composite->schema->expects($this->once())
            ->method('getInternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will($this->returnValue(array('internal' => 'Internal')));
        $composite->server->expects($this->once())
            ->method('readAttributes')
            ->with('guid', array('internal'))
            ->will($this->returnValue(array('internal' => 'test')));
        $object = new Object_Mock($composite, 'guid');
        $this->assertEquals(
            array('internal' => 'test'), $object->readInternal()
        );
    }

    public function testGetinternalHasResultArrayTheDataOfTheRequestedAttribute()
    {
        $composite = $this->getMockedComposite();
        $composite->schema->expects($this->exactly(2))
            ->method('getInternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will($this->returnValue(array('internal' => 'Internal')));
        $composite->server->expects($this->once())
            ->method('readAttributes')
            ->with('guid', array('internal'))
            ->will($this->returnValue(array('internal' => array('test'))));
        $object = new Object_Mock($composite, 'guid');
        $this->assertEquals(
            array('test'), $object->getInternal('internal')
        );
    }

    public function testGetinternalThrowsExceptionIfTheRequestedAttributeIsNotSupported()
    {
        $composite = $this->getMockedComposite();
        $composite->schema->expects($this->once())
            ->method('getInternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will($this->returnValue(array('internal' => 'Internal')));
        $object = new Object_Mock($composite, 'guid');
        try {
            $object->getInternal('test');
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals('Attribute "test" not supported!', $e->getMessage());
        }
    }

    public function testGetinternalThrowsExceptionIfTheRequestedAttributeHasNoValue()
    {
        $composite = $this->getMockedComposite();
        $composite->schema->expects($this->exactly(2))
            ->method('getInternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will(
                $this->returnValue(
                    array('internal' => 'Internal', 'test' => 'Test')
                )
            );
        $composite->server->expects($this->once())
            ->method('readAttributes')
            ->with('guid', array('internal', 'test'))
            ->will($this->returnValue(array('internal' => array('test'))));
        $object = new Object_Mock($composite, 'guid');
        try {
            $object->getInternal('test');
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception_Novalue $e) {
            $this->assertEquals('No value for attribute "test"!', $e->getMessage());
        }
    }

    public function testGetexternalHasResultArrayTheDataOfTheRequestedAttribute()
    {
        $composite = $this->getMockedComposite();
        $composite->structure->expects($this->exactly(1))
            ->method('mapExternalToInternalAttribute')
            ->with('Objectclass')
            ->will($this->returnValue('objectClass'));
        $composite->schema->expects($this->exactly(1))
            ->method('getExternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will($this->returnValue(array('Objectclass')));
        $composite->schema->expects($this->exactly(2))
            ->method('getInternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will($this->returnValue(array('objectClass' => 'Objectclass')));
        $composite->server->expects($this->once())
            ->method('readAttributes')
            ->with('guid', array('objectClass'))
            ->will($this->returnValue(array('objectClass' => array('test'))));
        $object = new Object_Mock($composite, 'guid');
        $this->assertEquals(
            array('test'), $object->getExternal('Objectclass')
        );
    }

    public function testGetexternalThrowsExceptionIfTheRequestedAttributeIsNotSupported()
    {
        $composite = $this->getMockedComposite();
        $composite->schema->expects($this->once())
            ->method('getExternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will($this->returnValue(array('external')));
        $object = new Object_Mock($composite, 'guid');
        try {
            $object->getExternal('test');
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals('Attribute "Test" not supported!', $e->getMessage());
        }
    }

    public function testGetexternalThrowsExceptionIfTheRequestedClassDoesNotExist()
    {
        $composite = $this->getMockedComposite();
        $composite->schema->expects($this->once())
            ->method('getExternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will($this->returnValue(array('Test')));
        $object = new Object_Mock($composite, 'guid');
        try {
            $object->getExternal('test');
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals('Attribute "Test" not supported!', $e->getMessage());
        }
    }

    public function testDeleteHasPostconditionThatTheObjectWasDeletedOnTheServer()
    {
        $composite = $this->getMockedComposite();
        $composite->server->expects($this->once())
            ->method('delete')
            ->with('guid');
        $object = new Object_Mock($composite, 'guid');
        $object->delete();
    }

    public function testSaveHasParameterArrayTheDataToSave()
    {
        $composite = $this->getMockedComposite();
        $composite->schema->expects($this->exactly(3))
            ->method('getInternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will(
                $this->returnValue(
                    array(
                        'objectClass' =>
                        'Horde_Kolab_Server_Object_Attribute_Objectclass'
                    )
                )
            );
        $composite->server->expects($this->exactly(2))
            ->method('readAttributes')
            ->with('guid', array('objectClass'))
            ->will($this->returnValue(array('objectClass' => array('test'))));
        $object = new Object_Mock($composite, 'guid');
        $object->save(array());
    }

    public function testSaveHasPostconditionThatTheObjectWasAddedToTheServerIfItDidNotExistBefore()
    {
        $composite = $this->getMockedComposite();
        $composite->structure->expects($this->exactly(1))
            ->method('mapExternalToInternalAttribute')
            ->with('Objectclass')
            ->will($this->returnValue('objectClass'));
        $composite->schema->expects($this->exactly(1))
            ->method('getInternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will(
                $this->returnValue(
                    array(
                        'objectClass' =>
                        'Horde_Kolab_Server_Object_Attribute_Objectclass'
                    )
                )
            );
        $composite->structure->expects($this->exactly(1))
            ->method('generateServerGuid')
            ->with(
                'Object_Mock', null,
                array('objectClass' => array('top')))
            ->will(
                $this->returnValue(
                    array(
                        'objectClass' =>
                        'Horde_Kolab_Server_Object_Attribute_Objectclass'
                    )
                )
            );
        $composite->server->expects($this->exactly(1))
            ->method('add')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'), array('objectClass' => array('top')));
        $object = new Object_Mock($composite);
        $object->save(array('Objectclass' => 'top'));
    }
}

class Object_Mock extends Horde_Kolab_Server_Object_Base
{
    public function getActions() {}
    static public function getFilter() {}
    public function generateId(array &$info) {}
    public function prepareObjectInformation(array &$info) {}
}