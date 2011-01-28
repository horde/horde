<?php
/**
 * Interface testing for Horde_History::
 *
 * PHP version 5
 *
 * @category Horde
 * @package  History
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=History
 */

/**
 * A test suite for the Horde_History:: interface. DOX format is suggested for
 * the PHPUnit test report.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  History
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=History
 */
class Horde_History_InterfaceTest extends PHPUnit_Framework_TestCase
{
    /**
     * Identify the environments we want to run our tests in.
     *
     * @return array The selected environments.
     */
    public function getEnvironments()
    {
        if (empty($this->_environments)) {
            /* The db environment provides our only test scenario before
             * refactoring.  */
            $this->_environments = array('Mock', 'Sql');
        }
        return $this->_environments;
    }

    /**
     * Specifically set the environments we wish to support.
     *
     * @param array $environments The selected environments.
     *
     * @return NULL
     */
    public function setEnvironments($environments)
    {
        $this->_environments = $environments;
    }

    /**
     * Initialize the given environment.
     *
     * @param string $environment The selected environment.
     *
     * @return Horde_Injector The environment.
     */
    public function initializeEnvironment($environment)
    {
        switch ($environment) {
        case 'Sql':
            $table = <<<EOL
CREATE TABLE horde_histories (
    history_id       INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    object_uid       VARCHAR(255) NOT NULL,
    history_action   VARCHAR(32) NOT NULL,
    history_ts       BIGINT NOT NULL,
    history_desc     TEXT,
    history_who      VARCHAR(255),
    history_extra    TEXT
)
EOL;

            $db = new Horde_Db_Adapter_Pdo_Sqlite(array('dbname' => ':memory:'));
            $db->execute($table);
            $db->execute('CREATE INDEX history_action_idx ON horde_histories (history_action)');
            $db->execute('CREATE INDEX history_ts_idx ON horde_histories (history_ts)');
            $db->execute('CREATE INDEX history_uid_idx ON horde_histories (object_uid)');

            $logger = new Horde_Log_Logger(new Horde_Log_Handler_Null());

            return new Horde_History_Sql($db, $logger);

        case 'Mock':
            return new Horde_History_Mock();
        }
    }

    /**
     * Return the history handler for the specified environment.
     *
     * @param string $environment The selected environment.
     *
     * @return Horde_History The history.
     */
    public function getHistory($environment)
    {
        if (!isset($this->_histories[$environment])) {
            $this->_histories[$environment] = $this->initializeEnvironment($environment);
        }
        return $this->_histories[$environment];
    }

