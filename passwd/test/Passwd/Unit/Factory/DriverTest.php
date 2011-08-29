<?php
/**
 * Test the backend driver factory.
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
require_once dirname(__FILE__) . '/../../Autoload.php';

class Passwd_Unit_Factory_DriverTest extends Passwd_TestCase
{

    public function testGettingSubdriversWorks()
    {
        $driver_factory = new Passwd_Factory_Driver($this->getInjector());
        $params = array('is_subdriver' => true, 'driver' => 'horde'); 
        $driver = $driver_factory->create('Horde', $params);
        $this->assertInstanceOf('Passwd_Driver', $driver);
    }

//     This test is currently blocked by a static call
//     public function testGettingTheSameDriverTwiceWorks()
//     {
//         $GLOBALS['injector']    = $this->getInjector();
//         $driver_factory         = new Passwd_Factory_Driver($this->getInjector());
//         // The Horde Driver is not the perfect mockup but sufficient for now
//         $driver1 = $driver_factory->create('Horde');
//         $driver2 = $driver_factory->create('Horde');
//         $this->assertEquals($driver1, $driver2);
//     }

}