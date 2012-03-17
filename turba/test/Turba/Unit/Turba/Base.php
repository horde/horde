<?php
/**
 * Test the core Turba class with various backends.
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
 * Test the core Turba class with various backends.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
class Turba_Unit_Turba_Base extends Turba_TestCase
{
    /**
     * The test setup.
     *
     * @var Horde_Test_Setup
     */
    static $setup;

    /**
     * The default share name expected to be used.
     *
     * @var string
     */
    protected $default_name = 'Address book of test@example.com';

    public static function setUpBeforeClass()
    {
        self::createBasicTurbaSetup(self::$setup);
        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass()
    {
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
    }

    public function tearDown()
    {
        $turba_shares = $injector->getInstance('Turba_Shares');
        foreach ($turba_shares->listShares('test@example.com') as $share) {
            $turba_shares->removeShare($share);
        }
        $GLOBALS['injector']->setInstance('Turba_Factory_Addressbooks', null);
        parent::tearDown();
    }

    public function testCreateDefaultShare()
    {
        $turba_shares = $injector->getInstance('Turba_Shares');
        $GLOBALS['conf']['share']['auto_create'] = true;
        Turba::getConfigFromShares(array('test' => array('use_shares' => true)));
        $this->assertEquals(
            1, count($turba_shares->listShares('test@example.com'))
        );
    }

    public function testDefaultShareName()
    {
        $turba_shares = $injector->getInstance('Turba_Shares');
        $GLOBALS['conf']['share']['auto_create'] = true;
        Turba::getConfigFromShares(array('test' => array('use_shares' => true)));
        $shares = $turba_shares->listShares('test@example.com');
        $default = array_pop($shares);
        $this->assertInstanceOf('Horde_Share_Object', $default);
        $this->assertEquals(
            $this->default_name,
            $default->get('name')
        );
    }

    public function testNoAutoCreate()
    {
        $turba_shares = $injector->getInstance('Turba_Shares');
        $GLOBALS['conf']['share']['auto_create'] = false;
        Turba::getConfigFromShares(array('test' => array('use_shares' => true)));
        $this->assertEquals(
            0, count($turba_shares->listShares('test@example.com'))
        );
    }

    public function testDefaultShareDeletePermission()
    {
        $turba_shares = $injector->getInstance('Turba_Shares');
        $GLOBALS['conf']['share']['auto_create'] = true;
        Turba::getConfigFromShares(array('test' => array('use_shares' => true)));
        $shares = $turba_shares->listShares('test@example.com');
        $default = array_pop($shares);
        $this->assertInstanceOf('Horde_Share_Object', $default);
        $this->assertTrue(
            $default->hasPermission(
                $GLOBALS['registry']->getAuth(), Horde_Perms::DELETE
            )
        );
    }

}
