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
require_once dirname(__FILE__) . '/../LdapBase.php';

/**
 * Test the LDAP query handler.
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
class Horde_Kolab_Server_Query_LdapTest extends Horde_Kolab_Server_LdapBase
{

    public function testMethodConstructHasParameterQueryelementTheQueryCriteria()
    {
        $equals = new Horde_Kolab_Server_Query_Element_Equals('equals', 'equals');
        $query = new Horde_Kolab_Server_Query_Ldap($equals);
    }

    public function testMethodConstructHasPostconditionThatTheQueryCriteriaWereSaved()
    {
        $equals = new Horde_Kolab_Server_Query_Element_Equals('equals', 'equals');
        $query = new Horde_Kolab_Server_Query_Ldap($equals);
        $this->assertEquals(
            '(equals=equals)',
            (string) $query
        );
    }

    public function testMethodTostringHasResultStringTheQuery()
    {
        $equals = new Horde_Kolab_Server_Query_Element_Equals('equals', 'equals');
        $contains = new Horde_Kolab_Server_Query_Element_Equals('contains', 'contains');
        $or = new Horde_Kolab_Server_Query_Element_Or(array($equals, $contains));
        $query = new Horde_Kolab_Server_Query_Ldap($or);
        $this->assertEquals(
            '(|(equals=equals)(contains=contains))',
            (string) $query
        );
    }

    public function testMethodConvertequealsHasResultNetldapfilter()
    {
        $equals = new Horde_Kolab_Server_Query_Element_Equals('equals', 'equals');
        $query = new Horde_Kolab_Server_Query_Ldap($equals);
        $this->assertEquals(
            '(equals=equals)',
            $query->convertEquals($equals)->asString()
        );
    }

    public function testMethodConvertbeginsHasResultNetldapfilter()
    {
        $begins = new Horde_Kolab_Server_Query_Element_Begins('begins', 'begins');
        $query = new Horde_Kolab_Server_Query_Ldap($begins);
        $this->assertEquals(
            '(begins=begins*)',
            $query->convertBegins($begins)->asString()
        );
    }

    public function testMethodConvertendsHasResultNetldapfilter()
    {
        $ends = new Horde_Kolab_Server_Query_Element_Ends('ends', 'ends');
        $query = new Horde_Kolab_Server_Query_Ldap($ends);
        $this->assertEquals(
            '(ends=*ends)',
            $query->convertEnds($ends)->asString()
        );
    }

    public function testMethodConvertcontainsHasResultNetldapfilter()
    {
        $contains = new Horde_Kolab_Server_Query_Element_Contains('contains', 'contains');
        $query = new Horde_Kolab_Server_Query_Ldap($contains);
        $this->assertEquals(
            '(contains=*contains*)',
            $query->convertContains($contains)->asString()
        );
    }

    public function testMethodConvertlessHasResultNetldapfilter()
    {
        $less = new Horde_Kolab_Server_Query_Element_Less('less', 'less');
        $query = new Horde_Kolab_Server_Query_Ldap($less);
        $this->assertEquals(
            '(less<less)',
            $query->convertLess($less)->asString()
        );
    }

    public function testMethodConvertgreaterHasResultNetldapfilter()
    {
        $greater = new Horde_Kolab_Server_Query_Element_Greater('greater', 'greater');
        $query = new Horde_Kolab_Server_Query_Ldap($greater);
        $this->assertEquals(
            '(greater>greater)',
            $query->convertGreater($greater)->asString()
        );
    }

    public function testMethodConvertapproxHasResultNetldapfilter()
    {
        $approx = new Horde_Kolab_Server_Query_Element_Approx('approx', 'approx');
        $query = new Horde_Kolab_Server_Query_Ldap($approx);
        $this->assertEquals(
            '(approx~=approx)',
            $query->convertApprox($approx)->asString()
        );
    }

    public function testMethodConvertnotHasResultNetldapfilter()
    {
        $equals = new Horde_Kolab_Server_Query_Element_Equals('equals', 'equals');
        $not = new Horde_Kolab_Server_Query_Element_Not($equals);
        $query = new Horde_Kolab_Server_Query_Ldap($not);
        $this->assertEquals(
            '(!(equals=equals))',
            $query->convertNot($not)->asString()
        );
    }

    public function testMethodConvertandHasResultNetldapfilter()
    {
        $equals = new Horde_Kolab_Server_Query_Element_Equals('equals', 'equals');
        $contains = new Horde_Kolab_Server_Query_Element_Equals('contains', 'contains');
        $and = new Horde_Kolab_Server_Query_Element_And(array($equals, $contains));
        $query = new Horde_Kolab_Server_Query_Ldap($and);
        $this->assertEquals(
            '(&(equals=equals)(contains=contains))',
            $query->convertAnd($and)->asString()
        );
    }

    public function testMethodConvertorHasResultNetldapfilter()
    {
        $equals = new Horde_Kolab_Server_Query_Element_Equals('equals', 'equals');
        $contains = new Horde_Kolab_Server_Query_Element_Equals('contains', 'contains');
        $or = new Horde_Kolab_Server_Query_Element_Or(array($equals, $contains));
        $query = new Horde_Kolab_Server_Query_Ldap($or);
        $this->assertEquals(
            '(|(equals=equals)(contains=contains))',
            $query->convertOr($or)->asString()
        );
    }

    public function testMethodConvertorThrowsExceptionIfLessThanTwoElementsWereProvided()
    {
        $equals = new Horde_Kolab_Server_Query_Element_Equals('equals', 'equals');
        $or = new Horde_Kolab_Server_Query_Element_Or(array($equals));
        $query = new Horde_Kolab_Server_Query_Ldap($or);
        try {
            $query->convertOr($or)->asString();
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals(Horde_Kolab_Server_Exception::INVALID_QUERY, $e->getCode());
        }
    }
}
