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
require_once dirname(__FILE__) . '/../../../../TestCase.php';

/**
 * Test the base attribute.
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
class Horde_Kolab_Server_Class_Server_Object_Attribute_BaseTest
extends Horde_Kolab_Server_TestCase
{
    public function setUp()
    {
        $this->attribute = $this->getMock(
            'Horde_Kolab_Server_Structure_Attribute_Interface'
        );
    }

    public function testMethodConstructHasParameterAttributeTheAdapterCoveringTheInternalSideOfTheAttribute()
    {
        $attribute = new Attribute_Mock($this->attribute, '');
    }

    public function testMethodConstructHasParameterStringTheNameOfTheAttribute()
    {
        $attribute = new Attribute_Mock($this->attribute, 'name');
    }

    public function testMethodGetattributeReturnsAttributeInteralAssociatedWithThisAttribute()
    {
        $attribute = new Attribute_Mock($this->attribute, '');
        $this->assertType(
            'Horde_Kolab_Server_Structure_Attribute_Interface',
            $attribute->getAttribute()
        );
    }

    public function testMethodGetnameReturnsStringTheNameOfTheAttribute()
    {
        $attribute = new Attribute_Mock($this->attribute, 'name');
        $this->assertEquals('name', $attribute->getName());
    }

    public function testMethodIsemptyHasParameterArrayDataValues()
    {
        $attribute = new Attribute_Mock($this->attribute, 'name');
        $attribute->isEmpty(array());
    }

    public function testMethodIsemptyReturnsFalseIfTheValueIndicatedByTheAttributeNameIsNotEmptyInTheDataArray()
    {
        $attribute = new Attribute_Mock($this->attribute, 'name', 'name');
        $this->assertFalse($attribute->isEmpty(array('name' => 'HELLO')));
    }

    public function testMethodIsemptyReturnsTrueIfTheValueIndicatedByTheAttributeNameIsMissingInTheDataArray()
    {
        $attribute = new Attribute_Mock($this->attribute, 'name');
        $this->assertTrue($attribute->isEmpty(array()));
    }

    public function testMethodIsemptyReturnsTrueIfTheValueIndicatedByTheAttributeNameIsStringEmptyInTheDataArray()
    {
        $attribute = new Attribute_Mock($this->attribute, 'name');
        $this->assertTrue($attribute->isEmpty(array('name' => '')));
    }

    public function testMethodIsemptyReturnsTrueIfTheValueIndicatedByTheAttributeNameIsNullInTheDataArray()
    {
        $attribute = new Attribute_Mock($this->attribute, 'name');
        $this->assertTrue($attribute->isEmpty(array('name' => null)));
    }

    public function testMethodIsemptyReturnsTrueIfTheValueIndicatedByTheAttributeNameIsEmptyArrayInTheDataArray()
    {
        $attribute = new Attribute_Mock($this->attribute, 'name');
        $this->assertTrue($attribute->isEmpty(array('name' => array())));
    }
}

class Attribute_Mock extends Horde_Kolab_Server_Object_Attribute_Base
{
    public function value() {}
    public function update(array $changes) {}
}