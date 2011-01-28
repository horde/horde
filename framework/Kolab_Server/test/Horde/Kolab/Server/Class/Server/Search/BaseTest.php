<?php
/**
 * Test the search handler.
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
 * Test the search handler.
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
class Horde_Kolab_Server_Class_Server_Search_BaseTest
extends Horde_Kolab_Server_TestCase
{
    public function testSetcompositeHasParameterServercomposite()
    {
        $composite = $this->getMockedComposite();
        $composite->structure->expects($this->once())
            ->method('getSearchOperations')
            ->will($this->returnValue(array()));
        $search = new Horde_Kolab_Server_Search_Base();
        $search->setComposite($composite);
    }

    public function testSetcompositeHasPostconditionThatTheAvailableSearchOperationsAreSet()
    {
        $composite = $this->getMockedComposite();
        $composite->structure->expects($this->once())
            ->method('getSearchOperations')
            ->will($this->returnValue(array('Object_Search')));
        $search = new Horde_Kolab_Server_Search_Base();
        $search->setComposite($composite);
        $this->assertEquals(
            array(
                'call' => array('class' => 'Object_Search'),
                'reset' => array('class' => 'Object_Search')
            ),
            $search->getSearchOperations()
        );
    }

    public function testSetcompositeThrowsExceptionIfADeclaredSearchClassDoesNotExist()
    {
        $composite = $this->getMockedComposite();
        $composite->structure->expects($this->once())
            ->method('getSearchOperations')
            ->will($this->returnValue(array('Object_Search_NoSuchClass')));
        $search = new Horde_Kolab_Server_Search_Base();
        try {
            $search->setComposite($composite);
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertContains(
                'getSearchOperations specified non-existing class "Object_Search_NoSuchClass"!',
                $e->getMessage()
            );
        }
    }

    public function testGetsearchoperationsHasResultTheSearchOperationsSupportedByThisServer()
    {
        $this->testSetcompositeHasPostconditionThatTheAvailableSearchOperationsAreSet();
    }

    public function testCallHasResultTheResultOfTheSearchOperation()
    {
        $composite = $this->getMockedComposite();
        $composite->structure->expects($this->once())
            ->method('getSearchOperations')
            ->will($this->returnValue(array('Object_Search')));
        $search = new Horde_Kolab_Server_Search_Base();
        $search->setComposite($composite);
        $this->assertEquals(1, $search->call());
    }

    public function testCallHasPostConditionThatTheSearchWasCalledWithTheServerRepresentation()
    {
        $composite = $this->getMockedComposite();
        $composite->structure->expects($this->once())
            ->method('getSearchOperations')
            ->will($this->returnValue(array('Object_Search')));
        $search = new Horde_Kolab_Server_Search_Base();
        $search->setComposite($composite);
        $search->call('a');
        $this->assertEquals(array('a'), Object_Search::$last_call);
    }

    public function testCallThrowsExceptionIfTheSearchOperationIsNotSupported()
    {
        $composite = $this->getMockedComposite();
        $composite->structure->expects($this->once())
            ->method('getSearchOperations')
            ->will($this->returnValue(array('Object_Search')));
        $search = new Horde_Kolab_Server_Search_Base();
        $search->setComposite($composite);
        try {
            $search->search();
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertContains(
                'does not support method "search"',
                $e->getMessage()
            );
        }
    }

    public function tearDown()
    {
        Object_Search::reset();
    }
}

class Object_Dummy
{
    static public function getSearchOperations()
    {
        return array('Object_Search');
    }
}

class Object_Search
{
    static public $calls = 0;

    static public $last_call;

    static public function call()
    {
        self::$last_call = func_get_args();
        return ++self::$calls;
    }

    static public function reset()
    {
        self::$calls = 0;
    }
}