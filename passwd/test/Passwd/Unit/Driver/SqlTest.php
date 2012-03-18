<?php
/**
 * Test the Sql backend driver.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Passwd
 * @subpackage UnitTests
 * @author     Ralf Lang <lang@b1-systems.de>
 * @link       http://www.horde.org/apps/passwd
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';


class Passwd_Unit_Driver_SqlTest extends Passwd_TestCase
{

    public function setUp()
    {
        $this->getSqlDriver();

    }

    public function tearDown()
    {
    }

    public function testSetup()
    {
        $driver = $this->driver;
        $this->assertInstanceOf('Passwd_Driver', $driver);
    }

    /**
     * @expectedException Passwd_Exception
     */
    public function testChangePasswordFailsForNonexistingUser()
    {
        $res = $this->driver->changePassword('Patricia', 'alt', 'neu');
    }

}