    public function testMethodLogHasPostConditionThatTimestampAndActorAreAlwaysRecorded()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $history->log('test', array('who' => 'me','action' => 'test'));
            $this->assertTrue($history->getActionTimestamp('test', 'test') > 0);
            $data = $history->getHistory('test');
            $this->assertTrue(isset($data[0]['who']));
        }
    }

    public function testMethodLogHasPostConditionThatTheGivenEventHasBeenRecorded()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'));
            $this->assertEquals(1000, $history->getActionTimestamp('test', 'test'));
        }
    }

    public function testMethodLogHasParameterStringGuid()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            try {
                $history->log(array());
                $this->fail('No exception!');
            } catch (Horde_History_Exception $e) {
            }
        }
    }

    public function testMethodLogHasArrayParameterBooleanReplaceaction()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'));
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'), false);
            $history->log('test', array('who' => 'you', 'ts' => 2000, 'action' => 'yours'));
            $history->log('test', array('who' => 'you', 'ts' => 2000, 'action' => 'yours'), true);
            $data = $history->getHistory('test');
            $expect = array(
                'action' => 'test',
                'desc'   => '',
                'who'    => 'me',
                'id'     => 1,
                'ts'     => 1000,
            );
            $this->assertEquals($expect, $data[0]);
            $expect = array(
                'action' => 'test',
                'desc'   => '',
                'who'    => 'me',
                'id'     => 2,
                'ts'     => 1000,
            );
            $this->assertEquals($expect, $data[1]);
            $expect = array(
                'action' => 'yours',
                'desc'   => '',
                'who'    => 'you',
                'id'     => 3,
                'ts'     => 2000,
            );
            $this->assertEquals($expect, $data[2]);
        }
    }

    public function testMethodGethistoryHasParameterStringGuid()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            try {
                $history->getHistory(array());
                $this->fail('No exception!');
            } catch (Horde_History_Exception $e) {
            }
        }
    }

    public function testMethodGethistoryHasResultHordehistorylogRepresentingTheHistoryLogMatchingTheGivenGuid()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'));
            $history->log('test', array('who' => 'you', 'ts' => 2000, 'action' => 'yours', 'extra' => array('a' => 'a')));
            $data = $history->getHistory('test');
            $expect = array(
                'action' => 'test',
                'desc'   => '',
                'who'    => 'me',
                'id'     => 1,
                'ts'     => 1000,
            );
            $this->assertEquals($expect, $data[0]);
            $expect = array(
                'action' => 'yours',
                'desc'   => '',
                'who'    => 'you',
                'id'     => 2,
                'ts'     => 2000,
                'extra'  => array('a' => 'a'),
            );
            $this->assertEquals($expect, $data[1]);
        }
    }

    public function testMethodGetbytimestampHasParameterStringCmp()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            try {
                $history->getByTimestamp(array(), 1);
                $this->fail('No exception!');
            } catch (Horde_History_Exception $e) {
            }
        }
    }

    public function testMethodGetbytimestampHasParameterIntegerTs()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            try {
                $history->getByTimestamp('>', 'hello');
                $this->fail('No exception!');
            } catch (Horde_History_Exception $e) {
            }
        }
    }

    public function testMethodGetbytimestampHasParameterArrayFilters()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'));
            $history->log('test', array('who' => 'you', 'ts' => 2000, 'action' => 'yours', 'extra' => array('a' => 'a')));
            $result = $history->getByTimestamp('>', 1, array(array('field' => 'who', 'op' => '=', 'value' => 'you')));
            $this->assertEquals(array('test' => 2), $result);
        }
    }

    public function testMethodGetbytimestampHasParameterStringParent()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $history->log('test:a', array('who' => 'me', 'ts' => 1000, 'action' => 'test'));
            $history->log('test:b', array('who' => 'you', 'ts' => 2000, 'action' => 'yours'));
            $history->log('yours', array('who' => 'you', 'ts' => 3000, 'action' => 'yours'));
            $result = $history->getByTimestamp('>', 1, array(), 'test');
            $this->assertEquals(array('test:a' => 1, 'test:b' => 2), $result);
        }
    }

    public function testMethodGetbytimestampHasResultArrayContainingTheMatchingEventIds()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'));
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'));
            $history->log('test', array('who' => 'you', 'ts' => 2000, 'action' => 'yours', 'extra' => array('a' => 'a')));
            $result = $history->getByTimestamp('<=', 1000);
            $this->assertEquals(array('test' => 2), $result);
            $result = $history->getByTimestamp('<', 1001);
            $this->assertEquals(array('test' => 2), $result);
            $result = $history->getByTimestamp('>', 1001);
            $this->assertEquals(array('test' => 3), $result);
            $result = $history->getByTimestamp('>=', 2000);
            $this->assertEquals(array('test' => 3), $result);
            $result = $history->getByTimestamp('=', 2000);
            $this->assertEquals(array('test' => 3), $result);
            $result = $history->getByTimestamp('>', 2000);
            $this->assertEquals(array(), $result);
        }
    }

    public function testMethodGetactiontimestampHasParameterStringGuid()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            try {
                $history->getActionTimestamp(array(), 'test');
                $this->fail('No exception!');
            } catch (Horde_History_Exception $e) {
            }
        }
    }

    public function testMethodGetactiontimestampHasParameterStringAction()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            try {
                $history->getActionTimestamp('test', array());
                $this->fail('No exception!');
            } catch (Horde_History_Exception $e) {
            }
        }
    }

    public function testMethodGetactiontimestampHasResultIntegerZeroIfGethistoryReturnsAnError()
    {
        $history = $this->getHistory('Sql');
        $this->assertEquals(0, $history->getActionTimestamp('test', 'test'));
    }

    public function testMethodGetactiontimestampHasResultIntegerZeroIfThereIsNoMatchingRecord()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $this->assertEquals(0, $history->getActionTimestamp('test', 'test'));
        }
    }

    public function testMethodGetactiontimestampHasResultIntegerTimestampOfTheMatchingRecord()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $history->log('test', array('who' => 'me', 'ts' => 1, 'action' => 'test'));
            $history->log('test', array('who' => 'me', 'ts' => 2, 'action' => 'test'));
            $this->assertEquals(2, $history->getActionTimestamp('test', 'test'));
            $history->log('test', array('who' => 'me', 'ts' => 3, 'action' => 'test'));
            $this->assertEquals(3, $history->getActionTimestamp('test', 'test'));
        }
    }

    public function testMethodRemovebynamesHasPostconditionThatAllNamedRevordsHaveBeenRemoved()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'));
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'), false);
            $history->log('yours', array('who' => 'you', 'ts' => 2000, 'action' => 'yours'));
            $history->log('yours', array('who' => 'you', 'ts' => 2000, 'action' => 'yours'), true);
            $history->removeByNames(array('test'));
            $data = $history->getHistory('test');
            $this->assertEquals(0, count($data));
            $data = $history->getHistory('yours');
            $expect = array(
                'action' => 'yours',
                'desc'   => '',
                'who'    => 'you',
                'id'     => 3,
                'ts'     => 2000,
            );
            $this->assertEquals($expect, $data[0]);
            $history->removeByNames(array('yours'));
            $data = $history->getHistory('yours');
            $this->assertEquals(0, count($data));
        }
    }

    public function testMethodRemovebynamesHasParameterArrayNames()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'));
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'), false);
            $history->log('yours', array('who' => 'you', 'ts' => 2000, 'action' => 'yours'));
            $history->log('yours', array('who' => 'you', 'ts' => 2000, 'action' => 'yours'), true);
            $history->removeByNames(array('test', 'yours'));
            $data = $history->getHistory('test');
            $this->assertEquals(0, count($data));
            $data = $history->getHistory('yours');
            $this->assertEquals(0, count($data));
        }
    }

    public function testMethodRemovebynamesSucceedsIfParameterNamesIsEmpty()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'));
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'), false);
            $history->log('test', array('who' => 'you', 'ts' => 2000, 'action' => 'yours'));
            $history->log('test', array('who' => 'you', 'ts' => 2000, 'action' => 'yours'), true);
            $history->removeByNames(array());
        }
    }

    public function testHordehistorysqlConvertsDbExceptionsToHordeHistoryExceptions()
    {
        $db = new Horde_Db_Adapter_Pdo_Sqlite(array('dbname' => ':memory:'));
        $logger = new Horde_Log_Logger(new Horde_Log_Handler_Null());
        $history = new Horde_History_Sql($db, $logger);

        try {
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'));
            $this->fail('No exception was thrown!');
        } catch (Horde_Exception $e) {
        }

        try {
            $history->getHistory('test');
            $this->fail('No exception was thrown!');
        } catch (Horde_Exception $e) {
        }

        try {
            $history->getByTimestamp('>', 1, array(array('field' => 'who', 'op' => '=', 'value' => 'you')));
            $this->fail('No exception was thrown!');
        } catch (Horde_Exception $e) {
        }

        try {
            $history->removeByNames(array('test'));
            $this->fail('No exception was thrown!');
        } catch (Horde_Exception $e) {
        }
    }
}
