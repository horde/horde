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
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */

/**
 * Test the core Nag class with various backends.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If you did not
 * receive this file, see http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @category   Horde
 * @package    Nag
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/nag
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */
class Nag_Unit_Nag_Base extends Nag_TestCase
{
    static $setup;

    /**
     * The default share name expected to be used.
     *
     * @var string
     */
    protected $default_name = 'Task list of test@example.com';

    public static function setUpBeforeClass()
    {
        self::$setup->setup(
            array(
                'Horde_Prefs' => array(
                    'factory' => 'Prefs',
                    'method' => 'Null',
                    'params' => array(
                        'user' => 'test@example.com',
                        'app' => 'nag'
                    ),
                ),
                'Horde_Registry' => array(
                    'factory' => 'Registry',
                    'method' => 'Stub',
                    'params' => array(
                        'user' => 'test@example.com',
                        'app' => 'nag'
                    ),
                ),
            )
        );
        self::$setup->makeGlobal(
            array(
                'prefs' => 'Horde_Prefs',
                'registry' => 'Horde_Registry',
                'injector' => 'Horde_Injector',
            )
        );
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
