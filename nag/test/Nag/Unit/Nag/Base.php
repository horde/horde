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
    /**
     * The default share name expected to be used.
     *
     * @var string
     */
    protected $default_name = 'Task list of test@example.com';

    public static function setUpBeforeClass()
    {
        $GLOBALS['prefs'] = new Horde_Prefs('kronolith', new Horde_Prefs_Storage_Null('test@example.com'));
        $GLOBALS['registry'] = new Nag_Stub_Registry();
        parent::setUpBeforeClass();
    }

    public function setUp()
    {
        $GLOBALS['injector'] = self::getInjector();
    }

    public function tearDown()
    {
        foreach ($GLOBALS['nag_shares']->listShares('test@example.com') as $share) {
            $GLOBALS['nag_shares']->removeShare($share);
        }
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
