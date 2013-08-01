<?php
/**
 * Test the Sql backend driver.
 *
 * @author     Ralf Lang <lang@b1-systems.de>
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @internal
 * @package    Passwd
 * @subpackage UnitTests
 */
class Passwd_Unit_Driver_SqlTest extends Passwd_TestCase
{
    private $driver;

    public function setUp()
    {
        $GLOBALS['injector'] = $this->getInjector();
        $factory = new Passwd_Factory_Driver($this->getInjector());
        $factory->backends = array();

        // Get a Horde_Db_Adapter to prevent usage of Horde_Core_Factory_Db.
        $db = new Horde_Db_Adapter_Pdo_Sqlite(array('dbname' => ':memory:'));
        $db->execute("CREATE TABLE horde_users (
            user_uid VARCHAR(255) PRIMARY KEY NOT NULL,
            user_pass VARCHAR(255) NOT NULL,
            user_soft_expiration_date INTEGER,
            user_hard_expiration_date INTEGER
        );");

        $this->driver = $factory->create('Sqlite', array(
            'driver' => 'Sql',
            'params' => array(
                'db' => $db
            ),
            'is_subdriver' => true
        ));

        $registry = $this->getMock('Horde_Registry', array(), array(), '', false);
        $registry->expects($this->any())
            ->method('get')
            ->will($this->returnValue('foo'));
        $GLOBALS['registry'] = $registry;
    }

    public function testSetup()
    {
        $this->assertInstanceOf('Passwd_Driver', $this->driver);
    }

    /**
     * @expectedException Passwd_Exception
     */
    public function testChangePasswordFailsForNonexistingUser()
    {
        $res = $this->driver->changePassword('Patricia', 'alt', 'neu');
    }

}
