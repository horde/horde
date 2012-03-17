<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Autoload.php';

/**
 * @author     Ralf Lang <lang@ralf-lang.de>
 * @license    http://www.horde.org/licenses/gpl GPL
 * @category   Horde
 * @package    Passwd
 * @subpackage UnitTests
 */

class Passwd_TestCase extends PHPUnit_Framework_TestCase
{


    /**
     * The prepared backend driver
     *
     * @var Passwd_Driver
     */

    protected $driver;

    protected function getInjector()
    {
        return new Horde_Injector(new Horde_Injector_TopLevel());
    }

    protected function getSqlDriver() 
    {
        $GLOBALS['injector']    = $this->getInjector();
        $driver_factory         = new Passwd_Factory_Driver($this->getInjector());
        // get a Horde_Db_Adapter to prevent usage of the Horde_Core_Factory_Db
        // Setup the table
        $db = new Horde_Db_Adapter_Pdo_Sqlite(array('dbname' => ':memory:'));
        $table = "CREATE TABLE horde_users ( user_uid VARCHAR(255) PRIMARY KEY NOT NULL,
                    user_pass VARCHAR(255) NOT NULL,
                    user_soft_expiration_date INTEGER,
                    user_hard_expiration_date INTEGER
                 );";
        $db->execute($table);

        $this->driver = $driver_factory->create('Sqlite', 
                                        array(
                                            'driver' => 'Sql',
                                            'params' => array(
                                                'db' => $db
                                                ),
                                            'is_subdriver' => true
                                            )
                                        );
    }

}
