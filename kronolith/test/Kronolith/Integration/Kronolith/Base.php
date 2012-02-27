<?php
/**
 * Test the core Kronolith class with various backends.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Kronolith
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/kronolith
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */

/**
 * Test the core Kronolith class with various backends.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If you did not
 * receive this file, see http://www.horde.org/licenses/gpl
 *
 * @category   Horde
 * @package    Kronolith
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/kronolith
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Kronolith_Integration_Kronolith_Base extends Kronolith_TestCase
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
    protected $default_name = 'Calendar of test@example.com';

    public static function setUpBeforeClass()
    {
        self::$setup = new Horde_Test_Setup();
        self::createBasicKronolithSetup(self::$setup);
        parent::setUpBeforeClass();
    }

    public function setUp()
    {
        $GLOBALS['conf']['autoshare']['shareperms'] = 'none';
        $error = self::$setup->getError();
        if (!empty($error)) {
            $this->markTestSkipped($error);
        }
    }

    public function tearDown()
    {
        foreach ($GLOBALS['injector']->getInstance('Kronolith_Shares')->listShares('test@example.com') as $share) {
            $GLOBALS['injector']->getInstance('Kronolith_Shares')->removeShare($share);
        }
        $GLOBALS['injector']->setInstance('Kronolith_Factory_Calendars', null);
        parent::tearDown();
    }

    public function testCreateDefaultShare()
    {
        $GLOBALS['conf']['share']['auto_create'] = true;
        Kronolith::initialize();
        $this->assertEquals(1, count($GLOBALS['display_calendars']));
    }

    public function testDefaultShareName()
    {
        $GLOBALS['conf']['share']['auto_create'] = true;
        Kronolith::initialize();
        $shares = $GLOBALS['injector']->getInstance('Kronolith_Shares')->listShares('test@example.com');
        $default = array_pop($shares);
        $this->assertEquals(
            $this->default_name,
            $default->get('name')
        );
    }

    public function testNoAutoCreate()
    {
        $GLOBALS['conf']['share']['auto_create'] = false;
        Kronolith::initialize();
        $this->assertEquals(0, count($GLOBALS['display_calendars']));
    }

    public function testDefaultShareDeletePermission()
    {
        $GLOBALS['conf']['share']['auto_create'] = true;
        Kronolith::initialize();
        $shares = $GLOBALS['injector']->getInstance('Kronolith_Shares')->listShares('test@example.com');
        $default = array_pop($shares);
        $this->assertTrue(
            $default->hasPermission(
                $GLOBALS['registry']->getAuth(), Horde_Perms::DELETE
            )
        );
    }

}
