<?php
/**
 * Test the core Mnemo class with various backends.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Mnemo
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/mnemo
 * @license    http://www.horde.org/licenses/apache
 */

/**
 * Test the core Mnemo class with various backends.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category   Horde
 * @package    Mnemo
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/mnemo
 * @license    http://www.horde.org/licenses/apache
 */
class Mnemo_Unit_Mnemo_Base extends Mnemo_TestCase
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
    protected $default_name = 'Notepad of test@example.com';

    public static function setUpBeforeClass()
    {
        self::createBasicMnemoSetup(self::$setup);
        parent::setUpBeforeClass();
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
        foreach ($GLOBALS['mnemo_shares']->listShares('test@example.com') as $share) {
            $GLOBALS['mnemo_shares']->removeShare($share);
        }
        $GLOBALS['injector']->setInstance('Mnemo_Factory_Notepads', null);
        parent::tearDown();
    }

    public function testCreateDefaultShare()
    {
        $GLOBALS['conf']['share']['auto_create'] = true;
        Mnemo::initialize();
        $this->assertEquals(1, count($GLOBALS['display_notepads']));
    }

    public function testDefaultShareName()
    {
        $GLOBALS['conf']['share']['auto_create'] = true;
        Mnemo::initialize();
        $shares = $GLOBALS['mnemo_shares']->listShares('test@example.com');
        $default = array_pop($shares);
        $this->assertEquals(
            $this->default_name,
            $default->get('name')
        );
    }

    public function testNoAutoCreate()
    {
        $GLOBALS['conf']['share']['auto_create'] = false;
        Mnemo::initialize();
        $this->assertEquals(0, count($GLOBALS['display_notepads']));
    }

    public function testDefaultShareDeletePermission()
    {
        $GLOBALS['conf']['share']['auto_create'] = true;
        Mnemo::initialize();
        $shares = $GLOBALS['mnemo_shares']->listShares('test@example.com');
        $default = array_pop($shares);
        $this->assertTrue(
            $default->hasPermission(
                $GLOBALS['registry']->getAuth(), Horde_Perms::DELETE
            )
        );
    }

}
