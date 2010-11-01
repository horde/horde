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
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
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

    public function testMethodGetguidHasResultStringGuidTheObjectIdOnTheServer()
    {
        $composite = $this->getComposite();
        $object = new Object_Mock($composite, 'guid');
        $this->assertEquals('guid', $object->getGuid());
    }

    public function testMethodGetguidThrowsExceptionIfGuidHasNotBeenSetYet()
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

    public function testMethodGetexternalattributesHasResultArrayTheExternalAttributesSupportedByTheObject()
    {
        $composite = $this->getMockedComposite();
        $composite->structure->expects($this->once())
            ->method('getExternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will($this->returnValue(array('external')));
        $object = new Object_Mock($composite, 'guid');
        $this->assertEquals(array('external'), $object->getExternalAttributes());
    }

    public function testMethodGetinternalattributesHasResultArrayTheInternalAttributesSupportedByTheObject()
    {
        $composite = $this->getMockedComposite();
        $composite->structure->expects($this->once())
            ->method('getInternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will($this->returnValue(array('internal')));
        $object = new Object_Mock($composite, 'guid');
        $this->assertEquals(array('internal'), $object->getInternalAttributes());
    }

    public function testMethodExistsHasResultBooleanFalseIfTheGuidIsNotSpecified()
    {
        $composite = $this->getMockedComposite();
        $object = new Object_Mock($composite);
        $this->assertFalse($object->exists());
    }

    public function testMethodExistsHasResultBooleanFalseIfTheServerReturnedAnError()
    {
        $composite = $this->getMockedComposite();
        $composite->structure->expects($this->once())
            ->method('getInternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will($this->returnValue(array('internal')));
        $composite->server->expects($this->once())
            ->method('readAttributes')
            ->with('guid', array('internal'))
            ->will($this->throwException(new Horde_Kolab_Server_Exception('')));
        $object = new Object_Mock($composite, 'guid');
        $this->assertFalse($object->exists());
    }

    public function testMethodExistsHasResultBooleanTrueIfTheServerReturnedData()
    {
        $composite = $this->getMockedComposite();
        $composite->structure->expects($this->once())
            ->method('getInternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will($this->returnValue(array('internal')));
        $composite->server->expects($this->once())
            ->method('readAttributes')
            ->with('guid', array('internal'))
            ->will($this->returnValue(array('a' => 'a')));
        $object = new Object_Mock($composite, 'guid');
        $this->assertTrue($object->exists());
    }

    public function testMethodReadinternalHasResultArrayDataTheInternalObjectData()
    {
        $composite = $this->getMockedComposite();
        $composite->structure->expects($this->once())
            ->method('getInternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will($this->returnValue(array('internal')));
        $composite->server->expects($this->once())
            ->method('readAttributes')
            ->with('guid', array('internal'))
            ->will($this->returnValue(array('internal' => 'test')));
        $object = new Object_Mock($composite, 'guid');
        $this->assertEquals(
            array('internal' => 'test'), $object->readInternal()
        );
    }

    public function testMethodGetinternalHasResultArrayTheDataOfTheRequestedAttribute()
    {
        $composite = $this->getMockedComposite();
        $composite->structure->expects($this->exactly(1))
            ->method('getInternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will($this->returnValue(array('internal')));
        $composite->server->expects($this->once())
            ->method('readAttributes')
            ->with('guid', array('internal'))
            ->will($this->returnValue(array('internal' => array('test'))));
        $object = new Object_Mock($composite, 'guid');
        $this->assertEquals(
            array('internal' => array('test')), $object->getInternal(array('internal'))
        );
    }

    public function testMethodGetinternalThrowsExceptionIfTheRequestedAttributeIsNotSupported()
    {
        $composite = $this->getMockedComposite();
        $composite->structure->expects($this->once())
            ->method('getInternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will($this->returnValue(array('internal')));
        $object = new Object_Mock($composite, 'guid');
        try {
            $object->getInternal(array('test'));
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals('No value for attribute "test"!', $e->getMessage());
        }
    }

    public function testMethodGetinternalThrowsExceptionIfTheRequestedAttributeHasNoValue()
    {
        $composite = $this->getMockedComposite();
        $composite->structure->expects($this->exactly(1))
            ->method('getInternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will(
                $this->returnValue(
                    array('internal', 'test')
                )
            );
        $composite->server->expects($this->once())
            ->method('readAttributes')
            ->with('guid', array('internal', 'test'))
            ->will($this->returnValue(array('internal' => array('test'))));
        $object = new Object_Mock($composite, 'guid');
        try {
            $object->getInternal(array('test'));
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception_Novalue $e) {
            $this->assertEquals('No value for attribute "test"!', $e->getMessage());
        }
    }

    public function testMethodGetexternalHasResultArrayTheDataOfTheRequestedAttribute()
    {
        $composite = $this->getMockedComposite();
        $composite->structure->expects($this->exactly(1))
            ->method('getExternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will($this->returnValue(array('objectClass')));
        $external = $this->getMock('Horde_Kolab_Server_Object_Attribute_Interface');
        $external->expects($this->once())
            ->method('value')
            ->will($this->returnValue(array('test')));
        $composite->structure->expects($this->exactly(1))
            ->method('getExternalAttribute')
            ->with('objectClass')
            ->will(
                $this->returnValue(
                    $external
                )
            );
        $object = new Object_Mock($composite, 'guid');
        $this->assertEquals(
            array('test'), $object->getExternal('objectClass')
        );
    }

    public function testMethodGetexternalThrowsExceptionIfTheRequestedAttributeIsNotSupported()
    {
        $composite = $this->getMockedComposite();
        $composite->structure->expects($this->once())
            ->method('getExternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will($this->returnValue(array('external')));
        $object = new Object_Mock($composite, 'guid');
        try {
            $object->getExternal('test');
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals('Attribute "test" not supported!', $e->getMessage());
        }
    }

    public function testMethodDeleteHasPostconditionThatTheObjectWasDeletedOnTheServer()
    {
        $composite = $this->getMockedComposite();
        $composite->server->expects($this->once())
            ->method('delete')
            ->with('guid');
        $object = new Object_Mock($composite, 'guid');
        $object->delete();
    }

    public function testMethodSaveHasParameterArrayTheDataToSave()
    {
        $composite = $this->getMockedComposite();
        $composite->structure->expects($this->exactly(1))
            ->method('getInternalAttributes')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'))
            ->will(
                $this->returnValue(
                    array(
                        'objectClass'
                    )
                )
            );
        $composite->server->expects($this->exactly(1))
            ->method('readAttributes')
            ->with('guid', array('objectClass'))
            ->will($this->returnValue(array('objectClass' => array('test'))));
        $object = new Object_Mock($composite, 'guid');
        $object->save(array());
    }

    public function testMethodSaveHasPostconditionThatTheObjectWasAddedToTheServerIfItDidNotExistBefore()
    {
        $composite = $this->getMockedComposite();
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
        $external = $this->getMock('Horde_Kolab_Server_Object_Attribute_Interface');
        $external->expects($this->exactly(1))
            ->method('update')
            ->will($this->returnValue(array('objectClass' => array('top'))));
        $composite->structure->expects($this->exactly(1))
            ->method('getExternalAttribute')
            ->with('objectClass')
            ->will(
                $this->returnValue(
                    $external
                )
            );
        $composite->server->expects($this->exactly(1))
            ->method('add')
            ->with($this->isInstanceOf('Horde_Kolab_Server_Object_Interface'), array('objectClass' => array('top')));
        $object = new Object_Mock($composite);
        $object->save(array('objectClass' => 'top'));
    }
}

class Object_Mock extends Horde_Kolab_Server_Object_Base
{
    public function getActions() {}
    static public function getFilter() {}
    public function generateId(array &$info) {}
    public function prepareObjectInformation(array &$info) {}
}