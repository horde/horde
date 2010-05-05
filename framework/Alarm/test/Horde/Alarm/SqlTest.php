<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Horde_Alarm
 * @subpackage UnitTests
 */

class Horde_Alarm_SqlTest extends PHPUnit_Framework_TestCase
{
    protected static $db;
    protected static $migrator;
    protected static $alarm;

    public static function setUpBeforeClass()
    {
        // @todo: remove when we no longer depend on DB.
        error_reporting(E_ALL);

        // @fixme
        $GLOBALS['language'] = 'en_US';

        $config = getenv('ALARM_TEST_CONFIG');
        if ($config === false) {
            $config = dirname(__FILE__) . '/conf.php';
        }
        if (file_exists($config)) {
            require $config;
        }
        if (!isset($conf['alarm']['test'])) {
            $this->markTestSkipped('No configuration for Horde_Alarm test.');
            return;
        }

        $adapter = str_replace(' ', '_' , ucwords(str_replace('_', ' ', basename($conf['alarm']['test']['horde']['adapter']))));
        $class = 'Horde_Db_Adapter_' . $adapter;
        self::$db = new $class($conf['alarm']['test']['horde']);

        $logger = new Horde_Log_Logger(new Horde_Log_Handler_Null());
        self::$migrator = new Horde_Db_Migration_Migrator(self::$db, $logger, array('migrationsPath' => dirname(dirname(dirname(dirname(__FILE__)))) . '/migrations'));
        self::$migrator->up();

        self::$alarm = Horde_Alarm::factory('sql', $conf['alarm']['test']['pear']);
    }

    public static function tearDownAfterClass()
    {
        self::$migrator->down();
    }

    public function testSet()
    {
        $now = time();
        $date = new Horde_Date($now);
        $end = new Horde_Date($now + 3600);
        $hash = array('id' => 'personalalarm',
                      'user' => 'john',
                      'start' => $date,
                      'end' => $end,
                      'methods' => array(),
                      'params' => array(),
                      'title' => 'This is a personal alarm.');
        self::$alarm->set($hash);
    }

    /**
     * @depends testSet
     */
    public function testExists()
    {
        $this->assertTrue(self::$alarm->exists('personalalarm', 'john'));
    }
}
