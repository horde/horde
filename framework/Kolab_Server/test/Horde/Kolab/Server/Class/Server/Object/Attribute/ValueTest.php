<?php
/**
 * Test the value attribute.
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
require_once dirname(__FILE__) . '/../../../../Autoload.php';

/**
 * Test the value attribute.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Class_Server_Object_Attribute_ValueTest
extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->markTestIncomplete('Needs to be fixed');

        $this->object = $this->getMock(
            'Horde_Kolab_Server_Object_Interface', array(), array(), '', false
        );
        $this->composite = $this->getMock(
            'Horde_Kolab_Server_Composite', array(), array(), '', false
        );
    }

    public function testMethodValueHasResultArrayTheValuesOfTheAttribute()
    {
        $attribute = new Horde_Kolab_Server_Object_Attribute_Value(
            $this->object, $this->composite, 'name'
        );
        $this->object->expects($this->once())
            ->method('getInternal')
            ->with('name')
            ->will($this->returnValue(array(1, 2)));
        $this->assertEquals(array(1, 2), $attribute->value());
    }

    public function testMethodConsumeHasParameterArrayData()
    {
        $attribute = new Horde_Kolab_Server_Object_Attribute_Value(
            $this->object, $this->composite, 'name'
        );
        $data = array();
        $attribute->consume($data);
    }

    public function testMethodConsumeHasPostconditionThatTheAttributeValueHasBeenRemovedFromTheDataArray()
    {
        $attribute = new Horde_Kolab_Server_Object_Attribute_Value(
            $this->object, $this->composite, 'name', 'name'
        );
        $data = array('name' => 'test');
        $attribute->consume($data);
        $this->assertEquals(array(), $data);
    }

    public function testMethodChangesHasParameterArrayData()
    {
        $attribute = new Horde_Kolab_Server_Object_Attribute_Value(
            $this->object, $this->composite, 'name'
        );
        $this->object->expects($this->once())
            ->method('exists')
            ->with()
            ->will($this->returnValue(false));
        $data = array();
        $attribute->update($data);
    }

    public function testMethodChangesHasResultArrayEmptyIfTheObjectDoesNotExistAndThereAreNoChangesToTheAttribute()
    {
        $attribute = new Horde_Kolab_Server_Object_Attribute_Value(
            $this->object, $this->composite, 'name'
        );
        $this->object->expects($this->once())
            ->method('exists')
            ->with()
            ->will($this->returnValue(false));
        $data = array();
        $this->assertEquals(array(), $attribute->update($data));
    }

    public function testMethodChangesHasResultArrayWithAddedValuesIfTheObjectDoesNotExistAndThereAreChangesToTheAttribute()
    {
        $attribute = new Horde_Kolab_Server_Object_Attribute_Value(
            $this->object, $this->composite, 'name', 'name'
        );
        $this->object->expects($this->once())
            ->method('exists')
            ->with()
            ->will($this->returnValue(false));
        $data = array('name' => 'a');
        $this->assertEquals(
            array(array('name' => array('a'))),
            $attribute->update($data)
        );
    }

    public function testMethodChangesHasResultArrayWithAddedValuesIfTheObjectExistsButHadNoValueForTheAttributeAndThereAreNoChangesToTheAttribute()
    {
        $attribute = new Horde_Kolab_Server_Object_Attribute_Value(
            $this->object, $this->composite, 'name'
        );
        $this->object->expects($this->once())
            ->method('exists')
            ->with()
            ->will($this->returnValue(true));
        $this->object->expects($this->once())
            ->method('getInternal')
            ->with('name')
            ->will(
                $this->throwException(
                    new Horde_Kolab_Server_Exception_Novalue('')
                )
            );
        $data = array();
        $this->assertEquals(array(), $attribute->update($data));
    }

    public function testMethodChangesHasResultArrayWithAddedValuesIfTheObjectExistsButHadNoValueForTheAttributeAndThereAreChangesToTheAttribute()
    {
        $attribute = new Horde_Kolab_Server_Object_Attribute_Value(
            $this->object, $this->composite, 'name'
        );
        $this->object->expects($this->once())
            ->method('exists')
            ->with()
            ->will($this->returnValue(true));
        $this->object->expects($this->once())
            ->method('getInternal')
            ->with('name')
            ->will(
                $this->throwException(
                    new Horde_Kolab_Server_Exception_Novalue('')
                )
            );
        $data = array('name' => 'a');
        $this->assertEquals(
            array('add' => array('name' => array('a'))),
            $attribute->update($data)
        );
    }

    public function testMethodChangesHasResultArrayWithDeletedValuesIfTheObjectExistsAndHadAValueForTheAttributeAndTheNewValueIsEmpty()
    {
        $attribute = new Horde_Kolab_Server_Object_Attribute_Value(
            $this->object, $this->composite, 'name'
        );
        $this->object->expects($this->once())
            ->method('exists')
            ->with()
            ->will($this->returnValue(true));
        $this->object->expects($this->once())
            ->method('getInternal')
            ->with('name')
            ->will($this->returnValue(array('a')));
        $data = array('name' => null);
        $this->assertEquals(
            array('delete' => array('name' => array('a'))),
            $attribute->update($data)
        );
    }

    public function testMethodChangesHasResultArrayWithReplacedValuesIfTheObjectExistsAndHadASingleValueForTheAttributeAndTheNewValueHasASingleNewValue()
    {
        $attribute = new Horde_Kolab_Server_Object_Attribute_Value(
            $this->object, $this->composite, 'name'
        );
        $this->object->expects($this->once())
            ->method('exists')
            ->with()
            ->will($this->returnValue(true));
        $this->object->expects($this->once())
            ->method('getInternal')
            ->with('name')
            ->will($this->returnValue(array('a')));
        $data = array('name' => array('b'));
        $this->assertEquals(
            array('replace' => array('name' => array('b'))),
            $attribute->update($data)
        );
    }

    public function testMethodChangesHasResultArrayEmptyIfTheObjectExistsAndHadASingleValueForTheAttributeAndTheNewValueHasASingleNewValueAndBothAreEqual()
    {
        $attribute = new Horde_Kolab_Server_Object_Attribute_Value(
            $this->object, $this->composite, 'name'
        );
        $this->object->expects($this->once())
            ->method('exists')
            ->with()
            ->will($this->returnValue(true));
        $this->object->expects($this->once())
            ->method('getInternal')
            ->with('name')
            ->will($this->returnValue(array('a')));
        $data = array('name' => array('a'));
        $this->assertEquals(array(), $attribute->update($data));
    }

    public function testMethodChangesHasResultArrayWithAddedAndDeletedValuesIfTheObjectExistsAndHadValuesForTheAttributeAndNewValuesHaveBeenProvided()
    {
        $attribute = new Horde_Kolab_Server_Object_Attribute_Value(
            $this->object, $this->composite, 'name'
        );
        $this->object->expects($this->once())
            ->method('exists')
            ->with()
            ->will($this->returnValue(true));
        $this->object->expects($this->once())
            ->method('getInternal')
            ->with('name')
            ->will($this->returnValue(array('a', 'c')));
        $data = array('name' => array('b', 'c', 'd'));
        $this->assertEquals(
            array(
                'add' => array('name' => array('b', 'd')),
                'delete' => array('name' => array('a'))
            ),
            $attribute->update($data)
        );
    }
}