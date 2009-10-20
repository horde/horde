<?php
/**
 * Test the base attribute.
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
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the base attribute.
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
class Horde_Kolab_Server_Attribute_BaseTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->object = $this->getMock(
            'Horde_Kolab_Server_Object', array(), array(), '', false
        );
        $this->composite = $this->getMock(
            'Horde_Kolab_Server_Composite', array(), array(), '', false
        );
    }

    public function testMethodConstructHasParameterObjectTheObjectOwningTheAttributeAndParameterCompositeWhichIsTheLinkToTheServer()
    {
        $attribute = new Attribute_Mock($this->object, $this->composite, '');
    }

    public function testMethodConstructHasParameterStringTheNameOfTheAttribute()
    {
        $attribute = new Attribute_Mock($this->object, $this->composite, 'name');
    }

    public function testMethodGetobjectReturnsObjectAssociatedWithThisAttribute()
    {
        $attribute = new Attribute_Mock($this->object, $this->composite, '');
        $this->assertType('Horde_Kolab_Server_Object', $attribute->getObject());
    }

    public function testMethodGetnameReturnsStringTheNameOfTheAttribute()
    {
        $attribute = new Attribute_Mock($this->object, $this->composite, 'name');
        $this->assertEquals('name', $attribute->getInternalName());
    }

    public function testMethodIsemptyHasParameterArrayDataValues()
    {
        $attribute = new Attribute_Mock($this->object, $this->composite, 'name');
        $attribute->isEmpty(array());
    }

    public function testMethodIsemptyReturnsFalseIfTheValueIndicatedByTheAttributeNameIsNotEmptyInTheDataArray()
    {
        $attribute = new Attribute_Mock($this->object, $this->composite, 'name', 'name');
        $this->assertFalse($attribute->isEmpty(array('name' => 'HELLO')));
    }

    public function testMethodIsemptyReturnsTrueIfTheValueIndicatedByTheAttributeNameIsMissingInTheDataArray()
    {
        $attribute = new Attribute_Mock($this->object, $this->composite, 'name');
        $this->assertTrue($attribute->isEmpty(array()));
    }

    public function testMethodIsemptyReturnsTrueIfTheValueIndicatedByTheAttributeNameIsStringEmptyInTheDataArray()
    {
        $attribute = new Attribute_Mock($this->object, $this->composite, 'name');
        $this->assertTrue($attribute->isEmpty(array('name' => '')));
    }

    public function testMethodIsemptyReturnsTrueIfTheValueIndicatedByTheAttributeNameIsNullInTheDataArray()
    {
        $attribute = new Attribute_Mock($this->object, $this->composite, 'name');
        $this->assertTrue($attribute->isEmpty(array('name' => null)));
    }

    public function testMethodIsemptyReturnsTrueIfTheValueIndicatedByTheAttributeNameIsEmptyArrayInTheDataArray()
    {
        $attribute = new Attribute_Mock($this->object, $this->composite, 'name');
        $this->assertTrue($attribute->isEmpty(array('name' => array())));
    }
}

class Attribute_Mock extends Horde_Kolab_Server_Object_Attribute_Base
{
    public function value() {}
    public function update(array $changes) {}
    public function consume(array &$changes) {}
}