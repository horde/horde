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
require_once __DIR__ . '/../../Autoload.php';

class Passwd_Unit_Factory_DriverTest extends Passwd_TestCase
{

    protected $_backends = array();

    public function setUp()
    {
        $this->_backends = array(
            'hordeauth' => array(
                'disabled' => false,
                'name' => 'Horde',
                'preferred' => false,
                'policy' => array('minLength' => 6, 'minNumeric' => 1),
                'driver' => 'Horde',
                'params' => array()));
    }

    public function testGettingSubdriversWorks()
    {
        $driver_factory = new Passwd_Factory_Driver($this->getInjector());
        $params = array('is_subdriver' => true, 'driver' => 'horde');
        $driver = $driver_factory->create('Horde', $params);
        $this->assertInstanceOf('Passwd_Driver', $driver);
    }


    public function testGetBackendsReturnsResultOfSetBackends()
    {
        $GLOBALS['injector']    = $this->getInjector();
        $driverFactory         = new Passwd_Factory_Driver($this->getInjector());
        $driverFactory->setBackends($this->_backends);
        // The Horde Driver is not the perfect mockup but sufficient for now
        $this->assertArrayHasKey('hordeauth', $driverFactory->getBackends());
    }

    /**
     * @expectedException Passwd_Exception
     */
    public function testGetBackendsThrowsExceptionIfNoBackendIsSet()
    {
        $GLOBALS['injector']    = $this->getInjector();
        $driverFactory         = new Passwd_Factory_Driver($this->getInjector());
        $driverFactory->getBackends();
    }
    // This test is currently blocked by a static call
    public function testGettingTheSameDriverTwiceWorks()
    {
        $GLOBALS['injector']    = $this->getInjector();
        $driverFactory         = new Passwd_Factory_Driver($this->getInjector());
        // The Horde Driver is not the perfect mockup but sufficient for now
        $driverFactory->setBackends($this->_backends);
        $driver1 = $driverFactory->create('hordeauth');
        $driver2 = $driverFactory->create('hordeauth');
        $this->assertEquals($driver1, $driver2);
    }

}