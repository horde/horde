<?php
/**
 * Test the decorator for time measurements.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Test the decorator for time measurements.
 *
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Unit_Decorator_TimedTest
extends Horde_Kolab_Format_TestCase
{
    public function testConstructor()
    {
        $this->getFactory()->create(
            'XML', 'contact', array('timelog' => true)
        );
    }

    public function testTimeSpent()
    {
        $timed = $this->_getTimedMock();
        $a = '';
        $timed->load($a);
        $this->assertInternalType(
            'float',
            $timed->timeSpent()
        );
    }

    public function testTimeSpentIncreases()
    {
        $timed = $this->_getTimedMock();
        $a = '';
        $timed->load($a);
        $t_one = $timed->timeSpent();
        $timed->save(array());
        $this->assertTrue(
            $t_one < $timed->timeSpent()
        );
    }

    public function testLogLoad()
    {
        $timed = $this->_getTimedMock();
        $a = '';
        $timed->load($a);
        $this->assertContains(
            'Kolab Format data parsing complete. Time spent:',
            array_pop($this->logger->log)
        );
    }

    public function testLogSave()
    {
        $timed = $this->_getTimedMock();
        $a = array();
        $timed->save($a);
        $this->assertContains(
            'Kolab Format data generation complete. Time spent:',
            array_pop($this->logger->log)
        );
    }

    public function testNoLog()
    {
        $timed = new Horde_Kolab_Format_Decorator_Timed(
            $this->getMock('Horde_Kolab_Format'),
            new Horde_Support_Timer(),
            true
        );
        $a = array();
        $timed->save($a);
    }

    private function _getTimedMock()
    {
        $this->logger = new Horde_Kolab_Format_Stub_Log();
        return new Horde_Kolab_Format_Decorator_Timed(
            $this->getMock('Horde_Kolab_Format'),
            new Horde_Support_Timer(),
            $this->logger
        );
    }
}
