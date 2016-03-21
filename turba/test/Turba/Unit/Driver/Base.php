<?php
/**
 * Driver test base.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Turba
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/turba
 * @license    http://www.horde.org/licenses/apache Apache-like
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../TestCase.php';

/**
 * Driver test base.
 *
 * Copyright 2011-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category   Horde
 * @package    Turba
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/turba
 * @license    http://www.horde.org/licenses/apache Apache-like
 */
class Turba_Unit_Driver_Base extends Turba_TestCase
{
    /**
     * The test setup.
     *
     * @var Horde_Test_Setup
     */
    static $setup;

    /**
     * @static Turba_Driver
     */
    static $driver;

    /**
     * List of tasks added during the test.
     */
    private $_added = array();

    public static function setUpBeforeClass()
    {
        self::$setup = new Horde_Test_Setup();
        self::createBasicTurbaSetup(self::$setup);
        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass()
    {
        self::$driver = null;
        self::tearDownBasicTurbaSetup();
        self::tearDownShares();
        parent::tearDownAfterClass();
    }

    public function setUp()
    {
        $error = self::$setup->getError();
        if (!empty($error)) {
            $this->markTestSkipped($error);
        }
        $GLOBALS['injector']->setInstance('Turba_Tagger', new Turba_Tagger());
    }

    public function tearDown()
    {
        parent::tearDown();
        foreach ($this->_added as $added) {
            try {
                self::$driver->delete($added);
            } catch (Turba_Exception $e) {
            }
        }
    }

    private function _add($attributes)
    {
        $id = self::$driver->add($attributes);
        $this->_added[] = $id;
        return $id;
    }

    public function testAdd()
    {
        $id = $this->_add(array('lastname' => 'TEST'));
        $contact = self::$driver->getObject($id);
        $this->assertEquals('TEST', $contact->attributes['lastname']);
    }

    public function testNullSearch()
    {
        $this->assertInstanceOf(
            'Turba_List',
            self::$driver->search(array(), null, 'AND')
        );
    }

    public function testDuplicateDetectionFromAsWithNoEmail()
    {
        if (!class_exists('Horde_ActiveSync')){
            $this->markTestSkipped('ActiveSync not installed.');
        }
        $state = $this->getMock('Horde_ActiveSync_State_Base', array(), array(), '', false);
        $fixture = array(
            'userAgent' => 'Apple-iPad3C6/1202.435',
            'properties' => array(Horde_ActiveSync_Device::OS => 'iOS 8.1.1')
        );
        $device = new Horde_ActiveSync_Device($state, $fixture);
        $eas_obj = new Horde_ActiveSync_Message_Contact(array('device' => $device, 'protocolversion' => Horde_ActiveSync::VERSION_FOURTEEN));
        $eas_obj->firstname = 'Firstname';
        $eas_obj->fileas = 'Firstname';
        $eas_obj->homephonenumber = '+55555555';
        $hash = self::$driver->fromASContact($eas_obj);
        self::$driver->add($hash);
        $result = self::$driver->search($hash);
        $this->assertEquals(1, count($result));
    }

}
