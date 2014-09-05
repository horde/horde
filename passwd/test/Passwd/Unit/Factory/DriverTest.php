<?php
/**
 * Test the backend driver factory.
 *
 * @author     Ralf Lang <lang@b1-systems.de>
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @internal
 * @license    http://www.horde.org/licenses/gpl GPL
 * @package    Passwd
 * @subpackage UnitTests
 */
class Passwd_Unit_Factory_DriverTest extends Passwd_TestCase
{
    protected $_backends = array();

    public function setUp()
    {
        $this->markTestIncomplete('Factories with configuration files don\'t work out of the box.');
        $this->_backends = array(
            'null' => array(
                'disabled' => false,
                'name' => 'Null',
                'driver' => 'Null',
                'policy' => array(
                    'minLength' => 6,
                    'minNumeric' => 1
                )
            )
        );
    }

    public function testGettingSubdriversWorks()
    {
        $factory = new Passwd_Factory_Driver($this->getInjector());
        $factory->backends = array();

        $driver = $factory->create('Null', array(
            'is_subdriver' => true,
            'driver' => 'Null'
        ));

        $this->assertInstanceOf('Passwd_Driver', $driver);
    }

    public function testGetBackendsReturnsResultOfSetBackends()
    {
        $GLOBALS['injector'] = $this->getInjector();
        $factory = new Passwd_Factory_Driver($this->getInjector());
        $factory->backends = $this->_backends;

        $this->assertArrayHasKey('null', $factory->backends);
    }

    // This test is currently blocked by a static call
    public function testGettingTheSameDriverTwiceWorks()
    {
        $GLOBALS['injector'] = $this->getInjector();
        $factory = new Passwd_Factory_Driver($this->getInjector());
        $factory->backends = $this->_backends;

        $driver1 = $factory->create('null');
        $driver2 = $factory->create('null');

        $this->assertEquals($driver1, $driver2);
    }

}
