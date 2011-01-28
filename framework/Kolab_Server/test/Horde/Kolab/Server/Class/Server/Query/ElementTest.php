<?php
/**
 * Test the LDAP query elements.
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
 * Test the LDAP query elements.
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
class Horde_Kolab_Server_Class_Server_Query_ElementTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->writer = $this->getMock(
            'Horde_Kolab_Server_Query_Ldap', array(), array(), '', false
        );
    }

    public function testClassAndMethodConvertHasResultMixedTheConvertedElement()
    {
        $this->writer->expects($this->exactly(1))
            ->method('convertAnd')
            ->will($this->returnValue('converted'));
        $and = new Horde_Kolab_Server_Query_Element_And(array());
        $this->assertEquals('converted', $and->convert($this->writer));
    }

    public function testClassApproxMethodConvertHasResultMixedTheConvertedElement()
    {
        $this->writer->expects($this->exactly(1))
            ->method('convertApprox')
            ->will($this->returnValue('converted'));
        $approx = new Horde_Kolab_Server_Query_Element_Approx('', '');
        $this->assertEquals('converted', $approx->convert($this->writer));
    }

    public function testClassBeginsMethodConvertHasResultMixedTheConvertedElement()
    {
        $this->writer->expects($this->exactly(1))
            ->method('convertBegins')
            ->will($this->returnValue('converted'));
        $begins = new Horde_Kolab_Server_Query_Element_Begins('', '');
        $this->assertEquals('converted', $begins->convert($this->writer));
    }

    public function testClassContainsMethodConvertHasResultMixedTheConvertedElement()
    {
        $this->writer->expects($this->exactly(1))
            ->method('convertContains')
            ->will($this->returnValue('converted'));
        $contains = new Horde_Kolab_Server_Query_Element_Contains('', '');
        $this->assertEquals('converted', $contains->convert($this->writer));
    }

    public function testClassEndsMethodConvertHasResultMixedTheConvertedElement()
    {
        $this->writer->expects($this->exactly(1))
            ->method('convertEnds')
            ->will($this->returnValue('converted'));
        $ends = new Horde_Kolab_Server_Query_Element_Ends('', '');
        $this->assertEquals('converted', $ends->convert($this->writer));
    }

    public function testClassEqualsMethodConvertHasResultMixedTheConvertedElement()
    {
        $this->writer->expects($this->exactly(1))
            ->method('convertEquals')
            ->will($this->returnValue('converted'));
        $equals = new Horde_Kolab_Server_Query_Element_Equals('', '');
        $this->assertEquals('converted', $equals->convert($this->writer));
    }

    public function testClassGreaterMethodConvertHasResultMixedTheConvertedElement()
    {
        $this->writer->expects($this->exactly(1))
            ->method('convertGreater')
            ->will($this->returnValue('converted'));
        $greater = new Horde_Kolab_Server_Query_Element_Greater('', '');
        $this->assertEquals('converted', $greater->convert($this->writer));
    }

    public function testClassLessMethodConvertHasResultMixedTheConvertedElement()
    {
        $this->writer->expects($this->exactly(1))
            ->method('convertLess')
            ->will($this->returnValue('converted'));
        $less = new Horde_Kolab_Server_Query_Element_Less('', '');
        $this->assertEquals('converted', $less->convert($this->writer));
    }

    public function testClassNotMethodConstructHasPostconditionThatTheElementWasSavedAsArray()
    {
        $less = new Horde_Kolab_Server_Query_Element_Less('', '');
        $not = new Horde_Kolab_Server_Query_Element_Not($less);
        $this->assertType('array', $not->getElements());
    }

    public function testClassNotMethodConvertHasResultMixedTheConvertedElement()
    {
        $this->writer->expects($this->exactly(1))
            ->method('convertNot')
            ->will($this->returnValue('converted'));
        $less = new Horde_Kolab_Server_Query_Element_Less('', '');
        $not = new Horde_Kolab_Server_Query_Element_Not($less);
        $this->assertEquals('converted', $not->convert($this->writer));
    }

    public function testClassOrMethodConvertHasResultMixedTheConvertedElement()
    {
        $this->writer->expects($this->exactly(1))
            ->method('convertOr')
            ->will($this->returnValue('converted'));
        $or = new Horde_Kolab_Server_Query_Element_Or(array());
        $this->assertEquals('converted', $or->convert($this->writer));
    }

    public function testClassGroupMethodConstructHasParameterArrayElements()
    {
        $or = new Horde_Kolab_Server_Query_Element_Or(array());
    }

    public function testClassGroupMethodConstructHasPostconditionThatTheElementsWereSaved()
    {
        $or = new Horde_Kolab_Server_Query_Element_Or(array());
        $this->assertEquals(array(), $or->getElements());
    }

    /**
     * @expectedException Exception
     */
    public function testClassGroupMethodGetnameThrowsException()
    {
        $or = new Horde_Kolab_Server_Query_Element_Or(array());
        $or->getName();
    }

    /**
     * @expectedException Exception
     */
    public function testClassGroupMethodGetvalueThrowsException()
    {
        $or = new Horde_Kolab_Server_Query_Element_Or(array());
        $or->getValue();
    }

    public function testClassGroupMethodGetelementsHasResultArrayTheGroupElements()
    {
        $or = new Horde_Kolab_Server_Query_Element_Or(array());
        $this->assertEquals(array(), $or->getElements());
    }

    public function testClassSingleMethodConstructHasParameterStringName()
    {
        $equals = new Horde_Kolab_Server_Query_Element_Equals('name', '');
    }

    public function testClassSingleMethodConstructHasParameterStringValue()
    {
        $equals = new Horde_Kolab_Server_Query_Element_Equals('', 'value');
    }

    public function testClassSingleMethodConstructHasPostconditionThatNameAndValueWereSaved()
    {
        $equals = new Horde_Kolab_Server_Query_Element_Equals('name', 'value');
        $this->assertEquals('name', $equals->getName());
        $this->assertEquals('value', $equals->getValue());
    }

    public function testClassSingleMethodGetnameHasResultStringTheName()
    {
        $equals = new Horde_Kolab_Server_Query_Element_Equals('name', '');
        $this->assertEquals('name', $equals->getName());
    }

    public function testClassSingleMethodGetvalueHasResultStringTheValue()
    {
        $equals = new Horde_Kolab_Server_Query_Element_Equals('', 'value');
        $this->assertEquals('value', $equals->getValue());
    }

    /**
     * @expectedException Exception
     */
    public function testClassSingleMethodGetelementsThrowsException()
    {
        $equals = new Horde_Kolab_Server_Query_Element_Equals('', '');
        $equals->getElements();
    }
}
