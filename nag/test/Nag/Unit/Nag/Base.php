<?php
/**
 * Test the core Nag class with various backends.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Nag
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/nag
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */

/**
 * Test the core Nag class with various backends.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If you did not
 * receive this file, see http://www.horde.org/licenses/gpl
 *
 * @category   Horde
 * @package    Nag
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/nag
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Nag_Unit_Nag_Base extends Nag_TestCase
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
    protected $default_name = 'Task list of test@example.com';

    public static function setUpBeforeClass()
    {
        self::createBasicNagSetup(self::$setup);
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
        foreach ($GLOBALS['nag_shares']->listShares('test@example.com') as $share) {
            $GLOBALS['nag_shares']->removeShare($share);
        }
        $GLOBALS['injector']->setInstance('Nag_Factory_Tasklists', null);
        parent::tearDown();
    }

    public function testCreateDefaultShare()
    {
        $GLOBALS['conf']['share']['auto_create'] = true;
        Nag::initialize();
        $this->assertEquals(1, count($GLOBALS['display_tasklists']));
    }

    public function testDefaultShareName()
    {
        $GLOBALS['conf']['share']['auto_create'] = true;
        Nag::initialize();
        $shares = $GLOBALS['nag_shares']->listShares('test@example.com');
        $default = array_pop($shares);
        $this->assertEquals(
            $this->default_name,
            $default->get('name')
        );
    }

    public function testNoAutoCreate()
    {
        $GLOBALS['conf']['share']['auto_create'] = false;
        Nag::initialize();
        $this->assertEquals(0, count($GLOBALS['display_tasklists']));
    }

    public function testDefaultShareDeletePermission()
    {
        $GLOBALS['conf']['share']['auto_create'] = true;
        Nag::initialize();
        $shares = $GLOBALS['nag_shares']->listShares('test@example.com');
        $default = array_pop($shares);
        $this->assertTrue(
            $default->hasPermission(
                $GLOBALS['registry']->getAuth(), Horde_Perms::DELETE
            )
        );
    }

}
