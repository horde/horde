<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @package    History
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_History_TestBase extends Horde_Test_Case
{
    protected static $history;

    public function testMethodLogHasPostConditionThatTimestampAndActorAreAlwaysRecorded()
    {
        self::$history->log('test_uid', array('who' => 'me', 'action' => 'test_action'));
        $this->assertTrue(self::$history->getActionTimestamp('test_uid', 'test_action') > 0);
        $data = self::$history->getHistory('test_uid');
        $this->assertTrue(isset($data[0]['who']));
    }

    public function testMethodLogHasPostConditionThatTheGivenEventHasBeenRecorded()
    {
        self::$history->log('test_uid', array('who' => 'me', 'ts' => 1000, 'action' => 'test_action'));
        $this->assertEquals(1000, self::$history->getActionTimestamp('test_uid', 'test_action'));
    }

    public function testMethodLogHasParameterStringGuid()
    {
        try {
            self::$history->log(array());
            $this->fail('No exception!');
        } catch (InvalidArgumentException $e) {
        }
    }

    public function testMethodLogHasArrayParameterBooleanReplaceaction()
    {
        self::$history->log('test_uid', array('who' => 'me', 'ts' => 1000, 'action' => 'test_action'));
        self::$history->log('test_uid', array('who' => 'me', 'ts' => 1000, 'action' => 'test_action'), false);
        self::$history->log('test_uid', array('who' => 'you', 'ts' => 2000, 'action' => 'yours_action'));
        self::$history->log('test_uid', array('who' => 'you', 'ts' => 2000, 'action' => 'yours_action'), true);

        $data = self::$history->getHistory('test_uid');
        $expect = array(
            'action' => 'test_action',
            'desc'   => null,
            'who'    => 'me',
            'ts'     => 1000,
        );
        foreach ($expect as $key => $value) {
            $this->assertEquals($value, $data[0][$key]);
        }
        $expect = array(
            'action' => 'test_action',
            'desc'   => null,
            'who'    => 'me',
            'ts'     => 1000,
        );
        foreach ($expect as $key => $value) {
            $this->assertEquals($value, $data[1][$key]);
        }

        $expect = array(
            'action' => 'yours_action',
            'desc'   => '',
            'who'    => 'you',
            'ts'     => 2000,
        );
        foreach ($expect as $key => $value) {
            $this->assertEquals($value, $data[2][$key]);
        }
    }

    public function testMethodGethistoryHasParameterStringGuid()
    {
        try {
            self::$history->getHistory(array());
            $this->fail('No exception!');
        } catch (Horde_History_Exception $e) {
        }
    }

    public function testMethodGethistoryHasResultHordehistorylogRepresentingTheHistoryLogMatchingTheGivenGuid()
    {
        self::$history->log('test_uid', array('who' => 'me', 'ts' => 1000, 'action' => 'test_action'));
        self::$history->log('test_uid', array('who' => 'you', 'ts' => 2000, 'action' => 'yours_action', 'extra' => array('a' => 'a')));
        $data = self::$history->getHistory('test_uid');
        $expect = array(
            'action' => 'test_action',
            'desc'   => '',
            'who'    => 'me',
            'ts'     => 1000,
        );
        foreach ($expect as $key => $value) {
            $this->assertEquals($value, $data[0][$key]);
        }
        $expect = array(
            'action' => 'yours_action',
            'desc'   => '',
            'who'    => 'you',
            'ts'     => 2000,
            'extra'  => array('a' => 'a'),
        );
        foreach ($expect as $key => $value) {
            $this->assertEquals($value, $data[1][$key]);
        }
    }

    public function testMethodGetbytimestampHasParameterStringCmp()
    {
        try {
            self::$history->getByTimestamp(array(), 1);
            $this->fail('No exception!');
        } catch (Horde_History_Exception $e) {
        }
    }

    public function testMethodGetbytimestampHasParameterIntegerTs()
    {
        try {
            self::$history->getByTimestamp('>', 'hello');
            $this->fail('No exception!');
        } catch (Horde_History_Exception $e) {
        }
    }

    public function testMethodGetbytimestampHasParameterArrayFilters()
    {
        self::$history->log('test_uid', array('who' => 'me', 'ts' => 1000, 'action' => 'test_action'));
        self::$history->log('test_uid', array('who' => 'you', 'ts' => 2000, 'action' => 'yours_action', 'extra' => array('a' => 'a')));
        $result = self::$history->getByTimestamp('>', 1, array(array('field' => 'who', 'op' => '=', 'value' => 'you')));
        $this->assertEquals(array('test_uid' => 2), $result);
    }

    public function testMethodGetbytimestampHasParameterStringParent()
    {
        self::$history->log('test_uid:a_uid', array('who' => 'me', 'ts' => 1000, 'action' => 'test_action'));
        self::$history->log('test_uid:b_uid', array('who' => 'you', 'ts' => 2000, 'action' => 'yours_action'));
        self::$history->log('yours_uid', array('who' => 'you', 'ts' => 3000, 'action' => 'yours_action'));
        $result = self::$history->getByTimestamp('>', 1, array(), 'test_uid');
        $this->assertEquals(array('test_uid:a_uid' => 1, 'test_uid:b_uid' => 2), $result);
    }

    public function testMethodGetbytimestampHasResultArrayContainingTheMatchingEventIds()
    {
        self::$history->log('test_uid', array('who' => 'me', 'ts' => 1000, 'action' => 'test_action'));
        self::$history->log('test_uid', array('who' => 'me', 'ts' => 1000, 'action' => 'test_action'));
        self::$history->log('test_uid', array('who' => 'you', 'ts' => 2000, 'action' => 'yours_action', 'extra' => array('a' => 'a')));
        $result = self::$history->getByTimestamp('<=', 1000);
        $this->assertEquals(array('test_uid' => 2), $result);
        $result = self::$history->getByTimestamp('<', 1001);
        $this->assertEquals(array('test_uid' => 2), $result);
        $result = self::$history->getByTimestamp('>', 1001);
        $this->assertEquals(array('test_uid' => 3), $result);
        $result = self::$history->getByTimestamp('>=', 2000);
        $this->assertEquals(array('test_uid' => 3), $result);
        $result = self::$history->getByTimestamp('=', 2000);
        $this->assertEquals(array('test_uid' => 3), $result);
        $result = self::$history->getByTimestamp('>', 2000);
        $this->assertEquals(array(), $result);
    }

    public function testMethodGetactiontimestampHasParameterStringGuid()
    {
        try {
            self::$history->getActionTimestamp(array(), 'test');
            $this->fail('No exception!');
        } catch (Horde_History_Exception $e) {
        }
    }

    public function testMethodGetactiontimestampHasParameterStringAction()
    {
        try {
            self::$history->getActionTimestamp('test', array());
            $this->fail('No exception!');
        } catch (Horde_History_Exception $e) {
        }
    }

    public function testMethodGetactiontimestampHasResultIntegerZeroIfGethistoryReturnsAnError()
    {
        $this->assertEquals(0, self::$history->getActionTimestamp('test', 'test'));
    }

    public function testMethodGetactiontimestampHasResultIntegerZeroIfThereIsNoMatchingRecord()
    {
        $this->assertEquals(0, self::$history->getActionTimestamp('test', 'test'));
    }

    public function testMethodGetactiontimestampHasResultIntegerTimestampOfTheMatchingRecord()
    {
        self::$history->log('test_uid', array('who' => 'me', 'ts' => 1, 'action' => 'test_action'));
        self::$history->log('test_uid', array('who' => 'me', 'ts' => 2, 'action' => 'test_action'));
        $this->assertEquals(2, self::$history->getActionTimestamp('test_uid', 'test_action'));
        self::$history->log('test_uid', array('who' => 'me', 'ts' => 3, 'action' => 'test_action'));
        $this->assertEquals(3, self::$history->getActionTimestamp('test_uid', 'test_action'));
    }

    public function testMethodRemovebynamesHasPostconditionThatAllNamedRevordsHaveBeenRemoved()
    {
        self::$history->log('test_uid', array('who' => 'me', 'ts' => 1000, 'action' => 'test_action'));
        self::$history->log('test_uid', array('who' => 'me', 'ts' => 1000, 'action' => 'test_action'), false);
        self::$history->log('yours_uid', array('who' => 'you', 'ts' => 2000, 'action' => 'yours_action'));
        self::$history->log('yours_uid', array('who' => 'you', 'ts' => 2000, 'action' => 'yours_action'), true);
        self::$history->removeByNames(array('test_uid'));
        $data = self::$history->getHistory('test_uid');
        $this->assertEquals(0, count($data));
        $data = self::$history->getHistory('yours_uid');
        $expect = array(
            'action' => 'yours_action',
            'desc'   => '',
            'who'    => 'you',
            'id'     => 3,
            'ts'     => 2000,
            'modseq' => 4
        );
        $this->assertEquals($expect, $data[0]);
        self::$history->removeByNames(array('yours_uid'));
        $data = self::$history->getHistory('yours_uid');
        $this->assertEquals(0, count($data));

    }

    public function testMethodRemovebynamesHasParameterArrayNames()
    {
        self::$history->log('test_uid', array('who' => 'me', 'ts' => 1000, 'action' => 'test_action'));
        self::$history->log('test_uid', array('who' => 'me', 'ts' => 1000, 'action' => 'test_action'), false);
        self::$history->log('yours_uid', array('who' => 'you', 'ts' => 2000, 'action' => 'yours_action'));
        self::$history->log('yours_uid', array('who' => 'you', 'ts' => 2000, 'action' => 'yours_action'), true);
        self::$history->removeByNames(array('test_uid', 'yours_uid'));
        $data = self::$history->getHistory('test_uid');
        $this->assertEquals(0, count($data));
        $data = self::$history->getHistory('yours_uid');
        $this->assertEquals(0, count($data));
    }

    public function testMethodRemovebynamesSucceedsIfParameterNamesIsEmpty()
    {
        self::$history->log('test_uid', array('who' => 'me', 'ts' => 1000, 'action' => 'test_action'));
        self::$history->log('test_uid', array('who' => 'me', 'ts' => 1000, 'action' => 'test_action'), false);
        self::$history->log('test_uid', array('who' => 'you', 'ts' => 2000, 'action' => 'yours_action'));
        self::$history->log('test_uid', array('who' => 'you', 'ts' => 2000, 'action' => 'yours_action'), true);
        self::$history->removeByNames(array());
    }

    public function testConditionThatEmptyHistoryReturnsAFalseHighestModSeq()
    {
        $this->assertFalse(self::$history->getHighestModSeq());
    }

    public function testModSeqMethodsHavePostConditionThatMaxModSeqIncrements()
    {
        self::$history->log('test_uid', array('who' => 'me', 'action' => 'test_action'));
        $this->assertEquals(self::$history->getHighestModSeq(), 1);
        self::$history->log('test_uid', array('who' => 'me', 'action' => 'test_other_action'));
        $this->assertEquals(self::$history->getHighestModSeq(), 2);
    }

    public function testMethodLogHasPostConditionThatModSeqIsRecorded()
    {
        self::$history->log('test_uid', array('who' => 'me', 'action' => 'test_action'));
        $data = self::$history->getHistory('test_uid');
        $this->assertEquals($data[0]['modseq'], 1);
        self::$history->log('test_uid', array('who' => 'you', 'action' => 'your_action'));
        $data = self::$history->getHistory('test_uid');
        $this->assertEquals($data[0]['modseq'], 1);
        $this->assertEquals($data[1]['modseq'], 2);
    }

    public function testMethodLogHasPostConditionThatModSeqIsRecordedWhenLogIsOverwritten()
    {
        self::$history->log('test_uid', array('who' => 'me', 'action' => 'test_action'));
        $data = self::$history->getHistory('test_uid');
        $this->assertEquals($data[0]['modseq'], 1);
        self::$history->log('test_uid', array('who' => 'me', 'action' => 'test_action'), true);
        $data = self::$history->getHistory('test_uid');
        $this->assertEquals($data[0]['modseq'], 2);
        $this->assertTrue(empty($data[1]));
    }

    public function testMethodGetActionModSeqHasResultMatchingRequestedEntry()
    {
        self::$history->log('test_uid', array('who' => 'me', 'ts' => 1, 'action' => 'test_action'));
        self::$history->log('test_uid', array('who' => 'me', 'ts' => 2, 'action' => 'test_action'));
        self::$history->log('test_uid', array('who' => 'me', 'ts' => 3, 'action' => 'test_otheraction'));
        $this->assertEquals(self::$history->getActionModSeq('test_uid', 'test_action'), 2);
        self::$history->log('test_otheruid', array('who' => 'me', 'ts' => 3, 'action' => 'test_action'));
        $this->assertEquals(self::$history->getActionModSeq('test_uid', 'test_action'), 2);
        self::$history->log('test_uid', array('who' => 'you', 'ts' => 5, 'action' => 'test_action'), true);
        $this->assertEquals(self::$history->getActionModSeq('test_uid', 'test_action'), 5);
    }

    public function testMethodGetbymodseqHasResultArrayContainingTheMatchingEventIds()
    {
        self::$history->log('appone:test_uid', array('who' => 'me', 'ts' => 1000, 'action' => 'test_action')); // 1
        self::$history->log('appone:test_uid', array('who' => 'me', 'ts' => 1001, 'action' => 'test_action')); // 2
        self::$history->log('apptwo:test_uid', array('who' => 'you', 'ts' => 1002, 'action' => 'test_special_action')); // 3
        self::$history->log('apptwo:test_uid', array('who' => 'me', 'ts' => 1003, 'action' => 'test_action')); // 4
        self::$history->log('appone:test_uid', array('who' => 'me', 'ts' => 1004, 'action' => 'test_action')); // 5
        self::$history->log('appone:test_uid', array('who' => 'you', 'ts' => 2000, 'action' => 'yours_action', 'extra' => array('a' => 'a'))); // 6
        self::$history->log('appone:test_uid', array('who' => 'you', 'ts' => 2000, 'action' => 'yours_action', 'extra' => array('a' => 'a'))); // 7
        self::$history->log('appone:test_uid', array('who' => 'you', 'ts' => 2000, 'action' => 'yours_action', 'extra' => array('a' => 'a'))); // 8

        // Only have two unique UIDS.
        $result = self::$history->getByModSeq(0, 5);
        $this->assertEquals(array('appone:test_uid' => 5, 'apptwo:test_uid' => 4), $result);

        $result = self::$history->getByModSeq(4, 8);
        $this->assertEquals(array('appone:test_uid' => 8), $result);

        // Test using action filter.
        $filter = array(array('op' => '=', 'field' => 'action', 'value' => 'test_special_action'));
        $result = self::$history->getByModSeq(0, 5, $filter);
        $this->assertEquals(array('apptwo:test_uid' => 3), $result);

        // Test using parent
        $result = self::$history->getByModSeq(0, 5, array(), 'apptwo');
        $this->assertEquals(array('apptwo:test_uid' => 4), $result);

        // Test parent AND filter
        $result = self::$history->getByModSeq(0, 5, $filter, 'apptwo');
        $this->assertEquals(array('apptwo:test_uid' => 3), $result);
    }

}
