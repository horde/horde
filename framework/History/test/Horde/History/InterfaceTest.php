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
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
    const ENVIRONMENT_MOCK = 'Mock';

    /** The environment using a database */
    const ENVIRONMENT_DB = 'Sql';

    /**
     * Path to the temporary sqlite db used for testing the db environment.
     */
    private $_db_file;

    /**
     * Test setup.
     */
    public function setUp()
    {
        if (in_array(self::ENVIRONMENT_DB, $this->getEnvironments())) {
            /* PEAR DB is not E_STRICT safe. */
            $this->_errorReporting = error_reporting(E_ALL & ~E_STRICT);
        }
    }

    /**
     * Test cleanup.
     */
    public function tearDown()
    {
        if (in_array(self::ENVIRONMENT_DB, $this->getEnvironments())) {
            error_reporting($this->_errorReporting);
        }
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
            /* The db environment provides our only test scenario before
             * refactoring.  */
            $this->_environments = array(
                self::ENVIRONMENT_MOCK,
                /** Uncomment if you want to run a sqlity based test */
                //self::ENVIRONMENT_DB,
            );
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

            $injector = new Horde_Injector(new Horde_Injector_TopLevel());
            $injector->bindFactory(
                'Horde_History',
                'Horde_History_Factory',
                'getHistory'
            );

            $config = new stdClass;
            $config->driver = 'Sql';
            $config->params = $conf['sql'];
            $injector->setInstance('Horde_History_Config', $config);
            break;
        case self::ENVIRONMENT_MOCK:
            $injector = new Horde_Injector(new Horde_Injector_TopLevel());
            $injector->bindImplementation(
                'Horde_History',
                'Horde_History_Mock'
            );
        }
        return $injector;
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
            $injector = $this->initializeEnvironment($environment);
            $this->_histories[$environment] = $injector->getInstance('Horde_History');
        }
        return $this->_histories[$environment];
    }

    public function testMethodFactoryHasResultHordehistory()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $history1 = Horde_History::factory($environment);
            $this->assertType('Horde_History', $history1);
            $history2 = Horde_History::factory($environment);
            $this->assertType('Horde_History', $history2);
        }
    }

    public function testMethodLogHasPostConditionThatTimestampAndActorAreAlwaysRecorded()
    {
        foreach ($this->getEnvironments() as $environment) {
            $history = $this->getHistory($environment);
            $history->log('test', array('action' => 'test'));
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
            } catch (InvalidArgumentException $e) {
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
            } catch (InvalidArgumentException $e) {
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
            } catch (InvalidArgumentException $e) {
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
            } catch (InvalidArgumentException $e) {
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
            } catch (InvalidArgumentException $e) {
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
            } catch (InvalidArgumentException $e) {
            }
        }
    }

    public function testMethodGetactiontimestampHasResultIntegerZeroIfGethistoryReturnsAnError()
    {
        if (!in_array(self::ENVIRONMENT_DB, $this->getEnvironments())) {
            return;
        }
        $injector = $this->initializeEnvironment(self::ENVIRONMENT_DB);
        $mock = new Dummy_Db();
        $injector->setInstance('DB_common_write', $mock);
        $history = $injector->getInstance('Horde_History');
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

    public function testHordehistorysqlConvertsPearErrorToHordeexceptions()
    {
        if (!in_array(self::ENVIRONMENT_DB, $this->getEnvironments())) {
            return;
        }
        $injector = $this->initializeEnvironment(self::ENVIRONMENT_DB);
        $mock = new Dummy_Db();
        $injector->setInstance('DB_common_write', $mock);
        $history = $injector->getInstance('Horde_History');

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

if (!class_exists('DB_common')) {
    class DB_common {}
}

/**
 * A dummy database connection producing nothing bot errors.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
class Dummy_Db extends DB_common
{
    public function &query($query, $params = array())
    {
        $e = new PEAR_Error('Error');
        return $e;
    }

    public function nextId($seq_name, $ondemand = true)
    {
        return new PEAR_Error('Error');
    }

    public function &getAll($query, $params = array(),
                            $fetchmode = DB_FETCHMODE_DEFAULT)
    {
        $e = new PEAR_Error('Error');
        return $e;
    }

    public function &getAssoc($query, $force_array = false, $params = array(),
                              $fetchmode = DB_FETCHMODE_DEFAULT, $group = false)
    {
        $e = new PEAR_Error('Error');
        return $e;
    }
}
