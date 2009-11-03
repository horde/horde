<?php
/**
 * Test the LDAP query handler.
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
 * Test the LDAP query handler.
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
class Horde_Kolab_Server_Class_Server_Query_LdapTest extends Horde_Kolab_Server_LdapTestCase
{
    public function setUp()
    {
        $this->skipIfNoLdap();
        $this->structure = $this->getMock(
            'Horde_Kolab_Server_Structure_Interface'
        );
    }

    public function testMethodConstructHasParameterQueryelementTheQueryCriteria()
    {
        $equals = new Horde_Kolab_Server_Query_Element_Equals('equals', 'equals');
        $query = new Horde_Kolab_Server_Query_Ldap($equals, $this->structure);
    }

    public function testMethodConstructHasPostconditionThatTheQueryCriteriaWereSaved()
    {
        $this->structure->expects($this->once())
            ->method('mapExternalToInternalAttribute')
            ->will($this->returnValue('equals'));
        $equals = new Horde_Kolab_Server_Query_Element_Equals('equals', 'equals');
        $query = new Horde_Kolab_Server_Query_Ldap($equals, $this->structure);
        $this->assertEquals(
            '(equals=equals)',
            (string) $query
        );
    }

    public function testMethodTostringHasResultStringTheQuery()
    {
        $this->structure->expects($this->exactly(2))
            ->method('mapExternalToInternalAttribute')
            ->will($this->returnValue('internal'));
        $equals = new Horde_Kolab_Server_Query_Element_Equals('equals', 'equals');
        $contains = new Horde_Kolab_Server_Query_Element_Equals('contains', 'contains');
        $or = new Horde_Kolab_Server_Query_Element_Or(array($equals, $contains));
        $query = new Horde_Kolab_Server_Query_Ldap($or, $this->structure);
        $this->assertEquals(
            '(|(internal=equals)(internal=contains))',
            (string) $query
        );
    }

    public function testMethodConvertequealsHasResultNetldapfilter()
    {
        $this->structure->expects($this->once())
            ->method('mapExternalToInternalAttribute')
            ->will($this->returnValue('equals'));
        $equals = new Horde_Kolab_Server_Query_Element_Equals('equals', 'equals');
        $query = new Horde_Kolab_Server_Query_Ldap($equals, $this->structure);
        $this->assertEquals(
            '(equals=equals)',
            $query->convertEquals($equals)->asString()
        );
    }

    public function testMethodConvertbeginsHasResultNetldapfilter()
    {
        $this->structure->expects($this->once())
            ->method('mapExternalToInternalAttribute')
            ->will($this->returnValue('begins'));
        $begins = new Horde_Kolab_Server_Query_Element_Begins('begins', 'begins');
        $query = new Horde_Kolab_Server_Query_Ldap($begins, $this->structure);
        $this->assertEquals(
            '(begins=begins*)',
            $query->convertBegins($begins)->asString()
        );
    }

    public function testMethodConvertendsHasResultNetldapfilter()
    {
        $this->structure->expects($this->once())
            ->method('mapExternalToInternalAttribute')
            ->will($this->returnValue('ends'));
        $ends = new Horde_Kolab_Server_Query_Element_Ends('ends', 'ends');
        $query = new Horde_Kolab_Server_Query_Ldap($ends, $this->structure);
        $this->assertEquals(
            '(ends=*ends)',
            $query->convertEnds($ends)->asString()
        );
    }

    public function testMethodConvertcontainsHasResultNetldapfilter()
    {
        $this->structure->expects($this->once())
            ->method('mapExternalToInternalAttribute')
            ->will($this->returnValue('contains'));
        $contains = new Horde_Kolab_Server_Query_Element_Contains('contains', 'contains');
        $query = new Horde_Kolab_Server_Query_Ldap($contains, $this->structure);
        $this->assertEquals(
            '(contains=*contains*)',
            $query->convertContains($contains)->asString()
        );
    }

    public function testMethodConvertlessHasResultNetldapfilter()
    {
        $this->structure->expects($this->once())
            ->method('mapExternalToInternalAttribute')
            ->will($this->returnValue('less'));
        $less = new Horde_Kolab_Server_Query_Element_Less('less', 'less');
        $query = new Horde_Kolab_Server_Query_Ldap($less, $this->structure);
        $this->assertEquals(
            '(less<less)',
            $query->convertLess($less)->asString()
        );
    }

    public function testMethodConvertgreaterHasResultNetldapfilter()
    {
        $this->structure->expects($this->once())
            ->method('mapExternalToInternalAttribute')
            ->will($this->returnValue('greater'));
        $greater = new Horde_Kolab_Server_Query_Element_Greater('greater', 'greater');
        $query = new Horde_Kolab_Server_Query_Ldap($greater, $this->structure);
        $this->assertEquals(
            '(greater>greater)',
            $query->convertGreater($greater)->asString()
        );
    }

    public function testMethodConvertapproxHasResultNetldapfilter()
    {
        $this->structure->expects($this->once())
            ->method('mapExternalToInternalAttribute')
            ->will($this->returnValue('approx'));
        $approx = new Horde_Kolab_Server_Query_Element_Approx('approx', 'approx');
        $query = new Horde_Kolab_Server_Query_Ldap($approx, $this->structure);
        $this->assertEquals(
            '(approx~=approx)',
            $query->convertApprox($approx)->asString()
        );
    }

    public function testMethodConvertnotHasResultNetldapfilter()
    {
        $this->structure->expects($this->once())
            ->method('mapExternalToInternalAttribute')
            ->will($this->returnValue('equals'));
        $equals = new Horde_Kolab_Server_Query_Element_Equals('equals', 'equals');
        $not = new Horde_Kolab_Server_Query_Element_Not($equals, $this->structure);
        $query = new Horde_Kolab_Server_Query_Ldap($not, $this->structure);
        $this->assertEquals(
            '(!(equals=equals))',
            $query->convertNot($not)->asString()
        );
    }

    public function testMethodConvertandHasResultNetldapfilter()
    {
        $this->structure->expects($this->exactly(2))
            ->method('mapExternalToInternalAttribute')
            ->will($this->returnValue('internal'));
        $equals = new Horde_Kolab_Server_Query_Element_Equals('equals', 'equals');
        $contains = new Horde_Kolab_Server_Query_Element_Equals('contains', 'contains');
        $and = new Horde_Kolab_Server_Query_Element_And(array($equals, $contains));
        $query = new Horde_Kolab_Server_Query_Ldap($and, $this->structure);
        $this->assertEquals(
            '(&(internal=equals)(internal=contains))',
            $query->convertAnd($and)->asString()
        );
    }

    public function testMethodConvertorHasResultNetldapfilter()
    {
        $this->structure->expects($this->exactly(2))
            ->method('mapExternalToInternalAttribute')
            ->will($this->returnValue('internal'));
        $equals = new Horde_Kolab_Server_Query_Element_Equals('equals', 'equals');
        $contains = new Horde_Kolab_Server_Query_Element_Equals('contains', 'contains');
        $or = new Horde_Kolab_Server_Query_Element_Or(array($equals, $contains));
        $query = new Horde_Kolab_Server_Query_Ldap($or, $this->structure);
        $this->assertEquals(
            '(|(internal=equals)(internal=contains))',
            $query->convertOr($or)->asString()
        );
    }

    public function testMethodConvertorThrowsExceptionIfLessThanTwoElementsWereProvided()
    {
        $equals = new Horde_Kolab_Server_Query_Element_Equals('equals', 'equals');
        $or = new Horde_Kolab_Server_Query_Element_Or(array($equals));
        $query = new Horde_Kolab_Server_Query_Ldap($or, $this->structure);

        /** Hide strict errors from the Net_LDAP2 library */
        $error_reporting = error_reporting();
        error_reporting($error_reporting & ~E_STRICT);

        try {
            $query->convertOr($or)->asString();
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals(Horde_Kolab_Server_Exception::INVALID_QUERY, $e->getCode());
        }

        /** Reactivate original error reporting */
        error_reporting($error_reporting);
    }
}
