<?php
/**
 * Test the mapped query element.
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
require_once dirname(__FILE__) . '/../../../../Autoload.php';

/**
 * Test the mapped query element.
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
class Horde_Kolab_Server_Class_Server_Query_Element_MappedTest
extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->element = $this->getMock(
            'Horde_Kolab_Server_Query_Element_Interface'
        );
        $this->mapper  = $this->getMock(
            'Horde_Kolab_Server_Decorator_Map', array(), array(), '', false, false
        );
    }

    public function testMethodConstructHasParameterElementTheDecoratedElement()
    {
        $element = new Horde_Kolab_Server_Query_Element_Mapped(
            $this->element,
            $this->mapper
        );
    }

    public function testMethodConstructHasParameterMapper()
    {
        $this->testMethodConstructHasParameterElementTheDecoratedElement();
    }

    public function testMethodGetnameHasResultStringTheMappedNameOfTheElement()
    {
        $this->element->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('test'));
        $this->mapper->expects($this->once())
            ->method('mapField')
            ->with('test')
            ->will($this->returnValue('mapped'));
        $element = new Horde_Kolab_Server_Query_Element_Mapped(
            $this->element,
            $this->mapper
        );
        $this->assertEquals('mapped', $element->getName());
    }

    public function testMethodGetvalueHasResultTheValueOfTheMappedElement()
    {
        $this->element->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue('test'));
        $element = new Horde_Kolab_Server_Query_Element_Mapped(
            $this->element,
            $this->mapper
        );
        $this->assertEquals('test', $element->getValue());
    }

    public function testMethodGetelementsHasResultArrayOfMappedElements()
    {
        $elements = array(
            $this->getMock(
                'Horde_Kolab_Server_Query_Element_Interface'
            ),
            $this->getMock(
                'Horde_Kolab_Server_Query_Element_Interface'
            ),
        );
        $this->element->expects($this->once())
            ->method('getElements')
            ->will($this->returnValue($elements));
        $element = new Horde_Kolab_Server_Query_Element_Mapped(
            $this->element,
            $this->mapper
        );
        $elements = $element->getElements();
        foreach ($elements as $element) {
            $this->assertType(
                'Horde_Kolab_Server_Query_Element_Mapped', $element
            );
        }
    }

    public function testMethodConvertHasResultStringTheConvertedElement()
    {
        $this->element->expects($this->once())
            ->method('convert')
            ->will($this->returnValue('test'));
        $element = new Horde_Kolab_Server_Query_Element_Mapped(
            $this->element,
            $this->mapper
        );
        $query = $this->getMock('Horde_Kolab_Server_Query_Interface');
        $this->assertEquals('test', $element->convert($query));
    }

    public function testMethodGetnameHasPostconditionThatTheCallWasDelegated()
    {
        $this->element->expects($this->once())
            ->method('getName');
        $element = new Horde_Kolab_Server_Query_Element_Mapped(
            $this->element,
            $this->mapper
        );
        $element->getName();
    }

    public function testMethodGetvalueHasPostconditionThatTheCallWasDelegated()
    {
        $this->element->expects($this->once())
            ->method('getValue');
        $element = new Horde_Kolab_Server_Query_Element_Mapped(
            $this->element,
            $this->mapper
        );
        $element->getValue();
    }

    public function testMethodGetelementsHasPostconditionThatTheCallWasDelegated()
    {
        $elements = array();
        $this->element->expects($this->once())
            ->method('getElements')
            ->will($this->returnValue($elements));
        $element = new Horde_Kolab_Server_Query_Element_Mapped(
            $this->element,
            $this->mapper
        );
        $element->getElements();
    }

    public function testMethodConvertHasPostconditionThatTheCallWasDelegated()
    {
        $this->element->expects($this->once())
            ->method('convert');
        $element = new Horde_Kolab_Server_Query_Element_Mapped(
            $this->element,
            $this->mapper
        );
        $query = $this->getMock('Horde_Kolab_Server_Query_Interface');
        $element->convert($query);
    }
}
