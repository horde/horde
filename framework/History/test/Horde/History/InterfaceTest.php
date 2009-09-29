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
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/**
 * A test suite for the Horde_History:: interface. DOX format is suggested for
 * the PHPUnit test report.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
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
    /** The basic mock environment */
    const ENVIRONMENT_MOCK = 'mock';

    /** The environment using a database */
    const ENVIRONMENT_DB = 'db';

    /**
     * Path to the temporary sqlite db used for testing the db environment.
     */
    private $_db_file;


    public function setUp()
    {
    }

    public function tearDown()
    {
        if (!empty($this->_db_file)) {
            unlink($this->_db_file);
        }
    }

    /**
     * Identify the environments we want to run our tests in.
     *
     * @return array The selected environments.
     */
    public function getEnvironments()
    {
        if (empty($this->_environments)) {
            /** The db environment provides our only test scenario before refactoring */
            $this->_environments = array(self::ENVIRONMENT_DB);
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
     * Return the history handler for the specified environment.
     *
     * @param string $environment The selected environment.
     *
     * @return History The history.
     */
    public function &getHistory($environment)
    {
        if (!isset($this->_histories[$environment])) {
            switch ($environment) {
            case self::ENVIRONMENT_DB:
                global $conf;

                $this->_db_file = Horde::getTempFile('Horde_Test', false);
                $this->_db = sqlite_open($this->_db_file, '0640');
                $table = <<<EOL
CREATE TABLE horde_histories (
    history_id       INT UNSIGNED NOT NULL,
    object_uid       VARCHAR(255) NOT NULL,
    history_action   VARCHAR(32) NOT NULL,
    history_ts       BIGINT NOT NULL,
    history_desc     TEXT,
    history_who      VARCHAR(255),
    history_extra    TEXT,
--
    PRIMARY KEY (history_id)
);
EOL;

                sqlite_exec($this->_db, $table);
                sqlite_exec($this->_db, 'CREATE INDEX history_action_idx ON horde_histories (history_action);');
                sqlite_exec($this->_db, 'CREATE INDEX history_ts_idx ON horde_histories (history_ts);');
                sqlite_exec($this->_db, 'CREATE INDEX history_uid_idx ON horde_histories (object_uid);');
                sqlite_close($this->_db);

                $conf['sql']['database'] =  $this->_db_file;
                $conf['sql']['mode'] = '0640';
                $conf['sql']['charset'] = 'utf-8';
                $conf['sql']['phptype'] = 'sqlite';

                $history = new Horde_History();
                break;
            }
        }
        $this->_histories[$environment] = &$history;
        return $this->_histories[$environment];
    }

    public function testMethodSingletonHasResultHordehistoryWhichIsAlwaysTheSame()
    {
        //TODO: More complex
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $history1 = Horde_History::singleton();
            $history2 = Horde_History::singleton();
            $this->assertSame($history1, $history2);
        }
    }

    public function testMethodLogHasPostConditionThatTimestampAndActorAreAlwaysRecorded()
    {
        //TODO: More complex
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $history->log('test', array('action' => 'test'));
            $this->assertTrue($history->getActionTimestamp('test', 'test') > 0);
	    $data = $history->getHistory('test')->getData();
            $this->assertTrue(isset($data[0]['who']));
        }
    }

    public function testMethodLogHasPostConditionThatTheGivenEventHasBeenRecorded()
    {
        //TODO: More complex
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'));
            $this->assertEquals(1000, $history->getActionTimestamp('test', 'test'));
        }
    }

    public function testMethodLogHasParameterStringGuid()
    {
        //@todo: Add a check for the type.
    }

    public function testMethodLogHasArrayParameterAttributes()
    {
        //@todo: type hint the array
    }

    public function testMethodLogHasArrayParameterBooleanReplaceaction()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'));
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'), false);
            $history->log('test', array('who' => 'you', 'ts' => 2000, 'action' => 'yours'));
            $history->log('test', array('who' => 'you', 'ts' => 2000, 'action' => 'yours'), true);
            $data = $history->getHistory('test')->getData();
            $expect = array(
                array(
                    'action' => 'test',
                    'desc'   => '',
                    'who'    => 'me',
                    'id'     => 1,
                    'ts'     => 1000,
                ),
                array(
                    'action' => 'test',
                    'desc'   => '',
                    'who'    => 'me',
                    'id'     => 2,
                    'ts'     => 1000,
                ),
                array(
                    'action' => 'yours',
                    'desc'   => '',
                    'who'    => 'you',
                    'id'     => 3,
                    'ts'     => 2000,
                ),
            );
            $this->assertEquals($expect, $data);
        }
    }

    public function testMethodGethistoryHasParameterStringGuid()
    {
        //@todo: Add a check for the type.
    }

    public function testMethodGethistoryHasResultHordehistoryobjectRepresentingTheHistoryLogMatchingTheGivenGuid()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'));
            $history->log('test', array('who' => 'you', 'ts' => 2000, 'action' => 'yours', 'extra' => array('a' => 'a')));
            $data = $history->getHistory('test')->getData();
            $expect = array(
                array(
                    'action' => 'test',
                    'desc'   => '',
                    'who'    => 'me',
                    'id'     => 1,
                    'ts'     => 1000,
                ),
                array(
                    'action' => 'yours',
                    'desc'   => '',
                    'who'    => 'you',
                    'id'     => 2,
                    'ts'     => 2000,
		    'extra'  => array('a' => 'a'),
                ),
            );
            $this->assertEquals($expect, $data);
        }
    }

    public function testMethodGetbytimestampHasParameterStringCmp()
    {
    }

    public function testMethodGetbytimestampHasParameterIntegerTs()
    {
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
        //TODO: More complex
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'));
            $history->log('test', array('who' => 'you', 'ts' => 2000, 'action' => 'yours', 'extra' => array('a' => 'a')));
	    $result = $history->getByTimestamp('<', 1001);
            $this->assertEquals(array('test' => 1), $result);
        }
    }

    public function testMethodGetactiontimestampHasParameterStringGuid()
    {
        //@todo: Add a check for the type.
    }

    public function testMethodGetactiontimestampHasParameterStringAction()
    {
        //@todo: Add a check for the type.
    }

    public function testMethodGetactiontimestampHasResultIntegerZeroIfGethistoryReturnsAnError()
    {
        //@todo: test
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
            $data = $history->getHistory('test')->getData();
            $this->assertEquals(array(), $data);
            $data = $history->getHistory('yours')->getData();
            $expect = array(
                array(
                    'action' => 'yours',
                    'desc'   => '',
                    'who'    => 'you',
                    'id'     => 3,
                    'ts'     => 2000,
                ),
            );
            $this->assertEquals($expect, $data);
            $history->removeByNames(array('yours'));
            $data = $history->getHistory('yours')->getData();
            $this->assertEquals(array(), $data);
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
            $data = $history->getHistory('test')->getData();
            $this->assertEquals(array(), $data);
            $data = $history->getHistory('yours')->getData();
            $this->assertEquals(array(), $data);
        }
    }

    public function testMethodRemovebynamesHasResultBooleanTrueIfParameterNamesIsEmpty()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'));
            $history->log('test', array('who' => 'me', 'ts' => 1000, 'action' => 'test'), false);
            $history->log('test', array('who' => 'you', 'ts' => 2000, 'action' => 'yours'));
            $history->log('test', array('who' => 'you', 'ts' => 2000, 'action' => 'yours'), true);
            $this->assertEquals(true, $data = $history->removeByNames(array()));
        }
    }

}